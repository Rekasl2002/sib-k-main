<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <?php echo view('reports/_print_style'); ?>
  <title>Laporan Individu Siswa</title>
</head>
<body>
  <div class="header">
    <img class="logo" src="<?= esc($school['logo']) ?>" alt="logo">
    <div class="meta">
      <div class="title">Laporan Individu Siswa</div>
      <div class="sub"><?= esc($school['name']) ?> • TA <?= esc($school['year']) ?> • Semester <?= esc($school['semester']) ?></div>
      <div class="small"><?= esc($school['address']) ?> | Telp <?= esc($school['phone']) ?> | <?= esc($school['website']) ?></div>
      <?php if ($period['from'] || $period['to']): ?>
        <div class="small">Periode: <?= esc($period['from'] ?: '...') ?> s/d <?= esc($period['to'] ?: '...') ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid">
    <div class="card" style="flex:1">
      <h4>Profil Siswa</h4>
      <table>
        <tr><th>Nama</th><td><?= esc($student['full_name'] ?? '-') ?></td></tr>
        <tr><th>NIS/NISN</th><td><?= esc(($student['nis'] ?? '') . (($student['nisn']??'') ? ' / '.$student['nisn'] : '')) ?></td></tr>
        <tr><th>Kelas</th><td><?= esc($student['class_name'] ?? '-') ?></td></tr>
        <tr><th>Tahun Ajaran</th><td><?= esc($school['year']) ?> (<?= esc($school['semester']) ?>)</td></tr>
      </table>
      <div class="stat">
        <div class="box">Total Sesi: <b><?= (int)$totalSessions ?></b></div>
        <div class="box">Total Pelanggaran: <b><?= (int)$totalViolations ?></b></div>
        <div class="box">Total Poin: <b><?= (int)$totalPoints ?></b></div>
      </div>
    </div>
  </div>

  <h4>Riwayat Sesi Konseling</h4>
  <table>
    <thead>
      <tr>
        <th>Tanggal</th><th>Konselor</th><th>Durasi (mnt)</th><th>Topik</th><th>Catatan</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($sessions): foreach ($sessions as $s): ?>
      <tr>
        <td><?= esc($s['session_date'] ?? '-') ?></td>
        <td><?= esc($s['counselor_name'] ?? '-') ?></td>
        <td class="right"><?= (int)($s['duration_minutes'] ?? 0) ?></td>
        <td><?= esc($s['topic'] ?? '-') ?></td>
        <td><?= esc($s['notes'] ?? '-') ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="5" class="center small">Belum ada data</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <h4>Riwayat Pelanggaran</h4>
  <table>
    <thead>
      <tr>
        <th>Tanggal</th><th>Kategori</th><th>Level</th><th>Deskripsi</th><th class="right">Poin</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($violations): foreach ($violations as $v): ?>
      <tr>
        <td><?= esc($v['violation_date'] ?? '-') ?></td>
        <td><?= esc($v['category_name'] ?? '-') ?></td>
        <td><?= esc($v['level'] ?? '-') ?></td>
        <td><?= esc($v['description'] ?? '-') ?></td>
        <td class="right"><?= (int)($v['points'] ?? 0) ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="5" class="center small">Belum ada data</td></tr>
    <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="4" class="right">Total Poin</th>
        <th class="right"><?= (int)$totalPoints ?></th>
      </tr>
    </tfoot>
  </table>
</body>
</html>
