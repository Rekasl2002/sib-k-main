<?php

/**
 * File Path: app/Controllers/Parents/ReportController.php
 *
 * Parent • Reports
 * - /parent/reports/children               : daftar anak + ringkasan singkat
 * - /parent/reports/child/{id}             : laporan individual anak (HTML, print-friendly)
 * - /parent/reports/child/{id}?format=pdf  : download PDF server-side (tanpa footer browser/layout)
 */

namespace App\Controllers\Parents;

use App\Libraries\PDFGenerator;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;

class ReportController extends BaseParentController
{
    public function childrenReport()
    {
        $this->requireParent();

        $parentId = (int) session('user_id');
        if ($parentId <= 0) {
            throw PageNotFoundException::forPageNotFound('Akun tidak valid.');
        }

        $children = $this->db->table('students s')
            ->select('
                s.id,
                COALESCE(u.full_name, "-") AS full_name,
                s.nis,
                s.gender,
                s.class_id,
                c.class_name,
                c.grade_level
            ')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        $violationStats = [];
        foreach ($children as $child) {
            $violationStats[$child['id']] = $this->getViolationQuickStats((int) $child['id']);
        }

        return view('parent/reports/children', [
            'title'          => 'Laporan Anak',
            'children'       => $children,
            'violationStats' => $violationStats,
        ]);
    }

    public function childReport(int $studentId)
    {
        $this->requireParent();

        $format = strtolower((string) $this->request->getGet('format'));
        if ($format === 'pdf') {
            return $this->childReportPdf($studentId);
        }

        $data = $this->buildChildReportData($studentId, false);
        return view('parent/reports/child_report', $data);
    }

    /**
     * GET /parent/reports/child/{id}?format=pdf
     * Download PDF server-side.
     */
    public function childReportPdf(int $studentId)
    {
        $this->requireParent();

        $data = $this->buildChildReportData($studentId, true);

        // Opsional: timestamp untuk header PDF view
        $data['generatedAt'] = Time::now('Asia/Jakarta')->toDateTimeString();

        $safeName = $this->makeSafePdfFilename((string) ($data['title'] ?? 'laporan-anak'));
        $filename = $safeName . '.pdf';

        $pdfView = $this->resolveChildReportPdfView();

        $pdf = new PDFGenerator();

        // Matikan footer PDFGenerator (kalau kamu tidak mau nomor halaman).
        $pdf->setFooterText(null);

        $binary = $pdf->generate($pdfView, $data, $filename, false);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            // ✅ penting: attachment = download, bukan preview
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setBody($binary);
    }

    // --------------------------------------------------------------------
    // Helpers (Data builder + util)
    // --------------------------------------------------------------------

    protected function buildChildReportData(int $studentId, bool $isPdf): array
    {
        $student = $this->findChildForCurrentParent($studentId);
        if (!$student) {
            throw PageNotFoundException::forPageNotFound(
                'Data anak tidak ditemukan atau tidak terhubung dengan akun ini.'
            );
        }

        $parentName = 'Orang Tua';
        if (function_exists('auth_user')) {
            $au = auth_user();
            if (!empty($au['full_name'])) {
                $parentName = $au['full_name'];
            }
        } elseif (session()->has('full_name')) {
            $parentName = (string) session('full_name');
        }

        $violationSummary = $this->getViolationSummaryForReport($studentId);
        $violations       = $this->getViolationDetailsForReport($studentId);
        $sessions         = $this->getSessionSummaryForReport($studentId);

        $docTitle = 'Laporan Anak - ' . ($student['full_name'] ?? 'Tanpa Nama');
        if (!empty($parentName) && $parentName !== 'Orang Tua') {
            $docTitle .= ' - ' . $parentName;
        }

        return [
            'title'            => $docTitle,
            'pageTitle'        => $isPdf ? 'Laporan Anak' : 'Lihat/Cetak Laporan Anak',
            'student'          => $student,
            'violationSummary' => $violationSummary,
            'violations'       => $violations,
            'sessions'         => $sessions,
            'today'            => Time::today('Asia/Jakarta')->toDateString(),
            'parentName'       => $parentName,
            'isPdf'            => $isPdf,
        ];
    }

    protected function resolveChildReportPdfView(): string
    {
        $pdfViewPath = APPPATH . 'Views/parent/reports/child_report_pdf.php';
        if (is_file($pdfViewPath)) {
            return 'parent/reports/child_report_pdf';
        }
        return 'parent/reports/child_report';
    }

    protected function makeSafePdfFilename(string $title): string
    {
        $name = trim($title);
        $name = preg_replace('/[^\pL\pN\-\_\s]+/u', '', $name) ?? 'laporan-anak';
        $name = preg_replace('/\s+/u', ' ', $name) ?? 'laporan-anak';
        $name = str_replace(' ', '-', $name);
        $name = trim($name, '-');

        if ($name === '') {
            $name = 'laporan-anak';
        }

        return mb_substr($name, 0, 80);
    }

    protected function findChildForCurrentParent(int $studentId): ?array
    {
        $parentId = (int) session('user_id');
        if ($parentId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = $this->db->table('students s')
            ->select('
                s.*,
                COALESCE(u.full_name, "-") AS full_name,
                c.class_name,
                c.grade_level,
                c.major
            ')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', $studentId)
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    protected function getViolationQuickStats(int $studentId): array
    {
        $row = $this->db->table('violations v')
            ->select('COUNT(v.id) AS total_violations, COALESCE(SUM(vc.point_deduction), 0) AS total_points')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.student_id', $studentId)
            ->where('v.deleted_at', null)
            ->get()
            ->getRowArray();

        return [
            'total_violations' => (int) ($row['total_violations'] ?? 0),
            'total_points'     => (int) ($row['total_points'] ?? 0),
        ];
    }

    protected function getViolationSummaryForReport(int $studentId): array
    {
        $stats = $this->getViolationQuickStats($studentId);

        $last = $this->db->table('violations v')
            ->select('v.violation_date')
            ->where('v.student_id', $studentId)
            ->where('v.deleted_at', null)
            ->orderBy('v.violation_date', 'DESC')
            ->orderBy('v.id', 'DESC')
            ->get()
            ->getRowArray();

        return [
            'total_violations'    => $stats['total_violations'],
            'total_points'        => $stats['total_points'],
            'last_violation_date' => $last['violation_date'] ?? null,
        ];
    }

    protected function getViolationDetailsForReport(int $studentId): array
    {
        $rows = $this->db->table('violations v')
            ->select(
                'v.id,
                 v.violation_date,
                 v.violation_time,
                 v.location,
                 v.description,
                 vc.category_name   AS category_name,
                 vc.severity_level  AS category_severity,
                 vc.point_deduction,
                 u.full_name        AS recorder_name'
            )
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->join('users u', 'u.id = v.reported_by', 'left')
            ->where('v.student_id', $studentId)
            ->where('v.deleted_at', null)
            ->orderBy('v.violation_date', 'DESC')
            ->orderBy('v.id', 'DESC')
            ->get()
            ->getResultArray();

        return $rows ?: [];
    }

    protected function getSessionSummaryForReport(int $studentId): array
    {
        $student = $this->findChildForCurrentParent($studentId);
        if (!$student) {
            return [];
        }
        $classId = (int) ($student['class_id'] ?? 0);

        $fieldNames = [];
        try {
            $fields = $this->db->getFieldData('counseling_sessions');
            $fieldNames = array_map(static fn($f) => $f->name, $fields);
        } catch (\Throwable $e) {
            $fieldNames = [];
        }

        $hasConfidential = in_array('is_confidential', $fieldNames, true);
        $hasTopic        = in_array('topic', $fieldNames, true);
        $hasLocation     = in_array('location', $fieldNames, true);

        $b = $this->db->table('counseling_sessions cs');

        $b->select([
            'cs.id',
            'cs.session_date',
            'cs.session_time',
            'cs.session_type',
            'cs.status',
            'u.full_name AS counselor_name',
        ]);

        if ($hasTopic && $hasConfidential) {
            $b->select(new RawSql(
                "CASE WHEN cs.is_confidential = 1 THEN 'Sesi Konseling (Terbatas)' ELSE cs.topic END AS topic"
            ));
        } elseif ($hasTopic) {
            $b->select('cs.topic AS topic');
        } else {
            $b->select(new RawSql("'-' AS topic"));
        }

        if ($hasLocation && $hasConfidential) {
            $b->select(new RawSql(
                "CASE WHEN cs.is_confidential = 1 THEN NULL ELSE cs.location END AS location"
            ));
        } elseif ($hasLocation) {
            $b->select('cs.location AS location');
        } else {
            $b->select(new RawSql("NULL AS location"));
        }

        $b->join('users u', 'u.id = cs.counselor_id', 'left');

        if ($this->db->tableExists('session_participants')) {
            $b->join('session_participants sp', 'sp.session_id = cs.id AND sp.deleted_at IS NULL', 'left');
        }

        $b->where('cs.deleted_at', null);

        $b->groupStart()
            ->where('cs.student_id', $studentId);

        if ($this->db->tableExists('session_participants')) {
            $b->orWhere('sp.student_id', $studentId);
        }

        if ($classId > 0) {
            $b->orGroupStart()
                ->where('cs.session_type', 'Klasikal')
                ->where('cs.class_id', $classId)
            ->groupEnd();
        }

        $b->groupEnd();
        $b->distinct();

        $b->orderBy('cs.session_date', 'DESC')
          ->orderBy('cs.session_time', 'DESC');

        return $b->get()->getResultArray() ?: [];
    }
}
