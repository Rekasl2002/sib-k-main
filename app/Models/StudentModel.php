<?php

/**
 * File Path: app/Models/StudentModel.php
 *
 * Student Model
 * Mengelola data siswa dengan relasi ke users, classes, dan parents
 *
 * Catatan (Normalisasi Nama):
 * - Kolom students.full_name sudah DIHAPUS.
 * - Nama siswa sekarang single source of truth: users.full_name (JOIN via students.user_id).
 * - Model ini menjaga kompatibilitas dengan banyak view/controller dengan menyediakan key "full_name"
 *   dari users.full_name pada query-query detail/list.
 *
 * Catatan (Revisi Poin Pelanggaran):
 * - Kolom students.total_violation_points diperlakukan sebagai CACHE untuk Tahun Ajaran AKTIF
 *   (gabung ganjil+genap via academic_years.year_name).
 * - Perhitungan/refresh idealnya dilakukan oleh ViolationService, tetapi model ini juga menyediakan
 *   helper resync agar pemanggilan lama (updateViolationPoints) tidak membuat cache menjadi salah.
 *
 * @package    SIB-K
 * @subpackage Models
 * @category   Academic Data
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table            = 'students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    /**
     * IMPORTANT:
     * - Jangan masukkan 'full_name' di allowedFields karena kolom students.full_name sudah dihapus.
     * - Nama siswa dikelola di tabel users (users.full_name).
     */
    protected $allowedFields = [
        'user_id',
        'class_id',
        'nisn',
        'nis',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'parent_id',
        'admission_date',
        'status',
        'total_violation_points',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Matikan validasi level Model agar tidak bentrok dengan
     * App\Validation\StudentValidation yang dipanggil dari Controller.
     */
    protected $skipValidation       = true;
    protected $cleanValidationRules = true;

    /**
     * (Opsional) Aturan disimpan jika suatu saat ingin mengaktifkan validasi model.
     * NOTE: full_name dihapus dari rules karena bukan field students lagi.
     */
    protected $validationRules = [
        'user_id'        => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]|is_unique[students.user_id,id,{id}]',
        'nisn'           => 'permit_empty|numeric|exact_length[10]|is_unique[students.nisn,id,{id}]',
        'nis'            => 'permit_empty|numeric|min_length[4]|max_length[20]|is_unique[students.nis,id,{id}]',
        'gender'         => 'permit_empty|in_list[L,P]',
        'birth_place'    => 'permit_empty|max_length[100]',
        'birth_date'     => 'permit_empty|valid_date[Y-m-d]',
        'religion'       => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
        'address'        => 'permit_empty|max_length[255]',
        'parent_id'      => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
        'admission_date' => 'permit_empty|valid_date[Y-m-d]',
        'status'         => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar]',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required'  => 'User ID harus diisi',
            'is_unique' => 'User sudah terdaftar sebagai siswa',
        ],
        'nisn' => [
            'required'   => 'NISN harus diisi',
            'min_length' => 'NISN minimal 10 digit',
            'is_unique'  => 'NISN sudah terdaftar',
            'numeric'    => 'NISN harus berupa angka',
        ],
        'nis' => [
            'required'  => 'NIS harus diisi',
            'is_unique' => 'NIS sudah terdaftar',
        ],
        'gender' => [
            'required' => 'Jenis kelamin harus dipilih',
            'in_list'  => 'Jenis kelamin harus L atau P',
        ],
    ];

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // ==========================================================
    // Helper internal: resolve range Tahun Ajaran aktif (year_name)
    // Menggabungkan ganjil+genap: MIN(start_date) s/d MAX(end_date)
    // ==========================================================

    /**
     * @return array{year_name:?string,date_from:?string,date_to:?string}
     */
    private function resolveActiveSchoolYearRange(): array
    {
        $db = \Config\Database::connect();

        // 1) Prefer is_active=1
        $row = $db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('updated_at', 'DESC')
            ->get(1)
            ->getRowArray();

        $yearName = trim((string) ($row['year_name'] ?? ''));

        // 2) Fallback: berdasarkan tanggal hari ini
        if ($yearName === '') {
            $today = date('Y-m-d');
            $row = $db->table('academic_years')
                ->select('year_name')
                ->where('deleted_at', null)
                ->where('start_date <=', $today)
                ->where('end_date >=', $today)
                ->orderBy('start_date', 'DESC')
                ->get(1)
                ->getRowArray();

            $yearName = trim((string) ($row['year_name'] ?? ''));
        }

        if ($yearName === '') {
            return ['year_name' => null, 'date_from' => null, 'date_to' => null];
        }

        $range = $db->table('academic_years')
            ->select('MIN(start_date) as date_from, MAX(end_date) as date_to')
            ->where('deleted_at', null)
            ->where('year_name', $yearName)
            ->get()
            ->getRowArray();

        return [
            'year_name' => $yearName,
            'date_from' => ($range['date_from'] ?? null) ?: null,
            'date_to'   => ($range['date_to'] ?? null) ?: null,
        ];
    }

    /**
     * Resync cache total_violation_points untuk siswa tertentu
     * berdasarkan Tahun Ajaran aktif (gabung ganjil+genap).
     *
     * NOTE: Idealnya dipanggil dari ViolationService, tapi disediakan di sini
     * agar pemanggilan lama (updateViolationPoints) tetap aman.
     */
    public function resyncViolationPointsForActiveYear(int $studentId): bool
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) return false;

        $range = $this->resolveActiveSchoolYearRange();

        // Jika range tidak ditemukan, fallback ke 0 (cache aman)
        if (empty($range['date_from']) || empty($range['date_to'])) {
            return (bool) $this->update($studentId, ['total_violation_points' => 0]);
        }

        $db = \Config\Database::connect();

        $row = $db->table('violations')
            ->select('SUM(vc.point_deduction) as total_points')
            ->join('violation_categories vc', 'vc.id = violations.category_id')
            ->where('violations.deleted_at', null)
            ->where('violations.status !=', 'Dibatalkan')
            ->where('violations.student_id', $studentId)
            ->where('violations.violation_date >=', $range['date_from'])
            ->where('violations.violation_date <=', $range['date_to'])
            ->get()
            ->getRowArray();

        $total = (int) ($row['total_points'] ?? 0);
        if ($total < 0) $total = 0;

        return (bool) $this->update($studentId, ['total_violation_points' => $total]);
    }

    /**
     * Get student with complete details
     */
    public function getStudentWithDetails($studentId)
    {
        return $this->select(
                'students.*,
                 users.username, users.email,
                 users.full_name AS full_name,
                 users.full_name AS user_full_name,
                 users.phone, users.profile_photo,
                 classes.class_name, classes.grade_level, classes.major,
                 academic_years.year_name,
                 parent.full_name AS parent_name, parent.phone AS parent_phone, parent.email AS parent_email'
            )
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id', 'left')
            ->join('users AS parent', 'parent.id = students.parent_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.id', $studentId)
            ->first();
    }

    /**
     * Ambil data siswa berdasarkan user_id
     * (ditambah JOIN users supaya selalu tersedia full_name dari users)
     */
    public function getByUserId($userId)
    {
        return $this->select('students.*, users.full_name AS full_name, users.email, users.phone, users.username')
            ->join('users', 'users.id = students.user_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.user_id', $userId)
            ->first();
    }

    public function getByNISN($nisn)
    {
        return $this->select('students.*, users.full_name AS full_name, users.email, users.phone, users.username')
            ->join('users', 'users.id = students.user_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.nisn', $nisn)
            ->first();
    }

    public function getByNIS($nis)
    {
        return $this->select('students.*, users.full_name AS full_name, users.email, users.phone, users.username')
            ->join('users', 'users.id = students.user_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.nis', $nis)
            ->first();
    }

    /**
     * Get all students with details
     */
    public function getAllWithDetails()
    {
        return $this->select(
                'students.*,
                 users.full_name AS full_name, users.email, users.phone,
                 classes.class_name, classes.grade_level,
                 academic_years.year_name'
            )
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id', 'left')
            ->where('students.deleted_at', null)
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
    }

    public function getByClass($classId)
    {
        return $this->select('students.*, users.full_name AS full_name, users.email, users.phone, users.profile_photo')
            ->join('users', 'users.id = students.user_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.class_id', $classId)
            ->where('students.status', 'Aktif')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
    }

    public function getByGradeLevel($gradeLevel)
    {
        return $this->select('students.*, users.full_name AS full_name, classes.class_name')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('classes.grade_level', $gradeLevel)
            ->where('students.status', 'Aktif')
            ->orderBy('classes.class_name', 'ASC')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
    }

    public function getByParent($parentId)
    {
        return $this->select('students.*, users.full_name AS full_name, users.email, classes.class_name, classes.grade_level')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.parent_id', $parentId)
            ->where('students.status', 'Aktif')
            ->findAll();
    }

    public function searchStudents($keyword)
    {
        return $this->select('students.*, users.full_name AS full_name, users.email, classes.class_name')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->groupStart()
                ->like('users.full_name', $keyword)
                ->orLike('students.nisn', $keyword)
                ->orLike('students.nis', $keyword)
                ->orLike('classes.class_name', $keyword)
            ->groupEnd()
            ->where('students.deleted_at', null)
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
    }

    /**
     * Update poin pelanggaran siswa.
     *
     * Revisi penting:
     * - Karena total_violation_points adalah CACHE untuk Tahun Ajaran aktif,
     *   metode ini secara default akan melakukan RESYNC (menghitung ulang) agar nilainya akurat.
     * - Untuk mempertahankan perilaku lama (delta), set $forceResyncActiveYear = false.
     *
     * @param int  $studentId
     * @param int  $points
     * @param bool $isAddition
     * @param bool $forceResyncActiveYear
     * @return bool
     */
    public function updateViolationPoints($studentId, $points, $isAddition = true, $forceResyncActiveYear = true)
    {
        $studentId = (int) $studentId;

        // Default: aman untuk skema per Tahun Ajaran (hitung ulang sesuai TA aktif)
        if ($forceResyncActiveYear) {
            return $this->resyncViolationPointsForActiveYear($studentId);
        }

        // Legacy: delta update (HATI-HATI, bisa stale saat TA berubah)
        $student = $this->find($studentId);
        if (! $student) {
            return false;
        }

        $current = (int) ($student['total_violation_points'] ?? 0);
        $new     = $isAddition ? ($current + (int) $points) : ($current - (int) $points);
        $new     = max(0, $new);

        return (bool) $this->update($studentId, ['total_violation_points' => $new]);
    }

    public function changeStatus($studentId, $status)
    {
        return (bool) $this->update($studentId, ['status' => $status]);
    }

    public function assignParent($studentId, $parentId)
    {
        return (bool) $this->update($studentId, ['parent_id' => $parentId]);
    }

    public function moveToClass($studentId, $newClassId)
    {
        return (bool) $this->update($studentId, ['class_id' => $newClassId]);
    }

    /**
     * Statistik siswa (perbaikan reset builder)
     */
    public function getStatistics()
    {
        // total
        $total = $this->where('deleted_at', null)->countAllResults();

        // aktif
        $active = $this->where('deleted_at', null)
                       ->where('status', 'Aktif')
                       ->countAllResults();

        // alumni
        $alumni = $this->where('deleted_at', null)
                       ->where('status', 'Alumni')
                       ->countAllResults();

        // by gender (pakai query baru)
        $db = \Config\Database::connect();
        $byGender = $db->table($this->table)
            ->select('gender, COUNT(*) as total')
            ->where('deleted_at', null)
            ->where('status', 'Aktif')
            ->groupBy('gender')
            ->get()
            ->getResultArray();

        $byGrade = $db->table($this->table)
            ->select('classes.grade_level, COUNT(students.id) as total')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.status', 'Aktif')
            ->groupBy('classes.grade_level')
            ->get()
            ->getResultArray();

        return [
            'total'     => $total,
            'active'    => $active,
            'alumni'    => $alumni,
            'by_gender' => $byGender,
            'by_grade'  => $byGrade,
        ];
    }

    public function getHighViolationStudents($threshold = 50)
    {
        return $this->select('students.*, users.full_name AS full_name, classes.class_name')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('students.total_violation_points >=', (int) $threshold)
            ->where('students.status', 'Aktif')
            ->orderBy('students.total_violation_points', 'DESC')
            ->findAll();
    }

    /**
     * Query scope untuk Counselor (builder) agar caller bisa lanjutkan filter sendiri.
     * Ditambah JOIN users supaya nama siswa tersedia.
     */
    public function forCounselor(int $counselorId)
    {
        return $this->select('students.*, users.full_name AS full_name, classes.class_name')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('classes.counselor_id', $counselorId);
    }
}
