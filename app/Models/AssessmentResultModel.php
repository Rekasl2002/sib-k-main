<?php
/**
 * File Path: app/Models/AssessmentResultModel.php
 *
 * Assessment Result Model
 * Model untuk mengelola data hasil asesmen siswa
 *
 * Status yang dipakai:
 * - Assigned   : ditugaskan, belum mulai (started_at NULL, time_spent_seconds 0)
 * - In Progress: sedang dikerjakan (started_at terisi)
 * - Completed  : selesai dikerjakan (menunggu penilaian otomatis/manual)
 * - Graded     : sudah dinilai
 * - Expired    : kadaluarsa
 * - Abandoned  : ditinggalkan (mis. student start baru saat masih in-progress)
 *
 * @package    SIB-K
 * @subpackage Models
 * @category   Model
 */

namespace App\Models;

use CodeIgniter\Model;

class AssessmentResultModel extends Model
{
    protected $table            = 'assessment_results';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $protectFields    = true;
    protected $allowedFields    = [
        'assessment_id','student_id','attempt_number','status',
        'total_score','max_score','percentage','is_passed',
        'questions_answered','total_questions','correct_answers',
        'started_at','completed_at','graded_at','time_spent_seconds',
        'interpretation','dimension_scores','recommendations',
        'reviewed_by','reviewed_at','counselor_notes',
        'ip_address','user_agent',
        'created_at','updated_at','deleted_at',
    ];

    /** Casts: gunakan tipe nullable (awalan ?) agar nilai NULL valid di awal siklus. */
    protected array $casts = [
        'assessment_id'       => 'integer',
        'student_id'          => 'integer',
        'attempt_number'      => 'integer',

        'total_score'         => '?float',
        'max_score'           => '?float',
        'percentage'          => '?float',
        'is_passed'           => '?integer',

        'questions_answered'  => '?integer',
        'total_questions'     => '?integer',
        'correct_answers'     => '?integer',

        'time_spent_seconds'  => '?integer',

        // Catatan: beberapa instalasi CI4 punya cast json/array berbeda.
        // Kita tetap decode manual di afterFind agar kompatibel lintas versi/skema.
        'dimension_scores'    => '?array',
        'reviewed_by'         => '?integer',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['autoAttemptNumber', 'ensureStartedAt', 'fillClientMeta', 'encodeJsonFields'];
    protected $beforeUpdate   = ['fillClientMeta', 'encodeJsonFields'];
    protected $afterFind      = ['decodeJsonFields'];

    /** Status yang dihitung sebagai MENGURAS KUOTA percobaan. */
    public const QUOTA_STATUSES = ['Completed','Graded','Expired','Abandoned'];

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

    private function joinSoftDeleteGuard(string $tableAliasOrName, string $realTableName): string
    {
        // Jika tabel punya kolom deleted_at, tambahkan guard "AND xxx.deleted_at IS NULL"
        if ($this->fieldExists($realTableName, 'deleted_at')) {
            return "{$tableAliasOrName}.deleted_at IS NULL";
        }
        return '1=1';
    }

    /**
     * Bangun SELECT untuk evaluation_mode yang kompatibel:
     * - Jika assessments.evaluation_mode ada: pakai COALESCE(evaluation_mode, CASE use_passing_score ...)
     * - Jika tidak ada: pakai CASE use_passing_score ...
     */
    private function selectEvaluationMode(string $aAlias = 'assessments'): string
    {
        if ($this->fieldExists('assessments', 'evaluation_mode')) {
            return "COALESCE({$aAlias}.evaluation_mode, CASE WHEN {$aAlias}.use_passing_score = 1 THEN 'pass_fail' ELSE 'score_only' END) AS evaluation_mode";
        }
        return "CASE WHEN {$aAlias}.use_passing_score = 1 THEN 'pass_fail' ELSE 'score_only' END AS evaluation_mode";
    }

    /**
     * Bangun SELECT untuk show_score_to_student yang kompatibel:
     * - Jika assessments.show_score_to_student ada: COALESCE(show_score_to_student, show_result_immediately)
     * - Jika tidak ada: show_result_immediately
     */
    private function selectShowScoreToStudent(string $aAlias = 'assessments'): string
    {
        if ($this->fieldExists('assessments', 'show_score_to_student')) {
            return "COALESCE({$aAlias}.show_score_to_student, {$aAlias}.show_result_immediately) AS show_score_to_student";
        }
        return "{$aAlias}.show_result_immediately AS show_score_to_student";
    }

    /**
     * Join & select student name yang kompatibel:
     * - Jika students.user_id ada: join users as student_users, gunakan student_users.full_name
     * - Jika students.full_name ada (tanpa user_id): gunakan students.full_name
     * - Jika keduanya ada: gunakan COALESCE(student_users.full_name, students.full_name)
     */
    private function applyStudentNameSelectAndJoin(string $studentsAlias = 'students', string $usersAlias = 'student_users'): array
    {
        $hasStudentUserId = $this->fieldExists('students', 'user_id');
        $hasStudentFull   = $this->fieldExists('students', 'full_name');
        $hasUserFull      = $this->fieldExists('users', 'full_name');

        $needsJoinUsers = $hasStudentUserId && $hasUserFull;

        $select = '';
        if ($needsJoinUsers && $hasStudentFull) {
            $select = "COALESCE({$usersAlias}.full_name, {$studentsAlias}.full_name) AS student_name";
        } elseif ($needsJoinUsers) {
            $select = "{$usersAlias}.full_name AS student_name";
        } elseif ($hasStudentFull) {
            $select = "{$studentsAlias}.full_name AS student_name";
        } else {
            // fallback terakhir: jangan bikin error SQL, beri string kosong
            $select = "'' AS student_name";
        }

        return [
            'needsJoinUsers' => $needsJoinUsers,
            'select'         => $select,
        ];
    }

    /* ==========================
     * Auto fields & meta
     * ========================== */

    /**
     * Otomatis set attempt_number jika belum ada.
     * Menghitung dari baris non-deleted (deleted_at IS NULL).
     */
    protected function autoAttemptNumber(array $data): array
    {
        if (!isset($data['data'])) return $data;

        $row = $data['data'];
        $attemptProvided = array_key_exists('attempt_number', $row)
            && $row['attempt_number'] !== null
            && $row['attempt_number'] !== '';

        if ($attemptProvided || !isset($row['assessment_id'], $row['student_id'])) {
            return $data;
        }

        $assessmentId = (int) $row['assessment_id'];
        $studentId    = (int) $row['student_id'];
        if ($assessmentId <= 0 || $studentId <= 0) return $data;

        $db = \Config\Database::connect();

        $maxRow = $db->table($this->table)
            ->selectMax('attempt_number', 'max_attempt')
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId);

        // guard soft delete jika ada
        if ($this->fieldExists($this->table, 'deleted_at')) {
            $maxRow->where('deleted_at', null);
        }

        $maxRow = $maxRow->get()->getRowArray();

        $maxAttempt = (int) ($maxRow['max_attempt'] ?? 0);
        $data['data']['attempt_number'] = $maxAttempt + 1;

        return $data;
    }

