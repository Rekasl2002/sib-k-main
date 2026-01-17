<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Helper permission (dan auth bila ada) supaya fungsi-fungsi seperti:
        // is_logged_in(), has_permission(), is_admin(), auth_role_id() tetap aman dipanggil.
        try {
            helper(['permission', 'auth', 'url']);
        } catch (\Throwable $e) {
            // Jika helper tidak ada, biarkan fallback berjalan.
        }

        $session = session();

        // 1) Wajib login (fallback: cek session user_id)
        $loggedIn = function_exists('is_logged_in')
            ? (bool) is_logged_in()
            : (bool) $session->get('user_id');

        if (! $loggedIn) {
            // Untuk request JSON/AJAX: kembalikan 401 JSON (biar jelas di client)
            if ($this->wantsJson($request)) {
                $res = Services::response();
                return $res->setStatusCode(401)->setJSON([
                    'status'  => 401,
                    'message' => 'Silakan login terlebih dahulu.',
                ]);
            }

            return redirect()->to(base_url('login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        // 2) Normalisasi argumen permission dari route
        // Default mode = ALL (harus punya semua permission).
        // Opsional mode ANY: gunakan "permission:any,perm1,perm2" atau "permission:or,perm1,perm2"
        [$mode, $required] = $this->parseRequiredPermissions($arguments);

        // Jika tidak ada permission yang dipersyaratkan → lolos
        if (empty($required)) {
            return null;
        }

        // 3) Admin/Administrator → bypass (tambah fallback role_id / role_name)
        if ($this->isSuperUser($session)) {
            return null;
        }

        $roleName = (string) ($session->get('role_name') ?? '');

        // 4) Ambil izin user dari session (fallback beberapa key)
        $userPermissions = $this->getSessionPermissions($session);

        // 5) Evaluasi permission
        //    - mode ALL: wajib punya SEMUA (AND)
        //    - mode ANY: cukup punya SALAH SATU (OR) [opsional]
        $missing = [];
        $hasAny  = false;

        foreach ($required as $perm) {
            $has = function_exists('has_permission')
                ? (bool) has_permission($perm)
                : in_array($perm, $userPermissions, true);

            if ($has) {
                $hasAny = true;
                if ($mode === 'any') {
                    // kalau mode ANY dan sudah ada salah satu yang cocok, boleh langsung lolos
                    return null;
                }
            } else {
                $missing[] = $perm;
            }
        }

        $allowed = ($mode === 'any') ? $hasAny : empty($missing);
        if ($allowed) {
            return null;
        }

        // Logging untuk debugging
        $username = (string) ($session->get('username') ?? $session->get('email') ?? $session->get('user_id') ?? 'unknown');
        log_message('warning', sprintf(
            'Permission denied for user %s to %s. Missing: %s (required: %s) [mode=%s]',
            $username,
            uri_string(),
            implode(', ', $missing ?: $required),
            implode(', ', $required),
            $mode
        ));

        // Untuk request JSON/AJAX: balas 403 JSON (lebih jelas di frontend)
        if ($this->wantsJson($request)) {
            $res = Services::response();
            return $res->setStatusCode(403)->setJSON([
                'status'   => 403,
                'message'  => 'Anda tidak memiliki izin untuk mengakses resource ini.',
                'required' => array_values($required),
                'missing'  => array_values($missing ?: $required),
                'mode'     => $mode,
            ]);
        }

        // HTML normal: redirect ke dashboard sesuai role
        return redirect()->to($this->getRedirectPath($roleName))
            ->with('error', 'Anda tidak memiliki izin untuk mengakses halaman tersebut.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    /**
     * Tentukan path dashboard tujuan sesuai role.
     * Bisa diberi role name (opsional); jika kosong, ambil dari session helper.
     */
    private function getRedirectPath(?string $roleName = null): string
    {
        // Jika tersedia roleName, pakai nama; jika tidak, gunakan role_id dari helper
        $name = strtolower(trim((string) $roleName));
        if ($name !== '') {
            return match ($name) {
                'administrator', 'admin'         => base_url('admin/dashboard'),
                'koordinator bk', 'koordinator'  => base_url('koordinator/dashboard'),
                'guru bk', 'counselor'           => base_url('counselor/dashboard'),
                'wali kelas', 'homeroom'         => base_url('homeroom/dashboard'),
                'siswa', 'student'               => base_url('student/dashboard'),
                'orang tua', 'parent'            => base_url('parent/dashboard'),
                default                          => base_url('dashboard'),
            };
        }

        // Fallback by role_id (jaga kompatibilitas)
        if (function_exists('auth_role_id')) {
            $roleId = (int) auth_role_id();
            return match ($roleId) {
                1       => base_url('admin/dashboard'),
                2       => base_url('koordinator/dashboard'),
                3       => base_url('counselor/dashboard'),
                4       => base_url('homeroom/dashboard'),
                5       => base_url('student/dashboard'),
                6       => base_url('parent/dashboard'),
                default => base_url('dashboard'),
            };
        }

        // Fallback paling aman
        $sid = (int) (session('role_id') ?? 0);
        return match ($sid) {
            1       => base_url('admin/dashboard'),
            2       => base_url('koordinator/dashboard'),
            3       => base_url('counselor/dashboard'),
            4       => base_url('homeroom/dashboard'),
            5       => base_url('student/dashboard'),
            6       => base_url('parent/dashboard'),
            default => base_url('dashboard'),
        };
    }

    /**
     * Parse argumen filter.
     * - Default mode: ALL (AND)
     * - Opsional mode ANY (OR):
     *   - permission:any,perm1,perm2
     *   - permission:or,perm1,perm2
     */
    private function parseRequiredPermissions($arguments): array
    {
        $args = [];

        if (is_array($arguments)) {
            $args = $arguments;
        } elseif (is_string($arguments) && $arguments !== '') {
            // just in case ada yang passing string manual
            $args = preg_split('/\s*,\s*/', $arguments, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        // trim + buang kosong
        $args = array_values(array_filter(array_map(static function ($v) {
            return trim((string) $v);
        }, $args), static fn($v) => $v !== ''));

        $mode = 'all';
        if (!empty($args)) {
            $head = strtolower($args[0]);
            if (in_array($head, ['any', 'or', 'one'], true)) {
                $mode = 'any';
                array_shift($args);
            } elseif (in_array($head, ['all', 'and'], true)) {
                $mode = 'all';
                array_shift($args);
            }
        }

        return [$mode, $args];
    }

    /**
     * Deteksi super user (admin) dengan beberapa fallback:
     * - is_admin() jika ada
     * - role_id == 1
     * - role_name mengandung admin/administrator
     */
    private function isSuperUser($session): bool
    {
        if (function_exists('is_admin') && is_admin()) {
            return true;
        }

        $rid  = (int) ($session->get('role_id') ?? 0);
        $name = strtolower((string) ($session->get('role_name') ?? ''));

        if ($rid === 1) {
            return true;
        }

        if ($name !== '' && (str_contains($name, 'admin') || str_contains($name, 'administrator'))) {
            return true;
        }

        return false;
    }

    /**
     * Ambil permission dari session dengan beberapa key fallback.
     */
    private function getSessionPermissions($session): array
    {
        $userPermissions = $session->get('auth_permissions');

        if (!is_array($userPermissions)) {
            $userPermissions = $session->get('permissions');
        }

        if (!is_array($userPermissions)) {
            $userPermissions = $session->get('permission_names');
        }

        if (!is_array($userPermissions)) {
            $userPermissions = [];
        }

        // Normalisasi string list
        $userPermissions = array_values(array_filter(array_map(static function ($v) {
            return trim((string) $v);
        }, $userPermissions), static fn($v) => $v !== ''));

        return $userPermissions;
    }

    /**
     * Untuk request AJAX/API, lebih baik balas JSON daripada redirect.
     */
    private function wantsJson(RequestInterface $request): bool
    {
        if (method_exists($request, 'isAJAX') && $request->isAJAX()) {
            return true;
        }

        $accept = strtolower((string) $request->getHeaderLine('Accept'));
        if ($accept !== '' && (str_contains($accept, 'application/json') || str_contains($accept, 'text/json'))) {
            return true;
        }

        return false;
    }
}
