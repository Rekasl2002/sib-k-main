<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/homeroom_teacher/messages/detail.php
 * Fitur: Pesan Internal.
 * Peran/izin: Wali Kelas memakai tampilan mandiri; logika data tetap dari controller per peran dan service terkait.
 */
?>
<?php
$basePath = trim((string)($basePath ?? ''), '/');
$msg = is_array($msg ?? null) ? $msg : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0"><?= esc($msg['subject'] ?? 'Detail Pesan') ?></h4>
    <small class="text-muted">Dari: <?= esc($msg['sender_name'] ?? '-') ?></small>
  </div>
  <div class="d-flex gap-2">
    <?php if ((int)($msg['created_by'] ?? 0) === (int)(session('user_id') ?? 0)): ?>
      <a href="<?= site_url($basePath . '/messages/edit/' . (int)($msg['id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
    <?php endif; ?>
    <a href="<?= site_url($basePath . '/messages/inbox') ?>" class="btn btn-light btn-sm">Kotak Masuk</a>
  </div>
</div>

<?= show_alerts() ?>

<div class="card mb-3">
  <div class="card-body">
    <div class="text-muted small mb-3"><?= esc($msg['created_at'] ?? '') ?></div>
    <div style="white-space:pre-wrap"><?= esc($msg['body'] ?? '') ?></div>
    <?php if (!empty($attachments)): ?>
      <div class="mt-3">
        <div class="fw-semibold mb-2"><i class="mdi mdi-paperclip"></i> Lampiran</div>
        <ul class="list-group">
          <?php foreach ($attachments as $att): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-truncate"><?= esc($att['file_name'] ?? 'berkas') ?></span>
              <a href="<?= site_url($basePath . '/messages/attachment/' . (int)($att['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">Unduh</a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
  <?php if (!empty($participants)): ?>
    <div class="card-footer small text-muted">
      Penerima:
      <?= esc(implode(', ', array_map(static fn($p) => $p['full_name'] ?? $p['email'] ?? '-', $participants))) ?>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Balas Pesan</h5></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" action="<?= site_url($basePath . '/messages/reply/' . (int)($msg['id'] ?? 0)) ?>">
      <?= csrf_field() ?>
      <textarea name="body" class="form-control mb-3" rows="4" required></textarea>
      <div class="mb-3">
        <label class="form-label">Lampiran <span class="text-muted">(boleh dikosongkan, maksimal 5 berkas, 5 MB per berkas)</span></label>
        <input type="file" name="attachments[]" class="form-control" multiple>
      </div>
      <div class="d-flex justify-content-between">
        <span></span>
        <button type="submit" class="btn btn-primary">Kirim Balasan</button>
      </div>
    </form>
    <form method="post" action="<?= site_url($basePath . '/messages/delete/' . (int)($msg['id'] ?? 0)) ?>" class="mt-2" onsubmit="return confirm('Hapus pesan ini?')">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Pesan</button>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
