<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File Path: app/Views/homeroom_teacher/students/form.php
 * Fitur: Manajemen siswa kelas binaan.
 * Peran/izin: Wali Kelas dengan manage_students; hanya untuk kelas binaannya.
 * Berhubungan dengan: HomeroomTeacher\StudentController, students, users,
 * classes, dan akun orang tua pada users.role_id Orang Tua.
 */

$errors = session()->getFlashdata('errors') ?? [];
if (! is_array($errors)) {
    $errors = [];
}

$student = is_array($student ?? null) ? $student : [];
$classes = is_array($classes ?? null) ? $classes : [];
$parents = is_array($parents ?? null) ? $parents : [];
$mode = (string) ($mode ?? 'create');
$isEdit = $mode === 'edit';
$action = (string) ($action ?? current_url());

$genderOptions = is_array($gender_options ?? null) ? $gender_options : ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$religionOptions = is_array($religion_options ?? null) ? $religion_options : ['Islam'];
$statusOptions = is_array($status_options ?? null) ? $status_options : ['Aktif'];

$val = static fn (string $key, $default = '') => old($key, $student[$key] ?? $default);
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' is-invalid' : '';
$fieldError = static fn (string $key): string => isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . esc($errors[$key]) . '</div>' : '';
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0"><?= esc($pageTitle ?? ($isEdit ? 'Edit Siswa' : 'Tambah Siswa')) ?></h4>
        <p class="text-muted mb-0">Data hanya berlaku untuk siswa pada kelas binaan Anda.</p>
      </div>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/my-class') ?>">Kelas Binaan</a></li>
          <li class="breadcrumb-item active"><?= $isEdit ? 'Edit' : 'Tambah' ?></li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>

