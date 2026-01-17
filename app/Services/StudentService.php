<?php

/**
 * File Path: app/Services/StudentService.php
 *
 * Student Service
 * Business logic layer untuk Student management & Student Portal
 *
 * Catatan (Normalisasi Nama):
 * - Kolom students.full_name sudah DIHAPUS.
 * - Nama siswa sekarang single source of truth: users.full_name.
 * - Service ini memastikan query mengembalikan key 'full_name' dari users.full_name,
 *   dan tidak pernah insert/update students.full_name lagi.
 *
 * Catatan (Avatar Listing Admin):
 * - Bug umum: pada halaman list, user_avatar(null) sering fallback ke session profile_photo,
 *   akibatnya semua baris avatar jadi foto user login.
 * - Solusi di service: pastikan getAllStudents() mengambil users.profile_photo dan
 *   menormalkan menjadi URL final berbasis user_id (uploads/profile_photos/{user_id}/...).
 *
 * Tambahan (2026-01-07):
 * - Mode "pagination hanya di VIEW" (DataTables):
 *   Jika $perPage <= 0 => ambil semua data terfilter via findAll(), tidak pakai paginate().
 *   Return 'pager' => null.
 *
 * @package    SIB-K
 * @subpackage Services
 * @category   Business Logic
 * @author     Development Team
 * @created    2025-01-05
 * @updated    2026-01-07
 */

namespace App\Services;

use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Models\RoleModel;
use CodeIgniter\Database\BaseConnection;

