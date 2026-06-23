<?php

/**
 * File Path: app/Controllers/Counselor/DashboardController.php
 *
 * Guru BK • Dashboard
 * Tata letak patokan Admin + NNG: welcome + 3 shortcut, 5 kartu kecil
 * (Catatan Kegiatan, Jadwal Mendatang, Konsultasi belum diproses, Tugas Baru,
 * Pesan Masuk), bar+line chart (lingkup binaan), lalu tabel Jadwal/Kegiatan BK
 * Mendatang + Aktivitas Terbaru. Boleh diakses Guru BK & Koordinator.
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Services\DashboardService;
use App\Services\BkServiceService;

class DashboardController extends BaseController
{
    protected DashboardService $dash;
    protected BkServiceService $bk;
    protected $db;

    public function __construct()
    {
        helper(['auth', 'url']);
        $this->dash = new DashboardService();
        $this->bk   = new BkServiceService();
        $this->db   = \Config\Database::connect();
    }

    public function index()
    {
        if (! $this->isLoggedInSafe()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (! $this->isGuruBkSafe() && ! $this->isKoordinatorSafe()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $userId = $this->authIdSafe();
        if ($userId <= 0) {
            return redirect()->to('/login')->with('error', 'Sesi login tidak valid. Silakan login ulang.');
        }
        $role = 'guru-bk';

        $activeAcademic = $this->getActiveAcademicYearInfo();
        $currentUser    = function_exists('auth_user') ? (auth_user() ?? []) : [];

        $upcoming = $this->flattenSchedule($this->bk->scheduleByType($role, $userId, 'upcoming'), 6);

        $cards = [
            [
                'label' => 'Catatan Kegiatan', 'value' => $this->dash->catatanKegiatanCount($role, $userId),
                'icon' => 'mdi mdi-clipboard-text-outline', 'color' => 'primary',
                'url' => base_url('counselor/reports'), 'link_text' => 'Layanan & asesmen saya',
            ],
            [
                'label' => 'Jadwal Mendatang', 'value' => $this->bk->upcomingScheduleCount($role, $userId),
                'icon' => 'mdi mdi-calendar-clock', 'color' => 'success',
                'url' => base_url('counselor/counseling'), 'link_text' => 'Kegiatan BK akan datang',
            ],
            [
                'label' => 'Konsultasi Perlu Ditinjau', 'value' => $this->dash->complaintsPendingReview($role, $userId),
                'icon' => 'mdi mdi-message-alert-outline', 'color' => 'danger',
                'url' => base_url('counselor/consultations'), 'link_text' => 'Tinjau Konsultasi',
            ],
            [
                'label' => 'Tugas Baru', 'value' => $this->dash->newAssignmentsForCounselor($userId),
                'icon' => 'mdi mdi-account-arrow-right-outline', 'color' => 'warning',
                'url' => base_url('counselor/assignments'), 'link_text' => 'Dari Koordinator BK',
            ],
            [
                'label' => 'Pesan Masuk', 'value' => $this->dash->unreadMessages($userId),
                'icon' => 'mdi mdi-email-outline', 'color' => 'info',
                'url' => base_url('counselor/messages'), 'link_text' => 'Buka Pesan',
            ],
        ];

        $data = [
            'pageTitle'      => 'Dashboard Guru BK',
            'currentUser'    => $currentUser,
            'activeAcademic' => $activeAcademic,
            'welcome' => [
                'name' => $currentUser['full_name'] ?? 'Guru BK',
                'role_label' => 'Guru BK',
                'ay' => $activeAcademic['year'] ?? '',
                'sem' => $activeAcademic['semester'] ?? '',
                'desc' => 'Kelola layanan BK, konsultasi, penugasan, dan tindak lanjut siswa binaan.',
                'shortcuts' => [
                    ['label' => 'Lihat Laporan', 'url' => base_url('counselor/reports'), 'icon' => 'mdi-file-chart'],
                    ['label' => 'Konseling', 'url' => base_url('counselor/counseling'), 'icon' => 'mdi-account-heart-outline'],
                    ['label' => 'Konsultasi & Pengaduan', 'url' => base_url('counselor/consultations'), 'icon' => 'mdi-message-alert-outline'],
                ],
            ],
            'cards'            => $cards,
            'featureCounts'    => $this->dash->featureCounts($role, $userId),
            'monthlyTrend'     => $this->dash->monthlyTrend($role, $userId),
            'upcoming'         => $upcoming,
            'recentActivities' => $this->dash->recentActivities($role, $userId, 8),
        ];

        return view('counselor/dashboard', $data);
    }

    /** Ratakan hasil scheduleByType [type=>rows] menjadi satu daftar terurut. */
    private function flattenSchedule(array $byType, int $limit = 6): array
    {
        $flat = [];
        foreach ($byType as $rows) {
            foreach ($rows as $r) {
                $flat[] = $r;
            }
        }
        usort($flat, static function ($a, $b) {
            $da = $a['scheduled_at'] ?? $a['held_at'] ?? $a['created_at'] ?? '';
            $db = $b['scheduled_at'] ?? $b['held_at'] ?? $b['created_at'] ?? '';
            return strcmp((string) $da, (string) $db);
        });
        return array_slice($flat, 0, $limit);
    }

    protected function getActiveAcademicYearInfo(): array
    {
        try {
            if (! $this->db->tableExists('academic_years')) {
                return [];
            }
            $row = $this->db->table('academic_years')
                ->where('is_active', 1)->where('deleted_at', null)
                ->orderBy('id', 'DESC')->get(1)->getRowArray();
            if (! $row) {
                return [];
            }
            return [
                'year'     => (string) ($row['year_name'] ?? ''),
                'semester' => (string) ($row['semester'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ========================== Safe auth helpers ========================== */

    private function isLoggedInSafe(): bool
    {
        if (function_exists('is_logged_in')) {
            return (bool) is_logged_in();
        }
        return ! empty(session('user_id') ?? session('id'));
    }

    private function roleIdSafe(): int
    {
        return (int) (session('role_id') ?? 0);
    }

    private function isGuruBkSafe(): bool
    {
        if (function_exists('is_guru_bk')) {
            return (bool) is_guru_bk();
        }
        return $this->roleIdSafe() === 3;
    }

    private function isKoordinatorSafe(): bool
    {
        if (function_exists('is_koordinator')) {
            return (bool) is_koordinator();
        }
        return $this->roleIdSafe() === 2;
    }

    private function authIdSafe(): int
    {
        if (function_exists('auth_id')) {
            return (int) auth_id();
        }
        return (int) (session('user_id') ?? session('id') ?? 0);
    }
}
