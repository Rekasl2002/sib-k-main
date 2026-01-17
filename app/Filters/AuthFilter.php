<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('settings');

        $session = service('session');
        $now     = time();

        // Timeout idle (menit) dari Settings -> detik
        $maxIdle = (int) setting('session_timeout_minutes', 120, 'security') * 60;

        // Cek idle sebelum memperbarui jejak
        $last = (int) ($session->get('last_activity') ?? $now);
        if ($maxIdle > 0 && ($now - $last) > $maxIdle) {
            $session->destroy();
            return redirect()->to('/auth/login')->with('error', 'Sesi berakhir, silakan login kembali.');
        }
        // Perbarui jejak aktivitas
        $session->set('last_activity', $now);

        // Wajib login
        if (! $session->get('is_logged_in')) {
            $session->set('redirect_url', current_url());
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        // Akun harus aktif
        $userId = $session->get('user_id');
        if ($userId) {
            $userModel = new \App\Models\UserModel();
            $user      = $userModel->find($userId);

            if (! $user || (int)($user['is_active'] ?? 0) !== 1) {
                $session->destroy();
                return redirect()->to('/auth/login')->with('error', 'Akun Anda tidak aktif. Silakan hubungi administrator.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
