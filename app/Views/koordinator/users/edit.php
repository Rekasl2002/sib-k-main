<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/users/edit.php
 *
 * Edit User Form View (Koordinator)
 * Tambahan:
 * - Penugasan kelas (Guru BK multi, Wali Kelas single)
 * - Anti duplikat: kelas yang sudah dipakai user lain tidak ditampilkan (kecuali milik user yang sedang diedit)
 */

$errors = session()->getFlashdata('errors') ?? [];

$user  = $user  ?? [];
$roles = $roles ?? [];

$classesCounselor = $classes_counselor ?? [];
$classesHomeroom  = $classes_homeroom ?? [];

$assignments = $assignments ?? ['counselor_class_ids' => [], 'homeroom_class_id' => null];

$roleIds = $role_ids ?? ['Guru BK' => 3, 'Wali Kelas' => 4];
$guruBkRoleId    = (int)($roleIds['Guru BK'] ?? 3);
$waliKelasRoleId = (int)($roleIds['Wali Kelas'] ?? 4);

$allowedRoleNames = ['Guru BK','Wali Kelas','Counselor','Homeroom Teacher','HomeroomTeacher'];
$filteredRoles = [];
if (!empty($roles) && is_array($roles)) {
    foreach ($roles as $r) {
        $name = (string)($r['role_name'] ?? '');
        foreach ($allowedRoleNames as $allowed) {
            if ($name !== '' && strcasecmp($name, $allowed) === 0) {
                $filteredRoles[] = $r;
                break;
            }
        }
    }
}

$oldCounselorIds = old('counselor_class_ids', $assignments['counselor_class_ids'] ?? []);
if (!is_array($oldCounselorIds)) $oldCounselorIds = [$oldCounselorIds];
$oldCounselorIds = array_values(array_unique(array_map('intval', $oldCounselorIds)));

$oldHomeroomId = old('homeroom_class_id', $assignments['homeroom_class_id'] ?? '');
$oldHomeroomId = ($oldHomeroomId === '' || $oldHomeroomId === null) ? '' : (int)$oldHomeroomId;

if (!function_exists('koor_user_avatar')) {
    function koor_user_avatar($path = null): string
    {
        if (function_exists('user_avatar')) {
            return user_avatar($path);
        }
        return $path ? base_url($path) : base_url('assets/images/users/default-avatar.svg');
    }
}

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
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Edit Pengguna</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/users') ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active">Edit Pengguna</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="row"><div class="col-12">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div></div>
<?php endif; ?>

