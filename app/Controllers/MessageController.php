<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MessageModel;
use App\Models\MessageParticipantModel;
use App\Models\UserModel;

/**
 * MessageController
 *
 * Fitur: Inbox, Sent, Compose, Send, Detail, Reply, Delete, Mark-as-read.
 * Catatan penting:
 * - Gunakan soft delete (deleted_at) sesuai konfigurasi model, bukan kolom is_deleted.
 * - Sesuaikan variabel view dengan file di app/Views/messages:
 *   - inbox.php  : membutuhkan $rows dan $pager
 *   - sent.php   : membutuhkan $rows dan $pager
 *   - compose.php: membutuhkan $recipients
 *   - detail.php : membutuhkan $msg
 */
class MessageController extends BaseController
{
    /** @var MessageModel */
    protected $message;

    /** @var MessageParticipantModel */
    protected $participant;

    /** @var UserModel */
    protected $user;

    public function __construct()
    {
        $this->message     = new MessageModel();
        $this->participant = new MessageParticipantModel();
        // UserModel opsional untuk daftar penerima
        if (class_exists(UserModel::class)) {
            $this->user = new UserModel();
        }
        // === penting untuk fungsi send_notification()
        helper(['form', 'notification']);
    }

    // ---------------------------------------------------------------------
    // Listing
    // ---------------------------------------------------------------------

    /**
     * Default -> redirect ke inbox
     */
    public function index()
    {
        return redirect()->to(route_to('messages.inbox'));
    }

    /**
     * Kotak masuk
     */
    public function inbox()
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $uid     = (int) session('user_id');
        $perPage = (int) ($this->request->getGet('per_page') ?: 10);

        // Ambil pesan di mana user menjadi participant (recipient) & belum soft-deleted
        $rows = $this->message
            ->select('messages.*, message_participants.is_read')
            ->join('message_participants', 'message_participants.message_id = messages.id', 'inner')
            ->where('message_participants.user_id', $uid)
            ->where('messages.deleted_at', null)
            ->where('message_participants.deleted_at', null)
            ->orderBy('messages.created_at', 'DESC')
            ->paginate($perPage);

        $data = [
            'title' => 'Kotak Masuk',
            'rows'  => $rows,
            'pager' => $this->message->pager,
        ];

