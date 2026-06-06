<?php

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\CounselingSessionModel;
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
     * Daftar sesi konseling (ringkasan yang boleh dilihat orang tua)
     *
     * Mendukung:
     * 1) Individu:   cs.student_id = anak.id
     * 2) Kelompok:   ada sp.student_id = anak.id
     * 3) Klasikal:   cs.session_type='Klasikal' AND cs.class_id = anak.class_id
     *
     * Default selaras student/schedule/index:
     * - upcoming (>= hari ini), status != 'Dibatalkan'
     */
    public function sessions($studentId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // FIX: ambil nama anak via users.full_name
        $student = $this->db->table('students s')
            ->select('s.id, s.class_id, u.full_name AS full_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->where('s.id', (int) $studentId)
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->route('parent.children.index')
                ->with('error', 'Data anak tidak ditemukan.');
        }

        // Ambil filter dari querystring
        $status  = $this->request->getGet('status'); // Dijadwalkan|Selesai|Dibatalkan|null
        $range   = $this->request->getGet('range') ?: 'upcoming'; // upcoming default
        $q       = trim((string) $this->request->getGet('q'));
        $perPage = (int) ($this->request->getGet('perPage') ?? 10);
        $perPage = max(5, min($perPage, 50));
        $today   = date('Y-m-d');

        $b = $this->db->table('counseling_sessions cs')
            ->select("
                cs.id,
                cs.session_date,
                cs.session_time,
                cs.location,
                cs.topic,
                cs.problem_description,
                cs.status,
                cs.is_confidential,
                cs.session_type,
                u.full_name AS counselor_name
            ")
            ->join('users u', 'u.id = cs.counselor_id', 'left')
            ->join('session_participants sp', 'sp.session_id = cs.id AND sp.deleted_at IS NULL', 'left')
            ->where('cs.deleted_at', null)
            ->groupStart()
                ->where('cs.student_id', (int) $student['id'])
                ->orWhere('sp.student_id', (int) $student['id']);

        if (!empty($student['class_id'])) {
            $b->orGroupStart()
                ->where('cs.session_type', 'Klasikal')
                ->where('cs.class_id', (int) $student['class_id'])
              ->groupEnd();
        }

        $b->groupEnd();

        // Filter status spesifik
        if ($status && in_array($status, ['Dijadwalkan', 'Selesai', 'Dibatalkan', 'Tidak Hadir'], true)) {
            $b->where('cs.status', $status);
        }

        // Range waktu
        if ($range === 'past') {
            $b->groupStart()
                ->where('DATE(cs.session_date) <', $today)
                ->orWhereIn('cs.status', ['Selesai', 'Dibatalkan', 'Tidak Hadir'])
            ->groupEnd();
        } elseif ($range === 'all') {
            // no-op
        } else {
            $b->where('DATE(cs.session_date) >=', $today)
              ->where('cs.status !=', 'Dibatalkan');
        }

        // Pencarian sederhana
        if ($q !== '') {
            $b->groupStart()
                ->like('cs.topic', $q)
                ->orLike('cs.problem_description', $q)
                ->orLike('cs.location', $q)
            ->groupEnd();
        }

        // Urutan: upcoming ASC, past DESC
        if ($range === 'past') {
            $b->orderBy('cs.session_date', 'DESC')->orderBy('cs.session_time', 'DESC');
        } else {
            $b->orderBy('cs.session_date', 'ASC')->orderBy('cs.session_time', 'ASC');
        }

        $rows = $b->distinct()->limit($perPage)->get()->getResultArray();

        // Sensor basic di daftar bila confidential dan bukan status "Dijadwalkan"
        foreach ($rows as &$r) {
            if ((int) ($r['is_confidential'] ?? 0) === 1 && ($r['status'] ?? '') !== 'Dijadwalkan') {
                $r['topic']    = 'Sesi Konseling (Terbatas)';
                $r['location'] = null;
                unset($r['problem_description']);
            }
        }
        unset($r);

        if ($range === 'past') {
            return view('parent/child/sessions_history', [
                'title'   => 'Riwayat Sesi Konseling',
                'student' => [
                    'id'        => $student['id'],
                    'full_name' => $student['full_name'] ?? '-',
                ],
                'history' => $rows,
            ]);
        }

        return view('parent/child/sessions', [
            'title'   => 'Sesi Konseling',
            'student' => [
                'id'        => $student['id'],
                'full_name' => $student['full_name'] ?? '-',
            ],
            'filters'  => [
                'status'  => $status,
                'range'   => $range,
                'q'       => $q,
                'perPage' => $perPage,
            ],
            'sessions' => $rows,
        ]);
    }

    /**
     * Detail sesi konseling untuk Orang Tua — diselaraskan dengan Student\ScheduleController::detail
     * URL: /parent/child/{studentId}/sessions/{sessionId}
     */
    public function sessionDetail(int $studentId, int $sessionId)
    {
        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // Pastikan anak memang terhubung ke parent + ambil nama via users
        $student = $this->db->table('students s')
            ->select('s.id, s.class_id, u.full_name AS full_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->where('s.id', $studentId)
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->route('parent.children.index')->with('error', 'Data anak tidak ditemukan.');
        }

        // Ambil detail sesi
        $session = $this->db->table('counseling_sessions cs')
            ->select('
                cs.*,
                u.full_name AS counselor_name,
                u.email     AS counselor_email,
                c.class_name
            ')
            ->join('users u', 'u.id = cs.counselor_id', 'left')
            ->join('classes c', 'c.id = cs.class_id', 'left')
            ->where('cs.id', $sessionId)
            ->where('cs.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$session) {
            return redirect()->route('parent.children.sessions', [$studentId])
                ->with('error', 'Sesi konseling tidak ditemukan.');
        }

        // Jika sesi rahasia, TIDAK boleh diakses
        if (!empty($session['is_confidential'])) {
            return redirect()->route('parent.children.sessions', [$studentId])
                ->with('error', 'Sesi konseling ini bersifat rahasia dan tidak dapat diakses.');
        }

        // Cek hak akses anak terhadap sesi ini
        $allowed = false;
        $type    = (string) ($session['session_type'] ?? '');
        $classId = (int) ($student['class_id'] ?? 0);

        if ($type === 'Individu') {
            $allowed = ((int) ($session['student_id'] ?? 0) === (int) $student['id']);
        } elseif ($type === 'Klasikal') {
            $allowed = ($classId > 0 && (int) ($session['class_id'] ?? 0) === $classId);
        } elseif ($type === 'Kelompok') {
            $count = $this->db->table('session_participants')
                ->where('session_id', $sessionId)
                ->where('student_id', $student['id'])
                ->where('deleted_at', null)
                ->countAllResults();
            $allowed = ($count > 0);
        }

        if (!$allowed) {
            return redirect()->route('parent.children.sessions', [$studentId])
                ->with('error', 'Anda tidak memiliki akses ke sesi konseling ini.');
        }

        // Daftar peserta (opsional) – untuk konsistensi data
        $participants = [];
        if (in_array($type, ['Kelompok', 'Klasikal'], true)) {
            $participants = $this->db->table('session_participants sp')
                ->select('
                    sp.student_id,
                    sp.attendance_status,
                    sp.participation_note,
                    s.nisn, s.nik,
                    u.full_name AS student_name,
                    c.class_name
                ')
                ->join('students s', 's.id = sp.student_id')
                ->join('users u', 'u.id = s.user_id', 'left')
                ->join('classes c', 'c.id = s.class_id', 'left')
                ->where('sp.session_id', $sessionId)
                ->where('sp.deleted_at', null)
                ->orderBy('u.full_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        // Catatan partisipasi anak (Kelompok/Klasikal)
        $participationNote = null;
        if (in_array($type, ['Kelompok', 'Klasikal'], true)) {
            $ownRow = $this->db->table('session_participants')
                ->select('participation_note')
                ->where('session_id', $sessionId)
                ->where('student_id', $student['id'])
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (!empty($ownRow['participation_note'])) {
                $participationNote = $ownRow['participation_note'];
            }
        }

        // Catatan sesi yang boleh dilihat orang tua: is_confidential = 0
        $notes = $this->db->table('session_notes sn')
            ->select('
                sn.id,
                sn.session_id,
                sn.note_type,
                sn.note_content,
                sn.is_important,
                sn.attachments,
                sn.created_at,
                u2.full_name AS counselor_name
            ')
            ->join('users u2', 'u2.id = sn.created_by', 'left')
            ->where('sn.session_id', $sessionId)
            ->where('sn.is_confidential', 0)
            ->where('sn.deleted_at', null)
            ->orderBy('sn.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $canSeeNotes = true;

        return view('parent/child/session_detail', [
            'title'             => 'Detail Sesi Konseling',
            'studentId'         => $studentId, 
            'student'           => [
                'id'        => $student['id'],
                'full_name' => $student['full_name'] ?? '-',
            ],
            'session'           => $session,
            'participants'      => $participants,
            'sessionNotes'      => $notes,
            'participationNote' => $participationNote,
            'canSeeNotes'       => $participationNote,

        ]);
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
