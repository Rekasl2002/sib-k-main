<?php

/**
 * File: app/Controllers/Student/GuidanceController.php
 * Fitur: Bimbingan.
 * Peran/izin: Siswa melihat bimbingan yang terkait dirinya/kelasnya.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Student;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class GuidanceController extends BaseBkServiceController
{
    protected string $roleKey = 'siswa';
    protected string $roleLabel = 'Siswa';
    protected string $routePrefix = 'student/guidance';
    protected string $viewPrefix = 'student';
    protected string $serviceType = 'Bimbingan';
    protected bool $canManage = false;
}
