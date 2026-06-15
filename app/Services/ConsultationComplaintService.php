<?php

/**
 * File: app/Services/ConsultationComplaintService.php
 * Fitur: Konsultasi dan Pengaduan.
 * Peran/izin: Pelapor membuat/memantau aduan sendiri; Koordinator BK dan
 * Guru BK meninjau, mengubah status, dan menugaskan tindak lanjut.
 * Berhubungan dengan: ConsultationComplaintModel, users, students, classes.
 */

namespace App\Services;

use App\Models\ConsultationComplaintModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

class ConsultationComplaintService
{
    private BaseConnection $db;
    private ConsultationComplaintModel $model;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->model = new ConsultationComplaintModel();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function list(string $role, int $userId, array $filters = []): array
    {
        $builder = $this->baseBuilder();
        $this->applyRoleScope($builder, $role, $userId);

        if (! empty($filters['status'])) {
            $builder->where('cc.status', $filters['status']);
        }
        if (! empty($filters['request_type'])) {
            $builder->where('cc.request_type', $filters['request_type']);
        }
        if (! empty($filters['priority'])) {
            $builder->where('cc.priority', $filters['priority']);
        }
        if (! empty($filters['q'])) {
            $builder->groupStart()
                ->like('cc.title', (string) $filters['q'])
                ->orLike('cc.description', (string) $filters['q'])
                ->orLike('su.full_name', (string) $filters['q'])
                ->groupEnd();
        }

        return $builder
            ->orderBy('cc.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function find(int $id, string $role, int $userId): ?array
    {
        $builder = $this->baseBuilder()->where('cc.id', $id);
        $this->applyRoleScope($builder, $role, $userId);

        return $builder->get()->getRowArray() ?: null;
    }

    public function create(array $post, string $role, int $userId): int
    {
        $id = (int) $this->model->insert($this->payload($post, $role, $userId), true);
        return $id;
    }

    public function update(int $id, array $post): bool
    {
        return (bool) $this->model->update($id, $this->payload($post, '', 0, false));
    }

    /**
     * Soft delete hanya untuk laporan milik sendiri (reporter_user_id = userId).
     * Mencatat deleted_by agar bisa dipulihkan lewat Tempat Sampah.
     *
     * @return array{success:bool,message:string}
     */
    public function deleteOwn(int $id, int $userId): array
    {
        $row = $this->db->table('consultation_complaints')
            ->select('id, reporter_user_id')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if (! $row) {
            return ['success' => false, 'message' => 'Data tidak ditemukan.'];
        }

        if ((int) $row['reporter_user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Anda hanya dapat menghapus laporan yang Anda buat sendiri.'];
        }

        $ok = $this->db->table('consultation_complaints')
            ->where('id', $id)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $userId,
            ]);

        return [
            'success' => (bool) $ok,
            'message' => $ok ? 'Laporan dipindahkan ke Tempat Sampah.' : 'Gagal menghapus laporan.',
        ];
    }

    public function review(int $id, array $post, int $userId): bool
    {
        $status = (string) ($post['status'] ?? 'Ditinjau');
        $payload = [
            'status' => $status,
            'priority' => $post['priority'] ?? null,
            'assigned_to_user_id' => $this->nullableInt($post['assigned_to_user_id'] ?? null),
            'handled_by' => $userId,
            'handled_at' => date('Y-m-d H:i:s'),
            'closed_at' => in_array($status, ['Selesai', 'Ditolak', 'Diarsipkan'], true) ? date('Y-m-d H:i:s') : null,
            'privacy_level' => $post['privacy_level'] ?? null,
            'visible_to_homeroom' => ! empty($post['visible_to_homeroom']) ? 1 : 0,
        ];

        return (bool) $this->model->update($id, array_filter($payload, static fn($value) => $value !== null));
    }

    /**
     * @return array<string,mixed>
     */
    public function formOptions(string $role, int $userId): array
    {
        return [
            'students' => $this->studentsForRole($role, $userId),
            'counselors' => $this->db->table('users')
                ->select('id, full_name')
                ->whereIn('role_id', [2, 3])
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('full_name', 'ASC')
                ->get()
                ->getResultArray(),
        ];
    }

