<?php // app/Views/parent/career/detail.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('rowa')) {
  function rowa($r): array {
    return is_array($r) ? $r : (is_object($r) ? (array) $r : []);
  }
}
if (!function_exists('vc')) {
  function vc($r, $k, $d = '') {
    $a = rowa($r);
    return esc($a[$k] ?? $d);
  }
}

$career       = rowa($career ?? []);
$skills       = $skills ?? [];
$links        = $links ?? [];
$related      = $related ?? [];
$universities = $universities ?? [];

$sector  = $career['sector'] ?? '';
$edu     = $career['min_education'] ?? '';
$demand  = $career['demand_level'] ?? null;
$thumb   = $career['thumbnail'] ?? ($career['image'] ?? null);
$salary  = $career['avg_salary_idr'] ?? null;
$pathway = $career['pathways'] ?? null;
$creatorName = $career['created_by_name'] ?? null;
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="card shadow-sm mb-3">
        <div class="card-body d-flex gap-3 align-items-start">
          <?php if ($thumb): ?>
            <div class="flex-shrink-0">
              <div class="border rounded bg-white" style="width:120px;height:120px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                <img
                  src="<?= esc($thumb) ?>"
                  alt="Gambar <?= vc($career, 'title') ?>"
                  style="max-width:100%;max-height:100%;object-fit:cover;"
                >
              </div>
            </div>
          <?php endif; ?>

          <div class="flex-grow-1">
            <h4 class="mb-1"><?= vc($career, 'title', 'Judul karier') ?></h4>

            <?php if ($creatorName): ?>
              <div class="small text-muted mb-1">
                Dibagikan oleh <?= esc($creatorName) ?>
              </div>
            <?php endif; ?>

            <div class="mb-2">
              <?php if ($sector): ?>
                <span class="badge bg-light text-body border me-1">
                  Sektor: <?= esc($sector) ?>
                </span>
              <?php endif; ?>
              <?php if ($edu): ?>
                <span class="badge bg-secondary me-1">
                  Min Edukasi: <?= esc($edu) ?>
                </span>
              <?php endif; ?>
              <?php if ($demand !== null && $demand !== ''): ?>
                <span class="badge bg-info me-1">
                  Demand: <?= esc((string) $demand) ?>/10
                </span>
              <?php endif; ?>
              <?php if ($salary !== null && $salary !== ''): ?>
                <span class="badge bg-success">
                  Perkiraan gaji: Rp <?= esc(number_format((float) $salary, 0, ',', '.')) ?>/bulan
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Deskripsi Pekerjaan</h5>
        </div>
        <div class="card-body">
          <p class="mb-0">
            <?= nl2br(esc($career['description'] ?? 'Belum ada deskripsi lengkap yang diinput oleh Guru BK.')) ?>
          </p>
        </div>
      </div>

      <?php if (!empty($pathway)): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-header">
            <h5 class="mb-0">Jalur Pengembangan / Pathway</h5>
          </div>
          <div class="card-body">
            <p class="mb-0">
              <?= nl2br(esc($pathway)) ?>
            </p>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($skills)): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-header">
            <h5 class="mb-0">Kemampuan yang Dibutuhkan</h5>
          </div>
          <div class="card-body">
            <?php foreach ($skills as $sk): ?>
              <span class="badge bg-light text-body border me-1 mb-1">
                <?= esc(is_array($sk) ? ($sk['name'] ?? json_encode($sk)) : $sk) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($links)): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-header">
            <h5 class="mb-0">Sumber Referensi</h5>
          </div>
          <div class="card-body">
            <ul class="mb-0">
              <?php foreach ($links as $lk): ?>
                <?php
                  $url   = is_array($lk) ? ($lk['url'] ?? '') : $lk;
                  $label = is_array($lk) ? ($lk['label'] ?? $url) : $url;
                ?>
                <?php if ($url): ?>
                  <li>
                    <a href="<?= esc($url) ?>" target="_blank" rel="noopener">
                      <?= esc($label) ?>
                    </a>
                  </li>
                <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($related)): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Karier Terkait</h5>
            <span class="small text-muted">Masih dalam sektor yang sama</span>
          </div>
          <div class="card-body">
            <div class="row g-2">
              <?php foreach ($related as $relRaw): ?>
                <?php $rel = rowa($relRaw); ?>
                <div class="col-12 col-md-6">
                  <a href="<?= site_url('parent/career/' . (int) ($rel['id'] ?? 0)) ?>"
                     class="text-decoration-none d-block border rounded p-2 h-100">
                    <div class="fw-semibold mb-1">
                      <?= esc($rel['title'] ?? 'Tanpa judul') ?>
                    </div>
                    <div class="small text-muted">
                      <?= esc($rel['sector'] ?? '-') ?>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <a href="<?= site_url('parent/career') ?>" class="btn btn-light">
          Kembali ke daftar karir
        </a>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
