<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicYearsTable extends Migration
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
            'year_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
                'unique'     => true,
                'comment'    => 'Format: 2024/2025',
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Hanya satu tahun ajaran yang bisa aktif',
            ],
            'semester' => [
                'type'       => 'ENUM',
                'constraint' => ['Ganjil', 'Genap'],
                'default'    => 'Ganjil',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('academic_years');

        // Insert default academic year
        $data = [
            'year_name'  => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date'   => '2025-06-30',
            'is_active'  => 1,
            'semester'   => 'Ganjil',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('academic_years')->insert($data);
    }

    public function down()
    {
        $this->forge->dropTable('academic_years');
    }
}
