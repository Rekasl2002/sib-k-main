# CLAUDE.md — Panduan Pengembangan SIB-K

Panduan membangun fitur pengembangan **Sistem Informasi Bimbingan dan Konseling (SIB-K)** MA Persis 31 Banjaran. Dibaca tiap sesi; jaga ringkas & akurat.

## 1. Tentang proyek
Aplikasi BK berbasis web (lanjutan kerja praktik). Arah pengembangan **bergeser dari pencatatan pelanggaran/poin** ke **administrasi layanan BK**: konsultasi/pengaduan, bimbingan, konseling, kolaborasi orang tua, kunjungan rumah, konferensi kasus, asesmen, info karier/studi, pesan, notifikasi, penugasan, plus pembaruan dashboard/laporan/impor. **Kerahasiaan data = aturan utama.**

## 2. Tech stack & cara menjalankan
- **CodeIgniter 4.6.3** (template Qovex/Bootstrap 5), PHP, MySQL. Lingkungan **Laragon (Apache)**.
- baseURL `http://localhost:30/`. DB `sibk_mapersis31` @ `127.0.0.1`, user `root`, password kosong (lokal).
- Migrasi: `php spark migrate` (⚠️ `migrate:rollback` men-drop SEMUA tabel — satu migration tunggal; backup dulu). Seed: `php spark db:seed DatabaseSeeder` (reset + data awal + contoh). Rute: `php spark routes`. Log: `writable/logs/`.
- Reset satu-klik dari web: **Admin → Pengaturan → Reset Data Aplikasi** (ketik RESET + password). Panduan pasang di server: **`DEPLOYMENT.md`**.
- **OPcache nonaktif** lokal → perubahan PHP langsung berlaku.
- **Jangan commit `.env`/kredensial apa pun** (repo GitHub PUBLIK). File `env` root = template tanpa kredensial; kredensial server asli di `.env.server` lokal (untracked).
- Baca PDF via `pdftotext -f <a> -l <b> file.pdf -` (poppler `pdftoppm`/render TIDAK ada). Baca `.docx` via `python` + `zipfile` (`word/document.xml`).

## 3. Sumber kebenaran (baca sebelum tiap fitur)
⚠️ `backupNInformasi/` & `bahan lain/` **untracked git sejak 2026-07-09** (berisi data siswa asli & dokumen penelitian; repo publik) — hanya ada di komputer lokal; backup di luar git.
**Authoritative utama**: file rencana pengembangan + diagram draw.io. Prototipe = patokan sekunder (besar kemungkinan berubah setelah evaluasi/konfirmasi ulang responden). Bab 3 skripsi = authoritative untuk **Black Box Testing**. Semua bisa berubah sesuai bimbingan/keadaan.
- `backupNInformasi/hasilAnalisis/Rencana_Pengembangan_SIBK_Berdasarkan_Wawancara_dan_Rancangan.docx` — **rencana induk** (fitur, ERD, halaman, hak akses). *(Varian "buatan AI" diabaikan.)*
- `backupNInformasi/hasilWawancara/Data_Fitur_Diminta_Guru_BK_Revisi_Bu_Guru BK 1.docx` — detail kebutuhan & kerahasiaan per fitur (revisi Guru BK 1).
- `backupNInformasi/hasilWawancara/transkripRekamanSeluruhWawancara.txt` + `hasilWawancaraTertulis.csv` (atau `Hasil_Wawancara_Tertulis_SIBK_Rapi.docx`).
- `backupNInformasi/diagram/diagram_prototipe_skripsi.drawio` (+ `.drawio.pdf`) — halaman: 8 Activity, 8 Use Case, 1 **ERD** ("ERD Bubble"), 8 **Wireframe**, 1 **CRUD**. PNG ada di `diagram/gambarDariDrawio/{activityDiagram,useCaseDiagram,CRUD}` (Wireframe & ERD hanya di `.drawio`/`.pdf`).
- `backupNInformasi/fileHasilKPdanDraftSkripsi/LaporanKPREKASLIF2022008rev.pdf` — baseline fitur lama; `Draft_Skripsi_..._Bab3_...docx` — metode & pengujian.
- Prototipe visual (`/prototype`) **sudah dihapus dari kode** (bersih-bersih deploy 2026-07-06); rujukan alur/tampilan kini hanya lewat diagram drawio & dokumen di `backupNInformasi/`.
- ⚠️ Istilah skripsi masih menulis **"Pelaporan Pelanggaran"** untuk yang sekarang bernama **"Konsultasi & Pengaduan"** (rencana awal; akan diperbaiki di Bab 4).

