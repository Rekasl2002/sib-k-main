<?php

/**
 * File: app/Controllers/RoleFeatures/BaseConsultationController.php
 * Fitur: Konsultasi dan Pengaduan.
 * Peran/izin: Pelapor membuat dan melihat aduan sendiri; Koordinator BK/Guru
 * BK meninjau dan memproses; Wali Kelas melihat aduan terkait kelas/perannya.
 * Berhubungan dengan: ConsultationComplaintService dan view consultation per peran.
 */

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Services\ConsultationComplaintService;

abstract class BaseConsultationController extends BaseController
{
    protected string $roleKey = '';
    protected string $roleLabel = '';
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected bool $canSubmit = false;
    protected bool $canReview = false;

    protected ConsultationComplaintService $service;

    public function __construct()
    {
        helper(['form', 'url', 'auth', 'permission', 'notification']);
        $this->service = new ConsultationComplaintService();
    }

    public function index()
    {
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'status' => trim((string) $this->request->getGet('status')),
            'request_type' => trim((string) $this->request->getGet('request_type')),
            'priority' => trim((string) $this->request->getGet('priority')),
        ];

        return $this->render('index', [
            'title' => 'Konsultasi & Pengaduan',
            'rows' => $this->service->list($this->roleKey, $this->currentUserId(), $filters),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        if (! $this->canSubmit && ! $this->canReview) {
            return $this->deny();
        }

        return $this->render('form', [
            'title' => 'Ajukan Konsultasi atau Pengaduan',
            'row' => [],
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
            'action' => site_url($this->routePrefix . '/store'),
        ]);
    }

    public function store()
    {
        if ($r = $this->gateAvailable()) {
            return $r;
        }
        if (! $this->canSubmit && ! $this->canReview) {
            return $this->deny();
        }

        $id = $this->service->create($this->request->getPost() ?? [], $this->roleKey, $this->currentUserId());

        $this->notifyHandlersNewComplaint((int) $id, (string) ($this->request->getPost('title') ?? ''));

        return redirect()->to(site_url($this->routePrefix . '/show/' . $id))
            ->with('success', 'Konsultasi atau pengaduan berhasil dikirim.');
    }

    public function show($id)
    {
        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan atau tidak bisa diakses.');
        }

        return $this->render('show', [
            'title' => 'Detail Konsultasi & Pengaduan',
            'row' => $row,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
        ]);
    }

    public function edit($id)
    {
        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan.');
        }
        if (! $this->canSubmit || ! in_array($row['status'] ?? '', ['Diajukan', 'Ditinjau'], true)) {
            return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))->with('error', 'Data tidak bisa diedit pada status ini.');
        }

        return $this->render('form', [
            'title' => 'Edit Konsultasi & Pengaduan',
            'row' => $row,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
            'action' => site_url($this->routePrefix . '/update/' . (int) $id),
        ]);
    }

    public function update($id)
    {
        if ($r = $this->gateAvailable()) {
            return $r;
        }
        if (! $this->canSubmit) {
            return $this->deny();
        }

        $this->service->update((int) $id, $this->request->getPost() ?? []);

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))->with('success', 'Data berhasil diperbarui.');
    }

    public function review($id)
    {
        if ($r = $this->gateAvailable()) {
            return $r;
        }
        if (! $this->canReview) {
            return $this->deny();
        }

        $this->service->review((int) $id, $this->request->getPost() ?? [], $this->currentUserId());

        $this->notifyReporterStatusChange((int) $id);

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))->with('success', 'Status berhasil diperbarui.');
    }

    public function delete($id)
    {
        if ($r = $this->gateAvailable()) {
            return $r;
        }

        $res = $this->service->deleteOwn((int) $id, $this->currentUserId());

        return redirect()->to(site_url($this->routePrefix))
            ->with($res['success'] ? 'success' : 'error', $res['message']);
    }

    /**
     * Gerbang ketersediaan fitur sesuai sakelar Pengaturan Admin (Fase 0).
     * Mengembalikan RedirectResponse bila fitur dimatikan untuk peran ini, atau null.
     */
    protected function gateAvailable()
    {
        helper('consultation');
        $roleName = str_replace('-', ' ', $this->roleKey);

        if (function_exists('consultation_role_can_view') && ! consultation_role_can_view($roleName)) {
            $base   = explode('/', trim($this->routePrefix, '/'))[0] ?: 'dashboard';
            $target = $base === 'dashboard' ? '/dashboard' : '/' . $base . '/dashboard';

            return redirect()->to(site_url($target))
                ->with('error', 'Fitur Konsultasi & Pengaduan sedang tidak tersedia untuk peran Anda.');
        }

        return null;
    }

    protected function render(string $view, array $data = [])
    {
        if ($r = $this->gateAvailable()) {
            return $r;
        }

        $viewPath = $this->viewPrefix . '/consultation/' . $view;

        return view($viewPath, array_merge([
            'roleKey' => $this->roleKey,
            'roleLabel' => $this->roleLabel,
            'routePrefix' => trim($this->routePrefix, '/'),
            'canSubmit' => $this->canSubmit,
            'canReview' => $this->canReview,
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
     * Beritahu penangani (Koordinator BK & Guru BK) bahwa ada laporan baru.
     */
    protected function notifyHandlersNewComplaint(int $id, string $title): void
    {
        if ($id <= 0) {
            return;
        }

        $db = \Config\Database::connect();
        $handlers = $db->table('users')
            ->select('id')
            ->whereIn('role_id', [2, 3])
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->get()->getResultArray();

        $reporter = $this->currentUserId();
        $title = trim($title) !== '' ? $title : 'Tanpa judul';

        foreach ($handlers as $h) {
            $hid = (int) ($h['id'] ?? 0);
            if ($hid <= 0 || $hid === $reporter) {
                continue;
            }
            $prefix = role_route_prefix($hid);
            send_notification(
                $hid,
                'Konsultasi & Pengaduan Baru',
                'Ada laporan baru: ' . $title,
                'info',
                ['complaint_id' => $id],
                $prefix !== '' ? site_url($prefix . '/consultations/show/' . $id) : null
            );
        }
    }

    /**
     * Beritahu pelapor bahwa status laporannya berubah.
     */
    protected function notifyReporterStatusChange(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $db = \Config\Database::connect();
        $row = $db->table('consultation_complaints')
            ->select('reporter_user_id, title, status')
            ->where('id', $id)
            ->get()->getRowArray();

        $reporterId = (int) ($row['reporter_user_id'] ?? 0);
        if ($reporterId <= 0 || $reporterId === $this->currentUserId()) {
            return;
        }

        $prefix = role_route_prefix($reporterId);
        send_notification(
            $reporterId,
            'Status Konsultasi & Pengaduan Diperbarui',
            'Laporan "' . ($row['title'] ?? 'Tanpa judul') . '" kini berstatus: ' . ($row['status'] ?? '-'),
            'info',
            ['complaint_id' => $id],
            $prefix !== '' ? site_url($prefix . '/consultations/show/' . $id) : null
        );
    }
}
