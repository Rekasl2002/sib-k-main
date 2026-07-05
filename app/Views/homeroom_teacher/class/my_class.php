<?php // app/Views/homeroom_teacher/class/my_class.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/homeroom_teacher/class/my_class.php
 * Kelas Binaan Wali Kelas — daftar siswa + Filter/Saring + DataTables (ala koordinator/users).
 */

helper('permission');

$pageTitle       = $pageTitle ?? 'Kelas Binaan';
$class           = is_array($class ?? null) ? $class : [];
$activeYear      = is_array($activeYear ?? null) ? $activeYear : [];
$stats           = is_array($stats ?? null) ? $stats : [];
$students        = is_array($students ?? null) ? $students : [];
$isMultipleClass = !empty($class['is_multiple']);
$studentCount    = count($students);

// Indeks kolom (0-based) tergantung ada/tidaknya kolom Kelas (isMultipleClass)
// Kolom: #, Nama, NISN/NIK, Gender, [Kelas jika multiple], Orang Tua, Status, Aksi
$genderColIdx = 3;
$aksiColIdx   = $isMultipleClass ? 7 : 6;
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="mdi mdi-google-classroom me-2"></i><?= esc($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Wali Kelas</a></li>
                    <li class="breadcrumb-item active">Kelas Binaan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $fk => $fc): ?>
    <?php if (session()->getFlashdata($fk)): ?>
        <div class="alert alert-<?= $fc ?> alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata($fk)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (empty($class)): ?>
    <div class="alert alert-warning">
        <i class="mdi mdi-alert me-2"></i>
        Anda belum terhubung dengan kelas manapun pada tahun ajaran aktif. Silakan hubungi Koordinator BK.
    </div>
<?php else: ?>

<!-- Info Kelas + Statistik -->
<div class="row">
    <!-- Info Kelas -->
    <div class="col-md-6 col-xl-4">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-1">Kelas Binaan</p>
                        <h5 class="mb-1">
                            <?= esc($isMultipleClass
                                ? ($class['class_count'] ?? '') . ' kelas: ' . ($class['class_name'] ?? '-')
                                : ($class['class_name'] ?? '-')) ?>
                        </h5>
                        <p class="mb-0 text-muted small">
                            Tingkat <?= esc($class['grade_level'] ?? '-') ?>
                            <?= !empty($class['major']) ? ' · ' . esc($class['major']) : '' ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title"><i class="mdi mdi-google-classroom font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Siswa -->
    <div class="col-md-2 col-xl-2">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-2">Total Siswa</p>
                        <h4 class="mb-0 text-dark"><?= (int)($stats['total_students'] ?? $studentCount) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                            <span class="avatar-title"><i class="mdi mdi-account-group font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-xl-2">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-2">Laki-laki</p>
                        <h4 class="mb-0 text-dark"><?= (int)($stats['total_male'] ?? 0) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                            <span class="avatar-title"><i class="mdi mdi-gender-male font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-xl-2">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-2">Perempuan</p>
                        <h4 class="mb-0 text-dark"><?= (int)($stats['total_female'] ?? 0) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle" style="background-color:#d63384;">
                            <span class="avatar-title"><i class="mdi mdi-gender-female font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tahun Ajaran -->
    <?php if (!empty($activeYear)): ?>
    <div class="col-md-2 col-xl-2">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-1">Tahun Ajaran</p>
                        <h6 class="mb-0 text-dark"><?= esc($activeYear['year_name'] ?? '-') ?></h6>
                        <p class="mb-0 text-muted small"><?= esc($activeYear['semester'] ?? '') ?></p>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                            <span class="avatar-title"><i class="mdi mdi-calendar-check font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Filter/Saring Card -->
