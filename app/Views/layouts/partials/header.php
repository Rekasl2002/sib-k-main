<?php

/**
 * File Path: app/Views/layouts/partials/header.php
 *
 * Header Wrapper
 * Untuk kompatibilitas: beberapa layout lama memanggil partial header.php.
 * Kita arahkan supaya selalu memakai topbar modern (layouts/partials/topbar.php),
 * sehingga tidak muncul header dobel dan tampilan konsisten.
 */

?>
<?= $this->include('layouts/partials/topbar') ?>
