<?php

/**
 * File Path: app/Models/AssessmentQuestionModel.php
 *
 * Assessment Question Model
 * Model untuk mengelola data pertanyaan asesmen
 */

namespace App\Models;

use CodeIgniter\Model;

class AssessmentQuestionModel extends Model
{
    protected $table            = 'assessment_questions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $protectFields    = true;
    protected $allowedFields    = [
        'assessment_id','question_text','question_type','options','correct_answer','points',
        'order_number','is_required','explanation','image_url','dimension',
        'created_at','updated_at','deleted_at'
    ];

    /**
     * Cast ringan untuk konsistensi tipe data.
     * Catatan: options didecode via afterFind (bukan casts JSON bawaan CI),
     * supaya kompatibel dengan kolom TEXT dan beberapa format (array / assoc).
     */
    protected array $casts = [
        'id'            => 'integer',
        'assessment_id' => 'integer',
        'points'        => '?float',
        'order_number'  => '?integer',
        'is_required'   => 'integer',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'assessment_id' => 'required|integer',
        'question_text' => 'required',
        'question_type' => 'required|in_list[Multiple Choice,Essay,True/False,Rating Scale,Checkbox]',
        'points'        => 'permit_empty|decimal',
        'order_number'  => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'assessment_id' => [
            'required' => 'ID Asesmen harus diisi',
            'integer'  => 'ID Asesmen harus berupa angka',
        ],
        'question_text' => [
            'required' => 'Teks pertanyaan harus diisi',
        ],
        'question_type' => [
            'required' => 'Tipe pertanyaan harus dipilih',
            'in_list'  => 'Tipe pertanyaan tidak valid',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    // Tambahan penting:
    // - ensureOrderNumber: kalau order_number kosong, otomatis diisi nextOrderNumber()
    // - normalizeTextFields: rapikan input string & flags
    protected $beforeInsert   = ['ensureOrderNumber', 'normalizeTextFields', 'encodeOptions'];
    protected $beforeUpdate   = ['normalizeTextFields', 'encodeOptions'];
    protected $afterFind      = ['decodeOptions'];

    /**
     * Builder untuk pertanyaan pada satu asesmen, berurutan ASC.
     */
    public function byAssessment(int $assessmentId): self
    {
        // Tambahkan deleted_at IS NULL agar aman untuk operasi builder manual.
        return $this->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->orderBy('order_number', 'ASC');
    }

    /**
     * Versi yang langsung mengembalikan array.
     */
    public function getByAssessment(int $assessmentId, bool $ordered = true): array
    {
        $builder = $this->where('assessment_id', $assessmentId)->where('deleted_at', null);
        if ($ordered) {
            $builder->orderBy('order_number', 'ASC');
        }
        return $builder->findAll();
    }

    /**
     * Hitung nomor urut berikutnya untuk suatu asesmen.
     */
    public function nextOrderNumber(int $assessmentId): int
    {
        $row = $this->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->selectMax('order_number')
            ->first();

        $max = (int) ($row['order_number'] ?? 0);
        return $max + 1;
    }

    /**
     * Geser urutan pertanyaan ini ke posisi target secara atomik.
     * Otomatis clamp ke 1..N dan menggeser pertanyaan lain.
     */
    public function moveToOrder(int $questionId, int $newOrder): bool
    {
        $row = $this->asArray()->find($questionId);
        if (! $row) return false;

        $assessmentId = (int) $row['assessment_id'];
        $oldOrder     = (int) ($row['order_number'] ?? 0);

        // Hitung total aktif (soft delete aware)
        $total = (int) $this->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->countAllResults();

        if ($total <= 0) return false;

        // Clamp ke [1..total]
        if ($newOrder < 1) $newOrder = 1;
        if ($newOrder > $total) $newOrder = $total;

        if ($newOrder === $oldOrder) return true;

        $this->db->transStart();
        try {
            if ($newOrder < $oldOrder) {
                // Geser turun [newOrder .. oldOrder-1] : +1
                $this->db->table($this->table)
                    ->set('order_number', 'order_number + 1', false)
                    ->where('assessment_id', $assessmentId)
                    ->where('deleted_at', null)
                    ->where('order_number >=', $newOrder)
                    ->where('order_number <',  $oldOrder)
                    ->update();
            } else {
                // Geser naik [oldOrder+1 .. newOrder] : -1
                $this->db->table($this->table)
                    ->set('order_number', 'order_number - 1', false)
                    ->where('assessment_id', $assessmentId)
                    ->where('deleted_at', null)
                    ->where('order_number <=', $newOrder)
                    ->where('order_number >',  $oldOrder)
                    ->update();
            }

            // Tempatkan target di posisi baru
            $this->update($questionId, ['order_number' => $newOrder]);

            $this->db->transComplete();
            return $this->db->transStatus();
        } catch (\Throwable $e) {
            log_message('error', 'moveToOrder failed: '.$e->getMessage());
            $this->db->transRollback();
            return false;
        }
    }

    /**
     * Rapikan ulang supaya berurutan 1..N (mis. setelah operasi manual).
     */
    public function normalizeOrders(int $assessmentId): bool
    {
        $rows = $this->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->orderBy('order_number', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $db = $this->db;
        $db->transStart();
        try {
            $n = 1;
            foreach ($rows as $r) {
                if ((int)$r['order_number'] !== $n) {
                    $this->update((int)$r['id'], ['order_number' => $n]);
                }
                $n++;
            }
            $db->transComplete();
            return $db->transStatus();
        } catch (\Throwable $e) {
            log_message('error', 'normalizeOrders error: '.$e->getMessage());
            $db->transRollback();
            return false;
        }
    }

    /**
     * Reorder bulk (opsional): [question_id => order_number].
     * Catatan: tidak dipakai di UI saat ini, tapi dibiarkan untuk utilitas.
     */
    public function reorderQuestions(array $orderedIds): bool
    {
        $this->db->transStart();
        try {
            foreach ($orderedIds as $questionId => $orderNumber) {
                $this->update((int)$questionId, ['order_number' => (int)$orderNumber]);
            }
            $this->db->transComplete();
            return $this->db->transStatus();
        } catch (\Throwable $e) {
            log_message('error', 'Error reordering questions: ' . $e->getMessage());
            $this->db->transRollback();
            return false;
        }
    }

    /**
     * Get pertanyaan beserta info asesmen.
     */
    public function getQuestionWithAssessment(int $id): ?array
    {
        return $this->select('assessment_questions.*,
                              assessments.title as assessment_title,
                              assessments.assessment_type')
            ->join('assessments', 'assessments.id = assessment_questions.assessment_id AND assessments.deleted_at IS NULL', 'inner')
            ->where('assessment_questions.id', $id)
            ->where('assessment_questions.deleted_at', null)
            ->first();
    }

    public function getByType(int $assessmentId, string $type): array
    {
        return $this->where('assessment_id', $assessmentId)
            ->where('question_type', $type)
            ->where('deleted_at', null)
            ->orderBy('order_number', 'ASC')
            ->findAll();
    }

    public function duplicateQuestion(int $questionId, ?int $newAssessmentId = null)
    {
        $question = $this->asArray()->find($questionId);
        if (! $question) return false;

        unset($question['id'], $question['created_at'], $question['updated_at'], $question['deleted_at']);

        if ($newAssessmentId !== null) {
            $question['assessment_id'] = $newAssessmentId;
        }
        $assessmentId = (int) $question['assessment_id'];
        $question['order_number'] = $this->nextOrderNumber($assessmentId);

        if (isset($question['options']) && is_array($question['options'])) {
            $question['options'] = json_encode($this->normalizeOptions($question['options']), JSON_UNESCAPED_UNICODE);
        }

        // Return insert ID (lebih konsisten dipakai service)
        return $this->insert($question, true);
    }

    public function bulkInsert(array $questions): bool
    {
        $this->db->transStart();
        try {
            foreach ($questions as $q) {
                if (isset($q['options']) && is_array($q['options'])) {
                    $q['options'] = json_encode($this->normalizeOptions($q['options']), JSON_UNESCAPED_UNICODE);
                }
                if (empty($q['order_number']) && !empty($q['assessment_id'])) {
                    $q['order_number'] = $this->nextOrderNumber((int)$q['assessment_id']);
                }
                $this->insert($q);
            }
            $this->db->transComplete();
            return $this->db->transStatus();
        } catch (\Throwable $e) {
            log_message('error', 'Error bulk inserting questions: ' . $e->getMessage());
            $this->db->transRollback();
            return false;
        }
    }

    public function getQuestionStatistics(int $questionId): array
    {
        $stats = [
            'total_answers'     => 0,
            'correct_answers'   => 0,
            'incorrect_answers' => 0,
            'average_score'     => 0,
            'difficulty_level'  => 'Medium',
        ];

        $answers = $this->db->table('assessment_answers')
            ->select('COUNT(*) as total,
                      SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
                      AVG(score) as avg_score')
            ->where('question_id', $questionId)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if ($answers && (int)$answers['total'] > 0) {
            $stats['total_answers']     = (int) $answers['total'];
            $stats['correct_answers']   = (int) $answers['correct'];
            $stats['incorrect_answers'] = $stats['total_answers'] - $stats['correct_answers'];
            $stats['average_score']     = round((float) $answers['avg_score'], 2);

            $correctPct = ($stats['correct_answers'] / $stats['total_answers']) * 100;
            if ($correctPct >= 75) $stats['difficulty_level'] = 'Easy';
            elseif ($correctPct >= 40) $stats['difficulty_level'] = 'Medium';
            else $stats['difficulty_level'] = 'Hard';
        }
        return $stats;
    }

    public function countByAssessment(int $assessmentId): int
    {
        return $this->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->countAllResults();
    }

    public function getQuestionsWithStats(int $assessmentId): array
    {
        return $this->select('assessment_questions.*,
                              COUNT(DISTINCT assessment_answers.student_id) as answer_count,
                              AVG(assessment_answers.score) as avg_score,
                              SUM(CASE WHEN assessment_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count')
            // penting: filter soft-delete answers di join condition
            ->join('assessment_answers', 'assessment_answers.question_id = assessment_questions.id AND assessment_answers.deleted_at IS NULL', 'left')
            ->where('assessment_questions.assessment_id', $assessmentId)
            ->where('assessment_questions.deleted_at', null)
            ->groupBy('assessment_questions.id')
            ->orderBy('assessment_questions.order_number', 'ASC')
            ->findAll();
    }

    public function getQuestionTypes(): array
    {
        return [
            'Multiple Choice' => 'Multiple Choice (Pilihan Ganda)',
            'Essay'           => 'Essay (Uraian)',
            'True/False'      => 'True/False (Benar/Salah)',
            'Rating Scale'    => 'Rating Scale (Skala)',
            'Checkbox'        => 'Checkbox (Multi-Pilihan)',
        ];
    }

    public function validateQuestionOptions(string $type, $options): bool
    {
        switch ($type) {
            case 'Multiple Choice':
            case 'Checkbox':
                return is_array($options) && count(array_filter($options, fn($v) => $v !== '' && $v !== null)) >= 2;

            case 'True/False':
                return is_array($options) && count($options) === 2;

            case 'Rating Scale':
                return is_array($options)
                    && isset($options['min'], $options['max'])
                    && is_numeric($options['min']) && is_numeric($options['max'])
                    && $options['max'] > $options['min'];

            case 'Essay':
                return true;

            default:
                return false;
        }
    }

    /* ===== Callbacks & Helpers ===== */

    /**
     * Pastikan order_number selalu terisi agar sorting stabil.
     * Dipanggil otomatis saat insert bila order_number kosong.
     */
    protected function ensureOrderNumber(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) return $data;

        $row =& $data['data'];

        $hasOrder = array_key_exists('order_number', $row) && $row['order_number'] !== null && $row['order_number'] !== '';
        if ($hasOrder) return $data;

        $assessmentId = isset($row['assessment_id']) ? (int) $row['assessment_id'] : 0;
        if ($assessmentId <= 0) return $data;

        $row['order_number'] = $this->nextOrderNumber($assessmentId);
        return $data;
    }

    /**
     * Normalisasi field string & flag agar konsisten.
     */
    protected function normalizeTextFields(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) return $data;

        $row =& $data['data'];

        foreach (['question_text','correct_answer','explanation','image_url','dimension'] as $f) {
            if (array_key_exists($f, $row) && is_string($row[$f])) {
                $row[$f] = trim($row[$f]);
                if ($row[$f] === '') {
                    // correct_answer/explanation/image_url/dimension boleh null
                    if ($f !== 'question_text') {
                        $row[$f] = null;
                    }
                }
            }
        }

        if (array_key_exists('is_required', $row)) {
            $row['is_required'] = (int) (
                (string) $row['is_required'] === '1'
                || $row['is_required'] === 1
                || $row['is_required'] === true
            );
        }

        if (array_key_exists('points', $row)) {
            if ($row['points'] === '' || $row['points'] === null) {
                $row['points'] = null;
            } elseif (is_numeric($row['points'])) {
                $row['points'] = (float) $row['points'];
                if ($row['points'] < 0) $row['points'] = 0.0;
            }
        }

        return $data;
    }

    protected function encodeOptions(array $data): array
    {
        if (!isset($data['data'])) return $data;

        if (array_key_exists('options', $data['data'])) {
            $opts = $data['data']['options'];
            if (is_array($opts)) {
                $data['data']['options'] = json_encode(
                    $this->normalizeOptions($opts),
                    JSON_UNESCAPED_UNICODE
                );
            }
        }
        return $data;
    }

    protected function decodeOptions(array $data): array
    {
        if (!isset($data['data'])) return $data;

        // Multiple records
        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as $k => $row) {
                if (isset($row['options']) && is_string($row['options']) && $row['options'] !== '') {
                    $decoded = json_decode($row['options'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['data'][$k]['options'] = $decoded;
                    }
                }
            }
        } else {
            // Single record
            if (isset($data['data']['options']) && is_string($data['data']['options']) && $data['data']['options'] !== '') {
                $decoded = json_decode($data['data']['options'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['data']['options'] = $decoded;
                }
            }
        }
        return $data;
    }

    protected function normalizeOptions(array $options): array
    {
        // Jika associative (Rating Scale {min,max} atau object lain), rapikan minimal lalu biarkan.
        $isAssoc = array_keys($options) !== range(0, count($options) - 1);
        if ($isAssoc) {
            // Trim string pada value assoc
            foreach ($options as $k => $v) {
                if (is_string($v)) {
                    $options[$k] = trim($v);
                }
            }
            return $options;
        }

        $clean = [];
        foreach ($options as $opt) {
            if ($opt === null) continue;
            $t = is_string($opt) ? trim($opt) : $opt;
            if ($t !== '' && $t !== null) $clean[] = $t;
        }
        return array_values($clean);
    }
}
