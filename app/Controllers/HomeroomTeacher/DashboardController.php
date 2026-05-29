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

        // Get dashboard statistics
        $stats = $this->getClassStatistics($classIds);

        // Get recent violations (last 7 days)
        $recentViolations = $this->getRecentViolations($classIds, 7);

        // Get top violators (top 5)
        $topViolators = $this->getTopViolators($classIds, 5);

        // NEW: siswa perlu perhatian (top 5)
        $attentionStudents = $this->getAttentionStudents($classIds, 5);

        // Get recent counseling sessions for students in this class
        $recentSessions = $this->getRecentSessions($classIds, 5);

        $monthsBack = 6;

        // Tren Layanan BK (6 bulan terakhir) -> pelanggaran + sesi konseling
        $trendLabels     = $this->monthsLabel($monthsBack);
        $trendViolations = $this->getMonthlyViolationsForClass($classIds, $monthsBack);
        $trendSessions   = $this->getMonthlySessionsForClass($classIds, $monthsBack);

        // (opsional) biarkan ini tetap ada kalau masih dipakai tempat lain
        $violationTrends = $this->getViolationTrends($classIds, $monthsBack);

        // Pelanggaran per kategori (6 bulan terakhir)
        $violationByCategory  = $this->getViolationByCategory($classIds, 5, $monthsBack);
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
            'classes'             => $classes,
            'classIds'            => $classIds,
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

        if (empty($classIds)) {
            return $emptyStats;
        }

        try {
            $stats = [];

            $currentMonth = date('m');
            $currentYear  = date('Y');

            // ---------- FILTER DASAR SISWA AKTIF ----------
            $studentFilter = [
                'status'     => 'Aktif',
                'deleted_at' => null,
            ];

            // Total students (Aktif)
            $studentCountBuilder = $this->db->table('students')->where($studentFilter);
            $this->applyClassFilter($studentCountBuilder, 'class_id', $classIds);
            $stats['total_students'] = $studentCountBuilder->countAllResults();

            // Total violations this month
            $violationsMonthBuilder = $this->db->table('violations')
                ->join('students', 'students.id = violations.student_id')
                ->where('MONTH(violations.violation_date)', $currentMonth)
                ->where('YEAR(violations.violation_date)', $currentYear)
                ->where('violations.deleted_at', null);
            $this->applyClassFilter($violationsMonthBuilder, 'students.class_id', $classIds);
            $stats['violations_this_month'] = $violationsMonthBuilder->countAllResults();

            // Total violations this week (7 hari terakhir)
            $violationsWeekBuilder = $this->db->table('violations')
                ->join('students', 'students.id = violations.student_id')
                ->where('violations.violation_date >=', date('Y-m-d', strtotime('-7 days')))
                ->where('violations.deleted_at', null);
            $this->applyClassFilter($violationsWeekBuilder, 'students.class_id', $classIds);
            $stats['violations_this_week'] = $violationsWeekBuilder->countAllResults();

            // Students with violations this month
            $studentsViolationBuilder = $this->db->table('violations')
                ->select('COUNT(DISTINCT violations.student_id) as count')
                ->join('students', 'students.id = violations.student_id')
                ->where('MONTH(violations.violation_date)', $currentMonth)
                ->where('YEAR(violations.violation_date)', $currentYear)
                ->where('violations.deleted_at', null);
            $this->applyClassFilter($studentsViolationBuilder, 'students.class_id', $classIds);
            $rowStudentsViolation = $studentsViolationBuilder->get()->getRow();

            $stats['students_with_violations'] = $rowStudentsViolation->count ?? 0;

            // Students in counseling this month
            $studentsCounselingBuilder = $this->db->table('counseling_sessions')
                ->select('COUNT(DISTINCT counseling_sessions.student_id) as count')
                ->join('students', 'students.id = counseling_sessions.student_id')
                ->where('MONTH(counseling_sessions.session_date)', $currentMonth)
                ->where('YEAR(counseling_sessions.session_date)', $currentYear)
                ->where('counseling_sessions.deleted_at', null);
            $this->applyClassFilter($studentsCounselingBuilder, 'students.class_id', $classIds);
            $rowStudentsCounseling = $studentsCounselingBuilder->get()->getRow();

            $stats['students_in_counseling'] = $rowStudentsCounseling->count ?? 0;

            // Average violation points
            $avgPointsBuilder = $this->db->table('violations')
                ->select('AVG(violation_categories.point_deduction) as avg_points')
                ->join('students', 'students.id = violations.student_id')
                ->join('violation_categories', 'violation_categories.id = violations.category_id')
                ->where('violations.deleted_at', null);
            $this->applyClassFilter($avgPointsBuilder, 'students.class_id', $classIds);
            $avgPoints = $avgPointsBuilder->get()->getRow();

            $stats['avg_violation_points'] = $avgPoints ? round($avgPoints->avg_points, 1) : 0;

            // ---------- Gender distribution (hanya siswa aktif) ----------
            $genderRow = $this->db->table('students')
                ->select("
                    SUM(CASE WHEN gender IN ('L','Laki-laki') THEN 1 ELSE 0 END) AS male,
                    SUM(CASE WHEN gender IN ('P','Perempuan') THEN 1 ELSE 0 END) AS female
                ", false)
                ->where($studentFilter);
            $this->applyClassFilter($genderRow, 'class_id', $classIds);
            $genderRow = $genderRow->get()->getRowArray();

            $stats['gender_distribution'] = [
                'male'   => (int) ($genderRow['male'] ?? 0),
                'female' => (int) ($genderRow['female'] ?? 0),
            ];

            // Percentage changes (compare with last month)
            $lastMonth     = date('m', strtotime('-1 month'));
            $lastMonthYear = date('Y', strtotime('-1 month'));

            $lastMonthBuilder = $this->db->table('violations')
                ->join('students', 'students.id = violations.student_id')
                ->where('MONTH(violations.violation_date)', $lastMonth)
                ->where('YEAR(violations.violation_date)', $lastMonthYear)
                ->where('violations.deleted_at', null);
            $this->applyClassFilter($lastMonthBuilder, 'students.class_id', $classIds);
            $lastMonthViolations = $lastMonthBuilder->countAllResults();

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
            return $emptyStats;
        }
    }

    /**
     * Get recent violations
     *
     * @param int|array $classIds
     * @param int $days
     * @return array
     */
    private function getRecentViolations($classIds, $days = 7)
    {
        try {
            $builder = $this->db->table('violations v')
                ->select("
                    v.*,
                    su.full_name AS student_name,
                    s.nik,
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
                ->where('s.deleted_at', null)
                ->where('s.status', 'Aktif')
                ->where('v.violation_date >=', date('Y-m-d', strtotime("-{$days} days")))
                ->where('v.deleted_at', null)
                ->where('vc.deleted_at', null);

            $this->applyClassFilter($builder, 's.class_id', $classIds);

            return $builder
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
     * @param int|array $classIds
     * @param int $months
     * @return array
     */
    private function getViolationTrends($classIds, $months = 6)
    {
        try {
            $trends = [];

            for ($i = $months - 1; $i >= 0; $i--) {
                $month     = date('Y-m', strtotime("-{$i} months"));
                $monthName = date('M Y', strtotime("-{$i} months"));

                $builder = $this->db->table('violations')
                    ->join('students', 'students.id = violations.student_id')
                    ->where("DATE_FORMAT(violations.violation_date, '%Y-%m')", $month)
                    ->where('violations.deleted_at', null);

                $this->applyClassFilter($builder, 'students.class_id', $classIds);
                $count = $builder->countAllResults();

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

    private function getMonthlyViolationsForClass($classIds, int $monthsBack = 6): array
    {
        try {
            $labels = $this->monthsLabel($monthsBack);
            $start  = Time::now()
                ->subMonths(max(0, $monthsBack - 1))
                ->format('Y-m-01');

            $builder = $this->db->table('violations v')
                ->select("DATE_FORMAT(v.violation_date, '%Y-%m') AS ym, COUNT(*) AS total", false)
                ->join('students s', 's.id = v.student_id', 'inner')
                ->where('v.deleted_at', null)
                ->where('v.violation_date >=', $start);

            $this->applyClassFilter($builder, 's.class_id', $classIds);

            $rows = $builder
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

    private function getMonthlySessionsForClass($classIds, int $monthsBack = 6): array
    {
        try {
            $labels = $this->monthsLabel($monthsBack);
            $start  = Time::now()
                ->subMonths(max(0, $monthsBack - 1))
                ->format('Y-m-01');

            $ids = $this->normalizeClassIds($classIds);

            $builder = $this->db->table('counseling_sessions cs')
                ->select("DATE_FORMAT(cs.session_date, '%Y-%m') AS ym, COUNT(*) AS total", false)
                ->join('students s', 's.id = cs.student_id', 'left') // individual sessions
                ->where('cs.deleted_at', null)
                ->where('cs.session_date >=', $start)
                ->groupStart();

            if (count($ids) === 1) {
                $builder->where('cs.class_id', $ids[0])
                    ->orWhere('s.class_id', $ids[0]);
            } else {
                $builder->whereIn('cs.class_id', $ids ?: [0])
                    ->orWhereIn('s.class_id', $ids ?: [0]);
            }

            $rows = $builder
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
     * @param int|array $classIds
     * @param int $limit
     * @return array
     */
    private function getTopViolators($classIds, $limit = 5)
    {
        try {
            $builder = $this->db->table('students s')
                ->select("
                    s.id,
                    su.full_name AS full_name,
                    s.nik,
                    s.nisn,
                    COUNT(v.id) AS violation_count,
                    COALESCE(SUM(vc.point_deduction), 0) AS total_points
                ", false)
                ->join('users su', 'su.id = s.user_id', 'left')
                ->join('violations v', 'v.student_id = s.id AND v.deleted_at IS NULL', 'left')
                ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
                ->where('s.deleted_at', null)
                ->where('s.status', 'Aktif');

            $this->applyClassFilter($builder, 's.class_id', $classIds);

            return $builder
                ->groupBy('s.id, su.full_name, s.nik, s.nisn')
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
     * @param int|array $classIds
     * @param int $limit
     * @return array
     */
    private function getAttentionStudents($classIds, int $limit = 5): array
    {
        try {
            $date30 = date('Y-m-d', strtotime('-30 days'));
            $date30Esc = $this->db->escape($date30);

            $builder = $this->db->table('students s')
                ->select("
                    s.id,
                    su.full_name AS full_name,
                    s.nik,
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
                ->where('s.deleted_at', null)
                ->where('s.status', 'Aktif');

            $this->applyClassFilter($builder, 's.class_id', $classIds);

            $rows = $builder
                ->groupBy('s.id, su.full_name, s.nik, s.nisn')
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

    /**
     * Get violations grouped by category
     *
     * @param int|array $classIds
     * @return array
     */
    private function getViolationByCategory($classIds, int $limit = 5, int $monthsBack = 6)
    {
        try {
            $start = Time::now()
                ->subMonths(max(0, $monthsBack - 1))
                ->format('Y-m-01');

            $builder = $this->db->table('violations v')
                ->select('vc.category_name, COUNT(v.id) as count, vc.severity_level')
                ->join('violation_categories vc', 'vc.id = v.category_id', 'inner')
                ->join('students s', 's.id = v.student_id', 'inner')
                ->where('v.deleted_at', null)
                ->where('vc.deleted_at', null)
                ->where('v.violation_date >=', $start);

            $this->applyClassFilter($builder, 's.class_id', $classIds);

            return $builder
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
