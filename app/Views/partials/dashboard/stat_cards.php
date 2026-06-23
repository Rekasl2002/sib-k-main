<?php
/**
 * Partial: baris kartu statistik kecil dashboard (gaya "mini-stat" Admin =
 * patokan desain). Konsisten untuk SEMUA peran.
 *
 * Param $cards: list dari [
 *   'label'     => string,
 *   'value'     => int|string,
 *   'icon'      => string (kelas ikon lengkap, mis. 'mdi mdi-account-group'),
 *   'color'     => string (primary|success|info|warning|danger|secondary),
 *   'url'       => string (opsional, tujuan saat panah diklik),
 *   'link_text' => string (opsional, label kecil di kaki kartu),
 * ]
 *
 * Tata letak:
 * - Semua kartu MUAT dalam SATU baris pada layar lebar (col-xl auto: berbagi
 *   lebar sama rata, berapa pun jumlahnya), turun ke 2 kolom di tablet & 1 di HP.
 * - Tinggi kartu SERAGAM (d-flex + flex-fill), apa pun panjang teksnya.
 * - Ikon di kiri (lebar tetap) + konten di kanan (label, angka, link) sejajar.
 * - Label di-clamp 2 baris; teks link dipotong 1 baris (ellipsis) agar kartu
 *   tidak memanjang ke bawah; teks penuh tetap muncul saat di-hover (title).
 */
$cards = is_array($cards ?? null) ? $cards : [];
?>
<style>
  .mini-stat .mini-stat-icon {
    width: 56px; height: 56px;
    display: flex; align-items: center; justify-content: center;
    border-radius: .35rem;
    background: rgba(255, 255, 255, .15);
  }
  .mini-stat .mini-stat-label {
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden; min-height: 38px;
  }
  .mini-stat .mini-stat-link { min-width: 0; }
</style>
<div class="row">
  <?php foreach ($cards as $card): ?>
    <?php
      $color = $card['color'] ?? 'primary';
      $label = (string) ($card['label'] ?? '-');
      $link  = (string) ($card['link_text'] ?? '');
      $url   = (string) ($card['url'] ?? '#');
    ?>
    <div class="col-xl col-md-6 d-flex">
      <div class="card mini-stat bg-<?= esc($color) ?> text-white flex-fill">
        <div class="card-body d-flex align-items-start">
          <div class="mini-stat-icon flex-shrink-0 me-3">
            <i class="<?= esc($card['icon'] ?? 'mdi mdi-chart-box') ?> font-size-30"></i>
          </div>
          <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
            <h5 class="font-size-14 text-uppercase text-white opacity-75 mb-1 mini-stat-label" title="<?= esc($label) ?>"><?= esc($label) ?></h5>
            <h4 class="fw-semibold font-size-24 mb-0 text-white">
              <?= number_format((int) ($card['value'] ?? 0), 0, ',', '.') ?>
            </h4>
            <div class="mt-auto pt-2">
              <a href="<?= esc($url) ?>" class="text-white opacity-75 d-flex align-items-center mini-stat-link"<?= $link !== '' ? ' title="' . esc($link, 'attr') . '"' : '' ?>>
                <span class="flex-grow-1 text-truncate"><?= $link !== '' ? esc($link) : '&nbsp;' ?></span>
                <i class="mdi mdi-arrow-right h5 mb-0 ms-2 flex-shrink-0"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
