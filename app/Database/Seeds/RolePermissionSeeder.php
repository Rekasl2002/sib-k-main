<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $data = [];

        // Role: Admin (id: 1) - ALL PERMISSIONS
        for ($i = 1; $i <= 27; $i++) {
            $data[] = [
                'role_id'       => 1,
                'permission_id' => $i,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }

        // Role: Koordinator BK (id: 2)
        $koordinatorPermissions = [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 15, 16, 18, 19, 20, 22, 23];
        foreach ($koordinatorPermissions as $perm) {
            $data[] = [
                'role_id'       => 2,
                'permission_id' => $perm,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }

        // Role: Guru BK (id: 3)
        $guruBKPermissions = [4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 24, 25];
        foreach ($guruBKPermissions as $perm) {
            $data[] = [
                'role_id'       => 3,
                'permission_id' => $perm,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }

        // Role: Wali Kelas (id: 4)
        $waliKelasPermissions = [5, 6, 7, 12, 13, 15, 18, 24, 25, 26];
        foreach ($waliKelasPermissions as $perm) {
            $data[] = [
                'role_id'       => 4,
                'permission_id' => $perm,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }

        // Role: Siswa (id: 5)
        $siswaPermissions = [5, 9, 13, 14, 15, 17, 27];
        foreach ($siswaPermissions as $perm) {
            $data[] = [
                'role_id'       => 5,
                'permission_id' => $perm,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }

        // Role: Orang Tua (id: 6)
        $orangTuaPermissions = [5, 7, 12, 13, 15, 17, 24, 25, 27];
        foreach ($orangTuaPermissions as $perm) {
            $data[] = [
                'role_id'       => 6,
                'permission_id' => $perm,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }

        // Truncate table first
        $this->db->table('role_permissions')->emptyTable();

        // Insert batch data
        $this->db->table('role_permissions')->insertBatch($data);

        echo "✓ Role Permissions seeded successfully!\n";
        echo "  - Admin: 27 permissions\n";
        echo "  - Koordinator BK: 19 permissions\n";
        echo "  - Guru BK: 17 permissions\n";
        echo "  - Wali Kelas: 10 permissions\n";
        echo "  - Siswa: 7 permissions\n";
        echo "  - Orang Tua: 9 permissions\n";
    }
}
