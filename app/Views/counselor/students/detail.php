<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    /** @var array $student */


    // Label gender rapi
    $genderLabel = '-';
    if (($student['gender'] ?? '') === 'L') {
        $genderLabel = 'Laki-laki';
    } elseif (($student['gender'] ?? '') === 'P') {
        $genderLabel = 'Perempuan';
    }

    $ageText = student_age_text($student['birth_date'] ?? null);

    // Status badge sederhana
    $status = $student['status'] ?? '';
    $statusClass = 'secondary';
    if ($status === 'Aktif') {
        $statusClass = 'success';
    } elseif ($status === 'Alumni') {
        $statusClass = 'info';
    } elseif (in_array($status, ['Pindah', 'Keluar'], true)) {
        $statusClass = 'warning';
    }

    // Foto profil (fallback ke default-avatar.svg via helper user_avatar)
    $avatarUrl = user_avatar($student['profile_photo'] ?? null);
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Detail Siswa</h4>
            <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Guru BK</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/students') ?>">Siswa Binaan</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
                <a href="<?= base_url('counselor/students') ?>" class="btn btn-sm btn-secondary">
                    <i class="mdi mdi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Kolom utama -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body pb-2">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <img src="<?= esc($avatarUrl) ?>"
                             alt="Foto Siswa"
                             class="rounded-circle avatar-md img-thumbnail">
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">
                            <?= esc($student['full_name'] ?? '-') ?>
                        </h5>
                        <p class="mb-1 text-muted">
                            Kelas: <?= esc($student['class_name'] ?? '-') ?>
                            <?php if (!empty($student['grade_level'])): ?>
                                (Tingkat <?= esc($student['grade_level']) ?>)
                            <?php endif; ?>
                        </p>
                        <span class="badge bg-<?= esc($statusClass) ?> me-1">
                            <?= esc($status !== '' ? $status : 'Status tidak diketahui') ?>
                        </span>
                    </div>
                </div>

                <hr class="my-3">

                <h6 class="text-muted mb-3">Data Akademik & Identitas</h6>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">NIK</div>
                    <div class="col-sm-8"><?= esc($student['nik'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">NISN</div>
                    <div class="col-sm-8"><?= esc($student['nisn'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Jenis Kelamin</div>
                    <div class="col-sm-8"><?= esc($genderLabel) ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Tempat Lahir</div>
                    <div class="col-sm-8"><?= esc($student['birth_place'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Tanggal Lahir</div>
                    <div class="col-sm-8">
                        <?= esc($student['birth_date'] ?? '-') ?>
                        <?php if ($ageText !== '-'): ?>
                            <span class="text-muted">(<?= esc($ageText) ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Agama</div>
                    <div class="col-sm-8"><?= esc($student['religion'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Tanggal Masuk</div>
                    <div class="col-sm-8"><?= esc($student['admission_date'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Alamat</div>
                    <div class="col-sm-8">
                        <?= nl2br(esc($student['address'] ?? '-')) ?>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Kebutuhan Khusus</div>
                    <div class="col-sm-8"><?= esc($student['special_needs'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Disabilitas</div>
                    <div class="col-sm-8"><?= esc($student['disability'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Nomor KIP/PIP</div>
                    <div class="col-sm-8"><?= esc($student['kip_pip_number'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Nama Ayah Kandung</div>
                    <div class="col-sm-8"><?= esc($student['father_name'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Nama Ibu Kandung</div>
                    <div class="col-sm-8"><?= esc($student['mother_name'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Nama Wali</div>
                    <div class="col-sm-8"><?= esc($student['guardian_name'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <!-- Orang Tua / Wali -->
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-3">Orang Tua / Wali</h6>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Nama Orang Tua</div>
                    <div class="col-sm-8"><?= esc($student['parent_name'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">No. HP Orang Tua</div>
                    <div class="col-sm-8"><?= esc($student['parent_phone'] ?? '-') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom samping -->
    <div class="col-lg-4">
        <!-- Info akun -->
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-3">Akun Sistem</h6>

                <div class="row mb-2">
                    <div class="col-sm-5 text-muted">Username</div>
                    <div class="col-sm-7"><?= esc($student['username'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-5 text-muted">Email</div>
                    <div class="col-sm-7">
                        <?php if (!empty($student['email'])): ?>
                            <a href="mailto:<?= esc($student['email']) ?>">
                                <?= esc($student['email']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-5 text-muted">No. HP Siswa</div>
                    <div class="col-sm-7"><?= esc($student['phone'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-5 text-muted">Terakhir Login</div>
                    <div class="col-sm-7"><?= esc($student['last_login'] ?? '-') ?></div>
                </div>
            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
