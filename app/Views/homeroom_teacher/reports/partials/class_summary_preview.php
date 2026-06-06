<?php
$data   = $data ?? [];
$school = $data['school'] ?? [];
$period = $data['period']['label'] ?? '-';
$scope  = $data['scope']['label'] ?? 'Kelas';
$kpi    = $data['kpi'] ?? [];

if (!function_exists('ht_report_n0')) {
    function ht_report_n0($v): string
    {
        return number_format((float)($v ?? 0), 0, ',', '.');
    }
}
?>

<div class="mb-3">
  <h5 class="mb-1">Laporan Kelas (Wali Kelas)</h5>
  <div class="text-muted small">
    <?= esc($school['name'] ?? '-') ?> - Periode: <b><?= esc($period) ?></b> - Kelas: <?= esc($scope) ?><br>
    Dibuat: <?= esc($data['generated_at'] ?? '-') ?>
  </div>
</div>

<?php if (empty($data)): ?>
  <div class="alert alert-warning mb-0">Data laporan tidak tersedia.</div>
  <?php return; ?>
<?php endif; ?>

<div class="row g-3">
  <div class="col-md-4">
    <div class="p-3 border rounded">
      <div class="text-muted">Total Siswa</div>
      <div class="h4 mb-0"><?= esc(ht_report_n0($kpi['students_total'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-3 border rounded">
      <div class="text-muted">Total Sesi</div>
      <div class="h4 mb-0"><?= esc(ht_report_n0($kpi['sessions_total'] ?? 0)) ?></div>
      <div class="small text-muted">Durasi: <?= esc(ht_report_n0($kpi['sessions_duration_total'] ?? 0)) ?> menit</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-3 border rounded">
      <div class="text-muted">Asesmen Selesai</div>
      <div class="h4 mb-0">
        <?= esc(ht_report_n0($kpi['assessments_completed'] ?? 0)) ?>/<?= esc(ht_report_n0($kpi['assessments_assigned'] ?? 0)) ?>
      </div>
      <div class="small text-muted">Rata-rata: <?= esc($kpi['assessments_avg_percentage'] ?? 0) ?>%</div>
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

<div class="mt-3 text-muted small">
  Catatan: Ringkasan ini tidak menampilkan isi/catatan sesi konseling untuk menjaga kerahasiaan.
</div>
