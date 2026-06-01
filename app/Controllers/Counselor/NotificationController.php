<?php

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseRoleNotificationController;

class NotificationController extends BaseRoleNotificationController
{
    protected string $routePrefix = 'counselor';
    protected string $viewPrefix = 'counselor/notifications';
    protected string $roleLabel = 'Guru BK';
}
