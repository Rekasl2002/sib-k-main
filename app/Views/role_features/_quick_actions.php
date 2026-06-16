<?php
/**
 * File: app/Views/role_features/_quick_actions.php
 * Partial kartu "Akses Cepat" (shortcut) untuk dashboard peran read-only
 * (Siswa, Orang Tua, Wali Kelas). Fase 7: dashboard difokuskan pada jadwal
 * mendatang + tombol cepat, tanpa daftar/listing panjang.
 *
 * Variabel:
 * - $quickShortcuts: list of ['label','url','icon','color'(opsional)]
 * - $quickTitle (opsional)
 */
$quickShortcuts = is_array($quickShortcuts ?? null) ? $quickShortcuts : [];
$quickTitle = (string) ($quickTitle ?? 'Akses Cepat');
if (! $quickShortcuts) { return; }
?>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h6 class="mb-3 text-dark"><i class="mdi mdi-lightning-bolt-outline me-1"></i><?= esc($quickTitle) ?></h6>
    <div class="row g-2">
      <?php foreach ($quickShortcuts as $s): ?>
        <?php $color = $s['color'] ?? 'primary'; ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="<?= esc($s['url'] ?? '#', 'attr') ?>" class="btn btn-soft-<?= esc($color, 'attr') ?> w-100 text-start d-flex align-items-center">
            <i class="mdi <?= esc($s['icon'] ?? 'mdi-arrow-right', 'attr') ?> font-size-20 me-2"></i>
            <span class="text-dark"><?= esc($s['label'] ?? '-') ?></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
