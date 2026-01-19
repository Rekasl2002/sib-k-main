<?php

/**
 * File Path: app/Services/ViolationService.php
 *
 * Violation Service
 * Business logic layer untuk Case & Violation Management
 *
 * Catatan revisi (KP):
 * - Total poin pelanggaran disimpan di students.total_violation_points sebagai cache,
 *   dan dihitung PER TAHUN AJARAN (year_name) aktif, menggabungkan ganjil+genap.
 * - Filter index/report bisa memakai academic_year (year_name) atau date range.
 *
 * NOTE:
 * - Untuk menjaga kompatibilitas berbagai versi model, perhitungan range TA dilakukan di Service
 *   via query builder (range-aware), sehingga tidak bergantung pada signature method model tertentu.
 * - students.full_name sudah dihapus -> dropdown siswa harus join ke users.full_name.
 */

namespace App\Services;

use App\Models\ViolationModel;
use App\Models\ViolationCategoryModel;
use App\Models\SanctionModel;
use App\Models\StudentModel;

class ViolationService
{
    protected $violationModel;
    protected $categoryModel;
    protected $sanctionModel;
    protected $studentModel;
    protected $returnType = 'array';
    protected $db;

    public function __construct()
    {
        $this->violationModel = new ViolationModel();
        $this->categoryModel  = new ViolationCategoryModel();
        $this->sanctionModel  = new SanctionModel();
        $this->studentModel   = new StudentModel();
        $this->db             = \Config\Database::connect();
    }

    // ==========================================================
    // Tahun Ajaran (School Year) helpers
    // ==========================================================

    /**
     * Resolve range tanggal untuk 1 Tahun Ajaran berdasarkan year_name.
     * Jika $yearName kosong, akan:
     * 1) ambil academic_years.is_active=1,
     * 2) fallback ke baris yang mencakup tanggal hari ini.
     *
     * @return array{year_name:?string,date_from:?string,date_to:?string}
     */
    private function resolveSchoolYearRange(?string $yearName = null): array
    {
        $yearName = trim((string) ($yearName ?? ''));

        // 1) Prefer Tahun Ajaran aktif
        if ($yearName === '') {
            $row = $this->db->table('academic_years')
                ->select('year_name')
                ->where('deleted_at', null)
                ->where('is_active', 1)
                ->orderBy('updated_at', 'DESC')
                ->get(1)
                ->getRowArray();

            $yearName = trim((string) ($row['year_name'] ?? ''));
        }

        // 2) Fallback: Tahun Ajaran berdasarkan "hari ini"
        if ($yearName === '') {
            $today = date('Y-m-d');
            $row = $this->db->table('academic_years')
                ->select('year_name')
                ->where('deleted_at', null)
                ->where('start_date <=', $today)
                ->where('end_date >=', $today)
                ->orderBy('start_date', 'DESC')
                ->get(1)
                ->getRowArray();

            $yearName = trim((string) ($row['year_name'] ?? ''));
        }

        if ($yearName === '') {
            return ['year_name' => null, 'date_from' => null, 'date_to' => null];
        }

        // Gabungkan semester: MIN(start_date) & MAX(end_date)
        $range = $this->db->table('academic_years')
            ->select('MIN(start_date) as date_from, MAX(end_date) as date_to', false)
            ->where('deleted_at', null)
            ->where('year_name', $yearName)
            ->get()
            ->getRowArray();

        return [
            'year_name' => $yearName,
            'date_from' => ($range['date_from'] ?? null) ?: null,
            'date_to'   => ($range['date_to'] ?? null) ?: null,
        ];
    }

    /**
     * Jika filters memiliki academic_year_id (mis. semester id), ubah jadi year_name.
     */
    private function resolveYearNameFromAcademicYearId($academicYearId): ?string
    {
        $id = (int) $academicYearId;
        if ($id <= 0) return null;

        $row = $this->db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('id', $id)
            ->get(1)
            ->getRowArray();

        $yn = trim((string) ($row['year_name'] ?? ''));
        return $yn !== '' ? $yn : null;
    }

