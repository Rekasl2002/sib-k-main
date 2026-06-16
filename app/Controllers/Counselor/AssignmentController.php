<?php

/**
 * File: app/Controllers/Counselor/AssignmentController.php
 * Fitur: Penugasan.
 * Peran/izin: Guru BK melihat dan memperbarui status tugas dari Koordinator BK.
 * Berhubungan dengan: BaseBkAssignmentController.
 */

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseBkAssignmentController;

class AssignmentController extends BaseBkAssignmentController
{
    protected string $roleKey = 'guru-bk';
    protected string $roleLabel = 'Guru BK';
    protected string $routePrefix = 'counselor/assignments';
    protected string $viewPrefix = 'counselor';
    protected bool $canManage = false;
    protected string $featureLabel = 'Tugas';
}
