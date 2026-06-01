<?php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseRoleMessageController;

class MessageController extends BaseRoleMessageController
{
    protected string $routePrefix = 'homeroom';
    protected string $viewPrefix = 'homeroom_teacher/messages';
    protected string $roleLabel = 'Wali Kelas';
}
