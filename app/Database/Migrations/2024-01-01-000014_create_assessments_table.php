<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000014_create_assessments_table.php
 * 
 * Migration: Create Assessments Table
 * Tabel untuk menyimpan master data asesmen psikologi/minat bakat
 * 
 * @package    SIB-K
 * @subpackage Migrations
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssessmentsTable extends Migration
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
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'comment'    => 'Judul/nama asesmen',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Deskripsi dan tujuan asesmen',
            ],
            'assessment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Jenis asesmen (Psikologi, Minat Bakat, Kepribadian, Career, dll)',
            ],
            'target_audience' => [
                'type'       => 'ENUM',
                'constraint' => ['Individual', 'Class', 'Grade', 'All'],
                'default'    => 'Individual',
                'comment'    => 'Target peserta asesmen',
            ],
            'target_class_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID kelas target (jika target_audience = Class)',
            ],
            'target_grade' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'comment'    => 'Tingkat kelas target (X/XI/XII) jika target_audience = Grade',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'User ID guru BK yang membuat asesmen',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Status aktif asesmen (1=aktif, 0=nonaktif)',
            ],
            'is_published' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Status publikasi (1=sudah dipublikasi, 0=draft)',
            ],
            'start_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal mulai asesmen dapat diakses',
            ],
            'end_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal akhir asesmen dapat diakses',
            ],
            'duration_minutes' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Durasi pengerjaan dalam menit (null = unlimited)',
            ],
            'passing_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'comment'    => 'Nilai minimum untuk lulus (%)',
            ],
            'max_attempts' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'comment'    => 'Maksimal percobaan pengerjaan',
            ],
            'show_result_immediately' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Tampilkan hasil langsung setelah selesai (1=ya, 0=tidak)',
            ],
            'allow_review' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Izinkan siswa review jawaban (1=ya, 0=tidak)',
            ],
            'instructions' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Instruksi pengerjaan asesmen',
            ],
            'total_questions' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Total jumlah pertanyaan',
            ],
            'total_participants' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Total peserta yang mengerjakan',
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
        $this->forge->addKey('assessment_type');
        $this->forge->addKey('target_audience');
        $this->forge->addKey('created_by');
        $this->forge->addKey('is_active');
        $this->forge->addKey('is_published');
        $this->forge->addKey(['start_date', 'end_date']); // Composite index
        $this->forge->addKey(['is_active', 'is_published', 'deleted_at']); // For active assessments query

        // Create table
        $this->forge->createTable('assessments');

        // Add Foreign Keys
        $this->db->query('ALTER TABLE assessments 
            ADD CONSTRAINT fk_assessments_target_class 
            FOREIGN KEY (target_class_id) REFERENCES classes(id) 
            ON DELETE SET NULL ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE assessments 
            ADD CONSTRAINT fk_assessments_created_by 
            FOREIGN KEY (created_by) REFERENCES users(id) 
            ON DELETE RESTRICT ON UPDATE CASCADE');

        // Add table comment
        $this->db->query("ALTER TABLE assessments COMMENT = 'Tabel master asesmen psikologi dan minat bakat'");
    }

    public function down()
    {
        // Drop foreign keys first
        $this->db->query('ALTER TABLE assessments DROP FOREIGN KEY fk_assessments_target_class');
        $this->db->query('ALTER TABLE assessments DROP FOREIGN KEY fk_assessments_created_by');

        // Drop table
        $this->forge->dropTable('assessments');
    }
}
