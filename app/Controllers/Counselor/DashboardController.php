<?php

/**
 * File Path: app/Controllers/Counselor/DashboardController.php
 *
 * Counselor Dashboard Controller
 * Menampilkan dashboard untuk Guru BK dengan statistik, jadwal, dan data siswa binaan
 *
 * @package    SIB-K
 * @subpackage Controllers/Counselor
 * @category   Dashboard
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Services\CounselingService;
use App\Models\CounselingSessionModel;
use App\Models\StudentModel;
use App\Models\ViolationModel;
use CodeIgniter\I18n\Time;

class DashboardController extends BaseController
{
    /**
     * @var CounselingService
     */
    protected $counselingService;
    protected $sessionModel;
    protected $studentModel;
    protected $violationModel;
    protected $db;

    public function __construct()
    {
        // Optional: load helper agar fungsi auth/role tidak error jika belum di-autoload.
        if (function_exists('helper')) {
            try {
                helper(['auth', 'permission', 'app', 'url']);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $this->counselingService = new CounselingService();
        $this->sessionModel      = new CounselingSessionModel();
        $this->studentModel      = new StudentModel();
        $this->db                = \Config\Database::connect();

        // Optional - tidak wajib dipakai, tapi aman kalau model tersedia
        if (class_exists('\App\Models\ViolationModel')) {
            $this->violationModel = new ViolationModel();
        }
    }

    /**
     * Display counselor dashboard
     *
     * @return string|\CodeIgniter\HTTP\ResponseInterface
     */
    public function index()
    {
        // Check authentication and role
        if (!$this->isLoggedInSafe()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Guru BK dan Koordinator boleh akses
        if (!$this->isGuruBkSafe() && !$this->isKoordinatorSafe()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $counselorId = $this->authIdSafe();
        if ($counselorId <= 0) {
            return redirect()->to('/login')->with('error', 'Sesi login tidak valid. Silakan login ulang.');
        }

        // Get dashboard statistics
        $data['stats'] = $this->counselingService->getDashboardStats($counselorId);

        // Get today's sessions
        $data['todaySessions'] = $this->getTodaySessions($counselorId);

        // Get upcoming sessions (next 7 days)
        $data['upcomingSessions'] = $this->getUpcomingSessions($counselorId);

        // Get assigned students (siswa binaan)
        $data['assignedStudents'] = $this->getAssignedStudents($counselorId);

        // Get chart data for last 6 months
        $data['chartData'] = $this->getSessionChartData($counselorId);

        // Trend Pelanggaran/Kasus & Sanksi (6 bulan)
        $data['violationChartData'] = $this->getViolationChartData($counselorId);
        $data['sanctionChartData']  = $this->getSanctionChartData($counselorId);

        // Get recent activities
        $data['recentActivities'] = $this->getRecentActivities($counselorId);

        // Get pending sessions (need follow-up)
        $data['pendingSessions'] = $this->getPendingSessions($counselorId);

        // ===== Tambahan untuk Welcome Card (Tahun Ajaran + Semester + Kelas Binaan) =====
        $data['activeAcademic']   = $this->getActiveAcademicYear();
        $data['currentUser']      = $this->getCurrentUserRow($counselorId);
        $data['assignedClasses']  = $this->getAssignedClassesForCounselor(
            $counselorId,
            $data['activeAcademic']['id'] ?? null
        );

        // ===== Tambahan untuk Chart: Pelanggaran per Kategori =====
        $data['violationByCategory'] = $this->getViolationByCategoryForCounselor(
            $counselorId,
            $data['activeAcademic'] ?? null
        );
        $data['categoryRangeLabel'] = '6 bulan terakhir';


        // Page metadata
        $data['title']       = 'Dashboard Guru BK';
        $data['pageTitle']   = 'Dashboard Guru BK';
        $data['breadcrumbs'] = [
            ['title' => 'Home', 'url' => base_url('counselor/dashboard')],
            ['title' => 'Dashboard', 'url' => '#', 'active' => true],
        ];

        return view('counselor/dashboard', $data);
    }

    /**
     * Get today's counseling sessions
     *
     * @param int $counselorId
     * @return array
     */
    private function getTodaySessions($counselorId)
    {
        $today = date('Y-m-d');

        return $this->sessionModel
            ->select('counseling_sessions.*,
                      students.nisn, students.nis,
                      users.full_name as student_name,
                      classes.class_name')
            ->join('students', 'students.id = counseling_sessions.student_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = counseling_sessions.class_id', 'left')
            ->where('counseling_sessions.counselor_id', (int) $counselorId)
            ->where('counseling_sessions.session_date', $today)
            ->where('counseling_sessions.status !=', 'Dibatalkan')
            ->where('counseling_sessions.deleted_at', null)
            ->orderBy('counseling_sessions.session_time', 'ASC')
            ->findAll();
    }

    /**
     * Get upcoming counseling sessions (next 7 days)
     *
     * @param int $counselorId
     * @return array
     */
    private function getUpcomingSessions($counselorId)
    {
        $today    = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));

        return $this->sessionModel
            ->select('counseling_sessions.*,
                      students.nisn, students.nis,
                      users.full_name as student_name,
                      classes.class_name')
            ->join('students', 'students.id = counseling_sessions.student_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = counseling_sessions.class_id', 'left')
            ->where('counseling_sessions.counselor_id', (int) $counselorId)
            ->where('counseling_sessions.session_date >', $today)
            ->where('counseling_sessions.session_date <=', $nextWeek)
            ->where('counseling_sessions.status', 'Dijadwalkan')
            ->where('counseling_sessions.deleted_at', null)
            ->orderBy('counseling_sessions.session_date', 'ASC')
            ->orderBy('counseling_sessions.session_time', 'ASC')
            ->limit(10)
            ->findAll();
    }

    /**
     * Get assigned students (siswa binaan) for counselor
     *
     * @param int $counselorId
     * @return array
     */
    private function getAssignedStudents($counselorId)
    {
        $counselorId = (int) $counselorId;

        $students = $this->db->table('students')
            ->select('students.id, students.nisn, students.nis, students.total_violation_points,
                      users.full_name as student_name, users.email,
                      classes.class_name,
                      COUNT(DISTINCT counseling_sessions.id) as total_sessions')
            ->join('users', 'users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->join(
                'counseling_sessions',
                'counseling_sessions.student_id = students.id
                 AND counseling_sessions.counselor_id = ' . $counselorId . '
                 AND counseling_sessions.deleted_at IS NULL',
                'left',
                false
            )
            ->where('students.status', 'Aktif')
            ->where('students.deleted_at', null) // ✅ tambah: jangan ambil siswa soft-deleted
            ->where('users.deleted_at', null)    // ✅ tambah: jangan ambil user soft-deleted
            ->groupBy('students.id, students.nisn, students.nis, students.total_violation_points,
                       users.full_name, users.email, classes.class_name')
            ->having('COUNT(DISTINCT counseling_sessions.id) >', 0)
            ->orderBy('total_sessions', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return $students;
    }

    /**
     * Get chart data for session statistics (last 6 months)
     *
     * Perbaikan penting:
     * - Jangan pakai countAllResults(false) berulang karena kondisi where/like bisa menumpuk.
     * - Diganti 1 query agregasi (lebih cepat dan akurat).
     *
     * @param int $counselorId
     * @return array
     */
    private function getSessionChartData($counselorId)
    {
        $chartData = [
            'labels'     => [],
            'individual' => [],
            'group'      => [],
            'class'      => [],
        ];

        $monthIndex = []; // 'YYYY-MM' => index
        for ($i = 5; $i >= 0; $i--) {
            $ym        = date('Y-m', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime("-{$i} months"));

            $monthIndex[$ym]            = count($chartData['labels']);
            $chartData['labels'][]      = $monthName;
            $chartData['individual'][]  = 0;
            $chartData['group'][]       = 0;
            $chartData['class'][]       = 0;
        }

        $startDate = date('Y-m-01', strtotime('-5 months'));
        $endDate   = date('Y-m-t'); // akhir bulan ini

        try {
            $rows = $this->db->table('counseling_sessions')
                ->select("DATE_FORMAT(session_date, '%Y-%m') AS ym, session_type, COUNT(*) AS total")
                ->where('counselor_id', (int) $counselorId)
                ->where('status !=', 'Dibatalkan')
                ->where('deleted_at', null)
                ->where('session_date >=', $startDate)
                ->where('session_date <=', $endDate)
                ->groupBy('ym, session_type')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $ym   = $r['ym'] ?? null;
                $type = $r['session_type'] ?? null;
                $cnt  = (int) ($r['total'] ?? 0);

                if (!$ym || !isset($monthIndex[$ym])) {
                    continue;
                }

                $idx = $monthIndex[$ym];

                if ($type === 'Individu') {
                    $chartData['individual'][$idx] = $cnt;
                } elseif ($type === 'Kelompok') {
                    $chartData['group'][$idx] = $cnt;
                } elseif ($type === 'Klasikal') {
                    $chartData['class'][$idx] = $cnt;
                }
            }
        } catch (\Throwable $e) {
            // Optional: biar dashboard tidak fatal kalau query gagal
            log_message('error', 'Dashboard getSessionChartData error: ' . $e->getMessage());
        }

        return $chartData;
    }

    /**
     * Get recent activities
     *
     * Perbaikan:
     * - Jangan hanya status Selesai (bisa bikin kosong).
     * - Tampilkan sesi terbaru apapun statusnya (kecuali soft-deleted),
     *   lalu bedakan icon & text berdasarkan status.
     *
     * @param int $counselorId
     * @return array
     */
    private function getRecentActivities($counselorId)
    {
        $recentSessions = $this->db->table('counseling_sessions')
            ->select('counseling_sessions.id, counseling_sessions.topic,
                      counseling_sessions.session_date, counseling_sessions.session_type,
                      counseling_sessions.status,
                      counseling_sessions.created_at, counseling_sessions.updated_at,
                      students.nisn,
                      users.full_name as student_name,
                      classes.class_name')
            ->join('students', 'students.id = counseling_sessions.student_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = counseling_sessions.class_id', 'left')
            ->where('counseling_sessions.counselor_id', (int) $counselorId)
            ->where('counseling_sessions.deleted_at', null)
            ->orderBy('counseling_sessions.updated_at', 'DESC')
            ->orderBy('counseling_sessions.created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $activities = [];

        foreach ($recentSessions as $session) {
            $status = $session['status'] ?? 'Dijadwalkan';

            if ($status === 'Selesai') {
                $icon  = 'mdi-check-circle';
                $color = 'success';
                $title = 'Sesi Konseling Selesai';
            } elseif ($status === 'Dibatalkan') {
                $icon  = 'mdi-close-circle';
                $color = 'danger';
                $title = 'Sesi Konseling Dibatalkan';
            } else {
                $icon  = 'mdi-calendar-clock';
                $color = 'info';
                $title = 'Sesi Konseling Dijadwalkan';
            }

            $who = $session['student_name']
                ? $session['student_name']
                : (($session['class_name'] ?? null) ? ('Kelas ' . $session['class_name']) : 'Kelompok/Kelas');

            $timeSource = $session['updated_at'] ?? $session['created_at'] ?? null;

            $activities[] = [
                'type'        => 'session',
                'icon'        => $icon,
                'color'       => $color,
                'title'       => $title,
                'description' => 'Topik "' . ($session['topic'] ?? '-') . '" dengan ' . $who,
                'time'        => $this->formatTimeAgoSafe($timeSource),
                'url'         => base_url('counselor/sessions/detail/' . $session['id']),
            ];
        }

        return $activities;
    }

    /**
     * Get pending sessions that need follow-up
     *
     * Perbaikan logika:
     * - "Perlu follow-up" biasanya = sesi selesai tapi follow_up_plan masih kosong.
     *
     * @param int $counselorId
     * @return array
     */
    private function getPendingSessions($counselorId)
    {
        return $this->db->table('counseling_sessions')
            ->select('counseling_sessions.*,
                      students.nisn,
                      users.full_name as student_name')
            ->join('students', 'students.id = counseling_sessions.student_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->where('counseling_sessions.counselor_id', (int) $counselorId)
            ->where('counseling_sessions.status', 'Selesai')
            ->groupStart()
                ->where('counseling_sessions.follow_up_plan', null)
                ->orWhere('counseling_sessions.follow_up_plan', '')
            ->groupEnd()
            ->where('counseling_sessions.deleted_at', null)
            ->orderBy('counseling_sessions.session_date', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }

    /**
     * AJAX: Get quick stats (for auto-refresh)
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getQuickStats()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request',
            ]);
        }

        $counselorId = $this->authIdSafe();
        $stats       = $this->counselingService->getDashboardStats($counselorId);

        return $this->response->setJSON([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * ==========================
     * Safe helper wrappers
     * ==========================
     */

    private function isLoggedInSafe(): bool
    {
        if (function_exists('is_logged_in')) {
            return (bool) is_logged_in();
        }

        $s  = session();
        $id = $s->get('user_id') ?? $s->get('id') ?? null;

        if (!$id) {
            $u = $s->get('user');
            if (is_array($u)) {
                $id = $u['id'] ?? $u['user_id'] ?? null;
            } elseif (is_object($u)) {
                $id = $u->id ?? $u->user_id ?? null;
            }
        }

        return !empty($id);
    }

    private function roleIdSafe(): int
    {
        $s = session();

        $rid = $s->get('role_id');
        if ($rid) return (int) $rid;

        $u = $s->get('user');
        if (is_array($u)) {
            return (int) ($u['role_id'] ?? 0);
        }
        if (is_object($u)) {
            return (int) ($u->role_id ?? 0);
        }

        return 0;
    }

    private function roleNameSafe(): string
    {
        $s = session();

        $role = $s->get('role') ?? $s->get('role_name') ?? '';
        if (is_string($role) && $role !== '') return $role;

        $u = $s->get('user');
        if (is_array($u)) {
            return (string) ($u['role'] ?? $u['role_name'] ?? '');
        }
        if (is_object($u)) {
            return (string) ($u->role ?? $u->role_name ?? '');
        }

        return '';
    }

    private function isGuruBkSafe(): bool
    {
        if (function_exists('is_guru_bk')) {
            return (bool) is_guru_bk();
        }

        // ✅ fallback berbasis role_id dari database: 3 = Guru BK
        if ($this->roleIdSafe() === 3) return true;

        $role = strtolower(trim($this->roleNameSafe()));
        return in_array($role, ['guru bk', 'guru_bk', 'gurubk', 'counselor', 'guru-bk'], true);
    }

    private function isKoordinatorSafe(): bool
    {
        if (function_exists('is_koordinator')) {
            return (bool) is_koordinator();
        }

        // ✅ fallback berbasis role_id dari database: 2 = Koordinator BK
        if ($this->roleIdSafe() === 2) return true;

        $role = strtolower(trim($this->roleNameSafe()));
        return in_array($role, ['koordinator bk', 'koordinator_bk', 'koordinator', 'koordinator-bk'], true);
    }

    private function authIdSafe(): int
    {
        if (function_exists('auth_id')) {
            return (int) auth_id();
        }

        $s  = session();
        $id = $s->get('user_id') ?? $s->get('id') ?? null;

        if (!$id) {
            $u = $s->get('user');
            if (is_array($u)) {
                $id = $u['id'] ?? $u['user_id'] ?? null;
            } elseif (is_object($u)) {
                $id = $u->id ?? $u->user_id ?? null;
            }
        }

        return (int) ($id ?? 0);
    }

    private function formatTimeAgoSafe(?string $datetime): string
    {
        if (!$datetime) {
            return '-';
        }

        if (function_exists('time_ago')) {
            try {
                return (string) time_ago($datetime);
            } catch (\Throwable $e) {
                // fallback ke humanize
            }
        }

        try {
            return Time::parse($datetime)->humanize();
        } catch (\Throwable $e) {
            $ts = strtotime($datetime);
            return $ts ? date('d M Y H:i', $ts) : (string) $datetime;
        }
    }

    /**
     * Chart data: Tren Pelanggaran/Kasus (6 bulan terakhir)
     * Sumber: table `violations`
     *
     * Rule akses:
     * - dihitung jika handled_by = counselorId ATAU reported_by = counselorId
     */
    private function getViolationChartData($counselorId): array
    {
        $chartData = [
            'labels'       => [],
            'reported'     => [],
            'in_process'   => [],
            'completed'    => [],
        ];

        $monthIndex = [];
        for ($i = 5; $i >= 0; $i--) {
            $ym        = date('Y-m', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime("-{$i} months"));

            $monthIndex[$ym]           = count($chartData['labels']);
            $chartData['labels'][]     = $monthName;
            $chartData['reported'][]   = 0;
            $chartData['in_process'][] = 0;
            $chartData['completed'][]  = 0;
        }

        $startDate = date('Y-m-01', strtotime('-5 months'));
        $endDate   = date('Y-m-t');

        try {
            $rows = $this->db->table('violations v')
                ->select("DATE_FORMAT(v.violation_date, '%Y-%m') AS ym, v.status, COUNT(*) AS total")
                ->groupStart()
                    ->where('v.handled_by', (int) $counselorId)
                    ->orWhere('v.reported_by', (int) $counselorId)
                ->groupEnd()
                ->where('v.deleted_at', null)
                ->where('v.violation_date >=', $startDate)
                ->where('v.violation_date <=', $endDate)
                ->groupBy('ym, v.status')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $ym  = $r['ym'] ?? null;
                $st  = $r['status'] ?? null;
                $cnt = (int) ($r['total'] ?? 0);

                if (!$ym || !isset($monthIndex[$ym])) continue;
                $idx = $monthIndex[$ym];

                if ($st === 'Dilaporkan') {
                    $chartData['reported'][$idx] = $cnt;
                } elseif ($st === 'Dalam Proses') {
                    $chartData['in_process'][$idx] = $cnt;
                } elseif ($st === 'Selesai') {
                    $chartData['completed'][$idx] = $cnt;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard getViolationChartData error: ' . $e->getMessage());
        }

        return $chartData;
    }

    /**
     * Chart data: Tren Sanksi (6 bulan terakhir)
     * Sumber: table `sanctions` JOIN `violations`
     *
     * Agar relevan untuk Guru BK:
     * - dihitung jika pelanggarannya handled_by = counselorId ATAU reported_by = counselorId
     */
    private function getSanctionChartData($counselorId): array
    {
        $chartData = [
            'labels'    => [],
            'scheduled' => [],
            'ongoing'   => [],
            'completed' => [],
        ];

        $monthIndex = [];
        for ($i = 5; $i >= 0; $i--) {
            $ym        = date('Y-m', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime("-{$i} months"));

            $monthIndex[$ym]          = count($chartData['labels']);
            $chartData['labels'][]    = $monthName;
            $chartData['scheduled'][] = 0;
            $chartData['ongoing'][]   = 0;
            $chartData['completed'][] = 0;
        }

        $startDate = date('Y-m-01', strtotime('-5 months'));
        $endDate   = date('Y-m-t');

        try {
            $rows = $this->db->table('sanctions s')
                ->select("DATE_FORMAT(s.sanction_date, '%Y-%m') AS ym, s.status, COUNT(*) AS total")
                ->join('violations v', 'v.id = s.violation_id', 'left')
                ->groupStart()
                    ->where('v.handled_by', (int) $counselorId)
                    ->orWhere('v.reported_by', (int) $counselorId)
                ->groupEnd()
                ->where('s.deleted_at', null)
                ->where('s.sanction_date >=', $startDate)
                ->where('s.sanction_date <=', $endDate)
                ->groupBy('ym, s.status')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $ym  = $r['ym'] ?? null;
                $st  = $r['status'] ?? null;
                $cnt = (int) ($r['total'] ?? 0);

                if (!$ym || !isset($monthIndex[$ym])) continue;
                $idx = $monthIndex[$ym];

                if ($st === 'Dijadwalkan') {
                    $chartData['scheduled'][$idx] = $cnt;
                } elseif ($st === 'Sedang Berjalan') {
                    $chartData['ongoing'][$idx] = $cnt;
                } elseif ($st === 'Selesai') {
                    $chartData['completed'][$idx] = $cnt;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard getSanctionChartData error: ' . $e->getMessage());
        }

        return $chartData;
    }

    /**
     * Ambil Tahun Ajaran yang sedang aktif (untuk ditampilkan di Welcome Card)
     */
    private function getActiveAcademicYear(): ?array
    {
        try {
            $row = $this->db->table('academic_years')
                ->select('id, year_name, semester, start_date, end_date')
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ambil data user login (minimal untuk full_name)
     */
    private function getCurrentUserRow(int $userId): array
    {
        try {
            $row = $this->db->table('users')
                ->select('id, full_name')
                ->where('id', (int) $userId)
                ->get(1)
                ->getRowArray();

            if ($row) {
                return $row;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // fallback dari session jika query gagal
        return [
            'id'        => $userId,
            'full_name' => session('full_name') ?? session('name') ?? 'Guru BK',
        ];
    }

    /**
     * Ambil daftar kelas binaan Guru BK (classes.counselor_id)
     * Opsional: difilter hanya untuk Tahun Ajaran aktif.
     */
    private function getAssignedClassesForCounselor(int $counselorId, ?int $academicYearId = null): array
    {
        try {
            $builder = $this->db->table('classes c')
                ->select('c.id, c.class_name, c.grade_level, c.major, c.academic_year_id')
                ->where('c.deleted_at', null)
                ->where('c.is_active', 1)
                ->where('c.counselor_id', (int) $counselorId);

            if (!empty($academicYearId)) {
                $builder->where('c.academic_year_id', (int) $academicYearId);
            }

            return $builder
                ->orderBy('c.grade_level', 'ASC')
                ->orderBy('c.class_name', 'ASC')
                ->get()
                ->getResultArray() ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Data doughnut chart Pelanggaran per Kategori (scope Guru BK)
     * - Konsisten dengan chart lain: ikut yang handled_by atau reported_by (Guru BK tsb)
     * - Difilter periode Tahun Ajaran aktif (violation_date BETWEEN start_date AND end_date) jika tersedia
     */
    private function getViolationByCategoryForCounselor(int $counselorId, ?array $activeAcademic = null): array
    {
        try {
            $monthsBack = 6;
            $start = Time::now()
                ->subMonths(max(0, $monthsBack - 1))
                ->format('Y-m-01');

            // Ambil pelanggaran siswa yang kelasnya dibina oleh Guru BK ini
            $builder = $this->db->table('violations v')
                ->select('vc.category_name, COUNT(v.id) as count')
                ->join('violation_categories vc', 'vc.id = v.category_id', 'inner')
                ->join('students s', 's.id = v.student_id', 'inner')
                ->join('classes c', 'c.id = s.class_id', 'inner')
                ->where('v.deleted_at', null)
                ->where('vc.deleted_at', null)
                ->where('c.deleted_at', null)
                ->where('c.counselor_id', (int) $counselorId)
                ->where('v.violation_date >=', $start)
                ->groupBy('v.category_id')
                ->orderBy('count', 'DESC')
                ->limit(5);

            return $builder->get()->getResultArray() ?? [];
        } catch (\Throwable $e) {
            log_message('error', '[COUNSELOR DASHBOARD] getViolationByCategoryForCounselor error: ' . $e->getMessage());
            return [];
        }
    }
}
