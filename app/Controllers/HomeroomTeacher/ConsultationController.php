<?php

/**
 * File: app/Controllers/HomeroomTeacher/ConsultationController.php
 * Fitur: Konsultasi & Pengaduan.
 * Peran/izin: Wali Kelas mengajukan konsultasi/pengaduan dan melihat data
 * yang terkait kelas perwaliannya.
 * Berhubungan dengan: BaseConsultationController.
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseConsultationController;

class ConsultationController extends BaseConsultationController
{
    protected string $roleKey = 'wali-kelas';
    protected string $roleLabel = 'Wali Kelas';
    protected string $routePrefix = 'homeroom/consultations';
    protected string $viewPrefix = 'homeroom_teacher';
    protected bool $canSubmit = true;
    protected bool $canReview = false;
}
