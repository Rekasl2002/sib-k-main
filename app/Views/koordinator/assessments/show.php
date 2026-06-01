<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $a = $assessment ?? []; $id = (int)($a['id'] ?? 0); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0"><?= esc($a['title'] ?? 'Detail Asesmen') ?></h4>
    <small class="text-muted">Koordinator BK</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('koordinator/assessments') ?>" class="btn btn-outline-secondary btn-sm">Daftar</a>
    <a href="<?= site_url('koordinator/assessments/edit/' . $id) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
    <a href="<?= site_url('koordinator/assessments/' . $id . '/questions') ?>" class="btn btn-outline-primary btn-sm">Pertanyaan</a>
    <a href="<?= site_url('koordinator/assessments/' . $id . '/assign') ?>" class="btn btn-outline-success btn-sm">Tugaskan</a>
    <a href="<?= site_url('koordinator/assessments/' . $id . '/results') ?>" class="btn btn-outline-info btn-sm">Hasil</a>
  </div>
</div>

<?= show_alerts() ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Tipe</dt><dd class="col-sm-8"><?= esc($a['assessment_type'] ?? '-') ?></dd>
          <dt class="col-sm-4">Target</dt><dd class="col-sm-8"><?= esc($a['target_audience'] ?? '-') ?></dd>
          <dt class="col-sm-4">Kelas/Tingkat</dt><dd class="col-sm-8"><?= esc($a['target_class_name'] ?? $a['target_grade'] ?? '-') ?></dd>
          <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?= !empty($a['is_published']) ? 'Dipublikasikan' : 'Draft' ?>, <?= !empty($a['is_active']) ? 'Aktif' : 'Nonaktif' ?></dd>
          <dt class="col-sm-4">Periode</dt><dd class="col-sm-8"><?= esc($a['start_date'] ?? '-') ?> sampai <?= esc($a['end_date'] ?? '-') ?></dd>
          <dt class="col-sm-4">Deskripsi</dt><dd class="col-sm-8"><?= nl2br(esc($a['description'] ?? '-')) ?></dd>
          <dt class="col-sm-4">Instruksi</dt><dd class="col-sm-8"><?= nl2br(esc($a['instructions'] ?? '-')) ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h6>Ringkasan</h6>
        <div class="d-flex justify-content-between"><span>Pertanyaan</span><strong><?= count($questions ?? []) ?></strong></div>
        <div class="d-flex justify-content-between"><span>Peserta</span><strong><?= (int)($stats['total_participants'] ?? $a['total_participants'] ?? 0) ?></strong></div>
        <div class="d-flex justify-content-between"><span>Selesai</span><strong><?= (int)($stats['completed'] ?? 0) ?></strong></div>
        <hr>
        <form method="post" action="<?= site_url('koordinator/assessments/' . $id . (!empty($a['is_published']) ? '/unpublish' : '/publish')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-<?= !empty($a['is_published']) ? 'warning' : 'success' ?> w-100" type="submit">
            <?= !empty($a['is_published']) ? 'Batalkan Publikasi' : 'Publikasikan' ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-body table-responsive">
    <h6>Daftar Pertanyaan</h6>
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>No</th><th>Pertanyaan</th><th>Tipe</th><th class="text-end">Poin</th></tr></thead>
      <tbody>
        <?php if (empty($questions)): ?>
          <tr><td colspan="4" class="text-center text-muted">Belum ada pertanyaan.</td></tr>
        <?php else: foreach ($questions as $q): ?>
          <tr>
            <td><?= (int)($q['order_number'] ?? 0) ?></td>
            <td><?= esc($q['question_text'] ?? '-') ?></td>
            <td><?= esc($q['question_type'] ?? '-') ?></td>
            <td class="text-end"><?= esc($q['points'] ?? '0') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
