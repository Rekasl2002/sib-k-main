<?php // app/Views/parent/career/explore.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('rowa')) {
  function rowa($r): array {
    return is_array($r) ? $r : (is_object($r) ? (array) $r : []);
  }
}
if (!function_exists('v')) {
  function v($r, $k, $d = '') {
    $a = rowa($r);
    return esc($a[$k] ?? $d);
  }
}
if (!function_exists('clip')) {
  function clip($s, $len = 140) {
    $s = (string) ($s ?? '');
    if (mb_strlen($s) <= $len) {
      return esc($s);
    }
    return esc(mb_substr($s, 0, $len - 3) . '...');
  }
}
if (!function_exists('pagerInfo')) {
  function pagerInfo($pager, string $group = 'default'): ?array
  {
    if (!$pager) {
      return null;
    }
    $total = $pager->getTotal($group);
    if ($total < 1) {
      return null;
    }
    $perPage = $pager->getPerPage($group);
    $current = $pager->getCurrentPage($group);
    $from    = ($current - 1) * $perPage + 1;
    $to      = min($current * $perPage, $total);
    return [$from, $to, $total];
  }
}

$filters            = $filters            ?? [];
$careers            = $careers            ?? [];
$sectors            = $sectors            ?? [];
$educs              = $educs              ?? [];
$universities       = $universities       ?? [];
$savedIds           = $savedIds           ?? [];
$savedCareerIds     = $savedCareerIds     ?? $savedIds;
$savedUniversityIds = $savedUniversityIds ?? [];

$uniFilters   = $uniFilters   ?? [];
$uniLocations = $uniLocations ?? [];
$uniAccrs     = $uniAccrs     ?? [];

$req        = service('request');
$activeTab  = $activeTab ?? ($req->getGet('tab') ?: 'careers');
if (!in_array($activeTab, ['careers', 'universities'], true)) {
  $activeTab = 'careers';
}

// Data anak
$children      = $children      ?? [];
$activeChildId = $activeChildId ?? null;
?>

