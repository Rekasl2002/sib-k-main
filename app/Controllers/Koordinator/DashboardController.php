<?php
/**
 * File Path: app/Controllers/Koordinator/DashboardController.php
 *
 * Koordinator BK • Dashboard
 * Tata letak mengikuti patokan Admin + riset Nielsen Norman Group:
 *   1) Welcome card + 3 shortcut, 2) 4 kartu kecil, 3) bar+line chart,
 *   4) tabel detail (Guru BK catatan terbanyak + aktivitas terbaru).
 * Data agregat dipusatkan di DashboardService.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\Koordinator\BaseKoordinatorController;
use App\Services\DashboardService;

class DashboardController extends BaseKoordinatorController
{
    protected DashboardService $dash;

    public function __construct()
    {
        $this->dash = new DashboardService();
    }

    public function index()
    {
        if (method_exists($this, 'requireKoordinator')) {
            $this->requireKoordinator();
        }

        helper('auth');
        $currentUser = function_exists('auth_user') ? (auth_user() ?? []) : [];
        $userId      = (int) (session('user_id') ?? session('id') ?? 0);
        $role        = 'koordinator-bk';

        $activeAcademic = $this->getActiveAcademicYearInfo();

        $cards = [
            [
                'label' => 'Catatan Kegiatan', 'value' => $this->dash->catatanKegiatanCount($role, $userId),
                'icon' => 'mdi mdi-clipboard-text-outline', 'color' => 'primary',
                'url' => base_url('koordinator/reports'), 'link_text' => 'Semua layanan & asesmen',
            ],
            [
                'label' => 'Konsultasi Belum Diproses', 'value' => $this->dash->openComplaints($role, $userId),
                'icon' => 'mdi mdi-message-alert-outline', 'color' => 'danger',
                'url' => base_url('koordinator/consultations'), 'link_text' => 'Tinjau Konsultasi',
            ],
            [
                'label' => 'Tertugaskan ke Guru BK', 'value' => $this->dash->openAssignmentsAll(),
                'icon' => 'mdi mdi-account-arrow-right-outline', 'color' => 'success',
                'url' => base_url('koordinator/assignments'), 'link_text' => 'Kelola Penugasan',
            ],
            [
                'label' => 'Pesan Masuk', 'value' => $this->dash->unreadMessages($userId),
                'icon' => 'mdi mdi-email-outline', 'color' => 'info',
                'url' => base_url('koordinator/messages'), 'link_text' => 'Buka Pesan',
            ],
        ];

        $data = [
            'pageTitle'     => 'Dashboard Koordinator BK',
            'currentUser'   => $currentUser,
            'activeAcademic' => $activeAcademic,
            'welcome' => [
                'name' => $currentUser['full_name'] ?? 'Koordinator BK',
                'role_label' => 'Koordinator BK',
                'ay' => $activeAcademic['year'] ?? '',
                'sem' => $activeAcademic['semester'] ?? '',
                'desc' => 'Pantau seluruh layanan BK, konsultasi, penugasan, dan aktivitas Guru BK.',
                'shortcuts' => [
                    ['label' => 'Lihat Laporan', 'url' => base_url('koordinator/reports'), 'icon' => 'mdi-file-chart'],
                    ['label' => 'Penugasan', 'url' => base_url('koordinator/assignments'), 'icon' => 'mdi-account-arrow-right-outline'],
                    ['label' => 'Konsultasi & Pengaduan', 'url' => base_url('koordinator/consultations'), 'icon' => 'mdi-message-alert-outline'],
                ],
            ],
            'cards'            => $cards,
            'featureCounts'    => $this->dash->featureCounts($role, $userId),
            'monthlyTrend'     => $this->dash->monthlyTrend($role, $userId),
            'topCounselors'    => $this->dash->topCounselorsByRecords(5),
            'recentActivities' => $this->dash->recentActivities($role, $userId, 8),
        ];

        return view('koordinator/dashboard', $data);
    }

    protected function getActiveAcademicYearInfo(): array
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('academic_years')) {
                return [];
            }
            $row = $db->table('academic_years')
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get(1)->getRowArray();
            if (! $row) {
                return [];
            }
            return [
                'year'     => (string) ($row['year_name'] ?? $row['academic_year'] ?? ''),
                'semester' => (string) ($row['semester'] ?? ''),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'getActiveAcademicYearInfo (koordinator) error: ' . $e->getMessage());
            return [];
        }
    }
}
