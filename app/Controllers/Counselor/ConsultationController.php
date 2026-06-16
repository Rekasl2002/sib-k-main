<?php

/**
 * File: app/Controllers/Counselor/ConsultationController.php
 * Fitur: Konsultasi & Pengaduan.
 * Peran/izin: Guru BK meninjau, membuat, dan memproses konsultasi/pengaduan.
 * Berhubungan dengan: BaseConsultationController.
 */

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseConsultationController;

class ConsultationController extends BaseConsultationController
{
    protected string $roleKey = 'guru-bk';
    protected string $roleLabel = 'Guru BK';
    protected string $routePrefix = 'counselor/consultations';
    protected string $viewPrefix = 'counselor';
    protected bool $canSubmit = true;
    protected bool $canReview = true;
}
