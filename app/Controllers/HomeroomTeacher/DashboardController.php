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
use App\Models\ViolationModel;
use App\Models\CounselingSessionModel;
use CodeIgniter\I18n\Time;


class DashboardController extends BaseController
{
    protected $classModel;
    protected $studentModel;
    protected $violationModel;
    protected $sessionModel;
    protected $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->classModel     = new ClassModel();
        $this->studentModel   = new StudentModel();
        $this->violationModel = new ViolationModel();
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

        // Get homeroom teacher's class
        $class = $this->getHomeroomClass($userId);

        if (!$class) {
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

        $classId = (int) $class['id'];

        // Get dashboard statistics
        $stats = $this->getClassStatistics($classId);

        // Get recent violations (last 7 days)
        $recentViolations = $this->getRecentViolations($classId, 7);

        // Get top violators (top 5)
        $topViolators = $this->getTopViolators($classId, 5);

        // NEW: siswa perlu perhatian (top 5)
        $attentionStudents = $this->getAttentionStudents($classId, 5);

        // Get recent counseling sessions for students in this class
        $recentSessions = $this->getRecentSessions($classId, 5);

        $monthsBack = 6;

        // Tren Layanan BK (6 bulan terakhir) -> pelanggaran + sesi konseling
        $trendLabels     = $this->monthsLabel($monthsBack);
        $trendViolations = $this->getMonthlyViolationsForClass((int) $class['id'], $monthsBack);
        $trendSessions   = $this->getMonthlySessionsForClass((int) $class['id'], $monthsBack);

        // (opsional) biarkan ini tetap ada kalau masih dipakai tempat lain
        $violationTrends = $this->getViolationTrends((int) $class['id'], $monthsBack);

        // Pelanggaran per kategori (6 bulan terakhir)
        $violationByCategory  = $this->getViolationByCategory((int) $class['id'], 5, $monthsBack);
        $categoryRangeLabel   = $monthsBack . ' bulan terakhir';

        // ===== FIX: gunakan helper auth_user() (bukan current_user()) =====
        $currentUser = auth_user();

        // Prepare data for view
        $data = [
            'title'               => 'Dashboard Wali Kelas',
            'pageTitle'           => 'Dashboard Wali Kelas',
            'breadcrumbs'         => [
                ['title' => 'Dashboard', 'url' => '#', 'active' => true],
            ],
            'hasClass'            => true,
            'class'               => $class,
            'stats'               => $stats,
            'recentViolations'    => $recentViolations,
            'trendLabels'        => $trendLabels,
            'trendViolations'    => $trendViolations,
            'trendSessions'      => $trendSessions,
            'categoryRangeLabel' => $categoryRangeLabel,
            'violationByCategory'  => $violationByCategory,
            'topViolators'        => $topViolators,
            'attentionStudents'   => $attentionStudents, // <-- penting untuk card "Siswa Perlu Perhatian"
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

        $class = $this->getHomeroomClass($userId);

        if (!$class) {
            return json_error('Class not found');
        }

        $stats = $this->getClassStatistics((int) $class['id']);

        return json_success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get homeroom teacher's class
     *
     * @param int $userId
     * @return array|null
     */
    private function getHomeroomClass($userId)
    {
        try {
            $class = $this->db->table('classes')
                ->select('classes.*, academic_years.year_name, academic_years.semester')
                ->join('academic_years', 'academic_years.id = classes.academic_year_id')
                ->where('classes.homeroom_teacher_id', $userId)
                ->where('classes.deleted_at', null)
                ->where('academic_years.is_active', 1)
                ->orderBy('classes.created_at', 'DESC')
                ->get()
                ->getRowArray();

            return $class;
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get class error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get class statistics
     *
     * @param int $classId
     * @return array
     */
    private function getClassStatistics($classId)
    {
        try {
            $stats = [];

            $currentMonth = date('m');
            $currentYear  = date('Y');

            // ---------- FILTER DASAR SISWA AKTIF ----------
            $studentFilter = [
                'class_id'   => $classId,
                'status'     => 'Aktif',
                'deleted_at' => null,
            ];

            // Total students (Aktif)
            $stats['total_students'] = $this->db->table('students')
                ->where($studentFilter)
                ->countAllResults();

            // Total violations this month
            $stats['violations_this_month'] = $this->db->table('violations')
                ->join('students', 'students.id = violations.student_id')
                ->where('students.class_id', $classId)
                ->where('MONTH(violations.violation_date)', $currentMonth)
                ->where('YEAR(violations.violation_date)', $currentYear)
                ->where('violations.deleted_at', null)
                ->countAllResults();

            // Total violations this week (7 hari terakhir)
            $stats['violations_this_week'] = $this->db->table('violations')
                ->join('students', 'students.id = violations.student_id')
                ->where('students.class_id', $classId)
                ->where('violations.violation_date >=', date('Y-m-d', strtotime('-7 days')))
                ->where('violations.deleted_at', null)
                ->countAllResults();

            // Students with violations this month
            $rowStudentsViolation = $this->db->table('violations')
                ->select('COUNT(DISTINCT violations.student_id) as count')
                ->join('students', 'students.id = violations.student_id')
                ->where('students.class_id', $classId)
                ->where('MONTH(violations.violation_date)', $currentMonth)
                ->where('YEAR(violations.violation_date)', $currentYear)
                ->where('violations.deleted_at', null)
                ->get()
                ->getRow();

            $stats['students_with_violations'] = $rowStudentsViolation->count ?? 0;

            // Students in counseling this month
            $rowStudentsCounseling = $this->db->table('counseling_sessions')
                ->select('COUNT(DISTINCT counseling_sessions.student_id) as count')
                ->join('students', 'students.id = counseling_sessions.student_id')
                ->where('students.class_id', $classId)
                ->where('MONTH(counseling_sessions.session_date)', $currentMonth)
                ->where('YEAR(counseling_sessions.session_date)', $currentYear)
                ->where('counseling_sessions.deleted_at', null)
                ->get()
                ->getRow();

            $stats['students_in_counseling'] = $rowStudentsCounseling->count ?? 0;

            // Average violation points
            $avgPoints = $this->db->table('violations')
                ->select('AVG(violation_categories.point_deduction) as avg_points')
                ->join('students', 'students.id = violations.student_id')
                ->join('violation_categories', 'violation_categories.id = violations.category_id')
                ->where('students.class_id', $classId)
                ->where('violations.deleted_at', null)
                ->get()
                ->getRow();

            $stats['avg_violation_points'] = $avgPoints ? round($avgPoints->avg_points, 1) : 0;

            // ---------- Gender distribution (hanya siswa aktif) ----------
            $genderRow = $this->db->table('students')
                ->select("
                    SUM(CASE WHEN gender IN ('L','Laki-laki') THEN 1 ELSE 0 END) AS male,
                    SUM(CASE WHEN gender IN ('P','Perempuan') THEN 1 ELSE 0 END) AS female
                ", false)
                ->where($studentFilter)
                ->get()
                ->getRowArray();

            $stats['gender_distribution'] = [
                'male'   => (int) ($genderRow['male'] ?? 0),
                'female' => (int) ($genderRow['female'] ?? 0),
            ];

            // Percentage changes (compare with last month)
            $lastMonth     = date('m', strtotime('-1 month'));
            $lastMonthYear = date('Y', strtotime('-1 month'));

            $lastMonthViolations = $this->db->table('violations')
                ->join('students', 'students.id = violations.student_id')
                ->where('students.class_id', $classId)
                ->where('MONTH(violations.violation_date)', $lastMonth)
                ->where('YEAR(violations.violation_date)', $lastMonthYear)
                ->where('violations.deleted_at', null)
                ->countAllResults();

            if ($lastMonthViolations > 0) {
                $percentageChange = (($stats['violations_this_month'] - $lastMonthViolations) / $lastMonthViolations) * 100;
                $stats['violation_change_percentage'] = round($percentageChange, 1);
                $stats['violation_trend']             = $percentageChange > 0 ? 'up' : 'down';
            } else {
                $stats['violation_change_percentage'] = 0;
                $stats['violation_trend']             = 'stable';
            }

            return $stats;
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get statistics error: ' . $e->getMessage());
            return [
                'total_students'              => 0,
                'violations_this_month'       => 0,
                'violations_this_week'        => 0,
                'students_with_violations'    => 0,
                'students_in_counseling'      => 0,
                'avg_violation_points'        => 0,
                'gender_distribution'         => ['male' => 0, 'female' => 0],
                'violation_change_percentage' => 0,
                'violation_trend'             => 'stable',
            ];
        }
    }

    /**
     * Get recent violations
     *
     * @param int $classId
     * @param int $days
     * @return array
     */
    private function getRecentViolations($classId, $days = 7)
    {
        try {
            return $this->db->table('violations v')
                ->select("
                    v.*,
                    su.full_name AS student_name,
                    s.nisn,
                    vc.category_name,
                    vc.severity_level,
                    vc.point_deduction,
                    ru.full_name AS reported_by_name
                ", false)
                ->join('students s', 's.id = v.student_id')
                ->join('users su', 'su.id = s.user_id', 'left')
                ->join('violation_categories vc', 'vc.id = v.category_id')
                ->join('users ru', 'ru.id = v.reported_by', 'left')
                ->where('s.class_id', $classId)
                ->where('s.deleted_at', null)
                ->where('s.status', 'Aktif')
                ->where('v.violation_date >=', date('Y-m-d', strtotime("-{$days} days")))
                ->where('v.deleted_at', null)
                ->where('vc.deleted_at', null)
                ->orderBy('v.violation_date', 'DESC')
                ->orderBy('v.created_at', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get recent violations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get violation trends (monthly data for charts)
     *
     * @param int $classId
     * @param int $months
     * @return array
     */
    private function getViolationTrends($classId, $months = 6)
    {
        try {
            $trends = [];

            for ($i = $months - 1; $i >= 0; $i--) {
                $month     = date('Y-m', strtotime("-{$i} months"));
                $monthName = date('M Y', strtotime("-{$i} months"));

                $count = $this->db->table('violations')
                    ->join('students', 'students.id = violations.student_id')
                    ->where('students.class_id', $classId)
                    ->where("DATE_FORMAT(violations.violation_date, '%Y-%m')", $month)
                    ->where('violations.deleted_at', null)
                    ->countAllResults();

                $trends[] = [
                    'month' => $monthName,
                    'count' => $count,
                ];
            }

            return $trends;
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get violation trends error: ' . $e->getMessage());
            return [];
        }
    }

    private function monthsLabel(int $monthsBack): array
    {
        $labels = [];
        $now = Time::now()->setDay(1); // awal bulan ini

        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $labels[] = $now->subMonths($i)->format('Y-m');
        }
        return $labels;
    }

    private function mapMonthRowsToSeries(array $labels, array $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            $map[(string) ($r['ym'] ?? '')] = (int) ($r['total'] ?? 0);
        }

        $series = [];
        foreach ($labels as $ym) {
            $series[] = (int) ($map[$ym] ?? 0);
        }
        return $series;
    }

    private function getMonthlyViolationsForClass(int $classId, int $monthsBack = 6): array
    {
        try {
            $labels = $this->monthsLabel($monthsBack);
            $start  = Time::now()
                ->subMonths(max(0, $monthsBack - 1))
                ->format('Y-m-01');

            $rows = $this->db->table('violations v')
                ->select("DATE_FORMAT(v.violation_date, '%Y-%m') AS ym, COUNT(*) AS total", false)
                ->join('students s', 's.id = v.student_id', 'inner')
                ->where('s.class_id', $classId)
                ->where('v.deleted_at', null)
                ->where('v.violation_date >=', $start)
                ->groupBy('ym')
                ->orderBy('ym', 'ASC')
                ->get()
                ->getResultArray();

            return $this->mapMonthRowsToSeries($labels, $rows);
        } catch (\Throwable $e) {
            log_message('error', '[HOMEROOM DASHBOARD] monthly violations error: ' . $e->getMessage());
            return array_fill(0, $monthsBack, 0);
        }
    }

    private function getMonthlySessionsForClass(int $classId, int $monthsBack = 6): array
    {
        try {
            $labels = $this->monthsLabel($monthsBack);
            $start  = Time::now()
                ->subMonths(max(0, $monthsBack - 1))
                ->format('Y-m-01');

            $rows = $this->db->table('counseling_sessions cs')
                ->select("DATE_FORMAT(cs.session_date, '%Y-%m') AS ym, COUNT(*) AS total", false)
                ->join('students s', 's.id = cs.student_id', 'left') // individual sessions
                ->where('cs.deleted_at', null)
                ->where('cs.session_date >=', $start)
                ->groupStart()
                    ->where('cs.class_id', $classId)     // sesi klasikal per kelas
                    ->orWhere('s.class_id', $classId)    // sesi individu siswa kelas ini
                ->groupEnd()
                ->where('cs.status !=', 'Dibatalkan')    // biar tidak menghitung yang batal
                ->groupBy('ym')
                ->orderBy('ym', 'ASC')
                ->get()
                ->getResultArray();

            return $this->mapMonthRowsToSeries($labels, $rows);
        } catch (\Throwable $e) {
            log_message('error', '[HOMEROOM DASHBOARD] monthly sessions error: ' . $e->getMessage());
            return array_fill(0, $monthsBack, 0);
        }
    }

    /**
     * Get top violators
     *
     * @param int $classId
     * @param int $limit
     * @return array
     */
    private function getTopViolators($classId, $limit = 5)
    {
        try {
            return $this->db->table('students s')
                ->select("
                    s.id,
                    su.full_name AS full_name,
                    s.nisn,
                    COUNT(v.id) AS violation_count,
                    COALESCE(SUM(vc.point_deduction), 0) AS total_points
                ", false)
                ->join('users su', 'su.id = s.user_id', 'left')
                ->join('violations v', 'v.student_id = s.id AND v.deleted_at IS NULL', 'left')
                ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
                ->where('s.class_id', $classId)
                ->where('s.deleted_at', null)
                ->where('s.status', 'Aktif')
                ->groupBy('s.id')
                ->having('violation_count >', 0)
                ->orderBy('total_points', 'DESC')
                ->orderBy('violation_count', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get top violators error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * NEW: Get students that need attention (heuristic)
     * Kriteria (sederhana tapi efektif):
     * - total_points >= 25, atau
     * - ada kasus aktif (status Dilaporkan/Dalam Proses), atau
     * - repeat offender, atau
     * - 3+ pelanggaran dalam 30 hari
     *
     * Output menyesuaikan view:
     * - full_name, nisn, total_points, violation_count
     * - attention_status, attention_level (bootstrap color suffix)
     *
     * @param int $classId
     * @param int $limit
     * @return array
     */
    private function getAttentionStudents(int $classId, int $limit = 5): array
    {
        try {
            $date30 = date('Y-m-d', strtotime('-30 days'));
            $date30Esc = $this->db->escape($date30);

            $rows = $this->db->table('students s')
                ->select("
                    s.id,
                    su.full_name AS full_name,
                    s.nisn,
                    COALESCE(SUM(vc.point_deduction), 0) AS total_points,
                    COUNT(v.id) AS violation_count,
                    SUM(CASE WHEN v.status IN ('Dilaporkan','Dalam Proses') THEN 1 ELSE 0 END) AS active_cases,
                    SUM(CASE WHEN v.is_repeat_offender = 1 THEN 1 ELSE 0 END) AS repeat_count,
                    SUM(CASE WHEN v.violation_date >= {$date30Esc} THEN 1 ELSE 0 END) AS violations_30d
                ", false)
                ->join('users su', 'su.id = s.user_id', 'left')
                ->join('violations v', 'v.student_id = s.id AND v.deleted_at IS NULL', 'left')
                ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
                ->where('s.class_id', $classId)
                ->where('s.deleted_at', null)
                ->where('s.status', 'Aktif')
                ->groupBy('s.id')
                ->having(
                    "(COALESCE(SUM(vc.point_deduction), 0) >= 25
                      OR SUM(CASE WHEN v.status IN ('Dilaporkan','Dalam Proses') THEN 1 ELSE 0 END) >= 1
                      OR SUM(CASE WHEN v.is_repeat_offender = 1 THEN 1 ELSE 0 END) >= 1
                      OR SUM(CASE WHEN v.violation_date >= {$date30Esc} THEN 1 ELSE 0 END) >= 3)",
                    null,
                    false
                )
                ->orderBy('total_points', 'DESC')
                ->orderBy('violations_30d', 'DESC')
                ->orderBy('violation_count', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            foreach ($rows as &$r) {
                $total  = (int) ($r['total_points'] ?? 0);
                $active = (int) ($r['active_cases'] ?? 0);
                $repeat = (int) ($r['repeat_count'] ?? 0);
                $v30    = (int) ($r['violations_30d'] ?? 0);

                // Default
                $r['attention_status'] = 'Perlu pemantauan';
                $r['attention_level']  = 'secondary';

                if ($total >= 50) {
                    $r['attention_status'] = 'Poin sangat tinggi';
                    $r['attention_level']  = 'danger';
                } elseif ($total >= 25) {
                    $r['attention_status'] = 'Poin tinggi';
                    $r['attention_level']  = 'warning';
                } elseif ($active >= 1) {
                    $r['attention_status'] = 'Kasus aktif';
                    $r['attention_level']  = 'warning';
                } elseif ($repeat >= 1) {
                    $r['attention_status'] = 'Pelanggar berulang';
                    $r['attention_level']  = 'warning';
                } elseif ($v30 >= 3) {
                    $r['attention_status'] = 'Sering melanggar (30 hari)';
                    $r['attention_level']  = 'info';
                }
            }
            unset($r);

            return $rows;
        } catch (\Throwable $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get attention students error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent counseling sessions
     *
     * @param int $classId
     * @param int $limit
     * @return array
     */
    private function getRecentSessions($classId, $limit = 5)
    {
        try {
            return $this->db->table('counseling_sessions cs')
                ->select("
                    cs.*,
                    su.full_name AS student_name,
                    s.nisn,
                    cu.full_name AS counselor_name
                ", false)
                ->join('students s', 's.id = cs.student_id')
                ->join('users su', 'su.id = s.user_id', 'left')
                ->join('users cu', 'cu.id = cs.counselor_id', 'left')
                ->where('s.class_id', $classId)
                ->where('cs.deleted_at', null)
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

    /**
     * Get violations grouped by category
     *
     * @param int $classId
     * @return array
     */
    private function getViolationByCategory($classId, int $limit = 5, int $monthsBack = 6)
    {
        try {
            $start = Time::now()
                ->subMonths(max(0, $monthsBack - 1))
                ->format('Y-m-01');

            return $this->db->table('violations v')
                ->select('vc.category_name, COUNT(v.id) as count, vc.severity_level')
                ->join('violation_categories vc', 'vc.id = v.category_id', 'inner')
                ->join('students s', 's.id = v.student_id', 'inner')
                ->where('s.class_id', (int) $classId)
                ->where('v.deleted_at', null)
                ->where('vc.deleted_at', null)
                ->where('v.violation_date >=', $start)
                ->groupBy('v.category_id')
                ->orderBy('count', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get violation by category error: ' . $e->getMessage());
            return [];
        }
    }
}
