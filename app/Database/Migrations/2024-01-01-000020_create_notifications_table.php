<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'user_id'    => ['type'=>'INT','unsigned'=>true],
            'title'      => ['type'=>'VARCHAR','constraint'=>150],
            'message'    => ['type'=>'TEXT','null'=>true],
            'type'       => ['type'=>'VARCHAR','constraint'=>20,'default'=>'info'],
            'link'       => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'data'       => ['type'=>'JSON','null'=>true], // MariaDB 10.6+/MySQL 8
            'is_read'    => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'read_at'    => ['type'=>'DATETIME','null'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
            'deleted_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id','is_read']);
        $this->forge->addForeignKey('user_id','users','id','CASCADE','CASCADE');
        $this->forge->createTable('notifications', true);
    }

    public function down()
    {
        $this->forge->dropTable('notifications', true);
    }
}
