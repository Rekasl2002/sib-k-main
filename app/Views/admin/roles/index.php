<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/roles/index.php
 *
 * Admin • Kelola Peran (index)
 * - Tampilan diseragamkan dengan halaman Kelola Siswa:
 *   page-title-box, kartu statistik, tabel DataTables, tombol aksi ikon.
 * - Peran bawaan sistem (id 1-6) diberi badge dan tidak bisa dihapus.
 */

$roles          = $roles ?? [];
$stats          = $stats ?? ['total_roles' => 0, 'builtin_roles' => 0, 'total_permissions' => 0, 'total_users' => 0];
$builtinRoleIds = array_map('intval', $builtinRoleIds ?? [1, 2, 3, 4, 5, 6]);
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Kelola Peran</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Peran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
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

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Peran</p>
                        <h4 class="mb-0"><?= number_format((int) $stats['total_roles']) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title">
                                <i class="mdi mdi-shield-account font-size-24"></i>
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
                        <p class="text-muted fw-medium">Peran Bawaan Sistem</p>
                        <h4 class="mb-0"><?= number_format((int) $stats['builtin_roles']) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                            <span class="avatar-title">
                                <i class="mdi mdi-shield-lock font-size-24"></i>
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
                        <p class="text-muted fw-medium">Total Izin Akses</p>
                        <h4 class="mb-0"><?= number_format((int) $stats['total_permissions']) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                            <span class="avatar-title">
                                <i class="mdi mdi-key-variant font-size-24"></i>
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
                        <p class="text-muted fw-medium">Total Pengguna</p>
                        <h4 class="mb-0"><?= number_format((int) $stats['total_users']) ?></h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                            <span class="avatar-title">
                                <i class="mdi mdi-account-group font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Penjelasan singkat untuk orang awam -->
<div class="row">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-start" role="alert">
            <i class="mdi mdi-information-outline font-size-18 me-2 mt-1"></i>
            <div>
                <strong>Apa itu Peran &amp; Izin Akses?</strong>
                Setiap pengguna memiliki satu <em>peran</em> (mis. Guru BK atau Siswa).
                Peran menentukan <em>izin akses</em>, yaitu fitur apa saja yang boleh dibuka dan dilakukan pengguna tersebut.
                Klik tombol <span class="badge bg-primary"><i class="mdi mdi-pencil"></i></span> Edit untuk mengatur izin tiap peran.
                Perubahan izin langsung berlaku bagi pengguna peran tersebut.
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Peran</h4>
                <a href="<?= base_url('admin/roles/create') ?>" class="btn btn-success">
                    <i class="mdi mdi-plus me-1"></i> Tambah Peran
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="rolesTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Peran</th>
                                <th>Deskripsi</th>
                                <th style="width:120px;">Pengguna</th>
                                <th style="width:120px;">Izin Akses</th>
                                <th style="width:110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $r): ?>
                                    <?php
                                        $rid       = (int) $r['id'];
                                        $isBuiltin = in_array($rid, $builtinRoleIds, true);
                                        $userCount = (int) ($r['user_count'] ?? 0);
                                    ?>
                                    <tr>
                                        <!-- No diisi DataTables -->
                                        <td class="text-center"></td>

                                        <td>
                                            <div class="fw-semibold"><?= esc($r['role_name']) ?></div>
                                            <?php if ($isBuiltin): ?>
                                                <span class="badge bg-info-subtle text-info" title="Peran ini dipakai langsung oleh sistem sehingga namanya tidak bisa diubah dan tidak bisa dihapus.">
                                                    <i class="mdi mdi-shield-lock me-1"></i>Bawaan Sistem
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Peran Tambahan</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-wrap" style="min-width:260px; white-space:normal;">
                                            <?= esc($r['description'] ?: '-') ?>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-primary font-size-12">
                                                <?= number_format($userCount) ?> pengguna
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($rid === 1): ?>
                                                <span class="badge bg-success font-size-12" title="Admin otomatis memiliki seluruh izin (tidak dibatasi centang).">Semua izin</span>
                                            <?php else: ?>
                                                <span class="badge bg-info font-size-12"><?= (int) $r['permission_count'] ?> izin</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= route_to('admin.roles.edit', $rid) ?>"
                                                   class="btn btn-sm btn-primary"
                                                   data-bs-toggle="tooltip"
                                                   title="Edit peran & atur izin">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <?php if (!$isBuiltin): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger btn-delete"
                                                            data-id="<?= $rid ?>"
                                                            data-name="<?= esc($r['role_name'], 'attr') ?>"
                                                            data-users="<?= $userCount ?>"
                                                            data-bs-toggle="tooltip"
                                                            title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-secondary"
                                                            disabled
                                                            title="Peran bawaan sistem tidak bisa dihapus">
                                                        <i class="mdi mdi-lock"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="mdi mdi-shield-off font-size-24 d-block mb-2"></i>
                                        Tidak ada data peran
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="mdi mdi-alert-circle text-danger me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus peran <strong id="roleName"></strong>?</p>
                <p class="text-danger mb-0">
                    <i class="mdi mdi-information me-1"></i>
                    Peran yang sudah dihapus tidak dapat dikembalikan!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="mdi mdi-close me-1"></i>Batal
                </button>
                <form id="deleteForm" method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete me-1"></i>Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables (pagination di VIEW, sama seperti Kelola Siswa) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });

    <?php if (!empty($roles)): ?>
    var table;
    var dtOptions = {
        pageLength: 10,
        order: [], // pertahankan urutan id (peran bawaan dulu)
        columnDefs: [
            { orderable: false, targets: [0, 5] } // No + Aksi
        ]
    };
    if (window.SIBK && typeof SIBK.initDataTable === 'function') {
        table = SIBK.initDataTable('rolesTable', dtOptions);
    } else {
        table = $('#rolesTable').DataTable(Object.assign({
            responsive: true,
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
        }, dtOptions));
    }

    // Nomor urut selalu benar walau sort/search/paging
    function renumber() {
        var info = table.page.info();
        table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
            cell.innerHTML = info.start + i + 1;
        });
    }
    table.on('order.dt search.dt draw.dt', renumber);
    renumber();
    <?php endif; ?>

    // Hapus peran (delegated agar tetap berfungsi di semua halaman DataTables)
    $(document).on('click', '.btn-delete', function() {
        const roleId    = $(this).data('id');
        const roleName  = $(this).data('name');
        const userCount = parseInt($(this).data('users'), 10) || 0;

        if (userCount > 0) {
            alert('Peran "' + roleName + '" masih dipakai ' + userCount + ' pengguna dan tidak dapat dihapus.\nPindahkan dahulu pengguna tersebut ke peran lain.');
            return;
        }

        $('#roleName').text(roleName);
        $('#deleteForm').attr('action', '<?= base_url('admin/roles/delete') ?>/' + roleId);

        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    });

    // Auto-hide alerts (biarkan alert info penjelasan tetap tampil)
    setTimeout(function() {
        $('.alert-success, .alert-danger').fadeOut('slow');
    }, 5000);
});
</script>
<?= $this->endSection() ?>
