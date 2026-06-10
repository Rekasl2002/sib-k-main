<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Skema penyimpanan Form Evaluasi & Konfirmasi Penerimaan Prototipe SIB-K.
 *
 * CATATAN: Modul ini bersifat sementara (untuk tahap evaluasi prototipe skripsi).
 * Saat aplikasi sudah final, modul beserta tabel ini dapat dihapus dengan aman:
 * jalankan rollback migration ini, lalu hapus controller, views, routes, dan tombolnya.
 */
class CreatePrototypeEvaluationSchema extends Migration
{
    public function up()
    {
        // Tabel utama: satu baris per responden/pengisian.
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'respondent_name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'respondent_relation'      => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'respondent_role'          => ['type' => 'VARCHAR', 'constraint' => 30],
            'role_label'               => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'consent_participate'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'consent_data_usage'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reviewed_prototype'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'accessible_feature_count' => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'feature_notes'            => ['type' => 'TEXT', 'null' => true],
            'suggestions'              => ['type' => 'TEXT', 'null' => true],
            'ip_address'               => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'submitted_at'             => ['type' => 'DATETIME', 'null' => true],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('respondent_role');
        $this->forge->createTable('prototype_evaluations', true, ['ENGINE' => 'InnoDB']);

        // Tabel detail: satu baris per jawaban (fitur x pertanyaan).
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'evaluation_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'feature_key'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'feature_title' => ['type' => 'VARCHAR', 'constraint' => 120],
            'category'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'question_no'   => ['type' => 'TINYINT', 'constraint' => 2],
            'question_text' => ['type' => 'VARCHAR', 'constraint' => 190],
            'answer'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('evaluation_id');
        $this->forge->addKey('feature_key');
        $this->forge->addForeignKey('evaluation_id', 'prototype_evaluations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('prototype_evaluation_answers', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('prototype_evaluation_answers', true);
        $this->forge->dropTable('prototype_evaluations', true);
    }
}
