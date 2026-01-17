<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // Student 1 - Ahmad Fajar Nugraha
            [
                'id'                     => 1,
                'user_id'                => 7,
                'class_id'               => 1, // X-IPA-1
                'nisn'                   => '0123456789',
                'nis'                    => '2024001',
                'gender'                 => 'L',
                'birth_place'            => 'Bandung',
                'birth_date'             => '2008-05-15',
                'religion'               => 'Islam',
                'address'                => 'Jl. Merdeka No. 123, Bandung',
                'parent_id'              => 9, // Suryanto
                'admission_date'         => '2024-07-01',
                'status'                 => 'Aktif',
                'total_violation_points' => 0,
                'created_at'             => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ],

            // Student 2 - Putri Amanda Sari
            [
                'id'                     => 2,
                'user_id'                => 8,
                'class_id'               => 1, // X-IPA-1
                'nisn'                   => '0123456790',
                'nis'                    => '2024002',
                'gender'                 => 'P',
                'birth_place'            => 'Jakarta',
                'birth_date'             => '2008-08-20',
                'religion'               => 'Islam',
                'address'                => 'Jl. Sudirman No. 45, Bandung',
                'parent_id'              => 10, // Dewi Lestari
                'admission_date'         => '2024-07-01',
                'status'                 => 'Aktif',
                'total_violation_points' => 0,
                'created_at'             => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ],
        ];

        // Truncate table first
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('students')->emptyTable();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        // Insert batch data
        $this->db->table('students')->insertBatch($data);

        echo "✓ Students seeded successfully!\n";
        echo "  - Ahmad Fajar Nugraha (X-IPA-1)\n";
        echo "  - Putri Amanda Sari (X-IPA-1)\n";
        echo "  Total: 2 students\n";
    }
}
