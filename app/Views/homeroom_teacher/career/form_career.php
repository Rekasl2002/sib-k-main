<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$career = is_array($career ?? null) ? $career : [];
$mode = $mode ?? 'create';
$action = $mode === 'edit'
    ? site_url('homeroom/career-info/careers/update/' . (int)($career['id'] ?? 0))
    : site_url('homeroom/career-info/careers/store');
$skills = old('skills', $career['required_skills_array'] ?? []);
$skills = is_array($skills) ? $skills : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0"><?= esc($title ?? 'Form Info Karier') ?></h4>
    <small class="text-muted">Wali Kelas</small>
  </div>
  <a href="<?= site_url('homeroom/career-info?tab=careers') ?>" class="btn btn-light btn-sm">Kembali</a>
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
          <label class="form-label">Judul Karier</label>
          <input type="text" name="title" class="form-control" value="<?= esc(old('title', $career['title'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Sektor</label>
          <input type="text" name="sector" class="form-control" value="<?= esc(old('sector', $career['sector'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Minimal Pendidikan</label>
          <input type="text" name="min_education" class="form-control" value="<?= esc(old('min_education', $career['min_education'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Perkiraan Gaji</label>
          <input type="number" name="avg_salary_idr" class="form-control" value="<?= esc(old('avg_salary_idr', $career['avg_salary_idr'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Tingkat Permintaan</label>
          <input type="number" min="0" max="10" name="demand_level" class="form-control" value="<?= esc(old('demand_level', $career['demand_level'] ?? 0)) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Deskripsi</label>
          <textarea name="description" rows="5" class="form-control" required><?= esc(old('description', $career['description'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Jalur/Panduan</label>
          <textarea name="pathways" rows="4" class="form-control"><?= esc(old('pathways', $career['pathways'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Keterampilan</label>
          <?php for ($i = 0; $i < 4; $i++): ?>
            <input type="text" name="skills[]" class="form-control mb-2" value="<?= esc($skills[$i] ?? '') ?>">
          <?php endfor; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="is_active" class="form-select" required>
            <option value="1" <?= (string)old('is_active', $career['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= (string)old('is_active', $career['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Publikasi</label>
          <select name="is_public" class="form-select">
            <option value="1" <?= (string)old('is_public', $career['is_public'] ?? '0') === '1' ? 'selected' : '' ?>>Publik</option>
            <option value="0" <?= (string)old('is_public', $career['is_public'] ?? '0') === '0' ? 'selected' : '' ?>>Internal</option>
          </select>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= site_url('homeroom/career-info?tab=careers') ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
