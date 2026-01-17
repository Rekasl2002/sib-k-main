<?php
// app/Controllers/Student/ProfileController.php
namespace App\Controllers\Student;

class ProfileController extends BaseStudentController
{
    /**
     * GET /student/profile
     * Tampilkan profil siswa (mode view/edit digunakan oleh view untuk UX),
     * namun biodata resmi tetap read-only sesuai perancangan.
     * Perubahan Email/Telepon/Foto dilakukan di /profile (Profil Global).
     */
    public function index()
    {
        $this->requireStudent();

        // Ambil profil siswa + info akun + kelas
        $profile = $this->db->table('students s')
            ->select('
                s.*,
                u.email,
                u.full_name AS user_full_name,
                u.phone,
                u.profile_photo,
                c.class_name,
                c.grade_level,
                c.major
            ')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', $this->studentId)
            ->get()
            ->getRow();

        // Mode tampilan untuk kebutuhan UI (view/edit)
        $mode = $this->request->getGet('mode') === 'edit' ? 'edit' : 'view';

        // Policy sederhana untuk memberi hint ke view bahwa akun boleh ubah 3 field via /profile
        $accountEditable = ['email', 'phone', 'profile_photo'];

        return view('student/profile', [
            'title'            => 'Profil Siswa',
            'profile'          => $profile,
            'mode'             => $mode,
            'today'            => date('Y-m-d'),
            'accountEditable'  => $accountEditable, // dipakai view untuk tombol/link ke /profile
        ]);
    }

    /**
     * POST /student/profile/update
     * Sesuai kebijakan: tidak mengubah biodata resmi (tabel students).
     * Arahkan siswa ke Profil Global untuk mengubah Email/Telepon/Foto.
     */
    public function update()
    {
        $this->requireStudent();

        return redirect()
            ->to(route_to('profile') . '?mode=edit')
            ->with('info', 'Perubahan Email/Telepon/Foto lakukan di Profil Akun. Biodata resmi siswa tidak dapat diubah langsung dari sini.');
    }
}
