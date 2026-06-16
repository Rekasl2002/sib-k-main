<?php

/**
 * File: app/Services/BkServiceService.php
 * Fitur: Bimbingan, Konseling, Kolaborasi Orang Tua, Kunjungan Rumah,
 * Konferensi Kasus, Dashboard, dan Laporan BK.
 * Peran/izin: Koordinator BK/Guru BK mengelola layanan; Wali Kelas, Siswa,
 * dan Orang Tua membaca data sesuai cakupan relasi.
 * Berhubungan dengan: BkServiceRecordModel, session_participants,
 * session_notes, consultation_complaints, bk_assignments.
 */

namespace App\Services;

use App\Models\BkServiceRecordModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

class BkServiceService
{
    private BaseConnection $db;
    private BkServiceRecordModel $recordModel;

    /** @var array<string,array<string,mixed>> */
    private array $detailMap = [
        'Bimbingan' => [
            'table' => 'guidances',
            'fields' => ['guidance_type', 'material_topic', 'summary'],
            'defaults' => ['guidance_type' => 'Klasikal'],
        ],
        'Konseling' => [
            'table' => 'counseling_sessions',
            'fields' => [
                'counseling_type', 'problem_description', 'session_summary',
                'follow_up_plan', 'privacy_level', 'follow_up_status',
                'is_confidential', 'student_id', 'counselor_id', 'class_id',
                'session_type', 'session_date', 'session_time', 'location',
                'topic', 'status', 'duration_minutes',
            ],
            'defaults' => ['counseling_type' => 'Individu', 'is_confidential' => 1],
        ],
        'Kolaborasi Orang Tua' => [
            'table' => 'parent_collaborations',
            'fields' => ['parent_name', 'topic', 'summary', 'follow_up'],
            'defaults' => [],
        ],
        'Kunjungan Rumah' => [
            'table' => 'home_visits',
            'fields' => ['address_snapshot', 'problem_topic', 'visit_result', 'follow_up'],
            'defaults' => [],
        ],
        'Konferensi Kasus' => [
            'table' => 'case_conferences',
            'fields' => ['chronology', 'discussion_summary', 'decision_summary', 'follow_up_plan'],
            'defaults' => [],
        ],
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->recordModel = new BkServiceRecordModel();
    }

    /**
     * @return list<string>
     */
    public function serviceTypes(): array
    {
        return array_keys($this->detailMap);
    }

