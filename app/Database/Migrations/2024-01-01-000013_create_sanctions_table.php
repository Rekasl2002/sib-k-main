<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000013_create_sanctions_table.php
 * 
 * Migration: Create Sanctions Table
 * Tabel untuk menyimpan sanksi yang diberikan untuk pelanggaran
 * 
 * @package    SIB-K
 * @subpackage Migrations
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSanctionsTable extends Migration
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
            'violation_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Foreign key ke tabel violations',
            ],
            'sanction_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => 'Jenis sanksi (Teguran Lisan, Teguran Tertulis, Skorsing, dll)',
            ],
            'sanction_date' => [
                'type'    => 'DATE',
                'comment' => 'Tanggal pemberian sanksi',
            ],
            'start_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal mulai pelaksanaan sanksi',
            ],
            'end_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal selesai sanksi (untuk skorsing, pembinaan berkala, dll)',
            ],
            'duration_days' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Durasi sanksi dalam hari',
            ],
            'description' => [
                'type'    => 'TEXT',
                'comment' => 'Deskripsi detail sanksi yang diberikan',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Dijadwalkan', 'Sedang Berjalan', 'Selesai', 'Dibatalkan'],
                'default'    => 'Dijadwalkan',
                'comment'    => 'Status pelaksanaan sanksi',
            ],
            'completed_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal selesai sanksi',
            ],
            'completion_notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Catatan penyelesaian sanksi',
            ],
            'assigned_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'User ID yang memberikan sanksi (Guru BK/Koordinator)',
            ],
            'verified_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'User ID yang memverifikasi sanksi (Koordinator/Kepala Sekolah)',
            ],
            'verified_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu verifikasi sanksi',
            ],
            'parent_acknowledged' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Status acknowledgement orang tua (0=belum, 1=sudah)',
            ],
            'parent_acknowledged_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu acknowledgement orang tua',
            ],
            'documents' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Dokumen terkait sanksi (surat, berita acara - JSON array)',
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
        $this->forge->addKey('violation_id');
        $this->forge->addKey('sanction_type');
        $this->forge->addKey('sanction_date');
        $this->forge->addKey('status');
        $this->forge->addKey('assigned_by');
        $this->forge->addKey(['status', 'sanction_date']); // Composite index for filtering
        $this->forge->addKey(['violation_id', 'status']); // For violation sanctions tracking

        // Create table
        $this->forge->createTable('sanctions');

        // Add Foreign Keys
        $this->db->query('ALTER TABLE sanctions 
            ADD CONSTRAINT fk_sanctions_violation 
            FOREIGN KEY (violation_id) REFERENCES violations(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE sanctions 
            ADD CONSTRAINT fk_sanctions_assigned_by 
            FOREIGN KEY (assigned_by) REFERENCES users(id) 
            ON DELETE RESTRICT ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE sanctions 
            ADD CONSTRAINT fk_sanctions_verified_by 
            FOREIGN KEY (verified_by) REFERENCES users(id) 
            ON DELETE SET NULL ON UPDATE CASCADE');

        // Add table comment
        $this->db->query("ALTER TABLE sanctions COMMENT = 'Tabel sanksi untuk pelanggaran siswa'");
    }

    public function down()
    {
        // Drop foreign keys first
        $this->db->query('ALTER TABLE sanctions DROP FOREIGN KEY fk_sanctions_violation');
        $this->db->query('ALTER TABLE sanctions DROP FOREIGN KEY fk_sanctions_assigned_by');
        $this->db->query('ALTER TABLE sanctions DROP FOREIGN KEY fk_sanctions_verified_by');

        // Drop table
        $this->forge->dropTable('sanctions');
    }
}
