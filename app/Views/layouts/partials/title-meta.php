<?php

/**
 * File Path: app/Views/layouts/partials/title-meta.php
 * 
 * Title & Meta Tags
 * Meta tags dan title untuk setiap halaman
 * 
 * @package    SIB-K
 * @subpackage Views/Layouts/Partials
 * @category   Layout
 * @author     Development Team
 * @created    2025-01-01
 */

// Pastikan helper yang diperlukan tersedia untuk base_url() dan setting()
helper(['url', 'settings']);

// Nama sekolah untuk suffix title & meta author
$schoolName = env('school.name', 'MA Persis 31 Banjaran');

// Nama aplikasi untuk fallback (kalau halaman tidak mengirim title apa pun)
$appName = setting('app_name', env('app.appName', 'SIB-K'), 'general');

// Prioritas judul tab:
// 1) $page_title  (paling umum dipakai dari controller/view)
// 2) $pageTitle   (varian penamaan yang kadang dipakai)
// 3) $title       (yang sudah ada sebelumnya)
// 4) $appName      (fallback dari setting/env)
// 5) 'Dashboard'   (fallback terakhir)
$tabTitle = $page_title
    ?? $pageTitle
    ?? $title
    ?? $appName
    ?? 'Dashboard';

// Kalau judul kosong/spasi saja, pakai fallback terakhir
$tabTitle = trim((string) $tabTitle);
if ($tabTitle === '') {
    $tabTitle = 'Dashboard';
}
?>
<meta charset="utf-8" />
<title><?= esc($tabTitle) ?> | SIB-K - <?= esc($schoolName) ?></title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta content="Sistem Informasi Bimbingan dan Konseling" name="description" />
<meta content="<?= esc($schoolName) ?>" name="author" />

<meta name="csrf-token" content="<?= csrf_hash() ?>">

<!-- App favicon -->
<link rel="icon" href="<?= base_url(setting('favicon_path', 'assets/images/favicon.ico', 'branding')) ?>" type="image/x-icon">
