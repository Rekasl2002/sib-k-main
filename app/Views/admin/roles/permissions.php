<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/roles/permissions.php
 *
 * Role Permissions View
 * Placeholder untuk pengaturan hak akses role
 *
 * @package    SIB-K
 * @subpackage Views/Admin/Roles
 * @category   Role Management
 */
?>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Pengaturan Hak Akses - <?= esc($role['name']) ?></h4>

                <p class="text-muted">
                    Daftar hak akses di bawah ini merupakan contoh data statis. Implementasikan logika
                    penyimpanan sesuai kebutuhan aplikasi Anda.
                </p>

                <form>
                    <div class="mb-3">
                        <label class="form-label">Daftar Hak Akses</label>
                        <?php foreach ($available_permissions as $permission): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="perm-<?= md5($permission) ?>" disabled checked>
                                <label class="form-check-label" for="perm-<?= md5($permission) ?>">
                                    <?= esc($permission) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-end">
                        <a href="<?= base_url('admin/roles') ?>" class="btn btn-secondary">Kembali</a>
                        <button type="button" class="btn btn-primary" disabled>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>