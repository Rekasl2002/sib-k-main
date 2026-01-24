<?php

/**
 * File Path: app/Views/counselor/dashboard.php
 *
 * Counselor Dashboard View
 * Dashboard untuk Guru BK dengan statistik, chart, dan data sesi konseling
 *
 * @package    SIB-K
 * @subpackage Views/Counselor
 * @category   Dashboard
 * @author     Development Team
 * @created    2025-01-06
 */

$this->extend('layouts/main');
$this->section('content');

$currentUser        = $currentUser ?? ['full_name' => session('full_name') ?? session('name') ?? 'Guru BK'];
$activeAcademic     = $activeAcademic ?? [];
$assignedClasses    = $assignedClasses ?? [];
$violationByCategory = $violationByCategory ?? [];

?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Dashboard GURU BK</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Welcome + Tombol Cepat -->
<div class="row">
    <div class="col-12">
        <div class="card welcome-card">
            <div class="card-body">
                <div class="row align-items-start g-3">

                    <!-- KIRI: Teks Welcome -->
                    <div class="col-lg-7">
                        <h4 class="text-white mb-2">
                            Selamat Datang, <?= esc($currentUser['full_name'] ?? 'Guru BK') ?>!
                        </h4>

                        <p class="text-white-50 mb-3">
                            Anda adalah <strong>Guru BK</strong>
                            <?php if (!empty($activeAcademic['year_name'])): ?>
                                <span class="ms-1">• Tahun Ajaran <?= esc($activeAcademic['year_name']) ?> Semester <?= esc($activeAcademic['semester'] ?? '-') ?></span>
                            <?php endif; ?>
                        </p>

                        <div>
                            <div class="text-white-50 mb-2">Kelas binaan:</div>

                            <?php if (!empty($assignedClasses)): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($assignedClasses as $c): ?>
                                        <span class="badge bg-light text-dark">
                                            <?= esc($c['class_name'] ?? '-') ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-white">-</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- KANAN: Tombol Cepat -->
                    <div class="col-lg-5">
                        <div class="d-flex justify-content-lg-end">
                            <div class="w-100" style="max-width: 420px;">
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <a href="<?= base_url('counselor/sessions/create') ?>" class="btn btn-light w-100 text-start">
                                            <i class="mdi mdi-plus-circle me-1"></i> Tambah Sesi Baru
                                        </a>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <a href="<?= base_url('counselor/sessions') ?>" class="btn btn-light w-100 text-start">
                                            <i class="mdi mdi-calendar-check me-1"></i> Lihat Semua Sesi
                                        </a>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <a href="<?= base_url('counselor/cases') ?>" class="btn btn-light w-100 text-start">
                                            <i class="mdi mdi-alert-circle-outline me-1"></i> Kelola Pelanggaran
                                        </a>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <a href="<?= base_url('counselor/reports') ?>" class="btn btn-light w-100 text-start">
                                            <i class="mdi mdi-file-chart me-1"></i> Laporan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /row -->
            </div>
        </div>
    </div>
</div>


<!-- Statistics Cards -->
<div class="row">
    <!-- Total Sessions -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Total Sesi</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)($stats['total_sessions'] ?? 0) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 text-end dash-widget">
                        <div class="avatar-sm rounded-circle bg-soft-primary">
                            <span class="avatar-title bg-primary rounded-circle">
                                <i class="mdi mdi-calendar-check font-size-24 text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sessions Today -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Sesi Hari Ini</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)($stats['sessions_today'] ?? 0) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 text-end dash-widget">
                        <div class="avatar-sm rounded-circle bg-soft-success">
                            <span class="avatar-title bg-success rounded-circle">
                                <i class="mdi mdi-clock-outline font-size-24 text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sessions This Month -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Sesi Bulan Ini</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)($stats['sessions_this_month'] ?? 0) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 text-end dash-widget">
                        <div class="avatar-sm rounded-circle bg-soft-info">
                            <span class="avatar-title bg-info rounded-circle">
                                <i class="mdi mdi-calendar-month font-size-24 text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Sesi Mendatang</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)($stats['upcoming_sessions'] ?? 0) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 text-end dash-widget">
                        <div class="avatar-sm rounded-circle bg-soft-warning">
                            <span class="avatar-title bg-warning rounded-circle">
                                <i class="mdi mdi-calendar-clock font-size-24 text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row: Session Trend -->
