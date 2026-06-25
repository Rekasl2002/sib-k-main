<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom `deleted_by` pada tabel `users` agar akun pengguna hasil
 * soft delete dapat masuk ke fitur Tempat Sampah (terlihat & dapat dipulihkan
 * oleh pengguna yang menghapusnya, selaras dengan `students`).
 *
 * Tabel `users` sudah punya `deleted_at`, tetapi belum `deleted_by`, sehingga
 * akun yang dihapus tidak pernah muncul di Tempat Sampah (TrashService
 * memfilter berdasarkan `deleted_by`). Temuan TC-MU-08 (BUG-11).
 *
 * Idempoten: hanya menambah kolom bila belum ada.
 */
class AddDeletedByToUsers extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('users')) {
            return;
        }
        $fields = $this->db->getFieldNames('users');
        if (! in_array('deleted_at', $fields, true)) {
            return;
        }
        if (! in_array('deleted_by', $fields, true)) {
            $this->db->query('ALTER TABLE `users` ADD deleted_by INT NULL AFTER deleted_at');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('users')) {
            return;
        }
        if (in_array('deleted_by', $this->db->getFieldNames('users'), true)) {
            $this->db->query('ALTER TABLE `users` DROP COLUMN deleted_by');
        }
    }
}
