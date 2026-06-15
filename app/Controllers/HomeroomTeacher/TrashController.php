<?php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\RoleFeatures\BaseTrashController;

class TrashController extends BaseTrashController
{
    protected string $routePrefix = 'homeroom';
    protected string $viewPrefix  = 'homeroom_teacher/trash';
    protected string $roleLabel   = 'Wali Kelas';
}
