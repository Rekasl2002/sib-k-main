<?php
/**
 * File Path: app/Controllers/Koordinator/DashboardController.php
 *
 * Koordinator BK • Dashboard
 * Merangkum statistik sekolah: siswa, staf, sesi konseling, asesmen, laporan, notifikasi.
 * Memakai service yang ada; fallback ke model jika method tidak tersedia.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\Koordinator\BaseKoordinatorController;
use CodeIgniter\I18n\Time;

// Services
use App\Services\CoordinatorService;
use App\Services\AssessmentService;
use App\Services\ReportService;
use App\Services\StudentService;
use App\Services\UserService;

// Models (fallback)
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\AssessmentModel;
use App\Models\AssessmentResultModel;

class DashboardController extends BaseKoordinatorController
{
    protected CoordinatorService $coord;
    protected ?AssessmentService $assessmentSvc = null;
    protected ?ReportService $reportSvc         = null;
    protected ?StudentService $studentSvc       = null;
    protected ?UserService $userSvc             = null;

    // Fallback models
    protected ?StudentModel $studentModel               = null;
    protected ?UserModel $userModel                     = null;
    protected ?RoleModel $roleModel                     = null;
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
        $this->reportSvc     = class_exists(ReportService::class)     ? new ReportService()     : null;
        $this->studentSvc    = class_exists(StudentService::class)    ? new StudentService()    : null;
        $this->userSvc       = class_exists(UserService::class)       ? new UserService()       : null;

        // Models fallback
        $this->studentModel          = class_exists(StudentModel::class)          ? new StudentModel()          : null;
        $this->userModel             = class_exists(UserModel::class)             ? new UserModel()             : null;
        $this->roleModel             = class_exists(RoleModel::class)             ? new RoleModel()             : null;
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

        $topCounselors = $this->tryGetTopCounselorsBySessions(5);

        // ---------- RINGKASAN ASESMEN ----------
        $assessmentCompletion = $this->tryGetAssessmentCompletionTop(5);

        // ---------- AKTIVITAS TERBARU ----------
        $recentActivities = $this->safeCall($this->coord, 'getRecentActivities', [10]) ?? [];

        helper('auth');
        $currentUser = function_exists('auth_user') ? (auth_user() ?? []) : [];
        $activeAcademic = $this->getActiveAcademicYearInfo();

        $data = [
            'pageTitle'            => 'Dashboard Koordinator BK',
            'currentUser'          => $currentUser,
            'activeAcademic'       => $activeAcademic,
            'quick'                => $quickStats,
            'topCounselors'        => $topCounselors,
            'assessmentCompletion' => $assessmentCompletion,
            'recentActivities'     => $recentActivities,
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
