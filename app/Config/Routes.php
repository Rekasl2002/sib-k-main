<?php

/**
 * File Path: app/Config/Routes.php
 *
 * Complete Routes Configuration (RBAC-ready)
 * Qovex Template â€¢ CodeIgniter 4.6.3
 *
 * IMPORTANT (RBAC Permissions):
 * - Semua filter permission di file ini WAJIB sesuai dengan `permissions.permission_name` di database.
 * - Format filter yang dipakai:  'permission:<permission_name>[,<permission_name_lain>...]'
 * - Jangan pakai nama permission yang tidak ada di tabel `permissions`. Jika butuh, gunakan yang sudah ada & paling relevan.
 */

use CodeIgniter\Router\RouteCollection;
use Config\Services;

/** @var RouteCollection $routes */

/**
 * âœ… SAFETY: Pastikan $routes terdefinisi.
 */
if (!isset($routes) || !($routes instanceof RouteCollection)) {
    $routes = Services::routes();
}

/**
 * âœ… OPSIONAL (standar CI4):
 * Muat Routes sistem terlebih dahulu (kalau ada).
 * Ini membantu menjaga default behavior CI4 tetap konsisten.
 */
$systemRoutes = SYSTEMPATH . 'Config/Routes.php';
if (is_file($systemRoutes)) {
    require $systemRoutes;
}

/**
 * --------------------------------------------------------------------
 * Router Setup (standar CI4)
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);

// 404 override custom kamu sudah ada di bawah (lihat bagian akhir file).

// Disarankan: matikan AutoRoute untuk keamanan (semua rute eksplisit)
$routes->setAutoRoute(false);


// UI Manajemen Sesi Konseling lama sudah digantikan fitur Konseling final.
// Tabel counseling_sessions tetap dipakai sebagai detail data Konseling.

// -------------------------------
// Default
// -------------------------------

// âœ… FIX: Masuk web langsung ke halaman login
$routes->get('/', static fn() => redirect()->to('/login'));

// âœ… OPSIONAL: tetap sediakan akses Home::index (kalau masih dibutuhkan)
$routes->get('home', 'Home::index');

/**
 * âœ… Dashboard universal (opsional, tapi enak untuk redirect fail-safe)
 * Mengarahkan user ke dashboard sesuai role_name di session.
 */
$routes->get('dashboard', static function () {
    $session = session();
    $role = strtolower(trim((string)($session->get('role_name') ?? '')));

    return match ($role) {
        'admin', 'administrator'        => redirect()->to('/admin/dashboard'),
        'koordinator bk', 'koordinator' => redirect()->to('/koordinator/dashboard'),
        'guru bk', 'counselor'          => redirect()->to('/counselor/dashboard'),
        'wali kelas', 'homeroom'        => redirect()->to('/homeroom/dashboard'),
        'siswa', 'student'              => redirect()->to('/student/dashboard'),
        'orang tua', 'parent'           => redirect()->to('/parent/dashboard'),
        default                         => redirect()->to('/'),
    };
}, ['filter' => 'auth']);

// Convenience redirects untuk root group agar tidak 404 ketika user akses langsung
$routes->get('admin', static fn() => redirect()->to('/admin/dashboard'), ['filter' => 'auth']);
$routes->get('counselor', static fn() => redirect()->to('/counselor/dashboard'), ['filter' => 'auth']);
$routes->get('koordinator', static fn() => redirect()->to('/koordinator/dashboard'), ['filter' => 'auth']);
$routes->get('homeroom', static fn() => redirect()->to('/homeroom/dashboard'), ['filter' => 'auth']);
$routes->get('parent', static fn() => redirect()->to('/parent/dashboard'), ['filter' => 'auth']);
$routes->get('student', static fn() => redirect()->to('/student/dashboard'), ['filter' => 'auth']);

/**
 * âœ… Kompatibilitas untuk AuthFilter lama yang redirect ke /auth/login.
 * Ini mencegah 404 / redirect loop.
 */
$routes->group('auth', ['filter' => 'csrf'], function ($routes) {
    $routes->get('login', 'Auth\AuthController::index');
    $routes->post('login', 'Auth\AuthController::login');
    $routes->post('logout', 'Auth\AuthController::logout');
    $routes->get('register', 'Auth\AuthController::register');
    $routes->post('register', 'Auth\AuthController::doRegister');
    $routes->get('forgot-password', 'Auth\AuthController::forgotPassword');
    $routes->post('forgot-password', 'Auth\AuthController::sendResetLink');
    $routes->get('reset-password/(:segment)', 'Auth\AuthController::resetPassword/$1');
    $routes->post('reset-password', 'Auth\AuthController::doResetPassword');
});

/**
 * âœ… Opsional kompatibilitas:
 * beberapa view/controller lama kadang pakai prefix "homeroom_teacher/*"
 * kita redirect ke rute "homeroom/*" biar tidak 404.
 *
 * PERBAIKAN:
 * - Hindari 'auth,role:...' (error CI4).
 * - Gunakan nested group.
 */
$routes->group('homeroom_teacher', ['filter' => 'auth'], function ($routes) {
    $routes->group('', ['filter' => 'role:wali kelas,homeroom'], function ($routes) {
        $routes->get('/', static fn() => redirect()->to('/homeroom/dashboard'));
        $routes->get('dashboard', static fn() => redirect()->to('/homeroom/dashboard'));
        $routes->get('reports', static fn() => redirect()->to('/homeroom/reports'));

        $routes->get('reports/preview', static function () {
            $q  = service('request')->getGet() ?? [];
            $qs = http_build_query($q);
            return redirect()->to('/homeroom/reports/preview' . ($qs ? ('?' . $qs) : ''));
        });

        $routes->get('reports/download', static function () {
            $q  = service('request')->getGet() ?? [];
            $qs = http_build_query($q);
            return redirect()->to('/homeroom/reports/download' . ($qs ? ('?' . $qs) : ''));
        });
    });
});

// -------------------------------
// Authentication (public)
// -------------------------------
$routes->group('', ['filter' => 'csrf'], function ($routes) {
    $routes->get('login', 'Auth\AuthController::index', ['as' => 'login']);
    $routes->post('login', 'Auth\AuthController::login', ['as' => 'login.submit']);

    $routes->post('logout', 'Auth\AuthController::logout', ['as' => 'logout']);

    $routes->get('register', 'Auth\AuthController::register', ['as' => 'register']);
    $routes->post('register', 'Auth\AuthController::doRegister', ['as' => 'register.submit']);

    $routes->get('forgot-password', 'Auth\AuthController::forgotPassword', ['as' => 'password.forgot']);
    $routes->post('forgot-password', 'Auth\AuthController::sendResetLink', ['as' => 'password.email']);
    $routes->get('reset-password/(:segment)', 'Auth\AuthController::resetPassword/$1', ['as' => 'password.reset']);
    $routes->post('reset-password', 'Auth\AuthController::doResetPassword', ['as' => 'password.update']);
});
$routes->get('verify/(:segment)', 'Auth\AuthController::verify/$1', ['as' => 'verification.verify']);

// -------------------------------
// Profile (all authenticated)
// -------------------------------
$routes->group('profile', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ProfileController::index', ['as' => 'profile']);
    $routes->post('change-password', 'ProfileController::changePassword', ['as' => 'profile.password']);

    // Edit dialihkan ke index, karena ProfileController versi sekarang tidak punya method edit()
    $routes->get('edit', static fn() => redirect()->to('/profile'), ['as' => 'profile.edit']);
    $routes->post('update', 'ProfileController::update', ['as' => 'profile.update']);
    $routes->post('upload-photo', 'ProfileController::uploadPhoto', ['as' => 'profile.photo']);
});

