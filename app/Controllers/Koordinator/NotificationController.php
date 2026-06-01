<?php

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseRoleNotificationController;

class NotificationController extends BaseRoleNotificationController
{
    protected string $routePrefix = 'koordinator';
    protected string $viewPrefix = 'koordinator/notifications';
    protected string $roleLabel = 'Koordinator BK';
}
