<?php

/**
 * File: app/Models/BkAssignmentModel.php
 * Fitur: Penugasan Guru BK.
 * Peran/izin: Koordinator BK membuat/memperbarui tugas; Guru BK melihat dan
 * memperbarui status tugas yang diberikan kepadanya.
 * Berhubungan dengan: users, classes, students, bk_assignment_status_histories.
 */

namespace App\Models;

use CodeIgniter\Model;

class BkAssignmentModel extends Model
{
    protected $table            = 'bk_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'assignment_type',
        'title',
        'instruction',
        'assigned_by',
        'assigned_to_user_id',
        'class_id',
        'student_id',
        'source_type',
        'source_id',
        'priority',
        'status',
        'due_at',
        'assigned_at',
        'completed_at',
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
