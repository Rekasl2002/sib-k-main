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
        'nik',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'special_needs',
        'disability',
        'kip_pip_number',
        'father_name',
        'mother_name',
        'guardian_name',
        'parent_id',
        'admission_date',
        'status',
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
        'nik'            => 'permit_empty|numeric|exact_length[16]|is_unique[students.nik,id,{id}]',
        'gender'         => 'permit_empty|in_list[L,P]',
        'birth_place'    => 'permit_empty|max_length[100]',
        'birth_date'     => 'permit_empty|valid_date[Y-m-d]',
        'religion'       => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
        'address'        => 'permit_empty|max_length[255]',
        'special_needs'  => 'permit_empty|max_length[100]',
        'disability'     => 'permit_empty|max_length[100]',
        'kip_pip_number' => 'permit_empty|max_length[50]',
        'father_name'    => 'permit_empty|max_length[255]',
        'mother_name'    => 'permit_empty|max_length[255]',
        'guardian_name'  => 'permit_empty|max_length[255]',
        'parent_id'      => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
        'admission_date' => 'permit_empty|valid_date[Y-m-d]',
        'status'         => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar,Tidak Aktif]',
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
        'nik' => [
            'is_unique' => 'NIK sudah terdaftar',
            'numeric'   => 'NIK harus berupa angka',
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
                ->orLike('classes.class_name', $keyword)
            ->groupEnd()
            ->where('students.deleted_at', null)
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
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
        return [];
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