    /**
     * Normalisasi filter:
     * - Jika user memilih academic_year (year_name) dan date_from/date_to kosong,
     *   maka otomatis isi date_from/date_to dari range Tahun Ajaran tsb.
     * - Jika user mengisi date_from/date_to manual, maka itu yang dipakai (override).
     * - Support academic_year_id (semester) -> year_name.
     */
    public function normalizeAcademicYearFilter(array $filters): array
    {
        $yearName = trim((string) ($filters['academic_year'] ?? ''));

        if ($yearName === '' && !empty($filters['academic_year_id'])) {
            $yn = $this->resolveYearNameFromAcademicYearId($filters['academic_year_id']);
            if ($yn) $yearName = $yn;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo   = trim((string) ($filters['date_to'] ?? ''));

        // Manual range override
        if ($dateFrom !== '' || $dateTo !== '') {
            $filters['academic_year_resolved'] = null;
            return $filters;
        }

        // Map tahun ajaran ke range
        if ($yearName !== '') {
            $range = $this->resolveSchoolYearRange($yearName);
            if (!empty($range['date_from'])) $filters['date_from'] = $range['date_from'];
            if (!empty($range['date_to']))   $filters['date_to']   = $range['date_to'];
            $filters['academic_year_resolved'] = $range['year_name'];
            return $filters;
        }

        $filters['academic_year_resolved'] = null;
        return $filters;
    }

    /**
     * Dropdown Tahun Ajaran (unik per year_name).
     * Aman (hindari kasus SELECT `DISTINCT` ... yang ter-escape).
     *
     * @return string[]
     */
    public function getAcademicYearOptions(): array
    {
        $rows = $this->db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('year_name IS NOT NULL', null, false)
            ->where('year_name !=', '')
            ->groupBy('year_name')
            ->orderBy('year_name', 'DESC')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $yn = trim((string) ($r['year_name'] ?? ''));
            if ($yn !== '') $out[] = $yn;
        }
        return $out;
    }

    // ==========================================================
    // Core helpers untuk perhitungan poin (range aware)
    // ==========================================================

    private function calcStudentTotalPoints(int $studentId, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        if ($studentId <= 0) return 0;

        $qb = $this->db->table('violations v')
            ->select('COALESCE(SUM(vc.point_deduction),0) AS total_points', false)
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.deleted_at', null)
            ->where('v.student_id', $studentId)
            ->where('v.status !=', 'Dibatalkan');

        $dateFrom = trim((string)($dateFrom ?? ''));
        $dateTo   = trim((string)($dateTo ?? ''));

        if ($dateFrom !== '') $qb->where('v.violation_date >=', $dateFrom);
        if ($dateTo !== '')   $qb->where('v.violation_date <=', $dateTo);

        $row = $qb->get()->getRowArray();
        $total = (int) ($row['total_points'] ?? 0);

        return max(0, $total);
    }

    private function fetchStudentViolations(int $studentId, ?string $dateFrom = null, ?string $dateTo = null, ?int $limit = null): array
    {
        if ($studentId <= 0) return [];

        $qb = $this->db->table('violations v')
            ->select('v.*, vc.category_name, vc.severity_level, vc.point_deduction')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.deleted_at', null)
            ->where('v.student_id', $studentId)
            ->where('v.status !=', 'Dibatalkan');

        $dateFrom = trim((string)($dateFrom ?? ''));
        $dateTo   = trim((string)($dateTo ?? ''));

        if ($dateFrom !== '') $qb->where('v.violation_date >=', $dateFrom);
        if ($dateTo !== '')   $qb->where('v.violation_date <=', $dateTo);

        $qb->orderBy('v.violation_date', 'DESC')
           ->orderBy('v.created_at', 'DESC');

        if ($limit !== null && (int)$limit > 0) {
            $qb->limit((int)$limit);
        }

        return $qb->get()->getResultArray();
    }

    // ==========================================================
    // CRUD
    // ==========================================================

