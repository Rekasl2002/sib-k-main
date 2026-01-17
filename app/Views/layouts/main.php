<!doctype html>
<html lang="id">
<head>
    <?php
    /**
     * File Path: app/Views/layouts/main.php
     *
     * Main Layout Template (Qovex -> SIBK themed)
     * - Menggabungkan partials (title-meta, head-css, body, menu, footer, vendor-scripts)
     * - Menyediakan renderSection: styles, content, scripts, modals
     * - Theme CSS (app.css) dipasang paling akhir untuk override vendor & fixes
     */
    ?>

    <?= $this->include('layouts/partials/title-meta') ?>
    <?= $this->include('layouts/partials/head-css') ?>

    <!-- Global fixes harus setelah head-css agar override vendor jika perlu -->
    <link rel="stylesheet" href="<?= base_url('assets/css/fixes.css') ?>">

    <!-- Theme CSS paling akhir agar override fixes.css & vendor -->
    <link rel="stylesheet" href="<?= base_url('assets/custom/css/app.css') ?>?v=<?= @filemtime(FCPATH . 'assets/custom/css/app.css') ?>">

    <!-- Styles khusus halaman (letakkan setelah theme agar bisa override theme) -->
    <?= $this->renderSection('styles') ?>

    <!-- Cegah sidebar collapsed tersimpan (jalankan sedini mungkin, sebelum app.js membaca state) -->
    <script>
      (function () {
        try {
          localStorage.removeItem('vertical-collpsed');
          localStorage.removeItem('sidebar-vertical-collpsed');
        } catch (e) {}
      })();
    </script>
</head>

<?= $this->include('layouts/partials/body') ?>

<!-- Begin page -->
<div id="layout-wrapper">

    <?= $this->include('layouts/partials/menu') ?>

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">

        <div class="page-content">

            <div class="container-fluid sibk-container" id="page-content">
                <?= $this->renderSection('content') ?>
            </div>
            <!-- container-fluid -->

        </div>
        <!-- End Page-content -->

        <?= $this->include('layouts/partials/footer') ?>

    </div>
    <!-- end main content-->

</div>
<!-- END layout-wrapper -->

<?= $this->include('layouts/partials/right-sidebar') ?>

<?= $this->include('layouts/partials/vendor-scripts') ?>

<!-- App js -->
<script src="<?= base_url('assets/js/app.js') ?>"></script>

<!-- SIBK Sidebar toggle (HARUS setelah app.js) -->
<script src="<?= base_url('assets/custom/js/sibk-sidebar-toggle.js') ?>?v=<?= @filemtime(FCPATH . 'assets/custom/js/sibk-sidebar-toggle.js') ?>"></script>


<!-- Scripts khusus halaman -->
<?= $this->renderSection('scripts') ?>

<!-- Tempatkan SEMUA modal di sini agar tidak kena stacking context/z-index wrapper -->
<?= $this->renderSection('modals') ?>

<script>
(function () {
  // Saat mouse wheel di atas input number yg fokus, lepas fokus agar tidak stepUp/stepDown
  document.addEventListener('wheel', function (e) {
    const el = document.activeElement;
    if (el && el.tagName === 'INPUT' && el.type === 'number' && el.contains(e.target)) {
      el.blur();
    }
  }, { passive: true });
})();

document.addEventListener('DOMContentLoaded', function () {
  // Pastikan sidebar terbuka saat awal load
  document.body.classList.remove('vertical-collpsed');
  try {
    localStorage.removeItem('vertical-collpsed');
    localStorage.removeItem('sidebar-vertical-collpsed');
  } catch (e) {}
});
</script>

</body>
</html>
