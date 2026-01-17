<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/roles/form.php
 *
 * Role Form View
 * Placeholder form untuk create/edit role
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
                <h4 class="card-title mb-4">
                    <?= $mode === 'create' ? 'Tambah Role Baru' : 'Edit Role' ?>
                </h4>

                <div class="alert alert-warning" role="alert">
                    <i class="mdi mdi-alert-circle-outline me-2"></i>
                    Form ini bersifat demonstrasi dan belum terhubung dengan proses penyimpanan data.
                </div>

                <form>
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Nama Role</label>
                        <input type="text" id="role_name" class="form-control"
                            value="<?= esc($role['name'] ?? '') ?>"
                            placeholder="Contoh: Administrator" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="role_description" class="form-label">Deskripsi</label>
                        <textarea id="role_description" rows="4" class="form-control" disabled><?= esc($role['description'] ?? '') ?></textarea>
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