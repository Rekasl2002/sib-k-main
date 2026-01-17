<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageParticipantModel extends Model
{
    protected $table          = 'message_participants';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['message_id','user_id','role','is_read','read_at','starred'];
}
