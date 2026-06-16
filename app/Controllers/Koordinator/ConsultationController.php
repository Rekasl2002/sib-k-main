<?php

/**
 * File: app/Controllers/Koordinator/ConsultationController.php
 * Fitur: Konsultasi & Pengaduan.
 * Peran/izin: Koordinator BK meninjau, membuat bila perlu, dan mengubah status.
 * Berhubungan dengan: BaseConsultationController, consultation_complaints.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseConsultationController;

class ConsultationController extends BaseConsultationController
{
    protected string $roleKey = 'koordinator-bk';
    protected string $roleLabel = 'Koordinator BK';
    protected string $routePrefix = 'koordinator/consultations';
    protected string $viewPrefix = 'koordinator';
    protected bool $canSubmit = true;
    protected bool $canReview = true;
}
