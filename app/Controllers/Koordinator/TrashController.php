<?php

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseTrashController;

class TrashController extends BaseTrashController
{
    protected string $routePrefix = 'koordinator';
    protected string $viewPrefix  = 'koordinator/trash';
    protected string $roleLabel   = 'Koordinator BK';
}
