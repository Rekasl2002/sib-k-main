<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File Path: app/Views/homeroom_teacher/students/parent_form.php
 * Fitur: Form akun orang tua siswa binaan.
 * Peran/izin: Wali Kelas dengan manage_students.
 * Berhubungan dengan: HomeroomTeacher\StudentController dan tabel users.
 */

$parent = is_array($parent ?? null) ? $parent : [];
$mode = (string) ($mode ?? 'create');
$isEdit = $mode === 'edit';
$action = (string) ($action ?? current_url());
$val = static fn (string $key, $default = '') => old($key, $parent[$key] ?? $default);
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0"><?= esc($pageTitle ?? ($isEdit ? 'Edit Akun Orang Tua' : 'Tambah Akun Orang Tua')) ?></h4>
        <p class="text-muted mb-0">Akun ini dapat dihubungkan ke satu atau beberapa siswa.</p>
      </div>
      <a href="<?= base_url('homeroom/parents') ?>" class="btn btn-outline-secondary">Kembali</a>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?= esc($action, 'attr') ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="<?= $isEdit ? 'full_name' : 'parent_full_name' ?>" class="form-control" value="<?= esc($val('full_name')) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Username <span class="text-danger">*</span></label>
          <input type="text" name="<?= $isEdit ? 'username' : 'parent_username' ?>" class="form-control" value="<?= esc($val('username')) ?>" required>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="<?= $isEdit ? 'email' : 'parent_email' ?>" class="form-control" value="<?= esc($val('email')) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Telepon</label>
          <input type="text" name="<?= $isEdit ? 'phone' : 'parent_phone' ?>" class="form-control" value="<?= esc($val('phone')) ?>" placeholder="08xxxxxxxxxx">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label"><?= $isEdit ? 'Password Baru' : 'Password Awal' ?></label>
          <input type="password" name="<?= $isEdit ? 'password' : 'parent_password' ?>" class="form-control">
          <small class="text-muted"><?= $isEdit ? 'Kosongkan bila tidak diganti.' : 'Kosongkan untuk orangtua123.' ?></small>
        </div>
      </div>
      <?php if ($isEdit): ?>
        <div class="form-check form-switch mb-3">
          <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= (int) $val('is_active', 1) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="is_active">Akun aktif</label>
        </div>
      <?php endif; ?>
      <div class="d-flex justify-content-between">
        <a href="<?= base_url('homeroom/parents') ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </div>
  </div>
</form>

<?= $this->endSection() ?>
