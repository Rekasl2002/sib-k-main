<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000017_create_assessment_results_table.php
 * 
 * Migration: Create Assessment Results Table
 * Tabel untuk menyimpan hasil asesmen siswa
 * 
 * @package    SIB-K
 * @subpackage Migrations
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssessmentResultsTable extends Migration
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
            'assessment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Foreign key ke tabel assessments',
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Foreign key ke tabel students',
            ],
            'attempt_number' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'comment'    => 'Percobaan ke berapa',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['In Progress', 'Completed', 'Graded', 'Expired'],
                'default'    => 'In Progress',
                'comment'    => 'Status pengerjaan/penilaian',
            ],
            'total_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '7,2',
                'null'       => true,
                'default'    => 0.00,
                'comment'    => 'Total nilai yang diperoleh',
            ],
            'max_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '7,2',
                'null'       => true,
                'comment'    => 'Nilai maksimal asesmen',
            ],
            'percentage' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'comment'    => 'Persentase nilai (0-100)',
            ],
            'is_passed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'comment'    => 'Lulus atau tidak (1=lulus, 0=tidak lulus, null=belum dinilai)',
            ],
            'questions_answered' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Jumlah pertanyaan yang sudah dijawab',
            ],
            'total_questions' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Total pertanyaan dalam asesmen',
            ],
            'correct_answers' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Jumlah jawaban benar (untuk MC/True-False)',
            ],
            'started_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu mulai mengerjakan',
            ],
            'completed_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu selesai mengerjakan',
            ],
            'graded_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu selesai dinilai (untuk essay)',
            ],
            'time_spent_seconds' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Total waktu pengerjaan (detik)',
            ],
            'interpretation' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Interpretasi/analisis hasil asesmen',
            ],
            'dimension_scores' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment'    => 'Nilai per dimensi (JSON) untuk asesmen psikologi',
            ],
            'recommendations' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Rekomendasi berdasarkan hasil asesmen',
            ],
            'reviewed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'User ID guru BK yang mereview hasil',
            ],
            'reviewed_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu review oleh guru BK',
            ],
            'counselor_notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Catatan guru BK tentang hasil asesmen',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
                'comment'    => 'IP address saat mengerjakan',
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Browser/device info',
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
        $this->forge->addKey('assessment_id');
        $this->forge->addKey('student_id');
        $this->forge->addKey('status');
        $this->forge->addKey('reviewed_by');
        $this->forge->addKey(['assessment_id', 'student_id']); // Composite index for student results
        $this->forge->addKey(['assessment_id', 'status']); // For assessment statistics
        $this->forge->addKey(['student_id', 'completed_at']); // For student history
        $this->forge->addKey(['assessment_id', 'student_id', 'attempt_number']); // Unique attempt tracking

        // Create table
        $this->forge->createTable('assessment_results');

        // Add Foreign Keys
        $this->db->query('ALTER TABLE assessment_results 
            ADD CONSTRAINT fk_results_assessment 
            FOREIGN KEY (assessment_id) REFERENCES assessments(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE assessment_results 
            ADD CONSTRAINT fk_results_student 
            FOREIGN KEY (student_id) REFERENCES students(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE assessment_results 
            ADD CONSTRAINT fk_results_reviewed_by 
            FOREIGN KEY (reviewed_by) REFERENCES users(id) 
            ON DELETE SET NULL ON UPDATE CASCADE');

        // Add unique constraint - one result per student per assessment per attempt
        $this->db->query('ALTER TABLE assessment_results 
            ADD UNIQUE KEY unique_student_assessment_attempt (student_id, assessment_id, attempt_number)');

        // Add table comment
        $this->db->query("ALTER TABLE assessment_results COMMENT = 'Tabel hasil asesmen siswa'");
    }

    public function down()
    {
        // Drop foreign keys first
        $this->db->query('ALTER TABLE assessment_results DROP FOREIGN KEY fk_results_assessment');
        $this->db->query('ALTER TABLE assessment_results DROP FOREIGN KEY fk_results_student');
        $this->db->query('ALTER TABLE assessment_results DROP FOREIGN KEY fk_results_reviewed_by');

        // Drop table
        $this->forge->dropTable('assessment_results');
    }
}