## 4. Peran & RBAC (WAJIB diikuti)
`roles.id`: 1 Admin, 2 Koordinator BK, 3 Guru BK, 4 Wali Kelas, 5 Siswa, 6 Orang Tua.
- Rute dikunci per peran via **nested group** + filter `role:` lalu `permission:` (`app/Config/Routes.php`). **Jangan** `'auth,role:...'` (error CI4) — pakai group bersarang.
- Filter `permission:` HARUS memakai `permissions.permission_name` yang ada (**27 permission final**, daftar + pemetaan peran ada di `InitialDataSeeder`; katalog label Indonesia di `PermissionModel::catalog()`). Jika kurang → tambah di `InitialDataSeeder` + catalog (jangan mengarang nama di rute).
- Namespace controller per peran: `App\Controllers\{Admin, Koordinator, Counselor (Guru BK), HomeroomTeacher (Wali Kelas), Student, Parents, Auth, Api, RoleFeatures}`. Untuk logika lintas-peran, lihat pola `RoleFeatures/Base*Controller`.

## 5. Konvensi
- Bahasa UI & komentar: **Indonesia**. Ikuti `layouts/main` & gaya kode yang ada.
- Migrasi: `app/Database/Migrations/YYYY-MM-DD-HHMMSS_Nama.php`, InnoDB, sediakan `down()`.
- Model `App\Models`, `returnType=array`, `allowedFields` eksplisit, `useTimestamps` bila perlu.
- **Soft delete** untuk data layanan BK (semua tabel BK punya `deleted_at`) — jangan hard delete; riwayat harus bisa diaudit.
- CSRF berbasis **session**, field `csrf_token_sibk`, `regenerate=true`, `redirect=true`. Form WAJIB `csrf_field()`. (Token kedaluwarsa saat idle lama = perilaku keamanan yang dipertahankan.)
- Validasi (Guru BK 2): tolak kolom wajib kosong, format salah, duplikat, tidak logis/relevan.
- Responsif HP/laptop; tombol jelas; bahasa sederhana; langkah ringkas.

## 6. Matriks CRUD (hak akses — acuan utama)
`*` = terbatas (data sendiri / kelas binaan / undangan / status). `-` = tanpa akses.

| Fitur | Admin | Koord BK | Guru BK | Wali Kelas | Siswa | Orang Tua |
|---|---|---|---|---|---|---|
| Konsultasi & Pengaduan | - | R,U,D* | C, R,U,D* | C,R,U* | C,R,U,D* | C,R,U,D* |
| Notifikasi Internal | R,U,D* | R,U,D* | R,U,D* | R,U,D* | R,U,D* | R,U,D* |
| Pesan Internal | C,R,U,D* | C,R,U,D* | C,R,U,D* | C,R,U,D* | C,R,U,D* | C,R,U,D* |
| Asesmen | - | C,R,U,D* | C,R,U,D* | R* | C,R,U* | R* |
| Info Karier/Studi | - | C,R,U,D* | C,R,U,D* | R* | C,R,U* | R* |
| Bimbingan | - | C,R,U,D* | C,R,U,D* | R* | R* | R* |
| Konseling | - | C,R,U,D* | C,R,U,D* | R* | R* | R* |
| Kolaborasi Orang Tua | - | C,R,U,D* | C,R,U,D* | R* | R* | R* |
| Kunjungan Rumah | - | C,R,U,D* | C,R,U,D* | R* | R* | R* |
| Konferensi Kasus | - | C,R,U,D* | C,R,U* | R* | R* | R* |
| Penugasan | - | C,R,U,D* | R,U* | - | - | - |

