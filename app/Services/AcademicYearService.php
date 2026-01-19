<?php

/**
 * File Path: app/Services/AcademicYearService.php
 *
 * Academic Year Service
 * Business logic layer untuk Academic Year management
 *
 * Update penting:
 * - year_name boleh duplikat untuk kebutuhan Ganjil & Genap (mis. 2024/2025)
 * - Mendukung 3 semester: Ganjil, Genap, Ganjil-Genap
 *
 * Rule bisnis:
 * - Jika ada semester "Ganjil-Genap" untuk suatu year_name, maka tidak boleh ada record lain untuk year_name tsb.
 * - Jika mode split (Ganjil/Genap), maksimal 2 record per year_name dan tidak boleh dobel kombinasi (year_name + semester).
 *
 * @package    SIB-K
 * @subpackage Services
 * @category   Business Logic
 */

namespace App\Services;

use App\Models\AcademicYearModel;
use App\Models\ClassModel;
use App\Validation\AcademicYearValidation;
use CodeIgniter\Database\BaseConnection;

class AcademicYearService
{
    protected AcademicYearModel $academicYearModel;
    protected ClassModel $classModel;
    protected BaseConnection $db;

    /**
     * Opsional: aktifkan jika ingin mencegah bentrok rentang tanggal antar academic year.
     * Default: false (agar perilaku existing tidak berubah).
     */
    protected bool $enforceOverlapCheck = false;

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
     * - year_name boleh duplikat, tapi:
     *   - Jika ada "Ganjil-Genap" => harus single record untuk year_name itu.
     *   - Jika split => maksimal 2 record (Ganjil & Genap) dan tidak boleh dobel semester.
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

        $allowed = ['Ganjil', 'Genap', 'Ganjil-Genap'];
        if (!in_array($semester, $allowed, true)) {
            return ['ok' => false, 'message' => 'Semester harus Ganjil, Genap, atau Ganjil-Genap.'];
        }

        $builder = $this->db->table('academic_years')
            ->select('id, semester')
            ->where('year_name', $yearName)
            ->where('deleted_at', null);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        $rows = $builder->get()->getResultArray();

        // Deteksi apakah year_name ini sudah "mode gabungan"
        $hasCombined = false;
        foreach ($rows as $r) {
            if (strcasecmp((string)($r['semester'] ?? ''), 'Ganjil-Genap') === 0) {
                $hasCombined = true;
                break;
            }
        }

        // Kalau sudah ada Ganjil-Genap, tidak boleh tambah semester lain
        if ($hasCombined) {
            return [
                'ok' => false,
                'message' => "Nama Tahun Ajaran \"{$yearName}\" sudah memakai semester \"Ganjil-Genap\" (gabungan). Tidak bisa menambah semester lain.",
            ];
        }

        // Kalau user memilih Ganjil-Genap, harus benar-benar single record (tidak boleh ada Ganjil/Genap)
        if ($semester === 'Ganjil-Genap') {
            if (!empty($rows)) {
                return [
                    'ok' => false,
                    'message' => "Nama Tahun Ajaran \"{$yearName}\" sudah memiliki data semester (Ganjil/Genap). Tidak bisa memilih \"Ganjil-Genap\" untuk tahun ajaran yang sudah dipisah.",
                ];
            }
            return ['ok' => true, 'message' => 'OK'];
        }

        // Di sini berarti semester Ganjil / Genap (mode split)
        $count = is_array($rows) ? count($rows) : 0;

        if ($count >= 2) {
            return [
                'ok' => false,
                'message' => "Nama Tahun Ajaran \"{$yearName}\" sudah dipakai untuk 2 semester (Ganjil & Genap).",
            ];
        }

