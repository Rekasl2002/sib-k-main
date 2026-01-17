<?php

/**
 * File Path: app/Config/Filters.php
 *
 * Filters Configuration
 * Mendaftarkan semua filter yang akan digunakan di aplikasi
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Aliases untuk Filter classes agar mudah dibaca.
     *
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        // Built-in
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,

        // Custom
        'auth'       => \App\Filters\AuthFilter::class,
        'role'       => \App\Filters\RoleFilter::class,
        'permission' => \App\Filters\PermissionFilter::class,
        'cors'       => \App\Filters\CorsFilter::class,
    ];

    /**
     * Global filters (before & after).
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // Filter karakter ilegal (disarankan)
            'invalidchars',

            // CSRF global.
            // Catatan: kalau ada endpoint tertentu yang kamu butuh bebas CSRF (mis. webhook),
            // tambahkan di except.
            'csrf' => [
                'except' => [
                    // API biasanya tidak pakai cookie CSRF
                    'api/*',
                ],
            ],

            // Honeypot opsional
            // 'honeypot',
        ],
        'after' => [
            // Toolbar hanya nyaman di development.
            // Kalau kamu ingin otomatis nonaktif di production, biarkan ini.
            // (CI otomatis tidak tampil kalau environment bukan development, tapi filter tetap dipanggil.
            // Jika ingin benar-benar hemat, bisa kamu pindah ke env routes/filters.)
            'toolbar',

            // Secure headers (disarankan)
            'secureheaders',

            // 'honeypot',
        ],
    ];

    /**
     * Filters berdasarkan HTTP method.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [
        /**
         * Preflight OPTIONS biasanya muncul untuk request API (CORS).
         * Jika CORS filter kamu aman, ini oke.
         *
         * Jika mau lebih ketat: jangan pakai $methods, tapi pakai $filters pattern khusus api/*
         * (lihat di bawah).
         */
        // 'options' => ['cors'],
    ];

    /**
     * Pattern-based filters.
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        /**
         * Auth “pagar luar”:
         * Ini akan memaksa semua URL dengan prefix berikut wajib login.
         *
         * Catatan:
         * - Kalau kamu sudah mengatur auth di Routes.php group, ini bisa jadi dobel.
         * - Tidak salah, tapi saat debugging RBAC bisa bikin bingung.
         * - Kalau kamu mau rapi dan single source of truth, kamu bisa kosongkan bagian ini
         *   dan andalkan Routes.php.
         */
        'auth' => [
            'before' => [
                'admin/*',
                'koordinator/*',
                'counselor/*',
                'homeroom/*',
                'student/*',
                'parent/*',
                'messages/*',
                'notifications/*',
                'upload/*',
                'download/*',
                'api/*',
                'profile/*',
                'dashboard',
            ],
        ],

        /**
         * CORS hanya untuk API.
         * Ini lebih aman daripada global.
         */
        'cors' => [
            'before' => [
                'api/*',
            ],
        ],

        // 'role' dan 'permission' tetap dipanggil per-route (dengan parameter)
        // melalui konfigurasi di app/Config/Routes.php.
    ];
}
