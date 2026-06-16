<?php

/**
 * File: app/Controllers/Parents/ConsultationController.php
 * Fitur: Konsultasi & Pengaduan.
 * Peran/izin: Orang Tua mengajukan konsultasi/pengaduan terkait anaknya.
 * Berhubungan dengan: BaseConsultationController.
 */

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseConsultationController;

class ConsultationController extends BaseConsultationController
{
    protected string $roleKey = 'orang-tua';
    protected string $roleLabel = 'Orang Tua';
    protected string $routePrefix = 'parent/consultations';
    protected string $viewPrefix = 'parent';
    protected bool $canSubmit = true;
    protected bool $canReview = false;
}
