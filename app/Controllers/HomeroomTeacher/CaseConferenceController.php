<?php

/**
 * File: app/Controllers/HomeroomTeacher/CaseConferenceController.php
 * Fitur: Konferensi Kasus.
 * Peran/izin: Wali Kelas melihat konferensi kasus jika terkait/diundang.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CaseConferenceController extends BaseBkServiceController
{
    protected string $roleKey = 'wali-kelas';
    protected string $roleLabel = 'Wali Kelas';
    protected string $routePrefix = 'homeroom/case-conferences';
    protected string $viewPrefix = 'homeroom_teacher';
    protected string $serviceType = 'Konferensi Kasus';
    protected bool $canManage = false;
}
