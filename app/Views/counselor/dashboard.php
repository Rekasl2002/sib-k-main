<?php
// app/Views/counselor/dashboard.php
// Dashboard Guru BK — tata letak patokan Admin + NNG.
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$featureCounts    = is_array($featureCounts ?? null) ? $featureCounts : [];
$monthlyTrend     = is_array($monthlyTrend ?? null) ? $monthlyTrend : ['labels' => [], 'data' => []];
$upcoming         = $upcoming ?? [];
$recentActivities = $recentActivities ?? [];

if (! function_exists('dash_time')) {
    function dash_time($v): string
    {
        if (empty($v)) return '-';
        $ts = strtotime((string) $v);
        return $ts ? date('d M Y H:i', $ts) : (string) $v;
    }
}
if (! function_exists('dash_date')) {
    function dash_date($v): string
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
      <h4 class="mb-0">Dashboard Guru BK</h4>
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
        <h4 class="card-title mb-4"><i class="mdi mdi-chart-bar me-2"></i>Jumlah Data per Fitur BK (Lingkup Saya)</h4>
        <div style="height: 260px;">
          <canvas id="featureBarChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-5 d-flex">
    <div class="card flex-fill">
      <div class="card-body">
        <h4 class="card-title mb-4"><i class="mdi mdi-chart-line me-2"></i>Catatan BK Dibuat (6 Bulan)</h4>
        <div style="height: 260px;">
          <canvas id="trendLineChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Zona bawah: tabel detail -->
<div class="row">
  <div class="col-xl-7">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <h4 class="card-title mb-0 flex-grow-1"><i class="mdi mdi-calendar-check-outline me-2"></i>Jadwal/Kegiatan BK Mendatang</h4>
          <a href="<?= base_url('counselor/counseling') ?>" class="btn btn-sm btn-primary">Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i></a>
        </div>
        <?php if (! empty($upcoming)): ?>
          <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
              <thead class="table-light">
                <tr><th>Jenis</th><th>Judul</th><th>Sasaran</th><th>Waktu</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($upcoming as $u): ?>
                  <tr>
                    <td><span class="badge bg-soft-primary text-primary"><?= esc($u['service_type'] ?? '-') ?></span></td>
                    <td><?= esc($u['title'] ?? '-') ?></td>
                    <td><?= esc($u['student_name'] ?? $u['class_name'] ?? '-') ?></td>
                    <td><?= dash_date($u['scheduled_at'] ?? $u['held_at'] ?? null) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= esc($u['status'] ?? '-') ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <?php $this->setData(['esIcon' => 'mdi-calendar-blank-outline', 'esText' => 'Tidak ada jadwal/kegiatan BK mendatang.']) ?>
          <?= $this->include('partials/dashboard/empty_state') ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
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
          <?php $this->setData(['esIcon' => 'mdi-history', 'esText' => 'Belum ada aktivitas terbaru.']) ?>
          <?= $this->include('partials/dashboard/empty_state') ?>
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
          datasets: [{ label: 'Jumlah Data', data: <?= json_encode(array_values($featureCounts)) ?>, backgroundColor: palette, borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
      });
    }

    const lineCtx = document.getElementById('trendLineChart');
    if (lineCtx) {
      new Chart(lineCtx, {
        type: 'line',
        data: {
          labels: <?= json_encode($monthlyTrend['labels'] ?? []) ?>,
          datasets: [{ label: 'Catatan BK', data: <?= json_encode($monthlyTrend['data'] ?? []) ?>, borderColor: '#556ee6', backgroundColor: 'rgba(85,110,230,0.1)', borderWidth: 2, fill: true, tension: 0.4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
      });
    }
  })();
</script>
<?= $this->endSection() ?>
