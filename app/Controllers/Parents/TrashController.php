<?php

namespace App\Controllers\Parents;

use App\Controllers\RoleFeatures\BaseTrashController;

class TrashController extends BaseTrashController
{
    protected string $routePrefix = 'parent';
    protected string $viewPrefix  = 'parent/trash';
    protected string $roleLabel   = 'Orang Tua';
}
