<?php
/**
 * File Path: app/Views/errors/html/error_exception.php
 * SIB-K Themed Exception Page (Dev-friendly, Copyable)
 */

helper('url');

// Branding (aman kalau helper setting() belum ada)
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

// Ambil exception & info yang mungkin dikirim CI (toleran berbagai versi/handler)
$ex = $exception ?? null;

$exClass   = $ex ? get_class($ex) : ($type ?? 'Exception');
$exMessage = $ex ? $ex->getMessage() : ($message ?? 'Exception');
$exFile    = $ex ? $ex->getFile() : ($file ?? '');
$exLine    = $ex ? (int) $ex->getLine() : (int) ($line ?? 0);

// code/status
$exCode = $ex ? (int) ($ex->getCode() ?: 0) : (int) ($code ?? 0);
$statusCode = (int) ($statusCode ?? $status_code ?? 500);
if ($statusCode <= 0) $statusCode = 500;

// Trace: bisa dari $trace (string) atau dari exception
$traceText = '';
$traceArr  = [];

if (isset($trace) && is_string($trace) && trim($trace) !== '') {
  $traceText = $trace;
} elseif ($ex) {
  // String full exception (biasanya sudah termasuk stacktrace)
  $traceText = (string) $ex;
  $traceArr  = $ex->getTrace();
}

// Request context (aman, ringkas)
$req = service('request');

$now = date('Y-m-d H:i:s');
$envName = defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown';
$phpVer  = PHP_VERSION;
$ciVer = 'unknown';

// CI4 punya class constant: \CodeIgniter\CodeIgniter::CI_VERSION
if (class_exists('\CodeIgniter\CodeIgniter') && defined('\CodeIgniter\CodeIgniter::CI_VERSION')) {
    $ciVer = \CodeIgniter\CodeIgniter::CI_VERSION;
} elseif (defined('CI_VERSION')) {
    // fallback legacy: jangan panggil CI_VERSION langsung, pakai constant('CI_VERSION')
    $ciVer = (string) constant('CI_VERSION');
}


$method = method_exists($req, 'getMethod') ? strtoupper((string)$req->getMethod()) : '';
$currentUrl = '';
try {
  $currentUrl = current_url();
} catch (\Throwable $t) {
  $currentUrl = '';
}

$ip = method_exists($req, 'getIPAddress') ? (string)$req->getIPAddress() : '';
$ua = method_exists($req, 'getUserAgent') && $req->getUserAgent()
  ? (string)$req->getUserAgent()
  : ($_SERVER['HTTP_USER_AGENT'] ?? '');

$get = $_GET ?? [];
$post = $_POST ?? [];

// Headers (ambil subset yang biasanya berguna)
$headersSubset = [];
try {
  if (method_exists($req, 'getHeaders')) {
    $hdrs = $req->getHeaders();
    $pick = ['host','referer','origin','content-type','accept','accept-language','x-requested-with','user-agent'];
    foreach ($pick as $k) {
      foreach ($hdrs as $hk => $hv) {
        if (strtolower($hk) === $k) {
          $headersSubset[$hk] = (string) $hv->getValueLine();
        }
      }
    }
  }
} catch (\Throwable $t) {
  $headersSubset = [];
}

// Helper pretty json
$pretty = function ($v): string {
  try {
    return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
  } catch (\Throwable $t) {
    return '';
  }
};

// Build "Copy All" payload
$allPayload = [];
$allPayload[] = "=== SIB-K Exception Dump ===";
$allPayload[] = "Time       : {$now}";
$allPayload[] = "Env        : {$envName}";
$allPayload[] = "PHP        : {$phpVer}";
$allPayload[] = "CI         : {$ciVer}";
$allPayload[] = "Status     : {$statusCode}";
$allPayload[] = "Code       : {$exCode}";
$allPayload[] = "Type       : {$exClass}";
$allPayload[] = "Message    : {$exMessage}";
$allPayload[] = "File       : {$exFile}";
$allPayload[] = "Line       : {$exLine}";
$allPayload[] = "";
$allPayload[] = "--- Request ---";
$allPayload[] = "Method     : {$method}";
$allPayload[] = "URL        : {$currentUrl}";
$allPayload[] = "IP         : {$ip}";
$allPayload[] = "User-Agent : {$ua}";
$allPayload[] = "";
$allPayload[] = "GET        : " . ($pretty($get) ?: '[]');
$allPayload[] = "POST       : " . ($pretty($post) ?: '[]');
$allPayload[] = "Headers    : " . ($pretty($headersSubset) ?: '{}');
$allPayload[] = "";
$allPayload[] = "--- Trace ---";
$allPayload[] = $traceText ?: '(no trace available)';
$allPayloadStr = implode("\n", $allPayload);

