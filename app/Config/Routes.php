<?php

/**
 * File Path: app/Config/Routes.php
 *
 * Complete Routes Configuration (RBAC-ready)
 * Qovex Template • CodeIgniter 4
 */

use CodeIgniter\Router\RouteCollection;
use Config\Services;

/** @var RouteCollection $routes */

/**
 * ✅ SAFETY: Pastikan $routes terdefinisi.
 */
if (!isset($routes) || !($routes instanceof RouteCollection)) {
    $routes = Services::routes();
}

/**
 * ✅ OPSIONAL (standar CI4):
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

// 404 override custom kamu sudah ada di bawah, jadi ini cukup default dulu:
$routes->set404Override();

// Disarankan: matikan AutoRoute untuk keamanan (semua rute eksplisit)
$routes->setAutoRoute(false);

// -------------------------------
// Default
// -------------------------------
$routes->get('/', 'Home::index');
$routes->get('test', 'Test::index');

/**
 * ✅ Dashboard universal (opsional, tapi enak untuk redirect fail-safe)
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
 * ✅ Kompatibilitas untuk AuthFilter lama yang redirect ke /auth/login.
 * Ini mencegah 404 / redirect loop.
 */
$routes->group('auth', ['filter' => 'csrf'], function ($routes) {
    $routes->get('login', 'Auth\AuthController::index');
    $routes->post('login', 'Auth\AuthController::login');
    $routes->match(['get', 'post'], 'logout', 'Auth\AuthController::logout');
    $routes->get('register', 'Auth\AuthController::register');
    $routes->post('register', 'Auth\AuthController::doRegister');
    $routes->get('forgot-password', 'Auth\AuthController::forgotPassword');
    $routes->post('forgot-password', 'Auth\AuthController::sendResetLink');
    $routes->get('reset-password/(:segment)', 'Auth\AuthController::resetPassword/$1');
    $routes->post('reset-password', 'Auth\AuthController::doResetPassword');
});