// ===============================
// ADMIN (role locked)
// ===============================
// PERBAIKAN: hindari 'auth,role:admin' -> pakai nested group
$routes->group('admin', [
    'filter'    => 'auth',
    'namespace' => 'App\Controllers\Admin'
], function ($routes) {

    $routes->group('', ['filter' => 'role:admin,administrator'], function ($routes) {

        // Dashboard (izin: view_dashboard)
        $routes->get('dashboard', 'DashboardController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'admin.dashboard'
        ]);
        $routes->get('dashboard/stats', 'DashboardController::getStats', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'admin.dashboard.stats'
        ]);

        $routes->group('notifications', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'NotificationController::index', ['as' => 'admin.notifications']);
            $routes->get('unread', 'NotificationController::unread', ['as' => 'admin.notifications.unread']);
            $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1', ['as' => 'admin.notifications.read']);
            $routes->post('mark-all-read', 'NotificationController::markAllAsRead', ['as' => 'admin.notifications.read_all']);
            $routes->post('delete/(:num)', 'NotificationController::delete/$1', ['as' => 'admin.notifications.delete']);
            $routes->post('delete-all', 'NotificationController::deleteAll', ['as' => 'admin.notifications.delete_all']);
            $routes->get('count', 'NotificationController::getUnreadCount', ['as' => 'admin.notifications.count']);
        });

        // Tempat Sampah (pemulihan soft delete) - hanya data milik penghapus.
        $routes->group('trash', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'TrashController::index', ['as' => 'admin.trash']);
            $routes->post('restore', 'TrashController::restore', ['as' => 'admin.trash.restore']);
            $routes->post('force-delete', 'TrashController::forceDelete', ['as' => 'admin.trash.force']);
        });

        $routes->group('messages', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'MessageController::index', ['as' => 'admin.messages']);
            $routes->get('summary', 'MessageController::summary', ['as' => 'admin.messages.summary']);
            $routes->get('chat/(:num)', 'MessageController::chat/$1', ['as' => 'admin.messages.chat']);
            $routes->get('poll/(:num)', 'MessageController::poll/$1', ['as' => 'admin.messages.poll']);
            $routes->post('send/(:num)', 'MessageController::send/$1', ['as' => 'admin.messages.send']);
            $routes->post('delete', 'MessageController::delete', ['as' => 'admin.messages.delete']);
            $routes->post('delete-all', 'MessageController::deleteAll', ['as' => 'admin.messages.delete_all']);
            $routes->get('attachment/(:num)', 'MessageController::downloadAttachment/$1', ['as' => 'admin.messages.attachment']);
        });

        // USER MANAGEMENT (izin: manage_users)
        $routes->group('users', ['filter' => 'permission:manage_users'], function ($routes) {
            $routes->get('/', 'UserController::index', ['as' => 'admin.users']);
            $routes->get('create', 'UserController::create', ['as' => 'admin.users.create']);
            $routes->post('store', 'UserController::store', ['as' => 'admin.users.store']);
            $routes->get('show/(:num)', 'UserController::show/$1', ['as' => 'admin.users.show']);
            $routes->get('edit/(:num)', 'UserController::edit/$1', ['as' => 'admin.users.edit']);
            $routes->post('update/(:num)', 'UserController::update/$1', ['as' => 'admin.users.update']);
            $routes->post('delete/(:num)', 'UserController::delete/$1', ['as' => 'admin.users.delete']);
            $routes->post('toggle-active/(:num)', 'UserController::toggleActive/$1', ['as' => 'admin.users.toggle']);
            $routes->post('reset-password/(:num)', 'UserController::resetPassword/$1', ['as' => 'admin.users.reset']);
            $routes->post('upload-photo/(:num)', 'UserController::uploadPhoto/$1', ['as' => 'admin.users.photo']);
            $routes->get('export', 'UserController::export', ['as' => 'admin.users.export']);
            $routes->get('search', 'UserController::search', ['as' => 'admin.users.search']);
        });

        // PERMINTAAN RESET PASSWORD (izin: manage_users)
        $routes->group('password-reset-requests', ['filter' => 'permission:manage_users'], function ($routes) {
            $routes->get('/', 'PasswordResetRequestController::index', ['as' => 'admin.password_resets']);
            $routes->post('resolve/(:num)', 'PasswordResetRequestController::resolve/$1', ['as' => 'admin.password_resets.resolve']);
        });

        // ROLE MANAGEMENT (izin: manage_roles)
        $routes->group('roles', ['filter' => 'permission:manage_roles'], function ($routes) {
            $routes->get('/', 'RoleController::index', ['as' => 'admin.roles']);
            $routes->get('create', 'RoleController::create', ['as' => 'admin.roles.create']);
            $routes->post('store', 'RoleController::store', ['as' => 'admin.roles.store']);
            $routes->get('edit/(:num)', 'RoleController::edit/$1', ['as' => 'admin.roles.edit']);
            $routes->post('update/(:num)', 'RoleController::update/$1', ['as' => 'admin.roles.update']);
            $routes->post('delete/(:num)', 'RoleController::delete/$1', ['as' => 'admin.roles.delete']);
            $routes->get('permissions/(:num)', 'RoleController::permissions/$1', ['as' => 'admin.roles.permissions']);
            $routes->post('assign-permissions/(:num)', 'RoleController::assignPermissions/$1', ['as' => 'admin.roles.assign']);
        });

        // ACADEMIC YEAR (izin: manage_academic_data)
        $routes->group('academic-years', ['filter' => 'permission:manage_academic_data'], function ($routes) {
            $routes->get('/', 'AcademicYearController::index', ['as' => 'admin.academic_years']);
            $routes->get('create', 'AcademicYearController::create', ['as' => 'admin.academic_years.create']);
            $routes->post('store', 'AcademicYearController::store', ['as' => 'admin.academic_years.store']);
            $routes->get('edit/(:num)', 'AcademicYearController::edit/$1', ['as' => 'admin.academic_years.edit']);
            $routes->post('update/(:num)', 'AcademicYearController::update/$1', ['as' => 'admin.academic_years.update']);
            $routes->post('delete/(:num)', 'AcademicYearController::delete/$1', ['as' => 'admin.academic_years.delete']);
            $routes->post('set-active/(:num)', 'AcademicYearController::setActive/$1', ['as' => 'admin.academic_years.activate']);
            $routes->get('get-suggested', 'AcademicYearController::getSuggested', ['as' => 'admin.academic_years.suggested']);
            $routes->get('check-overlap', 'AcademicYearController::checkOverlap', ['as' => 'admin.academic_years.check']);
            $routes->get('generate-year-name', 'AcademicYearController::generateYearName', ['as' => 'admin.academic_years.generate']);
        });

        // CLASSES (izin: manage_academic_data)
        $routes->group('classes', ['filter' => 'permission:manage_academic_data'], function ($routes) {
            $routes->get('/', 'ClassController::index', ['as' => 'admin.classes']);
            $routes->get('create', 'ClassController::create', ['as' => 'admin.classes.create']);
            $routes->post('store', 'ClassController::store', ['as' => 'admin.classes.store']);
            $routes->get('edit/(:num)', 'ClassController::edit/$1', ['as' => 'admin.classes.edit']);
            $routes->post('update/(:num)', 'ClassController::update/$1', ['as' => 'admin.classes.update']);
            $routes->post('delete/(:num)', 'ClassController::delete/$1', ['as' => 'admin.classes.delete']);
            $routes->get('detail/(:num)', 'ClassController::detail/$1', ['as' => 'admin.classes.detail']);
            $routes->get('get-suggested-name', 'ClassController::getSuggestedName', ['as' => 'admin.classes.suggested']);
            $routes->post('assign-homeroom/(:num)', 'ClassController::assignHomeroom/$1', ['as' => 'admin.classes.homeroom']);
            $routes->post('assign-counselor/(:num)', 'ClassController::assignCounselor/$1', ['as' => 'admin.classes.counselor']);
        });

        // STUDENTS
        $routes->group('students', function ($routes) {
            // Lihat/daftar (izin: view_all_students)
            $routes->get('/', 'StudentController::index', ['filter' => 'permission:view_all_students', 'as' => 'admin.students']);
            $routes->get('profile/(:num)', 'StudentController::profile/$1', ['filter' => 'permission:view_all_students', 'as' => 'admin.students.profile']);
            $routes->get('search', 'StudentController::search', ['filter' => 'permission:view_all_students', 'as' => 'admin.students.search']);
            $routes->get('by-class/(:num)', 'StudentController::getByClass/$1', ['filter' => 'permission:view_all_students', 'as' => 'admin.students.by_class']);
            $routes->get('stats', 'StudentController::getStats', ['filter' => 'permission:view_all_students', 'as' => 'admin.students.stats']);

            // CRUD & mutasi (izin: manage_academic_data)
            $routes->get('create', 'StudentController::create', ['filter' => 'permission:manage_academic_data', 'as' => 'admin.students.create']);
            $routes->post('store', 'StudentController::store', ['filter' => 'permission:manage_academic_data', 'as' => 'admin.students.store']);
            $routes->get('edit/(:num)', 'StudentController::edit/$1', ['filter' => 'permission:manage_academic_data', 'as' => 'admin.students.edit']);
            $routes->post('update/(:num)', 'StudentController::update/$1', ['filter' => 'permission:manage_academic_data', 'as' => 'admin.students.update']);
            $routes->post('delete/(:num)', 'StudentController::delete/$1', ['filter' => 'permission:manage_academic_data', 'as' => 'admin.students.delete']);
            $routes->post('change-class/(:num)', 'StudentController::changeClass/$1', ['filter' => 'permission:manage_academic_data', 'as' => 'admin.students.change_class']);

            // Export/Import (izin: import_export_data)
            $routes->get('export', 'StudentController::export', ['filter' => 'permission:import_export_data', 'as' => 'admin.students.export']);
            $routes->get('import', 'StudentController::import', ['filter' => 'permission:import_export_data', 'as' => 'admin.students.import']);
            $routes->post('do-import', 'StudentController::doImport', ['filter' => 'permission:import_export_data', 'as' => 'admin.students.do_import']);
            $routes->get('download-template', 'StudentController::downloadTemplate', ['filter' => 'permission:import_export_data', 'as' => 'admin.students.template']);
        });

        // SYSTEM SETTINGS (izin: manage_settings)
        $routes->group('settings', ['filter' => 'permission:manage_settings'], function ($routes) {
            $routes->get('/', 'SettingController::index', ['as' => 'admin.settings']);
            $routes->post('update', 'SettingController::update', ['as' => 'admin.settings.update']);
            $routes->post('reset', 'SettingController::reset', ['as' => 'admin.settings.reset']);
        });

        // EXPORT (izin: import_export_data)
        $routes->group('export', ['filter' => 'permission:import_export_data'], function ($routes) {
            $routes->get('/', 'ExportController::options', ['as' => 'admin.export']);
            $routes->get('students', 'ExportController::students', ['as' => 'admin.export.students']);
            $routes->get('sessions', 'ExportController::sessions', ['as' => 'admin.export.sessions']);
        });
    });
});

