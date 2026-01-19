<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/students/index.php
 *
 * Counselor - Students Index
 * - List siswa binaan + filter + DataTables
 * - Tombol sinkron poin pelanggaran:
 *   Default: Tahun Ajaran aktif
 *   Opsi: Tahun ajaran dipilih / periode custom
 */

// Helpers aman-aman
try {
    if (!function_exists('show_alerts')) helper('app');
} catch (\Throwable $e) {}

try {
    if (!function_exists('user_avatar')) helper('auth');
} catch (\Throwable $e) {}

// Safe vars
$stats          = $stats ?? [];
$students       = $students ?? [];
$classes        = $classes ?? [];
$filters        = $filters ?? [];
$status_options = $status_options ?? ['Aktif','Alumni','Pindah','Keluar'];
$gender_options = $gender_options ?? ['L' => 'Laki-laki', 'P' => 'Perempuan'];

// Opsi tahun ajaran (kalau controller ngirim). Nama variabel dibikin fleksibel.
$academicYears = $academicYears ?? ($academic_years ?? ($year_options ?? []));
if (!is_array($academicYears)) $academicYears = [];

// Prefill untuk modal (kalau suatu saat kamu kirim filter TA/periode ke halaman ini)
$prefYear = trim((string)($filters['academic_year'] ?? ''));
$prefFrom = trim((string)($filters['date_from'] ?? ''));
$prefTo   = trim((string)($filters['date_to'] ?? ''));

// Biar tidak dobel alert jika show_alerts() sudah menangani flash
$useShowAlerts = function_exists('show_alerts');
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
<?php if ($useShowAlerts): ?>
    <?= show_alerts() ?>
