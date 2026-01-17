<!-- app/Views/student/violations/categories.php -->
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * @var array|null $categories            Daftar kategori flat (opsional, dari controller)
 * @var array|null $categoriesBySeverity  Daftar kategori yang sudah dikelompokkan per tingkat
 */

// Label heading per tingkat
$labels = [
    'Ringan'  => 'Pelanggaran Ringan',
    'Sedang'  => 'Pelanggaran Sedang',
    'Berat'   => 'Pelanggaran Berat',
    'Lainnya' => 'Kategori Lainnya',
];

// Normalisasi variabel supaya view bisa jalan baik ketika:
// - controller mengirim $categories (flat), atau
// - controller mengirim $categoriesBySeverity (sudah digroup)
$categories           = $categories ?? [];
$categoriesBySeverity = $categoriesBySeverity ?? [];

// Jika belum ada $categoriesBySeverity tapi ada $categories flat, kelompokkan di sini
if (empty($categoriesBySeverity) && ! empty($categories)) {
    // Siapkan struktur awal
    $categoriesBySeverity = [
        'Ringan'  => [],
        'Sedang'  => [],
        'Berat'   => [],
        'Lainnya' => [],
    ];

    foreach ($categories as $cat) {
        $row = is_array($cat) ? $cat : (array) $cat;
        $sev = $row['severity_level'] ?? null;

        if (! $sev || ! array_key_exists($sev, $categoriesBySeverity)) {
            $categoriesBySeverity['Lainnya'][] = $row;
        } else {
            $categoriesBySeverity[$sev][] = $row;
        }
    }
}
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <h4 class="mb-0">KATEGORI PELANGGARAN</h4>
      <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"> <a href="<?= base_url('student/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"> <a href="<?= base_url('student/violations') ?>">Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item active">Kategori Kasus & Pelanggaran</li>
        </ol>
    </div>

    <?php
      $hasAny = false;
      foreach ($labels as $sevKey => $title):
        $items = $categoriesBySeverity[$sevKey] ?? [];
        if (empty($items)) {
            continue;
        }
        $hasAny = true;
    ?>
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><?= esc($title) ?></h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
              <thead>
                <tr>
                  <th style="width: 220px;">Kategori</th>
                  <th>Deskripsi</th>
                  <th style="width: 120px;">Poin</th>
                  <th style="width: 260px;">Contoh</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $cat): ?>
                  <?php $cat = is_array($cat) ? $cat : (array) $cat; ?>
                  <tr>
                    <td>
                      <strong><?= esc($cat['category_name'] ?? '-') ?></strong>
                    </td>
                    <td>
                      <?= nl2br(esc($cat['description'] ?? '-', 'html')) ?>
                    </td>
                    <td>
                      <span class="badge bg-danger">
                        <?= (int) ($cat['point_deduction'] ?? ($cat['points'] ?? 0)) ?> poin
                      </span>
                    </td>
                    <td>
                      <?= nl2br(esc($cat['examples'] ?? '-', 'html')) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (! $hasAny): ?>
      <div class="card">
        <div class="card-body">
          <p class="mb-0 text-muted">
            Belum ada kategori pelanggaran yang aktif di sistem.
          </p>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?= $this->endSection() ?>
