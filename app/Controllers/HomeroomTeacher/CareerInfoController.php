<?php

/**
 * File Path: app/Controllers/HomeroomTeacher/CareerInfoController.php
 *
 * Wali Kelas - Info Karier dan Studi Lanjut
 * - Mengelola dan melihat daftar career_options & university_info
 * - Melihat rekap pilihan karier & universitas siswa per kelas perwalian
 */

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Models\CareerOptionModel;
use App\Models\UniversityInfoModel;
use CodeIgniter\Database\BaseConnection;

class CareerInfoController extends BaseController
{
    protected CareerOptionModel $careers;
    protected UniversityInfoModel $universities;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->careers      = new CareerOptionModel();
        $this->universities = new UniversityInfoModel();
        $this->db           = db_connect();

        // FIX: pastikan helper auth + permission tersedia
        helper(['auth', 'permission', 'form', 'url']);
    }

    /**
     * Ambil ID kelas perwalian milik wali kelas yang sedang login.
     *
     * Asumsi: tabel `classes` punya kolom `homeroom_teacher_id`
     * yang mengacu ke `users.id` wali kelas. (Sesuai skema DB sibk_mapersis31.sql)
     *
     * @return int|null
     */
    protected function getHomeroomClassId(): ?int
    {
        // FIX: gunakan helper yang benar (auth_id), bukan current_user_id
        $userId = auth_id();
        if (!$userId) {
            return null;
        }

        $row = $this->db->table('classes')
            ->select('id')
            ->where('homeroom_teacher_id', (int) $userId)
            ->where('is_active', 1)
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->get()
            ->getFirstRow('array');

        return $row ? (int) $row['id'] : null;
    }

    /**
     * Halaman utama Info Karier dan Studi Lanjut (Wali Kelas)
     * Tampilan & filter meniru Counselor\CareerInfoController::index(),
     * tapi hanya READ dan view diarahkan ke homeroom_teacher.
     */
    public function index()
    {
        require_permission(['manage_career_info', 'view_career_info']);

        // ------------------------------
        // Filters untuk Karier (careers)
        // ------------------------------
        $careerFilters = [
            'q'      => $this->request->getGet('q'),
            'sector' => $this->request->getGet('sector'),
            'edu'    => $this->request->getGet('edu'),
            'status' => $this->request->getGet('status'),
            'pub'    => $this->request->getGet('pub'),
            'sort'   => $this->request->getGet('sort'),
        ];

        // Gunakan alias tabel + join ke users untuk ambil nama pembuat
        $qb = $this->careers
            ->select('career_options.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = career_options.created_by', 'left');

        if (!empty($careerFilters['q'])) {
            $q = trim($careerFilters['q']);
            $qb = $qb->groupStart()
                ->like('career_options.title', $q)
                ->orLike('career_options.sector', $q)
                ->orLike('career_options.description', $q)
            ->groupEnd();
        }
        if (!empty($careerFilters['sector'])) {
            $qb->where('career_options.sector', $careerFilters['sector']);
        }
        if (!empty($careerFilters['edu'])) {
            $qb->where('career_options.min_education', $careerFilters['edu']);
        }
        // Filter status
        if ($careerFilters['status'] !== null && $careerFilters['status'] !== '') {
            $qb->where('career_options.is_active', (int) $careerFilters['status']);
        }
        // Filter publikasi
        if ($careerFilters['pub'] !== null && $careerFilters['pub'] !== '') {
            $qb->where('career_options.is_public', (int) $careerFilters['pub']);
        }
        // Sort
        if (!empty($careerFilters['sort']) && $careerFilters['sort'] === 'demand') {
            $qb->orderBy('career_options.demand_level', 'DESC');
        } else {
            $qb->orderBy('career_options.title', 'ASC');
        }

        // Tampilan disamakan dengan Manajemen Siswa: paginasi di sisi tampilan
        // (DataTables), jadi ambil seluruh baris hasil saringan.
        $careers = $qb->findAll();

        // ------------------------------
        // Listing & filter Universitas (satu halaman, satu jalur saring)
        // ------------------------------
        $uniFilters = [
            'q'      => $this->request->getGet('uq'),
            'acc'    => $this->request->getGet('uacc'),
            'loc'    => $this->request->getGet('uloc'),
            'status' => $this->request->getGet('ustatus'),
            'pub'    => $this->request->getGet('upub'),
            'sort'   => $this->request->getGet('usort'),
        ];

        $ub = $this->universities
            ->select('university_info.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = university_info.created_by', 'left');

        if (!empty($uniFilters['q'])) {
            $uq = trim($uniFilters['q']);
            $ub = $ub->groupStart()
                ->like('university_info.university_name', $uq)
                ->orLike('university_info.alias', $uq)
                ->orLike('university_info.description', $uq)
            ->groupEnd();
        }
        if (!empty($uniFilters['acc'])) {
            $ub->where('university_info.accreditation', $uniFilters['acc']);
        }
        if (!empty($uniFilters['loc'])) {
            $ub->where('university_info.location', $uniFilters['loc']);
        }
        if (($uniFilters['status'] ?? '') !== null && ($uniFilters['status'] ?? '') !== '') {
            $ub->where('university_info.is_active', (int) $uniFilters['status']);
        }
        if (($uniFilters['pub'] ?? '') !== null && ($uniFilters['pub'] ?? '') !== '') {
            $ub->where('university_info.is_public', (int) $uniFilters['pub']);
        }
        $usort = $uniFilters['sort'] ?? '';
        if ($usort === 'name_desc') {
            $ub->orderBy('university_info.university_name', 'DESC');
        } elseif ($usort === 'acc') {
            $ub->orderBy('university_info.accreditation', 'ASC')->orderBy('university_info.university_name', 'ASC');
        } elseif ($usort === 'loc') {
            $ub->orderBy('university_info.location', 'ASC')->orderBy('university_info.university_name', 'ASC');
        } else {
            $ub->orderBy('university_info.university_name', 'ASC');
        }
        $universities = $ub->findAll();

        $stats = [
            'careers_total'  => (new CareerOptionModel())->countAllResults(),
            'careers_active' => (new CareerOptionModel())->where('is_active', 1)->countAllResults(),
            'uni_total'      => (new UniversityInfoModel())->countAllResults(),
            'uni_active'     => (new UniversityInfoModel())->where('is_active', 1)->countAllResults(),
        ];

        $data = [
            'careers'       => $careers,
            'careerFilters' => $careerFilters,
            'universities'  => $universities,
            'uniFilters'    => $uniFilters,
            'stats'         => $stats,
            'activeTab'     => $this->request->getGet('tab') ?: 'careers',
        ];

        return view('homeroom_teacher/career/index', $data);
    }

    /**
     * Rekap pilihan Karier & Universitas siswa UNTUK SATU KELAS PERWALIAN
     * - Logika mirip Counselor\CareerInfoController::studentChoices()
     * - class_id SELALU dipaksa = kelas perwalian wali kelas yang login
     */
    public function studentChoices()
    {
        require_permission(['manage_career_info', 'view_career_info']);

        $req       = $this->request;
        $activeTab = $req->getGet('tab') === 'universities' ? 'universities' : 'careers';

        $q       = trim((string) ($req->getGet('q') ?? ''));
        $sort    = $req->getGet('sort') ?: '';
        $perPage = (int) ($req->getGet('per_page') ?: 10);
        if ($perPage <= 0)  $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        $classId = $this->getHomeroomClassId();
        if (!$classId) {
            return redirect()->to(route_to('homeroom.dashboard'))
                ->with('error', 'Kelas perwalian belum dikonfigurasi untuk akun wali kelas ini.');
        }

        $db           = $this->db;
        $hasCareerTbl = $db->tableExists('student_saved_careers');
        $hasUnivTbl   = $db->tableExists('student_saved_universities');

        $careerChoices = [];
        $careerPager   = null;
        $uniChoices    = [];
        $uniPager      = null;

        // -------------------------------------------------------------
        // Data pilihan karier siswa (hanya siswa kelas perwalian)
        // -------------------------------------------------------------
        if ($hasCareerTbl) {
            $cb = $this->careers
                ->select("
                    student_saved_careers.id          AS saved_id,
                    student_saved_careers.created_at  AS saved_at,
                    students.id                       AS student_id,
                    students.nisn                      AS nisn,
                    users.full_name                   AS student_name,
                    classes.id                        AS class_id,
                    classes.class_name                AS class_name,
                    classes.grade_level               AS grade_level,
                    career_options.id                 AS career_id,
                    career_options.title              AS career_title,
                    career_options.sector             AS sector,
                    career_options.min_education      AS min_education
                ")
                ->join(
                    'student_saved_careers',
                    'student_saved_careers.career_id = career_options.id',
                    'inner'
                )
                ->join(
                    'students',
                    'students.id = student_saved_careers.student_id',
                    'inner'
                )
                ->join('users', 'users.id = students.user_id', 'left')
                ->join('classes', 'classes.id = students.class_id', 'left')
                ->where('students.class_id', (int) $classId);

            if ($q !== '') {
                $cb->groupStart()
                    ->like('users.full_name', $q)
                    ->orLike('students.nisn', $q)
                    ->orLike('classes.class_name', $q)
                    ->orLike('career_options.title', $q)
                ->groupEnd();
            }

            // Sorting sederhana; bisa dikembangkan kalau perlu
            switch ($sort) {
                case 'student_desc':
                    $cb->orderBy('users.full_name', 'DESC');
                    break;
                case 'class':
                    $cb->orderBy('classes.grade_level', 'ASC')
                       ->orderBy('classes.class_name', 'ASC')
                       ->orderBy('users.full_name', 'ASC');
                    break;
                default:
                    $cb->orderBy('users.full_name', 'ASC')
                       ->orderBy('career_options.title', 'ASC');
            }

            $careerChoices = $cb->paginate($perPage, 'student_careers');
            $careerPager   = $this->careers->pager;
        }

        // -------------------------------------------------------------
        // Data pilihan PERGURUAN TINGGI siswa (hanya siswa kelas perwalian)
        // -------------------------------------------------------------
        if ($hasUnivTbl) {
            $ub = $this->universities
                ->select("
                    student_saved_universities.id         AS saved_id,
                    student_saved_universities.created_at AS saved_at,
                    students.id                           AS student_id,
                    students.nisn                          AS nisn,
                    users.full_name                       AS student_name,
                    classes.id                            AS class_id,
                    classes.class_name                    AS class_name,
                    classes.grade_level                   AS grade_level,
                    university_info.id                    AS university_id,
                    university_info.university_name       AS university_name,
                    university_info.accreditation         AS accreditation,
                    university_info.location              AS location
                ")
                ->join(
                    'student_saved_universities',
                    'student_saved_universities.university_id = university_info.id',
                    'inner'
                )
                ->join(
                    'students',
                    'students.id = student_saved_universities.student_id',
                    'inner'
                )
                ->join('users', 'users.id = students.user_id', 'left')
                ->join('classes', 'classes.id = students.class_id', 'left')
                ->where('students.class_id', (int) $classId);

            if ($q !== '') {
                $ub->groupStart()
                    ->like('users.full_name', $q)
                    ->orLike('students.nisn', $q)
                    ->orLike('classes.class_name', $q)
                    ->orLike('university_info.university_name', $q)
                    ->orLike('university_info.location', $q)
                ->groupEnd();
            }

            switch ($sort) {
                case 'student_desc':
                    $ub->orderBy('users.full_name', 'DESC');
                    break;
                case 'class':
                    $ub->orderBy('classes.grade_level', 'ASC')
                       ->orderBy('classes.class_name', 'ASC')
                       ->orderBy('users.full_name', 'ASC');
                    break;
                default:
                    $ub->orderBy('users.full_name', 'ASC')
                       ->orderBy('university_info.university_name', 'ASC');
            }

            $uniChoices = $ub->paginate($perPage, 'student_universities');
            $uniPager   = $this->universities->pager;
        }

        // -------------------------------------------------------------
        // Data kelas untuk filter (hanya kelas wali ini)
        // -------------------------------------------------------------
        $classes = $db->table('classes')
            ->select('id, class_name, grade_level')
            ->where('id', $classId)
            ->get()
            ->getResultArray();

        $data = [
            'activeTab' => $activeTab,
            'filters'   => [
                'q'        => $q,
                'class_id' => $classId,
                'sort'     => $sort,
                'per_page' => $perPage,
            ],
            'classes'           => $classes,
            'hasCareerTable'    => $hasCareerTbl,
            'hasUnivTable'      => $hasUnivTbl,
            'careerChoices'     => $careerChoices,
            'careerPager'       => $careerPager,
            'universityChoices' => $uniChoices,
            'universityPager'   => $uniPager,
        ];

        return view('homeroom_teacher/career/student_choices', $data);
    }

    public function createCareer()
    {
        require_permission('manage_career_info');

        return view('homeroom_teacher/career/form_career', [
            'title'  => 'Tambah Info Karier',
            'mode'   => 'create',
            'career' => [],
            'errors' => session('errors') ?? [],
        ]);
    }

    public function storeCareer()
    {
        require_permission('manage_career_info');

        if (!$this->validate([
            'title'       => 'required|min_length[3]',
            'description' => 'required|min_length[10]',
            'is_active'   => 'required|in_list[0,1]',
            'is_public'   => 'permit_empty|in_list[0,1]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = $this->buildCareerPayload();
        $payload['created_by'] = (int) session('user_id');
        $this->careers->insert($payload);

        return redirect()->to(site_url('homeroom/career-info?tab=careers'))
            ->with('success', 'Info karier berhasil ditambahkan.');
    }

    public function editCareer(int $id)
    {
        require_permission('manage_career_info');

        $career = $this->careers->find($id);
        if (!$career) {
            return redirect()->to(site_url('homeroom/career-info?tab=careers'))
                ->with('error', 'Info karier tidak ditemukan.');
        }

        $career['required_skills_array'] = !empty($career['required_skills'])
            ? (json_decode($career['required_skills'], true) ?: [])
            : [];

        return view('homeroom_teacher/career/form_career', [
            'title'  => 'Edit Info Karier',
            'mode'   => 'edit',
            'career' => $career,
            'errors' => session('errors') ?? [],
        ]);
    }

    public function updateCareer(int $id)
    {
        require_permission('manage_career_info');

        if (!$this->careers->find($id)) {
            return redirect()->to(site_url('homeroom/career-info?tab=careers'))
                ->with('error', 'Info karier tidak ditemukan.');
        }

        if (!$this->validate([
            'title'       => 'required|min_length[3]',
            'description' => 'required|min_length[10]',
            'is_active'   => 'required|in_list[0,1]',
            'is_public'   => 'permit_empty|in_list[0,1]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->careers->update($id, $this->buildCareerPayload());

        return redirect()->to(site_url('homeroom/career-info?tab=careers'))
            ->with('success', 'Info karier berhasil diperbarui.');
    }

    public function deleteCareer(int $id)
    {
        require_permission('manage_career_info');

        if ($this->careers->find($id)) {
            $this->careers->delete($id);
        }

        return redirect()->to(site_url('homeroom/career-info?tab=careers'))
            ->with('success', 'Info karier berhasil dihapus.');
    }

    public function toggleCareer(int $id)
    {
        require_permission('manage_career_info');

        $career = $this->careers->find($id);
        if (!$career) {
            return redirect()->to(site_url('homeroom/career-info?tab=careers'))
                ->with('error', 'Info karier tidak ditemukan.');
        }

        $this->careers->update($id, ['is_active' => (int)($career['is_active'] ?? 0) === 1 ? 0 : 1]);

        return redirect()->to(site_url('homeroom/career-info?tab=careers'))
            ->with('success', 'Status info karier berhasil diubah.');
    }

    public function toggleCareerPublic(int $id)
    {
        require_permission('manage_career_info');

        $career = $this->careers->find($id);
        if (!$career) {
            return redirect()->to(site_url('homeroom/career-info?tab=careers'))
                ->with('error', 'Info karier tidak ditemukan.');
        }

        $this->careers->update($id, ['is_public' => (int)($career['is_public'] ?? 0) === 1 ? 0 : 1]);

        return redirect()->to(site_url('homeroom/career-info?tab=careers'))
            ->with('success', 'Publikasi info karier berhasil diubah.');
    }

    public function universities()
    {
        require_permission(['manage_career_info', 'view_career_info']);

        $qs = $this->request->getGet();
        $qs['tab'] = 'universities';

        return redirect()->to(site_url('homeroom/career-info') . '?' . http_build_query($qs));
    }

    public function createUniversity()
    {
        require_permission('manage_career_info');

        return view('homeroom_teacher/career/form_university', [
            'title' => 'Tambah Info Studi Lanjut',
            'mode'  => 'create',
            'uni'   => [],
            'errors'=> session('errors') ?? [],
        ]);
    }

    public function storeUniversity()
    {
        require_permission('manage_career_info');

        if (!$this->validate([
            'university_name' => 'required|min_length[3]',
            'website'         => 'permit_empty|valid_url',
            'is_active'       => 'required|in_list[0,1]',
            'is_public'       => 'permit_empty|in_list[0,1]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = $this->buildUniversityPayload();
        $payload['created_by'] = (int) session('user_id');
        $this->universities->insert($payload);

        return redirect()->to(site_url('homeroom/career-info?tab=universities'))
            ->with('success', 'Info studi lanjut berhasil ditambahkan.');
    }

    public function editUniversity(int $id)
    {
        require_permission('manage_career_info');

        $uni = $this->universities->find($id);
        if (!$uni) {
            return redirect()->to(site_url('homeroom/career-info?tab=universities'))
                ->with('error', 'Info studi lanjut tidak ditemukan.');
        }

        return view('homeroom_teacher/career/form_university', [
            'title' => 'Edit Info Studi Lanjut',
            'mode'  => 'edit',
            'uni'   => $uni,
            'errors'=> session('errors') ?? [],
        ]);
    }

    public function updateUniversity(int $id)
    {
        require_permission('manage_career_info');

        if (!$this->universities->find($id)) {
            return redirect()->to(site_url('homeroom/career-info?tab=universities'))
                ->with('error', 'Info studi lanjut tidak ditemukan.');
        }

        if (!$this->validate([
            'university_name' => 'required|min_length[3]',
            'website'         => 'permit_empty|valid_url',
            'is_active'       => 'required|in_list[0,1]',
            'is_public'       => 'permit_empty|in_list[0,1]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->universities->update($id, $this->buildUniversityPayload());

        return redirect()->to(site_url('homeroom/career-info?tab=universities'))
            ->with('success', 'Info studi lanjut berhasil diperbarui.');
    }

    public function deleteUniversity(int $id)
    {
        require_permission('manage_career_info');

        if ($this->universities->find($id)) {
            $this->universities->delete($id);
        }

        return redirect()->to(site_url('homeroom/career-info?tab=universities'))
            ->with('success', 'Info studi lanjut berhasil dihapus.');
    }

    public function toggleUniversity(int $id)
    {
        require_permission('manage_career_info');

        $uni = $this->universities->find($id);
        if (!$uni) {
            return redirect()->to(site_url('homeroom/career-info?tab=universities'))
                ->with('error', 'Info studi lanjut tidak ditemukan.');
        }

        $this->universities->update($id, ['is_active' => (int)($uni['is_active'] ?? 0) === 1 ? 0 : 1]);

        return redirect()->to(site_url('homeroom/career-info?tab=universities'))
            ->with('success', 'Status info studi lanjut berhasil diubah.');
    }

    public function toggleUniversityPublic(int $id)
    {
        require_permission('manage_career_info');

        $uni = $this->universities->find($id);
        if (!$uni) {
            return redirect()->to(site_url('homeroom/career-info?tab=universities'))
                ->with('error', 'Info studi lanjut tidak ditemukan.');
        }

        $this->universities->update($id, ['is_public' => (int)($uni['is_public'] ?? 0) === 1 ? 0 : 1]);

        return redirect()->to(site_url('homeroom/career-info?tab=universities'))
            ->with('success', 'Publikasi info studi lanjut berhasil diubah.');
    }

    protected function buildCareerPayload(): array
    {
        $skills = array_values(array_filter(array_map('trim', (array)$this->request->getPost('skills'))));

        return [
            'title'           => trim((string)$this->request->getPost('title')),
            'sector'          => trim((string)$this->request->getPost('sector')) ?: null,
            'min_education'   => trim((string)$this->request->getPost('min_education')) ?: null,
            'description'     => trim((string)$this->request->getPost('description')),
            'required_skills' => $skills ? json_encode($skills, JSON_UNESCAPED_SLASHES) : null,
            'pathways'        => trim((string)$this->request->getPost('pathways')) ?: null,
            'avg_salary_idr'  => $this->request->getPost('avg_salary_idr') ?: null,
            'demand_level'    => (int)($this->request->getPost('demand_level') ?: 0),
            'is_active'       => (int)$this->request->getPost('is_active'),
            'is_public'       => (int)($this->request->getPost('is_public') ?? 0),
        ];
    }

    protected function buildUniversityPayload(): array
    {
        return [
            'university_name' => trim((string)$this->request->getPost('university_name')),
            'alias'           => trim((string)$this->request->getPost('alias')) ?: null,
            'accreditation'   => trim((string)$this->request->getPost('accreditation')) ?: null,
            'location'        => trim((string)$this->request->getPost('location')) ?: null,
            'website'         => $this->normalizeUrl($this->request->getPost('website')),
            'description'     => trim((string)$this->request->getPost('description')) ?: null,
            'admission_info'  => trim((string)$this->request->getPost('admission_info')) ?: null,
            'tuition_range'   => trim((string)$this->request->getPost('tuition_range')) ?: null,
            'logo'            => $this->normalizeUrl($this->request->getPost('logo')),
            'is_active'       => (int)$this->request->getPost('is_active'),
            'is_public'       => (int)($this->request->getPost('is_public') ?? 0),
        ];
    }

    protected function normalizeUrl($url): ?string
    {
        $url = trim((string)$url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