/**
 * ✅ Opsional kompatibilitas:
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

    // Tambah fallback GET logout supaya kompatibel dengan link di UI
    $routes->post('logout', 'Auth\AuthController::logout', ['as' => 'logout']);
    $routes->get('logout', 'Auth\AuthController::logout', ['as' => 'logout.get']);

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
        });

        // EXPORT (izin: import_export_data)
        $routes->group('export', ['filter' => 'permission:import_export_data'], function ($routes) {
            $routes->get('/', 'ExportController::options', ['as' => 'admin.export']);
            $routes->get('students', 'ExportController::students', ['as' => 'admin.export.students']);
            $routes->get('violations', 'ExportController::violations', ['as' => 'admin.export.violations']);
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

        // STUDENTS (Koordinator: R semua, U akademik)
        $routes->group('students', function ($routes) {
            $routes->get('/', 'StudentController::index', [
                'filter' => 'permission:view_all_students',
                'as'     => 'koordinator.students.index'
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

            $routes->get('edit/(:num)', 'StudentController::edit/$1', [
                'filter' => 'permission:view_all_students,manage_academic_data',
                'as'     => 'koordinator.students.edit'
            ]);
            $routes->post('update/(:num)', 'StudentController::update/$1', [
                'filter' => 'permission:view_all_students,manage_academic_data',
                'as'     => 'koordinator.students.update'
            ]);

            // ✅ TAMBAHAN: Sinkron poin pelanggaran (Koordinator)
            // Filter minimal: view_all_students (detail permission/guard tambahan sudah ada di controller)
            $routes->post('sync-violation-points', 'StudentController::syncViolationPoints', [
                'filter' => 'permission:view_all_students',
                'as'     => 'koordinator.students.syncViolationPoints'
            ]);

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

        // SESSIONS (Koordinator) - READ (izin: view_counseling_sessions)
        $routes->group('sessions', ['filter' => 'permission:view_counseling_sessions'], function ($routes) {
            $routes->get('/', 'SessionController::index', ['as' => 'koordinator.sessions.index']);
            $routes->get('detail/(:num)', 'SessionController::show/$1', ['as' => 'koordinator.sessions.detail']);
            $routes->get('(:num)', 'SessionController::show/$1');
        });

        // CASES / PELANGGARAN
        $routes->group('cases', function ($routes) {
            $routes->get('/', 'CaseController::index', [
                'filter' => 'permission:view_violations',
                'as'     => 'koordinator.cases.index'
            ]);
            $routes->get('detail/(:num)', 'CaseController::detail/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'koordinator.cases.detail'
            ]);
            $routes->get('(:num)', 'CaseController::detail/$1', ['filter' => 'permission:view_violations']);

            $routes->get('create', 'CaseController::create', [
                'filter' => 'permission:manage_violations',
                'as'     => 'koordinator.cases.create'
            ]);
            $routes->post('store', 'CaseController::store', [
                'filter' => 'permission:manage_violations',
                'as'     => 'koordinator.cases.store'
            ]);

            $routes->get('edit/(:num)', 'CaseController::edit/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'koordinator.cases.edit'
            ]);
            $routes->post('update/(:num)', 'CaseController::update/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'koordinator.cases.update'
            ]);

            $routes->post('delete/(:num)', 'CaseController::delete/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'koordinator.cases.delete'
            ]);

            $routes->post('notifyParent/(:num)', 'CaseController::notifyParent/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'koordinator.cases.notify'
            ]);

            // Assign Guru BK: Koordinator tetap boleh meski tanpa manage_violations
            $routes->post('assignCounselor/(:num)', 'CaseController::assignCounselor/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'koordinator.cases.assign'
            ]);

            $routes->post('addSanction/(:num)', 'CaseController::addSanction/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions',
                'as'     => 'koordinator.cases.sanction',
            ]);
        });

        // Alias "violations" untuk kompatibilitas
        $routes->group('violations', ['filter' => 'permission:view_violations'], function ($routes) {
            $routes->get('/', static fn() => redirect()->to('/koordinator/cases'));
            $routes->get('detail/(:num)', static fn($id) => redirect()->to('/koordinator/cases/detail/' . $id));
        });

        // SANCTIONS
        $routes->group('sanctions', function ($routes) {
            $routes->post('create/(:num)', 'SanctionController::store/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions',
                'as'     => 'koordinator.sanctions.create'
            ]);
            $routes->post('store/(:num)', 'SanctionController::store/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions',
                'as'     => 'koordinator.sanctions.store'
            ]);

            $routes->get('show/(:num)', 'SanctionController::show/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'koordinator.sanctions.show'
            ]);

            $routes->get('edit/(:num)', 'SanctionController::edit/$1', [
                'filter' => 'permission:manage_sanctions',
                'as'     => 'koordinator.sanctions.edit'
            ]);
            $routes->post('update/(:num)', 'SanctionController::update/$1', [
                'filter' => 'permission:manage_sanctions',
                'as'     => 'koordinator.sanctions.update'
            ]);

            $routes->post('delete/(:num)', 'SanctionController::delete/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions',
                'as'     => 'koordinator.sanctions.delete'
            ]);

            $routes->post('complete/(:num)', 'SanctionController::complete/$1', [
                'filter' => 'permission:manage_sanctions',
                'as'     => 'koordinator.sanctions.complete'
            ]);
            $routes->post('verify/(:num)', 'SanctionController::verify/$1', [
                'filter' => 'permission:manage_sanctions',
                'as'     => 'koordinator.sanctions.verify'
            ]);
            $routes->post('acknowledge/(:num)', 'SanctionController::acknowledge/$1', [
                'filter' => 'permission:manage_sanctions',
                'as'     => 'koordinator.sanctions.ack'
            ]);
            $routes->post('ack/(:num)', 'SanctionController::acknowledge/$1', [
                'filter' => 'permission:manage_sanctions',
                'as'     => 'koordinator.sanctions.ack.alias'
            ]);
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
            $routes->post('(:num)/results/(:num)/delete', 'AssessmentController::deleteResult/$1/$2', ['as' => 'koordinator.assessments.results.delete']);
        });

        // Reports (izin: view_reports + generate_reports untuk download)
        $routes->group('reports', ['filter' => 'permission:view_reports'], function ($routes) {
            $routes->get('/', 'ReportController::index', ['as' => 'koordinator.reports']);
            $routes->get('preview', 'ReportController::preview', ['as' => 'koordinator.reports.preview']);

            $routes->match(['get', 'post'], 'download', 'ReportController::download', [
                'filter' => 'permission:generate_reports',
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

        // Sessions: list/detail pakai view, CRUD pakai manage
        $routes->group('sessions', function ($routes) {
            $routes->get('/', 'SessionController::index', [
                'filter' => 'permission:view_counseling_sessions',
                'as'     => 'counselor.sessions'
            ]);

            $routes->get('detail/(:num)', 'SessionController::show/$1', [
                'filter' => 'permission:view_counseling_sessions',
                'as'     => 'counselor.sessions.detail'
            ]);

            $routes->get('create', 'SessionController::create', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.create'
            ]);
            $routes->post('store', 'SessionController::store', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.store'
            ]);
            $routes->get('edit/(:num)', 'SessionController::edit/$1', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.edit'
            ]);
            $routes->post('update/(:num)', 'SessionController::update/$1', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.update'
            ]);
            $routes->post('delete/(:num)', 'SessionController::delete/$1', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.delete'
            ]);

            $routes->post('addNote/(:num)', 'SessionController::addNote/$1', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.note'
            ]);

            $routes->post('notes/update/(:num)', 'SessionController::updateNote/$1', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.notes.update'
            ]);
            $routes->post('notes/delete/(:num)', 'SessionController::deleteNote/$1', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.notes.delete'
            ]);

            $routes->post('participants/update/(:num)', 'SessionController::updateParticipant/$1', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.participants.update',
            ]);

            $routes->post('participants/note/update', 'SessionController::updateParticipantNote', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.participant_note.update'
            ]);
            $routes->post('participants/note/delete', 'SessionController::deleteParticipantNote', [
                'filter' => 'permission:manage_counseling_sessions',
                'as'     => 'counselor.sessions.participant_note.delete'
            ]);

            $routes->get('students-by-class', 'SessionController::getStudentsByClass', [
                'filter' => 'permission:view_counseling_sessions',
                'as'     => 'counselor.sessions.students'
            ]);
        });

        // Schedule
        $routes->get('schedule', 'ScheduleController::index', [
            'filter' => 'permission:schedule_counseling',
            'as'     => 'counselor.schedule'
        ]);
        $routes->get('schedule/create', 'SessionController::create', [
            'filter' => 'permission:manage_counseling_sessions',
            'as'     => 'counselor.schedule.create'
        ]);
        $routes->get('schedule/events', 'ScheduleController::events', [
            'filter' => 'permission:schedule_counseling',
            'as'     => 'counselor.schedule.events'
        ]);
        $routes->post('schedule/reschedule', 'ScheduleController::reschedule', [
            'filter' => 'permission:manage_counseling_sessions',
            'as'     => 'counselor.schedule.reschedule'
        ]);

        // Students (binaan)
        $routes->group('students', ['filter' => 'permission:view_all_students'], function ($routes) {
            $routes->get('/', 'StudentController::index', ['as' => 'counselor.students']);
            $routes->get('(:num)', 'StudentController::show/$1', ['as' => 'counselor.students.show']);
            $routes->get('(:num)/edit', 'StudentController::edit/$1', ['as' => 'counselor.students.edit']);
            $routes->post('(:num)', 'StudentController::update/$1', ['as' => 'counselor.students.update']);
            $routes->get('detail/(:num)', 'StudentController::detail/$1', ['as' => 'counselor.students.detail']);

            // ✅ Sinkron poin pelanggaran (Guru BK) - biarkan akses mengikuti group filter view_all_students
            // (guard tambahan tetap bisa dilakukan di controller bila diperlukan)
            $routes->post('sync-violation-points', 'StudentController::syncViolationPoints', [
                'as' => 'counselor.students.syncViolationPoints'
            ]);
        });

        // Cases & Violations
        $routes->group('cases', function ($routes) {
            $routes->get('/', 'CaseController::index', [
                'filter' => 'permission:view_violations',
                'as'     => 'counselor.cases'
            ]);
            $routes->get('detail/(:num)', 'CaseController::detail/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'counselor.cases.detail'
            ]);

            $routes->get('create', 'CaseController::create', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.cases.create'
            ]);
            $routes->post('store', 'CaseController::store', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.cases.store'
            ]);
            $routes->get('edit/(:num)', 'CaseController::edit/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.cases.edit'
            ]);
            $routes->post('update/(:num)', 'CaseController::update/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.cases.update'
            ]);
            $routes->post('delete/(:num)', 'CaseController::delete/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.cases.delete'
            ]);

            $routes->post('addSanction/(:num)', 'CaseController::addSanction/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions',
                'as'     => 'counselor.cases.sanction'
            ]);
            $routes->post('notifyParent/(:num)', 'CaseController::notifyParent/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.cases.notify'
            ]);
        });

        // Sanctions
        $routes->group('sanctions', function ($routes) {
            $routes->post('create/(:num)', 'SanctionController::store/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions'
            ]);
            $routes->get('show/(:num)', 'SanctionController::show/$1', [
                'filter' => 'permission:view_violations'
            ]);
            $routes->get('edit/(:num)', 'SanctionController::edit/$1', [
                'filter' => 'permission:manage_sanctions'
            ]);
            $routes->post('update/(:num)', 'SanctionController::update/$1', [
                'filter' => 'permission:manage_sanctions'
            ]);
            $routes->post('delete/(:num)', 'SanctionController::delete/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions'
            ]);
            $routes->post('complete/(:num)', 'SanctionController::complete/$1', [
                'filter' => 'permission:manage_sanctions'
            ]);
            $routes->post('verify/(:num)', 'SanctionController::verify/$1', [
                'filter' => 'permission:manage_sanctions'
            ]);
            $routes->post('ack/(:num)', 'SanctionController::acknowledge/$1', [
                'filter' => 'permission:manage_sanctions'
            ]);
        });

        // Violations (alias tampilan terpisah)
        $routes->group('violations', function ($routes) {
            $routes->get('/', 'CaseController::violationsIndex', [
                'filter' => 'permission:view_violations',
                'as'     => 'counselor.violations'
            ]);
            $routes->get('create', 'CaseController::create', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.violations.create'
            ]);
            $routes->post('store', 'CaseController::store', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.violations.store'
            ]);
            $routes->get('detail/(:num)', 'CaseController::detail/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'counselor.violations.detail'
            ]);
            $routes->post('update/(:num)', 'CaseController::update/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.violations.update'
            ]);
            $routes->post('delete/(:num)', 'CaseController::delete/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.violations.delete'
            ]);
            $routes->post('addSanction/(:num)', 'CaseController::addSanction/$1', [
                'filter' => 'permission:manage_violations,manage_sanctions',
                'as'     => 'counselor.violations.sanction'
            ]);
            $routes->post('notifyParent/(:num)', 'CaseController::notifyParent/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'counselor.violations.notify'
            ]);
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
            $routes->post('(:num)/assign/revoke', 'AssessmentController::revokeAssignments/$1', ['as' => 'counselor.assessments.assign.revoke']);

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

        // Reports
        $routes->group('reports', ['filter' => 'permission:view_reports'], function ($routes) {
            $routes->get('/', 'ReportController::index', ['as' => 'counselor.reports']);
            $routes->get('preview', 'ReportController::preview', ['as' => 'counselor.reports.preview']);
            $routes->get('download', 'ReportController::download', [
                'filter' => 'permission:generate_reports',
                'as'     => 'counselor.reports.download'
            ]);

            $routes->get('violation-report', static function () {
                return redirect()->to('/counselor/reports?type=violations');
            }, ['as' => 'counselor.reports.violation']);

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
            }, ['filter' => 'permission:generate_reports', 'as' => 'counselor.reports.pdf']);

            $routes->post('generate-excel', static function () {
                $q  = service('request')->getPost() ?? [];
                $qs = http_build_query($q);
                $url = '/counselor/reports/download?format=xlsx' . ($qs ? '&' . $qs : '');
                return redirect()->to($url);
            }, ['filter' => 'permission:generate_reports', 'as' => 'counselor.reports.excel']);
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
            $routes->post('careers/publish/(:num)', 'CareerInfoController::toggleCareerPublic/$1', ['as' => 'counselor.career.publish']);

            $routes->get('student-choices', 'CareerInfoController::studentChoices', ['as' => 'counselor.career.choices']);

            $routes->get('universities', 'CareerInfoController::universities', ['as' => 'counselor.university.index']);
            $routes->get('universities/create', 'CareerInfoController::createUniversity', ['as' => 'counselor.university.create']);
            $routes->post('universities/store', 'CareerInfoController::storeUniversity', ['as' => 'counselor.university.store']);
            $routes->get('universities/edit/(:num)', 'CareerInfoController::editUniversity/$1', ['as' => 'counselor.university.edit']);
            $routes->post('universities/update/(:num)', 'CareerInfoController::updateUniversity/$1', ['as' => 'counselor.university.update']);
            $routes->post('universities/delete/(:num)', 'CareerInfoController::deleteUniversity/$1', ['as' => 'counselor.university.delete']);
            $routes->post('universities/toggle/(:num)', 'CareerInfoController::toggleUniversity/$1', ['as' => 'counselor.university.toggle']);
            $routes->post('universities/publish/(:num)', 'CareerInfoController::toggleUniversityPublic/$1', ['as' => 'counselor.university.publish']);
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

        // Pelanggaran: list/detail view, CRUD manage
        $routes->group('violations', function ($routes) {
            $routes->get('/', 'ViolationController::index', [
                'filter' => 'permission:view_violations',
                'as'     => 'homeroom.violations'
            ]);
            $routes->get('detail/(:num)', 'ViolationController::detail/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'homeroom.violations.detail'
            ]);

            $routes->get('create', 'ViolationController::create', [
                'filter' => 'permission:manage_violations',
                'as'     => 'homeroom.violations.create'
            ]);
            $routes->post('store', 'ViolationController::store', [
                'filter' => 'permission:manage_violations',
                'as'     => 'homeroom.violations.store'
            ]);
            $routes->get('edit/(:num)', 'ViolationController::edit/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'homeroom.violations.edit'
            ]);
            $routes->post('update/(:num)', 'ViolationController::update/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'homeroom.violations.update'
            ]);
            $routes->post('delete/(:num)', 'ViolationController::delete/$1', [
                'filter' => 'permission:manage_violations',
                'as'     => 'homeroom.violations.delete'
            ]);
        });

        // Reports
        $routes->group('reports', ['filter' => 'permission:view_reports'], function ($routes) {
            $routes->get('/', 'ClassReportController::index', ['as' => 'homeroom.reports']);
            $routes->get('preview', 'ClassReportController::preview', ['as' => 'homeroom.reports.preview']);
            $routes->match(['get', 'post'], 'download', 'ClassReportController::download', ['as' => 'homeroom.reports.download']);

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
            'filter' => 'permission:view_student_portfolio',
            'as'     => 'homeroom.students'
        ]);
        $routes->get('students/index', 'StudentController::index', [
            'filter' => 'permission:view_student_portfolio',
            'as'     => 'homeroom.students.index'
        ]);
        $routes->get('students/(:num)', 'StudentController::show/$1', [
            'filter' => 'permission:view_student_portfolio',
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

        $routes->get('career-info', 'CareerInfoController::index', [
            'filter' => 'permission:view_career_info',
            'as'     => 'homeroom.career.index'
        ]);
        $routes->get('career-info/student-choices', 'CareerInfoController::studentChoices', [
            'filter' => 'permission:view_career_info',
            'as'     => 'homeroom.career.choices'
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

        // Jadwal/request konseling
        $routes->group('schedule', ['filter' => 'permission:schedule_counseling'], function ($routes) {
            $routes->get('/', 'ScheduleController::index', ['as' => 'student.schedule']);
            $routes->get('request', 'ScheduleController::requestForm', ['as' => 'student.schedule.request']);
            $routes->post('request', 'ScheduleController::storeRequest', ['as' => 'student.schedule.store']);
            $routes->post('submit-request', 'ScheduleController::submitRequest', ['as' => 'student.schedule.submit']);
            $routes->get('history', 'ScheduleController::history', ['as' => 'student.schedule.history']);
            $routes->get('detail/(:num)', 'ScheduleController::detail/$1', ['as' => 'student.schedule.detail']);
        });

        // Assessments
        $routes->group('assessments', ['filter' => 'permission:take_assessments'], function ($routes) {
            $routes->get('/', 'AssessmentController::available', ['as' => 'student.assessments']);
            $routes->get('available', 'AssessmentController::available', ['as' => 'student.assessments.available']);
            $routes->get('take/(:num)', 'AssessmentController::take/$1', ['as' => 'student.assessments.take']);
            $routes->match(['get', 'post'], 'start/(:num)', 'AssessmentController::start/$1', ['as' => 'student.assessments.start']);
            $routes->get('resume/(:num)', 'AssessmentController::resume/$1', ['as' => 'student.assessments.resume']);
            $routes->post('submit/(:num)', 'AssessmentController::submit/$1', ['as' => 'student.assessments.submit']);
            $routes->get('results', 'AssessmentController::results', ['as' => 'student.assessments.results']);
            $routes->get('review/(:num)', 'AssessmentController::review/$1', ['as' => 'student.assessments.review']);
        });

        // Career
        $routes->group('career', ['filter' => 'permission:view_career_info'], function ($routes) {
            $routes->get('/', 'CareerController::index', ['as' => 'student.career']);
            $routes->get('explore', 'CareerController::explore', ['as' => 'student.career.explore']);
            $routes->get('saved', 'CareerController::saved', ['as' => 'student.career.saved']);
            $routes->post('save/(:num)', 'CareerController::save/$1', ['as' => 'student.career.save']);
            $routes->post('remove/(:num)', 'CareerController::remove/$1', ['as' => 'student.career.remove']);
            $routes->get('(:num)', 'CareerController::detail/$1', ['as' => 'student.career.detail']);
        });

        // Riwayat pelanggaran
        $routes->get('violations', 'ViolationController::index', [
            'filter' => 'permission:view_violations',
            'as'     => 'student.violations'
        ]);
        $routes->get('violations/categories', 'ViolationController::categories', [
            'filter' => 'permission:view_violations',
            'as'     => 'student.violations.categories'
        ]);
        $routes->get('violations/(:num)', 'ViolationController::detail/$1', [
            'filter' => 'permission:view_violations',
            'as'     => 'student.violations.detail'
        ]);
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

        $routes->get('profile', static fn() => redirect()->to('/profile'), [
            'filter' => 'permission:view_dashboard',
            'as'     => 'parent.profile.edit'
        ]);

        // ✅ FIX: gunakan FQN dengan leading backslash agar tidak kena prefix namespace group
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
            $routes->get('(:num)/staff', 'ChildController::staff/$1', [
                'filter' => 'permission:view_student_portfolio',
                'as'     => 'parent.children.staff'
            ]);

            // Pelanggaran anak
            $routes->get('(:num)/violations', 'ChildController::violations/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'parent.children.violations'
            ]);
            $routes->get('(:num)/violations/(:num)', 'ChildController::violationDetail/$1/$2', [
                'filter' => 'permission:view_violations',
                'as'     => 'parent.children.violations.detail'
            ]);
            $routes->post('(:num)/violations/(:num)/ack', 'ChildController::acknowledgeSanctions/$1/$2', [
                'filter' => 'permission:view_violations',
                'as'     => 'parent.children.violations.ack'
            ]);
            $routes->get('(:num)/violations/categories', 'ChildController::violationCategories/$1', [
                'filter' => 'permission:view_violations',
                'as'     => 'parent.children.violations.categories'
            ]);

            // Jadwal sesi anak
            $routes->get('(:num)/sessions', 'ChildController::sessions/$1', [
                'filter' => 'permission:view_counseling_sessions',
                'as'     => 'parent.children.sessions'
            ]);
            $routes->get('(:num)/sessions/(:num)', 'ChildController::sessionDetail/$1/$2', [
                'filter' => 'permission:view_counseling_sessions',
                'as'     => 'parent.children.sessions.detail'
            ]);

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

        // Komunikasi
        $routes->group('communication', ['filter' => 'permission:send_messages'], function ($routes) {
            $routes->get('/', 'CommunicationController::index', ['as' => 'parent.communication']);
            $routes->post('send-message', 'CommunicationController::sendMessage', ['as' => 'parent.communication.send']);
        });

        // Career
        $routes->group('career', ['filter' => 'permission:view_career_info'], function ($routes) {
            $routes->get('/', 'CareerController::index', ['as' => 'parent.career']);
            $routes->get('explore', 'CareerController::explore', ['as' => 'parent.career.explore']);
            $routes->get('saved', 'CareerController::saved', ['as' => 'parent.career.saved']);
            $routes->post('save/(:num)', 'CareerController::save/$1', ['as' => 'parent.career.save']);
            $routes->post('remove/(:num)', 'CareerController::remove/$1', ['as' => 'parent.career.remove']);
            $routes->get('(:num)', 'CareerController::detail/$1', ['as' => 'parent.career.detail']);
        });

        // Reports
        $routes->group('reports', ['filter' => 'permission:view_reports'], function ($routes) {
            $routes->get('child/(:num)', 'ReportController::childReport/$1', ['as' => 'parent.reports.child']);
            $routes->get('children', 'ReportController::childrenReport', ['as' => 'parent.reports.children']);
        });
    });
});

// ===============================
// Messages & Notifications
// ===============================
$routes->group('messages', ['filter' => 'auth'], function ($routes) {
    $routes->group('', ['filter' => 'permission:send_messages'], function ($routes) {
        $routes->get('/', 'MessageController::index', ['as' => 'messages.index']);
        $routes->get('inbox', 'MessageController::inbox', ['as' => 'messages.inbox']);
        $routes->get('sent', 'MessageController::sent', ['as' => 'messages.sent']);
        $routes->get('compose', 'MessageController::compose', ['as' => 'messages.compose']);
        $routes->post('send', 'MessageController::send', ['as' => 'messages.send']);
        $routes->get('detail/(:num)', 'MessageController::detail/$1', ['as' => 'messages.detail']);
        $routes->post('reply/(:num)', 'MessageController::reply/$1', ['as' => 'messages.reply']);
        $routes->post('delete/(:num)', 'MessageController::delete/$1', ['as' => 'messages.delete']);
        $routes->post('mark-read/(:num)', 'MessageController::markAsRead/$1', ['as' => 'messages.read']);
    });
});

$routes->group('notifications', ['filter' => 'auth'], function ($routes) {
    $routes->group('', ['filter' => 'permission:view_dashboard'], function ($routes) {
        $routes->get('/', 'NotificationController::index', ['as' => 'notifications']);
        $routes->get('unread', 'NotificationController::unread', ['as' => 'notifications.unread']);
        $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1', ['as' => 'notifications.read']);
        $routes->post('mark-all-read', 'NotificationController::markAllAsRead', ['as' => 'notifications.read_all']);
        $routes->post('delete/(:num)', 'NotificationController::delete/$1', ['as' => 'notifications.delete']);
        $routes->get('count', 'NotificationController::getUnreadCount', ['as' => 'notifications.count']);
    });
});

// ===============================
// API (AJAX/REST)
// ===============================
$routes->group('api', ['filter' => 'auth', 'namespace' => 'App\Controllers\Api'], function ($routes) {

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
 * ✅ OPSIONAL (standar CI4):
 * Load routes tambahan per environment (development/production).
 */
$envRoutes = APPPATH . 'Config/' . (defined('ENVIRONMENT') ? ENVIRONMENT : 'production') . '/Routes.php';
if (is_file($envRoutes)) {
    require $envRoutes;
}
