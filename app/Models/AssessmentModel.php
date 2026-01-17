<?php

/**
 * File Path: app/Models/AssessmentModel.php
 *
 * Assessment Model
 * Model untuk mengelola data asesmen psikologi dan minat bakat
 *
 * @package    SIB-K
 * @subpackage Models
 * @category   Model
 * @author     Development Team
 * @created    2025-01-06
 * @updated    2026-01-02
 */

namespace App\Models;

use CodeIgniter\Model;

class AssessmentModel extends Model
{
    // === Konfigurasi dasar (tanpa typed property untuk properti turunan CI) ===
    protected $table            = 'assessments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'title',
        'description',
        'assessment_type',
        'evaluation_mode',         // enum: pass_fail, score_only, survey
        'target_audience',
        'target_class_id',
        'target_grade',
        'created_by',
        'is_active',
        'is_published',
        'start_date',
        'end_date',
        'duration_minutes',
        'passing_score',
        'use_passing_score',
        'show_score_to_student',   // kontrol "Tampilkan nilai ke siswa"
        'max_attempts',
        'show_result_immediately',
        'allow_review',
        'result_release_at',
        'instructions',
        'total_questions',
        'total_participants',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Casting ringan agar konsisten di view/controller.
     * Catatan: $casts pada BaseModel CI4 bertipe array, maka deklarasikan bertipe array di sini.
     */
    protected array $casts = [
        'id'                      => 'integer',
        'created_by'              => 'integer',
        'is_active'               => 'integer',
        'is_published'            => 'integer',
        'duration_minutes'        => '?integer',
        'passing_score'           => '?float',
        'use_passing_score'       => 'integer',
        'show_score_to_student'   => 'integer',
        'max_attempts'            => 'integer',
        'total_questions'         => 'integer',
        'total_participants'      => 'integer',
        'show_result_immediately' => 'integer',
        'allow_review'            => 'integer',
    ];

