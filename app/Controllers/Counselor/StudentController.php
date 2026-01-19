<?php
/**
 * File Path: app/Controllers/Counselor/StudentController.php
 * Counselor Students (RU only)
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Services\ViolationService;

class StudentController extends BaseController
{
    protected StudentModel $studentModel;
    protected UserModel $userModel;
    protected ClassModel $classModel;
    protected ViolationService $violationService;
    protected $db;

    public function __construct()
    {
        helper(['auth']);
        $this->studentModel     = new StudentModel();
        $this->userModel        = new UserModel();
        $this->classModel       = new ClassModel();
        $this->violationService = new ViolationService();
        $this->db               = \Config\Database::connect();
    }

    private function me(): int
    {
        return (int) (function_exists('auth_id') ? auth_id() : 0);
    }

    /**
     * Builder data siswa + scope ke counselor aktif.
     * Kolom disusun agar kompatibel dengan view "mirip admin".
     */
    private function scopedBuilder()
    {
        $uid = $this->me();

        // Pakai model instance baru agar query lain tidak saling "ketiban"
        $model = new StudentModel();

        return $model
            ->asArray()
            ->select([
                // students
                'students.id',
                'students.user_id',
                'students.class_id',
                'students.nis',
                'students.nisn',
                'students.gender',
                'students.status',
                'students.admission_date',
                'students.created_at',
                'students.birth_place',
                'students.birth_date',
                'students.religion',
                'students.address',
                'COALESCE(students.total_violation_points,0) AS total_violation_points',

                // users (akun siswa)
                'u.full_name',
                'u.username',
                'u.email',
                'u.phone',
                'u.profile_photo',
                'u.is_active AS is_active',
                'u.last_login',
                'u.created_at AS user_created_at',

                // kelas
                'c.class_name',
                'c.grade_level',
                'c.counselor_id',

                // orang tua (opsional)
                'p.id AS parent_id',
                'p.full_name AS parent_name',
                'p.phone AS parent_phone',
            ])
            ->join('users u', 'u.id = students.user_id AND u.deleted_at IS NULL', 'inner')
            ->join('classes c', 'c.id = students.class_id AND c.deleted_at IS NULL', 'left')
            ->join('users p', 'p.id = students.parent_id AND p.deleted_at IS NULL', 'left')
            ->where('students.deleted_at', null)
            // hanya siswa yang kelasnya dibina counselor login
            ->where('c.counselor_id', $uid);
    }

    /**
     * GET /counselor/students
     * Filter: class_id, grade_level, status, gender, search
     * Pagination: DIURUS VIEW (DataTables), controller tidak pakai paginate()
     */
    public function index()
    {
        $uid = $this->me();

        $filters = [
            'class_id'    => trim((string) ($this->request->getGet('class_id') ?? '')),
            'grade_level' => trim((string) ($this->request->getGet('grade_level') ?? '')),
            'status'      => trim((string) ($this->request->getGet('status') ?? '')),
            'gender'      => trim((string) ($this->request->getGet('gender') ?? '')),
            'search'      => trim((string) ($this->request->getGet('search') ?? '')),
        ];

        $builder = $this->scopedBuilder();

        // Terapkan filter
        if ($filters['class_id'] !== '') {
            $builder->where('students.class_id', (int) $filters['class_id']);
        }
        if ($filters['grade_level'] !== '') {
            $builder->where('c.grade_level', $filters['grade_level']);
        }
        if ($filters['status'] !== '') {
            $builder->where('students.status', $filters['status']);
        }
        if ($filters['gender'] !== '') {
            $builder->where('students.gender', $filters['gender']);
        }
        if ($filters['search'] !== '') {
            $q = $filters['search'];
            $builder->groupStart()
                ->like('u.full_name', $q)
                ->orLike('u.email', $q)
                ->orLike('students.nis', $q)
                ->orLike('students.nisn', $q)
            ->groupEnd();
        }

        // Ambil semua hasil (pagination diurus DataTables pada view)
        $students = $builder
            ->orderBy('c.class_name', 'ASC')
            ->orderBy('u.full_name', 'ASC')
            ->findAll();

        // Dropdown kelas: hanya kelas binaan counselor
        $classes = $this->classModel->asArray()
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('counselor_id', $uid)
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();

        // Statistik scope counselor (builder baru tiap hitung)
        $stats = [
            'total'   => $this->scopedBuilder()->countAllResults(),
            'active'  => $this->scopedBuilder()->where('students.status', 'Aktif')->countAllResults(),
            'alumni'  => $this->scopedBuilder()->where('students.status', 'Alumni')->countAllResults(),
            'moved'   => $this->scopedBuilder()->where('students.status', 'Pindah')->countAllResults(),
            'dropped' => $this->scopedBuilder()->where('students.status', 'Keluar')->countAllResults(),
        ];

        $statusOptions = ['Aktif', 'Alumni', 'Pindah', 'Keluar'];
        $genderOptions = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

        // Opsi Tahun Ajaran (untuk dropdown di tombol sinkron)
        $academicYearOptions = [];
        try {
            if (method_exists($this->violationService, 'getAcademicYearOptions')) {
                $academicYearOptions = (array) $this->violationService->getAcademicYearOptions();
            }
        } catch (\Throwable $e) {
            $academicYearOptions = [];
        }

        $data = [
            'title'          => 'Siswa Binaan',
            'page_title'     => 'Siswa Binaan',

            'students'       => $students,
            'classes'        => $classes,
            'filters'        => $filters,
            'stats'          => $stats,
            'status_options' => $statusOptions,
            'gender_options' => $genderOptions,

            // IMPORTANT: samakan dengan yang dibaca view (agar dropdown aktif)
            'academicYears'  => $academicYearOptions, // view kamu sudah support $academicYears
            'academic_years' => $academicYearOptions, // fallback kalau view pakai snake
            'year_options'   => $academicYearOptions, // fallback

            // tetap boleh dipertahankan kalau ada bagian lain yang sudah pakai key ini
            'academic_year_options' => $academicYearOptions,

            'canCreate'      => false,
            'canDelete'      => false,
            'canImport'      => false,
            'canExport'      => false,
            'canUpdate'      => true,
        ];

        return view('counselor/students/index', $data);
    }

    public function show(int $id)
    {
        $student = $this->scopedBuilder()
            ->where('students.id', $id)
            ->first();

        if (!$student) {
            return redirect()->to('counselor/students')
                ->with('error', 'Siswa tidak ditemukan atau bukan binaan Anda.');
        }

        return view('counselor/students/profile', [
            'title'      => 'Profil Siswa',
            'page_title' => 'Profil Siswa',
            'student'    => $student,
        ]);
    }

    public function edit(int $id)
    {
        $uid = $this->me();

        $student = $this->scopedBuilder()
            ->where('students.id', $id)
            ->first();

        if (!$student) {
            return redirect()->to('counselor/students')
                ->with('error', 'Siswa tidak ditemukan atau bukan binaan Anda.');
        }

        $classes = $this->classModel->asArray()
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('counselor_id', $uid)
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();

        $data = [
            'title'            => 'Edit Siswa',
            'page_title'       => 'Edit Siswa',

            'student'          => $student,
            'classes'          => $classes,
            'parents'          => [],
            'status_options'   => ['Aktif', 'Alumni', 'Pindah', 'Keluar'],
            'gender_options'   => ['L' => 'Laki-laki', 'P' => 'Perempuan'],
            'religion_options' => ['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'],
        ];

        return view('counselor/students/edit', $data);
    }

    public function update(int $id)
    {
        $uid = $this->me();

        $exists = $this->scopedBuilder()->where('students.id', $id)->first();
        if (!$exists) {
            return redirect()->to('counselor/students')->with('error', 'Anda tidak memiliki akses ke siswa ini.');
        }

        $rules = [
            'full_name'      => 'permit_empty|min_length[3]|max_length[100]',
            'gender'         => 'permit_empty|in_list[L,P]',
            'class_id'       => 'permit_empty|is_natural_no_zero',
            'phone'          => 'permit_empty|max_length[30]',
            'birth_place'    => 'permit_empty|max_length[100]',
            'birth_date'     => 'permit_empty|valid_date',
            'religion'       => 'permit_empty|max_length[20]',
            'admission_date' => 'permit_empty|valid_date',
            'address'        => 'permit_empty|max_length[255]',
            'status'         => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar]',
            'parent_id'      => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = (array) $this->request->getPost();

        // Update ke tabel users (full_name + phone)
        $userId = (int) ($exists['user_id'] ?? 0);
        if ($userId > 0) {
            $userPayload = [];

            if (!empty($post['full_name'])) {
                $userPayload['full_name'] = $post['full_name'];
            }
            if (array_key_exists('phone', $post) && $post['phone'] !== '') {
                $userPayload['phone'] = $post['phone'];
            }

            if (!empty($userPayload)) {
                $this->userModel->update($userId, $userPayload);
            }
        }

        // Update ke tabel students (hindari update kolom yang sebenarnya milik users)
        $allowedStudent = [
            'gender','class_id','birth_place','birth_date','religion',
            'admission_date','address','status','parent_id'
        ];
        $data = array_intersect_key($post, array_flip($allowedStudent));

        if (!empty($data['class_id']))  $data['class_id']  = (int) $data['class_id'];
        if (!empty($data['parent_id'])) $data['parent_id'] = (int) $data['parent_id'];

        // Security: kalau ganti class, pastikan kelas tersebut memang kelas binaan counselor ini
        if (!empty($data['class_id'])) {
            $okClass = $this->db->table('classes')
                ->select('id')
                ->where('deleted_at', null)
                ->where('is_active', 1)
                ->where('counselor_id', $uid)
                ->where('id', (int) $data['class_id'])
                ->get(1)->getRowArray();

            if (!$okClass) {
                return redirect()->back()->withInput()->with('error', 'Kelas tidak valid atau bukan binaan Anda.');
            }
        }

        if (!$this->studentModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan perubahan.');
        }

        return redirect()->to('counselor/students/'.$id)->with('success', 'Data siswa diperbarui.');
    }

    public function detail($id = null)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to('counselor/students')->with('error', 'Siswa tidak ditemukan.');
        }
        return $this->show($id);
    }

    // =========================
    // Sinkron poin (scoped + filter TA/periode)
    // =========================

    private function normalizeDate($date): ?string
    {
        $date = trim((string) ($date ?? ''));
        if ($date === '') return null;

        // format YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }
        return $date;
    }

    private function resolveYearNameFromAcademicYearId(int $academicYearId): ?string
    {
        if ($academicYearId <= 0) return null;

        $row = $this->db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('id', $academicYearId)
            ->get(1)
            ->getRowArray();

        $yn = trim((string) ($row['year_name'] ?? ''));
        return $yn !== '' ? $yn : null;
    }

    private function resolveActiveYearName(): ?string
    {
        // 1) Prefer academic_years.is_active=1
        $row = $this->db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('updated_at', 'DESC')
            ->get(1)
            ->getRowArray();

        $yn = trim((string) ($row['year_name'] ?? ''));
        if ($yn !== '') return $yn;

        // 2) Fallback: yang mencakup hari ini
        $today = date('Y-m-d');
        $row = $this->db->table('academic_years')
            ->select('year_name')
            ->where('deleted_at', null)
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->orderBy('start_date', 'DESC')
            ->get(1)
            ->getRowArray();

        $yn = trim((string) ($row['year_name'] ?? ''));
        return $yn !== '' ? $yn : null;
    }

    private function resolveRangeFromYearName(?string $yearName): array
    {
        $yearName = trim((string) ($yearName ?? ''));
        if ($yearName === '') {
            return ['year_name' => null, 'date_from' => null, 'date_to' => null];
        }

        $range = $this->db->table('academic_years')
            ->select('MIN(start_date) as date_from, MAX(end_date) as date_to', false)
            ->where('deleted_at', null)
            ->where('year_name', $yearName)
            ->get()
            ->getRowArray();

        return [
            'year_name' => $yearName,
            'date_from' => ($range['date_from'] ?? null) ?: null,
            'date_to'   => ($range['date_to'] ?? null) ?: null,
        ];
    }

    /**
     * Ambil daftar student_id binaan counselor login (untuk sinkron).
     */
    private function scopedStudentIdsForSync(int $counselorId): array
    {
        $rows = $this->db->table('students s')
            ->select('s.id')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'inner')
            ->where('s.deleted_at', null)
            ->where('c.counselor_id', $counselorId)
            ->get()
            ->getResultArray();

        $ids = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id > 0) $ids[] = $id;
        }
        return $ids;
    }

    public function syncViolationPoints()
    {
        if (function_exists('is_logged_in') && !is_logged_in()) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (
            function_exists('is_guru_bk') &&
            function_exists('is_koordinator') &&
            !is_guru_bk() && !is_koordinator()
        ) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        $uid = $this->me();
        if ($uid <= 0) {
            return redirect()->back()->with('error', 'User tidak valid.');
        }

        $studentIds = $this->scopedStudentIdsForSync($uid);
        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'Tidak ada siswa binaan yang bisa disinkronkan.');
        }

        $syncMode = trim((string) ($this->request->getPost('sync_mode') ?? 'active'));
        if ($syncMode === '') $syncMode = 'active';

        $yearName = trim((string) ($this->request->getPost('academic_year') ?? ''));
        $yearId   = (int) ($this->request->getPost('academic_year_id') ?? 0);

        $dateFrom = $this->normalizeDate($this->request->getPost('date_from') ?? null);
        $dateTo   = $this->normalizeDate($this->request->getPost('date_to') ?? null);

        $range = ['mode' => 'active_year', 'year_name' => null, 'date_from' => null, 'date_to' => null];

        if ($syncMode === 'range') {
            if (!$dateFrom && !$dateTo) {
                return redirect()->back()->with('error', 'Mode periode dipilih, tapi tanggal belum diisi.');
            }
            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
            $range = [
                'mode'      => 'custom_range',
                'year_name' => null,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ];
        } elseif ($syncMode === 'year') {
            if ($yearName === '' && $yearId <= 0) {
                return redirect()->back()->with('error', 'Mode Tahun Ajaran dipilih, tapi Tahun Ajaran belum dipilih.');
            }
            if ($yearName === '' && $yearId > 0) {
                $yn = $this->resolveYearNameFromAcademicYearId($yearId);
                if ($yn) $yearName = $yn;
            }
            if ($yearName === '') {
                return redirect()->back()->with('error', 'Tahun Ajaran tidak valid.');
            }
            $r = $this->resolveRangeFromYearName($yearName);
            if (empty($r['date_from']) && empty($r['date_to'])) {
                return redirect()->back()->with('error', 'Range Tahun Ajaran tidak ditemukan. Pastikan academic_years punya start_date/end_date.');
            }
            $range = [
                'mode'      => 'selected_year',
                'year_name' => $r['year_name'],
                'date_from' => $r['date_from'],
                'date_to'   => $r['date_to'],
            ];
        } else {
            // active
            $activeYear = $this->resolveActiveYearName();
            $r = $this->resolveRangeFromYearName($activeYear);
            $range = [
                'mode'      => 'active_year',
                'year_name' => $r['year_name'],
                'date_from' => $r['date_from'],
                'date_to'   => $r['date_to'],
            ];
        }

        // Hitung total poin per siswa dalam 1 query
        $qb = $this->db->table('violations v')
            ->select('v.student_id, COALESCE(SUM(vc.point_deduction),0) AS total_points', false)
            ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
            ->whereIn('v.student_id', $studentIds)
            ->where('v.deleted_at', null)
            ->where('v.status !=', 'Dibatalkan');

        if (!empty($range['date_from'])) {
            $qb->where('v.violation_date >=', $range['date_from']);
        }
        if (!empty($range['date_to'])) {
            $qb->where('v.violation_date <=', $range['date_to']);
        }

        $rows = $qb->groupBy('v.student_id')->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $sid = (int) ($r['student_id'] ?? 0);
            $pts = (int) ($r['total_points'] ?? 0);
            if ($sid > 0) $map[$sid] = max(0, $pts);
        }

        $payload = [];
        foreach ($studentIds as $sid) {
            $payload[] = [
                'id' => $sid,
                'total_violation_points' => (int) ($map[$sid] ?? 0),
            ];
        }

        try {
            $this->db->table('students')->updateBatch($payload, 'id');

            $label = '';
            if ($range['mode'] === 'custom_range') {
                $from = $range['date_from'] ?? '-';
                $to   = $range['date_to'] ?? '-';
                $label = "Periode {$from} s/d {$to}";
            } else {
                $yn = $range['year_name'] ?? '';
                $label = $yn !== '' ? "Tahun Ajaran {$yn}" : 'Tahun Ajaran aktif';
            }

            return redirect()->back()->with(
                'success',
                'Sinkronisasi poin pelanggaran berhasil untuk ' . count($studentIds) . ' siswa binaan. (' . $label . ')'
            );
        } catch (\Throwable $e) {
            log_message('error', 'StudentController::syncViolationPoints - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal sinkronisasi poin: ' . $e->getMessage());
        }
    }
}
