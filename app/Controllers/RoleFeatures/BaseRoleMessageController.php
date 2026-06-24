<?php

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\MessageAttachmentModel;
use App\Models\MessageModel;
use App\Models\MessageParticipantModel;
use App\Models\UserModel;

/**
 * Pesan Internal — pendekatan media sosial (ala WhatsApp).
 *
 * Hanya dua halaman: HALAMAN UTAMA (daftar percakapan + mulai percakapan baru +
 * hapus percakapan terpilih) dan HALAMAN PERCAKAPAN (gelembung chat 1-lawan-1).
 *
 * Model data: tabel `conversations` (pasangan 2 pengguna, soft delete per pihak)
 * + `messages` (tiap baris = satu gelembung, bertaut `conversation_id`)
 * + `message_participants` (penanda dibaca untuk penerima tiap gelembung)
 * + `message_attachments` (lampiran).
 *
 * Aturan penerima per peran (matriks kerahasiaan) TETAP ditegakkan di sisi server.
 */
abstract class BaseRoleMessageController extends BaseController
{
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected string $roleLabel = '';

    protected MessageModel $message;
    protected MessageParticipantModel $participant;
    protected MessageAttachmentModel $attachment;
    protected ConversationModel $conversation;
    protected UserModel $user;

    /** Batas unggah lampiran pesan. */
    protected int $maxAttachments = 5;
    protected int $maxAttachmentSizeKb = 5120; // 5 MB per berkas
    /** @var list<string> Ekstensi yang diizinkan. */
    protected array $allowedAttachmentExt = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'];

    public function __construct()
    {
        $this->message      = new MessageModel();
        $this->participant  = new MessageParticipantModel();
        $this->attachment   = new MessageAttachmentModel();
        $this->conversation = new ConversationModel();
        $this->user         = new UserModel();

        helper(['form', 'notification', 'url', 'auth']);
    }

    // ===================================================================
    // HALAMAN UTAMA — daftar percakapan
    // ===================================================================

    public function index()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $conversations = $this->conversationList($uid);

        // Pilihan pengguna untuk memulai percakapan baru (sesuai matriks penerima).
        $recipients = $this->usersMeta($this->allowedRecipientIds($uid, (int) session('role_id')));
        // Urutkan: per peran lalu nama.
        usort($recipients, static function ($a, $b) {
            return ($a['role_id'] <=> $b['role_id']) ?: strcasecmp((string) $a['full_name'], (string) $b['full_name']);
        });

