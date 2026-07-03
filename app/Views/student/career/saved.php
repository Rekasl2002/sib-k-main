<?php // app/Views/student/career/saved.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('rowa')) {
  function rowa($r): array
  {
    return is_array($r) ? $r : (is_object($r) ? (array) $r : []);
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

$careers         = $careers ?? [];
$careerCount     = $careerCount ?? count($careers);
$universities    = $universities ?? [];
$universityCount = $universityCount ?? count($universities);
$activeTab       = $activeTab ?? 'careers';
if (!in_array($activeTab, ['careers', 'universities'], true)) {
  $activeTab = 'careers';
}
$pager    = $pager    ?? null;
$uniPager = $uniPager ?? null;
$q        = $q        ?? '';
$uq       = $uq       ?? '';
?>

<div class="container-fluid">
  <!-- Page Title -->
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="mb-0">Item Tersimpan</h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('student/dashboard') ?>">Siswa</a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('student/career') ?>">Info Karier dan Studi Lanjut</a></li>
            <li class="breadcrumb-item active">Item Tersimpan</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Kartu Statistik -->
  <div class="row">
    <div class="col-6 col-md-3">
      <div class="card mini-stats-wid">
        <div class="card-body">
          <div class="d-flex">
            <div class="flex-grow-1">
              <p class="text-dark fw-medium mb-2">Karier Tersimpan</p>
              <h4 class="mb-0 text-dark"><?= number_format((int) $careerCount) ?></h4>
            </div>
            <div class="flex-shrink-0 align-self-center">
              <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                <span class="avatar-title"><i class="mdi mdi-briefcase-outline font-size-24"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card mini-stats-wid">
        <div class="card-body">
          <div class="d-flex">
            <div class="flex-grow-1">
              <p class="text-dark fw-medium mb-2">Perguruan Tinggi Tersimpan</p>
              <h4 class="mb-0 text-dark"><?= number_format((int) $universityCount) ?></h4>
            </div>
            <div class="flex-shrink-0 align-self-center">
              <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                <span class="avatar-title"><i class="mdi mdi-town-hall font-size-24"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body pb-0">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <p class="text-dark mb-0">
          Karier dan perguruan tinggi yang kamu tandai sebagai favorit akan muncul di sini.
        </p>
        <div>
          <a href="<?= site_url('student/career') ?>" class="btn btn-light btn-sm">
            Kembali ke eksplorasi
          </a>
        </div>
      </div>

      <ul class="nav nav-tabs mb-0">
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'careers' ? 'active' : '') ?>"
             href="<?= site_url('student/career/saved?tab=careers') ?>">
            Karier Tersimpan (<?= (int) $careerCount ?>)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'universities' ? 'active' : '') ?>"
             href="<?= site_url('student/career/saved?tab=universities') ?>">
            Perguruan Tinggi Tersimpan (<?= (int) $universityCount ?>)
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="tab-content">
    <!-- Karier tersimpan -->
    <div class="tab-pane fade <?= ($activeTab === 'careers' ? 'show active' : '') ?>">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- Cari di karier tersimpan -->
          <form class="row g-2 align-items-end mb-3" method="get" action="<?= site_url('student/career/saved') ?>">
            <input type="hidden" name="tab" value="careers">
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Cari karier tersimpan</label>
              <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Cari judul atau sektor karier..."
                value="<?= esc($q) ?>"
              >
            </div>
            <div class="col-6 col-md-2 d-grid">
              <button class="btn btn-primary">Cari</button>
            </div>
            <?php if ($q !== ''): ?>
              <div class="col-6 col-md-2 d-grid">
                <a class="btn btn-light" href="<?= site_url('student/career/saved?tab=careers') ?>">Reset</a>
              </div>
            <?php endif; ?>
          </form>

          <?php if (empty($careers)): ?>
            <div class="alert alert-info mb-0">
              <?php if ($q !== ''): ?>
                Tidak ada karier tersimpan yang cocok dengan pencarian "<?= esc($q) ?>".
              <?php else: ?>
                Belum ada karier yang kamu simpan.
                Coba eksplor di halaman <a href="<?= site_url('student/career') ?>">Info Karier dan Studi Lanjut</a>.
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($careers as $cRaw): ?>
                <?php
                  $c      = rowa($cRaw);
                  $id     = (int) ($c['id'] ?? 0);
                  $ttl    = $c['title'] ?? 'Tanpa Judul';
                  $sec    = $c['sector'] ?? null;
                  $edu    = $c['min_education'] ?? null;
                  $desc   = $c['description'] ?? ($c['short_description'] ?? '');
                  $thumb  = $c['thumbnail'] ?? ($c['image'] ?? null);
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

                      <div class="mb-2">
                        <?php if ($sec): ?>
                          <span class="badge bg-light text-body border me-1">
                            <?= esc($sec) ?>
                          </span>
                        <?php endif; ?>
                        <?php if ($edu): ?>
                          <span class="badge bg-secondary me-1">
                            <?= esc($edu) ?>
                          </span>
                        <?php endif; ?>
                      </div>

                      <p class="card-text flex-grow-1"><?= clip($desc, 140) ?></p>

                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <a
                          class="btn btn-outline-primary btn-sm"
                          href="<?= site_url('student/career/' . $id) ?>"
                        >
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('student/career/remove/' . $id) ?>">
                          <?= csrf_field() ?>
                          <button class="btn btn-outline-danger btn-sm" type="submit">
                            Hapus
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php $info = pagerInfo($pager); ?>
            <?php if ($info): ?>
              <p class="text-muted small mt-3 mb-1">
                Menampilkan <?= $info[0] ?>-<?= $info[1] ?> dari <?= $info[2] ?> data
              </p>
            <?php endif; ?>
            <div class="mt-1">
              <?= $pager ? $pager->links() : '' ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Perguruan tinggi tersimpan -->
    <div class="tab-pane fade <?= ($activeTab === 'universities' ? 'show active' : '') ?>">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- Cari di perguruan tinggi tersimpan -->
          <form class="row g-2 align-items-end mb-3" method="get" action="<?= site_url('student/career/saved') ?>">
            <input type="hidden" name="tab" value="universities">
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Cari perguruan tinggi tersimpan</label>
              <input
                type="text"
                name="u_q"
                class="form-control"
                placeholder="Cari nama, alias, atau lokasi..."
                value="<?= esc($uq) ?>"
              >
            </div>
            <div class="col-6 col-md-2 d-grid">
              <button class="btn btn-primary">Cari</button>
            </div>
            <?php if ($uq !== ''): ?>
              <div class="col-6 col-md-2 d-grid">
                <a class="btn btn-light" href="<?= site_url('student/career/saved?tab=universities') ?>">Reset</a>
              </div>
            <?php endif; ?>
          </form>

          <?php if (empty($universities)): ?>
            <div class="alert alert-info mb-0">
              <?php if ($uq !== ''): ?>
                Tidak ada perguruan tinggi tersimpan yang cocok dengan pencarian "<?= esc($uq) ?>".
              <?php else: ?>
                Belum ada perguruan tinggi yang kamu simpan.
                Coba jelajahi tab <strong>Info Perguruan Tinggi</strong> di halaman eksplorasi.
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($universities as $uRaw): ?>
                <?php
                  $u      = rowa($uRaw);
                  $uid    = (int) ($u['id'] ?? 0);
                  $name   = $u['university_name'] ?? 'Nama belum diisi';
                  $alias  = $u['alias'] ?? '';
                  $accr   = $u['accreditation'] ?? '';
                  $loc    = $u['location'] ?? '';
                  $desc   = $u['description'] ?? '';
                  $logo   = $u['logo'] ?? null;
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
                      </div>

                      <p class="card-text flex-grow-1"><?= clip($desc, 140) ?></p>

                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <a
                          class="btn btn-outline-primary btn-sm"
                          href="<?= site_url('student/career/' . $uid . '?type=uni') ?>"
                        >
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('student/career/remove/' . $uid . '?type=uni') ?>">
                          <?= csrf_field() ?>
                          <button class="btn btn-outline-danger btn-sm" type="submit">
                            Hapus
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php $uniInfo = pagerInfo($uniPager, 'universities'); ?>
            <?php if ($uniInfo): ?>
              <p class="text-muted small mt-3 mb-1">
                Menampilkan <?= $uniInfo[0] ?>-<?= $uniInfo[1] ?> dari <?= $uniInfo[2] ?> data
              </p>
            <?php endif; ?>
            <div class="mt-1">
              <?= $uniPager ? $uniPager->links('universities') : '' ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .nav-tabs .nav-link { white-space: nowrap; }
</style>

<?= $this->endSection() ?>
