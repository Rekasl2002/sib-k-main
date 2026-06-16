<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/student/messages/edit.php
 * Fitur: Pesan Internal.
 * Peran/izin: Siswa memakai tampilan mandiri; logika data tetap dari controller per peran dan service terkait.
 */
?>
<?php
$basePath = trim((string)($basePath ?? ''), '/');
$msg = is_array($msg ?? null) ? $msg : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Edit Pesan Internal</h4>
    <small class="text-muted"><?= esc($roleLabel ?? 'Pengguna') ?></small>
  </div>
  <a href="<?= site_url($basePath . '/messages/detail/' . (int)($msg['id'] ?? 0)) ?>" class="btn btn-light btn-sm">Kembali</a>
</div>

<?= show_alerts() ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= site_url($basePath . '/messages/update/' . (int)($msg['id'] ?? 0)) ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Subjek</label>
        <input type="text" name="subject" class="form-control" value="<?= esc(old('subject', $msg['subject'] ?? '')) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Isi Pesan</label>
        <textarea name="body" class="form-control" rows="8" required><?= esc(old('body', $msg['body'] ?? '')) ?></textarea>
      </div>
      <div class="d-flex justify-content-end gap-2">
        <a href="<?= site_url($basePath . '/messages/detail/' . (int)($msg['id'] ?? 0)) ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
