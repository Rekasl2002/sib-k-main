<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowHomeroomViolationSubmissions extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('violation_submissions')) {
            return;
        }

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query(
                "ALTER TABLE violation_submissions MODIFY reporter_type ENUM('student', 'parent', 'homeroom') NOT NULL"
            );
        }

        $permissions = [
            'view_violation_submissions'    => 'Lihat pengaduan pelanggaran',
            'review_violation_submissions'  => 'Tinjau pengaduan pelanggaran',
            'manage_violation_submissions'  => 'Kelola pengaduan pelanggaran',
            'convert_violation_submissions' => 'Konversi pengaduan menjadi kasus pelanggaran',
            'submit_violation_submissions'  => 'Ajukan laporan/pengaduan pelanggaran',
            'view_career_info'              => 'Lihat fitur info karier dan info studi lanjut',
            'send_messages'                 => 'Kirim pesan internal',
        ];

        foreach ($permissions as $name => $description) {
            $this->ensurePermission($name, $description);
        }

        foreach ([2, 3] as $roleId) {
            $this->grant($roleId, [
                'view_violation_submissions',
                'review_violation_submissions',
                'manage_violation_submissions',
                'convert_violation_submissions',
                'view_career_info',
            ]);
        }

        foreach ([2, 3, 4, 5, 6] as $roleId) {
            $this->grant($roleId, ['send_messages']);
        }

        $this->grant(4, ['submit_violation_submissions', 'view_career_info']);
        $this->grant(5, ['submit_violation_submissions', 'view_career_info']);
        $this->grant(6, ['submit_violation_submissions', 'view_career_info']);
    }

    public function down()
    {
        if (! $this->db->tableExists('violation_submissions')) {
            return;
        }

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->table('violation_submissions')
                ->where('reporter_type', 'homeroom')
                ->update(['reporter_type' => 'student']);

            $this->db->query(
                "ALTER TABLE violation_submissions MODIFY reporter_type ENUM('student', 'parent') NOT NULL"
            );
        }
    }

    private function ensurePermission(string $name, string $description): int
    {
        if (! $this->db->tableExists('permissions')) {
            return 0;
        }

        $existing = $this->db->table('permissions')
            ->select('id')
            ->where('permission_name', $name)
            ->get()
            ->getRowArray();

        if ($existing) {
            return (int) $existing['id'];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('permissions')->insert([
            'permission_name' => $name,
            'description'     => $description,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<int, string> $permissionNames
     */
    private function grant(int $roleId, array $permissionNames): void
    {
        if (! $this->db->tableExists('role_permissions') || ! $this->db->tableExists('permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($permissionNames as $permissionName) {
            $permission = $this->db->table('permissions')
                ->select('id')
                ->where('permission_name', $permissionName)
                ->get()
                ->getRowArray();

            $permissionId = (int)($permission['id'] ?? 0);
            if ($permissionId <= 0) {
                continue;
            }

            $exists = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $this->db->table('role_permissions')->insert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }
}
