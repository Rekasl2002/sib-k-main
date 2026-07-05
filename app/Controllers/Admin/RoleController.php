<?php

/**
 * File Path: app/Controllers/Admin/RoleController.php
 *
 * Role Controller
 * RBAC: CRUD Role + Sinkronisasi Izin (role_permissions)
 *
 * Catatan:
 * - Peran bawaan sistem (id 1-6: Admin, Koordinator BK, Guru BK, Wali Kelas,
 *   Siswa, Orang Tua) TIDAK boleh diganti nama / dihapus karena nama peran
 *   dipakai langsung oleh kode (has_role, sidebar, filter rute). Deskripsi
 *   dan daftar izinnya tetap boleh diubah.
 * - Daftar izin ditampilkan per kelompok fitur dengan label bahasa Indonesia
 *   dari PermissionModel::catalog() agar mudah dipahami orang awam.
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;

class RoleController extends BaseController
{
    /**
     * id peran bawaan sistem yang namanya dikunci (dipakai kode aplikasi).
     */
    private const BUILTIN_ROLE_IDS = [1, 2, 3, 4, 5, 6];

    protected $db;
    protected $roleModel;
    protected $permModel;
    protected $rpModel;

    public function __construct()
    {
        $this->db        = \Config\Database::connect();
        $this->roleModel = new RoleModel();           // kolom: role_name, description
        $this->permModel = new PermissionModel();     // kolom: permission_name, description
        $this->rpModel   = new RolePermissionModel(); // pivot role_permissions
        helper(['permission', 'form']);
    }

    private function isBuiltinRole(int $id): bool
    {
        return in_array($id, self::BUILTIN_ROLE_IDS, true);
    }

    // GET /admin/roles
    public function index()
    {
        require_permission('manage_roles');

        $roles = $this->db->table('roles r')
            ->select('r.*, COUNT(DISTINCT rp.id) AS permission_count, COUNT(DISTINCT u.id) AS user_count')
            ->join('role_permissions rp', 'rp.role_id = r.id', 'left')
            ->join('users u', 'u.role_id = r.id AND u.deleted_at IS NULL', 'left')
            ->groupBy('r.id')
            ->orderBy('r.id', 'ASC')
            ->get()->getResultArray();

        $stats = [
            'total_roles'       => count($roles),
            'builtin_roles'     => count(array_filter($roles, fn($r) => $this->isBuiltinRole((int) $r['id']))),
            'total_permissions' => (int) $this->db->table('permissions')->countAllResults(),
            'total_users'       => (int) $this->db->table('users')->where('deleted_at IS NULL')->countAllResults(),
        ];

        return view('admin/roles/index', [
            'title'          => 'Kelola Peran',
            'page_title'     => 'Kelola Peran',
            'roles'          => $roles,
            'stats'          => $stats,
            'builtinRoleIds' => self::BUILTIN_ROLE_IDS,
        ]);
    }

    // GET /admin/roles/create
    public function create()
    {
        require_permission('manage_roles');

        return view('admin/roles/create', [
            'title'              => 'Tambah Peran',
            'page_title'         => 'Tambah Peran',
            'groupedPermissions' => $this->permModel->getPermissionsGroupedIndo(),
        ]);
    }

    // POST /admin/roles/store
    public function store()
    {
        require_permission('manage_roles');

        $data = $this->request->getPost(['role_name', 'description']);

        if (! $this->roleModel->insert($data)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->roleModel->errors()));
        }

        $roleId  = (int) $this->roleModel->getInsertID();
        $permIds = (array) $this->request->getPost('permissions');

        $this->syncPermissions($roleId, $permIds);

        return redirect()->to(route_to('admin.roles'))->with('success', 'Peran baru berhasil dibuat.');
    }

    // GET /admin/roles/edit/{id}
    public function edit(int $id)
    {
        require_permission('manage_roles');

        $role = $this->roleModel->find($id);
        if (! $role) {
            return redirect()->to(base_url('admin/roles'))
                ->with('error', 'Peran tidak ditemukan.');
        }

        $assignedIds = array_map('intval', array_column(
            $this->db->table('role_permissions')
                ->select('permission_id')
                ->where('role_id', $id)
                ->get()->getResultArray(),
            'permission_id'
        ));

        $userCount = (int) $this->db->table('users')
            ->where('role_id', $id)
            ->where('deleted_at IS NULL')
            ->countAllResults();

        return view('admin/roles/edit', [
            'title'              => 'Edit Peran & Izin',
            'page_title'         => 'Edit Peran & Izin',
            'role'               => $role,
            'groupedPermissions' => $this->permModel->getPermissionsGroupedIndo(),
            'assignedIds'        => $assignedIds,
            'userCount'          => $userCount,
            'isBuiltin'          => $this->isBuiltinRole($id),
        ]);
    }

    // POST /admin/roles/update/{id}
    public function update($id)
    {
        require_permission('manage_roles');

        $id   = (int) $id;
        $role = $this->roleModel->find($id);
        if (! $role) {
            return redirect()->to(route_to('admin.roles'))->with('error', 'Peran tidak ditemukan.');
        }

        $data = $this->request->getPost(['role_name', 'description']);

        // Nama peran bawaan dikunci: dipakai langsung oleh kode aplikasi (has_role, menu, rute).
        if ($this->isBuiltinRole($id)) {
            $data['role_name'] = $role['role_name'];
        }

        if (! $this->roleModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->roleModel->errors()));
        }

        return redirect()->to(base_url('admin/roles/edit/' . $id))
            ->with('success', 'Data peran berhasil diperbarui.');
    }

    // POST /admin/roles/delete/{id}
    public function delete($id)
    {
        require_permission('manage_roles');

        $id = (int) $id;
        if ($this->isBuiltinRole($id)) {
            return redirect()->back()->with('error', 'Peran bawaan sistem tidak boleh dihapus.');
        }

        $role = $this->roleModel->find($id);
        if (! $role) {
            return redirect()->to(route_to('admin.roles'))->with('error', 'Peran tidak ditemukan.');
        }

        if (! $this->roleModel->canDelete($id)) {
            return redirect()->back()->with('error', 'Peran masih digunakan oleh pengguna dan tidak dapat dihapus.');
        }

        $this->db->transStart();
        $this->rpModel->where('role_id', $id)->delete();
        $this->roleModel->delete($id);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal menghapus peran.');
        }

        return redirect()->to(route_to('admin.roles'))->with('success', 'Peran berhasil dihapus.');
    }

    // GET /admin/roles/permissions/{id} → alias edit
    public function permissions($id)
    {
        return $this->edit((int) $id);
    }

    // POST /admin/roles/assign-permissions/{id}
    public function assignPermissions(int $id)
    {
        require_permission('manage_roles');

        $role = $this->roleModel->find($id);
        if (! $role) {
            return redirect()->to(route_to('admin.roles'))->with('error', 'Peran tidak ditemukan.');
        }

        // ambil 'permissions' (tanpa bracket)
        $permissionIds = (array) ($this->request->getPost('permissions') ?? []);
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        $this->db->transStart();

        // reset izin lama
        $this->db->table('role_permissions')->where('role_id', $id)->delete();

        if (! empty($permissionIds)) {
            $now = date('Y-m-d H:i:s');
            $batch = [];
            foreach ($permissionIds as $pid) {
                $batch[] = [
                    'role_id'       => $id,
                    'permission_id' => $pid,
                    'created_at'    => $now,
                ];
            }
            $this->db->table('role_permissions')->insertBatch($batch);
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan izin.');
        }

        // segarkan cache izin session bila role yg diedit = role user yang login
        if ((int) session('role_id') === (int) $id) {
            $keys = $this->db->table('role_permissions rp')
                ->select('p.permission_name')
                ->join('permissions p', 'p.id = rp.permission_id')
                ->where('rp.role_id', $id)
                ->get()->getResultArray();

            session()->set('auth_permissions', array_map(static fn($r) => $r['permission_name'], $keys));
        } else {
            session()->remove('auth_permissions');
        }

        return redirect()->to(base_url('admin/roles/edit/' . $id))
            ->with('success', 'Izin akses berhasil disimpan dan langsung berlaku.');
    }

    /**
     * Sinkronisasi izin role:
     * - Hapus semua izin lama untuk role
     * - Insert ulang sesuai daftar terbaru
     */
    private function syncPermissions(int $roleId, array $permIds): void
    {
        // hapus dulu semua izin lama
        $this->rpModel->where('role_id', $roleId)->delete();

        // sanitasi
        $permIds = array_values(array_unique(array_filter($permIds, 'is_numeric')));
        if (!$permIds) return;

        $now   = date('Y-m-d H:i:s');
        $batch = [];
        foreach ($permIds as $pid) {
            $batch[] = [
                'role_id'       => $roleId,
                'permission_id' => (int) $pid,
                'created_at'    => $now, // tabel punya ini; tidak ada updated_at
            ];
        }
        $this->rpModel->insertBatch($batch);
    }
}
