# CLAUDE.md — Panduan Pengembangan SIB-K

Panduan membangun fitur pengembangan **Sistem Informasi Bimbingan dan Konseling (SIB-K)** MA Persis 31 Banjaran. Dibaca tiap sesi; jaga ringkas & akurat.

## 1. Tentang proyek
Aplikasi BK berbasis web (lanjutan kerja praktik). Arah pengembangan **bergeser dari pencatatan pelanggaran/poin** ke **administrasi layanan BK**: konsultasi/pengaduan, bimbingan, konseling, kolaborasi orang tua, kunjungan rumah, konferensi kasus, asesmen, info karier/studi, pesan, notifikasi, penugasan, plus pembaruan dashboard/laporan/impor. **Kerahasiaan data = aturan utama.**

## 2. Tech stack & cara menjalankan
- **CodeIgniter 4.6.3** (template Qovex/Bootstrap 5), PHP, MySQL. Lingkungan **Laragon (Apache)**.
- baseURL `http://localhost:30/`. DB `sibk_mapersis31` @ `127.0.0.1`, user `root`, password kosong (lokal).
- Migrasi: `php spark migrate` / rollback `php spark migrate:rollback`. Seed: `php spark db:seed <Seeder>`. Rute: `php spark routes`. Log: `writable/logs/`.
- **OPcache nonaktif** lokal → perubahan PHP langsung berlaku.
- **Jangan commit `.env`** (kredensial).
- Baca PDF via `pdftotext -f <a> -l <b> file.pdf -` (poppler `pdftoppm`/render TIDAK ada). Baca `.docx` via `python` + `zipfile` (`word/document.xml`).

## 3. Sumber kebenaran (baca sebelum tiap fitur)
**Authoritative utama**: file rencana pengembangan + diagram draw.io. Prototipe = patokan sekunder (besar kemungkinan berubah setelah evaluasi/konfirmasi ulang responden). Bab 3 skripsi = authoritative untuk **Black Box Testing**. Semua bisa berubah sesuai bimbingan/keadaan.
- `backupNInformasi/hasilAnalisis/Rencana_Pengembangan_SIBK_Berdasarkan_Wawancara_dan_Rancangan.docx` — **rencana induk** (fitur, ERD, halaman, hak akses). *(Varian "buatan AI" diabaikan.)*
- `backupNInformasi/hasilWawancara/Data_Fitur_Diminta_Guru_BK_Revisi_Bu_Guru BK 1.docx` — detail kebutuhan & kerahasiaan per fitur (revisi Guru BK 1).
- `backupNInformasi/hasilWawancara/transkripRekamanSeluruhWawancara.txt` + `hasilWawancaraTertulis.csv` (atau `Hasil_Wawancara_Tertulis_SIBK_Rapi.docx`).
- `backupNInformasi/diagram/diagram_prototipe_skripsi.drawio` (+ `.drawio.pdf`) — halaman: 8 Activity, 8 Use Case, 1 **ERD** ("ERD Bubble"), 8 **Wireframe**, 1 **CRUD**. PNG ada di `diagram/gambarDariDrawio/{activityDiagram,useCaseDiagram,CRUD}` (Wireframe & ERD hanya di `.drawio`/`.pdf`).
- `backupNInformasi/fileHasilKPdanDraftSkripsi/LaporanKPREKASLIF2022008rev.pdf` — baseline fitur lama; `Draft_Skripsi_..._Bab3_...docx` — metode & pengujian.
- **Prototipe** (`/prototype`) = referensi visual/alur per peran. Bangun fitur nyata menyerupai prototipe.
- ⚠️ Istilah skripsi masih menulis **"Pelaporan Pelanggaran"** untuk yang sekarang bernama **"Konsultasi & Pengaduan"** (rencana awal; akan diperbaiki di Bab 4).

## 4. Peran & RBAC (WAJIB diikuti)
`roles.id`: 1 Admin, 2 Koordinator BK, 3 Guru BK, 4 Wali Kelas, 5 Siswa, 6 Orang Tua.
- Rute dikunci per peran via **nested group** + filter `role:` lalu `permission:` (`app/Config/Routes.php`). **Jangan** `'auth,role:...'` (error CI4) — pakai group bersarang.
- Filter `permission:` HARUS memakai `permissions.permission_name` yang ada (36 permission). Permission BK sudah di-seed: `manage_bk_services`,`view_bk_services`,`manage_bk_assignments`,`view_bk_assignments`,`manage_consultation_complaints`,`review_consultation_complaints`,`submit_consultation_complaints`,`manage_assessments`,`take_assessments`,`manage_career_info`/`view_career_info`,`send_messages`,`view_counseling_sessions`/`manage_counseling_sessions`,`schedule_counseling`,`generate_bk_reports`/`view_bk_reports`,`view_reports*`/`generate_reports*`,`view_dashboard`,`view_all_students`,`view_student_portfolio`,`import_export_data`,`access_simulation_suite`. Jika kurang → tambah di `PermissionSeeder` + `RolePermissionSeeder` (jangan mengarang nama di rute).
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

