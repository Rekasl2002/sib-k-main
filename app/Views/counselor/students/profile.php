<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/counselor/students/profile.php
 *
 * Student Profile (Counselor)
 * - Aman terhadap variabel null/undefined (pakai ?? dan pengecekan)
 * - Breadcrumb & tombol diarahkan ke counselor/*
 * - Aksi sesuai izin: hanya Edit jika $canUpdate = true
 *
 * Variabel yang diharapkan:
 * - $student (array) : data siswa + user + kelas (sebisa mungkin)
 * - (opsional) $canUpdate (bool)
 */

// Helper nilai aman
$fullName   = $student['full_name']   ?? '-';
$username   = $student['username']    ?? null; // bisa tidak ada jika tidak di-select
$email      = $student['email']       ?? null;
$phone      = $student['phone']       ?? null;
$kelas      = $student['class_name']  ?? null;
$grade      = $student['grade_level'] ?? null;
$status     = $student['status']      ?? '-';
$gender     = $student['gender']      ?? null;

$nik        = $student['nik']         ?? '-';
$nisn       = $student['nisn']        ?? '-';

$birthPlace = $student['birth_place'] ?? null;
$birthDate  = $student['birth_date']  ?? null;
$religion   = $student['religion']    ?? null;
$address    = $student['address']     ?? null;
$specialNeeds = $student['special_needs'] ?? null;
$disability = $student['disability'] ?? null;
$kipPipNumber = $student['kip_pip_number'] ?? null;
$fatherName = $student['father_name'] ?? null;
$motherName = $student['mother_name'] ?? null;
$guardianName = $student['guardian_name'] ?? null;

$admission  = $student['admission_date'] ?? null;

$userId     = $student['user_id']     ?? null; // biasanya id user
$studentId  = $student['id']          ?? null;

$isActive   = (int) ($student['is_active'] ?? 1);
$lastLogin  = $student['last_login']      ?? null;
$userCreated= $student['user_created_at'] ?? null;

$parentId   = $student['parent_id']   ?? null;
$parentName = $student['parent_name'] ?? null;
$parentPhone= $student['parent_phone']?? null;

$avatar     = user_avatar($student['profile_photo'] ?? null);

// Warna status
$statusColors = [
    'Aktif'  => 'success',
    'Alumni' => 'info',
    'Pindah' => 'warning',
    'Keluar' => 'danger',
];
$statusColor = $statusColors[$status] ?? 'secondary';

