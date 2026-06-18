<?php
/**
 * File Path: app/Views/homeroom_teacher/reports/partials/sections_preview.php
 * Wali Kelas • Pratinjau Laporan multi-fitur (AJAX partial) — garis besar aman.
 */
$sections    = $sections ?? [];
$periodLabel = $periodLabel ?? null;
?>
<div class="alert alert-info py-2 small mb-3"><i class="fas fa-shield-alt me-1"></i> Catatan rahasia konseling &amp; skor asesmen individu tidak ditampilkan (sesuai aturan kerahasiaan).</div>

<?php if (!empty($periodLabel)): ?>
  <div class="text-dark small mb-3"><i class="far fa-calendar-alt me-1"></i> Periode: <b><?= esc($periodLabel) ?></b></div>
<?php endif; ?>

<?php if (empty($sections)): ?>
  <div class="alert alert-warning mb-0">Belum ada jenis laporan yang dipilih. Centang minimal satu pada <b>Jenis Laporan</b>.</div>
<?php else: ?>
  <?php foreach ($sections as $sec): ?>
    <?php $cols = $sec['columns'] ?? []; $rows = $sec['rows'] ?? []; ?>
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-semibold"><?= esc($sec['title'] ?? 'Laporan') ?></h6>
        <span class="badge bg-light text-dark border">Baris: <?= (int) count($rows) ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><?php foreach ($cols as $c): ?><th class="text-uppercase small"><?= esc((string) $c) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): ?>
              <?php foreach ($rows as $row): ?>
                <tr><?php foreach ((array) $row as $cell): ?><td><?= esc(is_scalar($cell) ? (string) $cell : json_encode($cell, JSON_UNESCAPED_UNICODE)) ?></td><?php endforeach; ?></tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="<?= max(1, count($cols)) ?>" class="text-center text-dark">(tidak ada data)</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
