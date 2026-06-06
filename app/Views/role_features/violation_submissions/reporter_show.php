<?php
$basePath = trim((string)($basePath ?? ''), '/');
$row = is_array($row ?? null) ? $row : [];
$status = $row['status'] ?? 'Diajukan';
$badge = match ($status) {
    'Ditolak' => 'danger',
    'Diterima' => 'success',
    'Ditinjau' => 'info',
    default => 'warning',
};
$subject = $row['subject_student_name'] ?? $row['subject_other_name'] ?? '-';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Detail Pengaduan #<?= (int)($row['id'] ?? 0) ?></h4>
    <span class="badge bg-<?= esc($badge) ?>"><?= esc($status) ?></span>
  </div>
  <a href="<?= site_url($basePath) ?>" class="btn btn-light btn-sm">Kembali</a>
</div>

<?= show_alerts() ?>

<div class="card">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Terlapor</dt>
      <dd class="col-sm-9"><?= esc($subject) ?><?= !empty($row['subject_student_class']) ? ' - ' . esc($row['subject_student_class']) : '' ?></dd>
      <dt class="col-sm-3">Waktu</dt>
      <dd class="col-sm-9"><?= esc($row['occurred_date'] ?? '-') ?> <?= esc($row['occurred_time'] ?? '') ?></dd>
      <dt class="col-sm-3">Lokasi</dt>
      <dd class="col-sm-9"><?= esc($row['location'] ?? '-') ?></dd>
      <dt class="col-sm-3">Saksi</dt>
      <dd class="col-sm-9"><?= esc($row['witness'] ?? '-') ?></dd>
      <dt class="col-sm-3">Deskripsi</dt>
      <dd class="col-sm-9"><div style="white-space:pre-wrap"><?= esc($row['description'] ?? '-') ?></div></dd>
      <dt class="col-sm-3">Catatan Tinjauan</dt>
      <dd class="col-sm-9"><?= esc($row['review_notes'] ?? '-') ?></dd>
    </dl>
  </div>
  <div class="card-footer d-flex gap-2">
    <?php if (!empty($isEditable)): ?>
      <a href="<?= site_url($basePath . '/edit/' . (int)($row['id'] ?? 0)) ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
      <form method="post" action="<?= site_url($basePath . '/delete/' . (int)($row['id'] ?? 0)) ?>" onsubmit="return confirm('Hapus pengaduan ini?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
      </form>
    <?php endif; ?>
  </div>
</div>
