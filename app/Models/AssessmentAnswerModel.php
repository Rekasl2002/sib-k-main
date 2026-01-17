<?php

/**
 * File Path: app/Models/AssessmentAnswerModel.php
 *
 * Assessment Answer Model
 * Model untuk mengelola data jawaban siswa pada asesmen
 *
 * @package    SIB-K
 * @subpackage Models
 * @category   Model
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Models;

use CodeIgniter\Model;

class AssessmentAnswerModel extends Model
{
    protected $table            = 'assessment_answers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $protectFields = true;
    protected $allowedFields = [
        'question_id','student_id','result_id',
        'answer_text','answer_option','answer_options',
        'score','is_correct','is_auto_graded','graded_by','graded_at','feedback',
        'answered_at','time_spent_seconds','created_at','updated_at','deleted_at',
        'ip_address','user_agent',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Casts (pastikan kompatibel dengan BaseModel yang mengetik property sebagai array)
    protected array $casts = [
        'is_correct'         => '?int',   // NULL | 0 | 1
        'is_auto_graded'     => 'int',
        'score'              => '?float',
        'time_spent_seconds' => '?int',
    ];

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['normalizePayload', 'fillClientMeta'];
    protected $beforeUpdate   = ['normalizePayload', 'fillClientMeta'];
    protected $afterFind      = ['decodeMultiAnswers'];

    /**
     * Cache hasil fieldExists agar query builder tidak boros introspeksi.
     * @var array<string,bool>
     */
    private array $fieldExistsCache = [];

    /* ==========================================================
     * Schema helpers (agar aman lintas skema DB)
     * ========================================================== */

    private function fieldExists(string $table, string $field): bool
    {
        $key = strtolower($table.'.'.$field);
        if (array_key_exists($key, $this->fieldExistsCache)) {
            return $this->fieldExistsCache[$key];
        }

        try {
            $db = $this->db ?? \Config\Database::connect();
            $ok = $db->fieldExists($field, $table);
        } catch (\Throwable $e) {
            $ok = false;
        }

        return $this->fieldExistsCache[$key] = (bool)$ok;
    }

    private function joinSoftDeleteGuard(string $aliasOrName, string $realTableName): string
    {
        if ($this->fieldExists($realTableName, 'deleted_at')) {
            return "{$aliasOrName}.deleted_at IS NULL";
        }
        return '1=1';
    }

    /**
     * Bangun SELECT student_name yang kompatibel:
     * - Jika students.user_id ada & users.full_name ada → join users (u) dan pakai COALESCE(u.full_name, s.full_name jika ada)
     * - Jika tidak → pakai s.full_name jika ada
     */
    private function applyStudentNameSelectAndJoin(string $studentsAlias = 's', string $usersAlias = 'u'): array
    {
        $hasStudentUserId = $this->fieldExists('students', 'user_id');
        $hasStudentFull   = $this->fieldExists('students', 'full_name');
        $hasUserFull      = $this->fieldExists('users', 'full_name');

        $needsJoinUsers = $hasStudentUserId && $hasUserFull;

        if ($needsJoinUsers && $hasStudentFull) {
            $select = "COALESCE({$usersAlias}.full_name, {$studentsAlias}.full_name) AS student_name";
        } elseif ($needsJoinUsers) {
            $select = "{$usersAlias}.full_name AS student_name";
        } elseif ($hasStudentFull) {
            $select = "{$studentsAlias}.full_name AS student_name";
        } else {
            $select = "'' AS student_name";
        }

        return [
            'needsJoinUsers' => $needsJoinUsers,
            'select'         => $select,
        ];
    }

    /* ==========================================================
     * Normalisasi & meta
     * ========================================================== */

    /**
     * Pastikan answered_at terisi saat INSERT (bukan saat update/grading),
     * dan answer_options ter-encode JSON jika array.
     */
    protected function normalizePayload(array $data): array
    {
        if (!isset($data['data'])) return $data;

        $row =& $data['data'];

        // Deteksi update: CI4 biasanya menaruh 'id' pada beforeUpdate
        $isUpdate = isset($data['id']) && !empty($data['id']);

        // answered_at: hanya auto-set saat INSERT (agar grading/update tidak mengubah waktu jawab)
        if (!$isUpdate && (!isset($row['answered_at']) || $row['answered_at'] === null || $row['answered_at'] === '')) {
            $row['answered_at'] = date('Y-m-d H:i:s');
        }

        // Default is_auto_graded = 0 pada insert jika tidak dikirim
        if (!$isUpdate && !array_key_exists('is_auto_graded', $row)) {
            $row['is_auto_graded'] = 0;
        }

        // time_spent_seconds non-negatif
        if (array_key_exists('time_spent_seconds', $row) && $row['time_spent_seconds'] !== null && $row['time_spent_seconds'] !== '') {
            $row['time_spent_seconds'] = max(0, (int)$row['time_spent_seconds']);
        }

        // Trim teks agar rapi
        if (array_key_exists('answer_text', $row) && is_string($row['answer_text'])) {
            $row['answer_text'] = trim($row['answer_text']);
        }
        if (array_key_exists('feedback', $row) && is_string($row['feedback'])) {
            $row['feedback'] = trim($row['feedback']);
        }

        // Encode multi-answer
        if (array_key_exists('answer_options', $row) && is_array($row['answer_options'])) {
            $clean = array_values(array_filter($row['answer_options'], fn($v) => $v !== '' && $v !== null));
            $row['answer_options'] = json_encode($clean, JSON_UNESCAPED_UNICODE);
        }

        return $data;
    }

    /**
     * Isi ip_address & user_agent bila tersedia dan kolomnya ada.
     * Tidak memaksa overwrite (hanya isi jika kosong).
     */
    protected function fillClientMeta(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) return $data;

        $row =& $data['data'];

        // Kalau field tidak ada di tabel, skip
        if (!$this->fieldExists($this->table, 'ip_address') && !$this->fieldExists($this->table, 'user_agent')) {
            return $data;
        }

        $request = service('request');
        if (!$request instanceof \CodeIgniter\HTTP\IncomingRequest) return $data;

        if ($this->fieldExists($this->table, 'ip_address') && empty($row['ip_address'])) {
            $row['ip_address'] = $request->getIPAddress();
        }

        if ($this->fieldExists($this->table, 'user_agent') && empty($row['user_agent'])) {
            $ua = $request->getUserAgent();
            $row['user_agent'] = $ua ? $ua->getAgentString() : null;
        }

        return $data;
    }

    /**
     * Decode answer_options (JSON) setelah find.
     * Sekalian decode assessment_questions.options bila ikut terseleksi (mis. getByResult()).
     */
    protected function decodeMultiAnswers(array $data): array
    {
        if (!isset($data['data'])) return $data;

        $decodeRow = function (array $row): array {
            // answer_options
            if (isset($row['answer_options']) && is_string($row['answer_options']) && $row['answer_options'] !== '' && $this->looksJson($row['answer_options'])) {
                $decoded = json_decode($row['answer_options'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row['answer_options'] = $decoded;
                }
            }

            // assessment_questions.options (alias: options)
            if (isset($row['options']) && is_string($row['options']) && $row['options'] !== '' && $this->looksJson($row['options'])) {
                $decoded = json_decode($row['options'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row['options'] = $decoded;
                }
            }

            return $row;
        };

        // Multiple records
        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as $k => $row) {
                if (is_array($row)) {
                    $data['data'][$k] = $decodeRow($row);
                }
            }
        } else {
            // Single record
            if (is_array($data['data'])) {
                $data['data'] = $decodeRow($data['data']);
            }
        }

        return $data;
    }

    /* ==========================
     * Query helpers
     * ========================== */

    /**
     * Ambil jawaban (dengan metadata pertanyaan) untuk satu result.
     */
    public function getByResult(int $resultId): array
    {
        $joinQ = 'assessment_questions.id = assessment_answers.question_id AND '.$this->joinSoftDeleteGuard('assessment_questions', 'assessment_questions');

        $builder = $this->select('assessment_answers.*,
                              assessment_questions.question_text,
                              assessment_questions.question_type,
                              assessment_questions.options,
                              assessment_questions.points as max_points,
                              assessment_questions.correct_answer,
                              assessment_questions.order_number')
            ->join('assessment_questions', $joinQ, 'inner')
            ->where('assessment_answers.result_id', $resultId);

        // Guard soft delete jika ada
        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('assessment_answers.deleted_at', null);
        }

        return $builder->orderBy('assessment_questions.order_number', 'ASC')
            ->asArray()
            ->findAll();
    }

    /**
     * Ambil satu jawaban siswa untuk satu pertanyaan (opsional filter result).
     */
    public function getStudentAnswer(int $studentId, int $questionId, ?int $resultId = null): ?array
    {
        $builder = $this->where('student_id', $studentId)->where('question_id', $questionId);
        if ($resultId) $builder->where('result_id', $resultId);
        return $builder->asArray()->first();
    }

    /**
     * Insert/update jawaban siswa (idempotent per student+question+result).
     */
    public function saveAnswer(array $data): bool
    {
        $existing = $this->where('student_id', $data['student_id'] ?? null)
            ->where('question_id', $data['question_id'] ?? null)
            ->where('result_id', $data['result_id'] ?? null)
            ->asArray()
            ->first();

        if ($existing) {
            $data['id'] = $existing['id'];
            return (bool) $this->update($existing['id'], $data);
        }
        return (bool) $this->insert($data);
    }

    /* ==========================
     * Grading
     * ========================== */

    /**
     * Auto-grade untuk objektif (MC / TF / Checkbox).
     * Rating Scale dibiarkan ke service (agar konsisten interpretasi).
     * Catatan penting:
     * - Bila soal objektif TIDAK memiliki kunci (correct_answer kosong/NULL),
     *   method ini tidak melakukan apa-apa (return false) agar status tetap "Belum dinilai".
     */
    public function autoGradeAnswer(int $answerId): bool
    {
        $joinQ = 'assessment_questions.id = assessment_answers.question_id AND '.$this->joinSoftDeleteGuard('assessment_questions', 'assessment_questions');

        $builder = $this->select('assessment_answers.*,
                                 assessment_questions.correct_answer,
                                 assessment_questions.points,
                                 assessment_questions.question_type')
            ->join('assessment_questions', $joinQ, 'inner')
            ->asArray();

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('assessment_answers.deleted_at', null);
        }

        $answer = $builder->find($answerId);
        if (!$answer) return false;

        $score = 0.0;
        $isCorrect = null;

        $qType   = (string)($answer['question_type'] ?? '');
        $points  = (float)($answer['points'] ?? 0);

        // correct_answer bisa string JSON atau array (jika pernah ter-decode)
        $correct = $answer['correct_answer'] ?? null;

        switch ($qType) {
            case 'True/False':
            case 'Multiple Choice': {
                $student = $answer['answer_option'] ?? null;

                if (is_array($correct)) {
                    $correct = (string)($correct[0] ?? '');
                } elseif (is_string($correct) && $this->looksJson($correct)) {
                    $tmp = json_decode($correct, true);
                    if (is_array($tmp)) $correct = (string) ($tmp[0] ?? '');
                }

                if ($correct === null || $correct === '') {
                    return false; // tidak ada kunci
                }

                $isCorrect = ($student !== null && (string)$student !== '' && (string)$student === (string)$correct);
                $score = $isCorrect ? $points : 0.0;
                break;
            }

            case 'Checkbox': {
                $student = $answer['answer_options'] ?? null;
                if (is_string($student) && $this->looksJson($student)) {
                    $student = json_decode($student, true);
                }

                if (is_string($correct) && $this->looksJson($correct)) {
                    $correct = json_decode($correct, true);
                }

                if (!is_array($correct) || count($correct) === 0) {
                    return false; // tidak ada kunci
                }

                if (is_array($student)) {
                    $student = array_values(array_map('strval', $student));
                    $correct = array_values(array_map('strval', $correct));
                    sort($student);
                    sort($correct);
                    $isCorrect = ($student === $correct);
                    $score = $isCorrect ? $points : 0.0;
                } else {
                    return false; // tidak ada jawaban / format tidak sesuai
                }
                break;
            }

            default:
                // Essay / Rating Scale: tidak di-auto-grade di level model.
                return false;
        }

        return (bool)$this->update($answerId, [
            'score'          => $score,
            'is_correct'     => $isCorrect,
            'is_auto_graded' => 1,
            'graded_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Manual grade (Essay / kasus khusus).
     * Tidak menyetel is_correct agar bisa tetap NULL (Belum Dinilai vs nilai numerik).
     */
    public function manualGradeAnswer(int $answerId, float $score, int $gradedBy, ?string $feedback = null): bool
    {
        $joinQ = 'assessment_questions.id = assessment_answers.question_id AND '.$this->joinSoftDeleteGuard('assessment_questions', 'assessment_questions');

        $builder = $this->select('assessment_answers.*,
                                 assessment_questions.points')
            ->join('assessment_questions', $joinQ, 'inner')
            ->asArray();

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('assessment_answers.deleted_at', null);
        }

        $answer = $builder->find($answerId);
        if (!$answer) return false;

        $max = (float) ($answer['points'] ?? 0);
        $score = max(0, min($max, (float) $score));

        $update = [
            'score'          => $score,
            'is_auto_graded' => 0,
            'graded_by'      => $gradedBy,
            'graded_at'      => date('Y-m-d H:i:s'),
        ];
        if ($feedback !== null) $update['feedback'] = $feedback;

        return (bool)$this->update($answerId, $update);
    }

    /**
     * Auto-grade massal untuk satu result (MC/TF/Checkbox).
     * Menghormati soal tanpa kunci (akan dilewati).
     */
    public function bulkAutoGrade(int $resultId): int
    {
        $joinQ = 'assessment_questions.id = assessment_answers.question_id AND '.$this->joinSoftDeleteGuard('assessment_questions', 'assessment_questions');

        $builder = $this->select('assessment_answers.id')
            ->join('assessment_questions', $joinQ, 'inner')
            ->where('assessment_answers.result_id', $resultId)
            ->whereIn('assessment_questions.question_type', ['Multiple Choice', 'True/False', 'Checkbox'])
            ->where('assessment_answers.is_auto_graded', 0);

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('assessment_answers.deleted_at', null);
        }

        $answers = $builder->asArray()->findAll();

        $graded = 0;
        foreach ($answers as $row) {
            $id = $row['id'] ?? null;
            if ($id && $this->autoGradeAnswer((int) $id)) $graded++;
        }
        return $graded;
    }

    /**
     * Daftar jawaban yang butuh penilaian manual.
     * Selain Essay & Rating Scale, juga masukkan soal objektif TANPA kunci jawaban.
     *
     * Kompatibel nama siswa:
     * - users.full_name via students.user_id, atau fallback students.full_name
     */
    public function getNeedingGrading(?int $assessmentId = null, ?int $counselorId = null): array
    {
        $stuMeta = $this->applyStudentNameSelectAndJoin('s', 'u');

        $joinQ   = 'assessment_questions.id = assessment_answers.question_id AND '.$this->joinSoftDeleteGuard('assessment_questions', 'assessment_questions');
        $joinR   = 'assessment_results.id = assessment_answers.result_id AND '.$this->joinSoftDeleteGuard('assessment_results', 'assessment_results');
        $joinA   = 'assessments.id = assessment_results.assessment_id AND '.$this->joinSoftDeleteGuard('assessments', 'assessments');
        $joinS   = 's.id = assessment_answers.student_id AND '.$this->joinSoftDeleteGuard('s', 'students');

        $builder = $this->select("
                assessment_answers.*,
                assessment_questions.question_text,
                assessment_questions.question_type,
                assessment_questions.points,
                {$stuMeta['select']},
                s.nisn,
                " . ($this->fieldExists('students', 'nis') ? "s.nis," : "NULL AS nis,") . "
                assessments.title as assessment_title
            ")
            ->join('assessment_questions', $joinQ, 'inner')
            ->join('assessment_results', $joinR, 'inner')
            ->join('assessments', $joinA, 'inner')
            ->join('students s', $joinS, 'left');

        if (!empty($stuMeta['needsJoinUsers'])) {
            $builder->join('users u', 'u.id = s.user_id AND '.$this->joinSoftDeleteGuard('u', 'users'), 'left');
        }

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('assessment_answers.deleted_at', null);
        }

        $builder->groupStart()
                ->whereIn('assessment_questions.question_type', ['Essay', 'Rating Scale'])
                ->orGroupStart()
                    ->whereIn('assessment_questions.question_type', ['Multiple Choice', 'True/False', 'Checkbox'])
                    ->groupStart()
                        ->where('assessment_questions.correct_answer', null)
                        ->orWhere('assessment_questions.correct_answer', '')
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->where('assessment_answers.graded_at', null);

        if ($assessmentId) $builder->where('assessments.id', $assessmentId);
        if ($counselorId)  $builder->where('assessments.created_by', $counselorId);

        return $builder->orderBy('assessment_answers.answered_at', 'ASC')
            ->asArray()
            ->findAll();
    }

    /* ==========================
     * Statistik & utilitas
     * ========================== */

    public function getAnswerStatistics(int $questionId): array
    {
        $stats = [
            'total_answers'      => 0,
            'graded'             => 0,
            'pending_grading'    => 0,
            'correct_count'      => 0,
            'incorrect_count'    => 0,
            'average_score'      => 0.0,
            'average_time_spent' => 0.0,
        ];

        $q = $this->db->table($this->table)
            ->select('COUNT(*) as total,
                      SUM(CASE WHEN graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded,
                      SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
                      SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as incorrect,
                      AVG(score) as avg_score,
                      AVG(time_spent_seconds) as avg_time')
            ->where('question_id', $questionId);

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $q->where('deleted_at', null);
        }

        $row = $q->get()->getRowArray();

        if ($row) {
            $stats['total_answers']      = (int) ($row['total'] ?? 0);
            $stats['graded']             = (int) ($row['graded'] ?? 0);
            $stats['pending_grading']    = $stats['total_answers'] - $stats['graded'];
            $stats['correct_count']      = (int) ($row['correct'] ?? 0);
            $stats['incorrect_count']    = (int) ($row['incorrect'] ?? 0);
            $stats['average_score']      = round((float) ($row['avg_score'] ?? 0), 2);
            $stats['average_time_spent'] = round((float) ($row['avg_time'] ?? 0), 2);
        }

        return $stats;
    }

    public function getStudentProgress(int $studentId, int $assessmentId): array
    {
        $joinAns = 'assessment_answers.result_id = assessment_results.id';
        if ($this->fieldExists($this->table, 'deleted_at')) {
            $joinAns .= ' AND assessment_answers.deleted_at IS NULL';
        }

        $qb = $this->db->table('assessment_results')
            ->select('assessment_results.id as result_id,
                      COUNT(assessment_answers.id) as total_answered,
                      SUM(CASE WHEN assessment_answers.graded_at IS NOT NULL THEN 1 ELSE 0 END) as graded_count,
                      SUM(assessment_answers.score) as total_score')
            ->join('assessment_answers', $joinAns, 'left')
            ->where('assessment_results.student_id', $studentId)
            ->where('assessment_results.assessment_id', $assessmentId);

        if ($this->fieldExists('assessment_results', 'deleted_at')) {
            $qb->where('assessment_results.deleted_at', null);
        }

        $row = $qb->groupBy('assessment_results.id')->get()->getRowArray();

        if (!$row) {
            return [
                'result_id'      => null,
                'total_answered' => 0,
                'graded_count'   => 0,
                'total_score'    => 0.0,
            ];
        }

        return [
            'result_id'      => $row['result_id'],
            'total_answered' => (int) ($row['total_answered'] ?? 0),
            'graded_count'   => (int) ($row['graded_count'] ?? 0),
            'total_score'    => (float) ($row['total_score'] ?? 0),
        ];
    }

    public function deleteByResult(int $resultId): bool
    {
        return (bool) $this->where('result_id', $resultId)->delete();
    }

    /**
     * Distribusi jawaban untuk satu pertanyaan.
     * Menggabungkan single-value (answer_option) dan multi-value (answer_options).
     */
    public function getAnswerDistribution(int $questionId): array
    {
        $builder = $this->select('answer_option, answer_options')
            ->where('question_id', $questionId);

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('deleted_at', null);
        }

        $rows = $builder->asArray()->findAll();

        $dist = [];
        foreach ($rows as $r) {
            // single
            if (!empty($r['answer_option'])) {
                $key = (string) $r['answer_option'];
                $dist[$key] = ($dist[$key] ?? 0) + 1;
            }
            // multi
            if (!empty($r['answer_options'])) {
                $arr = is_array($r['answer_options'])
                    ? $r['answer_options']
                    : ($this->looksJson($r['answer_options']) ? json_decode($r['answer_options'], true) : []);
                if (is_array($arr)) {
                    foreach ($arr as $item) {
                        $key = (string) $item;
                        $dist[$key] = ($dist[$key] ?? 0) + 1;
                    }
                }
            }
        }

        ksort($dist);
        return $dist;
    }

    public function updateTimeSpent(int $answerId, int $seconds): bool
    {
        return (bool)$this->update($answerId, ['time_spent_seconds' => max(0, $seconds)]);
    }

    public function getRecentAnswersByStudent(int $studentId, int $limit = 10): array
    {
        $joinQ = 'assessment_questions.id = assessment_answers.question_id AND '.$this->joinSoftDeleteGuard('assessment_questions', 'assessment_questions');
        $joinR = 'assessment_results.id = assessment_answers.result_id AND '.$this->joinSoftDeleteGuard('assessment_results', 'assessment_results');
        $joinA = 'assessments.id = assessment_results.assessment_id AND '.$this->joinSoftDeleteGuard('assessments', 'assessments');

        $builder = $this->select('assessment_answers.*,
                              assessment_questions.question_text,
                              assessments.title as assessment_title')
            ->join('assessment_questions', $joinQ, 'inner')
            ->join('assessment_results', $joinR, 'inner')
            ->join('assessments', $joinA, 'inner')
            ->where('assessment_answers.student_id', $studentId);

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('assessment_answers.deleted_at', null);
        }

        return $builder->orderBy('assessment_answers.answered_at', 'DESC')
            ->limit($limit)
            ->asArray()
            ->findAll();
    }

    /* ==========================
     * Helpers
     * ========================== */

    private function looksJson($value): bool
    {
        if (!is_string($value)) return false;
        $v = trim($value);
        if ($v === '') return false;
        if ($v[0] !== '[' && $v[0] !== '{' && $v[0] !== '"') return false;
        json_decode($v, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
