<?php

/**
 * File Path: app/Views/layouts/partials/topbar.php
 *
 * Header (Mapersis-like)
 * - Navigasi/top actions berada di bar atas (bukan di dalam pill/card)
 * - Tanpa search + tanpa settings (sesuai permintaan)
 */

try {
    helper('permission');
} catch (\Throwable $e) {
    // ignore jika helper tidak ada
}

$user     = function_exists('auth_user') ? auth_user() : [];
$userRole = function_exists('auth_role') ? auth_role() : '';

// Pastikan $user array agar akses index aman
if (!is_array($user)) {
    $user = is_object($user) ? (array) $user : [];
}

// App name fallback (optional)
$__appName = function_exists('setting') ? setting('app_name', 'SIB-K', 'general') : 'SIB-K';

// Resolve dashboard/home URL by role (robust)
$__role = strtolower((string) $userRole);
$__homeUrl = base_url('/');
if (str_contains($__role, 'admin')) $__homeUrl = base_url('admin/dashboard');
elseif (str_contains($__role, 'koordinator')) $__homeUrl = base_url('koordinator/dashboard');
elseif (str_contains($__role, 'counselor') || str_contains($__role, 'guru')) $__homeUrl = base_url('counselor/dashboard');
elseif (str_contains($__role, 'homeroom') || str_contains($__role, 'wali')) $__homeUrl = base_url('homeroom/dashboard');
elseif (str_contains($__role, 'student') || str_contains($__role, 'siswa')) $__homeUrl = base_url('student/dashboard');
elseif (str_contains($__role, 'parent') || str_contains($__role, 'orang')) $__homeUrl = base_url('parent/dashboard');

// Logo resolver (optional, aman jika file tidak ada)
$__logoUrl = null;
$__logoCandidates = [
    'assets/images/logo-sm.png',
    'assets/images/logo-sm.svg',
    'assets/images/logo-dark.png',
    'assets/images/logo.png',
    'assets/images/logo.svg',
];
foreach ($__logoCandidates as $__c) {
    if (defined('FCPATH') && is_file(FCPATH . ltrim($__c, '/'))) {
        $__logoUrl = base_url($__c);
        break;
    }
}

// Avatar resolver
$__avatar     = session('profile_photo') ?: ($user['profile_photo'] ?? null);
$__avatarUrl  = $__avatar ? base_url($__avatar) : base_url('assets/images/users/avatar-silhouette.svg');

// Cache-busting (opsional)
$__avatarBust = '';
if ($__avatar && defined('FCPATH') && is_file(FCPATH . ltrim($__avatar, '/'))) {
    $__avatarBust = '?t=' . @filemtime(FCPATH . ltrim($__avatar, '/'));
}

// --- Notification data ---
$__uid             = (int) session('user_id');
$__unread          = 0;
$__items           = [];
$__urlIndex        = site_url('notifications');
$__urlMarkRead     = site_url('notifications/mark-read');
$__urlMarkAll      = site_url('notifications/mark-all-read');
$__urlCount        = site_url('notifications/count');

if ($__uid && class_exists(\App\Models\NotificationModel::class)) {
    $__model  = new \App\Models\NotificationModel();
    $__unread = (int) $__model->where('user_id', $__uid)->where('is_read', 0)->countAllResults();
    $__items  = $__model->where('user_id', $__uid)->orderBy('created_at', 'DESC')->findAll(10);
}
$__badgeZero = ($__unread <= 0) ? '1' : '0';

// CSRF cookie name (CI4 ambil dari config Security)
$__csrfCookieName = '';
try {
    $__sec = config('Security');
    $__csrfCookieName = (string) ($__sec->csrfCookieName ?? '');
} catch (\Throwable $e) {
    $__csrfCookieName = '';
}

