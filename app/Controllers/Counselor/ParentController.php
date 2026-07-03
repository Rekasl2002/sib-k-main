<?php
/**
 * File Path: app/Controllers/Counselor/ParentController.php
 *
 * Counselor • Parent Account Management
 * Guru BK mengelola akun orang tua dari siswa binaan (kelas yang memiliki counselor_id = Guru BK login).
 * CRUD: C,R,U,D* — hanya orang tua yang terhubung ke siswa kelas binaan Guru BK ini.
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\UserService;

class ParentController extends BaseController
{
    protected UserModel $userModel;
    protected UserService $userService;
    protected $db;

    public function __construct()
    {
        helper(['auth']);
        $this->userModel    = new UserModel();
        $this->userService  = new UserService();
        $this->db           = \Config\Database::connect();
    }

    private function me(): int
    {
        return (int)(function_exists('auth_id') ? auth_id() : 0);
    }

    private function normalizePhone(?string $phone): string
    {
        $p = trim((string)($phone ?? ''));
        if ($p === '') return '';
        $p = preg_replace('/[^\d+]/', '', $p) ?? '';
        if (str_starts_with($p, '+62')) return '0' . substr($p, 3);
        if (str_starts_with($p, '62'))  return '0' . substr($p, 2);
        return $p;
    }

    private function parentRoleId(): int
    {
        $row = $this->db->table('roles')->select('id')->where('role_name', 'Orang Tua')->get()->getRowArray();
        return (int)($row['id'] ?? 6);
    }

    /**
     * Kumpulkan class_id yang dibina Guru BK yang login (dari classes.counselor_id).
     */
    private function scopedClassIds(): array
    {
        $uid = $this->me();
        if ($uid <= 0) return [];

        $rows = $this->db->table('classes')
            ->select('id')
            ->where('counselor_id', $uid)
            ->where('deleted_at', null)
            ->get()->getResultArray();

        return array_values(array_unique(array_filter(array_map(
            static fn($r) => (int)($r['id'] ?? 0), $rows
        ))));
    }

    /**
     * Daftar orang tua yang terhubung ke siswa kelas binaan Guru BK ini.
     */
    private function parentsInScope(array $classIds): array
    {
        if (empty($classIds)) return [];

        return $this->db->table('users p')
            ->select([
                'p.id',
                'p.username',
                'p.full_name',
                'p.email',
                'p.phone',
                'p.is_active',
                'COUNT(s.id) AS child_count',
                "GROUP_CONCAT(DISTINCT CONCAT(c.grade_level, ' ', c.class_name) ORDER BY c.grade_level, c.class_name SEPARATOR ', ') AS child_classes",
            ])
            ->join('students s', 's.parent_id = p.id AND s.deleted_at IS NULL', 'inner')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->whereIn('s.class_id', $classIds)
            ->where('p.deleted_at', null)
            ->groupBy('p.id')
            ->orderBy('p.full_name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Validasi orang tua masuk scope Guru BK ini.
     */
    private function parentInScope(int $parentId, array $classIds): ?array
    {
        if ($parentId <= 0 || empty($classIds)) return null;

        $parent = $this->db->table('users p')
            ->select('p.*')
            ->join('students s', 's.parent_id = p.id AND s.deleted_at IS NULL', 'inner')
            ->where('p.id', $parentId)
            ->whereIn('s.class_id', $classIds)
            ->where('p.deleted_at', null)
            ->groupBy('p.id')
            ->get()->getRowArray();

        return $parent ?: null;
    }

    /**
     * GET /counselor/parents
     * Daftar akun orang tua siswa binaan.
     */
    public function index()
    {
        $classIds = $this->scopedClassIds();

        $parents = $this->parentsInScope($classIds);

        $totalParents    = count($parents);
        $activeParents   = count(array_filter($parents, static fn($p) => (int)($p['is_active'] ?? 0) === 1));
        $inactiveParents = $totalParents - $activeParents;

        return view('counselor/parents/index', [
            'pageTitle'       => 'Akun Orang Tua Siswa Binaan',
            'parents'         => $parents,
            'classIds'        => $classIds,
            'totalParents'    => $totalParents,
            'activeParents'   => $activeParents,
            'inactiveParents' => $inactiveParents,
        ]);
    }

    /**
     * GET /counselor/parents/(:num)
     * Detail satu akun orang tua beserta daftar anak binaan.
     */
    public function show($id)
    {
        $classIds = $this->scopedClassIds();
        $parent   = $this->parentInScope((int)$id, $classIds);

        if (!$parent) {
            return redirect()->to(base_url('counselor/parents'))
                ->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        $children = $this->db->table('students s')
            ->select('s.id AS student_id, u.full_name, s.nisn, s.gender, s.status, c.class_name, c.grade_level')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.parent_id', (int)$id)
            ->whereIn('s.class_id', $classIds)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()->getResultArray();

        return view('counselor/parents/show', [
            'pageTitle' => 'Detail Orang Tua',
            'parent'    => $parent,
            'children'  => $children,
        ]);
    }

    /**
     * GET /counselor/parents/create
     */
    public function create()
    {
        return view('counselor/parents/form', [
            'pageTitle' => 'Tambah Akun Orang Tua',
            'mode'      => 'create',
            'parent'    => [],
            'action'    => base_url('counselor/parents/store'),
        ]);
    }

    /**
     * POST /counselor/parents/store
     */
    public function store()
    {
        $post     = $this->request->getPost() ?? [];
        $result   = $this->createParentFromPost($post);

        if (!($result['success'] ?? false)) {
            return redirect()->back()->withInput()->with('error', $result['message'] ?? 'Gagal membuat akun orang tua.');
        }

        return redirect()->to(base_url('counselor/parents'))
            ->with('success', 'Akun orang tua berhasil dibuat. Hubungkan melalui form edit siswa.');
    }

    /**
     * GET /counselor/parents/edit/(:num)
     */
    public function edit($id)
    {
        $classIds = $this->scopedClassIds();
        $parent   = $this->parentInScope((int)$id, $classIds);

        if (!$parent) {
            return redirect()->to(base_url('counselor/parents'))
                ->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        return view('counselor/parents/form', [
            'pageTitle' => 'Edit Akun Orang Tua',
            'mode'      => 'edit',
            'parent'    => $parent,
            'action'    => base_url('counselor/parents/update/' . (int)$id),
        ]);
    }

    /**
     * POST /counselor/parents/update/(:num)
     */
    public function update($id)
    {
        $classIds = $this->scopedClassIds();
        $parent   = $this->parentInScope((int)$id, $classIds);

        if (!$parent) {
            return redirect()->to(base_url('counselor/parents'))
                ->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        $post     = $this->request->getPost() ?? [];
        $fullName = trim((string)($post['full_name'] ?? ''));
        $username = trim((string)($post['username'] ?? ''));
        $email    = strtolower(trim((string)($post['email'] ?? '')));
        $phone    = $this->normalizePhone($post['phone'] ?? '');

        if ($fullName === '' || $username === '') {
            return redirect()->back()->withInput()->with('error', 'Nama lengkap dan username wajib diisi.');
        }

        $dupUsername = $this->db->table('users')
            ->where('username', $username)->where('id !=', (int)$id)->where('deleted_at', null)
            ->countAllResults();
        if ($dupUsername > 0) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan.');
        }

        if ($email !== '') {
            $dupEmail = $this->db->table('users')
                ->where('email', $email)->where('id !=', (int)$id)->where('deleted_at', null)
                ->countAllResults();
            if ($dupEmail > 0) {
                return redirect()->back()->withInput()->with('error', 'Email sudah digunakan.');
            }
        }

        $payload = [
            'full_name' => $fullName,
            'username'  => $username,
            'email'     => $email !== '' ? $email : null,
            'phone'     => $phone !== '' ? $phone : null,
            'is_active' => !empty($post['is_active']) ? 1 : 0,
        ];

        if (trim((string)($post['password'] ?? '')) !== '') {
            $payload['password'] = (string)$post['password'];
        }

        $this->userModel->update((int)$id, $payload);

        return redirect()->to(base_url('counselor/parents/' . (int)$id))
            ->with('success', 'Akun orang tua berhasil diperbarui.');
    }

    /**
     * POST /counselor/parents/delete/(:num)
     * Lepas keterhubungan orang tua dari siswa binaan; hapus akun jika tidak ada anak lain.
     */
    public function delete($id)
    {
        $classIds = $this->scopedClassIds();
        $parent   = $this->parentInScope((int)$id, $classIds);

        if (!$parent) {
            return redirect()->to(base_url('counselor/parents'))
                ->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        // Lepas dari siswa binaan
        $this->db->table('students')
            ->whereIn('class_id', $classIds)
            ->where('parent_id', (int)$id)
            ->update(['parent_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);

        $remaining = $this->db->table('students')
            ->where('parent_id', (int)$id)->where('deleted_at', null)->countAllResults();

        if ($remaining === 0) {
            $this->userModel->delete((int)$id);
            return redirect()->to(base_url('counselor/parents'))->with('success', 'Akun orang tua berhasil dihapus.');
        }

        return redirect()->to(base_url('counselor/parents'))
            ->with('warning', 'Keterhubungan dilepas. Akun tidak dihapus karena masih terhubung dengan siswa lain.');
    }

    /**
     * POST /counselor/parents/reset-password/(:num)
     * Reset password akun orang tua (hanya yang terhubung ke siswa binaan Guru BK ini).
     */
    public function resetParentPassword($id)
    {
        $classIds = $this->scopedClassIds();
        $parent   = $this->parentInScope((int)$id, $classIds);

        if (!$parent) {
            return redirect()->to(base_url('counselor/parents'))
                ->with('error', 'Akun orang tua tidak ditemukan di kelas binaan Anda.');
        }

        $newPassword = $this->generateRandomPassword();
        $result      = $this->userService->changePassword((int)$id, $newPassword);

        if (empty($result['success'])) {
            return redirect()->back()->with('error', $result['message'] ?? 'Gagal reset password.');
        }

        return redirect()->back()
            ->with('success', 'Password orang tua berhasil direset. Password baru: ' . $newPassword . ' (harap catat & sampaikan ke orang tua).');
    }

    private function generateRandomPassword(int $length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password   = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
    }

    private function createParentFromPost(array $post): array
    {
        $fullName = trim((string)($post['full_name'] ?? ''));
        $username = trim((string)($post['username'] ?? ''));
        $email    = strtolower(trim((string)($post['email'] ?? '')));
        $phone    = $this->normalizePhone($post['phone'] ?? '');
        $password = trim((string)($post['password'] ?? ''));

        if ($fullName === '' || $username === '') {
            return ['success' => false, 'message' => 'Nama lengkap dan username wajib diisi.'];
        }
        if ($password === '') $password = 'orangtua123';

        $dup = $this->db->table('users')->where('username', $username)->where('deleted_at', null)->countAllResults();
        if ($dup > 0) return ['success' => false, 'message' => 'Username sudah digunakan.'];

        if ($email !== '') {
            $dupE = $this->db->table('users')->where('email', $email)->where('deleted_at', null)->countAllResults();
            if ($dupE > 0) return ['success' => false, 'message' => 'Email sudah digunakan.'];
        }

        $parentId = (int)$this->userModel->insert([
            'role_id'   => $this->parentRoleId(),
            'username'  => $username,
            'email'     => $email !== '' ? $email : null,
            'password'  => $password,
            'full_name' => $fullName,
            'phone'     => $phone !== '' ? $phone : null,
            'is_active' => 1,
        ], true);

        if ($parentId <= 0) return ['success' => false, 'message' => 'Gagal menyimpan akun orang tua.'];

        return ['success' => true, 'parent_id' => $parentId];
    }
}
