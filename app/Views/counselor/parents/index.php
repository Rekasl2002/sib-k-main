<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/parents/index.php
 * Daftar akun orang tua siswa binaan — Guru BK.
 * - Filter/Saring Data (client-side, ala koordinator/users)
 * - Pagination via DataTables
 */

helper('permission');

$parents         = is_array($parents ?? null) ? $parents : [];
$totalParents    = $totalParents ?? count($parents);
$activeParents   = $activeParents ?? 0;
$inactiveParents = $inactiveParents ?? 0;

// Kolom Status di index 6 (0-based): #, Nama, Username, Kontak, Kelas Anak, Jml Anak, Status, Aksi
$statusColIdx = 6;
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="mdi mdi-account-supervisor me-2"></i><?= esc($pageTitle ?? 'Akun Orang Tua') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/students') ?>">Siswa Binaan</a></li>
                    <li class="breadcrumb-item active">Akun Orang Tua</li>
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

<!-- Kartu Statistik -->
<div class="row">
    <div class="col-md-4">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-2">Total Orang Tua</p>
                        <h4 class="mb-0 text-dark"><?= (int)$totalParents ?></h4>
                        <p class="mb-0 text-muted small">dari siswa binaan</p>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title"><i class="mdi mdi-account-supervisor font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-2">Akun Aktif</p>
                        <h4 class="mb-0 text-dark"><?= (int)$activeParents ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                            <span class="avatar-title"><i class="mdi mdi-account-check font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mini-stats-wid h-100">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-dark fw-medium mb-2">Akun Nonaktif</p>
                        <h4 class="mb-0 text-dark"><?= (int)$inactiveParents ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-secondary">
                            <span class="avatar-title"><i class="mdi mdi-account-off font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Pencarian</label>
                        <input type="text" id="searchInput" class="form-control"
                               placeholder="Cari nama, username, email, atau kontak...">
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

<!-- Tabel Orang Tua -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Akun Orang Tua</h4>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (function_exists('has_permission') && has_permission('manage_bk_services')): ?>
                        <a href="<?= base_url('counselor/parents/create') ?>" class="btn btn-success">
                            <i class="mdi mdi-account-plus me-1"></i>Tambah Akun Orang Tua
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('counselor/students') ?>" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>Siswa Binaan
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tblOrangTua" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;" class="text-center">No</th>
                                <th>Nama Lengkap</th>
                                <th style="width:140px;">Username</th>
                                <th style="width:200px;">Kontak</th>
                                <th style="width:160px;">Kelas Anak</th>
                                <th style="width:80px;" class="text-center">Jml Anak</th>
                                <th style="width:100px;" class="text-center">Status</th>
                                <th style="width:120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($parents)): ?>
                                <?php foreach ($parents as $p): ?>
                                    <tr>
                                        <td class="text-center"></td>
                                        <td class="fw-semibold"><?= esc($p['full_name'] ?? '-') ?></td>
                                        <td><code><?= esc($p['username'] ?? '-') ?></code></td>
                                        <td>
                                            <?php if (!empty($p['phone'])): ?>
                                                <div><?= esc($p['phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($p['email'])): ?>
                                                <small class="text-muted"><?= esc($p['email']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($p['phone']) && empty($p['email'])): ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= esc($p['child_classes'] ?? '-') ?></td>
                                        <td class="text-center"><?= (int)($p['child_count'] ?? 0) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= (int)($p['is_active'] ?? 0) === 1 ? 'success' : 'secondary' ?>">
                                                <?= (int)($p['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1 justify-content-center">
                                                <a href="<?= base_url('counselor/parents/' . (int)($p['id'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-info"
                                                   data-bs-toggle="tooltip" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <?php if (function_exists('has_permission') && has_permission('manage_bk_services')): ?>
                                                    <a href="<?= base_url('counselor/parents/edit/' . (int)($p['id'] ?? 0)) ?>"
                                                       class="btn btn-sm btn-primary"
                                                       data-bs-toggle="tooltip" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="mdi mdi-account-off text-dark" style="font-size: 48px;"></i>
                                        <p class="text-dark mt-2 mb-0">Belum ada akun orang tua yang terhubung dengan siswa binaan Anda.</p>
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
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    // Tooltip
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        .forEach(function (el) { new bootstrap.Tooltip(el); });

    var statusColIdx = <?= (int)$statusColIdx ?>;

    <?php if (!empty($parents)): ?>
    var table = $('#tblOrangTua').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 7] }
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
        var status = $('#statusFilter').val();
        var search = $('#searchInput').val();

        var statusSearch = '';
        if (status === '1') statusSearch = '^Aktif$';
        else if (status === '0') statusSearch = '^Nonaktif$';

        table.column(statusColIdx).search(statusSearch, true, false);
        table.search(search).draw();
    }

    $('#btnFilter').on('click', applyFilter);

    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) applyFilter();
    });

    $('#btnReset').on('click', function () {
        $('#statusFilter').val('');
        $('#searchInput').val('');
        table.column(statusColIdx).search('', true, false);
        table.search('').draw();
    });
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
