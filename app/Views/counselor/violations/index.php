<?php
/**
 * File Path: app/Views/counselor/violations/index.php
 * Halaman khusus daftar Pelanggaran (lebih sederhana dari "Kasus & Pelanggaran")
 */
$this->extend('layouts/main');
$this->section('content');
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Pelanggaran Siswa</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Pelanggaran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php helper('app'); ?>
<?= show_alerts() ?>

<!-- Ringkasan Singkat -->
<div class="row">
    <div class="col-md-3">
        <div class="card card-h-100">
            <div class="card-body">
                <span class="text-muted d-block">Total Pelanggaran</span>
                <h3 class="mb-0">
                    <?= (int) ($stats['overall']['total_violations'] ?? 0) ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-h-100">
            <div class="card-body">
                <span class="text-muted d-block">Belum Dinotifikasi Ortu</span>
                <h3 class="mb-0 text-danger">
                    <?= (int) ($stats['overall']['parents_not_notified'] ?? 0) ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Filter lebih ringkas -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            <i class="mdi mdi-filter-variant me-2"></i>Filter
        </h5>
        <div>
            <a href="<?= base_url('counselor/violations/create') ?>" class="btn btn-success btn-sm">
                <i class="mdi mdi-plus"></i> Tambah Pelanggaran
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="<?= base_url('counselor/violations') ?>" method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="Dilaporkan"   <?= ($filters['status'] ?? '') === 'Dilaporkan' ? 'selected' : '' ?>>Dilaporkan</option>
                    <option value="Dalam Proses" <?= ($filters['status'] ?? '') === 'Dalam Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                    <option value="Selesai"      <?= ($filters['status'] ?? '') === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tingkat</label>
                <select name="severity_level" class="form-select">
                    <option value="">Semua</option>
                    <option value="Ringan" <?= ($filters['severity_level'] ?? '') === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
                    <option value="Sedang" <?= ($filters['severity_level'] ?? '') === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                    <option value="Berat"  <?= ($filters['severity_level'] ?? '') === 'Berat'  ? 'selected' : '' ?>>Berat</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Siswa</label>
                <select name="student_id" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= ($filters['student_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['full_name'] ?? '-') ?> - <?= esc($s['nisn'] ?? '-') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['category_name'] ?? '-') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Dari</label>
                <input type="date"
                       name="date_from"
                       class="form-control"
                       value="<?= esc($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai</label>
                <input type="date"
                       name="date_to"
                       class="form-control"
                       value="<?= esc($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Notifikasi Ortu</label>
                <select name="parent_notified" class="form-select">
                    <option value="">Semua</option>
                    <option value="no" <?= ($filters['parent_notified'] ?? '') === 'no' ? 'selected' : '' ?>>Belum</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="mdi mdi-magnify me-1"></i>Filter
                    </button>
                    <a href="<?= base_url('counselor/violations') ?>" class="btn btn-secondary">
                        <i class="mdi mdi-refresh me-1"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabel ringkas pelanggaran -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Daftar Pelanggaran</h5>
        <span class="badge bg-primary">
            Total: <?= is_countable($violations ?? []) ? count($violations) : 0 ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (!empty($violations)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Kategori</th>
                            <th>Tingkat</th>
                            <th>Poin</th>
                            <th>Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($violations as $i => $v): ?>
                            <?php
                                $violationId   = (int) ($v['id'] ?? 0);
                                $vDateRaw      = $v['violation_date'] ?? null;
                                $vTimeRaw      = $v['violation_time'] ?? null;
                                $studentName   = $v['student_name'] ?? '-';
                                $nisn          = $v['nisn'] ?? '-';
                                $className     = $v['class_name'] ?? '';
                                $categoryName  = $v['category_name'] ?? '-';
                                $severity      = $v['severity_level'] ?? '';
                                $pointDeduct   = (int) ($v['point_deduction'] ?? 0);
                                $status        = $v['status'] ?? '';

                                $sevClass = $severity === 'Berat'
                                    ? 'bg-danger'
                                    : ($severity === 'Sedang'
                                        ? 'bg-warning'
                                        : 'bg-info');

                                $stClass = $status === 'Selesai'
                                    ? 'bg-success'
                                    : ($status === 'Dalam Proses'
                                        ? 'bg-warning'
                                        : 'bg-info');
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php if (!empty($vDateRaw)): ?>
                                        <strong><?= date('d/m/Y', strtotime($vDateRaw)) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                    <?php if (!empty($vTimeRaw)): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= date('H:i', strtotime($vTimeRaw)) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= esc($studentName) ?></strong><br>
                                    <small class="text-muted">
                                        <?= esc($nisn) ?>
                                        <?php if ($className !== ''): ?>
                                            | <?= esc($className) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td><?= esc($categoryName) ?></td>
                                <td>
                                    <span class="badge <?= $sevClass ?>">
                                        <?= esc($severity) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-danger">
                                        -<?= $pointDeduct ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge <?= $stClass ?>">
                                        <?= esc($status) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= base_url('counselor/violations/detail/' . $violationId) ?>"
                                           class="btn btn-sm btn-info"
                                           title="Detail">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <?php if (function_exists('is_koordinator') && is_koordinator()): ?>
                                            <form action="<?= base_url('counselor/violations/delete/' . $violationId) ?>"
                                                  method="post"
                                                  onsubmit="return confirm('Hapus data ini?')"
                                                  style="display:inline-block;">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-danger" title="Hapus">
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
        <?php else: ?>
            <div class="text-center py-5">
                <div class="avatar-lg mx-auto mb-3">
                    <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-1">
                        <i class="mdi mdi-alert-circle-outline"></i>
                    </span>
                </div>
                <h5 class="mb-2">Belum ada data</h5>
                <p class="text-muted mb-3">
                    Coba ubah filter atau tambahkan pelanggaran baru.
                </p>
                <a href="<?= base_url('counselor/violations/create') ?>" class="btn btn-success">
                    <i class="mdi mdi-plus me-1"></i>Tambah Pelanggaran
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection(); ?>
