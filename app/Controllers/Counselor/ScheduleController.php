<?php

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Models\CounselingSessionModel;
use CodeIgniter\HTTP\ResponseInterface;
use DateInterval;
use DateTime;
use DateTimeZone;

class ScheduleController extends BaseController
{
    protected CounselingSessionModel $sessionModel;
    protected string $tz = 'Asia/Jakarta';

    public function __construct()
    {
        $this->sessionModel = new CounselingSessionModel();
    }

    public function index()
    {
        if (!is_logged_in()) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if (!is_guru_bk() && !is_koordinator()) return redirect()->to('/')->with('error', 'Akses ditolak');

        return view('counselor/schedule/index', [
            'title'       => 'Kalender Jadwal',
            'pageTitle'   => 'Kalender ',
            // preferensi kalender (opsional)
            'defaultView' => 'dayGridMonth',
            'defaultDate' => date('Y-m-d'),
            'canDrag'     => true,
            'filters'     => [
                'class_id'   => $this->request->getGet('class_id'),
                'student_id' => $this->request->getGet('student_id'),
                'status'     => $this->request->getGet('status'),
                'start'      => $this->request->getGet('start'),
                'end'        => $this->request->getGet('end'),
            ],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => base_url('counselor/dashboard')],
                ['title' => 'Jadwal', 'url' => '#', 'active' => true],
            ],
        ]);
    }

    

    /**
     * FullCalendar event feed (GET /counselor/schedule/events?start=YYYY-MM-DD&end=YYYY-MM-DD)
     */
    public function events(): ResponseInterface
    {
        if (!is_logged_in()) return $this->response->setJSON([]);
        if (!is_guru_bk() && !is_koordinator()) return $this->response->setJSON([]);

        $start = $this->request->getGet('start'); // FullCalendar kirim UTC date (harian), inclusive-exclusive
        $end   = $this->request->getGet('end');
        if (!$start || !$end) return $this->response->setJSON([]);

        // Karena field kamu: session_date (DATE), session_time (TIME/STRING), duration_minutes (INT)
        // Kita ambil range berdasarkan session_date
        $builder = db_connect()->table('counseling_sessions cs')
            ->select('cs.id, cs.counselor_id, cs.student_id, cs.class_id, cs.topic, cs.location, cs.status, cs.session_date, cs.session_time, cs.duration_minutes,
                      u.full_name AS student_name, c.class_name')
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users u',    'u.id = s.user_id',     'left')
            ->join('classes c',  'c.id = cs.class_id',   'left');

        // Batasi hanya sesi milik counselor ini (kecuali Koordinator)
        if (!is_koordinator()) {
            $builder->where('cs.counselor_id', (int) auth_id());
        }

        // FullCalendar end itu exclusive; untuk amannya kita include sampai end-1 hari
        $endInclusive = date('Y-m-d', strtotime($end . ' -1 day'));
        $builder->where('cs.session_date >=', $start)
                ->where('cs.session_date <=', $endInclusive);

        // Filter opsional
        $classId   = $this->request->getGet('class_id');
        $studentId = $this->request->getGet('student_id');
        $status    = $this->request->getGet('status');
        if ($classId)   $builder->where('cs.class_id', (int)$classId);
        if ($studentId) $builder->where('cs.student_id', (int)$studentId);
        if ($status !== null && $status !== '') $builder->where('cs.status', $status);

        $rows = $builder->get()->getResultArray();

        $events = [];
        $tz = new DateTimeZone($this->tz);

        foreach ($rows as $r) {
            $titleParts = [];
            if (!empty($r['student_name'])) $titleParts[] = $r['student_name'];
            if (!empty($r['topic']))        $titleParts[] = $r['topic'];
            if (!empty($r['class_name']))   $titleParts[] = '(' . $r['class_name'] . ')';
            $title = $titleParts ? implode(' • ', $titleParts) : 'Sesi Konseling';

            // Bangun start & end dari session_date + session_time + duration
            $dateStr = $r['session_date'] ?? date('Y-m-d');
            $timeStr = trim((string)($r['session_time'] ?? ''));
            if ($timeStr === '' || $timeStr === '00:00:00') $timeStr = '08:00:00'; // default jam 08.00

            try {
                $startDt = new DateTime($dateStr . ' ' . $timeStr, $tz);
            } catch (\Throwable) {
                $startDt = new DateTime(date('Y-m-d') . ' 08:00:00', $tz);
            }

            $dur = (int)($r['duration_minutes'] ?? 0);
            if ($dur <= 0) $dur = 45; // default 45 menit
            $endDt = (clone $startDt)->add(new DateInterval('PT' . $dur . 'M'));

            [$bg, $bd] = $this->statusColor($r['status'] ?? '');

            $events[] = [
                'id'              => (int)$r['id'],
                'title'           => $title,
                'start'           => $startDt->format(DateTime::ATOM),
                'end'             => $endDt->format(DateTime::ATOM),
                'backgroundColor' => $bg,
                'borderColor'     => $bd,
                'extendedProps'   => [
                    'session_id'   => (int)$r['id'],
                    'status'       => $r['status'] ?? '',
                    'location'     => $r['location'] ?? '',
                    'student_id'   => (int)($r['student_id'] ?? 0),
                    'student_name' => $r['student_name'] ?? '',
                    'class_name'   => $r['class_name'] ?? '',
                ],
            ];
        }

        return $this->response->setJSON($events);
    }

    /**
     * Drag/drop & resize handler (POST JSON: {id, start, end})
     */
    public function reschedule(): ResponseInterface
    {
        if (!is_logged_in()) return $this->jsonFail('Unauthorized', 401);
        if (!is_guru_bk() && !is_koordinator()) return $this->jsonFail('Forbidden', 403);

        $payload = $this->request->getJSON(true);
        $id    = (int)($payload['id'] ?? 0);
        $start = $payload['start'] ?? null; // ISO
        $end   = $payload['end']   ?? null; // ISO

        if ($id <= 0 || !$start) return $this->jsonFail('Data tidak lengkap', 422);

        // Pastikan user berhak ubah
        $row = $this->sessionModel->asArray()->find($id);
        if (!$row) return $this->jsonFail('Sesi tidak ditemukan', 404);
        if (!is_koordinator() && (int)($row['counselor_id'] ?? 0) !== (int)auth_id()) {
            return $this->jsonFail('Anda tidak memiliki akses', 403);
        }

        // Hitung nilai baru (session_date, session_time, duration_minutes)
        try {
            $tz = new DateTimeZone($this->tz);
            $s  = new DateTime($start);
            $s->setTimezone($tz);

            $upd = [
                'session_date' => $s->format('Y-m-d'),
                'session_time' => $s->format('H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            if ($end) {
                $e = new DateTime($end);
                $e->setTimezone($tz);
                $dur = max(15, (int) round(($e->getTimestamp() - $s->getTimestamp()) / 60)); // min 15 menit
                $upd['duration_minutes'] = $dur;
            }

            $this->sessionModel->update($id, $upd);
            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Reschedule error: ' . $e->getMessage());
            return $this->jsonFail('Gagal memperbarui jadwal', 500);
        }
    }

    // ===== Helpers =====

    protected function statusColor(string $status): array
    {
        $s = strtolower(trim($status));
        // mapping status versi Indonesia
        return match ($s) {
            'dijadwalkan' => ['#2d8cf0', '#2d8cf0'], // biru
            'selesai'     => ['#00c853', '#00c853'], // hijau
            'dibatalkan'  => ['#e53935', '#e53935'], // merah
            'menunggu'    => ['#f7b924', '#f7b924'], // kuning
            default       => ['#6c757d', '#6c757d'], // abu
        };
    }

    protected function jsonFail(string $message, int $status): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['success' => false, 'message' => $message]);
    }
}


