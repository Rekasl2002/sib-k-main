<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .prototype-diagram-frame {
    max-height: 520px;
    overflow: auto;
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #f8fafc;
  }
  .prototype-diagram-frame img {
    width: 100%;
    min-width: 680px;
    height: auto;
    display: block;
  }
  @media (max-width: 767.98px) {
    .prototype-diagram-frame img {
      min-width: 560px;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features = is_array($features ?? null) ? $features : [];
$feature = is_array($feature ?? null) ? $feature : [];
$page = is_array($page ?? null) ? $page : [];
$roleOptions = is_array($roleOptions ?? null) ? $roleOptions : [];
$roleSummary = is_array($roleSummary ?? null) ? $roleSummary : [];
$demoScreens = is_array($demoScreens ?? null) ? $demoScreens : [];
$diagramImages = is_array($diagramImages ?? null) ? $diagramImages : [];
$featureKey = (string) ($featureKey ?? '');
$pageKey = (string) ($pageKey ?? 'list');
$roleMode = (string) ($roleMode ?? '');
$roleLabel = (string) ($roleLabel ?? 'Pengguna');
$demoStartUrl = (string) ($demoStartUrl ?? 'prototype');
$tone = (string) ($feature['tone'] ?? 'primary');
$timeline = is_array($page['timeline'] ?? null) ? $page['timeline'] : [];
$actions = is_array($page['actions'] ?? null) ? $page['actions'] : [];

if (! function_exists('prototype_screen_type_label')) {
    function prototype_screen_type_label(string $type): string
    {
        return match ($type) {
            'dashboard' => 'Dashboard',
            'list' => 'Daftar',
            'form', 'compose', 'answer' => 'Form',
            'detail' => 'Detail',
            'conversation' => 'Percakapan',
            'catalog' => 'Katalog',
            'calendar' => 'Jadwal',
            'report' => 'Laporan',
            'assessment_questions' => 'Pertanyaan',
            'assessment_answer' => 'Pengerjaan Siswa',
            default => 'Halaman',
        };
    }
}
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0"><?= esc($feature['title'] ?? 'Fitur Prototipe') ?></h4>
        <p class="text-muted mb-0">Pembuka fitur sebelum masuk ke tampilan halaman aplikasi.</p>
      </div>
      <div class="page-title-right">
        <div class="d-flex flex-wrap justify-content-sm-end gap-2 mb-2">
          <a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="mdi mdi-view-grid-outline me-1"></i> Halaman Awal Prototipe
          </a>
        </div>
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>">Prototipe</a></li>
          <li class="breadcrumb-item active"><?= esc($feature['short_title'] ?? 'Fitur') ?></li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="d-flex align-items-start gap-3">
              <div class="avatar-sm rounded bg-soft-<?= esc($tone) ?> d-flex align-items-center justify-content-center">
                <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape-outline') ?> text-<?= esc($tone) ?> font-size-22"></i>
              </div>
              <div>
                <h4 class="mb-2"><?= esc($feature['title'] ?? '') ?></h4>
                <p class="text-muted mb-2"><?= esc($feature['outcome'] ?? '') ?></p>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge bg-light text-dark border">Tampilan: <?= esc($roleLabel) ?></span>
                  <span class="badge bg-light text-dark border"><?= esc($feature['roles'] ?? '') ?></span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="<?= base_url($demoStartUrl) ?>" class="btn btn-primary btn-lg">
              <i class="mdi mdi-monitor-eye me-1"></i> Lihat Halaman-Halaman Demo
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($diagramImages): ?>
  <div class="row mb-3">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
          <div>
            <h5 class="mb-0">Diagram Rancangan Fitur</h5>
            <small class="text-muted">Activity Diagram dan Use Case Diagram untuk fitur ini.</small>
          </div>
          <span class="badge bg-light text-dark border align-self-md-center"><?= count($diagramImages) ?> diagram</span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach ($diagramImages as $diagram): ?>
              <?php
                $imageUrl = base_url('prototype/diagram-image/' . rawurlencode((string) ($diagram['type'] ?? '')) . '/' . rawurlencode((string) ($diagram['file'] ?? '')));
              ?>
              <div class="col-xl-6">
                <div class="border rounded h-100">
                  <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <h6 class="mb-0"><?= esc($diagram['label'] ?? 'Diagram') ?></h6>
                    <a href="<?= $imageUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary">Buka gambar</a>
                  </div>
                  <?php if (! empty($diagram['description'])): ?>
                    <div class="p-3 pt-2 small text-muted border-bottom"><i class="mdi mdi-information-outline me-1"></i><?= esc($diagram['description']) ?></div>
                  <?php endif; ?>
                  <div class="prototype-diagram-frame">
                    <img src="<?= $imageUrl ?>" alt="<?= esc(($diagram['label'] ?? 'Diagram') . ' ' . ($feature['title'] ?? 'Fitur')) ?>">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Halaman Demo Yang Tersedia</h5>
        <span class="badge bg-primary"><?= count($demoScreens) ?> halaman</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th width="6%">No</th>
                <th>Nama Halaman</th>
                <th>Jenis</th>
                <th width="16%">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php $demoNumber = 1; ?>
              <?php foreach ($demoScreens as $screen): ?>
                <tr>
                  <td><?= $demoNumber++ ?></td>
                  <td class="fw-semibold"><?= esc($screen['title'] ?? '-') ?></td>
                  <td><span class="badge bg-light text-dark border"><?= esc(prototype_screen_type_label((string) ($screen['type'] ?? ''))) ?></span></td>
                  <td>
                    <a href="<?= base_url($screen['url'] ?? $demoStartUrl) ?>" class="btn btn-sm btn-outline-primary">
                      Buka
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Alur Singkat Fitur</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <?php foreach ($timeline as $step): ?>
            <div class="col-md-6 mb-2">
              <div class="d-flex gap-2">
                <i class="mdi mdi-check-circle-outline text-success mt-1"></i>
                <div><?= esc($step) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (! $timeline): ?>
            <div class="col-12 text-muted">Alur fitur mengikuti rancangan activity diagram dan halaman demo yang tersedia.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Sudut Pandang Peran</h5>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($roleOptions as $key => $label): ?>
            <a
              href="<?= base_url('prototype/flow/' . $featureKey . '/' . $pageKey . '?role=' . rawurlencode((string) $key)) ?>"
              class="btn btn-sm <?= $key === $roleMode ? 'btn-primary' : 'btn-outline-primary' ?>">
              <?= esc($label) ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php foreach ($roleSummary as $summary): ?>
          <div class="d-flex gap-2 mb-2">
            <i class="mdi mdi-shield-check-outline text-primary mt-1"></i>
            <div class="text-muted"><?= esc($summary) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Aksi Dalam Fitur</h5>
      </div>
      <div class="card-body">
        <?php foreach ($actions as $action): ?>
          <div class="d-flex gap-2 mb-2">
            <i class="mdi mdi-chevron-right text-secondary mt-1"></i>
            <div><?= esc($action) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (! $actions): ?>
          <p class="text-muted mb-0">Aksi akan menyesuaikan hak akses pengguna di halaman demo.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if (! $diagramImages): ?>
      <div class="card">
        <div class="card-body">
          <h5 class="mb-2">Catatan</h5>
          <p class="text-muted mb-0">Diagram fitur ini belum tersedia dalam folder gambar draw.io.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($features as $key => $item): ?>
            <a
              href="<?= base_url($item['url'] ?? '#') ?>"
              class="btn btn-sm <?= $key === $featureKey ? 'btn-primary' : 'btn-outline-primary' ?>">
              <?= esc($item['short_title'] ?? $item['title'] ?? $key) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