    private function baseBuilder(): BaseBuilder
    {
        return $this->db->table('consultation_complaints cc')
            ->select(
                'cc.*, ru.full_name AS reporter_name, su.full_name AS student_name, s.nisn,' .
                'c.class_name, au.full_name AS assigned_to_name, hu.full_name AS handled_by_name'
            )
            ->join('users ru', 'ru.id = cc.reporter_user_id AND ru.deleted_at IS NULL', 'left')
            ->join('students s', 's.id = cc.subject_student_id AND s.deleted_at IS NULL', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->join('users au', 'au.id = cc.assigned_to_user_id AND au.deleted_at IS NULL', 'left')
            ->join('users hu', 'hu.id = cc.handled_by AND hu.deleted_at IS NULL', 'left')
            ->where('cc.deleted_at', null);
    }

    private function applyRoleScope(BaseBuilder $builder, string $role, int $userId): void
    {
        if (in_array($role, ['admin', 'koordinator-bk', 'guru-bk'], true)) {
            return;
        }

        if ($role === 'wali-kelas') {
            $builder->groupStart()
                ->where('cc.reporter_user_id', $userId)
                ->orWhere('c.homeroom_teacher_id', $userId)
                ->orWhere('cc.visible_to_homeroom', 1)
                ->groupEnd();
            return;
        }

        if ($role === 'siswa') {
            $builder->groupStart()
                ->where('cc.reporter_user_id', $userId)
                ->orWhere('s.user_id', $userId)
                ->groupEnd();
            return;
        }

        if ($role === 'orang-tua') {
            $builder->groupStart()
                ->where('cc.reporter_user_id', $userId)
                ->orWhere('s.parent_id', $userId)
                ->groupEnd();
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $post, string $role, int $userId, bool $insert = true): array
    {
        $payload = [
            'subject_student_id' => $this->nullableInt($post['subject_student_id'] ?? $post['student_id'] ?? null),
            'subject_other_name' => trim((string) ($post['subject_other_name'] ?? '')) ?: null,
            'request_type' => $post['request_type'] ?? 'Konsultasi',
            'category' => trim((string) ($post['category'] ?? '')) ?: null,
            'title' => trim((string) ($post['title'] ?? 'Konsultasi BK')),
            'description' => trim((string) ($post['description'] ?? '')),
            'occurred_at' => $this->normalizeDateTime($post['occurred_at'] ?? null, $post['occurred_date'] ?? null, $post['occurred_time'] ?? null),
            'location' => trim((string) ($post['location'] ?? '')) ?: null,
            'witness' => trim((string) ($post['witness'] ?? '')) ?: null,
            'priority' => $post['priority'] ?? 'Sedang',
            'status' => $post['status'] ?? 'Diajukan',
            'privacy_level' => $post['privacy_level'] ?? 'Rahasia BK',
            'visible_to_homeroom' => ! empty($post['visible_to_homeroom']) ? 1 : 0,
            'assigned_to_user_id' => $this->nullableInt($post['assigned_to_user_id'] ?? null),
        ];

        if ($insert) {
            $payload['reporter_type'] = $this->reporterType($role);
            $payload['reporter_user_id'] = $userId;
        }

        return $payload;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function studentsForRole(string $role, int $userId): array
    {
        $builder = $this->db->table('students s')
            ->select('s.id, s.nisn, u.full_name, c.class_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.deleted_at', null)
            ->where('s.status', 'Aktif');

        if ($role === 'wali-kelas') {
            $builder->where('c.homeroom_teacher_id', $userId);
        } elseif ($role === 'siswa') {
            $builder->where('s.user_id', $userId);
        } elseif ($role === 'orang-tua') {
            $builder->where('s.parent_id', $userId);
        }

        return $builder->orderBy('u.full_name', 'ASC')->get()->getResultArray();
    }

    private function reporterType(string $role): string
    {
        return match ($role) {
            'siswa' => 'student',
            'orang-tua' => 'parent',
            'wali-kelas' => 'homeroom',
            'guru-bk' => 'counselor',
            'koordinator-bk' => 'coordinator',
            default => 'student',
        };
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
