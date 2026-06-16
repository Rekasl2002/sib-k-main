<?php

/**
 * File: app/Controllers/Counselor/CaseConferenceController.php
 * Fitur: Konferensi Kasus.
 * Peran/izin: Guru BK mengelola atau mengikuti konferensi kasus sesuai tugas.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CaseConferenceController extends BaseBkServiceController
{
    protected string $roleKey = 'guru-bk';
    protected string $roleLabel = 'Guru BK';
    protected string $routePrefix = 'counselor/case-conferences';
    protected string $viewPrefix = 'counselor';
    protected string $serviceType = 'Konferensi Kasus';
    protected bool $canManage = true;
}
