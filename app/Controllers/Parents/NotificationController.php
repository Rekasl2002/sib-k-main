<?php

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseRoleNotificationController;

class NotificationController extends BaseRoleNotificationController
{
    protected string $routePrefix = 'parent';
    protected string $viewPrefix = 'parent/notifications';
    protected string $roleLabel = 'Orang Tua';
}
