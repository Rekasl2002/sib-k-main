<?php

/**
 * File Path: app/Language/id/Validation.php
 *
 * Terjemahan Indonesia untuk pesan validasi bawaan CodeIgniter 4.
 * Berkas ini adalah SUMBER UTAMA pesan validasi aplikasi:
 * karena defaultLocale = 'id', semua rule yang TIDAK diberi pesan kustom
 * di controller/model akan memakai pesan di sini (bukan bahasa Inggris bawaan).
 *
 * Gaya bahasa: sederhana dan mudah dipahami pengguna awam.
 * {field} otomatis diganti label kolom (atau nama kolom bila label tidak diberikan).
 */

return [
    // Pesan inti (untuk developer, jarang tampil ke pengguna)
    'noRuleSets'      => 'Tidak ada kumpulan aturan validasi yang ditentukan di konfigurasi.',
    'ruleNotFound'    => '"{0}" bukan aturan validasi yang dikenal.',
    'groupNotFound'   => '"{0}" bukan grup aturan validasi.',
    'groupNotArray'   => 'Grup aturan "{0}" harus berupa array.',
    'invalidTemplate' => '"{0}" bukan template validasi yang dikenal.',

    // Pesan aturan bawaan CI4
    'alpha'                 => 'Kolom {field} hanya boleh berisi huruf.',
    'alpha_dash'            => 'Kolom {field} hanya boleh berisi huruf, angka, garis bawah (_), dan tanda hubung (-).',
    'alpha_numeric'         => 'Kolom {field} hanya boleh berisi huruf dan angka.',
    'alpha_numeric_punct'   => 'Kolom {field} hanya boleh berisi huruf, angka, spasi, dan tanda baca umum.',
    'alpha_numeric_space'   => 'Kolom {field} hanya boleh berisi huruf, angka, dan spasi.',
    'alpha_space'           => 'Kolom {field} hanya boleh berisi huruf dan spasi.',
    'decimal'               => 'Kolom {field} harus berupa angka (boleh desimal).',
    'differs'               => 'Isi kolom {field} harus berbeda dengan kolom {param}.',
    'equals'                => 'Isi kolom {field} harus tepat: {param}.',
    'exact_length'          => 'Kolom {field} harus tepat {param} karakter.',
    'field_exists'          => 'Kolom {field} harus ada.',
    'greater_than'          => 'Kolom {field} harus berupa angka lebih besar dari {param}.',
    'greater_than_equal_to' => 'Kolom {field} harus berupa angka minimal {param}.',
    'hex'                   => 'Kolom {field} hanya boleh berisi karakter heksadesimal.',
    'in_list'               => 'Pilihan {field} harus salah satu dari: {param}.',
    'integer'               => 'Kolom {field} harus berupa angka bulat (tanpa koma).',
    'is_natural'            => 'Kolom {field} hanya boleh berisi angka.',
    'is_natural_no_zero'    => 'Kolom {field} harus berupa angka lebih dari nol.',
    'is_not_unique'         => 'Pilihan {field} tidak ditemukan pada data yang tersedia.',
    'is_unique'             => '{field} sudah digunakan. Silakan gunakan yang lain.',
    'less_than'             => 'Kolom {field} harus berupa angka kurang dari {param}.',
    'less_than_equal_to'    => 'Kolom {field} harus berupa angka maksimal {param}.',
    'matches'               => 'Isi kolom {field} tidak sama dengan kolom {param}.',
    'max_length'            => 'Kolom {field} maksimal {param} karakter.',
    'min_length'            => 'Kolom {field} minimal {param} karakter.',
    'not_equals'            => 'Isi kolom {field} tidak boleh: {param}.',
    'not_in_list'           => 'Pilihan {field} tidak boleh salah satu dari: {param}.',
    'numeric'               => 'Kolom {field} hanya boleh berisi angka.',
    'regex_match'           => 'Format isian kolom {field} tidak sesuai.',
    'required'              => 'Kolom {field} wajib diisi.',
    'required_with'         => 'Kolom {field} wajib diisi bila kolom {param} diisi.',
    'required_without'      => 'Kolom {field} wajib diisi bila kolom {param} kosong.',
    'string'                => 'Kolom {field} harus berupa teks.',
    'timezone'              => 'Kolom {field} harus berupa zona waktu yang benar.',
    'valid_base64'          => 'Kolom {field} harus berupa teks base64 yang benar.',
    'valid_email'           => 'Kolom {field} harus berupa alamat email yang benar (contoh: nama@contoh.com).',
    'valid_emails'          => 'Semua alamat email pada kolom {field} harus benar.',
    'valid_ip'              => 'Kolom {field} harus berupa alamat IP yang benar.',
    'valid_url'             => 'Kolom {field} harus berupa alamat situs (URL) yang benar.',
    'valid_url_strict'      => 'Kolom {field} harus berupa alamat situs (URL) yang benar.',
    'valid_date'            => 'Kolom {field} harus berupa tanggal yang benar.',
    'valid_json'            => 'Kolom {field} harus berupa JSON yang benar.',

    // Kartu kredit (tidak dipakai aplikasi, disediakan untuk kelengkapan)
    'valid_cc_num' => '{field} bukan nomor kartu kredit yang benar.',

    // Unggah berkas
    'uploaded' => 'Berkas {field} wajib diunggah.',
    'max_size' => 'Ukuran berkas {field} terlalu besar.',
    'is_image' => 'Berkas {field} harus berupa gambar yang benar.',
    'mime_in'  => 'Jenis berkas {field} tidak diizinkan.',
    'ext_in'   => 'Ekstensi berkas {field} tidak diizinkan.',
    'max_dims' => 'Berkas {field} bukan gambar, atau ukurannya (lebar/tinggi) terlalu besar.',
    'min_dims' => 'Berkas {field} bukan gambar, atau ukurannya (lebar/tinggi) terlalu kecil.',

    // Custom rules aplikasi (App\Libraries\ValidationHelper)
    'valid_phone'             => 'Kolom {field} harus diawali 08 dan terdiri dari 10-15 digit angka (contoh: 081234567890).',
    'valid_nisn'              => 'Kolom {field} harus terdiri dari tepat 10 digit angka.',
    'valid_indo_date'         => 'Kolom {field} harus berformat tanggal dd-mm-tttt atau dd/mm/tttt.',
    'valid_academic_year'     => 'Kolom {field} harus berformat tahun ajaran, contoh: 2025/2026.',
    'strong_password'         => 'Kolom {field} minimal 6 karakter dan mengandung huruf serta angka.',
    'valid_time'              => 'Kolom {field} harus berformat jam JJ:MM (contoh: 08.30 ditulis 08:30).',
    'unique_with_soft_delete' => '{field} sudah digunakan. Silakan gunakan yang lain.',
    'valid_file_extension'    => 'Ekstensi berkas {field} tidak diizinkan.',
    'valid_file_size'         => 'Ukuran berkas {field} terlalu besar.',
    'valid_image'             => 'Berkas {field} harus berupa gambar (jpg, jpeg, png, atau gif).',
    'valid_username'          => 'Kolom {field} harus 3-50 karakter dan hanya boleh berisi huruf, angka, titik, atau garis bawah (_).',
    'valid_nik'               => 'Kolom {field} harus terdiri dari tepat 16 digit angka.',
    'valid_grade_level'       => 'Pilihan {field} bukan tingkat kelas yang tersedia.',
    'valid_semester'          => 'Pilihan {field} harus Ganjil atau Genap.',
    'valid_gender'            => 'Pilihan {field} harus L (Laki-laki) atau P (Perempuan).',
    'valid_religion'          => 'Pilihan {field} harus dipilih dari daftar yang tersedia.',
    'valid_date_range'        => 'Tanggal mulai pada {field} harus sebelum tanggal selesai.',
];
