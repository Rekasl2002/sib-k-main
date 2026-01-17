<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Notifikasi</h5></div>
  <ul class="list-group list-group-flush">
    <?php foreach ($items as $n): ?>
      <li class="list-group-item <?= $n['is_read'] ? '' : 'bg-light' ?>">
        <a href="<?= esc($n['link'] ?? '#') ?>" class="fw-semibold"><?= esc($n['title']) ?></a>
        <?php if(!empty($n['message'])): ?><div class="text-muted"><?= esc($n['message']) ?></div><?php endif; ?>
        <small class="text-muted"><?= esc($n['created_at']) ?></small>
      </li>
    <?php endforeach; ?>
  </ul>
  <div class="card-footer"><?= $pager->links() ?></div>
</div>

<?= $this->endSection() ?>
