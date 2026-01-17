<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Terkirim</h5></div>
  <div class="list-group list-group-flush">
    <?php if (empty($rows)): ?>
      <div class="p-3 text-muted">Belum ada pesan terkirim.</div>
    <?php else: foreach ($rows as $row): ?>
      <a class="list-group-item list-group-item-action" href="<?= site_url('messages/detail/'.$row['id']) ?>">
        <div class="d-flex justify-content-between">
          <span><?= esc($row['subject']) ?></span>
          <small class="text-muted"><?= esc($row['created_at']) ?></small>
        </div>
        <div class="text-truncate text-muted"><?= esc(strip_tags($row['body'])) ?></div>
      </a>
    <?php endforeach; endif; ?>
  </div>
  <div class="card-footer"><?= $pager->links() ?></div>
</div>

<?= $this->endSection() ?>
