<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 0 - Fondasi perbaikan SIB-K.
 *
 * 1) Menambah kolom data tambahan siswa: `hobi` dan `ekskul_organisasi`
 *    agar informasi siswa lebih lengkap bagi Guru BK & Koordinator BK
 *    (dan dapat diisi mandiri oleh Siswa/Orang Tua di luar field terkunci).
 * 2) Menambah kolom `deleted_by` pada semua tabel proyek yang punya `deleted_at`
 *    untuk fitur Tempat Sampah: data hasil soft delete hanya terlihat oleh
 *    pengguna yang menghapusnya, lalu bisa dipulihkan atau dihapus permanen.
 *
 * Idempoten: hanya menambah kolom bila belum ada, sehingga aman dijalankan ulang.
 */
class AddProfileAndTrashColumns extends Migration
{
    /**
     * Tabel proyek yang mengikuti fitur Tempat Sampah (memakai soft delete `deleted_at`).
     *
     * @var list<string>
     */
    private array $trashTables = [
        'bk_service_records',
        'guidances',
        'counseling_sessions',
        'parent_collaborations',
        'home_visits',
        'case_conferences',
        'session_participants',
        'session_notes',
        'consultation_complaints',
        'consultation_complaint_attachments',
        'bk_assignments',
        'bk_assignment_status_histories',
        'messages',
        'message_participants',
        'notifications',
        'students',
        'assessments',
    ];

    public function up(): void
    {
        // 1) Kolom data tambahan siswa.
        if (! $this->columnExists('students', 'hobi')) {
            $this->db->query('ALTER TABLE students ADD hobi VARCHAR(255) NULL AFTER disability');
        }
        if (! $this->columnExists('students', 'ekskul_organisasi')) {
            $this->db->query('ALTER TABLE students ADD ekskul_organisasi VARCHAR(255) NULL AFTER hobi');
        }

        // 2) Kolom deleted_by untuk Tempat Sampah (hanya bila tabel ada & punya deleted_at).
        foreach ($this->trashTables as $table) {
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
        foreach (['ekskul_organisasi', 'hobi'] as $column) {
            if ($this->columnExists('students', $column)) {
                $this->db->query('ALTER TABLE students DROP COLUMN ' . $column);
            }
        }

        foreach ($this->trashTables as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            if ($this->columnExists($table, 'deleted_by')) {
                $this->db->query('ALTER TABLE `' . $table . '` DROP COLUMN deleted_by');
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        return in_array($column, $this->db->getFieldNames($table), true);
    }
}
