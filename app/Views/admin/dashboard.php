<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/dashboard.php
 * 
 * Admin Dashboard View
 * Menampilkan statistik dan overview sistem dengan Qovex Template
 * 
 * @package    SIB-K
 * @subpackage Views/Admin
 * @category   Dashboard
 * @author     Development Team
 * @created    2025-01-05
 */
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Dashboard Admin</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Message -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Selamat Datang, <?= esc($user['full_name']) ?>!</h5>
                        <p class="text-muted mb-0">
                            <i class="mdi mdi-calendar-check me-1"></i>
                            <?php if ($active_year): ?>
                                Tahun Ajaran Aktif: <strong><?= esc($active_year['year_name']) ?></strong> - Semester <strong><?= esc($active_year['semester']) ?></strong>
                            <?php else: ?>
                                <span class="text-danger">Belum ada tahun ajaran aktif</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <!-- Total Users -->
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stat bg-primary text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-start mini-stat-img me-4">
                        <i class="mdi mdi-account-group font-size-40"></i>
                    </div>
                    <h5 class="font-size-16 text-uppercase text-white-50">Total Pengguna</h5>
                    <h4 class="fw-medium font-size-24">
                        <?= number_format($stats['total_users']) ?>
                        <i class="mdi mdi-arrow-up text-success ms-2"></i>
                    </h4>
                </div>
                <div class="pt-2">
                    <div class="float-end">
                        <a href="<?= base_url('admin/users') ?>" class="text-white-50">
                            <i class="mdi mdi-arrow-right h5"></i>
                        </a>
                    </div>
                    <p class="text-white-50 mb-0 mt-1">Kelola Pengguna</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Students -->
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stat bg-success text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-start mini-stat-img me-4">
                        <i class="mdi mdi-school font-size-40"></i>
                    </div>
                    <h5 class="font-size-16 text-uppercase text-white-50">Total Siswa</h5>
                    <h4 class="fw-medium font-size-24">
                        <?= number_format($stats['total_students']) ?>
                    </h4>
                </div>
                <div class="pt-2">
                    <div class="float-end">
                        <a href="<?= base_url('admin/students') ?>" class="text-white-50">
                            <i class="mdi mdi-arrow-right h5"></i>
                        </a>
                    </div>
                    <p class="text-white-50 mb-0 mt-1">Kelola Siswa</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Students -->
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stat bg-info text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-start mini-stat-img me-4">
                        <i class="mdi mdi-account-check font-size-40"></i>
                    </div>
                    <h5 class="font-size-16 text-uppercase text-white-50">Siswa Aktif</h5>
                    <h4 class="fw-medium font-size-24">
                        <?= number_format($stats['total_active_students']) ?>
                    </h4>
                </div>
                <div class="pt-2">
                    <div class="float-end">
                        <a href="<?= base_url('admin/students') ?>" class="text-white-50">
                            <i class="mdi mdi-arrow-right h5"></i>
                        </a>
                    </div>
                    <p class="text-white-50 mb-0 mt-1">Lihat Detail</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Classes -->
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stat bg-warning text-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="float-start mini-stat-img me-4">
                        <i class="mdi mdi-google-classroom font-size-40"></i>
                    </div>
                    <h5 class="font-size-16 text-uppercase text-white-50">Total Kelas</h5>
                    <h4 class="fw-medium font-size-24">
                        <?= number_format($stats['total_classes']) ?>
                    </h4>
                </div>
                <div class="pt-2">
                    <div class="float-end">
                        <a href="<?= base_url('admin/classes') ?>" class="text-white-50">
                            <i class="mdi mdi-arrow-right h5"></i>
                        </a>
                    </div>
                    <p class="text-white-50 mb-0 mt-1">Kelola Kelas</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Distribution -->
<div class="row">
    <!-- Student Growth Chart -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-chart-line me-2"></i>Pertumbuhan Siswa (6 Bulan Terakhir)
                </h4>
                <div>
                    <canvas id="studentGrowthChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Users by Role -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-group me-2"></i>Pengguna Berdasarkan Role
                </h4>
                <div>
                    <canvas id="usersByRoleChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Distribution Statistics -->
