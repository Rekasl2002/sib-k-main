<?php

/**
 * File: app/Controllers/Koordinator/ParentCollaborationController.php
 * Fitur: Kolaborasi Orang Tua.
 * Peran/izin: Koordinator BK mengelola agenda dan ringkasan kolaborasi.
 * Berhubungan dengan: BaseBkServiceController, parent_collaborations.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class ParentCollaborationController extends BaseBkServiceController
{
    protected string $roleKey = 'koordinator-bk';
    protected string $roleLabel = 'Koordinator BK';
    protected string $routePrefix = 'koordinator/parent-collaborations';
    protected string $viewPrefix = 'koordinator';
    protected string $serviceType = 'Kolaborasi Orang Tua';
    protected bool $canManage = true;
}
