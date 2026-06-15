<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 0 - Permission "view_staff_info".
 *
 * Mengaktifkan menu "Info Guru" (kontak Guru BK & Wali Kelas) yang sebelumnya
 * digerbang permission yang belum ada sehingga tidak pernah muncul. Permission
 * diberikan ke Siswa & Orang Tua (kebutuhan inti dari wawancara), serta ke
 * Koordinator BK, Guru BK, dan Wali Kelas agar direktori staf konsisten.
 *
 * Mengikuti pola GrantDataManagementPermissions (idempoten).
 */
class GrantViewStaffInfoPermission extends Migration
{
    private array $permissions = [
        'view_staff_info' => 'Lihat info kontak Guru BK dan Wali Kelas',
    ];

    private array $rolePermissions = [
        2 => ['view_staff_info'],
        3 => ['view_staff_info'],
        4 => ['view_staff_info'],
        5 => ['view_staff_info'],
        6 => ['view_staff_info'],
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->permissions as $name => $description) {
            $exists = $this->db->table('permissions')
                ->where('permission_name', $name)
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('permissions')->insert([
                    'permission_name' => $name,
                    'description' => $description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $permissionRows = $this->db->table('permissions')
            ->select('id, permission_name')
            ->whereIn('permission_name', array_keys($this->permissions))
            ->get()
            ->getResultArray();

        $permissionMap = [];
        foreach ($permissionRows as $row) {
            $permissionMap[(string) $row['permission_name']] = (int) $row['id'];
        }

        foreach ($this->rolePermissions as $roleId => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionMap[$permissionName] ?? 0;
                if ($permissionId <= 0) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->countAllResults();

                if ($exists === 0) {
                    $this->db->table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = $this->db->table('permissions')
            ->select('id')
            ->whereIn('permission_name', array_keys($this->permissions))
            ->get()
            ->getResultArray();

        $ids = array_values(array_filter(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $permissionIds)));
        if (! empty($ids)) {
            $this->db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
            $this->db->table('permissions')->whereIn('id', $ids)->delete();
        }
    }
}
