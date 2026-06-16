<?php

/**
 * File: app/Controllers/Counselor/ParentCollaborationController.php
 * Fitur: Kolaborasi Orang Tua.
 * Peran/izin: Guru BK mencatat kolaborasi bersama orang tua/wali.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class ParentCollaborationController extends BaseBkServiceController
{
    protected string $roleKey = 'guru-bk';
    protected string $roleLabel = 'Guru BK';
    protected string $routePrefix = 'counselor/parent-collaborations';
    protected string $viewPrefix = 'counselor';
    protected string $serviceType = 'Kolaborasi Orang Tua';
    protected bool $canManage = true;
}
