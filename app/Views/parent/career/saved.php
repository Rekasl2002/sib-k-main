<?php // app/Views/parent/career/saved.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('rowa')) {
  function rowa($r): array {
    return is_array($r) ? $r : (is_object($r) ? (array) $r : []);
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

$careers         = $careers ?? [];
$careerCount     = $careerCount ?? count($careers);
$universities    = $universities ?? [];
$universityCount = $universityCount ?? count($universities);
$activeTab       = $activeTab ?? 'careers';
if (!in_array($activeTab, ['careers', 'universities'], true)) {
  $activeTab = 'careers';
}

$children      = $children      ?? [];
$activeChildId = $activeChildId ?? null;
?>

<div class="container-fluid">
  <div class="card shadow-sm mb-3">
    <div class="card-body pb-0">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <h4 class="mb-0">Pilihan Karir & Kuliah Anak</h4>
          <div class="small text-muted">
            Orang tua dapat melihat dan mengatur daftar karier serta perguruan tinggi yang disimpan untuk anak.
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
          <form method="get" action="<?= site_url('parent/career/saved') ?>" class="d-flex align-items-center gap-2">
            <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">
            <label class="small mb-0">Anak</label>
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

          <a href="<?= site_url('parent/career' . ($activeChildId ? '?child_id=' . (int)$activeChildId : '')) ?>" class="btn btn-light btn-sm">
            Kembali ke eksplorasi
          </a>
        </div>
      </div>

      <ul class="nav nav-tabs mb-0">
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'careers' ? 'active' : '') ?>"
             href="<?= site_url('parent/career/saved?tab=careers' . ($activeChildId ? '&child_id=' . (int)$activeChildId : '')) ?>">
            Karier Tersimpan (<?= (int) $careerCount ?>)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'universities' ? 'active' : '') ?>"
             href="<?= site_url('parent/career/saved?tab=universities' . ($activeChildId ? '&child_id=' . (int)$activeChildId : '')) ?>">
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
          <?php if (!$activeChildId): ?>
            <div class="alert alert-info mb-0">
              Pilih anak terlebih dahulu untuk melihat daftar karier tersimpan.
            </div>
          <?php elseif (empty($careers)): ?>
            <div class="alert alert-info mb-0">
              Belum ada karier yang disimpan untuk anak ini.
              Anda dapat menambah dari halaman <a href="<?= site_url('parent/career?child_id=' . (int)$activeChildId) ?>">Info Karir & Kuliah</a>.
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
                          href="<?= site_url('parent/career/' . $id) ?>"
                        >
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('parent/career/remove/' . $id) ?>">
                          <?= csrf_field() ?>
                          <?php if ($activeChildId): ?>
                            <input type="hidden" name="child_id" value="<?= (int)$activeChildId ?>">
                          <?php endif; ?>
                          <button class="btn btn-outline-danger btn-sm" type="submit">
                            Hapus dari anak
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Perguruan tinggi tersimpan -->
    <div class="tab-pane fade <?= ($activeTab === 'universities' ? 'show active' : '') ?>">
      <div class="card shadow-sm">
        <div class="card-body">
          <?php if (!$activeChildId): ?>
            <div class="alert alert-info mb-0">
              Pilih anak terlebih dahulu untuk melihat daftar perguruan tinggi tersimpan.
            </div>
          <?php elseif (empty($universities)): ?>
            <div class="alert alert-info mb-0">
              Belum ada perguruan tinggi yang disimpan untuk anak ini.
              Anda dapat menambah dari tab <strong>Info Perguruan Tinggi</strong> di halaman eksplorasi.
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
                  $isPub  = (int) ($u['is_public'] ?? 0) === 1;
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
                        <span class="badge bg-info-subtle text-info-emphasis border">
                          <?= $isPub ? 'Negeri' : 'Swasta' ?>
                        </span>
                      </div>

                      <p class="card-text flex-grow-1"><?= clip($desc, 140) ?></p>

                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <a
                          class="btn btn-outline-primary btn-sm"
                          href="<?= site_url('parent/career/' . $uid . '?type=uni') ?>"
                        >
                          Detail
                        </a>

                        <form method="post" action="<?= site_url('parent/career/remove/' . $uid . '?type=uni') ?>">
                          <?= csrf_field() ?>
                          <?php if ($activeChildId): ?>
                            <input type="hidden" name="child_id" value="<?= (int)$activeChildId ?>">
                          <?php endif; ?>
                          <button class="btn btn-outline-danger btn-sm" type="submit">
                            Hapus dari anak
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
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
