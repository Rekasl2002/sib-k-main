<?php
/**
 * File Path: app/Views/homeroom_teacher/reports/partials/class_summary_preview.php
 *
 * Homeroom Teacher • Class Summary Preview (AJAX partial)
 * - Konsisten dengan preview Koordinator (KPI + tabel rekap)
 * - Aman dari bentrok function (pakai nama unik + function_exists)
 * - Toleran terhadap variasi bentuk data status asesmen
 */

$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Kelas';
$kpi    = $data['kpi'] ?? [];

// Hindari bentrok nama function antar view/partial
if (!function_exists('ht_report_n0')) {
    function ht_report_n0($v): string
    {
        return number_format((float)($v ?? 0), 0, ',', '.');
    }
}

if (!function_exists('ht_assessment_status_label')) {
    function ht_assessment_status_label($status): string
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

// Normalisasi ringkas status asesmen agar fleksibel (map atau list)
$assStatusRaw = $data['assessments']['statusCounts'] ?? $data['assessments']['byStatus'] ?? [];
$assStatusPairs = [];

// Bentuk 1: associative map: [0=>10, 1=>5, ...] atau ['Assigned'=>10, ...]
if (is_array($assStatusRaw) && $assStatusRaw) {
    $keys = array_keys($assStatusRaw);
    $isAssoc = $keys !== range(0, count($keys) - 1);

    if ($isAssoc) {
        foreach ($assStatusRaw as $k => $v) {
            $assStatusPairs[] = ['status' => $k, 'count' => $v];
        }
    } else {
        // Bentuk 2: list of arrays: [['status'=>0,'count'=>10], ...] atau [['label'=>'Assigned','count'=>10], ...]
        foreach ($assStatusRaw as $row) {
            if (!is_array($row)) continue;
            $status = $row['status'] ?? $row['label'] ?? $row['key'] ?? null;
            $count  = $row['count'] ?? $row['total'] ?? $row['value'] ?? null;
            if ($status === null || $count === null) continue;
            $assStatusPairs[] = ['status' => $status, 'count' => $count];
        }
    }
}
?>

<div class="mb-3">
  <h5 class="mb-1">Laporan Kelas (Wali Kelas)</h5>
  <div class="text-muted small">
    <?= esc($school['name'] ?? '-') ?> • Periode: <b><?= esc($period) ?></b> • Kelas: <?= esc($scope) ?><br>
    Dibuat: <?= esc($data['generated_at'] ?? '-') ?>
  </div>
</div>

<?php if (empty($data)): ?>
  <div class="alert alert-warning mb-0">
    Data laporan tidak tersedia.
  </div>
  <?php return; ?>
<?php endif; ?>

<div class="row g-3">
  <div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Total Siswa</div>
      <div class="h4 mb-0"><?= esc(ht_report_n0($kpi['students_total'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Total Sesi</div>
      <div class="h4 mb-0"><?= esc(ht_report_n0($kpi['sessions_total'] ?? 0)) ?></div>
      <div class="small text-muted">Durasi: <?= esc(ht_report_n0($kpi['sessions_duration_total'] ?? 0)) ?> menit</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Pelanggaran</div>
      <div class="h4 mb-0"><?= esc(ht_report_n0($kpi['violations_total'] ?? 0)) ?></div>
      <div class="small text-muted">
        Aktif: <?= esc(ht_report_n0($kpi['violations_active'] ?? 0)) ?>
        • Poin: <?= esc(ht_report_n0($kpi['violations_points_total'] ?? 0)) ?>
      </div>
    </div>
  </div>
  <!--<div class="col-md-3">
    <div class="p-3 border rounded">
      <div class="text-muted">Asesmen</div>
      <div class="h4 mb-0">
        <?= esc(ht_report_n0($kpi['assessments_completed'] ?? 0)) ?>/<?= esc(ht_report_n0($kpi['assessments_assigned'] ?? 0)) ?>
      </div>
      <div class="small text-muted">Avg: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
    </div>
  </div>-->
</div>

<!--<?php if (!empty($assStatusPairs)): ?>
  <div class="mt-3">
    <div class="small text-muted mb-1">Status asesmen:</div>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($assStatusPairs as $p): ?>
        <span class="badge bg-light text-dark border">
          <?= esc(ht_assessment_status_label($p['status'])) ?>: <?= esc(ht_report_n0($p['count'])) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>-->

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
              <td class="text-end"><?= esc(ht_report_n0($r['count'] ?? 0)) ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['duration'] ?? 0)) ?></td>
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
          <tr><th>Guru BK</th><th class="text-end">Jumlah</th><th class="text-end">Durasi (m)</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['sessions']['byCounselor'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['count'] ?? 0)) ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['duration'] ?? 0)) ?></td>
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
              <td class="text-end"><?= esc(ht_report_n0($r['count'] ?? 0)) ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['points'] ?? 0)) ?></td>
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
              <td class="text-end"><?= esc(ht_report_n0($r['count'] ?? 0)) ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['points'] ?? 0)) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['violations']['byCategory'])): ?>
            <tr><td colspan="3" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<hr class="my-4">

<h6 class="mb-2">Rekap Sanksi <!-- & Asesmen--></h6>
<div class="row g-3">
  <div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Jenis Sanksi</th><th class="text-end">Jumlah</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['sanctions']['byType'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['count'] ?? 0)) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['sanctions']['byType'])): ?>
            <tr><td colspan="2" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!--<div class="col-md-6">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Asesmen</th><th class="text-end">Ditugaskan</th><th class="text-end">Selesai</th><th class="text-end">Rata-Rata (%)</th></tr>
        </thead>
        <tbody>
          <?php foreach (($data['assessments']['byAssessment'] ?? []) as $r): ?>
            <tr>
              <td><?= esc($r['label'] ?? '-') ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['assigned'] ?? 0)) ?></td>
              <td class="text-end"><?= esc(ht_report_n0($r['completed'] ?? 0)) ?></td>
              <td class="text-end"><?= esc($r['avg_percentage'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data['assessments']['byAssessment'])): ?>
            <tr><td colspan="4" class="text-muted">(tidak ada data)</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>-->
</div>

<div class="mt-3 text-muted small">
  Catatan: Ringkasan ini tidak menampilkan isi/catatan sesi konseling untuk menjaga kerahasiaan.
</div>
