<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'general, branding, academic, mail, security, notifications, points'],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'string', 'comment' => 'string,int,bool,json'],
            'autoload'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['group', 'key']);
        $this->forge->createTable('settings', true);

        // Seed minimal defaults
        $this->db->table('settings')->insertBatch([
            ['group'=>'general','key'=>'app_name','value'=>'SIB-K','type'=>'string','autoload'=>1],
            ['group'=>'general','key'=>'school_name','value'=>'Madrasah Aliyah Persis 31 Banjaran','type'=>'string','autoload'=>1],
            ['group'=>'general','key'=>'contact_email','value'=>'admin@example.test','type'=>'string','autoload'=>1],
            ['group'=>'branding','key'=>'logo_path','value'=>null,'type'=>'string','autoload'=>1],
            ['group'=>'branding','key'=>'favicon_path','value'=>null,'type'=>'string','autoload'=>1],
            ['group'=>'academic','key'=>'default_academic_year_id','value'=>null,'type'=>'int','autoload'=>1],
            ['group'=>'notifications','key'=>'enable_email','value'=>'1','type'=>'bool','autoload'=>1],
            ['group'=>'notifications','key'=>'enable_internal','value'=>'1','type'=>'bool','autoload'=>1],
            ['group'=>'mail','key'=>'from_name','value'=>'SIB-K','type'=>'string','autoload'=>1],
            ['group'=>'mail','key'=>'from_email','value'=>'no-reply@example.test','type'=>'string','autoload'=>1],
            ['group'=>'security','key'=>'session_timeout_minutes','value'=>'60','type'=>'int','autoload'=>1],
            ['group'=>'points','key'=>'probation_threshold','value'=>'50','type'=>'int','autoload'=>1],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('settings', true);
    }
}
