<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CareerAndUniversitySeeder extends Seeder
{
    public function run()
    {
        // Seed career_options
        $this->db->table('career_options')->insertBatch([
            [
                'title'           => 'Pengembang Web',
                'sector'          => 'TI',
                'min_education'   => 'SMA/SMK',
                'description'     => 'Membangun situs & aplikasi web.',
                'required_skills' => json_encode(['HTML/CSS','JS','PHP','Git']),
                'pathways'        => 'Belajar dasar web → Framework → Proyek nyata → Portofolio',
                'avg_salary_idr'  => 6000000,
                'demand_level'    => 9,
                'external_links'  => json_encode([['label'=>'MDN Web Docs','url'=>'https://developer.mozilla.org']]),
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'title'           => 'Perawat',
                'sector'          => 'Kesehatan',
                'min_education'   => 'D3',
                'description'     => 'Merawat pasien di fasilitas kesehatan.',
                'required_skills' => json_encode(['Empati','Ketelitian','Komunikasi']),
                'pathways'        => 'D3 Keperawatan → Uji kompetensi → Praktik',
                'avg_salary_idr'  => 5000000,
                'demand_level'    => 8,
                'external_links'  => null,
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
        ]);

        // Seed university_info
        $this->db->table('university_info')->insertBatch([
            [
                'university_name' => 'Institut Teknologi Bandung',
                'alias'           => 'ITB',
                'accreditation'   => 'Unggul',
                'location'        => 'Bandung',
                'website'         => 'https://www.itb.ac.id',
                'programs'        => json_encode([['name'=>'Informatika','degree'=>'S1']]),
                'is_public'       => 1,
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'university_name' => 'Universitas Padjadjaran',
                'alias'           => 'UNPAD',
                'accreditation'   => 'Unggul',
                'location'        => 'Bandung',
                'website'         => 'https://www.unpad.ac.id',
                'programs'        => json_encode([['name'=>'Keperawatan','degree'=>'D3']]),
                'is_public'       => 1,
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
