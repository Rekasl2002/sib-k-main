<?php
// File: app/Views/parent/reports/child_report.php

helper(['url']);

// ----------------------
// Safe defaults (anti warning Intelephense)
// ----------------------
$isPdf            = (bool) ($isPdf ?? false);
$title            = (string) ($title ?? 'Laporan Anak');

$student          = $student ?? [];
$parentName       = (string) ($parentName ?? '');
$violationSummary = $violationSummary ?? [];
$violations       = $violations ?? [];
$sessions         = $sessions ?? [];

// ----------------------
// Formatter Indonesia (tanpa Time::setLocale)
// ----------------------
if (!function_exists('fmt_date_id_short')) {
    function fmt_date_id_short($date): string
    {
        if (empty($date)) return '-';
        $ts = strtotime((string) $date);
        if (!$ts) return '-';

        // Prefer Intl (lebih akurat utk locale)
        if (class_exists(\IntlDateFormatter::class)) {
            $fmt = new \IntlDateFormatter(
                'id_ID',
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                'Asia/Jakarta',
                \IntlDateFormatter::GREGORIAN,
                'dd MMM yyyy'
            );
            $out = $fmt->format($ts);
            if ($out !== false) return (string) $out;
        }

        // Fallback jika ext-intl tidak tersedia
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        $d = (int) date('d', $ts);
        $m = (int) date('n', $ts);
        $y = date('Y', $ts);
        return sprintf('%02d %s %s', $d, ($months[$m] ?? date('M', $ts)), $y);
    }
}

if (!function_exists('fmt_time_hm')) {
    function fmt_time_hm($time): string
    {
        if (empty($time)) return '-';
        $ts = strtotime((string) $time);
        if (!$ts) return '-';
        return date('H:i', $ts);
    }
}

// ----------------------
// Data helper
// ----------------------
$studentId = (int) ($student['id'] ?? 0);

$pdfUrl = $studentId > 0
    ? base_url('parent/reports/child/' . $studentId . '?format=pdf')
    : '#';

$todayText = esc(fmt_date_id_short($today ?? date('Y-m-d')));

$printHint = 'Jika memakai Print browser: matikan opsi "Headers and footers" di dialog Print agar URL/ikon tidak ikut.';

/** Mapping sederhana (opsional) untuk gender */
$gender = (string) ($student['gender'] ?? '');
$genderLabel = $gender;
if (strtoupper($gender) === 'L') $genderLabel = 'Laki-laki';
if (strtoupper($gender) === 'P') $genderLabel = 'Perempuan';

/**
 * Renderer isi laporan (dipakai untuk mode web & mode PDF).
 * Supaya tidak duplikasi banyak HTML.
 */
