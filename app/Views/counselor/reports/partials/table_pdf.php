<?php
/**
 * File Path: app/Views/counselor/reports/partials/table_pdf.php
 *
 * Counselor Reports • Table PDF
 * Dipakai oleh Counselor\ReportController::download()
 */

$title   = $title   ?? 'Laporan';
$columns = $columns ?? [];
$rows    = $rows    ?? [];

$filters     = $filters     ?? [];
$generatedAt = $generatedAt ?? date('Y-m-d H:i:s');

// Period label: utamakan $periodLabel jika dikirim, kalau tidak buat dari filters.
$periodLabel = $periodLabel ?? null;
if ($periodLabel === null) {
    $from = (string)($filters['date_from'] ?? '');
    $to   = (string)($filters['date_to'] ?? '');
    if ($from !== '' || $to !== '') {
        $periodLabel = ($from !== '' ? $from : '-') . ' s/d ' . ($to !== '' ? $to : '-');
    } else {
        $periodLabel = '-';
    }
}

// Fallback kolom jika kosong: buat berdasarkan jumlah kolom terbesar dari row pertama.
if (empty($columns)) {
    $maxCols = 1;
    foreach ($rows as $r) {
        if (is_array($r)) {
            $maxCols = max($maxCols, count($r));
        }
    }
    $columns = [];
    for ($i = 1; $i <= $maxCols; $i++) {
        $columns[] = 'Kolom ' . $i;
    }
}

$colCount = max(1, count($columns));

function cell_is_numeric($v): bool
{
    if ($v === null) return false;
    $s = trim((string)$v);
    if ($s === '') return false;

    // Angka dengan ribuan (1.234), desimal (12,5) atau kombinasi sederhana.
    // Tidak menganggap tanggal/uuid sebagai angka.
    return (bool) preg_match('/^-?\d{1,3}([.,]\d{3})*([.,]\d+)?$/', $s) || is_numeric($s);
}

function filter_label_type(?string $type): string
{
    $type = strtolower(trim((string)$type));
    return match ($type) {
        'students'           => 'Data Siswa (Binaan)',
        'sessions'           => 'Sesi Konseling',
        'violations'         => 'Pelanggaran (Binaan)',
        'assessments'        => 'Asesmen (Binaan)',
        'career'             => 'Info Karir',
        'universities'       => 'Info Perguruan Tinggi',
        'career_choices'     => 'Pilihan Karir Siswa',
        'university_choices' => 'Pilihan PT Siswa',
        default              => $type !== '' ? $type : 'sessions',
    };
}

// Susun ringkasan filter aktif (yang ada nilainya saja).
$filterLines = [];

$type = $filters['type'] ?? '';
if ($type !== '') {
    $filterLines[] = 'Jenis: ' . filter_label_type((string)$type);
}
if (!empty($filters['class_id'])) {
    $filterLines[] = 'Kelas ID: ' . esc((string)$filters['class_id']);
}
if (!empty($filters['assessment_id'])) {
    $filterLines[] = 'Asesmen ID: ' . esc((string)$filters['assessment_id']);
}
if (!empty($filters['status'])) {
    $filterLines[] = 'Status: ' . esc((string)$filters['status']);
}
if (!empty($filters['search'])) {
    $filterLines[] = 'Pencarian: ' . esc((string)$filters['search']);
}
if (!empty($filters['sort_by'])) {
    $dir = (string)($filters['sort_dir'] ?? 'asc');
    $filterLines[] = 'Sort: ' . esc((string)$filters['sort_by']) . ' (' . esc(strtoupper($dir)) . ')';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= esc($title) ?></title>
  <style>
    @page { margin: 14mm 12mm; }

    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 11.5px;
      color: #111;
    }

    .box {
      border: 1px solid #ddd;
      padding: 10px 12px;
      border-radius: 6px;
    }

    .h1 {
      font-size: 16px;
      margin: 0;
      padding: 0;
    }

    .muted {
      color: #666;
      font-size: 10.5px;
      margin-top: 4px;
      line-height: 1.35;
    }

    .meta-row {
      margin-top: 6px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      table-layout: fixed;
    }

    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }

    th, td {
      border: 1px solid #ddd;
      padding: 6px;
      vertical-align: top;
      word-wrap: break-word;
      word-break: break-word;
    }

    th {
      background: #f3f3f3;
      font-weight: bold;
    }

    tr { page-break-inside: avoid; }

    .right { text-align: right; }
    .center { text-align: center; }

    .note {
      margin-top: 10px;
      font-size: 10.5px;
      color: #666;
    }
  </style>
</head>
<body>

  <div class="box">
    <div class="h1"><?= esc($title) ?></div>

    <div class="muted meta-row">
      Periode: <b><?= esc($periodLabel) ?></b><br>
      Dibuat: <?= esc($generatedAt) ?><br>
      Total baris: <b><?= count($rows) ?></b>
      <?php if (!empty($filterLines)): ?>
        <br>Filter:
        <?= esc(implode(' • ', $filterLines)) ?>
      <?php endif; ?>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <?php foreach ($columns as $col): ?>
          <th><?= esc((string)$col) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>

    <tbody>
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $row): ?>
          <?php
            // Normalisasi row agar indexing konsisten
            $vals = is_array($row) ? array_values($row) : [(string)$row];

            // Pad/truncate supaya jumlah kolom konsisten
            if (count($vals) < $colCount) {
                $vals = array_pad($vals, $colCount, '');
            } elseif (count($vals) > $colCount) {
                $vals = array_slice($vals, 0, $colCount);
            }
          ?>
          <tr>
            <?php foreach ($vals as $cell): ?>
              <td class="<?= cell_is_numeric($cell) ? 'right' : '' ?>">
                <?= esc((string)$cell) ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="<?= $colCount ?>" class="center muted">(tidak ada data)</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="note">
    Catatan: Laporan Counselor dibatasi pada data binaan sesuai hak akses.
  </div>

</body>
</html>
