<?php

/**
 * File Path: app/Views/homeroom_teacher/dashboard.php
 *
 * Homeroom Teacher Dashboard View
 * Dashboard utama untuk Wali Kelas
 *
 * @package    SIB-K
 * @subpackage Views/HomeroomTeacher
 * @category   View
 * @author     Development Team
 * @created    2025-01-07
 */

$this->extend('layouts/main');
$this->section('content');

/**
 * Helper kecil khusus view ini
 * - violation_status_badge_class: mapping status pelanggaran -> warna badge yang kontras
 */
if (!function_exists('violation_status_badge_class')) {
    function violation_status_badge_class($status): string
    {
        $s = strtolower(trim((string) $status));

        if ($s === '') {
            return 'badge-soft-secondary text-secondary';
        }

        if (str_contains($s, 'baru') || str_contains($s, 'terlapor')) {
            return 'badge-soft-info text-info';
        }

        if (str_contains($s, 'proses') || str_contains($s, 'penanganan') || str_contains($s, 'diproses')) {
            return 'badge-soft-warning text-warning';
        }

        if (str_contains($s, 'selesai') || str_contains($s, 'tuntas') || str_contains($s, 'ditutup')) {
            return 'badge-soft-success text-success';
        }

        if (str_contains($s, 'batal') || str_contains($s, 'dibatalkan') || str_contains($s, 'dihapus')) {
            return 'badge-soft-danger text-danger';
        }

        return 'badge-soft-secondary text-secondary';
    }
}

?>

