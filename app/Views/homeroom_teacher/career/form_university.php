<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$uni = is_array($uni ?? null) ? $uni : [];
$mode = $mode ?? 'create';
$action = $mode === 'edit'
    ? site_url('homeroom/career-info/universities/update/' . (int)($uni['id'] ?? 0))
    : site_url('homeroom/career-info/universities/store');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0"><?= esc($title ?? 'Form Info Studi Lanjut') ?></h4>
    <small class="text-muted">Wali Kelas</small>
  </div>
  <a href="<?= site_url('homeroom/career-info?tab=universities') ?>" class="btn btn-light btn-sm">Kembali</a>
</div>

<?= show_alerts() ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Nama Perguruan Tinggi</label>
          <input type="text" name="university_name" class="form-control" value="<?= esc(old('university_name', $uni['university_name'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Alias</label>
          <input type="text" name="alias" class="form-control" value="<?= esc(old('alias', $uni['alias'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Akreditasi</label>
          <input type="text" name="accreditation" class="form-control" value="<?= esc(old('accreditation', $uni['accreditation'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Lokasi</label>
          <input type="text" name="location" class="form-control" value="<?= esc(old('location', $uni['location'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Website</label>
          <input type="url" name="website" class="form-control" value="<?= esc(old('website', $uni['website'] ?? '')) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Logo URL</label>
          <input type="url" name="logo" class="form-control" value="<?= esc(old('logo', $uni['logo'] ?? '')) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Deskripsi</label>
          <textarea name="description" rows="5" class="form-control"><?= esc(old('description', $uni['description'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Info Penerimaan</label>
          <textarea name="admission_info" rows="4" class="form-control"><?= esc(old('admission_info', $uni['admission_info'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Kisaran Biaya</label>
          <textarea name="tuition_range" rows="4" class="form-control"><?= esc(old('tuition_range', $uni['tuition_range'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="is_active" class="form-select" required>
            <option value="1" <?= (string)old('is_active', $uni['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= (string)old('is_active', $uni['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Publikasi</label>
          <select name="is_public" class="form-select">
            <option value="1" <?= (string)old('is_public', $uni['is_public'] ?? '0') === '1' ? 'selected' : '' ?>>Publik</option>
            <option value="0" <?= (string)old('is_public', $uni['is_public'] ?? '0') === '0' ? 'selected' : '' ?>>Internal</option>
          </select>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= site_url('homeroom/career-info?tab=universities') ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
