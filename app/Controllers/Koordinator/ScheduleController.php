<?php

namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateInterval;
use DateTime;
use DateTimeZone;

class ScheduleController extends BaseController
{
    protected string $tz = 'Asia/Jakarta';

    public function __construct()
    {
        helper(['auth', 'permission']);
    }

    public function index()
    {
        if ($redir = $this->guardKoordinator()) {
            return $redir;
        }

        $filters = [
            'class_id'     => $this->request->getGet('class_id'),
            'student_id'   => $this->request->getGet('student_id'),
            'counselor_id' => $this->request->getGet('counselor_id'),
            'session_type' => $this->request->getGet('session_type'),
            'status'       => $this->request->getGet('status'),
            'start'        => $this->request->getGet('start'),
            'end'          => $this->request->getGet('end'),
        ];

        $queryString = http_build_query(array_filter($filters, static fn ($value) => $value !== null && $value !== ''));

        return view('counselor/schedule/index', [
            'title'         => 'Kalender Jadwal Konseling',
            'pageTitle'     => 'Kalender Jadwal Konseling',
            'calendarTitle' => 'Kalender Jadwal Konseling',
            'defaultView'   => 'dayGridMonth',
            'defaultDate'   => date('Y-m-d'),
            'canCreate'     => false,
            'canDrag'       => false,
            'filters'       => $filters,
            'eventsUrl'     => rtrim(base_url('koordinator/schedule/events'), '/') . ($queryString ? '?' . $queryString : ''),
            'reschUrl'      => '',
            'createUrl'     => '',
            'detailBase'    => rtrim(base_url('koordinator/sessions/detail'), '/'),
            'dashboardUrl'  => base_url('koordinator/dashboard'),
            'listUrl'       => base_url('koordinator/sessions'),
            'breadcrumbs'   => [
                ['title' => 'Dashboard', 'url' => base_url('koordinator/dashboard')],
                ['title' => 'Kalender Jadwal', 'url' => '#', 'active' => true],
            ],
        ]);
    }

