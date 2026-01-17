<?php
/**
 * File Path: app/Views/admin/users/index.php
 *
 * Admin • Users Index
 * - Pagination + length selector + search via DataTables (VIEW ONLY)
 * - Filter card dibuat konsisten dengan counselor/sessions
 * - Toggle Active pakai AJAX agar tidak redirect ke JSON page
 */

$this->extend('layouts/main');
$this->section('content');

helper(['url', 'form']);
helper('app'); // untuk show_alerts() jika ada

// Safe fallback avatar kalau helper user_avatar() belum ada
if (!function_exists('user_avatar')) {
    function user_avatar($path = null): string
    {
        $default = base_url('assets/images/users/default-avatar.svg');
        if (!$path) return $default;

        // Jika sudah URL absolut
        if (preg_match('#^https?://#i', (string)$path)) return $path;

        // Jika path relatif ke public (umum: uploads/...)
        return base_url(ltrim((string)$path, '/'));
    }
}

// Safe formatter datetime
if (!function_exists('fmt_dt_id')) {
    function fmt_dt_id($dt): string
    {
        if (empty($dt)) return '-';
        try {
            $t = \CodeIgniter\I18n\Time::parse($dt);
            return esc($t->toLocalizedString('dd MMMM yyyy HH:mm'));
        } catch (\Throwable $e) {
            return esc((string)$dt);
        }
    }
}

$filters = $filters ?? [];
$roles   = $roles ?? [];
$stats   = $stats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'admin' => 0];

// ✅ Robust: admin count bisa datang dari stats['admin'] atau stats['by_role']['Admin']
$adminCount = (int) (
    ($stats['admin'] ?? null)
    ?? ($stats['by_role']['Admin'] ?? null)
    ?? 0
);

// Query export: hanya param yang relevan
$exportQuery = http_build_query([
    'role_id'   => $filters['role_id'] ?? '',
    'is_active' => $filters['is_active'] ?? '',
    'search'    => $filters['search'] ?? '',
]);
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Manajemen Pengguna</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Pengguna</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (function_exists('show_alerts')): ?>
    <?= show_alerts() ?>
<?php endif; ?>

