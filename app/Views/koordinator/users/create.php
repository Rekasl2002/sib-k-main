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
                <?= esc(session()->getFlashdata('error')) ?>
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

                                <?php
                                    $counselorLabelMap = [];
                                    foreach ($classesCounselor as $c) { $counselorLabelMap[(int)($c['id'] ?? 0)] = class_label($c); }
                                    $homeroomLabelMap = [];
                                    foreach ($classesHomeroom as $c) { $homeroomLabelMap[(int)($c['id'] ?? 0)] = class_label($c); }
                                ?>
                                <!-- Guru BK: banyak kelas binaan (chip) -->
                                <div id="assign_guru_bk" style="display:none;">
                                    <div class="js-multi" data-name="counselor_class_ids[]">
                                        <small class="text-muted d-block mb-2">Pilih satu atau beberapa kelas binaan. Klik nama kelas pada daftar &mdash; otomatis masuk ke kotak di bawah.</small>
                                        <div class="js-chips border rounded p-2 mb-2 bg-light">
                                            <?php foreach ($oldCounselorIds as $cid): $cid = (int)$cid; if ($cid <= 0) continue; ?>
                                                <span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;">
                                                    <span><?= esc($counselorLabelMap[$cid] ?? ('Kelas #' . $cid)) ?></span>
                                                    <input type="hidden" name="counselor_class_ids[]" value="<?= $cid ?>">
                                                    <button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>
                                                </span>
                                            <?php endforeach; ?>
                                            <span class="text-muted js-chip-empty"<?= ! empty($oldCounselorIds) ? ' style="display:none;"' : '' ?>>Belum ada kelas dipilih.</span>
                                        </div>
                                        <select class="form-select select2-search js-picker">
                                            <option value="">Ketik untuk mencari kelas&hellip;</option>
                                            <?php foreach ($classesCounselor as $c): ?>
                                                <option value="<?= (int)($c['id'] ?? 0) ?>"><?= esc(class_label($c)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Bisa lebih dari satu; kelas yang sudah dipilih tidak muncul lagi. Tekan × pada chip untuk menghapus.</small>
                                    </div>
                                </div>

                                <!-- Wali Kelas: satu kelas perwalian (chip tunggal) -->
                                <div id="assign_wali_kelas" style="display:none;">
                                    <div class="js-single" data-name="homeroom_class_id">
                                        <small class="text-muted d-block mb-2">Pilih 1 kelas perwalian.</small>
                                        <input type="hidden" name="homeroom_class_id" value="<?= ($oldHomeroomId !== '' && (int)$oldHomeroomId > 0) ? (int)$oldHomeroomId : '' ?>">
                                        <div class="js-chips border rounded p-2 mb-2 bg-light">
                                            <?php if ($oldHomeroomId !== '' && (int)$oldHomeroomId > 0): ?>
                                                <span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;">
                                                    <span><?= esc($homeroomLabelMap[(int)$oldHomeroomId] ?? ('Kelas #' . (int)$oldHomeroomId)) ?></span>
                                                    <button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>
                                                </span>
                                            <?php endif; ?>
                                            <span class="text-muted js-chip-empty"<?= ($oldHomeroomId !== '' && (int)$oldHomeroomId > 0) ? ' style="display:none;"' : '' ?>>Belum ada kelas dipilih.</span>
                                        </div>
                                        <select class="form-select select2-search js-picker-single">
                                            <option value="">Ketik untuk mencari kelas&hellip;</option>
                                            <?php foreach ($classesHomeroom as $c): ?>
                                                <option value="<?= (int)($c['id'] ?? 0) ?>"><?= esc(class_label($c)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
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
                                    Email
                                </label>
                                <input type="email"
                                       class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                       id="email"
                                       name="email"
                                       value="<?= old('email') ?>"
                                       placeholder="contoh@email.com">
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

    const GURU_BK_ID = <?= (int)$guruBkRoleId ?>;
    const WALI_ID    = <?= (int)$waliKelasRoleId ?>;

    function clearChips(containerId) {
        const box = document.getElementById(containerId);
        if (!box) return;
        box.querySelectorAll('.js-chip').forEach(ch => ch.remove());
        const single = box.querySelector('.js-single input[type=hidden][name="homeroom_class_id"]');
        if (single) single.value = '';
        const empty = box.querySelector('.js-chip-empty');
        if (empty) empty.style.display = '';
        if (window.jQuery && typeof window.__restoreAllOptions === 'function') {
            $(box).find('.js-picker, .js-picker-single').each(function () { window.__restoreAllOptions($(this)); });
        }
    }

    function refreshAssignmentVisibility() {
        const v = parseInt((roleSelect && roleSelect.value) ? roleSelect.value : '0', 10);

        if (assignBK)   assignBK.style.display   = (v === GURU_BK_ID) ? '' : 'none';
        if (assignWali) assignWali.style.display = (v === WALI_ID) ? '' : 'none';

        if (v === GURU_BK_ID) {
            clearChips('assign_wali_kelas');
        } else if (v === WALI_ID) {
            clearChips('assign_guru_bk');
        } else {
            clearChips('assign_guru_bk');
            clearChips('assign_wali_kelas');
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

<!-- Penugasan Kelas: pola chip (select2 + kotak chip) ala form Bimbingan -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(function () {
    if (window.jQuery && $.fn.select2) {
      $('.select2-search').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: 'Ketik untuk mencari...' });
    }

    function pickerOptionByValue($picker, val) {
      return $picker.find('option').filter(function () { return this.value === String(val); });
    }
    function detachOption($chip, $picker, val) {
      var $opt = pickerOptionByValue($picker, val);
      if ($opt.length) { $chip.data('opt', $opt); $chip.data('optParent', $opt.parent()); $opt.detach(); }
    }
    function restoreOption($chip) {
      var $opt = $chip.data('opt'); var $parent = $chip.data('optParent');
      if ($opt && $parent && $parent.length) { $parent.append($opt); }
    }
    window.__restoreAllOptions = function ($picker) {
      var $widget = $picker.closest('.js-multi, .js-single');
      $widget.find('.js-chip').each(function () { restoreOption($(this)); });
    };

    function addChip($widget, name, val, text) {
      if (!val) { return null; }
      var $chips = $widget.find('.js-chips'); var dup = false;
      $chips.find('input[type=hidden]').each(function () { if (this.name === name && this.value === String(val)) { dup = true; } });
      if (dup) { return null; }
      var $chip = $('<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;"></span>');
      $('<span></span>').text(text).appendTo($chip);
      if (name !== '__single') { $('<input type="hidden">').attr('name', name).val(val).appendTo($chip); }
      $('<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>').appendTo($chip);
      $widget.find('.js-chip-empty').hide().before($chip);
      return $chip;
    }

    $('.js-multi, .js-single').each(function () {
      var $widget = $(this); var $picker = $widget.find('.js-picker, .js-picker-single');
      $widget.find('.js-chip').each(function () { detachOption($(this), $picker, $(this).find('input[type=hidden]').val()); });
    });

    $('.js-multi .js-picker').on('select2:select', function () {
      var $widget = $(this).closest('.js-multi'); var $picker = $(this);
      var val = $picker.val(); var text = $.trim($picker.find('option:selected').text());
      var $chip = addChip($widget, $widget.data('name'), val, text);
      $picker.val('').trigger('change');
      if ($chip) { detachOption($chip, $picker, val); }
    });
    $('.js-multi').on('click', '.js-chip-remove', function () {
      var $widget = $(this).closest('.js-multi'); var $chip = $(this).closest('.js-chip');
      restoreOption($chip); $chip.remove();
      if ($widget.find('.js-chip').length === 0) { $widget.find('.js-chip-empty').show(); }
    });

    $('.js-single .js-picker-single').on('select2:select', function () {
      var $widget = $(this).closest('.js-single'); var $picker = $(this);
      var val = $picker.val(); var text = $.trim($picker.find('option:selected').text());
      if (val) {
        $widget.find('.js-chip').each(function () { restoreOption($(this)); }).remove();
        $widget.find('input[type=hidden][name="' + $widget.data('name') + '"]').val(val);
        var $chip = addChip($widget, '__single', val, text);
        if ($chip) { detachOption($chip, $picker, val); }
      }
      $picker.val('').trigger('change');
    });
    $('.js-single').on('click', '.js-chip-remove', function () {
      var $widget = $(this).closest('.js-single'); var $chip = $(this).closest('.js-chip');
      restoreOption($chip);
      $widget.find('input[type=hidden][name="' + $widget.data('name') + '"]').val('');
      $chip.remove();
      $widget.find('.js-chip-empty').show();
    });
  });
</script>
<?= $this->endSection() ?>
