<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <?php echo view('reports/_print_style'); ?>
  <title>Ringkasan Sesi Konseling</title>
</head>
<body>
  <div class="header">
    <img class="logo" src="<?= esc($school['logo']) ?>" alt="logo">
    <div class="meta">
      <div class="title">Ringkasan Sesi Konseling</div>
      <div class="sub"><?= esc($school['name']) ?> • Periode: <?= esc($period['from'] ?: '...') ?> s/d <?= esc($period['to'] ?: '...') ?></div>
      <div class="small"><?= esc($school['address']) ?> | <?= esc($school['website']) ?></div>
    </div>
  </div>

  <div class="stat">
    <div class="box">Total Sesi: <b><?= (int)$total ?></b></div>
    <div class="box">Total Durasi: <b><?= (int)$totalDuration ?> menit</b></div>
  </div>

  <h4>Agregat per Konselor</h4>
  <table>
    <thead><tr><th>Konselor</th><th class="right">Jumlah Sesi</th><th class="right">Durasi (mnt)</th></tr></thead>
    <tbody>
    <?php if ($perCounselor): foreach ($perCounselor as $c): ?>
      <tr>
        <td><?= esc($c['counselor_name'] ?? '-') ?></td>
        <td class="right"><?= (int)$c['count'] ?></td>
        <td class="right"><?= (int)$c['duration'] ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="3" class="center small">Tidak ada data</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <h4>Daftar Sesi</h4>
  <table>
    <thead>
      <tr>
        <th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Konselor</th><th>Durasi</th><th>Topik</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($rows): foreach ($rows as $r): ?>
      <tr>
        <td><?= esc($r['session_date'] ?? '-') ?></td>
        <td><?= esc($r['student_name'] ?? '-') ?></td>
        <td><?= esc($r['class_name'] ?? '-') ?></td>
        <td><?= esc($r['counselor_name'] ?? '-') ?></td>
        <td class="right"><?= (int)($r['duration_minutes'] ?? 0) ?></td>
        <td><?= esc($r['topic'] ?? '-') ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="6" class="center small">Tidak ada data</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
