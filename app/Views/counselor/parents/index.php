<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/parents/index.php
 * Daftar akun orang tua siswa binaan — Guru BK.
 */

helper('permission');

$parents         = is_array($parents ?? null) ? $parents : [];
$totalParents    = $totalParents ?? count($parents);
$activeParents   = $activeParents ?? 0;
$inactiveParents = $inactiveParents ?? 0;
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
        <div class="card mini-stats-wid">
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
        <div class="card mini-stats-wid">
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
        <div class="card mini-stats-wid">
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

<!-- Tombol Aksi -->
<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap gap-2">
        <?php if (function_exists('has_permission') && has_permission('manage_bk_services')): ?>
            <a href="<?= base_url('counselor/parents/create') ?>" class="btn btn-primary">
                <i class="mdi mdi-account-plus me-1"></i>Tambah Akun Orang Tua
            </a>
        <?php endif; ?>
        <a href="<?= base_url('counselor/students') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Siswa Binaan
        </a>
    </div>
</div>

<!-- Tabel -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="mdi mdi-account-multiple me-2"></i>Daftar Akun Orang Tua
                </h5>
                <span class="badge bg-primary"><?= (int)$totalParents ?> akun</span>
            </div>
            <div class="card-body">
                <?php if (empty($parents)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="mdi mdi-account-off font-size-48 d-block mb-2"></i>
                        Belum ada akun orang tua yang terhubung dengan siswa binaan Anda.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tblOrangTua" class="table table-hover align-middle dt-responsive nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Kontak</th>
                                    <th>Kelas Anak</th>
                                    <th class="text-center">Jml Anak</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center no-sort">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parents as $i => $p): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
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
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="<?= base_url('counselor/parents/' . (int)($p['id'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <?php if (function_exists('has_permission') && has_permission('manage_bk_services')): ?>
                                                    <a href="<?= base_url('counselor/parents/edit/' . (int)($p['id'] ?? 0)) ?>"
                                                       class="btn btn-sm btn-outline-secondary" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable === 'undefined') return;
    $('#tblOrangTua').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
        language: {
            url: '<?= base_url('assets/libs/datatables/i18n/id.json') ?>',
            emptyTable: 'Tidak ada akun orang tua.',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ akun',
            paginate: { previous: '&laquo;', next: '&raquo;' }
        },
        columnDefs: [
            { targets: [0], orderable: false },
            { targets: '.no-sort', orderable: false }
        ],
        order: [[1, 'asc']]
    });
});
</script>

<?= $this->endSection() ?>
