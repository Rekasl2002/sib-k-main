<?php // app/Views/parent/career/university_detail.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('rowa')) {
  function rowa($r): array {
    return is_array($r) ? $r : (is_object($r) ? (array) $r : []);
  }
}
if (!function_exists('vu')) {
  function vu($r, $k, $d = '') {
    $a = rowa($r);
    return esc($a[$k] ?? $d);
  }
}

$u         = rowa($university ?? []);
$faculties = $faculties ?? [];
$programs  = $programs ?? [];

$isPublic  = (int) ($u['is_public'] ?? 0) === 1;
$accr      = $u['accreditation'] ?? '';
$loc       = $u['location'] ?? '';
$website   = $u['website'] ?? '';
$logo      = $u['logo'] ?? null;
$creatorName = $u['created_by_name'] ?? null;
$backUrl   = $backUrl ?? site_url('parent/career?tab=universities');
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm mb-3">
        <div class="card-body d-flex gap-3 align-items-start">
          <?php if ($logo): ?>
            <div class="flex-shrink-0">
              <div class="border rounded p-2 bg-white" style="width:96px;height:96px;display:flex;align-items:center;justify-content:center;">
                <img
                  src="<?= esc($logo) ?>"
                  alt="Logo <?= vu($u, 'university_name') ?>"
                  style="max-width:100%;max-height:100%;object-fit:contain;"
                >
              </div>
            </div>
          <?php endif; ?>

          <div class="flex-grow-1">
            <h4 class="mb-1">
              <?= vu($u, 'university_name', 'Nama perguruan tinggi') ?>
              <?php if (!empty($u['alias'])): ?>
                <span class="text-muted fs-6"> (<?= esc($u['alias']) ?>)</span>
              <?php endif; ?>
            </h4>

            <?php if ($creatorName): ?>
              <div class="small text-muted mb-1">
                Dibagikan oleh <?= esc($creatorName) ?>
              </div>
            <?php endif; ?>

            <div class="mb-2">
              <?php if ($accr): ?>
                <span class="badge bg-secondary me-1">
                  Akreditasi: <?= esc($accr) ?>
                </span>
              <?php endif; ?>
              <?php if ($loc): ?>
                <span class="badge bg-light text-body border me-1">
                  <?= esc($loc) ?>
                </span>
              <?php endif; ?>
              <span class="badge bg-info-subtle text-info-emphasis border">
                <?= $isPublic ? 'Perguruan Tinggi Negeri' : 'Perguruan Tinggi Swasta' ?>
              </span>
            </div>

            <?php if ($website): ?>
              <div class="mb-1">
                Website:
                <a href="<?= esc($website) ?>" target="_blank" rel="noopener">
                  <?= esc($website) ?>
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Profil Singkat</h5>
        </div>
        <div class="card-body">
          <p class="mb-0">
            <?= nl2br(esc($u['description'] ?? 'Belum ada deskripsi yang diinput oleh Guru BK.')) ?>
          </p>
        </div>
      </div>

      <?php if (!empty($faculties) || !empty($programs)): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-header">
            <h5 class="mb-0">Fakultas & Program Studi</h5>
          </div>
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
                    <li><?= esc(is_array($p) ? ($p['name'] ?? json_encode($p)) : $p) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Informasi Penerimaan & Biaya</h5>
        </div>
        <div class="card-body">
          <dl class="mb-0">
            <dt>Informasi Penerimaan</dt>
            <dd><?= nl2br(esc($u['admission_info'] ?? 'Belum ada informasi penerimaan.')) ?></dd>

            <dt class="mt-2">Kisaran Biaya Kuliah</dt>
            <dd><?= nl2br(esc($u['tuition_range'] ?? 'Belum ada informasi biaya.')) ?></dd>

            <dt class="mt-2">Beasiswa</dt>
            <dd><?= nl2br(esc($u['scholarships'] ?? 'Belum ada informasi beasiswa.')) ?></dd>
          </dl>
        </div>
      </div>

      <div class="mb-3">
        <a href="<?= esc($backUrl) ?>" class="btn btn-light">
          Kembali ke daftar perguruan tinggi
        </a>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h6 class="mb-0">Kontak & Informasi Tambahan</h6>
        </div>
        <div class="card-body">
          <p class="mb-2">
            <?= nl2br(esc($u['contacts'] ?? 'Belum ada informasi kontak yang diinput.')) ?>
          </p>

          <p class="text-muted small mb-0">
            Informasi di halaman ini hanya sebagai referensi awal.
            Untuk data resmi dan terbaru, selalu cek website resmi perguruan tinggi.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
