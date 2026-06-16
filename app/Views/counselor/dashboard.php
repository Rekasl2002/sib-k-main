<?php
// app/Views/counselor/dashboard.php
// Fitur Dashboard Guru BK: ringkasan siswa binaan, jadwal, dan layanan BK final.
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('counselor_dash_num')) {
    function counselor_dash_num($value): string
    {
        return number_format((int) ($value ?? 0), 0, ',', '.');
    }
}

$stats = is_array($stats ?? null) ? $stats : [];
$todaySessions = is_array($todaySessions ?? null) ? $todaySessions : [];
$upcomingSessions = is_array($upcomingSessions ?? null) ? $upcomingSessions : [];
$assignedStudents = is_array($assignedStudents ?? null) ? $assignedStudents : [];
$recentActivities = is_array($recentActivities ?? null) ? $recentActivities : [];
$pendingSessions = is_array($pendingSessions ?? null) ? $pendingSessions : [];
$assignedClasses = is_array($assignedClasses ?? null) ? $assignedClasses : [];
$currentUser = is_array($currentUser ?? null) ? $currentUser : [];
$activeAcademic = is_array($activeAcademic ?? null) ? $activeAcademic : [];
$bkSummary = is_array($bkSummary ?? null) ? $bkSummary : [];
?>

