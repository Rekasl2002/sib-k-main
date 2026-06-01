<?php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseRoleNotificationController;

class NotificationController extends BaseRoleNotificationController
{
    protected string $routePrefix = 'homeroom';
    protected string $viewPrefix = 'homeroom_teacher/notifications';
    protected string $roleLabel = 'Wali Kelas';
}
