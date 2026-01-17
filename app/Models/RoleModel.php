<?php

/**
 * File Path: app/Models/RoleModel.php
 *
 * Role Model
 * Mengelola data roles/peran pengguna dalam sistem (RBAC sederhana).
 *
 * Catatan linting:
 * - Jika hasil query akan diakses sebagai array dengan $row['kolom'],
 *   WAJIB akhiri chain query dengan ->asArray() sebelum first()/find()/findAll().
 * - Return type yang dideklarasikan (mis. ?array) harus benar-benar mengembalikan array|null.
 * 
 * @package    SIB-K
 * @subpackage Models
 * @category   RBAC
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    // ---------------------------------------------------------------------
    // Konfigurasi Model
    // ---------------------------------------------------------------------
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    // Kita konsisten pakai array supaya aman untuk akses indeks
    public    $returnType    = 'array';

    // Soft delete untuk tabel roles biasanya tidak wajib; sesuaikan skema Anda
    protected $useSoftDeletes   = false;

    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'description',
        'role_name',
        'description',
    ];
    

    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // ---------------------------------------------------------------------
    // Validasi sederhana
    // ---------------------------------------------------------------------
    protected $validationRules = [
        // is_unique dengan pengecualian id saat update
        'role_name'   => 'required|min_length[3]|max_length[50]|is_unique[roles.role_name,id,{id}]',
        'description' => 'permit_empty|max_length[500]',
    ];

    protected $validationMessages = [
        'role_name' => [
            'required'   => 'Nama role harus diisi',
            'min_length' => 'Nama role minimal 3 karakter',
            'max_length' => 'Nama role maksimal 50 karakter',
            'is_unique'  => 'Nama role sudah digunakan',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // ---------------------------------------------------------------------
    // Callbacks (disiapkan bila dibutuhkan)
    // ---------------------------------------------------------------------
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // =====================================================================
    // FUNGSI-FUNGSI UTAMA
    // =====================================================================

    /**
     * Ambil 1 role beserta daftar permissions-nya.
     *
     * @param int $roleId
     * @return array|null
     */
    public function getRoleWithPermissions(int $roleId): ?array
    {
        /** @var array<string,mixed>|null $role */
        $role = $this->asArray()->find($roleId);
        if (!$role) {
            return null;
        }

        // Ambil daftar permission via pivot role_permissions
        $db = \Config\Database::connect();
        $permissions = $db->table('role_permissions')
            ->select('permissions.*')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->get()
            ->getResultArray(); // sudah array

        $role['permissions'] = $permissions ?? [];
        return $role;
    }

    /**
     * Daftar seluruh role dengan jumlah user per role.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getRolesWithUserCount(): array
    {
        $db = \Config\Database::connect();

        return $db->table($this->table)
            ->select('roles.*, COUNT(users.id) AS user_count')
            ->join('users', 'users.role_id = roles.id', 'left')
            ->groupBy('roles.id')
            ->orderBy('roles.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Tetapkan ulang permissions untuk sebuah role.
     * Akan menghapus mapping lama lalu memasukkan mapping baru.
     *
     * @param int $roleId
     * @param array<int,int> $permissionIds
     * @return bool
     */
    public function assignPermissions(int $roleId, array $permissionIds): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // Hapus semua mapping lama role -> permission
        $db->table('role_permissions')
            ->where('role_id', $roleId)
            ->delete();

        // Masukkan mapping baru (jika ada)
        if (!empty($permissionIds)) {
            $now  = date('Y-m-d H:i:s');
            $data = [];
            foreach ($permissionIds as $pid) {
                $data[] = [
                    'role_id'       => (int) $roleId,
                    'permission_id' => (int) $pid,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
            if (!empty($data)) {
                $db->table('role_permissions')->insertBatch($data);
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Cek apakah role aman untuk dihapus (tidak dipakai user).
     */
    public function canDelete(int $roleId): bool
    {
        $db = \Config\Database::connect();

        $userCount = $db->table('users')
            ->where('role_id', $roleId)
            ->countAllResults();

        return $userCount === 0;
    }

    /**
     * Cari role berdasarkan nama.
     *
     * @param string $roleName
     * @return array|null
     */
    public function getRoleByName(string $roleName): ?array
    {
        /** @var array<string,mixed>|null $row */
        $row = $this->where('role_name', $roleName)->asArray()->first();
        return $row ?: null;
    }

    // =====================================================================
    // OPSIONAL: UTILITAS LISTING & PENCARIAN
    // =====================================================================

    /**
     * Daftar role dengan pencarian bebas & pagination sederhana.
     *
     * @param string|null $search
     * @param int $limit
     * @param int $offset
     * @return array<int,array<string,mixed>>
     */
    public function listRoles(?string $search = null, int $limit = 50, int $offset = 0): array
    {
        $builder = $this->select('roles.*');

        if ($search !== null && trim($search) !== '') {
            $s = trim($search);
            $builder->groupStart()
                ->like('roles.role_name', $s)
                ->orLike('roles.description', $s)
                ->groupEnd();
        }

        return $builder
            ->orderBy('roles.id', 'ASC')
            ->limit($limit, $offset)
            ->asArray()
            ->findAll();
    }

    /**
     * Hapus role beserta mapping permissions-nya (opsional).
     * NOTE: Pastikan sudah cek canDelete() sebelum memanggil ini jika perlu.
     */
    public function deleteRole(int $roleId, bool $purgeMappings = true): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        if ($purgeMappings) {
            $db->table('role_permissions')
                ->where('role_id', $roleId)
                ->delete();
        }

        $ok = (bool) $this->delete($roleId);

        $db->transComplete();
        return $ok && $db->transStatus();
    }
}
