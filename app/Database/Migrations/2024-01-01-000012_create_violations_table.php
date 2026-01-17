<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000012_create_violations_table.php
 * 
 * Migration: Create Violations Table
 * Tabel untuk menyimpan data pelanggaran siswa
 * 
 * @package    SIB-K
 * @subpackage Migrations
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViolationsTable extends Migration
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
                'comment'    => 'Foreign key ke tabel students',
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Foreign key ke tabel violation_categories',
            ],
            'violation_date' => [
                'type'    => 'DATE',
                'comment' => 'Tanggal terjadinya pelanggaran',
            ],
            'violation_time' => [
                'type'    => 'TIME',
                'null'    => true,
                'comment' => 'Waktu terjadinya pelanggaran',
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'comment'    => 'Lokasi terjadinya pelanggaran',
            ],
            'description' => [
                'type'    => 'TEXT',
                'comment' => 'Deskripsi detail pelanggaran',
            ],
            'evidence' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Bukti pelanggaran (foto/dokumen path - JSON array)',
            ],
            'reported_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'User ID yang melaporkan (guru/staff)',
            ],
            'handled_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Guru BK yang menangani',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Dilaporkan', 'Dalam Proses', 'Selesai', 'Dibatalkan'],
                'default'    => 'Dilaporkan',
                'comment'    => 'Status penanganan pelanggaran',
            ],
            'resolution_notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Catatan resolusi/penanganan pelanggaran',
            ],
            'resolution_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal selesai penanganan',
            ],
            'parent_notified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Status notifikasi orang tua (0=belum, 1=sudah)',
            ],
            'parent_notified_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu notifikasi orang tua',
            ],
            'is_repeat_offender' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Penanda siswa pelanggar berulang',
            ],
            'notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Catatan tambahan',
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

        // Primary Key
        $this->forge->addKey('id', true);

        // Indexes for performance
        $this->forge->addKey('student_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('violation_date');
        $this->forge->addKey('status');
        $this->forge->addKey('reported_by');
        $this->forge->addKey('handled_by');
        $this->forge->addKey(['student_id', 'violation_date']); // Composite index
        $this->forge->addKey(['status', 'deleted_at']); // For filtering active violations

        // Create table
        $this->forge->createTable('violations');

        // Add Foreign Keys
        $this->db->query('ALTER TABLE violations 
            ADD CONSTRAINT fk_violations_student 
            FOREIGN KEY (student_id) REFERENCES students(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE violations 
            ADD CONSTRAINT fk_violations_category 
            FOREIGN KEY (category_id) REFERENCES violation_categories(id) 
            ON DELETE RESTRICT ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE violations 
            ADD CONSTRAINT fk_violations_reported_by 
            FOREIGN KEY (reported_by) REFERENCES users(id) 
            ON DELETE RESTRICT ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE violations 
            ADD CONSTRAINT fk_violations_handled_by 
            FOREIGN KEY (handled_by) REFERENCES users(id) 
            ON DELETE SET NULL ON UPDATE CASCADE');

        // Add table comment
        $this->db->query("ALTER TABLE violations COMMENT = 'Tabel data pelanggaran siswa'");
    }

    public function down()
    {
        // Drop foreign keys first
        $this->db->query('ALTER TABLE violations DROP FOREIGN KEY fk_violations_student');
        $this->db->query('ALTER TABLE violations DROP FOREIGN KEY fk_violations_category');
        $this->db->query('ALTER TABLE violations DROP FOREIGN KEY fk_violations_reported_by');
        $this->db->query('ALTER TABLE violations DROP FOREIGN KEY fk_violations_handled_by');

        // Drop table
        $this->forge->dropTable('violations');
    }
}
