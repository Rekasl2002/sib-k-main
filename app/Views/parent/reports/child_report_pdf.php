<?php
/**
 * File Path: app/Views/parent/reports/child_report_pdf.php
 * Parent • Child Report PDF (server-side)
 */

$student          = $student          ?? [];
$careers          = $careers          ?? [];
$universities     = $universities     ?? [];
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
    <h4>Laporan Anak (Orang Tua/Wali)</h4>
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
        <th>NIK / NISN</th>
        <td>
          <?= esc(($student['nik'] ?? '-') . ' / ' . ($student['nisn'] ?? '-')) ?>
        </td>
      </tr>
      <tr><th>Umur</th><td><?= esc(student_age_text($student['birth_date'] ?? null)) ?></td></tr>
      <tr><th>Kebutuhan Khusus</th><td><?= esc($student['special_needs'] ?? '-') ?></td></tr>
      <tr><th>Disabilitas</th><td><?= esc($student['disability'] ?? '-') ?></td></tr>
      <tr><th>Nomor KIP/PIP</th><td><?= esc($student['kip_pip_number'] ?? '-') ?></td></tr>
      <tr><th>Nama Ayah Kandung</th><td><?= esc($student['father_name'] ?? '-') ?></td></tr>
      <tr><th>Nama Ibu Kandung</th><td><?= esc($student['mother_name'] ?? '-') ?></td></tr>
      <tr><th>Nama Wali</th><td><?= esc($student['guardian_name'] ?? '-') ?></td></tr>
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
    <div class="section-title">B. Pilihan Karier yang Disimpan Anak</div>

    <?php if (empty($careers)): ?>
      <div class="muted">Belum ada pilihan karier yang disimpan oleh anak ini.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Karier</th>
            <th style="width:140px;">Sektor</th>
            <th style="width:130px;">Min. Pendidikan</th>
            <th style="width:85px;">Disimpan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($careers as $c): ?>
            <tr>
              <td><?= esc($c['title'] ?? '-') ?></td>
              <td><?= esc($c['sector'] ?? '-') ?></td>
              <td><?= esc($c['min_education'] ?? '-') ?></td>
              <td><?= esc(pdf_fmt_date_id($c['saved_at'] ?? null)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="section">
    <div class="section-title">C. Pilihan Studi Lanjut yang Disimpan Anak</div>

    <?php if (empty($universities)): ?>
      <div class="muted">Belum ada pilihan studi lanjut yang disimpan oleh anak ini.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Perguruan Tinggi</th>
            <th style="width:90px;">Akreditasi</th>
            <th style="width:140px;">Lokasi</th>
            <th style="width:85px;">Disimpan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($universities as $u): ?>
            <tr>
              <td><?= esc($u['university_name'] ?? '-') ?><?= !empty($u['alias']) ? ' (' . esc($u['alias']) . ')' : '' ?></td>
              <td><?= esc($u['accreditation'] ?? '-') ?></td>
              <td><?= esc($u['location'] ?? '-') ?></td>
              <td><?= esc(pdf_fmt_date_id($u['saved_at'] ?? null)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</body>
</html>
