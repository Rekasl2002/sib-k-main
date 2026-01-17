<?php

/**
 * File Path: app/Services/AcademicYearService.php
 *
 * Academic Year Service
 * Business logic layer untuk Academic Year management
 *
 * Update penting:
 * - year_name boleh duplikat untuk kebutuhan Ganjil & Genap (mis. 2024/2025)
 * - Service akan membatasi:
 *   1) maksimal 2 data dengan year_name yang sama (Ganjil & Genap)
 *   2) kombinasi (year_name + semester) tidak boleh dobel
 *
 * @package    SIB-K
 * @subpackage Services
 * @category   Business Logic
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Services;

use App\Models\AcademicYearModel;
use App\Models\ClassModel;
use App\Validation\AcademicYearValidation;

class AcademicYearService
{
    protected $academicYearModel;
    protected $classModel;
    protected $db;

    public function __construct()
    {
        $this->academicYearModel = new AcademicYearModel();
        $this->classModel        = new ClassModel();
        $this->db                = \Config\Database::connect();
    }

    /**
     * Paksa hasil model/entity/builder menjadi array biasa.
     */
    private function asArray($row): array
    {
        if (is_array($row)) {
            return $row;
        }
        if (is_object($row)) {
            if (method_exists($row, 'toArray')) {
                return $row->toArray();
            }
            // Fallback aman untuk CI4 entity/stdClass
            $arr = json_decode(json_encode($row), true);
            return is_array($arr) ? $arr : [];
        }
        return [];
    }

    /**
     * (Opsional tapi disarankan) Sanitasi order_by & order_dir agar aman.
     */
    private function sanitizeOrder(array $filters): array
    {
        $allowedColumns = [
            'academic_years.year_name',
            'academic_years.start_date',
            'academic_years.end_date',
            'academic_years.semester',
            'academic_years.is_active',
            'academic_years.created_at',
            'academic_years.updated_at',
            'academic_years.id',
        ];

        $orderBy  = $filters['order_by'] ?? 'academic_years.year_name';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');

        if (!in_array($orderBy, $allowedColumns, true)) {
            $orderBy = 'academic_years.year_name';
        }

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        return [$orderBy, $orderDir];
    }

    /**
     * Rule bisnis:
     * - year_name boleh duplikat, tapi maksimal 2 (Ganjil & Genap)
     * - kombinasi year_name + semester tidak boleh dobel
     *
     * @param string   $yearName
     * @param string   $semester
     * @param int|null $excludeId (untuk update, agar record sendiri tidak dihitung)
     * @return array ['ok' => bool, 'message' => string]
     */
    private function guardYearNameSemester(string $yearName, string $semester, ?int $excludeId = null): array
    {
        $yearName = trim($yearName);
        $semester = trim($semester);

        if ($yearName === '' || $semester === '') {
            return ['ok' => false, 'message' => 'Nama Tahun Ajaran dan Semester wajib diisi.'];
        }

        // semester harus sesuai pilihan
        if (!in_array($semester, ['Ganjil', 'Genap'], true)) {
            return ['ok' => false, 'message' => 'Semester harus Ganjil atau Genap.'];
        }

        $builder = $this->db->table('academic_years')
            ->select('id, semester')
            ->where('year_name', $yearName)
            ->where('deleted_at', null);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        $rows  = $builder->get()->getResultArray();
        $count = is_array($rows) ? count($rows) : 0;

        // Maksimal 2 record per year_name
        if ($count >= 2) {
            return [
                'ok'      => false,
                'message' => "Nama Tahun Ajaran \"{$yearName}\" sudah dipakai untuk 2 semester (Ganjil & Genap).",
            ];
        }

        // Tidak boleh dobel kombinasi year_name + semester
        foreach ($rows as $r) {
            if (strcasecmp($r['semester'] ?? '', $semester) === 0) {
                return [
                    'ok'      => false,
                    'message' => "Nama Tahun Ajaran \"{$yearName}\" untuk semester \"{$semester}\" sudah ada. Pilih semester yang lain.",
                ];
            }
        }

        return ['ok' => true, 'message' => 'OK'];
    }

    /**
     * Get all academic years with filter and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @param bool $paginate
     * @return array
     */
    public function getAllAcademicYears($filters = [], $perPage = 10, bool $paginate = true)
    {
        $builder = $this->academicYearModel
            ->select('academic_years.*,
                    (SELECT COUNT(*) FROM classes
                    WHERE classes.academic_year_id = academic_years.id
                        AND classes.deleted_at IS NULL) as class_count');

        // Apply filters
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('academic_years.is_active', $filters['is_active']);
        }

        if (!empty($filters['semester'])) {
            $builder->where('academic_years.semester', $filters['semester']);
        }

        if (!empty($filters['search'])) {
            $builder->like('academic_years.year_name', $filters['search']);
        }

        // Order by (sanitize)
        [$orderBy, $orderDir] = $this->sanitizeOrder($filters);
        $builder->orderBy($orderBy, $orderDir);

        // ✅ Mode tanpa paginate (untuk DataTables di View)
        if ($paginate === false) {
            $rows = $builder->findAll();

            return [
                'academic_years' => $rows,
                'pager'          => null,
                'total'          => is_array($rows) ? count($rows) : 0,
                'per_page'       => null,
                'current_page'   => 1,
                'last_page'      => 1,
            ];
        }

        // Mode paginate CI4
        $academicYears = $builder->paginate($perPage);
        $pager         = $this->academicYearModel->pager;

        return [
            'academic_years' => $academicYears,
            'pager'          => $pager,
            'total'          => $pager ? $pager->getTotal() : (is_array($academicYears) ? count($academicYears) : 0),
            'per_page'       => $perPage,
            'current_page'   => $pager ? $pager->getCurrentPage() : 1,
            'last_page'      => $pager ? $pager->getPageCount() : 1,
        ];
    }

    /**
     * Get academic year by ID with details
     *
     * @param int $id
     * @return array|null
     */
    public function getAcademicYearById($id)
    {
        $year = $this->academicYearModel->find($id);
        if (!$year) {
            return null;
        }

        // Normalisasi ke array
        $year = $this->asArray($year);

        // class_count
        $year['class_count'] = $this->classModel
            ->where('academic_year_id', $id)
            ->countAllResults();

        // classes
        $year['classes'] = $this->classModel
            ->select('classes.*, COUNT(students.id) AS student_count')
            ->join(
                'students',
                'students.class_id = classes.id AND students.status = "Aktif" AND students.deleted_at IS NULL',
                'left'
            )
            ->where('classes.academic_year_id', $id)
            ->groupBy('classes.id')
            ->findAll();

        // duration_days
        $year['duration_days'] = AcademicYearValidation::getDuration(
            $year['start_date'] ?? '',
            $year['end_date'] ?? ''
        );

        return $year;
    }

    /**
     * Create new academic year
     *
     * @param array $data
     * @return array ['success' => bool, 'message' => string, 'year_id' => int|null]
     */
    public function createAcademicYear($data)
    {
        try {
            // Sanitize input
            $data = AcademicYearValidation::sanitizeInput($data);

            // Validate year name format
            $yearNameCheck = AcademicYearValidation::validateYearName($data['year_name'] ?? '');
            if (!$yearNameCheck['valid']) {
                return [
                    'success' => false,
                    'message' => $yearNameCheck['message'],
                ];
            }

            // Validate date range
            $dateRangeCheck = AcademicYearValidation::validateDateRange($data['start_date'] ?? '', $data['end_date'] ?? '');
            if (!$dateRangeCheck['valid']) {
                return [
                    'success' => false,
                    'message' => $dateRangeCheck['message'],
                ];
            }

            // ✅ Guard: max 2 same year_name + no duplicate year_name+semester
            $guard = $this->guardYearNameSemester($data['year_name'] ?? '', $data['semester'] ?? '');
            if (!$guard['ok']) {
                return [
                    'success' => false,
                    'message' => $guard['message'],
                ];
            }

            // Start transaction
            $this->db->transStart();

            // If set as active, deactivate others first
            if (!empty($data['is_active']) && (int)$data['is_active'] === 1) {
                $this->deactivateAllAcademicYears();
            }

            // Insert academic year
            if (!$this->academicYearModel->insert($data)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal membuat tahun ajaran: ' . implode(', ', (array) $this->academicYearModel->errors()),
                ];
            }

            $yearId = $this->academicYearModel->getInsertID();

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan data',
                ];
            }

            // Log activity
            $this->logActivity('create', $yearId, "Tahun ajaran {$data['year_name']} berhasil dibuat");

            return [
                'success' => true,
                'message' => 'Tahun ajaran berhasil dibuat',
                'year_id' => $yearId,
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error creating academic year: ' . $e->getMessage());

            $detail = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production')
                ? (': ' . $e->getMessage())
                : '';

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem' . $detail,
            ];
        }
    }

    /**
     * Update academic year
     *
     * @param int $id
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateAcademicYear($id, $data)
    {
        try {
            // Check if academic year exists
            $year = $this->academicYearModel->find($id);
            if (!$year) {
                return [
                    'success' => false,
                    'message' => 'Tahun ajaran tidak ditemukan',
                ];
            }
            $year = $this->asArray($year); // ✅ normalisasi

            // Sanitize input
            $data = AcademicYearValidation::sanitizeInput($data);

            // Validate year name format
            $yearNameCheck = AcademicYearValidation::validateYearName($data['year_name'] ?? '');
            if (!$yearNameCheck['valid']) {
                return [
                    'success' => false,
                    'message' => $yearNameCheck['message'],
                ];
            }

            // Validate date range
            $dateRangeCheck = AcademicYearValidation::validateDateRange($data['start_date'] ?? '', $data['end_date'] ?? '');
            if (!$dateRangeCheck['valid']) {
                return [
                    'success' => false,
                    'message' => $dateRangeCheck['message'],
                ];
            }

            // ✅ Guard: max 2 same year_name + no duplicate year_name+semester (exclude current)
            $guard = $this->guardYearNameSemester($data['year_name'] ?? '', $data['semester'] ?? '', (int)$id);
            if (!$guard['ok']) {
                return [
                    'success' => false,
                    'message' => $guard['message'],
                ];
            }

            // Start transaction
            $this->db->transStart();

            // If set as active, deactivate others first
            if (!empty($data['is_active']) && (int)$data['is_active'] === 1 && ((int)($year['is_active'] ?? 0)) !== 1) {
                $this->deactivateAllAcademicYears($id);
            }

            // Update academic year
            if (!$this->academicYearModel->update($id, $data)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal mengupdate tahun ajaran: ' . implode(', ', (array) $this->academicYearModel->errors()),
                ];
            }

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan data',
                ];
            }

            // Log activity
            $this->logActivity('update', $id, "Tahun ajaran {$data['year_name']} berhasil diupdate");

            return [
                'success' => true,
                'message' => 'Tahun ajaran berhasil diupdate',
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error updating academic year: ' . $e->getMessage());

            $detail = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production')
                ? (': ' . $e->getMessage())
                : '';

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem' . $detail,
            ];
        }
    }

    /**
     * Delete academic year
     *
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteAcademicYear($id)
    {
        try {
            // Check if academic year exists
            $year = $this->academicYearModel->find($id);
            if (!$year) {
                return [
                    'success' => false,
                    'message' => 'Tahun ajaran tidak ditemukan',
                ];
            }
            $year = $this->asArray($year);

            // Check if can be deleted
            $canDeleteCheck = AcademicYearValidation::canDelete($id);
            if (!$canDeleteCheck['can_delete']) {
                return [
                    'success' => false,
                    'message' => $canDeleteCheck['message'],
                ];
            }

            // Start transaction
            $this->db->transStart();

            // Soft delete academic year
            if (!$this->academicYearModel->delete($id)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal menghapus tahun ajaran',
                ];
            }

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus data',
                ];
            }

            // Log activity
            $this->logActivity('delete', $id, "Tahun ajaran {$year['year_name']} berhasil dihapus");

            return [
                'success' => true,
                'message' => 'Tahun ajaran berhasil dihapus',
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error deleting academic year: ' . $e->getMessage());

            $detail = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production')
                ? (': ' . $e->getMessage())
                : '';

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem' . $detail,
            ];
        }
    }

    /**
     * Set academic year as active (deactivate others)
     *
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function setActiveAcademicYear($id)
    {
        try {
            // Check if academic year exists
            $year = $this->academicYearModel->find($id);
            if (!$year) {
                return [
                    'success' => false,
                    'message' => 'Tahun ajaran tidak ditemukan',
                ];
            }
            $year = $this->asArray($year);

            if ((int)($year['is_active'] ?? 0) === 1) {
                return [
                    'success' => true,
                    'message' => "Tahun ajaran {$year['year_name']} sudah aktif",
                ];
            }

            // Start transaction
            $this->db->transStart();

            // Deactivate all academic years
            $this->deactivateAllAcademicYears();

            // Activate this academic year
            if (!$this->academicYearModel->update($id, ['is_active' => 1])) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal mengaktifkan tahun ajaran',
                ];
            }

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan data',
                ];
            }

            helper('settings');
            set_setting('academic', 'default_academic_year_id', (int) $id, 'int');

            // Log activity
            $this->logActivity('set_active', $id, "Tahun ajaran {$year['year_name']} diset sebagai aktif");

            return [
                'success' => true,
                'message' => "Tahun ajaran {$year['year_name']} berhasil diaktifkan",
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error setting active academic year: ' . $e->getMessage());

            $detail = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production')
                ? (': ' . $e->getMessage())
                : '';

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem' . $detail,
            ];
        }
    }

    /**
     * Get active academic year
     *
     * @return array|null
     */
    public function getActiveAcademicYear()
    {
        $year = $this->academicYearModel
            ->where('is_active', 1)
            ->first();

        if (!$year) {
            return null;
        }

        $year = $this->asArray($year);

        // Get class count
        $year['class_count'] = $this->classModel
            ->where('academic_year_id', $year['id'])
            ->countAllResults();

        return $year;
    }

    /**
     * Get academic year statistics
     *
     * @return array
     */
    public function getAcademicYearStatistics()
    {
        // Pakai table builder biar tidak “ketularan” state query model sebelumnya
        $total  = $this->db->table('academic_years')->where('deleted_at', null)->countAllResults();
        $active = $this->db->table('academic_years')->where('deleted_at', null)->where('is_active', 1)->countAllResults();

        $stats = [
            'total'       => (int) $total,
            'active'      => (int) $active,
            'by_semester' => [],
        ];

        // Get count by semester
        $semesterStats = $this->db->table('academic_years')
            ->select('semester, COUNT(id) as total')
            ->where('deleted_at', null)
            ->groupBy('semester')
            ->get()
            ->getResultArray();

        foreach ($semesterStats as $stat) {
            $stats['by_semester'][$stat['semester']] = (int)$stat['total'];
        }

        return $stats;
    }

    /**
     * Deactivate all academic years
     *
     * @param int|null $excludeId Exclude this ID from deactivation
     * @return bool
     */
    protected function deactivateAllAcademicYears($excludeId = null)
    {
        $builder = $this->db->table('academic_years')
            ->set('is_active', 0);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->update();
    }

    /**
     * Check if academic year overlaps with existing years
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeId
     * @return array ['overlaps' => bool, 'conflicting_years' => array]
     */
    public function checkOverlap($startDate, $endDate, $excludeId = null)
    {
        $builder = $this->academicYearModel
            ->where('deleted_at', null)
            ->groupStart()
                ->groupStart()
                    ->where('start_date <=', $startDate)
                    ->where('end_date >=', $startDate)
                ->groupEnd()
                ->orGroupStart()
                    ->where('start_date <=', $endDate)
                    ->where('end_date >=', $endDate)
                ->groupEnd()
                ->orGroupStart()
                    ->where('start_date >=', $startDate)
                    ->where('end_date <=', $endDate)
                ->groupEnd()
            ->groupEnd();

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        $conflictingYears = $builder->findAll();

        return [
            'overlaps'          => !empty($conflictingYears),
            'conflicting_years' => $conflictingYears,
        ];
    }

    /**
     * Get suggested academic year based on latest
     *
     * @return array ['year_name' => string, 'semester' => string, 'start_date' => string, 'end_date' => string]
     */
    public function getSuggestedAcademicYear()
    {
        // Lebih stabil: ambil terbaru dari end_date
        $latest = $this->academicYearModel
            ->where('deleted_at', null)
            ->orderBy('end_date', 'DESC')
            ->orderBy('start_date', 'DESC')
            ->first();

        if (!$latest) {
            // No previous data, suggest current academic year
            $currentMonth = (int)date('m');
            $semester     = ($currentMonth >= 7) ? 'Ganjil' : 'Genap';
            $yearName     = AcademicYearValidation::generateYearName(date('Y-m-d'));
            $dateRange    = AcademicYearValidation::getDefaultDateRange($semester);

            return [
                'year_name'   => $yearName,
                'semester'    => $semester,
                'start_date'  => $dateRange['start_date'],
                'end_date'    => $dateRange['end_date'],
            ];
        }

        $latest = $this->asArray($latest);

        // Suggest next semester
        $parsed = AcademicYearValidation::parseYearName($latest['year_name'] ?? '');

        if (($latest['semester'] ?? '') === 'Ganjil') {
            // Next is Genap with same year
            $semester  = 'Genap';
            $yearName  = $latest['year_name'];
            $dateRange = AcademicYearValidation::getDefaultDateRange('Genap');

            // Adjust year for Genap
            $dateRange['start_date'] = ($parsed['year2'] ?: (int)date('Y')) . '-01-01';
            $dateRange['end_date']   = ($parsed['year2'] ?: (int)date('Y')) . '-06-30';
        } else {
            // Next is Ganjil with next year
            $semester  = 'Ganjil';
            $nextBase  = $parsed['year2'] ?: (int)date('Y');
            $yearName  = $nextBase . '/' . ($nextBase + 1);
            $dateRange = [
                'start_date' => $nextBase . '-07-01',
                'end_date'   => $nextBase . '-12-31',
            ];
        }

        return [
            'year_name'  => $yearName,
            'semester'   => $semester,
            'start_date' => $dateRange['start_date'],
            'end_date'   => $dateRange['end_date'],
        ];
    }

    /**
     * Log academic year activity
     *
     * @param string $action
     * @param int $yearId
     * @param string $description
     * @return void
     */
    private function logActivity($action, $yearId, $description)
    {
        log_message('info', "[AcademicYearService] Action: {$action}, Year ID: {$yearId}, Description: {$description}");
    }
}
