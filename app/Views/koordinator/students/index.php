<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/students/index.php
 *
 * Students List View (Koordinator BK)
 * - Pagination hanya di View (DataTables) agar konsisten dengan counselor/sessions
 * - Filter UI mengikuti layout counselor/sessions (Filter Card)
 *
 * @package    SIB-K
 * @subpackage Views/Koordinator/Students
 * @category   Student Management
 * @author     Development Team
 * @created    2025-12-16
 * @updated    2026-01-07
 */

helper(['permission', 'url']); // has_permission(), base_url()

// Default avatar sesuai ketentuan: public/assets/images/users/default-avatar.svg
$defaultAvatarRel = 'assets/images/users/default-avatar.svg';
$defaultAvatar    = base_url($defaultAvatarRel);

// Page length default DataTables (kalau controller mengirim $perPage, pakai itu)
$dtPageLength = (int)($perPage ?? 10);
if ($dtPageLength <= 0) $dtPageLength = 10;
if ($dtPageLength > 200) $dtPageLength = 200;

// Small helper untuk nama tampil
if (!function_exists('student_name')) {
    function student_name(array $s): string
    {
        foreach (['full_name','student_full_name','user_full_name','name'] as $k) {
            if (!empty($s[$k]) && trim((string)$s[$k]) !== '') return (string)$s[$k];
        }
        return '-';
    }
}

// Small helper untuk avatar src yang aman (tanpa user_avatar(null))
if (!function_exists('safe_avatar_src')) {
    function safe_avatar_src(array $s, string $defaultAvatar): string
    {
        $photo = trim((string)($s['profile_photo'] ?? ''));
        if ($photo === '') return $defaultAvatar;

        // buang query string
        $photoNoQ = trim((string)strtok($photo, '?'));
        if ($photoNoQ === '') return $defaultAvatar;

        // sudah URL penuh
        if (preg_match('~^https?://~i', $photoNoQ)) return $photoNoQ;

        // kalau sudah path relatif
        if (strpos($photoNoQ, '/') !== false || strpos($photoNoQ, '\\') !== false) {
            $rel = ltrim(str_replace('\\', '/', $photoNoQ), '/');
            return base_url($rel);
        }

        // filename saja -> coba bentuk uploads/profile_photos/{user_id}/{filename}
        $uid = (int)($s['user_id'] ?? 0);
        if ($uid > 0) {
            return base_url("uploads/profile_photos/{$uid}/{$photoNoQ}");
        }

        // fallback
        return $defaultAvatar;
    }
}
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Manajemen Siswa</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item active">Siswa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-2"></i>
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert me-2"></i>
        <?= session()->getFlashdata('warning') ?>
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

<?php
$importErrors   = session()->getFlashdata('import_errors');
$importWarnings = session()->getFlashdata('import_warnings');
?>

