<?php
$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Semua Data';
$kpi    = $data['kpi'] ?? [];

$sessionsByType      = $data['sessions']['byType'] ?? [];
$sessionsByCounselor = $data['sessions']['byCounselor'] ?? [];
$assByAssessment     = $data['assessments']['byAssessment'] ?? [];
$generatedAt         = (string)($data['generated_at'] ?? date('Y-m-d H:i:s'));

if (!function_exists('n0')) {
    function n0($v): string
    {
        return number_format((float)($v ?? 0), 0, ',', '.');
    }
}

$renderEmptyRow = function(int $colspan, string $text = '(tidak ada data)') {
    return '<tr><td colspan="'.(int)$colspan.'" class="muted">'.esc($text).'</td></tr>';
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Rekap BK</title>
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
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f3f3f3; font-weight: bold; }
        .header-table td { border: none; padding: 0; }
        .kpi-table td { width: 33.33%; }
        .kpi-title { font-weight: bold; }
        .kpi-value { font-size: 16px; font-weight: bold; margin-top: 2px; }
        .two-col { width: 100%; }
        .two-col td { width: 50%; border: none; padding: 0; vertical-align: top; }
        .padR { padding-right: 8px; }
        .padL { padding-left: 8px; }
    </style>
</head>
<body>

<div class="box">
    <table class="header-table" style="width:100%;">
        <tr>
            <td style="vertical-align:top;">
                <div class="h1">Laporan Rekap BK</div>
                <div class="small muted mt8">
                    <b><?= esc($school['name'] ?? '-') ?></b><br>
                    Periode: <?= esc($period) ?><br>
                    Lingkup: <?= esc($scope) ?><br>
                    Dibuat: <?= esc($generatedAt) ?>
                </div>
            </td>
        </tr>
    </table>
</div>

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
                <div class="kpi-title muted">Total Catatan Konseling</div>
                <div class="kpi-value"><?= n0($kpi['sessions_total'] ?? 0) ?></div>
                <div class="muted small">Durasi: <?= n0($kpi['sessions_duration_total'] ?? 0) ?> menit</div>
            </div>
        </td>
        <td>
            <div class="box">
                <div class="kpi-title muted">Asesmen</div>
                <div class="kpi-value"><?= n0($kpi['assessments_completed'] ?? 0) ?>/<?= n0($kpi['assessments_assigned'] ?? 0) ?></div>
                <div class="muted small">Rata-rata: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
            </div>
        </td>
    </tr>
</table>

<div class="h2">A. Rekap Catatan Konseling</div>
<table class="two-col">
    <tr>
        <td class="padR">
            <div class="box">
                <b>Per Jenis</b>
                <table class="mt8">
                    <thead>
                        <tr><th>Jenis</th><th class="right nowrap">Jumlah</th><th class="right nowrap">Durasi (m)</th></tr>
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
                        <tr><th>Guru BK</th><th class="right nowrap">Jumlah</th><th class="right nowrap">Durasi (m)</th></tr>
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

<div class="h2">B. Rekap Asesmen</div>
<div class="box">
    <table>
        <thead>
            <tr><th>Asesmen</th><th class="right nowrap">Ditugaskan</th><th class="right nowrap">Selesai</th><th class="right nowrap">Rata-rata (%)</th></tr>
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

<div class="mt16 muted small">
    Dicetak dari modul Koordinator BK - Sistem Informasi BK (SIB-K).
</div>

</body>
</html>
