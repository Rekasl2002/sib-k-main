<?php
// app/Controllers/Student/DashboardController.php

namespace App\Controllers\Student;

use App\Services\DashboardService;
use App\Services\BkServiceService;
use CodeIgniter\I18n\Time;

class DashboardController extends BaseStudentController
{
    /**
     * Dashboard ringkas siswa:
     * - Info siswa & kelas
     * - Tahun ajaran aktif
     * - Jadwal konseling mendatang (Individu/Kelompok/Klasikal)
     * - Asesmen tersedia (All / Class / Grade [roman/angka] / Individual)
     * - Hasil asesmen terbaru
     */
    public function index()
    {
        $this->requireStudent();

        $today = Time::today($this->tz)->toDateString();

        /**
         * FIX SCHEMA:
         * - students.full_name sudah dihapus
         * - Nama siswa diambil dari users.full_name via students.user_id
         * - Tetap alias sebagai "full_name" agar view student/dashboard tetap kompatibel.
         */
        $student = $this->db->table('students s')
            ->select(
                's.id as student_id, u.full_name as full_name, s.nisn, s.nik, s.class_id,' .
                'c.class_name, c.grade_level, c.major'
            )
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.id', $this->studentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRow();

            helper('auth');

        // current user (nama untuk welcome)
        $currentUser = function_exists('auth_user') ? (auth_user() ?? []) : [
            'full_name' => session('full_name') ?? session('name') ?? 'Siswa'
        ];

        // tahun ajaran aktif + semester
        $activeAcademic = $this->getActiveAcademicYearInfo();

        // Guard ringan bila profil tidak ditemukan
        if (!$student) {
            return view('student/dashboard', [
                'student'          => null,
                'activeYear'       => null,
                'upcomingSessions' => [],
                'assessments'      => [],
                'recentResults'    => [],
                'currentUser'    => $currentUser,
                'activeAcademic' => $activeAcademic,
            ]);
        }

        $classId = (int) ($student->class_id ?? 0);

        // 2) Tahun ajaran aktif (pakai alias agar view bisa baca year_label)
        $activeYear = $this->db->table('academic_years')
            ->select('id, year_name AS year_label, semester, start_date, end_date')
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        /**
         * 3) Jadwal/Kegiatan BK mendatang (semua jenis layanan BK, bukan hanya
         * konseling). Diambil dari agregator jadwal read-only BkServiceService
         * sesuai cakupan siswa. Hanya garis besar aman: jenis, tanggal-waktu,
         * lokasi, status (tanpa topik/detail rahasia).
         */
        $bk = new BkServiceService();
        $upcomingSessions = $this->flattenSchedule($bk->scheduleByType('siswa', (int) $this->userId, 'upcoming'), 6);

        // 4) Meta kelas/tingkat untuk filter asesmen (dukung roman/angka)
        $gradeRoman = null;
        $gradeNum   = null;

        $romanToNum = ['X' => '10', 'XI' => '11', 'XII' => '12'];
        $numToRoman = ['10' => 'X', '11' => 'XI', '12' => 'XII'];

        $rawGrade = strtoupper((string) ($student->grade_level ?? ''));
        if (isset($romanToNum[$rawGrade])) {
            $gradeRoman = $rawGrade;
            $gradeNum   = $romanToNum[$rawGrade];
        } elseif (isset($numToRoman[$rawGrade])) {
            $gradeNum   = $rawGrade;
            $gradeRoman = $numToRoman[$rawGrade];
        } else {
            $gradeRoman = $rawGrade ?: null;
            $gradeNum   = $romanToNum[$rawGrade] ?? null;
        }

        /**
         * 5) Asesmen tersedia (aktif, publish, window valid, target match, non-deleted)
         * + flag has_done agar view bisa menyembunyikan tombol "Kerjakan" bila sudah dikerjakan
         *
         * Catatan: groupBy diperluas untuk aman jika MySQL strict mode (ONLY_FULL_GROUP_BY) aktif.
         */
        $assessments = $this->db->table('assessments a')
            ->select(
                'a.id, a.title, a.assessment_type, a.start_date, a.end_date, a.duration_minutes,' .
                'a.target_audience, a.target_class_id, a.target_grade, a.total_questions'
            )
            // untuk target Individual, cek assignment lewat assessment_results yang non-deleted (mengikuti pola file lama)
            ->join(
                'assessment_results ar_i',
                'ar_i.assessment_id = a.id AND ar_i.student_id = ' . (int) $this->studentId . ' AND ar_i.deleted_at IS NULL',
                'left'
            )
            // hasil/riwayat pengerjaan siswa (semua target), non-deleted
            ->join(
                'assessment_results rs',
                'rs.assessment_id = a.id AND rs.student_id = ' . (int) $this->studentId . ' AND rs.deleted_at IS NULL',
                'left'
            )
            ->select("MAX(CASE WHEN rs.status IN ('Completed','Graded') THEN 1 ELSE 0 END) AS has_done", false)
            ->where('a.is_active', 1)
            ->where('a.is_published', 1)
            ->where('a.deleted_at', null)
            ->groupStart()
                ->where('(a.start_date IS NULL OR a.start_date <= ' . $this->db->escape($today) . ')', null, false)
            ->groupEnd()
            ->groupStart()
                ->where('(a.end_date IS NULL OR a.end_date >= ' . $this->db->escape($today) . ')', null, false)
            ->groupEnd()
            ->groupStart()
                ->where('a.target_audience', 'All')
                ->orGroupStart()
                    ->where('a.target_audience', 'Class')
                    ->where('a.target_class_id', $classId)
                ->groupEnd()
                ->orGroupStart()
                    ->where('a.target_audience', 'Grade')
                    ->groupStart()
                        ->where('a.target_grade', $gradeRoman)
                        ->orWhere('a.target_grade', $gradeNum)
                    ->groupEnd()
                ->groupEnd()
                ->orGroupStart()
                    ->where('a.target_audience', 'Individual')
                    ->where('ar_i.id IS NOT NULL', null, false)
                ->groupEnd()
            ->groupEnd()
            ->groupBy('a.id, a.title, a.assessment_type, a.start_date, a.end_date, a.duration_minutes, a.target_audience, a.target_class_id, a.target_grade, a.total_questions')
            ->orderBy('a.start_date', 'ASC')
            ->limit(5)
            ->get()
            ->getResult();

        // 6) Ringkas hasil asesmen terakhir (non-deleted)
        $recentResults = $this->db->table('assessment_results r')
            ->select('r.id, r.assessment_id, a.title, r.status, r.percentage, r.is_passed, r.completed_at')
            ->join('assessments a', 'a.id = r.assessment_id AND a.deleted_at IS NULL', 'left')
            ->where('r.student_id', (int) $this->studentId)
            ->where('r.deleted_at', null)
            ->orderBy('r.completed_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResult();

        // Kartu kecil
        $dash = new DashboardService();
        $availableAssessments = 0;
        foreach ($assessments as $a) {
            if (empty($a->has_done)) {
                $availableAssessments++;
            }
        }

        $cards = [
            [
                'label' => 'Jadwal/Kegiatan BK', 'value' => count($upcomingSessions),
                'icon' => 'mdi mdi-calendar-clock', 'color' => 'primary',
                'url' => base_url('student/jadwal-bk'), 'link_text' => 'Mendatang',
            ],
            [
                'label' => 'Asesmen Tersedia', 'value' => $availableAssessments,
                'icon' => 'mdi mdi-clipboard-list-outline', 'color' => 'success',
                'url' => base_url('student/assessments'), 'link_text' => 'Kerjakan Asesmen',
            ],
            [
                'label' => 'Hasil Asesmen', 'value' => count($recentResults),
                'icon' => 'mdi mdi-clipboard-check-outline', 'color' => 'warning',
                'url' => base_url('student/assessments'), 'link_text' => 'Lihat Hasil',
            ],
            [
                'label' => 'Pesan Masuk', 'value' => $dash->unreadMessages((int) $this->userId),
                'icon' => 'mdi mdi-email-outline', 'color' => 'info',
                'url' => base_url('student/messages'), 'link_text' => 'Buka Pesan',
            ],
        ];

        $welcome = [
            'name' => $currentUser['full_name'] ?? 'Siswa',
            'role_label' => 'Siswa',
            'ay' => $activeAcademic['year'] ?? '',
            'sem' => $activeAcademic['semester'] ?? '',
            'desc' => 'Lihat data pribadi, jadwal kegiatan/acara BK, asesmen, serta info karier dan studi lanjut.',
            'shortcuts' => [
                ['label' => 'Profil Saya', 'url' => base_url('student/profile'), 'icon' => 'mdi-account-circle-outline'],
                ['label' => 'Jadwal Kegiatan BK', 'url' => base_url('student/jadwal-bk'), 'icon' => 'mdi-calendar-heart'],
                ['label' => 'Asesmen', 'url' => base_url('student/assessments'), 'icon' => 'mdi-clipboard-list-outline'],
            ],
        ];

        return view('student/dashboard', [
            'title'            => 'Dashboard Siswa',
            'student'          => $student,
            'activeYear'       => $activeYear,
            'upcomingSessions' => $upcomingSessions,
            'assessments'      => $assessments,
            'recentResults'    => $recentResults,
            'currentUser'      => $currentUser,
            'activeAcademic'   => $activeAcademic,
            'cards'            => $cards,
            'welcome'          => $welcome,
        ]);
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
            log_message('error', 'getActiveAcademicYearInfo (student) error: ' . $e->getMessage());
            return [];
        }
    }
}
