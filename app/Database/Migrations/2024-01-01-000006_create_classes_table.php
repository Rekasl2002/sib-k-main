<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClassesTable extends Migration
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
            'academic_year_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'class_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
                'comment'    => 'Format: X-IPA-1, XI-IPS-2, XII-IPA-3',
            ],
            'grade_level' => [
                'type'       => 'ENUM',
                'constraint' => ['X', 'XI', 'XII'],
                'null'       => false,
            ],
            'major' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'comment'    => 'IPA, IPS, Bahasa, dll',
            ],
            'homeroom_teacher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'counselor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Guru BK yang bertanggung jawab',
            ],
            'max_students' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 36,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('homeroom_teacher_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('counselor_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('classes');

        // Insert sample classes
        $data = [
            [
                'academic_year_id' => 1,
                'class_name'       => 'X-IPA-1',
                'grade_level'      => 'X',
                'major'            => 'IPA',
                'max_students'     => 36,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'academic_year_id' => 1,
                'class_name'       => 'X-IPA-2',
                'grade_level'      => 'X',
                'major'            => 'IPA',
                'max_students'     => 36,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'academic_year_id' => 1,
                'class_name'       => 'X-IPS-1',
                'grade_level'      => 'X',
                'major'            => 'IPS',
                'max_students'     => 36,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'academic_year_id' => 1,
                'class_name'       => 'XI-IPA-1',
                'grade_level'      => 'XI',
                'major'            => 'IPA',
                'max_students'     => 36,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'academic_year_id' => 1,
                'class_name'       => 'XII-IPA-1',
                'grade_level'      => 'XII',
                'major'            => 'IPA',
                'max_students'     => 36,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('classes')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('classes');
    }
}
