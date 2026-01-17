<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolePermissionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'permission_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_permissions');

        // Assign permissions to roles
        // Role: Admin (id: 1) - All permissions
        $adminPermissions = [];
        for ($i = 1; $i <= 14; $i++) {
            $adminPermissions[] = [
                'role_id' => 1,
                'permission_id' => $i,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // Role: Koordinator BK (id: 2)
        $koordinatorPermissions = [
            ['role_id' => 2, 'permission_id' => 1, 'created_at' => date('Y-m-d H:i:s')], // manage_users
            ['role_id' => 2, 'permission_id' => 3, 'created_at' => date('Y-m-d H:i:s')], // manage_academic_data
            ['role_id' => 2, 'permission_id' => 4, 'created_at' => date('Y-m-d H:i:s')], // manage_counseling_sessions
            ['role_id' => 2, 'permission_id' => 5, 'created_at' => date('Y-m-d H:i:s')], // view_counseling_sessions
            ['role_id' => 2, 'permission_id' => 6, 'created_at' => date('Y-m-d H:i:s')], // manage_violations
            ['role_id' => 2, 'permission_id' => 7, 'created_at' => date('Y-m-d H:i:s')], // view_violations
            ['role_id' => 2, 'permission_id' => 8, 'created_at' => date('Y-m-d H:i:s')], // manage_assessments
            ['role_id' => 2, 'permission_id' => 10, 'created_at' => date('Y-m-d H:i:s')], // view_student_portfolio
            ['role_id' => 2, 'permission_id' => 11, 'created_at' => date('Y-m-d H:i:s')], // generate_reports
            ['role_id' => 2, 'permission_id' => 12, 'created_at' => date('Y-m-d H:i:s')], // view_reports
            ['role_id' => 2, 'permission_id' => 13, 'created_at' => date('Y-m-d H:i:s')], // send_messages
        ];

        // Role: Guru BK (id: 3)
        $guruBKPermissions = [
            ['role_id' => 3, 'permission_id' => 4, 'created_at' => date('Y-m-d H:i:s')], // manage_counseling_sessions
            ['role_id' => 3, 'permission_id' => 5, 'created_at' => date('Y-m-d H:i:s')], // view_counseling_sessions
            ['role_id' => 3, 'permission_id' => 6, 'created_at' => date('Y-m-d H:i:s')], // manage_violations
            ['role_id' => 3, 'permission_id' => 7, 'created_at' => date('Y-m-d H:i:s')], // view_violations
            ['role_id' => 3, 'permission_id' => 8, 'created_at' => date('Y-m-d H:i:s')], // manage_assessments
            ['role_id' => 3, 'permission_id' => 10, 'created_at' => date('Y-m-d H:i:s')], // view_student_portfolio
            ['role_id' => 3, 'permission_id' => 11, 'created_at' => date('Y-m-d H:i:s')], // generate_reports
            ['role_id' => 3, 'permission_id' => 12, 'created_at' => date('Y-m-d H:i:s')], // view_reports
            ['role_id' => 3, 'permission_id' => 13, 'created_at' => date('Y-m-d H:i:s')], // send_messages
        ];

        // Role: Wali Kelas (id: 4)
        $waliKelasPermissions = [
            ['role_id' => 4, 'permission_id' => 6, 'created_at' => date('Y-m-d H:i:s')], // manage_violations
            ['role_id' => 4, 'permission_id' => 7, 'created_at' => date('Y-m-d H:i:s')], // view_violations
            ['role_id' => 4, 'permission_id' => 12, 'created_at' => date('Y-m-d H:i:s')], // view_reports
            ['role_id' => 4, 'permission_id' => 13, 'created_at' => date('Y-m-d H:i:s')], // send_messages
        ];

        // Role: Siswa (id: 5)
        $siswaPermissions = [
            ['role_id' => 5, 'permission_id' => 9, 'created_at' => date('Y-m-d H:i:s')], // take_assessments
            ['role_id' => 5, 'permission_id' => 14, 'created_at' => date('Y-m-d H:i:s')], // schedule_counseling
            ['role_id' => 5, 'permission_id' => 13, 'created_at' => date('Y-m-d H:i:s')], // send_messages
        ];

        // Role: Orang Tua (id: 6)
        $orangTuaPermissions = [
            ['role_id' => 6, 'permission_id' => 7, 'created_at' => date('Y-m-d H:i:s')], // view_violations
            ['role_id' => 6, 'permission_id' => 12, 'created_at' => date('Y-m-d H:i:s')], // view_reports
            ['role_id' => 6, 'permission_id' => 13, 'created_at' => date('Y-m-d H:i:s')], // send_messages
        ];

        // Insert all permissions
        $allPermissions = array_merge(
            $adminPermissions,
            $koordinatorPermissions,
            $guruBKPermissions,
            $waliKelasPermissions,
            $siswaPermissions,
            $orangTuaPermissions
        );

        $this->db->table('role_permissions')->insertBatch($allPermissions);
    }

    public function down()
    {
        $this->forge->dropTable('role_permissions');
    }
}
