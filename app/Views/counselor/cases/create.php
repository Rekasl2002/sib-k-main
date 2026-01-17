<?php

/**
 * File Path: app/Views/counselor/cases/create.php
 * 
 * Create Violation View
 * Form untuk melaporkan pelanggaran siswa baru
 * 
 * @package    SIB-K
 * @subpackage Views/Counselor/Cases
 * @category   View
 * @author     Development Team
 * @created    2025-01-06
 */

$this->extend('layouts/main');
$this->section('content');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Tambah Kasus & Pelanggaran</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/cases') ?>">Kasus & Pelanggaran</a></li>
                    <li class="breadcrumb-item active">Tambah Kasus & Pelanggaran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php helper('app'); ?>
<?= show_alerts() ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Terdapat kesalahan pada input:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Create Form -->
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header bg-danger">
                <h4 class="card-title mb-0 text-white">
                    <i class="mdi mdi-alert-circle-outline me-2"></i>Form Laporan Pelanggaran
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('counselor/cases/store') ?>" 
                method="post" 
                id="createViolationForm" 
                enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- Student Selection -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label required">Siswa yang Melanggar</label>
                            <select name="student_id" id="studentSelect" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>" <?= old('student_id') == $student['id'] ? 'selected' : '' ?>>
                                        <?= esc($student['full_name']) ?> - <?= esc($student['nisn']) ?>
                                        <?php if ($student['class_name']): ?>
                                            (<?= esc($student['class_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Pilih siswa yang melakukan pelanggaran</small>
                        </div>
                    </div>

                    <!-- Category Selection (Grouped by Severity) -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label required">Kategori Pelanggaran</label>
                            <select name="category_id" id="categorySelect" class="form-select" required onchange="updateCategoryInfo()">
                                <option value="">-- Pilih Kategori Pelanggaran --</option>

                                <?php if (!empty($categories['Ringan'])): ?>
                                    <optgroup label="⚠️ PELANGGARAN RINGAN">
                                        <?php foreach ($categories['Ringan'] as $category): ?>
                                            <option value="<?= $category['id'] ?>"
                                                data-points="<?= $category['point_deduction'] ?>"
                                                data-severity="Ringan"
                                                data-description="<?= esc($category['description']) ?>"
                                                <?= old('category_id') == $category['id'] ? 'selected' : '' ?>>
                                                <?= esc($category['category_name']) ?> (-<?= $category['point_deduction'] ?> poin)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($categories['Sedang'])): ?>
                                    <optgroup label="⚠️⚠️ PELANGGARAN SEDANG">
                                        <?php foreach ($categories['Sedang'] as $category): ?>
                                            <option value="<?= $category['id'] ?>"
                                                data-points="<?= $category['point_deduction'] ?>"
                                                data-severity="Sedang"
                                                data-description="<?= esc($category['description']) ?>"
                                                <?= old('category_id') == $category['id'] ? 'selected' : '' ?>>
                                                <?= esc($category['category_name']) ?> (-<?= $category['point_deduction'] ?> poin)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($categories['Berat'])): ?>
                                    <optgroup label="🚨 PELANGGARAN BERAT">
                                        <?php foreach ($categories['Berat'] as $category): ?>
                                            <option value="<?= $category['id'] ?>"
                                                data-points="<?= $category['point_deduction'] ?>"
                                                data-severity="Berat"
                                                data-description="<?= esc($category['description']) ?>"
                                                <?= old('category_id') == $category['id'] ? 'selected' : '' ?>>
                                                <?= esc($category['category_name']) ?> (-<?= $category['point_deduction'] ?> poin)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>

                            <!-- Category Info Display -->
                            <div id="categoryInfo" class="mt-2" style="display: none;">
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-information fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <strong>Informasi Kategori:</strong>
                                            <p class="mb-1" id="categoryDescription"></p>
                                            <div class="mt-2">
                                                <span class="badge bg-warning" id="categorySeverity"></span>
                                                <span class="badge bg-danger" id="categoryPoints"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Kejadian</label>
                            <input type="date" name="violation_date" class="form-control"
                                value="<?= old('violation_date', date('Y-m-d')) ?>"
                                max="<?= date('Y-m-d') ?>" required>
                            <small class="text-muted">Tanggal terjadinya pelanggaran</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Waktu Kejadian</label>
                            <input type="time" name="violation_time" class="form-control" value="<?= old('violation_time') ?>">
                            <small class="text-muted">Opsional - Waktu terjadinya pelanggaran</small>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Lokasi Kejadian</label>
                            <input type="text" name="location" class="form-control"
                                placeholder="Contoh: Kantin, Kelas X-1, Lapangan, dll"
                                value="<?= old('location') ?>">
                            <small class="text-muted">Tempat terjadinya pelanggaran</small>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label required">Deskripsi Lengkap Pelanggaran</label>
                            <textarea name="description" class="form-control" rows="5"
                                placeholder="Tuliskan kronologi lengkap pelanggaran yang terjadi..."
                                required><?= old('description') ?></textarea>
                            <small class="text-muted">
                                <i class="mdi mdi-information-outline me-1"></i>
                                Jelaskan secara detail apa yang terjadi, siapa yang terlibat, dan bukti-bukti yang ada
                            </small>
                        </div>
                    </div>
                                        
                    <!-- Saksi & Bukti -->
                    <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Saksi (optional)</label>
                        <input type="text" name="witness" class="form-control"
                            value="<?= old('witness') ?>" placeholder="Nama saksi, contoh: Bpk. Dedi">
                        <small class="text-muted">Bisa diisi satu nama; jika lebih dari satu, pisahkan dengan koma.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Barang Bukti (optional)</label>
                        <input type="file" name="evidence[]" class="form-control" multiple
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.mp4">
                        <small class="text-muted">
                        Anda dapat mengunggah beberapa file (jpg, png, pdf, doc, docx, mp4). Maks 5MB per file.
                        </small>
                    </div>
                    </div>


                    <!-- Handler Assignment -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Ditangani Oleh</label>
                            <select name="handled_by" class="form-select">
                                <option value="<?= auth_id() ?>" selected>Saya Sendiri</option>
                            </select>
                            <small class="text-muted">Default: Anda akan menangani kasus ini. Koordinator dapat menugaskan ke guru BK lain.</small>
                        </div>
                    </div>

                    <!-- Initial Status -->
                    <input type="hidden" name="status" value="Dilaporkan">

                    <!-- Notes Section -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="3"
                                placeholder="Catatan atau informasi tambahan yang relevan..."><?= old('notes') ?></textarea>
                        </div>
                    </div>

                    <!-- Info Alert -->
                    <div class="alert alert-warning border-0">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-alert fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <strong>Penting:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Pastikan semua data yang dilaporkan akurat dan sesuai fakta</li>
                                    <li>Pelanggaran akan tercatat dalam sistem dan mempengaruhi poin siswa</li>
                                    <li>Orang tua/wali siswa akan diberi notifikasi tentang pelanggaran ini</li>
                                    <li>Sanksi dapat ditambahkan setelah pelanggaran dilaporkan</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= base_url('counselor/cases') ?>" class="btn btn-secondary">
                                    <i class="mdi mdi-arrow-left me-1"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-danger" id="submitBtn">
                                    <i class="mdi mdi-content-save me-1"></i>Simpan Laporan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
    // Update category info display
    function updateCategoryInfo() {
        const select = document.getElementById('categorySelect');
        const infoDiv = document.getElementById('categoryInfo');
        const descDiv = document.getElementById('categoryDescription');
        const severityBadge = document.getElementById('categorySeverity');
        const pointsBadge = document.getElementById('categoryPoints');

        if (select.value) {
            const option = select.options[select.selectedIndex];
            const points = option.dataset.points;
            const severity = option.dataset.severity;
            const description = option.dataset.description;

            descDiv.textContent = description;
            severityBadge.textContent = 'Tingkat: ' + severity;
            pointsBadge.textContent = 'Poin: -' + points;

            infoDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'none';
        }
    }

    // Form validation
    document.getElementById('createViolationForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');

        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menyimpan...';
    });

    // Initialize category info if already selected (for old input)
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect.value) {
            updateCategoryInfo();
        }
    });

    // Required field indicator style
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
        .form-label.required::after {
            content: " *";
            color: #f46a6a;
            font-weight: bold;
        }
    `;
        document.head.appendChild(style);
    });
    
    // snippet kecil untuk menampilkan daftar nama file yang dipilih
    document.addEventListener('change', function (e) {
    if (e.target && e.target.name === 'evidence[]') {
        const files = Array.from(e.target.files).map(f => f.name).join(', ');
        if (files) {
        e.target.nextElementSibling?.insertAdjacentHTML('afterend',
            `<div class="form-text mt-1">Dipilih: ${files}</div>`);
        }
    }
    });
    
</script>
<?php $this->endSection(); ?>