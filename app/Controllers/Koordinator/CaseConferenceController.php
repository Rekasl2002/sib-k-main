<?php

/**
 * File: app/Controllers/Koordinator/CaseConferenceController.php
 * Fitur: Konferensi Kasus.
 * Peran/izin: Koordinator BK mengelola konferensi dan keputusan tindak lanjut.
 * Berhubungan dengan: BaseBkServiceController, case_conferences.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CaseConferenceController extends BaseBkServiceController
{
    protected string $roleKey = 'koordinator-bk';
    protected string $roleLabel = 'Koordinator BK';
    protected string $routePrefix = 'koordinator/case-conferences';
    protected string $viewPrefix = 'koordinator';
    protected string $serviceType = 'Konferensi Kasus';
    protected bool $canManage = true;
}
