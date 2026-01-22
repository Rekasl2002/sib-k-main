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
    }

    /**
     * List submissions milik pelapor (student/parent).
     */
    public function listForReporter(int $reporterUserId, string $reporterType = 'student'): array
    {
        $builder = $this->db->table('violation_submissions vs');
        $builder->select([
            'vs.*',
            'vc.category_name',
            'su.full_name AS subject_student_name',
            'ss.nis AS subject_student_nis',
            'c.class_name AS subject_student_class',
        ]);
        $builder->join('violation_categories vc', 'vc.id = vs.category_id', 'left');
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
     * Detail submission (harus milik pelapor).
     */
    public function getDetailForReporter(int $id, int $reporterUserId, string $reporterType = 'student'): ?array
    {
        $builder = $this->db->table('violation_submissions vs');
        $builder->select([
            'vs.*',
            'vc.category_name',
            'ru.full_name AS reporter_name',
            'hu.full_name AS handled_by_name',
            'su.full_name AS subject_student_name',
            'ss.nis AS subject_student_nis',
            'c.class_name AS subject_student_class',
        ]);
        $builder->join('violation_categories vc', 'vc.id = vs.category_id', 'left');
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
        return (int) $this->model->getInsertID();
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
            $data['converted_violation_id'],
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
     * Editable jika belum Ditolak/Diterima/Dikonversi.
     */
    public function isEditable(array $row): bool
    {
        $status = $row['status'] ?? 'Diajukan';
        return !in_array($status, ['Ditolak', 'Diterima', 'Dikonversi'], true);
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
}
