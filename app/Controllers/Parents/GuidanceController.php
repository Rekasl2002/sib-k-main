<?php

/**
 * File: app/Controllers/Parents/GuidanceController.php
 * Fitur: Bimbingan.
 * Peran/izin: Orang Tua melihat bimbingan yang aman ditampilkan terkait anak.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class GuidanceController extends BaseBkServiceController
{
    protected string $roleKey = 'orang-tua';
    protected string $roleLabel = 'Orang Tua';
    protected string $routePrefix = 'parent/guidance';
    protected string $viewPrefix = 'parent';
    protected string $serviceType = 'Bimbingan';
    protected bool $canManage = false;
}
