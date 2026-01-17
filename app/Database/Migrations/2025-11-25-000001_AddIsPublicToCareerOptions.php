<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsPublicToCareerOptions extends Migration
{
    public function up()
    {
        // Kolom publikasi (default: private)
        $this->db->query("
            ALTER TABLE `career_options`
            ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 0
        ");

        // Index gabungan untuk query portal siswa
        $this->db->query("
            CREATE INDEX `idx_career_public_active`
            ON `career_options` (`is_public`, `is_active`)
        ");
    }

    public function down()
    {
        // Drop index + kolom
        $this->db->query("DROP INDEX `idx_career_public_active` ON `career_options`");
        $this->db->query("ALTER TABLE `career_options` DROP COLUMN `is_public`");
    }
}