    // Validation (disimpan untuk referensi; dinonaktifkan via $skipValidation)
    protected $validationRules = [
        'title'                    => 'required|max_length[200]',
        'assessment_type'          => 'required|max_length[50]',
        'evaluation_mode'          => 'permit_empty|in_list[pass_fail,score_only,survey]',
        'target_audience'          => 'required|in_list[Individual,Class,Grade,All]',
        'target_class_id'          => 'permit_empty|integer',
        'target_grade'             => 'permit_empty|max_length[10]',
        'created_by'               => 'permit_empty|integer',
        'is_active'                => 'permit_empty|in_list[0,1]',
        'is_published'             => 'permit_empty|in_list[0,1]',
        'duration_minutes'         => 'permit_empty|integer|greater_than_equal_to[0]',
        'max_attempts'             => 'permit_empty|integer|greater_than_equal_to[1]',
        'use_passing_score'        => 'permit_empty|in_list[0,1]',
        'show_score_to_student'    => 'permit_empty|in_list[0,1]',
        'passing_score'            => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'start_date'               => 'permit_empty|valid_date',
        'end_date'                 => 'permit_empty|valid_date',
        'result_release_at'        => 'permit_empty|valid_date',
        'show_result_immediately'  => 'permit_empty|in_list[0,1]',
        'allow_review'             => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'Judul asesmen harus diisi',
            'max_length' => 'Judul maksimal 200 karakter',
        ],
        'assessment_type' => [
            'required' => 'Jenis asesmen harus dipilih',
        ],
        'target_audience' => [
            'required' => 'Target peserta harus dipilih',
            'in_list'  => 'Target peserta tidak valid',
        ],
        'evaluation_mode' => [
            'in_list'  => 'Mode penilaian tidak valid',
        ],
        'show_score_to_student' => [
            'in_list'  => 'Opsi tampilkan nilai ke siswa tidak valid',
        ],
    ];

    /**
     * Penting:
     * - Dinonaktifkan agar update parsial (publish/unpublish/toggle) tidak gagal karena rule 'required'.
     * - Validasi sebaiknya dilakukan di Validation class / Controller / Service.
     */
    protected $skipValidation       = true;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['normalizeForSave', 'ensureCreatedBy'];
    protected $beforeUpdate   = ['normalizeForSave'];

    /** Status yang dihitung sebagai MENGURAS KUOTA percobaan */
    public const QUOTA_STATUSES = ['Completed','Graded','Expired','Abandoned'];

    public function scopeActive()
    {
        return $this->where('is_active', 1);
    }

    /** Isi created_by dari session jika kosong */
    protected function ensureCreatedBy(array $data)
    {
        if (empty($data['data']['created_by'])) {
            $uid = (int) (session('user_id') ?? 0);
            if ($uid > 0) {
                $data['data']['created_by'] = $uid;
            }
        }
        return $data;
    }

    /**
     * Normalisasi data sebelum insert/update.
     * - Ubah '' dari form menjadi NULL untuk field yang memang nullable.
     * - Normalisasi flag boolean menjadi 0/1.
     * - Clamp passing_score ke rentang 0..100 namun tetap boleh NULL.
     */
    protected function normalizeForSave(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $data;
        }

        $arr =& $data['data'];

        // Ubah string kosong menjadi NULL untuk field-field nullable
        foreach ([
            'duration_minutes',
            'start_date',
            'end_date',
            'result_release_at',
            'target_class_id',
            'target_grade',
            'passing_score',
        ] as $f) {
            if (array_key_exists($f, $arr) && $arr[$f] === '') {
                $arr[$f] = null;
            }
        }

        // Normalisasi flag boolean kecil (tinyint 0/1)
        foreach ([
            'is_active',
            'is_published',
            'show_result_immediately',
            'allow_review',
            'use_passing_score',
            'show_score_to_student',
        ] as $flag) {
            if (array_key_exists($flag, $arr)) {
                $arr[$flag] = (int) (
                    (string) $arr[$flag] === '1'
                    || $arr[$flag] === 1
                    || $arr[$flag] === true
                );
            }
        }

        // Clamp passing_score bila ada dan bukan NULL
        if (array_key_exists('passing_score', $arr) && $arr['passing_score'] !== null) {
            if (is_numeric($arr['passing_score'])) {
                $ps = (float) $arr['passing_score'];
                if ($ps < 0) $ps = 0;
                if ($ps > 100) $ps = 100;
                $arr['passing_score'] = $ps;
            } else {
                $arr['passing_score'] = null;
            }
        }

        return $data;
    }

    /**
     * Get all active and published assessments
     */
    public function getActiveAssessments()
    {
        $today = date('Y-m-d');

        return $this->select('assessments.*, users.full_name as creator_name')
            ->join('users', 'users.id = assessments.created_by AND users.deleted_at IS NULL', 'left')
            ->where('assessments.is_active', 1)
            ->where('assessments.is_published', 1)
            ->groupStart()
                ->where('assessments.start_date <=', $today)
                ->orWhere('assessments.start_date', null)
            ->groupEnd()
            ->groupStart()
                ->where('assessments.end_date >=', $today)
                ->orWhere('assessments.end_date', null)
            ->groupEnd()
            ->orderBy('assessments.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get assessments by counselor
     */
    public function getByCounselor($counselorId, $filters = [])
    {
        $builder = $this->select('assessments.*, users.full_name as creator_name, classes.class_name as target_class_name')
            ->join('users', 'users.id = assessments.created_by AND users.deleted_at IS NULL', 'left')
            ->join('classes', 'classes.id = assessments.target_class_id AND classes.deleted_at IS NULL', 'left')
            ->where('assessments.created_by', $counselorId);

        // Apply filters
        if (!empty($filters['assessment_type'])) {
            $builder->where('assessments.assessment_type', $filters['assessment_type']);
        }

        if (array_key_exists('is_published', $filters) && $filters['is_published'] !== '' && $filters['is_published'] !== null) {
            $builder->where('assessments.is_published', (int) $filters['is_published']);
        }

        if (!empty($filters['target_audience'])) {
            $builder->where('assessments.target_audience', $filters['target_audience']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('assessments.title', $filters['search'])
                ->orLike('assessments.description', $filters['search'])
                ->groupEnd();
        }

        $builder->orderBy('assessments.created_at', 'DESC');

        return $builder->findAll();
    }

    /**
     * Get assessment with full details
     */
    public function getAssessmentWithDetails($id)
    {
        return $this->select('assessments.*, users.full_name as creator_name, users.email as creator_email, classes.class_name as target_class_name')
            ->join('users', 'users.id = assessments.created_by AND users.deleted_at IS NULL', 'left')
            ->join('classes', 'classes.id = assessments.target_class_id AND classes.deleted_at IS NULL', 'left')
            ->where('assessments.id', $id)
            ->first();
    }

    /**
     * Get available assessments for student
     * - Hormati rentang tanggal & publish/active
     * - Target 'Individual' hanya muncul bila siswa benar-benar di-assign
     *   (assessment_assignees) atau sudah punya result (pre-allocation).
     * - attempt_count menghitung hanya status yang menguras kuota.
     */
    public function getAvailableForStudent($studentId)
    {
        $db    = \Config\Database::connect();
        $today = date('Y-m-d');
        $sid   = (int) $studentId;

        // Get student info
        $student = $db->table('students s')
            ->select('s.id, s.class_id, c.grade_level, c.grade')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.id', $sid)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$student) {
            return [];
        }

        // Grade bisa roman atau angka
        [$gradeRoman, $gradeNum] = $this->gradeRomanAndNumber($student['grade_level'] ?? $student['grade'] ?? null);
        $classId = (int) ($student['class_id'] ?? 0);

        // subquery attempt_count (hanya status kuota)
        $quotaStatuses = implode("','", array_map('addslashes', self::QUOTA_STATUSES));

        $attemptCountSql = "(SELECT COUNT(*)
                               FROM assessment_results r
                              WHERE r.assessment_id = assessments.id
                                AND r.student_id = {$sid}
                                AND r.deleted_at IS NULL
                                AND r.status IN ('{$quotaStatuses}')
                            ) as attempt_count";

        $builder = $this->select("assessments.*, users.full_name as creator_name, {$attemptCountSql}")
            ->join('users', 'users.id = assessments.created_by AND users.deleted_at IS NULL', 'left')
            ->where('assessments.is_active', 1)
            ->where('assessments.is_published', 1)
            ->groupStart()
                ->where('assessments.start_date <=', $today)
                ->orWhere('assessments.start_date', null)
            ->groupEnd()
            ->groupStart()
                ->where('assessments.end_date >=', $today)
                ->orWhere('assessments.end_date', null)
            ->groupEnd();

        // Target audience filter
        $builder->groupStart()
            // Semua siswa
            ->where('assessments.target_audience', 'All')

            // Target kelas
            ->orGroupStart()
                ->where('assessments.target_audience', 'Class')
                ->where('assessments.target_class_id', $classId)
            ->groupEnd()

            // Target tingkat (match roman/angka) - jika grade tidak diketahui, blok group ini
            ->orGroupStart()
                ->where('assessments.target_audience', 'Grade')
                ->groupStart();

        if ($gradeRoman !== null || $gradeNum !== null) {
            if ($gradeRoman !== null) {
                $builder->where('assessments.target_grade', $gradeRoman);
            }
            if ($gradeNum !== null) {
                $builder->orWhere('assessments.target_grade', $gradeNum);
            }
        } else {
            // grade tidak diketahui → jangan biarkan match ke target_grade IS NULL
            $builder->where('1=0', null, false);
        }

        $builder->groupEnd()
            ->groupEnd()

            // Target Individual: wajib assignment eksplisit ATAU sudah ada result (pre-allocated)
            ->orGroupStart()
                ->where('assessments.target_audience', 'Individual')
                ->groupStart()
                    ->where("EXISTS(SELECT 1 FROM assessment_assignees x WHERE x.assessment_id = assessments.id AND x.student_id = {$sid})", null, false)
                    ->orWhere("EXISTS(SELECT 1 FROM assessment_results  y WHERE y.assessment_id = assessments.id AND y.student_id = {$sid})", null, false)
                ->groupEnd()
            ->groupEnd()
        ->groupEnd();

        $builder->orderBy('assessments.start_date', 'ASC')
                ->orderBy('assessments.created_at', 'DESC');

        return $builder->findAll();
    }

    /**
     * Publish assessment
     */
    public function publishAssessment($id)
    {
        return $this->update($id, [
            'is_published' => 1,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Unpublish assessment
     */
    public function unpublishAssessment($id)
    {
        return $this->update($id, [
            'is_published' => 0,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleActiveStatus($id)
    {
        /** @var array<string,mixed>|null $assessment */
        $assessment = $this->asArray()->find($id);

        if (!$assessment) {
            return false;
        }
        if (is_object($assessment)) {
            $assessment = (array) $assessment;
        }

        return $this->update($id, [
            'is_active' => $assessment['is_active'] ? 0 : 1,
        ]);
    }

    /**
     * Get assessment statistics
     */
    public function getStatistics($id)
    {
        $db = \Config\Database::connect();

        $stats = [
            'total_participants' => 0,
            'completed'          => 0,
            'in_progress'        => 0,
            'average_score'      => 0,
            'highest_score'      => 0,
            'lowest_score'       => 0,
            'pass_rate'          => 0,
        ];

        // Get result statistics
        $results = $db->table('assessment_results')
            ->select('COUNT(*) as total,
                      SUM(CASE WHEN status IN ("Completed","Graded") THEN 1 ELSE 0 END) as completed,
                      SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END) as in_progress,
                      AVG(CASE WHEN status IN ("Completed","Graded") THEN percentage ELSE NULL END) as avg_score,
                      MAX(percentage) as max_score,
                      MIN(percentage) as min_score,
                      SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) as passed')
            ->where('assessment_id', $id)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($results) {
            $stats['total_participants'] = (int) ($results['total'] ?? 0);
            $stats['completed']          = (int) ($results['completed'] ?? 0);
            $stats['in_progress']        = (int) ($results['in_progress'] ?? 0);
            $stats['average_score']      = round((float) ($results['avg_score'] ?? 0), 2);
            $stats['highest_score']      = round((float) ($results['max_score'] ?? 0), 2);
            $stats['lowest_score']       = round((float) ($results['min_score'] ?? 0), 2);

            if ((int) ($results['completed'] ?? 0) > 0) {
                $stats['pass_rate'] = round(((float) ($results['passed'] ?? 0) / (float) $results['completed']) * 100, 2);
            }
        }

        return $stats;
    }

    /**
     * Get dashboard statistics for counselor
     */
    public function getCounselorStats($counselorId)
    {
        $cid = (int) $counselorId;

        // Pisahkan query agar kondisi tidak menumpuk
        $total = (new self())->where('created_by', $cid)->countAllResults();
        $published = (new self())->where('created_by', $cid)
            ->where('is_published', 1)
            ->countAllResults();
        $draft = (new self())->where('created_by', $cid)
            ->where('is_published', 0)
            ->countAllResults();
        $active = (new self())->where('created_by', $cid)
            ->where('is_active', 1)
            ->where('is_published', 1)
            ->countAllResults();

        return [
            'total_assessments' => (int) $total,
            'published'         => (int) $published,
            'draft'             => (int) $draft,
            'active'            => (int) $active,
        ];
    }

    /**
     * Get assessments by type
     */
    public function getByType($type)
    {
        return $this->where('assessment_type', $type)
            ->where('is_active', 1)
            ->where('is_published', 1)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Check if student can take assessment
     *
     * @return array [can_take => bool, message => string, attempts_left => int]
     */
    public function canStudentTakeAssessment($assessmentId, $studentId)
    {
        /** @var array<string,mixed>|null $assessment */
        $assessment = $this->asArray()->find((int) $assessmentId);

        if (!$assessment) {
            return [
                'can_take'      => false,
                'message'       => 'Asesmen tidak ditemukan',
                'attempts_left' => 0,
            ];
        }

        if (is_object($assessment)) {
            $assessment = (array) $assessment;
        }

        // Check published, active & window
        if (!(int) ($assessment['is_published'] ?? 0) || !(int) ($assessment['is_active'] ?? 0)) {
            return [
                'can_take'      => false,
                'message'       => 'Asesmen tidak tersedia',
                'attempts_left' => 0,
            ];
        }

        $today = date('Y-m-d');
        $start = !empty($assessment['start_date']) ? date('Y-m-d', strtotime($assessment['start_date'])) : null;
        $end   = !empty($assessment['end_date'])   ? date('Y-m-d', strtotime($assessment['end_date']))   : null;

        if ($start && $start > $today) {
            return [
                'can_take'      => false,
                'message'       => 'Asesmen belum dimulai',
                'attempts_left' => 0,
            ];
        }

        if ($end && $end < $today) {
            return [
                'can_take'      => false,
                'message'       => 'Asesmen sudah berakhir',
                'attempts_left' => 0,
            ];
        }

        // Kuota percobaan: hitung hanya status yang menguras kuota
        $db  = \Config\Database::connect();
        $sid = (int) $studentId;

        $attemptCount = $db->table('assessment_results')
            ->where('assessment_id', (int) $assessmentId)
            ->where('student_id', $sid)
            ->whereIn('status', self::QUOTA_STATUSES)
            ->where('deleted_at', null)
            ->countAllResults();

        $maxAttempts  = (int) ($assessment['max_attempts'] ?? 0);
        $attemptsLeft = $maxAttempts > 0 ? max(0, $maxAttempts - $attemptCount) : 0;

        if ($maxAttempts > 0 && $attemptCount >= $maxAttempts) {
            return [
                'can_take'      => false,
                'message'       => 'Anda sudah mencapai maksimal percobaan',
                'attempts_left' => $attemptsLeft,
            ];
        }

        // In Progress tidak boleh lebih dari satu
        $inProgressCount = $db->table('assessment_results')
            ->where('assessment_id', (int) $assessmentId)
            ->where('student_id', $sid)
            ->where('status', 'In Progress')
            ->where('deleted_at', null)
            ->countAllResults();

        if ($inProgressCount > 0) {
            return [
                'can_take'      => false,
                'message'       => 'Anda memiliki percobaan yang belum selesai',
                'attempts_left' => $attemptsLeft,
            ];
        }

        return [
            'can_take'      => true,
            'message'       => 'Anda dapat mengikuti asesmen',
            'attempts_left' => $attemptsLeft,
        ];
    }

    // ------------------------------------------------------------
    // Helpers privat
    // ------------------------------------------------------------

    /**
     * Kembalikan pasangan [ROMAN, ANGKA] dari grade (roman/angka/null).
     */
    private function gradeRomanAndNumber($grade): array
    {
        $romanMap = ['10' => 'X', '11' => 'XI', '12' => 'XII'];
        $numMap   = ['X' => '10', 'XI' => '11', 'XII' => '12'];

        $g = strtoupper(trim((string) $grade));
        if ($g === '') return [null, null];

        if (isset($numMap[$g])) {
            return [$g, $numMap[$g]];
        }
        if (isset($romanMap[$g])) {
            return [$romanMap[$g], $g];
        }
        return [null, null];
    }
}