        return view('messages/inbox', $data);
    }

    /**
     * Pesan terkirim
     */
    public function sent()
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $uid     = (int) session('user_id');
        $perPage = (int) ($this->request->getGet('per_page') ?: 10);

        $rows = $this->message
            ->select('messages.*')
            ->where('messages.created_by', $uid)
            ->where('messages.deleted_at', null)
            ->orderBy('messages.created_at', 'DESC')
            ->paginate($perPage);

        $data = [
            'title' => 'Terkirim',
            'rows'  => $rows,
            'pager' => $this->message->pager,
        ];

        return view('messages/sent', $data);
    }

    // ---------------------------------------------------------------------
    // Compose / Send
    // ---------------------------------------------------------------------

    /**
     * Form tulis pesan baru
     */
    public function compose()
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $recipients = [];
        if ($this->user) {
            // ambil id, full_name, email untuk pilihan penerima
            $recipients = $this->user
                ->select('id, full_name, email')
                ->where('is_active', 1)
                ->orderBy('full_name', 'ASC')
                ->findAll();
        }

        $data = [
            'title'      => 'Tulis Pesan',
            'recipients' => $recipients,
        ];

        return view('messages/compose', $data);
    }

    /**
     * Kirim pesan baru
     */
    public function send()
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $uid     = (int) session('user_id');
        $subject = trim((string) $this->request->getPost('subject'));
        $body    = (string) $this->request->getPost('body');
        $to      = (array) $this->request->getPost('to');

        if ($subject === '' || empty($to)) {
            return redirect()->back()->withInput()->with('error', 'Subjek dan penerima harus diisi.');
        }

        // Simpan pesan
        $msgId = $this->message->insert([
            'subject'    => $subject,
            'body'       => $body,
            'created_by' => $uid,
        ], true);

        if (! $msgId) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengirim pesan.');
        }

        // Simpan participants (penerima)
        $to = array_unique(array_map('intval', $to));
        $batch = [];
        $now   = date('Y-m-d H:i:s');
        foreach ($to as $rid) {
            if ($rid <= 0) continue;
            $batch[] = [
                'message_id' => $msgId,
                'user_id'    => $rid,
                'role'       => 'recipient',
                'is_read'    => 0,
                'read_at'    => null,
                'starred'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($batch) {
            $this->participant->insertBatch($batch);
        }

        // === TRIGGER NOTIFIKASI untuk setiap penerima ===
        // Nama pengirim untuk isi notifikasi
        $senderName = 'Pengguna';
        if (function_exists('auth_user')) {
            $au = auth_user();
            if (!empty($au['full_name'])) $senderName = $au['full_name'];
        }
        if ($senderName === 'Pengguna') {
            $row = \Config\Database::connect()
                ->table('users')->select('full_name')->where('id', $uid)
                ->get()->getRowArray();
            if (!empty($row['full_name'])) {
                $senderName = $row['full_name'];
            }
        }
        $preview = trim(mb_substr(strip_tags($body), 0, 80));
        if (mb_strlen($body) > 80) $preview .= '…';

        foreach ($to as $rid) {
            // simpan notifikasi (type 'message' sudah ada di helper-icon kamu)
            send_notification(
                (int) $rid,
                'Pesan Baru',
                "Pesan baru dari {$senderName}: " . ($subject ?: '(tanpa subjek)'),
                'message',
                ['message_id' => (int)$msgId, 'from_user_id' => (int)$uid, 'preview' => $preview],
                site_url('messages/detail/' . (int)$msgId) // ← link langsung ke detail
            );
        }

        return redirect()->to(route_to('messages.sent'))->with('success', 'Pesan terkirim.');
    }

    // ---------------------------------------------------------------------
    // Detail / Reply
    // ---------------------------------------------------------------------

    /**
     * Detail pesan
     */
    public function detail($id)
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $uid = (int) session('user_id');
        $id  = (int) $id;

        // User boleh melihat jika dia pengirim atau penerima (participant)
        $builder = $this->message
            ->select('messages.*')
            ->join('message_participants', 'message_participants.message_id = messages.id', 'left')
            ->groupStart()
                ->where('messages.created_by', $uid)
                ->orWhere('message_participants.user_id', $uid)
            ->groupEnd()
            ->where('messages.id', $id)
            ->where('messages.deleted_at', null);

        $row = $builder->first();

        if (! $row) {
            return redirect()->to(route_to('messages.inbox'))->with('error', 'Pesan tidak ditemukan atau akses ditolak');
        }

        // pastikan array untuk view
        $row = (array) $row;

        // Daftar participant
        $participants = $this->participant
            ->select('message_participants.*, users.full_name')
            ->join('users', 'users.id = message_participants.user_id', 'left')
            ->where('message_participants.message_id', $id)
            ->where('message_participants.deleted_at', null)
            ->findAll();

        // Tandai dibaca untuk penerima
        $this->participant
            ->where('message_id', $id)
            ->where('user_id', $uid)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        $data = [
            'title'        => 'Detail Pesan',
            'msg'          => $row,            // <- sesuai views/messages/detail.php
            'participants' => $participants,
        ];

        return view('messages/detail', $data);
    }

    /**
     * Balas pesan
     */
    public function reply($id)
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $uid  = (int) session('user_id');
        $id   = (int) $id;
        $body = (string) $this->request->getPost('body');

        if (trim($body) === '') {
            return redirect()->back()->with('error', 'Isi balasan tidak boleh kosong.');
        }

        // Pastikan user terkait dengan pesan
        $original = $this->message
            ->select('messages.*')
            ->join('message_participants', 'message_participants.message_id = messages.id', 'left')
            ->groupStart()
                ->where('messages.created_by', $uid)
                ->orWhere('message_participants.user_id', $uid)
            ->groupEnd()
            ->where('messages.id', $id)
            ->first();

        if (! $original) {
            return redirect()->to(route_to('messages.inbox'))->with('error', 'Pesan tidak ditemukan atau akses ditolak');
        }

        $original = (array) $original;

        // Buat pesan baru sebagai balasan
        $newId = $this->message->insert([
            'subject'    => 'Re: ' . ($original['subject'] ?? '(tanpa subjek)'),
            'body'       => $body,
            'created_by' => $uid,
        ], true);

        if (! $newId) {
            return redirect()->back()->with('error', 'Gagal mengirim balasan.');
        }

        // Penerima balasan: semua participant + pengirim sebelumnya, kecuali diri sendiri
        $recipientIds = [];

        // pengirim sebelumnya
        if (! empty($original['created_by']) && (int)$original['created_by'] !== $uid) {
            $recipientIds[] = (int) $original['created_by'];
        }

        // semua participant
        $parts = $this->participant
            ->select('user_id')
            ->where('message_id', $id)
            ->where('deleted_at', null)
            ->findAll();

        foreach ($parts as $p) {
            $pid = (int) ($p['user_id'] ?? 0);
            if ($pid > 0 && $pid !== $uid) {
                $recipientIds[] = $pid;
            }
        }

        $recipientIds = array_values(array_unique($recipientIds));

        if ($recipientIds) {
            $batch = [];
            $now   = date('Y-m-d H:i:s');
            foreach ($recipientIds as $rid) {
                $batch[] = [
                    'message_id' => $newId,
                    'user_id'    => $rid,
                    'role'       => 'recipient',
                    'is_read'    => 0,
                    'read_at'    => null,
                    'starred'    => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->participant->insertBatch($batch);

            // === TRIGGER NOTIFIKASI untuk penerima balasan ===
            $senderName = 'Pengguna';
            if (function_exists('auth_user')) {
                $au = auth_user();
                if (!empty($au['full_name'])) {
                    $senderName = $au['full_name'];
            }
            }
            if ($senderName === 'Pengguna') {
                $row = \Config\Database::connect()
                    ->table('users')->select('full_name')->where('id', $uid)
                    ->get()->getRowArray();
                if (!empty($row['full_name'])) {
                    $senderName = $row['full_name'];
                }
            }

            $preview = trim(mb_substr(strip_tags($body), 0, 80));
            if (mb_strlen($body) > 80) $preview .= '…';

            foreach ($recipientIds as $rid) {
                send_notification(
                    (int) $rid,
                    'Balasan Pesan',
                    "Balasan dari {$senderName}: " . ($original['subject'] ?? 'Pesan'),
                    'message',
                    ['message_id' => (int)$newId, 'reply_to' => (int)$id, 'preview' => $preview],
                    site_url('messages/detail/' . (int)$newId)
                );
            }
        }

        return redirect()->to(route_to('messages.detail', $newId))->with('success', 'Balasan terkirim.');
    }

    // ---------------------------------------------------------------------
    // Delete / Mark read
    // ---------------------------------------------------------------------

    /**
     * Hapus pesan.
     * - Jika pengirim: hapus (soft delete) pesan + partisipan
     * - Jika penerima: hapus (soft delete) partisipannya saja
     */
    public function delete($id)
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $uid = (int) session('user_id');
        $id  = (int) $id;

        $msg = $this->message->select('id, created_by')->where('id', $id)->first();
        if (! $msg) {
            return redirect()->to(route_to('messages.inbox'))->with('error', 'Pesan tidak ditemukan.');
        }

        $msg = (array) $msg;

        if ((int) $msg['created_by'] === $uid) {
            // pengirim: hapus pesan + semua participant
            $this->message->delete($id);
            $this->participant->where('message_id', $id)->delete();
        } else {
            // penerima: hapus partisipasi user ini
            $this->participant
                ->where('message_id', $id)
                ->where('user_id', $uid)
                ->delete();
        }

        return redirect()->to(route_to('messages.inbox'))->with('success', 'Pesan berhasil dihapus.');
    }

    /**
     * Tandai sebagai dibaca (AJAX).
     */
    public function markAsRead($id)
    {
        if (! function_exists('is_logged_in') || ! is_logged_in()) {
            // kembalikan JSON agar aman untuk panggilan AJAX
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthenticated']);
        }

        $uid = (int) session('user_id');
        $this->participant
            ->where('message_id', (int) $id)
            ->where('user_id', $uid)
            ->set([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ])->update();

        return $this->response->setJSON(['status' => 'ok']);
    }
}
