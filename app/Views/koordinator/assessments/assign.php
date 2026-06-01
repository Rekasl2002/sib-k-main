<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $assessmentId = (int)($assessment['id'] ?? 0); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Tugaskan Asesmen</h4>
    <small class="text-muted"><?= esc($assessment['title'] ?? '-') ?></small>
  </div>
  <a href="<?= site_url('koordinator/assessments/show/' . $assessmentId) ?>" class="btn btn-outline-secondary btn-sm">Detail Asesmen</a>
</div>

<?= show_alerts() ?>

<form method="post" action="<?= site_url('koordinator/assessments/' . $assessmentId . '/assign/process') ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-body">
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="replace_assignments" value="1" id="replace_assignments">
        <label class="form-check-label" for="replace_assignments">Ganti penugasan siswa eligible dengan pilihan di bawah</label>
      </div>
      <?php if (empty($students_by_class)): ?>
        <div class="alert alert-info mb-0">Tidak ada siswa eligible yang belum ditugaskan.</div>
      <?php else: ?>
        <?php foreach ($students_by_class as $className => $students): ?>
          <h6 class="mt-3"><?= esc($className) ?></h6>
          <div class="row g-2">
            <?php foreach ($students as $student): ?>
              <div class="col-md-4">
                <label class="border rounded p-2 d-block">
                  <input type="checkbox" name="student_ids[]" value="<?= (int)$student['id'] ?>" class="form-check-input me-1">
                  <?= esc($student['full_name'] ?? '-') ?>
                  <span class="text-muted small d-block"><?= esc($student['nisn'] ?? '') ?></span>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="card-footer text-end">
      <button type="submit" class="btn btn-primary">Simpan Penugasan</button>
    </div>
  </div>
</form>

<form method="post" action="<?= site_url('koordinator/assessments/' . $assessmentId . '/assign/sync') ?>" class="mt-3">
  <?= csrf_field() ?>
  <button class="btn btn-outline-secondary" type="submit">Sinkronkan Penugasan ke Hasil</button>
</form>

<?= $this->endSection() ?>
