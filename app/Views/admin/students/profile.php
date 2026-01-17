<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/students/profile.php
 * 
 * Student Profile View
 * Menampilkan profil lengkap siswa dengan semua informasi
 * 
 * @package    SIB-K
 * @subpackage Views/Admin/Students
 * @category   Student Management
 * @author     Development Team
 * @created    2025-01-05
 */
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Profil Siswa</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/students') ?>">Siswa</a></li>
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
                <?= session()->getFlashdata('success') ?>
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
                <?= session()->getFlashdata('error') ?>
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
                            <img src="<?= user_avatar($student['profile_photo']) ?>"
                                alt="<?= esc($student['full_name']) ?>"
                                class="avatar-xl rounded-circle mb-3">

                            <h4 class="mb-1"><?= esc($student['full_name']) ?></h4>
                            <p class="text-muted mb-2">@<?= esc($student['username']) ?></p>

                            <div class="mb-3">
                                <?php if ($student['class_name']): ?>
                                    <span class="badge bg-primary font-size-14">
                                        <?= esc($student['grade_level']) ?> - <?= esc($student['class_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary font-size-14">Belum Ada Kelas</span>
                                <?php endif; ?>

                                <?php
                                $statusColors = [
                                    'Aktif' => 'success',
                                    'Alumni' => 'info',
                                    'Pindah' => 'warning',
                                    'Keluar' => 'danger'
                                ];
                                $statusColor = $statusColors[$student['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $statusColor ?> font-size-14 ms-1">
                                    <?= esc($student['status']) ?>
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

                    <div class="col-lg-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border mb-3">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary bg-soft text-primary font-size-18">
                                                        <i class="mdi mdi-card-account-details"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="text-muted mb-1">NISN</p>
                                                <h5 class="mb-0"><code><?= esc($student['nisn']) ?></code></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border mb-3">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-success bg-soft text-success font-size-18">
                                                        <i class="mdi mdi-card-text"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="text-muted mb-1">NIS</p>
                                                <h5 class="mb-0"><code><?= esc($student['nis']) ?></code></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border mb-3">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-<?= $student['total_violation_points'] > 0 ? 'danger' : 'success' ?> bg-soft text-<?= $student['total_violation_points'] > 0 ? 'danger' : 'success' ?> font-size-18">
                                                        <i class="mdi mdi-alert-circle"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="text-muted mb-1">Poin Pelanggaran</p>
                                                <h5 class="mb-0"><?= $student['total_violation_points'] ?> Poin</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border mb-3">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-info bg-soft text-info font-size-18">
                                                        <i class="mdi mdi-calendar-account"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="text-muted mb-1">Tanggal Masuk</p>
                                                <h5 class="mb-0">
                                                    <?php if ($student['admission_date']): ?>
                                                        <?= date('d M Y', strtotime($student['admission_date'])) ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="<?= base_url('admin/students/edit/' . $student['id']) ?>" class="btn btn-primary">
                                <i class="mdi mdi-pencil me-1"></i>Edit Data Siswa
                            </a>
                            <a href="<?= base_url('admin/users/edit/' . $student['user_id']) ?>" class="btn btn-info">
                                <i class="mdi mdi-account-edit me-1"></i>Edit Akun Pengguna
                            </a>
                            <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary">
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
                                <td class="text-muted" style="width: 40%;">
                                    <i class="mdi mdi-account me-1"></i>Nama Lengkap
                                </td>
                                <td class="fw-medium"><?= esc($student['full_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-gender-<?= $student['gender'] == 'L' ? 'male' : 'female' ?> me-1"></i>Jenis Kelamin
                                </td>
                                <td class="fw-medium">
                                    <?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-map-marker me-1"></i>Tempat Lahir
                                </td>
                                <td class="fw-medium"><?= $student['birth_place'] ? esc($student['birth_place']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-calendar me-1"></i>Tanggal Lahir
                                </td>
                                <td class="fw-medium">
                                    <?php if ($student['birth_date']): ?>
                                        <?= date('d F Y', strtotime($student['birth_date'])) ?>
                                        <?php
                                        $birthDate = new DateTime($student['birth_date']);
                                        $today = new DateTime();
                                        $age = $today->diff($birthDate)->y;
                                        ?>
                                        <span class="text-muted">(<?= $age ?> tahun)</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-book-cross me-1"></i>Agama
                                </td>
                                <td class="fw-medium"><?= $student['religion'] ? esc($student['religion']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-home me-1"></i>Alamat
                                </td>
                                <td class="fw-medium"><?= $student['address'] ? esc($student['address']) : '-' ?></td>
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
                                <td class="text-muted" style="width: 40%;">
                                    <i class="mdi mdi-google-classroom me-1"></i>Kelas
                                </td>
                                <td class="fw-medium">
                                    <?php if ($student['class_name']): ?>
                                        <span class="badge bg-primary">
                                            <?= esc($student['grade_level']) ?> - <?= esc($student['class_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Belum ada kelas</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-card-account-details me-1"></i>NISN
                                </td>
                                <td class="fw-medium"><code><?= esc($student['nisn']) ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-card-text me-1"></i>NIS
                                </td>
                                <td class="fw-medium"><code><?= esc($student['nis']) ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-calendar-check me-1"></i>Status
                                </td>
                                <td class="fw-medium">
                                    <span class="badge bg-<?= $statusColor ?>">
                                        <?= esc($student['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-calendar-import me-1"></i>Tanggal Masuk
                                </td>
                                <td class="fw-medium">
                                    <?= $student['admission_date'] ? date('d F Y', strtotime($student['admission_date'])) : '-' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="mdi mdi-alert-circle me-1"></i>Total Poin Pelanggaran
                                </td>
                                <td class="fw-medium">
                                    <span class="badge bg-<?= $student['total_violation_points'] > 0 ? 'danger' : 'success' ?> font-size-14">
                                        <?= $student['total_violation_points'] ?> Poin
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Parent/Guardian Information -->
<?php if ($student['parent_id']): ?>
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
                                            <td class="text-muted" style="width: 40%;">
                                                <i class="mdi mdi-account me-1"></i>Nama
                                            </td>
                                            <td class="fw-medium"><?= esc($student['parent_name']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">
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
                            <div class="d-flex align-items-center h-100">
                                <a href="<?= base_url('admin/users/show/' . $student['parent_id']) ?>" class="btn btn-sm btn-info">
                                    <i class="mdi mdi-eye me-1"></i>Lihat Detail Orang Tua
                                </a>
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
                                        <td class="text-muted" style="width: 40%;">
                                            <i class="mdi mdi-account-key me-1"></i>Username
                                        </td>
                                        <td class="fw-medium">@<?= esc($student['username']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="mdi mdi-email me-1"></i>Email
                                        </td>
                                        <td class="fw-medium"><?= esc($student['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="mdi mdi-phone me-1"></i>Telepon
                                        </td>
                                        <td class="fw-medium"><?= $student['phone'] ? esc($student['phone']) : '-' ?></td>
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
                                        <td class="text-muted" style="width: 40%;">
                                            <i class="mdi mdi-check-circle me-1"></i>Status Akun
                                        </td>
                                        <td class="fw-medium">
                                            <?php if ($student['is_active'] == 1): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="mdi mdi-login-variant me-1"></i>Terakhir Login
                                        </td>
                                        <td class="fw-medium">
                                            <?php if ($student['last_login']): ?>
                                                <?= date('d M Y, H:i', strtotime($student['last_login'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Belum pernah login</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="mdi mdi-calendar-plus me-1"></i>Terdaftar
                                        </td>
                                        <td class="fw-medium">
                                            <?= date('d M Y, H:i', strtotime($student['user_created_at'])) ?>
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

<!-- Placeholder for Future Modules 
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
            <h5 class="card-title mb-3">
                    <i class="mdi mdi-alert-circle text-danger me-2"></i>Riwayat Pelanggaran
                </h5>
                <div class="text-center py-4">
                    <i class="mdi mdi-alert-octagon text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-2">
                        Modul pelanggaran akan tersedia di Fase 3
                    </p>
                </div>
            </div>
        </div>
    </div>
    

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="mdi mdi-account-voice text-primary me-2"></i>Riwayat Konseling
                </h5>
                <div class="text-center py-4">
                    <i class="mdi mdi-account-group text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-2">
                        Modul konseling akan tersedia di Fase 3
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="mdi mdi-clipboard-check text-success me-2"></i>Hasil Asesmen
                </h5>
                <div class="text-center py-4">
                    <i class="mdi mdi-clipboard-text text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-2">
                        Modul asesmen akan tersedia di Fase 3
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
-->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?= $this->endSection() ?>