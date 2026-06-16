<?php

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Models\MessageAttachmentModel;
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
    protected MessageAttachmentModel $attachment;
    protected UserModel $user;

    /** Batas unggah lampiran pesan. */
    protected int $maxAttachments = 5;
    protected int $maxAttachmentSizeKb = 5120; // 5 MB per berkas
    /** @var list<string> Ekstensi yang diizinkan. */
    protected array $allowedAttachmentExt = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'];

    public function __construct()
    {
        $this->message     = new MessageModel();
        $this->participant = new MessageParticipantModel();
        $this->attachment  = new MessageAttachmentModel();
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

        return $this->render('compose', [
            'title'      => 'Tulis Pesan Internal',
            'recipients' => $this->allowedRecipients($uid),
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

        // Penegakan matriks penerima (sisi server): buang penerima yang tidak diizinkan.
        $to = array_values(array_intersect($to, $this->allowedRecipientIds($uid, (int) session('role_id'))));

        if ($subject === '' || $body === '' || empty($to)) {
            return redirect()->back()->withInput()->with('error', 'Penerima tidak diizinkan, atau subjek/isi pesan kosong.');
        }
        if (mb_strlen($subject) > 150) {
            return redirect()->back()->withInput()->with('error', 'Subjek maksimal 150 karakter.');
        }
        if (mb_strlen($body) > 5000) {
            return redirect()->back()->withInput()->with('error', 'Isi pesan maksimal 5000 karakter.');
        }
        if ($err = $this->validateAttachments()) {
            return redirect()->back()->withInput()->with('error', $err);
        }

        $msgId = (int) $this->message->insert([
            'subject'    => $subject,
            'body'       => $body,
            'created_by' => $uid,
        ], true);

        if ($msgId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengirim pesan.');
        }

        $this->storeAttachments($msgId, $uid);

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

        $attachments = $this->attachment
            ->where('message_id', $id)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->render('detail', [
            'title'        => 'Detail Pesan Internal',
            'msg'          => (array)$row,
            'participants' => $participants,
            'attachments'  => $attachments,
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
        if ($err = $this->validateAttachments()) {
            return redirect()->back()->with('error', $err);
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

        $this->storeAttachments($newId, $uid);

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

    /**
     * Unduh lampiran pesan. Hanya pengirim atau penerima pesan terkait yang boleh.
     */
    public function downloadAttachment($id)
    {
        $uid = $this->currentUserId();
        $id  = (int) $id;
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $att = $this->attachment->where('id', $id)->where('deleted_at', null)->first();
        if (! $att) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Lampiran tidak ditemukan.');
        }

        $msgId = (int) $att['message_id'];

        // Pastikan pengguna terhubung dengan pesan (pengirim atau penerima).
        $allowed = $this->message
            ->select('messages.id')
            ->join('message_participants', 'message_participants.message_id = messages.id', 'left')
            ->groupStart()
                ->where('messages.created_by', $uid)
                ->orWhere('message_participants.user_id', $uid)
            ->groupEnd()
            ->where('messages.id', $msgId)
            ->where('messages.deleted_at', null)
            ->first();

        if (! $allowed) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Akses lampiran ditolak.');
        }

        $fullPath = WRITEPATH . 'uploads/messages/' . $att['file_path'];
        if (! is_file($fullPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Berkas lampiran tidak tersedia.');
        }

        return $this->response->download($fullPath, null)->setFileName($att['file_name']);
    }

    /**
     * Validasi berkas lampiran (jumlah, ukuran, ekstensi). Mengembalikan pesan
     * kesalahan berbahasa sederhana, atau null bila lolos / tidak ada lampiran.
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
     * Pindahkan berkas lampiran ke disk dan catat metadata-nya.
     */
    protected function storeAttachments(int $messageId, int $uid): void
    {
        $files = $this->request->getFileMultiple('attachments');
        if (empty($files)) {
            return;
        }

        $dir = WRITEPATH . 'uploads/messages';
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
                log_message('error', '[MESSAGE] Gagal menyimpan lampiran: ' . $e->getMessage());
                continue;
            }

            $this->attachment->insert([
                'message_id'  => $messageId,
                'file_path'   => $newName,
                'file_name'   => mb_substr($file->getClientName(), 0, 255),
                'file_type'   => $file->getClientMimeType(),
                'file_size'   => $file->getSize(),
                'uploaded_by' => $uid,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    /**
     * Daftar id penerima yang DIIZINKAN untuk pengirim, sesuai matriks kerahasiaan
     * (timbal balik antar pihak yang terhubung).
     *
     * @return list<int>
     */
    protected function allowedRecipientIds(int $uid, int $roleId): array
    {
        $db = \Config\Database::connect();

        $usersByRole = static function (array $roleIds) use ($db): array {
            if (empty($roleIds)) {
                return [];
            }
            $rows = $db->table('users')->select('id')
                ->whereIn('role_id', $roleIds)
                ->where('is_active', 1)->where('deleted_at', null)
                ->get()->getResultArray();
            return array_map(static fn($r) => (int) $r['id'], $rows);
        };

        $ids = [];

        if ($roleId === 1) {            // Admin -> Koordinator, Guru BK, Wali Kelas
            $ids = $usersByRole([2, 3, 4]);
        } elseif ($roleId === 2) {      // Koordinator -> Admin, Guru BK, Wali Kelas
            $ids = $usersByRole([1, 3, 4]);
        } elseif ($roleId === 3) {      // Guru BK -> Admin, Koordinator, Wali Kelas + siswa binaan & ortunya
            $ids = array_merge($usersByRole([1, 2, 4]), $this->relatedStudentUserIds($db, 'c.counselor_id', $uid));
        } elseif ($roleId === 4) {      // Wali Kelas -> Admin, Koordinator, Guru BK + siswa binaan & ortunya
            $ids = array_merge($usersByRole([1, 2, 3]), $this->relatedStudentUserIds($db, 'c.homeroom_teacher_id', $uid));
        } elseif ($roleId === 5) {      // Siswa -> orang tua, wali kelas, guru bk (miliknya)
            $row = $db->table('students s')
                ->select('s.parent_id, c.homeroom_teacher_id, c.counselor_id')
                ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
                ->where('s.user_id', $uid)->where('s.deleted_at', null)
                ->get()->getRowArray();
            foreach (['parent_id', 'homeroom_teacher_id', 'counselor_id'] as $k) {
                if (! empty($row[$k])) {
                    $ids[] = (int) $row[$k];
                }
            }
        } elseif ($roleId === 6) {      // Orang Tua -> anak, wali kelas anak, guru bk anak
            $rows = $db->table('students s')
                ->select('s.user_id, c.homeroom_teacher_id, c.counselor_id')
                ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
                ->where('s.parent_id', $uid)->where('s.deleted_at', null)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                foreach (['user_id', 'homeroom_teacher_id', 'counselor_id'] as $k) {
                    if (! empty($r[$k])) {
                        $ids[] = (int) $r[$k];
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($ids, static fn($v) => (int) $v > 0 && (int) $v !== $uid)));
    }

    /**
     * Id user siswa + orang tua dari kelas yang dibina staf (Guru BK/Wali Kelas).
     *
     * @return list<int>
     */
    private function relatedStudentUserIds($db, string $classField, int $staffUid): array
    {
        $rows = $db->table('students s')
            ->select('s.user_id, s.parent_id')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'inner')
            ->where($classField, $staffUid)
            ->where('s.deleted_at', null)
            ->get()->getResultArray();

        $ids = [];
        foreach ($rows as $r) {
            if (! empty($r['user_id'])) {
                $ids[] = (int) $r['user_id'];
            }
            if (! empty($r['parent_id'])) {
                $ids[] = (int) $r['parent_id'];
            }
        }
        return $ids;
    }

    /**
     * Baris penerima yang diizinkan (untuk dropdown compose).
     *
     * @return list<array<string,mixed>>
     */
    protected function allowedRecipients(int $uid): array
    {
        $ids = $this->allowedRecipientIds($uid, (int) session('role_id'));
        if (empty($ids)) {
            return [];
        }

        return $this->user
            ->select('users.id, users.full_name, users.email, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->whereIn('users.id', $ids)
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->orderBy('roles.id', 'ASC')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
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
            1 => 'admin',
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