    /**
     * @return array<string,mixed>
     */
    public function meta(string $serviceType): array
    {
        $labels = [
            'Bimbingan' => ['slug' => 'guidance', 'title' => 'Bimbingan', 'icon' => 'mdi mdi-account-group-outline'],
            'Konseling' => ['slug' => 'counseling', 'title' => 'Konseling', 'icon' => 'mdi mdi-account-heart-outline'],
            'Kolaborasi Orang Tua' => ['slug' => 'parent-collaboration', 'title' => 'Kolaborasi Orang Tua', 'icon' => 'mdi mdi-account-child-outline'],
            'Kunjungan Rumah' => ['slug' => 'home-visits', 'title' => 'Kunjungan Rumah', 'icon' => 'mdi mdi-home-heart'],
            'Konferensi Kasus' => ['slug' => 'case-conferences', 'title' => 'Konferensi Kasus', 'icon' => 'mdi mdi-account-multiple-check-outline'],
        ];

        return $labels[$serviceType] ?? ['slug' => 'bk-services', 'title' => $serviceType, 'icon' => 'mdi mdi-clipboard-text-outline'];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function list(string $serviceType, string $role, int $userId, array $filters = []): array
    {
        $builder = $this->baseListBuilder($serviceType);
        $this->applyRoleScope($builder, $role, $userId);
        $this->applyFilters($builder, $filters);

        return $builder
            ->groupBy('bsr.id')
            ->orderBy('COALESCE(bsr.scheduled_at, bsr.held_at, bsr.created_at)', 'DESC', false)
            ->get()
            ->getResultArray();
    }

    public function find(int $id, string $role, int $userId): ?array
    {
        $builder = $this->baseListBuilder(null)
            ->where('bsr.id', $id);
        $this->applyRoleScope($builder, $role, $userId);

        $record = $builder->groupBy('bsr.id')->get()->getRowArray();
        if (! $record) {
            return null;
        }

        $record['detail'] = $this->detailFor((int) $record['id'], (string) $record['service_type']);
        $record['participants'] = $this->participantsFor((int) $record['id']);
        $record['notes'] = $this->notesFor((int) $record['id'], $role);

        return $record;
    }

    public function create(string $serviceType, array $post, int $userId): int
    {
        $this->db->transStart();

        $recordId = (int) $this->recordModel->insert($this->servicePayload($serviceType, $post, $userId), true);
        $this->upsertDetail($recordId, $serviceType, $post);
        $this->saveParticipants($recordId, $post);
        $this->createInitialNote($recordId, $post, $userId);

        $this->db->transComplete();

        return $recordId;
    }

    public function update(int $id, string $serviceType, array $post, int $userId): bool
    {
        $this->db->transStart();

        $this->recordModel->update($id, $this->servicePayload($serviceType, $post, $userId, false));
        $this->upsertDetail($id, $serviceType, $post);
        if (isset($post['replace_participants'])) {
            $this->db->table('session_participants')->where('bk_service_record_id', $id)->delete();
            $this->saveParticipants($id, $post);
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->recordModel->delete($id);
    }

    public function addNote(int $recordId, array $post, int $userId): bool
    {
        return $this->insertFiltered('session_notes', [
            'bk_service_record_id' => $recordId,
            'session_id' => null,
            'created_by' => $userId,
            'note_type' => $post['note_type'] ?? 'Observasi',
            'note_content' => trim((string) ($post['note_content'] ?? '')),
            'is_important' => ! empty($post['is_important']) ? 1 : 0,
            'is_confidential' => ! empty($post['is_confidential']) ? 1 : 0,
            'visibility_level' => $post['visibility_level'] ?? 'Internal BK',
            'follow_up_status' => $post['follow_up_status'] ?? null,
            'assigned_to_user_id' => $this->nullableInt($post['assigned_to_user_id'] ?? null),
            'due_date' => $post['due_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateParticipant(int $participantId, array $post): bool
    {
        return (bool) $this->db->table('session_participants')
            ->where('id', $participantId)
            ->update($this->filterFields('session_participants', [
                'attendance_status' => $post['attendance_status'] ?? 'Hadir',
                'invitation_status' => $post['invitation_status'] ?? 'Konfirmasi',
                'participant_note' => $post['participant_note'] ?? null,
                'participation_note' => $post['participant_note'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
    }

    /**
     * Daftar id pengguna (Siswa & Orang Tua) yang menjadi peserta/undangan suatu
     * layanan, untuk pemberitahuan JADWAL (ringkasan aman, tanpa detail catatan).
     *
     * @return list<int>
     */
    public function notifiableUserIds(int $recordId): array
    {
        $ids = [];

        // Siswa yang diundang langsung + orang tuanya.
        $studentRows = $this->db->table('session_participants sp')
            ->select('s.user_id, s.parent_id')
            ->join('students s', 's.id = sp.participant_student_id', 'inner')
            ->where('sp.bk_service_record_id', $recordId)
            ->where('sp.participant_type', 'student')
            ->where('sp.deleted_at', null)
            ->get()->getResultArray();

        // Siswa anggota kelas yang diundang + orang tuanya.
        $classRows = $this->db->table('session_participants sp')
            ->select('s.user_id, s.parent_id')
            ->join('students s', 's.class_id = sp.participant_class_id AND s.deleted_at IS NULL', 'inner')
            ->where('sp.bk_service_record_id', $recordId)
            ->where('sp.participant_type', 'class')
            ->where('sp.deleted_at', null)
            ->get()->getResultArray();

        foreach (array_merge($studentRows, $classRows) as $r) {
            if (! empty($r['user_id'])) {
                $ids[] = (int) $r['user_id'];
            }
            if (! empty($r['parent_id'])) {
                $ids[] = (int) $r['parent_id'];
            }
        }

        // Peserta yang dicatat langsung sebagai user/orang tua (mis. Wali Kelas/Orang Tua).
        $userRows = $this->db->table('session_participants')
            ->select('participant_user_id, participant_parent_id')
            ->where('bk_service_record_id', $recordId)
            ->where('deleted_at', null)
            ->get()->getResultArray();
        foreach ($userRows as $r) {
            if (! empty($r['participant_user_id'])) {
                $ids[] = (int) $r['participant_user_id'];
            }
            if (! empty($r['participant_parent_id'])) {
                $ids[] = (int) $r['participant_parent_id'];
            }
        }

        return array_values(array_unique(array_filter($ids, static fn($v) => (int) $v > 0)));
    }

    /**
     * @return array<string,mixed>
     */
    public function formOptions(string $role, int $userId): array
    {
        return [
            'students' => $this->studentsForRole($role, $userId),
            'classes' => $this->classesForRole($role, $userId),
            'counselors' => $this->usersByRoleIds([2, 3]),
            'assignments' => $this->assignmentsForUser($role, $userId),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function dashboardSummary(string $role, int $userId): array
    {
        $summary = [
            'total_services' => 0,
            'scheduled' => 0,
            'need_follow_up' => 0,
            'complaints_open' => 0,
            'assignments_open' => 0,
            'by_type' => [],
        ];

        foreach ($this->serviceTypes() as $type) {
            $rows = $this->list($type, $role, $userId);
            $summary['by_type'][$type] = count($rows);
            foreach ($rows as $row) {
                $summary['total_services']++;
                if (($row['status'] ?? '') === 'Dijadwalkan') {
                    $summary['scheduled']++;
                }
                if (($row['status'] ?? '') === 'Perlu Tindak Lanjut') {
                    $summary['need_follow_up']++;
                }
            }
        }

        $summary['complaints_open'] = (int) $this->db->table('consultation_complaints')
            ->whereIn('status', ['Diajukan', 'Ditinjau', 'Diterima', 'Dijadwalkan'])
            ->countAllResults();

        $summary['assignments_open'] = (int) $this->db->table('bk_assignments')
            ->whereIn('status', ['Ditugaskan', 'Dibaca', 'Berjalan'])
            ->countAllResults();

        return $summary;
    }

    private function baseListBuilder(?string $serviceType): BaseBuilder
    {
        $builder = $this->db->table('bk_service_records bsr')
            ->select(
                'bsr.*, su.full_name AS student_name, s.nisn, s.parent_id, sc.homeroom_teacher_id AS student_homeroom_id,' .
                'c.class_name, c.grade_level, c.homeroom_teacher_id, cu.full_name AS counselor_name,' .
                'creator.full_name AS created_by_name'
            )
            ->join('students s', 's.id = bsr.target_student_id AND s.deleted_at IS NULL', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes sc', 'sc.id = s.class_id AND sc.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = bsr.target_class_id AND c.deleted_at IS NULL', 'left')
            ->join('users cu', 'cu.id = bsr.counselor_id AND cu.deleted_at IS NULL', 'left')
            ->join('users creator', 'creator.id = bsr.created_by AND creator.deleted_at IS NULL', 'left')
            ->join('session_participants sp_scope', 'sp_scope.bk_service_record_id = bsr.id AND sp_scope.deleted_at IS NULL', 'left')
            ->where('bsr.deleted_at', null);

        if ($serviceType !== null) {
            $builder->where('bsr.service_type', $serviceType);
        }

        return $builder;
    }

    private function applyRoleScope(BaseBuilder $builder, string $role, int $userId): void
    {
        if (in_array($role, ['admin', 'koordinator-bk'], true)) {
            return;
        }

        $userId = (int) $userId;

        if ($role === 'guru-bk') {
            $builder->groupStart()
                ->where('bsr.counselor_id', $userId)
                ->orWhere('bsr.created_by', $userId)
                ->orWhere('sp_scope.participant_user_id', $userId)
                ->groupEnd();
            return;
        }

        if ($role === 'wali-kelas') {
            $builder->groupStart()
                ->where('c.homeroom_teacher_id', $userId)
                ->orWhere('sc.homeroom_teacher_id', $userId)
                ->orWhere('sp_scope.participant_user_id', $userId)
                ->groupEnd();
            return;
        }

        if ($role === 'siswa') {
            $builder->where(
                '(s.user_id = ' . $userId . ' OR sp_scope.participant_user_id = ' . $userId
                . ' OR sp_scope.participant_student_id IN (SELECT id FROM students WHERE user_id = ' . $userId . '))',
                null,
                false
            );
            return;
        }

        if ($role === 'orang-tua') {
            $builder->where(
                '(s.parent_id = ' . $userId . ' OR sp_scope.participant_parent_id = ' . $userId
                . ' OR sp_scope.participant_user_id = ' . $userId . ')',
                null,
                false
            );
        }
    }

    private function applyFilters(BaseBuilder $builder, array $filters): void
    {
        if (! empty($filters['status'])) {
            $builder->where('bsr.status', $filters['status']);
        }
        if (! empty($filters['class_id'])) {
            $builder->groupStart()
                ->where('bsr.target_class_id', (int) $filters['class_id'])
                ->orWhere('s.class_id', (int) $filters['class_id'])
                ->groupEnd();
        }
        if (! empty($filters['student_id'])) {
            $builder->where('bsr.target_student_id', (int) $filters['student_id']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('DATE(COALESCE(bsr.scheduled_at, bsr.held_at, bsr.created_at)) >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $builder->where('DATE(COALESCE(bsr.scheduled_at, bsr.held_at, bsr.created_at)) <=', $filters['date_to']);
        }
        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->groupStart()
                ->like('bsr.title', $q)
                ->orLike('su.full_name', $q)
                ->orLike('c.class_name', $q)
                ->orLike('cu.full_name', $q)
                ->groupEnd();
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function servicePayload(string $serviceType, array $post, int $userId, bool $insert = true): array
    {
        $scheduledAt = $this->normalizeDateTime($post['scheduled_at'] ?? null, $post['scheduled_date'] ?? null, $post['scheduled_time'] ?? null);
        $heldAt = $this->normalizeDateTime($post['held_at'] ?? null, $post['held_date'] ?? null, $post['held_time'] ?? null);

        $payload = [
            'service_type' => $serviceType,
            'title' => trim((string) ($post['title'] ?? $post['topic'] ?? $serviceType)),
            'target_student_id' => $this->nullableInt($post['target_student_id'] ?? $post['student_id'] ?? null),
            'target_class_id' => $this->nullableInt($post['target_class_id'] ?? $post['class_id'] ?? null),
            'counselor_id' => $this->nullableInt($post['counselor_id'] ?? null) ?: $userId,
            'assignment_id' => $this->nullableInt($post['assignment_id'] ?? null),
            'source_complaint_id' => $this->nullableInt($post['source_complaint_id'] ?? null),
            'scheduled_at' => $scheduledAt,
            'held_at' => $heldAt,
            'location' => trim((string) ($post['location'] ?? '')) ?: null,
            'status' => $post['status'] ?? 'Dijadwalkan',
            'duration_minutes' => $this->nullableInt($post['duration_minutes'] ?? null),
            'privacy_level' => $post['privacy_level'] ?? 'Rahasia BK',
        ];

        if ($insert) {
            $payload['created_by'] = $userId;
        }

        return $payload;
    }

    private function upsertDetail(int $recordId, string $serviceType, array $post): void
    {
        $config = $this->detailMap[$serviceType] ?? null;
        if (! $config || ! $this->db->tableExists($config['table'])) {
            return;
        }

        $table = (string) $config['table'];
        $payload = array_merge((array) ($config['defaults'] ?? []), [
            'bk_service_record_id' => $recordId,
        ]);

        foreach ((array) $config['fields'] as $field) {
            if (array_key_exists($field, $post)) {
                $payload[$field] = is_string($post[$field]) ? trim($post[$field]) : $post[$field];
            }
        }

        if ($serviceType === 'Bimbingan' && isset($post['topic'])) {
            $payload['material_topic'] = trim((string) $post['topic']);
        }
        if ($serviceType === 'Kolaborasi Orang Tua' && isset($post['parent_names'])) {
            $payload['parent_name'] = trim((string) $post['parent_names']);
        }
        if ($serviceType === 'Kunjungan Rumah' && isset($post['address'])) {
            $payload['address_snapshot'] = trim((string) $post['address']);
        }

        if ($serviceType === 'Konseling') {
            $record = $this->recordModel->find($recordId) ?: [];
            $payload += [
                'student_id' => $record['target_student_id'] ?? null,
                'class_id' => $record['target_class_id'] ?? null,
                'counselor_id' => $record['counselor_id'] ?? null,
                'session_type' => $post['counseling_type'] ?? 'Individu',
                'session_date' => substr((string) ($record['scheduled_at'] ?? date('Y-m-d')), 0, 10),
                'session_time' => substr((string) ($record['scheduled_at'] ?? '08:00:00'), 11, 8) ?: null,
                'location' => $record['location'] ?? null,
                'topic' => $record['title'] ?? null,
                'status' => $record['status'] ?? 'Dijadwalkan',
                'duration_minutes' => $record['duration_minutes'] ?? null,
            ];
        }

        $existing = $this->db->table($table)
            ->select('id')
            ->where('bk_service_record_id', $recordId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $payload = $this->filterFields($table, $payload + ['updated_at' => date('Y-m-d H:i:s')]);

        if ($existing) {
            $this->db->table($table)->where('id', (int) $existing['id'])->update($payload);
            return;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->insertFiltered($table, $payload);
    }

    private function createInitialNote(int $recordId, array $post, int $userId): void
    {
        $content = trim((string) ($post['initial_note'] ?? $post['summary'] ?? ''));
        if ($content === '') {
            return;
        }

        $this->addNote($recordId, [
            'note_type' => 'Observasi',
            'note_content' => $content,
            'is_confidential' => 1,
            'visibility_level' => 'Internal BK',
        ], $userId);
    }

    private function saveParticipants(int $recordId, array $post): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [];

        foreach (['target_student_id', 'student_id'] as $key) {
            $studentId = $this->nullableInt($post[$key] ?? null);
            if ($studentId) {
                $rows[] = [
                    'bk_service_record_id' => $recordId,
                    'participant_type' => 'student',
                    'participant_student_id' => $studentId,
                    'student_id' => $studentId,
                    'role_in_session' => 'Siswa terkait',
                    'attendance_status' => 'Hadir',
                    'invitation_status' => 'Konfirmasi',
                    'is_active' => 1,
                    'joined_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $classId = $this->nullableInt($post['target_class_id'] ?? $post['class_id'] ?? null);
        if ($classId) {
            $rows[] = [
                'bk_service_record_id' => $recordId,
                'participant_type' => 'class',
                'participant_class_id' => $classId,
                'role_in_session' => 'Kelas sasaran',
                'attendance_status' => 'Hadir',
                'invitation_status' => 'Konfirmasi',
                'is_active' => 1,
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $manualText = trim((string) ($post['manual_participants'] ?? $post['external_attendees'] ?? $post['parent_names'] ?? ''));
        foreach (preg_split('/\r\n|\r|\n|,/', $manualText) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$name, $role] = array_pad(array_map('trim', explode('-', $line, 2)), 2, 'Peserta');
            $rows[] = [
                'bk_service_record_id' => $recordId,
                'participant_type' => 'manual',
                'manual_name' => $name,
                'role_in_session' => $role ?: 'Peserta',
                'attendance_status' => 'Hadir',
                'invitation_status' => 'Konfirmasi',
                'is_active' => 1,
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($rows as $row) {
            $this->insertFiltered('session_participants', $row);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function detailFor(int $recordId, string $serviceType): array
    {
        $table = $this->detailMap[$serviceType]['table'] ?? '';
        if ($table === '' || ! $this->db->tableExists($table)) {
            return [];
        }

        return $this->db->table($table)
            ->where('bk_service_record_id', $recordId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray() ?: [];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function participantsFor(int $recordId): array
    {
        if (! $this->db->tableExists('session_participants')) {
            return [];
        }

        return $this->db->table('session_participants sp')
            ->select(
                'sp.*, su.full_name AS participant_student_name, uu.full_name AS participant_user_name,' .
                'pu.full_name AS participant_parent_name, c.class_name AS participant_class_name'
            )
            ->join('students ps', 'ps.id = sp.participant_student_id', 'left')
            ->join('users su', 'su.id = ps.user_id', 'left')
            ->join('users uu', 'uu.id = sp.participant_user_id', 'left')
            ->join('users pu', 'pu.id = sp.participant_parent_id', 'left')
            ->join('classes c', 'c.id = sp.participant_class_id', 'left')
            ->where('sp.bk_service_record_id', $recordId)
            ->where('sp.deleted_at', null)
            ->orderBy('sp.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function notesFor(int $recordId, string $role): array
    {
        if (! $this->db->tableExists('session_notes')) {
            return [];
        }

        $builder = $this->db->table('session_notes sn')
            ->select('sn.*, u.full_name AS author_name')
            ->join('users u', 'u.id = sn.created_by', 'left')
            ->where('sn.bk_service_record_id', $recordId)
            ->where('sn.deleted_at', null);

        if (! in_array($role, ['admin', 'koordinator-bk', 'guru-bk'], true)) {
            $builder->groupStart()
                ->where('sn.is_confidential', 0)
                ->orWhereIn('sn.visibility_level', ['Ringkasan Wali Kelas', 'Publik Terbatas'])
                ->groupEnd();
        }

        return $builder->orderBy('sn.created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function studentsForRole(string $role, int $userId): array
    {
        $builder = $this->db->table('students s')
            ->select('s.id, s.nisn, u.full_name, c.class_name, s.parent_id')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.deleted_at', null)
            ->where('s.status', 'Aktif');

        if ($role === 'guru-bk') {
            $builder->where('c.counselor_id', $userId);
        } elseif ($role === 'wali-kelas') {
            $builder->where('c.homeroom_teacher_id', $userId);
        } elseif ($role === 'siswa') {
            $builder->where('s.user_id', $userId);
        } elseif ($role === 'orang-tua') {
            $builder->where('s.parent_id', $userId);
        }

        return $builder->orderBy('u.full_name', 'ASC')->get()->getResultArray();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function classesForRole(string $role, int $userId): array
    {
        $builder = $this->db->table('classes')
            ->select('id, class_name, grade_level, major')
            ->where('deleted_at', null)
            ->where('is_active', 1);

        if ($role === 'guru-bk') {
            $builder->where('counselor_id', $userId);
        } elseif ($role === 'wali-kelas') {
            $builder->where('homeroom_teacher_id', $userId);
        }

        return $builder->orderBy('grade_level', 'ASC')->orderBy('class_name', 'ASC')->get()->getResultArray();
    }

    /**
     * @param list<int> $roleIds
     * @return list<array<string,mixed>>
     */
    private function usersByRoleIds(array $roleIds): array
    {
        return $this->db->table('users')
            ->select('id, full_name, role_id')
            ->whereIn('role_id', $roleIds)
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('role_id', 'ASC')
            ->orderBy('full_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function assignmentsForUser(string $role, int $userId): array
    {
        if (! $this->db->tableExists('bk_assignments')) {
            return [];
        }

        $builder = $this->db->table('bk_assignments')
            ->select('id, title, assignment_type, status')
            ->where('deleted_at', null);

        if ($role === 'guru-bk') {
            $builder->where('assigned_to_user_id', $userId);
        }

        return $builder->orderBy('assigned_at', 'DESC')->get()->getResultArray();
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

    private function insertFiltered(string $table, array $payload): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        return (bool) $this->db->table($table)->insert($this->filterFields($table, $payload));
    }

    /**
     * @return array<string,mixed>
     */
    private function filterFields(string $table, array $payload): array
    {
        $fields = array_flip($this->db->getFieldNames($table));
        return array_intersect_key($payload, $fields);
    }
}
