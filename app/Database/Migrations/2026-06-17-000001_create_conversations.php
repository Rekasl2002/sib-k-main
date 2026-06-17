<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perbaikan Kedua (Item #3) — Layer percakapan ala media sosial (WhatsApp) untuk Pesan.
 *
 * Membuat tabel `conversations` (percakapan 1-lawan-1 antara dua pengguna) dan
 * menambahkan kolom `conversation_id` ke tabel `messages` (tiap baris messages =
 * satu gelembung chat di dalam percakapan).
 *
 * Pasangan pengguna disimpan terurut: user_one_id = id terkecil, user_two_id = id
 * terbesar → indeks unik mencegah percakapan ganda. Soft delete PER PIHAK
 * (one_deleted_at / two_deleted_at) agar menghapus percakapan di satu sisi tidak
 * menghapusnya di sisi lawan (akan muncul kembali bila ada pesan baru).
 *
 * Idempoten: aman dijalankan ulang.
 */
class CreateConversations extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('conversations')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_one_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'user_two_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'last_message_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'last_message_at' => ['type' => 'DATETIME', 'null' => true],
                // Soft delete per pihak: bila pihak ini menghapus percakapan dari daftarnya.
                'one_deleted_at'  => ['type' => 'DATETIME', 'null' => true],
                'two_deleted_at'  => ['type' => 'DATETIME', 'null' => true],
                'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
                'deleted_by'      => ['type' => 'INT', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['user_one_id', 'user_two_id']);
            $this->forge->addKey('last_message_at');
            $this->forge->createTable('conversations', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->fieldExists('conversation_id', 'messages')) {
            $this->forge->addColumn('messages', [
                'conversation_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'id',
                ],
            ]);
            $this->db->query('CREATE INDEX messages_conversation_id ON messages (conversation_id)');
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('conversation_id', 'messages')) {
            // Lepas index lebih dulu (abaikan bila tak ada), lalu kolomnya.
            try {
                $this->db->query('DROP INDEX messages_conversation_id ON messages');
            } catch (\Throwable $e) {
                // index mungkin sudah tidak ada — abaikan
            }
            $this->forge->dropColumn('messages', 'conversation_id');
        }

        $this->forge->dropTable('conversations', true);
    }
}
