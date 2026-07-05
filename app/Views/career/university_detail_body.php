<?php
/**
 * File Path: app/Views/career/university_detail_body.php
 *
 * Badan halaman DETAIL Perguruan Tinggi — dipakai bersama SEMUA peran agar konsisten.
 *
 * Variabel:
 * - $university (array) data university_info (boleh berisi created_by_name)
 * - $backUrl    (string) URL tombol "Kembali"
 */

$u       = is_array($university ?? null) ? $university : (array) ($university ?? []);
$backUrl = $backUrl ?? site_url();

$faculties = $u['faculties'] ?? [];
$faculties = is_array($faculties) ? $faculties : (json_decode((string) $faculties, true) ?: []);

$programs = $u['programs'] ?? [];
$programs = is_array($programs) ? $programs : (json_decode((string) $programs, true) ?: []);

$scholarships = $u['scholarships'] ?? [];
$scholarships = is_array($scholarships) ? $scholarships : (json_decode((string) $scholarships, true) ?: []);

$contacts = $u['contacts'] ?? [];
$contacts = is_array($contacts) ? $contacts : (json_decode((string) $contacts, true) ?: []);

$accr    = trim((string) ($u['accreditation'] ?? ''));
$loc     = trim((string) ($u['location'] ?? ''));
$website = trim((string) ($u['website'] ?? ''));
$logo    = $u['logo'] ?? null;
$logoSrc = $logo ? (preg_match('~^https?://~', (string) $logo) ? $logo : base_url($logo)) : null;
$isActive    = (int) ($u['is_active'] ?? 0) === 1;
$creatorName = trim((string) ($u['created_by_name'] ?? ''));

// Helper render list/teks dari nilai yang mungkin string biasa atau array
$renderText = static function ($val): string {
    if (is_array($val)) {
        $parts = [];
        foreach ($val as $v) {
            $parts[] = is_array($v) ? implode(' — ', array_filter($v)) : (string) $v;
        }
        return esc(implode("\n", array_filter($parts)));
    }
    return nl2br(esc((string) $val));
};
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Detail Perguruan Tinggi</h4>
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
    <div class="col-12 col-lg-8">
        <!-- Ringkasan -->
        <div class="card">
            <div class="card-body d-flex gap-3 align-items-start">
                <?php if ($logoSrc): ?>
                    <div class="flex-shrink-0 border rounded p-2 bg-white" style="width:88px;height:88px;display:flex;align-items:center;justify-content:center;">
                        <img src="<?= esc($logoSrc, 'attr') ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                    </div>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <h4 class="mb-1">
                        <?= esc($u['university_name'] ?? 'Nama Perguruan Tinggi') ?>
                        <?php if (!empty($u['alias'])): ?>
                            <span class="text-muted fs-6">(<?= esc($u['alias']) ?>)</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($creatorName !== ''): ?>
                        <div class="small text-muted mb-2">Dibagikan oleh <?= esc($creatorName) ?></div>
                    <?php endif; ?>
                    <div class="mb-1">
                        <?php if ($accr !== ''): ?>
                            <span class="badge bg-secondary">Akreditasi: <?= esc($accr) ?></span>
                        <?php endif; ?>
                        <?php if ($loc !== ''): ?>
                            <span class="badge bg-info"><?= esc($loc) ?></span>
                        <?php endif; ?>
                        <span class="badge <?= $isActive ? 'bg-success' : 'bg-dark' ?>">
                            <?= $isActive ? 'Ditampilkan ke Siswa' : 'Disembunyikan dari Siswa' ?>
                        </span>
                    </div>
                    <?php if ($website !== ''): ?>
                        <div class="mt-1">Website:
                            <a href="<?= esc($website, 'attr') ?>" target="_blank" rel="noopener"><?= esc($website) ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profil -->
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Profil Singkat</h5></div>
            <div class="card-body"><p class="mb-0"><?= nl2br(esc($u['description'] ?? 'Belum ada deskripsi.')) ?></p></div>
        </div>

        <?php if (!empty($faculties) || !empty($programs)): ?>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Fakultas &amp; Program Studi</h5></div>
                <div class="card-body row">
                    <?php if (!empty($faculties)): ?>
                        <div class="col-12 col-md-6 mb-2">
                            <h6>Fakultas</h6>
                            <ul class="mb-0">
                                <?php foreach ($faculties as $f): ?>
                                    <li><?= esc(is_array($f) ? ($f['name'] ?? json_encode($f)) : $f) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($programs)): ?>
                        <div class="col-12 col-md-6 mb-2">
                            <h6>Program Studi</h6>
                            <ul class="mb-0">
                                <?php foreach ($programs as $p): ?>
                                    <?php
                                        $nm  = is_array($p) ? ($p['name'] ?? '') : $p;
                                        $deg = is_array($p) ? ($p['degree'] ?? '') : '';
                                    ?>
                                    <li><?= esc($nm) ?><?= $deg !== '' ? ' (' . esc($deg) . ')' : '' ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Penerimaan & Biaya -->
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Informasi Penerimaan &amp; Biaya</h5></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Informasi Penerimaan</dt>
                    <dd><?= !empty($u['admission_info']) ? nl2br(esc($u['admission_info'])) : 'Belum ada informasi.' ?></dd>
                    <dt class="mt-2">Kisaran Biaya Studi Lanjut</dt>
                    <dd><?= !empty($u['tuition_range']) ? nl2br(esc($u['tuition_range'])) : 'Belum ada informasi.' ?></dd>
                    <dt class="mt-2">Beasiswa</dt>
                    <dd><?= !empty($scholarships) ? $renderText($scholarships) : 'Belum ada informasi.' ?></dd>
                </dl>
            </div>
        </div>

        <div class="mb-3">
            <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">Kontak &amp; Informasi Tambahan</h6></div>
            <div class="card-body">
                <p class="mb-2"><?= !empty($contacts) ? $renderText($contacts) : 'Belum ada informasi kontak.' ?></p>
                <p class="text-muted small mb-0">
                    Informasi ini hanya referensi awal. Untuk data resmi &amp; terbaru, cek situs resmi perguruan tinggi.
                </p>
            </div>
        </div>
    </div>
</div>
