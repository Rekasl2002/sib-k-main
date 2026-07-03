<?php // app/Views/homeroom_teacher/class/my_class.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
helper('permission');

$pageTitle       = $pageTitle ?? 'Kelas Binaan';
$class           = is_array($class ?? null) ? $class : [];
$activeYear      = is_array($activeYear ?? null) ? $activeYear : [];
$stats           = is_array($stats ?? null) ? $stats : [];
$students        = is_array($students ?? null) ? $students : [];
$isMultipleClass = !empty($class['is_multiple']);
$studentCount    = count($students);
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="mdi mdi-google-classroom me-2"></i><?= esc($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
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
        <div class="card mini-stats-wid">
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

    <!-- Statistik Siswa -->
    <div class="col-md-2 col-xl-2">
        <div class="card mini-stats-wid">
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
        <div class="card mini-stats-wid">
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
        <div class="card mini-stats-wid">
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
        <div class="card mini-stats-wid">
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

<!-- Tombol Aksi Atas -->
<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap gap-2">
        <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
            <a href="<?= site_url('homeroom/students/create') ?>" class="btn btn-primary">
                <i class="mdi mdi-account-plus me-1"></i>Tambah Siswa
            </a>
            <a href="<?= site_url('homeroom/parents') ?>" class="btn btn-outline-primary">
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

<!-- Tabel Daftar Siswa -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="mdi mdi-account-multiple me-2"></i>Daftar Siswa Aktif
                </h5>
                <span class="badge bg-primary"><?= $studentCount ?> siswa</span>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="mdi mdi-account-off font-size-48 d-block mb-2"></i>
                        Belum ada data siswa aktif untuk kelas ini.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tblSiswa" class="table table-hover align-middle dt-responsive nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN / NIK</th>
                                    <th>Jenis Kelamin</th>
                                    <?php if ($isMultipleClass): ?><th>Kelas</th><?php endif; ?>
                                    <th>Orang Tua</th>
                                    <th>Status</th>
                                    <th class="text-center no-sort">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $i => $s): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc($s['full_name'] ?? '-') ?></div>
                                        </td>
                                        <td class="small">
                                            <div>NISN: <span class="fw-semibold"><?= esc($s['nisn'] ?? '-') ?></span></div>
                                            <div>NIK: <span class="fw-semibold"><?= esc($s['nik'] ?? '-') ?></span></div>
                                        </td>
                                        <td>
                                            <?php $g = $s['gender'] ?? ''; ?>
                                            <span class="badge <?= $g === 'L' ? 'bg-info' : ($g === 'P' ? 'bg-pink' : 'bg-secondary') ?>" style="<?= $g === 'P' ? 'background-color:#d63384;' : '' ?>">
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
                                        <td>
                                            <span class="badge <?= ($s['status'] ?? '') === 'Aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= esc($s['status'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="<?= site_url('homeroom/students/' . (int)($s['id'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
                                                    <a href="<?= site_url('homeroom/students/edit/' . (int)($s['id'] ?? 0)) ?>"
                                                       class="btn btn-sm btn-outline-secondary" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <?php if (!empty($s['parent_id'])): ?>
                                                        <a href="<?= site_url('homeroom/parents/' . (int)$s['parent_id']) ?>"
                                                           class="btn btn-sm btn-outline-info" title="Akun Orang Tua">
                                                            <i class="mdi mdi-account-supervisor"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <form action="<?= site_url('homeroom/students/delete/' . (int)($s['id'] ?? 0)) ?>"
                                                          method="post" class="d-inline"
                                                          onsubmit="return confirm('Hapus siswa ini dari kelas binaan?')">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form>
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

<?php endif; // end if class not empty ?>

<!-- DataTables -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tbl = document.getElementById('tblSiswa');
    if (!tbl || typeof $.fn.DataTable === 'undefined') return;

    $('#tblSiswa').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        language: {
            url: '<?= base_url('assets/libs/datatables/i18n/id.json') ?>',
            emptyTable: 'Tidak ada data siswa.',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ siswa',
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