        return $this->render('index', [
            'title'         => 'Pesan',
            'conversations' => $conversations,
            'recipients'    => $recipients,
        ]);
    }

    // ===================================================================
    // HALAMAN PERCAKAPAN
    // ===================================================================

    public function chat($otherId)
    {
        $uid     = $this->currentUserId();
        $otherId = (int) $otherId;
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $conv = $this->conversation->findPair($uid, $otherId);

        // Lawan bicara harus diizinkan (matriks) ATAU sudah pernah ada percakapan.
        $allowed = in_array($otherId, $this->allowedRecipientIds($uid, (int) session('role_id')), true);
        if ($otherId <= 0 || $otherId === $uid || (! $allowed && ! $conv)) {
            return redirect()->to(site_url($this->routePrefix . '/messages'))
                ->with('error', 'Pengguna tidak ditemukan atau tidak dapat dikirimi pesan.');
        }

        $meta  = $this->usersMeta([$otherId]);
        $other = $meta[$otherId] ?? null;
        if (! $other) {
            return redirect()->to(site_url($this->routePrefix . '/messages'))
                ->with('error', 'Pengguna tidak ditemukan.');
        }

        $messages = [];
        $lastId   = 0;
        if ($conv) {
            $messages = $this->conversationMessages((int) $conv['id'], $uid);
            $lastId   = $messages ? (int) end($messages)['id'] : 0;
            // Tandai gelembung masuk sebagai sudah dibaca.
            $this->markConversationRead((int) $conv['id'], $uid);
        }

        return $this->render('chat', [
            'title'          => 'Percakapan',
            'other'          => $other,
            'conversationId' => $conv['id'] ?? null,
            'messages'       => $messages,
            'lastMessageId'  => $lastId,
            // Aturan lampiran (sumber tunggal di controller) untuk validasi & petunjuk di view.
            'attachMax'      => $this->maxAttachments,
            'attachMaxMb'    => (int) round($this->maxAttachmentSizeKb / 1024),
            'attachExts'     => array_values($this->allowedAttachmentExt),
        ]);
    }

    /**
     * Kirim gelembung pesan ke lawan bicara. Membuat percakapan bila belum ada
     * (percakapan baru baru tersimpan saat pesan pertama dikirim).
     */
    public function send($otherId)
    {
        $uid     = $this->currentUserId();
        $otherId = (int) $otherId;
        if ($uid <= 0) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(401)->setJSON(['status' => 'unauthenticated'])
                : redirect()->to('/login');
        }

        $body = trim((string) $this->request->getPost('body'));

        // Penegakan matriks penerima (sisi server).
        $allowed = in_array($otherId, $this->allowedRecipientIds($uid, (int) session('role_id')), true);
        if ($otherId <= 0 || $otherId === $uid || ! $allowed) {
            return $this->fail('Penerima tidak diizinkan.');
        }

        $hasFiles = $this->hasAttachments();
        if ($body === '' && ! $hasFiles) {
            return $this->fail('Isi pesan tidak boleh kosong.');
        }
        if (mb_strlen($body) > 5000) {
            return $this->fail('Isi pesan maksimal 5000 karakter.');
        }
        if ($err = $this->validateAttachments()) {
            return $this->fail($err);
        }

        $conv   = $this->conversation->findPair($uid, $otherId);
        $convId = $conv ? (int) $conv['id'] : $this->createConversation($uid, $otherId);
        if ($convId <= 0) {
            return $this->fail('Gagal memulai percakapan.');
        }

        $msgId = (int) $this->message->insert([
            'subject'         => '',
            'body'            => $body,
            'created_by'      => $uid,
            'conversation_id' => $convId,
        ], true);

        if ($msgId <= 0) {
            return $this->fail('Gagal mengirim pesan.');
        }

        $this->storeAttachments($msgId, $uid);

        $now = date('Y-m-d H:i:s');
        $this->participant->insert([
            'message_id' => $msgId,
            'user_id'    => $otherId,
            'role'       => 'recipient',
            'is_read'    => 0,
            'read_at'    => null,
            'starred'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Perbarui ringkasan percakapan + munculkan kembali bila pernah dihapus salah satu pihak.
        $this->conversation->update($convId, [
            'last_message_id' => $msgId,
            'last_message_at' => $now,
            'one_deleted_at'  => null,
            'two_deleted_at'  => null,
        ]);

        // Notifikasi ke penerima → mengarah ke percakapan dengan pengirim.
        $prefixes = $this->recipientRoutePrefixes([$otherId]);
        $preview  = trim(mb_substr(strip_tags($body !== '' ? $body : 'Mengirim lampiran'), 0, 90));
        send_notification(
            $otherId,
            'Pesan Baru',
            'Pesan dari ' . $this->currentUserName($uid),
            'message',
            ['conversation_id' => $convId, 'preview' => $preview],
            '/' . ($prefixes[$otherId] ?? $this->routePrefix) . '/messages/chat/' . $uid
        );

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'ok',
                'message' => $this->bubblePayload($msgId, $uid, $body, $now),
                'csrf'    => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url($this->routePrefix . '/messages/chat/' . $otherId));
    }

    /**
     * Polling gelembung baru pada percakapan terbuka (id > after).
     */
    public function poll($otherId)
    {
        $uid     = $this->currentUserId();
        $otherId = (int) $otherId;
        if ($uid <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'unauthenticated']);
        }

        $conv = $this->conversation->findPair($uid, $otherId);
        if (! $conv) {
            return $this->response->setJSON(['messages' => [], 'last_id' => 0]);
        }

        $after = (int) $this->request->getGet('after');
        $rows  = $this->conversationMessages((int) $conv['id'], $uid, $after);
        $this->markConversationRead((int) $conv['id'], $uid);

        $lastId = $rows ? (int) end($rows)['id'] : $after;

        return $this->response->setJSON([
            'messages' => array_map(fn($r) => [
                'id'         => (int) $r['id'],
                'body'       => $r['body'],
                'mine'       => ((int) $r['created_by'] === $uid),
                'created_at' => $r['created_at'],
                'time'       => date('H:i', strtotime($r['created_at'])),
                'attachments' => $r['attachments'],
            ], $rows),
            'last_id' => $lastId,
        ]);
    }

    /**
     * Ringkasan untuk pembaruan otomatis halaman utama (badge belum dibaca + cuplikan).
     */
    public function summary()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'unauthenticated']);
        }

        $list  = $this->conversationList($uid);
        $total = 0;
        $items = [];
        foreach ($list as $c) {
            $total += (int) $c['unread'];
            $items[] = [
                'conversation_id' => (int) $c['id'],
                'other_id'        => (int) $c['other_id'],
                'unread'          => (int) $c['unread'],
                'preview'         => $c['preview'],
                'time'            => $c['time'],
            ];
        }

        return $this->response->setJSON(['total' => $total, 'items' => $items]);
    }

    /**
     * Hapus (soft delete) percakapan terpilih — hanya dari sisi penghapus.
     */
    public function delete()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return redirect()->to('/login');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('conversation_ids')))));
        if (empty($ids)) {
            return redirect()->to(site_url($this->routePrefix . '/messages'))
                ->with('error', 'Tidak ada percakapan yang dipilih.');
        }

        $now   = date('Y-m-d H:i:s');
        $count = 0;
        foreach ($ids as $cid) {
            $conv = $this->conversation->where('id', $cid)->where('deleted_at', null)->first();
            if (! $conv) {
                continue;
            }
            if ((int) $conv['user_one_id'] === $uid) {
                $this->conversation->update($cid, ['one_deleted_at' => $now]);
                $count++;
            } elseif ((int) $conv['user_two_id'] === $uid) {
                $this->conversation->update($cid, ['two_deleted_at' => $now]);
                $count++;
            }
        }

        return redirect()->to(site_url($this->routePrefix . '/messages'))
            ->with('success', $count . ' percakapan dihapus.');
    }

    // ===================================================================
    // Data percakapan (helper privat)
    // ===================================================================

    /**
     * Daftar percakapan milik $uid (yang belum dihapus dari sisinya & sudah berisi
     * minimal satu pesan), lengkap dengan meta lawan bicara, cuplikan, & jumlah belum dibaca.
     *
     * @return list<array<string,mixed>>
     */
    protected function conversationList(int $uid): array
    {
        $db = \Config\Database::connect();

        $rows = $db->table('conversations c')
            ->select('c.id, c.user_one_id, c.user_two_id, c.last_message_id, c.last_message_at')
            ->where('c.deleted_at', null)
            ->where('c.last_message_id IS NOT NULL')
            ->groupStart()
                ->groupStart()->where('c.user_one_id', $uid)->where('c.one_deleted_at', null)->groupEnd()
                ->orGroupStart()->where('c.user_two_id', $uid)->where('c.two_deleted_at', null)->groupEnd()
            ->groupEnd()
            ->orderBy('c.last_message_at', 'DESC')
            ->get()->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $otherIds = [];
        $lastIds  = [];
        $cids     = [];
        foreach ($rows as $r) {
            $cids[]     = (int) $r['id'];
            $otherIds[] = ((int) $r['user_one_id'] === $uid) ? (int) $r['user_two_id'] : (int) $r['user_one_id'];
            if (! empty($r['last_message_id'])) {
                $lastIds[] = (int) $r['last_message_id'];
            }
        }

        $meta = $this->usersMeta($otherIds);

        // Cuplikan pesan terakhir.
        $lastMsg = [];
        if ($lastIds) {
            foreach ($db->table('messages')->select('id, body, created_by')->whereIn('id', $lastIds)->get()->getResultArray() as $m) {
                $lastMsg[(int) $m['id']] = $m;
            }
        }

        // Jumlah belum dibaca per percakapan.
        $unread = [];
        $cntRows = $db->table('messages m')
            ->select('m.conversation_id AS cid, COUNT(*) AS n')
            ->join('message_participants mp', 'mp.message_id = m.id AND mp.user_id = ' . (int) $uid . ' AND mp.is_read = 0 AND mp.deleted_at IS NULL', 'inner')
            ->whereIn('m.conversation_id', $cids)
            ->where('m.deleted_at', null)
            ->groupBy('m.conversation_id')
            ->get()->getResultArray();
        foreach ($cntRows as $u) {
            $unread[(int) $u['cid']] = (int) $u['n'];
        }

        $out = [];
        foreach ($rows as $r) {
            $oid  = ((int) $r['user_one_id'] === $uid) ? (int) $r['user_two_id'] : (int) $r['user_one_id'];
            $info = $meta[$oid] ?? null;
            if (! $info) {
                continue; // lawan bicara sudah tidak ada
            }
            $lm      = $lastMsg[(int) $r['last_message_id']] ?? null;
            $preview = $lm ? trim(mb_substr(strip_tags((string) $lm['body']), 0, 60)) : '';
            if ($lm && trim((string) $lm['body']) === '') {
                $preview = '📎 Lampiran';
            }
            if ($lm && (int) $lm['created_by'] === $uid) {
                $preview = 'Anda: ' . $preview;
            }

            $out[] = array_merge($info, [
                'id'              => (int) $r['id'],
                'other_id'        => $oid,
                'unread'          => $unread[(int) $r['id']] ?? 0,
                'preview'         => $preview,
                'last_message_at' => $r['last_message_at'],
                'time'            => $r['last_message_at'] ? $this->humanTime($r['last_message_at']) : '',
            ]);
        }

        return $out;
    }

    /**
     * Gelembung sebuah percakapan (urut waktu naik), lengkap dengan lampiran.
     *
     * @return list<array<string,mixed>>
     */
    protected function conversationMessages(int $convId, int $uid, int $after = 0): array
    {
        $db = \Config\Database::connect();

        $q = $db->table('messages m')
            ->select('m.id, m.body, m.created_by, m.created_at')
            ->where('m.conversation_id', $convId)
            ->where('m.deleted_at', null);
        if ($after > 0) {
            $q->where('m.id >', $after);
        }
        $rows = $q->orderBy('m.id', 'ASC')->get()->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $ids = array_map(static fn($r) => (int) $r['id'], $rows);
        $att = [];
        foreach ($db->table('message_attachments')->select('id, message_id, file_name')
                     ->whereIn('message_id', $ids)->where('deleted_at', null)->orderBy('id', 'ASC')
                     ->get()->getResultArray() as $a) {
            $att[(int) $a['message_id']][] = [
                'id'   => (int) $a['id'],
                'name' => $a['file_name'],
                'url'  => site_url($this->routePrefix . '/messages/attachment/' . (int) $a['id']),
            ];
        }

        foreach ($rows as &$r) {
            $r['attachments'] = $att[(int) $r['id']] ?? [];
        }
        unset($r);

        return $rows;
    }

    /** Tandai semua gelembung masuk dalam percakapan sebagai sudah dibaca oleh $uid. */
    protected function markConversationRead(int $convId, int $uid): void
    {
        $db  = \Config\Database::connect();
        $ids = $db->table('messages')->select('id')->where('conversation_id', $convId)->where('deleted_at', null)
            ->get()->getResultArray();
        $ids = array_map(static fn($r) => (int) $r['id'], $ids);
        if (empty($ids)) {
            return;
        }
        $this->participant
            ->whereIn('message_id', $ids)
            ->where('user_id', $uid)
            ->where('is_read', 0)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();
    }

    protected function createConversation(int $uid, int $otherId): int
    {
        [$one, $two] = $uid <= $otherId ? [$uid, $otherId] : [$otherId, $uid];

        return (int) $this->conversation->insert([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'created_by'  => $uid,
        ], true);
    }

    /**
     * Meta tampilan tiap pengguna: id, nama, foto, peran, dan baris keterangan
     * (Siswa → kelas/tingkat/jurusan; Orang Tua → orang tua dari siswa siapa).
     *
     * @param  array<int> $ids
     * @return array<int,array<string,mixed>>  keyed by user id
     */
    protected function usersMeta(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));
        if (empty($ids)) {
            return [];
        }

        $db = \Config\Database::connect();

        $users = $db->table('users u')
            ->select('u.id, u.full_name, u.username, u.profile_photo, u.role_id, r.role_name')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->whereIn('u.id', $ids)
            ->get()->getResultArray();

        // Meta siswa (kelas/tingkat/jurusan).
        $studentMeta = [];
        foreach ($db->table('students s')
                     ->select('s.user_id, c.class_name, c.grade_level, c.major')
                     ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
                     ->whereIn('s.user_id', $ids)->where('s.deleted_at', null)
                     ->get()->getResultArray() as $s) {
            $studentMeta[(int) $s['user_id']] = $s;
        }

        // Meta orang tua (anak-anaknya).
        $parentChildren = [];
        foreach ($db->table('students s')
                     ->select('s.parent_id, cu.full_name AS child_name')
                     ->join('users cu', 'cu.id = s.user_id', 'left')
                     ->whereIn('s.parent_id', $ids)->where('s.deleted_at', null)
                     ->get()->getResultArray() as $p) {
            $pid = (int) $p['parent_id'];
            if (! empty($p['child_name'])) {
                $parentChildren[$pid][] = $p['child_name'];
            }
        }

        $out = [];
        foreach ($users as $u) {
            $id     = (int) $u['id'];
            $roleId = (int) $u['role_id'];
            $sub    = (string) ($u['role_name'] ?? '');

            if ($roleId === 5 && isset($studentMeta[$id])) {
                $sm    = $studentMeta[$id];
                $parts = [];
                if (! empty($sm['class_name'])) {
                    $parts[] = 'Kelas ' . $sm['class_name'];
                }
                if (! empty($sm['grade_level'])) {
                    $parts[] = 'Tingkat ' . $sm['grade_level'];
                }
                if (! empty($sm['major'])) {
                    $parts[] = 'Jurusan ' . $sm['major'];
                }
                if ($parts) {
                    $sub = 'Siswa — ' . implode(' · ', $parts);
                }
            } elseif ($roleId === 6 && ! empty($parentChildren[$id])) {
                $sub = 'Orang Tua dari ' . implode(', ', $parentChildren[$id]);
            }

            $out[$id] = [
                'id'            => $id,
                'full_name'     => $u['full_name'] ?: $u['username'],
                'profile_photo' => $u['profile_photo'],
                'role_id'       => $roleId,
                'role_name'     => $u['role_name'] ?? '',
                'subtitle'      => $sub,
            ];
        }

        return $out;
    }

    /** Payload satu gelembung (untuk respons AJAX kirim). */
    protected function bubblePayload(int $msgId, int $uid, string $body, string $now): array
    {
        $att = [];
        foreach ($this->attachment->where('message_id', $msgId)->where('deleted_at', null)->orderBy('id', 'ASC')->findAll() as $a) {
            $att[] = [
                'id'   => (int) $a['id'],
                'name' => $a['file_name'],
                'url'  => site_url($this->routePrefix . '/messages/attachment/' . (int) $a['id']),
            ];
        }

        return [
            'id'          => $msgId,
            'body'        => $body,
            'mine'        => true,
            'time'        => date('H:i', strtotime($now)),
            'attachments' => $att,
        ];
    }

    protected function humanTime(string $datetime): string
    {
        $ts = strtotime($datetime);
        if (! $ts) {
            return '';
        }
        if (date('Y-m-d', $ts) === date('Y-m-d')) {
            return date('H:i', $ts);
        }
        if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
            return 'Kemarin';
        }
        return date('d/m/Y', $ts);
    }

    protected function fail(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => $message, 'csrf' => csrf_hash()]);
        }
        return redirect()->back()->withInput()->with('error', $message);
    }

    protected function hasAttachments(): bool
    {
        $files = $this->request->getFileMultiple('attachments');
        if (empty($files)) {
            return false;
        }
        foreach ($files as $f) {
            if ($f && $f->getName() !== '') {
                return true;
            }
        }
        return false;
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
