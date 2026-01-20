<?php

/**
 * File Path: app/Controllers/Koordinator/StudentController.php
 *
 * Koordinator BK • Student Controller
 * Kelola siswa untuk role Koordinator BK:
 * - Boleh: view list/detail, edit/update data akademik, import/export
 * - Tidak boleh: create/store manual, delete, changeClass (diblock di controller)
 *
 * Tambahan (2026-01-21):
 * - Fitur sinkron poin pelanggaran (mirip akun Guru BK):
 *   - Quick Sync: Tahun Ajaran aktif
 *   - Opsi Sync: pilih Tahun Ajaran (year_name) / periode tanggal
 *   - Bisa (opsional) mengikuti filter list saat ini (class/grade/status/gender/search)
 *
 * @package    SIB-K
 * @subpackage Controllers/Koordinator
 * @category   Student Management
 */

namespace App\Controllers\Koordinator;

use App\Services\StudentService;
use App\Services\ViolationService;
use App\Validation\StudentValidation;
use App\Libraries\ExcelImporter;
use App\Models\StudentModel;

class StudentController extends BaseKoordinatorController
{
    protected StudentService $studentService;
    protected ExcelImporter $excelImporter;
    protected StudentModel $studentModel;
    protected ViolationService $violationService;
    protected $db;

    public function __construct()
    {
        $this->studentService   = new StudentService();
        $this->excelImporter    = new ExcelImporter();
        $this->studentModel     = new StudentModel();
        $this->violationService = new ViolationService();
        $this->db               = \Config\Database::connect();
    }

    /**
     * Helper: Blok aksi CRUD manual yang tidak diperbolehkan untuk Koordinator
     */
    private function denyManualCrud(string $action = 'Aksi ini')
    {
        return redirect()->to(base_url('koordinator/students'))
            ->with('error', $action . ' tidak tersedia untuk akun Koordinator. Gunakan fitur Import jika perlu menambahkan data siswa.');
    }

    /**
     * Helper: Permission untuk edit/update siswa.
     * Mengakomodasi perbedaan setup permission (manage_students vs manage_academic_data).
     */
    private function requireManageStudentsPermission()
    {
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('has_permission')) {
            if (!has_permission('manage_students') && !has_permission('manage_academic_data')) {
                return redirect()->to(base_url('koordinator/students'))
                    ->with('error', 'Anda tidak memiliki izin untuk mengubah data siswa.');
            }
            return null;
        }

        // Fallback: gunakan require_permission jika tersedia
        if (function_exists('require_permission')) {
            require_permission('manage_students');
        }

