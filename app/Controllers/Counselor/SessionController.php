<?php

/**
 * File Path: app/Controllers/Counselor/SessionController.php
 *
 * Session Controller
 * Controller untuk mengelola CRUD counseling sessions
 *
 * @package    SIB-K
 * @subpackage Controllers/Counselor
 * @category   Counseling
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Models\CounselingSessionModel;
use App\Models\SessionNoteModel;
use App\Models\SessionParticipantModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Services\CounselingService;
use App\Validation\SessionValidation;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class SessionController extends BaseController
{
    /** @var CounselingSessionModel */
    protected $sessionModel;

    /** @var SessionNoteModel */
    protected $noteModel;

    /** @var SessionParticipantModel */
    protected $participantModel;

    /** @var StudentModel */
    protected $studentModel;

    /** @var ClassModel */
    protected $classModel;

    /** @var CounselingService */
    protected $counselingService;

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->sessionModel      = new CounselingSessionModel();
        $this->noteModel         = new SessionNoteModel();
        $this->participantModel  = new SessionParticipantModel();
        $this->studentModel      = new StudentModel();
        $this->classModel        = new ClassModel();
        $this->counselingService = new CounselingService();
        $this->db                = \Config\Database::connect();
    }

    // =========================================================================
    // INDEX (LIST)
    // =========================================================================

    /**
     * Display list of counseling sessions
     *
     * @return string|RedirectResponse|ResponseInterface
     */
    public function index()
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $counselorId = auth_id();

        $filters = [
            'status'       => $this->request->getGet('status'),
            'session_type' => $this->request->getGet('session_type'),
            'start_date'   => $this->request->getGet('start_date'),
            'end_date'     => $this->request->getGet('end_date'),
            'student_id'   => $this->request->getGet('student_id'),
            'search'       => $this->request->getGet('search'),
        ];

        $sessions = $this->counselingService->getSessionsByCounselor($counselorId, $filters);
        if (is_object($sessions)) {
            $sessions = (array) $sessions;
        }
        if (is_array($sessions)) {
            $sessions = array_map(static function ($row) {
                return is_object($row) ? (array) $row : $row;
            }, $sessions);
        } else {
            $sessions = [];
        }

        $data = [
            'sessions'    => $sessions,
            'students'    => $this->getActiveStudents(),
            'classes'     => $this->getActiveClasses(),
            'filters'     => $filters,
            'title'       => 'Daftar Sesi Konseling',
            'pageTitle'   => 'Sesi Konseling',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Sesi Konseling', 'url' => '#', 'active' => true],
            ],
        ];

        return view('counselor/sessions/index', $data);
    }

    // =========================================================================
    // CREATE (FORM)
    // =========================================================================

    /**
     * Show create session form
     *
     * @return string|RedirectResponse|ResponseInterface
     */
    public function create()
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $data = [
            'students'    => $this->getActiveStudents(),
            'classes'     => $this->getActiveClasses(),
            'title'       => 'Tambah Sesi Konseling',
            'pageTitle'   => 'Tambah Sesi Konseling',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Sesi Konseling', 'url' => base_url('counselor/sessions')],
                ['title' => 'Tambah', 'url' => '#', 'active' => true],
            ],
        ];

        return view('counselor/sessions/create', $data);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /**
     * Store new counseling session
     *
     * @return RedirectResponse
     */
    public function store()
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        // --- Normalisasi input dulu (SEBELUM validasi) ---
        $post = $this->request->getPost();

        // Normalisasi participants -> array<int>
        $rawParticipants = $this->request->getPost('participants');
        if ($rawParticipants === null) {
            $post['participants'] = [];
        } else {
            if (is_string($rawParticipants)) {
                $rawParticipants = preg_split('/[,\s]+/', trim($rawParticipants)) ?: [];
            }
            $post['participants'] = array_values(array_unique(
                array_filter(array_map('intval', (array) $rawParticipants))
            ));
        }

        // Normalisasi waktu & durasi
        $post['session_time'] = $this->normalizeTime($post['session_time'] ?? null);
        $post['duration_minutes'] = ($post['duration_minutes'] ?? '') !== ''
            ? max(0, (int) $post['duration_minutes'])
            : null;

        // Checkbox kerahasiaan: default 0 jika tidak dikirim
        $post['is_confidential'] = isset($post['is_confidential']) ? (int) $post['is_confidential'] : 0;

        // counselor pemilik sesi
        $post['counselor_id'] = auth_id();

        // Pastikan validator membaca data yang sudah dinormalisasi
        $this->request->setGlobal('post', $post);

        // Validasi rules umum
        $rules = SessionValidation::createRules();
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Validasi khusus bergantung tipe sesi
        $customValidation = SessionValidation::validateSessionType($post);
        if ($customValidation !== true) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $customValidation);
        }

        // Transaksi
        $this->db->transStart();
        try {
            $now       = date('Y-m-d H:i:s');

            $sessionId = $this->sessionModel->insert([
                'counselor_id'        => (int) $post['counselor_id'],
                'student_id'          => !empty($post['student_id']) ? (int) $post['student_id'] : null,
                'class_id'            => !empty($post['class_id']) ? (int) $post['class_id'] : null,
                'session_type'        => $post['session_type'],
                'session_date'        => $post['session_date'],
                'session_time'        => $post['session_time'] ?? null,
                'location'            => $post['location'] ?? null,
                'topic'               => $post['topic'],
                'problem_description' => $post['problem_description'] ?? null,
                'session_summary'     => $post['session_summary'] ?? null,
                'follow_up_plan'      => $post['follow_up_plan'] ?? null,
                'is_confidential'     => $post['is_confidential'],
                'duration_minutes'    => $post['duration_minutes'],
                'status'              => 'Dijadwalkan',
                'created_at'          => $now,
                'updated_at'          => $now,
            ], true);

            if (!$sessionId) {
                log_message('error', 'Session insert failed. Model errors: {errors} DB error: {dberr}', [
                    'errors' => json_encode($this->sessionModel->errors()),
                    'dberr'  => json_encode($this->sessionModel->db->error()),
                ]);
                throw new \RuntimeException('Gagal menyimpan sesi konseling');
            }

            // --- Insert peserta sesuai tipe ---
            $builder = $this->db->table('session_participants');

            if ($post['session_type'] === 'Kelompok') {
                $newIds = $post['participants']; // sudah dinormalisasi
                if (!empty($newIds)) {
                    $rows = [];
                    foreach ($newIds as $sid) {
                        $rows[] = [
                            'session_id'        => (int) $sessionId,
                            'student_id'        => (int) $sid,
                            'attendance_status' => 'Hadir',
                            'joined_at'         => $now,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ];
                    }
                    if (!empty($rows)) {
                        $builder->insertBatch($rows);
                    }
                }
            } elseif ($post['session_type'] === 'Klasikal' && !empty($post['class_id'])) {
                $classStudents = $this->studentModel
                    ->asArray()
                    ->where('class_id', (int) $post['class_id'])
                    ->where('status', 'Aktif')
                    ->findAll();

                if (!empty($classStudents)) {
                    $rows = [];
                    foreach ($classStudents as $stuRow) {
                        $rows[] = [
                            'session_id'        => (int) $sessionId,
                            'student_id'        => (int) ($stuRow['id'] ?? 0),
                            'attendance_status' => 'Hadir',
                            'joined_at'         => $now,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ];
                    }
                    if (!empty($rows)) {
                        $builder->insertBatch($rows);
                    }
                }
            }
            // Individu: tidak perlu insert ke session_participants (student_id disimpan di tabel sessions)

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal');
            }

            return redirect()->to('counselor/sessions')
                ->with('success', 'Sesi konseling berhasil ditambahkan');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error creating session: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // SHOW (DETAIL)
    // =========================================================================

    /**
     * Show session detail with notes/participants
     *
     * @param int $id
     * @return string|RedirectResponse|ResponseInterface
     */
    public function show($id)
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        // Detail sesi
        $sessionRow = null;
        if (method_exists($this->sessionModel, 'getSessionWithDetails')) {
            $tmp = $this->sessionModel->getSessionWithDetails((int) $id);
            if (is_object($tmp)) {
                $sessionRow = (array) $tmp;
            } elseif (is_array($tmp)) {
                $sessionRow = $tmp;
            }
        }
        if (!is_array($sessionRow)) {
            $sessionRow = $this->sessionModel->asArray()->find((int) $id);
        }
        if (empty($sessionRow)) {
            return redirect()->to('counselor/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }

        // Ownership
        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        // Participants (join agar ada nama siswa)
        $participants = $this->db->table('session_participants sp')
            ->select("
                sp.id AS participant_id,
                sp.student_id,
                s.nisn, s.nis,
                u.full_name AS student_name,
                c.class_name,
                sp.attendance_status,
                sp.participation_note
            ")
            ->join('students s', 's.id = sp.student_id')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('sp.session_id', (int) $id)
            ->where('sp.deleted_at', null)
            ->orderBy('u.full_name', 'asc')
            ->get()->getResultArray();

        // Ambil notes (dengan nama konselor)
        $notes = method_exists($this->noteModel, 'getBySession')
            ? $this->noteModel->getBySession((int) $id)
            : $this->noteModel->asArray()
                ->select('session_notes.*, users.full_name AS counselor_name, users.email AS counselor_email')
                ->join('users', 'users.id = session_notes.created_by', 'left')
                ->where('session_notes.session_id', (int) $id)
                ->where('session_notes.deleted_at', null)
                ->orderBy('session_notes.created_at', 'DESC')
                ->findAll();

        // kompatibilitas: jika ada view yang membaca $session['notes']
        $sessionRow['notes'] = $notes;

        $data = [
            'session'      => $sessionRow,
            'participants' => $participants,
            'notes'        => $notes,
            'title'        => 'Detail Sesi Konseling',
            'pageTitle'    => 'Detail Sesi Konseling',
            'breadcrumbs'  => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Sesi Konseling', 'url' => base_url('counselor/sessions')],
                ['title' => 'Detail', 'url' => '#', 'active' => true],
            ],
        ];

        return view('counselor/sessions/detail', $data);
    }

    // =========================================================================
    // EDIT (FORM)
    // =========================================================================

    /**
     * Show edit session form
     *
     * @param int $id
     * @return string|RedirectResponse|ResponseInterface
     */
    public function edit($id)
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $sessionRow = $this->sessionModel->asArray()->find((int) $id);
        if (empty($sessionRow)) {
            return redirect()->to('counselor/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }

        // Hydration untuk Individu (tampilkan siswa terpilih)
        if (($sessionRow['session_type'] ?? '') === 'Individu' && !empty($sessionRow['student_id'])) {
            $stu = $this->studentModel
                ->asArray()
                ->select('students.id, students.nisn, students.nis, users.full_name AS student_name, classes.class_name')
                ->join('users', 'users.id = students.user_id')
                ->join('classes', 'classes.id = students.class_id', 'left')
                ->where('students.id', (int) $sessionRow['student_id'])
                ->first();

            if ($stu) {
                $sessionRow['student_name'] = $stu['student_name'];
                $sessionRow['student_nisn'] = $stu['nisn'];
                $sessionRow['student_nis']  = $stu['nis'];
                $sessionRow['class_name']   = $stu['class_name'];
            }
        }

        // Hydration untuk Klasikal (tampilkan nama kelas)
        if (($sessionRow['session_type'] ?? '') === 'Klasikal' && !empty($sessionRow['class_id'])) {
            $cls = $this->classModel
                ->asArray()
                ->select('id, class_name')
                ->find((int) $sessionRow['class_id']);

            if ($cls) {
                $sessionRow['class_name'] = $cls['class_name'];
            }
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        // ID peserta terpilih (untuk preselect)
        $selectedIds = $this->db->table('session_participants')
            ->select('student_id')
            ->where('session_id', (int) $id)
            ->where('deleted_at', null)
            ->get()->getResultArray();

        $data = [
            'session'                => $sessionRow,
            'students'               => $this->getActiveStudents(),
            'classes'                => $this->getActiveClasses(),
            'selectedParticipantIds' => array_map('intval', array_column($selectedIds, 'student_id')),
            'title'                  => 'Edit Sesi Konseling',
            'pageTitle'              => 'Edit Sesi Konseling',
            'breadcrumbs'            => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Sesi Konseling', 'url' => base_url('counselor/sessions')],
                ['title' => 'Edit', 'url' => '#', 'active' => true],
            ],
        ];

        return view('counselor/sessions/edit', $data);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /**
     * Update counseling session
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function update($id)
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $sessionRow = $this->sessionModel->asArray()->find((int) $id);
        if (empty($sessionRow)) {
            return redirect()->to('counselor/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        // --- Normalisasi input dulu ---
        $post = $this->request->getPost();

        // participants -> array<int>
        $rawParticipants = $this->request->getPost('participants');
        if ($rawParticipants === null) {
            $post['participants'] = [];
        } else {
            if (is_string($rawParticipants)) {
                $rawParticipants = preg_split('/[,\s]+/', trim($rawParticipants)) ?: [];
            }
            $post['participants'] = array_values(array_unique(
                array_filter(array_map('intval', (array) $rawParticipants))
            ));
        }

        // session_time: pertahankan lama jika kosong
        $rawTime = isset($post['session_time']) ? trim((string) $post['session_time']) : null;
        if ($rawTime === '' || $rawTime === null) {
            $post['session_time'] = $sessionRow['session_time'] ?? null;
        } else {
            $normalized = $this->normalizeTime($rawTime);
            if ($normalized === null) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', ['session_time' => 'Format waktu tidak valid (HH:MM)']);
            }
            $post['session_time'] = $normalized;
        }

        // durasi
        $post['duration_minutes'] = ($post['duration_minutes'] ?? '') !== ''
            ? max(0, (int) $post['duration_minutes'])
            : null;

        // checkbox kerahasiaan
        $post['is_confidential'] = isset($post['is_confidential']) ? (int) $post['is_confidential'] : 0;

        // Pastikan validator membaca data yang dinormalisasi
        $this->request->setGlobal('post', $post);

        // Validasi umum
        $rules = SessionValidation::updateRules();
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Validasi khusus pembatalan (opsional)
        if (isset($post['status'])) {
            $customValidation = SessionValidation::validateCancellation($post);
            if ($customValidation !== true) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', $customValidation);
            }
        }

        try {
            $now = date('Y-m-d H:i:s');

            $updateData = [
                'session_type'        => $post['session_type'],
                'student_id'          => !empty($post['student_id']) ? (int) $post['student_id'] : null,
                'class_id'            => !empty($post['class_id']) ? (int) $post['class_id'] : null,
                'session_date'        => $post['session_date'],
                'session_time'        => $post['session_time'],
                'location'            => $post['location'] ?? null,
                'topic'               => $post['topic'],
                'problem_description' => $post['problem_description'] ?? null,
                'session_summary'     => $post['session_summary'] ?? null,
                'follow_up_plan'      => $post['follow_up_plan'] ?? null,
                'status'              => $post['status'] ?? ($sessionRow['status'] ?? 'Dijadwalkan'),
                'cancellation_reason' => $post['cancellation_reason'] ?? null,
                'is_confidential'     => $post['is_confidential'],
                'duration_minutes'    => $post['duration_minutes'],
                'updated_at'          => $now,
            ];

            $this->sessionModel->update((int) $id, $updateData);

            // Sinkronisasi kerahasiaan catatan sesi -> session_notes.is_confidential
            if (method_exists($this->noteModel, 'setConfidentialBySession')) {
                $this->noteModel->setConfidentialBySession((int) $id, (bool) $post['is_confidential']);
            }

            // --- Sinkron peserta ---
            $builder = $this->db->table('session_participants');

            // Ambil peserta lama
            $old = $builder->select('student_id')
                ->where('session_id', (int) $id)
                ->where('deleted_at', null)
                ->get()->getResultArray();

            $oldIds = array_map('intval', array_column($old, 'student_id'));

            // Tentukan peserta baru berdasarkan tipe
            if ($post['session_type'] === 'Klasikal' && !empty($post['class_id'])) {
                $classStudents = $this->studentModel
                    ->asArray()
                    ->where('class_id', (int) $post['class_id'])
                    ->where('status', 'Aktif')
                    ->findAll();
                $newIds = array_map(
                    static fn($r) => (int) ($r['id'] ?? 0),
                    $classStudents
                );
            } elseif ($post['session_type'] === 'Kelompok') {
                $newIds = $post['participants']; // sudah dinormalisasi
            } else {
                $newIds = []; // Individu: kosongkan relasi peserta
            }

            $newIds = array_values(array_unique(array_filter(array_map('intval', (array) $newIds))));

            $toAdd = array_diff($newIds, $oldIds);
            $toDel = array_diff($oldIds, $newIds);

            if (!empty($toAdd)) {
                $rows = [];
                foreach ($toAdd as $sid) {
                    $rows[] = [
                        'session_id'        => (int) $id,
                        'student_id'        => (int) $sid,
                        'attendance_status' => 'Hadir',
                        'joined_at'         => $now,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
                $builder->insertBatch($rows);
            }

            if (!empty($toDel)) {
                // Hard delete baris peserta yang tidak lagi dipilih
                $builder->where('session_id', (int) $id)
                    ->whereIn('student_id', $toDel)
                    ->delete();
            }

            return redirect()->to('counselor/sessions/detail/' . (int) $id)
                ->with('success', 'Sesi konseling berhasil diupdate');
        } catch (\Throwable $e) {
            log_message('error', 'Error updating session: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /**
     * Delete (soft delete) counseling session
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function delete($id)
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $sessionRow = $this->sessionModel->asArray()->find((int) $id);
        if (empty($sessionRow)) {
            return redirect()->to('counselor/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        try {
            $this->sessionModel->delete((int) $id);
            return redirect()->to('counselor/sessions')->with('success', 'Sesi konseling berhasil dihapus');
        } catch (\Throwable $e) {
            log_message('error', 'Error deleting session: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus sesi');
        }
    }

    // =========================================================================
    // ADD NOTE
    // =========================================================================

    /**
     * Add note to counseling session
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function addNote($id)
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $sessionRow = $this->sessionModel->asArray()->find((int) $id);
        if (empty($sessionRow)) {
            return redirect()->to('counselor/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }
        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        $rules = [
            'note_content' => 'required|min_length[5]|max_length[2000]',
            'is_important' => 'permit_empty|in_list[0,1]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Normalisasi jenis catatan dari POST, fallback ke Observasi
        $allowedTypes = ['Observasi', 'Diagnosis', 'Intervensi', 'Follow-up', 'Lainnya'];
        $noteType     = $post['note_type'] ?? 'Observasi';
        if (!in_array($noteType, $allowedTypes, true)) {
            $noteType = 'Observasi';
        }

        // Upload lampiran (boleh kosong)
        $attachments = $this->handleNoteAttachmentsUpload();

        try {
            $this->noteModel->insert([
                'session_id'      => (int) $id,
                'created_by'      => (int) auth_id(),
                'note_type'       => $noteType,
                'note_content'    => (string) $post['note_content'],
                'is_confidential' => isset($post['is_confidential'])
                    ? (int) (bool) $post['is_confidential']
                    : (int) ($sessionRow['is_confidential'] ?? 1),
                'is_important'    => isset($post['is_important']) ? 1 : 0,
                'attachments'     => $attachments, // akan di-JSON-kan oleh callback model
            ]);

            return redirect()->to('counselor/sessions/detail/' . (int) $id)
                ->with('success', 'Catatan berhasil ditambahkan');
        } catch (\Throwable $e) {
            log_message('error', 'Error adding note: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan catatan.');
        }
    }

    // =========================================================================
    // UPDATE NOTE
    // =========================================================================

    /**
     * Update existing session note (content / flags / attachments)
     *
     * @param int $noteId
     * @return RedirectResponse
     */
    public function updateNote($noteId)
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $note = $this->noteModel->getById((int) $noteId);
        if (!$note) {
            return redirect()->to('counselor/sessions')->with('error', 'Catatan sesi tidak ditemukan');
        }

        $sessionId  = (int) ($note['session_id'] ?? 0);
        $sessionRow = $this->sessionModel->asArray()->find($sessionId);
        if (empty($sessionRow)) {
            return redirect()->to('counselor/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        $rules = [
            'note_content' => 'required|min_length[5]|max_length[10000]',
            'is_important' => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
            ->withInput()
            ->with('error', 'Validasi gagal. Catatan tidak tersimpan.')
            ->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        $isConfidential = isset($post['is_confidential'])
            ? (int) (bool) $post['is_confidential']
            : (int) ($note['is_confidential'] ?? 0);
        $isImportant = isset($post['is_important']) ? 1 : 0;

        // Jenis catatan (boleh diubah)
        $allowedTypes = ['Observasi', 'Diagnosis', 'Intervensi', 'Follow-up', 'Lainnya'];
        $noteType     = $post['note_type'] ?? ($note['note_type'] ?? 'Observasi');
        if (!in_array($noteType, $allowedTypes, true)) {
            $noteType = 'Observasi';
        }

        // Ambil lampiran lama sebagai array
        $originalAttachments = $this->noteModel->attachmentsToArray($note);

        // Daftar lampiran yang diminta dihapus (berdasarkan path)
        $deleteRequested = $this->request->getPost('delete_attachments');
        $deleteRequested = is_array($deleteRequested) ? $deleteRequested : [];

        $remainingAttachments = [];
        $deletedPaths         = [];

        foreach ($originalAttachments as $path) {
            if (in_array($path, $deleteRequested, true)) {
                $deletedPaths[] = $path;
            } else {
                $remainingAttachments[] = $path;
            }
        }

        // Lampiran baru (jika ada upload baru)
        $newAttachments = $this->handleNoteAttachmentsUpload();

        // Gabungkan lampiran lama yang masih dipertahankan + lampiran baru
        $allAttachments = array_values(array_unique(array_merge($remainingAttachments, $newAttachments)));

        // Data update utama
        $updateData = [
            'note_type'       => $noteType,
            'note_content'    => (string) $post['note_content'],
            'is_confidential' => $isConfidential,
            'is_important'    => $isImportant,
            'attachments'     => $allAttachments, // boleh kosong (akan jadi "[]")
        ];

        try {
            $ok = $this->noteModel->update((int) $noteId, $updateData);
        if ($ok === false) {
            $errs = $this->noteModel->errors();
            log_message('error', 'Failed updating session note. note_id={noteId} errors={errors}', [
                'noteId' => (int) $noteId,
                'errors' => json_encode($errs),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui catatan. Silakan cek input Anda.')
                ->with('errors', $errs ?: ['note_content' => 'Gagal memperbarui catatan.']);
        }

            // Hapus file fisik untuk lampiran yang dihapus (jika ada di disk)
            if (!empty($deletedPaths)) {
                foreach ($deletedPaths as $path) {
                    $fullPath = FCPATH . ltrim((string) $path, '/');
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }

            return redirect()->to('counselor/sessions/detail/' . $sessionId)
                ->with('success', 'Catatan berhasil diperbarui');
        } catch (\Throwable $e) {
            log_message('error', 'Error updating session note: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui catatan.');
        }
    }

    // =========================================================================
    // DELETE NOTE
    // =========================================================================

    /**
     * Soft delete session note
     *
     * @param int $noteId
     * @return RedirectResponse
     */
    public function deleteNote($noteId)
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $note = $this->noteModel->getById((int) $noteId);
        if (!$note) {
            return redirect()->to('counselor/sessions')->with('error', 'Catatan sesi tidak ditemukan');
        }

        $sessionId  = (int) ($note['session_id'] ?? 0);
        $sessionRow = $this->sessionModel->asArray()->find($sessionId);
        if (!$sessionRow) {
            return redirect()->to('counselor/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        try {
            $this->noteModel->deleteNote((int) $noteId);

            return redirect()->to('counselor/sessions/detail/' . $sessionId)
                ->with('success', 'Catatan berhasil dihapus');
        } catch (\Throwable $e) {
            log_message('error', 'Error deleting session note: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus catatan.');
        }
    }

    // =========================================================================
    // PARTICIPANT UPDATE (KEHADIRAN + OPSIONAL CATATAN)
    // =========================================================================

    /**
     * Update kehadiran dan (opsional) catatan partisipasi untuk satu peserta.
     *
     * Route: POST /counselor/sessions/participants/update/{sessionId}
     *
     * Input:
     *  - participant_id (required, hidden)
     *  - attendance_status (required, select)
     *  - participation_note (optional, textarea)
     *
     * @param int $sessionId
     * @return RedirectResponse
     */
    public function updateParticipant(int $sessionId): RedirectResponse
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $sessionId     = (int) $sessionId;
        $participantId = (int) $this->request->getPost('participant_id');

        if ($sessionId <= 0 || $participantId <= 0) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Data sesi atau peserta tidak valid.');
        }

        // Pastikan sesi ada dan milik konselor (kecuali koordinator)
        $sessionRow = $this->sessionModel->asArray()->find($sessionId);
        if (!$sessionRow) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Sesi konseling tidak ditemukan.');
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Anda tidak memiliki akses ke sesi ini.');
        }

        // Pastikan participant memang milik sesi tersebut
        $participant = $this->participantModel
            ->asArray()
            ->find($participantId);

        if (!$participant || (int) ($participant['session_id'] ?? 0) !== $sessionId) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Peserta sesi tidak ditemukan.');
        }

        // Validasi input
        $rules = [
            'attendance_status'  => 'required|max_length[50]',
            'participation_note' => 'permit_empty|max_length[1000]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $attendance = trim((string) $this->request->getPost('attendance_status'));
        $noteText   = trim((string) $this->request->getPost('participation_note'));

        $updateData = [
            'attendance_status'  => $attendance,
            'participation_note' => ($noteText !== '') ? $noteText : null,
        ];

        try {
            $this->participantModel->update($participantId, $updateData);

            return redirect()->to('counselor/sessions/detail/' . $sessionId)
                ->with('success', 'Kehadiran peserta berhasil diperbarui.');
        } catch (\Throwable $e) {
            log_message('error', 'Error updating participant (attendance): ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui kehadiran peserta.');
        }
    }

    // =========================================================================
    // PARTICIPATION NOTE (BARU / LEGACY)
    // =========================================================================

    /**
     * Update catatan partisipasi peserta pada sesi konseling
     *
     * Route: POST counselor/sessions/participants/note/update
     *
     * Input:
     *  - participant_id (hidden)
     *  - session_id     (hidden)
     *  - participation_note (textarea)
     *
     * @return RedirectResponse
     */
    public function updateParticipantNote(): RedirectResponse
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $participantId = (int) $this->request->getPost('participant_id');
        $sessionId     = (int) $this->request->getPost('session_id');

        if ($participantId <= 0 || $sessionId <= 0) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Data peserta atau sesi tidak valid');
        }

        // Ambil peserta
        $participant = $this->participantModel
            ->asArray()
            ->find($participantId);

        if (!$participant || (int) ($participant['session_id'] ?? 0) !== $sessionId) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Peserta sesi tidak ditemukan');
        }

        // Pastikan pemilik sesi (atau Koordinator)
        $sessionRow = $this->sessionModel->asArray()->find($sessionId);
        if (!$sessionRow) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Sesi konseling tidak ditemukan');
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        // Validasi sederhana: maksimal 1000 karakter, boleh kosong
        $rules = [
            'participation_note' => 'permit_empty|max_length[1000]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $noteText = trim((string) $this->request->getPost('participation_note'));

        $updateData = [
            'participation_note' => ($noteText !== '') ? $noteText : null,
        ];

        try {
            $this->participantModel->update($participantId, $updateData);

            return redirect()->to('counselor/sessions/detail/' . $sessionId)
                ->with('success', 'Catatan partisipasi berhasil disimpan');
        } catch (\Throwable $e) {
            log_message('error', 'Error updating participation note: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan catatan partisipasi.');
        }
    }

    /**
     * Mengosongkan catatan partisipasi peserta (set NULL)
     *
     * Route: POST counselor/sessions/participants/note/delete
     *
     * Input:
     *  - participant_id
     *  - session_id
     *
     * @return RedirectResponse
     */
    public function deleteParticipantNote(): RedirectResponse
    {
        if (!is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        if (!is_guru_bk() && !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $participantId = (int) $this->request->getPost('participant_id');
        $sessionId     = (int) $this->request->getPost('session_id');

        if ($participantId <= 0 || $sessionId <= 0) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Data peserta atau sesi tidak valid');
        }

        $participant = $this->participantModel
            ->asArray()
            ->find($participantId);

        if (!$participant || (int) ($participant['session_id'] ?? 0) !== $sessionId) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Peserta sesi tidak ditemukan');
        }

        $sessionRow = $this->sessionModel->asArray()->find($sessionId);
        if (!$sessionRow) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Sesi konseling tidak ditemukan');
        }

        if (!is_koordinator() && (int) ($sessionRow['counselor_id'] ?? 0) !== (int) auth_id()) {
            return redirect()->to('counselor/sessions')
                ->with('error', 'Anda tidak memiliki akses ke sesi ini');
        }

        try {
            $this->participantModel->update($participantId, [
                'participation_note' => null,
            ]);

            return redirect()->to('counselor/sessions/detail/' . $sessionId)
                ->with('success', 'Catatan partisipasi berhasil dihapus');
        } catch (\Throwable $e) {
            log_message('error', 'Error deleting participation note: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus catatan partisipasi.');
        }
    }

    // =========================================================================
    // AJAX: GET STUDENTS BY CLASS
    // =========================================================================

    /**
     * Get students by class (AJAX for form)
     *
     * @return ResponseInterface
     */
    public function getStudentsByClass()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request',
            ]);
        }

        $classId = $this->request->getGet('class_id');
        if (empty($classId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Class ID required',
            ]);
        }

        try {
            $students = $this->studentModel
                ->asArray()
                ->select('students.id, students.nisn, students.nis, users.full_name as student_name')
                ->join('users', 'users.id = students.user_id')
                ->where('students.class_id', (int) $classId)
                ->where('students.status', 'Aktif')
                ->orderBy('users.full_name', 'ASC')
                ->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data'    => $students,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Upload lampiran catatan sesi dan kembalikan array path relatif.
     *
     * Path yang disimpan: "uploads/counseling_notes/xxxx.ext"
     * (disimpan di FCPATH/uploads/counseling_notes)
     *
     * @return array<int,string>
     */
    private function handleNoteAttachmentsUpload(): array
    {
        $files = $this->request->getFiles();
        $paths = [];

        if (!isset($files['attachments'])) {
            return $paths;
        }

        $fileSet  = $files['attachments'];
        $fileList = is_array($fileSet) ? $fileSet : [$fileSet];

        foreach ($fileList as $file) {
            if (!$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $newName   = $file->getRandomName();
            $targetDir = FCPATH . 'uploads/counseling_notes';

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            if ($file->move($targetDir, $newName)) {
                // Simpan path relatif terhadap public root
                $paths[] = 'uploads/counseling_notes/' . $newName;
            }
        }

        return $paths;
    }

    /**
     * Get active students for dropdown
     *
     * @return array<int, array<string, mixed>>
     */
    private function getActiveStudents(): array
    {
        $rows = $this->studentModel
            ->asArray()
            ->select('students.id, students.nisn, students.nis, users.full_name as student_name, classes.class_name')
            ->join('users', 'users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.status', 'Aktif')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();

        return array_map(static function ($r) {
            return is_object($r) ? (array) $r : $r;
        }, $rows);
    }

    /**
     * Normalisasi waktu "H:i" / "H:i:s" / string bebas -> "H:i:s" atau null
     */
    private function normalizeTime(?string $t): ?string
    {
        if ($t === null) {
            return null;
        }
        $t = trim($t);
        if ($t === '') {
            return null;
        }

        $t = str_replace('.', ':', $t);

        if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
            return $t . ':00';
        }

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $t)) {
            return $t;
        }

        $dt = date_create($t);
        return $dt ? $dt->format('H:i:s') : null;
    }

    /**
     * Get active classes for dropdown
     *
     * @return array<int, array<string, mixed>>
     */
    private function getActiveClasses(): array
    {
        $rows = $this->classModel
            ->asArray()
            ->where('is_active', 1)
            ->orderBy('class_name', 'ASC')
            ->findAll();

        return array_map(static function ($r) {
            return is_object($r) ? (array) $r : $r;
        }, $rows);
    }
}
