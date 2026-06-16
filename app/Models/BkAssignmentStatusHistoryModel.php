<?php

/**
 * File: app/Models/BkAssignmentStatusHistoryModel.php
 * Fitur: Riwayat status Penugasan.
 * Peran/izin: Dibuat otomatis saat Koordinator BK atau Guru BK mengubah status
 * tugas.
 * Berhubungan dengan: bk_assignments dan users.
 */

namespace App\Models;

use CodeIgniter\Model;

class BkAssignmentStatusHistoryModel extends Model
{
    protected $table            = 'bk_assignment_status_histories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'assignment_id',
        'status',
        'note',
        'changed_by',
        'changed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
