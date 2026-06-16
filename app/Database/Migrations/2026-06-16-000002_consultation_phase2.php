<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 2 (pelunasan utang) - Konsultasi & Pengaduan.
 *
 * 1) Perluas ENUM `request_type` agar memuat jenis baru yang sudah dipakai di
 *    view ("Laporan Wali Kelas", "Lainnya/Tidak Bisa Menentukan") supaya tidak
 *    ditolak MySQL strict mode.
 * 2) Tambah kolom privasi `visible_to_parent` & `visible_to_student` agar pelapor
 *    bisa memilih apakah orang tua dan/atau siswa terkait boleh melihat laporan
 *    (laporan TIDAK lagi otomatis tampil ke subjek).
 * 3) Tambah `file_name` & `file_size` pada `consultation_complaint_attachments`
 *    agar nama & ukuran asli berkas bukti tersimpan (konsisten message_attachments).
 * 4) Buat tabel `consultation_complaint_subjects` untuk subjek (siswa) lebih dari
 *    satu, baik dari data maupun ditulis manual.
 *
 * Idempoten: hanya mengubah bila perlu, aman dijalankan ulang.
 */
class ConsultationPhase2 extends Migration
{
    public function up(): void
    {
        // 1) Perluas ENUM request_type.
        $this->db->query(<<<'SQL'
ALTER TABLE consultation_complaints
MODIFY request_type ENUM(
    'Konsultasi','Pengaduan','Permintaan Konseling','Laporan Orang Tua',
    'Laporan Wali Kelas','Lainnya','Lainnya/Tidak Bisa Menentukan'
) NOT NULL DEFAULT 'Konsultasi'
SQL);

        // 2) Kolom privasi tambahan.
        if (! $this->columnExists('consultation_complaints', 'visible_to_parent')) {
            $this->db->query('ALTER TABLE consultation_complaints ADD visible_to_parent TINYINT(1) NOT NULL DEFAULT 0 AFTER visible_to_homeroom');
        }
        if (! $this->columnExists('consultation_complaints', 'visible_to_student')) {
            $this->db->query('ALTER TABLE consultation_complaints ADD visible_to_student TINYINT(1) NOT NULL DEFAULT 0 AFTER visible_to_parent');
        }

        // 3) Metadata berkas lampiran.
        if (! $this->columnExists('consultation_complaint_attachments', 'file_name')) {
            $this->db->query('ALTER TABLE consultation_complaint_attachments ADD file_name VARCHAR(255) NULL AFTER file_path');
        }
        if (! $this->columnExists('consultation_complaint_attachments', 'file_size')) {
            $this->db->query('ALTER TABLE consultation_complaint_attachments ADD file_size INT UNSIGNED NULL AFTER file_type');
        }

        // 4) Tabel subjek (siswa) lebih dari satu.
        if (! $this->db->tableExists('consultation_complaint_subjects')) {
            $this->db->query(<<<'SQL'
CREATE TABLE consultation_complaint_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NULL,
    manual_name VARCHAR(190) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_ccsubj_complaint (complaint_id),
    INDEX idx_ccsubj_student (student_id),
    CONSTRAINT fk_ccsubj_complaint FOREIGN KEY (complaint_id) REFERENCES consultation_complaints(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ccsubj_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('consultation_complaint_subjects')) {
            $this->db->query('DROP TABLE consultation_complaint_subjects');
        }

        foreach (['file_size', 'file_name'] as $col) {
            if ($this->columnExists('consultation_complaint_attachments', $col)) {
                $this->db->query('ALTER TABLE consultation_complaint_attachments DROP COLUMN ' . $col);
            }
        }

        foreach (['visible_to_student', 'visible_to_parent'] as $col) {
            if ($this->columnExists('consultation_complaints', $col)) {
                $this->db->query('ALTER TABLE consultation_complaints DROP COLUMN ' . $col);
            }
        }

        $this->db->query(<<<'SQL'
ALTER TABLE consultation_complaints
MODIFY request_type ENUM(
    'Konsultasi','Pengaduan','Permintaan Konseling','Laporan Orang Tua','Lainnya'
) NOT NULL DEFAULT 'Konsultasi'
SQL);
    }

    private function columnExists(string $table, string $column): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        return in_array($column, $this->db->getFieldNames($table), true);
    }
}