class StudentService
{
    protected StudentModel $studentModel;
    protected UserModel $userModel;
    protected ClassModel $classModel;
    protected AcademicYearModel $academicYearModel;
    protected RoleModel $roleModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->studentModel       = new StudentModel();
        $this->userModel          = new UserModel();
        $this->classModel         = new ClassModel();
        $this->academicYearModel  = new AcademicYearModel();
        $this->roleModel          = new RoleModel();
        $this->db                 = \Config\Database::connect();
    }

    private function asArray($row): array
    {
        if (is_array($row)) return $row;
        if (is_object($row)) {
            if (method_exists($row, 'toArray')) return $row->toArray();
            $arr = json_decode(json_encode($row), true);
            return is_array($arr) ? $arr : [];
        }
        return [];
    }

    /**
     * Resolve avatar URL untuk user (tanpa bergantung session).
     * - Jika $photo kosong: default avatar assets/images/users/default-avatar.svg
     * - Jika $photo sudah URL: return apa adanya
     * - Jika $photo sudah path (mengandung "/"): base_url(path)
     * - Jika $photo filename: base_url("uploads/profile_photos/{userId}/{filename}") + cache buster ?v=filemtime
     */
    private function resolveUserAvatarUrl(?string $photo, ?int $userId = null): string
    {
        // Pastikan base_url tersedia
        helper('url');

        $defaultRel = 'assets/images/users/default-avatar.svg';
        $defaultUrl = base_url($defaultRel);

        $raw = trim((string) ($photo ?? ''));
        if ($raw === '') {
            return $defaultUrl;
        }

        // buang query string (?v=...)
        $rawNoQ = (string) strtok($raw, '?');
        $rawNoQ = trim($rawNoQ);
        if ($rawNoQ === '') {
            return $defaultUrl;
        }

        // sudah URL penuh
        if (preg_match('~^https?://~i', $rawNoQ)) {
            return $rawNoQ;
        }

        // sudah path relatif (mis. uploads/... atau assets/...)
        if (strpos($rawNoQ, '/') !== false || strpos($rawNoQ, '\\') !== false) {
            $rel = ltrim(str_replace('\\', '/', $rawNoQ), '/');
            return base_url($rel);
        }

        // filename saja -> bangun path berdasarkan userId
        $uid = (int) ($userId ?? 0);
        if ($uid > 0) {
            $rel = "uploads/profile_photos/{$uid}/{$rawNoQ}";
            $abs = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

            $v = is_file($abs) ? (string) @filemtime($abs) : (string) time();
            return base_url($rel) . '?v=' . rawurlencode($v);
        }

        // fallback kalau userId tidak tersedia
        $rel = "uploads/profile_photos/{$rawNoQ}";
        return base_url($rel) . '?v=' . rawurlencode((string) time());
    }

    // ---------------------------------------------------------------------
    // ADMIN/UMUM: Listing & CRUD
    // ---------------------------------------------------------------------

    /**
     * Get all students with filter and pagination
     *
     * Mode:
     * - $perPage > 0 : server-side pagination (paginate)
     * - $perPage <= 0: ambil semua data (untuk pagination DataTables di VIEW)
     *
     * @param array $filters
     * @param int   $perPage
     * @param string $pagerGroup
     * @return array
     */
    public function getAllStudents($filters = [], $perPage = 10, string $pagerGroup = 'default')
    {
        $builder = $this->studentModel
            ->asArray()
            ->select('students.*,
                      users.full_name AS full_name, users.email, users.username, users.phone, users.is_active,
                      users.profile_photo AS profile_photo,
                      classes.class_name, classes.grade_level')
            ->join('users', 'users.id = students.user_id AND users.deleted_at IS NULL')
            ->join('classes', 'classes.id = students.class_id AND classes.deleted_at IS NULL', 'left')
            ->where('students.deleted_at', null);

        // Filters
        if (!empty($filters['class_id'])) {
            $builder->where('students.class_id', $filters['class_id']);
        }
        if (!empty($filters['grade_level'])) {
            $builder->where('classes.grade_level', $filters['grade_level']);
        }
        if (!empty($filters['status'])) {
            $builder->where('students.status', $filters['status']);
        }
        if (!empty($filters['gender'])) {
            $builder->where('students.gender', $filters['gender']);
        }
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('users.full_name', $filters['search'])
                ->orLike('students.nisn', $filters['search'])
                ->orLike('students.nis', $filters['search'])
                ->orLike('users.email', $filters['search'])
            ->groupEnd();
        }

        /**
         * ORDER BY guard:
         * - Mencegah order_by mengarah ke kolom yang sudah dihapus (students.full_name),
         *   dan mencegah injection via order_by.
         */
        $allowedOrderColumns = [
            // students
            'students.created_at' => 'students.created_at',
            'students.updated_at' => 'students.updated_at',
            'students.nisn'       => 'students.nisn',
            'students.nis'        => 'students.nis',
            'students.status'     => 'students.status',
            'students.gender'     => 'students.gender',

            // classes
            'classes.class_name'  => 'classes.class_name',
            'classes.grade_level' => 'classes.grade_level',

            // users (nama siswa)
            'users.full_name'     => 'users.full_name',
            'full_name'           => 'users.full_name',     // kompatibilitas dari UI
            'students.full_name'  => 'users.full_name',     // kompatibilitas lama (kolom sudah dihapus)
        ];

        $orderByRaw = $filters['order_by'] ?? 'students.created_at';
        $orderBy    = $allowedOrderColumns[$orderByRaw] ?? 'students.created_at';

        $orderDirRaw = strtoupper((string)($filters['order_dir'] ?? 'DESC'));
        $orderDir    = in_array($orderDirRaw, ['ASC', 'DESC'], true) ? $orderDirRaw : 'DESC';

        $builder->orderBy($orderBy, $orderDir);

        // ✅ Pagination Mode Switch
        $perPageInt = (int) $perPage;
        $pager = null;

        if ($perPageInt > 0) {
            $students = $builder->paginate($perPageInt, $pagerGroup);
            $pager    = $this->studentModel->pager;
        } else {
            // Pagination hanya di VIEW (DataTables) => ambil semua data terfilter
            $students = $builder->findAll();
            $pager    = null;
        }

        /**
         * Normalisasi avatar supaya TIDAK fallback ke session profile_photo.
         * - profile_photo_raw: nilai asli dari DB (filename/null)
         * - profile_photo: URL final (atau default avatar)
         */
        if (is_array($students)) {
            foreach ($students as $i => $row) {
                $r = $this->asArray($row);

                $userId = isset($r['user_id']) ? (int) $r['user_id'] : null;

                $r['profile_photo_raw'] = $r['profile_photo'] ?? null;
                $r['profile_photo']     = $this->resolveUserAvatarUrl($r['profile_photo_raw'] ?? null, $userId);

                $students[$i] = $r;
            }
        }

        return [
            'students' => $students,
            'pager'    => $pager,
        ];
    }

    /**
     * Get student by ID with full details
     *
     * @param int $studentId
     * @return array|null
     */
    public function getStudentById($studentId)
    {
        $student = $this->studentModel
            ->asArray()
            ->select('students.*,
                      users.full_name AS full_name, users.email, users.username, users.phone, users.profile_photo,
                      users.is_active, users.last_login, users.created_at as user_created_at,
                      classes.class_name, classes.grade_level')
            ->join('users', 'users.id = students.user_id AND users.deleted_at IS NULL')
            ->join('classes', 'classes.id = students.class_id AND classes.deleted_at IS NULL', 'left')
            ->where('students.id', $studentId)
            ->where('students.deleted_at', null)
            ->first();

        if (!$student) {
            return null;
        }

        // Parent info (jika ada)
        if (!empty($student['parent_id'])) {
            $parent = $this->userModel
                ->asArray()
                ->where('deleted_at', null)
                ->find($student['parent_id']);
            $student['parent_name']  = $parent['full_name'] ?? null;
            $student['parent_phone'] = $parent['phone'] ?? null;
        }

        return $student;
    }

    /**
     * Create new student with existing user account
     *
     * @param array $data
     * @return array ['success'=>bool,'message'=>string,'student_id'=>int|null]
     */
    public function createStudent($data)
    {
        $this->db->transStart();

        try {
            $user = $this->userModel->asArray()->where('deleted_at', null)->find($data['user_id'] ?? 0);
            if (!$user) {
                return ['success'=>false,'message'=>'User tidak ditemukan','student_id'=>null];
            }

            $existingStudent = $this->studentModel->asArray()->where('deleted_at', null)->where('user_id', $data['user_id'])->first();
            if ($existingStudent) {
                return ['success'=>false,'message'=>'User sudah terdaftar sebagai siswa','student_id'=>null];
            }

            // Nama siswa harusnya disimpan di users.full_name
            $fullName = trim((string)($data['full_name'] ?? ''));
            if ($fullName === '') {
                $fullName = (string)($user['full_name'] ?? '');
            }

            $studentData = [
                'user_id'                => $data['user_id'],
                'class_id'               => $data['class_id'] ?? null,
                'nisn'                   => $data['nisn'] ?? null,
                'nis'                    => $data['nis'] ?? null,
                'gender'                 => $data['gender'] ?? null,
                'birth_place'            => $data['birth_place'] ?? null,
                'birth_date'             => $data['birth_date'] ?? null,
                'religion'               => $data['religion'] ?? null,
                'address'                => $data['address'] ?? null,
                'parent_id'              => $data['parent_id'] ?? null,
                'admission_date'         => $data['admission_date'] ?? date('Y-m-d'),
                'status'                 => $data['status'] ?? 'Aktif',
                'total_violation_points' => 0,
            ];

            // Sinkron nama lengkap user (jika diberikan/berubah)
            if (!empty($user['id']) && $fullName !== '' && $fullName !== ($user['full_name'] ?? null)) {
                if (!$this->userModel->update((int)$user['id'], ['full_name' => $fullName])) {
                    $this->db->transRollback();
                    return [
                        'success'    => false,
                        'message'    => 'Gagal mengupdate nama lengkap user: ' . implode(', ', $this->userModel->errors() ?: []),
                        'student_id' => null,
                    ];
                }
            }

            if (!$this->studentModel->insert($studentData)) {
                $this->db->transRollback();
                return [
                    'success'    => false,
                    'message'    => 'Gagal menyimpan data siswa: ' . implode(', ', $this->studentModel->errors()),
                    'student_id' => null,
                ];
            }

            $studentId = $this->studentModel->getInsertID();
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return ['success'=>false,'message'=>'Terjadi kesalahan saat menyimpan data','student_id'=>null];
            }

            $this->logActivity('create_student', $studentId, "Siswa baru dibuat: {$studentData['nisn']} - " . ($fullName ?: ($user['full_name'] ?? '-')));

            return ['success'=>true,'message'=>'Data siswa berhasil ditambahkan','student_id'=>$studentId];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error creating student: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Terjadi kesalahan sistem: ' . $e->getMessage(),'student_id'=>null];
        }
    }

    /**
     * Create new student with new user account
     *
     * @param array $data
     * @return array ['success'=>bool,'message'=>string,'student_id'=>int|null,'user_id'=>int|null]
     */
    public function createStudentWithUser($data)
    {
        $this->db->transStart();

        try {
            $studentRole = $this->roleModel->asArray()->where('role_name', 'Siswa')->first();
            if (!$studentRole) {
                return ['success'=>false,'message'=>'Role Siswa tidak ditemukan','student_id'=>null,'user_id'=>null];
            }

            $fullName = trim((string)($data['full_name'] ?? ''));

            $userData = [
                'role_id'   => $studentRole['id'],
                'username'  => $data['username'],
                'email'     => $data['email'],
                'password'  => $data['password'], // hashing di UserModel
                'full_name' => $fullName,
                'phone'     => $data['phone'] ?? null,
                'is_active' => 1,
            ];

            if (!$this->userModel->insert($userData)) {
                $this->db->transRollback();
                return ['success'=>false,'message'=>'Gagal membuat akun user: ' . implode(', ', $this->userModel->errors()),'student_id'=>null,'user_id'=>null];
            }

            $userId = $this->userModel->getInsertID();

            // students.full_name sudah tidak ada, jadi jangan disimpan ke students
            $studentData = [
                'user_id'                => $userId,
                'class_id'               => $data['class_id'] ?? null,
                'nisn'                   => $data['nisn'] ?? null,
                'nis'                    => $data['nis'] ?? null,
                'gender'                 => $data['gender'] ?? null,
                'birth_place'            => $data['birth_place'] ?? null,
                'birth_date'             => $data['birth_date'] ?? null,
                'religion'               => $data['religion'] ?? null,
                'address'                => $data['address'] ?? null,
                'parent_id'              => $data['parent_id'] ?? null,
                'admission_date'         => $data['admission_date'] ?? date('Y-m-d'),
                'status'                 => $data['status'] ?? 'Aktif',
                'total_violation_points' => 0,
            ];

            if (!$this->studentModel->insert($studentData)) {
                $this->db->transRollback();
                return ['success'=>false,'message'=>'Gagal menyimpan data siswa: ' . implode(', ', $this->studentModel->errors()),'student_id'=>null,'user_id'=>null];
            }

            $studentId = $this->studentModel->getInsertID();
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return ['success'=>false,'message'=>'Terjadi kesalahan saat menyimpan data','student_id'=>null,'user_id'=>null];
            }

            $this->logActivity('create_student_with_user', $studentId, "Siswa dan user baru dibuat: {$studentData['nisn']} - {$fullName}");

            return ['success'=>true,'message'=>'Siswa dan akun user berhasil dibuat','student_id'=>$studentId,'user_id'=>$userId];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error creating student with user: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Terjadi kesalahan sistem: ' . $e->getMessage(),'student_id'=>null,'user_id'=>null];
        }
    }

    /**
     * Update student data
     *
     * @param int   $studentId
     * @param array $data
     * @return array
     */
    public function updateStudent($studentId, $data)
    {
        $this->db->transStart();

        try {
            $student = $this->studentModel->asArray()->where('deleted_at', null)->find($studentId);
            if (!$student) {
                return ['success'=>false,'message'=>'Data siswa tidak ditemukan'];
            }

            // students.full_name sudah tidak ada, jadi jangan di-update di students.
            $updateData = [
                'class_id'       => $data['class_id'] ?? null,
                'nisn'           => $data['nisn'] ?? null,
                'nis'            => $data['nis'] ?? null,
                'gender'         => $data['gender'] ?? null,
                'birth_place'    => $data['birth_place'] ?? null,
                'birth_date'     => $data['birth_date'] ?? null,
                'religion'       => $data['religion'] ?? null,
                'address'        => $data['address'] ?? null,
                'parent_id'      => $data['parent_id'] ?? null,
                'admission_date' => $data['admission_date'] ?? null,
                'status'         => $data['status'] ?? 'Aktif',
            ];

            if (!$this->studentModel->update($studentId, $updateData)) {
                $this->db->transRollback();
                return ['success'=>false,'message'=>'Gagal mengupdate data siswa: ' . implode(', ', $this->studentModel->errors())];
            }

            // Nama siswa disimpan di users.full_name
            $fullName = trim((string)($data['full_name'] ?? ''));

            if (!empty($student['user_id'])) {
                $userId = (int)$student['user_id'];

                // Jika full_name kosong, jangan overwrite jadi kosong
                if ($fullName !== '') {
                    if (!$this->userModel->update($userId, ['full_name' => $fullName])) {
                        $this->db->transRollback();
                        return ['success'=>false,'message'=>'Gagal mengupdate nama lengkap user: ' . implode(', ', $this->userModel->errors() ?: [])];
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return ['success'=>false,'message'=>'Terjadi kesalahan saat mengupdate data'];
            }

            $this->logActivity('update_student', $studentId, "Data siswa diupdate: " . ($data['nisn'] ?? '-'));

            return ['success'=>true,'message'=>'Data siswa berhasil diupdate'];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error updating student: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    /**
     * Delete student (soft delete)
     *
     * @param int $studentId
     * @return array
     */
    public function deleteStudent($studentId)
    {
        try {
            $student = $this->getStudentById($studentId);
            if (!$student) {
                return ['success'=>false,'message'=>'Data siswa tidak ditemukan'];
            }

            if (!$this->studentModel->delete($studentId)) {
                return ['success'=>false,'message'=>'Gagal menghapus data siswa'];
            }

            $this->logActivity('delete_student', $studentId, "Siswa dihapus: {$student['nisn']} - " . ($student['full_name'] ?? '-'));
            return ['success'=>true,'message'=>'Data siswa berhasil dihapus'];
        } catch (\Exception $e) {
            log_message('error', 'Error deleting student: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    /**
     * Change student class
     *
     * @param int $studentId
     * @param int $newClassId
     * @return array
     */
    public function changeClass($studentId, $newClassId)
    {
        try {
            $student = $this->studentModel->asArray()->where('deleted_at', null)->find($studentId);
            if (!$student) {
                return ['success'=>false,'message'=>'Data siswa tidak ditemukan'];
            }

            // ClassModel::find() menghormati soft delete
            $class = $this->classModel->asArray()->find($newClassId);
            if (!$class) {
                return ['success'=>false,'message'=>'Kelas tidak ditemukan'];
            }

            if (!$this->studentModel->update($studentId, ['class_id' => $newClassId])) {
                return ['success'=>false,'message'=>'Gagal memindahkan siswa ke kelas baru'];
            }

            $this->logActivity('change_class', $studentId, "Siswa dipindahkan ke kelas: {$class['class_name']}");
            return ['success'=>true,'message'=>"Siswa berhasil dipindahkan ke kelas {$class['class_name']}"];
        } catch (\Exception $e) {
            log_message('error', 'Error changing class: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    /**
     * Get student statistics (aman dari builder carry-over)
     *
     * @return array
     */
    public function getStudentStatistics()
    {
        $total  = $this->db->table('students')->where('deleted_at', null)->countAllResults();
        $active = $this->db->table('students')->where('deleted_at', null)->where('status', 'Aktif')->countAllResults();
        $alumni = $this->db->table('students')->where('deleted_at', null)->where('status', 'Alumni')->countAllResults();
        $moved  = $this->db->table('students')->where('deleted_at', null)->where('status', 'Pindah')->countAllResults();
        $drop   = $this->db->table('students')->where('deleted_at', null)->where('status', 'Keluar')->countAllResults();

        $stats = [
            'total'       => (int) $total,
            'active'      => (int) $active,
            'alumni'      => (int) $alumni,
            'moved'       => (int) $moved,
            'dropped'     => (int) $drop,
            'by_gender'   => [],
            'by_grade'    => [],
            'by_religion' => [],
        ];

        // by gender
        $genderStats = $this->db->table('students')
            ->select('gender, COUNT(id) as total')
            ->where('deleted_at', null)
            ->groupBy('gender')
            ->get()->getResultArray();
        foreach ($genderStats as $g) {
            $stats['by_gender'][$g['gender'] ?? ''] = (int) $g['total'];
        }

        // by grade (kelas aktif)
        $gradeStats = $this->db->table('students')
            ->select('classes.grade_level, COUNT(students.id) as total')
            ->join('classes', 'classes.id = students.class_id AND classes.deleted_at IS NULL', 'left')
            ->where('students.deleted_at', null)
            ->where('students.status', 'Aktif')
            ->groupBy('classes.grade_level')
            ->get()->getResultArray();
        foreach ($gradeStats as $row) {
            $stats['by_grade'][$row['grade_level'] ?? 'Belum Ada Kelas'] = (int) $row['total'];
        }

        // by religion
        $religionStats = $this->db->table('students')
            ->select('religion, COUNT(id) as total')
            ->where('deleted_at', null)
            ->where('religion IS NOT NULL', null, false)
            ->groupBy('religion')
            ->get()->getResultArray();
        foreach ($religionStats as $r) {
            $stats['by_religion'][$r['religion']] = (int) $r['total'];
        }

        return $stats;
    }

    /**
     * Get available classes for dropdown
     *
     * @param int|null $academicYearId
     * @return array
     */
    public function getAvailableClasses($academicYearId = null)
    {
        $builder = $this->classModel->select('classes.*, academic_years.year_name AS year_label')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id AND academic_years.deleted_at IS NULL')
            ->where('classes.is_active', 1)
            ->where('classes.deleted_at', null);

        if ($academicYearId) {
            $builder->where('classes.academic_year_id', $academicYearId);
        }

        return $builder->orderBy('classes.grade_level', 'ASC')
            ->orderBy('classes.class_name', 'ASC')
            ->findAll();
    }

    /**
     * Get available parent users for dropdown
     *
     * @return array
     */
    public function getAvailableParents()
    {
        $parentRole = $this->roleModel->asArray()->where('role_name', 'Orang Tua')->first();
        if (!$parentRole) return [];

        return $this->userModel
            ->asArray()
            ->where('role_id', $parentRole['id'])
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    /**
     * Log student activity
     *
     * @param string $action
     * @param int    $studentId
     * @param string $description
     * @return void
     */
    private function logActivity($action, $studentId, $description)
    {
        log_message('info', "[StudentService] Action: {$action}, Student ID: {$studentId}, Description: {$description}");
    }

    /**
     * Cari user ber-role Siswa yang belum punya record di tabel students
     *
     * @param string|null $q
     * @return array
     */
    public function getAvailableStudentUsers(?string $q = null): array
    {
        $role = $this->roleModel->select('id')->where('role_name', 'Siswa')->first();
        $role = $this->asArray($role);
        if (!$role) return [];

        $builder = $this->db->table('users u')
            ->select('u.id, u.full_name, u.email, u.username')
            ->join('students s', 's.user_id = u.id AND s.deleted_at IS NULL', 'left')
            ->where('u.role_id', (int) $role['id'])
            ->where('u.is_active', 1)
            ->where('u.deleted_at', null)
            ->where('s.id', null);

        if ($q) {
            $builder->groupStart()
                ->like('u.full_name', $q)
                ->orLike('u.email', $q)
                ->orLike('u.username', $q)
            ->groupEnd();
        }

        return $builder->orderBy('u.full_name', 'ASC')->get()->getResultArray();
    }

    // ---------------------------------------------------------------------
    // STUDENT PORTAL: helper untuk akun Siswa (Dashboard, Jadwal, Asesmen, Karier)
    // ---------------------------------------------------------------------

    /**
     * Ambil profil siswa berdasarkan user_id (untuk portal Siswa)
     * Ditambah join users agar full_name tersedia (karena students.full_name sudah dihapus).
     *
     * @param int $userId
     * @return array|null
     */
    public function getStudentByUserId(int $userId): ?array
    {
        $row = $this->db->table('students s')
            ->select('s.*,
                      u.full_name AS full_name, u.email, u.username, u.phone, u.profile_photo,
                      c.id as class_id, c.class_name, c.grade_level')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.user_id', $userId)
            ->where('s.deleted_at', null)
            ->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * Sesi BK relevan untuk siswa: Individu (miliknya) & Klasikal (kelasnya), mulai hari ini
     *
     * @param int|null $studentId
     * @param int|null $classId
     * @param int      $limit
     * @return array
     */
    public function getUpcomingSessions(?int $studentId, ?int $classId, int $limit = 5): array
    {
        $builder = $this->db->table('counseling_sessions cs')->select('cs.*');

        $builder->groupStart()
            ->groupStart()
                ->where('cs.session_type', 'Individu')
                ->where('cs.student_id', $studentId)
            ->groupEnd()
            ->orGroupStart()
                ->where('cs.session_type', 'Klasikal')
                ->where('cs.class_id', $classId)
            ->groupEnd()
        ->groupEnd();

        $builder->where('cs.deleted_at', null)
            ->where('cs.session_date >=', date('Y-m-d'))
            ->orderBy('cs.session_date', 'asc')
            ->orderBy('cs.session_time', 'asc')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Daftar asesmen tersedia untuk siswa berdasarkan target (All/Grade/Class/Individual) dan periode aktif
     *
     * @param array $student Row dari students & kelas
     * @param int   $limit
     * @return array
     */
    public function getAvailableAssessments(array $student, int $limit = 20): array
    {
        $today   = date('Y-m-d');
        $classId = (int) ($student['class_id'] ?? 0);
        $grade   = $student['grade_level'] ?? null;
        $sid     = (int) ($student['id'] ?? 0);

        $qb = $this->db->table('assessments a')
            ->select('a.*')
            ->join('assessment_results ar_i', "ar_i.assessment_id = a.id AND ar_i.student_id = {$sid} AND ar_i.deleted_at IS NULL", 'left')
            ->where('a.is_active', 1)
            ->where('a.is_published', 1)
            ->where('a.deleted_at', null)
            ->groupStart()
                ->where('a.start_date IS NULL', null, false)
                ->orWhere('a.start_date <=', $today)
            ->groupEnd()
            ->groupStart()
                ->where('a.end_date IS NULL', null, false)
                ->orWhere('a.end_date >=', $today)
            ->groupEnd()
            ->groupStart()
                ->where('a.target_audience', 'All')
                ->orGroupStart()->where('a.target_audience', 'Grade')->where('a.target_grade', $grade)->groupEnd()
                ->orGroupStart()->where('a.target_audience', 'Class')->where('a.target_class_id', $classId)->groupEnd()
                ->orGroupStart()
                    ->where('a.target_audience', 'Individual')
                    ->where('ar_i.id IS NOT NULL', null, false)
                ->groupEnd()
            ->groupEnd()
            ->groupBy('a.id')
            ->orderBy('a.start_date', 'asc')
            ->limit($limit);

        return $qb->get()->getResultArray();
    }

    /**
     * Hasil asesmen milik siswa
     *
     * @param int $studentId
     * @param int $limit
     * @return array
     */
    public function getAssessmentResults(int $studentId, int $limit = 20): array
    {
        return $this->db->table('assessment_results r')
            ->select('r.*, a.title, a.assessment_type')
            ->join('assessments a', 'a.id = r.assessment_id AND a.deleted_at IS NULL', 'left')
            ->where('r.student_id', $studentId)
            ->where('r.deleted_at', null)
            ->orderBy('r.created_at', 'desc')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Ringkasan pelanggaran siswa (opsional)
     *
     * @param int $studentId
     * @return array{total:int, latest:array|null}
     */
    public function getViolationSummary(int $studentId): array
    {
        if (!method_exists($this->db, 'tableExists') || !$this->db->tableExists('violations')) {
            return ['total' => 0, 'latest' => null];
        }

        $total = $this->db->table('violations')
            ->where('student_id', $studentId)
            ->where('deleted_at', null)
            ->countAllResults();

        $latest = $this->db->table('violations')
            ->where('student_id', $studentId)
            ->where('deleted_at', null)
            ->orderBy('violation_date', 'desc')
            ->get(1)->getRowArray();

        return ['total' => (int)$total, 'latest' => $latest ?: null];
    }

    /**
     * Sorotan info karier (opsional)
     *
     * @param int $limit
     * @return array
     */
    public function getCareerHighlights(int $limit = 6): array
    {
        if (!method_exists($this->db, 'tableExists') || !$this->db->tableExists('career_options')) {
            return [];
        }

        return $this->db->table('career_options')
            ->select('id, title, sector, min_education, avg_salary_idr')
            ->where('is_active', 1)
            ->where('is_public', 1)
            ->where('deleted_at', null)
            ->orderBy('demand_level', 'desc')
            ->limit($limit)
            ->get()->getResultArray();
    }
}
