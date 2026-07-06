<?php

namespace App\Database\Seeds;

/**
 * File Path: app/Database/Seeds/SampleDataSeeder.php
 *
 * DATA CONTOH per fitur untuk kondisi pertama pakai / demo serah terima:
 * penugasan, konsultasi & pengaduan, 5 layanan BK (bimbingan, konseling,
 * kolaborasi orang tua, kunjungan rumah, konferensi kasus), notifikasi,
 * pesan, asesmen, serta info karier & studi lanjut.
 *
 * Seeder ini mengasumsikan InitialDataSeeder sudah dijalankan (memakai id
 * akun/kelas/siswa dari sana). Tanggal ditulis absolut berpusat pada tanggal
 * acuan 2026-07-06; DatabaseSeeder menggesernya agar berpusat di "hari ini".
 *
 * Referensi id dari InitialDataSeeder:
 * - users: 3 Koordinator Koordinator BK 1, 5 Guru BK Guru BK 1, 6 Guru BK Guru BK 2, 7 Wali Kelas
 *   Wali Kelas 1, 8 siswa Siswa 1, 9 siswa Siswa 2, 10 ortu Asri, 11 ortu Rina.
 * - students: 1 Siswa 1 (Kelas 10 - C), 2 Siswa 2 (Kelas 11 - C).
 * - classes: 3 = Kelas 10 - C, 6 = Kelas 11 - C.
 */
