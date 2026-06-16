<?php

/**
 * File: app/Controllers/Parents/HomeVisitController.php
 * Fitur: Kunjungan Rumah.
 * Peran/izin: Orang Tua melihat jadwal/hasil ringkas kunjungan rumah anak.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class HomeVisitController extends BaseBkServiceController
{
    protected string $roleKey = 'orang-tua';
    protected string $roleLabel = 'Orang Tua';
    protected string $routePrefix = 'parent/home-visits';
    protected string $viewPrefix = 'parent';
    protected string $serviceType = 'Kunjungan Rumah';
    protected bool $canManage = false;
}
