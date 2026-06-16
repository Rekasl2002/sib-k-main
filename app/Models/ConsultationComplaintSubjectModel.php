<?php

/**
 * File: app/Models/ConsultationComplaintSubjectModel.php
 * Fitur: Konsultasi & Pengaduan - subjek (siswa terkait) lebih dari satu.
 * Setiap baris = satu siswa dari data (student_id) ATAU nama manual (manual_name).
 * Berhubungan dengan: consultation_complaints, students.
 */

namespace App\Models;

use CodeIgniter\Model;

class ConsultationComplaintSubjectModel extends Model
{
    protected $table          = 'consultation_complaint_subjects';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'complaint_id',
        'student_id',
        'manual_name',
    ];

    protected $dateFormat   = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
