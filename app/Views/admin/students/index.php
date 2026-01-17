<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/students/index.php
 *
 * Admin • Students Index
 * - Pagination hanya di VIEW menggunakan DataTables (seperti counselor/sessions)
 * - Filter Card konsisten dengan counselor/sessions
 * - Tetap mempertahankan fitur: statistik, filter server (GET), create/import/export, delete modal, avatar fallback
 */

helper(['url']);
helper('auth'); // jika ada user_avatar, tapi kita tidak wajib memakainya

$filters = $filters ?? [];
$stats   = $stats ?? ['total' => 0, 'active' => 0, 'alumni' => 0, 'moved' => 0, 'dropped' => 0];
$classes = $classes ?? [];

$status_options = $status_options ?? ['Aktif', 'Alumni', 'Pindah', 'Keluar'];
$gender_options = $gender_options ?? ['L' => 'Laki-laki', 'P' => 'Perempuan'];

$perPage = (int)($per_page ?? 10);
if ($perPage <= 0) $perPage = 10;

// Default avatar sesuai ketentuan
$defaultAvatarRel = 'assets/images/users/default-avatar.svg';
$defaultAvatar    = base_url($defaultAvatarRel);

// Helper aman untuk ambil avatar dari row (service idealnya sudah memberi URL final di profile_photo)
if (!function_exists('student_avatar_url')) {
    function student_avatar_url(array $student, string $defaultAvatar): string
    {
        $raw = trim((string)($student['profile_photo'] ?? ''));
        if ($raw === '') return $defaultAvatar;

        // Jika sudah URL absolut
        if (preg_match('~^https?://~i', $raw)) return $raw;

        // Jika sudah path relatif (uploads/... atau assets/...)
        $norm = ltrim(str_replace('\\', '/', $raw), '/');
        return base_url($norm);
    }
}

// Optional alerts helper
helper('app');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Manajemen Siswa</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Siswa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (function_exists('show_alerts')): ?>
    <?= show_alerts() ?>
