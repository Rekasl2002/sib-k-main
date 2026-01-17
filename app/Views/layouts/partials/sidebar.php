<?php

/**
 * File Path: app/Views/layouts/partials/sidebar.php
 *
 * Sidebar Menu
 * Menu navigasi dinamis berbasis role & permission (RBAC)
 */

try {
    // url_is() ada di url helper. auth_user/auth_role biasanya di auth helper.
    helper(['permission', 'auth', 'url']);
} catch (\Throwable $e) {
    // Jika salah satu helper tidak ada, kita tetap jalan dengan fallback.
}

$session = session();

/**
 * ------------------------------------------------------------
 * Toggle fitur opsional (biar rapi, tidak pakai komentar “liar”)
 * ------------------------------------------------------------
 * Kalau nanti fitur sudah siap, tinggal ubah ke true.
 */
$__enableAssessments = false;
$__enableCareerInfo  = false;
$__enableCommonMenu  = false;

// Ambil user & role (aman)
$user = function_exists('auth_user')
    ? (auth_user() ?: [])
    : (is_array($session->get('auth_user')) ? $session->get('auth_user') : []);

$__roleId   = (int) ($session->get('role_id') ?? 0);
$__roleName = (string) ($session->get('role_name') ?? '');

// Fallback role name dari auth_role() jika tersedia
if ($__roleName === '' && function_exists('auth_role')) {
    $__roleName = (string) auth_role();
}

$__roleNameNorm = strtolower(trim($__roleName));

// Mapping role_id → role name (fallback)
if ($__roleNameNorm === '' && $__roleId) {
    $__roleNameNorm = match ($__roleId) {
        1 => 'admin',
        2 => 'koordinator bk',
        3 => 'guru bk',
        4 => 'wali kelas',
        5 => 'siswa',
        6 => 'orang tua',
        default => 'pengguna',
    };
}

// Label role untuk ditampilkan
$userRole = $__roleName !== '' ? $__roleName : ucfirst($__roleNameNorm ?: 'Pengguna');

// Branding (aman: kalau setting() tidak ada, fallback)
$__appName    = function_exists('setting') ? setting('app_name', 'SIB-K', 'general') : 'SIB-K';
$__schoolName = env('school.name', 'MA Persis 31 Banjaran');

$__logoPath = function_exists('setting') ? setting('logo_path', null, 'branding') : null;
$__logoUrl  = $__logoPath ? base_url($__logoPath) : base_url('assets/images/logo-sm.png');

// Cache-busting logo agar kalau ganti logo langsung update
$__logoBust = '';
if ($__logoPath && is_file(FCPATH . ltrim($__logoPath, '/'))) {
    $__logoBust = '?t=' . @filemtime(FCPATH . ltrim($__logoPath, '/'));
}

// Avatar resolver: utamakan session -> fallback DB -> default
$__avatar    = $session->get('profile_photo') ?: ($user['profile_photo'] ?? null);
$__avatarUrl = $__avatar ? base_url($__avatar) : base_url('assets/images/users/default-avatar.svg');

// Cache-busting avatar
$__avatarBust = '';
if ($__avatar && is_file(FCPATH . ltrim($__avatar, '/'))) {
    $__avatarBust = '?t=' . @filemtime(FCPATH . ltrim($__avatar, '/'));
}

// Nama lengkap
$__fullName = $user['full_name'] ?? $user['name'] ?? 'Pengguna';

/**
 * ------------------------------------------------------------
 * Permission helpers (fallback jika has_permission tidak ada)
 * ------------------------------------------------------------
 */
$__sessionPerms = $session->get('auth_permissions');
if (!is_array($__sessionPerms)) {
    $__sessionPerms = $session->get('permissions');
}
if (!is_array($__sessionPerms)) {
    $__sessionPerms = [];
}

$__can = function (string $perm) use ($__sessionPerms): bool {
    if (function_exists('has_permission')) {
        return (bool) has_permission($perm);
    }
    return in_array($perm, $__sessionPerms, true);
};

$__canAny = function (array $perms) use ($__can): bool {
    foreach ($perms as $p) {
        if ($__can($p)) return true;
    }
    return false;
};

// Role check helper (cek nama + fallback id)
$__isRoleAny = function (array $names, ?int $roleId = null) use ($__roleNameNorm, $__roleId): bool {
    if ($roleId !== null && $roleId === (int)$__roleId) {
        return true;
    }
    foreach ($names as $name) {
        if ($__roleNameNorm === strtolower(trim((string)$name))) return true;
    }
    return false;
};

