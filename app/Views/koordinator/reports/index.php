<?php
/**
 * File Path: app/Views/koordinator/reports/index.php
 *
 * Koordinator BK • Laporan Agregat
 * - Filter + AJAX preview
 * - Export PDF/XLSX (izin: generate_reports)
 * - UI: preset periode, chips ringkasan, simpan filter
 */

?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
helper(['url']);

$classes    = $classes ?? [];
$counselors = $counselors ?? [];
$categories = $categories ?? [];

$valFrom    = $valFrom ?? date('Y-m-01');
$valTo      = $valTo ?? date('Y-m-d');

$valClass     = $valClass ?? '';
$valCounselor = $valCounselor ?? '';
$valCategory  = $valCategory ?? '';

$valPaper   = $valPaper ?? 'A4';
$valOrient  = $valOrient ?? 'portrait';

// permission opsional (kalau helper ada)
$canDownload = true;
if (function_exists('has_permission')) {
    $canDownload = has_permission('generate_reports');
}

function optLabel(array $rows, string $id, string $idKey, string $labelKey, string $fallbackPrefix): string
{
    foreach ($rows as $r) {
        if ((string)($r[$idKey] ?? '') === (string)$id) {
            return (string)($r[$labelKey] ?? ($fallbackPrefix . ' #' . $id));
        }
    }
    return $id ? ($fallbackPrefix . ' #' . $id) : '';
}
$classLabel     = $valClass ? optLabel($classes, $valClass, 'id', 'class_name', 'Kelas') : '';
$counselorLabel = $valCounselor ? optLabel($counselors, $valCounselor, 'id', 'full_name', 'User') : '';
$categoryLabel  = $valCategory ? optLabel($categories, $valCategory, 'id', 'category_name', 'Kategori') : '';

