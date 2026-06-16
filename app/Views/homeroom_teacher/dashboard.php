<?php
$this->extend('layouts/main');
$this->section('content');

$stats = $stats ?? [];
$genderMale = (int) ($stats['gender_distribution']['male'] ?? 0);
$genderFemale = (int) ($stats['gender_distribution']['female'] ?? 0);
$totalActiveStudents = $genderMale + $genderFemale;
$attentionStudents = $attentionStudents ?? [];
$recentSessions = $recentSessions ?? [];
?>

<?php if (!$hasClass): ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="mdi mdi-alert-circle-outline text-warning" style="font-size: 64px;"></i>
                    <h4 class="mt-3">Belum Ada Kelas yang Ditugaskan</h4>
                    <p class="text-dark"><?= esc($message) ?></p>
                    <a href="<?= base_url('/') ?>" class="btn btn-primary mt-3">
                        <i class="mdi mdi-home me-1"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Dashboard Wali Kelas</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<!-- Akses Cepat (Fase 7: dashboard fokus jadwal mendatang + tombol cepat) -->
<?php
helper('url');
$quickShortcuts = [
  ['label' => 'Kelas Binaan', 'url' => base_url('homeroom/my-class'), 'icon' => 'mdi-google-classroom', 'color' => 'primary'],
  ['label' => 'Jadwal Kegiatan/Acara BK', 'url' => base_url('homeroom/jadwal-bk'), 'icon' => 'mdi-calendar-heart', 'color' => 'success'],
];
if (! function_exists('consultation_role_can_view') || consultation_role_can_view('wali kelas')) {
  $quickShortcuts[] = ['label' => 'Konsultasi & Pengaduan', 'url' => base_url('homeroom/consultations'), 'icon' => 'mdi-message-alert-outline', 'color' => 'warning'];
}
$quickShortcuts[] = ['label' => 'Impor Data Siswa', 'url' => base_url('homeroom/students/import'), 'icon' => 'mdi-file-import-outline', 'color' => 'info'];
$quickShortcuts[] = ['label' => 'Info Karier & Studi', 'url' => base_url('homeroom/career-info'), 'icon' => 'mdi-school-outline', 'color' => 'secondary'];
$quickShortcuts[] = ['label' => 'Laporan', 'url' => base_url('homeroom/reports'), 'icon' => 'mdi-file-chart', 'color' => 'primary'];
echo $this->include('role_features/_quick_actions');
?>

