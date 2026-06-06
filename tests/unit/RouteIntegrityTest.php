<?php

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RouteIntegrityTest extends CIUnitTestCase
{
    public function testAllExplicitRouteHandlersResolveToExistingControllerMethods(): void
    {
        $routes = $this->loadApplicationRoutes();
        $missing = [];

        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'CLI', '*'] as $verb) {
            foreach ($routes->getRoutes($verb) as $uri => $handler) {
                if (! is_string($handler) || ! str_contains($handler, '::')) {
                    continue;
                }

                [$class, $method] = explode('::', preg_replace('#/\$[0-9].*$#', '', $handler), 2);
                $class = ltrim($class, '\\');
                $method = preg_replace('/[^A-Za-z0-9_].*$/', '', $method);

                if (! class_exists($class) || ! method_exists($class, $method)) {
                    $missing[] = sprintf('%s %s => %s::%s', $verb, $uri, $class, $method);
                }
            }
        }

        $missing = array_values(array_unique($missing));

        $this->assertSame([], $missing, "Route handler berikut belum punya controller/method:\n" . implode("\n", $missing));
    }

    public function testStaticViewReferencesHaveMatchingFiles(): void
    {
        $missing = [];
        $patterns = [
            '~(?<![\w])view\s*\(\s*[\'"]([^\'"]+)[\'"]~',
            '~->(?:include|extend)\s*\(\s*[\'"]([^\'"]+)[\'"]~',
        ];

        foreach ($this->phpFiles([APPPATH . 'Controllers', APPPATH . 'Services', APPPATH . 'Views', APPPATH . 'Config']) as $file) {
            $content = file_get_contents($file) ?: '';

            foreach ($patterns as $pattern) {
                if (! preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches[1] as [$view, $offset]) {
                    if ($view === '' || str_contains($view, '$') || str_contains($view, '{')) {
                        continue;
                    }

                    $path = APPPATH . 'Views/' . str_replace('\\', '/', $view);
                    if (! str_ends_with($path, '.php')) {
                        $path .= '.php';
                    }

                    if (! is_file($path)) {
                        $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                        $missing[] = sprintf('%s:%d references %s', $this->relativePath($file), $line, $view);
                    }
                }
            }
        }

        $this->assertSame([], $missing, "View berikut direferensikan tetapi filenya tidak ada:\n" . implode("\n", $missing));
    }

    public function testControllersDoNotContainAdHocDebugOutput(): void
    {
        $debugCalls = [];
        $pattern = '/\b(print_r|var_dump|dump|dd)\s*\(/';

        foreach ($this->phpFiles([APPPATH . 'Controllers']) as $file) {
            $content = file_get_contents($file) ?: '';

            if (! preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[1] as [$function, $offset]) {
                $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                $debugCalls[] = sprintf('%s:%d contains %s()', $this->relativePath($file), $line, $function);
            }
        }

        $this->assertSame([], $debugCalls, "Masih ada output debug di controller:\n" . implode("\n", $debugCalls));
    }

    public function testStudentViolationSubmissionCreateViewUsesStudentFlow(): void
    {
        $content = file_get_contents(APPPATH . 'Views/student/violation_submissions/create.php') ?: '';

        $this->assertStringContainsString("base_url('student/dashboard')", $content);
        $this->assertStringContainsString("base_url('student/violation-submissions/store')", $content);
        $this->assertStringContainsString("base_url('student/violation-submissions')", $content);
        $this->assertStringNotContainsString("base_url('parent/dashboard')", $content);
        $this->assertStringNotContainsString("base_url('parent/violation-submissions/store')", $content);
    }

    public function testStudentScheduleRequestViewIsCounselingRequestForm(): void
    {
        $content = file_get_contents(APPPATH . 'Views/student/schedule/request.php') ?: '';

        foreach (['session_date', 'session_time', 'topic', 'description'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $content);
        }

        $this->assertStringContainsString("route_to('student.schedule.store')", $content);
        $this->assertStringNotContainsString('parent.children.sessions.detail', $content);
        $this->assertStringNotContainsString('parent/child/', $content);
    }

    public function testViolationExportIsRemovedWithDeprecatedFeature(): void
    {
        $content = file_get_contents(APPPATH . 'Controllers/ExportController.php') ?: '';

        $this->assertStringNotContainsString('function violations', $content);
        $this->assertStringNotContainsString('vc.point_deduction', $content);
        $this->assertStringNotContainsString('violations.points', $content);
    }

    public function testPermissionFiltersAreDeclaredInBothSeeders(): void
    {
        $routePermissions = $this->routePermissionNames();
        $permissionSeeder = $this->permissionNamesFromPermissionSeeder();
        $databaseSeeder = $this->permissionNamesFromDatabaseSeeder();

        $missingInPermissionSeeder = array_values(array_diff($routePermissions, $permissionSeeder));
        $missingInDatabaseSeeder = array_values(array_diff($routePermissions, $databaseSeeder));

        $this->assertSame([], $missingInPermissionSeeder, 'Permission route belum ada di PermissionSeeder: ' . implode(', ', $missingInPermissionSeeder));
        $this->assertSame([], $missingInDatabaseSeeder, 'Permission route belum ada di DatabaseSeeder: ' . implode(', ', $missingInDatabaseSeeder));
        $this->assertSame($permissionSeeder, $databaseSeeder, 'Urutan permission di PermissionSeeder dan DatabaseSeeder harus sama agar ID tetap konsisten.');
    }

    private function loadApplicationRoutes(): RouteCollection
    {
        $routes = service('routes');
        $routes->resetRoutes();
        require APPPATH . 'Config/Routes.php';

        return $routes;
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    private function phpFiles(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function routePermissionNames(): array
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php') ?: '';
        preg_match_all('/permission:([^\'"]+)/', $routes, $matches);

        $permissions = [];
        foreach ($matches[1] as $filter) {
            foreach (explode(',', $filter) as $permission) {
                $permission = trim($permission);
                if ($permission !== '' && $permission !== 'any' && preg_match('/^[a-z_]+$/', $permission)) {
                    $permissions[] = $permission;
                }
            }
        }

        $permissions = array_values(array_unique($permissions));
        sort($permissions);

        return $permissions;
    }

    /**
     * @return list<string>
     */
    private function permissionNamesFromPermissionSeeder(): array
    {
        $content = file_get_contents(APPPATH . 'Database/Seeds/PermissionSeeder.php') ?: '';
        preg_match_all('/\'permission_name\'\s*=>\s*\'([^\']+)\'/', $content, $matches);

        return $matches[1];
    }

    /**
     * @return list<string>
     */
    private function permissionNamesFromDatabaseSeeder(): array
    {
        $content = file_get_contents(APPPATH . 'Database/Seeds/DatabaseSeeder.php') ?: '';
        $start = strpos($content, '$permissions = [');
        $end = $start === false ? false : strpos($content, '];', $start);

        $this->assertNotFalse($start, 'Blok $permissions di DatabaseSeeder tidak ditemukan.');
        $this->assertNotFalse($end, 'Akhir blok $permissions di DatabaseSeeder tidak ditemukan.');

        $block = substr($content, (int) $start, (int) $end - (int) $start);
        preg_match_all('/\'([a-z_]+)\'\s*=>/', $block, $matches);

        return $matches[1];
    }

    private function relativePath(string $file): string
    {
        return str_replace('\\', '/', str_replace(rtrim(HOMEPATH, '\\/') . DIRECTORY_SEPARATOR, '', $file));
    }
}
