<?php
/**
 * File Path: app/Views/homeroom_teacher/career/index.php
 * Halaman utama Info Karir/Kuliah (Wali Kelas)
 *
 * Variabel:
 * - $careers, $careerPager, $careerFilters
 * - $universities, $uniPager
 * - $activeTab ('careers'|'universities')
 */

$this->extend('layouts/main');
$this->section('content');

$flashSuccess = session()->getFlashdata('success');
$flashError   = session()->getFlashdata('error');
$req          = service('request');

// Prefill filter karir
$careerFilters = $careerFilters ?? [
    'q'      => $req->getGet('q'),
    'sector' => $req->getGet('sector'),
    'edu'    => $req->getGet('edu'),
    'status' => $req->getGet('status'),
    'pub'    => $req->getGet('pub'),
    'sort'   => $req->getGet('sort'),
];

// Prefill filter universitas (opsional)
$uniFilters = [
    'q'      => $req->getGet('uq'),
    'acc'    => $req->getGet('uacc'),
    'loc'    => $req->getGet('uloc'),
    'status' => $req->getGet('ustat'),
];
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
      <div>
        <h4 class="mb-1">Info Karir & Perguruan Tinggi</h4>
        <p class="text-muted mb-0">
          Referensi untuk mendampingi perencanaan karir dan studi lanjut siswa di kelas perwalian Anda.
        </p>
      </div>
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
             href="<?= site_url('homeroom/career-info?tab=careers') ?>">
            Karir
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($activeTab === 'universities' ? 'active' : '') ?>"
             href="<?= site_url('homeroom/career-info?tab=universities') ?>">
            Perguruan Tinggi
          </a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <!-- Rekap pilihan siswa -->
        <a href="<?= route_to('homeroom.career.choices') ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
          <i class="mdi mdi-account-multiple-outline me-1"></i> Pilihan Karir &amp; PT Siswa
        </a>
        <!-- Tidak ada tombol tambah di versi wali kelas -->
      </div>
    </div>

    <div class="tab-content">
      <!-- TAB: Karir -->
      <div class="tab-pane <?= ($activeTab === 'careers' ? 'active show' : '') ?>" id="tab-careers">

        <div class="card">
          <div class="card-body">

            <form class="row g-2 mb-3" method="get" action="<?= site_url('homeroom/career-info') ?>">
              <input type="hidden" name="tab" value="careers">

              <div class="col-md-3">
                <label class="form-label">Cari</label>
                <input type="text" name="q" value="<?= esc($careerFilters['q'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="Judul / sektor / deskripsi">
              </div>

              <div class="col-md-2">
                <label class="form-label">Sektor</label>
                <input type="text" name="sector" value="<?= esc($careerFilters['sector'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="Mis. Kesehatan">
              </div>

              <div class="col-md-2">
                <label class="form-label">Min. Pendidikan</label>
                <input type="text" name="edu" value="<?= esc($careerFilters['edu'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="SMA/SMK/D3/S1">
              </div>

              <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="">Semua</option>
                  <option value="1" <?= ($careerFilters['status'] === '1' ? 'selected' : '') ?>>Aktif</option>
                  <option value="0" <?= ($careerFilters['status'] === '0' ? 'selected' : '') ?>>Nonaktif</option>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label">Publikasi</label>
                <select name="pub" class="form-select form-select-sm">
                  <option value="">Semua</option>
                  <option value="1" <?= ($careerFilters['pub'] === '1' ? 'selected' : '') ?>>Publik</option>
                  <option value="0" <?= ($careerFilters['pub'] === '0' ? 'selected' : '') ?>>Internal</option>
                </select>
              </div>

              <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table table-sm align-middle table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Judul Karir</th>
                    <th>Sektor</th>
                    <th>Min. Pendidikan</th>
                    <th>Dibuat Oleh</th>
                    <th>Status</th>
                    <th>Publik</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (!empty($careers) && count($careers)): ?>
                  <?php $no = 1 + ((int) ($careerPager?->getCurrentPage('careers') ?? 1) - 1) * (int) ($careerPager?->getPerPage('careers') ?? 10); ?>
                  <?php foreach ($careers as $career): ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= esc($career['title'] ?? '') ?></td>
                      <td><?= esc($career['sector'] ?? '-') ?></td>
                      <td><?= esc($career['min_education'] ?? '-') ?></td>
                      <td><?= esc($career['created_by_name'] ?? '-') ?></td>
                      <td>
                        <?php if (!empty($career['is_active'])): ?>
                          <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">Nonaktif</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($career['is_public'])): ?>
                          <span class="badge bg-info text-dark">Publik</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark">Internal</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted">Belum ada data karir.</td>
                  </tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>

            <?php if ($careerPager): ?>
              <div class="mt-2">
                <?= $careerPager->links('careers', 'default_full') ?>
              </div>
            <?php endif; ?>

          </div>
        </div>

      </div>

      <!-- TAB: Universitas -->
      <div class="tab-pane <?= ($activeTab === 'universities' ? 'active show' : '') ?>" id="tab-universities">

        <div class="card">
          <div class="card-body">

            <form class="row g-2 mb-3" method="get" action="<?= site_url('homeroom/career-info') ?>">
              <input type="hidden" name="tab" value="universities">

              <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input type="text" name="uq" value="<?= esc($uniFilters['q'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="Nama universitas / prodi / lokasi">
              </div>

              <div class="col-md-2">
                <label class="form-label">Akreditasi</label>
                <input type="text" name="uacc" value="<?= esc($uniFilters['acc'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="A/B/C/unggul">
              </div>

              <div class="col-md-3">
                <label class="form-label">Lokasi</label>
                <input type="text" name="uloc" value="<?= esc($uniFilters['loc'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="Kota / provinsi">
              </div>

              <div class="col-md-2">
                <label class="form-label">Status</label>
                <input type="text" name="ustat" value="<?= esc($uniFilters['status'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="Negeri / swasta">
              </div>

              <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table table-sm align-middle table-bordered">
                <thead class="table-light">
                    <tr>
                    <th>#</th>
                    <th>Nama Universitas</th>
                    <th>Akreditasi</th>
                    <th>Lokasi</th>
                    <th>Dibuat Oleh</th>
                    <th>Status</th>
                    <th>Publik</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($universities) && count($universities)): ?>
                    <?php
                    $no = 1 + ((int) ($uniPager?->getCurrentPage('universities') ?? 1) - 1)
                            * (int) ($uniPager?->getPerPage('universities') ?? 10);
                    ?>
                    <?php foreach ($universities as $univ): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= esc($univ['university_name'] ?? '') ?></td>
                        <td><?= esc($univ['accreditation'] ?? '-') ?></td>
                        <td><?= esc($univ['location'] ?? '-') ?></td>
                        <td><?= esc($univ['created_by_name'] ?? '-') ?></td>
                        <td>
                        <?php if (!empty($univ['is_active'])): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nonaktif</span>
                        <?php endif; ?>
                        </td>
                        <td>
                        <?php if (!empty($univ['is_public'])): ?>
                            <span class="badge bg-info text-dark">Publik</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Internal</span>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                    <td colspan="7" class="text-center text-muted">Belum ada data universitas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
                </table>
            </div>

            <?php if ($uniPager): ?>
              <div class="mt-2">
                <?= $uniPager->links('universities', 'default_full') ?>
              </div>
            <?php endif; ?>

          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<?php $this->endSection(); ?>
