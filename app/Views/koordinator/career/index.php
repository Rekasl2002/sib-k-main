<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $activeTab = $activeTab === 'universities' ? 'universities' : 'careers'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Fitur Info Karier dan Info Studi Lanjut</h4>
    <small class="text-muted">Koordinator BK - data referensi dan pilihan siswa</small>
  </div>
  <a href="<?= site_url('koordinator/career-info/student-choices') ?>" class="btn btn-outline-primary btn-sm">Pilihan Siswa</a>
</div>

<?= show_alerts() ?>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'careers' ? 'active' : '' ?>" href="<?= site_url('koordinator/career-info?tab=careers') ?>">Karier</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'universities' ? 'active' : '' ?>" href="<?= site_url('koordinator/career-info?tab=universities') ?>">Info Studi Lanjut</a>
  </li>
</ul>

<?php if ($activeTab === 'careers'): ?>
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <input type="hidden" name="tab" value="careers">
        <div class="col-md-8">
          <input type="text" name="q" class="form-control" placeholder="Cari karier, sektor, atau deskripsi" value="<?= esc($careerFilters['q'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">Status</option>
            <option value="1" <?= ($careerFilters['status'] ?? '') === '1' ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= ($careerFilters['status'] ?? '') === '0' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100">Filter</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead><tr><th>Karier</th><th>Sektor</th><th>Pendidikan</th><th>Status</th><th>Dibuat Oleh</th></tr></thead>
        <tbody>
          <?php if (empty($careers)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data karier.</td></tr>
          <?php else: foreach ($careers as $career): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= esc($career['title'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($career['short_description'] ?? '') ?></small>
              </td>
              <td><?= esc($career['sector'] ?? '-') ?></td>
              <td><?= esc($career['min_education'] ?? '-') ?></td>
              <td>
                <span class="badge bg-<?= !empty($career['is_active']) ? 'success' : 'secondary' ?>"><?= !empty($career['is_active']) ? 'Aktif' : 'Nonaktif' ?></span>
              </td>
              <td><?= esc($career['created_by_name'] ?? '-') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php if (!empty($careerPager)): ?><div class="mt-3"><?= $careerPager->links('careers') ?></div><?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead><tr><th>Perguruan Tinggi</th><th>Lokasi</th><th>Akreditasi</th><th>Status</th><th>Dibuat Oleh</th></tr></thead>
        <tbody>
          <?php if (empty($universities)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data studi lanjut.</td></tr>
          <?php else: foreach ($universities as $university): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= esc($university['university_name'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($university['alias'] ?? '') ?></small>
              </td>
              <td><?= esc($university['location'] ?? '-') ?></td>
              <td><?= esc($university['accreditation'] ?? '-') ?></td>
              <td>
                <span class="badge bg-<?= !empty($university['is_active']) ? 'success' : 'secondary' ?>"><?= !empty($university['is_active']) ? 'Aktif' : 'Nonaktif' ?></span>
              </td>
              <td><?= esc($university['created_by_name'] ?? '-') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php if (!empty($uniPager)): ?><div class="mt-3"><?= $uniPager->links('universities') ?></div><?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
