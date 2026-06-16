<?php

namespace App\Controllers\Student;

use App\Controllers\RoleFeatures\BaseRoleNotificationController;

class NotificationController extends BaseRoleNotificationController
{
    protected string $routePrefix = 'student';
    protected string $viewPrefix = 'student/notifications';
    protected string $roleLabel = 'Siswa';
    protected bool $restrictBkDetail = true;
}