        foreach ($rows as $r) {
            if (strcasecmp((string)($r['semester'] ?? ''), $semester) === 0) {
                return [
                    'ok' => false,
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

        // class_count (konsisten dengan list: filter soft delete)
        $year['class_count'] = $this->classModel
            ->where('academic_year_id', $id)
            ->where('deleted_at', null)
            ->countAllResults();

        // classes (filter soft delete classes)
        $year['classes'] = $this->classModel
            ->select('classes.*, COUNT(students.id) AS student_count')
            ->join(
                'students',
                'students.class_id = classes.id AND students.status = "Aktif" AND students.deleted_at IS NULL',
                'left'
            )
            ->where('classes.academic_year_id', $id)
            ->where('classes.deleted_at', null)
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

            // (Opsional) Cek overlap tanggal jika suatu saat ingin diaktifkan
            if ($this->enforceOverlapCheck) {
                $ov = $this->checkOverlap($data['start_date'] ?? '', $data['end_date'] ?? '');
                if (!empty($ov['overlaps'])) {
                    return [
                        'success' => false,
                        'message' => 'Rentang tanggal tahun ajaran bentrok dengan data tahun ajaran lain.',
                    ];
                }
            }

            // ✅ Guard: fleksibel semester (split vs gabungan)
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
            // Aman walau transaksi belum dimulai, CI4 handle
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
            $year = $this->asArray($year);

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

            // (Opsional) Cek overlap tanggal jika suatu saat ingin diaktifkan
            if ($this->enforceOverlapCheck) {
                $ov = $this->checkOverlap($data['start_date'] ?? '', $data['end_date'] ?? '', (int)$id);
                if (!empty($ov['overlaps'])) {
                    return [
                        'success' => false,
                        'message' => 'Rentang tanggal tahun ajaran bentrok dengan data tahun ajaran lain.',
                    ];
                }
            }

            // ✅ Guard: fleksibel semester (exclude current)
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
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->first();

        if (!$year) {
            return null;
        }

        $year = $this->asArray($year);

        // Get class count
        $year['class_count'] = $this->classModel
            ->where('academic_year_id', $year['id'])
            ->where('deleted_at', null)
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
            $stats['by_semester'][$stat['semester']] = (int)($stat['total'] ?? 0);
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
            ->set('is_active', 0)
            ->where('deleted_at', null);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return (bool) $builder->update();
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

        $latestSemester = (string)($latest['semester'] ?? '');

        if ($latestSemester === 'Ganjil') {
            // Next is Genap with same year
            $semester  = 'Genap';
            $yearName  = $latest['year_name'];
            $dateRange = AcademicYearValidation::getDefaultDateRange('Genap');

            $parsed = AcademicYearValidation::parseYearName($latest['year_name'] ?? '');
            $dateRange['start_date'] = ($parsed['year2'] ?: (int)date('Y')) . '-01-01';
            $dateRange['end_date']   = ($parsed['year2'] ?: (int)date('Y')) . '-06-30';

        } elseif ($latestSemester === 'Genap') {
            // Next is Ganjil with next year
            $parsed   = AcademicYearValidation::parseYearName($latest['year_name'] ?? '');
            $nextBase = $parsed['year2'] ?: (int)date('Y');

            $semester  = 'Ganjil';
            $yearName  = $nextBase . '/' . ($nextBase + 1);
            $dateRange = [
                'start_date' => $nextBase . '-07-01',
                'end_date'   => $nextBase . '-12-31',
            ];

        } elseif ($latestSemester === 'Ganjil-Genap') {
            // Next is Ganjil-Genap with next year
            $parsed   = AcademicYearValidation::parseYearName($latest['year_name'] ?? '');
            $nextBase = $parsed['year2'] ?: (int)date('Y');

            $semester  = 'Ganjil-Genap';
            $yearName  = $nextBase . '/' . ($nextBase + 1);
            $dateRange = [
                'start_date' => $nextBase . '-07-01',
                'end_date'   => ($nextBase + 1) . '-06-30',
            ];

        } else {
            // fallback aman
            $semester  = 'Ganjil';
            $yearName  = AcademicYearValidation::generateYearName(date('Y-m-d'));
            $dateRange = AcademicYearValidation::getDefaultDateRange('Ganjil');
        }

        return [
            'year_name'  => $yearName,
            'semester'   => $semester,
            'start_date' => $dateRange['start_date'],
            'end_date'   => $dateRange['end_date'],
        ];
    }

    /**
     * Dropdown opsi year_name (gabungan data existing + generate range)
     */
    public function getYearNameOptions(int $yearsBack = 10, int $yearsForward = 5): array
    {
        $current = (int) date('Y');

        // Generate range
        $generated = [];
        for ($y = $current - $yearsBack; $y <= $current + $yearsForward; $y++) {
            $generated[] = $y . '/' . ($y + 1);
        }

        // Ambil year_name existing dari DB
        $existingRows = $this->academicYearModel
            ->select('year_name')
            ->where('deleted_at', null)
            ->groupBy('year_name')
            ->orderBy('year_name', 'DESC')
            ->findAll();

        $existing = [];
        foreach ((array)$existingRows as $r) {
            $yn = trim((string)($r['year_name'] ?? ''));
            if ($yn !== '') {
                $existing[] = $yn;
            }
        }

        $all = array_values(array_unique(array_merge($existing, $generated)));

        // Sort DESC berdasarkan tahun pertama
        usort($all, function ($a, $b) {
            $ay = (int) substr((string)$a, 0, 4);
            $by = (int) substr((string)$b, 0, 4);
            return $by <=> $ay;
        });

        return $all;
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
