<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\StudentModel;
use App\Models\CounselingSessionModel;
use App\Models\ViolationModel;
use App\Models\RoleModel;
use App\Models\ClassModel;
use App\Models\NotificationModel;
use CodeIgniter\Database\BaseConnection;

class CoordinatorService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Statistik cepat untuk kartu-kartu utama di Dashboard Koordinator.
     *
     * Mengembalikan key:
     * - totalStudents
     * - activeStudents
     * - inactiveStudents
     * - totalStaff
     * - totalCounselors
     * - totalHomerooms
     * - activeCases
     * - closedCases
     * - totalSessions
     * - todaySessions
     * - upcomingSessions
     * - unreadNotifications
     *
     * Seluruh hitungan mengabaikan data yang di-soft-delete (deleted_at IS NULL).
     */
    public function getQuickStats(): array
    {
        $studentModel   = new StudentModel();
        $sessionModel   = new CounselingSessionModel();
        $violationModel = new ViolationModel();
        $userModel      = new UserModel();
        $roleModel      = new RoleModel();

        // --- Siswa (non deleted) ---
        $totalStudents = $studentModel
            ->where('deleted_at', null)
            ->countAllResults();

        $activeStudents = $studentModel
            ->where('deleted_at', null)
            ->where('status', 'Aktif')
            ->countAllResults();

        $inactiveStudents = $studentModel
            ->where('deleted_at', null)
            ->where('status !=', 'Aktif')
            ->countAllResults();

        // --- Guru BK & Wali Kelas (non deleted) ---
        $roles   = $roleModel->whereIn('role_name', ['Guru BK', 'Wali Kelas'])->findAll();
        $roleIds = array_column($roles, 'id');

        $totalStaff      = 0;
        $totalCounselors = 0;
        $totalHomerooms  = 0;

        if (!empty($roleIds)) {
            $totalStaff = $userModel
                ->whereIn('role_id', $roleIds)
                ->where('deleted_at', null)
                ->countAllResults();

            $counselorRoleId = null;
            $homeroomRoleId  = null;

            foreach ($roles as $r) {
                $name = $r['role_name'] ?? '';
                if ($name === 'Guru BK') {
                    $counselorRoleId = $r['id'] ?? null;
                } elseif ($name === 'Wali Kelas') {
                    $homeroomRoleId = $r['id'] ?? null;
                }
            }

            if ($counselorRoleId) {
                $totalCounselors = $userModel
                    ->where('role_id', $counselorRoleId)
                    ->where('deleted_at', null)
                    ->countAllResults();
            }

            if ($homeroomRoleId) {
                $totalHomerooms = $userModel
                    ->where('role_id', $homeroomRoleId)
                    ->where('deleted_at', null)
                    ->countAllResults();
            }
        }

        // --- Kasus pelanggaran (non deleted) ---
        $activeCases = $violationModel
            ->where('deleted_at', null)
            ->whereIn('status', ['Dilaporkan', 'Dalam Proses'])
            ->countAllResults();

        $closedCases = $violationModel
            ->where('deleted_at', null)
            ->whereIn('status', ['Selesai', 'Ditutup'])
            ->countAllResults();

        // --- Sesi konseling (non deleted) ---
        $today = date('Y-m-d');

        $totalSessions = $sessionModel
            ->where('deleted_at', null)
            ->countAllResults();

        $todaySessions = $sessionModel
            ->where('deleted_at', null)
            ->where('session_date', $today)
            ->countAllResults();

        $upcomingSessions = $sessionModel
            ->where('deleted_at', null)
            ->where('session_date >', $today)
            ->countAllResults();

        // --- Notifikasi (semua pengguna) yang belum dibaca, non deleted ---
        $unreadNotifications = 0;
        if (class_exists(NotificationModel::class)) {
            $notifModel = new NotificationModel();
            $unreadNotifications = $notifModel
                ->where('deleted_at', null)
                ->where('is_read', 0)
                ->countAllResults();
        }

        return [
            'totalStudents'       => (int) $totalStudents,
            'activeStudents'      => (int) $activeStudents,
            'inactiveStudents'    => (int) $inactiveStudents,
            'totalStaff'          => (int) $totalStaff,
            'totalCounselors'     => (int) $totalCounselors,
            'totalHomerooms'      => (int) $totalHomerooms,
            'activeCases'         => (int) $activeCases,
            'closedCases'         => (int) $closedCases,
            'totalSessions'       => (int) $totalSessions,
            'todaySessions'       => (int) $todaySessions,
            'upcomingSessions'    => (int) $upcomingSessions,
            'unreadNotifications' => (int) $unreadNotifications,
        ];
    }

    /**
     * Ringkasan pelanggaran per tingkat (Ringan/Sedang/Berat)
     * untuk kebutuhan chart di dashboard.
     *
     * Hanya menghitung data yang tidak di-soft-delete.
     */
    public function getViolationSummaryByLevel(): array
    {
        if (!method_exists($this->db, 'tableExists') || !$this->db->tableExists('violations')) {
            return [];
        }

        return $this->db->table('violations v')
            ->select('c.severity_level AS level, COUNT(*) AS total')
            ->join('violation_categories c', 'c.id = v.category_id', 'left')
            ->where('v.deleted_at', null)
            ->groupBy('c.severity_level')
            // Urutkan: Ringan -> Sedang -> Berat
            ->orderBy('FIELD(c.severity_level, "Ringan","Sedang","Berat")', '', false)
            ->get()
            ->getResultArray();
    }

    /**
     * Aktivitas terbaru lintas modul:
     * - Sesi konseling
     * - Pelanggaran
     * - Notifikasi
     *
     * Return: array of [type, created_at, message]
     * Semua sumber hanya mengambil data yang tidak di-soft-delete.
     */
    public function getRecentActivities(int $limit = 10): array
    {
        if (!method_exists($this->db, 'tableExists')) {
            return [];
        }

        $parts = [];

        if ($this->db->tableExists('counseling_sessions')) {
            $parts[] = "
                (
                    SELECT
                        'session' AS type,
                        session_date AS created_at,
                        CONCAT('Sesi konseling #', id) AS message
                    FROM counseling_sessions
                    WHERE deleted_at IS NULL
                )
            ";
        }

        if ($this->db->tableExists('violations')) {
            $parts[] = "
                (
                    SELECT
                        'violation' AS type,
                        violation_date AS created_at,
                        CONCAT('Pelanggaran #', id) AS message
                    FROM violations
                    WHERE deleted_at IS NULL
                )
            ";
        }

        if ($this->db->tableExists('notifications')) {
            $parts[] = "
                (
                    SELECT
                        'notification' AS type,
                        created_at,
                        title AS message
                    FROM notifications
                    WHERE deleted_at IS NULL
                )
            ";
        }

        if (empty($parts)) {
            return [];
        }

        $sql = implode(' UNION ALL ', $parts)
             . ' ORDER BY created_at DESC LIMIT ' . (int) $limit;

        return $this->db->query($sql)->getResultArray();
    }

    /**
     * Statistik sesi konseling per status (Terjadwal, Selesai, Dibatalkan, dll).
     * Mengabaikan data yang di-soft-delete.
     */
    public function getSessionStatsByStatus(): array
    {
        if (!method_exists($this->db, 'tableExists') || !$this->db->tableExists('counseling_sessions')) {
            return [];
        }

        return $this->db->table('counseling_sessions')
            ->select('status, COUNT(*) AS total')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Distribusi siswa per tingkat (grade_level) untuk heatmap / chart.
     * Mengabaikan siswa yang di-soft-delete.
     */
    public function getStudentDistributionByGrade(): array
    {
        if (
            !method_exists($this->db, 'tableExists') ||
            !$this->db->tableExists('students') ||
            !$this->db->tableExists('classes')
        ) {
            return [];
        }

        return $this->db->table('students s')
            ->select('c.grade_level, COUNT(*) AS total')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.deleted_at', null)
            ->groupBy('c.grade_level')
            ->orderBy('c.grade_level', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Ringkasan asesmen seluruh sekolah:
     * - total asesmen
     * - aktif
     * - published
     * - total hasil (assessment_results)
     *
     * Mengabaikan data asesmen & hasil yang di-soft-delete.
     */
    public function getAssessmentSummary(): array
    {
        if (!method_exists($this->db, 'tableExists')) {
            return [];
        }

        $summary = [
            'total'             => 0,
            'active'            => 0,
            'published'         => 0,
            'completed_results' => 0,
        ];

        if ($this->db->tableExists('assessments')) {
            $summary['total'] = (int) $this->db->table('assessments')
                ->where('deleted_at', null)
                ->countAllResults();

            $summary['active'] = (int) $this->db->table('assessments')
                ->where('deleted_at', null)
                ->where('is_active', 1)
                ->countAllResults();

            $summary['published'] = (int) $this->db->table('assessments')
                ->where('deleted_at', null)
                ->where('is_published', 1)
                ->countAllResults();
        }

        if ($this->db->tableExists('assessment_results')) {
            $summary['completed_results'] = (int) $this->db->table('assessment_results')
                ->where('deleted_at', null)
                ->countAllResults();
        }

        return $summary;
    }

    /**
     * Statistik kelas (proxy ke ClassModel::getStatistics()).
     * Implementasi ClassModel tetap dipakai apa adanya.
     */
    public function getClassStatistics(): array
    {
        if (!class_exists(ClassModel::class)) {
            return [];
        }

        $classModel = new ClassModel();
        if (!method_exists($classModel, 'getStatistics')) {
            return [];
        }

        return $classModel->getStatistics();
    }

    /**
     * Ringkasan menyeluruh untuk laporan sekolah:
     * - quick_stats
     * - violations_by_level
     * - session_by_status
     * - students_by_grade
     * - assessments
     * - class_statistics
     */
    public function getSchoolWideSummary(): array
    {
        return [
            'quick_stats'         => $this->getQuickStats(),
            'violations_by_level' => $this->getViolationSummaryByLevel(),
            'session_by_status'   => $this->getSessionStatsByStatus(),
            'students_by_grade'   => $this->getStudentDistributionByGrade(),
            'assessments'         => $this->getAssessmentSummary(),
            'class_statistics'    => $this->getClassStatistics(),
        ];
    }

    /**
     * Guru BK dengan jumlah sesi konseling terbanyak.
     *
     * @param int $limit
     * @return array
     *
     * Contoh hasil:
     * [
     *   [
     *     'counselor_id'   => 5,
     *     'counselor_name' => 'Bu Siti',
     *     'total'          => 12,
     *     'duration'       => 540,
     *     'class_names'    => 'X-IPA-1, X-IPA-2'
     *   ],
     *   ...
     * ]
     *
     * Hanya mempertimbangkan sesi yang tidak di-soft-delete
     * dan kelas aktif yang tidak di-soft-delete.
     */
    public function getTopCounselorsBySessions(int $limit = 5): array
    {
        if (!method_exists($this->db, 'tableExists') || !$this->db->tableExists('counseling_sessions')) {
            return [];
        }

        // Hitung sesi konseling per guru BK
        $rows = $this->db->table('counseling_sessions cs')
            ->select(
                'cs.counselor_id,
                 u.full_name AS counselor_name,
                 COUNT(DISTINCT cs.id) AS total,
                 COALESCE(SUM(cs.duration_minutes), 0) AS duration'
            )
            ->join('users u', 'u.id = cs.counselor_id', 'left')
            ->where('cs.deleted_at', null)
            ->groupBy('cs.counselor_id, u.full_name')
            ->orderBy('total', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        // Tambahkan informasi kelas binaan dari tabel classes
        if (empty($rows) || !$this->db->tableExists('classes')) {
            return $rows;
        }

        $ids = array_column($rows, 'counselor_id');
        if (empty($ids)) {
            return $rows;
        }

        $classRows = $this->db->table('classes')
            ->select(
                'counselor_id,
                 GROUP_CONCAT(class_name ORDER BY class_name SEPARATOR ", ") AS class_names'
            )
            ->whereIn('counselor_id', $ids)
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->groupBy('counselor_id')
            ->get()
            ->getResultArray();

        $classMap = [];
        foreach ($classRows as $cr) {
            $cid = $cr['counselor_id'] ?? null;
            if ($cid === null) {
                continue;
            }
            $classMap[$cid] = $cr['class_names'] ?? '';
        }

        foreach ($rows as &$r) {
            $cid = $r['counselor_id'] ?? null;
            $r['class_names'] = $classMap[$cid] ?? '';
        }
        unset($r);

        return $rows;
    }

    /**
     * Tren bulanan sesi konseling (untuk grafik Tren Layanan BK).
     * Output: [['ym' => '2025-11', 'total' => 3], ...]
     * Hanya mengambil sesi yang tidak di-soft-delete.
     */
    public function getSessionMonthlyTrend(int $monthsBack = 6): array
    {
        if (!method_exists($this->db, 'tableExists') || !$this->db->tableExists('counseling_sessions')) {
            return [];
        }

        return $this->db->table('counseling_sessions')
            ->select("DATE_FORMAT(session_date, '%Y-%m') AS ym, COUNT(*) AS total", false)
            ->where('deleted_at', null)
            ->groupBy('ym')
            ->orderBy('ym', 'ASC')
            ->get()
            ->getResultArray();
    }
}
