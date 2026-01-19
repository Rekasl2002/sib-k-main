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
 * Tambahan (Revisi Poin Pelanggaran per Tahun Ajaran):
 * - Sediakan helper untuk resolve Tahun Ajaran aktif (gabung ganjil+genap via academic_years.year_name)
 * - Sediakan helper computeViolationPointsByRange() dan getViolationSummaryFiltered()
 * - getViolationSummary() defaultnya memakai Tahun Ajaran aktif (agar konsisten dengan cache students.total_violation_points)
 *
 * @package    SIB-K
 * @subpackage Services
 * @category   Business Logic
 * @author     Development Team
 * @created    2025-01-05
 * @updated    2026-01-19
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
     * - Jika $photo filename: base_url("uploads/profile_photos/{user_id}/{filename}") + cache buster ?v=filemtime
     */
    private function resolveUserAvatarUrl(?string $photo, ?int $userId = null): string
    {
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

    // ==========================================================
    // Helper: Tahun Ajaran aktif (gabung ganjil+genap via year_name)
    // ==========================================================

    /**
     * Resolve year_name Tahun Ajaran aktif.
     * Prefer is_active=1, fallback ke tanggal hari ini.
     */
    public function getActiveAcademicYearName(): ?string
    {
        $row = $this->db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('updated_at', 'DESC')
            ->get(1)->getRowArray();

        $year = trim((string) ($row['year_name'] ?? ''));
        if ($year !== '') return $year;

        $today = date('Y-m-d');
        $row = $this->db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->orderBy('start_date', 'DESC')
            ->get(1)->getRowArray();

        $year = trim((string) ($row['year_name'] ?? ''));
        return $year !== '' ? $year : null;
    }

    /**
     * Ambil range tanggal Tahun Ajaran untuk year_name tertentu.
     * Jika year_name null, pakai Tahun Ajaran aktif.
     *
     * @return array{year_name:?string,date_from:?string,date_to:?string}
     */
    public function getAcademicYearDateRange(?string $yearName = null): array
    {
        $year = trim((string) ($yearName ?? ''));
        if ($year === '') {
            $year = (string) ($this->getActiveAcademicYearName() ?? '');
        }
        $year = trim($year);

        if ($year === '') {
            return ['year_name' => null, 'date_from' => null, 'date_to' => null];
        }

        $range = $this->db->table('academic_years')
            ->select('MIN(start_date) as date_from, MAX(end_date) as date_to')
            ->where('deleted_at', null)
            ->where('year_name', $year)
            ->get()->getRowArray();

        return [
            'year_name' => $year,
            'date_from' => ($range['date_from'] ?? null) ?: null,
            'date_to'   => ($range['date_to'] ?? null) ?: null,
        ];
    }

    /**
     * Hitung total poin pelanggaran siswa berdasarkan filter.
     * Default: Tahun Ajaran aktif (gabung ganjil+genap), exclude status Dibatalkan.
     *
     * filters:
     * - year_name: string (opsional)
     * - date_from/date_to: string Y-m-d (opsional, override year_name)
     * - include_cancelled: bool (default false)
     */
    public function computeViolationPointsByRange(int $studentId, array $filters = []): int
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) return 0;

        $includeCancelled = (bool) ($filters['include_cancelled'] ?? false);

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo   = trim((string) ($filters['date_to'] ?? ''));

        // Jika tidak ada date range eksplisit, pakai Tahun Ajaran (aktif atau year_name yang dipilih)
        if ($dateFrom === '' || $dateTo === '') {
            $yr = $this->getAcademicYearDateRange($filters['year_name'] ?? null);
            $dateFrom = (string) ($yr['date_from'] ?? '');
            $dateTo   = (string) ($yr['date_to'] ?? '');
        }

        // Kalau tetap tidak ada range, fallback 0 (lebih aman daripada “semua waktu” untuk poin tahunan)
        if ($dateFrom === '' || $dateTo === '') {
            return 0;
        }

        $qb = $this->db->table('violations v')
            ->select('SUM(vc.point_deduction) as total_points')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.deleted_at', null)
            ->where('v.student_id', $studentId)
            ->where('v.violation_date >=', $dateFrom)
            ->where('v.violation_date <=', $dateTo);

        if (!$includeCancelled) {
            $qb->where('v.status !=', 'Dibatalkan');
        }

        $row = $qb->get()->getRowArray();
        $total = (int) ($row['total_points'] ?? 0);
        return max(0, $total);
    }

    /**
     * Resync cache students.total_violation_points untuk 1 siswa
     * (berdasarkan Tahun Ajaran aktif / year_name / date range).
     */
    public function resyncStudentViolationPointsCache(int $studentId, array $filters = []): bool
    {
        $points = $this->computeViolationPointsByRange($studentId, $filters);
        return (bool) $this->studentModel->update((int) $studentId, ['total_violation_points' => $points]);
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
            'students.created_at' => 'students.created_at',
            'students.updated_at' => 'students.updated_at',
            'students.nisn'       => 'students.nisn',
            'students.nis'        => 'students.nis',
            'students.status'     => 'students.status',
            'students.gender'     => 'students.gender',
            'classes.class_name'  => 'classes.class_name',
            'classes.grade_level' => 'classes.grade_level',
            'users.full_name'     => 'users.full_name',
            'full_name'           => 'users.full_name',
            'students.full_name'  => 'users.full_name',
        ];

        $orderByRaw = $filters['order_by'] ?? 'students.created_at';
        $orderBy    = $allowedOrderColumns[$orderByRaw] ?? 'students.created_at';

        $orderDirRaw = strtoupper((string) ($filters['order_dir'] ?? 'DESC'));
        $orderDir    = in_array($orderDirRaw, ['ASC', 'DESC'], true) ? $orderDirRaw : 'DESC';

        $builder->orderBy($orderBy, $orderDir);

        // ✅ Pagination Mode Switch
        $perPageInt = (int) $perPage;
        $pager = null;

        if ($perPageInt > 0) {
            $students = $builder->paginate($perPageInt, $pagerGroup);
            $pager    = $this->studentModel->pager;
        } else {
            $students = $builder->findAll();
            $pager    = null;
        }

        // Normalisasi avatar supaya tidak fallback ke session profile_photo
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
        try {
            // Validasi sebelum transaksi
            $user = $this->userModel->asArray()->where('deleted_at', null)->find($data['user_id'] ?? 0);
            if (!$user) {
                return ['success'=>false,'message'=>'User tidak ditemukan','student_id'=>null];
            }

            $existingStudent = $this->studentModel->asArray()
                ->where('deleted_at', null)
                ->where('user_id', $data['user_id'])
                ->first();

            if ($existingStudent) {
                return ['success'=>false,'message'=>'User sudah terdaftar sebagai siswa','student_id'=>null];
            }

            $this->db->transStart();

            // Nama siswa harusnya disimpan di users.full_name
            $fullName = trim((string) ($data['full_name'] ?? ''));
            if ($fullName === '') {
                $fullName = (string) ($user['full_name'] ?? '');
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
                if (!$this->userModel->update((int) $user['id'], ['full_name' => $fullName])) {
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
                    'message'    => 'Gagal menyimpan data siswa: ' . implode(', ', $this->studentModel->errors() ?: []),
                    'student_id' => null,
                ];
            }

            $studentId = (int) $this->studentModel->getInsertID();

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                return ['success'=>false,'message'=>'Terjadi kesalahan saat menyimpan data','student_id'=>null];
            }

            $this->logActivity(
                'create_student',
                $studentId,
                "Siswa baru dibuat: {$studentData['nisn']} - " . ($fullName ?: ($user['full_name'] ?? '-'))
            );

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
        try {
            // Validasi sebelum transaksi
            $studentRole = $this->roleModel->asArray()->where('role_name', 'Siswa')->first();
            if (!$studentRole) {
                return ['success'=>false,'message'=>'Role Siswa tidak ditemukan','student_id'=>null,'user_id'=>null];
            }

            $fullName = trim((string) ($data['full_name'] ?? ''));

            $this->db->transStart();

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
                return [
                    'success'=>false,
                    'message'=>'Gagal membuat akun user: ' . implode(', ', $this->userModel->errors() ?: []),
                    'student_id'=>null,
                    'user_id'=>null
                ];
            }

            $userId = (int) $this->userModel->getInsertID();

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
                return [
                    'success'=>false,
                    'message'=>'Gagal menyimpan data siswa: ' . implode(', ', $this->studentModel->errors() ?: []),
                    'student_id'=>null,
                    'user_id'=>null
                ];
            }

            $studentId = (int) $this->studentModel->getInsertID();

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
        try {
            // Validasi sebelum transaksi
            $student = $this->studentModel->asArray()->where('deleted_at', null)->find($studentId);
            if (!$student) {
                return ['success'=>false,'message'=>'Data siswa tidak ditemukan'];
            }

            $this->db->transStart();

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
                return ['success'=>false,'message'=>'Gagal mengupdate data siswa: ' . implode(', ', $this->studentModel->errors() ?: [])];
            }

            // Nama siswa disimpan di users.full_name
            $fullName = trim((string) ($data['full_name'] ?? ''));

            if (!empty($student['user_id'])) {
                $userId = (int) $student['user_id'];

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

            $this->logActivity('update_student', (int) $studentId, "Data siswa diupdate: " . ($data['nisn'] ?? '-'));

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

            $this->logActivity('delete_student', (int) $studentId, "Siswa dihapus: {$student['nisn']} - " . ($student['full_name'] ?? '-'));
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

            $this->logActivity('change_class', (int) $studentId, "Siswa dipindahkan ke kelas: {$class['class_name']}");
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
     */
    private function logActivity($action, $studentId, $description)
    {
        log_message('info', "[StudentService] Action: {$action}, Student ID: {$studentId}, Description: {$description}");
    }

    /**
     * Cari user ber-role Siswa yang belum punya record di tabel students
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
    // STUDENT PORTAL: helper untuk akun Siswa
    // ---------------------------------------------------------------------

    /**
     * Ambil profil siswa berdasarkan user_id (untuk portal Siswa)
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
     * Ringkasan pelanggaran siswa (default: Tahun Ajaran AKTIF)
     *
     * Return: {total:int, latest:array|null}
     */
    public function getViolationSummary(int $studentId): array
    {
        // Default: TA aktif, exclude cancelled
        return $this->getViolationSummaryFiltered($studentId, [
            'use_active_year'     => true,
            'include_cancelled'   => false,
        ]);
    }

    /**
     * Ringkasan pelanggaran siswa dengan filter (untuk kebutuhan lintas role).
     *
     * filters:
     * - use_active_year: bool (default true)
     * - year_name: string (opsional)
     * - date_from/date_to: string Y-m-d (opsional, override year_name)
     * - include_cancelled: bool (default false)
     *
     * @return array{total:int,latest:array|null,total_points:int,range:array}
     */
    public function getViolationSummaryFiltered(int $studentId, array $filters = []): array
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            return ['total' => 0, 'latest' => null, 'total_points' => 0, 'range' => ['date_from'=>null,'date_to'=>null,'year_name'=>null]];
        }

        // Pastikan tabel ada (CI4 BaseConnection punya tableExists)
        if (!method_exists($this->db, 'tableExists') || !$this->db->tableExists('violations')) {
            return ['total' => 0, 'latest' => null, 'total_points' => 0, 'range' => ['date_from'=>null,'date_to'=>null,'year_name'=>null]];
        }

        $includeCancelled = (bool) ($filters['include_cancelled'] ?? false);

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo   = trim((string) ($filters['date_to'] ?? ''));

        $range = ['year_name' => null, 'date_from' => null, 'date_to' => null];

        $useActiveYear = (bool) ($filters['use_active_year'] ?? true);

        if ($dateFrom === '' || $dateTo === '') {
            if ($useActiveYear || !empty($filters['year_name'])) {
                $range = $this->getAcademicYearDateRange($filters['year_name'] ?? null);
                $dateFrom = (string) ($range['date_from'] ?? '');
                $dateTo   = (string) ($range['date_to'] ?? '');
            }
        } else {
            $range = ['year_name' => (string) ($filters['year_name'] ?? null), 'date_from' => $dateFrom, 'date_to' => $dateTo];
        }

        $totalQb = $this->db->table('violations v')
            ->where('v.student_id', $studentId)
            ->where('v.deleted_at', null);

        $latestQb = $this->db->table('violations v')
            ->select('v.*')
            ->where('v.student_id', $studentId)
            ->where('v.deleted_at', null);

        if (!$includeCancelled) {
            $totalQb->where('v.status !=', 'Dibatalkan');
            $latestQb->where('v.status !=', 'Dibatalkan');
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            $totalQb->where('v.violation_date >=', $dateFrom)->where('v.violation_date <=', $dateTo);
            $latestQb->where('v.violation_date >=', $dateFrom)->where('v.violation_date <=', $dateTo);
        }

        $total = (int) $totalQb->countAllResults();

        $latest = $latestQb
            ->orderBy('v.violation_date', 'desc')
            ->orderBy('v.created_at', 'desc')
            ->get(1)->getRowArray();

        $totalPoints = $this->computeViolationPointsByRange($studentId, [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'year_name' => $range['year_name'] ?? null,
            'include_cancelled' => $includeCancelled,
        ]);

        return [
            'total'        => $total,
            'latest'       => $latest ?: null,
            'total_points' => (int) $totalPoints,
            'range'        => [
                'year_name' => $range['year_name'] ?? null,
                'date_from' => $dateFrom !== '' ? $dateFrom : null,
                'date_to'   => $dateTo !== '' ? $dateTo : null,
            ],
        ];
    }

    /**
     * Sorotan info karier (opsional)
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
