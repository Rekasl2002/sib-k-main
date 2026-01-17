<?php

/**
 * File Path: app/Views/layouts/partials/vendor-scripts.php
 * 
 * Vendor Scripts
 * Load semua JavaScript libraries yang dibutuhkan
 * Dengan CDN fallback untuk file yang tidak ada
 * 
 * @package    SIB-K
 * @subpackage Views/Layouts/Partials
 * @category   Layout
 * @author     Development Team
 * @created    2025-01-06
 */
?>

<!-- JAVASCRIPT -->

<!-- jQuery (if needed by template) -->
<?php if (file_exists(FCPATH . 'assets/libs/jquery/jquery.min.js')): ?>
    <script src="<?= base_url('assets/libs/jquery/jquery.min.js') ?>"></script>
<?php else: ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php endif; ?>

<!-- Bootstrap Bundle -->
<?php if (file_exists(FCPATH . 'assets/js/bootstrap.bundle.min.js')): ?>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<?php elseif (file_exists(FCPATH . 'assets/libs/bootstrap/js/bootstrap.bundle.min.js')): ?>
    <script src="<?= base_url('assets/libs/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<?php else: ?>
    <!-- Fallback to CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>

<!-- MetisMenu (for sidebar) -->
<?php if (file_exists(FCPATH . 'assets/libs/metismenu/metisMenu.min.js')): ?>
    <script src="<?= base_url('assets/libs/metismenu/metisMenu.min.js') ?>"></script>
<?php endif; ?>

<!-- Simplebar (for scrollbar) -->
<?php if (file_exists(FCPATH . 'assets/libs/simplebar/simplebar.min.js')): ?>
    <script src="<?= base_url('assets/libs/simplebar/simplebar.min.js') ?>"></script>
<?php endif; ?>

<!-- Waves effect -->
<?php if (file_exists(FCPATH . 'assets/libs/node-waves/waves.min.js')): ?>
    <script src="<?= base_url('assets/libs/node-waves/waves.min.js') ?>"></script>
<?php endif; ?>

<!-- Waypoints (for counter animation) -->
<?php if (file_exists(FCPATH . 'assets/libs/waypoints/lib/jquery.waypoints.min.js')): ?>
    <script src="<?= base_url('assets/libs/waypoints/lib/jquery.waypoints.min.js') ?>"></script>
<?php endif; ?>

<!-- CounterUp (for number animation) -->
<?php if (file_exists(FCPATH . 'assets/libs/jquery.counterup/jquery.counterup.min.js')): ?>
    <script src="<?= base_url('assets/libs/jquery.counterup/jquery.counterup.min.js') ?>"></script>
<?php endif; ?>

<!-- Chart.js (untuk grafik di dashboard, termasuk Tren Layanan BK) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- App js -->
<script src="<?= base_url('assets/js/app.js') ?>"></script>

<!-- Custom JS -->
<script src="<?= base_url('assets/custom/js/app.js') ?>"></script>



