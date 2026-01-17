<?php
declare(strict_types=1);

/**
 * File Path: app/Models/UserModel.php
 *
 * User Model
 * - Menyimpan user + relasi role + permission
 * - Password dikirim via field "password" (plaintext) lalu di-hash ke "password_hash" via callback
 * - Soft delete enabled
 *
 * Catatan penting:
 * - Field "password" TIDAK disimpan ke DB. Selalu dihapus dari payload pada callback.
 */

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'role_id',
        'username',
        'email',
        'password',        // plaintext dari form/import (akan DIHAPUS di callback)
        'password_hash',   // yang disimpan ke DB
        'full_name',
        'phone',
        'profile_photo',
        'is_active',
        'last_login',
    ];

    // Timestamps & soft deletes
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Kami mematikan validasi di level Model agar tidak bentrok dengan
     * App\Validation\UserValidation di Controller dan mencegah error placeholder {id}.
     */
    protected $skipValidation       = true;
    protected $cleanValidationRules = true;

    // Simpan rules di sini (tidak aktif karena $skipValidation = true)
    protected $validationRules = [
        'role_id'   => 'required|integer|is_not_unique[roles.id]',
        'username'  => 'required|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]|regex_match[/^[A-Za-z0-9._-]+$/]',
        'email'     => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        'password'  => 'permit_empty|min_length[6]|max_length[255]',
        'full_name' => 'required|min_length[3]|max_length[255]',
        'phone'     => 'permit_empty|regex_match[/^[0-9]{10,20}$/]',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'username' => [
            'required'    => 'Username harus diisi',
            'min_length'  => 'Username minimal 3 karakter',
            'is_unique'   => 'Username sudah digunakan',
            'regex_match' => 'Username hanya boleh huruf, angka, titik, underscore, atau minus',
        ],
        'email' => [
            'required'    => 'Email harus diisi',
            'valid_email' => 'Email tidak valid',
            'is_unique'   => 'Email sudah digunakan',
        ],
        'password' => [
            'min_length' => 'Password minimal 6 karakter',
        ],
        'full_name' => [
            'required'   => 'Nama lengkap harus diisi',
            'min_length' => 'Nama lengkap minimal 3 karakter',
        ],
        'phone' => [
            'regex_match' => 'Nomor telepon hanya boleh 10–20 digit angka',
        ],
    ];

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword', 'normalizeFields'];
    protected $beforeUpdate   = ['hashPassword', 'normalizeFields'];

    /**
     * Normalisasi field umum (rapihkan data).
     * - email lower-case
     * - username trim
     * - full_name trim
     */
    protected function normalizeFields(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return $data;
        }

        $payload =& $data['data'];

        if (isset($payload['email'])) {
            $payload['email'] = strtolower(trim((string) $payload['email']));
        }
        if (isset($payload['username'])) {
            $payload['username'] = trim((string) $payload['username']);
        }
        if (isset($payload['full_name'])) {
            $payload['full_name'] = trim((string) $payload['full_name']);
        }
        if (isset($payload['phone'])) {
            $payload['phone'] = trim((string) $payload['phone']);
        }

        return $data;
    }

    /**
     * Hash password (dipanggil saat insert/update)
     * - Jika field 'password' dikirim dan tidak kosong => set password_hash
     * - APAPUN kondisinya, jika key 'password' ada => selalu di-unset (agar tidak pernah masuk SQL)
     */
    protected function hashPassword(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return $data;
        }

        $payload =& $data['data'];

        if (array_key_exists('password', $payload)) {
            $plain = trim((string) $payload['password']);

            if ($plain !== '') {
                // PASSWORD_DEFAULT lebih fleksibel untuk masa depan, tapi BCRYPT juga aman.
                $payload['password_hash'] = password_hash($plain, PASSWORD_BCRYPT);
            }

            // Jangan pernah simpan plaintext, meski kosong
            unset($payload['password']);
        }

        return $data;
    }

    /**
     * Ambil user beserta info role.
     */
    public function getUserWithRole(int $userId): ?array
    {
        return $this->select('users.*, roles.role_name, roles.description as role_description')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $userId)
            ->where('users.deleted_at', null)
            ->first();
    }

    /**
     * Ambil user + role + daftar permissions.
     */
    public function getUserWithPermissions(int $userId): ?array
    {
        $user = $this->getUserWithRole($userId);
        if (! $user) {
            return null;
        }

        $db = \Config\Database::connect();
        $permissions = $db->table('role_permissions')
            ->select('permissions.permission_name')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $user['role_id'])
            ->get()
            ->getResultArray();

        $user['permissions'] = array_column($permissions, 'permission_name');
        return $user;
    }

    /**
     * Autentikasi username/email + password.
     */
    public function authenticate(string $usernameOrEmail, string $password)
    {
        $user = $this->groupStart()
                ->where('username', $usernameOrEmail)
                ->orWhere('email', strtolower($usernameOrEmail))
            ->groupEnd()
            ->where('deleted_at', null)
            ->first();

        if (! $user) {
            return false;
        }

        if (empty($user['password_hash']) || ! password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        if ((int) ($user['is_active'] ?? 0) !== 1) {
            return false;
        }

        // Optional: rehash jika algoritma berubah (future-proof)
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_BCRYPT)) {
            $this->update((int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            ]);
        }

        // Update last login
        $this->update((int) $user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        return $this->getUserWithPermissions((int) $user['id']);
    }

    /**
     * Daftar semua user + nama role.
     */
    public function getAllWithRole(): array
    {
        return $this->select('users.*, roles.role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.deleted_at', null)
            ->orderBy('users.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Ambil user berdasarkan role_id.
     */
    public function getUsersByRole(int $roleId): array
    {
        return $this->where('role_id', $roleId)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    /**
     * Ambil user berdasarkan nama role.
     */
    public function getUsersByRoleName(string $roleName): array
    {
        return $this->select('users.*')
            ->join('roles', 'roles.id = users.role_id')
            ->where('roles.role_name', $roleName)
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
    }

    /**
     * Cek apakah user punya permission tertentu.
     */
    public function hasPermission(int $userId, string $permissionName): bool
    {
        $db = \Config\Database::connect();

        $count = $db->table('users')
            ->join('role_permissions', 'role_permissions.role_id = users.role_id')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('users.id', $userId)
            ->where('users.deleted_at', null)
            ->where('permissions.permission_name', $permissionName)
            ->countAllResults();

        return $count > 0;
    }

    /**
     * Update profil (tanpa mengubah role & password hash).
     */
    public function updateProfile(int $userId, array $data): bool
    {
        unset($data['password_hash'], $data['role_id']);

        // Jika ada "password" di payload profile, biar callback yang handle hashing,
        // tapi jangan izinkan password kosong nyangkut.
        return $this->update($userId, $data);
    }

    /**
     * Ganti password secara eksplisit.
     */
    public function changePassword(int $userId, string $newPassword): bool
    {
        return $this->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);
    }

    /**
     * Aktif/nonaktifkan user.
     */
    public function toggleActive(int $userId, bool $status = true): bool
    {
        return $this->update($userId, ['is_active' => $status ? 1 : 0]);
    }

    /**
     * Statistik user per role.
     */
    public function getUserStatistics(): array
    {
        $db = \Config\Database::connect();

        return $db->table('users')
            ->select('roles.role_name, COUNT(users.id) as total')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.deleted_at', null)
            ->groupBy('roles.id, roles.role_name')
            ->get()
            ->getResultArray();
    }
}
