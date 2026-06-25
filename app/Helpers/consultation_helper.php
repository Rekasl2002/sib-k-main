<?php

/**
 * File Path: app/Helpers/consultation_helper.php
 *
 * Helper fitur Konsultasi & Pengaduan.
 * Membaca sakelar (toggle) dari Pengaturan Aplikasi Admin (tabel settings, group
 * "consultation") untuk menentukan apakah fitur aktif dan peran mana saja yang
 * boleh mengirim Konsultasi & Pengaduan.
 *
 * Aturan (sesuai catatan perbaikan + TC-ST-04):
 * - Koordinator BK & Guru BK SELALU punya akses (TIDAK tunduk pada sakelar apa pun,
 *   termasuk saat sakelar utama/master DIMATIKAN) agar laporan yang masih berjalan
 *   tetap dapat ditangani/ditutup oleh staf BK.
 * - Wali Kelas, Siswa, Orang Tua mengikuti sakelar masing-masing + sakelar utama;
 *   bila sakelar utama dimatikan, ketiga peran pelapor ini disembunyikan & ditolak.
 * - Admin tidak memakai fitur ini.
 * - Bawaan (default) bila setting belum ada: SEMUA menyala.
 */

if (! function_exists('consultation_feature_enabled')) {
    /**
     * Apakah fitur Konsultasi & Pengaduan menyala secara keseluruhan (sakelar utama)?
     */
    function consultation_feature_enabled(): bool
    {
        return filter_var(setting('consultation.enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }
}

if (! function_exists('consultation_role_can_submit')) {
    /**
     * Apakah peran tertentu boleh MENGIRIM (membuat) Konsultasi & Pengaduan?
     *
     * @param string|null $roleName Nama peran (mis. "Siswa", "Guru BK", "Wali Kelas").
     */
    function consultation_role_can_submit(?string $roleName): bool
    {
        $role = strtolower(trim((string) $roleName));

        // Staf BK (Koordinator BK & Guru BK) tidak tunduk pada sakelar Pengaturan:
        // mereka SELALU dapat mengakses & menangani Konsultasi & Pengaduan, termasuk
        // saat sakelar utama (master) dimatikan (TC-ST-04).
        if (in_array($role, ['koordinator bk', 'koordinator', 'coordinator', 'guru bk', 'counselor'], true)) {
            return true;
        }

        // Peran pelapor (Wali Kelas, Siswa, Orang Tua) tunduk pada sakelar utama
        // DAN sakelar perannya masing-masing.
        if (! consultation_feature_enabled()) {
            return false;
        }

        return match (true) {
            in_array($role, ['wali kelas', 'homeroom'], true) => filter_var(setting('consultation.homeroom_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            in_array($role, ['siswa', 'student'], true)       => filter_var(setting('consultation.student_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            in_array($role, ['orang tua', 'parent'], true)    => filter_var(setting('consultation.parent_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            default                                           => false,
        };
    }
}

if (! function_exists('consultation_role_can_view')) {
    /**
     * Apakah peran boleh MELIHAT menu Konsultasi & Pengaduan (sidebar/halaman)?
     * Untuk saat ini sama dengan hak mengirim; dipisah agar mudah dikembangkan.
     */
    function consultation_role_can_view(?string $roleName): bool
    {
        return consultation_role_can_submit($roleName);
    }
}
