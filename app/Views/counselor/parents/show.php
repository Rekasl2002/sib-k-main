<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/parents/show.php
 * Detail akun orang tua — Guru BK.
 */

helper('permission');

$parent   = is_array($parent ?? null) ? $parent : [];
$children = is_array($children ?? null) ? $children : [];
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="mdi mdi-account-supervisor me-2"></i><?= esc($pageTitle ?? 'Detail Orang Tua') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Guru BK</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/students') ?>">Siswa Binaan</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/parents') ?>">Akun Orang Tua</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $fk => $fc): ?>
    <?php if (session()->getFlashdata($fk)): ?>
        <div class="alert alert-<?= $fc ?> alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata($fk)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="row">
    <!-- Info Akun -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="mdi mdi-account me-2"></i>Informasi Akun</h5>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width:40%;">Nama Lengkap</td>
                                <td class="fw-semibold"><?= esc($parent['full_name'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Username</td>
                                <td><code><?= esc($parent['username'] ?? '-') ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email</td>
                                <td><?= !empty($parent['email']) ? esc($parent['email']) : '<span class="text-muted">-</span>' ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Telepon</td>
                                <td>
                                    <?php if (!empty($parent['phone'])): ?>
                                        <?= esc($parent['phone']) ?>
                                        <?= view('components/wa_button', ['phone' => $parent['phone'], 'label' => 'WA', 'class' => 'btn btn-sm btn-success ms-1']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status Akun</td>
                                <td>
                                    <span class="badge bg-<?= (int)($parent['is_active'] ?? 0) === 1 ? 'success' : 'secondary' ?>">
                                        <?= (int)($parent['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <?php if (function_exists('has_permission') && has_permission('manage_bk_services')): ?>
                        <a href="<?= base_url('counselor/parents/edit/' . (int)($parent['id'] ?? 0)) ?>"
                           class="btn btn-primary">
                            <i class="mdi mdi-pencil me-1"></i>Edit Akun
                        </a>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordParentModal">
                            <i class="mdi mdi-key-variant me-1"></i>Reset Password
                        </button>
                    <?php endif; ?>
                    <a href="<?= base_url('counselor/parents') ?>" class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Anak -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-3">
                <h5 class="card-title mb-0"><i class="mdi mdi-school me-2"></i>Siswa yang Terhubung</h5>
            </div>
            <div class="card-body">
                <?php if (empty($children)): ?>
                    <p class="text-muted mb-0">Tidak ada siswa binaan yang terhubung dengan akun ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th>Kelas</th>
                                    <th class="text-center">JK</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center no-sort">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc($child['full_name'] ?? '-') ?></td>
                                        <td class="small"><?= esc($child['nisn'] ?? '-') ?></td>
                                        <td class="small"><?= esc(($child['grade_level'] ?? '') . ' ' . ($child['class_name'] ?? '')) ?></td>
                                        <td class="text-center">
                                            <?php $g = $child['gender'] ?? ''; ?>
                                            <span class="badge <?= $g === 'L' ? 'bg-info' : ($g === 'P' ? 'bg-danger' : 'bg-secondary') ?>">
                                                <?= $g === 'L' ? 'L' : ($g === 'P' ? 'P' : '-') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= ($child['status'] ?? '') === 'Aktif' ? 'success' : 'secondary' ?>">
                                                <?= esc($child['status'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('counselor/students/' . (int)($child['student_id'] ?? 0)) ?>"
                                               class="btn btn-sm btn-outline-primary" title="Profil Siswa">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reset Password Orang Tua -->
<?php if (function_exists('has_permission') && has_permission('manage_bk_services')): ?>
<div class="modal fade" id="resetPasswordParentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-key-variant text-warning me-2"></i>Reset Password Orang Tua</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('counselor/parents/reset-password/' . (int)($parent['id'] ?? 0)) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Reset password akun <strong><?= esc($parent['full_name'] ?? '-') ?></strong>?</p>
                    <p class="text-warning mb-0"><i class="mdi mdi-information me-1"></i>Password baru dibuat otomatis. Catat & sampaikan ke orang tua.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="mdi mdi-key-variant me-1"></i>Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
