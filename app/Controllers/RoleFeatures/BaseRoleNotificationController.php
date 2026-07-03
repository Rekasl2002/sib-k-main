<?php

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseRoleNotificationController extends BaseController
{
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected string $roleLabel = '';

    /**
     * Bila true (peran Siswa & Orang Tua), isi notifikasi terkait layanan BK
     * dipaksa hanya menampilkan garis besar aman (tanpa topik/detail) walau
     * isi tersimpan memuat rincian. Lapis pertahanan kerahasiaan di sisi tampilan.
     */
    protected bool $restrictBkDetail = false;

    protected NotificationModel $notif;

    public function __construct()
    {
        $this->notif = new NotificationModel();
        helper(['notification', 'url', 'form']);
    }

    public function index()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        // Muat seluruh notifikasi milik pengguna; paginasi ditangani DataTables di view.
        $items = $this->notif
            ->where('user_id', $uid)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $items = $this->maskBkDetail($items);

        $stats = ['total' => count($items), 'unread' => 0, 'read' => 0];
        foreach ($items as $it) {
            if (empty($it['is_read'])) {
                $stats['unread']++;
            } else {
                $stats['read']++;
            }
        }

        return view($this->viewPrefix . '/index', [
            'title'      => 'Notifikasi Internal',
            'items'      => $items,
            'stats'      => $stats,
            'categories' => notification_categories(),
            'basePath'   => trim($this->routePrefix, '/'),
            'roleLabel'  => $this->roleLabel,
        ]);
    }

    public function unread()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        if (! $this->request->isAJAX()) {
            return redirect()->to(site_url($this->routePrefix . '/notifications'));
        }

        $items = $this->notif
            ->where('user_id', $uid)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll(20);

        return $this->noCache()->response->setJSON($this->maskBkDetail($items));
    }

    public function markAsRead($id)
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $ok = $this->notif
            ->where('id', (int)$id)
            ->where('user_id', $uid)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => $ok ? 'ok' : 'err']);
        }

        return redirect()->to(site_url($this->routePrefix . '/notifications'));
    }

    public function markAllAsRead()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $this->notif
            ->where('user_id', $uid)
            ->where('is_read', 0)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'ok']);
        }

        return redirect()->to(site_url($this->routePrefix . '/notifications'));
    }

    public function delete($id)
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $row = $this->notif
            ->where('id', (int)$id)
            ->where('user_id', $uid)
            ->first();

        if ($row) {
            $this->notif->delete((int)$id);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => $row ? 'ok' : 'not_found']);
        }

        return redirect()->to(site_url($this->routePrefix . '/notifications'));
    }

    /**
     * Hapus (soft delete) SEMUA notifikasi milik pengguna saat ini.
     */
    public function deleteAll()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $ids = $this->notif->where('user_id', $uid)->findColumn('id') ?? [];
        if (!empty($ids)) {
            $this->notif->delete($ids);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'ok']);
        }

        return redirect()->to(site_url($this->routePrefix . '/notifications'))
            ->with('success', 'Semua notifikasi berhasil dihapus.');
    }

    public function getUnreadCount()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        if (! $this->request->isAJAX()) {
            return redirect()->to(site_url($this->routePrefix . '/notifications'));
        }

        $count = $this->notif
            ->where('user_id', $uid)
            ->where('is_read', 0)
            ->countAllResults();

        return $this->noCache()->response->setJSON(['count' => (int)$count]);
    }

    /**
     * Sembunyikan rincian notifikasi layanan BK untuk Siswa & Orang Tua.
     * Semua notifikasi yang berkategori detail layanan BK (jadwal & tindak
     * lanjut/catatan) dipaksa menjadi garis besar aman, sebab kedua peran ini
     * hanya boleh melihat JADWAL — bukan detail Bimbingan/Konseling/Kolaborasi
     * Orang Tua/Kunjungan Rumah/Konferensi Kasus. Tipe lain (pesan, asesmen,
     * konsultasi milik sendiri) tetap apa adanya.
     */
    protected function maskBkDetail(array $items): array
    {
        if (! $this->restrictBkDetail) {
            return $items;
        }

        foreach ($items as &$item) {
            if (notification_is_bk_detail((string)($item['type'] ?? ''))) {
                $item['title']   = 'Jadwal Kegiatan/Acara BK';
                $item['message'] = 'Ada jadwal kegiatan/acara BK untuk Anda. Silakan cek halaman Jadwal Kegiatan/Acara BK.';
            }
        }
        unset($item);

        return $items;
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? 0);
    }

    protected function denyUnauthenticated(): ResponseInterface
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'unauthenticated']);
        }

        return redirect()->to(site_url('/login'));
    }

    protected function noCache(): self
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        return $this;
    }
}
