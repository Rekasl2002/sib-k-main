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

class StudentController extends BaseController
{
    protected StudentModel $studentModel;
    protected UserModel $userModel;
    protected ClassModel $classModel;
    protected $db;

    public function __construct()
    {
        helper(['auth']);
        $this->studentModel     = new StudentModel();
        $this->userModel        = new UserModel();
        $this->classModel       = new ClassModel();
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
                'students.nisn',
                'students.nik',
                'students.gender',
                'students.status',
                'students.admission_date',
                'students.created_at',
                'students.birth_place',
                'students.birth_date',
                'students.religion',
                'students.address',
                'students.special_needs',
                'students.disability',
                'students.kip_pip_number',
                'students.father_name',
                'students.mother_name',
                'students.guardian_name',

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
                ->orLike('students.nisn', $q)
                ->orLike('students.nik', $q)
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
        $academicYearOptions = [];

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
            'status'         => 'permit_empty|in_list[Aktif,Alumni,Pindah,Keluar,Tidak Aktif]',
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
}
