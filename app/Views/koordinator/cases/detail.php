<?php

/**
 * File Path: app/Views/koordinator/cases/detail.php
 *
 * Koordinator • Case Detail View
 * Disamakan pola dengan counselor/cases/detail.php, dengan kebutuhan Koordinator:
 * - Prefix kasus tetap /koordinator
 * - Assign Guru BK ada (sidebar) meski hanya R/U (tidak wajib manage_violations)
 * - Aksi ekstra (hapus/notif/tambah sanksi) hanya jika manage_violations aktif
 * - Aksi sanksi mengikuti manage_sanctions (edit/hapus/tambah)
 */

$this->extend('layouts/main');
$this->section('content');

// helper optional: jangan fatal kalau helper tidak ada
try {
    helper(['app', 'permission']);
} catch (\Throwable $e) {
    // ignore
}

// Badge status & severity (samakan pola counselor, tapi aman jika null)
$statusVal   = (string)($violation['status'] ?? '-');
$severityVal = (string)($violation['severity_level'] ?? '-');

$statusBadgeClass = match ($statusVal) {
    'Dilaporkan'    => 'bg-info',
    'Dalam Proses'  => 'bg-warning',
    'Selesai'       => 'bg-success',
    'Dibatalkan'    => 'bg-secondary',
    default         => 'bg-secondary'
};

$severityBadgeClass = match ($severityVal) {
    'Ringan' => 'bg-info',
    'Sedang' => 'bg-warning',
    'Berat'  => 'bg-danger',
    default  => 'bg-secondary'
};

// ID pelanggaran
$violationId = (int)($violation['id'] ?? 0);

// Permission helper (robust)
$can = function (string $perm): bool {
    if (function_exists('has_permission')) return (bool) has_permission($perm);
    // fallback: jangan blok UI jika helper permission tidak tersedia (controller tetap jadi guard utama)
    return true;
};

$isKoordinator = function_exists('is_koordinator') ? (bool) is_koordinator() : false;

// Akses sesuai aturan terbaru
$canUpdateCase       = $isKoordinator;                    // U untuk Koordinator (default)
$canExtraViolations  = $can('manage_violations');         // CRUD ekstra: tambah/hapus/notify (dan sebelumnya assign)
$canSanctionsManage  = $can('manage_sanctions');          // kelola sanksi
$canAddSanction      = $canExtraViolations && $canSanctionsManage;
$canEditSanction     = $canSanctionsManage;
$canDeleteSanction   = $canSanctionsManage && $canExtraViolations;

// ✅ Assign Counselor: boleh walau hanya R/U (default Koordinator), atau jika diberi permission khusus
// Catatan: controller & routes tetap harus meng-guard (idealnya pakai permission:assign_counselor).
$canAssignCounselor  = $canUpdateCase || $can('assign_counselor') || $canExtraViolations;

// ✅ Prefix UI untuk halaman sanksi:
// Kalau view koordinator/sanctions belum ada, fallback ke counselor agar tidak ViewException.
$hasKoordSanctionViews = is_file(APPPATH . 'Views/koordinator/sanctions/show.php')
    && is_file(APPPATH . 'Views/koordinator/sanctions/edit.php');
$sanctionUiPrefix = $hasKoordSanctionViews ? 'koordinator' : 'counselor';

// URL yang mungkin dipakai oleh partial modal add_sanction (biar fleksibel)
$sanction_store_url_case = base_url('koordinator/cases/addSanction/' . $violationId);
$sanction_store_url_ctrl = base_url('koordinator/sanctions/store/' . $violationId);
?>

<!-- Alert Messages -->
<?= function_exists('show_alerts') ? show_alerts() : '' ?>

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

<?php if ($violationId <= 0): ?>
    <div class="alert alert-danger">Data pelanggaran tidak valid.</div>
    <?php $this->endSection(); return; ?>