// ===============================
// KOORDINATOR BK (role locked)
// ===============================
// PERBAIKAN: hindari 'auth,role:...' -> nested group
$routes->group('koordinator', [
    'filter'    => 'auth',
    'namespace' => 'App\Controllers\Koordinator'
], function ($routes) {

    $routes->group('', ['filter' => 'role:koordinator bk,koordinator,coordinator'], function ($routes) {

        $routes->get('dashboard', 'DashboardController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'koordinator.dashboard'
        ]);

        $routes->group('notifications', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'NotificationController::index', ['as' => 'koordinator.notifications']);
            $routes->get('unread', 'NotificationController::unread', ['as' => 'koordinator.notifications.unread']);
            $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1', ['as' => 'koordinator.notifications.read']);
            $routes->post('mark-all-read', 'NotificationController::markAllAsRead', ['as' => 'koordinator.notifications.read_all']);
            $routes->post('delete/(:num)', 'NotificationController::delete/$1', ['as' => 'koordinator.notifications.delete']);
            $routes->post('delete-all', 'NotificationController::deleteAll', ['as' => 'koordinator.notifications.delete_all']);
            $routes->get('count', 'NotificationController::getUnreadCount', ['as' => 'koordinator.notifications.count']);
        });

        // Tempat Sampah (pemulihan soft delete) - hanya data milik penghapus.
        $routes->group('trash', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'TrashController::index', ['as' => 'koordinator.trash']);
            $routes->post('restore', 'TrashController::restore', ['as' => 'koordinator.trash.restore']);
            $routes->post('force-delete', 'TrashController::forceDelete', ['as' => 'koordinator.trash.force']);
        });

        $routes->group('messages', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'MessageController::index', ['as' => 'koordinator.messages']);
            $routes->get('summary', 'MessageController::summary', ['as' => 'koordinator.messages.summary']);
            $routes->get('chat/(:num)', 'MessageController::chat/$1', ['as' => 'koordinator.messages.chat']);
            $routes->get('poll/(:num)', 'MessageController::poll/$1', ['as' => 'koordinator.messages.poll']);
            $routes->post('send/(:num)', 'MessageController::send/$1', ['as' => 'koordinator.messages.send']);
            $routes->post('delete', 'MessageController::delete', ['as' => 'koordinator.messages.delete']);
            $routes->post('delete-all', 'MessageController::deleteAll', ['as' => 'koordinator.messages.delete_all']);
            $routes->get('attachment/(:num)', 'MessageController::downloadAttachment/$1', ['as' => 'koordinator.messages.attachment']);
        });

        // USER MANAGEMENT (izin: manage_users)
        $routes->group('users', ['filter' => 'permission:manage_users'], function ($routes) {
            $routes->get('/', 'UserController::index', ['as' => 'koordinator.users.index']);

            $routes->get('create', 'UserController::create', ['as' => 'koordinator.users.create']);
            $routes->post('store', 'UserController::store', ['as' => 'koordinator.users.store']);

            $routes->get('show/(:num)', 'UserController::show/$1', ['as' => 'koordinator.users.show']);
            $routes->get('edit/(:num)', 'UserController::edit/$1', ['as' => 'koordinator.users.edit']);
            $routes->post('update/(:num)', 'UserController::update/$1', ['as' => 'koordinator.users.update']);

            $routes->post('delete/(:num)', 'UserController::delete/$1', ['as' => 'koordinator.users.delete']);
            $routes->get('delete/(:num)', 'UserController::delete/$1', ['as' => 'koordinator.users.delete.get']);

            $routes->post('toggle-active/(:num)', 'UserController::toggleActive/$1', ['as' => 'koordinator.users.toggle']);

            $routes->post('reset-password/(:num)', 'UserController::resetPassword/$1', ['as' => 'koordinator.users.reset']);
            $routes->get('reset-password/(:num)', 'UserController::resetPassword/$1', ['as' => 'koordinator.users.reset.get']);

            $routes->post('upload-photo/(:num)', 'UserController::uploadPhoto/$1', ['as' => 'koordinator.users.photo']);

            $routes->get('export', 'UserController::export', ['as' => 'koordinator.users.export']);
            $routes->get('search', 'UserController::search', ['as' => 'koordinator.users.search']);

            $routes->post('change-password', 'UserController::changePassword', ['as' => 'koordinator.users.change_password']);

            $routes->get('(:num)', 'UserController::show/$1');
        });

        // Staf (anggap bagian dari manajemen user)
        $routes->group('staff', ['filter' => 'permission:manage_users'], function ($routes) {
            $routes->get('/', 'StaffController::index', ['as' => 'koordinator.staff.index']);
            $routes->get('create', 'StaffController::create', ['as' => 'koordinator.staff.create']);
            $routes->post('store', 'StaffController::store', ['as' => 'koordinator.staff.store']);
            $routes->get('edit/(:num)', 'StaffController::edit/$1', ['as' => 'koordinator.staff.edit']);
            $routes->post('update/(:num)', 'StaffController::update/$1', ['as' => 'koordinator.staff.update']);
            $routes->post('toggle/(:num)', 'StaffController::toggleActive/$1', ['as' => 'koordinator.staff.toggle']);
        });

        // STUDENTS (Koordinator: lihat semua sesuai scope; edit/update mengikuti guard controller)
        $routes->group('students', function ($routes) {
            $routes->get('/', 'StudentController::index', [
                'filter' => 'permission:view_all_students',
                'as'     => 'koordinator.students.index'
            ]);
            $routes->get('create', 'StudentController::create', [
                'filter' => 'permission:manage_students',
                'as'     => 'koordinator.students.create'
            ]);
            $routes->post('store', 'StudentController::store', [
                'filter' => 'permission:manage_students',
                'as'     => 'koordinator.students.store'
            ]);
            $routes->get('profile/(:num)', 'StudentController::profile/$1', [
                'filter' => 'permission:view_all_students',
                'as'     => 'koordinator.students.profile'
            ]);
            $routes->get('search', 'StudentController::search', [
                'filter' => 'permission:view_all_students',
                'as'     => 'koordinator.students.search'
            ]);
            $routes->get('by-class/(:num)', 'StudentController::getByClass/$1', [
                'filter' => 'permission:view_all_students',
                'as'     => 'koordinator.students.by_class'
            ]);
            $routes->get('stats', 'StudentController::getStats', [
                'filter' => 'permission:view_all_students',
                'as'     => 'koordinator.students.stats'
            ]);

            // Edit/update: pakai permission yang memang ada di tabel (hindari manage_academic_data yang khusus Admin)
            $routes->get('edit/(:num)', 'StudentController::edit/$1', [
                'filter' => 'permission:manage_students',
                'as'     => 'koordinator.students.edit'
            ]);
            $routes->post('update/(:num)', 'StudentController::update/$1', [
                'filter' => 'permission:manage_students',
                'as'     => 'koordinator.students.update'
            ]);
            $routes->post('delete/(:num)', 'StudentController::delete/$1', [
                'filter' => 'permission:manage_students',
                'as'     => 'koordinator.students.delete'
            ]);

            // Impor/ekspor data siswa dan akun orang tua untuk pembagian kerja pengelolaan data.
            $routes->get('export', 'StudentController::export', [
                'filter' => 'permission:import_export_data',
                'as'     => 'koordinator.students.export'
            ]);
            $routes->get('import', 'StudentController::import', [
                'filter' => 'permission:import_export_data',
                'as'     => 'koordinator.students.import'
            ]);
            $routes->post('do-import', 'StudentController::doImport', [
                'filter' => 'permission:import_export_data',
                'as'     => 'koordinator.students.do_import'
            ]);
            $routes->get('download-template', 'StudentController::downloadTemplate', [
                'filter' => 'permission:import_export_data',
                'as'     => 'koordinator.students.template'
            ]);
        });

        $routes->get('sessions', static fn() => redirect()->to('/koordinator/counseling'), ['as' => 'koordinator.sessions.index']);
        $routes->get('sessions/(:any)', static fn() => redirect()->to('/koordinator/counseling'));
        $routes->get('schedule', static fn() => redirect()->to('/koordinator/counseling'), ['as' => 'koordinator.schedule']);
        $routes->get('schedule/(:any)', static fn() => redirect()->to('/koordinator/counseling'));


        // Fitur final pengembangan BK: Konsultasi & Pengaduan, layanan BK, dan Penugasan.
        $routes->group('consultations', ['filter' => 'permission:any,manage_consultation_complaints,review_consultation_complaints,submit_consultation_complaints'], function ($routes) {
            $routes->get('/', 'ConsultationController::index', ['as' => 'koordinator.consultations.index']);
            $routes->get('create', 'ConsultationController::create', ['as' => 'koordinator.consultations.create']);
            $routes->post('store', 'ConsultationController::store', ['as' => 'koordinator.consultations.store']);
            $routes->get('show/(:num)', 'ConsultationController::show/$1', ['as' => 'koordinator.consultations.show']);
            $routes->get('edit/(:num)', 'ConsultationController::edit/$1', ['as' => 'koordinator.consultations.edit']);
            $routes->post('update/(:num)', 'ConsultationController::update/$1', ['as' => 'koordinator.consultations.update']);
            $routes->post('delete/(:num)', 'ConsultationController::delete/$1', ['as' => 'koordinator.consultations.delete']);
            $routes->post('review/(:num)', 'ConsultationController::review/$1', ['as' => 'koordinator.consultations.review']);
            $routes->get('attachment/(:num)', 'ConsultationController::downloadAttachment/$1', ['as' => 'koordinator.consultations.attachment']);
            $routes->post('attachment-delete/(:num)', 'ConsultationController::deleteAttachment/$1', ['as' => 'koordinator.consultations.attachmentDelete']);
        });

        $bkServiceRoutes = static function ($routes, string $controller, string $alias): void {
            $routes->get('/', $controller . '::index', ['as' => $alias . '.index']);
            $routes->get('create', $controller . '::create', ['as' => $alias . '.create']);
            $routes->post('store', $controller . '::store', ['as' => $alias . '.store']);
            $routes->get('show/(:num)', $controller . '::show/$1', ['as' => $alias . '.show']);
            $routes->get('edit/(:num)', $controller . '::edit/$1', ['as' => $alias . '.edit']);
            $routes->post('update/(:num)', $controller . '::update/$1', ['as' => $alias . '.update']);
            $routes->post('delete/(:num)', $controller . '::delete/$1', ['as' => $alias . '.delete']);
            $routes->post('note/(:num)', $controller . '::addNote/$1', ['as' => $alias . '.note']);
            $routes->post('participants/(:num)', $controller . '::updateParticipant/$1', ['as' => $alias . '.participant']);
            $routes->post('participant-add/(:num)', $controller . '::addParticipant/$1', ['as' => $alias . '.participantAdd']);
            $routes->post('participant-delete/(:num)', $controller . '::deleteParticipant/$1', ['as' => $alias . '.participantDelete']);
            $routes->post('note-delete/(:num)', $controller . '::deleteNote/$1', ['as' => $alias . '.noteDelete']);
        };

        $routes->group('guidance', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'GuidanceController', 'koordinator.guidance'));
        $routes->group('counseling', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'CounselingController', 'koordinator.counseling'));
        $routes->group('parent-collaborations', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'ParentCollaborationController', 'koordinator.parent_collaborations'));
        $routes->group('home-visits', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'HomeVisitController', 'koordinator.home_visits'));
        $routes->group('case-conferences', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'CaseConferenceController', 'koordinator.case_conferences'));

        $routes->group('assignments', ['filter' => 'permission:any,manage_bk_assignments,view_bk_assignments'], function ($routes) {
            $routes->get('/', 'AssignmentController::index', ['as' => 'koordinator.assignments.index']);
            $routes->get('create', 'AssignmentController::create', ['as' => 'koordinator.assignments.create']);
            $routes->post('store', 'AssignmentController::store', ['as' => 'koordinator.assignments.store']);
            $routes->get('show/(:num)', 'AssignmentController::show/$1', ['as' => 'koordinator.assignments.show']);
            $routes->get('edit/(:num)', 'AssignmentController::edit/$1', ['as' => 'koordinator.assignments.edit']);
            $routes->post('update/(:num)', 'AssignmentController::update/$1', ['as' => 'koordinator.assignments.update']);
            $routes->post('status/(:num)', 'AssignmentController::status/$1', ['as' => 'koordinator.assignments.status']);
            $routes->post('delete/(:num)', 'AssignmentController::delete/$1', ['as' => 'koordinator.assignments.delete']);
        });

        // Assessments (izin: manage_assessments)
        $routes->group('assessments', ['filter' => 'permission:manage_assessments'], static function ($routes) {
            $routes->get('/', 'AssessmentController::index', ['as' => 'koordinator.assessments.index']);

            $routes->get('create', 'AssessmentController::create', ['as' => 'koordinator.assessments.create']);
            $routes->post('store', 'AssessmentController::store', ['as' => 'koordinator.assessments.store']);

            $routes->get('show/(:num)', 'AssessmentController::show/$1', ['as' => 'koordinator.assessments.show']);
            $routes->get('edit/(:num)', 'AssessmentController::edit/$1', ['as' => 'koordinator.assessments.edit']);
            $routes->post('update/(:num)', 'AssessmentController::update/$1', ['as' => 'koordinator.assessments.update']);

            $routes->post('delete/(:num)', 'AssessmentController::delete/$1', ['as' => 'koordinator.assessments.delete']);
            $routes->post('duplicate/(:num)', 'AssessmentController::duplicate/$1', ['as' => 'koordinator.assessments.duplicate']);
            $routes->post('publish/(:num)', 'AssessmentController::publish/$1', ['as' => 'koordinator.assessments.publish']);
            $routes->post('unpublish/(:num)', 'AssessmentController::unpublish/$1', ['as' => 'koordinator.assessments.unpublish']);

            $routes->get('(:num)/questions', 'AssessmentController::questions/$1', ['as' => 'koordinator.assessments.questions']);
            $routes->post('(:num)/questions/add', 'AssessmentController::addQuestion/$1', ['as' => 'koordinator.assessments.questions.add']);
            $routes->post('(:num)/questions/(:num)/update', 'AssessmentController::updateQuestion/$1/$2', ['as' => 'koordinator.assessments.questions.update']);
            $routes->post('(:num)/questions/(:num)/delete', 'AssessmentController::deleteQuestion/$1/$2', ['as' => 'koordinator.assessments.questions.delete']);

            $routes->get('(:num)/assign', 'AssessmentController::assign/$1', ['as' => 'koordinator.assessments.assign']);
            $routes->post('(:num)/assign/process', 'AssessmentController::processAssign/$1', ['as' => 'koordinator.assessments.assign.process']);
            $routes->post('(:num)/assign/revoke', 'AssessmentController::revokeAssign/$1', ['as' => 'koordinator.assessments.assign.revoke']);
            $routes->post('(:num)/assign/sync', 'AssessmentController::syncAssignments/$1', ['as' => 'koordinator.assessments.assign.sync']);

            $routes->get('(:num)/results', 'AssessmentController::results/$1', ['as' => 'koordinator.assessments.results']);
            $routes->get('(:num)/results/export', 'AssessmentController::exportResults/$1', ['as' => 'koordinator.assessments.results.export']);
            $routes->get('(:num)/results/(:num)', 'AssessmentController::resultDetail/$1/$2', ['as' => 'koordinator.assessments.results.detail']);

            $routes->post('grade/submit', 'AssessmentController::submitGrade', ['as' => 'koordinator.assessments.grade.submit']);
            $routes->post('grade/answer', 'AssessmentController::gradeAnswerAction', ['as' => 'koordinator.assessments.grade.answer']);
            $routes->post('review/(:num)', 'AssessmentController::reviewSave/$1', ['as' => 'koordinator.assessments.review.save']);
            $routes->post('(:num)/results/(:num)/ungrade', 'AssessmentController::ungradeResult/$1/$2', ['as' => 'koordinator.assessments.results.ungrade']);
            $routes->post('(:num)/results/(:num)/delete', 'AssessmentController::deleteResult/$1/$2', ['as' => 'koordinator.assessments.results.delete']);
        });

        // Career & University info (Koordinator = paritas penuh dengan Guru BK)
        $routes->group('career-info', ['filter' => 'permission:manage_career_info'], static function ($routes) {
            $routes->get('/', 'CareerInfoController::index', ['as' => 'koordinator.career.index']);
            $routes->get('careers', 'CareerInfoController::index');
            $routes->get('careers/create', 'CareerInfoController::createCareer', ['as' => 'koordinator.career.create']);
            $routes->post('careers/store', 'CareerInfoController::storeCareer', ['as' => 'koordinator.career.store']);
            $routes->get('careers/edit/(:num)', 'CareerInfoController::editCareer/$1', ['as' => 'koordinator.career.edit']);
            $routes->post('careers/update/(:num)', 'CareerInfoController::updateCareer/$1', ['as' => 'koordinator.career.update']);
            $routes->post('careers/delete/(:num)', 'CareerInfoController::deleteCareer/$1', ['as' => 'koordinator.career.delete']);
            $routes->post('careers/toggle/(:num)', 'CareerInfoController::toggleCareer/$1', ['as' => 'koordinator.career.toggle']);
            $routes->get('careers/detail/(:num)', 'CareerInfoController::showCareer/$1', ['as' => 'koordinator.career.show']);

            $routes->get('student-choices', 'CareerInfoController::studentChoices', ['as' => 'koordinator.career.choices']);

            $routes->get('universities', 'CareerInfoController::universities', ['as' => 'koordinator.university.index']);
            $routes->get('universities/create', 'CareerInfoController::createUniversity', ['as' => 'koordinator.university.create']);
            $routes->post('universities/store', 'CareerInfoController::storeUniversity', ['as' => 'koordinator.university.store']);
            $routes->get('universities/edit/(:num)', 'CareerInfoController::editUniversity/$1', ['as' => 'koordinator.university.edit']);
            $routes->post('universities/update/(:num)', 'CareerInfoController::updateUniversity/$1', ['as' => 'koordinator.university.update']);
            $routes->post('universities/delete/(:num)', 'CareerInfoController::deleteUniversity/$1', ['as' => 'koordinator.university.delete']);
            $routes->post('universities/toggle/(:num)', 'CareerInfoController::toggleUniversity/$1', ['as' => 'koordinator.university.toggle']);
            $routes->get('universities/detail/(:num)', 'CareerInfoController::showUniversity/$1', ['as' => 'koordinator.university.show']);
        });

        // Reports:
        // - Koordinator akses agregat -> view_reports_aggregate
        // - Download agregat -> generate_reports_aggregate
        $routes->group('reports', ['filter' => 'permission:view_reports_aggregate'], function ($routes) {
            $routes->get('/', 'ReportController::index', ['as' => 'koordinator.reports']);
            $routes->get('preview', 'ReportController::preview', ['as' => 'koordinator.reports.preview']);

            $routes->match(['GET', 'POST'], 'download', 'ReportController::download', [
                'filter' => 'permission:generate_reports_aggregate',
                'as'     => 'koordinator.reports.download',
            ]);
        });
    });
});

