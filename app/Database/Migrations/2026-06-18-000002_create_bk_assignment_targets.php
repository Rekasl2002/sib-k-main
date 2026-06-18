<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perbaikan Kedua (Versi 2) - Fitur Penugasan (Item #10).
 *
 * 1) Perluas ENUM `bk_assignments.assignment_type` dengan jenis tugas baru:
 *    Pelaksanaan Asesmen, Pelaksanaan Layanan, Administrasi & Laporan,
 *    Pendampingan Siswa, Lainnya.
 * 2) Buat tabel pivot `bk_assignment_targets` agar satu tugas dapat ditujukan
 *    ke BANYAK Guru BK (petugas), BANYAK kelas, dan BANYAK siswa sekaligus.
 *    Kolom lama `assigned_to_user_id`/`class_id`/`student_id` tetap dipakai
 *    sebagai target UTAMA/representatif (pilihan pertama) demi kompatibilitas.
 *
 * Idempoten sewajarnya. down() membuang pivot & mengembalikan ENUM ke 4 nilai
 * awal (kondisi sebelum migrasi ini).
 */
class CreateBkAssignmentTargets extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
ALTER TABLE bk_assignments
MODIFY assignment_type ENUM(
    'Kelas Binaan','Tugas Layanan','Tindak Lanjut','Koordinasi',
    'Pelaksanaan Asesmen','Pelaksanaan Layanan','Administrasi & Laporan',
    'Pendampingan Siswa','Lainnya'
) NOT NULL DEFAULT 'Tugas Layanan'
SQL);

        // Isian bebas untuk jenis tugas "Lainnya".
        if (! $this->db->fieldExists('assignment_type_other', 'bk_assignments')) {
            $this->db->query("ALTER TABLE bk_assignments ADD COLUMN assignment_type_other VARCHAR(150) NULL AFTER assignment_type");
        }

        if (! $this->db->tableExists('bk_assignment_targets')) {
            $this->db->query(<<<'SQL'
CREATE TABLE bk_assignment_targets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    target_type ENUM('counselor','class','student') NOT NULL,
    user_id INT UNSIGNED NULL,
    class_id INT UNSIGNED NULL,
    student_id INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_bk_assignment_targets_assignment (assignment_id),
    INDEX idx_bk_assignment_targets_type (target_type),
    INDEX idx_bk_assignment_targets_user (user_id),
    INDEX idx_bk_assignment_targets_class (class_id),
    INDEX idx_bk_assignment_targets_student (student_id),
    CONSTRAINT fk_bk_assignment_targets_assignment FOREIGN KEY (assignment_id) REFERENCES bk_assignments(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_bk_assignment_targets_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_bk_assignment_targets_class FOREIGN KEY (class_id) REFERENCES classes(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_bk_assignment_targets_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('bk_assignment_targets')) {
            $this->db->query('DROP TABLE bk_assignment_targets');
        }

        if ($this->db->fieldExists('assignment_type_other', 'bk_assignments')) {
            $this->db->query('ALTER TABLE bk_assignments DROP COLUMN assignment_type_other');
        }

        $this->db->query(<<<'SQL'
ALTER TABLE bk_assignments
MODIFY assignment_type ENUM(
    'Kelas Binaan','Tugas Layanan','Tindak Lanjut','Koordinasi'
) NOT NULL DEFAULT 'Tugas Layanan'
SQL);
    }
}
