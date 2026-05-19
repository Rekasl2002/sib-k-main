<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignCoreSchemaWithApplication extends Migration
{
    private array $routePermissions = [
        'view_dashboard'               => 'Akses dashboard sesuai role',
        'manage_career_info'           => 'Kelola informasi karir dan universitas',
        'view_career_info'             => 'Lihat informasi karir dan universitas',
        'manage_sanctions'             => 'Kelola sanksi pelanggaran',
        'import_export_data'           => 'Import/Export data via Excel',
        'view_all_students'            => 'Lihat semua data siswa',
        'manage_settings'               => 'Kelola pengaturan aplikasi',
        'view_reports_aggregate'        => 'Lihat laporan agregat',
        'generate_reports_aggregate'    => 'Unduh/generate laporan agregat',
        'view_reports_individual'       => 'Lihat laporan individual siswa',
        'generate_reports_individual'   => 'Unduh/generate laporan individual siswa',
        'manage_light_violations'       => 'Kelola pelanggaran ringan untuk wali kelas',
        'submit_violation_submissions'  => 'Ajukan laporan/pengaduan pelanggaran',
    ];

    public function up()
    {
        $this->alignAssessmentsTable();
        $this->alignAssessmentResultsTable();
        $this->createAssessmentAssigneesTable();
        $this->createPasswordResetsTable();
        $this->createEmailVerificationsTable();
        $this->ensureRoutePermissions();
    }

    public function down()
    {
        if ($this->db->tableExists('email_verifications')) {
            $this->forge->dropTable('email_verifications', true);
        }

        if ($this->db->tableExists('password_resets')) {
            $this->forge->dropTable('password_resets', true);
        }

        if ($this->db->tableExists('assessment_assignees')) {
            $this->forge->dropTable('assessment_assignees', true);
        }

        if ($this->db->tableExists('assessments')) {
            foreach (['evaluation_mode', 'use_passing_score', 'show_score_to_student', 'result_release_at'] as $field) {
                if ($this->db->fieldExists($field, 'assessments')) {
                    $this->forge->dropColumn('assessments', $field);
                }
            }
        }

        if ($this->isMySQL() && $this->db->tableExists('assessment_results')) {
            $this->db->query(
                "ALTER TABLE assessment_results MODIFY status ENUM('In Progress','Completed','Graded','Expired') " .
                "NOT NULL DEFAULT 'In Progress' COMMENT 'Status pengerjaan/penilaian'"
            );
        }
    }

    private function alignAssessmentsTable(): void
    {
        if (! $this->db->tableExists('assessments')) {
            return;
        }

        $addedEvaluationMode = $this->addColumnIfMissing('assessments', 'evaluation_mode', [
            'type'       => 'ENUM',
            'constraint' => ['pass_fail', 'score_only', 'survey'],
            'default'    => 'pass_fail',
            'after'      => 'assessment_type',
        ]);

        $this->addColumnIfMissing('assessments', 'use_passing_score', [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 1,
            'after'      => 'passing_score',
        ]);

        $this->addColumnIfMissing('assessments', 'show_score_to_student', [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 1,
            'after'      => 'show_result_immediately',
        ]);

        $this->addColumnIfMissing('assessments', 'result_release_at', [
            'type' => 'DATETIME',
            'null' => true,
            'after' => 'end_date',
        ]);

        $builder = $this->db->table('assessments')
            ->set('evaluation_mode', "CASE WHEN use_passing_score = 1 THEN 'pass_fail' ELSE 'score_only' END", false);

        if (! $addedEvaluationMode) {
            $builder->where('evaluation_mode IS NULL', null, false);
        }

        $builder->update();
    }

    private function alignAssessmentResultsTable(): void
    {
        if (! $this->isMySQL() || ! $this->db->tableExists('assessment_results')) {
            return;
        }

        $this->db->query(
            "ALTER TABLE assessment_results MODIFY status ENUM('Assigned','In Progress','Completed','Graded','Expired','Abandoned') " .
            "NOT NULL DEFAULT 'Assigned' COMMENT 'Status pengerjaan/penilaian'"
        );
    }

    private function createAssessmentAssigneesTable(): void
    {
        if ($this->db->tableExists('assessment_assignees')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'assessment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'assigned_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'assigned_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['assessment_id', 'student_id'], false, true, 'unique_assessment_student');
        $this->forge->addKey('assessment_id');
        $this->forge->addKey('student_id');
        $this->forge->addForeignKey('assessment_id', 'assessments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('assessment_assignees');
    }

    private function createPasswordResetsTable(): void
    {
        if ($this->db->tableExists('password_resets')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('password_resets');
    }

    private function createEmailVerificationsTable(): void
    {
        if ($this->db->tableExists('email_verifications')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('token');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('email_verifications');
    }

    private function ensureRoutePermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        foreach ($this->routePermissions as $name => $description) {
            $permission = $this->db->table('permissions')
                ->where('permission_name', $name)
                ->get()
                ->getRowArray();

            if (! $permission) {
                $this->db->table('permissions')->insert([
                    'permission_name' => $name,
                    'description'     => $description,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if (! $this->db->tableExists('roles') || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $this->grantPermissions(1, array_keys($this->routePermissions));
        $this->grantPermissions(2, [
            'view_dashboard',
            'manage_career_info',
            'manage_sanctions',
            'import_export_data',
            'view_all_students',
            'view_reports_aggregate',
            'generate_reports_aggregate',
        ]);
        $this->grantPermissions(3, [
            'view_dashboard',
            'manage_career_info',
            'view_career_info',
            'manage_sanctions',
            'view_all_students',
            'view_reports_individual',
            'generate_reports_individual',
        ]);
        $this->grantPermissions(4, [
            'view_dashboard',
            'manage_sanctions',
            'view_reports_individual',
            'generate_reports_individual',
            'manage_light_violations',
        ]);
        $this->grantPermissions(5, [
            'view_dashboard',
            'view_career_info',
            'submit_violation_submissions',
        ]);
        $this->grantPermissions(6, [
            'view_dashboard',
            'view_career_info',
            'view_reports_individual',
            'generate_reports_individual',
            'submit_violation_submissions',
        ]);
    }

    private function grantPermissions(int $roleId, array $permissionNames): void
    {
        $role = $this->db->table('roles')->where('id', $roleId)->get()->getRowArray();
        if (! $role) {
            return;
        }

        foreach ($permissionNames as $permissionName) {
            $permission = $this->db->table('permissions')
                ->select('id')
                ->where('permission_name', $permissionName)
                ->get()
                ->getRowArray();

            if (! $permission) {
                continue;
            }

            $exists = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', (int) $permission['id'])
                ->get()
                ->getRowArray();

            if (! $exists) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => (int) $permission['id'],
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function addColumnIfMissing(string $table, string $field, array $definition): bool
    {
        if ($this->db->fieldExists($field, $table)) {
            return false;
        }

        $this->forge->addColumn($table, [$field => $definition]);
        return true;
    }

    private function isMySQL(): bool
    {
        return stripos((string) $this->db->DBDriver, 'mysql') !== false;
    }
}
