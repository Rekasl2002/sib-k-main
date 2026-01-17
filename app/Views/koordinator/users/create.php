<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/koordinator/users/create.php
 *
 * Create User Form View (Koordinator)
 * Tambahan:
 * - Penugasan kelas (Guru BK multi, Wali Kelas single)
 * - is_active OFF tetap kirim 0 (hidden)
 * - Anti duplikat: kelas yang sudah dipakai tidak ditampilkan (list dipasok controller)
 */

$errors = session()->getFlashdata('errors') ?? [];
$roles  = $roles ?? [];

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

$oldActive = old('is_active', '1');
if (is_array($oldActive)) $oldActive = end($oldActive);
$oldActive = ((string)$oldActive === '1') ? '1' : '0';

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
            <h4 class="mb-0">Tambah Pengguna</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/users') ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active">Tambah Pengguna</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info" role="alert">
            <i class="mdi mdi-information-outline me-2"></i>
            Koordinator hanya dapat membuat akun <strong>Guru BK</strong> dan <strong>Wali Kelas</strong>.
        </div>
    </div>
</div>

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

<?php if (!empty($errors) && is_array($errors)): ?>
    <div class="row">
        <div class="col-12">
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
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-plus me-2"></i>Informasi Pengguna
                </h4>

                <form action="<?= base_url('koordinator/users/store') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role_id" class="form-label">
                                    Role <span class="text-danger">*</span>
                                </label>
                                <select
                                    class="form-select<?= isset($errors['role_id']) ? ' is-invalid' : '' ?>"
                                    id="role_id"
                                    name="role_id"
                                    required>
                                    <option value="">Pilih Role</option>
                                    <?php foreach ($filteredRoles as $role): ?>
                                        <option value="<?= (int)($role['id'] ?? 0) ?>" <?= old('role_id') == ($role['id'] ?? null) ? 'selected' : '' ?>>
                                            <?= esc($role['role_name'] ?? '-') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Hanya role Guru BK dan Wali Kelas yang tersedia untuk Koordinator.
                                </small>
                                <?php if (isset($errors['role_id'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?= esc($errors['role_id']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control<?= isset($errors['full_name']) ? ' is-invalid' : '' ?>"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= old('full_name') ?>"
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

                                <div class="mb alert-light border mb-3">
                                    <small class="text-muted">
                                        Pilihan Penugasan Kelas akan muncul setelah memilih/sesuai Role.
                                        <br>
                                        <strong>Catatan:</strong> Kelas yang sudah ditugaskan ke pengguna lain otomatis tidak ditampilkan agar tidak terjadi duplikat.
                                    </small>
                                </div>

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
                                       class="form-control<?= isset($errors['username']) ? ' is-invalid' : '' ?>"
                                       id="username"
                                       name="username"
                                       value="<?= old('username') ?>"
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
                                       class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                       id="email"
                                       name="email"
                                       value="<?= old('email') ?>"
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
                                <label for="password" class="form-label">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                                           id="password"
                                           name="password"
                                           placeholder="Minimal 6 karakter"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Toggle password visibility">
                                        <i class="mdi mdi-eye-outline" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Minimal 6 karakter.</small>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?= esc($errors['password']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">
                                    Konfirmasi Password <span class="text-danger">*</span>
                                </label>
                                <input type="password"
                                       class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
                                       id="password_confirm"
                                       name="password_confirm"
                                       placeholder="Ulangi password"
                                       required>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['password_confirm']) ?>
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
                                       class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                       id="phone"
                                       name="phone"
                                       value="<?= old('phone') ?>"
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
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           <?= $oldActive === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Aktifkan pengguna setelah dibuat
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3" role="alert">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Informasi:</strong> Password akan di-enkripsi secara otomatis.
                        Pastikan untuk mencatat password dan menyampaikannya kepada pengguna.
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
                                        <i class="mdi mdi-content-save me-1"></i> Simpan
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('togglePassword');
    const password  = document.getElementById('password');
    const eyeIcon   = document.getElementById('eyeIcon');
    if (toggleBtn && password && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            eyeIcon.classList.toggle('mdi-eye-outline');
            eyeIcon.classList.toggle('mdi-eye-off-outline');
        });
    }

    const passConfirm = document.getElementById('password_confirm');
    if (passConfirm && password) {
        passConfirm.addEventListener('keyup', function () {
            if (!password.value || !passConfirm.value) {
                passConfirm.classList.remove('is-valid','is-invalid');
                return;
            }
            const same = password.value === passConfirm.value;
            passConfirm.classList.toggle('is-valid', same);
            passConfirm.classList.toggle('is-invalid', !same);
        });
    }

    const username = document.getElementById('username');
    if (username) {
        username.addEventListener('keyup', function () {
            this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '');
        });
    }

    const phone = document.getElementById('phone');
    if (phone) {
        phone.addEventListener('keyup', function () {
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

        // cegah nilai "nyangkut" saat user ganti role
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
        document.querySelectorAll('.alert').forEach(function(a){
            a.classList.add('fade');
        });
    }, 5000);

    Array.prototype.slice.call(document.querySelectorAll('.needs-validation')).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>
<?= $this->endSection() ?>
