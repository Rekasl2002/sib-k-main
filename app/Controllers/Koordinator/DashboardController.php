<?php
/**
 * File Path: app/Controllers/Koordinator/DashboardController.php
 *
 * Koordinator BK • Dashboard
 * Merangkum statistik sekolah: siswa, staf, pelanggaran, sesi konseling, asesmen, laporan, notifikasi.
 * Memakai service yang ada; fallback ke model jika method tidak tersedia.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\Koordinator\BaseKoordinatorController;
use CodeIgniter\I18n\Time;

// Services
use App\Services\CoordinatorService;
use App\Services\AssessmentService;
use App\Services\ViolationService;
use App\Services\ReportService;
use App\Services\StudentService;
use App\Services\UserService;

// Models (fallback)
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\ViolationModel;
use App\Models\AssessmentModel;
use App\Models\AssessmentResultModel;

class DashboardController extends BaseKoordinatorController
{
    protected CoordinatorService $coord;
    protected ?AssessmentService $assessmentSvc = null;
    protected ?ViolationService $violationSvc   = null;
    protected ?ReportService $reportSvc         = null;
    protected ?StudentService $studentSvc       = null;
    protected ?UserService $userSvc             = null;

    // Fallback models
    protected ?StudentModel $studentModel               = null;
    protected ?UserModel $userModel                     = null;
    protected ?RoleModel $roleModel                     = null;
    protected ?ViolationModel $violationModel           = null;
    protected ?AssessmentModel $assessmentModel         = null;
    protected ?AssessmentResultModel $assessmentResultModel = null;

    public function __construct()
    {
        // Penting: JANGAN panggil parent::__construct()
        // BaseKoordinatorController tidak punya constructor, jadi memanggilnya akan
        // memicu error "Cannot call constructor" di PHP.

        // Services (yang pasti ada)
        $this->coord = new CoordinatorService();

        // Services (opsional, jika tersedia)
        $this->assessmentSvc = class_exists(AssessmentService::class) ? new AssessmentService() : null;
        $this->violationSvc  = class_exists(ViolationService::class)  ? new ViolationService()  : null;
        $this->reportSvc     = class_exists(ReportService::class)     ? new ReportService()     : null;
        $this->studentSvc    = class_exists(StudentService::class)    ? new StudentService()    : null;
        $this->userSvc       = class_exists(UserService::class)       ? new UserService()       : null;

        // Models fallback
        $this->studentModel          = class_exists(StudentModel::class)          ? new StudentModel()          : null;
        $this->userModel             = class_exists(UserModel::class)             ? new UserModel()             : null;
        $this->roleModel             = class_exists(RoleModel::class)             ? new RoleModel()             : null;
        $this->violationModel        = class_exists(ViolationModel::class)        ? new ViolationModel()        : null;
        $this->assessmentModel       = class_exists(AssessmentModel::class)       ? new AssessmentModel()       : null;
        $this->assessmentResultModel = class_exists(AssessmentResultModel::class) ? new AssessmentResultModel() : null;
    }

    public function index()
    {
        // Pastikan hanya Koordinator BK yang bisa mengakses
        if (method_exists($this, 'requireKoordinator')) {
            $this->requireKoordinator();
        }

        // ---------- QUICK STATS ----------
        $quick = $this->safeCall($this->coord, 'getQuickStats', []) ?? [];

        // Tambahan quick (asesmen aktif, laporan, notifikasi)
        $assessmentQuick = [
            'activeAssessments'   => $this->tryCountActiveAssessments(),
            'totalAssessmentDone' => $this->tryCountAssessmentResultsMonth(),
        ];

        $reportsQuick = [
            'reportsGenerated' => $this->tryCountReportsGeneratedMonth(),
        ];

        $notificationsQuick = [
            'unreadNotifications' => $this->tryCountUnreadNotifications(),
        ];

        $quickStats = array_merge($quick, $assessmentQuick, $reportsQuick, $notificationsQuick);

        // ---------- DISTRIBUSI PELANGGARAN ----------
        $violationsByLevel = $this->safeCall($this->coord, 'getViolationSummaryByLevel', []) ?? [];

        // ---------- TREN BULANAN ----------
        $monthsBack          = 6;
        $monthlyViolations   = $this->tryGetMonthlyViolations($monthsBack);
        $monthlySessions     = $this->tryGetMonthlySessions($monthsBack);   // lewat CoordinatorService
        // $monthlyAssessments  = $this->tryGetMonthlyAssessments($monthsBack);

        // ---------- TOP LIST ----------
        $topStudents   = $this->tryGetTopStudentsByViolations(5);
        $topCounselors = $this->tryGetTopCounselorsBySessions(5);

        // ---------- RINGKASAN ASESMEN ----------
        $assessmentCompletion = $this->tryGetAssessmentCompletionTop(5);

        // ---------- AKTIVITAS TERBARU ----------
        $recentActivities = $this->safeCall($this->coord, 'getRecentActivities', [10]) ?? [];

        // ---------- RINGKASAN SEKOLAH LENGKAP ----------
        $schoolSummary = $this->safeCall($this->coord, 'getSchoolWideSummary', []) ?? [];

        // ---------- PELANGGARAN PER KATEGORI (untuk widget doughnut) ----------
        $monthsBack = 6;
        $violationByCategory = $this->tryGetViolationByCategory(5, $monthsBack);
        $categoryRangeLabel = $monthsBack . ' bulan terakhir';

        // ---------- CURRENT USER (untuk kartu Selamat Datang) ----------
        helper('auth');
        $currentUser = function_exists('auth_user') ? (auth_user() ?? []) : [];
        $activeAcademic = $this->getActiveAcademicYearInfo();


        // Kirim ke view
        $data = [
            'pageTitle'            => 'Dashboard Koordinator BK',
            'currentUser'        => $currentUser,
            'activeAcademic' => $activeAcademic,
            'violationByCategory'=> $violationByCategory,
            'categoryRangeLabel' => $categoryRangeLabel,
            'quick'                => $quickStats,
            'violationsByLevel'    => $violationsByLevel,
            'monthlyViolations'    => $monthlyViolations,
            'monthlySessions'      => $monthlySessions,
            //'monthlyAssessments'   => $monthlyAssessments,
            'topStudents'          => $topStudents,
            'topCounselors'        => $topCounselors,
            'assessmentCompletion' => $assessmentCompletion,
            'recentActivities'     => $recentActivities,
            'schoolSummary'        => $schoolSummary,
        ];

        return view('koordinator/dashboard', $data);
    }

    /* ============================================================
     * Helper aman untuk panggil service + fallback query builder
     * ============================================================
     */

    protected function safeCall($obj, string $method, array $args)
    {
        if (!$obj || !method_exists($obj, $method)) {
            return null;
        }

        try {
            return $obj->{$method}(...$args);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard safeCall error: ' . $e->getMessage());
            return null;
        }
    }

    protected function monthsLabel(int $monthsBack = 6): array
    {
        // Contoh: ['2025-07','2025-08','...','2025-12']
        $labels = [];
        $now    = Time::now();
        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $labels[] = $now->subMonths($i)->format('Y-m');
        }
        return $labels;
    }

    protected function getActiveAcademicYearInfo(): array
    {
        try {
            $db = \Config\Database::connect();

            // pastikan tabel ada
            if (!method_exists($db, 'tableExists') || !$db->tableExists('academic_years')) {
                return [];
            }

            $fields = $db->getFieldNames('academic_years');

            $b = $db->table('academic_years')->select('*');

            // soft delete guard
            if (in_array('deleted_at', $fields, true)) {
                $b->where('deleted_at', null);
            }

            // cari yang aktif dengan beberapa kemungkinan nama kolom
            if (in_array('is_active', $fields, true)) {
                $b->where('is_active', 1);
            } elseif (in_array('active', $fields, true)) {
                $b->where('active', 1);
            } elseif (in_array('status', $fields, true)) {
                $b->where('status', 'active');
            }

            // ambil yang terbaru/aktif (fallback kalau lebih dari 1)
            $orderCol = in_array('updated_at', $fields, true) ? 'updated_at' : 'id';
            $row = $b->orderBy($orderCol, 'DESC')->get(1)->getRowArray();

            if (!$row) return [];

            // beberapa kemungkinan nama kolom tahun ajaran/semester
            $year = $row['year_name']
                ?? $row['academic_year']
                ?? $row['name']
                ?? $row['label']
                ?? $row['tahun_ajaran']
                ?? '';

            $semester = $row['semester']
                ?? $row['semester_name']
                ?? $row['term']
                ?? $row['periode']
                ?? '';

            return [
                'year'     => (string) $year,
                'semester' => (string) $semester,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'getActiveAcademicYearInfo error: ' . $e->getMessage());
            return [];
        }
    }

    /* ---------- Pelanggaran per kategori (widget doughnut) ---------- */
    protected function tryGetViolationByCategory(int $limit = 5, int $monthsBack = 6): array
    {
        // Jika nanti CoordinatorService punya method khusus, kita pakai dulu
        $val = $this->safeCall($this->coord, 'getViolationByCategory', [$limit, $monthsBack]);
        if (is_array($val)) {
            return $val;
        }

        try {
            $db = \Config\Database::connect();

            $builder = $db->table('violation_categories vc');
            $builder->select('vc.category_name, vc.severity_level, COUNT(v.id) as count');
            $builder->join('violations v', 'v.category_id = vc.id AND v.deleted_at IS NULL', 'inner');
            $builder->where('vc.deleted_at', null);

            // Default: 6 bulan terakhir (mulai dari awal bulan)
            if ($monthsBack > 0) {
                $start = Time::now()
                    ->subMonths(max(0, $monthsBack - 1))
                    ->format('Y-m-01');

                $builder->where('v.violation_date >=', $start);
            }

            return $builder
                ->groupBy('vc.id')
                ->orderBy('count', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'tryGetViolationByCategory error: ' . $e->getMessage());
            return [];
        }
    }


    /* ---------- Quick counts tambahan ---------- */

    protected function tryCountActiveAssessments(): int
    {
        // Prioritas via service
        $val = $this->safeCall($this->assessmentSvc, 'countActiveAssessments', []);
        if (is_numeric($val)) {
            return (int) $val;
        }

        // Fallback via model (cocok dengan struktur tabel assessments)
        if ($this->assessmentModel) {
            try {
                return (int) $this->assessmentModel
                    ->where('deleted_at', null)
                    ->groupStart()
                        ->where('is_active', 1)
                        ->orWhere('is_published', 1)
                    ->groupEnd()
                    ->countAllResults();
            } catch (\Throwable $e) {
                log_message('error', 'tryCountActiveAssessments error: ' . $e->getMessage());
            }
        }

        return 0;
    }

    protected function tryCountAssessmentResultsMonth(): int
    {
        // Bulan berjalan
        $now   = Time::now();
        $start = $now->format('Y-m-01 00:00:00');
        $end   = $now->format('Y-m-t 23:59:59');

        $val = $this->safeCall($this->assessmentSvc, 'countResultsInRange', [$start, $end]);
        if (is_numeric($val)) {
            return (int) $val;
        }

        if ($this->assessmentResultModel) {
            try {
                return (int) $this->assessmentResultModel
                    ->where('deleted_at', null)
                    ->where('created_at >=', $start)
                    ->where('created_at <=', $end)
                    ->countAllResults();
            } catch (\Throwable $e) {
                log_message('error', 'tryCountAssessmentResultsMonth error: ' . $e->getMessage());
            }
        }
        return 0;
    }

    protected function tryCountReportsGeneratedMonth(): int
    {
        $now   = Time::now();
        $start = $now->format('Y-m-01 00:00:00');
        $end   = $now->format('Y-m-t 23:59:59');

        $val = $this->safeCall($this->reportSvc, 'countGeneratedInRange', [$start, $end]);
        return is_numeric($val) ? (int) $val : 0;
    }

    protected function tryCountUnreadNotifications(): int
    {
        // Placeholder sampai ada NotificationService/Model khusus
        return 0;
    }

    /* ---------- Tren bulanan ---------- */

    protected function tryGetMonthlyViolations(int $monthsBack): array
    {
        // Coba via ViolationService
        $val = $this->safeCall($this->violationSvc, 'getMonthlyTrend', [$monthsBack]);
        if (is_array($val)) {
            return $this->normalizeMonthSeries($val, $monthsBack);
        }

        // Fallback via model
        if (!$this->violationModel) {
            return $this->emptyMonthSeries($monthsBack);
        }

        try {
            $labels = $this->monthsLabel($monthsBack);
            $db     = $this->violationModel->builder();

            // Hanya pelanggaran non soft-delete
            $rows = $db->select("DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total", false)
                ->where('deleted_at', null)
                ->groupBy('ym')
                ->orderBy('ym', 'ASC')
                ->get()
                ->getResultArray();

            return $this->mapMonthRowsToSeries($labels, $rows);
        } catch (\Throwable $e) {
            log_message('error', 'monthly violations error: ' . $e->getMessage());
        }

        return $this->emptyMonthSeries($monthsBack);
    }

    protected function tryGetMonthlySessions(int $monthsBack): array
    {
        // Disediakan di CoordinatorService (sudah memfilter deleted_at)
        $val = $this->safeCall($this->coord, 'getSessionMonthlyTrend', [$monthsBack]);
        if (is_array($val)) {
            return $this->normalizeMonthSeries($val, $monthsBack);
        }

        // Jika belum ada, tampilkan kosong (tidak fatal)
        return $this->emptyMonthSeries($monthsBack);
    }

    protected function tryGetMonthlyAssessments(int $monthsBack): array
    {
        $val = $this->safeCall($this->assessmentSvc, 'getMonthlyTrend', [$monthsBack]);
        if (is_array($val)) {
            return $this->normalizeMonthSeries($val, $monthsBack);
        }

        // Fallback via results
        if (!$this->assessmentResultModel) {
            return $this->emptyMonthSeries($monthsBack);
        }

        try {
            $labels = $this->monthsLabel($monthsBack);
            $db     = $this->assessmentResultModel->builder();

            $rows = $db->select("DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total", false)
                ->where('deleted_at', null)
                ->groupBy('ym')
                ->orderBy('ym', 'ASC')
                ->get()
                ->getResultArray();

            return $this->mapMonthRowsToSeries($labels, $rows);
        } catch (\Throwable $e) {
            log_message('error', 'monthly assessments error: ' . $e->getMessage());
        }

        return $this->emptyMonthSeries($monthsBack);
    }

    protected function emptyMonthSeries(int $monthsBack): array
    {
        return [
            'labels' => $this->monthsLabel($monthsBack),
            'data'   => array_fill(0, $monthsBack, 0),
        ];
    }

    protected function normalizeMonthSeries(array $rows, int $monthsBack): array
    {
        // Terima input: [['ym'=>'2025-08','total'=>12], ...] atau ['labels'=>[], 'data'=>[]]
        if (isset($rows['labels'], $rows['data'])) {
            return [
                'labels' => array_values($rows['labels']),
                'data'   => array_map('intval', $rows['data']),
            ];
        }

        $labels = $this->monthsLabel($monthsBack);
        return $this->mapMonthRowsToSeries($labels, $rows);
    }

    protected function mapMonthRowsToSeries(array $labels, array $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            $ym = $r['ym'] ?? ($r['month'] ?? null);
            if ($ym === null) {
                continue;
            }
            $map[$ym] = (int) ($r['total'] ?? 0);
        }

        $data = [];
        foreach ($labels as $ym) {
            $data[] = (int) ($map[$ym] ?? 0);
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /* ---------- Top list ---------- */

        protected function tryGetTopStudentsByViolations(int $limit = 5): array
    {
        // Coba dulu kalau ViolationService sudah punya
        $val = $this->safeCall($this->violationSvc, 'getTopStudents', [$limit]);
        if (is_array($val) && $val) {
            return $val;
        }

        if (!$this->violationModel) {
            return [];
        }

        try {
        // Pakai alias supaya aman (termasuk jika DB pakai prefix)
        $table = method_exists($this->violationModel, 'getTable')
            ? $this->violationModel->getTable()
            : 'violations';

        $db = $this->violationModel->builder($table . ' v');

        $rows = $db
            ->select('v.student_id, u.full_name AS student_name, c.class_name, COUNT(*) AS total', false)
            ->join('students s', 's.id = v.student_id', 'left')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('v.deleted_at', null)
            ->where('s.deleted_at', null)
            ->where('u.deleted_at', null)
            // opsional: kalau mau abaikan yang "Dibatalkan"
            ->whereIn('v.status', ['Dilaporkan', 'Dalam Proses', 'Selesai'])
            ->groupBy('v.student_id, u.full_name, c.class_name')
            ->orderBy('total', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return $rows;
    } catch (\Throwable $e) {
        log_message('error', 'top students error: ' . $e->getMessage());
    }

        return [];
    }

    protected function tryGetTopCounselorsBySessions(int $limit = 5): array
    {
        // Banyak proyek menaruh ini di CoordinatorService atau ReportService
        $val = $this->safeCall($this->coord, 'getTopCounselorsBySessions', [$limit]);
        if (is_array($val) && $val) {
            return $val;
        }

        $val = $this->safeCall($this->reportSvc, 'getTopCounselorsBySessions', [$limit]);
        if (is_array($val) && $val) {
            return $val;
        }

        // Jika belum ada model sesi, kita lewati (tidak fatal)
        return [];
    }

    /* ---------- Asesmen completion ---------- */

    protected function tryGetAssessmentCompletionTop(int $limit = 5): array
    {
        $val = $this->safeCall($this->assessmentSvc, 'getCompletionByAssessment', [$limit]);
        if (is_array($val) && $val) {
            return $val;
        }

        if (!$this->assessmentResultModel) {
            return [];
        }

        try {
            $builder = $this->assessmentResultModel->builder()
                ->select('assessment_id, COUNT(*) AS filled', false)
                ->where('deleted_at', null); // abaikan soft delete

            // Gunakan daftar status yang menghitung kuota percobaan
            if (defined(AssessmentResultModel::class . '::QUOTA_STATUSES')) {
                $builder->whereIn('status', AssessmentResultModel::QUOTA_STATUSES);
            } else {
                // fallback aman kalau constant tidak ada
                $builder->whereIn('status', ['Completed', 'Graded']);
            }

            $rows = $builder
                ->groupBy('assessment_id')
                ->orderBy('filled', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            // Tambahkan judul asesmen
            if ($this->assessmentModel && $rows) {
                $ids    = array_column($rows, 'assessment_id');
                $titles = $this->assessmentModel
                    ->where('deleted_at', null)     // abaikan asesmen yang di-soft delete
                    ->whereIn('id', $ids)
                    ->findAll();

                $titleMap = [];
                foreach ($titles as $t) {
                    $titleMap[$t['id']] = $t['title'] ?? ('Asesmen #' . $t['id']);
                }

                foreach ($rows as &$r) {
                    $r['title'] = $titleMap[$r['assessment_id']] ?? ('Asesmen #' . $r['assessment_id']);
                }
                unset($r);
            }

            return $rows;
        } catch (\Throwable $e) {
            log_message('error', 'assessment completion error: ' . $e->getMessage());
        }

        return [];
    }
}
