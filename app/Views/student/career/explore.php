<?php // app/Views/student/career/explore.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helpers ringkas supaya tahan banting
if (!function_exists('rowa')) {
  function rowa($r): array
  {
    return is_array($r) ? $r : (is_object($r) ? (array) $r : []);
  }
}
if (!function_exists('v')) {
  function v($r, $k, $d = '')
  {
    $a = rowa($r);
    return esc($a[$k] ?? $d);
  }
}
if (!function_exists('clip')) {
  function clip($s, $len = 140)
  {
    $s = (string) ($s ?? '');
    if (mb_strlen($s) <= $len) {
      return esc($s);
    }
    return esc(mb_substr($s, 0, $len - 3) . '...');
  }
}

// Normalisasi variabel supaya tidak notice
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

// Tentukan tab aktif: default "careers"
$req = service('request');
$activeTab = $activeTab ?? ($req->getGet('tab') ?: 'careers');
if (!in_array($activeTab, ['careers', 'universities'], true)) {
  $activeTab = 'careers';
}
?>

<div class="container-fluid">
  <!-- Header + Tabs: mirip tampilan Guru BK tapi versi siswa -->
  <div class="card shadow-sm mb-3">
    <div class="card-body pb-0">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <h4 class="mb-0">Info Karir & Kuliah</h4>
          <div class="small text-muted">
            Jelajahi pilihan karir dan perguruan tinggi yang sudah dikurasi oleh Guru BK.
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= site_url('student/career/saved') ?>" class="btn btn-outline-primary btn-sm">
            <i class="mdi mdi-bookmark-outline me-1"></i>Item Tersimpan
          </a>
        </div>
      </div>

      <ul class="nav nav-tabs mb-0">
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'careers' ? 'active' : '') ?>"
             href="<?= site_url('student/career?tab=careers') ?>">
            <i class="mdi mdi-briefcase-outline me-1"></i>Pilihan Karir
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'universities' ? 'active' : '') ?>"
             href="<?= site_url('student/career?tab=universities') ?>">
            <i class="mdi mdi-town-hall me-1"></i>Info Perguruan Tinggi
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="tab-content">
    <!-- TAB: Pilihan Karir -->
    <div class="tab-pane fade <?= ($activeTab === 'careers' ? 'show active' : '') ?>">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- Filter Karir -->
          <form class="row g-2 align-items-end mb-3" method="get" action="<?= site_url('student/career') ?>">
            <input type="hidden" name="tab" value="careers">

            <div class="col-12 col-md-4">
              <label class="form-label mb-1">Kata kunci</label>
              <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Cari judul atau deskripsi karir..."
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
                <option
                  value="popular"
                  <?= (!empty($filters['sort']) && $filters['sort'] === 'popular') ? 'selected' : '' ?>
                >
                  Paling populer
                </option>
                <option
                  value="salary"
                  <?= (!empty($filters['sort']) && $filters['sort'] === 'salary') ? 'selected' : '' ?>
                >
                  Gaji rata-rata tertinggi
                </option>
              </select>
            </div>

            <div class="col-6 col-md-1 d-grid">
              <button class="btn btn-primary">Filter</button>
            </div>

            <div class="col-12 col-md-2 d-grid d-md-block">
              <a class="btn btn-light w-100" href="<?= base_url('student/career?tab=careers') ?>">Reset</a>
            </div>
          </form>

          <!-- Grid kartu karir -->
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
                  // Nama pembuat info (jika dikirim dari controller)
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
                      <h5 class="card-title mb-1"><?= esc($ttl) ?></h5>

                      <?php if ($creator): ?>
                        <div class="small text-muted mb-1">
                          Dibagikan oleh <?= esc($creator) ?>
                        </div>
                      <?php endif; ?>

                      <div class="mb-2">
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

                      <p class="card-text flex-grow-1"><?= clip($desc, 160) ?></p>

                      <?php if (!empty($skills)): ?>
                        <div class="mb-2">
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
                        </div>
                      <?php endif; ?>

                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <a
                          class="btn btn-outline-primary btn-sm"
                          href="<?= site_url('student/career/' . $id) ?>"
                        >
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('student/career/save/' . $id) ?>">
                          <?= csrf_field() ?>
                          <?php if ($isSaved): ?>
                            <button class="btn btn-success btn-sm" type="button" disabled>
                              Tersimpan
                            </button>
                          <?php else: ?>
                            <button class="btn btn-primary btn-sm" type="submit">
                              Simpan
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

          <div class="mt-3">
            <?= isset($pager) ? $pager->links() : '' ?>
          </div>
        </div>
      </div>
    </div>
    <!-- /TAB: Pilihan Karir -->

    <!-- TAB: Info Perguruan Tinggi -->
    <div class="tab-pane fade <?= ($activeTab === 'universities' ? 'show active' : '') ?>">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- Filter Universitas -->
          <form class="row g-2 align-items-end mb-3" method="get" action="<?= site_url('student/career') ?>">
            <input type="hidden" name="tab" value="universities">

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
                <option
                  value="location"
                  <?= (!empty($uniFilters['sort']) && $uniFilters['sort'] === 'location') ? 'selected' : '' ?>
                >
                  Lokasi
                </option>
                <option
                  value="accr"
                  <?= (!empty($uniFilters['sort']) && $uniFilters['sort'] === 'accr') ? 'selected' : '' ?>
                >
                  Akreditasi
                </option>
              </select>
            </div>

            <div class="col-6 col-md-1 d-grid">
              <button class="btn btn-primary">Filter</button>
            </div>

            <div class="col-12 col-md-2 d-grid d-md-block">
              <a class="btn btn-light w-100" href="<?= site_url('student/career?tab=universities') ?>">Reset</a>
            </div>
          </form>

          <p class="text-muted small">
            Daftar ini menampilkan perguruan tinggi yang sudah didaftarkan oleh Guru BK
            dan dapat dijadikan referensi awal.
          </p>

          <?php
          $uniList = is_array($universities) ? $universities : [];
          ?>

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
                  $isPub      = (int) ($u['is_public'] ?? 0) === 1;
                  $isSavedUni = in_array($uid, $savedUniversityIds, true);
                  // Nama pembuat info (jika dikirim dari controller)
                  $creatorUni = $u['created_by_name'] ?? null;
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
                      <h5 class="card-title mb-1">
                        <?= esc($name) ?>
                        <?php if ($alias): ?>
                          <span class="text-muted small"> (<?= esc($alias) ?>)</span>
                        <?php endif; ?>
                      </h5>

                      <?php if ($creatorUni): ?>
                        <div class="small text-muted mb-1">
                          Dibagikan oleh <?= esc($creatorUni) ?>
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
                          <?= $isPub ? 'Publik' : 'Non-publik' ?>
                        </span>
                      </div>

                      <p class="card-text flex-grow-1"><?= clip($desc, 160) ?></p>

                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <a
                          class="btn btn-outline-primary btn-sm"
                          href="<?= site_url('student/career/' . $uid . '?type=uni') ?>"
                        >
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('student/career/save/' . $uid . '?type=uni') ?>">
                          <?= csrf_field() ?>
                          <?php if ($isSavedUni): ?>
                            <button class="btn btn-success btn-sm" type="button" disabled>
                              Tersimpan
                            </button>
                          <?php else: ?>
                            <button class="btn btn-primary btn-sm" type="submit">
                              Simpan
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
              <div class="mt-3">
                <?= $uniPager->links('universities') ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- /TAB: Info Perguruan Tinggi -->
  </div>
</div>

<style>
  .nav-tabs .nav-link { white-space: nowrap; }
  .btn { white-space: nowrap; }
</style>

<?= $this->endSection() ?>
