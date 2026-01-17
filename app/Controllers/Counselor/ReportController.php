<?php

/**
 * File Path: app/Controllers/Counselor/ReportController.php
 *
 * Counselor • Report Controller
 * - Preview laporan (AJAX) + Download PDF/XLSX
 * - Scope dibatasi untuk data binaan counselor (melalui ReportService)
 *
 * Catatan:
 * - Tidak memakai BaseCounselorController (karena tidak ada).
 * - Extend App\Controllers\BaseController agar $request/$response tersedia.
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
    protected PDFGenerator $pdf;

    public function __construct()
    {
        $this->report = new ReportService();
        $this->pdf    = new PDFGenerator();

        // aman walau sudah di-autoload di BaseController
        if (function_exists('helper')) {
            try {
                helper(['url', 'form', 'text', 'number', 'date', 'permission', 'settings', 'auth']);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    /**
     * Halaman laporan counselor.
     * View: counselor/reports/index
     */
    public function index()
    {
        if ($redir = $this->ensurePerm('view_reports', '/counselor/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        $db = db_connect();
        $counselorId = $this->currentUserId();

        // hanya kelas binaan counselor (biar filter tidak menampilkan kelas lain)
        $classes = $db->table('classes')
            ->select('id, class_name')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('counselor_id', $counselorId)
            ->orderBy('class_name', 'ASC')
            ->get()->getResultArray();

        // daftar asesmen (opsional) untuk filter report asesmen
        $assessments = $db->table('assessments')
            ->select('id, title')
            ->where('deleted_at', null)
            ->where('created_by', $counselorId)
            ->orderBy('title', 'ASC')
            ->get()->getResultArray();

        // default periode: bulan ini
        $valFrom = (string)($this->request->getGet('date_from') ?: date('Y-m-01'));
        $valTo   = (string)($this->request->getGet('date_to')   ?: date('Y-m-d'));

        $valPaper  = $this->normalizePaper((string)($this->request->getGet('paper') ?: 'A4'));
        $valOrient = $this->normalizeOrientation((string)($this->request->getGet('orientation') ?: 'portrait'));

        // jenis laporan default
        $valType = (string)($this->request->getGet('type') ?: 'sessions');

        return view('counselor/reports/index', [
            'pageTitle'   => 'Laporan',

            'classes'     => $classes,
            'assessments' => $assessments,

            'valFrom'     => $valFrom,
            'valTo'       => $valTo,
            'valClass'    => (string)($this->request->getGet('class_id') ?? ''),
            'valStatus'   => (string)($this->request->getGet('status') ?? ''),
            'valSearch'   => (string)($this->request->getGet('search') ?? ''),
            'valSortBy'   => (string)($this->request->getGet('sort_by') ?? ''),
            'valSortDir'  => (string)($this->request->getGet('sort_dir') ?? 'desc'),

            'valType'     => $valType,
            'valPaper'    => $valPaper,
            'valOrient'   => $valOrient,

            // untuk report asesmen
            'valAssessmentId' => (string)($this->request->getGet('assessment_id') ?? ''),
        ]);
    }

    /**
     * AJAX preview (HTML)
     * View partial: counselor/reports/partials/table
     */
    public function preview()
    {
        if ($redir = $this->ensurePerm('view_reports', '/counselor/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        $counselorId = $this->currentUserId();
        $f = $this->filters();

        [$title, $columns, $rows] = $this->buildPayload($f, $counselorId);

        return view('counselor/reports/partials/table', [
            'title'   => $title,
            'columns' => $columns,
            'rows'    => $rows,
        ]);
    }

    /**
     * Download PDF/XLSX
     */
    public function download()
    {
        if ($redir = $this->ensurePerm('generate_reports', '/counselor/reports', 'Anda tidak punya izin untuk mengunduh laporan.')) {
            return $redir;
        }

        $counselorId = $this->currentUserId();
        $f = $this->filters();

        $format      = strtolower((string)($this->request->getGet('format') ?: 'pdf'));
        $paper       = $this->normalizePaper((string)($this->request->getGet('paper') ?: 'A4'));
        $orientation = $this->normalizeOrientation((string)($this->request->getGet('orientation') ?: 'portrait'));

        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            $format = 'pdf';
        }

        [$title, $columns, $rows] = $this->buildPayload($f, $counselorId);

        $filename = $this->safeFilename(
            'laporan_' . ($f['type'] ?: 'report') . '_' .
            ($f['date_from'] ?: 'all') . '_' . ($f['date_to'] ?: 'all')
        );

        if ($format === 'xlsx') {
            $tmpPath = $this->buildTableXlsx($title, $columns, $rows, $filename, $f);

            register_shutdown_function(static function () use ($tmpPath) {
                @unlink($tmpPath);
            });

            return $this->response
                ->download($tmpPath, null)
                ->setFileName($filename . '.xlsx');
        }

        // PDF
        $html = view('counselor/reports/partials/table_pdf', [
            'title'   => $title,
            'columns' => $columns,
            'rows'    => $rows,
            'filters' => $f,
        ]);

        $bin = $this->pdf->render($html, $paper, $orientation);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
            ->setBody($bin);
    }

    // =========================================================
    // Payload builder (ambil data dari ReportService)
    // =========================================================

    private function buildPayload(array $f, int $counselorId): array
    {
        $type = $this->normalizeType((string)($f['type'] ?? 'sessions'));

        $title = 'LAPORAN';
        $columns = [];
        $rows = [];

        // filter yang dikirim ke ReportService
        $filter = [
            'date_from'     => $f['date_from'],
            'date_to'       => $f['date_to'],
            'class_id'      => $f['class_id'],
            'status'        => $f['status'],
            'search'        => $f['search'],
            'sort_by'       => $f['sort_by'],
            'sort_dir'      => $f['sort_dir'],
            'assessment_id' => $f['assessment_id'],
        ];

        switch ($type) {
            case 'students':
                $title = 'Laporan Data Siswa (Binaan)';
                $out = $this->report->students($filter, $counselorId);
                $columns = $out['columns'] ?? [];
                $rows    = $out['rows'] ?? [];
                break;

            case 'sessions':
                $title = 'Laporan Sesi Konseling';
                $out = $this->report->sessions($filter, $counselorId);
                $columns = $out['columns'] ?? [];
                $rows    = $out['rows'] ?? [];
                break;

            case 'violations':
                $title = 'Laporan Pelanggaran (Binaan)';
                $out = $this->report->violations($filter, $counselorId);
                $columns = $out['columns'] ?? [];
                $rows    = $out['rows'] ?? [];
                break;

            case 'assessments':
                $title = 'Laporan Asesmen (Binaan)';
                $out = $this->report->assessments($filter, $counselorId);
                $columns = $out['columns'] ?? [];
                $rows    = $out['rows'] ?? [];

                // rapikan status agar tidak tampil 0/1/2 mentah
                $rows = $this->mapAssessmentStatusRows($rows);
                break;

            case 'career_choices':
                $title = 'Laporan Pilihan Karir Siswa';
                $out = $this->report->careerChoices($filter, $counselorId);
                $columns = $out['columns'] ?? [];
                $rows    = $out['rows'] ?? [];
                break;

            case 'university_choices':
                $title = 'Laporan Pilihan Universitas Siswa';
                $out = $this->report->universityChoices($filter, $counselorId);
                $columns = $out['columns'] ?? [];
                $rows    = $out['rows'] ?? [];
                break;

            default:
                $title = 'Laporan Sesi Konseling';
                $out = $this->report->sessions($filter, $counselorId);
                $columns = $out['columns'] ?? [];
                $rows    = $out['rows'] ?? [];
                break;
        }

        return [$title, $columns, $rows];
    }

    private function mapAssessmentStatusRows(array $rows): array
    {
        foreach ($rows as &$r) {
            if (is_array($r) && array_key_exists('status', $r)) {
                $r['status'] = $this->assessmentStatusLabel($r['status']);
            }
        }
        unset($r);

        return $rows;
    }

    // =========================================================
    // Helpers (filter, sanitasi, permission)
    // =========================================================

    protected function filters(): array
    {
        $dateFrom = $this->normalizeDate($this->request->getGet('date_from'));
        $dateTo   = $this->normalizeDate($this->request->getGet('date_to'));

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'type'          => (string)($this->request->getGet('type') ?: 'sessions'),

            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,

            'class_id'      => $this->request->getGet('class_id') ? (int)$this->request->getGet('class_id') : null,
            'status'        => (string)($this->request->getGet('status') ?? ''),
            'search'        => (string)($this->request->getGet('search') ?? ''),

            'sort_by'       => (string)($this->request->getGet('sort_by') ?? ''),
            'sort_dir'      => (string)($this->request->getGet('sort_dir') ?? 'desc'),

            // khusus asesmen
            'assessment_id' => $this->request->getGet('assessment_id') ? (int)$this->request->getGet('assessment_id') : null,
        ];
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = [
            'students',
            'sessions',
            'violations',
            'assessments',
            'career_choices',
            'university_choices',
        ];
        return in_array($type, $allowed, true) ? $type : 'sessions';
    }

    private function safeFilename(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_\-]+/i', '_', $name) ?? 'report';
        $name = trim($name, '_');
        if (strlen($name) > 120) $name = substr($name, 0, 120);
        return $name ?: 'report';
    }

    private function normalizeDate($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') return null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return null;

        [$y, $m, $d] = array_map('intval', explode('-', $value));
        if (!checkdate($m, $d, $y)) return null;

        return $value;
    }

    private function normalizePaper(string $paper): string
    {
        $paper = strtoupper(trim($paper));
        $allowed = ['A4', 'A3', 'LETTER', 'LEGAL'];
        return in_array($paper, $allowed, true) ? $paper : 'A4';
    }

    private function normalizeOrientation(string $orientation): string
    {
        $orientation = strtolower(trim($orientation));
        return in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : 'portrait';
    }

    /**
     * Guard permission opsional.
     * Kalau helper has_permission() tidak ada, jangan blok (guard role di routes tetap berjalan).
     */
    private function ensurePerm(string $perm, string $redirectTo, string $message): ?RedirectResponse
    {
        if (function_exists('has_permission')) {
            if (!has_permission($perm)) {
                return redirect()->to($redirectTo)->with('error', $message);
            }
        }
        return null;
    }

    /**
     * Ambil user id login dengan fallback aman.
     * Dibuat tanpa memanggil current_user_id() secara langsung (biar Intelephense tidak error).
     */
    private function currentUserId(): int
    {
        // 1) Jika project punya helper current_user_id()
        try {
            if (function_exists('current_user_id')) {
                $id = (int) call_user_func('current_user_id'); // <- aman untuk Intelephense
                if ($id > 0) return $id;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) CI Shield (jika ada): service('authentication')->user()
        try {
            $auth = service('authentication');
            if (is_object($auth) && method_exists($auth, 'user')) {
                $u = $auth->user();
                if ($u && isset($u->id)) {
                    $id = (int) $u->id;
                    if ($id > 0) return $id;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 3) Fallback: session
        $session = session();

        $candidates = [
            $session->get('user_id'),
            $session->get('id'),
            $session->get('uid'),
            $session->get('logged_in_user_id'),
        ];

        foreach ($candidates as $cand) {
            $id = (int)($cand ?? 0);
            if ($id > 0) return $id;
        }

        // 4) Fallback terakhir: data user di session (array)
        $user = $session->get('user');
        if (is_array($user)) {
            $id = (int)($user['id'] ?? $user['user_id'] ?? 0);
            if ($id > 0) return $id;
        }

        return 0;
    }

    /**
     * Mapping status asesmen agar manusiawi.
     * Mengatasi kasus status numeric 0/1/2 yang muncul di laporan.
     */
    protected function assessmentStatusLabel($status): string
    {
        if ($status === null || $status === '') {
            return 'Unknown';
        }

        if (is_numeric($status)) {
            $i = (int) $status;
            return match ($i) {
                0 => 'Belum Mulai',
                1 => 'Sedang Dikerjakan',
                2 => 'Selesai',
                3 => 'Dinilai',
                default => 'Unknown (' . $i . ')',
            };
        }

        $s = trim((string)$status);
        $key = strtolower($s);

        $map = [
            'assigned'     => 'Belum Mulai',
            'not_started'  => 'Belum Mulai',
            'in progress'  => 'Sedang Dikerjakan',
            'in_progress'  => 'Sedang Dikerjakan',
            'started'      => 'Sedang Dikerjakan',
            'completed'    => 'Selesai',
            'done'         => 'Selesai',
            'graded'       => 'Dinilai',
        ];

        return $map[$key] ?? $s;
    }

    // =========================================================
    // XLSX Builder (tabel sederhana)
    // =========================================================

    private function buildTableXlsx(string $title, array $columns, array $rows, string $filename, array $filters): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        $r = 1;

        // Meta
        $sheet->setCellValueExplicit("A{$r}", 'Judul', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("B{$r}", $title, DataType::TYPE_STRING);
        $r++;

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $sheet->setCellValueExplicit("A{$r}", 'Periode', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(
                "B{$r}",
                (string)($filters['date_from'] ?? '-') . ' s/d ' . (string)($filters['date_to'] ?? '-'),
                DataType::TYPE_STRING
            );
            $r++;
        }

        $sheet->setCellValueExplicit("A{$r}", 'Dibuat', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("B{$r}", date('Y-m-d H:i:s'), DataType::TYPE_STRING);
        $r += 2;

        // Header
        if (empty($columns)) {
            $columns = ['Data'];
        }

        foreach ($columns as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValueExplicit($col . $r, (string)$h, DataType::TYPE_STRING);
        }

        $sheet->getStyle("A{$r}:" . Coordinate::stringFromColumnIndex(count($columns)) . "{$r}")
            ->getFont()->setBold(true);

        $sheet->freezePane('A' . ($r + 1));
        $r++;

        // Rows
        if (empty($rows)) {
            $sheet->setCellValueExplicit("A{$r}", '(tidak ada data)', DataType::TYPE_STRING);
        } else {
            foreach ($rows as $row) {
                $vals = is_array($row) ? array_values($row) : [(string)$row];

                foreach ($vals as $i => $val) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValueExplicit(
                        $col . $r,
                        is_scalar($val) ? (string)$val : json_encode($val, JSON_UNESCAPED_UNICODE),
                        DataType::TYPE_STRING
                    );
                }
                $r++;
            }
        }

        // Auto size kolom
        for ($i = 1; $i <= count($columns); $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tmpPath = WRITEPATH . 'uploads/' . $filename . '.xlsx';
        @mkdir(dirname($tmpPath), 0775, true);
        (new Xlsx($spreadsheet))->save($tmpPath);

        return $tmpPath;
    }
}