<div class="row">
    <!-- Students by Grade -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-chart-bar me-2"></i>Siswa Berdasarkan Tingkat
                </h4>
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tingkat</th>
                                <th>Jumlah Siswa</th>
                                <th>Persentase</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['students_by_grade'])): ?>
                                <?php
                                $total = array_sum($stats['students_by_grade']);
                                foreach ($stats['students_by_grade'] as $grade => $count):
                                    $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                                    $progressColor = $grade == 'X' ? 'primary' : ($grade == 'XI' ? 'success' : 'info');
                                ?>
                                    <tr>
                                        <td>
                                            <h5 class="font-size-14 mb-0">
                                                <span class="badge bg-<?= $progressColor ?> font-size-12">
                                                    Kelas <?= esc($grade) ?>
                                                </span>
                                            </h5>
                                        </td>
                                        <td><?= number_format($count) ?> siswa</td>
                                        <td><?= $percentage ?>%</td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-<?= $progressColor ?>"
                                                    role="progressbar"
                                                    style="width: <?= $percentage ?>%"
                                                    aria-valuenow="<?= $percentage ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data siswa</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Students by Status -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-chart-donut me-2"></i>Siswa Berdasarkan Status
                </h4>
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th>Jumlah Siswa</th>
                                <th>Persentase</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['students_by_status'])): ?>
                                <?php
                                $total = array_sum($stats['students_by_status']);
                                $statusColors = [
                                    'Aktif' => 'success',
                                    'Alumni' => 'info',
                                    'Pindah' => 'warning',
                                    'Keluar' => 'danger'
                                ];
                                foreach ($stats['students_by_status'] as $status => $count):
                                    $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                                    $color = $statusColors[$status] ?? 'secondary';
                                ?>
                                    <tr>
                                        <td>
                                            <h5 class="font-size-14 mb-0">
                                                <span class="badge bg-<?= $color ?> font-size-12">
                                                    <?= esc($status) ?>
                                                </span>
                                            </h5>
                                        </td>
                                        <td><?= number_format($count) ?> siswa</td>
                                        <td><?= $percentage ?>%</td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-<?= $color ?>"
                                                    role="progressbar"
                                                    style="width: <?= $percentage ?>%"
                                                    aria-valuenow="<?= $percentage ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data siswa</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Data -->
<div class="row">
    <!-- Recent Students -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="mdi mdi-account-multiple-plus me-2"></i>Siswa Terbaru
                    </h4>
                    <div class="flex-shrink-0">
                        <a href="<?= base_url('admin/students') ?>" class="btn btn-sm btn-primary">
                            Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>NISN</th>
                                <th>Kelas</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['recent_students'])): ?>
                                <?php foreach ($stats['recent_students'] as $student): ?>
                                    <tr>
                                        <td>
                                            <h5 class="font-size-14 mb-0">
                                                <?= esc($student['full_name']) ?>
                                            </h5>
                                            <p class="text-muted mb-0 font-size-12">
                                                <?= esc($student['email']) ?>
                                            </p>
                                        </td>
                                        <td><?= esc($student['nisn']) ?></td>
                                        <td>
                                            <?php if ($student['class_name']): ?>
                                                <span class="badge bg-primary">
                                                    <?= esc($student['grade_level']) ?> - <?= esc($student['class_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Belum Ada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d M Y', strtotime($student['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada siswa terbaru</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="mdi mdi-account-plus me-2"></i>Pengguna Terbaru
                    </h4>
                    <div class="flex-shrink-0">
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-primary">
                            Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['recent_users'])): ?>
                                <?php foreach ($stats['recent_users'] as $user_item): ?>
                                    <tr>
                                        <td>
                                            <h5 class="font-size-14 mb-0">
                                                <?= esc($user_item['full_name']) ?>
                                            </h5>
                                            <p class="text-muted mb-0 font-size-12">
                                                <?= esc($user_item['email']) ?>
                                            </p>
                                        </td>
                                        <td><?= esc($user_item['username']) ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= esc($user_item['role_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($user_item['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada pengguna terbaru</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Student Growth Chart
    <?php if (!empty($stats['student_growth_chart']['labels'])): ?>
        const studentGrowthCtx = document.getElementById('studentGrowthChart').getContext('2d');
        new Chart(studentGrowthCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($stats['student_growth_chart']['labels']) ?>,
                datasets: [{
                    label: 'Jumlah Siswa Terdaftar',
                    data: <?= json_encode($stats['student_growth_chart']['data']) ?>,
                    borderColor: '#556ee6',
                    backgroundColor: 'rgba(85, 110, 230, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    <?php endif; ?>

    // Users by Role Chart
    <?php if (!empty($stats['users_by_role'])): ?>
        const usersByRoleCtx = document.getElementById('usersByRoleChart').getContext('2d');
        new Chart(usersByRoleCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($stats['users_by_role'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($stats['users_by_role'])) ?>,
                    backgroundColor: [
                        '#556ee6',
                        '#34c38f',
                        '#f46a6a',
                        '#50a5f1',
                        '#f1b44c',
                        '#7b6cb6'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' pengguna';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>
</script>

<?= $this->endSection() ?>