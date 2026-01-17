<?php

/**
 * File Path: app/Controllers/Admin/UserController.php
 *
 * User Controller
 * Handle CRUD operations untuk User management
 *
 * @package    SIB-K
 * @subpackage Controllers/Admin
 * @category   User Management
 * @author     Development Team
 * @created    2025-01-05
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\UserService;
use App\Validation\UserValidation;
use App\Models\UserModel;

class UserController extends BaseController
{
    protected $userService;
    protected $userModel;

    public function __construct()
    {
        helper('permission');
        $this->userService = new UserService();
        $this->userModel   = new UserModel();
    }

    /**
     * Display users list
     */
    public function index()
    {
        require_permission('manage_users');

        $filters = [
            'role_id'   => $this->request->getGet('role_id'),
            'is_active' => $this->request->getGet('is_active'),
            'search'    => $this->request->getGet('search'),
            'order_by'  => $this->request->getGet('order_by') ?? 'users.created_at',
            'order_dir' => $this->request->getGet('order_dir') ?? 'DESC',
        ];

        $usersData = $this->userService->getAllUsers($filters, 0); // 0 = tanpa paginate DB
        $roles     = $this->userService->getRoles();
        $stats     = (array) $this->userService->getUserStatistics();

        /**
         * ✅ FIX: Pastikan kartu "Admin" menghitung jumlah akun Admin yang benar.
         * Banyak kasus: key stats tidak cocok dengan view, atau role Admin tidak ikut terhitung.
         * Di sini kita paksa hitung ulang admin_count berdasarkan role_id Admin.
         */
        $adminRoleId = null;
        if (!empty($roles) && is_array($roles)) {
            foreach ($roles as $r) {
                $rid  = $r['id'] ?? $r['role_id'] ?? null;
                $name = strtolower((string) ($r['role_name'] ?? $r['name'] ?? ''));
                $slug = strtolower((string) ($r['slug'] ?? ''));
                if ($rid && ($slug === 'admin' || $name === 'admin' || str_contains($name, 'admin'))) {
                    $adminRoleId = (int) $rid;
                    break;
                }
            }
        }

        $adminCount = 0;
        if ($adminRoleId) {
            $adminCount = (int) $this->userModel->where('role_id', $adminRoleId)->countAllResults();
        }

        // set beberapa key agar view apapun tetap kebaca
        $stats['admin'] = $adminCount;
        if (!isset($stats['by_role']) || !is_array($stats['by_role'])) {
            $stats['by_role'] = [];
        }
        $stats['by_role']['Admin'] = $adminCount;

        $data = [
            'title'      => 'Manajemen Pengguna',
            'page_title' => 'Manajemen Pengguna',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Pengguna', 'link' => null],
            ],
            'users'   => $usersData['users'],
            'pager'   => $usersData['pager'],
            'roles'   => $roles,
            'stats'   => $stats,
            'filters' => $filters,
        ];

        return view('admin/users/index', $data);
    }

    /**
     * Display create user form
     */
    public function create()
    {
        require_permission('manage_users');

        $roles   = $this->userService->getRoles();
        $classes = $this->userService->getClasses();

        $data = [
            'title'      => 'Tambah Pengguna Baru',
            'page_title' => 'Tambah Pengguna',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Pengguna', 'link' => base_url('admin/users')],
                ['title' => 'Tambah', 'link' => null],
            ],
            'roles'      => $roles,
            'classes'    => $classes,
            'validation' => \Config\Services::validation(),
        ];

        return view('admin/users/create', $data);
    }

    /**
     * Store new user
     */
    public function store()
    {
        require_permission('manage_users');

        $rules = UserValidation::createRules();
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = UserValidation::sanitizeInput($this->request->getPost());
        $data['student'] = (array) $this->request->getPost('student');

        // ✅ Penting: jangan simpan/overwite profile_photo dengan "default avatar lama"
        // Jika form mengirim profile_photo kosong/default, kita buang supaya DB tetap NULL (dan view pakai default-avatar.svg).
        $data = $this->normalizeProfilePhotoInput($data, true);

        $result = $this->userService->createUser($data);

        if (! $result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('/admin/users')->with('success', 'User berhasil dibuat.');
    }

    /**
     * Display user detail
     */
    public function show($id)
    {
        require_permission('manage_users');

        $user = $this->userService->getUserById((int) $id);

        if (! $user) {
            return redirect()->to('admin/users')
                ->with('error', 'User tidak ditemukan');
        }

        $data = [
            'title'      => 'Detail Pengguna',
            'page_title' => 'Detail Pengguna',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Pengguna', 'link' => base_url('admin/users')],
                ['title' => 'Detail', 'link' => null],
            ],
            'user' => $user,
        ];

        return view('admin/users/show', $data);
    }

    /**
     * Display edit user form
     */
    public function edit($id)
    {
        require_permission('manage_users');

        $user = $this->userService->getUserById((int) $id);
        if (! $user) {
            return redirect()->to('admin/users')
                ->with('error', 'User tidak ditemukan');
        }

        $roles = $this->userService->getRoles();

        $data = [
            'title'      => 'Edit Pengguna',
            'page_title' => 'Edit Pengguna',
            'breadcrumb' => [
                ['title' => 'Admin', 'link' => base_url('admin/dashboard')],
                ['title' => 'Pengguna', 'link' => base_url('admin/users')],
                ['title' => 'Edit', 'link' => null],
            ],
            'user'       => $user,
            'roles'      => $roles,
            'validation' => \Config\Services::validation(),
        ];

        return view('admin/users/edit', $data);
    }

    /**
     * Update user data
     */
    public function update(int $id)
    {
        require_permission('manage_users');

        // 1. Ambil semua data POST
        $postData = $this->request->getPost();

        // ✅ Jangan overwrite profile_photo dengan nilai kosong/default dari form edit
        // (misal hidden input atau nilai default-avatar.png yang tersimpan)
        $postData = $this->normalizeProfilePhotoInput($postData, false);

        // 2. Proteksi agar user tidak bisa menonaktifkan/mengganti role diri sendiri
        $currentUserId = (int) session()->get('user_id');
        if ($id === $currentUserId) {
            $existing = $this->userModel->find($id);
            if ($existing) {
                $postData['is_active'] = $existing['is_active'];
                $postData['role_id']   = $existing['role_id'];
            }
        } else {
            $postData['is_active'] = !empty($postData['is_active']) ? 1 : 0;
        }

        $result = $this->userService->updateUser($id, $postData);

        if (! $result['success']) {
            return redirect()->back()->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to('/admin/users')->with('success', $result['message']);
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        require_permission('manage_users');

        $result = $this->userService->deleteUser((int) $id);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->to('admin/users')->with('success', $result['message']);
    }

    /**
     * Toggle user active status
     *
     * ✅ FIX:
     * - Kalau dipanggil AJAX: return JSON (seperti sebelumnya)
     * - Kalau dibuka via browser (klik link biasa): redirect balik ke /admin/users
     *   sehingga tidak "nyangkut" di halaman JSON (hitam).
     */
    public function toggleActive($id)
    {
        require_permission('manage_users');

        $result = $this->userService->toggleActive((int) $id);

        $accept = strtolower((string) $this->request->getHeaderLine('Accept'));
        $isJson = str_contains($accept, 'application/json');

        if ($this->request->isAJAX() || $isJson) {
            return $this->response->setJSON($result);
        }

        if (!empty($result['success'])) {
            return redirect()->to('/admin/users')->with('success', $result['message'] ?? 'Status user berhasil diubah.');
        }

        return redirect()->to('/admin/users')->with('error', $result['message'] ?? 'Gagal mengubah status user.');
    }

    /**
     * Reset user password
     */
    public function resetPassword($id)
    {
        require_permission('manage_users');

        $user = $this->userService->getUserById((int) $id);
        if (! $user) {
            return redirect()->to('admin/users')->with('error', 'User tidak ditemukan');
        }

        $newPassword = $this->generateRandomPassword();
        $result      = $this->userService->changePassword((int) $id, $newPassword);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()
            ->with('success', "Password berhasil direset. Password baru: <strong>{$newPassword}</strong>. Harap catat dan sampaikan kepada user.");
    }

    /**
     * Upload profile photo
     */
    public function uploadPhoto($id)
    {
        require_permission('manage_users');

        $rules = UserValidation::profilePhotoRules();
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('error', implode(', ', $this->validator->getErrors()));
        }

        $file   = $this->request->getFile('profile_photo');
        $result = $this->userService->uploadProfilePhoto((int) $id, $file);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Export users to CSV
     */
    public function export()
    {
        require_permission('manage_users');

        $filters = [
            'role_id'   => $this->request->getGet('role_id'),
            'is_active' => $this->request->getGet('is_active'),
            'search'    => $this->request->getGet('search'),
        ];

        $usersData = $this->userService->getAllUsers($filters, 10000);

        $exportData = [];
        foreach ($usersData['users'] as $user) {
            $exportData[] = [
                'ID'             => $user['id'],
                'Username'       => $user['username'],
                'Email'          => $user['email'],
                'Nama Lengkap'   => $user['full_name'],
                'Role'           => $user['role_name'],
                'Telepon'        => $user['phone'] ?? '-',
                'Status'         => ((int) $user['is_active'] === 1) ? 'Aktif' : 'Nonaktif',
                'Terakhir Login' => ! empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-',
                'Dibuat'         => date('d/m/Y H:i', strtotime($user['created_at'])),
            ];
        }

        $filename = 'users_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

        if (! empty($exportData)) {
            fputcsv($output, array_keys($exportData[0]));
        }
        foreach ($exportData as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Search users via AJAX
     */
    public function search()
    {
        require_permission('manage_users');

        $keyword = $this->request->getGet('q');
        if (empty($keyword)) {
            return $this->response->setJSON(['results' => []]);
        }

        $filters = ['search' => $keyword];
        $usersData = $this->userService->getAllUsers($filters, 10);

        $results = [];
        foreach ($usersData['users'] as $user) {
            $results[] = [
                'id'       => $user['id'],
                'text'     => $user['full_name'] . ' (' . $user['username'] . ')',
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role_name'],
            ];
        }

        return $this->response->setJSON(['results' => $results]);
    }

    private function generateRandomPassword($length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%';
        $password   = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
    }

    public function changePassword()
    {
        require_permission('manage_users');

        $rules = [
            'old_password'         => 'required|validateOldPassword',
            'new_password'         => 'required|min_length[6]|max_length[255]',
            'new_password_confirm' => 'required|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $userId  = (int) session()->get('user_id');
        $newPass = (string) $this->request->getPost('new_password');

        $result = $this->userService->changePassword($userId, $newPass);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', 'Password berhasil diubah.');
    }

    /**
     * Normalisasi input profile_photo dari form:
     * - Jika kosong: untuk update -> UNSET (agar tidak menimpa foto lama)
     * - Jika default lama: UNSET (agar view jatuh ke default-avatar.svg)
     * - Untuk create (isCreate=true): juga dibuang agar DB NULL (lebih bersih)
     */
    private function normalizeProfilePhotoInput(array $data, bool $isCreate = false): array
    {
        if (!array_key_exists('profile_photo', $data)) {
            return $data;
        }

        $raw = trim((string) $data['profile_photo']);

        if ($raw === '') {
            unset($data['profile_photo']);
            return $data;
        }

        if ($this->isLegacyDefaultAvatarValue($raw)) {
            unset($data['profile_photo']);
            return $data;
        }

        return $data;
    }

    /**
     * Deteksi nilai default avatar lama yang sering tersimpan di DB/form.
     * Jika ditemukan, harus dianggap "tidak ada foto".
     */
    private function isLegacyDefaultAvatarValue(string $value): bool
    {
        $v = strtolower(ltrim(str_replace('\\', '/', trim($value)), '/'));
        $b = strtolower(basename($v));

        $legacy = [
            'default-avatar.png',
            'default-avatar.jpg',
            'default-avatar.jpeg',
            'default-avatar.svg',

            'assets/images/default-avatar.png',
            'assets/images/default-avatar.svg',
            'assets/images/users/default-avatar.png',
            'assets/images/users/default-avatar.svg',

            'public/assets/images/default-avatar.png',
            'public/assets/images/default-avatar.svg',
            'public/assets/images/users/default-avatar.png',
            'public/assets/images/users/default-avatar.svg',
        ];

        return in_array($v, $legacy, true) || in_array($b, $legacy, true);
    }
}
