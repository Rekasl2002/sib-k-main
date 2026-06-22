<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perapian basis data — membuang kolom yang tidak terpakai di aplikasi.
 *
 * Hasil audit pemakaian kolom (form, controller, service, view, laporan):
 *  1. session_notes.related_participant_id
 *     Hanya dideklarasikan di allowedFields; tidak pernah diisi/dibaca.
 *  2. session_participants.participant_note
 *     Duplikat dari `participation_note`. BkServiceService menulis ke kedua
 *     kolom dari sumber yang sama, namun yang DIBACA (jadwal Siswa/Ortu/Wali
 *     Kelas) hanya `participation_note`. Kolom ini redundan.
 *  3. consultation_complaints.witness
 *     Kotak "Saksi / Pihak Terkait" sudah dihapus dari form pada Perbaikan
 *     Kedua #9; kolom tidak lagi diisi pengguna.
 *
 * Tidak ada indeks/foreign key yang menyentuh ketiga kolom, sehingga aman
 * di-drop langsung. `down()` mengembalikan ketiga kolom ke definisi semula.
 */
class DropUnusedColumnsCleanup extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('related_participant_id', 'session_notes')) {
            $this->db->query('ALTER TABLE `session_notes` DROP COLUMN `related_participant_id`');
        }

        if ($this->db->fieldExists('participant_note', 'session_participants')) {
            $this->db->query('ALTER TABLE `session_participants` DROP COLUMN `participant_note`');
        }

        if ($this->db->fieldExists('witness', 'consultation_complaints')) {
            $this->db->query('ALTER TABLE `consultation_complaints` DROP COLUMN `witness`');
        }
    }

    public function down()
    {
        // 1) session_notes.related_participant_id (semula int unsigned NULL, setelah related_to/is_important)
        if (! $this->db->fieldExists('related_participant_id', 'session_notes')) {
            $this->db->query('ALTER TABLE `session_notes` ADD COLUMN `related_participant_id` INT UNSIGNED NULL AFTER `attachments`');
        }

        // 2) session_participants.participant_note (semula text NULL, sebelum invitation_status)
        if (! $this->db->fieldExists('participant_note', 'session_participants')) {
            $this->db->query('ALTER TABLE `session_participants` ADD COLUMN `participant_note` TEXT NULL AFTER `role_in_session`');
        }

        // 3) consultation_complaints.witness (semula varchar(190) NULL, setelah location)
        if (! $this->db->fieldExists('witness', 'consultation_complaints')) {
            $this->db->query('ALTER TABLE `consultation_complaints` ADD COLUMN `witness` VARCHAR(190) NULL AFTER `location`');
        }
    }
}
