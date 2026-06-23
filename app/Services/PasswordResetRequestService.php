<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\PasswordResetRequestModel;
use CodeIgniter\HTTP\IncomingRequest;

class PasswordResetRequestService
{
    public function createAdminRequest(
        ?string $email,
        ?string $phone,
        ?array $user,
        IncomingRequest $request
    ): array {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $requestId = null;

        if ($db->tableExists('password_reset_requests')) {
            $model = new PasswordResetRequestModel();
            $requestId = (int) $model->insert([
                'user_id'      => $user['id'] ?? null,
                'email'        => $email ?: null,
                'phone'        => $phone ?: null,
                'status'       => 'pending',
                'requested_ip' => $request->getIPAddress(),
                'user_agent'   => substr($request->getUserAgent()->getAgentString(), 0, 255),
                'requested_at' => $now,
            ], true);
        }

        $admins = $this->activeAdmins();
        $notifyAdmins = $this->envBool('password_reset.notifyAdmins', true);
        $notificationId = null;
        $notificationCount = 0;

        // CATATAN: permintaan reset password TIDAK lagi membuat pesan di fitur Pesan.
        // Fitur Pesan kini berbentuk percakapan (ala WhatsApp) antar pengguna, bukan
        // kotak masuk surel. Pemberitahuan ke Admin cukup lewat Notifikasi (lonceng)
        // + halaman khusus Admin "Permintaan Reset Password" — keduanya tidak perlu
        // dibalas. Maka di sini hanya membuat Notifikasi.
        if ($notifyAdmins && $admins !== []) {
            $link = $this->actionLink($user, $email, $phone);

            $notificationIds = $this->createNotifications($admins, $email, $phone, $user, $link, $requestId);
            $notificationCount = count($notificationIds);
            $notificationId = $notificationIds[0] ?? null;

            if ($requestId && $db->tableExists('password_reset_requests')) {
                (new PasswordResetRequestModel())->update($requestId, [
                    'status'                => $notificationCount > 0 ? 'notified' : 'pending',
                    'admin_notification_id' => $notificationId,
                    'notified_at'           => $notificationCount > 0 ? $now : null,
                ]);
            }
        }

        return [
            'request_id'          => $requestId,
            'user_found'          => $user !== null,
            'admin_count'         => count($admins),
            'notification_count'  => $notificationCount,
            'admin_notifications' => $notifyAdmins,
        ];
    }

    public function sendResetLinkEmail(array $user, string $resetUrl): bool
    {
        $emailAddress = trim((string)($user['email'] ?? ''));
        if ($emailAddress === '') {
            return false;
        }

        $email = service('email');
        $email->setTo($emailAddress);
        $email->setSubject('Reset Password SIB-K');
        $email->setMessage(view('emails/reset_password', [
            'name'      => $user['full_name'] ?? 'Pengguna',
            'resetUrl'  => $resetUrl,
            'expiresIn' => '1 jam',
        ]));

        if ($email->send()) {
            return true;
        }

        log_message('error', '[PASSWORD RESET] Failed to send reset email: ' . $email->printDebugger(['headers']));
        return false;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function activeAdmins(): array
    {
        $db = db_connect();
        if (! $db->tableExists('users') || ! $db->tableExists('roles')) {
            return [];
        }

        return $db->table('users')
            ->select('users.id, users.full_name, users.email')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->whereIn('roles.role_name', ['Admin', 'Administrator'])
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->get()
            ->getResultArray();
    }

    private function actionLink(?array $user, ?string $email, ?string $phone): string
    {
        if ($user && ! empty($user['id'])) {
            return site_url('admin/users/edit/' . (int) $user['id']);
        }

        $keyword = $email ?: ($phone ?: '');
        return site_url('admin/users' . ($keyword !== '' ? ('?search=' . rawurlencode($keyword)) : ''));
    }

    /**
     * @param list<array<string,mixed>> $admins
     *
     * @return list<int>
     */
    private function createNotifications(
        array $admins,
        ?string $email,
        ?string $phone,
        ?array $user,
        string $link,
        ?int $requestId
    ): array {
        $db = db_connect();
        if (! $db->tableExists('notifications')) {
            return [];
        }

        $ids = [];
        $model = new NotificationModel();
        $contact = $email ?: ($phone ?: 'kontak tidak diisi');
        $name = $user['full_name'] ?? $contact;

        foreach ($admins as $admin) {
            try {
                $id = (int) $model->insert([
                    'user_id' => (int) $admin['id'],
                    'title'   => 'Permintaan Reset Password',
                    'message' => 'Permintaan reset password untuk ' . $name . '.',
                    'type'    => 'password_reset',
                    'link'    => $link,
                    'data'    => json_encode([
                        'request_id' => $requestId,
                        'user_id'    => $user['id'] ?? null,
                        'email'      => $email,
                        'phone'      => $phone,
                    ], JSON_UNESCAPED_UNICODE),
                    'is_read' => 0,
                ], true);

                if ($id > 0) {
                    $ids[] = $id;
                }
            } catch (\Throwable $e) {
                log_message('error', '[PASSWORD RESET] Failed to create admin notification: ' . $e->getMessage());
            }
        }

        return $ids;
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = env($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
