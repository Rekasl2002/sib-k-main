<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .prototype-feature .proto-card,
  .prototype-feature .proto-panel {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .prototype-feature .proto-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 22px;
  }
  .prototype-feature .role-switch,
  .prototype-feature .proto-workbar {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .prototype-feature .role-switch .btn,
  .prototype-feature .proto-nav .btn,
  .prototype-feature .proto-workbar .btn {
    border-radius: 8px;
  }
  .prototype-feature .proto-nav {
    gap: 8px;
  }
  .prototype-feature .proto-table td,
  .prototype-feature .proto-table th {
    vertical-align: middle;
    white-space: nowrap;
  }
  .prototype-feature .proto-route {
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid rgba(15, 23, 42, .08);
    padding: 10px 12px;
    font-size: 12px;
  }
  .prototype-feature .proto-step {
    position: relative;
    padding-left: 24px;
    min-height: 34px;
  }
  .prototype-feature .proto-step::before {
    content: "";
    position: absolute;
    left: 0;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid var(--sibk-primary, #1f6f54);
    background: #fff;
  }
  .prototype-feature .proto-step::after {
    content: "";
    position: absolute;
    left: 5px;
    top: 18px;
    bottom: -12px;
    width: 2px;
    background: rgba(31, 111, 84, .18);
  }
  .prototype-feature .proto-step:last-child::after {
    display: none;
  }
  .prototype-feature .proto-empty {
    border: 1px dashed rgba(15, 23, 42, .18);
    border-radius: 8px;
    background: #f8fafc;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features = is_array($features ?? null) ? $features : [];
$feature = is_array($feature ?? null) ? $feature : [];
$demo = is_array($demo ?? null) ? $demo : [];
$activeKey = (string) ($activeKey ?? '');
$roleMode = (string) ($roleMode ?? '');
$roleLabel = (string) ($roleLabel ?? ($demo['role_label'] ?? 'Pengguna'));
$roleOptions = is_array($roleOptions ?? null) ? $roleOptions : [];
$metrics = is_array($demo['metrics'] ?? null) ? $demo['metrics'] : [];
$records = is_array($demo['records'] ?? null) ? $demo['records'] : [];
$formFields = is_array($demo['form_fields'] ?? null) ? $demo['form_fields'] : [];
$steps = is_array($demo['steps'] ?? null) ? $demo['steps'] : [];
$detailCards = is_array($demo['detail_cards'] ?? null) ? $demo['detail_cards'] : [];
$allowedActions = is_array($demo['allowed_actions'] ?? null) ? $demo['allowed_actions'] : [];
$roleNote = (string) ($demo['role_note'] ?? 'Tampilan dan aksi prototipe mengikuti hak akses contoh peran aktif.');
$privacyNote = (string) ($demo['privacy_note'] ?? '');
$pages = is_array($feature['pages'] ?? null) ? $feature['pages'] : [];
$headers = $records ? array_keys($records[0]) : [];
$isTried = ! empty($isTried);
$progressUrl = (string) ($progressUrl ?? '');

if (! function_exists('prototype_status_tone')) {
    function prototype_status_tone(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'diajukan', 'draft', 'draf', 'belum dibaca', 'menunggu konfirmasi', 'belum selesai' => 'warning',
            'ditinjau', 'aktif', 'dijadwalkan', 'terjadwal', 'berjalan' => 'info',
            'diterima', 'selesai', 'siap', 'publik', 'dibaca', 'sudah dicoba' => 'success',
            'ditolak', 'mendesak' => 'danger',
            'rahasia bk', 'rahasia tinggi', 'terbatas' => 'dark',
            default => 'secondary',
        };
    }
}
?>

<div
  class="prototype-feature"
  data-feature="<?= esc($activeKey) ?>"
  data-progress-url="<?= esc(base_url($progressUrl)) ?>"
  data-tried="<?= $isTried ? '1' : '0' ?>">
  <nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>">Prototipe</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($feature['short_title'] ?? 'Fitur') ?></li>
    </ol>
  </nav>

  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <div class="d-flex align-items-start gap-3">
        <span class="proto-icon bg-<?= esc($feature['tone'] ?? 'primary') ?> bg-opacity-10 text-<?= esc($feature['tone'] ?? 'primary') ?>">
          <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape-outline') ?>"></i>
        </span>
        <div>
          <h2 class="page-title mb-1"><?= esc($feature['title'] ?? 'Prototipe') ?></h2>
          <p class="text-muted mb-0"><?= esc($feature['outcome'] ?? '') ?></p>
        </div>
      </div>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>" class="btn btn-light">
        <i class="mdi mdi-view-grid-outline me-1"></i> Semua Contoh
      </a>
    </div>
  </div>

  <div class="role-switch p-3 mb-4">
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
      <div>
        <div class="fw-semibold">Sudut pandang aktif: <?= esc($roleLabel) ?></div>
        <small class="text-muted"><?= esc($roleNote) ?></small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($roleOptions as $key => $label): ?>
          <a
            href="<?= base_url('prototype/' . $activeKey . '?role=' . rawurlencode((string) $key)) ?>"
            class="btn btn-sm <?= $key === $roleMode ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= esc($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap proto-nav mb-4">
    <?php foreach ($features as $key => $item): ?>
      <?php $isActive = $key === $activeKey; ?>
      <a class="btn <?= $isActive ? 'btn-primary' : (! empty($item['is_tried']) ? 'btn-outline-success' : 'btn-outline-primary') ?>" href="<?= base_url($item['url'] ?? '#') ?>">
        <i class="<?= esc($item['icon'] ?? 'mdi mdi-shape-outline') ?> me-1"></i>
        <?= esc($item['short_title'] ?? $item['title'] ?? $key) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div id="prototypeNotice" class="alert alert-info d-none" role="alert"></div>

  <div class="proto-workbar p-3 mb-4">
    <div class="row g-3 align-items-center">
      <div class="col-xl-4">
        <div class="proto-route d-flex align-items-center gap-2">
          <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape-outline') ?>"></i>
          <span>Halaman contoh: <?= esc($feature['short_title'] ?? $feature['title'] ?? 'Fitur') ?> untuk <?= esc($roleLabel) ?></span>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($pages as $page): ?>
            <span class="badge bg-light text-dark"><?= esc($page) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-xl-4 text-xl-end">
        <button type="button" id="markPrototypeTried" class="btn <?= $isTried ? 'btn-outline-success' : 'btn-outline-primary' ?>">
          <i class="mdi mdi-check-all me-1"></i> <?= $isTried ? 'Sudah Dicoba' : 'Tandai Sudah Dicoba' ?>
        </button>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <?php foreach ($metrics as $metric): ?>
      <?php $tone = (string) ($metric['tone'] ?? 'primary'); ?>
      <div class="col-md-6 col-xl-3">
        <div class="card proto-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div>
                <div class="metric-value h3 mb-1"><?= esc((string) ($metric['value'] ?? '-')) ?></div>
                <div class="text-muted"><?= esc($metric['label'] ?? '') ?></div>
              </div>
            <span class="badge bg-<?= esc($tone) ?> bg-opacity-10 text-<?= esc($tone) ?>">Contoh</span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($privacyNote !== ''): ?>
    <div class="alert alert-warning d-flex gap-2 align-items-start">
      <i class="mdi mdi-lock-alert-outline mt-1"></i>
      <div><?= esc($privacyNote) ?></div>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card proto-card mb-4">
        <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between gap-2">
          <div>
            <h5 class="mb-0">Data Contoh</h5>
            <small class="text-muted">Contoh data yang akan tampil pada halaman aplikasi.</small>
          </div>
          <span class="badge bg-light text-dark align-self-md-center"><?= count($records) ?> data</span>
        </div>
        <div class="card-body">
          <?php if ($records && $headers): ?>
            <div class="table-responsive">
              <table class="table table-hover proto-table mb-0" id="prototypeDataTable">
                <thead>
                  <tr>
                    <?php foreach ($headers as $header): ?>
                      <th><?= esc($header) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody id="prototypeDataBody">
                  <?php foreach ($records as $row): ?>
                    <tr>
                      <?php foreach ($headers as $header): ?>
                        <?php $value = (string) ($row[$header] ?? '-'); ?>
                        <td>
                          <?php if (in_array(strtolower($header), ['status', 'prioritas', 'akses'], true)): ?>
                            <span class="badge bg-<?= esc(prototype_status_tone($value)) ?>"><?= esc($value) ?></span>
                          <?php else: ?>
                            <?= esc($value) ?>
                          <?php endif; ?>
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="proto-empty p-4 text-center text-muted">Belum ada data contoh untuk tampilan ini.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="row g-3">
        <?php foreach ($detailCards as $card): ?>
          <div class="col-md-6">
            <div class="card proto-card h-100">
              <div class="card-body">
                <h5 class="mb-2"><?= esc($card['title'] ?? 'Detail') ?></h5>
                <p class="text-muted mb-0"><?= esc($card['body'] ?? '') ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card proto-card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0"><?= esc($demo['form_title'] ?? 'Form Contoh') ?></h5>
        </div>
        <div class="card-body">
          <?php if ($formFields): ?>
            <form id="prototypeDemoForm">
              <?php foreach ($formFields as $index => $field): ?>
                <?php
                  $label = (string) ($field['label'] ?? 'Field');
                  $type = (string) ($field['type'] ?? 'text');
                  $name = 'field_' . $index;
                  $placeholder = (string) ($field['placeholder'] ?? '');
                  $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                ?>
                <div class="mb-3">
                  <label class="form-label" for="<?= esc($name) ?>"><?= esc($label) ?></label>
                  <?php if ($type === 'select'): ?>
                    <select class="form-select" id="<?= esc($name) ?>" data-proto-input data-label="<?= esc($label) ?>">
                      <option value="" selected disabled>Pilih <?= esc(strtolower($label)) ?></option>
                      <?php foreach ($options as $option): ?>
                        <option value="<?= esc($option) ?>"><?= esc($option) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php elseif ($type === 'textarea'): ?>
                    <textarea class="form-control" id="<?= esc($name) ?>" rows="4" placeholder="<?= esc($placeholder) ?>" data-proto-input data-label="<?= esc($label) ?>"></textarea>
                  <?php else: ?>
                    <input class="form-control" id="<?= esc($name) ?>" type="text" placeholder="<?= esc($placeholder) ?>" data-proto-input data-label="<?= esc($label) ?>">
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-primary w-100">
                <i class="mdi mdi-content-save-outline me-1"></i> Simpan Contoh
              </button>
            </form>
          <?php else: ?>
            <div class="proto-empty p-4 text-center text-muted">Tidak ada formulir contoh pada halaman ini.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card proto-card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Alur Halaman</h5>
        </div>
        <div class="card-body">
          <?php foreach ($steps as $step): ?>
            <div class="proto-step mb-3">
              <div class="fw-semibold"><?= esc($step) ?></div>
              <small class="text-muted">Tahap ini menunjukkan alur penggunaan halaman.</small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card proto-card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Aksi Peran Ini</h5>
        </div>
        <div class="card-body">
          <?php foreach ($allowedActions as $action): ?>
            <div class="d-flex gap-2 mb-2">
              <i class="mdi mdi-check-circle-outline text-success mt-1"></i>
              <div><?= esc($action) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const root = document.querySelector('.prototype-feature');
  if (!root) return;

  const notice = document.getElementById('prototypeNotice');
  const progressUrl = root.dataset.progressUrl || '';
  let hasMarkedProgress = root.dataset.tried === '1';

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showNotice(message, type) {
    if (!notice) return;
    notice.className = 'alert alert-' + (type || 'info');
    notice.textContent = message;
    notice.classList.remove('d-none');
  }

  function markProgress() {
    if (hasMarkedProgress || !progressUrl) return;
    hasMarkedProgress = true;

    fetch(progressUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    }).catch(function () {});

    const button = document.getElementById('markPrototypeTried');
    if (button) {
      button.classList.remove('btn-outline-primary');
      button.classList.add('btn-outline-success');
      button.innerHTML = '<i class="mdi mdi-check-all me-1"></i> Sudah Dicoba';
    }
  }

  function validateInputs(inputs) {
    let firstInvalid = null;
    inputs.forEach(function (input) {
      const invalid = String(input.value || '').trim() === '';
      input.classList.toggle('is-invalid', invalid);
      if (invalid && !firstInvalid) firstInvalid = input;
    });

    if (firstInvalid) {
      firstInvalid.focus();
      showNotice('Lengkapi semua isian contoh terlebih dahulu.', 'danger');
      return false;
    }

    return true;
  }

  root.querySelectorAll('[data-proto-input]').forEach(function (input) {
    input.addEventListener('input', function () { input.classList.remove('is-invalid'); });
    input.addEventListener('change', function () { input.classList.remove('is-invalid'); });
  });

  document.getElementById('markPrototypeTried')?.addEventListener('click', function () {
    markProgress();
    showNotice('Halaman prototipe ini sudah ditandai dicoba untuk sudut pandang aktif.', 'success');
  });

  document.getElementById('prototypeDemoForm')?.addEventListener('submit', function (event) {
    event.preventDefault();
    const inputs = Array.from(root.querySelectorAll('[data-proto-input]'));
    if (!validateInputs(inputs)) return;

    const table = document.getElementById('prototypeDataTable');
    const body = document.getElementById('prototypeDataBody');
    const headers = table ? Array.from(table.querySelectorAll('thead th')).map(function (th) { return th.textContent.trim(); }) : [];
    const values = inputs.map(function (input) { return String(input.value || '').trim(); });

    if (body && headers.length) {
      const randomCode = 'DMO-' + String(Math.floor(Math.random() * 900) + 100);
      let valueIndex = 0;
      const cells = headers.map(function (header, index) {
        const normalized = header.toLowerCase();
        let value;
        if (index === 0 && ['kode', 'id', 'waktu'].includes(normalized)) {
          value = randomCode;
        } else if (normalized === 'status') {
          value = '<span class="badge bg-primary">Contoh Baru</span>';
          return '<td>' + value + '</td>';
        } else {
          value = values[valueIndex] || values[values.length - 1] || 'Data contoh';
          valueIndex = Math.min(valueIndex + 1, values.length - 1);
        }
        return '<td>' + escapeHtml(value) + '</td>';
      });
      body.insertAdjacentHTML('afterbegin', '<tr>' + cells.join('') + '</tr>');
    }

    inputs.forEach(function (input) { input.value = ''; });
    markProgress();
    showNotice('Data contoh berhasil ditambahkan. Perubahan ini hanya terjadi di halaman prototipe.', 'success');
  });
})();
</script>
<?= $this->endSection() ?>
