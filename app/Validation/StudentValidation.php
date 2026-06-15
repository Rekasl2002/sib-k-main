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
            'nik'       => [
                'numeric'                => 'NIK hanya boleh angka',
                'exact_length'           => 'NIK harus 16 digit',
                'is_unique'              => 'NIK sudah terdaftar',
                'unique_with_soft_delete'=> 'NIK sudah terdaftar',
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
            'nik'           => 'permit_empty|numeric|exact_length[16]|is_unique[students.nik]',
            'gender'        => 'required|in_list[L,P]',
            'birth_place'   => 'permit_empty|max_length[100]',
            'birth_date'    => 'permit_empty|valid_date[Y-m-d]',
            'religion'      => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
            'address'       => 'permit_empty|max_length[255]',
            'special_needs' => 'permit_empty|max_length[100]',
            'disability'    => 'permit_empty|max_length[100]',
            'hobi'          => 'permit_empty|max_length[255]',
            'ekskul_organisasi' => 'permit_empty|max_length[255]',
            'kip_pip_number'=> 'permit_empty|max_length[50]',
            'father_name'   => 'permit_empty|max_length[255]',
            'mother_name'   => 'permit_empty|max_length[255]',
            'guardian_name' => 'permit_empty|max_length[255]',
            // gunakan valid_phone agar konsisten dengan Config\Validation
            'phone'         => 'permit_empty|valid_phone',
            'parent_id'     => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
            'admission_date'=> 'permit_empty|valid_date[Y-m-d]',
            'status'        => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar,Tidak Aktif]',
        ];
    }

    /**
     * ===== Aturan UPDATE (dinamis) =====
     * - Sertakan 'id' agar placeholder {id} terisi
     * - Unik NISN/NIK hanya jika nilainya berubah
     * - class_id dibuat REQUIRED agar konsisten dengan form edit (required)
     */
    public static function rulesForUpdate(array $existing, array $input): array
    {
        $rules = [
            'id'            => 'required|is_natural_no_zero',
            'full_name'     => 'required|min_length[3]|max_length[100]',
            'nisn'          => 'required|numeric|exact_length[10]',
            'nik'           => 'permit_empty|numeric|exact_length[16]',
            // class_id wajib pada edit
            'class_id'      => 'required|is_natural_no_zero|is_not_unique[classes.id]',
            'gender'        => 'required|in_list[L,P]',
            'birth_place'   => 'permit_empty|max_length[100]',
            'birth_date'    => 'permit_empty|valid_date[Y-m-d]',
            'religion'      => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
            'address'       => 'permit_empty|max_length[255]',
            'special_needs' => 'permit_empty|max_length[100]',
            'disability'    => 'permit_empty|max_length[100]',
            'hobi'          => 'permit_empty|max_length[255]',
            'ekskul_organisasi' => 'permit_empty|max_length[255]',
            'kip_pip_number'=> 'permit_empty|max_length[50]',
            'father_name'   => 'permit_empty|max_length[255]',
            'mother_name'   => 'permit_empty|max_length[255]',
            'guardian_name' => 'permit_empty|max_length[255]',
            'phone'         => 'permit_empty|valid_phone',
            'parent_id'     => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
            'admission_date'=> 'permit_empty|valid_date[Y-m-d]',
            'status'        => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar,Tidak Aktif]',
        ];

        // tambahkan unik hanya jika berubah
        if (array_key_exists('nisn', $input) && $input['nisn'] !== ($existing['nisn'] ?? null)) {
            // Ganti ke unique_with_soft_delete[...] jika tabel students pakai soft deletes
            $rules['nisn'] .= '|is_unique[students.nisn,id,{id}]';
            // $rules['nisn'] .= '|unique_with_soft_delete[students.nisn,id,{id}]';
        }

        if (array_key_exists('nik', $input) && $input['nik'] !== ($existing['nik'] ?? null)) {
            $rules['nik'] .= '|is_unique[students.nik,id,{id}]';
            // $rules['nik'] .= '|unique_with_soft_delete[students.nik,id,{id}]';
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
            'nik'           => "permit_empty|numeric|exact_length[16]|is_unique[students.nik,id,{$studentId}]",
            'gender'        => 'required|in_list[L,P]',
            'birth_place'   => 'permit_empty|max_length[100]',
            'birth_date'    => 'permit_empty|valid_date[Y-m-d]',
            'religion'      => 'permit_empty|max_length[50]|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
            'address'       => 'permit_empty|max_length[255]',
            'special_needs' => 'permit_empty|max_length[100]',
            'disability'    => 'permit_empty|max_length[100]',
            'hobi'          => 'permit_empty|max_length[255]',
            'ekskul_organisasi' => 'permit_empty|max_length[255]',
            'kip_pip_number'=> 'permit_empty|max_length[50]',
            'father_name'   => 'permit_empty|max_length[255]',
            'mother_name'   => 'permit_empty|max_length[255]',
            'guardian_name' => 'permit_empty|max_length[255]',
            'phone'         => 'permit_empty|valid_phone',
            'parent_id'     => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
            'admission_date'=> 'permit_empty|valid_date[Y-m-d]',
            'status'        => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar,Tidak Aktif]',
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
            'email'     => 'permit_empty|valid_email|max_length[255]|is_unique[users.email]',
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
            'nik',
            'special_needs',
            'disability',
            'hobi',
            'ekskul_organisasi',
            'kip_pip_number',
            'father_name',
            'mother_name',
            'guardian_name',
            'parent_id',
            'admission_date',
            'phone',
        ];

        foreach ($optional as $field) {
            if (array_key_exists($field, $sanitized) && $sanitized[$field] === '') {
                $sanitized[$field] = null;
            }
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
        return ['Aktif', 'Alumni', 'Pindah', 'Keluar', 'Tidak Aktif'];
    }

    public static function getGenderOptions(): array
    {
        return ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    }
}
