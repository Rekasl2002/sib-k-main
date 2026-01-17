<?php
// app/Controllers/Student/StaffController.php

namespace App\Controllers\Student;

class StaffController extends BaseStudentController
{
    public function index()
    {
        $this->requireStudent();

        // Ambil profil siswa + kelas (alias "id" agar view staff.php tetap kompatibel)
        $student = $this->db->table('students s')
            ->select('
                s.id AS id,
                s.class_id,
                s.nis,
                s.nisn,
                u.full_name AS full_name,
                u.profile_photo AS profile_photo,
                c.class_name,
                c.grade_level,
                c.major
            ')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.id', (int) $this->studentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $student) {
            return redirect()->to('/student/dashboard')
                ->with('error', 'Profil siswa tidak ditemukan.');
        }

        $classId = (int) ($student['class_id'] ?? 0);

        $class     = null;
        $homeroom  = null;
        $counselor = null;

        if ($classId > 0) {
            $class = $this->db->table('classes c')
                ->select('c.id, c.class_name, c.grade_level, c.major, c.homeroom_teacher_id, c.counselor_id')
                ->where('c.id', $classId)
                ->where('c.deleted_at', null)
                ->get()
                ->getRowArray();

            $staffIds = [];
            if (! empty($class['homeroom_teacher_id'])) $staffIds[] = (int) $class['homeroom_teacher_id'];
            if (! empty($class['counselor_id']))        $staffIds[] = (int) $class['counselor_id'];
            $staffIds = array_values(array_unique(array_filter($staffIds)));

            if (! empty($staffIds)) {
                $rows = $this->db->table('users u')
                    ->select('u.id, u.full_name, u.email, u.phone, u.profile_photo, u.is_active, u.role_id')
                    ->whereIn('u.id', $staffIds)
                    ->where('u.deleted_at', null)
                    ->get()
                    ->getResultArray();

                $byId = [];
                foreach ($rows as $r) {
                    $byId[(int) ($r['id'] ?? 0)] = $r;
                }

                if (! empty($class['homeroom_teacher_id'])) {
                    $homeroom = $byId[(int) $class['homeroom_teacher_id']] ?? null;
                }
                if (! empty($class['counselor_id'])) {
                    $counselor = $byId[(int) $class['counselor_id']] ?? null;
                }
            }
        }

        // ✅ Reuse view yang sama (parent/child/staff.php) dengan parameter pembeda
        return view('student/staff', [
            'title'            => 'Info Guru BK & Wali Kelas',
            'student'          => $student,
            'class'            => $class,
            'homeroom'         => $homeroom,
            'counselor'        => $counselor,
            'siblings'         => [],

            // penyesuaian untuk akun siswa
            'dashboardRouteName' => 'student.dashboard',
            'staffRouteName'     => 'student.staff', // tidak dipakai (karena siblings kosong), tapi aman
            'showSwitcher'       => false,
            'waMode'             => 'student',
        ]);
    }
}