<?php if (!$hasClass): ?>
    <!-- No Class Assigned -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="mdi mdi-alert-circle-outline text-warning" style="font-size: 64px;"></i>
                    <h4 class="mt-3">Belum Ada Kelas yang Ditugaskan</h4>
                    <p class="text-muted"><?= esc($message) ?></p>
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

    <!-- Welcome Card -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card welcome-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="text-white mb-2">Selamat Datang, <?= esc($currentUser['full_name']) ?>!</h4>
                            <p class="text-white-50 mb-0">
                                Anda adalah Wali Kelas <strong><?= esc($class['class_name']) ?></strong> -
                                Tahun Ajaran <?= esc($class['year_name']) ?> Semester <?= esc($class['semester']) ?>
                            </p>
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
                                    <i class="mdi mdi-file-chart me-1"></i> Lihat Laporan Kelas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <!-- Total Students (only active) -->
        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <?php
                        // Hitung total siswa aktif dari distribusi gender
                        $genderMale   = (int) ($stats['gender_distribution']['male'] ?? 0);
                        $genderFemale = (int) ($stats['gender_distribution']['female'] ?? 0);
                        $totalActiveStudents = $genderMale + $genderFemale;
                    ?>
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-2">Total Siswa (Aktif)</p>
                            <h4 class="mb-0 counter"><?= number_format($totalActiveStudents) ?></h4>
                        </div>
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-soft-primary align-self-center">
                            <span class="avatar-title rounded-circle bg-primary">
                                <i class="mdi mdi-account-group font-size-24 text-white"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="mdi mdi-gender-male text-info"></i> <?= $genderMale ?> Laki-laki
                            <span class="mx-2">|</span>
                            <i class="mdi mdi-gender-female text-danger"></i> <?= $genderFemale ?> Perempuan
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violations This Month -->
        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-2">Pelanggaran Bulan Ini</p>
                            <h4 class="mb-0 counter"><?= number_format($stats['violations_this_month']) ?></h4>
                        </div>
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-soft-danger align-self-center">
                            <span class="avatar-title">
                                <i class="mdi mdi-alert-circle-outline font-size-24 text-danger"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <?php if ($stats['violation_trend'] === 'up'): ?>
                            <small class="text-danger">
                                <i class="mdi mdi-arrow-up"></i> <?= abs($stats['violation_change_percentage']) ?>% dari bulan lalu
                            </small>
                        <?php elseif ($stats['violation_trend'] === 'down'): ?>
                            <small class="text-success">
                                <i class="mdi mdi-arrow-down"></i> <?= abs($stats['violation_change_percentage']) ?>% dari bulan lalu
                            </small>
                        <?php else: ?>
                            <small class="text-muted">
                                <i class="mdi mdi-minus"></i> Tidak ada perubahan
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violations This Week -->
        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-2">Pelanggaran Minggu Ini</p>
                            <h4 class="mb-0 counter"><?= number_format($stats['violations_this_week']) ?></h4>
                        </div>
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-soft-warning align-self-center">
                            <span class="avatar-title">
                                <i class="mdi mdi-alert font-size-24 text-warning"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            Hitungan dalam satu Minggu
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students in Counseling -->
        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-2">Dalam Konseling</p>
                            <h4 class="mb-0 counter"><?= number_format($stats['students_in_counseling']) ?></h4>
                        </div>
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-soft-success align-self-center">
                            <span class="avatar-title rounded-circle bg-success">
                                <i class="mdi mdi-comment-account-outline font-size-24 text-white"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            Siswa bulan ini
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Violation Trends Chart -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-chart-line text-primary me-2"></i>Tren Layanan BK (6 bulan terakhir)
                        </h5>
                        <small class="text-muted">Pelanggaran • Sesi</small>
                    </div>

                    <div class="position-relative" style="height:260px; width:100%;">
                        <canvas id="bk-trend-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violation by Category -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-chart-donut text-info me-2"></i>Pelanggaran per Kategori
                        </h5>
                        <small class="text-muted"><?= esc($categoryRangeLabel ?? '6 bulan terakhir') ?></small>
                    </div>
                    <div class="chart-container" style="height: 260px;">
                        <?php if (!empty($violationByCategory)): ?>
                             <div class="position-relative" style="height:240px; width:100%;">
                            <canvas id="categoryChart"></canvas>
                            </div>
                            <div class="mt-2 small text-muted">
                                Menampilkan <?= min(5, count($violationByCategory)) ?> kategori teratas berdasarkan jumlah kasus.
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="mdi mdi-information-outline font-size-24 text-muted"></i>
                                <p class="text-muted mt-2">Belum ada data pelanggaran</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Violations and Right Column Widgets -->
    <div class="row">
        <!-- Recent Violations -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-clipboard-alert text-danger me-2"></i>Pelanggaran Terbaru (7 Hari)
                        </h5>
                        <a href="<?= base_url('homeroom/violations') ?>" class="btn btn-sm btn-soft-primary">
                            Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <?php if (!empty($recentViolations)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Siswa</th>
                                        <th>Kategori</th>
                                        <th>Poin</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recentViolations, 0, 5) as $violation): ?>
                                        <tr>
                                            <td>
                                                <small><?= format_indo_short($violation['violation_date']) ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-2">
                                                        <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                            <?= strtoupper(substr($violation['student_name'], 0, 1)) ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 font-size-14"><?= esc($violation['student_name']) ?></h6>
                                                        <small class="text-muted"><?= esc($violation['nisn']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-soft-<?= $violation['severity_level'] === 'Berat'
                                                    ? 'danger'
                                                    : ($violation['severity_level'] === 'Sedang' ? 'warning' : 'info') ?>">
                                                    <?= esc($violation['category_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="point-indicator <?= $violation['point_deduction'] >= 50
                                                    ? 'high'
                                                    : ($violation['point_deduction'] >= 25 ? 'medium' : 'low') ?>">
                                                    <?= $violation['point_deduction'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php $status = $violation['status'] ?? '-'; ?>
                                                <!-- Badge status sekarang menggunakan warna yang kontras, tidak lagi putih -->
                                                <span class="badge <?= violation_status_badge_class($status) ?>">
                                                    <?= esc($status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('homeroom/violations/detail/' . $violation['id']) ?>"
                                                   class="btn btn-sm btn-soft-info"
                                                   data-bs-toggle="tooltip"
                                                   title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="mdi mdi-emoticon-happy-outline text-success font-size-48"></i>
                            <p class="text-muted mt-2">Tidak ada pelanggaran dalam 7 hari terakhir</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Siswa Perlu Perhatian + Top Violators + Aksi Cepat -->
        <div class="col-xl-4">

            <!-- 3.1 Widget Siswa Perlu Perhatian -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="mdi mdi-account-alert-outline text-danger me-2"></i>Siswa Perlu Perhatian
                    </h5>

                    <?php $attentionStudents = $attentionStudents ?? []; ?>

                    <?php if (!empty($attentionStudents)): ?>
                        <p class="text-muted small mb-2">
                            Ringkasan siswa dengan poin pelanggaran tinggi atau status khusus di kelas Anda.
                        </p>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($attentionStudents, 0, 5) as $student): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title rounded-circle bg-soft-danger text-danger font-size-16 fw-bold">
                                                    <?= strtoupper(substr($student['full_name'] ?? $student['student_name'] ?? 'S', 0, 1)) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 font-size-14">
                                                <?= esc($student['full_name'] ?? $student['student_name'] ?? 'Tanpa Nama') ?>
                                            </h6>
                                            <?php if (!empty($student['nisn'])): ?>
                                                <small class="text-muted d-block"><?= esc($student['nisn']) ?></small>
                                            <?php endif; ?>

                                            <?php if (!empty($student['status'])): ?>
                                                <span class="badge mt-1 <?= esc($student['status_class'] ?? 'badge-soft-warning text-warning') ?>">
                                                    <?= esc($student['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-shrink-0 text-end">
                                            <?php if (isset($student['total_points'])): ?>
                                                <h6 class="mb-0 text-danger">
                                                    <?= (int) $student['total_points'] ?> poin
                                                </h6>
                                            <?php endif; ?>
                                            <?php if (isset($student['violation_count'])): ?>
                                                <small class="text-muted">
                                                    <?= (int) $student['violation_count'] ?> pelanggaran
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-end">
                            <a href="<?= base_url('homeroom/reports') ?>" class="btn btn-sm btn-soft-primary">
                                Lihat Rekap Kelas
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="mdi mdi-check-circle-outline text-success font-size-36"></i>
                            <p class="text-muted mt-2 mb-0">
                                Belum ada siswa yang ditandai perlu perhatian khusus.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Violators -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="mdi mdi-account-alert text-warning me-2"></i>Siswa dengan Poin Tertinggi
                    </h5>

                    <?php if (!empty($topViolators)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topViolators as $index => $student): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title rounded-circle bg-soft-<?= $index === 0 ? 'danger' : 'warning' ?> text-<?= $index === 0 ? 'danger' : 'warning' ?> font-size-16 fw-bold">
                                                    #<?= $index + 1 ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 font-size-14"><?= esc($student['full_name']) ?></h6>
                                            <small class="text-muted"><?= esc($student['nisn']) ?></small>
                                        </div>
                                        <div class="flex-shrink-0 text-end">
                                            <h6 class="mb-0 text-<?= $student['total_points'] >= 75 ? 'danger' : 'warning' ?>">
                                                <?= $student['total_points'] ?> poin
                                            </h6>
                                            <small class="text-muted"><?= $student['violation_count'] ?> pelanggaran</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="mdi mdi-emoticon-happy text-success font-size-36"></i>
                            <p class="text-muted mt-2 mb-0">Tidak ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3.3 Shortcut Aksi Cepat -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="mdi mdi-flash text-primary me-2"></i>Aksi Cepat
                    </h5>
                    <p class="text-muted small">
                        Shortcut untuk tugas harian Wali Kelas.
                    </p>
                </div>
            </div>

        </div>
    </div>

<?php endif; ?>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function () {
        <?php if ($hasClass): ?>

        // Bootstrap tooltip untuk tombol "Lihat Detail"
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // --------------------------
        // Line Chart: Tren Layanan BK (Pelanggaran + Sesi)
        // --------------------------
        const ctxTrend = document.getElementById('bk-trend-chart');
        const labelsTrend = <?= json_encode(array_values($trendLabels ?? [])) ?>;
        const dataViol = <?= json_encode(array_map('intval', $trendViolations ?? [])) ?>;
        const dataSess = <?= json_encode(array_map('intval', $trendSessions ?? [])) ?>;

        if (ctxTrend && typeof Chart !== 'undefined' && labelsTrend.length) {
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: labelsTrend,
                    datasets: [
                        { label: 'Pelanggaran', data: dataViol, borderWidth: 2, tension: 0.3 },
                        { label: 'Sesi Konseling', data: dataSess, borderWidth: 2, tension: 0.3 }
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
                        legend: { display: true, position: 'top' }
                    }
                }
            });
        }

        // --------------------------
        // Doughnut Chart: Pelanggaran per Kategori
        // --------------------------
        const catRaw = <?= json_encode($violationByCategory ?? []) ?>;

        if (catRaw && catRaw.length && document.getElementById('categoryChart')) {
            const ctx2 = document.getElementById('categoryChart').getContext('2d');
            const labels = catRaw.map(r => r.category_name || '');
            // gunakan field "count" dari query
            const values = catRaw.map(r => Number(r.count ?? 0));

            // Palet warna sederhana
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

            new Chart(ctx2, {
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
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        <?php endif; ?>
    });
</script>
<?php $this->endSection(); ?>
