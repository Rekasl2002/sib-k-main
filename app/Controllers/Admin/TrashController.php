<?php

namespace App\Controllers\Admin;

use App\Controllers\RoleFeatures\BaseTrashController;

class TrashController extends BaseTrashController
{
    protected string $routePrefix = 'admin';
    protected string $viewPrefix  = 'admin/trash';
    protected string $roleLabel   = 'Admin';
}