<?php if (!empty($importWarnings) && is_array($importWarnings)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert me-2"></i>
        <strong>Peringatan Import:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($importWarnings as $w): ?>
                <li><?= esc($w) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($importErrors) && is_array($importErrors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-close-circle me-2"></i>
        <strong>Detail Error Import:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($importErrors as $e): ?>
                <li><?= esc($e) ?></li>
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
                <form action="<?= base_url('koordinator/students') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Kelas</label>
                            <select name="class_id" class="form-select" id="classFilter">
                                <option value="">Semua Kelas</option>
                                <?php foreach (($classes ?? []) as $class): ?>
                                    <option value="<?= esc($class['id']) ?>" <?= (($filters['class_id'] ?? '') == $class['id']) ? 'selected' : '' ?>>
                                        <?= esc($class['grade_level']) ?> - <?= esc($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tingkat</label>
                            <select name="grade_level" class="form-select">
                                <option value="">Semua Tingkat</option>
                                <option value="X" <?= (($filters['grade_level'] ?? '') == 'X') ? 'selected' : '' ?>>X</option>
                                <option value="XI" <?= (($filters['grade_level'] ?? '') == 'XI') ? 'selected' : '' ?>>XI</option>
                                <option value="XII" <?= (($filters['grade_level'] ?? '') == 'XII') ? 'selected' : '' ?>>XII</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <?php foreach (($status_options ?? []) as $status): ?>
                                    <option value="<?= esc($status) ?>" <?= (($filters['status'] ?? '') == $status) ? 'selected' : '' ?>>
                                        <?= esc($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach (($gender_options ?? []) as $key => $value): ?>
                                    <option value="<?= esc($key) ?>" <?= (($filters['gender'] ?? '') == $key) ? 'selected' : '' ?>>
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
                            <a href="<?= base_url('koordinator/students') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>

                        <!-- Opsional: simpan default pageLength DataTables via query (tidak wajib) -->
                        <input type="hidden" name="per_page" value="<?= (int)$dtPageLength ?>">
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
                <h4 class="card-title mb-0">Daftar Siswa</h4>

                <div class="text-end">
                    <?php if (has_permission('import_export_data')): ?>
                        <!--<a href="<?= base_url('koordinator/students/import') ?>" class="btn btn-info">
                            <i class="mdi mdi-upload me-1"></i> Impor
                        </a>-->
                        <a href="<?= base_url('koordinator/students/export') . '?' . http_build_query($filters ?? []) ?>" class="btn btn-primary ms-1">
                            <i class="mdi mdi-download me-1"></i> Ekspor CSV
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="studentsTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;" class="text-center">No</th>
                                <th>Siswa</th>
                                <th style="width:120px;">NISN</th>
                                <th style="width:120px;">NIS</th>
                                <th style="width:160px;">Kelas</th>
                                <th style="width:90px;">Gender</th>
                                <th style="width:120px;">Status</th>
                                <th style="width:80px;" class="text-center">Poin</th>
                                <th style="width:140px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students) && is_array($students)): ?>
                                <?php foreach ($students as $student):
                                    $student = (array)$student;
                                    $studentName = student_name($student);
                                    $avatarSrc   = safe_avatar_src($student, $defaultAvatar);
                                ?>
                                    <tr>
                                        <!-- No diisi DataTables agar selalu benar -->
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
                                                    <h5 class="font-size-14 mb-0"><?= esc($studentName) ?></h5>
                                                    <p class="text-muted mb-0 font-size-12"><?= esc($student['email'] ?? '-') ?></p>
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

                                        <td>
                                            <?php if (($student['gender'] ?? '') === 'L'): ?>
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
                                            $statusVal   = $student['status'] ?? '-';
                                            $statusColor = $statusColors[$statusVal] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= esc($statusColor, 'attr') ?> font-size-12">
                                                <?= esc($statusVal) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <?php $p = (int)($student['total_violation_points'] ?? 0); ?>
                                            <?php if ($p > 0): ?>
                                                <span class="badge bg-danger font-size-12"><?= $p ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success font-size-12">0</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('koordinator/students/profile/' . (int)($student['id'] ?? 0)) ?>"
                                                    class="btn btn-sm btn-info"
                                                    title="Profil">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>

                                                <?php if (has_permission('manage_academic_data')): ?>
                                                    <a href="<?= base_url('koordinator/students/edit/' . (int)($student['id'] ?? 0)) ?>"
                                                        class="btn btn-sm btn-primary"
                                                        title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        disabled
                                                        title="Tidak punya izin untuk edit">
                                                        <i class="mdi mdi-pencil-off"></i>
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
                                        <p class="text-muted mt-2 mb-0">Tidak ada data siswa</p>
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables (pagination di VIEW) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Select2 (opsional, untuk dropdown Kelas biar enak dicari) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el);
        });

        // Select2 untuk Kelas
        $('#classFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Semua Kelas',
            allowClear: true,
            width: '100%'
        });

        // DataTables (pagination di VIEW)
        <?php if (!empty($students) && is_array($students)): ?>
            var table;

            if (window.SIBK && typeof SIBK.initDataTable === 'function') {
                table = SIBK.initDataTable('studentsTable', {
                    pageLength: <?= (int)$dtPageLength ?>,
                    order: [
                        [1, 'asc'] // kolom "Siswa"
                    ],
                    columnDefs: [
                        { orderable: false, targets: [0, 8] } // No + Aksi
                    ]
                });
            } else {
                table = $('#studentsTable').DataTable({
                    responsive: true,
                    pageLength: <?= (int)$dtPageLength ?>,
                    order: [
                        [1, 'asc']
                    ],
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

        // Auto-hide alert
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?= $this->endSection() ?>
