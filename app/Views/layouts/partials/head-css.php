<?php
/**
 * File Path: app/Views/layouts/partials/head-css.php
 */
helper('settings');
?>
<!-- Bootstrap Css -->
<link href="<?= base_url('assets/css/bootstrap.min.css') ?>" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="<?= base_url('assets/css/icons.min.css') ?>" rel="stylesheet" type="text/css" />
<!-- App Css -->
<link href="<?= base_url('assets/css/app.min.css') ?>" id="app-style" rel="stylesheet" type="text/css" />
<!-- Custom Css (if exists) -->
<?php if (file_exists(FCPATH . 'assets/custom/css/app.css')): ?>
  <link href="<?= base_url('assets/custom/css/app.css') ?>" rel="stylesheet" type="text/css" />
<?php endif; ?>

<!-- App favicon -->
<link rel="shortcut icon" id="favicon" href="<?= base_url(setting('favicon_path', 'assets/images/favicon.ico', 'branding')) ?>">
