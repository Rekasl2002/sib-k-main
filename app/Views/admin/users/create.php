<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/users/create.php
 *
 * Create User Form View
 * Form untuk menambah pengguna baru + (opsional) data siswa jika Role = Siswa
 *
 * @package    SIB-K
 * @subpackage Views/Admin/Users
 * @category   User Management
 * @author     Development Team
 * @created    2025-01-05
 * @updated    2025-11-13 - Samakan aturan phone & data siswa (NISN/NIS) dengan StudentValidation
 * @updated    2025-11-13 - Data Siswa diselaraskan dengan form admin/students/create
 */

// Ambil flash errors secara aman agar tidak notice saat kosong
$errors = session()->getFlashdata('errors') ?? [];

// Tentukan role id untuk "Siswa" (default 5, tetapi bila ada di $roles akan dipakai yang sebenarnya)
$studentRoleId = 5;
if (!empty($roles) && is_array($roles)) {
    foreach ($roles as $r) {
        if (isset($r['role_name']) && strcasecmp($r['role_name'], 'Siswa') === 0) {
            $studentRoleId = (int) ($r['id'] ?? 5);
            break;
        }
    }
}

// Pastikan variabel kelas ada agar dropdown tidak error
$classes = $classes ?? [];
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Tambah Pengguna Baru</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/users') ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active">Tambah Pengguna</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Error Messages -->
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
                    <?php foreach ($errors as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Create User Form -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-plus me-2"></i>Informasi Pengguna
                </h4>

                <form action="<?= base_url('admin/users/store') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="row">
                        <!-- Role Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role_id" class="form-label">
                                    Peran <span class="text-danger">*</span>
                                </label>
                                <select
                                    class="form-select<?= isset($errors['role_id']) ? ' is-invalid' : '' ?>"
                                    id="role_id"
                                    name="role_id"
                                    required>
                                    <option value="">Pilih Peran</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= (int)$role['id'] ?>" <?= old('role_id') == $role['id'] ? 'selected' : '' ?>>
                                            <?= esc($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['role_id'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?= esc($errors['role_id']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control<?= isset($errors['full_name']) ? ' is-invalid' : '' ?>"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= old('full_name') ?>"
                                       placeholder="Masukkan nama lengkap"
                                       required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['full_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Username -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    Username <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control<?= isset($errors['username']) ? ' is-invalid' : '' ?>"
                                       id="username"
                                       name="username"
                                       value="<?= old('username') ?>"
                                       placeholder="Masukkan username"
                                       required>
                                 <div class="form-text">Username hanya boleh berisi huruf, angka, garis bawah (_) atau tanda minus (-), minimal 3 karakter.</div>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['username']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                       class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                       id="email"
                                       name="email"
                                       value="<?= old('email') ?>"
                                       placeholder="contoh@email.com"
                                       required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Password -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                                           id="password"
                                           name="password"
                                           placeholder="Minimal 8 karakter"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Toggle password visibility">
                                        <i class="mdi mdi-eye-outline" id="eyeIcon"></i>
                                    </button>
                                </div>
                                 <div class="form-text">Minimal 8 karakter.</div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?= esc($errors['password']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Password Confirmation -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">
                                    Konfirmasi Password <span class="text-danger">*</span>
                                </label>
                                <input type="password"
                                       class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
                                       id="password_confirm"
                                       name="password_confirm"
                                       placeholder="Ulangi password"
                                       required>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['password_confirm']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Phone -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="text"
                                       class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                       id="phone"
                                       name="phone"
                                       value="<?= old('phone') ?>"
                                       placeholder="08xxxxxxxxxx"
                                       minlength="10"
                                       maxlength="15"
                                       pattern="08[0-9]{8,13}"
                                       inputmode="numeric">
                                <div class="form-text">
                                    Opsional. Jika diisi, harus diawali <code>08</code> dan terdiri dari 10–15 digit angka.
                                    Contoh: <code>081234567890</code>.
                                </div>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Active -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch form-switch-lg mt-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           <?= old('is_active', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Aktifkan pengguna setelah dibuat
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================== Data Siswa (Role = Siswa) ===================== -->
                    <div id="student-section" class="border rounded p-3 mt-3" style="display:none">
                        <h6 class="mb-3"><i class="mdi mdi-school me-1"></i> Data Siswa</h6>

                        <!-- Baris 1: NISN & NIS -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="student_nisn" class="form-label">
                                        NISN <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="student_nisn"
                                        name="student[nisn]"
                                        value="<?= old('student.nisn') ?>"
                                        placeholder="10 digit angka"
                                        maxlength="10"
                                        pattern="[0-9]{10}"
                                        inputmode="numeric"
                                    >
                                    <small class="text-muted">
                                        Nomor Induk Siswa Nasional (harus tepat 10 digit angka).
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="student_nis" class="form-label">
                                        NIS <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="student_nis"
                                        name="student[nis]"
                                        value="<?= old('student.nis') ?>"
                                        placeholder="4–20 digit angka"
                                        minlength="4"
                                        maxlength="20"
                                        pattern="[0-9]{4,20}"
                                        inputmode="numeric"
                                    >
                                    <small class="text-muted">
                                        Nomor Induk Siswa (4–20 digit angka).
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Baris 2: Gender, Tempat & Tanggal Lahir -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="student_gender" class="form-label">
                                        Jenis Kelamin <span class="text-danger">*</span>
                                    </label>
                                    <?php $oldStudentGender = old('student.gender'); ?>
                                    <select class="form-select" id="student_gender" name="student[gender]">
                                        <option value="">Pilih</option>
                                        <option value="L" <?= $oldStudentGender === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= $oldStudentGender === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="student_birth_place" class="form-label">Tempat Lahir</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="student_birth_place"
                                        name="student[birth_place]"
                                        value="<?= old('student.birth_place') ?>"
                                    >
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="student_birth_date" class="form-label">Tanggal Lahir</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="student_birth_date"
                                        name="student[birth_date]"
                                        value="<?= old('student.birth_date') ?>"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Baris 3: Agama, Kelas, Tanggal Masuk -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="student_religion" class="form-label">Agama</label>
                                    <?php $oldStudentReligion = old('student.religion'); ?>
                                    <select class="form-select" id="student_religion" name="student[religion]">
                                        <option value="">Pilih</option>
                                        <option value="Islam"    <?= $oldStudentReligion === 'Islam' ? 'selected' : '' ?>>Islam</option>
                                        <option value="Kristen"  <?= $oldStudentReligion === 'Kristen' ? 'selected' : '' ?>>Kristen</option>
                                        <option value="Katolik"  <?= $oldStudentReligion === 'Katolik' ? 'selected' : '' ?>>Katolik</option>
                                        <option value="Hindu"    <?= $oldStudentReligion === 'Hindu' ? 'selected' : '' ?>>Hindu</option>
                                        <option value="Buddha"   <?= $oldStudentReligion === 'Buddha' ? 'selected' : '' ?>>Buddha</option>
                                        <option value="Konghucu" <?= $oldStudentReligion === 'Konghucu' ? 'selected' : '' ?>>Konghucu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="student_class_id" class="form-label">Kelas</label>
                                    <?php $oldStudentClass = old('student.class_id'); ?>
                                    <select name="student[class_id]" id="student_class_id" class="form-select">
                                        <option value="">Pilih Kelas</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= (int)$c['id'] ?>" <?= (string)$oldStudentClass === (string)$c['id'] ? 'selected' : '' ?>>
                                                <?= esc($c['class_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="student_admission_date" class="form-label">Tanggal Masuk</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="student_admission_date"
                                        name="student[admission_date]"
                                        value="<?= old('student.admission_date') ?: date('Y-m-d') ?>"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Baris 4: Alamat, Orang Tua/Wali, Status -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="student_address" class="form-label">Alamat Lengkap</label>
                                    <textarea
                                        class="form-control"
                                        id="student_address"
                                        name="student[address]"
                                        rows="3"
                                    ><?= old('student.address') ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="student_parent_id" class="form-label">Orang Tua/Wali</label>
                                    <?php $oldStudentParent = old('student.parent_id'); ?>
                                    <select class="form-select" id="student_parent_id" name="student[parent_id]">
                                        <option value="">Pilih Orang Tua</option>
                                        <!-- Sesuaikan dengan data orang tua yang tersedia di sistem Anda -->
                                        <option value="10" <?= $oldStudentParent === '10' ? 'selected' : '' ?>>Dewi Lestari</option>
                                        <option value="9"  <?= $oldStudentParent === '9'  ? 'selected' : '' ?>>Suryanto</option>
                                        <option value="26" <?= $oldStudentParent === '26' ? 'selected' : '' ?>>test</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="student_status" class="form-label">Status</label>
                                    <?php $oldStudentStatus = old('student.status', 'Aktif'); ?>
                                    <select class="form-select" id="student_status" name="student[status]">
                                        <option value="Aktif"  <?= $oldStudentStatus === 'Aktif'  ? 'selected' : '' ?>>Aktif</option>
                                        <option value="Alumni" <?= $oldStudentStatus === 'Alumni' ? 'selected' : '' ?>>Alumni</option>
                                        <option value="Pindah" <?= $oldStudentStatus === 'Pindah' ? 'selected' : '' ?>>Pindah</option>
                                        <option value="Keluar" <?= $oldStudentStatus === 'Keluar' ? 'selected' : '' ?>>Keluar</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-2 mb-0">
                            <i class="mdi mdi-alert-outline me-2"></i>
                            Jika Role = <strong>Siswa</strong>, <strong>NISN</strong>, <strong>NIS</strong>, dan
                            <strong>Jenis Kelamin</strong> wajib diisi dan harus sesuai aturan
                            (NISN 10 digit angka, NIS 4–20 digit angka).
                        </div>
                    </div>
                    <!-- =================== /Data Siswa (Role = Siswa) ===================== -->

                    <!-- Info Box -->
                    <div class="alert alert-info mt-3" role="alert">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Informasi:</strong> Password akan di-enkripsi secara otomatis.
                        Pastikan untuk mencatat password dan menyampaikannya kepada pengguna.
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">
                                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-light me-2">
                                        <i class="mdi mdi-refresh me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-content-save me-1"></i> Simpan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div><!-- card-body -->
        </div><!-- card -->
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle Password Visibility
    const toggleBtn = document.getElementById('togglePassword');
    const password  = document.getElementById('password');
    const eyeIcon   = document.getElementById('eyeIcon');
    if (toggleBtn && password && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            eyeIcon.classList.toggle('mdi-eye-outline');
            eyeIcon.classList.toggle('mdi-eye-off-outline');
        });
    }

    // Real-time Password Match Validation
    const passConfirm = document.getElementById('password_confirm');
    if (passConfirm && password) {
        passConfirm.addEventListener('keyup', function () {
            if (!password.value || !passConfirm.value) {
                passConfirm.classList.remove('is-valid','is-invalid');
                return;
            }
            const same = password.value === passConfirm.value;
            passConfirm.classList.toggle('is-valid', same);
            passConfirm.classList.toggle('is-invalid', !same);
        });
    }

    // Username: alfanumerik + underscore + dash
    const username = document.getElementById('username');
    if (username) {
        username.addEventListener('keyup', function () {
            // huruf, angka, underscore, dash
            this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '');
        });
    }

    // Phone: angka saja
    const phone = document.getElementById('phone');
    if (phone) {
        phone.addEventListener('keyup', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // NISN & NIS siswa: angka saja
    const nisnInput    = document.querySelector('input[name="student[nisn]"]');
    const nisInput     = document.querySelector('input[name="student[nis]"]');
    if (nisnInput) {
        nisnInput.addEventListener('keyup', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    if (nisInput) {
        nisInput.addEventListener('keyup', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Gender siswa
    const studentGender = document.getElementById('student_gender');

    // Toggle section siswa + set required untuk NISN/NIS/Gender jika role = Siswa
    const roleSelect   = document.querySelector('select[name="role_id"]');
    const studentBox   = document.getElementById('student-section');
    const STUDENT_ROLE = <?= (int)$studentRoleId ?>;

    function toggleStudentBox() {
        const isStudent = roleSelect && parseInt(roleSelect.value, 10) === STUDENT_ROLE;

        if (studentBox) {
            studentBox.style.display = isStudent ? '' : 'none';
        }

        if (nisnInput)     nisnInput.required     = isStudent;
        if (nisInput)      nisInput.required      = isStudent;
        if (studentGender) studentGender.required = isStudent;
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleStudentBox);
        toggleStudentBox(); // initial
    }

    // Auto-hide alerts after 5s
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(a){
            a.classList.add('fade');
        });
    }, 5000);

    // Bootstrap-like client validation
    Array.prototype.slice.call(document.querySelectorAll('.needs-validation')).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>
<?= $this->endSection() ?>
