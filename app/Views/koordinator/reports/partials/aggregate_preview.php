<?php
$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Semua Data';
$kpi    = $data['kpi'] ?? [];

// aman dari redeclare
$n0 = static fn($v) => number_format((float)($v ?? 0), 0, ',', '.');

/**
 * Normalisasi label status asesmen:
 * - kalau numeric 0/1/2/3 -> label ID
 * - kalau string (Assigned/Completed/Graded/...) -> label ID
 */
$assessmentStatusLabel = static function ($status): string {
    if ($status === null) return 'Unknown';

    // numeric code
    if (is_numeric($status)) {
        $i = (int)$status;
        return match ($i) {
            0 => 'Belum Mulai',
            1 => 'Sedang Dikerjakan',
            2 => 'Selesai',
            3 => 'Dinilai',
            default => 'Unknown (' . $i . ')',
        };
    }

    $s = trim((string)$status);
    if ($s === '') return 'Unknown';

    $key = strtolower($s);

    $map = [
        'assigned'      => 'Belum Mulai',
        'not started'   => 'Belum Mulai',
        'not_started'   => 'Belum Mulai',

        'in progress'   => 'Sedang Dikerjakan',
        'in_progress'   => 'Sedang Dikerjakan',
        'started'       => 'Sedang Dikerjakan',

        'completed'     => 'Selesai',
        'done'          => 'Selesai',

        'graded'        => 'Dinilai',
    ];

    return $map[$key] ?? $s;
};

/**
 * Render badge status dari data yang bisa berbentuk:
 * A) list: [ ['label'=>'Selesai','count'=>2], ... ]
 * B) map : [ 'Selesai' => 2, 'Assigned' => 1, ... ]
 */
$renderStatusBadges = static function ($items, callable $labelFn) use ($n0) {
    $items = $items ?? [];

    // jika kosong
    if (!$items) {
        echo '<span class="text-muted">(tidak ada data)</span>';
        return;
    }

    $list = [];

    // format list?
    $isList = isset($items[0]) && is_array($items[0]) && array_key_exists('label', $items[0]);

    if ($isList) {
        foreach ($items as $row) {
            if (!is_array($row)) continue;
            $label = $row['label'] ?? null;
            $count = $row['count'] ?? 0;

            // khusus label status asesmen, labelFn bisa mengubah
            $label = $labelFn($label);

            $list[] = [
                'label' => $label,
                'count' => (int)$count,
            ];
        }
    } else {
        // format map
        foreach ($items as $k => $v) {
            $label = $labelFn($k);
            $list[] = [
                'label' => $label,
                'count' => (int)$v,
            ];
        }
    }

    // tampilkan
    foreach ($list as $row) {
        $label = (string)($row['label'] ?? 'Unknown');
        $count = (int)($row['count'] ?? 0);

        echo '<span class="badge bg-light text-dark border me-1 mb-1">'
            . esc($label) . ': ' . $n0($count)
            . '</span>';
    }
};
?>

<div class="mb-3">
  <h5 class="mb-1">Laporan Agregat BK</h5>
  <div class="text-muted small">
    <?= esc($school['name'] ?? '-') ?> • Periode: <b><?= esc($period) ?></b> • Lingkup: <?= esc($scope) ?><br>
    Dibuat: <?= esc($data['generated_at'] ?? '-') ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Total Siswa</div>
      <div class="h4 mb-0"><?= $n0($kpi['students_total'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Total Sesi</div>
      <div class="h4 mb-0"><?= $n0($kpi['sessions_total'] ?? 0) ?></div>
      <div class="small text-muted">Durasi: <?= $n0($kpi['sessions_duration_total'] ?? 0) ?> menit</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Pelanggaran</div>
      <div class="h4 mb-0"><?= $n0($kpi['violations_total'] ?? 0) ?></div>
      <div class="small text-muted">
        Aktif: <?= $n0($kpi['violations_active'] ?? 0) ?> • Poin: <?= $n0($kpi['violations_points_total'] ?? 0) ?>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Asesmen</div>
      <div class="h4 mb-0"><?= $n0($kpi['assessments_completed'] ?? 0) ?>/<?= $n0($kpi['assessments_assigned'] ?? 0) ?></div>
      <div class="small text-muted">Avg: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
    </div>
  </div>
</div>

<hr class="my-4">

<h6 class="mb-2">Rekap Sesi Konseling</h6>
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
            <tr><td colspan="3" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Konselor</th><th class="text-end">Jumlah</th><th class="text-end">Durasi (m)</th></tr>
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
            <tr><td colspan="3" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<hr class="my-4">

<h6 class="mb-2">Rekap Kasus/Pelanggaran</h6>
<div class="row g-3">
  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Level</th><th class="text-end">Jumlah</th><th class="text-end">Total Poin</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['violations']['byLevel'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= $n0($r['count'] ?? 0) ?></td>
              <td class="text-end"><?= $n0($r['points'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['violations']['byLevel'])): ?>
            <tr><td colspan="3" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Kategori</th><th class="text-end">Jumlah</th><th class="text-end">Total Poin</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['violations']['byCategory'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= $n0($r['count'] ?? 0) ?></td>
              <td class="text-end"><?= $n0($r['points'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['violations']['byCategory'])): ?>
            <tr><td colspan="3" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-12">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Kelas</th><th class="text-end">Jumlah</th><th class="text-end">Total Poin</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['violations']['byClass'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= $n0($r['count'] ?? 0) ?></td>
              <td class="text-end"><?= $n0($r['points'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['violations']['byClass'])): ?>
            <tr><td colspan="3" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<hr class="my-4">

<h6 class="mb-2">Rekap Sanksi & Asesmen</h6>
<div class="row g-3">
  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-2">
        <thead class="table-light">
          <tr><th>Jenis Sanksi</th><th class="text-end">Jumlah</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['sanctions']['byType'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= $n0($r['count'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['sanctions']['byType'])): ?>
            <tr><td colspan="2" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="small text-muted mb-1">Status sanksi:</div>
      <div>
        <?php $renderStatusBadges(($data['sanctions']['byStatus'] ?? []), static fn($x) => (string)$x); ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-2">
        <thead class="table-light">
          <tr><th>Asesmen</th><th class="text-end">Assigned</th><th class="text-end">Completed</th><th class="text-end">Avg (%)</th></tr>
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
            <tr><td colspan="4" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="small text-muted mb-1">Status asesmen:</div>
      <div>
        <?php $renderStatusBadges(($data['assessments']['byStatus'] ?? []), $assessmentStatusLabel); ?>
      </div>
    </div>
  </div>
</div>
