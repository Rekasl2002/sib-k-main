<?php

/**
 * File Path: app/Views/admin/classes/index.php
 *
 * Admin • Classes Index
 * - Pagination + length selector + search via DataTables (VIEW ONLY)
 * - Filter card dibuat konsisten dengan counselor/sessions
 * - Tetap mempertahankan fitur lama: stats cards, alert flash, tooltips, delete modal
 */

?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
helper('app');

$page_title = $page_title ?? 'Daftar Kelas';
$breadcrumb = $breadcrumb ?? [
    ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
    ['title' => 'Kelas', 'link' => null],
];

$filters = $filters ?? [];
$stats   = $stats ?? ['total' => 0, 'active' => 0, 'by_grade' => []];

$classes         = $classes ?? [];
$academic_years  = $academic_years ?? [];
$grade_levels    = $grade_levels ?? [];      // dari controller lama
$majors          = $majors ?? [];            // dari controller lama (assoc)
$status_options  = $status_options ?? [];    // dari controller lama (assoc)

$filterAcademicYear = (string)($filters['academic_year_id'] ?? '');
$filterGrade        = (string)($filters['grade_level'] ?? '');
$filterMajor        = (string)($filters['major'] ?? '');
$filterStatus       = (string)($filters['is_active'] ?? '');
$filterSearch       = (string)($filters['search'] ?? '');

