<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/users/edit.php
 *
 * Edit User Form View
 * Form untuk mengedit data pengguna
 *
 * @package    SIB-K
 * @subpackage Views/Admin/Users
 * @category   User Management
 * @author     Development Team
 * @created    2025-01-05
 */
?>

<?php
// Ambil sekali saja supaya tidak habis saat dipakai berkali-kali di view
$errors = session()->getFlashdata('errors') ?? [];
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Edit Pengguna</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/users') ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active">Edit Pengguna</li>
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
                    <?php foreach ($errors as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <!-- User Info Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-circle me-2"></i>Profil Pengguna
                </h4>

                <div class="text-center">
                    <img src="<?= user_avatar($user['profile_photo']) ?>"
                         alt="<?= esc($user['full_name']) ?>"
                         class="avatar-lg rounded-circle mb-3">

                    <h5 class="mb-1"><?= esc($user['full_name']) ?></h5>
                    <p class="text-muted mb-2">@<?= esc($user['username']) ?></p>
                    <span class="badge bg-info font-size-12"><?= esc($user['role_name']) ?></span>
                </div>

                <hr class="my-4">

                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">ID Peran:</td>
                                <td class="text-end"><strong>#<?= (int) $user['id'] ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status:</td>
                                <td class="text-end">
                                    <?php if ((int)$user['is_active'] === 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Terakhir Login:</td>
                                <td class="text-end">
                                    <?php if (!empty($user['last_login'])): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($user['last_login'])) ?></small>
                                    <?php else: ?>
                                        <small>-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Terdaftar:</td>
                                <td class="text-end">
                                    <small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/users/show/' . (int)$user['id']) ?>" class="btn btn-info">
                        <i class="mdi mdi-eye me-1"></i> Lihat Detail
                    </a>

                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                        <i class="mdi mdi-key-variant me-1"></i> Reset Password
                    </button>

                    <?php if ((int)$user['id'] !== (int)session()->get('user_id')): ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="mdi mdi-delete me-1"></i> Hapus Pengguna
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-edit me-2"></i>Edit Informasi Pengguna
                </h4>

                <form action="<?= base_url('admin/users/update/' . (int)$user['id']) ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <!-- penting untuk placeholder {id} di validator -->
                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">

                    <!-- fallback untuk checkbox status -->
                    <input type="hidden" name="is_active" value="0">

                    <div class="row">
                        <!-- Role Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role_id" class="form-label">
                                    Peran <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>"
                                        id="role_id"
                                        name="role_id"
                                        required>
                                    <option value="">Pilih Peran</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= (int)$role['id'] ?>"
                                            <?= (old('role_id', $user['role_id']) == $role['id']) ? 'selected' : '' ?>>
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
                                       class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= esc(old('full_name', $user['full_name'])) ?>"
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
                                       class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                       id="username"
                                       name="username"
                                       value="<?= esc(old('username', $user['username'])) ?>"
                                       placeholder="Masukkan username (alfanumerik)"
                                       required>
                                <div class="form-text">
                                    Username hanya boleh berisi huruf, angka, garis bawah (_) atau tanda minus (-), minimal 3 karakter.
                                </div>
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
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       id="email"
                                       name="email"
                                       value="<?= esc(old('email', $user['email'])) ?>"
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
                        <!-- Phone -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="text"
                                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                       id="phone"
                                       name="phone"
                                       value="<?= esc(old('phone', (string)($user['phone'] ?? ''))) ?>"
                                       placeholder="08xxxxxxxxxx">
                                <div class="form-text">
                                    Opsional. Jika diisi, harus diawali 08 dan terdiri dari 10–15 digit angka. Contoh: 081234567890.
                                </div>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Active (satu-satunya checkbox status) -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch form-switch-lg mt-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           <?= (old('is_active', (int)$user['is_active']) == 1) ? 'checked' : '' ?>
                                           <?= ((int)$user['id'] === (int)session()->get('user_id')) ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        <?php if ((int)$user['id'] === (int)session()->get('user_id')): ?>
                                            Anda tidak dapat menonaktifkan akun sendiri
                                        <?php else: ?>
                                            Pengguna aktif
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">
                    <i class="mdi mdi-key-variant text-warning me-2"></i>Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/reset-password/' . (int)$user['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin mereset password untuk pengguna <strong><?= esc($user['full_name']) ?></strong>?</p>
                    <p class="text-warning mb-0">
                        <i class="mdi mdi-information me-1"></i>
                        Password baru akan dibuat secara otomatis. Pastikan untuk mencatat dan menyampaikannya kepada user.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="mdi mdi-key-variant me-1"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if ((int)$user['id'] !== (int)session()->get('user_id')): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="mdi mdi-alert-circle text-danger me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/delete/' . (int)$user['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pengguna <strong><?= esc($user['full_name']) ?></strong>?</p>
                    <p class="text-danger mb-0">
                        <i class="mdi mdi-information me-1"></i>
                        Data yang sudah dihapus tidak dapat dikembalikan!
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete me-1"></i>Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function() {
        'use strict';

        // Username Validation (Alphanumeric only)
        const username = document.getElementById('username');
        if (username) {
            username.addEventListener('input', function () {
                this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
            });
        }

        // Phone Number Validation (Numbers only)
        const phone = document.getElementById('phone');
        if (phone) {
            phone.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(el) {
                el.classList.remove('show');
            });
        }, 5000);

        // Browser-side constraint validation
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
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
