<?php
/**
 * Partial: baris kartu statistik kecil dashboard (gaya "mini-stat" Admin =
 * patokan desain). Konsisten untuk semua peran.
 *
 * Param $cards: list dari [
 *   'label'     => string,
 *   'value'     => int|string,
 *   'icon'      => string (kelas ikon lengkap, mis. 'mdi mdi-account-group'),
 *   'color'     => string (primary|success|info|warning|danger|secondary),
 *   'url'       => string (opsional, tujuan saat panah diklik),
 *   'link_text' => string (opsional, label kecil di kaki kartu),
 * ]
 * Param $col (opsional): kelas kolom, default 'col-xl-3 col-md-6'.
 */
$cards = is_array($cards ?? null) ? $cards : [];
$col   = $col ?? 'col-xl-3 col-md-6';
?>
<div class="row">
  <?php foreach ($cards as $card): ?>
    <div class="<?= esc($col) ?>">
      <div class="card mini-stat bg-<?= esc($card['color'] ?? 'primary') ?> text-white">
        <div class="card-body">
          <div class="mb-4">
            <div class="float-start mini-stat-img me-4">
              <i class="<?= esc($card['icon'] ?? 'mdi mdi-chart-box') ?> font-size-40"></i>
            </div>
            <h5 class="font-size-16 text-uppercase text-white-50"><?= esc($card['label'] ?? '-') ?></h5>
            <h4 class="fw-medium font-size-24 mb-0">
              <?= number_format((int) ($card['value'] ?? 0), 0, ',', '.') ?>
            </h4>
          </div>
          <div class="pt-2">
            <?php if (! empty($card['url'])): ?>
              <div class="float-end">
                <a href="<?= esc($card['url'], 'attr') ?>" class="text-white-50">
                  <i class="mdi mdi-arrow-right h5"></i>
                </a>
              </div>
            <?php endif; ?>
            <p class="text-white-50 mb-0 mt-1"><?= ! empty($card['link_text']) ? esc($card['link_text']) : '&nbsp;' ?></p>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