<?php endif; ?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Detail Kasus & Pelanggaran</h4>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/cases') ?>">Kasus & Pelanggaran</a></li>
                    <li class="breadcrumb-item active">Detail Kasus & Pelanggaran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">

        <!-- Violation Info Card -->
        <div class="card">
            <div class="card-header bg-danger">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0 text-white">
                        <i class="mdi mdi-alert-circle-outline me-2"></i>Informasi Pelanggaran
                    </h4>
                    <span class="badge <?= $statusBadgeClass ?> fs-6"><?= esc($statusVal) ?></span>
                </div>
            </div>

            <div class="card-body">
                <!-- Header Info -->
                <div class="mb-4 pb-3 border-bottom">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-soft-danger text-danger rounded">
                                            <i class="mdi mdi-alert fs-5"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1">Kategori</p>
                                    <h6 class="mb-0"><?= esc($violation['category_name'] ?? '-') ?></h6>
                                    <span class="badge <?= $severityBadgeClass ?> mt-1"><?= esc($severityVal) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-soft-info text-info rounded">
                                            <i class="mdi mdi-calendar fs-5"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1">Tanggal Kejadian</p>
                                    <h6 class="mb-0">
                                        <?= !empty($violation['violation_date']) ? date('d F Y', strtotime($violation['violation_date'])) : '-' ?>
                                    </h6>
                                    <?php if (!empty($violation['violation_time'])): ?>
                                        <small class="text-muted"><?= date('H:i', strtotime($violation['violation_time'])) ?> WIB</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($violation['location'])): ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-soft-success text-success rounded">
                                                <i class="mdi mdi-map-marker fs-5"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">Lokasi</p>
                                        <h6 class="mb-0"><?= esc($violation['location']) ?></h6>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-soft-warning text-warning rounded">
                                            <i class="mdi mdi-chart-line fs-5"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1">Poin Pengurangan</p>
                                    <h6 class="mb-0 text-danger">-<?= (int)($violation['point_deduction'] ?? 0) ?> Poin</h6>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Student Info -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3"><i class="mdi mdi-account me-2"></i>Data Siswa</h5>
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-md">
                                <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-4">
                                    <?= esc(strtoupper(substr((string)($violation['student_name'] ?? 'S'), 0, 2))) ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1"><?= esc($violation['student_name'] ?? '-') ?></h6>
                            <p class="text-muted mb-0">
                                NISN: <?= esc($violation['nisn'] ?? '-') ?>
                                <?php if (!empty($violation['class_name'])): ?>
                                    | Kelas: <?= esc($violation['class_name']) ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($violation['is_repeat_offender'])): ?>
                                <span class="badge bg-danger mt-1">
                                    <i class="mdi mdi-repeat"></i> Pelanggar Berulang
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3"><i class="mdi mdi-text-box-outline me-2"></i>Deskripsi Pelanggaran</h5>
                    <p class="text-muted mb-0"><?= nl2br(esc((string)($violation['description'] ?? ''))) ?></p>
                </div>

                <!-- Witness & Evidence -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3"><i class="mdi mdi-account-eye-outline me-2"></i>Saksi & Bukti</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Saksi</p>
                            <?php if (!empty($violation['witness'])): ?>
                                <h6 class="mb-0"><?= esc($violation['witness']) ?></h6>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada saksi dicatat.</span>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <?php
                                $files = $violation['evidence_files']
                                    ?? (is_string($violation['evidence'] ?? null) ? json_decode($violation['evidence'], true) : []);
                                if (!is_array($files)) { $files = []; }
                            ?>
                            <p class="text-muted mb-1">Bukti</p>

                            <?php if ($files): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($files as $file):
                                        $rel = ltrim(preg_replace('#/+#','/', (string)$file), '/');
                                        $url = base_url($rel);
                                        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
                                        $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
                                    ?>
                                        <li class="d-flex align-items-center mb-2">
                                            <i class="mdi <?= $isImage ? 'mdi-image' : 'mdi-file' ?> text-secondary me-2"></i>
                                            <a href="<?= $url ?>" target="_blank" rel="noopener" class="text-decoration-underline">
                                                Lihat bukti
                                            </a>
                                            <small class="text-muted ms-2"><?= esc(basename($rel)) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada bukti terlampir.</span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- Resolution Section -->
                <?php if (!empty($violation['resolution_notes'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3"><i class="mdi mdi-file-document-outline me-2"></i>Catatan Penyelesaian</h5>
                        <p class="text-muted mb-0"><?= nl2br(esc((string)$violation['resolution_notes'])) ?></p>
                        <?php if (!empty($violation['resolution_date'])): ?>
                            <small class="text-muted">
                                <i class="mdi mdi-calendar-check me-1"></i>
                                Diselesaikan: <?= date('d M Y', strtotime($violation['resolution_date'])) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Notes -->
                <?php if (!empty($violation['notes'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3"><i class="mdi mdi-note-text-outline me-2"></i>Catatan Tambahan</h5>
                        <p class="text-muted mb-0"><?= nl2br(esc((string)$violation['notes'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Reporter & Handler Info -->
                <div class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted mb-1">Dilaporkan Oleh:</label>
                            <p class="mb-0"><strong><?= esc($violation['reporter_name'] ?? '-') ?></strong></p>
                            <small class="text-muted"><?= esc($violation['reporter_email'] ?? '-') ?></small>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-1">Ditangani Oleh:</label>
                            <?php if (!empty($violation['handler_name'])): ?>
                                <p class="mb-0"><strong><?= esc($violation['handler_name']) ?></strong></p>
                                <small class="text-muted"><?= esc($violation['handler_email'] ?? '-') ?></small>
                            <?php else: ?>
                                <p class="mb-0 text-muted">Belum ditugaskan</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Parent Notification 
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Status Notifikasi Orang Tua:</strong>
                            <?php if (!empty($violation['parent_notified'])): ?>
                                <span class="badge bg-success ms-2">
                                    <i class="mdi mdi-check"></i> Sudah Dinotifikasi
                                </span>
                                <?php if (!empty($violation['parent_notified_at'])): ?>
                                    <br><small class="text-muted">
                                        Pada: <?= date('d M Y H:i', strtotime($violation['parent_notified_at'])) ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2">
                                    <i class="mdi mdi-bell-off"></i> Belum Dinotifikasi
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($violation['parent_notified']) && $canExtraViolations): ?>
                            <form action="<?= base_url('koordinator/cases/notifyParent/' . $violationId) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="mdi mdi-send me-1"></i>Kirim Notifikasi
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>-->

                <!-- Metadata -->
                <div class="mt-4 pt-3 border-top">
                    <div class="row text-muted">
                        <div class="col-md-6">
                            <?php if (!empty($violation['created_at'])): ?>
                                <small><i class="mdi mdi-clock-outline me-1"></i>Dibuat: <?= date('d M Y H:i', strtotime($violation['created_at'])) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($violation['updated_at'])): ?>
                            <div class="col-md-6 text-md-end">
                                <small><i class="mdi mdi-update me-1"></i>Diperbarui: <?= date('d M Y H:i', strtotime($violation['updated_at'])) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Sanctions Section -->
        <div class="card">
            <div class="card-header bg-warning">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0 text-white">
                        <i class="mdi mdi-gavel me-2"></i>Sanksi yang Diberikan
                    </h4>

                    <?php if ($canAddSanction && ($violation['status'] ?? '') !== 'Dibatalkan'): ?>
                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addSanctionModal">
                            <i class="mdi mdi-plus me-1"></i>Tambah Sanksi
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <?php if (!empty($violation['sanctions'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis Sanksi</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Pemberi</th>
                                    <th width="170">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($violation['sanctions'] as $sanction): ?>
                                    <?php $sid = (int)($sanction['id'] ?? 0); ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($sanction['sanction_type'] ?? '-') ?></strong>
                                            <?php if (!empty($sanction['duration_days'])): ?>
                                                <br><small class="text-muted">Durasi: <?= (int)$sanction['duration_days'] ?> hari</small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= !empty($sanction['sanction_date']) ? date('d/m/Y', strtotime($sanction['sanction_date'])) : '-' ?>
                                            <?php if (!empty($sanction['start_date']) && !empty($sanction['end_date'])): ?>
                                                <br><small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($sanction['start_date'])) ?> -
                                                    <?= date('d/m/Y', strtotime($sanction['end_date'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php
                                            $sanctionStatus = (string)($sanction['status'] ?? '-');
                                            $sanctionBadge = match ($sanctionStatus) {
                                                'Dijadwalkan'      => 'bg-info',
                                                'Sedang Berjalan'  => 'bg-warning',
                                                'Selesai'          => 'bg-success',
                                                'Dibatalkan'       => 'bg-secondary',
                                                default            => 'bg-secondary',
                                            };
                                            ?>
                                            <span class="badge <?= $sanctionBadge ?>"><?= esc($sanctionStatus) ?></span>
                                        </td>

                                        <td><small><?= esc($sanction['assigned_by_name'] ?? '-') ?></small></td>

                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <!-- Read selalu tampil -->
                                                <a href="<?= base_url($sanctionUiPrefix . '/sanctions/show/' . $sid) ?>" class="btn btn-info" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>

                                                <?php if ($canEditSanction): ?>
                                                    <a href="<?= base_url($sanctionUiPrefix . '/sanctions/edit/' . $sid) ?>" class="btn btn-primary" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($canDeleteSanction): ?>
                                                    <form action="<?= base_url($sanctionUiPrefix . '/sanctions/delete/' . $sid) ?>"
                                                          method="post"
                                                          onsubmit="return confirm('Hapus sanksi ini? Tindakan tidak dapat dibatalkan.');"
                                                          style="display:inline-block">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-danger" title="Hapus">
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
                    <div class="text-center py-4">
                        <div class="avatar-md mx-auto mb-3">
                            <span class="avatar-title bg-soft-warning text-warning rounded-circle fs-3">
                                <i class="mdi mdi-gavel"></i>
                            </span>
                        </div>
                        <h6 class="mb-2">Belum Ada Sanksi</h6>
                        <p class="text-muted mb-3">Tambahkan sanksi untuk pelanggaran ini</p>
                        <?php if ($canAddSanction && ($violation['status'] ?? '') !== 'Dibatalkan'): ?>
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addSanctionModal">
                                <i class="mdi mdi-plus me-1"></i>Tambah Sanksi
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="mdi mdi-cog-outline me-2"></i>Aksi</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($canUpdateCase && !in_array($statusVal, ['Selesai','Dibatalkan'], true)): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                            <i class="mdi mdi-pencil me-1"></i>Update Status
                        </button>
                    <?php endif; ?>

                    <?php if ($canUpdateCase): ?>
                        <a href="<?= base_url('koordinator/cases/edit/' . $violationId) ?>" class="btn btn-warning">
                            <i class="mdi mdi-file-document-edit-outline me-1"></i>Ubah Data
                        </a>
                    <?php endif; ?>

                    <?php if ($canExtraViolations): ?>
                        <button type="button" class="btn btn-danger" onclick="deleteViolation(<?= $violationId ?>)">
                            <i class="mdi mdi-delete me-1"></i>Hapus Pelanggaran
                        </button>
                    <?php endif; ?>

                    <a href="<?= base_url('koordinator/cases') ?>" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Daftar
                    </a>
                </div>

                <!-- ✅ Assign BK: boleh walau hanya R/U (tidak wajib manage_violations) -->
                <?php if (!empty($counselors) && is_array($counselors) && $canAssignCounselor): ?>
                    <hr>
                    <form action="<?= base_url('koordinator/cases/assignCounselor/' . $violationId) ?>" method="post">
                        <?= csrf_field() ?>
                        <label class="form-label">Tugaskan ke Guru BK</label>
                        <select name="handled_by" class="form-select" required>
                            <option value="">-- Pilih Guru BK --</option>
                            <?php foreach ($counselors as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"
                                    <?= ((int)($violation['handled_by'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                    <?= esc($c['name'] ?? $c['full_name'] ?? $c['email'] ?? ('User#'.$c['id'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary w-100 mt-2" type="submit">
                            <i class="mdi mdi-account-switch me-1"></i>Assign
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student History -->
        <?php
            $hist = $student_history ?? [];
            $histStats = is_array($hist) ? ($hist['statistics'] ?? []) : [];
            $bySeverity = is_array($histStats) ? ($histStats['by_severity'] ?? []) : [];
        ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="mdi mdi-history me-2"></i>Riwayat Siswa</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Pelanggaran:</span>
                        <strong><?= (int)($histStats['total_violations'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Poin:</span>
                        <strong class="text-danger">-<?= (int)($histStats['total_points'] ?? 0) ?></strong>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <h6 class="mb-2">Berdasarkan Tingkat:</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="badge bg-info">Ringan</span>
                        <strong><?= (int)($bySeverity['Ringan'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="badge bg-warning">Sedang</span>
                        <strong><?= (int)($bySeverity['Sedang'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-danger">Berat</span>
                        <strong><?= (int)($bySeverity['Berat'] ?? 0) ?></strong>
                    </div>
                </div>

                <?php if (!empty($histStats['is_repeat_offender'])): ?>
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="mdi mdi-alert-circle-outline me-1"></i>
                        <strong>Pelanggar Berulang!</strong>
                        <p class="mb-0 mt-1 small">Siswa ini memiliki 3+ pelanggaran dalam 3 bulan terakhir</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Add Sanction Modal -->
<?php if ($canAddSanction): ?>
    <?php
        // variabel ini bisa dipakai oleh partial modal jika kamu sudah set di sana
        $sanction_post_url = $sanction_store_url_ctrl; // direkomendasikan pakai SanctionController
        $sanction_post_url_alt = $sanction_store_url_case; // fallback jika modal masih pakai CaseController
    ?>
    <?= $this->include('koordinator/cases/add_sanction') ?>
<?php endif; ?>

<!-- Update Status Modal -->
<?php if ($canUpdateCase && !in_array($statusVal, ['Selesai','Dibatalkan'], true)): ?>
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('koordinator/cases/update/' . $violationId) ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Update Status Pelanggaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status Baru</label>
                        <select name="status" class="form-select" required>
                            <option value="Dilaporkan"   <?= $statusVal === 'Dilaporkan' ? 'selected' : '' ?>>Dilaporkan</option>
                            <option value="Dalam Proses" <?= $statusVal === 'Dalam Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                            <option value="Selesai"      <?= $statusVal === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="Dibatalkan"   <?= $statusVal === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Penyelesaian</label>
                        <textarea name="resolution_notes" class="form-control" rows="3"
                                  placeholder="Opsional, tetapi disarankan saat menandai kasus sebagai Selesai atau Dibatalkan."><?= esc($violation['resolution_notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
    function deleteViolation(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus pelanggaran ini?\n\nData yang terhapus tidak dapat dikembalikan!')) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('koordinator/cases/delete/') ?>' + id;

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '<?= csrf_token() ?>';
        csrf.value = '<?= csrf_hash() ?>';
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }
</script>
<?php $this->endSection(); ?>
