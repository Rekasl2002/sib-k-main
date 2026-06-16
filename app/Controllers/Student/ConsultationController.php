<?php

/**
 * File: app/Controllers/Student/ConsultationController.php
 * Fitur: Konsultasi & Pengaduan.
 * Peran/izin: Siswa mengajukan konsultasi/pengaduan dan memantau statusnya.
 * Berhubungan dengan: BaseConsultationController.
 */

namespace App\Controllers\Student;

use App\Controllers\RoleFeatures\BaseConsultationController;

class ConsultationController extends BaseConsultationController
{
    protected string $roleKey = 'siswa';
    protected string $roleLabel = 'Siswa';
    protected string $routePrefix = 'student/consultations';
    protected string $viewPrefix = 'student';
    protected bool $canSubmit = true;
    protected bool $canReview = false;
}
