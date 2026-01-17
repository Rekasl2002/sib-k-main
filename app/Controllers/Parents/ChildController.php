<?php

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\ViolationModel;
use App\Models\CounselingSessionModel;
use App\Models\MessageModel;
use App\Models\MessageParticipantModel;
use App\Models\ViolationCategoryModel;
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
                s.id, s.user_id, s.nisn, s.nis, s.class_id, s.status,
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
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } else {
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
            'email'      => $email,
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
     * Riwayat pelanggaran anak untuk akun Orang Tua.
     * URL: /parent/child/{studentId}/violations
     */
    public function violations($studentId)
    {
        $studentId = (int) $studentId;

        $student = $this->findChildForCurrentParent($studentId);
        if (!$student) {
            return view('errors/html/error_404');
        }

        $db = $this->db;

        // Deteksi nama kolom dinamis di tabel violations (amanin kalau DB error)
        $fieldNames = [];
        try {
            $violationFields = $db->getFieldData('violations');
            $fieldNames      = array_map(static fn($f) => $f->name, $violationFields);
        } catch (\Throwable $e) {
            log_message('warning', '[PARENT violations] getFieldData failed: ' . $e->getMessage());
        }

        // Kolom kategori: category_id (baru) atau violation_category_id (lama)
        $catCol = in_array('category_id', $fieldNames, true)
            ? 'category_id'
            : (in_array('violation_category_id', $fieldNames, true)
                ? 'violation_category_id'
                : null);

        // Kolom pelapor: reported_by (baru) / recorder_id / created_by (fallback)
        $recCol = in_array('reported_by', $fieldNames, true)
            ? 'reported_by'
            : (in_array('recorder_id', $fieldNames, true)
                ? 'recorder_id'
                : (in_array('created_by', $fieldNames, true) ? 'created_by' : null));

        $builder = $db->table('violations v');

        // Kolom dasar
        $select = [
            'v.id',
            'COALESCE(v.violation_date, DATE(v.created_at)) AS recorded_at',
            'v.violation_date',
            'v.violation_time',
            'v.description',
        ];

        // Kolom kategori & poin
        if ($catCol) {
            $select[] = 'COALESCE(vc.category_name, "-") AS violation_type';
            $select[] = 'COALESCE(vc.point_deduction, 0) AS points';
        } else {
            $select[] = "'-' AS violation_type";
            $select[] = '0   AS points';
        }

        // Kolom nama guru/pelapor
        if ($recCol) {
            $select[] = 'u.full_name AS recorder';
        } else {
            $select[] = "'-' AS recorder";
        }

        // Ringkasan semua sanksi
        $select[] = "GROUP_CONCAT(DISTINCT CONCAT(s.sanction_type, ' (', s.status, ')') SEPARATOR '; ') AS sanctions_summary";

        $builder->select(implode(', ', $select));

        if ($catCol) {
            $builder->join('violation_categories vc', "vc.id = v.$catCol", 'left');
        }

        if ($recCol) {
            $builder->join('users u', "u.id = v.$recCol", 'left');
        }

        $builder->join('sanctions s', 's.violation_id = v.id AND s.deleted_at IS NULL', 'left');

        $builder
            ->where('v.student_id', (int) $student['id'])
            ->where('v.deleted_at', null)
            ->groupBy('v.id')
            ->orderBy('v.violation_date', 'DESC')
            ->orderBy('v.created_at', 'DESC');

        $rows = $builder->get()->getResultArray();

        // Hitung total poin pelanggaran
        $totalPoints = 0;
        foreach ($rows as $row) {
            $totalPoints += (int) ($row['points'] ?? 0);
        }

        $data = [
            'title'        => 'Kasus & Pelanggaran',
            'student'      => $student,
            'violations'   => $rows,
            'total_points' => $totalPoints,
        ];

        return view('parent/violations/index', $data);
    }

    /**
     * Detail satu pelanggaran anak.
     * URL: /parent/child/{studentId}/violations/{violationId}
     */
    public function violationDetail($studentId, $violationId)
    {
        $studentId   = (int) $studentId;
        $violationId = (int) $violationId;

        if ($violationId <= 0) {
            return redirect()
                ->route('parent.children.violations', [$studentId])
                ->with('error', 'Data pelanggaran tidak ditemukan.');
        }

        $student = $this->findChildForCurrentParent($studentId);
        if (!$student) {
            return redirect()
                ->route('parent.children.index')
                ->with('error', 'Data anak tidak ditemukan.');
        }

        $db = $this->db;

        // FIX: nama siswa dari users
        $violation = $db->table('violations v')
            ->select([
                'v.id',
                'v.student_id',
                'v.violation_date',
                'v.violation_time',
                'v.location',
                'v.description',
                'v.witness',
                'v.evidence',
                'v.status',
                'v.resolution_notes',
                'v.resolution_date',
                'v.parent_notified',
                'v.parent_notified_at',

                'vc.category_name',
                'vc.severity_level',
                'vc.point_deduction',
                'vc.description AS category_description',
                'vc.examples',

                // data siswa
                'su.full_name AS student_full_name',
                's.nis       AS student_nis',
                's.nisn      AS student_nisn',
                'c.class_name AS class_name',

                // petugas
                'reporter.full_name AS reporter_name',
                'handler.full_name  AS handler_name',
            ])
            ->join('students s', 's.id = v.student_id AND s.deleted_at IS NULL')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->join('users reporter', 'reporter.id = v.reported_by', 'left')
            ->join('users handler', 'handler.id = v.handled_by', 'left')
            ->where('v.id', $violationId)
            ->where('v.student_id', $student['id'])
            ->where('v.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$violation) {
            return redirect()
                ->route('parent.children.violations', [$studentId])
                ->with('error', 'Data pelanggaran tidak ditemukan atau tidak dapat diakses.');
        }

        // Daftar sanksi
        $sanctions = $db->table('sanctions s')
            ->select([
                's.id',
                's.violation_id',
                's.sanction_type',
                's.sanction_date',
                's.start_date',
                's.end_date',
                's.duration_days',
                's.description AS sanction_description',
                's.status',
                's.completed_date',
                's.completion_notes AS sanction_notes',
                's.parent_acknowledged',
                's.parent_acknowledged_at',
                'assigner.full_name AS assigned_by_name',
                'verifier.full_name AS verified_by_name',
            ])
            ->join('users assigner', 'assigner.id = s.assigned_by', 'left')
            ->join('users verifier', 'verifier.id = s.verified_by', 'left')
            ->where('s.violation_id', $violation['id'])
            ->where('s.deleted_at', null)
            ->orderBy('s.sanction_date', 'ASC')
            ->orderBy('s.created_at', 'ASC')
            ->get()
            ->getResultArray();

        $data = [
            'title'     => 'Detail Pelanggaran Anak',
            'violation' => $violation,
            'sanctions' => $sanctions,
            'student'   => [
                'id'         => $student['id'],
                'full_name'  => $violation['student_full_name'] ?? ($student['full_name'] ?? ''),
                'nis'        => $violation['student_nis'] ?? ($student['nis'] ?? ''),
                'nisn'       => $violation['student_nisn'] ?? ($student['nisn'] ?? ''),
                'class_name' => $violation['class_name'] ?? ($student['class_name'] ?? ''),
            ],
        ];

        return view('parent/violations/detail', $data);
    }

    /**
     * Orang tua mengonfirmasi bahwa ia sudah mengetahui sanksi pada sebuah pelanggaran.
     *
     * URL (POST): /parent/child/{studentId}/violations/{violationId}/ack
     * Route name: parent.children.violations.ack
     */
    public function acknowledgeSanctions($studentId, $violationId)
    {
        $studentId   = (int) $studentId;
        $violationId = (int) $violationId;

        $parentId = $this->currentParentId();
        if (!$parentId) {
            return redirect()->to('/login');
        }

        $student = $this->findChildForCurrentParent($studentId);
        if (!$student) {
            return redirect()
                ->route('parent.children.index')
                ->with('error', 'Data anak tidak ditemukan.');
        }

        // Pastikan pelanggaran milik siswa tersebut
        $violation = $this->db->table('violations v')
            ->select('v.id, v.student_id, v.category_id, v.reported_by, v.handled_by, vc.category_name')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.id', $violationId)
            ->where('v.student_id', (int) $student['id'])
            ->where('v.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$violation) {
            return redirect()
                ->route('parent.children.violations', [$studentId])
                ->with('error', 'Data pelanggaran tidak ditemukan atau tidak dapat diakses.');
        }

        // Ambil semua sanksi pada pelanggaran ini
        $sanctions = $this->db->table('sanctions s')
            ->select('s.id, s.violation_id, s.sanction_type, s.assigned_by, s.parent_acknowledged')
            ->where('s.violation_id', $violationId)
            ->where('s.deleted_at', null)
            ->get()
            ->getResultArray();

        if (empty($sanctions)) {
            return redirect()->back()->with('error', 'Belum ada sanksi yang bisa dikonfirmasi pada kasus ini.');
        }

        $pendingIds  = [];
        $assignedBys = [];
        foreach ($sanctions as $s) {
            $sid = (int) ($s['id'] ?? 0);
            if ($sid <= 0) continue;

            if ((int) ($s['parent_acknowledged'] ?? 0) !== 1) {
                $pendingIds[] = $sid;
            }

            $ab = (int) ($s['assigned_by'] ?? 0);
            if ($ab > 0) $assignedBys[] = $ab;
        }

        if (empty($pendingIds)) {
            return redirect()->back()->with('success', 'Konfirmasi orang tua sudah tercatat sebelumnya.');
        }

        $now = date('Y-m-d H:i:s');

        // Update idempotent: hanya yang belum acknowledged
        $this->db->transBegin();
        try {
            $this->db->table('sanctions')
                ->where('violation_id', $violationId)
                ->where('deleted_at', null)
                ->whereIn('id', $pendingIds)
                ->update([
                    'parent_acknowledged'    => 1,
                    'parent_acknowledged_at' => $now,
                    'updated_at'             => $now,
                ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed');
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Parents\\ChildController::acknowledgeSanctions - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan konfirmasi. Silakan coba lagi.');
        }

        // ======================
        // NOTIFIKASI ke Guru BK / Koordinator BK
        // ======================
        // Coba muat helper notifikasi (opsional)
        if (function_exists('helper')) {
            try {
                helper('notification');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $parentRow = $this->db->table('users')
            ->select('id, full_name')
            ->where('id', $parentId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $parentName  = $parentRow['full_name'] ?? 'Orang tua';
        $studentName = $student['full_name'] ?? ($student['name'] ?? 'Siswa');
        $className   = $student['class_name'] ?? '';
        $caseName    = $violation['category_name'] ?? 'Pelanggaran';

        $title   = 'Konfirmasi Orang Tua';
        $message = $parentName . ' telah mengonfirmasi mengetahui sanksi untuk ' . $studentName
            . ($className ? ' (Kelas ' . $className . ')' : '')
            . ' terkait pelanggaran: ' . $caseName . '.';

        $data = [
            'violation_id' => (int) $violationId,
            'student_id'   => (int) ($student['id'] ?? 0),
            'parent_id'    => (int) $parentId,
            'sanction_ids' => array_values(array_unique($pendingIds)),
            'ack_at'       => $now,
        ];

        // target: semua Koordinator BK + Guru BK yang relevan (assigned_by / handled_by)
        $recipientIds = [];

        foreach ($assignedBys as $ab) {
            if ($ab > 0) $recipientIds[] = (int) $ab;
        }

        $handledBy = (int) ($violation['handled_by'] ?? 0);
        if ($handledBy > 0) $recipientIds[] = $handledBy;

        // semua Koordinator BK (role_id=2)
        $koords = $this->db->table('users')
            ->select('id, role_id')
            ->where('role_id', 2)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        foreach ($koords as $k) {
            $rid = (int) ($k['id'] ?? 0);
            if ($rid > 0) $recipientIds[] = $rid;
        }

        $recipientIds = array_values(array_unique(array_filter($recipientIds, static fn($v) => (int) $v > 0)));

        if (!empty($recipientIds)) {
            // Ambil role penerima agar link sesuai modul (counselor / koordinator)
            $recipientRows = $this->db->table('users')
                ->select('id, role_id')
                ->whereIn('id', $recipientIds)
                ->where('deleted_at', null)
                ->get()
                ->getResultArray();

            foreach ($recipientRows as $u) {
                $uid  = (int) ($u['id'] ?? 0);
                $role = (int) ($u['role_id'] ?? 0);
                if ($uid <= 0) continue;

                $link = null;
                if ($role === 2) {
                    $link = base_url('koordinator/cases/detail/' . $violationId);
                } elseif ($role === 3) {
                    $link = base_url('counselor/cases/detail/' . $violationId);
                }

                if (function_exists('send_notification')) {
                    @send_notification($uid, $title, $message, 'info', $data, $link);
                } else {
                    // fallback langsung insert
                    try {
                        $this->db->table('notifications')->insert([
                            'user_id'    => $uid,
                            'title'      => $title,
                            'message'    => $message,
                            'type'       => 'info',
                            'link'       => $link,
                            'data'       => json_encode($data, JSON_UNESCAPED_UNICODE),
                            'is_read'    => 0,
                            'created_at' => $now,
                        ]);
                    } catch (\Throwable $e) {
                        log_message('error', 'Failed to insert notification: ' . $e->getMessage());
                    }
                }
            }
        }

        return redirect()->back()->with('success', 'Status orang tua mengetahui telah dicatat.');
    }

    /**
     * Daftar kategori pelanggaran untuk referensi Orang Tua.
     * URL: /parent/child/{studentId}/violations/categories
     */
    public function violationCategories($studentId)
    {
        $studentId = (int) $studentId;
        $student   = $this->findChildForCurrentParent($studentId);

        if (!$student) {
            return view('errors/html/error_404');
        }

        $categoryModel = new ViolationCategoryModel();
        $categories    = $categoryModel->getActiveCategories();

        $data = [
            'title'      => 'Kategori Pelanggaran',
            'categories' => $categories,
            'student'    => $student,
        ];

        return view('parent/violations/categories', $data);
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
                    s.nisn, s.nis,
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
