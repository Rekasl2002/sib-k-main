<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
helper('form');
$A = $assessment ?? [];
$method = $method ?? 'create';
$isEdit = $method === 'edit';
$action = $isEdit
    ? site_url('koordinator/assessments/update/' . (int)($A['id'] ?? 0))
    : site_url('koordinator/assessments/store');
$targetAudiences = [
    'Individual' => 'Per siswa tertentu',
    'Class'      => 'Per kelas',
    'Grade'      => 'Per tingkat',
    'All'        => 'Semua siswa',
];
$types = ['Psikologi', 'Minat Bakat', 'Kecerdasan', 'Motivasi', 'Custom'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= esc($title ?? ($isEdit ? 'Edit Asesmen' : 'Buat Asesmen')) ?></h4>
  <a href="<?= site_url('koordinator/assessments') ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>

<?= show_alerts() ?>

<form method="post" action="<?= $action ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label" for="title">Judul asesmen</label>
        <input class="form-control" id="title" name="title" value="<?= esc(old('title', $A['title'] ?? '')) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="assessment_type">Tipe asesmen</label>
        <select class="form-select" id="assessment_type" name="assessment_type" required>
          <option value="">Pilih tipe</option>
          <?php foreach ($types as $type): ?>
            <option value="<?= esc($type, 'attr') ?>" <?= old('assessment_type', $A['assessment_type'] ?? '') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label" for="description">Deskripsi</label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= esc(old('description', $A['description'] ?? '')) ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label" for="instructions">Instruksi pengerjaan</label>
        <textarea class="form-control" id="instructions" name="instructions" rows="3"><?= esc(old('instructions', $A['instructions'] ?? '')) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="target_audience">Target peserta</label>
        <select class="form-select" id="target_audience" name="target_audience" required>
          <?php foreach ($targetAudiences as $value => $label): ?>
            <option value="<?= esc($value, 'attr') ?>" <?= old('target_audience', $A['target_audience'] ?? 'Individual') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="target_class_id">Kelas target</label>
        <select class="form-select" id="target_class_id" name="target_class_id">
          <option value="">Tidak dibatasi kelas</option>
          <?php foreach (($classes ?? []) as $class): ?>
            <option value="<?= (int)$class['id'] ?>" <?= (string)old('target_class_id', $A['target_class_id'] ?? '') === (string)$class['id'] ? 'selected' : '' ?>>
              <?= esc($class['class_name'] ?? '-') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="target_grade">Tingkat target</label>
        <select class="form-select" id="target_grade" name="target_grade">
          <option value="">Tidak dibatasi tingkat</option>
          <?php foreach (($grades ?? []) as $value => $label): ?>
            <option value="<?= esc($value, 'attr') ?>" <?= (string)old('target_grade', $A['target_grade'] ?? '') === (string)$value ? 'selected' : '' ?>><?= esc($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="start_date">Tanggal mulai</label>
        <input class="form-control" type="date" id="start_date" name="start_date" value="<?= esc(old('start_date', $A['start_date'] ?? '')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="end_date">Tanggal selesai</label>
        <input class="form-control" type="date" id="end_date" name="end_date" value="<?= esc(old('end_date', $A['end_date'] ?? '')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="duration_minutes">Durasi menit</label>
        <input class="form-control" type="number" min="0" id="duration_minutes" name="duration_minutes" value="<?= esc(old('duration_minutes', $A['duration_minutes'] ?? '')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="max_attempts">Maks. percobaan</label>
        <input class="form-control" type="number" min="0" id="max_attempts" name="max_attempts" value="<?= esc(old('max_attempts', $A['max_attempts'] ?? 1)) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="evaluation_mode">Mode penilaian</label>
        <select class="form-select" id="evaluation_mode" name="evaluation_mode">
          <?php foreach (($evaluation_modes ?? ['pass_fail' => 'Pass/Fail', 'score_only' => 'Skor Saja', 'survey' => 'Survei']) as $value => $label): ?>
            <option value="<?= esc($value, 'attr') ?>" <?= old('evaluation_mode', $A['evaluation_mode'] ?? 'pass_fail') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="passing_score">Nilai kelulusan</label>
        <input class="form-control" type="number" step="0.01" min="0" max="100" id="passing_score" name="passing_score" value="<?= esc(old('passing_score', $A['passing_score'] ?? '')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="result_release_at">Rilis hasil</label>
        <input class="form-control" type="datetime-local" id="result_release_at" name="result_release_at" value="<?= esc(old('result_release_at', isset($A['result_release_at']) ? str_replace(' ', 'T', substr((string)$A['result_release_at'], 0, 16)) : '')) ?>">
      </div>
    </div>

    <div class="row g-2 mt-3">
      <?php
      $checks = [
          'is_active' => 'Aktif',
          'is_published' => 'Dipublikasikan',
          'use_passing_score' => 'Gunakan nilai kelulusan',
          'show_score_to_student' => 'Tampilkan nilai ke siswa',
          'show_result_immediately' => 'Tampilkan hasil segera',
          'allow_review' => 'Izinkan review hasil',
      ];
      ?>
      <?php foreach ($checks as $field => $label): ?>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="<?= esc($field, 'attr') ?>" name="<?= esc($field, 'attr') ?>" value="1" <?= (int)old($field, $A[$field] ?? 0) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="<?= esc($field, 'attr') ?>"><?= esc($label) ?></label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-footer text-end">
    <button type="submit" class="btn btn-primary">Simpan</button>
  </div>
</form>

<?= $this->endSection() ?>
