<?php

/**
 * File: app/Controllers/Student/BkScheduleController.php
 * Fitur: Halaman terpadu "Jadwal Kegiatan/Acara BK" untuk Siswa.
 * Melebur 5 layanan BK (Bimbingan, Konseling, Kolaborasi Orang Tua, Kunjungan
 * Rumah, Konferensi Kasus) menjadi satu halaman. JADWAL SAJA (tanggal–waktu–
 * lokasi), tanpa detail catatan layanan BK.
 */

namespace App\Controllers\Student;

use App\Services\BkServiceService;

class BkScheduleController extends BaseStudentController
{
    public function index()
    {
        $this->requireStudent();

        $service = new BkServiceService();

        return view('role_features/bk_schedule/index', [
            'title'                  => 'Jadwal Kegiatan/Acara BK',
            'role'                   => 'siswa',
            'schedule'               => $service->scheduleByType('siswa', (int) $this->userId, 'upcoming'),
            'showDetailEye'          => false,
            'showAssessmentReminder' => false,
        ]);
    }
}
