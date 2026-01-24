<?php

namespace App\Controllers\Koordinator;

use App\Controllers\Koordinator\BaseKoordinatorController;
use App\Services\ReportService;
use App\Libraries\PDFGenerator;
use CodeIgniter\HTTP\RedirectResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportController extends BaseKoordinatorController
{
    protected ReportService $report;
    protected PDFGenerator $pdf;

    public function __construct()
    {
        $this->report = new ReportService();
        $this->pdf    = new PDFGenerator();

        helper(['url', 'form', 'text', 'number', 'date']);
    }

    public function index()
    {
        if ($redir = $this->ensurePerm('view_reports_aggregate', '/koordinator/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        $req = service('request');
        $db  = db_connect();

        $classes = $db->table('classes')
            ->select('id, class_name')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('class_name', 'ASC')
            ->get()->getResultArray();

        $counselors = $db->table('users u')
            ->select('u.id, u.full_name, r.role_name')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->where('u.deleted_at', null)
            ->whereIn('r.role_name', ['Guru BK', 'Koordinator BK'])
            ->orderBy('u.full_name', 'ASC')
            ->get()->getResultArray();

        $categories = $db->table('violation_categories')
            ->select('id, category_name')
            ->where('deleted_at', null)
            ->orderBy('category_name', 'ASC')
            ->get()->getResultArray();

        // default: bulan ini
        $valFrom = (string) ($req->getGet('date_from') ?: date('Y-m-01'));
        $valTo   = (string) ($req->getGet('date_to')   ?: date('Y-m-d'));

        // tampilan export
        $valPaper  = $this->normalizePaper((string)($req->getGet('paper') ?: 'A4'));
        $valOrient = $this->normalizeOrientation((string)($req->getGet('orientation') ?: 'portrait'));

        return view('koordinator/reports/index', [
            'pageTitle'   => 'Laporan',

            'classes'     => $classes,
            'counselors'  => $counselors,
            'categories'  => $categories,

            'valFrom'     => $valFrom,
            'valTo'       => $valTo,

            'valClass'     => (string) ($req->getGet('class_id') ?? ''),
            'valCounselor' => (string) ($req->getGet('counselor_id') ?? ''),
            'valCategory'  => (string) ($req->getGet('category_id') ?? ''),

            'valPaper'    => $valPaper,
            'valOrient'   => $valOrient,
        ]);
    }

    public function preview()
    {
        if ($redir = $this->ensurePerm('view_reports_aggregate', '/koordinator/dashboard', 'Akses laporan ditolak.')) {
            return $redir;
        }

        $f = $this->filters();

        try {
            $data = $this->report->schoolAggregate(
                $f['date_from'],
                $f['date_to'],
                $f['class_id'],
                $f['counselor_id'],
                $f['category_id']
            );

            // opsional: rapikan status asesmen (0/1/2 -> label)
            $data = $this->humanizeAggregate($data);

            return view('koordinator/reports/partials/aggregate_preview', [
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            // Untuk AJAX preview: jangan blank putih, tampilkan alert agar user paham.
            if ($this->request->isAJAX()) {
                $msg = esc($e->getMessage());
                return $this->response->setStatusCode(200)->setBody(
                    '<div class="alert alert-danger mb-0">
                        <b>Gagal memuat pratinjau.</b><br>
                        <small class="text-muted">Detail: ' . $msg . '</small>
                     </div>'
                );
            }

            // Non-AJAX: lempar agar CI error page tampil (dev)
            throw $e;
        }
    }

    public function download()
    {
        if ($redir = $this->ensurePerm('generate_reports_aggregate', '/koordinator/reports', 'Anda tidak punya izin untuk mengunduh laporan.')) {
            return $redir;
        }

        $f = $this->filters();

        $format      = strtolower((string) ($this->request->getGet('format') ?: 'pdf'));
        $paper       = $this->normalizePaper((string) ($this->request->getGet('paper') ?: 'A4'));
        $orientation = $this->normalizeOrientation((string) ($this->request->getGet('orientation') ?: 'portrait'));

        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            $format = 'pdf';
        }

        try {
            $data = $this->report->schoolAggregate(
                $f['date_from'],
                $f['date_to'],
                $f['class_id'],
                $f['counselor_id'],
                $f['category_id']
            );

            $data = $this->humanizeAggregate($data);

            $filename = $this->safeFilename(
                'laporan_agregat_' .
                ($data['period']['from'] ?: 'all') . '_' .
                ($data['period']['to'] ?: 'all')
            );

            if ($format === 'xlsx') {
                $tmpPath = $this->buildAggregateXlsx($data, $filename);

                // auto-clean setelah response selesai
                register_shutdown_function(static function () use ($tmpPath) {
                    @unlink($tmpPath);
                });

                return $this->response
                    ->download($tmpPath, null)
                    ->setFileName($filename . '.xlsx');
            }

            // PDF default
            $html = view('koordinator/reports/partials/aggregate_pdf', [
                'data' => $data,
            ]);

            $bin = $this->pdf->render($html, $paper, $orientation);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
                ->setBody($bin);
        } catch (\Throwable $e) {
            // UX: balik ke halaman report dengan flash error
            return redirect()->to('/koordinator/reports')
                ->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    // =========================
    // Helpers
    // =========================
    protected function filters(): array
    {
        $dateFrom = $this->normalizeDate($this->request->getGet('date_from'));
        $dateTo   = $this->normalizeDate($this->request->getGet('date_to'));

        // kalau kebalik, tukar (biar UX gak “ngambek”)
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'class_id'     => $this->request->getGet('class_id') ? (int) $this->request->getGet('class_id') : null,
            'counselor_id' => $this->request->getGet('counselor_id') ? (int) $this->request->getGet('counselor_id') : null,
            'category_id'  => $this->request->getGet('category_id') ? (int) $this->request->getGet('category_id') : null,
        ];
    }

    private function safeFilename(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_\-]+/i', '_', $name) ?? 'report';
        $name = trim($name, '_');

        // batasi panjang (hindari error OS tertentu)
        if (strlen($name) > 120) {
            $name = substr($name, 0, 120);
        }

        return $name ?: 'report';
    }

    private function normalizeDate($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') return null;

        // format yang kita izinkan: YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return null;

        [$y, $m, $d] = array_map('intval', explode('-', $value));
        if (!checkdate($m, $d, $y)) return null;

        return $value;
    }

    /**
     * PDF libs biasanya menerima: A4/A3 atau letter/legal.
     * Kita kembalikan bentuk yang konsisten dengan opsi di view.
     */
    private function normalizePaper(string $paper): string
    {
        $p = strtolower(trim($paper));
        $map = [
            'a4'     => 'A4',
            'a3'     => 'A3',
            'letter' => 'letter',
            'legal'  => 'legal',
        ];
        return $map[$p] ?? 'A4';
    }

    private function normalizeOrientation(string $orientation): string
    {
        $o = strtolower(trim($orientation));
        return in_array($o, ['portrait', 'landscape'], true) ? $o : 'portrait';
    }

    /**
     * Guard permission opsional.
     * Kalau helper has_permission() tidak ada, jangan blok (role guard BaseKoordinatorController tetap berjalan).
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
     * Opsional: rapikan output agregat tanpa mengubah ReportService,
     * terutama untuk status asesmen yang kadang berupa numeric code (0/1/2/3).
     */
    private function humanizeAggregate(array $data): array
    {
        // Humanize assessment status breakdown (kalau view/PDF menampilkan byStatus)
        if (isset($data['assessments']['byStatus']) && is_array($data['assessments']['byStatus'])) {
            $out = [];
            foreach ($data['assessments']['byStatus'] as $statusKey => $count) {
                $label = $this->assessmentStatusLabel($statusKey);
                $out[$label] = ($out[$label] ?? 0) + (int)$count;
            }
            $data['assessments']['byStatus'] = $out;
        }

        // (Opsional lain bisa ditambah di sini bila perlu)
        return $data;
    }

    private function buildAggregateXlsx(array $data, string $filename): string
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Overview
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Overview');

        $school = $data['school'] ?? [];
        $period = $data['period']['label'] ?? '-';

        $meta = [
            ['Judul', 'Laporan Agregat BK'],
            ['Sekolah', (string) ($school['name'] ?? '-')],
            ['Periode', (string) $period],
            ['Scope', (string) ($data['scope']['label'] ?? 'Semua Data')],
            ['Dibuat', (string) ($data['generated_at'] ?? date('Y-m-d H:i:s'))],
        ];

        $row = 1;
        foreach ($meta as $pair) {
            $sheet->setCellValueExplicit("A{$row}", (string) $pair[0], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", (string) $pair[1], DataType::TYPE_STRING);
            $row++;
        }

        $row += 1;
        $sheet->setCellValueExplicit("A{$row}", 'Ringkasan KPI', DataType::TYPE_STRING);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $kpi = $data['kpi'] ?? [];
        $kpiPairs = [
            ['Total Siswa', (string) ($kpi['students_total'] ?? 0)],
            ['Total Sesi', (string) ($kpi['sessions_total'] ?? 0)],
            ['Total Durasi (menit)', (string) ($kpi['sessions_duration_total'] ?? 0)],
            ['Total Pelanggaran', (string) ($kpi['violations_total'] ?? 0)],
            ['Total Poin', (string) ($kpi['violations_points_total'] ?? 0)],
            ['Kasus Aktif', (string) ($kpi['violations_active'] ?? 0)],
            ['Total Sanksi', (string) ($kpi['sanctions_total'] ?? 0)],
            ['Asesmen Assigned', (string) ($kpi['assessments_assigned'] ?? 0)],
            ['Asesmen Completed', (string) ($kpi['assessments_completed'] ?? 0)],
            ['Avg Score (%)', (string) ($kpi['assessments_avg_percentage'] ?? 0)],
        ];

        foreach ($kpiPairs as $pair) {
            $sheet->setCellValueExplicit("A{$row}", (string) $pair[0], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", (string) $pair[1], DataType::TYPE_STRING);
            $row++;
        }

        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 2: Sessions
        $sessionsSheet = $spreadsheet->createSheet();
        $sessionsSheet->setTitle('Sessions');

        $this->writeTable(
            $sessionsSheet,
            1,
            ['Jenis', 'Jumlah', 'Durasi (menit)'],
            array_map(static function ($r) {
                return [
                    (string) ($r['label'] ?? ''),
                    (string) ($r['count'] ?? 0),
                    (string) ($r['duration'] ?? 0),
                ];
            }, $data['sessions']['byType'] ?? [])
        );

        $start = 1 + 2 + max(1, count($data['sessions']['byType'] ?? [])) + 2;

        $this->writeTable(
            $sessionsSheet,
            $start,
            ['Konselor', 'Jumlah', 'Durasi (menit)'],
            array_map(static function ($r) {
                return [
                    (string) ($r['label'] ?? ''),
                    (string) ($r['count'] ?? 0),
                    (string) ($r['duration'] ?? 0),
                ];
            }, $data['sessions']['byCounselor'] ?? [])
        );

        // Sheet 3: Violations
        $vioSheet = $spreadsheet->createSheet();
        $vioSheet->setTitle('Violations');

        $this->writeTable(
            $vioSheet,
            1,
            ['Level', 'Jumlah', 'Total Poin'],
            array_map(static function ($r) {
                return [
                    (string) ($r['label'] ?? ''),
                    (string) ($r['count'] ?? 0),
                    (string) ($r['points'] ?? 0),
                ];
            }, $data['violations']['byLevel'] ?? [])
        );

        $start = 1 + 2 + max(1, count($data['violations']['byLevel'] ?? [])) + 2;

        $this->writeTable(
            $vioSheet,
            $start,
            ['Kategori', 'Jumlah', 'Total Poin'],
            array_map(static function ($r) {
                return [
                    (string) ($r['label'] ?? ''),
                    (string) ($r['count'] ?? 0),
                    (string) ($r['points'] ?? 0),
                ];
            }, $data['violations']['byCategory'] ?? [])
        );

        // Sheet 4: Sanctions
        $sanSheet = $spreadsheet->createSheet();
        $sanSheet->setTitle('Sanctions');

        $this->writeTable(
            $sanSheet,
            1,
            ['Jenis Sanksi', 'Jumlah'],
            array_map(static function ($r) {
                return [(string) ($r['label'] ?? ''), (string) ($r['count'] ?? 0)];
            }, $data['sanctions']['byType'] ?? [])
        );

        // Sheet 5: Assessments
        $assSheet = $spreadsheet->createSheet();
        $assSheet->setTitle('Assessments');

        $this->writeTable(
            $assSheet,
            1,
            ['Asesmen', 'Assigned', 'Completed', 'Avg (%)'],
            array_map(static function ($r) {
                return [
                    (string) ($r['label'] ?? ''),
                    (string) ($r['assigned'] ?? 0),
                    (string) ($r['completed'] ?? 0),
                    (string) ($r['avg_percentage'] ?? 0),
                ];
            }, $data['assessments']['byAssessment'] ?? [])
        );

        // (Opsional) Sheet 6: Assessment Status
        if (!empty($data['assessments']['byStatus']) && is_array($data['assessments']['byStatus'])) {
            $stSheet = $spreadsheet->createSheet();
            $stSheet->setTitle('Assessment Status');

            $rows = [];
            foreach ($data['assessments']['byStatus'] as $label => $count) {
                $rows[] = [(string)$label, (string)(int)$count];
            }

            $this->writeTable($stSheet, 1, ['Status', 'Jumlah'], $rows);
        }

        $tmpPath = WRITEPATH . 'uploads/' . $filename . '.xlsx';
        @mkdir(dirname($tmpPath), 0775, true);

        (new Xlsx($spreadsheet))->save($tmpPath);

        return $tmpPath;
    }

    private function writeTable($sheet, int $startRow, array $headers, array $rows): void
    {
        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValueExplicit($col . $startRow, (string) $h, DataType::TYPE_STRING);
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
                    is_scalar($val) ? (string) $val : json_encode($val, JSON_UNESCAPED_UNICODE),
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

    /**
     * Map status asesmen ke label manusia.
     * Menangani kemungkinan status numeric (0/1/2/3) atau string (Assigned/Completed/Graded).
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

        $s = trim((string) $status);
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
}
