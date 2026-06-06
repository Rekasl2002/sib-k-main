<?php
// app/Controllers/HomeroomTeacher/StudentController.php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

class StudentController extends BaseController
{
    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
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
     * GET /homeroom/students
     * Daftar siswa di kelas perwalian wali kelas yang login.
     */
    public function index()
    {
        $session = session();
        $userId  = $session->get('user_id') ?? $session->get('id');
        $roleId  = (int) ($session->get('role_id') ?? 0);

        if (!$userId) {
            return redirect()->to('/login');
        }

        // Guard sederhana (opsional): pastikan role adalah Wali Kelas
        if ($roleId && $roleId !== 4) {
            return redirect()->to('/')->with('error', 'Akses khusus Wali Kelas.');
        }

        // Tahun ajaran aktif
        $activeYearQ = $this->db->table('academic_years')
            ->select('id, year_name, semester')
            ->where('is_active', 1);

        // Soft delete guard (jika kolom ada)
        try {
            $activeYearQ->where('deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan jika kolom tidak ada
        }

        $activeYear = $activeYearQ->get()->getRowArray();

        // Kelas yang diampu wali kelas pada tahun aktif
        $builder = $this->db->table('classes c')
            ->select('c.id, c.class_name, c.grade_level, c.major')
            ->where('c.homeroom_teacher_id', (int)$userId)
            ->where('c.is_active', 1);

        // Soft delete guard (jika kolom ada)
        try {
            $builder->where('c.deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan jika kolom tidak ada
        }

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

        // Daftar siswa aktif
        // NOTE: kolom students.full_name sudah dihapus -> ambil dari users.full_name
        $students = [];
        if (!empty($classIds)) {
            $studentsQ = $this->db->table('students s')
                ->select([
                    's.id',
                    'u.full_name AS full_name',
                    's.nik',
                    's.nisn',
                    's.gender',
                    'c.class_name',
                    'c.grade_level',
                ])
                ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
                ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
                ->whereIn('s.class_id', $classIds)
                ->where('s.status', 'Aktif');

            // Soft delete guard (jika kolom ada)
            try {
                $studentsQ->where('s.deleted_at', null);
            } catch (\Throwable $e) {
                // abaikan jika kolom tidak ada
            }

            $students = $studentsQ
                ->orderBy('u.full_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('homeroom_teacher/students/index', [
            'pageTitle'  => 'Daftar Siswa Kelas Saya',
            'activeYear' => $activeYear,
            'class'      => $class,
            'classes'    => $classes,
            'classIds'   => $classIds,
            'students'   => $students,
        ]);
    }

    /**
     * GET /homeroom/students/(:num)
     * Halaman detail siswa (ringkasan biodata, akademik, dan jadwal)
     * untuk Wali Kelas. Tanpa membuka catatan konseling yang rahasia.
     */
    public function show($id)
    {
        $session = session();
        $userId  = $session->get('user_id') ?? $session->get('id');
        $roleId  = (int) ($session->get('role_id') ?? 0);

        if (!$userId) {
            return redirect()->to('/login');
        }

        // Guard role (opsional) – sama seperti index()
        if ($roleId && $roleId !== 4) {
            return redirect()->to('/')->with('error', 'Akses khusus Wali Kelas.');
        }

        // Tahun ajaran aktif
        $activeYearQ = $this->db->table('academic_years')
            ->select('id, year_name, semester')
            ->where('is_active', 1);

        try {
            $activeYearQ->where('deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan
        }

        $activeYear = $activeYearQ->get()->getRowArray();

        // Kelas yang diampu wali kelas ini
        $builder = $this->db->table('classes c')
            ->select('c.*, ay.year_name, ay.semester')
            ->join('academic_years ay', 'ay.id = c.academic_year_id', 'left')
            ->where('c.homeroom_teacher_id', (int)$userId)
            ->where('c.is_active', 1);

        try {
            $builder->where('c.deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan
        }

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
            return redirect()
                ->route('homeroom.students')
                ->with('error', 'Anda belum memiliki kelas aktif.');
        }

        // Ambil data siswa + akun user + info kelas yang sudah di-join
        // NOTE: jangan pakai COALESCE(s.full_name, u.full_name) karena s.full_name sudah tidak ada
        $studentQ = $this->db->table('students s')
            ->select("
                s.*,
                u.full_name AS full_name,
                u.email,
                u.phone,
                u.profile_photo,
                c.class_name,
                c.grade_level      AS grade_label,
                c.major            AS major_name,
                ay.year_name       AS academic_year_name
            ")
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('academic_years ay', 'ay.id = c.academic_year_id', 'left')
            ->where('s.id', (int)$id)
            ->whereIn('s.class_id', $classIds); // pastikan milik kelas wali ini

        // Soft delete guard untuk students (jika kolom ada)
        try {
            $studentQ->where('s.deleted_at', null);
        } catch (\Throwable $e) {
            // abaikan
        }

        $student = $studentQ->get()->getRowArray();

        if (!$student) {
            return redirect()
                ->route('homeroom.students')
                ->with('error', 'Siswa tidak ditemukan.');
        }

        // Tambahkan alias pada $class supaya cocok dengan yang dipakai di view
        if ($class) {
            $class['grade_label']        = $class['grade_level'] ?? null;
            $class['major_name']         = $class['major'] ?? null;
            $class['academic_year_name'] = $class['year_name'] ?? ($activeYear['year_name'] ?? null);
        }

        // Ambil data Orang Tua / Wali (dari users, via students.parent_id)
        if (!empty($student['parent_id'])) {
            $parent = $this->db->table('users u')
                ->select('u.id, u.full_name, u.phone, u.email')
                ->where('u.id', (int)$student['parent_id'])
                ->where('u.deleted_at', null)
                ->get()
                ->getRowArray();

            if ($parent) {
                // Tanpa menambah variabel baru di view: injeksikan ke array student
                $student['parent_name']  = $parent['full_name'] ?? null;
                $student['parent_phone'] = $parent['phone'] ?? null;
                $student['parent_email'] = $parent['email'] ?? null;
            }
        }

        // Jadwal konseling mendatang (meta saja, tanpa isi ringkasan/masalah detail)
        $today = date('Y-m-d');

        $upcomingSessions = $this->db->table('counseling_sessions cs')
            ->select("
                cs.id,
                cs.session_date,
                cs.session_time,
                cs.topic,
                cs.status,
                u.full_name AS counselor_name
            ")
            ->join('users u', 'u.id = cs.counselor_id AND u.deleted_at IS NULL', 'left')
            ->where('cs.student_id', (int)$student['id'])
            ->where('cs.deleted_at', null)
            ->where('cs.status !=', 'Dibatalkan')
            ->where('cs.session_date >=', $today)
            ->orderBy('cs.session_date', 'ASC')
            ->orderBy('cs.session_time', 'ASC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return view('homeroom_teacher/students/show', [
            'pageTitle'        => 'Detail Siswa',
            'student'          => $student,
            'class'            => $class,
            'classes'          => $classes,
            'classIds'         => $classIds,
            'activeYear'       => $activeYear,
            'upcomingSessions' => $upcomingSessions,
        ]);
    }
}
