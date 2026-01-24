<!-- app/Views/student/dashboard.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$currentUser    = is_array($currentUser ?? null) ? $currentUser : (array) ($currentUser ?? []);
$activeAcademic = is_array($activeAcademic ?? null) ? $activeAcademic : (array) ($activeAcademic ?? []);
$ay  = trim((string) ($activeAcademic['year'] ?? ''));
$sem = trim((string) ($activeAcademic['semester'] ?? ''));
?>

<?php
// Helper aman untuk ambil nilai dari array/objek
if (!function_exists('v')) {
  /**
   * @param array|object|null $src
   * @param string            $key
   * @param mixed             $default
   * @param bool              $escape  True: kembalikan nilai yang sudah di-esc
   */
  function v($src, string $key, $default = '', bool $escape = true) {
    if (is_array($src)) {
      $val = $src[$key] ?? $default;
    } elseif (is_object($src)) {
      $val = isset($src->$key) ? $src->$key : $default;
    } else {
      $val = $default;
    }
    return $escape ? esc($val) : $val;
  }
}

// Fallback multi-key: ambil key pertama yang tersedia
if (!function_exists('vx')) {
  function vx($src, array $keys, $default='-') {
    foreach ($keys as $k) {
      $v = v($src, $k, null, false);
      if ($v !== null && $v !== '') return esc($v);
    }
    return esc($default);
  }
}

if (!function_exists('badgeClass')) {
  function badgeClass($status) {
    $s = strtolower((string)$status);
    return match (true) {
      str_contains($s,'selesai')      => 'bg-success',
      str_contains($s,'dijadwalkan')  => 'bg-info',
      str_contains($s,'proses')       => 'bg-primary',
      str_contains($s,'batal')        => 'bg-danger',
      str_contains($s,'tidak hadir')  => 'bg-warning',
      default                         => 'bg-secondary',
    };
  }
}
?>

<div class="page-content">
  <div class="container-fluid">

    <!-- Title / Breadcrumb -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
          <h4 class="mb-sm-0">Dashboard Siswa</h4>
          <div class="page-title-right">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

<!-- Welcome Card Siswa (style sama seperti Admin) -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card welcome-card">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-md-7">
                        <h4 class="text-white mb-2">
                            Selamat Datang, <?= esc($currentUser['full_name'] ?? 'Siswa') ?>!
                        </h4>

                        <p class="text-white-50 mb-2">
                            Anda login sebagai <strong>Siswa</strong>
                            <?php if ($ay !== '' && $sem !== ''): ?>
                                <span class="ms-1">• Tahun Ajaran <?= esc($ay) ?> Semester <?= esc($sem) ?></span>
                            <?php elseif ($ay !== ''): ?>
                                <span class="ms-1">• Tahun Ajaran <?= esc($ay) ?></span>
                            <?php endif; ?>
                        </p>

                        <p class="text-white-50 mb-0">
                            Akses data pribadi, jadwal konseling, riwayat kasus ringkas, asesmen, serta portal informasi karir & perguruan tinggi.
                        </p>
                    </div>

                    <!-- Tombol cepat (kanan) -->
                    <div class="col-md-5">
                        <div class="d-grid gap-2">
                            <a href="<?= base_url('student/profile') ?>" class="btn btn-light">
                                <i class="mdi mdi-account-circle-outline me-1"></i> Profil Saya
                            </a>
                            <a href="<?= base_url('student/staff') ?>" class="btn btn-light">
                                <i class="mdi mdi-account-circle-outline me-1"></i> Info Guru
                            </a>
                        </div>
                    </div>
                </div><!-- /row -->
            </div>
        </div>
    </div>
