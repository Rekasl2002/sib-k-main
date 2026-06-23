<?php

/**
 * File Path: app/Controllers/HomeroomTeacher/DashboardController.php
 *
 * Wali Kelas • Dashboard
 * Tata letak patokan Admin + NNG: welcome + 3 shortcut, kartu kecil, chart
 * (komposisi siswa + jumlah data per fitur BK kelas), dan tabel Jadwal/Kegiatan
 * BK Mendatang kelas binaan. Fokus pada kegiatan BK (bukan "konseling" saja).
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Services\DashboardService;
use App\Services\BkServiceService;

class DashboardController extends BaseController
{
    protected $db;
    protected DashboardService $dash;
    protected BkServiceService $bk;

    public function __construct()
    {
        $this->db   = \Config\Database::connect();
        $this->dash = new DashboardService();
        $this->bk   = new BkServiceService();
        helper(['auth', 'permission', 'url']);
    }

    public function index()
    {
        if (! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (! is_homeroom_teacher()) {
            return redirect()->to(get_dashboard_url())->with('error', 'Akses ditolak');
        }

        $userId = (int) auth_id();
        if (! $userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $classes = $this->getHomeroomClasses($userId);
        if (empty($classes)) {
            return view('homeroom_teacher/dashboard', [
                'pageTitle' => 'Dashboard Wali Kelas',
                'hasClass'  => false,
                'message'   => 'Anda belum ditugaskan sebagai wali kelas. Silakan hubungi administrator.',
            ]);
        }

        $classIds = $this->classIds($classes);
        $class    = $this->summarizeHomeroomClasses($classes);
        $stats    = $this->getClassStatistics($classIds);
        $role     = 'wali-kelas';

        $genderMale   = (int) ($stats['gender_distribution']['male'] ?? 0);
        $genderFemale = (int) ($stats['gender_distribution']['female'] ?? 0);

        $cards = [
            [
                'label' => 'Total Siswa', 'value' => $stats['total_students'] ?? 0,
                'icon' => 'mdi mdi-account-group', 'color' => 'primary',
                'url' => base_url('homeroom/my-class'), 'link_text' => 'Lihat Kelas Binaan',
            ],
            [
                'label' => 'Kegiatan BK (Kelas)', 'value' => $this->dash->catatanKegiatanCount($role, $userId),
                'icon' => 'mdi mdi-clipboard-text-outline', 'color' => 'success',
                'url' => base_url('homeroom/jadwal-bk'), 'link_text' => 'Jadwal Kegiatan BK',
            ],
            [
                'label' => 'Kelas Binaan', 'value' => (int) ($class['class_count'] ?? count($classes)),
                'icon' => 'mdi mdi-google-classroom', 'color' => 'warning',
                'url' => base_url('homeroom/my-class'), 'link_text' => $class['class_name'] ?? '-',
            ],
            [
                'label' => 'Pesan Masuk', 'value' => $this->dash->unreadMessages($userId),
                'icon' => 'mdi mdi-email-outline', 'color' => 'info',
                'url' => base_url('homeroom/messages'), 'link_text' => 'Buka Pesan',
            ],
        ];

        $upcoming = $this->flattenSchedule($this->bk->scheduleByType($role, $userId, 'upcoming'), 8);

        $data = [
            'pageTitle'   => 'Dashboard Wali Kelas',
            'hasClass'    => true,
            'class'       => $class,
            'currentUser' => auth_user(),
            'welcome' => [
                'name' => (auth_user()['full_name'] ?? 'Wali Kelas'),
                'role_label' => 'Wali Kelas',
                'ay' => $class['year_name'] ?? '',
                'sem' => $class['semester'] ?? '',
                'desc' => 'Pantau siswa kelas binaan dan jadwal/kegiatan BK yang melibatkan kelas Anda.',
                'shortcuts' => [
                    ['label' => 'Kelas Binaan', 'url' => base_url('homeroom/my-class'), 'icon' => 'mdi-google-classroom'],
                    ['label' => 'Laporan Kelas', 'url' => base_url('homeroom/reports'), 'icon' => 'mdi-file-chart'],
                    ['label' => 'Konsultasi & Pengaduan', 'url' => base_url('homeroom/consultations'), 'icon' => 'mdi-message-alert-outline'],
                ],
            ],
            'cards'         => $cards,
            'genderMale'    => $genderMale,
            'genderFemale'  => $genderFemale,
            'featureCounts' => $this->dash->featureCounts($role, $userId),
            'upcoming'      => $upcoming,
        ];

        return view('homeroom_teacher/dashboard', $data);
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
                ->get()->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get classes error: ' . $e->getMessage());
            return [];
        }
    }

    private function summarizeHomeroomClasses(array $classes): array
    {
        if (count($classes) <= 1) {
            return $classes[0] ?? [];
        }
        $summary = $classes[0];
        $summary['id'] = null;
        $summary['class_count'] = count($classes);
        $summary['is_multiple'] = true;
        $summary['class_name'] = implode(', ', array_map(static fn($row) => (string) ($row['class_name'] ?? '-'), $classes));
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

    private function getClassStatistics($classIds)
    {
        $classIds = $this->normalizeClassIds($classIds);
        $out = [
            'total_students'      => 0,
            'gender_distribution' => ['male' => 0, 'female' => 0],
        ];
        if (empty($classIds)) {
            return $out;
        }
        try {
            $studentFilter = ['status' => 'Aktif', 'deleted_at' => null];

            $cnt = $this->db->table('students')->where($studentFilter);
            $this->applyClassFilter($cnt, 'class_id', $classIds);
            $out['total_students'] = $cnt->countAllResults();

            $genderRow = $this->db->table('students')
                ->select("
                    SUM(CASE WHEN gender IN ('L','Laki-laki') THEN 1 ELSE 0 END) AS male,
                    SUM(CASE WHEN gender IN ('P','Perempuan') THEN 1 ELSE 0 END) AS female
                ", false)
                ->where($studentFilter);
            $this->applyClassFilter($genderRow, 'class_id', $classIds);
            $genderRow = $genderRow->get()->getRowArray();

            $out['gender_distribution'] = [
                'male'   => (int) ($genderRow['male'] ?? 0),
                'female' => (int) ($genderRow['female'] ?? 0),
            ];
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM DASHBOARD] Get statistics error: ' . $e->getMessage());
        }
        return $out;
    }
}
