<?php

/**
 * File: app/Controllers/Admin/NotificationController.php
 * Fitur: Notifikasi Internal.
 * Peran/izin: Admin dengan izin view_dashboard memakai controller per peran.
 * Berhubungan dengan: BaseRoleNotificationController dan view admin/notifications.
 */

namespace App\Controllers\Admin;

use App\Controllers\RoleFeatures\BaseRoleNotificationController;

class NotificationController extends BaseRoleNotificationController
{
    protected string $routePrefix = 'admin';
    protected string $viewPrefix = 'admin/notifications';
    protected string $roleLabel = 'Admin';
}
