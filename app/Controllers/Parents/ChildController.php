<?php

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\MessageModel;
use App\Models\MessageParticipantModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;

class ChildController extends BaseController
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();

        helper(['url', 'form', 'text']);
    }

    /**
     * Ambil parent id yang login (lebih robust untuk proyek yang simpan di session 'id' atau 'user_id').
     */
    protected function currentParentId(): int
    {
        return (int) (session('user_id') ?? session('id') ?? 0);
    }

    /**
     * Helper: pastikan anak memang milik parent yang sedang login.
     *
     * @param int $studentId
     * @return array|null
     */
    protected function findChildForCurrentParent(int $studentId): ?array
    {
        $parentId = $this->currentParentId();

        if ($studentId <= 0 || $parentId <= 0) {
            return null;
        }

        // FIX: pastikan nama anak selalu tersedia via users.full_name
        $row = $this->db->table('students s')
            ->select('
                s.*,
                u.full_name AS full_name,
                u.email, u.phone, u.profile_photo,
                c.class_name,
                c.grade_level,
                c.major
            ')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', $studentId)
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Daftar semua anak milik user parent saat ini
     */
    public function index()
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // FIX: jangan orderBy students.full_name (bisa tidak ada), gunakan users.full_name
        $students = $this->db->table('students s')
            ->select('
                s.id, s.user_id, s.nisn, s.nik, s.class_id, s.status,
                u.full_name,
                c.class_name
            ')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('parent/child/index', [
            'title'    => 'Anak Saya',
            'students' => $students,
        ]);
    }

    /**
     * Profil anak + ringkasan (read-only untuk biodata resmi).
     */
    public function profile($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        $student = $this->db->table('students s')
            ->select('
                s.*,
                u.full_name AS full_name,
                u.email, u.phone, u.profile_photo, u.id AS user_id,
                c.class_name, c.grade_level, c.major
            ')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', (int) $studentId)
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->route('parent.children.index')->with('error', 'Data anak tidak ditemukan.');
        }

        // Semua anak milik parent untuk dropdown switch
        $siblings = $this->db->table('students s')
            ->select('s.id, u.full_name AS full_name, c.class_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('parent/child/profile', [
            'title'    => 'Profil Anak',
            'profile'  => $student,
            'siblings' => $siblings,
        ]);
    }

    /**
     * Field biodata anak yang BOLEH diubah Orang Tua. Lima field terkunci
     * (Kelas/class_id, Tingkat, Jurusan, NISN, NIK) TIDAK pernah ikut disimpan.
     */
    private array $editableChildFields = [
        'gender', 'birth_place', 'birth_date', 'religion', 'address',
        'special_needs', 'disability', 'hobi', 'ekskul_organisasi',
        'kip_pip_number', 'father_name', 'mother_name', 'guardian_name',
    ];

    /**
     * Form edit data anak (langsung, bukan pengajuan).
     * GET /parent/child/{id}/edit
     */
    public function edit($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        $student = $this->findChildForCurrentParent((int) $studentId);
        if (!$student) {
            return redirect()->route('parent.children.index')->with('error', 'Data anak tidak ditemukan.');
        }

        return view('parent/child/edit', [
            'title'   => 'Ubah Data Anak',
            'profile' => $student,
            'today'   => date('Y-m-d'),
        ]);
    }

    /**
     * Simpan perubahan data anak (langsung). Orang Tua boleh mengubah seluruh
     * biodata anaknya KECUALI lima field terkunci.
     * POST /parent/child/{id}/update
     */
    public function update($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        $student = $this->findChildForCurrentParent((int) $studentId);
        if (!$student) {
            return redirect()->route('parent.children.index')->with('error', 'Data anak tidak ditemukan.');
        }

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

        $studentData = [];
        foreach ($this->editableChildFields as $f) {
            $val = $this->request->getPost($f);
            $val = is_string($val) ? trim($val) : $val;
            $studentData[$f] = ($val === '' || $val === null) ? null : $val;
        }
        $studentData['updated_at'] = date('Y-m-d H:i:s');

        $childUserId = (int) ($student['user_id'] ?? 0);
        $fullName = trim((string) $this->request->getPost('full_name'));
        $phone    = trim((string) $this->request->getPost('phone'));

        $this->db->transBegin();
        try {
            $this->db->table('students')->where('id', (int) $studentId)->update($studentData);

            if ($childUserId > 0) {
                $this->db->table('users')->where('id', $childUserId)->update([
                    'full_name'  => $fullName,
                    'phone'      => $phone === '' ? null : $phone,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[PARENT CHILD UPDATE] ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan perubahan. Coba lagi.');
        }

        return redirect()->route('parent.children.profile', [(int) $studentId])
            ->with('success', 'Data anak berhasil diperbarui. (Kelas, Tingkat, Jurusan, NISN, dan NIK hanya dapat diubah oleh sekolah.)');
    }

    /**
     * Orang tua mengajukan perubahan data anak (pesan internal).
     */
    public function requestUpdate($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // FIX: ambil nama anak via users.full_name
        $student = $this->db->table('students s')
            ->select('s.id, s.user_id, s.class_id, s.parent_id, u.full_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->where('s.id', (int) $studentId)
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->back()->with('error', 'Tidak berhak mengajukan perubahan untuk data ini.');
        }

        $requestedFields = $this->request->getPost('requested_fields'); // array teks ringkas
        $notes           = $this->request->getPost('notes');

        if (!$requestedFields && !$notes) {
            return redirect()->back()->with('error', 'Mohon jelaskan perubahan yang diajukan.');
        }

        // Ringkas isi pesan (plain → disanitasi saat display)
        $body = "Permintaan perubahan data siswa #{$student['id']} - " . ($student['full_name'] ?? '-') . ":\n";
        if (is_array($requestedFields)) {
            foreach ($requestedFields as $rf) {
                $rf = trim((string) $rf);
                if ($rf !== '') {
                    $body .= "- {$rf}\n";
                }
            }
        }
        if ($notes) {
            $body .= "\nCatatan orang tua:\n" . trim((string) $notes);
        }

        $this->db->transBegin();
        try {
            $messageId = model(MessageModel::class)->insert([
                'subject'    => 'Permintaan Perubahan Data Siswa',
                'body'       => nl2br(esc($body)),
                'created_by' => $parentId,
                'is_draft'   => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ], true);

            $recipientIds = $this->resolveRecipients($student);

            // Kalau masih kosong (misal mapping belum lengkap), jangan silent fail
            if (empty($recipientIds)) {
                $this->db->transRollback();
                return redirect()->back()->with('error', 'Gagal menentukan penerima (Wali Kelas/Guru BK). Periksa data kelas anak.');
            }

            foreach (array_unique($recipientIds) as $uid) {
                model(MessageParticipantModel::class)->insert([
                    'message_id' => $messageId,
                    'user_id'    => (int) $uid,
                    'role'       => 'to',
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->transCommit();
            return redirect()->back()->with('success', 'Permintaan perubahan telah dikirim ke pihak sekolah.');
        } catch (DatabaseException $e) {
            $this->db->transRollback();
            log_message('error', '[PARENT REQUEST UPDATE] ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengirim permintaan. Coba lagi.');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[PARENT REQUEST UPDATE] ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan. Coba lagi.');
        }
    }

    /**
     * Tentukan penerima pesan permintaan update data:
     * - Prioritas: Wali Kelas + Guru BK dari kelas anak (classes.homeroom_teacher_id, classes.counselor_id)
     * - Fallback: Koordinator/Admin (berdasarkan nama role bila tabel roles tersedia)
     */
    private function resolveRecipients(array $student): array
    {
        $recipients = [];

        $classId = (int) ($student['class_id'] ?? 0);
        if ($classId > 0) {
            $class = $this->db->table('classes')
                ->select('id, homeroom_teacher_id, counselor_id')
                ->where('id', $classId)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if ($class) {
                if (!empty($class['homeroom_teacher_id'])) {
                    $recipients[] = (int) $class['homeroom_teacher_id'];
                }
                if (!empty($class['counselor_id'])) {
                    $recipients[] = (int) $class['counselor_id'];
                }
            }
        }

        // Fallback: cari Koordinator/Admin jika ada tabel roles + users.role_id
        if (empty($recipients)) {
            try {
                $roles = $this->db->table('roles')
                    ->select('id, name')
                    ->where('deleted_at', null)
                    ->get()
                    ->getResultArray();

                if ($roles) {
                    $roleIds = [];
                    foreach ($roles as $r) {
                        $name = strtolower((string) ($r['name'] ?? ''));
                        if (str_contains($name, 'koordinator') || str_contains($name, 'admin')) {
                            $roleIds[] = (int) $r['id'];
                        }
                    }

                    if (!empty($roleIds)) {
                        $rows = $this->db->table('users')
                            ->select('id')
                            ->whereIn('role_id', $roleIds)
                            ->where('deleted_at', null)
                            ->limit(10)
                            ->get()
                            ->getResultArray();

                        foreach ($rows as $u) {
                            $recipients[] = (int) ($u['id'] ?? 0);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // roles table mungkin tidak ada, tidak masalah
                log_message('warning', '[PARENT resolveRecipients] fallback roles not available: ' . $e->getMessage());
            }
        }

        // Bersihkan invalid
        $recipients = array_values(array_filter(array_unique($recipients), static fn($x) => (int)$x > 0));

        return $recipients;
    }

    /**
     * Orang tua dapat memperbarui EMAIL & PHONE anak (users.* milik anak)
     * POST /parent/child/{id}/contact
     */
    public function updateContact($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // Pastikan siswa milik parent
        $row = $this->db->table('students')
            ->select('id, user_id, parent_id')
            ->where('id', (int) $studentId)
            ->where('parent_id', $parentId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$row || empty($row['user_id'])) {
            return redirect()->back()->with('error', 'Tidak berhak memperbarui kontak anak.');
        }

        $childUserId = (int) $row['user_id'];
        $email       = strtolower(trim((string) $this->request->getPost('email')));
        $phone       = trim((string) $this->request->getPost('phone'));

        // Validasi manual (ringan)
        $errors = [];
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif ($email !== '') {
            // Cek unik email (kecuali milik anak sendiri)
            $dup = $this->db->table('users')->select('id')
                ->where('email', $email)
                ->where('id !=', $childUserId)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();
            if ($dup) {
                $errors['email'] = 'Email sudah dipakai pengguna lain.';
            }
        }

        if ($phone !== '') {
            if (!preg_match('~^[0-9+()\s-]{6,20}$~', $phone)) {
                $errors['phone'] = 'Nomor telepon tidak valid.';
            }
        }

        if (!empty($errors)) {
            return redirect()->back()
                ->with('error', 'Periksa kembali input Anda.')
                ->with('errors_contact', $errors)
                ->withInput();
        }

        // Update ke tabel users
        $this->db->table('users')->where('id', $childUserId)->update([
            'email'      => $email !== '' ? $email : null,
            'phone'      => $phone === '' ? null : $phone,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->route('parent.children.profile', [$studentId])
            ->with('success', 'Kontak anak berhasil diperbarui.');
    }

    /**
     * Orang tua dapat memperbarui FOTO PROFIL anak (users.profile_photo)
     * POST /parent/child/{id}/upload-photo
     */
    public function uploadPhoto($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // Pastikan siswa milik parent
        $row = $this->db->table('students')
            ->select('id, user_id, parent_id')
            ->where('id', (int) $studentId)
            ->where('parent_id', $parentId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$row || empty($row['user_id'])) {
            return redirect()->back()->with('error', 'Tidak berhak mengunggah foto untuk anak ini.');
        }

        $childUserId = (int) $row['user_id'];
        $file        = $this->request->getFile('profile_photo');

        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()->back()->with('error', 'Tidak ada file yang diunggah.');
        }

        // Validasi file
        $rules = [
            'profile_photo' => 'uploaded[profile_photo]'
                . '|is_image[profile_photo]'
                . '|mime_in[profile_photo,image/jpg,image/jpeg,image/png,image/webp]'
                . '|ext_in[profile_photo,jpg,jpeg,png,webp]'
                . '|max_size[profile_photo,2048]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', 'Periksa kembali file yang diunggah.')
                ->with('errors_photo', $this->validator->getErrors());
        }

        // Simpan ke folder khusus anak
        $targetDir = FCPATH . 'uploads/profile_photos/' . $childUserId;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $newName = 'avatar_' . $childUserId . '_' . time() . '.' . $file->getExtension();
        if (!$file->move($targetDir, $newName, true)) {
            return redirect()->back()->with('error', 'Gagal menyimpan file. Coba lagi.');
        }

        $relPath = 'uploads/profile_photos/' . $childUserId . '/' . $newName;
        $this->db->table('users')->where('id', $childUserId)->update([
            'profile_photo' => $relPath,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return redirect()->route('parent.children.profile', [$studentId])
            ->with('success', 'Foto profil anak berhasil diperbarui.');
    }

    /**
     * Info Guru BK & Wali Kelas untuk anak tertentu (Orang Tua).
     * Route: parent/child/{id}/staff
     */
    public function staff($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // Optional guard: kalau session punya role_id, pastikan Orang Tua (role_id=6)
        $roleId = (int) (session('role_id') ?? 0);
        if ($roleId > 0 && $roleId !== 6) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $studentId = (int) $studentId;

        // Pastikan anak milik parent login
        $student = $this->findChildForCurrentParent($studentId);
        if (!$student) {
            return redirect()->route('parent.dashboard')->with('error', 'Data anak tidak ditemukan.');
        }

        // Untuk dropdown ganti anak (opsional tapi enak dipakai)
        $siblings = $this->db->table('students s')
            ->select('s.id, u.full_name AS full_name, c.class_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        $classId = (int) ($student['class_id'] ?? 0);

        $class = null;
        $homeroom = null;
        $counselor = null;

        if ($classId > 0) {
            // Ambil data kelas + id wali kelas & guru BK
            $class = $this->db->table('classes c')
                ->select('c.id, c.class_name, c.grade_level, c.major, c.homeroom_teacher_id, c.counselor_id')
                ->where('c.id', $classId)
                ->where('c.deleted_at', null)
                ->get()
                ->getRowArray();

            $staffIds = [];
            if (!empty($class['homeroom_teacher_id'])) $staffIds[] = (int) $class['homeroom_teacher_id'];
            if (!empty($class['counselor_id']))        $staffIds[] = (int) $class['counselor_id'];
            $staffIds = array_values(array_unique(array_filter($staffIds)));

            if (!empty($staffIds)) {
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

                if (!empty($class['homeroom_teacher_id'])) {
                    $homeroom = $byId[(int) $class['homeroom_teacher_id']] ?? null;
                }
                if (!empty($class['counselor_id'])) {
                    $counselor = $byId[(int) $class['counselor_id']] ?? null;
                }
            }
        }

        return view('parent/child/staff', [
            'title'     => 'Info Guru BK & Wali Kelas',
            'student'   => $student,
            'class'     => $class,
            'homeroom'  => $homeroom,
            'counselor' => $counselor,
            'siblings'  => $siblings,
        ]);
    }
}
