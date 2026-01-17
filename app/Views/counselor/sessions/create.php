<?php

/**
 * File Path: app/Views/counselor/sessions/create.php
 * 
 * Create Session View
 * Form untuk menambah sesi konseling baru (dinamis berdasarkan tipe)
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
            <h4 class="mb-sm-0">Tambah Sesi Konseling</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/sessions') ?>">Sesi Konseling</a></li>
                    <li class="breadcrumb-item active">Tambah Sesi Konseling</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php
// Load helper if not loaded
helper('app');
?>
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

<!-- Create Session Form -->
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0 text-white">
                    <i class="mdi mdi-calendar-plus me-2"></i>Form Tambah Sesi Konseling
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('counselor/sessions/store') ?>" method="post" id="createSessionForm">
                    <?= csrf_field() ?>

                    <!-- Session Type Selection -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label required">Jenis Sesi Konseling</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="session_type" id="typeIndividu" value="Individu" <?= old_value('session_type') === 'Individu' ? 'checked' : '' ?> required>
                                <label class="btn btn-outline-info" for="typeIndividu">
                                    <i class="mdi mdi-account"></i> Individu
                                </label>

                                <input type="radio" class="btn-check" name="session_type" id="typeKelompok" value="Kelompok" <?= old_value('session_type') === 'Kelompok' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-warning" for="typeKelompok">
                                    <i class="mdi mdi-account-group"></i> Kelompok
                                </label>

                                <input type="radio" class="btn-check" name="session_type" id="typeKlasikal" value="Klasikal" <?= old_value('session_type') === 'Klasikal' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary" for="typeKlasikal">
                                    <i class="mdi mdi-google-classroom"></i> Klasikal
                                </label>
                            </div>
                            <small class="text-muted">Pilih jenis sesi konseling yang akan dilakukan</small>
                        </div>
                    </div>

                    <hr class="mb-4">

                    <!-- Dynamic Fields Container -->
                    <div id="dynamicFields">

                        <!-- Student Field (for Individu) -->
                        <div class="row mb-3 field-individu" style="display: none;">
                            <div class="col-md-12">
                                <label class="form-label required">Pilih Siswa</label>
                                <select name="student_id" id="studentSelect" class="form-select">
                                    <option value="">-- Pilih Siswa --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>" <?= old_value('student_id') == $student['id'] ? 'selected' : '' ?>>
                                            <?= esc($student['student_name']) ?> - <?= esc($student['nisn']) ?>
                                            <?php if ($student['class_name']): ?>
                                                (<?= esc($student['class_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Class Field (for Klasikal) -->
                        <div class="row mb-3 field-klasikal" style="display: none;">
                            <div class="col-md-12">
                                <label class="form-label required">Pilih Kelas</label>
                                <select name="class_id" id="classSelect" class="form-select">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>" <?= old_value('class_id') == $class['id'] ? 'selected' : '' ?>>
                                            <?= esc($class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Semua siswa di kelas ini akan menjadi peserta</small>
                            </div>
                        </div>

                        <!-- Participants Field (for Kelompok) -->
                        <div class="row mb-3 field-kelompok" style="display: none;">
                            <div class="col-md-12">
                                <label class="form-label required">Pilih Peserta (minimal 2 siswa)</label>
                                <select name="participants[]" id="participantsSelect" class="form-select" multiple>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>">
                                            <?= esc($student['student_name']) ?> - <?= esc($student['nisn']) ?>
                                            <?php if ($student['class_name']): ?>
                                                (<?= esc($student['class_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pilih minimal 2 siswa untuk sesi kelompok</small>
                            </div>
                        </div>

                    </div>

                    <!-- Session Date & Time -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Sesi</label>
                            <input type="date" name="session_date" class="form-control" value="<?= old_value('session_date', date('Y-m-d')) ?>" required>
                            <small class="text-muted">Tanggal pelaksanaan sesi konseling</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Waktu Sesi</label>
                            <input type="time" name="session_time" class="form-control" step="0">
                            <small class="text-muted">Opsional - waktu pelaksanaan</small>
                        </div>
                    </div>

                    <!-- Topic & Location -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label required">Topik/Judul Sesi</label>
                            <input type="text" name="topic" class="form-control" value="<?= old_value('topic') ?>" placeholder="Contoh: Konseling Akademik, Masalah Pribadi, dll" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" class="form-control" value="<?= old_value('location', 'Ruang BK') ?>" placeholder="Contoh: Ruang BK, Kelas">
                        </div>
                    </div>

                    <!-- Problem Description -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi Masalah/Topik</label>
                            <textarea name="problem_description" class="form-control" rows="4" placeholder="Jelaskan singkat tentang masalah atau topik yang akan dibahas dalam sesi ini (opsional)"><?= old_value('problem_description') ?></textarea>
                            <small class="text-muted">Opsional - deskripsi awal masalah atau topik pembahasan</small>
                        </div>
                    </div>

                    <!-- Duration & Confidential -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Durasi (menit)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="<?= old_value('duration_minutes', '60') ?>" min="1" max="480" placeholder="60">
                            <small class="text-muted">Estimasi durasi sesi dalam menit</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Kerahasiaan</label>
                            <div class="form-check form-switch form-switch-lg mt-2">
                            <input type="checkbox" name="is_confidential" value="1"<?= old_value('is_confidential','0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isConfidential">
                                    <i class="mdi mdi-lock"></i> Sesi Rahasia (Confidential)
                                </label>
                            </div>
                            <small class="text-muted">Centang jika sesi ini bersifat rahasia</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="mdi mdi-content-save me-1"></i> Simpan Sesi
                            </button>
                            <a href="<?= base_url('counselor/sessions') ?>" class="btn btn-secondary btn-lg">
                                <i class="mdi mdi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card border-info">
            <div class="card-body">
                <h5 class="card-title text-info">
                    <i class="mdi mdi-information"></i> Informasi
                </h5>
                <ul class="mb-0">
                    <li><strong>Sesi Individu:</strong> Konseling satu-on-one dengan siswa tertentu</li>
                    <li><strong>Sesi Kelompok:</strong> Konseling dengan beberapa siswa (minimal 2 siswa)</li>
                    <li><strong>Sesi Klasikal:</strong> Konseling dengan satu kelas penuh (semua siswa di kelas dipilih)</li>
                    <li>Semua field bertanda <span class="text-danger">*</span> wajib diisi</li>
                    <li>Setelah sesi dibuat, Anda dapat menambahkan catatan progres di halaman detail</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<!-- Select2 for better dropdown -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .required:after {
        content: " *";
        color: red;
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#studentSelect').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Siswa --',
            allowClear: true,
            width: '100%'
        });

        $('#classSelect').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Kelas --',
            allowClear: true,
            width: '100%'
        });

        $('#participantsSelect').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih minimal 2 siswa',
            allowClear: true,
            width: '100%'
        });

        // Handle session type change
        $('input[name="session_type"]').on('change', function() {
            const selectedType = $(this).val();

            // Hide all dynamic fields first
            $('.field-individu, .field-kelompok, .field-klasikal').hide();

            // Clear all dynamic field values
            $('#studentSelect').val('').trigger('change');
            $('#classSelect').val('').trigger('change');
            $('#participantsSelect').val([]).trigger('change');

            // Show relevant fields based on selected type
            if (selectedType === 'Individu') {
                $('.field-individu').show();
                $('#studentSelect').prop('required', true);
                $('#classSelect').prop('required', false);
            } else if (selectedType === 'Kelompok') {
                $('.field-kelompok').show();
                $('#participantsSelect').prop('required', true);
                $('#studentSelect').prop('required', false);
                $('#classSelect').prop('required', false);
            } else if (selectedType === 'Klasikal') {
                $('.field-klasikal').show();
                $('#classSelect').prop('required', true);
                $('#studentSelect').prop('required', false);
            }
        });

        // Trigger change on page load to show correct fields
        const checkedType = $('input[name="session_type"]:checked');
        if (checkedType.length > 0) {
            checkedType.trigger('change');
        } else {
            // Default to Individu if none selected
            $('#typeIndividu').prop('checked', true).trigger('change');
        }

        // Form validation before submit
        $('#createSessionForm').on('submit', function(e) {
            const sessionType = $('input[name="session_type"]:checked').val();

            if (sessionType === 'Individu') {
                const studentId = $('#studentSelect').val();
                if (!studentId) {
                    e.preventDefault();
                    alert('Silakan pilih siswa untuk sesi individu!');
                    $('#studentSelect').focus();
                    return false;
                }
            } else if (sessionType === 'Kelompok') {
                const participants = $('#participantsSelect').val();
                if (!participants || participants.length < 2) {
                    e.preventDefault();
                    alert('Silakan pilih minimal 2 siswa untuk sesi kelompok!');
                    $('#participantsSelect').focus();
                    return false;
                }
            } else if (sessionType === 'Klasikal') {
                const classId = $('#classSelect').val();
                if (!classId) {
                    e.preventDefault();
                    alert('Silakan pilih kelas untuk sesi klasikal!');
                    $('#classSelect').focus();
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
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?php $this->endSection(); ?>