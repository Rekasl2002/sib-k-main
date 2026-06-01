<?php

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseRoleMessageController;

class MessageController extends BaseRoleMessageController
{
    protected string $routePrefix = 'parent';
    protected string $viewPrefix = 'parent/messages';
    protected string $roleLabel = 'Orang Tua';
}