class SampleDataSeeder extends BaseDataSeeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $this->seedBkAssignments($now);
        $this->seedConsultationComplaints($now);
        $this->seedBkServices($now);
        $this->seedCommunication($now);
        $this->seedAssessments($now);
        $this->seedCareerAndUniversity($now);

        echo "Data contoh per fitur selesai di-seed.\n";
    }

    private function seedBkAssignments(string $now): void
    {
        $this->insertRows('bk_assignments', [
            ['id' => 1, 'assignment_type' => 'Kelas Binaan', 'title' => 'Binaan Kelas 10 - C', 'instruction' => 'Guru BK 1 menjadi Guru BK pembina Kelas 10 - C pada semester berjalan.', 'assigned_by' => 3, 'assigned_to_user_id' => 5, 'class_id' => 3, 'priority' => 'Sedang', 'status' => 'Ditugaskan', 'due_at' => '2026-12-18 15:00:00', 'assigned_at' => '2026-07-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assignment_type' => 'Kelas Binaan', 'title' => 'Binaan Kelas 11 - C', 'instruction' => 'Guru BK 2 mendampingi Kelas 11 - C dan mencatat layanan yang berjalan.', 'assigned_by' => 3, 'assigned_to_user_id' => 6, 'class_id' => 6, 'priority' => 'Sedang', 'status' => 'Berjalan', 'due_at' => '2026-12-18 15:00:00', 'assigned_at' => '2026-07-01 08:15:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assignment_type' => 'Pelaksanaan Layanan', 'title' => 'Siapkan bimbingan klasikal etika digital', 'instruction' => 'Susun materi singkat, jadwal kelas, dan catatan tindak lanjut siswa yang perlu diperhatikan.', 'assigned_by' => 3, 'assigned_to_user_id' => 5, 'class_id' => 3, 'priority' => 'Tinggi', 'status' => 'Dibaca', 'due_at' => '2026-07-10 07:30:00', 'assigned_at' => '2026-07-02 09:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assignment_type' => 'Tindak Lanjut', 'title' => 'Rencanakan kunjungan rumah Siswa 1', 'instruction' => 'Koordinasikan jadwal dengan orang tua dan catat alamat kunjungan pada layanan kunjungan rumah.', 'assigned_by' => 3, 'assigned_to_user_id' => 5, 'student_id' => 1, 'source_type' => 'consultation_complaints', 'source_id' => 2, 'priority' => 'Tinggi', 'status' => 'Berjalan', 'due_at' => '2026-07-15 14:00:00', 'assigned_at' => '2026-07-03 10:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assignment_type' => 'Koordinasi', 'title' => 'Konferensi kasus adaptasi belajar Siswa 2', 'instruction' => 'Undang Guru BK, wali kelas, orang tua, dan siswa bila diperlukan. Catatan lengkap hanya untuk internal BK.', 'assigned_by' => 3, 'assigned_to_user_id' => 6, 'student_id' => 2, 'source_type' => 'bk_service_records', 'source_id' => 2, 'priority' => 'Mendesak', 'status' => 'Ditugaskan', 'due_at' => '2026-07-18 10:00:00', 'assigned_at' => '2026-07-04 13:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Pivot sasaran penugasan (Guru BK/kelas/siswa bisa lebih dari satu).
        $this->insertRows('bk_assignment_targets', [
            ['id' => 1, 'assignment_id' => 1, 'target_type' => 'counselor', 'user_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assignment_id' => 1, 'target_type' => 'class', 'class_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assignment_id' => 2, 'target_type' => 'counselor', 'user_id' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assignment_id' => 2, 'target_type' => 'class', 'class_id' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assignment_id' => 3, 'target_type' => 'counselor', 'user_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'assignment_id' => 3, 'target_type' => 'class', 'class_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'assignment_id' => 4, 'target_type' => 'counselor', 'user_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'assignment_id' => 4, 'target_type' => 'student', 'student_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'assignment_id' => 5, 'target_type' => 'counselor', 'user_id' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'assignment_id' => 5, 'target_type' => 'student', 'student_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('bk_assignment_status_histories', [
            ['id' => 1, 'assignment_id' => 1, 'status' => 'Ditugaskan', 'note' => 'Koordinator menetapkan kelas binaan.', 'changed_by' => 3, 'changed_at' => '2026-07-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assignment_id' => 2, 'status' => 'Ditugaskan', 'note' => 'Koordinator menetapkan kelas binaan.', 'changed_by' => 3, 'changed_at' => '2026-07-01 08:15:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assignment_id' => 2, 'status' => 'Berjalan', 'note' => 'Guru BK mulai memetakan kebutuhan kelas.', 'changed_by' => 6, 'changed_at' => '2026-07-02 09:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assignment_id' => 3, 'status' => 'Dibaca', 'note' => 'Materi sedang disiapkan.', 'changed_by' => 5, 'changed_at' => '2026-07-03 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assignment_id' => 4, 'status' => 'Berjalan', 'note' => 'Menunggu konfirmasi jadwal orang tua.', 'changed_by' => 5, 'changed_at' => '2026-07-04 11:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'assignment_id' => 5, 'status' => 'Ditugaskan', 'note' => 'Konferensi kasus masuk prioritas koordinasi.', 'changed_by' => 3, 'changed_at' => '2026-07-04 13:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedConsultationComplaints(string $now): void
    {
        $this->insertRows('consultation_complaints', [
            ['id' => 1, 'reporter_type' => 'student', 'reporter_user_id' => 9, 'subject_student_id' => 2, 'request_type' => 'Konsultasi', 'category' => 'Belajar', 'title' => 'Konsultasi kesulitan fokus belajar', 'description' => 'Siswa ingin berbicara dengan Guru BK karena merasa sulit fokus saat jam pertama.', 'occurred_at' => '2026-07-01 07:30:00', 'location' => 'Kelas 11 - C', 'priority' => 'Sedang', 'status' => 'Dijadwalkan', 'privacy_level' => 'Rahasia BK', 'visible_to_homeroom' => 0, 'assigned_to_user_id' => 6, 'handled_by' => 6, 'handled_at' => '2026-07-02 09:00:00', 'created_at' => '2026-07-01 08:05:00', 'updated_at' => $now],
            ['id' => 2, 'reporter_type' => 'parent', 'reporter_user_id' => 10, 'subject_student_id' => 1, 'request_type' => 'Laporan Orang Tua', 'category' => 'Kehadiran', 'title' => 'Orang tua meminta koordinasi perkembangan anak', 'description' => 'Orang tua menyampaikan anak terlihat kurang bersemangat dan meminta pendampingan sekolah.', 'occurred_at' => '2026-07-02 20:00:00', 'location' => 'Rumah siswa', 'priority' => 'Tinggi', 'status' => 'Diterima', 'privacy_level' => 'Rahasia BK', 'visible_to_homeroom' => 0, 'assigned_to_user_id' => 5, 'handled_by' => 5, 'handled_at' => '2026-07-03 08:30:00', 'created_at' => '2026-07-02 20:25:00', 'updated_at' => $now],
            ['id' => 3, 'reporter_type' => 'homeroom', 'reporter_user_id' => 7, 'subject_student_id' => 1, 'request_type' => 'Permintaan Bimbingan', 'category' => 'Motivasi belajar', 'title' => 'Wali kelas meminta pendampingan motivasi belajar', 'description' => 'Wali kelas melihat perubahan motivasi belajar dan meminta Guru BK melakukan pendampingan awal.', 'occurred_at' => '2026-07-03 10:00:00', 'location' => 'Ruang Kelas 10 - C', 'priority' => 'Sedang', 'status' => 'Ditinjau', 'privacy_level' => 'Dapat Dilihat Wali Kelas', 'visible_to_homeroom' => 1, 'assigned_to_user_id' => 5, 'created_at' => '2026-07-03 11:00:00', 'updated_at' => $now],
            ['id' => 4, 'reporter_type' => 'student', 'reporter_user_id' => 8, 'subject_student_id' => 1, 'request_type' => 'Permintaan Mediasi', 'category' => 'Relasi teman', 'title' => 'Siswa meminta bantuan menyelesaikan konflik teman', 'description' => 'Siswa meminta mediasi karena merasa perlu bantuan menyelesaikan kesalahpahaman dengan teman sekelas.', 'occurred_at' => '2026-07-05 12:30:00', 'location' => 'Koridor Kelas 10 - C', 'priority' => 'Sedang', 'status' => 'Diajukan', 'privacy_level' => 'Rahasia BK', 'visible_to_homeroom' => 0, 'assigned_to_user_id' => 5, 'created_at' => '2026-07-05 13:10:00', 'updated_at' => $now],
        ]);

        // Pivot siswa terkait laporan (mendukung lebih dari satu siswa per laporan).
        $this->insertRows('consultation_complaint_subjects', [
            ['id' => 1, 'complaint_id' => 1, 'student_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'complaint_id' => 2, 'student_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'complaint_id' => 3, 'student_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'complaint_id' => 4, 'student_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedBkServices(string $now): void
    {
        $this->insertRows('bk_service_records', [
            ['id' => 1, 'service_type' => 'Bimbingan', 'title' => 'Bimbingan klasikal etika media sosial', 'target_class_id' => 3, 'counselor_id' => 5, 'assignment_id' => 3, 'scheduled_at' => '2026-07-10 08:00:00', 'location' => 'Ruang Kelas 10 - C', 'status' => 'Dijadwalkan', 'duration_minutes' => 60, 'privacy_level' => 'Umum Terbatas', 'created_by' => 5, 'created_at' => '2026-07-02 09:30:00', 'updated_at' => $now],
            ['id' => 2, 'service_type' => 'Konseling', 'title' => 'Konseling individu Siswa 2', 'target_student_id' => 2, 'target_class_id' => 6, 'counselor_id' => 6, 'assignment_id' => 2, 'source_complaint_id' => 1, 'scheduled_at' => '2026-07-11 09:00:00', 'location' => 'Ruang BK 1', 'status' => 'Dijadwalkan', 'duration_minutes' => 45, 'privacy_level' => 'Rahasia BK', 'created_by' => 6, 'created_at' => '2026-07-02 10:00:00', 'updated_at' => $now],
            ['id' => 3, 'service_type' => 'Kolaborasi Orang Tua', 'title' => 'Kolaborasi orang tua Siswa 1', 'target_student_id' => 1, 'target_class_id' => 3, 'counselor_id' => 5, 'source_complaint_id' => 2, 'held_at' => '2026-07-04 10:00:00', 'location' => 'Ruang BK 2', 'status' => 'Selesai', 'duration_minutes' => 60, 'privacy_level' => 'Rahasia BK', 'created_by' => 5, 'created_at' => '2026-07-03 09:00:00', 'updated_at' => $now],
            ['id' => 4, 'service_type' => 'Kunjungan Rumah', 'title' => 'Kunjungan rumah Siswa 1', 'target_student_id' => 1, 'target_class_id' => 3, 'counselor_id' => 5, 'assignment_id' => 4, 'source_complaint_id' => 2, 'scheduled_at' => '2026-07-15 14:00:00', 'location' => 'Rumah siswa', 'status' => 'Dijadwalkan', 'duration_minutes' => 90, 'privacy_level' => 'Rahasia BK', 'created_by' => 5, 'created_at' => '2026-07-04 11:00:00', 'updated_at' => $now],
            ['id' => 5, 'service_type' => 'Konferensi Kasus', 'title' => 'Konferensi kasus adaptasi belajar Siswa 2', 'target_student_id' => 2, 'target_class_id' => 6, 'counselor_id' => 3, 'assignment_id' => 5, 'source_complaint_id' => 1, 'scheduled_at' => '2026-07-18 10:00:00', 'location' => 'Ruang Rapat BK', 'status' => 'Dijadwalkan', 'duration_minutes' => 90, 'privacy_level' => 'Rahasia Tinggi', 'created_by' => 3, 'created_at' => '2026-07-04 13:30:00', 'updated_at' => $now],
            ['id' => 6, 'service_type' => 'Konseling', 'title' => 'Konseling kelompok manajemen waktu', 'target_class_id' => 6, 'counselor_id' => 6, 'held_at' => '2026-07-03 09:00:00', 'location' => 'Ruang BK 3', 'status' => 'Selesai', 'duration_minutes' => 50, 'privacy_level' => 'Ringkasan Terbatas', 'created_by' => 6, 'created_at' => '2026-07-02 08:00:00', 'updated_at' => $now],
        ]);

        $this->updateRow('consultation_complaints', ['converted_service_record_id' => 2], ['id' => 1]);
        $this->updateRow('consultation_complaints', ['converted_service_record_id' => 3], ['id' => 2]);

        $this->insertRows('guidances', [
            ['id' => 1, 'bk_service_record_id' => 1, 'guidance_type' => 'Klasikal', 'material_topic' => 'Etika media sosial dan relasi teman', 'summary' => 'Materi bimbingan kelas untuk pencegahan konflik dan perundungan digital.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('counseling_sessions', [
            ['id' => 1, 'bk_service_record_id' => 2, 'student_id' => 2, 'counselor_id' => 6, 'class_id' => 6, 'session_type' => 'Individu', 'session_date' => '2026-07-11', 'session_time' => '09:00:00', 'location' => 'Ruang BK 1', 'topic' => 'Kesulitan fokus belajar', 'problem_description' => 'Siswa meminta ruang konsultasi karena sulit fokus pada jam pertama.', 'status' => 'Dijadwalkan', 'is_confidential' => 1, 'duration_minutes' => 45, 'counseling_type' => 'Individu', 'privacy_level' => 'Rahasia BK', 'follow_up_status' => 'Belum', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'bk_service_record_id' => 6, 'student_id' => null, 'counselor_id' => 6, 'class_id' => 6, 'session_type' => 'Kelompok', 'session_date' => '2026-07-03', 'session_time' => '09:00:00', 'location' => 'Ruang BK 3', 'topic' => 'Manajemen waktu belajar', 'problem_description' => 'Beberapa siswa Kelas 11 - C membutuhkan strategi belajar menjelang penilaian.', 'session_summary' => 'Siswa menyusun jadwal mingguan dan target belajar.', 'follow_up_plan' => 'Guru BK memantau komitmen belajar selama dua pekan.', 'status' => 'Selesai', 'is_confidential' => 1, 'duration_minutes' => 50, 'counseling_type' => 'Kelompok', 'privacy_level' => 'Ringkasan Terbatas', 'follow_up_status' => 'Berjalan', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('parent_collaborations', [
            ['id' => 1, 'bk_service_record_id' => 3, 'parent_name' => 'Ibu Siswa 1', 'topic' => 'Koordinasi motivasi belajar dan kehadiran', 'summary' => 'Orang tua menyampaikan perubahan kebiasaan belajar di rumah. Guru BK menjelaskan rencana pendampingan sekolah.', 'follow_up' => 'Guru BK mengirim ringkasan jadwal pendampingan kepada orang tua; wali kelas hanya menerima bagian umum.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('home_visits', [
            ['id' => 1, 'bk_service_record_id' => 4, 'address_snapshot' => 'Jalan Contoh No. 1, RT/RW 001/002, Desa Contoh, Kecamatan Contoh, Kabupaten Contoh, 40000', 'problem_topic' => 'Pendampingan motivasi belajar di rumah', 'visit_result' => 'Masih dijadwalkan. Guru BK menunggu konfirmasi akhir dari orang tua.', 'follow_up' => 'Siapkan lembar observasi singkat dan dokumentasi hasil kunjungan.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('case_conferences', [
            ['id' => 1, 'bk_service_record_id' => 5, 'chronology' => 'Siswa beberapa kali menyampaikan kesulitan fokus dan membutuhkan dukungan lintas pihak.', 'discussion_summary' => 'Konferensi akan mempertemukan Koordinator BK, Guru BK, wali kelas, dan orang tua untuk menentukan dukungan yang proporsional.', 'decision_summary' => 'Keputusan belum ditetapkan karena konferensi masih dijadwalkan.', 'follow_up_plan' => 'Kumpulkan ringkasan observasi wali kelas dan catatan konseling awal.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('session_participants', [
            ['id' => 1, 'bk_service_record_id' => 1, 'session_id' => null, 'participant_type' => 'class', 'participant_class_id' => 3, 'manual_name' => 'Kelas 10 - C', 'role_in_session' => 'Peserta bimbingan', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Diundang', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'bk_service_record_id' => 2, 'session_id' => 1, 'student_id' => 2, 'participant_type' => 'student', 'participant_student_id' => 2, 'manual_name' => 'Siswa 2', 'role_in_session' => 'Siswa konseling', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'bk_service_record_id' => 2, 'session_id' => 1, 'participant_type' => 'user', 'participant_user_id' => 6, 'manual_name' => 'Guru BK 2', 'role_in_session' => 'Guru BK', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'bk_service_record_id' => 3, 'session_id' => null, 'student_id' => 1, 'participant_type' => 'student', 'participant_student_id' => 1, 'manual_name' => 'Siswa 1', 'role_in_session' => 'Siswa terkait', 'attendance_status' => 'Hadir', 'joined_at' => '2026-07-04 10:00:00', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'bk_service_record_id' => 3, 'session_id' => null, 'participant_type' => 'parent', 'participant_parent_id' => 10, 'manual_name' => 'Ibu Siswa 1', 'role_in_session' => 'Orang tua', 'attendance_status' => 'Hadir', 'joined_at' => '2026-07-04 10:00:00', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'bk_service_record_id' => 4, 'session_id' => null, 'participant_type' => 'parent', 'participant_parent_id' => 10, 'manual_name' => 'Ibu Siswa 1', 'role_in_session' => 'Tuan rumah', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Diundang', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'bk_service_record_id' => 5, 'session_id' => null, 'student_id' => 2, 'participant_type' => 'student', 'participant_student_id' => 2, 'manual_name' => 'Siswa 2', 'role_in_session' => 'Siswa terkait', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Diundang', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'bk_service_record_id' => 5, 'session_id' => null, 'participant_type' => 'user', 'participant_user_id' => 3, 'manual_name' => 'Koordinator BK 1', 'role_in_session' => 'Koordinator BK', 'attendance_status' => 'Belum Hadir', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'bk_service_record_id' => 6, 'session_id' => 2, 'student_id' => 2, 'participant_type' => 'student', 'participant_student_id' => 2, 'manual_name' => 'Siswa 2', 'role_in_session' => 'Peserta konseling kelompok', 'attendance_status' => 'Hadir', 'joined_at' => '2026-07-03 09:00:00', 'invitation_status' => 'Konfirmasi', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('session_notes', [
            ['id' => 1, 'bk_service_record_id' => 1, 'session_id' => null, 'created_by' => 5, 'note_type' => 'Rencana', 'note_content' => 'Bimbingan klasikal menekankan etika komunikasi, jejak digital, dan cara meminta bantuan BK.', 'is_important' => 0, 'is_confidential' => 0, 'visibility_level' => 'Publik Terbatas', 'follow_up_status' => 'Belum', 'assigned_to_user_id' => 5, 'due_date' => '2026-07-10', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'bk_service_record_id' => 2, 'session_id' => 1, 'created_by' => 6, 'note_type' => 'Persiapan', 'note_content' => 'Sesi konseling perlu menjaga kerahasiaan isi. Wali kelas hanya menerima ringkasan tindak lanjut umum bila diperlukan.', 'is_important' => 1, 'is_confidential' => 1, 'visibility_level' => 'Internal BK', 'follow_up_status' => 'Belum', 'assigned_to_user_id' => 6, 'due_date' => '2026-07-11', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'bk_service_record_id' => 3, 'session_id' => null, 'created_by' => 5, 'note_type' => 'Hasil', 'note_content' => 'Orang tua setuju berkoordinasi mingguan. Ringkasan untuk wali kelas hanya berisi kebutuhan pemantauan umum.', 'is_important' => 1, 'is_confidential' => 1, 'visibility_level' => 'Koordinator dan Guru BK', 'follow_up_status' => 'Berjalan', 'assigned_to_user_id' => 5, 'due_date' => '2026-07-08', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'bk_service_record_id' => 5, 'session_id' => null, 'created_by' => 3, 'note_type' => 'Agenda', 'note_content' => 'Konferensi kasus membahas kebutuhan dukungan siswa tanpa membuka catatan konseling rinci ke pihak yang tidak berwenang.', 'is_important' => 1, 'is_confidential' => 1, 'visibility_level' => 'Internal BK', 'follow_up_status' => 'Belum', 'assigned_to_user_id' => 6, 'due_date' => '2026-07-18', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedCommunication(string $now): void
    {
        $this->insertRows('notifications', [
            ['id' => 1, 'user_id' => 5, 'title' => 'Tugas baru dari Koordinator BK', 'message' => 'Siapkan bimbingan klasikal etika digital untuk Kelas 10 - C.', 'type' => 'assignment', 'link' => '/counselor/assignments', 'data' => json_encode(['assignment_id' => 3]), 'is_read' => 0, 'created_at' => '2026-07-02 09:05:00', 'updated_at' => $now],
            ['id' => 2, 'user_id' => 6, 'title' => 'Konseling Siswa 2 dijadwalkan', 'message' => 'Sesi konseling individu dijadwalkan pada 11 Juli 2026 pukul 09.00.', 'type' => 'bk_service', 'link' => '/counselor/counseling', 'data' => json_encode(['service_id' => 2]), 'is_read' => 0, 'created_at' => '2026-07-02 10:05:00', 'updated_at' => $now],
            ['id' => 3, 'user_id' => 9, 'title' => 'Permintaan konsultasi diterima', 'message' => 'Guru BK telah menjadwalkan konsultasi kamu.', 'type' => 'consultation', 'link' => '/student/consultations', 'data' => json_encode(['complaint_id' => 1]), 'is_read' => 0, 'created_at' => '2026-07-02 10:10:00', 'updated_at' => $now],
            ['id' => 4, 'user_id' => 10, 'title' => 'Kolaborasi orang tua selesai dicatat', 'message' => 'Ringkasan tindak lanjut sudah dicatat Guru BK.', 'type' => 'parent_collaboration', 'link' => '/parent/jadwal-bk', 'data' => json_encode(['service_id' => 3]), 'is_read' => 1, 'read_at' => '2026-07-04 13:00:00', 'created_at' => '2026-07-04 12:30:00', 'updated_at' => $now],
            ['id' => 5, 'user_id' => 3, 'title' => 'Konferensi kasus menunggu peserta', 'message' => 'Peserta konferensi kasus Siswa 2 belum semuanya mengonfirmasi.', 'type' => 'case_conference', 'link' => '/koordinator/case-conferences', 'data' => json_encode(['service_id' => 5]), 'is_read' => 0, 'created_at' => '2026-07-05 14:00:00', 'updated_at' => $now],
        ]);

        // Pesan ala chat (percakapan 1-lawan-1); user_one_id selalu <= user_two_id.
        $this->insertRows('conversations', [
            // Koordinator Koordinator BK 1(3) <-> Guru BK Guru BK 1(5)
            ['id' => 1, 'user_one_id' => 3, 'user_two_id' => 5, 'last_message_id' => 2, 'last_message_at' => '2026-07-03 10:05:00', 'created_by' => 3, 'created_at' => '2026-07-03 10:00:00', 'updated_at' => $now],
            // Guru BK Guru BK 1(5) <-> Wali Kelas Wali Kelas 1(7)
            ['id' => 2, 'user_one_id' => 5, 'user_two_id' => 7, 'last_message_id' => 4, 'last_message_at' => '2026-07-04 09:20:00', 'created_by' => 5, 'created_at' => '2026-07-04 09:00:00', 'updated_at' => $now],
            // Guru BK Guru BK 1(5) <-> Orang Tua 1(10)
            ['id' => 3, 'user_one_id' => 5, 'user_two_id' => 10, 'last_message_id' => 6, 'last_message_at' => '2026-07-05 14:10:00', 'created_by' => 10, 'created_at' => '2026-07-05 14:00:00', 'updated_at' => $now],
            // Guru BK Guru BK 1(5) <-> Siswa Siswa 1(8)
            ['id' => 4, 'user_one_id' => 5, 'user_two_id' => 8, 'last_message_id' => 7, 'last_message_at' => '2026-07-06 08:30:00', 'created_by' => 5, 'created_at' => '2026-07-06 08:30:00', 'updated_at' => $now],
        ]);

        $this->insertRows('messages', [
            ['id' => 1, 'conversation_id' => 1, 'subject' => '', 'body' => 'Guru BK 1, mohon konfirmasi jam bimbingan klasikal Kelas 10 - C pada Jumat ini.', 'created_by' => 3, 'is_draft' => 0, 'created_at' => '2026-07-03 10:00:00', 'updated_at' => $now],
            ['id' => 2, 'conversation_id' => 1, 'subject' => '', 'body' => 'Baik Pak, jam ke-3 dan ke-4 insyaAllah siap.', 'created_by' => 5, 'is_draft' => 0, 'created_at' => '2026-07-03 10:05:00', 'updated_at' => $now],
            ['id' => 3, 'conversation_id' => 2, 'subject' => '', 'body' => 'Wali Kelas 1, ada perkembangan baik dari ananda di kelas binaan Ibu.', 'created_by' => 5, 'is_draft' => 0, 'created_at' => '2026-07-04 09:00:00', 'updated_at' => $now],
            ['id' => 4, 'conversation_id' => 2, 'subject' => '', 'body' => 'Alhamdulillah, terima kasih informasinya Guru BK 1.', 'created_by' => 7, 'is_draft' => 0, 'created_at' => '2026-07-04 09:20:00', 'updated_at' => $now],
            ['id' => 5, 'conversation_id' => 3, 'subject' => '', 'body' => 'Assalamualaikum Bu, kami ingin memastikan jadwal pendampingan ananda.', 'created_by' => 10, 'is_draft' => 0, 'created_at' => '2026-07-05 14:00:00', 'updated_at' => $now],
            ['id' => 6, 'conversation_id' => 3, 'subject' => '', 'body' => 'Waalaikumsalam Bu, akan kami atur pekan depan dan kabari kembali.', 'created_by' => 5, 'is_draft' => 0, 'created_at' => '2026-07-05 14:10:00', 'updated_at' => $now],
            ['id' => 7, 'conversation_id' => 4, 'subject' => '', 'body' => 'Nak, jangan lupa mengisi asesmen minat karier ya sebelum tenggat.', 'created_by' => 5, 'is_draft' => 0, 'created_at' => '2026-07-06 08:30:00', 'updated_at' => $now],
        ]);

        // Penanda dibaca per gelembung; sebagian sengaja belum dibaca agar badge terlihat.
        $this->insertRows('message_participants', [
            ['id' => 1, 'message_id' => 1, 'user_id' => 5, 'role' => 'recipient', 'is_read' => 1, 'read_at' => '2026-07-03 10:04:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'message_id' => 2, 'user_id' => 3, 'role' => 'recipient', 'is_read' => 1, 'read_at' => '2026-07-03 10:06:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'message_id' => 3, 'user_id' => 7, 'role' => 'recipient', 'is_read' => 1, 'read_at' => '2026-07-04 09:19:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'message_id' => 4, 'user_id' => 5, 'role' => 'recipient', 'is_read' => 1, 'read_at' => '2026-07-04 09:25:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'message_id' => 5, 'user_id' => 5, 'role' => 'recipient', 'is_read' => 1, 'read_at' => '2026-07-05 14:05:00', 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'message_id' => 6, 'user_id' => 10, 'role' => 'recipient', 'is_read' => 0, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'message_id' => 7, 'user_id' => 8, 'role' => 'recipient', 'is_read' => 0, 'starred' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedAssessments(string $now): void
    {
        $this->insertRows('assessments', [
            ['id' => 1, 'title' => 'Asesmen Minat Karier dan Studi Lanjut', 'description' => 'Memetakan minat awal siswa terhadap bidang studi dan karier.', 'assessment_type' => 'Minat Bakat', 'evaluation_mode' => 'survey', 'target_audience' => 'Class', 'target_class_id' => 6, 'created_by' => 6, 'is_active' => 1, 'is_published' => 1, 'start_date' => '2026-07-01', 'end_date' => '2026-07-31', 'duration_minutes' => 30, 'passing_score' => null, 'use_passing_score' => 0, 'show_score_to_student' => 1, 'max_attempts' => 1, 'show_result_immediately' => 1, 'allow_review' => 1, 'instructions' => 'Jawab sesuai kondisi dan minat diri saat ini.', 'total_questions' => 4, 'total_participants' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Screening Kesejahteraan Siswa', 'description' => 'Screening singkat untuk melihat kebutuhan pendampingan dasar.', 'assessment_type' => 'Psikologi', 'evaluation_mode' => 'score_only', 'target_audience' => 'All', 'created_by' => 5, 'is_active' => 1, 'is_published' => 1, 'start_date' => '2026-07-03', 'end_date' => '2026-08-03', 'duration_minutes' => 20, 'passing_score' => null, 'use_passing_score' => 0, 'show_score_to_student' => 0, 'max_attempts' => 1, 'show_result_immediately' => 0, 'allow_review' => 1, 'instructions' => 'Pilih jawaban yang paling mendekati kondisi kamu.', 'total_questions' => 3, 'total_participants' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_questions', [
            ['id' => 1, 'assessment_id' => 1, 'question_text' => 'Bidang kegiatan apa yang paling kamu sukai?', 'question_type' => 'Multiple Choice', 'options' => json_encode(['Teknologi', 'Kesehatan', 'Pendidikan', 'Bisnis']), 'points' => 0, 'order_number' => 1, 'is_required' => 1, 'dimension' => 'minat', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 1, 'question_text' => 'Saya senang menyelesaikan masalah dengan logika.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 2, 'is_required' => 1, 'dimension' => 'analitis', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 1, 'question_text' => 'Saya nyaman berdiskusi tentang rencana masa depan dengan Guru BK.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 3, 'is_required' => 1, 'dimension' => 'kesiapan_konsultasi', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'assessment_id' => 1, 'question_text' => 'Tuliskan pilihan studi lanjut atau karier yang sedang kamu pikirkan.', 'question_type' => 'Essay', 'points' => 0, 'order_number' => 4, 'is_required' => 0, 'dimension' => 'narasi', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'assessment_id' => 2, 'question_text' => 'Saya merasa punya orang yang dapat saya hubungi saat ada masalah.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 1, 'is_required' => 1, 'dimension' => 'dukungan', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'assessment_id' => 2, 'question_text' => 'Saya merasa jadwal belajar saya teratur.', 'question_type' => 'Rating Scale', 'options' => json_encode(['1', '2', '3', '4', '5']), 'points' => 0, 'order_number' => 2, 'is_required' => 1, 'dimension' => 'rutinitas', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'assessment_id' => 2, 'question_text' => 'Saya ingin dibantu Guru BK untuk menyusun rencana belajar.', 'question_type' => 'True/False', 'options' => json_encode(['True', 'False']), 'points' => 0, 'order_number' => 3, 'is_required' => 1, 'dimension' => 'kebutuhan_layanan', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_assignees', [
            ['id' => 1, 'assessment_id' => 1, 'student_id' => 2, 'assigned_by' => 6, 'assigned_at' => '2026-07-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 2, 'student_id' => 1, 'assigned_by' => 5, 'assigned_at' => '2026-07-03 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 2, 'student_id' => 2, 'assigned_by' => 5, 'assigned_at' => '2026-07-03 08:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_results', [
            ['id' => 1, 'assessment_id' => 1, 'student_id' => 2, 'attempt_number' => 1, 'status' => 'Completed', 'questions_answered' => 4, 'total_questions' => 4, 'started_at' => '2026-07-05 08:10:00', 'completed_at' => '2026-07-05 08:28:00', 'time_spent_seconds' => 1080, 'interpretation' => 'Minat dominan pada teknologi dan kegiatan analitis.', 'dimension_scores' => json_encode(['Teknologi' => 84, 'Analitis' => 78, 'Kesiapan Konsultasi' => 75]), 'recommendations' => 'Eksplorasi informatika, data, dan robotika. Diskusikan pilihan studi lanjut dengan Guru BK.', 'reviewed_by' => 6, 'reviewed_at' => '2026-07-05 10:00:00', 'counselor_notes' => 'Cocok diberi referensi program studi terkait teknologi.', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'assessment_id' => 2, 'student_id' => 1, 'attempt_number' => 1, 'status' => 'In Progress', 'questions_answered' => 1, 'total_questions' => 3, 'started_at' => '2026-07-06 08:00:00', 'time_spent_seconds' => 300, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'assessment_id' => 2, 'student_id' => 2, 'attempt_number' => 1, 'status' => 'Assigned', 'questions_answered' => 0, 'total_questions' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('assessment_answers', [
            ['id' => 1, 'question_id' => 1, 'student_id' => 2, 'result_id' => 1, 'answer_option' => 'Teknologi', 'answered_at' => '2026-07-05 08:12:00', 'time_spent_seconds' => 120, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'question_id' => 2, 'student_id' => 2, 'result_id' => 1, 'answer_option' => '5', 'answered_at' => '2026-07-05 08:15:00', 'time_spent_seconds' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'question_id' => 3, 'student_id' => 2, 'result_id' => 1, 'answer_option' => '4', 'answered_at' => '2026-07-05 08:20:00', 'time_spent_seconds' => 100, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'question_id' => 4, 'student_id' => 2, 'result_id' => 1, 'answer_text' => 'Saya tertarik belajar pemrograman dan membuat aplikasi.', 'answered_at' => '2026-07-05 08:27:00', 'time_spent_seconds' => 600, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'question_id' => 5, 'student_id' => 1, 'result_id' => 2, 'answer_option' => '3', 'answered_at' => '2026-07-06 08:05:00', 'time_spent_seconds' => 120, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedCareerAndUniversity(string $now): void
    {
        $this->insertRows('career_options', [
            ['id' => 1, 'title' => 'Pengembang Perangkat Lunak', 'sector' => 'Teknologi Informasi', 'min_education' => 'D3/S1', 'description' => 'Membangun aplikasi web, mobile, dan sistem informasi.', 'required_skills' => json_encode(['Logika', 'Pemrograman', 'Kolaborasi']), 'pathways' => 'Belajar dasar pemrograman, membuat portofolio, mengikuti magang.', 'avg_salary_idr' => 7500000, 'demand_level' => 9, 'external_links' => json_encode([['label' => 'Dicoding', 'url' => 'https://www.dicoding.com']]), 'is_active' => 1, 'created_by' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Konselor Pendidikan', 'sector' => 'Pendidikan', 'min_education' => 'S1', 'description' => 'Membantu siswa memahami potensi dan mengambil keputusan pendidikan.', 'required_skills' => json_encode(['Empati', 'Komunikasi', 'Observasi']), 'pathways' => 'S1 BK/Psikologi, praktik lapangan, dan pengembangan kompetensi konseling.', 'avg_salary_idr' => 5000000, 'demand_level' => 7, 'is_active' => 1, 'created_by' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'title' => 'Perawat', 'sector' => 'Kesehatan', 'min_education' => 'D3', 'description' => 'Memberikan layanan keperawatan di fasilitas kesehatan.', 'required_skills' => json_encode(['Ketelitian', 'Empati', 'Manajemen waktu']), 'pathways' => 'D3/S1 Keperawatan, uji kompetensi, praktik klinis.', 'avg_salary_idr' => 5200000, 'demand_level' => 8, 'is_active' => 1, 'created_by' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'title' => 'Guru dan Tenaga Pendidikan', 'sector' => 'Pendidikan', 'min_education' => 'S1', 'description' => 'Mengajar, membimbing, dan mengembangkan kegiatan pendidikan.', 'required_skills' => json_encode(['Komunikasi', 'Manajemen kelas', 'Kesabaran']), 'pathways' => 'S1 Pendidikan, PPG, dan praktik mengajar.', 'avg_salary_idr' => 4800000, 'demand_level' => 6, 'is_active' => 1, 'created_by' => 6, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('university_info', [
            ['id' => 1, 'university_name' => 'Institut Teknologi Bandung', 'alias' => 'ITB', 'accreditation' => 'Unggul', 'location' => 'Bandung', 'website' => 'https://www.itb.ac.id', 'description' => 'Perguruan tinggi negeri berfokus pada sains, teknologi, seni, dan desain.', 'faculties' => json_encode(['STEI', 'FTI', 'FMIPA']), 'programs' => json_encode([['name' => 'Informatika', 'degree' => 'S1'], ['name' => 'Sistem dan Teknologi Informasi', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah', 'Beasiswa alumni']), 'contacts' => json_encode(['email' => 'humas@itb.ac.id']), 'is_active' => 1, 'created_by' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'university_name' => 'Universitas Pendidikan Indonesia', 'alias' => 'UPI', 'accreditation' => 'Unggul', 'location' => 'Bandung', 'website' => 'https://www.upi.edu', 'description' => 'Perguruan tinggi negeri dengan kekuatan utama pada bidang pendidikan.', 'faculties' => json_encode(['FIP', 'FPIPS', 'FPMIPA']), 'programs' => json_encode([['name' => 'Bimbingan dan Konseling', 'degree' => 'S1'], ['name' => 'Pendidikan Guru', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah']), 'is_active' => 1, 'created_by' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'university_name' => 'Universitas Padjadjaran', 'alias' => 'UNPAD', 'accreditation' => 'Unggul', 'location' => 'Sumedang', 'website' => 'https://www.unpad.ac.id', 'description' => 'Perguruan tinggi negeri dengan pilihan program kesehatan, sosial, dan sains.', 'faculties' => json_encode(['FK', 'FIK', 'FMIPA']), 'programs' => json_encode([['name' => 'Keperawatan', 'degree' => 'S1'], ['name' => 'Psikologi', 'degree' => 'S1']]), 'admission_info' => 'SNBP, SNBT, dan seleksi mandiri.', 'tuition_range' => 'UKT bertingkat', 'scholarships' => json_encode(['KIP Kuliah', 'Beasiswa prestasi']), 'is_active' => 1, 'created_by' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->insertRows('student_saved_careers', [
            ['id' => 1, 'student_id' => 2, 'career_id' => 1, 'created_at' => '2026-07-05 10:00:00', 'updated_at' => $now],
            ['id' => 2, 'student_id' => 1, 'career_id' => 2, 'created_at' => '2026-07-06 10:00:00', 'updated_at' => $now],
        ]);

        $this->insertRows('student_saved_universities', [
            ['id' => 1, 'student_id' => 2, 'university_id' => 1, 'created_at' => '2026-07-05 10:05:00', 'updated_at' => $now],
            ['id' => 2, 'student_id' => 1, 'university_id' => 2, 'created_at' => '2026-07-06 10:05:00', 'updated_at' => $now],
        ]);
    }
}
