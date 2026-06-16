<?php
// app/Controllers/Student/ProfileController.php
namespace App\Controllers\Student;

class ProfileController extends BaseStudentController
{
    /**
     * Field biodata siswa yang BOLEH diubah sendiri oleh Siswa.
     * Lima field terkunci (Kelas/class_id, Tingkat, Jurusan, NISN, NIK) TIDAK
     * pernah ikut disimpan walau dikirim.
     */
    private array $editableStudentFields = [
        'gender', 'birth_place', 'birth_date', 'religion', 'address',
        'special_needs', 'disability', 'hobi', 'ekskul_organisasi',
        'kip_pip_number', 'father_name', 'mother_name', 'guardian_name',
    ];

    /**
     * GET /student/profile
     */
    public function index()
    {
        $this->requireStudent();

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

        $mode = $this->request->getGet('mode') === 'edit' ? 'edit' : 'view';

        // Lima field terkunci yang tidak boleh diubah Siswa.
        $lockedFields = ['Kelas', 'Tingkat', 'Jurusan', 'NISN', 'NIK'];

        return view('student/profile', [
            'title'           => 'Profil Siswa',
            'profile'         => $profile,
            'mode'            => $mode,
            'today'           => date('Y-m-d'),
            'lockedFields'    => $lockedFields,
            'accountEditable' => ['email', 'phone', 'profile_photo'],
        ]);
    }

    /**
     * POST /student/profile/update
     * Siswa boleh mengubah seluruh biodata MILIKNYA, KECUALI lima field terkunci
     * (Kelas, Tingkat, Jurusan, NISN, NIK). Nama & telepon ikut bisa diubah.
     */
    public function update()
    {
        $this->requireStudent();

        $rules = [
            'full_name'   => 'required|max_length[255]',
            'phone'       => 'permit_empty|max_length[20]|regex_match[/^[0-9+()\s-]{6,20}$/]',
            'gender'      => 'permit_empty|in_list[L,P]',
            'birth_place' => 'permit_empty|max_length[100]',
            'birth_date'  => 'permit_empty|valid_date[Y-m-d]',
            'religion'    => 'permit_empty|in_list[Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]',
            'address'     => 'permit_empty|max_length[255]',
            'special_needs'     => 'permit_empty|max_length[100]',
            'disability'        => 'permit_empty|max_length[100]',
            'hobi'              => 'permit_empty|max_length[255]',
            'ekskul_organisasi' => 'permit_empty|max_length[255]',
            'kip_pip_number'    => 'permit_empty|max_length[50]',
            'father_name'       => 'permit_empty|max_length[255]',
            'mother_name'       => 'permit_empty|max_length[255]',
            'guardian_name'     => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', 'Periksa kembali data yang diisi.')
                ->with('errors', $this->validator->getErrors());
        }

        // Susun data biodata siswa (hanya field yang diizinkan; '' → null).
        $studentData = [];
        foreach ($this->editableStudentFields as $f) {
            $val = $this->request->getPost($f);
            $val = is_string($val) ? trim($val) : $val;
            $studentData[$f] = ($val === '' || $val === null) ? null : $val;
        }
        $studentData['updated_at'] = date('Y-m-d H:i:s');

        // Akun: nama & telepon.
        $userRow = $this->db->table('students')->select('user_id')
            ->where('id', $this->studentId)->get()->getRowArray();
        $userId = (int) ($userRow['user_id'] ?? 0);

        $fullName = trim((string) $this->request->getPost('full_name'));
        $phone    = trim((string) $this->request->getPost('phone'));

        $this->db->transStart();

        $this->db->table('students')->where('id', $this->studentId)->update($studentData);

        if ($userId > 0) {
            $this->db->table('users')->where('id', $userId)->update([
                'full_name'  => $fullName,
                'phone'      => $phone === '' ? null : $phone,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan perubahan. Coba lagi.');
        }

        // Segarkan nama di session bila ada.
        if ($fullName !== '') {
            session()->set('full_name', $fullName);
        }

        return redirect()->to(route_to('student.profile'))
            ->with('success', 'Biodata berhasil diperbarui. (Kelas, Tingkat, Jurusan, NISN, dan NIK hanya dapat diubah oleh sekolah.)');
    }
}
