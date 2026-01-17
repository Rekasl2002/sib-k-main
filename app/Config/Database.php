<?php

/**
 * File Path: app/Config/Database.php
 * 
 * Database Configuration
 * Mengkonfigurasi koneksi database untuk aplikasi SIB-K
 * 
 * @package    SIB-K
 * @subpackage Config
 * @category   Configuration
 * @author     Development Team
 * @created    2025-01-01
 */

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
    ];

    /**
     * This database connection is used when
     * running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => 'localhost',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8mb4',
        'DBCollat'    => 'utf8mb4_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }

        // Load database configuration from .env file
        $this->loadFromEnv();
    }

    /**
     * Load database configuration from environment variables
     */
    private function loadFromEnv(): void
    {
        // Default Database Configuration
        $this->default = [
            'DSN'          => '',
            'hostname'     => env('database.default.hostname', 'localhost'),
            'username'     => env('database.default.username', 'root'),
            'password'     => env('database.default.password', ''),
            'database'     => env('database.default.database', 'sibk_mapersis31'),
            'DBDriver'     => env('database.default.DBDriver', 'MySQLi'),
            'DBPrefix'     => env('database.default.DBPrefix', ''),
            'pConnect'     => false,
            'DBDebug'      => env('database.default.DBDebug', ENVIRONMENT !== 'production'),
            'charset'      => env('database.default.charset', 'utf8mb4'),
            'DBCollat'     => env('database.default.DBCollat', 'utf8mb4_general_ci'),
            'swapPre'      => '',
            'encrypt'      => false,
            'compress'     => false,
            'strictOn'     => false,
            'failover'     => [],
            'port'         => (int) env('database.default.port', 3306),
            'numberNative' => false,
        ];

        // Test Database Configuration
        if (ENVIRONMENT === 'testing') {
            $this->tests = [
                'DSN'         => '',
                'hostname'    => env('database.tests.hostname', 'localhost'),
                'username'    => env('database.tests.username', 'root'),
                'password'    => env('database.tests.password', ''),
                'database'    => env('database.tests.database', 'sibk_testing'),
                'DBDriver'    => env('database.tests.DBDriver', 'MySQLi'),
                'DBPrefix'    => env('database.tests.DBPrefix', 'test_'),
                'pConnect'    => false,
                'DBDebug'     => true,
                'charset'     => env('database.tests.charset', 'utf8mb4'),
                'DBCollat'    => env('database.tests.DBCollat', 'utf8mb4_general_ci'),
                'swapPre'     => '',
                'encrypt'     => false,
                'compress'    => false,
                'strictOn'    => false,
                'failover'    => [],
                'port'        => (int) env('database.tests.port', 3306),
                'foreignKeys' => true,
                'busyTimeout' => 1000,
            ];
        }
    }
}