// ===============================
// COUNSELOR (Guru BK) (role locked)
// ===============================
// PERBAIKAN: hindari 'auth,role:guru bk' -> nested group
$routes->group('counselor', [
    'filter'    => 'auth',
    'namespace' => 'App\Controllers\Counselor'
], function ($routes) {

    $routes->group('', ['filter' => 'role:guru bk,counselor'], function ($routes) {

        // Dashboard (izin: view_dashboard)
        $routes->get('dashboard', 'DashboardController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'counselor.dashboard'
        ]);
        $routes->get('dashboard/getQuickStats', 'DashboardController::getQuickStats', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'counselor.dashboard.stats'
        ]);

        $routes->group('notifications', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'NotificationController::index', ['as' => 'counselor.notifications']);
            $routes->get('unread', 'NotificationController::unread', ['as' => 'counselor.notifications.unread']);
            $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1', ['as' => 'counselor.notifications.read']);
            $routes->post('mark-all-read', 'NotificationController::markAllAsRead', ['as' => 'counselor.notifications.read_all']);
            $routes->post('delete/(:num)', 'NotificationController::delete/$1', ['as' => 'counselor.notifications.delete']);
            $routes->post('delete-all', 'NotificationController::deleteAll', ['as' => 'counselor.notifications.delete_all']);
            $routes->get('count', 'NotificationController::getUnreadCount', ['as' => 'counselor.notifications.count']);
        });

        // Tempat Sampah (pemulihan soft delete) - hanya data milik penghapus.
        $routes->group('trash', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'TrashController::index', ['as' => 'counselor.trash']);
            $routes->post('restore', 'TrashController::restore', ['as' => 'counselor.trash.restore']);
            $routes->post('force-delete', 'TrashController::forceDelete', ['as' => 'counselor.trash.force']);
        });

        $routes->group('messages', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'MessageController::index', ['as' => 'counselor.messages']);
            $routes->get('summary', 'MessageController::summary', ['as' => 'counselor.messages.summary']);
            $routes->get('chat/(:num)', 'MessageController::chat/$1', ['as' => 'counselor.messages.chat']);
            $routes->get('poll/(:num)', 'MessageController::poll/$1', ['as' => 'counselor.messages.poll']);
            $routes->post('send/(:num)', 'MessageController::send/$1', ['as' => 'counselor.messages.send']);
            $routes->post('delete', 'MessageController::delete', ['as' => 'counselor.messages.delete']);
            $routes->post('delete-all', 'MessageController::deleteAll', ['as' => 'counselor.messages.delete_all']);
            $routes->get('attachment/(:num)', 'MessageController::downloadAttachment/$1', ['as' => 'counselor.messages.attachment']);
        });

        $routes->get('sessions', static fn() => redirect()->to('/counselor/counseling'), ['as' => 'counselor.sessions']);
        $routes->get('sessions/(:any)', static fn() => redirect()->to('/counselor/counseling'));
        $routes->get('schedule', static fn() => redirect()->to('/counselor/counseling'), ['as' => 'counselor.schedule']);
        $routes->get('schedule/(:any)', static fn() => redirect()->to('/counselor/counseling'));

        // Students (binaan)
        $routes->group('students', ['filter' => 'permission:view_all_students'], function ($routes) {
            $routes->get('/', 'StudentController::index', ['as' => 'counselor.students']);
            $routes->get('(:num)', 'StudentController::show/$1', ['as' => 'counselor.students.show']);
            $routes->get('(:num)/edit', 'StudentController::edit/$1', ['as' => 'counselor.students.edit']);
            $routes->post('(:num)', 'StudentController::update/$1', ['as' => 'counselor.students.update']);
            $routes->get('detail/(:num)', 'StudentController::detail/$1', ['as' => 'counselor.students.detail']);
            $routes->post('reset-password/(:num)', 'StudentController::resetStudentPassword/$1', ['as' => 'counselor.students.reset_password']);
        });

        // Akun Orang Tua siswa binaan (Guru BK: C,R,U,D* sesuai Matriks CRUD)
        $routes->group('parents', ['filter' => 'permission:view_all_students'], function ($routes) {
            $routes->get('/', 'ParentController::index', ['as' => 'counselor.parents.index']);
            $routes->get('(:num)', 'ParentController::show/$1', ['as' => 'counselor.parents.show']);
            $routes->get('create', 'ParentController::create', [
                'filter' => 'permission:manage_bk_services',
                'as'     => 'counselor.parents.create',
            ]);
            $routes->post('store', 'ParentController::store', [
                'filter' => 'permission:manage_bk_services',
                'as'     => 'counselor.parents.store',
            ]);
            $routes->get('edit/(:num)', 'ParentController::edit/$1', [
                'filter' => 'permission:manage_bk_services',
                'as'     => 'counselor.parents.edit',
            ]);
            $routes->post('update/(:num)', 'ParentController::update/$1', [
                'filter' => 'permission:manage_bk_services',
                'as'     => 'counselor.parents.update',
            ]);
            $routes->post('delete/(:num)', 'ParentController::delete/$1', [
                'filter' => 'permission:manage_bk_services',
                'as'     => 'counselor.parents.delete',
            ]);
            $routes->post('reset-password/(:num)', 'ParentController::resetParentPassword/$1', [
                'filter' => 'permission:manage_bk_services',
                'as'     => 'counselor.parents.reset_password',
            ]);
        });

        // Fitur final pengembangan BK untuk Guru BK.
        $routes->group('consultations', ['filter' => 'permission:any,manage_consultation_complaints,review_consultation_complaints,submit_consultation_complaints'], function ($routes) {
            $routes->get('/', 'ConsultationController::index', ['as' => 'counselor.consultations.index']);
            $routes->get('create', 'ConsultationController::create', ['as' => 'counselor.consultations.create']);
            $routes->post('store', 'ConsultationController::store', ['as' => 'counselor.consultations.store']);
            $routes->get('show/(:num)', 'ConsultationController::show/$1', ['as' => 'counselor.consultations.show']);
            $routes->get('edit/(:num)', 'ConsultationController::edit/$1', ['as' => 'counselor.consultations.edit']);
            $routes->post('update/(:num)', 'ConsultationController::update/$1', ['as' => 'counselor.consultations.update']);
            $routes->post('delete/(:num)', 'ConsultationController::delete/$1', ['as' => 'counselor.consultations.delete']);
            $routes->post('review/(:num)', 'ConsultationController::review/$1', ['as' => 'counselor.consultations.review']);
            $routes->get('attachment/(:num)', 'ConsultationController::downloadAttachment/$1', ['as' => 'counselor.consultations.attachment']);
            $routes->post('attachment-delete/(:num)', 'ConsultationController::deleteAttachment/$1', ['as' => 'counselor.consultations.attachmentDelete']);
        });

        $bkServiceRoutes = static function ($routes, string $controller, string $alias): void {
            $routes->get('/', $controller . '::index', ['as' => $alias . '.index']);
            $routes->get('create', $controller . '::create', ['as' => $alias . '.create']);
            $routes->post('store', $controller . '::store', ['as' => $alias . '.store']);
            $routes->get('show/(:num)', $controller . '::show/$1', ['as' => $alias . '.show']);
            $routes->get('edit/(:num)', $controller . '::edit/$1', ['as' => $alias . '.edit']);
            $routes->post('update/(:num)', $controller . '::update/$1', ['as' => $alias . '.update']);
            $routes->post('delete/(:num)', $controller . '::delete/$1', ['as' => $alias . '.delete']);
            $routes->post('note/(:num)', $controller . '::addNote/$1', ['as' => $alias . '.note']);
            $routes->post('participants/(:num)', $controller . '::updateParticipant/$1', ['as' => $alias . '.participant']);
            $routes->post('participant-add/(:num)', $controller . '::addParticipant/$1', ['as' => $alias . '.participantAdd']);
            $routes->post('participant-delete/(:num)', $controller . '::deleteParticipant/$1', ['as' => $alias . '.participantDelete']);
            $routes->post('note-delete/(:num)', $controller . '::deleteNote/$1', ['as' => $alias . '.noteDelete']);
        };

        $routes->group('guidance', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'GuidanceController', 'counselor.guidance'));
        $routes->group('counseling', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'CounselingController', 'counselor.counseling'));
        $routes->group('parent-collaborations', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'ParentCollaborationController', 'counselor.parent_collaborations'));
        $routes->group('home-visits', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'HomeVisitController', 'counselor.home_visits'));
        $routes->group('case-conferences', ['filter' => 'permission:any,manage_bk_services,view_bk_services'], static fn($routes) => $bkServiceRoutes($routes, 'CaseConferenceController', 'counselor.case_conferences'));

        $routes->group('assignments', ['filter' => 'permission:view_bk_assignments'], function ($routes) {
            $routes->get('/', 'AssignmentController::index', ['as' => 'counselor.assignments.index']);
            $routes->get('show/(:num)', 'AssignmentController::show/$1', ['as' => 'counselor.assignments.show']);
            $routes->post('status/(:num)', 'AssignmentController::status/$1', ['as' => 'counselor.assignments.status']);
        });

        // Assessments
        $routes->group('assessments', ['filter' => 'permission:manage_assessments'], function ($routes) {
            $routes->get('/', 'AssessmentController::index', ['as' => 'counselor.assessments']);
            $routes->get('create', 'AssessmentController::create', ['as' => 'counselor.assessments.create']);
            $routes->post('store', 'AssessmentController::store', ['as' => 'counselor.assessments.store']);
            $routes->get('(:num)', 'AssessmentController::show/$1', ['as' => 'counselor.assessments.show']);
            $routes->get('(:num)/edit', 'AssessmentController::edit/$1', ['as' => 'counselor.assessments.edit']);
            $routes->post('(:num)/update', 'AssessmentController::update/$1', ['as' => 'counselor.assessments.update']);
            $routes->post('(:num)/delete', 'AssessmentController::delete/$1', ['as' => 'counselor.assessments.delete']);

            $routes->get('(:num)/questions', 'AssessmentController::questions/$1', ['as' => 'counselor.assessments.questions']);
            $routes->post('(:num)/questions/add', 'AssessmentController::addQuestion/$1', ['as' => 'counselor.assessments.questions.add']);
            $routes->post('(:num)/questions/(:num)/update', 'AssessmentController::updateQuestion/$1/$2', ['as' => 'counselor.assessments.questions.update']);
            $routes->post('(:num)/questions/(:num)/delete', 'AssessmentController::deleteQuestion/$1/$2', ['as' => 'counselor.assessments.questions.delete']);

            $routes->get('(:num)/assign', 'AssessmentController::assign/$1', ['as' => 'counselor.assessments.assign']);
            $routes->post('(:num)/assign/process', 'AssessmentController::processAssign/$1', ['as' => 'counselor.assessments.assign.process']);
            $routes->post('(:num)/assign/sync', 'AssessmentController::syncAssignments/$1', ['as' => 'counselor.assessments.assign.sync']);
            $routes->post('(:num)/assign/revoke', 'AssessmentController::revokeAssign/$1', ['as' => 'counselor.assessments.assign.revoke']);

            $routes->get('(:num)/results', 'AssessmentController::results/$1', ['as' => 'counselor.assessments.results']);
            $routes->get('(:num)/results/(:num)', 'AssessmentController::resultDetail/$1/$2', ['as' => 'counselor.assessments.result.detail']);
            $routes->get('(:num)/grading', 'AssessmentController::grading/$1', ['as' => 'counselor.assessments.grading']);
            $routes->post('grade/submit', 'AssessmentController::submitGrade', ['as' => 'counselor.assessments.grade.submit']);
            $routes->post('(:num)/results/(:num)/ungrade', 'AssessmentController::ungradeResult/$1/$2', ['as' => 'counselor.assessments.result.ungrade']);
            $routes->post('(:num)/results/(:num)/delete', 'AssessmentController::deleteResult/$1/$2', ['as' => 'counselor.assessments.results.delete']);
            $routes->post('answers/grade', 'AssessmentController::gradeAnswerAction', ['as' => 'counselor.assessments.answer.grade']);

            $routes->post('(:num)/publish', 'AssessmentController::publish/$1', ['as' => 'counselor.assessments.publish']);
            $routes->post('(:num)/unpublish', 'AssessmentController::unpublish/$1', ['as' => 'counselor.assessments.unpublish']);
            $routes->get('(:num)/publish', 'AssessmentController::publish/$1');
            $routes->get('(:num)/unpublish', 'AssessmentController::unpublish/$1');

            $routes->post('(:num)/duplicate', 'AssessmentController::duplicate/$1', ['as' => 'counselor.assessments.duplicate']);
        });

        // Reports:
        // - Guru BK akses individual -> view_reports_individual
        // - Download individual -> generate_reports_individual
        $routes->group('reports', ['filter' => 'permission:view_reports_individual'], function ($routes) {
            $routes->get('/', 'ReportController::index', ['as' => 'counselor.reports']);
            $routes->get('preview', 'ReportController::preview', ['as' => 'counselor.reports.preview']);
            $routes->get('download', 'ReportController::download', [
                'filter' => 'permission:generate_reports_individual',
                'as'     => 'counselor.reports.download'
            ]);

            $routes->get('session-summary', static function () {
                return redirect()->to('/counselor/reports?type=sessions');
            }, ['as' => 'counselor.reports.session']);

            $routes->get('student/(:num)', static function ($studentId) {
                return redirect()->to('/counselor/reports/preview?type=students&student_id=' . $studentId);
            }, ['as' => 'counselor.reports.student']);

            $routes->post('generate-pdf', static function () {
                $q  = service('request')->getPost() ?? [];
                $qs = http_build_query($q);
                $url = '/counselor/reports/download?format=pdf' . ($qs ? '&' . $qs : '');
                return redirect()->to($url);
            }, ['filter' => 'permission:generate_reports_individual', 'as' => 'counselor.reports.pdf']);

            $routes->post('generate-excel', static function () {
                $q  = service('request')->getPost() ?? [];
                $qs = http_build_query($q);
                $url = '/counselor/reports/download?format=xlsx' . ($qs ? '&' . $qs : '');
                return redirect()->to($url);
            }, ['filter' => 'permission:generate_reports_individual', 'as' => 'counselor.reports.excel']);
        });

        // Career & University info
        $routes->group('career-info', ['filter' => 'permission:manage_career_info'], static function ($routes) {
            $routes->get('/', 'CareerInfoController::index', ['as' => 'counselor.career.index']);
            $routes->get('careers', 'CareerInfoController::index');
            $routes->get('careers/create', 'CareerInfoController::createCareer', ['as' => 'counselor.career.create']);
            $routes->post('careers/store', 'CareerInfoController::storeCareer', ['as' => 'counselor.career.store']);
            $routes->get('careers/edit/(:num)', 'CareerInfoController::editCareer/$1', ['as' => 'counselor.career.edit']);
            $routes->post('careers/update/(:num)', 'CareerInfoController::updateCareer/$1', ['as' => 'counselor.career.update']);
            $routes->post('careers/delete/(:num)', 'CareerInfoController::deleteCareer/$1', ['as' => 'counselor.career.delete']);
            $routes->post('careers/toggle/(:num)', 'CareerInfoController::toggleCareer/$1', ['as' => 'counselor.career.toggle']);
            $routes->get('careers/detail/(:num)', 'CareerInfoController::showCareer/$1', ['as' => 'counselor.career.show']);

            $routes->get('student-choices', 'CareerInfoController::studentChoices', ['as' => 'counselor.career.choices']);

            $routes->get('universities', 'CareerInfoController::universities', ['as' => 'counselor.university.index']);
            $routes->get('universities/create', 'CareerInfoController::createUniversity', ['as' => 'counselor.university.create']);
            $routes->post('universities/store', 'CareerInfoController::storeUniversity', ['as' => 'counselor.university.store']);
            $routes->get('universities/edit/(:num)', 'CareerInfoController::editUniversity/$1', ['as' => 'counselor.university.edit']);
            $routes->post('universities/update/(:num)', 'CareerInfoController::updateUniversity/$1', ['as' => 'counselor.university.update']);
            $routes->post('universities/delete/(:num)', 'CareerInfoController::deleteUniversity/$1', ['as' => 'counselor.university.delete']);
            $routes->post('universities/toggle/(:num)', 'CareerInfoController::toggleUniversity/$1', ['as' => 'counselor.university.toggle']);
            $routes->get('universities/detail/(:num)', 'CareerInfoController::showUniversity/$1', ['as' => 'counselor.university.show']);
        });
    });
});

