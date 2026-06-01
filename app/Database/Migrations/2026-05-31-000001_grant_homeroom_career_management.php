<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class GrantHomeroomCareerManagement extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('permissions') || !$this->db->tableExists('role_permissions')) {
            return;
        }

        $permission = $this->db->table('permissions')
            ->select('id')
            ->where('permission_name', 'manage_career_info')
            ->get()
            ->getRowArray();

        $permissionId = (int)($permission['id'] ?? 0);
        if ($permissionId <= 0) {
            return;
        }

        $exists = $this->db->table('role_permissions')
            ->where('role_id', 4)
            ->where('permission_id', $permissionId)
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('role_permissions')->insert([
            'role_id'       => 4,
            'permission_id' => $permissionId,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function down()
    {
        if (!$this->db->tableExists('permissions') || !$this->db->tableExists('role_permissions')) {
            return;
        }

        $permission = $this->db->table('permissions')
            ->select('id')
            ->where('permission_name', 'manage_career_info')
            ->get()
            ->getRowArray();

        $permissionId = (int)($permission['id'] ?? 0);
        if ($permissionId <= 0) {
            return;
        }

        $this->db->table('role_permissions')
            ->where('role_id', 4)
            ->where('permission_id', $permissionId)
            ->delete();
    }
}