<div class="row">
    <div class="col-12">
        <div class="card filter-compact">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 text-dark">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data
                </h5>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Jenis Kelamin</label>
                        <select id="genderFilter" class="form-select">
                            <option value="">Semua Jenis Kelamin</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Pencarian</label>
                        <input type="text" id="searchInput" class="form-control"
                               placeholder="Cari nama, NISN, NIK, atau orang tua...">
                    </div>
                </div>
                <div class="row mt-2 g-3">
                    <div class="col-md-2">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" id="btnFilter" class="btn btn-primary w-100">
                            <i class="mdi mdi-magnify me-1"></i> Filter/Saring
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" id="btnReset" class="btn btn-secondary w-100">
                            <i class="mdi mdi-refresh me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Daftar Siswa -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Siswa Aktif</h4>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
                        <a href="<?= site_url('homeroom/students/create') ?>" class="btn btn-success">
                            <i class="mdi mdi-account-plus me-1"></i>Tambah Siswa
                        </a>
                        <a href="<?= site_url('homeroom/parents') ?>" class="btn btn-primary">
                            <i class="mdi mdi-account-supervisor me-1"></i>Akun Orang Tua
                        </a>
                    <?php endif; ?>
                    <?php if (function_exists('has_permission') && has_permission('import_export_data')): ?>
                        <a href="<?= site_url('homeroom/students/import') ?>" class="btn btn-info text-white">
                            <i class="mdi mdi-file-import me-1"></i>Impor
                        </a>
                        <a href="<?= site_url('homeroom/students/export') ?>" class="btn btn-outline-success">
                            <i class="mdi mdi-file-export me-1"></i>Ekspor CSV
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tblSiswa" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;" class="text-center">No</th>
                                <th>Nama Siswa</th>
                                <th style="width:160px;">NISN / NIK</th>
                                <th style="width:120px;">Jenis Kelamin</th>
                                <?php if ($isMultipleClass): ?><th style="width:100px;">Kelas</th><?php endif; ?>
                                <th>Orang Tua</th>
                                <th style="width:90px;" class="text-center">Status</th>
                                <th style="width:130px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td class="text-center"></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc($s['full_name'] ?? '-') ?></div>
                                        </td>
                                        <td class="small">
                                            <div>NISN: <span class="fw-semibold"><?= esc($s['nisn'] ?? '-') ?></span></div>
                                            <div>NIK: <span class="fw-semibold"><?= esc($s['nik'] ?? '-') ?></span></div>
                                        </td>
                                        <td>
                                            <?php $g = $s['gender'] ?? ''; ?>
                                            <span class="badge <?= $g === 'L' ? 'bg-info' : ($g === 'P' ? 'bg-pink' : 'bg-secondary') ?>"
                                                  style="<?= $g === 'P' ? 'background-color:#d63384;' : '' ?>">
                                                <?= $g === 'L' ? 'Laki-laki' : ($g === 'P' ? 'Perempuan' : '-') ?>
                                            </span>
                                        </td>
                                        <?php if ($isMultipleClass): ?>
                                            <td><?= esc($s['class_name'] ?? '-') ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if (!empty($s['parent_id'])): ?>
                                                <div class="fw-semibold"><?= esc($s['parent_name'] ?? 'Orang Tua') ?></div>
                                                <small class="text-muted"><?= esc($s['parent_phone'] ?? $s['parent_email'] ?? '') ?></small>
                                            <?php else: ?>
                                                <span class="text-muted small">Belum terhubung</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= ($s['status'] ?? '') === 'Aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= esc($s['status'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1 justify-content-center">
                                                <a href="<?= site_url('homeroom/students/' . (int)($s['id'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-info"
                                                   data-bs-toggle="tooltip" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
                                                    <a href="<?= site_url('homeroom/students/edit/' . (int)($s['id'] ?? 0)) ?>"
                                                       class="btn btn-sm btn-primary"
                                                       data-bs-toggle="tooltip" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <?php if (!empty($s['parent_id'])): ?>
                                                        <a href="<?= site_url('homeroom/parents/' . (int)$s['parent_id']) ?>"
                                                           class="btn btn-sm btn-outline-secondary"
                                                           data-bs-toggle="tooltip" title="Akun Orang Tua">
                                                            <i class="mdi mdi-account-supervisor"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <form action="<?= site_url('homeroom/students/delete/' . (int)($s['id'] ?? 0)) ?>"
                                                          method="post" class="d-inline"
                                                          onsubmit="return confirm('Hapus siswa ini dari kelas binaan?')">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                                data-bs-toggle="tooltip" title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $isMultipleClass ? 8 : 7 ?>" class="text-center py-5">
                                        <i class="mdi mdi-account-off text-dark" style="font-size: 48px;"></i>
                                        <p class="text-dark mt-2 mb-0">Belum ada data siswa aktif untuk kelas ini.</p>
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

<?php endif; // end if class not empty ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    // Tooltip
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        .forEach(function (el) { new bootstrap.Tooltip(el); });

    var genderColIdx = <?= (int)$genderColIdx ?>;
    var aksiColIdx   = <?= (int)$aksiColIdx ?>;

    <?php if (!empty($students)): ?>
    var table = $('#tblSiswa').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, aksiColIdx] }
        ],
        dom: "rt" +
             "<'row align-items-center mt-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start'p>>" +
             "<'row'<'col-12 mt-2'i>>",
        language: {
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang sesuai",
            emptyTable: "Tidak ada data tersedia",
            processing: "Memproses...",
            paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
        }
    });

    // Nomor urut otomatis walau sorting/paging
    function renumber() {
        var info = table.page.info();
        table.column(0, { page: 'current' }).nodes().each(function (cell, i) {
            cell.innerHTML = info.start + i + 1;
        });
    }
    table.on('order.dt draw.dt', renumber);
    renumber();

    // Filter card — pencarian client-side
    function applyFilter() {
        var gender = $('#genderFilter').val();
        var search = $('#searchInput').val();

        // Gender: filter kolom "Jenis Kelamin" (badge teks: Laki-laki / Perempuan)
        var genderSearch = '';
        if (gender === 'L') genderSearch = '^Laki-laki$';
        else if (gender === 'P') genderSearch = '^Perempuan$';
        table.column(genderColIdx).search(genderSearch, true, false);

        table.search(search).draw();
    }

    $('#btnFilter').on('click', applyFilter);

    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) applyFilter();
    });

    $('#btnReset').on('click', function () {
        $('#genderFilter').val('');
        $('#searchInput').val('');
        table.column(genderColIdx).search('', true, false);
        table.search('').draw();
    });
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
