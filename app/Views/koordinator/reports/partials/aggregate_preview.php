<?php
$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Semua Data';
$kpi    = $data['kpi'] ?? [];

$n0 = static fn($v) => number_format((float)($v ?? 0), 0, ',', '.');

$assessmentStatusLabel = static function ($status): string {
    if ($status === null) return 'Tidak Diketahui';

    if (is_numeric($status)) {
        return match ((int)$status) {
            0 => 'Belum Mulai',
            1 => 'Sedang Dikerjakan',
            2 => 'Selesai',
            3 => 'Dinilai',
            default => 'Tidak Diketahui',
        };
    }

    $map = [
        'assigned'     => 'Belum Mulai',
        'not started'  => 'Belum Mulai',
        'not_started'  => 'Belum Mulai',
        'in progress'  => 'Sedang Dikerjakan',
        'in_progress'  => 'Sedang Dikerjakan',
        'started'      => 'Sedang Dikerjakan',
        'completed'    => 'Selesai',
        'done'         => 'Selesai',
        'graded'       => 'Dinilai',
    ];

    $key = strtolower(trim((string)$status));
    return $map[$key] ?? (string)$status;
};

$renderStatusBadges = static function ($items, callable $labelFn) use ($n0) {
    if (!$items) {
        echo '<span class="text-dark">(tidak ada data)</span>';
        return;
    }

    foreach ($items as $key => $value) {
        if (is_array($value)) {
            $label = $value['label'] ?? $value['status'] ?? $key;
            $count = $value['count'] ?? $value['total'] ?? 0;
        } else {
            $label = $key;
            $count = $value;
        }

        echo '<span class="badge bg-light text-dark border me-1 mb-1">'
            . esc($labelFn($label)) . ': ' . $n0($count)
            . '</span>';
    }
};
?>

<div class="mb-3">
  <h5 class="mb-1">Laporan Rekap BK</h5>
  <div class="text-dark small">
    <?= esc($school['name'] ?? '-') ?> - Periode: <b><?= esc($period) ?></b> - Lingkup: <?= esc($scope) ?><br>
    Dibuat: <?= esc($data['generated_at'] ?? '-') ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="p-3 border rounded">
      <div class="text-dark">Total Siswa</div>
      <div class="h4 mb-0"><?= $n0($kpi['students_total'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-3 border rounded">
      <div class="text-dark">Total Catatan Konseling</div>
      <div class="h4 mb-0"><?= $n0($kpi['sessions_total'] ?? 0) ?></div>
      <div class="small text-dark">Durasi: <?= $n0($kpi['sessions_duration_total'] ?? 0) ?> menit</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-3 border rounded">
      <div class="text-dark">Asesmen Selesai</div>
      <div class="h4 mb-0"><?= $n0($kpi['assessments_completed'] ?? 0) ?>/<?= $n0($kpi['assessments_assigned'] ?? 0) ?></div>
      <div class="small text-dark">Rata-rata: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
    </div>
  </div>
</div>

<hr class="my-4">

<h6 class="mb-2">Rekap Catatan Konseling</h6>
<div class="row g-3">
  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Jenis</th><th class="text-end">Jumlah</th><th class="text-end">Durasi (m)</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['sessions']['byType'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= $n0($r['count'] ?? 0) ?></td>
              <td class="text-end"><?= $n0($r['duration'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['sessions']['byType'])): ?>
            <tr><td colspan="3" class="text-dark">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Guru BK</th><th class="text-end">Jumlah</th><th class="text-end">Durasi (m)</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['sessions']['byCounselor'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= $n0($r['count'] ?? 0) ?></td>
              <td class="text-end"><?= $n0($r['duration'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['sessions']['byCounselor'])): ?>
            <tr><td colspan="3" class="text-dark">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<hr class="my-4">

<h6 class="mb-2">Rekap Asesmen</h6>
<div class="table-responsive">
  <table class="table table-sm table-bordered mb-2">
    <thead class="table-light">
      <tr><th>Asesmen</th><th class="text-end">Ditugaskan</th><th class="text-end">Selesai</th><th class="text-end">Rata-rata (%)</th></tr>
    </thead>
    <tbody>
      <?php foreach (($data['assessments']['byAssessment'] ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['label'] ?? '-') ?></td>
          <td class="text-end"><?= $n0($r['assigned'] ?? 0) ?></td>
          <td class="text-end"><?= $n0($r['completed'] ?? 0) ?></td>
          <td class="text-end"><?= esc($r['avg_percentage'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($data['assessments']['byAssessment'])): ?>
        <tr><td colspan="4" class="text-dark">(tidak ada data)</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="small text-dark mb-1">Status asesmen:</div>
<div>
  <?php $renderStatusBadges(($data['assessments']['byStatus'] ?? []), $assessmentStatusLabel); ?>
</div>