<!-- tempat alert khusus AJAX -->
<div id="ajaxAlert"></div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-2"></i>
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Terdapat kesalahan pada input:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ((array) session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Users</p>
                        <h4 class="mb-0"><?= number_format((int)($stats['total'] ?? 0)) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title">
                                <i class="mdi mdi-account-group font-size-24"></i>
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
                        <p class="text-muted fw-medium">Aktif</p>
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
                        <p class="text-muted fw-medium">Nonaktif</p>
                        <h4 class="mb-0"><?= number_format((int)($stats['inactive'] ?? 0)) ?></h4>
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

    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Admin</p>
                        <h4 class="mb-0"><?= number_format($adminCount) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                            <span class="avatar-title">
                                <i class="mdi mdi-shield-account font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card (konsisten ala counselor/sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/users') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Peran</label>
                            <select name="role_id" class="form-select">
                                <option value="">Semua Peran</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= ((string)($filters['role_id'] ?? '') === (string)$r['id']) ? 'selected' : '' ?>>
                                        <?= esc($r['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="1" <?= ((string)($filters['is_active'] ?? '') === '1') ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= ((string)($filters['is_active'] ?? '') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Pencarian</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Nama, username, email..."
                                   value="<?= esc($filters['search'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>

                    <!-- tetap bawa order jika kamu masih pakai di controller/service -->
                    <input type="hidden" name="order_by" value="<?= esc($filters['order_by'] ?? 'created_at') ?>">
                    <input type="hidden" name="order_dir" value="<?= esc($filters['order_dir'] ?? 'desc') ?>">
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Pengguna</h4>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('admin/users/export' . ($exportQuery ? ('?' . $exportQuery) : '')) ?>" class="btn btn-outline-success">
                        <i class="mdi mdi-file-excel-outline me-1"></i> Expor CSV
                    </a>
                    <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary">
                        <i class="mdi mdi-plus-circle me-1"></i> Tambah User
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Pengguna</th>
                                <th width="12%">Username</th>
                                <th width="14%">Peran</th>
                                <th width="14%">Telepon</th>
                                <th width="10%">Status</th>
                                <th width="12%">Terakhir Login</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($users) && is_array($users)): ?>
                            <?php foreach ($users as $u): ?>
                                <?php
                                    $isActive = (int)($u['is_active'] ?? 0) === 1;
                                    $statusBadge = $isActive ? 'success' : 'secondary';

                                    $lastLoginTs = 0;
                                    if (!empty($u['last_login'])) {
                                        try { $lastLoginTs = (int) strtotime((string)$u['last_login']); } catch (\Throwable $e) { $lastLoginTs = 0; }
                                    }
                                ?>
                                <tr>
                                    <!-- nomor diisi via DataTables agar selalu 1..n sesuai sort/search -->
                                    <td class="text-center"></td>

                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <img src="<?= user_avatar($u['profile_photo'] ?? null) ?>"
                                                     alt="<?= esc($u['full_name'] ?? $u['username'] ?? 'User') ?>"
                                                     class="avatar-xs rounded-circle">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold"><?= esc($u['full_name'] ?? '-') ?></div>
                                                <div class="text-muted font-size-12"><?= esc($u['email'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td><code><?= esc($u['username'] ?? '-') ?></code></td>
                                    <td>
                                        <span class="badge bg-info"><?= esc($u['role_name'] ?? '-') ?></span>
                                    </td>
                                    <td><?= esc($u['phone'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $statusBadge ?>"><?= $isActive ? 'Aktif' : 'Nonaktif' ?></span>
                                    </td>

                                    <!-- ✅ data-order supaya sorting DataTables akurat -->
                                    <td data-order="<?= (int)$lastLoginTs ?>"><?= fmt_dt_id($u['last_login'] ?? null) ?></td>

                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="<?= base_url('admin/users/show/' . (int)$u['id']) ?>"
                                               class="btn btn-sm btn-info"
                                               data-bs-toggle="tooltip"
                                               title="Detail">
                                                <i class="mdi mdi-eye"></i>
                                            </a>

                                            <a href="<?= base_url('admin/users/edit/' . (int)$u['id']) ?>"
                                               class="btn btn-sm btn-warning"
                                               data-bs-toggle="tooltip"
                                               title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>

                                            <!-- ✅ FIX: Toggle pakai AJAX, tidak pindah halaman 
                                            <button type="button"
                                                    class="btn btn-sm <?= $isActive ? 'btn-secondary' : 'btn-success' ?> js-toggle-active"
                                                    data-id="<?= (int)$u['id'] ?>"
                                                    data-name="<?= esc($u['full_name'] ?? $u['username'] ?? 'User') ?>"
                                                    data-active="<?= $isActive ? 1 : 0 ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="mdi <?= $isActive ? 'mdi-account-off' : 'mdi-account-check' ?>"></i>
                                            </button>-->

                                            <!-- Tombol reset (kalau suatu saat kamu aktifkan lagi) -->
                                            <!--<button type="button"
                                                    class="btn btn-sm btn-outline-dark btn-reset"
                                                    data-id="<?= (int)$u['id'] ?>"
                                                    data-name="<?= esc($u['full_name'] ?? $u['username'] ?? 'User') ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Reset Password">
                                                <i class="mdi mdi-lock-reset"></i>
                                            </button>-->

                                            <button type="button"
                                                    class="btn btn-sm btn-danger btn-delete"
                                                    data-id="<?= (int)$u['id'] ?>"
                                                    data-name="<?= esc($u['full_name'] ?? $u['username'] ?? 'User') ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Hapus">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="mdi mdi-account-off text-muted" style="font-size: 48px;"></i>
                                    <p class="text-muted mt-2">Tidak ada data user</p>
                                    <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-plus"></i> Tambah User
                                    </a>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-lock-reset me-2"></i>Reset Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Reset password untuk user berikut?</p>
                <div class="alert alert-warning mb-0">
                    <strong>User:</strong> <span id="resetUserName"></span>
                </div>
                <p class="text-muted mt-2 mb-0">
                    <small><i class="mdi mdi-information"></i> Password akan di-generate ulang sesuai aturan sistem.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="mdi mdi-close"></i> Batal
                </button>
                <form id="resetForm" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-dark">
                        <i class="mdi mdi-lock-reset"></i> Ya, Reset
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-alert-circle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus user ini?</p>
                <div class="alert alert-warning mb-0">
                    <strong>User:</strong> <span id="deleteUserName"></span>
                </div>
                <p class="text-muted mt-2 mb-0">
                    <small><i class="mdi mdi-information"></i> Penghapusan bersifat permanen untuk akun user.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="mdi mdi-close"></i> Batal
                </button>
                <form id="deleteForm" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {

    function initTooltips() {
        try {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (el) {
                try { bootstrap.Tooltip.getInstance(el)?.dispose(); } catch(e) {}
                new bootstrap.Tooltip(el);
            });
        } catch(e) {}
    }

    function showAjaxAlert(type, message) {
        var html = `
          <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="mdi ${type === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        `;
        $('#ajaxAlert').html(html);
        setTimeout(function(){ $('#ajaxAlert .alert').fadeOut('slow'); }, 3500);
    }

    // DataTables (pagination di VIEW, default 10 per halaman, user bisa pilih)
    <?php if (!empty($users) && is_array($users)): ?>
    var dt;

    if (window.SIBK && typeof SIBK.initDataTable === 'function') {
        dt = SIBK.initDataTable('usersTable', {
            pageLength: 10,
            order: [[6, 'desc']], // kolom Last Login
            columnDefs: [
                { orderable: false, targets: [0, 7] } // No & Aksi
            ]
        });
    } else {
        dt = $('#usersTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[6, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0, 7] }
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

    // Fix nomor urut: selalu 1..n sesuai sort/search (dan tidak mulai dari 2)
    dt.on('order.dt search.dt draw.dt', function () {
        let info = dt.page.info();
        dt.column(0, { page: 'current', search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
            cell.innerHTML = (info.start + i + 1);
        });
        initTooltips();
    }).draw();
    <?php else: ?>
        initTooltips();
    <?php endif; ?>

    // ✅ Toggle Active via AJAX (tidak pindah halaman hitam)
    $(document).on('click', '.js-toggle-active', function() {
        var id     = $(this).data('id');
        var name   = $(this).data('name');
        var active = parseInt($(this).data('active'), 10) === 1;

        var confirmMsg = active
            ? `Nonaktifkan user "${name}"?`
            : `Aktifkan user "${name}"?`;

        if (!confirm(confirmMsg)) return;

        $.ajax({
            url: '<?= base_url('admin/users/toggle-active/') ?>' + id,
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(res) {
                if (res && res.success) {
                    showAjaxAlert('success', res.message || 'Status user berhasil diubah.');
                    // reload agar badge/status/statistik ikut update
                    setTimeout(function(){ window.location.reload(); }, 500);
                } else {
                    showAjaxAlert('danger', (res && res.message) ? res.message : 'Gagal mengubah status user.');
                }
            },
            error: function() {
                showAjaxAlert('danger', 'Terjadi kesalahan saat mengubah status user.');
            }
        });
    });

    // Reset password modal handler (kalau tombol diaktifkan lagi)
    $('.btn-reset').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#resetUserName').text(name);
        $('#resetForm').attr('action', '<?= base_url('admin/users/reset-password/') ?>' + id);

        $('#resetModal').modal('show');
    });

    // Delete modal handler
    $('.btn-delete').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#deleteUserName').text(name);
        $('#deleteForm').attr('action', '<?= base_url('admin/users/delete/') ?>' + id);

        $('#deleteModal').modal('show');
    });

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
<?php $this->endSection(); ?>
