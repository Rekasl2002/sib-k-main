<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentsTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'unique'     => true,
            ],
            'class_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'nisn' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => false,
                'unique'     => true,
                'comment'    => 'Nomor Induk Siswa Nasional',
            ],
            'nis' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => false,
                'unique'     => true,
                'comment'    => 'Nomor Induk Siswa',
            ],
            'gender' => [
                'type'       => 'ENUM',
                'constraint' => ['L', 'P'],
                'null'       => false,
            ],
            'birth_place' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'birth_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'religion' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Link ke tabel users dengan role Orang Tua',
            ],
            'admission_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal masuk sekolah',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Aktif', 'Alumni', 'Pindah', 'Keluar'],
                'default'    => 'Aktif',
            ],
            'total_violation_points' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Total poin pelanggaran',
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('class_id', 'classes', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('parent_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('students');
    }

    public function down()
    {
        $this->forge->dropTable('students');
    }
}
