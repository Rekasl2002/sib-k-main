<?php

/**
 * File Path: app/Views/admin/academic_years/form.php
 * 
 * Academic Year Form View
 * Form untuk create dan edit tahun ajaran
 * 
 * @package    SIB-K
 * @subpackage Views/Admin/AcademicYears
 * @category   Academic Year Management
 * @author     Development Team
 * @created    2025-01-06
 */

$isEdit = isset($academic_year);
$formAction = $isEdit ? base_url('admin/academic-years/update/' . $academic_year['id']) : base_url('admin/academic-years/store');
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

<!-- Form Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-form-select text-primary me-2"></i>
                    <?= $isEdit ? 'Edit Data Tahun Ajaran' : 'Formulir Tambah Tahun Ajaran' ?>
                </h4>

                <form action="<?= $formAction ?>" method="post" id="academicYearForm">
                    <?= csrf_field() ?>

                    <div class="row">
                        <!-- Year Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="year_name" class="form-label">
                                    Nama Tahun Ajaran <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control"
                                        id="year_name"
                                        name="year_name"
                                        value="<?= old('year_name', $academic_year['year_name'] ?? '') ?>"
                                        placeholder="2024/2025"
                                        pattern="\d{4}/\d{4}"
                                        required>
                                    <button class="btn btn-outline-primary" type="button" id="btnAutoYear">
                                        <i class="mdi mdi-auto-fix"></i> Auto
                                    </button>
                                </div>
                                <div class="form-text">Format: YYYY/YYYY (contoh: 2024/2025)</div>
                                <div id="yearNameWarning" class="text-warning small mt-1" style="display: none;">
                                    <i class="mdi mdi-alert"></i> <span id="yearNameWarningText"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Semester -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="semester" class="form-label">
                                    Semester <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="">-- Pilih Semester --</option>
                                    <?php foreach ($semester_options as $key => $value): ?>
                                        <option value="<?= $key ?>"
                                            <?= (old('semester', $academic_year['semester'] ?? '') == $key) ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Start Date -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">
                                    Tanggal Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    class="form-control"
                                    id="start_date"
                                    name="start_date"
                                    value="<?= old('start_date', $academic_year['start_date'] ?? '') ?>"
                                    required>
                            </div>
                        </div>

                        <!-- End Date -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">
                                    Tanggal Selesai <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    class="form-control"
                                    id="end_date"
                                    name="end_date"
                                    value="<?= old('end_date', $academic_year['end_date'] ?? '') ?>"
                                    required>
                                <div id="durationInfo" class="text-info small mt-1" style="display: none;">
                                    <i class="mdi mdi-calendar-range"></i> Durasi: <strong id="durationText"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overlap Warning -->
                    <div id="overlapWarning" class="mdi alert-warning" role="alert" style="display: none;">
                        <i class="mdi mdi-alert me-2"></i>
                        <strong>Peringatan:</strong> Tahun ajaran ini bentrok dengan: <span id="overlapYears"></span>
                    </div>

                    <div class="row">
                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="is_active" class="form-label">
                                    Status
                                </label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="0" <?= (old('is_active', $academic_year['is_active'] ?? '0') == '0') ? 'selected' : '' ?>>
                                        Tidak Aktif
                                    </option>
                                    <option value="1" <?= (old('is_active', $academic_year['is_active'] ?? '0') == '1') ? 'selected' : '' ?>>
                                        Aktif
                                    </option>
                                </select>
                                <div class="form-text">
                                    <i class="mdi mdi-information-outline"></i>
                                    Hanya satu tahun ajaran yang bisa aktif. Tahun ajaran lain akan otomatis dinonaktifkan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($isEdit && isset($academic_year['class_count'])): ?>
                        <div class="mdi alert-info" role="alert">
                            <i class="mdi mdi-information me-2"></i>
                            <strong>Info:</strong> Tahun ajaran ini memiliki <strong><?= $academic_year['class_count'] ?> kelas</strong>.
                            <?php if ($academic_year['class_count'] > 0): ?>
                                Tahun ajaran tidak dapat dihapus selama masih ada kelas.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('admin/academic-years') ?>" class="btn btn-secondary">
                                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-light me-2">
                                        <i class="mdi mdi-refresh me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                                        <i class="mdi mdi-content-save me-1"></i>
                                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const yearNameInput = document.getElementById('year_name');
        const semesterSelect = document.getElementById('semester');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const btnAutoYear = document.getElementById('btnAutoYear');
        const academicYearForm = document.getElementById('academicYearForm');
        const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
        const excludeId = isEdit ? <?= $academic_year['id'] ?? 'null' ?> : null;

        // Use suggestion button (only for create)
        <?php if (!$isEdit && isset($suggested)): ?>
            const useSuggestionBtn = document.getElementById('useSuggestion');
            if (useSuggestionBtn) {
                useSuggestionBtn.addEventListener('click', function() {
                    yearNameInput.value = '<?= $suggested['year_name'] ?>';
                    semesterSelect.value = '<?= $suggested['semester'] ?>';
                    startDateInput.value = '<?= $suggested['start_date'] ?>';
                    endDateInput.value = '<?= $suggested['end_date'] ?>';
                    calculateDuration();
                    checkOverlap();
                });
            }
        <?php endif; ?>

        // Auto-generate year name and semester from start date
        btnAutoYear.addEventListener('click', function() {
            const startDate = startDateInput.value;
            if (!startDate) {
                alert('Silakan pilih tanggal mulai terlebih dahulu');
                startDateInput.focus();
                return;
            }

            // Show loading
            btnAutoYear.disabled = true;
            btnAutoYear.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Loading...';

            // AJAX request
            fetch(`<?= base_url('admin/academic-years/generate-year-name') ?>?start_date=${startDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        yearNameInput.value = data.year_name;
                        semesterSelect.value = data.semester;
                        validateYearName();
                    } else {
                        alert(data.message || 'Gagal generate nama tahun ajaran');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat generate nama tahun ajaran');
                })
                .finally(() => {
                    btnAutoYear.disabled = false;
                    btnAutoYear.innerHTML = '<i class="mdi mdi-auto-fix"></i> Auto';
                });
        });

        // Validate year name format
        function validateYearName() {
            const yearName = yearNameInput.value;
            const pattern = /^\d{4}\/\d{4}$/;
            const warningDiv = document.getElementById('yearNameWarning');
            const warningText = document.getElementById('yearNameWarningText');

            if (!yearName) {
                warningDiv.style.display = 'none';
                return;
            }

            if (!pattern.test(yearName)) {
                warningText.textContent = 'Format harus YYYY/YYYY (contoh: 2024/2025)';
                warningDiv.style.display = 'block';
                return;
            }

            const [year1, year2] = yearName.split('/').map(y => parseInt(y));
            if (year2 !== year1 + 1) {
                warningText.textContent = 'Tahun kedua harus lebih besar 1 dari tahun pertama';
                warningDiv.style.display = 'block';
                return;
            }

            warningDiv.style.display = 'none';
        }

        yearNameInput.addEventListener('blur', validateYearName);

        // Calculate duration
        function calculateDuration() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const durationInfo = document.getElementById('durationInfo');
            const durationText = document.getElementById('durationText');

            if (!startDate || !endDate) {
                durationInfo.style.display = 'none';
                return;
            }

            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = end - start;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const diffMonths = Math.round(diffDays / 30);

            if (diffDays < 0) {
                durationText.textContent = 'Tanggal selesai harus lebih besar dari tanggal mulai';
                durationInfo.className = 'text-danger small mt-1';
                durationInfo.style.display = 'block';
                return;
            }

            if (diffDays < 90) {
                durationText.textContent = `${diffMonths} bulan (minimal 3 bulan)`;
                durationInfo.className = 'text-warning small mt-1';
            } else if (diffDays > 400) {
                durationText.textContent = `${diffMonths} bulan (maksimal 13 bulan)`;
                durationInfo.className = 'text-warning small mt-1';
            } else {
                durationText.textContent = `${diffMonths} bulan`;
                durationInfo.className = 'text-info small mt-1';
            }

            durationInfo.style.display = 'block';
        }

        startDateInput.addEventListener('change', function() {
            calculateDuration();
            checkOverlap();
        });

        endDateInput.addEventListener('change', function() {
            calculateDuration();
            checkOverlap();
        });

        // Check overlap with existing academic years
        let checkOverlapTimeout;

        function checkOverlap() {
            clearTimeout(checkOverlapTimeout);
            checkOverlapTimeout = setTimeout(() => {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                const overlapWarning = document.getElementById('overlapWarning');
                const overlapYears = document.getElementById('overlapYears');

                if (!startDate || !endDate) {
                    overlapWarning.style.display = 'none';
                    return;
                }

                let url = `<?= base_url('admin/academic-years/check-overlap') ?>?start_date=${startDate}&end_date=${endDate}`;
                if (excludeId) {
                    url += `&exclude_id=${excludeId}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.overlaps) {
                            const yearNames = data.conflicting_years.map(y => y.year_name + ' (' + y.semester + ')').join(', ');
                            overlapYears.textContent = yearNames;
                            overlapWarning.style.display = 'block';
                        } else {
                            overlapWarning.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }, 500);
        }

        // Form validation before submit
        academicYearForm.addEventListener('submit', function(e) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (endDate <= startDate) {
                e.preventDefault();
                alert('Tanggal selesai harus lebih besar dari tanggal mulai');
                return false;
            }

            const diffDays = (endDate - startDate) / (1000 * 60 * 60 * 24);
            if (diffDays < 90) {
                e.preventDefault();
                alert('Durasi tahun ajaran minimal 3 bulan');
                return false;
            }

            if (diffDays > 400) {
                e.preventDefault();
                alert('Durasi tahun ajaran maksimal 13 bulan');
                return false;
            }

            // Disable submit button to prevent double submit
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Menyimpan...';
        });

        // Initial calculation
        calculateDuration();
        checkOverlap();
    });
</script>

<?= $this->endSection() ?>