<?php

/**
 * File: app/Controllers/HomeroomTeacher/BkScheduleController.php
 * Fitur: Halaman terpadu "Jadwal Kegiatan/Acara BK" untuk Wali Kelas.
 * Melebur 5 layanan BK menjadi satu halaman. Bawaan: jadwal saja. Entri yang
 * diizinkan Koordinator/Guru BK (visible_to_homeroom) diberi tombol ikon mata
 * untuk membuka detail. Punya halaman Riwayat (akses dari dalam halaman Jadwal).
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Services\BkServiceService;

class BkScheduleController extends BaseController
{
    private function currentUserId(): int
    {
        return (int) (session('user_id') ?? session('id') ?? 0);
    }

    public function index()
    {
        helper(['url']);
        $service = new BkServiceService();

        return view('role_features/bk_schedule/index', [
            'title'         => 'Jadwal Kegiatan/Acara BK',
            'role'          => 'wali-kelas',
            'schedule'      => $service->scheduleByType('wali-kelas', $this->currentUserId(), 'upcoming'),
            'showDetailEye' => true,
            'isHistory'     => false,
            'historyUrl'    => [
                'history' => site_url('homeroom/jadwal-bk/riwayat'),
                'back'    => site_url('homeroom/jadwal-bk'),
            ],
        ]);
    }

    public function history()
    {
        helper(['url']);
        $service = new BkServiceService();

        return view('role_features/bk_schedule/index', [
            'title'         => 'Riwayat Kegiatan/Acara BK',
            'role'          => 'wali-kelas',
            'schedule'      => $service->scheduleByType('wali-kelas', $this->currentUserId(), 'past'),
            'showDetailEye' => true,
            'isHistory'     => true,
            'historyUrl'    => [
                'history' => site_url('homeroom/jadwal-bk/riwayat'),
                'back'    => site_url('homeroom/jadwal-bk'),
            ],
        ]);
    }
}
