<?php // app/Views/koordinator/dashboard.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
use CodeIgniter\I18n\Time;

// Helpers kecil agar view tahan banting untuk array/objek
if (!function_exists('rowa')) {
    function rowa($r): array
    {
        return is_array($r)
            ? $r
            : (is_object($r) ? (array) $r : []);
    }
}

if (!function_exists('gv')) {
    /**
     * Get value aman dari array/objek
     *
     * @param array|object|null $src
     * @param string            $key
     * @param mixed             $default
     * @param bool              $escape
     * @return mixed
     */
    function gv($src, string $key, $default = '', bool $escape = true)
    {
        $a   = rowa($src ?? []);
        $val = $a[$key] ?? $default;
        return $escape ? esc($val) : $val;
    }
}

// Normalisasi data dari controller (kompatibel dengan versi lama & baru)
$quick            = rowa($quick ?? $stats ?? []);
$violationsData   = array_map('rowa', $violationsByLevel ?? $violationsChart ?? []);
$recentActivities = $recentActivities ?? [];
// Current user (untuk kartu Selamat Datang)
$currentUser = rowa($currentUser ?? []);
$activeAcademicYearLabel = (string) ($activeAcademicYearLabel ?? '');

// Data pelanggaran per kategori (untuk chart doughnut)
$violationByCategory = array_map('rowa', $violationByCategory ?? []);
$categoryRangeLabel  = (string) ($categoryRangeLabel ?? '');


// Data tren bulanan (labels + data) – jika tidak ada, fallback ke array kosong
$monthlyViolations  = rowa($monthlyViolations  ?? []);
$monthlySessions    = rowa($monthlySessions    ?? []);
$monthlyAssessments = rowa($monthlyAssessments ?? []);

// Tentukan label untuk grafik tren
$trendLabels = $monthlyViolations['labels']
    ?? $monthlySessions['labels']
    ?? $monthlyAssessments['labels']
    ?? [];

$trendViolations = $monthlyViolations['data']  ?? [];
$trendSessions   = $monthlySessions['data']    ?? [];
$trendAssessments= $monthlyAssessments['data'] ?? [];

// Top list
$topStudents          = $topStudents ?? [];
$topCounselors        = $topCounselors ?? [];
$assessmentCompletion = $assessmentCompletion ?? [];

// Hitung rasio siswa per staf (kalau data ada)
$ratioStudentStaff = null;
$totalStudents     = (int) ($quick['totalStudents'] ?? 0);
$totalStaff        = (int) ($quick['totalStaff'] ?? 0);
if ($totalStudents > 0 && $totalStaff > 0) {
    $ratioStudentStaff = $totalStudents / max(1, $totalStaff);
}

// Hitung total pelanggaran (untuk persen)
$totalViolations = 0;
foreach ($violationsData as $row) {
    $totalViolations += (int) ($row['total'] ?? 0);
}
?>

