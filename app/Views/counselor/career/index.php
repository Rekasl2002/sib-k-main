<?php
/**
 * File Path: app/Views/counselor/career/index.php
 * Halaman utama Info Karir/Kuliah (Guru BK)
 *
 * Variabel yang diharapkan dari Controller:
 * - $careers, $careerPager, $careerFilters (array)
 * - $universities, $uniPager
 * - $activeTab ('careers'|'universities')
 */

$this->extend('layouts/main');
$this->section('content');

$flashSuccess = session()->getFlashdata('success');
$flashError   = session()->getFlashdata('error');
$errors       = session('errors') ?? [];

// Agar view tetap bisa membaca nilai GET untuk prefill filter,
// walau controller belum mengoper "uniFilters".
$req = service('request');
$activeTab = $activeTab ?? ($req->getGet('tab') ?: 'careers');

// Prefill filter karir (fallback bila $careerFilters tidak ada)
$careerFilters = $careerFilters ?? [
    'q'      => $req->getGet('q'),
    'sector' => $req->getGet('sector'),
    'edu'    => $req->getGet('edu'),
    'status' => $req->getGet('status'),
    'pub'    => $req->getGet('pub'),
    'sort'   => $req->getGet('sort'),
];

// Prefill filter universitas
$uniFilters = [
    'q'      => $req->getGet('uq'),
    'acc'    => $req->getGet('uacc'),
    'loc'    => $req->getGet('uloc'),
    'status' => $req->getGet('ustatus'),
    'pub'    => $req->getGet('upub'),
    'sort'   => $req->getGet('usort'),
];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">
    <i class="mdi mdi-school-outline me-2"></i>Info Karir & Kuliah
  </h4>
</div>

