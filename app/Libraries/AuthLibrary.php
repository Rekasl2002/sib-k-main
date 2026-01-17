<?php

/**
 * File Path: app/Libraries/AuthLibrary.php
 * 
 * Auth Library
 * Menyediakan helper functions untuk authentication dan authorization (RBAC)
 * 
 * @package    SIB-K
 * @subpackage Libraries
 * @category   Authentication
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Libraries;

use App\Models\UserModel;
use App\Models\PermissionModel;

class AuthLibrary
{
    protected $session;
    protected $userModel;
    protected $permissionModel;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->userModel = new UserModel();
        $this->permissionModel = new PermissionModel();
    }

    /**
     * Login user
     * 
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password)
    {
        $user = $this->userModel->authenticate($username, $password);

        if (!$user) {
            return false;
        }

        // Set session data
        $sessionData = [
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'email'         => $user['email'],
            'full_name'     => $user['full_name'],
            'role_id'       => $user['role_id'],
            'role_name'     => $user['role_name'],
            'permissions'   => $user['permissions'],
            'profile_photo' => $user['profile_photo'] ?? null,
            'is_logged_in'  => true,
        ];

        $this->session->set($sessionData);

        // Log activity
        $this->logActivity($user['id'], 'login', 'User logged in');

        return true;
    }

    /**
     * Logout user
     * 
     * @return void
     */
    public function logout()
    {
        $userId = $this->session->get('user_id');

        if ($userId) {
            $this->logActivity($userId, 'logout', 'User logged out');
        }

        $this->session->destroy();
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->session->get('is_logged_in') === true;
    }

    /**
     * Get current user data
     * 
     * @return array|null
     */
    public function user()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id'            => $this->session->get('user_id'),
            'username'      => $this->session->get('username'),
            'email'         => $this->session->get('email'),
            'full_name'     => $this->session->get('full_name'),
            'role_id'       => $this->session->get('role_id'),
            'role_name'     => $this->session->get('role_name'),
            'permissions'   => $this->session->get('permissions'),
            'profile_photo' => $this->session->get('profile_photo'),
        ];
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public function id()
    {
        return $this->session->get('user_id');
    }

    /**
     * Get current user role
     * 
     * @return string|null
     */
    public function role()
    {
        return $this->session->get('role_name');
    }

    /**
     * Get current user permissions
     * 
     * @return array
     */
    public function permissions()
    {
        return $this->session->get('permissions') ?? [];
    }

    /**
     * Check if user has specific role
     * 
     * @param string|array $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userRole = $this->session->get('role_name');

        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }

        return $userRole === $roles;
    }

    /**
     * Check if user has specific permission
     * 
     * @param string|array $permissions
     * @return bool
     */
    public function hasPermission($permissions)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userPermissions = $this->session->get('permissions') ?? [];

        if (is_array($permissions)) {
            // Check if user has ANY of the permissions
            return count(array_intersect($permissions, $userPermissions)) > 0;
        }

        return in_array($permissions, $userPermissions);
    }

    /**
     * Check if user has ALL specified permissions
     * 
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userPermissions = $this->session->get('permissions') ?? [];

        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    /**
     * Check if user is admin
     * 
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('Admin');
    }

    /**
     * Check if user is koordinator BK
     * 
     * @return bool
     */
    public function isKoordinator()
    {
        return $this->hasRole('Koordinator BK');
    }

    /**
     * Check if user is guru BK
     * 
     * @return bool
     */
    public function isGuruBK()
    {
        return $this->hasRole('Guru BK');
    }

    /**
     * Check if user is wali kelas
     * 
     * @return bool
     */
    public function isWaliKelas()
    {
        return $this->hasRole('Wali Kelas');
    }

    /**
     * Check if user is siswa
     * 
     * @return bool
     */
    public function isSiswa()
    {
        return $this->hasRole('Siswa');
    }

    /**
     * Check if user is orang tua
     * 
     * @return bool
     */
    public function isOrangTua()
    {
        return $this->hasRole('Orang Tua');
    }

    /**
     * Get redirect path based on role
     * 
     * @return string
     */
    public function getRedirectPath()
    {
        $role = $this->session->get('role_name');

        switch ($role) {
            case 'Admin':
                return '/admin/dashboard';
            case 'Koordinator BK':
                return '/koordinator/dashboard';
            case 'Guru BK':
                return '/counselor/dashboard';
            case 'Wali Kelas':
                return '/homeroom/dashboard';
            case 'Siswa':
                return '/student/dashboard';
            case 'Orang Tua':
                return '/parent/dashboard';
            default:
                return '/';
        }
    }

    /**
     * Refresh user session data
     * 
     * @return bool
     */
    public function refreshUserSession()
    {
        $userId = $this->session->get('user_id');

        if (!$userId) {
            return false;
        }

        $user = $this->userModel->getUserWithPermissions($userId);

        if (!$user) {
            $this->logout();
            return false;
        }

        // Update session data
        $this->session->set([
            'username'      => $user['username'],
            'email'         => $user['email'],
            'full_name'     => $user['full_name'],
            'role_id'       => $user['role_id'],
            'role_name'     => $user['role_name'],
            'permissions'   => $user['permissions'],
            'profile_photo' => $user['profile_photo'] ?? null,
        ]);

        return true;
    }

    /**
     * Log user activity
     * 
     * @param int $userId
     * @param string $action
     * @param string $description
     * @return void
     */
    protected function logActivity($userId, $action, $description)
    {
        $db = \Config\Database::connect();

        $data = [
            'user_id'     => $userId,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => \Config\Services::request()->getIPAddress(),
            'user_agent'  => \Config\Services::request()->getUserAgent()->getAgentString(),
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        // Check if activity_logs table exists
        if ($db->tableExists('activity_logs')) {
            $db->table('activity_logs')->insert($data);
        }
    }

    /**
     * Check access based on permission
     * Throw exception if access denied
     * 
     * @param string|array $permissions
     * @throws \CodeIgniter\Exceptions\PageNotFoundException
     * @return void
     */
    public function requirePermission($permissions)
    {
        if (!$this->hasPermission($permissions)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Access denied. You do not have permission to access this resource.');
        }
    }

    /**
     * Check access based on role
     * Throw exception if access denied
     * 
     * @param string|array $roles
     * @throws \CodeIgniter\Exceptions\PageNotFoundException
     * @return void
     */
    public function requireRole($roles)
    {
        if (!$this->hasRole($roles)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Access denied. This page is restricted to specific roles.');
        }
    }

    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public function generateCSRFToken()
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        return $token;
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public function validateCSRFToken($token)
    {
        $sessionToken = $this->session->get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }
}
