<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveCasesViolationsAndSanctions extends Migration
{
    private array $removedPermissions = [
        'manage_violations',
        'view_violations',
        'manage_sanctions',
        'manage_light_violations',
        'convert_violation_submissions',
    ];

    public function up()
    {
        $this->cleanViolationSubmissions();
        $this->dropRemovedFeatureTables();
        $this->dropStudentViolationPoints();
        $this->removePermissions();
        $this->removeViolationNotifications();
    }

    public function down()
    {
        // Intentionally left empty: this migration removes a deprecated feature.
    }

    private function cleanViolationSubmissions(): void
    {
        if (! $this->db->tableExists('violation_submissions')) {
            return;
        }

        if ($this->db->DBDriver === 'MySQLi') {
            $this->dropForeignKeyIfExists('violation_submissions', 'fk_violation_submissions_category');
            $this->dropForeignKeyIfExists('violation_submissions', 'fk_violation_submissions_converted');
        }

        if ($this->db->fieldExists('status', 'violation_submissions')) {
            $this->db->table('violation_submissions')
                ->where('status', 'Dikonversi')
                ->update(['status' => 'Diterima']);

            if ($this->db->DBDriver === 'MySQLi') {
                $table = $this->ident($this->db->prefixTable('violation_submissions'));
                $this->db->query(
                    "ALTER TABLE {$table} MODIFY `status` ENUM('Diajukan', 'Ditinjau', 'Ditolak', 'Diterima') NOT NULL DEFAULT 'Diajukan'"
                );
            }
        }

        foreach (['category_id', 'converted_violation_id'] as $column) {
            if ($this->db->fieldExists($column, 'violation_submissions')) {
                $this->forge->dropColumn('violation_submissions', $column);
            }
        }
    }

    private function dropRemovedFeatureTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach (['sanctions', 'violations', 'violation_categories'] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function dropStudentViolationPoints(): void
    {
        if ($this->db->tableExists('students') && $this->db->fieldExists('total_violation_points', 'students')) {
            $this->forge->dropColumn('students', 'total_violation_points');
        }
    }

    private function removePermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $rows = $this->db->table('permissions')
            ->select('id')
            ->whereIn('permission_name', $this->removedPermissions)
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

    private function removeViolationNotifications(): void
    {
        if ($this->db->tableExists('notifications')) {
            $this->db->table('notifications')->where('type', 'violation')->delete();
        }
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        $tableName = $this->db->prefixTable($table);

        $row = $this->db->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$tableName, $constraint]
        )->getRowArray();

        if (! $row) {
            return;
        }

        $this->db->query(
            'ALTER TABLE ' . $this->ident($tableName) . ' DROP FOREIGN KEY ' . $this->ident($constraint)
        );
    }

    private function ident(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
