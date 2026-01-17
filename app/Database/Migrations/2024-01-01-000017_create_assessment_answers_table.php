<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000016_create_assessment_answers_table.php
 * 
 * Migration: Create Assessment Answers Table
 * Tabel untuk menyimpan jawaban siswa terhadap pertanyaan asesmen
 * 
 * @package    SIB-K
 * @subpackage Migrations
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssessmentAnswersTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('assessment_answers')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'question_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Foreign key ke tabel assessment_questions',
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Foreign key ke tabel students',
            ],
            'result_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Foreign key ke tabel assessment_results',
            ],
            'answer_text' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Jawaban teks (untuk Essay)',
            ],
            'answer_option' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Pilihan jawaban (untuk Multiple Choice/True-False/Rating)',
            ],
            'answer_options' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Multiple pilihan (JSON array untuk Checkbox)',
            ],
            'score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => 0.00,
                'comment'    => 'Nilai/skor yang diperoleh',
            ],
            'is_correct' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'comment'    => 'Flag jawaban benar (1=benar, 0=salah, null=belum dinilai)',
            ],
            'is_auto_graded' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Dinilai otomatis oleh sistem (1=ya, 0=manual)',
            ],
            'graded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'User ID guru BK yang menilai (untuk manual grading)',
            ],
            'graded_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu penilaian',
            ],
            'feedback' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Feedback dari guru untuk jawaban siswa',
            ],
            'answered_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Waktu siswa menjawab pertanyaan',
            ],
            'time_spent_seconds' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Waktu yang dihabiskan untuk menjawab (detik)',
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
        $this->forge->addKey('question_id');
        $this->forge->addKey('student_id');
        $this->forge->addKey('result_id');
        $this->forge->addKey('is_auto_graded');
        $this->forge->addKey(['student_id', 'question_id']); // Composite index - unique student answer per question
        $this->forge->addKey(['result_id', 'question_id']); // For result compilation
        $this->forge->addKey(['graded_by', 'graded_at']); // For tracking graded answers

        // Create table
        $this->forge->createTable('assessment_answers');

        // Add Foreign Keys
        $this->db->query('ALTER TABLE assessment_answers 
            ADD CONSTRAINT fk_answers_question 
            FOREIGN KEY (question_id) REFERENCES assessment_questions(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE assessment_answers 
            ADD CONSTRAINT fk_answers_student 
            FOREIGN KEY (student_id) REFERENCES students(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE assessment_answers 
            ADD CONSTRAINT fk_answers_result 
            FOREIGN KEY (result_id) REFERENCES assessment_results(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE assessment_answers 
            ADD CONSTRAINT fk_answers_graded_by 
            FOREIGN KEY (graded_by) REFERENCES users(id) 
            ON DELETE SET NULL ON UPDATE CASCADE');

        // Add unique constraint - one answer per student per question per attempt
        $this->db->query('ALTER TABLE assessment_answers 
            ADD UNIQUE KEY unique_student_question_result (student_id, question_id, result_id)');

        // Add table comment
        $this->db->query("ALTER TABLE assessment_answers COMMENT = 'Tabel jawaban siswa pada asesmen'");
    }

    public function down()
    {


        if (! $this->db->tableExists('assessment_answers')) {
            return;
        }

        $this->db->disableForeignKeyChecks();

        try {
            $this->forge->dropTable('assessment_answers', true);
        } finally {
            $this->db->enableForeignKeyChecks();
        }
    }
}
