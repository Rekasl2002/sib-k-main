<?php

/**
 * File Path: app/Controllers/Koordinator/SessionController.php
 *
 * Koordinator BK • Session Controller (Read-only)
 * - Koordinator boleh melihat semua sesi (semua konselor)
 * - Koordinator tidak boleh CRUD sesi, notes, atau update peserta (read-only)
 *
 * @package    SIB-K
 * @subpackage Controllers/Koordinator
 * @category   Counseling
 */

namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use App\Models\CounselingSessionModel;
use App\Models\SessionNoteModel;
use App\Models\SessionParticipantModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
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

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->sessionModel     = new CounselingSessionModel();
        $this->noteModel        = new SessionNoteModel();
        $this->participantModel = new SessionParticipantModel();
        $this->studentModel     = new StudentModel();
        $this->classModel       = new ClassModel();
        $this->db               = \Config\Database::connect();
    }

    // =========================================================================
    // GUARDS
    // =========================================================================

    /**
     * Guard: wajib login + role koordinator
     */
    private function guardKoordinator(): ?RedirectResponse
    {
        if (!function_exists('is_logged_in') || !is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!function_exists('is_koordinator') || !is_koordinator()) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        return null;
    }

    /**
     * Blok semua aksi tulis untuk Koordinator (read-only)
     */
    private function denyWrite(string $message = 'Akses ditolak. Koordinator hanya dapat melihat data.'): RedirectResponse
    {
        return redirect()->to('koordinator/sessions')->with('error', $message);
    }

    // =========================================================================
    // INDEX (LIST)
    // =========================================================================

    /**
     * Display list of counseling sessions (ALL counselors) for Koordinator
     *
     * @return string|RedirectResponse|ResponseInterface
     */
    public function index()
    {
        if ($redir = $this->guardKoordinator()) {
            return $redir;
        }

        $filters = [
            'status'       => $this->request->getGet('status'),
            'session_type' => $this->request->getGet('session_type'),
            'start_date'   => $this->request->getGet('start_date'),
            'end_date'     => $this->request->getGet('end_date'),
            'student_id'   => $this->request->getGet('student_id'),
            'counselor_id' => $this->request->getGet('counselor_id'),
            'search'       => $this->request->getGet('search'),
        ];

        // Query sesi (read-only) + join student/class/counselor + counts
        $builder = $this->db->table('counseling_sessions cs')
            ->select('
                cs.*,
                s.nisn, s.nis,
                su.full_name AS student_name,
                c.class_name,
                cu.full_name AS counselor_name,
                cu.email AS counselor_email,
                (SELECT COUNT(*) FROM session_notes sn
                    WHERE sn.session_id = cs.id AND sn.deleted_at IS NULL
                ) AS note_count,
                (SELECT COUNT(*) FROM session_participants sp
                    WHERE sp.session_id = cs.id
                      AND (sp.is_active = 1 OR sp.is_active IS NULL)
                      AND sp.deleted_at IS NULL
                ) AS participant_count
            ')
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->join('classes c', 'c.id = cs.class_id', 'left')
            ->join('users cu', 'cu.id = cs.counselor_id', 'left')
            ->where('cs.deleted_at', null);

        // Filters
        if (!empty($filters['session_type'])) {
            $builder->where('cs.session_type', $filters['session_type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('cs.status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $builder->where('cs.session_date >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $builder->where('cs.session_date <=', $filters['end_date']);
        }

        if (!empty($filters['counselor_id'])) {
            $builder->where('cs.counselor_id', (int) $filters['counselor_id']);
        }

        // Filter siswa (hanya relevan untuk sesi Individu; tapi aman tetap dipakai)
        if (!empty($filters['student_id'])) {
            $builder->where('cs.student_id', (int) $filters['student_id']);
        }

        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('cs.topic', $q)
                ->orLike('su.full_name', $q)
                ->orLike('s.nisn', $q)
                ->orLike('cu.full_name', $q)
                ->groupEnd();
        }

        $builder->orderBy('cs.session_date', 'DESC')
            ->orderBy('cs.session_time', 'DESC');

        $sessions = $builder->get()->getResultArray();
        $sessions = is_array($sessions) ? $sessions : [];

        $data = [
            'sessions'    => $sessions,
            'students'    => $this->getActiveStudents(),
            'classes'     => $this->getActiveClasses(),
            'counselors'  => $this->getCounselorsForFilter(),
            'filters'     => $filters,
            'title'       => 'Daftar Sesi Konseling (Koordinator)',
            'pageTitle'   => 'Sesi Konseling',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Sesi Konseling', 'url' => '#', 'active' => true],
            ],
        ];

        return view('koordinator/sessions/index', $data);
    }

    // =========================================================================
    // SHOW (DETAIL)
    // =========================================================================

    /**
     * Show session detail with notes/participants (read-only)
     *
     * Route biasanya: GET koordinator/sessions/detail/(:num) -> show($1)
     *
     * @param int $id
     * @return string|RedirectResponse|ResponseInterface
     */
    public function show($id)
    {
        if ($redir = $this->guardKoordinator()) {
            return $redir;
        }

        $id = (int) $id;

        // Ambil sesi + info siswa/konselor/kelas (pakai alias yang cocok untuk view)
        $sessionRow = $this->db->table('counseling_sessions cs')
            ->select('
                cs.*,
                s.nisn AS student_nisn,
                s.nis  AS student_nis,
                su.full_name AS student_name,
                su.email AS student_email,
                cu.full_name AS counselor_name,
                cu.email AS counselor_email,
                c.class_name
            ')
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->join('users cu', 'cu.id = cs.counselor_id', 'left')
            ->join('classes c', 'c.id = cs.class_id', 'left')
            ->where('cs.id', $id)
            ->where('cs.deleted_at', null)
            ->get()->getRowArray();

        if (empty($sessionRow)) {
            return redirect()->to('koordinator/sessions')->with('error', 'Sesi konseling tidak ditemukan');
        }

        // Participants (join agar ada nama siswa + kelas)
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
            ->where('sp.session_id', $id)
            ->where('sp.deleted_at', null)
            ->orderBy('u.full_name', 'asc')
            ->get()->getResultArray();

        // Notes (dengan nama/email pembuat)
        $notes = method_exists($this->noteModel, 'getBySession')
            ? $this->noteModel->getBySession($id)
            : $this->noteModel->asArray()
                ->select('session_notes.*, users.full_name AS counselor_name, users.email AS counselor_email')
                ->join('users', 'users.id = session_notes.created_by', 'left')
                ->where('session_notes.session_id', $id)
                ->where('session_notes.deleted_at', null)
                ->orderBy('session_notes.created_at', 'DESC')
                ->findAll();

        // kompatibilitas: jika view membaca $session['notes']
        $sessionRow['notes'] = $notes;

        $data = [
            'session'      => $sessionRow,
            'participants' => $participants,
            'notes'        => $notes,
            'title'        => 'Detail Sesi Konseling',
            'pageTitle'    => 'Detail Sesi Konseling',
            'breadcrumbs'  => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Sesi Konseling', 'url' => base_url('koordinator/sessions')],
                ['title' => 'Detail', 'url' => '#', 'active' => true],
            ],
        ];

        return view('koordinator/sessions/detail', $data);
    }

    // =========================================================================
    // BLOCKED WRITE METHODS (Optional safety)
    // =========================================================================

    public function create() { return $this->denyWrite(); }
    public function store()  { return $this->denyWrite(); }
    public function edit($id = null)   { return $this->denyWrite(); }
    public function update($id = null) { return $this->denyWrite(); }
    public function delete($id = null) { return $this->denyWrite(); }

    public function addNote($id = null)        { return $this->denyWrite(); }
    public function updateNote($noteId = null) { return $this->denyWrite(); }
    public function deleteNote($noteId = null) { return $this->denyWrite(); }

    public function updateParticipant(int $sessionId): RedirectResponse { return $this->denyWrite(); }
    public function updateParticipantNote(): RedirectResponse { return $this->denyWrite(); }
    public function deleteParticipantNote(): RedirectResponse { return $this->denyWrite(); }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Get active students for filter dropdown
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

        return is_array($rows) ? $rows : [];
    }

    /**
     * Get active classes for filter dropdown
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

        return is_array($rows) ? $rows : [];
    }

    /**
     * Ambil daftar konselor untuk filter (berdasarkan sesi yang ada),
     * tanpa asumsi struktur role/role_id.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCounselorsForFilter(): array
    {
        $rows = $this->db->table('counseling_sessions cs')
            ->select('cu.id, cu.full_name')
            ->join('users cu', 'cu.id = cs.counselor_id', 'left')
            ->where('cs.deleted_at', null)
            ->where('cu.id IS NOT NULL', null, false)
            ->groupBy('cu.id')
            ->orderBy('cu.full_name', 'ASC')
            ->get()->getResultArray();

        return is_array($rows) ? $rows : [];
    }
}
