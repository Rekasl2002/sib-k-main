<?php

/**
 * File Path: app/Controllers/Koordinator/CaseController.php
 *
 * Koordinator • Case Controller
 * Mengelola kasus/pelanggaran + aksi terkait (assign BK, notify parent, tambah sanksi)
 * Diselaraskan pola dengan Counselor\CaseController, namun khusus role Koordinator.
 *
 * Catatan Akses:
 * - Default (sesuai perancangan): Koordinator hanya R + U (tanpa manage_violations)
 * - Jika permission manage_violations dicentang: boleh CRUD + aksi ekstra
 * - Pengecualian (permintaan terbaru): Assign Guru BK tetap boleh untuk Koordinator (meski hanya R/U)
 *
 * Revisi KP:
 * - Filter total poin/pelanggaran mengikuti Tahun Ajaran (year_name).
 * - Jika user tidak memilih date_from/date_to dan tidak memilih academic_year,
 *   default diarahkan ke Tahun Ajaran aktif (konsisten dengan cache poin tahunan).
 *
 * @package    SIB-K
 * @subpackage Controllers/Koordinator
 * @category   Controller
 * @created    2025-12-17
 */

namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use App\Services\ViolationService;
use App\Models\ViolationModel;
use App\Models\SanctionModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class CaseController extends BaseController
{
    protected ViolationService $violationService;
    protected ViolationModel $violationModel;
    protected SanctionModel $sanctionModel;
    protected StudentModel $studentModel;
    protected ClassModel $classModel;
    protected $db;

    public function __construct()
    {
        // Pastikan helper auth tersedia (is_logged_in(), is_koordinator(), auth_id())
        try {
            helper(['auth', 'permission', 'app']);
        } catch (\Throwable $e) {
            // ignore
        }

        $this->violationService = new ViolationService();
        $this->violationModel   = new ViolationModel();
        $this->sanctionModel    = new SanctionModel();
        $this->studentModel     = new StudentModel();
        $this->classModel       = new ClassModel();
        $this->db               = \Config\Database::connect();
    }

    /**
     * Guard login + role koordinator
     */
    private function ensureAuth()
    {
        // helper optional: jangan sampai fatal kalau helper tidak ada
        try {
            helper(['auth', 'permission', 'app']);
        } catch (\Throwable $e) {
            // ignore
        }

        if (!function_exists('is_logged_in') || !is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!function_exists('is_koordinator') || !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }
        return null;
    }

    /**
     * Guard permission opsional (RBAC granular).
     * Jika helper/func permission tidak tersedia, jangan blok (role guard tetap berjalan).
     */
    private function ensurePerm(string $perm)
    {
        if (function_exists('has_permission')) {
            if (!has_permission($perm)) {
                return redirect()->to('/')->with('error', 'Akses ditolak');
            }
        }
        return null;
    }

    /**
     * Guard untuk aksi assign BK:
     * - Default: Koordinator boleh (meski R/U).
     * - Jika kamu ingin RBAC lebih ketat, bisa nyalakan permission khusus (mis. assign_counselor),
     *   tapi secara default tidak memblok Koordinator.
     */
    private function ensureAssignAllowed()
    {
        // Koordinator role sudah di-guard oleh ensureAuth(), jadi default: allow.
        // Jika ingin paksa RBAC: uncomment blok di bawah, dan siapkan permission assign_counselor.
        /*
        if (function_exists('has_permission')) {
            if (!has_permission('assign_counselor') && !has_permission('manage_violations')) {
                return redirect()->to('/')->with('error', 'Akses ditolak');
            }
        }
        */
        return null;
    }

    /**
     * Ambil year_name Tahun Ajaran aktif (untuk default filter).
     */
    private function getActiveAcademicYearName(): ?string
    {
        try {
            $row = $this->db->table('academic_years')
                ->select('year_name')
                ->where('deleted_at', null)
                ->where('is_active', 1)
                ->orderBy('updated_at', 'DESC')
                ->get(1)
                ->getRowArray();

            $yn = trim((string)($row['year_name'] ?? ''));
            return $yn !== '' ? $yn : null;
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::getActiveAcademicYearName - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalisasi filter TA:
     * - Kalau user tidak isi date_from/date_to dan tidak pilih academic_year,
     *   default diarahkan ke Tahun Ajaran aktif.
     * - Lalu panggil ViolationService::normalizeAcademicYearFilter() jika ada,
     *   supaya date_from/date_to terisi otomatis (gabungan ganjil+genap).
     */
    private function normalizeYearFilterForUI(array $filters): array
    {
        $ay = trim((string)($filters['academic_year'] ?? ''));
        $df = trim((string)($filters['date_from'] ?? ''));
        $dt = trim((string)($filters['date_to'] ?? ''));

        // opsi "all" (kalau suatu saat kamu pakai di UI)
        if (strcasecmp($ay, 'all') === 0) {
            $filters['academic_year'] = '';
            $ay = '';
        }

        // default ke TA aktif jika tidak ada range tanggal dan tidak pilih TA
        if ($ay === '' && $df === '' && $dt === '') {
            $active = $this->getActiveAcademicYearName();
            if ($active) {
                $filters['academic_year'] = $active;
            }
        }

        // isi date_from/date_to otomatis jika academic_year dipilih
        try {
            if (method_exists($this->violationService, 'normalizeAcademicYearFilter')) {
                $filters = $this->violationService->normalizeAcademicYearFilter($filters);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::normalizeYearFilterForUI - ' . $e->getMessage());
        }

        return $filters;
    }

    /**
     * Dropdown Tahun Ajaran (year_name) untuk UI.
     * Prioritas: ViolationService->getAcademicYearOptions(), fallback query DB.
     */
    private function getAcademicYearOptionsSafe(): array
    {
        try {
            if (method_exists($this->violationService, 'getAcademicYearOptions')) {
                $opts = $this->violationService->getAcademicYearOptions();
                if (is_array($opts)) return $opts;
            }
        } catch (\Throwable $e) {
            // lanjut fallback
        }

        $out = [];
        try {
            $rows = $this->db->table('academic_years')
                ->select('DISTINCT year_name')
                ->where('deleted_at', null)
                ->orderBy('year_name', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $yn = trim((string)($r['year_name'] ?? ''));
                if ($yn !== '') $out[] = $yn;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::getAcademicYearOptionsSafe - ' . $e->getMessage());
        }

        return $out;
    }

    /**
     * Safe wrapper untuk history siswa agar detail page tidak crash
     * jika signature service berbeda atau ada perubahan model.
     */
    private function getStudentHistorySafe(int $studentId, array $filters = []): array
    {
        // pastikan filter juga dinormalisasi (TA aktif + date range dari TA)
        $filters = $this->normalizeYearFilterForUI($filters);

        // 1) Coba panggil service dengan 2 parameter
        try {
            return (array) $this->violationService->getStudentViolationHistory($studentId, $filters);
        } catch (\ArgumentCountError $e) {
            // 2) Jika service lama hanya terima 1 arg
            try {
                return (array) $this->violationService->getStudentViolationHistory($studentId);
            } catch (\Throwable $e2) {
                log_message('error', 'Koordinator CaseController::getStudentHistorySafe fallback(1) - ' . $e2->getMessage());
            }
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::getStudentHistorySafe fallback(0) - ' . $e->getMessage());
        }

        // 3) Fallback manual pakai model (kalau method tersedia)
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo   = $filters['date_to'] ?? null;

        $violations = [];
        $totalPoints = 0;

        try {
            if (method_exists($this->violationModel, 'getViolationsWithFilters')) {
                $violations = $this->violationModel->getViolationsWithFilters([
                    'student_id' => $studentId,
                    'date_from'  => $dateFrom,
                    'date_to'    => $dateTo,
                ]);
            }
        } catch (\Throwable $e) {}

        try {
            if (method_exists($this->violationModel, 'getStudentTotalPoints')) {
                $totalPoints = (int) $this->violationModel->getStudentTotalPoints($studentId, [
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ]);
            }
        } catch (\Throwable $e) {}

        $stats = [
            'total_violations'    => is_array($violations) ? count($violations) : 0,
            'total_points'        => (int)$totalPoints,
            'by_severity'         => ['Ringan' => 0, 'Sedang' => 0, 'Berat' => 0],
            'by_status'           => ['Dilaporkan' => 0, 'Dalam Proses' => 0, 'Selesai' => 0, 'Dibatalkan' => 0],
            'is_repeat_offender'  => false,
            'last_violation_date' => null,
        ];

        foreach ((array)$violations as $v) {
            $sev = (string)($v['severity_level'] ?? '');
            $st  = (string)($v['status'] ?? '');

            if (isset($stats['by_severity'][$sev])) $stats['by_severity'][$sev]++;
            if (isset($stats['by_status'][$st])) $stats['by_status'][$st]++;

            if (!empty($v['is_repeat_offender'])) $stats['is_repeat_offender'] = true;

            if (!empty($v['violation_date'])) {
                if (empty($stats['last_violation_date']) || $v['violation_date'] > $stats['last_violation_date']) {
                    $stats['last_violation_date'] = $v['violation_date'];
                }
            }
        }

        return [
            'violations'      => (array)$violations,
            'statistics'      => $stats,
            'filters_applied' => $filters,
        ];
    }

    /**
     * Normalisasi path relatif agar tidak ada double slash / leading slash
     */
    private function normalizeRel(string $path): string
    {
        $path = preg_replace('#/+#', '/', (string) $path);
        return ltrim($path, '/');
    }

    /**
     * Upload evidence pelanggaran ke public/uploads/violations/YYYY/MM
     * @param string $inputName name field file (mis. evidence)
     * @return array relative paths (mis. uploads/violations/2025/11/abc.png)
     */
    private function uploadEvidenceMultiple(string $inputName = 'evidence'): array
    {
        $files = $this->request->getFileMultiple($inputName);
        if (!$files) return [];

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'mp4'];
        $maxSize    = 5 * 1024 * 1024; // 5MB

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'violations';

        $ym     = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target = $baseDir . DIRECTORY_SEPARATOR . $ym;

        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        $paths = [];
        foreach ($files as $file) {
            if (!$file) continue;

            if (!$file->isValid() || $file->hasMoved()) {
                // skip; kalau memang tidak ada file (UPLOAD_ERR_NO_FILE) juga aman
                continue;
            }

            $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) continue;

            if ($file->getSize() > $maxSize) continue;

            $newName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($target, $newName);

            $rel = 'uploads/violations/' . str_replace(DIRECTORY_SEPARATOR, '/', $ym) . '/' . $newName;
            $paths[] = $this->normalizeRel($rel);
        }

        return $paths;
    }

    /**
     * Hapus file evidence yang dicentang (aman: hanya dalam uploads/violations/)
     */
    private function deleteEvidenceFiles(array $paths): void
    {
        foreach ($paths as $rel) {
            $rel = $this->normalizeRel((string) $rel);

            // hard guard: hanya boleh dalam folder uploads/violations/
            if (strpos($rel, 'uploads/violations/') !== 0) continue;
            if (strpos($rel, '..') !== false) continue;

            $abs = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }

    /**
     * Dropdown siswa aktif (selaras Counselor) - versi lebih "tahan beda skema".
     *
     * Output minimal untuk dropdown:
     * - id, nisn, nis, full_name, class_name
     */
    private function getActiveStudents(): array
    {
        $db = $this->db;

        // Deteksi kolom agar tidak error jika skema berbeda
        $studentCols = [];
        $userCols    = [];
        $classCols   = [];

        try { $studentCols = $db->getFieldNames('students'); } catch (\Throwable $e) {}
        try { $userCols    = $db->getFieldNames('users'); } catch (\Throwable $e) {}
        try { $classCols   = $db->getFieldNames('classes'); } catch (\Throwable $e) {}

        $has = static fn(array $cols, string $c): bool => in_array($c, $cols, true);

        // Tentukan sumber nama (prioritas: users.full_name -> users.name -> students.full_name -> students.name)
        $joinUsers = false;
        $nameExpr  = "''";

        if ($has($userCols, 'full_name')) {
            $joinUsers = true;
            $nameExpr  = 'u.full_name';
        } elseif ($has($userCols, 'name')) {
            $joinUsers = true;
            $nameExpr  = 'u.name';
        } elseif ($has($studentCols, 'full_name')) {
            $nameExpr = 's.full_name';
        } elseif ($has($studentCols, 'name')) {
            $nameExpr = 's.name';
        } elseif ($has($studentCols, 'student_name')) {
            $nameExpr = 's.student_name';
        }

        $b = $db->table('students s');

        $select = [
            's.id',
            ($has($studentCols, 'nisn') ? 's.nisn' : "'' AS nisn"),
            ($has($studentCols, 'nis')  ? 's.nis'  : "'' AS nis"),
            ($has($classCols, 'class_name') ? 'c.class_name' : "'' AS class_name"),
            $nameExpr . ' AS full_name',
        ];

        $b->select(implode(', ', $select), false)
          ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left');

        if ($joinUsers) {
            $b->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left');
        }

        $b->where('s.deleted_at', null);

        // Filter status Aktif hanya jika kolomnya ada
        if ($has($studentCols, 'status')) {
            $b->where('s.status', 'Aktif');
        }

        $b->orderBy($nameExpr, 'ASC', false);

        return $b->get()->getResultArray();
    }

    /**
     * R (Read) - SELALU boleh untuk Koordinator (tanpa manage_violations)
     */
    public function index()
    {
        if ($r = $this->ensureAuth()) return $r;

        $filters = [
            // ✅ Revisi KP: filter Tahun Ajaran (year_name) / semester id (optional)
            'academic_year'      => $this->request->getGet('academic_year'),
            'academic_year_id'   => $this->request->getGet('academic_year_id'),

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

        // ✅ Normalisasi: default TA aktif + isi date range otomatis dari academic_year
        $filters = $this->normalizeYearFilterForUI($filters);

        // Koordinator: lihat semua (tidak dibatasi handled_by)
        $data = [
            'title'       => 'Kasus & Pelanggaran',
            'pageTitle'   => 'Kasus & Pelanggaran',

            'violations'  => $this->violationService->getViolations($filters),
            'pager'       => null,

            'filters'     => $filters,
            'students'    => $this->getActiveStudents(),
            'categories'  => $this->violationService->getActiveCategories(),

            // ✅ Stats ikut filter TA/range yang sama
            'stats'       => $this->violationService->getDashboardStats($filters),

            // ✅ Dropdown TA untuk UI Koordinator (agar sama seperti Guru BK)
            'academic_year_options' => $this->getAcademicYearOptionsSafe(),
            'active_academic_year'  => $this->getActiveAcademicYearName(),

            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => '#', 'active' => true],
            ],
        ];

        return view('koordinator/cases/index', $data);
    }

    /**
     * C (Create) - hanya jika manage_violations dicentang
     */
    public function create()
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_violations')) return $r;

        $data = [
            'title'       => 'Tambah Kasus & Pelanggaran',
            'pageTitle'   => 'Tambah Kasus & Pelanggaran',

            // ✅ FIX: pakai dropdown siswa dari controller (hindari error full_name di service)
            'students'    => $this->getActiveStudents(),
            'categories'  => $this->violationService->getCategoriesGrouped(),
            'counselors'  => $this->violationService->getCounselors(),
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('koordinator/cases')],
                ['title' => 'Tambah', 'url' => ''],
            ],
        ];

        return view('koordinator/cases/create', $data);
    }

    /**
     * C (Create) - hanya jika manage_violations dicentang
     */
    public function store()
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_violations')) return $r;

        $data = $this->request->getPost();
        $data['reported_by'] = function_exists('auth_id') ? auth_id() : null;

        // Default handled_by jika form tidak mengirim
        if (empty($data['handled_by'])) {
            $data['handled_by'] = function_exists('auth_id') ? auth_id() : null;
        }

        // Evidence upload (multiple)
        $uploaded = $this->uploadEvidenceMultiple('evidence');
        $data['evidence'] = $uploaded ? json_encode($uploaded, JSON_UNESCAPED_SLASHES) : null;

        try {
            $result = $this->violationService->createViolation($data);

            if (!empty($result['success'])) {
                $vid = (int) ($result['violation_id'] ?? 0);
                if ($vid > 0) {
                    return redirect()->to(base_url('koordinator/cases/detail/' . $vid))
                        ->with('success', $result['message'] ?? 'Pelanggaran berhasil ditambahkan');
                }

                return redirect()->to(base_url('koordinator/cases'))
                    ->with('success', $result['message'] ?? 'Pelanggaran berhasil ditambahkan');
            }

            return redirect()->back()->withInput()->with('errors', $result['errors'] ?? [$result['message'] ?? 'Terjadi kesalahan']);
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * R (Read) - SELALU boleh untuk Koordinator (tanpa manage_violations)
     */
    public function detail($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to(base_url('koordinator/cases'))->with('error', 'ID kasus tidak valid');
        }

        // kompatibilitas: beberapa service memakai nama method berbeda
        $violation = null;
        try {
            if (method_exists($this->violationService, 'getViolationDetail')) {
                $violation = $this->violationService->getViolationDetail($id);
            } elseif (method_exists($this->violationService, 'getViolationWithSanctions')) {
                $violation = $this->violationService->getViolationWithSanctions($id);
            } else {
                $violation = $this->violationService->getViolationById($id);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::detail fetch error: ' . $e->getMessage());
        }

        if (!$violation) {
            return redirect()->to(base_url('koordinator/cases'))->with('error', 'Kasus tidak ditemukan');
        }

        // ✅ History default ikut TA aktif (biar nyambung dengan sistem poin per TA)
        $historyFilters = [
            'academic_year' => $this->getActiveAcademicYearName(),
        ];
        $studentHistory = $this->getStudentHistorySafe((int)$violation['student_id'], $historyFilters);

        // untuk dropdown assign guru BK di sidebar (tetap ditampilkan di view)
        $counselors = $this->violationService->getCounselors();

        // untuk dropdown jenis sanksi di modal (kalau method tersedia di model/service)
        $sanctionTypes = [];
        if (method_exists($this->sanctionModel, 'getCommonSanctionTypes')) {
            $sanctionTypes = (array) $this->sanctionModel->getCommonSanctionTypes();
        } elseif (method_exists($this->violationService, 'getSanctionTypes')) {
            $sanctionTypes = (array) $this->violationService->getSanctionTypes();
        }

        $data = [
            'title'           => 'Detail Kasus & Pelanggaran',
            'pageTitle'       => 'Detail Kasus & Pelanggaran',
            'violation'       => $violation,
            'student_history' => $studentHistory,
            'counselors'      => $counselors,
            'sanction_types'  => $sanctionTypes,
            'breadcrumbs'     => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('koordinator/cases')],
                ['title' => 'Detail', 'url' => ''],
            ],
        ];

        return view('koordinator/cases/detail', $data);
    }

    /**
     * U (Update - form) - SELALU boleh untuk Koordinator (tanpa manage_violations)
     */
    public function edit($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to(base_url('koordinator/cases'))->with('error', 'ID kasus tidak valid');
        }

        // ✅ Ambil data selaras kebutuhan view (join student + kategori)
        $db = $this->db;
        $violation = $db->table('violations v')
            ->select(
                'v.*,
                u.full_name AS student_name,
                s.nisn, s.nis,
                c.class_name,
                vc.category_name,
                vc.severity_level,
                vc.point_deduction'
            )
            ->join('students s', 's.id = v.student_id', 'left')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.id', $id)
            ->where('v.deleted_at', null) // aman kalau soft delete dipakai
            ->get()
            ->getRowArray();

        if (!$violation) {
            return redirect()->to(base_url('koordinator/cases'))->with('error', 'Kasus tidak ditemukan');
        }

        // pastikan evidence_files tersedia untuk view edit
        if (empty($violation['evidence_files'])) {
            $violation['evidence_files'] = [];
            if (!empty($violation['evidence'])) {
                $arr = json_decode((string) $violation['evidence'], true);
                if (is_array($arr)) $violation['evidence_files'] = $arr;
            }
        }

        $data = [
            'title'       => 'Edit Kasus & Pelanggaran',
            'pageTitle'   => 'Edit Kasus & Pelanggaran',
            'violation'   => $violation,

            // ✅ FIX: pakai dropdown siswa dari controller (hindari error full_name di service)
            'students'    => $this->getActiveStudents(),

            'categories'  => $this->violationService->getCategoriesGrouped(),
            'counselors'  => $this->violationService->getCounselors(),
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('koordinator/cases')],
                ['title' => 'Edit', 'url' => ''],
            ],
        ];

        return view('koordinator/cases/edit', $data);
    }

    /**
     * U (Update - submit) - SELALU boleh untuk Koordinator (tanpa manage_violations)
     */
    public function update($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID kasus tidak valid');
        }

        $data = $this->request->getPost();

        // Ambil evidence lama (dari service bila ada, fallback dari model)
        $existingEvidence = [];
        try {
            if (method_exists($this->violationService, 'getViolationEvidence')) {
                $existingEvidence = $this->violationService->getViolationEvidence($id);
            } else {
                $row = $this->violationModel->asArray()->find($id);
                if ($row && !empty($row['evidence'])) {
                    $tmp = json_decode((string) $row['evidence'], true);
                    if (is_array($tmp)) $existingEvidence = $tmp;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::update evidence fetch error: ' . $e->getMessage());
        }

        if (!is_array($existingEvidence)) $existingEvidence = [];

        $removeEvidence = $this->request->getPost('remove_evidence') ?? [];
        if (!is_array($removeEvidence)) $removeEvidence = [];

        // Hapus file fisik (opsional tapi rapi)
        if ($removeEvidence) {
            $this->deleteEvidenceFiles($removeEvidence);
        }

        // Filter yang disimpan
        $removeEvidenceNorm = array_map(fn($p) => $this->normalizeRel((string) $p), $removeEvidence);
        $existingEvidence = array_values(array_filter($existingEvidence, function ($p) use ($removeEvidenceNorm) {
            return !in_array($this->normalizeRel((string) $p), $removeEvidenceNorm, true);
        }));

        // Evidence baru
        $newUploads = $this->uploadEvidenceMultiple('evidence');
        if ($newUploads) {
            $existingEvidence = array_values(array_merge($existingEvidence, $newUploads));
        }

        $data['evidence'] = $existingEvidence
            ? json_encode($existingEvidence, JSON_UNESCAPED_SLASHES)
            : null;

        // Auto resolution date (selaras Counselor)
        if (isset($data['status']) && in_array($data['status'], ['Selesai', 'Dibatalkan'], true)) {
            $data['resolution_date'] = date('Y-m-d');
        } elseif (isset($data['status'])) {
            $data['resolution_date'] = null;
        }

        try {
            $result = $this->violationService->updateViolation($id, $data);

            if (!empty($result['success'])) {
                return redirect()->to(base_url('koordinator/cases/detail/' . $id))
                    ->with('success', $result['message'] ?? 'Kasus berhasil diperbarui');
            }

            return redirect()->back()->withInput()->with('errors', $result['errors'] ?? [$result['message'] ?? 'Terjadi kesalahan']);
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * D (Delete) - hanya jika manage_violations dicentang
     */
    public function delete($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_violations')) return $r;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID kasus tidak valid');
        }

        try {
            $result = $this->violationService->deleteViolation($id);

            if (!empty($result['success'])) {
                return redirect()->to(base_url('koordinator/cases'))
                    ->with('success', $result['message'] ?? 'Kasus berhasil dihapus');
            }

            return redirect()->back()->with('error', $result['error'] ?? $result['message'] ?? 'Gagal menghapus kasus');
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::delete error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus kasus.');
        }
    }

    /**
     * Aksi ekstra - hanya jika manage_violations dicentang
     */
    public function notifyParent($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_violations')) return $r;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID kasus tidak valid');
        }

        try {
            $result = $this->violationService->notifyParent($id);

            if (!empty($result['success'])) {
                return redirect()->back()->with('success', $result['message'] ?? 'Notifikasi orang tua berhasil dikirim');
            }

            return redirect()->back()->with('error', $result['error'] ?? $result['message'] ?? 'Gagal mengirim notifikasi');
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::notifyParent error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim notifikasi.');
        }
    }

    /**
     * Assign Guru BK untuk menangani kasus
     *
     * ✅ Kebijakan terbaru:
     * - Koordinator boleh assign meski hanya R/U (tanpa manage_violations).
     *
     * Kompatibel dengan input name="handled_by" atau "counselor_id"
     */
    public function assignCounselor($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensureAssignAllowed()) return $r;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID kasus tidak valid');
        }

        // Pastikan kasus ada
        $row = null;
        try {
            $row = $this->violationModel->asArray()->find($id);
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::assignCounselor find violation error: ' . $e->getMessage());
        }
        if (!$row) {
            return redirect()->back()->with('error', 'Kasus tidak ditemukan');
        }

        $counselorId = (int) ($this->request->getPost('counselor_id')
            ?? $this->request->getPost('handled_by')
            ?? 0);

        if ($counselorId <= 0) {
            return redirect()->back()->with('error', 'Pilih Guru BK untuk penanganan');
        }

        // ✅ Validasi: hanya boleh assign ke user yang memang termasuk daftar Guru BK
        $counselors = [];
        try {
            $counselors = (array) $this->violationService->getCounselors();
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::assignCounselor getCounselors error: ' . $e->getMessage());
        }

        $allowedIds = [];
        if (is_array($counselors)) {
            foreach ($counselors as $c) {
                if (is_array($c) && isset($c['id'])) $allowedIds[] = (int) $c['id'];
            }
        }

        if ($allowedIds && !in_array($counselorId, $allowedIds, true)) {
            return redirect()->back()->with('error', 'Guru BK tidak valid / tidak tersedia.');
        }

        try {
            $result = $this->violationService->assignCounselor($id, $counselorId);

            if (!empty($result['success'])) {
                return redirect()->back()->with('success', $result['message'] ?? 'Penangan (Guru BK) berhasil ditugaskan');
            }

            return redirect()->back()->with('error', $result['error'] ?? $result['message'] ?? 'Gagal menugaskan penangan');
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::assignCounselor error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menugaskan penangan.');
        }
    }

    /**
     * Tambah sanksi dari halaman detail (compat untuk form action /koordinator/cases/addSanction/{violationId})
     * Aksi ekstra - dibatasi: perlu manage_violations + manage_sanctions
     */
    public function addSanction($violationId): RedirectResponse
    {
        if ($r = $this->ensureAuth()) return $r;

        // add sanction = aksi ekstra (bukan sekadar U data kasus)
        if ($r = $this->ensurePerm('manage_violations')) return $r;
        if ($r = $this->ensurePerm('manage_sanctions')) return $r;

        $violationId = (int) $violationId;
        if ($violationId <= 0) {
            return redirect()->to(base_url('koordinator/cases'))->with('error', 'ID pelanggaran tidak valid');
        }

        $violation = $this->violationModel->asArray()->find($violationId);
        if (!$violation) {
            return redirect()->to(base_url('koordinator/cases'))->with('error', 'Pelanggaran tidak ditemukan');
        }

        $rules = [
            'sanction_type'  => 'required|max_length[100]',
            'sanction_date'  => 'required|valid_date',
            'description'    => 'required|min_length[10]',
            'status'         => 'permit_empty|in_list[Dijadwalkan,Sedang Berjalan,Selesai,Dibatalkan]',
            'start_date'     => 'permit_empty|valid_date',
            'end_date'       => 'permit_empty|valid_date',
            'completed_date' => 'permit_empty|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();
        $documents = $this->handleSanctionDocumentsUpload($this->request->getFileMultiple('documents'));

        $status = $post['status'] ?? 'Dijadwalkan';
        $completedDate = $post['completed_date'] ?? null;
        if ($status === 'Selesai' && empty($completedDate)) {
            $completedDate = $post['sanction_date'] ?? null;
        }

        $payload = [
            'violation_id'            => $violationId,
            'sanction_type'           => trim((string) ($post['sanction_type'] ?? '')),
            'sanction_date'           => $post['sanction_date'] ?? null,
            'start_date'              => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date'                => !empty($post['end_date']) ? $post['end_date'] : null,
            'description'             => trim((string) ($post['description'] ?? '')),
            'status'                  => $status,
            'completed_date'          => $completedDate,
            'completion_notes'        => !empty($post['completion_notes']) ? $post['completion_notes'] : null,
            'assigned_by'             => function_exists('auth_id') ? auth_id() : null,

            // verifikasi koordinator (opsional)
            'verified_by'             => !empty($post['verify_now']) ? (function_exists('auth_id') ? auth_id() : null) : null,
            'verified_at'             => !empty($post['verify_now']) ? date('Y-m-d H:i:s') : null,

            // acknowledgement orang tua
            'parent_acknowledged'     => !empty($post['parent_acknowledged']) ? 1 : 0,
            'parent_acknowledged_at'  => !empty($post['parent_acknowledged'])
                ? (!empty($post['parent_acknowledged_at']) ? $post['parent_acknowledged_at'] : date('Y-m-d H:i:s'))
                : null,

            'documents'               => $documents ? json_encode($documents, JSON_UNESCAPED_SLASHES) : null,
            'notes'                   => !empty($post['notes']) ? $post['notes'] : null,
        ];

        try {
            $id = $this->sanctionModel->insert($payload);
            if (!$id) {
                return redirect()->back()->withInput()->with('errors', $this->sanctionModel->errors());
            }

            // Jika kasus masih "Dilaporkan", otomatis jadi "Dalam Proses"
            if (($violation['status'] ?? '') === 'Dilaporkan') {
                $this->violationModel->update($violationId, ['status' => 'Dalam Proses']);
            }

            return redirect()->to(base_url('koordinator/cases/detail/' . $violationId))
                ->with('success', 'Sanksi berhasil ditambahkan.');
        } catch (\Throwable $e) {
            log_message('error', 'Koordinator CaseController::addSanction error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Upload dokumen sanksi (multiple) ke public/uploads/sanctions/YYYY/MM
     * @return array relative paths
     */
    private function handleSanctionDocumentsUpload(?array $files, ?string $existingJson = null): array
    {
        // untuk addSanction: existingJson umumnya null, tapi tetap aman
        $kept = is_string($existingJson) ? json_decode($existingJson, true) : [];
        if (!is_array($kept)) $kept = [];

        if (!$files) return $kept;

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp4'];
        $max     = 5 * 1024 * 1024;

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'sanctions';

        $ym     = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target = $baseDir . DIRECTORY_SEPARATOR . $ym;

        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) continue;

            $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;
            if ($file->getSize() > $max) continue;

            $new = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($target, $new);

            $rel = 'uploads/sanctions/' . str_replace(DIRECTORY_SEPARATOR, '/', $ym) . '/' . $new;
            $kept[] = $this->normalizeRel($rel);
        }

        return $kept;
    }
}
