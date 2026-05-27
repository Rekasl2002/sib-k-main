<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .simulation-feature .sim-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .simulation-feature .sim-icon {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 22px;
  }
  .simulation-feature .sim-nav {
    gap: 8px;
  }
  .simulation-feature .sim-nav .btn {
    border-radius: 8px;
    text-align: left;
  }
  .simulation-feature .sim-step {
    position: relative;
    padding-left: 28px;
    min-height: 42px;
  }
  .simulation-feature .sim-step::before {
    content: "";
    position: absolute;
    top: 4px;
    left: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #adb5bd;
    background: #fff;
  }
  .simulation-feature .sim-step.is-active::before {
    border-color: var(--sibk-primary, #1f6f54);
    background: var(--sibk-primary, #1f6f54);
  }
  .simulation-feature .metric-value {
    font-size: 26px;
    line-height: 1;
    font-weight: 700;
  }
  .simulation-feature .sim-preview {
    min-height: 220px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid rgba(15, 23, 42, .08);
    padding: 16px;
  }
  .simulation-feature .sim-alert {
    border-radius: 8px;
  }
  .simulation-feature .sim-browser-bar {
    border-radius: 8px;
    border: 1px solid rgba(15, 23, 42, .08);
    background: #fff;
    padding: 10px 12px;
    font-size: 12px;
  }
  .simulation-feature .sim-browser-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    display: inline-block;
  }
  .simulation-feature .sim-screen-tabs {
    gap: 8px;
  }
  .simulation-feature .sim-screen-tab {
    border-radius: 8px;
    min-height: 38px;
  }
  .simulation-feature .sim-screen {
    display: none;
  }
  .simulation-feature .sim-screen.is-active {
    display: block;
  }
  .simulation-feature .sim-activity {
    border-left: 3px solid rgba(31, 111, 84, .25);
    padding-left: 12px;
  }
  .simulation-feature .sim-work-status {
    border-radius: 8px;
    background: #fff;
    border: 1px solid rgba(15, 23, 42, .08);
  }
  .simulation-feature .sim-table th,
  .simulation-feature .sim-table td {
    white-space: nowrap;
  }
  .simulation-feature .role-switch {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .simulation-feature .role-switch .btn {
    border-radius: 8px;
  }
  .simulation-feature .sim-nav .btn.disabled,
  .simulation-feature .flow-next.disabled {
    pointer-events: none;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features  = is_array($features ?? null) ? $features : [];
$feature   = is_array($feature ?? null) ? $feature : [];
$demo      = is_array($demo ?? null) ? $demo : [];
$activeKey = (string) ($activeKey ?? '');
$metrics   = is_array($demo['metrics'] ?? null) ? $demo['metrics'] : [];
$steps     = is_array($demo['steps'] ?? null) ? $demo['steps'] : [];
$records   = is_array($demo['records'] ?? null) ? $demo['records'] : [];
$form      = is_array($demo['form'] ?? null) ? $demo['form'] : [];
$action    = (string) ($demo['action'] ?? 'Jalankan Simulasi');
$columns   = $records ? array_keys($records[0]) : [];
$featureKeys = array_keys($features);
$activeIndex = array_search($activeKey, $featureKeys, true);
$activeIndex = $activeIndex === false ? 0 : (int) $activeIndex;
$prevFeature = $features[$featureKeys[$activeIndex - 1] ?? ''] ?? null;
$nextFeature = $features[$featureKeys[$activeIndex + 1] ?? ''] ?? null;
$screenLabels = $steps ?: ['Isi data demo', 'Proses data demo', 'Lihat hasil demo'];
$roleMode = (string) ($roleMode ?? '');
$roleLabel = (string) ($roleLabel ?? ($demo['role_label'] ?? 'Pengguna'));
$roleOptions = is_array($roleOptions ?? null) ? $roleOptions : [];
$roleNote = (string) ($demo['role_note'] ?? 'Tampilan dan aksi di halaman ini mengikuti hak akses demo peran aktif.');
$isTried = ! empty($isTried);
$progressUrl = (string) ($progressUrl ?? '');
?>

<div
  class="simulation-feature"
  data-feature="<?= esc($activeKey) ?>"
  data-progress-url="<?= esc(base_url($progressUrl)) ?>"
  data-tried="<?= $isTried ? '1' : '0' ?>">
  <nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('simulation?role=' . rawurlencode($roleMode)) ?>">Simulasi Fitur</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($feature['short_title'] ?? 'Fitur') ?></li>
    </ol>
  </nav>

  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <div class="d-flex align-items-start gap-3">
        <span class="sim-icon bg-<?= esc($feature['tone'] ?? 'primary') ?> bg-opacity-10 text-<?= esc($feature['tone'] ?? 'primary') ?>">
          <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape') ?>"></i>
        </span>
        <div>
          <h2 class="page-title mb-1"><?= esc($feature['title'] ?? 'Simulasi') ?></h2>
          <p class="text-muted mb-0"><?= esc($feature['summary'] ?? '') ?></p>
        </div>
      </div>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="<?= base_url('simulation?role=' . rawurlencode($roleMode)) ?>" class="btn btn-light">
        <i class="mdi mdi-view-grid-outline me-1"></i> Semua Simulasi
      </a>
    </div>
  </div>

  <div class="role-switch p-3 mb-4">
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
      <div>
        <div class="fw-semibold">Mode peran aktif: <?= esc($roleLabel) ?></div>
        <small class="text-muted"><?= esc($roleNote) ?></small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($roleOptions as $key => $label): ?>
          <a
            href="<?= base_url('simulation/' . $activeKey . '?role=' . rawurlencode((string) $key)) ?>"
            class="btn btn-sm <?= $key === $roleMode ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= esc($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap sim-nav mb-4">
    <?php foreach ($features as $key => $item): ?>
      <?php $isActive = ($key === $activeKey); ?>
      <?php $isLocked = ! empty($item['is_locked']); ?>
      <?php if ($isLocked): ?>
        <span class="btn btn-outline-secondary disabled">
          <i class="mdi mdi-lock-outline me-1"></i>
          <?= esc($item['short_title'] ?? $item['title'] ?? $key) ?>
        </span>
      <?php else: ?>
        <a class="btn <?= $isActive ? 'btn-primary' : (! empty($item['is_tried']) ? 'btn-outline-success' : 'btn-outline-primary') ?>" href="<?= base_url($item['url'] ?? '#') ?>">
          <i class="<?= esc($item['icon'] ?? 'mdi mdi-shape') ?> me-1"></i>
          <?= esc($item['short_title'] ?? $item['title'] ?? $key) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div id="simulationNotice" class="alert alert-success sim-alert d-none" role="alert"></div>

  <div class="row g-3 mb-3">
    <?php foreach ($metrics as $metric): ?>
      <div class="col-md-4">
        <div class="card sim-card h-100">
          <div class="card-body">
            <div class="metric-value"><?= esc((string) ($metric['value'] ?? '')) ?></div>
            <div class="text-muted"><?= esc($metric['label'] ?? '') ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3">
    <div class="col-xl-4">
      <div class="card sim-card h-100">
        <div class="card-header bg-white">
          <h5 class="mb-0">Panel Simulasi</h5>
        </div>
        <div class="card-body">
          <?php foreach ($form as $index => $field): ?>
            <div class="mb-3">
              <label class="form-label"><?= esc($field) ?></label>
              <?php if ($index === count($form) - 1 && count($form) > 3): ?>
                <select class="form-select sim-input">
                  <option value="" selected disabled>Contoh: Aktif / Draft / Menunggu</option>
                  <option value="Aktif">Aktif</option>
                  <option value="Draft">Draft</option>
                  <option value="Menunggu">Menunggu</option>
                </select>
              <?php elseif (str_contains(strtolower((string) $field), 'catatan') || str_contains(strtolower((string) $field), 'kronologi')): ?>
                <textarea class="form-control sim-input" rows="3" placeholder="Contoh: isian simulasi untuk <?= esc(strtolower((string) $field)) ?>."></textarea>
              <?php elseif (str_contains(strtolower((string) $field), 'tanggal') || str_contains(strtolower((string) $field), 'periode')): ?>
                <input type="date" class="form-control sim-input">
                <small class="text-muted">Contoh: 2026-05-20</small>
              <?php elseif (str_contains(strtolower((string) $field), 'password')): ?>
                <input type="password" class="form-control sim-input" placeholder="Contoh: password-demo">
              <?php elseif (str_contains(strtolower((string) $field), 'file') || str_contains(strtolower((string) $field), 'logo')): ?>
                <input type="text" class="form-control sim-input" placeholder="Contoh: file-demo.xlsx">
              <?php else: ?>
                <input type="text" class="form-control sim-input" placeholder="Contoh: <?= esc($field) ?> Demo">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <button id="runSimulation" type="button" class="btn btn-primary w-100">
            <i class="mdi mdi-play-circle-outline me-1"></i> <?= esc($action) ?>
          </button>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card sim-card h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Alur Kerja</h5>
          <span class="badge bg-primary"><span id="stepCounter">1</span>/<?= max(1, count($steps)) ?></span>
        </div>
        <div class="card-body">
          <?php foreach ($steps as $index => $step): ?>
            <div class="sim-step mb-3 <?= $index === 0 ? 'is-active' : '' ?>" data-step-index="<?= $index ?>">
              <div class="fw-semibold"><?= esc($step) ?></div>
              <small class="text-muted">Langkah <?= $index + 1 ?> dalam simulasi alur fitur.</small>
            </div>
          <?php endforeach; ?>
          <div class="d-flex gap-2">
            <button id="prevStep" type="button" class="btn btn-outline-secondary flex-fill">
              <i class="mdi mdi-chevron-left"></i>
            </button>
            <button id="nextStep" type="button" class="btn btn-outline-primary flex-fill">
              <i class="mdi mdi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card sim-card h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Layar Kerja Demo</h5>
          <span class="badge bg-light text-dark" id="simulationRoute"><?= esc('/simulation/' . $activeKey . '/halaman-1') ?></span>
        </div>
        <div class="card-body">
          <div class="sim-browser-bar d-flex align-items-center gap-2 mb-3">
            <span class="sim-browser-dot bg-danger"></span>
            <span class="sim-browser-dot bg-warning"></span>
            <span class="sim-browser-dot bg-success"></span>
            <span class="text-muted ms-1" id="simulationScreenTitle"><?= esc($screenLabels[0] ?? 'Halaman demo') ?></span>
          </div>

          <div class="d-flex flex-wrap sim-screen-tabs mb-3" role="tablist">
            <?php foreach ($screenLabels as $index => $label): ?>
              <button
                type="button"
                class="btn btn-sm <?= $index === 0 ? 'btn-primary' : 'btn-outline-primary' ?> sim-screen-tab"
                data-screen-index="<?= $index ?>">
                <?= esc('Halaman ' . ($index + 1)) ?>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="sim-preview" id="simulationPreview">
            <?php foreach ($screenLabels as $index => $label): ?>
              <?php $record = $records[$index % max(1, count($records))] ?? []; ?>
              <div class="sim-screen <?= $index === 0 ? 'is-active' : '' ?>" data-screen-panel="<?= $index ?>">
                <div class="d-flex align-items-start gap-3 mb-3">
                  <span class="sim-icon bg-<?= esc($feature['tone'] ?? 'primary') ?> bg-opacity-10 text-<?= esc($feature['tone'] ?? 'primary') ?>">
                    <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape') ?>"></i>
                  </span>
                  <div>
                    <div class="fw-semibold"><?= esc($label) ?></div>
                    <small class="text-muted"><?= esc($feature['short_title'] ?? $feature['title'] ?? 'Simulasi') ?> berjalan pada mode demo.</small>
                  </div>
                </div>
                <div class="sim-work-status p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Status pekerjaan</span>
                    <span class="badge bg-<?= $index === 0 ? 'warning' : ($index + 1 === count($screenLabels) ? 'success' : 'info') ?>">
                      <?= $index === 0 ? 'Draft' : ($index + 1 === count($screenLabels) ? 'Siap selesai' : 'Diproses') ?>
                    </span>
                  </div>
                  <div class="text-muted">Isi panel kiri, pindah halaman, lalu jalankan aksi untuk membuat hasil demo.</div>
                </div>
                <?php if ($record): ?>
                  <div class="sim-activity">
                    <?php foreach (array_slice($record, 0, 3) as $recordKey => $recordValue): ?>
                      <div class="mb-1"><strong><?= esc((string) $recordKey) ?>:</strong> <?= esc((string) $recordValue) ?></div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="d-flex gap-2 mt-3">
            <button id="openPrevScreen" type="button" class="btn btn-outline-secondary flex-fill">
              <i class="mdi mdi-chevron-left"></i> Halaman
            </button>
            <button id="openNextScreen" type="button" class="btn btn-outline-primary flex-fill">
              Halaman <i class="mdi mdi-chevron-right"></i>
            </button>
          </div>
          <div class="text-muted small mt-2">
            Perpindahan layar ini hanya simulasi antarmuka dan tidak menyimpan data ke database.
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card sim-card mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Data Contoh</h5>
      <span class="badge bg-light text-dark"><span id="recordCount"><?= count($records) ?></span> baris</span>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle sim-table mb-0">
        <thead>
          <tr>
            <?php foreach ($columns as $column): ?>
              <th><?= esc($column) ?></th>
            <?php endforeach; ?>
            <th>Status Demo</th>
          </tr>
        </thead>
        <tbody id="simulationRecordBody">
          <?php foreach ($records as $record): ?>
            <tr>
              <?php foreach ($columns as $column): ?>
                <td><?= esc((string) ($record[$column] ?? '-')) ?></td>
              <?php endforeach; ?>
              <td><span class="badge bg-success">Contoh</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-3">
    <?php if ($prevFeature): ?>
      <a href="<?= base_url($prevFeature['url'] ?? 'simulation') ?>" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> <?= esc($prevFeature['short_title'] ?? 'Sebelumnya') ?>
      </a>
    <?php else: ?>
      <a href="<?= base_url('simulation?role=' . rawurlencode($roleMode)) ?>" class="btn btn-outline-secondary">
        <i class="mdi mdi-view-grid-outline me-1"></i> Daftar Simulasi
      </a>
    <?php endif; ?>

    <?php if ($nextFeature): ?>
      <a
        id="flowNextLink"
        href="<?= $isTried ? base_url($nextFeature['url'] ?? 'simulation') : '#' ?>"
        data-next-url="<?= esc(base_url($nextFeature['url'] ?? 'simulation')) ?>"
        data-next-label="<?= esc('Lanjut ke ' . ($nextFeature['short_title'] ?? 'Simulasi berikutnya')) ?>"
        class="btn <?= $isTried ? 'btn-primary' : 'btn-outline-secondary disabled' ?> flow-next"
        aria-disabled="<?= $isTried ? 'false' : 'true' ?>">
        <?= $isTried ? 'Lanjut ke ' . esc($nextFeature['short_title'] ?? 'Simulasi berikutnya') : 'Coba simulasi ini dulu' ?> <i class="mdi mdi-arrow-right ms-1"></i>
      </a>
    <?php else: ?>
      <a
        id="flowNextLink"
        href="<?= $isTried ? base_url('prototype?role=' . rawurlencode($roleMode)) : '#' ?>"
        data-next-url="<?= esc(base_url('prototype?role=' . rawurlencode($roleMode))) ?>"
        data-next-label="Lanjut ke Prototipe"
        class="btn <?= $isTried ? 'btn-primary' : 'btn-outline-secondary disabled' ?> flow-next"
        aria-disabled="<?= $isTried ? 'false' : 'true' ?>">
        <?= $isTried ? 'Lanjut ke Prototipe' : 'Coba simulasi ini dulu' ?> <i class="mdi mdi-arrow-right ms-1"></i>
      </a>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const root = document.querySelector('.simulation-feature');
  if (!root) return;

  const notice = document.getElementById('simulationNotice');
  const steps = Array.from(root.querySelectorAll('[data-step-index]'));
  const stepCounter = document.getElementById('stepCounter');
  const preview = document.getElementById('simulationPreview');
  const body = document.getElementById('simulationRecordBody');
  const recordCount = document.getElementById('recordCount');
  const screenTabs = Array.from(root.querySelectorAll('[data-screen-index]'));
  const screenPanels = Array.from(root.querySelectorAll('[data-screen-panel]'));
  const routeLabel = document.getElementById('simulationRoute');
  const screenTitle = document.getElementById('simulationScreenTitle');
  const featureKey = root.dataset.feature || 'fitur';
  const progressUrl = root.dataset.progressUrl || '';
  const nextLink = document.getElementById('flowNextLink');
  let hasMarkedProgress = root.dataset.tried === '1';
  let currentStep = 0;

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showNotice(message) {
    if (!notice) return;
    notice.className = 'alert alert-success sim-alert';
    notice.textContent = message;
    notice.classList.remove('d-none');
    markProgress();
  }

  function showValidation(message) {
    if (!notice) return;
    notice.className = 'alert alert-danger sim-alert';
    notice.textContent = message;
    notice.classList.remove('d-none');
  }

  function validateRequiredInputs(inputs) {
    let firstInvalid = null;

    inputs.forEach((input) => {
      const value = String(input.value || '').trim();
      const invalid = value === '';
      input.classList.toggle('is-invalid', invalid);
      if (invalid && !firstInvalid) firstInvalid = input;
    });

    if (firstInvalid) {
      firstInvalid.focus();
      showValidation('Lengkapi semua masukan terlebih dahulu sebelum menjalankan simulasi.');
      return false;
    }

    return true;
  }

  root.querySelectorAll('.sim-input').forEach((input) => {
    input.addEventListener('input', () => input.classList.remove('is-invalid'));
    input.addEventListener('change', () => input.classList.remove('is-invalid'));
  });

  function enableNext(nextUrl) {
    if (!nextLink) return;
    const targetUrl = nextUrl || nextLink.dataset.nextUrl || nextLink.getAttribute('href') || '#';
    nextLink.href = targetUrl;
    nextLink.classList.remove('btn-outline-secondary', 'disabled');
    nextLink.classList.add('btn-primary');
    nextLink.setAttribute('aria-disabled', 'false');
    nextLink.innerHTML = escapeHtml(nextLink.dataset.nextLabel || 'Lanjut') + ' <i class="mdi mdi-arrow-right ms-1"></i>';
  }

  function markProgress() {
    if (hasMarkedProgress || !progressUrl) {
      enableNext();
      return;
    }

    hasMarkedProgress = true;
    fetch(progressUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then((response) => response.ok ? response.json() : null)
      .then((payload) => {
        enableNext(payload && payload.next_url ? payload.next_url : null);
      })
      .catch(() => enableNext());
  }

  function renderStep() {
    steps.forEach((step, index) => {
      step.classList.toggle('is-active', index <= currentStep);
    });
    if (stepCounter) stepCounter.textContent = String(currentStep + 1);
  }

  function setScreen(index) {
    const maxIndex = Math.max(0, Math.max(steps.length, screenPanels.length) - 1);
    currentStep = Math.min(maxIndex, Math.max(0, index));

    renderStep();

    screenPanels.forEach((panel, panelIndex) => {
      panel.classList.toggle('is-active', panelIndex === currentStep);
    });

    screenTabs.forEach((tab, tabIndex) => {
      const isActive = tabIndex === currentStep;
      tab.classList.toggle('btn-primary', isActive);
      tab.classList.toggle('btn-outline-primary', !isActive);
    });

    const activePanel = screenPanels[currentStep];
    const label = activePanel?.querySelector('.fw-semibold')?.textContent?.trim() || 'Halaman demo';
    if (screenTitle) screenTitle.textContent = label;
    if (routeLabel) routeLabel.textContent = '/simulation/' + featureKey + '/halaman-' + String(currentStep + 1);
  }

  document.getElementById('prevStep')?.addEventListener('click', () => {
    setScreen(currentStep - 1);
  });

  document.getElementById('nextStep')?.addEventListener('click', () => {
    setScreen(currentStep + 1);
  });

  document.getElementById('openPrevScreen')?.addEventListener('click', () => {
    setScreen(currentStep - 1);
  });

  document.getElementById('openNextScreen')?.addEventListener('click', () => {
    setScreen(currentStep + 1);
  });

  screenTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      setScreen(Number(tab.dataset.screenIndex || '0'));
    });
  });

  document.getElementById('runSimulation')?.addEventListener('click', () => {
    const inputNodes = Array.from(root.querySelectorAll('.sim-input'));
    if (!validateRequiredInputs(inputNodes)) return;

    const values = inputNodes.map((input) => input.value || '-');
    const title = document.querySelector('.page-title')?.textContent || 'Simulasi';
    const first = values[0] || 'Data simulasi';
    const second = values[1] || 'Detail simulasi';
    const third = values[2] || 'Aktif';

    setScreen(Math.max(0, Math.max(steps.length, screenPanels.length) - 1));
    const activePanel = preview?.querySelector('.sim-screen.is-active') || preview;
    if (activePanel) {
      activePanel.innerHTML =
        '<div class="alert alert-success mb-3">Aksi simulasi berhasil dijalankan. Data operasional aplikasi tidak berubah.</div>' +
        '<div class="fw-semibold mb-2">' + escapeHtml(title) + '</div>' +
        '<div class="text-muted mb-3">Hasil demo dibuat dari input panel kiri.</div>' +
        '<div class="border rounded p-3 bg-white">' +
          '<div><strong>Input utama:</strong> ' + escapeHtml(first) + '</div>' +
          '<div><strong>Detail:</strong> ' + escapeHtml(second) + '</div>' +
          '<div><strong>Status:</strong> ' + escapeHtml(third) + '</div>' +
        '</div>' +
        '<div class="sim-activity mt-3"><div class="fw-semibold">Aktivitas terbaru</div><small class="text-muted">Halaman demo memperlihatkan hasil seperti proses kerja selesai.</small></div>';
    }

    if (body) {
      const columnCount = body.closest('table')?.querySelectorAll('thead th').length || 4;
      const cells = [];
      for (let i = 0; i < columnCount - 1; i++) {
        cells.push('<td>' + escapeHtml(values[i] || ('Demo ' + (i + 1))) + '</td>');
      }
      cells.push('<td><span class="badge bg-primary">Baru disimulasikan</span></td>');
      body.insertAdjacentHTML('afterbegin', '<tr>' + cells.join('') + '</tr>');
    }

    if (recordCount) {
      recordCount.textContent = String(Number(recordCount.textContent || '0') + 1);
    }

    showNotice('Simulasi berhasil dijalankan. Perubahan hanya terjadi pada tampilan halaman ini.');
  });

  setScreen(0);
})();
</script>
<?= $this->endSection() ?>
