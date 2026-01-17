<?php
// app/Controllers/Student/ScheduleController.php
namespace App\Controllers\Student;

use CodeIgniter\I18n\Time;

class ScheduleController extends BaseStudentController
{
    /**
     * Jadwal konseling siswa (yang akan datang / hari ini).
     *
     * Menampilkan:
     * - Sesi Individu milik siswa
     * - Sesi Klasikal yang menargetkan kelas siswa
     * - Sesi Kelompok yang mencantumkan siswa di session_participants
     *
     * Hanya sesi:
     * - Belum dihapus (deleted_at IS NULL)
     * - Tanggal >= hari ini
     * - Status bukan "Dibatalkan"
     */
    public function index()
    {
        $this->requireStudent();

        $today = Time::today('Asia/Jakarta')->toDateString();

        // Ambil class_id siswa
        $student = $this->db->table('students')
            ->select('class_id')
            ->where('id', $this->studentId)
            ->get()
            ->getRowArray();

        $classId = (int) ($student['class_id'] ?? 0);

        $builder = $this->db->table('counseling_sessions cs')
            ->select('cs.id, cs.session_type, cs.session_date, cs.session_time, cs.topic, cs.location, cs.status')
            ->join('session_participants sp', 'sp.session_id = cs.id', 'left')
            ->groupStart()
                // Sesi Individu milik siswa ini
                ->groupStart()
                    ->where('cs.session_type', 'Individu')
                    ->where('cs.student_id', $this->studentId)
                ->groupEnd()
                // Sesi Klasikal untuk kelas siswa ini
                ->orGroupStart()
                    ->where('cs.session_type', 'Klasikal')
                    ->where('cs.class_id', $classId)
                ->groupEnd()
                // Sesi Kelompok: siswa muncul di session_participants
                ->orGroupStart()
                    ->where('cs.session_type', 'Kelompok')
                    ->where('sp.student_id', $this->studentId)
                ->groupEnd()
            ->groupEnd()
            ->where('cs.deleted_at', null)
            ->where('cs.session_date >=', $today)
            ->where('cs.status !=', 'Dibatalkan')
            ->groupBy('cs.id')
            ->orderBy('cs.session_date', 'ASC')
            ->orderBy('cs.session_time', 'ASC');

        $sessions = $builder->get()->getResultArray();

        return view('student/schedule/index', [
            'title'            => 'Sesi Konseling',
            'sessions' => $sessions,
            'today'    => $today,
        ]);
    }

    /**
     * Halaman form pengajuan sesi konseling.
     * Siswa TIDAK memilih Guru BK; otomatis diisi Guru BK kelasnya.
     */
    public function requestForm()
    {
        $this->requireStudent();

        $info = $this->getStudentClassAndCounselor();

        return view('student/schedule/request', [
            // Untuk ditampilkan sebagai informasi saja di view (bukan dipilih)
            'defaultCounselor' => $info['counselor_id'] ?? null,
            'classId'          => $info['class_id'] ?? null,
            'today'            => Time::today('Asia/Jakarta')->toDateString(),
        ]);
    }

    /**
     * Simpan pengajuan sesi (via form biasa).
     * Route: POST /student/schedule/request
     *
     * Siswa tidak mengirim counselor_id; sistem otomatis pakai Guru BK kelas.
     */
    public function storeRequest()
    {
        $this->requireStudent();

        // Validasi input dasar (tanpa counselor_id)
        $rules = [
            'session_date' => 'required|valid_date',
            'session_time' => 'permit_empty',
            'topic'        => 'required|max_length[255]',
            'description'  => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Periksa kembali data yang diisi.')
                ->with('errors', $this->validator->getErrors());
        }

        try {
            // Ambil informasi kelas & Guru BK kelas
            $info = $this->getStudentClassAndCounselor();

            $classId     = (int) ($info['class_id'] ?? 0);
            $counselorId = (int) ($info['counselor_id'] ?? 0);

            // Wajib punya kelas & Guru BK untuk mengajukan konseling
            if ($classId <= 0 || $counselorId <= 0) {
                return redirect()->back()
                    ->withInput()
                    ->with(
                        'error',
                        'Kelas Anda belum dikaitkan dengan Guru BK. Mohon hubungi wali kelas atau admin sekolah.'
                    );
            }

            // Bangun payload standar (selalu menggunakan Guru BK kelas siswa)
            $payload = $this->buildRequestPayload($counselorId, $classId);

            // Cek duplikat jadwal dengan Guru BK yang sama
            $dupMsg = $this->detectDuplicate(
                $payload['session_date'],
                $payload['session_time'],
                $counselorId
            );

            if ($dupMsg !== null) {
                return redirect()->back()
                    ->with('error', $dupMsg)
                    ->withInput();
            }

            // Simpan ke tabel counseling_sessions
            $this->db->table('counseling_sessions')->insert($payload);

            return redirect()->to(route_to('student.schedule'))
                ->with('success', 'Permintaan sesi konseling terkirim ke Guru BK kelas Anda.');
        } catch (\Throwable $e) {
            log_message('error', 'Error submitting counseling request: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Terjadi kesalahan saat menyimpan permintaan. Silakan coba lagi atau hubungi Guru BK.'
                );
        }
    }

