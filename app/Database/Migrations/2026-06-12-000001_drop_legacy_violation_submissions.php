<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropLegacyViolationSubmissions extends Migration
{
    private array $legacyPermissions = [
        'submit_violation_submissions',
        'view_violation_submissions',
        'review_violation_submissions',
        'manage_violation_submissions',
    ];

    public function up()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        if ($this->db->tableExists('violation_submissions')) {
            $this->forge->dropTable('violation_submissions', true);
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        $this->removeLegacyPermissions();
        $this->removeLegacyNotifications();
    }

    public function down()
    {
        // Fitur pelaporan pelanggaran lama sudah digantikan oleh Konsultasi & Pengaduan.
    }

    private function removeLegacyPermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $rows = $this->db->table('permissions')
            ->select('id')
            ->whereIn('permission_name', $this->legacyPermissions)
            ->get()
            ->getResultArray();

        $ids = array_values(array_filter(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $rows)));
        if ($ids === []) {
            return;
        }

        if ($this->db->tableExists('role_permissions')) {
            $this->db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
        }

        $this->db->table('permissions')->whereIn('id', $ids)->delete();
    }

    private function removeLegacyNotifications(): void
    {
        if (! $this->db->tableExists('notifications')) {
            return;
        }

        $this->db->table('notifications')
            ->whereIn('type', ['violation', 'violation_submission'])
            ->delete();
    }
}
