<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/students/index.php
 */
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Siswa Binaan</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Siswa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Siswa</p>
                        <h4 class="mb-0"><?= number_format($stats['total'] ?? 0) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title">
                                <i class="mdi mdi-school font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Siswa Aktif</p>
                        <h4 class="mb-0"><?= number_format($stats['active'] ?? 0) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                            <span class="avatar-title">
                                <i class="mdi mdi-account-check font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Alumni</p>
                        <h4 class="mb-0"><?= number_format($stats['alumni'] ?? 0) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                            <span class="avatar-title">
                                <i class="mdi mdi-school-outline font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Pindah/Keluar</p>
                        <h4 class="mb-0"><?= number_format(($stats['moved'] ?? 0) + ($stats['dropped'] ?? 0)) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                            <span class="avatar-title">
                                <i class="mdi mdi-account-off font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card (disamakan gaya dengan counselor/sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('counselor/students') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Kelas</label>
                            <select name="class_id" class="form-select">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= ($filters['class_id'] ?? '') == $class['id'] ? 'selected' : '' ?>>
                                        <?= esc($class['grade_level']) ?> - <?= esc($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tingkat</label>
                            <select name="grade_level" class="form-select">
                                <option value="">Semua Tingkat</option>
                                <option value="X"   <?= ($filters['grade_level'] ?? '') == 'X' ? 'selected' : '' ?>>X</option>
                                <option value="XI"  <?= ($filters['grade_level'] ?? '') == 'XI' ? 'selected' : '' ?>>XI</option>
                                <option value="XII" <?= ($filters['grade_level'] ?? '') == 'XII' ? 'selected' : '' ?>>XII</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <?php foreach ($status_options as $status): ?>
                                    <option value="<?= $status ?>" <?= ($filters['status'] ?? '') == $status ? 'selected' : '' ?>>
                                        <?= esc($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach ($gender_options as $key => $value): ?>
                                    <option value="<?= $key ?>" <?= ($filters['gender'] ?? '') == $key ? 'selected' : '' ?>>
                                        <?= esc($value) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Pencarian</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="NISN, NIS, Nama, Email..."
                                   value="<?= esc($filters['search'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('counselor/students') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Students Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">
                        <i class="mdi mdi-account-group me-2"></i>Daftar Siswa
                    </h4>
                </div>
                <div class="text-sm-end">
                    <form action="<?= base_url('counselor/students/sync-violation-points') ?>"
                          method="post"
                          class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit"
                                class="btn btn-outline-warning btn-rounded waves-effect waves-light"
                                onclick="return confirm('Hitung ulang poin pelanggaran semua siswa binaan Anda berdasarkan data pelanggaran yang masih aktif?');">
                            <i class="mdi mdi-refresh me-1"></i> Sinkron Poin Pelanggaran
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <p class="text-muted mb-3 font-size-12">
                    Jika Poin Pelanggaran tampak tidak sesuai atau memiliki kesalahan, gunakan tombol sinkronisasi.
                </p>

                <div class="table-responsive">
                    <table id="studentsTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Siswa</th>
                                <th>NISN</th>
                                <th>NIS</th>
                                <th>Kelas</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Poin</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($students as $student): ?>
                                    <?php $points = (int) ($student['total_violation_points'] ?? 0); ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <img src="<?= user_avatar($student['profile_photo'] ?? null) ?>"
                                                         alt="<?= esc($student['full_name'] ?? '-') ?>"
                                                         class="avatar-xs rounded-circle">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="font-size-14 mb-0">
                                                        <?= esc($student['full_name'] ?? '-') ?>
                                                    </h5>
                                                    <p class="text-muted mb-0 font-size-12">
                                                        <?= esc($student['email'] ?? '') ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code><?= esc($student['nisn'] ?? '-') ?></code></td>
                                        <td><code><?= esc($student['nis'] ?? '-') ?></code></td>
                                        <td>
                                            <?php if (!empty($student['class_name'])): ?>
                                                <span class="badge bg-primary">
                                                    <?= esc($student['grade_level'] ?? '') ?> - <?= esc($student['class_name'] ?? '') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Belum Ada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($student['gender'] ?? '') == 'L'): ?>
                                                <i class="mdi mdi-gender-male text-primary"></i> L
                                            <?php else: ?>
                                                <i class="mdi mdi-gender-female text-danger"></i> P
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'Aktif'  => 'success',
                                                'Alumni' => 'info',
                                                'Pindah' => 'warning',
                                                'Keluar' => 'danger'
                                            ];
                                            $status      = $student['status'] ?? 'Aktif';
                                            $statusColor = $statusColors[$status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?> font-size-12">
                                                <?= esc($status) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($points > 0): ?>
                                                <span class="badge bg-danger font-size-12"><?= $points ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success font-size-12">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('counselor/students/' . $student['id']) ?>"
                                                   class="btn btn-sm btn-info"
                                                   data-bs-toggle="tooltip"
                                                   title="Profil">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>

                                                <?php if (!empty($canUpdate)): ?>
                                                <a href="<?= base_url('counselor/students/' . $student['id'] . '/edit') ?>"
                                                   class="btn btn-sm btn-primary"
                                                   data-bs-toggle="tooltip"
                                                   title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="mdi mdi-account-off font-size-24 d-block mb-2"></i>
                                        Tidak ada data siswa
                                    </td>
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
<!-- DataTables (samakan dengan counselor/sessions) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

        // DataTables pagination di View (kotak + lengthMenu + default 10)
        var table;
        if (window.SIBK && typeof SIBK.initDataTable === 'function') {
            table = SIBK.initDataTable('studentsTable', {
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: [0, 8] } // No dan Aksi tidak perlu sort
                ]
            });
        } else {
            table = $('#studentsTable').DataTable({
                responsive: true,
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: [0, 8] }
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    zeroRecords: "Tidak ada data yang sesuai",
                    emptyTable: "Tidak ada data tersedia",
                    processing: "Memproses...",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
        }

        // Auto-hide alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(a){
                a.classList.remove('show');
                a.classList.add('fade');
            });
        }, 5000);
    });
</script>
<?= $this->endSection() ?>
