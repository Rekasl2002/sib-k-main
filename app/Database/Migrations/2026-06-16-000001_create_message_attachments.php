<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 3 (utang Pesan) - Tabel lampiran Pesan Internal.
 *
 * Menyimpan berkas lampiran yang diunggah pada Pesan/Balasan. Mengikuti pola
 * `consultation_complaint_attachments`: file disimpan di disk, baris ini menyimpan
 * metadata (path, nama asli, tipe, ukuran, pengunggah). Soft delete + deleted_by
 * agar selaras fitur Tempat Sampah.
 *
 * Idempoten: hanya membuat tabel bila belum ada.
 */
class CreateMessageAttachments extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('message_attachments')) {
            return;
        }

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'message_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'file_path'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_name'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_type'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'file_size'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'uploaded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_by'  => ['type' => 'INT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('message_id');
        $this->forge->createTable('message_attachments', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('message_attachments', true);
    }
}
