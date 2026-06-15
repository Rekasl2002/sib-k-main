<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/students/edit.php
 *
 * Edit Data Siswa (Counselor / Guru BK)
 * - Paritas form dengan Koordinator BK, tetapi dalam lingkup siswa binaan.
 * - Kelas yang dapat dipilih hanya kelas binaan Guru BK ini.
 */

$errors = session()->getFlashdata('errors') ?? [];
if (!is_array($errors)) $errors = [];

if (isset($student) && is_object($student)) {
    $student = (array) $student;
}

$classes        = is_array($classes ?? null) ? $classes : [];
$parents        = is_array($parents ?? null) ? $parents : [];
$genderOptions  = is_array($gender_options ?? null) ? $gender_options : ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$religionOptions = is_array($religion_options ?? null) ? $religion_options : ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
$statusOptions  = is_array($status_options ?? null) ? $status_options : ['Aktif', 'Alumni', 'Pindah', 'Keluar'];
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Edit Siswa</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/students') ?>">Siswa Binaan</a></li>
                    <li class="breadcrumb-item active">Edit Siswa</li>
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
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i><strong>Terdapat kesalahan:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?><li><?= esc(is_string($error) ? $error : json_encode($error)) ?></li><?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Info Siswa -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4"><i class="mdi mdi-account-circle me-2"></i>Info Siswa</h4>
                <div class="text-center">
                    <img src="<?= user_avatar($student['profile_photo'] ?? null) ?>" alt="<?= esc($student['full_name'] ?? '-') ?>" class="avatar-lg rounded-circle mb-3">
                    <h5 class="mb-1"><?= esc($student['full_name'] ?? '-') ?></h5>
                    <p class="text-dark mb-2">@<?= esc($student['username'] ?? '-') ?></p>
                    <div class="mb-2">
                        <?php if (!empty($student['class_name'])): ?>
                            <span class="badge bg-primary font-size-12"><?= esc($student['grade_level'] ?? '-') ?> - <?= esc($student['class_name']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary font-size-12">Belum Ada Kelas</span>
                        <?php endif; ?>
                        <span class="badge bg-<?= ($student['status'] ?? '') === 'Aktif' ? 'success' : 'secondary' ?> font-size-12 ms-1"><?= esc($student['status'] ?? '-') ?></span>
                    </div>
                </div>
                <hr class="my-4">
                <div class="d-grid gap-2">
                    <?php if (!empty($student['id'])): ?>
                        <a href="<?= base_url('counselor/students/' . (int) $student['id']) ?>" class="btn btn-info"><i class="mdi mdi-eye me-1"></i> Lihat Profil</a>
                    <?php endif; ?>
                    <a href="<?= base_url('counselor/students') ?>" class="btn btn-secondary"><i class="mdi mdi-arrow-left me-1"></i> Kembali ke Daftar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Edit -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4"><i class="mdi mdi-account-edit me-2"></i>Edit Data Siswa</h4>
                <div class="alert alert-info" role="alert">
                    <i class="mdi mdi-information-outline me-2"></i>
                    Anda dapat mengubah data siswa binaan. Pilihan kelas dibatasi pada kelas binaan Anda.
                </div>

                <form action="<?= base_url('counselor/students/' . (int) ($student['id'] ?? 0)) ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= esc($student['id'] ?? '') ?>">

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= old('full_name') ?? esc($student['full_name'] ?? '') ?>" minlength="3" maxlength="100" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NISN <span class="text-danger">*</span></label>
                            <input type="text" name="nisn" class="form-control" value="<?= old('nisn') ?? esc($student['nisn'] ?? '') ?>" maxlength="10" inputmode="numeric" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" class="form-control" value="<?= old('nik') ?? esc($student['nik'] ?? '') ?>" maxlength="16" inputmode="numeric">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">Pilih</option>
                                <?php foreach ($genderOptions as $key => $label): ?>
                                    <option value="<?= esc($key) ?>" <?= (old('gender') ?? ($student['gender'] ?? '')) == $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="birth_place" class="form-control" value="<?= old('birth_place') ?? esc($student['birth_place'] ?? '') ?>" maxlength="100">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control" value="<?= old('birth_date') ?? ($student['birth_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Agama</label>
                            <select name="religion" class="form-select">
                                <option value="">Pilih</option>
                                <?php foreach ($religionOptions as $religion): ?>
                                    <option value="<?= esc($religion) ?>" <?= (old('religion') ?? ($student['religion'] ?? '')) == $religion ? 'selected' : '' ?>><?= esc($religion) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kelas (binaan) <span class="text-danger">*</span></label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= esc($class['id']) ?>" <?= (old('class_id') ?? ($student['class_id'] ?? '')) == $class['id'] ? 'selected' : '' ?>>
                                        <?= esc($class['grade_level'] ?? '-') ?> - <?= esc($class['class_name'] ?? '-') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Masuk</label>
                            <input type="date" name="admission_date" class="form-control" value="<?= old('admission_date') ?? ($student['admission_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="address" class="form-control" rows="3" maxlength="255"><?= old('address') ?? esc($student['address'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kebutuhan Khusus</label>
                            <input type="text" name="special_needs" class="form-control" value="<?= old('special_needs') ?? esc($student['special_needs'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Disabilitas</label>
                            <input type="text" name="disability" class="form-control" value="<?= old('disability') ?? esc($student['disability'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nomor KIP/PIP</label>
                            <input type="text" name="kip_pip_number" class="form-control" value="<?= old('kip_pip_number') ?? esc($student['kip_pip_number'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hobi</label>
                            <input type="text" name="hobi" class="form-control" value="<?= old('hobi') ?? esc($student['hobi'] ?? '') ?>" placeholder="Contoh: Membaca, sepak bola">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ekstrakurikuler / Organisasi</label>
                            <input type="text" name="ekskul_organisasi" class="form-control" value="<?= old('ekskul_organisasi') ?? esc($student['ekskul_organisasi'] ?? '') ?>" placeholder="Contoh: Pramuka, Rohis">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Ayah Kandung</label>
                            <input type="text" name="father_name" class="form-control" value="<?= old('father_name') ?? esc($student['father_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Ibu Kandung</label>
                            <input type="text" name="mother_name" class="form-control" value="<?= old('mother_name') ?? esc($student['mother_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Wali</label>
                            <input type="text" name="guardian_name" class="form-control" value="<?= old('guardian_name') ?? esc($student['guardian_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Orang Tua/Wali</label>
                            <select name="parent_id" class="form-select">
                                <option value="">Pilih Orang Tua</option>
                                <?php foreach ($parents as $parent): ?>
                                    <option value="<?= esc($parent['id']) ?>" <?= (old('parent_id') ?? ($student['parent_id'] ?? '')) == $parent['id'] ? 'selected' : '' ?>>
                                        <?= esc($parent['full_name'] ?? '-') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statusOptions as $opt): ?>
                                    <option value="<?= esc($opt) ?>" <?= (old('status') ?? ($student['status'] ?? '')) == $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" name="phone" class="form-control" value="<?= old('phone') ?? esc($student['phone'] ?? '') ?>" maxlength="30" inputmode="tel" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('counselor/students') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i> Kembali</a>
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
