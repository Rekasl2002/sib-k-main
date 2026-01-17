<?php

/**
 * File Path: app/Controllers/HomeroomTeacher/CareerInfoController.php
 *
 * Wali Kelas • Info Karir & Perguruan Tinggi
 * - Melihat daftar career_options & university_info (READ ONLY)
 * - Melihat rekap pilihan karir & universitas siswa per kelas perwalian
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
        helper(['auth', 'permission']);
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
     * Halaman utama Info Karir & Perguruan Tinggi (Wali Kelas)
     * Tampilan & filter meniru Counselor\CareerInfoController::index(),
     * tapi hanya READ dan view diarahkan ke homeroom_teacher.
     */
    public function index()
    {
        // Ganti 'view_career_info' sesuai permission yang dipakai untuk Wali Kelas
        require_permission('view_career_info');

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

        return view('homeroom_teacher/career/index', $data);
    }

    /**
     * Rekap pilihan Karir & Universitas siswa UNTUK SATU KELAS PERWALIAN
     * - Logika mirip Counselor\CareerInfoController::studentChoices()
     * - class_id SELALU dipaksa = kelas perwalian wali kelas yang login
     */
    public function studentChoices()
    {
        // Ganti 'view_career_info' sesuai permission baca yang kamu pakai
        require_permission('view_career_info');

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
        // Data pilihan KARIR siswa (hanya siswa kelas perwalian)
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
                    ->orLike('students.nis', $q)
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
                    students.nis                          AS nis,
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
                    ->orLike('students.nis', $q)
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
}
