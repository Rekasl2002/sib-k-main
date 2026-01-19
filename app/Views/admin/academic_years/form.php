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
 */

$isEdit = isset($academic_year);

// Normalisasi biar view tahan banting
$academic_year = $academic_year ?? [];
if (is_object($academic_year)) {
    $academic_year = (array) $academic_year;
}

$breadcrumb       = $breadcrumb ?? [];
$page_title       = $page_title ?? ($isEdit ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran');
$semester_options = $semester_options ?? [];
$suggested        = $suggested ?? null;

// ✅ Dropdown year_name options (dikirim dari Controller: $data['year_name_options'])
$year_name_options = $year_name_options ?? [];

$formAction = $isEdit
    ? base_url('admin/academic-years/update/' . ($academic_year['id'] ?? 0))
    : base_url('admin/academic-years/store');

// Nilai terpilih year_name: old -> data edit -> suggested (create)
$selectedYearName = old('year_name', $academic_year['year_name'] ?? ($suggested['year_name'] ?? ''));

// Pastikan nilai terpilih ada di option list
$optsYearName = is_array($year_name_options) ? $year_name_options : [];
if ($selectedYearName && !in_array($selectedYearName, $optsYearName, true)) {
    array_unshift($optsYearName, $selectedYearName);
    $optsYearName = array_values(array_unique($optsYearName));
}
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18"><?= esc($page_title) ?></h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php
                        $title = esc($item['title'] ?? '');
                        $link  = $item['link'] ?? null;
                        ?>
                        <?php if ($link): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= esc($link) ?>"><?= $title ?></a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= $title ?></li>
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
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Validasi Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ((array) session()->getFlashdata('errors') as $error): ?>
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

                <form action="<?= esc($formAction) ?>" method="post" id="academicYearForm">
                    <?= csrf_field() ?>

                    <div class="row">
                        <!-- Year Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="year_name" class="form-label">
                                    Nama Tahun Ajaran <span class="text-danger">*</span>
                                </label>

                                <!-- ✅ Dropdown agar user tidak salah ketik -->
                                <div class="input-group">
                                    <select class="form-select" id="year_name" name="year_name" required>
                                        <option value="">-- Pilih Tahun Ajaran --</option>
                                        <?php foreach ($optsYearName as $yn): ?>
                                            <option value="<?= esc($yn) ?>" <?= ($selectedYearName === $yn) ? 'selected' : '' ?>>
                                                <?= esc($yn) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

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
                                        <option value="<?= esc($key) ?>"
                                            <?= (old('semester', $academic_year['semester'] ?? '') == $key) ? 'selected' : '' ?>>
                                            <?= esc($value) ?>
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
                                    value="<?= esc(old('start_date', $academic_year['start_date'] ?? ($suggested['start_date'] ?? ''))) ?>"
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
                                    value="<?= esc(old('end_date', $academic_year['end_date'] ?? ($suggested['end_date'] ?? ''))) ?>"
                                    required>
                                <div id="durationInfo" class="text-info small mt-1" style="display: none;">
                                    <i class="mdi mdi-calendar-range"></i> Durasi: <strong id="durationText"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overlap Warning -->
                    <div id="overlapWarning" class="alert alert-warning" role="alert" style="display: none;">
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
                        <div class="alert alert-info" role="alert">
                            <i class="mdi mdi-information me-2"></i>
                            <strong>Info:</strong> Tahun ajaran ini memiliki <strong><?= (int) $academic_year['class_count'] ?> kelas</strong>.
                            <?php if ((int)$academic_year['class_count'] > 0): ?>
                                Tahun ajaran tidak dapat dihapus selama masih ada kelas.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= esc(base_url('admin/academic-years')) ?>" class="btn btn-secondary">
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
    const yearNameSelect  = document.getElementById('year_name');
    const semesterSelect  = document.getElementById('semester');
    const startDateInput  = document.getElementById('start_date');
    const endDateInput    = document.getElementById('end_date');
    const btnAutoYear     = document.getElementById('btnAutoYear');
    const academicYearForm = document.getElementById('academicYearForm');

    const isEdit   = <?= $isEdit ? 'true' : 'false' ?>;
    const excludeId = isEdit ? <?= (int)($academic_year['id'] ?? 0) ?> : null;

    function setYearNameValue(val) {
        if (!val) return;
        const exists = Array.from(yearNameSelect.options).some(o => o.value === val);
        if (!exists) {
            yearNameSelect.add(new Option(val, val));
        }
        yearNameSelect.value = val;
    }

    // Use suggestion button (only for create) - kalau elemen ada di layout/partial lain
    <?php if (!$isEdit && is_array($suggested) && !empty($suggested)): ?>
        const useSuggestionBtn = document.getElementById('useSuggestion');
        if (useSuggestionBtn) {
            useSuggestionBtn.addEventListener('click', function() {
                setYearNameValue('<?= esc($suggested['year_name'] ?? '', 'js') ?>');
                semesterSelect.value = '<?= esc($suggested['semester'] ?? '', 'js') ?>';
                startDateInput.value = '<?= esc($suggested['start_date'] ?? '', 'js') ?>';
                endDateInput.value   = '<?= esc($suggested['end_date'] ?? '', 'js') ?>';
                calculateDuration();
                checkOverlap();
                validateYearName();
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

        btnAutoYear.disabled = true;
        btnAutoYear.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Loading...';

        const url = `<?= esc(base_url('admin/academic-years/generate-year-name')) ?>?start_date=${encodeURIComponent(startDate)}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    setYearNameValue(data.year_name || '');
                    semesterSelect.value = data.semester || '';
                    validateYearName();
                } else {
                    alert((data && data.message) ? data.message : 'Gagal generate nama tahun ajaran');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Terjadi kesalahan saat generate nama tahun ajaran');
            })
            .finally(() => {
                btnAutoYear.disabled = false;
                btnAutoYear.innerHTML = '<i class="mdi mdi-auto-fix"></i> Auto';
            });
    });

    // Validate year name format
    function validateYearName() {
        const yearName = yearNameSelect.value;
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

        const parts = yearName.split('/');
        const year1 = parseInt(parts[0], 10);
        const year2 = parseInt(parts[1], 10);

        if (year2 !== year1 + 1) {
            warningText.textContent = 'Tahun kedua harus lebih besar 1 dari tahun pertama';
            warningDiv.style.display = 'block';
            return;
        }

        warningDiv.style.display = 'none';
    }

    yearNameSelect.addEventListener('change', validateYearName);

    // Calculate duration
    function calculateDuration() {
        const startDate = startDateInput.value;
        const endDate   = endDateInput.value;
        const durationInfo = document.getElementById('durationInfo');
        const durationText = document.getElementById('durationText');

        if (!startDate || !endDate) {
            durationInfo.style.display = 'none';
            return;
        }

        const start = new Date(startDate);
        const end   = new Date(endDate);

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
            const endDate   = endDateInput.value;
            const overlapWarning = document.getElementById('overlapWarning');
            const overlapYears   = document.getElementById('overlapYears');

            if (!startDate || !endDate) {
                overlapWarning.style.display = 'none';
                return;
            }

            let url = `<?= esc(base_url('admin/academic-years/check-overlap')) ?>?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
            if (excludeId) {
                url += `&exclude_id=${encodeURIComponent(excludeId)}`;
            }

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data && data.success && data.overlaps) {
                        const yearNames = (data.conflicting_years || [])
                            .map(y => (y.year_name + ' (' + y.semester + ')'))
                            .join(', ');
                        overlapYears.textContent = yearNames;
                        overlapWarning.style.display = 'block';
                    } else {
                        overlapWarning.style.display = 'none';
                    }
                })
                .catch(err => console.error('Error:', err));
        }, 500);
    }

    // Form validation before submit
    academicYearForm.addEventListener('submit', function(e) {
        const startVal = startDateInput.value;
        const endVal   = endDateInput.value;

        if (!startVal || !endVal) {
            e.preventDefault();
            alert('Tanggal mulai dan tanggal selesai wajib diisi');
            return false;
        }

        const startDate = new Date(startVal);
        const endDate   = new Date(endVal);

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

    // Initial run
    calculateDuration();
    checkOverlap();
    validateYearName();
});
</script>

<?= $this->endSection() ?>