    /**
     * FullCalendar event feed untuk seluruh sesi konseling semua Guru BK.
     */
    public function events(): ResponseInterface
    {
        if ($redir = $this->guardKoordinator()) {
            return $this->response->setStatusCode(403)->setJSON([]);
        }

        $start = substr((string) $this->request->getGet('start'), 0, 10);
        $end   = substr((string) $this->request->getGet('end'), 0, 10);

        if ($start === '' || $end === '') {
            return $this->response->setJSON([]);
        }

        $endInclusive = date('Y-m-d', strtotime($end . ' -1 day'));

        $builder = db_connect()->table('counseling_sessions cs')
            ->select('
                cs.id,
                cs.counselor_id,
                cs.student_id,
                cs.class_id,
                cs.session_type,
                cs.topic,
                cs.location,
                cs.status,
                cs.session_date,
                cs.session_time,
                cs.duration_minutes,
                su.full_name AS student_name,
                cu.full_name AS counselor_name,
                c.class_name,
                (
                    SELECT COUNT(*)
                    FROM session_participants sp
                    WHERE sp.session_id = cs.id
                      AND sp.deleted_at IS NULL
                      AND (sp.is_active = 1 OR sp.is_active IS NULL)
                ) AS participant_count
            ')
            ->join('students s', 's.id = cs.student_id AND s.deleted_at IS NULL', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('users cu', 'cu.id = cs.counselor_id AND cu.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = cs.class_id AND c.deleted_at IS NULL', 'left')
            ->where('cs.deleted_at', null)
            ->where('cs.session_date >=', $start)
            ->where('cs.session_date <=', $endInclusive);

        $classId = $this->request->getGet('class_id');
        if ($classId !== null && $classId !== '') {
            $builder->where('cs.class_id', (int) $classId);
        }

        $studentId = $this->request->getGet('student_id');
        if ($studentId !== null && $studentId !== '') {
            $builder->where('cs.student_id', (int) $studentId);
        }

        $counselorId = $this->request->getGet('counselor_id');
        if ($counselorId !== null && $counselorId !== '') {
            $builder->where('cs.counselor_id', (int) $counselorId);
        }

        $sessionType = $this->request->getGet('session_type');
        if ($sessionType !== null && $sessionType !== '') {
            $builder->where('cs.session_type', $sessionType);
        }

        $status = $this->request->getGet('status');
        if ($status !== null && $status !== '') {
            $builder->where('cs.status', $status);
        }

        $rows = $builder
            ->orderBy('cs.session_date', 'ASC')
            ->orderBy('cs.session_time', 'ASC')
            ->get()
            ->getResultArray();

        $events = [];
        $tz = new DateTimeZone($this->tz);

        foreach ($rows as $row) {
            $date = (string) ($row['session_date'] ?? date('Y-m-d'));
            $time = trim((string) ($row['session_time'] ?? ''));
            if ($time === '' || $time === '00:00:00') {
                $time = '08:00:00';
            }

            try {
                $startDt = new DateTime($date . ' ' . $time, $tz);
            } catch (\Throwable) {
                $startDt = new DateTime(date('Y-m-d') . ' 08:00:00', $tz);
            }

            $duration = (int) ($row['duration_minutes'] ?? 0);
            if ($duration <= 0) {
                $duration = 45;
            }

            $endDt = (clone $startDt)->add(new DateInterval('PT' . $duration . 'M'));
            [$background, $border] = $this->statusColor((string) ($row['status'] ?? ''));

            $title = $this->buildEventTitle($row);

            $events[] = [
                'id'              => (int) $row['id'],
                'title'           => $title,
                'start'           => $startDt->format(DateTime::ATOM),
                'end'             => $endDt->format(DateTime::ATOM),
                'backgroundColor' => $background,
                'borderColor'     => $border,
                'extendedProps'   => [
                    'session_id'        => (int) $row['id'],
                    'status'            => $row['status'] ?? '',
                    'location'          => $row['location'] ?? '',
                    'session_type'      => $row['session_type'] ?? '',
                    'student_id'        => (int) ($row['student_id'] ?? 0),
                    'student_name'      => $row['student_name'] ?? '',
                    'class_name'        => $row['class_name'] ?? '',
                    'counselor_name'    => $row['counselor_name'] ?? '',
                    'participant_count' => (int) ($row['participant_count'] ?? 0),
                ],
            ];
        }

        return $this->response->setJSON($events);
    }

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
     * @param array<string,mixed> $row
     */
    private function buildEventTitle(array $row): string
    {
        $parts = [];

        if (!empty($row['counselor_name'])) {
            $parts[] = (string) $row['counselor_name'];
        }

        $subject = trim((string) ($row['student_name'] ?? ''));
        if ($subject === '' && (int) ($row['participant_count'] ?? 0) > 0) {
            $subject = (int) $row['participant_count'] . ' peserta';
        }
        if ($subject === '' && !empty($row['class_name'])) {
            $subject = (string) $row['class_name'];
        }

        $topic = trim((string) ($row['topic'] ?? ''));
        $sessionType = trim((string) ($row['session_type'] ?? 'Sesi Konseling'));

        $summary = $topic !== '' ? $topic : $sessionType;
        if ($subject !== '') {
            $summary .= ' - ' . $subject;
        }

        $parts[] = $summary;

        return implode(' • ', array_filter($parts));
    }

    private function statusColor(string $status): array
    {
        return match (strtolower(trim($status))) {
            'dijadwalkan' => ['#2d8cf0', '#2d8cf0'],
            'selesai'     => ['#00a86b', '#00a86b'],
            'dibatalkan'  => ['#e53935', '#e53935'],
            'ditunda',
            'menunggu'    => ['#f7b924', '#f7b924'],
            'tidak hadir' => ['#8d6e63', '#8d6e63'],
            default       => ['#6c757d', '#6c757d'],
        };
    }
}
