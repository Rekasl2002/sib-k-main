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
        $featureSections = $this->prototypeFeatureSections($roleMode);
        $features = $this->flattenPrototypeSections($featureSections);

        return view('prototype/index', [
            'title'       => 'Prototipe Pengembangan SIB-K',
            'features'    => $features,
            'featureSections' => $featureSections,
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'stats'       => [
                ['label' => 'Halaman bisa dicek', 'value' => $this->flowPageCountForRole($roleMode), 'icon' => 'mdi mdi-monitor-dashboard', 'tone' => 'primary'],
                ['label' => 'Fitur inti', 'value' => 11, 'icon' => 'mdi mdi-shape-plus', 'tone' => 'success'],
                ['label' => 'Fitur diperbarui', 'value' => 3, 'icon' => 'mdi mdi-tools', 'tone' => 'info'],
                ['label' => 'Dihapus/digantikan', 'value' => 3, 'icon' => 'mdi mdi-archive-remove-outline', 'tone' => 'warning'],
            ],
            'roleSummary' => $this->roleAccessSummary($roleMode),
            'flowPageCount' => $this->flowPageCountForRole($roleMode),
            'flowStartUrl' => $this->firstFlowUrl($roleMode),
            'crudDiagramUrl' => $this->crudDiagramUrl(),
        ]);
    }

    public function feature(string $key)
    {
        return $this->showFeature($key);
    }

    public function violationSubmissions()
    {
        return $this->showFeature('consultation');
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
        return $this->showFeature('career-study');
    }

    public function flow(string $featureKey, string $pageKey = '')
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $features = $this->flowFeaturesForRole($roleMode);

        if (! isset($features[$featureKey])) {
            return redirect()->to($this->urlWithRole('prototype', $roleMode))
                ->with('info', 'Halaman tersebut tidak tersedia untuk sudut pandang ' . $this->roleLabel($roleMode) . '.');
        }

        $feature = $features[$featureKey];
        $pageKey = $pageKey !== '' ? $pageKey : (string) array_key_first($feature['pages']);

        if (! isset($feature['pages'][$pageKey])) {
            throw PageNotFoundException::forPageNotFound('Halaman prototipe tidak ditemukan.');
        }

        $page = $this->decorateFlowPage($featureKey, $pageKey, $feature['pages'][$pageKey], $roleMode);
        $sequence = $this->flowSequence($features);
        $currentIndex = 0;

        foreach ($sequence as $index => $item) {
            if ($item['feature_key'] === $featureKey && $item['page_key'] === $pageKey) {
                $currentIndex = $index;
                break;
            }
        }

        return view('prototype/flow_page', [
            'title'       => $page['title'] . ' - ' . $feature['title'],
            'features'    => $features,
            'featureKey'  => $featureKey,
            'feature'     => $feature,
            'pageKey'     => $pageKey,
            'page'        => $page,
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'roleSummary' => $this->roleAccessSummary($roleMode),
            'flowCurrentNumber' => $currentIndex + 1,
            'flowTotal' => count($sequence),
            'globalPreviousPage' => $sequence[$currentIndex - 1] ?? null,
            'globalNextPage' => $sequence[$currentIndex + 1] ?? null,
            'demoStartUrl' => $this->urlWithRole('prototype/demo/' . $featureKey, $roleMode),
            'demoScreens' => $this->finalDemoFeature($featureKey, $roleMode)['screens'] ?? [],
            'diagramImages' => $this->diagramImagesForFeature($featureKey),
        ]);
    }

    public function demo(string $featureKey, string $screenKey = '', ?int $id = null)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $features = $this->flowFeaturesForRole($roleMode);

        if (! isset($features[$featureKey])) {
            return redirect()->to($this->urlWithRole('prototype', $roleMode))
                ->with('info', 'Halaman demo tersebut tidak tersedia untuk sudut pandang ' . $this->roleLabel($roleMode) . '.');
        }

        $demoFeature = $this->finalDemoFeature($featureKey, $roleMode);
        $screens = $demoFeature['screens'] ?? [];
        $screenKey = $screenKey !== '' ? $screenKey : (string) array_key_first($screens);

        if (! isset($screens[$screenKey])) {
            throw PageNotFoundException::forPageNotFound('Halaman demo aplikasi tidak ditemukan.');
        }

        $screen = $this->finalDemoPage($featureKey, $screenKey, $screens[$screenKey], $roleMode, $id ?? 1);

        return view('prototype/app_demo_page', [
            'title'       => $screen['title'] . ' - ' . $demoFeature['title'],
            'features'    => $features,
            'featureKey'  => $featureKey,
            'feature'     => $demoFeature,
            'screenKey'   => $screenKey,
            'screen'      => $screen,
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'canDelete'   => $this->canDeleteForRole($featureKey, $roleMode),
        ]);
    }

    public function diagram(string $type = 'pdf')
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $type = strtolower($type);
        $path = match ($type) {
            'drawio' => ROOTPATH . 'backupNInformasi/diagram/diagram_prototipe_skripsi.drawio',
            default => ROOTPATH . 'backupNInformasi/diagram/diagram_prototipe_skripsi.drawio.pdf',
        };

        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound('File diagram tidak ditemukan.');
        }

        if ($type === 'drawio') {
            return $this->response
                ->setHeader('Content-Type', 'application/xml')
                ->setHeader('Content-Disposition', 'attachment; filename="diagram_prototipe_skripsi.drawio"')
                ->setBody((string) file_get_contents($path));
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="diagram_prototipe_skripsi.drawio.pdf"')
            ->setBody((string) file_get_contents($path));
    }

    public function diagramImage(string $type, string $file)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $type = strtolower(trim($type));
        $folder = match ($type) {
            'activity' => 'activityDiagram',
            'use-case' => 'useCaseDiagram',
            'crud' => 'CRUD',
            default => '',
        };

        if ($folder === '') {
            throw PageNotFoundException::forPageNotFound('Jenis diagram tidak ditemukan.');
        }

        $fileName = basename(str_replace('\\', '/', rawurldecode($file)));
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

        if (! in_array($extension, $allowedExtensions, true)) {
            throw PageNotFoundException::forPageNotFound('Format gambar diagram tidak didukung.');
        }

        $baseDir = ROOTPATH . 'backupNInformasi/diagram/gambarDariDrawio/' . $folder;
        $baseRealPath = realpath($baseDir);
        $path = realpath($baseDir . DIRECTORY_SEPARATOR . $fileName);

        if ($baseRealPath === false || $path === false || ! str_starts_with($path, $baseRealPath) || ! is_file($path)) {
            throw PageNotFoundException::forPageNotFound('Gambar diagram tidak ditemukan.');
        }

        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody((string) file_get_contents($path));
    }

    public function studentImportTemplate()
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $path = ROOTPATH . 'backupNInformasi/templateAtauIsiSistem/template_import_siswa_2026-06-10_contoh.xlsx';
        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound('Template impor siswa tidak ditemukan.');
        }

        return $this->response->download($path, null)
            ->setFileName('template_import_siswa_2026-06-10_contoh.xlsx');
    }

    public function progress(string $key)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $features = $this->featuresForRole($roleMode);

        if (! isset($features[$key])) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Prototipe tidak tersedia untuk peran ini.',
            ]);
        }

        $progress = $this->progressForRole($roleMode);
        $progress[] = $key;
        $progress = array_values(array_unique($progress));
        session()->set($this->progressSessionKey($roleMode), $progress);

        return $this->response->setJSON([
            'ok' => true,
            'completed' => $progress,
        ]);
    }

    private function showFeature(string $key)
    {
        if ($guard = $this->guardAccess()) {
            return $guard;
        }

        $roleMode = $this->currentDemoRole();
        $allFeatures = $this->features();
        $features = $this->featuresForRole($roleMode);

        if (! isset($allFeatures[$key])) {
            throw PageNotFoundException::forPageNotFound('Prototipe tidak ditemukan.');
        }

        if (! isset($features[$key])) {
            return redirect()->to($this->urlWithRole('prototype', $roleMode))
                ->with('info', 'Halaman tersebut tidak tersedia untuk sudut pandang ' . $this->roleLabel($roleMode) . '.');
        }

        $features = $this->annotateFeatures($features, $roleMode);

        return view('prototype/feature', [
            'title'       => $features[$key]['title'],
            'activeKey'   => $key,
            'feature'     => $features[$key],
            'features'    => $features,
            'demo'        => $this->demoForRole($key, $roleMode),
            'roleMode'    => $roleMode,
            'roleLabel'   => $this->roleLabel($roleMode),
            'roleOptions' => $this->availableRoleOptions($roleMode),
            'isTried'     => in_array($key, $this->progressForRole($roleMode), true),
            'progressUrl' => $this->urlWithRole('prototype/progress/' . $key, $roleMode),
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
            'admin'          => 'Admin',
            'koordinator-bk' => 'Koordinator BK',
            'guru-bk'        => 'Guru BK',
            'wali-kelas'     => 'Wali Kelas',
            'siswa'          => 'Siswa',
            'orang-tua'      => 'Orang Tua',
        ];
    }

    private function currentDemoRole(): string
    {
        $sessionRole = $this->actualSessionRole();
        $queryRole = $this->normalizeRole((string) $this->request->getGet('role'));

        if ($queryRole !== ''
            && isset($this->roleOptions()[$queryRole])
            && in_array($queryRole, $this->previewableRolesFor($sessionRole), true)
        ) {
            return $queryRole;
        }

        return isset($this->roleOptions()[$sessionRole]) ? $sessionRole : self::DEFAULT_ROLE;
    }

    private function actualSessionRole(): string
    {
        $sessionRole = $this->normalizeRole((string) (session('role_name') ?? ''));
        if ($sessionRole === '') {
            $sessionRole = match ((int) (session('role_id') ?? 0)) {
                1 => 'admin',
                2 => 'koordinator-bk',
                3 => 'guru-bk',
                4 => 'wali-kelas',
                5 => 'siswa',
                6 => 'orang-tua',
                default => '',
            };
        }

        return isset($this->roleOptions()[$sessionRole]) ? $sessionRole : self::DEFAULT_ROLE;
    }

    private function previewableRolesFor(string $role): array
    {
        if ($role === 'admin' || (function_exists('simulation_access_is_admin') && simulation_access_is_admin())) {
            return array_keys($this->roleOptions());
        }

        return match ($role) {
            'koordinator-bk' => ['koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
            'guru-bk' => ['guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
            default => [$role],
        };
    }

    private function normalizeRole(string $role): string
    {
        $role = strtolower(trim(str_replace('_', '-', $role)));
        $role = preg_replace('/\s+/', '-', $role) ?: '';

        return match ($role) {
            'administrator', 'admin' => 'admin',
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
        $allowedRoles = $this->previewableRolesFor($this->actualSessionRole());
        $options = array_intersect_key($this->roleOptions(), array_flip($allowedRoles));

        return $options !== [] ? $options : [$role => $this->roleLabel($role)];
    }

    private function featuresForRole(string $role): array
    {
        return array_filter($this->features(), static function (array $feature) use ($role): bool {
            $roles = $feature['role_modes'] ?? [];
            return in_array('all', $roles, true) || in_array($role, $roles, true);
        });
    }

    private function annotateFeatures(array $features, string $role): array
    {
        $progress = $this->progressForRole($role);

        foreach ($features as $key => &$feature) {
            $feature['url'] = $this->urlWithRole((string) ($feature['url'] ?? 'prototype/' . $key), $role);
            $feature['is_tried'] = in_array((string) $key, $progress, true);
            $feature['is_accessible'] = true;
            $feature['access_note'] = 'Fitur ini bisa dicek dari sudut pandang ' . $this->roleLabel($role) . '.';
        }

        return $features;
    }

    private function prototypeFeatureSections(string $role): array
    {
        $features = $this->features();

        return [
            [
                'title' => 'Fitur Inti yang Dirancang',
                'description' => 'Sebelas fitur inti yang menjadi fokus rancangan dan pengembangan aplikasi BK.',
                'items' => $this->prototypeSectionItems([
                    'consultation',
                    'notifications',
                    'messages',
                    'assessments',
                    'career-study',
                    'guidance',
                    'counseling',
                    'parent-collaboration',
                    'home-visits',
                    'case-conferences',
                    'assignments',
                ], $features, $role),
            ],
            [
                'title' => 'Fitur yang Diperbarui atau Diperbaiki',
                'description' => 'Fitur lama yang tetap dipakai, tetapi isi, akses, atau tampilannya disesuaikan dengan kebutuhan BK.',
                'items' => $this->prototypeSectionItems([
                    'dashboard',
                    'reports',
                    'student-import',
                ], $features, $role),
            ],
            [
                'title' => 'Fitur yang Dihapus atau Digantikan',
                'description' => 'Fitur lama yang tidak lagi menjadi arah utama pengembangan karena diganti oleh rancangan layanan BK baru.',
                'items' => $this->removedPrototypeFeatures(),
            ],
        ];
    }

    private function prototypeSectionItems(array $keys, array $features, string $role): array
    {
        $items = [];
        $progress = $this->progressForRole($role);

        foreach ($keys as $key) {
            if (! isset($features[$key])) {
                continue;
            }

            $feature = $features[$key];
            $allowed = $this->roleAllowed($role, $feature['role_modes'] ?? []);
            $feature['is_accessible'] = $allowed;
            $feature['is_tried'] = in_array((string) $key, $progress, true);
            $feature['access_note'] = $allowed
                ? 'Fitur ini bisa dicek dari sudut pandang ' . $this->roleLabel($role) . '.'
                : 'Fitur ini direncanakan untuk peran lain, sehingga ' . $this->roleLabel($role) . ' cukup mengetahui bahwa fitur ini akan dibuat/diperbarui.';
            $feature['url'] = $allowed ? $this->urlWithRole((string) ($feature['url'] ?? 'prototype/' . $key), $role) : '';
            $items[$key] = $feature;
        }

        return $items;
    }

    private function removedPrototypeFeatures(): array
    {
        return [
            'legacy-counseling-sessions' => [
                'title' => 'Manajemen Sesi Konseling Lama',
                'short_title' => 'Sesi Konseling Lama',
                'icon' => 'mdi mdi-calendar-remove-outline',
                'tone' => 'secondary',
                'roles' => 'Digantikan oleh layanan BK baru',
                'outcome' => 'Fitur lama diganti oleh Bimbingan, Konseling, Kolaborasi Orang Tua, Kunjungan Rumah, dan Konferensi Kasus agar pencatatan lebih jelas.',
                'pages' => ['Tidak dibuat demo baru'],
                'is_accessible' => false,
                'is_removed' => true,
                'access_note' => 'Tidak perlu diuji. Fitur ini hanya ditampilkan agar responden mengetahui bagian lama yang digantikan.',
                'url' => '',
            ],
            'legacy-violations' => [
                'title' => 'Manajemen Kasus & Pelanggaran',
                'short_title' => 'Kasus & Pelanggaran',
                'icon' => 'mdi mdi-alert-remove-outline',
                'tone' => 'danger',
                'roles' => 'Dihapus dari fokus BK',
                'outcome' => 'Pencatatan sanksi, pelanggaran, dan poin tidak lagi menjadi fokus pengembangan karena ranah tersebut lebih dekat ke kesiswaan.',
                'pages' => ['Tidak dibuat demo baru'],
                'is_accessible' => false,
                'is_removed' => true,
                'access_note' => 'Tidak perlu diuji. Kebutuhan yang masih relevan dialihkan ke Konsultasi & Pengaduan dan layanan BK.',
                'url' => '',
            ],
            'legacy-point-sync' => [
                'title' => 'Sinkron Total Poin Pelanggaran',
                'short_title' => 'Sinkron Poin',
                'icon' => 'mdi mdi-sync-off',
                'tone' => 'warning',
                'roles' => 'Dihapus',
                'outcome' => 'Fitur ini dihapus karena pengembangan tidak lagi memakai perhitungan poin pelanggaran.',
                'pages' => ['Tidak dibuat demo baru'],
                'is_accessible' => false,
                'is_removed' => true,
                'access_note' => 'Tidak perlu diuji karena tidak direncanakan masuk ke pengembangan baru.',
                'url' => '',
            ],
        ];
    }

    private function flattenPrototypeSections(array $sections): array
    {
        $features = [];
        foreach ($sections as $section) {
            foreach (($section['items'] ?? []) as $key => $item) {
                $features[$key] = $item;
            }
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

    private function features(): array
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard Pengembangan BK',
                'short_title' => 'Dashboard',
                'url' => 'prototype/flow/dashboard/overview',
                'icon' => 'mdi mdi-view-dashboard-outline',
                'tone' => 'primary',
                'roles' => 'Semua peran',
                'role_modes' => ['all'],
                'outcome' => 'Ringkasan jadwal, tugas, konsultasi, asesmen, dan laporan tampil sesuai hak akses peran.',
                'pages' => ['Dashboard'],
            ],
            'consultation' => [
                'title' => 'Konsultasi & Pengaduan',
                'short_title' => 'Konsultasi',
                'url' => 'prototype/flow/consultation/list',
                'icon' => 'mdi mdi-message-alert-outline',
                'tone' => 'danger',
                'roles' => 'Siswa, Orang Tua, Wali Kelas, Guru BK, Koordinator BK',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Pengguna mengajukan konsultasi/pengaduan, Guru BK meninjau, lalu kasus dapat dijadwalkan menjadi layanan BK.',
                'pages' => ['Daftar konsultasi', 'Form pengajuan', 'Detail status', 'Tinjauan Guru BK'],
            ],
            'notifications' => [
                'title' => 'Notifikasi Internal',
                'short_title' => 'Notifikasi',
                'url' => 'prototype/flow/notifications/list',
                'icon' => 'mdi mdi-bell-ring-outline',
                'tone' => 'warning',
                'roles' => 'Semua peran',
                'role_modes' => ['all'],
                'outcome' => 'Pengguna menerima pengingat jadwal, tugas, perubahan status, dan pesan layanan BK.',
                'pages' => ['Pusat notifikasi', 'Detail notifikasi'],
            ],
            'messages' => [
                'title' => 'Pesan Internal',
                'short_title' => 'Pesan',
                'url' => 'prototype/flow/messages/inbox',
                'icon' => 'mdi mdi-email-multiple-outline',
                'tone' => 'info',
                'roles' => 'Koordinator BK, Guru BK, Wali Kelas, Siswa, Orang Tua',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Komunikasi BK tercatat dalam percakapan internal agar koordinasi dapat dilacak.',
                'pages' => ['Kotak masuk', 'Tulis pesan', 'Detail percakapan'],
            ],
            'assessments' => [
                'title' => 'Asesmen',
                'short_title' => 'Asesmen',
                'url' => 'prototype/flow/assessments/list',
                'icon' => 'mdi mdi-clipboard-check-outline',
                'tone' => 'success',
                'roles' => 'Koordinator BK, Guru BK, Siswa, Wali Kelas, Orang Tua',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Guru BK menugaskan asesmen, siswa mengerjakan, dan hasilnya menjadi bahan pendampingan.',
                'pages' => ['Daftar asesmen', 'Buat asesmen', 'Pertanyaan', 'Penugasan', 'Pengerjaan siswa', 'Hasil'],
            ],
            'career-study' => [
                'title' => 'Info Karier dan Info Studi Lanjut',
                'short_title' => 'Karier/Studi',
                'url' => 'prototype/flow/career-study/career-catalog',
                'icon' => 'mdi mdi-school-outline',
                'tone' => 'secondary',
                'roles' => 'Guru BK, Siswa, Orang Tua, Wali Kelas, Koordinator BK',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Referensi karier/studi lanjut dikelola Guru BK dan dapat disimpan sebagai minat siswa.',
                'pages' => ['Katalog karier', 'Katalog studi lanjut', 'Pilihan tersimpan', 'Kelola referensi'],
            ],
            'student-import' => [
                'title' => 'Impor Data Siswa & Orang Tua',
                'short_title' => 'Impor Siswa',
                'url' => 'prototype/flow/student-import/upload',
                'icon' => 'mdi mdi-file-import-outline',
                'tone' => 'primary',
                'roles' => 'Admin, Koordinator BK, Wali Kelas',
                'role_modes' => ['admin', 'koordinator-bk', 'wali-kelas'],
                'outcome' => 'Koordinator BK dan Wali Kelas dapat membantu impor data siswa memakai template yang sama dengan fitur Admin.',
                'pages' => ['Unggah file', 'Template impor'],
            ],
            'guidance' => [
                'title' => 'Bimbingan',
                'short_title' => 'Bimbingan',
                'url' => 'prototype/flow/guidance/list',
                'icon' => 'mdi mdi-account-group-outline',
                'tone' => 'primary',
                'roles' => 'Koordinator BK, Guru BK, Wali Kelas, Siswa, Orang Tua',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Guru BK menjadwalkan bimbingan klasikal/kelompok, mencatat peserta, materi, dan tindak lanjut.',
                'pages' => ['Daftar bimbingan', 'Form bimbingan', 'Detail bimbingan'],
            ],
            'counseling' => [
                'title' => 'Konseling',
                'short_title' => 'Konseling',
                'url' => 'prototype/flow/counseling/schedule',
                'icon' => 'mdi mdi-account-heart-outline',
                'tone' => 'success',
                'roles' => 'Koordinator BK, Guru BK, Siswa, Orang Tua, Wali Kelas terbatas',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Konseling dijadwalkan dan dicatat dengan kerahasiaan yang lebih ketat.',
                'pages' => ['Daftar konseling', 'Form konseling', 'Detail konseling'],
            ],
            'parent-collaboration' => [
                'title' => 'Kolaborasi Orang Tua',
                'short_title' => 'Kolaborasi OT',
                'url' => 'prototype/flow/parent-collaboration/list',
                'icon' => 'mdi mdi-account-supervisor-outline',
                'tone' => 'warning',
                'roles' => 'Koordinator BK, Guru BK, Orang Tua, Wali Kelas terbatas, Siswa',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Pertemuan orang tua dicatat sebagai koordinasi resmi beserta hasil dan tindak lanjutnya.',
                'pages' => ['Daftar kolaborasi', 'Form kolaborasi', 'Detail kolaborasi'],
            ],
            'home-visits' => [
                'title' => 'Kunjungan Rumah',
                'short_title' => 'Kunjungan Rumah',
                'url' => 'prototype/flow/home-visits/list',
                'icon' => 'mdi mdi-home-account',
                'tone' => 'danger',
                'roles' => 'Koordinator BK, Guru BK, Orang Tua, Wali Kelas terbatas, Siswa',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Kunjungan rumah dijadwalkan dengan alamat, peserta, hasil kunjungan, dan rencana tindak lanjut.',
                'pages' => ['Daftar kunjungan', 'Form kunjungan', 'Detail kunjungan'],
            ],
            'case-conferences' => [
                'title' => 'Konferensi Kasus',
                'short_title' => 'Konferensi',
                'url' => 'prototype/flow/case-conferences/list',
                'icon' => 'mdi mdi-account-multiple-check-outline',
                'tone' => 'dark',
                'roles' => 'Koordinator BK, Guru BK, Wali Kelas, Siswa, Orang Tua bila diundang',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
                'outcome' => 'Koordinator/Guru BK mengundang pihak terkait, mencatat keputusan, dan menetapkan tindak lanjut kasus.',
                'pages' => ['Daftar konferensi', 'Form konferensi', 'Detail konferensi'],
            ],
            'assignments' => [
                'title' => 'Penugasan',
                'short_title' => 'Penugasan',
                'url' => 'prototype/flow/assignments/list',
                'icon' => 'mdi mdi-clipboard-account-outline',
                'tone' => 'info',
                'roles' => 'Koordinator BK dan Guru BK',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk'],
                'outcome' => 'Koordinator BK menetapkan kelas binaan dan tugas layanan kepada Guru BK beserta status pelaksanaannya.',
                'pages' => ['Daftar tugas', 'Form tugas', 'Detail tugas', 'Kelas binaan'],
            ],
            'reports' => [
                'title' => 'Laporan BK',
                'short_title' => 'Laporan',
                'url' => 'prototype/flow/reports/index',
                'icon' => 'mdi mdi-file-chart-outline',
                'tone' => 'secondary',
                'roles' => 'Koordinator BK, Guru BK, Wali Kelas, Admin',
                'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas'],
                'outcome' => 'Laporan menampilkan ringkasan layanan, asesmen, tindak lanjut, dan riwayat siswa sesuai batas akses.',
                'pages' => ['Laporan BK', 'Laporan agregat', 'Laporan individual'],
            ],
        ];
    }

    private function demoForRole(string $key, string $role): array
    {
        $base = $this->demoData()[$key] ?? [];
        $roleProfile = $this->roleAccessProfile($role, $key);

        return array_replace_recursive($base, $roleProfile, [
            'role_mode' => $role,
            'role_label' => $this->roleLabel($role),
        ]);
    }

    private function flowFeaturesForRole(string $role): array
    {
        $featureMeta = $this->features();
        $flows = $this->flowBlueprints();
        $features = [];

        foreach ($featureMeta as $key => $feature) {
            if (! isset($flows[$key]) || ! $this->roleAllowed($role, $feature['role_modes'] ?? [])) {
                continue;
            }

            $pages = [];
            foreach ($flows[$key]['pages'] as $pageKey => $page) {
                if (! $this->roleAllowed($role, $page['role_modes'] ?? ['all'])) {
                    continue;
                }

                $page['url'] = $this->urlWithRole('prototype/flow/' . $key . '/' . $pageKey, $role);
                $pages[$pageKey] = $page;
            }

            if ($pages === []) {
                continue;
            }

            $feature['pages'] = $pages;
            $feature['url'] = $this->urlWithRole('prototype/flow/' . $key . '/' . array_key_first($pages), $role);
            $features[$key] = $feature;
        }

        return $features;
    }

    private function roleAllowed(string $role, array $roles): bool
    {
        return in_array('all', $roles, true) || in_array($role, $roles, true) || $role === 'admin';
    }

    private function flowPageCountForRole(string $role): int
    {
        $total = 0;
        foreach ($this->flowFeaturesForRole($role) as $feature) {
            $total += count($feature['pages'] ?? []);
        }

        return $total;
    }

    private function flowSequence(array $features): array
    {
        $sequence = [];

        foreach ($features as $featureKey => $feature) {
            foreach (($feature['pages'] ?? []) as $pageKey => $page) {
                $sequence[] = [
                    'feature_key' => (string) $featureKey,
                    'page_key' => (string) $pageKey,
                    'feature_title' => (string) ($feature['short_title'] ?? $feature['title'] ?? $featureKey),
                    'title' => (string) ($page['title'] ?? $pageKey),
                    'url' => (string) ($page['url'] ?? 'prototype'),
                ];
            }
        }

        return $sequence;
    }

    private function firstFlowUrl(string $role): string
    {
        $features = $this->flowFeaturesForRole($role);
        $firstFeatureKey = array_key_first($features);
        if ($firstFeatureKey === null) {
            return $this->urlWithRole('prototype', $role);
        }

        $firstPageKey = array_key_first($features[$firstFeatureKey]['pages']);
        return $this->urlWithRole('prototype/flow/' . $firstFeatureKey . '/' . $firstPageKey, $role);
    }

    private function finalDemoFeature(string $featureKey, string $role): array
    {
        $features = $this->features();
        $feature = $features[$featureKey] ?? [
            'title' => 'Halaman Demo',
            'short_title' => 'Demo',
            'icon' => 'mdi mdi-shape-outline',
            'tone' => 'primary',
            'outcome' => '',
            'roles' => '',
        ];

        $screens = $this->finalDemoScreens()[$featureKey] ?? [
            'index' => ['title' => 'Daftar Data', 'type' => 'list'],
            'create' => ['title' => 'Tambah Data', 'type' => 'form'],
            'detail' => ['title' => 'Detail Data', 'type' => 'detail'],
            'edit' => ['title' => 'Edit Data', 'type' => 'form'],
        ];

        foreach ($screens as $screenKey => &$screen) {
            if (! $this->roleAllowed($role, $screen['role_modes'] ?? ['all'])) {
                unset($screens[$screenKey]);
                continue;
            }

            $screen['url'] = $this->urlWithRole('prototype/demo/' . $featureKey . '/' . $screenKey, $role);
        }
        unset($screen);

        $feature['screens'] = $screens;
        $feature['demo_url'] = $this->urlWithRole('prototype/demo/' . $featureKey, $role);
        $feature['intro_url'] = $this->urlWithRole('prototype/flow/' . $featureKey . '/' . array_key_first($this->flowBlueprints()[$featureKey]['pages'] ?? ['list' => []]), $role);

        return $feature;
    }

    private function finalDemoPage(string $featureKey, string $screenKey, array $screen, string $role, int $id): array
    {
        $demo = $this->demoForRole($featureKey, $role);
        $screenType = (string) ($screen['type'] ?? 'list');
        $records = $this->finalDemoRecords($featureKey, $screenKey, $role, $demo);
        $record = $records[$id - 1] ?? ($records[0] ?? []);
        $formFields = $this->finalDemoFields($featureKey, $screenKey, $screenType, $demo, $role);

        return array_replace_recursive($screen, [
            'type' => $screenType,
            'metrics' => $this->finalDemoMetrics($featureKey, $screenKey, $demo),
            'records' => $records,
            'record' => $record,
            'form_fields' => $formFields,
            'questions' => $this->assessmentQuestionExamples(),
            'sections' => $this->finalDemoSections($featureKey, $screenKey, $role, $record),
            'filters' => $this->finalDemoFilters($featureKey, $screenKey, $role),
            'timeline' => $this->finalDemoTimeline($featureKey, $screenKey, $demo),
            'notes' => $this->finalDemoNotes($featureKey, $screenKey, $role, $demo),
            'primary_action' => $this->finalPrimaryAction($featureKey, $screenKey, $role),
            'empty_text' => 'Belum ada data contoh.',
            'id' => max(1, $id),
        ]);
    }

    private function finalDemoScreens(): array
    {
        $staff = ['admin', 'koordinator-bk', 'guru-bk'];
        $coordinator = ['admin', 'koordinator-bk'];
        $studentFamily = ['admin', 'siswa', 'orang-tua'];
        $allUsers = ['all'];

        return [
            'dashboard' => [
                'index' => ['title' => 'Dashboard', 'type' => 'dashboard'],
            ],
            'consultation' => [
                'index' => ['title' => 'Konsultasi & Pengaduan', 'type' => 'list'],
                'create' => ['title' => 'Ajukan Konsultasi/Pengaduan', 'type' => 'form', 'role_modes' => ['admin', 'wali-kelas', 'siswa', 'orang-tua']],
                'detail' => ['title' => 'Detail Pengajuan', 'type' => 'detail'],
                'edit' => ['title' => 'Edit Pengajuan', 'type' => 'form', 'role_modes' => ['admin', 'wali-kelas', 'siswa', 'orang-tua']],
                'review' => ['title' => 'Tinjau Pengajuan', 'type' => 'detail', 'role_modes' => $staff],
                'schedule' => ['title' => 'Jadwalkan Tindak Lanjut', 'type' => 'form', 'role_modes' => $staff],
            ],
            'notifications' => [
                'index' => ['title' => 'Notifikasi', 'type' => 'list'],
                'detail' => ['title' => 'Detail Notifikasi', 'type' => 'detail'],
            ],
            'messages' => [
                'inbox' => ['title' => 'Kotak Masuk', 'type' => 'list'],
                'compose' => ['title' => 'Tulis Pesan', 'type' => 'form'],
                'thread' => ['title' => 'Percakapan', 'type' => 'conversation'],
                'sent' => ['title' => 'Pesan Terkirim', 'type' => 'list'],
            ],
            'assessments' => [
                'index' => ['title' => 'Asesmen', 'type' => 'list'],
                'create' => ['title' => 'Buat Asesmen', 'type' => 'form', 'role_modes' => $staff],
                'questions' => ['title' => 'Pertanyaan Asesmen', 'type' => 'assessment_questions', 'role_modes' => $staff],
                'assign' => ['title' => 'Tugaskan Asesmen', 'type' => 'form', 'role_modes' => $staff],
                'answer' => ['title' => 'Kerjakan Asesmen', 'type' => 'assessment_answer', 'role_modes' => ['admin', 'siswa']],
                'student-preview' => ['title' => 'Pratinjau Pengerjaan Siswa', 'type' => 'assessment_answer', 'role_modes' => $staff],
                'results' => ['title' => 'Hasil Asesmen', 'type' => 'list'],
                'detail' => ['title' => 'Detail Hasil Asesmen', 'type' => 'detail'],
            ],
            'career-study' => [
                'careers' => ['title' => 'Info Karier', 'type' => 'catalog'],
                'study' => ['title' => 'Info Studi Lanjut', 'type' => 'catalog'],
                'detail' => ['title' => 'Detail Referensi', 'type' => 'detail'],
                'saved' => ['title' => 'Minat Tersimpan', 'type' => 'list', 'role_modes' => $allUsers],
                'manage' => ['title' => 'Kelola Referensi', 'type' => 'list', 'role_modes' => $staff],
                'create' => ['title' => 'Tambah Referensi', 'type' => 'form', 'role_modes' => $staff],
            ],
            'student-import' => [
                'upload' => ['title' => 'Unggah File Impor', 'type' => 'form'],
                'template' => ['title' => 'Template Impor', 'type' => 'detail'],
            ],
            'guidance' => [
                'index' => ['title' => 'Bimbingan', 'type' => 'list'],
                'create' => ['title' => 'Tambah Bimbingan', 'type' => 'form', 'role_modes' => $staff],
                'detail' => ['title' => 'Detail Bimbingan', 'type' => 'detail', 'role_modes' => $staff],
                'edit' => ['title' => 'Edit Bimbingan', 'type' => 'form', 'role_modes' => $staff],
            ],
            'counseling' => [
                'index' => ['title' => 'Konseling', 'type' => 'list'],
                'create' => ['title' => 'Tambah Konseling', 'type' => 'form', 'role_modes' => $staff],
                'detail' => ['title' => 'Detail Konseling', 'type' => 'detail', 'role_modes' => $staff],
                'edit' => ['title' => 'Edit Konseling', 'type' => 'form', 'role_modes' => $staff],
            ],
            'parent-collaboration' => [
                'index' => ['title' => 'Kolaborasi Orang Tua', 'type' => 'list'],
                'create' => ['title' => 'Tambah Kolaborasi', 'type' => 'form', 'role_modes' => $staff],
                'detail' => ['title' => 'Detail Kolaborasi', 'type' => 'detail', 'role_modes' => $staff],
                'edit' => ['title' => 'Edit Kolaborasi', 'type' => 'form', 'role_modes' => $staff],
            ],
            'home-visits' => [
                'index' => ['title' => 'Kunjungan Rumah', 'type' => 'list'],
                'create' => ['title' => 'Tambah Kunjungan', 'type' => 'form', 'role_modes' => $staff],
                'detail' => ['title' => 'Detail Kunjungan', 'type' => 'detail', 'role_modes' => $staff],
                'edit' => ['title' => 'Edit Kunjungan', 'type' => 'form', 'role_modes' => $staff],
            ],
            'case-conferences' => [
                'index' => ['title' => 'Konferensi Kasus', 'type' => 'list'],
                'create' => ['title' => 'Tambah Konferensi', 'type' => 'form', 'role_modes' => $staff],
                'detail' => ['title' => 'Detail Konferensi', 'type' => 'detail', 'role_modes' => $staff],
                'edit' => ['title' => 'Edit Konferensi', 'type' => 'form', 'role_modes' => $staff],
            ],
            'assignments' => [
                'index' => ['title' => 'Penugasan', 'type' => 'list'],
                'create' => ['title' => 'Buat Tugas', 'type' => 'form', 'role_modes' => $coordinator],
                'detail' => ['title' => 'Detail Tugas', 'type' => 'detail'],
                'edit' => ['title' => 'Edit Tugas', 'type' => 'form', 'role_modes' => $coordinator],
                'class-assignment' => ['title' => 'Kelas Binaan', 'type' => 'list'],
            ],
            'reports' => [
                'index' => ['title' => 'Laporan BK', 'type' => 'report'],
                'aggregate' => ['title' => 'Laporan Agregat', 'type' => 'report', 'role_modes' => ['admin', 'koordinator-bk']],
                'individual' => ['title' => 'Laporan Individu Siswa', 'type' => 'report'],
            ],
        ];
    }

    private function finalDemoRecords(string $featureKey, string $screenKey, string $role, array $demo): array
    {
        if ($featureKey === 'dashboard') {
            return $demo['records'] ?? [];
        }

        if ($featureKey === 'messages') {
            return match ($screenKey) {
                'thread' => $this->flowRecords($featureKey, 'thread', $role, $demo),
                'sent' => [
                    ['Tanggal' => '10 Jun 2026', 'Penerima' => 'Orang Tua Siswa 2', 'Subjek' => 'Konfirmasi jadwal konseling', 'Status' => 'Terkirim'],
                    ['Tanggal' => '8 Jun 2026', 'Penerima' => 'Wali Kelas XI C', 'Subjek' => 'Koordinasi bimbingan kelas', 'Status' => 'Dibaca'],
                ],
                default => $demo['records'] ?? [],
            };
        }

        if ($featureKey === 'notifications') {
            return [
                ['Tanggal' => '12 Jun 2026', 'Judul' => 'Jadwal konseling mendatang', 'Tipe' => 'Jadwal', 'Tujuan Halaman' => 'Detail konseling', 'Status' => 'Belum dibaca'],
                ['Tanggal' => '13 Jun 2026', 'Judul' => 'Tugas baru dari Koordinator BK', 'Tipe' => 'Penugasan', 'Tujuan Halaman' => 'Detail tugas', 'Status' => 'Dibaca'],
                ['Tanggal' => '15 Jun 2026', 'Judul' => 'Pengajuan konsultasi diperbarui', 'Tipe' => 'Konsultasi', 'Tujuan Halaman' => 'Detail pengajuan', 'Status' => 'Dibaca'],
            ];
        }

        if ($featureKey === 'assessments') {
            return match ($screenKey) {
                'questions' => [
                    ['No' => '1', 'Pertanyaan' => 'Bidang kegiatan apa yang paling kamu sukai?', 'Tipe' => 'Pilihan Ganda', 'Poin' => '1', 'Wajib' => 'Ya'],
                    ['No' => '2', 'Pertanyaan' => 'Saya senang mengerjakan tugas dengan logika dan teknologi.', 'Tipe' => 'Skala Penilaian', 'Poin' => '1', 'Wajib' => 'Ya'],
                    ['No' => '3', 'Pertanyaan' => 'Ceritakan rencana studi/karier yang kamu minati.', 'Tipe' => 'Esai', 'Poin' => '0', 'Wajib' => 'Ya'],
                ],
                'results', 'detail' => $this->flowRecords($featureKey, 'results', $role, $demo),
                default => $demo['records'] ?? [],
            };
        }

        if ($featureKey === 'career-study') {
            return match ($screenKey) {
                'study' => [
                    ['Nama' => 'Universitas Pendidikan Indonesia', 'Jenis' => 'Studi Lanjut', 'Bidang' => 'Pendidikan', 'Status' => 'Publik'],
                    ['Nama' => 'Institut Teknologi Bandung', 'Jenis' => 'Studi Lanjut', 'Bidang' => 'Teknologi', 'Status' => 'Publik'],
                    ['Nama' => 'Politeknik Manufaktur Bandung', 'Jenis' => 'Studi Lanjut', 'Bidang' => 'Teknik', 'Status' => 'Publik'],
                ],
                'saved' => $this->flowRecords($featureKey, 'saved', $role, $demo),
                default => $demo['records'] ?? [],
            };
        }

        if ($featureKey === 'student-import') {
            return match ($screenKey) {
                'template' => [
                    ['Kelompok Kolom' => 'Identitas siswa', 'Kolom' => 'Nama Lengkap, NISN, NIK, Tempat Lahir, Tanggal Lahir, Jenis Kelamin', 'Wajib' => 'Nama, NISN, tanggal lahir, jenis kelamin'],
                    ['Kelompok Kolom' => 'Kelas dan status', 'Kolom' => 'Tingkat - Rombel, Status', 'Wajib' => 'Status dan kelas/rombel disarankan diisi'],
                    ['Kelompok Kolom' => 'Kontak dan alamat', 'Kolom' => 'Alamat, No Telepon', 'Wajib' => 'Opsional'],
                    ['Kelompok Kolom' => 'Keterangan siswa', 'Kolom' => 'Kebutuhan Khusus, Disabilitas, Nomor KIP/PIP', 'Wajib' => 'Opsional'],
                    ['Kelompok Kolom' => 'Data orang tua/wali', 'Kolom' => 'Nama Ayah Kandung, Nama Ibu Kandung, Nama Wali', 'Wajib' => 'Opsional'],
                ],
                default => [],
            };
        }

        if ($featureKey === 'reports') {
            return match ($screenKey) {
                'individual' => [
                    ['Tanggal' => '4 Jun 2026', 'Fitur/Catatan' => 'Konsultasi & Pengaduan', 'Ringkasan' => 'Siswa 2 mengajukan konsultasi tentang fokus belajar.', 'Tindak Lanjut' => 'Dijadwalkan konseling individu', 'Akses' => 'Terbatas'],
                    ['Tanggal' => '13 Jun 2026', 'Fitur/Catatan' => 'Konseling', 'Ringkasan' => 'Konseling individu dilakukan dengan ringkasan aman.', 'Tindak Lanjut' => 'Pemantauan belajar pekan berikutnya', 'Akses' => 'Rahasia BK'],
                    ['Tanggal' => '15 Jun 2026', 'Fitur/Catatan' => 'Asesmen', 'Ringkasan' => 'Asesmen minat karier menunjukkan minat teknologi.', 'Tindak Lanjut' => 'Diskusi studi lanjut', 'Akses' => 'Terbatas'],
                ],
                'aggregate' => [
                    ['Jenis Layanan' => 'Konsultasi & Pengaduan', 'Jumlah' => '4', 'Selesai' => '1', 'Perlu Tindak Lanjut' => '2', 'Periode' => 'Juni 2026'],
                    ['Jenis Layanan' => 'Bimbingan', 'Jumlah' => '2', 'Selesai' => '1', 'Perlu Tindak Lanjut' => '1', 'Periode' => 'Juni 2026'],
                    ['Jenis Layanan' => 'Konseling', 'Jumlah' => '2', 'Selesai' => '1', 'Perlu Tindak Lanjut' => '1', 'Periode' => 'Juni 2026'],
                ],
                default => $demo['records'] ?? [],
            };
        }

        $bkServiceFeatures = ['guidance', 'counseling', 'parent-collaboration', 'home-visits', 'case-conferences'];
        if (in_array($featureKey, $bkServiceFeatures, true) && $screenKey === 'index') {
            $isStaff = in_array($role, ['admin', 'koordinator-bk', 'guru-bk'], true);
            // Wali Kelas, Siswa, dan Orang Tua cukup melihat daftar jadwal/undangan dengan
            // kolom aman (jadwal, lokasi, status) langsung di halaman utama, tanpa halaman detail.
            if (! $isStaff) {
                return $this->bkScheduleRecords($featureKey);
            }
        }

        $mappedPage = match ($screenKey) {
            'class-assignment' => 'class-assignment',
            'results' => 'results',
            'aggregate', 'individual' => $screenKey,
            default => $screenKey,
        };

        if (in_array($mappedPage, ['class-assignment', 'results', 'aggregate', 'individual'], true)) {
            return $this->flowRecords($featureKey, $mappedPage, $role, $demo);
        }

        return $demo['records'] ?? [];
    }

    private function bkScheduleRecords(string $featureKey): array
    {
        return match ($featureKey) {
            'guidance' => [
                ['Judul' => 'Etika media sosial', 'Jenis Layanan' => 'Bimbingan', 'Kelas' => 'X IPA C', 'Jadwal' => '12 Jun 2026 08.00', 'Lokasi' => 'Kelas X IPA C', 'Status' => 'Dijadwalkan'],
                ['Judul' => 'Rencana belajar pekanan', 'Jenis Layanan' => 'Bimbingan', 'Kelas' => 'XI C', 'Jadwal' => '25 Jun 2026 10.00', 'Lokasi' => 'Ruang BK 2', 'Status' => 'Dijadwalkan'],
            ],
            'counseling' => [
                ['Judul' => 'Undangan sesi konseling', 'Jenis Layanan' => 'Konseling', 'Jadwal' => '13 Jun 2026 09.00', 'Lokasi' => 'Ruang BK 1', 'Status' => 'Dijadwalkan'],
            ],
            'parent-collaboration' => [
                ['Judul' => 'Pertemuan orang tua', 'Jenis Layanan' => 'Kolaborasi Orang Tua', 'Siswa' => 'Siswa 1', 'Jadwal' => '6 Jun 2026 13.00', 'Lokasi' => 'Ruang BK 1', 'Status' => 'Selesai'],
                ['Judul' => 'Pertemuan orang tua', 'Jenis Layanan' => 'Kolaborasi Orang Tua', 'Siswa' => 'Siswa 2', 'Jadwal' => '20 Jun 2026 13.00', 'Lokasi' => 'Ruang BK 1', 'Status' => 'Dijadwalkan'],
            ],
            'home-visits' => [
                ['Judul' => 'Kunjungan rumah', 'Jenis Layanan' => 'Kunjungan Rumah', 'Siswa/Kelas' => 'Siswa 1 / X IPA C', 'Jadwal' => '18 Jun 2026 14.00', 'Status' => 'Dijadwalkan'],
            ],
            'case-conferences' => [
                ['Judul' => 'Undangan konferensi kasus', 'Jenis Layanan' => 'Konferensi Kasus', 'Tanggal' => '20 Jun 2026', 'Tempat' => 'Ruang Rapat BK', 'Status' => 'Dijadwalkan'],
            ],
            default => [],
        };
    }

    private function finalDemoFields(string $featureKey, string $screenKey, string $screenType, array $demo, string $role = ''): array
    {
        if (! in_array($screenType, ['form', 'report'], true)) {
            return [];
        }

        if ($screenType === 'report') {
            return match ($screenKey) {
                'individual' => [
                    ['label' => 'Periode', 'type' => 'select', 'options' => ['Juni 2026', 'Semester Genap 2025/2026', 'Tahun Ajaran 2025/2026']],
                    ['label' => 'Kelas', 'type' => 'select', 'options' => ['X IPA C', 'XI C', 'XII C']],
                    ['label' => 'Akun/Nama Siswa', 'type' => 'select', 'options' => ['Siswa 2', 'Siswa 1', 'Siswa Contoh']],
                    ['label' => 'Jenis Laporan', 'type' => 'select', 'options' => ['Semua fitur/catatan BK', 'Konsultasi & Pengaduan', 'Bimbingan', 'Konseling', 'Kolaborasi Orang Tua', 'Kunjungan Rumah', 'Konferensi Kasus', 'Asesmen', 'Penugasan']],
                ],
                'aggregate' => [
                    ['label' => 'Periode', 'type' => 'select', 'options' => ['Juni 2026', 'Semester Genap 2025/2026', 'Tahun Ajaran 2025/2026']],
                    ['label' => 'Kelas', 'type' => 'select', 'options' => ['Semua Kelas', 'X IPA C', 'XI C', 'XII C']],
                    ['label' => 'Jenis Rekap', 'type' => 'select', 'options' => ['Semua layanan BK', 'Konsultasi', 'Bimbingan', 'Konseling', 'Asesmen', 'Penugasan']],
                ],
                default => [
                    ['label' => 'Periode', 'type' => 'select', 'options' => ['Juni 2026', 'Semester Genap 2025/2026']],
                    ['label' => 'Jenis laporan', 'type' => 'select', 'options' => ['Laporan agregat', 'Laporan individual siswa']],
                ],
            };
        }

        return match ($screenKey) {
            'compose' => $this->flowFormFields($featureKey, 'compose', 'compose', $demo),
            'assign' => [
                ['label' => 'Asesmen', 'type' => 'select', 'options' => ['Minat Karier', 'Kesejahteraan Siswa', 'Gaya Belajar']],
                ['label' => 'Target', 'type' => 'select', 'options' => ['Siswa 2', 'Siswa 1', 'X IPA C', 'XI C']],
                ['label' => 'Batas pengerjaan', 'type' => 'date', 'placeholder' => '2026-06-30'],
                ['label' => 'Catatan untuk siswa', 'type' => 'textarea', 'placeholder' => 'Tuliskan instruksi yang mudah dipahami siswa'],
            ],
            'schedule' => [
                ['label' => 'Jenis tindak lanjut', 'type' => 'select', 'options' => ['Bimbingan', 'Konseling', 'Kolaborasi Orang Tua', 'Konferensi Kasus']],
                ['label' => 'Petugas', 'type' => 'select', 'options' => ['Guru BK 1', 'Guru BK 2', 'Guru BK 3']],
                ['label' => 'Tanggal dan waktu', 'type' => 'text', 'placeholder' => 'Contoh: 14 Juni 2026, 09.00'],
                ['label' => 'Catatan jadwal', 'type' => 'textarea', 'placeholder' => 'Tuliskan keterangan singkat'],
            ],
            'create', 'edit' => match ($featureKey) {
                'consultation' => [
                    ['label' => 'Jenis pengajuan', 'type' => 'select', 'options' => ['Konsultasi', 'Pengaduan', 'Permintaan Konseling', 'Laporan Orang Tua', 'Lainnya']],
                    ['label' => 'Siswa terkait', 'type' => 'select', 'options' => ['Siswa 2', 'Siswa 1', 'Tidak ada / umum']],
                    ['label' => 'Kategori', 'type' => 'text', 'placeholder' => 'Contoh: belajar, relasi teman, keluarga'],
                    ['label' => 'Judul', 'type' => 'text', 'placeholder' => 'Contoh: ingin konsultasi rencana belajar'],
                    ['label' => 'Keterangan', 'type' => 'textarea', 'placeholder' => 'Tuliskan kebutuhan atau kronologi secara singkat'],
                    ['label' => 'Tanggal kejadian', 'type' => 'date', 'placeholder' => '2026-06-10'],
                    ['label' => 'Lokasi', 'type' => 'text', 'placeholder' => 'Contoh: kelas XI C'],
                    ['label' => 'Prioritas', 'type' => 'select', 'options' => ['Rendah', 'Sedang', 'Tinggi', 'Mendesak']],
                ],
                'assessments' => [
                    ['label' => 'Judul asesmen', 'type' => 'text', 'placeholder' => 'Contoh: Asesmen Minat Karier'],
                    ['label' => 'Jenis asesmen', 'type' => 'select', 'options' => ['Minat Karier', 'Kesejahteraan Siswa', 'Gaya Belajar', 'Survei']],
                    ['label' => 'Cara penilaian', 'type' => 'select', 'options' => ['Lulus/Tidak Lulus', 'Skor Saja', 'Survei']],
                    ['label' => 'Target peserta', 'type' => 'select', 'options' => ['Siswa Tertentu', 'Kelas', 'Tingkat', 'Semua Siswa']],
                    ['label' => 'Kelas target', 'type' => 'select', 'options' => ['Tidak spesifik', 'X IPA C', 'XI C', 'XII C']],
                    ['label' => 'Tanggal mulai', 'type' => 'date', 'placeholder' => '2026-06-10'],
                    ['label' => 'Tanggal selesai', 'type' => 'date', 'placeholder' => '2026-06-30'],
                    ['label' => 'Durasi menit', 'type' => 'text', 'placeholder' => 'Contoh: 45'],
                    ['label' => 'Instruksi', 'type' => 'textarea', 'placeholder' => 'Tuliskan petunjuk pengerjaan untuk siswa'],
                ],
                'career-study' => [
                    ['label' => 'Jenis referensi', 'type' => 'select', 'options' => ['Karier', 'Studi Lanjut']],
                    ['label' => 'Status publikasi', 'type' => 'select', 'options' => ['Publik', 'Draf', 'Nonaktif']],
                    ['label' => 'Catatan kurasi', 'type' => 'textarea', 'placeholder' => 'Tuliskan catatan sebelum referensi ditayangkan'],
                ],
                'student-import' => [
                    ['label' => 'Pilih file Excel', 'type' => 'file', 'placeholder' => '.xlsx,.xls,.csv'],
                ],
                'guidance' => $this->bkServiceFields('Bimbingan'),
                'counseling' => $this->bkServiceFields('Konseling'),
                'parent-collaboration' => $this->bkServiceFields('Kolaborasi Orang Tua'),
                'home-visits' => $this->bkServiceFields('Kunjungan Rumah'),
                'case-conferences' => $this->bkServiceFields('Konferensi Kasus'),
                'assignments' => [
                    ['label' => 'Jenis tugas', 'type' => 'select', 'options' => ['Kelas Binaan', 'Tugas Layanan', 'Tindak Lanjut', 'Koordinasi']],
                    ['label' => 'Judul tugas', 'type' => 'text', 'placeholder' => 'Contoh: tindak lanjut konsultasi siswa'],
                    ['label' => 'Instruksi', 'type' => 'textarea', 'placeholder' => 'Tuliskan instruksi dan batas waktu'],
                    ['label' => 'Ditugaskan kepada', 'type' => 'select', 'options' => ['Guru BK 1', 'Guru BK 2', 'Guru BK 3']],
                    ['label' => 'Kelas', 'type' => 'select', 'options' => ['Tidak terkait kelas', 'X IPA C', 'XI C', 'XII C']],
                    ['label' => 'Siswa', 'type' => 'select', 'options' => ['Tidak terkait siswa', 'Siswa 2', 'Siswa 1']],
                    ['label' => 'Prioritas', 'type' => 'select', 'options' => ['Rendah', 'Sedang', 'Tinggi', 'Mendesak']],
                    ['label' => 'Batas waktu', 'type' => 'date', 'placeholder' => '2026-06-20'],
                ],
                default => $demo['form_fields'] ?? [],
            },
            default => $demo['form_fields'] ?? [],
        };
    }

    private function bkServiceFields(string $serviceType): array
    {
        $titleLabel = match ($serviceType) {
            'Bimbingan' => 'Topik/Judul bimbingan',
            'Konseling' => 'Topik/Judul konseling',
            'Kolaborasi Orang Tua' => 'Topik/Judul kolaborasi',
            'Kunjungan Rumah' => 'Judul/Masalah',
            'Konferensi Kasus' => 'Masalah/Topik/Judul',
            default => 'Judul layanan',
        };
        $locationLabel = $serviceType === 'Konferensi Kasus' ? 'Tempat' : 'Lokasi';

        $base = [
            ['label' => $titleLabel, 'type' => 'text', 'placeholder' => 'Contoh: pendampingan rencana belajar'],
            ['label' => 'Siswa terkait', 'type' => 'select', 'options' => ['Tidak spesifik', 'Siswa 2', 'Siswa 1']],
            ['label' => 'Kelas terkait', 'type' => 'select', 'options' => ['Tidak spesifik', 'X IPA C', 'XI C', 'XII C']],
            ['label' => 'Guru BK/Petugas', 'type' => 'select', 'options' => ['Guru BK 1', 'Guru BK 2', 'Guru BK 3']],
            ['label' => 'Tanggal/Jadwal', 'type' => 'text', 'placeholder' => 'Contoh: 18 Juni 2026, 09.00'],
            ['label' => $locationLabel, 'type' => 'text', 'placeholder' => 'Contoh: Ruang BK 1'],
            ['label' => 'Durasi menit', 'type' => 'text', 'placeholder' => 'Contoh: 45'],
            ['label' => 'Status', 'type' => 'select', 'options' => ['Draf', 'Dijadwalkan', 'Berlangsung', 'Selesai', 'Dibatalkan', 'Perlu Tindak Lanjut']],
        ];

        $detail = match ($serviceType) {
            'Bimbingan' => [
                ['label' => 'Jenis bimbingan', 'type' => 'select', 'options' => ['Kelompok', 'Klasikal', 'Kelas Besar']],
                ['label' => 'Topik materi', 'type' => 'text', 'placeholder' => 'Contoh: etika media sosial'],
                ['label' => 'Ringkasan materi', 'type' => 'textarea', 'placeholder' => 'Tuliskan ringkasan materi dan respons peserta'],
            ],
            'Konseling' => [
                ['label' => 'Jenis konseling', 'type' => 'select', 'options' => ['Individu', 'Kelompok']],
                ['label' => 'Deskripsi masalah', 'type' => 'textarea', 'placeholder' => 'Catatan masalah sesuai batas kerahasiaan'],
                ['label' => 'Ringkasan sesi', 'type' => 'textarea', 'placeholder' => 'Catatan sesi untuk internal BK'],
                ['label' => 'Status tindak lanjut', 'type' => 'select', 'options' => ['Belum', 'Berjalan', 'Selesai', 'Dibatalkan']],
            ],
            'Kolaborasi Orang Tua' => [
                ['label' => 'Nama-nama orang tua', 'type' => 'textarea', 'placeholder' => 'Contoh: Ibu Siti, Bapak Ahmad'],
                ['label' => 'Topik kolaborasi', 'type' => 'text', 'placeholder' => 'Contoh: motivasi belajar'],
                ['label' => 'Pihak yang menghadiri', 'type' => 'textarea', 'placeholder' => 'Tulis satu per satu, termasuk pihak luar aplikasi bila ada'],
                ['label' => 'Ringkasan pertemuan', 'type' => 'textarea', 'placeholder' => 'Tuliskan hasil diskusi'],
                ['label' => 'Tindak lanjut', 'type' => 'textarea', 'placeholder' => 'Tuliskan kesepakatan lanjutan'],
            ],
            'Kunjungan Rumah' => [
                ['label' => 'Wali kelas', 'type' => 'select', 'options' => ['Wali Kelas 1', 'Wali Kelas XI C', 'Tidak dilibatkan']],
                ['label' => 'Alamat kunjungan', 'type' => 'textarea', 'placeholder' => 'Tuliskan alamat lengkap/snapshot alamat'],
                ['label' => 'Topik masalah', 'type' => 'text', 'placeholder' => 'Contoh: motivasi belajar'],
                ['label' => 'Hasil kunjungan', 'type' => 'textarea', 'placeholder' => 'Tuliskan hasil kunjungan'],
                ['label' => 'Tindak lanjut', 'type' => 'textarea', 'placeholder' => 'Tuliskan rencana bantuan'],
            ],
            'Konferensi Kasus' => [
                ['label' => 'Pihak yang menghadiri', 'type' => 'textarea', 'placeholder' => 'Contoh: Koordinator BK, Guru BK, wali kelas, orang tua, pihak lain'],
                ['label' => 'Kronologi singkat', 'type' => 'textarea', 'placeholder' => 'Tuliskan kronologi yang aman dibahas'],
                ['label' => 'Ringkasan diskusi', 'type' => 'textarea', 'placeholder' => 'Tuliskan ringkasan konferensi'],
                ['label' => 'Keputusan', 'type' => 'textarea', 'placeholder' => 'Tuliskan keputusan'],
                ['label' => 'Rencana tindak lanjut', 'type' => 'textarea', 'placeholder' => 'Tuliskan tindak lanjut dan penanggung jawab'],
            ],
            default => [],
        };

        return array_merge($base, $detail);
    }

    private function assessmentQuestionExamples(): array
    {
        return [
            [
                'number' => 1,
                'text' => 'Bidang kegiatan apa yang paling kamu sukai?',
                'type' => 'Pilihan Ganda',
                'options' => ['Teknologi', 'Pendidikan', 'Kesehatan', 'Bisnis'],
                'answer' => 'Teknologi',
                'correct' => 'Teknologi',
                'points' => 1,
                'required' => true,
                'dimension' => 'Minat Karier',
                'explanation' => 'Pilihan ini membantu Guru BK melihat arah minat awal siswa.',
            ],
            [
                'number' => 2,
                'text' => 'Kegiatan apa saja yang kamu minati untuk dipelajari lebih lanjut?',
                'type' => 'Pilihan Jamak',
                'options' => ['Pemrograman', 'Desain', 'Menulis', 'Berbicara di depan umum'],
                'answer' => ['Pemrograman', 'Desain'],
                'correct' => [],
                'points' => 0,
                'required' => true,
                'dimension' => 'Eksplorasi Minat',
                'explanation' => 'Pilihan jamak dipakai ketika siswa boleh memilih lebih dari satu jawaban.',
            ],
            [
                'number' => 3,
                'text' => 'Saya merasa mampu mengatur waktu belajar secara mandiri.',
                'type' => 'Skala Penilaian',
                'options' => ['1', '2', '3', '4', '5'],
                'answer' => '4',
                'correct' => '',
                'points' => 0,
                'required' => true,
                'dimension' => 'Kemandirian Belajar',
                'explanation' => 'Skala 1 sampai 5 digunakan untuk membaca kecenderungan, bukan benar-salah.',
            ],
            [
                'number' => 4,
                'text' => 'Ceritakan rencana studi atau karier yang ingin kamu coba setelah lulus.',
                'type' => 'Esai',
                'options' => [],
                'answer' => 'Saya ingin mempelajari teknologi informasi karena suka membuat aplikasi sederhana.',
                'correct' => 'Jawaban dinilai manual oleh Guru BK.',
                'points' => 0,
                'required' => true,
                'dimension' => 'Rencana Lanjutan',
                'explanation' => 'Esai memberi ruang bagi siswa menjelaskan alasan dan kebutuhannya.',
            ],
        ];
    }

    private function finalDemoSections(string $featureKey, string $screenKey, string $role, array $record): array
    {
        if (! in_array($screenKey, ['detail', 'review', 'result', 'template'], true)) {
            return [];
        }

        $participants = [
            ['Nama' => 'Siswa 2', 'Peran' => 'Siswa terkait', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
            ['Nama' => 'Guru BK 1', 'Peran' => 'Guru BK', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
            ['Nama' => 'Orang Tua Siswa 1', 'Peran' => 'Orang tua', 'Undangan' => 'Diundang', 'Kehadiran' => 'Menunggu'],
        ];

        return match ($featureKey) {
            'guidance' => [
                ['title' => 'Peserta dan Kehadiran', 'type' => 'table', 'records' => [
                    ['Nama' => 'Kelas X IPA C', 'Peran' => 'Peserta kelas', 'Undangan' => 'Diundang', 'Kehadiran' => '32 hadir'],
                    ['Nama' => 'Guru BK 1', 'Peran' => 'Guru BK', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                ]],
                ['title' => 'Jadwal dan Durasi', 'type' => 'text', 'body' => 'Contoh: Jumat, 12 Juni 2026 pukul 08.00 di kelas X IPA C selama 60 menit.'],
                ['title' => 'Catatan Materi', 'type' => 'text', 'body' => 'Materi etika media sosial, latihan refleksi perilaku digital, dan tindak lanjut untuk siswa yang membutuhkan pendampingan tambahan.'],
            ],
            'counseling' => [
                ['title' => 'Peserta', 'type' => 'table', 'records' => [
                    ['Nama' => 'Siswa 2', 'Peran' => 'Siswa', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                    ['Nama' => 'Guru BK 2', 'Peran' => 'Guru BK', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                ]],
                ['title' => 'Catatan dan Tindak Lanjut', 'type' => 'text', 'body' => $role === 'guru-bk' || $role === 'koordinator-bk' || $role === 'admin'
                    ? 'Catatan internal BK: siswa membutuhkan pendampingan pengaturan fokus belajar. Tindak lanjut dijadwalkan pada pekan berikutnya.'
                    : 'Ringkasan terbatas: jadwal konseling telah dilakukan dan tindak lanjut akan diinformasikan oleh Guru BK bila diperlukan.'],
                ['title' => 'Deskripsi Masalah', 'type' => 'text', 'body' => $role === 'guru-bk' || $role === 'koordinator-bk' || $role === 'admin'
                    ? 'Siswa menyampaikan kesulitan menjaga fokus belajar pada jam pertama dan membutuhkan strategi pengaturan waktu.'
                    : 'Ringkasan masalah tidak ditampilkan penuh karena termasuk data konseling yang dibatasi.'],
            ],
            'parent-collaboration' => [
                ['title' => 'Undangan dan Kehadiran', 'type' => 'table', 'records' => $participants],
                ['title' => 'Pihak yang Hadir', 'type' => 'text', 'body' => 'Contoh manual: Ibu Siti, Bapak Ahmad, Guru BK 1, dan Wali Kelas 1. Nama pihak luar aplikasi tetap dapat ditulis satu per satu.'],
                ['title' => 'Ringkasan dan Tindak Lanjut', 'type' => 'text', 'body' => 'Orang tua dan Guru BK menyepakati pemantauan belajar di rumah. Guru BK membuat pengingat tindak lanjut dua minggu setelah pertemuan.'],
            ],
            'home-visits' => [
                ['title' => 'Peserta Kunjungan', 'type' => 'table', 'records' => [
                    ['Nama' => 'Guru BK 1', 'Peran' => 'Guru BK', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                    ['Nama' => 'Orang Tua Siswa 1', 'Peran' => 'Orang tua', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                ]],
                ['title' => 'Alamat dan Wali Kelas', 'type' => 'text', 'body' => 'Siswa/Kelas/Wali kelas: Siswa 1 / X IPA C / Wali Kelas 1. Alamat rumah: Jl. Raya Banjaran No. 12.'],
                ['title' => 'Hasil Kunjungan', 'type' => 'text', 'body' => 'Lingkungan belajar di rumah perlu penyesuaian jadwal. Tindak lanjut: Guru BK dan orang tua menyepakati jadwal belajar malam yang lebih teratur.'],
            ],
            'case-conferences' => [
                ['title' => 'Peserta Konferensi', 'type' => 'table', 'records' => [
                    ['Nama' => 'Ustadz Koordinator BK 1', 'Peran' => 'Pimpinan konferensi', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                    ['Nama' => 'Guru BK 2', 'Peran' => 'Guru BK', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                    ['Nama' => 'Wali Kelas 1', 'Peran' => 'Wali kelas', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Hadir'],
                    ['Nama' => 'Orang Tua Siswa 2', 'Peran' => 'Orang tua', 'Undangan' => 'Diundang', 'Kehadiran' => 'Menunggu'],
                ]],
                ['title' => 'Tempat dan Pihak Hadir Manual', 'type' => 'text', 'body' => 'Tempat: Ruang Rapat BK. Pihak hadir dapat ditulis manual bila belum memiliki akun aplikasi, misalnya perwakilan asrama atau pihak sekolah lain.'],
                ['title' => 'Keputusan dan Tindak Lanjut', 'type' => 'text', 'body' => in_array($role, ['admin', 'koordinator-bk', 'guru-bk'], true)
                    ? 'Keputusan contoh: Guru BK melakukan konseling lanjutan, wali kelas memantau perubahan belajar, dan Koordinator BK memeriksa perkembangan tugas setelah dua minggu.'
                    : 'Ringkasan terbatas: tindak lanjut akan disampaikan oleh Guru BK sesuai kebutuhan dan batas kerahasiaan data siswa.'],
            ],
            'student-import' => [
                ['title' => 'Template yang Digunakan', 'type' => 'text', 'body' => 'Prototype memakai template contoh yang sama dengan fitur Impor Siswa pada Admin: template_import_siswa_2026-06-10_contoh.xlsx.'],
                ['title' => 'Kolom Template', 'type' => 'table', 'records' => [
                    ['Kolom' => 'Nama Lengkap, NISN, NIK, Tempat Lahir, Tanggal Lahir, Jenis Kelamin', 'Keterangan' => 'Identitas utama siswa.'],
                    ['Kolom' => 'Tingkat - Rombel, Status', 'Keterangan' => 'Penempatan kelas dan status siswa.'],
                    ['Kolom' => 'Alamat, No Telepon', 'Keterangan' => 'Kontak dan alamat siswa.'],
                    ['Kolom' => 'Nama Ayah Kandung, Nama Ibu Kandung, Nama Wali', 'Keterangan' => 'Data orang tua/wali berada di template siswa yang sama.'],
                ]],
            ],
            'assignments' => [
                ['title' => 'Riwayat Status', 'type' => 'table', 'records' => [
                    ['Tanggal' => '1 Jun 2026', 'Status' => 'Ditugaskan', 'Oleh' => 'Ustadz Koordinator BK 1', 'Catatan' => 'Tugas dibuat oleh Koordinator BK.'],
                    ['Tanggal' => '3 Jun 2026', 'Status' => 'Berjalan', 'Oleh' => 'Guru BK 2', 'Catatan' => 'Mulai memetakan kebutuhan kelas.'],
                ]],
            ],
            'assessments' => [
                ['title' => 'Contoh Jawaban', 'type' => 'questions', 'questions' => $this->assessmentQuestionExamples()],
            ],
            default => [],
        };
    }

    private function finalDemoMetrics(string $featureKey, string $screenKey, array $demo): array
    {
        if ($featureKey === 'student-import') {
            return [];
        }

        if (in_array($screenKey, ['detail', 'edit'], true)) {
            return [];
        }

        return $demo['metrics'] ?? [];
    }

    private function finalDemoFilters(string $featureKey, string $screenKey, string $role = ''): array
    {
        if (! in_array($screenKey, ['index', 'inbox', 'sent', 'careers', 'study', 'manage', 'class-assignment', 'results'], true)) {
            return [];
        }

        return match ($featureKey) {
            'dashboard' => [],
            'consultation' => [
                ['label' => 'Jenis Pengajuan', 'type' => 'select', 'options' => ['Semua Jenis', 'Konsultasi', 'Pengaduan', 'Permintaan Konseling', 'Laporan Orang Tua']],
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Diajukan', 'Ditinjau', 'Diterima', 'Ditolak', 'Dijadwalkan', 'Selesai']],
                ['label' => 'Prioritas', 'type' => 'select', 'options' => ['Semua Prioritas', 'Rendah', 'Sedang', 'Tinggi', 'Mendesak']],
                ['label' => 'Periode', 'type' => 'date-range'],
                ['label' => 'Cari judul/pelapor', 'type' => 'search'],
            ],
            'notifications' => [
                ['label' => 'Status Baca', 'type' => 'select', 'options' => ['Semua', 'Belum dibaca', 'Dibaca']],
                ['label' => 'Tipe', 'type' => 'select', 'options' => ['Semua Tipe', 'Jadwal', 'Penugasan', 'Konsultasi', 'Pesan', 'Asesmen']],
                ['label' => 'Periode', 'type' => 'date-range'],
            ],
            'messages' => [
                ['label' => 'Status Baca', 'type' => 'select', 'options' => ['Semua', 'Belum dibaca', 'Dibaca']],
                ['label' => 'Penerima/Pengirim', 'type' => 'select', 'options' => ['Semua', 'Koordinator BK', 'Guru BK', 'Wali Kelas', 'Siswa', 'Orang Tua']],
                ['label' => 'Cari subjek', 'type' => 'search'],
            ],
            'assessments' => [
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Aktif', 'Draf', 'Dipublikasi', 'Selesai']],
                ['label' => 'Target', 'type' => 'select', 'options' => ['Semua Target', 'Siswa Tertentu', 'Kelas', 'Tingkat', 'Semua Siswa']],
                ['label' => 'Jenis', 'type' => 'select', 'options' => ['Semua Jenis', 'Minat Karier', 'Kesejahteraan Siswa', 'Gaya Belajar']],
                ['label' => 'Cari asesmen/siswa', 'type' => 'search'],
            ],
            'career-study' => [
                ['label' => 'Jenis', 'type' => 'select', 'options' => ['Semua Jenis', 'Karier', 'Studi Lanjut']],
                ['label' => 'Bidang', 'type' => 'select', 'options' => ['Semua Bidang', 'Teknologi', 'Pendidikan', 'Kesehatan', 'Teknik']],
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Publik', 'Draf']],
                ['label' => 'Cari referensi', 'type' => 'search'],
            ],
            'student-import' => [],
            'guidance' => [
                ['label' => 'Jenis Bimbingan', 'type' => 'select', 'options' => ['Semua Jenis', 'Kelompok', 'Klasikal', 'Kelas Besar']],
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Draf', 'Dijadwalkan', 'Berlangsung', 'Selesai', 'Perlu Tindak Lanjut']],
                ['label' => 'Kelas', 'type' => 'select', 'options' => ['Semua Kelas', 'X IPA C', 'XI C', 'XII C']],
                ['label' => 'Periode', 'type' => 'date-range'],
            ],
            'counseling' => [
                ['label' => 'Jenis Konseling', 'type' => 'select', 'options' => ['Semua Jenis', 'Individu', 'Kelompok']],
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Dijadwalkan', 'Berlangsung', 'Selesai', 'Perlu Tindak Lanjut']],
                ['label' => 'Periode', 'type' => 'date-range'],
            ],
            'parent-collaboration' => [
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Dijadwalkan', 'Berlangsung', 'Selesai', 'Perlu Tindak Lanjut']],
                ['label' => 'Kelas', 'type' => 'select', 'options' => ['Semua Kelas', 'X IPA C', 'XI C', 'XII C']],
                ['label' => 'Periode', 'type' => 'date-range'],
                ['label' => 'Cari siswa/orang tua', 'type' => 'search'],
            ],
            'home-visits' => [
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Draf', 'Dijadwalkan', 'Berlangsung', 'Selesai', 'Dibatalkan']],
                ['label' => 'Prioritas', 'type' => 'select', 'options' => ['Semua Prioritas', 'Sedang', 'Tinggi', 'Mendesak']],
                ['label' => 'Periode', 'type' => 'date-range'],
                ['label' => 'Cari siswa/alamat', 'type' => 'search'],
            ],
            'case-conferences' => [
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Draf', 'Dijadwalkan', 'Berlangsung', 'Selesai', 'Perlu Tindak Lanjut']],
                ['label' => 'Pimpinan', 'type' => 'select', 'options' => ['Semua', 'Ustadz Koordinator BK 1', 'Guru BK 1', 'Guru BK 2']],
                ['label' => 'Periode', 'type' => 'date-range'],
                ['label' => 'Cari siswa/kasus', 'type' => 'search'],
            ],
            'assignments' => [
                ['label' => 'Jenis Tugas', 'type' => 'select', 'options' => ['Semua Jenis', 'Kelas Binaan', 'Tugas Layanan', 'Tindak Lanjut', 'Koordinasi']],
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Draf', 'Ditugaskan', 'Dibaca', 'Berjalan', 'Selesai', 'Dibatalkan']],
                ['label' => 'Prioritas', 'type' => 'select', 'options' => ['Semua Prioritas', 'Rendah', 'Sedang', 'Tinggi', 'Mendesak']],
                ['label' => 'Cari tugas/guru', 'type' => 'search'],
            ],
            default => [
                ['label' => 'Status', 'type' => 'select', 'options' => ['Semua Status', 'Aktif', 'Selesai']],
                ['label' => 'Periode', 'type' => 'date-range'],
                ['label' => 'Pencarian', 'type' => 'search'],
            ],
        };
    }

    private function finalDemoTimeline(string $featureKey, string $screenKey, array $demo): array
    {
        if (in_array($screenKey, ['detail', 'review', 'notes', 'result', 'decision'], true)) {
            return $demo['steps'] ?? [];
        }

        return [];
    }

    private function finalDemoNotes(string $featureKey, string $screenKey, string $role, array $demo): array
    {
        $notes = $demo['detail_cards'] ?? [];

        if ($screenKey === 'notes') {
            $notes[] = ['title' => 'Catatan Rahasia', 'body' => 'Catatan rinci hanya terlihat untuk Guru BK dan Koordinator BK.'];
        }

        if ($screenKey === 'review') {
            $notes[] = ['title' => 'Tinjauan BK', 'body' => 'Pengajuan dapat diterima, dikembalikan, atau dijadwalkan sebagai tindak lanjut.'];
        }

        if ($role === 'siswa' || $role === 'orang-tua') {
            $notes[] = ['title' => 'Informasi yang Ditampilkan', 'body' => 'Halaman ini hanya menampilkan ringkasan yang aman untuk peran pengguna.'];
        }

        return $notes;
    }

    private function finalPrimaryAction(string $featureKey, string $screenKey, string $role): array
    {
        $canCreate = in_array($screenKey, ['index', 'inbox', 'careers', 'study', 'manage'], true);
        $staff = ['admin', 'koordinator-bk', 'guru-bk'];
        $createLabels = [
            'consultation' => 'Ajukan Konsultasi',
            'messages' => 'Tulis Pesan',
            'assessments' => 'Buat Asesmen',
            'career-study' => 'Tambah Referensi',
            'student-import' => 'Unggah Data',
            'guidance' => 'Tambah Bimbingan',
            'counseling' => 'Tambah Konseling',
            'parent-collaboration' => 'Tambah Kolaborasi',
            'home-visits' => 'Tambah Kunjungan',
            'case-conferences' => 'Tambah Konferensi',
            'assignments' => 'Buat Tugas',
        ];

        if (! $canCreate || ! isset($createLabels[$featureKey])) {
            return [];
        }

        if (in_array($featureKey, ['assessments', 'career-study', 'guidance', 'counseling', 'parent-collaboration', 'home-visits', 'case-conferences'], true)
            && ! in_array($role, $staff, true)) {
            return [];
        }

        // Konsultasi & Pengaduan hanya dibuat oleh pengaju (Siswa, Orang Tua, Wali Kelas).
        // Guru BK & Koordinator BK meninjau/menindaklanjuti, bukan membuat pengajuan.
        if ($featureKey === 'consultation' && ! in_array($role, ['admin', 'wali-kelas', 'siswa', 'orang-tua'], true)) {
            return [];
        }

        $target = match ($featureKey) {
            'messages' => 'compose',
            'student-import' => 'upload',
            default => 'create',
        };
        if ($featureKey === 'assignments' && ! in_array($role, ['admin', 'koordinator-bk'], true)) {
            return [];
        }

        return [
            'label' => $createLabels[$featureKey],
            'url' => $this->urlWithRole('prototype/demo/' . $featureKey . '/' . $target, $role),
            'icon' => 'mdi mdi-plus-circle',
        ];
    }

    /**
     * Hak hapus (Delete) per fitur x peran, mengikuti Matriks CRUD.
     * Tanda * (terbatas) di matriks diartikan: hanya atas data miliknya sendiri.
     */
    private function canDeleteForRole(string $featureKey, string $role): bool
    {
        if ($role === 'admin') {
            return true; // mode pratinjau/audit
        }

        $deleteRoles = [
            'consultation'         => ['koordinator-bk', 'guru-bk', 'siswa', 'orang-tua'],
            'notifications'        => ['koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
            'messages'             => ['koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'],
            'assessments'          => ['koordinator-bk', 'guru-bk'],
            'career-study'         => ['koordinator-bk', 'guru-bk'],
            'guidance'             => ['koordinator-bk', 'guru-bk'],
            'counseling'           => ['koordinator-bk', 'guru-bk'],
            'parent-collaboration' => ['koordinator-bk', 'guru-bk'],
            'home-visits'          => ['koordinator-bk', 'guru-bk'],
            'case-conferences'     => ['koordinator-bk'], // Guru BK hanya C,R,U
            'assignments'          => ['koordinator-bk'], // Guru BK hanya R,U
        ];

        return in_array($role, $deleteRoles[$featureKey] ?? [], true);
    }

    private function decorateFlowPage(string $featureKey, string $pageKey, array $page, string $role): array
    {
        $demo = $this->demoForRole($featureKey, $role);
        $pageType = (string) ($page['type'] ?? 'list');

        $page['type'] = $pageType;
        $page['metrics'] = $page['metrics'] ?? ($demo['metrics'] ?? []);
        $page['records'] = $page['records'] ?? $this->flowRecords($featureKey, $pageKey, $role, $demo);
        $page['form_fields'] = $page['form_fields'] ?? $this->flowFormFields($featureKey, $pageKey, $pageType, $demo);
        $page['cards'] = $page['cards'] ?? $this->flowCards($featureKey, $pageKey, $role, $demo);
        $page['timeline'] = $page['timeline'] ?? ($demo['steps'] ?? []);
        $page['actions'] = $page['actions'] ?? ($demo['allowed_actions'] ?? []);
        $page['privacy_note'] = $page['privacy_note'] ?? ($demo['privacy_note'] ?? '');
        $page['empty_text'] = $page['empty_text'] ?? 'Belum ada data contoh pada halaman ini.';

        return $page;
    }

    private function flowRecords(string $featureKey, string $pageKey, string $role, array $demo): array
    {
        $base = $demo['records'] ?? [];

        if ($featureKey === 'student-import') {
            return match ($pageKey) {
                'template' => $this->finalDemoRecords($featureKey, 'template', $role, $demo),
                'upload' => [],
                default => $base,
            };
        }

        return match ($pageKey) {
            'agenda' => [
                ['Waktu' => '12 Jun 2026 08.00', 'Agenda' => 'Bimbingan X IPA C', 'Petugas' => 'Guru BK 1', 'Status' => 'Dijadwalkan'],
                ['Waktu' => '13 Jun 2026 09.00', 'Agenda' => 'Konseling Siswa 2', 'Petugas' => 'Guru BK 2', 'Status' => 'Dijadwalkan'],
                ['Waktu' => '18 Jun 2026 14.00', 'Agenda' => 'Kunjungan rumah Siswa 1', 'Petugas' => 'Guru BK 1', 'Status' => 'Menunggu konfirmasi'],
            ],
            'priority' => [
                ['Prioritas' => 'Tinggi', 'Siswa' => 'Siswa 1', 'Kebutuhan' => 'Konfirmasi kunjungan rumah', 'Status' => 'Berjalan'],
                ['Prioritas' => 'Mendesak', 'Siswa' => 'Siswa 2', 'Kebutuhan' => 'Konferensi kasus', 'Status' => 'Dijadwalkan'],
            ],
            'compose', 'create', 'builder', 'assign', 'request', 'new', 'prepare', 'invite', 'upload' => [],
            'thread' => [
                ['Waktu' => '09.15', 'Pengirim' => 'Guru BK', 'Pesan' => 'Mohon konfirmasi jadwal konsultasi.', 'Status' => 'Terkirim'],
                ['Waktu' => '09.20', 'Pengirim' => $this->roleLabel($role), 'Pesan' => 'Baik, saya bisa hadir sesuai jadwal.', 'Status' => 'Dibaca'],
            ],
            'answer' => [
                ['No' => '1', 'Pertanyaan' => 'Bidang kegiatan apa yang paling kamu sukai?', 'Jawaban' => 'Belum diisi'],
                ['No' => '2', 'Pertanyaan' => 'Saya senang menyelesaikan masalah dengan logika.', 'Jawaban' => 'Belum diisi'],
                ['No' => '3', 'Pertanyaan' => 'Saya nyaman berdiskusi tentang rencana masa depan.', 'Jawaban' => 'Belum diisi'],
            ],
            'results', 'review' => [
                ['Siswa' => 'Siswa 2', 'Asesmen' => 'Minat Karier', 'Hasil' => 'Teknologi dan analitis', 'Status' => 'Selesai'],
                ['Siswa' => 'Siswa 1', 'Asesmen' => 'Kesejahteraan Siswa', 'Hasil' => 'Sedang diproses', 'Status' => 'Berjalan'],
            ],
            'saved', 'student-interest' => [
                ['Siswa' => 'Siswa 2', 'Pilihan' => 'Pengembang Perangkat Lunak', 'Jenis' => 'Karier', 'Status' => 'Tersimpan'],
                ['Siswa' => 'Siswa 1', 'Pilihan' => 'Bimbingan dan Konseling - UPI', 'Jenis' => 'Studi Lanjut', 'Status' => 'Tersimpan'],
            ],
            'calendar' => [
                ['Tanggal' => '12 Jun 2026', 'Kegiatan' => 'Bimbingan X IPA C', 'Lokasi' => 'Kelas X IPA C', 'Status' => 'Dijadwalkan'],
                ['Tanggal' => '13 Jun 2026', 'Kegiatan' => 'Konseling Siswa 2', 'Lokasi' => 'Ruang BK 1', 'Status' => 'Dijadwalkan'],
                ['Tanggal' => '20 Jun 2026', 'Kegiatan' => 'Konferensi kasus', 'Lokasi' => 'Ruang Rapat BK', 'Status' => 'Dijadwalkan'],
            ],
            'participants' => [
                ['Nama' => 'Siswa 2', 'Peran' => 'Siswa terkait', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Belum Hadir'],
                ['Nama' => 'Guru BK 1', 'Peran' => 'Guru BK', 'Undangan' => 'Konfirmasi', 'Kehadiran' => 'Belum Hadir'],
                ['Nama' => 'Orang Tua Siswa 1', 'Peran' => 'Orang tua', 'Undangan' => 'Diundang', 'Kehadiran' => 'Belum Hadir'],
            ],
            'status-history' => [
                ['Tanggal' => '1 Jun 2026', 'Status' => 'Ditugaskan', 'Oleh' => 'Ustadz Koordinator BK 1', 'Catatan' => 'Kelas binaan ditetapkan.'],
                ['Tanggal' => '3 Jun 2026', 'Status' => 'Berjalan', 'Oleh' => 'Guru BK 2', 'Catatan' => 'Mulai memetakan kebutuhan kelas.'],
            ],
            'class-assignment' => [
                ['Kelas' => 'X IPA C', 'Guru BK' => 'Guru BK 1', 'Jumlah Siswa' => '36', 'Status' => 'Ditugaskan'],
                ['Kelas' => 'XI C', 'Guru BK' => 'Guru BK 2', 'Jumlah Siswa' => '34', 'Status' => 'Berjalan'],
            ],
            'aggregate', 'individual' => [
                ['Laporan' => 'Rekap layanan BK Juni 2026', 'Periode' => 'Juni 2026', 'Akses' => 'Koordinator/Guru BK', 'Status' => 'Siap'],
                ['Laporan' => 'Perkembangan Siswa 2', 'Periode' => 'Juni 2026', 'Akses' => 'Terbatas', 'Status' => 'Siap'],
            ],
            default => $base,
        };
    }

    private function flowFormFields(string $featureKey, string $pageKey, string $pageType, array $demo): array
    {
        if (! in_array($pageType, ['form', 'compose', 'filter', 'answer'], true)) {
            return [];
        }

        return match ($pageKey) {
            'preferences' => [
                ['label' => 'Jenis notifikasi', 'type' => 'select', 'options' => ['Jadwal', 'Pesan', 'Tugas', 'Status pengajuan']],
                ['label' => 'Cara tampil', 'type' => 'select', 'options' => ['Tampilkan semua', 'Hanya yang penting']],
            ],
            'compose' => [
                ['label' => 'Penerima', 'type' => 'select', 'options' => ['Guru BK', 'Koordinator BK', 'Wali Kelas', 'Siswa', 'Orang Tua']],
                ['label' => 'Subjek', 'type' => 'text', 'placeholder' => 'Contoh: konfirmasi jadwal konsultasi'],
                ['label' => 'Isi pesan', 'type' => 'textarea', 'placeholder' => 'Tuliskan pesan dengan jelas dan sopan'],
            ],
            'answer' => [
                ['label' => 'Bidang yang diminati', 'type' => 'select', 'options' => ['Teknologi', 'Pendidikan', 'Kesehatan', 'Bisnis']],
                ['label' => 'Alasan pilihan', 'type' => 'textarea', 'placeholder' => 'Tuliskan alasan singkat'],
            ],
            'filter', 'aggregate', 'individual' => [
                ['label' => 'Periode', 'type' => 'select', 'options' => ['Juni 2026', 'Juli 2026']],
                ['label' => 'Jenis laporan', 'type' => 'select', 'options' => ['Layanan BK', 'Asesmen', 'Tindak lanjut', 'Penugasan']],
            ],
            default => $demo['form_fields'] ?? [],
        };
    }

    private function flowCards(string $featureKey, string $pageKey, string $role, array $demo): array
    {
        $cards = $demo['detail_cards'] ?? [];
        $roleLabel = $this->roleLabel($role);

        $extra = match ($pageKey) {
            'detail', 'summary' => [
                ['title' => 'Yang Dilihat ' . $roleLabel, 'body' => 'Informasi disesuaikan dengan peran aktif agar data pribadi dan catatan BK tetap aman.'],
            ],
            'review' => [
                ['title' => 'Keputusan Petugas', 'body' => 'Petugas dapat menerima, menolak, atau menjadwalkan tindak lanjut sesuai kebutuhan siswa.'],
            ],
            'confidential-notes' => [
                ['title' => 'Catatan Rahasia', 'body' => 'Isi lengkap hanya untuk Guru BK dan Koordinator BK yang berwenang.'],
            ],
            'follow-up' => [
                ['title' => 'Tindak Lanjut', 'body' => 'Setiap rencana tindak lanjut memiliki penanggung jawab, tanggal, dan status.'],
            ],
            default => [],
        };

        return array_merge($cards, $extra);
    }

    private function flowBlueprints(): array
    {
        $staff = ['admin', 'koordinator-bk', 'guru-bk'];
        $bkAndHomeroom = ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas'];
        $family = ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'orang-tua'];

        return [
            'dashboard' => [
                'pages' => [
                    'overview' => $this->flowPage('Beranda Dashboard', 'Ringkasan utama sesuai peran pengguna.', 'dashboard'),
                ],
            ],
            'consultation' => [
                'pages' => [
                    'list' => $this->flowPage('Daftar Konsultasi & Pengaduan', 'Antrian dan riwayat pengajuan yang relevan dengan peran aktif.', 'list'),
                    'create' => $this->flowPage('Isi Form Konsultasi atau Pengaduan', 'Form sederhana untuk menyampaikan kebutuhan, kejadian, atau permintaan bantuan.', 'form', ['admin', 'wali-kelas', 'siswa', 'orang-tua']),
                    'detail' => $this->flowPage('Detail Status Pengajuan', 'Status, ringkasan, dan balasan petugas BK.', 'detail'),
                    'review' => $this->flowPage('Tinjauan Guru BK', 'Petugas meninjau isi pengajuan dan menentukan tindak lanjut.', 'form', $staff),
                    'schedule' => $this->flowPage('Jadwalkan Tindak Lanjut', 'Pengajuan yang diterima dapat dijadikan jadwal layanan BK.', 'form', $staff),
                ],
            ],
            'notifications' => [
                'pages' => [
                    'list' => $this->flowPage('Pusat Notifikasi', 'Semua pemberitahuan penting tampil di satu tempat.', 'list'),
                    'detail' => $this->flowPage('Detail Notifikasi', 'Isi pemberitahuan dan tautan menuju halaman terkait.', 'detail'),
                ],
            ],
            'messages' => [
                'pages' => [
                    'inbox' => $this->flowPage('Kotak Masuk Pesan', 'Daftar percakapan internal yang masuk.', 'list'),
                    'compose' => $this->flowPage('Tulis Pesan', 'Kirim pesan kepada pihak yang berkaitan dengan layanan BK.', 'compose'),
                    'thread' => $this->flowPage('Detail Percakapan', 'Riwayat pesan dan balasan dalam satu thread.', 'conversation'),
                    'sent' => $this->flowPage('Pesan Terkirim', 'Daftar pesan yang sudah dikirim pengguna.', 'list'),
                ],
            ],
            'assessments' => [
                'pages' => [
                    'list' => $this->flowPage('Daftar Asesmen', 'Daftar asesmen aktif dan riwayat pengerjaan.', 'list'),
                    'builder' => $this->flowPage('Buat Asesmen', 'Guru BK menyusun pertanyaan dan pengaturan asesmen.', 'form', $staff),
                    'questions' => $this->flowPage('Pertanyaan Asesmen', 'Guru BK/Koordinator menyusun pilihan ganda, pilihan jamak, skala penilaian, dan esai.', 'detail', $staff),
                    'assign' => $this->flowPage('Tugaskan Asesmen', 'Pilih siswa, kelas, atau angkatan penerima asesmen.', 'form', $staff),
                    'answer' => $this->flowPage('Isi Asesmen', 'Siswa menjawab pertanyaan yang ditugaskan.', 'answer', ['admin', 'siswa']),
                    'student-preview' => $this->flowPage('Pratinjau Pengerjaan Siswa', 'Guru BK/Koordinator melihat tampilan asesmen seperti yang akan dikerjakan siswa.', 'answer', $staff),
                    'results' => $this->flowPage('Hasil Asesmen', 'Ringkasan hasil yang boleh dilihat sesuai peran.', 'detail'),
                    'review' => $this->flowPage('Tinjauan Guru BK', 'Guru BK membaca hasil dan menambahkan catatan pendampingan.', 'detail', $staff),
                ],
            ],
            'career-study' => [
                'pages' => [
                    'career-catalog' => $this->flowPage('Katalog Karier', 'Daftar pilihan karier yang dapat dieksplorasi siswa.', 'catalog'),
                    'study-catalog' => $this->flowPage('Katalog Studi Lanjut', 'Daftar kampus, jurusan, dan informasi studi lanjut.', 'catalog'),
                    'detail' => $this->flowPage('Detail Karier atau Studi', 'Informasi lengkap pilihan yang sedang dibuka.', 'detail'),
                    'saved' => $this->flowPage('Pilihan Tersimpan', 'Pilihan karier/studi yang disimpan siswa.', 'list'),
                    'manage' => $this->flowPage('Kelola Referensi', 'Guru BK menambah dan memperbarui referensi.', 'form', $staff),
                    'student-interest' => $this->flowPage('Minat Siswa', 'Guru BK melihat arah minat siswa sebagai bahan bimbingan.', 'list', $bkAndHomeroom),
                ],
            ],
            'student-import' => [
                'pages' => [
                    'upload' => $this->flowPage('Unggah File Impor', 'Halaman mengikuti fitur Impor Siswa yang sudah ada pada Admin: unduh template, pilih file, lalu unggah untuk diproses.', 'form', ['admin', 'koordinator-bk', 'wali-kelas']),
                    'template' => $this->flowPage('Template Impor', 'Template contoh yang digunakan untuk mengisi data siswa dan data orang tua/wali dalam satu file.', 'detail', ['admin', 'koordinator-bk', 'wali-kelas']),
                ],
            ],
            'guidance' => [
                'pages' => [
                    'list' => $this->flowPage('Daftar Bimbingan', 'Jadwal dan riwayat bimbingan klasikal atau kelompok.', 'list'),
                    'create' => $this->flowPage('Buat Jadwal Bimbingan', 'Guru BK menentukan kelas, materi, waktu, dan tempat.', 'form', $staff),
                    'detail' => $this->flowPage('Detail Bimbingan', 'Detail berisi jadwal, peserta, catatan materi, dan tindak lanjut.', 'detail', $staff),
                ],
            ],
            'counseling' => [
                'pages' => [
                    'schedule' => $this->flowPage('Jadwal Konseling', 'Jadwal konseling yang terlihat sesuai peran.', 'calendar'),
                    'request' => $this->flowPage('Ajukan Jadwal Konseling', 'Hanya untuk staf sesuai hak akses.', 'form', $staff),
                    'detail' => $this->flowPage('Detail Konseling', 'Detail berisi ringkasan, catatan sesuai hak akses, peserta, dan tindak lanjut.', 'detail', $staff),
                ],
            ],
            'parent-collaboration' => [
                'pages' => [
                    'list' => $this->flowPage('Daftar Kolaborasi Orang Tua', 'Jadwal dan riwayat koordinasi dengan orang tua.', 'list'),
                    'create' => $this->flowPage('Buat Jadwal Kolaborasi', 'Guru BK membuat jadwal pertemuan dengan orang tua.', 'form', $staff),
                    'detail' => $this->flowPage('Detail Kolaborasi', 'Detail berisi undangan, kehadiran, ringkasan, dan tindak lanjut.', 'detail', $staff),
                ],
            ],
            'home-visits' => [
                'pages' => [
                    'list' => $this->flowPage('Daftar Kunjungan Rumah', 'Jadwal dan riwayat kunjungan rumah.', 'list'),
                    'create' => $this->flowPage('Buat Jadwal Kunjungan', 'Guru BK menentukan siswa, alamat, jadwal, dan tujuan.', 'form', $staff),
                    'detail' => $this->flowPage('Detail Kunjungan Rumah', 'Detail berisi alamat, peserta, persiapan, hasil, dan tindak lanjut.', 'detail', $staff),
                ],
            ],
            'case-conferences' => [
                'pages' => [
                    'list' => $this->flowPage('Daftar Konferensi Kasus', 'Daftar konferensi kasus yang melibatkan peran aktif.', 'list'),
                    'create' => $this->flowPage('Buat Konferensi Kasus', 'Koordinator/Guru BK membuat agenda konferensi kasus.', 'form', $staff),
                    'detail' => $this->flowPage('Detail Konferensi Kasus', 'Detail berisi peserta, kronologi, catatan rapat, keputusan, dan tindak lanjut.', 'detail', $staff),
                ],
            ],
            'assignments' => [
                'pages' => [
                    'list' => $this->flowPage('Daftar Penugasan', 'Daftar tugas dari Koordinator BK kepada Guru BK.', 'list', $staff),
                    'create' => $this->flowPage('Buat Tugas', 'Koordinator BK memberi instruksi dan batas waktu.', 'form', ['admin', 'koordinator-bk']),
                    'class-assignment' => $this->flowPage('Kelas Binaan Guru BK', 'Penetapan kelas yang menjadi binaan Guru BK.', 'list', $staff),
                    'detail' => $this->flowPage('Detail Tugas', 'Instruksi, prioritas, dan catatan tugas.', 'detail', $staff),
                ],
            ],
            'reports' => [
                'pages' => [
                    'index' => $this->flowPage('Pusat Laporan BK', 'Pilih jenis laporan yang akan dilihat.', 'report'),
                    'aggregate' => $this->flowPage('Laporan Agregat Layanan', 'Rekap layanan BK per periode.', 'report', $staff),
                    'individual' => $this->flowPage('Laporan Individu Siswa', 'Ringkasan perkembangan siswa sesuai batas akses.', 'report'),
                ],
            ],
        ];
    }

    private function flowPage(string $title, string $subtitle, string $type = 'list', array $roleModes = ['all']): array
    {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'type' => $type,
            'role_modes' => $roleModes,
        ];
    }

    private function diagramImagesForFeature(string $featureKey): array
    {
        $activityPrefix = 'diagram_prototipe_skripsi-Activity - ';
        $useCasePrefix = 'diagram_prototipe_skripsi-Use Case - ';
        $combinedService = 'Bimbingan, Konseling, Kolaborasi  Orang Tua, Kunjungan Rumah';

        $names = [
            'consultation' => 'Konsultasi & Pengaduan',
            'notifications' => 'Notifikasi Internal',
            'messages' => 'Pesan Internal',
            'assessments' => 'Asesmen',
            'career-study' => 'Info Karier dan Info Studi Lanjut',
            'guidance' => $combinedService,
            'counseling' => $combinedService,
            'parent-collaboration' => $combinedService,
            'home-visits' => $combinedService,
            'case-conferences' => 'Konferensi Kasus',
            'assignments' => 'Penugasan',
        ];

        if (! isset($names[$featureKey])) {
            return [];
        }

        $featureName = $names[$featureKey];
        $diagrams = [
            [
                'label' => 'Activity Diagram',
                'type' => 'activity',
                'file' => $activityPrefix . $featureName . '.drawio.png',
                'folder' => 'activityDiagram',
                'description' => 'Activity Diagram adalah gambar alur langkah-langkah saat fitur ini digunakan, dari mulai sampai selesai — seperti peta langkah. Membantu melihat urutan kegiatan: dimulai dari apa, lalu apa berikutnya, termasuk bila ada pilihan atau percabangan.',
            ],
            [
                'label' => 'Use Case Diagram',
                'type' => 'use-case',
                'file' => $useCasePrefix . $featureName . '.drawio.png',
                'folder' => 'useCaseDiagram',
                'description' => 'Use Case Diagram adalah gambar yang menunjukkan siapa (peran pengguna) dapat melakukan apa saja pada fitur ini. Setiap peran digambarkan sebagai "aktor" (orang), dan tindakan yang bisa dilakukannya digambarkan sebagai oval. Membantu melihat fitur ini dipakai oleh siapa dan untuk keperluan apa.',
            ],
        ];

        return array_values(array_filter($diagrams, static function (array $diagram): bool {
            return is_file(ROOTPATH . 'backupNInformasi/diagram/gambarDariDrawio/' . $diagram['folder'] . '/' . $diagram['file']);
        }));
    }

    private function crudDiagramUrl(): string
    {
        $file = 'diagram_prototipe_skripsi-CRUD-SemuaFiturPengembangan.drawio.png';
        $path = ROOTPATH . 'backupNInformasi/diagram/gambarDariDrawio/CRUD/' . $file;
        if (! is_file($path)) {
            return '';
        }

        return base_url('prototype/diagram-image/crud/' . rawurlencode($file));
    }

    private function diagramGroups(): array
    {
        $path = ROOTPATH . 'backupNInformasi/diagram/diagram_prototipe_skripsi.drawio';
        $groups = [
            'activity' => [],
            'use_case' => [],
            'wireframe' => [],
            'other' => [],
        ];

        if (! is_file($path)) {
            return $groups;
        }

        $xml = @simplexml_load_file($path);
        if (! $xml) {
            return $groups;
        }

        foreach ($xml->diagram as $diagram) {
            $name = (string) $diagram['name'];
            $lower = strtolower($name);
            if (str_starts_with($lower, 'activity')) {
                $groups['activity'][] = $name;
            } elseif (str_starts_with($lower, 'use case')) {
                $groups['use_case'][] = $name;
            } elseif (str_starts_with($lower, 'wireframe')) {
                $groups['wireframe'][] = $name;
            } else {
                $groups['other'][] = $name;
            }
        }

        return $groups;
    }

    private function roleAccessSummary(string $role): array
    {
        return match ($role) {
            'admin' => [
                'Admin dipakai untuk menguji semua halaman prototipe dan mengelola akses contoh.',
                'Admin tidak menjadi aktor utama proses BK, sehingga isi layanan tetap mengikuti peran BK.',
            ],
            'koordinator-bk' => [
                'Memantau layanan lintas Guru BK, meninjau laporan, dan memberi penugasan.',
                'Dapat melihat ringkasan kasus rahasia sesuai kebutuhan koordinasi BK.',
            ],
            'guru-bk' => [
                'Mengelola layanan BK, asesmen, konsultasi, catatan peserta, dan tindak lanjut.',
                'Menjadi pemilik utama catatan rinci konseling yang bersifat rahasia.',
            ],
            'wali-kelas' => [
                'Mengirim konsultasi/pengaduan dan melihat ringkasan terbatas untuk siswa kelas binaan.',
                'Tidak melihat catatan konseling rahasia.',
            ],
            'siswa' => [
                'Mengajukan konsultasi, menerima notifikasi, mengisi asesmen, dan melihat jadwal/ringkasan yang relevan.',
                'Tidak melihat laporan orang tua atau catatan rahasia internal BK.',
            ],
            'orang-tua' => [
                'Mengajukan konsultasi, berkomunikasi dengan BK, dan melihat informasi perkembangan anak yang boleh dibagikan.',
                'Tidak melihat catatan konseling rinci yang bersifat internal BK.',
            ],
            default => [],
        };
    }

    private function roleAccessProfile(string $role, string $key): array
    {
        $commonActions = [
            'admin' => ['Lihat semua halaman contoh', 'Uji tampilan per peran'],
            'koordinator-bk' => ['Pantau data', 'Tinjau status', 'Unduh ringkasan'],
            'guru-bk' => ['Tambah data', 'Ubah status', 'Catat tindak lanjut'],
            'wali-kelas' => ['Kirim masukan', 'Lihat ringkasan kelas'],
            'siswa' => ['Ajukan data', 'Lihat status saya'],
            'orang-tua' => ['Ajukan data', 'Lihat status anak'],
        ];

        $notes = [
            'admin' => 'Tampilan Admin berfungsi untuk audit prototipe dan pemeriksaan akses, bukan aktor utama layanan BK.',
            'koordinator-bk' => 'Tampilan Koordinator BK menekankan pemantauan, penugasan, dan validasi tindak lanjut.',
            'guru-bk' => 'Tampilan Guru BK menekankan pencatatan layanan, kerahasiaan data, dan tindak lanjut siswa.',
            'wali-kelas' => 'Tampilan Wali Kelas hanya menerima ringkasan yang relevan dengan pembinaan kelas.',
            'siswa' => 'Tampilan Siswa hanya melihat data pribadi, jadwal, dan status pengajuan miliknya.',
            'orang-tua' => 'Tampilan Orang Tua hanya melihat informasi anak yang memang boleh dibagikan oleh BK.',
        ];

        $profile = [
            'role_note' => $notes[$role] ?? 'Tampilan mengikuti peran pengguna aktif.',
            'allowed_actions' => $commonActions[$role] ?? ['Lihat halaman contoh'],
        ];

        if ($key === 'assignments' && $role === 'guru-bk') {
            $profile['allowed_actions'] = ['Lihat tugas', 'Ubah status tugas', 'Tambah catatan progres'];
        }

        if (in_array($key, ['counseling', 'case-conferences'], true) && in_array($role, ['wali-kelas', 'siswa', 'orang-tua'], true)) {
            $profile['privacy_note'] = 'Data yang terlihat pada tampilan ini hanya ringkasan terbatas. Catatan rinci tetap berada pada Guru BK dan Koordinator BK.';
        }

        if ($key === 'consultation' && in_array($role, ['siswa', 'orang-tua', 'wali-kelas'], true)) {
            $profile['form_title'] = 'Form Pengajuan';
        }

        if ($key === 'consultation' && in_array($role, ['koordinator-bk', 'guru-bk'], true)) {
            $profile['form_title'] = 'Form Tinjauan Petugas';
        }

        if ($key === 'student-import' && $role === 'wali-kelas') {
            $profile['form_fields'] = [
                ['label' => 'Pilih file Excel', 'type' => 'file', 'placeholder' => '.xlsx,.xls,.csv'],
            ];
            $profile['detail_cards'] = [
                ['title' => 'Batas Akses', 'body' => 'Wali Kelas direncanakan dapat membantu impor sesuai kelas perwaliannya. Bentuk prototype tetap mengikuti halaman Impor Siswa yang sudah ada.'],
                ['title' => 'Template', 'body' => 'Data orang tua/wali diisi dalam template siswa yang sama melalui kolom Nama Ayah Kandung, Nama Ibu Kandung, dan Nama Wali.'],
            ];
        }

        return $profile;
    }

    private function demoData(): array
    {
        return [
            'dashboard' => [
                'metrics' => [
                    ['label' => 'Agenda BK bulan ini', 'value' => '18', 'tone' => 'primary'],
                    ['label' => 'Konsultasi aktif', 'value' => '4', 'tone' => 'danger'],
                    ['label' => 'Tugas berjalan', 'value' => '3', 'tone' => 'info'],
                    ['label' => 'Asesmen belum selesai', 'value' => '2', 'tone' => 'warning'],
                ],
                'records' => [
                    ['Tanggal' => '12 Jun 2026', 'Jenis' => 'Bimbingan', 'Kegiatan' => 'Bimbingan X IPA C', 'Petugas' => 'Guru BK 1', 'Status' => 'Dijadwalkan'],
                    ['Tanggal' => '13 Jun 2026', 'Jenis' => 'Konseling', 'Kegiatan' => 'Konseling Siswa 2', 'Petugas' => 'Guru BK 2', 'Status' => 'Dijadwalkan'],
                    ['Tanggal' => '20 Jun 2026', 'Jenis' => 'Konferensi Kasus', 'Kegiatan' => 'Adaptasi belajar Siswa 2', 'Petugas' => 'Ustadz Koordinator BK 1', 'Status' => 'Menunggu konfirmasi'],
                ],
                'form_title' => 'Filter Ringkasan',
                'form_fields' => [
                    ['label' => 'Periode', 'type' => 'select', 'options' => ['Juni 2026', 'Juli 2026']],
                    ['label' => 'Jenis data', 'type' => 'select', 'options' => ['Semua', 'Layanan BK', 'Asesmen', 'Tugas']],
                ],
                'steps' => ['Pilih peran', 'Baca prioritas', 'Buka detail agenda', 'Tindak lanjuti'],
                'detail_cards' => [
                    ['title' => 'Prioritas Hari Ini', 'body' => 'Konseling Siswa 2 dan tugas bimbingan klasikal menjadi prioritas contoh minggu ini.'],
                    ['title' => 'Catatan Akses', 'body' => 'Setiap peran melihat isi dashboard yang berbeda sesuai kebutuhan dan kerahasiaan.'],
                ],
            ],
            'consultation' => [
                'metrics' => [
                    ['label' => 'Diajukan', 'value' => '1', 'tone' => 'warning'],
                    ['label' => 'Ditinjau', 'value' => '1', 'tone' => 'info'],
                    ['label' => 'Dijadwalkan', 'value' => '1', 'tone' => 'primary'],
                    ['label' => 'Selesai', 'value' => '1', 'tone' => 'success'],
                ],
                'records' => [
                    ['Kode' => 'KPG-001', 'Pelapor' => 'Siswa 2', 'Siswa' => 'Siswa 2', 'Jenis' => 'Konsultasi', 'Kategori' => 'Belajar', 'Judul' => 'Fokus belajar', 'Prioritas' => 'Sedang', 'Status' => 'Dijadwalkan'],
                    ['Kode' => 'KPG-002', 'Pelapor' => 'Orang Tua Siswa 1', 'Siswa' => 'Siswa 1', 'Jenis' => 'Laporan Orang Tua', 'Kategori' => 'Motivasi', 'Judul' => 'Motivasi belajar', 'Prioritas' => 'Tinggi', 'Status' => 'Diterima'],
                    ['Kode' => 'KPG-003', 'Pelapor' => 'Wali Kelas 1', 'Siswa' => 'Siswa Contoh', 'Jenis' => 'Permintaan Konseling', 'Kategori' => 'Kelas', 'Judul' => 'Motivasi belajar', 'Prioritas' => 'Sedang', 'Status' => 'Ditinjau'],
                    ['Kode' => 'KPG-004', 'Pelapor' => 'Siswa 1', 'Siswa' => 'Siswa 1', 'Jenis' => 'Pengaduan', 'Kategori' => 'Relasi teman', 'Judul' => 'Butuh bantuan bicara dengan teman', 'Prioritas' => 'Sedang', 'Status' => 'Diajukan'],
                ],
                'form_title' => 'Form Konsultasi & Pengaduan',
                'form_fields' => [
                    ['label' => 'Jenis pengajuan', 'type' => 'select', 'options' => ['Konsultasi', 'Pengaduan', 'Permintaan Konseling', 'Laporan Orang Tua']],
                    ['label' => 'Judul', 'type' => 'text', 'placeholder' => 'Contoh: ingin konsultasi rencana belajar'],
                    ['label' => 'Keterangan', 'type' => 'textarea', 'placeholder' => 'Tuliskan kronologi atau kebutuhan secara singkat'],
                    ['label' => 'Prioritas', 'type' => 'select', 'options' => ['Rendah', 'Sedang', 'Tinggi', 'Mendesak']],
                ],
                'steps' => ['Diajukan', 'Ditinjau BK', 'Diterima/Ditolak', 'Dijadwalkan', 'Selesai'],
                'detail_cards' => [
                    ['title' => 'Konversi Layanan', 'body' => 'Pengajuan valid dapat dijadikan konseling, kolaborasi orang tua, kunjungan rumah, atau konferensi kasus.'],
                    ['title' => 'Kerahasiaan', 'body' => 'Pengajuan siswa/orang tua tidak otomatis terlihat oleh siswa lain atau wali kelas kecuali ringkasan memang dibagikan.'],
                ],
            ],
            'notifications' => [
                'metrics' => [
                    ['label' => 'Belum dibaca', 'value' => '3', 'tone' => 'danger'],
                    ['label' => 'Jadwal', 'value' => '2', 'tone' => 'primary'],
                    ['label' => 'Tugas', 'value' => '1', 'tone' => 'info'],
                    ['label' => 'Status pengajuan', 'value' => '2', 'tone' => 'success'],
                ],
                'records' => [
                    ['Waktu' => 'Hari ini', 'Judul' => 'Tugas baru dari Koordinator BK', 'Kategori' => 'Penugasan', 'Status' => 'Belum dibaca'],
                    ['Waktu' => 'Kemarin', 'Judul' => 'Konseling Siswa 2 dijadwalkan', 'Kategori' => 'Konseling', 'Status' => 'Belum dibaca'],
                    ['Waktu' => '6 Jun', 'Judul' => 'Kolaborasi orang tua selesai dicatat', 'Kategori' => 'Kolaborasi', 'Status' => 'Dibaca'],
                ],
                'form_title' => 'Kirim Notifikasi Contoh',
                'form_fields' => [
                    ['label' => 'Penerima', 'type' => 'select', 'options' => ['Guru BK', 'Wali Kelas', 'Siswa', 'Orang Tua']],
                    ['label' => 'Judul', 'type' => 'text', 'placeholder' => 'Contoh: jadwal konseling berubah'],
                    ['label' => 'Isi', 'type' => 'textarea', 'placeholder' => 'Tuliskan pesan singkat notifikasi'],
                ],
                'steps' => ['Dibuat sistem/petugas', 'Masuk pusat notifikasi', 'Dibaca pengguna', 'Arahkan ke detail'],
                'detail_cards' => [
                    ['title' => 'Fungsi Utama', 'body' => 'Mengurangi informasi terlewat pada jadwal, tugas, konsultasi, dan tindak lanjut.'],
                ],
            ],
            'messages' => [
                'metrics' => [
                    ['label' => 'Percakapan aktif', 'value' => '4', 'tone' => 'primary'],
                    ['label' => 'Belum dibaca', 'value' => '3', 'tone' => 'danger'],
                    ['label' => 'Melibatkan orang tua', 'value' => '1', 'tone' => 'warning'],
                    ['label' => 'Terkait kasus', 'value' => '1', 'tone' => 'dark'],
                ],
                'records' => [
                    ['Dari' => 'Guru BK 1', 'Subjek' => 'Koordinasi bimbingan klasikal', 'Kepada' => 'Wali Kelas 1', 'Status' => 'Belum dibaca'],
                    ['Dari' => 'Orang Tua Siswa 1', 'Subjek' => 'Jadwal konsultasi lanjutan', 'Kepada' => 'Guru BK 1', 'Status' => 'Belum dibaca'],
                    ['Dari' => 'Ustadz Koordinator BK 1', 'Subjek' => 'Undangan konferensi kasus', 'Kepada' => 'Guru BK 2', 'Status' => 'Belum dibaca'],
                ],
                'form_title' => 'Tulis Pesan',
                'form_fields' => [
                    ['label' => 'Penerima', 'type' => 'select', 'options' => ['Koordinator BK', 'Guru BK', 'Wali Kelas', 'Siswa', 'Orang Tua']],
                    ['label' => 'Subjek', 'type' => 'text', 'placeholder' => 'Contoh: koordinasi tindak lanjut'],
                    ['label' => 'Isi pesan', 'type' => 'textarea', 'placeholder' => 'Tuliskan pesan yang ingin disampaikan'],
                ],
                'steps' => ['Tulis pesan', 'Pilih penerima', 'Kirim', 'Balas di thread'],
                'detail_cards' => [
                    ['title' => 'Jejak Komunikasi', 'body' => 'Pesan internal menjadi riwayat koordinasi tanpa perlu memindahkan data rahasia ke aplikasi luar.'],
                ],
            ],
            'assessments' => [
                'metrics' => [
                    ['label' => 'Asesmen aktif', 'value' => '2', 'tone' => 'success'],
                    ['label' => 'Siswa ditugaskan', 'value' => '5', 'tone' => 'primary'],
                    ['label' => 'Selesai', 'value' => '1', 'tone' => 'info'],
                    ['label' => 'Belum selesai', 'value' => '2', 'tone' => 'warning'],
                ],
                'records' => [
                    ['Judul' => 'Asesmen Minat Karier', 'Jenis' => 'Minat Karier', 'Penilaian' => 'Survei', 'Target' => 'XI C', 'Pertanyaan' => '4', 'Peserta' => '34', 'Status' => 'Aktif'],
                    ['Judul' => 'Pemetaan Kesejahteraan Siswa', 'Jenis' => 'Kesejahteraan Siswa', 'Penilaian' => 'Skor Saja', 'Target' => 'Semua siswa', 'Pertanyaan' => '10', 'Peserta' => '120', 'Status' => 'Aktif'],
                    ['Judul' => 'Asesmen Rencana Belajar Siswa 2', 'Jenis' => 'Gaya Belajar', 'Penilaian' => 'Lulus/Tidak Lulus', 'Target' => 'Siswa Tertentu', 'Pertanyaan' => '6', 'Peserta' => '1', 'Status' => 'Selesai'],
                ],
                'form_title' => 'Form Asesmen Contoh',
                'form_fields' => [
                    ['label' => 'Judul asesmen', 'type' => 'text', 'placeholder' => 'Contoh: asesmen minat belajar'],
                    ['label' => 'Target', 'type' => 'select', 'options' => ['Siswa Tertentu', 'Kelas', 'Tingkat', 'Semua siswa']],
                    ['label' => 'Pertanyaan contoh', 'type' => 'textarea', 'placeholder' => 'Contoh: saya mengetahui bidang karier yang diminati'],
                ],
                'steps' => ['Buat asesmen', 'Tugaskan siswa', 'Siswa mengerjakan', 'Guru BK meninjau hasil'],
                'detail_cards' => [
                    ['title' => 'Hasil Contoh', 'body' => 'Siswa 2 memiliki kecenderungan minat teknologi dan perlu diskusi studi lanjut.'],
                    ['title' => 'Hal yang Perlu Diuji', 'body' => 'Nanti tanggal aktif, durasi pengerjaan, jumlah percobaan, dan hasil asesmen perlu diuji agar tidak membingungkan pengguna.'],
                ],
            ],
            'career-study' => [
                'metrics' => [
                    ['label' => 'Referensi karier', 'value' => '4', 'tone' => 'primary'],
                    ['label' => 'Kampus/prodi', 'value' => '3', 'tone' => 'info'],
                    ['label' => 'Pilihan tersimpan', 'value' => '3', 'tone' => 'success'],
                    ['label' => 'Perlu kurasi', 'value' => '1', 'tone' => 'warning'],
                ],
                'records' => [
                    ['Nama' => 'Pengembang Perangkat Lunak', 'Jenis' => 'Karier', 'Bidang' => 'Teknologi', 'Status' => 'Publik'],
                    ['Nama' => 'Universitas Pendidikan Indonesia', 'Jenis' => 'Studi Lanjut', 'Bidang' => 'Pendidikan', 'Status' => 'Publik'],
                    ['Nama' => 'Konselor Pendidikan', 'Jenis' => 'Karier', 'Bidang' => 'Pendidikan', 'Status' => 'Publik'],
                ],
                'form_title' => 'Tambah Referensi',
                'form_fields' => [
                    ['label' => 'Nama referensi', 'type' => 'text', 'placeholder' => 'Contoh: Teknik Informatika'],
                    ['label' => 'Jenis', 'type' => 'select', 'options' => ['Karier', 'Studi Lanjut']],
                    ['label' => 'Keterangan', 'type' => 'textarea', 'placeholder' => 'Tuliskan deskripsi singkat dan jalur masuk'],
                ],
                'steps' => ['Guru BK menambah referensi', 'Siswa membuka katalog', 'Siswa menyimpan minat', 'Guru BK melihat arah minat'],
                'detail_cards' => [
                    ['title' => 'Pilihan Siswa 2', 'body' => 'Pengembang Perangkat Lunak dan ITB tersimpan sebagai contoh minat.'],
                    ['title' => 'Pilihan Siswa 1', 'body' => 'Konselor Pendidikan dan UPI tersimpan sebagai contoh minat.'],
                ],
            ],
            'student-import' => [
                'metrics' => [],
                'records' => [],
                'form_title' => 'Unggah File Impor',
                'form_fields' => [
                    ['label' => 'Pilih file Excel', 'type' => 'file', 'placeholder' => '.xlsx,.xls,.csv'],
                ],
                'steps' => ['Download template Excel', 'Isi data siswa atau gunakan file sekolah', 'Unggah file yang sudah diisi', 'Sistem memvalidasi dan memproses data'],
                'detail_cards' => [
                    ['title' => 'Template Impor', 'body' => 'Template yang dipakai sama dengan template pada fitur Impor Siswa Admin. Data orang tua/wali berada di file siswa yang sama.'],
                    ['title' => 'Petunjuk', 'body' => 'Pastikan NISN, nama, tanggal lahir, dan jenis kelamin tersedia sebelum file diunggah.'],
                ],
            ],
            'guidance' => [
                'metrics' => [
                    ['label' => 'Bimbingan terjadwal', 'value' => '1', 'tone' => 'primary'],
                    ['label' => 'Peserta kelas', 'value' => '36', 'tone' => 'info'],
                    ['label' => 'Materi siap', 'value' => '1', 'tone' => 'success'],
                    ['label' => 'Tindak lanjut', 'value' => '1', 'tone' => 'warning'],
                ],
                'records' => [
                    ['Kode' => 'BIM-001', 'Jenis Layanan' => 'Bimbingan', 'Judul' => 'Etika media sosial', 'Jenis Bimbingan' => 'Klasikal', 'Kelas' => 'X IPA C', 'Guru BK' => 'Guru BK 1', 'Jadwal' => '12 Jun 2026 08.00', 'Durasi' => '60 menit', 'Lokasi' => 'Kelas X IPA C', 'Status' => 'Dijadwalkan'],
                    ['Kode' => 'BIM-002', 'Jenis Layanan' => 'Bimbingan', 'Judul' => 'Rencana belajar pekanan', 'Jenis Bimbingan' => 'Kelompok', 'Kelas' => 'XI C', 'Guru BK' => 'Guru BK 2', 'Jadwal' => '25 Jun 2026 10.00', 'Durasi' => '45 menit', 'Lokasi' => 'Ruang BK 2', 'Status' => 'Draf'],
                ],
                'form_title' => 'Form Bimbingan',
                'form_fields' => [
                    ['label' => 'Judul bimbingan', 'type' => 'text', 'placeholder' => 'Contoh: etika pergaulan digital'],
                    ['label' => 'Jenis bimbingan', 'type' => 'select', 'options' => ['Klasikal', 'Kelompok', 'Kelas Besar']],
                    ['label' => 'Kelas/peserta', 'type' => 'select', 'options' => ['X IPA C', 'XI C', 'XII C']],
                    ['label' => 'Durasi menit', 'type' => 'text', 'placeholder' => 'Contoh: 60'],
                    ['label' => 'Catatan materi', 'type' => 'textarea', 'placeholder' => 'Tuliskan materi dan target bimbingan'],
                ],
                'steps' => ['Buat jadwal', 'Undang peserta', 'Catat kehadiran', 'Catat materi dan tindak lanjut'],
                'detail_cards' => [
                    ['title' => 'Daftar Peserta Bersama', 'body' => 'Peserta bimbingan dicatat pada satu daftar peserta yang sama sehingga Guru BK tidak perlu mengisi data berulang.'],
                ],
            ],
            'counseling' => [
                'metrics' => [
                    ['label' => 'Jadwal aktif', 'value' => '1', 'tone' => 'primary'],
                    ['label' => 'Selesai bulan ini', 'value' => '1', 'tone' => 'success'],
                    ['label' => 'Rahasia BK', 'value' => '2', 'tone' => 'dark'],
                    ['label' => 'Perlu tindak lanjut', 'value' => '1', 'tone' => 'warning'],
                ],
                'records' => [
                    ['Kode' => 'KSL-001', 'Jenis Layanan' => 'Konseling', 'Judul' => 'Konseling individu Siswa 2', 'Jenis Konseling' => 'Individu', 'Siswa/Kelas' => 'Siswa 2', 'Guru BK' => 'Guru BK 2', 'Jadwal' => '13 Jun 2026 09.00', 'Durasi' => '45 menit', 'Status' => 'Dijadwalkan'],
                    ['Kode' => 'KSL-002', 'Jenis Layanan' => 'Konseling', 'Judul' => 'Konseling kelompok manajemen waktu', 'Jenis Konseling' => 'Kelompok', 'Siswa/Kelas' => 'XII C', 'Guru BK' => 'Guru BK 1', 'Jadwal' => '3 Jun 2026 10.00', 'Durasi' => '50 menit', 'Status' => 'Selesai'],
                ],
                'form_title' => 'Form Konseling',
                'form_fields' => [
                    ['label' => 'Siswa/kelas', 'type' => 'select', 'options' => ['Siswa 2', 'Siswa 1', 'XII C']],
                    ['label' => 'Jenis konseling', 'type' => 'select', 'options' => ['Individu', 'Kelompok']],
                    ['label' => 'Topik umum', 'type' => 'text', 'placeholder' => 'Contoh: fokus belajar'],
                    ['label' => 'Jadwal dan durasi', 'type' => 'text', 'placeholder' => 'Contoh: 13 Juni 2026, 09.00, 45 menit'],
                    ['label' => 'Deskripsi masalah', 'type' => 'textarea', 'placeholder' => 'Tuliskan masalah utama sesuai batas kerahasiaan'],
                    ['label' => 'Catatan rahasia', 'type' => 'textarea', 'placeholder' => 'Catatan ini hanya untuk Guru BK/Koordinator BK'],
                ],
                'steps' => ['Terima kebutuhan', 'Jadwalkan sesi', 'Lakukan konseling', 'Catat tindak lanjut rahasia'],
                'detail_cards' => [
                    ['title' => 'Kerahasiaan Catatan', 'body' => 'Catatan rinci konseling tidak diberikan ke siswa, orang tua, atau wali kelas secara otomatis.'],
                ],
            ],
            'parent-collaboration' => [
                'metrics' => [
                    ['label' => 'Pertemuan selesai', 'value' => '1', 'tone' => 'success'],
                    ['label' => 'Orang tua terlibat', 'value' => '1', 'tone' => 'primary'],
                    ['label' => 'Ringkasan dikirim', 'value' => '1', 'tone' => 'info'],
                    ['label' => 'Tindak lanjut', 'value' => '1', 'tone' => 'warning'],
                ],
                'records' => [
                    ['Kode' => 'KOT-001', 'Jenis Layanan' => 'Kolaborasi Orang Tua', 'Siswa' => 'Siswa 1', 'Orang Tua' => 'Ibu Siti, Bapak Ahmad', 'Topik' => 'Motivasi belajar', 'Jadwal' => '6 Jun 2026 13.00', 'Pihak Hadir' => 'Orang tua, Guru BK 1, Wali Kelas 1', 'Lokasi' => 'Ruang BK 1', 'Status' => 'Selesai'],
                    ['Kode' => 'KOT-002', 'Jenis Layanan' => 'Kolaborasi Orang Tua', 'Siswa' => 'Siswa 2', 'Orang Tua' => 'Orang Tua Siswa 2', 'Topik' => 'Adaptasi belajar', 'Jadwal' => '20 Jun 2026 13.00', 'Pihak Hadir' => 'Menunggu konfirmasi', 'Lokasi' => 'Ruang BK 1', 'Status' => 'Terjadwal'],
                ],
                'form_title' => 'Form Kolaborasi Orang Tua',
                'form_fields' => [
                    ['label' => 'Siswa', 'type' => 'select', 'options' => ['Siswa 2', 'Siswa 1', 'Siswa Contoh']],
                    ['label' => 'Nama-nama orang tua', 'type' => 'textarea', 'placeholder' => 'Tulis nama orang tua/wali yang dihubungi'],
                    ['label' => 'Topik/Judul', 'type' => 'text', 'placeholder' => 'Contoh: motivasi belajar'],
                    ['label' => 'Pihak yang menghadiri', 'type' => 'textarea', 'placeholder' => 'Tulis satu per satu, termasuk pihak luar aplikasi bila ada'],
                    ['label' => 'Ringkasan', 'type' => 'textarea', 'placeholder' => 'Tuliskan hasil diskusi dan tindak lanjut'],
                ],
                'steps' => ['Jadwalkan pertemuan', 'Undang orang tua', 'Catat hasil', 'Bagikan ringkasan sesuai hak akses'],
                'detail_cards' => [
                    ['title' => 'Akses Siswa', 'body' => 'Laporan orang tua tidak otomatis ditampilkan kepada siswa. BK menentukan ringkasan yang aman dibagikan.'],
                ],
            ],
            'home-visits' => [
                'metrics' => [
                    ['label' => 'Kunjungan terjadwal', 'value' => '1', 'tone' => 'primary'],
                    ['label' => 'Menunggu konfirmasi', 'value' => '1', 'tone' => 'warning'],
                    ['label' => 'Peserta diundang', 'value' => '2', 'tone' => 'info'],
                    ['label' => 'Catatan rahasia', 'value' => '1', 'tone' => 'dark'],
                ],
                'records' => [
                    ['Kode' => 'KRM-001', 'Jenis Layanan' => 'Kunjungan Rumah', 'Siswa/Kelas' => 'Siswa 1 / X IPA C', 'Wali Kelas' => 'Wali Kelas 1', 'Alamat' => 'Jl. Raya Banjaran No. 12', 'Judul/Masalah' => 'Motivasi belajar', 'Jadwal' => '18 Jun 2026 14.00', 'Status' => 'Dijadwalkan'],
                    ['Kode' => 'KRM-002', 'Jenis Layanan' => 'Kunjungan Rumah', 'Siswa/Kelas' => 'Siswa 2 / XI C', 'Wali Kelas' => 'Wali Kelas 1', 'Alamat' => 'Kp. Banjaran', 'Judul/Masalah' => 'Adaptasi belajar', 'Jadwal' => 'Belum ditentukan', 'Status' => 'Draf'],
                ],
                'form_title' => 'Form Kunjungan Rumah',
                'form_fields' => [
                    ['label' => 'Siswa', 'type' => 'select', 'options' => ['Siswa 2', 'Siswa 1', 'Siswa Contoh']],
                    ['label' => 'Kelas/Wali kelas', 'type' => 'text', 'placeholder' => 'Contoh: XI C / Wali Kelas 1'],
                    ['label' => 'Alamat kunjungan', 'type' => 'textarea', 'placeholder' => 'Tuliskan alamat atau snapshot alamat'],
                    ['label' => 'Jadwal', 'type' => 'text', 'placeholder' => 'Contoh: 18 Juni 2026, 14.00'],
                    ['label' => 'Judul/masalah', 'type' => 'textarea', 'placeholder' => 'Tuliskan masalah dan tujuan kunjungan'],
                ],
                'steps' => ['Tentukan kebutuhan', 'Koordinasi orang tua', 'Lakukan kunjungan', 'Catat hasil dan tindak lanjut'],
                'detail_cards' => [
                    ['title' => 'Lokasi/Alamat', 'body' => 'Alamat kunjungan ditulis langsung pada data kunjungan rumah agar mudah diperiksa sebelum Guru BK berangkat.'],
                ],
            ],
            'case-conferences' => [
                'metrics' => [
                    ['label' => 'Konferensi aktif', 'value' => '1', 'tone' => 'dark'],
                    ['label' => 'Peserta diundang', 'value' => '4', 'tone' => 'primary'],
                    ['label' => 'Keputusan belum final', 'value' => '1', 'tone' => 'warning'],
                    ['label' => 'Tindak lanjut', 'value' => '1', 'tone' => 'info'],
                ],
                'records' => [
                    ['Kode' => 'KFR-001', 'Jenis Layanan' => 'Konferensi Kasus', 'Masalah/Topik' => 'Adaptasi belajar Siswa 2', 'Pimpinan' => 'Ustadz Koordinator BK 1', 'Siswa' => 'Siswa 2', 'Tanggal' => '20 Jun 2026', 'Tempat' => 'Ruang Rapat BK', 'Status' => 'Dijadwalkan'],
                    ['Kode' => 'KFR-002', 'Jenis Layanan' => 'Konferensi Kasus', 'Masalah/Topik' => 'Koordinasi motivasi belajar', 'Pimpinan' => 'Guru BK 1', 'Siswa' => 'Siswa 1', 'Tanggal' => 'Belum ditentukan', 'Tempat' => 'Belum ditentukan', 'Status' => 'Draf'],
                ],
                'form_title' => 'Form Konferensi Kasus',
                'form_fields' => [
                    ['label' => 'Siswa/kasus', 'type' => 'select', 'options' => ['Siswa 2', 'Siswa 1', 'Siswa Contoh']],
                    ['label' => 'Masalah/topik/judul', 'type' => 'text', 'placeholder' => 'Contoh: adaptasi belajar Siswa 2'],
                    ['label' => 'Tanggal dan tempat', 'type' => 'text', 'placeholder' => 'Contoh: 20 Juni 2026, Ruang Rapat BK'],
                    ['label' => 'Pihak yang menghadiri', 'type' => 'textarea', 'placeholder' => 'Tulis satu per satu, termasuk pihak luar aplikasi bila ada'],
                    ['label' => 'Kronologi singkat', 'type' => 'textarea', 'placeholder' => 'Tuliskan kronologi yang aman dibahas bersama'],
                    ['label' => 'Rencana keputusan', 'type' => 'textarea', 'placeholder' => 'Tuliskan keputusan atau tindak lanjut'],
                ],
                'steps' => ['Identifikasi kasus', 'Undang peserta', 'Diskusi dan keputusan', 'Tugaskan tindak lanjut'],
                'detail_cards' => [
                    ['title' => 'Batas Informasi', 'body' => 'Isi konferensi kasus membedakan kronologi, ringkasan diskusi, keputusan, dan tindak lanjut agar hak akses lebih jelas.'],
                ],
            ],
            'assignments' => [
                'metrics' => [
                    ['label' => 'Tugas aktif', 'value' => '5', 'tone' => 'primary'],
                    ['label' => 'Kelas binaan', 'value' => '2', 'tone' => 'success'],
                    ['label' => 'Berjalan', 'value' => '2', 'tone' => 'info'],
                    ['label' => 'Mendesak', 'value' => '1', 'tone' => 'danger'],
                ],
                'records' => [
                    ['Kode' => 'TGS-001', 'Jenis Tugas' => 'Kelas Binaan', 'Tugas' => 'Binaan kelas X IPA C', 'Penerima' => 'Guru BK 1', 'Kelas' => 'X IPA C', 'Prioritas' => 'Sedang', 'Batas Waktu' => '30 Jun 2026', 'Status' => 'Ditugaskan'],
                    ['Kode' => 'TGS-002', 'Jenis Tugas' => 'Kelas Binaan', 'Tugas' => 'Binaan kelas XI C', 'Penerima' => 'Guru BK 2', 'Kelas' => 'XI C', 'Prioritas' => 'Sedang', 'Batas Waktu' => '30 Jun 2026', 'Status' => 'Berjalan'],
                    ['Kode' => 'TGS-005', 'Jenis Tugas' => 'Koordinasi', 'Tugas' => 'Konferensi kasus Siswa 2', 'Penerima' => 'Guru BK 2', 'Kelas' => 'XI C', 'Prioritas' => 'Mendesak', 'Batas Waktu' => '20 Jun 2026', 'Status' => 'Ditugaskan'],
                ],
                'form_title' => 'Form Penugasan',
                'form_fields' => [
                    ['label' => 'Jenis tugas', 'type' => 'select', 'options' => ['Kelas Binaan', 'Tugas Layanan', 'Tindak Lanjut', 'Koordinasi']],
                    ['label' => 'Penerima', 'type' => 'select', 'options' => ['Guru BK 1', 'Guru BK 2', 'Guru BK 3']],
                    ['label' => 'Judul tugas', 'type' => 'text', 'placeholder' => 'Contoh: tindak lanjut konsultasi siswa'],
                    ['label' => 'Instruksi', 'type' => 'textarea', 'placeholder' => 'Tuliskan instruksi dan batas waktu'],
                ],
                'steps' => ['Koordinator membuat tugas', 'Guru BK membaca', 'Status diperbarui', 'Riwayat tersimpan'],
                'detail_cards' => [
                    ['title' => 'Riwayat Status', 'body' => 'Setiap perubahan status tugas tersimpan sebagai riwayat sehingga Koordinator BK dan Guru BK dapat melihat perkembangan tugas.'],
                ],
            ],
            'reports' => [
                'metrics' => [
                    ['label' => 'Laporan agregat', 'value' => '4', 'tone' => 'primary'],
                    ['label' => 'Laporan individual', 'value' => '3', 'tone' => 'info'],
                    ['label' => 'Laporan siap ditinjau', 'value' => '5', 'tone' => 'success'],
                    ['label' => 'Data rahasia', 'value' => 'Terbatas', 'tone' => 'dark'],
                ],
                'records' => [
                    ['Laporan' => 'Rekap layanan BK Juni 2026', 'Periode' => 'Juni 2026', 'Kelas' => 'Semua Kelas', 'Siswa' => '-', 'Jenis Laporan' => 'Semua layanan BK', 'Status' => 'Siap'],
                    ['Laporan' => 'Perkembangan Siswa 2', 'Periode' => 'Juni 2026', 'Kelas' => 'XI C', 'Siswa' => 'Siswa 2', 'Jenis Laporan' => 'Semua fitur/catatan BK', 'Status' => 'Siap'],
                    ['Laporan' => 'Progres asesmen minat karier', 'Periode' => 'Juni 2026', 'Kelas' => 'XI C', 'Siswa' => 'Siswa 2', 'Jenis Laporan' => 'Asesmen', 'Status' => 'Siap'],
                ],
                'form_title' => 'Saring Laporan',
                'form_fields' => [
                    ['label' => 'Jenis laporan', 'type' => 'select', 'options' => ['Agregat layanan', 'Individu siswa', 'Asesmen', 'Tugas BK']],
                    ['label' => 'Periode', 'type' => 'select', 'options' => ['Juni 2026', 'Juli 2026']],
                ],
                'steps' => ['Pilih filter', 'Terapkan hak akses', 'Tampilkan ringkasan', 'Tinjau isi laporan'],
                'detail_cards' => [
                    ['title' => 'Dashboard dan Laporan', 'body' => 'Halaman ini menunjukkan contoh ringkasan data yang akan tampil setelah fitur pengembangan digunakan.'],
                ],
            ],
        ];
    }
}
