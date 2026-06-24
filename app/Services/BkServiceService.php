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

        $rows = $builder
            ->groupBy('bsr.id')
            ->orderBy('COALESCE(bsr.scheduled_at, bsr.held_at, bsr.created_at)', 'DESC', false)
            ->get()
            ->getResultArray();

        // Kerahasiaan daftar: Siswa & Orang Tua hanya boleh melihat JADWAL
        // (tanggal–waktu–lokasi), tanpa topik/durasi. Judul disamarkan menjadi
        // label netral & durasi disembunyikan.
        if (in_array($role, ['siswa', 'orang-tua'], true)) {
            foreach ($rows as &$r) {
                $r['title'] = $this->safeScheduleTitle((string) ($r['service_type'] ?? ''));
                $r['duration_minutes'] = null;
            }
            unset($r);
        }

        return $rows;
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
        $record['notes'] = $this->notesFor((int) $record['id'], $role, ! empty($record['visible_to_homeroom']));

        $this->applyConfidentiality($record, $role);

        return $record;
    }

    /**
     * Kerahasiaan detail layanan BK di SISI SERVER (lihat CLAUDE.md §6):
     *  - Siswa & Orang Tua: JADWAL SAJA (tanggal–waktu–lokasi). Tidak boleh melihat
     *    topik/durasi/deskripsi/Detail Khusus/catatan. Judul disamarkan ke label
     *    netral, durasi & detail dikosongkan.
     *  - Wali Kelas: detail rinci (Detail Khusus) hanya bila Koordinator BK/Guru BK
     *    mengizinkan pada data tersebut (visible_to_homeroom = 1). Catatan sudah
     *    disaring di notesFor().
     */
    private function applyConfidentiality(array &$record, string $role): void
    {
        if (in_array($role, ['siswa', 'orang-tua'], true)) {
            $record['detail'] = [];
            $record['notes'] = [];
            $record['title'] = $this->safeScheduleTitle((string) ($record['service_type'] ?? ''));
            $record['duration_minutes'] = null;
            return;
        }

        if ($role === 'wali-kelas' && empty($record['visible_to_homeroom'])) {
            $record['detail'] = [];
        }
    }

    /**
     * Label netral pengganti judul/topik untuk peran read-only (Siswa/Orang Tua),
     * agar topik tidak bocor namun jenis kegiatan tetap dikenali.
     */
    private function safeScheduleTitle(string $serviceType): string
    {
        return $serviceType !== '' ? ('Kegiatan/Acara BK: ' . $serviceType) : 'Kegiatan/Acara BK';
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
        // Tambahkan peserta baru tanpa menghapus yang sudah ada (status kehadiran
        // yang sudah disetel tetap aman). Penghapusan peserta dilakukan terpisah.
        $this->saveParticipants($id, $post);
        // Catatan baru opsional dari form edit.
        if (trim((string) ($post['initial_note'] ?? '')) !== '') {
            $this->addNote($id, [
                'note_type' => 'Observasi',
                'note_content' => trim((string) $post['initial_note']),
                'visible_to_homeroom' => $post['visible_to_homeroom'] ?? 0,
            ], $userId);
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
        // Alih fungsi opsi "dirahasiakan": jika diizinkan, catatan boleh dilihat
        // Wali Kelas terkait (visibility 'Ringkasan Wali Kelas'); bawaan internal BK.
        $visibleToHomeroom = ! empty($post['visible_to_homeroom']);
        $visibility = $post['visibility_level'] ?? ($visibleToHomeroom ? 'Ringkasan Wali Kelas' : 'Internal BK');

        return $this->insertFiltered('session_notes', [
            'bk_service_record_id' => $recordId,
            'session_id' => null,
            'created_by' => $userId,
            'note_type' => $post['note_type'] ?? 'Observasi',
            'note_content' => trim((string) ($post['note_content'] ?? '')),
            'is_important' => ! empty($post['is_important']) ? 1 : 0,
            'is_confidential' => $visibleToHomeroom ? 0 : 1,
            'visibility_level' => $visibility,
            'follow_up_status' => $post['follow_up_status'] ?? null,
            'assigned_to_user_id' => $this->nullableInt($post['assigned_to_user_id'] ?? null),
            'due_date' => $post['due_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Hapus (soft delete) catatan. Hanya pembuat catatan yang boleh menghapus
     * catatannya sendiri (peran lain tidak bisa).
     */
    public function deleteNote(int $noteId, int $recordId, int $userId): bool
    {
        $note = $this->db->table('session_notes')
            ->where('id', $noteId)
            ->where('bk_service_record_id', $recordId)
            ->where('deleted_at', null)
            ->get()->getRowArray();
        if (! $note || (int) ($note['created_by'] ?? 0) !== $userId) {
            return false;
        }

        $update = ['deleted_at' => date('Y-m-d H:i:s')];
        if ($this->db->fieldExists('deleted_by', 'session_notes')) {
            $update['deleted_by'] = $userId;
        }

        return (bool) $this->db->table('session_notes')->where('id', $noteId)->update($update);
    }

    public function updateParticipant(int $participantId, array $post): bool
    {
        return (bool) $this->db->table('session_participants')
            ->where('id', $participantId)
            ->update($this->filterFields('session_participants', [
                'attendance_status' => $post['attendance_status'] ?? 'Hadir',
                'invitation_status' => $post['invitation_status'] ?? 'Konfirmasi',
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
            // Penanggung Jawab khusus Konferensi Kasus: HANYA Koordinator BK (role 2).
            'coordinators' => $this->usersByRoleIds([2]),
            'parents' => $this->usersByRoleIds([6]),
            'homeroom_teachers' => $this->usersByRoleIds([4]),
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

    /**
     * Agregasi JADWAL kegiatan/acara BK per jenis layanan untuk peran read-only
     * (Siswa, Orang Tua, Wali Kelas). Hanya jadwal (tanggal–waktu–lokasi), tanpa
     * detail catatan. Dipakai halaman terpadu "Jadwal Kegiatan/Acara BK".
     *
     * @param string $when 'upcoming' (akan datang) atau 'past' (riwayat).
     * @return array<string,array<int,array<string,mixed>>> [serviceType => rows]
     */
    public function scheduleByType(string $role, int $userId, string $when = 'upcoming'): array
    {
        $today = date('Y-m-d');
        $result = [];

        foreach ($this->serviceTypes() as $type) {
            $rows = $this->list($type, $role, $userId);
            $bucket = [];

            foreach ($rows as $row) {
                $raw = ! empty($row['scheduled_at']) ? $row['scheduled_at'] : ($row['held_at'] ?? null);
                $date = $raw ? substr((string) $raw, 0, 10) : null;
                $status = (string) ($row['status'] ?? '');
                $finished = in_array($status, ['Selesai', 'Dibatalkan'], true);

                // "Akan datang": punya tanggal >= hari ini dan belum selesai/dibatalkan.
                $isUpcoming = ($date !== null && $date >= $today && ! $finished);

                if ($when === 'upcoming' ? $isUpcoming : ! $isUpcoming) {
                    $bucket[] = $row;
                }
            }

            usort($bucket, static function ($a, $b) use ($when) {
                $da = $a['scheduled_at'] ?? $a['held_at'] ?? $a['created_at'] ?? '';
                $db = $b['scheduled_at'] ?? $b['held_at'] ?? $b['created_at'] ?? '';
                return $when === 'upcoming'
                    ? strcmp((string) $da, (string) $db)
                    : strcmp((string) $db, (string) $da);
            });

            $result[$type] = $bucket;
        }

        return $result;
    }

    /**
     * Hitung total entri jadwal "akan datang" (semua jenis layanan) untuk ringkasan
     * dashboard peran read-only.
     */
    public function upcomingScheduleCount(string $role, int $userId): int
    {
        $total = 0;
        foreach ($this->scheduleByType($role, $userId, 'upcoming') as $rows) {
            $total += count($rows);
        }
        return $total;
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

        // Subjek utama (target) diturunkan dari pilihan PERTAMA bila tidak dikirim
        // eksplisit. Sejak Perbaikan Kedua, Siswa/Kelas Sasaran dipilih sebagai
        // DAFTAR (tombol "+") dan dikirim lewat participant_student_ids[] /
        // participant_class_ids[]; pilihan pertama menjadi target representatif.
        $targetStudentId = $this->nullableInt($post['target_student_id'] ?? $post['student_id'] ?? null)
            ?? $this->firstPositiveInt($post['participant_student_ids'] ?? []);
        $targetClassId = $this->nullableInt($post['target_class_id'] ?? $post['class_id'] ?? null)
            ?? $this->firstPositiveInt($post['participant_class_ids'] ?? []);

        // Penanggung Jawab/Guru BK. Konferensi Kasus BOLEH kosong bila yang mengisi
        // adalah Guru BK (penanggung jawab hanya Koordinator BK — ditetapkan kemudian).
        $counselorId = $this->nullableInt($post['counselor_id'] ?? null);
        if ($counselorId === null && $serviceType !== 'Konferensi Kasus') {
            $counselorId = $userId;
        }

        $payload = [
            'service_type' => $serviceType,
            'title' => trim((string) ($post['title'] ?? $post['topic'] ?? $serviceType)),
            'target_student_id' => $targetStudentId,
            'target_class_id' => $targetClassId,
            'counselor_id' => $counselorId,
            'assignment_id' => $this->nullableInt($post['assignment_id'] ?? null),
            'source_complaint_id' => $this->nullableInt($post['source_complaint_id'] ?? null),
            'scheduled_at' => $scheduledAt,
            'held_at' => $heldAt,
            'location' => trim((string) ($post['location'] ?? '')) ?: null,
            'status' => $post['status'] ?? 'Dijadwalkan',
            'duration_minutes' => $this->nullableInt($post['duration_minutes'] ?? null),
            'privacy_level' => $post['privacy_level'] ?? 'Rahasia BK',
            // Izin apakah catatan layanan BK boleh dilihat Wali Kelas terkait
            // (alih fungsi opsi "dirahasiakan" lama). Bawaan MATI.
            'visible_to_homeroom' => ! empty($post['visible_to_homeroom']) ? 1 : 0,
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
            'visible_to_homeroom' => $post['visible_to_homeroom'] ?? 0,
        ], $userId);
    }

    /**
     * Simpan peserta/undangan. AMAN untuk dipanggil ulang saat edit: peserta yang
     * sudah ada (dengan kunci sama) TIDAK diduplikasi sehingga status kehadiran
     * yang sudah disetel tidak hilang. Sumber peserta:
     *  - subjek utama (target_student_id / target_class_id)
     *  - Siswa data (participant_student_ids[]), Kelas data (participant_class_ids[])
     *  - Orang Tua data (participant_parent_ids[]), Wali Kelas data (participant_user_ids[])
     *  - Peserta Tambahan manual (manual_participants[] atau teks dipisah baris/koma)
     */
    private function saveParticipants(int $recordId, array $post): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->existingParticipantKeys($recordId);
        $rows = [];

        $base = [
            'bk_service_record_id' => $recordId,
            'attendance_status' => 'Hadir',
            'invitation_status' => 'Konfirmasi',
            'is_active' => 1,
            'joined_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $push = static function (string $key, array $row) use (&$rows, &$existing): void {
            if (isset($existing[$key])) {
                return;
            }
            $existing[$key] = true;
            $rows[] = $row;
        };

        // Siswa subjek utama + Siswa dari pilihan ganda.
        $studentIds = [];
        foreach (['target_student_id', 'student_id'] as $key) {
            $sid = $this->nullableInt($post[$key] ?? null);
            if ($sid) {
                $studentIds[] = $sid;
            }
        }
        foreach ((array) ($post['participant_student_ids'] ?? []) as $sid) {
            $sid = (int) $sid;
            if ($sid > 0) {
                $studentIds[] = $sid;
            }
        }
        foreach (array_unique($studentIds) as $sid) {
            $push('student:' . $sid, $base + [
                'participant_type' => 'student',
                'participant_student_id' => $sid,
                'student_id' => $sid,
                'role_in_session' => 'Siswa terkait',
            ]);
        }

        // Kelas subjek utama + Kelas dari pilihan ganda.
        $classIds = [];
        $mainClass = $this->nullableInt($post['target_class_id'] ?? $post['class_id'] ?? null);
        if ($mainClass) {
            $classIds[] = $mainClass;
        }
        foreach ((array) ($post['participant_class_ids'] ?? []) as $cid) {
            $cid = (int) $cid;
            if ($cid > 0) {
                $classIds[] = $cid;
            }
        }
        foreach (array_unique($classIds) as $cid) {
            $push('class:' . $cid, $base + [
                'participant_type' => 'class',
                'participant_class_id' => $cid,
                'role_in_session' => 'Kelas sasaran',
            ]);
        }

        // Orang Tua dari data.
        foreach ((array) ($post['participant_parent_ids'] ?? []) as $pid) {
            $pid = (int) $pid;
            if ($pid > 0) {
                $push('parent:' . $pid, $base + [
                    'participant_type' => 'parent',
                    'participant_parent_id' => $pid,
                    'role_in_session' => 'Orang Tua',
                ]);
            }
        }

        // Wali Kelas / pengguna lain dari data.
        foreach ((array) ($post['participant_user_ids'] ?? []) as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $push('user:' . $uid, $base + [
                    'participant_type' => 'user',
                    'participant_user_id' => $uid,
                    'role_in_session' => 'Wali Kelas/Petugas',
                ]);
            }
        }

        // Peserta Tambahan manual (array baris atau teks lama dipisah baris/koma).
        $manualLines = [];
        if (isset($post['manual_participants']) && is_array($post['manual_participants'])) {
            $manualLines = $post['manual_participants'];
        } else {
            $manualText = trim((string) ($post['manual_participants'] ?? $post['external_attendees'] ?? $post['parent_names'] ?? ''));
            $manualLines = preg_split('/\r\n|\r|\n|,/', $manualText) ?: [];
        }
        foreach ($manualLines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            [$name, $role] = array_pad(array_map('trim', explode('-', $line, 2)), 2, 'Peserta');
            if ($name === '') {
                continue;
            }
            // Manual selalu ditambah (tak ada kunci unik), namun hindari dobel persis.
            $push('manual:' . mb_strtolower($name) . ':' . mb_strtolower($role ?: 'peserta'), $base + [
                'participant_type' => 'manual',
                'manual_name' => $name,
                'role_in_session' => $role ?: 'Peserta',
            ]);
        }

        foreach ($rows as $row) {
            $this->insertFiltered('session_participants', $row);
        }
    }

    /**
     * Kunci peserta yang sudah tercatat (belum dihapus) agar tidak diduplikasi
     * saat form edit menyimpan ulang.
     *
     * @return array<string,bool>
     */
    private function existingParticipantKeys(int $recordId): array
    {
        $keys = [];
        if (! $this->db->tableExists('session_participants')) {
            return $keys;
        }

        $rows = $this->db->table('session_participants')
            ->select('participant_type, participant_student_id, participant_class_id, participant_parent_id, participant_user_id, manual_name, role_in_session')
            ->where('bk_service_record_id', $recordId)
            ->where('deleted_at', null)
            ->get()->getResultArray();

        foreach ($rows as $r) {
            switch ($r['participant_type']) {
                case 'student':
                    $keys['student:' . (int) $r['participant_student_id']] = true;
                    break;
                case 'class':
                    $keys['class:' . (int) $r['participant_class_id']] = true;
                    break;
                case 'parent':
                    $keys['parent:' . (int) $r['participant_parent_id']] = true;
                    break;
                case 'user':
                    $keys['user:' . (int) $r['participant_user_id']] = true;
                    break;
                case 'manual':
                    $keys['manual:' . mb_strtolower((string) $r['manual_name']) . ':' . mb_strtolower((string) ($r['role_in_session'] ?: 'peserta'))] = true;
                    break;
            }
        }

        return $keys;
    }

    /**
     * Hapus (soft delete) satu peserta/undangan dari sebuah layanan. Dipakai untuk
     * menghapus peserta tambahan di halaman edit.
     */
    public function deleteParticipant(int $participantId, int $recordId, int $userId): bool
    {
        $row = $this->db->table('session_participants')
            ->where('id', $participantId)
            ->where('bk_service_record_id', $recordId)
            ->where('deleted_at', null)
            ->get()->getRowArray();
        if (! $row) {
            return false;
        }

        $update = ['deleted_at' => date('Y-m-d H:i:s')];
        if ($this->db->fieldExists('deleted_by', 'session_participants')) {
            $update['deleted_by'] = $userId;
        }

        return (bool) $this->db->table('session_participants')->where('id', $participantId)->update($update);
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
    private function notesFor(int $recordId, string $role, bool $visibleToHomeroom = false): array
    {
        if (! $this->db->tableExists('session_notes')) {
            return [];
        }

        // Siswa & Orang Tua TIDAK pernah melihat detail/catatan layanan BK
        // (hanya jadwal). Kerahasiaan dijaga di sisi server.
        if (in_array($role, ['siswa', 'orang-tua'], true)) {
            return [];
        }

        // Wali Kelas hanya boleh melihat catatan bila Koordinator BK/Guru BK
        // mengizinkan pada data tersebut (visible_to_homeroom = 1). Bawaan mati.
        if ($role === 'wali-kelas' && ! $visibleToHomeroom) {
            return [];
        }

        $builder = $this->db->table('session_notes sn')
            ->select('sn.*, u.full_name AS author_name')
            ->join('users u', 'u.id = sn.created_by', 'left')
            ->where('sn.bk_service_record_id', $recordId)
            ->where('sn.deleted_at', null);

        if ($role === 'wali-kelas') {
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

    /**
     * Kembalikan bilangan bulat positif PERTAMA dari sebuah daftar (mis. daftar
     * id Siswa/Kelas Sasaran dari tombol "+"). null bila tak ada yang valid.
     */
    private function firstPositiveInt($list): ?int
    {
        foreach ((array) $list as $value) {
            if ((int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
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
