-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 19 Jan 2026 pada 19.41
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
(1, '2023/2024', '2023-07-01', '2024-06-30', 0, 'Genap', '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(2, '2024/2025', '2024-07-01', '2025-06-30', 1, 'Ganjil', '2025-10-06 23:09:44', '2026-01-12 14:13:31', NULL),
(3, '2026/2027', '2026-07-09', '2027-06-11', 0, 'Ganjil-Genap', '2025-10-06 23:09:44', '2026-01-19 07:00:41', NULL),
(4, '2025/2026', '2026-01-12', '2026-06-12', 0, 'Genap', '2026-01-12 14:02:03', '2026-01-12 14:11:19', NULL),
(5, '2025/2026', '2025-07-16', '2025-12-18', 0, 'Ganjil', '2026-01-19 07:48:57', '2026-01-19 07:48:57', NULL);

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

--
-- Dumping data untuk tabel `assessments`
--

INSERT INTO `assessments` (`id`, `title`, `description`, `assessment_type`, `evaluation_mode`, `target_audience`, `target_class_id`, `target_grade`, `created_by`, `is_active`, `is_published`, `start_date`, `end_date`, `duration_minutes`, `passing_score`, `use_passing_score`, `max_attempts`, `show_result_immediately`, `allow_review`, `show_score_to_student`, `result_release_at`, `instructions`, `total_questions`, `total_participants`, `created_at`, `updated_at`, `deleted_at`) VALUES
(22, 'test', 'test', 'Minat Bakat', 'pass_fail', 'Class', 1, NULL, 3, 1, 1, '2025-11-29', '2025-12-01', 120, 70.00, 0, 1, 1, 0, 0, NULL, 'Testetstestsetest123', 5, 0, '2025-11-29 03:54:42', '2025-11-30 08:51:26', NULL),
(23, 'tset', 'tset', 'Psikologi', 'pass_fail', 'Individual', NULL, NULL, 3, 1, 0, '2025-11-30', '2025-11-30', 60, 0.00, 0, 1, 1, 1, 1, NULL, 'tset', 0, 0, '2025-11-30 00:46:10', '2025-11-30 00:46:29', '2025-11-30 00:46:29'),
(24, 'test', 'tset', 'Kecerdasan', 'pass_fail', 'Individual', NULL, NULL, 3, 1, 1, '2025-11-30', '2025-11-30', NULL, 70.00, 0, 1, 1, 1, 1, NULL, 'tsetsetest', 1, 0, '2025-11-30 00:47:29', '2025-11-30 06:26:14', '2025-11-30 06:26:14'),
(25, 'testmotivasi', 'tsetmotiv', 'Motivasi', 'pass_fail', 'Individual', NULL, NULL, 3, 1, 1, '2025-11-30', '2025-12-02', 60, 70.00, 1, 1, 0, 1, 1, '2025-11-30 10:00:00', 'tset', 1, 0, '2025-11-30 06:27:28', '2026-01-02 16:01:37', NULL);

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

--
-- Dumping data untuk tabel `assessment_answers`
--

INSERT INTO `assessment_answers` (`id`, `question_id`, `student_id`, `result_id`, `answer_text`, `answer_option`, `answer_options`, `score`, `is_correct`, `is_auto_graded`, `graded_by`, `graded_at`, `feedback`, `answered_at`, `time_spent_seconds`, `created_at`, `updated_at`, `deleted_at`) VALUES
(123, 28, 1, 73, NULL, 'True', NULL, 1.00, 1, 1, NULL, NULL, NULL, '2025-11-30 00:25:18', NULL, '2025-11-30 00:25:18', NULL, NULL),
(124, 29, 1, 73, NULL, '1', NULL, 0.00, 0, 0, 3, '2025-11-30 00:56:58', 'test', '2025-11-30 00:56:58', NULL, '2025-11-30 00:25:18', '2025-11-30 00:56:58', NULL),
(125, 30, 1, 73, NULL, NULL, '[\"test2\"]', 1.00, 1, 1, NULL, NULL, NULL, '2025-11-30 00:25:18', NULL, '2025-11-30 00:25:18', NULL, NULL),
(126, 31, 1, 73, 'test', NULL, NULL, 1.00, 1, 0, 3, '2025-11-30 00:26:08', '', '2025-11-30 00:26:08', NULL, '2025-11-30 00:25:18', '2025-11-30 00:26:08', NULL),
(127, 27, 1, 73, NULL, 'test1tes', NULL, 0.00, NULL, 0, NULL, NULL, NULL, '2025-11-30 00:25:18', NULL, '2025-11-30 00:25:18', NULL, NULL),
(128, 28, 1, 74, NULL, 'True', NULL, 1.00, 1, 1, NULL, NULL, NULL, '2025-11-30 00:57:59', NULL, '2025-11-30 00:57:59', NULL, NULL),
(129, 29, 1, 74, NULL, '1', NULL, 0.00, 0, 1, NULL, NULL, NULL, '2025-11-30 00:57:59', NULL, '2025-11-30 00:57:59', NULL, NULL),
(130, 30, 1, 74, NULL, NULL, '[\"test2\"]', 1.00, 1, 1, NULL, NULL, NULL, '2025-11-30 00:57:59', NULL, '2025-11-30 00:57:59', NULL, NULL),
(131, 31, 1, 74, 'test', NULL, NULL, 1.00, 1, 0, 3, '2025-11-30 00:58:48', 'test', '2025-11-30 00:58:48', NULL, '2025-11-30 00:57:59', '2025-11-30 00:58:48', NULL),
(132, 27, 1, 74, NULL, 'test1tes', NULL, 0.00, NULL, 0, NULL, NULL, NULL, '2025-11-30 00:57:59', NULL, '2025-11-30 00:57:59', NULL, NULL),
(135, 33, 1, 78, NULL, 'test2', NULL, 1.00, 1, 1, NULL, NULL, NULL, '2025-11-30 07:51:13', NULL, '2025-11-30 07:51:13', NULL, NULL),
(136, 33, 1, 79, NULL, 'test2', NULL, 1.00, 1, 1, NULL, NULL, NULL, '2025-11-30 15:29:22', 7, '2025-11-30 15:29:22', NULL, NULL);

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

--
-- Dumping data untuk tabel `assessment_questions`
--

INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question_text`, `question_type`, `options`, `correct_answer`, `points`, `order_number`, `is_required`, `explanation`, `image_url`, `dimension`, `created_at`, `updated_at`, `deleted_at`) VALUES
(27, 22, 'test', 'Multiple Choice', '[\"test1tes\",\"Tdsfsdf2\",\"TEstsdtg3\",\"estes4\"]', '', 0.00, 4, 0, 'test', 'https://mapersis31banjaran.sch.id/storage/homepage/logo/dN98YOyO8JljuEuJyAc3yQWAy0ECY1uClmvPU0YJ.png', 'test', '2025-11-29 03:56:22', '2025-11-29 03:59:46', NULL),
(28, 22, 'Test', 'True/False', '[\"True\",\"False\"]', 'True', 1.00, 5, 1, 'test', 'uploads/assessment_questions/q_20251129_040018_c125b0c3.png', 'testtest', '2025-11-29 03:57:15', '2026-01-02 15:33:15', NULL),
(29, 22, 'testtesttest', 'Rating Scale', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '5', 1.00, 1, 1, 'testsetsetes', NULL, '235w3r', '2025-11-29 03:57:26', '2025-11-29 03:57:26', NULL),
(30, 22, 'testsetsetste', 'Checkbox', '[\"test2\",\"test1\",\"tset3\",\"ests4\"]', '[\"test2\"]', 1.00, 2, 1, 'testes', NULL, 'test', '2025-11-29 03:57:57', '2025-11-29 03:57:57', NULL),
(31, 22, 'tsetse', 'Essay', '[]', '', 1.00, 3, 1, 'tsetsee', 'uploads/assessment_questions/q_20251129_113413_e447d07a.png', 'tsetsest', '2025-11-29 03:58:48', '2025-11-29 11:34:13', NULL),
(32, 24, 'test', 'Multiple Choice', '[\"test1\",\"test2\",\"tset3\",\"terst4\"]', 'test1', 1.00, 1, 1, 'tset', 'uploads/assessment_questions/q_20251130_015608_d3a5ba0e.png', 'tset', '2025-11-30 01:56:08', '2025-11-30 01:56:08', NULL),
(33, 25, 'tset', 'Multiple Choice', '[\"test2\",\"tset1\"]', 'test2', 1.00, 1, 1, 'test', 'https://mapersis31banjaran.sch.id/storage/homepage/logo/dN98YOyO8JljuEuJyAc3yQWAy0ECY1uClmvPU0YJ.png', 'tset', '2025-11-30 06:27:56', '2025-11-30 06:27:56', NULL);

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

--
-- Dumping data untuk tabel `assessment_results`
--

INSERT INTO `assessment_results` (`id`, `assessment_id`, `student_id`, `attempt_number`, `status`, `total_score`, `max_score`, `percentage`, `is_passed`, `questions_answered`, `total_questions`, `correct_answers`, `started_at`, `completed_at`, `graded_at`, `time_spent_seconds`, `interpretation`, `dimension_scores`, `recommendations`, `reviewed_by`, `reviewed_at`, `counselor_notes`, `ip_address`, `user_agent`, `created_at`, `updated_at`, `deleted_at`) VALUES
(73, 22, 1, 1, 'Graded', 3.00, 4.00, 75.00, 1, 5, 5, 3, '2025-11-30 00:25:08', '2025-11-30 00:25:18', '2025-11-30 00:56:58', 10, 'test', 's:63:\"{\"testtest\":\"100\",\"235w3r\":\"100\",\"test\":\"100\",\"tsetsest\":\"100\"}\";', 'tset', NULL, '2025-11-30 00:25:57', 'tset', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-30 00:24:50', '2025-11-30 00:57:31', '2025-11-30 00:57:31'),
(74, 22, 1, 1, 'Graded', 3.00, 4.00, 75.00, 1, 5, 5, 3, '2025-11-30 00:57:47', '2025-11-30 00:57:59', '2025-11-30 00:58:48', 12, 'test', 's:63:\"{\"testtest\":\"100\",\"235w3r\":\"100\",\"test\":\"100\",\"tsetsest\":\"100\"}\";', 'tset', 3, '2025-11-30 00:58:42', 'tset', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-30 00:57:36', '2025-11-30 00:58:48', NULL),
(77, 25, 1, 1, 'Assigned', 0.00, 1.00, NULL, NULL, 0, 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-30 07:50:11', '2025-11-30 07:50:16', '2025-11-30 07:50:16'),
(78, 25, 1, 1, 'Graded', 1.00, 1.00, 100.00, NULL, 1, 1, 1, '2025-11-30 07:51:11', '2025-11-30 07:51:13', '2025-11-30 07:51:53', 2, 'test', 's:14:\"{\"tset\":\"100\"}\";', 'tset', 3, '2025-11-30 07:51:53', 'tset', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-30 07:50:55', '2025-11-30 15:28:49', '2025-11-30 15:28:49'),
(79, 25, 1, 1, 'Completed', 1.00, 1.00, 100.00, 1, 1, 1, 1, '2025-11-30 15:29:15', '2025-11-30 15:29:22', NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-30 15:28:54', '2025-11-30 15:29:22', NULL),
(80, 25, 32, 1, 'Assigned', 0.00, 1.00, NULL, NULL, 0, 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-02 15:59:54', '2026-01-02 15:59:54', NULL);

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

--
-- Dumping data untuk tabel `career_options`
--

INSERT INTO `career_options` (`id`, `title`, `sector`, `min_education`, `description`, `required_skills`, `pathways`, `avg_salary_idr`, `demand_level`, `external_links`, `is_public`, `is_active`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 'testtest', 'TI', 'SMA/SMK', 'test', '[\"HTML/CSS\"]', 'test', 5000000, 8, '[{\"url\": \"https://developer.mozilla.org\", \"label\": \"test\"}]', 1, 1, 3, '2025-11-25 03:15:51', '2025-11-25 05:45:52', NULL),
(4, 'testtestestsetest', 'testsetsetestestes', 'SMA/SMK', 'tests634364346', '[\"tsetsetest\"]', 'tsetesset', 12, 2, NULL, 1, 1, 3, '2025-11-25 05:00:04', '2025-11-29 11:36:31', NULL),
(5, 'test3', 'test123', 'S1', 'test123', '[\"test123\"]', 'test123', 1600000, 2, NULL, 1, 1, 3, '2025-11-25 05:47:17', '2025-11-25 17:19:21', NULL),
(6, 'test', 'tset', 'SMA/SMK', 'testestestsetsetsets', '[\"tsetsetes\"]', 'tsetsetsete', 2000000, 5, '[{\"url\": \"https://developer.mozilla.org\", \"label\": \"MDN Web Docs\"}]', 1, 1, 3, '2025-11-29 11:36:22', '2025-11-29 11:36:22', NULL),
(7, 'testsetsetsetes', 'setesttestest', 'SMA/SMK', 'tsesetsesesees', NULL, 'gsdgsdsdgsdg', 1600000000, 5, NULL, 1, 1, 3, '2025-11-30 17:44:00', '2025-11-30 17:44:00', NULL);

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
(10, 2, 'XII-IPS-1', 'XII', 'IPS', NULL, 4, 36, 1, '2025-10-06 23:09:44', '2025-10-06 23:09:44', NULL),
(11, 4, 'X-IPA-A', 'X', 'IPA', NULL, NULL, 36, 0, '2026-01-12 14:06:18', '2026-01-14 01:26:02', NULL),
(12, 3, 'X-IPA-A-Ganjil-2025', 'X', 'IPA', NULL, NULL, 36, 1, '2026-01-12 14:16:56', '2026-01-12 14:16:56', NULL),
(13, 3, 'X-IPA-A-Ganjil_Genap-2026_2027', 'X', 'IPA', NULL, NULL, 20, 0, '2026-01-19 07:02:16', '2026-01-19 07:02:16', NULL);

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

--
-- Dumping data untuk tabel `counseling_sessions`
--

INSERT INTO `counseling_sessions` (`id`, `student_id`, `counselor_id`, `class_id`, `session_type`, `session_date`, `session_time`, `location`, `topic`, `problem_description`, `session_summary`, `follow_up_plan`, `status`, `is_confidential`, `duration_minutes`, `cancellation_reason`, `created_at`, `updated_at`, `deleted_at`) VALUES
(36, 1, 3, NULL, 'Individu', '2025-12-11', '12:00:00', 'Ruang BKtest', 'test', 'test', 'test', 'tset', 'Dijadwalkan', 0, 60, '', '2025-11-28 23:08:51', '2025-11-29 00:15:22', NULL),
(37, NULL, 3, NULL, 'Kelompok', '2025-11-30', '12:00:00', 'Ruang BKtest', 'test', 'test', NULL, NULL, 'Dijadwalkan', 0, 60, NULL, '2025-11-28 23:09:06', '2025-11-28 23:16:35', '2025-11-28 23:16:35'),
(38, NULL, 3, 1, 'Klasikal', '2025-12-01', '12:00:00', 'Ruang BKtset', 'tset', 'TEST', 'tset4214cv', 'tsetsdfxza', 'Dijadwalkan', 0, 60, '', '2025-11-28 23:09:21', '2025-12-16 21:03:24', NULL),
(39, NULL, 3, NULL, 'Kelompok', '2025-11-30', '12:00:00', 'Ruang BK', 'testse', 'tsetseset', 'test', 'tset', 'Dijadwalkan', 0, 60, '', '2025-11-28 23:16:50', '2025-11-29 00:12:42', NULL),
(40, 2, 3, NULL, 'Individu', '2025-12-05', '12:00:00', 'Ruang BK', 'test123FSDFSDFSD', 'TESVSDF', 'TESTSE', 'TSETSETES', 'Dibatalkan', 0, 60, 'test', '2025-11-29 01:23:04', '2025-11-30 01:42:41', NULL),
(41, NULL, 3, NULL, 'Kelompok', '2026-01-14', '23:00:00', 'Ruang BK Final', 'testfinal', 'Test final sesi konseling bk', NULL, NULL, 'Dijadwalkan', 0, 60, NULL, '2026-01-14 21:03:02', '2026-01-14 21:03:02', NULL),
(42, NULL, 3, 1, 'Klasikal', '2026-01-14', NULL, 'Ruang BK', 'testkeals', '', NULL, NULL, 'Dijadwalkan', 0, 60, NULL, '2026-01-14 21:03:15', '2026-01-14 21:03:15', NULL),
(43, 1, 3, NULL, 'Individu', '2026-01-15', '23:00:00', 'Ruang BK finals', 'testindividufinal', 'testindividufinal test', NULL, NULL, 'Dijadwalkan', 1, 60, NULL, '2026-01-14 21:04:18', '2026-01-14 21:04:18', NULL),
(44, 2, 3, NULL, 'Individu', '2026-01-16', '12:00:00', 'Ruang BKtest', 'test', 'tsetsesdfgvxsdvfsedr', '', '', 'Dijadwalkan', 0, 120, '', '2026-01-14 21:04:37', '2026-01-15 03:16:49', NULL);

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

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `data`, `is_read`, `read_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(9, 3, 'Pelanggaran Baru Dilaporkan', 'Pelanggaran Tidak Sopan dilaporkan oleh X-IPA-1', 'violation', NULL, '{\"violation_id\": 19}', 1, '2025-11-26 11:05:56', '2025-11-23 16:44:43', '2025-11-26 11:05:56', NULL),
(10, 9, 'Pelanggaran Siswa', 'Anak Anda melakukan pelanggaran: Tidak Sopan', 'violation', NULL, '{\"violation_id\": 19}', 1, '2025-12-01 00:02:52', '2025-11-23 16:44:43', '2025-12-01 00:02:52', NULL),
(11, 3, 'Pelanggaran Baru Dilaporkan', 'Pelanggaran Tidak Mengerjakan Tugas dilaporkan oleh X-IPA-1', 'violation', NULL, '{\"violation_id\": 30}', 1, '2025-12-07 04:28:46', '2025-12-07 04:25:51', '2025-12-07 04:28:46', NULL),
(12, 9, 'Pelanggaran Siswa', 'Anak Anda melakukan pelanggaran: Tidak Mengerjakan Tugas', 'violation', NULL, '{\"violation_id\": 30}', 0, NULL, '2025-12-07 04:25:51', NULL, NULL),
(13, 3, 'Pelanggaran Baru Dilaporkan', 'Pelanggaran Tidak Mengerjakan Tugas dilaporkan oleh X-IPA-1', 'violation', NULL, '{\"violation_id\": 31}', 0, NULL, '2025-12-07 04:37:44', NULL, NULL),
(14, 9, 'Pelanggaran Siswa', 'Anak Anda melakukan pelanggaran: Tidak Mengerjakan Tugas', 'violation', NULL, '{\"violation_id\": 31}', 0, NULL, '2025-12-07 04:37:44', NULL, NULL),
(15, 3, 'Pelanggaran Baru Dilaporkan', 'Pelanggaran Kelengkapan Seragam dilaporkan oleh X-IPA-1', 'violation', NULL, '{\"violation_id\": 35}', 0, NULL, '2026-01-15 03:18:47', NULL, NULL),
(16, 57, 'Pelanggaran Siswa', 'Anak Anda melakukan pelanggaran: Kelengkapan Seragam', 'violation', NULL, '{\"violation_id\": 35}', 0, NULL, '2026-01-15 03:18:47', NULL, NULL),
(17, 3, 'Pelanggaran Baru Dilaporkan', 'Pelanggaran Tidak Mengerjakan Tugas dilaporkan oleh X-IPA-1', 'violation', NULL, '{\"violation_id\": 36}', 0, NULL, '2026-01-15 03:20:21', NULL, NULL),
(18, 10, 'Pelanggaran Siswa', 'Anak Anda melakukan pelanggaran: Tidak Mengerjakan Tugas', 'violation', NULL, '{\"violation_id\": 36}', 0, NULL, '2026-01-15 03:20:21', NULL, NULL),
(19, 3, 'Pelanggaran Baru Dilaporkan', 'Pelanggaran Tidak Mengerjakan Tugas dilaporkan oleh X-IPA-1', 'violation', NULL, '{\"violation_id\": 37}', 0, NULL, '2026-01-15 03:21:37', NULL, NULL),
(20, 10, 'Pelanggaran Siswa', 'Anak Anda melakukan pelanggaran: Tidak Mengerjakan Tugas', 'violation', NULL, '{\"violation_id\": 37}', 0, NULL, '2026-01-15 03:21:37', NULL, NULL),
(21, 2, 'Konfirmasi Orang Tua', 'Suryanto telah mengonfirmasi mengetahui sanksi untuk Ahmad Fajar Nugraha (Kelas X-IPA-1) terkait pelanggaran: Kelengkapan Seragam.', 'info', 'http://localhost:10/koordinator/cases/detail/32', '{\"ack_at\": \"2026-01-16 05:00:52\", \"parent_id\": 9, \"student_id\": 1, \"sanction_ids\": [12], \"violation_id\": 32}', 0, NULL, '2026-01-16 05:00:52', NULL, NULL),
(22, 3, 'Konfirmasi Orang Tua', 'Suryanto telah mengonfirmasi mengetahui sanksi untuk Ahmad Fajar Nugraha (Kelas X-IPA-1) terkait pelanggaran: Kelengkapan Seragam.', 'info', 'http://localhost:10/counselor/cases/detail/32', '{\"ack_at\": \"2026-01-16 05:00:52\", \"parent_id\": 9, \"student_id\": 1, \"sanction_ids\": [12], \"violation_id\": 32}', 0, NULL, '2026-01-16 05:00:52', NULL, NULL);

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
(1, 'manage_users', 'Kelola pengguna sistem (CRUD users)', '2025-10-06 23:09:43', NULL),
(2, 'manage_roles', 'Kelola peran dan izin akses', '2025-10-06 23:09:43', NULL),
(3, 'manage_academic_data', 'Kelola data akademik (kelas, tahun ajaran)', '2025-10-06 23:09:43', NULL),
(4, 'manage_counseling_sessions', 'Kelola sesi konseling (create, update, delete)', '2025-10-06 23:09:43', NULL),
(5, 'view_counseling_sessions', 'Lihat sesi konseling', '2025-10-06 23:09:43', NULL),
(6, 'manage_violations', 'Kelola pelanggaran siswa (create, update, delete)', '2025-10-06 23:09:43', NULL),
(7, 'view_violations', 'Lihat pelanggaran siswa', '2025-10-06 23:09:43', NULL),
(8, 'manage_assessments', 'Kelola asesmen (AUM, ITP)', '2025-10-06 23:09:43', NULL),
(9, 'take_assessments', 'Mengerjakan asesmen yang diberikan', '2025-10-06 23:09:43', NULL),
(10, 'view_student_portfolio', 'Lihat portofolio digital siswa', '2025-10-06 23:09:43', NULL),
(11, 'generate_reports', 'Generate laporan (PDF/Excel)', '2025-10-06 23:09:43', NULL),
(12, 'view_reports', 'Lihat laporan', '2025-10-06 23:09:43', NULL),
(13, 'send_messages', 'Kirim pesan internal', '2025-10-06 23:09:43', NULL),
(14, 'schedule_counseling', 'Jadwalkan konseling', '2025-10-06 23:09:43', NULL),
(15, 'view_dashboard', 'Akses dashboard sesuai role', '2025-10-06 23:09:43', NULL),
(16, 'manage_career_info', 'Kelola informasi karir dan universitas', '2025-10-06 23:09:43', NULL),
(17, 'view_career_info', 'Lihat informasi karir dan universitas', '2025-10-06 23:09:43', NULL),
(18, 'manage_sanctions', 'Kelola sanksi pelanggaran', '2025-10-06 23:09:43', NULL),
(19, 'import_export_data', 'Import/Export data via Excel', '2025-10-06 23:09:43', NULL),
(20, 'view_all_students', 'Lihat semua data siswa', '2025-10-06 23:09:43', NULL),
(21, 'manage_settings', 'Kelola pengaturan aplikasi', '2025-11-17 09:55:56', NULL),
(22, 'manage_staff', 'Kelola data staf (Koordinator)', NULL, NULL),
(23, 'view_staff', 'Lihat data staf', NULL, NULL),
(24, 'view_class_students', 'Wali kelas melihat siswa perwalian', NULL, NULL),
(25, 'view_child_data', 'Orang tua melihat data anak', NULL, NULL),
(26, 'view_counseling_schedule', 'Lihat jadwal sesi konseling', NULL, NULL),
(27, 'view_counseling_notes', 'permissionsAudit catatan konseling rahasia (Koordinator)', NULL, NULL),
(28, 'manage_light_violations', 'Kelola pelanggaran ringan (Wali kelas)', NULL, NULL),
(29, 'view_reports_individual', 'Akses laporan individual', NULL, NULL),
(30, 'generate_reports_individual', 'Generate/Download laporan individual', NULL, NULL),
(31, 'view_reports_aggregate', 'Akses laporan agregat', NULL, NULL),
(32, 'generate_reports_aggregate', 'Generate/Download laporan agregat', NULL, NULL);

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
(702, 2, 11, '2026-01-17 03:05:01'),
(703, 2, 19, '2026-01-17 03:05:01'),
(704, 2, 3, '2026-01-17 03:05:01'),
(705, 2, 8, '2026-01-17 03:05:01'),
(706, 2, 16, '2026-01-17 03:05:01'),
(707, 2, 4, '2026-01-17 03:05:01'),
(708, 2, 2, '2026-01-17 03:05:01'),
(709, 2, 18, '2026-01-17 03:05:01'),
(710, 2, 21, '2026-01-17 03:05:01'),
(711, 2, 1, '2026-01-17 03:05:01'),
(712, 2, 6, '2026-01-17 03:05:01'),
(713, 2, 14, '2026-01-17 03:05:01'),
(714, 2, 20, '2026-01-17 03:05:01'),
(715, 2, 17, '2026-01-17 03:05:01'),
(716, 2, 5, '2026-01-17 03:05:01'),
(717, 2, 15, '2026-01-17 03:05:01'),
(718, 2, 12, '2026-01-17 03:05:01'),
(719, 2, 10, '2026-01-17 03:05:01'),
(720, 2, 7, '2026-01-17 03:05:01'),
(721, 6, 14, '2026-01-17 03:05:09'),
(722, 6, 9, '2026-01-17 03:05:09'),
(723, 6, 17, '2026-01-17 03:05:09'),
(724, 6, 15, '2026-01-17 03:05:09'),
(725, 6, 7, '2026-01-17 03:05:09'),
(726, 5, 14, '2026-01-17 03:05:15'),
(727, 5, 9, '2026-01-17 03:05:15'),
(728, 5, 17, '2026-01-17 03:05:15'),
(729, 5, 15, '2026-01-17 03:05:15'),
(730, 5, 7, '2026-01-17 03:05:15'),
(757, 3, 11, '2026-01-17 03:06:20'),
(758, 3, 8, '2026-01-17 03:06:20'),
(759, 3, 16, '2026-01-17 03:06:20'),
(760, 3, 4, '2026-01-17 03:06:20'),
(761, 3, 2, '2026-01-17 03:06:20'),
(762, 3, 18, '2026-01-17 03:06:20'),
(763, 3, 21, '2026-01-17 03:06:20'),
(764, 3, 1, '2026-01-17 03:06:20'),
(765, 3, 6, '2026-01-17 03:06:20'),
(766, 3, 14, '2026-01-17 03:06:20'),
(767, 3, 20, '2026-01-17 03:06:20'),
(768, 3, 17, '2026-01-17 03:06:20'),
(769, 3, 5, '2026-01-17 03:06:20'),
(770, 3, 15, '2026-01-17 03:06:20'),
(771, 3, 12, '2026-01-17 03:06:20'),
(772, 3, 10, '2026-01-17 03:06:20'),
(773, 3, 7, '2026-01-17 03:06:20'),
(774, 1, 11, '2026-01-17 03:08:22'),
(775, 1, 19, '2026-01-17 03:08:22'),
(776, 1, 3, '2026-01-17 03:08:22'),
(777, 1, 2, '2026-01-17 03:08:22'),
(778, 1, 21, '2026-01-17 03:08:22'),
(779, 1, 1, '2026-01-17 03:08:22'),
(780, 1, 20, '2026-01-17 03:08:22'),
(781, 1, 15, '2026-01-17 03:08:22'),
(782, 1, 10, '2026-01-17 03:08:22'),
(783, 4, 11, '2026-01-17 05:45:09'),
(784, 4, 30, '2026-01-17 05:45:09'),
(785, 4, 28, '2026-01-17 05:45:09'),
(786, 4, 6, '2026-01-17 05:45:09'),
(787, 4, 17, '2026-01-17 05:45:09'),
(788, 4, 24, '2026-01-17 05:45:09'),
(789, 4, 27, '2026-01-17 05:45:09'),
(790, 4, 5, '2026-01-17 05:45:09'),
(791, 4, 15, '2026-01-17 05:45:09'),
(792, 4, 12, '2026-01-17 05:45:09'),
(793, 4, 29, '2026-01-17 05:45:09'),
(794, 4, 10, '2026-01-17 05:45:09'),
(795, 4, 7, '2026-01-17 05:45:09');

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

--
-- Dumping data untuk tabel `sanctions`
--

INSERT INTO `sanctions` (`id`, `violation_id`, `sanction_type`, `sanction_date`, `start_date`, `end_date`, `duration_days`, `description`, `status`, `completed_date`, `completion_notes`, `assigned_by`, `verified_by`, `verified_at`, `parent_acknowledged`, `parent_acknowledged_at`, `documents`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(8, 28, 'Skorsing 1 Hari', '2025-11-29', '2025-11-30', '2025-12-01', 1, 'Testsetseestsegbdf13456', 'Selesai', '2025-11-30', 'test', 3, 2, '2025-12-18 14:10:47', 1, '2025-12-18 14:09:00', '[\"uploads/sanctions/2025/11/1764390552_8b81db7e7eea210a.png\"]', 'tsetsese', '2025-11-29 11:29:12', '2025-12-18 14:10:57', NULL),
(9, 28, 'Pemanggilan Orang Tua', '2025-11-29', '2025-11-30', '2025-11-30', 0, 'gsdfgdfsgdsfgdsgdsgsdsdg', 'Selesai', '2025-12-18', NULL, 3, 2, '2025-12-18 16:54:03', 1, '2025-12-18 16:54:00', '[\"uploads/sanctions/2025/11/1764390694_d1fc26b33f0e0aef.png\"]', 'testsetse', '2025-11-29 11:31:34', '2025-12-18 16:54:12', NULL),
(10, 28, 'Teguran Lisan', '2026-01-13', '2026-01-13', '2026-01-14', 1, 'Testfinaliguess', 'Selesai', '2026-01-13', 'testfina', 2, 2, '2026-01-13 04:22:09', 1, '2026-01-13 04:21:00', '[\"uploads/sanctions/2026/01/1768252889_1c3c59f96d0a212f.png\"]', 'testfinal', '2026-01-13 04:21:29', '2026-01-13 04:22:19', NULL),
(11, 34, 'Teguran Tertulis', '2026-01-14', '2026-01-15', '2026-01-16', 1, 'Memberikan surat teguran kepada orang tua Test perubaha', 'Sedang Berjalan', NULL, 'test', 3, NULL, NULL, 0, NULL, '[\"uploads/sanctions/2026/01/1768401638_631d8f41974e4d3c.png\"]', 'Test', '2026-01-14 21:12:08', '2026-01-14 21:40:55', NULL),
(12, 32, 'Pemanggilan Orang Tua', '2026-01-16', '2026-01-18', '2026-01-18', 0, 'testtsefsdsdfsdfsdf', 'Dijadwalkan', '0000-00-00', '', 3, NULL, NULL, 1, '2026-01-16 05:00:52', '[\"uploads/sanctions/2026/01/1768512104_65fa519fc16a4f98.png\"]', 'testsesfsdsf', '2026-01-16 04:21:44', '2026-01-16 05:00:52', NULL);

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

--
-- Dumping data untuk tabel `session_notes`
--

INSERT INTO `session_notes` (`id`, `session_id`, `created_by`, `note_type`, `note_content`, `is_important`, `is_confidential`, `attachments`, `created_at`, `updated_at`, `deleted_at`) VALUES
(17, 38, 3, 'Observasi', 'Testgamvbar', 0, 0, '[\"uploads\\/counseling_notes\\/1764346199_5f4b69e719a913e6b784.png\"]', '2025-11-28 23:09:53', '2025-12-16 21:03:24', NULL),
(18, 38, 3, 'Observasi', 'TESTT', 0, 0, '[]', '2025-11-28 23:10:11', '2025-12-16 21:33:14', NULL),
(19, 36, 3, 'Intervensi', 'testtset', 0, 0, '[]', '2025-11-28 23:10:32', '2026-01-08 14:25:36', NULL),
(20, 37, 3, 'Observasi', 'tsetsesetsest', 0, 0, '[\"uploads\\/counseling_notes\\/1764346259_8e3548ab455b4a434556.png\"]', '2025-11-28 23:10:52', '2025-11-28 23:10:59', NULL),
(21, 39, 3, 'Observasi', 'testsesetes', 0, 0, '[\"uploads\\/counseling_notes\\/1764350210_19817bb55fbf4b657510.png\"]', '2025-11-28 23:16:58', '2025-11-29 00:16:50', NULL),
(22, 39, 3, 'Follow-up', 'TesPENTING', 1, 1, '[]', '2025-11-29 00:16:35', '2025-11-29 01:18:37', NULL),
(23, 39, 3, 'Observasi', 'testsdvvdxcvvx', 0, 0, '[\"uploads\\/counseling_notes\\/1764350937_59ff4ca3f285aab963e3.pdf\",\"uploads\\/counseling_notes\\/1764353925_917c9df03ff620149ad2.png\"]', '2025-11-29 00:28:21', '2025-11-29 01:18:45', NULL),
(24, 39, 3, 'Observasi', 'testjeniscatatan', 0, 0, '[]', '2025-11-29 00:46:21', '2025-11-29 00:50:05', '2025-11-29 00:50:05'),
(25, 39, 3, 'Diagnosis', 'gsdgsd', 0, 0, '[]', '2025-11-29 00:50:17', '2025-11-29 01:08:39', NULL),
(26, 39, 3, 'Follow-up', 'tESTFOLLOW UP', 1, 1, '[\"uploads\\/counseling_notes\\/1764353951_ff53bcb45bf777cb6280.png\"]', '2025-11-29 01:19:11', '2025-11-29 01:19:11', NULL),
(27, 41, 3, 'Follow-up', 'Testperubahan catatan sesi', 0, 0, '[\"uploads\\/counseling_notes\\/1768399575_6b764c77f69a34fb6af5.png\"]', '2026-01-14 21:05:51', '2026-01-14 21:06:15', NULL);

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

--
-- Dumping data untuk tabel `session_participants`
--

INSERT INTO `session_participants` (`id`, `session_id`, `student_id`, `attendance_status`, `participation_note`, `is_active`, `joined_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(12, 37, 1, 'Hadir', 'testsetse', 1, '2025-11-28 23:09:06', '2025-11-28 23:09:06', '2025-11-28 23:10:49', NULL),
(13, 37, 2, 'Hadir', NULL, 1, '2025-11-28 23:09:06', '2025-11-28 23:09:06', '2025-11-28 23:09:06', NULL),
(14, 38, 1, 'Hadir', 'TEST', 1, '2025-11-28 23:09:21', '2025-11-28 23:09:21', '2025-12-16 21:03:59', NULL),
(15, 38, 2, 'Hadir', NULL, 1, '2025-11-28 23:09:21', '2025-11-28 23:09:21', '2025-11-28 23:09:21', NULL),
(16, 39, 1, 'Hadir', 'Adsv', 1, '2025-11-28 23:16:50', '2025-11-28 23:16:50', '2026-01-02 14:24:53', NULL),
(17, 39, 2, 'Hadir', 'test23', 1, '2025-11-28 23:16:50', '2025-11-28 23:16:50', '2025-11-29 01:18:20', NULL),
(18, 38, 32, 'Hadir', NULL, 1, '2025-12-16 21:03:24', '2025-12-16 21:03:24', '2025-12-16 21:03:24', NULL),
(19, 41, 1, 'Hadir', 'gdxg', 1, '2026-01-14 21:03:02', '2026-01-14 21:03:02', '2026-01-14 21:05:30', NULL),
(20, 41, 2, 'Hadir', 'gsdgsd', 1, '2026-01-14 21:03:02', '2026-01-14 21:03:02', '2026-01-14 21:05:33', NULL),
(21, 42, 1, 'Hadir', NULL, 1, '2026-01-14 21:03:15', '2026-01-14 21:03:15', '2026-01-14 21:03:15', NULL),
(22, 42, 2, 'Hadir', NULL, 1, '2026-01-14 21:03:15', '2026-01-14 21:03:15', '2026-01-14 21:03:15', NULL),
(23, 42, 32, 'Hadir', NULL, 1, '2026-01-14 21:03:15', '2026-01-14 21:03:15', '2026-01-14 21:03:15', NULL);

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
(2, 8, 1, '0123456790', '2024002', 'P', 'Jakarta', '2008-08-20', 'Islam', 'Jl. Sudirman No. 45, Bandung', 10, '2024-07-01', 'Aktif', 20, '2025-10-06 23:09:44', '2026-01-19 10:00:30', NULL),
(15, 32, 1, '1234567890', '12345678901234', 'L', 'Bandung', '2002-12-12', 'Islam', 'Kp. Leuwigadung RT. 3 RW. 10 Ds. Ciapus Kec. Banjaran Kab. Bandung Jawa Barat 40377', 26, '2025-11-13', 'Alumni', 0, '2025-11-13 12:16:26', '2025-11-14 03:19:41', NULL),
(21, 38, 1, '0222441678', '123454559042', 'P', 'Bandu', '2008-04-01', 'Islam', 'testts', 26, '2025-11-14', 'Alumni', 0, '2025-11-14 12:52:26', '2025-11-15 04:13:40', NULL),
(26, 46, 2, '0322444678', '123454259542', 'L', 'Bandung', '2007-11-22', 'Islam', 'testestsetestsetsetestes', 26, '2025-12-10', 'Aktif', 15, '2025-12-10 03:26:28', '2026-01-19 10:00:30', NULL),
(32, 56, 1, '1231117890', '156351235674', 'L', 'Testtempat1', '2007-01-02', 'Islam', 'T33egsed3w', 57, '2024-07-01', 'Aktif', 5, '2025-12-11 15:12:37', '2026-01-19 10:00:30', NULL),
(33, 58, 2, '1231112323', '154236578954', 'P', 'Testtempat2', '2008-02-01', 'Kristen', 'Alamatimport2', 59, '2024-07-01', 'Aktif', 0, '2025-12-11 15:12:37', '2026-01-19 10:00:30', NULL),
(34, 60, 3, '1231112567', '154236578567', 'L', 'Testtempat3', '2009-03-03', 'Hindu', 'Alamatimport3', 59, '2024-07-01', 'Aktif', 0, '2025-12-11 15:12:37', '2026-01-19 10:00:30', NULL),
(35, 63, 1, '0123456999', '17774559042', 'L', 'Bandung', '2012-01-01', 'Islam', 'testsevdsdvfse', 59, '2026-01-02', 'Pindah', 0, '2026-01-02 14:00:04', '2026-01-02 14:00:18', NULL),
(36, 64, 2, '1234561890', '5424564452', 'L', 'Bandung', '2006-05-15', 'Islam', 'Test Alamat', 65, '2026-01-02', 'Aktif', 0, '2026-01-02 14:03:15', '2026-01-19 10:00:30', NULL),
(37, 67, 4, '1234569999', '512124452', 'L', 'Bandung', '2005-05-15', 'Islam', 'Jl. Contoh No. 123', 68, '2026-01-02', 'Aktif', 0, '2026-01-02 14:47:53', '2026-01-19 10:00:30', NULL),
(38, 73, 7, '1217897890', '32156', 'L', 'Bandung', '2008-05-15', 'Islam', 'Jl. Contoh No. 123', 74, '2026-01-17', 'Aktif', 0, '2026-01-17 02:55:30', '2026-01-19 10:00:30', NULL);

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

--
-- Dumping data untuk tabel `student_saved_careers`
--

INSERT INTO `student_saved_careers` (`id`, `student_id`, `career_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 1, 5, '2025-12-10 03:32:30', '2025-12-10 03:32:30', NULL);

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

--
-- Dumping data untuk tabel `student_saved_universities`
--

INSERT INTO `student_saved_universities` (`id`, `student_id`, `university_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 1, 1, '2025-11-28 15:56:40', '2025-11-28 15:56:40', NULL);

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

--
-- Dumping data untuk tabel `university_info`
--

INSERT INTO `university_info` (`id`, `university_name`, `alias`, `accreditation`, `location`, `website`, `logo`, `description`, `faculties`, `programs`, `admission_info`, `tuition_range`, `scholarships`, `contacts`, `is_public`, `is_active`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Institut Teknologi Bandung', 'ITB', 'Unggul', 'Bandung', 'https://www.itb.ac.id', 'https://itb.ac.id/assets/images/logo-itb-1920-new.png', 'test', '[\"test\"]', '[{\"name\": \"Informatika\", \"degree\": \"S1\"}]', 'test', 'test', '[\"test\"]', '[\"tetestsetest\"]', 1, 1, 3, '2025-10-22 12:34:08', '2025-11-30 17:43:07', NULL),
(2, 'Universitas Padjadjaran 123', 'UNPAD', 'Unggul', 'Bandung', 'https://www.unpad.ac.id', NULL, NULL, NULL, '[{\"name\": \"Keperawatan\", \"degree\": \"D3\"}]', NULL, NULL, NULL, NULL, 1, 1, 3, '2025-10-22 12:34:08', '2025-11-25 04:55:23', NULL),
(3, 'testsesetse', 'testestestes', 'B', 'tsetsetsetse', NULL, NULL, 'tsetsesetest', '[\"tsetsetes\"]', '[{\"name\": \"tsetestest\", \"degree\": \"tsetset\"}]', 'setsetse', 'tsetsese', '[\"tsetsetes\"]', '[\"testestse\"]', 1, 1, 3, '2025-11-29 11:37:08', '2025-11-29 11:37:08', NULL),
(4, 'testsetse', 'tsetsetestesteststsetse', 'Baik', 'tsetsetsetestestes', NULL, NULL, 'setsetsetestestsetest', '[\"testestsetestestsettes\"]', '[{\"name\": \"tsetsetes\", \"degree\": \"\"}]', 'ttsetest', 'tsesetsetse', '[\"tsetsetset\"]', '[\"setestsetsetset\"]', 1, 1, 3, '2025-11-30 17:43:33', '2025-11-30 17:43:33', NULL);

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
(1, 1, 'admin', 'admin@sibk.sch.id', '$2y$12$heMORrm/RzkMfg3wyMFQcevCzB1tZLMHkuKlFc/D8IOafssfQyhTa', 'Administrator Sistem', '081234567890', 'uploads/profile_photos/1/avatar_1_1762425658.png', 1, '2026-01-19 09:29:22', '2025-10-06 23:09:44', '2026-01-19 09:29:22', NULL),
(2, 2, 'koordinator', 'koordinator.bk@sibk.sch.id', '$2y$12$zns3nUkxaz0J3s0R8v72h.ymItjjXOmGOs73ncM7xe2bQ7ElLOk4C', 'Drs. Ahmad Supriyadi, M.Pd', '081234567891', 'uploads/profile_photos/2/avatar_2_1762493958.png', 1, '2026-01-17 09:15:11', '2025-10-06 23:09:44', '2026-01-17 09:15:11', NULL),
(3, 3, 'gurubk1', 'siti.nurhaliza@sibk.sch.id', '$2y$12$YDMZVFh7ZvFJrFxk8LiOpeQc5sTlN6aHmOj/GCEhcewVk.X1CJkmS', 'Siti Nurhaliza, S.Pd', '08123456789210', NULL, 1, '2026-01-19 09:46:18', '2025-10-06 23:09:44', '2026-01-19 09:46:18', NULL),
(4, 3, 'gurubk2', 'budi.santoso@sibk.sch.id', '$2y$12$Z2aKT1gMb0cv4/t/6sKHG.AFq8LfnJZh1Cp.Q/FUmip8V0JQOsBKW', 'Budi Santoso, S.Psi', '081234567893', NULL, 1, '2026-01-16 04:07:31', '2025-10-06 23:09:44', '2026-01-16 04:07:31', NULL),
(5, 4, 'walikelas1', 'rina.wati@sibk.sch.id', '$2y$12$8fASGFimY2i.BLFrUa0n9uSn3oNaLeemn4FtJ3Sd.nXdofSDFEMI6', 'Rina Wati, S.Pd', '081234567894', NULL, 1, '2026-01-17 05:45:24', '2025-10-06 23:09:44', '2026-01-17 05:45:24', NULL),
(6, 4, 'walikelas2', 'dedi.kusuma@sibk.sch.id', '$2y$12$vFGZKXi.ljeFePUqgzCyjO4ejouycRauS4wdhLVDKg2VUjushiw2G', 'Dedi Kusuma, S.Pd', '081234567890', NULL, 1, '2026-01-07 15:58:44', '2025-10-06 23:09:44', '2026-01-07 15:58:44', NULL),
(7, 5, 'siswa001', 'ahmad.fajar@student.sibk.com', '$2y$12$r3Tqwc446x/1vn1MK8D7EeTn1gSg/N2wOb2LT86HASu/WYx3XYNie', 'Ahmad Fajar Nugraha', '081234567896', 'uploads/profile_photos/7/avatar_7_1764520994.png', 1, '2026-01-17 02:59:28', '2025-10-06 23:09:44', '2026-01-17 02:59:28', NULL),
(8, 5, 'siswa002', 'putri.amanda@student.sibk.sch.id', '$2y$12$uRhyZdMTsek8/Twgvuitfu/E2taWjCSJnAvpMAWkX3Z09PdJi9Lkm', 'Putri Amanda Sari', '081234567897', NULL, 1, '2026-01-05 13:55:13', '2025-10-06 23:09:44', '2026-01-05 13:55:13', NULL),
(9, 6, 'parent001', 'suryanto@gmail.com', '$2y$12$ynxbMtySIlOEHgngx0GynecxtjiGcSzofNGJm8fveae8R8bTBkDXK', 'Suryanto', '0859106732065', NULL, 1, '2026-01-17 01:50:39', '2025-10-06 23:09:44', '2026-01-17 01:50:39', NULL),
(10, 6, 'parent002', 'dewi.lestari@gmail.com', '$2y$12$2.dHWWeeq2dOTQskJQLjIeiCDzL9ZSn0/UOYJKwr6/wGWLwFmgfy2', 'Dewi Lestari', '081234567899', NULL, 1, '2026-01-05 13:56:01', '2025-10-06 23:09:44', '2026-01-05 13:56:01', NULL),
(26, 6, 'test', 'test@gmail.com', '$2y$12$/fN1TzaNsy4A2r9SXuu8Ke6sMqEm5cNzBZv1FNqpe8M07MBHryV2S', 'test', '08123556634634', NULL, 1, NULL, '2025-11-12 14:46:16', '2025-11-12 14:46:24', NULL),
(32, 5, 'testsiswa', 'shakiralhamdi@gmail.com', '$2y$12$7Kor/6JCFRFehAEc8CweFuzWMMRWs9WB6gJbrcj9CJxJngi.11qBy', 'Reka Shakiralhamdi Latief', '0859106732065', NULL, 1, NULL, '2025-11-13 12:16:26', '2025-11-13 12:16:26', NULL),
(38, 5, 'testsiswa1', 'test1@gmail.com', '$2y$12$G.H4hAiMFMn38j3L1gFK6esHzqdazRvqTjkOJ8asH/Xe1GSK8n8cq', 'test4124', '0859132732065', NULL, 1, NULL, '2025-11-14 12:52:25', '2025-11-15 04:13:40', NULL),
(46, 5, 'test23r', 'Test3@gmail.com', '$2y$12$2MTk4XDx1bOZ0gPp4v21B./8z8cBlqRURcVoFbVr4ST6VDZ1wJ3r2', 'TEst123', '089273481345', NULL, 1, NULL, '2025-12-10 03:26:27', '2025-12-10 03:26:27', NULL),
(56, 5, '1231117890', 'Testimport1@gmail.com', '$2y$12$EcxWOnuafNQ6kgjow6KQKuDk6xPZyYrqmaw7XuSXZASSPzl0XHji.', 'Testimport1', '081234567822', NULL, 1, '2026-01-02 14:37:18', '2025-12-11 15:12:36', '2026-01-02 14:37:18', NULL),
(57, 6, 'testorangtua1_7890', 'emailorangtua1@gmail.com', '$2y$12$RVQfrKxg.tNMZ.D3sUHbSuBqZuea9s.6RQ1o2qPLCKdF9CvjhTPKO', 'Testorangtua1', '081298765444', NULL, 1, NULL, '2025-12-11 15:12:36', '2025-12-11 15:12:36', NULL),
(58, 5, '1231112323', 'Testimport2@gmail.com', '$2y$12$rI9umLoUO1on.BKQzGntDugJz3Krf.Yjin29uIRWuAUrE1K7V490a', 'Testimport2', '081234567811', NULL, 1, '2025-12-11 15:29:25', '2025-12-11 15:12:37', '2026-01-07 13:20:39', NULL),
(59, 6, 'testorangtua2_2323', 'emailorangtua2@gmail.com', '$2y$12$CVPfUUCiOTyE4AlLy/qFL.VGVt38rRX/Nt3/OF4jGzgQdQJVW1dcq', 'Testorangtua2', '081298765455', NULL, 1, '2025-12-11 15:29:37', '2025-12-11 15:12:37', '2025-12-11 15:29:37', NULL),
(60, 5, '1231112567', 'Testimport3@gmail.com', '$2y$12$BjVa5rZqZvJHnZuIgx/x3OIjUnG0GpRuTzqhE0aozWgQ13Y3MqwNi', 'Testimport3', '081234567811', NULL, 1, NULL, '2025-12-11 15:12:37', '2025-12-11 15:12:37', NULL),
(63, 5, 'test123', 'testestse@gmail.com', '$2y$12$WgqqmkLGbqbNifaz9cy8A.VhgtHzi76LN57yfnCrHo0KMQq1ve5Da', 'testbuatakun', '085666711546', NULL, 1, NULL, '2026-01-02 14:00:03', '2026-01-02 14:00:18', NULL),
(64, 5, '1234561890', 'testimport10@example.com', '$2y$12$6ebzCASiM5nygZzPYvfyK.jjY2u8jEVIolaXwkNskJNe9Jb6xzHZy', 'Testimport100', '081234567890', NULL, 1, NULL, '2026-01-02 14:03:15', '2026-01-02 14:12:45', NULL),
(65, 6, 'arial_1890', 'arial@example.com', '$2y$12$22gFlg4u/3JAHFBYJ/.tU.qWLX/oEFkQkCrzChBZrdmtQFoPfvTpa', 'Arial', '081298765432', NULL, 1, NULL, '2026-01-02 14:03:15', '2026-01-02 14:03:15', NULL),
(66, 3, 'testBK', 'testBK123@gmail.com', '$2y$12$U8mEH.BXMvcsvJSQQ/cokeUu23njwfiTJ6gNKpvDBUZhskIxpogXG', 'TestBK123', '08232545895', NULL, 1, NULL, '2026-01-02 14:14:18', '2026-01-02 14:14:18', NULL),
(67, 5, '1234569999', 'testimport11@example.com', '$2y$12$PgFgxO153o13vLJsyBZT5uimRDMahbepXWAFKU8Z./BTiW4mp5ejK', 'Testimport11', '081234567890', NULL, 1, '2026-01-02 14:48:32', '2026-01-02 14:47:53', '2026-01-02 14:48:32', NULL),
(68, 6, 'bold_9999', 'bold@example.com', '$2y$12$2chwEfjrucnqz0bZPpJ3Jep8OHY8gGJSHCO2W5fPzwVXSK6/PEDRG', 'Bold', '081298765432', NULL, 1, '2026-01-02 14:48:59', '2026-01-02 14:47:53', '2026-01-02 14:48:59', NULL),
(69, 4, 'walikelas3', 'walikelas3@yahoo.com', '$2y$12$Ia8clSSbzfKfDUJX1Uys4OduqbNg2mvspeMrAD8.qdPGdWxrn6wOO', 'Walikelas3Lengkap', '087132657952', NULL, 1, NULL, '2026-01-05 09:54:44', '2026-01-14 00:46:15', '2026-01-14 00:46:15'),
(71, 3, 'gurubk3', 'gurubk3@gmail.com', '$2y$12$ocS/wCdCjaLrzGOWCUybseKbxBoKJmWb1CeZ5A4UTB311OzrE96dS', 'Gurubk3', '08544136521', NULL, 1, NULL, '2026-01-13 23:23:35', '2026-01-13 23:24:32', NULL),
(72, 3, 'gurubk4', 'gurubk4@gmail.com', '$2y$12$7BgignSo3ERCK/mvMV6.buRt7OKqtND3V3EYBrbE8SZoW9lN0cBsO', 'Gurubk4123', '0851342785', NULL, 1, NULL, '2026-01-14 00:36:06', '2026-01-14 00:36:24', NULL),
(73, 5, '1217897890', 'aris@example.com', '$2y$12$1VBJ1t0iYWwMujWwOExsy.OUskiX1.Pl7QYBpsNwnrki5aajIFvgK', 'Aris Fatuta', '081234567890', NULL, 1, '2026-01-17 02:58:43', '2026-01-17 02:55:30', '2026-01-17 02:58:43', NULL),
(74, 6, 'alfa_ramian_yuda_7890', 'alfa@example.com', '$2y$12$NsV8Oi2deWDINnwiTq2G8.918l/DY.KyPpGpH.xgLgumsGQZaPJB6', 'Alfa Ramian Yuda', '081298765432', NULL, 1, '2026-01-17 02:59:08', '2026-01-17 02:55:30', '2026-01-17 02:59:08', NULL);

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

--
-- Dumping data untuk tabel `violations`
--

INSERT INTO `violations` (`id`, `student_id`, `category_id`, `violation_date`, `violation_time`, `location`, `description`, `witness`, `evidence`, `reported_by`, `handled_by`, `status`, `resolution_notes`, `resolution_date`, `parent_notified`, `parent_notified_at`, `is_repeat_offender`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(25, 1, 5, '2025-11-28', '08:00:00', 'TEST', 'TESTTESTSETTSEESTEST', 'TEST', '[\"uploads/violations/2025/11/1764354382_f077d57ea10d294b.png\"]', 3, 3, 'Dilaporkan', NULL, NULL, 0, NULL, 0, 'TEST', '2025-11-29 01:26:22', '2025-11-29 01:26:46', '2025-11-29 01:26:46'),
(26, 1, 1, '2025-11-26', '08:00:00', 'TESTSET', 'TESTSETESTESTESTES', 'TSETSETES', NULL, 3, 3, 'Dilaporkan', NULL, NULL, 0, NULL, 0, 'TSETSESETSE', '2025-11-29 01:27:38', '2025-11-29 01:29:10', '2025-11-29 01:29:10'),
(27, 1, 5, '2025-11-28', '07:00:00', 'TESTSETSETSE', 'TESTESTESTESTESTSE', 'TSETSETESTES', NULL, 3, 3, 'Dilaporkan', NULL, NULL, 0, NULL, 0, 'TSETSEEST', '2025-11-29 01:28:44', '2025-11-29 03:53:41', '2025-11-29 03:53:41'),
(28, 1, 9, '2025-11-29', '12:00:00', 'test', 'tsettestsesetse', 'test', '[\"uploads/violations/2025/11/1764390491_0c671a4706f9748c.png\"]', 3, 3, 'Selesai', 'test', '2025-11-30', 1, '2025-11-29 11:28:44', 0, 'tsetse', '2025-11-29 11:28:11', '2025-12-17 14:06:21', NULL),
(29, 1, 1, '2025-11-29', '07:00:00', 'test', 'tsetsetsetsetes', 'tsetset', '[\"uploads/violations/2025/11/1764396624_af6cde4d23673a92.png\"]', 3, 3, 'Dilaporkan', NULL, NULL, 0, NULL, 0, 'tsetsese', '2025-11-29 13:10:24', '2025-11-29 13:10:24', NULL),
(31, 1, 4, '2025-12-06', '14:00:00', 'Kelas', 'Adalafastestr', 'Abang', '[\"uploads/violations/2025/12/1765057064_25eced593141edec.png\"]', 5, 3, 'Selesai', '', '2026-01-13', 0, NULL, 1, NULL, '2025-12-07 04:37:44', '2026-01-13 21:26:58', NULL),
(32, 1, 2, '2025-12-06', '07:00:00', 'Test Lapangan', 'Test556556556', 'testsets', '[\"uploads/violations/2026/01/1768400109_f28b30627968b2ff.png\"]', 3, 3, 'Dalam Proses', '', NULL, 0, NULL, 1, 'testsete', '2025-12-07 04:44:07', '2026-01-14 21:15:09', NULL),
(33, 26, 10, '2026-01-02', '08:41:00', 'Di kelas', 'Menyontek dengan sengaja', 'Pengawas', '[\"uploads/violations/2026/01/1768400934_d215e892cfd54eb1.png\"]', 2, 3, 'Dilaporkan', '', NULL, 0, NULL, 0, 'testtambahan catatan', '2026-01-13 21:42:45', '2026-01-14 21:28:54', NULL),
(34, 2, 5, '2026-01-14', '09:00:00', 'Kelas', 'Membawa perangkat keras laptop ke kelas tanpa izin', 'Guru Senbud', '[\"uploads/violations/2026/01/1768399876_1c6267d6a983a229.png\"]', 3, 3, 'Dalam Proses', NULL, NULL, 0, NULL, 0, 'Testcatatanfinal pelanggaran', '2026-01-14 21:11:16', '2026-01-14 21:12:08', NULL),
(35, 32, 2, '2026-01-15', '12:00:00', 'Lapangan', 'Testfinal Walikelas lapor pelanggaran', 'Guru', '[\"uploads/violations/2026/01/1768421927_d1727310720a1eab.png\",\"uploads/violations/2026/01/1768421947_6fef87819bc671ee.png\"]', 5, 3, 'Dilaporkan', NULL, NULL, 0, NULL, 0, NULL, '2026-01-15 03:18:47', '2026-01-15 03:19:39', NULL),
(36, 2, 4, '2026-01-15', '05:00:00', 'Kelas', 'Test2final', 'Abang', '[\"uploads/violations/2026/01/1768422021_c12979c10c6a9be8.png\"]', 5, 3, 'Dilaporkan', NULL, NULL, 0, NULL, 0, NULL, '2026-01-15 03:20:21', '2026-01-15 03:20:21', NULL),
(37, 2, 4, '2026-01-15', '08:00:00', 'Testtemnpat', 'TEsfeasdfgd123', 'testsets', '[\"uploads/violations/2026/01/1768427155_2b7e65eb645c729d.png\"]', 5, 3, 'Dilaporkan', NULL, NULL, 0, NULL, 1, NULL, '2026-01-15 03:21:36', '2026-01-15 04:45:55', NULL);

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=796;

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT untuk tabel `violation_categories`
--
ALTER TABLE `violation_categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
