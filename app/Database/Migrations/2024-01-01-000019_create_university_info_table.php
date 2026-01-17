<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUniversityInfoTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'university_name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'alias'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'accreditation'   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true], // Unggul/A/B/C
            'location'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'website'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'logo'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'faculties'       => ['type' => 'JSON', 'null' => true], // ["Teknik","Kedokteran",...]
            'programs'        => ['type' => 'JSON', 'null' => true], // [{name:"Informatika",degree:"S1"}]
            'admission_info'  => ['type' => 'TEXT', 'null' => true], // SNBP/SNBT/Mandiri
            'tuition_range'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'scholarships'    => ['type' => 'TEXT', 'null' => true],
            'contacts'        => ['type' => 'TEXT', 'null' => true],
            'is_public'       => ['type' => 'TINYINT', 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->createTable('university_info');
    }

    public function down()
    {
        $this->forge->dropTable('university_info');
    }
}
