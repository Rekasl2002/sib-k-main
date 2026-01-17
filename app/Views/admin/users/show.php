<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/users/show.php
 * 
 * User Detail View
 * Menampilkan detail lengkap pengguna
 * 
 * @package    SIB-K
 * @subpackage Views/Admin/Users
 * @category   User Management
 * @author     Development Team
 * @created    2025-01-05
 */
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Detail Pengguna</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/users') ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active">Detail Pengguna</li>
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

<div class="row">
    <!-- User Profile Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="text-center">
                    <img src="<?= user_avatar($user['profile_photo']) ?>"
                        alt="<?= esc($user['full_name']) ?>"
                        class="avatar-xl rounded-circle mb-3">

                    <h4 class="mb-1"><?= esc($user['full_name']) ?></h4>
                    <p class="text-muted mb-2">@<?= esc($user['username']) ?></p>

                    <div class="mb-3">
                        <span class="badge bg-info font-size-14"><?= esc($user['role_name']) ?></span>
                        <?php if ($user['is_active'] == 1): ?>
                            <span class="badge bg-success font-size-14">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-danger font-size-14">Nonaktif</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <!--<a href="mailto:<?= esc($user['email']) ?>" class="btn btn-sm btn-soft-primary">
                            <i class="mdi mdi-email-outline me-1"></i>Email
                        </a>-->

                        <?php if (!empty($user['phone'])): ?>
                            <!--<a href="tel:<?= esc($user['phone']) ?>" class="btn btn-sm btn-soft-success">
                                <i class="mdi mdi-phone-outline me-1"></i>Telepon
                            </a>-->

                            <?= view('components/wa_button', [
                                'phone' => $user['phone'],
                                'label' => 'WhatsApp',
                                // pilih salah satu style:
                                'class' => 'btn btn-sm btn-success',
                                // opsional prefill text:
                                // 'text'  => 'Halo ' . ($user['full_name'] ?? '') . ', saya menghubungi dari SIB-K.'
                            ]) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">

                <div>
                    <h5 class="font-size-15 mb-3">Informasi Akun</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 40%;">
                                        <i class="mdi mdi-card-account-details me-1"></i>ID Peran
                                    </td>
                                    <td class="fw-medium">#<?= $user['id'] ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-account-key me-1"></i>Username
                                    </td>
                                    <td class="fw-medium"><?= esc($user['username']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-email me-1"></i>Email
                                    </td>
                                    <td class="fw-medium"><?= esc($user['email']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-phone me-1"></i>Telepon
                                    </td>
                                    <td class="fw-medium"><?= !empty($user['phone']) ? esc($user['phone']) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-shield-account me-1"></i>Peran
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= esc($user['role_name']) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-check-circle me-1"></i>Status
                                    </td>
                                    <td>
                                        <?php if ($user['is_active'] == 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr class="my-4">

                <div>
                    <h5 class="font-size-15 mb-3">Aktivitas</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 40%;">
                                        <i class="mdi mdi-calendar-plus me-1"></i>Terdaftar
                                    </td>
                                    <td class="fw-medium">
                                        <?= date('d M Y, H:i', strtotime($user['created_at'])) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-calendar-edit me-1"></i>Terakhir Update
                                    </td>
                                    <td class="fw-medium">
                                        <?= date('d M Y, H:i', strtotime($user['updated_at'])) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-login-variant me-1"></i>Terakhir Login
                                    </td>
                                    <td class="fw-medium">
                                        <?php if ($user['last_login']): ?>
                                            <?= date('d M Y, H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Belum pernah login</span>
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

    <!-- User Details & Actions -->
    <div class="col-lg-8">
        <!-- Action Buttons Card -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-cog me-2"></i>Aksi Manajemen
                    </h5>
                    <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="<?= base_url('admin/users/edit/' . $user['id']) ?>" class="btn btn-primary w-100">
                            <i class="mdi mdi-pencil me-1"></i> Edit Pengguna
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <!--<button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                            <i class="mdi mdi-key-variant me-1"></i> Reset Password
                        </button>-->
                    </div>
                    <div class="col-md-6 mb-2">
                        <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                            <i class="mdi mdi-camera me-1"></i> Upload Foto Profil
                        </button>
                    </div>
                    <?php if ($user['id'] != session()->get('user_id')): ?>
                        <div class="col-md-6 mb-2">
                            <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="mdi mdi-delete me-1"></i> Hapus Pengguna
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student Information (if user is student) -->
        <?php if ($user['is_student']): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="mdi mdi-school me-2"></i>Data Siswa
                    </h5>

                    <div class="mdi alert-info" role="alert">
                        <i class="mdi mdi-information me-2"></i>
                        Peran ini terdaftar sebagai siswa dalam sistem.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted mb-1">NISN:</label>
                                <p class="fw-medium"><?= esc($user['student_data']['nisn']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted mb-1">NIS:</label>
                                <p class="fw-medium"><?= esc($user['student_data']['nis']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted mb-1">Status Siswa:</label>
                                <p class="fw-medium">
                                    <span class="badge bg-<?= $user['student_data']['status'] == 'Aktif' ? 'success' : 'secondary' ?>">
                                        <?= esc($user['student_data']['status']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <a href="<?= base_url('admin/students/profile/' . $user['student_data']['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-eye me-1"></i>Lihat Profil Siswa Lengkap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Additional Information Card -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="mdi mdi-information me-2"></i>Informasi Tambahan
                </h5>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mdi alert-secondary" role="alert">
                            <h6 class="alert-heading">
                                <i class="mdi mdi-file-document me-2"></i>Deskripsi Peran
                            </h6>
                            <p class="mb-0">
                                <?= esc($user['role_description'] ?? 'Tidak ada deskripsi untuk peran ini.') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-xs">
                                            <span class="avatar-title rounded-circle bg-primary bg-soft text-primary font-size-18">
                                                <i class="mdi mdi-account-clock"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-1">Usia Akun</p>
                                        <h5 class="mb-0">
                                            <?php
                                            $created = new DateTime($user['created_at']);
                                            $now = new DateTime();
                                            $diff = $created->diff($now);

                                            if ($diff->y > 0) {
                                                echo $diff->y . ' tahun';
                                            } elseif ($diff->m > 0) {
                                                echo $diff->m . ' bulan';
                                            } else {
                                                echo $diff->d . ' hari';
                                            }
                                            ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-xs">
                                            <span class="avatar-title rounded-circle bg-success bg-soft text-success font-size-18">
                                                <i class="mdi mdi-calendar-check"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-1">Status Login</p>
                                        <h5 class="mb-0">
                                            <?php if ($user['last_login']): ?>
                                                <?php
                                                $lastLogin = new DateTime($user['last_login']);
                                                $diff = $lastLogin->diff($now);

                                                if ($diff->d > 0) {
                                                    echo $diff->d . ' hari lalu';
                                                } elseif ($diff->h > 0) {
                                                    echo $diff->h . ' jam lalu';
                                                } else {
                                                    echo $diff->i . ' menit lalu';
                                                }
                                                ?>
                                            <?php else: ?>
                                                Belum pernah
                                            <?php endif; ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">
                    <i class="mdi mdi-key-variant text-warning me-2"></i>Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/reset-password/' . $user['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin mereset password untuk pengguna <strong><?= esc($user['full_name']) ?></strong>?</p>
                    <p class="text-warning mb-0">
                        <i class="mdi mdi-information me-1"></i>
                        Password baru akan dibuat secara otomatis. Pastikan untuk mencatat dan menyampaikannya kepada user.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="mdi mdi-key-variant me-1"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Photo Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadPhotoModalLabel">
                    <i class="mdi mdi-camera text-info me-2"></i>Upload Foto Profil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/upload-photo/' . $user['id']) ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Pilih Foto</label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/jpeg,image/jpg,image/png" required>
                        <small class="form-text text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                    </div>
                    <div class="text-center">
                        <img id="preview" src="#" alt="Preview" style="max-width: 100%; max-height: 300px; display: none;" class="img-thumbnail">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="mdi mdi-upload me-1"></i>Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="mdi mdi-alert-circle text-danger me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/delete/' . $user['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pengguna <strong><?= esc($user['full_name']) ?></strong>?</p>
                    <p class="text-danger mb-0">
                        <i class="mdi mdi-information me-1"></i>
                        Data yang sudah dihapus tidak dapat dikembalikan!
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete me-1"></i>Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        // Preview uploaded image
        $('#profile_photo').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#preview').attr('src', e.target.result).show();
                }
                reader.readAsDataURL(file);
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?= $this->endSection() ?>