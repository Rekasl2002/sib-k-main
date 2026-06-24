<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom `deleted_by` pada tabel Info Karier & Studi Lanjut
 * (`career_options` dan `university_info`) agar data hasil soft delete
 * dapat masuk ke fitur Tempat Sampah (terlihat & dapat dipulihkan oleh
 * pengguna yang menghapusnya).
 *
 * Kedua tabel sudah punya `deleted_at`, namun dikecualikan dari migrasi
 * 2026-06-15-000002 (bukan tabel proyek BK), sehingga ditambahkan di sini.
 *
 * Idempoten: hanya menambah kolom bila belum ada.
 */
class AddDeletedByToCareerInfo extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'career_options',
        'university_info',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $fields = $this->db->getFieldNames($table);
            if (! in_array('deleted_at', $fields, true)) {
                continue;
            }
            if (! in_array('deleted_by', $fields, true)) {
                $this->db->query('ALTER TABLE `' . $table . '` ADD deleted_by INT NULL AFTER deleted_at');
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            if (in_array('deleted_by', $this->db->getFieldNames($table), true)) {
                $this->db->query('ALTER TABLE `' . $table . '` DROP COLUMN deleted_by');
            }
        }
    }
}
