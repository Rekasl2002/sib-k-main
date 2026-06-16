<?php

/**
 * File: app/Controllers/Koordinator/HomeVisitController.php
 * Fitur: Kunjungan Rumah.
 * Peran/izin: Koordinator BK mengelola jadwal, alamat, dan hasil kunjungan.
 * Berhubungan dengan: BaseBkServiceController, home_visits.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class HomeVisitController extends BaseBkServiceController
{
    protected string $roleKey = 'koordinator-bk';
    protected string $roleLabel = 'Koordinator BK';
    protected string $routePrefix = 'koordinator/home-visits';
    protected string $viewPrefix = 'koordinator';
    protected string $serviceType = 'Kunjungan Rumah';
    protected bool $canManage = true;
}
