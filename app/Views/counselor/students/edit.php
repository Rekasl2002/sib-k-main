<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/students/edit.php
 *
 * Edit Data Siswa (Counselor / Guru BK)
 * - Counselor hanya boleh U (update) kolom tertentu: address, phone, status, notes_bk
 * - Field lain ditampilkan read-only
 */

$errors = session()->getFlashdata('errors') ?? [];

// Normalisasi $student jadi array agar aman diakses dengan ['key']
if (isset($student) && is_object($student)) {
    $student = (array) $student;
}

// Helper aman
$s = static fn($k, $d='-') => esc($student[$k] ?? $d);

// Opsi status lokal (hindari dependency variabel lain)
$statusOptions = ['Aktif','Alumni','Pindah','Keluar'];
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Edit Siswa</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/students') ?>">Siswa</a></li>
                    <li class="breadcrumb-item active">Edit Siswa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
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
                <?php foreach ($errors as $errKey => $errVal): ?>
                    <li><?= esc(is_string($errVal) ? $errVal : json_encode($errVal)) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Student Info (read-only) -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-circle me-2"></i>Info Siswa
                </h4>

                <div class="text-center">
                    <img src="<?= user_avatar($student['profile_photo'] ?? null) ?>"
                         alt="<?= $s('full_name') ?>"
                         class="avatar-lg rounded-circle mb-3">

                    <h5 class="mb-1"><?= $s('full_name') ?></h5>
                    <?php if (!empty($student['username'])): ?>
                        <p class="text-muted mb-2">@<?= esc($student['username']) ?></p>
                    <?php elseif (!empty($student['email'])): ?>
                        <p class="text-muted mb-2"><?= esc($student['email']) ?></p>
                    <?php endif; ?>

                    <div class="mb-2">
                        <?php if (!empty($student['class_name'])): ?>
                            <span class="badge bg-primary font-size-12">
                                <?= esc($student['grade_level'] ?? '-') ?> - <?= esc($student['class_name']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary font-size-12">Belum Ada Kelas</span>
                        <?php endif; ?>

                        <?php
                            $st = $student['status'] ?? '-';
                            $colors = ['Aktif'=>'success','Alumni'=>'info','Pindah'=>'warning','Keluar'=>'danger'];
                            $c = $colors[$st] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $c ?> font-size-12 ms-1"><?= esc($st) ?></span>
                    </div>
                </div>

                <hr class="my-4">

                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Student ID</td>
                                <td class="text-end"><strong>#<?= esc($student['id'] ?? '-') ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">NISN</td>
                                <td class="text-end"><code><?= $s('nisn') ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">NIS</td>
                                <td class="text-end"><code><?= $s('nis') ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Poin Pelanggaran</td>
                                <td class="text-end">
                                    <?php $p = (int) ($student['total_violation_points'] ?? 0); $pc = $p>0?'danger':'success'; ?>
                                    <span class="badge bg-<?= $pc ?>"><?= $p ?></span>
                                </td>
                            </tr>
                            <?php if (!empty($student['created_at'])): ?>
                            <tr>
                                <td class="text-muted">Terdaftar</td>
                                <td class="text-end"><small><?= date('d/m/Y', strtotime($student['created_at'])) ?></small></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <div class="d-grid gap-2">
                    <?php if (!empty($student['id'])): ?>
                    <a href="<?= base_url('counselor/students/' . (int) $student['id']) ?>" class="btn btn-info">
                        <i class="mdi mdi-eye me-1"></i> Lihat Profil
                    </a>
                    <?php endif; ?>
                    <a href="<?= base_url('counselor/students') ?>" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form (allowed fields only) -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-edit me-2"></i>Edit Data yang Diizinkan
                </h4>

                <form action="<?= base_url('counselor/students/' . (int) ($student['id'] ?? 0)) ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= esc($student['id'] ?? '') ?>">

                    <!-- Alamat -->
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                                  id="address"
                                  name="address"
                                  rows="3"
                                  maxlength="255"
                                  placeholder="Alamat domisili siswa..."><?= old('address') ?? esc($student['address'] ?? '') ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['address']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Telepon -->
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
                        <small class="text-muted">Opsional, maksimal 30 karakter.</small>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" <?= isset($errors['status']) ? 'is-invalid' : '' ?>name="status">
                            <?php $curStatus = old('status') ?? ($student['status'] ?? 'Aktif'); ?>
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($curStatus === $opt) ? 'selected' : '' ?>>
                                    <?= esc($opt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="invalid-feedback d-block"><?= esc($errors['status']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Catatan BK 
                    <div class="mb-3">
                        <label for="notes_bk" class="form-label">Catatan BK</label>
                        <textarea class="form-control <?= isset($errors['notes_bk']) ? 'is-invalid' : '' ?>"
                                  id="notes_bk"
                                  name="notes_bk"
                                  rows="4"
                                  maxlength="1000"
                                  placeholder="Catatan khusus Guru BK untuk siswa ini..."><?= old('notes_bk') ?? esc($student['notes_bk'] ?? '') ?></textarea>
                        <?php if (isset($errors['notes_bk'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['notes_bk']) ?></div>
                        <?php endif; ?>
                    </div>-->

                    <!-- Info: read-only fields -->
                    <div class="alert alert-warning" role="alert">
                        <i class="mdi mdi-alert-outline me-2"></i>
                        <strong>Perhatian:</strong> Data lain seperti NIS/NISN, kelas, akun pengguna (username/email), dan data pribadi lain
                        bersifat <strong>read-only</strong> untuk Guru BK. Hubungi Admin bila perlu perubahan.
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('counselor/students') ?>" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali
                        </a>
                        <div>
                            <button type="reset" class="btn btn-light me-2">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i> Simpan Perubahan
                            </button>
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

    // Auto-hide alerts (5s)
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(a){
            a.classList.remove('show'); a.classList.add('fade');
        });
    }, 5000);

    // Bootstrap client-side validation
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            // Sanitasi sederhana nomor telepon (angka saja)
            var phone = form.querySelector('#phone');
            if (phone) phone.value = phone.value.replace(/[^\d+]/g, '');

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
<?= $this->endSection() ?>
