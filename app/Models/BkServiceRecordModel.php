<?php

/**
 * File: app/Models/BkServiceRecordModel.php
 * Fitur: Layanan BK terpadu.
 * Peran/izin: Koordinator BK dan Guru BK mengelola; Wali Kelas, Siswa, dan
 * Orang Tua hanya melihat data sesuai hak akses.
 * Berhubungan dengan: guidances, counseling_sessions, parent_collaborations,
 * home_visits, case_conferences, session_participants, session_notes.
 */

namespace App\Models;

use CodeIgniter\Model;

class BkServiceRecordModel extends Model
{
    protected $table            = 'bk_service_records';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'service_type',
        'title',
        'target_student_id',
        'target_class_id',
        'counselor_id',
        'assignment_id',
        'source_complaint_id',
        'scheduled_at',
        'held_at',
        'location',
        'status',
        'duration_minutes',
        'privacy_level',
        'visible_to_homeroom',
        'created_by',
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