// ===============================
// HOMEROOM (Wali Kelas) (role locked)
// ===============================
$routes->group('homeroom', [
    'filter'    => 'auth',
    'namespace' => 'App\Controllers\HomeroomTeacher',
], function ($routes) {

    $routes->group('', ['filter' => 'role:wali kelas,homeroom'], function ($routes) {

        $routes->get('dashboard', 'DashboardController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'homeroom.dashboard'
        ]);
        $routes->get('dashboard/stats', 'DashboardController::getStats', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'homeroom.dashboard.stats'
        ]);

        $routes->group('notifications', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'NotificationController::index', ['as' => 'homeroom.notifications']);
            $routes->get('unread', 'NotificationController::unread', ['as' => 'homeroom.notifications.unread']);
            $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1', ['as' => 'homeroom.notifications.read']);
            $routes->post('mark-all-read', 'NotificationController::markAllAsRead', ['as' => 'homeroom.notifications.read_all']);
            $routes->post('delete/(:num)', 'NotificationController::delete/$1', ['as' => 'homeroom.notifications.delete']);
            $routes->post('delete-all', 'NotificationController::deleteAll', ['as' => 'homeroom.notifications.delete_all']);
            $routes->get('count', 'NotificationController::getUnreadCount', ['as' => 'homeroom.notifications.count']);
        });

        // Tempat Sampah (pemulihan soft delete) - hanya data milik penghapus.
        $routes->group('trash', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'TrashController::index', ['as' => 'homeroom.trash']);
            $routes->post('restore', 'TrashController::restore', ['as' => 'homeroom.trash.restore']);
            $routes->post('force-delete', 'TrashController::forceDelete', ['as' => 'homeroom.trash.force']);
        });

        $routes->group('messages', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'MessageController::index', ['as' => 'homeroom.messages']);
            $routes->get('summary', 'MessageController::summary', ['as' => 'homeroom.messages.summary']);
            $routes->get('chat/(:num)', 'MessageController::chat/$1', ['as' => 'homeroom.messages.chat']);
            $routes->get('poll/(:num)', 'MessageController::poll/$1', ['as' => 'homeroom.messages.poll']);
            $routes->post('send/(:num)', 'MessageController::send/$1', ['as' => 'homeroom.messages.send']);
            $routes->post('delete', 'MessageController::delete', ['as' => 'homeroom.messages.delete']);
            $routes->post('delete-all', 'MessageController::deleteAll', ['as' => 'homeroom.messages.delete_all']);
            $routes->get('attachment/(:num)', 'MessageController::downloadAttachment/$1', ['as' => 'homeroom.messages.attachment']);
        });


        // Fitur final pengembangan BK untuk Wali Kelas.
        $routes->group('consultations', ['filter' => 'permission:submit_consultation_complaints'], function ($routes) {
            $routes->get('/', 'ConsultationController::index', ['as' => 'homeroom.consultations.index']);
            $routes->get('create', 'ConsultationController::create', ['as' => 'homeroom.consultations.create']);
            $routes->post('store', 'ConsultationController::store', ['as' => 'homeroom.consultations.store']);
            $routes->get('show/(:num)', 'ConsultationController::show/$1', ['as' => 'homeroom.consultations.show']);
            $routes->get('edit/(:num)', 'ConsultationController::edit/$1', ['as' => 'homeroom.consultations.edit']);
            $routes->post('update/(:num)', 'ConsultationController::update/$1', ['as' => 'homeroom.consultations.update']);
            $routes->post('delete/(:num)', 'ConsultationController::delete/$1', ['as' => 'homeroom.consultations.delete']);
            $routes->get('attachment/(:num)', 'ConsultationController::downloadAttachment/$1', ['as' => 'homeroom.consultations.attachment']);
            $routes->post('attachment-delete/(:num)', 'ConsultationController::deleteAttachment/$1', ['as' => 'homeroom.consultations.attachmentDelete']);
        });

        $bkReadRoutes = static function ($routes, string $controller, string $alias): void {
            $routes->get('/', $controller . '::index', ['as' => $alias . '.index']);
            $routes->get('show/(:num)', $controller . '::show/$1', ['as' => $alias . '.show']);
        };

        // Halaman terpadu Jadwal Kegiatan/Acara BK (+ Riwayat di dalam halaman).
        $routes->get('jadwal-bk', 'BkScheduleController::index', [
            'filter' => 'permission:view_bk_services',
            'as'     => 'homeroom.bk_schedule'
        ]);
        $routes->get('jadwal-bk/riwayat', 'BkScheduleController::history', [
            'filter' => 'permission:view_bk_services',
            'as'     => 'homeroom.bk_schedule.history'
        ]);

        $routes->group('guidance', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'GuidanceController', 'homeroom.guidance'));
        $routes->group('counseling', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'CounselingController', 'homeroom.counseling'));
        $routes->group('parent-collaborations', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'ParentCollaborationController', 'homeroom.parent_collaborations'));
        $routes->group('home-visits', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'HomeVisitController', 'homeroom.home_visits'));
        $routes->group('case-conferences', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'CaseConferenceController', 'homeroom.case_conferences'));

        // Reports (wali kelas -> individual)
        $routes->group('reports', ['filter' => 'permission:view_reports_individual'], function ($routes) {
            $routes->get('/', 'ClassReportController::index', ['as' => 'homeroom.reports']);
            $routes->get('preview', 'ClassReportController::preview', ['as' => 'homeroom.reports.preview']);
            $routes->match(['GET', 'POST'], 'download', 'ClassReportController::download', [
                'filter' => 'permission:generate_reports_individual',
                'as'     => 'homeroom.reports.download'
            ]);

            $routes->get('data', static function () {
                $q  = service('request')->getGet() ?? [];
                $qs = http_build_query($q);
                return redirect()->to('/homeroom/reports/preview' . ($qs ? ('?' . $qs) : ''));
            }, ['as' => 'homeroom.reports.data']);
        });

        $routes->get('my-class', 'ClassController::myClass', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'homeroom.myclass'
        ]);

        $routes->get('sessions', 'StudentSessionsController::sessions', [
            'filter' => 'permission:view_counseling_sessions',
            'as'     => 'homeroom.sessions'
        ]);

        // Data siswa wali kelas
        $routes->get('students', 'StudentController::index', [
            'filter' => 'permission:view_all_students',
            'as'     => 'homeroom.students'
        ]);
        $routes->get('students/index', 'StudentController::index', [
            'filter' => 'permission:view_all_students',
            'as'     => 'homeroom.students.index'
        ]);
        $routes->get('students/create', 'StudentController::create', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.students.create'
        ]);
        $routes->post('students/store', 'StudentController::store', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.students.store'
        ]);
        $routes->get('students/edit/(:num)', 'StudentController::edit/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.students.edit'
        ]);
        $routes->post('students/update/(:num)', 'StudentController::update/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.students.update'
        ]);
        $routes->post('students/delete/(:num)', 'StudentController::delete/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.students.delete'
        ]);
        $routes->post('students/reset-password/(:num)', 'StudentController::resetStudentPassword/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.students.reset_password'
        ]);
        $routes->get('students/export', 'StudentController::export', [
            'filter' => 'permission:import_export_data',
            'as'     => 'homeroom.students.export'
        ]);
        $routes->get('students/import', 'StudentImportController::import', [
            'filter' => 'permission:import_export_data',
            'as'     => 'homeroom.students.import'
        ]);
        $routes->post('students/do-import', 'StudentImportController::doImport', [
            'filter' => 'permission:import_export_data',
            'as'     => 'homeroom.students.do_import'
        ]);
        $routes->get('students/download-template', 'StudentImportController::downloadTemplate', [
            'filter' => 'permission:import_export_data',
            'as'     => 'homeroom.students.template'
        ]);
        $routes->get('parents', 'StudentController::parents', [
            'filter' => 'permission:view_all_students',
            'as'     => 'homeroom.parents'
        ]);
        $routes->get('parents/create', 'StudentController::createParent', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.parents.create'
        ]);
        $routes->post('parents/store', 'StudentController::storeParent', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.parents.store'
        ]);
        $routes->get('parents/(:num)', 'StudentController::showParent/$1', [
            'filter' => 'permission:view_all_students',
            'as'     => 'homeroom.parents.show'
        ]);
        $routes->get('parents/edit/(:num)', 'StudentController::editParent/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.parents.edit'
        ]);
        $routes->post('parents/update/(:num)', 'StudentController::updateParent/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.parents.update'
        ]);
        $routes->post('parents/delete/(:num)', 'StudentController::deleteParent/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.parents.delete'
        ]);
        $routes->post('parents/reset-password/(:num)', 'StudentController::resetParentPassword/$1', [
            'filter' => 'permission:manage_students',
            'as'     => 'homeroom.parents.reset_password'
        ]);
        $routes->get('students/(:num)', 'StudentController::show/$1', [
            'filter' => 'permission:view_all_students',
            'as'     => 'homeroom.students.show'
        ]);

        $routes->get('students/(:num)/sessions', 'StudentSessionsController::sessions/$1', [
            'filter' => 'permission:view_counseling_sessions',
            'as'     => 'homeroom.students.sessions'
        ]);
        $routes->get('students/(:num)/sessions/(:num)', 'StudentSessionsController::sessionDetail/$1/$2', [
            'filter' => 'permission:view_counseling_sessions',
            'as'     => 'homeroom.students.sessions.detail'
        ]);

        // Info Karier dan Studi Lanjut — Wali Kelas HANYA BACA (R*) sesuai Matriks CRUD.
        // Mengelola data karier/perguruan tinggi = wewenang Koordinator BK & Guru BK,
        // jadi TIDAK ada rute tambah/edit/hapus/aktif/publikasi di sini (penegakan sisi server).
        $routes->get('career-info', 'CareerInfoController::index', [
            'filter' => 'permission:view_career_info',
            'as'     => 'homeroom.career.index'
        ]);
        $routes->get('career-info/universities', 'CareerInfoController::universities', [
            'filter' => 'permission:view_career_info',
            'as'     => 'homeroom.university.index'
        ]);
        $routes->get('career-info/student-choices', 'CareerInfoController::studentChoices', [
            'filter' => 'permission:view_career_info',
            'as'     => 'homeroom.career.choices'
        ]);
        $routes->get('career-info/careers/detail/(:num)', 'CareerInfoController::showCareer/$1', [
            'filter' => 'permission:view_career_info',
            'as'     => 'homeroom.career.show'
        ]);
        $routes->get('career-info/universities/detail/(:num)', 'CareerInfoController::showUniversity/$1', [
            'filter' => 'permission:view_career_info',
            'as'     => 'homeroom.university.show'
        ]);
    });
});