?>
<header id="page-topbar">
  <div class="navbar-header">
    <div class="container-fluid">

      <!-- BAR HEADER (bukan pill) -->
      <div class="sibk-topbar-wrap">

        <!-- LEFT: Hamburger + Brand -->
        <div class="sibk-topbar-left">
          <button type="button"
                  class="btn btn-sm px-3 font-size-16 header-item toggle-btn waves-effect"
                  id="vertical-menu-btn"
                  aria-label="Toggle sidebar">
            <i class="fa fa-fw fa-bars"></i>
          </button>
        </div>

        <!-- RIGHT: Fullscreen + Notifications + User -->
        <div class="sibk-topbar-right">

          <!-- Fullscreen -->
          <div class="dropdown d-none d-lg-inline-block">
            <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen" aria-label="Fullscreen">
              <i class="mdi mdi-fullscreen"></i>
            </button>
          </div>

          <!-- Notifications 
          <div class="dropdown d-inline-block">
            <button type="button" class="btn header-item noti-icon"
                    id="page-header-notifications-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    aria-label="Notifikasi">
              <i class="mdi mdi-bell-outline"></i>

              <span class="badge rounded-pill bg-danger"
                    id="notification-badge"
                    data-zero="<?= esc($__badgeZero) ?>"><?= esc((string)$__unread) ?></span>
            </button>

            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                 aria-labelledby="page-header-notifications-dropdown" style="min-width:380px">
              <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0">Notifikasi</h6>
                <button class="btn btn-sm btn-link" id="notifMarkAll" type="button">Tandai semua dibaca</button>
              </div>

              <div style="max-height:420px; overflow:auto">
                <?php if (!$__items): ?>
                  <div class="p-3 text-center text-muted">Tidak ada notifikasi.</div>
                <?php else: foreach ($__items as $__n): ?>
                  <a href="<?= esc($__n['link'] ?? '#') ?>"
                     class="text-reset notification-item d-block <?= !empty($__n['is_read']) ? '' : 'bg-light' ?>"
                     data-notif-id="<?= (int)($__n['id'] ?? 0) ?>">
                    <div class="d-flex">
                      <div class="avatar-xs me-3">
                        <span class="avatar-title bg-primary bg-soft rounded-circle font-size-16">
                          <i class="mdi mdi-bell"></i>
                        </span>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="mb-1"><?= esc($__n['title'] ?? 'Notifikasi') ?></h6>
                        <?php if (!empty($__n['message'])): ?>
                          <div class="text-muted text-truncate"><?= esc($__n['message']) ?></div>
                        <?php endif; ?>
                        <p class="mb-0"><small class="text-muted"><?= esc($__n['created_at'] ?? '') ?></small></p>
                      </div>
                    </div>
                  </a>
                <?php endforeach; endif; ?>
              </div>

              <div class="p-2 border-top text-center">
                <a class="btn btn-sm btn-light w-100" href="<?= $__urlIndex ?>">Lihat semua notifikasi</a>
              </div>
            </div>
          </div>-->

          <!-- User -->
          <div class="dropdown d-inline-block">
            <button type="button" class="btn header-item waves-effect"
                    id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    aria-label="User menu">
              <img class="rounded-circle header-profile-user"
                   src="<?= $__avatarUrl . $__avatarBust ?>"
                   alt="<?= esc($user['full_name'] ?? 'User') ?>">
              <span class="d-none d-xl-inline-block ms-1"><?= esc($user['full_name'] ?? 'Pengguna') ?></span>
              <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-end">
              <div class="dropdown-header">
                <h6 class="mb-0"><?= esc($user['full_name'] ?? 'Pengguna') ?></h6>
                <small class="text-muted"><?= function_exists('format_role_badge') ? format_role_badge($userRole) : esc((string)$userRole) ?></small>
              </div>
              <div class="dropdown-divider"></div>

              <a class="dropdown-item" href="<?= base_url('profile') ?>">
                <i class="bx bx-user font-size-16 align-middle me-1"></i> Profil Saya
              </a>

              <!--<a class="dropdown-item" href="<?= base_url('messages') ?>">
                <i class="bx bx-envelope font-size-16 align-middle me-1"></i> Pesan
              </a>-->

              <div class="dropdown-divider"></div>

              <form action="<?= route_to('logout') ?>" method="post" class="px-3 my-1">
                <?= csrf_field() ?>
                <button type="submit" class="dropdown-item text-danger w-100 text-start">
                  <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Logout
                </button>
              </form>
            </div>
          </div>

        </div><!-- /right -->

      </div><!-- /sibk-topbar-wrap -->

    </div>
  </div>

  <script>
  (function(){
      const uid = <?= (int)$__uid ?>;

      // Jika belum login, jangan polling / jangan kirim request notifikasi
      if (!uid) return;

      // CSRF
      const csrfName = '<?= csrf_token() ?>';
      let csrfHash   = '<?= csrf_hash() ?>';

      // Nama cookie CSRF dari config Security (bisa kosong jika mode session)
      const csrfCookieName = <?= json_encode($__csrfCookieName) ?>;

      function getCookie(name) {
          if (!name) return '';
          const safe = name.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
          const v = document.cookie.match('(^|;)\\s*' + safe + '\\s*=\\s*([^;]+)');
          return v ? decodeURIComponent(v.pop()) : '';
      }

      function csrfPayload() {
          const cookieVal = getCookie(csrfCookieName);
          const tokenVal = cookieVal || csrfHash;
          const o = {};
          o[csrfName] = tokenVal;
          return o;
      }

      // Guard: kalau suatu saat elemen lonceng berubah jadi <a>, cegah navigasi.
      const notifBtn = document.getElementById('page-header-notifications-dropdown');
      notifBtn?.addEventListener('click', function(e){
          if (notifBtn.tagName && notifBtn.tagName.toLowerCase() === 'a') e.preventDefault();
      });

      // Klik item notifikasi: mark as read (tanpa mengganggu navigasi ke link)
      document.addEventListener('click', function(e){
          const a = e.target.closest('a.notification-item[data-notif-id]');
          if (!a) return;

          const id = a.getAttribute('data-notif-id');
          if (!id) return;

          // Optimistic UI
          a.classList.remove('bg-light');

          const badge = document.getElementById('notification-badge');
          if (badge) {
              const n = parseInt(badge.textContent || '0', 10);
              const next = Math.max(0, (isNaN(n) ? 0 : n) - 1);
              badge.textContent = String(next);
              badge.setAttribute('data-zero', next <= 0 ? '1' : '0');
          }

          fetch('<?= rtrim($__urlMarkRead, "/") ?>/' + encodeURIComponent(id), {
              method:'POST',
              headers:{
                  'X-Requested-With':'XMLHttpRequest',
                  'Content-Type':'application/x-www-form-urlencoded'
              },
              body: new URLSearchParams(csrfPayload()),
              keepalive: true
          }).catch(()=>{});
      });

      // Mark all as read
      document.getElementById('notifMarkAll')?.addEventListener('click', function(){
          fetch('<?= $__urlMarkAll ?>', {
              method:'POST',
              headers:{
                  'X-Requested-With':'XMLHttpRequest',
                  'Content-Type':'application/x-www-form-urlencoded'
              },
              body: new URLSearchParams(csrfPayload()),
              keepalive: true
          }).then(()=>location.reload()).catch(()=>location.reload());
      });

      // Polling unread count (30s), stop ketika tab tidak aktif
      async function refreshCount(){
          if (document.visibilityState !== 'visible') return;

          try {
              const r = await fetch('<?= $__urlCount ?>', {
                  headers: { 'X-Requested-With': 'XMLHttpRequest' },
                  cache: 'no-store'
              });
              if (!r.ok) return;

              const j = await r.json();
              const badge = document.getElementById('notification-badge');
              if (!badge) return;

              const c = (j && typeof j.count !== 'undefined') ? parseInt(j.count, 10) : 0;
              const safe = isNaN(c) ? 0 : c;
              badge.textContent = String(safe);
              badge.setAttribute('data-zero', (safe <= 0) ? '1' : '0');
          } catch (e) {}
      }

      // Jalankan sekali saat load, lalu interval
      refreshCount();
      setInterval(refreshCount, 30000);
  })();
  </script>
</header>
