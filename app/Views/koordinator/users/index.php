<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/users/index.php
 *
 * Users List View (Koordinator)
 * - UI filter + pagination konsisten dengan counselor/sessions (DataTables di View)
 * - Koordinator hanya mengelola akun Guru BK & Wali Kelas (role dibatasi di dropdown)
 *
 * Catatan:
 * - Agar benar-benar "pagination hanya view", controller sebaiknya tidak memotong data dengan paginate().
 */

helper(['url']);

$stats   = $stats   ?? ['total' => 0, 'active' => 0, 'inactive' => 0];
$filters = $filters ?? ['role_id' => '', 'is_active' => '', 'search' => ''];
$roles   = $roles   ?? [];
$users   = $users   ?? [];

// Default avatar sesuai ketentuan
$defaultAvatarRel = 'assets/images/users/default-avatar.svg';
$defaultAvatar    = base_url($defaultAvatarRel);

// Page length DataTables (opsional: jika controller ngirim $perPage)
$dtPageLength = (int)($perPage ?? 10);
if ($dtPageLength <= 0) $dtPageLength = 10;
if ($dtPageLength > 200) $dtPageLength = 200;

// Filter role sesuai aturan Koordinator (UI layer; validasi wajib tetap di controller)
$allowedRoleNames = ['Guru BK','Wali Kelas','Counselor','Homeroom Teacher','HomeroomTeacher'];
$filteredRoles = [];
if (!empty($roles) && is_array($roles)) {
    foreach ($roles as $r) {
        $name = (string)($r['role_name'] ?? '');
        foreach ($allowedRoleNames as $allowed) {
            if ($name !== '' && strcasecmp($name, $allowed) === 0) {
                $filteredRoles[] = $r;
                break;
            }
        }
    }
}

// Avatar src aman (tanpa user_avatar(null))
if (!function_exists('safe_user_avatar_src')) {
    function safe_user_avatar_src(array $u, string $defaultAvatar): string
    {
        $photo = trim((string)($u['profile_photo'] ?? ''));
        if ($photo === '') return $defaultAvatar;

        $photoNoQ = trim((string)strtok($photo, '?'));
        if ($photoNoQ === '') return $defaultAvatar;

        if (preg_match('~^https?://~i', $photoNoQ)) {
            return $photoNoQ;
        }

        // path relatif (uploads/... atau assets/...)
        if (strpos($photoNoQ, '/') !== false || strpos($photoNoQ, '\\') !== false) {
            $rel = ltrim(str_replace('\\', '/', $photoNoQ), '/');
            return base_url($rel);
        }

        // filename saja -> uploads/profile_photos/{id}/{filename}
        $uid = (int)($u['id'] ?? 0);
        if ($uid > 0) {
            return base_url("uploads/profile_photos/{$uid}/{$photoNoQ}");
        }

        return $defaultAvatar;
    }
}
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Manajemen Pengguna</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item active">Pengguna</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Info aturan Koordinator -->
<div class="mb alert-info" role="alert">
    <i class="mdi mdi-information-outline me-2"></i>
    Koordinator hanya dapat mengelola akun <strong>Guru BK</strong> dan <strong>Wali Kelas</strong>.
</div>

<!-- Success/Error Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-2"></i>
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-4">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Pengguna</p>
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

    <div class="col-md-4">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Pengguna Aktif</p>
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

    <div class="col-md-4">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Pengguna Nonaktif</p>
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
</div>

