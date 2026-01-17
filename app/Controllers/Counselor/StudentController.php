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

    public function __construct()
    {
        helper(['auth']);
        $this->studentModel     = new StudentModel();
        $this->userModel        = new UserModel();
        $this->classModel       = new ClassModel();
        $this->violationService = new ViolationService();
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
            ->join('users u', 'u.id = students.user_id')
            ->join('classes c', 'c.id = students.class_id', 'left')
            ->join('users p', 'p.id = students.parent_id', 'left')
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

        $data = [
            // Tambahan untuk judul tab (dibaca oleh layouts/partials/title-meta.php)
            'title'           => 'Siswa Binaan',
            'page_title'      => 'Siswa Binaan',

            'students'        => $students,
            'classes'         => $classes,
            'filters'         => $filters,
            'stats'           => $stats,
            'status_options'  => $statusOptions,
            'gender_options'  => $genderOptions,

            'canCreate'       => false,
            'canDelete'       => false,
            'canImport'       => false,
            'canExport'       => false,
            'canUpdate'       => true,
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
            // Tambahan untuk judul tab (dibaca oleh layouts/partials/title-meta.php)
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
            ->where('is_active', 1)
            ->where('counselor_id', $uid)
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();

        $data = [
            // Tambahan untuk judul tab (dibaca oleh layouts/partials/title-meta.php)
            'title'           => 'Edit Siswa',
            'page_title'      => 'Edit Siswa',

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

        $post = $this->request->getPost();

        if (!empty($post['full_name']) && !empty($exists['user_id'])) {
            $this->userModel->update($exists['user_id'], [
                'full_name' => $post['full_name']
            ]);
        }

        $allowed = [
            'gender','class_id','phone','birth_place','birth_date','religion',
            'admission_date','address','status','parent_id'
        ];
        $data = array_intersect_key($post, array_flip($allowed));

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

        $result = $this->violationService->syncAllStudentsViolationPoints();

        if (!empty($result['success'])) {
            return redirect()->back()->with(
                'success',
                $result['message'] ?? 'Poin pelanggaran berhasil disinkronkan.'
            );
        }

        return redirect()->back()->with(
            'error',
            $result['message'] ?? 'Gagal menyinkronkan poin pelanggaran.'
        );
    }
}
