<?php

/**
 * File: app/Controllers/Counselor/HomeVisitController.php
 * Fitur: Kunjungan Rumah.
 * Peran/izin: Guru BK mencatat rencana dan hasil kunjungan rumah.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class HomeVisitController extends BaseBkServiceController
{
    protected string $roleKey = 'guru-bk';
    protected string $roleLabel = 'Guru BK';
    protected string $routePrefix = 'counselor/home-visits';
    protected string $viewPrefix = 'counselor';
    protected string $serviceType = 'Kunjungan Rumah';
    protected bool $canManage = true;
}
