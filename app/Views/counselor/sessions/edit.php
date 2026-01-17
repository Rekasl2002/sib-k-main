<?php

/**
 * File Path: app/Views/counselor/sessions/edit.php
 *
 * Edit Session View
 * Form untuk mengedit sesi konseling yang sudah ada
 *
 * @package    SIB-K
 * @subpackage Views/Counselor/Sessions
 * @category   View
 * @author     Development Team
 * @created    2025-01-06
 */

$this->extend('layouts/main');
$this->section('content');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Sesi Konseling</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/sessions') ?>">Sesi Konseling</a></li>
                    <li class="breadcrumb-item active">Edit Sesi Konseling</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?= show_alerts() ?>
<?php
// Kumpulkan error dari berbagai sumber secara aman
$__errs = [];

// 1) Dari CI4 validator (setelah $this->validate(...) / withInput())
$val = \Config\Services::validation();
if ($val && $val->getErrors()) {
    $__errs = array_merge($__errs, $val->getErrors());
}

// 2) Dari flashdata 'errors' (custom validation atau manual errors)
if (session()->has('errors')) {
    $e = session('errors');
    if (is_string($e)) {
        $__errs[] = $e;
    } elseif (is_array($e)) {
        foreach ($e as $v) {
            if (is_array($v)) {
                foreach ($v as $m) { $__errs[] = $m; }
            } elseif (is_string($v)) {
                $__errs[] = $v;
            }
        }
    }
}

// 3) Jika masih memakai helper validation_errors() yang mungkin mengembalikan array
if (function_exists('validation_errors')) {
    $ve = validation_errors();
    if (is_string($ve) && trim($ve) !== '') {
        $__errs[] = $ve;
    } elseif (is_array($ve)) {
        foreach ($ve as $v) {
            if (is_array($v)) {
                foreach ($v as $m) { $__errs[] = $m; }
            } elseif (is_string($v)) {
                $__errs[] = $v;
            }
        }
    }
}

// Rapikan & filter
$__errs = array_values(array_unique(array_filter(array_map('strval', $__errs))));
?>

