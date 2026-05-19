# SIB-K

Sistem Informasi Layanan Bimbingan dan Konseling.

## Stack

- PHP 8.1+
- CodeIgniter 4
- MySQL/MariaDB
- Composer

## Setup Lokal

1. Install dependency:

   ```bash
   composer install
   ```

2. Salin konfigurasi environment dari template internal proyek, lalu sesuaikan koneksi database dan base URL.

3. Jalankan migration:

   ```bash
   php spark migrate
   ```

4. Jalankan seeder bila dibutuhkan:

   ```bash
   php spark db:seed DatabaseSeeder
   ```

5. Arahkan web server ke folder `public`.

## Catatan Repo

- File `.env`, `vendor/`, `writable/`, `public/writable/`, dan file upload/runtime tidak disimpan di Git.
- Dependency dipulihkan dengan `composer install`.
- File runtime seperti cache, debugbar, session, dan hasil export dibuat ulang oleh aplikasi.
