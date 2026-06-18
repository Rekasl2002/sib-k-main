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
                s.nisn,
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

        return view('parent/reports/children', [
            'title'    => 'Laporan Anak',
            'children' => $children,
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

        // Perbaikan Kedua — Item #11: laporan Orang Tua HANYA data lengkap anak
        // (biodata + pilihan Karier/Studi Lanjut anak). TANPA jadwal/aktivitas/asesmen.
        $careers      = $this->getChildSavedCareers($studentId);
        $universities = $this->getChildSavedUniversities($studentId);

        $docTitle = 'Laporan Anak - ' . ($student['full_name'] ?? 'Tanpa Nama');
        if (!empty($parentName) && $parentName !== 'Orang Tua') {
            $docTitle .= ' - ' . $parentName;
        }

        return [
            'title'            => $docTitle,
            'pageTitle'        => $isPdf ? 'Laporan Anak' : 'Lihat/Cetak Laporan Anak',
            'student'          => $student,
            'careers'          => $careers,
            'universities'     => $universities,
            'today'            => Time::today('Asia/Jakarta')->toDateString(),
            'parentName'       => $parentName,
            'isPdf'            => $isPdf,
        ];
    }

    /**
     * Pilihan karier yang DISIMPAN oleh anak (data milik anak, bukan jadwal/kegiatan BK).
     */
    protected function getChildSavedCareers(int $studentId): array
    {
        if (!$this->findChildForCurrentParent($studentId)) {
            return [];
        }

        return $this->db->table('student_saved_careers ssc')
            ->select('co.title, co.sector, co.min_education, ssc.created_at AS saved_at')
            ->join('career_options co', 'co.id = ssc.career_id', 'left')
            ->where('ssc.student_id', $studentId)
            ->where('ssc.deleted_at', null)
            ->where('co.deleted_at', null)
            ->orderBy('ssc.created_at', 'DESC')
            ->get()->getResultArray() ?: [];
    }

    /**
     * Pilihan perguruan tinggi / studi lanjut yang DISIMPAN oleh anak.
     */
    protected function getChildSavedUniversities(int $studentId): array
    {
        if (!$this->findChildForCurrentParent($studentId)) {
            return [];
        }

        return $this->db->table('student_saved_universities ssu')
            ->select('ui.university_name, ui.alias, ui.accreditation, ui.location, ssu.created_at AS saved_at')
            ->join('university_info ui', 'ui.id = ssu.university_id', 'left')
            ->where('ssu.student_id', $studentId)
            ->where('ssu.deleted_at', null)
            ->where('ui.deleted_at', null)
            ->orderBy('ssu.created_at', 'DESC')
            ->get()->getResultArray() ?: [];
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
}
