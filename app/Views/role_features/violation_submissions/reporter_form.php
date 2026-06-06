<?php
$basePath = trim((string)($basePath ?? ''), '/');
$row = is_array($row ?? null) ? $row : [];
$mode = $mode ?? 'create';
$action = $mode === 'edit'
    ? site_url($basePath . '/update/' . (int)($row['id'] ?? 0))
    : site_url($basePath . '/store');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0"><?= esc($title ?? 'Form Pengaduan Pelanggaran') ?></h4>
    <small class="text-muted">Status awal pengaduan akan disimpan sebagai Diajukan.</small>
  </div>
  <a href="<?= site_url($basePath) ?>" class="btn btn-light btn-sm">Kembali</a>
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
    <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Siswa Terlapor</label>
          <select name="subject_student_id" class="form-select">
            <option value="">Pilih siswa terdaftar</option>
            <?php $selectedStudent = (string)old('subject_student_id', $row['subject_student_id'] ?? ''); ?>
            <?php foreach (($students ?? []) as $student): ?>
              <option value="<?= (int)$student['id'] ?>" <?= $selectedStudent === (string)$student['id'] ? 'selected' : '' ?>>
                <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-') . ' - ' . ($student['nisn'] ?? '-')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Nama Terlapor Lainnya</label>
          <input type="text" name="subject_other_name" class="form-control" value="<?= esc(old('subject_other_name', $row['subject_other_name'] ?? '')) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Tanggal</label>
          <input type="date" name="occurred_date" class="form-control" value="<?= esc(old('occurred_date', $row['occurred_date'] ?? '')) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Waktu</label>
          <input type="time" name="occurred_time" class="form-control" value="<?= esc(old('occurred_time', $row['occurred_time'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Lokasi</label>
          <input type="text" name="location" class="form-control" value="<?= esc(old('location', $row['location'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Saksi</label>
          <input type="text" name="witness" class="form-control" value="<?= esc(old('witness', $row['witness'] ?? '')) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Deskripsi</label>
          <textarea name="description" rows="6" class="form-control" required><?= esc(old('description', $row['description'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Bukti</label>
          <input type="file" name="evidence_files[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
          <?php $files = is_array($row['evidence_json'] ?? null) ? $row['evidence_json'] : []; ?>
          <?php if ($mode === 'edit' && $files): ?>
            <div class="mt-2">
              <?php foreach ($files as $file): ?>
                <label class="d-block small">
                  <input type="checkbox" name="remove_evidence[]" value="<?= esc($file) ?>"> Hapus <?= esc(basename($file)) ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= site_url($basePath) ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
