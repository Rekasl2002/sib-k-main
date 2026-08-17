# SIB-K — Sistem Informasi Bimbingan dan Konseling

**Baca dalam bahasa lain:** [English](README.md) · **Bahasa Indonesia**

SIB-K adalah aplikasi web untuk mengelola administrasi layanan Bimbingan dan
Konseling (BK) sekolah: konsultasi dan pengaduan, bimbingan, konseling, kolaborasi
orang tua, kunjungan rumah, konferensi kasus, asesmen, info karier dan studi lanjut,
pesan internal, notifikasi, penugasan, dashboard, serta pelaporan.

Aplikasi ini dibangun untuk **MA Persis 31 Banjaran**, namun dapat disesuaikan untuk
sekolah lain melalui halaman Pengaturan Aplikasi.

> **Kerahasiaan adalah aturan utama.** Detail konseling, catatan rahasia, dan hasil
> asesmen perorangan hanya dapat dibaca oleh Guru BK yang menangani dan Koordinator BK.
> Peran lain paling banyak hanya melihat jadwal atau garis besar yang aman. Mohon
> pertahankan aturan ini ketika mengubah kode.

---

## Daftar isi

- [Fitur](#fitur)
- [Peran pengguna](#peran-pengguna)
- [Kebutuhan sistem](#kebutuhan-sistem)
- [Pemasangan lokal](#pemasangan-lokal)
- [Pemasangan di server (produksi)](#pemasangan-di-server-produksi)
- [Akun dan kredensial](#akun-dan-kredensial)
- [Basis data dan migrasi](#basis-data-dan-migrasi)
- [Cadangan (backup)](#cadangan-backup)
- [Catatan keamanan](#catatan-keamanan)
- [Struktur repositori](#struktur-repositori)
- [Lisensi](#lisensi)

---

## Fitur

| Bagian | Keterangan |
|---|---|
| Konsultasi & Pengaduan | Siswa, orang tua, dan wali kelas mengirim konsultasi, pengaduan, atau permintaan; staf BK meninjau, memproses, dan dapat mengubahnya menjadi catatan layanan. |
| Lima layanan BK | Bimbingan, Konseling, Kolaborasi Orang Tua, Kunjungan Rumah, dan Konferensi Kasus — satu induk catatan layanan dengan peserta, catatan, dan tindak lanjut. |
| Asesmen | Angket/kuesioner daring: susun pertanyaan, terbitkan, tugaskan ke siswa atau kelas, nilai, dan unduh rekapnya. |
| Info Karier dan Studi Lanjut | Pilihan karier dan info perguruan tinggi, dengan status Ditampilkan/Disembunyikan per item serta daftar pilihan yang disimpan siswa. |
| Penugasan | Koordinator BK membagi pekerjaan ke Guru BK dan memantau riwayat statusnya. |
| Pesan Internal | Pesan bergaya chat beserta lampiran, dibatasi matriks penerima per peran. |
| Notifikasi | Pemberitahuan otomatis dari aktivitas fitur, dengan matriks kategori per peran yang diatur Admin. |
| Dashboard & Laporan | Dashboard khusus tiap peran, serta laporan PDF/Excel banyak fitur sesuai cakupan akses masing-masing peran. |
| Impor Siswa & Orang Tua | Menambahkan banyak siswa sekaligus dari berkas Excel, lengkap dengan pembuatan akun orang tuanya. |
| Administrasi | Pengguna, peran/izin, tahun ajaran, kelas, siswa, pengaturan aplikasi, permintaan reset password, tempat sampah, dan reset seluruh data yang terlindungi konfirmasi. |

## Peran pengguna

1. **Admin** — akun, peran, data master, dan pengaturan aplikasi. Tidak membuka isi catatan BK yang rahasia.
2. **Koordinator BK** — akses penuh ke data layanan BK, membagi tugas Guru BK, laporan tingkat sekolah.
3. **Guru BK** — melayani kelas/siswa binaannya; melihat detail rahasia hanya untuk kasus yang ditanganinya.
4. **Wali Kelas** — hanya kelas perwaliannya; informasi umum, tanpa detail konseling yang rahasia.
5. **Siswa** — mengajukan konsultasi, mengisi asesmen, melihat jadwal dan hasil miliknya sendiri.
6. **Orang Tua/Wali** — jadwal dan ringkasan umum anak, serta konsultasi dengan Guru BK.

Hak akses ditegakkan pada tingkat rute (filter peran + izin) dan sekali lagi di
controller serta tampilan. Matriks hak akses lengkap disertakan pada buku panduan
pengguna internal.

---

## Kebutuhan sistem

| Komponen | Versi / catatan |
|---|---|
| PHP | 8.1 atau lebih baru (teruji sampai 8.4) |
| Ekstensi PHP | `intl`, `mbstring`, `mysqli`, `gd`, `zip` |
| Basis data | MySQL 8.x atau MariaDB setara, `utf8mb4` |
| Web server | Apache dengan `mod_rewrite`, atau Nginx dengan aturan rewrite setara |
| Composer | 2.x |
| Framework | CodeIgniter 4.6 (dipasang lewat Composer) |

---

## Pemasangan lokal

Langkah berikut mengasumsikan lingkungan lokal seperti Laragon, XAMPP, atau
PHP + MySQL biasa.

### 1. Ambil kode dan pasang dependensi

```bash
git clone https://github.com/Rekasl2002/sib-k-main.git
cd sib-k-main
composer install
```

### 2. Buat berkas environment

Repositori menyertakan template bernama `env` (tanpa titik di depan dan tanpa
kredensial apa pun). Salin lalu isi dengan nilai Anda sendiri —
**jangan pernah commit berkas `.env` hasil salinan itu.**

```bash
cp env .env          # Windows PowerShell: Copy-Item env .env
```

Ubah `.env`, minimal bagian berikut:

```ini
CI_ENVIRONMENT = development          # gunakan "production" di server publik

app.baseURL = 'http://localhost:8080/'

database.default.hostname = 127.0.0.1
database.default.database = <nama_basis_data>
database.default.username = <user_basis_data>
database.default.password = <password_basis_data>
```

Lalu buat kunci enkripsi (perintah ini menulis `encryption.key` ke `.env`):

```bash
php spark key:generate
```

### 3. Buat basis data dan jalankan migrasi

Buat basis data kosong ber-charset `utf8mb4` sesuai `database.default.database`, lalu:

```bash
php spark migrate
```

### 4. Isi data awal

```bash
php spark db:seed DatabaseSeeder
```

Perintah ini mengisi data awal wajib (peran, izin, akun bawaan, tahun ajaran, kelas,
pengaturan) beserta data contoh tiap fitur, dengan tanggal jadwal contoh digeser ke
sekitar hari seeding.

Bila hanya ingin data awal wajib tanpa data contoh, jalankan
`php spark db:seed InitialDataSeeder`.

### 5. Jalankan aplikasi

Arahkan document root web server ke folder **`public/`** — jangan ke root proyek.
Dengan server bawaan CodeIgniter:

```bash
php spark serve
```

Buka alamat `app.baseURL` yang Anda atur, lalu masuk memakai kredensial yang
diberikan administrator (lihat [Akun dan kredensial](#akun-dan-kredensial)).

---

## Pemasangan di server (produksi)

Pemasangan di server — mencakup document root, kepemilikan dan izin berkas, HTTPS,
prosedur pembaruan, dan penanganan error — dijelaskan pada dokumen terpisah:

- **[DEPLOYMENT.id.md](DEPLOYMENT.id.md)** (Bahasa Indonesia)
- **[DEPLOYMENT.md](DEPLOYMENT.md)** (English)

Daftar periksa minimum untuk produksi:

- [ ] `CI_ENVIRONMENT = production` di `.env` (mode development menampilkan detail error dan path server ke semua pengunjung).
- [ ] Document root diarahkan ke `public/`.
- [ ] `php spark key:generate` sudah dijalankan di server.
- [ ] Folder `writable/` dapat ditulis user web server; `.env` hanya terbaca pemiliknya (`chmod 640`).
- [ ] HTTPS aktif, lalu `app.forceGlobalSecureRequests = true` dan `cookie.secure = true`.
- [ ] Seluruh password akun bawaan sudah diganti, dan akun contoh yang tidak dipakai dihapus.

---

## Akun dan kredensial

Seeder membuat sejumlah akun awal untuk tiap peran agar aplikasi langsung dapat
dipakai setelah dipasang.

**Username dan password akun tersebut sengaja tidak dicantumkan di repositori ini.**
Kredensialnya disertakan pada buku panduan administrator internal. Hal yang sama
berlaku untuk pola penamaan dan password awal pada fitur impor siswa/orang tua —
pola itu hanya didokumentasikan di panduan internal, karena mempublikasikannya akan
membuat akun hasil impor mudah ditebak.

Setelah pemasangan:

1. Masuk sebagai Admin.
2. Segera ganti **seluruh** password bawaan.
3. Hapus atau nonaktifkan akun contoh yang tidak diperlukan.
4. Ingatkan siswa dan orang tua hasil impor untuk mengganti password awal saat pertama kali masuk.

Tidak ada reset password mandiri lewat email. Pengguna yang lupa password mengirim
permintaan dari halaman login; Admin membuatkan password baru lalu menandai
permintaan itu selesai. Wali Kelas juga dapat mengatur ulang password siswa di kelas
perwaliannya.

---

## Basis data dan migrasi

Seluruh skema berada dalam **satu migration**, `CreateSibkSchema`. Semua tabel BK
memakai soft delete (`deleted_at`) agar riwayat layanan tetap dapat diaudit.

> ### ⚠️ Jangan pernah menjalankan `php spark migrate:rollback` di server
> Karena skema berupa satu migration tunggal, `down()`-nya **menghapus seluruh tabel
> aplikasi**. Selalu buat dump basis data sebelum operasi migrasi atau reset apa pun.

Perubahan skema berikutnya sebaiknya ditambahkan sebagai berkas migration **baru** di
`app/Database/Migrations/`, lalu dijalankan dengan `php spark migrate`.

Tersedia halaman **Pengaturan → Reset Data Aplikasi** di dalam aplikasi (khusus Admin,
dilindungi dengan mengetik `RESET` beserta password Admin sendiri) yang menghapus
seluruh data dan berkas unggahan lalu mengisi ulang data awal dan data contoh.
Tindakan ini tidak dapat dibatalkan.

---

## Cadangan (backup)

Cadangkan ketiganya sekaligus:

```bash
mysqldump -u <user_db> -p <nama_db> > backup_$(date +%F).sql
```

- dump basis data,
- folder `writable/uploads/`,
- folder `public/uploads/`.

Log aplikasi tersimpan di `writable/logs/`.

---

## Catatan keamanan

- **Jangan pernah commit `.env` atau kredensial apa pun.** Repositori ini bersifat
  publik. Berkas `env` yang di-track hanyalah template tanpa kredensial; `.env` dan
  `.env.*` diabaikan Git.
- Jalankan server publik dengan `CI_ENVIRONMENT = production`.
- Jaga `.env` pada izin `chmod 640` milik user web server, dan `writable/` pada `775`.
  Jangan memakai `777`.
- Perlindungan CSRF berbasis sesi dan aktif di setiap formulir. Formulir yang lama
  didiamkan akan ditolak — ini perilaku keamanan yang disengaja; muat ulang halaman
  lalu isi kembali.
- Sesi berakhir setelah batas waktu diam yang dapat diatur di pengaturan aplikasi.
- Bahan penelitian (`backupNInformasi/`, `bahan lain/`) berisi data siswa asli dan
  sengaja **tidak di-track Git**. Jangan memasukkannya ke repositori atau
  mengunggahnya ke server.

---

## Struktur repositori

```
app/          Kode aplikasi (Controllers, Models, Views, Services, Libraries, Migrations, Seeds)
public/       Akar web — document root harus diarahkan ke sini
system/       Framework CodeIgniter
writable/     Berkas runtime: cache, log, sesi, unggahan (tidak di-track)
env           Template .env tanpa kredensial
DEPLOYMENT.md Panduan pemasangan di server dan penanganan error
```

Folder `vendor/`, `writable/*`, berkas unggahan, dan `.env` tidak di-track dan dibuat
ulang oleh `composer install`, oleh aplikasi saat berjalan, atau oleh Anda sendiri.

---

## Lisensi

Lihat [LICENSE](LICENSE).