$renderReportContent = static function () use (
    $student,
    $genderLabel,
    $todayText,
    $parentName,
    $violationSummary,
    $violations,
    $sessions
) {
    $summary     = $violationSummary ?? [];
    $hasViols    = !empty($violations);
    $hasSessions = !empty($sessions);
    ?>
    <div id="parent-child-report" class="my-3">
        <div class="report-card">

            <!-- Header laporan -->
            <div class="text-center mb-4 report-header">
                <h1 class="h4 mb-1">Laporan Individual Siswa</h1>
                <p class="mb-1">Untuk Orang Tua / Wali</p>
                <small class="text-muted">Dicetak: <?= $todayText ?></small>

                <?php if (!empty($parentName)): ?>
                    <div class="mt-1">
                        <small class="text-muted">Akun Orang Tua: <?= esc($parentName) ?></small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- A. Data Anak -->
            <div class="report-section mb-4">
                <div class="report-section-title">A. Data Anak</div>
                <table class="table table-sm meta-table mb-0">
                    <tbody>
                    <tr>
                        <th>Nama Lengkap</th>
                        <td><?= esc($student['full_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>NIS / NISN</th>
                        <td>
                            <?= esc($student['nis'] ?? '-') ?>
                            <?php if (!empty($student['nisn'])): ?>
                                / <?= esc($student['nisn']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td><?= esc($student['class_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Tingkat / Jurusan</th>
                        <td>
                            <?= esc($student['grade_level'] ?? '-') ?>
                            <?php if (!empty($student['major'])): ?>
                                / <?= esc($student['major']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Jenis Kelamin</th>
                        <td><?= esc($genderLabel ?: '-') ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- B. Ringkasan & Daftar Pelanggaran -->
            <div class="report-section mb-4">
                <div class="report-section-title">B. Ringkasan Pelanggaran</div>

                <table class="table table-sm meta-table mb-3">
                    <tbody>
                    <tr>
                        <th>Jumlah Pelanggaran</th>
                        <td><?= (int)($summary['total_violations'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th>Total Poin</th>
                        <td><?= (int)($summary['total_points'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th>Pelanggaran Terakhir</th>
                        <td>
                            <?php
                            $last = $summary['last_violation_date'] ?? null;
                            echo esc(fmt_date_id_short($last));
                            ?>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <h6 class="mb-2">Daftar Pelanggaran</h6>

                <?php if (!$hasViols): ?>
                    <p class="text-muted mb-0">Tidak ada data pelanggaran yang tercatat untuk anak ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                            <tr>
                                <th style="width: 90px;">Tanggal</th>
                                <th style="width: 170px;">Kategori</th>
                                <th style="width: 70px;">Poin</th>
                                <th>Uraian Singkat</th>
                                <th style="width: 160px;">Pencatat</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($violations as $v): ?>
                                <tr>
                                    <td><?= esc(fmt_date_id_short($v['violation_date'] ?? null)) ?></td>
                                    <td>
                                        <?= esc($v['category_name'] ?? '-') ?><br>
                                        <small class="text-muted">Tingkat: <?= esc($v['category_severity'] ?? '-') ?></small>
                                    </td>
                                    <td><?= (int)($v['point_deduction'] ?? 0) ?></td>
                                    <td><?= esc($v['description'] ?? '-') ?></td>
                                    <td><?= esc($v['recorder_name'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- C. Ringkasan Sesi Konseling -->
            <div class="report-section mb-4">
                <div class="report-section-title">C. Ringkasan Sesi Konseling</div>

                <?php if (!$hasSessions): ?>
                    <p class="text-muted mb-0">Belum ada sesi konseling yang tercatat untuk anak ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead>
                            <tr>
                                <th style="width: 90px;">Tanggal</th>
                                <th style="width: 70px;">Waktu</th>
                                <th style="width: 95px;">Jenis</th>
                                <th>Topik / Fokus</th>
                                <th style="width: 130px;">Lokasi</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 160px;">Guru BK</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><?= esc(fmt_date_id_short($s['session_date'] ?? null)) ?></td>
                                    <td><?= esc(fmt_time_hm($s['session_time'] ?? null)) ?></td>
                                    <td><?= esc($s['session_type'] ?? '-') ?></td>
                                    <td><?= esc($s['topic'] ?? '-') ?></td>
                                    <td><?= esc($s['location'] ?? '-') ?></td>
                                    <td><?= esc($s['status'] ?? '-') ?></td>
                                    <td><?= esc($s['counselor_name'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <?php
};
?>

<?php if ($isPdf): ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($title) ?></title>
    <style>
        @page { size: A4; margin: 1.5cm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        #parent-child-report { max-width: 100%; }
        .report-card { border: 1px solid #e5e5e5; border-radius: 8px; padding: 18px; }
        .report-header { margin-bottom: 14px; text-align: center; }
        .h4 { font-size: 16px; margin: 0 0 4px 0; }
        .text-muted { color: #6c757d; }
        .report-section { page-break-inside: avoid; margin-bottom: 14px; }
        .report-section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #6c757d; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d9d9d9; padding: 6px; vertical-align: top; }
        th { background: #f6f6f6; text-align: left; }
        .meta-table th { width: 35%; }
        .table-sm th, .table-sm td { padding: 5px; }
    </style>
</head>
<body>
<?php $renderReportContent(); ?>
</body>
</html>

<?php else: ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    #parent-child-report { max-width: 900px; margin: 0 auto; }

    #parent-child-report .report-card {
        background: #ffffff;
        border-radius: .5rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,.06);
        padding: 2rem;
    }

    #parent-child-report .report-section-title {
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #6c757d;
        margin-bottom: .75rem;
    }

    #parent-child-report table th,
    #parent-child-report table td { font-size: .875rem; vertical-align: top; }

    #parent-child-report .meta-table th { width: 35%; font-weight: 500; }

    .btn-print-report { margin-bottom: 1rem; }

    @media print {
        #page-topbar, .vertical-menu, .footer, .right-bar, .page-title-box,
        .btn-print-report, .btn-download-pdf, .print-hint {
            display: none !important;
        }

        body { background: #ffffff !important; }

        .main-content, .page-content, .container-fluid {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }

        #parent-child-report {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }

        #parent-child-report .report-card {
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 1.5cm !important;
        }

        @page { size: A4; margin: 1.5cm; }
        .report-section { page-break-inside: avoid; }
        a[href]:after { content: ""; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">LIHAT/CETAK LAPORAN ANAK</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('parent/reports/children') ?>">Laporan</a></li>
                    <li class="breadcrumb-item active">Lihat/Cetak</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-end gap-2 my-2">
    <!-- Download PDF (Solusi 2) -->
    <a href="<?= esc($pdfUrl) ?>"
       class="btn btn-primary btn-sm btn-download-pdf"
       rel="noopener">
        <i class="mdi mdi-file-pdf-box me-1"></i> Download PDF
    </a>
</div>

<?php $renderReportContent(); ?>

<?= $this->endSection() ?>

<?php endif; ?>
