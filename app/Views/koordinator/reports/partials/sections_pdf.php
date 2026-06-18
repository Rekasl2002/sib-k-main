<?php
/**
 * File Path: app/Views/koordinator/reports/partials/sections_pdf.php
 * Koordinator BK • PDF Laporan multi-fitur (server-side, Dompdf)
 */
$sections    = $sections    ?? [];
$reportTitle = $reportTitle ?? 'Laporan BK (Koordinator BK)';
$schoolName  = $schoolName  ?? '';
$periodLabel = $periodLabel ?? '-';
$scopeLabel  = $scopeLabel  ?? '';
$generatedAt = $generatedAt ?? date('Y-m-d H:i:s');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= esc($reportTitle) ?></title>
  <style>
    @page { margin: 14mm 12mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
    .box { border: 1px solid #ddd; padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; }
    .h1 { font-size: 16px; margin: 0; font-weight: bold; }
    .muted { color: #666; font-size: 10.5px; margin-top: 4px; line-height: 1.4; }
    .section { margin-top: 12px; }
    .section-title { font-weight: bold; font-size: 12px; margin: 0 0 6px 0; padding: 4px 0; border-bottom: 1px solid #ccc; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    thead { display: table-header-group; }
    th, td { border: 1px solid #ddd; padding: 5px 6px; vertical-align: top; word-wrap: break-word; word-break: break-word; font-size: 10.5px; }
    th { background: #f3f3f3; font-weight: bold; }
    tr { page-break-inside: avoid; }
    .empty { color: #888; font-style: italic; }
  </style>
</head>
<body>
  <div class="box">
    <div class="h1"><?= esc($reportTitle) ?></div>
    <div class="muted">
      <?php if ($schoolName !== ''): ?><?= esc($schoolName) ?><br><?php endif; ?>
      Periode: <b><?= esc($periodLabel) ?></b><br>
      <?php if ($scopeLabel !== ''): ?>Lingkup: <?= esc($scopeLabel) ?><br><?php endif; ?>
      Dibuat: <?= esc($generatedAt) ?>
    </div>
  </div>

  <?php if (empty($sections)): ?>
    <div class="empty">Tidak ada jenis laporan yang dipilih.</div>
  <?php else: ?>
    <?php foreach ($sections as $sec): ?>
      <?php $cols = $sec['columns'] ?? []; $rows = $sec['rows'] ?? []; ?>
      <div class="section">
        <div class="section-title"><?= esc($sec['title'] ?? 'Laporan') ?> (<?= (int) count($rows) ?> baris)</div>
        <table>
          <thead><tr><?php foreach ($cols as $c): ?><th><?= esc((string) $c) ?></th><?php endforeach; ?></tr></thead>
          <tbody>
            <?php if (!empty($rows)): ?>
              <?php foreach ($rows as $row): ?>
                <tr><?php foreach ((array) $row as $cell): ?><td><?= esc(is_scalar($cell) ? (string) $cell : json_encode($cell, JSON_UNESCAPED_UNICODE)) ?></td><?php endforeach; ?></tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="<?= max(1, count($cols)) ?>" class="empty">(tidak ada data)</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
