<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration tunggal skema SIB-K MA Persis 31 Banjaran.
 *
 * Menggantikan seluruh migration bertahap masa pengembangan (2026-05-26 s.d.
 * 2026-07-05) dengan satu definisi skema final berisi 39 tabel aplikasi.
 * Skema diambil persis dari basis data kanonis hasil audit 2026-07-06
 * (commit c884b48: 43 tabel OK, 0 drift), dikurangi tabel modul
 * prototipe/simulasi yang dihapus saat persiapan deployment:
 * prototype_evaluations, prototype_evaluation_answers, simulation_access_grants.
 *
 * Semua tabel InnoDB, utf8mb4_unicode_ci. Tabel layanan BK memakai soft delete
 * (kolom deleted_at) agar riwayat tetap dapat diaudit.
 */
class CreateSibkSchema extends Migration
{
    public function up()
    {
        // FK dimatikan sementara supaya urutan pembuatan tabel tidak menjadi masalah.
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->createStatements() as $sql) {
            $this->db->query($sql);
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse(array_keys($this->createStatements())) as $table) {
            $this->db->query("DROP TABLE IF EXISTS `{$table}`");
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Definisi CREATE TABLE per tabel, dikelompokkan per domain fitur.
     *
     * @return array<string,string>
     */
    private function createStatements(): array
    {
        return [
            // ===== Inti: peran, izin, pengguna, pengaturan =====

            'roles' => <<<'SQL'
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'permissions' => <<<'SQL'
CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_name` (`permission_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'role_permissions' => <<<'SQL'
CREATE TABLE `role_permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permissions_pair` (`role_id`,`permission_id`),
  KEY `idx_role_permissions_permission_id` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'users' => <<<'SQL'
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role_id` (`role_id`),
  KEY `idx_users_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'settings' => <<<'SQL'
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `autoload` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_group_key` (`group`,`key`),
  KEY `idx_settings_autoload` (`autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            // ===== Akademik: tahun ajaran, kelas, siswa =====

            'academic_years' => <<<'SQL'
CREATE TABLE `academic_years` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `year_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `semester` enum('Ganjil','Genap') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ganjil',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_name` (`year_name`),
  KEY `idx_academic_years_active` (`is_active`),
  KEY `idx_academic_years_dates` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'classes' => <<<'SQL'
CREATE TABLE `classes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` int unsigned NOT NULL,
  `class_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `major` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `homeroom_teacher_id` int unsigned DEFAULT NULL,
  `counselor_id` int unsigned DEFAULT NULL,
  `max_students` int NOT NULL DEFAULT '36',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_classes_academic_year_id` (`academic_year_id`),
  KEY `idx_classes_homeroom_teacher_id` (`homeroom_teacher_id`),
  KEY `idx_classes_counselor_id` (`counselor_id`),
  KEY `idx_classes_grade_level` (`grade_level`),
  CONSTRAINT `fk_classes_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_classes_counselor` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_classes_homeroom_teacher` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'students' => <<<'SQL'
CREATE TABLE `students` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `class_id` int unsigned DEFAULT NULL,
  `nisn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_place` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `religion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_needs` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disability` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hobi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ekskul_organisasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kip_pip_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` enum('Aktif','Alumni','Pindah','Keluar','Tidak Aktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `nisn` (`nisn`),
  UNIQUE KEY `nik` (`nik`),
  KEY `idx_students_class_id` (`class_id`),
  KEY `idx_students_parent_id` (`parent_id`),
  KEY `idx_students_status` (`status`),
  CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            // ===== Bantu autentikasi: reset password & verifikasi email =====

            'password_resets' => <<<'SQL'
CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_email` (`email`),
  KEY `idx_password_resets_token` (`token`),
  KEY `idx_password_resets_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'email_verifications' => <<<'SQL'
CREATE TABLE `email_verifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_verifications_user_id` (`user_id`),
  KEY `idx_email_verifications_token` (`token`),
  CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'password_reset_requests' => <<<'SQL'
CREATE TABLE `password_reset_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `admin_message_id` int unsigned DEFAULT NULL,
  `admin_notification_id` int unsigned DEFAULT NULL,
  `requested_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requested_at` datetime NOT NULL,
  `notified_at` datetime DEFAULT NULL,
  `resolved_by` int unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_reset_requests_admin_message_id_foreign` (`admin_message_id`),
  KEY `password_reset_requests_admin_notification_id_foreign` (`admin_notification_id`),
  KEY `password_reset_requests_resolved_by_foreign` (`resolved_by`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`),
  KEY `phone` (`phone`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `password_reset_requests_admin_message_id_foreign` FOREIGN KEY (`admin_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `password_reset_requests_admin_notification_id_foreign` FOREIGN KEY (`admin_notification_id`) REFERENCES `notifications` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `password_reset_requests_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `password_reset_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

            // ===== Layanan BK: induk layanan + 5 jenis layanan + peserta + catatan =====

            'bk_service_records' => <<<'SQL'
CREATE TABLE `bk_service_records` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `service_type` enum('Bimbingan','Konseling','Kolaborasi Orang Tua','Kunjungan Rumah','Konferensi Kasus') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_student_id` int unsigned DEFAULT NULL,
  `target_class_id` int unsigned DEFAULT NULL,
  `counselor_id` int unsigned DEFAULT NULL,
  `assignment_id` int unsigned DEFAULT NULL,
  `source_complaint_id` int unsigned DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `held_at` datetime DEFAULT NULL,
  `location` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Draft','Dijadwalkan','Berlangsung','Selesai','Dibatalkan','Perlu Tindak Lanjut') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Dijadwalkan',
  `duration_minutes` int DEFAULT NULL,
  `privacy_level` enum('Umum Terbatas','Rahasia BK','Rahasia Tinggi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rahasia BK',
  `visible_to_homeroom` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bk_service_type` (`service_type`),
  KEY `idx_bk_service_status` (`status`),
  KEY `idx_bk_service_student` (`target_student_id`),
  KEY `idx_bk_service_class` (`target_class_id`),
  KEY `idx_bk_service_counselor` (`counselor_id`),
  KEY `idx_bk_service_schedule` (`scheduled_at`),
  KEY `fk_bk_service_created_by` (`created_by`),
  KEY `fk_bk_service_complaint` (`source_complaint_id`),
  KEY `fk_bk_service_assignment` (`assignment_id`),
  CONSTRAINT `fk_bk_service_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `bk_assignments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_service_class` FOREIGN KEY (`target_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_service_complaint` FOREIGN KEY (`source_complaint_id`) REFERENCES `consultation_complaints` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_service_counselor` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_service_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_service_student` FOREIGN KEY (`target_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'guidances' => <<<'SQL'
CREATE TABLE `guidances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bk_service_record_id` int unsigned NOT NULL,
  `guidance_type` enum('Kelompok','Klasikal','Kelas Besar') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_topic` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_guidances_service` (`bk_service_record_id`),
  CONSTRAINT `fk_guidances_service` FOREIGN KEY (`bk_service_record_id`) REFERENCES `bk_service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'counseling_sessions' => <<<'SQL'
CREATE TABLE `counseling_sessions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bk_service_record_id` int unsigned DEFAULT NULL,
  `counseling_type` enum('Individu','Kelompok') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Individu',
  `student_id` int unsigned DEFAULT NULL,
  `counselor_id` int unsigned DEFAULT NULL,
  `class_id` int unsigned DEFAULT NULL,
  `session_type` enum('Individu','Kelompok','Klasikal') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Individu',
  `session_date` date NOT NULL,
  `session_time` time DEFAULT NULL,
  `location` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `problem_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `session_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `follow_up_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Dijadwalkan','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Dijadwalkan',
  `is_confidential` tinyint(1) NOT NULL DEFAULT '1',
  `duration_minutes` int DEFAULT NULL,
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `privacy_level` enum('Rahasia BK','Ringkasan Terbatas','Rahasia Tinggi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rahasia BK',
  `follow_up_status` enum('Belum','Berjalan','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_counseling_sessions_student_id` (`student_id`),
  KEY `idx_counseling_sessions_counselor_id` (`counselor_id`),
  KEY `idx_counseling_sessions_class_id` (`class_id`),
  KEY `idx_counseling_sessions_status` (`status`),
  KEY `idx_counseling_sessions_date` (`session_date`),
  KEY `fk_counseling_sessions_bk_service` (`bk_service_record_id`),
  CONSTRAINT `fk_counseling_sessions_bk_service` FOREIGN KEY (`bk_service_record_id`) REFERENCES `bk_service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_counseling_sessions_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_counseling_sessions_counselor` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_counseling_sessions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'parent_collaborations' => <<<'SQL'
CREATE TABLE `parent_collaborations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bk_service_record_id` int unsigned NOT NULL,
  `parent_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topic` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `follow_up` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_parent_collaboration_service` (`bk_service_record_id`),
  CONSTRAINT `fk_parent_collaboration_service` FOREIGN KEY (`bk_service_record_id`) REFERENCES `bk_service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'home_visits' => <<<'SQL'
CREATE TABLE `home_visits` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bk_service_record_id` int unsigned NOT NULL,
  `address_snapshot` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `problem_topic` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `follow_up` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_home_visit_service` (`bk_service_record_id`),
  CONSTRAINT `fk_home_visit_service` FOREIGN KEY (`bk_service_record_id`) REFERENCES `bk_service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'case_conferences' => <<<'SQL'
CREATE TABLE `case_conferences` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bk_service_record_id` int unsigned NOT NULL,
  `chronology` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `discussion_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `decision_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `follow_up_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_case_conference_service` (`bk_service_record_id`),
  CONSTRAINT `fk_case_conference_service` FOREIGN KEY (`bk_service_record_id`) REFERENCES `bk_service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'session_participants' => <<<'SQL'
CREATE TABLE `session_participants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bk_service_record_id` int unsigned DEFAULT NULL,
  `participant_type` enum('student','user','parent','class','manual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `participant_student_id` int unsigned DEFAULT NULL,
  `participant_user_id` int unsigned DEFAULT NULL,
  `participant_parent_id` int unsigned DEFAULT NULL,
  `participant_class_id` int unsigned DEFAULT NULL,
  `manual_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_in_session` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invitation_status` enum('Belum Dikirim','Diundang','Konfirmasi','Tidak Hadir') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Belum Dikirim',
  `session_id` int unsigned DEFAULT NULL,
  `student_id` int unsigned DEFAULT NULL,
  `attendance_status` enum('Hadir','Izin','Sakit','Alpha','Belum Hadir') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Belum Hadir',
  `participation_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `joined_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_participants_pair` (`session_id`,`student_id`),
  KEY `idx_session_participants_student_id` (`student_id`),
  KEY `fk_session_participants_bk_service` (`bk_service_record_id`),
  CONSTRAINT `fk_session_participants_bk_service` FOREIGN KEY (`bk_service_record_id`) REFERENCES `bk_service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_session_participants_session` FOREIGN KEY (`session_id`) REFERENCES `counseling_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_session_participants_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'session_notes' => <<<'SQL'
CREATE TABLE `session_notes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bk_service_record_id` int unsigned DEFAULT NULL,
  `session_id` int unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `note_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Umum',
  `note_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT '0',
  `is_confidential` tinyint(1) NOT NULL DEFAULT '1',
  `attachments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `visibility_level` enum('Internal BK','Koordinator dan Guru BK','Ringkasan Wali Kelas','Publik Terbatas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Internal BK',
  `follow_up_status` enum('Belum','Berjalan','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to_user_id` int unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_notes_session_id` (`session_id`),
  KEY `idx_session_notes_created_by` (`created_by`),
  KEY `fk_session_notes_bk_service` (`bk_service_record_id`),
  CONSTRAINT `fk_session_notes_bk_service` FOREIGN KEY (`bk_service_record_id`) REFERENCES `bk_service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_session_notes_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_session_notes_session` FOREIGN KEY (`session_id`) REFERENCES `counseling_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            // ===== Konsultasi & Pengaduan =====

            'consultation_complaints' => <<<'SQL'
CREATE TABLE `consultation_complaints` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reporter_type` enum('student','parent','homeroom','counselor','coordinator') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reporter_user_id` int unsigned DEFAULT NULL,
  `subject_student_id` int unsigned DEFAULT NULL,
  `subject_other_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_type` enum('Konsultasi','Pengaduan','Permintaan Konseling','Permintaan Bimbingan','Permintaan Informasi Karier/Studi','Permintaan Mediasi','Laporan Orang Tua','Laporan Wali Kelas','Lainnya','Lainnya/Tidak Bisa Menentukan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Konsultasi',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` datetime DEFAULT NULL,
  `location` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('Rendah','Sedang','Tinggi','Mendesak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sedang',
  `status` enum('Diajukan','Ditinjau','Diterima','Ditolak','Dijadwalkan','Selesai','Diarsipkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Diajukan',
  `privacy_level` enum('Terbatas','Rahasia BK','Dapat Dilihat Wali Kelas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rahasia BK',
  `visible_to_homeroom` tinyint(1) NOT NULL DEFAULT '0',
  `visible_to_parent` tinyint(1) NOT NULL DEFAULT '0',
  `visible_to_student` tinyint(1) NOT NULL DEFAULT '0',
  `assigned_to_user_id` int unsigned DEFAULT NULL,
  `handled_by` int unsigned DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `converted_service_record_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_consultation_reporter` (`reporter_user_id`),
  KEY `idx_consultation_subject` (`subject_student_id`),
  KEY `idx_consultation_assigned` (`assigned_to_user_id`),
  KEY `idx_consultation_status` (`status`),
  KEY `idx_consultation_request_type` (`request_type`),
  KEY `fk_consultation_handled_by` (`handled_by`),
  CONSTRAINT `fk_consultation_assigned_to` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_subject_student` FOREIGN KEY (`subject_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'consultation_complaint_subjects' => <<<'SQL'
CREATE TABLE `consultation_complaint_subjects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int unsigned NOT NULL,
  `student_id` int unsigned DEFAULT NULL,
  `manual_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ccsubj_complaint` (`complaint_id`),
  KEY `idx_ccsubj_student` (`student_id`),
  CONSTRAINT `fk_ccsubj_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `consultation_complaints` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ccsubj_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'consultation_complaint_attachments' => <<<'SQL'
CREATE TABLE `consultation_complaint_attachments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int unsigned NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int unsigned DEFAULT NULL,
  `uploaded_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_consultation_attachments_complaint` (`complaint_id`),
  KEY `idx_consultation_attachments_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_consultation_attachments_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `consultation_complaints` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_attachments_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            // ===== Penugasan Guru BK =====

            'bk_assignments' => <<<'SQL'
CREATE TABLE `bk_assignments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `assignment_type` enum('Kelas Binaan','Tugas Layanan','Tindak Lanjut','Koordinasi','Pelaksanaan Asesmen','Pelaksanaan Layanan','Administrasi & Laporan','Pendampingan Siswa','Lainnya') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tugas Layanan',
  `assignment_type_other` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instruction` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `assigned_by` int unsigned DEFAULT NULL,
  `assigned_to_user_id` int unsigned DEFAULT NULL,
  `class_id` int unsigned DEFAULT NULL,
  `student_id` int unsigned DEFAULT NULL,
  `source_type` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` int unsigned DEFAULT NULL,
  `priority` enum('Rendah','Sedang','Tinggi','Mendesak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sedang',
  `status` enum('Draft','Ditugaskan','Dibaca','Berjalan','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ditugaskan',
  `due_at` datetime DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bk_assignments_assigned_by` (`assigned_by`),
  KEY `idx_bk_assignments_assigned_to` (`assigned_to_user_id`),
  KEY `idx_bk_assignments_class` (`class_id`),
  KEY `idx_bk_assignments_student` (`student_id`),
  KEY `idx_bk_assignments_status` (`status`),
  CONSTRAINT `fk_bk_assignments_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_assignments_assigned_to` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_assignments_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_assignments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'bk_assignment_targets' => <<<'SQL'
CREATE TABLE `bk_assignment_targets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` int unsigned NOT NULL,
  `target_type` enum('counselor','class','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `class_id` int unsigned DEFAULT NULL,
  `student_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bk_assignment_targets_assignment` (`assignment_id`),
  KEY `idx_bk_assignment_targets_type` (`target_type`),
  KEY `idx_bk_assignment_targets_user` (`user_id`),
  KEY `idx_bk_assignment_targets_class` (`class_id`),
  KEY `idx_bk_assignment_targets_student` (`student_id`),
  CONSTRAINT `fk_bk_assignment_targets_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `bk_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_assignment_targets_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_assignment_targets_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_assignment_targets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'bk_assignment_status_histories' => <<<'SQL'
CREATE TABLE `bk_assignment_status_histories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` int unsigned NOT NULL,
  `status` enum('Draft','Ditugaskan','Dibaca','Berjalan','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `changed_by` int unsigned DEFAULT NULL,
  `changed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bk_assignment_history_assignment` (`assignment_id`),
  KEY `idx_bk_assignment_history_changed_by` (`changed_by`),
  CONSTRAINT `fk_bk_assignment_history_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `bk_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bk_assignment_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            // ===== Asesmen =====

            'assessments' => <<<'SQL'
CREATE TABLE `assessments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `assessment_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `evaluation_mode` enum('pass_fail','score_only','survey') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pass_fail',
  `target_audience` enum('Individual','Class','Grade','All') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Individual',
  `target_class_id` int unsigned DEFAULT NULL,
  `target_grade` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `passing_score` decimal(7,2) DEFAULT NULL,
  `use_passing_score` tinyint(1) NOT NULL DEFAULT '1',
  `show_score_to_student` tinyint(1) NOT NULL DEFAULT '1',
  `max_attempts` int NOT NULL DEFAULT '1',
  `show_result_immediately` tinyint(1) NOT NULL DEFAULT '1',
  `allow_review` tinyint(1) NOT NULL DEFAULT '1',
  `result_release_at` datetime DEFAULT NULL,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_questions` int NOT NULL DEFAULT '0',
  `total_participants` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assessments_target_class_id` (`target_class_id`),
  KEY `idx_assessments_created_by` (`created_by`),
  KEY `idx_assessments_active_published` (`is_active`,`is_published`),
  KEY `idx_assessments_type` (`assessment_type`),
  CONSTRAINT `fk_assessments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assessments_target_class` FOREIGN KEY (`target_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'assessment_questions' => <<<'SQL'
CREATE TABLE `assessment_questions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int unsigned NOT NULL,
  `question_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Multiple Choice',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `correct_answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `points` decimal(7,2) DEFAULT '0.00',
  `order_number` int NOT NULL DEFAULT '1',
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dimension` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assessment_questions_assessment_id` (`assessment_id`),
  KEY `idx_assessment_questions_order` (`assessment_id`,`order_number`),
  CONSTRAINT `fk_assessment_questions_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'assessment_assignees' => <<<'SQL'
CREATE TABLE `assessment_assignees` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int unsigned NOT NULL,
  `student_id` int unsigned NOT NULL,
  `assigned_by` int unsigned DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assessment_assignees_pair` (`assessment_id`,`student_id`),
  KEY `idx_assessment_assignees_student_id` (`student_id`),
  KEY `idx_assessment_assignees_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_assessment_assignees_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_assignees_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_assignees_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'assessment_results' => <<<'SQL'
CREATE TABLE `assessment_results` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int unsigned NOT NULL,
  `student_id` int unsigned NOT NULL,
  `attempt_number` int NOT NULL DEFAULT '1',
  `status` enum('Assigned','In Progress','Completed','Graded','Expired','Abandoned') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Assigned',
  `total_score` decimal(7,2) DEFAULT '0.00',
  `max_score` decimal(7,2) DEFAULT '0.00',
  `percentage` decimal(6,2) DEFAULT '0.00',
  `is_passed` tinyint(1) DEFAULT NULL,
  `questions_answered` int DEFAULT '0',
  `total_questions` int DEFAULT '0',
  `correct_answers` int DEFAULT '0',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `time_spent_seconds` int DEFAULT '0',
  `interpretation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dimension_scores` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recommendations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reviewed_by` int unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `counselor_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assessment_results_attempt` (`assessment_id`,`student_id`,`attempt_number`),
  KEY `idx_assessment_results_student_id` (`student_id`),
  KEY `idx_assessment_results_reviewed_by` (`reviewed_by`),
  KEY `idx_assessment_results_status` (`status`),
  CONSTRAINT `fk_assessment_results_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_results_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'assessment_answers' => <<<'SQL'
CREATE TABLE `assessment_answers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `question_id` int unsigned NOT NULL,
  `student_id` int unsigned NOT NULL,
  `result_id` int unsigned NOT NULL,
  `answer_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `answer_option` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `answer_options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `score` decimal(7,2) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `is_auto_graded` tinyint(1) NOT NULL DEFAULT '0',
  `graded_by` int unsigned DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `answered_at` datetime DEFAULT NULL,
  `time_spent_seconds` int DEFAULT '0',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assessment_answers_question_id` (`question_id`),
  KEY `idx_assessment_answers_student_id` (`student_id`),
  KEY `idx_assessment_answers_result_id` (`result_id`),
  KEY `idx_assessment_answers_graded_by` (`graded_by`),
  CONSTRAINT `fk_assessment_answers_graded_by` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_answers_question` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_answers_result` FOREIGN KEY (`result_id`) REFERENCES `assessment_results` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_answers_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            // ===== Info Karier & Studi Lanjut =====

            'career_options' => <<<'SQL'
CREATE TABLE `career_options` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sector` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_education` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `required_skills` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pathways` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `avg_salary_idr` bigint DEFAULT NULL,
  `demand_level` tinyint DEFAULT NULL,
  `external_links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_career_options_created_by` (`created_by`),
  KEY `idx_career_options_active` (`is_active`),
  CONSTRAINT `fk_career_options_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'university_info' => <<<'SQL'
CREATE TABLE `university_info` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `university_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accreditation` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `faculties` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `programs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admission_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tuition_range` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scholarships` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contacts` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_university_info_created_by` (`created_by`),
  KEY `idx_university_info_active` (`is_active`),
  CONSTRAINT `fk_university_info_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'student_saved_careers' => <<<'SQL'
CREATE TABLE `student_saved_careers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned NOT NULL,
  `career_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_saved_careers_pair` (`student_id`,`career_id`),
  KEY `idx_student_saved_careers_career_id` (`career_id`),
  CONSTRAINT `fk_student_saved_careers_career` FOREIGN KEY (`career_id`) REFERENCES `career_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_saved_careers_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'student_saved_universities' => <<<'SQL'
CREATE TABLE `student_saved_universities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned NOT NULL,
  `university_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_saved_universities_pair` (`student_id`,`university_id`),
  KEY `idx_student_saved_universities_university_id` (`university_id`),
  CONSTRAINT `fk_student_saved_universities_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_saved_universities_university` FOREIGN KEY (`university_id`) REFERENCES `university_info` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            // ===== Komunikasi: pesan & notifikasi =====

            'conversations' => <<<'SQL'
CREATE TABLE `conversations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_one_id` int unsigned NOT NULL,
  `user_two_id` int unsigned NOT NULL,
  `last_message_id` int unsigned DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `one_deleted_at` datetime DEFAULT NULL,
  `two_deleted_at` datetime DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_one_id_user_two_id` (`user_one_id`,`user_two_id`),
  KEY `last_message_at` (`last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

            'messages' => <<<'SQL'
CREATE TABLE `messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int unsigned DEFAULT NULL,
  `subject` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `is_draft` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_created_by` (`created_by`),
  KEY `messages_conversation_id` (`conversation_id`),
  CONSTRAINT `fk_messages_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'message_participants' => <<<'SQL'
CREATE TABLE `message_participants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `role` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recipient',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `starred` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_message_participants_pair` (`message_id`,`user_id`),
  KEY `idx_message_participants_user_id` (`user_id`),
  KEY `idx_message_participants_read` (`is_read`),
  CONSTRAINT `fk_message_participants_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_message_participants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

            'message_attachments' => <<<'SQL'
CREATE TABLE `message_attachments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int unsigned NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_size` int unsigned DEFAULT NULL,
  `uploaded_by` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

            'notifications' => <<<'SQL'
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `title` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  KEY `idx_notifications_type` (`type`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        ];
    }
}
