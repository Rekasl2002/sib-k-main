<?php

/**
 * File Path: app/Views/counselor/cases/index.php
 *
 * Cases Index View
 * Tampilan daftar kasus pelanggaran siswa dengan filter, statistik, dan aksi
 *
 * Catatan:
 * - Pagination + "Tampilkan ... data" + "Cari:" menggunakan DataTables (di View).
 * - Filter card dibuat konsisten dengan counselor/sessions.
 * - Repeat offender tetap didukung (checkbox is_repeat_offender=1).
 * - Menampilkan badge "Pelanggar Berulang" pada baris yang relevan.
 * - Kompatibel dengan $stats berbentuk $stats['overall'][key] atau $stats[key].
 *
 * @package    SIB-K
 * @subpackage Views/Counselor/Cases
 * @category   View
 * @created    2025-01-06
 * @updated    2026-01-07
 */

$this->extend('layouts/main');
$this->section('content');
?>

<?php
if (!function_exists('val')) {
    function val($row, $key) {
        return esc(is_array($row) ? ($row[$key] ?? '') : ($row->$key ?? ''));
    }
}

if (!function_exists('statv')) {
    function statv($stats, $key) {
        if (is_array($stats) && isset($stats['overall']) && is_array($stats['overall']) && array_key_exists($key, $stats['overall'])) {
            return (int) ($stats['overall'][$key] ?? 0);
        }
        return (int) ($stats[$key] ?? 0);
    }
}
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Kasus & Pelanggaran</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kasus & Pelanggaran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php helper('app'); ?>
<?= show_alerts() ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Terdapat kesalahan pada input:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block">Total Pelanggaran</span>
                        <h4 class="mb-3"><?= statv($stats ?? [], 'total_violations') ?></h4>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <div class="avatar-sm rounded-circle bg-soft-primary">
                            <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-3">
                                <i class="mdi mdi-alert-circle-outline"></i>
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
                        <span class="text-muted mb-3 lh-1 d-block">Dalam Proses</span>
                        <h4 class="mb-3"><span class="text-warning"><?= statv($stats ?? [], 'in_process') ?></span></h4>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <div class="avatar-sm rounded-circle bg-soft-warning">
                            <span class="avatar-title bg-soft-warning text-warning rounded-circle fs-3">
                                <i class="mdi mdi-progress-clock"></i>
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
                        <span class="text-muted mb-3 lh-1 d-block">Selesai</span>
                        <h4 class="mb-3"><span class="text-success"><?= statv($stats ?? [], 'completed') ?></span></h4>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <div class="avatar-sm rounded-circle bg-soft-success">
                            <span class="avatar-title bg-soft-success text-success rounded-circle fs-3">
                                <i class="mdi mdi-check-circle-outline"></i>
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
                        <span class="text-muted mb-3 lh-1 d-block">Pending Notifikasi</span>
                        <h4 class="mb-3"><span class="text-danger"><?= statv($stats ?? [], 'parents_not_notified') ?></span></h4>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <div class="avatar-sm rounded-circle bg-soft-danger">
                            <span class="avatar-title bg-soft-danger text-danger rounded-circle fs-3">
                                <i class="mdi mdi-bell-alert-outline"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (statv($stats ?? [], 'repeat_offenders') > 0): ?>
                    <div class="mt-2">
                        <span class="badge bg-danger">
                            <i class="mdi mdi-repeat"></i> Repeat Offenders: <?= statv($stats ?? [], 'repeat_offenders') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card (konsisten dengan sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('counselor/cases') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="Dilaporkan"   <?= ($filters['status'] ?? '') === 'Dilaporkan' ? 'selected' : '' ?>>Dilaporkan</option>
                                <option value="Dalam Proses" <?= ($filters['status'] ?? '') === 'Dalam Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                                <option value="Selesai"      <?= ($filters['status'] ?? '') === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="Dibatalkan"   <?= ($filters['status'] ?? '') === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tingkat Keparahan</label>
                            <select name="severity_level" class="form-select">
                                <option value="">Semua Tingkat</option>
                                <option value="Ringan" <?= ($filters['severity_level'] ?? '') === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
                                <option value="Sedang" <?= ($filters['severity_level'] ?? '') === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                                <option value="Berat"  <?= ($filters['severity_level'] ?? '') === 'Berat'  ? 'selected' : '' ?>>Berat</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>
                    </div>

                    <div class="row mt-2 g-3">
                        <div class="col-md-4">
                            <label class="form-label">Siswa</label>
                            <select name="student_id" class="form-select" id="studentFilter">
                                <option value="">Semua Siswa</option>
                                <?php foreach (($students ?? []) as $student): ?>
                                    <option value="<?= (int)$student['id'] ?>" <?= ($filters['student_id'] ?? '') == $student['id'] ? 'selected' : '' ?>>
                                        <?= esc($student['full_name'] ?? '') ?> - <?= esc($student['nisn'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-select" id="categoryFilter">
                                <option value="">Semua Kategori</option>
                                <?php foreach (($categories ?? []) as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>" <?= ($filters['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                        <?= esc($category['category_name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Notifikasi Ortu</label>
                            <select name="parent_notified" class="form-select">
                                <option value="">Semua</option>
                                <option value="no" <?= ($filters['parent_notified'] ?? '') === 'no' ? 'selected' : '' ?>>Belum Dinotifikasi</option>
                            </select>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_repeat_offender" value="1"
                                       <?= !empty($filters['is_repeat_offender']) ? 'checked' : '' ?>>
                                <label class="form-check-label">Pelanggar berulang</label>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('counselor/cases') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Table (DataTables seperti sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">Daftar Pelanggaran</h4>
                    <small class="text-muted">Pagination dan pencarian tabel memakai DataTables.</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-primary">Total: <?= (int) (is_countable($violations ?? []) ? count($violations) : 0) ?> data</span>
                    <a href="<?= base_url('counselor/cases/create') ?>" class="btn btn-success">
                        <i class="mdi mdi-plus me-1"></i> Tambah Pelanggaran
                    </a>
                </div>
            </div>

            <div class="card-body">
                <?php if (!empty($violations) && is_array($violations)): ?>
                    <div class="table-responsive">
                        <table id="casesTable" class="table table-hover table-bordered nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">No</th>
                                    <th>Tanggal</th>
                                    <th>Siswa</th>
                                    <th>Kategori</th>
                                    <th>Tingkat</th>
                                    <th>Poin</th>
                                    <th>Status</th>
                                    <th>Penanganan</th>
                                    <th width="180">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($violations as $violation): ?>
                                    <?php
                                        $severity = $violation['severity_level'] ?? '';
                                        $severityBadge = match ($severity) {
                                            'Ringan' => 'bg-info',
                                            'Sedang' => 'bg-warning',
                                            'Berat'  => 'bg-danger',
                                            default  => 'bg-secondary'
                                        };

                                        $status = $violation['status'] ?? '-';
                                        $statusBadge = match ($status) {
                                            'Dilaporkan'   => 'bg-info',
                                            'Dalam Proses' => 'bg-warning',
                                            'Selesai'      => 'bg-success',
                                            'Dibatalkan'   => 'bg-secondary',
                                            default        => 'bg-secondary'
                                        };
                                    ?>
                                    <tr>
                                        <!-- No diisi oleh DataTables -->
                                        <td class="text-center"></td>

                                        <td data-order="<?= esc($violation['violation_date'] ?? '') ?>">
                                            <strong><?= !empty($violation['violation_date']) ? date('d/m/Y', strtotime($violation['violation_date'])) : '-' ?></strong>
                                            <?php if (!empty($violation['violation_time'])): ?>
                                                <br><small class="text-muted"><?= date('H:i', strtotime($violation['violation_time'])) ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <strong><?= esc($violation['student_name'] ?? '-') ?></strong><br>
                                            <small class="text-muted">
                                                <?= esc($violation['nisn'] ?? '') ?>
                                                <?php if (!empty($violation['class_name'])): ?>
                                                    | <?= esc($violation['class_name']) ?>
                                                <?php endif; ?>
                                            </small>
                                            <?php if (!empty($violation['is_repeat_offender'])): ?>
                                                <br><span class="badge bg-danger mt-1">
                                                    <i class="mdi mdi-repeat"></i> Pelanggar Berulang
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= esc($violation['category_name'] ?? '-') ?></td>

                                        <td>
                                            <span class="badge <?= $severityBadge ?>">
                                                <?= esc($severity ?: '-') ?>
                                            </span>
                                        </td>

                                        <td><strong class="text-danger">-<?= (int)($violation['point_deduction'] ?? 0) ?></strong></td>

                                        <td>
                                            <span class="badge <?= $statusBadge ?>"><?= esc($status) ?></span>
                                            <?php if (isset($violation['parent_notified']) && !$violation['parent_notified']): ?>
                                                <br><span class="badge bg-danger mt-1">
                                                    <i class="mdi mdi-bell-off"></i> Belum Notifikasi
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($violation['handler_name'])): ?>
                                                <small><?= esc($violation['handler_name']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Belum ditangani</small>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('counselor/cases/detail/' . (int)$violation['id']) ?>" class="btn btn-sm btn-info" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>

                                                <?php if (function_exists('is_guru_bk') && function_exists('is_koordinator')): ?>
                                                    <?php
                                                        $uid = (int) (function_exists('auth_id') ? auth_id() : 0);
                                                        $canEditDelete = is_koordinator()
                                                            || ((int)($violation['handled_by']  ?? 0) === $uid)
                                                            || ((int)($violation['reported_by'] ?? 0) === $uid);
                                                    ?>
                                                    <?php if ($canEditDelete): ?>
                                                        <a href="<?= base_url('counselor/cases/edit/' . (int)$violation['id']) ?>" class="btn btn-sm btn-warning" title="Ubah">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteViolation(<?= (int)$violation['id'] ?>)" title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-1">
                                <i class="mdi mdi-alert-circle-outline"></i>
                            </span>
                        </div>
                        <h5 class="mb-2">Tidak Ada Data Pelanggaran</h5>
                        <p class="text-muted mb-3">Belum ada data pelanggaran yang tercatat atau sesuai filter yang dipilih.</p>
                        <a href="<?= base_url('counselor/cases/create') ?>" class="btn btn-success">
                            <i class="mdi mdi-plus me-1"></i> Tambah Pelanggaran Baru
                        </a>
                    </div>
                <?php endif; ?>
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

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Delete Violation with Confirmation (POST + CSRF)
    function deleteViolation(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data pelanggaran ini?\n\nData yang terhapus tidak dapat dikembalikan!')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= base_url('counselor/cases/delete/') ?>' + id;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '<?= csrf_token() ?>';
            csrf.value = '<?= csrf_hash() ?>';
            form.appendChild(csrf);

            document.body.appendChild(form);
            form.submit();
        }
    }

    $(document).ready(function() {
        // Select2 untuk dropdown agar konsisten dengan sessions
        $('#studentFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Siswa',
            allowClear: true,
            width: '100%'
        });
        $('#categoryFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Kategori',
            allowClear: true,
            width: '100%'
        });

        <?php if (!empty($violations) && is_array($violations)): ?>
            var table;

            if (window.SIBK && typeof SIBK.initDataTable === 'function') {
                table = SIBK.initDataTable('casesTable', {
                    pageLength: 10,
                    order: [[1, 'desc']], // Tanggal desc
                    columnDefs: [
                        { orderable: false, targets: [0, 8] } // No + Aksi
                    ]
                });
            } else {
                table = $('#casesTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[1, 'desc']],
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

            // Nomor urut (mulai 1) konsisten seperti sessions
            function renumber() {
                var info = table.page.info();
                table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                    cell.innerHTML = info.start + i + 1;
                });
            }
            table.on('order.dt search.dt draw.dt', renumber);
            renumber();
        <?php endif; ?>

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?php $this->endSection(); ?>
