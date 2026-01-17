<?php

/**
 * File Path: app/Views/homeroom_teacher/violations/index.php
 *
 * Homeroom Teacher Violations Index View
 * Daftar pelanggaran dengan filter dan search
 *
 * @package    SIB-K
 * @subpackage Views/HomeroomTeacher
 * @category   View
 * @author     Development Team
 * @created    2025-01-07
 */

?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helpers kecil supaya view lebih tahan banting
if (!function_exists('rowa')) {
    function rowa($r): array
    {
        return is_array($r)
            ? $r
            : (is_object($r) ? (array)$r : []);
    }
}
if (!function_exists('h')) {
    function h($v)
    {
        return esc($v ?? '');
    }
}

$violations = $violations ?? [];
$students   = $students   ?? [];
$categories = $categories ?? [];
$filters    = $filters    ?? [];
$class      = rowa($class ?? $homeroom_class ?? []);
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <?= esc($pageTitle ?? $title ?? 'Daftar Pelanggaran Siswa') ?>
            </h4>

            <?php if (!empty($breadcrumbs ?? [])): ?>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <?php foreach ($breadcrumbs as $bc): ?>
                            <li class="breadcrumb-item<?= !empty($bc['active']) ? ' active' : '' ?>">
                                <?php if (!empty($bc['url']) && empty($bc['active'])): ?>
                                    <a href="<?= esc($bc['url']) ?>"><?= esc($bc['title']) ?></a>
                                <?php else: ?>
                                    <?= esc($bc['title']) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Flash messages -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <i class="mdi mdi-alert-circle-outline me-2"></i>
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <i class="mdi mdi-check-circle-outline me-2"></i>
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Filter & Info kelas -->
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h5 class="card-title mb-0">Filter Pelanggaran</h5>
                    <small class="text-muted">
                        Kelas Perwalian:
                        <strong><?= h($class['class_name'] ?? '-') ?></strong>
                        <?php if (!empty($class['year_name'])): ?>
                            (<?= h($class['year_name']) ?> · Semester <?= h($class['semester'] ?? '-') ?>)
                        <?php endif; ?>
                    </small>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="<?= base_url('homeroom/violations/create') ?>" class="btn btn-danger">
                        <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Pelanggaran
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form method="get" action="<?= current_url() ?>" class="row g-3 align-items-end">

                    <!-- Siswa -->
                    <div class="col-md-3">
                        <label class="form-label">Siswa</label>
                        <select name="student_id" class="form-select">
                            <option value="">Semua Siswa</option>
                            <?php foreach ($students as $student): ?>
                                <?php $student = rowa($student); ?>
                                <option value="<?= esc($student['id']) ?>"
                                    <?= ($filters['student_id'] ?? '') == $student['id'] ? 'selected' : '' ?>>
                                    <?= esc($student['nisn'] ?? '-') ?> - <?= esc($student['full_name'] ?? '-') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Kategori -->
                    <div class="col-md-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php $cat = rowa($cat); ?>
                                <option value="<?= esc($cat['id']) ?>"
                                    <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= esc($cat['category_name'] ?? '-') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tingkat Pelanggaran -->
                    <div class="col-md-2">
                        <label class="form-label">Tingkat</label>
                        <select name="severity_level" class="form-select">
                            <option value="">Semua</option>
                            <?php
                            $sevFilter = $filters['severity_level'] ?? '';
                            $levels = ['Ringan', 'Sedang', 'Berat'];
                            foreach ($levels as $lvl): ?>
                                <option value="<?= esc($lvl) ?>" <?= $sevFilter === $lvl ? 'selected' : '' ?>>
                                    <?= esc($lvl) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <?php
                            $statusFilter = $filters['status'] ?? '';
                            $statusList = ['Dilaporkan', 'Dalam Proses', 'Selesai', 'Dibatalkan'];
                            foreach ($statusList as $st): ?>
                                <option value="<?= esc($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>>
                                    <?= esc($st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rentang tanggal -->
                    <div class="col-md-2">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date"
                               name="start_date"
                               value="<?= esc($filters['start_date'] ?? '') ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date"
                               name="end_date"
                               value="<?= esc($filters['end_date'] ?? '') ?>"
                               class="form-control">
                    </div>

                    <!-- Pencarian -->
                    <div class="col-md-3">
                        <label class="form-label">Cari (nama, NISN, kategori)</label>
                        <input type="text"
                               name="search"
                               value="<?= esc($filters['search'] ?? '') ?>"
                               class="form-control"
                               placeholder="Ketik kata kunci...">
                    </div>

                    <!-- Tombol -->
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="mdi mdi-magnify me-1"></i> Terapkan Filter
                        </button>
                        <a href="<?= base_url('homeroom/violations') ?>" class="btn btn-outline-secondary">
                            <i class="mdi mdi-close-circle-outline"></i>
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabel data pelanggaran -->
<div class="row">
    <div class="col-12">
        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Pelanggaran</h5>
            </div>

            <div class="card-body">
                <?php if (empty($violations)): ?>
                    <div class="text-center py-4">
                        <div class="avatar-md mx-auto mb-3">
                            <span class="avatar-title bg-soft-info text-info rounded-circle fs-3">
                                <i class="mdi mdi-alert-circle-outline"></i>
                            </span>
                        </div>
                        <h6 class="mb-1">Belum ada pelanggaran</h6>
                        <p class="text-muted mb-0">
                            Silakan gunakan tombol <strong>Tambah Pelanggaran</strong> untuk mencatat pelanggaran baru.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th>Siswa</th>
                                    <th>Kategori</th>
                                    <th>Tingkat</th>
                                    <th>Tanggal</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($violations as $v): ?>
                                    <?php
                                    $v = rowa($v);

                                    // Badge tingkat
                                    $severity = (string)($v['severity_level'] ?? '');
                                    $sevBadge = match ($severity) {
                                        'Ringan' => 'badge bg-success',
                                        'Sedang' => 'badge bg-warning text-dark',
                                        'Berat'  => 'badge bg-danger',
                                        default  => 'badge bg-secondary',
                                    };

                                    // Badge status (perbaikan: jangan pakai teks putih di background terang)
                                    $status = (string)($v['status'] ?? '');
                                    $statusBadge = match ($status) {
                                        'Dilaporkan'      => 'badge bg-info text-dark',
                                        'Dalam Proses'    => 'badge bg-warning text-dark',
                                        'Selesai'         => 'badge bg-success',
                                        'Dibatalkan'      => 'badge bg-secondary',
                                        default           => 'badge bg-light text-dark',
                                    };
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <div class="fw-semibold">
                                                <?= h($v['student_name'] ?? '-') ?>
                                            </div>
                                            <small class="text-muted">
                                                NISN: <?= h($v['nisn'] ?? '-') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div><?= h($v['category_name'] ?? '-') ?></div>
                                            <?php if (!empty($v['point_deduction'])): ?>
                                                <small class="text-muted">
                                                    Poin: -<?= (int)$v['point_deduction'] ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="<?= $sevBadge ?>">
                                                <?= h($severity ?: '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($v['violation_date'])): ?>
                                                <?= date('d/m/Y', strtotime($v['violation_date'])) ?>
                                                <?php if (!empty($v['violation_time'])): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= date('H:i', strtotime($v['violation_time'])) ?> WIB
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($v['location'])): ?>
                                                <?= h($v['location']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="<?= $statusBadge ?>">
                                                <?= h($status ?: '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('homeroom/violations/detail/' . $v['violation_id']) ?>"
                                            class="btn btn-sm btn-outline-primary mb-1">
                                                <i class="mdi mdi-eye-outline"></i> Detail
                                            </a>

                                            <?php if (($v['severity_level'] ?? '') === 'Ringan'): ?>
                                                <a href="<?= base_url('homeroom/violations/edit/' . $v['violation_id']) ?>"
                                                class="btn btn-sm btn-outline-warning mb-1 ms-1">
                                                    <i class="mdi mdi-pencil-outline"></i> Edit
                                                </a>
                                            <?php endif; ?>
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tombol export (placeholder, bisa dihubungkan ke fitur export nanti)
        const exportExcelBtn = document.getElementById('exportExcel');
        const exportPdfBtn   = document.getElementById('exportPDF');

        function showExportInfo() {
            if (window.SIBK && typeof SIBK.showAlert === 'function') {
                SIBK.showAlert('Fitur export akan tersedia di fase berikutnya.', 'info');
            } else {
                alert('Fitur export akan tersedia di fase berikutnya.');
            }
        }

        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', showExportInfo);
        }
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', showExportInfo);
        }

        // Tooltip (jaga-jaga kalau view ini nanti pakai data-bs-toggle="tooltip")
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
<?= $this->endSection() ?>
