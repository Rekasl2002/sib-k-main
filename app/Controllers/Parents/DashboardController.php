<?php

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

class DashboardController extends BaseController
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Lebih robust: beberapa proyek menyimpan id di user_id atau id
        $parentId = (int) (session('user_id') ?? session('id') ?? 0);
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // Anak milik parent + ringkasan per anak
        // FIX: students.full_name -> users.full_name (karena kolom students.full_name bisa sudah dihapus)
        $children = $this->db->table('students s')
            ->select("
                s.id,
                u.full_name AS full_name,
                s.nisn, s.nik, s.class_id,
                c.class_name, c.grade_level, c.major,
                u.email, u.phone, u.profile_photo
            ")
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        // Agregat lintas anak
        $stats = [
            'children'          => count($children),
            'upcoming_sessions' => 0,
        ];

        /**
         * Jadwal/Kegiatan BK mendatang (semua jenis layanan BK, bukan hanya
         * konseling) — diambil dari agregator jadwal read-only BkServiceService
         * sesuai cakupan Orang Tua. Hanya garis besar aman: jenis, tanggal-waktu,
         * lokasi, status.
         */
        $bk = new \App\Services\BkServiceService();
        $upcoming = $this->flattenSchedule($bk->scheduleByType('orang-tua', $parentId, 'upcoming'), 8);
        $stats['upcoming_sessions'] = count($upcoming);

        helper('auth');

        $currentUser = function_exists('auth_user') ? (auth_user() ?? []) : [
            'full_name' => session('full_name') ?? session('name') ?? 'Orang Tua'
        ];

        // Tahun ajaran aktif + semester (pakai helper yang sama seperti Admin)
        $activeAcademic = $this->getActiveAcademicYearInfo();

        $dash = new \App\Services\DashboardService();
        $cards = [
            [
                'label' => 'Anak Terdaftar', 'value' => $stats['children'] ?? count($children),
                'icon' => 'mdi mdi-account-child-circle', 'color' => 'primary',
                'url' => base_url('parent/children'), 'link_text' => 'Daftar Anak',
            ],
            [
                'label' => 'Jadwal/Kegiatan BK', 'value' => $stats['upcoming_sessions'] ?? 0,
                'icon' => 'mdi mdi-calendar-clock', 'color' => 'success',
                'url' => base_url('parent/jadwal-bk'), 'link_text' => 'Mendatang',
            ],
            [
                'label' => 'Pesan Masuk', 'value' => $dash->unreadMessages($parentId),
                'icon' => 'mdi mdi-email-outline', 'color' => 'info',
                'url' => base_url('parent/messages'), 'link_text' => 'Buka Pesan',
            ],
        ];

        $welcome = [
            'name' => $currentUser['full_name'] ?? 'Orang Tua',
            'role_label' => 'Orang Tua/Wali',
            'ay' => $activeAcademic['year'] ?? '',
            'sem' => $activeAcademic['semester'] ?? '',
            'desc' => 'Pantau perkembangan anak, jadwal kegiatan/acara BK, dan info karier & studi lanjut.',
            'shortcuts' => [
                ['label' => 'Daftar Anak', 'url' => base_url('parent/children'), 'icon' => 'mdi-account-child-circle'],
                ['label' => 'Laporan Anak', 'url' => base_url('parent/reports/children'), 'icon' => 'mdi-file-chart'],
                ['label' => 'Konsultasi & Pengaduan', 'url' => base_url('parent/consultations'), 'icon' => 'mdi-message-alert-outline'],
            ],
        ];

        return view('parent/dashboard', [
            'title'            => 'Dashboard Orang Tua',
            'children'         => $children,
            'stats'            => $stats,
            'upcoming'         => $upcoming,
            'currentUser'      => $currentUser,
            'activeAcademic'   => $activeAcademic,
            'cards'            => $cards,
            'welcome'          => $welcome,
        ]);
    }

    /** Ratakan hasil scheduleByType [type=>rows] menjadi satu daftar terurut. */
    private function flattenSchedule(array $byType, int $limit = 8): array
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
            $db = \Config\Database::connect();

            if (method_exists($db, 'tableExists') && !$db->tableExists('academic_years')) {
                return [];
            }

            $fields = $db->getFieldNames('academic_years');

            $b = $db->table('academic_years')->select('*');

            if (in_array('deleted_at', $fields, true)) {
                $b->where('deleted_at', null);
            }

            if (in_array('is_active', $fields, true)) {
                $b->where('is_active', 1);
            } elseif (in_array('active', $fields, true)) {
                $b->where('active', 1);
            } elseif (in_array('status', $fields, true)) {
                $b->where('status', 'active');
            }

            $orderCol = in_array('updated_at', $fields, true) ? 'updated_at' : 'id';
            $row = $b->orderBy($orderCol, 'DESC')->get(1)->getRowArray();
            if (!$row) return [];

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
            log_message('error', 'getActiveAcademicYearInfo (parent) error: ' . $e->getMessage());
            return [];
        }
    }
}
