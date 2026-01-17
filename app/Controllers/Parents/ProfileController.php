<?php
// app/Controllers/Parents/ProfileController.php

namespace App\Controllers\Parents;

use App\Controllers\BaseController;

class ProfileController extends BaseController
{
    /**
     * GET /parent/profile
     * Arahkan ke Profil Global (/profile?mode=edit) sesuai kebijakan baru.
     */
    public function edit()
    {
        // pastikan user login
        if (! session('user_id')) {
            return redirect()->to('/login');
        }

        return redirect()
            ->to('/profile?mode=edit')
            ->with('info', 'Pengaturan Email/Telepon/Foto dipindahkan ke Profil Akun.');
    }

    /**
     * POST /parent/profile
     * Secara rute, endpoint ini sudah dipetakan langsung ke \App\Controllers\ProfileController::update.
     * Method ini disediakan hanya sebagai fallback jika ada pemanggilan langsung.
     */
    public function update()
    {
        if (! session('user_id')) {
            return redirect()->to('/login');
        }

        // Fallback aman: arahkan ke endpoint global agar validasi & policy terpusat.
        // (Jika rute sudah benar, method ini tidak akan terpakai.)
        return redirect()
            ->to('/profile')
            ->with('info', 'Silakan ubah profil melalui halaman Profil Akun.');
    }
}
