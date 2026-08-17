# SIB-K — Panduan Pemasangan di Server

**Baca dalam bahasa lain:** [English](DEPLOYMENT.md) · **Bahasa Indonesia**

Cara memasang SIB-K di server Linux. Contoh di sini memakai **aaPanel**, tetapi semua
langkahnya berlaku juga untuk server LAMP/LEMP biasa — yang berbeda hanya klik
khusus panelnya.

- [1. Prasyarat](#1-prasyarat)
- [2. Placeholder yang dipakai di panduan ini](#2-placeholder-yang-dipakai-di-panduan-ini)
- [3. Langkah pemasangan](#3-langkah-pemasangan)
- [4. Kepemilikan dan izin berkas](#4-kepemilikan-dan-izin-berkas)
- [5. Daftar periksa setelah terpasang](#5-daftar-periksa-setelah-terpasang)
- [6. Memperbarui situs yang sudah terpasang](#6-memperbarui-situs-yang-sudah-terpasang)
- [7. Cadangan dan pemulihan](#7-cadangan-dan-pemulihan)
- [8. Penanganan masalah](#8-penanganan-masalah)
- [9. Rangkuman perintah](#9-rangkuman-perintah)

---

## 1. Prasyarat

| Komponen | Kebutuhan |
|---|---|
| PHP | 8.1+ (disarankan 8.4). Di aaPanel: *App Store → PHP 8.x → Install*. |
| Ekstensi PHP | `intl`, `mbstring`, `mysqli`, `gd`, `zip`. Di aaPanel: *App Store → PHP 8.x → Setting → Install extensions*. |
| Basis data | MySQL 8.x atau MariaDB setara. |
| Web server | Nginx atau Apache. Rewrite ditangani `public/.htaccess` di Apache, atau aturan rewrite di Nginx (lihat [3.5](#35-arahkan-document-root-ke-public)). |
| Composer | Diperlukan kecuali folder `vendor/` diunggah dari komputer pengembangan. |
| Git | Disarankan, agar pembaruan cukup lewat `git pull`. |

---

## 2. Placeholder yang dipakai di panduan ini

Ganti dengan nilai milik Anda. **Jangan menempelkan kredensial asli ke berkas mana pun
yang di-track Git.**

| Placeholder | Arti | Contoh nilai |
|---|---|---|
| `<APP_DIR>` | Folder proyek di server | `/www/wwwroot/domain-anda.example` |
| `<WEB_USER>` | User yang menjalankan web server / PHP-FPM | `www` (aaPanel), `www-data` (Debian/Ubuntu), `apache` (RHEL) |
| `<DOMAIN>` | Domain situs | `domain-anda.example` |
| `<DB_NAME>` `<DB_USER>` `<DB_PASS>` | Kredensial basis data | dibuat pada langkah 3.1 |

Untuk memastikan user web server di mesin Anda:

```bash
ps aux | grep -E 'php-fpm|apache2|httpd|nginx' | grep -v grep | head -5
```

---

## 3. Langkah pemasangan

Ringkasan alur: siapkan PHP dan basis data → buat website → taruh kode → composer →
`.env` → arahkan document root ke `public/` → atur kepemilikan dan izin → migrasi dan
seeder → SSL → ganti password bawaan.

### 3.1 Buat website dan basis data

1. **Website → Add site**, isi `<DOMAIN>`.
2. Pilih **PHP version** 8.x. Pastikan bukan PHP lama (misalnya 7.4) yang mungkin juga
   terpasang di server.
3. Buat basis data dari formulir yang sama atau lewat menu **Databases**. Catat
   `<DB_NAME>`, `<DB_USER>`, dan `<DB_PASS>` — simpan di pengelola kata sandi, bukan di
   berkas di dalam folder proyek.

Gunakan charset `utf8mb4`.

### 3.2 Taruh kode aplikasi

```bash
cd <APP_DIR>
rm -f index.html 404.html .htaccess        # hapus berkas bawaan panel
git clone https://github.com/Rekasl2002/sib-k-main.git .
```

Bila mengunggah ZIP dari komputer pengembangan, **KECUALIKAN**:

- berkas `.env` / `.env.*` lokal — buat `.env` baru di server;
- bahan penelitian atau arsip yang berisi data siswa asli;
- `tests/` dan `phpunit.xml.dist`.

### 3.3 Pasang dependensi

```bash
cd <APP_DIR>
composer install --no-dev
```

Bila Composer tidak tersedia di server, unggah folder `vendor/` dari komputer
pengembangan.

### 3.4 Buat dan isi `.env`

```bash
cd <APP_DIR>
cp env .env
nano .env        # atau ubah lewat file manager panel
```

Isi minimal:

```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://<DOMAIN>/'

database.default.hostname = 127.0.0.1
database.default.database = <DB_NAME>
database.default.username = <DB_USER>
database.default.password = <DB_PASS>
```

Buat kunci enkripsi (menulis `encryption.key` ke `.env`):

```bash
php spark key:generate
```

Setelah HTTPS aktif (langkah 3.8), tambahkan juga:

```ini
app.forceGlobalSecureRequests = true
cookie.secure = true
```

> **`CI_ENVIRONMENT = production` WAJIB di server publik.** Mode development
> menampilkan detail error dan path server ke semua pengunjung. Bila perlu mengubahnya
> sementara ke `development` untuk menelusuri masalah, segera kembalikan setelah
> selesai.

### 3.5 Arahkan document root ke `public/`

CodeIgniter 4 hanya boleh diakses lewat folder `public/`, agar `app/`, `writable/`,
dan `.env` tidak bisa diunduh orang.

- **aaPanel:** *Website → klik situs → Site directory → Run directory = `/public`* → Save.
- **Apache (manual):** atur `DocumentRoot` ke `<APP_DIR>/public`, izinkan `.htaccess`
  (`AllowOverride All`), dan aktifkan `mod_rewrite`.
- **Nginx:** atur `root <APP_DIR>/public;` lalu tambahkan aturan rewrite berikut
  (aaPanel: tab *URL rewrite*). Tanpa itu, beranda tampil tetapi semua halaman lain 404.

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 3.6 Atur kepemilikan dan izin

Ini penyebab kegagalan pemasangan yang paling sering — lihat
[bagian 4](#4-kepemilikan-dan-izin-berkas).

```bash
cd <APP_DIR>
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
chmod 640 .env
```

### 3.7 Migrasi dan data awal

```bash
cd <APP_DIR>
php spark migrate
php spark db:seed DatabaseSeeder
```

`DatabaseSeeder` mengisi data awal wajib (peran, izin, akun bawaan, tahun ajaran,
kelas, pengaturan) beserta data contoh tiap fitur, dengan tanggal jadwal contoh
digeser ke sekitar hari seeding. Bila hanya ingin data awal tanpa data contoh,
jalankan `php spark db:seed InitialDataSeeder`.

> ### ⚠️ Jangan pernah menjalankan `php spark migrate:rollback` di server
> Skema SIB-K adalah satu migration tunggal yang `down()`-nya **menghapus seluruh
> tabel aplikasi**. Buat dump terlebih dahulu — lihat
> [bagian 7](#7-cadangan-dan-pemulihan).

### 3.8 Pasang SSL (HTTPS)

1. *Website → klik situs → SSL → Let's Encrypt → centang domain → Apply*.
2. Aktifkan **Force HTTPS**.
3. Kembali ke `.env`, aktifkan `app.forceGlobalSecureRequests = true` dan
   `cookie.secure = true`.

---

## 4. Kepemilikan dan izin berkas

### Mengapa penting

Setiap berkas/folder Linux punya **pemilik (owner)**, **grup**, dan izin untuk
pemilik / grup / lainnya: `r` (baca) = 4, `w` (tulis) = 2, `x` (jalankan atau masuk
folder) = 1.

| Izin | Arti | Dipakai untuk |
|---|---|---|
| `755` | Pemilik boleh menulis; grup dan lainnya hanya baca dan masuk | Kode aplikasi |
| `775` | Pemilik **dan grup** boleh menulis; lainnya hanya baca | `writable/` |
| `640` | Pemilik baca-tulis, grup hanya baca, lainnya tidak sama sekali | `.env` (berisi password basis data) |
| `777` | Semua orang boleh menulis | **Jangan pernah** — apalagi untuk berkas kredensial |

Bila kode di-clone atau diunggah sebagai `root`, semua berkas menjadi milik `root`.
Dengan izin `755`, hanya pemilik yang boleh menulis — sehingga PHP-FPM yang berjalan
sebagai `<WEB_USER>` tidak bisa membuat berkas cache, sesi, log, atau unggahan, dan
seluruh halaman gagal dengan error 500.

### Aturan praktis SIB-K

- Seluruh proyek dimiliki `<WEB_USER>:<WEB_USER>`.
- Folder `writable/` ber-izin `775`.
- Berkas `.env` ber-izin `640`.

```bash
cd <APP_DIR>
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
chmod 640 .env
```

Catatan penulisan perintah: titik (`.`) di akhir `chown -R <WEB_USER>:<WEB_USER> .`
adalah target (folder saat ini) dan **wajib** — tanpa itu muncul error
`missing operand`. Opsi `-R` memakai satu tanda strip dan berarti rekursif.

**Ulangi `chown -R <WEB_USER>:<WEB_USER> .` setiap selesai `git pull` atau mengunggah
berkas sebagai root**, karena berkas baru itu akan dimiliki root lagi.

---

## 5. Daftar periksa setelah terpasang

- [ ] Masuk sebagai Admin dan **ganti seluruh password akun bawaan**. Kredensial akun
      seeder disertakan pada buku panduan administrator internal, tidak dipublikasikan
      di repositori ini.
- [ ] Hapus atau nonaktifkan akun contoh yang tidak diperlukan.
- [ ] Periksa menu **Pengaturan Aplikasi**: nama sekolah, logo, tahun ajaran, sakelar
      Konsultasi & Pengaduan, dan matriks notifikasi per peran.
- [ ] Pastikan `CI_ENVIRONMENT = production`, dan membuka URL yang salah menampilkan
      halaman error biasa, bukan trace.
- [ ] Pastikan `.env` tidak dapat diakses lewat HTTP (`https://<DOMAIN>/.env` tidak
      boleh mengembalikan isi berkas — dengan document root di `public/` memang tidak bisa).
- [ ] Login sekali untuk tiap peran dan pastikan menu serta hak aksesnya sesuai.
- [ ] Siapkan rutinitas pencadangan (bagian 7).

Untuk mengosongkan/mengulang data di kemudian hari: **Pengaturan → Reset Data
Aplikasi** (ketik `RESET` beserta password Admin). Seluruh data dan berkas unggahan
dihapus, data awal dan contoh diisi ulang, dan semua pengguna dikeluarkan. Tindakan
ini tidak dapat dibatalkan.

---

## 6. Memperbarui situs yang sudah terpasang

```bash
cd <APP_DIR>
git pull
composer install --no-dev        # bila composer.json berubah
php spark migrate                # bila ada migration baru
chown -R <WEB_USER>:<WEB_USER> . # WAJIB karena pull dijalankan sebagai root
```

OPcache aktif di server (berbeda dengan lokal), sehingga perubahan PHP mungkin belum
terlihat sampai PHP-FPM dimuat ulang (aaPanel: *App Store → PHP 8.x → Reload*).

---

## 7. Cadangan dan pemulihan

Cadangkan basis data dan kedua folder unggahan sekaligus, dan selalu sebelum operasi
migrasi atau reset:

```bash
mysqldump -u <DB_USER> -p <DB_NAME> > /root/backup_sibk_$(date +%F).sql
tar czf /root/backup_sibk_uploads_$(date +%F).tar.gz \
    <APP_DIR>/writable/uploads <APP_DIR>/public/uploads
```

Pemulihan:

```bash
mysql -u <DB_USER> -p <DB_NAME> < /root/backup_sibk_YYYY-MM-DD.sql
```

Simpan berkas cadangan di luar folder web. Ingat bahwa isinya memuat data konseling
yang bersifat rahasia — simpan dan kirimkan dengan hati-hati.

---

## 8. Penanganan masalah

### "Cache unable to write" / gagal menulis sesi atau log / unggahan gagal

```
CodeIgniter\Cache\Exceptions\CacheException:
Cache unable to write to "<APP_DIR>/writable/cache/".
```

Semua halaman menghasilkan error 500, bahkan sebelum login, karena CodeIgniter
menyiapkan layanan cache di awal setiap permintaan.

**Penyebab:** proyek dimiliki `root` dengan izin `755`, sedangkan PHP-FPM berjalan
sebagai `<WEB_USER>` — sehingga web server hanya bisa membaca, tidak bisa menulis.

**Diagnosis:**

```bash
ls -ld <APP_DIR>/writable/cache
sudo -u <WEB_USER> touch <APP_DIR>/writable/cache/tes \
  && echo "BISA TULIS" || echo "GAGAL"
```

**Solusi:**

```bash
cd <APP_DIR>
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
```

### Beranda tampil tetapi semua halaman lain 404

Rewrite belum aktif, atau Run directory belum diarahkan ke `/public`. Lihat
[3.5](#35-arahkan-document-root-ke-public); untuk Nginx tambahkan blok `try_files`.

### Error 500 tanpa keterangan

Buka log aplikasi `writable/logs/log-<tanggal>.log`. Bila perlu, ubah sementara
`CI_ENVIRONMENT = development` di `.env` untuk melihat trace, lalu **kembalikan** ke
`production`.

### `open_basedir restriction in effect`

Panel membatasi folder yang boleh diakses PHP. Perluas agar mencakup seluruh folder
proyek (bukan hanya `public/`): *Website → situs → PHP → pengaturan open_basedir /
Anti-XSS*.

### Perubahan kode PHP tidak muncul

OPcache aktif di server. Muat ulang PHP-FPM setelah memperbarui kode.

### Halaman error menyebut versi PHP yang salah

Situs memakai pool PHP yang keliru: *Website → situs → PHP version → pilih 8.x*.

### Formulir ditolak karena token kedaluwarsa setelah lama didiamkan

Perilaku keamanan CSRF yang memang dipertahankan. Muat ulang halaman lalu isi ulang
formulir.

### Error basis data setelah `git pull`

Kemungkinan ada migration baru: jalankan `php spark migrate`. **Jangan** menjalankan
`migrate:rollback` — perintah itu menghapus seluruh tabel. Buat dump terlebih dahulu.

### Email reset password tidak terkirim

Jalur reset lewat email memang dimatikan. Pengguna mengirim permintaan dari halaman
login, lalu Admin membuatkan password baru melalui
**Admin → Permintaan Reset Password**.

---

## 9. Rangkuman perintah

```bash
# ===== DEPLOY AWAL =====
cd <APP_DIR>
git clone https://github.com/Rekasl2002/sib-k-main.git .
composer install --no-dev
cp env .env
#   -> ubah .env: CI_ENVIRONMENT, app.baseURL, database.default.*
php spark key:generate
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
chmod 640 .env
php spark migrate
php spark db:seed DatabaseSeeder

# ===== SETIAP KALI UPDATE KODE =====
cd <APP_DIR>
git pull
composer install --no-dev          # bila composer.json berubah
php spark migrate                  # bila ada migration baru
chown -R <WEB_USER>:<WEB_USER> .   # WAJIB karena pull dijalankan sebagai root

# ===== BILA MUNCUL ERROR 'unable to write' =====
chown -R <WEB_USER>:<WEB_USER> <APP_DIR>
chmod -R 775 <APP_DIR>/writable

# ===== BACKUP (sebelum operasi berisiko) =====
mysqldump -u <DB_USER> -p <DB_NAME> > /root/backup_sibk_$(date +%F).sql
```

---

Lihat juga: [README.id.md](README.id.md) untuk gambaran proyek dan pemasangan lokal.
