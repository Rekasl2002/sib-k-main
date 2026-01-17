<?php

/**
 * File Path: app/Controllers/Counselor/SanctionController.php
 *
 * Counselor • Sanction Controller
 * - CRUD dasar sanksi (store/show/edit/update/delete)
 * - Workflow tambahan: complete(), verify(), acknowledge()
 * - Upload dokumen multi-file + (FIX) hapus lampiran yang dicentang (remove_docs[])
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Models\SanctionModel;
use App\Models\ViolationModel;

class SanctionController extends BaseController
{
    protected SanctionModel $sanctionModel;
    protected ViolationModel $violationModel;

    public function __construct()
    {
        helper(['auth']); // untuk is_logged_in(), is_guru_bk(), is_koordinator(), auth_id()

        $this->sanctionModel  = new SanctionModel();
        $this->violationModel = new ViolationModel();
    }

    private function ensureAuth()
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }
        return null;
    }

    public function store($violationId)
    {
        if ($r = $this->ensureAuth()) return $r;

        // pastikan violation ada
        $violation = $this->violationModel->asArray()->find($violationId);
        if (!$violation) {
            return redirect()->to('/counselor/cases')->with('error', 'Pelanggaran tidak ditemukan');
        }

        // validasi dasar
        $rules = [
            'sanction_type'           => 'required|max_length[100]',
            'sanction_date'           => 'required|valid_date',
            'description'             => 'required|min_length[10]',
            'status'                  => 'permit_empty|in_list[Dijadwalkan,Sedang Berjalan,Selesai,Dibatalkan]',
            'start_date'              => 'permit_empty|valid_date',
            'end_date'                => 'permit_empty|valid_date',
            'completed_date'          => 'permit_empty|valid_date',
            'parent_acknowledged_at'  => 'permit_empty|valid_date',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // upload dokumen (opsional, multiple)
        // handleDocumentsUpload() mengembalikan JSON string (atau null), jadi simpan langsung tanpa json_encode lagi
        $documentsJson = $this->handleDocumentsUpload($this->request->getFileMultiple('documents'));

        $payload = [
            'violation_id'           => (int) $violationId,
            'sanction_type'          => trim($post['sanction_type'] ?? ''),
            'sanction_date'          => $post['sanction_date'] ?? null,
            'start_date'             => $post['start_date'] ?: null,
            'end_date'               => $post['end_date'] ?: null,
            'description'            => trim($post['description'] ?? ''),
            'status'                 => $post['status'] ?: 'Dijadwalkan',
            'completed_date'         => ($post['status'] ?? '') === 'Selesai'
                ? ($post['completed_date'] ?: ($post['sanction_date'] ?? null))
                : ($post['completed_date'] ?: null),
            'completion_notes'       => $post['completion_notes'] ?: null,
            'assigned_by'            => auth_id(),
            'verified_by'            => (is_koordinator() && !empty($post['verify_now'])) ? auth_id() : null,
            'verified_at'            => (is_koordinator() && !empty($post['verify_now'])) ? date('Y-m-d H:i:s') : null,
            'parent_acknowledged'    => !empty($post['parent_acknowledged']) ? 1 : 0,
            'parent_acknowledged_at' => !empty($post['parent_acknowledged'])
                ? ($post['parent_acknowledged_at'] ?: date('Y-m-d H:i:s'))
                : null,
            'documents'              => $documentsJson ?: null,
            'notes'                  => $post['notes'] ?? null,
        ];

        $id = $this->sanctionModel->insert($payload);
        if (!$id) {
            return redirect()->back()->withInput()->with('errors', $this->sanctionModel->errors());
        }

        // Jika violation masih "Dilaporkan", naikkan ke "Dalam Proses"
        if (($violation['status'] ?? '') === 'Dilaporkan') {
            $this->violationModel->update($violationId, ['status' => 'Dalam Proses']);
        }

        return redirect()->to('/counselor/cases/detail/' . $violationId)
            ->with('success', 'Sanksi berhasil ditambahkan.');
    }

    public function show($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $sanction = $this->sanctionModel->getSanctionWithDetails($id);
        if (!$sanction) {
            return redirect()->to('/counselor/cases')->with('error', 'Data sanksi tidak ditemukan');
        }

        $violationId = (int) ($sanction['violation_id'] ?? 0);

        $data = [
            'sanction'   => $sanction,

            'title'      => 'Detail Sanksi',
            'pageTitle'  => 'Detail Sanksi',
            'page_title' => 'Detail Sanksi',

            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('counselor/cases')],
                $violationId > 0
                    ? ['title' => 'Detail Kasus', 'url' => base_url('counselor/cases/detail/' . $violationId)]
                    : ['title' => 'Kasus', 'url' => base_url('counselor/cases')],
                ['title' => 'Detail Sanksi', 'url' => '#', 'active' => true],
            ],
        ];

        return view('counselor/sanctions/show', $data);
    }

    public function edit($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $sanction = $this->sanctionModel->getSanctionWithDetails($id);
        if (!$sanction) {
            return redirect()->to('/counselor/cases')->with('error', 'Data sanksi tidak ditemukan');
        }

        $violationId = (int) ($sanction['violation_id'] ?? 0);

        $data = [
            'sanction'   => $sanction,

            'title'      => 'Edit Sanksi',
            'pageTitle'  => 'Edit Sanksi',
            'page_title' => 'Edit Sanksi',

            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Kasus & Pelanggaran', 'url' => base_url('counselor/cases')],
                $violationId > 0
                    ? ['title' => 'Detail Kasus', 'url' => base_url('counselor/cases/detail/' . $violationId)]
                    : ['title' => 'Kasus', 'url' => base_url('counselor/cases')],
                ['title' => 'Edit Sanksi', 'url' => '#', 'active' => true],
            ],
        ];

        return view('counselor/sanctions/edit', $data);
    }

    public function update($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $sanction = $this->sanctionModel->asArray()->find($id);
        if (!$sanction) {
            return redirect()->to('/counselor/cases')->with('error', 'Data sanksi tidak ditemukan');
        }

        $rules = [
            'sanction_type'           => 'required|max_length[100]',
            'sanction_date'           => 'required|valid_date',
            'description'             => 'required|min_length[10]',
            'status'                  => 'permit_empty|in_list[Dijadwalkan,Sedang Berjalan,Selesai,Dibatalkan]',
            'start_date'              => 'permit_empty|valid_date',
            'end_date'                => 'permit_empty|valid_date',
            'completed_date'          => 'permit_empty|valid_date',
            'parent_acknowledged_at'  => 'permit_empty|valid_date',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // =========================================================
        // FIX: Hapus lampiran yang dicentang (remove_docs[])
        // - View mengirim: name="remove_docs[]" value="<path>"
        // - Tanpa ini, existing lampiran selalu ikut merge dan tidak pernah hilang
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

            // normalize => array of string path
            $currentPaths = [];
            foreach ($current as $doc) {
                $p = is_array($doc) ? ($doc['path'] ?? '') : (string) $doc;
                if ($p !== '') $currentPaths[] = $p;
            }

            $toRemove = array_values(array_unique(array_filter(array_map('strval', $removeDocs))));
            $kept     = array_values(array_filter($currentPaths, fn($p) => !in_array($p, $toRemove, true)));

            // Optional: hapus file fisik secara aman
            foreach ($toRemove as $relPath) {
                $relPath = ltrim((string) $relPath, '/');
                if ($relPath === '') continue;

                // cegah path traversal
                if (str_contains($relPath, '..')) continue;

                // batasi hanya folder uploads/sanctions
                if (strpos($relPath, 'uploads/sanctions/') !== 0) continue;

                $abs = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

                $realAbs  = realpath($abs);
                $realBase = realpath(rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sanctions');

                // realpath bisa false jika file sudah hilang, itu aman (skip)
                if ($realAbs && $realBase && strpos($realAbs, $realBase) === 0 && is_file($realAbs)) {
                    @unlink($realAbs);
                }
            }

            $existingJson = !empty($kept)
                ? json_encode($kept, JSON_UNESCAPED_SLASHES)
                : null;
        }

        // upload/merge dokumen baru (kalau ada), memakai existingJson yang sudah dibersihkan
        $documents = $this->handleDocumentsUpload(
            $this->request->getFileMultiple('documents'),
            $existingJson
        );

        $payload = [
            'sanction_type'          => trim($post['sanction_type']),
            'sanction_date'          => $post['sanction_date'],
            'start_date'             => $post['start_date'] ?: null,
            'end_date'               => $post['end_date'] ?: null,
            'description'            => trim($post['description']),
            'status'                 => $post['status'] ?: ($sanction['status'] ?? 'Dijadwalkan'),
            'completed_date'         => (($post['status'] ?? '') === 'Selesai')
                ? ($post['completed_date'] ?: ($post['sanction_date'] ?? date('Y-m-d')))
                : ($post['completed_date'] ?: null),
            'completion_notes'       => $post['completion_notes'] ?: null,
            'parent_acknowledged'    => !empty($post['parent_acknowledged']) ? 1 : 0,
            'parent_acknowledged_at' => !empty($post['parent_acknowledged'])
                ? ($post['parent_acknowledged_at'] ?: date('Y-m-d H:i:s'))
                : null,
            'documents'              => $documents,
            'notes'                  => $post['notes'] ?? null,
        ];

        // optional verify_now (Koordinator saja)
        if (is_koordinator() && !empty($post['verify_now'])) {
            $payload['verified_by'] = auth_id();
            $payload['verified_at'] = date('Y-m-d H:i:s');
        }

        if (!$this->sanctionModel->update($id, $payload)) {
            return redirect()->back()->withInput()->with('errors', $this->sanctionModel->errors());
        }

        return redirect()->to('/counselor/sanctions/show/' . $id)->with('success', 'Sanksi berhasil diperbarui.');
    }

    public function delete($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        // Koordinator dan Guru BK sama-sama boleh menghapus (soft delete) sesuai matriks peran
        $this->sanctionModel->delete($id);
        return redirect()->back()->with('success', 'Sanksi dihapus.');
    }

    // Aksi khusus agar kolom-kolom dipakai sesuai workflow
    public function complete($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $notes = $this->request->getPost('completion_notes') ?: null;
        $date  = $this->request->getPost('completed_date') ?: date('Y-m-d');

        $this->sanctionModel->update($id, [
            'status'           => 'Selesai',
            'completed_date'   => $date,
            'completion_notes' => $notes,
        ]);

        return redirect()->back()->with('success', 'Sanksi ditandai selesai.');
    }

    public function verify($id)
    {
        if ($r = $this->ensureAuth()) return $r;
        if (!is_koordinator()) {
            return redirect()->back()->with('error', 'Hanya Koordinator yang dapat memverifikasi.');
        }

        $this->sanctionModel->update($id, [
            'verified_by' => auth_id(),
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Sanksi terverifikasi.');
    }

    public function acknowledge($id)
    {
        if ($r = $this->ensureAuth()) return $r;

        $at = $this->request->getPost('parent_acknowledged_at') ?: date('Y-m-d H:i:s');

        $this->sanctionModel->update($id, [
            'parent_acknowledged'    => 1,
            'parent_acknowledged_at' => $at,
        ]);

        return redirect()->back()->with('success', 'Status orang tua mengetahui telah dicatat.');
    }

    /**
     * Upload multiple documents (images/pdf/doc/xls/mp4)
     * @param array|null $files
     * @param string|null $existingJson previous JSON to merge (optional)
     * @return string|null JSON string
     */
    private function handleDocumentsUpload(?array $files, ?string $existingJson = null)
    {
        if (!$files) return $existingJson ?? null;

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp4'];
        $max     = 5 * 1024 * 1024;

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sanctions';
        $ym      = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $target  = $baseDir . DIRECTORY_SEPARATOR . $ym;
        if (!is_dir($target)) @mkdir($target, 0775, true);

        $kept = is_string($existingJson) ? json_decode($existingJson, true) : [];
        if (!is_array($kept)) $kept = [];

        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) continue;

            $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;
            if ($file->getSize() > $max) continue;

            $new = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($target, $new);

            $rel = 'uploads/sanctions/' . str_replace(DIRECTORY_SEPARATOR, '/', $ym) . '/' . $new;
            $kept[] = $rel;
        }

        return $kept ? json_encode($kept, JSON_UNESCAPED_SLASHES) : ($existingJson ?? null);
    }
}
