<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMessageParticipantsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'message_id' => ['type'=>'INT','unsigned'=>true],
            'user_id'    => ['type'=>'INT','unsigned'=>true],
            'role'       => ['type'=>'ENUM','constraint'=>['sender','to','cc','bcc'],'default'=>'to'],
            'is_read'    => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'read_at'    => ['type'=>'DATETIME','null'=>true],
            'starred'    => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
            'deleted_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['message_id','user_id','role']);
        $this->forge->addKey(['user_id','is_read']);
        $this->forge->addForeignKey('message_id','messages','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('user_id','users','id','CASCADE','CASCADE');
        $this->forge->createTable('message_participants', true);
    }

    public function down()
    {
        $this->forge->dropTable('message_participants', true);
    }
}
