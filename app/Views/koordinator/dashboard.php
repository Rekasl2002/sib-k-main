<?php
// app/Views/koordinator/dashboard.php
// Dashboard Koordinator BK — tata letak patokan Admin + NNG.
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$featureCounts    = is_array($featureCounts ?? null) ? $featureCounts : [];
$monthlyTrend     = is_array($monthlyTrend ?? null) ? $monthlyTrend : ['labels' => [], 'data' => []];
$topCounselors    = $topCounselors ?? [];
$recentActivities = $recentActivities ?? [];

if (! function_exists('dash_time')) {
    function dash_time($v): string
    {
        if (empty($v)) return '-';
        $ts = strtotime((string) $v);
        return $ts ? date('d M Y H:i', $ts) : (string) $v;
    }
}
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">Dashboard Koordinator BK</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?= $this->include('partials/dashboard/welcome') ?>
<?= $this->include('partials/dashboard/stat_cards') ?>

<!-- Zona tengah: chart -->
<div class="row">
  <div class="col-xl-7 d-flex">
    <div class="card flex-fill">
      <div class="card-body">
        <h4 class="card-title mb-4"><i class="mdi mdi-chart-bar me-2"></i>Jumlah Data per Fitur BK</h4>
        <div style="height: 220px;">
          <canvas id="featureBarChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-5 d-flex">
    <div class="card flex-fill">
      <div class="card-body">
        <h4 class="card-title mb-4"><i class="mdi mdi-chart-line me-2"></i>Catatan BK Dibuat (6 Bulan)</h4>
        <div style="height: 220px;">
          <canvas id="trendLineChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Zona bawah: tabel detail -->
<div class="row">
  <div class="col-xl-6">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3"><i class="mdi mdi-account-tie-outline me-2"></i>Guru BK dengan Catatan Terbanyak</h4>
        <?php if (! empty($topCounselors)): ?>
          <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Guru BK</th><th class="text-end">Jumlah Catatan</th></tr>
              </thead>
              <tbody>
                <?php foreach ($topCounselors as $i => $row): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($row['name'] ?? '-') ?></td>
                    <td class="text-end"><span class="badge bg-primary font-size-12"><?= number_format((int) ($row['total'] ?? 0)) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-dark mb-0">Belum ada catatan kegiatan.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3"><i class="mdi mdi-history me-2"></i>Aktivitas Terbaru</h4>
        <?php if (! empty($recentActivities)): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($recentActivities as $act): ?>
              <div class="list-group-item px-0 d-flex align-items-start">
                <div class="avatar-xs me-3 mt-1">
                  <span class="avatar-title rounded-circle bg-soft-<?= esc($act['color'] ?? 'primary') ?> text-<?= esc($act['color'] ?? 'primary') ?>">
                    <i class="mdi <?= esc($act['icon'] ?? 'mdi-circle-medium') ?>"></i>
                  </span>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-1 font-size-14"><?= esc($act['title'] ?? '-') ?></h6>
                  <small class="text-dark">
                    <span class="badge bg-light text-dark border me-1"><?= esc($act['type'] ?? '-') ?></span>
                    <?= dash_time($act['time'] ?? null) ?>
                  </small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-dark mb-0">Belum ada aktivitas terbaru.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  (function () {
    const palette = ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#7b6cb6', '#02a499', '#ec4561', '#564ab1'];

    const barCtx = document.getElementById('featureBarChart');
    if (barCtx) {
      new Chart(barCtx, {
        type: 'bar',
        data: {
          labels: <?= json_encode(array_keys($featureCounts)) ?>,
          datasets: [{
            label: 'Jumlah Data',
            data: <?= json_encode(array_values($featureCounts)) ?>,
            backgroundColor: palette,
            borderRadius: 4
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    }

    const lineCtx = document.getElementById('trendLineChart');
    if (lineCtx) {
      new Chart(lineCtx, {
        type: 'line',
        data: {
          labels: <?= json_encode($monthlyTrend['labels'] ?? []) ?>,
          datasets: [{
            label: 'Catatan BK',
            data: <?= json_encode($monthlyTrend['data'] ?? []) ?>,
            borderColor: '#556ee6',
            backgroundColor: 'rgba(85,110,230,0.1)',
            borderWidth: 2, fill: true, tension: 0.4
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    }
  })();
</script>
<?= $this->endSection() ?>
