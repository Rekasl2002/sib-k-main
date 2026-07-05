<?php
/**
 * File Path: app/Views/career/career_detail_body.php
 *
 * Badan halaman DETAIL Karier — dipakai bersama SEMUA peran
 * (Koordinator, Guru BK, Wali Kelas, Siswa, Orang Tua) agar tampilannya konsisten.
 *
 * Variabel:
 * - $career  (array) data career_options (boleh berisi created_by_name)
 * - $backUrl (string) URL tombol "Kembali"
 *
 * Catatan: partial ini TIDAK meng-extend layout; disertakan dari view per peran
 * yang sudah meng-extend layouts/main.
 */

$career  = is_array($career ?? null) ? $career : (array) ($career ?? []);
$backUrl = $backUrl ?? site_url();

// Decode JSON bila perlu
$skills = $career['required_skills'] ?? [];
$skills = is_array($skills) ? $skills : (json_decode((string) $skills, true) ?: []);

$links = $career['external_links'] ?? [];
$links = is_array($links) ? $links : (json_decode((string) $links, true) ?: []);

$sector  = trim((string) ($career['sector'] ?? ''));
$edu     = trim((string) ($career['min_education'] ?? ''));
$demand  = $career['demand_level'] ?? null;
$salary  = $career['avg_salary_idr'] ?? null;
$pathway = trim((string) ($career['pathways'] ?? ''));
$isActive    = (int) ($career['is_active'] ?? 0) === 1;
$creatorName = trim((string) ($career['created_by_name'] ?? ''));
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Detail Karier</h4>
            <div class="page-title-right d-flex align-items-center flex-wrap gap-3">
                <?php if (! empty($crumbs) && is_array($crumbs)): ?>
                    <ol class="breadcrumb m-0">
                        <?php foreach (array_values($crumbs) as $ci => $c): ?>
                            <?php if (! empty($c['url']) && $ci < count($crumbs) - 1): ?>
                                <li class="breadcrumb-item"><a href="<?= esc($c['url'], 'attr') ?>"><?= esc($c['label'] ?? '') ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active"><?= esc($c['label'] ?? '') ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
                <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-secondary btn-sm">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-9">
        <!-- Ringkasan -->
        <div class="card">
            <div class="card-body">
                <h4 class="mb-1"><?= esc($career['title'] ?? 'Judul Karier') ?></h4>
                <?php if ($creatorName !== ''): ?>
                    <div class="small text-muted mb-2">Dibagikan oleh <?= esc($creatorName) ?></div>
                <?php endif; ?>
                <div class="mb-0">
                    <?php if ($sector !== ''): ?>
                        <span class="badge bg-info">Sektor: <?= esc($sector) ?></span>
                    <?php endif; ?>
                    <?php if ($edu !== ''): ?>
                        <span class="badge bg-secondary">Pendidikan Min.: <?= esc($edu) ?></span>
                    <?php endif; ?>
                    <?php if ($demand !== null && $demand !== ''): ?>
                        <span class="badge bg-primary">Permintaan: <?= esc((string) $demand) ?>/10</span>
                    <?php endif; ?>
                    <?php if ($salary !== null && $salary !== ''): ?>
                        <span class="badge bg-success">Perkiraan Gaji: Rp <?= esc(number_format((float) $salary, 0, ',', '.')) ?>/bulan</span>
                    <?php endif; ?>
                    <span class="badge <?= $isActive ? 'bg-success' : 'bg-dark' ?>">
                        <?= $isActive ? 'Ditampilkan ke Siswa' : 'Disembunyikan dari Siswa' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Deskripsi -->
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Deskripsi / Gambaran Pekerjaan</h5></div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(esc($career['description'] ?? 'Belum ada deskripsi.')) ?></p>
            </div>
        </div>

        <?php if ($pathway !== ''): ?>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Jalur Pengembangan / Pathway</h5></div>
                <div class="card-body"><p class="mb-0"><?= nl2br(esc($pathway)) ?></p></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($skills)): ?>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Keahlian yang Disarankan</h5></div>
                <div class="card-body">
                    <?php foreach ($skills as $sk): ?>
                        <span class="badge bg-light text-dark border me-1 mb-1">
                            <?= esc(is_array($sk) ? ($sk['name'] ?? json_encode($sk)) : $sk) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($links)): ?>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Sumber Referensi</h5></div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php foreach ($links as $lk): ?>
                            <?php
                                $url   = is_array($lk) ? ($lk['url'] ?? '') : $lk;
                                $label = is_array($lk) ? ($lk['label'] ?? $url) : $url;
                            ?>
                            <?php if (!empty($url)): ?>
                                <li><a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener"><?= esc($label) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</div>
