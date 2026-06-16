<?php

/**
 * File: app/Services/BkAssignmentService.php
 * Fitur: Penugasan Guru BK.
 * Peran/izin: Koordinator BK mengelola tugas dan kelas binaan; Guru BK melihat
 * serta memperbarui status tugas yang diterima.
 * Berhubungan dengan: BkAssignmentModel, BkAssignmentStatusHistoryModel,
 * classes, students, users.
 */

namespace App\Services;

use App\Models\BkAssignmentModel;
use App\Models\BkAssignmentStatusHistoryModel;
use CodeIgniter\Database\BaseConnection;

class BkAssignmentService
{
    private BaseConnection $db;
    private BkAssignmentModel $model;
    private BkAssignmentStatusHistoryModel $historyModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->model = new BkAssignmentModel();
        $this->historyModel = new BkAssignmentStatusHistoryModel();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function list(string $role, int $userId, array $filters = []): array
    {
        $builder = $this->db->table('bk_assignments ba')
            ->select(
                'ba.*, assigner.full_name AS assigned_by_name, assignee.full_name AS assigned_to_name,' .
                'c.class_name, su.full_name AS student_name, s.nisn'
            )
            ->join('users assigner', 'assigner.id = ba.assigned_by', 'left')
            ->join('users assignee', 'assignee.id = ba.assigned_to_user_id', 'left')
            ->join('classes c', 'c.id = ba.class_id', 'left')
            ->join('students s', 's.id = ba.student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->where('ba.deleted_at', null);

        if ($role === 'guru-bk') {
            $builder->where('ba.assigned_to_user_id', $userId);
        } elseif (! in_array($role, ['admin', 'koordinator-bk'], true)) {
            $builder->where('1 = 0', null, false);
        }

        if (! empty($filters['status'])) {
            $builder->where('ba.status', $filters['status']);
        }
        if (! empty($filters['assignment_type'])) {
            $builder->where('ba.assignment_type', $filters['assignment_type']);
        }
        if (! empty($filters['q'])) {
            $builder->groupStart()
                ->like('ba.title', (string) $filters['q'])
                ->orLike('ba.instruction', (string) $filters['q'])
                ->orLike('assignee.full_name', (string) $filters['q'])
                ->groupEnd();
        }

        return $builder->orderBy('ba.assigned_at', 'DESC')->get()->getResultArray();
    }

    public function find(int $id, string $role, int $userId): ?array
    {
        $rows = $this->list($role, $userId, []);
        $row = null;
        foreach ($rows as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $id) {
                $row = $candidate;
                break;
            }
        }

        if (! $row) {
            return null;
        }

        $row['histories'] = $this->db->table('bk_assignment_status_histories h')
            ->select('h.*, u.full_name AS changed_by_name')
            ->join('users u', 'u.id = h.changed_by', 'left')
            ->where('h.assignment_id', $id)
            ->where('h.deleted_at', null)
            ->orderBy('h.changed_at', 'DESC')
            ->get()
            ->getResultArray();

        return $row;
    }

    public function create(array $post, int $userId): int
    {
        $payload = $this->payload($post);
        $payload['assigned_by'] = $userId;
        $payload['assigned_at'] = $payload['assigned_at'] ?? date('Y-m-d H:i:s');

        $id = (int) $this->model->insert($payload, true);
        $this->recordHistory($id, (string) ($payload['status'] ?? 'Ditugaskan'), 'Tugas dibuat.', $userId);

        return $id;
    }

    public function update(int $id, array $post, int $userId): bool
    {
        $payload = $this->payload($post);
        $ok = (bool) $this->model->update($id, $payload);
        if ($ok && isset($payload['status'])) {
            $this->recordHistory($id, (string) $payload['status'], (string) ($post['history_note'] ?? 'Status diperbarui.'), $userId);
        }

        return $ok;
    }

    public function updateStatus(int $id, string $status, string $note, int $userId): bool
    {
        $payload = ['status' => $status];
        if ($status === 'Selesai') {
            $payload['completed_at'] = date('Y-m-d H:i:s');
        }

        $ok = (bool) $this->model->update($id, $payload);
        if ($ok) {
            $this->recordHistory($id, $status, $note, $userId);
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->delete($id);
    }

    /**
     * @return array<string,mixed>
     */
    public function formOptions(): array
    {
        return [
            'counselors' => $this->db->table('users')
                ->select('id, full_name')
                ->where('role_id', 3)
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('full_name', 'ASC')
                ->get()
                ->getResultArray(),
            'classes' => $this->db->table('classes')
                ->select('id, class_name, grade_level')
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('grade_level', 'ASC')
                ->orderBy('class_name', 'ASC')
                ->get()
                ->getResultArray(),
            'students' => $this->db->table('students s')
                ->select('s.id, u.full_name, s.nisn, c.class_name')
                ->join('users u', 'u.id = s.user_id', 'left')
                ->join('classes c', 'c.id = s.class_id', 'left')
                ->where('s.status', 'Aktif')
                ->where('s.deleted_at', null)
                ->orderBy('u.full_name', 'ASC')
                ->get()
                ->getResultArray(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $post): array
    {
        return [
            'assignment_type' => $post['assignment_type'] ?? 'Tugas Layanan',
            'title' => trim((string) ($post['title'] ?? 'Tugas BK')),
            'instruction' => trim((string) ($post['instruction'] ?? '')),
            'assigned_to_user_id' => $this->nullableInt($post['assigned_to_user_id'] ?? null),
            'class_id' => $this->nullableInt($post['class_id'] ?? null),
            'student_id' => $this->nullableInt($post['student_id'] ?? null),
            'source_type' => trim((string) ($post['source_type'] ?? '')) ?: null,
            'source_id' => $this->nullableInt($post['source_id'] ?? null),
            'priority' => $post['priority'] ?? 'Sedang',
            'status' => $post['status'] ?? 'Ditugaskan',
            'due_at' => $this->normalizeDateTime($post['due_at'] ?? null, $post['due_date'] ?? null, $post['due_time'] ?? null),
            'assigned_at' => $this->normalizeDateTime($post['assigned_at'] ?? null, null, null),
        ];
    }

    private function recordHistory(int $assignmentId, string $status, string $note, int $userId): void
    {
        $this->historyModel->insert([
            'assignment_id' => $assignmentId,
            'status' => $status,
            'note' => $note,
            'changed_by' => $userId,
            'changed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function normalizeDateTime($direct, $date, $time): ?string
    {
        $direct = trim((string) ($direct ?? ''));
        if ($direct !== '') {
            return str_replace('T', ' ', $direct) . (strlen($direct) === 16 ? ':00' : '');
        }

        $date = trim((string) ($date ?? ''));
        if ($date === '') {
            return null;
        }

        $time = trim((string) ($time ?? '08:00'));
        return $date . ' ' . (strlen($time) === 5 ? $time . ':00' : $time);
    }

    private function nullableInt($value): ?int
    {
        $value = (int) ($value ?? 0);
        return $value > 0 ? $value : null;
    }
}
