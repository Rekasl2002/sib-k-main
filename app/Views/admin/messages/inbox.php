<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/admin/messages/inbox.php
 * Fitur: Pesan Internal.
 * Peran/izin: Admin memakai tampilan mandiri; logika data tetap dari controller per peran dan service terkait.
 */
?>
<?php $basePath = trim((string)($basePath ?? ''), '/'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Pesan Internal</h4>
    <small class="text-muted"><?= esc($roleLabel ?? 'Pengguna') ?> - kotak masuk</small>
  </div>
  <div class="btn-group">
    <a href="<?= site_url($basePath . '/messages/sent') ?>" class="btn btn-light btn-sm">Terkirim</a>
    <a href="<?= site_url($basePath . '/messages/compose') ?>" class="btn btn-primary btn-sm">Tulis Pesan</a>
  </div>
</div>

<?= show_alerts() ?>

<div class="card">
  <div class="list-group list-group-flush">
    <?php if (empty($rows)): ?>
      <div class="p-4 text-center text-muted">Belum ada pesan masuk.</div>
    <?php else: ?>
      <?php foreach ($rows as $row): ?>
        <a class="list-group-item list-group-item-action <?= !empty($row['is_read']) ? '' : 'fw-semibold bg-light' ?>"
           href="<?= site_url($basePath . '/messages/detail/' . (int)$row['id']) ?>">
          <div class="d-flex justify-content-between gap-3">
            <span><?= esc($row['subject'] ?? '(tanpa subjek)') ?></span>
            <small class="text-muted text-nowrap"><?= esc($row['created_at'] ?? '') ?></small>
          </div>
          <div class="small text-muted">Dari: <?= esc($row['sender_name'] ?? '-') ?></div>
          <div class="text-truncate text-muted"><?= esc(strip_tags((string)($row['body'] ?? ''))) ?></div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php if (!empty($pager)): ?>
    <div class="card-footer"><?= $pager->links() ?></div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>