    public function createViolation($data)
    {
        try {
            $category = $this->categoryModel->find($data['category_id'] ?? null);
            if (!$category || (int)($category['is_active'] ?? 0) !== 1) {
                return ['success' => false, 'message' => 'Kategori pelanggaran tidak valid atau tidak aktif'];
            }

            $student = $this->studentModel->find($data['student_id'] ?? null);
            if (!$student) {
                return ['success' => false, 'message' => 'Siswa tidak ditemukan'];
            }

            if (!isset($data['status']) || $data['status'] === '') {
                $data['status'] = 'Dilaporkan';
            }

            if (!isset($data['reported_by']) || !$data['reported_by']) {
                if (function_exists('auth_id')) {
                    $data['reported_by'] = auth_id();
                }
            }

            $violationId = $this->violationModel->insert($data);
            if (!$violationId) {
                return [
                    'success' => false,
                    'message' => 'Gagal menyimpan data pelanggaran',
                    'errors'  => $this->violationModel->errors(),
                ];
            }

            if (isset($data['student_id'])) {
                $this->syncStudentViolationPoints((int) $data['student_id']);
            }

            $nisn = $student['nisn'] ?? ($student['nis'] ?? 'unknown');
            $this->logActivity('create_violation', (int)$violationId, "Pelanggaran baru dilaporkan untuk siswa: {$nisn}");

            return [
                'success'      => true,
                'message'      => 'Data pelanggaran berhasil disimpan',
                'violation_id' => (int)$violationId,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::createViolation - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    public function updateViolation($id, $data)
    {
        try {
            $violation = $this->violationModel->asArray()->find($id);
            if (!$violation) {
                return ['success' => false, 'message' => 'Data pelanggaran tidak ditemukan'];
            }

            $oldStudentId = (int) ($violation['student_id'] ?? 0);

            $statusValidation = $this->validateStatusTransition(
                (string)($violation['status'] ?? ''),
                (string)($data['status'] ?? ($violation['status'] ?? ''))
            );

            if (!$statusValidation['valid']) {
                return ['success' => false, 'message' => $statusValidation['message']];
            }

            if (isset($data['status']) && $data['status'] === 'Selesai' && empty($data['resolution_date'])) {
                $data['resolution_date'] = date('Y-m-d');
            }

            $updated = $this->violationModel->update($id, $data);
            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Gagal memperbarui data pelanggaran',
                    'errors'  => $this->violationModel->errors(),
                ];
            }

            if ($oldStudentId > 0) {
                $this->syncStudentViolationPoints($oldStudentId);
            }

            if (isset($data['student_id'])) {
                $newStudentId = (int) $data['student_id'];
                if ($newStudentId > 0 && $newStudentId !== $oldStudentId) {
                    $this->syncStudentViolationPoints($newStudentId);
                }
            }

            $this->logActivity('update_violation', (int)$id, 'Data pelanggaran diperbarui');

            return ['success' => true, 'message' => 'Data pelanggaran berhasil diperbarui'];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::updateViolation - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    private function validateStatusTransition($currentStatus, $newStatus)
    {
        $allowedTransitions = [
            'Dilaporkan'   => ['Dalam Proses', 'Dibatalkan'],
            'Dalam Proses' => ['Selesai', 'Dibatalkan'],
            'Selesai'      => [],
            'Dibatalkan'   => [],
        ];

        if ($currentStatus === $newStatus) {
            return ['valid' => true, 'message' => 'Status tidak berubah'];
        }

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
            return [
                'valid'   => false,
                'message' => "Tidak dapat mengubah status dari '{$currentStatus}' menjadi '{$newStatus}'",
            ];
        }

        return ['valid' => true, 'message' => 'Transisi status valid'];
    }

    public function getViolations($filters = [])
    {
        $filters = $this->normalizeAcademicYearFilter((array) $filters);
        return $this->violationModel->getViolationsWithFilters($filters);
    }

    public function getViolationDetail($id)
    {
        $violation = $this->violationModel->getViolationWithDetails($id);
        if (!$violation) return null;

        $violation['sanctions'] = $this->sanctionModel->getByViolation($id);

        if (!empty($violation['evidence'])) {
            $decoded = json_decode($violation['evidence'], true);
            $violation['evidence_files'] = is_array($decoded) ? $decoded : [];
        }

        return $violation;
    }

    public function getDashboardStats($filters = [])
    {
        $filters = $this->normalizeAcademicYearFilter((array) $filters);

        $stats = $this->violationModel->getStatistics($filters);
        $statsBySeverity = $this->violationModel->getStatsBySeverity($filters);
        $topViolators = $this->violationModel->getTopViolators(5, $filters);

        $pendingNotifications = $this->violationModel->getPendingNotifications(10);

        return [
            'overall'               => $stats,
            'by_severity'           => $statsBySeverity,
            'top_violators'         => $topViolators,
            'pending_notifications' => is_countable($pendingNotifications) ? count($pendingNotifications) : 0,
        ];
    }

    public function getStudentViolationHistory($studentId, array $filters = [])
    {
        $studentId = (int) $studentId;

        if (!empty($filters['all_time'])) {
            $violations = $this->violationModel->getByStudent($studentId);

            $stats = [
                'total_violations'    => count($violations),
                'total_points'        => $this->calcStudentTotalPoints($studentId, null, null),
                'by_severity'         => ['Ringan' => 0, 'Sedang' => 0, 'Berat' => 0],
                'by_status'           => ['Dilaporkan' => 0, 'Dalam Proses' => 0, 'Selesai' => 0, 'Dibatalkan' => 0],
                'is_repeat_offender'  => false,
                'last_violation_date' => null,
            ];

            foreach ($violations as $violation) {
                if (isset($stats['by_severity'][$violation['severity_level'] ?? ''])) {
                    $stats['by_severity'][$violation['severity_level']]++;
                }
                if (isset($stats['by_status'][$violation['status'] ?? ''])) {
                    $stats['by_status'][$violation['status']]++;
                }
                if (!empty($violation['is_repeat_offender'])) {
                    $stats['is_repeat_offender'] = true;
                }
                if (!empty($violation['violation_date']) && (empty($stats['last_violation_date']) || $violation['violation_date'] > $stats['last_violation_date'])) {
                    $stats['last_violation_date'] = $violation['violation_date'];
                }
            }

            return [
                'violations'      => $violations,
                'statistics'      => $stats,
                'filters_applied' => $filters,
            ];
        }

        $filters = $this->normalizeAcademicYearFilter($filters);

        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $range = $this->resolveSchoolYearRange(null);
            if (!empty($range['date_from'])) $filters['date_from'] = $range['date_from'];
            if (!empty($range['date_to']))   $filters['date_to']   = $range['date_to'];
            $filters['academic_year_resolved'] = $range['year_name'] ?? null;
        }

        $dateFrom = $filters['date_from'] ?? null;
        $dateTo   = $filters['date_to'] ?? null;

        $violations  = $this->fetchStudentViolations($studentId, $dateFrom, $dateTo, null);
        $totalPoints = $this->calcStudentTotalPoints($studentId, $dateFrom, $dateTo);

        $stats = [
            'total_violations'    => count($violations),
            'total_points'        => (int) $totalPoints,
            'by_severity'         => ['Ringan' => 0, 'Sedang' => 0, 'Berat' => 0],
            'by_status'           => ['Dilaporkan' => 0, 'Dalam Proses' => 0, 'Selesai' => 0, 'Dibatalkan' => 0],
            'is_repeat_offender'  => false,
            'last_violation_date' => null,
        ];

        foreach ($violations as $violation) {
            if (isset($stats['by_severity'][$violation['severity_level'] ?? ''])) {
                $stats['by_severity'][$violation['severity_level']]++;
            }
            if (isset($stats['by_status'][$violation['status'] ?? ''])) {
                $stats['by_status'][$violation['status']]++;
            }
            if (!empty($violation['is_repeat_offender'])) {
                $stats['is_repeat_offender'] = true;
            }
            if (!empty($violation['violation_date']) && (empty($stats['last_violation_date']) || $violation['violation_date'] > $stats['last_violation_date'])) {
                $stats['last_violation_date'] = $violation['violation_date'];
            }
        }

        return [
            'violations'      => $violations,
            'statistics'      => $stats,
            'filters_applied' => $filters,
        ];
    }

    public function notifyParent($violationId)
    {
        try {
            $violation = $this->violationModel->getViolationWithDetails($violationId);
            if (!$violation) return ['success' => false, 'message' => 'Data pelanggaran tidak ditemukan'];

            if (!empty($violation['parent_notified'])) {
                return ['success' => false, 'message' => 'Orang tua sudah dinotifikasi sebelumnya'];
            }

            if (method_exists($this->violationModel, 'markParentNotified')) {
                $this->violationModel->markParentNotified($violationId);
            } else {
                $this->violationModel->update($violationId, [
                    'parent_notified'    => 1,
                    'parent_notified_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->logActivity('notify_parent', (int)$violationId, 'Notifikasi dikirim ke orang tua');

            return ['success' => true, 'message' => 'Notifikasi berhasil dikirim ke orang tua'];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::notifyParent - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }

    public function generateReport($filters = [])
    {
        $filters = $this->normalizeAcademicYearFilter((array) $filters);

        $violations = $this->violationModel->getViolationsWithFilters($filters);
        $statistics      = $this->violationModel->getStatistics($filters);
        $statsBySeverity = $this->violationModel->getStatsBySeverity($filters);
        $topViolators    = $this->violationModel->getTopViolators(10, $filters);

        $commonCategories = $this->db->table('violations')
            ->select('violation_categories.category_name, violation_categories.severity_level, COUNT(violations.id) as violation_count')
            ->join('violation_categories', 'violation_categories.id = violations.category_id')
            ->where('violations.deleted_at', null)
            ->where('violations.status !=', 'Dibatalkan');

        if (!empty($filters['date_from'])) $commonCategories->where('violations.violation_date >=', $filters['date_from']);
        if (!empty($filters['date_to']))   $commonCategories->where('violations.violation_date <=', $filters['date_to']);

        $commonCategories = $commonCategories
            ->groupBy('violations.category_id')
            ->orderBy('violation_count', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return [
            'violations' => $violations,
            'statistics' => [
                'overall'           => $statistics,
                'by_severity'       => $statsBySeverity,
                'common_categories' => $commonCategories,
            ],
            'top_violators'   => $topViolators,
            'filters_applied' => $filters,
            'generated_at'    => date('Y-m-d H:i:s'),
        ];
    }

    public function checkPromotionEligibility($studentId)
    {
        $studentId = (int) $studentId;

        $range = $this->resolveSchoolYearRange(null);
        $yearName = $range['year_name'] ?? null;

        $totalPoints = $this->calcStudentTotalPoints(
            $studentId,
            $range['date_from'] ?? null,
            $range['date_to'] ?? null
        );

        $threshold = 100;

        if ($totalPoints >= $threshold) {
            return [
                'eligible'     => false,
                'message'      => "Siswa tidak memenuhi syarat kenaikan kelas karena memiliki {$totalPoints} poin pelanggaran (threshold: {$threshold} poin)",
                'total_points' => $totalPoints,
                'threshold'    => $threshold,
                'year'         => $yearName,
                'date_from'    => $range['date_from'] ?? null,
                'date_to'      => $range['date_to'] ?? null,
            ];
        }

        return [
            'eligible'     => true,
            'message'      => "Siswa memenuhi syarat kenaikan kelas dengan {$totalPoints} poin pelanggaran",
            'total_points' => $totalPoints,
            'threshold'    => $threshold,
            'year'         => $yearName,
            'date_from'    => $range['date_from'] ?? null,
            'date_to'      => $range['date_to'] ?? null,
        ];
    }

    public function getActiveCategories()
    {
        if (method_exists($this->categoryModel, 'getActiveCategories')) {
            return $this->categoryModel->getActiveCategories();
        }

        return $this->categoryModel
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('severity_level', 'ASC')
            ->orderBy('category_name', 'ASC')
            ->findAll();
    }

    public function getCategoriesGrouped()
    {
        $categories = $this->getActiveCategories();

        $grouped = ['Ringan' => [], 'Sedang' => [], 'Berat' => []];

        foreach ($categories as $category) {
            $severity = $category['severity_level'] ?? 'Ringan';
            if (!isset($grouped[$severity])) $grouped[$severity] = [];
            $grouped[$severity][] = $category;
        }

        return $grouped;
    }

    public function bulkUpdateStatus($violationIds, $status)
    {
        try {
            $updatedCount = 0;

            foreach ($violationIds as $id) {
                $result = $this->updateViolation($id, ['status' => $status]);
                if (!empty($result['success'])) $updatedCount++;
            }

            return [
                'success'       => true,
                'message'       => "{$updatedCount} pelanggaran berhasil diperbarui",
                'updated_count' => $updatedCount,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::bulkUpdateStatus - ' . $e->getMessage());
            return [
                'success'       => false,
                'message'       => 'Terjadi kesalahan: ' . $e->getMessage(),
                'updated_count' => 0,
            ];
        }
    }

    public function deleteViolation($id)
    {
        try {
            $violation = $this->violationModel->find($id);
            if (!$violation) {
                return ['success' => false, 'message' => 'Data pelanggaran tidak ditemukan'];
            }

            $studentId = (int) ($violation['student_id'] ?? 0);

            $sanctions = $this->sanctionModel->getByViolation($id);
            if (count($sanctions) > 0) {
                return [
                    'success' => false,
                    'message' => 'Pelanggaran tidak dapat dihapus karena sudah memiliki sanksi. Silakan hapus sanksi terlebih dahulu.',
                ];
            }

            $this->violationModel->delete($id);

            if ($studentId > 0) {
                $this->syncStudentViolationPoints($studentId);
            }

            $this->logActivity('delete_violation', (int)$id, 'Pelanggaran dihapus');

            return ['success' => true, 'message' => 'Data pelanggaran berhasil dihapus'];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::deleteViolation - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }

    /**
     * Sinkron 1 siswa (cache) untuk Tahun Ajaran AKTIF.
     * Dipakai otomatis saat create/update/delete violation.
     */
    private function syncStudentViolationPoints($studentId)
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) return;

        try {
            $range = $this->resolveSchoolYearRange(null);

            $total = $this->calcStudentTotalPoints(
                $studentId,
                $range['date_from'] ?? null,
                $range['date_to'] ?? null
            );

            $this->studentModel->update($studentId, [
                'total_violation_points' => $total,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::syncStudentViolationPoints gagal untuk student_id ' . $studentId . ' - ' . $e->getMessage());
        }
    }

    public function syncAllStudentsViolationPoints(): array
    {
        // Kompatibilitas lama: default tetap Tahun Ajaran aktif
        return $this->syncAllStudentsViolationPointsWithFilters([]);
    }

    /**
     * Sinkronisasi cache poin untuk semua siswa dengan pilihan filter:
     * - default: Tahun Ajaran aktif (jika tanpa input)
     * - bisa: academic_year (year_name) atau academic_year_id
     * - bisa: date_from/date_to (override)
     *
     * WARNING:
     * - Ini akan MENIMPA students.total_violation_points (1 kolom global).
     */
    public function syncAllStudentsViolationPointsWithFilters(array $filters = []): array
    {
        try {
            $filters = $this->normalizeAcademicYearFilter((array) $filters);

            $dateFrom = trim((string)($filters['date_from'] ?? ''));
            $dateTo   = trim((string)($filters['date_to'] ?? ''));

            if ($dateFrom === '' && $dateTo === '') {
                $range = $this->resolveSchoolYearRange(null);
                $dateFrom = trim((string)($range['date_from'] ?? ''));
                $dateTo   = trim((string)($range['date_to'] ?? ''));
                $filters['academic_year_resolved'] = $range['year_name'] ?? null;
            }

            $resolvedYear = $filters['academic_year_resolved'] ?? null;

            $students = $this->studentModel
                ->select('id')
                ->where('status', 'Aktif')
                ->findAll();

            $totalStudents = 0;

            foreach ($students as $row) {
                $studentId = (int) ($row['id'] ?? 0);
                if ($studentId <= 0) continue;

                $total = $this->calcStudentTotalPoints(
                    $studentId,
                    $dateFrom !== '' ? $dateFrom : null,
                    $dateTo !== '' ? $dateTo : null
                );

                $this->studentModel->update($studentId, [
                    'total_violation_points' => $total,
                ]);

                $totalStudents++;
            }

            $labelParts = [];
            if (!empty($resolvedYear)) $labelParts[] = "Tahun Ajaran {$resolvedYear}";
            if ($dateFrom !== '' || $dateTo !== '') $labelParts[] = "Periode {$dateFrom} s/d {$dateTo}";
            $label = !empty($labelParts) ? (' (' . implode(' | ', $labelParts) . ')') : '';

            return [
                'success' => true,
                'message' => "Sinkronisasi poin pelanggaran berhasil untuk {$totalStudents} siswa aktif{$label}.",
            ];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::syncAllStudentsViolationPointsWithFilters - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menyinkronkan poin pelanggaran: ' . $e->getMessage()];
        }
    }

    public function syncAllStudentsViolationPointsForYear(string $yearName): array
    {
        $yearName = trim($yearName);
        if ($yearName === '') return ['success' => false, 'message' => 'Tahun Ajaran tidak valid.'];

        return $this->syncAllStudentsViolationPointsWithFilters(['academic_year' => $yearName]);
    }

    private function logActivity($action, $violationId, $description)
    {
        $userId = function_exists('auth_id') ? auth_id() : 'system';
        log_message('info', "[ViolationService] Action: {$action}, Violation ID: {$violationId}, Description: {$description}, User: {$userId}");
    }

    // ==========================================================
    // Tambahan method untuk kebutuhan Koordinator/Counselor views
    // ==========================================================

    public function getAllViolations(array $filters = [])
    {
        return $this->getViolations($filters);
    }

    public function getViolationWithSanctions(int $id): ?array
    {
        return $this->getViolationDetail($id);
    }

    public function getViolationById(int $id): ?array
    {
        return $this->violationModel->asArray()->find($id);
    }

    public function getViolationEvidence(int $id): array
    {
        $row = $this->violationModel->asArray()->select('evidence')->find($id);
        if (!$row) return [];

        $raw = $row['evidence'] ?? null;
        if (!$raw) return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getStudentsForFilter(): array
    {
        return $this->db->table('students s')
            ->select('s.id, u.full_name AS full_name, s.nisn, s.nis, s.class_id, s.status')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'inner')
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getStudentsForViolation(): array
    {
        return $this->db->table('students s')
            ->select('s.id, u.full_name AS full_name, s.nisn, s.nis, s.class_id')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'inner')
            ->where('s.deleted_at', null)
            ->where('s.status', 'Aktif')
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getViolationCategories(): array
    {
        if (method_exists($this->categoryModel, 'getActiveCategories')) {
            return $this->categoryModel->getActiveCategories();
        }

        return $this->categoryModel
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('severity_level', 'ASC')
            ->orderBy('category_name', 'ASC')
            ->findAll();
    }

    public function getCounselors(): array
    {
        return $this->db->table('users')
            ->select('id, full_name, email, phone')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('role_id', 3)
            ->orderBy('full_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function assignCounselor(int $violationId, int $counselorId): array
    {
        try {
            $violation = $this->violationModel->asArray()->find($violationId);
            if (!$violation) return ['success' => false, 'message' => 'Data pelanggaran tidak ditemukan'];

            $counselor = $this->db->table('users')
                ->select('id')
                ->where('deleted_at', null)
                ->where('is_active', 1)
                ->where('role_id', 3)
                ->where('id', $counselorId)
                ->get()
                ->getRowArray();

            if (!$counselor) {
                return ['success' => false, 'message' => 'Guru BK tidak valid atau tidak aktif'];
            }

            $payload = ['handled_by' => $counselorId];

            if (($violation['status'] ?? '') === 'Dilaporkan') {
                $payload['status'] = 'Dalam Proses';
            }

            $ok = $this->violationModel->update($violationId, $payload);
            if (!$ok) {
                return [
                    'success' => false,
                    'message' => 'Gagal menugaskan Guru BK',
                    'errors'  => $this->violationModel->errors(),
                ];
            }

            $sid = (int)($violation['student_id'] ?? 0);
            if ($sid > 0) {
                $this->syncStudentViolationPoints($sid);
            }

            $this->logActivity('assign_counselor', $violationId, 'Assign Guru BK (handled_by)');

            return ['success' => true, 'message' => 'Guru BK berhasil ditugaskan'];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::assignCounselor - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }

    public function getViolationStats(array $filters = []): array
    {
        $filters = $this->normalizeAcademicYearFilter((array) $filters);

        $overall = [];
        try {
            if (method_exists($this->violationModel, 'getStatistics')) {
                $overall = (array) $this->violationModel->getStatistics($filters);
            }
        } catch (\Throwable $e) {
            $overall = [];
        }

        $needKeys = ['total_violations', 'in_process', 'completed', 'parents_not_notified', 'repeat_offenders'];
        $missing = array_filter($needKeys, fn($k) => !array_key_exists($k, $overall));

        if (!empty($missing)) {
            $calc = $this->calculateOverallStatsFallback($filters);
            foreach ($missing as $k) {
                $overall[$k] = $calc[$k] ?? 0;
            }
        }

        $bySeverity = [];
        try {
            if (method_exists($this->violationModel, 'getStatsBySeverity')) {
                $bySeverity = (array) $this->violationModel->getStatsBySeverity($filters);
            }
        } catch (\Throwable $e) {
            $bySeverity = [];
        }

        return [
            'overall'     => $overall,
            'by_severity' => $bySeverity,
        ];
    }

    private function calculateOverallStatsFallback(array $filters = []): array
    {
        $b = $this->db->table('violations v');

        $needJoinCat = !empty($filters['severity_level']);
        if ($needJoinCat) {
            $b->join('violation_categories vc', 'vc.id = v.category_id', 'left');
        }

        $needJoinStudent = !empty($filters['search']);
        if ($needJoinStudent) {
            $b->join('students s', 's.id = v.student_id AND s.deleted_at IS NULL', 'left');
            $b->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left');
        }

        $b->where('v.deleted_at', null);

        if (!empty($filters['status'])) $b->where('v.status', $filters['status']);
        if (!empty($filters['student_id'])) $b->where('v.student_id', (int) $filters['student_id']);
        if (!empty($filters['category_id'])) $b->where('v.category_id', (int) $filters['category_id']);
        if (!empty($filters['date_from'])) $b->where('v.violation_date >=', $filters['date_from']);
        if (!empty($filters['date_to'])) $b->where('v.violation_date <=', $filters['date_to']);
        if (!empty($filters['is_repeat_offender'])) $b->where('v.is_repeat_offender', 1);
        if (!empty($filters['parent_notified']) && $filters['parent_notified'] === 'no') $b->where('v.parent_notified', 0);
        if (!empty($filters['severity_level']) && $needJoinCat) $b->where('vc.severity_level', $filters['severity_level']);

        if (!empty($filters['search']) && $needJoinStudent) {
            $q = trim((string) $filters['search']);
            $b->groupStart()
                ->like('u.full_name', $q)
                ->orLike('s.nisn', $q)
                ->orLike('s.nis', $q)
                ->orLike('v.description', $q)
                ->orLike('v.location', $q)
                ->groupEnd();
        }

        $b->select("
            COUNT(v.id) AS total_violations,
            SUM(CASE WHEN v.status = 'Dalam Proses' THEN 1 ELSE 0 END) AS in_process,
            SUM(CASE WHEN v.status = 'Selesai' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN v.parent_notified = 0 THEN 1 ELSE 0 END) AS parents_not_notified,
            SUM(CASE WHEN v.is_repeat_offender = 1 THEN 1 ELSE 0 END) AS repeat_offenders
        ", false);

        $row = $b->get()->getRowArray();

        return [
            'total_violations'     => (int) ($row['total_violations'] ?? 0),
            'in_process'           => (int) ($row['in_process'] ?? 0),
            'completed'            => (int) ($row['completed'] ?? 0),
            'parents_not_notified' => (int) ($row['parents_not_notified'] ?? 0),
            'repeat_offenders'     => (int) ($row['repeat_offenders'] ?? 0),
        ];
    }
}
