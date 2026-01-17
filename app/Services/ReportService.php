<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class ReportService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * Ubah kolom biner (0/1, true/false) menjadi label teks
     * supaya lebih ramah dibaca di laporan.
     */
    protected function mapBooleanLabel(array $rows, string $field, string $trueLabel, string $falseLabel): array
    {
        foreach ($rows as &$row) {
            if (!is_array($row) || !array_key_exists($field, $row)) {
                continue;
            }

            $value = $row[$field];

            // Normalisasi ke integer 0/1
            if (is_bool($value)) {
                $intVal = $value ? 1 : 0;
            } else {
                $intVal = (int) $value;
            }

            $row[$field] = $intVal === 1 ? $trueLabel : $falseLabel;
        }
        unset($row);

        return $rows;
    }

    // -----------------------------
    // Util
    // -----------------------------
    protected function applyDate(&$builder, ?string $from, ?string $to, string $field = 'date'): void
    {
        if ($from) {
            $builder->where("$field >=", $from);
        }
        if ($to) {
            $builder->where("$field <=", $to);
        }
    }

    protected function school(): array
    {
        helper('settings');

        return [
            'name'    => setting('school_name',   env('school.name', 'Nama Sekolah'),   'general'),
            'address' => setting('address',       env('school.address', ''),            'general'),
            'phone'   => setting('contact_phone', env('school.phone', ''),              'general'),
            'email'   => setting('contact_email', env('school.email', ''),              'general'),
            'website' => setting('website',       env('school.website', ''),            'general'),
            'logo'    => base_url(setting('logo_path', 'assets/images/logo.png', 'branding')),
        ];
    }

    /** Whitelist kolom sort untuk cegah SQL injection */
    protected function applySort($builder, string $sortBy, string $sortDir, array $whitelist): void
    {
        $sortBy  = in_array($sortBy, $whitelist, true) ? $sortBy : $whitelist[0];
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $builder->orderBy($sortBy, $sortDir);
    }

    /** Ambil ID siswa binaan dari counselor (melalui classes.counselor_id) */
    public function counselorStudentIds(int $counselorId, ?int $classId = null): array
    {
        $b = $this->db->table('students s')
            ->select('s.id')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('c.counselor_id', $counselorId)
            ->where('s.deleted_at', null);

        if ($classId) {
            $b->where('s.class_id', $classId);
        }

        return array_map('intval', array_column($b->get()->getResultArray(), 'id'));
    }

    // -----------------------------
    // Sumber Data Laporan (untuk View/Export)
    // -----------------------------

    /** Laporan Individu Siswa */
    public function studentIndividual(int $studentId, ?string $from = null, ?string $to = null): array
    {
        $student = $this->db->table('students s')
            ->select('s.*, u.full_name as full_name, c.class_name, c.id as class_id')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', $studentId)
            ->where('s.deleted_at', null)
            ->get()->getRowArray();

        $sess = $this->db->table('counseling_sessions cs')
            ->select('cs.*, u.full_name as counselor_name')
            ->join('users u', 'u.id = cs.counselor_id', 'left')
            ->where('cs.student_id', $studentId)
            ->where('cs.deleted_at', null);

        $this->applyDate($sess, $from, $to, 'cs.session_date');

        $sessions = $sess->orderBy('cs.session_date', 'ASC')
            ->orderBy('cs.session_time', 'ASC')
            ->get()->getResultArray();

        $vio = $this->db->table('violations v')
            ->select('v.*, vc.category_name, vc.severity_level, vc.point_deduction as points')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.student_id', $studentId)
            ->where('v.deleted_at', null);

        $this->applyDate($vio, $from, $to, 'v.violation_date');

        $violations = $vio->orderBy('v.violation_date', 'ASC')->get()->getResultArray();

        $totalSessions    = count($sessions);
        $totalViolations  = count($violations);
        $totalPoints      = array_sum(array_map(static fn ($x) => (int) ($x['points'] ?? 0), $violations));

        return [
            'school'          => $this->school(),
            'student'         => $student,
            'period'          => ['from' => $from, 'to' => $to],
            'sessions'        => $sessions,
            'violations'      => $violations,
            'totalSessions'   => $totalSessions,
            'totalViolations' => $totalViolations,
            'totalPoints'     => $totalPoints,
        ];
    }

    /** Ringkasan Sesi Konseling (global/filter) */
    public function sessionSummary(?string $from, ?string $to, ?int $counselorId = null, ?int $classId = null): array
    {
        $b = $this->db->table('counseling_sessions cs')
            ->select('cs.*, su.full_name as student_name, c.class_name, cu.full_name as counselor_name')
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('users cu', 'cu.id = cs.counselor_id', 'left')
            ->where('cs.deleted_at', null);

        $this->applyDate($b, $from, $to, 'cs.session_date');
        if ($counselorId) {
            $b->where('cs.counselor_id', $counselorId);
        }
        if ($classId) {
            $b->where('c.id', $classId);
        }

        $rows = $b->orderBy('cs.session_date', 'ASC')
            ->orderBy('cs.session_time', 'ASC')
            ->get()->getResultArray();

        $perCounselor = [];
        foreach ($rows as $r) {
            $key = (string) ($r['counselor_id'] ?? 0);
            $perCounselor[$key]['counselor_name'] = $r['counselor_name'] ?? '-';
            $perCounselor[$key]['count']    = ($perCounselor[$key]['count'] ?? 0) + 1;
            $perCounselor[$key]['duration'] = ($perCounselor[$key]['duration'] ?? 0) + (int) ($r['duration_minutes'] ?? 0);
        }

        return [
            'school'        => $this->school(),
            'period'        => ['from' => $from, 'to' => $to],
            'rows'          => $rows,
            'perCounselor'  => $perCounselor,
            'total'         => count($rows),
            'totalDuration' => array_sum(array_column($perCounselor, 'duration')),
        ];
    }

    /** Laporan Pelanggaran (rekap) */
    public function violationReport(?string $from, ?string $to, ?int $classId = null, ?int $categoryId = null): array
    {
        $b = $this->db->table('violations v')
            ->select('v.*, su.full_name as student_name, c.class_name, vc.category_name, vc.severity_level, vc.point_deduction as points')
            ->join('students s', 's.id = v.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.deleted_at', null);

        $this->applyDate($b, $from, $to, 'v.violation_date');
        if ($classId) {
            $b->where('c.id', $classId);
        }
        if ($categoryId) {
            $b->where('vc.id', $categoryId);
        }

        $rows = $b->orderBy('v.violation_date', 'ASC')->get()->getResultArray();

        $totalPoints = array_sum(array_map(static fn ($x) => (int) ($x['points'] ?? 0), $rows));
        $perCategory = [];
        foreach ($rows as $r) {
            $cat = $r['category_name'] ?? 'Tidak diketahui';
            $perCategory[$cat]['count']  = ($perCategory[$cat]['count'] ?? 0) + 1;
            $perCategory[$cat]['points'] = ($perCategory[$cat]['points'] ?? 0) + (int) ($r['points'] ?? 0);
        }

        return [
            'school'      => $this->school(),
            'period'      => ['from' => $from, 'to' => $to],
            'rows'        => $rows,
            'total'       => count($rows),
            'totalPoints' => $totalPoints,
            'perCategory' => $perCategory,
        ];
    }

    /** Agregat per Kelas (sesi & pelanggaran) */
    public function classAggregate(int $classId, ?string $from, ?string $to): array
    {
        $class = $this->db->table('classes')
            ->where('id', $classId)->where('deleted_at', null)
            ->get()->getRowArray();

        $students = $this->db->table('students s')
            ->select('s.*, u.full_name as full_name, c.class_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.class_id', $classId)
            ->where('s.deleted_at', null)
            ->get()->getResultArray();

        $studentIds = array_map(static fn ($x) => (int) $x['id'], $students);

        $sess = $this->db->table('counseling_sessions')
            ->where('deleted_at', null);
        if ($studentIds) {
            $sess->whereIn('student_id', $studentIds);
        }
        $this->applyDate($sess, $from, $to, 'session_date');
        $sessions = $sess->get()->getResultArray();

        $vio = $this->db->table('violations v')
            ->select('v.*, vc.category_name, vc.point_deduction as points')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->where('v.deleted_at', null);
        if ($studentIds) {
            $vio->whereIn('v.student_id', $studentIds);
        }
        $this->applyDate($vio, $from, $to, 'v.violation_date');
        $violations = $vio->get()->getResultArray();

        $perCategory = [];
        foreach ($violations as $v) {
            $cat = $v['category_name'] ?? 'Tidak diketahui';
            $perCategory[$cat] = ($perCategory[$cat] ?? 0) + 1;
        }

        return [
            'school'         => $this->school(),
            'class'          => $class,
            'period'         => ['from' => $from, 'to' => $to],
            'studentCount'   => count($students),
            'sessionCount'   => count($sessions),
            'violationCount' => count($violations),
            'perCategory'    => $perCategory,
            'students'       => $students,
            'sessions'       => $sessions,
            'violations'     => $violations,
        ];
    }

    /** Laporan Data Siswa (binaan counselor) */
    public function students(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (!$ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('students s')
            ->select('s.nisn, s.nis, u.full_name as full_name, s.gender, s.birth_date, s.status, c.class_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->whereIn('s.id', $ids)
            ->where('s.deleted_at', null);

        if (!empty($filter['status'])) {
            $b->where('s.status', $filter['status']);
        }
        if (!empty($filter['search'])) {
            $b->groupStart()
                ->like('u.full_name', $filter['search'])
                ->orLike('s.nisn', $filter['search'])
                ->orLike('s.nis', $filter['search'])
              ->groupEnd();
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'u.full_name',
            $filter['sort_dir'] ?? 'asc',
            ['u.full_name', 's.nisn', 's.nis', 'c.class_name', 's.status']
        );

        return [
            'columns' => ['NISN', 'NIS', 'Nama', 'JK', 'Tgl Lahir', 'Status', 'Kelas'],
            'rows'    => $b->get()->getResultArray(),
        ];
    }

    /** Laporan Sesi Konseling */
    public function sessions(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);

        $b = $this->db->table('counseling_sessions cs')
            ->select(
                'cs.id as session_id, cs.session_date, cs.session_time, cs.session_type, ' .
                'cs.location, cs.topic, cs.status, cs.duration_minutes, ' .
                'su.full_name as student, cstu.class_name as student_class, ' .
                'ctar.class_name as target_class'
            )
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes cstu', 'cstu.id = s.class_id', 'left')
            ->join('classes ctar', 'ctar.id = cs.class_id', 'left')
            ->where('cs.counselor_id', $counselorId)
            ->where('cs.deleted_at', null);

        if ($ids) {
            $b->groupStart()
                ->whereIn('cs.student_id', $ids)
                ->orWhere('cs.session_type !=', 'Individu')
              ->groupEnd();
        } else {
            $b->where('cs.session_type !=', 'Individu');
        }

        if (!empty($filter['date_from'])) {
            $b->where('cs.session_date >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('cs.session_date <=', $filter['date_to']);
        }
        if (!empty($filter['status'])) {
            $b->where('cs.status', $filter['status']);
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'cs.session_date',
            $filter['sort_dir'] ?? 'desc',
            ['cs.session_date', 'cs.session_type', 'cs.status', 'su.full_name']
        );

        $rows = $b->get()->getResultArray();

        $sessionIds = array_map(static fn ($r) => (int) ($r['session_id'] ?? 0), $rows);
        $participantsBySession = [];
        if (!empty($sessionIds)) {
            $p = $this->db->table('session_participants sp')
                ->select('sp.session_id, uu.full_name as student_name, cc.class_name')
                ->join('students st', 'st.id = sp.student_id', 'left')
                ->join('users uu', 'uu.id = st.user_id AND uu.deleted_at IS NULL', 'left')
                ->join('classes cc', 'cc.id = st.class_id', 'left')
                ->whereIn('sp.session_id', array_unique($sessionIds))
                ->where('sp.deleted_at', null)
                ->orderBy('uu.full_name', 'ASC')
                ->get()->getResultArray();

            foreach ($p as $row) {
                $sid = (int) ($row['session_id'] ?? 0);
                $participantsBySession[$sid][] = trim(($row['student_name'] ?? '-') . ' (' . ($row['class_name'] ?? '-') . ')');
            }
        }

        $final = [];
        foreach ($rows as $r) {
            $label = '-';
            $type  = $r['session_type'] ?? 'Individu';

            if ($type === 'Individu') {
                $name  = $r['student'] ?? '-';
                $kelas = $r['student_class'] ?? '-';
                $label = trim($name . ' (' . $kelas . ')');
            } elseif ($type === 'Kelompok') {
                $sid   = (int) ($r['session_id'] ?? 0);
                $list  = $participantsBySession[$sid] ?? [];
                $label = $list ? implode(', ', $list) : '-';
            } else {
                $label = $r['target_class'] ?? '-';
            }

            $final[] = [
                'session_date'     => $r['session_date'] ?? null,
                'session_time'     => $r['session_time'] ?? null,
                'session_type'     => $type,
                'location'         => $r['location'] ?? null,
                'topic'            => $r['topic'] ?? null,
                'student'          => $label,
                'status'           => $r['status'] ?? null,
                'duration_minutes' => $r['duration_minutes'] ?? null,
            ];
        }

        return [
            'columns' => ['Tanggal', 'Waktu', 'Jenis', 'Lokasi', 'Topik', 'Siswa/Kelas', 'Status', 'Durasi (m)'],
            'rows'    => $final,
        ];
    }

    /** Laporan Pelanggaran (detail baris) */
    public function violations(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (!$ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('violations v')
            ->select(
                'v.violation_date, v.violation_time, ' .
                'vc.category_name as kategori, ' .
                'su.full_name as student, c.class_name as class_name, ' .
                'v.location, v.status, v.is_repeat_offender, ' .
                'vc.point_deduction as points'
            )
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->join('students s', 's.id = v.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->whereIn('v.student_id', $ids)
            ->where('v.deleted_at', null);

        if (!empty($filter['date_from'])) {
            $b->where('v.violation_date >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('v.violation_date <=', $filter['date_to']);
        }
        if (!empty($filter['status'])) {
            $b->where('v.status', $filter['status']);
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'v.violation_date',
            $filter['sort_dir'] ?? 'desc',
            ['v.violation_date', 'v.status', 'vc.category_name', 'su.full_name', 'c.class_name', 'vc.point_deduction']
        );

        $rows = $b->get()->getResultArray();
        $rows = $this->mapBooleanLabel($rows, 'is_repeat_offender', 'Ya', 'Tidak');

        return [
            'columns' => ['Tanggal', 'Waktu', 'Kategori', 'Siswa', 'Kelas', 'Lokasi', 'Status', 'Berulang?', 'Poin'],
            'rows'    => $rows,
        ];
    }

    /** Laporan Asesmen */
    public function assessments(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (!$ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('assessment_results ar')
            ->select(
                'a.title, a.assessment_type, a.target_audience, a.target_class_id, a.target_grade, ' .
                'tc.class_name as target_class_name, ' .
                'su.full_name as student, c.class_name as class_name, ' .
                'ar.status, ar.percentage, ar.is_passed, ar.started_at, ar.completed_at'
            )
            ->join('assessments a', 'a.id = ar.assessment_id', 'left')
            ->join('students s', 's.id = ar.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('classes tc', 'tc.id = a.target_class_id', 'left')
            ->where('ar.deleted_at', null)
            ->where('a.deleted_at', null)
            ->where('s.deleted_at', null);

        if (!empty($filter['assessment_id'])) {
            $b->where('a.id', $filter['assessment_id']);
        }
        if (!empty($filter['date_from'])) {
            $b->where('ar.started_at >=', $filter['date_from'] . ' 00:00:00');
        }
        if (!empty($filter['date_to'])) {
            $b->where('ar.started_at <=', $filter['date_to'] . ' 23:59:59');
        }
        if ($ids) {
            $b->whereIn('ar.student_id', $ids);
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'ar.started_at',
            $filter['sort_dir'] ?? 'desc',
            ['a.title', 'su.full_name', 'ar.status', 'ar.percentage', 'ar.started_at']
        );

        $rows = $b->get()->getResultArray();
        $rows = $this->mapBooleanLabel($rows, 'is_passed', 'Lulus', 'Belum');

        $map = [
            'Individual' => 'Individu',
            'Class'      => 'Kelas',
            'Grade'      => 'Tingkatan',
            'All'        => 'Semua',
        ];

        $final = [];
        foreach ($rows as $r) {
            $aud   = $r['target_audience'] ?? 'Individual';
            $label = $map[$aud] ?? $aud;

            if ($aud === 'Class' && !empty($r['target_class_name'])) {
                $label .= ' (' . $r['target_class_name'] . ')';
            } elseif ($aud === 'Grade' && !empty($r['target_grade'])) {
                $label .= ' (' . $r['target_grade'] . ')';
            }

            $final[] = [
                'title'           => $r['title'] ?? null,
                'assessment_type' => $r['assessment_type'] ?? null,
                'target'          => $label,
                'student'         => $r['student'] ?? null,
                'class_name'      => $r['class_name'] ?? null,
                'status'          => $r['status'] ?? null,
                'percentage'      => $r['percentage'] ?? null,
                'is_passed'       => $r['is_passed'] ?? null,
                'started_at'      => $r['started_at'] ?? null,
                'completed_at'    => $r['completed_at'] ?? null,
            ];
        }

        return [
            'columns' => ['Asesmen', 'Tipe', 'Target', 'Siswa', 'Kelas', 'Status', '%', 'Lulus?', 'Mulai', 'Selesai'],
            'rows'    => $final,
        ];
    }

    /** Info Karir */
    public function career(array $filter): array
    {
        $b = $this->db->table('career_options')
            ->select('title, sector, min_education, avg_salary_idr, demand_level, is_active')
            ->where('deleted_at', null);

        if (!empty($filter['search'])) {
            $b->groupStart()
                ->like('title', $filter['search'])
                ->orLike('sector', $filter['search'])
              ->groupEnd();
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'title',
            $filter['sort_dir'] ?? 'asc',
            ['title', 'sector', 'min_education', 'avg_salary_idr', 'demand_level']
        );

        $rows = $b->get()->getResultArray();
        $rows = $this->mapBooleanLabel($rows, 'is_active', 'Aktif', 'Tidak Aktif');

        return [
            'columns' => ['Karier', 'Sektor', 'Min. Edu', 'Gaji Rata-rata (IDR)', 'Permintaan', 'Aktif'],
            'rows'    => $rows,
        ];
    }

    /** Info Universitas */
    public function universities(array $filter): array
    {
        $b = $this->db->table('university_info')
            ->select('university_name, alias, accreditation, location, website, is_active')
            ->where('deleted_at', null);

        if (!empty($filter['search'])) {
            $b->groupStart()
                ->like('university_name', $filter['search'])
                ->orLike('alias', $filter['search'])
              ->groupEnd();
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'university_name',
            $filter['sort_dir'] ?? 'asc',
            ['university_name', 'accreditation', 'location', 'is_active']
        );

        $rows = $b->get()->getResultArray();
        $rows = $this->mapBooleanLabel($rows, 'is_active', 'Aktif', 'Tidak Aktif');

        return [
            'columns' => ['Universitas', 'Alias', 'Akreditasi', 'Lokasi', 'Website', 'Aktif'],
            'rows'    => $rows,
        ];
    }

    /**
     * Laporan Pilihan Karir Siswa (berbasis Info Karir)
     * - Dibatasi hanya pada siswa binaan Guru BK terkait.
     */
    public function careerChoices(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (!$ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('student_saved_careers ssc')
            ->select(
                'co.title, co.sector, co.min_education, co.demand_level, co.is_active,' .
                'COUNT(DISTINCT ssc.student_id) AS students_count,' .
                'COUNT(*) AS saved_count'
            )
            ->join('career_options co', 'co.id = ssc.career_id', 'left')
            ->join('students st', 'st.id = ssc.student_id', 'left')
            ->join('classes c', 'c.id = st.class_id', 'left')
            ->whereIn('ssc.student_id', $ids)
            ->where('ssc.deleted_at', null)
            ->where('co.deleted_at', null)
            ->where('st.deleted_at', null)
            ->groupBy('ssc.career_id, co.title, co.sector, co.min_education, co.demand_level, co.is_active');

        if (!empty($filter['date_from'])) {
            $b->where('ssc.created_at >=', $filter['date_from'] . ' 00:00:00');
        }
        if (!empty($filter['date_to'])) {
            $b->where('ssc.created_at <=', $filter['date_to'] . ' 23:59:59');
        }
        if (!empty($filter['search'])) {
            $b->groupStart()
                ->like('co.title', $filter['search'])
                ->orLike('co.sector', $filter['search'])
              ->groupEnd();
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'students_count',
            $filter['sort_dir'] ?? 'desc',
            ['co.title', 'co.sector', 'students_count', 'saved_count']
        );

        $rows = $b->get()->getResultArray();
        $rows = $this->mapBooleanLabel($rows, 'is_active', 'Aktif', 'Tidak Aktif');

        $final = [];
        foreach ($rows as $r) {
            $final[] = [
                'title'          => $r['title'] ?? null,
                'sector'         => $r['sector'] ?? null,
                'min_education'  => $r['min_education'] ?? null,
                'demand_level'   => $r['demand_level'] ?? null,
                'is_active'      => $r['is_active'] ?? null,
                'students_count' => (int) ($r['students_count'] ?? 0),
                'saved_count'    => (int) ($r['saved_count'] ?? 0),
            ];
        }

        return [
            'columns' => ['Karier', 'Sektor', 'Min. Edu', 'Permintaan', 'Aktif', 'Jumlah Siswa', 'Total Simpan'],
            'rows'    => $final,
        ];
    }

    /**
     * Laporan Pilihan Perguruan Tinggi Siswa (berbasis Info PT)
     * - Dibatasi hanya pada siswa binaan Guru BK terkait.
     */
    public function universityChoices(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (!$ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('student_saved_universities ssu')
            ->select(
                'u.university_name, u.alias, u.accreditation, u.location, u.website, u.is_active,' .
                'COUNT(DISTINCT ssu.student_id) AS students_count,' .
                'COUNT(*) AS saved_count'
            )
            ->join('university_info u', 'u.id = ssu.university_id', 'left')
            ->join('students st', 'st.id = ssu.student_id', 'left')
            ->join('classes c', 'c.id = st.class_id', 'left')
            ->whereIn('ssu.student_id', $ids)
            ->where('ssu.deleted_at', null)
            ->where('u.deleted_at', null)
            ->where('st.deleted_at', null)
            ->groupBy('ssu.university_id, u.university_name, u.alias, u.accreditation, u.location, u.website, u.is_active');

        if (!empty($filter['date_from'])) {
            $b->where('ssu.created_at >=', $filter['date_from'] . ' 00:00:00');
        }
        if (!empty($filter['date_to'])) {
            $b->where('ssu.created_at <=', $filter['date_to'] . ' 23:59:59');
        }
        if (!empty($filter['search'])) {
            $b->groupStart()
                ->like('u.university_name', $filter['search'])
                ->orLike('u.alias', $filter['search'])
              ->groupEnd();
        }

        $this->applySort(
            $b,
            $filter['sort_by']  ?? 'students_count',
            $filter['sort_dir'] ?? 'desc',
            ['u.university_name', 'u.accreditation', 'u.location', 'students_count', 'saved_count']
        );

        $rows = $b->get()->getResultArray();
        $rows = $this->mapBooleanLabel($rows, 'is_active', 'Aktif', 'Tidak Aktif');

        $final = [];
        foreach ($rows as $r) {
            $final[] = [
                'university_name' => $r['university_name'] ?? null,
                'alias'           => $r['alias'] ?? null,
                'accreditation'   => $r['accreditation'] ?? null,
                'location'        => $r['location'] ?? null,
                'website'         => $r['website'] ?? null,
                'is_active'       => $r['is_active'] ?? null,
                'students_count'  => (int) ($r['students_count'] ?? 0),
                'saved_count'     => (int) ($r['saved_count'] ?? 0),
            ];
        }

        return [
            'columns' => ['Universitas', 'Alias', 'Akreditasi', 'Lokasi', 'Website', 'Aktif', 'Jumlah Siswa', 'Total Simpan'],
            'rows'    => $final,
        ];
    }

    /**
     * Laporan Agregat Sekolah (Koordinator BK)
     * - Rekap KPI + breakdown sesi, pelanggaran, sanksi, asesmen.
     * - Tidak menampilkan catatan konseling (privacy).
     */
    public function schoolAggregate(?string $from = null, ?string $to = null, ?int $classId = null, ?int $counselorId = null, ?int $categoryId = null): array
    {
        $school = $this->school();

        $scopeParts = [];
        $className = null;
        $counselorName = null;
        $categoryName = null;

        if ($classId) {
            $row = $this->db->table('classes')->select('class_name')->where('id', $classId)->get()->getRowArray();
            $className = $row['class_name'] ?? ('Kelas #' . $classId);
            $scopeParts[] = 'Kelas: ' . $className;
        }

        if ($counselorId) {
            $row = $this->db->table('users')->select('full_name')->where('id', $counselorId)->get()->getRowArray();
            $counselorName = $row['full_name'] ?? ('User #' . $counselorId);
            $scopeParts[] = 'BK: ' . $counselorName;
        }

        if ($categoryId) {
            $row = $this->db->table('violation_categories')->select('category_name')->where('id', $categoryId)->get()->getRowArray();
            $categoryName = $row['category_name'] ?? ('Kategori #' . $categoryId);
            $scopeParts[] = 'Kategori: ' . $categoryName;
        }

        $periodLabel = ($from ?: '-') . ' s/d ' . ($to ?: '-');
        $scopeLabel  = $scopeParts ? implode(' • ', $scopeParts) : 'Semua Data';

        // =========================
        // STUDENTS
        // =========================
        $stuB = $this->db->table('students s')
            ->select('COUNT(*) as total')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.deleted_at', null);

        if ($classId) {
            $stuB->where('s.class_id', $classId);
        }
        if ($counselorId) {
            $stuB->where('c.counselor_id', $counselorId);
        }

        $studentsTotal = (int) (($stuB->get()->getRowArray()['total'] ?? 0));

        // =========================
        // SESSIONS (ambil id dulu agar aman dari duplikasi)
        // =========================
        $sidB = $this->db->table('counseling_sessions cs')
            ->select('cs.id')
            ->where('cs.deleted_at', null);

        $this->applyDate($sidB, $from, $to, 'cs.session_date');

        if ($counselorId) {
            $sidB->where('cs.counselor_id', $counselorId);
        }

        if ($classId) {
            $cid = (int) $classId;

            $sidB->groupStart()
                ->groupStart()
                    ->where('cs.session_type', 'Klasikal')
                    ->where('cs.class_id', $cid)
                ->groupEnd()
                ->orGroupStart()
                    ->where('cs.session_type', 'Individu')
                    ->where("cs.student_id IN (SELECT id FROM students WHERE deleted_at IS NULL AND class_id = {$cid})", null, false)
                ->groupEnd()
                ->orGroupStart()
                    ->where('cs.session_type', 'Kelompok')
                    ->where("EXISTS (
                        SELECT 1
                        FROM session_participants sp
                        JOIN students s2 ON s2.id = sp.student_id AND s2.deleted_at IS NULL
                        WHERE sp.session_id = cs.id AND s2.class_id = {$cid}
                    )", null, false)
                ->groupEnd()
            ->groupEnd();
        }

        $sessionIds = array_map('intval', array_column($sidB->get()->getResultArray(), 'id'));

        $sessionsTotal = 0;
        $sessionsDuration = 0;

        $sessionsByType = [];
        $sessionsByCounselor = [];
        $sessionsByStatus = [];
        $sessionsByMonth = [];

        if ($sessionIds) {
            $sessRows = $this->db->table('counseling_sessions cs')
                ->select('cs.session_type, cs.status, cs.duration_minutes, cs.session_date, u.full_name as counselor_name')
                ->join('users u', 'u.id = cs.counselor_id', 'left')
                ->whereIn('cs.id', $sessionIds)
                ->get()->getResultArray();

            $sessionsTotal = count($sessRows);

            foreach ($sessRows as $r) {
                $type = (string) ($r['session_type'] ?? 'Lainnya');
                $status = (string) ($r['status'] ?? 'Unknown');
                $cname = (string) ($r['counselor_name'] ?? 'Tidak diketahui');
                $dur = (int) ($r['duration_minutes'] ?? 0);
                $date = (string) ($r['session_date'] ?? '');
                $month = $date ? substr($date, 0, 7) : 'Unknown';

                $sessionsDuration += $dur;

                if (!isset($sessionsByType[$type])) $sessionsByType[$type] = ['count' => 0, 'duration' => 0];
                $sessionsByType[$type]['count']++;
                $sessionsByType[$type]['duration'] += $dur;

                if (!isset($sessionsByStatus[$status])) $sessionsByStatus[$status] = 0;
                $sessionsByStatus[$status]++;

                if (!isset($sessionsByCounselor[$cname])) $sessionsByCounselor[$cname] = ['count' => 0, 'duration' => 0];
                $sessionsByCounselor[$cname]['count']++;
                $sessionsByCounselor[$cname]['duration'] += $dur;

                if (!isset($sessionsByMonth[$month])) $sessionsByMonth[$month] = 0;
                $sessionsByMonth[$month]++;
            }
        }

        $fmtCountDur = static function (array $map): array {
            $out = [];
            foreach ($map as $k => $v) {
                $out[] = ['label' => $k, 'count' => (int) ($v['count'] ?? 0), 'duration' => (int) ($v['duration'] ?? 0)];
            }
            usort($out, static fn ($a, $b) => ($b['count'] <=> $a['count']));
            return $out;
        };

        // =========================
        // VIOLATIONS
        // =========================
        $vioB = $this->db->table('violations v')
            ->select('v.student_id, v.status, vc.severity_level, v.category_id, vc.category_name, vc.point_deduction as points, s.class_id, c.class_name')
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->join('students s', 's.id = v.student_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('v.deleted_at', null)
            ->where('s.deleted_at', null);

        $this->applyDate($vioB, $from, $to, 'v.violation_date');

        if ($classId) {
            $vioB->where('s.class_id', (int) $classId);
        }
        if ($categoryId) {
            $vioB->where('v.category_id', (int) $categoryId);
        }
        if ($counselorId) {
            $cid = (int) $counselorId;
            $vioB->groupStart()
                ->where('v.handled_by', $cid)
                ->orWhere('v.reported_by', $cid)
            ->groupEnd();
        }

        $vioRows = $vioB->get()->getResultArray();

        $violationsTotal  = count($vioRows);
        $violationsPoints = 0;
        $violationsActive = 0;

        $vioByLevel = [];
        $vioByCategory = [];
        $vioByClass = [];
        $perStudent = [];

        foreach ($vioRows as $r) {
            $lvl  = (string) ($r['severity_level'] ?? 'Lainnya');
            $cat  = (string) ($r['category_name'] ?? 'Tanpa Kategori');
            $cls  = (string) ($r['class_name'] ?? 'Tanpa Kelas');
            $pts  = (int) ($r['points'] ?? 0);
            $st   = (string) ($r['status'] ?? '');

            $violationsPoints += $pts;

            if (in_array($st, ['Dilaporkan', 'Dalam Proses'], true)) {
                $violationsActive++;
            }

            if (!isset($vioByLevel[$lvl])) $vioByLevel[$lvl] = ['count' => 0, 'points' => 0];
            $vioByLevel[$lvl]['count']++;
            $vioByLevel[$lvl]['points'] += $pts;

            if (!isset($vioByCategory[$cat])) $vioByCategory[$cat] = ['count' => 0, 'points' => 0];
            $vioByCategory[$cat]['count']++;
            $vioByCategory[$cat]['points'] += $pts;

            if (!isset($vioByClass[$cls])) $vioByClass[$cls] = ['count' => 0, 'points' => 0];
            $vioByClass[$cls]['count']++;
            $vioByClass[$cls]['points'] += $pts;

            $sid = (int) ($r['student_id'] ?? 0);
            if ($sid > 0) {
                if (!isset($perStudent[$sid])) $perStudent[$sid] = 0;
                $perStudent[$sid]++;
            }
        }

        $repeatOffenders = 0;
        foreach ($perStudent as $cnt) {
            if ($cnt >= 2) $repeatOffenders++;
        }

        $fmtCountPoints = static function (array $map): array {
            $out = [];
            foreach ($map as $k => $v) {
                $out[] = ['label' => $k, 'count' => (int) ($v['count'] ?? 0), 'points' => (int) ($v['points'] ?? 0)];
            }
            usort($out, static fn ($a, $b) => ($b['count'] <=> $a['count']));
            return $out;
        };

        // =========================
        // SANCTIONS
        // =========================
        $sanB = $this->db->table('sanctions s')
            ->select('s.sanction_type, s.status')
            ->join('violations v', 'v.id = s.violation_id', 'left')
            ->join('students st', 'st.id = v.student_id', 'left')
            ->where('s.deleted_at', null)
            ->where('v.deleted_at', null)
            ->where('st.deleted_at', null);

        $this->applyDate($sanB, $from, $to, 's.sanction_date');

        if ($classId) {
            $sanB->where('st.class_id', (int) $classId);
        }
        if ($counselorId) {
            $sanB->where('s.assigned_by', (int) $counselorId);
        }

        $sanRows = $sanB->get()->getResultArray();

        $sanctionsTotal = count($sanRows);
        $sanByType = [];
        $sanByStatus = [];

        foreach ($sanRows as $r) {
            $t = (string) ($r['sanction_type'] ?? 'Lainnya');
            $st = (string) ($r['status'] ?? 'Unknown');

            if (!isset($sanByType[$t])) $sanByType[$t] = 0;
            $sanByType[$t]++;

            if (!isset($sanByStatus[$st])) $sanByStatus[$st] = 0;
            $sanByStatus[$st]++;
        }

        $fmtCountOnly = static function (array $map): array {
            $out = [];
            foreach ($map as $k => $v) {
                $out[] = ['label' => $k, 'count' => (int) $v];
            }
            usort($out, static fn ($a, $b) => ($b['count'] <=> $a['count']));
            return $out;
        };

        // =========================
        // ASSESSMENTS
        // =========================
        $assB = $this->db->table('assessment_results ar')
            ->select('ar.status, ar.percentage, ar.is_passed, a.title, a.created_by')
            ->join('assessments a', 'a.id = ar.assessment_id', 'left')
            ->join('students s', 's.id = ar.student_id', 'left')
            ->where('ar.deleted_at', null)
            ->where('a.deleted_at', null)
            ->where('s.deleted_at', null);

        if ($classId) {
            $assB->where('s.class_id', (int) $classId);
        }
        if ($counselorId) {
            $assB->where('a.created_by', (int) $counselorId);
        }

        if ($from) {
            $assB->where('DATE(COALESCE(ar.started_at, ar.created_at)) >=', $from);
        }
        if ($to) {
            $assB->where('DATE(COALESCE(ar.started_at, ar.created_at)) <=', $to);
        }

        $assRows = $assB->get()->getResultArray();

        $assAssigned = count($assRows);
        $assCompleted = 0;
        $assAvg = 0.0;
        $assAvgCount = 0;

        $assByStatus = [];
        $assByAssessment = [];

        foreach ($assRows as $r) {
            $st = (string) ($r['status'] ?? 'Unknown');
            $title = (string) ($r['title'] ?? 'Tanpa Judul');
            $pct = $r['percentage'];

            if (!isset($assByStatus[$st])) $assByStatus[$st] = 0;
            $assByStatus[$st]++;

            $isDone = in_array($st, ['Completed', 'Graded'], true);
            if ($isDone) $assCompleted++;

            if (!isset($assByAssessment[$title])) {
                $assByAssessment[$title] = ['assigned' => 0, 'completed' => 0, 'sum' => 0.0, 'cnt' => 0];
            }
            $assByAssessment[$title]['assigned']++;
            if ($isDone) $assByAssessment[$title]['completed']++;

            if ($pct !== null && $pct !== '') {
                $assByAssessment[$title]['sum'] += (float) $pct;
                $assByAssessment[$title]['cnt']++;

                $assAvg += (float) $pct;
                $assAvgCount++;
            }
        }

        $assAvgPct = $assAvgCount ? round($assAvg / $assAvgCount, 2) : 0;

        $assByAssessmentOut = [];
        foreach ($assByAssessment as $k => $v) {
            $avgPct = $v['cnt'] ? round($v['sum'] / $v['cnt'], 2) : 0;
            $assByAssessmentOut[] = [
                'label' => $k,
                'assigned' => (int) $v['assigned'],
                'completed' => (int) $v['completed'],
                'avg_percentage' => $avgPct,
            ];
        }
        usort($assByAssessmentOut, static fn ($a, $b) => ($b['assigned'] <=> $a['assigned']));

        // =========================
        // KPI bundle
        // =========================
        $kpi = [
            'students_total'             => $studentsTotal,

            'sessions_total'             => $sessionsTotal,
            'sessions_duration_total'    => $sessionsDuration,

            'violations_total'           => $violationsTotal,
            'violations_points_total'    => $violationsPoints,
            'violations_active'          => $violationsActive,
            'repeat_offenders'           => $repeatOffenders,

            'sanctions_total'            => $sanctionsTotal,

            'assessments_assigned'       => $assAssigned,
            'assessments_completed'      => $assCompleted,
            'assessments_avg_percentage' => $assAvgPct,
        ];

        return [
            'school'       => $school,
            'period'       => ['from' => $from, 'to' => $to, 'label' => $periodLabel],
            'scope'        => [
                'class_id'     => $classId,
                'class_name'   => $className,
                'counselor_id' => $counselorId,
                'counselor_name' => $counselorName,
                'category_id'  => $categoryId,
                'category_name' => $categoryName,
                'label'        => $scopeLabel,
            ],
            'generated_at' => date('Y-m-d H:i:s'),
            'kpi'          => $kpi,

            'sessions'     => [
                'byType'      => $fmtCountDur($sessionsByType),
                'byCounselor' => $fmtCountDur($sessionsByCounselor),
                'byStatus'    => $sessionsByStatus,
                'byMonth'     => $sessionsByMonth,
            ],

            'violations'   => [
                'byLevel'    => $fmtCountPoints($vioByLevel),
                'byCategory' => $fmtCountPoints($vioByCategory),
                'byClass'    => $fmtCountPoints($vioByClass),
            ],

            'sanctions'    => [
                'byType'   => $fmtCountOnly($sanByType),
                'byStatus' => $fmtCountOnly($sanByStatus),
            ],

            'assessments'  => [
                'byStatus'     => $fmtCountOnly($assByStatus),
                'byAssessment' => $assByAssessmentOut,
            ],
        ];
    }
}
