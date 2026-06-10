<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .prototype-page .proto-card,
  .prototype-page .proto-panel {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .prototype-page .proto-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 22px;
  }
  .prototype-page .proto-link {
    color: inherit;
    text-decoration: none;
  }
  .prototype-page .proto-link:hover .proto-card {
    border-color: rgba(31, 111, 84, .35);
    transform: translateY(-1px);
  }
  .prototype-page .proto-card {
    transition: border-color .18s ease, transform .18s ease;
  }
  .prototype-page .proto-card-muted {
    background: #f8fafc;
  }
  .prototype-page .proto-card-muted .proto-icon {
    opacity: .72;
  }
  .prototype-page .metric-value {
    font-size: 28px;
    line-height: 1;
    font-weight: 700;
  }
  .prototype-page .role-switch {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .prototype-page .role-switch .btn,
  .prototype-page .proto-card .btn {
    border-radius: 8px;
  }
  .prototype-page .proto-table td,
  .prototype-page .proto-table th {
    vertical-align: middle;
  }
  .prototype-page .proto-section-heading {
    border-left: 4px solid #1f6f54;
    padding-left: 12px;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features = is_array($features ?? null) ? $features : [];
$featureSections = is_array($featureSections ?? null) ? $featureSections : [];
if (! $featureSections && $features) {
    $featureSections = [[
        'title' => 'Fitur Pengembangan',
        'description' => 'Daftar fitur prototipe pengembangan aplikasi.',
        'items' => $features,
    ]];
}
$stats = is_array($stats ?? null) ? $stats : [];
$roleMode = (string) ($roleMode ?? '');
$roleLabel = (string) ($roleLabel ?? 'Pengguna');
$roleOptions = is_array($roleOptions ?? null) ? $roleOptions : [];
$roleSummary = is_array($roleSummary ?? null) ? $roleSummary : [];
$flowStartUrl = (string) ($flowStartUrl ?? 'prototype');
$flowPageCount = (int) ($flowPageCount ?? 0);
$crudDiagramUrl = (string) ($crudDiagramUrl ?? '');
?>

<div class="prototype-page">
  <nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Prototipe Pengembangan</li>
    </ol>
  </nav>

  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <h2 class="page-title mb-2">Prototipe Pengembangan SIB-K</h2>
      <p class="text-muted mb-0">
        Halaman contoh untuk fitur inti, fitur yang diperbarui, dan fitur lama yang digantikan. Data yang tampil adalah data contoh rentang Juni 2026 agar calon pengguna dapat menilai alur halaman tanpa mengubah data asli.
      </p>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <div class="d-flex flex-wrap justify-content-lg-end gap-2">
        <a href="<?= base_url($flowStartUrl) ?>" class="btn btn-primary">
          <i class="mdi mdi-play-circle-outline me-1"></i> Mulai Alur Demo
        </a>
        <a href="<?= base_url('prototype/evaluation') ?>" class="btn btn-success">
          <i class="mdi mdi-clipboard-check-outline me-1"></i> Isi Formulir Evaluasi
        </a>
        <?php helper('simulation_access'); ?>
        <?php if (function_exists('simulation_access_is_admin') && simulation_access_is_admin()): ?>
          <a href="<?= base_url('prototype/evaluation/results') ?>" class="btn btn-outline-dark">
            <i class="mdi mdi-poll me-1"></i> Hasil Evaluasi
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="role-switch p-3 mb-4">
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
      <div>
        <div class="fw-semibold">Sudut pandang aktif: <?= esc($roleLabel) ?></div>
        <?php if ($roleSummary): ?>
          <small class="text-muted"><?= esc($roleSummary[0] ?? '') ?></small>
        <?php else: ?>
          <small class="text-muted">Tampilan dan aksi contoh mengikuti peran pengguna aktif.</small>
        <?php endif; ?>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($roleOptions as $key => $label): ?>
          <a
            href="<?= base_url('prototype?role=' . rawurlencode((string) $key)) ?>"
            class="btn btn-sm <?= $key === $roleMode ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= esc($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
      <?php $tone = (string) ($stat['tone'] ?? 'primary'); ?>
      <div class="col-md-6 col-xl-3">
        <div class="card proto-card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <span class="proto-icon bg-<?= esc($tone) ?> bg-opacity-10 text-<?= esc($tone) ?>">
              <i class="<?= esc($stat['icon'] ?? 'mdi mdi-chart-box-outline') ?>"></i>
            </span>
            <div>
              <div class="metric-value"><?= esc((string) ($stat['value'] ?? 0)) ?></div>
              <div class="text-muted"><?= esc($stat['label'] ?? '') ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($roleSummary): ?>
    <div class="card proto-panel mb-4">
      <div class="card-body">
        <h5 class="mb-3">Catatan Hak Akses Tampilan <?= esc($roleLabel) ?></h5>
        <div class="row g-3">
          <?php foreach ($roleSummary as $summary): ?>
            <div class="col-lg-6">
              <div class="d-flex gap-2">
                <i class="mdi mdi-shield-check-outline text-primary mt-1"></i>
                <div class="text-muted"><?= esc($summary) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($crudDiagramUrl !== ''): ?>
    <div class="card proto-panel mb-4">
      <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
          <div class="proto-section-heading">
            <h5 class="mb-1">Tabel Hak Akses (CRUD)</h5>
            <p class="text-muted mb-0">Opsional, tidak wajib dilihat. Ringkasan &ldquo;siapa boleh melakukan apa&rdquo; pada setiap fitur untuk semua peran.</p>
          </div>
          <button class="btn btn-outline-primary btn-sm align-self-lg-start" type="button" data-bs-toggle="collapse" data-bs-target="#crudPanel" aria-expanded="false" aria-controls="crudPanel">
            <i class="mdi mdi-table-eye me-1"></i> Lihat Tabel Hak Akses
          </button>
        </div>
        <div class="collapse mt-3" id="crudPanel">
          <div class="alert alert-info">
            <div class="fw-semibold mb-1">Apa itu tabel CRUD?</div>
            <p class="mb-2">Tabel CRUD adalah ringkasan hak akses: untuk setiap fitur, tabel ini menunjukkan apa saja yang boleh dilakukan setiap peran terhadap data. CRUD adalah singkatan dari empat tindakan dasar pada data:</p>
            <ul class="mb-2">
              <li><strong>C &mdash; Create (Tambah):</strong> membuat atau menambah data baru.</li>
              <li><strong>R &mdash; Read (Lihat):</strong> melihat atau membaca data.</li>
              <li><strong>U &mdash; Update (Ubah):</strong> mengubah atau memperbarui data.</li>
              <li><strong>D &mdash; Delete (Hapus):</strong> menghapus data.</li>
            </ul>
            <p class="mb-0">Tanda <strong>*</strong> berarti aksesnya terbatas (misalnya hanya untuk data miliknya sendiri, hanya kelas binaannya, atau hanya bila diundang). Tanda <strong>&ndash;</strong> berarti peran tersebut tidak memiliki akses ke fitur itu. Singkatnya, tabel ini menjawab pertanyaan: <em>&ldquo;siapa boleh melakukan apa?&rdquo;</em></p>
          </div>
          <div class="border rounded p-2 bg-white text-center">
            <img src="<?= esc($crudDiagramUrl) ?>" alt="Tabel CRUD hak akses semua fitur pengembangan SIB-K" class="img-fluid" style="max-width:100%;">
          </div>
          <div class="text-end mt-2">
            <a href="<?= esc($crudDiagramUrl) ?>" target="_blank" class="btn btn-sm btn-light"><i class="mdi mdi-open-in-new me-1"></i> Buka gambar ukuran penuh</a>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php foreach ($featureSections as $section): ?>
    <?php $items = is_array($section['items'] ?? null) ? $section['items'] : []; ?>
    <section class="mb-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
        <div class="proto-section-heading">
          <h4 class="mb-1"><?= esc($section['title'] ?? 'Fitur Pengembangan') ?></h4>
          <p class="text-muted mb-0"><?= esc($section['description'] ?? '') ?></p>
        </div>
        <span class="badge bg-light text-dark border align-self-lg-start"><?= count($items) ?> fitur</span>
      </div>

      <div class="row g-3">
        <?php foreach ($items as $key => $item): ?>
          <?php
            $tone = (string) ($item['tone'] ?? 'primary');
            $isAccessible = ! empty($item['is_accessible']);
            $isRemoved = ! empty($item['is_removed']);
            $cardClass = $isAccessible ? '' : ' proto-card-muted';
            $pageCount = count($item['pages'] ?? []);
          ?>
          <div class="col-lg-6 col-xl-4">
            <?php if ($isAccessible): ?>
              <a class="proto-link" href="<?= base_url($item['url'] ?? '#') ?>">
            <?php else: ?>
              <div class="proto-link" aria-label="<?= esc($item['title'] ?? 'Fitur') ?>">
            <?php endif; ?>
              <div class="card proto-card<?= $cardClass ?> h-100">
                <div class="card-body">
                  <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="proto-icon bg-<?= esc($tone) ?> bg-opacity-10 text-<?= esc($tone) ?>">
                      <i class="<?= esc($item['icon'] ?? 'mdi mdi-shape-outline') ?>"></i>
                    </span>
                    <div class="flex-grow-1">
                      <h5 class="mb-1"><?= esc($item['title'] ?? '') ?></h5>
                      <small class="text-muted"><?= esc($item['roles'] ?? '') ?></small>
                    </div>
                  </div>
                  <p class="text-muted mb-2"><?= esc($item['outcome'] ?? '') ?></p>
                  <?php if (! $isAccessible && ! empty($item['access_note'])): ?>
                    <div class="alert alert-light border py-2 px-3 mb-3">
                      <small><?= esc($item['access_note']) ?></small>
                    </div>
                  <?php endif; ?>
                  <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="badge bg-light text-dark">
                      <?= $isRemoved ? 'Tidak dibuat demo' : esc((string) $pageCount) . ' halaman' ?>
                    </span>
                    <?php if ($isRemoved): ?>
                      <span class="btn btn-sm btn-outline-secondary disabled">Dihapus/Digantikan</span>
                    <?php elseif (! $isAccessible): ?>
                      <span class="btn btn-sm btn-outline-secondary disabled">Tidak perlu diuji</span>
                    <?php else: ?>
                      <span class="btn btn-sm <?= ! empty($item['is_tried']) ? 'btn-outline-success' : 'btn-outline-primary' ?>">
                        <?= ! empty($item['is_tried']) ? 'Sudah dicoba' : 'Buka contoh' ?>
                        <i class="mdi mdi-arrow-right ms-1"></i>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php if ($isAccessible): ?>
              </a>
            <?php else: ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <div class="card proto-panel">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
        <div>
          <h5 class="mb-1">Cakupan Halaman Prototipe</h5>
          <p class="text-muted mb-0">Ringkasan ini membantu mengecek apakah seluruh fitur pengembangan sudah punya halaman contoh. Tampilan ini memiliki <?= esc((string) $flowPageCount) ?> halaman alur yang bisa dibuka satu per satu.</p>
        </div>
        <span class="badge bg-primary align-self-lg-start">Tampilan: <?= esc($roleLabel) ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover proto-table mb-0">
          <thead>
            <tr>
              <th>Fitur</th>
              <th>Kategori</th>
              <th>Halaman Contoh</th>
              <th>Aktor</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($featureSections as $section): ?>
              <?php foreach (($section['items'] ?? []) as $item): ?>
                <?php
                  $isAccessible = ! empty($item['is_accessible']);
                  $isRemoved = ! empty($item['is_removed']);
                  $statusClass = $isRemoved ? 'secondary' : ($isAccessible ? (! empty($item['is_tried']) ? 'success' : 'light text-dark') : 'warning');
                  $statusLabel = $isRemoved ? 'Dihapus/Digantikan' : ($isAccessible ? (! empty($item['is_tried']) ? 'Sudah dicoba' : 'Siap dicoba') : 'Tidak perlu diuji peran ini');
                ?>
                <tr>
                  <td class="fw-semibold"><?= esc($item['short_title'] ?? $item['title'] ?? '') ?></td>
                  <td><?= esc($section['title'] ?? '-') ?></td>
                  <td><?= esc(implode(', ', $item['pages'] ?? [])) ?></td>
                  <td><?= esc($item['roles'] ?? '') ?></td>
                  <td>
                    <span class="badge bg-<?= esc($statusClass) ?>">
                      <?= esc($statusLabel) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
