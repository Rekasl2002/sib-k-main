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

    protected function applyDate(&$builder, ?string $from, ?string $to, string $field): void
    {
        if ($from) {
            $builder->where($field . ' >=', $from);
        }
        if ($to) {
            $builder->where($field . ' <=', $to);
        }
    }

    protected function school(): array
    {
        helper('settings');

        return [
            'name'    => setting('school_name', env('school.name', 'Nama Sekolah'), 'general'),
            'address' => setting('address', env('school.address', ''), 'general'),
            'phone'   => setting('contact_phone', env('school.phone', ''), 'general'),
            'email'   => setting('contact_email', env('school.email', ''), 'general'),
            'website' => setting('website', env('school.website', ''), 'general'),
            'logo'    => base_url(setting('logo_path', 'assets/images/logo.png', 'branding')),
        ];
    }

    protected function applySort($builder, string $sortBy, string $sortDir, array $whitelist): void
    {
        $sortBy  = in_array($sortBy, $whitelist, true) ? $sortBy : $whitelist[0];
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $builder->orderBy($sortBy, $sortDir);
    }

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

    public function studentOptionsForCounselor(int $counselorId, ?int $classId = null): array
    {
        $b = $this->studentOptionBuilder()
            ->where('c.counselor_id', $counselorId);

        if ($classId) {
            $b->where('s.class_id', $classId);
        }

        return $b->get()->getResultArray();
    }

    public function studentOptionsForClass(int $classId): array
    {
        return $this->studentOptionBuilder()
            ->where('s.class_id', $classId)
            ->get()
            ->getResultArray();
    }

    public function studentOptionsAll(): array
    {
        return $this->studentOptionBuilder()->get()->getResultArray();
    }

    private function studentOptionBuilder()
    {
        return $this->db->table('students s')
            ->select('s.id, s.nisn, u.full_name, c.class_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC');
    }

    public function studentIndividual(int $studentId, ?string $from = null, ?string $to = null, string $category = 'all'): array
    {
        $student = $this->db->table('students s')
            ->select('s.*, u.full_name, c.class_name, c.id as class_id')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', $studentId)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        $category = $this->normalizeIndividualCategory($category);

        $consultations = $this->shouldIncludeIndividualCategory($category, 'consultation')
            ? $this->studentConsultations($studentId, $from, $to)
            : [];
        $bkServices = $this->studentBkServices($studentId, $from, $to);
        $sessions = $this->shouldIncludeIndividualCategory($category, 'counseling')
            ? $this->studentSessions($studentId, $from, $to)
            : [];
        $assessments = $this->shouldIncludeIndividualCategory($category, 'assessment')
            ? $this->studentAssessments($studentId, $from, $to)
            : [];
        $careers = $this->shouldIncludeIndividualCategory($category, 'career')
            ? $this->studentCareers($studentId, $from, $to)
            : [];
        $universities = $this->shouldIncludeIndividualCategory($category, 'university')
            ? $this->studentUniversities($studentId, $from, $to)
            : [];

        if ($category !== 'all') {
            $bkServices = array_values(array_filter($bkServices, fn(array $row): bool => $this->serviceTypeKey((string) ($row['service_type'] ?? '')) === $category));
        }

        return [
            'school'               => $this->school(),
            'student'              => $student,
            'period'               => ['from' => $from, 'to' => $to],
            'consultations'         => $consultations,
            'bkServices'            => $bkServices,
            'sessions'             => $sessions,
            'assessments'          => $assessments,
            'careerChoices'        => $careers,
            'universityChoices'    => $universities,
            'totalSessions'        => count($sessions),
            'totalAssessments'     => count($assessments),
            'completedAssessments' => count(array_filter($assessments, static fn ($r) => in_array((string) ($r['status'] ?? ''), ['Completed', 'Graded'], true))),
            'totalCareerChoices'   => count($careers),
            'totalUniversityChoices' => count($universities),
        ];
    }

    public function studentIndividualTable(int $studentId, ?string $from = null, ?string $to = null, string $category = 'all'): array
    {
        $data = $this->studentIndividual($studentId, $from, $to, $category);
        $rows = [];

        foreach ($data['consultations'] as $row) {
            $rows[] = [
                'date'     => $row['created_at'] ?? $row['occurred_at'] ?? '-',
                'category' => 'Konsultasi & Pengaduan',
                'activity' => trim((string) ($row['title'] ?? '-')),
                'status'   => $row['status'] ?? '-',
                'notes'    => $row['category'] ?? $row['request_type'] ?? '-',
            ];
        }

        foreach ($data['bkServices'] as $row) {
            $rows[] = [
                'date'     => $row['held_at'] ?? $row['scheduled_at'] ?? $row['created_at'] ?? '-',
                'category' => $row['service_type'] ?? 'Layanan BK',
                'activity' => trim((string) ($row['title'] ?? '-')),
                'status'   => $row['status'] ?? '-',
                'notes'    => $row['location'] ?? $row['summary'] ?? '-',
            ];
        }

        foreach ($data['sessions'] as $row) {
            if (!empty($row['bk_service_record_id'])) {
                continue;
            }

            $rows[] = [
                'date'     => $row['session_date'] ?? '-',
                'category' => 'Konseling',
                'activity' => trim((string) ($row['topic'] ?? '-')),
                'status'   => $row['status'] ?? '-',
                'notes'    => $row['session_summary'] ?? $row['location'] ?? '-',
            ];
        }

        foreach ($data['assessments'] as $row) {
            $rows[] = [
                'date'     => $row['completed_at'] ?? $row['started_at'] ?? $row['created_at'] ?? '-',
                'category' => 'Asesmen',
                'activity' => $row['title'] ?? '-',
                'status'   => $row['status'] ?? '-',
                'notes'    => isset($row['percentage']) ? ((string) $row['percentage'] . '%') : '-',
            ];
        }

        foreach ($data['careerChoices'] as $row) {
            $rows[] = [
                'date'     => $row['saved_at'] ?? '-',
                'category' => 'Pilihan Karier',
                'activity' => $row['title'] ?? '-',
                'status'   => $row['sector'] ?? '-',
                'notes'    => $row['min_education'] ?? '-',
            ];
        }

        foreach ($data['universityChoices'] as $row) {
            $rows[] = [
                'date'     => $row['saved_at'] ?? '-',
                'category' => 'Pilihan Studi Lanjut',
                'activity' => $row['university_name'] ?? '-',
                'status'   => $row['accreditation'] ?? '-',
                'notes'    => $row['location'] ?? '-',
            ];
        }

        usort($rows, static fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));

        return [
            'columns' => ['Tanggal', 'Kategori', 'Kegiatan', 'Status', 'Catatan'],
            'rows'    => $rows,
            'student' => $data['student'],
            'data'    => $data,
        ];
    }

    /**
     * Pilihan jenis catatan untuk Laporan Individu Siswa.
     * Dipakai Koordinator BK dan Guru BK agar bisa memilih semua catatan BK atau salah satu fitur.
     *
     * @return array<string,string>
     */
    public function individualCategoryOptions(): array
    {
        return [
            'all' => 'Semua Catatan BK',
            'consultation' => 'Konsultasi & Pengaduan',
            'guidance' => 'Bimbingan',
            'counseling' => 'Konseling',
            'parent_collaboration' => 'Kolaborasi Orang Tua',
            'home_visit' => 'Kunjungan Rumah',
            'case_conference' => 'Konferensi Kasus',
            'assessment' => 'Asesmen',
            'career' => 'Info Karier',
            'university' => 'Info Studi Lanjut',
        ];
    }

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
            ->get()
            ->getResultArray();

        return [
            'school' => $this->school(),
            'period' => ['from' => $from, 'to' => $to],
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    public function classAggregate(int $classId, ?string $from, ?string $to): array
    {
        $class = $this->db->table('classes')
            ->where('id', $classId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $students = $this->db->table('students s')
            ->select('s.*, u.full_name, c.class_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.class_id', $classId)
            ->where('s.deleted_at', null)
            ->get()
            ->getResultArray();

        $ids = array_map(static fn ($r) => (int) $r['id'], $students);
        $sessions = [];

        if ($ids) {
            $sess = $this->db->table('counseling_sessions')
                ->where('deleted_at', null)
                ->whereIn('student_id', $ids);
            $this->applyDate($sess, $from, $to, 'session_date');
            $sessions = $sess->get()->getResultArray();
        }

        return [
            'school'       => $this->school(),
            'class'        => $class,
            'period'       => ['from' => $from, 'to' => $to],
            'studentCount' => count($students),
            'sessionCount' => count($sessions),
            'students'     => $students,
            'sessions'     => $sessions,
        ];
    }

    public function students(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (! $ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('students s')
            ->select('s.nisn, s.nik, u.full_name, s.gender, s.birth_date, TIMESTAMPDIFF(YEAR, s.birth_date, CURDATE()) AS age, s.special_needs, s.disability, s.kip_pip_number, s.father_name, s.mother_name, s.guardian_name, s.status, c.class_name', false)
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
                ->orLike('s.nik', $filter['search'])
                ->groupEnd();
        }

        $this->applySort($b, $filter['sort_by'] ?? 'u.full_name', $filter['sort_dir'] ?? 'asc', ['u.full_name', 's.nisn', 's.nik', 'c.class_name', 's.status']);

        return [
            'columns' => ['NISN', 'NIK', 'Nama', 'JK', 'Tgl Lahir', 'Umur', 'Kebutuhan Khusus', 'Disabilitas', 'Nomor KIP/PIP', 'Nama Ayah', 'Nama Ibu', 'Nama Wali', 'Status', 'Kelas'],
            'rows' => $b->get()->getResultArray(),
        ];
    }

    public function sessions(array $filter, int $counselorId): array
    {
        $b = $this->db->table('counseling_sessions cs')
            ->select('cs.session_date, cs.session_time, cs.session_type, cs.location, cs.topic, cs.status, cs.duration_minutes, su.full_name as student, cstu.class_name as student_class, ctar.class_name as target_class')
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes cstu', 'cstu.id = s.class_id', 'left')
            ->join('classes ctar', 'ctar.id = cs.class_id', 'left')
            ->where('cs.counselor_id', $counselorId)
            ->where('cs.deleted_at', null);

        if (!empty($filter['class_id'])) {
            $classId = (int) $filter['class_id'];
            $b->groupStart()
                ->where('s.class_id', $classId)
                ->orWhere('cs.class_id', $classId)
                ->groupEnd();
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

        $this->applySort($b, $filter['sort_by'] ?? 'cs.session_date', $filter['sort_dir'] ?? 'desc', ['cs.session_date', 'cs.session_type', 'cs.status', 'su.full_name']);

        $rows = [];
        foreach ($b->get()->getResultArray() as $r) {
            $type = (string) ($r['session_type'] ?? 'Individu');
            $target = $type === 'Klasikal'
                ? ($r['target_class'] ?? '-')
                : trim((string) ($r['student'] ?? '-') . ' (' . (string) ($r['student_class'] ?? '-') . ')');

            $rows[] = [
                'session_date' => $r['session_date'] ?? null,
                'session_time' => $r['session_time'] ?? null,
                'session_type' => $type,
                'location' => $r['location'] ?? null,
                'topic' => $r['topic'] ?? null,
                'student' => $target,
                'status' => $r['status'] ?? null,
                'duration_minutes' => $r['duration_minutes'] ?? null,
            ];
        }

        return [
            'columns' => ['Tanggal', 'Waktu', 'Jenis', 'Lokasi', 'Topik', 'Siswa/Kelas', 'Status', 'Durasi (m)'],
            'rows' => $rows,
        ];
    }

    public function assessments(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (! $ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('assessment_results ar')
            ->select('a.title, a.assessment_type, su.full_name as student, c.class_name, ar.status, ar.percentage, ar.is_passed, ar.started_at, ar.completed_at')
            ->join('assessments a', 'a.id = ar.assessment_id', 'left')
            ->join('students s', 's.id = ar.student_id', 'left')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->whereIn('ar.student_id', $ids)
            ->where('ar.deleted_at', null);

        if (!empty($filter['assessment_id'])) {
            $b->where('ar.assessment_id', (int) $filter['assessment_id']);
        }
        if (!empty($filter['status'])) {
            $b->where('ar.status', $filter['status']);
        }
        if (!empty($filter['date_from'])) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) <=', $filter['date_to']);
        }

        $this->applySort($b, $filter['sort_by'] ?? 'a.title', $filter['sort_dir'] ?? 'asc', ['a.title', 'a.assessment_type', 'su.full_name', 'c.class_name', 'ar.status', 'ar.percentage']);

        return [
            'columns' => ['Asesmen', 'Jenis', 'Siswa', 'Kelas', 'Status', 'Persentase', 'Lulus?', 'Mulai', 'Selesai'],
            'rows' => $b->get()->getResultArray(),
        ];
    }

    public function careerChoices(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (! $ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('student_saved_careers ssc')
            ->select('co.title, co.sector, co.min_education, co.demand_level, COUNT(DISTINCT ssc.student_id) AS students_count, COUNT(*) AS saved_count')
            ->join('career_options co', 'co.id = ssc.career_id', 'left')
            ->whereIn('ssc.student_id', $ids)
            ->where('ssc.deleted_at', null)
            ->where('co.deleted_at', null)
            ->groupBy('ssc.career_id, co.title, co.sector, co.min_education, co.demand_level');

        if (!empty($filter['date_from'])) {
            $b->where('ssc.created_at >=', $filter['date_from'] . ' 00:00:00');
        }
        if (!empty($filter['date_to'])) {
            $b->where('ssc.created_at <=', $filter['date_to'] . ' 23:59:59');
        }
        if (!empty($filter['search'])) {
            $b->like('co.title', $filter['search']);
        }

        return [
            'columns' => ['Karier', 'Sektor', 'Min. Edu', 'Permintaan', 'Jumlah Siswa', 'Total Simpan'],
            'rows' => $b->orderBy('students_count', 'DESC')->get()->getResultArray(),
        ];
    }

    public function universityChoices(array $filter, int $counselorId): array
    {
        $ids = $this->counselorStudentIds($counselorId, $filter['class_id'] ?? null);
        if (! $ids) {
            return ['columns' => [], 'rows' => []];
        }

        $b = $this->db->table('student_saved_universities ssu')
            ->select('ui.university_name, ui.alias, ui.accreditation, ui.location, ui.website, COUNT(DISTINCT ssu.student_id) AS students_count, COUNT(*) AS saved_count')
            ->join('university_info ui', 'ui.id = ssu.university_id', 'left')
            ->whereIn('ssu.student_id', $ids)
            ->where('ssu.deleted_at', null)
            ->where('ui.deleted_at', null)
            ->groupBy('ssu.university_id, ui.university_name, ui.alias, ui.accreditation, ui.location, ui.website');

        if (!empty($filter['date_from'])) {
            $b->where('ssu.created_at >=', $filter['date_from'] . ' 00:00:00');
        }
        if (!empty($filter['date_to'])) {
            $b->where('ssu.created_at <=', $filter['date_to'] . ' 23:59:59');
        }
        if (!empty($filter['search'])) {
            $b->like('ui.university_name', $filter['search']);
        }

        return [
            'columns' => ['Universitas', 'Alias', 'Akreditasi', 'Lokasi', 'Website', 'Jumlah Siswa', 'Total Simpan'],
            'rows' => $b->orderBy('students_count', 'DESC')->get()->getResultArray(),
        ];
    }

    public function schoolAggregate(?string $from = null, ?string $to = null, ?int $classId = null, ?int $counselorId = null, ?int $categoryId = null): array
    {
        $school = $this->school();
        $scopeParts = [];
        $className = null;
        $counselorName = null;

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

        $studentsTotal = $this->countStudents($classId, $counselorId);
        $sessionData = $this->aggregateSessions($from, $to, $classId, $counselorId);
        $assessmentData = $this->aggregateAssessments($from, $to, $classId, $counselorId);

        return [
            'school' => $school,
            'period' => ['from' => $from, 'to' => $to, 'label' => ($from ?: '-') . ' s/d ' . ($to ?: '-')],
            'scope' => [
                'class_id' => $classId,
                'class_name' => $className,
                'counselor_id' => $counselorId,
                'counselor_name' => $counselorName,
                'label' => $scopeParts ? implode(' - ', $scopeParts) : 'Semua Data',
            ],
            'generated_at' => date('Y-m-d H:i:s'),
            'kpi' => [
                'students_total' => $studentsTotal,
                'sessions_total' => $sessionData['total'],
                'sessions_duration_total' => $sessionData['duration'],
                'assessments_assigned' => $assessmentData['assigned'],
                'assessments_completed' => $assessmentData['completed'],
                'assessments_avg_percentage' => $assessmentData['avg'],
            ],
            'sessions' => [
                'byType' => $sessionData['byType'],
                'byCounselor' => $sessionData['byCounselor'],
                'byStatus' => $sessionData['byStatus'],
                'byMonth' => $sessionData['byMonth'],
            ],
            'assessments' => [
                'byStatus' => $assessmentData['byStatus'],
                'byAssessment' => $assessmentData['byAssessment'],
            ],
        ];
    }

    private function studentSessions(int $studentId, ?string $from, ?string $to): array
    {
        $b = $this->db->table('counseling_sessions cs')
            ->select('cs.*, u.full_name as counselor_name')
            ->join('users u', 'u.id = cs.counselor_id', 'left')
            ->where('cs.student_id', $studentId)
            ->where('cs.deleted_at', null);

        $this->applyDate($b, $from, $to, 'cs.session_date');

        return $b->orderBy('cs.session_date', 'ASC')->orderBy('cs.session_time', 'ASC')->get()->getResultArray();
    }

    private function studentConsultations(int $studentId, ?string $from, ?string $to): array
    {
        if (!$this->db->tableExists('consultation_complaints')) {
            return [];
        }

        $b = $this->db->table('consultation_complaints')
            ->where('subject_student_id', $studentId);

        if ($this->fieldExists('consultation_complaints', 'deleted_at')) {
            $b->where('deleted_at', null);
        }
        if ($from) {
            $b->where('DATE(COALESCE(occurred_at, created_at)) >=', $from);
        }
        if ($to) {
            $b->where('DATE(COALESCE(occurred_at, created_at)) <=', $to);
        }

        return $b->orderBy('COALESCE(occurred_at, created_at)', 'DESC', false)
            ->get()
            ->getResultArray();
    }

    private function studentBkServices(int $studentId, ?string $from, ?string $to): array
    {
        if (!$this->db->tableExists('bk_service_records')) {
            return [];
        }

        $student = $this->db->table('students')
            ->select('class_id')
            ->where('id', $studentId)
            ->get()
            ->getRowArray();
        $classId = (int) ($student['class_id'] ?? 0);

        $b = $this->db->table('bk_service_records bsr')
            ->select('DISTINCT bsr.*', false)
            ->join('session_participants sp', 'sp.bk_service_record_id = bsr.id AND sp.deleted_at IS NULL', 'left')
            ->groupStart()
                ->where('bsr.target_student_id', $studentId)
                ->orWhere('sp.participant_student_id', $studentId);

        if ($classId > 0) {
            $b->orWhere('bsr.target_class_id', $classId)
                ->orWhere('sp.participant_class_id', $classId);
        }

        $b->groupEnd();

        if ($this->fieldExists('bk_service_records', 'deleted_at')) {
            $b->where('bsr.deleted_at', null);
        }
        if ($from) {
            $b->where('DATE(COALESCE(bsr.held_at, bsr.scheduled_at, bsr.created_at)) >=', $from);
        }
        if ($to) {
            $b->where('DATE(COALESCE(bsr.held_at, bsr.scheduled_at, bsr.created_at)) <=', $to);
        }

        return $b->orderBy('COALESCE(bsr.held_at, bsr.scheduled_at, bsr.created_at)', 'DESC', false)
            ->get()
            ->getResultArray();
    }

    private function normalizeIndividualCategory(string $category): string
    {
        $category = strtolower(trim($category));
        return array_key_exists($category, $this->individualCategoryOptions()) ? $category : 'all';
    }

    private function shouldIncludeIndividualCategory(string $selected, string $target): bool
    {
        return $selected === 'all' || $selected === $target;
    }

    private function serviceTypeKey(string $serviceType): string
    {
        return match ($serviceType) {
            'Bimbingan' => 'guidance',
            'Konseling' => 'counseling',
            'Kolaborasi Orang Tua' => 'parent_collaboration',
            'Kunjungan Rumah' => 'home_visit',
            'Konferensi Kasus' => 'case_conference',
            default => 'all',
        };
    }

    private function fieldExists(string $table, string $field): bool
    {
        try {
            return $this->db->fieldExists($field, $table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function studentAssessments(int $studentId, ?string $from, ?string $to): array
    {
        $b = $this->db->table('assessment_results ar')
            ->select('ar.*, a.title, a.assessment_type')
            ->join('assessments a', 'a.id = ar.assessment_id', 'left')
            ->where('ar.student_id', $studentId)
            ->where('ar.deleted_at', null);

        if ($from) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) >=', $from);
        }
        if ($to) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) <=', $to);
        }

        return $b->orderBy('COALESCE(ar.completed_at, ar.started_at, ar.created_at)', 'DESC', false)->get()->getResultArray();
    }

    private function studentCareers(int $studentId, ?string $from, ?string $to): array
    {
        $b = $this->db->table('student_saved_careers ssc')
            ->select('co.title, co.sector, co.min_education, ssc.created_at as saved_at')
            ->join('career_options co', 'co.id = ssc.career_id', 'left')
            ->where('ssc.student_id', $studentId)
            ->where('ssc.deleted_at', null);

        if ($from) {
            $b->where('DATE(ssc.created_at) >=', $from);
        }
        if ($to) {
            $b->where('DATE(ssc.created_at) <=', $to);
        }

        return $b->orderBy('ssc.created_at', 'DESC')->get()->getResultArray();
    }

    private function studentUniversities(int $studentId, ?string $from, ?string $to): array
    {
        $b = $this->db->table('student_saved_universities ssu')
            ->select('ui.university_name, ui.alias, ui.accreditation, ui.location, ssu.created_at as saved_at')
            ->join('university_info ui', 'ui.id = ssu.university_id', 'left')
            ->where('ssu.student_id', $studentId)
            ->where('ssu.deleted_at', null);

        if ($from) {
            $b->where('DATE(ssu.created_at) >=', $from);
        }
        if ($to) {
            $b->where('DATE(ssu.created_at) <=', $to);
        }

        return $b->orderBy('ssu.created_at', 'DESC')->get()->getResultArray();
    }

    private function countStudents(?int $classId, ?int $counselorId): int
    {
        $b = $this->db->table('students s')
            ->select('COUNT(*) as total')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.deleted_at', null);

        if ($classId) {
            $b->where('s.class_id', $classId);
        }
        if ($counselorId) {
            $b->where('c.counselor_id', $counselorId);
        }

        return (int) ($b->get()->getRowArray()['total'] ?? 0);
    }

    private function aggregateSessions(?string $from, ?string $to, ?int $classId, ?int $counselorId): array
    {
        $b = $this->db->table('counseling_sessions cs')
            ->select('cs.session_type, cs.status, cs.duration_minutes, cs.session_date, u.full_name as counselor_name')
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users u', 'u.id = cs.counselor_id', 'left')
            ->where('cs.deleted_at', null);

        $this->applyDate($b, $from, $to, 'cs.session_date');

        if ($classId) {
            $b->groupStart()->where('s.class_id', $classId)->orWhere('cs.class_id', $classId)->groupEnd();
        }
        if ($counselorId) {
            $b->where('cs.counselor_id', $counselorId);
        }

        $rows = $b->get()->getResultArray();
        $byType = [];
        $byCounselor = [];
        $byStatus = [];
        $byMonth = [];
        $duration = 0;

        foreach ($rows as $row) {
            $type = (string) ($row['session_type'] ?? 'Lainnya');
            $counselor = (string) ($row['counselor_name'] ?? 'Tidak diketahui');
            $status = (string) ($row['status'] ?? 'Unknown');
            $month = !empty($row['session_date']) ? substr((string) $row['session_date'], 0, 7) : 'Unknown';
            $minutes = (int) ($row['duration_minutes'] ?? 0);

            $duration += $minutes;
            $this->addCountDuration($byType, $type, $minutes);
            $this->addCountDuration($byCounselor, $counselor, $minutes);
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
            $byMonth[$month] = ($byMonth[$month] ?? 0) + 1;
        }

        return [
            'total' => count($rows),
            'duration' => $duration,
            'byType' => $this->formatCountDuration($byType),
            'byCounselor' => $this->formatCountDuration($byCounselor),
            'byStatus' => $byStatus,
            'byMonth' => $byMonth,
        ];
    }

    private function aggregateAssessments(?string $from, ?string $to, ?int $classId, ?int $counselorId): array
    {
        $b = $this->db->table('assessment_results ar')
            ->select('ar.status, ar.percentage, a.title')
            ->join('assessments a', 'a.id = ar.assessment_id', 'left')
            ->join('students s', 's.id = ar.student_id', 'left')
            ->where('ar.deleted_at', null);

        if ($classId) {
            $b->where('s.class_id', $classId);
        }
        if ($counselorId) {
            $b->where('a.created_by', $counselorId);
        }
        if ($from) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) >=', $from);
        }
        if ($to) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) <=', $to);
        }

        $rows = $b->get()->getResultArray();
        $byStatus = [];
        $byAssessment = [];
        $completed = 0;
        $sum = 0.0;
        $cnt = 0;

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'Unknown');
            $title = (string) ($row['title'] ?? 'Tanpa Judul');
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            if (!isset($byAssessment[$title])) {
                $byAssessment[$title] = ['assigned' => 0, 'completed' => 0, 'sum' => 0.0, 'cnt' => 0];
            }

            $byAssessment[$title]['assigned']++;

            if (in_array($status, ['Completed', 'Graded'], true)) {
                $completed++;
                $byAssessment[$title]['completed']++;
            }

            if ($row['percentage'] !== null && $row['percentage'] !== '') {
                $pct = (float) $row['percentage'];
                $sum += $pct;
                $cnt++;
                $byAssessment[$title]['sum'] += $pct;
                $byAssessment[$title]['cnt']++;
            }
        }

        $assessmentRows = [];
        foreach ($byAssessment as $title => $row) {
            $assessmentRows[] = [
                'label' => $title,
                'assigned' => (int) $row['assigned'],
                'completed' => (int) $row['completed'],
                'avg_percentage' => $row['cnt'] ? round($row['sum'] / $row['cnt'], 2) : 0,
            ];
        }

        usort($assessmentRows, static fn ($a, $b) => ($b['assigned'] <=> $a['assigned']));

        return [
            'assigned' => count($rows),
            'completed' => $completed,
            'avg' => $cnt ? round($sum / $cnt, 2) : 0,
            'byStatus' => $byStatus,
            'byAssessment' => $assessmentRows,
        ];
    }

    private function addCountDuration(array &$bucket, string $key, int $duration): void
    {
        if (!isset($bucket[$key])) {
            $bucket[$key] = ['count' => 0, 'duration' => 0];
        }

        $bucket[$key]['count']++;
        $bucket[$key]['duration'] += $duration;
    }

    private function formatCountDuration(array $bucket): array
    {
        $rows = [];
        foreach ($bucket as $key => $value) {
            $rows[] = [
                'label' => $key,
                'count' => (int) ($value['count'] ?? 0),
                'duration' => (int) ($value['duration'] ?? 0),
            ];
        }

        usort($rows, static fn ($a, $b) => ($b['count'] <=> $a['count']));

        return $rows;
    }

    // =====================================================================
    // PERBAIKAN KEDUA — Item #11: Laporan multi-fitur (sections per fitur)
    // Dipakai bersama oleh Koordinator BK, Guru BK, dan Wali Kelas.
    // Tiap fitur menghasilkan satu/lebih "section": ['title','columns','rows'].
    // $scope = [
    //   'role' => 'koordinator'|'counselor'|'homeroom',
    //   'user_id' => int,
    //   'allowed_class_ids' => array|null (null = semua kelas),
    //   'allowed_student_ids' => array|null (null = semua siswa),
    //   'mask_confidential' => bool (true utk Wali Kelas),
    //   'single' => bool (true bila mode "satu siswa"),
    //   'counselor_id' => int|null (filter pembuat/penanggung jawab, opsional),
    // ]
    // =====================================================================

    /**
     * Daftar jenis laporan (checkbox) per peran.
     * @return array<string,string> key => label
     */
    public static function featureCatalog(string $role): array
    {
        $all = [
            'students'             => 'Data Siswa',
            'assignments'          => 'Penugasan / Tugas',
            'consultations'        => 'Konsultasi & Pengaduan',
            'guidance'             => 'Bimbingan',
            'counseling'           => 'Konseling',
            'parent_collaboration' => 'Kolaborasi Orang Tua',
            'home_visit'           => 'Kunjungan Rumah',
            'case_conference'      => 'Konferensi Kasus',
            'assessment'           => 'Asesmen',
            'career'               => 'Info Karier (Pilihan Siswa)',
            'university'           => 'Info Studi Lanjut (Pilihan Siswa)',
        ];

        return match ($role) {
            'koordinator' => ['aggregate' => 'Rekap Sekolah/Kelas (Agregat)'] + $all,
            'homeroom'    => array_diff_key($all, ['assignments' => true]), // Wali Kelas tanpa Penugasan
            default       => $all, // counselor (Guru BK)
        };
    }

    /**
     * Bangun seluruh section dari daftar fitur terpilih.
     * @param string[] $featureKeys
     * @return array<int,array{title:string,columns:array,rows:array}>
     */
    public function buildSections(array $featureKeys, array $filter, array $scope): array
    {
        $sections = [];
        foreach ($featureKeys as $key) {
            foreach ($this->sectionsForFeature((string) $key, $filter, $scope) as $sec) {
                $sections[] = $sec;
            }
        }
        return $sections;
    }

    /**
     * @return array<int,array{title:string,columns:array,rows:array}>
     */
    private function sectionsForFeature(string $key, array $filter, array $scope): array
    {
        $catalog = self::featureCatalog((string) ($scope['role'] ?? 'counselor'));
        if (!array_key_exists($key, $catalog)) {
            return [];
        }

        switch ($key) {
            case 'aggregate':            return $this->sectionAggregate($filter, $scope);
            case 'students':             return [$this->sectionStudents($filter, $scope)];
            case 'assignments':          return [$this->sectionAssignments($filter, $scope)];
            case 'consultations':        return [$this->sectionConsultations($filter, $scope)];
            case 'guidance':             return [$this->sectionServiceRecord('Bimbingan', $filter, $scope)];
            case 'counseling':           return [$this->sectionCounseling($filter, $scope)];
            case 'parent_collaboration': return [$this->sectionServiceRecord('Kolaborasi Orang Tua', $filter, $scope)];
            case 'home_visit':           return [$this->sectionServiceRecord('Kunjungan Rumah', $filter, $scope)];
            case 'case_conference':      return [$this->sectionServiceRecord('Konferensi Kasus', $filter, $scope)];
            case 'assessment':           return [$this->sectionAssessment($filter, $scope)];
            case 'career':               return [$this->sectionCareer($filter, $scope)];
            case 'university':           return [$this->sectionUniversity($filter, $scope)];
            default:                     return [];
        }
    }

    // ----- Helper scope -----

    private function studentIdsOfClass(int $classId): array
    {
        return array_map('intval', array_column(
            $this->db->table('students')->select('id')
                ->where('class_id', $classId)->where('deleted_at', null)
                ->get()->getResultArray(),
            'id'
        ));
    }

    /** @return array|null null = tanpa batasan (semua) */
    private function effectiveStudentIds(array $scope, array $filter): ?array
    {
        $allowed  = $scope['allowed_student_ids'] ?? null;
        $singleId = (int) ($filter['student_id'] ?? 0);

        if (!empty($scope['single']) && $singleId > 0) {
            if ($allowed === null || in_array($singleId, $allowed, true)) {
                return [$singleId];
            }
            return [0];
        }

        $classId = (int) ($filter['class_id'] ?? 0);
        if ($classId > 0) {
            $byClass = $this->studentIdsOfClass($classId);
            if ($allowed === null) {
                return $byClass ?: [0];
            }
            $inter = array_values(array_intersect($allowed, $byClass));
            return $inter ?: [0];
        }

        return $allowed;
    }

    /** @return array|null null = tanpa batasan (semua) */
    private function effectiveClassIds(array $scope, array $filter): ?array
    {
        $allowed = $scope['allowed_class_ids'] ?? null;
        $classId = (int) ($filter['class_id'] ?? 0);

        if ($classId > 0) {
            if ($allowed === null || in_array($classId, $allowed, true)) {
                return [$classId];
            }
            return [0];
        }

        return $allowed;
    }

    private function applyIn($builder, string $field, ?array $ids): void
    {
        if ($ids === null) {
            return;
        }
        if (empty($ids)) {
            $builder->where('1=0', null, false);
            return;
        }
        $builder->whereIn($field, $ids);
    }

    /**
     * Batasi bk_service_records ke siswa/kelas sasaran (target_student_id / target_class_id).
     */
    private function scopeServiceRecords($builder, ?array $studentIds, ?array $classIds): void
    {
        if ($studentIds === null && $classIds === null) {
            return; // semua
        }

        $builder->groupStart();
        if (is_array($studentIds)) {
            $builder->orWhereIn('bsr.target_student_id', empty($studentIds) ? [0] : $studentIds);
        }
        if (is_array($classIds)) {
            $builder->orWhereIn('bsr.target_class_id', empty($classIds) ? [0] : $classIds);
        }
        $builder->groupEnd();
    }

    private function humanizeAssessmentStatus($status): string
    {
        if ($status === null || $status === '') {
            return '-';
        }
        if (is_numeric($status)) {
            return match ((int) $status) {
                0 => 'Belum Mulai',
                1 => 'Sedang Dikerjakan',
                2 => 'Selesai',
                3 => 'Dinilai',
                default => (string) $status,
            };
        }
        $map = [
            'assigned' => 'Belum Mulai', 'not_started' => 'Belum Mulai',
            'in progress' => 'Sedang Dikerjakan', 'in_progress' => 'Sedang Dikerjakan', 'started' => 'Sedang Dikerjakan',
            'completed' => 'Selesai', 'done' => 'Selesai', 'graded' => 'Dinilai',
        ];
        return $map[strtolower(trim((string) $status))] ?? (string) $status;
    }

    private function emptySection(string $title, array $columns): array
    {
        return ['title' => $title, 'columns' => $columns, 'rows' => []];
    }

    // ----- Section: Data Siswa -----

    private function sectionStudents(array $filter, array $scope): array
    {
        $ids = $this->effectiveStudentIds($scope, $filter);
        $columns = ['NISN', 'NIK', 'Nama', 'JK', 'Kelas', 'Status'];

        $b = $this->db->table('students s')
            ->select('s.nisn, s.nik, u.full_name, s.gender, c.class_name, s.status')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.deleted_at', null);

        $this->applyIn($b, 's.id', $ids);
        if (!empty($filter['status'])) {
            $b->where('s.status', $filter['status']);
        }
        $b->orderBy('u.full_name', 'ASC');

        $rows = [];
        foreach ($b->get()->getResultArray() as $r) {
            $rows[] = [
                $r['nisn'] ?? '-',
                $r['nik'] ?? '-',
                $r['full_name'] ?? '-',
                $r['gender'] ?? '-',
                $r['class_name'] ?? '-',
                $r['status'] ?? '-',
            ];
        }

        return ['title' => 'Data Siswa', 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section: Penugasan / Tugas -----

    private function sectionAssignments(array $filter, array $scope): array
    {
        $columns = ['Batas Waktu', 'Judul', 'Jenis', 'Ditugaskan Ke', 'Sasaran', 'Prioritas', 'Status'];
        $title = 'Penugasan / Tugas';

        if (!$this->db->tableExists('bk_assignments')) {
            return $this->emptySection($title, $columns);
        }

        $b = $this->db->table('bk_assignments a')
            ->select('a.title, a.assignment_type, a.assignment_type_other, a.priority, a.status, a.due_at, a.assigned_at, a.created_at,
                       u.full_name AS assignee, c.class_name, su.full_name AS student_name')
            ->join('users u', 'u.id = a.assigned_to_user_id', 'left')
            ->join('classes c', 'c.id = a.class_id', 'left')
            ->join('students s', 's.id = a.student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->where('a.deleted_at', null);

        $uid = (int) ($scope['user_id'] ?? 0);
        if (($scope['role'] ?? '') === 'counselor' && $uid > 0) {
            $b->groupStart()
                ->where('a.assigned_to_user_id', $uid)
                ->orWhere('a.id IN (SELECT assignment_id FROM bk_assignment_targets WHERE target_type = "counselor" AND user_id = ' . $uid . ' AND deleted_at IS NULL)', null, false)
                ->groupEnd();
        }

        $classIds = $this->effectiveClassIds($scope, $filter);
        if (is_array($classIds)) {
            $b->whereIn('a.class_id', empty($classIds) ? [0] : $classIds);
        }

        if (!empty($filter['date_from'])) {
            $b->where('DATE(COALESCE(a.due_at, a.assigned_at, a.created_at)) >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('DATE(COALESCE(a.due_at, a.assigned_at, a.created_at)) <=', $filter['date_to']);
        }

        $b->orderBy('COALESCE(a.due_at, a.assigned_at, a.created_at)', 'DESC', false);

        $rows = [];
        foreach ($b->get()->getResultArray() as $r) {
            $type = (string) ($r['assignment_type'] ?? '-');
            if ($type === 'Lainnya' && !empty($r['assignment_type_other'])) {
                $type = (string) $r['assignment_type_other'];
            }
            $target = trim((string) ($r['class_name'] ?? ''));
            if (!empty($r['student_name'])) {
                $target = $target !== '' ? $target . ' / ' . $r['student_name'] : (string) $r['student_name'];
            }
            $rows[] = [
                !empty($r['due_at']) ? date('Y-m-d', strtotime((string) $r['due_at'])) : '-',
                $r['title'] ?? '-',
                $type,
                $r['assignee'] ?? '-',
                $target !== '' ? $target : '-',
                $r['priority'] ?? '-',
                $r['status'] ?? '-',
            ];
        }

        return ['title' => $title, 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section: Konsultasi & Pengaduan -----

    private function sectionConsultations(array $filter, array $scope): array
    {
        $columns = ['Tanggal', 'Judul', 'Jenis Laporan', 'Kategori', 'Prioritas', 'Status', 'Siswa'];
        $title = 'Konsultasi & Pengaduan';

        if (!$this->db->tableExists('consultation_complaints')) {
            return $this->emptySection($title, $columns);
        }

        $ids = $this->effectiveStudentIds($scope, $filter);

        $b = $this->db->table('consultation_complaints cc')
            ->select('cc.title, cc.request_type, cc.category, cc.priority, cc.status, cc.occurred_at, cc.created_at,
                       cc.subject_other_name, cc.visible_to_homeroom, su.full_name AS student_name')
            ->join('students s', 's.id = cc.subject_student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->where('cc.deleted_at', null);

        $this->applyIn($b, 'cc.subject_student_id', $ids);

        if (!empty($scope['mask_confidential'])) {
            // Wali Kelas hanya melihat yang ditandai terlihat untuk wali kelas
            $b->where('cc.visible_to_homeroom', 1);
        }

        if (!empty($filter['date_from'])) {
            $b->where('DATE(COALESCE(cc.occurred_at, cc.created_at)) >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('DATE(COALESCE(cc.occurred_at, cc.created_at)) <=', $filter['date_to']);
        }

        $b->orderBy('COALESCE(cc.occurred_at, cc.created_at)', 'DESC', false);

        $rows = [];
        foreach ($b->get()->getResultArray() as $r) {
            $date = $r['occurred_at'] ?? $r['created_at'] ?? null;
            $rows[] = [
                $date ? date('Y-m-d', strtotime((string) $date)) : '-',
                $r['title'] ?? '-',
                $r['request_type'] ?? '-',
                $r['category'] ?? '-',
                $r['priority'] ?? '-',
                $r['status'] ?? '-',
                $r['student_name'] ?: ($r['subject_other_name'] ?: '-'),
            ];
        }

        return ['title' => $title, 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section: Bimbingan / Kolaborasi Ortu / Kunjungan Rumah / Konferensi Kasus -----

    private function sectionServiceRecord(string $serviceType, array $filter, array $scope): array
    {
        $mask = !empty($scope['mask_confidential']);
        $studentIds = $this->effectiveStudentIds($scope, $filter);
        $classIds   = $this->effectiveClassIds($scope, $filter);

        if (!$this->db->tableExists('bk_service_records')) {
            return $this->emptySection($serviceType, ['Tanggal', 'Judul', 'Sasaran', 'Status']);
        }

        // Kolom & join detail per jenis layanan
        $detailJoin = null;
        $extraSelect = '';
        switch ($serviceType) {
            case 'Bimbingan':
                $detailJoin = ['guidances d', 'd.bk_service_record_id = bsr.id AND d.deleted_at IS NULL'];
                $extraSelect = ', d.guidance_type, d.material_topic';
                $columns = ['Tanggal', 'Judul', 'Jenis', 'Materi/Topik', 'Sasaran', 'Status', 'Durasi (m)'];
                break;
            case 'Kolaborasi Orang Tua':
                $detailJoin = ['parent_collaborations d', 'd.bk_service_record_id = bsr.id AND d.deleted_at IS NULL'];
                $extraSelect = ', d.parent_name, d.topic AS pc_topic';
                $columns = ['Tanggal', 'Judul', 'Orang Tua', 'Topik', 'Sasaran', 'Status'];
                break;
            case 'Kunjungan Rumah':
                $detailJoin = ['home_visits d', 'd.bk_service_record_id = bsr.id AND d.deleted_at IS NULL'];
                $extraSelect = ', d.problem_topic, d.visit_result';
                $columns = $mask
                    ? ['Tanggal', 'Judul', 'Topik Masalah', 'Sasaran', 'Status']
                    : ['Tanggal', 'Judul', 'Topik Masalah', 'Hasil', 'Sasaran', 'Status'];
                break;
            case 'Konferensi Kasus':
                $detailJoin = ['case_conferences d', 'd.bk_service_record_id = bsr.id AND d.deleted_at IS NULL'];
                $extraSelect = ', d.discussion_summary, d.decision_summary';
                $columns = $mask
                    ? ['Tanggal', 'Judul', 'Sasaran', 'Status'] // garis besar untuk Wali Kelas (rahasia disembunyikan)
                    : ['Tanggal', 'Judul', 'Ringkasan Diskusi', 'Keputusan', 'Sasaran', 'Status'];
                break;
            default:
                $columns = ['Tanggal', 'Judul', 'Sasaran', 'Status'];
        }

        $b = $this->db->table('bk_service_records bsr')
            ->select('bsr.title, bsr.status, bsr.held_at, bsr.scheduled_at, bsr.created_at, bsr.duration_minutes,
                       ctar.class_name AS target_class, su.full_name AS target_student' . $extraSelect)
            ->join('classes ctar', 'ctar.id = bsr.target_class_id', 'left')
            ->join('students s', 's.id = bsr.target_student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->where('bsr.service_type', $serviceType)
            ->where('bsr.deleted_at', null);

        if ($detailJoin) {
            $b->join($detailJoin[0], $detailJoin[1], 'left');
        }

        $this->scopeServiceRecords($b, $studentIds, $classIds);

        if (!empty($scope['counselor_id'])) {
            $b->where('bsr.counselor_id', (int) $scope['counselor_id']);
        }
        if (!empty($filter['date_from'])) {
            $b->where('DATE(COALESCE(bsr.held_at, bsr.scheduled_at, bsr.created_at)) >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('DATE(COALESCE(bsr.held_at, bsr.scheduled_at, bsr.created_at)) <=', $filter['date_to']);
        }

        $b->orderBy('COALESCE(bsr.held_at, bsr.scheduled_at, bsr.created_at)', 'DESC', false);

        $rows = [];
        foreach ($b->get()->getResultArray() as $r) {
            $date = $r['held_at'] ?? $r['scheduled_at'] ?? $r['created_at'] ?? null;
            $dateStr = $date ? date('Y-m-d', strtotime((string) $date)) : '-';
            $target = trim((string) ($r['target_class'] ?? ''));
            if (!empty($r['target_student'])) {
                $target = $target !== '' ? $target . ' / ' . $r['target_student'] : (string) $r['target_student'];
            }
            $target = $target !== '' ? $target : '-';

            switch ($serviceType) {
                case 'Bimbingan':
                    $rows[] = [$dateStr, $r['title'] ?? '-', $r['guidance_type'] ?? '-', $r['material_topic'] ?? '-', $target, $r['status'] ?? '-', $r['duration_minutes'] ?? '-'];
                    break;
                case 'Kolaborasi Orang Tua':
                    $rows[] = [$dateStr, $r['title'] ?? '-', $r['parent_name'] ?? '-', $r['pc_topic'] ?? '-', $target, $r['status'] ?? '-'];
                    break;
                case 'Kunjungan Rumah':
                    if ($mask) {
                        $rows[] = [$dateStr, $r['title'] ?? '-', $r['problem_topic'] ?? '-', $target, $r['status'] ?? '-'];
                    } else {
                        $rows[] = [$dateStr, $r['title'] ?? '-', $r['problem_topic'] ?? '-', $r['visit_result'] ?? '-', $target, $r['status'] ?? '-'];
                    }
                    break;
                case 'Konferensi Kasus':
                    if ($mask) {
                        $rows[] = [$dateStr, $r['title'] ?? '-', $target, $r['status'] ?? '-'];
                    } else {
                        $rows[] = [$dateStr, $r['title'] ?? '-', $r['discussion_summary'] ?? '-', $r['decision_summary'] ?? '-', $target, $r['status'] ?? '-'];
                    }
                    break;
                default:
                    $rows[] = [$dateStr, $r['title'] ?? '-', $target, $r['status'] ?? '-'];
            }
        }

        return ['title' => $serviceType, 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section: Konseling -----

    private function sectionCounseling(array $filter, array $scope): array
    {
        $mask = !empty($scope['mask_confidential']);
        $studentIds = $this->effectiveStudentIds($scope, $filter);
        $classIds   = $this->effectiveClassIds($scope, $filter);

        $columns = $mask
            ? ['Tanggal', 'Waktu', 'Jenis', 'Lokasi', 'Topik', 'Status']
            : ['Tanggal', 'Waktu', 'Jenis', 'Lokasi', 'Topik', 'Siswa/Kelas', 'Status', 'Durasi (m)'];

        $b = $this->db->table('counseling_sessions cs')
            ->select('cs.session_date, cs.session_time, cs.session_type, cs.location, cs.topic, cs.status,
                       cs.duration_minutes, cs.is_confidential, su.full_name AS student_name,
                       cstu.class_name AS student_class, ctar.class_name AS target_class')
            ->join('students s', 's.id = cs.student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->join('classes cstu', 'cstu.id = s.class_id', 'left')
            ->join('classes ctar', 'ctar.id = cs.class_id', 'left')
            ->where('cs.deleted_at', null);

        // scope siswa/kelas
        if ($studentIds !== null || $classIds !== null) {
            $b->groupStart();
            if (is_array($studentIds)) {
                $b->orWhereIn('cs.student_id', empty($studentIds) ? [0] : $studentIds);
            }
            if (is_array($classIds)) {
                $b->orWhereIn('cs.class_id', empty($classIds) ? [0] : $classIds);
            }
            $b->groupEnd();
        }

        if (!empty($scope['counselor_id'])) {
            $b->where('cs.counselor_id', (int) $scope['counselor_id']);
        }
        if (!empty($filter['date_from'])) {
            $b->where('cs.session_date >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('cs.session_date <=', $filter['date_to']);
        }

        $b->orderBy('cs.session_date', 'DESC')->orderBy('cs.session_time', 'DESC');

        $rows = [];
        foreach ($b->get()->getResultArray() as $r) {
            $type = (string) ($r['session_type'] ?? 'Individu');
            $confidential = (int) ($r['is_confidential'] ?? 0) === 1;
            $topic = $confidential ? 'Konseling (Terbatas)' : ($r['topic'] ?? '-');
            $time  = !empty($r['session_time']) ? date('H:i', strtotime((string) $r['session_time'])) : '-';
            $target = $type === 'Klasikal'
                ? ($r['target_class'] ?? '-')
                : trim((string) ($r['student_name'] ?? '-') . ' (' . (string) ($r['student_class'] ?? '-') . ')');

            if ($mask) {
                $rows[] = [$r['session_date'] ?? '-', $time, $type, $r['location'] ?? '-', $topic, $r['status'] ?? '-'];
            } else {
                $rows[] = [$r['session_date'] ?? '-', $time, $type, $r['location'] ?? '-', $topic, $target, $r['status'] ?? '-', $r['duration_minutes'] ?? '-'];
            }
        }

        return ['title' => 'Konseling', 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section: Asesmen -----

    private function sectionAssessment(array $filter, array $scope): array
    {
        $mask = !empty($scope['mask_confidential']);
        $ids = $this->effectiveStudentIds($scope, $filter);

        if ($mask) {
            // Wali Kelas: rekap per asesmen (tanpa skor individu)
            $columns = ['Asesmen', 'Jenis', 'Ditugaskan', 'Selesai'];
            $b = $this->db->table('assessment_results ar')
                ->select('a.title, a.assessment_type, COUNT(*) AS assigned,
                           SUM(CASE WHEN ar.status IN ("Completed","Graded") OR ar.status IN (2,3) THEN 1 ELSE 0 END) AS completed')
                ->join('assessments a', 'a.id = ar.assessment_id', 'left')
                ->where('ar.deleted_at', null)
                ->groupBy('ar.assessment_id, a.title, a.assessment_type');

            $this->applyIn($b, 'ar.student_id', $ids);
            if (!empty($filter['date_from'])) {
                $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) >=', $filter['date_from']);
            }
            if (!empty($filter['date_to'])) {
                $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) <=', $filter['date_to']);
            }
            $b->orderBy('a.title', 'ASC');

            $rows = [];
            foreach ($b->get()->getResultArray() as $r) {
                $rows[] = [$r['title'] ?? '-', $r['assessment_type'] ?? '-', (int) ($r['assigned'] ?? 0), (int) ($r['completed'] ?? 0)];
            }
            return ['title' => 'Asesmen (Rekap)', 'columns' => $columns, 'rows' => $rows];
        }

        // Guru BK / Koordinator: per siswa
        $columns = ['Asesmen', 'Jenis', 'Siswa', 'Kelas', 'Status', 'Persentase', 'Selesai'];
        $b = $this->db->table('assessment_results ar')
            ->select('a.title, a.assessment_type, su.full_name AS student, c.class_name, ar.status, ar.percentage, ar.completed_at')
            ->join('assessments a', 'a.id = ar.assessment_id', 'left')
            ->join('students s', 's.id = ar.student_id', 'left')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('ar.deleted_at', null);

        $this->applyIn($b, 'ar.student_id', $ids);
        if (!empty($scope['counselor_id'])) {
            $b->where('a.created_by', (int) $scope['counselor_id']);
        }
        if (!empty($filter['date_from'])) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) >=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $b->where('DATE(COALESCE(ar.started_at, ar.created_at)) <=', $filter['date_to']);
        }
        $b->orderBy('a.title', 'ASC')->orderBy('su.full_name', 'ASC');

        $rows = [];
        foreach ($b->get()->getResultArray() as $r) {
            $rows[] = [
                $r['title'] ?? '-',
                $r['assessment_type'] ?? '-',
                $r['student'] ?? '-',
                $r['class_name'] ?? '-',
                $this->humanizeAssessmentStatus($r['status'] ?? null),
                ($r['percentage'] !== null && $r['percentage'] !== '') ? ((string) $r['percentage'] . '%') : '-',
                !empty($r['completed_at']) ? date('Y-m-d', strtotime((string) $r['completed_at'])) : '-',
            ];
        }
        return ['title' => 'Asesmen', 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section: Info Karier (pilihan siswa) -----

    private function sectionCareer(array $filter, array $scope): array
    {
        $columns = ['Karier', 'Sektor', 'Min. Pendidikan', 'Jumlah Siswa', 'Total Simpan'];
        $ids = $this->effectiveStudentIds($scope, $filter);

        $b = $this->db->table('student_saved_careers ssc')
            ->select('co.title, co.sector, co.min_education, COUNT(DISTINCT ssc.student_id) AS students_count, COUNT(*) AS saved_count')
            ->join('career_options co', 'co.id = ssc.career_id', 'left')
            ->where('ssc.deleted_at', null)
            ->where('co.deleted_at', null)
            ->groupBy('ssc.career_id, co.title, co.sector, co.min_education');

        $this->applyIn($b, 'ssc.student_id', $ids);
        if (!empty($filter['date_from'])) {
            $b->where('ssc.created_at >=', $filter['date_from'] . ' 00:00:00');
        }
        if (!empty($filter['date_to'])) {
            $b->where('ssc.created_at <=', $filter['date_to'] . ' 23:59:59');
        }

        $rows = [];
        foreach ($b->orderBy('students_count', 'DESC')->get()->getResultArray() as $r) {
            $rows[] = [$r['title'] ?? '-', $r['sector'] ?? '-', $r['min_education'] ?? '-', (int) ($r['students_count'] ?? 0), (int) ($r['saved_count'] ?? 0)];
        }
        return ['title' => 'Info Karier (Pilihan Siswa)', 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section: Info Studi Lanjut (pilihan siswa) -----

    private function sectionUniversity(array $filter, array $scope): array
    {
        $columns = ['Perguruan Tinggi', 'Akreditasi', 'Lokasi', 'Jumlah Siswa', 'Total Simpan'];
        $ids = $this->effectiveStudentIds($scope, $filter);

        $b = $this->db->table('student_saved_universities ssu')
            ->select('ui.university_name, ui.accreditation, ui.location, COUNT(DISTINCT ssu.student_id) AS students_count, COUNT(*) AS saved_count')
            ->join('university_info ui', 'ui.id = ssu.university_id', 'left')
            ->where('ssu.deleted_at', null)
            ->where('ui.deleted_at', null)
            ->groupBy('ssu.university_id, ui.university_name, ui.accreditation, ui.location');

        $this->applyIn($b, 'ssu.student_id', $ids);
        if (!empty($filter['date_from'])) {
            $b->where('ssu.created_at >=', $filter['date_from'] . ' 00:00:00');
        }
        if (!empty($filter['date_to'])) {
            $b->where('ssu.created_at <=', $filter['date_to'] . ' 23:59:59');
        }

        $rows = [];
        foreach ($b->orderBy('students_count', 'DESC')->get()->getResultArray() as $r) {
            $rows[] = [$r['university_name'] ?? '-', $r['accreditation'] ?? '-', $r['location'] ?? '-', (int) ($r['students_count'] ?? 0), (int) ($r['saved_count'] ?? 0)];
        }
        return ['title' => 'Info Studi Lanjut (Pilihan Siswa)', 'columns' => $columns, 'rows' => $rows];
    }

    // ----- Section(s): Rekap Sekolah/Kelas (Agregat) — Koordinator -----

    private function sectionAggregate(array $filter, array $scope): array
    {
        $data = $this->schoolAggregate(
            $filter['date_from'] ?? null,
            $filter['date_to'] ?? null,
            !empty($filter['class_id']) ? (int) $filter['class_id'] : null,
            !empty($scope['counselor_id']) ? (int) $scope['counselor_id'] : null,
            null
        );

        $kpi = $data['kpi'] ?? [];
        $sections = [];

        $sections[] = [
            'title'   => 'Rekap — Ringkasan (KPI)',
            'columns' => ['Indikator', 'Nilai'],
            'rows'    => [
                ['Total Siswa', (int) ($kpi['students_total'] ?? 0)],
                ['Total Catatan Konseling', (int) ($kpi['sessions_total'] ?? 0)],
                ['Total Durasi Konseling (menit)', (int) ($kpi['sessions_duration_total'] ?? 0)],
                ['Asesmen Ditugaskan', (int) ($kpi['assessments_assigned'] ?? 0)],
                ['Asesmen Selesai', (int) ($kpi['assessments_completed'] ?? 0)],
                ['Rata-rata Nilai Asesmen (%)', (string) ($kpi['assessments_avg_percentage'] ?? 0)],
            ],
        ];

        $sections[] = [
            'title'   => 'Rekap — Konseling per Jenis',
            'columns' => ['Jenis', 'Jumlah', 'Durasi (menit)'],
            'rows'    => array_map(static fn ($r) => [(string) ($r['label'] ?? '-'), (int) ($r['count'] ?? 0), (int) ($r['duration'] ?? 0)], $data['sessions']['byType'] ?? []),
        ];

        $sections[] = [
            'title'   => 'Rekap — Konseling per Guru BK',
            'columns' => ['Guru BK', 'Jumlah', 'Durasi (menit)'],
            'rows'    => array_map(static fn ($r) => [(string) ($r['label'] ?? '-'), (int) ($r['count'] ?? 0), (int) ($r['duration'] ?? 0)], $data['sessions']['byCounselor'] ?? []),
        ];

        $sections[] = [
            'title'   => 'Rekap — Asesmen per Judul',
            'columns' => ['Asesmen', 'Ditugaskan', 'Selesai', 'Rata-rata (%)'],
            'rows'    => array_map(static fn ($r) => [(string) ($r['label'] ?? '-'), (int) ($r['assigned'] ?? 0), (int) ($r['completed'] ?? 0), (string) ($r['avg_percentage'] ?? 0)], $data['assessments']['byAssessment'] ?? []),
        ];

        return $sections;
    }
}
