<?php

/**
 * File Path: app/Database/Migrations/2026-07-05-000002_RestoreMissingIndexes.php
 *
 * Menyamakan DB produksi dengan skema kanonis hasil menjalankan seluruh
 * migrasi dari nol. Audit 2026-07-05 menemukan produksi kekurangan 2 hal
 * (penyimpangan lama, kemungkinan efek cache guard migrasi 2026-06-09 saat
 * dijalankan dulu — bukan akibat insiden pemulihan):
 *
 * 1. FK fk_bk_service_assignment (bk_service_records.assignment_id ->
 *    bk_assignments.id) beserta indeks penunjangnya.
 * 2. Indeks UNIQUE pada students.nik (dibuat migrasi align_students_with_
 *    emis_identity di DB baru, hilang di produksi).
 *
 * Data sudah diverifikasi aman sebelum migrasi ini dibuat:
 * 334 NIK semua terisi & unik; tidak ada assignment_id yatim.
 * Guard existence membuat migrasi ini aman dijalankan di DB mana pun.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestoreMissingIndexes extends Migration
{
    public function up()
    {
        // 1) FK bk_service_records.assignment_id -> bk_assignments.id
        if (! $this->constraintExists('bk_service_records', 'fk_bk_service_assignment')) {
            $this->db->query(
                'ALTER TABLE bk_service_records
                 ADD CONSTRAINT fk_bk_service_assignment
                 FOREIGN KEY (assignment_id) REFERENCES bk_assignments(id)
                 ON UPDATE CASCADE ON DELETE SET NULL'
            );
        }

        // 2) UNIQUE index students.nik
        if (! $this->indexExists('students', 'nik')) {
            $this->db->query('ALTER TABLE students ADD UNIQUE KEY nik (nik)');
        }
    }

    public function down()
    {
        if ($this->constraintExists('bk_service_records', 'fk_bk_service_assignment')) {
            $this->db->query('ALTER TABLE bk_service_records DROP FOREIGN KEY fk_bk_service_assignment');
            if ($this->indexExists('bk_service_records', 'fk_bk_service_assignment')) {
                $this->db->query('ALTER TABLE bk_service_records DROP INDEX fk_bk_service_assignment');
            }
        }

        if ($this->indexExists('students', 'nik')) {
            $this->db->query('ALTER TABLE students DROP INDEX nik');
        }
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS c FROM information_schema.table_constraints
             WHERE constraint_schema = DATABASE() AND table_name = ? AND constraint_name = ?',
            [$table, $constraint]
        )->getRowArray();

        return (int) ($row['c'] ?? 0) > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS c FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        )->getRowArray();

        return (int) ($row['c'] ?? 0) > 0;
    }
}
