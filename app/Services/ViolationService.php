<?php

/**
 * File Path: app/Services/ViolationService.php
 *
 * Violation Service
 * Business logic layer untuk Case & Violation Management
 *
 * @package    SIB-K
 * @subpackage Services
 * @category   Business Logic
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Services;

use App\Models\ViolationModel;
use App\Models\ViolationCategoryModel;
use App\Models\SanctionModel;
use App\Models\StudentModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

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

    /**
     * Create new violation with validation and auto-checks
     *
     * @param array $data
     * @return array [success => bool, message => string, violation_id => int|null]
     */
    public function createViolation($data)
    {
        try {
            // Validate category exists and active
            $category = $this->categoryModel->find($data['category_id'] ?? null);
            if (!$category || !($category['is_active'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Kategori pelanggaran tidak valid atau tidak aktif',
                ];
            }

            // Validate student exists
            $student = $this->studentModel->find($data['student_id'] ?? null);
            if (!$student) {
                return [
                    'success' => false,
                    'message' => 'Siswa tidak ditemukan',
                ];
            }

            // Set default values
            if (!isset($data['status']) || $data['status'] === '') {
                $data['status'] = 'Dilaporkan';
            }

            if (!isset($data['reported_by']) || !$data['reported_by']) {
                if (function_exists('auth_id')) {
                    $data['reported_by'] = auth_id();
                }
            }

            // Create violation
            $violationId = $this->violationModel->insert($data);

            if (!$violationId) {
                return [
                    'success' => false,
                    'message' => 'Gagal menyimpan data pelanggaran',
                    'errors'  => $this->violationModel->errors(),
                ];
            }

            // Sinkronkan total poin pelanggaran siswa di tabel students
            if (isset($data['student_id'])) {
                $this->syncStudentViolationPoints((int) $data['student_id']);
            }

            // Log activity
            $nisn = $student['nisn'] ?? ($student['nis'] ?? 'unknown');
            $this->logActivity(
                'create_violation',
                $violationId,
                "Pelanggaran baru dilaporkan untuk siswa: {$nisn}"
            );

            return [
                'success'      => true,
                'message'      => 'Data pelanggaran berhasil disimpan',
                'violation_id' => $violationId,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ViolationService::createViolation - ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update violation with workflow validation
     *
     * @param int   $id
     * @param array $data
     * @return array [success => bool, message => string]
     */
    public function updateViolation($id, $data)
    {
        try {
            $violation = $this->violationModel->asArray()->find($id);
            /** @var array|null $violation */

            if (!$violation) {
                return [
                    'success' => false,
                    'message' => 'Data pelanggaran tidak ditemukan',
                ];
            }

            $oldStudentId = (int) ($violation['student_id'] ?? 0);

            // Validate status transition
            /** @var array{valid:bool,message:string} $statusValidation */
            $statusValidation = $this->validateStatusTransition(
                $violation['status'],
                $data['status'] ?? $violation['status']
            );

            if (!$statusValidation['valid']) {
                return [
                    'success' => false,
                    'message' => $statusValidation['message'],
                ];
            }

            // If status changed to Selesai, set resolution_date
            if (isset($data['status']) && $data['status'] === 'Selesai' && empty($data['resolution_date'])) {
                $data['resolution_date'] = date('Y-m-d');
            }

            // Update violation
            $updated = $this->violationModel->update($id, $data);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Gagal memperbarui data pelanggaran',
                    'errors'  => $this->violationModel->errors(),
                ];
            }

            // Sinkronkan poin siswa (lama & baru bila pindah siswa)
            if ($oldStudentId > 0) {
                $this->syncStudentViolationPoints($oldStudentId);
            }

            if (isset($data['student_id'])) {
                $newStudentId = (int) $data['student_id'];
                if ($newStudentId > 0 && $newStudentId !== $oldStudentId) {
                    $this->syncStudentViolationPoints($newStudentId);
                }
            }

            // Log activity
            $this->logActivity('update_violation', $id, 'Data pelanggaran diperbarui');

            return [
                'success' => true,
                'message' => 'Data pelanggaran berhasil diperbarui',
            ];
        } catch (\Exception $e) {
            log_message('error', 'ViolationService::updateViolation - ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate status transition rules
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return array [valid => bool, message => string]
     */
    private function validateStatusTransition($currentStatus, $newStatus)
    {
        // Define allowed transitions
        $allowedTransitions = [
            'Dilaporkan'   => ['Dalam Proses', 'Dibatalkan'],
            'Dalam Proses' => ['Selesai', 'Dibatalkan'],
            'Selesai'      => [], // Cannot change from Selesai
            'Dibatalkan'   => [], // Cannot change from Dibatalkan
        ];

        // Same status is always allowed
        if ($currentStatus === $newStatus) {
            return ['valid' => true, 'message' => 'Status tidak berubah'];
        }

        // Check if transition is allowed
        if (
            !isset($allowedTransitions[$currentStatus]) ||
            !in_array($newStatus, $allowedTransitions[$currentStatus], true)
        ) {
            return [
                'valid'   => false,
                'message' => "Tidak dapat mengubah status dari '{$currentStatus}' menjadi '{$newStatus}'",
            ];
        }

        return ['valid' => true, 'message' => 'Transisi status valid'];
    }

    /**
     * Get violations with filters and pagination
     *
     * @param array $filters
     * @return array
     */
    public function getViolations($filters = [])
    {
        return $this->violationModel->getViolationsWithFilters($filters);
    }

    /**
     * Get violation detail with all related data
     *
     * @param int $id
     * @return array|null
     */
    public function getViolationDetail($id)
    {
        $violation = $this->violationModel->getViolationWithDetails($id);

        if (!$violation) {
            return null;
        }

        // Get sanctions for this violation
        $violation['sanctions'] = $this->sanctionModel->getByViolation($id);

        // Parse evidence JSON if exists
        if (!empty($violation['evidence'])) {
            $decoded = json_decode($violation['evidence'], true);
            $violation['evidence_files'] = is_array($decoded) ? $decoded : [];
        }

        return $violation;
    }

    /**
     * Get dashboard statistics for violations
     *
     * @param array $filters
     * @return array
     */
    public function getDashboardStats($filters = [])
    {
        // Get overall statistics
        $stats = $this->violationModel->getStatistics($filters);

        // Get statistics by severity
        $statsBySeverity = $this->violationModel->getStatsBySeverity($filters);

        // Get top violators
        $topViolators = $this->violationModel->getTopViolators(5, $filters);

        // Get pending notifications
        $pendingNotifications = $this->violationModel->getPendingNotifications(10);

        return [
            'overall'               => $stats,
            'by_severity'           => $statsBySeverity,
            'top_violators'         => $topViolators,
            'pending_notifications' => count($pendingNotifications),
        ];
    }

    /**
     * Get student violation history with statistics
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentViolationHistory($studentId)
    {
        // Get all violations
        $violations = $this->violationModel->getByStudent($studentId);

        // Calculate statistics
        $stats = [
            'total_violations'   => count($violations),
            'total_points'       => $this->violationModel->getStudentTotalPoints($studentId),
            'by_severity'        => [
                'Ringan' => 0,
                'Sedang' => 0,
                'Berat'  => 0,
            ],
            'by_status'          => [
                'Dilaporkan'   => 0,
                'Dalam Proses' => 0,
                'Selesai'      => 0,
                'Dibatalkan'   => 0,
            ],
            'is_repeat_offender' => false,
            'last_violation_date'=> null,
        ];

        foreach ($violations as $violation) {
            // Count by severity
            if (isset($stats['by_severity'][$violation['severity_level']])) {
                $stats['by_severity'][$violation['severity_level']]++;
            }

            // Count by status
            if (isset($stats['by_status'][$violation['status']])) {
                $stats['by_status'][$violation['status']]++;
            }

            // Check repeat offender
            if (!empty($violation['is_repeat_offender'])) {
                $stats['is_repeat_offender'] = true;
            }

            // Get last violation date
            if (
                empty($stats['last_violation_date']) ||
                $violation['violation_date'] > $stats['last_violation_date']
            ) {
                $stats['last_violation_date'] = $violation['violation_date'];
            }
        }

        return [
            'violations' => $violations,
            'statistics' => $stats,
        ];
    }

    /**
     * Process parent notification
     *
     * @param int $violationId
     * @return array [success => bool, message => string]
     */
    public function notifyParent($violationId)
    {
        try {
            $violation = $this->violationModel->getViolationWithDetails($violationId);

            if (!$violation) {
                return [
                    'success' => false,
                    'message' => 'Data pelanggaran tidak ditemukan',
                ];
            }

            if (!empty($violation['parent_notified'])) {
                return [
                    'success' => false,
                    'message' => 'Orang tua sudah dinotifikasi sebelumnya',
                ];
            }

            // TODO: Implement actual notification mechanism
            // For now, just mark as notified (fallback jika method tidak ada)
            if (method_exists($this->violationModel, 'markParentNotified')) {
                $this->violationModel->markParentNotified($violationId);
            } else {
                $this->violationModel->update($violationId, [
                    'parent_notified'    => 1,
                    'parent_notified_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Log activity
            $this->logActivity('notify_parent', $violationId, 'Notifikasi dikirim ke orang tua');

            return [
                'success' => true,
                'message' => 'Notifikasi berhasil dikirim ke orang tua',
            ];
        } catch (\Exception $e) {
            log_message('error', 'ViolationService::notifyParent - ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate violation report
     *
     * @param array $filters
     * @return array
     */
    public function generateReport($filters = [])
    {
        // Get violations
        $violations = $this->violationModel->getViolationsWithFilters($filters);

        // Get statistics
        $statistics      = $this->violationModel->getStatistics($filters);
        $statsBySeverity = $this->violationModel->getStatsBySeverity($filters);

        // Get top violators
        $topViolators = $this->violationModel->getTopViolators(10, $filters);

        // Get most common categories
        $db = \Config\Database::connect();
        $commonCategories = $db->table('violations')
            ->select(
                'violation_categories.category_name,
                 violation_categories.severity_level,
                 COUNT(violations.id) as violation_count'
            )
            ->join('violation_categories', 'violation_categories.id = violations.category_id')
            ->where('violations.deleted_at', null);

        if (!empty($filters['date_from'])) {
            $commonCategories->where('violations.violation_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $commonCategories->where('violations.violation_date <=', $filters['date_to']);
        }

        $commonCategories = $commonCategories
            ->groupBy('violations.category_id')
            ->orderBy('violation_count', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return [
            'violations' => $violations,
            'statistics' => [
                'overall'          => $statistics,
                'by_severity'      => $statsBySeverity,
                'common_categories'=> $commonCategories,
            ],
            'top_violators' => $topViolators,
            'filters_applied' => $filters,
            'generated_at'     => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Check if student can be promoted/graduated based on violations
     *
     * @param int $studentId
     * @return array [eligible => bool, message => string, total_points => int]
     */
    public function checkPromotionEligibility($studentId)
    {
        // Get current academic year total points
        $currentAcademicYear = date('Y') . '/' . (date('Y') + 1);

        $totalPoints = $this->violationModel->getStudentTotalPoints($studentId, [
            'date_from' => date('Y') . '-07-01', // Start of academic year (July)
            'date_to'   => date('Y-m-d'),
        ]);

        // Define threshold (example: 100 points)
        $threshold = 100;

        if ($totalPoints >= $threshold) {
            return [
                'eligible'     => false,
                'message'      => "Siswa tidak memenuhi syarat kenaikan kelas karena memiliki {$totalPoints} poin pelanggaran (threshold: {$threshold} poin)",
                'total_points' => $totalPoints,
                'threshold'    => $threshold,
                'year'         => $currentAcademicYear,
            ];
        }

        return [
            'eligible'     => true,
            'message'      => "Siswa memenuhi syarat kenaikan kelas dengan {$totalPoints} poin pelanggaran",
            'total_points' => $totalPoints,
            'threshold'    => $threshold,
            'year'         => $currentAcademicYear,
        ];
    }

    /**
     * Get active categories for selection
     *
     * @return array
     */
    public function getActiveCategories()
    {
        return $this->categoryModel->getActiveCategories();
    }

    /**
     * Get categories grouped by severity
     *
     * @return array
     */
    public function getCategoriesGrouped()
    {
        $categories = $this->categoryModel->getActiveCategories();

        $grouped = [
            'Ringan' => [],
            'Sedang' => [],
            'Berat'  => [],
        ];

        foreach ($categories as $category) {
            $severity = $category['severity_level'] ?? 'Ringan';
            if (!isset($grouped[$severity])) {
                $grouped[$severity] = [];
            }
            $grouped[$severity][] = $category;
        }

        return $grouped;
    }

    /**
     * Bulk update violations status
     *
     * @param array  $violationIds
     * @param string $status
     * @return array [success => bool, message => string, updated_count => int]
     */
    public function bulkUpdateStatus($violationIds, $status)
    {
        try {
            $updatedCount = 0;

            foreach ($violationIds as $id) {
                $result = $this->updateViolation($id, ['status' => $status]);
                if ($result['success']) {
                    $updatedCount++;
                }
            }

            return [
                'success'       => true,
                'message'       => "{$updatedCount} pelanggaran berhasil diperbarui",
                'updated_count' => $updatedCount,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ViolationService::bulkUpdateStatus - ' . $e->getMessage());

            return [
                'success'       => false,
                'message'       => 'Terjadi kesalahan: ' . $e->getMessage(),
                'updated_count' => 0,
            ];
        }
    }

    /**
     * Delete violation with validation
     *
     * @param int $id
     * @return array [success => bool, message => string]
     */
    public function deleteViolation($id)
    {
        try {
            $violation = $this->violationModel->find($id);

            if (!$violation) {
                return [
                    'success' => false,
                    'message' => 'Data pelanggaran tidak ditemukan',
                ];
            }

            $studentId = (int) ($violation['student_id'] ?? 0);

            // Check if has sanctions
            $sanctions = $this->sanctionModel->getByViolation($id);
            if (count($sanctions) > 0) {
                return [
                    'success' => false,
                    'message' => 'Pelanggaran tidak dapat dihapus karena sudah memiliki sanksi. Silakan hapus sanksi terlebih dahulu.',
                ];
            }

            // Delete violation
            $this->violationModel->delete($id);

            // Sinkronkan poin siswa setelah pelanggaran dihapus
            if ($studentId > 0) {
                $this->syncStudentViolationPoints($studentId);
            }

            // Log activity
            $this->logActivity('delete_violation', $id, 'Pelanggaran dihapus');

            return [
                'success' => true,
                'message' => 'Data pelanggaran berhasil dihapus',
            ];
        } catch (\Exception $e) {
            log_message('error', 'ViolationService::deleteViolation - ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sinkronkan kolom students.total_violation_points dari log pelanggaran.
     *
     * @param int $studentId
     * @return void
     */
    private function syncStudentViolationPoints($studentId)
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            return;
        }

        try {
            // Hitung ulang total poin dari tabel violations
            $total = (int) $this->violationModel->getStudentTotalPoints($studentId);

            if ($total < 0) {
                $total = 0;
            }

            // Update ke tabel students
            $this->studentModel->update($studentId, [
                'total_violation_points' => $total,
            ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'ViolationService::syncStudentViolationPoints gagal untuk student_id '
                . $studentId . ' - ' . $e->getMessage()
            );
        }
    }

    /**
     * Sinkronkan total_violation_points untuk semua siswa aktif.
     *
     * @return array{success:bool,message:string}
     */
    public function syncAllStudentsViolationPoints(): array
    {
        try {
            // Ambil semua siswa aktif (selaras dengan getActiveStudents/StudentController)
            $students = $this->studentModel
                ->select('id')
                ->where('status', 'Aktif')
                ->findAll();

            $totalStudents = 0;

            foreach ($students as $row) {
                $studentId = (int) ($row['id'] ?? 0);
                if ($studentId <= 0) {
                    continue;
                }

                $this->syncStudentViolationPoints($studentId);
                $totalStudents++;
            }

            return [
                'success' => true,
                'message' => 'Sinkronisasi poin pelanggaran berhasil untuk ' .
                    $totalStudents . ' siswa aktif.',
            ];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::syncAllStudentsViolationPoints - ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal menyinkronkan poin pelanggaran: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Log activity for audit trail
     *
     * @param string $action
     * @param int    $violationId
     * @param string $description
     * @return void
     */
    private function logActivity($action, $violationId, $description)
    {
        $userId = function_exists('auth_id') ? auth_id() : 'system';
        log_message(
            'info',
            "[ViolationService] Action: {$action}, Violation ID: {$violationId}, Description: {$description}, User: {$userId}"
        );
    }

    // ==========================================================
    // Tambahan method untuk kebutuhan Koordinator/Counselor views
    // (tanpa menghapus method yang sudah ada)
    // ==========================================================

    /**
     * Alias untuk Koordinator: ambil semua violations dengan filter + pagination.
     * Output mengikuti ViolationModel::getViolationsWithFilters (umumnya berisi 'violations' dan 'pager').
     */
    public function getAllViolations(array $filters = [])
    {
        return $this->getViolations($filters);
    }

    /**
     * Alias nama yang sering dipakai controller: detail violation + sanctions + evidence_files
     */
    public function getViolationWithSanctions(int $id): ?array
    {
        return $this->getViolationDetail($id);
    }

    /**
     * Ambil 1 violation (raw) untuk edit, sesuai kebutuhan controller Koordinator/Counselor.
     */
    public function getViolationById(int $id): ?array
    {
        return $this->violationModel->asArray()->find($id);
    }

    /**
     * Ambil evidence sebagai array (decode JSON) untuk kebutuhan update (remove_evidence).
     */
    public function getViolationEvidence(int $id): array
    {
        $row = $this->violationModel->asArray()->select('evidence')->find($id);
        if (!$row) return [];

        $raw = $row['evidence'] ?? null;
        if (!$raw) return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Dropdown filter: list siswa (umumnya semua yang tidak soft deleted).
     */
    public function getStudentsForFilter(): array
    {
        return $this->studentModel
            ->select('id, full_name, nisn, nis, class_id, status')
            ->where('deleted_at', null)
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    /**
     * Dropdown create/edit violation: siswa aktif saja (lebih aman).
     */
    public function getStudentsForViolation(): array
    {
        return $this->studentModel
            ->select('id, full_name, nisn, nis, class_id')
            ->where('deleted_at', null)
            ->where('status', 'Aktif')
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    /**
     * Dropdown kategori untuk filter/selection.
     */
    public function getViolationCategories(): array
    {
        // gunakan method yang sudah ada di model jika tersedia
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

    /**
     * List Guru BK untuk assign handler.
     * Berdasarkan schema: users.role_id = 3 adalah "Guru BK".
     */
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

    /**
     * Assign Guru BK sebagai penangan (handled_by).
     */
    public function assignCounselor(int $violationId, int $counselorId): array
    {
        try {
            $violation = $this->violationModel->asArray()->find($violationId);
            if (!$violation) {
                return ['success' => false, 'message' => 'Data pelanggaran tidak ditemukan'];
            }

            // validasi counselor benar-benar user Guru BK aktif
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

            $payload = [
                'handled_by' => $counselorId,
            ];

            // opsional: kalau masih Dilaporkan, otomatis masuk Dalam Proses
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

            $this->logActivity('assign_counselor', $violationId, 'Assign Guru BK (handled_by)');

            return ['success' => true, 'message' => 'Guru BK berhasil ditugaskan'];
        } catch (\Throwable $e) {
            log_message('error', 'ViolationService::assignCounselor - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }

    /**
     * Statistik ringkas untuk halaman index (dipakai view kartu ringkas).
     * Return format dibuat cocok dengan helper statv() yang membaca $stats['overall'][key].
     */
    public function getViolationStats(array $filters = []): array
    {
        // coba pakai statistik dari model dulu
        $overall = [];
        try {
            if (method_exists($this->violationModel, 'getStatistics')) {
                $overall = (array) $this->violationModel->getStatistics($filters);
            }
        } catch (\Throwable $e) {
            $overall = [];
        }

        // fallback/hardening: hitung key yang dibutuhkan jika tidak ada
        $needKeys = ['total_violations', 'in_process', 'completed', 'parents_not_notified', 'repeat_offenders'];
        $missing = array_filter($needKeys, fn($k) => !array_key_exists($k, $overall));

        if (!empty($missing)) {
            $calc = $this->calculateOverallStatsFallback($filters);
            foreach ($missing as $k) {
                $overall[$k] = $calc[$k] ?? 0;
            }
        }

        // by_severity jika ada
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

    /**
     * Fallback hitung statistik via query builder (agar view index stabil).
     */
    private function calculateOverallStatsFallback(array $filters = []): array
    {
        $b = $this->db->table('violations v');

        // join seperlunya untuk filter severity/search
        $needJoinCat = !empty($filters['severity_level']);
        if ($needJoinCat) {
            $b->join('violation_categories vc', 'vc.id = v.category_id', 'left');
        }

        $needJoinStudent = !empty($filters['search']);
        if ($needJoinStudent) {
            $b->join('students s', 's.id = v.student_id', 'left');
        }

        $b->where('v.deleted_at', null);

        // filter basic
        if (!empty($filters['status'])) {
            $b->where('v.status', $filters['status']);
        }
        if (!empty($filters['student_id'])) {
            $b->where('v.student_id', (int) $filters['student_id']);
        }
        if (!empty($filters['category_id'])) {
            $b->where('v.category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['date_from'])) {
            $b->where('v.violation_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $b->where('v.violation_date <=', $filters['date_to']);
        }
        if (!empty($filters['is_repeat_offender'])) {
            $b->where('v.is_repeat_offender', 1);
        }
        if (!empty($filters['parent_notified']) && $filters['parent_notified'] === 'no') {
            $b->where('v.parent_notified', 0);
        }
        if (!empty($filters['severity_level'])) {
            $b->where('vc.severity_level', $filters['severity_level']);
        }

        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            $b->groupStart()
                ->like('s.full_name', $q)
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
            'total_violations'    => (int) ($row['total_violations'] ?? 0),
            'in_process'          => (int) ($row['in_process'] ?? 0),
            'completed'           => (int) ($row['completed'] ?? 0),
            'parents_not_notified'=> (int) ($row['parents_not_notified'] ?? 0),
            'repeat_offenders'    => (int) ($row['repeat_offenders'] ?? 0),
        ];
    }
}