<div class="container-fluid">
    <!-- Header & breadcrumb mini -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-1">Dashboard Koordinator BK</h4>
                    <p class="text-muted mb-0">
                        Ikhtisar kondisi layanan BK seluruh madrasah:
                        siswa, staf BK & wali kelas, kasus, sesi konseling, asesmen, dan aktivitas terbaru.
                    </p>
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
    </div>

    <!-- Welcome Card (disamakan dengan Wali Kelas, disesuaikan untuk Koordinator BK) -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card welcome-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="text-white mb-2">
                                Selamat Datang, <?= esc($currentUser['full_name'] ?? 'Koordinator BK') ?>!
                            </h4>
                            <?php
                                $activeAcademic = rowa($activeAcademic ?? []);
                                $ay = trim((string) ($activeAcademic['year'] ?? ''));
                                $sem = trim((string) ($activeAcademic['semester'] ?? ''));
                                $ayText = '';

                                if ($ay !== '' && $sem !== '') {
                                    $ayText = "Tahun Ajaran {$ay} Semester {$sem}";
                                } elseif ($ay !== '') {
                                    $ayText = "Tahun Ajaran {$ay}";
                                }
                                ?>

                                <p class="text-white-50 mb-0">
                                    Anda login sebagai <strong>Koordinator BK</strong>
                                    <?php if ($ayText !== ''): ?>
                                        <span class="ms-1">• <?= esc($ayText) ?></span>
                                    <?php endif; ?>
                                    <br>
                                    Pantau pelanggaran, sesi konseling, dan kinerja layanan BK seluruh madrasah, sekaligus kelola akun Guru BK.
                                </p>
                            <?php if (!empty($activeAcademicYearLabel)): ?>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">
                                        <i class="mdi mdi-calendar-check-outline me-1"></i>
                                        Tahun Akademik Aktif: <?= esc($activeAcademicYearLabel) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-grid gap-2">
                                <a href="<?= base_url('homeroom/violations/create') ?>" class="btn btn-light">
                                    <i class="mdi mdi-alert-circle-outline me-1"></i> Laporkan Pelanggaran
                                </a>
                                <a href="<?= base_url('homeroom/violations') ?>" class="btn btn-light">
                                    <i class="mdi mdi-format-list-bulleted me-1"></i> Lihat Semua Pelanggaran
                                </a>
                                <a href="<?= base_url('homeroom/reports') ?>" class="btn btn-light">
                                    <i class="mdi mdi-file-chart me-1"></i> Lihat Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Quick Stats Utama -->
    <div class="row">
        <!-- Total Siswa -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Total Siswa</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'totalStudents', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-primary d-flex align-items-center justify-content-center">
                            <i class="bx bx-group text-primary font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Mengacu pada data siswa
                    </small>
                </div>
            </div>
        </div>

        <!-- Siswa Aktif -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Siswa Aktif</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'activeStudents', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-success d-flex align-items-center justify-content-center">
                            <i class="bx bx-user-check text-success font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Status <code>Aktif</code> di data siswa.
                    </small>
                </div>
            </div>
        </div>

        <!-- Staf BK & Wali Kelas -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Staf BK &amp; Wali Kelas</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'totalStaff', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-info d-flex align-items-center justify-content-center">
                            <i class="bx bx-user-voice text-info font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Guru BK + Wali Kelas aktif.
                    </small>
                </div>
            </div>
        </div>

        <!-- Kasus Aktif -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Kasus Aktif</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'activeCases', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-warning d-flex align-items-center justify-content-center">
                            <i class="bx bx-error-circle text-warning font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Status <code>Dilaporkan</code> / <code>Dalam Proses</code>.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Quick Stats Tambahan -->
    <div class="row">
        <!-- Kasus Selesai -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Kasus Selesai</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'closedCases', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-success d-flex align-items-center justify-content-center">
                            <i class="bx bx-check-circle text-success font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Kasus dengan status selesai/ditutup.
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Sesi Konseling -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Total Sesi Konseling</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'totalSessions', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-primary d-flex align-items-center justify-content-center">
                            <i class="bx bx-message-rounded-detail text-primary font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Seluruh record di sesi konseling
                    </small>
                </div>
            </div>
        </div>

        <!-- Sesi Hari Ini -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Sesi Konseling Hari Ini</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'todaySessions', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-info d-flex align-items-center justify-content-center">
                            <i class="bx bx-calendar-event text-info font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Berdasarkan tanggal sesi hari ini.
                    </small>
                </div>
            </div>
        </div>

        <!-- Asesmen Aktif 
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card mini-stats-wid shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-1">Asesmen Aktif</p>
                            <h4 class="mb-0">
                                <?= number_format((int) gv($quick, 'activeAssessments', 0, false)) ?>
                            </h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-soft-warning d-flex align-items-center justify-content-center">
                            <i class="bx bx-list-check text-warning font-size-20"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Asesmen yang dapat dikerjakan siswa.
                    </small>
                </div>
            </div>
        </div>-->
    </div>

    <!-- Insight Singkat & Ringkasan Pelanggaran -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title mb-2">
                        <i class="bx bx-bulb align-middle me-1 text-warning"></i>
                        Insight Beban Layanan
                    </h5>
                    <p class="text-muted mb-1">
                        Rasio siswa per staf BK &amp; wali:
                    </p>
                    <h3 class="mb-0">
                        <?php if ($ratioStudentStaff !== null): ?>
                            ± <?= number_format($ratioStudentStaff, 1) ?> siswa / staf
                        <?php else: ?>
                            <span class="text-muted">Data belum lengkap</span>
                        <?php endif; ?>
                    </h3>
                    <small class="text-muted d-block mt-2">
                        Membantu Koordinator mengukur sebaran beban layanan BK
                        dan kebutuhan penyesuaian penugasan.
                    </small>
                </div>
            </div>
        </div>

        <!-- Ringkasan Pelanggaran -->
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-bar-chart-alt-2 align-middle me-1 text-danger"></i>
                            Ringkasan Pelanggaran
                        </h5>
                        <small class="text-muted">
                            Total seluruh level:
                            <strong><?= number_format($totalViolations) ?></strong> kasus
                        </small>
                    </div>

                    <?php if ($totalViolations > 0): ?>
                        <div class="progress mt-2" style="height: 8px;">
                            <?php foreach ($violationsData as $row): ?>
                                <?php
                                $level = strtoupper((string) ($row['level'] ?? ''));
                                $count = (int) ($row['total'] ?? 0);
                                if ($count <= 0) continue;
                                $percent = round(($count / max(1, $totalViolations)) * 100, 1);

                                // Warna berdasarkan level
                                $barClass = 'bg-secondary';
                                if (stripos($level, 'RINGAN') !== false) {
                                    $barClass = 'bg-success';
                                } elseif (stripos($level, 'SEDANG') !== false) {
                                    $barClass = 'bg-warning';
                                } elseif (stripos($level, 'BERAT') !== false) {
                                    $barClass = 'bg-danger';
                                }
                                ?>
                                <div class="progress-bar <?= $barClass ?>"
                                     role="progressbar"
                                     style="width: <?= $percent ?>%;"
                                     aria-valuenow="<?= $percent ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 small text-muted">
                            Visual perbandingan proporsi pelanggaran menurut tingkat
                            (<code>tingkat keparahan</code>).
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Belum ada data pelanggaran yang tercatat.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- Row: Grafik Tren BK + Pelanggaran per Kategori -->
<div class="row">
    <!-- Grafik Tren -->
    <div class="col-lg-8 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">
                        Tren Layanan BK (<?= count($trendLabels) ?> bulan terakhir)
                    </h5>
                    <small class="text-muted">
                        Pelanggaran • Sesi
                    </small>
                </div>

                <div class="position-relative" style="height:260px; width:100%;">
                  <canvas id="bk-trend-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pelanggaran per Kategori -->
    <div class="col-lg-4 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-chart-donut text-info me-1"></i>Pelanggaran per Kategori
                    </h5>
                    <?php if (!empty($categoryRangeLabel)): ?>
                        <small class="text-muted"><?= esc($categoryRangeLabel) ?></small>
                    <?php endif; ?>
                </div>

                <?php if (!empty($violationByCategory)): ?>
                    <div class="position-relative" style="height:260px; width:100%;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="mt-2 small text-muted">
                        Menampilkan <?= min(5, count($violationByCategory)) ?> kategori teratas berdasarkan jumlah kasus.
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="mdi mdi-information-outline font-size-24 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">Belum ada data pelanggaran</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row: Detail Distribusi Pelanggaran -->
<div class="row">
    <div class="col-lg-12 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-2">
                    Detail Distribusi Pelanggaran
                </h5>

                <?php if (!empty($violationsData)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end">Persen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($violationsData as $row): ?>
                                    <?php
                                    $level = (string) ($row['level'] ?? '-');
                                    $count = (int) ($row['total'] ?? 0);
                                    $percent = $totalViolations > 0
                                        ? round(($count / $totalViolations) * 100, 1)
                                        : 0;
                                    ?>
                                    <tr>
                                        <td><?= esc(ucwords(strtolower($level))) ?></td>
                                        <td class="text-end"><?= number_format($count) ?></td>
                                        <td class="text-end"><?= $percent ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        Belum ada data pelanggaran yang dapat diringkas.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

    <!-- Row: Top List Siswa & Guru BK -->
    <div class="row mt-3">
        <!-- Top Siswa -->
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title mb-2">
                        Siswa dengan Kasus Terbanyak
                    </h5>
                    <?php if (!empty($topStudents)): ?>
                            <div class="table-responsive">
                              <table class="table table-sm align-middle mb-0">
                                  <thead>
                                      <tr>
                                          <th>Siswa</th>
                                          <th>Kelas</th>
                                          <th class="text-end">Jumlah Kasus</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <?php foreach ($topStudents as $row): ?>
                                          <?php
                                          $r            = rowa($row);
                                          $studentLabel = $r['student_name']
                                              ?? $r['full_name']
                                              ?? ('Siswa #' . ($r['student_id'] ?? '-'));

                                          $classLabel = trim((string) ($r['class_name'] ?? ''));
                                          if ($classLabel === '') {
                                              $classLabel = '-';
                                          }
                                          ?>
                                          <tr>
                                              <td><?= esc($studentLabel) ?></td>
                                              <td><?= esc($classLabel) ?></td>
                                              <td class="text-end"><?= number_format((int) ($r['total'] ?? 0)) ?></td>
                                          </tr>
                                      <?php endforeach; ?>
                                  </tbody>
                              </table>
                          </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Belum ada data siswa dengan kasus yang bisa ditampilkan.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Guru BK -->
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title mb-2">
                        Guru BK dengan Sesi Terbanyak
                    </h5>
                    <?php if (!empty($topCounselors)): ?>
                            <div class="table-responsive">
                              <table class="table table-sm align-middle mb-0">
                                  <thead>
                                      <tr>
                                          <th>Guru BK</th>
                                          <th>Kelas Binaan</th>
                                          <th class="text-end">Jumlah Sesi</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <?php foreach ($topCounselors as $row): ?>
                                          <?php
                                          $r = rowa($row);

                                          $guruLabel = $r['name']
                                              ?? $r['counselor_name']
                                              ?? $r['user_id']
                                              ?? '-';

                                          $classLabel = trim((string) ($r['class_names'] ?? ''));
                                          if ($classLabel === '') {
                                              $classLabel = '-';
                                          }
                                          ?>
                                          <tr>
                                              <td><?= esc($guruLabel) ?></td>
                                              <td><?= esc($classLabel) ?></td>
                                              <td class="text-end"><?= number_format((int) ($r['total'] ?? 0)) ?></td>
                                          </tr>
                                      <?php endforeach; ?>
                                  </tbody>
                              </table>
                          </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Belum ada data guru BK yang bisa diringkas.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Ringkasan Asesmen & Aktivitas Terbaru 
    <div class="row mt-3">
         Ringkasan Asesmen 
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title mb-2">
                        Ringkasan Penyelesaian Asesmen
                    </h5>
                    <?php if (!empty($assessmentCompletion)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Asesmen</th>
                                        <th class="text-end">Jumlah Jawaban</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assessmentCompletion as $row): ?>
                                        <?php $r = rowa($row); ?>
                                        <tr>
                                            <td><?= esc($r['title'] ?? ('Asesmen #' . ($r['assessment_id'] ?? '-'))) ?></td>
                                            <td class="text-end"><?= number_format((int) ($r['filled'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Belum ada data penyelesaian asesmen yang dapat ditampilkan.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

         Aktivitas Terbaru 
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-time-five align-middle me-1 text-info"></i>
                            Aktivitas Terbaru Layanan BK
                        </h5>
                        <small class="text-muted">
                            Mengambil <?= count($recentActivities) ?> aktivitas terakhir.
                        </small>
                    </div>

                    <?php if (!empty($recentActivities)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentActivities as $act): ?>
                                <?php
                                $a       = rowa($act);
                                $type    = strtolower((string) ($a['type'] ?? ''));
                                $created = $a['created_at'] ?? null;
                                $message = trim((string) ($a['message'] ?? ''));

                                $timeLabel = '-';
                                if (!empty($created)) {
                                    try {
                                        $t         = Time::parse($created);
                                        $timeLabel = $t->humanize();
                                    } catch (\Throwable $e) {
                                        $timeLabel = (string) $created;
                                    }
                                }

                                // Badge type
                                $badgeClass = 'bg-secondary';
                                $badgeText  = 'Aktivitas';

                                if (str_contains($type, 'session')) {
                                    $badgeClass = 'bg-info';
                                    $badgeText  = 'Sesi Konseling';
                                }
                                elseif (str_contains($type, 'violation')) {
                                    $badgeClass = 'bg-danger';
                                    $badgeText  = 'Pelanggaran';
                                }
                               /* elseif (str_contains($type, 'assessment')) {
                                    $badgeClass = 'bg-primary';
                                    $badgeText  = 'Asesmen';
                                }*/
                                elseif (str_contains($type, 'notification')) {
                                    $badgeClass = 'bg-dark';
                                    $badgeText  = 'Notifikasi';
                                }
                                ?>
                                <div class="list-group-item px-0 d-flex align-items-start">
                                    <div class="me-3">
                                        <span class="badge <?= $badgeClass ?> text-white rounded-pill">
                                            <?= esc($badgeText) ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">
                                            <?= esc($message !== '' ? $message : 'Aktivitas tanpa deskripsi.') ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= esc($timeLabel) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Belum ada aktivitas terbaru yang tercatat.
                        </p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>-->

</div><!-- /.container-fluid -->

<?= $this->endSection() ?>  <!-- ini menutup section('content') -->

<?= $this->section('scripts') ?>
<script>
    (function () {
        // Chart distribusi pelanggaran
        const ctxViol = document.getElementById('violations-by-level-chart');
        if (ctxViol && typeof Chart !== 'undefined') {
            const labelsViol = <?= json_encode(array_map(
                static fn($r) => (string) ($r['level'] ?? '-'),
                $violationsData
            )) ?>;
            const dataViol = <?= json_encode(array_map(
                static fn($r) => (int) ($r['total'] ?? 0),
                $violationsData
            )) ?>;

            new Chart(ctxViol, {
                type: 'bar',
                data: {
                    labels: labelsViol,
                    datasets: [{
                        label: 'Jumlah Pelanggaran',
                        data: dataViol,
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const value = context.parsed.y || 0;
                                    return ' ' + value + ' kasus';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Chart tren BK (pelanggaran, sesi, asesmen)
        const ctxTrend = document.getElementById('bk-trend-chart');
        if (ctxTrend && typeof Chart !== 'undefined') {
            const labelsTrend = <?= json_encode(array_values($trendLabels)) ?>;
            const dataViol = <?= json_encode(array_map('intval', $trendViolations)) ?>;
            const dataSess = <?= json_encode(array_map('intval', $trendSessions)) ?>;
            const dataAss  = <?= json_encode(array_map('intval', $trendAssessments)) ?>;

            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: labelsTrend,
                    datasets: [
                        { label: 'Pelanggaran', data: dataViol, borderWidth: 2, tension: 0.3 },
                        { label: 'Sesi Konseling', data: dataSess, borderWidth: 2, tension: 0.3 },
                        /*{ label: 'Asesmen', data: dataAss, borderWidth: 2, tension: 0.3 }*/
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    },
                    plugins: {
                        tooltip: { mode: 'index', intersect: false }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        }
        // Doughnut Chart: Pelanggaran per Kategori
        const ctxCat = document.getElementById('categoryChart');
        const catRaw = <?= json_encode($violationByCategory ?? []) ?>;

        if (ctxCat && typeof Chart !== 'undefined' && catRaw && catRaw.length) {
            const labels = catRaw.map(r => r.category_name || '');
            const values = catRaw.map(r => Number(r.count ?? 0));

            // Palet warna sederhana (disamakan dengan dashboard Wali Kelas)
            const baseColors = [
                'rgba(64,81,137,0.9)',
                'rgba(244,106,106,0.9)',
                'rgba(241,180,76,0.9)',
                'rgba(45,206,137,0.9)',
                'rgba(56,175,255,0.9)',
                'rgba(154,85,255,0.9)'
            ];
            const bgColors = values.map((v, i) => baseColors[i % baseColors.length]);
            const borderColors = bgColors.map(c => c.replace('0.9', '1'));

            new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: bgColors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const value = context.parsed || 0;
                                    return ' ' + value + ' kasus';
                                }
                            }
                        }
                    }
                }
            });
        }
    })();
</script>
<?= $this->endSection() ?>  <!-- ini menutup section('scripts') -->
