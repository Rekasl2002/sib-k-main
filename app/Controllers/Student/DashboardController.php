<?php
// app/Controllers/Student/DashboardController.php

namespace App\Controllers\Student;

use CodeIgniter\I18n\Time;

class DashboardController extends BaseStudentController
{
    /**
     * Dashboard ringkas siswa:
     * - Info siswa & kelas
     * - Tahun ajaran aktif
     * - Jadwal konseling mendatang (Individu/Kelompok/Klasikal)
     * - Asesmen tersedia (All / Class / Grade [roman/angka] / Individual)
     * - Hasil asesmen terbaru
     */
    public function index()
    {
        $this->requireStudent();

        $today = Time::today($this->tz)->toDateString();

        /**
         * FIX SCHEMA:
         * - students.full_name sudah dihapus
         * - Nama siswa diambil dari users.full_name via students.user_id
         * - Tetap alias sebagai "full_name" agar view student/dashboard tetap kompatibel.
         */
        $student = $this->db->table('students s')
            ->select(
                's.id as student_id, u.full_name as full_name, s.nis, s.nisn, s.class_id, s.total_violation_points,' .
                'c.class_name, c.grade_level, c.major'
            )
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.id', $this->studentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRow();

        // Guard ringan bila profil tidak ditemukan
        if (!$student) {
            return view('student/dashboard', [
                'student'          => null,
                'activeYear'       => null,
                'upcomingSessions' => [],
                'assessments'      => [],
                'recentResults'    => [],
            ]);
        }

        $classId = (int) ($student->class_id ?? 0);

        // 2) Tahun ajaran aktif (pakai alias agar view bisa baca year_label)
        $activeYear = $this->db->table('academic_years')
            ->select('id, year_name AS year_label, semester, start_date, end_date')
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        /**
         * 3) Jadwal konseling mendatang:
         * - Individu:   cs.student_id = siswa
         * - Kelompok:   ada sp.student_id = siswa
         * - Klasikal:   cs.class_id = kelas siswa
         *
         * NOTE privasi:
         * - jika is_confidential=1, topik disamarkan dan lokasi disembunyikan.
         */
        $b = $this->db->table('counseling_sessions cs')
            ->select('
                cs.id,
                cs.session_type,
                cs.session_date,
                cs.session_time,
                cs.location,
                cs.topic,
                cs.status,
                cs.is_confidential
            ')
            ->join(
                'session_participants sp',
                'sp.session_id = cs.id AND sp.student_id = ' . (int) $this->studentId . ' AND sp.deleted_at IS NULL',
                'left'
            )
            ->where('cs.deleted_at', null)
            ->where('DATE(cs.session_date) >=', $today)
            ->where('cs.status !=', 'Dibatalkan')
            ->groupStart()
                // Individu
                ->groupStart()
                    ->where('cs.session_type', 'Individu')
                    ->where('cs.student_id', (int) $this->studentId)
                ->groupEnd()
                // Kelompok (berdasarkan participant)
                ->orGroupStart()
                    ->where('cs.session_type', 'Kelompok')
                    ->where('sp.student_id IS NOT NULL', null, false)
                ->groupEnd();

        // Klasikal hanya kalau classId valid (>0)
        if ($classId > 0) {
            $b->orGroupStart()
                ->where('cs.session_type', 'Klasikal')
                ->where('cs.class_id', $classId)
            ->groupEnd();
        }

        $upcomingSessions = $b->groupEnd()
            ->distinct()
            ->orderBy('cs.session_date', 'ASC')
            ->orderBy('cs.session_time', 'ASC')
            ->limit(5)
            ->get()
            ->getResult();

        // Samarkan konten confidential di list dashboard
        foreach ($upcomingSessions as $s) {
            if ((int) ($s->is_confidential ?? 0) === 1) {
                $s->topic    = 'Sesi Konseling (Terbatas)';
                $s->location = null;
            }
        }

        // 4) Meta kelas/tingkat untuk filter asesmen (dukung roman/angka)
        $gradeRoman = null;
        $gradeNum   = null;

        $romanToNum = ['X' => '10', 'XI' => '11', 'XII' => '12'];
        $numToRoman = ['10' => 'X', '11' => 'XI', '12' => 'XII'];

        $rawGrade = strtoupper((string) ($student->grade_level ?? ''));
        if (isset($romanToNum[$rawGrade])) {
            $gradeRoman = $rawGrade;
            $gradeNum   = $romanToNum[$rawGrade];
        } elseif (isset($numToRoman[$rawGrade])) {
            $gradeNum   = $rawGrade;
            $gradeRoman = $numToRoman[$rawGrade];
        } else {
            $gradeRoman = $rawGrade ?: null;
            $gradeNum   = $romanToNum[$rawGrade] ?? null;
        }

        /**
         * 5) Asesmen tersedia (aktif, publish, window valid, target match, non-deleted)
         * + flag has_done agar view bisa menyembunyikan tombol "Kerjakan" bila sudah dikerjakan
         *
         * Catatan: groupBy diperluas untuk aman jika MySQL strict mode (ONLY_FULL_GROUP_BY) aktif.
         */
        $assessments = $this->db->table('assessments a')
            ->select(
                'a.id, a.title, a.assessment_type, a.start_date, a.end_date, a.duration_minutes,' .
                'a.target_audience, a.target_class_id, a.target_grade, a.total_questions'
            )
            // untuk target Individual, cek assignment lewat assessment_results yang non-deleted (mengikuti pola file lama)
            ->join(
                'assessment_results ar_i',
                'ar_i.assessment_id = a.id AND ar_i.student_id = ' . (int) $this->studentId . ' AND ar_i.deleted_at IS NULL',
                'left'
            )
            // hasil/riwayat pengerjaan siswa (semua target), non-deleted
            ->join(
                'assessment_results rs',
                'rs.assessment_id = a.id AND rs.student_id = ' . (int) $this->studentId . ' AND rs.deleted_at IS NULL',
                'left'
            )
            ->select("MAX(CASE WHEN rs.status IN ('Completed','Graded') THEN 1 ELSE 0 END) AS has_done", false)
            ->where('a.is_active', 1)
            ->where('a.is_published', 1)
            ->where('a.deleted_at', null)
            ->groupStart()
                ->where('(a.start_date IS NULL OR a.start_date <= ' . $this->db->escape($today) . ')', null, false)
            ->groupEnd()
            ->groupStart()
                ->where('(a.end_date IS NULL OR a.end_date >= ' . $this->db->escape($today) . ')', null, false)
            ->groupEnd()
            ->groupStart()
                ->where('a.target_audience', 'All')
                ->orGroupStart()
                    ->where('a.target_audience', 'Class')
                    ->where('a.target_class_id', $classId)
                ->groupEnd()
                ->orGroupStart()
                    ->where('a.target_audience', 'Grade')
                    ->groupStart()
                        ->where('a.target_grade', $gradeRoman)
                        ->orWhere('a.target_grade', $gradeNum)
                    ->groupEnd()
                ->groupEnd()
                ->orGroupStart()
                    ->where('a.target_audience', 'Individual')
                    ->where('ar_i.id IS NOT NULL', null, false)
                ->groupEnd()
            ->groupEnd()
            ->groupBy('a.id, a.title, a.assessment_type, a.start_date, a.end_date, a.duration_minutes, a.target_audience, a.target_class_id, a.target_grade, a.total_questions')
            ->orderBy('a.start_date', 'ASC')
            ->limit(5)
            ->get()
            ->getResult();

        // 6) Ringkas hasil asesmen terakhir (non-deleted)
        $recentResults = $this->db->table('assessment_results r')
            ->select('r.id, r.assessment_id, a.title, r.status, r.percentage, r.is_passed, r.completed_at')
            ->join('assessments a', 'a.id = r.assessment_id AND a.deleted_at IS NULL', 'left')
            ->where('r.student_id', (int) $this->studentId)
            ->where('r.deleted_at', null)
            ->orderBy('r.completed_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResult();

        return view('student/dashboard', [
            'title'            => 'Dashboard Siswa',
            'student'          => $student,
            'activeYear'       => $activeYear,
            'upcomingSessions' => $upcomingSessions,
            'assessments'      => $assessments,
            'recentResults'    => $recentResults,
        ]);
    }
}
