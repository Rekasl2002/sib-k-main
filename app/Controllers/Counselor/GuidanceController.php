<?php

/**
 * File: app/Controllers/Counselor/GuidanceController.php
 * Fitur: Bimbingan.
 * Peran/izin: Guru BK mengelola bimbingan untuk kelas/siswa binaan.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class GuidanceController extends BaseBkServiceController
{
    protected string $roleKey = 'guru-bk';
    protected string $roleLabel = 'Guru BK';
    protected string $routePrefix = 'counselor/guidance';
    protected string $viewPrefix = 'counselor';
    protected string $serviceType = 'Bimbingan';
    protected bool $canManage = true;
}
