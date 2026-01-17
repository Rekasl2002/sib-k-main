<?php
/**
 * File Path: app/Views/profile/_change_password.php
 *
 * Partial view: Form ubah password untuk semua role (self-service).
 * Menggunakan flashdata:
 * - pw_errors: array error validation (bisa numeric list atau associative per-field)
 * - pw_success: string pesan sukses
 */

$pwErrors  = session()->getFlashdata('pw_errors') ?? [];
$pwSuccess = session()->getFlashdata('pw_success');

// Normalisasi error agar aman untuk dua bentuk:
// 1) ['current_password' => '...', 'new_password' => '...']
// 2) [0 => '...', 1 => '...'] (list umum)
$fieldErrors = is_array($pwErrors) ? $pwErrors : [];
$generalErrors = [];

if (!empty($fieldErrors)) {
    foreach ($fieldErrors as $k => $v) {
        if (is_int($k)) {
            $generalErrors[] = (string) $v;
        }
    }
}

// Route fallback: kalau route alias belum dibuat, jangan fatal.
$actionUrl = '#';
try {
    $actionUrl = route_to('profile.password');
} catch (\Throwable $e) {
    // fallback aman: sesuaikan jika route kamu beda
    // (misal: /profile/change-password)
    $actionUrl = url_to('ProfileController::changePassword');
}

function pw_err(array $errors, string $key): string
{
    $msg = $errors[$key] ?? '';
    return is_string($msg) ? $msg : '';
}
?>

<div class="card mt-3">
    <div class="card-body">
        <h4 class="card-title mb-3">Ubah Password</h4>

        <?php if (!empty($pwSuccess)): ?>
            <div class="alert alert-success">
                <?= esc($pwSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($generalErrors)): ?>
            <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                    <?php foreach ($generalErrors as $msg): ?>
                        <li><?= esc($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= esc($actionUrl) ?>" method="post" autocomplete="off" id="form-change-password">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="current_password" class="form-label">Password Saat Ini</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control <?= pw_err($fieldErrors, 'current_password') ? 'is-invalid' : '' ?>"
                        id="current_password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        aria-describedby="current_password_feedback"
                    >
                    <button class="btn btn-outline-secondary" type="button" data-pw-toggle="#current_password" aria-label="Toggle password">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                    <?php if (pw_err($fieldErrors, 'current_password')): ?>
                        <div class="invalid-feedback" id="current_password_feedback">
                            <?= esc(pw_err($fieldErrors, 'current_password')) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">Password Baru</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control <?= pw_err($fieldErrors, 'new_password') ? 'is-invalid' : '' ?>"
                        id="new_password"
                        name="new_password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        aria-describedby="new_password_help new_password_feedback"
                    >
                    <button class="btn btn-outline-secondary" type="button" data-pw-toggle="#new_password" aria-label="Toggle password">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                    <?php if (pw_err($fieldErrors, 'new_password')): ?>
                        <div class="invalid-feedback" id="new_password_feedback">
                            <?= esc(pw_err($fieldErrors, 'new_password')) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <small class="text-muted" id="new_password_help">Minimal 8 karakter.</small>
            </div>

            <div class="mb-3">
                <label for="new_password_confirm" class="form-label">Konfirmasi Password Baru</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control <?= pw_err($fieldErrors, 'new_password_confirm') ? 'is-invalid' : '' ?>"
                        id="new_password_confirm"
                        name="new_password_confirm"
                        required
                        autocomplete="new-password"
                        aria-describedby="new_password_confirm_feedback"
                    >
                    <button class="btn btn-outline-secondary" type="button" data-pw-toggle="#new_password_confirm" aria-label="Toggle password">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                    <?php if (pw_err($fieldErrors, 'new_password_confirm')): ?>
                        <div class="invalid-feedback" id="new_password_confirm_feedback">
                            <?= esc(pw_err($fieldErrors, 'new_password_confirm')) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-change-password">
                <i class="mdi mdi-lock-reset me-1"></i> Simpan Password Baru
            </button>
        </form>
    </div>
</div>

<script>
(function(){
    // Toggle show/hide password + ganti ikon
    document.querySelectorAll('[data-pw-toggle]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var sel = btn.getAttribute('data-pw-toggle');
            var el = document.querySelector(sel);
            if (!el) return;

            var icon = btn.querySelector('i');
            if (el.type === 'password') {
                el.type = 'text';
                if (icon) {
                    icon.classList.remove('mdi-eye-outline');
                    icon.classList.add('mdi-eye-off-outline');
                }
            } else {
                el.type = 'password';
                if (icon) {
                    icon.classList.remove('mdi-eye-off-outline');
                    icon.classList.add('mdi-eye-outline');
                }
            }
        });
    });

    // Optional: cegah submit dobel
    var form = document.getElementById('form-change-password');
    var btn  = document.getElementById('btn-change-password');
    if (form && btn) {
        form.addEventListener('submit', function(){
            btn.disabled = true;
            setTimeout(function(){ btn.disabled = false; }, 4000);
        });
    }
})();
</script>
