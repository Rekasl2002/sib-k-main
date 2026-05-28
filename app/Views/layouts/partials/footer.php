<?php

/**
 * File Path: app/Views/layouts/partials/footer.php
 * 
 * Footer
 * Footer halaman dengan copyright dan informasi
 * 
 * @package    SIB-K
 * @subpackage Views/Layouts/Partials
 * @category   Layout
 * @author     Development Team
 * @created    2025-01-01
 */
?>
<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <?= date('Y') ?> ©
                <?= setting('app_name', env('app.appName'), 'general') ?>
                <?= setting('school_name', env('school.name'), 'general') ?>
                <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development'): ?>
                    <span class="ms-2 text-warning fw-semibold">Aplikasi dalam Tahap Pembuatan/Pengembangan</span>
                <?php endif; ?>
            </div>
            <div class="col-sm-6">
                <div class="text-sm-end d-none d-sm-block">
                    Sistem Informasi Bimbingan dan Konseling
                </div>
            </div>
        </div>
    </div>
</footer>
