<?php
// app/Views/parent/dashboard.php
// Dashboard Orang Tua — tata letak patokan Admin + NNG (tanpa chart).
// Fokus pada Jadwal/Kegiatan BK (bukan "konseling" saja).
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$children = is_array($children ?? null) ? $children : [];
$upcoming = is_array($upcoming ?? null) ? $upcoming : [];

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
      <h4 class="mb-0">Dashboard Orang Tua</h4>
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

<!-- Zona bawah: tabel detail -->
<div class="row">
  <div class="col-xl-7">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <h4 class="card-title mb-0 flex-grow-1"><i class="mdi mdi-calendar-check-outline me-2"></i>Jadwal/Kegiatan BK Mendatang</h4>
          <a href="<?= base_url('parent/jadwal-bk') ?>" class="btn btn-sm btn-primary">Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i></a>
        </div>
        <?php if (! empty($upcoming)): ?>
          <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
              <thead class="table-light">
                <tr><th>Jenis</th><th>Anak/Sasaran</th><th>Tanggal & Waktu</th><th>Lokasi</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($upcoming as $u): ?>
                  <?php $u = is_array($u) ? $u : (array) $u; ?>
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
          <p class="text-dark mb-0">Belum ada jadwal/kegiatan BK terdekat.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3"><i class="mdi mdi-account-child-outline me-2"></i>Ringkasan Anak</h4>
        <?php if (! empty($children)): ?>
          <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
              <thead class="table-light">
                <tr><th>Nama</th><th>Kelas</th></tr>
              </thead>
              <tbody>
                <?php foreach ($children as $c): ?>
                  <tr>
                    <td>
                      <a href="<?= base_url('parent/child/' . (int) ($c['id'] ?? 0) . '/profile') ?>"><?= esc($c['full_name'] ?? '-') ?></a>
                      <div class="small text-dark">NISN: <?= esc($c['nisn'] ?? '-') ?></div>
                    </td>
                    <td><?= esc($c['class_name'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-dark mb-0">Belum ada data anak terdaftar.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
