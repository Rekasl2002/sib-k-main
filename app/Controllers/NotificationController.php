<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\HTTP\ResponseInterface;

class NotificationController extends BaseController
{
    protected NotificationModel $notif;

    public function __construct()
    {
        $this->notif = new NotificationModel();

        // Optional helper: kalau helper tidak ada, jangan bikin crash.
        try {
            helper('notification');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Ambil user_id dari session (0 jika belum login).
     */
    protected function currentUserId(): int
    {
        return (int) session('user_id');
    }

    /**
     * Respon standar saat user belum login.
     * - AJAX: 401 JSON
     * - Non-AJAX: redirect ke halaman aman
     */
    protected function denyUnauthenticated(): ResponseInterface
    {
        if ($this->request->isAJAX()) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['status' => 'unauthenticated']);
        }

        // Sesuaikan kalau kamu punya route login spesifik
        return redirect()->to(site_url('/'));
    }

    /**
     * Helper: set header agar respon JSON tidak di-cache.
     */
    protected function noCache(): self
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        return $this;
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

        return view('notifications/index', [
            'items' => $items,
            'pager' => $this->notif->pager,
        ]);
    }

    public function unread()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        // Endpoint ini idealnya AJAX-only. Kalau kebuka di tab, arahkan ke halaman notifikasi.
        if (! $this->request->isAJAX()) {
            return redirect()->to(site_url('notifications'));
        }

        $items = $this->notif
            ->where('user_id', $uid)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll(20);

        $this->noCache();
        return $this->response->setJSON($items);
    }

    public function markAsRead($id)
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        // Kalau ada yang kebuka via GET di browser, jangan tampilkan JSON seperti "halaman kosong"
        if (! $this->request->isAJAX() && $this->request->getMethod() === 'get') {
            return redirect()->to(site_url('notifications'));
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'invalid_id']);
        }

        $ok = $this->notif
            ->where('id', $id)
            ->where('user_id', $uid)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->response->setJSON(['status' => $ok ? 'ok' : 'err']);
    }

    public function markAllAsRead()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        if (! $this->request->isAJAX() && $this->request->getMethod() === 'get') {
            return redirect()->to(site_url('notifications'));
        }

        $ok = $this->notif
            ->where('user_id', $uid)
            ->where('is_read', 0)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->response->setJSON(['status' => $ok ? 'ok' : 'err']);
    }

    public function delete($id)
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        if (! $this->request->isAJAX() && $this->request->getMethod() === 'get') {
            return redirect()->to(site_url('notifications'));
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'invalid_id']);
        }

        $row = $this->notif
            ->where('id', $id)
            ->where('user_id', $uid)
            ->first();

        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'not_found']);
        }

        $this->notif->delete($id);

        return $this->response->setJSON(['status' => 'ok']);
    }

    public function getUnreadCount()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        // Ini inti perbaikan untuk kasus "halaman kosong":
        // kalau endpoint dibuka langsung, jangan tampil JSON, redirect saja.
        if (! $this->request->isAJAX()) {
            return redirect()->to(site_url('notifications'));
        }

        $count = $this->notif
            ->where('user_id', $uid)
            ->where('is_read', 0)
            ->countAllResults();

        $this->noCache();
        return $this->response->setJSON(['count' => (int) $count]);
    }
}