<div class="row">
  <div class="col-lg-12">
    <div class="card welcome-card">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h4 class="text-white mb-2">Selamat Datang, <?= esc($currentUser['full_name'] ?? 'Wali Kelas') ?>!</h4>
            <p class="text-white-50 mb-0">
              Anda adalah Wali Kelas
              <strong><?= !empty($class['is_multiple']) ? esc(($class['class_count'] ?? 0) . ' kelas: ' . ($class['class_name'] ?? '-')) : esc($class['class_name'] ?? '-') ?></strong>
              - Tahun Ajaran <?= esc($class['year_name'] ?? '-') ?> Semester <?= esc($class['semester'] ?? '-') ?>
            </p>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('homeroom/reports') ?>" class="btn btn-light">
              <i class="mdi mdi-file-chart me-1"></i> Lihat Laporan Kelas
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-4 col-md-6">
    <div class="card mini-stats-wid">
      <div class="card-body">
        <div class="d-flex">
          <div class="flex-grow-1">
            <p class="text-dark fw-medium mb-2">Total Siswa Aktif</p>
            <h4 class="mb-0 counter"><?= number_format($totalActiveStudents) ?></h4>
          </div>
          <div class="mini-stat-icon avatar-sm rounded-circle bg-soft-primary align-self-center">
            <span class="avatar-title rounded-circle bg-primary">
              <i class="mdi mdi-account-group font-size-24 text-white"></i>
            </span>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-dark">
            <i class="mdi mdi-gender-male text-info"></i> <?= $genderMale ?> Laki-laki
            <span class="mx-2">|</span>
            <i class="mdi mdi-gender-female text-danger"></i> <?= $genderFemale ?> Perempuan
          </small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="card mini-stats-wid">
      <div class="card-body">
        <div class="d-flex">
          <div class="flex-grow-1">
            <p class="text-dark fw-medium mb-2">Sedang Dikonseling</p>
            <h4 class="mb-0 counter"><?= number_format((int) ($stats['students_in_counseling'] ?? 0)) ?></h4>
          </div>
          <div class="mini-stat-icon avatar-sm rounded-circle bg-soft-success align-self-center">
            <span class="avatar-title rounded-circle bg-success">
              <i class="mdi mdi-comment-account-outline font-size-24 text-white"></i>
            </span>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-dark">Siswa bulan ini</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-12">
    <div class="card mini-stats-wid">
      <div class="card-body">
        <div class="d-flex">
          <div class="flex-grow-1">
            <p class="text-dark fw-medium mb-2">Kelas Binaan</p>
            <h4 class="mb-0 counter"><?= number_format((int) ($class['class_count'] ?? 1)) ?></h4>
          </div>
          <div class="mini-stat-icon avatar-sm rounded-circle bg-soft-info align-self-center">
            <span class="avatar-title rounded-circle bg-info">
              <i class="mdi mdi-google-classroom font-size-24 text-white"></i>
            </span>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-dark"><?= esc($class['class_name'] ?? '-') ?></small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-7">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="card-title mb-0">
            <i class="mdi mdi-calendar-clock text-primary me-2"></i>Catatan Konseling Terbaru
          </h5>
          <a href="<?= base_url('homeroom/reports') ?>" class="btn btn-sm btn-soft-primary">Lihat Laporan</a>
        </div>

        <?php if (!empty($recentSessions)): ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Tanggal</th>
                  <th>Siswa</th>
                  <th>Topik</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($recentSessions, 0, 5) as $session): ?>
                  <tr>
                    <td><small><?= esc($session['session_date'] ?? '-') ?></small></td>
                    <td>
                      <div class="fw-semibold"><?= esc($session['student_name'] ?? '-') ?></div>
                      <small class="text-dark"><?= esc($session['class_name'] ?? $class['class_name'] ?? '-') ?></small>
                    </td>
                    <td><?= esc($session['topic'] ?? '-') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= esc($session['status'] ?? '-') ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="mdi mdi-calendar-blank-outline text-dark font-size-48"></i>
            <p class="text-dark mt-2 mb-0">Belum ada catatan konseling terbaru.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-account-heart-outline text-success me-2"></i>Siswa Perlu Perhatian
        </h5>

        <?php if (!empty($attentionStudents)): ?>
          <div class="list-group list-group-flush">
            <?php foreach (array_slice($attentionStudents, 0, 5) as $student): ?>
              <div class="list-group-item px-0">
                <div class="d-flex align-items-center">
                  <div class="avatar-sm me-3">
                    <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16 fw-bold">
                      <?= strtoupper(substr($student['full_name'] ?? $student['student_name'] ?? 'S', 0, 1)) ?>
                    </span>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-1 font-size-14"><?= esc($student['full_name'] ?? $student['student_name'] ?? 'Tanpa Nama') ?></h6>
                    <small class="text-dark">
                      NIK: <?= esc($student['nik'] ?? '-') ?> | NISN: <?= esc($student['nisn'] ?? '-') ?>
                    </small>
                    <?php if (!empty($student['status'])): ?>
                      <div class="mt-1">
                        <span class="badge bg-light text-dark border"><?= esc($student['status']) ?></span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-3">
            <i class="mdi mdi-check-circle-outline text-success font-size-36"></i>
            <p class="text-dark mt-2 mb-0">Belum ada siswa yang ditandai perlu perhatian khusus.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?php $this->endSection(); ?>