// ===============================
// STUDENT (Siswa) (role locked)
// ===============================
$routes->group('student', [
    'filter'    => 'auth',
    'namespace' => 'App\Controllers\Student'
], function ($routes) {

    $routes->group('', ['filter' => 'role:siswa,student'], function ($routes) {

        $routes->get('/', 'DashboardController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'student.home'
        ]);
        $routes->get('dashboard', 'DashboardController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'student.dashboard'
        ]);

        $routes->group('notifications', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'NotificationController::index', ['as' => 'student.notifications']);
            $routes->get('unread', 'NotificationController::unread', ['as' => 'student.notifications.unread']);
            $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1', ['as' => 'student.notifications.read']);
            $routes->post('mark-all-read', 'NotificationController::markAllAsRead', ['as' => 'student.notifications.read_all']);
            $routes->post('delete/(:num)', 'NotificationController::delete/$1', ['as' => 'student.notifications.delete']);
            $routes->post('delete-all', 'NotificationController::deleteAll', ['as' => 'student.notifications.delete_all']);
            $routes->get('count', 'NotificationController::getUnreadCount', ['as' => 'student.notifications.count']);
        });

        // Tempat Sampah (pemulihan soft delete) - hanya data milik penghapus.
        $routes->group('trash', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'TrashController::index', ['as' => 'student.trash']);
            $routes->post('restore', 'TrashController::restore', ['as' => 'student.trash.restore']);
            $routes->post('force-delete', 'TrashController::forceDelete', ['as' => 'student.trash.force']);
        });

        $routes->group('messages', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'MessageController::index', ['as' => 'student.messages']);
            $routes->get('summary', 'MessageController::summary', ['as' => 'student.messages.summary']);
            $routes->get('chat/(:num)', 'MessageController::chat/$1', ['as' => 'student.messages.chat']);
            $routes->get('poll/(:num)', 'MessageController::poll/$1', ['as' => 'student.messages.poll']);
            $routes->post('send/(:num)', 'MessageController::send/$1', ['as' => 'student.messages.send']);
            $routes->post('delete', 'MessageController::delete', ['as' => 'student.messages.delete']);
            $routes->post('delete-all', 'MessageController::deleteAll', ['as' => 'student.messages.delete_all']);
            $routes->get('attachment/(:num)', 'MessageController::downloadAttachment/$1', ['as' => 'student.messages.attachment']);
        });

        // Data pribadi siswa
        $routes->get('profile', 'ProfileController::index', [
            'filter' => 'permission:view_student_portfolio',
            'as'     => 'student.profile'
        ]);
        $routes->get('profile/edit', static fn() => redirect()->to('/student/profile?mode=edit'), [
            'filter' => 'permission:view_student_portfolio',
            'as'     => 'student.profile.edit'
        ]);
        $routes->post('profile/update', 'ProfileController::update', [
            'filter' => 'permission:view_student_portfolio',
            'as'     => 'student.profile.update'
        ]);

        $routes->get('staff', 'StaffController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'student.staff'
        ]);


        // Fitur final pengembangan BK untuk Siswa.
        $routes->group('consultations', ['filter' => 'permission:submit_consultation_complaints'], function ($routes) {
            $routes->get('/', 'ConsultationController::index', ['as' => 'student.consultations.index']);
            $routes->get('create', 'ConsultationController::create', ['as' => 'student.consultations.create']);
            $routes->post('store', 'ConsultationController::store', ['as' => 'student.consultations.store']);
            $routes->get('show/(:num)', 'ConsultationController::show/$1', ['as' => 'student.consultations.show']);
            $routes->get('edit/(:num)', 'ConsultationController::edit/$1', ['as' => 'student.consultations.edit']);
            $routes->post('update/(:num)', 'ConsultationController::update/$1', ['as' => 'student.consultations.update']);
            $routes->post('delete/(:num)', 'ConsultationController::delete/$1', ['as' => 'student.consultations.delete']);
            $routes->get('attachment/(:num)', 'ConsultationController::downloadAttachment/$1', ['as' => 'student.consultations.attachment']);
            $routes->post('attachment-delete/(:num)', 'ConsultationController::deleteAttachment/$1', ['as' => 'student.consultations.attachmentDelete']);
        });
        // Halaman terpadu Jadwal Kegiatan/Acara BK (jadwal saja, tanpa detail).
        $routes->get('jadwal-bk', 'BkScheduleController::index', [
            'filter' => 'permission:view_bk_services',
            'as'     => 'student.bk_schedule'
        ]);

        $routes->group('guidance', ['filter' => 'permission:view_bk_services'], function ($routes) {
            $routes->get('/', 'GuidanceController::index', ['as' => 'student.guidance.index']);
            $routes->get('show/(:num)', 'GuidanceController::show/$1', ['as' => 'student.guidance.show']);
        });
        $routes->group('counseling', ['filter' => 'permission:view_bk_services'], function ($routes) {
            $routes->get('/', 'CounselingController::index', ['as' => 'student.counseling.index']);
            $routes->get('show/(:num)', 'CounselingController::show/$1', ['as' => 'student.counseling.show']);
        });

        // Halaman lama "Sesi/Ajukan Konseling" dilebur ke "Jadwal Kegiatan/Acara BK".
        // Pengajuan kini lewat Konsultasi & Pengaduan. Rute lama redirect agar tautan
        // lama tetap aman.
        $routes->get('schedule', static fn() => redirect()->to('/student/jadwal-bk'), ['as' => 'student.schedule']);
        $routes->get('schedule/(:any)', static fn() => redirect()->to('/student/jadwal-bk'));

        // Assessments
        $routes->group('assessments', ['filter' => 'permission:take_assessments'], function ($routes) {
            $routes->get('/', 'AssessmentController::available', ['as' => 'student.assessments']);
            $routes->get('available', 'AssessmentController::available', ['as' => 'student.assessments.available']);
            $routes->get('take/(:num)', 'AssessmentController::take/$1', ['as' => 'student.assessments.take']);
            $routes->match(['GET', 'POST'], 'start/(:num)', 'AssessmentController::start/$1', ['as' => 'student.assessments.start']);
            $routes->get('resume/(:num)', 'AssessmentController::resume/$1', ['as' => 'student.assessments.resume']);
            $routes->post('submit/(:num)', 'AssessmentController::submit/$1', ['as' => 'student.assessments.submit']);
            $routes->get('results', 'AssessmentController::results', ['as' => 'student.assessments.results']);
            $routes->get('review/(:num)', 'AssessmentController::review/$1', ['as' => 'student.assessments.review']);
        });

        // Career (lihat)
        $routes->group('career', ['filter' => 'permission:view_career_info'], function ($routes) {
            $routes->get('/', 'CareerController::index', ['as' => 'student.career']);
            $routes->get('explore', 'CareerController::explore', ['as' => 'student.career.explore']);
            $routes->get('saved', 'CareerController::saved', ['as' => 'student.career.saved']);
            $routes->post('save/(:num)', 'CareerController::save/$1', ['as' => 'student.career.save']);
            $routes->post('remove/(:num)', 'CareerController::remove/$1', ['as' => 'student.career.remove']);
            $routes->get('(:num)', 'CareerController::detail/$1', ['as' => 'student.career.detail']);
        });

    });
});

