<?php

/**
 * File Path: app/Controllers/Koordinator/UserController.php
 *
 * Koordinator BK • User Management
 * - Pola & service mirip Admin\UserController (UI kembar di view)
 * - HARD GUARD: hanya boleh kelola user role Guru BK & Wali Kelas (server-side)
 *
 * Tambahan (2026):
 * - Penugasan kelas untuk Guru BK (classes.counselor_id) dan Wali Kelas (classes.homeroom_teacher_id)
 * - Fix normalisasi status is_active (switch OFF tidak lagi dianggap aktif)
 * - Anti duplikat penugasan:
 *   - Kelas yang sudah ditugaskan ke user lain tidak muncul di dropdown (create/edit)
 *   - Server-side: menolak jika request mencoba assign kelas yang sudah dipakai
 */

namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Services\UserService;
use App\Validation\UserValidation;
use Config\Database;

class UserController extends BaseController
{
    protected UserService $userService;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected ClassModel $classModel;

    /**
     * Role yang boleh dikelola Koordinator (nama role di tabel roles)
     */
    protected array $allowedRoleNames = ['Guru BK', 'Wali Kelas'];

    /**
     * Cache role IDs agar tidak query DB berulang dalam 1 request
     */
    protected ?array $allowedRoleIdsCache = null;

    /**
     * Cache map role_name => id (untuk robust jika ID role tidak selalu 3/4)
     */
    protected ?array $allowedRoleMapCache = null;

    public function __construct()
    {
        helper('permission');

        $this->userService = new UserService();
        $this->userModel   = new UserModel();
        $this->roleModel   = new RoleModel();
        $this->classModel  = new ClassModel();
    }

    /**
     * Resolve role_name => role_id yang diizinkan (Guru BK, Wali Kelas).
     */
    protected function allowedRoleMap(): array
    {
        if (is_array($this->allowedRoleMapCache)) {
            return $this->allowedRoleMapCache;
        }

        $rows = $this->roleModel
            ->select('id, role_name')
            ->whereIn('role_name', $this->allowedRoleNames)
            ->findAll();

        $map = [];
        foreach ($rows as $r) {
            $name = (string)($r['role_name'] ?? '');
            $id   = (int)($r['id'] ?? 0);
            if ($name !== '' && $id > 0) {
                $map[$name] = $id;
            }
        }

        // Fallback aman jika seed/role_name berbeda atau belum ada
        if (empty($map)) {
            $map = [
                'Guru BK'    => 3,
                'Wali Kelas' => 4,
            ];
        }

        $this->allowedRoleMapCache = $map;
        $this->allowedRoleIdsCache = array_values(array_unique(array_map('intval', array_values($map))));

        return $this->allowedRoleMapCache;
    }

    /**
     * Resolve role_id yang diizinkan (Guru BK, Wali Kelas).
     */
    protected function allowedRoleIds(): array
    {
        if (is_array($this->allowedRoleIdsCache)) {
            return $this->allowedRoleIdsCache;
        }

        $map = $this->allowedRoleMap();
        $ids = array_values(array_unique(array_map('intval', array_values($map))));
        $ids = array_values(array_filter($ids));

        // fallback ekstra
        if (empty($ids)) {
            $ids = [3, 4];
        }

        return $this->allowedRoleIdsCache = $ids;
    }

    protected function roleIdByName(string $roleName): int
    {
        $map = $this->allowedRoleMap();
        return (int)($map[$roleName] ?? 0);
    }

    protected function roleIsAllowed(?int $roleId): bool
    {
        if ($roleId === null) return false;
        return in_array((int)$roleId, $this->allowedRoleIds(), true);
    }

    /**
     * Ambil user + pastikan role boleh dikelola Koordinator.
     * Return: array user atau RedirectResponse
     */
    protected function getAllowedUserOrRedirect(int $id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return redirect()->to(base_url('koordinator/users'))
                ->with('error', 'User tidak ditemukan');
        }

        $roleId = (int)($user['role_id'] ?? 0);
        if (!$this->roleIsAllowed($roleId)) {
            return redirect()->to(base_url('koordinator/users'))
                ->with('error', 'Akses ditolak: Koordinator hanya boleh mengelola akun Guru BK & Wali Kelas.');
        }

