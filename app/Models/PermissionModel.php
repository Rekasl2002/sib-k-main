<?php

/**
 * File Path: app/Models/PermissionModel.php
 * 
 * Permission Model
 * Mengelola data permissions/izin akses dalam sistem RBAC
 * 
 * @package    SIB-K
 * @subpackage Models
 * @category   RBAC
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    public    $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'permission_name',
        'description',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'permission_name' => 'required|min_length[3]|max_length[100]|is_unique[permissions.permission_name,id,{id}]',
        'description'     => 'permit_empty|max_length[500]',
    ];

    protected $validationMessages = [
        'permission_name' => [
            'required'   => 'Nama permission harus diisi',
            'min_length' => 'Nama permission minimal 3 karakter',
            'max_length' => 'Nama permission maksimal 100 karakter',
            'is_unique'  => 'Nama permission sudah digunakan',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Katalog izin ramah awam (bahasa Indonesia).
     *
     * Dipakai halaman Kelola Peran agar Admin tidak perlu menghafal kode teknis.
     * Kunci = permission_name di tabel permissions.
     * - label      : nama singkat yang mudah dipahami
     * - keterangan : penjelasan sederhana apa yang bisa dilakukan pemilik izin
     * - group      : kelompok fitur untuk pengelompokan tampilan
     *
     * Izin baru yang belum terdaftar di sini otomatis masuk kelompok "Lainnya"
     * dengan label = kode teknisnya (lihat getPermissionsGroupedIndo()).
     *
     * @return array<string,array{label:string,keterangan:string,group:string}>
     */
    public static function catalog(): array
    {
        return [
            // ----- Umum -----
            'view_dashboard' => [
                'label'      => 'Membuka Dashboard',
                'keterangan' => 'Melihat halaman beranda berisi ringkasan data sesuai perannya.',
                'group'      => 'Umum',
            ],
            'view_staff_info' => [
                'label'      => 'Melihat Info Guru BK & Wali Kelas',
                'keterangan' => 'Melihat daftar nama dan kontak Guru BK serta Wali Kelas.',
                'group'      => 'Umum',
            ],
            'send_messages' => [
                'label'      => 'Menggunakan Pesan',
                'keterangan' => 'Mengirim dan menerima pesan (chat) di dalam aplikasi.',
                'group'      => 'Umum',
            ],
            // ----- Administrasi Sistem -----
            'manage_users' => [
                'label'      => 'Kelola Akun Pengguna',
                'keterangan' => 'Menambah, mengubah, menonaktifkan, dan menghapus akun pengguna.',
                'group'      => 'Administrasi Sistem',
            ],
            'manage_roles' => [
                'label'      => 'Kelola Peran & Izin Akses',
                'keterangan' => 'Mengatur peran pengguna dan izin apa saja yang dimiliki tiap peran (halaman ini).',
                'group'      => 'Administrasi Sistem',
            ],
            'manage_settings' => [
                'label'      => 'Kelola Pengaturan Aplikasi',
                'keterangan' => 'Mengubah pengaturan umum aplikasi, termasuk pengaturan notifikasi.',
                'group'      => 'Administrasi Sistem',
            ],

            // ----- Data Akademik & Siswa -----
            'manage_academic_data' => [
                'label'      => 'Kelola Tahun Ajaran & Kelas',
                'keterangan' => 'Menambah dan mengubah tahun ajaran, kelas, serta data akademik lainnya.',
                'group'      => 'Data Akademik & Siswa',
            ],
            'view_all_students' => [
                'label'      => 'Melihat Daftar Siswa',
                'keterangan' => 'Melihat daftar dan profil siswa sesuai lingkup perannya (mis. Wali Kelas hanya kelas binaan).',
                'group'      => 'Data Akademik & Siswa',
            ],
            'manage_students' => [
                'label'      => 'Kelola Data Siswa & Akun Orang Tua',
                'keterangan' => 'Menambah dan mengubah data siswa beserta akun orang tuanya sesuai lingkup peran.',
                'group'      => 'Data Akademik & Siswa',
            ],
            'view_student_portfolio' => [
                'label'      => 'Melihat Portofolio/Profil Pribadi',
                'keterangan' => 'Siswa melihat data dirinya sendiri; Orang Tua melihat data anaknya.',
                'group'      => 'Data Akademik & Siswa',
            ],
            'import_export_data' => [
                'label'      => 'Impor & Ekspor Data',
                'keterangan' => 'Mengunggah data siswa/orang tua dari berkas dan mengunduh data ke berkas (Excel/CSV).',
                'group'      => 'Data Akademik & Siswa',
            ],

            // ----- Konsultasi & Pengaduan -----
            'submit_consultation_complaints' => [
                'label'      => 'Mengajukan Konsultasi/Pengaduan',
                'keterangan' => 'Membuat laporan konsultasi atau pengaduan baru dan memantau statusnya.',
                'group'      => 'Konsultasi & Pengaduan',
            ],
            'review_consultation_complaints' => [
                'label'      => 'Meninjau Konsultasi/Pengaduan',
                'keterangan' => 'Melihat dan memproses laporan yang masuk (mengubah status, memberi tanggapan).',
                'group'      => 'Konsultasi & Pengaduan',
            ],
            'manage_consultation_complaints' => [
                'label'      => 'Kelola Penuh Konsultasi/Pengaduan',
                'keterangan' => 'Mengubah, menindaklanjuti menjadi layanan BK, dan menghapus laporan sesuai wewenang.',
                'group'      => 'Konsultasi & Pengaduan',
            ],

            // ----- Layanan BK -----
            'manage_bk_services' => [
                'label'      => 'Kelola Layanan BK',
                'keterangan' => 'Membuat dan mengubah catatan Bimbingan, Konseling, Kolaborasi Orang Tua, Kunjungan Rumah, dan Konferensi Kasus.',
                'group'      => 'Layanan BK',
            ],
            'view_bk_services' => [
                'label'      => 'Melihat Jadwal/Kegiatan BK',
                'keterangan' => 'Melihat jadwal atau garis besar kegiatan layanan BK sesuai batas kerahasiaan perannya.',
                'group'      => 'Layanan BK',
            ],
            'view_counseling_sessions' => [
                'label'      => 'Melihat Jadwal & Riwayat Sesi Kelas Binaan',
                'keterangan' => 'Wali Kelas melihat jadwal dan riwayat kegiatan BK siswa kelas binaannya (tanpa detail rahasia).',
                'group'      => 'Layanan BK',
            ],

            // ----- Penugasan -----
            'manage_bk_assignments' => [
                'label'      => 'Kelola Penugasan',
                'keterangan' => 'Koordinator BK membuat, mengubah, dan menghapus tugas untuk Guru BK.',
                'group'      => 'Penugasan',
            ],
            'view_bk_assignments' => [
                'label'      => 'Melihat & Mengerjakan Tugas',
                'keterangan' => 'Guru BK melihat tugas yang diberikan kepadanya dan memperbarui statusnya.',
                'group'      => 'Penugasan',
            ],

            // ----- Asesmen -----
            'manage_assessments' => [
                'label'      => 'Kelola Asesmen',
                'keterangan' => 'Membuat asesmen, membagikan ke siswa, dan melihat/menilai hasilnya.',
                'group'      => 'Asesmen',
            ],
            'take_assessments' => [
                'label'      => 'Mengerjakan Asesmen',
                'keterangan' => 'Siswa mengerjakan asesmen yang ditugaskan dan melihat hasilnya sendiri.',
                'group'      => 'Asesmen',
            ],

            // ----- Info Karier & Studi Lanjut -----
            'manage_career_info' => [
                'label'      => 'Kelola Info Karier & Studi Lanjut',
                'keterangan' => 'Menambah, mengubah, dan menyembunyikan info karier serta perguruan tinggi.',
                'group'      => 'Info Karier & Studi Lanjut',
            ],
            'view_career_info' => [
                'label'      => 'Melihat Info Karier & Studi Lanjut',
                'keterangan' => 'Melihat info karier/perguruan tinggi; Siswa & Orang Tua juga bisa menyimpan pilihan.',
                'group'      => 'Info Karier & Studi Lanjut',
            ],

            // ----- Laporan -----
            'view_reports_aggregate' => [
                'label'      => 'Melihat Laporan Rekap Sekolah',
                'keterangan' => 'Melihat laporan gabungan seluruh sekolah (khusus Koordinator BK).',
                'group'      => 'Laporan',
            ],
            'generate_reports_aggregate' => [
                'label'      => 'Mengunduh Laporan Rekap Sekolah',
                'keterangan' => 'Mengunduh laporan gabungan seluruh sekolah dalam bentuk PDF/Excel.',
                'group'      => 'Laporan',
            ],
            'view_reports_individual' => [
                'label'      => 'Melihat Laporan Lingkup Sendiri',
                'keterangan' => 'Melihat laporan sesuai lingkupnya: siswa binaan, kelas binaan, atau anak sendiri.',
                'group'      => 'Laporan',
            ],
            'generate_reports_individual' => [
                'label'      => 'Mengunduh Laporan Lingkup Sendiri',
                'keterangan' => 'Mengunduh laporan lingkup sendiri dalam bentuk PDF/Excel.',
                'group'      => 'Laporan',
            ],
        ];
    }

    /**
     * Urutan kelompok izin untuk tampilan halaman Kelola Peran.
     *
     * @return array<int,string>
     */
    public static function groupOrder(): array
    {
        return [
            'Umum',
            'Administrasi Sistem',
            'Data Akademik & Siswa',
            'Konsultasi & Pengaduan',
            'Layanan BK',
            'Penugasan',
            'Asesmen',
            'Info Karier & Studi Lanjut',
            'Laporan',
            'Lainnya',
        ];
    }

    /**
     * Ambil semua permission dari DB, dilengkapi label/keterangan/kelompok
     * berbahasa Indonesia dari catalog(), lalu dikelompokkan untuk tampilan.
     *
     * @return array<string,array<int,array<string,mixed>>> [nama_kelompok => [permission, ...]]
     */
    public function getPermissionsGroupedIndo(): array
    {
        $catalog     = self::catalog();
        $permissions = $this->orderBy('id', 'ASC')->findAll();

        $grouped = [];
        foreach ($permissions as $permission) {
            $name = (string) $permission['permission_name'];
            $meta = $catalog[$name] ?? null;

            $permission['label']      = $meta['label'] ?? $name;
            $permission['keterangan'] = $meta['keterangan'] ?? (string) ($permission['description'] ?? '');
            $group                    = $meta['group'] ?? 'Lainnya';

            $grouped[$group][] = $permission;
        }

        // Susun sesuai urutan kelompok baku; kelompok tak dikenal ditaruh di akhir.
        $ordered = [];
        foreach (self::groupOrder() as $groupName) {
            if (!empty($grouped[$groupName])) {
                $ordered[$groupName] = $grouped[$groupName];
                unset($grouped[$groupName]);
            }
        }

        return $ordered + $grouped;
    }

    /**
     * Get permissions for specific role
     * 
     * @param int $roleId
     * @return array
     */
    public function getPermissionsByRole($roleId)
    {
        $db = \Config\Database::connect();

        return $db->table('role_permissions')
            ->select('permissions.*')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->orderBy('permissions.permission_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get permission IDs for specific role
     * 
     * @param int $roleId
     * @return array
     */
    public function getPermissionIdsByRole($roleId)
    {
        $db = \Config\Database::connect();

        $permissions = $db->table('role_permissions')
            ->select('permission_id')
            ->where('role_id', $roleId)
            ->get()
            ->getResultArray();

        return array_column($permissions, 'permission_id');
    }

    /**
     * Check if role has specific permission
     * 
     * @param int $roleId
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission($roleId, $permissionName)
    {
        $db = \Config\Database::connect();

        $count = $db->table('role_permissions')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->where('permissions.permission_name', $permissionName)
            ->countAllResults();

        return $count > 0;
    }

    /**
     * Get permission by name
     * 
     * @param string $permissionName
     * @return array|null
     */
    public function getPermissionByName($permissionName)
    {
        return $this->where('permission_name', $permissionName)->first();
    }

    /**
     * Check if permission can be deleted
     * 
     * @param int $permissionId
     * @return bool
     */
    public function canDelete($permissionId)
    {
        $db = \Config\Database::connect();

        // Check if permission is assigned to any role
        $count = $db->table('role_permissions')
            ->where('permission_id', $permissionId)
            ->countAllResults();

        return $count === 0;
    }
}
