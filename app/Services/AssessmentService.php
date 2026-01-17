<?php

/**
 * File Path: app/Services/AssessmentService.php
 *
 * Assessment Service
 * Business logic layer untuk mengelola asesmen
 *
 * @package    SIB-K
 * @subpackage Services
 * @category   Service
 * @updated    2025-11-30
 */

namespace App\Services;

use App\Models\AssessmentModel;
use App\Models\AssessmentQuestionModel;
use App\Models\AssessmentAnswerModel;
use App\Models\AssessmentResultModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class AssessmentService
{
    protected AssessmentModel $assessments;
    protected AssessmentQuestionModel $questions;
    protected AssessmentAnswerModel $answers;
    protected AssessmentResultModel $results;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->assessments = new AssessmentModel();
        $this->questions   = new AssessmentQuestionModel();
        $this->answers     = new AssessmentAnswerModel();
        $this->results     = new AssessmentResultModel();
        $this->db          = \Config\Database::connect();
    }

    /* =========================================================
     * Daftar / CRUD Asesmen
     * ========================================================= */

    public function list(array $filters = []): array
    {
        $b = $this->assessments->select('*');

        if (isset($filters['type'])      && $filters['type']      !== '') {
            $b->where('assessment_type', $filters['type']);
        }
        if (isset($filters['active'])    && $filters['active']    !== '') {
            $b->where('is_active', (int) $filters['active']);
        }
        if (isset($filters['published']) && $filters['published'] !== '') {
            $b->where('is_published', (int) $filters['published']);
        }

        // Tambahan kompatibilitas:
        // - Jika kolom evaluation_mode / show_score_to_student ada, boleh difilter.
        // - Jika tidak ada, fallback memakai use_passing_score / show_result_immediately.
        if (isset($filters['evaluation_mode']) && $filters['evaluation_mode'] !== '') {
            $mode = (string) $filters['evaluation_mode'];
            if ($this->fieldExists('evaluation_mode', 'assessments')) {
                $b->where('evaluation_mode', $mode);
            } else {
                // pass_fail -> use_passing_score=1, score_only/survey -> 0
                $b->where('use_passing_score', $mode === 'pass_fail' ? 1 : 0);
            }
        }

        if (isset($filters['show_score_to_student']) && $filters['show_score_to_student'] !== '') {
            $val = (int) $filters['show_score_to_student'];
            if ($this->fieldExists('show_score_to_student', 'assessments')) {
                $b->where('show_score_to_student', $val);
            } else {
                $b->where('show_result_immediately', $val);
            }
        }

        return $b->orderBy('id', 'DESC')->findAll();
    }

    /**
     * Buat asesmen baru + (opsional) daftar pertanyaan awal.
     * Return: assessment_id
     */
    public function create(array $data, array $questionList = []): int
    {
        // Kolom non-DB yang mungkin ikut dari UI — jangan dikirim ke DB:
        unset($data['options']);

        // Pastikan created_by valid (fallback ke session)
        $uid = (int) ($data['created_by'] ?? (session('user_id') ?? 0));
        if ($uid <= 0) {
            throw new RuntimeException('created_by kosong: sesi login tidak valid.');
        }
        $data['created_by'] = $uid;

        // Normalisasi boolean/flag
        foreach ([
            'show_result_immediately',
            'allow_review',
            'is_active',
            'is_published',
            'use_passing_score',
            'show_score_to_student',
        ] as $f) {
            if (array_key_exists($f, $data)) {
                $data[$f] = $data[$f] ? 1 : 0;
            }
        }
        $data['is_published'] = (int) ($data['is_published'] ?? 0);

        // Sinkronisasi evaluation_mode <-> use_passing_score (kompat skema lama/baru)
        if (isset($data['evaluation_mode']) && (string)$data['evaluation_mode'] !== '') {
            $mode = (string) $data['evaluation_mode'];
            if ($mode === 'pass_fail') {
                $data['use_passing_score'] = 1;
            } elseif (in_array($mode, ['score_only', 'survey'], true)) {
                $data['use_passing_score'] = 0;
            }
        } else {
            // Jika evaluation_mode tidak diisi, pastikan use_passing_score ada
            if (!array_key_exists('use_passing_score', $data) || $data['use_passing_score'] === null) {
                $data['use_passing_score'] = 1; // default aman
            }
        }

        // Jika UI mengirim show_score_to_student tapi sistem lama pakai show_result_immediately
        if (array_key_exists('show_score_to_student', $data) && !array_key_exists('show_result_immediately', $data)) {
            $data['show_result_immediately'] = (int) $data['show_score_to_student'];
        }

        // Normalisasi tanggal
        foreach (['start_date', 'end_date', 'result_release_at'] as $k) {
            if (empty($data[$k])) {
                $data[$k] = null;
            }
        }

        // Normalisasi target audience
        $aud = $data['target_audience'] ?? null;

        // target_grade
        if ($aud !== 'Grade') {
            $data['target_grade'] = null;
        } else {
            $tg = trim((string)($data['target_grade'] ?? ''));
            $data['target_grade'] = ($tg === '') ? null : $tg;
        }

        // target_class_id
        if ($aud !== 'Class') {
            $data['target_class_id'] = null;
        } else {
            $cid = $data['target_class_id'] ?? null;
            $cid = ($cid === '' || $cid === null) ? null : (int)$cid;

            if ($cid !== null) {
                // Pastikan ada di tabel classes
                $exists = $this->db->table('classes')->select('id')->where('id', $cid)->get()->getFirstRow();
                if (! $exists) {
                    throw new \InvalidArgumentException('Target class tidak ditemukan.');
                }
            }
            $data['target_class_id'] = $cid;
        }

        // Counter awal sistem
        $data['total_questions']    = 0;
        $data['total_participants'] = 0;

        // Eksekusi dalam satu transaksi
        $this->db->transBegin();
        try {
            $assessmentId = (int) $this->assessments->insert($data, true);
            if (! $assessmentId) {
                $errors = $this->assessments->errors();
                $detail = $errors ? implode('; ', $errors) : 'Unknown validation/DB error';
                throw new RuntimeException('Gagal membuat asesmen: ' . $detail);
            }

            // Insert questions (urut berurutan)
            $order = 1;
            foreach ($questionList as $q) {
                $q['assessment_id'] = $assessmentId;
                $q['order_number']  = $order++;

                if (isset($q['options']) && is_array($q['options'])) {
                    $q['options'] = json_encode(
                        array_values(array_filter($q['options'], fn($v) => $v !== '' && $v !== null)),
                        JSON_UNESCAPED_UNICODE
                    );
                }
                $this->questions->insert($q);
            }

            // Update total_questions
            $totalQ = $this->questions->where('assessment_id', $assessmentId)->countAllResults();
            $this->assessments->update($assessmentId, ['total_questions' => $totalQ]);

            $this->db->transCommit();
            return $assessmentId;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function updateAssessment(int $id, array $data): void
    {
        // Normalisasi boolean
        $data['show_result_immediately'] = (int) ($data['show_result_immediately'] ?? 0);
        $data['allow_review']            = (int) ($data['allow_review'] ?? 0);
        $data['is_active']               = (int) ($data['is_active'] ?? 0);
        $data['is_published']            = (int) ($data['is_published'] ?? 0);

        if (array_key_exists('use_passing_score', $data)) {
            $data['use_passing_score'] = (int) ($data['use_passing_score'] ?? 0);
        }
        if (array_key_exists('show_score_to_student', $data)) {
            $data['show_score_to_student'] = (int) ($data['show_score_to_student'] ?? 0);
            // kompat: mapping ke field lama
            if (!array_key_exists('show_result_immediately', $data)) {
                $data['show_result_immediately'] = $data['show_score_to_student'];
            }
        }

        // Sinkronisasi evaluation_mode <-> use_passing_score
        if (isset($data['evaluation_mode']) && (string)$data['evaluation_mode'] !== '') {
            $mode = (string) $data['evaluation_mode'];
            if ($mode === 'pass_fail') {
                $data['use_passing_score'] = 1;
            } elseif (in_array($mode, ['score_only', 'survey'], true)) {
                $data['use_passing_score'] = 0;
            }
        }

        // Normalisasi tanggal
        foreach (['start_date','end_date','result_release_at'] as $k) {
            if (array_key_exists($k, $data) && empty($data[$k])) {
                $data[$k] = null;
            }
        }

        // Normalisasi target
        $aud = $data['target_audience'] ?? null;

        // target_grade
        if ($aud !== 'Grade') {
            $data['target_grade'] = null;
        } else {
            $tg = trim((string)($data['target_grade'] ?? ''));
            $data['target_grade'] = ($tg === '') ? null : $tg;
        }

        // target_class_id
        if ($aud !== 'Class') {
            $data['target_class_id'] = null;
        } else {
            $cid = $data['target_class_id'] ?? null;
            $cid = ($cid === '' || $cid === null) ? null : (int)$cid;

            if ($cid !== null) {
                // Pastikan ada di tabel classes
                $exists = $this->db->table('classes')->select('id')->where('id', $cid)->get()->getFirstRow();
                if (! $exists) {
                    throw new \InvalidArgumentException('Target class tidak ditemukan.');
                }
            }
            $data['target_class_id'] = $cid;
        }

        // Jangan kirim kolom non-DB
        unset($data['options']);

        $this->assessments->update($id, $data);
    }

    public function toggleActive(int $id): void
    {
        $ass = $this->assessments->asArray()->find($id);
        if (! $ass) {
            return;
        }
        $this->assessments->update($id, ['is_active' => ($ass['is_active'] ? 0 : 1)]);
    }

    public function publish(int $id, bool $publish = true): void
    {
        $this->assessments->update($id, ['is_published' => $publish ? 1 : 0]);
    }

    /* =========================================================
     * Target & Jadwal
     * ========================================================= */

    public function assignTarget(
        int $assessmentId,
        string $audience,
        ?int $classId,
        ?string $grade,
        ?string $startDate,
        ?string $endDate
    ): void {
        $payload = [
            'target_audience' => $audience,
            'target_class_id' => $audience === 'Class' ? $classId : null,
            'target_grade'    => $audience === 'Grade' ? $grade : null,
            'start_date'      => $startDate ?: null,
            'end_date'        => $endDate ?: null,
        ];
        $this->assessments->update($assessmentId, $payload);
    }

    /* =========================================================
     * Pertanyaan
     * ========================================================= */

    public function addQuestion(int $assessmentId, array $qData): int
    {
        // Encode options ke JSON text (kolom TEXT “JSON array”)
        if (isset($qData['options']) && is_array($qData['options'])) {
            $qData['options'] = json_encode(
                array_values(array_filter($qData['options'], fn($v) => $v !== '' && $v !== null)),
                JSON_UNESCAPED_UNICODE
            );
        }

        // Cari max order via DB langsung (robust)
        $row = $this->db->table('assessment_questions')
            ->selectMax('order_number')
            ->where('assessment_id', $assessmentId)
            ->get()->getRow();
        $maxOrder = (int)($row->order_number ?? 0);

        $qData['assessment_id'] = $assessmentId;
        $qData['order_number']  = $maxOrder + 1;

        $this->questions->insert($qData);

        $totalQ = $this->questions->where('assessment_id', $assessmentId)->countAllResults();
        $this->assessments->update($assessmentId, ['total_questions' => $totalQ]);

        return (int) $this->questions->getInsertID();
    }

    /**
     * Update konten pertanyaan (tanpa memaksa order_number).
     * Reorder ditangani di Controller via $qModel->moveToOrder().
     */
    public function updateQuestion(int $questionId, array $data): array
    {
        $m = new AssessmentQuestionModel();

        // Normalisasi & pemetaan field
        $options = $data['options'] ?? [];
        if (is_string($options)) {
            $dec = json_decode($options, true);
            $options = is_array($dec) ? $dec : [];
        }
        if (!is_array($options)) {
            $options = [];
        }
        $options = array_values(array_filter(array_map('strval', $options), static function($v) {
            return trim($v) !== '';
        }));

        $payload = [
            'question_text'  => trim((string)($data['question_text'] ?? '')),
            'question_type'  => (string)($data['question_type'] ?? 'Multiple Choice'),
            'points'         => max(0, (float)($data['points'] ?? 1)),
            'is_required'    => !empty($data['is_required']) ? 1 : 0,
            'correct_answer' => ($data['correct_answer'] ?? null) !== '' ? (string)$data['correct_answer'] : null,
            'dimension'      => ($data['dimension'] ?? null) !== '' ? (string)$data['dimension'] : null,
            'image_url'      => ($data['image_url'] ?? null) !== '' ? (string)$data['image_url'] : null,
            'explanation'    => ($data['explanation'] ?? null) !== '' ? (string)$data['explanation'] : null,
            'options'        => json_encode($options, JSON_UNESCAPED_UNICODE),
        ];

        try {
            if (! $m->update($questionId, $payload)) {
                return ['success' => false, 'message' => 'Gagal menyimpan perubahan pertanyaan.'];
            }
            return ['success' => true, 'message' => 'Pertanyaan diperbarui.'];
        } catch (\Throwable $e) {
            log_message('error', 'updateQuestion failed: '.$e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan.'];
        }
    }

    /**
     * Hapus pertanyaan + rapikan order dan total_questions.
     */
    public function removeQuestion(int $questionId): array
    {
        try {
            $q = $this->questions->asArray()->find($questionId);
            if (! $q) {
                return ['success' => false, 'message' => 'Pertanyaan tidak ditemukan.'];
            }

            $assessmentId = (int) $q['assessment_id'];

            $this->db->transBegin();

            $this->questions->delete($questionId);

            // Rapikan order menjadi 1..N
            if (method_exists($this->questions, 'normalizeOrders')) {
                $this->questions->normalizeOrders($assessmentId);
            } else {
                $rows = $this->db->table('assessment_questions')
                    ->where('assessment_id', $assessmentId)
                    ->orderBy('order_number', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get()->getResultArray();

                $ord = 1;
                foreach ($rows as $row) {
                    $this->db->table('assessment_questions')
                        ->where('id', (int)$row['id'])
                        ->update(['order_number' => $ord++]);
                }
            }

            // Update total_questions
            $total = $this->questions->where('assessment_id', $assessmentId)->countAllResults();
            $this->assessments->update($assessmentId, ['total_questions' => $total]);

            $this->db->transCommit();
            return ['success' => true, 'message' => 'Pertanyaan dihapus.'];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'removeQuestion failed: '.$e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus pertanyaan.'];
        }
    }

    /* =========================================================
     * PENUGASAN (Assigned) — Counselor side
     * ========================================================= */

    /** Gunakan tabel assessment_assignees jika tersedia. */
    protected function hasAssigneesTable(): bool
    {
        // CI4 biasanya punya tableExists / listTables tergantung driver
        if (method_exists($this->db, 'tableExists')) {
            return (bool) $this->db->tableExists('assessment_assignees');
        }
        if (method_exists($this->db, 'listTables')) {
            $tables = $this->db->listTables();
            return is_array($tables) && in_array('assessment_assignees', $tables, true);
        }
        return false;
    }

    /**
     * Assign sejumlah siswa ke sebuah asesmen.
     * - Bila tabel assessment_assignees ada: simpan di sana (hindari duplikat).
     * - Selalu pastikan ada baris "Assigned" di assessment_results (idempotent).
     * @return array{success:bool,message:string,data:array}
     */
    public function assignStudents(int $assessmentId, array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_map('intval', array_filter($studentIds, fn($v)=>$v !== null))));
        if (empty($studentIds)) {
            return ['success'=>false,'message'=>'Daftar siswa kosong.','data'=>[]];
        }

        $ass = $this->assessments->asArray()->find($assessmentId);
        if (! $ass) {
            return ['success'=>false,'message'=>'Asesmen tidak ditemukan.','data'=>[]];
        }

        $createdAssigned   = 0;
        $insertedAssignee  = 0;

        $this->db->transBegin();

        try {
            // 1) Simpan ke tabel assignees bila tersedia
            if ($this->hasAssigneesTable()) {
                $existRows = $this->db->table('assessment_assignees')
                    ->select('student_id')
                    ->where('assessment_id', $assessmentId)
                    ->whereIn('student_id', $studentIds)
                    ->get()->getResultArray();

                $existing = array_map(fn($r)=>(int)$r['student_id'], $existRows);
                $toInsert = array_values(array_diff($studentIds, $existing));

                foreach ($toInsert as $sid) {
                    $this->db->table('assessment_assignees')->insert([
                        'assessment_id' => $assessmentId,
                        'student_id'    => $sid,
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                    $insertedAssignee++;
                }
            }

            // 2) Pastikan ada row Assigned di assessment_results (idempotent)
            foreach ($studentIds as $sid) {
                $rid = $this->results->createAssignment($assessmentId, (int)$sid);
                if ($rid) {
                    $createdAssigned++;
                }
            }

            $this->db->transCommit();

            return [
                'success'=>true,
                'message'=>'Penugasan siswa berhasil.',
                'data'=>[
                    'assignees_inserted' => $insertedAssignee,
                    'assigned_results'   => $createdAssigned,
                ]
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error','assignStudents error: '.$e->getMessage());
            return ['success'=>false,'message'=>'Gagal menugaskan siswa: '.$e->getMessage(),'data'=>[]];
        }
    }

    /**
     * Cabut penugasan siswa.
     * - Hapus dari assessment_assignees (bila ada).
     * - Hapus assessment_results yang statusnya masih "Assigned".
     */
    public function revokeAssignments(int $assessmentId, array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_map('intval', array_filter($studentIds, fn($v)=>$v !== null))));
        if (empty($studentIds)) {
            return ['success'=>false,'message'=>'Daftar siswa kosong.','data'=>[]];
        }

        $removedAssignee        = 0;
        $removedAssignedResults = 0;

        $this->db->transBegin();

        try {
            if ($this->hasAssigneesTable()) {
                $this->db->table('assessment_assignees')
                    ->where('assessment_id', $assessmentId)
                    ->whereIn('student_id', $studentIds)
                    ->delete();
                $removedAssignee = $this->db->affectedRows();
            }

            $this->db->table('assessment_results')
                ->where('assessment_id', $assessmentId)
                ->whereIn('student_id', $studentIds)
                ->where('status', 'Assigned')
                ->delete();
            $removedAssignedResults = $this->db->affectedRows();

            $this->db->transCommit();

            return [
                'success'=>true,
                'message'=>'Penugasan dicabut.',
                'data'=>[
                    'assignees_removed' => $removedAssignee,
                    'assigned_removed'  => $removedAssignedResults,
                ]
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error','revokeAssignments error: '.$e->getMessage());
            return ['success'=>false,'message'=>'Gagal mencabut penugasan: '.$e->getMessage(),'data'=>[]];
        }
    }

    /**
     * Pastikan semua baris di assessment_assignees memiliki entry "Assigned" di assessment_results.
     */
    public function syncAssignmentsToResults(int $assessmentId): array
    {
        if (! $this->hasAssigneesTable()) {
            return ['success'=>false,'message'=>'Tabel assessment_assignees tidak tersedia.','data'=>[]];
        }

        $rows = $this->db->table('assessment_assignees')
            ->select('student_id')
            ->where('assessment_id', $assessmentId)
            ->get()->getResultArray();

        $studentIds = array_map(fn($r)=>(int)$r['student_id'], $rows);
        $created    = 0;

        $this->db->transBegin();
        try {
            foreach ($studentIds as $sid) {
                $rid = $this->results->createAssignment($assessmentId, $sid);
                if ($rid) {
                    $created++;
                }
            }

            $this->db->transCommit();
            return ['success'=>true,'message'=>'Sinkronisasi selesai.','data'=>['created_assigned'=>$created]];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error','syncAssignmentsToResults error: '.$e->getMessage());
            return ['success'=>false,'message'=>'Gagal sinkronisasi: '.$e->getMessage(),'data'=>[]];
        }
    }

    /**
     * Map siswa yang sudah berelasi dengan asesmen.
     * Dianggap "sudah ditugaskan" jika:
     *  - Ada di assessment_assignees (jika tabel ada), ATAU
     *  - Sudah punya baris di assessment_results (status apa pun) dan belum soft-deleted.
     * Return: [student_id => true]
     */
    public function getAssignedMap(int $assessmentId): array
    {
        $map = [];

        // 1) Dari tabel assignees (jika ada)
        if ($this->hasAssigneesTable()) {
            try {
                $rows = $this->db->table('assessment_assignees')
                    ->distinct()
                    ->select('student_id')
                    ->where('assessment_id', $assessmentId)
                    ->get()
                    ->getResultArray();

                foreach ($rows as $r) {
                    $sid = (int)($r['student_id'] ?? 0);
                    if ($sid > 0) {
                        $map[$sid] = true;
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan jika struktur berbeda
            }
        }

        // 2) Dari assessment_results: SEMUA status non-deleted
        $rows2 = $this->db->table('assessment_results')
            ->distinct()
            ->select('student_id')
            ->where('assessment_id', $assessmentId)
            ->where('deleted_at', null) // IS NULL
            ->get()
            ->getResultArray();

        foreach ($rows2 as $r) {
            $sid = (int)($r['student_id'] ?? 0);
            if ($sid > 0) {
                $map[$sid] = true;
            }
        }

        return $map;
    }

    /* =========================================================
     * Alur siswa: start → jawab → finalize → review
     * ========================================================= */

    /** Hitung total poin maksimum asesmen. */
    public function calculateMaxScore(int $assessmentId): float
    {
        $rows = $this->questions->select('points')->where('assessment_id', $assessmentId)->findAll();
        return array_reduce($rows, fn($c, $r) => $c + (float) ($r['points'] ?? 0), 0.0);
    }

    /**
     * Buat kerangka result "In Progress".
     * Catatan: Jika AssessmentResultModel memiliki startAssessment(), gunakan itu (lebih idempotent).
     */
    public function startResult(int $assessmentId, int $studentId, int $attempt = 1, ?string $ip = null, ?string $ua = null): int
    {
        // Lebih aman: gunakan mekanisme model (menghindari dobel in-progress)
        if (method_exists($this->results, 'startAssessment')) {
            $rid = $this->results->startAssessment($assessmentId, $studentId);
            $rid = (int) $rid;

            if ($rid > 0 && ($ip || $ua)) {
                $payload = [];
                if ($ip) $payload['ip_address'] = $ip;
                if ($ua) $payload['user_agent'] = $ua;
                if ($payload) {
                    $this->results->update($rid, $payload);
                }
            }
            return $rid;
        }

        // Fallback (jaga kompat lama)
        $totalQ   = (int) ($this->assessments->asArray()->find($assessmentId)['total_questions'] ?? 0);
        $maxScore = $this->calculateMaxScore($assessmentId);

        $insert = [
            'assessment_id'      => $assessmentId,
            'student_id'         => $studentId,
            'attempt_number'     => $attempt,
            'status'             => 'In Progress',
            'total_score'        => 0,
            'max_score'          => $maxScore,
            'percentage'         => null,
            'is_passed'          => null,
            'questions_answered' => 0,
            'total_questions'    => $totalQ,
            'correct_answers'    => null,
            'started_at'         => date('Y-m-d H:i:s'),
            'ip_address'         => $ip,
            'user_agent'         => $ua,
        ];

        $resultId = (int) $this->results->insert($insert, true);

        // Tambah counter partisipan
        $this->assessments->set('total_participants', 'total_participants+1', false)
            ->where('id', $assessmentId)->update();

        return $resultId;
    }

    /**
     * Simpan jawaban sebuah pertanyaan.
     * Perbaikan penting:
     * - Idempotent per (student_id + question_id + result_id) sehingga tidak dobel row.
     * - Progress (questions_answered & correct_answers) dihitung ulang dari DB (akurasi saat update).
     */
    public function submitAnswer(int $resultId, int $questionId, array $answer, ?int $studentId = null, ?int $spent = null): void
    {
        $q = $this->questions->asArray()->find($questionId);
        if (! $q) {
            return;
        }

        // Pastikan student_id ada
        $sid = (int) ($studentId ?? 0);
        if ($sid <= 0) {
            $r = $this->results->asArray()->find($resultId);
            $sid = (int) ($r['student_id'] ?? 0);
        }
        if ($sid <= 0) {
            return;
        }

        $type = (string) ($q['question_type'] ?? 'Essay');

        // Cari existing agar idempotent
        $existing = $this->answers->getStudentAnswer($sid, $questionId, $resultId);
        $answerId = $existing ? (int) ($existing['id'] ?? 0) : 0;

        // Payload dasar
        $payload = [
            'question_id'        => $questionId,
            'student_id'         => $sid,
            'result_id'          => $resultId,
            'answered_at'        => date('Y-m-d H:i:s'),
            'time_spent_seconds' => $spent,
        ];

        // Isi jawaban sesuai tipe
        if (in_array($type, ['Multiple Choice', 'True/False'], true)) {
            $payload['answer_option']  = $answer['option'] ?? null;
            $payload['is_auto_graded'] = 0; // akan diisi saat autograde sukses
        } elseif ($type === 'Rating Scale') {
            // rating scale: bisa dianggap "survey" atau numeric score.
            $payload['answer_option']  = $answer['option'] ?? null;
            $payload['is_auto_graded'] = 1;

            // jika numeric: clamp 0..points (agar tidak liar)
            $points = (float) ($q['points'] ?? 0);
            $opt    = $payload['answer_option'];
            if ($opt !== null && $opt !== '' && is_numeric($opt)) {
                $val = (float) $opt;
                if ($points > 0) {
                    $val = max(0, min($points, $val));
                }
                $payload['score']      = $val;
                $payload['graded_at']  = date('Y-m-d H:i:s');
                $payload['is_correct'] = null;
            }
        } elseif ($type === 'Checkbox') {
            // multi-select: simpan sebagai array; model akan encode JSON
            $payload['answer_options'] = $answer['options'] ?? [];
            $payload['is_auto_graded'] = 0; // akan diisi saat autograde sukses
        } else {
            // Essay / tipe teks
            $payload['answer_text']    = $answer['text'] ?? null;
            $payload['is_auto_graded'] = 0;
        }

        // Insert atau update
        if ($answerId > 0) {
            $this->answers->update($answerId, $payload);
        } else {
            $answerId = (int) $this->answers->insert($payload, true);
        }

        // Auto-grade untuk MC/TF/Checkbox bila memungkinkan (Rating Scale ditangani di atas)
        if ($answerId > 0 && in_array($type, ['Multiple Choice', 'True/False', 'Checkbox'], true)) {
            if (method_exists($this->answers, 'autoGradeAnswer')) {
                // autoGradeAnswer akan skip jika tidak ada kunci jawaban
                $this->answers->autoGradeAnswer($answerId);
            }
        }

        // Update progress hasil secara akurat (hindari salah hitung saat update jawaban)
        $row = $this->db->table('assessment_answers')
            ->select('COUNT(*) as answered,
                      SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->where('result_id', $resultId)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if ($row) {
            $this->results->update($resultId, [
                'questions_answered' => (int) ($row['answered'] ?? 0),
                'correct_answers'    => (int) ($row['correct'] ?? 0),
            ]);
        }
    }

    /**
     * Kembalikan status hasil dari "Graded" ke "Completed"
     * tanpa menghapus nilai & catatan yang sudah ada.
     */
    public function ungradeResult(int $resultId): array
    {
        $row = $this->results->asArray()->find($resultId);

        if (! $row) {
            return [
                'success' => false,
                'message' => 'Hasil asesmen tidak ditemukan.',
            ];
        }

        $ok = $this->results->update($resultId, [
            'status'    => 'Completed',
            'graded_at' => null,
        ]);

        return [
            'success' => (bool) $ok,
            'message' => $ok
                ? 'Status dinilai dibatalkan. Hasil kembali ke status "Selesai".'
                : 'Gagal mengubah status hasil asesmen.',
        ];
    }

    /**
     * Finalisasi satu result: set status Completed + hitung nilai ringkas.
     * Mode dinurunkan dari use_passing_score (tanpa mengandalkan evaluation_mode):
     * - use_passing_score = 1 → pass_fail
     * - use_passing_score = 0 → score_only
     * - Jika max_score <= 0     → survey (tanpa persentase)
     */
    public function finalizeResult(int $resultId, ?int $timeSpent = null, ?float $passing = null): void
    {
        $r = $this->results->asArray()->find($resultId);
        if (! $r) {
            return;
        }

        // Info asesmen minimal
        $asm = $this->db->table('assessments')
            ->select('passing_score, use_passing_score')
            ->where('id', (int)$r['assessment_id'])
            ->get()->getRowArray();

        $usePass      = (int)($asm['use_passing_score'] ?? 1) === 1;
        $passingScore = $passing ?? ($asm['passing_score'] ?? null);

        // Total score dari answers
        $rows = $this->answers->select('score')->where('result_id', $resultId)->findAll();
        $sum  = array_reduce($rows, fn($c, $row) => $c + (float) ($row['score'] ?? 0), 0.0);

        // Jika max_score <= 0, anggap survey
        $isSurvey = ((float)($r['max_score'] ?? 0)) <= 0;

        if ($isSurvey) {
            $percentage = null;
            $isPassed   = null;
        } else {
            $percentage = ($r['max_score'] > 0)
                ? round(($sum / (float) $r['max_score']) * 100, 2)
                : null;

            $isPassed   = $usePass && $percentage !== null && $passingScore !== null
                ? (int) ($percentage >= (float)$passingScore)
                : null;
        }

        $this->results->update($resultId, [
            'total_score'        => $isSurvey ? null : $sum,
            'percentage'         => $percentage,
            'is_passed'          => $isPassed,
            'status'             => 'Completed',
            'completed_at'       => date('Y-m-d H:i:s'),
            'time_spent_seconds' => $timeSpent,
        ]);
    }

    /**
     * Hitung ulang total_score, percentage, is_passed, correct_answers untuk satu result.
     * Tidak mengubah status (kecuali disuruh oleh pemanggil).
     */
    public function recalculateResult(int $resultId): void
    {
        $r = $this->results->asArray()->find($resultId);
        if (! $r) {
            return;
        }

        // Ambil answers
        $answers = $this->answers->asArray()->where('result_id', $resultId)->findAll();

        $sum      = 0.0;
        $cCorrect = 0;
        foreach ($answers as $a) {
            $sum += (float) ($a['score'] ?? 0);
            if (isset($a['is_correct']) && (string)$a['is_correct'] === '1') {
                $cCorrect++;
            }
        }

        // Info asesmen untuk passing
        $asm = $this->db->table('assessments')
            ->select('passing_score, use_passing_score')
            ->where('id', (int)$r['assessment_id'])
            ->get()->getRowArray();

        $usePass      = (int)($asm['use_passing_score'] ?? 1) === 1;
        $passingScore = $asm['passing_score'] ?? null;

        $isSurvey   = ((float)($r['max_score'] ?? 0)) <= 0;
        $percentage = $isSurvey
            ? null
            : (($r['max_score'] > 0) ? round(($sum / (float)$r['max_score']) * 100, 2) : null);

        $isPassed   = $isSurvey
            ? null
            : ($usePass && $percentage !== null && $passingScore !== null
                ? (int)($percentage >= (float)$passingScore)
                : null);

        $this->results->update($resultId, [
            'total_score'     => $isSurvey ? null : $sum,
            'percentage'      => $percentage,
            'is_passed'       => $isPassed,
            'correct_answers' => $cCorrect,
        ]);
    }

    /**
     * Tandai sebuah result sebagai "Graded".
     */
    public function markGraded(int $resultId): void
    {
        $this->results->update($resultId, [
            'status'    => 'Graded',
            'graded_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Review & interpretasi hasil oleh Guru BK.
     * Kolom JSON dimension_scores akan di-encode oleh service ini.
     * (Back-compat: method ini juga men-set status "Graded")
     */
    public function reviewResult(int $resultId, int $counselorId, array $review): void
    {
        $payload = [
            'interpretation'   => $review['interpretation']  ?? null,
            'dimension_scores' => isset($review['dimension_scores'])
                ? json_encode($review['dimension_scores'], JSON_UNESCAPED_UNICODE)
                : null,
            'recommendations'  => $review['recommendations'] ?? null,
            'counselor_notes'  => $review['counselor_notes'] ?? null,
            'reviewed_by'      => $counselorId,
            'reviewed_at'      => date('Y-m-d H:i:s'),
            'graded_at'        => date('Y-m-d H:i:s'),
            'status'           => 'Graded',
        ];
        $this->results->update($resultId, $payload);
    }

    /**
     * API baru: Submit review + finalisasi grading dalam satu langkah.
     * - Simpan interpretasi/rekomendasi/catatan/skor dimensi
     * - Recalculate total
     * - Set status "Graded"
     * Return: array{success:bool,message:string}
     */
    public function submitGrade(int $resultId, array $review): array
    {
        try {
            $row = $this->results->asArray()->find($resultId);
            if (! $row) {
                return ['success' => false, 'message' => 'Hasil asesmen tidak ditemukan'];
            }

            // Ambil identitas reviewer (Guru BK) dari auth/session
            $reviewerId = 0;
            if (function_exists('auth_user')) {
                $u          = auth_user();
                $reviewerId = (int) ($u['id'] ?? 0);
            }
            if ($reviewerId <= 0) {
                $reviewerId = (int) (session('user_id') ?? 0);
            }

            // Simpan meta review
            $payload = [
                'interpretation'   => $review['interpretation']  ?? null,
                'dimension_scores' => isset($review['dimension_scores'])
                    ? json_encode($review['dimension_scores'], JSON_UNESCAPED_UNICODE)
                    : null,
                'recommendations'  => $review['recommendations'] ?? null,
                'counselor_notes'  => $review['counselor_notes'] ?? null,
                'reviewed_at'      => date('Y-m-d H:i:s'),
            ];

            // Isi kolom reviewed_by kalau berhasil dapat ID reviewer
            if ($reviewerId > 0) {
                $payload['reviewed_by'] = $reviewerId;
            }

            $this->results->update($resultId, $payload);

            // Hitung ulang nilai ringkas
            $this->recalculateResult($resultId);

            // Tandai graded
            $this->markGraded($resultId);

            return ['success' => true, 'message' => 'Review & nilai tersimpan.'];
        } catch (\Throwable $e) {
            log_message('error', 'submitGrade error: '.$e->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan review & nilai.'];
        }
    }

    /**
     * Penilaian manual satu jawaban (semua tipe).
     * Bisa dipanggil dengan:
     *  - gradeAnswer($answerId, 3.5, $userId, 'Good');  // kompat lama
     *  - gradeAnswer($answerId, ['score'=>3.5,'is_correct'=>1,'feedback'=>'OK'], $userId);
     * Mengatur is_auto_graded=0 jika ada override.
     */
    public function gradeAnswer(int $answerId, $scoreOrPayload, int $gradedBy, ?string $feedback = null): array
    {
        try {
            // Bentuk payload fleksibel
            if (is_array($scoreOrPayload)) {
                $score     = array_key_exists('score', $scoreOrPayload) ? $scoreOrPayload['score'] : null;
                $isCorrect = array_key_exists('is_correct', $scoreOrPayload) ? $scoreOrPayload['is_correct'] : null;
                $fb        = array_key_exists('feedback', $scoreOrPayload) ? $scoreOrPayload['feedback'] : $feedback;
            } else {
                $score     = $scoreOrPayload;
                $isCorrect = null;
                $fb        = $feedback;
            }

            $data = [
                'graded_by'      => $gradedBy,
                'graded_at'      => date('Y-m-d H:i:s'),
                'is_auto_graded' => 0,
            ];

            if ($score !== null && $score !== '') {
                $data['score'] = (float) $score;
            }
            if ($isCorrect === 0 || $isCorrect === '0' || $isCorrect === 1 || $isCorrect === '1') {
                $data['is_correct'] = (int) $isCorrect;
            }
            if ($fb !== null) {
                $data['feedback'] = (string) $fb;
            }

            // Jika model punya method khusus, gunakan (signature 4 argumen)
            if (method_exists($this->answers, 'manualGradeAnswer')) {
                $ok = $this->answers->manualGradeAnswer(
                    $answerId,
                    (float)($data['score'] ?? 0),
                    $gradedBy,
                    $data['feedback'] ?? null
                );
                if (! $ok) {
                    throw new \Exception('Gagal menilai jawaban');
                }

                // Bila caller kirim is_correct, set via update terpisah
                if (array_key_exists('is_correct', $data)) {
                    $this->db->table('assessment_answers')
                        ->where('id', $answerId)
                        ->update(['is_correct' => (int)$data['is_correct']]);
                }
            } else {
                // Fallback: update kolom langsung
                $ok = $this->db->table('assessment_answers')->where('id', $answerId)->update($data);
                if (! $ok) {
                    throw new \Exception('Gagal menilai jawaban');
                }
            }

            // Recalc ringkas result
            $answer = $this->db->table('assessment_answers')->where('id', $answerId)->get()->getRowArray();
            if ($answer && !empty($answer['result_id'])) {
                if (method_exists($this->results, 'calculateScore')) {
                    $this->results->calculateScore((int) $answer['result_id']);
                } else {
                    $this->recalculateResult((int)$answer['result_id']);
                }
            }

            return ['success' => true, 'message' => 'Jawaban berhasil dinilai', 'data' => ['answer_id' => (int) $answerId]];
        } catch (\Throwable $e) {
            log_message('error', 'Error grading answer: '.$e->getMessage());
            return ['success' => false, 'message' => 'Gagal menilai jawaban: '.$e->getMessage(), 'data' => null];
        }
    }

    /* =========================================================
     * Publikasi & utilitas
     * ========================================================= */

    public function publishAssessment(int $assessmentId): array
    {
        try {
            $assessment = $this->db->table('assessments')->where('id', $assessmentId)->get()->getRowArray();
            if (! $assessment) {
                throw new \Exception('Asesmen tidak ditemukan');
            }

            // Hitung jumlah pertanyaan
            if (method_exists($this->questions, 'countByAssessment')) {
                $questionCount = $this->questions->countByAssessment($assessmentId);
            } else {
                $questionCount = (int) $this->db->table('assessment_questions')
                    ->where('assessment_id', $assessmentId)
                    ->countAllResults();
            }
            if ($questionCount === 0) {
                throw new \Exception('Tidak dapat mempublikasi asesmen tanpa pertanyaan');
            }

            $ok = $this->assessments->update($assessmentId, ['is_published' => 1]);
            if (! $ok) {
                throw new \Exception('Gagal mempublikasi asesmen');
            }

            return [
                'success' => true,
                'message' => 'Asesmen berhasil dipublikasi',
                'data'    => ['assessment_id' => (int) $assessmentId],
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Error publishing assessment: '.$e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal mempublikasi asesmen: '.$e->getMessage(),
                'data'    => null,
            ];
        }
    }

    public function unpublishAssessment(int $assessmentId): array
    {
        try {
            $ok = $this->assessments->update($assessmentId, ['is_published' => 0]);
            if (! $ok) {
                throw new \Exception('Gagal membatalkan publikasi asesmen');
            }

            return [
                'success' => true,
                'message' => 'Publikasi asesmen dibatalkan',
                'data'    => ['assessment_id' => (int) $assessmentId],
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Error unpublishing assessment: '.$e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal membatalkan publikasi asesmen: '.$e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Data ringkas untuk dashboard modul Asesmen.
     */
    public function getDashboardData(): array
    {
        try {
            $totalAssessments = (int) $this->db->table('assessments')->countAllResults();
            $totalQuestions   = (int) $this->db->table('assessment_questions')->countAllResults();
            $published        = (int) $this->db->table('assessments')->where('is_published', 1)->countAllResults();
            $active           = (int) $this->db->table('assessments')->where('is_active', 1)->countAllResults();

            return [
                'success' => true,
                'message' => 'OK',
                'data'    => compact('totalAssessments', 'totalQuestions', 'published', 'active'),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Error getting dashboard data: '.$e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal mengambil data dashboard: '.$e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Duplikasi asesmen (+pertanyaan).
     */
    public function duplicateAssessment(int $assessmentId, int $createdBy): array
    {
        $this->db->transBegin();
        try {
            $assessment = $this->db->table('assessments')->where('id', $assessmentId)->get()->getRowArray();
            if (! $assessment) {
                throw new \Exception('Asesmen tidak ditemukan');
            }

            unset(
                $assessment['id'],
                $assessment['created_at'],
                $assessment['updated_at'],
                $assessment['deleted_at']
            );

            $assessment['title']              = ($assessment['title'] ?? 'Asesmen').' (Salinan)';
            $assessment['created_by']         = $createdBy;
            $assessment['is_published']       = 0;
            $assessment['is_active']          = 0;
            $assessment['total_participants'] = 0;

            $newAssessmentId = (int) $this->assessments->insert($assessment, true);
            if (! $newAssessmentId) {
                throw new \Exception('Gagal menduplikasi asesmen');
            }

            // Ambil pertanyaan asal
            if (method_exists($this->questions, 'getByAssessment')) {
                $questions = $this->questions->getByAssessment($assessmentId) ?? [];
            } else {
                $questions = $this->questions
                    ->where('assessment_id', $assessmentId)
                    ->orderBy('order_number', 'ASC')
                    ->findAll();
            }

            foreach ($questions as $q) {
                if (method_exists($this->questions, 'duplicateQuestion')) {
                    $this->questions->duplicateQuestion((int) $q['id'], $newAssessmentId);
                    continue;
                }

                // Fallback duplikasi manual
                $copy = $q;
                unset(
                    $copy['id'],
                    $copy['created_at'],
                    $copy['updated_at'],
                    $copy['deleted_at']
                );
                $copy['assessment_id'] = $newAssessmentId;

                if (isset($copy['options']) && is_array($copy['options'])) {
                    $copy['options'] = json_encode($copy['options'], JSON_UNESCAPED_UNICODE);
                }

                $this->questions->insert($copy);
            }

            // Update total_questions pada salinan
            $totalQ = $this->questions->where('assessment_id', $newAssessmentId)->countAllResults();
            $this->assessments->update($newAssessmentId, ['total_questions' => $totalQ]);

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Asesmen berhasil diduplikasi',
                'data'    => ['assessment_id' => $newAssessmentId],
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error duplicating assessment: '.$e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal menduplikasi asesmen: '.$e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /* =========================================================
     * Helpers privat kecil (tidak mengganggu fitur existing)
     * ========================================================= */

    private function fieldExists(string $field, string $table): bool
    {
        if (method_exists($this->db, 'fieldExists')) {
            return (bool) $this->db->fieldExists($field, $table);
        }
        return false;
    }
}
