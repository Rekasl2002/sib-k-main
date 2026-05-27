<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SimulationAccessModel;
use Config\Database;

class SimulationAccessController extends BaseController
{
    private SimulationAccessModel $accessModel;

    public function __construct()
    {
        helper(['auth', 'permission', 'simulation_access']);
        $this->accessModel = new SimulationAccessModel();
    }

    public function index()
    {
        require_role(['Admin', 'Administrator']);

        $this->accessModel->ensureTable();

        $search = trim((string) ($this->request->getGet('search') ?? ''));
        $status = trim((string) ($this->request->getGet('status') ?? ''));

        $builder = Database::connect()->table('users u')
            ->select('u.id, u.role_id, u.username, u.email, u.full_name, u.is_active, u.last_login, r.role_name, sag.is_active AS simulation_access, sag.granted_at, sag.revoked_at, sag.notes, grantor.full_name AS granted_by_name')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join('simulation_access_grants sag', 'sag.user_id = u.id', 'left')
            ->join('users grantor', 'grantor.id = sag.granted_by', 'left')
            ->where('u.deleted_at', null);

        if ($search !== '') {
            $builder->groupStart()
                ->like('u.full_name', $search)
                ->orLike('u.username', $search)
                ->orLike('u.email', $search)
                ->orLike('r.role_name', $search)
                ->groupEnd();
        }

        if ($status === 'granted') {
            $builder->where('sag.is_active', 1);
        } elseif ($status === 'not_granted') {
            $builder->groupStart()
                ->where('sag.is_active IS NULL', null, false)
                ->orWhere('sag.is_active', 0)
                ->groupEnd();
        }

        $users = $builder
            ->orderBy('r.id', 'ASC')
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        $stats = [
            'total_users' => count($users),
            'granted'     => 0,
            'automatic'   => 0,
        ];

        foreach ($users as $user) {
            if ($this->isAdminRow($user)) {
                $stats['automatic']++;
            } elseif ((int) ($user['simulation_access'] ?? 0) === 1) {
                $stats['granted']++;
            }
        }

        return view('admin/simulation_access/index', [
            'title'   => 'Akses Prototipe/Simulasi',
            'users'   => $users,
            'stats'   => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function grant()
    {
        require_role(['Admin', 'Administrator']);

        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        $notes  = trim((string) ($this->request->getPost('notes') ?? ''));
        $adminId = (int) (session('user_id') ?? 0);

        if ($userId <= 0) {
            return redirect()->back()->with('error', 'Pengguna tidak valid.');
        }

        $user = $this->findUser($userId);
        if (! $user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        if ($this->isAdminRow($user)) {
            return redirect()->back()->with('info', 'Admin sudah memiliki akses otomatis.');
        }

        $ok = $this->accessModel->grant($userId, $adminId, $notes !== '' ? $notes : null);

        return redirect()->back()->with($ok ? 'success' : 'error', $ok
            ? 'Akses simulasi berhasil diberikan.'
            : 'Akses simulasi gagal diberikan.');
    }

    public function revoke()
    {
        require_role(['Admin', 'Administrator']);

        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        $adminId = (int) (session('user_id') ?? 0);

        if ($userId <= 0) {
            return redirect()->back()->with('error', 'Pengguna tidak valid.');
        }

        $user = $this->findUser($userId);
        if ($user && $this->isAdminRow($user)) {
            return redirect()->back()->with('info', 'Akses otomatis admin tidak dapat dicabut.');
        }

        $ok = $this->accessModel->revoke($userId, $adminId);

        return redirect()->back()->with($ok ? 'success' : 'error', $ok
            ? 'Akses simulasi berhasil dicabut.'
            : 'Akses simulasi gagal dicabut.');
    }

    private function findUser(int $userId): ?array
    {
        $row = Database::connect()->table('users u')
            ->select('u.id, u.username, u.email, u.full_name, u.role_id, r.role_name')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->where('u.id', $userId)
            ->where('u.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function isAdminRow(array $user): bool
    {
        $roleId = (int) ($user['role_id'] ?? 0);
        $role   = strtolower(trim((string) ($user['role_name'] ?? '')));

        return $roleId === 1 || in_array($role, ['admin', 'administrator'], true);
    }
}
