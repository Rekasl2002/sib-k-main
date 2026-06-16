<?php

/**
 * File: app/Models/ConsultationComplaintModel.php
 * Fitur: Konsultasi dan Pengaduan.
 * Peran/izin: Siswa, Orang Tua, dan Wali Kelas dapat mengajukan; Guru BK dan
 * Koordinator BK meninjau/menindaklanjuti.
 * Berhubungan dengan: users, students, bk_service_records.
 */

namespace App\Models;

use CodeIgniter\Model;

class ConsultationComplaintModel extends Model
{
    protected $table            = 'consultation_complaints';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'reporter_type',
        'reporter_user_id',
        'subject_student_id',
        'subject_other_name',
        'request_type',
        'category',
        'title',
        'description',
        'occurred_at',
        'location',
        'witness',
        'priority',
        'status',
        'privacy_level',
        'visible_to_homeroom',
        'visible_to_parent',
        'visible_to_student',
        'assigned_to_user_id',
        'handled_by',
        'handled_at',
        'closed_at',
        'converted_service_record_id',
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
