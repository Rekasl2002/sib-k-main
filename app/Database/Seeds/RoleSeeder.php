<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id'          => 1,
                'role_name'   => 'Admin',
                'description' => 'Administrator sistem dengan akses penuh ke seluruh fitur aplikasi',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 2,
                'role_name'   => 'Koordinator BK',
                'description' => 'Koordinator Bimbingan Konseling - Mengelola guru BK dan mengawasi semua layanan BK',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 3,
                'role_name'   => 'Guru BK',
                'description' => 'Guru Bimbingan Konseling - Melakukan konseling dan layanan BK kepada siswa',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 4,
                'role_name'   => 'Wali Kelas',
                'description' => 'Wali Kelas - Mengelola kelas dan mencatat pelanggaran siswa',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 5,
                'role_name'   => 'Siswa',
                'description' => 'Siswa - Mengakses layanan konseling dan informasi karir',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 6,
                'role_name'   => 'Orang Tua',
                'description' => 'Orang Tua/Wali Siswa - Melihat perkembangan dan pelanggaran anak',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        // Delete existing data (can't use truncate due to foreign keys)
        $this->db->table('roles')->emptyTable();

        // Insert batch data
        $this->db->table('roles')->insertBatch($data);

        echo "✓ Roles seeded successfully!\n";
    }
}
