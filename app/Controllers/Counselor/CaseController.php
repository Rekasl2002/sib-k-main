<?php

/**
 * File Path: app/Controllers/Counselor/CaseController.php
 *
 * Case Controller
 * Mengelola case/violation & sanksi siswa untuk akun Guru BK/Koordinator
 *
 * @package    SIB-K
 * @subpackage Controllers/Counselor
 * @category   Controller
 * @author     Development Team
 * @created    2025-01-06
 * @updated    2025-11-20
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Services\ViolationService;
use App\Models\ViolationModel;
use App\Models\ViolationCategoryModel;
use App\Models\SanctionModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class CaseController extends BaseController
{
    protected ViolationService $violationService;
    protected ViolationModel $violationModel;
    protected ViolationCategoryModel $categoryModel;
    protected SanctionModel $sanctionModel;
    protected StudentModel $studentModel;
    protected ClassModel $classModel;
    protected $db;
    protected string $returnType = 'array';

    public function __construct()
    {
        helper(['auth']); // memastikan is_logged_in(), is_guru_bk(), is_koordinator(), auth_id() tersedia

        $this->violationService = new ViolationService();
        $this->violationModel   = new ViolationModel();
        $this->categoryModel    = new ViolationCategoryModel();
        $this->sanctionModel    = new SanctionModel();
        $this->studentModel     = new StudentModel();
        $this->classModel       = new ClassModel();
        $this->db               = \Config\Database::connect();
    }

    /**
     * List Kasus & Pelanggaran
     * @return string|ResponseInterface
     */
    public function index()
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $filters = [
            'status'             => $this->request->getGet('status'),
            'severity_level'     => $this->request->getGet('severity_level'),
            'student_id'         => $this->request->getGet('student_id'),
            'category_id'        => $this->request->getGet('category_id'),
            'date_from'          => $this->request->getGet('date_from'),
            'date_to'            => $this->request->getGet('date_to'),
            'is_repeat_offender' => $this->request->getGet('is_repeat_offender'),
            'parent_notified'    => $this->request->getGet('parent_notified'),
            'search'             => $this->request->getGet('search'),
        ];

        if (is_guru_bk() && !is_koordinator()) {
            $filters['handled_by'] = auth_id();
        }

        $data['violations'] = $this->violationService->getViolations($filters);
        $data['students']   = $this->getActiveStudents();
        $data['categories'] = $this->violationService->getActiveCategories();
        $data['filters']    = $filters;
        $data['stats']      = $this->violationService->getDashboardStats($filters);

        // Meta title untuk tab browser (title-meta.php membaca: page_title -> pageTitle -> title)
        $data['title']      = 'Kasus & Pelanggaran';
        $data['pageTitle']  = 'Kasus & Pelanggaran';
        $data['page_title'] = 'Kasus & Pelanggaran';

        $data['breadcrumbs'] = [
            ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
            ['title' => 'Kasus & Pelanggaran', 'url' => '#', 'active' => true],
        ];

        return view('counselor/cases/index', $data);
    }

    /**
     * Halaman khusus daftar Pelanggaran
     * @return string|ResponseInterface
     */
    public function violationsIndex()
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $filters = [
            'status'          => $this->request->getGet('status'),
            'severity_level'  => $this->request->getGet('severity_level'),
            'student_id'      => $this->request->getGet('student_id'),
            'category_id'     => $this->request->getGet('category_id'),
            'date_from'       => $this->request->getGet('date_from'),
            'date_to'         => $this->request->getGet('date_to'),
            'parent_notified' => $this->request->getGet('parent_notified'),
            'search'          => $this->request->getGet('search'),
            'scope'           => 'violations',
        ];

        if (is_guru_bk() && !is_koordinator()) {
            $filters['handled_by'] = auth_id();
        }

        $data['violations'] = $this->violationService->getViolations($filters);
        $data['students']   = $this->getActiveStudents();
        $data['categories'] = $this->violationService->getActiveCategories();
        $data['filters']    = $filters;
        $data['stats']      = $this->violationService->getDashboardStats($filters);

        // Meta title untuk tab browser
        $data['title']      = 'Pelanggaran Siswa';
        $data['pageTitle']  = 'Daftar Pelanggaran';
        $data['page_title'] = 'Daftar Pelanggaran';

        $data['breadcrumbs'] = [
            ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
            ['title' => 'Pelanggaran', 'url' => '#', 'active' => true],
        ];

        return view('counselor/violations/index', $data);
    }

    /**
     * Form create pelanggaran
     * @return string|ResponseInterface
     */
    public function create()
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $data['students']   = $this->getActiveStudents();
        $data['categories'] = $this->violationService->getCategoriesGrouped();
        $data['classes']    = $this->getActiveClasses();

        // Meta title untuk tab browser
        $data['title']      = 'Tambah Kasus & Pelanggaran';
        $data['pageTitle']  = 'Tambah Kasus & Pelanggaran';
        $data['page_title'] = 'Tambah Kasus & Pelanggaran';

        $data['breadcrumbs'] = [
            ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
            ['title' => 'Kasus & Pelanggaran', 'url' => base_url('counselor/cases')],
            ['title' => 'Tambah', 'url' => '#', 'active' => true],
        ];

        return view('counselor/cases/create', $data);
    }

    /**
     * Simpan pelanggaran baru
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $data = $this->request->getPost();
        $data['reported_by'] = auth_id();
        if (empty($data['handled_by'])) $data['handled_by'] = auth_id();

        // Upload evidence ke public/uploads/violations/YYYY/MM
        $evidenceFiles = [];
        $files      = $this->request->getFileMultiple('evidence'); // name="evidence[]"
        $allowedExt = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','mp4'];
        $maxSize    = 5 * 1024 * 1024;

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'violations';
        $ym      = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target  = $baseDir . DIRECTORY_SEPARATOR . $ym;
        if (!is_dir($target)) { @mkdir($target, 0775, true); }

        $errors = [];

        if ($files) {
            foreach ($files as $file) {
                if (!$file || !$file->isValid() || $file->hasMoved()) {
                    if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = 'Upload gagal: ' . $file->getErrorString();
                    }
                    continue;
                }
                $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $errors[] = "Tipe file tidak diizinkan: {$file->getName()}";
                    continue;
                }
                if ($file->getSize() > $maxSize) {
                    $errors[] = "Ukuran file terlalu besar (maks 5MB): {$file->getName()}";
                    continue;
                }
                $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $file->move($target, $newName);
                $rel = 'uploads/violations/' . str_replace(DIRECTORY_SEPARATOR,'/',$ym) . '/' . $newName;
                $evidenceFiles[] = $rel;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data['evidence'] = $evidenceFiles ? json_encode($evidenceFiles, JSON_UNESCAPED_SLASHES) : null;

        try {
            $result = $this->violationService->createViolation($data);
            if (!$result['success']) {
                return redirect()->back()->withInput()->with('errors', $result['errors'] ?? [$result['message']]);
            }
            return redirect()->to('counselor/cases/detail/' . $result['violation_id'])
                ->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_message('error', 'Error storing violation: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Detail pelanggaran
     * @param int $id
     * @return string|ResponseInterface
     */
    public function detail($id)
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $violation = $this->violationService->getViolationDetail($id);
        if (!$violation) return redirect()->to('counselor/cases')->with('error', 'Data pelanggaran tidak ditemukan');

        if (is_guru_bk() && !is_koordinator()) {
            if ((int)$violation['handled_by'] !== (int)auth_id() && (int)$violation['reported_by'] !== (int)auth_id()) {
                return redirect()->to('counselor/cases')->with('error', 'Anda tidak memiliki akses ke kasus ini');
            }
        }

        $data['violation']       = $violation;
        $data['student_history'] = $this->violationService->getStudentViolationHistory($violation['student_id']);
        $data['sanction_types']  = $this->sanctionModel->getCommonSanctionTypes();

        $cat = trim((string)($violation['category_name'] ?? ''));
        $page = $cat !== '' ? ('Detail Kasus: ' . $cat) : 'Detail Kasus';

        // Meta title untuk tab browser
        $data['title']      = 'Detail Kasus & Pelanggaran';
        $data['pageTitle']  = 'Detail Kasus & Pelanggaran';
        $data['page_title'] = 'Detail Kasus & Pelanggaran';

        $data['breadcrumbs'] = [
            ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
            ['title' => 'Kasus & Pelanggaran', 'url' => base_url('counselor/cases')],
            ['title' => 'Detail', 'url' => '#', 'active' => true],
        ];

        return view('counselor/cases/detail', $data);
    }

    /**
     * Form edit pelanggaran
     * @param int $id
     * @return string|ResponseInterface|RedirectResponse
     */
    public function edit($id)
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error','Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error','Akses ditolak');

        // Perbaikan: join users u untuk ambil full_name
        $violation = $this->db->table('violations v')
            ->select('v.*, u.full_name AS student_name, s.nisn, vc.category_name, vc.severity_level, vc.point_deduction')
            ->join('students s', 's.id = v.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->join('violation_categories vc', 'vc.id = v.category_id')
            ->where('v.id', $id)
            ->get()->getRowArray();

        if (!$violation) return redirect()->to('counselor/cases')->with('error','Data tidak ditemukan');

        if (is_guru_bk() && !is_koordinator()) {
            $uid = (int)auth_id();
            if ((int)$violation['handled_by'] !== $uid && (int)$violation['reported_by'] !== $uid) {
                return redirect()->to('counselor/cases')->with('error','Anda tidak memiliki akses untuk mengubah kasus ini');
            }
        }

        $violation['evidence_files'] = [];
        if (!empty($violation['evidence'])) {
            $arr = json_decode($violation['evidence'], true);
            if (is_array($arr)) $violation['evidence_files'] = $arr;
        }

        $cat = trim((string)($violation['category_name'] ?? ''));

        // Tambahkan meta title + breadcrumbs (sebelumnya hanya compact('violation'))
        $data = [
            'violation'   => $violation,

            'title'       => 'Edit Kasus & Pelanggaran',
            'pageTitle'   => 'Edit Kasus & Pelanggaran',
            'page_title'  => 'Edit Kasus & Pelanggaran',

            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('counselor/cases')],
                ['title' => 'Edit', 'url' => '#', 'active' => true],
            ],
        ];

        return view('counselor/cases/edit', $data);
    }

    /**
     * Update pelanggaran
     * @param int $id
     * @return RedirectResponse
     */
    public function update($id): RedirectResponse
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $violation = $this->violationModel->asArray()->find($id);
        if (!$violation) return redirect()->to('counselor/cases')->with('error', 'Data pelanggaran tidak ditemukan');

        if (is_guru_bk() && !is_koordinator()) {
            $uid = (int)auth_id();
            if ((int)$violation['handled_by'] !== $uid && (int)$violation['reported_by'] !== $uid) {
                return redirect()->to('counselor/cases')->with('error', 'Anda tidak memiliki akses untuk mengubah kasus ini');
            }
        }

        $data = $this->request->getPost();

        // Ambil evidence lama (array)
        $existing = [];
        if (!empty($violation['evidence'])) {
            $tmp = json_decode($violation['evidence'], true);
            if (is_array($tmp)) $existing = $tmp;
        }

        // Hapus evidence yang dicentang
        $toRemove = (array) $this->request->getPost('remove_evidence'); // nilai = path relatif yang tersimpan
        if ($toRemove) {
            $toRemove = array_map([$this, 'normalizeRel'], $toRemove);
            $existing = array_values(array_filter($existing, function ($p) use ($toRemove) {
                return !in_array($this->normalizeRel($p), $toRemove, true);
            }));

            // opsional: hapus file fisik
            foreach ($toRemove as $rel) {
                $full = FCPATH . $rel;
                if (is_file($full)) { @unlink($full); }
            }
        }

        // Upload evidence baru (name="evidence[]")
        $newFiles   = $this->request->getFileMultiple('evidence');
        $newPaths   = $newFiles ? $this->uploadViolationEvidence($newFiles) : [];

        // Merge lama + baru
        $finalEvidence = array_values(array_filter(array_merge($existing, $newPaths)));

        // Simpan kembali ke kolom evidence (JSON atau null jika kosong)
        $data['evidence'] = $finalEvidence ? json_encode($finalEvidence, JSON_UNESCAPED_SLASHES) : null;

        try {
            $result = $this->violationService->updateViolation($id, $data);
            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['message']);
            }
            return redirect()->to('counselor/cases/detail/' . $id)->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_message('error', 'Error updating violation: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus pelanggaran (SoftDelete)
     * - Guru BK: hanya kasus yang ia tangani/laporkan
     * - Koordinator: bebas
     * Menggunakan ViolationService::deleteViolation() agar poin siswa ikut disinkronkan.
     *
     * @param int|null $id
     * @return RedirectResponse
     */
    public function delete($id = null): RedirectResponse
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/counselor/cases')->with('error', 'Anda tidak memiliki izin menghapus pelanggaran.');
        }

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->back()->with('error', 'Data pelanggaran tidak ditemukan.');
        }

        $row = $this->violationModel->asArray()->find($id);
        if (!$row) {
            return redirect()->back()->with('error', 'Data pelanggaran tidak ditemukan.');
        }

        if (is_guru_bk() && !is_koordinator()) {
            $uid = (int) auth_id();
            if ((int)($row['handled_by'] ?? 0) !== $uid && (int)($row['reported_by'] ?? 0) !== $uid) {
                return redirect()->to('/counselor/cases')->with('error', 'Anda tidak berhak menghapus pelanggaran ini.');
            }
        }

        try {
            $result = $this->violationService->deleteViolation($id);

            if (is_array($result) && !empty($result['success'])) {
                return redirect()->back()->with(
                    'success',
                    $result['message'] ?? 'Pelanggaran berhasil dihapus.'
                );
            }

            $message = is_array($result) && !empty($result['message'])
                ? $result['message']
                : 'Gagal menghapus pelanggaran.';

            return redirect()->back()->with('error', $message);
        } catch (\Throwable $e) {
            log_message('error', 'Error deleting violation: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus pelanggaran.');
        }
    }

    /**
     * Tambah sanksi (fallback/legacy).
     * Modal baru memakai route: counselor/sanctions/create/{violationId}
     * @param int $violationId
     * @return RedirectResponse
     */
    public function addSanction($violationId): RedirectResponse
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $violation = $this->violationModel->asArray()->find($violationId);
        if (!$violation) return redirect()->to('counselor/cases')->with('error', 'Data pelanggaran tidak ditemukan');

        if (is_guru_bk() && !is_koordinator()) {
            $uid = (int)auth_id();
            if ((int)$violation['handled_by'] !== $uid && (int)$violation['reported_by'] !== $uid) {
                return redirect()->to('counselor/cases/detail/' . $violationId)
                    ->with('error', 'Anda tidak berhak mengubah sanksi pada kasus ini.');
            }
        }

        $data = $this->request->getPost();
        $data['violation_id'] = $violationId;
        $data['assigned_by']  = auth_id();

        $rules = [
            'sanction_type' => 'required|max_length[100]',
            'sanction_date' => 'required|valid_date',
            'description'   => 'required|min_length[10]',
        ];

        // === Upload Documents (optional) ===
        $files = $this->request->getFileMultiple('documents'); // name="documents[]"
        $allowedExt = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','mp4'];
        $maxSize    = 5 * 1024 * 1024; // 5MB

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sanctions';
        $ym     = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target = $baseDir . DIRECTORY_SEPARATOR . $ym;
        if (!is_dir($target)) { @mkdir($target, 0775, true); }

        $docPaths = [];
        if ($files) {
            foreach ($files as $file) {
                if (!$file || !$file->isValid() || $file->hasMoved()) continue;
                $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) continue;
                if ($file->getSize() > $maxSize) continue;

                $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $file->move($target, $newName);

                // REL path tanpa slash depan + normalisasi double slash
                $rel = 'uploads/sanctions/' . str_replace(DIRECTORY_SEPARATOR,'/',$ym) . '/' . $newName;
                $rel = ltrim(preg_replace('#/+#','/',$rel), '/'); // "uploads/...."
                $docPaths[] = $rel;
            }
        }
        $data['documents'] = $docPaths ? json_encode($docPaths, JSON_UNESCAPED_SLASHES) : null;
        // === End Upload Documents ===

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $sanctionId = $this->sanctionModel->insert($data);
            if (!$sanctionId) {
                return redirect()->back()->withInput()->with('errors', $this->sanctionModel->errors());
            }

            if (($violation['status'] ?? '') === 'Dilaporkan') {
                $this->violationModel->update($violationId, ['status' => 'Dalam Proses']);
            }

            return redirect()->to('counselor/cases/detail/' . $violationId)->with('success', 'Sanksi berhasil ditambahkan');
        } catch (\Throwable $e) {
            log_message('error', 'Error adding sanction: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi ke orang tua
     * @param int $id
     * @return RedirectResponse
     */
    public function notifyParent($id): RedirectResponse
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        $violation = $this->violationModel->asArray()->find($id);
        if (!$violation) return redirect()->to('counselor/cases')->with('error', 'Data pelanggaran tidak ditemukan');

        if (is_guru_bk() && !is_koordinator()) {
            $uid = (int)auth_id();
            if ((int)$violation['handled_by'] !== $uid && (int)$violation['reported_by'] !== $uid) {
                return redirect()->to('counselor/cases/detail/' . $id)->with('error', 'Anda tidak berhak mengirim notifikasi untuk kasus ini.');
            }
        }

        try {
            $result = $this->violationService->notifyParent($id);
            if (!$result['success']) {
                return redirect()->back()->with('error', $result['message']);
            }
            return redirect()->back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_message('error', 'Error notifying parent: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Upload evidence pelanggaran ke uploads/violations/YYYY/MM
     * @param \CodeIgniter\HTTP\Files\UploadedFile[] $files
     * @return array relative paths (mis. uploads/violations/2025/11/abc.png)
     */
    private function uploadViolationEvidence(array $files): array
    {
        $allowedExt = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','mp4'];
        $maxSize    = 5 * 1024 * 1024; // 5MB

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'uploads'
                . DIRECTORY_SEPARATOR . 'violations';

        $ym     = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target = $baseDir . DIRECTORY_SEPARATOR . $ym;
        if (!is_dir($target)) { @mkdir($target, 0775, true); }

        $paths = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }
            $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                continue;
            }
            if ($file->getSize() > $maxSize) {
                continue;
            }

            $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($target, $newName);

            // simpan REL path tanpa leading slash + normalisasi
            $rel = 'uploads/violations/' . str_replace(DIRECTORY_SEPARATOR,'/',$ym) . '/' . $newName;
            $paths[] = $this->normalizeRel($rel);
        }
        return $paths;
    }

    /**
     * Normalisasi path relatif agar tidak ada double slash / leading slash
     */
    private function normalizeRel(string $path): string
    {
        $path = preg_replace('#/+#','/',$path ?? '');
        return ltrim($path, '/');
    }

    /**
     * Dropdown siswa aktif
     * @return array
     */
    private function getActiveStudents(): array
    {
        return $this->studentModel
            ->select('students.id, students.nisn, students.nis, users.full_name, classes.class_name')
            ->join('users', 'users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.status', 'Aktif')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
    }

    /**
     * Dropdown kelas aktif
     * @return array
     */
    private function getActiveClasses(): array
    {
        return $this->classModel
            ->where('is_active', 1)
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }
}
