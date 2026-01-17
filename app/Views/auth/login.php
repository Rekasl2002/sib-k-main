<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php helper('settings'); ?>

    <title><?= esc(setting('app_name', 'SIB-K', 'general')) ?> - Login</title>
    <link rel="icon" href="<?= base_url(setting('favicon_path', 'assets/images/favicon.ico', 'branding')) ?>" type="image/x-icon">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Optional: font (kalau tidak ada, fallback ke system) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            /* SIBK Mapersis-like */
            --sibk-primary: #1f6f54;
            --sibk-primary-2: #0f3b2c;
            --sibk-primary-3: #082318;
            --sibk-accent: #d1a545;
            --sibk-surface: #ffffff;
            --sibk-text: #0f172a;
            --sibk-muted: #64748b;
            --sibk-border: rgba(15, 23, 42, 0.10);
            --sibk-shadow: 0 18px 55px rgba(2, 8, 6, 0.35);
            --radius-xl: 22px;
            --radius-lg: 16px;
        }

        * { box-sizing: border-box; }

        body{
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;

            /* Background senada sidebar */
            background:
                radial-gradient(1200px 420px at -10% -10%, rgba(255,255,255,.10), transparent 60%),
                radial-gradient(900px 520px at 110% 0%, rgba(209,165,69,.14), transparent 60%),
                linear-gradient(180deg, var(--sibk-primary) 0%, var(--sibk-primary-2) 52%, var(--sibk-primary-3) 100%);
            color: var(--sibk-text);
            overflow-x: hidden;
        }

        /* Ornamen halus (tidak ganggu) */
        body::before{
            content:"";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
              radial-gradient(600px 260px at 20% 15%, rgba(255,255,255,.09), transparent 60%),
              radial-gradient(520px 220px at 80% 75%, rgba(255,255,255,.06), transparent 60%);
            opacity: .9;
        }

        .login-container{
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 1;
        }

        .login-card{
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: var(--radius-xl);
            box-shadow: var(--sibk-shadow);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        /* Header card: gradient senada sidebar + aksen emas */
        .login-header{
            position: relative;
            padding: 26px 22px 18px;
            color: #fff;
            text-align: center;
            background:
                radial-gradient(900px 260px at -10% -10%, rgba(255,255,255,.14), transparent 60%),
                linear-gradient(180deg, #1f6f54 0%, #0f3b2c 55%, #082318 100%);
            border-top: 4px solid var(--sibk-accent);
        }

        .login-header .brand-row{
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .logo-wrap{
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: rgba(255,255,255,.92);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            box-shadow: 0 10px 25px rgba(0,0,0,.25);
            border: 1px solid rgba(255,255,255,.25);
        }

        .logo-wrap img{
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-text{
            text-align: left;
            line-height: 1.05;
            min-width: 0;
        }

        .brand-text .app{
            font-weight: 800;
            letter-spacing: .2px;
            font-size: 1.18rem;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .brand-text .school{
            margin: 4px 0 0 0;
            font-size: .86rem;
            color: rgba(255,255,255,.78);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 320px;
        }

        .login-body{
            padding: 22px 22px 18px;
            background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,255,255,.90));
        }

        .hint{
            text-align: center;
            color: var(--sibk-muted);
            font-size: .85rem;
            margin: 0 0 14px 0;
        }

        .form-group{ margin-bottom: 14px; }

        .form-label{
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            font-size: .88rem;
        }

        .form-control{
            height: 48px;
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, 0.14);
            padding: 10px 14px;
            font-size: .95rem;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
            background: rgba(255,255,255,.9);
        }

        .form-control:focus{
            border-color: rgba(31,111,84,.55);
            box-shadow: 0 0 0 .22rem rgba(31,111,84,.14);
        }

        .input-group{
            position: relative;
        }

        .input-group .input-icon{
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(15, 23, 42, .55);
            cursor: pointer;
            z-index: 10;
            padding: 6px;
            border-radius: 10px;
            transition: background .15s ease;
        }

        .input-group .input-icon:hover{
            background: rgba(31,111,84,.10);
            color: rgba(31,111,84,1);
        }

        .form-check{
            margin: 10px 0 14px;
            user-select: none;
        }

        .form-check-input{
            border-color: rgba(15, 23, 42, .25);
        }

        .form-check-input:checked{
            background-color: var(--sibk-primary);
            border-color: var(--sibk-primary);
        }

        .btn-login{
            width: 100%;
            height: 48px;
            border-radius: 14px;
            border: none;
            color: #fff;
            font-weight: 800;
            letter-spacing: .2px;
            background:
                radial-gradient(700px 140px at 20% 0%, rgba(255,255,255,.16), transparent 55%),
                linear-gradient(135deg, var(--sibk-primary) 0%, #165a45 55%, #0b2b21 100%);
            box-shadow: 0 14px 30px rgba(2, 20, 14, .25);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }

        .btn-login:hover{
            transform: translateY(-1px);
            filter: brightness(1.02);
            box-shadow: 0 18px 42px rgba(2, 20, 14, .30);
        }

        .btn-login:active{
            transform: translateY(0px);
            box-shadow: 0 12px 28px rgba(2, 20, 14, .22);
        }

        .forgot-password{
            text-align: right;
            margin-top: 12px;
        }

        .forgot-password a{
            color: rgba(31,111,84,1);
            text-decoration: none;
            font-weight: 700;
            font-size: .88rem;
        }

        .forgot-password a:hover{
            text-decoration: underline;
        }

        .alert{
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            margin-bottom: 14px;
            box-shadow: 0 10px 26px rgba(15,23,42,.06);
        }

        .footer-text{
            text-align: center;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            color: rgba(15,23,42,.65);
            font-size: .82rem;
        }

        .footer-text .sub{
            margin: 4px 0 0 0;
            color: rgba(15,23,42,.55);
        }

        /* Mobile comfort */
        @media (max-width: 576px){
            body{ padding: 14px; }
            .login-header{ padding: 22px 18px 16px; }
            .login-body{ padding: 18px 18px 16px; }
            .brand-text .school{ max-width: 220px; }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="brand-row">
                    <div class="logo-wrap">
                        <img src="<?= base_url(setting('logo_path', 'assets/images/logo.png', 'branding')) ?>" alt="Logo" />
                    </div>
                    <div class="brand-text">
                        <p class="app mb-0"><?= esc(setting('app_name', 'SIB-K', 'general')) ?></p>
                        <p class="school mb-0"><?= esc(setting('school_name', env('school.name'), 'general')) ?></p>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="login-body">

                <!-- Success Message -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Validation Errors -->
                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0 ps-3">
                            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="<?= base_url('login') ?>" method="POST" id="loginForm" novalidate>
                    <?= csrf_field() ?>

                    <!-- Username -->
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i> Username atau Email
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="Masukkan username atau email"
                            value="<?= esc(old('username') ?? '') ?>"
                            required
                            autofocus>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Password
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                required>
                            <span class="input-icon" onclick="togglePassword()" role="button" aria-label="Tampilkan/Sembunyikan Password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check">
                        <input class="form-check-input"
                            type="checkbox"
                            id="remember"
                            name="remember"
                            value="1">
                        <label class="form-check-label" for="remember">
                            Ingat Saya
                        </label>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Masuk
                    </button>

                    <!-- Forgot Password 
                    <div class="forgot-password">
                        <a href="<?= base_url('forgot-password') ?>">
                            <i class="fas fa-question-circle me-1"></i> Lupa Password?
                        </a>
                    </div>-->
                </form>

                <!-- Footer -->
                <div class="footer-text">
                    <div>&copy; <?= date('Y') ?> <?= esc(setting('app_name', 'SIB-K', 'general')) ?>. All Rights Reserved.</div>
                    <div class="sub">Sistem Informasi Bimbingan dan Konseling</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (!passwordInput || !toggleIcon) return;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation ringan (tanpa alert yang mengganggu UI)
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const username = document.getElementById('username')?.value?.trim();
            const password = document.getElementById('password')?.value?.trim();

            if (!username || !password) {
                e.preventDefault();
                // fokus ke field pertama yang kosong
                if (!username) document.getElementById('username')?.focus();
                else document.getElementById('password')?.focus();
            }
        });

        // Auto hide alerts after 5 seconds (jika ada)
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                try {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                } catch (e) {}
            });
        }, 5000);
    </script>
</body>

</html>
