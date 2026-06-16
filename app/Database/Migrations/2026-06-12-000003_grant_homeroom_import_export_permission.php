<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class GrantHomeroomImportExportPermission extends Migration
{
    public function up(): void
    {
        $permission = $this->db->table('permissions')
            ->select('id')
            ->where('permission_name', 'import_export_data')
            ->get()
            ->getRowArray();

        $permissionId = (int) ($permission['id'] ?? 0);
        if ($permissionId <= 0) {
            return;
        }

        $exists = $this->db->table('role_permissions')
            ->where('role_id', 4)
            ->where('permission_id', $permissionId)
            ->countAllResults();

        if ($exists === 0) {
            $this->db->table('role_permissions')->insert([
                'role_id' => 4,
                'permission_id' => $permissionId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        $permission = $this->db->table('permissions')
            ->select('id')
            ->where('permission_name', 'import_export_data')
            ->get()
            ->getRowArray();

        $permissionId = (int) ($permission['id'] ?? 0);
        if ($permissionId > 0) {
            $this->db->table('role_permissions')
                ->where('role_id', 4)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }
}