        return $user;
    }

    /**
     * Ambil list kelas yang bisa dipilih untuk Guru BK:
     * - Create: hanya yang counselor_id IS NULL
     * - Edit: counselor_id IS NULL atau counselor_id = $currentUserId (biar kelas miliknya tetap muncul)
     */
    protected function getAssignableClassesForCounselor(?int $currentUserId = null): array
    {
        $builder = $this->classModel->asArray()
            ->select('id, class_name, grade_level, major, is_active, homeroom_teacher_id, counselor_id')
            ->where('deleted_at', null);

        if ($currentUserId && $currentUserId > 0) {
            $builder->groupStart()
                ->where('counselor_id', null)
                ->orWhere('counselor_id', $currentUserId)
                ->groupEnd();
        } else {
            $builder->where('counselor_id', null);
        }

        return $builder
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }

    /**
     * Ambil list kelas yang bisa dipilih untuk Wali Kelas:
     * - Create: hanya yang homeroom_teacher_id IS NULL
     * - Edit: homeroom_teacher_id IS NULL atau homeroom_teacher_id = $currentUserId
     */
    protected function getAssignableClassesForHomeroom(?int $currentUserId = null): array
    {
        $builder = $this->classModel->asArray()
            ->select('id, class_name, grade_level, major, is_active, homeroom_teacher_id, counselor_id')
            ->where('deleted_at', null);

        if ($currentUserId && $currentUserId > 0) {
            $builder->groupStart()
                ->where('homeroom_teacher_id', null)
                ->orWhere('homeroom_teacher_id', $currentUserId)
                ->groupEnd();
        } else {
            $builder->where('homeroom_teacher_id', null);
        }

        return $builder
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }

    /**
     * Ambil penugasan kelas current user (untuk edit).
     * - Guru BK: bisa banyak kelas (classes.counselor_id = userId)
     * - Wali Kelas: 1 kelas (classes.homeroom_teacher_id = userId)
     */
    protected function getUserClassAssignments(int $userId): array
    {
        $counselorIds = $this->classModel->asArray()
            ->select('id')
            ->where('deleted_at', null)
            ->where('counselor_id', $userId)
            ->findColumn('id') ?? [];

        $homeroom = $this->classModel->asArray()
            ->select('id')
            ->where('deleted_at', null)
            ->where('homeroom_teacher_id', $userId)
            ->first();

        return [
            'counselor_class_ids' => array_values(array_map('intval', $counselorIds)),
            'homeroom_class_id'   => $homeroom ? (int)($homeroom['id'] ?? 0) : null,
        ];
    }

    /**
     * Ambil detail kelas berdasarkan hasil assignment user.
     * - counselor_classes: list kelas binaan (Guru BK)
     * - homeroom_class: 1 kelas perwalian (Wali Kelas)
     */
    protected function getAssignmentClassDetails(array $assignments): array
    {
        $counselorClasses = [];
        $homeroomClass    = null;

        $cIds = $assignments['counselor_class_ids'] ?? [];
        if (!is_array($cIds)) $cIds = [$cIds];
        $cIds = array_values(array_unique(array_filter(array_map('intval', $cIds))));

        if (!empty($cIds)) {
            $counselorClasses = $this->classModel->asArray()
                ->select('id, class_name, grade_level, major, is_active')
                ->where('deleted_at', null)
                ->whereIn('id', $cIds)
                ->orderBy('grade_level', 'ASC')
                ->orderBy('class_name', 'ASC')
                ->findAll();
        }

        $hId = $assignments['homeroom_class_id'] ?? null;
        $hId = $hId ? (int)$hId : null;

        if ($hId) {
            $homeroomClass = $this->classModel->asArray()
                ->select('id, class_name, grade_level, major, is_active')
                ->where('deleted_at', null)
                ->where('id', $hId)
                ->first();
        }

        return [
            'counselor_classes' => $counselorClasses,
            'homeroom_class'    => $homeroomClass,
        ];
    }

    /**
     * Validasi anti duplikat:
     * - Jika kelas sudah dipakai user lain, request ditolak (server-side).
     */
    protected function validateAssignmentAvailability(
        int $roleId,
        array $counselorClassIds,
        ?int $homeroomClassId,
        int $currentUserId = 0
    ): array {
        $guruBkRoleId    = $this->roleIdByName('Guru BK') ?: 3;
        $waliKelasRoleId = $this->roleIdByName('Wali Kelas') ?: 4;

        $counselorClassIds = array_values(array_unique(array_filter(array_map('intval', (array)$counselorClassIds))));
        $homeroomClassId   = $homeroomClassId ? (int)$homeroomClassId : null;

        // Guru BK: cek kelas-kelas yang dipilih apakah sudah ada counselor lain
        if ($roleId === $guruBkRoleId && !empty($counselorClassIds)) {
            $rows = $this->classModel->asArray()
                ->select('id, class_name, counselor_id')
                ->where('deleted_at', null)
                ->whereIn('id', $counselorClassIds)
                ->where('counselor_id IS NOT NULL', null, false)
                ->findAll();

            $conflicted = [];
            foreach ($rows as $r) {
                $cid = (int)($r['counselor_id'] ?? 0);
                if ($cid !== 0 && $cid !== (int)$currentUserId) {
                    $conflicted[] = (string)($r['class_name'] ?? ('ID ' . (int)($r['id'] ?? 0)));
                }
            }

            if (!empty($conflicted)) {
                return [
                    'success' => false,
                    'message' => 'Gagal: beberapa kelas sudah ditugaskan ke Guru BK lain: ' . implode(', ', $conflicted) . '.',
                ];
            }
        }

        // Wali Kelas: cek kelas yang dipilih apakah sudah ada wali lain
        if ($roleId === $waliKelasRoleId && $homeroomClassId) {
            $row = $this->classModel->asArray()
                ->select('id, class_name, homeroom_teacher_id')
                ->where('deleted_at', null)
                ->where('id', $homeroomClassId)
                ->first();

            if ($row) {
                $hid = (int)($row['homeroom_teacher_id'] ?? 0);
                if ($hid !== 0 && $hid !== (int)$currentUserId) {
                    return [
                        'success' => false,
                        'message' => 'Gagal: kelas ' . ($row['class_name'] ?? '-') . ' sudah ditugaskan ke Wali Kelas lain.',
                    ];
                }
            }
        }

        return ['success' => true, 'message' => null];
    }

    /**
     * Sinkronkan penugasan kelas sesuai role.
     * Catatan:
     * - Guru BK: set classes.counselor_id ke userId untuk kelas terpilih
     * - Wali Kelas: set classes.homeroom_teacher_id ke userId untuk 1 kelas terpilih
     *
     * Aman dari "stealing":
     * - update hanya terjadi jika kolom masih NULL atau memang milik user ini.
     */
    protected function syncClassAssignments(int $userId, int $roleId, array $counselorClassIds, ?int $homeroomClassId): array
    {
        $db    = Database::connect();
        $table = method_exists($this->classModel, 'getTable') ? $this->classModel->getTable() : 'classes';

        $guruBkRoleId    = $this->roleIdByName('Guru BK') ?: 3;
        $waliKelasRoleId = $this->roleIdByName('Wali Kelas') ?: 4;

        $counselorClassIds = array_values(array_unique(array_filter(array_map('intval', $counselorClassIds))));
        $homeroomClassId   = $homeroomClassId ? (int)$homeroomClassId : null;

        // Bersihkan assignment lama milik user ini (supaya konsisten kalau role berubah)
        $db->table($table)->set('counselor_id', null)->where('deleted_at', null)->where('counselor_id', $userId)->update();
        $db->table($table)->set('homeroom_teacher_id', null)->where('deleted_at', null)->where('homeroom_teacher_id', $userId)->update();

        $warning = null;

        if ($roleId === $guruBkRoleId) {
            if (!empty($counselorClassIds)) {
                $db->table($table)
                    ->set('counselor_id', $userId)
                    ->where('deleted_at', null)
                    ->whereIn('id', $counselorClassIds)
                    ->groupStart()
                        ->where('counselor_id', null)
                        ->orWhere('counselor_id', $userId)
                    ->groupEnd()
                    ->update();

                // Kalau ada yang tidak ter-assign karena tiba-tiba konflik (race)
                $stillFree = $db->table($table)
                    ->select('class_name')
                    ->where('deleted_at', null)
                    ->whereIn('id', $counselorClassIds)
                    ->where('counselor_id !=', $userId)
                    ->get()->getResultArray();

                if (!empty($stillFree)) {
                    $names = array_values(array_filter(array_map(static fn($r) => (string)($r['class_name'] ?? ''), $stillFree)));
                    if (!empty($names)) {
                        $warning = 'Sebagian kelas tidak dapat ditugaskan karena sudah dipakai user lain: ' . implode(', ', $names);
                    }
                }
            }
        } elseif ($roleId === $waliKelasRoleId) {
            if ($homeroomClassId) {
                $db->table($table)
                    ->set('homeroom_teacher_id', $userId)
                    ->where('deleted_at', null)
                    ->where('id', $homeroomClassId)
                    ->groupStart()
                        ->where('homeroom_teacher_id', null)
                        ->orWhere('homeroom_teacher_id', $userId)
                    ->groupEnd()
                    ->update();

                $check = $db->table($table)
                    ->select('class_name, homeroom_teacher_id')
                    ->where('deleted_at', null)
                    ->where('id', $homeroomClassId)
                    ->get()->getRowArray();

                if ($check && (int)($check['homeroom_teacher_id'] ?? 0) !== $userId) {
                    $warning = 'Kelas ' . ($check['class_name'] ?? '-') . ' tidak dapat ditugaskan karena sudah dipakai user lain.';
                }
            }
        }

        return ['success' => true, 'warning' => $warning];
    }

    /**
     * GET /koordinator/users
     */
    public function index()
    {
        require_permission('manage_users');

        $allowedRoleIds = $this->allowedRoleIds();

        $filters = [
            'role_id'   => (string)($this->request->getGet('role_id') ?? ''),
            'is_active' => (string)($this->request->getGet('is_active') ?? ''),
            'search'    => (string)($this->request->getGet('search') ?? ''),
            'order_by'  => (string)($this->request->getGet('order_by') ?? 'users.created_at'),
            'order_dir' => (string)($this->request->getGet('order_dir') ?? 'DESC'),
        ];

        if ($filters['role_id'] !== '') {
            if (!in_array((int)$filters['role_id'], $allowedRoleIds, true)) {
                $filters['role_id'] = '';
            }
        }

        $allowedOrderBy = [
            'users.created_at',
            'users.full_name',
            'users.username',
            'users.email',
            'users.is_active',
            'users.last_login',
            'roles.role_name',
        ];
        if (!in_array($filters['order_by'], $allowedOrderBy, true)) {
            $filters['order_by'] = 'users.created_at';
        }
        $filters['order_dir'] = strtoupper($filters['order_dir']) === 'ASC' ? 'ASC' : 'DESC';

        $perPage = 10;

        $model = new UserModel();
        $model->asArray()
            ->select('users.*, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->whereIn('users.role_id', $allowedRoleIds);

        if ($filters['role_id'] !== '') {
            $model->where('users.role_id', (int)$filters['role_id']);
        }
        if ($filters['is_active'] !== '') {
            $model->where('users.is_active', (int)$filters['is_active']);
        }
        if (trim($filters['search']) !== '') {
            $q = trim($filters['search']);
            $model->groupStart()
                ->like('users.full_name', $q)
                ->orLike('users.username', $q)
                ->orLike('users.email', $q)
                ->groupEnd();
        }

        $users = $model->orderBy($filters['order_by'], $filters['order_dir'])
            ->paginate($perPage);

        $pager = $model->pager;

        $roles = $this->roleModel
            ->whereIn('role_name', $this->allowedRoleNames)
            ->findAll();

        $um = new UserModel();
        $total = $um->whereIn('role_id', $allowedRoleIds)->countAllResults();

        $um = new UserModel();
        $active = $um->whereIn('role_id', $allowedRoleIds)->where('is_active', 1)->countAllResults();

        $um = new UserModel();
        $inactive = $um->whereIn('role_id', $allowedRoleIds)->where('is_active', 0)->countAllResults();

        $data = [
            'title'      => 'Manajemen Pengguna',
            'page_title' => 'Manajemen Pengguna',
            'breadcrumb' => [
                ['title' => 'Koordinator BK', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Pengguna', 'link' => null],
            ],
            'users'   => $users,
            'pager'   => $pager,
            'roles'   => $roles,
            'stats'   => [
                'total'    => (int)$total,
                'active'   => (int)$active,
                'inactive' => (int)$inactive,
            ],
            'filters' => $filters,
        ];

        return view('koordinator/users/index', $data);
    }

    /**
     * GET /koordinator/users/create
     */
    public function create()
    {
        require_permission('manage_users');

        $roles = $this->roleModel
            ->whereIn('role_name', $this->allowedRoleNames)
            ->findAll();

        $data = [
            'title'            => 'Tambah Pengguna',
            'page_title'       => 'Tambah Pengguna',
            'breadcrumb'       => [
                ['title' => 'Koordinator BK', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Pengguna', 'link' => base_url('koordinator/users')],
                ['title' => 'Tambah Pengguna', 'link' => null],
            ],
            'roles'            => $roles,
            // Pisahkan list agar view bisa menampilkan sesuai role + sudah difilter anti duplikat
            'classes_counselor'=> $this->getAssignableClassesForCounselor(null),
            'classes_homeroom' => $this->getAssignableClassesForHomeroom(null),
            'assignments'      => ['counselor_class_ids' => [], 'homeroom_class_id' => null],
            'role_ids'         => $this->allowedRoleMap(),
            'validation'       => \Config\Services::validation(),
        ];

        return view('koordinator/users/create', $data);
    }

    /**
     * POST /koordinator/users/store
     */
    public function store()
    {
        require_permission('manage_users');

        $counselorClassIds = $this->request->getPost('counselor_class_ids') ?? [];
        $homeroomClassId   = $this->request->getPost('homeroom_class_id');

        $rawIsActive = $this->request->getPost('is_active');
        if (is_array($rawIsActive)) {
            $rawIsActive = end($rawIsActive);
        }

        $rules = UserValidation::createRules();
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = UserValidation::sanitizeInput($this->request->getPost());
        $data['student'] = [];
        $data['is_active'] = ((string)$rawIsActive === '1') ? 1 : 0;

        $roleId = (int)($data['role_id'] ?? 0);
        if (!$this->roleIsAllowed($roleId)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Role tidak valid. Koordinator hanya boleh membuat akun Guru BK / Wali Kelas.');
        }

        // Abaikan input penugasan yang tidak sesuai role (mencegah nilai nyangkut saat ganti role)
        $guruBkRoleId    = $this->roleIdByName('Guru BK') ?: 3;
        $waliKelasRoleId = $this->roleIdByName('Wali Kelas') ?: 4;
        if ($roleId === $guruBkRoleId) {
            $homeroomClassId = null;
        } elseif ($roleId === $waliKelasRoleId) {
            $counselorClassIds = [];
        }

        // Server-side anti duplikat (sebelum create user)
        $avail = $this->validateAssignmentAvailability($roleId, (array)$counselorClassIds, $homeroomClassId ? (int)$homeroomClassId : null, 0);
        if (empty($avail['success'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', $avail['message'] ?? 'Penugasan kelas tidak valid.');
        }

        $result = $this->userService->createUser($data);

        if (empty($result['success'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal membuat user.');
        }

        $userId = (int)($result['user_id'] ?? $result['id'] ?? 0);
        if ($userId <= 0) {
            $username = (string)($data['username'] ?? '');
            if ($username !== '') {
                $row = $this->userModel->asArray()->select('id')->where('username', $username)->orderBy('id', 'DESC')->first();
                $userId = (int)($row['id'] ?? 0);
            }
        }

        $warning = null;
        if ($userId > 0) {
            $sync = $this->syncClassAssignments(
                $userId,
                $roleId,
                (array)$counselorClassIds,
                $homeroomClassId ? (int)$homeroomClassId : null
            );
            if (!empty($sync['warning'])) {
                $warning = $sync['warning'];
            }
        }

        $redirect = redirect()->to(base_url('koordinator/users'))->with('success', $result['message'] ?? 'User berhasil dibuat.');
        if ($warning) $redirect->with('warning', $warning);
        return $redirect;
    }

    /**
     * GET /koordinator/users/show/{id}
     */
    public function show($id)
    {
        require_permission('manage_users');

        $userOrRedirect = $this->getAllowedUserOrRedirect((int)$id);
        if ($userOrRedirect instanceof \CodeIgniter\HTTP\RedirectResponse) return $userOrRedirect;

        // ✅ ambil penugasan kelas + detailnya
        $assignments = $this->getUserClassAssignments((int)$id);
        $assigned    = $this->getAssignmentClassDetails($assignments);

        $data = [
            'title'      => 'Detail Pengguna',
            'page_title' => 'Detail Pengguna',
            'breadcrumb' => [
                ['title' => 'Koordinator BK', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Pengguna', 'link' => base_url('koordinator/users')],
                ['title' => 'Detail Pengguna', 'link' => null],
            ],
            'user'       => $userOrRedirect,

            // ✅ tambahan untuk view
            'assignments' => $assignments,
            'assigned'    => $assigned,
        ];

        return view('koordinator/users/show', $data);
    }

    /**
     * GET /koordinator/users/edit/{id}
     */
    public function edit($id)
    {
        require_permission('manage_users');

        $userOrRedirect = $this->getAllowedUserOrRedirect((int)$id);
        if ($userOrRedirect instanceof \CodeIgniter\HTTP\RedirectResponse) return $userOrRedirect;

        $roles = $this->roleModel
            ->whereIn('role_name', $this->allowedRoleNames)
            ->findAll();

        $uid = (int)$id;

        $data = [
            'title'             => 'Edit Pengguna',
            'page_title'        => 'Edit Pengguna',
            'breadcrumb'        => [
                ['title' => 'Koordinator BK', 'link' => base_url('koordinator/dashboard')],
                ['title' => 'Pengguna', 'link' => base_url('koordinator/users')],
                ['title' => 'Edit Pengguna', 'link' => null],
            ],
            'user'              => $userOrRedirect,
            'roles'             => $roles,
            // Filter anti duplikat, tapi tetap tampilkan kelas milik user ini
            'classes_counselor' => $this->getAssignableClassesForCounselor($uid),
            'classes_homeroom'  => $this->getAssignableClassesForHomeroom($uid),
            'assignments'       => $this->getUserClassAssignments($uid),
            'role_ids'          => $this->allowedRoleMap(),
            'validation'        => \Config\Services::validation(),
        ];

        return view('koordinator/users/edit', $data);
    }

    /**
     * POST /koordinator/users/update/{id}
     */
    public function update(int $id)
    {
        require_permission('manage_users');

        $userOrRedirect = $this->getAllowedUserOrRedirect($id);
        if ($userOrRedirect instanceof \CodeIgniter\HTTP\RedirectResponse) return $userOrRedirect;

        $counselorClassIds = $this->request->getPost('counselor_class_ids') ?? [];
        $homeroomClassId   = $this->request->getPost('homeroom_class_id');

        $postData = $this->request->getPost();
        $postData['id'] = $id;

        if (isset($postData['is_active']) && is_array($postData['is_active'])) {
            $postData['is_active'] = end($postData['is_active']);
        }

        $currentUserId = (int)session()->get('user_id');

        if ($id === $currentUserId) {
            $existing = $this->userModel->find($id);
            if ($existing) {
                $postData['is_active'] = $existing['is_active'];
                $postData['role_id']   = $existing['role_id'];
            }
        } else {
            $postData['is_active'] = ((string)($postData['is_active'] ?? '0') === '1') ? 1 : 0;
        }

        $roleId = (int)($postData['role_id'] ?? 0);
        if (!$this->roleIsAllowed($roleId)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Role tidak valid. Koordinator hanya boleh menetapkan role Guru BK / Wali Kelas.');
        }

        $rules = method_exists(UserValidation::class, 'updateRules')
            ? UserValidation::updateRules($id)
            : UserValidation::createRules();

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $clean = method_exists(UserValidation::class, 'sanitizeInput')
            ? UserValidation::sanitizeInput($postData)
            : $postData;

        $clean['is_active'] = (int)($postData['is_active'] ?? 0);

        // Abaikan penugasan yang tidak sesuai role
        $guruBkRoleId    = $this->roleIdByName('Guru BK') ?: 3;
        $waliKelasRoleId = $this->roleIdByName('Wali Kelas') ?: 4;
        if ($roleId === $guruBkRoleId) {
            $homeroomClassId = null;
        } elseif ($roleId === $waliKelasRoleId) {
            $counselorClassIds = [];
        }

        // Server-side anti duplikat (sebelum update + sync)
        $avail = $this->validateAssignmentAvailability(
            $roleId,
            (array)$counselorClassIds,
            $homeroomClassId ? (int)$homeroomClassId : null,
            (int)$id
        );
        if (empty($avail['success'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', $avail['message'] ?? 'Penugasan kelas tidak valid.');
        }

        $result = $this->userService->updateUser($id, $clean);

        if (empty($result['success'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal memperbarui user.');
        }

        $warning = null;
        $sync = $this->syncClassAssignments(
            (int)$id,
            (int)($clean['role_id'] ?? 0),
            (array)$counselorClassIds,
            $homeroomClassId ? (int)$homeroomClassId : null
        );
        if (!empty($sync['warning'])) {
            $warning = $sync['warning'];
        }

        $redirect = redirect()->to(base_url('koordinator/users'))->with('success', $result['message'] ?? 'User berhasil diperbarui.');
        if ($warning) $redirect->with('warning', $warning);
        return $redirect;
    }

    /**
     * POST /koordinator/users/delete/{id}
     */
    public function delete($id)
    {
        require_permission('manage_users');

        $id = (int)$id;

        if ($id === (int)session()->get('user_id')) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $userOrRedirect = $this->getAllowedUserOrRedirect($id);
        if ($userOrRedirect instanceof \CodeIgniter\HTTP\RedirectResponse) return $userOrRedirect;

        $result = $this->userService->deleteUser($id);

        if (empty($result['success'])) {
            return redirect()->back()->with('error', $result['message'] ?? 'Gagal menghapus user.');
        }

        return redirect()->to(base_url('koordinator/users'))->with('success', $result['message'] ?? 'User berhasil dihapus.');
    }

    /**
     * POST /koordinator/users/toggle-active/{id}
     */
    public function toggleActive($id)
    {
        require_permission('manage_users');

        $id = (int)$id;

        if ($id === (int)session()->get('user_id')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda tidak dapat menonaktifkan akun sendiri.'
            ]);
        }

        $userOrRedirect = $this->getAllowedUserOrRedirect($id);
        if ($userOrRedirect instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $result = $this->userService->toggleActive($id);
        return $this->response->setJSON($result);
    }

    /**
     * POST /koordinator/users/reset-password/{id}
     */
    public function resetPassword($id)
    {
        require_permission('manage_users');

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->back()->with('error', 'Metode tidak valid.');
        }

        $id = (int)$id;

        $userOrRedirect = $this->getAllowedUserOrRedirect($id);
        if ($userOrRedirect instanceof \CodeIgniter\HTTP\RedirectResponse) return $userOrRedirect;

        $newPassword = $this->generateRandomPassword();
        $result      = $this->userService->changePassword($id, $newPassword);

        if (empty($result['success'])) {
            return redirect()->back()->with('error', $result['message'] ?? 'Gagal reset password.');
        }

        return redirect()->back()
            ->with('success', 'Password berhasil direset. Password baru: ' . $newPassword . ' (harap catat & sampaikan ke user).');
    }

    /**
     * POST /koordinator/users/upload-photo/{id}
     */
    public function uploadPhoto($id)
    {
        require_permission('manage_users');

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->back()->with('error', 'Metode tidak valid.');
        }

        $id = (int)$id;

        $userOrRedirect = $this->getAllowedUserOrRedirect($id);
        if ($userOrRedirect instanceof \CodeIgniter\HTTP\RedirectResponse) return $userOrRedirect;

        $rules = UserValidation::profilePhotoRules();
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', implode(', ', $this->validator->getErrors()));
        }

        $file   = $this->request->getFile('profile_photo');
        $result = $this->userService->uploadProfilePhoto($id, $file);

        if (empty($result['success'])) {
            return redirect()->back()->with('error', $result['message'] ?? 'Gagal upload foto.');
        }

        return redirect()->back()->with('success', $result['message'] ?? 'Foto berhasil diunggah.');
    }

    /**
     * GET /koordinator/users/export
     */
    public function export()
    {
        require_permission('manage_users');

        $allowedRoleIds = $this->allowedRoleIds();

        $filters = [
            'role_id'   => (string)($this->request->getGet('role_id') ?? ''),
            'is_active' => (string)($this->request->getGet('is_active') ?? ''),
            'search'    => (string)($this->request->getGet('search') ?? ''),
        ];

        if ($filters['role_id'] !== '') {
            if (!in_array((int)$filters['role_id'], $allowedRoleIds, true)) {
                $filters['role_id'] = '';
            }
        }

        $model = new UserModel();
        $model->asArray()
            ->select('users.*, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->whereIn('users.role_id', $allowedRoleIds);

        if ($filters['role_id'] !== '') $model->where('users.role_id', (int)$filters['role_id']);
        if ($filters['is_active'] !== '') $model->where('users.is_active', (int)$filters['is_active']);
        if (trim($filters['search']) !== '') {
            $q = trim($filters['search']);
            $model->groupStart()
                ->like('users.full_name', $q)
                ->orLike('users.username', $q)
                ->orLike('users.email', $q)
                ->groupEnd();
        }

        $rows = $model->orderBy('users.created_at', 'DESC')->findAll();

        $exportData = [];
        foreach ($rows as $u) {
            $exportData[] = [
                'ID'             => $u['id'],
                'Username'       => $u['username'],
                'Email'          => $u['email'],
                'Nama Lengkap'   => $u['full_name'],
                'Role'           => $u['role_name'],
                'Telepon'        => $u['phone'] ?? '-',
                'Status'         => ((int)$u['is_active'] === 1) ? 'Aktif' : 'Nonaktif',
                'Terakhir Login' => !empty($u['last_login']) ? date('d/m/Y H:i', strtotime($u['last_login'])) : '-',
                'Dibuat'         => !empty($u['created_at']) ? date('d/m/Y H:i', strtotime($u['created_at'])) : '-',
            ];
        }

        $filename = 'koordinator_users_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if (!empty($exportData)) {
            fputcsv($output, array_keys($exportData[0]));
        }
        foreach ($exportData as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * GET /koordinator/users/search?q=...
     */
    public function search()
    {
        require_permission('manage_users');

        $keyword = (string)$this->request->getGet('q');
        if (trim($keyword) === '') {
            return $this->response->setJSON(['results' => []]);
        }

        $allowedRoleIds = $this->allowedRoleIds();

        $model = new UserModel();
        $model->asArray()
            ->select('users.id, users.username, users.email, users.full_name, roles.role_name, users.role_id')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->whereIn('users.role_id', $allowedRoleIds)
            ->groupStart()
                ->like('users.full_name', $keyword)
                ->orLike('users.username', $keyword)
                ->orLike('users.email', $keyword)
            ->groupEnd()
            ->orderBy('users.full_name', 'ASC');

        $rows = $model->findAll(10);

        $results = [];
        foreach ($rows as $u) {
            $results[] = [
                'id'       => $u['id'],
                'text'     => $u['full_name'] . ' (' . $u['username'] . ')',
                'username' => $u['username'],
                'email'    => $u['email'],
                'role'     => $u['role_name'],
            ];
        }

        return $this->response->setJSON(['results' => $results]);
    }

    /**
     * POST /koordinator/users/change-password
     */
    public function changePassword()
    {
        require_permission('manage_users');

        $rules = [
            'old_password'         => 'required|validateOldPassword',
            'new_password'         => 'required|min_length[6]|max_length[255]',
            'new_password_confirm' => 'required|matches[new_password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $userId  = (int)session()->get('user_id');
        $newPass = (string)$this->request->getPost('new_password');

        $result = $this->userService->changePassword($userId, $newPass);

        if (empty($result['success'])) {
            return redirect()->back()->with('error', $result['message'] ?? 'Gagal mengubah password.');
        }

        return redirect()->back()->with('success', 'Password berhasil diubah.');
    }

    private function generateRandomPassword($length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
    }
}
