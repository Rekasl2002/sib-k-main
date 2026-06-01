<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php helper('settings'); ?>
    <title><?= esc(setting('app_name', 'SIB-K', 'general')) ?> - Registrasi</title>
    <link rel="icon" href="<?= base_url(setting('favicon_path', 'assets/images/favicon.ico', 'branding')) ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: linear-gradient(180deg,#1f6f54,#082318); padding: 20px; }
        .auth-card { width: 100%; max-width: 560px; border: 0; border-radius: 18px; box-shadow: 0 18px 55px rgba(2,8,6,.32); overflow: hidden; }
        .auth-head { background: #0f3b2c; color: #fff; padding: 22px; border-top: 4px solid #d1a545; }
        .auth-body { padding: 24px; background: #fff; }
    </style>
</head>
<body>
    <main class="card auth-card">
        <div class="auth-head">
            <h1 class="h5 mb-1">Registrasi Akun</h1>
            <div class="small opacity-75"><?= esc($schoolName ?? setting('school_name', env('school.name'), 'general')) ?></div>
        </div>
        <div class="auth-body">
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

            <form method="post" action="<?= site_url('register') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" value="<?= esc(old('username') ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= esc(old('email') ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="full_name">Nama lengkap</label>
                        <input class="form-control" id="full_name" name="full_name" value="<?= esc(old('full_name') ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Konfirmasi password</label>
                        <input class="form-control" type="password" id="password_confirm" name="password_confirm" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100 mt-4">Daftar</button>
            </form>
            <div class="text-center mt-3">
                <a href="<?= site_url('login') ?>" class="text-decoration-none">Sudah punya akun? Login</a>
            </div>
        </div>
    </main>
</body>
</html>
