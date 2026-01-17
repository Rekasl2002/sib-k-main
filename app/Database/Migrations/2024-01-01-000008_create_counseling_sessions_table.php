<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000008_create_counseling_sessions_table.php
 * 
 * Counseling Sessions Migration
 * Tabel untuk menyimpan data sesi konseling (individu, kelompok, klasikal)
 * 
 * @package    SIB-K
 * @subpackage Database/Migrations
 * @category   Counseling
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCounselingSessionsTable extends Migration
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
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Untuk sesi individu, null untuk sesi kelompok/klasikal',
            ],
            'counselor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'Guru BK yang menangani sesi',
            ],
            'class_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Untuk sesi klasikal (per kelas)',
            ],
            'session_type' => [
                'type'       => 'ENUM',
                'constraint' => ['Individu', 'Kelompok', 'Klasikal'],
                'null'       => false,
                'default'    => 'Individu',
                'comment'    => 'Jenis sesi konseling',
            ],
            'session_date' => [
                'type'    => 'DATE',
                'null'    => false,
                'comment' => 'Tanggal pelaksanaan sesi',
            ],
            'session_time' => [
                'type'    => 'TIME',
                'null'    => true,
                'comment' => 'Waktu pelaksanaan sesi',
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => 'Lokasi sesi (Ruang BK, Kelas, dll)',
            ],
            'topic' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
                'comment'    => 'Topik/judul sesi konseling',
            ],
            'problem_description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Deskripsi masalah atau topik yang dibahas',
            ],
            'session_summary' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Ringkasan hasil sesi konseling',
            ],
            'follow_up_plan' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Rencana tindak lanjut setelah sesi',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Dijadwalkan', 'Selesai', 'Dibatalkan'],
                'default'    => 'Dijadwalkan',
                'null'       => false,
                'comment'    => 'Status pelaksanaan sesi',
            ],
            'is_confidential' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Apakah sesi ini bersifat rahasia (1 = ya, 0 = tidak)',
            ],
            'duration_minutes' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Durasi sesi dalam menit',
            ],
            'cancellation_reason' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Alasan pembatalan jika status = Dibatalkan',
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
        $this->forge->addForeignKey('student_id', 'students', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('counselor_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('class_id', 'classes', 'id', 'SET NULL', 'CASCADE');

        // Add indexes for better query performance
        $this->forge->addKey('session_date');
        $this->forge->addKey('counselor_id');
        $this->forge->addKey('student_id');
        $this->forge->addKey('session_type');
        $this->forge->addKey('status');

        $this->forge->createTable('counseling_sessions');
    }

    public function down()
    {
        $this->forge->dropTable('counseling_sessions');
    }
}
