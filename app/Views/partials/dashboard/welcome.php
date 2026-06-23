<?php
/**
 * Partial: Welcome Card dashboard (konsisten semua peran).
 * Param $welcome: [
 *   'name'       => string,
 *   'role_label' => string,
 *   'ay'         => string (tahun ajaran, boleh kosong),
 *   'sem'        => string (semester, boleh kosong),
 *   'desc'       => string,
 *   'shortcuts'  => list<['label'=>, 'url'=>, 'icon'=>]> (maks 3),
 * ]
 */
$welcome   = is_array($welcome ?? null) ? $welcome : [];
$name      = $welcome['name'] ?? 'Pengguna';
$roleLabel = $welcome['role_label'] ?? '';
$ay        = trim((string) ($welcome['ay'] ?? ''));
$sem       = trim((string) ($welcome['sem'] ?? ''));
$desc      = $welcome['desc'] ?? '';
$shortcuts = array_slice($welcome['shortcuts'] ?? [], 0, 3);
?>
<div class="row mb-3">
  <div class="col-12">
    <div class="card welcome-card">
      <div class="card-body">
        <div class="row align-items-center g-3">
          <div class="col-md-7">
            <h4 class="text-white mb-2">Selamat Datang, <?= esc($name) ?>!</h4>
            <p class="text-white-50 mb-2">
              Anda login sebagai <strong><?= esc($roleLabel) ?></strong>
              <?php if ($ay !== '' && $sem !== ''): ?>
                <span class="ms-1">• Tahun Ajaran <?= esc($ay) ?> Semester <?= esc($sem) ?></span>
              <?php elseif ($ay !== ''): ?>
                <span class="ms-1">• Tahun Ajaran <?= esc($ay) ?></span>
              <?php endif; ?>
            </p>
            <?php if ($desc !== ''): ?>
              <p class="text-white-50 mb-0"><?= esc($desc) ?></p>
            <?php endif; ?>
          </div>
          <?php if (! empty($shortcuts)): ?>
            <div class="col-md-5">
              <div class="d-grid gap-2">
                <?php foreach ($shortcuts as $s): ?>
                  <a href="<?= esc($s['url'] ?? '#', 'attr') ?>" class="btn btn-light">
                    <i class="mdi <?= esc($s['icon'] ?? 'mdi-chevron-right') ?> me-1"></i> <?= esc($s['label'] ?? '-') ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
