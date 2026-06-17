<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perbaikan Kedua (Versi 2) - Konsultasi & Pengaduan.
 *
 * Perluas ENUM `request_type` agar memuat jenis laporan baru:
 *  - "Permintaan Bimbingan"             (minta dijadwalkan bimbingan kelompok/klasikal)
 *  - "Permintaan Informasi Karier/Studi" (minta info/arahan karier & studi lanjut)
 *  - "Permintaan Mediasi"               (minta bantuan menyelesaikan perselisihan)
 *
 * Idempoten: hanya MODIFY ENUM (aman dijalankan ulang). down() mengembalikan
 * ENUM ke kondisi sebelum migrasi ini (versi Fase 2).
 */
class ConsultationRequestTypesV2 extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
ALTER TABLE consultation_complaints
MODIFY request_type ENUM(
    'Konsultasi','Pengaduan','Permintaan Konseling',
    'Permintaan Bimbingan','Permintaan Informasi Karier/Studi','Permintaan Mediasi',
    'Laporan Orang Tua','Laporan Wali Kelas','Lainnya','Lainnya/Tidak Bisa Menentukan'
) NOT NULL DEFAULT 'Konsultasi'
SQL);
    }

    public function down(): void
    {
        $this->db->query(<<<'SQL'
ALTER TABLE consultation_complaints
MODIFY request_type ENUM(
    'Konsultasi','Pengaduan','Permintaan Konseling','Laporan Orang Tua',
    'Laporan Wali Kelas','Lainnya','Lainnya/Tidak Bisa Menentukan'
) NOT NULL DEFAULT 'Konsultasi'
SQL);
    }
}
