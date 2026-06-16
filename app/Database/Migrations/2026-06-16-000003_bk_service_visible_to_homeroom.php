<?php

/**
 * File: app/Database/Migrations/2026-06-16-000003_bk_service_visible_to_homeroom.php
 * Fitur: Layanan BK (Fase 4 — pelunasan utang).
 * Menambah kolom `visible_to_homeroom` pada bk_service_records sebagai alih
 * fungsi opsi "dirahasiakan" lama: izin apakah catatan layanan BK boleh dilihat
 * Wali Kelas yang bersangkutan. Bawaan MATI (0) — Wali Kelas hanya melihat
 * jadwal, kecuali diizinkan Koordinator BK/Guru BK per data.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BkServiceVisibleToHomeroom extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('visible_to_homeroom', 'bk_service_records')) {
            $this->forge->addColumn('bk_service_records', [
                'visible_to_homeroom' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'privacy_level',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('visible_to_homeroom', 'bk_service_records')) {
            $this->forge->dropColumn('bk_service_records', 'visible_to_homeroom');
        }
    }
}
