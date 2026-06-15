<?php

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseTrashController;

class TrashController extends BaseTrashController
{
    protected string $routePrefix = 'counselor';
    protected string $viewPrefix  = 'counselor/trash';
    protected string $roleLabel   = 'Guru BK';
}
