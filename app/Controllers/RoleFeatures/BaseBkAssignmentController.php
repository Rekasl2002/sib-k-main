<?php

/**
 * File: app/Controllers/RoleFeatures/BaseBkAssignmentController.php
 * Fitur: Penugasan Guru BK.
 * Peran/izin: Koordinator BK mengelola tugas; Guru BK melihat dan mengubah
 * status tugasnya. Controller turunan dibuat per peran.
 * Berhubungan dengan: BkAssignmentService dan view assignments per peran.
 */

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Services\BkAssignmentService;

abstract class BaseBkAssignmentController extends BaseController
{
    protected string $roleKey = '';
    protected string $roleLabel = '';
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected bool $canManage = false;

    // Label fitur untuk judul halaman (bisa berbeda per peran, mis. "Tugas" di Guru BK).
    protected string $featureLabel = 'Penugasan';

    protected BkAssignmentService $service;

    public function __construct()
    {
        helper(['form', 'url', 'auth', 'permission', 'notification']);
        $this->service = new BkAssignmentService();
    }

    public function index()
    {
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'status' => trim((string) $this->request->getGet('status')),
            'assignment_type' => trim((string) $this->request->getGet('assignment_type')),
        ];

        return $this->render('index', [
            'title' => $this->featureLabel,
            'rows' => $this->service->list($this->roleKey, $this->currentUserId(), $filters),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        return $this->render('form', [
            'title' => 'Buat ' . $this->featureLabel,
            'row' => [],
            'options' => $this->service->formOptions(),
            'action' => site_url($this->routePrefix . '/store'),
        ]);
    }

    public function store()
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        if ($err = $this->validateRequired($this->request->getPost() ?? [])) {
            return redirect()->back()->withInput()->with('error', $err);
        }

        $id = $this->service->create($this->request->getPost() ?? [], $this->currentUserId());

        $this->notifyAssignment((int) $id, true);

        return redirect()->to(site_url($this->routePrefix . '/show/' . $id))->with('success', 'Penugasan berhasil dibuat.');
    }

    public function show($id)
    {
        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan atau tidak bisa diakses.');
        }

        return $this->render('show', [
            'title' => 'Detail ' . $this->featureLabel,
            'row' => $row,
            'options' => $this->service->formOptions(),
        ]);
    }

    public function edit($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan.');
        }

        return $this->render('form', [
            'title' => 'Edit ' . $this->featureLabel,
            'row' => $row,
            'options' => $this->service->formOptions(),
            'action' => site_url($this->routePrefix . '/update/' . (int) $id),
        ]);
    }

    public function update($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        if ($err = $this->validateRequired($this->request->getPost() ?? [])) {
            return redirect()->back()->withInput()->with('error', $err);
        }

        $this->service->update((int) $id, $this->request->getPost() ?? [], $this->currentUserId());

        $this->notifyAssignment((int) $id, false);

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))->with('success', 'Penugasan berhasil diperbarui.');
    }

    public function status($id)
    {
        // Penegakan akses: Guru BK hanya boleh memperbarui status tugas yang
        // ditujukan kepadanya. find() sudah membatasi cakupan per peran, sehingga
        // tugas milik orang lain tidak akan ditemukan dan permintaan ditolak.
        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan atau tidak bisa diakses.');
        }

        $this->service->updateStatus(
            (int) $id,
            (string) ($this->request->getPost('status') ?? 'Berjalan'),
            (string) ($this->request->getPost('note') ?? 'Status diperbarui.'),
            $this->currentUserId()
        );

        $this->notifyAssignment((int) $id, false);

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function delete($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $this->service->delete((int) $id);

        return redirect()->to(site_url($this->routePrefix))->with('success', 'Penugasan berhasil dihapus.');
    }

    protected function render(string $view, array $data = [])
    {
        $viewPath = $this->viewPrefix . '/assignments/' . $view;

        return view($viewPath, array_merge([
            'roleKey' => $this->roleKey,
            'roleLabel' => $this->roleLabel,
            'routePrefix' => trim($this->routePrefix, '/'),
            'canManage' => $this->canManage,
        ], $data));
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? session('id') ?? 0);
    }

    protected function deny()
    {
        return redirect()->to(site_url($this->routePrefix))->with('error', 'Akses tidak tersedia untuk peran ini.');
    }

    /**
     * Penegakan field wajib di sisi server (HTML required tidak berlaku untuk
     * input chip tersembunyi Guru BK). Mengembalikan pesan error atau null.
     */
    protected function validateRequired(array $post): ?string
    {
        $counselorIds = $post['assigned_to_user_ids'] ?? ($post['assigned_to_user_id'] ?? null);
        $hasCounselor = is_array($counselorIds)
            ? count(array_filter($counselorIds, static fn ($v) => (int) $v > 0)) > 0
            : ((int) $counselorIds > 0);

        if (! $hasCounselor) {
            return 'Guru BK yang ditugaskan wajib dipilih (minimal satu).';
        }
        if (trim((string) ($post['title'] ?? '')) === '') {
            return 'Judul/Topik/Masalah wajib diisi.';
        }
        if (trim((string) ($post['instruction'] ?? '')) === '') {
            return 'Instruksi wajib diisi.';
        }
        if (trim((string) ($post['due_at'] ?? '')) === '') {
            return 'Batas Waktu wajib diisi.';
        }
        if (trim((string) ($post['priority'] ?? '')) === '') {
            return 'Prioritas wajib dipilih.';
        }
        if (($post['assignment_type'] ?? '') === 'Lainnya' && trim((string) ($post['assignment_type_other'] ?? '')) === '') {
            return 'Karena Jenis Tugas "Lainnya", isi keterangan jenis tugasnya.';
        }

        return null;
    }

    /**
     * Beritahu pihak terkait penugasan.
     * - Tugas baru: beritahu penerima tugas (Guru BK).
     * - Perubahan status: beritahu pihak lain (pembuat & penerima selain pelaku).
     */
    protected function notifyAssignment(int $id, bool $isNew): void
    {
        if ($id <= 0) {
            return;
        }

        $db = \Config\Database::connect();
        $row = $db->table('bk_assignments')
            ->select('title, status, assigned_by, assigned_to_user_id')
            ->where('id', $id)
            ->get()->getRowArray();
        if (! $row) {
            return;
        }

        $title  = trim((string) ($row['title'] ?? '')) !== '' ? $row['title'] : 'Tanpa judul';
        $me     = $this->currentUserId();
        $assigner = (int) ($row['assigned_by'] ?? 0);

        // Semua Guru BK petugas (pivot) + target utama, agar tiap petugas diberi tahu.
        $assignees = $this->service->assigneeIds($id);
        if ((int) ($row['assigned_to_user_id'] ?? 0) > 0) {
            $assignees[] = (int) $row['assigned_to_user_id'];
        }
        $assignees = array_values(array_unique(array_filter($assignees)));

        if ($isNew) {
            $targets = $assignees;
            $heading = 'Penugasan Baru';
            $message = 'Anda menerima tugas baru: ' . $title;
        } else {
            $targets = array_merge($assignees, [$assigner]);
            $heading = 'Status Penugasan Diperbarui';
            $message = 'Status tugas "' . $title . '" kini: ' . ($row['status'] ?? '-');
        }

        foreach (array_unique($targets) as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0 || $uid === $me) {
                continue;
            }
            $prefix = role_route_prefix($uid);
            send_notification(
                $uid,
                $heading,
                $message,
                'info',
                ['assignment_id' => $id],
                $prefix !== '' ? '/' . $prefix . '/assignments/show/' . $id : null
            );
        }
    }
}
