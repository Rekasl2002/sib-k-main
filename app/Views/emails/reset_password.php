<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password SIB-K</title>
</head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#1f2933">
    <p>Halo <?= esc($name ?? 'Pengguna') ?>,</p>
    <p>Kami menerima permintaan reset password untuk akun SIB-K Anda.</p>
    <p>
        <a href="<?= esc($resetUrl ?? '#', 'attr') ?>" style="display:inline-block;background:#1f6f54;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px">
            Reset Password
        </a>
    </p>
    <p>Link ini berlaku selama <?= esc($expiresIn ?? '1 jam') ?>. Abaikan email ini jika Anda tidak meminta reset password.</p>
    <p>Salam,<br>SIB-K</p>
</body>
</html>
