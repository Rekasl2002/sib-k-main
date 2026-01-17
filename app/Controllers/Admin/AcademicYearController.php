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
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AcademicYearService;
use App\Validation\AcademicYearValidation;

class AcademicYearController extends BaseController
{
    protected $academicYearService;

    public function __construct()
    {
        $this->academicYearService = new AcademicYearService();
    }

    /**
     * Display academic years list
     * 
     * @return string
     */
    public function index()
    {
        require_permission('manage_academic_years');

        $filters = [
            'is_active' => $this->request->getGet('is_active'),
            'semester'  => $this->request->getGet('semester'),
            'search'    => $this->request->getGet('search'),
            'order_by'  => $this->request->getGet('order_by') ?? 'academic_years.year_name',
            'order_dir' => $this->request->getGet('order_dir') ?? 'DESC',
        ];

        // ✅ Ambil semua data (tanpa paginate CI4) -> pagination ditangani DataTables di View
        $yearsData = $this->academicYearService->getAllAcademicYears($filters, 10, false);
        $stats      = $this->academicYearService->getAcademicYearStatistics();
        $activeYear = $this->academicYearService->getActiveAcademicYear();

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

            'semester_options' => [
                'Ganjil' => 'Ganjil',
                'Genap'  => 'Genap',
            ],
            'status_options' => [
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
        // Get suggested academic year
        $suggested = $this->academicYearService->getSuggestedAcademicYear();

        // Get dropdown options
        $semesterOptions = AcademicYearValidation::getSemesterOptions();

        $data = [
            'title' => 'Tambah Tahun Ajaran',
            'page_title' => 'Tambah Tahun Ajaran Baru',
            'breadcrumb' => [
                ['title' => 'Dashboard', 'link' => base_url('admin/dashboard')],
                ['title' => 'Tahun Ajaran', 'link' => base_url('admin/academic-years')],
                ['title' => 'Tambah Tahun Ajaran', 'link' => null],
            ],
            'semester_options' => $semesterOptions,
            'suggested' => $suggested,
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
        // Validate input
        $rules = AcademicYearValidation::createRules();

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Get and sanitize data
        $data = AcademicYearValidation::sanitizeInput($this->request->getPost());

        // Additional validation: date range
        $dateRangeCheck = AcademicYearValidation::validateDateRange($data['start_date'], $data['end_date']);
        if (!$dateRangeCheck['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $dateRangeCheck['message']);
        }

        // Check overlap
        $overlapCheck = $this->academicYearService->checkOverlap($data['start_date'], $data['end_date']);
        if ($overlapCheck['overlaps']) {
            $conflictNames = array_column($overlapCheck['conflicting_years'], 'year_name');
            return redirect()->back()
                ->withInput()
                ->with('error', 'Tahun ajaran ini bentrok dengan: ' . implode(', ', $conflictNames));
        }

        // Create academic year
        $result = $this->academicYearService->createAcademicYear($data);

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/academic-years')
            ->with('success', $result['message']);
    }

    /**
     * Display edit academic year form
     * 
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function edit($id)
    {
        // Get academic year data
        $year = $this->academicYearService->getAcademicYearById($id);

        if (!$year) {
            return redirect()->to('admin/academic-years')
                ->with('error', 'Tahun ajaran tidak ditemukan');
        }

        // Get dropdown options
        $semesterOptions = AcademicYearValidation::getSemesterOptions();

        $data = [
            'title' => 'Edit Tahun Ajaran',
            'page_title' => 'Edit Tahun Ajaran: ' . $year['year_name'],
            'breadcrumb' => [
                ['title' => 'Dashboard', 'link' => base_url('admin/dashboard')],
                ['title' => 'Tahun Ajaran', 'link' => base_url('admin/academic-years')],
                ['title' => 'Edit Tahun Ajaran', 'link' => null],
            ],
            'academic_year' => $year,
            'semester_options' => $semesterOptions,
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
        // Check if academic year exists
        $year = $this->academicYearService->getAcademicYearById($id);
        if (!$year) {
            return redirect()->to('admin/academic-years')
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

        // Additional validation: date range
        $dateRangeCheck = AcademicYearValidation::validateDateRange($data['start_date'], $data['end_date']);
        if (!$dateRangeCheck['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $dateRangeCheck['message']);
        }

        // Check overlap (exclude current year)
        $overlapCheck = $this->academicYearService->checkOverlap($data['start_date'], $data['end_date'], $id);
        if ($overlapCheck['overlaps']) {
            $conflictNames = array_column($overlapCheck['conflicting_years'], 'year_name');
            return redirect()->back()
                ->withInput()
                ->with('error', 'Tahun ajaran ini bentrok dengan: ' . implode(', ', $conflictNames));
        }

        // Update academic year
        $result = $this->academicYearService->updateAcademicYear($id, $data);

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/academic-years')
            ->with('success', $result['message']);
    }

    /**
     * Delete academic year
     * 
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function delete($id)
    {
        // Delete academic year
        $result = $this->academicYearService->deleteAcademicYear($id);

        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/academic-years')
            ->with('success', $result['message']);
    }

    /**
     * Set academic year as active
     * 
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function setActive($id)
    {
        // Set active
        $result = $this->academicYearService->setActiveAcademicYear($id);

        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/academic-years')
            ->with('success', $result['message']);
    }

    /**
     * Get suggested academic year via AJAX
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getSuggested()
    {
        $suggested = $this->academicYearService->getSuggestedAcademicYear();

        return $this->response->setJSON([
            'success' => true,
            'data' => $suggested,
        ]);
    }

    /**
     * Check overlap via AJAX
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function checkOverlap()
    {
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');
        $excludeId = $this->request->getGet('exclude_id');

        if (empty($startDate) || empty($endDate)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Parameter tidak lengkap',
            ]);
        }

        $result = $this->academicYearService->checkOverlap($startDate, $endDate, $excludeId);

        return $this->response->setJSON([
            'success' => true,
            'overlaps' => $result['overlaps'],
            'conflicting_years' => $result['conflicting_years'],
        ]);
    }

    /**
     * Generate year name via AJAX
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function generateYearName()
    {
        $startDate = $this->request->getGet('start_date');

        if (empty($startDate)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tanggal mulai harus diisi',
            ]);
        }

        $yearName = AcademicYearValidation::generateYearName($startDate);
        $semester = AcademicYearValidation::suggestSemester($startDate);

        return $this->response->setJSON([
            'success' => true,
            'year_name' => $yearName,
            'semester' => $semester,
        ]);
    }
}
