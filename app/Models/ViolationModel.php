<?php

/**
 * File Path: app/Models/ViolationModel.php
 *
 * Violation Model
 * Model untuk mengelola data pelanggaran siswa
 *
 * @package    SIB-K
 * @subpackage Models
 * @category   Model
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Models;

use CodeIgniter\Model;

class ViolationModel extends Model
{
    /** Konfigurasi repeat-offender agar mudah diubah dari satu tempat */
    public const REPEAT_WINDOW_DAYS = 90;  // jangka hari ke belakang
    public const REPEAT_THRESHOLD   = 3;   // ambang jumlah pelanggaran

    protected $table            = 'violations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    // ====== allowedFields (TERMASUK 'witness') ======
    protected $allowedFields    = [
        'student_id',
        'category_id',
        'violation_date',
        'violation_time',
        'location',
        'description',
        'witness',
        'evidence',
        'reported_by',
        'handled_by',
        'status',
        'resolution_notes',
        'resolution_date',
        'parent_notified',
        'parent_notified_at',
        'is_repeat_offender',
        'notes',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'student_id'     => 'required|integer',
        'category_id'    => 'required|integer',
        'violation_date' => 'required|valid_date',
        'violation_time' => 'permit_empty|regex_match[/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/]',
        'location'       => 'permit_empty|max_length[200]',
        'description'    => 'required|min_length[10]',
        'witness'        => 'permit_empty|max_length[200]',
        'reported_by'    => 'required|integer',
        'status'         => 'permit_empty|in_list[Dilaporkan,Dalam Proses,Selesai,Dibatalkan]',
    ];

    protected $validationMessages = [
        'student_id' => [
            'required' => 'Siswa harus dipilih',
            'integer'  => 'ID siswa tidak valid',
        ],
        'category_id' => [
            'required' => 'Kategori pelanggaran harus dipilih',
            'integer'  => 'ID kategori tidak valid',
        ],
        'violation_date' => [
            'required'   => 'Tanggal pelanggaran harus diisi',
            'valid_date' => 'Format tanggal tidak valid',
        ],
        'description' => [
            'required'   => 'Deskripsi pelanggaran harus diisi',
            'min_length' => 'Deskripsi minimal 10 karakter',
        ],
        'reported_by' => [
            'required' => 'Pelapor harus diisi',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['checkRepeatOffender'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = ['checkRepeatOffender'];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Helper: apply date_from/date_to ke builder dengan prefix table/alias.
     * @param mixed $builder
     */
    private function applyDateRange($builder, array $filters = [], string $dateColumn = 'violations.violation_date')
    {
        if (!empty($filters['date_from'])) {
            $builder->where($dateColumn . ' >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where($dateColumn . ' <=', $filters['date_to']);
        }
        return $builder;
    }

    /**
     * Helper: filter "belum notifikasi" yang aman (0 atau NULL).
     * @param mixed $builder
     */
    private function applyParentNotifiedFilter($builder, $value, string $column = 'violations.parent_notified')
    {
        if ($value === 'no') {
            // treat NULL as not notified
            $builder->groupStart()
                ->where($column, 0)
                ->orWhere($column, null)
                ->groupEnd();
        } elseif ($value === 'yes') {
            $builder->where($column, 1);
        }
        return $builder;
    }

    /**
     * Hitung & set flag repeat offender otomatis.
     * Aturan: ≥ REPEAT_THRESHOLD pelanggaran dalam REPEAT_WINDOW_DAYS terakhir (termasuk record ini).
     * Berjalan pada beforeInsert & beforeUpdate.
     *
     * Perbaikan:
     * - Jika status record yang sedang disimpan adalah "Dibatalkan", paksa is_repeat_offender=0.
     * - Abaikan pelanggaran yang "Dibatalkan" saat menghitung histori.
     */
    protected function checkRepeatOffender(array $data): array
    {
        if (empty($data['data']['student_id']) || empty($data['data']['violation_date'])) {
            return $data;
        }

        // Jika record ini dibatalkan, jangan pernah dianggap repeat offender.
        $currentStatus = (string)($data['data']['status'] ?? '');
        if ($currentStatus === 'Dibatalkan') {
            $data['data']['is_repeat_offender'] = 0;
            return $data;
        }

        $studentId = (int) $data['data']['student_id'];
        $dateStr   = substr((string) $data['data']['violation_date'], 0, 10); // Y-m-d

        try {
            $date = new \DateTime($dateStr);
        } catch (\Throwable $e) {
            // biarkan validasi tanggal yang menangani
            return $data;
        }

        $from = (clone $date)->modify('-' . self::REPEAT_WINDOW_DAYS . ' days')->format('Y-m-d');
        $to   = $date->format('Y-m-d');

        // Pakai koneksi DB bawaan model (lebih hemat daripada connect ulang).
        $db = $this->db ?? \Config\Database::connect();
        $qb = $db->table($this->table);

        $qb->select('COUNT(*) AS c')
            ->where('student_id', $studentId)
            ->where('deleted_at', null)
            ->where('violation_date >=', $from)
            ->where('violation_date <=', $to)
            ->where('status !=', 'Dibatalkan');

        // Jika update, jangan hitung record ini dua kali:
        // $data['id'] pada beforeUpdate bisa berupa int ATAU array id.
        if (!empty($data['id'])) {
            $currentId = is_array($data['id']) ? (int) reset($data['id']) : (int) $data['id'];
            if ($currentId > 0) {
                $qb->where('id !=', $currentId);
            }
        }

        $row = $qb->get()->getRowArray();
        $countPrev = (int) ($row['c'] ?? 0);

        // termasuk record yang sedang disimpan
        $predicted = $countPrev + 1;

        $data['data']['is_repeat_offender'] = ($predicted >= self::REPEAT_THRESHOLD) ? 1 : 0;
        return $data;
    }

    /**
     * Wrapper agar kompatibel dengan kode lain yang mungkin memanggil computeRepeatOffender().
     * Tidak menggandakan logika: cukup delegasikan ke checkRepeatOffender().
     */
    protected function computeRepeatOffender(array $data): array
    {
        return $this->checkRepeatOffender($data);
    }

    /**
     * Get violation with full details (joins)
     *
     * @param int $id
     * @return array|null
     */
    public function getViolationWithDetails($id)
    {
        return $this->select('violations.*,
                              students.nisn, students.nis,
                              student_users.full_name as student_name,
                              student_users.email as student_email,
                              classes.class_name,
                              violation_categories.category_name,
                              violation_categories.severity_level,
                              violation_categories.point_deduction,
                              reporter_users.full_name as reporter_name,
                              reporter_users.email as reporter_email,
                              handler_users.full_name as handler_name,
                              handler_users.email as handler_email,
                              (SELECT COUNT(*) FROM sanctions
                               WHERE sanctions.violation_id = violations.id
                               AND sanctions.deleted_at IS NULL) as sanction_count')
            ->join('students', 'students.id = violations.student_id')
            ->join('users as student_users', 'student_users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->join('violation_categories', 'violation_categories.id = violations.category_id')
            ->join('users as reporter_users', 'reporter_users.id = violations.reported_by')
            ->join('users as handler_users', 'handler_users.id = violations.handled_by', 'left')
            ->where('violations.id', $id)
            ->where('violations.deleted_at', null)
            ->first();
    }

    /**
     * Get violations with filters
     *
     * @param array $filters
     * @return array
     */
    public function getViolationsWithFilters($filters = [])
    {
        $builder = $this->select('violations.*,
                                  students.nisn,
                                  students.nis,
                                  student_users.full_name as student_name,
                                  classes.class_name,
                                  violation_categories.category_name,
                                  violation_categories.severity_level,
                                  violation_categories.point_deduction,
                                  reporter_users.full_name as reporter_name,
                                  handler_users.full_name as handler_name')
            ->join('students', 'students.id = violations.student_id')
            ->join('users as student_users', 'student_users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->join('violation_categories', 'violation_categories.id = violations.category_id')
            ->join('users as reporter_users', 'reporter_users.id = violations.reported_by')
            ->join('users as handler_users', 'handler_users.id = violations.handled_by', 'left')
            ->where('violations.deleted_at', null);

        // Apply filters
        if (!empty($filters['status'])) {
            $builder->where('violations.status', $filters['status']);
        }

        if (!empty($filters['severity_level'])) {
            $builder->where('violation_categories.severity_level', $filters['severity_level']);
        }

        if (!empty($filters['student_id'])) {
            $builder->where('violations.student_id', (int) $filters['student_id']);
        }

        if (!empty($filters['category_id'])) {
            $builder->where('violations.category_id', (int) $filters['category_id']);
        }

        // (Opsional) filter per kelas, membantu wali kelas/rekap kelas
        if (!empty($filters['class_id'])) {
            $builder->where('students.class_id', (int) $filters['class_id']);
        }

        // Date range
        $builder = $this->applyDateRange($builder, (array) $filters, 'violations.violation_date');

        if (!empty($filters['handled_by'])) {
            $builder->where('violations.handled_by', (int) $filters['handled_by']);
        }

        // (Opsional) filter pelapor
        if (!empty($filters['reported_by'])) {
            $builder->where('violations.reported_by', (int) $filters['reported_by']);
        }

        if (!empty($filters['is_repeat_offender'])) {
            $builder->where('violations.is_repeat_offender', 1);
        }

        if (!empty($filters['parent_notified'])) {
            $builder = $this->applyParentNotifiedFilter($builder, $filters['parent_notified'], 'violations.parent_notified');
        }

        // (Opsional) exclude cancelled tanpa mengubah default perilaku lama
        if (!empty($filters['exclude_cancelled'])) {
            $builder->where('violations.status !=', 'Dibatalkan');
        }

        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('student_users.full_name', $q)
                ->orLike('students.nisn', $q)
                ->orLike('students.nis', $q)
                ->orLike('violations.description', $q)
                ->orLike('violations.location', $q)
                ->orLike('violation_categories.category_name', $q)
                ->groupEnd();
        }

        // Order by date DESC
        $builder->orderBy('violations.violation_date', 'DESC');
        $builder->orderBy('violations.created_at', 'DESC');

        return $builder->findAll();
    }

    /**
     * Get violations by student
     *
     * Revisi:
     * - Tambah parameter $filters (opsional) untuk mendukung date_from/date_to
     *   (dipakai oleh ViolationService untuk hitung per Tahun Ajaran).
     * - Tetap kompatibel dengan pemanggilan lama: getByStudent($studentId, $limit)
     *
     * @param int $studentId
     * @param int|null $limit
     * @param array $filters
     * @return array
     */
    public function getByStudent($studentId, $limit = null, array $filters = [])
    {
        $studentId = (int) $studentId;

        $builder = $this->select('violations.*,
                                  violation_categories.category_name,
                                  violation_categories.severity_level,
                                  violation_categories.point_deduction')
            ->join('violation_categories', 'violation_categories.id = violations.category_id')
            ->where('violations.student_id', $studentId)
            ->where('violations.deleted_at', null)
            ->orderBy('violations.violation_date', 'DESC')
            ->orderBy('violations.created_at', 'DESC');

        // Date range filter (opsional)
        $builder = $this->applyDateRange($builder, $filters, 'violations.violation_date');

        // Optional filters tambahan (tidak memaksa, biar tidak merusak perilaku lama)
        if (!empty($filters['status'])) {
            $builder->where('violations.status', $filters['status']);
        }
        if (!empty($filters['exclude_cancelled'])) {
            $builder->where('violations.status !=', 'Dibatalkan');
        }

        if ($limit) {
            $builder->limit((int) $limit);
        }

        return $builder->findAll();
    }

    /**
     * Get statistics for violations
     *
     * Revisi:
     * - Tambah dukungan filter yang lebih lengkap agar kartu dashboard konsisten
     *   dengan tabel (status, student_id, category_id, handled_by, parent_notified, search, severity_level).
     *
     * @param array $filters
     * @return array
     */
    public function getStatistics($filters = [])
    {
        $db = $this->db ?? \Config\Database::connect();

        $builder = $db->table('violations v')
            ->select('COUNT(*) as total_violations,
                      SUM(CASE WHEN v.status = "Dilaporkan" THEN 1 ELSE 0 END) as reported,
                      SUM(CASE WHEN v.status = "Dalam Proses" THEN 1 ELSE 0 END) as in_process,
                      SUM(CASE WHEN v.status = "Selesai" THEN 1 ELSE 0 END) as completed,
                      SUM(CASE WHEN v.status = "Dibatalkan" THEN 1 ELSE 0 END) as cancelled,
                      SUM(CASE WHEN v.is_repeat_offender = 1 THEN 1 ELSE 0 END) as repeat_offenders,
                      SUM(CASE WHEN COALESCE(v.parent_notified,0) = 0 THEN 1 ELSE 0 END) as parents_not_notified')
            ->where('v.deleted_at', null);

        // Join opsional untuk filter severity/search
        $needJoinCat = !empty($filters['severity_level']);
        if ($needJoinCat) {
            $builder->join('violation_categories vc', 'vc.id = v.category_id', 'left');
        }

        $needJoinStudent = !empty($filters['search']) || !empty($filters['class_id']);
        if ($needJoinStudent) {
            $builder->join('students s', 's.id = v.student_id', 'left')
                    ->join('users su', 'su.id = s.user_id', 'left');
        }

        // Apply filters (opsional)
        if (!empty($filters['status'])) {
            $builder->where('v.status', $filters['status']);
        }
        if (!empty($filters['student_id'])) {
            $builder->where('v.student_id', (int) $filters['student_id']);
        }
        if (!empty($filters['category_id'])) {
            $builder->where('v.category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['handled_by'])) {
            $builder->where('v.handled_by', (int) $filters['handled_by']);
        }
        if (!empty($filters['reported_by'])) {
            $builder->where('v.reported_by', (int) $filters['reported_by']);
        }
        if (!empty($filters['is_repeat_offender'])) {
            $builder->where('v.is_repeat_offender', 1);
        }
        if (!empty($filters['parent_notified']) && $filters['parent_notified'] === 'no') {
            $builder->groupStart()
                ->where('v.parent_notified', 0)
                ->orWhere('v.parent_notified', null)
                ->groupEnd();
        }
        if (!empty($filters['class_id'])) {
            $builder->where('s.class_id', (int) $filters['class_id']);
        }
        if (!empty($filters['severity_level'])) {
            $builder->where('vc.severity_level', $filters['severity_level']);
        }

        // Apply date filter
        if (!empty($filters['date_from'])) {
            $builder->where('v.violation_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('v.violation_date <=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('su.full_name', $q)
                ->orLike('s.nisn', $q)
                ->orLike('s.nis', $q)
                ->orLike('v.description', $q)
                ->orLike('v.location', $q)
                ->groupEnd();
        }

        return $builder->get()->getRowArray();
    }

    /**
     * Get violations by severity level (statistics)
     *
     * Revisi:
     * - total_points sebaiknya tidak menghitung pelanggaran Dibatalkan,
     *   agar selaras dengan getStudentTotalPoints().
     * - Tambah dukungan filter lain agar chart/rekap konsisten dengan tabel.
     *
     * @param array $filters
     * @return array
     */
    public function getStatsBySeverity($filters = [])
    {
        $db = $this->db ?? \Config\Database::connect();

        $builder = $db->table('violations v')
            ->select('vc.severity_level,
                      COUNT(v.id) as violation_count,
                      SUM(vc.point_deduction) as total_points')
            ->join('violation_categories vc', 'vc.id = v.category_id')
            ->where('v.deleted_at', null)
            ->where('v.status !=', 'Dibatalkan')
            ->groupBy('vc.severity_level');

        // Join opsional untuk filter search/class
        $needJoinStudent = !empty($filters['search']) || !empty($filters['class_id']);
        if ($needJoinStudent) {
            $builder->join('students s', 's.id = v.student_id', 'left')
                    ->join('users su', 'su.id = s.user_id', 'left');
        }

        // Apply filters
        if (!empty($filters['status'])) {
            // jika user minta status spesifik, hormati (tetap di atas sudah exclude Dibatalkan)
            $builder->where('v.status', $filters['status']);
        }
        if (!empty($filters['student_id'])) {
            $builder->where('v.student_id', (int) $filters['student_id']);
        }
        if (!empty($filters['category_id'])) {
            $builder->where('v.category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['handled_by'])) {
            $builder->where('v.handled_by', (int) $filters['handled_by']);
        }
        if (!empty($filters['reported_by'])) {
            $builder->where('v.reported_by', (int) $filters['reported_by']);
        }
        if (!empty($filters['is_repeat_offender'])) {
            $builder->where('v.is_repeat_offender', 1);
        }
        if (!empty($filters['parent_notified']) && $filters['parent_notified'] === 'no') {
            $builder->groupStart()
                ->where('v.parent_notified', 0)
                ->orWhere('v.parent_notified', null)
                ->groupEnd();
        }
        if (!empty($filters['class_id'])) {
            $builder->where('s.class_id', (int) $filters['class_id']);
        }
        if (!empty($filters['severity_level'])) {
            // kalau user filter severity tertentu, maka hasil hanya 1 grup
            $builder->where('vc.severity_level', $filters['severity_level']);
        }

        // Apply date filter
        if (!empty($filters['date_from'])) {
            $builder->where('v.violation_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('v.violation_date <=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('su.full_name', $q)
                ->orLike('s.nisn', $q)
                ->orLike('s.nis', $q)
                ->orLike('v.description', $q)
                ->orLike('v.location', $q)
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get top violators (students with most violations)
     *
     * Revisi:
     * - Tambah dukungan filter lain (class_id, handled_by, category_id, severity_level, search, date range)
     *   agar rekap konsisten dengan tabel.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopViolators($limit = 10, $filters = [])
    {
        $db = $this->db ?? \Config\Database::connect();

        $builder = $db->table('violations v')
            ->select('s.id,
                      s.nisn,
                      s.nis,
                      u.full_name as student_name,
                      c.class_name,
                      COUNT(v.id) as violation_count,
                      SUM(vc.point_deduction) as total_points,
                      MAX(v.violation_date) as last_violation_date')
            ->join('students s', 's.id = v.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('violation_categories vc', 'vc.id = v.category_id')
            ->where('v.deleted_at', null)
            ->where('v.status !=', 'Dibatalkan')
            ->groupBy('s.id')
            ->orderBy('violation_count', 'DESC')
            ->limit((int) $limit);

        // Apply filters
        if (!empty($filters['status'])) {
            // tetap sudah exclude Dibatalkan, tapi hormati status spesifik
            $builder->where('v.status', $filters['status']);
        }
        if (!empty($filters['student_id'])) {
            $builder->where('v.student_id', (int) $filters['student_id']);
        }
        if (!empty($filters['category_id'])) {
            $builder->where('v.category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['handled_by'])) {
            $builder->where('v.handled_by', (int) $filters['handled_by']);
        }
        if (!empty($filters['reported_by'])) {
            $builder->where('v.reported_by', (int) $filters['reported_by']);
        }
        if (!empty($filters['is_repeat_offender'])) {
            $builder->where('v.is_repeat_offender', 1);
        }
        if (!empty($filters['parent_notified']) && $filters['parent_notified'] === 'no') {
            $builder->groupStart()
                ->where('v.parent_notified', 0)
                ->orWhere('v.parent_notified', null)
                ->groupEnd();
        }
        if (!empty($filters['class_id'])) {
            $builder->where('s.class_id', (int) $filters['class_id']);
        }
        if (!empty($filters['severity_level'])) {
            $builder->where('vc.severity_level', $filters['severity_level']);
        }

        // Apply date filter
        if (!empty($filters['date_from'])) {
            $builder->where('v.violation_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('v.violation_date <=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('u.full_name', $q)
                ->orLike('s.nisn', $q)
                ->orLike('s.nis', $q)
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Mark parent as notified
     *
     * @param int $id
     * @return bool
     */
    public function markParentNotified($id)
    {
        return $this->update($id, [
            'parent_notified'    => 1,
            'parent_notified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get pending notifications (parent not notified)
     *
     * @param int $limit
     * @return array
     */
    public function getPendingNotifications($limit = 20)
    {
        $builder = $this->select('violations.*,
                              students.nisn,
                              students.nis,
                              student_users.full_name as student_name,
                              student_users.email as student_email,
                              classes.class_name,
                              violation_categories.category_name,
                              violation_categories.severity_level')
            ->join('students', 'students.id = violations.student_id')
            ->join('users as student_users', 'student_users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->join('violation_categories', 'violation_categories.id = violations.category_id')
            ->where('violations.status !=', 'Dibatalkan')
            ->where('violations.deleted_at', null);

        // parent_notified 0 atau NULL
        $builder->groupStart()
            ->where('violations.parent_notified', 0)
            ->orWhere('violations.parent_notified', null)
            ->groupEnd();

        return $builder
            ->orderBy('violations.violation_date', 'DESC')
            ->limit((int) $limit)
            ->findAll();
    }

    /**
     * Get student total points from violations
     *
     * @param int $studentId
     * @param array $filters (optional date range)
     * @return int
     */
    public function getStudentTotalPoints($studentId, $filters = [])
    {
        $db = $this->db ?? \Config\Database::connect();

        $builder = $db->table('violations')
            ->select('SUM(violation_categories.point_deduction) as total_points')
            ->join('violation_categories', 'violation_categories.id = violations.category_id')
            ->where('violations.student_id', (int) $studentId)
            ->where('violations.deleted_at', null)
            ->where('violations.status !=', 'Dibatalkan');

        // Apply date filter if provided
        if (!empty($filters['date_from'])) {
            $builder->where('violations.violation_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('violations.violation_date <=', $filters['date_to']);
        }

        $result = $builder->get()->getRowArray();

        return (int) ($result['total_points'] ?? 0);
    }

    /**
     * Get violations for monthly report
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getMonthlyViolations($year, $month)
    {
        $startDate = date('Y-m-01', strtotime("$year-$month-01"));
        $endDate   = date('Y-m-t', strtotime("$year-$month-01"));

        return $this->getViolationsWithFilters([
            'date_from' => $startDate,
            'date_to'   => $endDate,
        ]);
    }
}
