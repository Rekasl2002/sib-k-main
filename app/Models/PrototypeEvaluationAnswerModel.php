<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model jawaban per fitur x pertanyaan untuk Form Evaluasi Prototipe (modul sementara).
 */
class PrototypeEvaluationAnswerModel extends Model
{
    protected $table            = 'prototype_evaluation_answers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'evaluation_id',
        'feature_key',
        'feature_title',
        'category',
        'question_no',
        'question_text',
        'answer',
    ];
}
