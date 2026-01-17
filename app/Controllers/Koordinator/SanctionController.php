<?php

namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use App\Models\SanctionModel;
use App\Models\ViolationModel;
use CodeIgniter\HTTP\RedirectResponse;

class SanctionController extends BaseController
{
    protected SanctionModel $sanctionModel;
    protected ViolationModel $violationModel;

    public function __construct()
    {
        $this->sanctionModel  = new SanctionModel();
        $this->violationModel = new ViolationModel();

        // Optional helper (kalau ada di project)
        if (function_exists('helper')) {
            try {
                // tambah 'auth' agar fungsi is_logged_in/is_koordinator/auth_id aman (kalau helper tersedia)
                helper(['auth', 'permission', 'app']);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    /**
     * Guard login + role koordinator
     */
    private function ensureAuth(): ?RedirectResponse
    {
        if (!function_exists('is_logged_in') || !is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!function_exists('is_koordinator') || !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        return null;
    }

    /**
     * Guard permission opsional (RBAC granular).
     * Jika helper/func permission tidak tersedia, jangan blok (role guard tetap berjalan).
     */
    private function ensurePerm(string $perm, string $redirectTo = '/koordinator/cases', string $message = 'Akses ditolak'): ?RedirectResponse
    {
        if (function_exists('has_permission')) {
            if (!has_permission($perm)) {
                return redirect()->to($redirectTo)->with('error', $message);
            }
        }
        return null;
    }

    /**
     * Normalisasi path relatif agar tidak ada double slash / leading slash
     */
    private function normalizeRel(string $path): string
    {
        $path = preg_replace('#/+#', '/', (string) $path);
        return ltrim($path, '/');
    }

    /**
     * Hapus file fisik dengan pengamanan:
     * - tolak path traversal
     * - batasi hanya uploads/sanctions/
     * - pastikan realpath masih di dalam base folder
     */
    private function safeUnlinkSanctionDoc(string $relPath): void
    {
        $relPath = $this->normalizeRel($relPath);
        if ($relPath === '') return;

        if (str_contains($relPath, '..')) return;
        if (strpos($relPath, 'uploads/sanctions/') !== 0) return;

        $abs = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

        $realAbs  = realpath($abs);
        $realBase = realpath(rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sanctions');

        // realpath bisa false kalau file sudah hilang; itu aman (skip)
        if ($realAbs && $realBase && strpos($realAbs, $realBase) === 0 && is_file($realAbs)) {
            @unlink($realAbs);
        }
    }

    /**
     * Tambah sanksi untuk suatu pelanggaran
     * - Create sanksi = aksi "C", maka dibatasi:
     *   butuh manage_sanctions + manage_violations (agar selaras kebijakan CRUD ekstra via manage_violations)
     */
    public function store($violationId)
    {
        if ($r = $this->ensureAuth()) return $r;

        if ($r = $this->ensurePerm('manage_sanctions', '/koordinator/cases', 'Anda tidak memiliki izin mengelola sanksi')) return $r;
        if ($r = $this->ensurePerm('manage_violations', '/koordinator/cases', 'Anda tidak memiliki izin untuk membuat/menghapus data pelanggaran')) return $r;

        $violationId = (int) $violationId;

        // pastikan violation ada
        $violation = $this->violationModel->asArray()->find($violationId);
        if (!$violation) {
            return redirect()->to('/koordinator/cases')->with('error', 'Pelanggaran tidak ditemukan');
        }

        // validasi dasar (selaras Counselor, tapi lebih toleran untuk datetime-local)
        $rules = [
            'sanction_type'  => 'required|max_length[100]',
            'sanction_date'  => 'required|valid_date',
            'description'    => 'required|min_length[10]',
            'status'         => 'permit_empty|in_list[Dijadwalkan,Sedang Berjalan,Selesai,Dibatalkan]',
            'start_date'     => 'permit_empty|valid_date',
            'end_date'       => 'permit_empty|valid_date',
            'completed_date' => 'permit_empty|valid_date',
            // NOTE: parent_acknowledged_at sering dari input datetime-local (ada "T"), jadi tidak divalidasi valid_date di sini
            'parent_acknowledged_at' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // upload dokumen (opsional, multiple)
        $documentsArr = $this->handleDocumentsUpload($this->request->getFileMultiple('documents'));

        // Normalisasi datetime-local (YYYY-MM-DDTHH:MM -> YYYY-MM-DD HH:MM:SS)
        $ackAtRaw = trim((string) ($post['parent_acknowledged_at'] ?? ''));
        $ackAt = null;
        if ($ackAtRaw !== '') {
            $ackAt = str_replace('T', ' ', $ackAtRaw);
            if (strlen($ackAt) === 16) $ackAt .= ':00';
        }

        $parentAck = !empty($post['parent_acknowledged']) ? 1 : 0;
        if ($parentAck && empty($ackAt)) {
            $ackAt = date('Y-m-d H:i:s');
        }
        if (!$parentAck) {
            $ackAt = null;
        }

        $status       = !empty($post['status']) ? $post['status'] : 'Dijadwalkan';
        $sanctionDate = $post['sanction_date'] ?? null;

        $completedDate = $post['completed_date'] ?? null;
        if ($status === 'Selesai') {
            $completedDate = $completedDate ?: $sanctionDate;
        } else {
            $completedDate = $completedDate ?: null;
        }

        $verifyNow = !empty($post['verify_now']);
        $authId    = function_exists('auth_id') ? auth_id() : null;

        $payload = [
            'violation_id'      => $violationId,
            'sanction_type'     => trim((string) ($post['sanction_type'] ?? '')),
            'sanction_date'     => $sanctionDate,
            'start_date'        => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date'          => !empty($post['end_date']) ? $post['end_date'] : null,
            'description'       => trim((string) ($post['description'] ?? '')),
            'status'            => $status,
            'completed_date'    => $completedDate,
            'completion_notes'  => !empty($post['completion_notes']) ? $post['completion_notes'] : null,

            'assigned_by'       => $authId,

            // Koordinator boleh verify (jika diizinkan)
            'verified_by'       => $verifyNow ? $authId : null,
            'verified_at'       => $verifyNow ? date('Y-m-d H:i:s') : null,

            'parent_acknowledged'    => $parentAck,
            'parent_acknowledged_at' => $ackAt,

            // Simpan konsisten sebagai JSON string
            'documents'         => !empty($documentsArr)
                ? json_encode($documentsArr, JSON_UNESCAPED_SLASHES)
                : null,

            'notes'             => $post['notes'] ?? null,
        ];

        $id = $this->sanctionModel->insert($payload);
        if (!$id) {
            return redirect()->back()->withInput()->with('errors', $this->sanctionModel->errors());
        }

        // Jika violation masih "Dilaporkan", naikkan ke "Dalam Proses"
        if (($violation['status'] ?? '') === 'Dilaporkan') {
            $this->violationModel->update($violationId, ['status' => 'Dalam Proses']);
        }

        return redirect()->to('/koordinator/cases/detail/' . $violationId)
            ->with('success', 'Sanksi berhasil ditambahkan.');
    }

    /**
     * Read sanksi (boleh untuk Koordinator tanpa manage_sanctions)
     */
    public function show($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $sanction = $this->sanctionModel->getSanctionWithDetails($id);
        if (!$sanction) {
            return redirect()->to('/koordinator/cases')->with('error', 'Data sanksi tidak ditemukan');
        }

        $violationId = (int)($sanction['violation_id'] ?? 0);

        $data = [
            'sanction'    => $sanction,

            // meta title untuk tab
            'title'       => 'Detail Sanksi',
            'pageTitle'   => 'Detail Sanksi',
            'page_title'  => 'Detail Sanksi',

            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('koordinator/cases')],
                $violationId > 0
                    ? ['title' => 'Detail Kasus', 'url' => base_url('koordinator/cases/detail/' . $violationId)]
                    : ['title' => 'Kasus', 'url' => base_url('koordinator/cases')],
                ['title' => 'Detail Sanksi', 'url' => '#', 'active' => true],
            ],
        ];

        return view('koordinator/sanctions/show', $data);
    }

    /**
     * Edit form (U) -> butuh manage_sanctions
     */
    public function edit($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_sanctions', '/koordinator/cases', 'Anda tidak memiliki izin mengelola sanksi')) return $r;

        $sanction = $this->sanctionModel->getSanctionWithDetails($id);
        if (!$sanction) {
            return redirect()->to('/koordinator/cases')->with('error', 'Data sanksi tidak ditemukan');
        }

        $violationId = (int)($sanction['violation_id'] ?? 0);

        $data = [
            'sanction'    => $sanction,

            // meta title untuk tab
            'title'       => 'Edit Sanksi',
            'pageTitle'   => 'Edit Sanksi',
            'page_title'  => 'Edit Sanksi',

            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('koordinator/cases')],
                $violationId > 0
                    ? ['title' => 'Detail Kasus', 'url' => base_url('koordinator/cases/detail/' . $violationId)]
                    : ['title' => 'Kasus', 'url' => base_url('koordinator/cases')],
                ['title' => 'Edit Sanksi', 'url' => '#', 'active' => true],
            ],
        ];

        return view('koordinator/sanctions/edit', $data);
    }

    /**
     * Update sanksi (U) -> butuh manage_sanctions
     */
    public function update($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_sanctions', '/koordinator/cases', 'Anda tidak memiliki izin mengelola sanksi')) return $r;

        $sanction = $this->sanctionModel->asArray()->find($id);
        if (!$sanction) {
            return redirect()->to('/koordinator/cases')->with('error', 'Data sanksi tidak ditemukan');
        }

        $rules = [
            'sanction_type'  => 'required|max_length[100]',
            'sanction_date'  => 'required|valid_date',
            'description'    => 'required|min_length[10]',
            'status'         => 'permit_empty|in_list[Dijadwalkan,Sedang Berjalan,Selesai,Dibatalkan]',
            'start_date'     => 'permit_empty|valid_date',
            'end_date'       => 'permit_empty|valid_date',
            'completed_date' => 'permit_empty|valid_date',
            'parent_acknowledged_at' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // =========================================================
        // FIX: Hapus lampiran yang dicentang (remove_docs[])
        // View biasanya mengirim: name="remove_docs[]" value="uploads/sanctions/..."
        // =========================================================
        $existingJson = $sanction['documents'] ?? null;

        $removeDocs = $this->request->getPost('remove_docs');
        if (!is_array($removeDocs)) {
            $removeDocs = $removeDocs ? [$removeDocs] : [];
        }

        if (!empty($removeDocs)) {
            $current = (is_string($existingJson) && trim($existingJson) !== '')
                ? json_decode($existingJson, true)
                : [];

            if (!is_array($current)) $current = [];

            // normalize + pastikan array berisi string path
            $currentPaths = [];
            foreach ($current as $doc) {
                $p = is_array($doc) ? ($doc['path'] ?? '') : (string) $doc;
                $p = $this->normalizeRel((string)$p);
                if ($p !== '') $currentPaths[] = $p;
            }

            $toRemove = array_values(array_unique(array_filter(array_map(function ($p) {
                return $this->normalizeRel((string)$p);
            }, $removeDocs))));

            // keep yang tidak dihapus
            $kept = array_values(array_filter($currentPaths, fn($p) => !in_array($p, $toRemove, true)));

            // hapus file fisik (optional tapi disarankan)
            foreach ($toRemove as $rel) {
                // hanya hapus kalau memang sebelumnya termasuk lampiran yang ada
                if (in_array($rel, $currentPaths, true)) {
                    $this->safeUnlinkSanctionDoc($rel);
                }
            }

            // siapkan existingJson baru untuk merge upload
            $existingJson = !empty($kept)
                ? json_encode($kept, JSON_UNESCAPED_SLASHES)
                : null;
        }

        // keep existing + upload baru (existingJson sudah dibersihkan dari yang dihapus)
        $documentsArr = $this->handleDocumentsUpload(
            $this->request->getFileMultiple('documents'),
            $existingJson
        );

        // Normalisasi datetime-local
        $ackAtRaw = trim((string) ($post['parent_acknowledged_at'] ?? ''));
        $ackAt = null;
        if ($ackAtRaw !== '') {
            $ackAt = str_replace('T', ' ', $ackAtRaw);
            if (strlen($ackAt) === 16) $ackAt .= ':00';
        }

        $parentAck = !empty($post['parent_acknowledged']) ? 1 : 0;
        if ($parentAck && empty($ackAt)) {
            $ackAt = date('Y-m-d H:i:s');
        }
        if (!$parentAck) {
            $ackAt = null;
        }

        $status       = !empty($post['status']) ? $post['status'] : ($sanction['status'] ?? 'Dijadwalkan');
        $sanctionDate = $post['sanction_date'] ?? ($sanction['sanction_date'] ?? null);

        $completedDate = $post['completed_date'] ?? null;
        if ($status === 'Selesai') {
            $completedDate = $completedDate ?: $sanctionDate;
        } else {
            $completedDate = $completedDate ?: null;
        }

        $payload = [
            'sanction_type'     => trim((string) ($post['sanction_type'] ?? '')),
            'sanction_date'     => $sanctionDate,
            'start_date'        => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date'          => !empty($post['end_date']) ? $post['end_date'] : null,
            'description'       => trim((string) ($post['description'] ?? '')),
            'status'            => $status,
            'completed_date'    => $completedDate,
            'completion_notes'  => !empty($post['completion_notes']) ? $post['completion_notes'] : null,

            'parent_acknowledged'    => $parentAck,
            'parent_acknowledged_at' => $ackAt,

            // Simpan konsisten sebagai JSON string
            'documents'         => !empty($documentsArr)
                ? json_encode($documentsArr, JSON_UNESCAPED_SLASHES)
                : null,

            'notes'             => $post['notes'] ?? null,
        ];

        // optional verify_now (Koordinator saja)
        if (!empty($post['verify_now'])) {
            $payload['verified_by'] = function_exists('auth_id') ? auth_id() : null;
            $payload['verified_at'] = date('Y-m-d H:i:s');
        }

        if (!$this->sanctionModel->update($id, $payload)) {
            return redirect()->back()->withInput()->with('errors', $this->sanctionModel->errors());
        }

        return redirect()->to('/koordinator/sanctions/show/' . $id)
            ->with('success', 'Sanksi berhasil diperbarui.');
    }

    /**
     * Delete sanksi (D) -> butuh manage_sanctions + manage_violations
     */
    public function delete($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        if ($r = $this->ensurePerm('manage_sanctions', '/koordinator/cases', 'Anda tidak memiliki izin mengelola sanksi')) return $r;
        if ($r = $this->ensurePerm('manage_violations', '/koordinator/cases', 'Anda tidak memiliki izin untuk membuat/menghapus data pelanggaran')) return $r;

        $sanction = $this->sanctionModel->asArray()->find($id);
        if (!$sanction) {
            return redirect()->back()->with('error', 'Data sanksi tidak ditemukan');
        }

        $this->sanctionModel->delete($id);
        return redirect()->back()->with('success', 'Sanksi dihapus.');
    }

    /**
     * Tandai selesai (U) -> butuh manage_sanctions
     */
    public function complete($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_sanctions', '/koordinator/cases', 'Anda tidak memiliki izin mengelola sanksi')) return $r;

        $notes = $this->request->getPost('completion_notes') ?: null;
        $date  = $this->request->getPost('completed_date') ?: date('Y-m-d');

        $this->sanctionModel->update($id, [
            'status'           => 'Selesai',
            'completed_date'   => $date,
            'completion_notes' => $notes,
        ]);

        return redirect()->back()->with('success', 'Sanksi ditandai selesai.');
    }

    /**
     * Verifikasi (U) -> butuh manage_sanctions
     */
    public function verify($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_sanctions', '/koordinator/cases', 'Anda tidak memiliki izin mengelola sanksi')) return $r;

        $this->sanctionModel->update($id, [
            'verified_by' => function_exists('auth_id') ? auth_id() : null,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Sanksi terverifikasi.');
    }

    /**
     * Acknowledge oleh orang tua (U) -> butuh manage_sanctions
     */
    public function acknowledge($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if ($r = $this->ensurePerm('manage_sanctions', '/koordinator/cases', 'Anda tidak memiliki izin mengelola sanksi')) return $r;

        $atRaw = $this->request->getPost('parent_acknowledged_at') ?: '';
        $atRaw = trim((string) $atRaw);

        $at = $atRaw ? str_replace('T', ' ', $atRaw) : date('Y-m-d H:i:s');
        if (strlen($at) === 16) $at .= ':00';

        $this->sanctionModel->update($id, [
            'parent_acknowledged'    => 1,
            'parent_acknowledged_at' => $at,
        ]);

        return redirect()->back()->with('success', 'Status orang tua mengetahui telah dicatat.');
    }

    /**
     * Upload multiple documents (images/pdf/doc/xls/mp4)
     * Return: array of relative paths (konsisten)
     */
    private function handleDocumentsUpload(?array $files, ?string $existingJson = null): array
    {
        $kept = [];

        // decode existing
        if (is_string($existingJson) && $existingJson !== '') {
            $tmp = json_decode($existingJson, true);
            if (is_array($tmp)) $kept = $tmp;
        }

        if (!$files) return $kept;

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp4'];
        $max     = 5 * 1024 * 1024;

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'sanctions';

        $ym     = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target = $baseDir . DIRECTORY_SEPARATOR . $ym;

        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) continue;

            $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;
            if ($file->getSize() > $max) continue;

            $new = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($target, $new);

            $rel = 'uploads/sanctions/' . str_replace(DIRECTORY_SEPARATOR, '/', $ym) . '/' . $new;
            $kept[] = $this->normalizeRel($rel);
        }

        return $kept;
    }
}
