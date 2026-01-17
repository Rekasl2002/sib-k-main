<?php

/**
 * File Path: app/Models/PermissionModel.php
 * 
 * Permission Model
 * Mengelola data permissions/izin akses dalam sistem RBAC
 * 
 * @package    SIB-K
 * @subpackage Models
 * @category   RBAC
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    public    $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'key',
        'name',
        'permission_name',
        'description',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'permission_name' => 'required|min_length[3]|max_length[100]|is_unique[permissions.permission_name,id,{id}]',
        'description'     => 'permit_empty|max_length[500]',
    ];

    protected $validationMessages = [
        'permission_name' => [
            'required'   => 'Nama permission harus diisi',
            'min_length' => 'Nama permission minimal 3 karakter',
            'max_length' => 'Nama permission maksimal 100 karakter',
            'is_unique'  => 'Nama permission sudah digunakan',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get all permissions grouped by category
     * 
     * @return array
     */
    public function getPermissionsGrouped()
    {
        $permissions = $this->orderBy('permission_name', 'ASC')->findAll();

        $grouped = [
            'User Management' => [],
            'Academic Data'   => [],
            'Counseling'      => [],
            'Violations'      => [],
            'Assessments'     => [],
            'Reports'         => [],
            'Communication'   => [],
            'Career Info'     => [],
            'Others'          => [],
        ];

        foreach ($permissions as $permission) {
            $name = $permission['permission_name'];

            if (strpos($name, 'manage_users') !== false || strpos($name, 'manage_roles') !== false) {
                $grouped['User Management'][] = $permission;
            } elseif (strpos($name, 'academic') !== false) {
                $grouped['Academic Data'][] = $permission;
            } elseif (strpos($name, 'counseling') !== false || strpos($name, 'schedule_counseling') !== false) {
                $grouped['Counseling'][] = $permission;
            } elseif (strpos($name, 'violation') !== false || strpos($name, 'sanction') !== false) {
                $grouped['Violations'][] = $permission;
            } elseif (strpos($name, 'assessment') !== false) {
                $grouped['Assessments'][] = $permission;
            } elseif (strpos($name, 'report') !== false) {
                $grouped['Reports'][] = $permission;
            } elseif (strpos($name, 'message') !== false) {
                $grouped['Communication'][] = $permission;
            } elseif (strpos($name, 'career') !== false) {
                $grouped['Career Info'][] = $permission;
            } else {
                $grouped['Others'][] = $permission;
            }
        }

        // Remove empty groups
        return array_filter($grouped, function ($group) {
            return !empty($group);
        });
    }

    /**
     * Get permissions for specific role
     * 
     * @param int $roleId
     * @return array
     */
    public function getPermissionsByRole($roleId)
    {
        $db = \Config\Database::connect();

        return $db->table('role_permissions')
            ->select('permissions.*')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->orderBy('permissions.permission_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get permission IDs for specific role
     * 
     * @param int $roleId
     * @return array
     */
    public function getPermissionIdsByRole($roleId)
    {
        $db = \Config\Database::connect();

        $permissions = $db->table('role_permissions')
            ->select('permission_id')
            ->where('role_id', $roleId)
            ->get()
            ->getResultArray();

        return array_column($permissions, 'permission_id');
    }

    /**
     * Check if role has specific permission
     * 
     * @param int $roleId
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission($roleId, $permissionName)
    {
        $db = \Config\Database::connect();

        $count = $db->table('role_permissions')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->where('permissions.permission_name', $permissionName)
            ->countAllResults();

        return $count > 0;
    }

    /**
     * Get permission by name
     * 
     * @param string $permissionName
     * @return array|null
     */
    public function getPermissionByName($permissionName)
    {
        return $this->where('permission_name', $permissionName)->first();
    }

    /**
     * Check if permission can be deleted
     * 
     * @param int $permissionId
     * @return bool
     */
    public function canDelete($permissionId)
    {
        $db = \Config\Database::connect();

        // Check if permission is assigned to any role
        $count = $db->table('role_permissions')
            ->where('permission_id', $permissionId)
            ->countAllResults();

        return $count === 0;
    }
}
