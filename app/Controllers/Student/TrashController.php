<?php

namespace App\Controllers\Student;

use App\Controllers\RoleFeatures\BaseTrashController;

class TrashController extends BaseTrashController
{
    protected string $routePrefix = 'student';
    protected string $viewPrefix  = 'student/trash';
    protected string $roleLabel   = 'Siswa';
}