        return null;
    }

    /**
     * Permission khusus untuk sinkron poin.
     * (umumnya ini selevel update data, tapi kita izinkan juga jika punya manage_violations).
     */
    private function requireSyncPermission()
    {
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('has_permission')) {
            if (
                !has_permission('manage_students') &&
                !has_permission('manage_academic_data') &&
                !has_permission('manage_violations')
            ) {
                return redirect()->to(base_url('koordinator/students'))
                    ->with('error', 'Anda tidak memiliki izin untuk sinkronisasi poin pelanggaran.');
            }
            return null;
        }

        if (function_exists('require_permission')) {
            // fallback paling aman: treat as student update privilege
            require_permission('manage_students');
        }

        return null;
    }

    /**
     * Display students list
     */
    public function index()
    {
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('view_all_students');
        }

        $filters = [
            'class_id'    => $this->request->getGet('class_id'),
            'grade_level' => $this->request->getGet('grade_level'),
            'status'      => $this->request->getGet('status'),
            'gender'      => $this->request->getGet('gender'),
            'search'      => $this->request->getGet('search'),
            'order_by'    => $this->request->getGet('order_by') ?? 'students.created_at',
            'order_dir'   => $this->request->getGet('order_dir') ?? 'DESC',
        ];

        // DataTables pageLength (untuk view), bukan untuk DB pagination.
        $dtPageLength = (int) ($this->request->getGet('per_page') ?? 10);
        if ($dtPageLength <= 0) $dtPageLength = 10;
        if ($dtPageLength > 200) $dtPageLength = 200;

        // ✅ Mode DataTables: ambil semua (perPage <= 0)
        $studentsData = $this->studentService->getAllStudents($filters, 0);
        $classes      = $this->studentService->getAvailableClasses();
        $stats        = $this->studentService->getStudentStatistics();

        // Opsi Tahun Ajaran untuk modal sinkron
        $academicYearOptions = [];
        try {
            if (method_exists($this->violationService, 'getAcademicYearOptions')) {
                $academicYearOptions = (array) $this->violationService->getAcademicYearOptions();
            }
        } catch (\Throwable $e) {
            $academicYearOptions = [];
        }

        $data = [
            'title'          => 'Manajemen Siswa',
            'page_title'     => 'Manajemen Siswa',
            'breadcrumb'     => [
                ['title' => 'Koordinator', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Siswa', 'link' => null],
            ],

            // data utama
            'students'       => $studentsData['students'],
            'pager'          => $studentsData['pager'], // null pada mode DataTables
            'classes'        => $classes,
            'stats'          => $stats,
            'filters'        => $filters,

            // options
            'gender_options' => StudentValidation::getGenderOptions(),
            'status_options' => StudentValidation::getStatusOptions(),

            // DataTables
            'perPage'        => $dtPageLength,

            // dropdown TA (samakan key dengan counselor view yang fleksibel)
            'academicYears'           => $academicYearOptions,
            'academic_years'          => $academicYearOptions,
            'year_options'            => $academicYearOptions,
            'academic_year_options'   => $academicYearOptions,
        ];

        return view('koordinator/students/index', $data);
    }

    /**
     * Display create student form (DIBLOK untuk Koordinator)
     */
    public function create()
    {
        return $this->denyManualCrud('Tambah siswa manual');
    }

    /**
     * Store new student (DIBLOK untuk Koordinator)
     */
    public function store()
    {
        return $this->denyManualCrud('Tambah siswa manual');
    }

    /**
     * Display student profile
     */
    public function profile($id)
    {
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('view_all_students');
        }

        $student = $this->studentService->getStudentById((int) $id);

        if (!$student) {
            return redirect()->to(base_url('koordinator/students'))
                ->with('error', 'Data siswa tidak ditemukan');
        }

        $data = [
            'title'      => 'Profil Siswa',
            'page_title' => 'Profil Siswa',
            'breadcrumb' => [
                ['title' => 'Koordinator', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Siswa', 'link' => base_url('koordinator/students')],
                ['title' => 'Profil Siswa', 'link' => null],
            ],
            'student' => $student,
        ];

        return view('koordinator/students/profile', $data);
    }

    /**
     * Display edit student form
     */
    public function edit($id)
    {
        $deny = $this->requireManageStudentsPermission();
        if ($deny) return $deny;

        $student = $this->studentService->getStudentById((int) $id);

        if (!$student) {
            return redirect()->to(base_url('koordinator/students'))
                ->with('error', 'Data siswa tidak ditemukan');
        }

        $classes = $this->studentService->getAvailableClasses();
        $parents = $this->studentService->getAvailableParents();

        $data = [
            'title'            => 'Edit Data Siswa',
            'page_title'       => 'Edit Siswa',
            'breadcrumb'       => [
                ['title' => 'Koordinator', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Siswa', 'link' => base_url('koordinator/students')],
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

        return view('koordinator/students/edit', $data);
    }

    /**
     * Update student data
     */
    public function update(int $id)
    {
        $deny = $this->requireManageStudentsPermission();
        if ($deny) return $deny;

        $postData = $this->request->getPost() ?? [];

        $result = $this->studentService->updateStudent($id, $postData);

        if (!($result['success'] ?? false)) {
            $errors = $result['errors'] ?? $result['validation_errors'] ?? null;

            $redir = redirect()->back()->withInput();
            if (is_array($errors) && !empty($errors)) {
                $redir = $redir->with('errors', $errors);
            }

            return $redir->with('error', $result['message'] ?? 'Gagal memperbarui data siswa.');
        }

        return redirect()->to(base_url('koordinator/students'))
            ->with('success', $result['message'] ?? 'Data siswa berhasil diperbarui.');
    }

    /**
     * Delete student (DIBLOK untuk Koordinator)
     */
    public function delete($id)
    {
        return $this->denyManualCrud('Hapus siswa');
    }

    /**
     * Change student class (DIBLOK untuk Koordinator)
     */
    public function changeClass($id)
    {
        return $this->denyManualCrud('Ubah kelas siswa');
    }

    /**
     * Export students to CSV
     */
    public function export()
    {
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('import_export_data');
        }

        $filters = [
            'class_id'    => $this->request->getGet('class_id'),
            'grade_level' => $this->request->getGet('grade_level'),
            'status'      => $this->request->getGet('status'),
            'gender'      => $this->request->getGet('gender'),
            'search'      => $this->request->getGet('search'),
        ];

        $studentsData = $this->studentService->getAllStudents($filters, 0);

        $exportData = [];
        foreach ($studentsData['students'] as $student) {
            $exportData[] = [
                'ID'                => $student['id'],
                'NISN'              => $student['nisn'],
                'NIS'               => $student['nis'],
                'Nama Lengkap'      => $student['full_name'],
                'Username'          => $student['username'],
                'Email'             => $student['email'],
                'Jenis Kelamin'     => ($student['gender'] ?? '') == 'L' ? 'Laki-laki' : 'Perempuan',
                'Kelas'             => $student['class_name'] ?? '-',
                'Tingkat'           => $student['grade_level'] ?? '-',
                'Tempat Lahir'      => $student['birth_place'] ?? '-',
                'Tanggal Lahir'     => !empty($student['birth_date']) ? date('d/m/Y', strtotime($student['birth_date'])) : '-',
                'Agama'             => $student['religion'] ?? '-',
                'Alamat'            => $student['address'] ?? '-',
                'Telepon'           => $student['phone'] ?? '-',
                'Status'            => $student['status'] ?? '-',
                'Poin Pelanggaran'  => (int) ($student['total_violation_points'] ?? 0),
                'Tanggal Masuk'     => !empty($student['admission_date']) ? date('d/m/Y', strtotime($student['admission_date'])) : '-',
                'Terdaftar'         => !empty($student['created_at']) ? date('d/m/Y H:i', strtotime($student['created_at'])) : '-',
            ];
        }

        $filename = 'students_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM untuk Excel UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if (!empty($exportData)) {
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
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('view_all_students');
        }

        $keyword = $this->request->getGet('q');
        if (empty($keyword)) {
            return $this->response->setJSON(['results' => []]);
        }

        $filters      = ['search' => $keyword];
        $studentsData = $this->studentService->getAllStudents($filters, 0);

        $results = [];
        foreach (array_slice($studentsData['students'], 0, 10) as $student) {
            $results[] = [
                'id'     => $student['id'],
                'text'   => ($student['full_name'] ?? '-') . ' (' . ($student['nisn'] ?? '-') . ')',
                'nisn'   => $student['nisn'] ?? '-',
                'nis'    => $student['nis'] ?? '-',
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
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('view_all_students');
        }

        $filters = [
            'class_id' => (int) $classId,
            'status'   => 'Aktif',
        ];

        $studentsData = $this->studentService->getAllStudents($filters, 0);

        $students = [];
        foreach ($studentsData['students'] as $student) {
            $students[] = [
                'id'        => $student['id'],
                'user_id'   => $student['user_id'],
                'nisn'      => $student['nisn'],
                'nis'       => $student['nis'],
                'full_name' => $student['full_name'],
                'gender'    => $student['gender'],
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
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('import_export_data');
        }

        $classes = $this->studentService->getAvailableClasses();

        $data = [
            'title'      => 'Impor Data Siswa',
            'page_title' => 'Impor Siswa',
            'breadcrumb' => [
                ['title' => 'Koordinator', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Siswa', 'link' => base_url('koordinator/students')],
                ['title' => 'Impor Siswa', 'link' => null],
            ],
            'classes'    => $classes,
        ];

        return view('koordinator/students/import', $data);
    }

    /**
     * Download Excel import template
     */
    public function downloadTemplate()
    {
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('import_export_data');
        }

        try {
            $filename = 'template_import_siswa_' . date('Y-m-d') . '.xlsx';
            $savePath = WRITEPATH . 'uploads/' . $filename;

            $this->excelImporter->generateTemplate($savePath);

            if (!file_exists($savePath)) {
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
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('import_export_data');
        }

        $rules = StudentValidation::importRules();

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('import_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File yang diupload tidak valid.');
        }

        try {
            $uploadPath = WRITEPATH . 'uploads/imports/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $newFileName = 'import_' . date('YmdHis') . '_' . uniqid() . '.' . $file->getExtension();

            if (!$file->move($uploadPath, $newFileName)) {
                throw new \Exception('Gagal memindahkan file upload.');
            }

            $filePath = $uploadPath . $newFileName;

            $results = $this->excelImporter->importStudents($filePath);

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $total  = (int) ($results['total_rows'] ?? 0);
            $ok     = (int) ($results['success'] ?? 0);
            $failed = (int) ($results['failed'] ?? 0);

            $message = sprintf(
                'Import selesai! Total: %d baris, Berhasil: %d, Gagal: %d',
                $total,
                $ok,
                $failed
            );

            if ($failed > 0) {
                session()->setFlashdata('import_errors', $results['errors'] ?? []);

                if ($ok > 0) {
                    session()->setFlashdata('warning', $message);
                } else {
                    session()->setFlashdata('error', 'Import gagal! ' . $message);
                }
            } else {
                session()->setFlashdata('success', $message);
            }

            if (!empty($results['warnings'])) {
                session()->setFlashdata('import_warnings', $results['warnings']);
            }

            if ($failed > 0 || !empty($results['warnings'])) {
                return redirect()->to(base_url('koordinator/students/import'));
            }

            return redirect()->to(base_url('koordinator/students'));
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
        try {
            helper('permission');
        } catch (\Throwable $e) {
            // ignore
        }

        if (function_exists('require_permission')) {
            require_permission('view_all_students');
        }

        $stats = $this->studentService->getStudentStatistics();

        return $this->response->setJSON([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    // ==========================================================
    // Sinkron poin pelanggaran (mirip Counselor)
    // ==========================================================

    private function normalizeDate($date): ?string
    {
        $date = trim((string) ($date ?? ''));
        if ($date === '') return null;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return null;
        return $date;
    }

    /**
     * Ambil daftar student_id untuk sync.
     * Default: semua siswa sesuai filter (class/grade/status/gender/search).
     */
    private function getStudentIdsForSync(array $filters = []): array
    {
        $qb = $this->db->table('students s')
            ->select('s.id')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'inner')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.deleted_at', null);

        $classId    = trim((string) ($filters['class_id'] ?? ''));
        $gradeLevel = trim((string) ($filters['grade_level'] ?? ''));
        $status     = trim((string) ($filters['status'] ?? ''));
        $gender     = trim((string) ($filters['gender'] ?? ''));
        $search     = trim((string) ($filters['search'] ?? ''));

        if ($classId !== '') {
            $qb->where('s.class_id', (int) $classId);
        }
        if ($gradeLevel !== '') {
            $qb->where('c.grade_level', $gradeLevel);
        }
        if ($status !== '') {
            $qb->where('s.status', $status);
        }
        if ($gender !== '') {
            $qb->where('s.gender', $gender);
        }
        if ($search !== '') {
            $qb->groupStart()
                ->like('u.full_name', $search)
                ->orLike('u.email', $search)
                ->orLike('s.nis', $search)
                ->orLike('s.nisn', $search)
            ->groupEnd();
        }

        $rows = $qb->get()->getResultArray();

        $ids = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id > 0) $ids[] = $id;
        }
        return $ids;
    }

    /**
     * POST /koordinator/students/sync-violation-points
     * Mode:
     * - active: Tahun Ajaran aktif (default)
     * - year: Tahun Ajaran dipilih (year_name)
     * - range: periode custom date_from/date_to
     */
    public function syncViolationPoints()
    {
        // Permission
        if (function_exists('require_permission')) {
            require_permission('view_all_students');
        }
        $deny = $this->requireSyncPermission();
        if ($deny) return $deny;

        $syncMode = trim((string) ($this->request->getPost('sync_mode') ?? 'active'));
        if ($syncMode === '') $syncMode = 'active';

        $yearName = trim((string) ($this->request->getPost('academic_year') ?? ''));

        $dateFrom = $this->normalizeDate($this->request->getPost('date_from'));
        $dateTo   = $this->normalizeDate($this->request->getPost('date_to'));

        // Optional: sync mengikuti filter list (kita ambil dari hidden input)
        $studentFilters = [
            'class_id'    => $this->request->getPost('class_id'),
            'grade_level' => $this->request->getPost('grade_level'),
            'status'      => $this->request->getPost('status'),
            'gender'      => $this->request->getPost('gender'),
            'search'      => $this->request->getPost('search'),
        ];

        $studentIds = $this->getStudentIdsForSync($studentFilters);
        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'Tidak ada siswa yang bisa disinkronkan (sesuai filter saat ini).');
        }

        // Build compute filters untuk StudentService
        $computeFilters = [
            'include_cancelled' => false,
        ];

        $label = '';

        if ($syncMode === 'range') {
            if (!$dateFrom || !$dateTo) {
                return redirect()->back()->with('error', 'Mode periode dipilih, tapi tanggal belum lengkap.');
            }
            if ($dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
            $computeFilters['date_from'] = $dateFrom;
            $computeFilters['date_to']   = $dateTo;
            $label = "Periode {$dateFrom} s/d {$dateTo}";
        } elseif ($syncMode === 'year') {
            if ($yearName === '') {
                return redirect()->back()->with('error', 'Mode Tahun Ajaran dipilih, tapi Tahun Ajaran belum dipilih.');
            }
            $computeFilters['year_name'] = $yearName;
            $label = "Tahun Ajaran {$yearName}";
        } else {
            // active
            $activeYear = $this->studentService->getActiveAcademicYearName();
            $label = $activeYear ? "Tahun Ajaran Aktif ({$activeYear})" : "Tahun Ajaran Aktif";
        }

        $payload = [];
        $updated = 0;

        try {
            $chunkSize = 500;

            foreach ($studentIds as $sid) {
                $sid = (int) $sid;
                if ($sid <= 0) continue;

                $points = (int) $this->studentService->computeViolationPointsByRange($sid, $computeFilters);

                $payload[] = [
                    'id'                    => $sid,
                    'total_violation_points' => max(0, $points),
                ];

                if (count($payload) >= $chunkSize) {
                    $this->db->table('students')->updateBatch($payload, 'id');
                    $updated += count($payload);
                    $payload = [];
                }
            }

            if (!empty($payload)) {
                $this->db->table('students')->updateBatch($payload, 'id');
                $updated += count($payload);
            }

            return redirect()->back()->with(
                'success',
                "Sinkronisasi poin pelanggaran berhasil untuk {$updated} siswa. ({$label})"
            );
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator StudentController::syncViolationPoints - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal sinkronisasi poin: ' . $e->getMessage());
        }
    }
}
