# Panduan Deploy SIB-K

Panduan singkat memasang **SIB-K MA Persis 31 Banjaran** di server (hosting/VPS).

## 1. Prasyarat server

- PHP 8.1 atau lebih baru (ekstensi: `intl`, `mbstring`, `mysqli`, `gd`, `zip`).
- MySQL 8.x (atau MariaDB setara).
- Apache dengan `mod_rewrite` aktif (atau Nginx dengan rewrite setara).
- Composer (untuk memasang dependensi `vendor/`).

## 2. Berkas yang diunggah ke server

Unggah seluruh isi proyek **KECUALI** folder/berkas berikut (bahan penelitian &
pengembangan, tidak dibutuhkan aplikasi):

- `backupNInformasi/` — dokumen skripsi, wawancara, diagram, pengujian.
- `bahan lain/` — arsip dump SQL referensi.
- `tests/`, `phpunit.xml.dist` — perangkat pengujian.
- `.git/`, `CLAUDE.md`, `DEPLOYMENT.md`, `README.md` (opsional diunggah).
- `.env` lokal — **jangan** memakai .env pengembangan; buat baru dari `.env.example`.

Folder `vendor/` boleh diunggah langsung dari lokal, atau dipasang di server
dengan `composer install --no-dev`.

## 3. Langkah pemasangan

1. **Document root** diarahkan ke folder `public/` (bukan root proyek).
   Di shared hosting: letakkan proyek di luar `public_html`, lalu arahkan atau
   symlink `public_html` ke folder `public/`.
2. Salin `.env.example` menjadi `.env`, lalu isi:
   - `app.baseURL` = alamat situs (akhiri dengan `/`).
   - `database.default.*` = kredensial database server.
   - Kunci enkripsi: jalankan `php spark key:generate`.
   - Bila sudah HTTPS: `app.forceGlobalSecureRequests = true` dan `cookie.secure = true`.
3. Beri izin tulis untuk folder `writable/` beserta seluruh isinya
   (mis. `chmod -R 775 writable` dengan owner user web server).
4. Buat database kosong (utf8mb4), lalu jalankan:
   ```
   php spark migrate
   php spark db:seed DatabaseSeeder
   ```
   Seeder mengisi **data awal** (akun, kelas 10 A s.d. 12 C, pengaturan) dan
   **data contoh** tiap fitur, dengan jadwal digeser ke sekitar tanggal seeding.

## 4. Akun bawaan (SEGERA ganti password!)

| Peran | Username | Password |
|---|---|---|
| Admin | `admin_isman`, `admin_maudy` | `admin123` |
| Koordinator BK | `koordinator_1`, `koordinator_maudy` | `koordinator123` |
| Guru BK | `gurubk_1`, `gurubk_2` | `gurubk123` |
| Wali Kelas 10 - C | `walikelas_1` | `walikelas123` |
| Siswa (Siswa 1, 10 - C) | `1000000001` | `01012010` |
| Siswa (Siswa 2, 11 - C) | `1000000002` | `02022009` |
| Orang Tua Siswa 1 | `ibu_siswa_1_0001` | `01012010` |
| Orang Tua Siswa 2 | `ibu_siswa_2_0002` | `02022009` |

Konvensi akun siswa/orang tua mengikuti fitur impor: username siswa = NISN,
password = tanggal lahir 8 angka (DDMMYYYY); akun orang tua = nama + 4 digit
akhir NISN anak, password sama dengan anak.

## 5. Setelah terpasang

- Masuk sebagai Admin → **ganti seluruh password bawaan**.
- Periksa menu **Pengaturan Aplikasi** (nama sekolah, logo, tahun ajaran,
  sakelar Konsultasi & Pengaduan, matriks notifikasi).
- Untuk mengosongkan/mengulang data kapan pun: **Pengaturan → Reset Data
  Aplikasi** (ketik `RESET` + password admin). Seluruh data & berkas unggahan
  dihapus lalu diisi ulang data awal + contoh, dan semua pengguna keluar.

## 6. Pemeliharaan

- Log aplikasi: `writable/logs/`.
- Backup berkala: dump database + folder `writable/uploads/` dan
  `public/uploads/`.
- Perubahan skema berikutnya cukup ditambahkan sebagai migration baru di
  `app/Database/Migrations/` lalu `php spark migrate`.
