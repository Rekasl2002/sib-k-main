<?php

/**
 * File Path: app/Controllers/HomeroomTeacher/ClassController.php
 *
 * Homeroom Teacher • Class
 * Menangani tampilan ringkas kelas perwalian (my-class) untuk akun Wali Kelas.
 *
 * Fitur:
 * - Menentukan tahun ajaran aktif
 * - Mengambil kelas aktif yang diampu oleh Wali Kelas yang login
 * - Menampilkan daftar siswa aktif di kelas tersebut
 * - Menampilkan statistik kelas (jumlah siswa, L/P, rata-rata poin pelanggaran)
 * - Menampilkan 5 pelanggaran terbaru di kelas (hanya yang tidak di-soft delete)
 * - Menampilkan 5 siswa dengan poin pelanggaran tertinggi
 *
 * Catatan perbaikan (2026-01-02+):
 * - Kolom students.full_name sudah dihapus -> pakai users.full_name via students.user_id
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;

class ClassController extends BaseController
{
    /**
     * @var BaseConnection
     */
    protected $db;

    /** @var ClassModel|null */
    protected $classModel;

    /** @var StudentModel|null */
    protected $studentModel;

    /** @var UserModel|null */
    protected $userModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();

        // Pakai model() agar aman kalau suatu saat modelnya dipakai
        try {
            $this->classModel = model(ClassModel::class);
        } catch (\Throwable $e) {
            $this->classModel = null;
        }

        try {
            $this->studentModel = model(StudentModel::class);
        } catch (\Throwable $e) {
            $this->studentModel = null;
        }

        try {
            $this->userModel = model(UserModel::class);
        } catch (\Throwable $e) {
            $this->userModel = null;
        }
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

    private function summarizeClasses(array $classes): array
    {
        if (count($classes) <= 1) {
            return $classes[0] ?? [];
        }

        $summary = $classes[0];
        $summary['id'] = null;
        $summary['is_multiple'] = true;
        $summary['class_count'] = count($classes);
        $summary['class_name'] = implode(', ', array_map(static function ($row) {
            return (string) ($row['class_name'] ?? '-');
        }, $classes));

        $grades = array_values(array_unique(array_filter(array_map(static function ($row) {
            return (string) ($row['grade_level'] ?? '');
        }, $classes))));
        $summary['grade_level'] = count($grades) === 1 ? $grades[0] : 'Beragam';

        $majors = array_values(array_unique(array_filter(array_map(static function ($row) {
            return (string) ($row['major'] ?? '');
        }, $classes))));
        $summary['major'] = count($majors) === 1 ? $majors[0] : '';

        return $summary;
    }

    /**
     * GET /homeroom/my-class
     * Tampilan ringkas kelas perwalian untuk Wali Kelas (role_id = 4)
     */
    public function myClass()
    {
        $session = session();
        $userId  = $session->get('user_id') ?? $session->get('id');
        $roleId  = (int) ($session->get('role_id') ?? 0);

        if (!$userId) {
            return redirect()->to('/login');
        }

        // Guard sederhana: hanya Wali Kelas (role_id = 4)
        if ($roleId && $roleId !== 4) {
            return redirect()->to('/')
                ->with('error', 'Akses ditolak: halaman ini khusus Wali Kelas.');
        }

        // Tahun ajaran aktif (tambahkan guard soft delete bila kolom tersedia)
        $activeYearQ = $this->db->table('academic_years')
            ->select('id, year_name, semester')
            ->where('is_active', 1);

        // Jika tabel academic_years punya deleted_at, ini aman; kalau tidak ada, CI akan error.
        // Karena kita tidak bisa cek struktur DB di sini, kita buat try-catch kecil yang aman.
        try {
            $activeYearQ->where('deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan jika kolom tidak ada
        }

        $activeYear = $activeYearQ->get()->getRowArray();

        // Kelas yang diampu wali kelas saat ini
        $builder = $this->db->table('classes c')
            ->select(
                'c.*, ' .
                'ay.year_name, ay.semester, ' .
                'u1.full_name AS homeroom_name, ' .
                'u2.full_name AS counselor_name'
            )
            ->join('academic_years ay', 'ay.id = c.academic_year_id', 'left')
            ->join('users u1', 'u1.id = c.homeroom_teacher_id AND u1.deleted_at IS NULL', 'left')
            ->join('users u2', 'u2.id = c.counselor_id AND u2.deleted_at IS NULL', 'left')
            ->where('c.homeroom_teacher_id', (int)$userId)
            ->where('c.is_active', 1);

        // Soft delete guard untuk classes
        try {
            $builder->where('c.deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan jika kolom tidak ada
        }

        // Jika ada tahun ajaran aktif, batasi ke tahun tersebut
        if ($activeYear && !empty($activeYear['id'])) {
            $builder->where('c.academic_year_id', (int)$activeYear['id']);
        }

        $classes = $builder
            ->orderBy('c.grade_level', 'ASC')
            ->orderBy('c.class_name', 'ASC')
            ->orderBy('c.id', 'ASC')
            ->get()
            ->getResultArray();
        $class = $this->summarizeClasses($classes);
        $classIds = $this->classIds($classes);

        if (empty($classIds)) {
            return view('homeroom_teacher/class/my_class', [
                'pageTitle'            => 'Kelas Binaan',
                'class'                => null,
                'classes'              => [],
                'activeYear'           => $activeYear,
                'stats'                => null,
                'students'             => [],
                'recentViolations'     => [],
                'topViolationStudents' => [],
            ]);
        }

        // =========================================================
        // Daftar siswa aktif di kelas
        // NOTE: students.full_name sudah dihapus -> pakai users.full_name
        // =========================================================
        $studentsQ = $this->db->table('students s')
            ->select([
                's.id',
                's.user_id',
                'u.full_name AS full_name',
                's.gender',
                's.nik',
                's.nisn',
                's.birth_date',
                's.total_violation_points',
                's.status',
                'c.class_name',
                'c.grade_level',
            ])
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->whereIn('s.class_id', $classIds)
            ->where('s.status', 'Aktif');

        // Soft delete guard untuk students
        try {
            $studentsQ->where('s.deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan jika kolom tidak ada
        }

        $students = $studentsQ
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        // =========================================================
        // Statistik ringkas kelas
        // =========================================================
        $statsQ = $this->db->table('students s')
            ->select([
                'COUNT(*) AS total_students',
                "SUM(CASE WHEN s.gender = 'L' THEN 1 ELSE 0 END) AS total_male",
                "SUM(CASE WHEN s.gender = 'P' THEN 1 ELSE 0 END) AS total_female",
                'COALESCE(AVG(s.total_violation_points),0) AS avg_points',
            ])
            ->whereIn('s.class_id', $classIds)
            ->where('s.status', 'Aktif');

        try {
            $statsQ->where('s.deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan
        }

        $stats = $statsQ->get()->getRowArray();

        // =========================================================
        // 5 pelanggaran terbaru di kelas ini (HANYA yang tidak di-soft delete)
        // NOTE: student_name ambil dari users.full_name
        // =========================================================
        $recentViolations = $this->db->table('violations v')
            ->select([
                'v.id',
                'v.violation_date',
                'v.violation_time',
                'v.location',
                'v.status',
                'vc.severity_level',
                'vc.category_name',
                'vc.point_deduction',
                'su.full_name AS student_name',
                's.nik AS student_nik',
                's.nisn AS student_nisn',
                'c.class_name',
            ])
            ->join('students s', 's.id = v.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->whereIn('s.class_id', $classIds)
            ->where('v.deleted_at', null)
            ->orderBy('v.violation_date', 'DESC')
            ->orderBy('v.created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // =========================================================
        // 5 siswa dengan poin pelanggaran tertinggi di kelas
        // NOTE: full_name ambil dari users.full_name
        // =========================================================
        $topStudentsQ = $this->db->table('students s')
            ->select([
                's.id',
                's.user_id',
                'u.full_name AS full_name',
                's.gender',
                's.nik',
                's.nisn',
                's.total_violation_points',
                'c.class_name',
            ])
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->whereIn('s.class_id', $classIds)
            ->where('s.status', 'Aktif')
            ->where('s.total_violation_points >', 0);

        try {
            $topStudentsQ->where('s.deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan
        }

        $topViolationStudents = $topStudentsQ
            ->orderBy('s.total_violation_points', 'DESC')
            ->orderBy('u.full_name', 'ASC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return view('homeroom_teacher/class/my_class', [
            'pageTitle'            => 'Kelas Binaan',
            'class'                => $class,
            'classes'              => $classes,
            'classIds'             => $classIds,
            'activeYear'           => $activeYear,
            'stats'                => $stats,
            'students'             => $students,
            'recentViolations'     => $recentViolations,
            'topViolationStudents' => $topViolationStudents,
        ]);
    }
}