<?php if (!empty($__errs)) : ?>
    <div class="alert alert-danger">
        <strong>Periksa kembali data Anda:</strong>
        <ul class="mb-0">
            <?php foreach ($__errs as $m) : ?>
                <li><?= esc($m) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Edit Session Form -->
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="card-title mb-0 text-white">
                    <i class="mdi mdi-pencil me-2"></i>Form Edit Sesi Konseling
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('counselor/sessions/update/' . $session['id']) ?>" method="post" id="editSessionForm">
                    <?= csrf_field() ?>

                    <!-- Session Info (Read-only) -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>ID Sesi:</strong> #<?= $session['id'] ?><br>
                                <strong>Dibuat:</strong> <?= indonesian_datetime($session['created_at']) ?>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <strong>Konselor:</strong> <?= esc(auth_name()) ?><br>
                                <strong>Status:</strong> <?= status_badge($session['status']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Session Type (Read-only) -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Jenis Sesi Konseling</label>
                            <div class="alert alert-secondary mb-0">
                                <i class="mdi mdi-information"></i>
                                <strong><?= esc($session['session_type']) ?></strong>
                                <small class="text-muted">(Jenis sesi tidak dapat diubah setelah dibuat)</small>
                            </div>
                            <input type="hidden" name="session_type" value="<?= esc($session['session_type']) ?>">
                        </div>
                    </div>

                    <!-- Student/Class Info (Read-only) atau Kelompok (editable) -->
                    <?php if ($session['session_type'] === 'Individu' && $session['student_id']): ?>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Siswa</label>
                                <input type="text" class="form-control"
                                value="<?= esc(($session['student_name'] ?? 'N/A')
                                        . (!empty($session['student_nis'])  ? ' ('.$session['student_nis'].')' : '')
                                        . (!empty($session['class_name'])   ? ' — Kelas '.$session['class_name'] : '')) ?>"
                                readonly>
                                <input type="hidden" name="student_id" value="<?= (int)$session['student_id'] ?>">
                            </div>
                        </div>
                    <?php elseif ($session['session_type'] === 'Klasikal' && $session['class_id']): ?>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Kelas</label>
                                <input type="text" class="form-control"value="Kelas <?= esc($session['class_name'] ?? 'N/A') ?>" readonly>
                                <input type="hidden" name="class_id" value="<?= (int)$session['class_id'] ?>">
                            </div>
                        </div>
                    <?php elseif ($session['session_type'] === 'Kelompok'): ?>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Peserta Kelompok</label>
                                <select name="participants[]" id="participants" class="form-select" multiple>
                                    <?php foreach (($students ?? []) as $stu): ?>
                                        <?php
                                            $sid        = (int)($stu['id'] ?? 0);
                                            $isSelected = in_array($sid, $selectedParticipantIds ?? [], true);
                                            $nm         = $stu['student_name'] ?? ($stu['name'] ?? 'Tanpa Nama');
                                            $nis        = $stu['nis'] ?? ($stu['nisn'] ?? '');
                                            $cls        = $stu['class_name'] ?? '';
                                        ?>
                                        <option value="<?= $sid ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= esc($nm) ?><?= $nis ? ' ('.esc($nis).')' : '' ?><?= $cls ? ' — '.esc($cls) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Tahan Ctrl/⌘ untuk memilih lebih dari satu.</small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="mb-4">

                    <!-- Session Date & Time -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Sesi</label>
                            <input type="date" name="session_date" class="form-control" value="<?= old_value('session_date', $session['session_date']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Waktu Sesi</label>
                            <?php
                            $__timeVal = old_value('session_time', $session['session_time'] ?? '');
                            if (is_string($__timeVal) && preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $__timeVal)) {
                                $__timeVal = substr($__timeVal, 0, 5); // jadikan "HH:MM" agar valid untuk input[type=time]
                            }
                            ?>
                            <input type="time" name="session_time" class="form-control" step="60" value="<?= esc($__timeVal) ?>">
                        </div>
                    </div>

                    <!-- Topic & Location -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label required">Topik/Judul Sesi</label>
                            <input type="text" name="topic" class="form-control" value="<?= old_value('topic', $session['topic']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" class="form-control" value="<?= old_value('location', $session['location']) ?>">
                        </div>
                    </div>

                    <!-- Problem Description -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi Masalah/Topik</label>
                            <textarea name="problem_description" class="form-control" rows="3"><?= old_value('problem_description', $session['problem_description']) ?></textarea>
                        </div>
                    </div>

                    <!-- Session Summary -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Ringkasan Hasil Sesi</label>
                            <textarea name="session_summary" class="form-control" rows="4" placeholder="Tuliskan ringkasan hasil sesi konseling, kesimpulan, atau catatan penting..."><?= old_value('session_summary', $session['session_summary']) ?></textarea>
                            <small class="text-muted">Ringkasan hasil pelaksanaan sesi konseling</small>
                        </div>
                    </div>

                    <!-- Follow-up Plan -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Rencana Tindak Lanjut</label>
                            <textarea name="follow_up_plan" class="form-control" rows="3" placeholder="Tuliskan rencana tindak lanjut atau rekomendasi setelah sesi ini..."><?= old_value('follow_up_plan', $session['follow_up_plan']) ?></textarea>
                            <small class="text-muted">Rencana tindak lanjut atau sesi berikutnya</small>
                        </div>
                    </div>

                    <!-- Status & Duration & Confidential -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label required">Status Sesi</label>
                            <select name="status" id="statusSelect" class="form-select" required>
                                <option value="Dijadwalkan" <?= old_value('status', $session['status']) === 'Dijadwalkan' ? 'selected' : '' ?>>Dijadwalkan</option>
                                <option value="Selesai" <?= old_value('status', $session['status']) === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="Dibatalkan" <?= old_value('status', $session['status']) === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durasi (menit)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="<?= old_value('duration_minutes', $session['duration_minutes']) ?>" min="1" max="480">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">Kerahasiaan</label>
                            <div class="form-check form-switch form-switch-lg mt-2">
                                <!-- kirim 0 saat dimatikan -->
                                <input type="hidden" name="is_confidential" value="0">
                                <?php $___isConf = (string) old_value('is_confidential', (string)($session['is_confidential'] ?? '0')) === '1'; ?>
                                <input class="form-check-input" type="checkbox" name="is_confidential" id="isConfidential" value="1" <?= $___isConf ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isConfidential">
                                    <i class="mdi mdi-lock"></i> Sesi Rahasia
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Reason (conditional) -->
                    <div class="row mb-3" id="cancellationReasonDiv" style="display: none;">
                        <div class="col-md-12">
                            <label class="form-label required">Alasan Pembatalan</label>
                            <textarea name="cancellation_reason" id="cancellationReason" class="form-control" rows="3" placeholder="Tuliskan alasan pembatalan sesi ini..."><?= old_value('cancellation_reason', $session['cancellation_reason']) ?></textarea>
                            <small class="text-danger">Wajib diisi jika status dibatalkan</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-warning btn-lg text-white">
                                <i class="mdi mdi-content-save me-1"></i> Update Sesi
                            </button>
                            <a href="<?= base_url('counselor/sessions/detail/' . $session['id']) ?>" class="btn btn-secondary btn-lg">
                                <i class="mdi mdi-arrow-left me-1"></i> Kembali
                            </a>
                            <a href="<?= base_url('counselor/sessions') ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="mdi mdi-format-list-bulleted me-1"></i> Ke Daftar
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="card border-warning">
            <div class="card-body">
                <h5 class="card-title text-warning">
                    <i class="mdi mdi-lightbulb"></i> Tips
                </h5>
                <ul class="mb-0">
                    <li>Ubah status ke <strong>"Selesai"</strong> setelah sesi konseling dilaksanakan</li>
                    <li>Isi <strong>"Ringkasan Hasil Sesi"</strong> untuk dokumentasi hasil konseling</li>
                    <li>Gunakan <strong>"Rencana Tindak Lanjut"</strong> untuk sesi berikutnya</li>
                    <li>Jika membatalkan sesi, wajib isi <strong>"Alasan Pembatalan"</strong></li>
                    <li>Anda dapat menambahkan catatan detail di halaman detail sesi</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<style>
    .required:after {
        content: " *";
        color: red;
    }
