<?php

/**
 * File: app/Controllers/Parents/ParentCollaborationController.php
 * Fitur: Kolaborasi Orang Tua.
 * Peran/izin: Orang Tua melihat agenda dan ringkasan kolaborasi yang terkait.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class ParentCollaborationController extends BaseBkServiceController
{
    protected string $roleKey = 'orang-tua';
    protected string $roleLabel = 'Orang Tua';
    protected string $routePrefix = 'parent/parent-collaborations';
    protected string $viewPrefix = 'parent';
    protected string $serviceType = 'Kolaborasi Orang Tua';
    protected bool $canManage = false;
}
