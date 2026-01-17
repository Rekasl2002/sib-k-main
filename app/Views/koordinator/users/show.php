<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/users/show.php
 *
 * User Detail View (Koordinator)
 * UI & flow disamakan dengan admin/users/show.php
 * Peraturan Koordinator: hanya mengelola akun Guru BK & Wali Kelas
 *
 * Tambahan:
 * - Tampilkan penugasan kelas:
 *   - Guru BK: classes.counselor_id = user_id (bisa banyak)
 *   - Wali Kelas: classes.homeroom_teacher_id = user_id (1 kelas)
 */

// ✅ FIX PHP0422: jangan pakai "$user = $user ?? [];"
$user ??= [];

// Cache flashdata (biar tidak “habis” kalau kepanggil berkali-kali)
$flashSuccess = session()->getFlashdata('success');
$flashError   = session()->getFlashdata('error');

// Avatar fallback aman
if (!function_exists('koor_user_avatar')) {
    function koor_user_avatar($path = null): string
    {
        if (function_exists('user_avatar')) {
            return user_avatar($path);
        }
        // fallback default avatar
        return $path ? base_url($path) : base_url('assets/images/users/default-avatar.svg');
    }
}

// Helper label kelas (opsional)
if (!function_exists('class_label')) {
    function class_label(array $c): string
    {
        $name  = (string)($c['class_name'] ?? '-');
        $grade = (string)($c['grade_level'] ?? '');
        $major = (string)($c['major'] ?? '');

        $parts = [];
        if ($grade !== '') $parts[] = 'Kelas ' . $grade;
        if ($major !== '') $parts[] = $major;

        $suffix = !empty($parts) ? ' • ' . implode(' • ', $parts) : '';
        return $name . $suffix;
    }
}

$isActive  = ((int)($user['is_active'] ?? 0) === 1);
$isSelf    = ((int)($user['id'] ?? 0) === (int)session()->get('user_id'));
$isStudent = !empty($user['is_student']); // seharusnya tidak muncul di Koordinator, tapi dibuat tahan banting

$now = new DateTimeImmutable('now');

// ================================
// Penugasan Kelas (NEW)
// ================================
// Bisa dikirim oleh controller sebagai:
// $assigned_classes = ['counselor' => [...], 'homeroom' => [...|null]]
$assignedClasses = $assigned_classes ?? null;

if ($assignedClasses === null) {
    // Fallback query di view (opsional, tapi bikin langsung jalan tanpa ubah controller)
    $assignedClasses = ['counselor' => [], 'homeroom' => null];

    if (function_exists('model')) {
        try {
            $classModel = model(\App\Models\ClassModel::class);

            // Guru BK: banyak kelas
            $assignedClasses['counselor'] = $classModel->asArray()
                ->select('id, class_name, grade_level, major, is_active')
                ->where('deleted_at', null)
                ->where('counselor_id', (int)($user['id'] ?? 0))
                ->orderBy('grade_level', 'ASC')
                ->orderBy('class_name', 'ASC')
                ->findAll();

            // Wali Kelas: 1 kelas
            $assignedClasses['homeroom'] = $classModel->asArray()
                ->select('id, class_name, grade_level, major, is_active')
                ->where('deleted_at', null)
                ->where('homeroom_teacher_id', (int)($user['id'] ?? 0))
                ->orderBy('grade_level', 'ASC')
                ->orderBy('class_name', 'ASC')
                ->first();
        } catch (\Throwable $e) {
            // kalau model tidak tersedia/bermasalah, biarkan kosong
        }
    }
}

$counselorClasses = $assignedClasses['counselor'] ?? [];
$homeroomClass    = $assignedClasses['homeroom'] ?? null;

