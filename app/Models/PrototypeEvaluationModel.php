<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model untuk Form Evaluasi Prototipe (modul sementara tahap evaluasi skripsi).
 */
class PrototypeEvaluationModel extends Model
{
    protected $table            = 'prototype_evaluations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'user_id',
        'respondent_name',
        'respondent_relation',
        'respondent_role',
        'role_label',
        'consent_participate',
        'consent_data_usage',
        'reviewed_prototype',
        'accessible_feature_count',
        'feature_notes',
        'suggestions',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];
}
