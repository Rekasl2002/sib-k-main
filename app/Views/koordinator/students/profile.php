<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/students/profile.php
 *
 * Student Profile View (Koordinator BK)
 * Menampilkan profil lengkap siswa dengan semua informasi
 *
 * @package    SIB-K
 * @subpackage Views/Koordinator/Students
 * @category   Student Management
 * @author     Development TeamF
 * @created    2025-12-16
 */

helper(['url']);

// Fallback aman untuk judul/breadcrumb jika controller mengirim variabelnya
$pageTitle  = $page_title ?? 'Profil Siswa';
$breadcrumb = $breadcrumb ?? [
    ['title' => 'Koordinator', 'link' => base_url('koordinator/dashboard')],
    ['title' => 'Data Siswa', 'link' => base_url('koordinator/students')],
    ['title' => 'Profil Siswa', 'link' => null],
];

// Status color map
$statusColors = [
    'Aktif'  => 'success',
    'Alumni' => 'info',
    'Pindah' => 'warning',
    'Keluar' => 'danger',
];

$statusVal   = $student['status'] ?? '-';
$statusColor = $statusColors[$statusVal] ?? 'secondary';


// Hitung umur (aman)
$ageText = null;
if (!empty($student['birth_date'])) {
    try {
        $birthDate = new DateTime($student['birth_date']);
        $today     = new DateTime();
        $age       = $today->diff($birthDate)->y;
        $ageText   = $age . ' tahun';
    } catch (\Throwable $e) {
        $ageText = null;
    }
}
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><?= esc($pageTitle) ?></h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if (!empty($item['link'])): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= $item['link'] ?>"><?= esc($item['title']) ?></a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= esc($item['title']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
                <div class="row">
                    <div class="col-lg-4">
                        <div class="text-center">
                            <img src="<?= user_avatar($student['profile_photo'] ?? null) ?>"
                                 alt="<?= esc($student['full_name'] ?? '-') ?>"
                                 class="avatar-xl rounded-circle mb-3">

                            <h4 class="mb-1"><?= esc($student['full_name'] ?? '-') ?></h4>
                            <p class="text-dark mb-2">@<?= esc($student['username'] ?? '-') ?></p>

                            <div class="mb-3">
                                <?php if (!empty($student['class_name'])): ?>
                                    <span class="badge bg-primary font-size-14">
                                        <?= esc($student['grade_level'] ?? '-') ?> - <?= esc($student['class_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary font-size-14">Belum Ada Kelas</span>
                                <?php endif; ?>

                                <span class="badge bg-<?= $statusColor ?> font-size-14 ms-1">
                                    <?= esc($statusVal) ?>
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
                            <a href="<?= base_url('koordinator/students/edit/' . ($student['id'] ?? 0)) ?>" class="btn btn-primary">
                                <i class="mdi mdi-pencil me-1"></i>Edit Data
                            </a>
                            <?php if (! empty($student['user_id'])): ?>
                                <a href="<?= base_url('koordinator/users/show/' . (int) $student['user_id']) ?>" class="btn btn-info">
                                    <i class="mdi mdi-account-cog me-1"></i>Kelola Akun &amp; Sandi
                                </a>
                            <?php endif; ?>
                            <?php if (! empty($student['parent_id'])): ?>
                                <a href="<?= base_url('koordinator/users/show/' . (int) $student['parent_id']) ?>" class="btn btn-outline-info">
                                    <i class="mdi mdi-account-supervisor me-1"></i>Akun Orang Tua
                                </a>
                            <?php endif; ?>
                            <a href="<?= base_url('koordinator/students') ?>" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>Kembali
                            </a>
                        </div>
                    </div>
                </div>

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
                                <td class="fw-medium"><?= esc($student['full_name'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-gender-<?= ($student['gender'] ?? '') === 'L' ? 'male' : 'female' ?> me-1"></i>Jenis Kelamin
                                </td>
                                <td class="fw-medium">
                                    <?= ($student['gender'] ?? '') === 'L' ? 'Laki-laki' : (($student['gender'] ?? '') === 'P' ? 'Perempuan' : '-') ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-map-marker me-1"></i>Tempat Lahir
                                </td>
                                <td class="fw-medium"><?= !empty($student['birth_place']) ? esc($student['birth_place']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-calendar me-1"></i>Tanggal Lahir
                                </td>
                                <td class="fw-medium">
                                    <?php if (!empty($student['birth_date'])): ?>
                                        <?= date('d F Y', strtotime($student['birth_date'])) ?>
                                        <?php if ($ageText): ?>
                                            <span class="text-dark">(<?= esc($ageText) ?>)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-book-cross me-1"></i>Agama
                                </td>
                                <td class="fw-medium"><?= !empty($student['religion']) ? esc($student['religion']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark">
                                    <i class="mdi mdi-home me-1"></i>Alamat
                                </td>
                                <td class="fw-medium"><?= !empty($student['address']) ? esc($student['address']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-account-heart me-1"></i>Kebutuhan Khusus</td>
                                <td class="fw-medium"><?= !empty($student['special_needs']) ? esc($student['special_needs']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-wheelchair-accessibility me-1"></i>Disabilitas</td>
                                <td class="fw-medium"><?= !empty($student['disability']) ? esc($student['disability']) : '-' ?></td>
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
                                <td class="fw-medium"><?= esc($student['nisn'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-card-account-details me-1"></i>NIK</td>
                                <td class="fw-medium"><?= !empty($student['nik']) ? esc($student['nik']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-google-classroom me-1"></i>Kelas</td>
                                <td class="fw-medium"><?= !empty($student['class_name']) ? esc(($student['grade_level'] ?? '') . ' - ' . $student['class_name']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-stairs me-1"></i>Tingkat</td>
                                <td class="fw-medium"><?= !empty($student['grade_level']) ? esc($student['grade_level']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-calendar-import me-1"></i>Tanggal Masuk</td>
                                <td class="fw-medium"><?= !empty($student['admission_date']) ? date('d F Y', strtotime($student['admission_date'])) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-card-bulleted me-1"></i>Nomor KIP/PIP</td>
                                <td class="fw-medium"><?= !empty($student['kip_pip_number']) ? esc($student['kip_pip_number']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-dark"><i class="mdi mdi-flag me-1"></i>Status Siswa</td>
                                <td class="fw-medium"><span class="badge bg-<?= $statusColor ?>"><?= esc($statusVal) ?></span></td>
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
<?php if (!empty($student['parent_id'])): ?>
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
                                            <td class="fw-medium"><?= esc($student['parent_name'] ?? '-') ?></td>
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
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Account Information -->
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
                                        <td class="fw-medium">@<?= esc($student['username'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-dark">
                                            <i class="mdi mdi-email me-1"></i>Email
                                        </td>
                                        <td class="fw-medium"><?= esc($student['email'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-dark">
                                            <i class="mdi mdi-phone me-1"></i>Telepon
                                        </td>
                                        <td class="fw-medium"><?= !empty($student['phone']) ? esc($student['phone']) : '-' ?></td>
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
                                            <?php if ((int)($student['is_active'] ?? 0) === 1): ?>
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
                                            <?php if (!empty($student['last_login'])): ?>
                                                <?= date('d M Y, H:i', strtotime($student['last_login'])) ?>
                                            <?php else: ?>
                                                <span class="text-dark">Belum pernah login</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-dark">
                                            <i class="mdi mdi-calendar-plus me-1"></i>Terdaftar
                                        </td>
                                        <td class="fw-medium">
                                            <?= !empty($student['user_created_at']) ? date('d M Y, H:i', strtotime($student['user_created_at'])) : '-' ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
