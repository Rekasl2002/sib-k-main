<?php
/**
 * File Path: app/Views/parent/reports/child_report_pdf.php
 * Parent • Child Report PDF (server-side)
 */

$student          = $student          ?? [];
$violations       = $violations       ?? [];
$sessions         = $sessions         ?? [];
$violationSummary = $violationSummary ?? [];
$today            = $today            ?? date('Y-m-d');
$title            = $title            ?? 'Laporan Anak';
$parentName       = $parentName       ?? '';

if (!function_exists('pdf_fmt_date_id')) {
    function pdf_fmt_date_id($date, string $fallback = '-'): string
    {
        if (empty($date)) return $fallback;

        $ts = strtotime((string) $date);
        if (!$ts) return $fallback;

        $out = date('d M Y', $ts);
        $map = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
            'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agu',
            'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des',
        ];
        return strtr($out, $map);
    }
}
if (!function_exists('pdf_fmt_time')) {
    function pdf_fmt_time($time, string $fallback = '-'): string
    {
        if (empty($time)) return $fallback;
        $ts = strtotime((string) $time);
        if (!$ts) return $fallback;
        return date('H:i', $ts);
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= esc($title) ?></title>
  <style>
    @page { margin: 18mm 15mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; margin:0; padding:0; }
    .report-header { text-align:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #ddd; }
    .report-header h4 { margin:0 0 4px 0; font-size:15px; }
    .report-meta { font-size:10px; color:#444; line-height:1.35; }
    .section { margin-top:10px; page-break-inside:avoid; }
    .section-title { font-weight:bold; margin-bottom:6px; font-size:11px; text-transform:uppercase; color:#333; }
    table { width:100%; border-collapse:collapse; table-layout:fixed; }
    th, td { border:1px solid #bbb; padding:6px; vertical-align:top; word-wrap:break-word; overflow-wrap:break-word; }
    th { background:#f2f2f2; font-weight:bold; }
    .meta-table th { width:32%; }
    .muted { color:#666; }
    tr { page-break-inside: avoid; }
    .small { font-size:10px; }
  </style>
</head>
<body>

  <div class="report-header">
    <h4>Laporan Individual Siswa (Orang Tua/Wali)</h4>
    <div class="report-meta">
      Dicetak: <?= esc(pdf_fmt_date_id($today)) ?><br>
      <?php if (!empty($parentName)): ?>
        Akun Orang Tua: <?= esc($parentName) ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="section">
    <div class="section-title">A. Data Anak</div>
    <table class="meta-table">
      <tr><th>Nama Lengkap</th><td><?= esc($student['full_name'] ?? '-') ?></td></tr>
      <tr>
        <th>NIS / NISN</th>
        <td>
          <?= esc($student['nis'] ?? '-') ?>
          <?php if (!empty($student['nisn'])): ?> / <?= esc($student['nisn']) ?><?php endif; ?>
        </td>
      </tr>
      <tr><th>Kelas</th><td><?= esc($student['class_name'] ?? '-') ?></td></tr>
      <tr>
        <th>Tingkat / Jurusan</th>
        <td>
          <?= esc($student['grade_level'] ?? '-') ?>
          <?php if (!empty($student['major'])): ?> / <?= esc($student['major']) ?><?php endif; ?>
        </td>
      </tr>
      <tr><th>Jenis Kelamin</th><td><?= esc($student['gender'] ?? '-') ?></td></tr>
    </table>
  </div>

  <div class="section">
    <div class="section-title">B. Ringkasan Pelanggaran</div>

    <table class="meta-table" style="margin-bottom:8px;">
      <tr><th>Jumlah Pelanggaran</th><td><?= (int)($violationSummary['total_violations'] ?? 0) ?></td></tr>
      <tr><th>Total Poin</th><td><?= (int)($violationSummary['total_points'] ?? 0) ?></td></tr>
      <tr><th>Pelanggaran Terakhir</th><td><?= esc(pdf_fmt_date_id($violationSummary['last_violation_date'] ?? null)) ?></td></tr>
    </table>

    <?php if (empty($violations)): ?>
      <div class="muted">Tidak ada data pelanggaran yang tercatat untuk anak ini.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th style="width:78px;">Tanggal</th>
            <th style="width:150px;">Kategori</th>
            <th style="width:52px;">Poin</th>
            <th>Uraian Singkat</th>
            <th style="width:120px;">Pencatat</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($violations as $v): ?>
            <tr>
              <td><?= esc(pdf_fmt_date_id($v['violation_date'] ?? null)) ?></td>
              <td>
                <?= esc($v['category_name'] ?? '-') ?><br>
                <span class="muted small">Tingkat: <?= esc($v['category_severity'] ?? '-') ?></span>
              </td>
              <td><?= (int)($v['point_deduction'] ?? 0) ?></td>
              <td><?= esc($v['description'] ?? '-') ?></td>
              <td><?= esc($v['recorder_name'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="section">
    <div class="section-title">C. Ringkasan Sesi Konseling</div>

    <?php if (empty($sessions)): ?>
      <div class="muted">Belum ada sesi konseling yang tercatat untuk anak ini.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th style="width:78px;">Tanggal</th>
            <th style="width:52px;">Waktu</th>
            <th style="width:78px;">Jenis</th>
            <th>Topik / Fokus</th>
            <th style="width:95px;">Lokasi</th>
            <th style="width:78px;">Status</th>
            <th style="width:120px;">Guru BK</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sessions as $s): ?>
            <tr>
              <td><?= esc(pdf_fmt_date_id($s['session_date'] ?? null)) ?></td>
              <td><?= esc(pdf_fmt_time($s['session_time'] ?? null)) ?></td>
              <td><?= esc($s['session_type'] ?? '-') ?></td>
              <td><?= esc($s['topic'] ?? '-') ?></td>
              <td><?= esc($s['location'] ?? '-') ?></td>
              <td><?= esc($s['status'] ?? '-') ?></td>
              <td><?= esc($s['counselor_name'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</body>
</html>
