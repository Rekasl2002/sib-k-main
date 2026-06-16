<?php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Services\ReportService;
use App\Libraries\PDFGenerator;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ClassReportController extends BaseController
{
    protected ReportService $report;
    protected ?PDFGenerator $pdf = null;

    public function __construct()
    {
        $this->report = new ReportService();

        if (function_exists('helper')) {
            try {
                helper(['url', 'form', 'text', 'number', 'date', 'permission', 'auth', 'settings']);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function index()
    {
        if ($redir = $this->ensurePerm('view_reports_individual', '/homeroom/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        $uid = $this->currentUserId();
        $db  = db_connect();
        $req = service('request');

        $classes = $db->table('classes')
            ->select('id, class_name')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('homeroom_teacher_id', $uid)
            ->orderBy('class_name', 'ASC')
            ->get()->getResultArray();

        $valFrom = (string)($req->getGet('date_from') ?: date('Y-m-01'));
        $valTo   = (string)($req->getGet('date_to')   ?: date('Y-m-d'));

        $requestedClassId = $req->getGet('class_id') ? (int)$req->getGet('class_id') : null;
        $resolvedClassId  = $this->resolveHomeroomClassId($uid, $requestedClassId);
        $valClass         = $resolvedClassId ? (string)$resolvedClassId : '';

        $valPaper  = $this->normalizePaper((string)($req->getGet('paper') ?: 'A4'));
        $valOrient = $this->normalizeOrientation((string)($req->getGet('orientation') ?: 'portrait'));
        $students  = $resolvedClassId ? $this->report->studentOptionsForClass($resolvedClassId) : [];

        $noClassAssigned = empty($classes);

        return view('homeroom_teacher/reports/index', [
            'pageTitle' => 'Laporan Kelas',
            'classes'   => $classes,
            'students'  => $students,

            'valFrom'   => $valFrom,
            'valTo'     => $valTo,
            'valClass'  => $valClass,

            'valPaper'  => $valPaper,
            'valOrient' => $valOrient,
            'valMode'   => (string)($req->getGet('mode') ?: 'class_summary'),
            'valStudent'=> (string)($req->getGet('student_id') ?? ''),

            'noClassAssigned' => $noClassAssigned,
        ]);
    }

    public function preview()
    {
        if ($redir = $this->ensurePerm('view_reports_individual', '/homeroom/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        $uid = $this->currentUserId();
        $f   = $this->filters($uid);

        if (empty($f['class_id'])) {
            return $this->response
                ->setStatusCode(200)
                ->setBody(
                    '<div class="alert alert-warning mb-0">'
                    . '<b>Belum ada kelas binaan.</b> Akun Wali Kelas ini belum di-assign ke kelas manapun.'
                    . '</div>'
                );
        }

        if (($f['mode'] ?? 'class_summary') === 'student_individual') {
            $out = $this->buildIndividualPayload($f);

            return view('homeroom_teacher/reports/partials/table', [
                'title' => $out['title'],
                'columns' => $out['columns'],
                'rows' => $out['rows'],
            ]);
        }

        $data = $this->report->schoolAggregate(
            $f['date_from'],
            $f['date_to'],
            $f['class_id'],
            null,
            null
        );

        return view($this->previewView(), [
            'data' => $data,
        ]);
    }

    public function download()
    {
        // FIX: Jangan pakai generate_reports untuk Homeroom kecuali memang kamu ingin memisah izin.
        if ($redir = $this->ensurePerm('view_reports_individual', '/homeroom/reports', 'Anda tidak punya izin untuk mengunduh laporan.')) {
            return $redir;
        }

        $uid = $this->currentUserId();
        $f   = $this->filters($uid);

        if (empty($f['class_id'])) {
            return redirect()->to('/homeroom/reports')
                ->with('error', 'Akun Wali Kelas ini belum di-assign ke kelas manapun, sehingga laporan tidak bisa dibuat.');
        }

        // FIX: getVar() supaya support GET atau POST
        $format      = strtolower((string)($this->request->getVar('format') ?: 'pdf'));
        $paper       = $this->normalizePaper((string)($this->request->getVar('paper') ?: 'A4'));
        $orientation = $this->normalizeOrientation((string)($this->request->getVar('orientation') ?: 'portrait'));

        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            $format = 'pdf';
        }

        if (($f['mode'] ?? 'class_summary') === 'student_individual') {
            $out = $this->buildIndividualPayload($f);
            $filename = $this->safeFilename('laporan_individu_' . ($f['student_id'] ?: 'siswa') . '_' . ($f['date_from'] ?: 'all') . '_' . ($f['date_to'] ?: 'all'));

            if ($format === 'xlsx') {
                $tmpPath = $this->buildTableXlsx($out['title'], $out['columns'], $out['rows'], $filename);
                register_shutdown_function(static function () use ($tmpPath) {
                    @unlink($tmpPath);
                });

                return $this->response->download($tmpPath, null)->setFileName($filename . '.xlsx');
            }

            if (! PDFGenerator::isAvailable()) {
                return redirect()->to('/homeroom/reports')
                    ->with('error', 'Fitur unduh PDF belum tersedia karena paket Dompdf belum terpasang di server. Unduh Excel tetap dapat digunakan.');
            }

            $html = view('counselor/reports/partials/table_pdf', [
                'title' => $out['title'],
                'columns' => $out['columns'],
                'rows' => $out['rows'],
                'filters' => $f,
            ]);

            $bin = $this->pdf()->render($html, $paper, $orientation);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
                ->setBody($bin);
        }

        $data = $this->report->schoolAggregate(
            $f['date_from'],
            $f['date_to'],
            $f['class_id'],
            null,
            null
        );

        $scopeLabel = (string)($data['scope']['label'] ?? 'kelas');
        $filename = $this->safeFilename(
            'laporan_kelas_' .
            $scopeLabel . '_' .
            ((string)($data['period']['from'] ?? 'all')) . '_' .
            ((string)($data['period']['to'] ?? 'all'))
        );

        if ($format === 'xlsx') {
            $tmpPath = $this->buildAggregateXlsx($data, $filename);

            register_shutdown_function(static function () use ($tmpPath) {
                @unlink($tmpPath);
            });

            return $this->response
                ->download($tmpPath, null)
                ->setFileName($filename . '.xlsx');
        }

        if (! PDFGenerator::isAvailable()) {
            return redirect()->to('/homeroom/reports')
                ->with(
                    'error',
                    'Fitur unduh PDF belum tersedia karena paket Dompdf belum terpasang di server. Unduh Excel tetap dapat digunakan.'
                );
        }

        $html = view($this->pdfView(), [
            'data' => $data,
        ]);

        try {
            $bin = $this->pdf()->render($html, $paper, $orientation);
        } catch (\Throwable $e) {
            log_message('error', '[HOMEROOM REPORT PDF] ' . $e->getMessage());

            return redirect()->to('/homeroom/reports')
                ->with('error', 'PDF belum bisa dibuat. Silakan coba unduh Excel atau periksa instalasi Dompdf di server.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
            ->setBody($bin);
    }

    // =========================
    // Helpers
    // =========================

    protected function filters(int $uid): array
    {
        // FIX: getVar() supaya support GET atau POST
        $dateFrom = $this->normalizeDate($this->request->getVar('date_from'));
        $dateTo   = $this->normalizeDate($this->request->getVar('date_to'));

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $requestedClassId = $this->request->getVar('class_id') ? (int)$this->request->getVar('class_id') : null;
        $classId          = $this->resolveHomeroomClassId($uid, $requestedClassId);

        return [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'class_id'  => $classId,
            'mode'      => (string)($this->request->getVar('mode') ?: 'class_summary'),
            'student_id'=> $this->request->getVar('student_id') ? (int)$this->request->getVar('student_id') : null,
        ];
    }

    private function buildIndividualPayload(array $f): array
    {
        $studentId = (int)($f['student_id'] ?? 0);
        $classId = (int)($f['class_id'] ?? 0);
        $allowed = $classId > 0
            ? array_map(static fn ($r) => (int)($r['id'] ?? 0), $this->report->studentOptionsForClass($classId))
            : [];

        if ($studentId <= 0 || !in_array($studentId, $allowed, true)) {
            return [
                'title' => 'Laporan Per Siswa',
                'columns' => ['Tanggal', 'Kategori', 'Kegiatan', 'Status', 'Catatan'],
                'rows' => [],
            ];
        }

        $out = $this->report->studentIndividualTable($studentId, $f['date_from'] ?? null, $f['date_to'] ?? null);
        $student = $out['student'] ?? [];

        return [
            'title' => 'Laporan Per Siswa - ' . (string)($student['full_name'] ?? 'Siswa'),
            'columns' => $out['columns'] ?? [],
            'rows' => $out['rows'] ?? [],
        ];
    }

    private function resolveHomeroomClassId(int $uid, ?int $requested): ?int
    {
        $db = db_connect();

        $rows = $db->table('classes')
            ->select('id')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('homeroom_teacher_id', $uid)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        if (empty($rows)) {
            return null;
        }

        $allowed = array_map(static fn($r) => (int)$r['id'], $rows);

        if ($requested && in_array($requested, $allowed, true)) {
            return $requested;
        }

        return $allowed[0];
    }

    private function currentUserId(): int
    {
        $session = session();

        $id = $session->get('user_id') ?? $session->get('id') ?? 0;

        if (is_array($id)) {
            $id = $id['id'] ?? 0;
        }

        return (int)$id;
    }

    private function pdf(): PDFGenerator
    {
        if ($this->pdf === null) {
            $this->pdf = new PDFGenerator();
        }

        return $this->pdf;
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

    private function safeFilename(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_\-]+/i', '_', $name) ?? 'report';
        $name = trim($name, '_');

        if (strlen($name) > 120) {
            $name = substr($name, 0, 120);
        }

        return $name ?: 'report';
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

    private function previewView(): string
    {
        $preferred = 'homeroom_teacher/reports/partials/class_summary_preview';
        $fallback  = 'homeroom_teacher/reports/partials/aggregate_preview';

        return $this->viewExists($preferred) ? $preferred : $fallback;
    }

    private function pdfView(): string
    {
        $preferred = 'homeroom_teacher/reports/partials/class_summary_pdf';
        $fallback  = 'homeroom_teacher/reports/partials/aggregate_pdf';

        return $this->viewExists($preferred) ? $preferred : $fallback;
    }

    private function viewExists(string $view): bool
    {
        $path = APPPATH . 'Views/' . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
        return is_file($path);
    }

    private function buildAggregateXlsx(array $data, string $filename): string
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan');

        $school = $data['school'] ?? [];
        $period = $data['period']['label'] ?? '-';

        $meta = [
            ['Judul', 'Laporan Kelas (Wali Kelas)'],
            ['Sekolah', (string)($school['name'] ?? '-')],
            ['Periode', (string)$period],
            ['Lingkup', (string)($data['scope']['label'] ?? 'Kelas')],
            ['Dibuat', (string)($data['generated_at'] ?? date('Y-m-d H:i:s'))],
        ];

        $row = 1;
        foreach ($meta as $pair) {
            $sheet->setCellValueExplicit("A{$row}", (string)$pair[0], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", (string)$pair[1], DataType::TYPE_STRING);
            $row++;
        }

        $row += 1;
        $sheet->setCellValueExplicit("A{$row}", 'Ringkasan Angka Penting', DataType::TYPE_STRING);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $kpi = $data['kpi'] ?? [];
        $kpiPairs = [
            ['Total Siswa', (string)($kpi['students_total'] ?? 0)],
            ['Total Catatan Konseling', (string)($kpi['sessions_total'] ?? 0)],
            ['Total Durasi (menit)', (string)($kpi['sessions_duration_total'] ?? 0)],
            ['Asesmen Ditugaskan', (string)($kpi['assessments_assigned'] ?? 0)],
            ['Asesmen Selesai', (string)($kpi['assessments_completed'] ?? 0)],
            ['Rata-rata Nilai (%)', (string)($kpi['assessments_avg_percentage'] ?? 0)],
        ];

        foreach ($kpiPairs as $pair) {
            $sheet->setCellValueExplicit("A{$row}", (string)$pair[0], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", (string)$pair[1], DataType::TYPE_STRING);
            $row++;
        }

        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sessionsSheet = $spreadsheet->createSheet();
        $sessionsSheet->setTitle('Catatan Konseling');

        $this->writeTable(
            $sessionsSheet,
            1,
            ['Jenis', 'Jumlah', 'Durasi (menit)'],
            array_map(static function ($r) {
                return [
                    (string)($r['label'] ?? ''),
                    (string)($r['count'] ?? 0),
                    (string)($r['duration'] ?? 0),
                ];
            }, $data['sessions']['byType'] ?? [])
        );

        $start = 1 + 2 + max(1, count($data['sessions']['byType'] ?? [])) + 2;

        $this->writeTable(
            $sessionsSheet,
            $start,
            ['Guru BK', 'Jumlah', 'Durasi (menit)'],
            array_map(static function ($r) {
                return [
                    (string)($r['label'] ?? ''),
                    (string)($r['count'] ?? 0),
                    (string)($r['duration'] ?? 0),
                ];
            }, $data['sessions']['byCounselor'] ?? [])
        );

        $assSheet = $spreadsheet->createSheet();
        $assSheet->setTitle('Asesmen');

        $this->writeTable(
            $assSheet,
            1,
            ['Asesmen', 'Ditugaskan', 'Selesai', 'Rata-rata (%)'],
            array_map(static function ($r) {
                return [
                    (string)($r['label'] ?? ''),
                    (string)($r['assigned'] ?? 0),
                    (string)($r['completed'] ?? 0),
                    (string)($r['avg_percentage'] ?? 0),
                ];
            }, $data['assessments']['byAssessment'] ?? [])
        );

        $tmpPath = WRITEPATH . 'uploads/' . $filename . '.xlsx';
        @mkdir(dirname($tmpPath), 0775, true);

        (new Xlsx($spreadsheet))->save($tmpPath);

        return $tmpPath;
    }

    private function buildTableXlsx(string $title, array $columns, array $rows, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan');

        $sheet->setCellValueExplicit('A1', 'Judul', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B1', $title, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('A2', 'Dibuat', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', date('Y-m-d H:i:s'), DataType::TYPE_STRING);

        $this->writeTable($sheet, 4, $columns ?: ['Data'], array_map(static function ($row) {
            return is_array($row)
                ? array_map(static fn ($value) => is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE), array_values($row))
                : [(string)$row];
        }, $rows));

        $tmpPath = WRITEPATH . 'uploads/' . $filename . '.xlsx';
        @mkdir(dirname($tmpPath), 0775, true);

        (new Xlsx($spreadsheet))->save($tmpPath);

        return $tmpPath;
    }

    private function writeTable($sheet, int $startRow, array $headers, array $rows): void
    {
        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValueExplicit($col . $startRow, (string)$h, DataType::TYPE_STRING);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A{$startRow}:{$lastCol}{$startRow}")->getFont()->setBold(true);
        $sheet->freezePane('A' . ($startRow + 1));

        $r = $startRow + 1;

        if (!$rows) {
            $sheet->setCellValueExplicit('A' . $r, '(tidak ada data)', DataType::TYPE_STRING);
            return;
        }

        foreach ($rows as $row) {
            $c = 1;
            foreach ($row as $val) {
                $col = Coordinate::stringFromColumnIndex($c);
                $sheet->setCellValueExplicit(
                    $col . $r,
                    is_scalar($val) ? (string)$val : json_encode($val, JSON_UNESCAPED_UNICODE),
                    DataType::TYPE_STRING
                );
                $c++;
            }
            $r++;
        }

        for ($i = 1; $i <= count($headers); $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