<?php endif; ?>

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
                        <h4 class="mb-0"><?= number_format((int)(($stats['moved'] ?? 0) + ($stats['dropped'] ?? 0))) ?></h4>
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
                <form action="<?= base_url('admin/students') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Kelas</label>
                            <select name="class_id" class="form-select">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= (int)$class['id'] ?>" <?= ((string)($filters['class_id'] ?? '') === (string)$class['id']) ? 'selected' : '' ?>>
                                        <?= esc($class['grade_level'] ?? '-') ?> - <?= esc($class['class_name'] ?? '-') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tingkat</label>
                            <select name="grade_level" class="form-select">
                                <option value="">Semua Tingkat</option>
                                <option value="X" <?= (($filters['grade_level'] ?? '') === 'X') ? 'selected' : '' ?>>X</option>
                                <option value="XI" <?= (($filters['grade_level'] ?? '') === 'XI') ? 'selected' : '' ?>>XI</option>
                                <option value="XII" <?= (($filters['grade_level'] ?? '') === 'XII') ? 'selected' : '' ?>>XII</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <?php foreach ($status_options as $st): ?>
                                    <option value="<?= esc($st) ?>" <?= (($filters['status'] ?? '') === $st) ? 'selected' : '' ?>>
                                        <?= esc($st) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach ($gender_options as $k => $v): ?>
                                    <option value="<?= esc($k) ?>" <?= (($filters['gender'] ?? '') === (string)$k) ? 'selected' : '' ?>>
                                        <?= esc($v) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                        

                    <!-- tetap bawa order jika kamu masih pakai di controller/service -->
                    <input type="hidden" name="order_by" value="<?= esc($filters['order_by'] ?? 'students.created_at') ?>">
                    <input type="hidden" name="order_dir" value="<?= esc($filters['order_dir'] ?? 'DESC') ?>">

                    <!-- per_page hanya untuk default pageLength DataTables (opsional) -->
                    <input type="hidden" name="per_page" value="<?= (int)$perPage ?>">
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
                <h4 class="card-title mb-0">Daftar Siswa</h4>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('admin/students/create') ?>" class="btn btn-success">
                        <i class="mdi mdi-plus me-1"></i> Tambah Siswa
                    </a>
                    <a href="<?= base_url('admin/students/import') ?>" class="btn btn-info">
                        <i class="mdi mdi-upload me-1"></i> Impor
                    </a>
                    <a href="<?= base_url('admin/students/export') . '?' . http_build_query((array)$filters) ?>" class="btn btn-primary">
                        <i class="mdi mdi-download me-1"></i> Ekspor CSV
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="studentsTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Siswa</th>
                                <th>NISN</th>
                                <th>NIS</th>
                                <th>Kelas</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Poin</th>
                                <th style="width:150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students) && is_array($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                        $studentName = $student['full_name'] ?? ($student['student_full_name'] ?? ($student['user_full_name'] ?? '-'));
                                        $avatarSrc   = student_avatar_url((array)$student, $defaultAvatar);

                                        $statusColors = [
                                            'Aktif'  => 'success',
                                            'Alumni' => 'info',
                                            'Pindah' => 'warning',
                                            'Keluar' => 'danger'
                                        ];
                                        $status      = $student['status'] ?? '-';
                                        $statusColor = $statusColors[$status] ?? 'secondary';

                                        $points = (int)($student['total_violation_points'] ?? 0);
                                    ?>
                                    <tr>
                                        <!-- No diisi DataTables -->
                                        <td class="text-center"></td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <img
                                                        src="<?= esc($avatarSrc, 'attr') ?>"
                                                        alt="<?= esc($studentName, 'attr') ?>"
                                                        class="avatar-xs rounded-circle"
                                                        loading="lazy"
                                                        style="object-fit: cover;"
                                                        onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
                                                    >
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold"><?= esc($studentName) ?></div>
                                                    <div class="text-muted font-size-12"><?= esc($student['email'] ?? '-') ?></div>
                                                </div>
                                            </div>
                                        </td>

                                        <td><code><?= esc($student['nisn'] ?? '-') ?></code></td>
                                        <td><code><?= esc($student['nis'] ?? '-') ?></code></td>

                                        <td>
                                            <?php if (!empty($student['class_name'])): ?>
                                                <span class="badge bg-primary">
                                                    <?= esc($student['grade_level'] ?? '-') ?> - <?= esc($student['class_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Belum Ada</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if (($student['gender'] ?? '') === 'L'): ?>
                                                <i class="mdi mdi-gender-male text-primary"></i> L
                                            <?php elseif (($student['gender'] ?? '') === 'P'): ?>
                                                <i class="mdi mdi-gender-female text-danger"></i> P
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-<?= $statusColor ?> font-size-12"><?= esc($status) ?></span>
                                        </td>

                                        <td class="text-center" data-order="<?= $points ?>">
                                            <?php if ($points > 0): ?>
                                                <span class="badge bg-danger font-size-12"><?= $points ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success font-size-12">0</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('admin/students/profile/' . (int)$student['id']) ?>"
                                                   class="btn btn-sm btn-info"
                                                   data-bs-toggle="tooltip"
                                                   title="Profil">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <a href="<?= base_url('admin/students/edit/' . (int)$student['id']) ?>"
                                                   class="btn btn-sm btn-primary"
                                                   data-bs-toggle="tooltip"
                                                   title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm btn-danger btn-delete"
                                                        data-id="<?= (int)$student['id'] ?>"
                                                        data-name="<?= esc($studentName, 'attr') ?>"
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
                                    <td colspan="9" class="text-center py-5 text-muted">
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
                <p>Apakah Anda yakin ingin menghapus data siswa <strong id="studentName"></strong>?</p>
                <p class="text-danger mb-0">
                    <i class="mdi mdi-information me-1"></i>
                    Data yang sudah dihapus tidak dapat dikembalikan!
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
<!-- DataTables (pagination di VIEW) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });

    // DataTables
    <?php if (!empty($students) && is_array($students)): ?>
    var table;
    if (window.SIBK && typeof SIBK.initDataTable === 'function') {
        table = SIBK.initDataTable('studentsTable', {
            pageLength: <?= (int)$perPage ?>,
            order: [[1, 'asc']], // Siswa (nama) ASC
            columnDefs: [
                { orderable: false, targets: [0, 8] } // No + Aksi
            ]
        });
    } else {
        table = $('#studentsTable').DataTable({
            responsive: true,
            pageLength: <?= (int)$perPage ?>,
            order: [[1, 'asc']],
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

    // Nomor urut selalu benar (mulai 1) walau sort/search/paging
    function renumber() {
        var info = table.page.info();
        table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
            cell.innerHTML = info.start + i + 1;
        });
    }
    table.on('order.dt search.dt draw.dt', renumber);
    renumber();
    <?php endif; ?>

    // Delete Student
    $('.btn-delete').on('click', function() {
        const studentId = $(this).data('id');
        const studentName = $(this).data('name');

        $('#studentName').text(studentName);
        $('#deleteForm').attr('action', '<?= base_url('admin/students/delete') ?>/' + studentId);

        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    });

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
<?= $this->endSection() ?>