<?php if (! empty($errors)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Periksa kembali data berikut:</strong>
    <ul class="mb-0 mt-2">
      <?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?= esc($action, 'attr') ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int) ($student['id'] ?? 0) ?>">
  <?php else: ?>
    <input type="hidden" name="create_with_user" value="1">
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Akun Siswa</h5>
          <div class="mb-3">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control<?= $invalid('full_name') ?>" value="<?= esc($val('full_name')) ?>" required>
            <?= $fieldError('full_name') ?>
          </div>

          <?php if (! $isEdit): ?>
            <div class="mb-3">
              <label class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control<?= $invalid('username') ?>" value="<?= old('username') ?>" required>
              <?= $fieldError('username') ?>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control<?= $invalid('email') ?>" value="<?= old('email') ?>">
              <?= $fieldError('email') ?>
            </div>
            <div class="mb-3">
              <label class="form-label">Password Awal <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control<?= $invalid('password') ?>" required>
              <?= $fieldError('password') ?>
            </div>
          <?php else: ?>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" value="<?= esc($student['username'] ?? '-') ?>" disabled>
              <small class="text-muted">Username diubah melalui manajemen pengguna bila diperlukan.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?= esc($student['email'] ?? '-') ?>" disabled>
            </div>
          <?php endif; ?>

          <div class="mb-0">
            <label class="form-label">Nomor Telepon Siswa</label>
            <input type="text" name="phone" class="form-control<?= $invalid('phone') ?>" value="<?= esc($val('phone')) ?>" placeholder="08xxxxxxxxxx">
            <?= $fieldError('phone') ?>
          </div>
        </div>
      </div>

      <?php if (! $isEdit): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Akun Orang Tua</h5>
            <div class="mb-3">
              <label class="form-label">Cara Menghubungkan</label>
              <select class="form-select" name="parent_mode" id="parentMode">
                <option value="existing" <?= old('parent_mode', 'existing') === 'existing' ? 'selected' : '' ?>>Pilih akun yang sudah ada</option>
                <option value="new" <?= old('parent_mode') === 'new' ? 'selected' : '' ?>>Buat akun orang tua baru</option>
                <option value="none" <?= old('parent_mode') === 'none' ? 'selected' : '' ?>>Belum dihubungkan</option>
              </select>
            </div>
            <div id="existingParentFields">
              <label class="form-label">Orang Tua/Wali</label>
              <select name="parent_id" class="form-select">
                <option value="">Belum dihubungkan</option>
                <?php foreach ($parents as $parent): ?>
                  <option value="<?= (int) ($parent['id'] ?? 0) ?>" <?= old('parent_id') == ($parent['id'] ?? null) ? 'selected' : '' ?>>
                    <?= esc(($parent['full_name'] ?? '-') . (! empty($parent['phone']) ? ' - ' . $parent['phone'] : '')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="newParentFields" style="display:none;">
              <div class="mb-2">
                <label class="form-label">Nama Orang Tua</label>
                <input type="text" name="parent_full_name" class="form-control" value="<?= old('parent_full_name') ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Username Orang Tua</label>
                <input type="text" name="parent_username" class="form-control" value="<?= old('parent_username') ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Email Orang Tua</label>
                <input type="email" name="parent_email" class="form-control" value="<?= old('parent_email') ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Telepon Orang Tua</label>
                <input type="text" name="parent_phone" class="form-control" value="<?= old('parent_phone') ?>" placeholder="08xxxxxxxxxx">
              </div>
              <div>
                <label class="form-label">Password Orang Tua</label>
                <input type="password" name="parent_password" class="form-control">
                <small class="text-muted">Kosongkan untuk memakai password awal: orangtua123.</small>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Data Siswa</h5>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">NISN <span class="text-danger">*</span></label>
              <input type="text" name="nisn" class="form-control<?= $invalid('nisn') ?>" value="<?= esc($val('nisn')) ?>" maxlength="10" required>
              <?= $fieldError('nisn') ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">NIK</label>
              <input type="text" name="nik" class="form-control<?= $invalid('nik') ?>" value="<?= esc($val('nik')) ?>" maxlength="16">
              <?= $fieldError('nik') ?>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
              <select name="gender" class="form-select<?= $invalid('gender') ?>" required>
                <option value="">Pilih</option>
                <?php foreach ($genderOptions as $key => $label): ?>
                  <option value="<?= esc($key) ?>" <?= $val('gender') === (string) $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $fieldError('gender') ?>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Kelas <span class="text-danger">*</span></label>
              <select name="class_id" class="form-select<?= $invalid('class_id') ?>" required>
                <option value="">Pilih Kelas</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= (int) ($class['id'] ?? 0) ?>" <?= (string) $val('class_id') === (string) ($class['id'] ?? '') ? 'selected' : '' ?>>
                    <?= esc(($class['grade_level'] ?? '-') . ' - ' . ($class['class_name'] ?? '-')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?= $fieldError('class_id') ?>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select<?= $invalid('status') ?>">
                <?php foreach ($statusOptions as $status): ?>
                  <option value="<?= esc($status) ?>" <?= $val('status', 'Aktif') === (string) $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $fieldError('status') ?>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Tempat Lahir</label>
              <input type="text" name="birth_place" class="form-control<?= $invalid('birth_place') ?>" value="<?= esc($val('birth_place')) ?>">
              <?= $fieldError('birth_place') ?>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date" name="birth_date" class="form-control<?= $invalid('birth_date') ?>" value="<?= esc($val('birth_date')) ?>">
              <?= $fieldError('birth_date') ?>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Agama</label>
              <select name="religion" class="form-select<?= $invalid('religion') ?>">
                <option value="">Pilih</option>
                <?php foreach ($religionOptions as $religion): ?>
                  <option value="<?= esc($religion) ?>" <?= $val('religion') === (string) $religion ? 'selected' : '' ?>><?= esc($religion) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $fieldError('religion') ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="address" class="form-control<?= $invalid('address') ?>" rows="3"><?= esc($val('address')) ?></textarea>
            <?= $fieldError('address') ?>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Nama Ayah</label>
              <input type="text" name="father_name" class="form-control<?= $invalid('father_name') ?>" value="<?= esc($val('father_name')) ?>">
              <?= $fieldError('father_name') ?>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Nama Ibu</label>
              <input type="text" name="mother_name" class="form-control<?= $invalid('mother_name') ?>" value="<?= esc($val('mother_name')) ?>">
              <?= $fieldError('mother_name') ?>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Nama Wali</label>
              <input type="text" name="guardian_name" class="form-control<?= $invalid('guardian_name') ?>" value="<?= esc($val('guardian_name')) ?>">
              <?= $fieldError('guardian_name') ?>
            </div>
          </div>

          <?php if ($isEdit): ?>
            <div class="mb-3">
              <label class="form-label">Orang Tua/Wali</label>
              <select name="parent_id" class="form-select<?= $invalid('parent_id') ?>">
                <option value="">Belum dihubungkan</option>
                <?php foreach ($parents as $parent): ?>
                  <option value="<?= (int) ($parent['id'] ?? 0) ?>" <?= (string) $val('parent_id') === (string) ($parent['id'] ?? '') ? 'selected' : '' ?>>
                    <?= esc(($parent['full_name'] ?? '-') . (! empty($parent['phone']) ? ' - ' . $parent['phone'] : '')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?= $fieldError('parent_id') ?>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-between">
            <a href="<?= base_url('homeroom/my-class') ?>" class="btn btn-outline-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php if (! $isEdit): ?>
<script>
(function() {
  const mode = document.getElementById('parentMode');
  const existing = document.getElementById('existingParentFields');
  const fresh = document.getElementById('newParentFields');
  function applyMode() {
    const value = mode ? mode.value : 'existing';
    if (existing) existing.style.display = value === 'existing' ? '' : 'none';
    if (fresh) fresh.style.display = value === 'new' ? '' : 'none';
  }
  if (mode) {
    mode.addEventListener('change', applyMode);
    applyMode();
  }
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
