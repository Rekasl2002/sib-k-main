<?php

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FeatureCoverageBlackBoxTest extends CIUnitTestCase
{
    public function testExistingAndPlannedFeaturesHaveApplicationSurface(): void
    {
        $surface = $this->applicationSurface();

        $features = [
            'Log In' => ['login', 'auth\\authcontroller::login', 'forgot-password', 'reset-password'],
            'Manajemen Peran dan Pengguna' => ['admin/users', 'admin/roles', 'koordinator/users', 'koordinator/staff'],
            'Profil dan Ganti Password' => ['profile/change-password', 'profilecontroller::changepassword'],
            'Manajemen Tahun Akademik' => ['academic-years', 'academicyearcontroller'],
            'Manajemen Kelas' => ['classes', 'classcontroller'],
            'Manajemen Siswa' => ['students', 'studentcontroller'],
            'Impor Siswa' => ['students/import', 'do-import', 'download-template'],
            'Manajemen Sesi Konseling' => ['sessions', 'sessioncontroller'],
            'Manajemen Kasus dan Pelanggaran' => ['cases', 'violations', 'casecontroller'],
            'Pengaturan Aplikasi' => ['settings', 'settingcontroller'],
            'Laporan' => ['reports', 'reportcontroller'],
            'Dashboard' => ['dashboard', 'dashboardcontroller'],
            'Pengaduan Pelanggaran' => ['violation-submissions', 'violationsubmissionscontroller', 'submit_violation_submissions'],
            'Notifikasi' => ['notifications', 'notificationcontroller', 'notifikasi internal'],
            'Pesan Internal' => ['messages', 'messagecontroller', 'pesan internal'],
            'Asesmen' => ['assessments', 'assessmentcontroller', 'assessmentapicontroller'],
            'Info Karier dan Info Studi Lanjut' => ['career-info', 'careercontroller', 'fitur info karier dan info studi lanjut'],
        ];

        foreach ($features as $feature => $needles) {
            foreach ($needles as $needle) {
                $this->assertStringContainsString(
                    strtolower($needle),
                    $surface,
                    sprintf('Surface aplikasi untuk fitur "%s" belum memuat "%s".', $feature, $needle)
                );
            }
        }
    }

    public function testReferenceDiagramCoversAllPlannedPrototypeFeatures(): void
    {
        $diagram = strtolower(file_get_contents(HOMEPATH . 'backupNInformasi/diagram/diagram_prototipe_skripsi.drawio') ?: '');

        $expected = [
            'activity - pengaduan pelanggaran',
            'activity - notifikasi internal',
            'activity - pesan internal',
            'activity - asesmen',
            'activity - info karier dan info studi lanjut',
            'use case - pengaduan pelanggaran',
            'use case - notifikasi internal',
            'use case - pesan internal',
            'use case - asesmen',
            'use case - info karier dan info studi lanjut',
            'erd bubble - kp + prototipe',
        ];

        foreach ($expected as $needle) {
            $this->assertStringContainsString($needle, $diagram, 'Diagram referensi belum memuat: ' . $needle);
        }
    }

    public function testReferenceDocumentsAreAvailable(): void
    {
        $documents = [
            HOMEPATH . 'backupNInformasi/fileHasilKPdanDraftSkripsi/draftSkripsiTerbaru.pdf',
            HOMEPATH . 'backupNInformasi/fileHasilKPdanDraftSkripsi/LaporanKPREKASLIF2022008rev.pdf',
            HOMEPATH . 'backupNInformasi/diagram/diagram_prototipe_skripsi.drawio',
        ];

        foreach ($documents as $document) {
            $this->assertFileExists($document);
            $this->assertGreaterThan(1024, filesize($document), basename($document) . ' tampak kosong atau tidak lengkap.');
        }
    }

    public function testForgotPasswordSupportsAdminRequestAndFutureSmtpMode(): void
    {
        $surface = $this->applicationSurface();

        foreach ([
            'password_reset_requests',
            'PasswordResetRequestService',
            'password_reset.mode',
            'admin_request',
            'smtp_link',
            'sendResetLinkEmail',
            'admin/users/edit/',
            'Permintaan reset password',
        ] as $needle) {
            $this->assertStringContainsString(
                strtolower($needle),
                $surface,
                'Flow lupa password belum menyiapkan: ' . $needle
            );
        }
    }

    private function applicationSurface(): string
    {
        $parts = [];
        $routes = $this->loadApplicationRoutes();

        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'CLI', '*'] as $verb) {
            foreach ($routes->getRoutes($verb) as $uri => $handler) {
                $parts[] = $verb . ' ' . $uri . ' ' . (is_string($handler) ? $handler : 'closure');
            }
        }

        foreach ($this->surfaceFiles() as $file) {
            $parts[] = str_replace('\\', '/', $file);
            $parts[] = file_get_contents($file) ?: '';
        }

        return strtolower(implode("\n", $parts));
    }

    private function loadApplicationRoutes(): RouteCollection
    {
        $routes = service('routes');
        $routes->resetRoutes();
        require APPPATH . 'Config/Routes.php';

        return $routes;
    }

    /**
     * @return list<string>
     */
    private function surfaceFiles(): array
    {
        $roots = [
            APPPATH . 'Config',
            APPPATH . 'Controllers',
            APPPATH . 'Database/Migrations',
            APPPATH . 'Views',
            APPPATH . 'Models',
            APPPATH . 'Services',
            APPPATH . 'Database/Seeds',
            HOMEPATH . 'backupNInformasi/diagram',
        ];
        $files = [];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['php', 'drawio'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        $files[] = HOMEPATH . '.env';
        sort($files);

        return $files;
    }
}