</div>

    <!-- Welcome + Info ringkas -->
    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                  <div class="fw-semibold">Kelas</div>
                  <div><?= v($student ?? [],'class_name','-') ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                  <div class="fw-semibold">Tahun Ajaran Aktif</div>
                  <div>
                    <!-- aman untuk year_label ATAU year_name -->
                    <?= vx($activeYear ?? [], ['year_label','year_name'], '-') ?>
                    <?php if (!empty(v($activeYear ?? [], 'semester', '', false))): ?>
                      (<?= v($activeYear ?? [],'semester','') ?>)
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                  <div class="fw-semibold">Poin Pelanggaran</div>
                  <div><?= v($student ?? [],'total_violation_points',0) ?></div>
                </div>
              </div>
            </div>

            <!-- Aksi cepat -->
            <div class="mt-3 d-flex flex-wrap gap-2">
              <!--<a href="<?= function_exists('route_to') ? route_to('student.schedule.request') : base_url('student/schedule/request') ?>" class="btn btn-sm btn-outline-primary">
                <i class="mdi mdi-calendar-plus-outline me-1"></i> Ajukan Konseling
              </a>-->
              <!-- <a href="<?= function_exists('route_to') ? route_to('student.assessments') : base_url('student/assessments') ?>" class="btn btn-sm btn-outline-success">
                <i class="mdi mdi-clipboard-check-outline me-1"></i> Lihat Asesmen
              </a>-->
              <!--<a href="<?= function_exists('route_to') ? route_to('student.assessments.results') : base_url('student/assessments/results') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="mdi mdi-chart-line me-1"></i> Hasil Saya
              </a>-->
              <a href="<?= base_url('student/violations') ?>" class="btn btn-sm btn-outline-warning">
                <i class="mdi mdi-alert-outline me-1"></i> Kasus & Pelanggaran
              </a>
              <a href="<?= base_url('student/schedule') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="mdi mdi-alert-outline me-1"></i> Sesi Konseling
              </a>
              <!--<a href="<?= base_url('student/career') ?>" class="btn btn-sm btn-outline-info">
                <i class="mdi mdi-compass-outline me-1"></i> Jelajahi Karier
              </a>-->
            </div>
          </div>
        </div>

        <!-- Jadwal Konseling Mendatang -->
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Jadwal Konseling Mendatang</h5>
            <?php if (!empty($upcomingSessions) && is_array($upcomingSessions)): ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Waktu</th>
                      <th>Jenis</th>
                      <th>Lokasi</th>
                      <th>Topik</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($upcomingSessions as $s): ?>
                      <?php $s = is_array($s) ? $s : (array)$s; ?>
                      <tr>
                        <td><?= esc($s['session_date'] ?? '-') ?></td>
                        <td><?= esc(($s['session_time'] ?? '') ?: '-') ?></td>
                        <td><?= esc($s['session_type'] ?? '-') ?></td>
                        <td><?= esc(($s['location'] ?? '') ?: '-') ?></td>
                        <td><?= esc($s['topic'] ?? '-') ?></td>
                        <td><span class="badge <?= badgeClass($s['status'] ?? '') ?>"><?= esc($s['status'] ?? '-') ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted mb-0">Belum ada jadwal konseling terdekat.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Opsional: Ringkasan Pelanggaran -->
        <?php if (!empty($violationSummary) && is_array($violationSummary)): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Ringkasan Pelanggaran</h5>
            <div class="row g-3">
              <div class="col-md-3">
                <div class="p-3 bg-light rounded text-center">
                  <div class="fw-semibold">Total Kasus</div>
                  <div class="fs-5"><?= esc($violationSummary['total'] ?? 0) ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded text-center">
                  <div class="fw-semibold">Aktif</div>
                  <div class="fs-5"><?= esc($violationSummary['open'] ?? 0) ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded text-center">
                  <div class="fw-semibold">Selesai</div>
                  <div class="fs-5"><?= esc($violationSummary['completed'] ?? 0) ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded text-center">
                  <div class="fw-semibold">Total Poin</div>
                  <div class="fs-5">
                    <?= esc(isset($violationSummary['points'])
                          ? $violationSummary['points']
                          : v($student ?? [], 'total_violation_points', 0, false)) ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sisi kanan: Asesmen & Hasil Terbaru + Karier -->
      <div class="col-xl-4">

        <!-- Asesmen Tersedia 
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Asesmen Tersedia</h5>
            <?php if (!empty($assessments) && is_array($assessments)): ?>
              <ul class="list-group">
                <?php foreach ($assessments as $a): ?>
                  <?php
                    $a = is_array($a) ? $a : (array)$a;

                    // Alternatif yang kamu pilih: sembunyikan item yang sudah dikerjakan.
                    // Controller idealnya sudah menyaring via HAVING; ini guard tambahan jika flag tersedia.
                    if (!empty($a['has_done'])) {
                      continue; // skip yang sudah Completed/Graded
                    }
                  ?>
                  <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="me-auto">
                      <div class="fw-semibold"><?= esc($a['title'] ?? 'Tanpa Judul') ?></div>
                      <small class="text-muted">
                        <?= esc($a['assessment_type'] ?? 'Assessment') ?>
                        <?php if (!empty($a['total_questions'])): ?>
                          • <?= esc($a['total_questions']) ?> soal
                        <?php endif; ?>
                      </small>
                    </div>
                    <a class="btn btn-sm btn-primary"
                       href="<?= function_exists('route_to')
                              ? route_to('student.assessments.take', (int)($a['id'] ?? 0))
                              : base_url('student/assessments/take/'.(int)($a['id'] ?? 0)) ?>">
                      Kerjakan
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted mb-0">Tidak ada asesmen tersedia saat ini.</p>
            <?php endif; ?>
          </div>
        </div>-->

        <!-- Hasil Asesmen Terbaru 
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Hasil Asesmen Terbaru</h5>
            <?php if (!empty($recentResults) && is_array($recentResults)): ?>
              <ul class="list-group">
                <?php foreach ($recentResults as $r): ?>
                  <?php $r = is_array($r) ? $r : (array)$r; ?>
                  <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="me-auto">
                      <div class="fw-semibold"><?= esc($r['title'] ?? '—') ?></div>
                      <small class="text-muted">
                        <?= esc($r['status'] ?? '-') ?>
                        <?php if (isset($r['percentage'])): ?> • <?= esc($r['percentage']) ?>%<?php endif; ?>
                      </small>
                    </div>
                    <?php if (!empty($r['id'])): ?>
                      <a class="btn btn-sm btn-outline-secondary"
                         href="<?= function_exists('route_to')
                                ? route_to('student.assessments.review', (int)$r['id'])
                                : base_url('student/assessments/review/'.(int)$r['id']) ?>">
                        Lihat
                      </a>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted mb-0">Belum ada hasil baru.</p>
            <?php endif; ?>
          </div>
        </div>-->

        <!-- Opsional: Highlight Karier -->
        <?php if (!empty($careerHighlights) && is_array($careerHighlights)): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Highlight Karier</h5>
            <ul class="list-group">
              <?php foreach ($careerHighlights as $c): ?>
                <?php $c = is_array($c) ? $c : (array)$c; ?>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                  <div class="me-auto">
                    <div class="fw-semibold"><?= esc($c['title'] ?? 'Karier') ?></div>
                    <small class="text-muted"><?= esc($c['sector'] ?? '-') ?></small>
                  </div>
                  <a class="btn btn-sm btn-outline-info" href="<?= base_url('student/career/'.(int)($c['id'] ?? 0)) ?>">Detail</a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
