<?php

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\SettingModel;
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
    protected SettingModel $settings;

    public function __construct()
    {
        $this->notif = new NotificationModel();
        $this->settings = new SettingModel();
        helper(['notification', 'url', 'form']);
    }

    public function index()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $items = $this->notif
            ->where('user_id', $uid)
            ->orderBy('created_at', 'DESC')
            ->paginate(20);

        return view($this->viewPrefix . '/index', [
            'title'     => 'Notifikasi Internal',
            'items'     => $this->maskBkDetail($items),
            'pager'     => $this->notif->pager,
            'basePath'  => trim($this->routePrefix, '/'),
            'roleLabel' => $this->roleLabel,
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

    public function preferences()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        return view($this->viewPrefix . '/preferences', [
            'title'       => 'Preferensi Notifikasi Internal',
            'basePath'    => trim($this->routePrefix, '/'),
            'roleLabel'   => $this->roleLabel,
            'types'       => $this->notificationTypes(),
            'preferences' => $this->readPreferences($uid),
        ]);
    }

    public function updatePreferences()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $enabled = (array)$this->request->getPost('enabled');
        $payload = [];
        foreach (array_keys($this->notificationTypes()) as $type) {
            $payload[$type] = array_key_exists($type, $enabled) ? 1 : 0;
        }

        $key = 'user_' . $uid;
        $existing = $this->settings
            ->where('group', 'notification_preferences')
            ->where('key', $key)
            ->first();

        $data = [
            'group'    => 'notification_preferences',
            'key'      => $key,
            'value'    => json_encode($payload, JSON_UNESCAPED_SLASHES),
            'type'     => 'json',
            'autoload' => 0,
        ];

        if ($existing) {
            $this->settings->update((int)$existing['id'], $data);
        } else {
            $this->settings->insert($data);
        }

        return redirect()->to(site_url($this->routePrefix . '/notifications/preferences'))
            ->with('success', 'Preferensi notifikasi berhasil disimpan.');
    }

    /**
     * Sembunyikan rincian notifikasi layanan BK untuk Siswa & Orang Tua.
     * Hanya notifikasi bertipe 'session' (jadwal/undangan layanan BK) yang
     * dipaksa menjadi garis besar aman; tipe lain (pesan, asesmen, konsultasi
     * milik sendiri) tetap apa adanya.
     */
    protected function maskBkDetail(array $items): array
    {
        if (! $this->restrictBkDetail) {
            return $items;
        }

        foreach ($items as &$item) {
            if (($item['type'] ?? '') === 'session') {
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

    protected function notificationTypes(): array
    {
        return [
            'message'              => 'Pesan internal',
            'assessment'           => 'Asesmen',
            'career'               => 'Fitur info karier dan info studi lanjut',
            'session'              => 'Sesi konseling',
            'info'                 => 'Informasi umum',
            'success'              => 'Pemberitahuan berhasil',
            'warning'              => 'Peringatan',
            'danger'               => 'Penting/darurat',
        ];
    }

    protected function readPreferences(int $uid): array
    {
        $defaults = array_fill_keys(array_keys($this->notificationTypes()), 1);
        $value = $this->settings->getValue('user_' . $uid, 'notification_preferences', []);

        if (!is_array($value)) {
            return $defaults;
        }

        foreach ($defaults as $type => $default) {
            $defaults[$type] = array_key_exists($type, $value) ? (int)(bool)$value[$type] : $default;
        }

        return $defaults;
    }
}