$orderBy  = (string)($filters['order_by'] ?? 'classes.created_at');
$orderDir = strtoupper((string)($filters['order_dir'] ?? 'DESC'));
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18"><?= esc($page_title) ?></h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if (!empty($item['link'])): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= esc($item['link']) ?>"><?= esc($item['title']) ?></a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= esc($item['title']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alerts (tetap seperti pola project) -->
<?= function_exists('show_alerts') ? show_alerts() : '' ?>

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

<?php if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="mdi mdi-information me-2"></i>
        <?= esc(session()->getFlashdata('info')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards (dipertahankan) -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Total Kelas</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)($stats['total'] ?? 0) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title">
                                <i class="mdi mdi-google-classroom font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Kelas Aktif</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)($stats['active'] ?? 0) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                            <span class="avatar-title">
                                <i class="mdi mdi-check-circle font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Kelas X</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)($stats['by_grade']['X'] ?? 0) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                            <span class="avatar-title">
                                <i class="mdi mdi-numeric-10-box font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Kelas XI & XII</span>
                        <h4 class="mb-3">
                            <span class="counter-value" data-target="<?= (int)(($stats['by_grade']['XI'] ?? 0) + ($stats['by_grade']['XII'] ?? 0)) ?>">0</span>
                        </h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                            <span class="avatar-title">
                                <i class="mdi mdi-school font-size-24"></i>
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
                <form action="<?= base_url('admin/classes') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach ($academic_years as $year): ?>
                                    <?php
                                        $yid = (string)($year['id'] ?? '');
                                        $yname = (string)($year['year_name'] ?? '-');
                                        $sem = (string)($year['semester'] ?? '');
                                    ?>
                                    <option value="<?= esc($yid) ?>" <?= ($filterAcademicYear === $yid) ? 'selected' : '' ?>>
                                        <?= esc($yname) ?><?= $sem ? ' - ' . esc($sem) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tingkat</label>
                            <select name="grade_level" class="form-select">
                                <option value="">Semua Tingkat</option>
                                <?php foreach ($grade_levels as $k => $v): ?>
                                    <?php
                                        $val = (string)$k;
                                        $lbl = is_string($v) ? $v : (string)$k;
                                    ?>
                                    <option value="<?= esc($val) ?>" <?= ($filterGrade === $val) ? 'selected' : '' ?>>
                                        <?= esc($lbl) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Jurusan</label>
                            <select name="major" class="form-select">
                                <option value="">Semua Jurusan</option>
                                <?php foreach ($majors as $k => $v): ?>
                                    <?php
                                        $val = is_string($k) ? (string)$k : (string)$v;
                                        $lbl = is_string($k) ? (string)$k : (string)$v;
                                    ?>
                                    <option value="<?= esc($val) ?>" <?= ($filterMajor === $val) ? 'selected' : '' ?>>
                                        <?= esc($lbl) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="">Semua Status</option>
                                <?php if (!empty($status_options) && is_array($status_options)): ?>
                                    <?php foreach ($status_options as $k => $v): ?>
                                        <option value="<?= esc((string)$k) ?>" <?= ($filterStatus === (string)$k) ? 'selected' : '' ?>>
                                            <?= esc((string)$v) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="1" <?= ($filterStatus === '1') ? 'selected' : '' ?>>Aktif</option>
                                    <option value="0" <?= ($filterStatus === '0') ? 'selected' : '' ?>>Tidak Aktif</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Pencarian</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Nama kelas, wali kelas..."
                                   value="<?= esc($filterSearch) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('admin/classes') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Classes Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-google-classroom me-2"></i>Daftar Kelas
                </h4>
                <a href="<?= base_url('admin/classes/create') ?>" class="btn btn-success btn-rounded waves-effect waves-light">
                    <i class="mdi mdi-plus me-1"></i> Tambah Kelas
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="classesTable" class="table table-striped table-hover align-middle mb-0 table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Kelas</th>
                                <th>Tahun Ajaran</th>
                                <th>Tingkat</th>
                                <th>Jurusan</th>
                                <th>Wali Kelas</th>
                                <th>Guru BK</th>
                                <th width="10%" class="text-center">Siswa</th>
                                <th width="8%" class="text-center">Status</th>
                                <th width="12%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="mdi mdi-information-outline font-size-24 text-muted d-block mb-2"></i>
                                        <span class="text-muted">Tidak ada data kelas</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $class): ?>
                                    <?php
                                        $isActive = !empty($class['is_active']);
                                        $studentCount = (int)($class['student_count'] ?? 0);
                                        $maxStudents  = (int)($class['max_students'] ?? 36);
                                    ?>
                                    <tr>
                                        <!-- No diisi oleh DataTables agar selalu urut -->
                                        <td class="text-center"></td>

                                        <td>
                                            <strong><?= esc($class['class_name'] ?? '-') ?></strong>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success ms-2">Aktif</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= esc($class['year_name'] ?? '-') ?>
                                            <small class="text-muted d-block"><?= esc($class['semester'] ?? '-') ?></small>
                                        </td>

                                        <td>
                                            <span class="badge bg-primary"><?= esc($class['grade_level'] ?? '-') ?></span>
                                        </td>

                                        <td><?= esc($class['major'] ?? '-') ?></td>

                                        <td>
                                            <?php if (!empty($class['homeroom_name'])): ?>
                                                <i class="mdi mdi-account-tie text-primary me-1"></i>
                                                <?= esc($class['homeroom_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">- Belum ditugaskan -</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($class['counselor_name'])): ?>
                                                <i class="mdi mdi-account-heart text-success me-1"></i>
                                                <?= esc($class['counselor_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">- Belum ditugaskan -</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-info font-size-12">
                                                <?= $studentCount ?> / <?= $maxStudents ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tidak Aktif</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('admin/classes/detail/' . (int)($class['id'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-info"
                                                   data-bs-toggle="tooltip"
                                                   title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <a href="<?= base_url('admin/classes/edit/' . (int)($class['id'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-primary"
                                                   data-bs-toggle="tooltip"
                                                   title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="confirmDelete(<?= (int)($class['id'] ?? 0) ?>, '<?= esc($class['class_name'] ?? '') ?>')"
                                                        data-bs-toggle="tooltip"
                                                        title="Hapus">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (dipertahankan) -->
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
                <p>Apakah Anda yakin ingin menghapus kelas <strong id="className"></strong>?</p>
                <div class="alert alert-warning" role="alert">
                    <i class="mdi mdi-alert me-2"></i>
                    <strong>Perhatian:</strong> Kelas yang memiliki siswa tidak dapat dihapus.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" method="post" style="display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete me-1"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables (samakan dengan counselor/sessions) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Confirm delete (dipertahankan)
    function confirmDelete(id, className) {
        document.getElementById('className').textContent = className;
        document.getElementById('deleteForm').action = '<?= base_url('admin/classes/delete/') ?>' + id;

        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }

    $(document).ready(function() {
        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });

        // Counter animation (dipertahankan)
        const counters = document.querySelectorAll('.counter-value');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const increment = (target > 0) ? (target / 100) : 1;

            const updateCounter = () => {
                const current = +counter.innerText;
                if (current < target) {
                    counter.innerText = Math.ceil(current + increment);
                    setTimeout(updateCounter, 10);
                } else {
                    counter.innerText = target;
                }
            };
            updateCounter();
        });

        // DataTables pagination di VIEW (konsisten ala counselor/sessions)
        <?php if (!empty($classes) && is_array($classes)): ?>
            var table;

            if (window.SIBK && typeof SIBK.initDataTable === 'function') {
                table = SIBK.initDataTable('classesTable', {
                    pageLength: 10,
                    order: [[1, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 9] } // No + Aksi
                    ]
                });
            } else {
                table = $('#classesTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[1, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 9] }
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

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?= $this->endSection() ?>
