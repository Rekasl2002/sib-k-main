<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php helper('settings'); ?>
    <title><?= esc(setting('app_name', 'SIB-K', 'general')) ?> - Lupa Password</title>
    <link rel="icon" href="<?= base_url(setting('favicon_path', 'assets/images/favicon.ico', 'branding')) ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: linear-gradient(180deg,#1f6f54,#082318); padding: 20px; }
        .auth-card { width: 100%; max-width: 460px; border: 0; border-radius: 18px; box-shadow: 0 18px 55px rgba(2,8,6,.32); overflow: hidden; }
        .auth-head { background: #0f3b2c; color: #fff; padding: 22px; border-top: 4px solid #d1a545; }
        .auth-body { padding: 24px; background: #fff; }
    </style>
</head>
<body>
    <main class="card auth-card">
        <div class="auth-head">
            <h1 class="h5 mb-1">Lupa Password</h1>
            <div class="small opacity-75"><?= esc($schoolName ?? setting('school_name', env('school.name'), 'general')) ?></div>
        </div>
        <div class="auth-body">
            <p class="text-muted small mb-3">
                Isi email akun atau nomor telepon yang dapat dihubungi. Permintaan akan diteruskan ke Admin sekolah untuk ditindaklanjuti.
            </p>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('forgot-password') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email akun</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= esc(old('email') ?? '') ?>" autofocus>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Nomor telepon yang dapat dihubungi</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= esc(old('phone') ?? '') ?>" placeholder="contoh: 081234567890">
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-paper-plane me-1"></i> Kirim Permintaan ke Admin
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="<?= site_url('login') ?>" class="text-decoration-none">Kembali ke login</a>
            </div>
        </div>
    </main>
</body>
</html>
