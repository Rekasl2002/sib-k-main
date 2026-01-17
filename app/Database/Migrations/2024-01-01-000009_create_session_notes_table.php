<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000009_create_session_notes_table.php
 *
 * Session Notes Migration
 * Tabel untuk menyimpan catatan-catatan dari setiap sesi konseling
 * Satu sesi bisa memiliki multiple notes (progress tracking)
 *
 * @package    SIB-K
 * @subpackage Database/Migrations
 * @category   Counseling
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSessionNotesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'session_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'FK ke counseling_sessions',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'User ID yang membuat catatan (counselor)',
            ],
            'note_type' => [
                'type'       => 'ENUM',
                'constraint' => ['Observasi', 'Diagnosis', 'Intervensi', 'Follow-up', 'Lainnya'],
                'default'    => 'Observasi',
                'null'       => false,
                'comment'    => 'Jenis catatan',
            ],
            'note_content' => [
                'type'    => 'TEXT',
                'null'    => false,
                'comment' => 'Isi catatan sesi',
            ],
            // 🔸 kolom baru untuk fitur "Catatan Penting"
            'is_important' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'null'       => false,
                'comment'    => '0 = biasa, 1 = penting',
            ],
            'is_confidential' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
                'null'       => false,
                'comment'    => 'Apakah catatan ini rahasia (1 = ya, 0 = tidak)',
            ],
            'attachments' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'JSON array untuk path file lampiran',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        // Foreign keys
        $this->forge->addForeignKey('session_id', 'counseling_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'CASCADE');

        // Indexes
        $this->forge->addKey('session_id');
        $this->forge->addKey('created_by');
        $this->forge->addKey('note_type');
        $this->forge->addKey('is_important');                           // untuk filter catatan penting
        $this->forge->addKey(['session_id', 'created_at']);              // untuk listing cepat per sesi

        // Buat tabel (if not exists) dengan atribut engine/charset yang aman
        $this->forge->createTable('session_notes', true, [
            'ENGINE'  => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        // Drop table akan otomatis menghapus FK di MySQL/InnoDB
        $this->forge->dropTable('session_notes', true);
    }
}
