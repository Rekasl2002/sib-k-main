<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perbaikan Kedua — Info Karier dan Studi Lanjut.
 *
 * Menghapus kolom `is_public` dari `career_options` dan `university_info`.
 * Visibilitas data ke siswa/orang tua kini cukup ditentukan oleh satu kolom
 * `is_active` (label UI: "Ditampilkan / Disembunyikan").
 *
 * Indeks komposit (is_public, is_active) dibongkar lebih dulu lalu diganti
 * indeks tunggal pada is_active agar query publik tetap efisien.
 */
class DropIsPublicFromCareerTables extends Migration
{
    public function up()
    {
        foreach (['career_options' => 'idx_career_options_public_active', 'university_info' => 'idx_university_info_public_active'] as $table => $oldIndex) {
            $activeIndex = $table === 'career_options' ? 'idx_career_options_active' : 'idx_university_info_active';

            // 1) Buang indeks komposit lama yang memuat is_public
            if ($this->indexExists($table, $oldIndex)) {
                $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$oldIndex}`");
            }

            // 2) Hapus kolom is_public
            if ($this->db->fieldExists('is_public', $table)) {
                $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `is_public`");
            }

            // 3) Tambah indeks tunggal pada is_active (jika belum ada)
            if (! $this->indexExists($table, $activeIndex)) {
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$activeIndex}` (`is_active`)");
            }
        }
    }

    public function down()
    {
        foreach (['career_options' => 'idx_career_options_public_active', 'university_info' => 'idx_university_info_public_active'] as $table => $oldIndex) {
            $activeIndex = $table === 'career_options' ? 'idx_career_options_active' : 'idx_university_info_active';

            // 1) Buang indeks tunggal is_active
            if ($this->indexExists($table, $activeIndex)) {
                $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$activeIndex}`");
            }

            // 2) Kembalikan kolom is_public
            if (! $this->db->fieldExists('is_public', $table)) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`");
            }

            // 3) Kembalikan indeks komposit (is_public, is_active)
            if (! $this->indexExists($table, $oldIndex)) {
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$oldIndex}` (`is_public`, `is_active`)");
            }
        }
    }

    /** Cek keberadaan indeks bernama pada sebuah tabel. */
    private function indexExists(string $table, string $index): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = " . $this->db->escape($index))->getResultArray();
        return ! empty($rows);
    }
}
