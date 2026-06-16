<?php
/**
 * View detail Penugasan.
 * Menampilkan instruksi, status, dan riwayat perubahan status.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$histories = is_array($row['histories'] ?? null) ? $row['histories'] : [];
$routePrefix = (string) ($routePrefix ?? '');
?>
<div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between"><div><h4 class="mb-sm-0"><?= esc($row['title'] ?? 'Penugasan') ?></h4><p class="text-muted mb-0"><?= esc($row['assignment_type'] ?? '-') ?> - <?= esc($row['status'] ?? '-') ?></p></div><div class="d-flex gap-2"><a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a><?php if (! empty($canManage)): ?><a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-primary">Edit</a><?php endif; ?></div></div></div></div>
<div class="row">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3">Instruksi</h5>
      <p><?= nl2br(esc($row['instruction'] ?? '-')) ?></p>
      <dl class="row mb-0">
        <dt class="col-md-4">Pemberi Tugas</dt><dd class="col-md-8"><?= esc($row['assigned_by_name'] ?? '-') ?></dd>
        <dt class="col-md-4">Guru BK</dt><dd class="col-md-8"><?= esc($row['assigned_to_name'] ?? '-') ?></dd>
        <dt class="col-md-4">Kelas/Siswa</dt><dd class="col-md-8"><?= esc($row['class_name'] ?? $row['student_name'] ?? '-') ?></dd>
        <dt class="col-md-4">Prioritas</dt><dd class="col-md-8"><?= esc($row['priority'] ?? '-') ?></dd>
        <dt class="col-md-4">Batas Waktu</dt><dd class="col-md-8"><?= esc($row['due_at'] ?? '-') ?></dd>
      </dl>
    </div></div>
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3">Riwayat Status</h5>
      <?php foreach ($histories as $history): ?>
        <div class="border rounded p-3 mb-2"><div class="d-flex justify-content-between"><strong><?= esc($history['status'] ?? '-') ?></strong><small class="text-muted"><?= esc($history['changed_at'] ?? '') ?></small></div><div><?= esc($history['note'] ?? '-') ?></div><small class="text-muted">Oleh <?= esc($history['changed_by_name'] ?? '-') ?></small></div>
      <?php endforeach; ?>
      <?php if (! $histories): ?><p class="text-muted mb-0">Belum ada riwayat.</p><?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3">Ubah Status</h5>
      <form method="post" action="<?= site_url($routePrefix . '/status/' . (int) $row['id']) ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><select name="status" class="form-select"><?php foreach (['Dibaca','Berjalan','Selesai','Dibatalkan'] as $status): ?><option value="<?= esc($status) ?>" <?= ($row['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><textarea name="note" class="form-control" rows="4" placeholder="Catatan perubahan status"></textarea></div>
        <button class="btn btn-primary w-100">Simpan Status</button>
      </form>
    </div></div>
  </div>
</div>
<?= $this->endSection() ?>

