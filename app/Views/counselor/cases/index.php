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
 * - Kompatibel dengan $stats berbentuk:
 *   - $stats['overall'][key] (hasil ViolationService::getViolationStats)
 *   - ATAU format dari getDashboardStats (overall + pending_notifications, dll)
 * - Kompatibel dengan $violations berbentuk:
 *   - array list rows
 *   - ATAU array dengan key ['violations' => [...], 'pager' => ...]
 *
 * Revisi KP:
 * - Tambah filter "Tahun Ajaran" (year_name). Jika dipilih dan date_from/date_to kosong,
 *   service akan mengisi otomatis range tahun ajaran (gabungan ganjil+genap).
 */

$this->extend('layouts/main');
$this->section('content');
?>

<?php
// --------------------------
// Helpers kecil (tahan banting)
// --------------------------
if (!function_exists('val')) {
    function val($row, $key) {
        return esc(is_array($row) ? ($row[$key] ?? '') : ($row->$key ?? ''));
    }
}

/**
 * Ambil angka statistik dari beberapa kemungkinan struktur.
 */
if (!function_exists('statv')) {
    function statv($stats, $key, $fallbackKey = null) {
        // 1) stats['overall'][key]
        if (is_array($stats) && isset($stats['overall']) && is_array($stats['overall'])) {
            if (array_key_exists($key, $stats['overall'])) {
                return (int) ($stats['overall'][$key] ?? 0);
            }
            if ($fallbackKey !== null && array_key_exists($fallbackKey, $stats['overall'])) {
                return (int) ($stats['overall'][$fallbackKey] ?? 0);
            }
        }

        // 2) stats[key] di root
        if (is_array($stats) && array_key_exists($key, $stats)) {
            return (int) ($stats[$key] ?? 0);
        }
        if (is_array($stats) && $fallbackKey !== null && array_key_exists($fallbackKey, $stats)) {
            return (int) ($stats[$fallbackKey] ?? 0);
        }

        return 0;
    }
}

// --------------------------
// Normalisasi data $violations (bisa list atau wrapper)
// --------------------------
$violationsRaw = $violations ?? [];
$violationRows = $violationsRaw;
$pager         = null;

if (is_array($violationsRaw) && array_key_exists('violations', $violationsRaw)) {
    $violationRows = $violationsRaw['violations'] ?? [];
    $pager         = $violationsRaw['pager'] ?? null;
}

if (!is_array($violationRows)) {
    $violationRows = [];
}