// UI labels safe
$title = $title ?? 'Exception';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($appName) ?> - Exception</title>
  <link rel="icon" href="<?= esc($faviconUrl) ?>" type="image/x-icon">

  <style>
    :root{
      --sibk-primary:#1f6f54;
      --sibk-primary-2:#0f3b2c;
      --sibk-primary-3:#082318;
      --sibk-accent:#d1a545;
      --surface:rgba(255,255,255,.92);
      --text:#0f172a;
      --muted:#64748b;
      --border:rgba(15,23,42,.12);
      --shadow:0 18px 55px rgba(2,8,6,.35);
      --radius:22px;
      --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
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
      color:var(--text);
    }
    .wrap{width:100%;max-width:1100px}
    .card{
      background:var(--surface);
      border:1px solid rgba(255,255,255,.18);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
      backdrop-filter: blur(10px);
    }
    .head{
      padding:18px;
      background:
        radial-gradient(900px 260px at -10% -10%, rgba(255,255,255,.14), transparent 60%),
        linear-gradient(180deg, #1f6f54 0%, #0f3b2c 55%, #082318 100%);
      border-top:4px solid var(--sibk-accent);
      color:#fff;
    }
    .brand{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
    .brand-left{display:flex;gap:12px;align-items:center;min-width:0}
    .logo{
      width:46px;height:46px;border-radius:14px;
      background:rgba(255,255,255,.92);
      display:flex;align-items:center;justify-content:center;
      padding:6px;border:1px solid rgba(255,255,255,.25);
      flex:0 0 auto;
    }
    .logo img{width:100%;height:100%;object-fit:contain}
    .bt{line-height:1.05;min-width:0}
    .bt .app{margin:0;font-weight:900;letter-spacing:.2px}
    .bt .school{margin:3px 0 0;font-size:.85rem;color:rgba(255,255,255,.78);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:540px}
    .top-actions{display:flex;gap:10px;align-items:center}
    .pill{
      display:inline-flex;align-items:center;gap:8px;
      padding:7px 10px;border-radius:999px;
      background:rgba(0,0,0,.20);
      border:1px solid rgba(255,255,255,.18);
      color:#fff;font-weight:900;font-size:.82rem;
      user-select:none;
    }
    .btn{
      border:0;cursor:pointer;
      height:38px;padding:0 12px;border-radius:12px;
      font-weight:900;letter-spacing:.2px;
      display:inline-flex;align-items:center;justify-content:center;
      text-decoration:none;
      user-select:none;
      transition:transform .12s ease, opacity .12s ease;
    }
    .btn:hover{transform:translateY(-1px)}
    .btn:active{transform:translateY(0)}
    .btn-copy{
      color:#fff;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.22);
    }

    .body{padding:18px}
    .grid{
      display:grid;
      grid-template-columns: 1.1fr .9fr;
      gap:14px;
    }
    @media (max-width: 980px){
      .grid{grid-template-columns:1fr}
    }

    .panel{
      background:rgba(255,255,255,.88);
      border:1px solid var(--border);
      border-radius:18px;
      padding:14px;
    }
    .panel h2{
      margin:0 0 10px;
      font-size:1rem;
      font-weight:950;
      letter-spacing:.2px;
      color:rgba(15,23,42,.85);
    }
    .meta{
      display:grid;
      grid-template-columns: 140px 1fr;
      gap:6px 10px;
      font-size:.9rem;
      color:rgba(15,23,42,.72);
    }
    .k{color:rgba(15,23,42,.55);font-weight:800}
    .v{font-family:var(--mono);font-size:.88rem;color:rgba(15,23,42,.82);word-break:break-word}
    .badge-danger{
      display:inline-flex;align-items:center;gap:8px;
      padding:6px 10px;border-radius:999px;
      background:rgba(239,68,68,.14);
      border:1px solid rgba(239,68,68,.18);
      color:rgba(153,27,27,1);
      font-weight:950;
      font-size:.82rem;
      margin-bottom:10px;
    }

    .copybar{
      display:flex;gap:10px;flex-wrap:wrap;align-items:center;
      margin-bottom:10px;
    }
    .btn-primary{
      color:#fff;
      background:
        radial-gradient(700px 140px at 20% 0%, rgba(255,255,255,.16), transparent 55%),
        linear-gradient(135deg, #1f6f54 0%, #165a45 55%, #0b2b21 100%);
      box-shadow:0 14px 30px rgba(2,20,14,.18);
    }
    .btn-ghost{
      background:rgba(15,23,42,.06);
      border:1px solid rgba(15,23,42,.10);
      color:rgba(15,23,42,.78);
    }

    .ta{
      width:100%;
      min-height: 190px;
      resize:vertical;
      border-radius:16px;
      border:1px solid rgba(15,23,42,.12);
      background:rgba(15,23,42,.04);
      padding:12px 12px;
      font-family:var(--mono);
      font-size:.88rem;
      color:rgba(15,23,42,.88);
      line-height:1.35;
      outline:none;
      white-space:pre;
      overflow:auto;
    }
    .ta:focus{border-color:rgba(31,111,84,.35);box-shadow:0 0 0 4px rgba(31,111,84,.10)}
    .small{
      font-size:.82rem;
      color:rgba(15,23,42,.55);
      margin-top:8px;
    }
    details{
      border-radius:18px;
      border:1px solid rgba(15,23,42,.10);
      background:rgba(255,255,255,.80);
      padding:10px 12px;
    }
    summary{
      cursor:pointer;
      font-weight:950;
      color:rgba(15,23,42,.78);
      user-select:none;
    }
    .toast{
      position:fixed;
      right:14px;
      bottom:14px;
      background:rgba(15,23,42,.88);
      color:#fff;
      padding:10px 12px;
      border-radius:14px;
      font-weight:900;
      font-size:.85rem;
      opacity:0;
      transform: translateY(8px);
      pointer-events:none;
      transition:opacity .18s ease, transform .18s ease;
      z-index:9999;
    }
    .toast.show{
      opacity:1;
      transform: translateY(0);
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">
      <div class="head">
        <div class="brand">
          <div class="brand-left">
            <div class="logo">
              <img src="<?= esc($logoUrl) ?>" alt="Logo">
            </div>
            <div class="bt">
              <p class="app"><?= esc($appName) ?></p>
              <p class="school"><?= esc($schoolName) ?></p>
            </div>
          </div>

          <div class="top-actions">
            <span class="pill">ENV: <?= esc($envName) ?></span>
            <span class="pill">HTTP <?= esc((string)$statusCode) ?></span>
            <button class="btn btn-copy" type="button" onclick="copyFrom('ta-all')">Copy All</button>
          </div>
        </div>
      </div>

      <div class="body">
        <div class="grid">
          <!-- LEFT: Summary + Copyable blocks -->
          <div class="panel">
            <div class="badge-danger">EXCEPTION · <?= esc($exClass) ?></div>

            <h2><?= esc($title) ?></h2>

            <div class="meta" style="margin-bottom:12px;">
              <div class="k">Message</div>
              <div class="v"><?= esc($exMessage) ?></div>

              <div class="k">File</div>
              <div class="v"><?= esc($exFile) ?></div>

              <div class="k">Line</div>
              <div class="v"><?= esc((string)$exLine) ?></div>

              <div class="k">Code</div>
              <div class="v"><?= esc((string)$exCode) ?></div>

              <div class="k">Time</div>
              <div class="v"><?= esc($now) ?></div>
            </div>

            <div class="copybar">
              <a class="btn btn-primary" href="<?= base_url('/') ?>">Ke Beranda</a>
              <button class="btn btn-ghost" type="button" onclick="history.back()">Kembali</button>
              <button class="btn btn-ghost" type="button" onclick="location.reload()">Muat Ulang</button>
              <button class="btn btn-ghost" type="button" onclick="selectAll('ta-message')">Select Message</button>
              <button class="btn btn-ghost" type="button" onclick="copyFrom('ta-message')">Copy Message</button>
            </div>

            <!-- Copyable: Message -->
            <?php
              $messageBlock = "[$exClass]\n"
                . "Message: $exMessage\n"
                . "File   : $exFile\n"
                . "Line   : $exLine\n"
                . "HTTP   : $statusCode\n"
                . "Code   : $exCode\n"
                . "Time   : $now\n";
            ?>
            <textarea id="ta-message" class="ta" readonly><?= esc($messageBlock) ?></textarea>

            <div class="small">
              Tip: klik textarea lalu <b>Ctrl+A</b> → <b>Ctrl+C</b> (atau tombol Copy).
            </div>

            <div style="height:12px"></div>

            <!-- Copyable: Trace -->
            <div class="copybar" style="margin-top:2px;">
              <button class="btn btn-ghost" type="button" onclick="selectAll('ta-trace')">Select Trace</button>
              <button class="btn btn-ghost" type="button" onclick="copyFrom('ta-trace')">Copy Trace</button>
            </div>

            <?php
              // trace versi "ringkas + jelas"
              $traceCompact = '';
              if ($ex && !empty($traceArr)) {
                $lines = [];
                $i = 0;
                foreach ($traceArr as $t) {
                  $i++;
                  $f = $t['file'] ?? '';
                  $l = $t['line'] ?? '';
                  $func = '';
                  if (isset($t['class'], $t['type'], $t['function'])) {
                    $func = $t['class'] . $t['type'] . $t['function'];
                  } elseif (isset($t['function'])) {
                    $func = (string)$t['function'];
                  }
                  $args = '';
                  if (isset($t['args']) && is_array($t['args'])) {
                    // Jangan dump args penuh (bisa besar), cukup count
                    $args = ' args=' . count($t['args']);
                  }
                  $loc = trim($f . ($l ? ":$l" : ''));
                  $lines[] = sprintf("#%d %s%s%s", $i, ($loc ? $loc . ' ' : ''), $func, $args);
                }
                $traceCompact = implode("\n", $lines);
              }

              $traceFinal = $traceCompact !== ''
                ? $traceCompact
                : ($traceText ?: '(no trace available)');
            ?>
            <textarea id="ta-trace" class="ta" readonly><?= esc($traceFinal) ?></textarea>

            <div style="height:12px"></div>

            <!-- Copyable: FULL dump (All) hidden but accessible -->
            <textarea id="ta-all" class="ta" readonly style="position:absolute;left:-99999px;top:-99999px;height:1px;width:1px;opacity:0;"><?= esc($allPayloadStr) ?></textarea>
          </div>

          <!-- RIGHT: Request + Environment details -->
          <div class="panel">
            <h2>Request & Context</h2>

            <?php
              $ctx = [];
              $ctx[] = "Method     : $method";
              $ctx[] = "URL        : $currentUrl";
              $ctx[] = "IP         : $ip";
              $ctx[] = "User-Agent : $ua";
              $ctx[] = "";
              $ctx[] = "GET:";
              $ctx[] = $pretty($get) ?: '[]';
              $ctx[] = "";
              $ctx[] = "POST:";
              $ctx[] = $pretty($post) ?: '[]';
              $ctx[] = "";
              $ctx[] = "Headers (subset):";
              $ctx[] = $pretty($headersSubset) ?: '{}';
              $ctx[] = "";
              $ctx[] = "Environment:";
              $ctx[] = "ENV : $envName";
              $ctx[] = "PHP : $phpVer";
              $ctx[] = "CI  : $ciVer";

              $ctxStr = implode("\n", $ctx);
            ?>

            <div class="copybar">
              <button class="btn btn-ghost" type="button" onclick="selectAll('ta-ctx')">Select Context</button>
              <button class="btn btn-ghost" type="button" onclick="copyFrom('ta-ctx')">Copy Context</button>
            </div>

            <textarea id="ta-ctx" class="ta" readonly><?= esc($ctxStr) ?></textarea>

            <div style="height:12px"></div>

            <details>
              <summary>Catatan Keamanan (Dev Only)</summary>
              <div class="small" style="margin-top:8px;">
                Halaman ini menampilkan detail teknis (trace, request). Pastikan pada <b>production</b> kamu memakai error page umum (tanpa detail).
              </div>
            </details>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="toast" class="toast">Copied ✅</div>

  <script>
    function showToast(text) {
      const t = document.getElementById('toast');
      if (!t) return;
      t.textContent = text || 'Copied ✅';
      t.classList.add('show');
      clearTimeout(window.__sibkToastTimer);
      window.__sibkToastTimer = setTimeout(() => t.classList.remove('show'), 1300);
    }

    function selectAll(id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.focus();
      el.select();
      el.setSelectionRange(0, el.value.length);
      showToast('Selected ✅');
    }

    async function copyFrom(id) {
      const el = document.getElementById(id);
      if (!el) return;

      const text = el.value || el.textContent || '';
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(text);
          showToast('Copied ✅');
          return;
        }
      } catch (e) {
        // fallback below
      }

      // Fallback (execCommand)
      try {
        el.focus();
        el.select();
        el.setSelectionRange(0, el.value.length);
        const ok = document.execCommand('copy');
        showToast(ok ? 'Copied ✅' : 'Copy failed ❌');
      } catch (e) {
        showToast('Copy failed ❌');
      }
    }
  </script>
</body>
</html>
