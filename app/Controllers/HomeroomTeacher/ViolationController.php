<?php

/**
 * File Path: app/Controllers/HomeroomTeacher/ViolationController.php
 *
 * Homeroom Teacher Violation Controller
 * Mengelola pelanggaran siswa di kelas yang diampu oleh wali kelas
 *
 * @package    SIB-K
 * @subpackage Controllers/HomeroomTeacher
 * @category   Controller
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Models\ViolationModel;
use App\Models\ViolationCategoryModel;

class ViolationController extends BaseController
{
    protected $classModel;
    protected $studentModel;
    protected $violationModel;
    protected $categoryModel;
    protected $db;
    protected $validation;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->classModel     = new ClassModel();
        $this->studentModel   = new StudentModel();
        $this->violationModel = new ViolationModel();
        $this->categoryModel  = new ViolationCategoryModel();
        $this->db             = \Config\Database::connect();
        $this->validation     = \Config\Services::validation();

        // Load helpers
        // FIX: tambahkan 'auth' agar is_logged_in(), auth_id(), auth_user() tersedia
        helper(['auth', 'permission', 'date', 'response', 'form']);
    }

    /**
     * Display list of violations
     *
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function index()
    {
        // Check authentication
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Check if user is homeroom teacher
        if (!is_homeroom_teacher()) {
            return redirect()->to(get_dashboard_url())->with('error', 'Akses ditolak');
        }

        // FIX: current_user_id() -> auth_id()
        $userId = auth_id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $userId = (int) $userId;

        // Get homeroom teacher's class
        $class = $this->getHomeroomClass($userId);

        if (!$class) {
            return redirect()->to('/homeroom/dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        // Get filter parameters
        $filters = [
            'student_id'     => $this->request->getGet('student_id'),
            'category_id'    => $this->request->getGet('category_id'),
            'severity_level' => $this->request->getGet('severity_level'),
            'start_date'     => $this->request->getGet('start_date'),
            'end_date'       => $this->request->getGet('end_date'),
            'status'         => $this->request->getGet('status'),
            'search'         => $this->request->getGet('search'),
        ];

        // Get violations with filters
        $violations = $this->getViolations($class['id'], $filters);

        // Get students in class for filter
        $students = $this->getClassStudents($class['id']);

        // Get violation categories for filter
        $categories = $this->categoryModel
            ->asArray()
            ->where('deleted_at', null)
            ->orderBy('severity_level', 'ASC')
            ->orderBy('category_name', 'ASC')
            ->findAll();

        // FIX: current_user() -> auth_user()
        $currentUser = auth_user();

        // Prepare data for view
        $data = [
            'title'          => 'Kasus & Pelanggaran',
            'pageTitle'      => 'Kasus & Pelanggaran',
            'breadcrumbs'    => [
                ['title' => 'Dashboard',   'url' => base_url('homeroom/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => '#', 'active' => true],
            ],
            'class'          => $class,
            'homeroom_class' => $class,
            'violations'     => $violations,
            'students'       => $students,
            'categories'     => $categories,
            'filters'        => $filters,
            'currentUser'    => $currentUser,
        ];

        return view('homeroom_teacher/violations/index', $data);
    }

    /**
     * Show create violation form
     *
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function create()
    {
        // Check authentication
        if (!is_logged_in() || !is_homeroom_teacher()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // FIX: current_user_id() -> auth_id()
        $userId = auth_id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $userId = (int) $userId;

        // Get homeroom teacher's class
        $class = $this->getHomeroomClass($userId);

        if (!$class) {
            return redirect()->to('/homeroom/dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        // Get students in class
        $students = $this->getClassStudents($class['id']);

        // Get violation categories
        // Wali Kelas hanya boleh mencatat pelanggaran dengan tingkat "Ringan" (sesuai matriks hak akses)
        $categories = $this->categoryModel
            ->asArray()
            ->where('deleted_at', null)
            ->where('severity_level', 'Ringan')
            ->orderBy('severity_level', 'ASC')
            ->orderBy('category_name', 'ASC')
            ->findAll();

        // Group categories by severity (untuk konsistensi tampilan)
        $groupedCategories = [
            'Ringan' => [],
            'Sedang' => [],
            'Berat'  => [],
        ];

        foreach ($categories as &$category) {
            $severity                       = $category['severity_level'] ?? 'Ringan';
            $category['points']             = $category['point_deduction'] ?? 0;
            $groupedCategories[$severity][] = $category;
        }
        unset($category);

        // FIX: current_user() -> auth_user()
        $currentUser = auth_user();

        // Prepare data for view
        $data = [
            'title'             => 'Tambah Kasus & Pelanggaran',
            'pageTitle'         => 'Tambah Kasus & Pelanggaran',
            'breadcrumbs'       => [
                ['title' => 'Dashboard',   'url' => base_url('homeroom/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('homeroom/violations')],
                ['title' => 'Tambah Kasus & Pelanggaran',      'url' => '#', 'active' => true],
            ],
            'class'             => $class,
            'homeroom_class'    => $class,
            'students'          => $students,
            'categories'        => $categories,
            'groupedCategories' => $groupedCategories,
            'currentUser'       => $currentUser,
            'validation'        => $this->validation,
        ];

        return view('homeroom_teacher/violations/create', $data);
    }

    /**
     * Show edit form for violation (Ringan only)
     *
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function edit($id)
    {
        // Check authentication
        if (!is_logged_in() || !is_homeroom_teacher()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // FIX: current_user_id() -> auth_id()
        $userId = auth_id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $userId = (int) $userId;

        // Get homeroom teacher's class
        $class = $this->getHomeroomClass($userId);

        if (!$class) {
            return redirect()->to('/homeroom/dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        // Ambil detail pelanggaran
        $violation = $this->getViolationDetail($id);

        if (!$violation) {
            return redirect()->to('/homeroom/violations')
                ->with('error', 'Data pelanggaran tidak ditemukan.');
        }

        // Pastikan pelanggaran milik kelas perwalian
        if ((int) $violation['class_id'] !== (int) $class['id']) {
            return redirect()->to('/homeroom/violations')
                ->with('error', 'Anda tidak memiliki akses untuk mengubah data ini.');
        }

        // Sesuai perancangan: Wali kelas hanya boleh mengubah pelanggaran Ringan
        $severity = $violation['severity_level'] ?? 'Sedang';
        if ($severity !== 'Ringan') {
            return redirect()->to('/homeroom/violations/detail/' . $id)
                ->with('error', 'Wali kelas hanya dapat mengubah pelanggaran dengan tingkat Ringan.');
        }

        // Ambil kategori pelanggaran Ringan untuk dropdown
        $categories = $this->categoryModel
            ->asArray()
            ->where('deleted_at', null)
            ->where('severity_level', 'Ringan')
            ->orderBy('severity_level', 'ASC')
            ->orderBy('category_name', 'ASC')
            ->findAll();

        // Group categories by severity (konsisten dengan create)
        $groupedCategories = [
            'Ringan' => [],
            'Sedang' => [],
            'Berat'  => [],
        ];

        foreach ($categories as &$category) {
            $sev                        = $category['severity_level'] ?? 'Ringan';
            $category['points']         = $category['point_deduction'] ?? 0;
            $groupedCategories[$sev][]  = $category;
        }
        unset($category);

        // FIX: current_user() -> auth_user()
        $currentUser = auth_user();

        $data = [
            'title'             => 'Edit Kasus & Pelanggaran',
            'pageTitle'         => 'Edit Kasus & Pelanggaran',
            'breadcrumbs'       => [
                ['title' => 'Dashboard',   'url' => base_url('homeroom/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('homeroom/violations')],
                ['title' => 'Edit Kasus & Pelanggaran',        'url' => '#', 'active' => true],
            ],
            'class'             => $class,
            'homeroom_class'    => $class,
            'violation'         => $violation,
            'categories'        => $categories,
            'groupedCategories' => $groupedCategories,
            'currentUser'       => $currentUser,
            'validation'        => $this->validation,
        ];

        return view('homeroom_teacher/violations/edit', $data);
    }

    /**
     * Store new violation
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function store()
    {
        // Check authentication
        if (!is_logged_in() || !is_homeroom_teacher()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // FIX: current_user_id() -> auth_id()
        $userId = auth_id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $userId = (int) $userId;

        // Get homeroom teacher's class
        $class = $this->getHomeroomClass($userId);

        if (!$class) {
            return redirect()->to('/homeroom/dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        // Validation rules
        $rules = [
            'student_id' => [
                'rules'  => 'required|numeric',
                'errors' => [
                    'required' => 'Siswa harus dipilih',
                    'numeric'  => 'Siswa tidak valid',
                ],
            ],
            'category_id' => [
                'rules'  => 'required|numeric',
                'errors' => [
                    'required' => 'Kategori pelanggaran harus dipilih',
                    'numeric'  => 'Kategori tidak valid',
                ],
            ],
            'violation_date' => [
                'rules'  => 'required|valid_date',
                'errors' => [
                    'required'   => 'Tanggal pelanggaran harus diisi',
                    'valid_date' => 'Format tanggal tidak valid',
                ],
            ],
            'violation_time' => [
                // Mengizinkan kosong, HH:MM, atau HH:MM:SS
                'rules'  => 'permit_empty|regex_match[/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/]',
                'errors' => [
                    'regex_match' => 'Format waktu harus HH:MM (00–23:00–59)',
                ],
            ],
            'location' => [
                'rules'  => 'permit_empty|max_length[200]',
                'errors' => [
                    'max_length' => 'Lokasi maksimal 200 karakter',
                ],
            ],
            'description' => [
                'rules'  => 'required|min_length[10]',
                'errors' => [
                    'required'   => 'Deskripsi pelanggaran harus diisi',
                    'min_length' => 'Deskripsi minimal 10 karakter',
                ],
            ],
            'witness' => [
                'rules'  => 'permit_empty|max_length[200]',
                'errors' => [
                    'max_length' => 'Saksi maksimal 200 karakter',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validation->getErrors())
                ->with('error', 'Mohon periksa kembali input Anda.');
        }

        // Normalisasi waktu ke format HH:MM:SS agar cocok dengan aturan model/DB
        $rawTime   = trim((string) $this->request->getPost('violation_time'));
        $timeForDb = null;
        if ($rawTime !== '') {
            if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $rawTime)) {
                // "07:00" -> "07:00:00"
                $timeForDb = $rawTime . ':00';
            } elseif (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $rawTime)) {
                // sudah "07:00:30"
                $timeForDb = $rawTime;
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', ['Format waktu kejadian tidak valid.'])
                    ->with('error', 'Format waktu harus HH:MM atau HH:MM:SS.');
            }
        }

        // Verify student belongs to homeroom class & is active
        $studentId = (int) $this->request->getPost('student_id');
        /** @var array|null $student */
        $student = $this->studentModel->asArray()->find($studentId);

        if (!$student) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Siswa tidak ditemukan']);
        }

        // Pastikan siswa memang dari kelas perwalian ini
        if ((int) ($student['class_id'] ?? 0) !== (int) $class['id']) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Siswa tidak termasuk dalam kelas perwalian Anda.']);
        }

        // Pastikan status siswa aktif
        $status        = (string) ($student['status'] ?? '');
        $allowedActive = ['active', 'Active', 'aktif', 'Aktif'];
        if (!in_array($status, $allowedActive, true)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Hanya siswa berstatus aktif yang dapat dilaporkan.']);
        }

        // Pastikan kategori valid & hanya "Ringan" yang boleh dicatat oleh Wali Kelas
        $categoryId = (int) $this->request->getPost('category_id');

        /** @var array|null $category */
        $category = $this->categoryModel
            ->asArray()
            ->where('id', $categoryId)
            ->where('deleted_at', null)
            ->first();

        if (!$category) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Kategori pelanggaran tidak ditemukan']);
        }

        // Sesuai matriks hak akses Wali Kelas hanya boleh mencatat pelanggaran Ringan
        if (($category['severity_level'] ?? 'Sedang') !== 'Ringan') {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Wali kelas hanya dapat mencatat pelanggaran dengan tingkat Ringan.'])
                ->with('error', 'Pelanggaran tingkat Sedang/Berat harus dilaporkan melalui Guru BK.');
        }

        // Otomatis tugaskan ke Guru BK yang bertanggung jawab atas kelas ini (jika ada)
        $handledBy = !empty($class['counselor_id']) ? (int) $class['counselor_id'] : null;

        // ==========================
        // Upload evidence (file bukti)
        // ==========================
        $evidenceFiles = [];
        $files      = $this->request->getFileMultiple('evidence'); // name="evidence[]"
        $allowedExt = $this->getAllowedEvidenceExt();
        $maxSize    = 5 * 1024 * 1024; // 5MB

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'violations';
        $ym      = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target  = $baseDir . DIRECTORY_SEPARATOR . $ym;
        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        $uploadErrors = [];

        if ($files) {
            foreach ($files as $file) {
                if (!$file || !$file->isValid() || $file->hasMoved()) {
                    if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                        $uploadErrors[] = 'Upload gagal: ' . $file->getErrorString();
                    }
                    continue;
                }

                $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $uploadErrors[] = "Tipe file tidak diizinkan: {$file->getName()}";
                    continue;
                }

                if ($file->getSize() > $maxSize) {
                    $uploadErrors[] = "Ukuran file terlalu besar (maks 5MB): {$file->getName()}";
                    continue;
                }

                $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

                try {
                    $file->move($target, $newName);
                } catch (\Throwable $e) {
                    $uploadErrors[] = "Gagal menyimpan file: {$file->getName()}";
                    log_message('error', '[HOMEROOM VIOLATION] move() failed: ' . $e->getMessage());
                    continue;
                }

                $rel = 'uploads/violations/' . str_replace(DIRECTORY_SEPARATOR, '/', $ym) . '/' . $newName;
                $evidenceFiles[] = $rel;
            }
        }

        if (!empty($uploadErrors)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $uploadErrors)
                ->with('error', 'Terjadi kesalahan saat mengunggah file bukti.');
        }

        $evidenceFiles = $this->uniqueEvidence($evidenceFiles);

        try {
            // Prepare violation data
            $violationData = [
                'student_id'     => $studentId,
                'category_id'    => $categoryId,
                'violation_date' => $this->request->getPost('violation_date'),
                'violation_time' => $timeForDb,
                'location'       => $this->request->getPost('location') ?: null,
                'description'    => $this->request->getPost('description'),
                'witness'        => $this->request->getPost('witness') ?: null,
                'reported_by'    => $userId,
                'handled_by'     => $handledBy,
                'evidence'       => !empty($evidenceFiles) ? json_encode($evidenceFiles, JSON_UNESCAPED_SLASHES) : null,
                'status'         => 'Dilaporkan',
                'created_at'     => date('Y-m-d H:i:s'),
            ];

            // Insert violation
            $violationId = $this->violationModel->insert($violationData);

            if ($violationId === false) {
                log_message('error', 'Violation insert errors: ' . json_encode($this->violationModel->errors()));
                return redirect()->back()
                    ->withInput()
                    ->with('errors', $this->violationModel->errors())
                    ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan periksa input Anda.');
            }

            if ($violationId) {
                // Get category for notification (ulang query untuk jaga konsistensi)
                /** @var array|null $categoryForNotif */
                $categoryForNotif = $this->categoryModel->asArray()->find($violationData['category_id']);

                if (!$categoryForNotif) {
                    return redirect()->back()
                        ->withInput()
                        ->with('errors', ['Kategori pelanggaran tidak ditemukan']);
                }

                // Sinkronkan total poin pelanggaran siswa di tabel students
                try {
                    if (method_exists($this->violationModel, 'getStudentTotalPoints')) {
                        $totalPoints = (int) $this->violationModel->getStudentTotalPoints((int) $studentId);

                        if ($totalPoints < 0) {
                            $totalPoints = 0;
                        }

                        $this->studentModel->update((int) $studentId, [
                            'total_violation_points' => $totalPoints,
                        ]);
                    }
                } catch (\Throwable $e) {
                    log_message(
                        'error',
                        '[VIOLATION STORE] Gagal sync total_violation_points untuk siswa '
                        . $studentId . ' - ' . $e->getMessage()
                    );
                }

                helper('notification');

                // Send notification to counselor (if exists)
                if (function_exists('send_notification') && !empty($class['counselor_id'])) {
                    send_notification(
                        $class['counselor_id'],
                        'Pelanggaran Baru Dilaporkan',
                        "Pelanggaran {$categoryForNotif['category_name']} dilaporkan oleh {$class['class_name']}",
                        'violation',
                        ['violation_id' => $violationId]
                    );
                } else {
                    log_message('warning', 'notification helper not found; skip counselor notification');
                }

                // Send notification to student's parent (if exists)
                if (function_exists('send_notification') && !empty($student['parent_id'])) {
                    send_notification(
                        $student['parent_id'],
                        'Pelanggaran Siswa',
                        "Anak Anda melakukan pelanggaran: {$categoryForNotif['category_name']}",
                        'violation',
                        ['violation_id' => $violationId]
                    );
                } else {
                    log_message('warning', 'notification helper not found; skip parent notification');
                }

                // Log activity
                log_message(
                    'info',
                    "[VIOLATION] New violation created by homeroom teacher. ID: {$violationId}, Student: {$studentId}, Category: {$violationData['category_id']}"
                );

                return redirect()->to('/homeroom/violations')
                    ->with('success', 'Pelanggaran berhasil dilaporkan.');
            }

            throw new \Exception('Failed to insert violation');
        } catch (\Exception $e) {
            log_message('error', '[VIOLATION STORE] Error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }
    }

    /**
     * Update existing violation (Ringan only)
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function update($id)
    {
        // Check authentication
        if (!is_logged_in() || !is_homeroom_teacher()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // FIX: current_user_id() -> auth_id()
        $userId = auth_id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $userId = (int) $userId;

        // Get homeroom teacher's class
        $class = $this->getHomeroomClass($userId);

        if (!$class) {
            return redirect()->to('/homeroom/dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        // Ambil detail pelanggaran saat ini
        $current = $this->getViolationDetail($id);

        if (!$current) {
            return redirect()->to('/homeroom/violations')
                ->with('error', 'Data pelanggaran tidak ditemukan.');
        }

        // Pastikan pelanggaran milik kelas perwalian
        if ((int) $current['class_id'] !== (int) $class['id']) {
            return redirect()->to('/homeroom/violations')
                ->with('error', 'Anda tidak memiliki akses untuk mengubah data ini.');
        }

        // Sesuai perancangan: Wali kelas hanya boleh mengubah pelanggaran Ringan
        $currentSeverity = $current['severity_level'] ?? 'Sedang';
        if ($currentSeverity !== 'Ringan') {
            return redirect()->to('/homeroom/violations/detail/' . $id)
                ->with('error', 'Wali kelas hanya dapat mengubah pelanggaran dengan tingkat Ringan.');
        }

        // Validation rules (tanpa student_id, karena tidak boleh diubah)
        $rules = [
            'category_id' => [
                'rules'  => 'required|numeric',
                'errors' => [
                    'required' => 'Kategori pelanggaran harus dipilih',
                    'numeric'  => 'Kategori tidak valid',
                ],
            ],
            'violation_date' => [
                'rules'  => 'required|valid_date',
                'errors' => [
                    'required'   => 'Tanggal pelanggaran harus diisi',
                    'valid_date' => 'Format tanggal tidak valid',
                ],
            ],
            'violation_time' => [
                // Mengizinkan kosong, HH:MM, atau HH:MM:SS
                'rules'  => 'permit_empty|regex_match[/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/]',
                'errors' => [
                    'regex_match' => 'Format waktu harus HH:MM (00–23:00–59)',
                ],
            ],
            'location' => [
                'rules'  => 'permit_empty|max_length[200]',
                'errors' => [
                    'max_length' => 'Lokasi maksimal 200 karakter',
                ],
            ],
            'description' => [
                'rules'  => 'required|min_length[10]',
                'errors' => [
                    'required'   => 'Deskripsi pelanggaran harus diisi',
                    'min_length' => 'Deskripsi minimal 10 karakter',
                ],
            ],
            'witness' => [
                'rules'  => 'permit_empty|max_length[200]',
                'errors' => [
                    'max_length' => 'Saksi maksimal 200 karakter',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validation->getErrors())
                ->with('error', 'Mohon periksa kembali input Anda.');
        }

        // Normalisasi waktu ke format HH:MM:SS agar cocok dengan aturan model/DB
        $rawTime   = trim((string) $this->request->getPost('violation_time'));
        $timeForDb = null;
        if ($rawTime !== '') {
            if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $rawTime)) {
                $timeForDb = $rawTime . ':00';
            } elseif (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $rawTime)) {
                $timeForDb = $rawTime;
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', ['Format waktu kejadian tidak valid.'])
                    ->with('error', 'Format waktu harus HH:MM atau HH:MM:SS.');
            }
        }

        $studentId  = (int) $current['student_id'];
        $categoryId = (int) $this->request->getPost('category_id');

        // Pastikan kategori valid & tetap Ringan
        $category = $this->categoryModel
            ->asArray()
            ->where('id', $categoryId)
            ->where('deleted_at', null)
            ->first();

        if (!$category) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Kategori pelanggaran tidak ditemukan']);
        }

        if (($category['severity_level'] ?? 'Sedang') !== 'Ringan') {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['Wali kelas hanya dapat memilih kategori pelanggaran dengan tingkat Ringan.'])
                ->with('error', 'Pelanggaran tingkat Sedang/Berat harus dikelola melalui Guru BK.');
        }

        // ==========================
        // Evidence: load existing
        // ==========================
        $existingEvidence = $this->decodeEvidence($current['evidence'] ?? null);

        // ==========================
        // Evidence: remove selected (remove_evidence[])
        // ==========================
        $toRemove = $this->request->getPost('remove_evidence') ?? [];
        if (!is_array($toRemove)) {
            $toRemove = [];
        }

        $toRemoveNorm = array_values(array_unique(array_map(
            fn($p) => $this->normalizeRel((string) $p),
            $toRemove
        )));

        // hanya boleh hapus yang memang ada di evidence pelanggaran ini
        $existingNorm   = array_map(fn($p) => $this->normalizeRel((string) $p), $existingEvidence);
        $allowedRemove  = $toRemoveNorm ? array_values(array_intersect($toRemoveNorm, $existingNorm)) : [];

        if (!empty($allowedRemove)) {
            // hapus dari array evidence
            $existingEvidence = array_values(array_filter($existingEvidence, function ($p) use ($allowedRemove) {
                return !in_array($this->normalizeRel((string) $p), $allowedRemove, true);
            }));

            // hapus file fisik (aman, hanya uploads/violations)
            foreach ($allowedRemove as $rel) {
                $this->deleteEvidenceFile($rel);
            }
        }

        // Evidence final dimulai dari existing yang tersisa
        $evidenceFiles = $existingEvidence;

        // ==========================
        // Upload evidence tambahan (jika ada)
        // ==========================
        $files      = $this->request->getFileMultiple('evidence'); // name="evidence[]"
        $allowedExt = $this->getAllowedEvidenceExt();
        $maxSize    = 5 * 1024 * 1024; // 5MB

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'violations';
        $ym      = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target  = $baseDir . DIRECTORY_SEPARATOR . $ym;
        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        $uploadErrors = [];

        if ($files) {
            foreach ($files as $file) {
                if (!$file || !$file->isValid() || $file->hasMoved()) {
                    if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                        $uploadErrors[] = 'Upload gagal: ' . $file->getErrorString();
                    }
                    continue;
                }

                $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $uploadErrors[] = "Tipe file tidak diizinkan: {$file->getName()}";
                    continue;
                }

                if ($file->getSize() > $maxSize) {
                    $uploadErrors[] = "Ukuran file terlalu besar (maks 5MB): {$file->getName()}";
                    continue;
                }

                $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

                try {
                    $file->move($target, $newName);
                } catch (\Throwable $e) {
                    $uploadErrors[] = "Gagal menyimpan file: {$file->getName()}";
                    log_message('error', '[HOMEROOM VIOLATION] move() failed: ' . $e->getMessage());
                    continue;
                }

                $rel = 'uploads/violations/' . str_replace(DIRECTORY_SEPARATOR, '/', $ym) . '/' . $newName;
                $evidenceFiles[] = $rel;
            }
        }

        if (!empty($uploadErrors)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $uploadErrors)
                ->with('error', 'Terjadi kesalahan saat mengunggah file bukti.');
        }

        $evidenceFiles = $this->uniqueEvidence($evidenceFiles);

        try {
            $updateData = [
                'category_id'    => $categoryId,
                'violation_date' => $this->request->getPost('violation_date'),
                'violation_time' => $timeForDb,
                'location'       => $this->request->getPost('location') ?: null,
                'description'    => $this->request->getPost('description'),
                'witness'        => $this->request->getPost('witness') ?: null,
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            // IMPORTANT: evidence harus diset juga ketika kosong (supaya bisa "hapus semua")
            $updateData['evidence'] = !empty($evidenceFiles)
                ? json_encode($evidenceFiles, JSON_UNESCAPED_SLASHES)
                : null;

            if (!$this->violationModel->update($id, $updateData)) {
                log_message('error', 'Violation update errors: ' . json_encode($this->violationModel->errors()));
                return redirect()->back()
                    ->withInput()
                    ->with('errors', $this->violationModel->errors())
                    ->with('error', 'Terjadi kesalahan saat menyimpan perubahan. Silakan periksa input Anda.');
            }

            // Sync total poin pelanggaran siswa (karena kategori bisa berubah)
            try {
                if (method_exists($this->violationModel, 'getStudentTotalPoints')) {
                    $totalPoints = (int) $this->violationModel->getStudentTotalPoints($studentId);
                    if ($totalPoints < 0) {
                        $totalPoints = 0;
                    }

                    $this->studentModel->update($studentId, [
                        'total_violation_points' => $totalPoints,
                    ]);
                }
            } catch (\Throwable $e) {
                log_message(
                    'error',
                    '[VIOLATION UPDATE] Gagal sync total_violation_points untuk siswa '
                    . $studentId . ' - ' . $e->getMessage()
                );
            }

            log_message(
                'info',
                "[VIOLATION] Violation updated by homeroom teacher. ID: {$id}, Student: {$studentId}, Category: {$categoryId}"
            );

            return redirect()->to('/homeroom/violations/detail/' . $id)
                ->with('success', 'Data pelanggaran berhasil diperbarui.');
        } catch (\Exception $e) {
            log_message('error', '[VIOLATION UPDATE] Error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi.');
        }
    }

    /**
     * Show violation detail
     *
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function detail($id)
    {
        // Check authentication
        if (!is_logged_in() || !is_homeroom_teacher()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // FIX: current_user_id() -> auth_id()
        $userId = auth_id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $userId = (int) $userId;

        // Get homeroom teacher's class
        $class = $this->getHomeroomClass($userId);

        if (!$class) {
            return redirect()->to('/homeroom/dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        // Get violation detail
        $violation = $this->getViolationDetail($id);

        if (!$violation) {
            return redirect()->to('/homeroom/violations')
                ->with('error', 'Data pelanggaran tidak ditemukan.');
        }

        // Verify violation belongs to homeroom class
        if ((int) $violation['class_id'] !== (int) $class['id']) {
            return redirect()->to('/homeroom/violations')
                ->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        // Get sanctions for this violation
        $sanctions = $this->getViolationSanctions($id);

        // Get student's violation history
        $studentHistory = $this->getStudentViolationHistory($violation['student_id'], 5);

        // FIX: current_user() -> auth_user()
        $currentUser = auth_user();

        // Prepare data for view
        $data = [
            'title'          => 'Detail Kasus & Pelanggaran',
            'pageTitle'      => 'Detail Kasus & Pelanggaran',
            'breadcrumbs'    => [
                ['title' => 'Dashboard',   'url' => base_url('homeroom/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('homeroom/violations')],
                ['title' => 'Detail Kasus & Pelanggaran',      'url' => '#', 'active' => true],
            ],
            'class'          => $class,
            'homeroom_class' => $class,
            'violation'      => $violation,
            'sanctions'      => $sanctions,
            'studentHistory' => $studentHistory,
            'currentUser'    => $currentUser,
        ];

        return view('homeroom_teacher/violations/detail', $data);
    }

    /**
     * Get homeroom teacher's class
     *
     * @param int $userId
     * @return array|null
     */
    private function getHomeroomClass($userId)
    {
        try {
            return $this->db->table('classes')
                ->select('classes.*, academic_years.year_name, academic_years.semester')
                ->join('academic_years', 'academic_years.id = classes.academic_year_id')
                ->where('classes.homeroom_teacher_id', $userId)
                ->where('classes.deleted_at', null)
                ->where('academic_years.is_active', 1)
                ->orderBy('classes.created_at', 'DESC')
                ->get()
                ->getRowArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM VIOLATION] Get class error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get students in class
     *
     * @param int $classId
     * @return array
     */
    private function getClassStudents($classId)
    {
        try {
            return $this->db->table('students')
                ->select([
                    'students.id',
                    'students.nisn',
                    // FIX: students.full_name sudah dihapus -> ambil dari users.full_name
                    "users.full_name AS full_name",
                ])
                ->join('users', 'users.id = students.user_id AND users.deleted_at IS NULL', 'left')
                ->where('students.class_id', $classId)
                ->where('students.deleted_at', null)
                // Hanya siswa berstatus aktif yang boleh dilaporkan oleh wali kelas
                ->whereIn('students.status', ['active', 'Active', 'aktif', 'Aktif'])
                ->orderBy('users.full_name', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM VIOLATION] Get students error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get violations with filters
     *
     * @param int   $classId
     * @param array $filters
     * @return array
     */
    private function getViolations($classId, $filters = [])
    {
        try {
            $builder = $this->db->table('violations v')
                ->select("
                    v.id AS violation_id,
                    v.violation_date, v.violation_time, v.location, v.status, v.created_at,
                    s.id AS student_id,
                    su.full_name AS student_name,
                    s.nisn,
                    vc.id AS category_id, vc.category_name, vc.severity_level, vc.point_deduction,
                    u.full_name AS reported_by_name
                ")
                ->join('students s', 's.id = v.student_id')
                ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
                ->join('violation_categories vc', 'vc.id = v.category_id')
                ->join('users u', 'u.id = v.reported_by AND u.deleted_at IS NULL', 'left') // left biar aman kalau data lama kosong
                ->where('s.class_id', $classId)
                ->where('s.deleted_at', null)
                ->where('v.deleted_at', null);

            // Apply filters
            if (!empty($filters['student_id'])) {
                $builder->where('v.student_id', $filters['student_id']);
            }
            if (!empty($filters['category_id'])) {
                $builder->where('v.category_id', $filters['category_id']);
            }
            if (!empty($filters['severity_level'])) {
                $builder->where('vc.severity_level', $filters['severity_level']);
            }
            if (!empty($filters['start_date'])) {
                $builder->where('v.violation_date >=', $filters['start_date']);
            }
            if (!empty($filters['end_date'])) {
                $builder->where('v.violation_date <=', $filters['end_date']);
            }
            if (!empty($filters['status'])) {
                $builder->where('v.status', $filters['status']);
            }
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $builder->groupStart()
                    // FIX: jangan like ke s.full_name (kolom sudah dihapus)
                    ->like('su.full_name', $search)
                    ->orLike('s.nisn', $search)
                    ->orLike('vc.category_name', $search)
                    ->orLike('v.description', $search)
                    ->groupEnd();
            }

            return $builder
                ->orderBy('v.violation_date', 'DESC')
                ->orderBy('v.created_at', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM VIOLATION] Get violations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get violation detail
     *
     * @param int $id
     * @return array|null
     */
    private function getViolationDetail($id)
    {
        try {
            $row = $this->db->table('violations v')
                ->select("
                    v.*,
                    s.id AS student_id,
                    su.full_name AS student_name,
                    s.nisn,
                    s.class_id,
                    s.gender,
                    vc.category_name,
                    vc.severity_level,
                    vc.point_deduction,
                    vc.description AS category_description,
                    ru.full_name AS reported_by_name,
                    hu.full_name AS handled_by_name,
                    IFNULL((
                        SELECT SUM(vc2.point_deduction)
                        FROM violations v2
                        JOIN violation_categories vc2 ON vc2.id = v2.category_id
                        WHERE v2.student_id = s.id
                        AND v2.deleted_at IS NULL
                    ), 0) AS student_total_points
                ")
                ->join('students s', 's.id = v.student_id')
                ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
                ->join('violation_categories vc', 'vc.id = v.category_id')
                ->join('users ru', 'ru.id = v.reported_by AND ru.deleted_at IS NULL', 'left')
                ->join('users hu', 'hu.id = v.handled_by AND hu.deleted_at IS NULL', 'left')
                ->where('v.id', $id)
                ->where('v.deleted_at', null)
                ->get()
                ->getRowArray();

            if ($row) {
                $row['student_total_points'] = (int) ($row['student_total_points'] ?? 0);
            }

            return $row;
        } catch (\Throwable $e) {
            log_message('error', '[HOMEROOM VIOLATION] Get detail error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get sanctions for violation
     *
     * @param int $violationId
     * @return array
     */
    private function getViolationSanctions($violationId)
    {
        try {
            return $this->db->table('sanctions')
                ->select('sanctions.*,
                         users.full_name as assigned_by_name,
                         verified.full_name as verified_by_name')
                // dibuat LEFT agar tetap aman jika user terkait sudah soft-delete
                ->join('users', 'users.id = sanctions.assigned_by AND users.deleted_at IS NULL', 'left')
                ->join('users as verified', 'verified.id = sanctions.verified_by AND verified.deleted_at IS NULL', 'left')
                ->where('sanctions.violation_id', $violationId)
                ->where('sanctions.deleted_at', null)
                ->orderBy('sanctions.sanction_date', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM VIOLATION] Get sanctions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get student's violation history
     *
     * @param int $studentId
     * @param int $limit
     * @return array
     */
    private function getStudentViolationHistory($studentId, $limit = 5)
    {
        try {
            return $this->db->table('violations')
                ->select('violations.*,
                         violation_categories.category_name,
                         violation_categories.severity_level,
                         violation_categories.point_deduction')
                ->join('violation_categories', 'violation_categories.id = violations.category_id')
                ->where('violations.student_id', $studentId)
                ->where('violations.deleted_at', null)
                ->orderBy('violations.violation_date', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[HOMEROOM VIOLATION] Get history error: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================
    // Helpers (tambahan) untuk evidence removal & keamanan path
    // =========================================================

    /**
     * Ekstensi bukti yang diizinkan (single source of truth)
     */
    private function getAllowedEvidenceExt(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'mp4'];
    }

    /**
     * Decode JSON evidence -> array of strings
     */
    private function decodeEvidence($json): array
    {
        if (empty($json)) return [];
        $decoded = json_decode((string) $json, true);
        if (!is_array($decoded)) return [];
        // paksa string + filter kosong
        $out = [];
        foreach ($decoded as $p) {
            $p = (string) $p;
            if ($p !== '') $out[] = $p;
        }
        return $out;
    }

    /**
     * Normalisasi path relatif (biar konsisten antara DB, form, filesystem)
     */
    private function normalizeRel(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?: $path;
        return ltrim($path, '/');
    }

    /**
     * Unik + bersih (hapus empty) evidence list
     */
    private function uniqueEvidence(array $paths): array
    {
        $norm = [];
        foreach ($paths as $p) {
            $p = $this->normalizeRel((string) $p);
            if ($p !== '') $norm[] = $p;
        }
        $norm = array_values(array_unique($norm));
        return $norm;
    }

    /**
     * Hapus file evidence secara aman (hanya dalam public/uploads/violations)
     */
    private function deleteEvidenceFile(string $rel): void
    {
        $rel = $this->normalizeRel($rel);
        if ($rel === '') return;

        // Guard: hanya boleh hapus dari folder uploads/violations/
        if (strpos($rel, 'uploads/violations/') !== 0) {
            return;
        }

        $baseDir = realpath(rtrim(FCPATH, "/\\") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'violations');
        $full    = rtrim(FCPATH, "/\\") . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $real    = realpath($full);

        if ($baseDir && $real && strpos($real, $baseDir) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}