// Hitung umur jika ada birth_date
$ageText = '-';
if (!empty($birthDate)) {
    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age   = $today->diff($birth)->y;
        $ageText = date('d F Y', strtotime($birthDate)) . ' ';
        $ageText .= '<span class="text-dark">(' . $age . ' tahun)</span>';
    } catch (\Throwable $e) {
        $ageText = date('d F Y', strtotime($birthDate));
    }
}
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Profil Siswa</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/students') ?>">Siswa</a></li>
                    <li class="breadcrumb-item active">Profil Siswa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                <?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Student Profile Header -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="text-center">
                            <img src="<?= $avatar ?>"
                                 alt="<?= esc($fullName) ?>"
                                 class="avatar-xl rounded-circle mb-3">

                            <h4 class="mb-1"><?= esc($fullName) ?></h4>
                            <?php if (!empty($username)): ?>
                                <p class="text-dark mb-2">@<?= esc($username) ?></p>
                            <?php elseif (!empty($email)): ?>
                                <p class="text-dark mb-2"><?= esc($email) ?></p>
                            <?php endif; ?>

                            <div class="mb-3">
                                <?php if (!empty($kelas)): ?>
                                    <span class="badge bg-primary font-size-14">
                                        <?= esc($grade ?? '-') ?> - <?= esc($kelas) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary font-size-14">Belum Ada Kelas</span>
                                <?php endif; ?>

                                <span class="badge bg-<?= $statusColor ?> font-size-14 ms-1">
                                    <?= esc($status) ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <!--<a href="mailto:<?= esc($student['email']) ?>" class="btn btn-sm btn-soft-primary">
                                    <i class="mdi mdi-email-outline me-1"></i>Email
                                </a>-->

                                <?php if (!empty($student['phone'])): ?>
                                    <!--<a href="tel:<?= esc($student['phone']) ?>" class="btn btn-sm btn-soft-success">
                                        <i class="mdi mdi-phone-outline me-1"></i>Telepon
                                    </a>-->

                                    <?= view('components/wa_button', [
                                        'phone' => $student['phone'],
                                        'label' => 'WhatsApp',
                                        'class' => 'btn btn-sm btn-success',
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 d-flex flex-column">
                        <div class="d-flex gap-2 flex-wrap mt-auto">
                            <?php if (!empty($canUpdate) && !empty($studentId)): ?>
                            <a href="<?= base_url('counselor/students/' . (int) $studentId . '/edit') ?>" class="btn btn-primary">
                                <i class="mdi mdi-pencil me-1"></i>Edit Data
                            </a>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordStudentModal">
                                <i class="mdi mdi-key-variant me-1"></i>Reset Password
                            </button>
                            <?php endif; ?>
                            <a href="<?= base_url('counselor/students') ?>" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>Kembali
                            </a>
                        </div>
                    </div>
                </div> <!-- /row -->
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Personal Information -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="mdi mdi-account me-2"></i>Informasi Personal
                </h5>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-dark" style="width: 40%;">
                                    <i class="mdi mdi-account me-1"></i>Nama Lengkap
                                </td>
                                <td class="fw-medium"><?= esc($fullName) ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-gender-<?= ($gender === 'L') ? 'male' : 'female' ?> me-1"></i>Jenis Kelamin
                                </td>
                                <td class="fw-medium">
                                    <?php
                                        if ($gender === 'L') echo 'Laki-laki';
                                        elseif ($gender === 'P') echo 'Perempuan';
                                        else echo '-';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-map-marker me-1"></i>Tempat Lahir
                                </td>
                                <td class="fw-medium"><?= !empty($birthPlace) ? esc($birthPlace) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-calendar me-1"></i>Tanggal Lahir
                                </td>
                                <td class="fw-medium">
                                    <?= $ageText ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-book-cross me-1"></i>Agama
                                </td>
                                <td class="fw-medium"><?= !empty($religion) ? esc($religion) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-home me-1"></i>Alamat
                                </td>
                                <td class="fw-medium"><?= !empty($address) ? esc($address) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-account-heart me-1"></i>Kebutuhan Khusus</td>
                                <td class="fw-medium"><?= !empty($specialNeeds) ? esc($specialNeeds) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-wheelchair-accessibility me-1"></i>Disabilitas</td>
                                <td class="fw-medium"><?= !empty($disability) ? esc($disability) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-soccer me-1"></i>Hobi</td>
                                <td class="fw-medium"><?= !empty($student['hobi']) ? esc($student['hobi']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-account-group me-1"></i>Ekstrakurikuler / Organisasi</td>
                                <td class="fw-medium"><?= !empty($student['ekskul_organisasi']) ? esc($student['ekskul_organisasi']) : '-' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Information -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="mdi mdi-school me-2"></i>Informasi Akademik
                </h5>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-dark" style="width: 40%;"><i class="mdi mdi-identifier me-1"></i>NISN</td>
                                <td class="fw-medium"><?= esc($nisn) ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-card-account-details me-1"></i>NIK</td>
                                <td class="fw-medium"><?= esc($nik) ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-google-classroom me-1"></i>Kelas</td>
                                <td class="fw-medium"><?= !empty($kelas) ? esc(($grade ?? '') . ' - ' . $kelas) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-stairs me-1"></i>Tingkat</td>
                                <td class="fw-medium"><?= !empty($grade) ? esc($grade) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-calendar-import me-1"></i>Tanggal Masuk</td>
                                <td class="fw-medium"><?= !empty($admission) ? date('d F Y', strtotime($admission)) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-card-bulleted me-1"></i>Nomor KIP/PIP</td>
                                <td class="fw-medium"><?= !empty($kipPipNumber) ? esc($kipPipNumber) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-flag me-1"></i>Status Siswa</td>
                                <td class="fw-medium"><span class="badge bg-<?= $statusColor ?>"><?= esc($status) ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Kegiatan / Acara BK -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="mdi mdi-clipboard-text-clock me-2"></i>Kegiatan / Acara BK yang Diikuti
                </h5>
                <?php if (! empty($bkActivities)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-dark">
                                    <th>Jenis Layanan</th>
                                    <th>Judul/Topik</th>
                                    <th>Tempat/Lokasi</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bkActivities as $act): ?>
                                    <?php $jadwal = $act['held_at'] ?? $act['scheduled_at'] ?? null; ?>
                                    <tr class="text-dark">
                                        <td><span class="badge bg-info"><?= esc($act['service_type'] ?? '-') ?></span></td>
                                        <td><?= esc($act['title'] ?? '-') ?></td>
                                        <td><?= !empty($act['location']) ? esc($act['location']) : '-' ?></td>
                                        <td><?= $jadwal ? esc(date('d M Y, H:i', strtotime($jadwal))) : '-' ?></td>
                                        <td><span class="badge bg-secondary"><?= esc($act['status'] ?? '-') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-dark mb-0">Belum ada kegiatan atau acara BK yang tercatat untuk siswa ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Parent/Guardian Information -->
<?php if (!empty($parentId)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="mdi mdi-account-supervisor me-2"></i>Informasi Orang Tua / Wali
                    </h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-dark" style="width: 40%;">
                                                <i class="mdi mdi-account me-1"></i>Nama
                                            </td>
                                            <td class="fw-medium"><?= esc($parentName ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-dark">
                                                <i class="mdi mdi-phone me-1"></i>Telepon
                                            </td>
                                            <td class="fw-medium">
                                                <?php if (!empty($student['parent_phone'])): ?>
                                                    <a href="tel:<?= esc($student['parent_phone']) ?>" class="me-2">
                                                        <?= esc($student['parent_phone']) ?>
                                                    </a>

                                                    <?= view('components/wa_button', [
                                                        'phone' => $student['parent_phone'],
                                                        'label' => 'WhatsApp',
                                                        'class' => 'btn btn-sm btn-success',
                                                    ]) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column gap-2 justify-content-center h-100">
                                <?php if (!empty($parentId)): ?>
                                    <a href="<?= base_url('counselor/parents/' . (int) $parentId) ?>" class="btn btn-outline-info">
                                        <i class="mdi mdi-account-supervisor me-1"></i>Lihat Profil Orang Tua
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('counselor/students') ?>" class="btn btn-outline-secondary">
                                    <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Daftar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Account Information (read-only untuk counselor) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="mdi mdi-account-key me-2"></i>Informasi Akun User
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-dark" style="width: 40%;">
                                            <i class="mdi mdi-account-key me-1"></i>Username
                                        </td>
                                        <td class="fw-medium">
                                            <?php if (!empty($username)): ?>
                                                <?= esc($username) ?>
                                            <?php else: ?>
                                                <span class="text-dark">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-dark">
                                            <i class="mdi mdi-email me-1"></i>Email
                                        </td>
                                        <td class="fw-medium"><?= !empty($email) ? esc($email) : '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-dark">
                                            <i class="mdi mdi-phone me-1"></i>Telepon
                                        </td>
                                        <td class="fw-medium"><?= !empty($phone) ? esc($phone) : '-' ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-dark" style="width: 40%;">
                                            <i class="mdi mdi-check-circle me-1"></i>Status Akun
                                        </td>
                                        <td class="fw-medium">
                                            <?php if ($isActive === 1): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-dark">
                                            <i class="mdi mdi-login-variant me-1"></i>Terakhir Login
                                        </td>
                                        <td class="fw-medium">
                                            <?= !empty($lastLogin) ? date('d M Y, H:i', strtotime($lastLogin)) : '<span class="text-dark">Belum pernah login</span>' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-dark">
                                            <i class="mdi mdi-calendar-plus me-1"></i>Terdaftar
                                        </td>
                                        <td class="fw-medium">
                                            <?= !empty($userCreated) ? date('d M Y, H:i', strtotime($userCreated)) : '-' ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- /row -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Reset Password Siswa -->
<?php if (!empty($canUpdate) && !empty($studentId)): ?>
<div class="modal fade" id="resetPasswordStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-key-variant text-warning me-2"></i>Reset Password Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('counselor/students/reset-password/' . (int) $studentId) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Reset password akun <strong><?= esc($fullName) ?></strong>?</p>
                    <p class="text-warning mb-0"><i class="mdi mdi-information me-1"></i>Password baru dibuat otomatis. Catat & sampaikan ke siswa.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="mdi mdi-key-variant me-1"></i>Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
