<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
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
        $id = 1;
        foreach ($permissions as $name => $description) {
            $rows[] = [
                'id' => $id++,
                'permission_name' => $name,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('permissions')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        $this->db->table('permissions')->insertBatch($rows);

        echo "OK Permissions seeded successfully!\n";
    }
}
