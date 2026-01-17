<?php
declare(strict_types=1);

namespace App\Validation;

final class UserValidation
{
    /**
     * ===== Pesan error standar =====
     */
    public static function messages(): array
    {
        return [
            'role_id' => [
                'required'           => 'Role wajib dipilih',
                'is_natural_no_zero' => 'Role tidak valid',
                'is_not_unique'      => 'Role tidak ditemukan',
            ],
            'username' => [
                'required'   => 'Username wajib diisi',
                'min_length' => 'Username minimal 3 karakter',
                'max_length' => 'Username maksimal 30 karakter',
                'alpha_dash' => 'Username hanya boleh huruf, angka, _ atau -',
                'is_unique'  => 'Username sudah digunakan',
            ],
            'email' => [
                'required'    => 'Email wajib diisi',
                'valid_email' => 'Format email tidak valid',
                'max_length'  => 'Email maksimal 255 karakter',
                'is_unique'   => 'Email sudah digunakan',
            ],
            'full_name' => [
                'required'   => 'Nama lengkap wajib diisi',
                'min_length' => 'Nama lengkap minimal 3 karakter',
                'max_length' => 'Nama lengkap maksimal 100 karakter',
            ],
            'phone' => [
                'valid_phone' => 'Nomor telepon harus diawali 08 dan terdiri dari 10–15 digit (format 08xxxxxxxxxx)',
            ],
            'password' => [
                'required'   => 'Password wajib diisi',
                'min_length' => 'Password minimal 6 karakter',
                'max_length' => 'Password maksimal 255 karakter',
            ],
            'password_confirm' => [
                'required' => 'Konfirmasi password wajib diisi',
                'matches'  => 'Konfirmasi password tidak sesuai',
            ],
            'is_active' => [
                'in_list' => 'Status aktif tidak valid',
            ],
            'id' => [
                'required'           => 'ID tidak valid',
                'is_natural_no_zero' => 'ID tidak valid',
            ],
        ];
    }

    /**
     * ===== Aturan CREATE (utama) =====
     */
    public static function rulesForCreate(): array
    {
        return [
            'role_id'          => 'required|is_natural_no_zero|is_not_unique[roles.id]',
            'username'         => 'required|min_length[3]|max_length[30]|alpha_dash|is_unique[users.username]',
            'email'            => 'required|valid_email|max_length[255]|is_unique[users.email]',
            'password'         => 'required|min_length[6]|max_length[255]',
            'password_confirm' => 'required|matches[password]',
            'full_name'        => 'required|min_length[3]|max_length[100]',
            // Telepon opsional, tapi kalau diisi harus valid_phone (08xxxxxxxx, 10–15 digit)
            'phone'            => 'permit_empty|valid_phone',
            'is_active'        => 'permit_empty|in_list[0,1]',
        ];
    }

    /**
     * ===== Aturan UPDATE (utama, dinamis) =====
     * - Tambahkan is_unique hanya jika nilainya berubah
     * - Sertakan field 'id' agar placeholder {id} bisa diisi
     */
    public static function rulesForUpdate(array $existing, array $input): array
    {
        $rules = [
            'id'               => 'required|is_natural_no_zero', // penting utk {id}
            'role_id'          => 'required|is_natural_no_zero|is_not_unique[roles.id]',
            'username'         => 'required|min_length[3]|max_length[30]|alpha_dash',
            'email'            => 'required|valid_email|max_length[255]',
            'full_name'        => 'required|min_length[3]|max_length[100]',
            'phone'            => 'permit_empty|valid_phone',
            'is_active'        => 'permit_empty|in_list[0,1]',
            'password'         => 'permit_empty|min_length[6]|max_length[255]',
            'password_confirm' => 'permit_empty|matches[password]',
        ];

        if (isset($input['username']) && $input['username'] !== ($existing['username'] ?? null)) {
            $rules['username'] .= '|is_unique[users.username,id,{id}]';
        }

        if (isset($input['email']) && $input['email'] !== ($existing['email'] ?? null)) {
            $rules['email'] .= '|is_unique[users.email,id,{id}]';
        }

        return $rules;
    }

    /**
     * ===== Wrapper untuk kompatibilitas kode lama =====
     * createRules() memanggil rulesForCreate()
     */
    public static function createRules(): array
    {
        return self::rulesForCreate();
    }

    /**
     * updateRules($userId) untuk kode lama yang tidak memakai dinamis-compare
     * (Langsung ignore berdasarkan ID numerik)
     */
    public static function updateRules(int $userId): array
    {
        return [
            'id'               => 'required|is_natural_no_zero',
            'role_id'          => 'required|is_natural_no_zero|is_not_unique[roles.id]',
            'username'         => "required|min_length[3]|max_length[30]|alpha_dash|is_unique[users.username,id,{$userId}]",
            'email'            => "required|valid_email|max_length[255]|is_unique[users.email,id,{$userId}]",
            'full_name'        => 'required|min_length[3]|max_length[100]',
            'phone'            => 'permit_empty|valid_phone',
            'is_active'        => 'permit_empty|in_list[0,1]',
            'password'         => 'permit_empty|min_length[6]|max_length[255]',
            'password_confirm' => 'permit_empty|matches[password]',
        ];
    }

