<?php

/**
 * File Path: app/Controllers/Admin/ClassController.php
 * 
 * Class Controller
 * Handle CRUD operations untuk Class management
 * 
 * @package    SIB-K
 * @subpackage Controllers/Admin
 * @category   Class Management
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ClassService;
use App\Validation\ClassValidation;

class ClassController extends BaseController
{
    protected $classService;

    public function __construct()
    {
        $this->classService = new ClassService();
    }

    /**
     * Display classes list
     * 
     * @return string
     */
    public function index()
    {
        // Get filters from request
        $filters = [
            'academic_year_id' => $this->request->getGet('academic_year_id'),
            'grade_level' => $this->request->getGet('grade_level'),
            'major' => $this->request->getGet('major'),
            'is_active' => $this->request->getGet('is_active'),
            'search' => $this->request->getGet('search'),
            'order_by' => $this->request->getGet('order_by') ?? 'classes.grade_level, classes.class_name',
            'order_dir' => $this->request->getGet('order_dir') ?? 'ASC',
        ];

        // Get pagination
        $perPage = 100000; // sesuaikan jika dataset sangat besar
        $classesData = $this->classService->getAllClasses($filters, $perPage);

        // Get dropdown options
        $academicYears = $this->classService->getAvailableAcademicYears();
        $gradeLevels = ClassValidation::getGradeLevelOptions();
        $majors = ClassValidation::getMajorOptions();
        $statusOptions = ClassValidation::getStatusOptions();

        // Get statistics
        $stats = $this->classService->getClassStatistics();

        $data = [
            'title' => 'Manajemen Kelas',
            'page_title' => 'Manajemen Kelas',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Kelas', 'link' => null],
            ],
            'classes' => $classesData['classes'],
            'pager' => $classesData['pager'],
            'total' => $classesData['total'],
            'per_page' => $perPage,
            'current_page' => $classesData['current_page'],
            'filters' => $filters,
            'academic_years' => $academicYears,
            'grade_levels' => $gradeLevels,
            'majors' => $majors,
            'status_options' => $statusOptions,
            'stats' => $stats,
        ];

        return view('admin/classes/index', $data);
    }

    /**
     * Display create class form
     * 
     * @return string
     */
    public function create()
    {
        // Get dropdown options
        $academicYears = $this->classService->getAvailableAcademicYears();
        $teachers = $this->classService->getAvailableTeachers();
        $counselors = $this->classService->getAvailableCounselors();
        $gradeLevels = ClassValidation::getGradeLevelOptions();
        $majors = ClassValidation::getMajorOptions();

        // Get suggested class name for preview
        $suggestedName = '';
        if ($this->request->getGet('grade') && $this->request->getGet('academic_year')) {
            $suggestedName = ClassValidation::getSuggestedClassName(
                $this->request->getGet('grade'),
                $this->request->getGet('major') ?? '',
                $this->request->getGet('academic_year')
            );
        }

        $data = [
            'title' => 'Tambah Kelas',
            'page_title' => 'Tambah Kelas Baru',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Kelas', 'link' => base_url('admin/classes')],
                ['title' => 'Tambah Kelas', 'link' => null],
            ],
            'academic_years' => $academicYears,
            'teachers' => $teachers,
            'counselors' => $counselors,
            'grade_levels' => $gradeLevels,
            'majors' => $majors,
            'suggested_name' => $suggestedName,
            'validation' => \Config\Services::validation(),
        ];

        return view('admin/classes/form', $data);
    }

    /**
     * Store new class
     * 
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function store()
    {
        // Validate input
        $rules = ClassValidation::createRules();

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Sanitize input
        $data = ClassValidation::sanitizeInput($this->request->getPost());

        // Create class
        $result = $this->classService->createClass($data);

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/classes')
            ->with('success', $result['message']);
    }

    /**
     * Display edit class form
     * 
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function edit($id)
    {
        // Get class data
        $class = $this->classService->getClassById($id);

        if (!$class) {
            return redirect()->to('admin/classes')
                ->with('error', 'Kelas tidak ditemukan');
        }

        // Get dropdown options
        $academicYears = $this->classService->getAvailableAcademicYears();
        $teachers = $this->classService->getAvailableTeachers();
        $counselors = $this->classService->getAvailableCounselors();
        $gradeLevels = ClassValidation::getGradeLevelOptions();
        $majors = ClassValidation::getMajorOptions();

        $data = [
            'title' => 'Edit Kelas',
            'page_title' => 'Edit Kelas: ' . $class['class_name'],
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Kelas', 'link' => base_url('admin/classes')],
                ['title' => 'Edit Kelas', 'link' => null],
            ],
            'class' => $class,
            'academic_years' => $academicYears,
            'teachers' => $teachers,
            'counselors' => $counselors,
            'grade_levels' => $gradeLevels,
            'majors' => $majors,
            'validation' => \Config\Services::validation(),
        ];

        return view('admin/classes/form', $data);
    }

    /**
     * Update class data
     * 
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function update($id)
    {
        // Check if class exists
        $class = $this->classService->getClassById($id);
        if (!$class) {
            return redirect()->to('admin/classes')
                ->with('error', 'Kelas tidak ditemukan');
        }

        // Validate input
        $rules = ClassValidation::updateRules($id);

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Sanitize input
        $data = ClassValidation::sanitizeInput($this->request->getPost());

        // Update class
        $result = $this->classService->updateClass($id, $data);

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/classes')
            ->with('success', $result['message']);
    }

    /**
     * Delete class
     * 
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function delete($id)
    {
        // Delete class
        $result = $this->classService->deleteClass($id);

        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message']);
        }

        return redirect()->to('admin/classes')
            ->with('success', $result['message']);
    }

    /**
     * Display class detail with students
     * 
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function detail($id)
    {
        // Get class data
        $class = $this->classService->getClassById($id);

        if (!$class) {
            return redirect()->to('admin/classes')
                ->with('error', 'Kelas tidak ditemukan');
        }

        // Get students in this class
        $students = $this->classService->getClassStudents($id);

        $data = [
            'title' => 'Detail Kelas',
            'page_title' => 'Detail Kelas: ' . $class['class_name'],
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Kelas', 'link' => base_url('admin/classes')],
                ['title' => 'Detail', 'link' => null],
            ],
            'class' => $class,
            'students' => $students,
        ];

        return view('admin/classes/detail', $data);
    }

    /**
     * Get suggested class name via AJAX
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getSuggestedName()
    {
        $gradeLevel = $this->request->getGet('grade_level');
        $major = $this->request->getGet('major');
        $academicYearId = $this->request->getGet('academic_year_id');

        if (empty($gradeLevel) || empty($academicYearId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Parameter tidak lengkap',
            ]);
        }

        $suggestedName = ClassValidation::getSuggestedClassName(
            $gradeLevel,
            $major,
            $academicYearId
        );

        return $this->response->setJSON([
            'success' => true,
            'suggested_name' => $suggestedName,
        ]);
    }

    /**
     * Assign homeroom teacher via AJAX
     * 
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function assignHomeroom($id)
    {
        $teacherId = $this->request->getPost('teacher_id');

        if (empty($teacherId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Wali kelas harus dipilih',
            ]);
        }

        $result = $this->classService->assignHomeroom($id, $teacherId);

        return $this->response->setJSON($result);
    }

    /**
     * Assign counselor via AJAX
     * 
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function assignCounselor($id)
    {
        $counselorId = $this->request->getPost('counselor_id');

        if (empty($counselorId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Guru BK harus dipilih',
            ]);
        }

        $result = $this->classService->assignCounselor($id, $counselorId);

        return $this->response->setJSON($result);
    }
}
