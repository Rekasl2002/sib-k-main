<?php // app/Views/koordinator/dashboard.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('dash_row')) {
    function dash_row($value): array
    {
        return is_array($value) ? $value : (is_object($value) ? (array) $value : []);
    }
}

if (!function_exists('dash_num')) {
    function dash_num($value): string
    {
        return number_format((int) ($value ?? 0), 0, ',', '.');
    }
}

$quick = dash_row($quick ?? []);
$currentUser = dash_row($currentUser ?? []);
$activeAcademic = dash_row($activeAcademic ?? []);
$topCounselors = $topCounselors ?? [];
$assessmentCompletion = $assessmentCompletion ?? [];
$recentActivities = $recentActivities ?? [];

$ay = trim((string) ($activeAcademic['year'] ?? ''));
$sem = trim((string) ($activeAcademic['semester'] ?? ''));
$ayText = trim($ay . ($sem !== '' ? ' Semester ' . $sem : ''));
?>

<div class="container-fluid">
  <div class="row mb-3">
    <div class="col-12">
      <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
          <h4 class="mb-1">Dashboard Koordinator BK</h4>
          <p class="text-muted mb-0">Ikhtisar layanan BK seluruh madrasah.</p>
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
              <h4 class="text-white mb-2">
                Selamat Datang, <?= esc($currentUser['full_name'] ?? 'Koordinator BK') ?>!
              </h4>
              <p class="text-white-50 mb-0">
                Anda login sebagai <strong>Koordinator BK</strong>
                <?php if ($ayText !== ''): ?>
                  <span class="ms-1">- <?= esc($ayText) ?></span>
                <?php endif; ?>
                <br>
                Pantau siswa, sesi konseling, asesmen, laporan, dan aktivitas terbaru.
              </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
              <a href="<?= base_url('koordinator/reports') ?>" class="btn btn-light">
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
          ['label' => 'Total Siswa', 'value' => $quick['totalStudents'] ?? 0, 'icon' => 'bx bx-group', 'color' => 'primary'],
          ['label' => 'Siswa Aktif', 'value' => $quick['activeStudents'] ?? 0, 'icon' => 'bx bx-user-check', 'color' => 'success'],
          ['label' => 'Staf BK & Wali Kelas', 'value' => $quick['totalStaff'] ?? 0, 'icon' => 'bx bx-user-voice', 'color' => 'info'],
          ['label' => 'Total Sesi', 'value' => $quick['totalSessions'] ?? 0, 'icon' => 'bx bx-conversation', 'color' => 'warning'],
          ['label' => 'Sesi Hari Ini', 'value' => $quick['todaySessions'] ?? 0, 'icon' => 'bx bx-calendar-check', 'color' => 'secondary'],
          ['label' => 'Sesi Mendatang', 'value' => $quick['upcomingSessions'] ?? 0, 'icon' => 'bx bx-calendar-event', 'color' => 'primary'],
          ['label' => 'Asesmen Aktif', 'value' => $quick['activeAssessments'] ?? 0, 'icon' => 'bx bx-clipboard', 'color' => 'success'],
          ['label' => 'Notifikasi Belum Dibaca', 'value' => $quick['unreadNotifications'] ?? 0, 'icon' => 'bx bx-bell', 'color' => 'danger'],
      ];
    ?>
    <?php foreach ($cards as $card): ?>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card mini-stats-wid shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="d-flex">
              <div class="flex-grow-1">
                <p class="text-muted fw-medium mb-1"><?= esc($card['label']) ?></p>
                <h4 class="mb-0"><?= dash_num($card['value']) ?></h4>
              </div>
              <div class="avatar-sm rounded-circle bg-soft-<?= esc($card['color']) ?> d-flex align-items-center justify-content-center">
                <i class="<?= esc($card['icon']) ?> text-<?= esc($card['color']) ?> font-size-20"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row">
    <div class="col-xl-6 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="mdi mdi-account-tie-outline text-primary me-1"></i>Guru BK dengan Sesi Terbanyak
          </h5>
          <?php if (!empty($topCounselors)): ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Guru BK</th><th>Kelas</th><th class="text-end">Sesi</th><th class="text-end">Durasi</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($topCounselors as $row): ?>
                    <tr>
                      <td><?= esc($row['counselor_name'] ?? '-') ?></td>
                      <td><?= esc($row['class_names'] ?? '-') ?></td>
                      <td class="text-end"><?= dash_num($row['total'] ?? 0) ?></td>
                      <td class="text-end"><?= dash_num($row['duration'] ?? 0) ?> m</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted mb-0">Belum ada data sesi konseling.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-xl-6 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="mdi mdi-clipboard-check-outline text-success me-1"></i>Asesmen Terisi
          </h5>
          <?php if (!empty($assessmentCompletion)): ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Asesmen</th><th class="text-end">Jumlah</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($assessmentCompletion as $row): ?>
                    <tr>
                      <td><?= esc($row['title'] ?? ('Asesmen #' . ($row['assessment_id'] ?? '-'))) ?></td>
                      <td class="text-end"><?= dash_num($row['filled'] ?? $row['total'] ?? 0) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted mb-0">Belum ada data asesmen terisi.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="mdi mdi-history text-info me-1"></i>Aktivitas Terbaru
          </h5>
          <?php if (!empty($recentActivities)): ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Waktu</th><th>Jenis</th><th>Aktivitas</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($recentActivities as $activity): ?>
                    <tr>
                      <td><?= esc($activity['created_at'] ?? '-') ?></td>
                      <td><span class="badge bg-light text-dark border"><?= esc($activity['type'] ?? '-') ?></span></td>
                      <td><?= esc($activity['message'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted mb-0">Belum ada aktivitas terbaru.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