<div class="row">
    <!-- Chart: Session Trends -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Tren Sesi Konseling (6 Bulan Terakhir)</h4>
            </div>
            <div class="card-body">
                <canvas id="sessionTrendChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Pelanggaran per Kategori -->
    <div class="col-xl-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-chart-donut text-info me-1"></i>Pelanggaran per Kategori
                    </h5>
                    <small class="text-muted"><?= esc($categoryRangeLabel ?? '6 bulan terakhir') ?></small>
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

<!-- Row: Violation + Sanction Trend -->
<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Tren Pelanggaran/Kasus (6 Bulan Terakhir)</h4>
            </div>
            <div class="card-body">
                <canvas id="violationTrendChart" height="260"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Tren Sanksi (6 Bulan Terakhir)</h4>
            </div>
            <div class="card-body">
                <canvas id="sanctionTrendChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Row: Today + Upcoming -->
<div class="row">
    <!-- Today's Sessions -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Sesi Hari Ini</h4>
                <div class="flex-shrink-0">
                    <span class="badge bg-primary"><?= count($todaySessions ?? []) ?> Sesi</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($todaySessions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Siswa/Topik</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($todaySessions as $session): ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold">
                                            <?= !empty($session['session_time']) ? date('H:i', strtotime($session['session_time'])) : '-' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0"><?= esc($session['topic'] ?? '-') ?></h6>
                                            <?php if (!empty($session['student_name'])): ?>
                                                <small class="text-muted"><?= esc($session['student_name']) ?></small>
                                            <?php elseif (!empty($session['class_name'])): ?>
                                                <small class="text-muted">Kelas <?= esc($session['class_name']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $type = $session['session_type'] ?? '';
                                        $typeClass = match ($type) {
                                            'Individu' => 'info',
                                            'Kelompok' => 'warning',
                                            'Klasikal' => 'primary',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $typeClass ?>"><?= esc($type ?: '-') ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $session['status'] ?? '';
                                        $statusClass = match ($status) {
                                            'Dijadwalkan' => 'warning',
                                            'Selesai' => 'success',
                                            'Dibatalkan' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= esc($status ?: '-') ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('counselor/sessions/detail/' . ($session['id'] ?? 0)) ?>"
                                           class="btn btn-sm btn-soft-primary">
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
                        <i class="mdi mdi-calendar-blank text-muted" style="font-size: 48px;"></i>
                        <p class="text-muted mt-2">Tidak ada sesi konseling hari ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Sesi Mendatang (7 Hari)</h4>
                <div class="flex-shrink-0">
                    <span class="badge bg-warning"><?= count($upcomingSessions ?? []) ?> Sesi</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($upcomingSessions)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingSessions as $session): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                <?= !empty($session['session_date']) ? date('d', strtotime($session['session_date'])) : '-' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="mb-1 text-truncate"><?= esc($session['topic'] ?? '-') ?></h6>
                                        <p class="text-muted text-truncate mb-2">
                                            <i class="mdi mdi-calendar me-1"></i>
                                            <?= !empty($session['session_date']) ? date('d M Y', strtotime($session['session_date'])) : '-' ?>
                                            <?php if (!empty($session['session_time'])): ?>
                                                | <i class="mdi mdi-clock-outline me-1"></i>
                                                <?= date('H:i', strtotime($session['session_time'])) ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($session['student_name'])): ?>
                                            <small class="text-muted">
                                                <i class="mdi mdi-account me-1"></i><?= esc($session['student_name']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="<?= base_url('counselor/sessions/detail/' . ($session['id'] ?? 0)) ?>"
                                           class="btn btn-sm btn-soft-info">
                                            Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="mdi mdi-calendar-check text-muted" style="font-size: 48px;"></i>
                        <p class="text-muted mt-2">Tidak ada sesi mendatang</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row: Assigned Students + Activities -->
<div class="row">
    <!-- Assigned Students -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Siswa Binaan</h4>
                <div class="flex-shrink-0">
                    <span class="badge bg-success"><?= count($assignedStudents ?? []) ?> Siswa</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($assignedStudents)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Sesi</th>
                                <th>Poin</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach (array_slice($assignedStudents, 0, 5) as $student): ?>
                                <tr>
                                    <td><?= esc($student['nisn'] ?? '-') ?></td>
                                    <td>
                                        <h6 class="mb-0"><?= esc($student['student_name'] ?? '-') ?></h6>
                                    </td>
                                    <td><?= esc($student['class_name'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= (int)($student['total_sessions'] ?? 0) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $points = (int)($student['total_violation_points'] ?? 0);
                                        $pointClass = $points > 50 ? 'danger' : ($points > 20 ? 'warning' : 'success');
                                        ?>
                                        <span class="badge bg-<?= $pointClass ?>"><?= $points ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (count($assignedStudents) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="<?= base_url('counselor/students') ?>" class="btn btn-sm btn-link">
                                Lihat Semua Siswa <i class="mdi mdi-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="mdi mdi-account-group text-muted" style="font-size: 48px;"></i>
                        <p class="text-muted mt-2">Belum ada siswa binaan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activities & Pending Follow-ups -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#activities" role="tab">
                            Aktivitas Terbaru
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#pending" role="tab">
                            Perlu Follow-up
                            <?php if (!empty($pendingSessions)): ?>
                                <span class="badge bg-danger rounded-pill"><?= count($pendingSessions) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <!-- Activities Tab -->
                    <div class="tab-pane active" id="activities" role="tabpanel">
                        <?php if (!empty($recentActivities)): ?>
                            <ul class="list-unstyled activity-wid mb-0">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php
                                    $aUrl = $activity['url'] ?? null;
                                    ?>
                                    <li class="activity-list activity-border">
                                        <div class="activity-icon avatar-xs">
                                            <span class="avatar-title bg-soft-<?= esc($activity['color'] ?? 'secondary') ?> text-<?= esc($activity['color'] ?? 'secondary') ?> rounded-circle">
                                                <i class="mdi <?= esc($activity['icon'] ?? 'mdi-information-outline') ?>"></i>
                                            </span>
                                        </div>
                                        <div class="timeline-list-item">
                                            <div class="d-flex">
                                                <div class="flex-grow-1 overflow-hidden me-4">
                                                    <h6 class="font-size-14 mb-1">
                                                        <?php if ($aUrl): ?>
                                                            <a href="<?= esc($aUrl) ?>" class="text-decoration-none">
                                                                <?= esc($activity['title'] ?? '-') ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <?= esc($activity['title'] ?? '-') ?>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="text-truncate text-muted mb-0"><?= esc($activity['description'] ?? '-') ?></p>
                                                </div>
                                                <div class="flex-shrink-0 text-end">
                                                    <small class="text-muted"><?= esc($activity['time'] ?? '-') ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Belum ada aktivitas terbaru</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Follow-ups Tab -->
                    <div class="tab-pane" id="pending" role="tabpanel">
                        <?php if (!empty($pendingSessions)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pendingSessions as $session): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= esc($session['topic'] ?? '-') ?></h6>
                                                <p class="text-muted mb-1">
                                                    <small>
                                                        <i class="mdi mdi-calendar me-1"></i>
                                                        <?= !empty($session['session_date']) ? date('d M Y', strtotime($session['session_date'])) : '-' ?>
                                                    </small>
                                                </p>
                                                <?php if (!empty($session['student_name'])): ?>
                                                    <small class="text-muted">
                                                        <i class="mdi mdi-account me-1"></i><?= esc($session['student_name']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <a href="<?= base_url('counselor/sessions/detail/' . ($session['id'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-soft-warning">
                                                    <i class="mdi mdi-bell-ring"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="mdi mdi-check-all text-success" style="font-size: 48px;"></i>
                                <p class="text-muted mt-2 mb-0">Semua sesi sudah ditindaklanjuti</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Counter animation
    const counters = document.querySelectorAll('.counter-value');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target') || '0', 10);
        const duration = 1000;
        const frames = Math.max(1, Math.floor(duration / 16));
        const increment = target / frames;
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };

        updateCounter();
    });

    // ===== Session Trend Chart =====
    const chartData = <?= json_encode($chartData ?? ['labels' => [], 'individual' => [], 'group' => [], 'class' => []]) ?>;

    const ctx = document.getElementById('sessionTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels || [],
                datasets: [
                    {
                        label: 'Individu',
                        data: chartData.individual || [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Kelompok',
                        data: chartData.group || [],
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Klasikal',
                        data: chartData.class || [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // ===== Violation Trend Chart =====
    const violationChartData = <?= json_encode($violationChartData ?? ['labels'=>[], 'reported'=>[], 'in_process'=>[], 'completed'=>[]]) ?>;
    const vctx = document.getElementById('violationTrendChart');

    if (vctx) {
        new Chart(vctx, {
            type: 'line',
            data: {
                labels: violationChartData.labels || [],
                datasets: [
                    {
                        label: 'Dilaporkan',
                        data: violationChartData.reported || [],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Dalam Proses',
                        data: violationChartData.in_process || [],
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Selesai',
                        data: violationChartData.completed || [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // ===== Sanction Trend Chart =====
    const sanctionChartData = <?= json_encode($sanctionChartData ?? ['labels'=>[], 'scheduled'=>[], 'ongoing'=>[], 'completed'=>[]]) ?>;
    const sctx = document.getElementById('sanctionTrendChart');

    if (sctx) {
        new Chart(sctx, {
            type: 'line',
            data: {
                labels: sanctionChartData.labels || [],
                datasets: [
                    {
                        label: 'Dijadwalkan',
                        data: sanctionChartData.scheduled || [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Sedang Berjalan',
                        data: sanctionChartData.ongoing || [],
                        borderColor: 'rgb(153, 102, 255)',
                        backgroundColor: 'rgba(153, 102, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Selesai',
                        data: sanctionChartData.completed || [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // ===== Doughnut Chart: Pelanggaran per Kategori =====
    const catRaw = <?= json_encode($violationByCategory ?? []) ?>;

    if (catRaw && catRaw.length && document.getElementById('categoryChart')) {
        const ctxCat = document.getElementById('categoryChart').getContext('2d');

        const labels = catRaw.map(r => r.category_name || '');
        const values = catRaw.map(r => Number(r.count ?? 0));

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
                    legend: { position: 'bottom' }
                },
                cutout: '60%'
            }
        });
    }

    // Auto-refresh stats every 5 minutes (optional)
    setInterval(function () {
        fetch('<?= base_url('counselor/dashboard/getQuickStats') ?>', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.success && data.data) {
                const keys = ['total_sessions', 'sessions_today', 'sessions_this_month', 'upcoming_sessions'];
                document.querySelectorAll('.counter-value').forEach((el, index) => {
                    const k = keys[index];
                    if (!k) return;
                    const val = parseInt(data.data[k] || 0, 10);
                    el.setAttribute('data-target', val);
                    el.textContent = val;
                });
            }
        })
        .catch(error => console.error('Error refreshing stats:', error));
    }, 300000);
});
</script>

<?php $this->endSection(); ?>
