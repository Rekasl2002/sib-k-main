<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File Path: app/Views/homeroom_teacher/students/parent_show.php
 * Fitur: Detail akun orang tua siswa binaan.
 * Peran/izin: Wali Kelas melihat dan mengelola akun orang tua sesuai kelas binaan.
 * Berhubungan dengan: HomeroomTeacher\StudentController, users, students.
 */

$parent = is_array($parent ?? null) ? $parent : [];
$children = is_array($children ?? null) ? $children : [];
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0">Detail Orang Tua</h4>
        <p class="text-muted mb-0"><?= esc($parent['full_name'] ?? '-') ?></p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= base_url('homeroom/parents/edit/' . (int) ($parent['id'] ?? 0)) ?>" class="btn btn-primary">Edit</a>
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordParentModal">
            <i class="mdi mdi-key-variant me-1"></i>Reset Password
        </button>
        <a href="<?= base_url('homeroom/parents') ?>" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $class): ?>
  <?php if (session()->getFlashdata($key)): ?>
    <div class="alert alert-<?= $class ?> alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata($key)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<div class="row">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Akun</h5>
        <dl class="row mb-0">
          <dt class="col-5">Nama</dt><dd class="col-7"><?= esc($parent['full_name'] ?? '-') ?></dd>
          <dt class="col-5">Username</dt><dd class="col-7"><code><?= esc($parent['username'] ?? '-') ?></code></dd>
          <dt class="col-5">Email</dt><dd class="col-7"><?= esc($parent['email'] ?? '-') ?></dd>
          <dt class="col-5">Telepon</dt><dd class="col-7"><?= esc($parent['phone'] ?? '-') ?></dd>
          <dt class="col-5">Status</dt><dd class="col-7"><?= (int) ($parent['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></dd>
        </dl>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Hapus/Putuskan Akun</h5>
        <p class="text-muted small">Jika akun masih terhubung dengan siswa lain di luar kelas binaan, sistem hanya melepas keterhubungan dari siswa binaan Anda.</p>
        <form method="post" action="<?= base_url('homeroom/parents/delete/' . (int) ($parent['id'] ?? 0)) ?>" onsubmit="return confirm('Hapus atau putuskan akun orang tua ini dari siswa binaan?')">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger w-100">Hapus/Putuskan</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Siswa yang Terhubung</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr><th>Nama Siswa</th><th>NISN</th><th>Kelas</th><th>Status</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach ($children as $child): ?>
                <tr>
                  <td><?= esc($child['full_name'] ?? '-') ?></td>
                  <td><code><?= esc($child['nisn'] ?? '-') ?></code></td>
                  <td><?= esc(trim(($child['grade_level'] ?? '') . ' ' . ($child['class_name'] ?? ''))) ?></td>
                  <td><?= esc($child['status'] ?? '-') ?></td>
                  <td class="text-end"><a href="<?= base_url('homeroom/students/' . (int) ($child['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
                </tr>
              <?php endforeach; ?>
              <?php if (! $children): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada siswa yang terhubung.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Reset Password Orang Tua -->
<div class="modal fade" id="resetPasswordParentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-key-variant text-warning me-2"></i>Reset Password Orang Tua</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('homeroom/parents/reset-password/' . (int) ($parent['id'] ?? 0)) ?>" method="POST">
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

<?= $this->endSection() ?>
