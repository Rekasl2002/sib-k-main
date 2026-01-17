<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/students/edit.php
 *
 * Edit Student Form View (Koordinator BK)
 * Form untuk mengedit data siswa sesuai kewenangan Koordinator.
 *
 * Catatan:
 * - Tidak ada aksi Hapus & Pindah Kelas pada view ini (dibatasi untuk Koordinator)
 * - Untuk edit akun user (username/email/), diarahkan ke Manajemen User Koordinator
 *
 * @package    SIB-K
 * @subpackage Views/Koordinator/Students
 * @category   Student Management
 * @author     Development Team
 * @created    2025-12-16
 */

// Ambil sekali agar tidak "habis" saat dipakai berulang
$errors = session()->getFlashdata('errors') ?? [];
if (!is_array($errors)) $errors = [];

// helper optional (aman kalau sudah autoload)
helper(['url', 'form']);
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Edit Siswa</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/students') ?>">Siswa</a></li>
                    <li class="breadcrumb-item active">Edit Siswa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                <?= session()->getFlashdata('success') ?>
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
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <strong>Terdapat kesalahan:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $k => $error): ?>
                        <li><?= esc(is_string($error) ? $error : (string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Student Info Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-circle me-2"></i>Info Siswa
                </h4>

                <div class="text-center">
                    <img src="<?= user_avatar($student['profile_photo'] ?? null) ?>"
                        alt="<?= esc($student['full_name'] ?? '-') ?>"
                        class="avatar-lg rounded-circle mb-3">

                    <h5 class="mb-1"><?= esc($student['full_name'] ?? '-') ?></h5>
                    <p class="text-muted mb-2">@<?= esc($student['username'] ?? '-') ?></p>

                    <?php if (!empty($student['class_name'])): ?>
                        <span class="badge bg-primary font-size-12">
                            <?= esc($student['grade_level'] ?? '-') ?> - <?= esc($student['class_name']) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary font-size-12">Belum Ada Kelas</span>
                    <?php endif; ?>

                    <span class="badge bg-<?= ($student['status'] ?? '') === 'Aktif' ? 'success' : 'secondary' ?> font-size-12 ms-1">
                        <?= esc($student['status'] ?? '-') ?>
                    </span>
                </div>

                <hr class="my-4">

                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Student ID:</td>
                                <td class="text-end"><strong>#<?= esc($student['id'] ?? '-') ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">NISN:</td>
                                <td class="text-end"><code><?= esc($student['nisn'] ?? '-') ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">NIS:</td>
                                <td class="text-end"><code><?= esc($student['nis'] ?? '-') ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Poin Pelanggaran:</td>
                                <td class="text-end">
                                    <span class="badge bg-<?= (int)($student['total_violation_points'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                        <?= (int)($student['total_violation_points'] ?? 0) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Terdaftar:</td>
                                <td class="text-end">
                                    <small>
                                        <?= !empty($student['created_at']) ? date('d/m/Y', strtotime($student['created_at'])) : '-' ?>
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Quick Actions -->
                <div class="d-grid gap-2">
                    <a href="<?= base_url('koordinator/students/profile/' . ($student['id'] ?? 0)) ?>" class="btn btn-info">
                        <i class="mdi mdi-eye me-1"></i> Lihat Profil Lengkap
                    </a>
                    <!-- Pindah Kelas & Hapus Siswa sengaja tidak ditampilkan untuk Koordinator -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-edit me-2"></i>Edit Data Siswa
                </h4>

                <form action="<?= base_url('koordinator/students/update/' . ($student['id'] ?? 0)) ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <!-- penting untuk placeholder {id} pada rules dinamis -->
                    <input type="hidden" name="id" value="<?= esc($student['id'] ?? '') ?>">

                    <!-- Nama Lengkap -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= old('full_name') ?? esc($student['full_name'] ?? '') ?>"
                                       minlength="3" maxlength="100"
                                       required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['full_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- NISN & NIS -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nisn" class="form-label">
                                    NISN <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control <?= isset($errors['nisn']) ? 'is-invalid' : '' ?>"
                                       id="nisn"
                                       name="nisn"
                                       value="<?= old('nisn') ?? esc($student['nisn'] ?? '') ?>"
                                       placeholder="10 digit"
                                       inputmode="numeric"
                                       pattern="\d{10}"
                                       maxlength="10"
                                       required>
                                <small class="text-muted">Nomor Induk Siswa Nasional (tepat 10 digit)</small>
                                <?php if (isset($errors['nisn'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['nisn']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nis" class="form-label">
                                    NIS <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control <?= isset($errors['nis']) ? 'is-invalid' : '' ?>"
                                       id="nis"
                                       name="nis"
                                       value="<?= old('nis') ?? esc($student['nis'] ?? '') ?>"
                                       placeholder="4–20 digit"
                                       inputmode="numeric"
                                       pattern="\d{4,20}"
                                       minlength="4" maxlength="20"
                                       required>
                                <small class="text-muted">Nomor Induk Siswa (4–20 digit angka)</small>
                                <?php if (isset($errors['nis'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['nis']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Gender, Tempat/Tanggal Lahir -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="gender" class="form-label">
                                    Jenis Kelamin <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>"
                                        id="gender" name="gender" required>
                                    <option value="">Pilih</option>
                                    <?php foreach (($gender_options ?? []) as $key => $value): ?>
                                        <option value="<?= esc($key) ?>" <?= (old('gender') ?? ($student['gender'] ?? '')) == $key ? 'selected' : '' ?>>
                                            <?= esc($value) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['gender'])): ?>
                                    <div class="invalid-feedback d-block"><?= esc($errors['gender']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="birth_place" class="form-label">Tempat Lahir</label>
                                <input type="text"
                                       class="form-control <?= isset($errors['birth_place']) ? 'is-invalid' : '' ?>"
                                       id="birth_place"
                                       name="birth_place"
                                       value="<?= old('birth_place') ?? esc($student['birth_place'] ?? '') ?>"
                                       maxlength="100">
                                <?php if (isset($errors['birth_place'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['birth_place']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Tanggal Lahir</label>
                                <input type="date"
                                       class="form-control <?= isset($errors['birth_date']) ? 'is-invalid' : '' ?>"
                                       id="birth_date"
                                       name="birth_date"
                                       value="<?= old('birth_date') ?? ($student['birth_date'] ?? '') ?>">
                                <?php if (isset($errors['birth_date'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['birth_date']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Agama, Kelas (wajib), Tanggal Masuk -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="religion" class="form-label">Agama</label>
                                <select class="form-select <?= isset($errors['religion']) ? 'is-invalid' : '' ?>" id="religion" name="religion">
                                    <option value="">Pilih</option>
                                    <?php foreach (($religion_options ?? []) as $religion): ?>
                                        <option value="<?= esc($religion) ?>" <?= (old('religion') ?? ($student['religion'] ?? '')) == $religion ? 'selected' : '' ?>>
                                            <?= esc($religion) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['religion'])): ?>
                                    <div class="invalid-feedback d-block"><?= esc($errors['religion']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="class_id" class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors['class_id']) ? 'is-invalid' : '' ?>"
                                        id="class_id" name="class_id" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php foreach (($classes ?? []) as $class): ?>
                                        <option value="<?= esc($class['id']) ?>" <?= (old('class_id') ?? ($student['class_id'] ?? '')) == $class['id'] ? 'selected' : '' ?>>
                                            <?= esc($class['grade_level'] ?? '-') ?> - <?= esc($class['class_name'] ?? '-') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['class_id'])): ?>
                                    <div class="invalid-feedback d-block"><?= esc($errors['class_id']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="admission_date" class="form-label">Tanggal Masuk</label>
                                <input type="date"
                                       class="form-control <?= isset($errors['admission_date']) ? 'is-invalid' : '' ?>"
                                       id="admission_date"
                                       name="admission_date"
                                       value="<?= old('admission_date') ?? ($student['admission_date'] ?? '') ?>">
                                <?php if (isset($errors['admission_date'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['admission_date']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Alamat, Orang Tua/Wali, Status -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="address" class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                                          id="address"
                                          name="address"
                                          rows="3"
                                          maxlength="255"><?= old('address') ?? esc($student['address'] ?? '') ?></textarea>
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['address']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="parent_id" class="form-label">Orang Tua/Wali</label>
                                <select class="form-select <?= isset($errors['parent_id']) ? 'is-invalid' : '' ?>"
                                        id="parent_id" name="parent_id">
                                    <option value="">Pilih Orang Tua</option>
                                    <?php foreach (($parents ?? []) as $parent): ?>
                                        <option value="<?= esc($parent['id']) ?>" <?= (old('parent_id') ?? ($student['parent_id'] ?? '')) == $parent['id'] ? 'selected' : '' ?>>
                                            <?= esc($parent['full_name'] ?? '-') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($student['parent_name'])): ?>
                                    <small class="text-muted">Saat ini: <?= esc($student['parent_name']) ?></small>
                                <?php endif; ?>
                                <?php if (isset($errors['parent_id'])): ?>
                                    <div class="invalid-feedback d-block"><?= esc($errors['parent_id']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" name="status">
                                    <?php foreach (($status_options ?? []) as $status): ?>
                                        <option value="<?= esc($status) ?>" <?= (old('status') ?? ($student['status'] ?? '')) == $status ? 'selected' : '' ?>>
                                            <?= esc($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback d-block"><?= esc($errors['status']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Telepon -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="text"
                                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                       id="phone"
                                       name="phone"
                                       value="<?= old('phone') ?? esc($student['phone'] ?? '') ?>"
                                       maxlength="30"
                                       inputmode="tel"
                                       placeholder="08xxxxxxxxxx">
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['phone']) ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Opsional, maksimal 30 karakter</small>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('koordinator/students') ?>" class="btn btn-secondary">
                                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-light me-2">
                                        <i class="mdi mdi-refresh me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="mdi mdi-content-save me-1"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function() {
        'use strict';

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(a){ a.classList.remove('show'); a.classList.add('fade'); });
        }, 5000);

        // Bootstrap client-side validation
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                // Hard validation untuk pattern NISN & NIS
                var nisn = form.querySelector('#nisn');
                var nis  = form.querySelector('#nis');

                if (nisn && !/^\d{10}$/.test(nisn.value)) {
                    nisn.setCustomValidity('NISN harus tepat 10 digit angka');
                } else if (nisn) {
                    nisn.setCustomValidity('');
                }

                if (nis && !/^\d{4,20}$/.test(nis.value)) {
                    nis.setCustomValidity('NIS harus 4–20 digit angka');
                } else if (nis) {
                    nis.setCustomValidity('');
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');

                // cegah double submit (opsional)
                var btn = document.getElementById('submitBtn');
                if (btn && form.checkValidity()) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Menyimpan...';
                }
            }, false);
        });

        // Bantuan input numerik saat mengetik (opsional)
        var onlyDigits = function(el) {
            el.addEventListener('input', function() {
                this.value = this.value.replace(/[^\d]/g, '');
            });
        };
        var nisnEl = document.getElementById('nisn');
        var nisEl  = document.getElementById('nis');
        if (nisnEl) onlyDigits(nisnEl);
        if (nisEl)  onlyDigits(nisEl);
    })();
</script>
<?= $this->endSection() ?>