// ===============================
// PARENT (Orang Tua) (role locked)
// ===============================
$routes->group('parent', [
    'filter'    => 'auth',
    'namespace' => 'App\Controllers\Parents'
], function ($routes) {

    $routes->group('', ['filter' => 'role:orang tua,parent'], function ($routes) {

        $routes->get('dashboard', 'DashboardController::index', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'parent.dashboard'
        ]);

        $routes->group('notifications', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'NotificationController::index', ['as' => 'parent.notifications']);
            $routes->get('unread', 'NotificationController::unread', ['as' => 'parent.notifications.unread']);
            $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1', ['as' => 'parent.notifications.read']);
            $routes->post('mark-all-read', 'NotificationController::markAllAsRead', ['as' => 'parent.notifications.read_all']);
            $routes->post('delete/(:num)', 'NotificationController::delete/$1', ['as' => 'parent.notifications.delete']);
            $routes->post('delete-all', 'NotificationController::deleteAll', ['as' => 'parent.notifications.delete_all']);
            $routes->get('count', 'NotificationController::getUnreadCount', ['as' => 'parent.notifications.count']);
        });

        // Tempat Sampah (pemulihan soft delete) - hanya data milik penghapus.
        $routes->group('trash', ['filter' => 'permission:view_dashboard'], function ($routes) {
            $routes->get('/', 'TrashController::index', ['as' => 'parent.trash']);
            $routes->post('restore', 'TrashController::restore', ['as' => 'parent.trash.restore']);
            $routes->post('force-delete', 'TrashController::forceDelete', ['as' => 'parent.trash.force']);
        });

        $routes->group('messages', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'MessageController::index', ['as' => 'parent.messages']);
            $routes->get('summary', 'MessageController::summary', ['as' => 'parent.messages.summary']);
            $routes->get('chat/(:num)', 'MessageController::chat/$1', ['as' => 'parent.messages.chat']);
            $routes->get('poll/(:num)', 'MessageController::poll/$1', ['as' => 'parent.messages.poll']);
            $routes->post('send/(:num)', 'MessageController::send/$1', ['as' => 'parent.messages.send']);
            $routes->post('delete', 'MessageController::delete', ['as' => 'parent.messages.delete']);
            $routes->post('delete-all', 'MessageController::deleteAll', ['as' => 'parent.messages.delete_all']);
            $routes->get('attachment/(:num)', 'MessageController::downloadAttachment/$1', ['as' => 'parent.messages.attachment']);
        });

        $routes->get('profile', static fn() => redirect()->to('/profile'), [
            'filter' => 'permission:view_dashboard',
            'as'     => 'parent.profile.edit'
        ]);

        // âœ… FIX: gunakan FQN dengan leading backslash agar tidak kena prefix namespace group
        $routes->post('profile', '\App\Controllers\ProfileController::update', [
            'filter' => 'permission:view_dashboard',
            'as'     => 'parent.profile.update'
        ]);

        // Data anak
        $routes->get('children', 'ChildController::index', [
            'filter' => 'permission:view_student_portfolio',
            'as'     => 'parent.children.index'
        ]);

        $routes->group('child', function ($routes) {
            $routes->get('(:num)/profile', 'ChildController::profile/$1', [
                'filter' => 'permission:view_student_portfolio',
                'as'     => 'parent.children.profile'
            ]);
            // Edit data anak langsung (kecuali 5 field terkunci).
            $routes->get('(:num)/edit', 'ChildController::edit/$1', [
                'filter' => 'permission:view_student_portfolio',
                'as'     => 'parent.children.edit'
            ]);
            $routes->post('(:num)/update', 'ChildController::update/$1', [
                'filter' => 'permission:view_student_portfolio',
                'as'     => 'parent.children.update'
            ]);
            $routes->get('(:num)/staff', 'ChildController::staff/$1', [
                'filter' => 'permission:view_student_portfolio',
                'as'     => 'parent.children.staff'
            ]);

            // Sesi/Konseling anak dilebur ke "Jadwal Kegiatan/Acara BK" (parent/jadwal-bk).
            // Rute lama redirect agar tautan lama tetap aman.
            $routes->get('(:num)/sessions', static fn() => redirect()->to('/parent/jadwal-bk'), ['as' => 'parent.children.sessions']);
            $routes->get('(:num)/sessions/(:num)', static fn() => redirect()->to('/parent/jadwal-bk'), ['as' => 'parent.children.sessions.detail']);

            $routes->post('(:num)/request-update', 'ChildController::requestUpdate/$1', [
                'filter' => 'permission:send_messages',
                'as'     => 'parent.children.request_update'
            ]);
            $routes->post('(:num)/contact', 'ChildController::updateContact/$1', [
                'filter' => 'permission:view_student_portfolio',
                'as'     => 'parent.children.contact'
            ]);
            $routes->post('(:num)/upload-photo', 'ChildController::uploadPhoto/$1', [
                'filter' => 'permission:view_student_portfolio',
                'as'     => 'parent.children.photo'
            ]);
        });


        // Fitur final pengembangan BK untuk Orang Tua.
        $routes->group('consultations', ['filter' => 'permission:submit_consultation_complaints'], function ($routes) {
            $routes->get('/', 'ConsultationController::index', ['as' => 'parent.consultations.index']);
            $routes->get('create', 'ConsultationController::create', ['as' => 'parent.consultations.create']);
            $routes->post('store', 'ConsultationController::store', ['as' => 'parent.consultations.store']);
            $routes->get('show/(:num)', 'ConsultationController::show/$1', ['as' => 'parent.consultations.show']);
            $routes->get('edit/(:num)', 'ConsultationController::edit/$1', ['as' => 'parent.consultations.edit']);
            $routes->post('update/(:num)', 'ConsultationController::update/$1', ['as' => 'parent.consultations.update']);
            $routes->post('delete/(:num)', 'ConsultationController::delete/$1', ['as' => 'parent.consultations.delete']);
            $routes->get('attachment/(:num)', 'ConsultationController::downloadAttachment/$1', ['as' => 'parent.consultations.attachment']);
            $routes->post('attachment-delete/(:num)', 'ConsultationController::deleteAttachment/$1', ['as' => 'parent.consultations.attachmentDelete']);
        });

        $bkReadRoutes = static function ($routes, string $controller, string $alias): void {
            $routes->get('/', $controller . '::index', ['as' => $alias . '.index']);
            $routes->get('show/(:num)', $controller . '::show/$1', ['as' => $alias . '.show']);
        };

        // Halaman terpadu Jadwal Kegiatan/Acara BK (jadwal saja + pengingat asesmen anak).
        $routes->get('jadwal-bk', 'BkScheduleController::index', [
            'filter' => 'permission:view_bk_services',
            'as'     => 'parent.bk_schedule'
        ]);

        $routes->group('guidance', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'GuidanceController', 'parent.guidance'));
        $routes->group('counseling', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'CounselingController', 'parent.counseling'));
        $routes->group('parent-collaborations', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'ParentCollaborationController', 'parent.parent_collaborations'));
        $routes->group('home-visits', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'HomeVisitController', 'parent.home_visits'));
        $routes->group('case-conferences', ['filter' => 'permission:view_bk_services'], static fn($routes) => $bkReadRoutes($routes, 'CaseConferenceController', 'parent.case_conferences'));

        // Komunikasi
        $routes->group('communication', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'CommunicationController::index', ['as' => 'parent.communication']);
            $routes->post('send-message', 'CommunicationController::sendMessage', ['as' => 'parent.communication.send']);
        });

        // Career (lihat)
        $routes->group('career', ['filter' => 'permission:view_career_info'], function ($routes) {
            $routes->get('/', 'CareerController::index', ['as' => 'parent.career']);
            $routes->get('explore', 'CareerController::explore', ['as' => 'parent.career.explore']);
            $routes->get('saved', 'CareerController::saved', ['as' => 'parent.career.saved']);
            $routes->post('save/(:num)', 'CareerController::save/$1', ['as' => 'parent.career.save']);
            $routes->post('remove/(:num)', 'CareerController::remove/$1', ['as' => 'parent.career.remove']);
            $routes->get('(:num)', 'CareerController::detail/$1', ['as' => 'parent.career.detail']);
        });

        // Reports (orang tua -> individual)
        $routes->group('reports', ['filter' => 'permission:view_reports_individual'], function ($routes) {
            $routes->get('child/(:num)', 'ReportController::childReport/$1', ['as' => 'parent.reports.child']);
            $routes->get('children', 'ReportController::childrenReport', ['as' => 'parent.reports.children']);
        });
    });
});

