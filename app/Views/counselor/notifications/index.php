<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/counselor/notifications/index.php
 * Fitur: Notifikasi Internal.
 * Peran/izin: Guru BK memakai tampilan mandiri; logika data tetap dari controller per peran dan service terkait.
 */
?>
<?php $basePath = trim((string)($basePath ?? ''), '/'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Notifikasi Internal</h4>
    <small class="text-muted"><?= esc($roleLabel ?? 'Pengguna') ?></small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url($basePath . '/notifications/preferences') ?>" class="btn btn-outline-primary btn-sm">Preferensi</a>
    <form method="post" action="<?= site_url($basePath . '/notifications/mark-all-read') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-light btn-sm">Tandai Semua Dibaca</button>
    </form>
  </div>
</div>

<?= show_alerts() ?>

<div class="card">
  <div class="list-group list-group-flush">
    <?php if (empty($items)): ?>
      <div class="p-4 text-center text-muted">Belum ada notifikasi.</div>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
        <?php $link = $item['link'] ?? '#'; ?>
        <div class="list-group-item <?= !empty($item['is_read']) ? '' : 'bg-light' ?>">
          <div class="d-flex justify-content-between gap-3">
            <a href="<?= esc($link ?: '#') ?>" class="fw-semibold"><?= esc($item['title'] ?? '-') ?></a>
            <small class="text-muted text-nowrap"><?= esc($item['created_at'] ?? '') ?></small>
          </div>
          <div class="text-muted"><?= esc($item['message'] ?? '') ?></div>
          <div class="mt-2 d-flex gap-2">
            <form method="post" action="<?= site_url($basePath . '/notifications/mark-read/' . (int)$item['id']) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline-primary btn-sm">Dibaca</button>
            </form>
            <form method="post" action="<?= site_url($basePath . '/notifications/delete/' . (int)$item['id']) ?>" onsubmit="return confirm('Hapus notifikasi ini?')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php if (!empty($pager)): ?>
    <div class="card-footer"><?= $pager->links() ?></div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
