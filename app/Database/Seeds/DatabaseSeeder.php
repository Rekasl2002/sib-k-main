<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * File Path: app/Database/Seeds/DatabaseSeeder.php
 *
 * Seeder utama SIB-K: mengosongkan seluruh tabel data aplikasi lalu mengisi
 * ulang DATA AWAL (InitialDataSeeder) + DATA CONTOH per fitur
 * (SampleDataSeeder). Dipakai saat:
 * - menyiapkan aplikasi pertama kali setelah `php spark migrate`;
 * - reset aplikasi lewat menu Admin -> Pengaturan -> Reset Data Aplikasi.
 *
 * Tabel bantu autentikasi (password_resets, email_verifications,
 * password_reset_requests) ikut dikosongkan karena isinya terkait akun lama.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Tanggal acuan penulisan data contoh di SampleDataSeeder. Semua tanggal
     * jadwal digeser sejauh (hari ini - acuan) supaya halaman jadwal/agenda
     * selalu berisi campuran kegiatan lampau dan akan datang.
     */
    private const SAMPLE_DATE_ANCHOR = '2026-07-06';

    /**
     * Urutan pengosongan tabel (anak dulu, induk belakangan).
     *
     * @var list<string>
     */
    private array $truncateOrder = [
        'password_resets',
        'email_verifications',
        'password_reset_requests',
        'bk_assignment_status_histories',
        'bk_assignment_targets',
        'bk_assignments',
        'session_notes',
        'session_participants',
        'case_conferences',
        'home_visits',
        'parent_collaborations',
        'guidances',
        'counseling_sessions',
        'bk_service_records',
        'consultation_complaint_attachments',
        'consultation_complaint_subjects',
        'consultation_complaints',
        'student_saved_universities',
        'student_saved_careers',
        'assessment_answers',
        'assessment_results',
        'assessment_assignees',
        'assessment_questions',
        'assessments',
        'message_attachments',
        'message_participants',
        'messages',
        'conversations',
        'notifications',
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

    public function run()
    {
        $this->resetTables();

        $this->call(InitialDataSeeder::class);
        $this->call(SampleDataSeeder::class);

        $this->shiftScheduleDatesToTodayWindow();

        echo "\nBasis data SIB-K berhasil diisi ulang (data awal + data contoh).\n";
        echo "Akun bawaan:\n";
        echo "- admin_1 / admin123 (Admin)\n";
        echo "- admin_2 / admin123 (Admin)\n";
        echo "- koordinator_1 / koordinator123 (Koordinator BK)\n";
        echo "- koordinator_2 / koordinator123 (Koordinator BK)\n";
        echo "- gurubk_1 / gurubk123 (Guru BK)\n";
        echo "- gurubk_2 / gurubk123 (Guru BK)\n";
        echo "- gurubk_3 / gurubk123 (Guru BK)\n";
        echo "- walikelas_1 / walikelas123 (Wali Kelas 10 - C)\n";
        echo "- 1000000001 / 01012010 (Siswa Siswa 1, Kelas 10 - C)\n";
        echo "- 1000000002 / 02022009 (Siswa Siswa 2, Kelas 11 - C)\n";
        echo "- ibu_siswa_1_0001 / 01012010 (Orang Tua Siswa 1)\n";
        echo "- ibu_siswa_2_0002 / 02022009 (Orang Tua Siswa 2)\n";
        echo "PENTING: segera ganti password bawaan setelah aplikasi dipakai sungguhan.\n";
    }

    private function resetTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->truncateOrder as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->table($table)->truncate();
            }
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Menggeser tanggal jadwal/agenda data contoh agar berpusat di hari ini.
     */
    private function shiftScheduleDatesToTodayWindow(): void
    {
        $anchor = strtotime(self::SAMPLE_DATE_ANCHOR);
        $today  = strtotime(date('Y-m-d'));
        $shift  = (int) round(($today - $anchor) / 86400);

        if ($shift === 0) {
            return;
        }

        $scheduleColumns = [
            'bk_service_records'             => ['scheduled_at', 'held_at'],
            'counseling_sessions'            => ['session_date'],
            'bk_assignments'                 => ['due_at', 'assigned_at'],
            'bk_assignment_status_histories' => ['changed_at'],
            'consultation_complaints'        => ['occurred_at', 'handled_at'],
            'assessments'                    => ['start_date', 'end_date'],
            'assessment_assignees'           => ['assigned_at'],
            'assessment_results'             => ['started_at', 'completed_at'],
            'session_notes'                  => ['due_date'],
        ];

        foreach ($scheduleColumns as $table => $columns) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            $fields = $this->db->getFieldNames($table);
            $sets   = [];
            foreach ($columns as $col) {
                if (in_array($col, $fields, true)) {
                    $sets[] = "`{$col}` = DATE_ADD(`{$col}`, INTERVAL {$shift} DAY)";
                }
            }

            if ($sets !== []) {
                $this->db->query("UPDATE `{$table}` SET " . implode(', ', $sets));
            }
        }
    }
}
