<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class PrototypeController extends BaseController
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

        return view('prototype/index', [
            'title'       => 'Prototipe Fitur Skripsi',
            'features'    => $features,
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'startUrl'    => $this->startUrl($features, $roleMode),
            'stats'       => [
                ['label' => 'Fitur untuk role ini', 'value' => count($features), 'icon' => 'mdi mdi-shape-plus', 'tone' => 'primary'],
                ['label' => 'Mode peran', 'value' => $this->roleLabel($roleMode), 'icon' => 'mdi mdi-account-switch', 'tone' => 'success'],
                ['label' => 'Alur simulasi', 'value' => 12, 'icon' => 'mdi mdi-source-branch', 'tone' => 'info'],
                ['label' => 'Butuh data asli', 'value' => 0, 'icon' => 'mdi mdi-database-off', 'tone' => 'warning'],
            ],
        ]);
    }

    public function violationSubmissions()
    {
        return $this->showFeature('violation-submissions');
    }

    public function notifications()
    {
        return $this->showFeature('notifications');
    }

    public function messages()
    {
        return $this->showFeature('messages');
    }

    public function assessments()
    {
        return $this->showFeature('assessments');
    }

    public function career()
    {
        return $this->showFeature('career');
    }

    private function showFeature(string $key)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $allFeatures = $this->features();
        $features = $this->featuresForRole($roleMode);
        $progress = $this->progressForRole($roleMode);

        if (! isset($allFeatures[$key])) {
            throw PageNotFoundException::forPageNotFound('Prototipe tidak ditemukan.');
        }

        if (! isset($features[$key])) {
            return redirect()->to($this->urlWithRole('prototype', $roleMode))
                ->with('info', 'Prototipe tersebut tidak tersedia untuk mode peran ' . $this->roleLabel($roleMode) . '.');
        }

        if (! $this->isFeatureUnlocked($key, $features, $progress)) {
            return redirect()->to($this->firstPendingUrl($features, $roleMode, $progress))
                ->with('info', 'Selesaikan prototipe sebelumnya terlebih dahulu agar alurnya tidak terlewat.');
        }

        $features = $this->annotateFeatures($features, $roleMode, $progress);

        return view('prototype/feature', [
            'title'       => $features[$key]['title'],
            'activeKey'   => $key,
            'feature'     => $features[$key],
            'features'    => $features,
            'demo'        => $this->demoForRole($key, $roleMode),
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'isTried'     => in_array($key, $progress, true),
            'progressUrl' => $this->urlWithRole('prototype/progress/' . $key, $roleMode),
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
                'message' => 'Prototipe tidak tersedia untuk peran ini.',
            ]);
        }

        if (! $this->isFeatureUnlocked($key, $features, $progress)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Prototipe sebelumnya belum dicoba.',
            ]);
        }

        $progress[] = $key;
        $progress = array_values(array_unique($progress));
        session()->set($this->progressSessionKey($roleMode), $progress);

        $features = $this->annotateFeatures($features, $roleMode, $progress);
        $next = $this->nextFeature($key, $features);

        return $this->response->setJSON([
            'ok' => true,
            'next_url' => $next ? base_url($next['url']) : base_url($this->urlWithRole('simulation', $roleMode)),
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
            ->with('error', 'Akses prototipe/simulasi belum diberikan oleh admin.');
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
        return 'prototype_progress_' . $role;
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
                return base_url($this->urlWithRole((string) ($feature['url'] ?? 'prototype'), $role));
            }
        }

        $first = reset($features) ?: ['url' => 'prototype'];
        return base_url($this->urlWithRole((string) ($first['url'] ?? 'prototype'), $role));
    }

    private function startUrl(array $features, string $role): string
    {
        foreach ($features as $feature) {
            if (empty($feature['is_tried'])) {
                return (string) ($feature['url'] ?? $this->urlWithRole('prototype', $role));
            }
        }

        $first = reset($features) ?: ['url' => $this->urlWithRole('prototype', $role)];
        return (string) ($first['url'] ?? $this->urlWithRole('prototype', $role));
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
            'violation-submissions' => [
                'title'       => 'Pelaporan/Pengaduan Pelanggaran',
                'short_title' => 'Pengaduan',
                'url'         => 'prototype/violation-submissions',
                'icon'        => 'mdi mdi-message-alert',
                'tone'        => 'danger',
                'roles'       => 'Siswa, Orang Tua, Wali Kelas, Guru BK, Koordinator BK',
                'role_modes'  => ['koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome'     => 'Pengaduan dapat dikirim, ditinjau, diterima/ditolak, lalu dikonversi menjadi kasus pelanggaran.',
            ],
            'notifications' => [
                'title'       => 'Notifikasi Internal',
                'short_title' => 'Notifikasi',
                'url'         => 'prototype/notifications',
                'icon'        => 'mdi mdi-bell-ring',
                'tone'        => 'warning',
                'roles'       => 'Pengguna non-admin',
                'role_modes'  => ['all'],
                'outcome'     => 'Pengguna menerima pengingat, status tindak lanjut, dan pemberitahuan perubahan data BK.',
            ],
            'messages' => [
                'title'       => 'Pesan Internal',
                'short_title' => 'Pesan',
                'url'         => 'prototype/messages',
                'icon'        => 'mdi mdi-email-multiple',
                'tone'        => 'primary',
                'roles'       => 'Koordinator BK, Guru BK, Wali Kelas, Siswa, Orang Tua',
                'role_modes'  => ['koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome'     => 'Komunikasi tercatat dalam percakapan internal sehingga tindak lanjut BK lebih mudah dilacak.',
            ],
            'assessments' => [
                'title'       => 'Asesmen',
                'short_title' => 'Asesmen',
                'url'         => 'prototype/assessments',
                'icon'        => 'mdi mdi-clipboard-check',
                'tone'        => 'success',
                'roles'       => 'Guru BK, Koordinator BK, Siswa',
                'role_modes'  => ['koordinator-bk', 'guru-bk', 'siswa'],
                'outcome'     => 'Guru BK menugaskan asesmen, siswa mengisi, sistem menampilkan ringkasan hasil dan rekomendasi awal.',
            ],
            'career' => [
                'title'       => 'Info Karier dan Kuliah',
                'short_title' => 'Karier/Kuliah',
                'url'         => 'prototype/career',
                'icon'        => 'mdi mdi-school-outline',
                'tone'        => 'info',
                'roles'       => 'Siswa, Orang Tua, Wali Kelas, Guru BK',
                'role_modes'  => ['guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome'     => 'Referensi karier/kuliah dikelola Guru BK, siswa menyimpan pilihan, dan pihak pendamping melihat arah minat.',
            ],
        ];
    }

    private function demoForRole(string $key, string $role): array
    {
        return array_replace($this->demoData()[$key] ?? [], $this->roleDemoOverrides()[$key][$role] ?? [], [
            'role_label' => $this->roleLabel($role),
            'role_mode'  => $role,
        ]);
    }

    private function roleDemoOverrides(): array
    {
        return [
            'violation-submissions' => [
                'koordinator-bk' => [
                    'can_submit' => false,
                    'can_review' => true,
                    'role_note' => 'Koordinator BK meninjau antrian pengaduan dan memantau konversi menjadi kasus.',
                ],
                'guru-bk' => [
                    'can_submit' => false,
                    'can_review' => true,
                    'role_note' => 'Guru BK memverifikasi, menerima/menolak, dan mengonversi pengaduan menjadi kasus.',
                ],
                'wali-kelas' => [
                    'can_submit' => true,
                    'can_review' => false,
                    'role_note' => 'Wali kelas membuat pengaduan untuk siswa kelas binaan dan memantau statusnya.',
                    'submissions' => [
                        ['id' => 'PGD-2026-003', 'reporter' => 'Pak Aditya', 'role' => 'Wali Kelas', 'subject' => 'Kelompok XI IPS 1', 'category' => 'Perundungan verbal', 'date' => '18 Mei 2026', 'status' => 'Dikonversi'],
                        ['id' => 'PGD-2026-006', 'reporter' => 'Pak Aditya', 'role' => 'Wali Kelas', 'subject' => 'Rafi Maulana', 'category' => 'Ketertiban', 'date' => '21 Mei 2026', 'status' => 'Ditinjau'],
                    ],
                ],
                'siswa' => [
                    'can_submit' => true,
                    'can_review' => false,
                    'role_note' => 'Siswa mengirim pengaduan dan hanya melihat status pengaduan yang dibuatnya.',
                    'submissions' => [
                        ['id' => 'PGD-2026-001', 'reporter' => 'Nadia Azzahra', 'role' => 'Siswa', 'subject' => 'Rafi Maulana - XI MIPA 2', 'category' => 'Ketertiban', 'date' => '20 Mei 2026', 'status' => 'Diajukan'],
                        ['id' => 'PGD-2026-004', 'reporter' => 'Nadia Azzahra', 'role' => 'Siswa', 'subject' => 'Kejadian di kelas', 'category' => 'Kedisiplinan', 'date' => '21 Mei 2026', 'status' => 'Ditinjau'],
                    ],
                ],
                'orang-tua' => [
                    'can_submit' => true,
                    'can_review' => false,
                    'role_note' => 'Orang tua mengirim pengaduan terkait anaknya dan melihat perkembangan status.',
                    'submissions' => [
                        ['id' => 'PGD-2026-002', 'reporter' => 'Ibu Siti Rahma', 'role' => 'Orang Tua', 'subject' => 'Siswa sekitar gerbang', 'category' => 'Kedisiplinan', 'date' => '19 Mei 2026', 'status' => 'Ditinjau'],
                    ],
                ],
            ],
            'notifications' => [
                'koordinator-bk' => [
                    'can_send' => true,
                    'targets' => ['Guru BK', 'Wali Kelas', 'Orang Tua'],
                    'role_note' => 'Koordinator BK dapat mengirim notifikasi koordinasi dan melihat notifikasi sistem.',
                ],
                'guru-bk' => [
                    'can_send' => true,
                    'targets' => ['Siswa', 'Orang Tua', 'Wali Kelas'],
                    'role_note' => 'Guru BK dapat mengirim pengingat tindak lanjut kepada siswa, orang tua, atau wali kelas.',
                ],
                'wali-kelas' => [
                    'can_send' => true,
                    'targets' => ['Guru BK', 'Orang Tua'],
                    'role_note' => 'Wali kelas dapat mengirim notifikasi koordinasi kelas.',
                ],
                'siswa' => [
                    'can_send' => false,
                    'role_note' => 'Siswa menerima notifikasi dan menandainya sudah dibaca.',
                ],
                'orang-tua' => [
                    'can_send' => false,
                    'role_note' => 'Orang tua menerima notifikasi perkembangan anak.',
                ],
            ],
            'messages' => [
                'koordinator-bk' => [
                    'recipients' => ['Guru BK', 'Wali Kelas'],
                    'role_note' => 'Koordinator BK berkomunikasi dengan petugas layanan dan wali kelas.',
                ],
                'guru-bk' => [
                    'recipients' => ['Siswa', 'Orang Tua', 'Wali Kelas', 'Koordinator BK'],
                    'role_note' => 'Guru BK dapat memulai percakapan tindak lanjut dengan siswa, orang tua, dan wali kelas.',
                ],
                'wali-kelas' => [
                    'recipients' => ['Guru BK', 'Orang Tua', 'Koordinator BK'],
                    'role_note' => 'Wali kelas memakai pesan untuk koordinasi siswa kelas binaan.',
                ],
                'siswa' => [
                    'recipients' => ['Guru BK', 'Wali Kelas'],
                    'role_note' => 'Siswa dapat menghubungi Guru BK atau wali kelas.',
                ],
                'orang-tua' => [
                    'recipients' => ['Guru BK', 'Wali Kelas'],
                    'role_note' => 'Orang tua dapat menghubungi Guru BK atau wali kelas terkait anaknya.',
                ],
            ],
            'assessments' => [
                'koordinator-bk' => [
                    'can_assign' => false,
                    'can_answer' => false,
                    'can_review' => true,
                    'role_note' => 'Koordinator BK memantau progres asesmen dan ringkasan hasil lintas kelas.',
                ],
                'guru-bk' => [
                    'can_assign' => true,
                    'can_answer' => false,
                    'can_review' => true,
                    'role_note' => 'Guru BK menugaskan asesmen dan membaca ringkasan hasil siswa.',
                ],
                'siswa' => [
                    'can_assign' => false,
                    'can_answer' => true,
                    'can_review' => false,
                    'role_note' => 'Siswa mengisi asesmen yang ditugaskan dan melihat status pengisiannya.',
                    'assessments' => [
                        ['name' => 'Asesmen Minat Belajar', 'target' => 'Untuk saya', 'status' => 'Aktif', 'progress' => 0],
                        ['name' => 'Screening Kesiapan Karier', 'target' => 'Untuk saya', 'status' => 'Menunggu', 'progress' => 0],
                    ],
                ],
            ],
            'career' => [
                'guru-bk' => [
                    'can_manage' => true,
                    'can_save' => false,
                    'can_delete' => false,
                    'role_note' => 'Guru BK menambahkan referensi karier/kuliah dan melihat pilihan yang disimpan siswa.',
                    'saved_by_students' => [
                        ['student' => 'Nadia Azzahra', 'class' => 'XI MIPA 2', 'choice' => 'Bimbingan dan Konseling', 'type' => 'Program Studi'],
                        ['student' => 'Ahmad Fajar', 'class' => 'XI MIPA 2', 'choice' => 'Analis Data Pendidikan', 'type' => 'Karier'],
                        ['student' => 'Putri Amanda', 'class' => 'XI IPS 1', 'choice' => 'Teknologi Informasi', 'type' => 'Program Studi'],
                    ],
                ],
                'siswa' => [
                    'can_manage' => false,
                    'can_save' => true,
                    'can_delete' => true,
                    'role_note' => 'Siswa melihat referensi, menyimpan pilihan, dan menghapus pilihan yang tidak lagi diminati.',
                    'saved' => ['Bimbingan dan Konseling', 'Teknologi Informasi'],
                ],
                'wali-kelas' => [
                    'can_manage' => false,
                    'can_save' => false,
                    'can_delete' => false,
                    'role_note' => 'Wali kelas melihat referensi dan ringkasan minat siswa kelas binaan.',
                    'saved_by_students' => [
                        ['student' => 'Nadia Azzahra', 'class' => 'XI MIPA 2', 'choice' => 'Bimbingan dan Konseling', 'type' => 'Program Studi'],
                        ['student' => 'Ahmad Fajar', 'class' => 'XI MIPA 2', 'choice' => 'Analis Data Pendidikan', 'type' => 'Karier'],
                    ],
                ],
                'orang-tua' => [
                    'can_manage' => false,
                    'can_save' => false,
                    'can_delete' => false,
                    'role_note' => 'Orang tua melihat referensi dan pilihan karier/kuliah yang disimpan anak.',
                    'saved' => ['Bimbingan dan Konseling', 'Teknologi Informasi'],
                ],
            ],
        ];
    }

    private function demoData(): array
    {
        return [
            'violation-submissions' => [
                'can_submit' => true,
                'can_review' => true,
                'submissions' => [
                    ['id' => 'PGD-2026-001', 'reporter' => 'Nadia Azzahra', 'role' => 'Siswa', 'subject' => 'Rafi Maulana - XI MIPA 2', 'category' => 'Ketertiban', 'date' => '20 Mei 2026', 'status' => 'Diajukan'],
                    ['id' => 'PGD-2026-002', 'reporter' => 'Ibu Siti Rahma', 'role' => 'Orang Tua', 'subject' => 'Siswa sekitar gerbang', 'category' => 'Kedisiplinan', 'date' => '19 Mei 2026', 'status' => 'Ditinjau'],
                    ['id' => 'PGD-2026-003', 'reporter' => 'Pak Aditya', 'role' => 'Wali Kelas', 'subject' => 'Kelompok XI IPS 1', 'category' => 'Perundungan verbal', 'date' => '18 Mei 2026', 'status' => 'Dikonversi'],
                ],
                'timeline' => ['Diajukan', 'Ditinjau', 'Diterima', 'Dikonversi'],
            ],
            'notifications' => [
                'can_send' => false,
                'targets' => ['Guru BK', 'Wali Kelas', 'Siswa', 'Orang Tua'],
                'items' => [
                    ['title' => 'Pengaduan baru masuk', 'body' => 'PGD-2026-001 menunggu verifikasi Guru BK.', 'time' => '10 menit lalu', 'status' => 'Belum dibaca'],
                    ['title' => 'Jadwal konseling berubah', 'body' => 'Sesi Nadia dipindah ke Jumat, 22 Mei 2026 pukul 09.00.', 'time' => '1 jam lalu', 'status' => 'Belum dibaca'],
                    ['title' => 'Hasil asesmen tersedia', 'body' => 'Ringkasan asesmen minat bakat sudah dapat ditinjau.', 'time' => 'Kemarin', 'status' => 'Dibaca'],
                ],
            ],
            'messages' => [
                'threads' => [
                    ['from' => 'Guru BK', 'subject' => 'Tindak lanjut pengaduan PGD-2026-001', 'snippet' => 'Mohon konfirmasi waktu kejadian dan saksi yang melihat.', 'time' => '09.30', 'unread' => true],
                    ['from' => 'Orang Tua Nadia', 'subject' => 'Permohonan jadwal konsultasi', 'snippet' => 'Kami ingin berdiskusi terkait perkembangan anak.', 'time' => 'Kemarin', 'unread' => false],
                    ['from' => 'Wali Kelas XI MIPA 2', 'subject' => 'Koordinasi kelas', 'snippet' => 'Ada beberapa siswa yang perlu dipantau minggu ini.', 'time' => '17 Mei', 'unread' => false],
                ],
                'recipients' => ['Guru BK', 'Wali Kelas', 'Orang Tua', 'Koordinator BK'],
            ],
            'assessments' => [
                'can_assign' => true,
                'can_answer' => true,
                'can_review' => true,
                'assessments' => [
                    ['name' => 'Asesmen Minat Belajar', 'target' => 'Kelas XI', 'status' => 'Aktif', 'progress' => 62],
                    ['name' => 'Screening Kesiapan Karier', 'target' => 'Kelas XII', 'status' => 'Draft', 'progress' => 20],
                    ['name' => 'Skala Kesejahteraan Siswa', 'target' => 'Semua siswa', 'status' => 'Selesai', 'progress' => 100],
                ],
                'questions' => [
                    'Saya merasa mudah berkonsentrasi saat belajar.',
                    'Saya mengetahui bidang karier yang ingin saya tekuni.',
                    'Saya nyaman meminta bantuan saat menghadapi masalah.',
                ],
                'results' => [
                    ['label' => 'Minat akademik', 'score' => 78],
                    ['label' => 'Kesiapan karier', 'score' => 66],
                    ['label' => 'Kebutuhan pendampingan', 'score' => 42],
                ],
            ],
            'career' => [
                'can_manage' => false,
                'can_save' => true,
                'can_delete' => true,
                'careers' => [
                    ['name' => 'Konselor Pendidikan', 'cluster' => 'Sosial Humaniora', 'match' => 86],
                    ['name' => 'Analis Data Pendidikan', 'cluster' => 'Teknologi', 'match' => 78],
                    ['name' => 'Perawat', 'cluster' => 'Kesehatan', 'match' => 71],
                ],
                'universities' => [
                    ['name' => 'UIN Sunan Gunung Djati', 'program' => 'Bimbingan Konseling Islam', 'city' => 'Bandung'],
                    ['name' => 'Universitas Pendidikan Indonesia', 'program' => 'Bimbingan dan Konseling', 'city' => 'Bandung'],
                    ['name' => 'Politeknik Negeri Bandung', 'program' => 'Teknologi Informasi', 'city' => 'Bandung'],
                ],
                'saved' => ['Bimbingan dan Konseling', 'Teknologi Informasi'],
                'saved_by_students' => [],
            ],
        ];
    }
}
