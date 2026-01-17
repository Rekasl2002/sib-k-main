<?php

/**
 * File Path: app/Controllers/Admin/StudentController.php
 *
 * Student Controller
 * Handle CRUD operations untuk Student management
 *
 * Catatan penting (Normalisasi Nama):
 * - Setelah kolom students.full_name dihapus, sumber nama siswa sebaiknya dari users.full_name (JOIN lewat students.user_id).
 * - Controller ini dibuat "tahan banting" dengan fallback key nama agar tidak error jika service mengubah alias field.
 *
 * Catatan penting (Avatar / Foto Profil):
 * - Bug umum: jika view memanggil user_avatar(null), helper akan fallback ke session profile_photo -> semua baris ikut foto user login.
 * - Controller ini menormalkan setiap row students pada index() agar profile_photo selalu terisi URL final (default-avatar jika kosong).
 *
 * Tambahan (2026-01-07):
 * - Halaman admin/students menggunakan "pagination hanya di VIEW" (DataTables).
 * - Jadi index() mengambil semua data terfilter (tanpa paginate), dan $pager = null.
 *
 * @package    SIB-K
 * @subpackage Controllers/Admin
 * @category   Student Management
 * @author     Development Team
 * @created    2025-01-05
 * @updated    2026-01-07
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\StudentService;
use App\Validation\StudentValidation;
use App\Libraries\ExcelImporter;
use App\Models\StudentModel;

class StudentController extends BaseController
{
    protected $studentService;
    protected $excelImporter;
    protected $studentModel;

    public function __construct()
    {
        // Helper yang sering kepakai (permission + url untuk base_url)
        helper(['permission', 'url']);

        $this->studentService = new StudentService();
        $this->excelImporter  = new ExcelImporter();
        $this->studentModel   = new StudentModel();
    }

    /**
     * Ambil nama siswa dari data hasil query/service dengan fallback beberapa key.
     * Ini berguna setelah kolom students.full_name dihapus (nama idealnya dari users.full_name).
     */
    private function getStudentDisplayName(array $student): string
    {
        // Urutan fallback: sesuaikan jika service kamu menggunakan alias berbeda
        $candidates = [
            'full_name',          // paling umum (biasanya sudah di-join dari users)
            'student_full_name',  // alias yang sering dipakai di beberapa service
            'user_full_name',     // kemungkinan alias lain
            'name',               // jaga-jaga
        ];

        foreach ($candidates as $key) {
            if (isset($student[$key]) && trim((string) $student[$key]) !== '') {
                return (string) $student[$key];
            }
        }

        return '-';
    }

    /**
     * Resolve URL avatar siswa TANPA bergantung session (anti "semua ikut foto user login").
     * - Jika $photo kosong -> default avatar (public/assets/images/users/default-avatar.svg)
     * - Jika $photo URL -> return apa adanya
     * - Jika $photo path/filename -> cek beberapa kandidat di public (FCPATH)
     */
    private function resolveStudentAvatarUrl(?string $photo, ?int $userId = null): string
    {
        $defaultRel = 'assets/images/users/default-avatar.svg';
        $defaultUrl = base_url($defaultRel);

        $raw = trim((string) ($photo ?? ''));
        if ($raw === '') {
            return $defaultUrl;
        }

        // buang query string (?v=...)
        $rawNoQ = (string) strtok($raw, '?');
        $rawNoQ = trim($rawNoQ);

        if ($rawNoQ === '') {
            return $defaultUrl;
        }

        // Jika sudah URL penuh
        if (preg_match('~^https?://~i', $rawNoQ)) {
            return $rawNoQ;
        }

        // Normalisasi slash
        $rel = ltrim(str_replace('\\', '/', $rawNoQ), '/');

        // Kandidat lokasi file (public/FCPATH)
        $candidates = [];
        $candidates[] = $rel;

        $base = basename($rel);

        // Kalau hanya filename, coba folder umum
        if (strpos($rel, '/') === false) {
            if ($userId) {
                $candidates[] = 'uploads/profile_photos/' . (int) $userId . '/' . $base;
                $candidates[] = 'uploads/profile_photos/' . (int) $userId . '/' . $rel;
            }
            $candidates[] = 'uploads/profile_photos/' . $rel;
            $candidates[] = 'uploads/users/' . $rel;
            $candidates[] = 'uploads/profiles/' . $rel;
        } else {
            // Kalau sudah mengandung folder, dan kebetulan formatnya uploads/profile_photos/{id}/...
            if (!$userId && preg_match('~^uploads/profile_photos/(\d+)/~', strtolower($rel), $m)) {
                $userId = (int) $m[1];
            }

            // coba versi basename-nya di folder lain
            if ($userId) {
                $candidates[] = 'uploads/profile_photos/' . (int) $userId . '/' . $base;
            }
            $candidates[] = 'uploads/profile_photos/' . $base;
            $candidates[] = 'uploads/users/' . $base;
            $candidates[] = 'uploads/profiles/' . $base;
        }

        foreach (array_unique($candidates) as $tryRel) {
            $tryRel = ltrim(str_replace('\\', '/', $tryRel), '/');
            $abs = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $tryRel);

            if (is_file($abs)) {
                return base_url($tryRel);
            }
        }

        return $defaultUrl;
    }

    /**
     * Normalisasi row siswa agar view tidak "ketarik" session photo.
     * - Pastikan 'full_name' terisi untuk tampilan
     * - Pastikan 'profile_photo' terisi URL final (default bila kosong/tidak valid)
     */
    private function normalizeStudentRow(array $student): array
    {
        $displayName = $this->getStudentDisplayName($student);

        // Pastikan key full_name minimal ada supaya view tidak bingung
        if (!isset($student['full_name']) || trim((string) $student['full_name']) === '') {
            $student['full_name'] = $displayName;
        }

        $userId = isset($student['user_id']) ? (int) $student['user_id'] : null;

        // Resolve jadi URL final. Ini penting agar view yang memanggil user_avatar(...) tidak fallback ke session.
        $student['profile_photo'] = $this->resolveStudentAvatarUrl($student['profile_photo'] ?? null, $userId);

        return $student;
    }

    /**
     * Clamp nilai per_page (untuk default pageLength di DataTables pada VIEW)
     */
    private function clampPerPage(?int $perPage): int
    {
        $pp = (int) ($perPage ?? 10);
        if ($pp <= 0) $pp = 10;
        if ($pp > 200) $pp = 200; // batas aman
        return $pp;
    }

    /**
     * Display students list
     * Catatan: Pagination hanya di VIEW (DataTables).
     */
    public function index()
    {
        require_permission('view_all_students');

        $filters = [
            'class_id'    => $this->request->getGet('class_id'),
            'grade_level' => $this->request->getGet('grade_level'),
            'status'      => $this->request->getGet('status'),
            'gender'      => $this->request->getGet('gender'),
            'search'      => $this->request->getGet('search'),
            'order_by'    => $this->request->getGet('order_by') ?? 'students.created_at',
            'order_dir'   => $this->request->getGet('order_dir') ?? 'DESC',
        ];

        // Per page sekarang hanya untuk default DataTables pageLength di VIEW (opsional)
        $perPage = $this->clampPerPage((int) ($this->request->getGet('per_page') ?? 10));

        /**
         * PENTING:
         * Pagination hanya di VIEW (DataTables), jadi ambil semua data terfilter.
         * StudentService sudah mendukung: $perPage <= 0 => findAll(), pager = null
         */
        $studentsData = $this->studentService->getAllStudents($filters, 0);

        $classes = $this->studentService->getAvailableClasses();
        $stats   = $this->studentService->getStudentStatistics();

        $rawStudents = $studentsData['students'] ?? [];
        if (!is_array($rawStudents)) {
            $rawStudents = [];
        }

        // Normalisasi row untuk nama + avatar (anti ikut session)
        $students = [];
        foreach ($rawStudents as $row) {
            $students[] = $this->normalizeStudentRow((array) $row);
        }

        $data = [
            'title'           => 'Manajemen Siswa',
            'page_title'      => 'Manajemen Siswa',
            'breadcrumb'      => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Siswa', 'link' => null],
            ],
            'students'        => $students,

            // Karena pagination di VIEW:
            'pager'           => null,
            'pagerGroup'      => 'default',

            // dipakai view untuk hidden input/per_page atau untuk DataTables pageLength default
            'perPage'         => $perPage,

            'classes'         => $classes,
            'stats'           => $stats,
            'filters'         => $filters,
            'gender_options'  => StudentValidation::getGenderOptions(),
            'status_options'  => StudentValidation::getStatusOptions(),
        ];

        return view('admin/students/index', $data);
    }

    /**
     * Display create student form
     */
    public function create()
    {
        require_permission('manage_students');

        $classes        = $this->studentService->getAvailableClasses();
        $parents        = $this->studentService->getAvailableParents();
        $availableUsers = $this->studentService->getAvailableStudentUsers();

        $data = [
            'title'            => 'Tambah Siswa',
            'page_title'       => 'Tambah Siswa',
            'breadcrumb'       => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Siswa', 'link' => base_url('admin/students')],
                ['title' => 'Tambah', 'link' => null],
            ],
            'classes'          => $classes,
            'parents'          => $parents,
            'availableUsers'   => $availableUsers,
            'gender_options'   => StudentValidation::getGenderOptions(),
            'religion_options' => StudentValidation::getReligionOptions(),
            'status_options'   => StudentValidation::getStatusOptions(),
            'validation'       => \Config\Services::validation(),
        ];

        return view('admin/students/create', $data);
    }

    /**
     * Store new student
     */
    public function store()
    {
        require_permission('manage_students');

        $createWithUser = $this->request->getPost('create_with_user') == '1';
        $rules = $createWithUser
            ? StudentValidation::createWithUserRules()
            : StudentValidation::createRules();

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = StudentValidation::sanitizeInput($this->request->getPost());

        $result = $createWithUser
            ? $this->studentService->createStudentWithUser($data)
            : $this->studentService->createStudent($data);

        if (! $result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/students')->with('success', $result['message']);
    }

    /**
     * Display student profile
     */
    public function profile($id)
    {
        require_permission('view_all_students');

        $student = $this->studentService->getStudentById((int) $id);

        if (! $student) {
            return redirect()->to('admin/students')->with('error', 'Data siswa tidak ditemukan');
        }

        $data = [
            'title'      => 'Profil Siswa',
            'page_title' => 'Profil Siswa',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Siswa', 'link' => base_url('admin/students')],
                ['title' => 'Profil', 'link' => null],
            ],
            'student' => $student,
        ];

        return view('admin/students/profile', $data);
    }

    /**
     * Display edit student form
     */
    public function edit($id)
    {
        require_permission('manage_students');

        $student = $this->studentService->getStudentById((int) $id);

        if (! $student) {
            return redirect()->to('admin/students')->with('error', 'Data siswa tidak ditemukan');
        }

        $classes = $this->studentService->getAvailableClasses();
        $parents = $this->studentService->getAvailableParents();

        $data = [
            'title'            => 'Edit Siswa',
            'page_title'       => 'Edit Siswa',
            'breadcrumb'       => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Siswa', 'link' => base_url('admin/students')],
                ['title' => 'Edit', 'link' => null],
            ],
            'student'          => $student,
            'classes'          => $classes,
            'parents'          => $parents,
            'gender_options'   => StudentValidation::getGenderOptions(),
            'religion_options' => StudentValidation::getReligionOptions(),
            'status_options'   => StudentValidation::getStatusOptions(),
            'validation'       => \Config\Services::validation(),
        ];

        return view('admin/students/edit', $data);
    }

    /**
     * Update student data
     */
    public function update(int $id)
    {
        require_permission('manage_students');

        $postData = $this->request->getPost();

        $result = $this->studentService->updateStudent($id, $postData);

        if (! $result['success']) {
            return redirect()->back()->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/students')->with('success', $result['message']);
    }

    /**
     * Delete student
     */
    public function delete($id)
    {
        require_permission('manage_students');

        $result = $this->studentService->deleteStudent((int) $id);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->to('admin/students')->with('success', $result['message']);
    }

    /**
     * Change student class
     */
    public function changeClass($id)
    {
        require_permission('manage_students');

        $newClassId = $this->request->getPost('class_id');

        if (empty($newClassId)) {
            return redirect()->back()->with('error', 'Kelas baru harus dipilih');
        }

        $result = $this->studentService->changeClass((int) $id, (int) $newClassId);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Export students to CSV
     */
    public function export()
    {
        require_permission('view_all_students');

        $filters = [
            'class_id'    => $this->request->getGet('class_id'),
            'grade_level' => $this->request->getGet('grade_level'),
            'status'      => $this->request->getGet('status'),
            'gender'      => $this->request->getGet('gender'),
            'search'      => $this->request->getGet('search'),
        ];

        // Export umumnya butuh semua data sesuai filter
        $studentsData = $this->studentService->getAllStudents($filters, 0);

        $exportData = [];
        foreach (($studentsData['students'] ?? []) as $student) {
            $student = (array) $student;

            $exportData[] = [
                'ID'                => $student['id'] ?? null,
                'NISN'              => $student['nisn'] ?? null,
                'NIS'               => $student['nis'] ?? null,
                'Nama Lengkap'      => $this->getStudentDisplayName($student),
                'Username'          => $student['username'] ?? '-',
                'Email'             => $student['email'] ?? '-',
                'Jenis Kelamin'     => ($student['gender'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan',
                'Kelas'             => $student['class_name'] ?? '-',
                'Tingkat'           => $student['grade_level'] ?? '-',
                'Tempat Lahir'      => $student['birth_place'] ?? '-',
                'Tanggal Lahir'     => ! empty($student['birth_date']) ? date('d/m/Y', strtotime($student['birth_date'])) : '-',
                'Agama'             => $student['religion'] ?? '-',
                'Alamat'            => $student['address'] ?? '-',
                'Telepon'           => $student['phone'] ?? '-',
                'Status'            => $student['status'] ?? '-',
                'Poin Pelanggaran'  => $student['total_violation_points'] ?? 0,
                'Tanggal Masuk'     => ! empty($student['admission_date']) ? date('d/m/Y', strtotime($student['admission_date'])) : '-',
                'Terdaftar'         => ! empty($student['created_at']) ? date('d/m/Y H:i', strtotime($student['created_at'])) : '-',
            ];
        }

        $filename = 'students_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM untuk Excel UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if (! empty($exportData)) {
            fputcsv($output, array_keys($exportData[0]));
        }

        foreach ($exportData as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Search students via AJAX (autocomplete)
     */
    public function search()
    {
        require_permission('view_all_students');

        $keyword = $this->request->getGet('q');

        if (empty($keyword)) {
            return $this->response->setJSON(['results' => []]);
        }

        $filters      = ['search' => $keyword];
        $studentsData = $this->studentService->getAllStudents($filters, 10);

        $results = [];
        foreach (($studentsData['students'] ?? []) as $student) {
            $student = (array) $student;
            $name = $this->getStudentDisplayName($student);

            $results[] = [
                'id'     => $student['id'] ?? null,
                'text'   => $name . ' (' . ($student['nisn'] ?? '-') . ')',
                'nisn'   => $student['nisn'] ?? null,
                'nis'    => $student['nis'] ?? null,
                'class'  => $student['class_name'] ?? '-',
                'status' => $student['status'] ?? '-',
            ];
        }

        return $this->response->setJSON(['results' => $results]);
    }

    /**
     * Get students by class via AJAX
     */
    public function getByClass($classId)
    {
        require_permission('view_all_students');

        $filters = [
            'class_id' => (int) $classId,
            'status'   => 'Aktif',
        ];

        $studentsData = $this->studentService->getAllStudents($filters, 100);

        $students = [];
        foreach (($studentsData['students'] ?? []) as $student) {
            $student = (array) $student;

            $students[] = [
                'id'        => $student['id'] ?? null,
                'user_id'   => $student['user_id'] ?? null,
                'nisn'      => $student['nisn'] ?? null,
                'nis'       => $student['nis'] ?? null,
                'full_name' => $this->getStudentDisplayName($student),
                'gender'    => $student['gender'] ?? null,
            ];
        }

        return $this->response->setJSON([
            'success'  => true,
            'students' => $students,
        ]);
    }

    /**
     * Display import students form
     */
    public function import()
    {
        require_permission('manage_students');

        $classes = $this->studentService->getAvailableClasses();

        $data = [
            'title'      => 'Impor Siswa',
            'page_title' => 'Impor Siswa',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Siswa', 'link' => base_url('admin/students')],
                ['title' => 'Impor Siswa', 'link' => null],
            ],
            'classes'    => $classes,
        ];

        return view('admin/students/import', $data);
    }

    /**
     * Download Excel import template
     */
    public function downloadTemplate()
    {
        require_permission('manage_students');

        try {
            $filename = 'template_import_siswa_' . date('Y-m-d') . '.xlsx';
            $savePath = WRITEPATH . 'uploads/' . $filename;

            $this->excelImporter->generateTemplate($savePath);

            if (! file_exists($savePath)) {
                return redirect()->back()->with('error', 'Gagal membuat template. File tidak ditemukan.');
            }

            return $this->response->download($savePath, null)
                ->setFileName($filename)
                ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } catch (\Exception $e) {
            log_message('error', 'Error generating template: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membuat template: ' . $e->getMessage());
        }
    }

    /**
     * Process student import from Excel file
     */
    public function doImport()
    {
        require_permission('manage_students');

        $rules = StudentValidation::importRules();

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'File yang diupload tidak valid.');
        }

        try {
            $uploadPath = WRITEPATH . 'uploads/imports/';
            if (! is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $newFileName = 'import_' . date('YmdHis') . '_' . uniqid() . '.' . $file->getExtension();

            if (! $file->move($uploadPath, $newFileName)) {
                throw new \Exception('Gagal memindahkan file upload.');
            }

            $filePath = $uploadPath . $newFileName;

            $results = $this->excelImporter->importStudents($filePath);

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $message = sprintf(
                'Import selesai! Total: %d baris, Berhasil: %d, Gagal: %d',
                $results['total_rows'] ?? 0,
                $results['success']    ?? 0,
                $results['failed']     ?? 0
            );

            $failedCount = (int) ($results['failed'] ?? 0);

            if ($failedCount > 0) {
                session()->setFlashdata('import_errors', $results['errors'] ?? []);

                if (! empty($results['success'])) {
                    session()->setFlashdata('warning', $message);
                } else {
                    session()->setFlashdata('error', 'Import gagal! ' . $message);
                }
            } else {
                session()->setFlashdata('success', $message);
            }

            if (! empty($results['warnings'])) {
                session()->setFlashdata('import_warnings', $results['warnings']);
            }

            return redirect()->to('admin/students');
        } catch (\Exception $e) {
            log_message('error', 'Import error: ' . $e->getMessage());

            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }

    /**
     * Get student statistics via AJAX
     */
    public function getStats()
    {
        require_permission('view_all_students');

        $stats = $this->studentService->getStudentStatistics();

        return $this->response->setJSON([
            'success' => true,
            'data'    => $stats,
        ]);
    }
}
