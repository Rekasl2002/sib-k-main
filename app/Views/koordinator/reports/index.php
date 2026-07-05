<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/reports/index.php
 * Koordinator BK • Laporan multi-fitur (Perbaikan Kedua — Item #11)
 */
helper(['url']);
$features   = $features ?? [];
$classes    = $classes ?? [];
$counselors = $counselors ?? [];
$students   = $students ?? [];
$valFrom    = $valFrom ?? date('Y-m-01');
$valTo      = $valTo ?? date('Y-m-d');
$valPaper   = $valPaper ?? 'A4';
$valOrient  = $valOrient ?? 'portrait';

$canDownload = !function_exists('has_permission') || has_permission('generate_reports_aggregate');
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Laporan BK (Koordinator BK)</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= site_url('koordinator/dashboard') ?>">Koordinator</a></li>
          <li class="breadcrumb-item active">Laporan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
  <div class="col-xl-4">
    <div class="card filter-compact">
      <div class="card-header py-2 d-flex align-items-start justify-content-between">
        <div>
          <h5 class="mb-1">Filter/Saring Laporan</h5>
          <small class="text-dark">Lingkup seluruh sekolah. Detail rahasia konseling tetap milik Guru BK terkait.</small>
        </div>
        <span class="badge bg-info-subtle text-info border border-info-subtle">Rekap</span>
      </div>
      <div class="card-body">
        <form id="filterForm" method="get" action="<?= route_to('koordinator.reports.preview') ?>" class="row g-3">

          <div class="col-12">
            <label class="form-label fw-semibold">Jenis Laporan <span class="text-danger">*</span></label>
            <div class="border rounded p-2" style="max-height:260px; overflow:auto;">
              <?php foreach ($features as $key => $label): ?>
                <div class="form-check">
                  <input class="form-check-input feat-check" type="checkbox" name="features[]" value="<?= esc($key) ?>" id="feat_<?= esc($key) ?>" <?= $key === 'aggregate' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="feat_<?= esc($key) ?>"><?= esc($label) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="d-flex gap-2 mt-1">
              <button type="button" class="btn btn-sm btn-link p-0" id="checkAll">Pilih semua</button>
              <span class="text-muted">·</span>
              <button type="button" class="btn btn-sm btn-link p-0" id="checkNone">Kosongkan</button>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Ringkasan</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="student_mode" id="modeAll" value="all" checked>
              <label class="btn btn-outline-primary btn-sm" for="modeAll">Semua siswa</label>
              <input type="radio" class="btn-check" name="student_mode" id="modeSingle" value="single">
              <label class="btn btn-outline-primary btn-sm" for="modeSingle">Satu siswa</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Periode</label>
            <div class="row g-2">
              <div class="col-6"><input type="date" name="date_from" class="form-control" value="<?= esc($valFrom) ?>"><div class="form-text text-dark">Dari</div></div>
              <div class="col-6"><input type="date" name="date_to" class="form-control" value="<?= esc($valTo) ?>"><div class="form-text text-dark">Sampai</div></div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Kelas</label>
            <select name="class_id" class="form-select">
              <option value="">Semua Kelas</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= esc($c['id']) ?>"><?= esc($c['class_name'] ?? ('Kelas #' . $c['id'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Guru BK (opsional)</label>
            <select name="counselor_id" class="form-select">
              <option value="">Semua Guru BK</option>
              <?php foreach ($counselors as $u): ?>
                <option value="<?= esc($u['id']) ?>"><?= esc($u['full_name'] ?? ('Pengguna #' . $u['id'])) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-dark">Menyempitkan rekap/asesmen sesuai penanggung jawab.</div>
          </div>

          <div class="col-12 d-none" id="studentWrap">
            <label class="form-label">Siswa</label>
            <select name="student_id" class="form-select">
              <option value="">Pilih Siswa</option>
              <?php foreach ($students as $s): ?>
                <option value="<?= esc($s['id']) ?>"><?= esc(($s['full_name'] ?? '-') . ' - ' . ($s['class_name'] ?? '-') . ' - NISN ' . ($s['nisn'] ?? '-')) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-dark">Wajib dipilih bila mode "Satu siswa".</div>
          </div>

          <div class="col-12">
            <label class="form-label">Opsi Unduhan</label>
            <div class="row g-2">
              <div class="col-6">
                <select name="paper" class="form-select">
                  <option value="A4" <?= strtoupper($valPaper) === 'A4' ? 'selected' : '' ?>>A4</option>
                  <option value="letter" <?= strtolower($valPaper) === 'letter' ? 'selected' : '' ?>>Letter</option>
                  <option value="legal" <?= strtolower($valPaper) === 'legal' ? 'selected' : '' ?>>Legal</option>
                </select>
                <div class="form-text text-dark">Ukuran kertas (PDF)</div>
              </div>
              <div class="col-6">
                <select name="orientation" class="form-select">
                  <option value="portrait" <?= $valOrient === 'portrait' ? 'selected' : '' ?>>Tegak</option>
                  <option value="landscape" <?= $valOrient === 'landscape' ? 'selected' : '' ?>>Mendatar</option>
                </select>
                <div class="form-text text-dark">Arah kertas (PDF)</div>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary"><i class="fas fa-eye me-1"></i> Pratinjau</button>
            <a id="dlPdf" class="btn btn-outline-secondary <?= $canDownload ? '' : 'disabled' ?>" href="#"><i class="fas fa-file-pdf me-1"></i> PDF</a>
            <a id="dlXlsx" class="btn btn-outline-success <?= $canDownload ? '' : 'disabled' ?>" href="#"><i class="fas fa-file-excel me-1"></i> Excel</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Pratinjau</h5>
        <small class="text-dark">Centang jenis laporan, lalu klik "Pratinjau".</small>
      </div>
      <div class="card-body" id="previewArea">
        <div class="text-dark">Pilih saringan di kiri, lalu klik <b>Pratinjau</b>. 📊</div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const form = document.getElementById('filterForm');
  const preview = document.getElementById('previewArea');
  const dlPdf = document.getElementById('dlPdf');
  const dlXlsx = document.getElementById('dlXlsx');
  const studentWrap = document.getElementById('studentWrap');
  const canDownload = <?= $canDownload ? 'true' : 'false' ?>;
  const downloadBase = "<?= route_to('koordinator.reports.download') ?>";

  function qs() { return new URLSearchParams(new FormData(form)).toString(); }
  function syncDownloadLinks() {
    if (!canDownload) { dlPdf.href = '#'; dlXlsx.href = '#'; return; }
    const q = qs();
    dlPdf.href = downloadBase + "?" + q + "&format=pdf";
    dlXlsx.href = downloadBase + "?" + q + "&format=xlsx";
  }
  function syncMode() { studentWrap.classList.toggle('d-none', !document.getElementById('modeSingle').checked); }

  async function loadPreview(e) {
    if (e) e.preventDefault();
    syncMode(); syncDownloadLinks();
    preview.innerHTML = '<div class="text-center text-dark py-4"><div class="spinner-border spinner-border-sm me-2"></div>Memuat pratinjau...</div>';
    try {
      const res = await fetch(form.action + "?" + qs(), { headers: { "X-Requested-With": "XMLHttpRequest" } });
      const html = await res.text();
      preview.innerHTML = res.ok ? html : '<div class="alert alert-danger mb-0">Gagal memuat pratinjau. (' + res.status + ')</div>';
    } catch (err) {
      preview.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat pratinjau. Coba ulang.</div>';
    }
  }

  document.getElementById('checkAll').addEventListener('click', () => { document.querySelectorAll('.feat-check').forEach(c => c.checked = true); syncDownloadLinks(); });
  document.getElementById('checkNone').addEventListener('click', () => { document.querySelectorAll('.feat-check').forEach(c => c.checked = false); syncDownloadLinks(); });
  form.addEventListener('change', () => { syncMode(); syncDownloadLinks(); });
  form.addEventListener('submit', loadPreview);
  syncMode(); syncDownloadLinks();
})();
</script>

<?= $this->endSection() ?>
