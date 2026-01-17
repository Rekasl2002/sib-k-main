<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Tulis Pesan</h5></div>
  <div class="card-body">
    <form method="post" action="<?= site_url('messages/send') ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Kepada</label>
        <select name="to[]" class="form-select" multiple required>
          <?php foreach ($recipients as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= esc($u['full_name'] ?: $u['email']) ?></option>
          <?php endforeach; ?>
        </select>
        <small class="text-muted">Tahan Ctrl/Command untuk multi pilih.</small>
      </div>
      <div class="mb-3">
        <label class="form-label">Subjek</label>
        <input type="text" class="form-control" name="subject" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Pesan</label>
        <textarea class="form-control" name="body" rows="6"></textarea>
      </div>
      <div class="d-flex justify-content-end gap-2">
        <a href="<?= site_url('messages/inbox') ?>" class="btn btn-light">Batal</a>
        <button class="btn btn-primary">Kirim</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
