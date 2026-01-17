<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // Kelas X
            [
                'id'                   => 1,
                'academic_year_id'     => 2, // 2024/2025
                'class_name'           => 'X-IPA-1',
                'grade_level'          => 'X',
                'major'                => 'IPA',
                'homeroom_teacher_id'  => 5, // Rina Wati
                'counselor_id'         => 3, // Siti Nurhaliza
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
            [
                'id'                   => 2,
                'academic_year_id'     => 2,
                'class_name'           => 'X-IPA-2',
                'grade_level'          => 'X',
                'major'                => 'IPA',
                'homeroom_teacher_id'  => 6, // Dedi Kusuma
                'counselor_id'         => 3, // Siti Nurhaliza
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
            [
                'id'                   => 3,
                'academic_year_id'     => 2,
                'class_name'           => 'X-IPS-1',
                'grade_level'          => 'X',
                'major'                => 'IPS',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 4, // Budi Santoso
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
            [
                'id'                   => 4,
                'academic_year_id'     => 2,
                'class_name'           => 'X-IPS-2',
                'grade_level'          => 'X',
                'major'                => 'IPS',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 4, // Budi Santoso
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],

            // Kelas XI
            [
                'id'                   => 5,
                'academic_year_id'     => 2,
                'class_name'           => 'XI-IPA-1',
                'grade_level'          => 'XI',
                'major'                => 'IPA',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 3, // Siti Nurhaliza
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
            [
                'id'                   => 6,
                'academic_year_id'     => 2,
                'class_name'           => 'XI-IPA-2',
                'grade_level'          => 'XI',
                'major'                => 'IPA',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 3, // Siti Nurhaliza
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
            [
                'id'                   => 7,
                'academic_year_id'     => 2,
                'class_name'           => 'XI-IPS-1',
                'grade_level'          => 'XI',
                'major'                => 'IPS',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 4, // Budi Santoso
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],

            // Kelas XII
            [
                'id'                   => 8,
                'academic_year_id'     => 2,
                'class_name'           => 'XII-IPA-1',
                'grade_level'          => 'XII',
                'major'                => 'IPA',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 3, // Siti Nurhaliza
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
            [
                'id'                   => 9,
                'academic_year_id'     => 2,
                'class_name'           => 'XII-IPA-2',
                'grade_level'          => 'XII',
                'major'                => 'IPA',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 4, // Budi Santoso
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
            [
                'id'                   => 10,
                'academic_year_id'     => 2,
                'class_name'           => 'XII-IPS-1',
                'grade_level'          => 'XII',
                'major'                => 'IPS',
                'homeroom_teacher_id'  => null,
                'counselor_id'         => 4, // Budi Santoso
                'max_students'         => 36,
                'is_active'            => 1,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ],
        ];

        // Truncate table first
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('classes')->emptyTable();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        // Insert batch data
        $this->db->table('classes')->insertBatch($data);

        echo "✓ Classes seeded successfully!\n";
        echo "  - 4 Kelas X (2 IPA, 2 IPS)\n";
        echo "  - 3 Kelas XI (2 IPA, 1 IPS)\n";
        echo "  - 3 Kelas XII (2 IPA, 1 IPS)\n";
        echo "  Total: 10 classes\n";
    }
}
