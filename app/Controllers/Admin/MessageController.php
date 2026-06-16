<?php

/**
 * File: app/Controllers/Admin/MessageController.php
 * Fitur: Pesan Internal.
 * Peran/izin: Admin dengan izin send_messages memakai controller per peran.
 * Berhubungan dengan: BaseRoleMessageController dan view admin/messages.
 */

namespace App\Controllers\Admin;

use App\Controllers\RoleFeatures\BaseRoleMessageController;

class MessageController extends BaseRoleMessageController
{
    protected string $routePrefix = 'admin';
    protected string $viewPrefix = 'admin/messages';
    protected string $roleLabel = 'Admin';
}
