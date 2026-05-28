<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Child tables first so re-running the seeder is deterministic.
     *
     * @var list<string>
     */
    private array $truncateOrder = [
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
        'sanctions',
        'violations',
        'violation_categories',
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

    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $this->resetTables();

        $this->seedRoles($now);
        $permissionMap = $this->seedPermissions($now);
        $this->seedRolePermissions($permissionMap, $now);
        $this->seedUsers($now);
        $this->seedAcademicData($now);
        $this->seedViolations($now);
        $this->seedCounseling($now);
        $this->seedCommunication($now);
        $this->seedAssessments($now);
        $this->seedCareerAndUniversity($now);
        $this->seedSettings($now);
        $this->seedSimulationAccess($now);

        echo "\nSIB-K demo database seeded successfully.\n";
        echo "Login demo:\n";
        echo "- admin / admin123\n";
        echo "- koordinator / koordinator123\n";
        echo "- gurubk1 / gurubk123\n";
        echo "- walikelas1 / walikelas123\n";
        echo "- siswa001 / siswa123\n";
        echo "- parent001 / parent123\n\n";
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
     * Insert rows one by one because demo rows intentionally omit optional
     * columns when the value is not relevant.
     *
     * @param list<array<string,mixed>> $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            $this->db->table($table)->insert($row);
        }
    }

    private function seedRoles(string $now): void
    {
        $this->insertRows('roles', [
            ['id' => 1, 'role_name' => 'Admin', 'description' => 'Administrator sistem dengan akses penuh', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'role_name' => 'Koordinator BK', 'description' => 'Koordinator layanan bimbingan konseling', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'role_name' => 'Guru BK', 'description' => 'Guru bimbingan konseling', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'role_name' => 'Wali Kelas', 'description' => 'Wali kelas dan pendamping akademik', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'role_name' => 'Siswa', 'description' => 'Peserta didik', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'role_name' => 'Orang Tua', 'description' => 'Orang tua atau wali siswa', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * @return array<string,int>
     */
    private function seedPermissions(string $now): array
    {
        $permissions = [
            'manage_users' => 'Kelola pengguna sistem',
            'manage_roles' => 'Kelola peran dan izin',
            'manage_academic_data' => 'Kelola kelas, tahun ajaran, dan siswa',
            'manage_counseling_sessions' => 'Kelola sesi konseling',
            'view_counseling_sessions' => 'Lihat sesi konseling',
            'manage_violations' => 'Kelola kasus pelanggaran',
            'view_violations' => 'Lihat kasus pelanggaran',
            'manage_sanctions' => 'Kelola sanksi pelanggaran',
            'manage_assessments' => 'Kelola asesmen',
            'take_assessments' => 'Mengerjakan asesmen',
            'view_student_portfolio' => 'Lihat portofolio siswa',
            'generate_reports' => 'Generate laporan umum',
            'view_reports' => 'Lihat laporan umum',
            'send_messages' => 'Kirim pesan internal',
            'schedule_counseling' => 'Ajukan atau jadwalkan konseling',
            'view_dashboard' => 'Akses dashboard sesuai role',
            'manage_career_info' => 'Kelola informasi karier dan kuliah',
            'view_career_info' => 'Lihat informasi karier dan kuliah',
            'import_export_data' => 'Import dan export data',
            'view_all_students' => 'Lihat semua data siswa',
            'manage_settings' => 'Kelola pengaturan aplikasi',
            'view_reports_aggregate' => 'Lihat laporan agregat',
            'generate_reports_aggregate' => 'Unduh laporan agregat',
            'view_reports_individual' => 'Lihat laporan individual siswa',
            'generate_reports_individual' => 'Unduh laporan individual siswa',
            'manage_light_violations' => 'Kelola pelanggaran ringan untuk wali kelas',
            'submit_violation_submissions' => 'Ajukan laporan atau pengaduan pelanggaran',
            'view_violation_submissions' => 'Lihat pengaduan pelanggaran',
            'review_violation_submissions' => 'Tinjau pengaduan pelanggaran',
            'manage_violation_submissions' => 'Kelola pengaduan pelanggaran',
            'convert_violation_submissions' => 'Konversi pengaduan menjadi kasus pelanggaran',
            'access_simulation_suite' => 'Akses halaman prototipe dan simulasi',
        ];

        $rows = [];
        $map = [];
        $id = 1;

        foreach ($permissions as $name => $description) {
            $rows[] = [
                'id' => $id,
                'permission_name' => $name,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $map[$name] = $id;
            $id++;
        }

        $this->insertRows('permissions', $rows);

        return $map;
    }

    /**
     * @param array<string,int> $permissionMap
     */
    private function seedRolePermissions(array $permissionMap, string $now): void
    {
        $disabledPermissions = [
            'submit_violation_submissions',
            'view_violation_submissions',
            'review_violation_submissions',
            'manage_violation_submissions',
            'convert_violation_submissions',
        ];

        $sets = [
            1 => array_values(array_diff(array_keys($permissionMap), $disabledPermissions)),
            2 => [
                'view_dashboard', 'manage_users', 'view_all_students', 'manage_academic_data',
                'manage_counseling_sessions', 'view_counseling_sessions', 'manage_violations',
                'view_violations', 'manage_sanctions', 'manage_assessments', 'manage_career_info',
                'view_career_info', 'send_messages', 'view_reports_aggregate',
                'generate_reports_aggregate', 'view_reports_individual', 'generate_reports_individual',
                'import_export_data',
            ],
            3 => [
                'view_dashboard', 'view_all_students', 'manage_counseling_sessions',
                'view_counseling_sessions', 'manage_violations', 'view_violations',
                'manage_sanctions', 'manage_assessments', 'manage_career_info',
                'view_career_info', 'send_messages', 'view_reports_individual',
                'generate_reports_individual',
            ],
            4 => [
                'view_dashboard', 'view_all_students', 'view_counseling_sessions',
                'view_violations', 'manage_light_violations', 'view_reports_individual',
                'generate_reports_individual', 'send_messages', 'view_career_info',
            ],
            5 => [
                'view_dashboard', 'take_assessments', 'schedule_counseling',
                'send_messages', 'view_career_info', 'view_violations',
                'view_student_portfolio',
            ],
            6 => [
                'view_dashboard', 'send_messages', 'view_career_info', 'view_violations',
                'view_reports_individual', 'generate_reports_individual',
                'view_student_portfolio',
            ],
        ];

        $rows = [];

        foreach ($sets as $roleId => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                if (! isset($permissionMap[$permissionName])) {
                    continue;
                }

                $rows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionMap[$permissionName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->insertRows('role_permissions', $rows);
    }

    private function seedUsers(string $now): void
    {
        $rows = [
            [1, 1, 'admin', 'admin@sibk.sch.id', 'admin123', 'Administrator Sistem', '081100000001'],
            [2, 2, 'koordinator', 'koordinator.bk@sibk.sch.id', 'koordinator123', 'Koordinator BK 1, S.Pd.MA.', '081100000002'],
            [3, 3, 'gurubk1', 'guru.bk@sibk.sch.id', 'gurubk123', 'Guru BK 2, S.Psi., M.M', '081100000003'],
            [4, 3, 'gurubk2', 'guru.bk.demo@sibk.sch.id', 'gurubk123', 'Guru BK Demo, S.Psi', '081100000004'],
            [5, 4, 'walikelas1', 'wali.kelas@sibk.sch.id', 'walikelas123', 'Wali Kelas 1 Ma’rifah, S.Pd', '081100000005'],
            [6, 4, 'walikelas2', 'wali.kelas.demo@sibk.sch.id', 'walikelas123', 'Wali Kelas Demo, S.Pd', '081100000006'],
            [7, 5, 'siswa001', null, 'siswa123', 'Nama Siswa Contoh', '081234567890'],
            [8, 5, 'siswa002', null, 'siswa123', 'Muhammad Iqbal Ramadhan', '082234567890'],
            [9, 5, 'siswa003', null, 'siswa123', 'Nabila Zahra Fitriani', '083234567890'],
            [10, 6, 'parent001', null, 'parent123', 'Tatang Ruhiyat', '081234567891'],
            [11, 6, 'parent002', null, 'parent123', 'Asep Hidayat', '082234567891'],
            [12, 6, 'parent003', null, 'parent123', 'Iis Nurhayati', '083234567891'],
        ];

        $payload = [];

        foreach ($rows as [$id, $roleId, $username, $email, $password, $name, $phone]) {
            $payload[] = [
                'id' => $id,
                'role_id' => $roleId,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'full_name' => $name,
                'phone' => $phone,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertRows('users', $payload);
    }

    private function seedAcademicData(string $now): void
    {
        $this->insertRows('academic_years', [
            ['id' => 1, 'year_name' => '2024/2025', 'start_date' => '2024-07-01', 'end_date' => '2025-06-30', 'is_active' => 0, 'semester' => 'Genap', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'year_name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => 1, 'semester' => 'Ganjil', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'year_name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => 0, 'semester' => 'Ganjil', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('classes', [
            ['id' => 1, 'academic_year_id' => 2, 'class_name' => 'Kelas 12 - A', 'grade_level' => '12', 'major' => 'Umum', 'homeroom_teacher_id' => 5, 'counselor_id' => 3, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'academic_year_id' => 2, 'class_name' => 'Kelas 12 - B', 'grade_level' => '12', 'major' => 'Umum', 'homeroom_teacher_id' => 5, 'counselor_id' => 3, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'academic_year_id' => 2, 'class_name' => 'Kelas 12 - C', 'grade_level' => '12', 'major' => 'Umum', 'homeroom_teacher_id' => 5, 'counselor_id' => 3, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'academic_year_id' => 2, 'class_name' => 'Kelas 11 - A', 'grade_level' => '11', 'major' => 'Umum', 'homeroom_teacher_id' => 6, 'counselor_id' => 4, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('students', [
            [
                'id' => 1, 'user_id' => 7, 'class_id' => 1, 'nisn' => '1000000003', 'nik' => '1000000000000003',
                'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2007-09-19', 'religion' => 'Islam',
                'address' => 'Kp. Contoh, Banjaran, Kabupaten Bandung, Jawa Barat 40377',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => 'PIP-2025-0001',
                'father_name' => 'Tatang Ruhiyat', 'mother_name' => 'Neneng Sulastri', 'guardian_name' => 'Tatang Ruhiyat',
                'parent_id' => 10, 'admission_date' => '2025-07-14', 'status' => 'Aktif', 'total_violation_points' => 25,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 2, 'user_id' => 8, 'class_id' => 1, 'nisn' => '1000000004', 'nik' => '1000000000000004',
                'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2008-01-15', 'religion' => 'Islam',
                'address' => 'Jl. Raya Banjaran No. 12, Kabupaten Bandung',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => null,
                'father_name' => 'Asep Hidayat', 'mother_name' => 'Sri Mulyani', 'guardian_name' => 'Asep Hidayat',
                'parent_id' => 11, 'admission_date' => '2025-07-14', 'status' => 'Aktif', 'total_violation_points' => 5,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 3, 'user_id' => 9, 'class_id' => 2, 'nisn' => '1000000005', 'nik' => '1000000000000005',
                'gender' => 'P', 'birth_place' => 'Garut', 'birth_date' => '2007-04-12', 'religion' => 'Islam',
                'address' => 'Kp. Sukamaju, Banjaran, Kabupaten Bandung',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => 'KIP-2025-0003',
                'father_name' => 'Dedi Supriadi', 'mother_name' => 'Iis Nurhayati', 'guardian_name' => 'Iis Nurhayati',
                'parent_id' => 12, 'admission_date' => '2025-07-14', 'status' => 'Aktif', 'total_violation_points' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    private function seedViolations(string $now): void
    {
        $this->insertRows('violation_categories', [
            ['id' => 1, 'category_name' => 'Keterlambatan', 'severity_level' => 'Ringan', 'point_deduction' => 5, 'description' => 'Datang terlambat tanpa keterangan yang sah.', 'examples' => 'Terlambat upacara, terlambat masuk kelas.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'category_name' => 'Kelengkapan Seragam', 'severity_level' => 'Ringan', 'point_deduction' => 5, 'description' => 'Seragam atau atribut sekolah tidak lengkap.', 'examples' => 'Tidak memakai dasi, sepatu tidak sesuai.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'category_name' => 'Membolos', 'severity_level' => 'Sedang', 'point_deduction' => 20, 'description' => 'Meninggalkan pelajaran tanpa izin.', 'examples' => 'Tidak masuk kelas setelah istirahat.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'category_name' => 'Berkelahi Ringan', 'severity_level' => 'Sedang', 'point_deduction' => 20, 'description' => 'Konflik fisik ringan atau adu mulut yang mengganggu.', 'examples' => 'Saling dorong, adu mulut.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'category_name' => 'Bullying', 'severity_level' => 'Berat', 'point_deduction' => 50, 'description' => 'Perundungan fisik, verbal, atau digital.', 'examples' => 'Intimidasi teman, perundungan grup chat.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'category_name' => 'Merusak Fasilitas', 'severity_level' => 'Sedang', 'point_deduction' => 25, 'description' => 'Merusak fasilitas sekolah.', 'examples' => 'Mencoret meja, merusak kursi.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('violations', [
            ['id' => 1, 'student_id' => 1, 'category_id' => 3, 'violation_date' => '2026-05-06', 'violation_time' => '08:10:00', 'location' => 'Koridor kelas X', 'description' => 'Tidak masuk pelajaran pertama setelah istirahat.', 'witness' => 'Pak Dedi', 'evidence' => 'uploads/violations/demo-bukti-pelanggaran.txt', 'reported_by' => 5, 'handled_by' => 3, 'status' => 'Dalam Proses', 'parent_notified' => 0, 'is_repeat_offender' => 0, 'notes' => 'Perlu sesi konseling tindak lanjut.', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'student_id' => 2, 'category_id' => 1, 'violation_date' => '2026-05-09', 'violation_time' => '07:18:00', 'location' => 'Gerbang sekolah', 'description' => 'Datang terlambat 18 menit.', 'witness' => 'Petugas piket', 'reported_by' => 6, 'handled_by' => 4, 'status' => 'Selesai', 'resolution_notes' => 'Siswa diberi pembinaan singkat.', 'resolution_date' => '2026-05-09', 'parent_notified' => 1, 'parent_notified_at' => '2026-05-09 09:30:00', 'is_repeat_offender' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'student_id' => 1, 'category_id' => 2, 'violation_date' => '2026-05-15', 'violation_time' => '07:00:00', 'location' => 'Lapangan upacara', 'description' => 'Tidak memakai atribut lengkap saat upacara.', 'witness' => 'Bu Rina', 'reported_by' => 5, 'handled_by' => 3, 'status' => 'Selesai', 'resolution_notes' => 'Membuat komitmen tertulis.', 'resolution_date' => '2026-05-15', 'parent_notified' => 1, 'parent_notified_at' => '2026-05-15 10:00:00', 'is_repeat_offender' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('sanctions', [
            ['id' => 1, 'violation_id' => 1, 'sanction_type' => 'Pembinaan BK', 'sanction_date' => '2026-05-07', 'start_date' => '2026-05-07', 'end_date' => '2026-05-14', 'duration_days' => 7, 'description' => 'Mengikuti pembinaan perilaku bersama Guru BK.', 'status' => 'Sedang Berjalan', 'assigned_by' => 3, 'documents' => 'uploads/violations/demo-sanksi.txt', 'notes' => 'Dipantau oleh wali kelas.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Fitur pengaduan pelanggaran belum diaktifkan, jadi data demonya tidak diisi.
    }

    private function seedCounseling(string $now): void
    {
        $this->insertRows('counseling_sessions', [
            ['id' => 1, 'student_id' => 1, 'counselor_id' => 3, 'class_id' => 1, 'session_type' => 'Individu', 'session_date' => '2026-05-20', 'session_time' => '09:00:00', 'location' => 'Ruang BK 1', 'topic' => 'Tindak lanjut membolos', 'problem_description' => 'Siswa perlu menggali penyebab meninggalkan kelas.', 'session_summary' => 'Siswa menyampaikan kesulitan mengikuti pelajaran pertama.', 'follow_up_plan' => 'Pertemuan lanjutan dengan wali kelas.', 'status' => 'Selesai', 'is_confidential' => 1, 'duration_minutes' => 45, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'student_id' => 2, 'counselor_id' => 4, 'class_id' => 1, 'session_type' => 'Individu', 'session_date' => '2026-05-27', 'session_time' => '10:00:00', 'location' => 'Ruang BK 2', 'topic' => 'Kedisiplinan pagi', 'problem_description' => 'Membahas pola keterlambatan.', 'status' => 'Dijadwalkan', 'is_confidential' => 1, 'duration_minutes' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'student_id' => null, 'counselor_id' => 3, 'class_id' => 1, 'session_type' => 'Klasikal', 'session_date' => '2026-05-29', 'session_time' => '08:00:00', 'location' => 'Kelas X-IPA-1', 'topic' => 'Etika pergaulan digital', 'problem_description' => 'Materi pencegahan perundungan digital.', 'status' => 'Dijadwalkan', 'is_confidential' => 0, 'duration_minutes' => 60, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('session_participants', [
            ['id' => 1, 'session_id' => 1, 'student_id' => 1, 'attendance_status' => 'Hadir', 'participation_note' => 'Aktif berdiskusi.', 'is_active' => 1, 'joined_at' => '2026-05-20 09:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'session_id' => 2, 'student_id' => 2, 'attendance_status' => 'Belum Hadir', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'session_id' => 3, 'student_id' => 1, 'attendance_status' => 'Belum Hadir', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'session_id' => 3, 'student_id' => 2, 'attendance_status' => 'Belum Hadir', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('session_notes', [
            ['id' => 1, 'session_id' => 1, 'created_by' => 3, 'note_type' => 'Observasi', 'note_content' => 'Siswa menunjukkan kesediaan memperbaiki rutinitas pagi.', 'is_important' => 1, 'is_confidential' => 1, 'attachments' => 'uploads/counseling/demo-catatan.txt', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedCommunication(string $now): void
    {
        $this->insertRows('notifications', [
            ['id' => 1, 'user_id' => 7, 'title' => 'Asesmen Baru', 'message' => 'Asesmen Minat Karier sudah tersedia.', 'type' => 'assessment', 'link' => '/student/assessments', 'data' => json_encode(['assessment_id' => 1]), 'is_read' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'user_id' => 10, 'title' => 'Notifikasi Pelanggaran', 'message' => 'Ada pembaruan kasus pelanggaran Ahmad.', 'type' => 'violation', 'link' => '/parent/violations/1', 'data' => json_encode(['violation_id' => 1]), 'is_read' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('messages', [
            ['id' => 1, 'subject' => 'Koordinasi tindak lanjut siswa', 'body' => 'Mohon wali kelas membantu memantau kehadiran Ahmad pekan ini.', 'created_by' => 3, 'is_draft' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'subject' => 'Pertanyaan jadwal konseling', 'body' => 'Apakah sesi konseling Putri dapat dimajukan?', 'created_by' => 11, 'is_draft' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('message_participants', [
            ['id' => 1, 'message_id' => 1, 'user_id' => 3, 'role' => 'sender', 'is_read' => 1, 'read_at' => $now, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'message_id' => 1, 'user_id' => 5, 'role' => 'recipient', 'is_read' => 0, 'starred' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'message_id' => 2, 'user_id' => 11, 'role' => 'sender', 'is_read' => 1, 'read_at' => $now, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'message_id' => 2, 'user_id' => 4, 'role' => 'recipient', 'is_read' => 0, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedAssessments(string $now): void
    {
        $this->insertRows('assessments', [
            ['id' => 1, 'title' => 'Asesmen Minat Karier X', 'description' => 'Memetakan minat awal siswa kelas X.', 'assessment_type' => 'Minat Bakat', 'evaluation_mode' => 'survey', 'target_audience' => 'Class', 'target_class_id' => 1, 'created_by' => 3, 'is_active' => 1, 'is_published' => 1, 'start_date' => '2026-05-01', 'end_date' => '2026-06-30', 'duration_minutes' => 30, 'passing_score' => null, 'use_passing_score' => 0, 'show_score_to_student' => 1, 'max_attempts' => 1, 'show_result_immediately' => 1, 'allow_review' => 1, 'instructions' => 'Jawab sesuai kondisi diri saat ini.', 'total_questions' => 3, 'total_participants' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Kuis Kedisiplinan Siswa', 'description' => 'Kuis singkat terkait tata tertib sekolah.', 'assessment_type' => 'Psikologi', 'evaluation_mode' => 'pass_fail', 'target_audience' => 'All', 'created_by' => 4, 'is_active' => 1, 'is_published' => 1, 'start_date' => '2026-05-01', 'end_date' => '2026-06-30', 'duration_minutes' => 20, 'passing_score' => 70, 'use_passing_score' => 1, 'show_score_to_student' => 1, 'max_attempts' => 2, 'show_result_immediately' => 1, 'allow_review' => 1, 'instructions' => 'Pilih jawaban paling tepat.', 'total_questions' => 2, 'total_participants' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_questions', [
            ['id' => 1, 'assessment_id' => 1, 'question_text' => 'Bidang kegiatan apa yang paling kamu sukai?', 'question_type' => 'Multiple Choice', 'options' => json_encode(['Teknologi', 'Kesehatan', 'Bisnis', 'Pendidikan']), 'points' => 0, 'order_number' => 1, 'is_required' => 1, 'dimension' => 'minat', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 1, 'question_text' => 'Saya senang menyelesaikan masalah dengan logika.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 2, 'is_required' => 1, 'dimension' => 'analitis', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 1, 'question_text' => 'Tuliskan cita-cita atau rencana studi yang kamu pikirkan.', 'question_type' => 'Essay', 'points' => 0, 'order_number' => 3, 'is_required' => 0, 'dimension' => 'narasi', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assessment_id' => 2, 'question_text' => 'Apa yang perlu dilakukan jika terlambat datang ke sekolah?', 'question_type' => 'Multiple Choice', 'options' => json_encode(['Langsung masuk kelas', 'Lapor ke petugas piket', 'Pulang ke rumah', 'Menunggu di kantin']), 'correct_answer' => 'Lapor ke petugas piket', 'points' => 50, 'order_number' => 1, 'is_required' => 1, 'explanation' => 'Siswa wajib melapor agar keterlambatan tercatat.', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assessment_id' => 2, 'question_text' => 'Menggunakan gawai saat pembelajaran tanpa izin diperbolehkan.', 'question_type' => 'True/False', 'options' => json_encode(['True', 'False']), 'correct_answer' => 'False', 'points' => 50, 'order_number' => 2, 'is_required' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_assignees', [
            ['id' => 1, 'assessment_id' => 1, 'student_id' => 1, 'assigned_by' => 3, 'assigned_at' => '2026-05-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 1, 'student_id' => 2, 'assigned_by' => 3, 'assigned_at' => '2026-05-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 2, 'student_id' => 1, 'assigned_by' => 4, 'assigned_at' => '2026-05-02 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assessment_id' => 2, 'student_id' => 2, 'assigned_by' => 4, 'assigned_at' => '2026-05-02 08:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_results', [
            ['id' => 1, 'assessment_id' => 1, 'student_id' => 1, 'attempt_number' => 1, 'status' => 'Completed', 'questions_answered' => 3, 'total_questions' => 3, 'started_at' => '2026-05-12 08:10:00', 'completed_at' => '2026-05-12 08:28:00', 'time_spent_seconds' => 1080, 'interpretation' => 'Minat dominan pada bidang teknologi dan analitis.', 'dimension_scores' => json_encode(['Teknologi' => 80, 'Analitis' => 75]), 'recommendations' => 'Eksplorasi informatika, data, dan robotika.', 'reviewed_by' => 3, 'reviewed_at' => '2026-05-12 10:00:00', 'counselor_notes' => 'Cocok diberi informasi jurusan terkait teknologi.', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 2, 'student_id' => 2, 'attempt_number' => 1, 'status' => 'Graded', 'total_score' => 100, 'max_score' => 100, 'percentage' => 100, 'is_passed' => 1, 'questions_answered' => 2, 'total_questions' => 2, 'correct_answers' => 2, 'started_at' => '2026-05-13 09:00:00', 'completed_at' => '2026-05-13 09:08:00', 'graded_at' => '2026-05-13 09:08:10', 'time_spent_seconds' => 480, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 2, 'student_id' => 1, 'attempt_number' => 1, 'status' => 'Assigned', 'total_questions' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_answers', [
            ['id' => 1, 'question_id' => 1, 'student_id' => 1, 'result_id' => 1, 'answer_option' => 'Teknologi', 'answered_at' => '2026-05-12 08:12:00', 'time_spent_seconds' => 120, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'question_id' => 2, 'student_id' => 1, 'result_id' => 1, 'answer_option' => '5', 'answered_at' => '2026-05-12 08:15:00', 'time_spent_seconds' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'question_id' => 3, 'student_id' => 1, 'result_id' => 1, 'answer_text' => 'Saya tertarik belajar pemrograman dan membuat aplikasi.', 'answered_at' => '2026-05-12 08:27:00', 'time_spent_seconds' => 600, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'question_id' => 4, 'student_id' => 2, 'result_id' => 2, 'answer_option' => 'Lapor ke petugas piket', 'score' => 50, 'is_correct' => 1, 'is_auto_graded' => 1, 'answered_at' => '2026-05-13 09:03:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'question_id' => 5, 'student_id' => 2, 'result_id' => 2, 'answer_option' => 'False', 'score' => 50, 'is_correct' => 1, 'is_auto_graded' => 1, 'answered_at' => '2026-05-13 09:07:00', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedCareerAndUniversity(string $now): void
    {
        $this->insertRows('career_options', [
            ['id' => 1, 'title' => 'Pengembang Perangkat Lunak', 'sector' => 'Teknologi Informasi', 'min_education' => 'D3/S1', 'description' => 'Membangun aplikasi web, mobile, dan sistem informasi.', 'required_skills' => json_encode(['Logika', 'Pemrograman', 'Kolaborasi']), 'pathways' => 'Belajar dasar pemrograman, membuat portofolio, mengikuti magang.', 'avg_salary_idr' => 7500000, 'demand_level' => 9, 'external_links' => json_encode([['label' => 'Dicoding', 'url' => 'https://www.dicoding.com']]), 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Konselor Pendidikan', 'sector' => 'Pendidikan', 'min_education' => 'S1', 'description' => 'Membantu siswa memahami potensi dan mengambil keputusan pendidikan.', 'required_skills' => json_encode(['Empati', 'Komunikasi', 'Observasi']), 'pathways' => 'S1 BK/Psikologi, sertifikasi konseling, praktik lapangan.', 'avg_salary_idr' => 5000000, 'demand_level' => 7, 'is_public' => 1, 'is_active' => 1, 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'title' => 'Perawat', 'sector' => 'Kesehatan', 'min_education' => 'D3', 'description' => 'Memberikan layanan keperawatan di fasilitas kesehatan.', 'required_skills' => json_encode(['Ketelitian', 'Empati', 'Manajemen waktu']), 'pathways' => 'D3/S1 Keperawatan, uji kompetensi, praktik klinis.', 'avg_salary_idr' => 5200000, 'demand_level' => 8, 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('university_info', [
            ['id' => 1, 'university_name' => 'Institut Teknologi Bandung', 'alias' => 'ITB', 'accreditation' => 'Unggul', 'location' => 'Bandung', 'website' => 'https://www.itb.ac.id', 'logo' => null, 'description' => 'Perguruan tinggi negeri berfokus pada sains, teknologi, seni, dan desain.', 'faculties' => json_encode(['STEI', 'FTI', 'FMIPA']), 'programs' => json_encode([['name' => 'Informatika', 'degree' => 'S1'], ['name' => 'Sistem dan Teknologi Informasi', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah', 'Beasiswa alumni']), 'contacts' => json_encode(['email' => 'humas@itb.ac.id']), 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'university_name' => 'Universitas Pendidikan Indonesia', 'alias' => 'UPI', 'accreditation' => 'Unggul', 'location' => 'Bandung', 'website' => 'https://www.upi.edu', 'description' => 'Perguruan tinggi negeri dengan kekuatan utama pada bidang pendidikan.', 'faculties' => json_encode(['FIP', 'FPIPS', 'FPMIPA']), 'programs' => json_encode([['name' => 'Bimbingan dan Konseling', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah']), 'is_public' => 1, 'is_active' => 1, 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'university_name' => 'Universitas Padjadjaran', 'alias' => 'UNPAD', 'accreditation' => 'Unggul', 'location' => 'Sumedang', 'website' => 'https://www.unpad.ac.id', 'description' => 'Perguruan tinggi negeri dengan pilihan program kesehatan, sosial, dan sains.', 'faculties' => json_encode(['FK', 'FIK', 'FMIPA']), 'programs' => json_encode([['name' => 'Keperawatan', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah', 'Beasiswa prestasi']), 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('student_saved_careers', [
            ['id' => 1, 'student_id' => 1, 'career_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'student_id' => 2, 'career_id' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('student_saved_universities', [
            ['id' => 1, 'student_id' => 1, 'university_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'student_id' => 2, 'university_id' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedSettings(string $now): void
    {
        $this->insertRows('settings', [
            ['id' => 1, 'group' => 'general', 'key' => 'app_name', 'value' => 'SIB-K', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'group' => 'general', 'key' => 'school_name', 'value' => 'MA Persis 31 Banjaran', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'group' => 'general', 'key' => 'contact_email', 'value' => 'admin@sibk.sch.id', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'group' => 'branding', 'key' => 'logo_path', 'value' => null, 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'group' => 'academic', 'key' => 'default_academic_year_id', 'value' => '2', 'type' => 'int', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'group' => 'notifications', 'key' => 'enable_internal', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'group' => 'points', 'key' => 'probation_threshold', 'value' => '50', 'type' => 'int', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedSimulationAccess(string $now): void
    {
        $this->insertRows('simulation_access_grants', [
            ['id' => 1, 'user_id' => 2, 'is_active' => 1, 'granted_by' => 1, 'granted_at' => $now, 'notes' => 'Demo akses prototipe untuk koordinator.', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'user_id' => 3, 'is_active' => 1, 'granted_by' => 1, 'granted_at' => $now, 'notes' => 'Demo akses prototipe untuk Guru BK.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
