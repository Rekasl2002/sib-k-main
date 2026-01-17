<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "\n";
        echo "========================================\n";
        echo "  SEEDING DATABASE - FASE 1\n";
        echo "  SIB-K (Sistem Informasi BK)\n";
        echo "========================================\n\n";

        // 1. Roles
        echo "[1/7] Seeding Roles...\n";
        $this->call('RoleSeeder');
        echo "\n";

        // 2. Permissions
        echo "[2/7] Seeding Permissions...\n";
        $this->call('PermissionSeeder');
        echo "\n";

        // 3. Role Permissions
        echo "[3/7] Seeding Role Permissions...\n";
        $this->call('RolePermissionSeeder');
        echo "\n";

        // 4. Users
        echo "[4/7] Seeding Users...\n";
        $this->call('UserSeeder');
        echo "\n";

        // 5. Academic Years
        echo "[5/7] Seeding Academic Years...\n";
        $this->call('AcademicYearSeeder');
        echo "\n";

        // 6. Classes
        echo "[6/7] Seeding Classes...\n";
        $this->call('ClassSeeder');
        echo "\n";

        // 7. Students
        echo "[7/7] Seeding Students...\n";
        $this->call('StudentSeeder');
        echo "\n";

        echo "========================================\n";
        echo "  ✓ DATABASE SEEDING COMPLETED!\n";
        echo "========================================\n\n";

        echo "Login Credentials:\n";
        echo "┌─────────────────┬──────────────┬─────────────────┐\n";
        echo "│ Role            │ Username     │ Password        │\n";
        echo "├─────────────────┼──────────────┼─────────────────┤\n";
        echo "│ Admin           │ admin        │ admin123        │\n";
        echo "│ Koordinator BK  │ koordinator  │ koordinator123  │\n";
        echo "│ Guru BK         │ gurubk1      │ gurubk123       │\n";
        echo "│ Wali Kelas      │ walikelas1   │ walikelas123    │\n";
        echo "│ Siswa           │ siswa001     │ siswa123        │\n";
        echo "│ Orang Tua       │ parent001    │ parent123       │\n";
        echo "└─────────────────┴──────────────┴─────────────────┘\n\n";

        echo "Database Summary:\n";
        echo "• 6 Roles\n";
        echo "• 20 Permissions\n";
        echo "• 10 Users (1 Admin, 1 Koordinator, 2 Guru BK, 2 Wali Kelas, 2 Siswa, 2 Orang Tua)\n";
        echo "• 3 Academic Years (2024/2025 Active)\n";
        echo "• 10 Classes (X, XI, XII)\n";
        echo "• 2 Students\n\n";
    }
}
