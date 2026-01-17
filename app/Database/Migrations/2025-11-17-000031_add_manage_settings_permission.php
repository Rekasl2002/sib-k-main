<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddManageSettingsPermission extends Migration
{
    public function up()
    {
        // Insert permission if not exists
        $perm = $this->db->table('permissions')->where('permission_name','manage_settings')->get()->getRowArray();
        if (!$perm) {
            $this->db->table('permissions')->insert([
                'permission_name' => 'manage_settings',
                'description'     => 'Kelola pengaturan aplikasi',
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        // Attach to Admin role (assume role_id = 1 = Admin seperti di dump)
        $adminRole = $this->db->table('roles')->where('id', 1)->get()->getRowArray();
        $permId    = $this->db->table('permissions')->where('permission_name','manage_settings')->get()->getRow('id');
        if ($adminRole && $permId) {
            $exists = $this->db->table('role_permissions')
                ->where(['role_id'=>1,'permission_id'=>$permId])->get()->getRowArray();
            if (!$exists) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => 1,
                    'permission_id' => $permId,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        // optional: hapus role_permissions & permission ini
    }
}
