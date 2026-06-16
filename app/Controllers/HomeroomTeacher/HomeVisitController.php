<?php

/**
 * File: app/Controllers/HomeroomTeacher/HomeVisitController.php
 * Fitur: Kunjungan Rumah.
 * Peran/izin: Wali Kelas melihat kunjungan rumah yang berkaitan dengan kelasnya.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class HomeVisitController extends BaseBkServiceController
{
    protected string $roleKey = 'wali-kelas';
    protected string $roleLabel = 'Wali Kelas';
    protected string $routePrefix = 'homeroom/home-visits';
    protected string $viewPrefix = 'homeroom_teacher';
    protected string $serviceType = 'Kunjungan Rumah';
    protected bool $canManage = false;
}
