<?php

/**
 * File Path: app/Views/layouts/partials/right-sidebar.php
 * 
 * Right Sidebar
 * Settings panel untuk theme customization
 * 
 * @package    SIB-K
 * @subpackage Views/Layouts/Partials
 * @category   Layout
 * @author     Development Team
 * @created    2025-01-01
 */
?>
<!-- Right Sidebar -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-body rightbar">
        <div class="right-bar">
            <div data-simplebar class="h-100">
                <div class="rightbar-title px-3 py-4">
                    <a href="javascript:void(0);" class="right-bar-toggle float-end" data-bs-dismiss="offcanvas" aria-label="Close">
                        <i class="mdi mdi-close noti-icon"></i>
                    </a>
                    <h5 class="m-0">Pengaturan Tampilan</h5>
                </div>

                <!-- Settings -->
                <hr class="mt-0" />
                <h6 class="text-center mb-0">Pilih Layout</h6>

                <div class="p-4">
                    <div class="mb-2">
                        <img src="<?= base_url('assets/images/layouts/layout-1.jpg') ?>" class="img-thumbnail" alt="Light Mode">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input theme-choice" id="light-mode-switch" checked />
                        <label class="form-check-label" for="light-mode-switch">Light Mode</label>
                    </div>

                    <div class="mb-2">
                        <img src="<?= base_url('assets/images/layouts/layout-2.jpg') ?>" class="img-thumbnail" alt="Dark Mode">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input theme-choice" id="dark-mode-switch" />
                        <label class="form-check-label" for="dark-mode-switch">Dark Mode</label>
                    </div>

                    <div class="mb-2">
                        <img src="<?= base_url('assets/images/layouts/layout-3.jpg') ?>" class="img-thumbnail" alt="RTL Mode">
                    </div>
                    <div class="form-check form-switch mb-5">
                        <input type="checkbox" class="form-check-input theme-choice" id="rtl-mode-switch"
                            data-appStyle="<?= base_url('assets/css/app-rtl.min.css') ?>" />
                        <label class="form-check-label" for="rtl-mode-switch">RTL Mode</label>
                    </div>

                </div>

            </div>
            <!-- end slimscroll-menu-->
        </div>
    </div>
</div>
<!-- /Right-bar -->

<!-- Right bar overlay-->
<div class="rightbar-overlay"></div>