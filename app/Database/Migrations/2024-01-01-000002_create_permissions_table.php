<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionsTable extends Migration
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
            'permission_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
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
        $this->forge->createTable('permissions');

        // Insert default permissions
        $data = [
            ['permission_name' => 'manage_users', 'description' => 'Kelola pengguna sistem', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'manage_roles', 'description' => 'Kelola peran dan izin', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'manage_academic_data', 'description' => 'Kelola data akademik (kelas, tahun ajaran)', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'manage_counseling_sessions', 'description' => 'Kelola sesi konseling', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'view_counseling_sessions', 'description' => 'Lihat sesi konseling', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'manage_violations', 'description' => 'Kelola pelanggaran siswa', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'view_violations', 'description' => 'Lihat pelanggaran siswa', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'manage_assessments', 'description' => 'Kelola asesmen', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'take_assessments', 'description' => 'Mengerjakan asesmen', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'view_student_portfolio', 'description' => 'Lihat portofolio siswa', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'generate_reports', 'description' => 'Generate laporan', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'view_reports', 'description' => 'Lihat laporan', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'send_messages', 'description' => 'Kirim pesan internal', 'created_at' => date('Y-m-d H:i:s')],
            ['permission_name' => 'schedule_counseling', 'description' => 'Jadwalkan konseling', 'created_at' => date('Y-m-d H:i:s')],
        ];

        $this->db->table('permissions')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('permissions');
    }
}
