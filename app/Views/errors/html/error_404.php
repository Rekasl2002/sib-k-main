<?php
helper('url');

$appName = function_exists('setting')
  ? setting('app_name', 'SIB-K', 'general')
  : 'SIB-K';

$schoolName = function_exists('setting')
  ? setting('school_name', env('school.name', ''), 'general')
  : env('school.name', '');

$logoPath = function_exists('setting')
  ? setting('logo_path', 'assets/images/logo.png', 'branding')
  : 'assets/images/logo.png';

$faviconPath = function_exists('setting')
  ? setting('favicon_path', 'assets/images/favicon.ico', 'branding')
  : 'assets/images/favicon.ico';

$logoUrl = base_url($logoPath);
$faviconUrl = base_url($faviconPath);

$headingText = $heading ?? 'Halaman Tidak Ditemukan';
$messageText = $message ?? 'Maaf, halaman yang Anda cari tidak tersedia atau sudah dipindahkan.';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($appName) ?> - 404</title>
  <link rel="icon" href="<?= esc($faviconUrl) ?>" type="image/x-icon">
  <style>
    :root{
      --sibk-primary:#1f6f54;
      --sibk-primary-2:#0f3b2c;
      --sibk-primary-3:#082318;
      --sibk-accent:#d1a545;
      --sibk-surface:#ffffff;
      --sibk-text:#0f172a;
      --sibk-muted:#64748b;
      --sibk-border:rgba(15,23,42,.10);
      --shadow:0 18px 55px rgba(2,8,6,.35);
      --radius:22px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:18px;
      font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
      background:
        radial-gradient(1200px 420px at -10% -10%, rgba(255,255,255,.10), transparent 60%),
        radial-gradient(900px 520px at 110% 0%, rgba(209,165,69,.14), transparent 60%),
        linear-gradient(180deg, var(--sibk-primary) 0%, var(--sibk-primary-2) 52%, var(--sibk-primary-3) 100%);
      color:var(--sibk-text);
    }
    .wrap{width:100%;max-width:720px;position:relative}
    .card{
      background:rgba(255,255,255,.92);
      border:1px solid rgba(255,255,255,.18);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
      backdrop-filter: blur(10px);
    }
    .head{
      padding:18px 18px 14px;
      background:
        radial-gradient(900px 260px at -10% -10%, rgba(255,255,255,.14), transparent 60%),
        linear-gradient(180deg, #1f6f54 0%, #0f3b2c 55%, #082318 100%);
      border-top:4px solid var(--sibk-accent);
      color:#fff;
    }
    .brand{display:flex;gap:12px;align-items:center}
    .logo{
      width:46px;height:46px;border-radius:14px;
      background:rgba(255,255,255,.92);
      display:flex;align-items:center;justify-content:center;
      padding:6px; border:1px solid rgba(255,255,255,.25);
    }
    .logo img{width:100%;height:100%;object-fit:contain}
    .bt{line-height:1.05;min-width:0}
    .bt .app{margin:0;font-weight:800;letter-spacing:.2px}
    .bt .school{margin:3px 0 0;font-size:.85rem;color:rgba(255,255,255,.78);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px}
    .body{padding:20px 18px 18px}
    .code{
      display:inline-flex;align-items:center;gap:10px;
      font-weight:900;letter-spacing:.5px;
      font-size:3.4rem;line-height:1;
      margin:4px 0 10px;
      color:rgba(15,23,42,.86);
    }
    .chip{
      display:inline-flex;align-items:center;gap:8px;
      padding:6px 10px;border-radius:999px;
      background:rgba(31,111,84,.12);
      border:1px solid rgba(31,111,84,.18);
      color:rgba(31,111,84,1);
      font-weight:800;font-size:.82rem;
    }
    h1{margin:10px 0 6px;font-size:1.25rem;color:var(--sibk-text)}
    p{margin:0;color:rgba(15,23,42,.65)}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
    .btn{
      appearance:none;border:0;cursor:pointer;
      height:44px;padding:0 14px;border-radius:14px;
      font-weight:800;letter-spacing:.2px;
      display:inline-flex;align-items:center;justify-content:center;
      text-decoration:none;
    }
    .btn-primary{
      color:#fff;
      background:
        radial-gradient(700px 140px at 20% 0%, rgba(255,255,255,.16), transparent 55%),
        linear-gradient(135deg, var(--sibk-primary) 0%, #165a45 55%, #0b2b21 100%);
      box-shadow:0 14px 30px rgba(2,20,14,.20);
    }
    .btn-ghost{
      background:rgba(15,23,42,.06);
      border:1px solid rgba(15,23,42,.10);
      color:rgba(15,23,42,.75);
    }
    .foot{
      margin-top:14px;
      padding-top:12px;
      border-top:1px solid rgba(15,23,42,.08);
      color:rgba(15,23,42,.55);
      font-size:.82rem;
      text-align:center;
    }
    @media (max-width:576px){
      .code{font-size:3.0rem}
      .bt .school{max-width:240px}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="head">
        <div class="brand">
          <div class="logo"><img src="<?= esc($logoUrl) ?>" alt="Logo"></div>
          <div class="bt">
            <p class="app"><?= esc($appName) ?></p>
            <p class="school"><?= esc($schoolName) ?></p>
          </div>
        </div>
      </div>

      <div class="body">
        <div class="chip">HTTP 404</div>
        <div class="code">404</div>

        <h1><?= esc($headingText) ?></h1>
        <p><?= esc(strip_tags($messageText)) ?></p>

        <div class="actions">
          <a class="btn btn-primary" href="<?= base_url('/') ?>">Ke Beranda</a>
          <button class="btn btn-ghost" type="button" onclick="history.back()">Kembali</button>
        </div>

        <div class="foot">
          &copy; <?= date('Y') ?> <?= esc($appName) ?> · Sistem Informasi Bimbingan dan Konseling
        </div>
      </div>
    </div>
  </div>
</body>
</html>
