<?php

/**
 * File Path: app/Database/Migrations/2026-07-05-000001_RemoveUnusedPermissions.php
 *
 * Sinkronisasi RBAC dengan keadaan aplikasi terkini:
 * menghapus 6 permission yang sudah TIDAK dipakai kode sama sekali
 * (tidak ada di rute, filter, controller, maupun menu):
 *
 * - manage_counseling_sessions : fitur Konseling kini di bawah manage_bk_services
 * - schedule_counseling        : fitur "Ajukan Konseling" siswa sudah dihapus
 *                                (pengajuan lewat Konsultasi & Pengaduan)
 * - view_reports / generate_reports : laporan kini memakai
 *                                view/generate_reports_aggregate & _individual
 * - view_bk_reports / generate_bk_reports : idem, tergantikan laporan agregat/individual
 *
 * down() mengembalikan permission beserta pemetaan perannya semula.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveUnusedPermissions extends Migration
{
    /**
     * permission_name => [deskripsi, daftar role_id pemilik semula]
     *
     * @var array<string,array{0:string,1:array<int,int>}>
     */
    private array $unused = [
        'manage_counseling_sessions' => ['Kelola sesi konseling lama yang masih dipakai aplikasi', [1, 3]],
        'schedule_counseling'        => ['Ajukan atau jadwalkan konseling', [1, 5]],
        'generate_reports'           => ['Unduh laporan umum', [1]],
        'view_reports'               => ['Lihat laporan umum', [1]],
        'view_bk_reports'            => ['Lihat laporan layanan BK', [1, 2, 3, 4, 6]],
        'generate_bk_reports'        => ['Unduh laporan layanan BK', [1, 2, 3]],
    ];

    public function up()
    {
        $names = array_keys($this->unused);

        $rows = $this->db->table('permissions')
            ->select('id')
            ->whereIn('permission_name', $names)
            ->get()->getResultArray();

        $ids = array_map(static fn($r) => (int) $r['id'], $rows);
        if ($ids === []) {
            return;
        }

        $this->db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
        $this->db->table('permissions')->whereIn('id', $ids)->delete();
    }

    public function down()
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->unused as $name => [$description, $roleIds]) {
            $exists = $this->db->table('permissions')
                ->where('permission_name', $name)
                ->get()->getFirstRow('array');

            if ($exists) {
                $permissionId = (int) $exists['id'];
            } else {
                $this->db->table('permissions')->insert([
                    'permission_name' => $name,
                    'description'     => $description,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
                $permissionId = (int) $this->db->insertID();
            }

            foreach ($roleIds as $roleId) {
                $already = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->countAllResults();

                if ($already === 0) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                        'created_at'    => $now,
                    ]);
                }
            }
        }
    }
}
