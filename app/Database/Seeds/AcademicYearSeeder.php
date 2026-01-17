<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id'         => 1,
                'year_name'  => '2023/2024',
                'start_date' => '2023-07-01',
                'end_date'   => '2024-06-30',
                'is_active'  => 0,
                'semester'   => 'Genap',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id'         => 2,
                'year_name'  => '2024/2025',
                'start_date' => '2024-07-01',
                'end_date'   => '2025-06-30',
                'is_active'  => 1,
                'semester'   => 'Ganjil',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id'         => 3,
                'year_name'  => '2025/2026',
                'start_date' => '2025-07-01',
                'end_date'   => '2026-06-30',
                'is_active'  => 0,
                'semester'   => 'Ganjil',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Truncate table first
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('academic_years')->emptyTable();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        // Insert batch data
        $this->db->table('academic_years')->insertBatch($data);

        echo "✓ Academic Years seeded successfully!\n";
        echo "  - 2023/2024 (Inactive)\n";
        echo "  - 2024/2025 (Active)\n";
        echo "  - 2025/2026 (Inactive)\n";
    }
}
