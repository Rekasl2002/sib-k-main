<?php

/**
 * File: app/Models/ConsultationComplaintAttachmentModel.php
 * Fitur: Konsultasi & Pengaduan - lampiran/bukti.
 * Berhubungan dengan: consultation_complaints, users (uploaded_by).
 */

namespace App\Models;

use CodeIgniter\Model;

class ConsultationComplaintAttachmentModel extends Model
{
    protected $table          = 'consultation_complaint_attachments';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'complaint_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
        'deleted_by',
    ];

    protected $dateFormat   = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