<?php if ($flashSuccess): ?>
  <div class="alert alert-success"><?= esc($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
  <div class="alert alert-danger"><?= esc($flashError) ?></div>
<?php endif; ?>

<!-- Tabs + tombol aksi kanan -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <ul class="nav nav-tabs mb-0">
    <li class="nav-item">
      <a class="nav-link <?= ($activeTab === 'careers' ? 'active' : '') ?>"
         href="<?= site_url('counselor/career-info?tab=careers') ?>">
        <i class="mdi mdi-briefcase-outline me-1"></i>Pilihan Karir
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($activeTab === 'universities' ? 'active' : '') ?>"
         href="<?= site_url('counselor/career-info/universities?tab=universities') ?>">
        <i class="mdi mdi-town-hall me-1"></i>Info Perguruan Tinggi
      </a>
    </li>
  </ul>
  <div class="d-flex align-items-center gap-2">
    <!-- Tombol baru: rekap pilihan karir & PT siswa -->
    <a href="<?= route_to('counselor.career.choices') ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
      <i class="mdi mdi-account-multiple-outline me-1"></i> Pilihan Karir & PT Siswa
    </a>

    <?php if ($activeTab === 'careers'): ?>
      <a href="<?= route_to('counselor.career.create') ?>" class="btn btn-success btn-sm shadow-sm">
        <i class="mdi mdi-plus me-1"></i> Tambah Karir
      </a>
    <?php else: ?>
      <a href="<?= route_to('counselor.university.create') ?>" class="btn btn-success btn-sm shadow-sm">
        <i class="mdi mdi-plus me-1"></i> Tambah Universitas
      </a>
    <?php endif; ?>
  </div>
</div>

<div class="tab-content">
  <!-- TAB: Karir -->
  <div class="tab-pane fade <?= ($activeTab === 'careers' ? 'show active' : '') ?>">
    <div class="card">
      <div class="card-body">

        <!-- Filter Karir -->
        <div class="mb-3">
          <form class="d-flex flex-wrap gap-2" method="get" action="<?= site_url('counselor/career-info') ?>">
            <input type="hidden" name="tab" value="careers">

            <div class="input-group">
              <input type="text" name="q" class="form-control form-control-sm"
                     placeholder="Cari judul karir..."
                     value="<?= esc($careerFilters['q'] ?? '') ?>">
            </div>

            <select name="sector" class="form-select form-select-sm">
              <option value="">Semua sektor</option>
              <?php
              // Kumpulkan sektor dari data yang tampil (fallback sederhana)
              $sectors = [];
              foreach (($careers ?? []) as $c) {
                if (!empty($c['sector'])) $sectors[$c['sector']] = $c['sector'];
              }
              ksort($sectors);
              ?>
              <?php foreach ($sectors as $s): ?>
                <option value="<?= esc($s) ?>" <?= ($careerFilters['sector'] ?? '') === $s ? 'selected' : '' ?>>
                  <?= esc($s) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <select name="edu" class="form-select form-select-sm">
              <option value="">Semua tingkat</option>
              <?php foreach (['SMA/SMK','D3','S1','S2'] as $e): ?>
                <option value="<?= esc($e) ?>" <?= ($careerFilters['edu'] ?? '') === $e ? 'selected' : '' ?>>
                  <?= esc($e) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <!-- Baru: Filter Status & Publikasi -->
            <select name="status" class="form-select form-select-sm" title="Status">
              <?php $fStatus = $careerFilters['status'] ?? ''; ?>
              <option value=""  <?= $fStatus === ''  ? 'selected' : '' ?>>Semua status</option>
              <option value="1" <?= $fStatus === '1' ? 'selected' : '' ?>>Aktif</option>
              <option value="0" <?= $fStatus === '0' ? 'selected' : '' ?>>Nonaktif</option>
            </select>

            <select name="pub" class="form-select form-select-sm" title="Publikasi">
              <?php $fPub = $careerFilters['pub'] ?? ''; ?>
              <option value=""  <?= $fPub === ''  ? 'selected' : '' ?>>Semua publikasi</option>
              <option value="1" <?= $fPub === '1' ? 'selected' : '' ?>>Published</option>
              <option value="0" <?= $fPub === '0' ? 'selected' : '' ?>>Private</option>
            </select>

            <select name="sort" class="form-select form-select-sm">
              <?php $fSort = $careerFilters['sort'] ?? ''; ?>
              <option value="" <?= $fSort === '' ? 'selected' : '' ?>>Urut Judul (A-Z)</option>
              <option value="demand" <?= $fSort === 'demand' ? 'selected' : '' ?>>Urut Permintaan (tinggi&rarr;rendah)</option>
            </select>

            <button class="btn btn-sm btn-primary" type="submit" title="Cari">
              <i class="mdi mdi-magnify"></i>
            </button>
          </form>
        </div>
        <!-- /Filter Karir -->

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead>
              <tr>
                <th>Judul</th>
                <th>Sektor</th>
                <th>Min Edukasi</th>
                <th>Permintaan</th>
                <th>Dibuat oleh</th>
                <th>Status</th>
                <th>Publikasi</th>
                <th style="width: 180px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!empty($careers)): ?>
              <?php foreach ($careers as $c): ?>
                <?php
                  $creatorName = trim((string)($c['created_by_name'] ?? ''));
                  $creatorId   = $c['created_by'] ?? null;
                ?>
                <tr>
                  <td><?= esc($c['title']) ?></td>
                  <td><?= esc($c['sector'] ?? '—') ?></td>
                  <td><?= esc($c['min_education'] ?? '—') ?></td>
                  <td><?= esc((string)($c['demand_level'] ?? 0)) ?>/10</td>
                  <td>
                    <?php if ($creatorName !== ''): ?>
                      <?= esc($creatorName) ?>
                    <?php elseif (!empty($creatorId)): ?>
                      <span class="text-muted">ID #<?= esc((string)$creatorId) ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((int)($c['is_active'] ?? 0) === 1): ?>
                      <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((int)($c['is_public'] ?? 0) === 1): ?>
                      <span class="badge bg-primary">Published</span>
                    <?php else: ?>
                      <span class="badge bg-dark">Private</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <a href="<?= route_to('counselor.career.edit', $c['id']) ?>"
                         class="btn btn-outline-primary" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                      </a>
                      <form action="<?= route_to('counselor.career.toggle', $c['id']) ?>"
                            method="post" onsubmit="return confirm('Ubah status karir ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-warning" type="submit" title="Toggle Aktif">
                          <i class="mdi mdi-toggle-switch"></i>
                        </button>
                      </form>
                      <form action="<?= route_to('counselor.career.publish', $c['id']) ?>"
                            method="post" onsubmit="return confirm('Ubah status publikasi karir ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-info" type="submit" title="Toggle Publikasi">
                          <i class="mdi mdi-earth"></i>
                        </button>
                      </form>
                      <form action="<?= route_to('counselor.career.delete', $c['id']) ?>"
                            method="post" onsubmit="return confirm('Hapus data karir ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger" type="submit" title="Hapus">
                          <i class="mdi mdi-delete"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted">Belum ada data karir.</td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if (!empty($careerPager)): ?>
          <div class="mt-3">
            <?= $careerPager->links() ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
  <!-- /TAB: Karir -->

  <!-- TAB: Universitas -->
  <div class="tab-pane fade <?= ($activeTab === 'universities' ? 'show active' : '') ?>">
    <div class="card">
      <div class="card-body">

        <!-- Filter Universitas (disamakan dengan Karir) -->
        <div class="mb-3">
          <form class="d-flex flex-wrap gap-2" method="get" action="<?= site_url('counselor/career-info/universities') ?>">
            <input type="hidden" name="tab" value="universities">

            <div class="input-group">
              <input type="text" name="uq" class="form-control form-control-sm"
                     placeholder="Cari nama/alias universitas..."
                     value="<?= esc($uniFilters['q'] ?? '') ?>">
            </div>

            <?php
            // Kumpulkan akreditasi & lokasi dari data yang tampil (fallback sederhana)
            $accs = [];
            $locs = [];
            foreach (($universities ?? []) as $u) {
              if (!empty($u['accreditation'])) $accs[$u['accreditation']] = $u['accreditation'];
              if (!empty($u['location']))      $locs[$u['location']]      = $u['location'];
            }
            // Tambahkan opsi standar akreditasi bila belum ada
            foreach (['Unggul','A','B','C','Baik','Baik Sekali'] as $std) { $accs[$std] = $accs[$std] ?? $std; }
            ksort($accs); ksort($locs);
            ?>

            <select name="uacc" class="form-select form-select-sm" title="Akreditasi">
              <?php $uAcc = $uniFilters['acc'] ?? ''; ?>
              <option value=""  <?= $uAcc === ''  ? 'selected' : '' ?>>Semua akreditasi</option>
              <?php foreach ($accs as $acc): ?>
                <option value="<?= esc($acc) ?>" <?= $uAcc === $acc ? 'selected' : '' ?>><?= esc($acc) ?></option>
              <?php endforeach; ?>
            </select>

            <select name="uloc" class="form-select form-select-sm" title="Lokasi">
              <?php $uLoc = $uniFilters['loc'] ?? ''; ?>
              <option value=""  <?= $uLoc === ''  ? 'selected' : '' ?>>Semua lokasi</option>
              <?php foreach ($locs as $loc): ?>
                <option value="<?= esc($loc) ?>" <?= $uLoc === $loc ? 'selected' : '' ?>><?= esc($loc) ?></option>
              <?php endforeach; ?>
            </select>

            <select name="ustatus" class="form-select form-select-sm" title="Status">
              <?php $uStatus = $uniFilters['status'] ?? ''; ?>
              <option value=""  <?= $uStatus === ''  ? 'selected' : '' ?>>Semua status</option>
              <option value="1" <?= $uStatus === '1' ? 'selected' : '' ?>>Aktif</option>
              <option value="0" <?= $uStatus === '0' ? 'selected' : '' ?>>Nonaktif</option>
            </select>

            <select name="upub" class="form-select form-select-sm" title="Publikasi">
              <?php $uPub = $uniFilters['pub'] ?? ''; ?>
              <option value=""  <?= $uPub === ''  ? 'selected' : '' ?>>Semua publikasi</option>
              <option value="1" <?= $uPub === '1' ? 'selected' : '' ?>>Published</option>
              <option value="0" <?= $uPub === '0' ? 'selected' : '' ?>>Private</option>
            </select>

            <select name="usort" class="form-select form-select-sm">
              <?php $uSort = $uniFilters['sort'] ?? ''; ?>
              <option value="" <?= $uSort === '' ? 'selected' : '' ?>>Urut Nama (A-Z)</option>
              <option value="name_desc" <?= $uSort === 'name_desc' ? 'selected' : '' ?>>Urut Nama (Z-A)</option>
              <option value="acc" <?= $uSort === 'acc' ? 'selected' : '' ?>>Urut Akreditasi</option>
              <option value="loc" <?= $uSort === 'loc' ? 'selected' : '' ?>>Urut Lokasi</option>
            </select>

            <button class="btn btn-sm btn-primary" type="submit" title="Cari">
              <i class="mdi mdi-magnify"></i>
            </button>
          </form>
        </div>
        <!-- /Filter Universitas -->

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Alias</th>
                <th>Akreditasi</th>
                <th>Lokasi</th>
                <th>Dibuat oleh</th>
                <th>Status</th>
                <th>Publikasi</th>
                <th style="width: 180px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!empty($universities)): ?>
              <?php foreach ($universities as $u): ?>
                <?php
                  $uCreatorName = trim((string)($u['created_by_name'] ?? ''));
                  $uCreatorId   = $u['created_by'] ?? null;
                ?>
                <tr>
                  <td><?= esc($u['university_name']) ?></td>
                  <td><?= esc($u['alias'] ?? '—') ?></td>
                  <td><?= esc($u['accreditation'] ?? '—') ?></td>
                  <td><?= esc($u['location'] ?? '—') ?></td>
                  <td>
                    <?php if ($uCreatorName !== ''): ?>
                      <?= esc($uCreatorName) ?>
                    <?php elseif (!empty($uCreatorId)): ?>
                      <span class="text-muted">ID #<?= esc((string)$uCreatorId) ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((int)($u['is_active'] ?? 0) === 1): ?>
                      <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((int)($u['is_public'] ?? 0) === 1): ?>
                      <span class="badge bg-primary">Published</span>
                    <?php else: ?>
                      <span class="badge bg-dark">Private</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <a href="<?= route_to('counselor.university.edit', $u['id']) ?>"
                         class="btn btn-outline-primary" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                      </a>
                      <form action="<?= route_to('counselor.university.toggle', $u['id']) ?>"
                            method="post" onsubmit="return confirm('Ubah status universitas ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-warning" type="submit" title="Toggle Aktif">
                          <i class="mdi mdi-toggle-switch"></i>
                        </button>
                      </form>
                      <form action="<?= route_to('counselor.university.publish', $u['id']) ?>"
                            method="post" onsubmit="return confirm('Ubah status publikasi universitas ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-info" type="submit" title="Toggle Publikasi">
                          <i class="mdi mdi-earth"></i>
                        </button>
                      </form>
                      <form action="<?= route_to('counselor.university.delete', $u['id']) ?>"
                            method="post" onsubmit="return confirm('Hapus universitas ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger" type="submit" title="Hapus">
                          <i class="mdi mdi-delete"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted">Belum ada data universitas.</td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if (!empty($uniPager)): ?>
          <div class="mt-3">
            <?= $uniPager->links() ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
  <!-- /TAB: Universitas -->
</div>

<!-- Opsi kecil agar label tab & tombol tidak terpotong -->
<style>
  .nav-tabs .nav-link { white-space: nowrap; }
  .btn { white-space: nowrap; }
</style>

<?= $this->endSection() ?>
