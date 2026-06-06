<?php
$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Kelas';
$kpi    = $data['kpi'] ?? [];

if (!function_exists('hr_n0')) {
    function hr_n0($v): string
    {
        return number_format((float)($v ?? 0), 0, ',', '.');
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Laporan Kelas (Wali Kelas)</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
    .muted { color: #666; }
    .h1 { font-size: 18px; margin: 0; }
    .meta { margin-top: 4px; font-size: 11px; }
    .box { border: 1px solid #ddd; padding: 8px; border-radius: 6px; }
    .kpi { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .kpi td { border: 1px solid #ddd; padding: 6px; vertical-align: top; width: 33.33%; }
    h3 { font-size: 13px; margin: 14px 0 6px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
    th { background: #f3f3f3; }
    .right { text-align: right; }
  </style>
</head>
<body>

  <div class="box">
    <div class="h1">Laporan Kelas (Wali Kelas)</div>
    <div class="meta muted">
      <?= esc($school['name'] ?? '-') ?> - Periode: <b><?= esc($period) ?></b> - Kelas: <?= esc($scope) ?><br>
      Dibuat: <?= esc($data['generated_at'] ?? '-') ?>
    </div>
  </div>

  <table class="kpi">
    <tr>
      <td>
        <b>Total Siswa</b>
        <div class="right"><?= hr_n0($kpi['students_total'] ?? 0) ?></div>
      </td>
      <td>
        <b>Total Sesi</b>
        <div class="right"><?= hr_n0($kpi['sessions_total'] ?? 0) ?></div>
        <div class="muted right">Durasi: <?= hr_n0($kpi['sessions_duration_total'] ?? 0) ?> m</div>
      </td>
      <td>
        <b>Asesmen Selesai</b>
        <div class="right"><?= hr_n0($kpi['assessments_completed'] ?? 0) ?>/<?= hr_n0($kpi['assessments_assigned'] ?? 0) ?></div>
        <div class="muted right">Rata-rata: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
      </td>
    </tr>
  </table>

  <h3>Rekap Sesi Konseling (per Jenis)</h3>
  <table>
    <thead><tr><th>Jenis</th><th class="right">Jumlah</th><th class="right">Durasi (m)</th></tr></thead>
    <tbody>
      <?php foreach (($data['sessions']['byType'] ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['label'] ?? '-') ?></td>
          <td class="right"><?= hr_n0($r['count'] ?? 0) ?></td>
          <td class="right"><?= hr_n0($r['duration'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($data['sessions']['byType'])): ?>
        <tr><td colspan="3" class="muted">(tidak ada data)</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Rekap Sesi Konseling (per Guru BK)</h3>
  <table>
    <thead><tr><th>Guru BK</th><th class="right">Jumlah</th><th class="right">Durasi (m)</th></tr></thead>
    <tbody>
      <?php foreach (($data['sessions']['byCounselor'] ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['label'] ?? '-') ?></td>
          <td class="right"><?= hr_n0($r['count'] ?? 0) ?></td>
          <td class="right"><?= hr_n0($r['duration'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($data['sessions']['byCounselor'])): ?>
        <tr><td colspan="3" class="muted">(tidak ada data)</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
