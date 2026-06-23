<?php

/**
 * File Path: app/Views/layouts/partials/sidebar.php
 *
 * Sidebar Menu
 * Menu navigasi dinamis berbasis role & permission (RBAC)
 */

try {
    // url_is() ada di url helper. auth_user/auth_role biasanya di auth helper.
    helper(['permission', 'auth', 'url', 'simulation_access', 'app']);
} catch (\Throwable $e) {
    // Jika salah satu helper tidak ada, kita tetap jalan dengan fallback.
}

$session = session();

/**
 * ------------------------------------------------------------
 * Toggle fitur opsional (biar rapi, tidak pakai komentar "liar")
 * ------------------------------------------------------------
 * Kalau nanti fitur sudah siap, tinggal ubah ke true.
 */
$__enableAssessments        = true;
$__enableCareerInfo         = true;
$__enableCommonMenu         = true;

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
if ($__logoPath && defined('FCPATH') && is_file(FCPATH . ltrim($__logoPath, '/'))) {
    $__logoBust = '?t=' . @filemtime(FCPATH . ltrim($__logoPath, '/'));
}

// Avatar resolver: utamakan session -> fallback DB -> default
$__avatar    = $session->get('profile_photo') ?: ($user['profile_photo'] ?? null);
$__avatarUrl = $__avatar ? base_url($__avatar) : base_url('assets/images/users/default-avatar.svg');