Aturan kerahasiaan kunci (wawancara + Guru BK 1):
- Detail konseling / catatan rahasia / hasil asesmen individu **hanya** Guru BK terkait & Koordinator BK. Siswa/Orang Tua/Wali Kelas hanya **jadwal/undangan/garis besar aman** (jadwal siswa cukup tanggal–waktu–lokasi, tanpa topik/durasi/deskripsi).
- Laporan orang tua **tidak otomatis** terlihat siswa.
- **Guru BK hanya menugaskan dirinya sendiri** sebagai petugas; memilih Guru BK/Petugas lain & penetapan kelas/siswa binaan = wewenang **Koordinator BK**.
- Pelapor utama Konsultasi/Pengaduan = **Siswa**; akses **Orang Tua/Wali Kelas sebagai pelapor masih perlu dikonfirmasi ulang** (Guru BK 1). Di prototipe sementara: Wali Kelas C,R,U* (boleh lapor & edit miliknya). Pegang Matriks CRUD bila ragu.

## 7. Database (skema final — SATU migration `2026-07-06-100001_CreateSibkSchema`)
39 tabel aplikasi dalam satu migration (raw SQL kanonis, `down()` men-drop semuanya). Seeder: `InitialDataSeeder` (data awal wajib) + `SampleDataSeeder` (contoh per fitur) + `DatabaseSeeder` (reset + panggil keduanya + geser tanggal jadwal ke sekitar hari ini). Kolom utama:
- **`bk_service_records`** (INDUK 5 layanan BK): `service_type, title, target_student_id, target_class_id, counselor_id, assignment_id, source_complaint_id, scheduled_at, held_at, location, status, duration_minutes, privacy_level, created_by`.
- **`guidances`**: `bk_service_record_id, guidance_type` (Kelompok/Klasikal/Kelas Besar)`, material_topic, summary`.
- **`counseling_sessions`** (kaya): `bk_service_record_id, counseling_type, student_id, counselor_id, class_id, session_type, session_date, session_time, location, topic, problem_description, session_summary, follow_up_plan, status, is_confidential, privacy_level, follow_up_status, duration_minutes`.
- **`parent_collaborations`**: `bk_service_record_id, parent_name, topic, summary, follow_up`.
- **`home_visits`**: `bk_service_record_id, address_snapshot, problem_topic, visit_result, follow_up` (alamat di-snapshot dari data siswa).
- **`case_conferences`**: `bk_service_record_id, chronology, discussion_summary, decision_summary, follow_up_plan`.
- **`session_participants`** (DIPAKAI semua layanan): `bk_service_record_id, participant_type` (student/user/parent/class/manual)`, participant_{student,user,parent,class}_id, manual_name, role_in_session, invitation_status, attendance_status, participation_note`.
- **`session_notes`** (DIPAKAI semua layanan): `bk_service_record_id, created_by, note_type, note_content, is_important, is_confidential, visibility_level, follow_up_status, assigned_to_user_id, due_date, completed_at, attachments`.
- **`consultation_complaints`**: `reporter_type, reporter_user_id, subject_student_id, subject_other_name, request_type, category, title, description, occurred_at, location, witness, priority, status, privacy_level, visible_to_homeroom, assigned_to_user_id, handled_by, converted_service_record_id` (+`consultation_complaint_attachments`: `complaint_id, file_path, file_type, uploaded_by`).
- **`bk_assignments`**: `assignment_type, title, instruction, assigned_by, assigned_to_user_id, class_id, student_id, source_type, source_id, priority, status, due_at` (+`bk_assignment_status_histories`: `assignment_id, status, note, changed_by, changed_at`).
- Semua tabel BK punya `created_at, updated_at, deleted_at` (soft delete).

