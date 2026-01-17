<?php

/**
 * File Path: app/Controllers/Admin/DashboardController.php
 * 
 * Admin Dashboard Controller
 * Menampilkan statistik dan overview sistem untuk Admin/Koordinator BK
 * 
 * @package    SIB-K
 * @subpackage Controllers/Admin
 * @category   Dashboard
 * @author     Development Team
 * @created    2025-01-05
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Models\RoleModel;

class DashboardController extends BaseController
{
    protected $userModel;
    protected $studentModel;
    protected $classModel;
    protected $academicYearModel;
    protected $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->academicYearModel = new AcademicYearModel();
        $this->roleModel = new RoleModel();
    }

    /**
     * Display admin dashboard with statistics
     * 
     * @return string
     */
    public function index()
    {
        // Get current user
        $user = auth_user();

        // Get active academic year
        $activeYear = $this->academicYearModel->where('is_active', 1)->first();

        // Get total statistics
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'total_students' => $this->getTotalStudents(),
            'total_active_students' => $this->getTotalActiveStudents(),
            'total_classes' => $this->getTotalClasses(),
            'users_by_role' => $this->getUsersByRole(),
            'students_by_grade' => $this->getStudentsByGrade(),
            'students_by_status' => $this->getStudentsByStatus(),
            'recent_students' => $this->getRecentStudents(5),
            'recent_users' => $this->getRecentUsers(5),
        ];

        // Get class statistics for active year
        if ($activeYear) {
            $stats['classes_by_grade'] = $this->getClassesByGrade($activeYear['id']);
        }

        // Chart data for student growth (last 6 months)
        $stats['student_growth_chart'] = $this->getStudentGrowthData();

        $data = [
            'title' => 'Dashboard Admin',
            'page_title' => 'Dashboard Admin',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => '#'],
                ['title' => 'Dashboard', 'link' => null],
            ],
            'user' => $user,
            'active_year' => $activeYear,
            'stats' => $stats,
        ];

        return view('admin/dashboard', $data);
    }

    /**
     * Get total users count
     * 
     * @return int
     */
    private function getTotalUsers()
    {
        return $this->userModel->where('is_active', 1)->countAllResults();
    }

    /**
     * Get total students count
     * 
     * @return int
     */
    private function getTotalStudents()
    {
        return $this->studentModel->countAllResults();
    }

    /**
     * Get total active students count
     * 
     * @return int
     */
    private function getTotalActiveStudents()
    {
        return $this->studentModel->where('status', 'Aktif')->countAllResults();
    }

    /**
     * Get total classes count
     * 
     * @return int
     */
    private function getTotalClasses()
    {
        return $this->classModel->where('is_active', 1)->countAllResults();
    }

    /**
     * Get users count by role
     * 
     * @return array
     */
    private function getUsersByRole()
    {
        $db = \Config\Database::connect();

        $query = $db->table('users')
            ->select('roles.role_name, COUNT(users.id) as total')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->groupBy('roles.id, roles.role_name')
            ->get();

        $result = [];
        foreach ($query->getResultArray() as $row) {
            $result[$row['role_name']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Get students count by grade level
     * 
     * @return array
     */
    private function getStudentsByGrade()
    {
        $db = \Config\Database::connect();

        $query = $db->table('students')
            ->select('classes.grade_level, COUNT(students.id) as total')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.status', 'Aktif')
            ->where('students.deleted_at', null)
            ->groupBy('classes.grade_level')
            ->orderBy('classes.grade_level', 'ASC')
            ->get();

        $result = [];
        foreach ($query->getResultArray() as $row) {
            $grade = $row['grade_level'] ?? 'Belum Ada Kelas';
            $result[$grade] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Get students count by status
     * 
     * @return array
     */
    private function getStudentsByStatus()
    {
        $db = \Config\Database::connect();

        $query = $db->table('students')
            ->select('status, COUNT(id) as total')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get();

        $result = [];
        foreach ($query->getResultArray() as $row) {
            $result[$row['status']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Get classes count by grade level for specific academic year
     * 
     * @param int $academicYearId
     * @return array
     */
    private function getClassesByGrade($academicYearId)
    {
        $db = \Config\Database::connect();

        $query = $db->table('classes')
            ->select('grade_level, COUNT(id) as total')
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->groupBy('grade_level')
            ->orderBy('grade_level', 'ASC')
            ->get();

        $result = [];
        foreach ($query->getResultArray() as $row) {
            $result[$row['grade_level']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Get recent students (last registered)
     * 
     * @param int $limit
     * @return array
     */
    private function getRecentStudents($limit = 5)
    {
        return $this->studentModel
            ->select('students.*, users.full_name, users.email, classes.class_name, classes.grade_level')
            ->join('users', 'users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->orderBy('students.created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get recent users (last registered)
     * 
     * @param int $limit
     * @return array
     */
    private function getRecentUsers($limit = 5)
    {
        return $this->userModel
            ->select('users.*, roles.role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->orderBy('users.created_at', 'DESC')
            ->limit($limit)
            ->find();
    }

    /**
     * Get student growth data for chart (last 6 months)
     * 
     * @return array
     */
    private function getStudentGrowthData()
    {
        $db = \Config\Database::connect();

        // Get data for last 6 months
        $query = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(id) as total
            FROM students
            WHERE deleted_at IS NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");

        $result = $query->getResultArray();

        // Format for chart.js
        $labels = [];
        $data = [];

        foreach ($result as $row) {
            // Convert 2025-01 to Jan 2025
            $date = \DateTime::createFromFormat('Y-m', $row['month']);
            $labels[] = $date->format('M Y');
            $data[] = (int) $row['total'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get dashboard statistics via AJAX
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getStats()
    {
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'total_students' => $this->getTotalStudents(),
            'total_active_students' => $this->getTotalActiveStudents(),
            'total_classes' => $this->getTotalClasses(),
        ];

        return $this->response->setJSON([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
