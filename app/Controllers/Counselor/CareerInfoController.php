<?php
/**
 * File Path: app/Controllers/Counselor/CareerInfoController.php
 *
 * Career Info Controller (Guru BK)
 * Mengelola data career_options & university_info
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Models\CareerOptionModel;
use App\Models\UniversityInfoModel;

class CareerInfoController extends BaseController
{
    protected CareerOptionModel $careers;
    protected UniversityInfoModel $universities;

    public function __construct()
    {
        $this->careers      = new CareerOptionModel();
        $this->universities = new UniversityInfoModel();
        helper(['form', 'permission', 'url']);
    }

    /** Halaman utama: tab daftar karir & universitas (default tab: careers) */
    public function index()
    {
        require_permission('manage_career_info');

        // ------------------------------
        // Filters untuk Karir (careers)
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

        // Gunakan group 'careers' agar pager menghasilkan page_careers
        $careers     = $qb->paginate(10, 'careers');
        $careerPager = $this->careers->pager;

        // ------------------------------
        // Listing Universitas (minimal agar tab tidak kosong)
        // ------------------------------
        $universities = $this->universities
            ->select('university_info.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = university_info.created_by', 'left')
            ->orderBy('university_info.university_name', 'ASC')
            ->paginate(10, 'universities'); // pager siap bila tab dipindah
        $uniPager = $this->universities->pager;

        $data = [
            'careers'       => $careers,
            'careerPager'   => $careerPager,
            'careerFilters' => $careerFilters,
            'universities'  => $universities,
            'uniPager'      => $uniPager,
            'activeTab'     => $this->request->getGet('tab') ?: 'careers',
        ];

        return view('counselor/career/index', $data);
    }

    /**
     * Rekap pilihan karir & perguruan tinggi siswa
     * URL: /counselor/career-info/student-choices
     */
    public function studentChoices()
    {
        require_permission('manage_career_info');

        $req       = $this->request;
        $activeTab = $req->getGet('tab') === 'universities' ? 'universities' : 'careers';

        $q       = trim((string) ($req->getGet('q') ?? ''));
        $classId = $req->getGet('class_id');
        $sort    = $req->getGet('sort') ?: '';
        $perPage = (int) ($req->getGet('per_page') ?: 10);
        if ($perPage <= 0)  $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        $db           = db_connect();
        $hasCareerTbl = $db->tableExists('student_saved_careers');
        $hasUnivTbl   = $db->tableExists('student_saved_universities');

        $careerChoices = [];
        $careerPager   = null;
        $uniChoices    = [];
        $uniPager      = null;

        // -------------------------------------------------------------
        // Data pilihan KARIR siswa
        // -------------------------------------------------------------
        if ($hasCareerTbl) {
            $cb = $this->careers
                ->select("
                    student_saved_careers.id          AS saved_id,
                    student_saved_careers.created_at  AS saved_at,
                    students.id                       AS student_id,
                    students.nis                      AS nis,
                    users.full_name                   AS student_name,
                    classes.id                        AS class_id,
                    classes.class_name                AS class_name,
                    classes.grade_level               AS grade_level,
                    career_options.id                 AS career_id,
                    career_options.title              AS career_title,
                    career_options.sector             AS sector,
                    career_options.min_education      AS min_education
                ")
                ->join('student_saved_careers', 'student_saved_careers.career_id = career_options.id', 'inner')
                ->join('students', 'students.id = student_saved_careers.student_id', 'inner')
                ->join('users', 'users.id = students.user_id', 'left')
                ->join('classes', 'classes.id = students.class_id', 'left');

            if ($q !== '') {
                $cb->groupStart()
                    ->like('users.full_name', $q)
                    ->orLike('students.nis', $q)
                    ->orLike('career_options.title', $q)
                ->groupEnd();
            }

            if ($classId !== null && $classId !== '') {
                $cb->where('students.class_id', (int) $classId);
            }

            switch ($sort) {
                case 'student_desc':
                    $cb->orderBy('users.full_name', 'DESC');
                    break;
                case 'class':
                    $cb->orderBy('classes.grade_level', 'ASC')
                       ->orderBy('classes.class_name', 'ASC')
                       ->orderBy('users.full_name', 'ASC');
                    break;
                case 'career':
                    $cb->orderBy('career_options.title', 'ASC')
                       ->orderBy('users.full_name', 'ASC');
                    break;
                case 'latest':
                    $cb->orderBy('student_saved_careers.created_at', 'DESC');
                    break;
                default:
                    $cb->orderBy('users.full_name', 'ASC');
                    break;
            }

            $careerChoices = $cb->paginate($perPage, 'student_careers');
            $careerPager   = $this->careers->pager;
        }

        // -------------------------------------------------------------
        // Data pilihan PERGURUAN TINGGI siswa
        // -------------------------------------------------------------
        if ($hasUnivTbl) {
            $ub = $this->universities
                ->select("
                    student_saved_universities.id         AS saved_id,
                    student_saved_universities.created_at AS saved_at,
                    students.id                           AS student_id,
                    students.nis                          As nis,
                    users.full_name                       AS student_name,
                    classes.id                            AS class_id,
                    classes.class_name                    AS class_name,
                    classes.grade_level                   AS grade_level,
                    university_info.id                    AS university_id,
                    university_info.university_name       AS university_name,
                    university_info.location              AS location,
                    university_info.accreditation         AS accreditation
                ")
                ->join('student_saved_universities', 'student_saved_universities.university_id = university_info.id', 'inner')
                ->join('students', 'students.id = student_saved_universities.student_id', 'inner')
                ->join('users', 'users.id = students.user_id', 'left')
                ->join('classes', 'classes.id = students.class_id', 'left');

            if ($q !== '') {
                $ub->groupStart()
                    ->like('users.full_name', $q)
                    ->orLike('students.nis', $q)
                    ->orLike('university_info.university_name', $q)
                ->groupEnd();
            }

            if ($classId !== null && $classId !== '') {
                $ub->where('students.class_id', (int) $classId);
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
                case 'name':
                    $ub->orderBy('university_info.university_name', 'ASC')
                       ->orderBy('users.full_name', 'ASC');
                    break;
                case 'latest':
                    $ub->orderBy('student_saved_universities.created_at', 'DESC');
                    break;
                default:
                    $ub->orderBy('users.full_name', 'ASC');
                    break;
            }

            $uniChoices = $ub->paginate($perPage, 'student_universities');
            $uniPager   = $this->universities->pager;
        }

        // -------------------------------------------------------------
        // Data kelas untuk filter
        // -------------------------------------------------------------
        $classes = $db->table('classes')
            ->select('id, class_name, grade_level')
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
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

        return view('counselor/career/student_choices', $data);
    }

    /* -----------------------------------------------------------------
     *  Career Options (career_options)
     * -----------------------------------------------------------------*/

    public function createCareer()
    {
        require_permission('manage_career_info');

        $data = [
            'career' => null,
            'errors' => session('errors') ?? [],
            'mode'   => 'create',
        ];

        return view('counselor/career/form_career', $data);
    }

    public function storeCareer()
    {
        require_permission('manage_career_info');

        $rules = [
            'title'         => 'required|string|min_length[3]',
            'sector'        => 'permit_empty|string|max_length[100]',
            'min_education' => 'permit_empty|string|max_length[50]',
            'description'   => 'required|string',
            'avg_salary_idr'=> 'permit_empty|integer',
            'demand_level'  => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[10]',
            'is_active'     => 'required|in_list[0,1]',
            'is_public'     => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = $this->buildCareerPayload();

        // Audit: siapa yang membuat data career option
        $payload['created_by'] = (int) session('user_id');

        $this->careers->insert($payload);

        return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
            ->with('success', 'Pilihan karir berhasil ditambahkan.');
    }

    public function editCareer(int $id)
    {
        require_permission('manage_career_info');

        // Ambil data karir + nama pembuat (users.full_name)
        $career = $this->careers
            ->select('career_options.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = career_options.created_by', 'left')
            ->where('career_options.id', $id)
            ->first();

        if (! $career) {
            return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
                ->with('error', 'Data karir tidak ditemukan.');
        }

        // decode JSON untuk ditampilkan di form
        $career['required_skills_array'] = !empty($career['required_skills'])
            ? (json_decode($career['required_skills'], true) ?: [])
            : [];

        $career['external_links_array'] = !empty($career['external_links'])
            ? (json_decode($career['external_links'], true) ?: [])
            : [];

        $data = [
            'career' => $career,
            'errors' => session('errors') ?? [],
            'mode'   => 'edit',
        ];

        return view('counselor/career/form_career', $data);
    }

    public function updateCareer(int $id)
    {
        require_permission('manage_career_info');

        $career = $this->careers->find($id);
        if (! $career) {
            return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
                ->with('error', 'Data karir tidak ditemukan.');
        }

        $rules = [
            'title'         => 'required|string|min_length[3]',
            'sector'        => 'permit_empty|string|max_length[100]',
            'min_education' => 'permit_empty|string|max_length[50]',
            'description'   => 'required|string',
            'avg_salary_idr'=> 'permit_empty|integer',
            'demand_level'  => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[10]',
            'is_active'     => 'required|in_list[0,1]',
            'is_public'     => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = $this->buildCareerPayload();

        $this->careers->update($id, $payload);

        return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
            ->with('success', 'Pilihan karir berhasil diperbarui.');
    }

    public function deleteCareer(int $id)
    {
        require_permission('manage_career_info');

        $career = $this->careers->find($id);
        if ($career) {
            $this->careers->delete($id); // soft delete
        }

        return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
            ->with('success', 'Pilihan karir berhasil dihapus.');
    }

    public function toggleCareer(int $id)
    {
        require_permission('manage_career_info');

        $career = $this->careers->find($id);
        if (! $career) {
            return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
                ->with('error', 'Data karir tidak ditemukan.');
        }

        $newStatus = (int) ($career['is_active'] ?? 0) === 1 ? 0 : 1;
        $this->careers->update($id, ['is_active' => $newStatus]);

        return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
            ->with('success', 'Status karir berhasil diubah.');
    }

    public function toggleCareerPublic(int $id)
    {
        require_permission('manage_career_info');

        $career = $this->careers->find($id);
        if (! $career) {
            return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
                ->with('error', 'Data karir tidak ditemukan.');
        }

        $new = (int) ($career['is_public'] ?? 0) === 1 ? 0 : 1;
        $this->careers->update($id, ['is_public' => $new]);

        $msg = $new ? 'Karir dipublikasikan.' : 'Karir di-set private.';
        return redirect()->to(route_to('counselor.career.index') . '?tab=careers')
            ->with('success', $msg);
    }

    /** Bangun payload career_options + JSON */
    protected function buildCareerPayload(): array
    {
        $skills = $this->request->getPost('skills') ?? [];
        $skills = array_values(array_filter(array_map('trim', (array) $skills)));

        $linkUrls   = $this->request->getPost('links_url')   ?? [];
        $linkLabels = $this->request->getPost('links_label') ?? [];

        $links = [];
        foreach ((array) $linkUrls as $i => $url) {
            $url   = trim((string) $url);
            $label = trim((string) ($linkLabels[$i] ?? ''));
            if ($url === '') continue;
            $links[] = [
                'url'   => $this->normalizeUrl($url) ?? $url,
                'label' => $label !== '' ? $label : $url,
            ];
        }

        return [
            'title'          => $this->request->getPost('title'),
            'sector'         => $this->request->getPost('sector') ?: null,
            'min_education'  => $this->request->getPost('min_education') ?: null,
            'description'    => $this->request->getPost('description'),
            'required_skills'=> $skills ? json_encode($skills) : null,
            'pathways'       => $this->request->getPost('pathways') ?: null,
            'avg_salary_idr' => $this->request->getPost('avg_salary_idr') ?: null,
            'demand_level'   => (int) ($this->request->getPost('demand_level') ?: 0),
            'external_links' => $links ? json_encode($links) : null,
            'is_active'      => (int) $this->request->getPost('is_active'),
            'is_public'      => (int) ($this->request->getPost('is_public', FILTER_VALIDATE_INT) ?? 0),
        ];
    }

    /* -----------------------------------------------------------------
     *  University Info (university_info)
     * -----------------------------------------------------------------*/

    /** Tab universitas dengan filter/sort yang sama seperti di view */
    public function universities()
    {
        require_permission('manage_career_info');

        $filters = [
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

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $ub = $ub->groupStart()
                ->like('university_info.university_name', $q)
                ->orLike('university_info.alias', $q)
                ->orLike('university_info.description', $q)
            ->groupEnd();
        }
        if (!empty($filters['acc'])) {
            $ub->where('university_info.accreditation', $filters['acc']);
        }
        if (!empty($filters['loc'])) {
            $ub->where('university_info.location', $filters['loc']);
        }
        if ($filters['status'] !== null && $filters['status'] !== '') {
            $ub->where('university_info.is_active', (int) $filters['status']);
        }
        if ($filters['pub'] !== null && $filters['pub'] !== '') {
            $ub->where('university_info.is_public', (int) $filters['pub']);
        }

        // Sort
        $sort = $filters['sort'] ?? '';
        if ($sort === 'name_desc') {
            $ub->orderBy('university_info.university_name', 'DESC');
        } elseif ($sort === 'acc') {
            $ub->orderBy('university_info.accreditation', 'ASC')
            ->orderBy('university_info.university_name', 'ASC');
        } elseif ($sort === 'loc') {
            $ub->orderBy('university_info.location', 'ASC')
            ->orderBy('university_info.university_name', 'ASC');
        } else {
            $ub->orderBy('university_info.university_name', 'ASC');
        }

        $universities = $ub->paginate(10, 'universities');
        $uniPager     = $this->universities->pager;

        $data = [
            'careers'       => [],       // supaya view index tetap bisa dipakai
            'careerPager'   => null,
            'careerFilters' => [],
            'universities'  => $universities,
            'uniPager'      => $uniPager,
            'activeTab'     => 'universities',
        ];

        return view('counselor/career/index', $data);
    }

    // FORM CREATE
    public function createUniversity()
    {
        require_permission('manage_career_info');

        $data = [
            'uni'  => [
                'university_name' => '',
                'alias'           => '',
                'accreditation'   => '',
                'location'        => '',
                'website'         => '',
                'logo'            => null,
                'description'     => '',
                'is_active'       => 1,
                'is_public'       => 0,
            ],
            'mode' => 'create',
        ];
        return view('counselor/career/form_university', $data);
    }

    // POST CREATE
    public function storeUniversity()
    {
        require_permission('manage_career_info');

        // Validasi dasar
        $baseRules = [
            'university_name' => 'required|string|min_length[3]',
            'accreditation'   => 'permit_empty|string|max_length[20]',
            'location'        => 'permit_empty|string|max_length[255]',
            'website'         => 'permit_empty|valid_url',
            'is_public'       => 'permit_empty|in_list[0,1]',
            'is_active'       => 'required|in_list[0,1]',
            'logo_source'     => 'permit_empty|in_list[url,upload]',
        ];

        // Validasi file jika sumber upload
        $logoSource = $this->request->getPost('logo_source') ?? 'url';
        if ($logoSource === 'upload') {
            $baseRules['logo_file'] =
                'uploaded[logo_file]|is_image[logo_file]|max_size[logo_file,2048]|mime_in[logo_file,image/png,image/jpeg,image/jpg,image/gif]';
        }

        if (! $this->validate($baseRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = $this->buildUniversityPayload();

        // Audit: siapa yang menambahkan info universitas
        $payload['created_by'] = (int) session('user_id');

        $this->universities->insert($payload);

        return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
            ->with('success', 'Data universitas berhasil ditambahkan.');
    }

    // FORM EDIT
    public function editUniversity(int $id)
    {
        require_permission('manage_career_info');

        $uni = $this->universities
            ->select('university_info.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = university_info.created_by', 'left')
            ->where('university_info.id', $id)
            ->first();

        if (! $uni) {
            return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
                ->with('error', 'Data universitas tidak ditemukan.');
        }

        // Decode JSON ke array agar view mudah menampilkannya (opsional, view juga punya fallback)
        $uni['faculties_array']    = !empty($uni['faculties'])
            ? (json_decode($uni['faculties'], true) ?: [])
            : [];
        $uni['programs_array']     = !empty($uni['programs'])
            ? (json_decode($uni['programs'], true) ?: [])
            : [];
        $uni['scholarships_array'] = !empty($uni['scholarships'])
            ? (json_decode($uni['scholarships'], true) ?: [])
            : [];
        $uni['contacts_array']     = !empty($uni['contacts'])
            ? (json_decode($uni['contacts'], true) ?: [])
            : [];

        // Ambil nama pembuat dari tabel users berdasarkan kolom created_by
        if (!empty($uni['created_by'])) {
            $db  = db_connect();
            $row = $db->table('users')
                ->select('full_name')
                ->where('id', (int) $uni['created_by'])
                ->get()
                ->getRowArray();

            if (!empty($row['full_name'])) {
                // View akan membaca $career['created_by_name']
                $uni['created_by_name'] = $row['full_name'];
            }
        }

        return view('counselor/career/form_university', ['uni' => $uni, 'mode' => 'edit']);
    }

    // POST UPDATE
    public function updateUniversity(int $id)
    {
        require_permission('manage_career_info');

        $existing = $this->universities->find($id);
        if (! $existing) {
            return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
                ->with('error', 'Data universitas tidak ditemukan.');
        }

        $baseRules = [
            'university_name' => 'required|string|min_length[3]',
            'accreditation'   => 'permit_empty|string|max_length[20]',
            'location'        => 'permit_empty|string|max_length[255]',
            'website'         => 'permit_empty|valid_url',
            'is_public'       => 'permit_empty|in_list[0,1]',
            'is_active'       => 'required|in_list[0,1]',
            'logo_source'     => 'permit_empty|in_list[url,upload]',
            'remove_logo'     => 'permit_empty|in_list[0,1]',
        ];

        $logoSource = $this->request->getPost('logo_source') ?? 'url';
        if ($logoSource === 'upload') {
            // File opsional saat edit, tapi jika ada harus valid
            if ($file = $this->request->getFile('logo_file')) {
                if ($file->isValid() && !$file->hasMoved() && $file->getSize() > 0) {
                    $baseRules['logo_file'] =
                        'is_image[logo_file]|max_size[logo_file,2048]|mime_in[logo_file,image/png,image/jpeg,image/jpg,image/gif]';
                }
            }
        }

        if (! $this->validate($baseRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = $this->buildUniversityPayload($existing);

        $this->universities->update($id, $payload);

        return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
            ->with('success', 'Data universitas berhasil diperbarui.');
    }

    public function deleteUniversity(int $id)
    {
        require_permission('manage_career_info');

        $row = $this->universities->find($id);
        if ($row) {
            if (!empty($row['logo']) && $this->isLocalUpload($row['logo'])) {
                $this->removeLocalFile($row['logo']);
            }
            $this->universities->delete($id); // soft delete
        }

        return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
            ->with('success', 'Data universitas berhasil dihapus.');
    }

    public function toggleUniversity(int $id)
    {
        require_permission('manage_career_info');

        $university = $this->universities->find($id);
        if (! $university) {
            return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
                ->with('error', 'Data universitas tidak ditemukan.');
        }

        $newStatus = (int) ($university['is_active'] ?? 0) === 1 ? 0 : 1;
        $this->universities->update($id, ['is_active' => $newStatus]);

        return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
            ->with('success', 'Status universitas berhasil diubah.');
    }

    public function toggleUniversityPublic(int $id)
    {
        require_permission('manage_career_info');

        $university = $this->universities->find($id);
        if (! $university) {
            return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
                ->with('error', 'Data universitas tidak ditemukan.');
        }

        $new = (int) ($university['is_public'] ?? 0) === 1 ? 0 : 1;
        $this->universities->update($id, ['is_public' => $new]);

        $msg = $new ? 'Universitas dipublikasikan.' : 'Universitas di-set private.';
        return redirect()->to(route_to('counselor.university.index') . '?tab=universities')
            ->with('success', $msg);
    }

    /** Bangun payload university_info + dukung URL/Upload logo */
    protected function buildUniversityPayload(?array $existing = null): array
    {
        // Arrays multi-value
        $faculties = $this->request->getPost('faculties') ?? [];
        $faculties = array_values(array_filter(array_map('trim', (array) $faculties)));

        $programNames   = $this->request->getPost('program_names')   ?? [];
        $programDegrees = $this->request->getPost('program_degrees') ?? [];
        $programs = [];
        foreach ((array) $programNames as $i => $name) {
            $name   = trim((string) $name);
            $degree = trim((string) ($programDegrees[$i] ?? ''));
            if ($name === '') continue;
            $programs[] = ['name' => $name, 'degree' => $degree];
        }

        $scholarships = $this->request->getPost('scholarships') ?? [];
        $scholarships = array_values(array_filter(array_map('trim', (array) $scholarships)));

        $contacts = $this->request->getPost('contacts') ?? [];
        $contacts = array_values(array_filter(array_map('trim', (array) $contacts)));

        // Flag dan field dasar
        $isActive = (int) ($this->request->getPost('is_active') ?? 0) === 1 ? 1 : 0;
        $isPublic = (int) ($this->request->getPost('is_public') ?? 0) === 1 ? 1 : 0;

        $payload = [
            'university_name' => $this->request->getPost('university_name'),
            'alias'           => $this->request->getPost('alias') ?: null,
            'accreditation'   => $this->request->getPost('accreditation') ?: null,
            'location'        => $this->request->getPost('location') ?: null,
            'website'         => $this->normalizeUrl($this->request->getPost('website') ?: null),
            'description'     => $this->request->getPost('description') ?: null,
            'faculties'       => $faculties ? json_encode($faculties)   : null,
            'programs'        => $programs  ? json_encode($programs)    : null,
            'admission_info'  => $this->request->getPost('admission_info') ?: null,
            'tuition_range'   => $this->request->getPost('tuition_range')  ?: null,
            'scholarships'    => $scholarships ? json_encode($scholarships) : null,
            'contacts'        => $contacts ? json_encode($contacts)         : null,
            'is_public'       => $isPublic,
            'is_active'       => $isActive,
        ];

        // Hapus logo?
        if ($this->request->getPost('remove_logo') === '1') {
            if ($existing && !empty($existing['logo']) && $this->isLocalUpload($existing['logo'])) {
                $this->removeLocalFile($existing['logo']);
            }
            $payload['logo'] = null;
            return $payload;
        }

        // Sumber logo: url | upload
        $src = $this->request->getPost('logo_source') ?? 'url';

        if ($src === 'url') {
            $url = $this->normalizeUrl((string)$this->request->getPost('logo_url'));
            if ($url) {
                $payload['logo'] = $url;
                // jika sebelumnya file lokal, hapus
                if ($existing && !empty($existing['logo']) && $this->isLocalUpload($existing['logo'])) {
                    $this->removeLocalFile($existing['logo']);
                }
            }
        } elseif ($src === 'upload') {
            $file = $this->request->getFile('logo_file');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $target  = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'universities';
                if (!is_dir($target)) {
                    @mkdir($target, 0775, true);
                }
                $file->move($target, $newName);
                $relPath = 'uploads/universities/' . $newName;

                $payload['logo'] = $relPath;

                // hapus file lama jika lokal
                if ($existing && !empty($existing['logo']) && $this->isLocalUpload($existing['logo'])) {
                    $this->removeLocalFile($existing['logo']);
                }
            }
        }

        return $payload;
    }

    /* =================================================================
     * Helpers
     * ================================================================= */

    /** Pastikan http/https valid; jika kosong atau invalid kembalikan null */
    protected function normalizeUrl($url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') return null;
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /** True bila path logo adalah upload lokal (bukan URL eksternal) */
    protected function isLocalUpload(?string $logo): bool
    {
        return is_string($logo) && strpos($logo, 'uploads/universities/') === 0;
    }

    /** Hapus file lokal bila ada */
    protected function removeLocalFile(string $relPath): void
    {
        $abs = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
