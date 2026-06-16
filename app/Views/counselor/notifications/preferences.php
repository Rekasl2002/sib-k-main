<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/counselor/notifications/preferences.php
 * Fitur: Notifikasi Internal.
 * Peran/izin: Guru BK memakai tampilan mandiri; logika data tetap dari controller per peran dan service terkait.
 */
?>
<?php
$basePath = trim((string)($basePath ?? ''), '/');
$types = is_array($types ?? null) ? $types : [];
$preferences = is_array($preferences ?? null) ? $preferences : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Preferensi Notifikasi Internal</h4>
    <small class="text-muted"><?= esc($roleLabel ?? 'Pengguna') ?></small>
  </div>
  <a href="<?= site_url($basePath . '/notifications') ?>" class="btn btn-light btn-sm">Daftar Notifikasi</a>
</div>

<?= show_alerts() ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= site_url($basePath . '/notifications/preferences') ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <?php foreach ($types as $type => $label): ?>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input
                class="form-check-input"
                type="checkbox"
                id="notif_<?= esc($type) ?>"
                name="enabled[<?= esc($type) ?>]"
                value="1"
                <?= !empty($preferences[$type]) ? 'checked' : '' ?>>
              <label class="form-check-label" for="notif_<?= esc($type) ?>"><?= esc($label) ?></label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">Simpan Preferensi</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
