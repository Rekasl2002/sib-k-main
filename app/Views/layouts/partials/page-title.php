<?php

/**
 * File Path: app/Views/layouts/partials/page-title.php
 *
 * Page Title + Breadcrumb (SIBK themed)
 * - Ditampilkan sebagai "card" paling atas (rapi seperti header section)
 * - Tetap mendukung $pageTitle dan $breadcrumbs dari controller
 * - Fallback breadcrumb jika create_breadcrumb() tidak tersedia
 */

$__hasTitle = isset($pageTitle) && $pageTitle !== null && $pageTitle !== '';
$__hasCrumb = isset($breadcrumbs) && is_array($breadcrumbs) && !empty($breadcrumbs);

// Kalau tidak ada title & breadcrumb, tidak render apa-apa
if (!$__hasTitle && !$__hasCrumb) {
    return;
}

$__title = $pageTitle ?? 'Dashboard';

// Fallback: buat breadcrumb sederhana jika helper tidak tersedia
$__renderBreadcrumb = function (array $crumbs): string {
    // Format yang umum: [ ['label'=>'SIB-K','url'=>'/'], ['label'=>'Dashboard'] ]
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">';

    foreach ($crumbs as $i => $c) {
        $label = '';
        $url   = '';

        if (is_array($c)) {
            $label = (string)($c['label'] ?? $c['title'] ?? $c['name'] ?? '');
            $url   = (string)($c['url'] ?? $c['href'] ?? '');
        } elseif (is_string($c)) {
            $label = $c;
        }

        $isLast = ($i === array_key_last($crumbs));

        if ($isLast || !$url) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . esc($label) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . esc($url) . '">' . esc($label) . '</a></li>';
        }
    }

    $html .= '</ol></nav>';
    return $html;
};
?>

<div class="row">
    <div class="col-12">

        <!-- Card header paling atas -->
        <div class="card sibk-pagehead mb-3">
            <div class="card-body py-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">

                    <!-- Left: Title -->
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0"><?= esc($__title) ?></h4>
                    </div>

                    <!-- Right: Breadcrumb -->
                    <?php if ($__hasCrumb): ?>
                        <div class="page-title-right">
                            <?php if (function_exists('create_breadcrumb')): ?>
                                <?= create_breadcrumb($breadcrumbs) ?>
                            <?php else: ?>
                                <?= $__renderBreadcrumb($breadcrumbs) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>
