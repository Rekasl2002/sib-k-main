<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File Path: app/Views/homeroom_teacher/students/import.php
 * Fitur: Impor Data Siswa dan Orang Tua (Wali Kelas).
 * Peran/izin: Wali Kelas dengan import_export_data; impor dibatasi ke kelas binaannya.
 * Berhubungan dengan: HomeroomTeacher\StudentImportController dan ExcelImporter.
 *
 * Catatan tampilan:
 * - Semua pesan/informasi tampil menetap (tidak bisa hilang/ditutup) tanpa pop-up.
 * - Teks dibuat gelap dan sederhana agar mudah dibaca.
 */

$classes     = is_array($classes ?? null) ? $classes : [];
$formAction  = $formAction ?? base_url('homeroom/students/do-import');
$templateUrl = $templateUrl ?? base_url('homeroom/students/download-template');
$backUrl     = $backUrl ?? base_url('homeroom/my-class');
$scopeNote   = trim((string) ($scopeNote ?? ''));
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0 font-size-18">Impor Data Siswa dan Orang Tua</h4>
      <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Wali Kelas</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/my-class') ?>">Kelas Binaan</a></li>
          <li class="breadcrumb-item active">Impor</li>
        </ol>
        <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-secondary">
          <i class="mdi mdi-arrow-left me-1"></i> Kembali
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Pesan hasil impor (menetap, tidak bisa ditutup/hilang) -->
<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $class): ?>
  <?php if (session()->getFlashdata($key)): ?>
    <div class="alert alert-<?= $class ?> border-0 shadow-sm" role="alert">
      <?= esc(session()->getFlashdata($key)) ?>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger border-0 shadow-sm" role="alert">
    <strong><i class="mdi mdi-alert-circle me-2"></i>Ada isian yang belum benar:</strong>
    <ul class="mb-0 mt-2">
      <?php foreach ((array) session()->getFlashdata('errors') as $error): ?>
        <li><?= esc($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('import_errors')): ?>
  <?php $importErrors = (array) session()->getFlashdata('import_errors'); ?>
  <?php if (!empty($importErrors)): ?>
    <div class="alert alert-danger border-0 shadow-sm" role="alert">
      <h5 class="alert-heading mb-2">
        <i class="mdi mdi-alert-circle me-2"></i>Baris yang tidak masuk (<?= count($importErrors) ?>)
      </h5>
      <p class="mb-2">Perbaiki baris berikut di file Excel, lalu unggah ulang. Baris lain yang sudah benar tetap tersimpan.</p>
      <div style="max-height: 320px; overflow-y: auto;">
        <ul class="mb-0">
          <?php foreach ($importErrors as $error): ?>
            <li><?= esc($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php if (session()->getFlashdata('import_warnings')): ?>
  <?php $importWarnings = (array) session()->getFlashdata('import_warnings'); ?>
  <?php if (!empty($importWarnings)): ?>
    <div class="alert alert-warning border-0 shadow-sm" role="alert">
      <h5 class="alert-heading mb-2">
        <i class="mdi mdi-information-outline me-2"></i>Catatan
      </h5>
      <ul class="mb-0">
        <?php foreach ($importWarnings as $warning): ?>
          <li><?= esc($warning) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
<?php endif; ?>

<div class="row">
  <div class="col-lg-7">
    <!-- Kartu unggah file -->
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">
          <i class="mdi mdi-upload text-primary me-2"></i>Unggah File Data Siswa
        </h4>

        <p class="mb-3">
          Masukkan banyak data siswa sekaligus dari satu file Excel. Data orang tua
          diisi pada file yang sama dan akunnya dibuat otomatis.
        </p>

        <?php if ($scopeNote !== ''): ?>
          <div class="alert alert-info border-0" role="alert">
            <i class="mdi mdi-information-outline me-1"></i>
            <?= esc($scopeNote) ?>
          </div>
        <?php endif; ?>

        <form action="<?= esc($formAction, 'attr') ?>" method="post" enctype="multipart/form-data" id="importForm">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="import_file" class="form-label fw-semibold">
              Pilih file Excel <span class="text-danger">*</span>
            </label>
            <input type="file"
                   class="form-control form-control-lg"
                   id="import_file"
                   name="import_file"
                   accept=".xlsx,.xls,.csv"
                   required>
            <div class="form-text">
              File berakhiran .xlsx, .xls, atau .csv. Ukuran paling besar 5 MB.
            </div>

            <!-- Pesan pemeriksaan file tampil di sini (menetap, tanpa pop-up) -->
            <div id="importMsg" class="mt-2" role="alert" aria-live="polite"></div>
            <div id="fileName" class="mt-2 fw-semibold"></div>
          </div>

          <div id="filePreview" class="alert alert-info border-0 d-none" role="alert">
            <i class="mdi mdi-file-excel me-2"></i>
            <strong>File dipilih:</strong> <span id="selectedFileName"></span>
            <span class="badge bg-primary ms-2" id="fileSize"></span>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
            <i class="mdi mdi-file-import-outline me-1"></i> Unggah &amp; Impor
          </button>
        </form>
      </div>
    </div>

    <!-- Cara memakai -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-help-circle text-info me-2"></i>Cara Memakai
        </h5>

        <p class="mb-2 fw-semibold">Ada dua cara:</p>
        <ol class="ps-3 mb-3">
          <li class="mb-1">
            Pakai file data siswa dari sekolah (misalnya dari EMIS/Dapodik) —
            <strong>langsung diunggah, tidak perlu diubah</strong>.
          </li>
          <li>
            Atau unduh <strong>Contoh File</strong> di samping, isi datanya, lalu unggah.
          </li>
        </ol>

        <p class="mb-2 fw-semibold">Langkah:</p>
        <ol class="ps-3 mb-0">
          <li class="mb-1">Klik "Pilih file Excel", lalu pilih file dari komputer.</li>
          <li class="mb-1">Klik tombol <strong>Unggah &amp; Impor</strong>.</li>
          <li>Tunggu sampai selesai. Hasilnya muncul di halaman ini.</li>
        </ol>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <!-- Kartu unduh contoh file -->
    <div class="card bg-primary text-white">
      <div class="card-body">
        <h5 class="card-title text-white mb-3">
          <i class="mdi mdi-download me-2"></i>Contoh File (Template)
        </h5>
        <p class="card-text">
          Unduh contoh file untuk mengisi data siswa dan orang tua. Di dalamnya
          sudah ada lembar <strong>"Petunjuk Pengisian"</strong>.
        </p>
        <a href="<?= esc($templateUrl, 'attr') ?>" class="btn btn-light btn-block fw-semibold">
          <i class="mdi mdi-microsoft-excel me-1"></i>
          Unduh Contoh File
        </a>
      </div>
    </div>

    <!-- Batasan kelas binaan -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-account-group text-primary me-2"></i>Kelas yang Bisa Diimpor
        </h5>
        <p class="mb-2">Sebagai Wali Kelas, Anda hanya dapat mengimpor siswa pada kelas binaan berikut:</p>
        <ul class="ps-3 mb-0">
          <?php foreach ($classes as $class): ?>
            <li><strong><?= esc($class['class_name'] ?? '-') ?></strong></li>
          <?php endforeach; ?>
          <?php if (!$classes): ?>
            <li>Belum ada kelas binaan aktif.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <!-- Kolom wajib & otomatis -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-clipboard-check text-success me-2"></i>Isi yang Diperlukan
        </h5>

        <p class="mb-2 fw-semibold">Wajib ada:</p>
        <ul class="ps-3 mb-3">
          <li><strong>NISN</strong> — 10 angka</li>
          <li><strong>Nama Lengkap</strong></li>
          <li><strong>Tanggal Lahir</strong></li>
          <li><strong>Jenis Kelamin</strong> — Laki-laki / Perempuan</li>
        </ul>

        <p class="mb-2 fw-semibold">Otomatis / boleh dikosongkan:</p>
        <ul class="ps-3 mb-0">
          <li><strong>Umur</strong> — tidak perlu diisi, dihitung sendiri oleh aplikasi.</li>
          <li><strong>Jurusan</strong> — bila kosong, otomatis menjadi <strong>IPA</strong>.</li>
          <li><strong>Kelas</strong> — tulis sesuai kelas binaan di atas, contoh <strong>Kelas 12 - A</strong>.</li>
          <li><strong>NIK, Alamat, No. HP, dan data orang tua</strong> — boleh dikosongkan.</li>
        </ul>
      </div>
    </div>

    <!-- Cara siswa masuk + catatan -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="mdi mdi-key text-warning me-2"></i>Cara Siswa Masuk
        </h5>
        <ul class="ps-3 mb-3">
          <li>Nama pengguna (username) = <strong>NISN</strong>.</li>
          <li>
            Kata sandi awal = <strong>tanggal lahir 8 angka</strong>
            (hari-bulan-tahun). Contoh lahir 19-09-2007 menjadi <strong>19092007</strong>.
          </li>
        </ul>

        <div class="alert alert-warning border-0 mb-0" role="alert">
          <i class="mdi mdi-alert-circle-outline me-1"></i>
          Data yang salah, dobel (NISN/NIK sama), atau di luar kelas binaan tidak akan
          dimasukkan dan akan ditampilkan di halaman ini. Data yang sudah benar tetap tersimpan.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript: pemeriksaan file di halaman, tanpa pop-up -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput        = document.getElementById('import_file');
    const filePreview      = document.getElementById('filePreview');
    const selectedFileName = document.getElementById('selectedFileName');
    const fileSize         = document.getElementById('fileSize');
    const fileNameEl       = document.getElementById('fileName');
    const submitBtn        = document.getElementById('submitBtn');
    const importForm       = document.getElementById('importForm');
    const importMsg        = document.getElementById('importMsg');

    function showMessage(text, type) {
        if (!importMsg) return;
        importMsg.className = 'mt-2 alert alert-' + (type || 'danger') + ' border-0 py-2 mb-0';
        importMsg.textContent = text;
    }

    function clearMessage() {
        if (!importMsg) return;
        importMsg.className = 'mt-2';
        importMsg.textContent = '';
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function resetPreview() {
        if (fileNameEl) fileNameEl.textContent = '';
        if (filePreview) filePreview.classList.add('d-none');
        if (selectedFileName) selectedFileName.textContent = '';
        if (fileSize) fileSize.textContent = '';
    }

    if (!fileInput || !importForm) return;

    fileInput.addEventListener('change', function(e) {
        clearMessage();
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;

        if (!file) {
            resetPreview();
            return;
        }

        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            showMessage('Ukuran file terlalu besar. Paling besar 5 MB. Silakan pilih file lain.', 'danger');
            fileInput.value = '';
            resetPreview();
            return;
        }

        const validExtensions = ['xlsx', 'xls', 'csv'];
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        if (!validExtensions.includes(extension)) {
            showMessage('Jenis file belum sesuai. Gunakan file Excel (.xlsx, .xls) atau .csv.', 'danger');
            fileInput.value = '';
            resetPreview();
            return;
        }

        if (fileNameEl) fileNameEl.textContent = 'File siap diunggah: ' + file.name;
        if (selectedFileName) selectedFileName.textContent = file.name;
        if (fileSize) fileSize.textContent = formatFileSize(file.size);
        if (filePreview) filePreview.classList.remove('d-none');
    });

    importForm.addEventListener('submit', function(e) {
        if (!fileInput.files || !fileInput.files.length) {
            e.preventDefault();
            showMessage('Silakan pilih file terlebih dahulu sebelum mengunggah.', 'danger');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Memproses...';
    });
});
</script>

<?= $this->endSection() ?>