// --------------------------
// Academic Year options (year_name)
// - Disarankan controller mengirim $academic_year_options dari service
// - Fallback: ambil langsung dari DB kalau belum dikirim
// --------------------------
$academicYearOptions = $academic_year_options ?? [];
if (!is_array($academicYearOptions)) {
    $academicYearOptions = [];
}
if (empty($academicYearOptions)) {
    try {
        $db = \Config\Database::connect();
        $rows = $db->table('academic_years')
            ->select('DISTINCT year_name')
            ->where('deleted_at', null)
            ->orderBy('year_name', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($rows as $r) {
            $yn = trim((string)($r['year_name'] ?? ''));
            if ($yn !== '') $academicYearOptions[] = $yn;
        }
    } catch (\Throwable $e) {
        $academicYearOptions = [];
    }
}

$selectedAcademicYear = trim((string)($filters['academic_year'] ?? ''));

// Statistik cards: mapping aman
$totalViolations = statv($stats ?? [], 'total_violations', 'total');
$inProcess       = statv($stats ?? [], 'in_process', 'in_progress');
$completed       = statv($stats ?? [], 'completed', 'done');

// Pending Notifikasi: kadang ada di overall['parents_not_notified'], kadang ada root['pending_notifications']
$pendingNotif = statv($stats ?? [], 'parents_not_notified');
if ($pendingNotif <= 0) {
    $pendingNotif = statv($stats ?? [], 'pending_notifications');
}

// Repeat offenders: tergantung model/service
$repeatOffenders = statv($stats ?? [], 'repeat_offenders', 'repeat_offender_count');
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
            <?php foreach ((array) session()->getFlashdata('errors') as $error): ?>
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
                        <h4 class="mb-3"><?= (int)$totalViolations ?></h4>
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
                        <h4 class="mb-3"><span class="text-warning"><?= (int)$inProcess ?></span></h4>
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
                        <h4 class="mb-3"><span class="text-success"><?= (int)$completed ?></span></h4>
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

    <!--<div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3 lh-1 d-block">Pending Notifikasi</span>
                        <h4 class="mb-3"><span class="text-danger"><?= (int)$pendingNotif ?></span></h4>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <div class="avatar-sm rounded-circle bg-soft-danger">
                            <span class="avatar-title bg-soft-danger text-danger rounded-circle fs-3">
                                <i class="mdi mdi-bell-alert-outline"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ((int)$repeatOffenders > 0): ?>
                    <div class="mt-2">
                        <span class="badge bg-danger">
                            <i class="mdi mdi-repeat"></i> Repeat Offenders: <?= (int)$repeatOffenders ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>-->
</div>

<!-- Filter Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">

                <div class="card mb-2"> 
                    <div class="card-body">Filter akan mencari data sesuai kelas-kelas yang dibina/ditugaskan.
                            Data diluar kelas binaan tidak akan muncul.
                            Misalnya, jika ditugaskan pada kelas <b>X-Mualimin-Ganjil-2025/2026</b>,
                            maka saat dipindahkan ke kelas <b>X-Mualimin-Genap-2025/2026</b>, sehingga data kelas sebelumnya tidak akan muncul.
                    </div>
                </div>

                <form action="<?= base_url('counselor/cases') ?>" method="get" id="filterForm">

                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="Dilaporkan"   <?= ($filters['status'] ?? '') === 'Dilaporkan' ? 'selected' : '' ?>>Dilaporkan</option>
                                <option value="Dalam Proses" <?= ($filters['status'] ?? '') === 'Dalam Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                                <option value="Selesai"      <?= ($filters['status'] ?? '') === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="Dibatalkan"   <?= ($filters['status'] ?? '') === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tingkat Keparahan</label>
                            <select name="severity_level" class="form-select">
                                <option value="">Semua Tingkat</option>
                                <option value="Ringan" <?= ($filters['severity_level'] ?? '') === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
                                <option value="Sedang" <?= ($filters['severity_level'] ?? '') === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                                <option value="Berat"  <?= ($filters['severity_level'] ?? '') === 'Berat'  ? 'selected' : '' ?>>Berat</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="academic_year" class="form-select" id="academicYearFilter">
                                <option value="">Tahun Ajaran Aktif</option>
                                <?php foreach (($academicYearOptions ?? []) as $yn): ?>
                                    <option value="<?= esc($yn) ?>" <?= $selectedAcademicYear === (string)$yn ? 'selected' : '' ?>>
                                        <?= esc($yn) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block mt-1">
                                Kosongkan Tanggal jika ingin menggunakan filter Tahun Ajaran.
                            </small>
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
                            <!--<label class="form-label">Notifikasi Ortu</label>
                            <select name="parent_notified" class="form-select">
                                <option value="">Semua</option>
                                <option value="no" <?= ($filters['parent_notified'] ?? '') === 'no' ? 'selected' : '' ?>>Belum Dinotifikasi</option>
                            </select>-->

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

<!-- Data Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">Daftar Pelanggaran</h4>
                    <small class="text-muted">Pagination dan pencarian tabel memakai DataTables.</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-primary">Total: <?= (int) (is_countable($violationRows) ? count($violationRows) : 0) ?> data</span>
                    <a href="<?= base_url('counselor/cases/create') ?>" class="btn btn-success">
                        <i class="mdi mdi-plus me-1"></i> Tambah Pelanggaran
                    </a>
                </div>
            </div>

            <div class="card-body">
                <?php if (!empty($violationRows) && is_array($violationRows)): ?>
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
                                <?php foreach ($violationRows as $violation): ?>
                                    <?php
                                        $severity = (string)($violation['severity_level'] ?? '');
                                        $severityBadge = 'bg-secondary';
                                        if ($severity === 'Ringan') $severityBadge = 'bg-info';
                                        elseif ($severity === 'Sedang') $severityBadge = 'bg-warning';
                                        elseif ($severity === 'Berat') $severityBadge = 'bg-danger';

                                        $status = (string)($violation['status'] ?? '-');
                                        $statusBadge = 'bg-secondary';
                                        if ($status === 'Dilaporkan') $statusBadge = 'bg-info';
                                        elseif ($status === 'Dalam Proses') $statusBadge = 'bg-warning';
                                        elseif ($status === 'Selesai') $statusBadge = 'bg-success';
                                        elseif ($status === 'Dibatalkan') $statusBadge = 'bg-secondary';

                                        $vDate = $violation['violation_date'] ?? '';
                                        $vTime = $violation['violation_time'] ?? '';
                                    ?>
                                    <tr>
                                        <!-- No diisi oleh DataTables -->
                                        <td class="text-center"></td>

                                        <td data-order="<?= esc($vDate) ?>">
                                            <strong>
                                                <?php
                                                if (!empty($vDate) && strtotime($vDate) !== false) {
                                                    echo esc(date('d/m/Y', strtotime($vDate)));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </strong>
                                            <?php if (!empty($vTime) && strtotime($vTime) !== false): ?>
                                                <br><small class="text-muted"><?= esc(date('H:i', strtotime($vTime))) ?></small>
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
                                            <span class="badge <?= esc($severityBadge) ?>">
                                                <?= esc($severity !== '' ? $severity : '-') ?>
                                            </span>
                                        </td>

                                        <td><strong class="text-danger">-<?= (int)($violation['point_deduction'] ?? 0) ?></strong></td>

                                        <td>
                                            <span class="badge <?= esc($statusBadge) ?>"><?= esc($status) ?></span>
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
                                                <a href="<?= base_url('counselor/cases/detail/' . (int)($violation['id'] ?? 0)) ?>" class="btn btn-sm btn-info" title="Detail">
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
                                                        <a href="<?= base_url('counselor/cases/edit/' . (int)($violation['id'] ?? 0)) ?>" class="btn btn-sm btn-warning" title="Ubah">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteViolation(<?= (int)($violation['id'] ?? 0) ?>)" title="Hapus">
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
        if (!id) return;

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
        // Select2 untuk dropdown agar konsisten
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

        $('#academicYearFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Tahun Ajaran',
            allowClear: true,
            width: '100%'
        });

        <?php if (!empty($violationRows) && is_array($violationRows)): ?>
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

            // Nomor urut (mulai 1)
            function renumber() {
                if (!table) return;
                var info = table.page.info();
                table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                    cell.innerHTML = info.start + i + 1;
                });
            }
            if (table) {
                table.on('order.dt search.dt draw.dt', renumber);
                renumber();
            }
        <?php endif; ?>

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?php $this->endSection(); ?>
