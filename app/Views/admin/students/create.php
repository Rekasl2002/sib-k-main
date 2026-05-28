<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/students/create.php
 * 
 * Create Student Form View
 * Form untuk menambah siswa baru
 * 
 * @package    SIB-K
 * @subpackage Views/Admin/Students
 * @category   Student Management
 * @author     Development Team
 * @created    2025-01-05
 */
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Tambah Siswa Baru</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/students') ?>">Siswa</a></li>
                    <li class="breadcrumb-item active">Tambah Siswa</li>
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
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <strong>Terdapat kesalahan:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Create Student Form -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-plus me-2"></i>Pilih Metode Pendaftaran
                </h4>

                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#newUser" role="tab">
                            <span class="d-block d-sm-none"><i class="fas fa-user-plus"></i></span>
                            <span class="d-none d-sm-block">
                                <i class="mdi mdi-account-plus me-2"></i>Buat dengan User Baru
                            </span>
                        </a>
                    </li>
                    <!--<li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#existingUser" role="tab">
                            <span class="d-block d-sm-none"><i class="fas fa-user"></i></span>
                            <span class="d-none d-sm-block">
                                <i class="mdi mdi-account-search me-2"></i>Pilih User Existing
                            </span>
                        </a>
                    </li> -->
                </ul>

                <!-- Tab panes -->
                <div class="tab-content p-3 text-muted">
                    <!-- New User Tab -->
                    <div class="tab-pane active" id="newUser" role="tabpanel">
                        <form action="<?= base_url('admin/students/store') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="create_with_user" value="1">

                            <h5 class="mb-3"><i class="mdi mdi-account-key me-2"></i>Informasi Akun User</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">
                                            Nama Lengkap <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="full_name" name="full_name"
                                            value="<?= old('full_name') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            Username <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="username" name="username"
                                            value="<?= old('username') ?>" required>
                                        <small class="text-muted">Username hanya boleh berisi huruf, angka, garis bawah (_) atau tanda minus (-), minimal 3 karakter.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            Email
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?= old('email') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            Password <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="mdi mdi-eye-outline" id="eyeIcon"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Minimal 8 karakter.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Nomor Telepon</label>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            value="<?= old('phone') ?>" placeholder="08xxxxxxxxxx">
                                            <small class="text-muted">Opsional. Jika diisi, harus diawali 08 dan terdiri dari 10–15 digit angka. Contoh: 081234567890.</small>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3"><i class="mdi mdi-school me-2"></i>Data Siswa</h5>

                            <?= $this->include('admin/students/_form_fields') ?>

                            <!-- Form Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary">
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
                    </div>

                    <!-- Existing User Tab -->
                    <div class="tab-pane" id="existingUser" role="tabpanel">
                        <form action="<?= base_url('admin/students/store') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="create_with_user" value="0">

                            <div class="alert alert-info" role="alert">
                                <i class="mdi mdi-information me-2"></i>
                                Pilih user yang sudah terdaftar dalam sistem untuk dijadikan siswa.
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="existing_user_id" class="form-label">Pilih User</label>
                                        <select name="user_id" id="existing_user_id" class="form-select" required>
                                            <option value="">— Pilih User —</option>
                                            <?php if (!empty($availableUsers)): ?>
                                            <?php foreach ($availableUsers as $u): ?>
                                                <option value="<?= (int)$u['id'] ?>" <?= set_select('user_id', (string)$u['id']) ?>>
                                                <?= esc($u['full_name'] ?: ($u['email'] ?: $u['username'])) ?>
                                                <?php if (!empty($u['email'])): ?> — <?= esc($u['email']) ?><?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <!-- Jika kosong, beri info -->
                                            <option value="" disabled>Tidak ada user ber-role Siswa yang tersedia</option>
                                            <?php endif; ?>
                                        </select>
                                        <small class="text-muted">Daftar otomatis berisi semua user ber-role <b>Siswa</b> yang belum punya data di tabel <code>students</code>.</small>
                                        </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3"><i class="mdi mdi-school me-2"></i>Data Siswa</h5>

                            <?= $this->include('admin/students/_form_fields') ?>

                            <!-- Form Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        // Toggle Password Visibility
        $('#togglePassword').on('click', function() {
            const passwordField = $('#password');
            const eyeIcon = $('#eyeIcon');

            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                eyeIcon.removeClass('mdi-eye-outline').addClass('mdi-eye-off-outline');
            } else {
                passwordField.attr('type', 'password');
                eyeIcon.removeClass('mdi-eye-off-outline').addClass('mdi-eye-outline');
            }
        });

        // NISN Validation (Numbers only)
        $('input[name="nisn"]').on('keyup', function() {
            const value = $(this).val();
            $(this).val(value.replace(/[^0-9]/g, ''));
        });

        // Phone Validation (Numbers only)
        $('input[name="phone"]').on('keyup', function() {
            const value = $(this).val();
            $(this).val(value.replace(/[^0-9]/g, ''));
        });

        // Username Validation (Alphanumeric only)
        $('#username').on('keyup', function() {
            const value = $(this).val();
            $(this).val(value.replace(/[^a-zA-Z0-9]/g, ''));
        });

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Form Validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');

            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    });
</script>
<?= $this->endSection() ?>
