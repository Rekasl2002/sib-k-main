<?php

/**
 * File Path: app/Views/admin/classes/form.php
 * 
 * Class Form View
 * Form untuk create dan edit kelas
 * 
 * @package    SIB-K
 * @subpackage Views/Admin/Classes
 * @category   Class Management
 * @author     Development Team
 * @created    2025-01-06
 */

$isEdit = isset($class);
$formAction = $isEdit ? base_url('admin/classes/update/' . $class['id']) : base_url('admin/classes/store');
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
                    <?= $isEdit ? 'Edit Data Kelas' : 'Formulir Tambah Kelas' ?>
                </h4>

                <form action="<?= $formAction ?>" method="post" id="classForm">
                    <?= csrf_field() ?>

                    <div class="row">
                        <!-- Academic Year -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="academic_year_id" class="form-label">
                                    Tahun Ajaran <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                    <option value="">-- Pilih Tahun Ajaran --</option>
                                    <?php foreach ($academic_years as $year): ?>
                                        <option value="<?= $year['id'] ?>"
                                            <?= (old('academic_year_id', $class['academic_year_id'] ?? '') == $year['id']) ? 'selected' : '' ?>>
                                            <?= esc($year['year_name']) ?> - <?= esc($year['semester']) ?>
                                            <?= $year['is_active'] ? '(Aktif)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Pilih tahun ajaran untuk kelas ini</div>
                            </div>
                        </div>

                        <!-- Grade Level -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="grade_level" class="form-label">
                                    Tingkat Kelas <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <option value="">-- Pilih Tingkat --</option>
                                    <?php foreach ($grade_levels as $key => $value): ?>
                                        <option value="<?= $key ?>"
                                            <?= (old('grade_level', $class['grade_level'] ?? '') == $key) ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Major -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="major" class="form-label">
                                    Jurusan
                                </label>
                                <select class="form-select" id="major" name="major">
                                    <option value="">-- Pilih Jurusan --</option>
                                    <?php foreach ($majors as $key => $value): ?>
                                        <option value="<?= $key ?>"
                                            <?= (old('major', $class['major'] ?? '') == $key) ? 'selected' : '' ?>>
                                            <?= $key ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Opsional</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Class Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class_name" class="form-label">
                                    Nama Kelas <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control"
                                        id="class_name"
                                        name="class_name"
                                        value="<?= old('class_name', $class['class_name'] ?? '') ?>"
                                        placeholder="Contoh: X-IPA-1-Ganjil-2025 atau X-IPA-A-Ganjil-2025"
                                        required>
                                </div>
                                <div class="form-text">
                                    Format: Tingkat-Jurusan-Nomor-Semester-Tahun
                                </div>
                                <div id="suggestedName" class="text-success small mt-1" style="display: none;">
                                    <i class="mdi mdi-lightbulb-on-outline"></i> Saran: <strong id="suggestedNameText"></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Max Students -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="max_students" class="form-label">
                                    Kapasitas Maksimal
                                </label>
                                <input type="number"
                                    class="form-control"
                                    id="max_students"
                                    name="max_students"
                                    min="1"
                                    max="50"
                                    value="<?= old('max_students', $class['max_students'] ?? '36') ?>"
                                    placeholder="36">
                                <div class="form-text">Maks 50 siswa</div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="is_active" class="form-label">
                                    Status
                                </label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="1" <?= (old('is_active', $class['is_active'] ?? '1') == '1') ? 'selected' : '' ?>>
                                        Aktif
                                    </option>
                                    <option value="0" <?= (old('is_active', $class['is_active'] ?? '1') == '0') ? 'selected' : '' ?>>
                                        Tidak Aktif
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">
                        <i class="mdi mdi-account-group text-primary me-2"></i>Penugasan Guru
                    </h5>

                    <div class="row">
                        <!-- Homeroom Teacher -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="homeroom_teacher_id" class="form-label">
                                    Wali Kelas
                                </label>
                                <select class="form-select" id="homeroom_teacher_id" name="homeroom_teacher_id">
                                    <option value="">-- Pilih Wali Kelas --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= $teacher['id'] ?>"
                                            <?= (old('homeroom_teacher_id', $class['homeroom_teacher_id'] ?? '') == $teacher['id']) ? 'selected' : '' ?>>
                                            <?= esc($teacher['full_name']) ?>
                                            <?php if ($teacher['email']): ?>
                                                (<?= esc($teacher['email']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="mdi mdi-information-outline"></i>
                                    Satu guru hanya bisa menjadi wali kelas di satu kelas aktif
                                </div>
                            </div>
                        </div>

                        <!-- Counselor -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="counselor_id" class="form-label">
                                    Guru BK
                                </label>
                                <select class="form-select" id="counselor_id" name="counselor_id">
                                    <option value="">-- Pilih Guru BK --</option>
                                    <?php foreach ($counselors as $counselor): ?>
                                        <option value="<?= $counselor['id'] ?>"
                                            <?= (old('counselor_id', $class['counselor_id'] ?? '') == $counselor['id']) ? 'selected' : '' ?>>
                                            <?= esc($counselor['full_name']) ?>
                                            <?php if ($counselor['email']): ?>
                                                (<?= esc($counselor['email']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="mdi mdi-information-outline"></i>
                                    Guru BK dapat menangani beberapa kelas
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($isEdit && isset($class['student_count'])): ?>
                        <div class="mb alert-info" role="alert">
                            <i class="mdi mdi-information me-2"></i>
                            <strong>Info:</strong> Kelas ini saat ini memiliki <strong><?= $class['student_count'] ?> siswa aktif</strong>.
                            <?php if ($class['student_count'] > 0): ?>
                                Kapasitas tidak boleh kurang dari jumlah siswa aktif.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('admin/classes') ?>" class="btn btn-secondary">
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
        const academicYearSelect = document.getElementById('academic_year_id');
        const gradeLevelSelect = document.getElementById('grade_level');
        const majorSelect = document.getElementById('major');
        const classNameInput = document.getElementById('class_name');
        const btnAutoGenerate = document.getElementById('btnAutoGenerate');
        const suggestedNameDiv = document.getElementById('suggestedName');
        const suggestedNameText = document.getElementById('suggestedNameText');
        const classForm = document.getElementById('classForm');

        // Auto-generate class name
        function generateClassName() {
            const academicYearId = academicYearSelect.value;
            const gradeLevel = gradeLevelSelect.value;
            const major = majorSelect.value;

            if (!academicYearId || !gradeLevel) {
                alert('Silakan pilih Tahun Ajaran dan Tingkat Kelas terlebih dahulu');
                return;
            }

            // Show loading
            btnAutoGenerate.disabled = true;
            btnAutoGenerate.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Loading...';

            // AJAX request to get suggested name
            fetch(`<?= base_url('admin/classes/get-suggested-name') ?>?academic_year_id=${academicYearId}&grade_level=${gradeLevel}&major=${major}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        suggestedNameText.textContent = data.suggested_name;
                        suggestedNameDiv.style.display = 'block';
                        classNameInput.value = data.suggested_name;
                        classNameInput.focus();
                    } else {
                        alert(data.message || 'Gagal generate nama kelas');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat generate nama kelas');
                })
                .finally(() => {
                    btnAutoGenerate.disabled = false;
                    btnAutoGenerate.innerHTML = '<i class="mdi mdi-auto-fix"></i> Auto';
                });
        }

        // Event listener for auto-generate button
        btnAutoGenerate.addEventListener('click', generateClassName);

        // Show suggestion when fields change
        let debounceTimer;

        function showSuggestion() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const academicYearId = academicYearSelect.value;
                const gradeLevel = gradeLevelSelect.value;
                const major = majorSelect.value;

                if (academicYearId && gradeLevel) {
                    fetch(`<?= base_url('admin/classes/get-suggested-name') ?>?academic_year_id=${academicYearId}&grade_level=${gradeLevel}&major=${major}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.suggested_name !== classNameInput.value) {
                                suggestedNameText.textContent = data.suggested_name;
                                suggestedNameDiv.style.display = 'block';
                            } else {
                                suggestedNameDiv.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            }, 500);
        }

        academicYearSelect.addEventListener('change', showSuggestion);
        gradeLevelSelect.addEventListener('change', showSuggestion);
        majorSelect.addEventListener('change', showSuggestion);

        // Form validation before submit
        classForm.addEventListener('submit', function(e) {
            const maxStudents = parseInt(document.getElementById('max_students').value);

            if (maxStudents < 1 || maxStudents > 50) {
                e.preventDefault();
                alert('Kapasitas maksimal harus antara 1 sampai 50 siswa');
                return false;
            }

            // Disable submit button to prevent double submit
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Menyimpan...';
        });

        // Click suggestion to use it
        suggestedNameDiv.addEventListener('click', function() {
            classNameInput.value = suggestedNameText.textContent;
            suggestedNameDiv.style.display = 'none';
            classNameInput.focus();
        });
    });
</script>

<?= $this->endSection() ?>