    /**
     * ===== (Opsional) Property set untuk dipakai via Config\Validation::ruleSets =====
     * NB: disediakan kalau ada bagian lain memanggil set ini. Sudah diselaraskan & ditambah rule 'id'.
     */
    public $userCreate = [
        'role_id'          => 'required|is_natural_no_zero|is_not_unique[roles.id]',
        'username'         => 'required|min_length[3]|max_length[30]|alpha_dash|is_unique[users.username]',
        'email'            => 'required|valid_email|max_length[255]|is_unique[users.email]',
        'password'         => 'required|min_length[6]|max_length[255]',
        'password_confirm' => 'required|matches[password]',
        'full_name'        => 'required|min_length[3]|max_length[100]',
        'phone'            => 'permit_empty|valid_phone',
        'is_active'        => 'permit_empty|in_list[0,1]',
    ];

    public $userUpdate = [
        'id'               => 'required|is_natural_no_zero', // supaya {id} terisi
        'role_id'          => 'required|is_natural_no_zero|is_not_unique[roles.id]',
        'username'         => 'required|min_length[3]|max_length[30]|alpha_dash|is_unique[users.username,id,{id}]',
        'email'            => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        'full_name'        => 'required|min_length[3]|max_length[100]',
        'phone'            => 'permit_empty|valid_phone',
        'is_active'        => 'permit_empty|in_list[0,1]',
        'password'         => 'permit_empty|min_length[6]|max_length[255]',
        'password_confirm' => 'permit_empty|matches[password]',
    ];

    /**
     * ===== Upload foto profil =====
     */
    public static function profilePhotoRules(): array
    {
        return [
            'profile_photo' => [
                'label' => 'Foto Profil',
                'rules' => 'uploaded[profile_photo]|is_image[profile_photo]|mime_in[profile_photo,image/jpg,image/jpeg,image/png]|max_size[profile_photo,2048]',
                'errors' => [
                    'uploaded' => 'Foto profil harus dipilih',
                    'is_image' => 'File harus berupa gambar',
                    'mime_in'  => 'Format foto harus JPG, JPEG, atau PNG',
                    'max_size' => 'Ukuran foto maksimal 2MB',
                ],
            ],
        ];
    }

    public static function profilePhotoUpdateRules(): array
    {
        return [
            'profile_photo' => [
                'label' => 'Foto Profil',
                'rules' => 'permit_empty|is_image[profile_photo]|mime_in[profile_photo,image/jpg,image/jpeg,image/png]|max_size[profile_photo,2048]',
                'errors' => [
                    'is_image' => 'File harus berupa gambar',
                    'mime_in'  => 'Format foto harus JPG, JPEG, atau PNG',
                    'max_size' => 'Ukuran foto maksimal 2MB',
                ],
            ],
        ];
    }

    /**
     * ===== Import =====
     */
    public static function importRules(): array
    {
        return [
            'import_file' => [
                'label' => 'File Import',
                'rules' => 'uploaded[import_file]|ext_in[import_file,xlsx,xls]|max_size[import_file,5120]',
                'errors' => [
                    'uploaded' => 'File import harus dipilih',
                    'ext_in'   => 'Format file harus XLSX atau XLS',
                    'max_size' => 'Ukuran file maksimal 5MB',
                ],
            ],
        ];
    }

    /**
     * ===== Validasi password lama (untuk changePassword) =====
     */
    public function validateOldPassword(string $str, string &$error = null, array $data = null): bool
    {
        $userModel = new \App\Models\UserModel();
        $userId    = session()->get('user_id');

        if (! $userId) {
            $error = 'Sesi tidak valid';
            return false;
        }

        $user = $userModel->find($userId);
        if (! $user) {
            $error = 'User tidak ditemukan';
            return false;
        }

        if (! password_verify($str, $user['password_hash'])) {
            $error = 'Password lama tidak sesuai';
            return false;
        }

        return true;
    }

    /**
     * ===== Sanitizer sederhana =====
     */
    public static function sanitizeInput(array $data): array
    {
        $sanitized = [];
        foreach ($data as $k => $v) {
            $sanitized[$k] = is_string($v) ? trim($v) : $v;
        }

        // hapus field konfirmasi
        unset($sanitized['password_confirm'], $sanitized['new_password_confirm']);

        // default is_active = 1 jika tidak di-set
        if (! isset($sanitized['is_active'])) {
            $sanitized['is_active'] = 1;
        }

        return $sanitized;
    }
}
