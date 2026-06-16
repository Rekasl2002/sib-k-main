<?php

/**
 * File: app/Controllers/HomeroomTeacher/ParentCollaborationController.php
 * Fitur: Kolaborasi Orang Tua.
 * Peran/izin: Wali Kelas melihat kolaborasi yang terkait siswa/kelasnya.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class ParentCollaborationController extends BaseBkServiceController
{
    protected string $roleKey = 'wali-kelas';
    protected string $roleLabel = 'Wali Kelas';
    protected string $routePrefix = 'homeroom/parent-collaborations';
    protected string $viewPrefix = 'homeroom_teacher';
    protected string $serviceType = 'Kolaborasi Orang Tua';
    protected bool $canManage = false;
}
