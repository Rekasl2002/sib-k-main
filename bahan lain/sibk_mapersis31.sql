-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 28 Jan 2026 pada 07.34
-- Versi server: 8.0.30
-- Versi PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `sibk_mapersis31`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int UNSIGNED NOT NULL,
  `year_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Format: 2024/2025',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Hanya satu tahun ajaran yang bisa aktif',
  `semester` enum('Ganjil','Genap','Ganjil-Genap') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ganjil',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `academic_years`
--

INSERT INTO `academic_years` (`id`, `year_name`, `start_date`, `end_date`, `is_active`, `semester`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, '2024/2025', '2024-07-01', '2025-06-30', 1, 'Ganjil', '2025-10-06 23:09:44', '2026-01-12 14:13:31', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `assessments`
--

CREATE TABLE `assessments` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Judul/nama asesmen',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Deskripsi dan tujuan asesmen',
  `assessment_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Jenis asesmen (Psikologi, Minat Bakat, Kepribadian, Career, dll)',
  `evaluation_mode` enum('pass_fail','score_only','survey') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pass_fail',
  `target_audience` enum('Individual','Class','Grade','All') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Individual' COMMENT 'Target peserta asesmen',
  `target_class_id` int UNSIGNED DEFAULT NULL COMMENT 'ID kelas target (jika target_audience = Class)',
  `target_grade` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tingkat kelas target (X/XI/XII) jika target_audience = Grade',
  `created_by` int UNSIGNED NOT NULL COMMENT 'User ID guru BK yang membuat asesmen',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Status aktif asesmen (1=aktif, 0=nonaktif)',
  `is_published` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Status publikasi (1=sudah dipublikasi, 0=draft)',
  `start_date` date DEFAULT NULL COMMENT 'Tanggal mulai asesmen dapat diakses',
  `end_date` date DEFAULT NULL COMMENT 'Tanggal akhir asesmen dapat diakses',
  `duration_minutes` int DEFAULT NULL COMMENT 'Durasi pengerjaan dalam menit (null = unlimited)',
  `passing_score` decimal(5,2) DEFAULT NULL COMMENT 'Nilai minimum untuk lulus (%)',
  `use_passing_score` tinyint(1) NOT NULL DEFAULT '1',
  `max_attempts` int NOT NULL DEFAULT '1' COMMENT 'Maksimal percobaan pengerjaan',
  `show_result_immediately` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Tampilkan hasil langsung setelah selesai (1=ya, 0=tidak)',
  `allow_review` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Izinkan siswa review jawaban (1=ya, 0=tidak)',
  `show_score_to_student` tinyint(1) NOT NULL DEFAULT '1',
  `result_release_at` datetime DEFAULT NULL,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Instruksi pengerjaan asesmen',
  `total_questions` int NOT NULL DEFAULT '0' COMMENT 'Total jumlah pertanyaan',
  `total_participants` int NOT NULL DEFAULT '0' COMMENT 'Total peserta yang mengerjakan',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel master asesmen psikologi dan minat bakat';

-- --------------------------------------------------------

--
-- Struktur dari tabel `assessment_answers`
--

