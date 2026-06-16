<?php

/**
 * File: app/Controllers/Koordinator/GuidanceController.php
 * Fitur: Bimbingan.
 * Peran/izin: Koordinator BK mengelola dan memantau seluruh bimbingan.
 * Berhubungan dengan: BaseBkServiceController, bk_service_records, guidances.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class GuidanceController extends BaseBkServiceController
{
    protected string $roleKey = 'koordinator-bk';
    protected string $roleLabel = 'Koordinator BK';
    protected string $routePrefix = 'koordinator/guidance';
    protected string $viewPrefix = 'koordinator';
    protected string $serviceType = 'Bimbingan';
    protected bool $canManage = true;
}
