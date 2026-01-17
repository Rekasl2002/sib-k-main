<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000010_create_session_participants_table.php
 * 
 * Session Participants Migration
 * Tabel untuk menyimpan peserta sesi konseling kelompok atau klasikal
 * Many-to-Many relationship antara sessions dan students
 * 
 * @package    SIB-K
 * @subpackage Database/Migrations
 * @category   Counseling
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSessionParticipantsTable extends Migration
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
            'session_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'FK ke counseling_sessions',
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'FK ke students',
            ],
            'attendance_status' => [
                'type'       => 'ENUM',
                'constraint' => ['Hadir', 'Tidak Hadir', 'Izin', 'Sakit'],
                'default'    => 'Hadir',
                'null'       => false,
                'comment'    => 'Status kehadiran peserta',
            ],
            'participation_note' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Catatan partisipasi siswa dalam sesi',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Status aktif peserta (untuk handle siswa yang keluar dari sesi)',
            ],
            'joined_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu siswa bergabung ke sesi',
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
        $this->forge->addForeignKey('session_id', 'counseling_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');

        // Unique constraint: satu siswa hanya bisa sekali dalam satu sesi
        $this->forge->addUniqueKey(['session_id', 'student_id']);

        // Add indexes for better query performance
        $this->forge->addKey('session_id');
        $this->forge->addKey('student_id');
        $this->forge->addKey('attendance_status');

        $this->forge->createTable('session_participants');
    }

    public function down()
    {
        $this->forge->dropTable('session_participants');
    }
}
