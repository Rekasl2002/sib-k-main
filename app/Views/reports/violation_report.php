<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <?php echo view('reports/_print_style'); ?>
  <title>Laporan Pelanggaran</title>
</head>
<body>
  <div class="header">
    <img class="logo" src="<?= esc($school['logo']) ?>" alt="logo">
    <div class="meta">
      <div class="title">Laporan Pelanggaran</div>
      <div class="sub"><?= esc($school['name']) ?> • Periode: <?= esc($period['from'] ?: '...') ?> s/d <?= esc($period['to'] ?: '...') ?></div>
      <div class="small">Total kasus: <?= (int)$total ?> • Total poin: <?= (int)$totalPoints ?></div>
    </div>
  </div>

  <h4>Agregat per Kategori</h4>
  <table>
    <thead><tr><th>Kategori</th><th class="right">Jumlah</th><th class="right">Total Poin</th></tr></thead>
    <tbody>
    <?php if ($perCategory): foreach ($perCategory as $name => $agg): ?>
      <tr>
        <td><?= esc($name) ?></td>
        <td class="right"><?= (int)$agg['count'] ?></td>
        <td class="right"><?= (int)$agg['points'] ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="3" class="center small">Tidak ada data</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <h4>Daftar Pelanggaran</h4>
  <table>
    <thead>
      <tr>
        <th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Kategori</th><th>Level</th><th>Deskripsi</th><th class="right">Poin</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($rows): foreach ($rows as $r): ?>
      <tr>
        <td><?= esc($r['violation_date'] ?? '-') ?></td>
        <td><?= esc($r['student_name'] ?? '-') ?></td>
        <td><?= esc($r['class_name'] ?? '-') ?></td>
        <td><?= esc($r['category_name'] ?? '-') ?></td>
        <td><?= esc($r['level'] ?? '-') ?></td>
        <td><?= esc($r['description'] ?? '-') ?></td>
        <td class="right"><?= (int)($r['points'] ?? 0) ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="7" class="center small">Tidak ada data</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
