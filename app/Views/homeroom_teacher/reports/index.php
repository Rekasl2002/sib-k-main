<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/homeroom_teacher/reports/index.php
 * Wali Kelas • Laporan multi-fitur (Perbaikan Kedua — Item #11) — garis besar aman.
 */
$features  = $features ?? [];
$classes   = $classes ?? [];
$students  = $students ?? [];
$valFrom   = $valFrom ?? date('Y-m-01');
$valTo     = $valTo ?? date('Y-m-d');
$valClass  = $valClass ?? '';
$valPaper  = $valPaper ?? 'A4';
$valOrient = $valOrient ?? 'portrait';
$noClassAssigned = $noClassAssigned ?? empty($classes);
$hasClasses = !$noClassAssigned;
$isSingleClass = $hasClasses && count($classes) <= 1;
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Laporan Kelas (Wali Kelas)</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Wali Kelas</a></li>
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
      <div class="card-header py-2">
        <h5 class="mb-0">Filter/Saring Laporan</h5>
        <small class="text-dark">Terbatas pada kelas binaan; detail rahasia tidak ditampilkan.</small>
      </div>
      <div class="card-body">
        <?php if (!$hasClasses): ?>
          <div class="alert alert-warning"><b>Tidak ada kelas binaan.</b><br>Akun Wali Kelas ini belum terhubung ke kelas (<code>classes.homeroom_teacher_id</code>).</div>
        <?php endif; ?>

        <form id="filterForm" method="get" action="<?= route_to('homeroom.reports.preview') ?>" class="row g-3" autocomplete="off">

          <div class="col-12">
            <label class="form-label fw-semibold">Jenis Laporan <span class="text-danger">*</span></label>
            <div class="border rounded p-2" style="max-height:240px; overflow:auto;">
              <?php foreach ($features as $key => $label): ?>
                <div class="form-check">
                  <input class="form-check-input feat-check" type="checkbox" name="features[]" value="<?= esc($key) ?>" id="feat_<?= esc($key) ?>" <?= $key === 'counseling' ? 'checked' : '' ?> <?= !$hasClasses ? 'disabled' : '' ?>>
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
              <input type="radio" class="btn-check" name="student_mode" id="modeAll" value="all" checked <?= !$hasClasses ? 'disabled' : '' ?>>
              <label class="btn btn-outline-primary btn-sm" for="modeAll">Semua siswa</label>
              <input type="radio" class="btn-check" name="student_mode" id="modeSingle" value="single" <?= !$hasClasses ? 'disabled' : '' ?>>
              <label class="btn btn-outline-primary btn-sm" for="modeSingle">Satu siswa</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Kelas</label>
            <select name="class_id" class="form-select" <?= (!$hasClasses || $isSingleClass) ? 'disabled' : '' ?>>
              <?php if (!$hasClasses): ?>
                <option value="">(tidak ada kelas binaan)</option>
              <?php else: ?>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= esc($c['id']) ?>" <?= (string) $c['id'] === (string) $valClass ? 'selected' : '' ?>><?= esc($c['class_name'] ?? ('Kelas #' . $c['id'])) ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
            <?php if ($hasClasses && $isSingleClass && $valClass !== ''): ?>
              <input type="hidden" name="class_id" value="<?= esc($valClass) ?>">
            <?php endif; ?>
          </div>

          <div class="col-12">
            <label class="form-label">Periode</label>
            <div class="row g-2">
              <div class="col-6"><input type="date" name="date_from" class="form-control" value="<?= esc($valFrom) ?>" <?= !$hasClasses ? 'disabled' : '' ?>><div class="form-text text-dark">Dari</div></div>
              <div class="col-6"><input type="date" name="date_to" class="form-control" value="<?= esc($valTo) ?>" <?= !$hasClasses ? 'disabled' : '' ?>><div class="form-text text-dark">Sampai</div></div>
            </div>
          </div>

          <div class="col-12 d-none" id="studentWrap">
            <label class="form-label">Siswa</label>
            <select name="student_id" class="form-select" <?= !$hasClasses ? 'disabled' : '' ?>>
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
                <select name="paper" class="form-select" <?= !$hasClasses ? 'disabled' : '' ?>>
                  <option value="A4" <?= strtoupper($valPaper) === 'A4' ? 'selected' : '' ?>>A4</option>
                  <option value="letter" <?= strtolower($valPaper) === 'letter' ? 'selected' : '' ?>>Letter</option>
                  <option value="legal" <?= strtolower($valPaper) === 'legal' ? 'selected' : '' ?>>Legal</option>
                </select>
                <div class="form-text text-dark">Ukuran kertas (PDF)</div>
              </div>
              <div class="col-6">
                <select name="orientation" class="form-select" <?= !$hasClasses ? 'disabled' : '' ?>>
                  <option value="portrait" <?= $valOrient === 'portrait' ? 'selected' : '' ?>>Tegak</option>
                  <option value="landscape" <?= $valOrient === 'landscape' ? 'selected' : '' ?>>Mendatar</option>
                </select>
                <div class="form-text text-dark">Arah kertas (PDF)</div>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary" <?= !$hasClasses ? 'disabled' : '' ?>><i class="fas fa-eye me-1"></i> Pratinjau</button>
            <a id="dlPdf" class="btn btn-outline-secondary <?= !$hasClasses ? 'disabled' : '' ?>" href="#" role="button"><i class="fas fa-file-pdf me-1"></i> PDF</a>
            <a id="dlXlsx" class="btn btn-outline-success <?= !$hasClasses ? 'disabled' : '' ?>" href="#" role="button"><i class="fas fa-file-excel me-1"></i> Excel</a>
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
        <?php if (!$hasClasses): ?>
          <div class="alert alert-warning mb-0">Tidak ada kelas binaan, jadi laporan belum bisa dibuat.</div>
        <?php else: ?>
          <div class="text-dark">Pilih saringan di kiri, lalu klik <b>Pratinjau</b>. 📊</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const HAS_CLASSES = <?= $hasClasses ? 'true' : 'false' ?>;
  const form = document.getElementById('filterForm');
  const preview = document.getElementById('previewArea');
  const dlPdf = document.getElementById('dlPdf');
  const dlXlsx = document.getElementById('dlXlsx');
  const studentWrap = document.getElementById('studentWrap');
  const downloadBase = "<?= route_to('homeroom.reports.download') ?>";

  function qs() { return new URLSearchParams(new FormData(form)).toString(); }
  function syncDownloadLinks() {
    if (!HAS_CLASSES) { dlPdf.href = '#'; dlXlsx.href = '#'; return; }
    const q = qs();
    dlPdf.href = downloadBase + "?" + q + "&format=pdf";
    dlXlsx.href = downloadBase + "?" + q + "&format=xlsx";
  }
  function syncMode() { studentWrap.classList.toggle('d-none', !document.getElementById('modeSingle').checked); }

  async function loadPreview(e) {
    if (e) e.preventDefault();
    if (!HAS_CLASSES) return;
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

  [dlPdf, dlXlsx].forEach(a => a.addEventListener('click', e => { if (!HAS_CLASSES || a.classList.contains('disabled')) e.preventDefault(); }));
  const ca = document.getElementById('checkAll'); if (ca) ca.addEventListener('click', () => { document.querySelectorAll('.feat-check').forEach(c => c.checked = true); syncDownloadLinks(); });
  const cn = document.getElementById('checkNone'); if (cn) cn.addEventListener('click', () => { document.querySelectorAll('.feat-check').forEach(c => c.checked = false); syncDownloadLinks(); });
  form.addEventListener('change', () => { syncMode(); syncDownloadLinks(); });
  form.addEventListener('submit', loadPreview);
  syncMode(); syncDownloadLinks();
})();
</script>

<?= $this->endSection() ?>
