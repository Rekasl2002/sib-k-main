<?php

/**
 * File: app/Controllers/Koordinator/CounselingController.php
 * Fitur: Konseling baru.
 * Peran/izin: Koordinator BK melihat/mengelola konseling sesuai rancangan baru.
 * Berhubungan dengan: BaseBkServiceController, bk_service_records, counseling_sessions.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CounselingController extends BaseBkServiceController
{
    protected string $roleKey = 'koordinator-bk';
    protected string $roleLabel = 'Koordinator BK';
    protected string $routePrefix = 'koordinator/counseling';
    protected string $viewPrefix = 'koordinator';
    protected string $serviceType = 'Konseling';
    protected bool $canManage = true;
}
