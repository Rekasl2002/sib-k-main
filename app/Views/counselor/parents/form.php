<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/parents/form.php
 * Form Tambah/Edit akun orang tua — Guru BK.
 */

$mode   = $mode ?? 'create';
$parent = is_array($parent ?? null) ? $parent : [];
$action = $action ?? base_url('counselor/parents/store');
$isEdit = $mode === 'edit';
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <i class="mdi mdi-account-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
                <?= esc($pageTitle ?? ($isEdit ? 'Edit Akun Orang Tua' : 'Tambah Akun Orang Tua')) ?>
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Guru BK</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/parents') ?>">Akun Orang Tua</a></li>
                    <li class="breadcrumb-item active"><?= $isEdit ? 'Edit' : 'Tambah' ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Flash / Validation Errors -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty(session()->getFlashdata('errors'))): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ((array)session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-3">
                <h5 class="card-title mb-0">
                    <?= $isEdit ? 'Perbarui data akun orang tua' : 'Isi data akun orang tua baru' ?>
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= esc($action, 'attr') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= esc(old('full_name', $parent['full_name'] ?? '')) ?>"
                               placeholder="Nama lengkap orang tua" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control"
                               value="<?= esc(old('username', $parent['username'] ?? '')) ?>"
                               placeholder="Username unik" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email', $parent['email'] ?? '')) ?>"
                               placeholder="Email (opsional)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= esc(old('phone', $parent['phone'] ?? '')) ?>"
                               placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <?= $isEdit ? 'Password Baru' : 'Password' ?>
                            <?php if (!$isEdit): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="password" name="password" class="form-control"
                               placeholder="<?= $isEdit ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 6 karakter (default: orangtua123)' ?>"
                               <?= !$isEdit ? '' : '' ?>>
                        <?php if ($isEdit): ?>
                            <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                        <?php else: ?>
                            <div class="form-text">Kosongkan untuk menggunakan password default: <code>orangtua123</code>.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isEdit): ?>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                       value="1" <?= (int)($parent['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">Akun Aktif</label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Buat Akun' ?>
                        </button>
                        <a href="<?= base_url('counselor/parents' . ($isEdit ? '/' . (int)($parent['id'] ?? 0) : '')) ?>"
                           class="btn btn-outline-secondary">
                            <i class="mdi mdi-close me-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