<div class="container-fluid">
  <!-- Page Title -->
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="mb-0">Info Karier dan Studi Lanjut</h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Orang Tua</a></li>
            <li class="breadcrumb-item active">Info Karier dan Studi Lanjut</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="mdi mdi-check-circle me-2"></i><?= esc(session()->getFlashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Kartu Statistik -->
  <div class="row">
    <?php
      $miniCards = [
        ['label' => 'Total Pilihan Karier',       'value' => isset($pager) ? $pager->getTotal() : count($careers),                'bg' => 'bg-primary',   'icon' => 'mdi-briefcase-outline'],
        ['label' => 'Karier Tersimpan Anak',      'value' => count($savedCareerIds),                                              'bg' => 'bg-success',   'icon' => 'mdi-bookmark-check-outline'],
        ['label' => 'Total Perguruan Tinggi',     'value' => isset($uniPager) ? $uniPager->getTotal('universities') : count($universities), 'bg' => 'bg-info',       'icon' => 'mdi-town-hall'],
        ['label' => 'PT Tersimpan Anak',          'value' => count($savedUniversityIds),                                          'bg' => 'bg-secondary', 'icon' => 'mdi-bookmark-check-outline'],
      ];
    ?>
    <?php foreach ($miniCards as $mc): ?>
      <div class="col-6 col-md-3">
        <div class="card mini-stats-wid">
          <div class="card-body">
            <div class="d-flex">
              <div class="flex-grow-1">
                <p class="text-dark fw-medium mb-2"><?= esc($mc['label']) ?></p>
                <h4 class="mb-0 text-dark"><?= number_format((int) $mc['value']) ?></h4>
              </div>
              <div class="flex-shrink-0 align-self-center">
                <div class="mini-stat-icon avatar-sm rounded-circle <?= esc($mc['bg'], 'attr') ?>">
                  <span class="avatar-title"><i class="mdi <?= esc($mc['icon'], 'attr') ?> font-size-24"></i></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Kontrol Anak + Tabs -->
  <div class="card">
    <div class="card-body pb-0">
      <p class="text-dark mb-3">
        Orang tua dapat melihat informasi karier dan perguruan tinggi yang dikurasi Guru BK
        sebagai bahan diskusi dengan anak.
      </p>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <!-- Dropdown anak aktif -->
          <form method="get" action="<?= site_url('parent/career') ?>" class="d-flex align-items-center gap-2">
            <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">
            <label class="small mb-0">Lihat pilihan untuk</label>
            <select name="child_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php if (empty($children)): ?>
                <option value="">(Belum ada anak terhubung)</option>
              <?php else: ?>
                <?php foreach ($children as $chRaw): ?>
                  <?php $ch = rowa($chRaw); $cid = (int)($ch['id'] ?? 0); ?>
                  <option value="<?= $cid ?>" <?= $cid === (int)$activeChildId ? 'selected' : '' ?>>
                    <?= esc($ch['full_name'] ?? ('Anak #' . $cid)) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </form>

          <a href="<?= site_url('parent/career/saved' . ($activeChildId ? '?child_id=' . (int)$activeChildId : '')) ?>" class="btn btn-outline-primary btn-sm">
            <i class="mdi mdi-bookmark-outline me-1"></i>Item Tersimpan Anak
          </a>
        </div>
      </div>

      <ul class="nav nav-tabs mb-0">
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'careers' ? 'active' : '') ?>"
             href="<?= site_url('parent/career?tab=careers' . ($activeChildId ? '&child_id=' . (int)$activeChildId : '')) ?>">
            <i class="mdi mdi-briefcase-outline me-1"></i>Pilihan Karier
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'universities' ? 'active' : '') ?>"
             href="<?= site_url('parent/career?tab=universities' . ($activeChildId ? '&child_id=' . (int)$activeChildId : '')) ?>">
            <i class="mdi mdi-town-hall me-1"></i>Info Perguruan Tinggi
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="tab-content">
    <!-- TAB: Pilihan Karier -->
    <div class="tab-pane fade <?= ($activeTab === 'careers' ? 'show active' : '') ?>">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- Filter Karier -->
          <form class="row g-2 align-items-end mb-3" method="get" action="<?= site_url('parent/career') ?>">
            <input type="hidden" name="tab" value="careers">
            <?php if ($activeChildId): ?>
              <input type="hidden" name="child_id" value="<?= (int)$activeChildId ?>">
            <?php endif; ?>

            <div class="col-12 col-md-4">
              <label class="form-label mb-1">Kata kunci</label>
              <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Cari judul atau deskripsi karier..."
                value="<?= esc($filters['q'] ?? '') ?>"
              >
            </div>

            <div class="col-6 col-md-3">
              <label class="form-label mb-1">Sektor</label>
              <select name="sector" class="form-select">
                <option value="">Semua sektor</option>
                <?php foreach ($sectors as $s): ?>
                  <option
                    value="<?= esc($s) ?>"
                    <?= (!empty($filters['sector']) && $filters['sector'] === $s) ? 'selected' : '' ?>
                  >
                    <?= esc($s) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label mb-1">Min. edukasi</label>
              <select name="edu" class="form-select">
                <option value="">Semua edukasi</option>
                <?php foreach ($educs as $e): ?>
                  <option
                    value="<?= esc($e) ?>"
                    <?= (!empty($filters['edu']) && $filters['edu'] === $e) ? 'selected' : '' ?>
                  >
                    <?= esc($e) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label mb-1">Urutkan</label>
              <select name="sort" class="form-select">
                <option value="">Terbaru</option>
                <option value="popular" <?= (!empty($filters['sort']) && $filters['sort'] === 'popular') ? 'selected' : '' ?>>
                  Paling populer
                </option>
                <option value="salary" <?= (!empty($filters['sort']) && $filters['sort'] === 'salary') ? 'selected' : '' ?>>
                  Gaji rata-rata tertinggi
                </option>
              </select>
            </div>

            <div class="col-6 col-md-1 d-grid">
              <button class="btn btn-primary">Filter</button>
            </div>

            <div class="col-12 col-md-2 d-grid d-md-block">
              <a class="btn btn-light w-100"
                 href="<?= base_url('parent/career?tab=careers' . ($activeChildId ? '&child_id=' . (int)$activeChildId : '')) ?>">
                Reset
              </a>
            </div>
          </form>

          <div class="row g-3">
            <?php if (empty($careers)): ?>
              <div class="col-12">
                <div class="alert alert-warning mb-0">
                  Belum ada data karier yang sesuai filter. Coba ubah kata kunci atau hapus filter.
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($careers as $cRaw): ?>
                <?php
                  $c        = rowa($cRaw);
                  $id       = (int) ($c['id'] ?? 0);
                  $ttl      = $c['title'] ?? 'Tanpa Judul';
                  $sec      = $c['sector'] ?? null;
                  $edu      = $c['min_education'] ?? null;
                  $desc     = $c['description'] ?? ($c['short_description'] ?? '');
                  $skills   = [];
                  if (!empty($c['required_skills'])) {
                    $skills = json_decode((string) $c['required_skills'], true) ?: [];
                  }
                  $demand   = $c['demand_level'] ?? null;
                  $thumb    = $c['thumbnail'] ?? ($c['image'] ?? null);
                  $isSaved  = in_array($id, $savedCareerIds, true);
                  $creator  = $c['created_by_name'] ?? null;
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                  <div class="card h-100 shadow-sm">
                    <?php if (!empty($thumb)): ?>
                      <img
                        src="<?= esc($thumb) ?>"
                        class="card-img-top"
                        alt="Gambar <?= esc($ttl) ?>"
                        style="object-fit:cover; height:160px;"
                      >
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                      <h5 class="card-title mb-1 cc-title"><?= esc($ttl) ?></h5>

                      <div class="small text-muted mb-1 cc-creator">
                        <?php if ($creator): ?>
                          Dibagikan oleh <?= esc($creator) ?>
                        <?php endif; ?>
                      </div>

                      <div class="mb-2 cc-badges">
                        <?php if ($sec): ?>
                          <span class="badge bg-light text-body border me-1">
                            Sektor: <?= esc($sec) ?>
                          </span>
                        <?php endif; ?>
                        <?php if ($edu): ?>
                          <span class="badge bg-secondary me-1">
                            Min Edukasi: <?= esc($edu) ?>
                          </span>
                        <?php endif; ?>
                        <?php if ($demand !== null && $demand !== ''): ?>
                          <span class="badge bg-info">
                            Demand: <?= esc((string) $demand) ?>/10
                          </span>
                        <?php endif; ?>
                      </div>

                      <p class="card-text flex-grow-1 cc-desc"><?= clip($desc, 160) ?></p>

                      <div class="mb-2 cc-skills">
                        <?php if (!empty($skills)): ?>
                          <?php foreach (array_slice($skills, 0, 3) as $sk): ?>
                            <span class="badge bg-light text-body border me-1">
                              <?= esc($sk) ?>
                            </span>
                          <?php endforeach; ?>
                          <?php if (count($skills) > 3): ?>
                            <span class="text-muted small">
                              +<?= count($skills) - 3 ?> skill lain
                            </span>
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>

                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <a class="btn btn-outline-primary btn-sm"
                           href="<?= site_url('parent/career/' . $id) ?>">
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('parent/career/save/' . $id) ?>">
                          <?= csrf_field() ?>
                          <?php if ($activeChildId): ?>
                            <input type="hidden" name="child_id" value="<?= (int)$activeChildId ?>">
                          <?php endif; ?>
                          <?php if (!$activeChildId): ?>
                            <button class="btn btn-secondary btn-sm" type="button" disabled>
                              Pilih anak di atas
                            </button>
                          <?php elseif ($isSaved): ?>
                            <button class="btn btn-success btn-sm" type="button" disabled>
                              Tersimpan untuk anak
                            </button>
                          <?php else: ?>
                            <button class="btn btn-primary btn-sm" type="submit">
                              Simpan untuk anak
                            </button>
                          <?php endif; ?>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <?php $info = isset($pager) ? pagerInfo($pager) : null; ?>
          <?php if ($info): ?>
            <p class="text-muted small mt-3 mb-1">
              Menampilkan <?= $info[0] ?>-<?= $info[1] ?> dari <?= $info[2] ?> data
            </p>
          <?php endif; ?>
          <div class="mt-1">
            <?= isset($pager) ? $pager->links('default', 'bootstrap_pagination') : '' ?>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB: Info Perguruan Tinggi -->
    <div class="tab-pane fade <?= ($activeTab === 'universities' ? 'show active' : '') ?>">
      <div class="card shadow-sm">
        <div class="card-body">

          <form class="row g-2 align-items-end mb-3" method="get" action="<?= site_url('parent/career') ?>">
            <input type="hidden" name="tab" value="universities">
            <?php if ($activeChildId): ?>
              <input type="hidden" name="child_id" value="<?= (int)$activeChildId ?>">
            <?php endif; ?>

            <div class="col-12 col-md-4">
              <label class="form-label mb-1">Kata kunci</label>
              <input
                type="text"
                name="u_q"
                class="form-control"
                placeholder="Cari nama, alias, atau lokasi..."
                value="<?= esc($uniFilters['q'] ?? '') ?>"
              >
            </div>

            <div class="col-6 col-md-3">
              <label class="form-label mb-1">Lokasi</label>
              <select name="u_loc" class="form-select">
                <option value="">Semua lokasi</option>
                <?php foreach ($uniLocations as $loc): ?>
                  <option
                    value="<?= esc($loc) ?>"
                    <?= (!empty($uniFilters['location']) && $uniFilters['location'] === $loc) ? 'selected' : '' ?>
                  >
                    <?= esc($loc) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label mb-1">Akreditasi</label>
              <select name="u_accr" class="form-select">
                <option value="">Semua</option>
                <?php foreach ($uniAccrs as $acc): ?>
                  <option
                    value="<?= esc($acc) ?>"
                    <?= (!empty($uniFilters['accr']) && $uniFilters['accr'] === $acc) ? 'selected' : '' ?>
                  >
                    <?= esc($acc) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label mb-1">Urutkan</label>
              <select name="u_sort" class="form-select">
                <option value="">Nama A-Z</option>
                <option value="location" <?= (!empty($uniFilters['sort']) && $uniFilters['sort'] === 'location') ? 'selected' : '' ?>>
                  Lokasi
                </option>
                <option value="accr" <?= (!empty($uniFilters['sort']) && $uniFilters['sort'] === 'accr') ? 'selected' : '' ?>>
                  Akreditasi
                </option>
              </select>
            </div>

            <div class="col-6 col-md-1 d-grid">
              <button class="btn btn-primary">Filter</button>
            </div>

            <div class="col-12 col-md-2 d-grid d-md-block">
              <a class="btn btn-light w-100"
                 href="<?= site_url('parent/career?tab=universities' . ($activeChildId ? '&child_id=' . (int)$activeChildId : '')) ?>">
                Reset
              </a>
            </div>
          </form>

          <p class="text-muted small">
            Daftar ini menampilkan perguruan tinggi yang sudah didaftarkan oleh Guru BK
            dan dapat dijadikan referensi awal diskusi dengan anak.
          </p>

          <?php $uniList = is_array($universities) ? $universities : []; ?>

          <?php if (empty($uniList)): ?>
            <div class="alert alert-info mb-0">
              Belum ada data universitas yang dapat ditampilkan. Silakan cek kembali nanti.
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($uniList as $uRaw): ?>
                <?php
                  $u          = rowa($uRaw);
                  $uid        = (int) ($u['id'] ?? 0);
                  $name       = $u['university_name'] ?? 'Nama belum diisi';
                  $alias      = $u['alias'] ?? '';
                  $accr       = $u['accreditation'] ?? '';
                  $loc        = $u['location'] ?? '';
                  $desc       = $u['description'] ?? '';
                  $logo       = $u['logo'] ?? null;
                  $creatorUni = $u['created_by_name'] ?? null;
                  $isSavedUni = in_array($uid, $savedUniversityIds, true);
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                  <div class="card h-100 shadow-sm">
                    <?php if (!empty($logo)): ?>
                      <img
                        src="<?= esc($logo) ?>"
                        class="card-img-top"
                        alt="Logo <?= esc($name) ?>"
                        style="object-fit:contain; height:140px; background:#fff;"
                      >
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                      <h5 class="card-title mb-1 cc-title">
                        <?= esc($name) ?>
                        <?php if ($alias): ?>
                          <span class="text-muted small"> (<?= esc($alias) ?>)</span>
                        <?php endif; ?>
                      </h5>

                      <div class="small text-muted mb-1 cc-creator">
                        <?php if ($creatorUni): ?>
                          Dibagikan oleh <?= esc($creatorUni) ?>
                        <?php endif; ?>
                      </div>

                      <div class="mb-2 cc-badges">
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
                      </div>

                      <p class="card-text flex-grow-1 cc-desc"><?= clip($desc, 160) ?></p>

                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <a class="btn btn-outline-primary btn-sm"
                           href="<?= site_url('parent/career/' . $uid . '?type=uni') ?>">
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('parent/career/save/' . $uid . '?type=uni') ?>">
                          <?= csrf_field() ?>
                          <?php if ($activeChildId): ?>
                            <input type="hidden" name="child_id" value="<?= (int)$activeChildId ?>">
                          <?php endif; ?>

                          <?php if (!$activeChildId): ?>
                            <button class="btn btn-secondary btn-sm" type="button" disabled>
                              Pilih anak di atas
                            </button>
                          <?php elseif ($isSavedUni): ?>
                            <button class="btn btn-success btn-sm" type="button" disabled>
                              Tersimpan untuk anak
                            </button>
                          <?php else: ?>
                            <button class="btn btn-primary btn-sm" type="submit">
                              Simpan untuk anak
                            </button>
                          <?php endif; ?>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if (isset($uniPager)): ?>
              <?php $uniInfo = pagerInfo($uniPager, 'universities'); ?>
              <?php if ($uniInfo): ?>
                <p class="text-muted small mt-3 mb-1">
                  Menampilkan <?= $uniInfo[0] ?>-<?= $uniInfo[1] ?> dari <?= $uniInfo[2] ?> data
                </p>
              <?php endif; ?>
              <div class="mt-1">
                <?= $uniPager->links('universities', 'bootstrap_pagination') ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .nav-tabs .nav-link { white-space: nowrap; }
  .btn { white-space: nowrap; }

  /* Konsistensi tinggi kartu Karier & Perguruan Tinggi, apa pun panjang isinya */
  .cc-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.6em;
  }
  .cc-creator { min-height: 1.3em; }
  .cc-badges { min-height: 30px; }
  .cc-desc {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .cc-skills { min-height: 30px; }
</style>

<?= $this->endSection() ?>
