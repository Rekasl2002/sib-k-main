<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Tables with project data are reset for a clean demo seed. Technical
     * auth-helper tables such as password reset tables are intentionally left.
     *
     * @var list<string>
     */
    private array $truncateOrder = [
        'simulation_access_grants',
        'bk_assignment_status_histories',
        'session_notes',
        'session_participants',
        'case_conferences',
        'home_visits',
        'parent_collaborations',
        'guidances',
        'counseling_sessions',
        'bk_service_records',
        'consultation_complaint_attachments',
        'consultation_complaints',
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

    /** @var array<string,list<string>> */
    private array $fieldCache = [];

    public function run()
    {
        $now = '2026-06-09 09:00:00';

        $this->resetTables();

        $this->seedRoles($now);
        $permissionMap = $this->seedPermissions($now);
        $this->seedRolePermissions($permissionMap, $now);
        $this->seedUsers($now);
        $this->seedAcademicData($now);
        $this->seedBkAssignments($now);
        $this->seedConsultationComplaints($now);
        $this->seedBkServices($now);
        $this->seedCommunication($now);
        $this->seedAssessments($now);
        $this->seedCareerAndUniversity($now);
        $this->seedSettings($now);
        $this->seedSimulationAccess($now);
        $this->call(DemoSchoolDataSeeder::class);

        // Geser semua tanggal jadwal/agenda agar berpusat di sekitar hari ini (~1 bulan).
        $this->shiftScheduleDatesToTodayWindow();

        echo "\nSIB-K development demo database seeded successfully.\n";
        echo "Akun demo:\n";
        echo "- admin / admin123\n";
        echo "- koordinator / koordinator123\n";
        echo "- gurubk_1 / gurubk123\n";
        echo "- gurubk_2 / gurubk123\n";
        echo "- walikelas_1 / walikelas123\n";
        echo "- siswa_2 / siswa123\n";
        echo "- siswa_1 / siswa123\n";
        echo "- ortu_2 / parent123\n\n";
    }

    /**
     * Menggeser tanggal jadwal/agenda demo agar berpusat di sekitar hari ini,
     * sehingga halaman "Jadwal akan datang" selalu terisi data ~1 bulan.
     * Anchor data demo = 2026-06-09 (tanggal acuan pembuatan seed).
     */
    private function shiftScheduleDatesToTodayWindow(): void
    {
        $anchor = strtotime('2026-06-09');
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
        ];

        foreach ($scheduleColumns as $table => $columns) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            $fields = $this->tableFields($table);
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
     * @param list<array<string,mixed>> $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        if (! $this->db->tableExists($table)) {
            return;
        }

        $fields = array_flip($this->tableFields($table));

        foreach ($rows as $row) {
            $filtered = array_intersect_key($row, $fields);
            if ($filtered !== []) {
                $this->db->table($table)->insert($filtered);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function tableFields(string $table): array
    {
        if (! isset($this->fieldCache[$table])) {
            $this->fieldCache[$table] = $this->db->getFieldNames($table);
        }

        return $this->fieldCache[$table];
    }

    private function updateRow(string $table, array $data, array $where): void
    {
        if ($this->db->tableExists($table)) {
            $this->db->table($table)->where($where)->update(array_intersect_key($data, array_flip($this->tableFields($table))));
        }
    }

    private function seedRoles(string $now): void
    {
        $this->insertRows('roles', [
            ['id' => 1, 'role_name' => 'Admin', 'description' => 'Administrator sistem dengan akses penuh', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'role_name' => 'Koordinator BK', 'description' => 'Koordinator layanan bimbingan dan konseling', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'role_name' => 'Guru BK', 'description' => 'Guru pelaksana layanan bimbingan dan konseling', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'role_name' => 'Wali Kelas', 'description' => 'Wali kelas sebagai pendamping akademik siswa', 'created_at' => $now, 'updated_at' => $now],
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
            'manage_roles' => 'Kelola peran dan hak akses',
            'manage_academic_data' => 'Kelola tahun akademik, kelas, dan siswa',
            'manage_students' => 'Kelola data siswa dan akun orang tua sesuai lingkup peran',
            'manage_counseling_sessions' => 'Kelola sesi konseling lama yang masih dipakai aplikasi',
            'view_counseling_sessions' => 'Lihat sesi konseling lama yang masih dipakai aplikasi',
            'manage_assessments' => 'Kelola asesmen',
            'take_assessments' => 'Mengerjakan asesmen',
            'view_student_portfolio' => 'Lihat portofolio siswa',
            'generate_reports' => 'Unduh laporan umum',
            'view_reports' => 'Lihat laporan umum',
            'send_messages' => 'Kirim pesan internal',
            'schedule_counseling' => 'Ajukan atau jadwalkan konseling',
            'view_dashboard' => 'Akses dashboard sesuai peran',
            'manage_career_info' => 'Kelola info karier dan studi lanjut',
            'view_career_info' => 'Lihat info karier dan studi lanjut',
            'import_export_data' => 'Import dan export data',
            'view_all_students' => 'Lihat semua data siswa sesuai lingkup peran',
            'manage_settings' => 'Kelola pengaturan aplikasi',
            'view_reports_aggregate' => 'Lihat laporan agregat',
            'generate_reports_aggregate' => 'Unduh laporan agregat',
            'view_reports_individual' => 'Lihat laporan individual siswa',
            'generate_reports_individual' => 'Unduh laporan individual siswa',
            'manage_bk_services' => 'Kelola layanan BK baru',
            'view_bk_services' => 'Lihat layanan BK baru',
            'manage_consultation_complaints' => 'Kelola konsultasi dan pengaduan',
            'submit_consultation_complaints' => 'Ajukan konsultasi atau pengaduan',
            'review_consultation_complaints' => 'Tinjau konsultasi dan pengaduan',
            'manage_bk_assignments' => 'Kelola penugasan Guru BK',
            'view_bk_assignments' => 'Lihat penugasan Guru BK',
            'view_bk_reports' => 'Lihat laporan layanan BK',
            'generate_bk_reports' => 'Unduh laporan layanan BK',
            'access_simulation_suite' => 'Akses halaman prototipe dan simulasi',
            'view_staff_info' => 'Lihat info kontak Guru BK dan Wali Kelas',
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
        $sets = [
            1 => array_keys($permissionMap),
            2 => [
                'view_dashboard', 'view_all_students', 'manage_students', 'manage_users',
                'manage_academic_data', 'import_export_data', 'send_messages',
                'manage_assessments', 'manage_career_info', 'view_career_info',
                'manage_bk_services', 'view_bk_services', 'manage_consultation_complaints',
                'review_consultation_complaints', 'manage_bk_assignments', 'view_bk_assignments',
                'view_bk_reports', 'generate_bk_reports', 'view_reports_aggregate',
                'generate_reports_aggregate', 'view_reports_individual', 'generate_reports_individual',
                'access_simulation_suite',
            ],
            3 => [
                'view_dashboard', 'view_all_students', 'send_messages', 'manage_assessments',
                'manage_career_info', 'view_career_info', 'manage_bk_services', 'view_bk_services',
                'manage_consultation_complaints', 'review_consultation_complaints',
                'view_bk_assignments', 'view_bk_reports', 'generate_bk_reports',
                'view_reports_individual', 'generate_reports_individual', 'access_simulation_suite',
                'manage_counseling_sessions', 'view_counseling_sessions',
            ],
            4 => [
                // Wali Kelas dapat membantu impor siswa/orang tua, tetapi prosesnya dibatasi ke kelas binaannya.
                'view_dashboard', 'view_all_students', 'manage_students', 'send_messages', 'submit_consultation_complaints',
                'view_bk_services', 'view_bk_reports', 'view_reports_individual',
                'generate_reports_individual', 'view_career_info', 'access_simulation_suite',
                'import_export_data', 'view_counseling_sessions',
            ],
            5 => [
                'view_dashboard', 'send_messages', 'submit_consultation_complaints', 'take_assessments',
                'schedule_counseling', 'view_bk_services', 'view_career_info', 'view_student_portfolio',
                'access_simulation_suite', 'view_counseling_sessions',
            ],
            6 => [
                'view_dashboard', 'send_messages', 'submit_consultation_complaints', 'view_bk_services',
                'view_career_info', 'view_bk_reports', 'view_reports_individual',
                'generate_reports_individual', 'view_student_portfolio', 'access_simulation_suite',
                'view_counseling_sessions',
            ],
        ];

        // Info Guru/Staf untuk semua peran non-Admin (Admin sudah punya semua permission).
        foreach ([2, 3, 4, 5, 6] as $roleIdForStaff) {
            $sets[$roleIdForStaff][] = 'view_staff_info';
        }

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
            [2, 2, 'koordinator', 'koordinator.bk@sibk.sch.id', 'koordinator123', 'Koordinator BK 1', '081100000002'],
            [3, 3, 'gurubk_1', 'gurubk1@sibk.sch.id', 'gurubk123', 'Guru BK 1', '081100000003'],
            [4, 3, 'gurubk_2', 'gurubk2@sibk.sch.id', 'gurubk123', 'Guru BK 2', '081100000004'],
            [5, 3, 'gurubk_3', 'gurubk3@sibk.sch.id', 'gurubk123', 'Guru BK 3', '081100000005'],
            [6, 4, 'walikelas_1', 'walikelas1@sibk.sch.id', 'walikelas123', "Wali Kelas 1", '081100000006'],
            [7, 4, 'walikelas_demo', 'wali.demo@sibk.sch.id', 'walikelas123', 'Wali Kelas Demo', '081100000007'],
            [8, 5, 'siswa_2', null, 'siswa123', 'Siswa 2', '081100000008'],
            [9, 5, 'siswa_1', null, 'siswa123', 'Siswa 1', '081100000009'],
            [10, 5, 'siswa_demo', null, 'siswa123', 'Siswa Demo Fathiyah', '081100000010'],
            [11, 6, 'ortu_2', null, 'parent123', 'Orang Tua Siswa 2', '081100000011'],
            [12, 6, 'ortu_1', null, 'parent123', 'Ibu Siswa 1', '081100000012'],
            [13, 6, 'parent_demo', null, 'parent123', 'Orang Tua Siswa Demo', '081100000013'],
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
            ['id' => 1, 'year_name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => 1, 'semester' => 'Genap', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'year_name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => 0, 'semester' => 'Ganjil', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('classes', [
            ['id' => 1, 'academic_year_id' => 1, 'class_name' => 'X IPA C', 'grade_level' => '10', 'major' => 'IPA', 'homeroom_teacher_id' => 6, 'counselor_id' => 3, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'academic_year_id' => 1, 'class_name' => 'XI C', 'grade_level' => '11', 'major' => 'Umum', 'homeroom_teacher_id' => 6, 'counselor_id' => 4, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'academic_year_id' => 1, 'class_name' => 'XII C', 'grade_level' => '12', 'major' => 'Umum', 'homeroom_teacher_id' => 7, 'counselor_id' => 5, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'academic_year_id' => 1, 'class_name' => 'X B', 'grade_level' => '10', 'major' => 'Umum', 'homeroom_teacher_id' => 7, 'counselor_id' => 3, 'max_students' => 36, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('students', [
            [
                'id' => 1, 'user_id' => 8, 'class_id' => 2, 'nisn' => '1000000003', 'nik' => '1000000000000003',
                'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2007-09-19', 'religion' => 'Islam',
                'address' => 'Kp. Banjaran, Kabupaten Bandung',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => null,
                'hobi' => 'Sepak bola dan membaca', 'ekskul_organisasi' => 'Pramuka, Rohis',
                'father_name' => 'Ayah Siswa 2', 'mother_name' => 'Ibu Siswa 2', 'guardian_name' => 'Orang Tua Siswa 2',
                'parent_id' => 11, 'admission_date' => '2025-07-14', 'status' => 'Aktif',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 2, 'user_id' => 9, 'class_id' => 1, 'nisn' => '1000000004', 'nik' => '1000000000000004',
                'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2008-01-15', 'religion' => 'Islam',
                'address' => 'Jl. Raya Banjaran No. 12, Kabupaten Bandung',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => 'KIP-2025-0002',
                'hobi' => 'Menulis dan kaligrafi', 'ekskul_organisasi' => 'PMR, Jurnalistik',
                'father_name' => 'Ayah Siswa 1', 'mother_name' => 'Ibu Siswa 1', 'guardian_name' => 'Ibu Siswa 1',
                'parent_id' => 12, 'admission_date' => '2025-07-14', 'status' => 'Aktif',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 3, 'user_id' => 10, 'class_id' => 3, 'nisn' => '1000000005', 'nik' => '1000000000000005',
                'gender' => 'P', 'birth_place' => 'Garut', 'birth_date' => '2007-04-12', 'religion' => 'Islam',
                'address' => 'Kp. Sukamaju, Kabupaten Bandung',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => 'KIP-2025-0003',
                'father_name' => 'Ayah Siswa Demo', 'mother_name' => 'Ibu Siswa Demo', 'guardian_name' => 'Orang Tua Siswa Demo',
                'parent_id' => 13, 'admission_date' => '2025-07-14', 'status' => 'Aktif',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    private function seedBkAssignments(string $now): void
    {
        $this->insertRows('bk_assignments', [
            ['id' => 1, 'assignment_type' => 'Kelas Binaan', 'title' => 'Binaan kelas X IPA C', 'instruction' => 'Guru BK 1 menjadi Guru BK pembina kelas X IPA C semester berjalan.', 'assigned_by' => 2, 'assigned_to_user_id' => 3, 'class_id' => 1, 'priority' => 'Sedang', 'status' => 'Ditugaskan', 'due_at' => '2026-06-30 15:00:00', 'assigned_at' => '2026-06-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assignment_type' => 'Kelas Binaan', 'title' => 'Binaan kelas XI C', 'instruction' => 'Guru BK 2 mendampingi kelas XI C dan mencatat layanan yang berjalan.', 'assigned_by' => 2, 'assigned_to_user_id' => 4, 'class_id' => 2, 'priority' => 'Sedang', 'status' => 'Berjalan', 'due_at' => '2026-06-30 15:00:00', 'assigned_at' => '2026-06-01 08:15:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assignment_type' => 'Tugas Layanan', 'title' => 'Siapkan bimbingan klasikal etika digital', 'instruction' => 'Susun materi singkat, jadwal kelas, dan catatan tindak lanjut siswa yang perlu diperhatikan.', 'assigned_by' => 2, 'assigned_to_user_id' => 3, 'class_id' => 1, 'priority' => 'Tinggi', 'status' => 'Dibaca', 'due_at' => '2026-06-12 07:30:00', 'assigned_at' => '2026-06-04 09:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assignment_type' => 'Tindak Lanjut', 'title' => 'Rencanakan kunjungan rumah Siswa 1', 'instruction' => 'Koordinasikan jadwal dengan orang tua dan catat alamat kunjungan pada layanan kunjungan rumah.', 'assigned_by' => 2, 'assigned_to_user_id' => 3, 'student_id' => 2, 'source_type' => 'consultation_complaints', 'source_id' => 2, 'priority' => 'Tinggi', 'status' => 'Berjalan', 'due_at' => '2026-06-18 14:00:00', 'assigned_at' => '2026-06-06 10:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assignment_type' => 'Koordinasi', 'title' => 'Konferensi kasus adaptasi belajar Siswa 2', 'instruction' => 'Undang Guru BK, wali kelas, orang tua, dan siswa bila diperlukan. Catatan lengkap hanya untuk internal BK.', 'assigned_by' => 2, 'assigned_to_user_id' => 4, 'student_id' => 1, 'source_type' => 'bk_service_records', 'source_id' => 2, 'priority' => 'Mendesak', 'status' => 'Ditugaskan', 'due_at' => '2026-06-20 10:00:00', 'assigned_at' => '2026-06-08 13:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('bk_assignment_status_histories', [
            ['id' => 1, 'assignment_id' => 1, 'status' => 'Ditugaskan', 'note' => 'Koordinator menetapkan kelas binaan.', 'changed_by' => 2, 'changed_at' => '2026-06-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assignment_id' => 2, 'status' => 'Ditugaskan', 'note' => 'Koordinator menetapkan kelas binaan.', 'changed_by' => 2, 'changed_at' => '2026-06-01 08:15:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assignment_id' => 2, 'status' => 'Berjalan', 'note' => 'Guru BK mulai memetakan kebutuhan kelas.', 'changed_by' => 4, 'changed_at' => '2026-06-03 09:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assignment_id' => 3, 'status' => 'Dibaca', 'note' => 'Materi sedang disiapkan.', 'changed_by' => 3, 'changed_at' => '2026-06-05 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assignment_id' => 4, 'status' => 'Berjalan', 'note' => 'Menunggu konfirmasi jadwal orang tua.', 'changed_by' => 3, 'changed_at' => '2026-06-08 11:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'assignment_id' => 5, 'status' => 'Ditugaskan', 'note' => 'Konferensi kasus masuk prioritas koordinasi.', 'changed_by' => 2, 'changed_at' => '2026-06-08 13:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedConsultationComplaints(string $now): void
    {
        $this->insertRows('consultation_complaints', [
            ['id' => 1, 'reporter_type' => 'student', 'reporter_user_id' => 8, 'subject_student_id' => 1, 'request_type' => 'Konsultasi', 'category' => 'Belajar', 'title' => 'Konsultasi kesulitan fokus belajar', 'description' => 'Siswa ingin berbicara dengan Guru BK karena merasa sulit fokus saat jam pertama.', 'occurred_at' => '2026-06-03 07:30:00', 'location' => 'Kelas XI C', 'priority' => 'Sedang', 'status' => 'Dijadwalkan', 'privacy_level' => 'Rahasia BK', 'visible_to_homeroom' => 0, 'assigned_to_user_id' => 4, 'handled_by' => 4, 'handled_at' => '2026-06-04 09:00:00', 'created_at' => '2026-06-03 08:05:00', 'updated_at' => $now],
            ['id' => 2, 'reporter_type' => 'parent', 'reporter_user_id' => 12, 'subject_student_id' => 2, 'request_type' => 'Laporan Orang Tua', 'category' => 'Kehadiran', 'title' => 'Orang tua meminta koordinasi perkembangan anak', 'description' => 'Orang tua menyampaikan anak terlihat kurang bersemangat dan meminta pendampingan sekolah.', 'occurred_at' => '2026-06-04 20:00:00', 'location' => 'Rumah siswa', 'priority' => 'Tinggi', 'status' => 'Diterima', 'privacy_level' => 'Rahasia BK', 'visible_to_homeroom' => 0, 'assigned_to_user_id' => 3, 'handled_by' => 3, 'handled_at' => '2026-06-05 08:30:00', 'created_at' => '2026-06-04 20:25:00', 'updated_at' => $now],
            ['id' => 3, 'reporter_type' => 'homeroom', 'reporter_user_id' => 6, 'subject_student_id' => 3, 'request_type' => 'Permintaan Konseling', 'category' => 'Motivasi belajar', 'title' => 'Wali kelas meminta tindak lanjut siswa demo', 'description' => 'Wali kelas melihat perubahan motivasi belajar dan meminta Guru BK melakukan asesmen awal.', 'occurred_at' => '2026-06-06 10:00:00', 'location' => 'Ruang kelas XII C', 'priority' => 'Sedang', 'status' => 'Ditinjau', 'privacy_level' => 'Dapat Dilihat Wali Kelas', 'visible_to_homeroom' => 1, 'assigned_to_user_id' => 5, 'created_at' => '2026-06-06 11:00:00', 'updated_at' => $now],
            ['id' => 4, 'reporter_type' => 'student', 'reporter_user_id' => 9, 'subject_student_id' => 2, 'request_type' => 'Pengaduan', 'category' => 'Relasi teman', 'title' => 'Siswa meminta bantuan menyelesaikan konflik teman', 'description' => 'Siswa mengisi pengaduan karena merasa perlu mediasi dengan teman sekelas.', 'occurred_at' => '2026-06-08 12:30:00', 'location' => 'Koridor kelas X IPA C', 'witness' => 'Teman sekelas', 'priority' => 'Sedang', 'status' => 'Diajukan', 'privacy_level' => 'Rahasia BK', 'visible_to_homeroom' => 0, 'assigned_to_user_id' => 3, 'created_at' => '2026-06-08 13:10:00', 'updated_at' => $now],
        ]);

        $this->insertRows('consultation_complaint_attachments', [
            ['id' => 1, 'complaint_id' => 2, 'file_path' => 'uploads/demo/laporan-orang-tua-khayra.pdf', 'file_type' => 'application/pdf', 'uploaded_by' => 12, 'created_at' => '2026-06-04 20:25:00', 'updated_at' => $now],
        ]);

    }

    private function seedBkServices(string $now): void
    {
        $this->insertRows('bk_service_records', [
            ['id' => 1, 'service_type' => 'Bimbingan', 'title' => 'Bimbingan klasikal etika media sosial', 'target_class_id' => 1, 'counselor_id' => 3, 'assignment_id' => 3, 'scheduled_at' => '2026-06-12 08:00:00', 'location' => 'Kelas X IPA C', 'status' => 'Dijadwalkan', 'duration_minutes' => 60, 'privacy_level' => 'Umum Terbatas', 'created_by' => 3, 'created_at' => '2026-06-05 09:00:00', 'updated_at' => $now],
            ['id' => 2, 'service_type' => 'Konseling', 'title' => 'Konseling individu Siswa 2', 'target_student_id' => 1, 'target_class_id' => 2, 'counselor_id' => 4, 'assignment_id' => 2, 'source_complaint_id' => 1, 'scheduled_at' => '2026-06-13 09:00:00', 'location' => 'Ruang BK 1', 'status' => 'Dijadwalkan', 'duration_minutes' => 45, 'privacy_level' => 'Rahasia BK', 'created_by' => 4, 'created_at' => '2026-06-04 10:00:00', 'updated_at' => $now],
            ['id' => 3, 'service_type' => 'Kolaborasi Orang Tua', 'title' => 'Kolaborasi orang tua Siswa 1', 'target_student_id' => 2, 'target_class_id' => 1, 'counselor_id' => 3, 'source_complaint_id' => 2, 'held_at' => '2026-06-06 10:00:00', 'location' => 'Ruang BK 2', 'status' => 'Selesai', 'duration_minutes' => 60, 'privacy_level' => 'Rahasia BK', 'created_by' => 3, 'created_at' => '2026-06-05 09:00:00', 'updated_at' => $now],
            ['id' => 4, 'service_type' => 'Kunjungan Rumah', 'title' => 'Kunjungan rumah Siswa 1', 'target_student_id' => 2, 'target_class_id' => 1, 'counselor_id' => 3, 'assignment_id' => 4, 'source_complaint_id' => 2, 'scheduled_at' => '2026-06-18 14:00:00', 'location' => 'Rumah siswa', 'status' => 'Dijadwalkan', 'duration_minutes' => 90, 'privacy_level' => 'Rahasia BK', 'created_by' => 3, 'created_at' => '2026-06-08 11:00:00', 'updated_at' => $now],
            ['id' => 5, 'service_type' => 'Konferensi Kasus', 'title' => 'Konferensi kasus adaptasi belajar Siswa 2', 'target_student_id' => 1, 'target_class_id' => 2, 'counselor_id' => 2, 'assignment_id' => 5, 'source_complaint_id' => 1, 'scheduled_at' => '2026-06-20 10:00:00', 'location' => 'Ruang Rapat BK', 'status' => 'Dijadwalkan', 'duration_minutes' => 90, 'privacy_level' => 'Rahasia Tinggi', 'created_by' => 2, 'created_at' => '2026-06-08 13:00:00', 'updated_at' => $now],
            ['id' => 6, 'service_type' => 'Konseling', 'title' => 'Konseling kelompok manajemen waktu', 'target_class_id' => 3, 'counselor_id' => 5, 'held_at' => '2026-06-03 09:00:00', 'location' => 'Ruang BK 3', 'status' => 'Selesai', 'duration_minutes' => 50, 'privacy_level' => 'Ringkasan Terbatas', 'created_by' => 5, 'created_at' => '2026-06-02 08:00:00', 'updated_at' => $now],
        ]);

        $this->updateRow('consultation_complaints', ['converted_service_record_id' => 2], ['id' => 1]);
        $this->updateRow('consultation_complaints', ['converted_service_record_id' => 3], ['id' => 2]);

        $this->insertRows('guidances', [
            ['id' => 1, 'bk_service_record_id' => 1, 'guidance_type' => 'Klasikal', 'material_topic' => 'Etika media sosial dan relasi teman', 'summary' => 'Materi bimbingan kelas untuk pencegahan konflik dan perundungan digital.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('counseling_sessions', [
            ['id' => 1, 'bk_service_record_id' => 2, 'student_id' => 1, 'counselor_id' => 4, 'class_id' => 2, 'session_type' => 'Individu', 'session_date' => '2026-06-13', 'session_time' => '09:00:00', 'location' => 'Ruang BK 1', 'topic' => 'Kesulitan fokus belajar', 'problem_description' => 'Siswa meminta ruang konsultasi karena sulit fokus pada jam pertama.', 'status' => 'Dijadwalkan', 'is_confidential' => 1, 'duration_minutes' => 45, 'counseling_type' => 'Individu', 'privacy_level' => 'Rahasia BK', 'follow_up_status' => 'Belum', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'bk_service_record_id' => 6, 'student_id' => null, 'counselor_id' => 5, 'class_id' => 3, 'session_type' => 'Kelompok', 'session_date' => '2026-06-03', 'session_time' => '09:00:00', 'location' => 'Ruang BK 3', 'topic' => 'Manajemen waktu belajar', 'problem_description' => 'Beberapa siswa kelas XII C membutuhkan strategi belajar menjelang ujian.', 'session_summary' => 'Siswa menyusun jadwal mingguan dan target belajar.', 'follow_up_plan' => 'Guru BK memantau komitmen belajar selama dua pekan.', 'status' => 'Selesai', 'is_confidential' => 1, 'duration_minutes' => 50, 'counseling_type' => 'Kelompok', 'privacy_level' => 'Ringkasan Terbatas', 'follow_up_status' => 'Berjalan', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('parent_collaborations', [
            ['id' => 1, 'bk_service_record_id' => 3, 'parent_name' => 'Ibu Siswa 1', 'topic' => 'Koordinasi motivasi belajar dan kehadiran', 'summary' => 'Orang tua menyampaikan perubahan kebiasaan belajar di rumah. Guru BK menjelaskan rencana pendampingan sekolah.', 'follow_up' => 'Guru BK mengirim ringkasan jadwal pendampingan kepada orang tua dan wali kelas hanya pada bagian umum.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('home_visits', [
            ['id' => 1, 'bk_service_record_id' => 4, 'address_snapshot' => 'Jl. Raya Banjaran No. 12, Kabupaten Bandung', 'problem_topic' => 'Pendampingan motivasi belajar di rumah', 'visit_result' => 'Masih dijadwalkan. Guru BK menunggu konfirmasi akhir dari orang tua.', 'follow_up' => 'Siapkan lembar observasi singkat dan dokumentasi hasil kunjungan.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('case_conferences', [
            ['id' => 1, 'bk_service_record_id' => 5, 'chronology' => 'Siswa beberapa kali menyampaikan kesulitan fokus dan membutuhkan dukungan lintas pihak.', 'discussion_summary' => 'Konferensi akan mempertemukan Koordinator BK, Guru BK, wali kelas, dan orang tua untuk menentukan dukungan yang proporsional.', 'decision_summary' => 'Keputusan belum ditetapkan karena konferensi masih dijadwalkan.', 'follow_up_plan' => 'Kumpulkan ringkasan observasi wali kelas dan catatan konseling awal.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('session_participants', [
            ['id' => 1, 'bk_service_record_id' => 1, 'session_id' => null, 'student_id' => null, 'participant_type' => 'class', 'participant_class_id' => 1, 'manual_name' => 'Kelas X IPA C', 'role_in_session' => 'Peserta bimbingan', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Diundang', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'bk_service_record_id' => 2, 'session_id' => 1, 'student_id' => 1, 'participant_type' => 'student', 'participant_student_id' => 1, 'role_in_session' => 'Siswa konseling', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'bk_service_record_id' => 2, 'session_id' => 1, 'student_id' => null, 'participant_type' => 'user', 'participant_user_id' => 4, 'manual_name' => 'Guru BK 2', 'role_in_session' => 'Guru BK', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'bk_service_record_id' => 3, 'session_id' => null, 'student_id' => 2, 'participant_type' => 'student', 'participant_student_id' => 2, 'manual_name' => 'Siswa 1', 'role_in_session' => 'Siswa terkait', 'attendance_status' => 'Hadir', 'joined_at' => '2026-06-06 10:00:00', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'bk_service_record_id' => 3, 'session_id' => null, 'student_id' => null, 'participant_type' => 'parent', 'participant_parent_id' => 12, 'manual_name' => 'Orang Tua Siswa 1', 'role_in_session' => 'Orang tua', 'attendance_status' => 'Hadir', 'joined_at' => '2026-06-06 10:00:00', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'bk_service_record_id' => 4, 'session_id' => null, 'student_id' => null, 'participant_type' => 'parent', 'participant_parent_id' => 12, 'manual_name' => 'Orang Tua Siswa 1', 'role_in_session' => 'Tuan rumah', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Diundang', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'bk_service_record_id' => 5, 'session_id' => null, 'student_id' => 1, 'participant_type' => 'student', 'participant_student_id' => 1, 'manual_name' => 'Siswa 2 Jahrama', 'role_in_session' => 'Siswa terkait', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Diundang', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'bk_service_record_id' => 5, 'session_id' => null, 'student_id' => null, 'participant_type' => 'user', 'participant_user_id' => 2, 'manual_name' => 'Ustadz Koordinator BK 1', 'role_in_session' => 'Koordinator BK', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'bk_service_record_id' => 6, 'session_id' => 2, 'student_id' => 3, 'participant_type' => 'student', 'participant_student_id' => 3, 'manual_name' => 'Siswa Demo Fathiyah', 'role_in_session' => 'Peserta konseling kelompok', 'attendance_status' => 'Hadir', 'joined_at' => '2026-06-03 09:00:00', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('session_notes', [
            ['id' => 1, 'bk_service_record_id' => 1, 'session_id' => null, 'created_by' => 3, 'note_type' => 'Rencana', 'note_content' => 'Bimbingan klasikal menekankan etika komunikasi, jejak digital, dan cara meminta bantuan BK.', 'is_important' => 0, 'is_confidential' => 0, 'visibility_level' => 'Publik Terbatas', 'follow_up_status' => 'Belum', 'assigned_to_user_id' => 3, 'due_date' => '2026-06-12', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'bk_service_record_id' => 2, 'session_id' => 1, 'created_by' => 4, 'note_type' => 'Persiapan', 'note_content' => 'Sesi konseling perlu menjaga kerahasiaan isi. Wali kelas hanya menerima ringkasan tindak lanjut umum bila diperlukan.', 'is_important' => 1, 'is_confidential' => 1, 'visibility_level' => 'Internal BK', 'follow_up_status' => 'Belum', 'assigned_to_user_id' => 4, 'due_date' => '2026-06-13', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'bk_service_record_id' => 3, 'session_id' => null, 'created_by' => 3, 'note_type' => 'Hasil', 'note_content' => 'Orang tua setuju berkoordinasi mingguan. Ringkasan untuk wali kelas hanya berisi kebutuhan pemantauan umum.', 'is_important' => 1, 'is_confidential' => 1, 'visibility_level' => 'Koordinator dan Guru BK', 'follow_up_status' => 'Berjalan', 'assigned_to_user_id' => 3, 'due_date' => '2026-06-16', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'bk_service_record_id' => 5, 'session_id' => null, 'created_by' => 2, 'note_type' => 'Agenda', 'note_content' => 'Konferensi kasus membahas kebutuhan dukungan siswa tanpa membuka catatan konseling rinci ke pihak yang tidak berwenang.', 'is_important' => 1, 'is_confidential' => 1, 'visibility_level' => 'Internal BK', 'follow_up_status' => 'Belum', 'assigned_to_user_id' => 4, 'due_date' => '2026-06-20', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedCommunication(string $now): void
    {
        $this->insertRows('notifications', [
            ['id' => 1, 'user_id' => 3, 'title' => 'Tugas baru dari Koordinator BK', 'message' => 'Siapkan bimbingan klasikal etika digital untuk kelas X IPA C.', 'type' => 'assignment', 'link' => '/prototype/assignments', 'data' => json_encode(['assignment_id' => 3]), 'is_read' => 0, 'created_at' => '2026-06-05 09:05:00', 'updated_at' => $now],
            ['id' => 2, 'user_id' => 4, 'title' => 'Konseling Siswa 2 dijadwalkan', 'message' => 'Sesi konseling dijadwalkan pada 13 Juni 2026 pukul 09.00.', 'type' => 'bk_service', 'link' => '/prototype/counseling', 'data' => json_encode(['service_id' => 2]), 'is_read' => 0, 'created_at' => '2026-06-04 10:05:00', 'updated_at' => $now],
            ['id' => 3, 'user_id' => 8, 'title' => 'Permintaan konsultasi diterima', 'message' => 'Guru BK telah menjadwalkan konsultasi kamu.', 'type' => 'consultation', 'link' => '/prototype/consultation', 'data' => json_encode(['complaint_id' => 1]), 'is_read' => 0, 'created_at' => '2026-06-04 10:10:00', 'updated_at' => $now],
            ['id' => 4, 'user_id' => 12, 'title' => 'Kolaborasi orang tua selesai dicatat', 'message' => 'Ringkasan tindak lanjut sudah dicatat Guru BK.', 'type' => 'parent_collaboration', 'link' => '/prototype/parent-collaboration', 'data' => json_encode(['service_id' => 3]), 'is_read' => 1, 'read_at' => '2026-06-06 13:00:00', 'created_at' => '2026-06-06 12:30:00', 'updated_at' => $now],
            ['id' => 5, 'user_id' => 2, 'title' => 'Konferensi kasus menunggu peserta', 'message' => 'Peserta konferensi kasus Siswa 2 belum semuanya mengonfirmasi.', 'type' => 'case_conference', 'link' => '/prototype/case-conferences', 'data' => json_encode(['service_id' => 5]), 'is_read' => 0, 'created_at' => '2026-06-08 14:00:00', 'updated_at' => $now],
        ]);

        $this->insertRows('messages', [
            ['id' => 1, 'subject' => 'Koordinasi bimbingan klasikal X IPA C', 'body' => 'Mohon konfirmasi jam bimbingan klasikal pada Jumat, 12 Juni 2026.', 'created_by' => 3, 'is_draft' => 0, 'created_at' => '2026-06-05 10:00:00', 'updated_at' => $now],
            ['id' => 2, 'subject' => 'Permohonan jadwal konsultasi lanjutan', 'body' => 'Kami ingin memastikan jadwal pendampingan anak setelah pertemuan orang tua.', 'created_by' => 12, 'is_draft' => 0, 'created_at' => '2026-06-06 14:00:00', 'updated_at' => $now],
            ['id' => 3, 'subject' => 'Undangan konferensi kasus', 'body' => 'Konferensi kasus adaptasi belajar Siswa 2 dijadwalkan pada 20 Juni 2026.', 'created_by' => 2, 'is_draft' => 0, 'created_at' => '2026-06-08 14:10:00', 'updated_at' => $now],
            ['id' => 4, 'subject' => 'Pertanyaan pengisian asesmen', 'body' => 'Apakah asesmen minat karier bisa dikerjakan ulang bila jawaban kurang sesuai?', 'created_by' => 8, 'is_draft' => 0, 'created_at' => '2026-06-09 08:30:00', 'updated_at' => $now],
        ]);

        $this->insertRows('message_participants', [
            ['id' => 1, 'message_id' => 1, 'user_id' => 3, 'role' => 'sender', 'is_read' => 1, 'read_at' => '2026-06-05 10:00:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'message_id' => 1, 'user_id' => 6, 'role' => 'recipient', 'is_read' => 0, 'starred' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'message_id' => 2, 'user_id' => 12, 'role' => 'sender', 'is_read' => 1, 'read_at' => '2026-06-06 14:00:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'message_id' => 2, 'user_id' => 3, 'role' => 'recipient', 'is_read' => 0, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'message_id' => 3, 'user_id' => 2, 'role' => 'sender', 'is_read' => 1, 'read_at' => '2026-06-08 14:10:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'message_id' => 3, 'user_id' => 4, 'role' => 'recipient', 'is_read' => 0, 'starred' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'message_id' => 3, 'user_id' => 6, 'role' => 'recipient', 'is_read' => 0, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'message_id' => 4, 'user_id' => 8, 'role' => 'sender', 'is_read' => 1, 'read_at' => '2026-06-09 08:30:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'message_id' => 4, 'user_id' => 4, 'role' => 'recipient', 'is_read' => 0, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedAssessments(string $now): void
    {
        $this->insertRows('assessments', [
            ['id' => 1, 'title' => 'Asesmen Minat Karier dan Studi Lanjut', 'description' => 'Memetakan minat awal siswa terhadap bidang studi dan karier.', 'assessment_type' => 'Minat Bakat', 'evaluation_mode' => 'survey', 'target_audience' => 'Class', 'target_class_id' => 2, 'created_by' => 4, 'is_active' => 1, 'is_published' => 1, 'start_date' => '2026-06-01', 'end_date' => '2026-06-30', 'duration_minutes' => 30, 'passing_score' => null, 'use_passing_score' => 0, 'show_score_to_student' => 1, 'max_attempts' => 1, 'show_result_immediately' => 1, 'allow_review' => 1, 'instructions' => 'Jawab sesuai kondisi dan minat diri saat ini.', 'total_questions' => 4, 'total_participants' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Screening Kesejahteraan Siswa', 'description' => 'Screening singkat untuk melihat kebutuhan pendampingan dasar.', 'assessment_type' => 'Psikologi', 'evaluation_mode' => 'score_only', 'target_audience' => 'All', 'created_by' => 3, 'is_active' => 1, 'is_published' => 1, 'start_date' => '2026-06-05', 'end_date' => '2026-07-05', 'duration_minutes' => 20, 'passing_score' => null, 'use_passing_score' => 0, 'show_score_to_student' => 0, 'max_attempts' => 1, 'show_result_immediately' => 0, 'allow_review' => 1, 'instructions' => 'Pilih jawaban yang paling mendekati kondisi kamu.', 'total_questions' => 3, 'total_participants' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_questions', [
            ['id' => 1, 'assessment_id' => 1, 'question_text' => 'Bidang kegiatan apa yang paling kamu sukai?', 'question_type' => 'Multiple Choice', 'options' => json_encode(['Teknologi', 'Kesehatan', 'Pendidikan', 'Bisnis']), 'points' => 0, 'order_number' => 1, 'is_required' => 1, 'dimension' => 'minat', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 1, 'question_text' => 'Saya senang menyelesaikan masalah dengan logika.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 2, 'is_required' => 1, 'dimension' => 'analitis', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 1, 'question_text' => 'Saya nyaman berdiskusi tentang rencana masa depan dengan Guru BK.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 3, 'is_required' => 1, 'dimension' => 'kesiapan_konsultasi', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assessment_id' => 1, 'question_text' => 'Tuliskan pilihan studi lanjut atau karier yang sedang kamu pikirkan.', 'question_type' => 'Essay', 'points' => 0, 'order_number' => 4, 'is_required' => 0, 'dimension' => 'narasi', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assessment_id' => 2, 'question_text' => 'Saya merasa punya orang yang dapat saya hubungi saat ada masalah.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 1, 'is_required' => 1, 'dimension' => 'dukungan', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'assessment_id' => 2, 'question_text' => 'Saya merasa jadwal belajar saya teratur.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 2, 'is_required' => 1, 'dimension' => 'rutinitas', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'assessment_id' => 2, 'question_text' => 'Saya ingin dibantu Guru BK untuk menyusun rencana belajar.', 'question_type' => 'True/False', 'options' => json_encode(['True', 'False']), 'points' => 0, 'order_number' => 3, 'is_required' => 1, 'dimension' => 'kebutuhan_layanan', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_assignees', [
            ['id' => 1, 'assessment_id' => 1, 'student_id' => 1, 'assigned_by' => 4, 'assigned_at' => '2026-06-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 1, 'student_id' => 3, 'assigned_by' => 4, 'assigned_at' => '2026-06-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 2, 'student_id' => 1, 'assigned_by' => 3, 'assigned_at' => '2026-06-05 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assessment_id' => 2, 'student_id' => 2, 'assigned_by' => 3, 'assigned_at' => '2026-06-05 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assessment_id' => 2, 'student_id' => 3, 'assigned_by' => 3, 'assigned_at' => '2026-06-05 08:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_results', [
            ['id' => 1, 'assessment_id' => 1, 'student_id' => 1, 'attempt_number' => 1, 'status' => 'Completed', 'questions_answered' => 4, 'total_questions' => 4, 'started_at' => '2026-06-07 08:10:00', 'completed_at' => '2026-06-07 08:28:00', 'time_spent_seconds' => 1080, 'interpretation' => 'Minat dominan pada teknologi dan kegiatan analitis.', 'dimension_scores' => json_encode(['Teknologi' => 84, 'Analitis' => 78, 'Kesiapan Konsultasi' => 75]), 'recommendations' => 'Eksplorasi informatika, data, dan robotika. Diskusikan pilihan studi lanjut dengan Guru BK.', 'reviewed_by' => 4, 'reviewed_at' => '2026-06-07 10:00:00', 'counselor_notes' => 'Cocok diberi referensi program studi terkait teknologi.', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 1, 'student_id' => 3, 'attempt_number' => 1, 'status' => 'Assigned', 'questions_answered' => 0, 'total_questions' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 2, 'student_id' => 2, 'attempt_number' => 1, 'status' => 'In Progress', 'questions_answered' => 1, 'total_questions' => 3, 'started_at' => '2026-06-09 08:00:00', 'time_spent_seconds' => 300, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_answers', [
            ['id' => 1, 'question_id' => 1, 'student_id' => 1, 'result_id' => 1, 'answer_option' => 'Teknologi', 'answered_at' => '2026-06-07 08:12:00', 'time_spent_seconds' => 120, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'question_id' => 2, 'student_id' => 1, 'result_id' => 1, 'answer_option' => '5', 'answered_at' => '2026-06-07 08:15:00', 'time_spent_seconds' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'question_id' => 3, 'student_id' => 1, 'result_id' => 1, 'answer_option' => '4', 'answered_at' => '2026-06-07 08:20:00', 'time_spent_seconds' => 100, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'question_id' => 4, 'student_id' => 1, 'result_id' => 1, 'answer_text' => 'Saya tertarik belajar pemrograman dan membuat aplikasi.', 'answered_at' => '2026-06-07 08:27:00', 'time_spent_seconds' => 600, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'question_id' => 5, 'student_id' => 2, 'result_id' => 3, 'answer_option' => '3', 'answered_at' => '2026-06-09 08:05:00', 'time_spent_seconds' => 120, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedCareerAndUniversity(string $now): void
    {
        $this->insertRows('career_options', [
            ['id' => 1, 'title' => 'Pengembang Perangkat Lunak', 'sector' => 'Teknologi Informasi', 'min_education' => 'D3/S1', 'description' => 'Membangun aplikasi web, mobile, dan sistem informasi.', 'required_skills' => json_encode(['Logika', 'Pemrograman', 'Kolaborasi']), 'pathways' => 'Belajar dasar pemrograman, membuat portofolio, mengikuti magang.', 'avg_salary_idr' => 7500000, 'demand_level' => 9, 'external_links' => json_encode([['label' => 'Dicoding', 'url' => 'https://www.dicoding.com']]), 'is_public' => 1, 'is_active' => 1, 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Konselor Pendidikan', 'sector' => 'Pendidikan', 'min_education' => 'S1', 'description' => 'Membantu siswa memahami potensi dan mengambil keputusan pendidikan.', 'required_skills' => json_encode(['Empati', 'Komunikasi', 'Observasi']), 'pathways' => 'S1 BK/Psikologi, praktik lapangan, dan pengembangan kompetensi konseling.', 'avg_salary_idr' => 5000000, 'demand_level' => 7, 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'title' => 'Perawat', 'sector' => 'Kesehatan', 'min_education' => 'D3', 'description' => 'Memberikan layanan keperawatan di fasilitas kesehatan.', 'required_skills' => json_encode(['Ketelitian', 'Empati', 'Manajemen waktu']), 'pathways' => 'D3/S1 Keperawatan, uji kompetensi, praktik klinis.', 'avg_salary_idr' => 5200000, 'demand_level' => 8, 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'title' => 'Guru dan Tenaga Pendidikan', 'sector' => 'Pendidikan', 'min_education' => 'S1', 'description' => 'Mengajar, membimbing, dan mengembangkan kegiatan pendidikan.', 'required_skills' => json_encode(['Komunikasi', 'Manajemen kelas', 'Kesabaran']), 'pathways' => 'S1 Pendidikan, PPG, dan praktik mengajar.', 'avg_salary_idr' => 4800000, 'demand_level' => 6, 'is_public' => 1, 'is_active' => 1, 'created_by' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('university_info', [
            ['id' => 1, 'university_name' => 'Institut Teknologi Bandung', 'alias' => 'ITB', 'accreditation' => 'Unggul', 'location' => 'Bandung', 'website' => 'https://www.itb.ac.id', 'description' => 'Perguruan tinggi negeri berfokus pada sains, teknologi, seni, dan desain.', 'faculties' => json_encode(['STEI', 'FTI', 'FMIPA']), 'programs' => json_encode([['name' => 'Informatika', 'degree' => 'S1'], ['name' => 'Sistem dan Teknologi Informasi', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah', 'Beasiswa alumni']), 'contacts' => json_encode(['email' => 'humas@itb.ac.id']), 'is_public' => 1, 'is_active' => 1, 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'university_name' => 'Universitas Pendidikan Indonesia', 'alias' => 'UPI', 'accreditation' => 'Unggul', 'location' => 'Bandung', 'website' => 'https://www.upi.edu', 'description' => 'Perguruan tinggi negeri dengan kekuatan utama pada bidang pendidikan.', 'faculties' => json_encode(['FIP', 'FPIPS', 'FPMIPA']), 'programs' => json_encode([['name' => 'Bimbingan dan Konseling', 'degree' => 'S1'], ['name' => 'Pendidikan Guru', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah']), 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'university_name' => 'Universitas Padjadjaran', 'alias' => 'UNPAD', 'accreditation' => 'Unggul', 'location' => 'Sumedang', 'website' => 'https://www.unpad.ac.id', 'description' => 'Perguruan tinggi negeri dengan pilihan program kesehatan, sosial, dan sains.', 'faculties' => json_encode(['FK', 'FIK', 'FMIPA']), 'programs' => json_encode([['name' => 'Keperawatan', 'degree' => 'S1'], ['name' => 'Psikologi', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah', 'Beasiswa prestasi']), 'is_public' => 1, 'is_active' => 1, 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('student_saved_careers', [
            ['id' => 1, 'student_id' => 1, 'career_id' => 1, 'created_at' => '2026-06-07 10:00:00', 'updated_at' => $now],
            ['id' => 2, 'student_id' => 2, 'career_id' => 2, 'created_at' => '2026-06-08 10:00:00', 'updated_at' => $now],
            ['id' => 3, 'student_id' => 3, 'career_id' => 4, 'created_at' => '2026-06-09 08:00:00', 'updated_at' => $now],
        ]);

        $this->insertRows('student_saved_universities', [
            ['id' => 1, 'student_id' => 1, 'university_id' => 1, 'created_at' => '2026-06-07 10:05:00', 'updated_at' => $now],
            ['id' => 2, 'student_id' => 2, 'university_id' => 2, 'created_at' => '2026-06-08 10:05:00', 'updated_at' => $now],
            ['id' => 3, 'student_id' => 3, 'university_id' => 3, 'created_at' => '2026-06-09 08:05:00', 'updated_at' => $now],
        ]);
    }

    private function seedSettings(string $now): void
    {
        $this->insertRows('settings', [
            ['id' => 1, 'group' => 'general', 'key' => 'app_name', 'value' => 'SIB-K', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'group' => 'general', 'key' => 'school_name', 'value' => 'MA Persis 31 Banjaran', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'group' => 'general', 'key' => 'contact_email', 'value' => 'admin@sibk.sch.id', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'group' => 'privacy', 'key' => 'counseling_notes_visibility', 'value' => 'internal_bk_only', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'group' => 'prototype', 'key' => 'demo_date_range', 'value' => '2026-06-01 s.d. 2026-07-05', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'group' => 'notifications', 'key' => 'enable_internal', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'group' => 'consultation', 'key' => 'enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'group' => 'consultation', 'key' => 'homeroom_enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'group' => 'consultation', 'key' => 'student_enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'group' => 'consultation', 'key' => 'parent_enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedSimulationAccess(string $now): void
    {
        $rows = [];
        for ($userId = 1; $userId <= 13; $userId++) {
            $rows[] = [
                'id' => $userId,
                'user_id' => $userId,
                'is_active' => 1,
                'granted_by' => 1,
                'granted_at' => $now,
                'notes' => 'Akses demo prototipe pengembangan untuk validasi calon pengguna.',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertRows('simulation_access_grants', $rows);
    }
}
