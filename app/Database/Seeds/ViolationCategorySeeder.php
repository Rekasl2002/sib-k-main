<?php

/**
 * File Path: app/Database/Seeds/ViolationCategorySeeder.php
 * 
 * Violation Category Seeder
 * Seed data kategori pelanggaran siswa dengan tingkat keparahan berbeda
 * 
 * @package    SIB-K
 * @subpackage Seeders
 * @category   Database
 * @author     Development Team
 * @created    2025-01-06
 * 
 * Command: php spark db:seed ViolationCategorySeeder
 */

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ViolationCategorySeeder extends Seeder
{
    public function run()
    {
        $data = [
            // ============================================
            // KATEGORI PELANGGARAN RINGAN (5-10 POIN)
            // ============================================
            [
                'category_name'    => 'Keterlambatan',
                'severity_level'   => 'Ringan',
                'point_deduction'  => 5,
                'description'      => 'Terlambat masuk kelas atau datang ke sekolah tanpa alasan yang sah',
                'examples'         => 'Terlambat masuk kelas pagi, terlambat setelah istirahat, terlambat masuk setelah upacara',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Kelengkapan Seragam',
                'severity_level'   => 'Ringan',
                'point_deduction'  => 5,
                'description'      => 'Tidak mengenakan seragam sekolah dengan lengkap dan rapi sesuai ketentuan',
                'examples'         => 'Tidak memakai dasi, tidak memakai topi, atribut tidak lengkap, sepatu tidak sesuai ketentuan',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Kebersihan & Kerapian',
                'severity_level'   => 'Ringan',
                'point_deduction'  => 5,
                'description'      => 'Tidak menjaga kebersihan dan kerapian diri atau lingkungan sekolah',
                'examples'         => 'Rambut tidak rapi, kuku panjang, membuang sampah sembarangan, tidak piket kelas',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Tidak Mengerjakan Tugas',
                'severity_level'   => 'Ringan',
                'point_deduction'  => 5,
                'description'      => 'Tidak mengerjakan atau tidak mengumpulkan tugas yang diberikan guru',
                'examples'         => 'Tidak mengerjakan PR, tidak mengumpulkan tugas tepat waktu, tidak membawa buku pelajaran',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Gadget di Kelas',
                'severity_level'   => 'Ringan',
                'point_deduction'  => 10,
                'description'      => 'Menggunakan gadget (HP, tablet) saat pembelajaran tanpa izin guru',
                'examples'         => 'Main HP saat pelajaran, mendengar musik dengan earphone, bermain game di kelas',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],

            // ============================================
            // KATEGORI PELANGGARAN SEDANG (15-30 POIN)
            // ============================================
            [
                'category_name'    => 'Membolos',
                'severity_level'   => 'Sedang',
                'point_deduction'  => 20,
                'description'      => 'Tidak masuk sekolah atau meninggalkan sekolah tanpa izin yang sah',
                'examples'         => 'Alfa tanpa keterangan, keluar sekolah tanpa izin, tidak masuk kelas saat jam pelajaran',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Merokok',
                'severity_level'   => 'Sedang',
                'point_deduction'  => 25,
                'description'      => 'Merokok atau membawa rokok di lingkungan sekolah',
                'examples'         => 'Merokok di toilet, membawa rokok ke sekolah, merokok di sekitar sekolah saat jam pelajaran',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Berkelahi Ringan',
                'severity_level'   => 'Sedang',
                'point_deduction'  => 20,
                'description'      => 'Terlibat perkelahian ringan atau adu mulut yang mengganggu ketertiban',
                'examples'         => 'Adu mulut dengan teman, saling dorong, pertengkaran verbal yang keras',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Tidak Sopan',
                'severity_level'   => 'Sedang',
                'point_deduction'  => 15,
                'description'      => 'Berperilaku tidak sopan kepada guru, staff, atau teman',
                'examples'         => 'Berbicara kasar, membantah guru, tidak menghormati orang tua/guru',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Mencontek',
                'severity_level'   => 'Sedang',
                'point_deduction'  => 15,
                'description'      => 'Mencontek saat ulangan atau ujian',
                'examples'         => 'Menyontek saat ujian, membawa contekan, membantu teman menyontek',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Merusak Fasilitas',
                'severity_level'   => 'Sedang',
                'point_deduction'  => 25,
                'description'      => 'Merusak atau menghilangkan fasilitas dan property sekolah',
                'examples'         => 'Mencoret-coret meja/dinding, merusak kursi, menghilangkan buku perpustakaan',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],

            // ============================================
            // KATEGORI PELANGGARAN BERAT (50+ POIN)
            // ============================================
            [
                'category_name'    => 'Berkelahi Berat',
                'severity_level'   => 'Berat',
                'point_deduction'  => 50,
                'description'      => 'Terlibat perkelahian fisik yang menyebabkan cedera atau kerusakan',
                'examples'         => 'Berkelahi dengan pukulan, tawuran, membawa senjata tajam',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Bullying',
                'severity_level'   => 'Berat',
                'point_deduction'  => 50,
                'description'      => 'Melakukan intimidasi, penganiayaan, atau perundungan terhadap siswa lain',
                'examples'         => 'Intimidasi fisik/verbal, cyber bullying, memeras teman, mengucilkan teman',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Pencurian',
                'severity_level'   => 'Berat',
                'point_deduction'  => 75,
                'description'      => 'Mengambil barang milik orang lain tanpa izin',
                'examples'         => 'Mencuri barang teman, mengambil uang, mencuri fasilitas sekolah',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Narkoba',
                'severity_level'   => 'Berat',
                'point_deduction'  => 100,
                'description'      => 'Menggunakan, membawa, atau mengedarkan narkotika dan zat adiktif',
                'examples'         => 'Menggunakan narkoba, membawa narkoba, mengedarkan narkoba, mabuk-mabukan',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Pornografi',
                'severity_level'   => 'Berat',
                'point_deduction'  => 75,
                'description'      => 'Membawa, menyebarkan, atau mengakses konten pornografi',
                'examples'         => 'Membawa majalah/video porno, menyebarkan konten porno, mengakses situs porno',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Pemalsuan Dokumen',
                'severity_level'   => 'Berat',
                'point_deduction'  => 60,
                'description'      => 'Memalsukan tanda tangan, surat izin, atau dokumen sekolah lainnya',
                'examples'         => 'Memalsukan tanda tangan orangtua, memalsukan surat sakit, memalsukan nilai',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Perjudian',
                'severity_level'   => 'Berat',
                'point_deduction'  => 60,
                'description'      => 'Melakukan perjudian dalam bentuk apapun di lingkungan sekolah',
                'examples'         => 'Main kartu judi, taruhan uang, judi online',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'category_name'    => 'Meninggalkan Sekolah Berkali-kali',
                'severity_level'   => 'Berat',
                'point_deduction'  => 50,
                'description'      => 'Meninggalkan sekolah tanpa izin lebih dari 3 kali dalam sebulan',
                'examples'         => 'Bolos berulang kali, sering keluar sekolah tanpa izin, alfa berkali-kali',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
        ];

        // Insert data using Query Builder for better performance
        $this->db->table('violation_categories')->insertBatch($data);

        echo "\n✅ ViolationCategorySeeder completed successfully!\n";
        echo "   - Total categories inserted: " . count($data) . "\n";
        echo "   - Ringan (5-10 poin): 5 categories\n";
        echo "   - Sedang (15-30 poin): 6 categories\n";
        echo "   - Berat (50-100 poin): 8 categories\n\n";
    }
}
