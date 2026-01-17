<?php
/**
 * File Path: app/Views/homeroom_teacher/reports/partials/class_summary_pdf.php
 *
 * Homeroom Teacher (Wali Kelas) • Class Summary PDF
 * - Konsisten gaya dengan Koordinator aggregate_pdf
 * - Scope: agregat per kelas (tanpa menampilkan catatan sesi rahasia)
 */

$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Kelas';
$kpi    = $data['kpi'] ?? [];

// Helper angka: aman untuk PDF
if (!function_exists('hr_n0')) {
    function hr_n0($v): string
    {
        return number_format((float)($v ?? 0), 0, ',', '.');
    }
}

// Mapping status asesmen agar tidak mentah 0/1/2 (opsional, dipakai jika ada byStatus/statusCounts)
if (!function_exists('hr_assessment_status_label')) {
    function hr_assessment_status_label($status): string
    {
        if ($status === null || $status === '') return 'Unknown';

        if (is_numeric($status)) {
            return match ((int)$status) {
                0 => 'Belum Mulai',
                1 => 'Sedang Dikerjakan',
                2 => 'Selesai',
                3 => 'Dinilai',
                default => 'Unknown (' . (int)$status . ')',
            };
        }

        $s = strtolower(trim((string)$status));
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
        return $map[$s] ?? (string)$status;
    }
}

// Optional: ringkasan status asesmen bila tersedia dari service
$assStatus = $data['assessments']['statusCounts'] ?? ($data['assessments']['byStatus'] ?? []);
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
    .kpi td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
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
      <?= esc($school['name'] ?? '-') ?> • Periode: <b><?= esc($period) ?></b> • Kelas: <?= esc($scope) ?><br>
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
        <b>Pelanggaran</b>
        <div class="right"><?= hr_n0($kpi['violations_total'] ?? 0) ?></div>
        <div class="muted right">Aktif: <?= hr_n0($kpi['violations_active'] ?? 0) ?> • Poin: <?= hr_n0($kpi['violations_points_total'] ?? 0) ?></div>
      </td>
      <!--<td>
        <b>Asesmen</b>
        <div class="right"><?= hr_n0($kpi['assessments_completed'] ?? 0) ?>/<?= hr_n0($kpi['assessments_assigned'] ?? 0) ?></div>
        <div class="muted right">Rata-Rata: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
      </td>-->
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

  <h3>Rekap Pelanggaran (per Level)</h3>
  <table>
    <thead><tr><th>Level</th><th class="right">Jumlah</th><th class="right">Total Poin</th></tr></thead>
    <tbody>
      <?php foreach (($data['violations']['byLevel'] ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['label'] ?? '-') ?></td>
          <td class="right"><?= hr_n0($r['count'] ?? 0) ?></td>
          <td class="right"><?= hr_n0($r['points'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($data['violations']['byLevel'])): ?>
        <tr><td colspan="3" class="muted">(tidak ada data)</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Rekap Pelanggaran (per Kategori)</h3>
  <table>
    <thead><tr><th>Kategori</th><th class="right">Jumlah</th><th class="right">Total Poin</th></tr></thead>
    <tbody>
      <?php foreach (($data['violations']['byCategory'] ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['label'] ?? '-') ?></td>
          <td class="right"><?= hr_n0($r['count'] ?? 0) ?></td>
          <td class="right"><?= hr_n0($r['points'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($data['violations']['byCategory'])): ?>
        <tr><td colspan="3" class="muted">(tidak ada data)</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Rekap Sanksi (per Jenis)</h3>
  <table>
    <thead><tr><th>Jenis Sanksi</th><th class="right">Jumlah</th></tr></thead>
    <tbody>
      <?php foreach (($data['sanctions']['byType'] ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['label'] ?? '-') ?></td>
          <td class="right"><?= hr_n0($r['count'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($data['sanctions']['byType'])): ?>
        <tr><td colspan="2" class="muted">(tidak ada data)</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!--<h3>Rekap Asesmen (per Asesmen)</h3>
  <table>
    <thead><tr><th>Asesmen</th><th class="right">Ditugaskan</th><th class="right">Selesai</th><th class="right">Rata-Rata (%)</th></tr></thead>
    <tbody>
      <?php foreach (($data['assessments']['byAssessment'] ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['label'] ?? '-') ?></td>
          <td class="right"><?= hr_n0($r['assigned'] ?? 0) ?></td>
          <td class="right"><?= hr_n0($r['completed'] ?? 0) ?></td>
          <td class="right"><?= esc($r['avg_percentage'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($data['assessments']['byAssessment'])): ?>
        <tr><td colspan="4" class="muted">(tidak ada data)</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if (is_array($assStatus) && !empty($assStatus)): ?>
    <h3>Status Asesmen</h3>
    <table>
      <thead><tr><th>Status</th><th class="right">Jumlah</th></tr></thead>
      <tbody>
        <?php foreach ($assStatus as $k => $v): ?>
          <tr>
            <td><?= esc(hr_assessment_status_label($k)) ?></td>
            <td class="right"><?= hr_n0($v) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>-->
</body>
</html>
