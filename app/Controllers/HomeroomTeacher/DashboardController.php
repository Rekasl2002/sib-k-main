<?php

/**
 * File Path: app/Controllers/HomeroomTeacher/DashboardController.php
 *
 * Homeroom Teacher Dashboard Controller
 * Menampilkan dashboard untuk Wali Kelas dengan statistik kelas yang diampu
 *
 * @package    SIB-K
 * @subpackage Controllers/HomeroomTeacher
 * @category   Controller
 * @author     Development Team
 * @created    2025-01-07
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Models\CounselingSessionModel;
use CodeIgniter\I18n\Time;


class DashboardController extends BaseController
{
    protected $classModel;
    protected $studentModel;
    protected $sessionModel;
    protected $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->classModel     = new ClassModel();
        $this->studentModel   = new StudentModel();
        $this->sessionModel   = new CounselingSessionModel();
        $this->db             = \Config\Database::connect();

        // Load helpers
        helper(['auth', 'permission', 'date', 'response']);
    }

    /**
     * Display homeroom teacher dashboard
     *
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function index()
    {
        // Check authentication
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Check if user is homeroom teacher
        if (!is_homeroom_teacher()) {
            return redirect()->to(get_dashboard_url())->with('error', 'Akses ditolak');
        }

        // ===== FIX: gunakan helper auth_id() (bukan current_user_id()) =====
        $userId = auth_id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $userId = (int) $userId;

        // Get homeroom teacher's classes. Satu wali bisa memegang lebih dari satu kelas
        // pada data demo, jadi dashboard dihitung dari seluruh kelas perwaliannya.
        $classes = $this->getHomeroomClasses($userId);

        if (empty($classes)) {
            $data = [
                'title'       => 'Dashboard Wali Kelas',
                'pageTitle'   => 'Dashboard Wali Kelas',
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '#', 'active' => true],
                ],
                'hasClass' => false,
                'message'  => 'Anda belum ditugaskan sebagai wali kelas. Silakan hubungi administrator.',
            ];

            return view('homeroom_teacher/dashboard', $data);
        }

        $classIds = $this->classIds($classes);
        $class    = $this->summarizeHomeroomClasses($classes);

        $stats = $this->getClassStatistics($classIds);

        $attentionStudents = $this->getAttentionStudents($classIds, 5);

        $recentSessions = $this->getRecentSessions($classIds, 5);

        $currentUser = auth_user();

        $data = [
            'title'               => 'Dashboard Wali Kelas',
            'pageTitle'           => 'Dashboard Wali Kelas',
            'breadcrumbs'         => [
                ['title' => 'Dashboard', 'url' => '#', 'active' => true],
            ],
            'hasClass'            => true,
            'class'               => $class,
            'classes'             => $classes,
            'classIds'            => $classIds,
            'stats'               => $stats,
            'attentionStudents'   => $attentionStudents,
            'recentSessions'      => $recentSessions,
            'currentUser'         => $currentUser,
        ];

        return view('homeroom_teacher/dashboard', $data);
    }

    /**
     * Get statistics via AJAX
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getStats()
    {
        // Check authentication
        if (!is_logged_in() || !is_homeroom_teacher()) {
            return json_unauthorized('Unauthorized access');
        }

        // ===== FIX: gunakan helper auth_id() (bukan current_user_id()) =====
        $userId = auth_id();
        if (!$userId) {
            return json_unauthorized('Unauthorized access');
        }
        $userId = (int) $userId;

        $classes = $this->getHomeroomClasses($userId);

        if (empty($classes)) {
            return json_error('Class not found');
        }

        $stats = $this->getClassStatistics($this->classIds($classes));

        return json_success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get homeroom teacher's classes.
     *
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    private function getHomeroomClasses($userId): array
    {
        try {
            return $this->db->table('classes')
                ->select('classes.*, academic_years.year_name, academic_years.semester')
                ->join('academic_years', 'academic_years.id = classes.academic_year_id')
                ->where('classes.homeroom_teacher_id', (int) $userId)
                ->where('classes.deleted_at', null)
                ->where('classes.is_active', 1)
                ->where('academic_years.is_active', 1)
                ->orderBy('classes.grade_level', 'ASC')
                ->orderBy('classes.class_name', 'ASC')
                ->orderBy('classes.id', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get classes error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Keep a compact class payload for the view while still supporting several
     * classes in the controller calculations.
     */
    private function summarizeHomeroomClasses(array $classes): array
    {
        if (count($classes) <= 1) {
            return $classes[0] ?? [];
        }

        $summary = $classes[0];
        $summary['id'] = null;
        $summary['class_count'] = count($classes);
        $summary['is_multiple'] = true;
        $summary['class_name'] = implode(', ', array_map(static function ($row) {
            return (string) ($row['class_name'] ?? '-');
        }, $classes));

        return $summary;
    }

    private function classIds(array $classes): array
    {
        $ids = [];
        foreach ($classes as $class) {
            $id = (int) ($class['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalizeClassIds($classIds): array
    {
        $ids = is_array($classIds) ? $classIds : [$classIds];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn($id) => $id > 0);

        return array_values(array_unique($ids));
    }

    private function applyClassFilter($builder, string $field, $classIds)
    {
        $ids = $this->normalizeClassIds($classIds);

        if (count($ids) === 1) {
            return $builder->where($field, $ids[0]);
        }

        return $builder->whereIn($field, $ids ?: [0]);
    }

    /**
     * Get class statistics
     *
     * @param int|array $classIds
     * @return array
     */
    private function getClassStatistics($classIds)
    {
        $classIds = $this->normalizeClassIds($classIds);
        $emptyStats = [
            'total_students'         => 0,
            'students_in_counseling' => 0,
            'gender_distribution'    => ['male' => 0, 'female' => 0],
        ];

        if (empty($classIds)) {
            return $emptyStats;
        }

        try {
            $studentFilter = ['status' => 'Aktif', 'deleted_at' => null];
            $studentCountBuilder = $this->db->table('students')->where($studentFilter);
            $this->applyClassFilter($studentCountBuilder, 'class_id', $classIds);
            $emptyStats['total_students'] = $studentCountBuilder->countAllResults();

            $currentMonth = date('m');
            $currentYear  = date('Y');
            $studentsCounselingBuilder = $this->db->table('counseling_sessions')
                ->select('COUNT(DISTINCT counseling_sessions.student_id) as count')
                ->join('students', 'students.id = counseling_sessions.student_id')
                ->where('MONTH(counseling_sessions.session_date)', $currentMonth)
                ->where('YEAR(counseling_sessions.session_date)', $currentYear)
                ->where('counseling_sessions.deleted_at', null);
            $this->applyClassFilter($studentsCounselingBuilder, 'students.class_id', $classIds);
            $rowStudentsCounseling = $studentsCounselingBuilder->get()->getRow();
            $emptyStats['students_in_counseling'] = (int) ($rowStudentsCounseling->count ?? 0);

            $genderRow = $this->db->table('students')
                ->select("
                    SUM(CASE WHEN gender IN ('L','Laki-laki') THEN 1 ELSE 0 END) AS male,
                    SUM(CASE WHEN gender IN ('P','Perempuan') THEN 1 ELSE 0 END) AS female
                ", false)
                ->where($studentFilter);
            $this->applyClassFilter($genderRow, 'class_id', $classIds);
            $genderRow = $genderRow->get()->getRowArray();

            $emptyStats['gender_distribution'] = [
                'male'   => (int) ($genderRow['male'] ?? 0),
                'female' => (int) ($genderRow['female'] ?? 0),
            ];

            return $emptyStats;
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get statistics error: ' . $e->getMessage());
            return $emptyStats;
        }
    }

    /**
     * Get students that need attention.
     * Placeholder for non-disciplinary indicators that can be added later.
     * 
     * @param int|array $classIds
     * @param int $limit
     * @return array
     */
    private function getAttentionStudents($classIds, int $limit = 5): array
    {
        return [];
    }

    /**
     * Get recent counseling sessions
     *
     * @param int|array $classIds
     * @param int $limit
     * @return array
     */
    private function getRecentSessions($classIds, $limit = 5)
    {
        try {
            $builder = $this->db->table('counseling_sessions cs')
                ->select("
                    cs.*,
                    su.full_name AS student_name,
                    s.nik,
                    s.nisn,
                    cu.full_name AS counselor_name
                ", false)
                ->join('students s', 's.id = cs.student_id')
                ->join('users su', 'su.id = s.user_id', 'left')
                ->join('users cu', 'cu.id = cs.counselor_id', 'left')
                ->where('cs.deleted_at', null);

            $this->applyClassFilter($builder, 's.class_id', $classIds);

            return $builder
                ->orderBy('cs.session_date', 'DESC')
                ->orderBy('cs.created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get recent sessions error: ' . $e->getMessage());
            return [];
        }
    }

}
