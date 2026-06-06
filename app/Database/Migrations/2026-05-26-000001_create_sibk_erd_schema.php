<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSibkErdSchema extends Migration
{
    /**
     * Drop order follows foreign-key dependencies from child to parent.
     *
     * @var list<string>
     */
    private array $dropOrder = [
        'email_verifications',
        'password_resets',
        'simulation_access_grants',
        'student_saved_universities',
        'student_saved_careers',
        'assessment_answers',
        'assessment_results',
        'assessment_assignees',
        'assessment_questions',
        'assessments',
        'message_participants',
        'messages',
        'notifications',
        'violation_submissions',
        'session_participants',
        'session_notes',
        'counseling_sessions',
        'students',
        'classes',
        'academic_years',
        'role_permissions',
        'users',
        'permissions',
        'roles',
        'settings',
        'university_info',
        'career_options',
    ];

    public function up()
    {
        $this->dropExistingTables();

        foreach ($this->schemaStatements() as $statement) {
            $this->db->query($statement);
        }
    }

    public function down()
    {
        $this->dropExistingTables();
    }

    private function dropExistingTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->dropOrder as $table) {
            $this->forge->dropTable($table, true);
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @return list<string>
     */
    private function schemaStatements(): array
    {
        return [
            <<<'SQL'
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    profile_photo VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_users_role_id (role_id),
    INDEX idx_users_deleted_at (deleted_at),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_role_permissions_pair (role_id, permission_id),
    INDEX idx_role_permissions_permission_id (permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE academic_years (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year_name VARCHAR(50) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    semester ENUM('Ganjil', 'Genap') NOT NULL DEFAULT 'Ganjil',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_academic_years_active (is_active),
    INDEX idx_academic_years_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    major VARCHAR(50) NULL,
    homeroom_teacher_id INT UNSIGNED NULL,
    counselor_id INT UNSIGNED NULL,
    max_students INT NOT NULL DEFAULT 36,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_classes_academic_year_id (academic_year_id),
    INDEX idx_classes_homeroom_teacher_id (homeroom_teacher_id),
    INDEX idx_classes_counselor_id (counselor_id),
    INDEX idx_classes_grade_level (grade_level),
    CONSTRAINT fk_classes_academic_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_classes_homeroom_teacher FOREIGN KEY (homeroom_teacher_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_classes_counselor FOREIGN KEY (counselor_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    class_id INT UNSIGNED NULL,
    nisn VARCHAR(20) NULL UNIQUE,
    nik VARCHAR(20) NULL UNIQUE,
    gender ENUM('L', 'P') NULL,
    birth_place VARCHAR(100) NULL,
    birth_date DATE NULL,
    religion VARCHAR(50) NULL,
    address TEXT NULL,
    special_needs VARCHAR(100) NULL,
    disability VARCHAR(100) NULL,
    kip_pip_number VARCHAR(50) NULL,
    father_name VARCHAR(255) NULL,
    mother_name VARCHAR(255) NULL,
    guardian_name VARCHAR(255) NULL,
    parent_id INT UNSIGNED NULL,
    admission_date DATE NULL,
    status ENUM('Aktif', 'Alumni', 'Pindah', 'Keluar', 'Tidak Aktif') NOT NULL DEFAULT 'Aktif',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_students_class_id (class_id),
    INDEX idx_students_parent_id (parent_id),
    INDEX idx_students_status (status),
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_students_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE counseling_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NULL,
    counselor_id INT UNSIGNED NULL,
    class_id INT UNSIGNED NULL,
    session_type ENUM('Individu', 'Kelompok', 'Klasikal') NOT NULL DEFAULT 'Individu',
    session_date DATE NOT NULL,
    session_time TIME NULL,
    location VARCHAR(150) NULL,
    topic VARCHAR(255) NULL,
    problem_description TEXT NULL,
    session_summary TEXT NULL,
    follow_up_plan TEXT NULL,
    status ENUM('Dijadwalkan', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Dijadwalkan',
    is_confidential TINYINT(1) NOT NULL DEFAULT 1,
    duration_minutes INT NULL,
    cancellation_reason TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_counseling_sessions_student_id (student_id),
    INDEX idx_counseling_sessions_counselor_id (counselor_id),
    INDEX idx_counseling_sessions_class_id (class_id),
    INDEX idx_counseling_sessions_status (status),
    INDEX idx_counseling_sessions_date (session_date),
    CONSTRAINT fk_counseling_sessions_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_counseling_sessions_counselor FOREIGN KEY (counselor_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_counseling_sessions_class FOREIGN KEY (class_id) REFERENCES classes(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE session_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    note_type VARCHAR(50) NOT NULL DEFAULT 'Umum',
    note_content TEXT NOT NULL,
    is_important TINYINT(1) NOT NULL DEFAULT 0,
    is_confidential TINYINT(1) NOT NULL DEFAULT 1,
    attachments TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_session_notes_session_id (session_id),
    INDEX idx_session_notes_created_by (created_by),
    CONSTRAINT fk_session_notes_session FOREIGN KEY (session_id) REFERENCES counseling_sessions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_session_notes_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE session_participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    attendance_status ENUM('Hadir', 'Izin', 'Sakit', 'Alpha', 'Belum Hadir') NOT NULL DEFAULT 'Belum Hadir',
    participation_note TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    joined_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_session_participants_pair (session_id, student_id),
    INDEX idx_session_participants_student_id (student_id),
    CONSTRAINT fk_session_participants_session FOREIGN KEY (session_id) REFERENCES counseling_sessions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_session_participants_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE violation_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_type ENUM('student', 'parent', 'homeroom') NOT NULL,
    reporter_user_id INT UNSIGNED NOT NULL,
    subject_student_id INT UNSIGNED NULL,
    subject_other_name VARCHAR(190) NULL,
    occurred_date DATE NULL,
    occurred_time TIME NULL,
    location VARCHAR(190) NULL,
    description TEXT NOT NULL,
    witness VARCHAR(190) NULL,
    evidence_json TEXT NULL,
    status ENUM('Diajukan', 'Ditinjau', 'Ditolak', 'Diterima') NOT NULL DEFAULT 'Diajukan',
    handled_by INT UNSIGNED NULL,
    handled_at DATETIME NULL,
    review_notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_violation_submissions_reporter_user_id (reporter_user_id),
    INDEX idx_violation_submissions_subject_student_id (subject_student_id),
    INDEX idx_violation_submissions_handled_by (handled_by),
    INDEX idx_violation_submissions_status (status),
    CONSTRAINT fk_violation_submissions_reporter FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_violation_submissions_subject_student FOREIGN KEY (subject_student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_violation_submissions_handled_by FOREIGN KEY (handled_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    link VARCHAR(255) NULL,
    data TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_notifications_user_read (user_id, is_read),
    INDEX idx_notifications_type (type),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    created_by INT UNSIGNED NULL,
    is_draft TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_messages_created_by (created_by),
    CONSTRAINT fk_messages_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE message_participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'recipient',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    starred TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_message_participants_pair (message_id, user_id),
    INDEX idx_message_participants_user_id (user_id),
    INDEX idx_message_participants_read (is_read),
    CONSTRAINT fk_message_participants_message FOREIGN KEY (message_id) REFERENCES messages(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_message_participants_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE assessments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    assessment_type VARCHAR(50) NOT NULL,
    evaluation_mode ENUM('pass_fail', 'score_only', 'survey') NOT NULL DEFAULT 'pass_fail',
    target_audience ENUM('Individual', 'Class', 'Grade', 'All') NOT NULL DEFAULT 'Individual',
    target_class_id INT UNSIGNED NULL,
    target_grade VARCHAR(10) NULL,
    created_by INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    start_date DATE NULL,
    end_date DATE NULL,
    duration_minutes INT NULL,
    passing_score DECIMAL(7,2) NULL,
    use_passing_score TINYINT(1) NOT NULL DEFAULT 1,
    show_score_to_student TINYINT(1) NOT NULL DEFAULT 1,
    max_attempts INT NOT NULL DEFAULT 1,
    show_result_immediately TINYINT(1) NOT NULL DEFAULT 1,
    allow_review TINYINT(1) NOT NULL DEFAULT 1,
    result_release_at DATETIME NULL,
    instructions TEXT NULL,
    total_questions INT NOT NULL DEFAULT 0,
    total_participants INT NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_assessments_target_class_id (target_class_id),
    INDEX idx_assessments_created_by (created_by),
    INDEX idx_assessments_active_published (is_active, is_published),
    INDEX idx_assessments_type (assessment_type),
    CONSTRAINT fk_assessments_target_class FOREIGN KEY (target_class_id) REFERENCES classes(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_assessments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE assessment_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL DEFAULT 'Multiple Choice',
    options TEXT NULL,
    correct_answer TEXT NULL,
    points DECIMAL(7,2) NULL DEFAULT 0,
    order_number INT NOT NULL DEFAULT 1,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    explanation TEXT NULL,
    image_url VARCHAR(255) NULL,
    dimension VARCHAR(100) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_assessment_questions_assessment_id (assessment_id),
    INDEX idx_assessment_questions_order (assessment_id, order_number),
    CONSTRAINT fk_assessment_questions_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE assessment_assignees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NULL,
    assigned_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_assessment_assignees_pair (assessment_id, student_id),
    INDEX idx_assessment_assignees_student_id (student_id),
    INDEX idx_assessment_assignees_assigned_by (assigned_by),
    CONSTRAINT fk_assessment_assignees_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessment_assignees_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessment_assignees_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE assessment_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    status ENUM('Assigned', 'In Progress', 'Completed', 'Graded', 'Expired', 'Abandoned') NOT NULL DEFAULT 'Assigned',
    total_score DECIMAL(7,2) NULL DEFAULT 0,
    max_score DECIMAL(7,2) NULL DEFAULT 0,
    percentage DECIMAL(6,2) NULL DEFAULT 0,
    is_passed TINYINT(1) NULL,
    questions_answered INT NULL DEFAULT 0,
    total_questions INT NULL DEFAULT 0,
    correct_answers INT NULL DEFAULT 0,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    graded_at DATETIME NULL,
    time_spent_seconds INT NULL DEFAULT 0,
    interpretation TEXT NULL,
    dimension_scores TEXT NULL,
    recommendations TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    counselor_notes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_assessment_results_attempt (assessment_id, student_id, attempt_number),
    INDEX idx_assessment_results_student_id (student_id),
    INDEX idx_assessment_results_reviewed_by (reviewed_by),
    INDEX idx_assessment_results_status (status),
    CONSTRAINT fk_assessment_results_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessment_results_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessment_results_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE assessment_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    result_id INT UNSIGNED NOT NULL,
    answer_text TEXT NULL,
    answer_option VARCHAR(190) NULL,
    answer_options TEXT NULL,
    score DECIMAL(7,2) NULL,
    is_correct TINYINT(1) NULL,
    is_auto_graded TINYINT(1) NOT NULL DEFAULT 0,
    graded_by INT UNSIGNED NULL,
    graded_at DATETIME NULL,
    feedback TEXT NULL,
    answered_at DATETIME NULL,
    time_spent_seconds INT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_assessment_answers_question_id (question_id),
    INDEX idx_assessment_answers_student_id (student_id),
    INDEX idx_assessment_answers_result_id (result_id),
    INDEX idx_assessment_answers_graded_by (graded_by),
    CONSTRAINT fk_assessment_answers_question FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessment_answers_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessment_answers_result FOREIGN KEY (result_id) REFERENCES assessment_results(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessment_answers_graded_by FOREIGN KEY (graded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE career_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    sector VARCHAR(100) NULL,
    min_education VARCHAR(100) NULL,
    description TEXT NULL,
    required_skills TEXT NULL,
    pathways TEXT NULL,
    avg_salary_idr BIGINT NULL,
    demand_level TINYINT NULL,
    external_links TEXT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_career_options_public_active (is_public, is_active),
    INDEX idx_career_options_created_by (created_by),
    CONSTRAINT fk_career_options_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE university_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    university_name VARCHAR(190) NOT NULL,
    alias VARCHAR(50) NULL,
    accreditation VARCHAR(50) NULL,
    location VARCHAR(150) NULL,
    website VARCHAR(255) NULL,
    logo VARCHAR(255) NULL,
    description TEXT NULL,
    faculties TEXT NULL,
    programs TEXT NULL,
    admission_info TEXT NULL,
    tuition_range VARCHAR(190) NULL,
    scholarships TEXT NULL,
    contacts TEXT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_university_info_public_active (is_public, is_active),
    INDEX idx_university_info_created_by (created_by),
    CONSTRAINT fk_university_info_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE student_saved_careers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    career_id INT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_student_saved_careers_pair (student_id, career_id),
    INDEX idx_student_saved_careers_career_id (career_id),
    CONSTRAINT fk_student_saved_careers_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_student_saved_careers_career FOREIGN KEY (career_id) REFERENCES career_options(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE student_saved_universities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    university_id INT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_student_saved_universities_pair (student_id, university_id),
    INDEX idx_student_saved_universities_university_id (university_id),
    CONSTRAINT fk_student_saved_universities_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_student_saved_universities_university FOREIGN KEY (university_id) REFERENCES university_info(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group` VARCHAR(50) NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `type` VARCHAR(20) NOT NULL DEFAULT 'string',
    autoload TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_settings_group_key (`group`, `key`),
    INDEX idx_settings_autoload (autoload)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NULL,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_token (token),
    INDEX idx_password_resets_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE email_verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NULL,
    INDEX idx_email_verifications_user_id (user_id),
    INDEX idx_email_verifications_token (token),
    CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE simulation_access_grants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    granted_by INT UNSIGNED NULL,
    granted_at DATETIME NULL,
    revoked_by INT UNSIGNED NULL,
    revoked_at DATETIME NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_simulation_access_grants_active (is_active),
    INDEX idx_simulation_access_grants_granted_by (granted_by),
    INDEX idx_simulation_access_grants_revoked_by (revoked_by),
    CONSTRAINT fk_simulation_access_grants_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_simulation_access_grants_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_simulation_access_grants_revoked_by FOREIGN KEY (revoked_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        ];
    }
}
