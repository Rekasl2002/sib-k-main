<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['id' => 1, 'permission_name' => 'manage_users', 'description' => 'Kelola pengguna sistem (CRUD users)', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 2, 'permission_name' => 'manage_roles', 'description' => 'Kelola peran dan izin akses', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 3, 'permission_name' => 'manage_academic_data', 'description' => 'Kelola data akademik (kelas, tahun ajaran)', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 4, 'permission_name' => 'manage_counseling_sessions', 'description' => 'Kelola sesi konseling (create, update, delete)', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 5, 'permission_name' => 'view_counseling_sessions', 'description' => 'Lihat sesi konseling', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 8, 'permission_name' => 'manage_assessments', 'description' => 'Kelola asesmen (AUM, ITP)', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 9, 'permission_name' => 'take_assessments', 'description' => 'Mengerjakan asesmen yang diberikan', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 10, 'permission_name' => 'view_student_portfolio', 'description' => 'Lihat portofolio digital siswa', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 11, 'permission_name' => 'generate_reports', 'description' => 'Generate laporan (PDF/Excel)', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 12, 'permission_name' => 'view_reports', 'description' => 'Lihat laporan', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 13, 'permission_name' => 'send_messages', 'description' => 'Kirim pesan internal', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 14, 'permission_name' => 'schedule_counseling', 'description' => 'Jadwalkan konseling', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 15, 'permission_name' => 'view_dashboard', 'description' => 'Akses dashboard sesuai role', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 16, 'permission_name' => 'manage_career_info', 'description' => 'Kelola fitur info karier dan info studi lanjut', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 17, 'permission_name' => 'view_career_info', 'description' => 'Lihat fitur info karier dan info studi lanjut', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 19, 'permission_name' => 'import_export_data', 'description' => 'Import/Export data via Excel', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 20, 'permission_name' => 'view_all_students', 'description' => 'Lihat semua data siswa', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 21, 'permission_name' => 'manage_settings', 'description' => 'Kelola pengaturan aplikasi', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 22, 'permission_name' => 'view_reports_aggregate', 'description' => 'Lihat laporan agregat', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 23, 'permission_name' => 'generate_reports_aggregate', 'description' => 'Unduh/generate laporan agregat', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 24, 'permission_name' => 'view_reports_individual', 'description' => 'Lihat laporan individual siswa', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 25, 'permission_name' => 'generate_reports_individual', 'description' => 'Unduh/generate laporan individual siswa', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 27, 'permission_name' => 'submit_violation_submissions', 'description' => 'Ajukan laporan/pengaduan pelanggaran', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 28, 'permission_name' => 'view_violation_submissions', 'description' => 'Lihat pengaduan pelanggaran', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 29, 'permission_name' => 'review_violation_submissions', 'description' => 'Tinjau pengaduan pelanggaran', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 30, 'permission_name' => 'manage_violation_submissions', 'description' => 'Kelola pengaduan pelanggaran', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 32, 'permission_name' => 'access_simulation_suite', 'description' => 'Akses halaman prototipe dan simulasi', 'created_at' => date('Y-m-d H:i:s')],
        ];

        // Truncate table first
        $this->db->table('permissions')->emptyTable();

        // Insert batch data
        $this->db->table('permissions')->insertBatch($data);

        echo "OK Permissions seeded successfully!\n";
    }
}
