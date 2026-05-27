<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .simulation-page .sim-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    transition: border-color .18s ease, transform .18s ease;
  }
  .simulation-page .sim-link {
    color: inherit;
    text-decoration: none;
  }
  .simulation-page .sim-link:hover .sim-card {
    border-color: rgba(31, 111, 84, .35);
    transform: translateY(-1px);
  }
  .simulation-page .sim-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 22px;
  }
  .simulation-page .metric-value {
    font-size: 28px;
    line-height: 1;
    font-weight: 700;
  }
  .simulation-page .role-switch {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .simulation-page .role-switch .btn {
    border-radius: 8px;
  }
  .simulation-page .sim-card.is-locked {
    opacity: .62;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features = is_array($features ?? null) ? $features : [];
$stats    = is_array($stats ?? null) ? $stats : [];
$roleMode = (string) ($roleMode ?? '');
$roleLabel = (string) ($roleLabel ?? 'Pengguna');
$roleOptions = is_array($roleOptions ?? null) ? $roleOptions : [];
$firstFeature = $features ? reset($features) : [];
$startUrl = (string) ($startUrl ?? ($firstFeature['url'] ?? 'simulation'));
?>

<div class="simulation-page">
  <nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Simulasi Fitur</li>
    </ol>
  </nav>

  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <h2 class="page-title mb-2">Simulasi Fitur</h2>
      <p class="text-muted mb-0">
        Area demo untuk fitur yang sudah berjalan di SIB-K. Seluruh interaksi di sini memakai data contoh dan tidak mengubah data operasional.
      </p>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="<?= base_url($startUrl) ?>" class="btn btn-primary">
        <i class="mdi mdi-play-circle-outline me-1"></i> Mulai Simulasi
      </a>
    </div>
  </div>

  <div class="role-switch p-3 mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <div class="fw-semibold">Mode peran aktif: <?= esc($roleLabel) ?></div>
        <small class="text-muted">Daftar fitur dan isi simulasi mengikuti kebutuhan role yang dipilih.</small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($roleOptions as $key => $label): ?>
          <a
            href="<?= base_url('simulation?role=' . rawurlencode((string) $key)) ?>"
            class="btn btn-sm <?= $key === $roleMode ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= esc($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
      <?php $tone = $stat['tone'] ?? 'primary'; ?>
      <div class="col-md-6 col-xl-3">
        <div class="card sim-card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <span class="sim-icon bg-<?= esc($tone) ?> bg-opacity-10 text-<?= esc($tone) ?>">
              <i class="<?= esc($stat['icon'] ?? 'mdi mdi-chart-box') ?>"></i>
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

  <div class="row g-3">
    <?php foreach ($features as $item): ?>
      <?php $tone = $item['tone'] ?? 'primary'; ?>
      <?php $isLocked = ! empty($item['is_locked']); ?>
      <?php $isTried = ! empty($item['is_tried']); ?>
      <div class="col-lg-6 col-xl-4">
        <?php if (! $isLocked): ?>
          <a class="sim-link" href="<?= base_url($item['url'] ?? '#') ?>">
        <?php else: ?>
          <div class="sim-link" aria-disabled="true">
        <?php endif; ?>
          <div class="card sim-card h-100 <?= $isLocked ? 'is-locked' : '' ?>">
            <div class="card-body">
              <div class="d-flex align-items-start gap-3 mb-3">
                <span class="sim-icon bg-<?= esc($tone) ?> bg-opacity-10 text-<?= esc($tone) ?>">
                  <i class="<?= esc($item['icon'] ?? 'mdi mdi-shape') ?>"></i>
                </span>
                <div>
                  <h5 class="mb-1"><?= esc($item['title'] ?? '') ?></h5>
                  <small class="text-muted"><?= esc($item['roles'] ?? '') ?></small>
                </div>
              </div>
              <p class="text-muted mb-3"><?= esc($item['summary'] ?? '') ?></p>
              <span class="btn btn-sm <?= $isLocked ? 'btn-outline-secondary disabled' : ($isTried ? 'btn-outline-success' : 'btn-outline-primary') ?>">
                <?php if ($isLocked): ?>
                  <i class="mdi mdi-lock-outline me-1"></i> Coba langkah sebelumnya
                <?php elseif ($isTried): ?>
                  <i class="mdi mdi-check-circle-outline me-1"></i> Sudah dicoba
                <?php else: ?>
                  Coba Simulasi <i class="mdi mdi-arrow-right ms-1"></i>
                <?php endif; ?>
              </span>
            </div>
          </div>
        <?php if (! $isLocked): ?>
          </a>
        <?php else: ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?= $this->endSection() ?>
