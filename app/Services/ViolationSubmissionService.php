<?php

namespace App\Services;

use App\Models\ViolationSubmissionsModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;

class ViolationSubmissionService
{
    protected ViolationSubmissionsModel $model;
    protected BaseConnection $db;

    /**
     * Batas & aturan upload bukti (sinkron dengan UI: 3MB per file).
     */
    protected int $maxFileSizeBytes = 3145728; // 3 * 1024 * 1024
    protected array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    protected array $allowedMimeTypes  = ['image/jpeg', 'image/png', 'application/pdf'];

    public function __construct()
    {
        $this->model = new ViolationSubmissionsModel();
        $this->db    = db_connect();

        helper(['notification', 'url']);
    }

    /**
     * List submissions milik pelapor (student/parent).
     */
    public function listForReporter(int $reporterUserId, string $reporterType = 'student'): array
    {
        $builder = $this->db->table('violation_submissions vs');
        $builder->select([
            'vs.*',
            'su.full_name AS subject_student_name',
            'ss.nisn AS subject_student_nisn',
            'c.class_name AS subject_student_class',
        ]);
        $builder->join('students ss', 'ss.id = vs.subject_student_id', 'left');
        $builder->join('users su', 'su.id = ss.user_id', 'left');
        $builder->join('classes c', 'c.id = ss.class_id', 'left');

        $builder->where('vs.reporter_user_id', $reporterUserId);
        $builder->where('vs.reporter_type', $reporterType);
        $builder->where('vs.deleted_at', null);

        $builder->orderBy('vs.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * List submission untuk petugas BK sesuai alur tinjau pada diagram.
     */
    public function listForReviewer(array $filters = []): array
    {
        $builder = $this->reviewerBuilder();

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $builder->where('vs.status', $status);
        }

        $reporterType = trim((string)($filters['reporter_type'] ?? ''));
        if ($reporterType !== '') {
            $builder->where('vs.reporter_type', $reporterType);
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('ru.full_name', $q)
                ->orLike('su.full_name', $q)
                ->orLike('ss.nisn', $q)
                ->orLike('vs.subject_other_name', $q)
                ->orLike('vs.description', $q)
                ->groupEnd();
        }

        return $builder
            ->orderBy('vs.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Detail submission untuk Guru BK/Koordinator BK.
     */
    public function getDetailForReviewer(int $id): ?array
    {
        $row = $this->reviewerBuilder()
            ->where('vs.id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $row['evidence_json'] = $this->normalizeEvidence($row['evidence_json'] ?? null);
        return $row;
    }

    /**
     * Ubah status tinjauan tanpa konversi.
     */
    public function reviewStatus(int $id, string $status, string $notes, int $handledBy): array
    {
        $allowed = ['Ditinjau', 'Ditolak', 'Diterima'];
        if (!in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Status pengaduan tidak valid.'];
        }

        $row = $this->getDetailForReviewer($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Pengaduan tidak ditemukan.'];
        }

        $ok = $this->model->update($id, [
            'status'       => $status,
            'handled_by'   => $handledBy ?: null,
            'handled_at'   => date('Y-m-d H:i:s'),
            'review_notes' => $notes !== '' ? $notes : null,
        ]);

        if (!$ok) {
            return ['success' => false, 'message' => 'Gagal memperbarui status pengaduan.'];
        }

        $this->notifyReporter($id, (int)($row['reporter_user_id'] ?? 0), (string)($row['reporter_type'] ?? ''), $status, $notes);

        return ['success' => true, 'message' => 'Status pengaduan diperbarui.'];
    }

    /**
     * Detail submission (harus milik pelapor).
     */
    public function getDetailForReporter(int $id, int $reporterUserId, string $reporterType = 'student'): ?array
    {
        $builder = $this->db->table('violation_submissions vs');
        $builder->select([
            'vs.*',
            'ru.full_name AS reporter_name',
            'hu.full_name AS handled_by_name',
            'su.full_name AS subject_student_name',
            'ss.nisn AS subject_student_nisn',
            'c.class_name AS subject_student_class',
        ]);
        $builder->join('users ru', 'ru.id = vs.reporter_user_id', 'left');
        $builder->join('users hu', 'hu.id = vs.handled_by', 'left');
        $builder->join('students ss', 'ss.id = vs.subject_student_id', 'left');
        $builder->join('users su', 'su.id = ss.user_id', 'left');
        $builder->join('classes c', 'c.id = ss.class_id', 'left');

        $builder->where('vs.id', $id);
        $builder->where('vs.reporter_user_id', $reporterUserId);
        $builder->where('vs.reporter_type', $reporterType);
        $builder->where('vs.deleted_at', null);

        $row = $builder->get()->getRowArray();
        if (!$row) {
            return null;
        }

        $row['evidence_json'] = $this->normalizeEvidence($row['evidence_json'] ?? null);
        return $row;
    }

    /**
     * Buat submission baru.
     */
    public function create(array $data, array $files = []): int
    {
        // Rapikan field yang umum (opsional, tapi aman)
        $data['subject_other_name'] = isset($data['subject_other_name']) ? trim((string) $data['subject_other_name']) : null;
        $data['location']          = isset($data['location']) ? trim((string) $data['location']) : null;
        $data['witness']           = isset($data['witness']) ? trim((string) $data['witness']) : null;
        $data['description']       = isset($data['description']) ? trim((string) $data['description']) : ($data['description'] ?? null);

        // Simpan evidence
        $evidencePaths = $this->storeEvidenceFiles($files);
        $data['evidence_json'] = $evidencePaths ?: null;

        // default status
        $data['status'] = $data['status'] ?? 'Diajukan';

        $this->model->insert($data);
        $newId = (int) $this->model->getInsertID();

        if ($newId > 0) {
            $this->notifyBkStaff($newId, (string)($data['description'] ?? ''));
        }

        return $newId;
    }

    /**
     * Update submission (hanya jika editable).
     */
    public function updateForReporter(
        int $id,
        int $reporterUserId,
        string $reporterType,
        array $data,
        array $newFiles = [],
        array $removePaths = []
    ): bool {
        $current = $this->model
            ->where('id', $id)
            ->where('reporter_user_id', $reporterUserId)
            ->where('reporter_type', $reporterType)
            ->where('deleted_at', null)
            ->first();

        if (!$current) {
            return false;
        }

        if (!$this->isEditable($current)) {
            return false;
        }

        // Jangan izinkan reporter mengubah workflow fields (kunci dari awal)
        unset(
            $data['status'],
            $data['handled_by'],
            $data['handled_at'],
            $data['review_notes'],
            $data['reporter_type'],
            $data['reporter_user_id']
        );

        // Rapikan field umum
        if (array_key_exists('subject_other_name', $data)) $data['subject_other_name'] = trim((string) $data['subject_other_name']);
        if (array_key_exists('location', $data))          $data['location']          = trim((string) $data['location']);
        if (array_key_exists('witness', $data))           $data['witness']           = trim((string) $data['witness']);
        if (array_key_exists('description', $data))       $data['description']       = trim((string) $data['description']);

        $currentEvidence = $this->normalizeEvidence($current['evidence_json'] ?? null);

        // remove selected old evidence (tidak hapus fisik file: aman untuk audit)
        if (!empty($removePaths)) {
            $removePaths = array_values(array_filter(array_map('strval', $removePaths)));
            $currentEvidence = array_values(array_filter($currentEvidence, function ($p) use ($removePaths) {
                return !in_array((string) $p, $removePaths, true);
            }));
        }

        // Tambah evidence baru
        $added  = $this->storeEvidenceFiles($newFiles);
        $merged = array_values(array_unique(array_merge($currentEvidence, $added)));

        $data['evidence_json'] = $merged ?: null;

        return (bool) $this->model->update($id, $data);
    }

    /**
     * Soft delete submission (hanya jika editable).
     */
    public function deleteForReporter(int $id, int $reporterUserId, string $reporterType): bool
    {
        $current = $this->model
            ->where('id', $id)
            ->where('reporter_user_id', $reporterUserId)
            ->where('reporter_type', $reporterType)
            ->where('deleted_at', null)
            ->first();

        if (!$current) {
            return false;
        }

        if (!$this->isEditable($current)) {
            return false;
        }

        return (bool) $this->model->delete($id);
    }

    /**
     * Editable jika belum Ditolak/Diterima.
     */
    public function isEditable(array $row): bool
    {
        $status = $row['status'] ?? 'Diajukan';
        return !in_array($status, ['Ditolak', 'Diterima'], true);
    }

    /**
     * Normalisasi evidence_json menjadi array of string.
     */
    public function normalizeEvidence($val): array
    {
        if (is_array($val)) {
            return array_values(array_filter(array_map('strval', $val)));
        }

        if (is_string($val) && $val !== '') {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return [];
    }

    /**
     * Simpan file bukti ke: public/uploads/violation_submissions/YYYY/MM
     * Return: array path relatif.
     *
     * Catatan: $files bisa berupa:
     * - array UploadedFile (getFileMultiple)
     * - nested array (getFiles)
     */
    protected function storeEvidenceFiles(array $files): array
    {
        $paths = [];

        $flatFiles = $this->flattenFiles($files);

        foreach ($flatFiles as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            if (!$file->isValid() || $file->hasMoved()) {
                continue;
            }

            // Size check (3MB)
            $size = (int) ($file->getSize() ?? 0);
            if ($size <= 0 || $size > $this->maxFileSizeBytes) {
                continue;
            }

            // Extension check
            $ext = strtolower((string) ($file->getClientExtension() ?? ''));
            if (!in_array($ext, $this->allowedExtensions, true)) {
                continue;
            }

            // MIME check (lebih aman dari sekadar ext)
            $mime = strtolower((string) ($file->getMimeType() ?? ''));
            if ($mime !== '' && !in_array($mime, $this->allowedMimeTypes, true)) {
                continue;
            }

            $year   = date('Y');
            $month  = date('m');
            $relDir = "uploads/violation_submissions/{$year}/{$month}/";
            $absDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relDir;

            if (!is_dir($absDir)) {
                if (!@mkdir($absDir, 0775, true) && !is_dir($absDir)) {
                    // gagal buat folder, skip file ini
                    continue;
                }
            }

            $newName = $file->getRandomName();

            try {
                $file->move($absDir, $newName);
            } catch (\Throwable $e) {
                // kalau gagal move, skip file ini
                continue;
            }

            $paths[] = $relDir . $newName;
        }

        return $paths;
    }

    /**
     * Flatten array file (mengatasi bentuk nested dari request->getFiles()).
     *
     * @param array $files
     * @return array<int, UploadedFile>
     */
    protected function flattenFiles(array $files): array
    {
        $out = [];

        $walker = function ($item) use (&$out, &$walker) {
            if ($item instanceof UploadedFile) {
                $out[] = $item;
                return;
            }
            if (is_array($item)) {
                foreach ($item as $v) {
                    $walker($v);
                }
            }
        };

        $walker($files);

        return $out;
    }

    protected function reviewerBuilder()
    {
        $builder = $this->db->table('violation_submissions vs');
        $builder->select([
            'vs.*',
            'ru.full_name AS reporter_name',
            'rr.role_name AS reporter_role',
            'hu.full_name AS handled_by_name',
            'su.full_name AS subject_student_name',
            'ss.nisn AS subject_student_nisn',
            'c.class_name AS subject_student_class',
        ]);
        $builder->join('users ru', 'ru.id = vs.reporter_user_id', 'left');
        $builder->join('roles rr', 'rr.id = ru.role_id', 'left');
        $builder->join('users hu', 'hu.id = vs.handled_by', 'left');
        $builder->join('students ss', 'ss.id = vs.subject_student_id', 'left');
        $builder->join('users su', 'su.id = ss.user_id', 'left');
        $builder->join('classes c', 'c.id = ss.class_id', 'left');
        $builder->where('vs.deleted_at', null);

        return $builder;
    }

    protected function notifyBkStaff(int $submissionId, string $description): void
    {
        try {
            $rows = $this->db->table('users u')
                ->select('u.id, r.role_name')
                ->join('roles r', 'r.id = u.role_id', 'inner')
                ->whereIn('r.role_name', ['Guru BK', 'Koordinator BK'])
                ->where('u.is_active', 1)
                ->where('u.deleted_at', null)
                ->get()
                ->getResultArray();

            $preview = trim(mb_substr(strip_tags($description), 0, 90));
            foreach ($rows as $row) {
                $role = strtolower((string)($row['role_name'] ?? ''));
                $prefix = str_contains($role, 'koordinator') ? 'koordinator' : 'counselor';
                send_notification(
                    (int)$row['id'],
                    'Pengaduan Pelanggaran Baru',
                    $preview !== '' ? $preview : 'Ada pengaduan pelanggaran baru yang perlu ditinjau.',
                    'violation_submission',
                    ['submission_id' => $submissionId],
                    site_url($prefix . '/violation-submissions/show/' . $submissionId)
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'notifyBkStaff violation submission failed: ' . $e->getMessage());
        }
    }

    protected function notifyReporter(int $submissionId, int $reporterUserId, string $reporterType, string $status, string $notes = ''): void
    {
        if ($reporterUserId <= 0) {
            return;
        }

        $prefix = match ($reporterType) {
            'parent'   => 'parent',
            'homeroom' => 'homeroom',
            default    => 'student',
        };

        $message = 'Status pengaduan Anda: ' . $status . '.';
        if (trim($notes) !== '') {
            $message .= ' Catatan: ' . trim($notes);
        }

        send_notification(
            $reporterUserId,
            'Update Status Pengaduan',
            $message,
            'violation_submission',
            ['submission_id' => $submissionId, 'status' => $status],
            site_url($prefix . '/violation-submissions/show/' . $submissionId)
        );
    }
}