</style>

<script>
    $(document).ready(function() {
        // Handle status change
        function handleStatusChange() {
            const status = $('#statusSelect').val();
            const cancellationDiv = $('#cancellationReasonDiv');
            const cancellationTextarea = $('#cancellationReason');

            if (status === 'Dibatalkan') {
                cancellationDiv.show();
                cancellationTextarea.prop('required', true);
            } else {
                cancellationDiv.hide();
                cancellationTextarea.prop('required', false);
            }
        }

        // Trigger on change
        $('#statusSelect').on('change', handleStatusChange);

        // Trigger on page load
        handleStatusChange();

        // Form validation before submit
        $('#editSessionForm').on('submit', function(e) {
            const status = $('#statusSelect').val();
            const cancellationReason = ($('#cancellationReason').val() || '').trim();

            // Validate cancellation reason if status is Dibatalkan
            if (status === 'Dibatalkan' && !cancellationReason) {
                e.preventDefault();
                alert('Alasan pembatalan harus diisi jika status dibatalkan!');
                $('#cancellationReason').focus();
                return false;
            }

            // Confirm if changing status to Selesai
            <?php $prevStatus = $session['status'] ?? ''; ?>
            if (status === 'Selesai' && '<?= esc($prevStatus) ?>' !== 'Selesai') {
                if (!confirm('Apakah Anda yakin sesi ini sudah selesai dilaksanakan?')) {
                    e.preventDefault();
                    return false;
                }
            }

            // Confirm if changing status to Dibatalkan
            if (status === 'Dibatalkan' && '<?= esc($prevStatus) ?>' !== 'Dibatalkan') {
                if (!confirm('Apakah Anda yakin ingin membatalkan sesi ini?')) {
                    e.preventDefault();
                    return false;
                }
            }

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="mdi mdi-loading mdi-spin me-1"></i> Menyimpan...');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert-success, .alert-danger, .alert-warning').not('.alert-info, .alert-light').fadeOut('slow');
        }, 5000);
    });
</script>
<?php $this->endSection(); ?>
