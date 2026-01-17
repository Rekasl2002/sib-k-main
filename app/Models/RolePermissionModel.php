<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = ['role_id', 'permission_id', 'created_at'];
    // PENTING: tabel role_permissions TIDAK punya updated_at
    protected $useTimestamps    = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
