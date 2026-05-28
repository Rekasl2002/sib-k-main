<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'id' => 1,
                'user_id' => 7,
                'class_id' => 1,
                'nisn' => '1000000003',
                'nik' => '1000000000000003',
                'gender' => 'P',
                'birth_place' => 'Bandung',
                'birth_date' => '2007-09-19',
                'religion' => 'Islam',
                'address' => 'Kp. Contoh, Banjaran, Kabupaten Bandung, Jawa Barat 40377',
                'special_needs' => 'Tidak Ada',
                'disability' => 'Tidak Ada',
                'kip_pip_number' => 'PIP-2025-0001',
                'father_name' => 'Tatang Ruhiyat',
                'mother_name' => 'Neneng Sulastri',
                'guardian_name' => 'Tatang Ruhiyat',
                'parent_id' => 10,
                'admission_date' => '2025-07-14',
                'status' => 'Aktif',
                'total_violation_points' => 25,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'user_id' => 8,
                'class_id' => 1,
                'nisn' => '1000000004',
                'nik' => '1000000000000004',
                'gender' => 'L',
                'birth_place' => 'Bandung',
                'birth_date' => '2008-01-15',
                'religion' => 'Islam',
                'address' => 'Jl. Raya Banjaran No. 12, Kabupaten Bandung',
                'special_needs' => 'Tidak Ada',
                'disability' => 'Tidak Ada',
                'kip_pip_number' => null,
                'father_name' => 'Asep Hidayat',
                'mother_name' => 'Sri Mulyani',
                'guardian_name' => 'Asep Hidayat',
                'parent_id' => 11,
                'admission_date' => '2025-07-14',
                'status' => 'Aktif',
                'total_violation_points' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'user_id' => 9,
                'class_id' => 2,
                'nisn' => '1000000005',
                'nik' => '1000000000000005',
                'gender' => 'P',
                'birth_place' => 'Garut',
                'birth_date' => '2007-04-12',
                'religion' => 'Islam',
                'address' => 'Kp. Sukamaju, Banjaran, Kabupaten Bandung',
                'special_needs' => 'Tidak Ada',
                'disability' => 'Tidak Ada',
                'kip_pip_number' => 'KIP-2025-0003',
                'father_name' => 'Dedi Supriadi',
                'mother_name' => 'Iis Nurhayati',
                'guardian_name' => 'Iis Nurhayati',
                'parent_id' => 12,
                'admission_date' => '2025-07-14',
                'status' => 'Aktif',
                'total_violation_points' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('students')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        $this->db->table('students')->insertBatch($data);

        echo "Students seeded successfully.\n";
        echo "Total: 3 students\n";
    }
}