    /**
     * Pastikan started_at terisi hanya ketika status In Progress.
     * Untuk status Assigned biarkan NULL agar UI menjadi "Kerjakan".
     */
    protected function ensureStartedAt(array $data): array
    {
        if (!isset($data['data'])) return $data;
        $status = $data['data']['status'] ?? null;

        if (empty($data['data']['started_at']) && $status === 'In Progress') {
            $data['data']['started_at'] = date('Y-m-d H:i:s');
        }
        return $data;
    }

    /**
     * Isi ip_address & user_agent ketika attempt berstatus In Progress.
     */
    protected function fillClientMeta(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $data;
        }

        $row =& $data['data'];
        if (($row['status'] ?? null) !== 'In Progress') return $data;

        $request = service('request');
        if (!$request instanceof \CodeIgniter\HTTP\IncomingRequest) return $data;

        if (empty($row['ip_address'])) {
            $row['ip_address'] = $request->getIPAddress();
        }
        if (empty($row['user_agent'])) {
            $ua = $request->getUserAgent();
            $row['user_agent'] = $ua ? $ua->getAgentString() : null;
        }
        return $data;
    }

    /**
     * Encode field JSON bila dikirim sebagai array.
     * Aman untuk data lama: jika sudah string, dibiarkan.
     */
    protected function encodeJsonFields(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) return $data;

        if (array_key_exists('dimension_scores', $data['data']) && is_array($data['data']['dimension_scores'])) {
            $data['data']['dimension_scores'] = json_encode($data['data']['dimension_scores'], JSON_UNESCAPED_UNICODE);
        }

        // rekomendasi kadang juga disimpan sebagai struktur
        if (array_key_exists('recommendations', $data['data']) && is_array($data['data']['recommendations'])) {
            $data['data']['recommendations'] = json_encode($data['data']['recommendations'], JSON_UNESCAPED_UNICODE);
        }

        return $data;
    }

    /**
     * Decode field JSON setelah find.
     */
    protected function decodeJsonFields(array $data): array
    {
        if (!isset($data['data'])) return $data;

        $decodeRow = function (array $row): array {
            foreach (['dimension_scores','recommendations'] as $field) {
                if (isset($row[$field]) && is_string($row[$field]) && $row[$field] !== '' && $this->looksJson($row[$field])) {
                    $decoded = json_decode($row[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $row[$field] = $decoded;
                    }
                }
            }
            return $row;
        };

        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as $k => $row) {
                if (is_array($row)) {
                    $data['data'][$k] = $decodeRow($row);
                }
            }
        } else {
            if (is_array($data['data'])) {
                $data['data'] = $decodeRow($data['data']);
            }
        }

        return $data;
    }

    /* ==========================
     * Query helpers (detail & listing)
     * ========================== */

    /**
     * Result + metadata asesmen/siswa.
     * Kompatibel untuk:
     * - assessment.evaluation_mode (jika ada) atau fallback CASE use_passing_score
     * - assessment.show_score_to_student (jika ada) atau fallback show_result_immediately
     * - student name: users.full_name via students.user_id atau fallback students.full_name
     */
    public function getResultWithDetails(int $id): ?array
    {
        $evalSel  = $this->selectEvaluationMode('assessments');
        $showSel  = $this->selectShowScoreToStudent('assessments');
        $stuMeta  = $this->applyStudentNameSelectAndJoin('students', 'student_users');

        $select = "
            {$this->table}.*,
            assessments.title AS assessment_title,
            assessments.assessment_type,
            assessments.passing_score,
            assessments.allow_review,
            {$showSel},
            assessments.result_release_at,
            {$evalSel},
            {$stuMeta['select']},
            students.nisn,
            " . ($this->fieldExists('students', 'nis') ? "students.nis," : "NULL AS nis,") . "
            students.class_id,
            classes.class_name,
            reviewers.full_name AS reviewer_name
        ";

        $q = $this->select($select)
            ->join('assessments', 'assessments.id = '.$this->table.'.assessment_id AND '.$this->joinSoftDeleteGuard('assessments', 'assessments'), 'inner')
            ->join('students',   'students.id = '.$this->table.'.student_id AND '.$this->joinSoftDeleteGuard('students', 'students'), 'inner');

        if (!empty($stuMeta['needsJoinUsers'])) {
            $q->join('users AS student_users', 'student_users.id = students.user_id AND '.$this->joinSoftDeleteGuard('student_users', 'users'), 'left');
        }

        $q->join('classes', 'classes.id = students.class_id AND '.$this->joinSoftDeleteGuard('classes', 'classes'), 'left')
          ->join('users AS reviewers', 'reviewers.id = '.$this->table.'.reviewed_by AND '.$this->joinSoftDeleteGuard('reviewers', 'users'), 'left')
          ->where($this->table.'.id', $id)
          ->asArray();

        return $q->first();
    }

    /**
     * Daftar result per asesmen dengan filter opsional.
     */
    public function getByAssessment(int $assessmentId, array $filters = []): array
    {
        $evalSel = $this->selectEvaluationMode('assessments');
        $stuMeta = $this->applyStudentNameSelectAndJoin('students', 'student_users');

        $builder = $this->select("
                {$this->table}.*,
                assessments.title AS assessment_title,
                {$evalSel},
                {$stuMeta['select']},
                students.nisn,
                " . ($this->fieldExists('students', 'nis') ? "students.nis," : "NULL AS nis,") . "
                classes.class_name
            ")
            ->join('assessments', 'assessments.id = '.$this->table.'.assessment_id AND '.$this->joinSoftDeleteGuard('assessments', 'assessments'), 'inner')
            ->join('students', 'students.id = '.$this->table.'.student_id AND '.$this->joinSoftDeleteGuard('students', 'students'), 'inner');

        if (!empty($stuMeta['needsJoinUsers'])) {
            $builder->join('users AS student_users', 'student_users.id = students.user_id AND '.$this->joinSoftDeleteGuard('student_users', 'users'), 'left');
        }

        $builder->join('classes',  'classes.id = students.class_id AND '.$this->joinSoftDeleteGuard('classes', 'classes'), 'left')
            ->where($this->table.'.assessment_id', $assessmentId);

        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where($this->table.'.status', $filters['status']);
        }
        if (isset($filters['class_id']) && $filters['class_id'] !== '') {
            $builder->where('students.class_id', $filters['class_id']);
        }
        if (isset($filters['is_passed']) && $filters['is_passed'] !== '') {
            $builder->where($this->table.'.is_passed', (int) $filters['is_passed']);
        }
        if (!empty($filters['search'])) {
            $search = (string)$filters['search'];
            $builder->groupStart();

            // Cari nama: kalau join users ada, cari di student_users.full_name. kalau tidak, cari di students.full_name (jika ada).
            if (!empty($stuMeta['needsJoinUsers']) && $this->fieldExists('users', 'full_name')) {
                $builder->like('student_users.full_name', $search);
            } elseif ($this->fieldExists('students', 'full_name')) {
                $builder->like('students.full_name', $search);
            }

            // Cari NIS/NISN
            $builder->orLike('students.nisn', $search);
            if ($this->fieldExists('students', 'nis')) {
                $builder->orLike('students.nis', $search);
            }

            $builder->groupEnd();
        }

        return $builder->orderBy($this->table.'.created_at', 'DESC')
            ->asArray()
            ->findAll();
    }

    /**
     * Daftar result per siswa dengan filter opsional.
     */
    public function getByStudent(int $studentId, array $filters = []): array
    {
        $evalSel = $this->selectEvaluationMode('assessments');
        $showSel = $this->selectShowScoreToStudent('assessments');

        $builder = $this->select("
                {$this->table}.*,
                assessments.title AS assessment_title,
                assessments.assessment_type,
                assessments.passing_score,
                assessments.allow_review,
                {$showSel},
                assessments.result_release_at,
                {$evalSel}
            ")
            ->join('assessments', 'assessments.id = '.$this->table.'.assessment_id AND '.$this->joinSoftDeleteGuard('assessments', 'assessments'), 'inner')
            ->where($this->table.'.student_id', $studentId);

        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where($this->table.'.status', $filters['status']);
        }
        if (isset($filters['assessment_type']) && $filters['assessment_type'] !== '') {
            $builder->where('assessments.assessment_type', $filters['assessment_type']);
        }
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('assessments.title', $filters['search'])
                ->orLike($this->table.'.interpretation', $filters['search'])
            ->groupEnd();
        }

        return $builder->orderBy($this->table.'.created_at', 'DESC')
            ->asArray()
            ->findAll();
    }

    /* ==========================
     * Lifecycle utilities
     * ========================== */

    /**
     * Menghitung attempt berikutnya (MAX(attempt_number)+1) untuk pasangan asesmen-siswa,
     * hanya menghitung baris yang belum di-soft delete.
     */
    public function nextAttemptNumber(int $assessmentId, int $studentId): int
    {
        $db = \Config\Database::connect();
        $q = $db->table($this->table)
            ->selectMax('attempt_number', 'max_attempt')
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId);

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $q->where('deleted_at', null);
        }

        $row = $q->get()->getRowArray();
        return ((int)($row['max_attempt'] ?? 0)) + 1;
    }

    /**
     * Mulai attempt untuk siswa (dipanggil saat siswa menekan "Kerjakan" / "Lanjutkan").
     * - Jika sudah ada In Progress: kembalikan ID tersebut.
     * - Jika ada Assigned: ubah ke In Progress, set started_at sekarang, lalu kembalikan ID.
     * - Jika tidak ada keduanya: buat attempt baru In Progress.
     * @return int|false ID result
     */
    public function startAssessment(int $assessmentId, int $studentId)
    {
        // 1) Sudah ada yang In Progress?
        $inProgress = $this->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->where('status', 'In Progress')
            ->orderBy('id', 'DESC')
            ->asArray()
            ->first();

        if ($inProgress) {
            return (int) ($inProgress['id'] ?? 0);
        }

        // 2) Ada yang Assigned? Naikkan ke In Progress
        $assigned = $this->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->where('status', 'Assigned')
            ->orderBy('id', 'DESC')
            ->asArray()
            ->first();

        if ($assigned) {
            $this->update($assigned['id'], [
                'status'     => 'In Progress',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
            return (int) $assigned['id'];
        }

        // 3) Tidak ada keduanya: buat attempt baru In Progress
        $db = \Config\Database::connect();

        // Validasi asesmen (guard soft delete bila ada)
        $asmQ = $db->table('assessments')->where('id', $assessmentId);
        if ($this->fieldExists('assessments', 'deleted_at')) {
            $asmQ->where('deleted_at', null);
        }
        $assessment = $asmQ->get()->getRowArray();
        if (!$assessment) return false;

        // Hitung total Q dan max score (guard soft delete bila ada)
        $qQ = $db->table('assessment_questions')->where('assessment_id', $assessmentId);
        if ($this->fieldExists('assessment_questions', 'deleted_at')) {
            $qQ->where('deleted_at', null);
        }
        $totalQuestions = $qQ->countAllResults();

        $msQ = $db->table('assessment_questions')->selectSum('points')->where('assessment_id', $assessmentId);
        if ($this->fieldExists('assessment_questions', 'deleted_at')) {
            $msQ->where('deleted_at', null);
        }
        $maxScoreRow = $msQ->get()->getRowArray();

        $data = [
            'assessment_id'      => $assessmentId,
            'student_id'         => $studentId,
            // attempt_number tidak di-set, akan diisi autoAttemptNumber()
            'status'             => 'In Progress',
            'total_questions'    => $totalQuestions,
            'questions_answered' => 0,
            'max_score'          => (float) ($maxScoreRow['points'] ?? 0),
            'total_score'        => 0.0,
            'time_spent_seconds' => 0,
        ];

        return (int) $this->insert($data, true);
    }

    /**
     * Tandai attempt selesai.
     */
    public function completeAssessment(int $resultId): bool
    {
        $result = $this->asArray()->find($resultId);
        if (!$result || $result['status'] !== 'In Progress') return false;

        $now = date('Y-m-d H:i:s');
        $update = [
            'status'        => 'Completed',
            'completed_at'  => $now,
        ];

        if (empty($result['time_spent_seconds']) && !empty($result['started_at'])) {
            $update['time_spent_seconds'] = max(0, strtotime($now) - strtotime($result['started_at']));
        }

        return (bool)$this->update($resultId, $update);
    }

    /**
     * Hitung skor & lulus/tidak, set status = Graded.
     * - survey    : jika max_score <= 0 → percentage & total_score = NULL, is_passed = NULL.
     * - pass_fail : percentage dihitung, is_passed diisi berdasar passing_score (jika ada).
     * - score_only: percentage dihitung bila max_score > 0, is_passed = NULL.
     */
    public function calculateScore(int $resultId): bool
    {
        $db = \Config\Database::connect();

        $ansQ = $db->table('assessment_answers')
            ->select('SUM(score) as total_score,
                      COUNT(*) as answered,
                      SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->where('result_id', $resultId);

        if ($this->fieldExists('assessment_answers', 'deleted_at')) {
            $ansQ->where('deleted_at', null);
        }

        $answers = $ansQ->get()->getRowArray();

        $result = $this->asArray()->find($resultId);
        if (!$result) return false;

        $asmQ = $db->table('assessments')
            ->select('passing_score, use_passing_score, show_result_immediately')
            ->where('id', $result['assessment_id']);

        if ($this->fieldExists('assessments', 'deleted_at')) {
            $asmQ->where('deleted_at', null);
        }

        $assessment = $asmQ->get()->getRowArray();

        $usePassing   = (int)($assessment['use_passing_score'] ?? 0);
        $passingScore = $assessment['passing_score'] ?? null;

        $totalScore = (float) ($answers['total_score'] ?? 0);
        $maxScore   = (float) ($result['max_score'] ?? 0);

        $isSurvey   = ($maxScore <= 0);
        $mode       = $isSurvey ? 'survey' : ($usePassing === 1 ? 'pass_fail' : 'score_only');

        $percentage = ($maxScore > 0) ? round(($totalScore / $maxScore) * 100, 2) : null;
        $isPassed   = null;

        if ($mode === 'survey') {
            $totalScore = null;
            $percentage = null;
            $isPassed   = null;
        } elseif ($mode === 'score_only') {
            $isPassed = null;
        } else {
            if ($passingScore !== null && $percentage !== null) {
                $isPassed = ((float)$percentage >= (float)$passingScore) ? 1 : 0;
            } else {
                $isPassed = null;
            }
        }

        return (bool)$this->update($resultId, [
            'status'             => 'Graded',
            'graded_at'          => date('Y-m-d H:i:s'),
            'total_score'        => $totalScore,
            'percentage'         => $percentage,
            'is_passed'          => $isPassed,
            'questions_answered' => (int) ($answers['answered'] ?? 0),
            'correct_answers'    => (int) ($answers['correct'] ?? 0),
        ]);
    }

    /* ==========================
     * Statistik
     * ========================== */

    public function getAssessmentStatistics(int $assessmentId): array
    {
        $db = \Config\Database::connect();

        $asmQ = $db->table('assessments')->select('use_passing_score')->where('id', $assessmentId);
        if ($this->fieldExists('assessments', 'deleted_at')) {
            $asmQ->where('deleted_at', null);
        }
        $asm = $asmQ->get()->getRowArray();

        $mode = ((int)($asm['use_passing_score'] ?? 1) === 1) ? 'pass_fail' : 'score_only';

        $stQ = $db->table($this->table)
            ->select("
                COUNT(*) as total_attempts,
                SUM(CASE WHEN status IN ('Completed','Graded') THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) as passed,
                AVG(CASE WHEN status IN ('Completed','Graded') THEN percentage ELSE NULL END) as avg_percentage,
                MAX(CASE WHEN status IN ('Completed','Graded') THEN percentage ELSE NULL END) as highest_percentage,
                MIN(CASE WHEN status IN ('Completed','Graded') THEN percentage ELSE NULL END) as lowest_percentage,
                AVG(time_spent_seconds) as avg_time
            ")
            ->where('assessment_id', $assessmentId);

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $stQ->where('deleted_at', null);
        }

        $stats = $stQ->get()->getRowArray();

        $result = [
            'total_attempts'       => (int) ($stats['total_attempts'] ?? 0),
            'completed'            => (int) ($stats['completed'] ?? 0),
            'in_progress'          => (int) ($stats['in_progress'] ?? 0),
            'passed'               => (int) ($stats['passed'] ?? 0),
            'failed'               => 0,
            'pass_rate'            => 0,
            'average_score'        => round((float) ($stats['avg_percentage'] ?? 0), 2),
            'highest_score'        => round((float) ($stats['highest_percentage'] ?? 0), 2),
            'lowest_score'         => round((float) ($stats['lowest_percentage'] ?? 0), 2),
            'average_time_minutes' => round(((float) ($stats['avg_time'] ?? 0)) / 60, 2),
        ];

        if ($mode === 'pass_fail') {
            $result['failed'] = $result['completed'] - $result['passed'];
            if ($result['completed'] > 0) {
                $result['pass_rate'] = round(($result['passed'] / $result['completed']) * 100, 2);
            }
        } else {
            $result['failed']    = 0;
            $result['pass_rate'] = 0;
        }

        return $result;
    }

    public function getStudentStatistics(int $studentId): array
    {
        $db = \Config\Database::connect();

        $q = $db->table($this->table)
            ->select("
                COUNT(*) as total_assessments,
                SUM(CASE WHEN status IN ('Completed','Graded') THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) as passed,
                AVG(CASE WHEN status IN ('Completed','Graded') THEN percentage ELSE NULL END) as avg_score
            ")
            ->where('student_id', $studentId);

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $q->where('deleted_at', null);
        }

        $stats = $q->get()->getRowArray();

        return [
            'total_assessments' => (int) ($stats['total_assessments'] ?? 0),
            'completed'         => (int) ($stats['completed'] ?? 0),
            'in_progress'       => (int) ($stats['in_progress'] ?? 0),
            'passed'            => (int) ($stats['passed'] ?? 0),
            'average_score'     => round((float) ($stats['avg_score'] ?? 0), 2),
        ];
    }

    public function getNeedingReview(?int $counselorId = null): array
    {
        $stuMeta = $this->applyStudentNameSelectAndJoin('students', 'student_users');

        $builder = $this->select("
                {$this->table}.*,
                assessments.title as assessment_title,
                {$stuMeta['select']},
                students.nisn
            ")
            ->join('assessments', 'assessments.id = '.$this->table.'.assessment_id AND '.$this->joinSoftDeleteGuard('assessments', 'assessments'), 'inner')
            ->join('students',   'students.id = '.$this->table.'.student_id AND '.$this->joinSoftDeleteGuard('students', 'students'), 'inner');

        if (!empty($stuMeta['needsJoinUsers'])) {
            $builder->join('users AS student_users', 'student_users.id = students.user_id AND '.$this->joinSoftDeleteGuard('student_users', 'users'), 'left');
        }

        $builder->where($this->table.'.status', 'Graded')
            ->where($this->table.'.reviewed_by', null);

        if ($counselorId) {
            $builder->where('assessments.created_by', $counselorId);
        }

        return $builder->orderBy($this->table.'.completed_at', 'ASC')
            ->asArray()
            ->findAll();
    }

    public function getRecentResults(int $limit = 10, array $filters = []): array
    {
        $stuMeta = $this->applyStudentNameSelectAndJoin('students', 'student_users');

        $builder = $this->select("
                {$this->table}.*,
                assessments.title as assessment_title,
                {$stuMeta['select']},
                students.nisn
            ")
            ->join('assessments', 'assessments.id = '.$this->table.'.assessment_id AND '.$this->joinSoftDeleteGuard('assessments', 'assessments'), 'inner')
            ->join('students',   'students.id = '.$this->table.'.student_id AND '.$this->joinSoftDeleteGuard('students', 'students'), 'inner');

        if (!empty($stuMeta['needsJoinUsers'])) {
            $builder->join('users AS student_users', 'student_users.id = students.user_id AND '.$this->joinSoftDeleteGuard('student_users', 'users'), 'left');
        }

        $builder->whereIn($this->table.'.status', ['Completed', 'Graded']);

        if (!empty($filters['counselor_id'])) {
            $builder->where('assessments.created_by', $filters['counselor_id']);
        }

        return $builder->orderBy($this->table.'.completed_at', 'DESC')
            ->limit($limit)
            ->asArray()
            ->findAll();
    }

    public function getTopPerformers(int $assessmentId, int $limit = 10): array
    {
        $stuMeta = $this->applyStudentNameSelectAndJoin('students', 'student_users');

        $builder = $this->select("
                {$this->table}.*,
                {$stuMeta['select']},
                students.nisn,
                " . ($this->fieldExists('students', 'nis') ? "students.nis," : "NULL AS nis,") . "
                classes.class_name
            ")
            ->join('students', 'students.id = '.$this->table.'.student_id AND '.$this->joinSoftDeleteGuard('students', 'students'), 'inner');

        if (!empty($stuMeta['needsJoinUsers'])) {
            $builder->join('users AS student_users', 'student_users.id = students.user_id AND '.$this->joinSoftDeleteGuard('student_users', 'users'), 'left');
        }

        $builder->join('classes',  'classes.id = students.class_id AND '.$this->joinSoftDeleteGuard('classes', 'classes'), 'left')
            ->where($this->table.'.assessment_id', $assessmentId)
            ->where($this->table.'.status', 'Graded')
            ->orderBy($this->table.'.percentage', 'DESC')
            ->limit($limit)
            ->asArray();

        return $builder->findAll();
    }

    public function getInProgressAttempt(int $assessmentId, int $studentId): ?array
    {
        return $this->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->where('status', 'In Progress')
            ->orderBy('id', 'DESC')
            ->asArray()
            ->first();
    }

    /* Extra helpers untuk halaman Available/Results */

    /** Hitung attempt non-deleted untuk 1 asesmen oleh 1 siswa (status yang menguras kuota) */
    public function attemptsUsed(int $assessmentId, int $studentId): int
    {
        return $this->where([
                'assessment_id' => $assessmentId,
                'student_id'    => $studentId,
            ])->whereIn('status', self::QUOTA_STATUSES)
              ->countAllResults();
    }

    /** Ambil attempt terbaru (non-deleted) untuk 1 asesmen oleh 1 siswa */
    public function latestAttempt(int $assessmentId, int $studentId): ?array
    {
        return $this->where([
                'assessment_id' => $assessmentId,
                'student_id'    => $studentId,
            ])->orderBy('attempt_number', 'DESC')
              ->orderBy('id', 'DESC')
              ->asArray()
              ->first();
    }

    /** Cek apakah ada attempt aktif (Assigned / In Progress) */
    public function activeAttempt(int $assessmentId, int $studentId): ?array
    {
        return $this->where([
                'assessment_id' => $assessmentId,
                'student_id'    => $studentId,
            ])->whereIn('status', ['Assigned','In Progress'])
              ->orderBy('attempt_number','DESC')
              ->orderBy('id','DESC')
              ->asArray()
              ->first();
    }

    /* ---------- Waktu ---------- */

    /**
     * Hitung detik berlalu sejak started_at hingga $now (default waktu saat ini).
     */
    public static function calcElapsedFromStartedAt(?string $startedAt, ?string $now = null): int
    {
        if (!$startedAt) return 0;
        $now = $now ?: date('Y-m-d H:i:s');
        return max(0, strtotime($now) - strtotime($startedAt));
    }

    /**
     * Tambah akumulasi detik ke time_spent_seconds secara aman.
     */
    public function addElapsedSeconds(int $resultId, int $additionalSeconds): bool
    {
        if ($additionalSeconds <= 0) return true;
        $row = $this->asArray()->find($resultId);
        if (!$row) return false;
        $newVal = (int)(isset($row['time_spent_seconds']) ? $row['time_spent_seconds'] : 0) + $additionalSeconds;
        return (bool)$this->update($resultId, ['time_spent_seconds' => $newVal, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Hitung attempt yang memakan kuota.
     * Assigned & In Progress tidak dihitung sebagai attempt terpakai.
     */
    public function countUsedAttempts(int $assessmentId, int $studentId): int
    {
        return $this->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->whereIn('status', self::QUOTA_STATUSES)
            ->countAllResults();
    }

    public function getAttemptHistory(int $assessmentId, int $studentId): array
    {
        return $this->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->orderBy('attempt_number', 'DESC')
            ->asArray()
            ->findAll();
    }

    /**
     * Tandai kadaluarsa attempt yang terlalu lama In Progress.
     * @return int jumlah baris yang ditandai Expired
     */
    public function expireOldResults(int $hoursOld = 24): int
    {
        $expireTime = date('Y-m-d H:i:s', strtotime("-{$hoursOld} hours"));

        $builder = $this->db->table($this->table);
        $builder->where('status', 'In Progress')
                ->where('started_at <', $expireTime)
                ->update([
                    'status'     => 'Expired',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

        return (int)$this->db->affectedRows();
    }

    /**
     * Attempt In Progress terbaru (alias lain).
     */
    public function findInProgress(int $assessmentId, int $studentId): ?array
    {
        return $this->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->where('status', 'In Progress')
            ->orderBy('id', 'DESC')
            ->asArray()
            ->first();
    }

    /**
     * Buat (jika belum ada) baris Assigned untuk siswa-asesmen ini.
     * attempt_number diisi otomatis oleh autoAttemptNumber().
     */
    public function createAssignment(int $assessmentId, int $studentId): int
    {
        // Jika sudah ada Assigned terbaru, pakai itu saja.
        $exist = $this->where([
            'assessment_id' => $assessmentId,
            'student_id'    => $studentId,
            'status'        => 'Assigned',
        ])->orderBy('id','DESC')->asArray()->first();

        if ($exist) return (int)$exist['id'];

        $db = \Config\Database::connect();

        $qQ = $db->table('assessment_questions')->where('assessment_id', $assessmentId);
        if ($this->fieldExists('assessment_questions', 'deleted_at')) {
            $qQ->where('deleted_at', null);
        }
        $totalQuestions = (int)$qQ->countAllResults();

        $msQ = $db->table('assessment_questions')->selectSum('points')->where('assessment_id', $assessmentId);
        if ($this->fieldExists('assessment_questions', 'deleted_at')) {
            $msQ->where('deleted_at', null);
        }
        $maxScoreRow = $msQ->get()->getRowArray();

        $data = [
            'assessment_id'      => $assessmentId,
            'student_id'         => $studentId,
            'status'             => 'Assigned',
            'total_score'        => 0.0,
            'max_score'          => (float) ($maxScoreRow['points'] ?? 0),
            'percentage'         => null,
            'is_passed'          => null,
            'questions_answered' => 0,
            'total_questions'    => $totalQuestions,
            'time_spent_seconds' => 0,
        ];

        return (int)$this->insert($data, true);
    }

    /**
     * Klaim baris Assigned menjadi In Progress saat siswa menekan "Kerjakan".
     */
    public function claimAssignedToStart(int $assessmentId, int $studentId)
    {
        $row = $this->where([
            'assessment_id' => $assessmentId,
            'student_id'    => $studentId,
            'status'        => 'Assigned',
        ])->orderBy('id','DESC')->asArray()->first();

        if (!$row) return false;

        $now = date('Y-m-d H:i:s');
        $ok  = $this->update((int)$row['id'], [
            'status'             => 'In Progress',
            'started_at'         => $now,
            'updated_at'         => $now,
            'time_spent_seconds' => 0,
        ]);
        return $ok ? (int)$row['id'] : false;
    }

    /**
     * Tambahan aman: tandai attempt In Progress lain menjadi Abandoned (opsional dipakai service/controller).
     * Tidak menghapus fungsi yang ada, hanya menambah utilitas.
     */
    public function abandonOtherInProgress(int $assessmentId, int $studentId, ?int $exceptResultId = null): int
    {
        $builder = $this->db->table($this->table)
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->where('status', 'In Progress');

        if ($this->fieldExists($this->table, 'deleted_at')) {
            $builder->where('deleted_at', null);
        }

        if ($exceptResultId) {
            $builder->where('id !=', $exceptResultId);
        }

        $builder->update([
            'status'     => 'Abandoned',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->affectedRows();
    }

    /* ==========================
     * Helpers kecil
     * ========================== */

    private function looksJson($value): bool
    {
        if (!is_string($value)) return false;
        $v = trim($value);
        if ($v === '') return false;
        if ($v[0] !== '{' && $v[0] !== '[' && $v[0] !== '"') return false;
        json_decode($v, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