CREATE TABLE `assessment_answers` (
  `id` int UNSIGNED NOT NULL,
  `question_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel assessment_questions',
  `student_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel students',
  `result_id` int UNSIGNED NOT NULL,
  `answer_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Jawaban teks (untuk Essay)',
  `answer_option` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Pilihan jawaban (untuk Multiple Choice/True-False/Rating)',
  `answer_options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Multiple pilihan (JSON array untuk Checkbox)',
  `score` decimal(10,2) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `is_auto_graded` tinyint(1) NOT NULL DEFAULT '0',
  `graded_by` int UNSIGNED DEFAULT NULL COMMENT 'User ID guru BK yang menilai (untuk manual grading)',
  `graded_at` datetime DEFAULT NULL COMMENT 'Waktu penilaian',
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Feedback dari guru untuk jawaban siswa',
  `answered_at` datetime DEFAULT NULL COMMENT 'Waktu siswa menjawab pertanyaan',
  `time_spent_seconds` int DEFAULT NULL COMMENT 'Waktu yang dihabiskan untuk menjawab (detik)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `assessment_questions`
--

CREATE TABLE `assessment_questions` (
  `id` int UNSIGNED NOT NULL,
  `assessment_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel assessments',
  `question_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Teks pertanyaan',
  `question_type` enum('Multiple Choice','Essay','True/False','Rating Scale','Checkbox') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Multiple Choice' COMMENT 'Tipe pertanyaan',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Pilihan jawaban (JSON array) untuk Multiple Choice/Checkbox',
  `correct_answer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Jawaban yang benar (untuk auto-scoring)',
  `points` decimal(5,2) NOT NULL DEFAULT '1.00' COMMENT 'Poin untuk jawaban benar',
  `order_number` int NOT NULL DEFAULT '0' COMMENT 'Urutan tampilan pertanyaan',
  `is_required` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Wajib dijawab (1=wajib, 0=opsional)',
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Penjelasan/pembahasan jawaban',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'URL gambar pendukung pertanyaan',
  `dimension` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Dimensi/aspek yang diukur (untuk asesmen psikologi)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel pertanyaan asesmen';

-- --------------------------------------------------------

--
-- Struktur dari tabel `assessment_results`
--

CREATE TABLE `assessment_results` (
  `id` int UNSIGNED NOT NULL,
  `assessment_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel assessments',
  `student_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel students',
  `attempt_number` int NOT NULL DEFAULT '1' COMMENT 'Percobaan ke berapa',
  `status` enum('Assigned','In Progress','Completed','Graded','Expired','Abandoned') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Assigned',
  `total_score` decimal(7,2) DEFAULT '0.00' COMMENT 'Total nilai yang diperoleh',
  `max_score` decimal(7,2) DEFAULT NULL COMMENT 'Nilai maksimal asesmen',
  `percentage` decimal(5,2) DEFAULT NULL COMMENT 'Persentase nilai (0-100)',
  `is_passed` tinyint(1) DEFAULT NULL COMMENT 'Lulus atau tidak (1=lulus, 0=tidak lulus, null=belum dinilai)',
  `questions_answered` int NOT NULL DEFAULT '0' COMMENT 'Jumlah pertanyaan yang sudah dijawab',
  `total_questions` int NOT NULL DEFAULT '0' COMMENT 'Total pertanyaan dalam asesmen',
  `correct_answers` int DEFAULT NULL COMMENT 'Jumlah jawaban benar (untuk MC/True-False)',
  `started_at` datetime DEFAULT NULL COMMENT 'Waktu mulai mengerjakan',
  `completed_at` datetime DEFAULT NULL COMMENT 'Waktu selesai mengerjakan',
  `graded_at` datetime DEFAULT NULL COMMENT 'Waktu selesai dinilai (untuk essay)',
  `time_spent_seconds` int DEFAULT NULL COMMENT 'Total waktu pengerjaan (detik)',
  `interpretation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Interpretasi/analisis hasil asesmen',
  `dimension_scores` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Nilai per dimensi (JSON) untuk asesmen psikologi',
  `recommendations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Rekomendasi berdasarkan hasil asesmen',
  `reviewed_by` int UNSIGNED DEFAULT NULL COMMENT 'User ID guru BK yang mereview hasil',
  `reviewed_at` datetime DEFAULT NULL COMMENT 'Waktu review oleh guru BK',
  `counselor_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan guru BK tentang hasil asesmen',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'IP address saat mengerjakan',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Browser/device info',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel hasil asesmen siswa';

-- --------------------------------------------------------

--
-- Struktur dari tabel `career_options`
--

CREATE TABLE `career_options` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sector` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `min_education` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `required_skills` json DEFAULT NULL,
  `pathways` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `avg_salary_idr` int DEFAULT NULL,
  `demand_level` tinyint NOT NULL DEFAULT '0',
  `external_links` json DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `classes`
--

CREATE TABLE `classes` (
  `id` int UNSIGNED NOT NULL,
  `academic_year_id` int UNSIGNED NOT NULL,
  `class_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Format: X-IPA-1, XI-IPS-2, XII-IPA-3',
  `grade_level` enum('X','XI','XII') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `major` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'IPA, IPS, Bahasa, dll',
  `homeroom_teacher_id` int UNSIGNED DEFAULT NULL,
  `counselor_id` int UNSIGNED DEFAULT NULL COMMENT 'Guru BK yang bertanggung jawab',
  `max_students` int NOT NULL DEFAULT '36',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `classes`
--

INSERT INTO `classes` (`id`, `academic_year_id`, `class_name`, `grade_level`, `major`, `homeroom_teacher_id`, `counselor_id`, `max_students`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, 'X-IPA-1', 'X', 'IPA', 5, 3, 36, 1, '2025-10-06 23:09:44', '2026-01-12 14:13:23', NULL),
(2, 2, 'X-IPA-2', 'X', 'IPA', 6, 3, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(3, 2, 'X-IPS-1', 'X', 'IPS', NULL, 4, 35, 1, '2025-10-06 23:09:44', '2026-01-03 12:35:23', NULL),
(4, 2, 'X-IPS-2', 'X', 'IPS', NULL, 4, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(5, 2, 'XI-IPA-1', 'XI', 'IPA', NULL, 3, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(6, 2, 'XI-IPA-2', 'XI', 'IPA', NULL, 3, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(7, 2, 'XI-IPS-1', 'XI', 'IPS', NULL, 4, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(8, 2, 'XII-IPA-1', 'XII', 'IPA', NULL, 3, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(9, 2, 'XII-IPA-2', 'XII', 'IPA', NULL, 4, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(10, 2, 'XII-IPS-1', 'XII', 'IPS', NULL, 4, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `counseling_sessions`
--

CREATE TABLE `counseling_sessions` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED DEFAULT NULL COMMENT 'Untuk sesi individu, null untuk sesi kelompok/klasikal',
  `counselor_id` int UNSIGNED NOT NULL COMMENT 'Guru BK yang menangani sesi',
  `class_id` int UNSIGNED DEFAULT NULL COMMENT 'Untuk sesi klasikal (per kelas)',
  `session_type` enum('Individu','Kelompok','Klasikal') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Individu' COMMENT 'Jenis sesi konseling',
  `session_date` date NOT NULL COMMENT 'Tanggal pelaksanaan sesi',
  `session_time` time DEFAULT NULL COMMENT 'Waktu pelaksanaan sesi',
  `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Lokasi sesi (Ruang BK, Kelas, dll)',
  `topic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Topik/judul sesi konseling',
  `problem_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Deskripsi masalah atau topik yang dibahas',
  `session_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Ringkasan hasil sesi konseling',
  `follow_up_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Rencana tindak lanjut setelah sesi',
  `status` enum('Dijadwalkan','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Dijadwalkan' COMMENT 'Status pelaksanaan sesi',
  `is_confidential` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Apakah sesi ini bersifat rahasia (1 = ya, 0 = tidak)',
  `duration_minutes` int DEFAULT NULL COMMENT 'Durasi sesi dalam menit',
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Alasan pembatalan jika status = Dibatalkan',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `messages`
--

CREATE TABLE `messages` (
  `id` int UNSIGNED NOT NULL,
  `subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_by` int UNSIGNED NOT NULL,
  `is_draft` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `messages`
--

INSERT INTO `messages` (`id`, `subject`, `body`, `created_by`, `is_draft`, `created_at`, `updated_at`, `deleted_at`) VALUES
(13, 'Permintaan Perubahan Data Siswa', 'Permintaan perubahan data siswa #1 - Ahmad Fajar Nugraha:<br />\n- test<br />\n<br />\nCatatan orang tua:<br />\ntset', 9, 0, '2025-11-30 21:57:36', '2025-11-30 21:57:36', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `message_participants`
--

CREATE TABLE `message_participants` (
  `id` int UNSIGNED NOT NULL,
  `message_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `role` enum('sender','to','cc','bcc') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'to',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `starred` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint UNSIGNED NOT NULL,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(17, '2024-01-01-000001', 'App\\Database\\Migrations\\CreateRolesTable', 'default', 'App', 1759762620, 1),
(18, '2024-01-01-000002', 'App\\Database\\Migrations\\CreatePermissionsTable', 'default', 'App', 1759762620, 1),
(19, '2024-01-01-000003', 'App\\Database\\Migrations\\CreateRolePermissionsTable', 'default', 'App', 1759762620, 1),
(20, '2024-01-01-000004', 'App\\Database\\Migrations\\CreateUsersTable', 'default', 'App', 1759762620, 1),
(21, '2024-01-01-000005', 'App\\Database\\Migrations\\CreateAcademicYearsTable', 'default', 'App', 1759762620, 1),
(22, '2024-01-01-000006', 'App\\Database\\Migrations\\CreateClassesTable', 'default', 'App', 1759762620, 1),
(23, '2024-01-01-000007', 'App\\Database\\Migrations\\CreateStudentsTable', 'default', 'App', 1759762620, 1),
(24, '2024-01-01-000008', 'App\\Database\\Migrations\\CreateCounselingSessionsTable', 'default', 'App', 1759762620, 1),
(25, '2024-01-01-000009', 'App\\Database\\Migrations\\CreateSessionNotesTable', 'default', 'App', 1759762620, 1),
(26, '2024-01-01-000010', 'App\\Database\\Migrations\\CreateSessionParticipantsTable', 'default', 'App', 1759762620, 1),
(27, '2024-01-01-000011', 'App\\Database\\Migrations\\CreateViolationCategoriesTable', 'default', 'App', 1759762621, 1),
(28, '2024-01-01-000012', 'App\\Database\\Migrations\\CreateViolationsTable', 'default', 'App', 1759762621, 1),
(29, '2024-01-01-000013', 'App\\Database\\Migrations\\CreateSanctionsTable', 'default', 'App', 1759762622, 1),
(30, '2024-01-01-000014', 'App\\Database\\Migrations\\CreateAssessmentsTable', 'default', 'App', 1759762622, 1),
(31, '2024-01-01-000015', 'App\\Database\\Migrations\\CreateAssessmentQuestionsTable', 'default', 'App', 1759762622, 1),
(32, '2024-01-01-000016', 'App\\Database\\Migrations\\CreateAssessmentResultsTable', 'default', 'App', 1759762623, 1),
(33, '2024-01-01-000017', 'App\\Database\\Migrations\\CreateAssessmentAnswersTable', 'default', 'App', 1759766499, 2),
(34, '2024-01-01-000020', 'App\\Database\\Migrations\\CreateNotificationsTable', 'default', 'App', 1760423176, 3),
(35, '2024-01-01-000021', 'App\\Database\\Migrations\\CreateMessagesTable', 'default', 'App', 1760423176, 3),
(36, '2024-01-01-000022', 'App\\Database\\Migrations\\CreateMessageParticipantsTable', 'default', 'App', 1760423176, 3),
(37, '2024-01-01-000018', 'App\\Database\\Migrations\\CreateCareerOptionsTable', 'default', 'App', 1761111217, 4),
(38, '2024-01-01-000019', 'App\\Database\\Migrations\\CreateUniversityInfoTable', 'default', 'App', 1761111217, 4),
(39, '2025-11-17-000030', 'App\\Database\\Migrations\\CreateSettingsTable', 'default', 'App', 1763348156, 5),
(40, '2025-11-17-000031', 'App\\Database\\Migrations\\AddManageSettingsPermission', 'default', 'App', 1763348156, 5);

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'info',
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `permissions`
--

CREATE TABLE `permissions` (
  `id` int UNSIGNED NOT NULL,
  `permission_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `permissions`
--

INSERT INTO `permissions` (`id`, `permission_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'manage_users', 'Akun/Peran Admin, Koordinator BK, Guru BK, atau Walikelas dapat menggelola pengguna dan data siswa pada sistem.', '2025-10-06 23:09:43', NULL),
(2, 'manage_roles', 'Akun/Peran Admin dan Koordinator BK dapat mengelola peran dan izin akses (Admin Seluruh Pengguna & Koordinator BK hanya Guru BK dan Wali Kelas)', '2025-10-06 23:09:43', NULL),
(3, 'manage_academic_data', 'Akun/Peran Admin dapat mengelola data akademik (kelas, tahun ajaran)', '2025-10-06 23:09:43', NULL),
(4, 'manage_counseling_sessions', 'Akun/Peran Guru BK dapat mengelola sesi konseling (create, update, delete)', '2025-10-06 23:09:43', NULL),
(5, 'view_counseling_sessions', 'Akun/Peran Koordinator BK, Guru BK, Walikelas, Orang Tua, atau Siswa dapat melihat sesi konseling', '2025-10-06 23:09:43', NULL),
(6, 'manage_violations', 'Akun/Peran dapat mengelola pelanggaran siswa (create, update, delete)', '2025-10-06 23:09:43', NULL),
(7, 'view_violations', 'Akun/Peran Koordinator BK, Guru BK, Walikelas, Orang Tua, atau Siswa dapat melihat pelanggaran siswa', '2025-10-06 23:09:43', NULL),
(8, 'manage_assessments', 'Akun/Peran dapat mengelola asesmen (AUM, ITP)', '2025-10-06 23:09:43', NULL),
(9, 'take_assessments', 'Akun/Peran Siswa dapat mengerjakan asesmen yang diberikan', '2025-10-06 23:09:43', NULL),
(10, 'view_student_portfolio', 'Akun/Peran Orang Tua atau Siswa dapat melihat portofolio digital siswa (data individu/Seluruh Anak)', '2025-10-06 23:09:43', NULL),
(11, 'generate_reports', 'Akun/Peran Koordinator BK, Guru BK, Walikelas, dan Orang Tua dapat Generate laporan (PDF/Excel)', '2025-10-06 23:09:43', NULL),
(13, 'send_messages', 'Akun/Peran dapat menggunakan pesan internal', '2025-10-06 23:09:43', NULL),
(15, 'view_dashboard', 'Akun/Peran memiliki akses dashboard masing-masing', '2025-10-06 23:09:43', NULL),
(16, 'manage_career_info', 'Akun/Peran Koordinator BK, Guru BK, atau Walikelas dapat mengelola informasi karir dan universitas', '2025-10-06 23:09:43', NULL),
(17, 'view_career_info', 'Akun/Peran Orang Tua dan Siswa dapa melihat informasi karir dan universitas', '2025-10-06 23:09:43', NULL),
(18, 'manage_sanctions', 'Akun/Peran Koordinator BK, Guru BK, dan Walikelas Kelola sanksi pelanggaran', '2025-10-06 23:09:43', NULL),
(19, 'import_export_data', 'Akun/Peran Admin Import/Export data via Excel', '2025-10-06 23:09:43', NULL),
(20, 'view_all_students', 'Akun Koordinator BK, Guru BK, atau Walikelas dapat melihat semua data siswa (Seluruh  Siswa, Guru BK Siswa Binaan, dan Wali Kelas Kelas Binaan)', '2025-10-06 23:09:43', NULL),
(21, 'manage_settings', 'Admin/Peran dapat mengelola pengaturan aplikasi', '2025-11-17 09:55:56', NULL),
(28, 'manage_light_violations', 'Akun/Peran Wali Kelas dapat mengelola pelanggaran ringan', NULL, NULL),
(29, 'view_reports_individual', 'Akun/Peran Guru BK, Wali Kelas, atau Orang Tua dapat mengakses halaman laporan individual', NULL, NULL),
(30, 'generate_reports_individual', 'Akun/Peran Guru BK, Wali Kelas, atau Orang Tua dapat Generate/Download laporan individual', NULL, NULL),
(31, 'view_reports_aggregate', 'Akun/Peran Koordinator BK dapat mengaskses halaman laporan agregat', NULL, NULL),
(32, 'generate_reports_aggregate', 'Akun/Peran Koordinator BK dapat Generate/Download laporan agregat', NULL, NULL),
(33, 'submit_violation_submissions', 'Akun/Peran dapat membuat & mengelola pengaduan pelanggaran (milik sendiri)', '2026-01-21 19:01:00', NULL),
(34, 'view_violation_submissions', 'Akun/Peran Koordinator BK, Guru BK, atau Wali Kelas dapat mengakses halaman menerima dan menolak pengaduan pelanggaran dari Orang Tua dan Siswa.', '2026-01-21 19:01:00', NULL),
(35, 'view_staff_info', 'Akun/Peran Siswa dan Orang Tua dapat melihat informasi Guru BK dan Wali Kelas', '2026-01-21 19:01:00', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id` int UNSIGNED NOT NULL,
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'Administrator sistem dengan akses penuh ke seluruh fitur aplikasi', '2025-10-06 23:09:43', '2025-10-06 23:09:43'),
(2, 'Koordinator BK', 'Koordinator Bimbingan Konseling - Mengelola guru BK dan mengawasi semua layanan BK', '2025-10-06 23:09:43', '2025-10-06 23:09:43'),
(3, 'Guru BK', 'Guru Bimbingan Konseling - Melakukan konseling dan layanan BK kepada siswa', '2025-10-06 23:09:43', '2025-10-06 23:09:43'),
(4, 'Wali Kelas', 'Wali Kelas - Mengelola kelas dan mencatat pelanggaran siswa', '2025-10-06 23:09:43', '2025-10-06 23:09:43'),
(5, 'Siswa', 'Siswa - Mengakses layanan konseling dan informasi karir', '2025-10-06 23:09:43', '2025-10-06 23:09:43'),
(6, 'Orang Tua', 'Orang Tua/Wali Siswa - Melihat perkembangan dan pelanggaran anak', '2025-10-06 23:09:43', '2025-10-06 23:09:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(859, 3, 11, '2026-01-22 09:15:36'),
(860, 3, 30, '2026-01-22 09:15:36'),
(861, 3, 4, '2026-01-22 09:15:36'),
(862, 3, 18, '2026-01-22 09:15:36'),
(863, 3, 1, '2026-01-22 09:15:36'),
(864, 3, 6, '2026-01-22 09:15:36'),
(866, 3, 20, '2026-01-22 09:15:36'),
(868, 3, 5, '2026-01-22 09:15:36'),
(869, 3, 15, '2026-01-22 09:15:36'),
(871, 3, 29, '2026-01-22 09:15:36'),
(872, 3, 10, '2026-01-22 09:15:36'),
(873, 3, 7, '2026-01-22 09:15:36'),
(944, 1, 11, '2026-01-22 12:38:51'),
(945, 1, 19, '2026-01-22 12:38:51'),
(946, 1, 3, '2026-01-22 12:38:51'),
(947, 1, 2, '2026-01-22 12:38:51'),
(948, 1, 21, '2026-01-22 12:38:51'),
(949, 1, 1, '2026-01-22 12:38:51'),
(950, 1, 15, '2026-01-22 12:38:51'),
(963, 2, 11, '2026-01-24 10:05:04'),
(964, 2, 32, '2026-01-24 10:05:04'),
(965, 2, 2, '2026-01-24 10:05:04'),
(966, 2, 18, '2026-01-24 10:05:04'),
(967, 2, 1, '2026-01-24 10:05:04'),
(968, 2, 6, '2026-01-24 10:05:04'),
(969, 2, 20, '2026-01-24 10:05:04'),
(970, 2, 5, '2026-01-24 10:05:04'),
(971, 2, 15, '2026-01-24 10:05:04'),
(972, 2, 31, '2026-01-24 10:05:04'),
(973, 2, 10, '2026-01-24 10:05:04'),
(974, 2, 7, '2026-01-24 10:05:04'),
(975, 4, 11, '2026-01-24 10:51:03'),
(976, 4, 30, '2026-01-24 10:51:03'),
(977, 4, 28, '2026-01-24 10:51:03'),
(978, 4, 6, '2026-01-24 10:51:03'),
(979, 4, 20, '2026-01-24 10:51:03'),
(980, 4, 5, '2026-01-24 10:51:03'),
(981, 4, 15, '2026-01-24 10:51:03'),
(982, 4, 29, '2026-01-24 10:51:03'),
(983, 4, 10, '2026-01-24 10:51:03'),
(984, 4, 7, '2026-01-24 10:51:03'),
(998, 6, 11, '2026-01-24 10:51:43'),
(999, 6, 30, '2026-01-24 10:51:43'),
(1000, 6, 5, '2026-01-24 10:51:43'),
(1001, 6, 15, '2026-01-24 10:51:43'),
(1002, 6, 29, '2026-01-24 10:51:43'),
(1003, 6, 10, '2026-01-24 10:51:43'),
(1004, 6, 7, '2026-01-24 10:51:43'),
(1005, 5, 5, '2026-01-24 10:51:51'),
(1006, 5, 15, '2026-01-24 10:51:51'),
(1007, 5, 35, '2026-01-24 10:51:51'),
(1008, 5, 10, '2026-01-24 10:51:51'),
(1009, 5, 7, '2026-01-24 10:51:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sanctions`
--

CREATE TABLE `sanctions` (
  `id` int UNSIGNED NOT NULL,
  `violation_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel violations',
  `sanction_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Jenis sanksi (Teguran Lisan, Teguran Tertulis, Skorsing, dll)',
  `sanction_date` date NOT NULL COMMENT 'Tanggal pemberian sanksi',
  `start_date` date DEFAULT NULL COMMENT 'Tanggal mulai pelaksanaan sanksi',
  `end_date` date DEFAULT NULL COMMENT 'Tanggal selesai sanksi (untuk skorsing, pembinaan berkala, dll)',
  `duration_days` int DEFAULT NULL COMMENT 'Durasi sanksi dalam hari',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Deskripsi detail sanksi yang diberikan',
  `status` enum('Dijadwalkan','Sedang Berjalan','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Dijadwalkan' COMMENT 'Status pelaksanaan sanksi',
  `completed_date` date DEFAULT NULL COMMENT 'Tanggal selesai sanksi',
  `completion_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan penyelesaian sanksi',
  `assigned_by` int UNSIGNED NOT NULL COMMENT 'User ID yang memberikan sanksi (Guru BK/Koordinator)',
  `verified_by` int UNSIGNED DEFAULT NULL COMMENT 'User ID yang memverifikasi sanksi (Koordinator/Kepala Sekolah)',
  `verified_at` datetime DEFAULT NULL COMMENT 'Waktu verifikasi sanksi',
  `parent_acknowledged` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Status acknowledgement orang tua (0=belum, 1=sudah)',
  `parent_acknowledged_at` datetime DEFAULT NULL COMMENT 'Waktu acknowledgement orang tua',
  `documents` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Dokumen terkait sanksi (surat, berita acara - JSON array)',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan tambahan',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel sanksi untuk pelanggaran siswa';

-- --------------------------------------------------------

--
-- Struktur dari tabel `session_notes`
--

CREATE TABLE `session_notes` (
  `id` int UNSIGNED NOT NULL,
  `session_id` int UNSIGNED NOT NULL COMMENT 'FK ke counseling_sessions',
  `created_by` int UNSIGNED NOT NULL COMMENT 'User ID yang membuat catatan (counselor)',
  `note_type` enum('Observasi','Diagnosis','Intervensi','Follow-up','Lainnya') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Observasi' COMMENT 'Jenis catatan',
  `note_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Isi catatan sesi',
  `is_important` tinyint(1) NOT NULL DEFAULT '0',
  `is_confidential` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Apakah catatan ini rahasia (1 = ya, 0 = tidak)',
  `attachments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'JSON array untuk path file lampiran',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `session_participants`
--

CREATE TABLE `session_participants` (
  `id` int UNSIGNED NOT NULL,
  `session_id` int UNSIGNED NOT NULL COMMENT 'FK ke counseling_sessions',
  `student_id` int UNSIGNED NOT NULL COMMENT 'FK ke students',
  `attendance_status` enum('Hadir','Tidak Hadir','Izin','Sakit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Hadir' COMMENT 'Status kehadiran peserta',
  `participation_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan partisipasi siswa dalam sesi',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Status aktif peserta (untuk handle siswa yang keluar dari sesi)',
  `joined_at` datetime DEFAULT NULL COMMENT 'Waktu siswa bergabung ke sesi',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` int UNSIGNED NOT NULL,
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'general, branding, academic, mail, security, notifications, points',
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'string' COMMENT 'string,int,bool,json',
  `autoload` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`id`, `group`, `key`, `value`, `type`, `autoload`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'general', 'app_name', 'SIB-K', 'string', 1, NULL, '2026-01-01 21:27:33', NULL),
(2, 'general', 'school_name', 'Madrasah Aliyah Persis 31 Banjaran', 'string', 1, NULL, '2026-01-01 21:27:33', NULL),
(3, 'general', 'contact_email', 'admin@example.test', 'string', 1, NULL, '2026-01-01 21:27:33', NULL),
(4, 'branding', 'logo_path', 'uploads/branding/1763354205_56b5b6ffe8c5ebe28eff.png', 'string', 1, NULL, '2025-11-17 11:36:45', NULL),
(5, 'branding', 'favicon_path', 'uploads/branding/1763354205_43fdb2a9168b16e4f7e1.png', 'string', 1, NULL, '2025-11-17 11:36:45', NULL),
(6, 'academic', 'default_academic_year_id', '2', 'int', 1, NULL, '2026-01-12 14:13:31', NULL),
(7, 'notifications', 'enable_email', '1', 'bool', 1, NULL, '2026-01-01 21:27:33', NULL),
(8, 'notifications', 'enable_internal', '1', 'bool', 1, NULL, '2026-01-01 21:27:33', NULL),
(9, 'mail', 'from_name', 'SIB-K', 'string', 1, NULL, '2026-01-01 21:27:33', NULL),
(10, 'mail', 'from_email', 'shakiralhamdi@gmail.com', 'string', 1, NULL, '2026-01-01 21:27:33', NULL),
(11, 'security', 'session_timeout_minutes', '60', 'int', 1, NULL, '2026-01-01 21:27:33', NULL),
(12, 'points', 'probation_threshold', '50', 'int', 1, NULL, '2026-01-01 21:27:33', NULL),
(13, 'general', 'contact_phone', '(022) 5940303', 'string', 0, '2025-11-17 10:50:33', '2026-01-01 21:27:33', NULL),
(14, 'general', 'address', 'Alamat: Jl. Pajagalan No.115, Banjaran, Kec. Banjaran, Kabupaten Bandung, Jawa Barat 40377', 'string', 0, '2025-11-17 10:50:33', '2026-01-01 21:27:33', NULL),
(15, 'mail', 'host', '', 'string', 0, '2025-11-17 10:50:33', '2025-12-31 13:40:48', NULL),
(16, 'mail', 'port', '0', 'int', 0, '2025-11-17 10:50:33', '2025-12-31 13:40:48', NULL),
(17, 'mail', 'crypto', 'tls', 'string', 0, '2025-11-17 10:50:33', '2026-01-01 21:27:33', NULL),
(18, 'security', 'password_min_length', '8', 'int', 0, '2025-11-17 10:50:33', '2026-01-01 21:27:33', NULL),
(19, 'security', 'login_captcha', '', 'bool', 0, '2025-11-17 10:50:33', '2026-01-01 21:27:33', NULL),
(20, 'points', 'warning_threshold', '25', 'int', 0, '2025-11-17 10:50:33', '2026-01-01 21:27:33', NULL),
(21, 'default_academic_year_id', '3', 'academic', 'string', 0, '2025-11-17 11:36:28', '2025-11-17 11:37:14', NULL),
(22, 'notifications', 'enable_sms', '', 'bool', 0, '2025-11-17 12:06:21', '2025-12-31 13:40:48', NULL),
(23, 'notifications', 'enable_whatsapp', '', 'bool', 0, '2025-11-17 12:06:21', '2025-12-31 13:40:48', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `students`
--

CREATE TABLE `students` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `class_id` int UNSIGNED DEFAULT NULL,
  `nisn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nomor Induk Siswa Nasional',
  `nis` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nomor Induk Siswa',
  `gender` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `birth_place` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `religion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `parent_id` int UNSIGNED DEFAULT NULL COMMENT 'Link ke tabel users dengan role Orang Tua',
  `admission_date` date DEFAULT NULL COMMENT 'Tanggal masuk sekolah',
  `status` enum('Aktif','Alumni','Pindah','Keluar') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Aktif',
  `total_violation_points` int NOT NULL DEFAULT '0' COMMENT 'Total poin pelanggaran',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `students`
--

INSERT INTO `students` (`id`, `user_id`, `class_id`, `nisn`, `nis`, `gender`, `birth_place`, `birth_date`, `religion`, `address`, `parent_id`, `admission_date`, `status`, `total_violation_points`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 7, 1, '0123456789', '2024001', 'L', 'Bandung', '2008-05-15', 'Islam', 'Jl. Merdeka No. 123, Bandung', 9, '2024-07-01', 'Aktif', 30, '2025-10-06 23:09:44', '2026-01-19 10:00:30', NULL),
(2, 8, 1, '0123456790', '2024002', 'P', 'Jakarta', '2008-08-20', 'Islam', 'Jl. Sudirman No. 45, Bandung', 10, '2024-07-01', 'Aktif', 20, '2025-10-06 23:09:44', '2026-01-19 10:00:30', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `student_saved_careers`
--

CREATE TABLE `student_saved_careers` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `career_id` int UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `student_saved_universities`
--

CREATE TABLE `student_saved_universities` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `university_id` int UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `university_info`
--

CREATE TABLE `university_info` (
  `id` int UNSIGNED NOT NULL,
  `university_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alias` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accreditation` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `faculties` json DEFAULT NULL,
  `programs` json DEFAULT NULL,
  `admission_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tuition_range` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `scholarships` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `contacts` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_public` tinyint NOT NULL DEFAULT '0',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profile_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `role_id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `profile_photo`, `is_active`, `last_login`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'admin', 'admin@sibk.sch.id', '$2y$12$heMORrm/RzkMfg3wyMFQcevCzB1tZLMHkuKlFc/D8IOafssfQyhTa', 'Administrator Sistem', '081234567890', 'uploads/profile_photos/1/avatar_1_1762425658.png', 1, '2026-01-24 11:04:25', '2025-10-06 23:09:44', '2026-01-24 11:04:25', NULL),
(2, 2, 'koordinator', 'koordinator.bk@sibk.sch.id', '$2y$12$zns3nUkxaz0J3s0R8v72h.ymItjjXOmGOs73ncM7xe2bQ7ElLOk4C', 'Drs. Ahmad Supriyadi, M.Pd', '081234567891', 'uploads/profile_photos/2/avatar_2_1762493958.png', 1, '2026-01-24 11:04:38', '2025-10-06 23:09:44', '2026-01-24 11:04:38', NULL),
(3, 3, 'gurubk1', 'siti.nurhaliza@sibk.sch.id', '$2y$12$YDMZVFh7ZvFJrFxk8LiOpeQc5sTlN6aHmOj/GCEhcewVk.X1CJkmS', 'Siti Nurhaliza, S.Pd', '08123456789210', NULL, 1, '2026-01-24 11:04:48', '2025-10-06 23:09:44', '2026-01-24 11:04:48', NULL),
(4, 3, 'gurubk2', 'budi.santoso@sibk.sch.id', '$2y$12$Z2aKT1gMb0cv4/t/6sKHG.AFq8LfnJZh1Cp.Q/FUmip8V0JQOsBKW', 'Budi Santoso, S.Psi', '081234567893', NULL, 1, '2026-01-16 04:07:31', '2025-10-06 23:09:44', '2026-01-16 04:07:31', NULL),
(5, 4, 'walikelas1', 'rina.wati@sibk.sch.id', '$2y$12$8fASGFimY2i.BLFrUa0n9uSn3oNaLeemn4FtJ3Sd.nXdofSDFEMI6', 'Rina Wati, S.Pd', '081234567894', NULL, 1, '2026-01-24 11:04:58', '2025-10-06 23:09:44', '2026-01-24 11:04:58', NULL),
(6, 4, 'walikelas2', 'dedi.kusuma@sibk.sch.id', '$2y$12$vFGZKXi.ljeFePUqgzCyjO4ejouycRauS4wdhLVDKg2VUjushiw2G', 'Dedi Kusuma, S.Pd', '081234567890', NULL, 1, '2026-01-07 15:58:44', '2025-10-06 23:09:44', '2026-01-07 15:58:44', NULL),
(7, 5, 'siswa001', 'ahmad.fajar@student.sibk.com', '$2y$12$r3Tqwc446x/1vn1MK8D7EeTn1gSg/N2wOb2LT86HASu/WYx3XYNie', 'Ahmad Fajar Nugraha', '081234567896', 'uploads/profile_photos/7/avatar_7_1764520994.png', 1, '2026-01-24 11:05:16', '2025-10-06 23:09:44', '2026-01-24 11:05:16', NULL),
(8, 5, 'siswa002', 'putri.amanda@student.sibk.sch.id', '$2y$12$uRhyZdMTsek8/Twgvuitfu/E2taWjCSJnAvpMAWkX3Z09PdJi9Lkm', 'Putri Amanda Sari', '081234567897', NULL, 1, '2026-01-05 13:55:13', '2025-10-06 23:09:44', '2026-01-05 13:55:13', NULL),
(9, 6, 'parent001', 'suryanto@gmail.com', '$2y$12$ynxbMtySIlOEHgngx0GynecxtjiGcSzofNGJm8fveae8R8bTBkDXK', 'Suryanto', '0859106732065', NULL, 1, '2026-01-24 11:05:06', '2025-10-06 23:09:44', '2026-01-24 11:05:06', NULL),
(10, 6, 'parent002', 'dewi.lestari@gmail.com', '$2y$12$2.dHWWeeq2dOTQskJQLjIeiCDzL9ZSn0/UOYJKwr6/wGWLwFmgfy2', 'Dewi Lestari', '081234567899', NULL, 1, '2026-01-05 13:56:01', '2025-10-06 23:09:44', '2026-01-05 13:56:01', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `violations`
--

CREATE TABLE `violations` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel students',
  `category_id` int UNSIGNED NOT NULL COMMENT 'Foreign key ke tabel violation_categories',
  `violation_date` date NOT NULL COMMENT 'Tanggal terjadinya pelanggaran',
  `violation_time` time DEFAULT NULL COMMENT 'Waktu terjadinya pelanggaran',
  `location` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Lokasi terjadinya pelanggaran',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Deskripsi detail pelanggaran',
  `witness` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `evidence` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Bukti pelanggaran (foto/dokumen path - JSON array)',
  `reported_by` int UNSIGNED NOT NULL COMMENT 'User ID yang melaporkan (guru/staff)',
  `handled_by` int UNSIGNED DEFAULT NULL COMMENT 'Guru BK yang menangani',
  `status` enum('Dilaporkan','Dalam Proses','Selesai','Dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Dilaporkan' COMMENT 'Status penanganan pelanggaran',
  `resolution_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan resolusi/penanganan pelanggaran',
  `resolution_date` date DEFAULT NULL COMMENT 'Tanggal selesai penanganan',
  `parent_notified` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Status notifikasi orang tua (0=belum, 1=sudah)',
  `parent_notified_at` datetime DEFAULT NULL COMMENT 'Waktu notifikasi orang tua',
  `is_repeat_offender` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Penanda siswa pelanggar berulang',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan tambahan',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel data pelanggaran siswa';

-- --------------------------------------------------------

--
-- Struktur dari tabel `violation_categories`
--

CREATE TABLE `violation_categories` (
  `id` int UNSIGNED NOT NULL,
  `category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama kategori pelanggaran',
  `severity_level` enum('Ringan','Sedang','Berat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ringan' COMMENT 'Tingkat keparahan pelanggaran',
  `points` int NOT NULL DEFAULT '0' COMMENT 'Poin pengurangan untuk pelanggaran',
  `point_deduction` int NOT NULL DEFAULT '0' COMMENT 'Poin yang dikurangi untuk pelanggaran ini',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Deskripsi detail kategori pelanggaran',
  `examples` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Contoh-contoh pelanggaran dalam kategori ini',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Status aktif kategori (1=aktif, 0=non-aktif)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel kategori pelanggaran siswa';

--
-- Dumping data untuk tabel `violation_categories`
--

INSERT INTO `violation_categories` (`id`, `category_name`, `severity_level`, `points`, `point_deduction`, `description`, `examples`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Keterlambatan', 'Ringan', 0, 5, 'Terlambat masuk kelas atau datang ke sekolah tanpa alasan yang sah', 'Terlambat masuk kelas pagi, terlambat setelah istirahat, terlambat masuk setelah upacara', 1, '2025-10-06 23:17:54', NULL, NULL),
(2, 'Kelengkapan Seragam', 'Ringan', 0, 5, 'Tidak mengenakan seragam sekolah dengan lengkap dan rapi sesuai ketentuan', 'Tidak memakai dasi, tidak memakai topi, atribut tidak lengkap, sepatu tidak sesuai ketentuan', 1, '2025-10-06 23:17:54', NULL, NULL),
(3, 'Kebersihan & Kerapian', 'Ringan', 0, 5, 'Tidak menjaga kebersihan dan kerapian diri atau lingkungan sekolah', 'Rambut tidak rapi, kuku panjang, membuang sampah sembarangan, tidak piket kelas', 1, '2025-10-06 23:17:54', NULL, NULL),
(4, 'Tidak Mengerjakan Tugas', 'Ringan', 0, 5, 'Tidak mengerjakan atau tidak mengumpulkan tugas yang diberikan guru', 'Tidak mengerjakan PR, tidak mengumpulkan tugas tepat waktu, tidak membawa buku pelajaran', 1, '2025-10-06 23:17:54', NULL, NULL),
(5, 'Gadget di Kelas', 'Ringan', 0, 10, 'Menggunakan gadget (HP, tablet) saat pembelajaran tanpa izin guru', 'Main HP saat pelajaran, mendengar musik dengan earphone, bermain game di kelas', 1, '2025-10-06 23:17:54', NULL, NULL),
(6, 'Membolos', 'Sedang', 0, 20, 'Tidak masuk sekolah atau meninggalkan sekolah tanpa izin yang sah', 'Alfa tanpa keterangan, keluar sekolah tanpa izin, tidak masuk kelas saat jam pelajaran', 1, '2025-10-06 23:17:54', NULL, NULL),
(7, 'Merokok', 'Sedang', 0, 25, 'Merokok atau membawa rokok di lingkungan sekolah', 'Merokok di toilet, membawa rokok ke sekolah, merokok di sekitar sekolah saat jam pelajaran', 1, '2025-10-06 23:17:54', NULL, NULL),
(8, 'Berkelahi Ringan', 'Sedang', 0, 20, 'Terlibat perkelahian ringan atau adu mulut yang mengganggu ketertiban', 'Adu mulut dengan teman, saling dorong, pertengkaran verbal yang keras', 1, '2025-10-06 23:17:54', NULL, NULL),
(9, 'Tidak Sopan', 'Sedang', 0, 15, 'Berperilaku tidak sopan kepada guru, staff, atau teman', 'Berbicara kasar, membantah guru, tidak menghormati orang tua/guru', 1, '2025-10-06 23:17:54', NULL, NULL),
(10, 'Mencontek', 'Sedang', 0, 15, 'Mencontek saat ulangan atau ujian', 'Menyontek saat ujian, membawa contekan, membantu teman menyontek', 1, '2025-10-06 23:17:54', NULL, NULL),
(11, 'Merusak Fasilitas', 'Sedang', 0, 25, 'Merusak atau menghilangkan fasilitas dan property sekolah', 'Mencoret-coret meja/dinding, merusak kursi, menghilangkan buku perpustakaan', 1, '2025-10-06 23:17:54', NULL, NULL),
(12, 'Berkelahi Berat', 'Berat', 0, 50, 'Terlibat perkelahian fisik yang menyebabkan cedera atau kerusakan', 'Berkelahi dengan pukulan, tawuran, membawa senjata tajam', 1, '2025-10-06 23:17:54', NULL, NULL),
(13, 'Bullying', 'Berat', 0, 50, 'Melakukan intimidasi, penganiayaan, atau perundungan terhadap siswa lain', 'Intimidasi fisik/verbal, cyber bullying, memeras teman, mengucilkan teman', 1, '2025-10-06 23:17:54', NULL, NULL),
(14, 'Pencurian', 'Berat', 0, 75, 'Mengambil barang milik orang lain tanpa izin', 'Mencuri barang teman, mengambil uang, mencuri fasilitas sekolah', 1, '2025-10-06 23:17:54', NULL, NULL),
(15, 'Narkoba', 'Berat', 0, 100, 'Menggunakan, membawa, atau mengedarkan narkotika dan zat adiktif', 'Menggunakan narkoba, membawa narkoba, mengedarkan narkoba, mabuk-mabukan', 1, '2025-10-06 23:17:54', NULL, NULL),
(16, 'Pornografi', 'Berat', 0, 75, 'Membawa, menyebarkan, atau mengakses konten pornografi', 'Membawa majalah/video porno, menyebarkan konten porno, mengakses situs porno', 1, '2025-10-06 23:17:54', NULL, NULL),
(17, 'Pemalsuan Dokumen', 'Berat', 0, 60, 'Memalsukan tanda tangan, surat izin, atau dokumen sekolah lainnya', 'Memalsukan tanda tangan orangtua, memalsukan surat sakit, memalsukan nilai', 1, '2025-10-06 23:17:54', NULL, NULL),
(18, 'Perjudian', 'Berat', 0, 60, 'Melakukan perjudian dalam bentuk apapun di lingkungan sekolah', 'Main kartu judi, taruhan uang, judi online', 1, '2025-10-06 23:17:54', NULL, NULL),
(19, 'Meninggalkan Sekolah Berkali-kali', 'Berat', 0, 50, 'Meninggalkan sekolah tanpa izin lebih dari 3 kali dalam sebulan', 'Bolos berulang kali, sering keluar sekolah tanpa izin, alfa berkali-kali', 1, '2025-10-06 23:17:54', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `violation_submissions`
--

CREATE TABLE `violation_submissions` (
  `id` int UNSIGNED NOT NULL COMMENT 'Primary key unik untuk setiap laporan pelanggaran yang diajukan',
  `reporter_type` enum('student','parent') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipe pelapor: student (siswa) | parent (orang tua/wali)',
  `reporter_user_id` int UNSIGNED NOT NULL COMMENT 'users.id milik pelapor yang login (wajib)',
  `subject_student_id` int UNSIGNED DEFAULT NULL COMMENT 'students.id pihak terlapor (jika terlapor adalah siswa terdaftar)',
  `subject_other_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama terlapor jika bukan siswa terdaftar / tidak ditemukan',
  `category_id` int UNSIGNED DEFAULT NULL COMMENT 'violation_categories.id kategori pelanggaran (opsional saat submit)',
  `occurred_date` date DEFAULT NULL COMMENT 'Tanggal kejadian pelanggaran (opsional jika tidak diketahui)',
  `occurred_time` time DEFAULT NULL COMMENT 'Waktu kejadian (opsional)',
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lokasi kejadian (opsional)',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Uraian/kronologi kejadian yang dilaporkan (wajib)',
  `witness` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Saksi kejadian (opsional)',
  `evidence_json` json DEFAULT NULL COMMENT 'JSON array path bukti, mis. ["uploads/a.jpg","uploads/b.jpg"]',
  `status` enum('Diajukan','Ditinjau','Ditolak','Diterima','Dikonversi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Diajukan' COMMENT 'Status workflow: Diajukan | Ditinjau | Ditolak | Diterima | Dikonversi',
  `handled_by` int UNSIGNED DEFAULT NULL COMMENT 'users.id petugas yang memproses (Guru BK/Koordinator/Wali Kelas)',
  `handled_at` datetime DEFAULT NULL COMMENT 'Waktu terakhir diproses/ditinjau oleh petugas',
  `review_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Catatan petugas saat meninjau (alasan terima/tolak, tindak lanjut)',
  `converted_violation_id` int UNSIGNED DEFAULT NULL COMMENT 'violations.id jika laporan sudah dikonversi menjadi pelanggaran resmi',
  `created_at` datetime DEFAULT NULL COMMENT 'Timestamp dibuat (audit)',
  `updated_at` datetime DEFAULT NULL COMMENT 'Timestamp diubah (audit)',
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete timestamp (NULL jika aktif)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laporan pelanggaran dari siswa/orang tua sebelum diverifikasi menjadi pelanggaran resmi';

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_year_semester` (`year_name`,`semester`);

--
-- Indeks untuk tabel `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_type` (`assessment_type`),
  ADD KEY `target_audience` (`target_audience`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_published` (`is_published`),
  ADD KEY `start_date_end_date` (`start_date`,`end_date`),
  ADD KEY `is_active_is_published_deleted_at` (`is_active`,`is_published`,`deleted_at`),
  ADD KEY `fk_assessments_target_class` (`target_class_id`),
  ADD KEY `idx_evaluation_mode` (`evaluation_mode`);

--
-- Indeks untuk tabel `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `result_id` (`result_id`),
  ADD KEY `is_auto_graded` (`is_auto_graded`),
  ADD KEY `student_id_question_id` (`student_id`,`question_id`),
  ADD KEY `result_id_question_id` (`result_id`,`question_id`),
  ADD KEY `graded_by_graded_at` (`graded_by`,`graded_at`);

--
-- Indeks untuk tabel `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `question_type` (`question_type`),
  ADD KEY `assessment_id_order_number` (`assessment_id`,`order_number`),
  ADD KEY `assessment_id_deleted_at` (`assessment_id`,`deleted_at`);

--
-- Indeks untuk tabel `assessment_results`
--
ALTER TABLE `assessment_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_assessment_attempt` (`assessment_id`,`student_id`,`attempt_number`,`deleted_at`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `assessment_id_student_id` (`assessment_id`,`student_id`),
  ADD KEY `assessment_id_status` (`assessment_id`,`status`),
  ADD KEY `student_id_completed_at` (`student_id`,`completed_at`),
  ADD KEY `assessment_id_student_id_attempt_number` (`assessment_id`,`student_id`,`attempt_number`);

--
-- Indeks untuk tabel `career_options`
--
ALTER TABLE `career_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector` (`sector`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `fk_career_created_by` (`created_by`),
  ADD KEY `idx_career_public_active` (`is_active`);

--
-- Indeks untuk tabel `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classes_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `classes_homeroom_teacher_id_foreign` (`homeroom_teacher_id`),
  ADD KEY `classes_counselor_id_foreign` (`counselor_id`);

--
-- Indeks untuk tabel `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `counseling_sessions_class_id_foreign` (`class_id`),
  ADD KEY `session_date` (`session_date`),
  ADD KEY `counselor_id` (`counselor_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `session_type` (`session_type`),
  ADD KEY `status` (`status`);

--
-- Indeks untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by_is_draft` (`created_by`,`is_draft`);

--
-- Indeks untuk tabel `message_participants`
--
ALTER TABLE `message_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_id_user_id_role` (`message_id`,`user_id`,`role`),
  ADD KEY `user_id_is_read` (`user_id`,`is_read`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_is_read` (`user_id`,`is_read`);

--
-- Indeks untuk tabel `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indeks untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_permissions_role_id_foreign` (`role_id`),
  ADD KEY `role_permissions_permission_id_foreign` (`permission_id`);

--
-- Indeks untuk tabel `sanctions`
--
ALTER TABLE `sanctions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `violation_id` (`violation_id`),
  ADD KEY `sanction_type` (`sanction_type`),
  ADD KEY `sanction_date` (`sanction_date`),
  ADD KEY `status` (`status`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `status_sanction_date` (`status`,`sanction_date`),
  ADD KEY `violation_id_status` (`violation_id`,`status`),
  ADD KEY `fk_sanctions_verified_by` (`verified_by`);

--
-- Indeks untuk tabel `session_notes`
--
ALTER TABLE `session_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `note_type` (`note_type`),
  ADD KEY `idx_is_important` (`is_important`);

--
-- Indeks untuk tabel `session_participants`
--
ALTER TABLE `session_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id_student_id` (`session_id`,`student_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `attendance_status` (`attendance_status`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_key` (`group`,`key`);

--
-- Indeks untuk tabel `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `students_class_id_foreign` (`class_id`),
  ADD KEY `students_parent_id_foreign` (`parent_id`);

--
-- Indeks untuk tabel `student_saved_careers`
--
ALTER TABLE `student_saved_careers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ssc_student_id_fk` (`student_id`),
  ADD KEY `ssc_career_id_fk` (`career_id`);

--
-- Indeks untuk tabel `student_saved_universities`
--
ALTER TABLE `student_saved_universities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ssu_student_id_fk` (`student_id`),
  ADD KEY `ssu_univ_id_fk` (`university_id`);

--
-- Indeks untuk tabel `university_info`
--
ALTER TABLE `university_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `fk_university_created_by` (`created_by`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- Indeks untuk tabel `violations`
--
ALTER TABLE `violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `violation_date` (`violation_date`),
  ADD KEY `status` (`status`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `handled_by` (`handled_by`),
  ADD KEY `student_id_violation_date` (`student_id`,`violation_date`),
  ADD KEY `status_deleted_at` (`status`,`deleted_at`),
  ADD KEY `idx_violations_student_date` (`student_id`,`violation_date`),
  ADD KEY `idx_violations_repeat` (`is_repeat_offender`);

--
-- Indeks untuk tabel `violation_categories`
--
ALTER TABLE `violation_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `severity_level` (`severity_level`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `deleted_at_is_active` (`deleted_at`,`is_active`);

--
-- Indeks untuk tabel `violation_submissions`
--
ALTER TABLE `violation_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_vs_converted_violation` (`converted_violation_id`),
  ADD KEY `vs_reporter_user_id_idx` (`reporter_user_id`),
  ADD KEY `vs_subject_student_id_idx` (`subject_student_id`),
  ADD KEY `vs_category_id_idx` (`category_id`),
  ADD KEY `vs_status_idx` (`status`),
  ADD KEY `vs_handled_by_idx` (`handled_by`),
  ADD KEY `vs_converted_violation_id_idx` (`converted_violation_id`),
  ADD KEY `vs_occurred_date_idx` (`occurred_date`),
  ADD KEY `vs_created_at_idx` (`created_at`),
  ADD KEY `vs_deleted_at_idx` (`deleted_at`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `assessment_answers`
--
ALTER TABLE `assessment_answers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT untuk tabel `assessment_questions`
--
ALTER TABLE `assessment_questions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT untuk tabel `assessment_results`
--
ALTER TABLE `assessment_results`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT untuk tabel `career_options`
--
ALTER TABLE `career_options`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT untuk tabel `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `message_participants`
--
ALTER TABLE `message_participants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1010;

--
-- AUTO_INCREMENT untuk tabel `sanctions`
--
ALTER TABLE `sanctions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `session_notes`
--
ALTER TABLE `session_notes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `session_participants`
--
ALTER TABLE `session_participants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `students`
--
ALTER TABLE `students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `student_saved_careers`
--
ALTER TABLE `student_saved_careers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `student_saved_universities`
--
ALTER TABLE `student_saved_universities`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `university_info`
--
ALTER TABLE `university_info`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT untuk tabel `violations`
--
ALTER TABLE `violations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `violation_categories`
--
ALTER TABLE `violation_categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `violation_submissions`
--
ALTER TABLE `violation_submissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key unik untuk setiap laporan pelanggaran yang diajukan', AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `fk_assessments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assessments_target_class` FOREIGN KEY (`target_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answers_result` FOREIGN KEY (`result_id`) REFERENCES `assessment_results` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answers_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD CONSTRAINT `fk_questions_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `assessment_results`
--
ALTER TABLE `assessment_results`
  ADD CONSTRAINT `fk_results_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_results_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `career_options`
--
ALTER TABLE `career_options`
  ADD CONSTRAINT `fk_career_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `classes_counselor_id_foreign` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `classes_homeroom_teacher_id_foreign` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL;

--
-- Ketidakleluasaan untuk tabel `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD CONSTRAINT `counseling_sessions_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `counseling_sessions_counselor_id_foreign` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `counseling_sessions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE SET NULL;

--
-- Ketidakleluasaan untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `message_participants`
--
ALTER TABLE `message_participants`
  ADD CONSTRAINT `message_participants_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `message_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sanctions`
--
ALTER TABLE `sanctions`
  ADD CONSTRAINT `fk_sanctions_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sanctions_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sanctions_violation` FOREIGN KEY (`violation_id`) REFERENCES `violations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `session_notes`
--
ALTER TABLE `session_notes`
  ADD CONSTRAINT `session_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `session_notes_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `counseling_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `session_participants`
--
ALTER TABLE `session_participants`
  ADD CONSTRAINT `session_participants_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `counseling_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `session_participants_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `students_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `student_saved_careers`
--
ALTER TABLE `student_saved_careers`
  ADD CONSTRAINT `ssc_career_id_fk` FOREIGN KEY (`career_id`) REFERENCES `career_options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ssc_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `student_saved_universities`
--
ALTER TABLE `student_saved_universities`
  ADD CONSTRAINT `ssu_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ssu_univ_id_fk` FOREIGN KEY (`university_id`) REFERENCES `university_info` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `university_info`
--
ALTER TABLE `university_info`
  ADD CONSTRAINT `fk_university_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `violations`
--
ALTER TABLE `violations`
  ADD CONSTRAINT `fk_violations_category` FOREIGN KEY (`category_id`) REFERENCES `violation_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_violations_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_violations_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_violations_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `violation_submissions`
--
ALTER TABLE `violation_submissions`
  ADD CONSTRAINT `fk_vs_category` FOREIGN KEY (`category_id`) REFERENCES `violation_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vs_converted_violation` FOREIGN KEY (`converted_violation_id`) REFERENCES `violations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vs_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vs_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vs_subject_student` FOREIGN KEY (`subject_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
