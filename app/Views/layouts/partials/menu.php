<?php

/**
 * File Path: app/Views/layouts/partials/menu.php
 *
 * Menu Integration
 * Mengintegrasikan topbar dan sidebar menu
 */

?>

<a class="visually-hidden-focusable" href="#page-content">Lewati ke konten</a>

<div class="sibk-nav">
  <?= $this->include('layouts/partials/topbar') ?>
  <?= $this->include('layouts/partials/sidebar') ?>
</div>

<!-- Backdrop untuk sidebar (khusus mobile/offcanvas) -->
<div class="sibk-sidebar-backdrop" aria-hidden="true"></div>
