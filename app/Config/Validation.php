<?php

/**
 * File Path: app/Config/Validation.php
 *
 * Validation Configuration
 * Mendaftarkan default dan custom validation rules
 *
 * @package    SIB-K
 * @subpackage Config
 * @category   Configuration
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Gunakan STRICT rules sekali saja (tidak perlu FQN + import ganda)
use CodeIgniter\Validation\Rules;
use CodeIgniter\Validation\FormatRules;
use CodeIgniter\Validation\FileRules;
use CodeIgniter\Validation\CreditCardRules;

class Validation extends BaseConfig
{
    /**
     * Kumpulan kelas penyedia rules yang tersedia.
     * Urutan penting bila ada nama rule yang sama: yang terakhir bisa override.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,

        // Custom rule providers (punya method rule seperti valid_phone(), valid_nisn(), dsb.)
        \App\Libraries\ValidationHelper::class,

        // NOTE:
        // Kalau UserValidation kamu bukan "rule provider" (hanya berisi array rules), biasanya tidak perlu didaftarkan di ruleSets.
        // Tapi aku biarkan sesuai file kamu agar tidak mengganggu perilaku project yang sudah berjalan.
        \App\Validation\UserValidation::class,
    ];

    /**
     * View untuk menampilkan error.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Pesan validasi Indonesia
    // --------------------------------------------------------------------
    // CATATAN: pesan default (rules bawaan CI4 + custom rules ValidationHelper)
    // sekarang ada di app/Language/id/Validation.php. Berkas bahasa itulah yang
    // dibaca otomatis oleh CI4 (defaultLocale = 'id') untuk semua rule yang
    // tidak diberi pesan kustom di controller/model. Dua array lama di sini
    // ($customMessages & $indonesianMessages) dihapus karena tidak pernah
    // dibaca framework (kode mati).

    // --------------------------------------------------------------------
    // Prebuilt validation rule sets (opsional; dipakai via config('Validation')->xxx)
    // --------------------------------------------------------------------

    public array $registration = [
        'username' => [
            'rules'  => 'required|min_length[3]|max_length[50]|valid_username|is_unique[users.username]',
            'errors' => [
                'required'  => 'Username harus diisi',
                'is_unique' => 'Username sudah digunakan',
            ],
        ],
        'email' => [
            'rules'  => 'permit_empty|valid_email|is_unique[users.email]',
            'errors' => [
                'valid_email' => 'Format email tidak valid',
                'is_unique'   => 'Email sudah digunakan',
            ],
        ],
        'password' => [
            'rules'  => 'required|strong_password',
            'errors' => [
                'required' => 'Password harus diisi',
            ],
        ],
        'password_confirm' => [
            'rules'  => 'required|matches[password]',
            'errors' => [
                'required' => 'Konfirmasi password harus diisi',
                'matches'  => 'Konfirmasi password tidak cocok',
            ],
        ],
        'full_name' => [
            'rules'  => 'required|min_length[3]|max_length[255]',
            'errors' => [
                'required' => 'Nama lengkap harus diisi',
            ],
        ],
    ];

    public array $login = [
        'username' => [
            'rules'  => 'required',
            'errors' => [
                'required' => 'Username atau email harus diisi',
            ],
        ],
        'password' => [
            'rules'  => 'required',
            'errors' => [
                'required' => 'Password harus diisi',
            ],
        ],
    ];

    public array $student = [
        'nisn' => [
            'rules'  => 'required|valid_nisn|is_unique[students.nisn]',
            'errors' => [
                'required'  => 'NISN harus diisi',
                'is_unique' => 'NISN sudah terdaftar',
            ],
        ],
        'nik' => [
            'rules'  => 'permit_empty|valid_nik|is_unique[students.nik]',
            'errors' => [
                'is_unique' => 'NIK sudah terdaftar',
            ],
        ],
        'full_name' => [
            'rules'  => 'required|min_length[3]|max_length[255]',
            'errors' => [
                'required' => 'Nama lengkap harus diisi',
            ],
        ],
        'gender' => [
            'rules'  => 'required|valid_gender',
            'errors' => [
                'required' => 'Jenis kelamin harus dipilih',
            ],
        ],
        'class_id' => [
            'rules'  => 'required|is_not_unique[classes.id]',
            'errors' => [
                'required'      => 'Kelas harus dipilih',
                'is_not_unique' => 'Kelas tidak valid',
            ],
        ],
    ];

    /**
     * Academic Year (Create/Edit)
     *
     * Catatan penting:
     * - year_name TIDAK lagi dibuat unique di level validation config, supaya bisa:
     *   2024/2025 + Ganjil dan 2024/2025 + Genap.
     * - Pembatasan "maksimal 2 nama sama" dan "tidak boleh duplikat year_name+semester"
     *   sebaiknya dijaga di Service/Model (business rule), atau lewat custom rule khusus.
     */
    public array $academicYear = [
        'year_name' => [
            // Dihapus: is_unique[academic_years.year_name]
            'rules'  => 'required|regex_match[/^\d{4}\/\d{4}$/]',
            'errors' => [
                'required' => 'Tahun ajaran harus diisi',
            ],
        ],
        'start_date' => [
            'rules'  => 'required|valid_date[Y-m-d]',
            'errors' => [
                'required'   => 'Tanggal mulai harus diisi',
                'valid_date' => 'Format tanggal tidak valid',
            ],
        ],
        'end_date' => [
            'rules'  => 'required|valid_date[Y-m-d]',
            'errors' => [
                'required'   => 'Tanggal selesai harus diisi',
                'valid_date' => 'Format tanggal tidak valid',
            ],
        ],
        'semester' => [
            'rules'  => 'required|valid_semester',
            'errors' => [
                'required' => 'Semester harus dipilih',
            ],
        ],
    ];
}
