<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCareerOptionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'sector'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'min_education'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // SMA/SMK, D3, S1, dst
            'description'      => ['type' => 'TEXT', 'null' => true],
            'required_skills'  => ['type' => 'JSON', 'null' => true], // ["Komunikasi","Analisis",...]
            'pathways'         => ['type' => 'TEXT', 'null' => true],  // jalur/roadmap singkat
            'avg_salary_idr'   => ['type' => 'INT', 'null' => true],   // rata2 gaji (estimasi)
            'demand_level'     => ['type' => 'TINYINT', 'default' => 0, 'null' => false], // 0-10
            'external_links'   => ['type' => 'JSON', 'null' => true], // [{label,url}]
            'is_active'        => ['type' => 'TINYINT', 'default' => 1],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('sector');
        $this->forge->addKey('is_active');

        $this->forge->createTable('career_options');

        // FK opsional ke users (pembuat konten)
        $this->db->query("
            ALTER TABLE career_options
            ADD CONSTRAINT fk_career_created_by
            FOREIGN KEY (created_by) REFERENCES users(id)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }

    public function down()
    {
        $this->forge->dropTable('career_options');
    }
}
