<?php

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Models\MessageModel;
use App\Models\MessageParticipantModel;
use App\Models\UserModel;

abstract class BaseRoleMessageController extends BaseController
{
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected string $roleLabel = '';

    protected MessageModel $message;
    protected MessageParticipantModel $participant;
    protected UserModel $user;

    public function __construct()
    {
        $this->message     = new MessageModel();
        $this->participant = new MessageParticipantModel();
        $this->user        = new UserModel();

        helper(['form', 'notification', 'url', 'auth']);
    }

    public function index()
    {
        return $this->inbox();
    }

    public function inbox()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $perPage = (int) ($this->request->getGet('per_page') ?: 10);
        $rows = $this->message
            ->select('messages.*, message_participants.is_read, sender.full_name AS sender_name')
            ->join('message_participants', 'message_participants.message_id = messages.id', 'inner')
            ->join('users sender', 'sender.id = messages.created_by', 'left')
            ->where('message_participants.user_id', $uid)
            ->where('messages.deleted_at', null)
            ->where('message_participants.deleted_at', null)
            ->orderBy('messages.created_at', 'DESC')
            ->paginate($perPage);

        return $this->render('inbox', [
            'title' => 'Kotak Masuk Pesan Internal',
            'rows'  => $rows,
            'pager' => $this->message->pager,
        ]);
    }

    public function sent()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $perPage = (int) ($this->request->getGet('per_page') ?: 10);
        $rows = $this->message
            ->select('messages.*')
            ->where('messages.created_by', $uid)
            ->where('messages.deleted_at', null)
            ->orderBy('messages.created_at', 'DESC')
            ->paginate($perPage);

        return $this->render('sent', [
            'title' => 'Pesan Terkirim',
            'rows'  => $rows,
            'pager' => $this->message->pager,
        ]);
    }

    public function compose()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $recipients = $this->user
            ->select('users.id, users.full_name, users.email, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->whereIn('users.role_id', [2, 3, 4, 5, 6])
            ->where('users.id !=', $uid)
            ->orderBy('roles.id', 'ASC')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();

        return $this->render('compose', [
            'title'      => 'Tulis Pesan Internal',
            'recipients' => $recipients,
        ]);
    }

    public function send()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $subject = trim((string) $this->request->getPost('subject'));
        $body    = trim((string) $this->request->getPost('body'));
        $to      = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('to')))));

        if ($subject === '' || $body === '' || empty($to)) {
            return redirect()->back()->withInput()->with('error', 'Penerima, subjek, dan isi pesan wajib diisi.');
        }

        $msgId = (int) $this->message->insert([
            'subject'    => $subject,
            'body'       => $body,
            'created_by' => $uid,
        ], true);

        if ($msgId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengirim pesan.');
        }

        $now = date('Y-m-d H:i:s');
        $batch = [];
        foreach ($to as $rid) {
            if ($rid <= 0 || $rid === $uid) {
                continue;
            }
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

        $senderName = $this->currentUserName($uid);
        $preview = trim(mb_substr(strip_tags($body), 0, 90));
        $routePrefixes = $this->recipientRoutePrefixes(array_column($batch, 'user_id'));
        foreach ($batch as $recipient) {
            $recipientId = (int)$recipient['user_id'];
            send_notification(
                $recipientId,
                'Pesan Internal Baru',
                "Pesan dari {$senderName}: {$subject}",
                'message',
                ['message_id' => $msgId, 'preview' => $preview],
                site_url(($routePrefixes[$recipientId] ?? $this->routePrefix) . '/messages/detail/' . $msgId)
            );
        }

        return redirect()->to(site_url($this->routePrefix . '/messages/sent'))
            ->with('success', 'Pesan berhasil dikirim.');
    }

    public function detail($id)
    {
        $uid = $this->currentUserId();
        $id  = (int) $id;
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $row = $this->message
            ->select('messages.*, sender.full_name AS sender_name')
            ->join('message_participants', 'message_participants.message_id = messages.id', 'left')
            ->join('users sender', 'sender.id = messages.created_by', 'left')
            ->groupStart()
                ->where('messages.created_by', $uid)
                ->orWhere('message_participants.user_id', $uid)
            ->groupEnd()
            ->where('messages.id', $id)
            ->where('messages.deleted_at', null)
            ->first();

        if (!$row) {
            return redirect()->to(site_url($this->routePrefix . '/messages/inbox'))
                ->with('error', 'Pesan tidak ditemukan atau akses ditolak.');
        }

        $participants = $this->participant
            ->select('message_participants.*, users.full_name, users.email')
            ->join('users', 'users.id = message_participants.user_id', 'left')
            ->where('message_participants.message_id', $id)
            ->where('message_participants.deleted_at', null)
            ->findAll();

        $this->participant
            ->where('message_id', $id)
            ->where('user_id', $uid)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->render('detail', [
            'title'        => 'Detail Pesan Internal',
            'msg'          => (array)$row,
            'participants' => $participants,
        ]);
    }

    public function edit($id)
    {
        $uid = $this->currentUserId();
        $id  = (int) $id;
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $row = $this->message
            ->where('id', $id)
            ->where('created_by', $uid)
            ->where('deleted_at', null)
            ->first();

        if (!$row) {
            return redirect()->to(site_url($this->routePrefix . '/messages/sent'))
                ->with('error', 'Pesan tidak ditemukan atau tidak dapat diedit.');
        }

        return $this->render('edit', [
            'title' => 'Edit Pesan Internal',
            'msg'   => (array)$row,
        ]);
    }

    public function update($id)
    {
        $uid = $this->currentUserId();
        $id  = (int) $id;
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $subject = trim((string) $this->request->getPost('subject'));
        $body    = trim((string) $this->request->getPost('body'));

        if ($subject === '' || $body === '') {
            return redirect()->back()->withInput()->with('error', 'Subjek dan isi pesan wajib diisi.');
        }

        $row = $this->message
            ->where('id', $id)
            ->where('created_by', $uid)
            ->where('deleted_at', null)
            ->first();

        if (!$row) {
            return redirect()->to(site_url($this->routePrefix . '/messages/sent'))
                ->with('error', 'Pesan tidak ditemukan atau tidak dapat diedit.');
        }

        $ok = $this->message->update($id, [
            'subject' => $subject,
            'body'    => $body,
        ]);

        return redirect()->to(site_url($this->routePrefix . '/messages/detail/' . $id))
            ->with($ok ? 'success' : 'error', $ok ? 'Pesan berhasil diperbarui.' : 'Gagal memperbarui pesan.');
    }

    public function reply($id)
    {
        $uid = $this->currentUserId();
        $id  = (int) $id;
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $body = trim((string) $this->request->getPost('body'));
        if ($body === '') {
            return redirect()->back()->with('error', 'Isi balasan tidak boleh kosong.');
        }

        $original = $this->message
            ->select('messages.*')
            ->join('message_participants', 'message_participants.message_id = messages.id', 'left')
            ->groupStart()
                ->where('messages.created_by', $uid)
                ->orWhere('message_participants.user_id', $uid)
            ->groupEnd()
            ->where('messages.id', $id)
            ->first();

        if (!$original) {
            return redirect()->to(site_url($this->routePrefix . '/messages/inbox'))
                ->with('error', 'Pesan tidak ditemukan atau akses ditolak.');
        }

        $original = (array)$original;
        $newId = (int) $this->message->insert([
            'subject'    => 'Re: ' . ($original['subject'] ?? '(tanpa subjek)'),
            'body'       => $body,
            'created_by' => $uid,
        ], true);

        if ($newId <= 0) {
            return redirect()->back()->with('error', 'Gagal mengirim balasan.');
        }

        $recipientIds = [];
        if (!empty($original['created_by']) && (int)$original['created_by'] !== $uid) {
            $recipientIds[] = (int)$original['created_by'];
        }

        $parts = $this->participant
            ->select('user_id')
            ->where('message_id', $id)
            ->where('deleted_at', null)
            ->findAll();
        foreach ($parts as $part) {
            $pid = (int)($part['user_id'] ?? 0);
            if ($pid > 0 && $pid !== $uid) {
                $recipientIds[] = $pid;
            }
        }

        $recipientIds = array_values(array_unique($recipientIds));
        $routePrefixes = $this->recipientRoutePrefixes($recipientIds);
        $now = date('Y-m-d H:i:s');
        foreach ($recipientIds as $rid) {
            $this->participant->insert([
                'message_id' => $newId,
                'user_id'    => $rid,
                'role'       => 'recipient',
                'is_read'    => 0,
                'read_at'    => null,
                'starred'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            send_notification(
                $rid,
                'Balasan Pesan Internal',
                'Balasan dari ' . $this->currentUserName($uid),
                'message',
                ['message_id' => $newId, 'reply_to' => $id],
                site_url(($routePrefixes[$rid] ?? $this->routePrefix) . '/messages/detail/' . $newId)
            );
        }

        return redirect()->to(site_url($this->routePrefix . '/messages/detail/' . $newId))
            ->with('success', 'Balasan berhasil dikirim.');
    }

    public function delete($id)
    {
        $uid = $this->currentUserId();
        $id  = (int) $id;
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $msg = $this->message->select('id, created_by')->where('id', $id)->first();
        if (!$msg) {
            return redirect()->to(site_url($this->routePrefix . '/messages/inbox'))->with('error', 'Pesan tidak ditemukan.');
        }

        $msg = (array)$msg;
        if ((int)$msg['created_by'] === $uid) {
            $this->message->delete($id);
            $this->participant->where('message_id', $id)->delete();
        } else {
            $this->participant
                ->where('message_id', $id)
                ->where('user_id', $uid)
                ->delete();
        }

        return redirect()->to(site_url($this->routePrefix . '/messages/inbox'))->with('success', 'Pesan berhasil dihapus.');
    }

    public function markAsRead($id)
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'unauthenticated']);
        }

        $this->participant
            ->where('message_id', (int)$id)
            ->where('user_id', $uid)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->response->setJSON(['status' => 'ok']);
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? 0);
    }

    protected function currentUserName(int $uid): string
    {
        $name = (string)(session('full_name') ?? '');
        if ($name !== '') {
            return $name;
        }

        $row = $this->user->select('full_name, username')->find($uid);
        return (string)($row['full_name'] ?? $row['username'] ?? 'Pengguna');
    }

    protected function recipientRoutePrefixes(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return [];
        }

        $rows = $this->user
            ->select('id, role_id')
            ->whereIn('id', $userIds)
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $row = (array)$row;
            $map[(int)$row['id']] = $this->routePrefixForRole((int)($row['role_id'] ?? 0));
        }

        return $map;
    }

    protected function routePrefixForRole(int $roleId): string
    {
        return match ($roleId) {
            2 => 'koordinator',
            3 => 'counselor',
            4 => 'homeroom',
            5 => 'student',
            6 => 'parent',
            default => $this->routePrefix,
        };
    }

    protected function render(string $view, array $data = [])
    {
        return view($this->viewPrefix . '/' . $view, array_merge([
            'basePath'  => trim($this->routePrefix, '/'),
            'roleLabel' => $this->roleLabel,
        ], $data));
    }
}
