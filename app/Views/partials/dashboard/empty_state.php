<?php
/**
 * Partial: tampilan "kosong" (empty state) seragam untuk panel/tabel dashboard.
 *
 * Karena argumen ke-2 $this->include() di CI4 = options (BUKAN data), kirim
 * variabel lewat $this->setData() tepat sebelum memanggil partial ini:
 *
 *   <?php $this->setData(['esIcon' => 'mdi-history', 'esText' => 'Belum ada aktivitas terbaru.']) ?>
 *   <?= $this->include('partials/dashboard/empty_state') ?>
 *
 * Param (via setData):
 *   $esIcon : nama ikon mdi tanpa prefix "mdi " (mis. 'mdi-calendar-blank-outline').
 *   $esText : kalimat keterangan.
 */
$icon = $esIcon ?? 'mdi-information-outline';
$text = $esText ?? 'Belum ada data.';
?>
<div class="text-center py-4">
  <i class="mdi <?= esc($icon) ?> text-dark font-size-48"></i>
  <p class="text-dark mt-2 mb-0"><?= esc($text) ?></p>
</div>
