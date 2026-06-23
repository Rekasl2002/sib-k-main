<?php
// app/Views/student/dashboard.php
// Dashboard Siswa — tata letak patokan Admin + NNG (tanpa chart).
// Fokus pada Jadwal/Kegiatan BK (bukan "konseling" saja).
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$student          = $student ?? null;
$activeYear       = $activeYear ?? null;
$upcomingSessions = is_array($upcomingSessions ?? null) ? $upcomingSessions : [];
$assessments      = is_array($assessments ?? null) ? $assessments : [];
$recentResults    = is_array($recentResults ?? null) ? $recentResults : [];

if (! function_exists('dash_date')) {
    function dash_date($v): string
    {
        if (empty($v)) return '-';
        $ts = strtotime((string) $v);
        return $ts ? date('d M Y H:i', $ts) : (string) $v;
    }
}
if (! function_exists('sv')) {
    function sv($src, string $key, $default = '-')
    {
        if (is_array($src)) return esc($src[$key] ?? $default);
        if (is_object($src)) return esc($src->$key ?? $default);
        return esc($default);
    }
}
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">Dashboard Siswa</h4>
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

<!-- Info ringkas siswa -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="p-3 bg-light rounded">
              <div class="fw-semibold">Kelas</div>
              <div><?= sv($student, 'class_name', '-') ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 bg-light rounded">
              <div class="fw-semibold">NIK / NISN</div>
              <div class="small"><?= sv($student, 'nik', '-') ?> / <?= sv($student, 'nisn', '-') ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 bg-light rounded">
              <div class="fw-semibold">Tahun Ajaran Aktif</div>
              <div>
                <?= sv($activeYear, 'year_label', '-') ?>
                <?php $semVal = is_object($activeYear) ? ($activeYear->semester ?? '') : (is_array($activeYear) ? ($activeYear['semester'] ?? '') : ''); ?>
                <?php if ($semVal !== ''): ?>(<?= esc($semVal) ?>)<?php endif; ?>
              </div>
            </div>
          </div>
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
          <a href="<?= base_url('student/jadwal-bk') ?>" class="btn btn-sm btn-primary">Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i></a>
        </div>
        <?php if (! empty($upcomingSessions)): ?>
          <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
              <thead class="table-light">
                <tr><th>Jenis</th><th>Tanggal & Waktu</th><th>Lokasi</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($upcomingSessions as $s): ?>
                  <?php $s = is_array($s) ? $s : (array) $s; ?>
                  <tr>
                    <td><span class="badge bg-soft-primary text-primary"><?= esc($s['service_type'] ?? '-') ?></span></td>
                    <td><?= dash_date($s['scheduled_at'] ?? $s['held_at'] ?? null) ?></td>
                    <td><?= esc($s['location'] ?? '') ?: '-' ?></td>
                    <td><span class="badge bg-light text-dark border"><?= esc($s['status'] ?? '-') ?></span></td>
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
        <h4 class="card-title mb-3"><i class="mdi mdi-clipboard-list-outline me-2"></i>Asesmen Tersedia</h4>
        <?php
        $availList = array_filter($assessments, static function ($a) {
            $a = is_array($a) ? $a : (array) $a;
            return empty($a['has_done']);
        });
        ?>
        <?php if (! empty($availList)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($availList as $a): ?>
              <?php $a = is_array($a) ? $a : (array) $a; ?>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <div class="me-auto">
                  <div class="fw-semibold"><?= esc($a['title'] ?? 'Tanpa Judul') ?></div>
                  <small class="text-dark"><?= esc($a['assessment_type'] ?? 'Asesmen') ?></small>
                </div>
                <a class="btn btn-sm btn-primary" href="<?= base_url('student/assessments/take/' . (int) ($a['id'] ?? 0)) ?>">Kerjakan</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-dark mb-0">Tidak ada asesmen tersedia saat ini.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3"><i class="mdi mdi-clipboard-check-outline me-2"></i>Hasil Asesmen Terbaru</h4>
        <?php if (! empty($recentResults)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($recentResults as $r): ?>
              <?php $r = is_array($r) ? $r : (array) $r; ?>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <div class="me-auto">
                  <div class="fw-semibold"><?= esc($r['title'] ?? '—') ?></div>
                  <small class="text-dark">
                    <?= esc($r['status'] ?? '-') ?><?php if (isset($r['percentage'])): ?> • <?= esc($r['percentage']) ?>%<?php endif; ?>
                  </small>
                </div>
                <?php if (! empty($r['id'])): ?>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('student/assessments/review/' . (int) $r['id']) ?>">Lihat</a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-dark mb-0">Belum ada hasil asesmen.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
