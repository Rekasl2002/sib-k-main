<?php

/**
 * File Path: app/Controllers/HomeroomTeacher/StudentSessionsController.php
 *
 * Homeroom Teacher • Student Sessions
 * - /homeroom/sessions                         → default: SEMUA siswa perwalian (bisa difilter student_id)
 * - /homeroom/students/{studentId}/sessions    → fokus 1 siswa (kompatibel)
 * - /homeroom/students/{studentId}/sessions?range=past → riwayat 1 siswa
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use CodeIgniter\Database\BaseConnection;

class StudentSessionsController extends BaseController
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['url']);
    }

    /**
     * Helper: pastikan siswa memang termasuk kelas perwalian Wali Kelas yang login.
     * NOTE: students.full_name sudah dihapus → ambil nama dari users.full_name.
     */
    protected function findStudentForCurrentHomeroom(int $studentId): ?array
    {
        $teacherId = (int) session('user_id');

        if ($teacherId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = model(StudentModel::class)
            ->select('
                students.id,
                students.user_id,
                u.full_name,
                students.class_id,
                c.class_name,
                c.grade_level,
                c.major
            ')
            ->join('users u', 'u.id = students.user_id', 'left')
            ->join('classes c', 'c.id = students.class_id', 'left')
            ->where('students.id', $studentId)
            ->where('students.deleted_at', null)
            ->where('c.deleted_at', null)
            ->where('c.homeroom_teacher_id', $teacherId)
            ->first();

        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : (array) $row;
    }

    /**
     * Helper: ambil seluruh siswa perwalian wali kelas yang sedang login.
     * NOTE: students.full_name sudah dihapus → ambil nama dari users.full_name.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function getHomeroomStudents(): array
    {
        $teacherId = (int) session('user_id');

        if ($teacherId <= 0) {
            return [];
        }

        $rows = model(StudentModel::class)
            ->select('
                students.id,
                students.user_id,
                u.full_name,
                students.class_id,
                c.class_name
            ')
            ->join('users u', 'u.id = students.user_id', 'left')
            ->join('classes c', 'c.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('c.deleted_at', null)
            ->where('c.homeroom_teacher_id', $teacherId)
            ->orderBy('u.full_name', 'ASC')
            ->findAll();

        return $rows ?: [];
    }

    /**
     * Daftar jadwal konseling (bisa mode SEMUA siswa atau 1 siswa)
     *
     * - /homeroom/sessions
     *     - default: semua siswa perwalian
     *     - filter: /homeroom/sessions?student_id=XX
     * - /homeroom/students/{studentId}/sessions (kompatibel, fokus 1 siswa)
     */
    public function sessions(?int $studentId = null)
    {
        $studentsList = $this->getHomeroomStudents();
        if (empty($studentsList)) {
            return redirect()->route('homeroom.dashboard')
                ->with('error', 'Belum ada data siswa perwalian untuk ditampilkan.');
        }

        // student_id boleh dari:
        // - parameter route (/homeroom/students/{id}/sessions)
        // - query (?student_id=)
        $requestedStudentId = (int) ($studentId ?: ($this->request->getGet('student_id') ?: 0));

        // Kalau 0 → mode ALL (semua siswa)
        $isAllMode = ($requestedStudentId <= 0);

        // Validasi kalau pilih 1 siswa
        $student = null;
        if (!$isAllMode) {
            $student = $this->findStudentForCurrentHomeroom($requestedStudentId);
            if (!$student) {
                return redirect()->to(site_url('homeroom/sessions'))
                    ->with('error', 'Data siswa tidak ditemukan atau bukan siswa perwalian Anda.');
            }
        }

        // Filter dari querystring
        $status  = $this->request->getGet('status');              // Dijadwalkan|Selesai|Dibatalkan|Tidak Hadir|null
        $range   = $this->request->getGet('range') ?: 'upcoming'; // upcoming | past | all
        $q       = trim((string) $this->request->getGet('q'));
        $perPage = (int) ($this->request->getGet('perPage') ?? 10);
        $perPage = max(5, min($perPage, 50));
        $today   = date('Y-m-d');

        // Kumpulan studentId & classId (untuk mode ALL)
        $allStudentIds = array_values(array_map(
            static fn($r) => (int) ($r['id'] ?? 0),
            $studentsList
        ));
        $allStudentIds = array_values(array_filter($allStudentIds));

        $allClassIds = array_values(array_unique(array_filter(array_map(
            static fn($r) => (int) ($r['class_id'] ?? 0),
            $studentsList
        ))));

        // Mapping class_id -> student_id pertama (untuk link detail sesi Klasikal)
        $firstStudentByClass = [];
        foreach ($studentsList as $s) {
            $cid = (int) ($s['class_id'] ?? 0);
            $sid = (int) ($s['id'] ?? 0);
            if ($cid > 0 && $sid > 0 && !isset($firstStudentByClass[$cid])) {
                $firstStudentByClass[$cid] = $sid;
            }
        }

        // Kalau mode 1 siswa, override kumpulan ID
        $studentIds = $isAllMode ? $allStudentIds : [(int) $student['id']];
        $classIds   = $isAllMode ? $allClassIds   : [ (int) ($student['class_id'] ?? 0) ];

        $studentIds = array_values(array_filter(array_map('intval', $studentIds)));
        $classIds   = array_values(array_filter(array_map('intval', $classIds)));

        if (empty($studentIds) && empty($classIds)) {
            return redirect()->route('homeroom.dashboard')
                ->with('error', 'Tidak ada siswa/kelas perwalian yang valid.');
        }

        // Untuk CASE IN (...) di SELECT (khusus nama peserta kelompok)
        $inStudentIdsSql = !empty($studentIds) ? implode(',', array_map('intval', $studentIds)) : '0';

        // Query sesi (gabungan: Individu, Kelompok, Klasikal)
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
                cs.student_id AS individual_student_id,
                cs.class_id,
                coun.full_name AS counselor_name,

                u_ind.full_name AS individual_student_name,
                cl.class_name AS class_name,

                MIN(CASE WHEN sp.student_id IN ($inStudentIdsSql) THEN sp.student_id END) AS any_participant_id,
                GROUP_CONCAT(
                    DISTINCT CASE
                        WHEN sp.student_id IN ($inStudentIdsSql) THEN u_sp.full_name
                        ELSE NULL
                    END
                    ORDER BY u_sp.full_name SEPARATOR ', '
                ) AS participant_names
            ", false)
            ->join('users coun', 'coun.id = cs.counselor_id', 'left')
            ->join('students s_ind', 's_ind.id = cs.student_id', 'left')
            ->join('users u_ind', 'u_ind.id = s_ind.user_id', 'left')
            ->join('classes cl', 'cl.id = cs.class_id', 'left')
            ->join('session_participants sp', 'sp.session_id = cs.id AND sp.deleted_at IS NULL', 'left')
            ->join('students s_sp', 's_sp.id = sp.student_id', 'left')
            ->join('users u_sp', 'u_sp.id = s_sp.user_id', 'left')
            ->where('cs.deleted_at', null)
            ->groupStart()
                // Individu
                ->groupStart()
                    ->where('cs.session_type', 'Individu')
                    ->whereIn('cs.student_id', $studentIds)
                ->groupEnd()
                // Kelompok: ada peserta dari siswa perwalian
                ->orGroupStart()
                    ->where('cs.session_type', 'Kelompok')
                    ->whereIn('sp.student_id', $studentIds)
                ->groupEnd()
                // Klasikal: kelas perwalian
                ->orGroupStart()
                    ->where('cs.session_type', 'Klasikal')
                    ->whereIn('cs.class_id', $classIds)
                ->groupEnd()
            ->groupEnd()
            ->groupBy('cs.id');

        // Filter status
        if ($status && in_array($status, ['Dijadwalkan', 'Selesai', 'Dibatalkan', 'Tidak Hadir'], true)) {
            $b->where('cs.status', $status);
        }

        // Filter range waktu
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

        // Pencarian
        if ($q !== '') {
            $b->groupStart()
                ->like('cs.topic', $q)
                ->orLike('cs.problem_description', $q)
                ->orLike('cs.location', $q)
            ->groupEnd();
        }

        // Urutan
        if ($range === 'past') {
            $b->orderBy('cs.session_date', 'DESC')->orderBy('cs.session_time', 'DESC');
        } else {
            $b->orderBy('cs.session_date', 'ASC')->orderBy('cs.session_time', 'ASC');
        }

        $rows = $b->distinct()
            ->limit($perPage)
            ->get()
            ->getResultArray();

        // Bentuk label siswa + context_student_id untuk link detail (penting di mode ALL)
        foreach ($rows as &$r) {
            $type = (string) ($r['session_type'] ?? '');

            // context_student_id untuk membangun URL detail: /homeroom/students/{studentId}/sessions/{sessionId}
            $contextStudentId = 0;

            if ($type === 'Klasikal') {
                $cid = (int) ($r['class_id'] ?? 0);
                $r['student_label'] = 'Kelas ' . ($r['class_name'] ?? '');
                $contextStudentId   = (int) ($firstStudentByClass[$cid] ?? 0);
            } elseif ($type === 'Kelompok') {
                $r['student_label'] = $r['participant_names'] ?: 'Kelompok';
                $contextStudentId   = (int) ($r['any_participant_id'] ?? 0);
            } else { // Individu
                $r['student_label'] = $r['individual_student_name'] ?: '-';
                $contextStudentId   = (int) ($r['individual_student_id'] ?? 0);
            }

            $r['context_student_id'] = $contextStudentId;

            // Sensor data untuk sesi rahasia: hanya judul generik (seperti Parent/Student)
            $isConf     = (int) ($r['is_confidential'] ?? 0) === 1;
            $statusText = (string) ($r['status'] ?? '');
            if ($isConf && $statusText !== 'Dijadwalkan') {
                $r['topic']    = 'Sesi Konseling (Terbatas)';
                $r['location'] = null;
                unset($r['problem_description']);
            }
        }
        unset($r);

        // Data student untuk header (kalau mode 1 siswa)
        $studentHeader = null;
        if (!$isAllMode && $student) {
            $studentHeader = [
                'id'        => (int) $student['id'],
                'full_name' => (string) ($student['full_name'] ?? ''),
            ];
        }

        // View
        if ($range === 'past') {
            return view('homeroom_teacher/students/sessions_history', [
                'title'          => 'Riwayat Jadwal Konseling',
                'student'        => $studentHeader,     // null kalau ALL
                'isAllMode'      => $isAllMode,
                'activeStudentId'=> $isAllMode ? null : (int) $requestedStudentId,
                'history'        => $rows,
                'studentsList'   => $studentsList,
                'filters'        => [
                    'status'  => $status,
                    'range'   => $range,
                    'q'       => $q,
                    'perPage' => $perPage,
                ],
            ]);
        }

        return view('homeroom_teacher/students/sessions', [
            'title'          => 'Jadwal Konseling ',
            'student'        => $studentHeader,     // null kalau ALL
            'isAllMode'      => $isAllMode,
            'activeStudentId'=> $isAllMode ? null : (int) $requestedStudentId,
            'filters'        => [
                'status'  => $status,
                'range'   => $range,
                'q'       => $q,
                'perPage' => $perPage,
            ],
            'sessions'      => $rows,
            'studentsList'  => $studentsList,
        ]);
    }

    /**
     * Detail sesi konseling untuk Wali Kelas
     * URL: /homeroom/students/{studentId}/sessions/{sessionId}
     *
     * (Bagian ini kamu tidak perlu ubah untuk kebutuhan "lihat semua siswa",
     * karena kita sudah siapkan context_student_id di list untuk membangun link detail.)
     */
    public function sessionDetail(int $studentId, int $sessionId)
    {
        $student = $this->findStudentForCurrentHomeroom($studentId);

        if (!$student) {
            return redirect()->to(site_url('homeroom/sessions'))
                ->with('error', 'Data siswa tidak ditemukan atau bukan siswa perwalian Anda.');
        }

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
            return redirect()->to(site_url('homeroom/sessions?student_id=' . (int) $studentId))
                ->with('error', 'Sesi konseling tidak ditemukan.');
        }

        if (!empty($session['is_confidential'])) {
            return redirect()->to(site_url('homeroom/sessions?student_id=' . (int) $studentId))
                ->with('error', 'Sesi konseling ini bersifat rahasia dan tidak dapat diakses.');
        }

        // Cek hak akses siswa terhadap sesi ini
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
            return redirect()->to(site_url('homeroom/sessions?student_id=' . (int) $studentId))
                ->with('error', 'Anda tidak memiliki akses ke sesi konseling ini.');
        }

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
                ->orderBy('c.class_name', 'ASC')
                ->orderBy('u.full_name', 'ASC')
                ->get()
                ->getResultArray();
        }

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
            ->orderBy('sn.created_at', 'ASC')
            ->get()
            ->getResultArray();

        return view('homeroom_teacher/students/session_detail', [
            'title'             => 'Detail Sesi Konseling',
            'student'           => [
                'id'        => (int) $student['id'],
                'full_name' => (string) ($student['full_name'] ?? ''),
            ],
            'session'           => $session,
            'participants'      => $participants,
            'sessionNotes'      => $notes,
            'participationNote' => $participationNote,
            'canSeeNotes'       => true,
        ]);
    }
}
