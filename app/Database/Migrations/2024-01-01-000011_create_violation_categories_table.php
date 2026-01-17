<?php

/**
 * File Path: app/Database/Migrations/2024-01-01-000011_create_violation_categories_table.php
 * 
 * Migration: Create Violation Categories Table
 * Tabel untuk menyimpan kategori pelanggaran siswa
 * 
 * @package    SIB-K
 * @subpackage Migrations
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViolationCategoriesTable extends Migration
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
            'category_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => 'Nama kategori pelanggaran',
            ],
            'severity_level' => [
                'type'       => 'ENUM',
                'constraint' => ['Ringan', 'Sedang', 'Berat'],
                'default'    => 'Ringan',
                'comment'    => 'Tingkat keparahan pelanggaran',
            ],
            'point_deduction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Poin yang dikurangi untuk pelanggaran ini',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Deskripsi detail kategori pelanggaran',
            ],
            'examples' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Contoh-contoh pelanggaran dalam kategori ini',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Status aktif kategori (1=aktif, 0=non-aktif)',
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

        // Primary Key
        $this->forge->addKey('id', true);

        // Indexes for performance
        $this->forge->addKey('severity_level');
        $this->forge->addKey('is_active');
        $this->forge->addKey(['deleted_at', 'is_active']); // Composite index for soft delete + active status

        // Create table
        $this->forge->createTable('violation_categories');

        // Add table comment
        $this->db->query("ALTER TABLE violation_categories COMMENT = 'Tabel kategori pelanggaran siswa'");
    }

    public function down()
    {
        $this->forge->dropTable('violation_categories');
    }
}
