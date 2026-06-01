<?php

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseRoleMessageController;

class MessageController extends BaseRoleMessageController
{
    protected string $routePrefix = 'koordinator';
    protected string $viewPrefix = 'koordinator/messages';
    protected string $roleLabel = 'Koordinator BK';
}