<?php if (session()->getFlashdata('warning')): ?>
    <div class="row"><div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-outline me-2"></i>
            <?= session()->getFlashdata('warning') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="row"><div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="row"><div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            <strong>Terdapat kesalahan:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-circle me-2"></i>Profil Pengguna
                </h4>

                <div class="text-center">
                    <img src="<?= esc(koor_user_avatar($user['profile_photo'] ?? null)) ?>"
                         alt="<?= esc($user['full_name'] ?? 'User') ?>"
                         class="avatar-lg rounded-circle mb-3">

                    <h5 class="mb-1"><?= esc($user['full_name'] ?? '-') ?></h5>
                    <p class="text-muted mb-2">@<?= esc($user['username'] ?? '-') ?></p>
                    <span class="badge bg-info font-size-12"><?= esc($user['role_name'] ?? '-') ?></span>
                </div>

                <hr class="my-4">

                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">User ID:</td>
                                <td class="text-end"><strong>#<?= (int)($user['id'] ?? 0) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status:</td>
                                <td class="text-end">
                                    <?php if ((int)($user['is_active'] ?? 0) === 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Terakhir Login:</td>
                                <td class="text-end">
                                    <?php if (!empty($user['last_login'])): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($user['last_login'])) ?></small>
                                    <?php else: ?>
                                        <small>-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Terdaftar:</td>
                                <td class="text-end">
                                    <?php if (!empty($user['created_at'])): ?>
                                        <small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                    <?php else: ?>
                                        <small>-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <div class="d-grid gap-2">
                    <a href="<?= base_url('koordinator/users/show/' . (int)($user['id'] ?? 0)) ?>" class="btn btn-info">
                        <i class="mdi mdi-eye me-1"></i> Lihat Detail
                    </a>

                    <!--<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                        <i class="mdi mdi-key-variant me-1"></i> Reset Password
                    </button>-->

                    <?php if ((int)($user['id'] ?? 0) !== (int)session()->get('user_id')): ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="mdi mdi-delete me-1"></i> Hapus User
                        </button>
                    <?php endif; ?>
                </div>

                <div class="alert alert-info mt-3 mb-0" role="alert">
                    <i class="mdi mdi-information-outline me-2"></i>
                    Koordinator hanya boleh mengelola akun <strong>Guru BK</strong> dan <strong>Wali Kelas</strong>.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-edit me-2"></i>Edit Informasi Pengguna
                </h4>

                <form action="<?= base_url('koordinator/users/update/' . (int)($user['id'] ?? 0)) ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>">
                    <input type="hidden" name="is_active" value="0">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role_id" class="form-label">
                                    Role <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>"
                                        id="role_id"
                                        name="role_id"
                                        required>
                                    <option value="">Pilih Role</option>
                                    <?php foreach ($filteredRoles as $role): ?>
                                        <option value="<?= (int)($role['id'] ?? 0) ?>"
                                            <?= (old('role_id', $user['role_id'] ?? '') == ($role['id'] ?? 0)) ? 'selected' : '' ?>>
                                            <?= esc($role['role_name'] ?? '-') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['role_id'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?= esc($errors['role_id']) ?>
                                    </div>
                                <?php endif; ?>
                                <small class="form-text text-muted">
                                    Role dibatasi sesuai kewenangan Koordinator.
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= esc(old('full_name', $user['full_name'] ?? '')) ?>"
                                       placeholder="Masukkan nama lengkap"
                                       required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['full_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Penugasan Kelas -->
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Penugasan Kelas</label>

                                <div id="assign_guru_bk" style="display:none;">
                                    <small class="text-muted d-block mb-2">Pilih satu atau beberapa kelas binaan.</small>
                                    <select class="form-select" name="counselor_class_ids[]" id="counselor_class_ids" multiple>
                                        <?php if (empty($classesCounselor)): ?>
                                            <option value="" disabled>(Tidak ada kelas tersedia untuk ditugaskan)</option>
                                        <?php else: ?>
                                            <?php foreach ($classesCounselor as $c): ?>
                                                <option value="<?= (int)($c['id'] ?? 0) ?>"
                                                    <?= in_array((int)($c['id'] ?? 0), $oldCounselorIds, true) ? 'selected' : '' ?>>
                                                    <?= esc(class_label($c)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <small class="form-text text-muted">Tips: gunakan Ctrl/Command untuk memilih banyak kelas, serta menghapus penugasan.</small>
                                </div>

                                <div id="assign_wali_kelas" style="display:none;">
                                    <small class="text-muted d-block mb-2">Pilih 1 kelas perwalian.</small>
                                    <select class="form-select" name="homeroom_class_id" id="homeroom_class_id">
                                        <option value="">(Tidak ditugaskan)</option>
                                        <?php if (empty($classesHomeroom)): ?>
                                            <option value="" disabled>(Tidak ada kelas tersedia untuk ditugaskan)</option>
                                        <?php else: ?>
                                            <?php foreach ($classesHomeroom as $c): ?>
                                                <option value="<?= (int)($c['id'] ?? 0) ?>"
                                                    <?= ((int)($c['id'] ?? 0) === (int)$oldHomeroomId) ? 'selected' : '' ?>>
                                                    <?= esc(class_label($c)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    Username <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                       id="username"
                                       name="username"
                                       value="<?= esc(old('username', $user['username'] ?? '')) ?>"
                                       placeholder="Masukkan username"
                                       required>
                                <small class="form-text text-muted">
                                    Username hanya boleh berisi huruf, angka, garis bawah (_) atau tanda minus (-), minimal 3 karakter.
                                </small>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['username']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       id="email"
                                       name="email"
                                       value="<?= esc(old('email', $user['email'] ?? '')) ?>"
                                       placeholder="contoh@email.com"
                                       required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="text"
                                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                       id="phone"
                                       name="phone"
                                       value="<?= esc(old('phone', (string)($user['phone'] ?? ''))) ?>"
                                       placeholder="08xxxxxxxxxx"
                                       minlength="10"
                                       maxlength="15"
                                       pattern="08[0-9]{8,13}"
                                       inputmode="numeric">
                                <small class="form-text text-muted">
                                    Opsional. Jika diisi, harus diawali <code>08</code> dan terdiri dari 10–15 digit angka.
                                </small>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch form-switch-lg mt-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           <?= (old('is_active', (int)($user['is_active'] ?? 0)) == 1) ? 'checked' : '' ?>
                                           <?= ((int)($user['id'] ?? 0) === (int)session()->get('user_id')) ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        <?php if ((int)($user['id'] ?? 0) === (int)session()->get('user_id')): ?>
                                            Anda tidak dapat menonaktifkan akun sendiri
                                        <?php else: ?>
                                            User aktif
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning" role="alert">
                        <i class="mdi mdi-alert-outline me-2"></i>
                        <strong>Perhatian:</strong> Untuk mengubah password, gunakan tombol "Reset Password" di samping kiri.
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('koordinator/users') ?>" class="btn btn-secondary">
                                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-light me-2">
                                        <i class="mdi mdi-refresh me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-content-save me-1"></i> Simpan Perubahan
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

<!-- Delete Confirmation Modal -->
<?php if ((int)($user['id'] ?? 0) !== (int)session()->get('user_id')): ?>
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
(function() {
    'use strict';

    const username = document.getElementById('username');
    if (username) {
        username.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '');
        });
    }

    const phone = document.getElementById('phone');
    if (phone) {
        phone.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    const roleSelect = document.getElementById('role_id');
    const assignBK   = document.getElementById('assign_guru_bk');
    const assignWali = document.getElementById('assign_wali_kelas');

    const bkSelect   = document.getElementById('counselor_class_ids');
    const waliSelect = document.getElementById('homeroom_class_id');

    const GURU_BK_ID = <?= (int)$guruBkRoleId ?>;
    const WALI_ID    = <?= (int)$waliKelasRoleId ?>;

    function clearMultiSelect(sel) {
        if (!sel) return;
        Array.from(sel.options).forEach(o => o.selected = false);
    }

    function refreshAssignmentVisibility() {
        const v = parseInt((roleSelect && roleSelect.value) ? roleSelect.value : '0', 10);

        if (assignBK)   assignBK.style.display   = (v === GURU_BK_ID) ? '' : 'none';
        if (assignWali) assignWali.style.display = (v === WALI_ID) ? '' : 'none';

        // cegah nilai "nyangkut" saat ganti role
        if (v === GURU_BK_ID) {
            if (waliSelect) waliSelect.value = '';
        } else if (v === WALI_ID) {
            clearMultiSelect(bkSelect);
        } else {
            clearMultiSelect(bkSelect);
            if (waliSelect) waliSelect.value = '';
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', refreshAssignmentVisibility);
        refreshAssignmentVisibility();
    }

    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(el) {
            el.classList.remove('show');
        });
    }, 5000);

    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
<?= $this->endSection() ?>