// Cache-busting avatar
$__avatarBust = '';
if ($__avatar && defined('FCPATH') && is_file(FCPATH . ltrim($__avatar, '/'))) {
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
$__isAdmin       = $__isRoleAny(['admin', 'administrator'], 1);
$__isKoordinator = $__isRoleAny(['koordinator', 'koordinator bk'], 2);
$__isCounselor   = $__isRoleAny(['counselor', 'guru bk'], 3);
$__isHomeroom    = $__isRoleAny(['homeroom', 'wali kelas'], 4);
$__isStudent     = $__isRoleAny(['student', 'siswa'], 5);
$__isParent      = $__isRoleAny(['parent', 'orang tua'], 6);
$__rolePrefix = match (true) {
    $__isAdmin => 'admin',
    $__isKoordinator => 'koordinator',
    $__isCounselor => 'counselor',
    $__isHomeroom => 'homeroom',
    $__isStudent => 'student',
    $__isParent => 'parent',
    default => 'dashboard',
};

$__canAccessDemoSuite = false;
try {
    $__canAccessDemoSuite = function_exists('can_access_simulation_suite')
        ? can_access_simulation_suite()
        : $__isAdmin;
} catch (\Throwable $e) {
    $__canAccessDemoSuite = $__isAdmin;
}

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

/**
 * ------------------------------------------------------------
 * Permission alias umum (supaya konsisten dengan Routes.php)
 * ------------------------------------------------------------
 */
$__permViewDashboard             = $__can('view_dashboard');
$__permManageUsers               = $__can('manage_users');
$__permManageRoles               = $__can('manage_roles');
$__permManageAcademicData        = $__can('manage_academic_data');
$__permViewAllStudents           = $__can('view_all_students');
$__permImportExport              = $__can('import_export_data');
$__permManageSettings            = $__can('manage_settings');

$__permViewCounselingSessions    = $__can('view_counseling_sessions');
$__permManageCounselingSessions  = $__can('manage_counseling_sessions');

$__permViewCounselingSchedule    = $__canAny(['view_counseling_schedule', 'schedule_counseling']);

$__permViewReports               = $__can('view_reports');
$__permViewReportsAggregate      = $__canAny(['view_reports_aggregate']);
$__permViewReportsIndividual     = $__canAny(['view_reports_individual']);
$__permGenerateReportsAggregate  = $__can('generate_reports_aggregate');
$__permGenerateReportsIndividual = $__can('generate_reports_individual');

$__permSendMessages              = $__can('send_messages');

$__permTakeAssessments           = $__can('take_assessments');
$__permManageAssessments         = $__can('manage_assessments');

$__permViewCareerInfo            = $__can('view_career_info');
$__permManageCareerInfo          = $__can('manage_career_info');

$__permViewStudentPortfolio      = $__can('view_student_portfolio');

$__permViewStaffInfo             = $__can('view_staff_info');

$__permViewBkServices            = $__can('view_bk_services');
$__permManageBkServices          = $__can('manage_bk_services');
$__permSubmitConsultations       = $__can('submit_consultation_complaints');
$__permReviewConsultations       = $__canAny(['review_consultation_complaints', 'manage_consultation_complaints']);
$__permViewBkAssignments         = $__can('view_bk_assignments');
$__permManageBkAssignments       = $__can('manage_bk_assignments');
$__permViewBkReports             = $__can('view_bk_reports');

// Sakelar fitur Konsultasi & Pengaduan dari Pengaturan Aplikasi Admin.
$__canUseConsultation = function () use ($__roleName, $__roleNameNorm, $__permSubmitConsultations): bool {
    if (function_exists('consultation_role_can_view')) {
        return consultation_role_can_view($__roleName !== '' ? $__roleName : $__roleNameNorm);
    }
    return $__permSubmitConsultations;
};


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

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('admin/dashboard') ?>" class="waves-effect<?= $__active('admin/dashboard*') ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['manage_users', 'manage_roles'])): ?>
          <li class="<?= $__mm(['admin/users*', 'admin/roles*', 'admin/password-reset-requests*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-account-group"></i>
              <span>Pengguna</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permManageUsers): ?>
                <li><a href="<?= base_url('admin/users') ?>" class="<?= $__active('admin/users*') ? 'active' : '' ?>">Kelola Pengguna</a></li>
                <li><a href="<?= base_url('admin/password-reset-requests') ?>" class="<?= $__active('admin/password-reset-requests*') ? 'active' : '' ?>">Permintaan Reset Password</a></li>
              <?php endif; ?>
              <?php if ($__permManageRoles): ?>
                <li><a href="<?= base_url('admin/roles') ?>" class="<?= $__active('admin/roles*') ? 'active' : '' ?>">Kelola Peran</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['manage_academic_data', 'manage_users', 'import_export_data'])): ?>
          <li class="<?= $__mm(['admin/academic-years*', 'admin/classes*', 'admin/students*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-school"></i>
              <span>Data Sekolah</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permManageAcademicData): ?>
                <li><a href="<?= base_url('admin/academic-years') ?>" class="<?= $__active('admin/academic-years*') ? 'active' : '' ?>">Kelola Tahun Ajaran</a></li>
                <li><a href="<?= base_url('admin/classes') ?>" class="<?= $__active('admin/classes*') ? 'active' : '' ?>">Kelola Kelas</a></li>
              <?php endif; ?>
              <?php if ($__permManageUsers): ?>
                <li><a href="<?= base_url('admin/students') ?>" class="<?= $__active('admin/students*') ? 'active' : '' ?>">Kelola Siswa</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <li class="menu-title">Lainnya</li>

          <?php if ($__permSendMessages): ?>
          <li>
            <a href="<?= base_url('admin/messages') ?>" class="waves-effect<?= $__active('admin/messages*') ?>">
              <i class="mdi mdi-email"></i>
              <span>Pesan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('admin/notifications') ?>" class="waves-effect<?= $__active('admin/notifications*') ?>">
              <i class="mdi mdi-bell"></i>
              <span>Notifikasi</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permManageSettings): ?>
          <li>
            <a href="<?= base_url('admin/settings') ?>" class="waves-effect<?= $__active('admin/settings*') ?>">
              <i class="mdi mdi-cogs"></i>
              <span>Pengaturan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('admin/trash') ?>" class="waves-effect<?= $__active('admin/trash*') ?>">
              <i class="mdi mdi-trash-can-outline"></i>
              <span>Tempat Sampah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isKoordinator): ?>
          <!-- KOORDINATOR BK MENU -->
          <li class="menu-title">Menu Koordinator</li>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('koordinator/dashboard') ?>" class="waves-effect<?= $__active('koordinator/dashboard*') ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permManageBkAssignments || $__permViewBkAssignments): ?>
          <li>
            <a href="<?= base_url('koordinator/assignments') ?>" class="waves-effect<?= $__active('koordinator/assignments*') ?>">
              <i class="mdi mdi-clipboard-check-outline"></i>
              <span>Penugasan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permManageUsers): ?>
          <li class="<?= $__mm(['koordinator/students*', 'koordinator/users*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-account-cog"></i>
              <span>Kelola</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <li><a href="<?= base_url('koordinator/students') ?>" class="<?= $__active('koordinator/students*') ? 'active' : '' ?>">Kelola Siswa</a></li>
              <li><a href="<?= base_url('koordinator/users') ?>" class="<?= $__active('koordinator/users*') ? 'active' : '' ?>">Kelola Pengguna</a></li>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['view_bk_services', 'manage_bk_services', 'manage_assessments', 'take_assessments'])): ?>
          <li class="<?= $__mm([
              'koordinator/guidance*', 'koordinator/counseling*',
              'koordinator/parent-collaborations*', 'koordinator/home-visits*',
              'koordinator/case-conferences*', 'koordinator/assessments*'
          ]) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-clipboard-text"></i>
              <span>Catatan BK</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permViewBkServices || $__permManageBkServices): ?>
                <li><a href="<?= base_url('koordinator/guidance') ?>" class="<?= $__active('koordinator/guidance*') ? 'active' : '' ?>">Bimbingan</a></li>
                <li><a href="<?= base_url('koordinator/counseling') ?>" class="<?= $__active('koordinator/counseling*') ? 'active' : '' ?>">Konseling</a></li>
                <li><a href="<?= base_url('koordinator/parent-collaborations') ?>" class="<?= $__active('koordinator/parent-collaborations*') ? 'active' : '' ?>">Kolaborasi Orang Tua</a></li>
                <li><a href="<?= base_url('koordinator/home-visits') ?>" class="<?= $__active('koordinator/home-visits*') ? 'active' : '' ?>">Kunjungan Rumah</a></li>
                <li><a href="<?= base_url('koordinator/case-conferences') ?>" class="<?= $__active('koordinator/case-conferences*') ? 'active' : '' ?>">Konferensi Kasus</a></li>
              <?php endif; ?>
              <?php if ($__enableAssessments && $__canAny(['manage_assessments', 'take_assessments'])): ?>
                <li><a href="<?= base_url('koordinator/assessments') ?>" class="<?= $__active('koordinator/assessments*') ? 'active' : '' ?>">Asesmen</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__permReviewConsultations || ($__enableCareerInfo && $__canAny(['manage_career_info', 'view_career_info']))): ?>
          <li class="<?= $__mm(['koordinator/consultations*', 'koordinator/career-info*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-shape-plus"></i>
              <span>Layanan BK Lainnya</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permReviewConsultations): ?>
                <li><a href="<?= base_url('koordinator/consultations') ?>" class="<?= $__active('koordinator/consultations*') ? 'active' : '' ?>">Konsultasi & Pengaduan</a></li>
              <?php endif; ?>
              <?php if ($__enableCareerInfo && $__canAny(['manage_career_info', 'view_career_info'])): ?>
                <li><a href="<?= base_url('koordinator/career-info') ?>" class="<?= $__active('koordinator/career-info*') ? 'active' : '' ?>">Info Karier dan Studi Lanjut</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__permViewReportsAggregate): ?>
          <li>
            <a href="<?= base_url('koordinator/reports') ?>" class="waves-effect<?= $__active('koordinator/reports*') ?>">
              <i class="mdi mdi-file-chart"></i>
              <span>Laporan</span>
            </a>
          </li>
          <?php endif; ?>

          <li class="menu-title">Lainnya</li>

          <?php if ($__permSendMessages): ?>
          <li>
            <a href="<?= base_url('koordinator/messages') ?>" class="waves-effect<?= $__active('koordinator/messages*') ?>">
              <i class="mdi mdi-email"></i>
              <span>Pesan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('koordinator/notifications') ?>" class="waves-effect<?= $__active('koordinator/notifications*') ?>">
              <i class="mdi mdi-bell"></i>
              <span>Notifikasi</span>
            </a>
          </li>
          <li>
            <a href="<?= base_url('koordinator/trash') ?>" class="waves-effect<?= $__active('koordinator/trash*') ?>">
              <i class="mdi mdi-trash-can-outline"></i>
              <span>Tempat Sampah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isCounselor): ?>
          <!-- GURU BK MENU -->
          <li class="menu-title">Menu Guru BK</li>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('counselor/dashboard') ?>" class="waves-effect<?= $__active('counselor/dashboard*') ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewBkAssignments || $__permViewAllStudents): ?>
          <li class="<?= $__mm(['counselor/assignments*', 'counselor/students*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-account-check"></i>
              <span>Tugas & Siswa</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permViewBkAssignments): ?>
                <li><a href="<?= base_url('counselor/assignments') ?>" class="<?= $__active('counselor/assignments*') ? 'active' : '' ?>">Tugas yang Diberikan</a></li>
              <?php endif; ?>
              <?php if ($__permViewAllStudents): ?>
                <li><a href="<?= base_url('counselor/students') ?>" class="<?= $__active('counselor/students*') ? 'active' : '' ?>">Siswa Binaan</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__canAny(['view_bk_services', 'manage_bk_services', 'manage_assessments', 'take_assessments'])): ?>
          <li class="<?= $__mm([
              'counselor/guidance*', 'counselor/counseling*',
              'counselor/parent-collaborations*', 'counselor/home-visits*',
              'counselor/case-conferences*', 'counselor/assessments*'
          ]) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-clipboard-text"></i>
              <span>Catatan BK</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permViewBkServices || $__permManageBkServices): ?>
                <li><a href="<?= base_url('counselor/guidance') ?>" class="<?= $__active('counselor/guidance*') ? 'active' : '' ?>">Bimbingan</a></li>
                <li><a href="<?= base_url('counselor/counseling') ?>" class="<?= $__active('counselor/counseling*') ? 'active' : '' ?>">Konseling</a></li>
                <li><a href="<?= base_url('counselor/parent-collaborations') ?>" class="<?= $__active('counselor/parent-collaborations*') ? 'active' : '' ?>">Kolaborasi Orang Tua</a></li>
                <li><a href="<?= base_url('counselor/home-visits') ?>" class="<?= $__active('counselor/home-visits*') ? 'active' : '' ?>">Kunjungan Rumah</a></li>
                <li><a href="<?= base_url('counselor/case-conferences') ?>" class="<?= $__active('counselor/case-conferences*') ? 'active' : '' ?>">Konferensi Kasus</a></li>
              <?php endif; ?>
              <?php if ($__enableAssessments && $__canAny(['manage_assessments', 'take_assessments'])): ?>
                <li><a href="<?= base_url('counselor/assessments') ?>" class="<?= $__active('counselor/assessments*') ? 'active' : '' ?>">Asesmen</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if (($__permReviewConsultations || $__permSubmitConsultations) || ($__enableCareerInfo && $__canAny(['manage_career_info', 'view_career_info']))): ?>
          <li class="<?= $__mm(['counselor/consultations*', 'counselor/career-info*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-shape-plus"></i>
              <span>Layanan BK Lainnya</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permReviewConsultations || $__permSubmitConsultations): ?>
                <li><a href="<?= base_url('counselor/consultations') ?>" class="<?= $__active('counselor/consultations*') ? 'active' : '' ?>">Konsultasi & Pengaduan</a></li>
              <?php endif; ?>
              <?php if ($__enableCareerInfo && $__canAny(['manage_career_info', 'view_career_info'])): ?>
                <li><a href="<?= base_url('counselor/career-info') ?>" class="<?= $__active('counselor/career-info*') ? 'active' : '' ?>">Info Karier dan Studi Lanjut</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__permViewReportsIndividual): ?>
          <li>
            <a href="<?= base_url('counselor/reports') ?>" class="waves-effect<?= $__active('counselor/reports*') ?>">
              <i class="mdi mdi-file-chart"></i>
              <span>Laporan</span>
            </a>
          </li>
          <?php endif; ?>

          <li class="menu-title">Lainnya</li>

          <?php if ($__permSendMessages): ?>
          <li>
            <a href="<?= base_url('counselor/messages') ?>" class="waves-effect<?= $__active('counselor/messages*') ?>">
              <i class="mdi mdi-email"></i>
              <span>Pesan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('counselor/notifications') ?>" class="waves-effect<?= $__active('counselor/notifications*') ?>">
              <i class="mdi mdi-bell"></i>
              <span>Notifikasi</span>
            </a>
          </li>
          <li>
            <a href="<?= base_url('counselor/trash') ?>" class="waves-effect<?= $__active('counselor/trash*') ?>">
              <i class="mdi mdi-trash-can-outline"></i>
              <span>Tempat Sampah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isHomeroom): ?>
          <!-- WALI KELAS MENU -->
          <li class="menu-title">Menu Wali Kelas</li>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('homeroom/dashboard') ?>" class="waves-effect<?= $__active('homeroom/dashboard*') ?>">
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
          <?php endif; ?>

          <?php if ($__permViewBkServices || ($__permSubmitConsultations && $__canUseConsultation()) || ($__enableCareerInfo && $__canAny(['manage_career_info', 'view_career_info']))): ?>
          <li class="<?= $__mm(['homeroom/jadwal-bk*', 'homeroom/consultations*', 'homeroom/career-info*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-calendar-heart"></i>
              <span>Jadwal/Layanan BK</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permViewBkServices): ?>
                <li><a href="<?= base_url('homeroom/jadwal-bk') ?>" class="<?= $__active('homeroom/jadwal-bk*') ? 'active' : '' ?>">Jadwal Kegiatan/Acara BK</a></li>
              <?php endif; ?>
              <?php if ($__permSubmitConsultations && $__canUseConsultation()): ?>
                <li><a href="<?= base_url('homeroom/consultations') ?>" class="<?= $__active('homeroom/consultations*') ? 'active' : '' ?>">Konsultasi & Pengaduan</a></li>
              <?php endif; ?>
              <?php if ($__enableCareerInfo && $__canAny(['manage_career_info', 'view_career_info'])): ?>
                <li><a href="<?= base_url('homeroom/career-info') ?>" class="<?= $__active('homeroom/career-info*') ? 'active' : '' ?>">Info Karier dan Studi Lanjut</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__permViewReportsIndividual): ?>
          <li>
            <a href="<?= base_url('homeroom/reports') ?>" class="waves-effect<?= $__active('homeroom/reports*') ?>">
              <i class="mdi mdi-file-chart"></i>
              <span>Laporan</span>
            </a>
          </li>
          <?php endif; ?>

          <li class="menu-title">Lainnya</li>

          <?php if ($__permSendMessages): ?>
          <li>
            <a href="<?= base_url('homeroom/messages') ?>" class="waves-effect<?= $__active('homeroom/messages*') ?>">
              <i class="mdi mdi-email"></i>
              <span>Pesan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('homeroom/notifications') ?>" class="waves-effect<?= $__active('homeroom/notifications*') ?>">
              <i class="mdi mdi-bell"></i>
              <span>Notifikasi</span>
            </a>
          </li>
          <li>
            <a href="<?= base_url('homeroom/trash') ?>" class="waves-effect<?= $__active('homeroom/trash*') ?>">
              <i class="mdi mdi-trash-can-outline"></i>
              <span>Tempat Sampah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isStudent): ?>
          <!-- SISWA MENU -->
          <li class="menu-title">Menu Siswa</li>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('student/dashboard') ?>" class="waves-effect<?= $__active('student/dashboard*') ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewStudentPortfolio || $__permViewStaffInfo): ?>
          <li class="<?= $__mm(['student/profile*', 'student/staff*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-account-circle"></i>
              <span>Profil & Guru</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permViewStudentPortfolio): ?>
                <li><a href="<?= base_url('student/profile') ?>" class="<?= $__active('student/profile*') ? 'active' : '' ?>">Profil Saya</a></li>
              <?php endif; ?>
              <?php if ($__permViewStaffInfo): ?>
                <li><a href="<?= base_url('student/staff') ?>" class="<?= $__active('student/staff*') ? 'active' : '' ?>">Info Guru</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__permViewBkServices || ($__enableAssessments && $__permTakeAssessments)): ?>
          <li class="<?= $__mm(['student/jadwal-bk*', 'student/assessments*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-calendar-check"></i>
              <span>Jadwal/Kegiatan BK</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permViewBkServices): ?>
                <li><a href="<?= base_url('student/jadwal-bk') ?>" class="<?= $__active('student/jadwal-bk*') ? 'active' : '' ?>">Jadwal Kegiatan/Acara BK</a></li>
              <?php endif; ?>
              <?php if ($__enableAssessments && $__permTakeAssessments): ?>
                <li><a href="<?= base_url('student/assessments') ?>" class="<?= $__active('student/assessments*') ? 'active' : '' ?>">Asesmen</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if (($__permSubmitConsultations && $__canUseConsultation()) || ($__enableCareerInfo && $__permViewCareerInfo)): ?>
          <li class="<?= $__mm(['student/consultations*', 'student/career*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-shape-plus"></i>
              <span>Layanan BK Lainnya</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permSubmitConsultations && $__canUseConsultation()): ?>
                <li><a href="<?= base_url('student/consultations') ?>" class="<?= $__active('student/consultations*') ? 'active' : '' ?>">Konsultasi & Pengaduan</a></li>
              <?php endif; ?>
              <?php if ($__enableCareerInfo && $__permViewCareerInfo): ?>
                <li><a href="<?= base_url('student/career') ?>" class="<?= $__active('student/career*') ? 'active' : '' ?>">Info Karier dan Studi Lanjut</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <li class="menu-title">Lainnya</li>

          <?php if ($__permSendMessages): ?>
          <li>
            <a href="<?= base_url('student/messages') ?>" class="waves-effect<?= $__active('student/messages*') ?>">
              <i class="mdi mdi-email"></i>
              <span>Pesan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('student/notifications') ?>" class="waves-effect<?= $__active('student/notifications*') ?>">
              <i class="mdi mdi-bell"></i>
              <span>Notifikasi</span>
            </a>
          </li>
          <li>
            <a href="<?= base_url('student/trash') ?>" class="waves-effect<?= $__active('student/trash*') ?>">
              <i class="mdi mdi-trash-can-outline"></i>
              <span>Tempat Sampah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php elseif ($__isParent): ?>
          <!-- ORANG TUA MENU -->
          <li class="menu-title">Menu Orang Tua</li>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('parent/dashboard') ?>" class="waves-effect<?= $__active('parent/dashboard*') ?>">
              <i class="mdi mdi-view-dashboard"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewStudentPortfolio || $__permViewBkServices): ?>
          <li class="<?= $__mm(['parent/children*', 'parent/child*', 'parent/jadwal-bk*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-account-child-circle"></i>
              <span>Anak & Jadwal</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permViewStudentPortfolio): ?>
                <li><a href="<?= base_url('parent/children') ?>" class="<?= $__activeAny(['parent/children*', 'parent/child*']) !== '' ? 'active' : '' ?>">Daftar Anak</a></li>
              <?php endif; ?>
              <?php if ($__permViewBkServices): ?>
                <li><a href="<?= base_url('parent/jadwal-bk') ?>" class="<?= $__active('parent/jadwal-bk*') ? 'active' : '' ?>">Jadwal Kegiatan/Acara BK</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if (($__permSubmitConsultations && $__canUseConsultation()) || ($__enableCareerInfo && $__permViewCareerInfo)): ?>
          <li class="<?= $__mm(['parent/consultations*', 'parent/career*']) ?>">
            <a href="javascript:void(0);" class="has-arrow waves-effect">
              <i class="mdi mdi-shape-plus"></i>
              <span>Layanan BK Lainnya</span>
            </a>
            <ul class="sub-menu" aria-expanded="false">
              <?php if ($__permSubmitConsultations && $__canUseConsultation()): ?>
                <li><a href="<?= base_url('parent/consultations') ?>" class="<?= $__active('parent/consultations*') ? 'active' : '' ?>">Konsultasi & Pengaduan</a></li>
              <?php endif; ?>
              <?php if ($__enableCareerInfo && $__permViewCareerInfo): ?>
                <li><a href="<?= base_url('parent/career') ?>" class="<?= $__active('parent/career*') ? 'active' : '' ?>">Info Karier dan Studi Lanjut</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($__permViewReportsIndividual): ?>
          <li>
            <a href="<?= base_url('parent/reports/children') ?>" class="waves-effect<?= $__active('parent/reports*') ?>">
              <i class="mdi mdi-printer"></i>
              <span>Cetak Data Anak</span>
            </a>
          </li>
          <?php endif; ?>

          <li class="menu-title">Lainnya</li>

          <?php if ($__permSendMessages): ?>
          <li>
            <a href="<?= base_url('parent/messages') ?>" class="waves-effect<?= $__active('parent/messages*') ?>">
              <i class="mdi mdi-email"></i>
              <span>Pesan</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($__permViewDashboard): ?>
          <li>
            <a href="<?= base_url('parent/notifications') ?>" class="waves-effect<?= $__active('parent/notifications*') ?>">
              <i class="mdi mdi-bell"></i>
              <span>Notifikasi</span>
            </a>
          </li>
          <li>
            <a href="<?= base_url('parent/trash') ?>" class="waves-effect<?= $__active('parent/trash*') ?>">
              <i class="mdi mdi-trash-can-outline"></i>
              <span>Tempat Sampah</span>
            </a>
          </li>
          <?php endif; ?>

        <?php endif; ?>

        <?php if ($__canAccessDemoSuite): ?>
        <li class="menu-title">Prototipe Pengembangan</li>
        <li class="<?= $__mm(['simulation*', 'prototype*', 'admin/simulation-access*']) ?>">
          <a href="javascript:void(0);" class="has-arrow waves-effect">
            <i class="mdi mdi-test-tube"></i>
            <span>Prototipe Pengembangan</span>
          </a>
          <ul class="sub-menu" aria-expanded="false">
            <li><a href="<?= base_url('simulation') ?>" class="<?= $__active('simulation*') ? 'active' : '' ?>">Simulasi Lama</a></li>
            <li><a href="<?= base_url('prototype') ?>" class="<?= $__active('prototype*') ? 'active' : '' ?>">Halaman Demo BK</a></li>
            <?php if ($__isAdmin): ?>
              <li><a href="<?= base_url('admin/simulation-access') ?>" class="<?= $__active('admin/simulation-access*') ? 'active' : '' ?>">Kelola Akses</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

      </ul>
    </div>
    <!-- /#sidebar-menu -->
  </div>
</div>
<!-- Left Sidebar End -->
