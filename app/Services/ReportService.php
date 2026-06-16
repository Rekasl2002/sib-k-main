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
}
