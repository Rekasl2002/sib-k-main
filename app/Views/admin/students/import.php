<?php

/**
 * File Path: app/Views/admin/students/import.php
 * 
 * Student Import View
 * Form untuk upload dan import data siswa dari Excel
 * 
 * @package    SIB-K
 * @subpackage Views/Admin/Students
 * @category   Student Management
 * @author     Development Team
 * @created    2025-01-01
 */
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18"><?= $page_title ?></h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if ($item['link']): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= $item['link'] ?>"><?= $item['title'] ?></a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= $item['title'] ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-2"></i>
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert me-2"></i>
        <?= session()->getFlashdata('warning') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Validasi Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Import Errors Detail -->
<?php if (session()->getFlashdata('import_errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">
            <i class="mdi mdi-alert-circle me-2"></i>Detail Error Impor
        </h5>
        <hr>
        <div style="max-height: 300px; overflow-y: auto;">
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('import_errors') as $error): ?>
                    <li><small><?= esc($error) ?></small></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Import Warnings -->
<?php if (session()->getFlashdata('import_warnings')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">
            <i class="mdi mdi-alert me-2"></i>Peringatan
        </h5>
        <hr>
        <ul class="mb-0">
            <?php foreach (session()->getFlashdata('import_warnings') as $warning): ?>
                <li><small><?= esc($warning) ?></small></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Main Content -->
<div class="row">
    <div class="col-lg-8">
        <!-- Upload Form Card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-upload text-primary me-2"></i>Unggah File Excel
                </h4>

                <form action="<?= base_url('admin/students/do-import') ?>" method="post" enctype="multipart/form-data" id="importForm">
                    <?= csrf_field() ?>

                    <!-- File Upload -->
                    <div class="mb-4">
                        <label for="import_file" class="form-label">
                            Pilih File Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file"
                            class="form-control"
                            id="import_file"
                            name="import_file"
                            accept=".xlsx,.xls,.csv"
                            required>
                        <div class="form-text">
                            Format yang didukung: XLSX, XLS, CSV (Maksimal 5MB)
                        </div>
                        <div id="fileName" class="mt-2 text-muted small"></div>
                    </div>

                    <!-- Selected File Preview -->
                    <div id="filePreview" class="alert alert-info d-none" role="alert">
                        <i class="mdi mdi-file-excel me-2"></i>
                        <strong>File dipilih:</strong> <span id="selectedFileName"></span>
                        <span class="badge bg-primary ms-2" id="fileSize"></span>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="mdi mdi-upload me-1"></i> Unggah & Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Download Template Card -->
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title text-white mb-3">
                    <i class="mdi mdi-download me-2"></i>Template Impor
                </h5>
                <p class="card-text">
                    Download template Excel untuk impor data siswa.
                    Template sudah dilengkapi contoh data, petunjuk pengisian,
                    serta dropdown otomatis untuk beberapa kolom (Jenis Kelamin, Status, Agama, dan Kelas).
                </p>
                <a href="<?= base_url('admin/students/download-template') ?>"
                    class="btn btn-light btn-block">
                    <i class="mdi mdi-microsoft-excel me-1"></i>
                    Download Template
                </a>
            </div>
        </div>

        <!-- Instructions Card -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="mdi mdi-information text-info me-2"></i>Petunjuk Impor
                </h5>

                <div class="mb-3">
                    <h6 class="font-size-14 mb-2">Langkah-langkah:</h6>
                    <ol class="ps-3 mb-0 small">
                        <li>Download template Excel</li>
                        <li>Isi data siswa sesuai format dan petunjuk di dalam file</li>
                        <li>Pastikan semua kolom wajib terisi dengan benar</li>
                        <li>Unggah file yang sudah diisi</li>
                        <li>Sistem akan memvalidasi dan memproses data</li>
                    </ol>
                </div>

                <div class="mb-3">
                    <h6 class="font-size-14 mb-2">Kolom Wajib:</h6>
                    <ul class="ps-3 mb-0 small">
                        <li><strong>NISN</strong> (minimal 10 digit angka)</li>
                        <li><strong>NIS</strong> (minimal 5 karakter)</li>
                        <li><strong>Nama Lengkap</strong></li>
                        <li><strong>Email</strong> (format valid)</li>
                        <li><strong>Jenis Kelamin</strong> (L / P)</li>
                        <li><strong>Tanggal Lahir</strong> (format tanggal valid)</li>
                        <li><strong>Tanggal Masuk</strong> (format tanggal valid)</li>
                        <li><strong>No. HP Siswa</strong></li>
                    </ul>
                    <p class="mb-0 mt-2 small text-muted">
                        Data orang tua (Nama, Email, No. HP) bersifat wajib
                        <strong>jika salah satu diisi</strong>. Jika orang tua punya lebih dari satu anak,
                        gunakan <strong>email yang sama</strong> untuk semua anak tersebut.
                    </p>
                </div>

                <div class="alert alert-warning py-2 mb-0" role="alert">
                    <small>
                        <i class="mdi mdi-alert-circle-outline me-1"></i>
                        <strong>Perhatian:</strong>
                        Data yang tidak valid atau duplikat (NISN, NIS, atau Email) tidak akan diimpor
                        dan akan muncul di laporan error. Baris yang valid tetap diproses.
                    </small>
                </div>
            </div>
        </div>

        <!-- Format Info Card -->
        <div class="card border-0 bg-light">
            <div class="card-body">
                <h6 class="mb-3">
                    <i class="mdi mdi-format-list-bulleted text-success me-2"></i>Format Data
                </h6>

                <table class="table table-sm table-borderless mb-0 small">
                    <tr>
                        <td class="text-muted" width="50%">Jenis Kelamin:</td>
                        <td><code>L</code> atau <code>P</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal:</td>
                        <td>
                            <code>DD-MM-YYYY</code>,
                            <code>DD/MM/YYYY</code>,
                            atau <code>DDMMYYYY</code>
                            <br><span class="text-muted">Contoh: <code>15-05-2008</code> atau <code>15052008</code></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status:</td>
                        <td>
                            <code>Aktif</code>,
                            <code>Alumni</code>,
                            <code>Pindah</code>,
                            <code>Keluar</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">No. HP:</td>
                        <td>
                            Hanya angka, diawali <code>08</code>,
                            tanpa spasi atau tanda baca lain.
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Password:</td>
                        <td>
                            Jika kolom Password dikosongkan:
                            <br>
                            &bull; Default siswa & orang tua:
                            <code>DDMMYYYY (contoh: 15052008)</code> dari tanggal lahir siswa
                            <br>
                            &bull; Jika tanggal lahir kosong/tidak valid:
                            <code>password123</code>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput      = document.getElementById('import_file');
        const filePreview    = document.getElementById('filePreview');
        const selectedFileName = document.getElementById('selectedFileName');
        const fileSize       = document.getElementById('fileSize');
        const submitBtn      = document.getElementById('submitBtn');
        const importForm     = document.getElementById('importForm');

        // File input change handler
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                // Show preview
                selectedFileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                filePreview.classList.remove('d-none');

                // Validate file size
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    alert('Ukuran file terlalu besar! Maksimal 5MB');
                    fileInput.value = '';
                    filePreview.classList.add('d-none');
                    return;
                }

                // Validate file extension
                const validExtensions = ['xlsx', 'xls', 'csv'];
                const extension = file.name.split('.').pop().toLowerCase();
                if (!validExtensions.includes(extension)) {
                    alert('Format file tidak didukung! Gunakan XLSX, XLS, atau CSV');
                    fileInput.value = '';
                    filePreview.classList.add('d-none');
                    return;
                }
            } else {
                filePreview.classList.add('d-none');
            }
        });

        // Form submit handler
        importForm.addEventListener('submit', function(e) {
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Silakan pilih file terlebih dahulu!');
                return;
            }

            // Disable submit button to prevent double submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Memproses...';
        });

        // Helper function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    });
</script>

<?= $this->endSection() ?>
