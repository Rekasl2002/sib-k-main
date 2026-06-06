<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class SimulationController extends BaseController
{
    private const DEFAULT_ROLE = 'guru-bk';

    public function index()
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $features = $this->featuresForRole($roleMode);
        $progress = $this->progressForRole($roleMode);
        $features = $this->annotateFeatures($features, $roleMode, $progress);

        return view('simulation/index', [
            'title'       => 'Simulasi Fitur',
            'features'    => $features,
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'startUrl'    => $this->startUrl($features, $roleMode),
            'stats'       => [
                ['label' => 'Fitur tersedia', 'value' => count($features), 'icon' => 'mdi mdi-apps', 'tone' => 'primary'],
                ['label' => 'Data asli berubah', 'value' => 0, 'icon' => 'mdi mdi-database-lock', 'tone' => 'success'],
                ['label' => 'Mode peran', 'value' => $this->roleLabel($roleMode), 'icon' => 'mdi mdi-account-switch', 'tone' => 'info'],
                ['label' => 'Mode demo', 'value' => 'UI', 'icon' => 'mdi mdi-monitor-dashboard', 'tone' => 'warning'],
            ],
        ]);
    }

    public function feature(string $key)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $allFeatures = $this->features();
        $features = $this->featuresForRole($roleMode);
        $progress = $this->progressForRole($roleMode);

        if (! isset($allFeatures[$key])) {
            throw PageNotFoundException::forPageNotFound('Simulasi fitur tidak ditemukan.');
        }

        if (! isset($features[$key])) {
            return redirect()->to($this->urlWithRole('simulation', $roleMode))
                ->with('info', 'Simulasi tersebut tidak tersedia untuk mode peran ' . $this->roleLabel($roleMode) . '.');
        }

        if (! $this->isFeatureUnlocked($key, $features, $progress)) {
            return redirect()->to($this->firstPendingUrl($features, $roleMode, $progress))
                ->with('info', 'Selesaikan simulasi sebelumnya terlebih dahulu agar alurnya tidak terlewat.');
        }

        $features = $this->annotateFeatures($features, $roleMode, $progress);

        return view('simulation/feature', [
            'title'       => $features[$key]['title'],
            'activeKey'   => $key,
            'feature'     => $features[$key],
            'features'    => $features,
            'demo'        => $this->demoForRole($key, $roleMode),
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'isTried'     => in_array($key, $progress, true),
            'progressUrl' => $this->urlWithRole('simulation/progress/' . $key, $roleMode),
        ]);
    }

    public function progress(string $key)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $features = $this->featuresForRole($roleMode);
        $progress = $this->progressForRole($roleMode);

        if (! isset($features[$key])) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Simulasi tidak tersedia untuk peran ini.',
            ]);
        }

        if (! $this->isFeatureUnlocked($key, $features, $progress)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Simulasi sebelumnya belum dicoba.',
            ]);
        }

        $progress[] = $key;
        $progress = array_values(array_unique($progress));
        session()->set($this->progressSessionKey($roleMode), $progress);

        $features = $this->annotateFeatures($features, $roleMode, $progress);
        $next = $this->nextFeature($key, $features);

        return $this->response->setJSON([
            'ok' => true,
            'next_url' => $next ? base_url($next['url']) : base_url($this->urlWithRole('prototype', $roleMode)),
            'completed' => $progress,
        ]);
    }

    private function guardAccess()
    {
        helper('simulation_access');

        if (can_access_simulation_suite()) {
            return null;
        }

        return redirect()->to('/dashboard')
            ->with('error', 'Akses simulasi fitur belum diberikan oleh admin.');
    }

    private function roleOptions(): array
    {
        return [
            'koordinator-bk' => 'Koordinator BK',
            'guru-bk'       => 'Guru BK',
            'wali-kelas'    => 'Wali Kelas',
            'siswa'         => 'Siswa',
            'orang-tua'     => 'Orang Tua',
        ];
    }

    private function currentDemoRole(): string
    {
        $sessionRole = $this->normalizeRole((string) (session('role_name') ?? ''));
        if ($sessionRole === '') {
            $sessionRole = match ((int) (session('role_id') ?? 0)) {
                2 => 'koordinator-bk',
                3 => 'guru-bk',
                4 => 'wali-kelas',
                5 => 'siswa',
                6 => 'orang-tua',
                default => '',
            };
        }

        if (isset($this->roleOptions()[$sessionRole])) {
            return $sessionRole;
        }

        if (function_exists('simulation_access_is_admin') && simulation_access_is_admin()) {
            $queryRole = $this->normalizeRole((string) $this->request->getGet('role'));
            if (isset($this->roleOptions()[$queryRole])) {
                return $queryRole;
            }
        }

        return self::DEFAULT_ROLE;
    }

    private function normalizeRole(string $role): string
    {
        $role = strtolower(trim(str_replace('_', '-', $role)));
        $role = preg_replace('/\s+/', '-', $role) ?: '';

        return match ($role) {
            'koordinator', 'koordinator-bk', 'coordinator', 'counseling-coordinator' => 'koordinator-bk',
            'guru-bk', 'counselor', 'konselor' => 'guru-bk',
            'wali-kelas', 'homeroom' => 'wali-kelas',
            'student', 'siswa' => 'siswa',
            'parent', 'orang-tua', 'ortu' => 'orang-tua',
            default => $role,
        };
    }

    private function roleLabel(string $role): string
    {
        return $this->roleOptions()[$role] ?? 'Pengguna';
    }

    private function availableRoleOptions(string $role): array
    {
        if (function_exists('simulation_access_is_admin') && simulation_access_is_admin()) {
            return $this->roleOptions();
        }

        return [$role => $this->roleLabel($role)];
    }

    private function featuresForRole(string $role): array
    {
        return array_filter($this->features(), static function (array $feature) use ($role): bool {
            $roles = $feature['role_modes'] ?? [];
            return in_array('all', $roles, true) || in_array($role, $roles, true);
        });
    }

    private function withRoleUrls(array $features, string $role): array
    {
        foreach ($features as &$feature) {
            $feature['url'] = $this->urlWithRole((string) ($feature['url'] ?? '#'), $role);
        }

        return $features;
    }

    private function urlWithRole(string $url, string $role): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . 'role=' . rawurlencode($role);
    }

    private function progressSessionKey(string $role): string
    {
        return 'simulation_progress_' . $role;
    }

    private function progressForRole(string $role): array
    {
        $progress = session()->get($this->progressSessionKey($role));
        return is_array($progress) ? array_values(array_unique(array_map('strval', $progress))) : [];
    }

    private function annotateFeatures(array $features, string $role, array $progress): array
    {
        $previousTried = true;

        foreach ($features as $key => &$feature) {
            $isTried = in_array((string) $key, $progress, true);
            $isLocked = ! $previousTried;

            $feature['url'] = $this->urlWithRole((string) ($feature['url'] ?? '#'), $role);
            $feature['is_tried'] = $isTried;
            $feature['is_locked'] = $isLocked;

            $previousTried = $isTried;
        }

        return $features;
    }

    private function isFeatureUnlocked(string $key, array $features, array $progress): bool
    {
        $previousTried = true;

        foreach (array_keys($features) as $featureKey) {
            if ($featureKey === $key) {
                return $previousTried;
            }

            $previousTried = in_array((string) $featureKey, $progress, true);
        }

        return false;
    }

    private function firstPendingUrl(array $features, string $role, array $progress): string
    {
        foreach ($features as $key => $feature) {
            if (! in_array((string) $key, $progress, true)) {
                return base_url($this->urlWithRole((string) ($feature['url'] ?? 'simulation'), $role));
            }
        }

        $first = reset($features) ?: ['url' => 'simulation'];
        return base_url($this->urlWithRole((string) ($first['url'] ?? 'simulation'), $role));
    }

    private function startUrl(array $features, string $role): string
    {
        foreach ($features as $feature) {
            if (empty($feature['is_tried'])) {
                return (string) ($feature['url'] ?? $this->urlWithRole('simulation', $role));
            }
        }

        $first = reset($features) ?: ['url' => $this->urlWithRole('simulation', $role)];
        return (string) ($first['url'] ?? $this->urlWithRole('simulation', $role));
    }

    private function nextFeature(string $key, array $features): ?array
    {
        $keys = array_keys($features);
        $index = array_search($key, $keys, true);
        if ($index === false) {
            return null;
        }

        return $features[$keys[$index + 1] ?? ''] ?? null;
    }

    private function features(): array
    {
        return [
            'login' => [
                'title'       => 'Log In',
                'short_title' => 'Log In',
                'url'         => 'simulation/login',
                'icon'        => 'mdi mdi-login',
                'tone'        => 'primary',
                'roles'       => 'Akun sekolah non-admin',
                'role_modes'  => ['all'],
                'summary'     => 'Simulasi autentikasi, validasi akun aktif, dan pengalihan dashboard sesuai peran.',
            ],
            'profile-password' => [
                'title'       => 'Profil dan Ganti Password',
                'short_title' => 'Profil',
                'url'         => 'simulation/profile-password',
                'icon'        => 'mdi mdi-account-circle',
                'tone'        => 'info',
                'roles'       => 'Akun sekolah non-admin',
                'role_modes'  => ['all'],
                'summary'     => 'Simulasi pembaruan profil, foto, kontak, dan perubahan password.',
            ],
            'students' => [
                'title'       => 'Data Siswa',
                'short_title' => 'Siswa',
                'url'         => 'simulation/students',
                'icon'        => 'mdi mdi-account-school',
                'tone'        => 'success',
                'roles'       => 'Koordinator BK, Guru BK, Wali Kelas',
                'role_modes'  => ['koordinator-bk', 'guru-bk', 'wali-kelas'],
                'summary'     => 'Simulasi pencarian siswa, profil siswa, dan ringkasan pendampingan sesuai kewenangan peran.',
            ],
            'student-import' => [
                'title'       => 'Impor Siswa',
                'short_title' => 'Impor Siswa',
                'url'         => 'simulation/student-import',
                'icon'        => 'mdi mdi-file-upload-outline',
                'tone'        => 'info',
                'roles'       => 'Koordinator BK',
                'role_modes'  => ['koordinator-bk'],
                'summary'     => 'Simulasi unggah template, validasi baris, dan pratinjau data sebelum impor.',
            ],
            'counseling-sessions' => [
                'title'       => 'Sesi Konseling',
                'short_title' => 'Sesi Konseling',
                'url'         => 'simulation/counseling-sessions',
                'icon'        => 'mdi mdi-calendar-check',
                'tone'        => 'warning',
                'roles'       => 'Guru BK, Koordinator BK, Wali Kelas, Siswa, Orang Tua',
                'role_modes'  => ['koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'summary'     => 'Simulasi jadwal, pengajuan, persetujuan, catatan sesi, dan status tindak lanjut.',
            ],
            'reports' => [
                'title'       => 'Laporan',
                'short_title' => 'Laporan',
                'url'         => 'simulation/reports',
                'icon'        => 'mdi mdi-file-chart',
                'tone'        => 'primary',
                'roles'       => 'Koordinator BK, Guru BK, Wali Kelas, Orang Tua',
                'role_modes'  => ['koordinator-bk', 'guru-bk', 'wali-kelas', 'orang-tua'],
                'summary'     => 'Simulasi filter laporan, pratinjau tabel, rekap individual/agregat, dan ekspor.',
            ],
            'dashboard' => [
                'title'       => 'Dashboard',
                'short_title' => 'Dashboard',
                'url'         => 'simulation/dashboard',
                'icon'        => 'mdi mdi-view-dashboard',
                'tone'        => 'success',
                'roles'       => 'Koordinator BK, Guru BK, Wali Kelas, Siswa, Orang Tua',
                'role_modes'  => ['all'],
                'summary'     => 'Simulasi kartu statistik, aktivitas terbaru, prioritas layanan BK, dan shortcut kerja sesuai peran.',
            ],
        ];
    }

    private function demoForRole(string $key, string $role): array
    {
        $base = $this->demoData()[$key] ?? [];
        $override = $this->roleDemoOverrides()[$key][$role] ?? [];

        if ($key === 'login') {
            $override = array_replace($override, $this->loginDemoForRole($role));
        }

        return array_replace($base, $override, [
            'role_label' => $this->roleLabel($role),
            'role_mode'  => $role,
        ]);
    }

    private function loginDemoForRole(string $role): array
    {
        $accounts = [
            'koordinator-bk' => ['Akun' => 'koordinator01', 'Role' => 'Koordinator BK', 'Hasil' => 'Masuk ke dashboard koordinator'],
            'guru-bk'       => ['Akun' => 'gurubk01', 'Role' => 'Guru BK', 'Hasil' => 'Masuk ke dashboard Guru BK'],
            'wali-kelas'    => ['Akun' => 'walikelas01', 'Role' => 'Wali Kelas', 'Hasil' => 'Masuk ke dashboard wali kelas'],
            'siswa'         => ['Akun' => 'siswa001', 'Role' => 'Siswa', 'Hasil' => 'Masuk ke dashboard siswa'],
            'orang-tua'     => ['Akun' => 'parent001', 'Role' => 'Orang Tua', 'Hasil' => 'Masuk ke dashboard orang tua'],
        ];

        return [
            'metrics' => [['label' => 'Role tujuan', 'value' => $this->roleLabel($role)], ['label' => 'Status akun', 'value' => 'Aktif'], ['label' => 'Redirect', 'value' => 'Dashboard']],
            'records' => [$accounts[$role] ?? $accounts[self::DEFAULT_ROLE]],
            'form' => ['Username/Email', 'Password'],
            'action' => 'Masuk sebagai ' . $this->roleLabel($role),
            'role_note' => 'Simulasi login diarahkan ke dashboard dan menu milik ' . $this->roleLabel($role) . '.',
        ];
    }

    private function roleDemoOverrides(): array
    {
        return [
            'profile-password' => [
                'guru-bk' => [
                    'role_note' => 'Guru BK memperbarui kontak profesional dan password akun layanan BK.',
                    'records' => [
                        ['Field' => 'Nama', 'Contoh' => 'Siti Nurhaliza, S.Pd', 'Status' => 'Dapat diperbarui'],
                        ['Field' => 'Email', 'Contoh' => 'siti.bk@sibk.sch.id', 'Status' => 'Dapat diperbarui'],
                        ['Field' => 'Nomor WA', 'Contoh' => '0812-0000-0003', 'Status' => 'Dapat diperbarui'],
                    ],
                ],
                'siswa' => [
                    'role_note' => 'Siswa memperbarui kontak pribadi dan keamanan akun tanpa mengubah data akademik.',
                    'records' => [
                        ['Field' => 'Nama', 'Contoh' => 'Ahmad Fajar Nugraha', 'Status' => 'Dapat dilihat'],
                        ['Field' => 'Email', 'Contoh' => 'ahmad.fajar@student.sch.id', 'Status' => 'Dapat diperbarui'],
                        ['Field' => 'Password', 'Contoh' => 'Minimal 6 karakter', 'Status' => 'Butuh konfirmasi'],
                    ],
                ],
                'orang-tua' => [
                    'role_note' => 'Orang tua memperbarui kontak agar notifikasi dan laporan anak tetap tersampaikan.',
                    'records' => [
                        ['Field' => 'Nama', 'Contoh' => 'Ibu Siti Rahma', 'Status' => 'Dapat diperbarui'],
                        ['Field' => 'Email', 'Contoh' => 'siti.rahma@mail.test', 'Status' => 'Dapat diperbarui'],
                        ['Field' => 'Nomor WA', 'Contoh' => '0812-0000-0006', 'Status' => 'Dapat diperbarui'],
                    ],
                ],
            ],
            'students' => [
                'koordinator-bk' => [
                    'metrics' => [['label' => 'Siswa aktif', 'value' => '620'], ['label' => 'Kelas dipantau', 'value' => '18'], ['label' => 'Perlu perhatian', 'value' => '12']],
                    'steps' => ['Koordinator memilih kelas atau guru BK', 'Meninjau daftar siswa lintas kelas', 'Melihat ringkasan sesi dan pendampingan', 'Mengarahkan tindak lanjut ke Guru BK'],
                    'form' => ['Cari siswa/kelas', 'Filter Guru BK', 'Prioritas pendampingan', 'Catatan koordinasi'],
                    'action' => 'Tinjau Siswa Simulasi',
                    'role_note' => 'Koordinator BK melihat cakupan lintas kelas dan mengarahkan tindak lanjut.',
                ],
                'guru-bk' => [
                    'metrics' => [['label' => 'Siswa binaan', 'value' => '186'], ['label' => 'Perlu follow-up', 'value' => '9'], ['label' => 'Profil lengkap', 'value' => '87%']],
                    'steps' => ['Guru BK mencari siswa binaan', 'Membuka profil siswa', 'Menambah catatan pendampingan', 'Menjadwalkan tindak lanjut bila perlu'],
                    'form' => ['Nama/NISN siswa', 'Jenis perhatian', 'Catatan pendampingan', 'Rencana tindak lanjut'],
                    'action' => 'Simpan Catatan Simulasi',
                    'role_note' => 'Guru BK berfokus pada profil, riwayat, dan catatan pendampingan siswa binaan.',
                ],
                'wali-kelas' => [
                    'metrics' => [['label' => 'Siswa kelas', 'value' => '32'], ['label' => 'Catatan kelas', 'value' => '15'], ['label' => 'Perlu koordinasi', 'value' => '3']],
                    'steps' => ['Wali kelas membuka kelas binaan', 'Memilih siswa', 'Melihat ringkasan kelas dan konseling', 'Mengirim catatan ke Guru BK'],
                    'form' => ['Siswa kelas', 'Kondisi kelas', 'Catatan wali kelas', 'Tujuan koordinasi'],
                    'action' => 'Kirim Catatan Kelas Simulasi',
                    'role_note' => 'Wali kelas hanya melihat siswa kelas binaan dan mengirim catatan koordinasi.',
                ],
            ],
            'student-import' => [
                'koordinator-bk' => [
                    'role_note' => 'Impor siswa hanya tersedia untuk Koordinator BK pada mode simulasi non-admin.',
                ],
            ],
            'counseling-sessions' => [
                'koordinator-bk' => [
                    'steps' => ['Koordinator memantau antrian jadwal', 'Mengecek beban Guru BK', 'Mengalihkan sesi bila perlu', 'Memastikan tindak lanjut tercatat'],
                    'form' => ['Filter Guru BK', 'Tanggal', 'Status sesi', 'Catatan koordinasi'],
                    'action' => 'Pantau Jadwal Simulasi',
                    'role_note' => 'Koordinator BK melihat jadwal lintas Guru BK dan status tindak lanjut.',
                ],
                'guru-bk' => [
                    'steps' => ['Guru BK menerima pengajuan', 'Menentukan jadwal', 'Mencatat sesi', 'Menandai tindak lanjut'],
                    'form' => ['Siswa', 'Jenis sesi', 'Tanggal', 'Catatan sesi', 'Tindak lanjut'],
                    'action' => 'Simpan Sesi Simulasi',
                    'role_note' => 'Guru BK dapat menjadwalkan, mencatat, dan menutup sesi konseling.',
                ],
                'wali-kelas' => [
                    'steps' => ['Wali kelas memilih siswa', 'Mengisi alasan rekomendasi', 'Mengirim ke Guru BK', 'Memantau status jadwal'],
                    'form' => ['Siswa kelas', 'Alasan rekomendasi', 'Urgensi', 'Catatan wali kelas'],
                    'action' => 'Ajukan Rekomendasi Simulasi',
                    'role_note' => 'Wali kelas mengusulkan sesi untuk siswa kelas binaan dan memantau statusnya.',
                ],
                'siswa' => [
                    'steps' => ['Siswa memilih jenis konseling', 'Memilih tanggal yang tersedia', 'Menulis kebutuhan awal', 'Menunggu konfirmasi Guru BK'],
                    'records' => [
                        ['Jenis' => 'Akademik', 'Jadwal' => '23 Mei 2026 10.00', 'Status' => 'Menunggu konfirmasi'],
                        ['Jenis' => 'Karier', 'Jadwal' => '24 Mei 2026 08.30', 'Status' => 'Disetujui'],
                    ],
                    'form' => ['Jenis konseling', 'Tanggal pilihan', 'Topik yang ingin dibahas', 'Catatan untuk Guru BK'],
                    'action' => 'Ajukan Jadwal Simulasi',
                    'role_note' => 'Siswa hanya mengajukan jadwal dan melihat status sesi miliknya.',
                ],
                'orang-tua' => [
                    'steps' => ['Orang tua memilih anak', 'Mengisi kebutuhan konsultasi', 'Memilih tanggal', 'Menunggu konfirmasi Guru BK'],
                    'records' => [
                        ['Anak' => 'Nadia Azzahra', 'Jenis' => 'Konsultasi orang tua', 'Jadwal' => '22 Mei 2026 09.00', 'Status' => 'Terjadwal'],
                        ['Anak' => 'Nadia Azzahra', 'Jenis' => 'Perkembangan belajar', 'Jadwal' => '25 Mei 2026 13.00', 'Status' => 'Menunggu'],
                    ],
                    'form' => ['Anak', 'Jenis konsultasi', 'Tanggal pilihan', 'Catatan orang tua'],
                    'action' => 'Ajukan Konsultasi Simulasi',
                    'role_note' => 'Orang tua hanya mengajukan dan memantau sesi terkait anaknya.',
                ],
            ],
            'reports' => [
                'koordinator-bk' => [
                    'form' => ['Jenis laporan agregat', 'Periode awal', 'Periode akhir', 'Kelas/Guru BK', 'Format'],
                    'action' => 'Preview Laporan Agregat Simulasi',
                    'role_note' => 'Koordinator BK melihat laporan agregat lintas kelas dan Guru BK.',
                ],
                'guru-bk' => [
                    'form' => ['Jenis laporan individual', 'Periode awal', 'Periode akhir', 'Siswa binaan', 'Format'],
                    'action' => 'Preview Laporan Siswa Simulasi',
                    'role_note' => 'Guru BK melihat laporan siswa binaan dan sesi yang ditangani.',
                ],
                'wali-kelas' => [
                    'form' => ['Jenis laporan kelas', 'Periode awal', 'Periode akhir', 'Siswa kelas', 'Format'],
                    'action' => 'Preview Laporan Kelas Simulasi',
                    'role_note' => 'Wali kelas melihat laporan kelas binaan dan siswa di kelasnya.',
                ],
                'orang-tua' => [
                    'metrics' => [['label' => 'Anak', 'value' => '1'], ['label' => 'Format', 'value' => 'PDF'], ['label' => 'Preview', 'value' => 'Aktif']],
                    'steps' => ['Orang tua memilih anak', 'Memilih periode laporan', 'Sistem menampilkan ringkasan', 'Laporan dapat dicetak'],
                    'records' => [
                        ['Laporan' => 'Ringkasan anak', 'Scope' => 'Individual', 'Status' => 'Siap preview'],
                        ['Laporan' => 'Riwayat sesi konseling', 'Scope' => 'Anak', 'Status' => 'Siap cetak'],
                    ],
                    'form' => ['Anak', 'Periode awal', 'Periode akhir', 'Jenis laporan', 'Format'],
                    'action' => 'Preview Laporan Anak Simulasi',
                    'role_note' => 'Orang tua hanya melihat laporan untuk anak yang terhubung.',
                ],
            ],
            'dashboard' => [
                'koordinator-bk' => [
                    'metrics' => [['label' => 'Siswa', 'value' => '620'], ['label' => 'Sesi bulan ini', 'value' => '38'], ['label' => 'Tindak lanjut', 'value' => '7']],
                    'records' => [
                        ['Widget' => 'Cakupan siswa', 'Isi' => 'Total dan status siswa aktif', 'Role' => 'Koordinator BK'],
                        ['Widget' => 'Beban Guru BK', 'Isi' => 'Jumlah sesi dan tindak lanjut', 'Role' => 'Koordinator BK'],
                        ['Widget' => 'Prioritas layanan', 'Isi' => 'Siswa perlu perhatian', 'Role' => 'Koordinator BK'],
                    ],
                    'role_note' => 'Dashboard Koordinator BK menekankan monitoring lintas kelas dan beban layanan.',
                ],
                'guru-bk' => [
                    'metrics' => [['label' => 'Siswa binaan', 'value' => '186'], ['label' => 'Sesi hari ini', 'value' => '4'], ['label' => 'Tindak lanjut', 'value' => '5']],
                    'records' => [
                        ['Widget' => 'Sesi hari ini', 'Isi' => 'Jadwal konseling', 'Role' => 'Guru BK'],
                        ['Widget' => 'Tindak lanjut aktif', 'Isi' => 'Daftar perlu tindak lanjut', 'Role' => 'Guru BK'],
                        ['Widget' => 'Perlu verifikasi', 'Isi' => 'Antrian tindak lanjut', 'Role' => 'Guru BK'],
                    ],
                    'role_note' => 'Dashboard Guru BK menekankan pekerjaan harian dan tindak lanjut siswa.',
                ],
                'wali-kelas' => [
                    'metrics' => [['label' => 'Siswa kelas', 'value' => '32'], ['label' => 'Catatan baru', 'value' => '3'], ['label' => 'Sesi terjadwal', 'value' => '2']],
                    'records' => [
                        ['Widget' => 'Kelas binaan', 'Isi' => 'Ringkasan siswa kelas', 'Role' => 'Wali Kelas'],
                        ['Widget' => 'Catatan BK', 'Isi' => 'Info perlu koordinasi', 'Role' => 'Wali Kelas'],
                        ['Widget' => 'Catatan kelas', 'Isi' => 'Info perlu koordinasi', 'Role' => 'Wali Kelas'],
                    ],
                    'role_note' => 'Dashboard Wali Kelas menampilkan kelas binaan dan koordinasi dengan Guru BK.',
                ],
                'siswa' => [
                    'metrics' => [['label' => 'Jadwal konseling', 'value' => '1'], ['label' => 'Catatan BK', 'value' => '10'], ['label' => 'Notifikasi', 'value' => '2']],
                    'records' => [
                        ['Widget' => 'Profil saya', 'Isi' => 'Status data siswa', 'Role' => 'Siswa'],
                        ['Widget' => 'Jadwal saya', 'Isi' => 'Sesi konseling', 'Role' => 'Siswa'],
                        ['Widget' => 'Riwayat saya', 'Isi' => 'Sesi dan tindak lanjut', 'Role' => 'Siswa'],
                    ],
                    'form' => ['Rentang waktu', 'Prioritas widget'],
                    'role_note' => 'Dashboard Siswa hanya menampilkan data miliknya.',
                ],
                'orang-tua' => [
                    'metrics' => [['label' => 'Anak terhubung', 'value' => '1'], ['label' => 'Laporan baru', 'value' => '2'], ['label' => 'Jadwal konsultasi', 'value' => '1']],
                    'records' => [
                        ['Widget' => 'Ringkasan anak', 'Isi' => 'Laporan dan notifikasi', 'Role' => 'Orang Tua'],
                        ['Widget' => 'Jadwal konsultasi', 'Isi' => 'Sesi orang tua', 'Role' => 'Orang Tua'],
                        ['Widget' => 'Tindak lanjut', 'Isi' => 'Catatan Guru BK', 'Role' => 'Orang Tua'],
                    ],
                    'form' => ['Anak', 'Rentang waktu', 'Prioritas widget'],
                    'role_note' => 'Dashboard Orang Tua hanya menampilkan data anak yang terhubung.',
                ],
            ],
        ];
    }

    private function demoData(): array
    {
        return [
            'login' => [
                'metrics' => [['label' => 'Role tujuan', 'value' => 'Non-admin'], ['label' => 'Status akun', 'value' => 'Aktif'], ['label' => 'Redirect', 'value' => 'Dashboard']],
                'steps' => ['Masukkan username/email', 'Validasi password dan status akun', 'Muat role dan permission', 'Redirect ke dashboard role'],
                'records' => [],
                'form' => ['Username/Email', 'Password'],
                'action' => 'Coba Login Simulasi',
            ],
            'profile-password' => [
                'metrics' => [['label' => 'Data profil', 'value' => '5'], ['label' => 'Validasi password', 'value' => '3'], ['label' => 'Foto profil', 'value' => 'Opsional']],
                'steps' => ['Pengguna membuka profil', 'Memperbarui kontak/foto', 'Mengisi password lama dan baru', 'Sistem menyimpan perubahan valid'],
                'records' => [
                    ['Field' => 'Nama', 'Contoh' => 'Ahmad Fajar Nugraha', 'Status' => 'Dapat diperbarui'],
                    ['Field' => 'Email', 'Contoh' => 'ahmad.fajar@student.sch.id', 'Status' => 'Dapat diperbarui'],
                    ['Field' => 'Password', 'Contoh' => 'Minimal 6 karakter', 'Status' => 'Butuh konfirmasi'],
                ],
                'form' => ['Nama lengkap', 'Email', 'Nomor telepon', 'Password lama', 'Password baru'],
                'action' => 'Simpan Profil Simulasi',
            ],
            'students' => [
                'metrics' => [['label' => 'Siswa aktif', 'value' => '620'], ['label' => 'Kelas', 'value' => '18'], ['label' => 'Profil lengkap', 'value' => '87%']],
                'steps' => ['Mencari siswa', 'Membuka profil siswa', 'Memeriksa riwayat pendampingan', 'Menentukan tindak lanjut sesuai kewenangan'],
                'records' => [
                    ['NISN' => '0123456789', 'Nama' => 'Ahmad Fajar Nugraha', 'Kelas' => 'XI MIPA 2', 'Catatan BK' => 'Perlu follow-up'],
                    ['NISN' => '0123456790', 'Nama' => 'Putri Amanda Sari', 'Kelas' => 'XI IPS 1', 'Catatan BK' => 'Stabil'],
                    ['NISN' => '0123456791', 'Nama' => 'Nadia Azzahra', 'Kelas' => 'XII MIPA 1', 'Catatan BK' => 'Pantau progres'],
                ],
                'form' => ['NISN/Nama siswa', 'Kelas', 'Filter status', 'Catatan'],
                'action' => 'Tinjau Siswa Simulasi',
            ],
            'student-import' => [
                'metrics' => [['label' => 'File contoh', 'value' => 'XLSX'], ['label' => 'Baris valid', 'value' => '48'], ['label' => 'Perlu koreksi', 'value' => '2']],
                'steps' => ['Koordinator mengunduh template', 'Mengunggah file siswa', 'Sistem memvalidasi NISN/NIK/email/kelas', 'Koordinator mengonfirmasi impor'],
                'records' => [
                    ['Baris' => '2', 'Nama' => 'Ahmad Fajar Nugraha', 'Status' => 'Valid'],
                    ['Baris' => '3', 'Nama' => 'Putri Amanda Sari', 'Status' => 'Valid'],
                    ['Baris' => '4', 'Nama' => 'Data Tanpa Kelas', 'Status' => 'Perlu koreksi'],
                ],
                'form' => ['Pilih file Excel', 'Tahun akademik', 'Mode impor', 'Kirim notifikasi akun'],
                'action' => 'Validasi File Simulasi',
            ],
            'counseling-sessions' => [
                'metrics' => [['label' => 'Sesi minggu ini', 'value' => '14'], ['label' => 'Menunggu', 'value' => '3'], ['label' => 'Selesai', 'value' => '9']],
                'steps' => ['Pengajuan jadwal dibuat', 'Guru BK menyetujui waktu', 'Sesi dicatat', 'Tindak lanjut dipantau'],
                'records' => [
                    ['Siswa' => 'Nadia Azzahra', 'Jenis' => 'Individual', 'Jadwal' => '22 Mei 2026 09.00', 'Status' => 'Terjadwal'],
                    ['Siswa' => 'Ahmad Fajar', 'Jenis' => 'Akademik', 'Jadwal' => '23 Mei 2026 10.00', 'Status' => 'Menunggu'],
                    ['Siswa' => 'Putri Amanda', 'Jenis' => 'Karier', 'Jadwal' => '24 Mei 2026 08.30', 'Status' => 'Selesai'],
                ],
                'form' => ['Siswa', 'Guru BK', 'Jenis sesi', 'Tanggal', 'Catatan awal'],
                'action' => 'Jadwalkan Sesi Simulasi',
            ],
            'reports' => [
                'metrics' => [['label' => 'Jenis laporan', 'value' => '6'], ['label' => 'Format', 'value' => 'PDF'], ['label' => 'Preview', 'value' => 'Aktif']],
                'steps' => ['Pengguna memilih jenis laporan', 'Filter periode/siswa/kelas diisi', 'Sistem menampilkan pratinjau', 'Laporan diunduh atau dicetak'],
                'records' => [
                    ['Laporan' => 'Individu siswa', 'Scope' => 'Individual', 'Status' => 'Siap preview'],
                    ['Laporan' => 'Rekap kelas', 'Scope' => 'Agregat', 'Status' => 'Siap preview'],
                    ['Laporan' => 'Sesi konseling', 'Scope' => 'Periode', 'Status' => 'Siap ekspor'],
                ],
                'form' => ['Jenis laporan', 'Periode awal', 'Periode akhir', 'Kelas/siswa', 'Format'],
                'action' => 'Tampilkan Preview Simulasi',
            ],
            'dashboard' => [
                'metrics' => [['label' => 'Siswa', 'value' => '620'], ['label' => 'Sesi bulan ini', 'value' => '38'], ['label' => 'Tindak lanjut', 'value' => '7']],
                'steps' => ['User login', 'Dashboard memuat statistik sesuai role', 'Aktivitas terbaru ditampilkan', 'Shortcut mengarahkan ke fitur utama'],
                'records' => [
                    ['Widget' => 'Statistik siswa', 'Isi' => 'Total dan siswa aktif', 'Role' => 'Koordinator'],
                    ['Widget' => 'Sesi hari ini', 'Isi' => 'Jadwal konseling', 'Role' => 'Guru BK'],
                    ['Widget' => 'Ringkasan anak', 'Isi' => 'Laporan dan notifikasi', 'Role' => 'Orang Tua'],
                ],
                'form' => ['Rentang waktu', 'Prioritas widget'],
                'action' => 'Muat Dashboard Simulasi',
            ],
        ];
    }
}
