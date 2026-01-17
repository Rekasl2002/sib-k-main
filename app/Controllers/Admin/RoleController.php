<?php

/**
 * File Path: app/Controllers/Admin/RoleController.php
 *
 * Role Controller
 * RBAC: CRUD Role + Sinkronisasi Izin (role_permissions)
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;

class RoleController extends BaseController
{
    protected $db;
    protected $roleModel;
    protected $permModel;
    protected $rpModel;

    public function __construct()
    {
        $this->db        = \Config\Database::connect();
        $this->roleModel = new RoleModel();           // pastikan RoleModel memakai kolom role_name, description
        $this->permModel = new PermissionModel();     // pastikan PermissionModel memakai kolom permission_name, description
        $this->rpModel   = new RolePermissionModel(); // pivot role_permissions
        helper(['permission', 'form']);
    }

    // GET /admin/roles
    public function index()
    {
        require_permission('manage_roles');

        $roles = $this->db->table('roles r')
            ->select('r.*, COUNT(rp.id) AS permission_count')
            ->join('role_permissions rp', 'rp.role_id = r.id', 'left')
            ->groupBy('r.id')
            ->orderBy('r.role_name', 'ASC')
            ->get()->getResultArray();

        // Tambahan untuk judul tab (dibaca oleh layouts/partials/title-meta.php)
        return view('admin/roles/index', [
            'title'      => 'Manajemen Peran',
            'page_title' => 'Manajemen Peran',
            'roles'      => $roles,
        ]);
    }

    // GET /admin/roles/create
    public function create()
    {
        require_permission('manage_roles');

        // konsisten gunakan permission_name
        $perms = $this->permModel->orderBy('permission_name', 'ASC')->findAll();

        // Tambahan untuk judul tab (dibaca oleh layouts/partials/title-meta.php)
        return view('admin/roles/create', [
            'title'       => 'Tambah Peran',
            'page_title'  => 'Tambah Peran',
            'permissions' => $perms,
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
        $permIds = (array) $this->request->getPost('permissions'); // <- perhatikan key-nya

        $this->syncPermissions($roleId, $permIds);

        return redirect()->to(route_to('admin.roles'))->with('success', 'Role dibuat.');
    }

    // GET /admin/roles/edit/{id}
    public function edit(int $id)
    {
        require_permission('manage_roles');

        $role = $this->roleModel->find($id);
        if (! $role) {
            return redirect()->to(base_url('admin/roles'))
                ->with('error', 'Role tidak ditemukan.');
        }

        // kolom yang benar adalah permission_name
        $permissions = $this->permModel->orderBy('permission_name', 'ASC')->findAll();

        $assignedIds = array_column(
            $this->db->table('role_permissions')
                ->select('permission_id')
                ->where('role_id', $id)
                ->get()->getResultArray(),
            'permission_id'
        );

        return view('admin/roles/edit', [
            'title'       => 'Edit Peran',
            'page_title'  => 'Edit Peran & Izin',
            'role'        => $role,
            'permissions' => $permissions,
            'assignedIds' => $assignedIds,
        ]);
    }

    // POST /admin/roles/update/{id}
    public function update($id)
    {
        require_permission('manage_roles');

        $data = $this->request->getPost(['role_name', 'description']);
        if (! $this->roleModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->roleModel->errors()));
        }

        // ambil 'permissions' (tanpa bracket)
        $this->syncPermissions((int)$id, (array) $this->request->getPost('permissions'));

        // bersihkan cache izin
        session()->remove('auth_permissions');

        return redirect()->to(route_to('admin.roles'))->with('success', 'Role diperbarui.');
    }

    // POST /admin/roles/delete/{id}
    public function delete($id)
    {
        require_permission('manage_roles');

        if ((int) $id === 1) {
            return redirect()->back()->with('error', 'Role ini tidak boleh dihapus.');
        }

        $this->rpModel->where('role_id', $id)->delete();
        $this->roleModel->delete($id);

        return redirect()->to(route_to('admin.roles'))->with('success', 'Role dihapus.');
    }

    // GET /admin/roles/permissions/{id} → alias edit
    public function permissions($id)
    {
        return $this->edit((int)$id);
    }

    // POST /admin/roles/assign-permissions/{id}
    public function assignPermissions(int $id)
    {
        require_permission('manage_roles');

        $role = $this->roleModel->find($id);
        if (! $role) {
            return redirect()->to(route_to('admin.roles'))->with('error', 'Role tidak ditemukan.');
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
            ->with('success', 'Izin berhasil disimpan.');
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
