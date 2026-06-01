<?php

namespace App\Controllers\Student;

use App\Controllers\RoleFeatures\BaseRoleMessageController;

class MessageController extends BaseRoleMessageController
{
    protected string $routePrefix = 'student';
    protected string $viewPrefix = 'student/messages';
    protected string $roleLabel = 'Siswa';
}