## 7. Database (skema SUDAH ada — migration `2026-06-09-000001_create_bk_development_schema`)
DB punya 40 tabel; **tabel layanan BK sudah dibuat, tinggal diisi fitur**. Kolom utama:
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

**Dipakai ulang**: `users, roles, permissions, role_permissions, students, classes, academic_years, assessments(+questions/assignees/results/answers), career_options, university_info, student_saved_{careers,universities}, messages, message_participants, notifications, settings, simulation_access_grants`.
**`violation_submissions`** = fitur lama "Pengaduan Pelanggaran" (tersembunyi) — cikal bakal Konsultasi & Pengaduan; pertimbangkan migrasi/arsip ke `consultation_complaints`.
**Dihapus**: tabel `violations/violation_categories/sanctions`/poin (sudah dihapus migrasi 2026-06-06).

## 8. Status implementasi & urutan build
**Sudah ada Model (+ sebagian Controller) → PERLUAS, jangan buat ulang:** Assessment*, CareerOption/UniversityInfo, Message*, Notification, Student, CounselingSession/SessionNote/SessionParticipant (model ada), Report, ViolationSubmissions (lengkap semua peran).
**Schema-only, BELUM ada kode fitur → BANGUN controller/model/view/route:** `bk_service_records`, `guidances`, `parent_collaborations`, `home_visits`, `case_conferences`, `consultation_complaints`(+attachments), `bk_assignments`(+histories). `counseling_sessions` perlu diselaraskan ke induk `bk_service_records`.

**Urutan saran** (pondasi → prioritas wawancara: konsultasi/pelaporan, asesmen, info karier paling diminta):
0. Layer bersama layanan BK (`bk_service_records` + `session_participants` + `session_notes`) + Model + wiring permission.
1. Konsultasi & Pengaduan  2. Notifikasi  3. Pesan  4. Asesmen  5. Info Karier & Studi
6. Bimbingan  7. Konseling  8. Kolaborasi Orang Tua  9. Kunjungan Rumah  10. Konferensi Kasus  11. Penugasan
**Pembaruan**: Dashboard (ringkasan per peran), Laporan (per jenis layanan, ekspor PDF/Excel), Impor Siswa & Orang Tua (Koordinator semua kelas; Wali Kelas kelas perwaliannya).
**Pertimbangan dari Guru BK 1** (boleh masuk tahap berikut): Siswa mengisi **Identitas Pribadi** sendiri (hobi, ekskul/organisasi; NISN/NIK opsional); **Jadwal Layanan Siswa** terbatas; **Info Guru BK/Wali Kelas** (kontak). Bimbingan = Kelompok/Klasikal/Kelas Besar; Konseling = Individu/Kelompok.

### Kriteria "selesai" tiap fitur
Skema sesuai + migrasi `up()/down()` jalan · CRUD & alur sesuai Activity Diagram + **Matriks CRUD (§6)** per peran · permission terdaftar & rute terkunci benar · UI hanya tampilkan yang relevan · validasi diterapkan · kerahasiaan terjaga · diuji login per peran (§9) · tanpa error · commit.

## 9. Pengujian & verifikasi
Akun seed: `admin/admin123`, `koordinator/koordinator123`, `gurubk_1/gurubk123`, `gurubk_2/gurubk123`, `walikelas_1/walikelas123`. (Siswa/Orang Tua: cek `AdminSeeder`/`UserSeeder`/`StudentSeeder`.)
- Uji tiap fitur login per peran; pastikan hak akses & kerahasiaan sesuai §6.
- Uji HTTP via curl: ambil **satu** token CSRF (`grep ... | head -1`, ada juga di meta tag), kirim sebagai field `csrf_token_sibk`.
- Skripsi: **Black Box Testing (Equivalence Partitioning)**, target 100% fungsi — siapkan data valid & tidak valid per fitur.

## 10. Modul prototipe & evaluasi (JANGAN diubah tanpa diminta)
- `/prototype` (PrototypeController + `app/Views/prototype/`) = referensi visual; akses via `access_simulation_suite`.
- `/prototype/evaluation` (PrototypeEvaluationController; tabel `prototype_evaluations`, `prototype_evaluation_answers`) = **modul sementara** evaluasi skripsi, dihapus saat aplikasi final.
- Lihat memory: aturan akses layanan BK & modul evaluasi.

## 11. Alur kerja per fitur
Satu fitur = satu sesi. **Plan Mode** (rancang → disetujui) → implementasi → `php spark migrate` (bila perlu) → uji login per peran → **commit** (branch per fitur). Commit sering agar progres aman bila batas penggunaan tercapai (proses berhenti & dilanjut setelah reset).
