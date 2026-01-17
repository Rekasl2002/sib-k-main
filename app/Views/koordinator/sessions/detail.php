<?php

/**
 * File Path: app/Views/koordinator/sessions/detail.php
 *
 * Koordinator BK - Session Detail (Read-only)
 * Halaman untuk menampilkan detail sesi konseling lengkap dengan peserta & catatan (tanpa aksi ubah data)
 */

$this->extend('layouts/main');
$this->section('content');

// Load helper alerts (sesuai proyek kamu)
helper('app');

// Normalisasi variabel agar aman dipakai di view
$participants = $participants ?? [];
// Dukung pola: $notes dikirim terpisah ATAU tertanam di $session['notes']
$notes        = $notes ?? ($session['notes'] ?? []);

// Get status badge class
$statusBadgeClass = match ($session['status'] ?? '') {
    'Dijadwalkan' => 'bg-info',
    'Selesai'     => 'bg-success',
    'Dibatalkan'  => 'bg-danger',
    default       => 'bg-secondary'
};
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Detail Sesi Konseling</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/sessions') ?>">Sesi Konseling</a></li>
                    <li class="breadcrumb-item active">Detail Sesi Konseling</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?= show_alerts() ?>

<!-- Session Detail -->
<div class="row">
    <div class="col-lg-8">
        <!-- Main Session Info -->
        <div class="card">
            <div class="card-header bg-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0 text-white">
                        <i class="mdi mdi-information-outline me-2"></i>Informasi Sesi
                    </h4>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($session['is_confidential'])): ?>
                            <span class="badge bg-warning fs-6">
                                <i class="mdi mdi-lock me-1"></i> Rahasia
                            </span>
                        <?php endif; ?>
                        <span class="badge <?= $statusBadgeClass ?> fs-6">
                            <?= esc($session['status'] ?? '-') ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Session Header -->
                <div class="mb-4 pb-3 border-bottom">
                    <h3 class="mb-3"><?= esc($session['topic'] ?? 'Tanpa Judul') ?></h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-soft-primary text-primary rounded">
                                            <i class="mdi mdi-shape fs-5"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1">Tipe Sesi</p>
                                    <h6 class="mb-0"><?= esc($session['session_type'] ?? '-') ?></h6>
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
                                    <p class="text-muted mb-1">Tanggal</p>
                                    <h6 class="mb-0">
                                        <?php if (!empty($session['session_date'])): ?>
                                            <?= date('d F Y', strtotime($session['session_date'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </h6>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($session['session_time'])): ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-soft-warning text-warning rounded">
                                                <i class="mdi mdi-clock-outline fs-5"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">Waktu</p>
                                        <h6 class="mb-0"><?= date('H:i', strtotime($session['session_time'])) ?> WIB</h6>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($session['location'])): ?>
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
                                        <h6 class="mb-0"><?= esc($session['location']) ?></h6>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($session['duration_minutes'])): ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-soft-danger text-danger rounded">
                                                <i class="mdi mdi-timer-outline fs-5"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">Durasi</p>
                                        <h6 class="mb-0"><?= esc($session['duration_minutes']) ?> Menit</h6>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Student/Class Info -->
                <?php if (($session['session_type'] ?? '') === 'Individu' && !empty($session['student_id'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3"><i class="mdi mdi-account me-2"></i>Siswa</h5>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md">
                                    <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-4">
                                        <?= strtoupper(substr((string)($session['student_name'] ?? '??'), 0, 2)) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1"><?= esc($session['student_name'] ?? 'Tanpa Nama') ?></h6>
                                <p class="text-muted mb-0">
                                    <?php if (!empty($session['student_nisn'])): ?>
                                        <?= esc($session['student_nisn']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($session['class_name'])): ?>
                                        <?= !empty($session['student_nisn']) ? ' | ' : '' ?>Kelas <?= esc($session['class_name']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php elseif (in_array(($session['session_type'] ?? ''), ['Kelompok', 'Klasikal'], true)): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3"><i class="mdi mdi-google-classroom me-2"></i>Kelas / Peserta</h5>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md">
                                    <span class="avatar-title bg-soft-info text-info rounded-circle fs-4">
                                        <i class="mdi mdi-account-group"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <?php if (!empty($session['class_name'])): ?>
                                    <h6 class="mb-1">Kelas <?= esc($session['class_name']) ?></h6>
                                <?php endif; ?>
                                <p class="text-muted mb-0">
                                    <i class="mdi mdi-account-multiple me-1"></i><?= count((array) $participants) ?> Peserta
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Problem Description -->
                <?php if (!empty($session['problem_description'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3"><i class="mdi mdi-text-box-outline me-2"></i>Deskripsi Masalah/Topik</h5>
                        <p class="text-muted mb-0"><?= nl2br(esc($session['problem_description'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Session Summary -->
                <?php if (!empty($session['session_summary'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3"><i class="mdi mdi-file-document-outline me-2"></i>Ringkasan Hasil Sesi</h5>
                        <p class="text-muted mb-0"><?= nl2br(esc($session['session_summary'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Follow-up Plan -->
                <?php if (!empty($session['follow_up_plan'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3"><i class="mdi mdi-calendar-check me-2"></i>Rencana Tindak Lanjut</h5>
                        <p class="text-muted mb-0"><?= nl2br(esc($session['follow_up_plan'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Cancellation Reason -->
                <?php if (($session['status'] ?? '') === 'Dibatalkan' && !empty($session['cancellation_reason'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3 text-danger"><i class="mdi mdi-alert-circle-outline me-2"></i>Alasan Pembatalan</h5>
                        <div class="alert alert-danger mb-0">
                            <?= nl2br(esc($session['cancellation_reason'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Participants List (read-only) -->
                <?php if (in_array(($session['session_type'] ?? ''), ['Kelompok', 'Klasikal'], true)): ?>
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="mdi mdi-account-multiple-outline me-2"></i>Daftar Peserta</h5>

                        <?php if (!empty($participants)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 48px;">#</th>
                                            <th>NIS/NISN</th>
                                            <th>Nama</th>
                                            <th>Kehadiran</th>
                                            <th>Catatan Partisipasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; foreach ((array) $participants as $p): ?>
                                            <?php
                                                $pNis  = $p['nis'] ?? ($p['nisn'] ?? '');
                                                $pName = $p['student_name'] ?? ($p['name'] ?? 'Tanpa Nama');
                                                $pAtt  = $p['attendance_status'] ?? '-';
                                                $pNote = $p['participation_note'] ?? '';
                                            ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= esc($pNis) ?></td>
                                                <td><?= esc($pName) ?></td>
                                                <td>
                                                    <?php if (!empty($pAtt) && $pAtt !== '-'): ?>
                                                        <span class="badge bg-light text-dark border"><?= esc($pAtt) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $pNote ? nl2br(esc($pNote)) : '<span class="text-muted">-</span>' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light mb-0">
                                Belum ada peserta tercatat untuk sesi ini.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Metadata -->
                <div class="mt-4 pt-3 border-top">
                    <div class="row text-muted">
                        <div class="col-md-6">
                            <small><i class="mdi mdi-clock-outline me-1"></i>Dibuat:
                                <?php if (!empty($session['created_at'])): ?>
                                    <?= date('d M Y H:i', strtotime($session['created_at'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small><i class="mdi mdi-update me-1"></i>Diperbarui:
                                <?php if (!empty($session['updated_at'])): ?>
                                    <?= date('d M Y H:i', strtotime($session['updated_at'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Session Notes (read-only) -->
        <div class="card">
            <div class="card-header bg-success">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0 text-white">
                        <i class="mdi mdi-note-text-outline me-2"></i>Catatan Sesi
                    </h4>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($notes)): ?>
                    <div class="timeline">
                        <?php foreach ($notes as $note): ?>
                            <?php
                                $author      = $note['counselor_name'] ?? ('User #' . (int)($note['created_by'] ?? 0));
                                $noteType    = $note['note_type'] ?? 'Observasi';
                                $createdAt   = !empty($note['created_at']) ? date('d M Y H:i', strtotime($note['created_at'])) : '-';
                                $isImportant = !empty($note['is_important']);
                                $isSecret    = !empty($note['is_confidential']);

                                $attachments = [];
                                if (!empty($note['attachments'])) {
                                    $decoded = json_decode((string)$note['attachments'], true);
                                    if (is_array($decoded)) {
                                        $attachments = array_values(array_filter($decoded, 'strlen'));
                                    }
                                }
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                <h6 class="mb-0"><?= esc($author) ?></h6>
                                                <span class="badge bg-light text-muted border">
                                                    <?= esc($noteType) ?>
                                                </span>
                                                <?php if ($isSecret): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="mdi mdi-lock me-1"></i>Catatan Rahasia
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($isImportant): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="mdi mdi-star me-1"></i>Penting
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="mdi mdi-clock-outline me-1"></i><?= $createdAt ?>
                                            </small>
                                        </div>
                                    </div>

                                    <p class="text-muted mb-0"><?= nl2br(esc($note['note_content'] ?? '')) ?></p>

                                    <?php if (!empty($attachments)): ?>
                                        <div class="mt-2">
                                            <small class="text-muted d-block mb-1">
                                                <i class="mdi mdi-paperclip me-1"></i>Lampiran:
                                            </small>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($attachments as $path): ?>
                                                    <li>
                                                        <a href="<?= base_url($path) ?>" target="_blank" rel="noopener">
                                                            <?= esc(basename($path)) ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-1">
                                <i class="mdi mdi-note-text-outline"></i>
                            </span>
                        </div>
                        <h5 class="mb-2">Belum Ada Catatan</h5>
                        <p class="text-muted mb-0">Catatan akan dibuat oleh Guru BK.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Info Box (Read-only) -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="mdi mdi-information-outline me-2"></i>Info</h5>
            </div>
            <div class="card-body">
                    Koordinator hanya melihat data sesi. Perubahan data dilakukan oleh Guru BK (Counselor).
            </div>
        </div>

        <!-- Back Button -->
        <div class="card">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= base_url('koordinator/sessions') ?>" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

        <!-- Counselor Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="mdi mdi-account-tie me-2"></i>Guru BK</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-lg">
                            <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-3">
                                <?= strtoupper(substr((string)($session['counselor_name'] ?? '??'), 0, 2)) ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1"><?= esc($session['counselor_name'] ?? 'Guru BK') ?></h6>
                        <p class="text-muted mb-0">
                            <small><?= esc($session['counselor_email'] ?? '-') ?></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php if (!empty($notes)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="mdi mdi-chart-line me-2"></i>Statistik</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Total Catatan</span>
                        <strong><?= count($notes) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Catatan Penting</span>
                        <strong class="text-danger">
                            <?= count(array_filter($notes, fn($n) => !empty($n['is_important']))) ?>
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Catatan Rahasia</span>
                        <strong class="text-warning">
                            <?= count(array_filter($notes, fn($n) => !empty($n['is_confidential']))) ?>
                        </strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
    /* Timeline Styles */
    .timeline { position: relative; padding-left: 30px; }
    .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
    .timeline-item { position: relative; margin-bottom: 30px; }
    .timeline-item:last-child { margin-bottom: 0; }
    .timeline-marker { position: absolute; left: -24px; width: 20px; height: 20px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #e9ecef; }
    .timeline-content { padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 3px solid #0ab39c; }

    /* Avatar Styles */
    .avatar-sm { width: 48px; height: 48px; }
    .avatar-md { width: 64px; height: 64px; }
    .avatar-lg { width: 80px; height: 80px; }
    .avatar-title { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; }

    /* Soft Backgrounds */
    .bg-soft-primary { background-color: rgba(64, 81, 237, 0.1) !important; }
    .bg-soft-info    { background-color: rgba(41, 156, 219, 0.1) !important; }
    .bg-soft-success { background-color: rgba(10, 179, 156, 0.1) !important; }
    .bg-soft-warning { background-color: rgba(249, 176, 20, 0.1) !important; }
    .bg-soft-danger  { background-color: rgba(242, 82, 82, 0.1) !important; }
</style>
<?php $this->endSection(); ?>
