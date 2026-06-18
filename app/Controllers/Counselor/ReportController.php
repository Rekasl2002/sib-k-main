<?php

/**
 * File Path: app/Controllers/Counselor/ReportController.php
 *
 * Guru BK • Laporan (Perbaikan Kedua — Item #11)
 * - Jenis Laporan = banyak fitur (checkbox), output SATU dokumen bersections.
 * - Mode: ringkasan semua siswa binaan / satu siswa.
 * - Scope dibatasi ke siswa & kelas binaan Guru BK (classes.counselor_id = uid).
 * - Unduh PDF (opsi kertas/arah) & Excel (1 sheet per fitur).
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Services\ReportService;
use App\Libraries\PDFGenerator;
use CodeIgniter\HTTP\RedirectResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportController extends BaseController
{
    protected ReportService $report;
    protected ?PDFGenerator $pdf = null;
    protected string $role = 'counselor';

    public function __construct()
    {
        $this->report = new ReportService();
        if (function_exists('helper')) {
            try {
                helper(['url', 'form', 'text', 'number', 'date', 'permission', 'settings', 'auth']);
            } catch (\Throwable $e) {
            }
        }
    }

    public function index()
    {
        if ($redir = $this->ensurePerm('view_reports_individual', '/counselor/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        $db  = db_connect();
        $uid = $this->currentUserId();

        $classes = $db->table('classes')
            ->select('id, class_name')
            ->where('deleted_at', null)->where('is_active', 1)
            ->where('counselor_id', $uid)
            ->orderBy('class_name', 'ASC')->get()->getResultArray();

        $students = $this->report->studentOptionsForCounselor($uid);

        return view('counselor/reports/index', [
            'pageTitle'   => 'Laporan',
            'features'    => ReportService::featureCatalog($this->role),
            'classes'     => $classes,
            'students'    => $students,
            'valFrom'     => (string) ($this->request->getGet('date_from') ?: date('Y-m-01')),
            'valTo'       => (string) ($this->request->getGet('date_to') ?: date('Y-m-d')),
            'valPaper'    => $this->normalizePaper((string) ($this->request->getGet('paper') ?: 'A4')),
            'valOrient'   => $this->normalizeOrientation((string) ($this->request->getGet('orientation') ?: 'portrait')),
        ]);
    }

    public function preview()
    {
        if ($redir = $this->ensurePerm('view_reports_individual', '/counselor/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        try {
            [$sections, $f] = $this->buildSections();
            return view('counselor/reports/partials/sections_preview', [
                'sections'    => $sections,
                'periodLabel' => $this->periodLabel($f),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[COUNSELOR REPORT PREVIEW] ' . $e->getMessage());
            return $this->response->setStatusCode(200)->setBody(
                '<div class="alert alert-danger mb-0"><b>Gagal memuat pratinjau.</b><br><small class="text-muted">' . esc($e->getMessage()) . '</small></div>'
            );
        }
    }

    public function download()
    {
        if ($redir = $this->ensurePerm('generate_reports_individual', '/counselor/reports', 'Anda tidak punya izin untuk mengunduh laporan.')) {
            return $redir;
        }

        $format      = strtolower((string) ($this->request->getGet('format') ?: 'pdf'));
        $paper       = $this->normalizePaper((string) ($this->request->getGet('paper') ?: 'A4'));
        $orientation = $this->normalizeOrientation((string) ($this->request->getGet('orientation') ?: 'portrait'));
        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            $format = 'pdf';
        }

        try {
            [$sections, $f] = $this->buildSections();
        } catch (\Throwable $e) {
            log_message('error', '[COUNSELOR REPORT PAYLOAD] ' . $e->getMessage());
            return redirect()->to('/counselor/reports')->with('error', 'Gagal menyiapkan data laporan: ' . $e->getMessage());
        }

        $filename = $this->safeFilename('laporan_gurubk_' . ($f['date_from'] ?: 'all') . '_' . ($f['date_to'] ?: 'all'));

        if ($format === 'xlsx') {
            try {
                $tmpPath = $this->buildSectionsXlsx($sections, $f, $filename);
            } catch (\Throwable $e) {
                log_message('error', '[COUNSELOR REPORT XLSX] ' . $e->getMessage());
                return redirect()->to('/counselor/reports')->with('error', 'Gagal membuat Excel: ' . $e->getMessage());
            }
            register_shutdown_function(static fn () => @unlink($tmpPath));
            return $this->response->download($tmpPath, null)->setFileName($filename . '.xlsx');
        }

        if (! PDFGenerator::isAvailable()) {
            return redirect()->to('/counselor/reports')->with('error', 'Fitur unduh PDF belum tersedia karena paket Dompdf belum terpasang. Unduh Excel tetap dapat digunakan.');
        }

        $html = view('counselor/reports/partials/sections_pdf', [
            'sections'    => $sections,
            'reportTitle' => 'Laporan BK (Guru BK)',
            'schoolName'  => (string) setting('school_name', env('school.name', ''), 'general'),
            'periodLabel' => $this->periodLabel($f),
            'generatedAt' => date('Y-m-d H:i:s'),
        ]);

        try {
            $bin = $this->pdf()->render($html, $paper, $orientation);
        } catch (\Throwable $e) {
            log_message('error', '[COUNSELOR REPORT PDF] ' . $e->getMessage());
            return redirect()->to('/counselor/reports')->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
            ->setBody($bin);
    }

    // =========================================================
    // Core: bangun sections sesuai scope Guru BK
    // =========================================================

    /** @return array{0:array,1:array} [sections, filter] */
    protected function buildSections(): array
    {
        $uid = $this->currentUserId();
        $f   = $this->filters();
        $scope = $this->buildScope($uid, $f);
        $sections = $this->report->buildSections($f['features'], $f, $scope);
        return [$sections, $f];
    }

    protected function buildScope(int $uid, array $f): array
    {
        $db = db_connect();
        $classIds = array_map('intval', array_column(
            $db->table('classes')->select('id')->where('deleted_at', null)->where('counselor_id', $uid)->get()->getResultArray(),
            'id'
        ));
        $studentIds = $this->report->counselorStudentIds($uid);

        return [
            'role'                => $this->role,
            'user_id'             => $uid,
            'allowed_class_ids'   => $classIds,
            'allowed_student_ids' => $studentIds,
            'mask_confidential'   => false,
            'single'              => ($f['student_mode'] === 'single'),
            'counselor_id'        => null,
        ];
    }

    protected function filters(): array
    {
        $dateFrom = $this->normalizeDate($this->request->getGet('date_from'));
        $dateTo   = $this->normalizeDate($this->request->getGet('date_to'));
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'features'     => $this->validFeatures(),
            'student_mode' => ($this->request->getGet('student_mode') === 'single') ? 'single' : 'all',
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'class_id'     => $this->request->getGet('class_id') ? (int) $this->request->getGet('class_id') : null,
            'student_id'   => $this->request->getGet('student_id') ? (int) $this->request->getGet('student_id') : null,
        ];
    }

    protected function validFeatures(): array
    {
        $allowed = array_keys(ReportService::featureCatalog($this->role));
        $req = (array) ($this->request->getGet('features') ?? []);
        $out = array_values(array_intersect($allowed, array_map('strval', $req)));
        return $out ?: ['counseling']; // default minimal
    }

    protected function periodLabel(array $f): string
    {
        return ($f['date_from'] ?: '-') . ' s/d ' . ($f['date_to'] ?: '-');
    }

    // =========================================================
    // XLSX (1 sheet per section)
    // =========================================================

    protected function buildSectionsXlsx(array $sections, array $filters, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $used = [];
        $first = true;

        if (empty($sections)) {
            $sections = [['title' => 'Laporan', 'columns' => ['Data'], 'rows' => []]];
        }

        foreach ($sections as $idx => $sec) {
            $sheet = $first ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $first = false;
            $sheet->setTitle($this->safeSheetTitle((string) ($sec['title'] ?? 'Laporan'), $used, $idx));

            $columns = $sec['columns'] ?? ['Data'];
            $rows    = $sec['rows'] ?? [];

            $r = 1;
            $sheet->setCellValueExplicit("A{$r}", (string) ($sec['title'] ?? 'Laporan'), DataType::TYPE_STRING);
            $r += 2;

            foreach ($columns as $i => $h) {
                $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1) . $r, (string) $h, DataType::TYPE_STRING);
            }
            $sheet->getStyle("A{$r}:" . Coordinate::stringFromColumnIndex(max(1, count($columns))) . "{$r}")->getFont()->setBold(true);
            $r++;

            if (empty($rows)) {
                $sheet->setCellValueExplicit("A{$r}", '(tidak ada data)', DataType::TYPE_STRING);
            } else {
                foreach ($rows as $row) {
                    $vals = is_array($row) ? array_values($row) : [(string) $row];
                    foreach ($vals as $i => $val) {
                        $sheet->setCellValueExplicit(
                            Coordinate::stringFromColumnIndex($i + 1) . $r,
                            is_scalar($val) ? (string) $val : json_encode($val, JSON_UNESCAPED_UNICODE),
                            DataType::TYPE_STRING
                        );
                    }
                    $r++;
                }
            }

            for ($i = 1; $i <= max(1, count($columns)); $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }
        }

        $tmpPath = WRITEPATH . 'uploads/' . $filename . '.xlsx';
        @mkdir(dirname($tmpPath), 0775, true);
        (new Xlsx($spreadsheet))->save($tmpPath);
        return $tmpPath;
    }

    private function safeSheetTitle(string $title, array &$used, int $idx): string
    {
        $t = preg_replace('/[\\\\\/\?\*\[\]\:]/', ' ', $title) ?? 'Sheet';
        $t = trim(mb_substr($t, 0, 28));
        if ($t === '') {
            $t = 'Sheet ' . ($idx + 1);
        }
        $base = $t;
        $n = 1;
        while (in_array(strtolower($t), $used, true)) {
            $t = mb_substr($base, 0, 25) . ' ' . (++$n);
        }
        $used[] = strtolower($t);
        return $t;
    }

    // =========================================================
    // Helpers umum
    // =========================================================

    private function pdf(): PDFGenerator
    {
        return $this->pdf ??= new PDFGenerator();
    }

    private function safeFilename(string $name): string
    {
        $name = strtolower(preg_replace('/[^a-z0-9_\-]+/i', '_', $name) ?? 'report');
        $name = trim($name, '_');
        if (strlen($name) > 120) {
            $name = substr($name, 0, 120);
        }
        return $name ?: 'report';
    }

    private function normalizeDate($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y) ? $value : null;
    }

    private function normalizePaper(string $paper): string
    {
        $p = strtolower(trim($paper));
        return ['a4' => 'A4', 'letter' => 'letter', 'legal' => 'legal'][$p] ?? 'A4';
    }

    private function normalizeOrientation(string $orientation): string
    {
        $o = strtolower(trim($orientation));
        return in_array($o, ['portrait', 'landscape'], true) ? $o : 'portrait';
    }

    private function ensurePerm(string $perm, string $redirectTo, string $message): ?RedirectResponse
    {
        if (function_exists('has_permission') && !has_permission($perm)) {
            return redirect()->to($redirectTo)->with('error', $message);
        }
        return null;
    }

    private function currentUserId(): int
    {
        try {
            if (function_exists('current_user_id')) {
                $id = (int) call_user_func('current_user_id');
                if ($id > 0) {
                    return $id;
                }
            }
        } catch (\Throwable $e) {
        }

        $session = session();
        foreach ([$session->get('user_id'), $session->get('id'), $session->get('uid')] as $cand) {
            $id = (int) ($cand ?? 0);
            if ($id > 0) {
                return $id;
            }
        }
        $user = $session->get('user');
        if (is_array($user)) {
            return (int) ($user['id'] ?? $user['user_id'] ?? 0);
        }
        return 0;
    }
}