$previewUrlBase  = route_to('koordinator.reports.preview');
$downloadUrlBase = route_to('koordinator.reports.download');
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Laporan Agregat (Koordinator BK)</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= site_url('koordinator/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Laporan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger">
    <?= esc(session()->getFlashdata('error')) ?>
  </div>
<?php endif; ?>

<div class="row">
  <!-- LEFT: FILTER -->
  <div class="col-xl-4">
    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <h5 class="mb-1">Filter Laporan</h5>
            <small class="text-muted">Rekap agregat tanpa menampilkan catatan sesi rahasia (privacy-safe).</small>
          </div>
          <div class="text-end">
            <span class="badge bg-info-subtle text-info border border-info-subtle">Agregat</span>
          </div>
        </div>
      </div>

      <div class="card-body">
        <form id="aggForm" method="get" action="<?= $previewUrlBase ?>" class="row g-3" novalidate>

          <!-- Preset -->
          <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="this_month">
                Bulan Ini
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="last_30">
                30 Hari
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="this_year">
                Tahun Ini
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="semester_ganjil">
                Semester Ganjil
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="semester_genap">
                Semester Genap
              </button>
            </div>
          </div>

          <!-- Date range -->
          <div class="col-12">
            <label class="form-label">Periode</label>
            <div class="row g-2">
              <div class="col-6">
                <input id="dateFrom" type="date" name="date_from" class="form-control" value="<?= esc($valFrom) ?>">
                <div class="form-text">Dari</div>
              </div>
              <div class="col-6">
                <input id="dateTo" type="date" name="date_to" class="form-control" value="<?= esc($valTo) ?>">
                <div class="form-text">Sampai</div>
              </div>
            </div>
            <div id="dateHint" class="form-text text-danger d-none">Tanggal tidak valid: “Dari” tidak boleh lebih besar dari “Sampai”.</div>
          </div>

          <!-- Scope filters -->
          <div class="col-12">
            <label class="form-label">Kelas (opsional)</label>
            <select name="class_id" class="form-select">
              <option value="">Semua Kelas</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= esc($c['id']) ?>" <?= (string)$c['id'] === (string)$valClass ? 'selected' : '' ?>>
                  <?= esc($c['class_name'] ?? ('Kelas #'.$c['id'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Guru BK (opsional)</label>
            <select name="counselor_id" class="form-select">
              <option value="">Semua Guru BK</option>
              <?php foreach ($counselors as $u): ?>
                <option value="<?= esc($u['id']) ?>" <?= (string)$u['id'] === (string)$valCounselor ? 'selected' : '' ?>>
                  <?= esc($u['full_name'] ?? ('User #'.$u['id'])) ?><?= !empty($u['role_name']) ? ' • '.esc($u['role_name']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Filter ini akan mempersempit rekap sesi/sanksi/asesmen sesuai pembuat/penanggung jawab.</div>
          </div>

          <div class="col-12">
            <label class="form-label">Kategori Pelanggaran (opsional)</label>
            <select name="category_id" class="form-select">
              <option value="">Semua Kategori</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= esc($cat['id']) ?>" <?= (string)$cat['id'] === (string)$valCategory ? 'selected' : '' ?>>
                  <?= esc($cat['category_name'] ?? ('Kategori #'.$cat['id'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Filter ini mempengaruhi bagian rekap pelanggaran.</div>
          </div>

          <!-- PDF options -->
          <div class="col-12">
            <label class="form-label">Opsi Export</label>
            <div class="row g-2">
              <div class="col-6">
                <select name="paper" class="form-select">
                  <option value="A4" <?= $valPaper === 'A4' ? 'selected' : '' ?>>A4</option>
                  <option value="letter" <?= $valPaper === 'letter' ? 'selected' : '' ?>>Letter</option>
                  <option value="legal" <?= $valPaper === 'legal' ? 'selected' : '' ?>>Legal</option>
                </select>
                <div class="form-text">Kertas (PDF)</div>
              </div>
              <div class="col-6">
                <select name="orientation" class="form-select">
                  <option value="portrait" <?= $valOrient === 'portrait' ? 'selected' : '' ?>>Portrait</option>
                  <option value="landscape" <?= $valOrient === 'landscape' ? 'selected' : '' ?>>Landscape</option>
                </select>
                <div class="form-text">Orientasi (PDF)</div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-eye me-1"></i> Pratinjau
            </button>

            <button type="button" id="btnReset" class="btn btn-light border">
              <i class="fas fa-rotate-left me-1"></i> Reset
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Export card -->
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div>
            <h6 class="mb-0">Unduh</h6>
            <small class="text-muted">PDF atau Excel (XLSX)</small>
          </div>
          <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Export</span>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <a id="dlPdf" class="btn btn-outline-secondary" href="#" <?= $canDownload ? '' : 'aria-disabled="true"' ?>>
            <i class="fas fa-file-pdf me-1"></i> PDF
          </a>

          <a id="dlXlsx" class="btn btn-outline-success" href="#" <?= $canDownload ? '' : 'aria-disabled="true"' ?>>
            <i class="fas fa-file-excel me-1"></i> Excel
          </a>
        </div>

        <div class="small text-muted mt-2">
          Tips: pilih <b>Landscape</b> kalau tabel rekapnya panjang.
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: PREVIEW -->
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex align-items-start justify-content-between">
        <div>
          <h5 class="mb-1">Pratinjau</h5>
          <div class="d-flex flex-wrap gap-2 mt-2" id="scopeChips">
            <?php if ($classLabel): ?>
              <span class="badge bg-light text-dark border">Kelas: <?= esc($classLabel) ?></span>
            <?php endif; ?>
            <?php if ($counselorLabel): ?>
              <span class="badge bg-light text-dark border">BK: <?= esc($counselorLabel) ?></span>
            <?php endif; ?>
            <?php if ($categoryLabel): ?>
              <span class="badge bg-light text-dark border">Kategori: <?= esc($categoryLabel) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="text-end">
        </div>
      </div>

      <div class="card-body" id="previewArea">
        <div class="text-muted">
          Pilih filter di kiri, lalu klik <b>Pratinjau</b>. 📊
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const form      = document.getElementById('aggForm');
  const preview   = document.getElementById('previewArea');
  const dlPdf     = document.getElementById('dlPdf');
  const dlXlsx    = document.getElementById('dlXlsx');
  const btnReset  = document.getElementById('btnReset');
  const btnCopy   = document.getElementById('btnCopyLink');

  const dateFrom  = document.getElementById('dateFrom');
  const dateTo    = document.getElementById('dateTo');
  const dateHint  = document.getElementById('dateHint');

  const canDownload = <?= $canDownload ? 'true' : 'false' ?>;

  const previewBase  = <?= json_encode($previewUrlBase) ?>;
  const downloadBase = <?= json_encode($downloadUrlBase) ?>;

  const STORAGE_KEY = 'sibk_koordinator_reports_filters_v1';

  function qs() {
    return new URLSearchParams(new FormData(form)).toString();
  }

  function isDateValid() {
    if (!dateFrom.value || !dateTo.value) return true;
    return (dateFrom.value <= dateTo.value);
  }

  function setDateHint() {
    const ok = isDateValid();
    dateHint.classList.toggle('d-none', ok);
    return ok;
  }

  function syncDownloadLinks() {
    const q = qs();
    if (!canDownload) {
      dlPdf.href = '#';
      dlXlsx.href = '#';
      dlPdf.classList.add('disabled');
      dlXlsx.classList.add('disabled');
      dlPdf.setAttribute('tabindex', '-1');
      dlXlsx.setAttribute('tabindex', '-1');
      return;
    }

    dlPdf.classList.remove('disabled');
    dlXlsx.classList.remove('disabled');
    dlPdf.removeAttribute('tabindex');
    dlXlsx.removeAttribute('tabindex');

    dlPdf.href  = downloadBase + "?" + q + "&format=pdf";
    dlXlsx.href = downloadBase + "?" + q + "&format=xlsx";
  }

  function saveFilters() {
    try {
      const data = Object.fromEntries(new FormData(form).entries());
      localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    } catch (e) {}
  }

  function loadFilters() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
      for (const [k,v] of Object.entries(data)) {
        const el = form.querySelector(`[name="${k}"]`);
        if (!el) continue;
        el.value = v;
      }
    } catch (e) {}
  }

  function setPreset(preset) {
    const now = new Date();
    const pad = (n) => String(n).padStart(2,'0');
    const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

    let from, to;

    if (preset === 'this_month') {
      from = new Date(now.getFullYear(), now.getMonth(), 1);
      to   = new Date(now.getFullYear(), now.getMonth()+1, 0);
    } else if (preset === 'last_30') {
      to = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      from = new Date(to);
      from.setDate(from.getDate() - 29);
    } else if (preset === 'this_year') {
      from = new Date(now.getFullYear(), 0, 1);
      to   = new Date(now.getFullYear(), 11, 31);
    } else if (preset === 'semester_ganjil') {
      // Jul–Dec (umumnya)
      const y = now.getFullYear();
      from = new Date(y, 6, 1);
      to   = new Date(y, 11, 31);
    } else if (preset === 'semester_genap') {
      // Jan–Jun (umumnya)
      const y = now.getFullYear();
      from = new Date(y, 0, 1);
      to   = new Date(y, 5, 30);
    } else {
      return;
    }

    dateFrom.value = fmt(from);
    dateTo.value   = fmt(to);
    setDateHint();
    syncDownloadLinks();
    saveFilters();
  }

  async function loadPreview(e){
    if (e) e.preventDefault();

    if (!setDateHint()) {
      preview.innerHTML = '<div class="alert alert-danger mb-0">Tanggal tidak valid. Perbaiki periode terlebih dahulu.</div>';
      return;
    }

    syncDownloadLinks();
    saveFilters();

    preview.innerHTML = `
      <div class="text-center text-muted py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
        Memuat pratinjau...
      </div>
    `;

    try {
      const res = await fetch(previewBase + "?" + qs(), {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      if (!res.ok) {
        const t = await res.text();
        preview.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat pratinjau. (' + res.status + ')</div><div class="small text-muted mt-2">' + (t || '') + '</div>';
        return;
      }

      const html = await res.text();
      preview.innerHTML = html || '<div class="alert alert-warning mb-0">Tidak ada data untuk filter tersebut.</div>';
    } catch (err) {
      preview.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat pratinjau. Coba ulang.</div>';
    }
  }

  function resetForm() {
    // reset ke default “bulan ini”
    setPreset('this_month');

    // kosongkan scope opsional
    const classSel = form.querySelector('[name="class_id"]');
    const csel     = form.querySelector('[name="counselor_id"]');
    const catSel   = form.querySelector('[name="category_id"]');
    if (classSel) classSel.value = '';
    if (csel) csel.value = '';
    if (catSel) catSel.value = '';

    // default export options
    const paper = form.querySelector('[name="paper"]');
    const orient = form.querySelector('[name="orientation"]');
    if (paper) paper.value = 'A4';
    if (orient) orient.value = 'portrait';

    setDateHint();
    syncDownloadLinks();
    saveFilters();
    preview.innerHTML = '<div class="text-muted">Filter direset. Klik <b>Pratinjau</b> untuk memuat data.</div>';
  }

  async function copyLink() {
    // link ke halaman ini (bukan endpoint preview)
    const url = new URL(window.location.href);
    url.search = qs();

    try {
      await navigator.clipboard.writeText(url.toString());
      btnCopy.innerHTML = '<i class="fas fa-check me-1"></i> Tersalin';
      setTimeout(() => btnCopy.innerHTML = '<i class="fas fa-link me-1"></i> Salin Link', 1200);
    } catch (e) {
      // fallback prompt
      window.prompt("Salin link ini:", url.toString());
    }
  }

  // init
  loadFilters();          // restore last used filter
  setDateHint();
  syncDownloadLinks();

  // events
  form.addEventListener('submit', loadPreview);
  form.addEventListener('change', function(){
    setDateHint();
    syncDownloadLinks();
    saveFilters();
  });

  dateFrom.addEventListener('input', () => { setDateHint(); syncDownloadLinks(); saveFilters(); });
  dateTo.addEventListener('input', () => { setDateHint(); syncDownloadLinks(); saveFilters(); });

  document.querySelectorAll('[data-preset]').forEach(btn => {
    btn.addEventListener('click', () => setPreset(btn.getAttribute('data-preset')));
  });

  btnReset.addEventListener('click', resetForm);
  btnCopy.addEventListener('click', copyLink);

  // Optional: auto preview kalau URL punya ?autopreview=1
  const params = new URLSearchParams(window.location.search);
  if (params.get('autopreview') === '1') {
    loadPreview();
  }
})();
</script>

<?= $this->endSection() ?>
