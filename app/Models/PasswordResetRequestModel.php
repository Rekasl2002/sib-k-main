<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetRequestModel extends Model
{
    protected $table          = 'password_reset_requests';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id',
        'email',
        'phone',
        'status',
        'admin_message_id',
        'admin_notification_id',
        'requested_ip',
        'user_agent',
        'requested_at',
        'notified_at',
        'resolved_by',
        'resolved_at',
        'notes',
    ];
}
