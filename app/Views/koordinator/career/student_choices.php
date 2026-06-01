<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $activeTab = $activeTab === 'universities' ? 'universities' : 'careers'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Pilihan Fitur Info Karier dan Info Studi Lanjut Siswa</h4>
    <small class="text-muted">Rekap minat siswa berdasarkan pilihan tersimpan</small>
  </div>
  <a href="<?= site_url('koordinator/career-info') ?>" class="btn btn-light btn-sm">Kembali</a>
</div>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'careers' ? 'active' : '' ?>" href="<?= site_url('koordinator/career-info/student-choices?tab=careers') ?>">Karier</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'universities' ? 'active' : '' ?>" href="<?= site_url('koordinator/career-info/student-choices?tab=universities') ?>">Info Studi Lanjut</a>
  </li>
</ul>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">
      <div class="col-md-10">
        <input type="text" name="q" class="form-control" placeholder="Cari siswa, NISN, atau pilihan" value="<?= esc($filters['q'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <?php if ($activeTab === 'careers'): ?>
      <table class="table table-striped align-middle mb-0">
        <thead><tr><th>Siswa</th><th>Kelas</th><th>Karier</th><th>Sektor</th><th>Disimpan</th></tr></thead>
        <tbody>
          <?php if (empty($careerChoices)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pilihan karier.</td></tr>
          <?php else: foreach ($careerChoices as $row): ?>
            <tr>
              <td><?= esc($row['student_name'] ?? '-') ?><div class="small text-muted"><?= esc($row['nisn'] ?? '') ?></div></td>
              <td><?= esc($row['class_name'] ?? '-') ?></td>
              <td><?= esc($row['career_title'] ?? '-') ?></td>
              <td><?= esc($row['sector'] ?? '-') ?></td>
              <td><?= esc($row['saved_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php if (!empty($careerPager)): ?><div class="mt-3"><?= $careerPager->links('student_careers') ?></div><?php endif; ?>
    <?php else: ?>
      <table class="table table-striped align-middle mb-0">
        <thead><tr><th>Siswa</th><th>Kelas</th><th>Perguruan Tinggi</th><th>Lokasi</th><th>Disimpan</th></tr></thead>
        <tbody>
          <?php if (empty($universityChoices)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pilihan studi lanjut.</td></tr>
          <?php else: foreach ($universityChoices as $row): ?>
            <tr>
              <td><?= esc($row['student_name'] ?? '-') ?><div class="small text-muted"><?= esc($row['nisn'] ?? '') ?></div></td>
              <td><?= esc($row['class_name'] ?? '-') ?></td>
              <td><?= esc($row['university_name'] ?? '-') ?></td>
              <td><?= esc($row['location'] ?? '-') ?></td>
              <td><?= esc($row['saved_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php if (!empty($universityPager)): ?><div class="mt-3"><?= $universityPager->links('student_universities') ?></div><?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
