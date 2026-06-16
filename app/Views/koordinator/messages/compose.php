<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/koordinator/messages/compose.php
 * Fitur: Pesan Internal.
 * Peran/izin: Koordinator BK memakai tampilan mandiri; logika data tetap dari controller per peran dan service terkait.
 */
?>
<?php $basePath = trim((string)($basePath ?? ''), '/'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Tulis Pesan Internal</h4>
    <small class="text-muted"><?= esc($roleLabel ?? 'Pengguna') ?></small>
  </div>
  <a href="<?= site_url($basePath . '/messages/inbox') ?>" class="btn btn-light btn-sm">Kotak Masuk</a>
</div>

<?= show_alerts() ?>

<div class="card">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" action="<?= site_url($basePath . '/messages/send') ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Penerima</label>
        <select name="to[]" class="form-select" multiple required size="8">
          <?php foreach (($recipients ?? []) as $user): ?>
            <option value="<?= (int)$user['id'] ?>">
              <?= esc(($user['full_name'] ?: $user['email']) . ' - ' . ($user['role_name'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Subjek</label>
        <input type="text" name="subject" class="form-control" value="<?= esc(old('subject')) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Isi Pesan</label>
        <textarea name="body" class="form-control" rows="7" required><?= esc(old('body')) ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Lampiran <span class="text-muted">(boleh dikosongkan, maksimal 5 berkas, 5 MB per berkas)</span></label>
        <input type="file" name="attachments[]" class="form-control" multiple>
        <small class="text-muted">Jenis berkas: gambar, PDF, Word, Excel, PowerPoint, teks, atau ZIP.</small>
      </div>
      <div class="d-flex justify-content-end gap-2">
        <a href="<?= site_url($basePath . '/messages/inbox') ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Kirim</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
