<?php

/**
 * File Path: app/Controllers/Parents/BaseParentController.php
 *
 * Base Controller untuk semua controller Orang Tua.
 * Di sini kita satukan helper & properti umum supaya controller turunan rapi.
 */

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @property IncomingRequest   $request
 * @property ResponseInterface $response
 * @property BaseConnection    $db
 */
abstract class BaseParentController extends BaseController
{
    /**
     * Koneksi DB yang bisa dipakai di semua controller Orang Tua.
     *
     * @var BaseConnection
     */
    protected $db;

    /**
     * initController dipanggil otomatis oleh CodeIgniter sebelum method lain.
     * Di sini kita sambungkan DB dan helper yang sering dipakai.
     */
    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        // Inisialisasi koneksi DB
        $this->db = \Config\Database::connect();

        // Helper umum (sesuaikan jika perlu)
        helper(['url', 'session']);
    }

    /**
     * Guard ringan untuk memastikan akses dari akun orang tua.
     *
     * Jika routes parent sudah memakai filter 'role:parent', method ini
     * tetap aman dipanggil (menjadi lapisan keamanan tambahan).
     */
    protected function requireParent(): void
    {
        $role = strtolower((string) (session('role_key') ?? session('role_name') ?? ''));

        // Sesuaikan daftar nama role parent di sini jika di DB berbeda
        if (! in_array($role, ['parent', 'orang tua'], true)) {
            // Bisa pilih: redirect ke login atau lempar 404.
            // Di sini redirect ke login dengan pesan error.
            redirect()
                ->to('/login')
                ->with('error', 'Anda tidak memiliki akses ke halaman Orang Tua.')
                ->send();
            exit;
        }
    }
}
