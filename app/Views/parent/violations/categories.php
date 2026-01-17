<!-- app/Views/parent/violations/categories.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('rowa')) {
    function rowa($r): array { return is_array($r) ? $r : (is_object($r) ? (array) $r : []); }
}

// Normalisasi
$categories = $categories ?? [];
$student    = rowa($student ?? []);
$studentId  = (int) ($student['id'] ?? 0);
$studentName = $student['full_name'] ?? null;

// Label per tingkat pelanggaran
$labels = [
    'Ringan' => 'Pelanggaran Ringan',
    'Sedang' => 'Pelanggaran Sedang',
    'Berat'  => 'Pelanggaran Berat',
];

// Kelompokkan berdasarkan severity_level
$grouped = [];
foreach ($categories as $cat) {
    $c   = rowa($cat);
    $lvl = $c['severity_level'] ?? 'Lainnya';
    $grouped[$lvl][] = $c;
}

$backUrl = $studentId
    ? base_url('parent/child/' . $studentId . '/violations')
    : base_url('parent/children');
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Kategori Pelanggaran (Referensi Orang Tua)</h4>
        <?php if ($studentName): ?>
          <div class="text-muted small">
            Untuk: <?= esc($studentName) ?>
          </div>
        <?php endif; ?>
      </div>
          <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= route_to('parent.children.violations', $studentId) ?>">Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item active">Kategori Kasus & Pelanggaran</li>
        </ol>
      </div>
    </div>

    <?php if (empty($categories)): ?>
      <div class="alert alert-info">
        Belum ada kategori pelanggaran yang aktif di sistem.
      </div>
    <?php else: ?>
      <?php foreach ($grouped as $level => $items): ?>
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">
              <?= esc($labels[$level] ?? ('Pelanggaran ' . $level)) ?>
            </h5>
            <p class="text-muted">
              Daftar jenis pelanggaran dengan tingkat: <strong><?= esc($level) ?></strong>.
              Informasi ini bersifat umum sebagai acuan bagi orang tua.
            </p>

            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width: 50px;">#</th>
                    <th>Nama Pelanggaran</th>
                    <th>Deskripsi</th>
                    <th>Pengurangan Poin</th>
                    <th>Contoh</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1; ?>
                  <?php foreach ($items as $cat): ?>
                    <?php $c = rowa($cat); ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td><?= esc($c['category_name'] ?? '-') ?></td>
                      <td>
                        <?php if (!empty($c['description'])): ?>
                          <span class="small d-inline-block" style="max-width: 260px;">
                            <?= nl2br(esc($c['description'])) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted small">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge bg-soft-danger text-danger">
                          <?= (int) ($c['point_deduction'] ?? 0) ?> poin
                        </span>
                      </td>
                      <td>
                        <?php if (!empty($c['examples'])): ?>
                          <span class="small d-inline-block" style="max-width: 260px;">
                            <?= nl2br(esc($c['examples'])) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted small">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<?= $this->endSection() ?>
