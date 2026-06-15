<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $permissionMap = $this->permissionMap();

        $sets = [
            1 => array_keys($permissionMap),
            2 => [
                'view_dashboard', 'view_all_students', 'manage_students', 'manage_users',
                'manage_academic_data', 'import_export_data', 'send_messages',
                'manage_assessments', 'manage_career_info', 'view_career_info',
                'manage_bk_services', 'view_bk_services', 'manage_consultation_complaints',
                'review_consultation_complaints', 'manage_bk_assignments', 'view_bk_assignments',
                'view_bk_reports', 'generate_bk_reports', 'view_reports_aggregate',
                'generate_reports_aggregate', 'view_reports_individual', 'generate_reports_individual',
                'access_simulation_suite',
            ],
            3 => [
                'view_dashboard', 'view_all_students', 'send_messages', 'manage_assessments',
                'manage_career_info', 'view_career_info', 'manage_bk_services', 'view_bk_services',
                'manage_consultation_complaints', 'review_consultation_complaints',
                'view_bk_assignments', 'view_bk_reports', 'generate_bk_reports',
                'view_reports_individual', 'generate_reports_individual', 'access_simulation_suite',
                'manage_counseling_sessions', 'view_counseling_sessions',
            ],
            4 => [
                // Wali Kelas mendapat import_export_data agar dapat membantu impor siswa/orang tua pada kelas binaannya.
                'view_dashboard', 'view_all_students', 'manage_students', 'send_messages', 'submit_consultation_complaints',
                'view_bk_services', 'view_bk_reports', 'view_reports_individual',
                'generate_reports_individual', 'view_career_info', 'access_simulation_suite',
                'import_export_data', 'view_counseling_sessions',
            ],
            5 => [
                'view_dashboard', 'send_messages', 'submit_consultation_complaints', 'take_assessments',
                'schedule_counseling', 'view_bk_services', 'view_career_info', 'view_student_portfolio',
                'access_simulation_suite', 'view_counseling_sessions',
            ],
            6 => [
                'view_dashboard', 'send_messages', 'submit_consultation_complaints', 'view_bk_services',
                'view_career_info', 'view_bk_reports', 'view_reports_individual',
                'generate_reports_individual', 'view_student_portfolio', 'access_simulation_suite',
                'view_counseling_sessions',
            ],
        ];

        // Info Guru/Staf untuk semua peran non-Admin (Admin sudah punya semua permission).
        foreach ([2, 3, 4, 5, 6] as $roleIdForStaff) {
            $sets[$roleIdForStaff][] = 'view_staff_info';
        }

        $rows = [];
        foreach ($sets as $roleId => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                if (! isset($permissionMap[$permissionName])) {
                    continue;
                }

                $rows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionMap[$permissionName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('role_permissions')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        $this->db->table('role_permissions')->insertBatch($rows);

        echo "OK Role Permissions seeded successfully!\n";
    }

    /**
     * @return array<string,int>
     */
    private function permissionMap(): array
    {
        $rows = $this->db->table('permissions')
            ->select('id, permission_name')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['permission_name']] = (int) $row['id'];
        }

        return $map;
    }
}
