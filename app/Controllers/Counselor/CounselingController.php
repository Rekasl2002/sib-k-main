<?php

/**
 * File: app/Controllers/Counselor/CounselingController.php
 * Fitur: Konseling baru.
 * Peran/izin: Guru BK mengelola konseling individu/kelompok sesuai hak akses.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CounselingController extends BaseBkServiceController
{
    protected string $roleKey = 'guru-bk';
    protected string $roleLabel = 'Guru BK';
    protected string $routePrefix = 'counselor/counseling';
    protected string $viewPrefix = 'counselor';
    protected string $serviceType = 'Konseling';
    protected bool $canManage = true;
}
