<?php

/**
 * File: app/Controllers/Student/CounselingController.php
 * Fitur: Konseling.
 * Peran/izin: Siswa melihat jadwal dan status konseling miliknya tanpa catatan
 * rahasia internal BK.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Student;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CounselingController extends BaseBkServiceController
{
    protected string $roleKey = 'siswa';
    protected string $roleLabel = 'Siswa';
    protected string $routePrefix = 'student/counseling';
    protected string $viewPrefix = 'student';
    protected string $serviceType = 'Konseling';
    protected bool $canManage = false;
}
