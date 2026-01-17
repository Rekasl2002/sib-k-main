<?php

/**
 * File Path: app/Database/Seeds/AdminSeeder.php
 * 
 * Admin Seeder
 * Membuat user admin default untuk sistem
 * 
 * @package    SIB-K
 * @subpackage Database/Seeds
 * @category   Seeder
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Get Admin role ID
        $adminRole = $this->db->table('roles')
            ->where('role_name', 'Admin')
            ->get()
            ->getRowArray();

        if (!$adminRole) {
            echo "\033[0;31m✗ Role 'Admin' tidak ditemukan. Jalankan RoleSeeder terlebih dahulu!\033[0m\n";
            return;
        }

        // Check if admin user already exists
        $existingAdmin = $this->db->table('users')
            ->where('username', 'admin')
            ->get()
            ->getRowArray();

        if ($existingAdmin) {
            echo "\033[0;33m⚠ User admin sudah ada, skip seeding.\033[0m\n";
            return;
        }

        // Create admin user
        $adminData = [
            'role_id'       => $adminRole['id'],
            'username'      => 'admin',
            'email'         => 'admin@sibk.sch.id',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT), // Default password
            'full_name'     => 'Administrator',
            'phone'         => '081234567890',
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($adminData);

        // Get Koordinator BK role ID
        $koordinatorRole = $this->db->table('roles')
            ->where('role_name', 'Koordinator BK')
            ->get()
            ->getRowArray();

        // Create default Koordinator BK user
        if ($koordinatorRole) {
            $existingKoordinator = $this->db->table('users')
                ->where('username', 'koordinator')
                ->get()
                ->getRowArray();

            if (!$existingKoordinator) {
                $koordinatorData = [
                    'role_id'       => $koordinatorRole['id'],
                    'username'      => 'koordinator',
                    'email'         => 'koordinator@sibk.sch.id',
                    'password_hash' => password_hash('koordinator123', PASSWORD_DEFAULT),
                    'full_name'     => 'Koordinator BK',
                    'phone'         => '081234567891',
                    'is_active'     => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ];

                $this->db->table('users')->insert($koordinatorData);
            }
        }

        // Get Guru BK role ID
        $guruBkRole = $this->db->table('roles')
            ->where('role_name', 'Guru BK')
            ->get()
            ->getRowArray();

        // Create default Guru BK user
        if ($guruBkRole) {
            $existingGuruBk = $this->db->table('users')
                ->where('username', 'gurubk')
                ->get()
                ->getRowArray();

            if (!$existingGuruBk) {
                $guruBkData = [
                    'role_id'       => $guruBkRole['id'],
                    'username'      => 'gurubk',
                    'email'         => 'gurubk@sibk.sch.id',
                    'password_hash' => password_hash('gurubk123', PASSWORD_DEFAULT),
                    'full_name'     => 'Guru BK Demo',
                    'phone'         => '081234567892',
                    'is_active'     => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ];

                $this->db->table('users')->insert($guruBkData);
            }
        }

        // Get Wali Kelas role ID
        $waliKelasRole = $this->db->table('roles')
            ->where('role_name', 'Wali Kelas')
            ->get()
            ->getRowArray();

        // Create default Wali Kelas user
        if ($waliKelasRole) {
            $existingWaliKelas = $this->db->table('users')
                ->where('username', 'walikelas')
                ->get()
                ->getRowArray();

            if (!$existingWaliKelas) {
                $waliKelasData = [
                    'role_id'       => $waliKelasRole['id'],
                    'username'      => 'walikelas',
                    'email'         => 'walikelas@sibk.sch.id',
                    'password_hash' => password_hash('walikelas123', PASSWORD_DEFAULT),
                    'full_name'     => 'Wali Kelas Demo',
                    'phone'         => '081234567893',
                    'is_active'     => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ];

                $this->db->table('users')->insert($waliKelasData);
            }
        }

        echo "\033[0;32m✓ Default users seeded successfully!\033[0m\n";
        echo "\033[1;36m\n===========================================\033[0m\n";
        echo "\033[1;37mDEFAULT LOGIN CREDENTIALS:\033[0m\n";
        echo "\033[1;36m===========================================\033[0m\n";
        echo "\033[1;33mAdmin:\033[0m\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
        echo "\033[1;33m\nKoordinator BK:\033[0m\n";
        echo "  Username: koordinator\n";
        echo "  Password: koordinator123\n";
        echo "\033[1;33m\nGuru BK:\033[0m\n";
        echo "  Username: gurubk\n";
        echo "  Password: gurubk123\n";
        echo "\033[1;33m\nWali Kelas:\033[0m\n";
        echo "  Username: walikelas\n";
        echo "  Password: walikelas123\n";
        echo "\033[1;36m===========================================\033[0m\n\n";
        echo "\033[0;31m⚠ PENTING: Ubah password default setelah login!\033[0m\n\n";
    }
}