// Flag role
$__isAdmin      = $__isRoleAny(['admin', 'administrator'], 1);
$__isKoordinator= $__isRoleAny(['koordinator', 'koordinator bk'], 2);
$__isCounselor  = $__isRoleAny(['counselor', 'guru bk'], 3);
$__isHomeroom   = $__isRoleAny(['homeroom', 'wali kelas'], 4);
$__isStudent    = $__isRoleAny(['student', 'siswa'], 5);
$__isParent     = $__isRoleAny(['parent', 'orang tua'], 6);

// Active helper
$__active = function (string $pattern): string {
    return function_exists('url_is') && url_is($pattern) ? ' active' : '';
};
$__activeAny = function (array $patterns) use ($__active): string {
    foreach ($patterns as $p) {
        if ($__active($p) !== '') return ' active';
    }
    return '';
};

// mm-active untuk parent menu (metismenu)
$__mm = function (array $patterns): string {
    if (!function_exists('url_is')) return '';
    foreach ($patterns as $p) {
        if (url_is($p)) return ' mm-active';
    }
    return '';
};

// Inline fallback gradient (boleh diganti via CSS)
$__sidebarBg = 'background:linear-gradient(180deg,#0f3a2c 0%, #0b2b21 55%, #071f18 100%);';

?>
<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu sibk-sidebar" style="<?= $__sidebarBg ?>">
  <div class="h-100" data-simplebar>

    <!-- Brand header -->
    <div class="sibk-sidebar-head">
      <a href="<?= base_url('/') ?>" class="sibk-sidebar-brand" title="<?= esc($__appName) ?>">
        <img src="<?= $__logoUrl . $__logoBust ?>"
             alt="Logo"
             class="sibk-sidebar-logo"
             style="height:34px;width:34px;max-height:34px;max-width:34px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,.92);">
        <div class="sibk-brand-stack" style="min-width:0;">
          <div class="sibk-brand-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= esc($__appName) ?>
          </div>
          <div class="sibk-brand-sub" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= esc($__schoolName) ?>
          </div>
        </div>
      </a>
    </div>

    <!-- User -->
    <div class="user-wid text-center py-4">
      <div class="user-img">
        <img src="<?= $__avatarUrl . $__avatarBust ?>"
             alt="<?= esc($__fullName) ?>"
             class="avatar-md mx-auto rounded-circle">
      </div>

      <div class="mt-3">
        <a href="<?= base_url('profile') ?>" class="text-body fw-medium font-size-16">
          <?= esc($__fullName) ?>
        </a>
        <p class="text-muted mt-1 mb-0 font-size-13">
          <?= esc($userRole ?: 'Pengguna') ?>
        </p>
      </div>
    </div>

    <!--- Sidemenu -->
    <div id="sidebar-menu">
      <ul class="metismenu list-unstyled" id="side-menu">

        <?php if ($__isAdmin): ?>
          <!-- ADMIN MENU -->
          <li class="menu-title">Menu Admin</li>

          <li>
            <a href="<?= base_url('admin/dashboard') ?>" class="waves-effect<?= $__activeAny(['admin/dashboard', 'admin']) ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <?php if ($__canAny(['manage_users','manage_roles'])): ?>
          <li class="<?= $__mm(['admin/users*','admin/roles*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-account-group"></i>
              <span>Pengguna</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__can('manage_users')): ?>
                <li><a href="<?= base_url('admin/users') ?>">Manajemen Pengguna</a></li>
              <?php endif; ?>
              <?php if ($__can('manage_roles')): ?>
                <li><a href="<?= base_url('admin/roles') ?>">Manajemen Peran</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['manage_academic_data','view_all_students'])): ?>
          <li class="<?= $__mm(['admin/academic-years*','admin/classes*','admin/students*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-school"></i>
              <span>Data Akademik</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__can('manage_academic_data')): ?>
                <li><a href="<?= base_url('admin/academic-years') ?>">Manajemen Tahun Ajaran</a></li>
                <li><a href="<?= base_url('admin/classes') ?>">Manajemen Kelas</a></li>
              <?php endif; ?>
              <?php if ($__can('view_all_students')): ?>
                <li><a href="<?= base_url('admin/students') ?>">Manajemen Siswa</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <li>
            <a href="<?= base_url('admin/settings') ?>" class="waves-effect<?= $__active('admin/settings*') ?>">
              <i class="mdi mdi-cogs"></i>
              <span>Pengaturan</span>
            </a>
          </li>

        <?php elseif ($__isKoordinator): ?>
          <!-- KOORDINATOR BK MENU -->
          <li class="menu-title">Menu Koordinator</li>

          <li>
            <a href="<?= base_url('koordinator/dashboard') ?>" class="waves-effect<?= $__activeAny(['koordinator/dashboard','koordinator']) ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <?php if ($__can('view_all_students')): ?>
          <li>
            <a href="<?= base_url('koordinator/students') ?>" class="waves-effect<?= $__active('koordinator/students*') ?>">
              <i class="mdi mdi-account-group"></i>
              <span>Manajemen Siswa</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__can('manage_users')): ?>
          <li>
            <a href="<?= base_url('koordinator/users') ?>" class="waves-effect<?= $__active('koordinator/users*') ?>">
              <i class="mdi mdi-account-multiple"></i>
              <span>Manajemen Pengguna</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__canAny([
              'view_counseling_sessions','manage_counseling_sessions',
              'view_violations','manage_violations',
              'manage_assessments','take_assessments'
          ])): ?>
          <li class="<?= $__mm(['koordinator/sessions*','koordinator/cases*','koordinator/assessments*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-clipboard-text"></i>
              <span>Layanan BK</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__canAny(['view_counseling_sessions','manage_counseling_sessions'])): ?>
                <li><a href="<?= base_url('koordinator/sessions') ?>">Sesi Konseling</a></li>
              <?php endif; ?>
              <?php if ($__canAny(['view_violations','manage_violations'])): ?>
                <li><a href="<?= base_url('koordinator/cases') ?>">Kasus & Pelanggaran</a></li>
              <?php endif; ?>

              <?php if ($__enableAssessments && $__canAny(['manage_assessments','take_assessments'])): ?>
                <li><a href="<?= base_url('koordinator/assessments') ?>">Asesmen</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__enableCareerInfo && $__canAny(['manage_career_info','view_career_info'])): ?>
          <li>
            <a href="<?= base_url('koordinator/career-info') ?>" class="waves-effect<?= $__active('koordinator/career-info*') ?>">
              <i class="mdi mdi-school-outline"></i>
              <span>Info Karir/Kuliah</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['view_reports','generate_reports'])): ?>
          <li>
            <a href="<?= base_url('koordinator/reports') ?>" class="waves-effect<?= $__active('koordinator/reports*') ?>">
              <i class="mdi mdi-file-chart"></i>
              <span>Laporan</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isCounselor): ?>
          <!-- GURU BK MENU -->
          <li class="menu-title">Menu Guru BK</li>

          <li>
            <a href="<?= base_url('counselor/dashboard') ?>" class="waves-effect<?= $__activeAny(['counselor/dashboard','counselor']) ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <?php if ($__can('view_all_students')): ?>
          <li>
            <a href="<?= base_url('counselor/students') ?>" class="waves-effect<?= $__active('counselor/students*') ?>">
              <i class="mdi mdi-account-group"></i>
              <span>Siswa Binaan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['view_counseling_sessions','manage_counseling_sessions','schedule_counseling'])): ?>
          <li class="<?= $__mm(['counselor/sessions*','counselor/schedule*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-calendar-check"></i>
              <span>Konseling</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__canAny(['view_counseling_sessions','manage_counseling_sessions'])): ?>
                <li><a href="<?= base_url('counselor/sessions') ?>">Sesi Konseling</a></li>
              <?php endif; ?>
              <?php if ($__can('schedule_counseling')): ?>
                <li><a href="<?= base_url('counselor/schedule') ?>">Kalender</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['view_violations','manage_violations'])): ?>
          <li>
            <a href="<?= base_url('counselor/cases') ?>" class="waves-effect<?= $__active('counselor/cases*') ?>">
              <i class="mdi mdi-alert-circle"></i>
              <span>Kasus & Pelanggaran</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__enableAssessments && $__canAny(['manage_assessments','take_assessments'])): ?>
          <li>
            <a href="<?= base_url('counselor/assessments') ?>" class="waves-effect<?= $__active('counselor/assessments*') ?>">
              <i class="mdi mdi-clipboard-check"></i>
              <span>Asesmen</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__enableCareerInfo && $__canAny(['manage_career_info','view_career_info'])): ?>
          <li>
            <a href="<?= base_url('counselor/career-info') ?>" class="waves-effect<?= $__active('counselor/career-info*') ?>">
              <i class="mdi mdi-school-outline"></i>
              <span>Info Karir/Kuliah</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['view_reports','generate_reports'])): ?>
          <li>
            <a href="<?= base_url('counselor/reports') ?>" class="waves-effect<?= $__active('counselor/reports*') ?>">
              <i class="mdi mdi-file-chart"></i>
              <span>Laporan</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isHomeroom): ?>
          <!-- WALI KELAS MENU -->
          <li class="menu-title">Menu Wali Kelas</li>

          <li>
            <a href="<?= base_url('homeroom/dashboard') ?>" class="waves-effect<?= $__activeAny(['homeroom/dashboard','homeroom']) ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="<?= base_url('homeroom/my-class') ?>" class="waves-effect<?= $__active('homeroom/my-class*') ?>">
              <i class="mdi mdi-google-classroom"></i>
              <span>Kelas Binaan</span>
            </a>
          </li>

          <?php if ($__can('schedule_counseling')): ?>
          <li>
            <a href="<?= base_url('homeroom/sessions') ?>" class="waves-effect<?= $__active('homeroom/sessions*') ?>">
              <i class="mdi mdi-calendar-check"></i>
              <span>Sesi Konseling</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__can('view_violations')): ?>
          <li>
            <a href="<?= base_url('homeroom/violations') ?>" class="waves-effect<?= $__active('homeroom/violations*') ?>">
              <i class="mdi mdi-alert-circle"></i>
              <span>Kasus & Pelanggaran</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__enableCareerInfo && $__can('view_career_info')): ?>
          <li>
            <a href="<?= base_url('homeroom/career-info') ?>" class="waves-effect<?= $__active('homeroom/career-info*') ?>">
              <i class="mdi mdi-briefcase-outline"></i>
              <span>Info Karir &amp; Kuliah</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__can('view_reports')): ?>
          <li>
            <a href="<?= base_url('homeroom/reports') ?>" class="waves-effect<?= $__active('homeroom/reports*') ?>">
              <i class="mdi mdi-file-chart"></i>
              <span>Laporan</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isStudent): ?>
          <!-- SISWA MENU -->
          <li class="menu-title">Menu Siswa</li>

          <li>
            <a href="<?= base_url('student/dashboard') ?>" class="waves-effect<?= $__activeAny(['student/dashboard','student']) ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="<?= base_url('student/profile') ?>" class="waves-effect<?= $__active('student/profile*') ?>">
              <i class="mdi mdi-account-circle"></i>
              <span>Profil Saya</span>
            </a>
          </li>

          <li>
            <a href="<?= base_url('student/staff') ?>" class="waves-effect<?= $__active('student/staff*') ?>">
              <i class="mdi mdi-account-tie"></i>
              <span>Info Guru</span>
            </a>
          </li>

          <?php if ($__enableAssessments && $__canAny(['take_assessments','manage_assessments'])): ?>
          <li>
            <a href="<?= base_url('student/assessments') ?>" class="waves-effect<?= $__active('student/assessments*') ?>">
              <i class="mdi mdi-clipboard-check"></i>
              <span>Asesmen</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__can('schedule_counseling')): ?>
          <li>
            <a href="<?= base_url('student/schedule') ?>" class="waves-effect<?= $__active('student/schedule*') ?>">
              <i class="mdi mdi-calendar"></i>
              <span>Sesi Konseling</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__can('view_violations')): ?>
          <li>
            <a href="<?= base_url('student/violations') ?>" class="waves-effect<?= $__active('student/violations*') ?>">
              <i class="mdi mdi-alert-circle"></i>
              <span>Kasus & Pelanggaran</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__enableCareerInfo): ?>
          <li>
            <a href="<?= base_url('student/career') ?>" class="waves-effect<?= $__active('student/career*') ?>">
              <i class="mdi mdi-school-outline"></i>
              <span>Info Karir/Kuliah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isParent): ?>
          <!-- ORANG TUA MENU -->
          <li class="menu-title">Menu Orang Tua</li>

          <li>
            <a href="<?= base_url('parent/dashboard') ?>" class="waves-effect<?= $__activeAny(['parent/dashboard','parent']) ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="<?= base_url('parent/reports/children') ?>" class="waves-effect<?= $__active('parent/reports*') ?>">
              <i class="mdi mdi-file-chart"></i>
              <span>Laporan Anak</span>
            </a>
          </li>

          <?php if ($__enableCareerInfo): ?>
          <li>
            <a href="<?= base_url('parent/career') ?>" class="waves-effect<?= $__active('parent/career*') ?>">
              <i class="mdi mdi-school-outline"></i>
              <span>Info Karir/Kuliah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php endif; ?>

        <?php if ($__enableCommonMenu): ?>
          <li class="menu-title">Menu Umum</li>

          <li>
            <a href="<?= base_url('messages') ?>" class="waves-effect<?= $__active('messages*') ?>">
              <i class="mdi mdi-email"></i>
              <span>Pesan</span>
            </a>
          </li>

          <li>
            <a href="<?= base_url('notifications') ?>" class="waves-effect<?= $__active('notifications*') ?>">
              <i class="mdi mdi-bell"></i>
              <span>Notifikasi</span>
            </a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
    <!-- /#sidebar-menu -->
  </div>
</div>
<!-- Left Sidebar End -->
