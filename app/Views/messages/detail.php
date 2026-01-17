<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?= esc($msg['subject']) ?></h5>
    <small class="text-muted">Dikirim: <?= esc($msg['created_at']) ?></small>
  </div>
  <div class="card-body">
    <div class="mb-3"><?= $msg['body'] ?></div>
    <hr>
    <form method="post" action="<?= site_url('messages/reply/'.$msg['id']) ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Balasan</label>
        <textarea class="form-control" name="body" rows="4" required></textarea>
      </div>
      <button class="btn btn-primary btn-sm">Kirim Balasan</button>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
