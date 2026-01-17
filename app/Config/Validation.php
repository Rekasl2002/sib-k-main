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
    // Custom Messages (opsional)
    // --------------------------------------------------------------------

    /**
     * Pesan error (ID) untuk custom rules dari ValidationHelper.
     *
     * @var array<string, array<string, string>>
     */
    public array $customMessages = [
        'valid_phone' => [
            'valid_phone' => 'Nomor telepon harus diawali 08 dan terdiri dari 10–15 digit (format 08xxxxxxxxxx).',
        ],
        'valid_nisn' => [
            'valid_nisn' => 'NISN harus terdiri dari tepat 10 digit angka.',
        ],
        'valid_nis' => [
            'valid_nis' => 'NIS harus terdiri dari 4–20 digit angka.',
        ],
        'valid_indo_date' => [
            '_default' => '{field} harus berformat tanggal Indonesia (dd-mm-yyyy atau dd/mm/yyyy)',
        ],
        'valid_academic_year' => [
            '_default' => '{field} harus berformat tahun ajaran (YYYY/YYYY)',
        ],
        'strong_password' => [
            '_default' => '{field} minimal 6 karakter dengan kombinasi huruf dan angka',
        ],
        'valid_time' => [
            '_default' => '{field} harus berformat waktu yang valid (HH:MM)',
        ],
        'unique_with_soft_delete' => [
            '_default' => '{field} sudah digunakan',
        ],
        'valid_file_extension' => [
            '_default' => 'Ekstensi file {field} tidak diizinkan',
        ],
        'valid_file_size' => [
            '_default' => 'Ukuran file {field} terlalu besar',
        ],
        'valid_image' => [
            '_default' => '{field} harus berupa file gambar yang valid (jpg, jpeg, png, gif)',
        ],
        'valid_username' => [
            '_default' => '{field} hanya boleh mengandung huruf, angka, titik, dan underscore (3-50 karakter)',
        ],
        'valid_nik' => [
            '_default' => '{field} harus 16 digit angka',
        ],
        'valid_grade_level' => [
            '_default' => '{field} harus X, XI, atau XII',
        ],
        'valid_semester' => [
            '_default' => '{field} harus Ganjil atau Genap',
        ],
        'valid_gender' => [
            '_default' => '{field} harus L (Laki-laki) atau P (Perempuan)',
        ],
        'valid_religion' => [
            '_default' => '{field} harus dipilih dari pilihan yang tersedia',
        ],
    ];

    /**
     * Pesan error (ID) untuk default rules bawaan CI.
     * (Opsional; pakai bila ingin override pesan default.)
     *
     * @var array<string, string>
     */
    public array $indonesianMessages = [
        // Required
        'required'              => '{field} harus diisi',
        'required_with'         => '{field} harus diisi ketika {param} diisi',
        'required_without'      => '{field} harus diisi ketika {param} tidak diisi',

        // String
        'min_length'            => '{field} minimal {param} karakter',
        'max_length'            => '{field} maksimal {param} karakter',
        'exact_length'          => '{field} harus tepat {param} karakter',
        'alpha'                 => '{field} hanya boleh berisi huruf',
        'alpha_space'           => '{field} hanya boleh berisi huruf dan spasi',
        'alpha_numeric'         => '{field} hanya boleh berisi huruf dan angka',
        'alpha_numeric_space'   => '{field} hanya boleh berisi huruf, angka, dan spasi',
        'alpha_dash'            => '{field} hanya boleh berisi huruf, angka, dash, dan underscore',

        // Numbers
        'numeric'               => '{field} harus berupa angka',
        'integer'               => '{field} harus berupa bilangan bulat',
        'decimal'               => '{field} harus berupa angka desimal',
        'greater_than'          => '{field} harus lebih besar dari {param}',
        'greater_than_equal_to' => '{field} harus lebih besar atau sama dengan {param}',
        'less_than'             => '{field} harus lebih kecil dari {param}',
        'less_than_equal_to'    => '{field} harus lebih kecil atau sama dengan {param}',

        // Email & URL
        'valid_email'           => '{field} harus berupa alamat email yang valid',
        'valid_emails'          => '{field} harus berupa alamat email yang valid',
        'valid_url'             => '{field} harus berupa URL yang valid',
        'valid_ip'              => '{field} harus berupa alamat IP yang valid',

        // Database
        'is_unique'             => '{field} sudah digunakan',
        'is_not_unique'         => '{field} tidak ditemukan dalam database',

        // Date
        'valid_date'            => '{field} harus berupa tanggal yang valid',

        // File Upload
        'uploaded'              => '{field} harus diunggah',
        'max_size'              => 'Ukuran {field} maksimal {param} KB',
        'max_dims'              => 'Dimensi {field} terlalu besar',
        'mime_in'               => 'Tipe file {field} tidak valid',
        'ext_in'                => 'Ekstensi file {field} tidak valid',
        'is_image'              => '{field} harus berupa gambar',

        // Matching
        'matches'               => '{field} tidak cocok dengan {param}',
        'differs'               => '{field} harus berbeda dengan {param}',

        // Lists
        'in_list'               => '{field} harus salah satu dari: {param}',
        'not_in_list'           => '{field} tidak boleh salah satu dari: {param}',

        // Others
        'regex_match'           => '{field} tidak sesuai dengan format yang diharapkan',
        'permit_empty'          => '{field} boleh kosong',
        'field_exists'          => '{field} harus ada',
    ];

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
            'rules'  => 'required|valid_email|is_unique[users.email]',
            'errors' => [
                'required'    => 'Email harus diisi',
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
        'nis' => [
            'rules'  => 'required|valid_nis|is_unique[students.nis]',
            'errors' => [
                'required'  => 'NIS harus diisi',
                'is_unique' => 'NIS sudah terdaftar',
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
