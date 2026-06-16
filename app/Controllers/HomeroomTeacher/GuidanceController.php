<?php

/**
 * File: app/Controllers/HomeroomTeacher/GuidanceController.php
 * Fitur: Bimbingan.
 * Peran/izin: Wali Kelas melihat bimbingan yang terkait kelas perwaliannya.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class GuidanceController extends BaseBkServiceController
{
    protected string $roleKey = 'wali-kelas';
    protected string $roleLabel = 'Wali Kelas';
    protected string $routePrefix = 'homeroom/guidance';
    protected string $viewPrefix = 'homeroom_teacher';
    protected string $serviceType = 'Bimbingan';
    protected bool $canManage = false;
}
