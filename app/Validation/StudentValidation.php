<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * File Path: app/Validation/StudentValidation.php
 *
 * Student Validation Rules
 * Custom validation rules untuk Student management
 */
final class StudentValidation
{
    /** ===== Pesan error umum ===== */
    public static function messages(): array
    {
        return [
            'full_name' => [
                'required' => 'Nama lengkap wajib diisi',
            ],
            'nisn'      => [
                'required'               => 'NISN wajib diisi',
                'numeric'                => 'NISN hanya boleh angka',
                'exact_length'           => 'NISN harus 10 digit',
                'is_unique'              => 'NISN sudah terdaftar',
                'unique_with_soft_delete'=> 'NISN sudah terdaftar',
            ],
            'nis'       => [
                'required'               => 'NIS wajib diisi',
                'numeric'                => 'NIS hanya boleh angka',
                'min_length'             => 'NIS minimal 4 digit',
                'max_length'             => 'NIS maksimal 20 digit',
                'is_unique'              => 'NIS sudah terdaftar',
                'unique_with_soft_delete'=> 'NIS sudah terdaftar',
            ],
            'class_id'  => [
                'required'           => 'Kelas harus dipilih',
                'is_natural_no_zero' => 'Kelas tidak valid',
                'is_not_unique'      => 'Kelas tidak ditemukan',
            ],
            'gender'    => [
                'required' => 'Jenis kelamin harus dipilih',
                'in_list'  => 'Jenis kelamin tidak valid',
            ],
            'birth_date'=> [
                'valid_date' => 'Format tanggal tidak valid (YYYY-MM-DD)',
            ],
            'religion'  => [
                'in_list'    => 'Agama harus salah satu dari: Islam, Kristen, Katolik, Hindu, Buddha, Konghucu',
                'max_length' => 'Agama maksimal 50 karakter',
            ],
            'phone'     => [
                'valid_phone' => 'Nomor telepon harus diawali 08 dan terdiri dari 10–15 digit (format 08xxxxxxxxxx)',
            ],
            'id'        => [
                'required'           => 'ID tidak valid',
                'is_natural_no_zero' => 'ID tidak valid',
            ],
        ];
    }