<div class="row mb-3">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-1">Dashboard Guru BK</h4>
        <p class="text-dark mb-0">Ringkasan layanan konseling dan siswa binaan.</p>
      </div>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row mb-3">
  <div class="col-12">
    <div class="card welcome-card">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h4 class="text-white mb-2">Selamat Datang, <?= esc($currentUser['full_name'] ?? 'Guru BK') ?>!</h4>
            <p class="text-white-50 mb-0">
              Tahun Ajaran <?= esc($activeAcademic['year_name'] ?? $activeAcademic['year'] ?? '-') ?>
              <?php if (!empty($activeAcademic['semester'])): ?>
                - Semester <?= esc($activeAcademic['semester']) ?>
              <?php endif; ?>
              <br>
              Kelola layanan BK, tindak lanjut siswa, dan laporan layanan BK.
            </p>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('counselor/reports') ?>" class="btn btn-light">
              <i class="mdi mdi-file-chart me-1"></i> Lihat Laporan
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <?php
    $cards = [
        ['label' => 'Jadwal Hari Ini', 'value' => count($todaySessions), 'icon' => 'mdi mdi-calendar-today', 'color' => 'primary'],
        ['label' => 'Jadwal Mendatang', 'value' => count($upcomingSessions), 'icon' => 'mdi mdi-calendar-clock', 'color' => 'success'],
        ['label' => 'Siswa Binaan', 'value' => count($assignedStudents), 'icon' => 'mdi mdi-account-group', 'color' => 'info'],
        ['label' => 'Perlu Tindak Lanjut', 'value' => count($pendingSessions), 'icon' => 'mdi mdi-clipboard-clock-outline', 'color' => 'warning'],
    ];
  ?>
  <?php foreach ($cards as $card): ?>
    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card mini-stats-wid h-100">
        <div class="card-body">
          <div class="d-flex">
            <div class="flex-grow-1">
              <p class="text-dark fw-medium mb-1"><?= esc($card['label']) ?></p>
              <h4 class="mb-0"><?= counselor_dash_num($card['value']) ?></h4>
            </div>
            <div class="avatar-sm rounded-circle bg-soft-<?= esc($card['color']) ?> d-flex align-items-center justify-content-center">
              <i class="<?= esc($card['icon']) ?> text-<?= esc($card['color']) ?> font-size-22"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <?php
    $bkCards = [
        ['label' => 'Total Layanan BK', 'value' => $bkSummary['total_services'] ?? 0, 'icon' => 'mdi mdi-clipboard-text-outline', 'color' => 'primary'],
        ['label' => 'Jadwal Layanan', 'value' => $bkSummary['scheduled'] ?? 0, 'icon' => 'mdi mdi-calendar-clock', 'color' => 'info'],
        ['label' => 'Perlu Tindak Lanjut', 'value' => $bkSummary['need_follow_up'] ?? 0, 'icon' => 'mdi mdi-clipboard-clock-outline', 'color' => 'warning'],
        ['label' => 'Konsultasi & Pengaduan Aktif', 'value' => $bkSummary['complaints_open'] ?? 0, 'icon' => 'mdi mdi-message-alert-outline', 'color' => 'danger'],
        ['label' => 'Penugasan Berjalan', 'value' => $bkSummary['assignments_open'] ?? 0, 'icon' => 'mdi mdi-account-arrow-right-outline', 'color' => 'success'],
    ];
  ?>
  <?php foreach ($bkCards as $card): ?>
    <div class="col-xl col-md-4 col-sm-6 mb-3">
      <div class="card mini-stats-wid h-100">
        <div class="card-body">
          <div class="d-flex">
            <div class="flex-grow-1">
              <p class="text-dark fw-medium mb-1"><?= esc($card['label']) ?></p>
              <h4 class="mb-0"><?= counselor_dash_num($card['value']) ?></h4>
            </div>
            <div class="avatar-sm rounded-circle bg-soft-<?= esc($card['color']) ?> d-flex align-items-center justify-content-center">
              <i class="<?= esc($card['icon']) ?> text-<?= esc($card['color']) ?> font-size-22"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (!empty($bkSummary['by_type']) && is_array($bkSummary['by_type'])): ?>
  <div class="row">
    <div class="col-12 mb-3">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="mdi mdi-chart-box-outline text-primary me-1"></i>Jumlah Layanan per Jenis
          </h5>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($bkSummary['by_type'] as $type => $count): ?>
              <span class="badge bg-light text-dark border px-3 py-2"><?= esc($type) ?>: <?= counselor_dash_num($count) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-xl-7 mb-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="card-title mb-0">
            <i class="mdi mdi-calendar-check-outline text-primary me-1"></i>Jadwal Hari Ini
          </h5>
          <a href="<?= base_url('counselor/counseling') ?>" class="btn btn-sm btn-soft-primary">Lihat Konseling</a>
        </div>
        <?php if (!empty($todaySessions)): ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>Waktu</th><th>Siswa</th><th>Topik</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($todaySessions, 0, 5) as $session): ?>
                  <tr>
                    <td><?= esc($session['session_time'] ?? '-') ?></td>
                    <td><?= esc($session['student_name'] ?? $session['full_name'] ?? '-') ?></td>
                    <td><?= esc($session['topic'] ?? '-') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= esc($session['status'] ?? '-') ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-dark mb-0">Tidak ada jadwal konseling hari ini.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5 mb-3">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-google-classroom text-success me-1"></i>Kelas Binaan
        </h5>
        <?php if (!empty($assignedClasses)): ?>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($assignedClasses as $class): ?>
              <span class="badge bg-light text-dark border px-3 py-2">
                <?= esc($class['class_name'] ?? '-') ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-dark mb-0">Belum ada kelas binaan aktif.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-6 mb-3">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-account-school-outline text-info me-1"></i>Siswa Binaan
        </h5>
        <?php if (!empty($assignedStudents)): ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>Nama</th><th>Kelas</th><th>NISN</th></tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($assignedStudents, 0, 6) as $student): ?>
                  <tr>
                    <td><?= esc($student['full_name'] ?? $student['student_name'] ?? '-') ?></td>
                    <td><?= esc($student['class_name'] ?? '-') ?></td>
                    <td><?= esc($student['nisn'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-dark mb-0">Belum ada siswa binaan.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-6 mb-3">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-history text-secondary me-1"></i>Aktivitas Terbaru
        </h5>
        <?php if (!empty($recentActivities)): ?>
          <div class="list-group list-group-flush">
            <?php foreach (array_slice($recentActivities, 0, 6) as $activity): ?>
              <div class="list-group-item px-0">
                <div class="fw-semibold"><?= esc($activity['title'] ?? $activity['message'] ?? '-') ?></div>
                <small class="text-dark"><?= esc($activity['created_at'] ?? $activity['time'] ?? '-') ?></small>
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
