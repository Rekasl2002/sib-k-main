<?php
// app/Controllers/HomeroomTeacher/StudentController.php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\StudentService;
use App\Validation\StudentValidation;
use CodeIgniter\Database\BaseConnection;

class StudentController extends BaseController
{
    /** @var BaseConnection */
    protected $db;
    protected StudentService $studentService;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->studentService = new StudentService();
        $this->userModel = new UserModel();
    }

    private function normalizeIdPhoneTo08(?string $phone): string
    {
        $p = trim((string) ($phone ?? ''));
        if ($p === '') {
            return '';
        }

        $p = preg_replace('/[^\d+]/', '', $p) ?? '';
        if (str_starts_with($p, '+62')) {
            return '0' . substr($p, 3);
        }
        if (str_starts_with($p, '62')) {
            return '0' . substr($p, 2);
        }

        return $p;
    }

    private function parentRoleId(): int
    {
        $row = $this->db->table('roles')
            ->select('id')
            ->where('role_name', 'Orang Tua')
            ->get()
            ->getRowArray();

        return (int) ($row['id'] ?? 6);
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
     * Konteks kelas binaan untuk Wali Kelas yang sedang login.
     *
     * @return array{user_id:int,role_id:int,activeYear:?array,classes:array,class:array,classIds:array}
     */
    private function homeroomContext(): array
    {
        $session = session();
        $userId  = (int) ($session->get('user_id') ?? $session->get('id') ?? 0);
        $roleId  = (int) ($session->get('role_id') ?? 0);

        $activeYearQ = $this->db->table('academic_years')
            ->select('id, year_name, semester')
            ->where('is_active', 1);

        try {
            $activeYearQ->where('deleted_at', null);
        } catch (\Throwable $e) {
        }

        $activeYear = $activeYearQ->get()->getRowArray();

        $builder = $this->db->table('classes c')
            ->select('c.id, c.class_name, c.grade_level, c.major, c.academic_year_id')
            ->where('c.homeroom_teacher_id', $userId)
            ->where('c.is_active', 1);

        try {
            $builder->where('c.deleted_at', null);
        } catch (\Throwable $e) {
        }

        if ($activeYear && ! empty($activeYear['id'])) {
            $builder->where('c.academic_year_id', (int) $activeYear['id']);
        }

        $classes = $builder
            ->orderBy('c.grade_level', 'ASC')
            ->orderBy('c.class_name', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'user_id' => $userId,
            'role_id' => $roleId,
            'activeYear' => $activeYear ?: null,
            'classes' => $classes,
            'class' => $this->summarizeClasses($classes),
            'classIds' => $this->classIds($classes),
        ];
    }

    private function guardHomeroom(array $context)
    {
        if (($context['user_id'] ?? 0) <= 0) {
            return redirect()->to('/login');
        }

        $roleId = (int) ($context['role_id'] ?? 0);
        if ($roleId && $roleId !== 4) {
            return redirect()->to('/')->with('error', 'Akses khusus Wali Kelas.');
        }

        return null;
    }

    private function studentInScope(int $studentId, array $classIds): ?array
    {
        if (empty($classIds)) {
            return null;
        }

        $row = $this->db->table('students s')
            ->select('s.*, u.username, u.full_name AS full_name, u.email, u.phone, u.profile_photo, c.class_name, c.grade_level, c.major')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.id', $studentId)
            ->whereIn('s.class_id', $classIds)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function parentsInScope(array $classIds): array
    {
        if (empty($classIds)) {
            return [];
        }

        return $this->db->table('users p')
            ->select([
                'p.id',
                'p.username',
                'p.full_name',
                'p.email',
                'p.phone',
                'p.is_active',
                'COUNT(s.id) AS child_count',
                "GROUP_CONCAT(DISTINCT CONCAT(c.grade_level, ' ', c.class_name) ORDER BY c.grade_level, c.class_name SEPARATOR ', ') AS child_classes",
            ])
            ->join('students s', 's.parent_id = p.id AND s.deleted_at IS NULL', 'inner')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->whereIn('s.class_id', $classIds)
            ->where('p.deleted_at', null)
            ->groupBy('p.id')
            ->orderBy('p.full_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function parentSelectionOptions(array $classIds): array
    {
        $roleId = $this->parentRoleId();
        $rows = $this->db->table('users p')
            ->select('p.id, p.username, p.full_name, p.email, p.phone, p.is_active, COUNT(s.id) AS total_child_count')
            ->join('students s', 's.parent_id = p.id AND s.deleted_at IS NULL', 'left')
            ->where('p.role_id', $roleId)
            ->where('p.deleted_at', null)
            ->groupBy('p.id')
            ->orderBy('p.full_name', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $classIds = array_values(array_filter(array_map('intval', $classIds)));
        $ownedParentIds = [];
        if (! empty($classIds)) {
            $ownedRows = $this->db->table('students')
                ->select('parent_id')
                ->whereIn('class_id', $classIds)
                ->where('parent_id IS NOT NULL', null, false)
                ->where('deleted_at', null)
                ->groupBy('parent_id')
                ->get()
                ->getResultArray();

            $ownedParentIds = array_flip(array_map(static fn ($row) => (int) ($row['parent_id'] ?? 0), $ownedRows));
        }

        return array_values(array_filter($rows, static function ($row) use ($ownedParentIds): bool {
            $id = (int) ($row['id'] ?? 0);
            $totalChildren = (int) ($row['total_child_count'] ?? 0);

            return $id > 0 && ($totalChildren === 0 || isset($ownedParentIds[$id]));
        }));
    }

    private function parentInScope(int $parentId, array $classIds): ?array
    {
        if ($parentId <= 0 || empty($classIds)) {
            return null;
        }

        $parent = $this->db->table('users p')
            ->select('p.*')
            ->join('students s', 's.parent_id = p.id AND s.deleted_at IS NULL', 'inner')
            ->where('p.id', $parentId)
            ->whereIn('s.class_id', $classIds)
            ->where('p.deleted_at', null)
            ->groupBy('p.id')
            ->get()
            ->getRowArray();

        return $parent ?: null;
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
                    's.parent_id',
                    'c.class_name',
                    'c.grade_level',
                    'p.full_name AS parent_name',
                    'p.phone AS parent_phone',
                    'p.email AS parent_email',
                ])
                ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
                ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
                ->join('users p', 'p.id = s.parent_id AND p.deleted_at IS NULL', 'left')
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

        return view('homeroom_teacher/students/show', [
            'pageTitle'  => 'Detail Siswa',
            'student'    => $student,
            'class'      => $class,
            'classes'    => $classes,
            'classIds'   => $classIds,
            'activeYear' => $activeYear,
        ]);
    }

    public function create()
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        if (empty($context['classIds'])) {
            return redirect()->to(base_url('homeroom/my-class'))
                ->with('error', 'Anda belum memiliki kelas binaan aktif.');
        }

        return view('homeroom_teacher/students/form', [
            'pageTitle' => 'Tambah Siswa',
            'mode' => 'create',
            'student' => [],
            'classes' => $context['classes'],
            'class' => $context['class'],
            'parents' => $this->parentSelectionOptions($context['classIds']),
            'action' => base_url('homeroom/students/store'),
            'gender_options' => StudentValidation::getGenderOptions(),
            'religion_options' => StudentValidation::getReligionOptions(),
            'status_options' => StudentValidation::getStatusOptions(),
        ]);
    }

    public function store()
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $classIds = $context['classIds'];
        $post = $this->request->getPost() ?? [];
        $post['create_with_user'] = '1';

        $classId = (int) ($post['class_id'] ?? 0);
        if (! in_array($classId, $classIds, true)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kelas siswa harus berada dalam kelas binaan Anda.');
        }

        if (isset($post['phone'])) {
            $post['phone'] = $this->normalizeIdPhoneTo08((string) $post['phone']);
        }

        $parentMode = (string) ($post['parent_mode'] ?? 'existing');
        if ($parentMode === 'new') {
            $parentResult = $this->createParentAccountFromPost($post);
            if (! ($parentResult['success'] ?? false)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $parentResult['message'] ?? 'Gagal membuat akun orang tua.');
            }
            $post['parent_id'] = (int) ($parentResult['parent_id'] ?? 0);
        } elseif ($parentMode === 'none') {
            $post['parent_id'] = null;
        }

        if (! empty($post['parent_id']) && ! $this->parentInScope((int) $post['parent_id'], $classIds)) {
            $parent = $this->db->table('users')
                ->select('id')
                ->where('id', (int) $post['parent_id'])
                ->where('role_id', $this->parentRoleId())
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $parent) {
                return redirect()->back()->withInput()->with('error', 'Akun orang tua tidak ditemukan.');
            }
        }

        $rules = StudentValidation::createWithUserRules();
        if (! $this->validateData($post, $rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = StudentValidation::sanitizeInput($post);
        if (isset($data['phone'])) {
            $data['phone'] = $this->normalizeIdPhoneTo08((string) $data['phone']);
        }

        $result = $this->studentService->createStudentWithUser($data);
        if (! ($result['success'] ?? false)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal menambah siswa.');
        }

        return redirect()->to(base_url('homeroom/my-class'))
            ->with('success', $result['message'] ?? 'Siswa berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $student = $this->studentInScope((int) $id, $context['classIds']);
        if (! $student) {
            return redirect()->to(base_url('homeroom/my-class'))->with('error', 'Siswa tidak ditemukan di kelas binaan Anda.');
        }

        return view('homeroom_teacher/students/form', [
            'pageTitle' => 'Edit Siswa',
            'mode' => 'edit',
            'student' => $student,
            'classes' => $context['classes'],
            'class' => $context['class'],
            'parents' => $this->parentSelectionOptions($context['classIds']),
            'action' => base_url('homeroom/students/update/' . (int) $id),
            'gender_options' => StudentValidation::getGenderOptions(),
            'religion_options' => StudentValidation::getReligionOptions(),
            'status_options' => StudentValidation::getStatusOptions(),
        ]);
    }

    public function update($id)
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $student = $this->studentInScope((int) $id, $context['classIds']);
        if (! $student) {
            return redirect()->to(base_url('homeroom/my-class'))->with('error', 'Siswa tidak ditemukan di kelas binaan Anda.');
        }

        $post = $this->request->getPost() ?? [];
        $post['id'] = (int) $id;
        $classId = (int) ($post['class_id'] ?? 0);
        if (! in_array($classId, $context['classIds'], true)) {
            return redirect()->back()->withInput()->with('error', 'Kelas siswa harus berada dalam kelas binaan Anda.');
        }

        if (isset($post['phone'])) {
            $post['phone'] = $this->normalizeIdPhoneTo08((string) $post['phone']);
        }

        if (! empty($post['parent_id'])) {
            $parent = $this->db->table('users')
                ->select('id')
                ->where('id', (int) $post['parent_id'])
                ->where('role_id', $this->parentRoleId())
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $parent) {
                return redirect()->back()->withInput()->with('error', 'Akun orang tua tidak ditemukan.');
            }
        }

        $rules = StudentValidation::rulesForUpdate($student, $post);
        if (! $this->validateData($post, $rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = StudentValidation::sanitizeInput($post);
        $result = $this->studentService->updateStudent((int) $id, $data);
        if (! ($result['success'] ?? false)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal memperbarui siswa.');
        }

        if (! empty($student['user_id']) && ! empty($data['phone'])) {
            $this->userModel->update((int) $student['user_id'], ['phone' => $data['phone']]);
        }

        return redirect()->to(base_url('homeroom/students/' . (int) $id))
            ->with('success', $result['message'] ?? 'Siswa berhasil diperbarui.');
    }

    public function delete($id)
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $student = $this->studentInScope((int) $id, $context['classIds']);
        if (! $student) {
            return redirect()->to(base_url('homeroom/my-class'))->with('error', 'Siswa tidak ditemukan di kelas binaan Anda.');
        }

        $result = $this->studentService->deleteStudent((int) $id);
        if (! ($result['success'] ?? false)) {
            return redirect()->back()->with('error', $result['message'] ?? 'Gagal menghapus siswa.');
        }

        return redirect()->to(base_url('homeroom/my-class'))
            ->with('success', $result['message'] ?? 'Siswa berhasil dihapus.');
    }

    public function export()
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $classIds = $context['classIds'];
        if (empty($classIds)) {
            return redirect()->to(base_url('homeroom/my-class'))->with('error', 'Anda belum memiliki kelas binaan aktif.');
        }

        $rows = $this->db->table('students s')
            ->select('s.*, u.full_name, u.username, u.email, u.phone, c.class_name, c.grade_level, p.full_name AS parent_name, p.phone AS parent_phone, p.email AS parent_email')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->join('users p', 'p.id = s.parent_id AND p.deleted_at IS NULL', 'left')
            ->whereIn('s.class_id', $classIds)
            ->where('s.deleted_at', null)
            ->orderBy('c.grade_level', 'ASC')
            ->orderBy('c.class_name', 'ASC')
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        $filename = 'siswa_kelas_binaan_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Nama', 'Username', 'Email', 'Telepon', 'NISN', 'NIK', 'Kelas', 'Jenis Kelamin', 'Status', 'Orang Tua', 'Telepon Orang Tua', 'Email Orang Tua']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['full_name'] ?? '',
                $row['username'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['nisn'] ?? '',
                $row['nik'] ?? '',
                trim(($row['grade_level'] ?? '') . ' ' . ($row['class_name'] ?? '')),
                ($row['gender'] ?? '') === 'L' ? 'Laki-laki' : (($row['gender'] ?? '') === 'P' ? 'Perempuan' : ''),
                $row['status'] ?? '',
                $row['parent_name'] ?? '',
                $row['parent_phone'] ?? '',
                $row['parent_email'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    public function parents()
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        return view('homeroom_teacher/students/parents', [
            'pageTitle' => 'Akun Orang Tua',
            'parents' => $this->parentsInScope($context['classIds']),
            'classes' => $context['classes'],
            'class' => $context['class'],
        ]);
    }

    public function showParent($id)
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $parent = $this->parentInScope((int) $id, $context['classIds']);
        if (! $parent) {
            return redirect()->to(base_url('homeroom/parents'))->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        $children = $this->db->table('students s')
            ->select('s.id, s.nisn, s.nik, u.full_name, c.class_name, c.grade_level, s.status')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.parent_id', (int) $id)
            ->whereIn('s.class_id', $context['classIds'])
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('homeroom_teacher/students/parent_show', [
            'pageTitle' => 'Detail Orang Tua',
            'parent' => $parent,
            'children' => $children,
        ]);
    }

    public function createParent()
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        return view('homeroom_teacher/students/parent_form', [
            'pageTitle' => 'Tambah Akun Orang Tua',
            'mode' => 'create',
            'parent' => [],
            'action' => base_url('homeroom/parents/store'),
        ]);
    }

    public function storeParent()
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $post = $this->request->getPost() ?? [];
        $result = $this->createParentAccountFromPost($post);
        if (! ($result['success'] ?? false)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal membuat akun orang tua.');
        }

        return redirect()->to(base_url('homeroom/parents'))
            ->with('success', 'Akun orang tua berhasil dibuat. Hubungkan akun ini melalui form tambah/edit siswa.');
    }

    public function editParent($id)
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $parent = $this->parentInScope((int) $id, $context['classIds']);
        if (! $parent) {
            return redirect()->to(base_url('homeroom/parents'))->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        return view('homeroom_teacher/students/parent_form', [
            'pageTitle' => 'Edit Akun Orang Tua',
            'mode' => 'edit',
            'parent' => $parent,
            'action' => base_url('homeroom/parents/update/' . (int) $id),
        ]);
    }

    public function updateParent($id)
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $parent = $this->parentInScope((int) $id, $context['classIds']);
        if (! $parent) {
            return redirect()->to(base_url('homeroom/parents'))->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        $post = $this->request->getPost() ?? [];
        $fullName = trim((string) ($post['full_name'] ?? ''));
        $username = trim((string) ($post['username'] ?? ''));
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $phone = $this->normalizeIdPhoneTo08((string) ($post['phone'] ?? ''));

        if ($fullName === '' || $username === '') {
            return redirect()->back()->withInput()->with('error', 'Nama lengkap dan username wajib diisi.');
        }

        $duplicateUsername = $this->db->table('users')
            ->where('username', $username)
            ->where('id !=', (int) $id)
            ->where('deleted_at', null)
            ->countAllResults();
        if ($duplicateUsername > 0) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan.');
        }

        if ($email !== '') {
            $duplicateEmail = $this->db->table('users')
                ->where('email', $email)
                ->where('id !=', (int) $id)
                ->where('deleted_at', null)
                ->countAllResults();
            if ($duplicateEmail > 0) {
                return redirect()->back()->withInput()->with('error', 'Email sudah digunakan.');
            }
        }

        $payload = [
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'is_active' => ! empty($post['is_active']) ? 1 : 0,
        ];

        if (trim((string) ($post['password'] ?? '')) !== '') {
            $payload['password'] = (string) $post['password'];
        }

        $this->userModel->update((int) $id, $payload);

        return redirect()->to(base_url('homeroom/parents/' . (int) $id))
            ->with('success', 'Akun orang tua berhasil diperbarui.');
    }

    public function deleteParent($id)
    {
        $context = $this->homeroomContext();
        if ($redir = $this->guardHomeroom($context)) {
            return $redir;
        }

        $parent = $this->parentInScope((int) $id, $context['classIds']);
        if (! $parent) {
            return redirect()->to(base_url('homeroom/parents'))->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        $this->db->table('students')
            ->whereIn('class_id', $context['classIds'])
            ->where('parent_id', (int) $id)
            ->update(['parent_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);

        $remainingLinks = $this->db->table('students')
            ->where('parent_id', (int) $id)
            ->where('deleted_at', null)
            ->countAllResults();

        if ($remainingLinks === 0) {
            $this->userModel->delete((int) $id);
            return redirect()->to(base_url('homeroom/parents'))->with('success', 'Akun orang tua berhasil dihapus.');
        }

        return redirect()->to(base_url('homeroom/parents'))
            ->with('warning', 'Keterhubungan orang tua dengan siswa binaan Anda sudah dilepas. Akun tidak dihapus karena masih terhubung dengan siswa lain.');
    }

    private function createParentAccountFromPost(array $post): array
    {
        $fullName = trim((string) ($post['parent_full_name'] ?? $post['full_name'] ?? ''));
        $username = trim((string) ($post['parent_username'] ?? $post['username'] ?? ''));
        $email = strtolower(trim((string) ($post['parent_email'] ?? $post['email'] ?? '')));
        $phone = $this->normalizeIdPhoneTo08((string) ($post['parent_phone'] ?? $post['phone'] ?? ''));
        $password = trim((string) ($post['parent_password'] ?? $post['password'] ?? ''));

        if ($fullName === '' || $username === '') {
            return ['success' => false, 'message' => 'Nama lengkap dan username orang tua wajib diisi.'];
        }

        if ($password === '') {
            $password = 'orangtua123';
        }

        $duplicateUsername = $this->db->table('users')
            ->where('username', $username)
            ->where('deleted_at', null)
            ->countAllResults();
        if ($duplicateUsername > 0) {
            return ['success' => false, 'message' => 'Username orang tua sudah digunakan.'];
        }

        if ($email !== '') {
            $duplicateEmail = $this->db->table('users')
                ->where('email', $email)
                ->where('deleted_at', null)
                ->countAllResults();
            if ($duplicateEmail > 0) {
                return ['success' => false, 'message' => 'Email orang tua sudah digunakan.'];
            }
        }

        $parentId = (int) $this->userModel->insert([
            'role_id' => $this->parentRoleId(),
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'password' => $password,
            'full_name' => $fullName,
            'phone' => $phone !== '' ? $phone : null,
            'is_active' => 1,
        ], true);

        if ($parentId <= 0) {
            return ['success' => false, 'message' => 'Gagal menyimpan akun orang tua.'];
        }

        return ['success' => true, 'parent_id' => $parentId];
    }
}
