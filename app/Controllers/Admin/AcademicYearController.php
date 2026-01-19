<?php

/**
 * File Path: app/Controllers/Admin/AcademicYearController.php
 *
 * Academic Year Controller
 * Handle CRUD operations untuk Academic Year management
 *
 * @package    SIB-K
 * @subpackage Controllers/Admin
 * @category   Academic Year Management
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AcademicYearService;
use App\Validation\AcademicYearValidation;

class AcademicYearController extends BaseController
{
    protected AcademicYearService $academicYearService;

    public function __construct()
    {
        $this->academicYearService = new AcademicYearService();
    }

    /**
     * Helper kecil supaya konsisten: hanya user berizin yang boleh akses semua endpoint di controller ini.
     */
    protected function ensurePermission(): void
    {
        // Pastikan helper/func ini memang ada di proyek kamu (kamu sudah pakai di index())
        require_permission('manage_academic_years');
    }

    /**
     * Display academic years list
     *
     * @return string
     */
    public function index()
    {
        $this->ensurePermission();

        $filters = [
            'is_active' => $this->request->getGet('is_active'),
            'semester'  => $this->request->getGet('semester'),
            'search'    => $this->request->getGet('search'),
            'order_by'  => $this->request->getGet('order_by') ?? 'academic_years.year_name',
            'order_dir' => $this->request->getGet('order_dir') ?? 'DESC',
        ];

        // ✅ Ambil semua data (tanpa paginate CI4) -> pagination ditangani DataTables di View
        $yearsData  = $this->academicYearService->getAllAcademicYears($filters, 10, false);
        $stats      = $this->academicYearService->getAcademicYearStatistics();
        $activeYear = $this->academicYearService->getActiveAcademicYear();

        // ✅ Semester options harus 3 (sinkron dengan Validation + DB enum)
        $semesterOptions = [
            'Ganjil'       => 'Ganjil',
            'Genap'        => 'Genap',
            'Ganjil-Genap' => 'Ganjil-Genap',
        ];

        $data = [
            'title'      => 'Manajemen Tahun Ajaran',
            'page_title' => 'Manajemen Tahun Ajaran',
            'breadcrumb' => [
                ['title' => 'Dashboard', 'link' => base_url('admin/dashboard')],
                ['title' => 'Tahun Ajaran', 'link' => null],
            ],

            'academic_years' => $yearsData['academic_years'],
            'filters'        => $filters,
            'stats'          => $stats,
            'active_year'    => $activeYear,

            'semester_options' => $semesterOptions,
            'status_options'   => [
                1 => 'Aktif',
                0 => 'Tidak Aktif',
            ],
        ];

        return view('admin/academic_years/index', $data);
    }

    /**
     * Display create academic year form
     *
     * @return string
     */
    public function create()
    {
        $this->ensurePermission();

        // Get suggested academic year
        $suggested = $this->academicYearService->getSuggestedAcademicYear();

        // Get dropdown options
        $semesterOptions = AcademicYearValidation::getSemesterOptions();

        // ✅ Dropdown year_name options (baru)
        $yearNameOptions = $this->academicYearService->getYearNameOptions();

        $data = [
            'title'      => 'Tambah Tahun Ajaran',
            'page_title' => 'Tambah Tahun Ajaran Baru',
            'breadcrumb' => [
                ['title' => 'Dashboard', 'link' => base_url('admin/dashboard')],
                ['title' => 'Tahun Ajaran', 'link' => base_url('admin/academic-years')],
                ['title' => 'Tambah Tahun Ajaran', 'link' => null],
            ],

            'semester_options'  => $semesterOptions,
            'year_name_options' => $yearNameOptions,
            'suggested'         => $suggested,

            'validation' => \Config\Services::validation(),
        ];

        return view('admin/academic_years/form', $data);
    }

    /**
     * Store new academic year
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function store()
    {
        $this->ensurePermission();

        // Validate input
        $rules = AcademicYearValidation::createRules();

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Get and sanitize data
        $data = AcademicYearValidation::sanitizeInput($this->request->getPost());

        // Additional validation: year name & date range
        $yearNameCheck = AcademicYearValidation::validateYearName($data['year_name'] ?? '');
        if (!$yearNameCheck['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $yearNameCheck['message']);
        }

        $dateRangeCheck = AcademicYearValidation::validateDateRange($data['start_date'] ?? '', $data['end_date'] ?? '');
        if (!$dateRangeCheck['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $dateRangeCheck['message']);
        }

        // Check overlap (tetap dipertahankan sesuai kode kamu sekarang)
        $overlapCheck = $this->academicYearService->checkOverlap($data['start_date'] ?? '', $data['end_date'] ?? '');
        if (!empty($overlapCheck['overlaps'])) {
            $conflictNames = [];
            foreach ((array)($overlapCheck['conflicting_years'] ?? []) as $c) {
                $yn = is_array($c) ? ($c['year_name'] ?? '') : ($c->year_name ?? '');
                $sm = is_array($c) ? ($c['semester'] ?? '')  : ($c->semester ?? '');
                $conflictNames[] = trim($yn . ($sm ? " ({$sm})" : ''));
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Tahun ajaran ini bentrok dengan: ' . implode(', ', array_filter($conflictNames)));
        }

        // Create academic year (guard semester policy ada di Service)
        $result = $this->academicYearService->createAcademicYear($data);

        if (empty($result['success'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal menyimpan data');
        }

        return redirect()->to(base_url('admin/academic-years'))
            ->with('success', $result['message'] ?? 'Berhasil');
    }

    /**
     * Display edit academic year form
     *
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function edit($id)
    {
        $this->ensurePermission();

        $id = (int) $id;

        // Get academic year data
        $year = $this->academicYearService->getAcademicYearById($id);

        if (!$year) {
            return redirect()->to(base_url('admin/academic-years'))
                ->with('error', 'Tahun ajaran tidak ditemukan');
        }

        // Get dropdown options
        $semesterOptions = AcademicYearValidation::getSemesterOptions();

        // ✅ Dropdown year_name options (baru)
        $yearNameOptions = $this->academicYearService->getYearNameOptions();
        $currentYearName = (string)($year['year_name'] ?? '');
        if ($currentYearName !== '' && !in_array($currentYearName, $yearNameOptions, true)) {
            array_unshift($yearNameOptions, $currentYearName);
            $yearNameOptions = array_values(array_unique($yearNameOptions));
        }

        $data = [
            'title'      => 'Edit Tahun Ajaran',
            'page_title' => 'Edit Tahun Ajaran: ' . ($year['year_name'] ?? ''),
            'breadcrumb' => [
                ['title' => 'Dashboard', 'link' => base_url('admin/dashboard')],
                ['title' => 'Tahun Ajaran', 'link' => base_url('admin/academic-years')],
                ['title' => 'Edit Tahun Ajaran', 'link' => null],
            ],

            'academic_year'     => $year,
            'semester_options'  => $semesterOptions,
            'year_name_options' => $yearNameOptions,

            'validation' => \Config\Services::validation(),
        ];

        return view('admin/academic_years/form', $data);
    }

    /**
     * Update academic year data
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function update($id)
    {
        $this->ensurePermission();

        $id = (int) $id;

        // Check if academic year exists
        $year = $this->academicYearService->getAcademicYearById($id);
        if (!$year) {
            return redirect()->to(base_url('admin/academic-years'))
                ->with('error', 'Tahun ajaran tidak ditemukan');
        }

        // Validate input
        $rules = AcademicYearValidation::updateRules($id);

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Get and sanitize data
        $data = AcademicYearValidation::sanitizeInput($this->request->getPost());

        // Additional validation: year name & date range
        $yearNameCheck = AcademicYearValidation::validateYearName($data['year_name'] ?? '');
        if (!$yearNameCheck['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $yearNameCheck['message']);
        }

        $dateRangeCheck = AcademicYearValidation::validateDateRange($data['start_date'] ?? '', $data['end_date'] ?? '');
        if (!$dateRangeCheck['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $dateRangeCheck['message']);
        }

        // Check overlap (exclude current year)
        $overlapCheck = $this->academicYearService->checkOverlap($data['start_date'] ?? '', $data['end_date'] ?? '', $id);
        if (!empty($overlapCheck['overlaps'])) {
            $conflictNames = [];
            foreach ((array)($overlapCheck['conflicting_years'] ?? []) as $c) {
                $yn = is_array($c) ? ($c['year_name'] ?? '') : ($c->year_name ?? '');
                $sm = is_array($c) ? ($c['semester'] ?? '')  : ($c->semester ?? '');
                $conflictNames[] = trim($yn . ($sm ? " ({$sm})" : ''));
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Tahun ajaran ini bentrok dengan: ' . implode(', ', array_filter($conflictNames)));
        }

        // Update academic year (guard semester policy ada di Service)
        $result = $this->academicYearService->updateAcademicYear($id, $data);

        if (empty($result['success'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal menyimpan perubahan');
        }

        return redirect()->to(base_url('admin/academic-years'))
            ->with('success', $result['message'] ?? 'Berhasil');
    }

    /**
     * Delete academic year
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function delete($id)
    {
        $this->ensurePermission();

        $id = (int) $id;

        $result = $this->academicYearService->deleteAcademicYear($id);

        if (empty($result['success'])) {
            return redirect()->back()
                ->with('error', $result['message'] ?? 'Gagal menghapus data');
        }

        return redirect()->to(base_url('admin/academic-years'))
            ->with('success', $result['message'] ?? 'Berhasil');
    }

    /**
     * Set academic year as active
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function setActive($id)
    {
        $this->ensurePermission();

        $id = (int) $id;

        $result = $this->academicYearService->setActiveAcademicYear($id);

        if (empty($result['success'])) {
            return redirect()->back()
                ->with('error', $result['message'] ?? 'Gagal mengaktifkan data');
        }

        return redirect()->to(base_url('admin/academic-years'))
            ->with('success', $result['message'] ?? 'Berhasil');
    }

    /**
     * Get suggested academic year via AJAX
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getSuggested()
    {
        $this->ensurePermission();

        $suggested = $this->academicYearService->getSuggestedAcademicYear();

        return $this->response->setJSON([
            'success' => true,
            'data'    => $suggested,
        ]);
    }

    /**
     * Check overlap via AJAX
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function checkOverlap()
    {
        $this->ensurePermission();

        $startDate = (string) $this->request->getGet('start_date');
        $endDate   = (string) $this->request->getGet('end_date');
        $excludeId = $this->request->getGet('exclude_id');

        $excludeId = ($excludeId !== null && $excludeId !== '') ? (int) $excludeId : null;

        if ($startDate === '' || $endDate === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Parameter tidak lengkap',
            ]);
        }

        $result = $this->academicYearService->checkOverlap($startDate, $endDate, $excludeId);

        return $this->response->setJSON([
            'success'           => true,
            'overlaps'          => !empty($result['overlaps']),
            'conflicting_years' => $result['conflicting_years'] ?? [],
        ]);
    }

    /**
     * Generate year name via AJAX
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function generateYearName()
    {
        $this->ensurePermission();

        $startDate = (string) $this->request->getGet('start_date');

        if ($startDate === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tanggal mulai harus diisi',
            ]);
        }

        $yearName = AcademicYearValidation::generateYearName($startDate);
        $semester = AcademicYearValidation::suggestSemester($startDate);

        return $this->response->setJSON([
            'success'   => true,
            'year_name' => $yearName,
            'semester'  => $semester,
        ]);
    }
}
