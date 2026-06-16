<?php

/**
 * File: app/Controllers/Koordinator/AssignmentController.php
 * Fitur: Penugasan.
 * Peran/izin: Koordinator BK membuat tugas dan menentukan Guru BK/kelas binaan.
 * Berhubungan dengan: BaseBkAssignmentController, bk_assignments.
 */

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseBkAssignmentController;
use Config\Database;

class AssignmentController extends BaseBkAssignmentController
{
    protected string $roleKey = 'koordinator-bk';
    protected string $roleLabel = 'Koordinator BK';
    protected string $routePrefix = 'koordinator/assignments';
    protected string $viewPrefix = 'koordinator';
    protected bool $canManage = true;

    public function index()
    {
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'status' => trim((string) $this->request->getGet('status')),
            'assignment_type' => trim((string) $this->request->getGet('assignment_type')),
        ];

        return $this->render('index', [
            'title' => 'Penugasan',
            'rows' => $this->service->list($this->roleKey, $this->currentUserId(), $filters),
            'filters' => $filters,
            'counselors' => $this->counselorRows(),
        ]);
    }

    private function roleIdByName(string $roleName): int
    {
        $row = Database::connect()->table('roles')
            ->select('id')
            ->where('role_name', $roleName)
            ->get()
            ->getRowArray();

        return (int) ($row['id'] ?? 0);
    }

    private function counselorRows(): array
    {
        $db = Database::connect();
        $guruBkRoleId = $this->roleIdByName('Guru BK') ?: 3;

        return $db->table('users u')
            ->select('u.id, u.full_name, u.username, u.email, u.phone, u.is_active, COUNT(c.id) AS class_count')
            ->select('GROUP_CONCAT(c.class_name ORDER BY c.grade_level, c.class_name SEPARATOR ", ") AS class_names', false)
            ->join('classes c', 'c.counselor_id = u.id AND c.deleted_at IS NULL', 'left')
            ->where('u.role_id', $guruBkRoleId)
            ->where('u.deleted_at', null)
            ->groupBy('u.id')
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();
    }
}
