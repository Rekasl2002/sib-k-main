<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $result = $result ?? []; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Detail Hasil Asesmen</h4>
    <small class="text-muted"><?= esc($result['assessment_title'] ?? '-') ?> - <?= esc($result['student_name'] ?? '-') ?></small>
  </div>
  <a href="<?= site_url('koordinator/assessments/' . (int)$assessmentId . '/results') ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>

<?= show_alerts() ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <dl class="mb-0">
          <dt>Siswa</dt><dd><?= esc($result['student_name'] ?? '-') ?></dd>
          <dt>NISN</dt><dd><?= esc($result['nisn'] ?? '-') ?></dd>
          <dt>Kelas</dt><dd><?= esc($result['class_name'] ?? '-') ?></dd>
          <dt>Status</dt><dd><?= esc($result['status'] ?? '-') ?></dd>
          <dt>Skor</dt><dd><?= esc($result['percentage'] ?? '-') ?>%</dd>
          <dt>Waktu selesai</dt><dd><?= esc($result['completed_at'] ?? '-') ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <form method="post" action="<?= site_url('koordinator/assessments/review/' . (int)$resultId) ?>" class="card">
      <?= csrf_field() ?>
      <div class="card-body">
        <h6>Catatan Review</h6>
        <div class="mb-3">
          <label class="form-label" for="interpretation">Interpretasi</label>
          <textarea class="form-control" id="interpretation" name="interpretation" rows="2"><?= esc($result['interpretation'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="recommendations">Rekomendasi</label>
          <textarea class="form-control" id="recommendations" name="recommendations" rows="2"><?= esc($result['recommendations'] ?? '') ?></textarea>
        </div>
        <div class="mb-0">
          <label class="form-label" for="counselor_notes">Catatan petugas BK</label>
          <textarea class="form-control" id="counselor_notes" name="counselor_notes" rows="2"><?= esc($result['counselor_notes'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="card-footer text-end">
        <button class="btn btn-primary" type="submit">Simpan Review</button>
      </div>
    </form>
  </div>
</div>

<div class="card mt-3">
  <div class="card-body table-responsive">
    <h6>Jawaban</h6>
    <table class="table align-middle">
      <thead><tr><th>No</th><th>Pertanyaan</th><th>Jawaban</th><th>Skor</th><th>Feedback</th></tr></thead>
      <tbody>
        <?php if (empty($questions)): ?>
          <tr><td colspan="5" class="text-center text-muted">Belum ada jawaban.</td></tr>
        <?php else: foreach ($questions as $q): ?>
          <?php
            $answer = $q['answer_text'] ?? $q['answer_option'] ?? $q['answer_options'] ?? '';
            if (is_string($answer) && str_starts_with(trim($answer), '[')) {
                $decoded = json_decode($answer, true);
                if (is_array($decoded)) $answer = implode(', ', $decoded);
            }
          ?>
          <tr>
            <td><?= (int)($q['order_number'] ?? 0) ?></td>
            <td><?= esc($q['question_text'] ?? '-') ?></td>
            <td><?= esc((string)$answer) ?></td>
            <td><?= esc($q['answer_score'] ?? '-') ?></td>
            <td><?= esc($q['feedback'] ?? '-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
