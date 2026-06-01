<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php helper('settings'); ?>
    <title><?= esc(setting('app_name', 'SIB-K', 'general')) ?> - Reset Password</title>
    <link rel="icon" href="<?= base_url(setting('favicon_path', 'assets/images/favicon.ico', 'branding')) ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h1 class="h5 mb-1">Reset Password</h1>
            <div class="small opacity-75"><?= esc($email ?? '') ?></div>
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

            <form method="post" action="<?= site_url('reset-password') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token ?? '', 'attr') ?>">
                <div class="mb-3">
                    <label for="password" class="form-label">Password baru</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6" autofocus>
                </div>
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Konfirmasi password baru</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                </div>
                <button type="submit" class="btn btn-success w-100">Simpan Password Baru</button>
            </form>
        </div>
    </main>
</body>
</html>