<?php else: ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>
                    <?= esc(session()->getFlashdata('success')) ?>
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
                    <?= esc(session()->getFlashdata('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Siswa</p>
                        <h4 class="mb-0"><?= number_format((int)($stats['total'] ?? 0)) ?></h4>
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
                        <h4 class="mb-0"><?= number_format((int)($stats['active'] ?? 0)) ?></h4>
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
                        <h4 class="mb-0"><?= number_format((int)($stats['alumni'] ?? 0)) ?></h4>
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
                        <h4 class="mb-0">
                            <?= number_format((int)(($stats['moved'] ?? 0) + ($stats['dropped'] ?? 0))) ?>
                        </h4>
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

<!-- Filter Card -->
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
                                    <option value="<?= (int)$class['id'] ?>" <?= ($filters['class_id'] ?? '') == $class['id'] ? 'selected' : '' ?>>
                                        <?= esc($class['grade_level'] ?? '') ?> - <?= esc($class['class_name'] ?? '') ?>
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
                                    <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') == $status ? 'selected' : '' ?>>
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
                                    <option value="<?= esc($key) ?>" <?= ($filters['gender'] ?? '') == $key ? 'selected' : '' ?>>
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
                    <small class="text-muted">
                        Jika poin pelanggaran tidak sesuai, gunakan sinkronisasi jika ingin menghitung ulang berdasarkan Tahun Ajaran/periode.
                    </small>
                </div>

                <div class="text-sm-end d-flex gap-2 align-items-center">
                    <!-- Quick Sync (default: Tahun Ajaran aktif) -->
                    <form action="<?= base_url('counselor/students/sync-violation-points') ?>"
                          method="post"
                          class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="sync_mode" value="active">
                        <button type="submit"
                                class="btn btn-outline-warning btn-rounded waves-effect waves-light"
                                onclick="return confirm('Hitung ulang poin pelanggaran berdasarkan Tahun Ajaran AKTIF?\n\nTips: klik tombol Opsi jika ingin memilih Tahun Ajaran/periode lain.');">
                            <i class="mdi mdi-refresh me-1"></i> Sinkron (Aktif)
                        </button>
                    </form>

                    <!-- Sync Options -->
                    <button type="button"
                            class="btn btn-warning btn-rounded waves-effect waves-light"
                            data-bs-toggle="modal"
                            data-bs-target="#syncModal">
                        <i class="mdi mdi-tune-vertical me-1"></i> Opsi Sinkron
                    </button>
                </div>
            </div>

            <div class="card-body">
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
                            <?php if (!empty($students) && is_array($students)): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($students as $student): ?>
                                    <?php $points = (int) ($student['total_violation_points'] ?? 0); ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <img src="<?= function_exists('user_avatar') ? user_avatar($student['profile_photo'] ?? null) : base_url('assets/images/users/avatar-1.jpg') ?>"
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
                                            <span class="badge bg-<?= esc($statusColor) ?> font-size-12">
                                                <?= esc($status) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($points > 0): ?>
                                                <span class="badge bg-danger font-size-12"><?= $points ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success font-size-12">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('counselor/students/' . (int)$student['id']) ?>"
                                                   class="btn btn-sm btn-info"
                                                   data-bs-toggle="tooltip"
                                                   title="Profil">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>

                                                <?php if (!empty($canUpdate)): ?>
                                                    <a href="<?= base_url('counselor/students/' . (int)$student['id'] . '/edit') ?>"
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

<!-- Modal: Opsi Sinkron -->
<div class="modal fade" id="syncModal" tabindex="-1" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form action="<?= base_url('counselor/students/sync-violation-points') ?>" method="post" id="syncForm">
                <?= csrf_field() ?>

                <div class="modal-header">
                    <h5 class="modal-title" id="syncModalLabel">
                        <i class="mdi mdi-refresh me-1"></i> Opsi Sinkron Poin Pelanggaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb alert-info">
                        <i class="mdi mdi-information-outline me-1"></i>
                        Default sinkron mengikuti <b>Tahun Ajaran aktif</b>. Anda juga bisa memilih <b>Tahun Ajaran</b> tertentu atau <b>periode tanggal</b>.
                            <small>
                                Poin tersinkron akan tersimpan di database sebagai cache,
                                untuk ditampilkan pada aplikasi. Tolong pertimbangkan pilihan sebelum menggunakan.
                            </small>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Mode Sinkron</label>

                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sync_mode" id="sync_mode_active" value="active" checked>
                                    <label class="form-check-label" for="sync_mode_active">
                                        Sesuai Tahun Ajaran aktif (bawaan)
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sync_mode" id="sync_mode_year" value="year" <?= empty($academicYears) ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="sync_mode_year">
                                        Sesuai Tahun Ajaran yang Dipilih
                                        <?php if (empty($academicYears)): ?>
                                            <span class="text-muted">- opsi tahun ajaran belum dikirim dari controller</span>
                                        <?php endif; ?>
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sync_mode" id="sync_mode_range" value="range">
                                    <label class="form-check-label" for="sync_mode_range">
                                        Sesuai Periode/Tanggal yang Dipilih
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="academic_year" class="form-select" id="syncAcademicYear" <?= empty($academicYears) ? 'disabled' : '' ?>>
                                <option value="">- Pilih Tahun Ajaran -</option>
                                <?php foreach ($academicYears as $yn): ?>
                                    <?php $yn = trim((string)$yn); ?>
                                    <?php if ($yn === '') continue; ?>
                                    <option value="<?= esc($yn) ?>" <?= ($prefYear !== '' && $prefYear === $yn) ? 'selected' : '' ?>>
                                        <?= esc($yn) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Dipakai jika memilih: "Tahun Ajaran tertentu".</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" name="date_from" id="syncDateFrom" value="<?= esc($prefFrom) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" name="date_to" id="syncDateTo" value="<?= esc($prefTo) ?>">
                        </div>

                        <div class="col-12">
                            <hr>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="syncConfirm" name="sync_confirm" value="1" required>
                                <label class="form-check-label" for="syncConfirm">
                                    Saya paham sinkronisasi akan menghitung ulang poin dan memperbarui cache poin siswa.
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-warning" id="syncSubmitBtn">
                        <i class="mdi mdi-refresh me-1"></i> Jalankan Sinkron
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

        // DataTables
        var table;
        if (window.SIBK && typeof SIBK.initDataTable === 'function') {
            table = SIBK.initDataTable('studentsTable', {
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: [0, 8] }
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

        // Renumber agar konsisten saat sorting/searching
        function renumber() {
            if (!table || !table.page) return;
            var info = table.page.info();
            table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                cell.innerHTML = info.start + i + 1;
            });
        }
        if (table) {
            table.on('order.dt search.dt draw.dt', renumber);
            renumber();
        }

        // Auto-hide alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(a){
                a.classList.remove('show');
                a.classList.add('fade');
            });
        }, 5000);

        // Sync modal: enable/disable + required sesuai mode
        function applySyncMode() {
            var mode = document.querySelector('input[name="sync_mode"]:checked')?.value || 'active';
            var yearSel = document.getElementById('syncAcademicYear');
            var dFrom   = document.getElementById('syncDateFrom');
            var dTo     = document.getElementById('syncDateTo');

            // Reset required
            if (yearSel) yearSel.required = false;
            if (dFrom) dFrom.required = false;
            if (dTo) dTo.required = false;

            if (mode === 'active') {
                if (yearSel) yearSel.disabled = true;
                if (dFrom) dFrom.disabled = true;
                if (dTo) dTo.disabled = true;
            } else if (mode === 'year') {
                if (yearSel) {
                    yearSel.disabled = false;
                    yearSel.required = true;
                }
                if (dFrom) dFrom.disabled = true;
                if (dTo) dTo.disabled = true;
            } else { // range
                if (yearSel) yearSel.disabled = true;
                if (dFrom) { dFrom.disabled = false; dFrom.required = true; }
                if (dTo) { dTo.disabled = false; dTo.required = true; }
            }
        }

        document.querySelectorAll('input[name="sync_mode"]').forEach(function(r){
            r.addEventListener('change', applySyncMode);
        });
        applySyncMode();

        // Konfirmasi submit
        document.getElementById('syncForm')?.addEventListener('submit', function(e){
            var mode = document.querySelector('input[name="sync_mode"]:checked')?.value || 'active';
            var msg = 'Jalankan sinkronisasi poin pelanggaran?\n\n';
            if (mode === 'active') msg += 'Mode: Tahun Ajaran AKTIF';
            if (mode === 'year') msg += 'Mode: Tahun Ajaran tertentu';
            if (mode === 'range') msg += 'Mode: Periode custom';

            if (!confirm(msg)) {
                e.preventDefault();
                return false;
            }

            // Optional: disable button biar tidak dobel klik
            var btn = document.getElementById('syncSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Memproses...';
            }
        });
    });
</script>
<?= $this->endSection() ?>
