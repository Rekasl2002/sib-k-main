<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File Path: app/Views/koordinator/students/create.php
 * Fitur: Manajemen Siswa.
 * Peran/izin: Koordinator BK dengan permission manage_students.
 * Berhubungan dengan: Koordinator\StudentController, StudentService, users,
 * students, classes, dan akun orang tua pada users.role_id Orang Tua.
 */

$errors = session()->getFlashdata('errors') ?? [];
if (! is_array($errors)) {
    $errors = [];
}

$classes = is_array($classes ?? null) ? $classes : [];
$parents = is_array($parents ?? null) ? $parents : [];
$genderOptions = is_array($gender_options ?? null) ? $gender_options : ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$religionOptions = is_array($religion_options ?? null) ? $religion_options : ['Islam'];
$statusOptions = is_array($status_options ?? null) ? $status_options : ['Aktif'];

$fieldError = static fn (string $key): string => isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . esc($errors[$key]) . '</div>' : '';
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' is-invalid' : '';
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Tambah Siswa</h4>
                <p class="text-muted mb-0">Koordinator BK dapat menambahkan akun siswa dan menghubungkannya dengan akun orang tua.</p>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/students') ?>">Siswa</a></li>
                    <li class="breadcrumb-item active">Tambah</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (! empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Periksa kembali data berikut:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="<?= base_url('koordinator/students/store') ?>" method="post" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="create_with_user" value="1">

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Akun Siswa</h5>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control<?= $invalid('full_name') ?>" value="<?= old('full_name') ?>" required>
                        <?= $fieldError('full_name') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control<?= $invalid('username') ?>" value="<?= old('username') ?>" required>
                        <small class="text-muted">Contoh: siswa_10a_01.</small>
                        <?= $fieldError('username') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control<?= $invalid('email') ?>" value="<?= old('email') ?>">
                        <?= $fieldError('email') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control<?= $invalid('phone') ?>" value="<?= old('phone') ?>" placeholder="08xxxxxxxxxx">
                        <?= $fieldError('phone') ?>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Password Awal <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control<?= $invalid('password') ?>" required>
                        <small class="text-muted">Minimal 6 karakter.</small>
                        <?= $fieldError('password') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Data Siswa</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NISN <span class="text-danger">*</span></label>
                            <input type="text" name="nisn" class="form-control<?= $invalid('nisn') ?>" value="<?= old('nisn') ?>" maxlength="10" inputmode="numeric" required>
                            <?= $fieldError('nisn') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" class="form-control<?= $invalid('nik') ?>" value="<?= old('nik') ?>" maxlength="16" inputmode="numeric">
                            <?= $fieldError('nik') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select<?= $invalid('gender') ?>" required>
                                <option value="">Pilih</option>
                                <?php foreach ($genderOptions as $key => $label): ?>
                                    <option value="<?= esc($key) ?>" <?= old('gender') === (string) $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('gender') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="class_id" class="form-select<?= $invalid('class_id') ?>">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= (int) ($class['id'] ?? 0) ?>" <?= old('class_id') == ($class['id'] ?? null) ? 'selected' : '' ?>>
                                        <?= esc(($class['grade_level'] ?? '-') . ' - ' . ($class['class_name'] ?? '-')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('class_id') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select<?= $invalid('status') ?>">
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= esc($status) ?>" <?= old('status', 'Aktif') === (string) $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('status') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="birth_place" class="form-control<?= $invalid('birth_place') ?>" value="<?= old('birth_place') ?>">
                            <?= $fieldError('birth_place') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control<?= $invalid('birth_date') ?>" value="<?= old('birth_date') ?>">
                            <?= $fieldError('birth_date') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Agama</label>
                            <select name="religion" class="form-select<?= $invalid('religion') ?>">
                                <option value="">Pilih</option>
                                <?php foreach ($religionOptions as $religion): ?>
                                    <option value="<?= esc($religion) ?>" <?= old('religion') === (string) $religion ? 'selected' : '' ?>><?= esc($religion) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('religion') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control<?= $invalid('address') ?>" rows="3"><?= old('address') ?></textarea>
                        <?= $fieldError('address') ?>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Ayah Kandung</label>
                            <input type="text" name="father_name" class="form-control<?= $invalid('father_name') ?>" value="<?= old('father_name') ?>">
                            <?= $fieldError('father_name') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Ibu Kandung</label>
                            <input type="text" name="mother_name" class="form-control<?= $invalid('mother_name') ?>" value="<?= old('mother_name') ?>">
                            <?= $fieldError('mother_name') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Wali</label>
                            <input type="text" name="guardian_name" class="form-control<?= $invalid('guardian_name') ?>" value="<?= old('guardian_name') ?>">
                            <?= $fieldError('guardian_name') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Orang Tua/Wali</label>
                            <select name="parent_id" class="form-select<?= $invalid('parent_id') ?>">
                                <option value="">Belum dihubungkan</option>
                                <?php foreach ($parents as $parent): ?>
                                    <option value="<?= (int) ($parent['id'] ?? 0) ?>" <?= old('parent_id') == ($parent['id'] ?? null) ? 'selected' : '' ?>>
                                        <?= esc(($parent['full_name'] ?? '-') . (! empty($parent['phone']) ? ' - ' . $parent['phone'] : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fieldError('parent_id') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Masuk</label>
                            <input type="date" name="admission_date" class="form-control<?= $invalid('admission_date') ?>" value="<?= old('admission_date', date('Y-m-d')) ?>">
                            <?= $fieldError('admission_date') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nomor KIP/PIP</label>
                            <input type="text" name="kip_pip_number" class="form-control<?= $invalid('kip_pip_number') ?>" value="<?= old('kip_pip_number') ?>">
                            <?= $fieldError('kip_pip_number') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kebutuhan Khusus</label>
                            <input type="text" name="special_needs" class="form-control<?= $invalid('special_needs') ?>" value="<?= old('special_needs') ?>" placeholder="Tulis 'Tidak Ada' bila tidak ada">
                            <?= $fieldError('special_needs') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Disabilitas</label>
                            <input type="text" name="disability" class="form-control<?= $invalid('disability') ?>" value="<?= old('disability') ?>" placeholder="Tulis 'Tidak Ada' bila tidak ada">
                            <?= $fieldError('disability') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hobi</label>
                            <input type="text" name="hobi" class="form-control<?= $invalid('hobi') ?>" value="<?= old('hobi') ?>" placeholder="Contoh: Membaca, sepak bola">
                            <?= $fieldError('hobi') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ekstrakurikuler / Organisasi</label>
                            <input type="text" name="ekskul_organisasi" class="form-control<?= $invalid('ekskul_organisasi') ?>" value="<?= old('ekskul_organisasi') ?>" placeholder="Contoh: Pramuka, Rohis">
                            <?= $fieldError('ekskul_organisasi') ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('koordinator/students') ?>" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i>Simpan Siswa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?= $this->endSection() ?>
