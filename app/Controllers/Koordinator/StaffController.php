<?php
namespace App\Controllers\Koordinator;

use App\Controllers\Koordinator\BaseKoordinatorController;
use App\Models\UserModel;
use App\Models\RoleModel;
use CodeIgniter\HTTP\RedirectResponse;

class StaffController extends BaseKoordinatorController
{
    protected $userModel;
    protected $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    /** List semua Guru BK & Wali Kelas */
    public function index()
    {
        $this->requireKoordinator();

        $roles = $this->roleModel->whereIn('role_name', ['Guru BK', 'Wali Kelas'])->findAll();
        $roleIds = array_column($roles, 'id');

        // Catatan: paginate() CI4 mengisi $this->userModel->pager
        $staff = $this->userModel
            ->whereIn('role_id', $roleIds)
            ->orderBy('full_name', 'ASC')
            ->paginate(15);

        $pager = $this->userModel->pager;

        return view('koordinator/staff/index', compact('staff', 'pager'));
    }

    /** Form create */
    public function create()
    {
        $this->requireKoordinator();

        $roles = $this->roleModel->whereIn('role_name', ['Guru BK', 'Wali Kelas'])->findAll();
        return view('koordinator/staff/form', ['roles' => $roles]);
    }

    /** Simpan akun baru */
    public function store()
    {
        $this->requireKoordinator();

        $data = $this->request->getPost();
        $rules = [
            'username'  => 'required|min_length[4]|is_unique[users.username]',
            'full_name' => 'required',
            'email'     => 'permit_empty|valid_email|is_unique[users.email]',
            'password'  => 'required|min_length[6]',
            'role_id'   => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Validasi gagal.')
                ->with('errors', $this->validator->getErrors());
        }

        $data['password']  = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['is_active'] = 1;

        $this->userModel->insert($data);
        return redirect()->to(route_to('koordinator.staff.index'))
            ->with('success', 'Akun berhasil dibuat.');
    }

    /** Form edit */
    public function edit(int $id)
    {
        $this->requireKoordinator();

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        $user  = $this->normalizeRecord($user);
        $roles = $this->roleModel->whereIn('role_name', ['Guru BK', 'Wali Kelas'])->findAll();

        return view('koordinator/staff/form', compact('user', 'roles'));
    }

    /** Update akun */
    public function update(int $id)
    {
        $this->requireKoordinator();

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }
        $user = $this->normalizeRecord($user);

        $data  = $this->request->getPost();
        // is_unique abaikan diri sendiri
        $rules = [
            'username'  => "required|min_length[4]|is_unique[users.username,id,{$id}]",
            'full_name' => 'required',
            'email'     => "permit_empty|valid_email|is_unique[users.email,id,{$id}]",
            'role_id'   => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Validasi gagal.')
                ->with('errors', $this->validator->getErrors());
        }

        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }

        $this->userModel->update($id, $data);
        return redirect()->to(route_to('koordinator.staff.index'))
            ->with('success', 'Akun diperbarui.');
    }

    /** Aktif/Nonaktifkan akun */
    public function toggleActive(int $id): RedirectResponse
    {
        $this->requireKoordinator();

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        $userArr  = $this->normalizeRecord($user);
        $isActive = (int)($userArr['is_active'] ?? 0);

        $this->userModel->update($id, ['is_active' => $isActive ? 0 : 1]);
        return redirect()->back()->with('success', 'Status akun diubah.');
    }

    /**
     * Normalisasi record menjadi array agar aman diakses dengan ['key']
     * - Jika objek punya toArray(), gunakan itu.
     * - Jika objek biasa, fallback json_encode/decode.
     */
    private function normalizeRecord($row): array
    {
        if (is_array($row)) {
            return $row;
        }
        if (is_object($row)) {
            if (method_exists($row, 'toArray')) {
                return $row->toArray();
            }
            // Fallback aman: konversi via JSON
            return json_decode(json_encode($row), true) ?? [];
        }
        return [];
    }
}
