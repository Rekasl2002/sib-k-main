<?php

/**
 * File: app/Services/ConsultationComplaintService.php
 * Fitur: Konsultasi dan Pengaduan.
 * Peran/izin: Pelapor membuat/memantau aduan sendiri; Koordinator BK dan
 * Guru BK meninjau, mengubah status, dan menugaskan tindak lanjut.
 * Berhubungan dengan: ConsultationComplaintModel, ConsultationComplaintSubjectModel,
 * users, students, classes.
 *
 * Kerahasiaan (Fase 2): isi laporan TIDAK otomatis tampil ke subjek. Subjek
 * (siswa/orang tua) hanya melihat bila pelapor/BK mengizinkan lewat sakelar
 * visible_to_student / visible_to_parent / visible_to_homeroom.
 */

namespace App\Services;

use App\Models\ConsultationComplaintModel;
use App\Models\ConsultationComplaintSubjectModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

class ConsultationComplaintService
{
    private BaseConnection $db;
    private ConsultationComplaintModel $model;
    private ConsultationComplaintSubjectModel $subjectModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->model = new ConsultationComplaintModel();
        $this->subjectModel = new ConsultationComplaintSubjectModel();
    }

    /**
     * Jenis laporan yang boleh dipilih per peran (penegakan di sisi server).
     *
     * @return list<string>
     */
    public function allowedRequestTypes(string $role): array
    {
        // Jenis "permintaan" yang relevan untuk semua pelapor (Perbaikan Kedua).
        $permintaan = ['Permintaan Konseling', 'Permintaan Bimbingan', 'Permintaan Informasi Karier/Studi', 'Permintaan Mediasi'];

        return match ($role) {
            'siswa'      => ['Konsultasi', 'Pengaduan', ...$permintaan, 'Lainnya/Tidak Bisa Menentukan'],
            'orang-tua'  => ['Konsultasi', 'Pengaduan', ...$permintaan, 'Laporan Orang Tua', 'Lainnya/Tidak Bisa Menentukan'],
            'wali-kelas' => ['Konsultasi', 'Pengaduan', ...$permintaan, 'Laporan Wali Kelas', 'Lainnya/Tidak Bisa Menentukan'],
            default      => ['Konsultasi', 'Pengaduan', ...$permintaan, 'Laporan Orang Tua', 'Laporan Wali Kelas', 'Lainnya/Tidak Bisa Menentukan'],
        };
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
                ->orLike('cc.subject_other_name', (string) $filters['q'])
                ->groupEnd();
        }

        return $builder
            ->orderBy('cc.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Ringkasan jumlah untuk kartu statistik (mengikuti lingkup peran).
     *
     * @return array<string,int>
     */
    public function stats(string $role, int $userId): array
    {
        $builder = $this->db->table('consultation_complaints cc')
            ->select('cc.status')
            ->where('cc.deleted_at', null);
        $this->applyRoleScope($builder, $role, $userId);

        $rows = $builder->get()->getResultArray();

        $stats = ['total' => 0, 'baru' => 0, 'proses' => 0, 'selesai' => 0];
        foreach ($rows as $r) {
            $stats['total']++;
            $status = (string) ($r['status'] ?? '');
            if ($status === 'Diajukan') {
                $stats['baru']++;
            } elseif (in_array($status, ['Ditinjau', 'Diterima', 'Dijadwalkan'], true)) {
                $stats['proses']++;
            } elseif (in_array($status, ['Selesai', 'Diarsipkan', 'Ditolak'], true)) {
                $stats['selesai']++;
            }
        }

        return $stats;
    }

    public function find(int $id, string $role, int $userId): ?array
    {
        $builder = $this->baseBuilder()->where('cc.id', $id);
        $this->applyRoleScope($builder, $role, $userId);

        return $builder->get()->getRowArray() ?: null;
    }

    /**
     * Daftar subjek (siswa terkait) sebuah laporan untuk ditampilkan.
     *
     * @return list<array<string,mixed>>
     */
    public function subjects(int $complaintId): array
    {
        return $this->db->table('consultation_complaint_subjects ccs')
            ->select('ccs.id, ccs.student_id, ccs.manual_name, u.full_name AS student_name, c.class_name, s.nisn')
            ->join('students s', 's.id = ccs.student_id AND s.deleted_at IS NULL', 'left')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('ccs.complaint_id', $complaintId)
            ->orderBy('ccs.id', 'ASC')
            ->get()->getResultArray();
    }

    public function create(array $post, string $role, int $userId): int
    {
        $id = (int) $this->model->insert($this->payload($post, $role, $userId), true);
        if ($id > 0) {
            $this->syncSubjects($id, $post, $role, $userId);
        }
        return $id;
    }

    public function update(int $id, array $post, string $role = '', int $userId = 0): bool
    {
        $ok = (bool) $this->model->update($id, $this->payload($post, $role, $userId, false));
        if ($ok) {
            $this->syncSubjects($id, $post, $role, $userId);
        }
        return $ok;
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

    public function review(int $id, array $post, int $userId, string $role = ''): bool
    {
        $status = (string) ($post['status'] ?? 'Ditinjau');

        // Penegakan sisi server (Matriks CRUD §6): Guru BK hanya boleh menugaskan
        // dirinya sendiri. Nilai selain dirinya/kosong dipaksa "belum ditugaskan".
        $assignedTo = $this->nullableInt($post['assigned_to_user_id'] ?? null);
        if ($role === 'guru-bk' && $assignedTo !== null && $assignedTo !== $userId) {
            $assignedTo = null;
        }

        $payload = [
            'status' => $status,
            'priority' => $post['priority'] ?? null,
            'assigned_to_user_id' => $assignedTo,
            'handled_by' => $userId,
            'handled_at' => date('Y-m-d H:i:s'),
            'closed_at' => in_array($status, ['Selesai', 'Ditolak', 'Diarsipkan'], true) ? date('Y-m-d H:i:s') : null,
            'privacy_level' => $post['privacy_level'] ?? null,
            'visible_to_homeroom' => ! empty($post['visible_to_homeroom']) ? 1 : 0,
            'visible_to_parent' => ! empty($post['visible_to_parent']) ? 1 : 0,
            'visible_to_student' => ! empty($post['visible_to_student']) ? 1 : 0,
        ];

        // Sakelar privasi selalu ikut tersimpan (termasuk saat dimatikan = 0).
        $always = ['visible_to_homeroom', 'visible_to_parent', 'visible_to_student'];
        $payload = array_filter(
            $payload,
            static fn($value, $key) => $value !== null || in_array($key, $always, true),
            ARRAY_FILTER_USE_BOTH
        );

        return (bool) $this->model->update($id, $payload);
    }

    /**
     * @return array<string,mixed>
     */
    public function formOptions(string $role, int $userId): array
    {
        return [
            'students' => $this->studentsForRole($role, $userId),
            'counselors' => $this->assignableCounselors($role, $userId),
            'request_types' => $this->allowedRequestTypes($role),
        ];
    }

    /**
     * Daftar petugas yang boleh ditugaskan ("Tugaskan ke").
     * Aturan Matriks CRUD (§6): Guru BK HANYA dapat menugaskan dirinya sendiri;
     * pemilihan Guru BK/petugas lain adalah wewenang Koordinator BK. Karena itu,
     * untuk peran guru-bk daftar dibatasi hanya pada akun yang bersangkutan.
     *
     * @return list<array<string,mixed>>
     */
    private function assignableCounselors(string $role, int $userId): array
    {
        $builder = $this->db->table('users')
            ->select('id, full_name')
            ->whereIn('role_id', [2, 3])
            ->where('is_active', 1)
            ->where('deleted_at', null);

        if ($role === 'guru-bk') {
            $builder->where('id', $userId);
        }

        return $builder->orderBy('full_name', 'ASC')->get()->getResultArray();
    }

    private function baseBuilder(): BaseBuilder
    {
        return $this->db->table('consultation_complaints cc')
            ->select(
                'cc.*, ru.full_name AS reporter_name, su.full_name AS student_name, s.nisn,' .
                'c.class_name, au.full_name AS assigned_to_name, hu.full_name AS handled_by_name,' .
                '(SELECT COUNT(*) FROM consultation_complaint_subjects ccs WHERE ccs.complaint_id = cc.id) AS subject_count'
            )
            ->join('users ru', 'ru.id = cc.reporter_user_id AND ru.deleted_at IS NULL', 'left')
            ->join('students s', 's.id = cc.subject_student_id AND s.deleted_at IS NULL', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->join('users au', 'au.id = cc.assigned_to_user_id AND au.deleted_at IS NULL', 'left')
            ->join('users hu', 'hu.id = cc.handled_by AND hu.deleted_at IS NULL', 'left')
            ->where('cc.deleted_at', null);
    }

    /**
     * Lingkup baca per peran. BK/Admin = semua. Pelapor = laporan miliknya.
     * Subjek (siswa/orang tua/wali kelas) HANYA bila diizinkan lewat sakelar
     * visible_to_* (laporan tidak otomatis tampil ke subjek).
     */
    private function applyRoleScope(BaseBuilder $builder, string $role, int $userId): void
    {
        if (in_array($role, ['admin', 'koordinator-bk', 'guru-bk'], true)) {
            return;
        }

        if ($role === 'wali-kelas') {
            $builder->groupStart()
                ->where('cc.reporter_user_id', $userId)
                ->orWhere('(cc.visible_to_homeroom = 1 AND ' . $this->subjectMatchSql('cx.homeroom_teacher_id = ' . $userId) . ')', null, false)
                ->groupEnd();
            return;
        }

        if ($role === 'siswa') {
            $builder->groupStart()
                ->where('cc.reporter_user_id', $userId)
                ->orWhere('(cc.visible_to_student = 1 AND ' . $this->subjectMatchSql('sx.user_id = ' . $userId) . ')', null, false)
                ->groupEnd();
            return;
        }

        if ($role === 'orang-tua') {
            $builder->groupStart()
                ->where('cc.reporter_user_id', $userId)
                ->orWhere('(cc.visible_to_parent = 1 AND ' . $this->subjectMatchSql('sx.parent_id = ' . $userId) . ')', null, false)
                ->groupEnd();
        }
    }

    /**
     * Subquery EXISTS: benar bila kondisi $cond cocok untuk salah satu subjek
     * laporan (subjek utama cc.subject_student_id maupun subjek tambahan).
     */
    private function subjectMatchSql(string $cond): string
    {
        return 'EXISTS (SELECT 1 FROM students sx '
            . 'LEFT JOIN classes cx ON cx.id = sx.class_id AND cx.deleted_at IS NULL '
            . 'WHERE sx.deleted_at IS NULL AND ' . $cond . ' AND '
            . '(sx.id = cc.subject_student_id OR sx.id IN '
            . '(SELECT ccs.student_id FROM consultation_complaint_subjects ccs WHERE ccs.complaint_id = cc.id)))';
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $post, string $role, int $userId, bool $insert = true): array
    {
        // Subjek utama = subjek pertama yang dipilih (untuk tampilan & lingkup lama).
        [$primaryStudentId, $primaryManual] = $this->primarySubject($post, $role, $userId);

        $payload = [
            'subject_student_id' => $primaryStudentId,
            'subject_other_name' => $primaryManual,
            'request_type' => $this->sanitizeRequestType((string) ($post['request_type'] ?? 'Konsultasi'), $role),
            'category' => trim((string) ($post['category'] ?? '')) ?: null,
            'title' => trim((string) ($post['title'] ?? 'Konsultasi BK')),
            'description' => trim((string) ($post['description'] ?? '')),
            'occurred_at' => $this->normalizeDateTime($post['occurred_at'] ?? null, $post['occurred_date'] ?? null, $post['occurred_time'] ?? null),
            'location' => trim((string) ($post['location'] ?? '')) ?: null,
            'priority' => $post['priority'] ?? 'Sedang',
            'status' => $post['status'] ?? 'Diajukan',
            'privacy_level' => $post['privacy_level'] ?? 'Rahasia BK',
            'visible_to_homeroom' => ! empty($post['visible_to_homeroom']) ? 1 : 0,
            'visible_to_parent' => ! empty($post['visible_to_parent']) ? 1 : 0,
            'visible_to_student' => ! empty($post['visible_to_student']) ? 1 : 0,
        ];

        if ($insert) {
            $payload['reporter_type'] = $this->reporterType($role);
            $payload['reporter_user_id'] = $userId;
        } else {
            unset($payload['status']); // status hanya diubah lewat review BK
        }

        return $payload;
    }

    /**
     * Jenis laporan yang dikirim dipaksa ke daftar yang diizinkan peran.
     */
    private function sanitizeRequestType(string $type, string $role): string
    {
        $allowed = $this->allowedRequestTypes($role);
        return in_array($type, $allowed, true) ? $type : ($allowed[0] ?? 'Konsultasi');
    }

    /**
     * Tentukan subjek utama (id siswa & nama manual pertama) dari input,
     * dengan penegakan: siswa dari data dibatasi pada yang boleh dipilih peran.
     *
     * @return array{0:?int,1:?string}
     */
    private function primarySubject(array $post, string $role, int $userId): array
    {
        $ids = $this->allowedSubjectStudentIds($post, $role, $userId);
        $manuals = $this->cleanManualNames($post);

        $primaryId = $ids[0] ?? $this->nullableInt($post['subject_student_id'] ?? $post['student_id'] ?? null);
        // Bila subjek utama (dari single field lama) tidak diizinkan, abaikan.
        if ($primaryId !== null && $role !== '' && ! in_array($role, ['admin', 'koordinator-bk', 'guru-bk'], true)) {
            $allowedSet = $this->studentIdSet($role, $userId);
            if (! in_array($primaryId, $allowedSet, true)) {
                $primaryId = $ids[0] ?? null;
            }
        }

        $primaryManual = $manuals[0] ?? (trim((string) ($post['subject_other_name'] ?? '')) ?: null);

        return [$primaryId, $primaryManual];
    }

    /**
     * Simpan ulang seluruh subjek (siswa data + manual) ke tabel junction.
     */
    private function syncSubjects(int $complaintId, array $post, string $role, int $userId): void
    {
        $this->subjectModel->where('complaint_id', $complaintId)->delete();

        $rows = [];
        foreach ($this->allowedSubjectStudentIds($post, $role, $userId) as $sid) {
            $rows[] = ['complaint_id' => $complaintId, 'student_id' => $sid, 'manual_name' => null];
        }
        foreach ($this->cleanManualNames($post) as $name) {
            $rows[] = ['complaint_id' => $complaintId, 'student_id' => null, 'manual_name' => $name];
        }

        if ($rows) {
            $this->subjectModel->insertBatch($rows);
        }
    }

    /**
     * Id siswa terpilih yang DIIZINKAN bagi peran (gabungan field multi & single).
     *
     * @return list<int>
     */
    private function allowedSubjectStudentIds(array $post, string $role, int $userId): array
    {
        $raw = array_merge(
            (array) ($post['subject_student_ids'] ?? []),
            [$post['subject_student_id'] ?? null, $post['student_id'] ?? null]
        );
        $ids = array_values(array_unique(array_filter(array_map('intval', $raw), static fn($v) => $v > 0)));

        if ($ids === [] || $role === '' || in_array($role, ['admin', 'koordinator-bk', 'guru-bk'], true)) {
            return $ids;
        }

        $allowed = $this->studentIdSet($role, $userId);
        return array_values(array_intersect($ids, $allowed));
    }

    /**
     * @return list<string>
     */
    private function cleanManualNames(array $post): array
    {
        $raw = array_merge(
            (array) ($post['subject_manual_names'] ?? []),
            [$post['subject_other_name'] ?? null]
        );
        $names = [];
        foreach ($raw as $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $names[mb_strtolower($name)] = mb_substr($name, 0, 190);
            }
        }

        return array_values($names);
    }

    /**
     * Himpunan id siswa yang boleh dipilih sebagai subjek oleh peran.
     *
     * @return list<int>
     */
    private function studentIdSet(string $role, int $userId): array
    {
        return array_map(
            static fn($r) => (int) $r['id'],
            $this->studentsForRole($role, $userId)
        );
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
