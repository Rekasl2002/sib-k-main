<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $a = $assessment ?? []; $assessmentId = (int)($a['id'] ?? 0); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Pertanyaan Asesmen</h4>
  <a href="<?= site_url('koordinator/assessments/show/' . $assessmentId) ?>" class="btn btn-outline-secondary btn-sm">Detail Asesmen</a>
</div>

<?= show_alerts() ?>

<div class="card mb-3">
  <div class="card-body">
    <form method="post" action="<?= site_url('koordinator/assessments/' . $assessmentId . '/questions/add') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label" for="question_text">Pertanyaan</label>
          <textarea class="form-control" id="question_text" name="question_text" rows="2" required></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="question_type">Tipe</label>
          <select class="form-select" id="question_type" name="question_type">
            <?php foreach (['Multiple Choice', 'True/False', 'Checkbox', 'Essay', 'Rating Scale'] as $type): ?>
              <option value="<?= esc($type, 'attr') ?>"><?= esc($type) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="points">Poin</label>
          <input class="form-control" type="number" step="0.01" id="points" name="points" value="1">
        </div>
        <div class="col-md-3">
          <label class="form-label" for="dimension">Dimensi</label>
          <input class="form-control" id="dimension" name="dimension">
        </div>
        <div class="col-md-6">
          <label class="form-label">Opsi jawaban</label>
          <input class="form-control mb-1" name="options[]" placeholder="Opsi 1">
          <input class="form-control mb-1" name="options[]" placeholder="Opsi 2">
          <input class="form-control" name="options[]" placeholder="Opsi 3">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="correct_option">Jawaban benar tunggal</label>
          <input class="form-control" id="correct_option" name="correct_option">
          <small class="text-muted">Untuk Checkbox, isi opsi benar lewat field di bawah.</small>
          <input class="form-control mt-1" name="correct_options[]" placeholder="Opsi benar checkbox">
        </div>
        <div class="col-12">
          <label class="form-label" for="explanation">Penjelasan</label>
          <textarea class="form-control" id="explanation" name="explanation" rows="2"></textarea>
        </div>
      </div>
      <div class="text-end mt-3">
        <button class="btn btn-primary" type="submit">Tambah Pertanyaan</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead><tr><th>No</th><th>Pertanyaan</th><th>Tipe</th><th>Poin</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php if (empty($questions)): ?>
        <tr><td colspan="5" class="text-center text-muted">Belum ada pertanyaan.</td></tr>
      <?php else: foreach ($questions as $q): ?>
        <tr>
          <td><?= (int)($q['order_number'] ?? 0) ?></td>
          <td><?= esc($q['question_text'] ?? '-') ?></td>
          <td><?= esc($q['question_type'] ?? '-') ?></td>
          <td><?= esc($q['points'] ?? '0') ?></td>
          <td class="text-end">
            <form method="post" action="<?= site_url('koordinator/assessments/' . $assessmentId . '/questions/' . (int)$q['id'] . '/delete') ?>" onsubmit="return confirm('Hapus pertanyaan ini?')" class="d-inline">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