    /**
     * Simpan pengajuan sesi (AJAX).
     * Route: POST /student/schedule/submit-request
     *
     * Frontend cukup kirim tanggal, waktu (opsional), topik, deskripsi.
     * Guru BK akan otomatis diambil dari kelas siswa.
     */
    public function submitRequest()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setJSON([
                'status'  => 405,
                'message' => 'Metode tidak diizinkan.',
            ]);
        }

        $this->requireStudent();

        $rules = [
            'session_date' => 'required|valid_date',
            'session_time' => 'permit_empty',
            'topic'        => 'required|max_length[255]',
            'description'  => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 422,
                    'message' => 'Periksa kembali data yang diisi.',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        try {
            $info = $this->getStudentClassAndCounselor();

            $classId     = (int) ($info['class_id'] ?? 0);
            $counselorId = (int) ($info['counselor_id'] ?? 0);

            if ($classId <= 0 || $counselorId <= 0) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status'  => 422,
                        'message' => 'Kelas Anda belum dikaitkan dengan Guru BK. Mohon hubungi wali kelas atau admin sekolah.',
                    ]);
            }

            $payload = $this->buildRequestPayload($counselorId, $classId);

            $dupMsg = $this->detectDuplicate(
                $payload['session_date'],
                $payload['session_time'],
                $counselorId
            );

            if ($dupMsg !== null) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => 409,
                        'message' => $dupMsg,
                    ]);
            }

            $this->db->table('counseling_sessions')->insert($payload);

            return $this->response->setJSON([
                'status'   => 200,
                'message'  => 'Permintaan sesi konseling terkirim ke Guru BK kelas Anda.',
                'redirect' => route_to('student.schedule'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error submitting counseling request (AJAX): ' . $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 500,
                    'message' => 'Terjadi kesalahan pada server. Silakan coba lagi atau hubungi Guru BK.',
                ]);
        }
    }

    /**
     * Riwayat sesi (lampau atau selesai/dibatalkan) milik siswa.
     * Route: GET /student/schedule/history
     *
     * Menampilkan:
     * - Individu milik siswa
     * - Klasikal berdasarkan class_id siswa
     * - Kelompok berdasarkan session_participants.student_id
     */
    public function history()
    {
        $this->requireStudent();

        $today = Time::today('Asia/Jakarta')->toDateString();

        $student = $this->db->table('students')
            ->select('class_id')
            ->where('id', $this->studentId)
            ->get()
            ->getRowArray();

        $classId = (int) ($student['class_id'] ?? 0);

        $builder = $this->db->table('counseling_sessions cs')
            ->select('cs.id, cs.session_type, cs.session_date, cs.session_time, cs.topic, cs.location, cs.status')
            ->join('session_participants sp', 'sp.session_id = cs.id', 'left')
            ->groupStart()
                // Individu
                ->groupStart()
                    ->where('cs.session_type', 'Individu')
                    ->where('cs.student_id', $this->studentId)
                ->groupEnd()
                // Klasikal
                ->orGroupStart()
                    ->where('cs.session_type', 'Klasikal')
                    ->where('cs.class_id', $classId)
                ->groupEnd()
                // Kelompok
                ->orGroupStart()
                    ->where('cs.session_type', 'Kelompok')
                    ->where('sp.student_id', $this->studentId)
                ->groupEnd()
            ->groupEnd()
            ->where('cs.deleted_at', null)
            ->groupStart() // kriteria "riwayat"
                ->where('cs.session_date <', $today)
                ->orWhereIn('cs.status', ['Selesai', 'Dibatalkan', 'Tidak Hadir'])
            ->groupEnd()
            ->groupBy('cs.id')
            ->orderBy('cs.session_date', 'DESC')
            ->orderBy('cs.session_time', 'DESC');

        $history = $builder->get()->getResultArray();

        return view('student/schedule/history', [
            'title'   => 'Riwayat Sesi Konseling',
            'history' => $history,
            'today'   => $today,
        ]);
    }

    /**
     * Detail sesi konseling untuk siswa.
     * Route yang diharapkan: GET /student/schedule/detail/(:num)
     *
     * Aturan akses:
     * - HANYA jika:
     *   - Individu: cs.student_id = siswa
     *   - Klasikal: cs.class_id = kelas siswa
     *   - Kelompok: siswa tercatat di session_participants
     * - TIDAK boleh diakses jika cs.is_confidential = 1 (sesi rahasia)
     * - Tabel session_notes: hanya ditampilkan yang is_confidential = 0
     */
    public function detail($id)
    {
        $this->requireStudent();

        $sessionId = (int) $id;
        if ($sessionId <= 0) {
            return redirect()->to(route_to('student.schedule'))
                ->with('error', 'Sesi konseling tidak ditemukan.');
        }

        // Ambil info kelas & konselor (sekalian reuse helper)
        $info    = $this->getStudentClassAndCounselor();
        $classId = (int) ($info['class_id'] ?? 0);

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

        if (! $session) {
            return redirect()->to(route_to('student.schedule'))
                ->with('error', 'Sesi konseling tidak ditemukan.');
        }

        // Jika sesi rahasia, siswa TIDAK boleh melihat detail sama sekali
        if (! empty($session['is_confidential'])) {
            return redirect()->to(route_to('student.schedule'))
                ->with('error', 'Sesi konseling ini bersifat rahasia dan tidak dapat diakses.');
        }

        // Cek hak akses siswa terhadap sesi ini
        $allowed = false;
        $type    = (string) ($session['session_type'] ?? '');

        if ($type === 'Individu') {
            // Sesi individu: hanya siswa pemilik
            if ((int) ($session['student_id'] ?? 0) === (int) $this->studentId) {
                $allowed = true;
            }
        } elseif ($type === 'Klasikal') {
            // Sesi klasikal: berdasarkan kelas
            if ($classId > 0 && (int) ($session['class_id'] ?? 0) === $classId) {
                $allowed = true;
            }
        } elseif ($type === 'Kelompok') {
            // Sesi kelompok: cek di session_participants
            $count = $this->db->table('session_participants')
                ->where('session_id', $sessionId)
                ->where('student_id', $this->studentId)
                ->where('deleted_at', null)
                ->countAllResults();

            if ($count > 0) {
                $allowed = true;
            }
        }

        if (! $allowed) {
            return redirect()->to(route_to('student.schedule'))
                ->with('error', 'Anda tidak memiliki akses ke sesi konseling ini.');
        }

        // Ambil daftar peserta untuk tipe Kelompok/Klasikal (informasi umum saja)
        $participants = [];
        if (in_array($type, ['Kelompok', 'Klasikal'], true)) {
            $participants = $this->db->table('session_participants sp')
                ->select('
                    sp.student_id,
                    sp.attendance_status,
                    sp.participation_note,
                    s.nisn,
                    s.nis,
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

        // Catatan partisipasi siswa ini (hanya relevan untuk Kelompok/Klasikal)
        $participationNote = null;
        if (in_array($type, ['Kelompok', 'Klasikal'], true)) {
            $ownRow = $this->db->table('session_participants')
                ->select('participation_note')
                ->where('session_id', $sessionId)
                ->where('student_id', $this->studentId)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (!empty($ownRow['participation_note'])) {
                $participationNote = $ownRow['participation_note'];
            }
        }

        // Ambil catatan sesi yang boleh dilihat siswa:
        // - hanya yang tidak rahasia (is_confidential = 0)
        // - belum dihapus
        $notes = $this->db->table('session_notes sn')
            ->select('
                sn.id,
                sn.session_id,
                sn.note_type,
                sn.note_content,
                sn.is_important,
                sn.attachments,
                sn.created_at,
                u.full_name AS counselor_name
            ')
            ->join('users u', 'u.id = sn.created_by', 'left')
            ->where('sn.session_id', $sessionId)
            ->where('sn.is_confidential', 0)
            ->where('sn.deleted_at', null)
            ->orderBy('sn.created_at', 'DESC')
            ->get()
            ->getResultArray();

        // Pada titik ini sesi sudah dipastikan tidak rahasia,
        // dan note yang diambil hanya yang is_confidential = 0,
        // jadi siswa boleh melihat catatan.
        $canSeeNotes = true;

        return view('student/schedule/detail', [
            'title'             => 'Detail Sesi Konseling',
            'session'           => $session,
            'participants'      => $participants,
            'sessionNotes'      => $notes,
            'participationNote' => $participationNote,
            'canSeeNotes'       => $canSeeNotes,
        ]);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Ambil class_id dan counselor_id dari kelas siswa.
     * @return array{class_id?:int|null,counselor_id?:int|null}
     */
    private function getStudentClassAndCounselor(): array
    {
        $row = $this->db->table('students s')
            ->select('c.id as class_id, c.counselor_id')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', $this->studentId)
            ->get()
            ->getRowArray();

        return $row ?: [];
    }

    /**
     * Bangun payload standar untuk pengajuan sesi individu oleh siswa.
     */
    private function buildRequestPayload(int $counselorId, int $classId): array
    {
        $date = (string) $this->request->getPost('session_date');
        $time = trim((string) $this->request->getPost('session_time'));
        $time = $time !== '' ? $time : null; // optional

        $now = Time::now('Asia/Jakarta')->toDateTimeString();

        return [
            'student_id'          => $this->studentId,
            'counselor_id'        => $counselorId,
            'class_id'            => $classId ?: null,
            'session_type'        => 'Individu',
            'session_date'        => $date,
            'session_time'        => $time,
            'location'            => 'Ruang BK',
            'topic'               => $this->request->getPost('topic'),
            'problem_description' => $this->request->getPost('description') ?: null,
            // Status awal: "Dijadwalkan" agar konsisten dengan modul lain
            'status'              => 'Dijadwalkan',
            // Pengajuan siswa default dirahasiakan (bisa diubah oleh BK jika perlu)
            'is_confidential'     => 1,
            // 'requested_by'        => 'student',   // jejak audit (opsional)
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
    }

    /**
     * Cegah duplikasi jadwal:
     * - Siswa tidak boleh punya dua sesi di tanggal & jam yang sama
     * - Opsional: hindari tabrakan konselor pada slot yang sama
     *
     * Diabaikan jika sesi sudah dibatalkan atau dihapus.
     *
     * @return string|null Pesan error bila ada duplikasi, atau null jika aman.
     */
    private function detectDuplicate(string $date, ?string $time, int $counselorId): ?string
    {
        // Cek duplikasi untuk siswa (hanya sesi aktif, bukan yang dibatalkan / dihapus)
        $qb = $this->db->table('counseling_sessions')
            ->where('student_id', $this->studentId)
            ->where('session_date', $date)
            ->where('deleted_at', null)
            ->where('status !=', 'Dibatalkan');

        if (! empty($time)) {
            $qb->where('session_time', $time);
        }

        $dupStudent = $qb->countAllResults();
        if ($dupStudent > 0) {
            return 'Anda sudah memiliki pengajuan/jadwal pada tanggal dan waktu tersebut.';
        }

        // Opsional: cek tabrakan konselor (jika waktu diisi)
        if (! empty($time) && $counselorId > 0) {
            $dupCounselor = $this->db->table('counseling_sessions')
                ->where('counselor_id', $counselorId)
                ->where('session_date', $date)
                ->where('session_time', $time)
                ->where('deleted_at', null)
                ->where('status !=', 'Dibatalkan')
                ->countAllResults();

            if ($dupCounselor > 0) {
                return 'Slot waktu tersebut sudah dipakai oleh konselor. Pilih waktu lain.';
            }
        }

        return null;
    }
}
