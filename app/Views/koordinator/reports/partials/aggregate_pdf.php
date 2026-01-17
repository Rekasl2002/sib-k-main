<?php
/**
 * File Path: app/Views/koordinator/reports/partials/aggregate_pdf.php
 *
 * Koordinator • Aggregate Report PDF
 * - Fokus agregat (tanpa isi/catatan sesi konseling demi privasi)
 * - Layout dibuat ramah PDF generator (table-based)
 */

$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Semua Data';
$kpi    = $data['kpi'] ?? [];

$sessionsByType      = $data['sessions']['byType'] ?? [];
$sessionsByCounselor = $data['sessions']['byCounselor'] ?? [];
$sessionsByStatus    = $data['sessions']['byStatus'] ?? [];
$sessionsByMonth     = $data['sessions']['byMonth'] ?? [];

$violByLevel    = $data['violations']['byLevel'] ?? [];
$violByCategory = $data['violations']['byCategory'] ?? [];
$violByClass    = $data['violations']['byClass'] ?? [];

$sanByType   = $data['sanctions']['byType'] ?? [];
$sanByStatus = $data['sanctions']['byStatus'] ?? [];

$assByAssessment = $data['assessments']['byAssessment'] ?? [];
$assByStatus     = $data['assessments']['byStatus'] ?? [];

if (!function_exists('n0')) {
    function n0($v): string
    {
        return number_format((float)($v ?? 0), 0, ',', '.');
    }
}

if (!function_exists('fmt_month_id')) {
    function fmt_month_id(string $ym): string
    {
        // input: YYYY-MM
        if (!preg_match('/^\d{4}\-\d{2}$/', $ym)) return $ym;
        $y = (int)substr($ym, 0, 4);
        $m = (int)substr($ym, 5, 2);
        $names = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return ($names[$m] ?? $ym) . ' ' . $y;
    }
}

$generatedAt = (string)($data['generated_at'] ?? date('Y-m-d H:i:s'));

$schoolName  = (string)($school['name'] ?? '-');
$schoolAddr  = trim((string)($school['address'] ?? ''));
$schoolPhone = trim((string)($school['phone'] ?? ''));
$schoolEmail = trim((string)($school['email'] ?? ''));
$schoolWeb   = trim((string)($school['website'] ?? ''));
$schoolLogo  = trim((string)($school['logo'] ?? ''));

$metaLines = [];
if ($schoolAddr !== '')  $metaLines[] = $schoolAddr;
$contactParts = [];
if ($schoolPhone !== '') $contactParts[] = $schoolPhone;
if ($schoolEmail !== '') $contactParts[] = $schoolEmail;
if ($schoolWeb !== '')   $contactParts[] = $schoolWeb;
if ($contactParts) $metaLines[] = implode(' • ', $contactParts);

// helper render table rows
$renderEmptyRow = function(int $colspan, string $text = '(tidak ada data)') {
    return '<tr><td colspan="'.(int)$colspan.'" class="muted">'.esc($text).'</td></tr>';
};

// normalize status maps to array rows
$mapToRows = function(array $map): array {
    // map: label => count
    $rows = [];
    foreach ($map as $label => $count) {
        $rows[] = ['label' => (string)$label, 'count' => (int)$count];
    }
    usort($rows, static fn($a, $b) => ($b['count'] <=> $a['count']));
    return $rows;
};

