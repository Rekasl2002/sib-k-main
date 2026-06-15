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
use App\Services\StudentService;

class StudentController extends BaseController
{
    protected StudentModel $studentModel;
    protected UserModel $userModel;
    protected ClassModel $classModel;
    protected StudentService $studentService;
    protected $db;

    public function __construct()
    {
        helper(['auth']);
        $this->studentModel     = new StudentModel();
        $this->userModel        = new UserModel();
        $this->classModel       = new ClassModel();
        $this->studentService   = new StudentService();
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
                'students.hobi',
                'students.ekskul_organisasi',
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
            'parents'          => $this->studentService->getAvailableParents(),
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

        $post = (array) $this->request->getPost();

        // Keamanan: bila kelas diubah, pastikan kelas tujuan adalah kelas binaan counselor ini.
        $newClassId = (int) ($post['class_id'] ?? 0);
        if ($newClassId > 0) {
            $okClass = $this->db->table('classes')
                ->select('id')
                ->where('deleted_at', null)
                ->where('is_active', 1)
                ->where('counselor_id', $uid)
                ->where('id', $newClassId)
                ->get(1)->getRowArray();

            if (!$okClass) {
                return redirect()->back()->withInput()->with('error', 'Kelas tidak valid atau bukan binaan Anda.');
            }
        }

        // Pakai service yang sama dengan Koordinator (sanitasi '' -> null, validasi server,
        // dukung semua field termasuk hobi/ekskul). Paritas penuh dalam lingkup binaan.
        $result = $this->studentService->updateStudent($id, $post);

        if (!($result['success'] ?? false)) {
            $redir = redirect()->back()->withInput();
            if (!empty($result['errors']) && is_array($result['errors'])) {
                $redir = $redir->with('errors', $result['errors']);
            }
            return $redir->with('error', $result['message'] ?? 'Gagal menyimpan perubahan.');
        }

        return redirect()->to('counselor/students/' . $id)->with('success', $result['message'] ?? 'Data siswa diperbarui.');
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
