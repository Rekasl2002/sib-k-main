<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTable extends Migration
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
            'role_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
                'unique'     => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('roles');

        // Insert default roles
        $data = [
            [
                'role_name'   => 'Admin',
                'description' => 'Administrator sistem dengan akses penuh',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'role_name'   => 'Koordinator BK',
                'description' => 'Koordinator Bimbingan Konseling',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'role_name'   => 'Guru BK',
                'description' => 'Guru Bimbingan Konseling',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'role_name'   => 'Wali Kelas',
                'description' => 'Wali Kelas',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'role_name'   => 'Siswa',
                'description' => 'Siswa',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'role_name'   => 'Orang Tua',
                'description' => 'Orang Tua/Wali Siswa',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('roles')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('roles');
    }
}
