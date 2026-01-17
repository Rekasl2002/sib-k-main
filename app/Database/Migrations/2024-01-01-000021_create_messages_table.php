<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMessagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'subject'    => ['type'=>'VARCHAR','constraint'=>200],
            'body'       => ['type'=>'TEXT','null'=>true],
            'created_by' => ['type'=>'INT','unsigned'=>true],
            'is_draft'   => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
            'deleted_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['created_by','is_draft']);
        $this->forge->addForeignKey('created_by','users','id','CASCADE','CASCADE');
        $this->forge->createTable('messages', true);
    }

    public function down()
    {
        $this->forge->dropTable('messages', true);
    }
}
