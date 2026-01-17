<?php

/**
 * File Path: app/Services/ClassService.php
 *
 * Class Service
 * Business logic layer untuk Class management
 *
 * @package    SIB-K
 * @subpackage Services
 * @category   Business Logic
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Services;

use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Models\UserModel;
use App\Models\StudentModel;
use App\Models\RoleModel;
use App\Validation\ClassValidation;
use CodeIgniter\Database\Exceptions\DatabaseException;

class ClassService
{
    protected $classModel;
    protected $academicYearModel;
    protected $userModel;
    protected $studentModel;
    protected $roleModel;
    protected $db;

    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->academicYearModel = new AcademicYearModel();
        $this->userModel = new UserModel();
        $this->studentModel = new StudentModel();
        $this->roleModel = new RoleModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Paksa hasil model/entity/builder menjadi array biasa.
     */
    private function asArray($row): array
    {
        if (is_array($row)) return $row;

        if (is_object($row)) {
            if (method_exists($row, 'toArray')) {
                return $row->toArray();
            }
            // Fallback aman untuk CI4 entity/stdClass
            $arr = json_decode(json_encode($row), true);
            return is_array($arr) ? $arr : [];
        }

        return [];
    }

    /**
     * Get all classes with filter and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getAllClasses($filters = [], $perPage = 100000)
    {
        $builder = $this->classModel
            ->select('classes.*,
                      academic_years.year_name,
                      academic_years.semester,
                      homeroom.full_name as homeroom_name,
                      counselor.full_name as counselor_name,
                      (SELECT COUNT(*)
                        FROM students
                        WHERE students.class_id = classes.id
                          AND students.status = "Aktif"
                          AND students.deleted_at IS NULL
                      ) as student_count')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id')
            ->join('users as homeroom', 'homeroom.id = classes.homeroom_teacher_id', 'left')
            ->join('users as counselor', 'counselor.id = classes.counselor_id', 'left');

        // Apply filters
        if (!empty($filters['academic_year_id'])) {
            $builder->where('classes.academic_year_id', $filters['academic_year_id']);
        }

        if (!empty($filters['grade_level'])) {
            $builder->where('classes.grade_level', $filters['grade_level']);
        }

        if (!empty($filters['major'])) {
            $builder->where('classes.major', $filters['major']);
        }

        // NOTE: agar nilai 0/false tidak dianggap "empty"
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $builder->where('classes.is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('classes.class_name', $filters['search'])
                ->orLike('homeroom.full_name', $filters['search'])
                ->orLike('counselor.full_name', $filters['search'])
                ->groupEnd();
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'classes.grade_level, classes.class_name';
        $orderDir = $filters['order_dir'] ?? 'ASC';

        $builder->orderBy($orderBy, $orderDir);

        // Get paginated results
        $classes = $builder->paginate($perPage);
        $pager = $this->classModel->pager;

        return [
            'classes'      => $classes,
            'pager'        => $pager,
            'total'        => $pager->getTotal(),
            'per_page'     => $perPage,
            'current_page' => $pager->getCurrentPage(),
            'last_page'    => $pager->getPageCount(),
        ];
    }

    /**
     * Get class by ID with full details
     *
     * @param int $id
     * @return array|null
     */
    public function getClassById($id)
    {
        // FIX UTAMA: "classes.," -> "classes.*" (atau gunakan array select biar lebih aman)
        $class = $this->classModel
            ->select([
                'classes.*',
                'academic_years.year_name',
                'academic_years.semester',
                'academic_years.start_date',
                'academic_years.end_date',
                'homeroom.full_name as homeroom_name',
                'homeroom.email as homeroom_email',
                'homeroom.phone as homeroom_phone',
                'counselor.full_name as counselor_name',
                'counselor.email as counselor_email',
                'counselor.phone as counselor_phone',
            ])
            ->join('academic_years', 'academic_years.id = classes.academic_year_id')
            ->join('users as homeroom', 'homeroom.id = classes.homeroom_teacher_id', 'left')
            ->join('users as counselor', 'counselor.id = classes.counselor_id', 'left')
            ->find((int) $id);

        if (!$class) {
            return null;
        }

        $class = $this->asArray($class);
        /** @var array $class */ // bantu Intelephense memahami tipe

        // Get student count (konsisten: hanya Aktif & tidak soft-deleted)
        $class['student_count'] = $this->studentModel
            ->where('class_id', (int) $id)
            ->where('status', 'Aktif')
            ->where('deleted_at', null)
            ->countAllResults();

        // Get gender distribution
        $genderStats = $this->db->table('students')
            ->select('gender, COUNT(*) as count')
            ->where('class_id', (int) $id)
            ->where('status', 'Aktif')
            ->where('deleted_at', null)
            ->groupBy('gender')
            ->get()
            ->getResultArray();

        $class['gender_stats'] = [
            'L' => 0,
            'P' => 0,
        ];

        foreach ($genderStats as $stat) {
            $g = $stat['gender'] ?? null;
            if ($g === 'L' || $g === 'P') {
                $class['gender_stats'][$g] = (int) $stat['count'];
            }
        }

        return $class;
    }

    /**
     * Create new class
     *
     * @param array $data
     * @return array ['success' => bool, 'message' => string, 'class_id' => int|null]
     */
    public function createClass($data)
    {
        try {
            // Sanitize input
            $data = ClassValidation::sanitizeInput($data);

            // Check if teacher is already homeroom
            if (!empty($data['homeroom_teacher_id'])) {
                if (ClassValidation::isTeacherAlreadyHomeroom($data['homeroom_teacher_id'])) {
                    return [
                        'success' => false,
                        'message' => 'Guru tersebut sudah menjadi wali kelas di kelas lain yang aktif',
                    ];
                }
            }

            // Start transaction
            $this->db->transStart();

            // Insert class
            if (!$this->classModel->insert($data)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal membuat kelas: ' . implode(', ', $this->classModel->errors()),
                ];
            }

            $classId = $this->classModel->getInsertID();

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan data',
                ];
            }

            // Log activity
            $this->logActivity('create', $classId, "Kelas {$data['class_name']} berhasil dibuat");

            return [
                'success' => true,
                'message' => 'Kelas berhasil dibuat',
                'class_id' => $classId,
            ];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error creating class: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update class
     *
     * @param int $id
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateClass($id, $data)
    {
        try {
            // Check if class exists
            $class = $this->classModel->find($id);
            if (!$class) {
                return [
                    'success' => false,
                    'message' => 'Kelas tidak ditemukan',
                ];
            }
            $class = $this->asArray($class);
            /** @var array $class */

            // Sanitize input
            $data = ClassValidation::sanitizeInput($data);

            // Validate capacity if changed
            if (isset($data['max_students']) && $data['max_students'] != $class['max_students']) {
                $capacityCheck = ClassValidation::validateCapacity($id, $data['max_students']);
                if (!$capacityCheck['valid']) {
                    return [
                        'success' => false,
                        'message' => $capacityCheck['message'],
                    ];
                }
            }

            // Check if teacher is already homeroom (exclude current class)
            if (!empty($data['homeroom_teacher_id']) && $data['homeroom_teacher_id'] != $class['homeroom_teacher_id']) {
                if (ClassValidation::isTeacherAlreadyHomeroom($data['homeroom_teacher_id'], $id)) {
                    return [
                        'success' => false,
                        'message' => 'Guru tersebut sudah menjadi wali kelas di kelas lain yang aktif',
                    ];
                }
            }

            // Start transaction
            $this->db->transStart();

            // Update class
            if (!$this->classModel->update($id, $data)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal mengupdate kelas: ' . implode(', ', $this->classModel->errors()),
                ];
            }

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan data',
                ];
            }

            // Log activity
            $this->logActivity('update', $id, "Kelas {$data['class_name']} berhasil diupdate");

            return [
                'success' => true,
                'message' => 'Kelas berhasil diupdate',
            ];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error updating class: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete class
     *
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteClass($id)
    {
        try {
            // Check if class exists
            $class = $this->classModel->find($id);
            if (!$class) {
                return [
                    'success' => false,
                    'message' => 'Kelas tidak ditemukan',
                ];
            }
            $class = $this->asArray($class);
            /** @var array $class */

            // Check if class has students
            $studentCount = $this->studentModel
                ->where('class_id', $id)
                ->countAllResults();

            if ($studentCount > 0) {
                return [
                    'success' => false,
                    'message' => "Tidak dapat menghapus kelas yang memiliki {$studentCount} siswa. Pindahkan siswa terlebih dahulu.",
                ];
            }

            // Start transaction
            $this->db->transStart();

            // Soft delete class
            if (!$this->classModel->delete($id)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal menghapus kelas',
                ];
            }

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus data',
                ];
            }

            // Log activity
            $this->logActivity('delete', $id, "Kelas {$class['class_name']} berhasil dihapus");

            return [
                'success' => true,
                'message' => 'Kelas berhasil dihapus',
            ];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error deleting class: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available teachers for homeroom (Wali Kelas)
     *
     * @return array
     */
    public function getAvailableTeachers()
    {
        // Get Wali Kelas role
        $waliKelasRole = $this->roleModel->where('role_name', 'Wali Kelas')->first();

        if (!$waliKelasRole) {
            return [];
        }

        $waliKelasRole = $this->asArray($waliKelasRole);

        return $this->userModel
            ->where('role_id', $waliKelasRole['id'])
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    /**
     * Get available counselors (Guru BK)
     *
     * @return array
     */
    public function getAvailableCounselors()
    {
        // Get Guru BK and Koordinator BK roles
        $counselorRoles = $this->roleModel
            ->whereIn('role_name', ['Guru BK', 'Koordinator BK'])
            ->findAll();

        if (empty($counselorRoles)) {
            return [];
        }

        // Pastikan array_column aman
        $rows = [];
        foreach ($counselorRoles as $r) {
            $rows[] = $this->asArray($r);
        }

        $roleIds = array_column($rows, 'id');

        if (empty($roleIds)) {
            return [];
        }

        return $this->userModel
            ->whereIn('role_id', $roleIds)
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    /**
     * Get students in a class
     *
     * @param int $classId
     * @param string $status Filter by status (default: Aktif)
     * @return array
     */
    public function getClassStudents($classId, $status = 'Aktif')
    {
        $builder = $this->studentModel
            ->select('students.*, users.full_name, users.email, users.phone')
            ->join('users', 'users.id = students.user_id')
            ->where('students.class_id', $classId);

        if ($status) {
            $builder->where('students.status', $status);
        }

        return $builder->orderBy('users.full_name', 'ASC')->findAll();
    }

    /**
     * Get class statistics
     *
     * @return array
     */
    public function getClassStatistics()
    {
        // Hindari countAllResults(false) karena bisa "nempel" kondisi query sebelumnya.
        $stats = [
            'total'    => $this->classModel->where('deleted_at', null)->countAllResults(),
            'active'   => $this->classModel->where('is_active', 1)->where('deleted_at', null)->countAllResults(),
            'by_grade' => [],
            'by_major' => [],
        ];

        // Get count by grade
        $gradeStats = $this->db->table('classes')
            ->select('grade_level, COUNT(id) as total')
            ->where('deleted_at', null)
            ->groupBy('grade_level')
            ->get()
            ->getResultArray();

        foreach ($gradeStats as $stat) {
            $stats['by_grade'][$stat['grade_level']] = (int) $stat['total'];
        }

        // Get count by major
        $majorStats = $this->db->table('classes')
            ->select('major, COUNT(id) as total')
            ->where('deleted_at', null)
            ->where('major IS NOT NULL', null, false)
            ->groupBy('major')
            ->get()
            ->getResultArray();

        foreach ($majorStats as $stat) {
            $stats['by_major'][$stat['major']] = (int) $stat['total'];
        }

        return $stats;
    }

    /**
     * Get available academic years for dropdown
     *
     * @return array
     */
    public function getAvailableAcademicYears()
    {
        return $this->academicYearModel
            ->orderBy('year_name', 'DESC')
            ->findAll();
    }

    /**
     * Assign homeroom teacher to class
     *
     * @param int $classId
     * @param int $teacherId
     * @return array ['success' => bool, 'message' => string]
     */
    public function assignHomeroom($classId, $teacherId)
    {
        try {
            // Check if teacher is already homeroom elsewhere
            if (ClassValidation::isTeacherAlreadyHomeroom($teacherId, $classId)) {
                return [
                    'success' => false,
                    'message' => 'Guru tersebut sudah menjadi wali kelas di kelas lain yang aktif',
                ];
            }

            // Update class
            if (!$this->classModel->update($classId, ['homeroom_teacher_id' => $teacherId])) {
                return [
                    'success' => false,
                    'message' => 'Gagal menugaskan wali kelas',
                ];
            }

            return [
                'success' => true,
                'message' => 'Wali kelas berhasil ditugaskan',
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error assigning homeroom: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Assign counselor to class
     *
     * @param int $classId
     * @param int $counselorId
     * @return array ['success' => bool, 'message' => string]
     */
    public function assignCounselor($classId, $counselorId)
    {
        try {
            // Update class
            if (!$this->classModel->update($classId, ['counselor_id' => $counselorId])) {
                return [
                    'success' => false,
                    'message' => 'Gagal menugaskan guru BK',
                ];
            }

            return [
                'success' => true,
                'message' => 'Guru BK berhasil ditugaskan',
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error assigning counselor: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Log class activity
     *
     * @param string $action
     * @param int $classId
     * @param string $description
     * @return void
     */
    private function logActivity($action, $classId, $description)
    {
        log_message('info', "[ClassService] Action: {$action}, Class ID: {$classId}, Description: {$description}");
    }
}
