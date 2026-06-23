<?php
// app/Views/homeroom_teacher/dashboard.php
// Dashboard Wali Kelas — tata letak patokan Admin + NNG. Fokus kegiatan BK kelas.

$featureCounts = is_array($featureCounts ?? null) ? $featureCounts : [];
$upcoming      = $upcoming ?? [];
$genderMale    = (int) ($genderMale ?? 0);
$genderFemale  = (int) ($genderFemale ?? 0);

if (! function_exists('dash_date')) {
    function dash_date($v): string
    {
        if (empty($v)) return '-';
        $ts = strtotime((string) $v);
        return $ts ? date('d M Y H:i', $ts) : (string) $v;
    }
}
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if (empty($hasClass)): ?>
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="mdi mdi-alert-circle-outline text-warning" style="font-size: 64px;"></i>
          <h4 class="mt-3">Belum Ada Kelas yang Ditugaskan</h4>
          <p class="text-dark"><?= esc($message ?? '') ?></p>
          <a href="<?= base_url('/') ?>" class="btn btn-primary mt-3"><i class="mdi mdi-home me-1"></i> Kembali ke Beranda</a>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">Dashboard Wali Kelas</h4>
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
  <div class="col-xl-5 d-flex">
    <div class="card flex-fill">
      <div class="card-body">
        <h4 class="card-title mb-4"><i class="mdi mdi-chart-donut me-2"></i>Komposisi Siswa</h4>
        <div style="height: 260px;">
          <canvas id="genderChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-7 d-flex">
    <div class="card flex-fill">
      <div class="card-body">
        <h4 class="card-title mb-4"><i class="mdi mdi-chart-bar me-2"></i>Jumlah Data per Fitur BK (Kelas)</h4>
        <div style="height: 260px;">
          <canvas id="featureBarChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Zona bawah: tabel detail -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <h4 class="card-title mb-0 flex-grow-1"><i class="mdi mdi-calendar-check-outline me-2"></i>Jadwal/Kegiatan BK Mendatang</h4>
          <a href="<?= base_url('homeroom/jadwal-bk') ?>" class="btn btn-sm btn-primary">Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i></a>
        </div>
        <?php if (! empty($upcoming)): ?>
          <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
              <thead class="table-light">
                <tr><th>Jenis</th><th>Sasaran</th><th>Waktu</th><th>Lokasi</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($upcoming as $u): ?>
                  <tr>
                    <td><span class="badge bg-soft-primary text-primary"><?= esc($u['service_type'] ?? '-') ?></span></td>
                    <td><?= esc($u['student_name'] ?? $u['class_name'] ?? '-') ?></td>
                    <td><?= dash_date($u['scheduled_at'] ?? $u['held_at'] ?? null) ?></td>
                    <td><?= esc($u['location'] ?? '') ?: '-' ?></td>
                    <td><span class="badge bg-light text-dark border"><?= esc($u['status'] ?? '-') ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <?php $this->setData(['esIcon' => 'mdi-calendar-blank-outline', 'esText' => 'Belum ada jadwal/kegiatan BK mendatang untuk kelas Anda.']) ?>
          <?= $this->include('partials/dashboard/empty_state') ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (! empty($hasClass)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  (function () {
    const palette = ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#7b6cb6', '#02a499', '#ec4561', '#564ab1'];

    const gCtx = document.getElementById('genderChart');
    if (gCtx) {
      new Chart(gCtx, {
        type: 'doughnut',
        data: {
          labels: ['Laki-laki', 'Perempuan'],
          datasets: [{ data: [<?= $genderMale ?>, <?= $genderFemale ?>], backgroundColor: ['#50a5f1', '#f46a6a'], borderWidth: 2, borderColor: '#fff' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
      });
    }

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
  })();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
