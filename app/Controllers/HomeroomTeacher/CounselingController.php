<?php

/**
 * File: app/Controllers/HomeroomTeacher/CounselingController.php
 * Fitur: Konseling.
 * Peran/izin: Wali Kelas melihat jadwal/ringkasan terbatas yang terkait kelas.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CounselingController extends BaseBkServiceController
{
    protected string $roleKey = 'wali-kelas';
    protected string $roleLabel = 'Wali Kelas';
    protected string $routePrefix = 'homeroom/counseling';
    protected string $viewPrefix = 'homeroom_teacher';
    protected string $serviceType = 'Konseling';
    protected bool $canManage = false;
}
