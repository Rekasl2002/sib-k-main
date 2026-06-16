<?php

/**
 * File: app/Controllers/Parents/CounselingController.php
 * Fitur: Konseling.
 * Peran/izin: Orang Tua melihat jadwal/status konseling anak secara terbatas.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CounselingController extends BaseBkServiceController
{
    protected string $roleKey = 'orang-tua';
    protected string $roleLabel = 'Orang Tua';
    protected string $routePrefix = 'parent/counseling';
    protected string $viewPrefix = 'parent';
    protected string $serviceType = 'Konseling';
    protected bool $canManage = false;
}
