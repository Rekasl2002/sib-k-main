<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/reports/index.php
 *
 * Counselor • Reports Index
 * - Filter + AJAX preview + Download PDF/XLSX
 * - Konsisten dengan Koordinator Reports UI, namun scope sesuai Counselor.
 */

$classes     = $classes ?? [];
$assessments = $assessments ?? [];

$valType   = $valType ?? 'sessions';
$valFrom   = $valFrom ?? date('Y-m-01');
$valTo     = $valTo ?? date('Y-m-d');

$valClass  = $valClass ?? '';
$valStatus = $valStatus ?? '';
$valSearch = $valSearch ?? '';

$valSortBy  = $valSortBy ?? '';
$valSortDir = $valSortDir ?? 'desc';

// Kompatibel: ada controller yang kirim valAssessmentId, ada juga yang masih valAssess
$valAssessmentId = $valAssessmentId ?? ($valAssess ?? '');

$valPaper  = $valPaper ?? 'A4';
$valOrient = $valOrient ?? 'portrait';
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Laporan (Guru BK)</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= site_url('counselor/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Laporan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Filter Laporan</h5>
        <small class="text-muted">Data dibatasi ke siswa/aktivitas binaan Guru BK.</small>
      </div>

      <div class="card-body">
        <form
          id="filterForm"
          method="get"
          action="<?= route_to('counselor.reports.preview') ?>"
          class="row g-3"
        >

          <div class="col-12">
            <label class="form-label">Jenis Laporan</label>
            <select name="type" class="form-select" id="typeSelect">
              <option value="students" <?= $valType==='students'?'selected':'' ?>>Data Siswa (Binaan)</option>
              <option value="sessions" <?= $valType==='sessions'?:'' ?>>Sesi Konseling</option>
              <option value="violations" <?= $valType==='violations'?:'' ?>>Kasus & Pelanggaran</option>
              <!--<option value="assessments" <?= $valType==='assessments'?:'' ?>>Asesmen</option>
              <option value="career" <?= $valType==='career'?:'' ?>>Info Karir</option>
              <option value="universities" <?= $valType==='universities'?:'' ?>>Info Perguruan Tinggi</option>
              <option value="career_choices" <?= $valType==='career_choices'?:'' ?>>Pilihan Karir Siswa</option>
              <option value="university_choices" <?= $valType==='university_choices'?:'' ?>>Pilihan PT Siswa</option>-->
            </select>
          </div>

          <div class="col-12" id="periodWrap">
            <label class="form-label">Periode</label>
            <div class="row g-2">
              <div class="col-6">
                <input type="date" name="date_from" class="form-control" value="<?= esc($valFrom) ?>">
                <div class="form-text">Dari</div>
              </div>
              <div class="col-6">
                <input type="date" name="date_to" class="form-control" value="<?= esc($valTo) ?>">
                <div class="form-text">Sampai</div>
              </div>
            </div>
          </div>

          <div class="col-12" id="classWrap">
            <label class="form-label">Kelas (opsional)</label>
            <select name="class_id" class="form-select">
              <option value="">Semua Kelas Binaan</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= esc($c['id']) ?>" <?= (string)$c['id']===(string)$valClass ? 'selected' : '' ?>>
                  <?= esc($c['class_name'] ?? ('Kelas #'.$c['id'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12" id="assessmentWrap">
            <label class="form-label">Asesmen (opsional)</label>
            <select name="assessment_id" class="form-select">
              <option value="">Semua Asesmen</option>
              <?php foreach ($assessments as $a): ?>
                <option value="<?= esc($a['id']) ?>" <?= (string)$a['id']===(string)$valAssessmentId ? 'selected' : '' ?>>
                  <?= esc($a['title'] ?? ('Asesmen #'.$a['id'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Hanya untuk laporan Asesmen.</div>
          </div>

          <div class="col-12" id="statusWrap">
            <label class="form-label">Status (opsional)</label>
            <select name="status" class="form-select" id="statusSelect">
              <option value="">Semua Status</option>
            </select>
          </div>

          <div class="col-12" id="searchWrap">
            <label class="form-label">Pencarian (opsional)</label>
            <input
              type="text"
              name="search"
              class="form-control"
              value="<?= esc($valSearch) ?>"
              placeholder="Nama siswa / judul / kata kunci..."
            >
          </div>

          <div class="col-12" id="sortWrap">
            <label class="form-label">Urutkan (opsional)</label>
            <div class="row g-2">
              <div class="col-7">
                <select name="sort_by" class="form-select" id="sortBy">
                  <option value="">Default</option>
                </select>
              </div>
              <div class="col-5">
                <select name="sort_dir" class="form-select" id="sortDir">
                  <option value="asc" <?= $valSortDir==='asc'?'selected':'' ?>>ASC</option>
                  <option value="desc" <?= $valSortDir==='desc'?'selected':'' ?>>DESC</option>
                </select>
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Opsi PDF</label>
            <div class="row g-2">
              <div class="col-6">
                <select name="paper" class="form-select">
                  <option value="A4" <?= strtoupper($valPaper)==='A4'?'selected':'' ?>>A4</option>
                  <option value="letter" <?= strtolower($valPaper)==='letter'?'selected':'' ?>>Letter</option>
                  <option value="legal" <?= strtolower($valPaper)==='legal'?'selected':'' ?>>Legal</option>
                </select>
                <div class="form-text">Kertas</div>
              </div>
              <div class="col-6">
                <select name="orientation" class="form-select">
                  <option value="portrait" <?= $valOrient==='portrait'?'selected':'' ?>>Portrait</option>
                  <option value="landscape" <?= $valOrient==='landscape'?'selected':'' ?>>Landscape</option>
                </select>
                <div class="form-text">Orientasi</div>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-eye me-1"></i> Pratinjau
            </button>

            <a id="dlPdf" class="btn btn-outline-secondary" href="#">
              <i class="fas fa-file-pdf me-1"></i> PDF
            </a>

            <a id="dlXlsx" class="btn btn-outline-success" href="#">
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
        <small class="text-muted">Klik “Pratinjau” untuk memuat data.</small>
      </div>
      <div class="card-body" id="previewArea">
        <div class="text-muted">
          Pilih filter di kiri, lalu klik <b>Pratinjau</b>. 📄
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('filterForm');
  const preview = document.getElementById('previewArea');
  const dlPdf = document.getElementById('dlPdf');
  const dlXlsx = document.getElementById('dlXlsx');

  const typeSelect = document.getElementById('typeSelect');
  const statusSelect = document.getElementById('statusSelect');
  const sortBy = document.getElementById('sortBy');
  const sortDir = document.getElementById('sortDir');

  const wrapPeriod = document.getElementById('periodWrap');
  const wrapClass = document.getElementById('classWrap');
  const wrapAssess = document.getElementById('assessmentWrap');
  const wrapStatus = document.getElementById('statusWrap');
  const wrapSearch = document.getElementById('searchWrap');
  const wrapSort = document.getElementById('sortWrap');

  const INIT_STATUS = "<?= esc($valStatus) ?>";
  const INIT_SORTBY = "<?= esc($valSortBy) ?>";

  const downloadBase = "<?= route_to('counselor.reports.download') ?>";

  let hydrated = false;

  function qs() {
    return new URLSearchParams(new FormData(form)).toString();
  }

  function syncDownloadLinks() {
    const q = qs();
    dlPdf.href  = downloadBase + "?" + q + "&format=pdf";
    dlXlsx.href = downloadBase + "?" + q + "&format=xlsx";
  }

  function setOptions(el, options, selectedValue) {
    const prev = selectedValue ?? el.value ?? '';
    el.innerHTML = '';

    options.forEach(opt => {
      const o = document.createElement('option');
      o.value = opt.value;
      o.textContent = opt.label;
      if (String(opt.value) === String(prev)) o.selected = true;
      el.appendChild(o);
    });

    // kalau prev tidak ada di options, default ke pertama
    if (!el.value && options.length) {
      el.value = options[0].value;
    }
  }

  function updateVisibility(){
    const type = typeSelect.value;

    // default show
    wrapPeriod.style.display = '';
    wrapClass.style.display = '';
    wrapAssess.style.display = 'none';
    wrapStatus.style.display = '';
    wrapSearch.style.display = '';
    wrapSort.style.display = '';

    let statusOptions = [{value:'', label:'Semua Status'}];
    let sortOptions   = [{value:'', label:'Default'}];

    if (type === 'students') {
      wrapPeriod.style.display = 'none';
      wrapAssess.style.display = 'none';

      statusOptions = [
        {value:'', label:'Semua Status'},
        {value:'active', label:'Aktif'},
        {value:'alumni', label:'Alumni'},
        {value:'moved', label:'Pindah'},
        {value:'dropped', label:'Keluar'}
      ];

      sortOptions = [
        {value:'', label:'Default'},
        {value:'u.full_name', label:'Nama'},
        {value:'s.nisn', label:'NISN'},
        {value:'s.nis', label:'NIS'},
        {value:'c.class_name', label:'Kelas'},
        {value:'s.status', label:'Status'}
      ];
    }

    if (type === 'sessions') {
      statusOptions = [
        {value:'', label:'Semua Status'},
        {value:'Scheduled', label:'Scheduled'},
        {value:'Completed', label:'Completed'},
        {value:'Cancelled', label:'Cancelled'}
      ];

      sortOptions = [
        {value:'', label:'Default'},
        {value:'cs.session_date', label:'Tanggal'},
        {value:'cs.session_type', label:'Jenis'},
        {value:'cs.status', label:'Status'},
        {value:'su.full_name', label:'Siswa'}
      ];
    }

    if (type === 'violations') {
      statusOptions = [
        {value:'', label:'Semua Status'},
        {value:'Dilaporkan', label:'Dilaporkan'},
        {value:'Dalam Proses', label:'Dalam Proses'},
        {value:'Selesai', label:'Selesai'}
      ];

      sortOptions = [
        {value:'', label:'Default'},
        {value:'v.violation_date', label:'Tanggal'},
        {value:'vc.point_deduction', label:'Poin'},
        {value:'vc.category_name', label:'Kategori'},
        {value:'v.status', label:'Status'},
        {value:'su.full_name', label:'Siswa'},
        {value:'c.class_name', label:'Kelas'}
      ];
    }

    if (type === 'assessments') {
      wrapAssess.style.display = '';

      // paling aman: status numeric (0/1/2/3) karena banyak DB menyimpan integer
      statusOptions = [
        {value:'', label:'Semua Status'},
        {value:'0', label:'Belum Mulai'},
        {value:'1', label:'Sedang Dikerjakan'},
        {value:'2', label:'Selesai'},
        {value:'3', label:'Dinilai'}
      ];

      sortOptions = [
        {value:'', label:'Default'},
        {value:'a.title', label:'Asesmen'},
        {value:'su.full_name', label:'Siswa'},
        {value:'ar.status', label:'Status'},
        {value:'ar.percentage', label:'Nilai (%)'},
        {value:'ar.started_at', label:'Mulai'}
      ];
    }

    if (type === 'career') {
      wrapPeriod.style.display = 'none';
      wrapClass.style.display  = 'none';
      // ReportService career() belum pakai filter status, jadi jangan bikin user berharap
      wrapStatus.style.display = 'none';

      sortOptions = [
        {value:'', label:'Default'},
        {value:'title', label:'Judul'},
        {value:'sector', label:'Sektor'},
        {value:'avg_salary_idr', label:'Gaji'},
        {value:'demand_level', label:'Permintaan'}
      ];
    }

    if (type === 'universities') {
      wrapPeriod.style.display = 'none';
      wrapClass.style.display  = 'none';

      // (opsional) kalau nanti kamu tambahkan filter is_active di service, bisa dipakai.
      statusOptions = [
        {value:'', label:'Semua'},
        {value:'1', label:'Aktif'},
        {value:'0', label:'Tidak Aktif'}
      ];

      sortOptions = [
        {value:'', label:'Default'},
        {value:'university_name', label:'Nama'},
        {value:'accreditation', label:'Akreditasi'},
        {value:'location', label:'Lokasi'},
        {value:'is_active', label:'Status'}
      ];
    }

    if (type === 'career_choices') {
      sortOptions = [
        {value:'', label:'Default'},
        {value:'co.title', label:'Karir'},
        {value:'students_count', label:'Jumlah Siswa'},
        {value:'saved_count', label:'Jumlah Pilihan'}
      ];
    }

    if (type === 'university_choices') {
      sortOptions = [
        {value:'', label:'Default'},
        {value:'u.university_name', label:'PT'},
        {value:'students_count', label:'Jumlah Siswa'},
        {value:'saved_count', label:'Jumlah Pilihan'}
      ];
    }

    const selectedStatus = hydrated ? (statusSelect.value ?? '') : INIT_STATUS;
    const selectedSortBy = hydrated ? (sortBy.value ?? '') : INIT_SORTBY;

    setOptions(statusSelect, statusOptions, selectedStatus);
    setOptions(sortBy, sortOptions, selectedSortBy);

    hydrated = true;
  }

  async function loadPreview(e){
    if (e) e.preventDefault();

    updateVisibility();
    syncDownloadLinks();

    preview.innerHTML =
      '<div class="text-center text-muted py-4">' +
        '<div class="spinner-border spinner-border-sm me-2"></div>' +
        'Memuat pratinjau...' +
      '</div>';

    try {
      const res = await fetch(form.action + "?" + qs(), {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      const html = await res.text();

      if (!res.ok) {
        preview.innerHTML = '<div class="alert alert-danger mb-0">' +
          '<b>Gagal memuat pratinjau.</b> (' + res.status + ')<br>' +
          '<div class="small text-muted mt-2">Cek error log / halaman error_exception untuk detail.</div>' +
        '</div>';
        return;
      }

      preview.innerHTML = html;
    } catch (err) {
      preview.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat pratinjau. Coba ulang.</div>';
    }
  }

  typeSelect.addEventListener('change', function(){
    updateVisibility();
    syncDownloadLinks();
  });

  form.addEventListener('change', syncDownloadLinks);
  form.addEventListener('submit', loadPreview);

  updateVisibility();
  syncDownloadLinks();
})();
</script>

<?= $this->endSection() ?>