<!-- Filter Card (konsisten dengan counselor/sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('koordinator/users') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select" id="roleFilter">
                                <option value="">Semua Role</option>
                                <?php foreach ($filteredRoles as $role): ?>
                                    <option value="<?= esc($role['id']) ?>" <?= (string)($filters['role_id'] ?? '') === (string)$role['id'] ? 'selected' : '' ?>>
                                        <?= esc($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Pencarian</label>
                            <input type="text"
                                name="search"
                                class="form-control"
                                placeholder="Username, email, atau nama..."
                                value="<?= esc($filters['search'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mt-2 g-3">
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('koordinator/users') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>

                        <!-- Opsional: simpan pageLength DataTables -->
                        <input type="hidden" name="per_page" value="<?= (int)$dtPageLength ?>">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Users Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Pengguna</h4>

                <div class="text-end">
                    <a href="<?= base_url('koordinator/users/create') ?>" class="btn btn-success">
                        <i class="mdi mdi-plus me-1"></i> Tambah Pengguna
                    </a>
                    <a href="<?= base_url('koordinator/users/export') . '?' . http_build_query($filters) ?>" class="btn btn-primary ms-1">
                        <i class="mdi mdi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;" class="text-center">No</th>
                                <th>Pengguna</th>
                                <th style="width:140px;">Username</th>
                                <th style="width:220px;">Email</th>
                                <th style="width:140px;">Role</th>
                                <th style="width:140px;">Telepon</th>
                                <th style="width:90px;" class="text-center">Status</th>
                                <th style="width:160px;">Terakhir Login</th>
                                <th style="width:150px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users) && is_array($users)): ?>
                                <?php foreach ($users as $user):
                                    $user = (array)$user;
                                    $fullName  = $user['full_name'] ?? '-';
                                    $avatarSrc = safe_user_avatar_src($user, $defaultAvatar);
                                ?>
                                    <tr>
                                        <!-- No akan diisi DataTables -->
                                        <td class="text-center"></td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <img
                                                        src="<?= esc($avatarSrc, 'attr') ?>"
                                                        alt="<?= esc($fullName, 'attr') ?>"
                                                        class="avatar-xs rounded-circle"
                                                        loading="lazy"
                                                        style="object-fit: cover;"
                                                        onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
                                                    >
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="font-size-14 mb-0"><?= esc($fullName) ?></h5>
                                                </div>
                                            </div>
                                        </td>

                                        <td><?= esc($user['username'] ?? '-') ?></td>
                                        <td><?= esc($user['email'] ?? '-') ?></td>

                                        <td>
                                            <span class="badge bg-info font-size-12">
                                                <?= esc($user['role_name'] ?? '-') ?>
                                            </span>
                                        </td>

                                        <td><?= esc($user['phone'] ?? '-') ?></td>

                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-flex align-items-center">
                                                <input class="form-check-input toggle-active"
                                                    type="checkbox"
                                                    data-id="<?= (int)($user['id'] ?? 0) ?>"
                                                    <?= ((int)($user['is_active'] ?? 0) === 1) ? 'checked' : '' ?>
                                                    <?= ((int)($user['id'] ?? 0) === (int)session()->get('user_id')) ? 'disabled' : '' ?>>
                                            </div>
                                        </td>

                                        <td>
                                            <?php if (!empty($user['last_login'])): ?>
                                                <small><?= esc(date('d/m/Y H:i', strtotime($user['last_login']))) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Belum pernah</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('koordinator/users/show/' . (int)($user['id'] ?? 0)) ?>"
                                                    class="btn btn-sm btn-info"
                                                    data-bs-toggle="tooltip"
                                                    title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <a href="<?= base_url('koordinator/users/edit/' . (int)($user['id'] ?? 0)) ?>"
                                                    class="btn btn-sm btn-primary"
                                                    data-bs-toggle="tooltip"
                                                    title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>

                                                <?php if ((int)($user['id'] ?? 0) !== (int)session()->get('user_id')): ?>
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger btn-delete"
                                                        data-id="<?= (int)($user['id'] ?? 0) ?>"
                                                        data-name="<?= esc($fullName) ?>"
                                                        data-bs-toggle="tooltip"
                                                        title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="mdi mdi-account-off text-muted" style="font-size: 48px;"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data pengguna</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tidak ada CI pager di sini (pagination hanya DataTables di VIEW) -->
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
                <p>Apakah Anda yakin ingin menghapus pengguna <strong id="userName"></strong>?</p>
                <p class="text-danger mb-0">
                    <i class="mdi mdi-information me-1"></i>
                    Data yang sudah dihapus tidak dapat dikembalikan!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="mdi mdi-close me-1"></i>Batal
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
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
<!-- DataTables (pagination di VIEW) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Select2 (opsional, untuk dropdown Role biar enak dicari) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Select2 Role filter
        $('#roleFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Semua Role',
            allowClear: true,
            width: '100%'
        });

        // DataTables (pagination di VIEW)
        <?php if (!empty($users) && is_array($users)): ?>
            var table;

            if (window.SIBK && typeof SIBK.initDataTable === 'function') {
                table = SIBK.initDataTable('usersTable', {
                    pageLength: <?= (int)$dtPageLength ?>,
                    order: [[1, 'asc']], // kolom "Pengguna"
                    columnDefs: [
                        { orderable: false, targets: [0, 6, 8] } // No + Status (switch) + Aksi
                    ]
                });
            } else {
                table = $('#usersTable').DataTable({
                    responsive: true,
                    pageLength: <?= (int)$dtPageLength ?>,
                    order: [[1, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 6, 8] }
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

        // Toggle Active Status (AJAX)
        $('.toggle-active').on('change', function() {
            const userId = $(this).data('id');
            const isChecked = $(this).is(':checked');
            const checkbox = $(this);

            if (!confirm('Apakah Anda yakin ingin mengubah status pengguna ini?')) {
                checkbox.prop('checked', !isChecked);
                return;
            }

            $.ajax({
                url: '<?= base_url('koordinator/users/toggle-active') ?>/' + userId,
                type: 'POST',
                data: {
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    } else {
                        checkbox.prop('checked', !isChecked);
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message
                            });
                        } else {
                            alert(response.message || 'Gagal');
                        }
                    }
                },
                error: function() {
                    checkbox.prop('checked', !isChecked);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan sistem'
                        });
                    } else {
                        alert('Terjadi kesalahan sistem');
                    }
                }
            });
        });

        // Delete User modal
        $('.btn-delete').on('click', function() {
            const userId = $(this).data('id');
            const userName = $(this).data('name');

            $('#userName').text(userName);
            $('#deleteForm').attr('action', '<?= base_url('koordinator/users/delete') ?>/' + userId);

            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?= $this->endSection() ?>
