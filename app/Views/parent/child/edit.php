<!-- app/Views/parent/child/edit.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
helper(['url', 'form']);
$profile   = is_array($profile ?? null) ? $profile : (array) ($profile ?? []);
$studentId = (int) ($profile['id'] ?? 0);
$today     = $today ?? date('Y-m-d');

$g = static function (string $k) use ($profile) {
    return old($k, $profile[$k] ?? '');
};
$birthDate = old('birth_date', ! empty($profile['birth_date']) ? date('Y-m-d', strtotime((string) $profile['birth_date'])) : '');
$gender    = old('gender', $profile['gender'] ?? '');
$religion  = old('religion', $profile['religion'] ?? '');
$errors    = session('errors') ?? [];
?>

<div class="page-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
          <h4 class="mb-sm-0 text-dark">Ubah Data Anak</h4>
          <div class="page-title-right">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= route_to('parent.children.index') ?>">Daftar Anak</a></li>
              <li class="breadcrumb-item"><a href="<?= route_to('parent.children.profile', $studentId) ?>">Profil Anak</a></li>
              <li class="breadcrumb-item active">Ubah</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <?php if (session('error')): ?>
      <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
      Anda boleh mengubah seluruh data anak, <strong>kecuali</strong> Kelas, Tingkat,
      Jurusan, NISN, dan NIK (hanya sekolah yang dapat mengubahnya).
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark">Data Anak: <?= esc($profile['full_name'] ?? '-') ?></h5>
        <form method="post" action="<?= route_to('parent.children.update', $studentId) ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label text-dark">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control" value="<?= esc(old('full_name', $profile['full_name'] ?? ''), 'attr') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Telepon</label>
              <input type="text" name="phone" class="form-control" value="<?= esc($g('phone'), 'attr') ?>" placeholder="08xxxxxxxxxx">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Jenis Kelamin</label>
              <select name="gender" class="form-select">
                <option value="">- Pilih -</option>
                <option value="L" <?= $gender === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="P" <?= $gender === 'P' ? 'selected' : '' ?>>Perempuan</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Agama</label>
              <select name="religion" class="form-select">
                <option value="">- Pilih -</option>
                <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                  <option value="<?= $ag ?>" <?= $religion === $ag ? 'selected' : '' ?>><?= $ag ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Tempat Lahir</label>
              <input type="text" name="birth_place" class="form-control" value="<?= esc($g('birth_place'), 'attr') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Tanggal Lahir</label>
              <input type="date" name="birth_date" class="form-control" value="<?= esc($birthDate, 'attr') ?>" max="<?= esc($today, 'attr') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Hobi</label>
              <input type="text" name="hobi" class="form-control" value="<?= esc($g('hobi'), 'attr') ?>" placeholder="mis. membaca, sepak bola">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Ekstrakurikuler/Organisasi</label>
              <input type="text" name="ekskul_organisasi" class="form-control" value="<?= esc($g('ekskul_organisasi'), 'attr') ?>" placeholder="mis. Pramuka, OSIS">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Kebutuhan Khusus</label>
              <input type="text" name="special_needs" class="form-control" value="<?= esc($g('special_needs'), 'attr') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Disabilitas</label>
              <input type="text" name="disability" class="form-control" value="<?= esc($g('disability'), 'attr') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Nomor KIP/PIP</label>
              <input type="text" name="kip_pip_number" class="form-control" value="<?= esc($g('kip_pip_number'), 'attr') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Nama Ayah Kandung</label>
              <input type="text" name="father_name" class="form-control" value="<?= esc($g('father_name'), 'attr') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Nama Ibu Kandung</label>
              <input type="text" name="mother_name" class="form-control" value="<?= esc($g('mother_name'), 'attr') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label text-dark">Nama Wali</label>
              <input type="text" name="guardian_name" class="form-control" value="<?= esc($g('guardian_name'), 'attr') ?>">
            </div>
            <div class="col-12">
              <label class="form-label text-dark">Alamat</label>
              <textarea name="address" class="form-control" rows="2"><?= esc($g('address')) ?></textarea>
            </div>
          </div>
          <div class="mt-4 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
            <a class="btn btn-light" href="<?= route_to('parent.children.profile', $studentId) ?>">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
