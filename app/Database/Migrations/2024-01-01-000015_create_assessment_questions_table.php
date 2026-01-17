<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000015_create_assessment_questions_table.php
 * 
 * Migration: Create Assessment Questions Table
 * Tabel untuk menyimpan pertanyaan-pertanyaan dalam asesmen
 * 
 * @package    SIB-K
 * @subpackage Migrations
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssessmentQuestionsTable extends Migration
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
            'question_text' => [
                'type'    => 'TEXT',
                'comment' => 'Teks pertanyaan',
            ],
            'question_type' => [
                'type'       => 'ENUM',
                'constraint' => ['Multiple Choice', 'Essay', 'True/False', 'Rating Scale', 'Checkbox'],
                'default'    => 'Multiple Choice',
                'comment'    => 'Tipe pertanyaan',
            ],
            'options' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Pilihan jawaban (JSON array) untuk Multiple Choice/Checkbox',
            ],
            'correct_answer' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Jawaban yang benar (untuk auto-scoring)',
            ],
            'points' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 1.00,
                'comment'    => 'Poin untuk jawaban benar',
            ],
            'order_number' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Urutan tampilan pertanyaan',
            ],
            'is_required' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Wajib dijawab (1=wajib, 0=opsional)',
            ],
            'explanation' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Penjelasan/pembahasan jawaban',
            ],
            'image_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'URL gambar pendukung pertanyaan',
            ],
            'dimension' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Dimensi/aspek yang diukur (untuk asesmen psikologi)',
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
        $this->forge->addKey('question_type');
        $this->forge->addKey(['assessment_id', 'order_number']); // Composite index for ordered retrieval
        $this->forge->addKey(['assessment_id', 'deleted_at']); // For active questions query

        // Create table
        $this->forge->createTable('assessment_questions');

        // Add Foreign Key
        $this->db->query('ALTER TABLE assessment_questions 
            ADD CONSTRAINT fk_questions_assessment 
            FOREIGN KEY (assessment_id) REFERENCES assessments(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        // Add table comment
        $this->db->query("ALTER TABLE assessment_questions COMMENT = 'Tabel pertanyaan asesmen'");
    }

    public function down()
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE assessment_questions DROP FOREIGN KEY fk_questions_assessment');

        // Drop table
        $this->forge->dropTable('assessment_questions');
    }
}
