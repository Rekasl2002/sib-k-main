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
use App\Models\ConsultationComplaintAttachmentModel;
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
    protected ConsultationComplaintAttachmentModel $attachment;

    /** Batas unggah lampiran bukti. */
    protected int $maxAttachments = 5;
    protected int $maxAttachmentSizeKb = 5120; // 5 MB per berkas
    /** @var list<string> Ekstensi yang diizinkan. */
    protected array $allowedAttachmentExt = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'];

    public function __construct()
    {
        helper(['form', 'url', 'auth', 'permission', 'notification']);
        $this->service = new ConsultationComplaintService();
        $this->attachment = new ConsultationComplaintAttachmentModel();
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
            'stats' => $this->service->stats($this->roleKey, $this->currentUserId()),
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
        if ($err = $this->validateRequired($this->request->getPost() ?? [])) {
            return redirect()->back()->withInput()->with('error', $err);
        }
        if ($err = $this->validateAttachments()) {
            return redirect()->back()->withInput()->with('error', $err);
        }

        $id = $this->service->create($this->request->getPost() ?? [], $this->roleKey, $this->currentUserId());

        $this->storeAttachments((int) $id, $this->currentUserId());

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
            'subjects' => $this->service->subjects((int) $id),
            'attachments' => $this->attachment->where('complaint_id', (int) $id)->where('deleted_at', null)->orderBy('id', 'ASC')->findAll(),
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
            'subjects' => $this->service->subjects((int) $id),
            'attachments' => $this->attachment->where('complaint_id', (int) $id)->where('deleted_at', null)->orderBy('id', 'ASC')->findAll(),
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

        // Hanya pemilik yang boleh memperbarui isi laporannya.
        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row || (int) ($row['reporter_user_id'] ?? 0) !== $this->currentUserId()) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Anda hanya dapat mengubah laporan milik sendiri.');
        }
        if ($err = $this->validateRequired($this->request->getPost() ?? [])) {
            return redirect()->back()->withInput()->with('error', $err);
        }
        if ($err = $this->validateAttachments()) {
            return redirect()->back()->withInput()->with('error', $err);
        }

        $this->service->update((int) $id, $this->request->getPost() ?? [], $this->roleKey, $this->currentUserId());

        $this->storeAttachments((int) $id, $this->currentUserId());

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

        $this->service->review((int) $id, $this->request->getPost() ?? [], $this->currentUserId(), $this->roleKey);

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
     * Unduh berkas bukti. Hanya pengguna yang berhak melihat laporannya.
     */
    public function downloadAttachment($id)
    {
        $att = $this->attachment->where('id', (int) $id)->where('deleted_at', null)->first();
        if (! $att) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Lampiran tidak ditemukan.');
        }

        // Gerbang akses: laporan terkait harus dapat dilihat oleh peran/pengguna ini.
        $row = $this->service->find((int) $att['complaint_id'], $this->roleKey, $this->currentUserId());
        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Akses lampiran ditolak.');
        }

        $fullPath = WRITEPATH . 'uploads/consultations/' . $att['file_path'];
        if (! is_file($fullPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Berkas lampiran tidak tersedia.');
        }

        return $this->response->download($fullPath, null)->setFileName($att['file_name'] ?: $att['file_path']);
    }

    /**
     * Hapus berkas bukti. Hanya pengunggah berkas yang boleh.
     */
    public function deleteAttachment($id)
    {
        $att = $this->attachment->where('id', (int) $id)->where('deleted_at', null)->first();
        if (! $att) {
            return redirect()->back()->with('error', 'Lampiran tidak ditemukan.');
        }

        $complaintId = (int) $att['complaint_id'];

        if ((int) ($att['uploaded_by'] ?? 0) !== $this->currentUserId()) {
            return redirect()->to(site_url($this->routePrefix . '/show/' . $complaintId))
                ->with('error', 'Anda hanya dapat menghapus lampiran yang Anda unggah.');
        }

        $this->attachment->update((int) $id, ['deleted_by' => $this->currentUserId()]);
        $this->attachment->delete((int) $id);

        return redirect()->to(site_url($this->routePrefix . '/show/' . $complaintId))
            ->with('success', 'Lampiran dihapus.');
    }

    /**
     * Validasi kolom wajib (penegakan sisi server, melengkapi atribut "required"
     * pada form yang hanya berlaku di sisi peramban). Aturan (Perbaikan Kedua,
     * Item #9): Judul, Deskripsi, dan Waktu kejadian wajib untuk semua pelapor;
     * Lokasi wajib hanya bagi peninjau (Koordinator BK & Guru BK), opsional bagi
     * pelapor. Mengembalikan pesan kesalahan pertama, atau null bila lolos.
     *
     * @param array<string,mixed> $post
     */
    protected function validateRequired(array $post): ?string
    {
        if (trim((string) ($post['title'] ?? '')) === '') {
            return 'Judul/Topik wajib diisi.';
        }
        if (trim((string) ($post['description'] ?? '')) === '') {
            return 'Deskripsi wajib diisi.';
        }

        $occurred = trim((string) ($post['occurred_at'] ?? '')) !== ''
            || trim((string) ($post['occurred_date'] ?? '')) !== '';
        if (! $occurred) {
            return 'Waktu kejadian wajib diisi.';
        }

        if ($this->canReview && trim((string) ($post['location'] ?? '')) === '') {
            return 'Tempat/Lokasi wajib diisi bagi peninjau.';
        }

        return null;
    }

    /**
     * Validasi berkas lampiran (jumlah, ukuran, ekstensi). Pesan berbahasa sederhana.
     */
    protected function validateAttachments(): ?string
    {
        $files = $this->request->getFileMultiple('attachments');
        if (empty($files)) {
            return null;
        }

        $files = array_filter($files, static fn($f) => $f && $f->getName() !== '');
        if (empty($files)) {
            return null;
        }

        if (count($files) > $this->maxAttachments) {
            return 'Lampiran maksimal ' . $this->maxAttachments . ' berkas sekaligus.';
        }

        foreach ($files as $file) {
            if (! $file->isValid()) {
                return 'Berkas lampiran gagal diunggah, silakan coba lagi.';
            }
            if ($file->getSizeByUnit('kb') > $this->maxAttachmentSizeKb) {
                return 'Setiap berkas lampiran maksimal ' . (int) ($this->maxAttachmentSizeKb / 1024) . ' MB.';
            }
            $ext = strtolower((string) $file->getClientExtension());
            if (! in_array($ext, $this->allowedAttachmentExt, true)) {
                return 'Jenis berkas "' . esc($ext) . '" tidak diizinkan.';
            }
        }

        return null;
    }

    /**
     * Pindahkan berkas bukti ke disk (di luar public) dan catat metadata.
     */
    protected function storeAttachments(int $complaintId, int $uid): void
    {
        if ($complaintId <= 0) {
            return;
        }

        $files = $this->request->getFileMultiple('attachments');
        if (empty($files)) {
            return;
        }

        $dir = WRITEPATH . 'uploads/consultations';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $now = date('Y-m-d H:i:s');
        foreach ($files as $file) {
            if (! $file || $file->getName() === '' || ! $file->isValid()) {
                continue;
            }

            $newName = $file->getRandomName();
            try {
                $file->move($dir, $newName);
            } catch (\Throwable $e) {
                log_message('error', '[CONSULTATION] Gagal menyimpan lampiran: ' . $e->getMessage());
                continue;
            }

            $this->attachment->insert([
                'complaint_id' => $complaintId,
                'file_path'    => $newName,
                'file_name'    => mb_substr($file->getClientName(), 0, 255),
                'file_type'    => $file->getClientMimeType(),
                'file_size'    => $file->getSize(),
                'uploaded_by'  => $uid,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
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
