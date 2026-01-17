<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <?php echo view('reports/_print_style'); ?>
  <title>Agregat Kelas</title>
</head>
<body>
  <div class="header">
    <img class="logo" src="<?= esc($school['logo']) ?>" alt="logo">
    <div class="meta">
      <div class="title">Agregat Kelas: <?= esc($class['class_name'] ?? "ID #{$class['id']}") ?></div>
      <div class="sub"><?= esc($school['name']) ?> • Periode: <?= esc($period['from'] ?: '...') ?> s/d <?= esc($period['to'] ?: '...') ?></div>
      <div class="small">Siswa: <?= (int)$studentCount ?> • Sesi: <?= (int)$sessionCount ?> • Pelanggaran: <?= (int)$violationCount ?></div>
    </div>
  </div>

  <h4>Agregat per Kategori Pelanggaran</h4>
  <table>
    <thead><tr><th>Kategori</th><th class="right">Jumlah</th></tr></thead>
    <tbody>
    <?php if ($perCategory): foreach ($perCategory as $k => $v): ?>
      <tr><td><?= esc($k) ?></td><td class="right"><?= (int)$v ?></td></tr>
    <?php endforeach; else: ?>
      <tr><td colspan="2" class="center small">Tidak ada data</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <h4>Daftar Sesi (ringkas)</h4>
  <table>
    <thead><tr><th>Tanggal</th><th>ID Siswa</th><th>Durasi</th><th>Topik</th></tr></thead>
    <tbody>
    <?php if ($sessions): foreach ($sessions as $s): ?>
      <tr>
        <td><?= esc($s['session_date'] ?? '-') ?></td>
        <td><?= esc($s['student_id'] ?? '-') ?></td>
        <td class="right"><?= (int)($s['duration_minutes'] ?? 0) ?></td>
        <td><?= esc($s['topic'] ?? '-') ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="4" class="center small">Tidak ada data</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
