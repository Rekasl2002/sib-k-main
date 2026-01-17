<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // Admin
            [
                'id'            => 1,
                'role_id'       => 1,
                'username'      => 'admin',
                'email'         => 'admin@sibk.sch.id',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'full_name'     => 'Administrator Sistem',
                'phone'         => '081234567890',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Koordinator BK
            [
                'id'            => 2,
                'role_id'       => 2,
                'username'      => 'koordinator',
                'email'         => 'koordinator.bk@sibk.sch.id',
                'password_hash' => password_hash('koordinator123', PASSWORD_BCRYPT),
                'full_name'     => 'Drs. Ahmad Supriyadi, M.Pd',
                'phone'         => '081234567891',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Guru BK 1
            [
                'id'            => 3,
                'role_id'       => 3,
                'username'      => 'gurubk1',
                'email'         => 'siti.nurhaliza@sibk.sch.id',
                'password_hash' => password_hash('gurubk123', PASSWORD_BCRYPT),
                'full_name'     => 'Siti Nurhaliza, S.Pd',
                'phone'         => '081234567892',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Guru BK 2
            [
                'id'            => 4,
                'role_id'       => 3,
                'username'      => 'gurubk2',
                'email'         => 'budi.santoso@sibk.sch.id',
                'password_hash' => password_hash('gurubk123', PASSWORD_BCRYPT),
                'full_name'     => 'Budi Santoso, S.Psi',
                'phone'         => '081234567893',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Wali Kelas 1
            [
                'id'            => 5,
                'role_id'       => 4,
                'username'      => 'walikelas1',
                'email'         => 'rina.wati@sibk.sch.id',
                'password_hash' => password_hash('walikelas123', PASSWORD_BCRYPT),
                'full_name'     => 'Rina Wati, S.Pd',
                'phone'         => '081234567894',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Wali Kelas 2
            [
                'id'            => 6,
                'role_id'       => 4,
                'username'      => 'walikelas2',
                'email'         => 'dedi.kusuma@sibk.sch.id',
                'password_hash' => password_hash('walikelas123', PASSWORD_BCRYPT),
                'full_name'     => 'Dedi Kusuma, S.Pd',
                'phone'         => '081234567895',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Siswa 1
            [
                'id'            => 7,
                'role_id'       => 5,
                'username'      => 'siswa001',
                'email'         => 'ahmad.fajar@student.sibk.sch.id',
                'password_hash' => password_hash('siswa123', PASSWORD_BCRYPT),
                'full_name'     => 'Ahmad Fajar Nugraha',
                'phone'         => '081234567896',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Siswa 2
            [
                'id'            => 8,
                'role_id'       => 5,
                'username'      => 'siswa002',
                'email'         => 'putri.amanda@student.sibk.sch.id',
                'password_hash' => password_hash('siswa123', PASSWORD_BCRYPT),
                'full_name'     => 'Putri Amanda Sari',
                'phone'         => '081234567897',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Orang Tua 1 (parent of siswa001)
            [
                'id'            => 9,
                'role_id'       => 6,
                'username'      => 'parent001',
                'email'         => 'suryanto@gmail.com',
                'password_hash' => password_hash('parent123', PASSWORD_BCRYPT),
                'full_name'     => 'Suryanto',
                'phone'         => '081234567898',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],

            // Orang Tua 2 (parent of siswa002)
            [
                'id'            => 10,
                'role_id'       => 6,
                'username'      => 'parent002',
                'email'         => 'dewi.lestari@gmail.com',
                'password_hash' => password_hash('parent123', PASSWORD_BCRYPT),
                'full_name'     => 'Dewi Lestari',
                'phone'         => '081234567899',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ];

        // Truncate table first (disable foreign key checks)
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('users')->emptyTable();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        // Insert batch data
        $this->db->table('users')->insertBatch($data);

        echo "✓ Users seeded successfully!\n";
        echo "  - 1 Admin\n";
        echo "  - 1 Koordinator BK\n";
        echo "  - 2 Guru BK\n";
        echo "  - 2 Wali Kelas\n";
        echo "  - 2 Siswa\n";
        echo "  - 2 Orang Tua\n";
        echo "\nDefault passwords:\n";
        echo "  - Admin: admin123\n";
        echo "  - Koordinator: koordinator123\n";
        echo "  - Guru BK: gurubk123\n";
        echo "  - Wali Kelas: walikelas123\n";
        echo "  - Siswa: siswa123\n";
        echo "  - Orang Tua: parent123\n";
    }
}
