<?php

namespace App\Database\Seeds;

/**
 * File Path: app/Database/Seeds/InitialDataSeeder.php
 *
 * DATA AWAL WAJIB aplikasi SIB-K MA Persis 31 Banjaran (kondisi pertama pakai):
 * peran, izin akses, akun pengguna hasil wawancara, tahun ajaran aktif,
 * kelas 10 A s.d. 12 C, dua siswa nyata beserta akun orang tuanya, dan
 * pengaturan aplikasi. Seeder ini mengasumsikan tabel masih kosong
 * (dijalankan setelah `php spark migrate` pada database baru, atau lewat
 * DatabaseSeeder yang mengosongkan tabel terlebih dahulu).
 *
 * Akun siswa & orang tua sengaja MENGIKUTI KONVENSI FITUR IMPOR
 * (app/Libraries/ExcelImporter.php) supaya seragam dengan siswa hasil impor:
 * - username siswa = NISN; password = tanggal lahir 8 angka (DDMMYYYY);
 * - username orang tua = nama_orang_tua + 4 digit akhir NISN anak (huruf kecil,
 *   pemisah underscore); password sama dengan password anak.
 */
class InitialDataSeeder extends BaseDataSeeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $this->seedRoles($now);
        $permissionMap = $this->seedPermissions($now);
        $this->seedRolePermissions($permissionMap, $now);
        $this->seedUsers($now);
        $this->seedAcademicData($now);
        $this->seedSettings($now);

        echo "Data awal SIB-K selesai di-seed (peran, izin, akun, kelas, pengaturan).\n";
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
     * 27 izin akses final (hasil sinkronisasi 2026-07-05, tanpa izin
     * prototipe/simulasi yang dihapus saat persiapan deployment).
     *
     * @return array<string,int>
     */
    private function seedPermissions(string $now): array
    {
        $permissions = [
            'manage_users' => 'Kelola pengguna sistem',
            'manage_roles' => 'Kelola peran dan hak akses',
            'manage_academic_data' => 'Kelola tahun akademik, kelas, dan siswa',
            'manage_students' => 'Kelola data siswa dan akun orang tua sesuai lingkup peran',
            'view_counseling_sessions' => 'Lihat jadwal kegiatan BK sesuai lingkup peran',
            'manage_assessments' => 'Kelola asesmen',
            'take_assessments' => 'Mengerjakan asesmen',
            'view_student_portfolio' => 'Lihat portofolio siswa',
            'send_messages' => 'Kirim pesan internal',
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
            'manage_bk_services' => 'Kelola layanan BK',
            'view_bk_services' => 'Lihat layanan BK',
            'manage_consultation_complaints' => 'Kelola konsultasi dan pengaduan',
            'submit_consultation_complaints' => 'Ajukan konsultasi atau pengaduan',
            'review_consultation_complaints' => 'Tinjau konsultasi dan pengaduan',
            'manage_bk_assignments' => 'Kelola penugasan Guru BK',
            'view_bk_assignments' => 'Lihat penugasan Guru BK',
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
     * Pemetaan peran -> izin sesuai kondisi kanonis basis data 2026-07-06.
     *
     * @param array<string,int> $permissionMap
     */
    private function seedRolePermissions(array $permissionMap, string $now): void
    {
        $sets = [
            1 => array_keys($permissionMap),
            2 => [
                'manage_users', 'manage_academic_data', 'manage_students', 'manage_assessments',
                'send_messages', 'view_dashboard', 'manage_career_info', 'view_career_info',
                'import_export_data', 'view_all_students', 'view_reports_aggregate',
                'generate_reports_aggregate', 'view_reports_individual', 'generate_reports_individual',
                'manage_bk_services', 'view_bk_services', 'manage_consultation_complaints',
                'review_consultation_complaints', 'manage_bk_assignments', 'view_bk_assignments',
                'view_staff_info',
            ],
            3 => [
                'view_counseling_sessions', 'manage_assessments', 'send_messages', 'view_dashboard',
                'manage_career_info', 'view_career_info', 'view_all_students',
                'view_reports_individual', 'generate_reports_individual', 'manage_bk_services',
                'view_bk_services', 'manage_consultation_complaints', 'review_consultation_complaints',
                'view_bk_assignments', 'view_staff_info',
            ],
            4 => [
                'manage_students', 'view_counseling_sessions', 'send_messages', 'view_dashboard',
                'view_career_info', 'import_export_data', 'view_all_students',
                'view_reports_individual', 'generate_reports_individual', 'view_bk_services',
                'submit_consultation_complaints', 'view_staff_info',
            ],
            5 => [
                'view_counseling_sessions', 'take_assessments', 'view_student_portfolio',
                'send_messages', 'view_dashboard', 'view_career_info', 'view_bk_services',
                'submit_consultation_complaints', 'view_staff_info',
            ],
            6 => [
                'view_counseling_sessions', 'view_student_portfolio', 'send_messages',
                'view_dashboard', 'view_career_info', 'view_reports_individual',
                'generate_reports_individual', 'view_bk_services',
                'submit_consultation_complaints', 'view_staff_info',
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

    /**
     * Akun pengguna hasil wawancara. Data siswa & orang tua diambil dari
     * berkas resmi sekolah (Daftar Siswa 2025/2026 Ganjil kelas 10 & 11).
     */
    private function seedUsers(string $now): void
    {
        $rows = [
            // [id, role, username, email, password, nama lengkap, telepon]
            // Admin = akun sistem generik (bukan narasumber wawancara).
            [1, 1, 'admin_1', 'admin1@sibk.sch.id', 'admin123', 'Admin 1', '081100000001'],
            [2, 1, 'admin_2', 'admin2@sibk.sch.id', 'admin123', 'Admin 2', '081100000002'],
            // Koordinator BK: Koordinator BK 1 (narasumber) + satu koordinator cadangan generik.
            [3, 2, 'koordinator_1', 'koordinator1@sibk.sch.id', 'koordinator123', 'Koordinator BK 1', '081100000003'],
            [4, 2, 'koordinator_2', 'koordinator2@sibk.sch.id', 'koordinator123', 'Koordinator 2', '081100000004'],
            // Guru BK: Guru BK 1, Guru BK 2, dan Guru BK 3 (ketiganya narasumber Guru BK).
            [5, 3, 'gurubk_1', 'gurubk1@sibk.sch.id', 'gurubk123', 'Guru BK 1', '081100000005'],
            [6, 3, 'gurubk_2', 'gurubk2@sibk.sch.id', 'gurubk123', 'Guru BK 2', '081100000006'],
            // Guru BK 3: sesuai wawancara berperan Guru BK (bukan koordinator).
            // Ditaruh di id 12 agar id 7-11 (dipakai data contoh) tidak bergeser.
            [12, 3, 'gurubk_3', 'gurubk3@sibk.sch.id', 'gurubk123', 'Guru BK 3', '081100000012'],
            [7, 4, 'walikelas_1', 'walikelas1@sibk.sch.id', 'walikelas123', "Wali Kelas 1", '081100000007'],
            // Siswa: username = NISN, password = tanggal lahir DDMMYYYY (konvensi impor).
            [8, 5, '1000000001', null, '01012010', 'Siswa 1', '081100000008'],
            [9, 5, '1000000002', null, '02022009', 'Siswa 2', '081100000009'],
            // Orang tua (ibu kandung): username = nama + 4 digit akhir NISN anak, password = password anak.
            [10, 6, 'ibu_siswa_1_0001', null, '01012010', 'Ibu Siswa 1', '081100000008'],
            [11, 6, 'ibu_siswa_2_0002', null, '02022009', 'Ibu Siswa 2', '081100000009'],
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
            ['id' => 1, 'year_name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => 1, 'semester' => 'Ganjil', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Kelas lengkap 10 A s.d. 12 C, format nama mengikuti file impor sekolah
        // ("Kelas <tingkat> - <rombel>"). Wali kelas hanya boleh membina SATU kelas:
        // Wali Kelas 1 -> Kelas 10 - C. Guru BK pembina: tingkat 10 = Guru BK 1,
        // tingkat 11 = Guru BK 2, tingkat 12 = Koordinator (Koordinator BK 1).
        $classes = [];
        $id = 1;
        $counselorByGrade = [10 => 5, 11 => 6, 12 => 3];

        foreach ([10, 11, 12] as $grade) {
            foreach (['A', 'B', 'C'] as $letter) {
                $classes[] = [
                    'id' => $id,
                    'academic_year_id' => 1,
                    'class_name' => sprintf('Kelas %d - %s', $grade, $letter),
                    'grade_level' => (string) $grade,
                    'major' => 'IPA',
                    'homeroom_teacher_id' => ($grade === 10 && $letter === 'C') ? 7 : null,
                    'counselor_id' => $counselorByGrade[$grade],
                    'max_students' => 36,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $id++;
            }
        }

        $this->insertRows('classes', $classes);

        $this->insertRows('students', [
            [
                'id' => 1, 'user_id' => 8, 'class_id' => 3, 'nisn' => '1000000001', 'nik' => '1000000000000001',
                'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2010-02-17', 'religion' => 'Islam',
                'address' => 'Jalan Contoh No. 1, RT/RW 001/002, Desa Contoh, Kecamatan Contoh, Kabupaten Contoh, 40000',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => null,
                'hobi' => 'Menulis dan kaligrafi', 'ekskul_organisasi' => 'PMR, Jurnalistik',
                'father_name' => 'Ayah Siswa 1', 'mother_name' => 'Ibu Siswa 1', 'guardian_name' => 'Wali Siswa 1',
                'parent_id' => 10, 'admission_date' => '2025-07-14', 'status' => 'Aktif',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 2, 'user_id' => 9, 'class_id' => 6, 'nisn' => '1000000002', 'nik' => '1000000000000002',
                'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2009-06-27', 'religion' => 'Islam',
                'address' => 'Jalan Contoh No. 2, RT/RW 003/004, Desa Contoh, Kecamatan Contoh, Kabupaten Contoh, 40000',
                'special_needs' => 'Tidak Ada', 'disability' => 'Tidak Ada', 'kip_pip_number' => null,
                'hobi' => 'Sepak bola dan membaca', 'ekskul_organisasi' => 'Pramuka, Rohis',
                'father_name' => 'Ayah Siswa 2', 'mother_name' => 'Ibu Siswa 2', 'guardian_name' => 'Rahmat',
                'parent_id' => 11, 'admission_date' => '2024-07-15', 'status' => 'Aktif',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    private function seedSettings(string $now): void
    {
        $this->insertRows('settings', [
            ['id' => 1, 'group' => 'general', 'key' => 'app_name', 'value' => 'SIB-K', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'group' => 'general', 'key' => 'school_name', 'value' => 'MA Persis 31 Banjaran', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'group' => 'general', 'key' => 'contact_email', 'value' => 'admin@sibk.sch.id', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'group' => 'privacy', 'key' => 'counseling_notes_visibility', 'value' => 'internal_bk_only', 'type' => 'string', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'group' => 'notifications', 'key' => 'enable_internal', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'group' => 'consultation', 'key' => 'enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'group' => 'consultation', 'key' => 'homeroom_enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'group' => 'consultation', 'key' => 'student_enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'group' => 'consultation', 'key' => 'parent_enabled', 'value' => '1', 'type' => 'bool', 'autoload' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
