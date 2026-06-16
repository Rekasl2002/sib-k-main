<?php

/**
 * File: app/Controllers/Parents/CaseConferenceController.php
 * Fitur: Konferensi Kasus.
 * Peran/izin: Orang Tua melihat konferensi kasus jika menjadi peserta/undangan.
 * Berhubungan dengan: BaseBkServiceController.
 */

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseBkServiceController;

class CaseConferenceController extends BaseBkServiceController
{
    protected string $roleKey = 'orang-tua';
    protected string $roleLabel = 'Orang Tua';
    protected string $routePrefix = 'parent/case-conferences';
    protected string $viewPrefix = 'parent';
    protected string $serviceType = 'Konferensi Kasus';
    protected bool $canManage = false;
}
