<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageAttachmentModel extends Model
{
    protected $table          = 'message_attachments';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $returnType     = 'array';

    protected $allowedFields = [
        'message_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
        'deleted_by',
    ];
}