?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Detail Pengguna</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/users') ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active">Detail Pengguna</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Info aturan -->
<div class="row">
    <div class="col-12">
        <div class="alert alert-info" role="alert">
            <i class="mdi mdi-information-outline me-2"></i>
            Koordinator hanya dapat mengelola akun <strong>Guru BK</strong> dan <strong>Wali Kelas</strong>.
            Jika ada akun di luar cakupan muncul di halaman ini, seharusnya diblok di controller.
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if ($flashSuccess): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                <?= esc($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <?= esc($flashError) ?>
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
                    <img src="<?= esc(koor_user_avatar($user['profile_photo'] ?? null)) ?>"
                        alt="<?= esc($user['full_name'] ?? 'User') ?>"
                        class="avatar-xl rounded-circle mb-3">

                    <h4 class="mb-1"><?= esc($user['full_name'] ?? '-') ?></h4>
                    <p class="text-muted mb-2">@<?= esc($user['username'] ?? '-') ?></p>

                    <div class="mb-3">
                        <span class="badge bg-info font-size-14"><?= esc($user['role_name'] ?? '-') ?></span>
                        <?php if ($isActive): ?>
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
                                        <i class="mdi mdi-card-account-details me-1"></i>User ID
                                    </td>
                                    <td class="fw-medium">#<?= (int)($user['id'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-account-key me-1"></i>Username
                                    </td>
                                    <td class="fw-medium"><?= esc($user['username'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-email me-1"></i>Email
                                    </td>
                                    <td class="fw-medium"><?= esc($user['email'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-phone me-1"></i>Telepon
                                    </td>
                                    <td class="fw-medium"><?= !empty($user['phone']) ? esc($user['phone']) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-shield-account me-1"></i>Role
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= esc($user['role_name'] ?? '-') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-check-circle me-1"></i>Status
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
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
                                        <?php if (!empty($user['created_at'])): ?>
                                            <?= date('d M Y, H:i', strtotime($user['created_at'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-calendar-edit me-1"></i>Terakhir Update
                                    </td>
                                    <td class="fw-medium">
                                        <?php if (!empty($user['updated_at'])): ?>
                                            <?= date('d M Y, H:i', strtotime($user['updated_at'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        <i class="mdi mdi-login-variant me-1"></i>Terakhir Login
                                    </td>
                                    <td class="fw-medium">
                                        <?php if (!empty($user['last_login'])): ?>
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
                    <a href="<?= base_url('koordinator/users') ?>" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="<?= base_url('koordinator/users/edit/' . (int)($user['id'] ?? 0)) ?>" class="btn btn-primary w-100">
                            <i class="mdi mdi-pencil me-1"></i> Edit Pengguna
                        </a>
                    </div>
                    <!--<div class="col-md-6 mb-2">
                        <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                            <i class="mdi mdi-key-variant me-1"></i> Reset Password
                        </button>
                    </div>
                    <div class="col-md-6 mb-2">
                        <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                            <i class="mdi mdi-camera me-1"></i> Upload Foto Profil
                        </button>
                    </div>-->
                    <?php if (!$isSelf): ?>
                        <div class="col-md-6 mb-2">
                            <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="mdi mdi-delete me-1"></i> Hapus Pengguna
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ✅ Penugasan Kelas (NEW) -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="mdi mdi-google-classroom me-2"></i>Penugasan Kelas
                </h5>

                <?php if (empty($counselorClasses) && empty($homeroomClass)): ?>
                    <div class="alert alert-light border mb-0">
                        <i class="mdi mdi-information-outline me-1"></i>
                        Belum ada penugasan kelas untuk user ini.
                    </div>
                <?php endif; ?>

                <?php if (!empty($homeroomClass)): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-2">
                                <span class="badge bg-primary">Wali Kelas</span>
                                <span class="ms-2">Kelas Perwalian</span>
                            </h6>
                            <?php if ((int)($homeroomClass['is_active'] ?? 1) === 1): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </div>

                        <div class="p-3 border rounded">
                            <div class="fw-semibold"><?= esc(class_label($homeroomClass)) ?></div>
                            <small class="text-muted">ID Kelas: #<?= (int)($homeroomClass['id'] ?? 0) ?></small>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($counselorClasses)): ?>
                    <div>
                        <h6 class="mb-2">
                            <span class="badge bg-info">Guru BK</span>
                            <span class="ms-2">Kelas Binaan (<?= count($counselorClasses) ?>)</span>
                        </h6>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:80px;">ID</th>
                                        <th>Kelas</th>
                                        <th style="width:120px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($counselorClasses as $c): ?>
                                    <tr>
                                        <td>#<?= (int)($c['id'] ?? 0) ?></td>
                                        <td><?= esc(class_label($c)) ?></td>
                                        <td>
                                            <?php if ((int)($c['is_active'] ?? 1) === 1): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Information (seharusnya tidak ada untuk Koordinator Users) -->
        <?php if ($isStudent): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="mdi mdi-school me-2"></i>Data Siswa
                    </h5>

                    <div class="alert alert-warning" role="alert">
                        <i class="mdi mdi-alert-outline me-2"></i>
                        User ini terdeteksi sebagai <strong>Siswa</strong>. Sesuai aturan, modul Kelola User Koordinator seharusnya tidak menampilkan akun siswa.
                    </div>

                    <?php $sd = $user['student_data'] ?? []; ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted mb-1">NISN:</label>
                                <p class="fw-medium"><?= esc($sd['nisn'] ?? '-') ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted mb-1">NIS:</label>
                                <p class="fw-medium"><?= esc($sd['nis'] ?? '-') ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted mb-1">Status Siswa:</label>
                                <p class="fw-medium">
                                    <?php $st = (string)($sd['status'] ?? '-'); ?>
                                    <span class="badge bg-<?= ($st === 'Aktif') ? 'success' : 'secondary' ?>">
                                        <?= esc($st) ?>
                                    </span>
                                </p>
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
                        <div class="alert alert-secondary" role="alert">
                            <h6 class="alert-heading">
                                <i class="mdi mdi-file-document me-2"></i>Deskripsi Role
                            </h6>
                            <p class="mb-0">
                                <?= esc($user['role_description'] ?? 'Tidak ada deskripsi untuk role ini.') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Account Age -->
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
                                            if (!empty($user['created_at'])) {
                                                $created = new DateTimeImmutable($user['created_at']);
                                                $diffAge = $created->diff($now); // ✅ jangan pakai $diff 2x (lebih jelas)

                                                if ($diffAge->y > 0) {
                                                    echo $diffAge->y . ' tahun';
                                                } elseif ($diffAge->m > 0) {
                                                    echo $diffAge->m . ' bulan';
                                                } else {
                                                    echo $diffAge->d . ' hari';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Login Status -->
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
                                            <?php if (!empty($user['last_login'])): ?>
                                                <?php
                                                $lastLogin = new DateTimeImmutable($user['last_login']);
                                                $diffLogin = $lastLogin->diff($now);

                                                if ($diffLogin->d > 0) {
                                                    echo $diffLogin->d . ' hari lalu';
                                                } elseif ($diffLogin->h > 0) {
                                                    echo $diffLogin->h . ' jam lalu';
                                                } else {
                                                    echo $diffLogin->i . ' menit lalu';
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
                </div><!-- /row -->
            </div>
        </div>

    </div><!-- /col -->
</div><!-- /row -->

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
            <form action="<?= base_url('koordinator/users/reset-password/' . (int)($user['id'] ?? 0)) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin mereset password untuk pengguna <strong><?= esc($user['full_name'] ?? '-') ?></strong>?</p>
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
            <form action="<?= base_url('koordinator/users/upload-photo/' . (int)($user['id'] ?? 0)) ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Pilih Foto</label>
                        <input type="file"
                               class="form-control"
                               id="profile_photo"
                               name="profile_photo"
                               accept="image/jpeg,image/jpg,image/png"
                               required>
                        <small class="form-text text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                    </div>
                    <div class="text-center">
                        <img id="preview" src="#" alt="Preview"
                             style="max-width: 100%; max-height: 300px; display: none;"
                             class="img-thumbnail">
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
<?php if (!$isSelf): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="mdi mdi-alert-circle text-danger me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('koordinator/users/delete/' . (int)($user['id'] ?? 0)) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pengguna <strong><?= esc($user['full_name'] ?? '-') ?></strong>?</p>
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
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Preview uploaded image (tanpa jQuery)
    const input = document.getElementById('profile_photo');
    const preview = document.getElementById('preview');

    if (input && preview) {
        input.addEventListener('change', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.classList.remove('show');
        });
    }, 5000);
});
</script>
<?= $this->endSection() ?>