**Tabel lain**: `users, roles, permissions, role_permissions, students, classes, academic_years, assessments(+questions/assignees/results/answers), career_options, university_info, student_saved_{careers,universities}, conversations, messages, message_participants, message_attachments, notifications, settings, password_resets, email_verifications, password_reset_requests, consultation_complaint_subjects, bk_assignment_targets`.
**Sudah TIDAK ada** (dihapus saat bersih-bersih deploy 2026-07-06): `prototype_evaluations(+answers)`, `simulation_access_grants`, seluruh tabel violation/poin lama.

## 8. Status implementasi
**SEMUA fitur inti sudah dibangun & diuji** (Black Box 190/190 lulus, 2026-06-25): Konsultasi & Pengaduan, 5 layanan BK, Asesmen, Info Karier/Studi, Pesan (chat), Notifikasi, Penugasan, Dashboard & Laporan per peran, Impor Siswa & Orang Tua, reset password via Admin, halaman Reset Data Aplikasi. Aplikasi **siap deploy** (bersih-bersih 2026-07-06). Kerja berikutnya = perbaikan/penyempurnaan atas fitur yang ada — PERLUAS, jangan buat ulang.
**Konvensi akun dari fitur impor** (`app/Libraries/ExcelImporter.php`): username siswa = NISN, password = tanggal lahir DDMMYYYY; akun orang tua = `nama_ortu_XXXX` (4 digit akhir NISN anak), password = password anak. Seeder mengikuti konvensi ini.
**Aturan**: satu Wali Kelas hanya membina SATU kelas aktif (ditegakkan `ClassValidation::isTeacherAlreadyHomeroom`).

### Kriteria "selesai" tiap fitur
Skema sesuai + migrasi `up()/down()` jalan · CRUD & alur sesuai Activity Diagram + **Matriks CRUD (§6)** per peran · permission terdaftar & rute terkunci benar · UI hanya tampilkan yang relevan · validasi diterapkan · kerahasiaan terjaga · diuji login per peran (§9) · tanpa error · commit.

## 9. Pengujian & verifikasi
Akun seed: `admin_1`+`admin_2/admin123`, `koordinator_1`+`koordinator_2/koordinator123`, `gurubk_1`+`gurubk_2`+`gurubk_3/gurubk123`, `walikelas_1/walikelas123` (wali Kelas 10 - C), siswa `1000000001/01012010` (Siswa 1, 10 - C) & `1000000002/02022009` (Siswa 2, 11 - C), ortu `ibu_siswa_1_0001/01012010` & `ibu_siswa_2_0002/02022009`.
- Uji tiap fitur login per peran; pastikan hak akses & kerahasiaan sesuai §6.
- Uji HTTP via curl: ambil **satu** token CSRF (`grep ... | head -1`, ada juga di meta tag), kirim sebagai field `csrf_token_sibk`.
- Skripsi: **Black Box Testing (Equivalence Partitioning)**, target 100% fungsi — siapkan data valid & tidak valid per fitur.

## 10. Modul prototipe & evaluasi — SUDAH DIHAPUS (2026-07-06)
Seluruh modul `/prototype`, `/simulation`, `admin/simulation-access`, permission `access_simulation_suite`, dan tabel evaluasinya sudah dihapus untuk deployment. Jangan membuat rujukan baru ke modul ini; bila butuh referensi visual lama, lihat riwayat git sebelum commit "Hapus total modul prototipe/simulasi".

## 11. Alur kerja per fitur
Satu fitur = satu sesi. **Plan Mode** (rancang → disetujui) → implementasi → `php spark migrate` (bila perlu) → uji login per peran → **commit** (branch per fitur). Commit sering agar progres aman bila batas penggunaan tercapai (proses berhenti & dilanjut setelah reset).
