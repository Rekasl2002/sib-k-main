<?php

/**
 * File: app/Models/BkAssignmentTargetModel.php
 * Fitur: Penugasan Guru BK (Perbaikan Kedua / Item #10).
 * Peran/izin: Koordinator BK menetapkan banyak petugas (Guru BK), kelas, dan
 * siswa untuk satu tugas. Tabel pivot pelengkap bk_assignments.
 * Berhubungan dengan: bk_assignments, users, classes, students.
 */

namespace App\Models;

use CodeIgniter\Model;

class BkAssignmentTargetModel extends Model
{
    protected $table            = 'bk_assignment_targets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'assignment_id',
        'target_type',
        'user_id',
        'class_id',
        'student_id',
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
