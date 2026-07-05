<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBkDevelopmentSchema extends Migration
{
    public function up()
    {
        $this->createConsultationComplaints();
        $this->createBkServiceRecords();
        $this->createBkServiceDetailTables();
        $this->syncSessionTables();
        $this->syncCounselingSessions();
        $this->createBkAssignments();
        $this->ensureDevelopmentPermissions();
        $this->cleanDeprecatedPointSettings();
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'bk_assignment_status_histories',
            'bk_assignments',
            'case_conferences',
            'home_visits',
            'parent_collaborations',
            'guidances',
            'consultation_complaint_attachments',
            'consultation_complaints',
            'bk_service_records',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Cek tabel/kolom TANPA cache CI4.
     * Saat seluruh migrasi dijalankan dalam SATU proses `spark migrate`
     * (instal dari nol), cache daftar tabel/kolom menjadi basi setelah
     * migrasi sebelumnya membuat tabel — guard bisa salah jawab dan
     * CREATE TABLE gagal "already exists". Riwayat lama tidak terdampak
     * karena tiap migrasi dulunya dijalankan pada proses terpisah.
     */
    private function tableExistsFresh(string $table): bool
    {
        $this->db->resetDataCache();

        return $this->db->tableExists($table, false);
    }

    private function fieldExistsFresh(string $column, string $table): bool
    {
        $this->db->resetDataCache();

        return $this->db->fieldExists($column, $table);
    }

    private function createConsultationComplaints(): void
    {
        if (! $this->tableExistsFresh('consultation_complaints')) {
            $this->db->query(<<<'SQL'
CREATE TABLE consultation_complaints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_type ENUM('student','parent','homeroom','counselor','coordinator') NOT NULL,
    reporter_user_id INT UNSIGNED NULL,
    subject_student_id INT UNSIGNED NULL,
    subject_other_name VARCHAR(190) NULL,
    request_type ENUM('Konsultasi','Pengaduan','Permintaan Konseling','Laporan Orang Tua','Lainnya') NOT NULL DEFAULT 'Konsultasi',
    category VARCHAR(100) NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    occurred_at DATETIME NULL,
    location VARCHAR(190) NULL,
    witness VARCHAR(190) NULL,
    priority ENUM('Rendah','Sedang','Tinggi','Mendesak') NOT NULL DEFAULT 'Sedang',
    status ENUM('Diajukan','Ditinjau','Diterima','Ditolak','Dijadwalkan','Selesai','Diarsipkan') NOT NULL DEFAULT 'Diajukan',
    privacy_level ENUM('Terbatas','Rahasia BK','Dapat Dilihat Wali Kelas') NOT NULL DEFAULT 'Rahasia BK',
    visible_to_homeroom TINYINT(1) NOT NULL DEFAULT 0,
    assigned_to_user_id INT UNSIGNED NULL,
    handled_by INT UNSIGNED NULL,
    handled_at DATETIME NULL,
    closed_at DATETIME NULL,
    converted_service_record_id INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_consultation_reporter (reporter_user_id),
    INDEX idx_consultation_subject (subject_student_id),
    INDEX idx_consultation_assigned (assigned_to_user_id),
    INDEX idx_consultation_status (status),
    INDEX idx_consultation_request_type (request_type),
    CONSTRAINT fk_consultation_reporter FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_consultation_subject_student FOREIGN KEY (subject_student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_consultation_assigned_to FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_consultation_handled_by FOREIGN KEY (handled_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! $this->tableExistsFresh('consultation_complaint_attachments')) {
            $this->db->query(<<<'SQL'
CREATE TABLE consultation_complaint_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NULL,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_consultation_attachments_complaint (complaint_id),
    INDEX idx_consultation_attachments_uploaded_by (uploaded_by),
    CONSTRAINT fk_consultation_attachments_complaint FOREIGN KEY (complaint_id) REFERENCES consultation_complaints(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_consultation_attachments_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }
    }

    private function createBkServiceRecords(): void
    {
        if ($this->tableExistsFresh('bk_service_records')) {
            return;
        }

        $this->db->query(<<<'SQL'
CREATE TABLE bk_service_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_type ENUM('Bimbingan','Konseling','Kolaborasi Orang Tua','Kunjungan Rumah','Konferensi Kasus') NOT NULL,
    title VARCHAR(190) NOT NULL,
    target_student_id INT UNSIGNED NULL,
    target_class_id INT UNSIGNED NULL,
    counselor_id INT UNSIGNED NULL,
    assignment_id INT UNSIGNED NULL,
    source_complaint_id INT UNSIGNED NULL,
    scheduled_at DATETIME NULL,
    held_at DATETIME NULL,
    location VARCHAR(190) NULL,
    status ENUM('Draft','Dijadwalkan','Berlangsung','Selesai','Dibatalkan','Perlu Tindak Lanjut') NOT NULL DEFAULT 'Dijadwalkan',
    duration_minutes INT NULL,
    privacy_level ENUM('Umum Terbatas','Rahasia BK','Rahasia Tinggi') NOT NULL DEFAULT 'Rahasia BK',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_bk_service_type (service_type),
    INDEX idx_bk_service_status (status),
    INDEX idx_bk_service_student (target_student_id),
    INDEX idx_bk_service_class (target_class_id),
    INDEX idx_bk_service_counselor (counselor_id),
    INDEX idx_bk_service_schedule (scheduled_at),
    CONSTRAINT fk_bk_service_student FOREIGN KEY (target_student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bk_service_class FOREIGN KEY (target_class_id) REFERENCES classes(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bk_service_counselor FOREIGN KEY (counselor_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bk_service_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bk_service_complaint FOREIGN KEY (source_complaint_id) REFERENCES consultation_complaints(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private function createBkServiceDetailTables(): void
    {
        if (! $this->tableExistsFresh('guidances')) {
            $this->db->query(<<<'SQL'
CREATE TABLE guidances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bk_service_record_id INT UNSIGNED NOT NULL,
    guidance_type ENUM('Kelompok','Klasikal','Kelas Besar') NOT NULL,
    material_topic VARCHAR(190) NULL,
    summary TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_guidances_service (bk_service_record_id),
    CONSTRAINT fk_guidances_service FOREIGN KEY (bk_service_record_id) REFERENCES bk_service_records(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! $this->tableExistsFresh('parent_collaborations')) {
            $this->db->query(<<<'SQL'
CREATE TABLE parent_collaborations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bk_service_record_id INT UNSIGNED NOT NULL,
    parent_name VARCHAR(190) NULL,
    topic VARCHAR(190) NULL,
    summary TEXT NULL,
    follow_up TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_parent_collaboration_service (bk_service_record_id),
    CONSTRAINT fk_parent_collaboration_service FOREIGN KEY (bk_service_record_id) REFERENCES bk_service_records(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! $this->tableExistsFresh('home_visits')) {
            $this->db->query(<<<'SQL'
CREATE TABLE home_visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bk_service_record_id INT UNSIGNED NOT NULL,
    address_snapshot TEXT NULL,
    problem_topic VARCHAR(190) NULL,
    visit_result TEXT NULL,
    follow_up TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_home_visit_service (bk_service_record_id),
    CONSTRAINT fk_home_visit_service FOREIGN KEY (bk_service_record_id) REFERENCES bk_service_records(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! $this->tableExistsFresh('case_conferences')) {
            $this->db->query(<<<'SQL'
CREATE TABLE case_conferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bk_service_record_id INT UNSIGNED NOT NULL,
    chronology TEXT NULL,
    discussion_summary TEXT NULL,
    decision_summary TEXT NULL,
    follow_up_plan TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_case_conference_service (bk_service_record_id),
    CONSTRAINT fk_case_conference_service FOREIGN KEY (bk_service_record_id) REFERENCES bk_service_records(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }
    }

    private function syncSessionTables(): void
    {
        if ($this->tableExistsFresh('session_notes')) {
            $this->makeColumnNullableIfExists('session_notes', 'session_id', 'INT UNSIGNED NULL');
            $this->addColumnIfMissing('session_notes', 'bk_service_record_id', 'INT UNSIGNED NULL AFTER id');
            $this->addColumnIfMissing('session_notes', 'related_participant_id', 'INT UNSIGNED NULL AFTER attachments');
            $this->addColumnIfMissing('session_notes', 'visibility_level', "ENUM('Internal BK','Koordinator dan Guru BK','Ringkasan Wali Kelas','Publik Terbatas') NOT NULL DEFAULT 'Internal BK' AFTER related_participant_id");
            $this->addColumnIfMissing('session_notes', 'follow_up_status', "ENUM('Belum','Berjalan','Selesai','Dibatalkan') NULL AFTER visibility_level");
            $this->addColumnIfMissing('session_notes', 'assigned_to_user_id', 'INT UNSIGNED NULL AFTER follow_up_status');
            $this->addColumnIfMissing('session_notes', 'due_date', 'DATE NULL AFTER assigned_to_user_id');
            $this->addColumnIfMissing('session_notes', 'completed_at', 'DATETIME NULL AFTER due_date');
            $this->addForeignKeyIfMissing('session_notes', 'fk_session_notes_bk_service', 'bk_service_record_id', 'bk_service_records', 'id', 'CASCADE', 'CASCADE');
        }

        if ($this->tableExistsFresh('session_participants')) {
            $this->makeColumnNullableIfExists('session_participants', 'session_id', 'INT UNSIGNED NULL');
            $this->makeColumnNullableIfExists('session_participants', 'student_id', 'INT UNSIGNED NULL');
            $this->addColumnIfMissing('session_participants', 'bk_service_record_id', 'INT UNSIGNED NULL AFTER id');
            $this->addColumnIfMissing('session_participants', 'participant_type', "ENUM('student','user','parent','class','manual') NOT NULL DEFAULT 'student' AFTER bk_service_record_id");
            $this->addColumnIfMissing('session_participants', 'participant_student_id', 'INT UNSIGNED NULL AFTER participant_type');
            $this->addColumnIfMissing('session_participants', 'participant_user_id', 'INT UNSIGNED NULL AFTER participant_student_id');
            $this->addColumnIfMissing('session_participants', 'participant_parent_id', 'INT UNSIGNED NULL AFTER participant_user_id');
            $this->addColumnIfMissing('session_participants', 'participant_class_id', 'INT UNSIGNED NULL AFTER participant_parent_id');
            $this->addColumnIfMissing('session_participants', 'manual_name', 'VARCHAR(190) NULL AFTER participant_class_id');
            $this->addColumnIfMissing('session_participants', 'role_in_session', 'VARCHAR(100) NULL AFTER manual_name');
            $this->addColumnIfMissing('session_participants', 'participant_note', 'TEXT NULL AFTER role_in_session');
            $this->addColumnIfMissing('session_participants', 'invitation_status', "ENUM('Belum Dikirim','Diundang','Konfirmasi','Tidak Hadir') NOT NULL DEFAULT 'Belum Dikirim' AFTER participant_note");
            $this->addForeignKeyIfMissing('session_participants', 'fk_session_participants_bk_service', 'bk_service_record_id', 'bk_service_records', 'id', 'CASCADE', 'CASCADE');
        }
    }

    private function syncCounselingSessions(): void
    {
        if (! $this->tableExistsFresh('counseling_sessions')) {
            $this->db->query(<<<'SQL'
CREATE TABLE counseling_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bk_service_record_id INT UNSIGNED NULL,
    counseling_type ENUM('Individu','Kelompok') NOT NULL DEFAULT 'Individu',
    problem_description TEXT NULL,
    session_summary TEXT NULL,
    follow_up_plan TEXT NULL,
    is_confidential TINYINT(1) NOT NULL DEFAULT 1,
    cancellation_reason TEXT NULL,
    privacy_level ENUM('Rahasia BK','Ringkasan Terbatas','Rahasia Tinggi') NOT NULL DEFAULT 'Rahasia BK',
    follow_up_status ENUM('Belum','Berjalan','Selesai','Dibatalkan') NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_counseling_sessions_bk_service FOREIGN KEY (bk_service_record_id) REFERENCES bk_service_records(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
            return;
        }

        $this->addColumnIfMissing('counseling_sessions', 'bk_service_record_id', 'INT UNSIGNED NULL AFTER id');
        $this->addColumnIfMissing('counseling_sessions', 'counseling_type', "ENUM('Individu','Kelompok') NOT NULL DEFAULT 'Individu' AFTER bk_service_record_id");
        $this->addColumnIfMissing('counseling_sessions', 'privacy_level', "ENUM('Rahasia BK','Ringkasan Terbatas','Rahasia Tinggi') NOT NULL DEFAULT 'Rahasia BK' AFTER cancellation_reason");
        $this->addColumnIfMissing('counseling_sessions', 'follow_up_status', "ENUM('Belum','Berjalan','Selesai','Dibatalkan') NULL AFTER privacy_level");
        $this->addForeignKeyIfMissing('counseling_sessions', 'fk_counseling_sessions_bk_service', 'bk_service_record_id', 'bk_service_records', 'id', 'CASCADE', 'CASCADE');
    }

    private function createBkAssignments(): void
    {
        if (! $this->tableExistsFresh('bk_assignments')) {
            $this->db->query(<<<'SQL'
CREATE TABLE bk_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_type ENUM('Kelas Binaan','Tugas Layanan','Tindak Lanjut','Koordinasi') NOT NULL DEFAULT 'Tugas Layanan',
    title VARCHAR(190) NOT NULL,
    instruction TEXT NULL,
    assigned_by INT UNSIGNED NULL,
    assigned_to_user_id INT UNSIGNED NULL,
    class_id INT UNSIGNED NULL,
    student_id INT UNSIGNED NULL,
    source_type VARCHAR(80) NULL,
    source_id INT UNSIGNED NULL,
    priority ENUM('Rendah','Sedang','Tinggi','Mendesak') NOT NULL DEFAULT 'Sedang',
    status ENUM('Draft','Ditugaskan','Dibaca','Berjalan','Selesai','Dibatalkan') NOT NULL DEFAULT 'Ditugaskan',
    due_at DATETIME NULL,
    assigned_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_bk_assignments_assigned_by (assigned_by),
    INDEX idx_bk_assignments_assigned_to (assigned_to_user_id),
    INDEX idx_bk_assignments_class (class_id),
    INDEX idx_bk_assignments_student (student_id),
    INDEX idx_bk_assignments_status (status),
    CONSTRAINT fk_bk_assignments_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bk_assignments_assigned_to FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bk_assignments_class FOREIGN KEY (class_id) REFERENCES classes(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bk_assignments_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! $this->tableExistsFresh('bk_assignment_status_histories')) {
            $this->db->query(<<<'SQL'
CREATE TABLE bk_assignment_status_histories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    status ENUM('Draft','Ditugaskan','Dibaca','Berjalan','Selesai','Dibatalkan') NOT NULL,
    note TEXT NULL,
    changed_by INT UNSIGNED NULL,
    changed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_bk_assignment_history_assignment (assignment_id),
    INDEX idx_bk_assignment_history_changed_by (changed_by),
    CONSTRAINT fk_bk_assignment_history_assignment FOREIGN KEY (assignment_id) REFERENCES bk_assignments(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_bk_assignment_history_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if ($this->tableExistsFresh('bk_service_records')) {
            $this->addForeignKeyIfMissing('bk_service_records', 'fk_bk_service_assignment', 'assignment_id', 'bk_assignments', 'id', 'CASCADE', 'SET NULL');
        }
    }

    private function ensureDevelopmentPermissions(): void
    {
        $permissions = [
            'manage_bk_services'          => 'Kelola seluruh layanan administrasi BK',
            'view_bk_services'            => 'Lihat layanan administrasi BK',
            'manage_consultation_complaints' => 'Kelola konsultasi dan pengaduan',
            'submit_consultation_complaints' => 'Ajukan konsultasi atau pengaduan',
            'review_consultation_complaints' => 'Tinjau konsultasi atau pengaduan',
            'manage_bk_assignments'       => 'Kelola penugasan Guru BK',
            'view_bk_assignments'         => 'Lihat penugasan Guru BK',
            'view_bk_reports'             => 'Lihat laporan layanan BK',
            'generate_bk_reports'         => 'Unduh laporan layanan BK',
            'access_simulation_suite'     => 'Akses halaman prototipe dan simulasi',
        ];

        foreach ($permissions as $name => $description) {
            $this->ensurePermission($name, $description);
        }
    }

    private function cleanDeprecatedPointSettings(): void
    {
        if ($this->tableExistsFresh('settings')) {
            $this->db->table('settings')->where('group', 'points')->delete();
        }
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (! $this->tableExistsFresh($table) || $this->fieldExistsFresh($column, $table)) {
            return;
        }

        $this->db->query('ALTER TABLE ' . $this->ident($table) . ' ADD ' . $this->ident($column) . ' ' . $definition);
    }

    private function makeColumnNullableIfExists(string $table, string $column, string $definition): void
    {
        if (! $this->tableExistsFresh($table) || ! $this->fieldExistsFresh($column, $table)) {
            return;
        }

        $this->db->query('ALTER TABLE ' . $this->ident($table) . ' MODIFY ' . $this->ident($column) . ' ' . $definition);
    }

    private function addForeignKeyIfMissing(string $table, string $constraint, string $column, string $refTable, string $refColumn, string $onUpdate, string $onDelete): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $tableName = $this->db->prefixTable($table);
        $row = $this->db->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$tableName, $constraint]
        )->getRowArray();

        if ($row) {
            return;
        }

        $this->db->query(
            'ALTER TABLE ' . $this->ident($table)
            . ' ADD CONSTRAINT ' . $this->ident($constraint)
            . ' FOREIGN KEY (' . $this->ident($column) . ') REFERENCES ' . $this->ident($refTable)
            . '(' . $this->ident($refColumn) . ') ON UPDATE ' . $onUpdate . ' ON DELETE ' . $onDelete
        );
    }

    private function ensurePermission(string $name, string $description): void
    {
        if (! $this->tableExistsFresh('permissions')) {
            return;
        }

        $existing = $this->db->table('permissions')
            ->select('id')
            ->where('permission_name', $name)
            ->get()
            ->getRowArray();

        if ($existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('permissions')->insert([
            'permission_name' => $name,
            'description' => $description,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ident(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
