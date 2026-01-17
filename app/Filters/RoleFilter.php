<?php

/**
 * File Path: app/Filters/RoleFilter.php
 * 
 * Role Filter
 * Middleware untuk memeriksa role user sebelum mengakses halaman tertentu
 * 
 * @package    SIB-K
 * @subpackage Filters
 * @category   Authorization
 * @author     Development Team
 * @created    2025-01-01
 * @updated    2025-11-26
 */

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Check if user has required role
     *
     * @param RequestInterface $request
     * @param array<string>|null $arguments (array of allowed roles)
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session  = \Config\Services::session();
        $response = \Config\Services::response();

        $isAjax   = method_exists($request, 'isAJAX') ? $request->isAJAX() : false;
        $path     = $request->getUri()->getPath() ?? '';
        $isApi    = str_starts_with($path, 'api/');

        // 1) Pastikan sudah login (fallback; AuthFilter juga sudah mengawal)
        if (!$session->get('is_logged_in') || !$session->get('user_id')) {
            if ($isAjax || $isApi) {
                return $response->setStatusCode(401)->setJSON([
                    'status'  => 401,
                    'message' => 'Unauthenticated. Please login.',
                ]);
            }
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // 2) Ambil role user dari session
        $rawRole  = $session->get('role_name') ?? '';
        $userRole = $this->normalizeRole($rawRole);

        // Jika tidak ada batasan role pada rute ini, loloskan
        if (empty($arguments)) {
            return;
        }

        // 3) Normalisasi daftar role yang diizinkan
        $allowed = array_map(fn($r) => $this->normalizeRole($r), (array) $arguments);

        // 4) Validasi
        if (!in_array($userRole, $allowed, true)) {
            // Log percobaan akses tanpa hak
            $u = $session->get('username') ?? ('user#'.$session->get('user_id'));
            log_message(
                'warning',
                sprintf(
                    'Unauthorized access attempt by %s (Role: %s) to %s; allowed: %s',
                    $u,
                    $rawRole ?: '-',
                    $path,
                    implode(',', $allowed)
                )
            );

            if ($isAjax || $isApi) {
                return $response->setStatusCode(403)->setJSON([
                    'status'  => 403,
                    'message' => 'Forbidden. You do not have access to this resource.',
                ]);
            }

            // 5) Redirect ke dashboard sesuai role (fail-safe untuk menghindari loop)
            $target = $this->getRedirectPath($rawRole);
            if (trim($target, '/') === trim($path, '/')) {
                $target = '/'; // fallback
            }

            return redirect()->to($target)
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }
    }

    /**
     * Allows After filters to inspect and modify the response object as needed.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelahnya
    }

    /**
     * Get redirect path based on user role (as displayed to user)
     *
     * @param string $role Original role case from session
     * @return string
     */
    private function getRedirectPath(string $role): string
    {
        // Peta berbasis label yang dipakai di aplikasi
        $paths = [
            'Admin'           => '/admin/dashboard',
            'Koordinator BK'  => '/koordinator/dashboard',
            'Guru BK'         => '/counselor/dashboard',
            'Wali Kelas'      => '/homeroom/dashboard',
            'Siswa'           => '/student/dashboard',
            'Orang Tua'       => '/parent/dashboard',
        ];

        // Cakup beberapa sinonim umum bila session menyimpan varian
        $fallbackByLower = [
            'admin'           => '/admin/dashboard',
            'koordinator bk'  => '/koordinator/dashboard',
            'coordinator'     => '/koordinator/dashboard',
            'guru bk'         => '/counselor/dashboard',
            'counselor'       => '/counselor/dashboard',
            'wali kelas'      => '/homeroom/dashboard',
            'homeroom'        => '/homeroom/dashboard',
            'siswa'           => '/student/dashboard',
            'student'         => '/student/dashboard',
            'orang tua'       => '/parent/dashboard',
            'parent'          => '/parent/dashboard',
        ];

        if (isset($paths[$role])) {
            return $paths[$role];
        }

        $lr = strtolower(trim($role));
        return $fallbackByLower[$lr] ?? '/';
    }

    /**
     * Normalize role label for comparison (lowercase + unify synonyms)
     *
     * @param string $role
     * @return string one of: admin|koordinator bk|guru bk|wali kelas|siswa|orang tua|<raw lower>
     */
    private function normalizeRole(string $role): string
    {
        $r = strtolower(trim($role));

        // Peta sinonim agar argumen di Routes fleksibel:
        // contoh penggunaan: ['role' => 'SISWA'] atau 'role:Student' tetap cocok.
        $map = [
            'admin'          => 'admin',
            'koordinator bk' => 'koordinator bk',
            'coordinator'    => 'koordinator bk',
            'guru bk'        => 'guru bk',
            'counselor'      => 'guru bk',
            'wali kelas'     => 'wali kelas',
            'homeroom'       => 'wali kelas',
            'siswa'          => 'siswa',
            'student'        => 'siswa',
            'orang tua'      => 'orang tua',
            'parent'         => 'orang tua',
            // beberapa varian uppercase yang mungkin dipakai:
            'siswas'         => 'siswa',
            'siswa/i'        => 'siswa',
            'siswa-siswi'    => 'siswa',
        ];

        return $map[$r] ?? $r;
    }
}
