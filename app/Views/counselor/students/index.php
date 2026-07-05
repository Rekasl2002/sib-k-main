<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/students/index.php
 *
 * Counselor - Students Index
 * - List siswa binaan + filter + DataTables
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
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Guru BK</a></li>
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
        <div class="card filter-compact">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 text-dark">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data
                </h5>
            </div>
            <div class="card-body py-3">
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
                                <?php
                                $gradeOptions = [];
                                foreach (($classes ?? []) as $class) {
                                    $grade = trim((string)($class['grade_level'] ?? ''));
                                    if ($grade !== '') $gradeOptions[$grade] = $grade;
                                }
                                if (!empty($filters['grade_level'])) $gradeOptions[(string)$filters['grade_level']] = (string)$filters['grade_level'];
                                uksort($gradeOptions, static function ($a, $b) {
                                    $rank = static fn($v) => ['7'=>7,'8'=>8,'9'=>9,'10'=>10,'X'=>10,'11'=>11,'XI'=>11,'12'=>12,'XII'=>12][$v] ?? 99;
                                    return ($rank($a) <=> $rank($b)) ?: strcmp((string)$a, (string)$b);
                                });
                                ?>
                                <?php foreach ($gradeOptions as $grade): ?>
                                    <option value="<?= esc($grade) ?>" <?= (($filters['grade_level'] ?? '') == $grade) ? 'selected' : '' ?>><?= esc($grade) ?></option>
                                <?php endforeach; ?>
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
                                   placeholder="NISN, NIK, Nama, Email..."
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
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('counselor/parents') ?>" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-account-supervisor me-1"></i>Akun Orang Tua
                    </a>
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
                                <th>NIK</th>
                                <th>Kelas</th>
                                <th>Jenis Kelamin</th>
                                <th>Status</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students) && is_array($students)): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($students as $student): ?>
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
                                        <td><code><?= esc($student['nik'] ?? '-') ?></code></td>
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
                                    <td colspan="8" class="text-center text-muted py-4">
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
                    { orderable: false, targets: [0, 7] }
                ]
            });
        } else {
            table = $('#studentsTable').DataTable({
                responsive: true,
                pageLength: 10,
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

    });
</script>
<?= $this->endSection() ?>