$monthRows = [];
if (is_array($sessionsByMonth) && $sessionsByMonth) {
    foreach ($sessionsByMonth as $ym => $count) {
        $monthRows[] = ['label' => fmt_month_id((string)$ym), 'count' => (int)$count];
    }
    // sort by label is not reliable; sort by original ym if possible
    // best-effort: keep insertion order if associative already chronological
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Agregat BK</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; margin: 0; padding: 0; }
        .muted { color: #666; }
        .small { font-size: 11px; }
        .h1 { font-size: 18px; margin: 0; }
        .h2 { font-size: 14px; margin: 18px 0 8px; }
        .box { border: 1px solid #ddd; padding: 10px 12px; border-radius: 8px; }
        .mt8 { margin-top: 8px; }
        .mt12 { margin-top: 12px; }
        .mt16 { margin-top: 16px; }
        .mt20 { margin-top: 20px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f3f3f3; font-weight: bold; }

        /* Header layout for PDF stability */
        .header-table td { border: none; padding: 0; }
        .logo { width: 56px; height: 56px; object-fit: contain; border: 1px solid #eee; border-radius: 8px; padding: 4px; }

        .kpi-table td { width: 25%; }
        .kpi-title { font-weight: bold; }
        .kpi-value { font-size: 16px; font-weight: bold; margin-top: 2px; }
        .kpi-sub { margin-top: 2px; }

        .two-col { width: 100%; }
        .two-col td { width: 50%; border: none; padding: 0; vertical-align: top; }
        .padR { padding-right: 8px; }
        .padL { padding-left: 8px; }

        .note { border-left: 4px solid #999; padding: 8px 10px; background: #fafafa; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="box">
    <table class="header-table" style="width:100%;">
        <tr>
            <td style="width:70px; vertical-align:top;">
                <?php if ($schoolLogo !== ''): ?>
                    <img class="logo" src="<?= esc($schoolLogo) ?>" alt="Logo">
                <?php else: ?>
                    <div class="logo center muted" style="line-height:56px;">LOGO</div>
                <?php endif; ?>
            </td>
            <td style="vertical-align:top;">
                <div class="h1">Laporan Agregat BK</div>
                <div class="small muted mt8">
                    <b><?= esc($schoolName) ?></b><br>
                    <?php foreach ($metaLines as $line): ?>
                        <?= esc($line) ?><br>
                    <?php endforeach; ?>
                </div>
            </td>
            <td style="width:260px; vertical-align:top;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr><td style="border:none; padding:0;" class="small muted">Periode</td></tr>
                    <tr><td style="border:none; padding:0;"><b><?= esc($period) ?></b></td></tr>
                    <tr><td style="border:none; padding:0;" class="small muted mt8">Lingkup</td></tr>
                    <tr><td style="border:none; padding:0;"><?= esc($scope) ?></td></tr>
                    <tr><td style="border:none; padding:0;" class="small muted mt8">Dibuat</td></tr>
                    <tr><td style="border:none; padding:0;"><?= esc($generatedAt) ?></td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- KPI -->
<table class="kpi-table mt12">
    <tr>
        <td>
            <div class="box">
                <div class="kpi-title muted">Total Siswa</div>
                <div class="kpi-value"><?= n0($kpi['students_total'] ?? 0) ?></div>
            </div>
        </td>
        <td>
            <div class="box">
                <div class="kpi-title muted">Total Sesi</div>
                <div class="kpi-value"><?= n0($kpi['sessions_total'] ?? 0) ?></div>
                <div class="kpi-sub muted small">Durasi: <?= n0($kpi['sessions_duration_total'] ?? 0) ?> menit</div>
            </div>
        </td>
        <td>
            <div class="box">
                <div class="kpi-title muted">Pelanggaran</div>
                <div class="kpi-value"><?= n0($kpi['violations_total'] ?? 0) ?></div>
                <div class="kpi-sub muted small">
                    Aktif: <?= n0($kpi['violations_active'] ?? 0) ?> • Poin: <?= n0($kpi['violations_points_total'] ?? 0) ?>
                </div>
                <div class="kpi-sub muted small">Pelanggaran Berulang: <?= n0($kpi['repeat_offenders'] ?? 0) ?></div>
                <div class="kpi-sub muted small">Total Sanksi: <?= n0($kpi['sanctions_total'] ?? 0) ?></div>
            </div>
        </td>
        <td>
            <div class="box">
                <div class="kpi-title muted">Asesmen</div>
                <div class="kpi-value"><?= n0($kpi['assessments_completed'] ?? 0) ?>/<?= n0($kpi['assessments_assigned'] ?? 0) ?></div>
                <div class="kpi-sub muted small">Rata-rata: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
            </div>
        </td>
    </tr>
</table>

<!-- SESSIONS -->
<div class="h2">A. Rekap Sesi Konseling</div>

<table class="two-col">
    <tr>
        <td class="padR">
            <div class="box">
                <b>Per Jenis</b>
                <table class="mt8">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th class="right nowrap">Jumlah</th>
                            <th class="right nowrap">Durasi (m)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($sessionsByType)): ?>
                        <?php foreach ($sessionsByType as $r): ?>
                            <tr>
                                <td><?= esc($r['label'] ?? '-') ?></td>
                                <td class="right"><?= n0($r['count'] ?? 0) ?></td>
                                <td class="right"><?= n0($r['duration'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?= $renderEmptyRow(3); ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </td>
        <td class="padL">
            <div class="box">
                <b>Per Guru BK</b>
                <table class="mt8">
                    <thead>
                        <tr>
                            <th>Konselor</th>
                            <th class="right nowrap">Jumlah</th>
                            <th class="right nowrap">Durasi (m)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($sessionsByCounselor)): ?>
                        <?php foreach ($sessionsByCounselor as $r): ?>
                            <tr>
                                <td><?= esc($r['label'] ?? '-') ?></td>
                                <td class="right"><?= n0($r['count'] ?? 0) ?></td>
                                <td class="right"><?= n0($r['duration'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?= $renderEmptyRow(3); ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="mt12 box">
    <b>Status Sesi</b>
    <table class="mt8">
        <thead>
            <tr><th>Status</th><th class="right nowrap">Jumlah</th></tr>
        </thead>
        <tbody>
        <?php
        $rows = $mapToRows(is_array($sessionsByStatus) ? $sessionsByStatus : []);
        ?>
        <?php if ($rows): ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= esc($r['label']) ?></td>
                    <td class="right"><?= n0($r['count']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <?= $renderEmptyRow(2); ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($monthRows): ?>
<div class="mt12 box">
    <b>Tren Sesi per Bulan</b>
    <table class="mt8">
        <thead>
            <tr><th>Bulan</th><th class="right nowrap">Jumlah</th></tr>
        </thead>
        <tbody>
            <?php foreach ($monthRows as $r): ?>
                <tr>
                    <td><?= esc($r['label']) ?></td>
                    <td class="right"><?= n0($r['count']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- VIOLATIONS -->
<div class="h2">B. Rekap Pelanggaran</div>

<table class="two-col">
    <tr>
        <td class="padR">
            <div class="box">
                <b>Per Level</b>
                <table class="mt8">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th class="right nowrap">Jumlah</th>
                            <th class="right nowrap">Total Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($violByLevel)): ?>
                        <?php foreach ($violByLevel as $r): ?>
                            <tr>
                                <td><?= esc($r['label'] ?? '-') ?></td>
                                <td class="right"><?= n0($r['count'] ?? 0) ?></td>
                                <td class="right"><?= n0($r['points'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?= $renderEmptyRow(3); ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </td>
        <td class="padL">
            <div class="box">
                <b>Per Kategori</b>
                <table class="mt8">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="right nowrap">Jumlah</th>
                            <th class="right nowrap">Total Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($violByCategory)): ?>
                        <?php foreach ($violByCategory as $r): ?>
                            <tr>
                                <td><?= esc($r['label'] ?? '-') ?></td>
                                <td class="right"><?= n0($r['count'] ?? 0) ?></td>
                                <td class="right"><?= n0($r['points'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?= $renderEmptyRow(3); ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

<?php if (!empty($violByClass)): ?>
<div class="mt12 box">
    <b>Per Kelas</b>
    <table class="mt8">
        <thead>
            <tr>
                <th>Kelas</th>
                <th class="right nowrap">Jumlah</th>
                <th class="right nowrap">Total Poin</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($violByClass as $r): ?>
                <tr>
                    <td><?= esc($r['label'] ?? '-') ?></td>
                    <td class="right"><?= n0($r['count'] ?? 0) ?></td>
                    <td class="right"><?= n0($r['points'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- SANCTIONS -->
<div class="h2">C. Rekap Sanksi</div>

<table class="two-col">
    <tr>
        <td class="padR">
            <div class="box">
                <b>Per Jenis</b>
                <table class="mt8">
                    <thead>
                        <tr><th>Jenis Sanksi</th><th class="right nowrap">Jumlah</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($sanByType)): ?>
                        <?php foreach ($sanByType as $r): ?>
                            <tr>
                                <td><?= esc($r['label'] ?? '-') ?></td>
                                <td class="right"><?= n0($r['count'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?= $renderEmptyRow(2); ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </td>
        <td class="padL">
            <div class="box">
                <b>Status</b>
                <table class="mt8">
                    <thead>
                        <tr><th>Status</th><th class="right nowrap">Jumlah</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($sanByStatus)): ?>
                        <?php foreach ($sanByStatus as $r): ?>
                            <tr>
                                <td><?= esc($r['label'] ?? '-') ?></td>
                                <td class="right"><?= n0($r['count'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?= $renderEmptyRow(2); ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

<!-- ASSESSMENTS -->
<div class="h2">D. Rekap Asesmen</div>

<div class="box">
    <b>Per Asesmen</b>
    <table class="mt8">
        <thead>
            <tr>
                <th>Asesmen</th>
                <th class="right nowrap">Assigned</th>
                <th class="right nowrap">Completed</th>
                <th class="right nowrap">Avg (%)</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($assByAssessment)): ?>
            <?php foreach ($assByAssessment as $r): ?>
                <tr>
                    <td><?= esc($r['label'] ?? '-') ?></td>
                    <td class="right"><?= n0($r['assigned'] ?? 0) ?></td>
                    <td class="right"><?= n0($r['completed'] ?? 0) ?></td>
                    <td class="right"><?= esc($r['avg_percentage'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <?= $renderEmptyRow(4); ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt12 box">
    <b>Status Asesmen</b>
    <table class="mt8">
        <thead>
            <tr><th>Status</th><th class="right nowrap">Jumlah</th></tr>
        </thead>
        <tbody>
        <?php
        $rows = $mapToRows(is_array($assByStatus) ? $assByStatus : []);
        ?>
        <?php if ($rows): ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= esc($r['label']) ?></td>
                    <td class="right"><?= n0($r['count']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <?= $renderEmptyRow(2); ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt16 muted small">
    Dicetak dari modul Koordinator BK • Sistem Informasi BK (SIB-K).
</div>

</body>
</html>
