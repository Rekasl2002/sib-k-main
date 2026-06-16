<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$classes = $classes ?? [];
$students = $students ?? [];

$valFrom  = $valFrom ?? date('Y-m-01');
$valTo    = $valTo ?? date('Y-m-d');
$valClass = $valClass ?? '';
$valMode = $valMode ?? 'class_summary';
$valStudent = $valStudent ?? '';

$valPaper  = $valPaper ?? 'A4';
$valOrient = $valOrient ?? 'portrait';

$hasClasses    = !empty($classes);
$isSingleClass = $hasClasses && count($classes) <= 1;
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Laporan Kelas (Wali Kelas)</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= site_url('homeroom_teacher/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Laporan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-4">
    <div class="card filter-compact">
      <div class="card-header py-2">
        <h5 class="mb-0">Filter/Saring Laporan</h5>
      </div>

      <div class="card-body">

        <?php if (!$hasClasses): ?>
          <div class="alert alert-warning">
            <b>Tidak ada kelas binaan.</b><br>
            Akun Wali Kelas ini belum terhubung ke kelas (kolom <code>classes.homeroom_teacher_id</code>).
          </div>
        <?php endif; ?>

        <form id="classReportForm" method="get" action="<?= route_to('homeroom.reports.preview') ?>" class="row g-3" autocomplete="off">

          <div class="col-12">
            <label class="form-label">Jenis Laporan</label>
            <select name="mode" class="form-select" id="modeSelect" <?= !$hasClasses ? 'disabled' : '' ?>>
              <option value="class_summary" <?= $valMode === 'class_summary' ? 'selected' : '' ?>>Ringkasan Kelas</option>
              <option value="student_individual" <?= $valMode === 'student_individual' ? 'selected' : '' ?>>Per Siswa</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Kelas</label>

            <select
              name="class_id"
              class="form-select"
              <?= (!$hasClasses || $isSingleClass) ? 'disabled' : '' ?>
            >
              <?php if (!$hasClasses): ?>
                <option value="">(tidak ada kelas binaan)</option>
              <?php else: ?>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= esc($c['id']) ?>" <?= (string)$c['id'] === (string)$valClass ? 'selected' : '' ?>>
                    <?= esc($c['class_name'] ?? ('Kelas #'.$c['id'])) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>

            <?php
            // Penting: select disabled tidak ikut FormData, jadi kita kirim via hidden input.
            if ($hasClasses && $isSingleClass && $valClass !== ''): ?>
              <input type="hidden" name="class_id" value="<?= esc($valClass) ?>">
            <?php endif; ?>
          </div>

          <div class="col-12">
            <label class="form-label">Periode</label>
            <div class="row g-2">
              <div class="col-6">
                <input type="date" name="date_from" class="form-control" value="<?= esc($valFrom) ?>" <?= !$hasClasses ? 'disabled' : '' ?>>
                <div class="form-text">Dari</div>
              </div>
              <div class="col-6">
                <input type="date" name="date_to" class="form-control" value="<?= esc($valTo) ?>" <?= !$hasClasses ? 'disabled' : '' ?>>
                <div class="form-text">Sampai</div>
              </div>
            </div>
          </div>

          <div class="col-12" id="studentWrap">
            <label class="form-label">Siswa</label>
            <select name="student_id" class="form-select" <?= !$hasClasses ? 'disabled' : '' ?>>
              <option value="">Pilih Siswa</option>
              <?php foreach ($students as $s): ?>
                <option value="<?= esc($s['id']) ?>" <?= (string)$s['id'] === (string)$valStudent ? 'selected' : '' ?>>
                  <?= esc(($s['full_name'] ?? '-') . ' - ' . ($s['class_name'] ?? '-') . ' - NISN ' . ($s['nisn'] ?? '-')) ?>
                </option>
              <?php endforeach; ?>
            </select>
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
                <div class="form-text">Kertas</div>
              </div>
              <div class="col-6">
                <select name="orientation" class="form-select" <?= !$hasClasses ? 'disabled' : '' ?>>
                  <option value="portrait" <?= $valOrient === 'portrait' ? 'selected' : '' ?>>Tegak</option>
                  <option value="landscape" <?= $valOrient === 'landscape' ? 'selected' : '' ?>>Mendatar</option>
                </select>
                <div class="form-text">Orientasi</div>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary" <?= !$hasClasses ? 'disabled' : '' ?>>
              <i class="fas fa-eye me-1"></i> Pratinjau
            </button>

            <a id="dlPdf" class="btn btn-outline-secondary <?= !$hasClasses ? 'disabled' : '' ?>" href="#" role="button" aria-disabled="<?= !$hasClasses ? 'true' : 'false' ?>">
              <i class="fas fa-file-pdf me-1"></i> PDF
            </a>

            <a id="dlXlsx" class="btn btn-outline-success <?= !$hasClasses ? 'disabled' : '' ?>" href="#" role="button" aria-disabled="<?= !$hasClasses ? 'true' : 'false' ?>">
              <i class="fas fa-file-excel me-1"></i> Excel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Pratinjau</h5>
        <small class="text-dark">Klik "Pratinjau" untuk memuat data.</small>
      </div>
      <div class="card-body" id="previewArea">
        <?php if (!$hasClasses): ?>
          <div class="alert alert-warning mb-0">
            Tidak ada kelas binaan, jadi laporan belum bisa dibuat.
          </div>
        <?php else: ?>
          <div class="text-dark">
            Pilih periode, lalu klik <b>Pratinjau</b>.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const HAS_CLASSES = <?= $hasClasses ? 'true' : 'false' ?>;

  const form = document.getElementById('classReportForm');
  const preview = document.getElementById('previewArea');
  const dlPdf = document.getElementById('dlPdf');
  const dlXlsx = document.getElementById('dlXlsx');
  const modeSelect = document.getElementById('modeSelect');
  const studentWrap = document.getElementById('studentWrap');

  const downloadBase = "<?= route_to('homeroom.reports.download') ?>";

  function qs() {
    return new URLSearchParams(new FormData(form)).toString();
  }

  function syncDownloadLinks() {
    if (!HAS_CLASSES) {
      dlPdf.href = '#';
      dlXlsx.href = '#';
      return;
    }

    const q = qs();
    dlPdf.href  = downloadBase + "?" + q + "&format=pdf";
    dlXlsx.href = downloadBase + "?" + q + "&format=xlsx";
  }

  function syncMode() {
    const isIndividual = modeSelect && modeSelect.value === 'student_individual';
    if (studentWrap) {
      studentWrap.classList.toggle('d-none', !isIndividual);
    }
  }

  async function loadPreview(e){
    if (e) e.preventDefault();
    if (!HAS_CLASSES) return;

    syncDownloadLinks();

    preview.innerHTML = '<div class="text-center text-dark py-4"><div class="spinner-border spinner-border-sm me-2"></div>Memuat pratinjau...</div>';

    try {
      const url = form.action + "?" + qs();
      const res = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" }});
      const html = await res.text();

      if (!res.ok) {
        preview.innerHTML =
          '<div class="alert alert-danger mb-0">' +
          '<b>Gagal memuat pratinjau.</b> (' + res.status + ')<br>' +
          '<div class="small mt-2" style="white-space:pre-wrap;">' + (html || '') + '</div>' +
          '</div>';
        return;
      }

      preview.innerHTML = html;
    } catch (err) {
      preview.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat pratinjau. Coba ulang.</div>';
    }
  }

  // Hindari klik download saat disabled
  [dlPdf, dlXlsx].forEach(a => {
    a.addEventListener('click', function(e){
      if (!HAS_CLASSES || a.classList.contains('disabled')) e.preventDefault();
    });
  });

  form.addEventListener('submit', loadPreview);
  form.addEventListener('change', function(){
    syncMode();
    syncDownloadLinks();
  });
  syncMode();
  syncDownloadLinks();
})();
</script>

<?= $this->endSection() ?>
