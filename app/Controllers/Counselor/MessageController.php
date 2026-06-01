<?php

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseRoleMessageController;

class MessageController extends BaseRoleMessageController
{
    protected string $routePrefix = 'counselor';
    protected string $viewPrefix = 'counselor/messages';
    protected string $roleLabel = 'Guru BK';
}
