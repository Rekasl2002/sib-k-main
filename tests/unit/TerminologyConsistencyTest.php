<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TerminologyConsistencyTest extends CIUnitTestCase
{
    public function testCareerStudyFeatureUsesCanonicalDisplayName(): void
    {
        $bannedPatterns = [
            '/info\s+kar(?:i|ie)r\s*\/\s*kuliah/i',
            '/info\s+kar(?:i|ie)r\s+dan\s+kuliah/i',
            '/kar(?:i|ie)r\s*\/\s*kuliah/i',
            '/kar(?:i|ie)r\s+dan\s+kuliah/i',
            '/info\s+kuliah/i',
            '/referensi\s+kuliah/i',
            '/pilihan\s+kuliah/i',
            '/\bkarir\b/i',
            '/\bkuliah\b/i',
        ];

        $violations = [];
        foreach ($this->terminologyFiles() as $file) {
            $content = str_replace('KIP Kuliah', '', file_get_contents($file) ?: '');

            foreach ($bannedPatterns as $pattern) {
                if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
                    $line = substr_count(substr($content, 0, $match[0][1]), "\n") + 1;
                    $violations[] = sprintf('%s:%d matches %s with "%s"', $this->relativePath($file), $line, $pattern, $match[0][0]);
                }
            }
        }

        if ($violations !== []) {
            $this->fail("Masih ada istilah lama:\n" . implode("\n", $violations));
        }

        $canonical = 'Fitur Info Karier dan Info Studi Lanjut';
        foreach ([
            APPPATH . 'Views/layouts/partials/sidebar.php',
            APPPATH . 'Views/student/career/explore.php',
            APPPATH . 'Views/parent/career/explore.php',
            APPPATH . 'Views/counselor/career/index.php',
            APPPATH . 'Views/homeroom_teacher/career/index.php',
            APPPATH . 'Views/koordinator/career/index.php',
            HOMEPATH . 'backupNInformasi/diagram/diagram_prototipe_skripsi.drawio',
        ] as $file) {
            $this->assertStringContainsString($canonical, file_get_contents($file) ?: '', basename($file) . ' belum memakai istilah baku.');
        }
    }

    /**
     * @return list<string>
     */
    private function terminologyFiles(): array
    {
        $roots = [
            APPPATH . 'Controllers',
            APPPATH . 'Views',
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

        sort($files);

        return $files;
    }

    private function relativePath(string $file): string
    {
        return str_replace('\\', '/', str_replace(rtrim(HOMEPATH, '\\/') . DIRECTORY_SEPARATOR, '', $file));
    }
}