// ===============================
// Redirect kompatibilitas untuk route global Pesan & Notifikasi
// ===============================
$roleScopedUrl = static function (string $path): string {
    $roleId = (int) (session('role_id') ?? 0);
    $roleName = strtolower(trim((string) (session('role_name') ?? '')));
    $prefix = match (true) {
        $roleId === 1 || str_contains($roleName, 'admin') => 'admin',
        $roleId === 2 || str_contains($roleName, 'koordinator') => 'koordinator',
        $roleId === 3 || str_contains($roleName, 'guru') || str_contains($roleName, 'counselor') => 'counselor',
        $roleId === 4 || str_contains($roleName, 'wali') || str_contains($roleName, 'homeroom') => 'homeroom',
        $roleId === 5 || str_contains($roleName, 'siswa') || str_contains($roleName, 'student') => 'student',
        $roleId === 6 || str_contains($roleName, 'orang') || str_contains($roleName, 'parent') => 'parent',
        default => 'dashboard',
    };

    $query = service('request')->getGet() ?? [];
    $qs = $query ? ('?' . http_build_query($query)) : '';

    return '/' . trim($prefix . '/' . ltrim($path, '/'), '/') . $qs;
};

$routes->group('messages', ['filter' => 'auth'], function ($routes) use ($roleScopedUrl) {
    $routes->get('/', static fn() => redirect()->to($roleScopedUrl('messages')), ['as' => 'messages.index']);
    // Kompatibilitas tautan lama (model email) → arahkan ke halaman utama Pesan baru.
    $routes->get('inbox', static fn() => redirect()->to($roleScopedUrl('messages')), ['as' => 'messages.inbox']);
    $routes->get('sent', static fn() => redirect()->to($roleScopedUrl('messages')), ['as' => 'messages.sent']);
    $routes->get('compose', static fn() => redirect()->to($roleScopedUrl('messages')), ['as' => 'messages.compose']);
    $routes->get('detail/(:num)', static fn($id) => redirect()->to($roleScopedUrl('messages')), ['as' => 'messages.detail']);
    $routes->get('chat/(:num)', static fn($id) => redirect()->to($roleScopedUrl('messages/chat/' . (int) $id)), ['as' => 'messages.chat']);
});

$routes->group('notifications', ['filter' => 'auth'], function ($routes) use ($roleScopedUrl) {
    $routes->get('/', static fn() => redirect()->to($roleScopedUrl('notifications')), ['as' => 'notifications']);
    $routes->get('unread', static fn() => redirect()->to($roleScopedUrl('notifications/unread')), ['as' => 'notifications.unread']);
    $routes->get('count', static fn() => redirect()->to($roleScopedUrl('notifications/count')), ['as' => 'notifications.count']);
});

// ===============================
// API (AJAX/REST)
// ===============================
$routes->group('api', ['filter' => 'auth', 'namespace' => 'App\Controllers\Api'], function ($routes) {

    // Token CSRF terkini (GET → tidak meregenerasi token). Dipakai klien untuk
    // pulih dari token basi akibat token diregenerasi proses lain (mis. menandai
    // notifikasi terbaca dari lonceng saat berpindah ke halaman percakapan),
    // sehingga balasan pesan tidak gagal dengan "The action you requested is not allowed."
    $routes->get('csrf', static function () {
        return \Config\Services::response()->setJSON(['token' => csrf_hash()]);
    });

    // Stats per-role
    $routes->get('stats/admin', 'StatsController::adminStats', ['filter' => 'role:admin,administrator']);
    $routes->get('stats/counselor', 'StatsController::counselorStats', ['filter' => 'role:guru bk,counselor']);
    $routes->get('stats/student', 'StatsController::studentStats', ['filter' => 'role:siswa,student']);

    // Students API
    $routes->group('students', ['filter' => 'permission:view_all_students'], function ($routes) {
        $routes->get('search', 'StudentApiController::search');
        $routes->get('by-class/(:num)', 'StudentApiController::getByClass/$1');
        $routes->get('(:num)', 'StudentApiController::show/$1');
    });

    $routes->group('classes', ['filter' => 'permission:manage_academic_data'], function ($routes) {
        $routes->get('active', 'ClassApiController::getActive');
        $routes->get('(:num)/students', 'ClassApiController::getStudents/$1');
    });

    // Assessments API
    $routes->group('assessments', ['filter' => 'permission:take_assessments'], function ($routes) {
        $routes->get('list', 'AssessmentApiController::list');
        $routes->get('(:num)', 'AssessmentApiController::show/$1');
        $routes->get('(:num)/questions', 'AssessmentApiController::getQuestions/$1');
        $routes->get('(:num)/statistics', 'AssessmentApiController::getStatistics/$1');
        $routes->get('(:num)/progress/(:num)', 'AssessmentApiController::getProgress/$1/$2');
        $routes->post('answer', 'AssessmentApiController::saveAnswer');
        $routes->post('(:num)/autosave', 'AssessmentApiController::autosave/$1');
    });

    $routes->group('notifications', function ($routes) {
        $routes->get('latest', 'NotificationApiController::getLatest');
        $routes->get('count', 'NotificationApiController::getUnreadCount');
        $routes->post('(:num)/read', 'NotificationApiController::markAsRead/$1');
    });

    $routes->group('messages', ['filter' => 'permission:send_messages'], function ($routes) {
        $routes->get('unread-count', 'MessageApiController::getUnreadCount');
        $routes->get('latest', 'MessageApiController::getLatest');
    });
});

// ===============================
// Upload / Download (auth)
// ===============================
$routes->group('upload', ['filter' => 'auth'], function ($routes) {
    $routes->post('profile-photo', 'UploadController::profilePhoto', ['as' => 'upload.photo']);
    $routes->post('document', 'UploadController::document', ['as' => 'upload.document']);
    $routes->post('temp', 'UploadController::temp', ['as' => 'upload.temp']);
});

$routes->group('download', ['filter' => 'auth'], function ($routes) {
    $routes->get('template/student-import', 'DownloadController::studentTemplate', ['as' => 'download.template.student']);
    $routes->get('report/(:segment)', 'DownloadController::report/$1', ['as' => 'download.report']);
    $routes->get('document/(:segment)', 'DownloadController::document/$1', ['as' => 'download.document']);
});

// -------------------------------
// 404 Override
// -------------------------------
$routes->set404Override(static function () {
    return view('errors/html/error_404');
});

/**
 * âœ… OPSIONAL (standar CI4):
 * Load routes tambahan per environment (development/production).
 */
$envRoutes = APPPATH . 'Config/' . (defined('ENVIRONMENT') ? ENVIRONMENT : 'production') . '/Routes.php';
if (is_file($envRoutes)) {
    require $envRoutes;
}