    /** ===== Aturan CREATE ===== */
    public static function createRules(): array
    {
        return [
            'user_id'       => 'required|is_natural_no_zero|is_not_unique[users.id]|is_unique[students.user_id]',
            // class_id opsional pada create (ikuti UI)
            'class_id'      => 'permit_empty|is_natural_no_zero|is_not_unique[classes.id]',
            'full_name'     => 'required|min_length[3]|max_length[100]',
            'nisn'          => 'required|numeric|exact_length[10]|is_unique[students.nisn]',
            'nis'           => 'required|numeric|min_length[4]|max_length[20]|is_unique[students.nis]',
            'gender'        => 'required|in_list[L,P]',
            'birth_place'   => 'permit_empty|max_length[100]',
            'birth_date'    => 'permit_empty|valid_date[Y-m-d]',
            'religion'      => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
            'address'       => 'permit_empty|max_length[255]',
            // gunakan valid_phone agar konsisten dengan Config\Validation
            'phone'         => 'permit_empty|valid_phone',
            'parent_id'     => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
            'admission_date'=> 'permit_empty|valid_date[Y-m-d]',
            'status'        => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar]',
        ];
    }

    /**
     * ===== Aturan UPDATE (dinamis) =====
     * - Sertakan 'id' agar placeholder {id} terisi
     * - Unik NISN/NIS hanya jika nilainya berubah
     * - class_id dibuat REQUIRED agar konsisten dengan form edit (required)
     */
    public static function rulesForUpdate(array $existing, array $input): array
    {
        $rules = [
            'id'            => 'required|is_natural_no_zero',
            'full_name'     => 'required|min_length[3]|max_length[100]',
            'nisn'          => 'required|numeric|exact_length[10]',
            'nis'           => 'required|numeric|min_length[4]|max_length[20]',
            // class_id wajib pada edit
            'class_id'      => 'required|is_natural_no_zero|is_not_unique[classes.id]',
            'gender'        => 'required|in_list[L,P]',
            'birth_place'   => 'permit_empty|max_length[100]',
            'birth_date'    => 'permit_empty|valid_date[Y-m-d]',
            'religion'      => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
            'address'       => 'permit_empty|max_length[255]',
            'phone'         => 'permit_empty|valid_phone',
            'parent_id'     => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
            'admission_date'=> 'permit_empty|valid_date[Y-m-d]',
            'status'        => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar]',
        ];

        // tambahkan unik hanya jika berubah
        if (array_key_exists('nisn', $input) && $input['nisn'] !== ($existing['nisn'] ?? null)) {
            // Ganti ke unique_with_soft_delete[...] jika tabel students pakai soft deletes
            $rules['nisn'] .= '|is_unique[students.nisn,id,{id}]';
            // $rules['nisn'] .= '|unique_with_soft_delete[students.nisn,id,{id}]';
        }

        if (array_key_exists('nis', $input) && $input['nis'] !== ($existing['nis'] ?? null)) {
            $rules['nis'] .= '|is_unique[students.nis,id,{id}]';
            // $rules['nis']  .= '|unique_with_soft_delete[students.nis,id,{id}]';
        }

        return $rules;
    }

    /** ===== Kompatibilitas gaya lama ===== */
    public static function updateRules(int $studentId): array
    {
        return [
            'class_id'      => 'required|is_natural_no_zero|is_not_unique[classes.id]',
            'full_name'     => 'required|min_length[3]|max_length[100]',
            'nisn'          => "required|numeric|exact_length[10]|is_unique[students.nisn,id,{$studentId}]",
            'nis'           => "required|numeric|min_length[4]|max_length[20]|is_unique[students.nis,id,{$studentId}]",
            'gender'        => 'required|in_list[L,P]',
            'birth_place'   => 'permit_empty|max_length[100]',
            'birth_date'    => 'permit_empty|valid_date[Y-m-d]',
            'religion'      => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
            'address'       => 'permit_empty|max_length[255]',
            'phone'         => 'permit_empty|valid_phone',
            'parent_id'     => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
            'admission_date'=> 'permit_empty|valid_date[Y-m-d]',
            'status'        => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar]',
        ];
    }

    /** ===== Create siswa + buat akun user sekaligus ===== */
    public static function createWithUserRules(): array
    {
        $studentRules = self::createRules();
        unset($studentRules['user_id']); // user baru akan dibuat

        $userRules = [
            // Anda juga punya custom valid_username; alpha_dash juga oke
            'username'  => 'required|min_length[3]|max_length[30]|alpha_dash|is_unique[users.username]',
            'email'     => 'required|valid_email|max_length[255]|is_unique[users.email]',
            'full_name' => 'required|min_length[3]|max_length[100]',
            'password'  => 'required|min_length[6]|max_length[255]',
        ];

        return array_merge($userRules, $studentRules);
    }

    /** ===== Import siswa ===== */
    public static function importRules(): array
    {
        return [
            'import_file' => 'uploaded[import_file]|ext_in[import_file,xlsx,xls,csv]|max_size[import_file,5120]',
            // biarkan opsional pada import
            'class_id'    => 'permit_empty|is_natural_no_zero|is_not_unique[classes.id]',
        ];
    }

    /** ===== Sanitizer ===== */
    public static function sanitizeInput(array $data): array
    {
        $sanitized = [];
        foreach ($data as $k => $v) {
            $sanitized[$k] = is_string($v) ? trim($v) : $v;
        }

        if (! isset($sanitized['status']) || $sanitized['status'] === '') {
            $sanitized['status'] = 'Aktif';
        }

        // Empty string → null pada field opsional
        $optional = [
            'class_id',
            'birth_place',
            'birth_date',
            'religion',
            'address',
            'parent_id',
            'admission_date',
            'phone',
        ];

        foreach ($optional as $field) {
            if (array_key_exists($field, $sanitized) && $sanitized[$field] === '') {
                $sanitized[$field] = null;
            }
        }

        if (! isset($sanitized['total_violation_points'])) {
            $sanitized['total_violation_points'] = 0;
        }

        return $sanitized;
    }

    /** ===== Validasi usia opsional ===== */
    public static function validateAge($birthDate, &$error): bool
    {
        if (empty($birthDate)) {
            return true;
        }

        try {
            $birth = new \DateTime($birthDate);
            $today = new \DateTime();
            $age   = $today->diff($birth)->y;

            if ($age < 6 || $age > 25) {
                $error = 'Usia siswa harus antara 6-25 tahun';
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $error = 'Format tanggal lahir tidak valid';
            return false;
        }
    }

    /** Opsi tampilan */
    public static function getReligionOptions(): array
    {
        return ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
    }

    public static function getStatusOptions(): array
    {
        return ['Aktif', 'Alumni', 'Pindah', 'Keluar'];
    }

    public static function getGenderOptions(): array
    {
        return ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    }
}
