<?php

/**
 * File Path: app/Controllers/Parents/CareerController.php
 *
 * Parent • Career & University Info
 * Orang tua dapat:
 * - Menjelajahi Info Karir & Perguruan Tinggi (publik, sama seperti siswa)
 * - Melihat, menambahkan, dan menghapus item tersimpan milik anak-anaknya
 *   (dipilih via dropdown child_id)
 */

namespace App\Controllers\Parents;

use App\Models\CareerOptionModel;
use App\Models\UniversityInfoModel;
use CodeIgniter\I18n\Time;

class CareerController extends BaseParentController
{
    protected CareerOptionModel $careers;
    protected UniversityInfoModel $unis;

    public function __construct()
    {
        /**
         * Pastikan koneksi DB tersedia (menghindari kasus BaseParentController tidak menginisialisasi $db
         * ketika controller memakai __construct()).
         */
        $this->db = \Config\Database::connect();

        $this->careers = new CareerOptionModel();
        $this->unis    = new UniversityInfoModel();
    }

    /**
     * GET /parent/career
     * Eksplor karier publik (aktif) + info perguruan tinggi (tab),
     * dengan dropdown untuk memilih anak yang sedang dilihat portofolionya.
     */
    public function index()
    {
        $this->requireParent();

        // Daftar anak yang terhubung ke orang tua ini
        $children      = $this->getChildrenForCurrentParent();
        $activeChildId = $this->resolveActiveChildId($children);

        // Tab aktif (careers|universities)
        $activeTab = $this->request->getGet('tab') ?? 'careers';
        if (!in_array($activeTab, ['careers', 'universities'], true)) {
            $activeTab = 'careers';
        }

        // ======================
        // FILTER KARIER
        // ======================
        $filters = [
            'q'      => trim((string) $this->request->getGet('q')),
            'sector' => $this->request->getGet('sector'),
            'edu'    => $this->request->getGet('edu'),
            // latest|popular|salary
            'sort'   => $this->request->getGet('sort'),
        ];

        // Hindari state “menumpuk” pada Model: selalu pakai clone untuk query yang berbeda
        $careerModel = clone $this->careers;

        if (method_exists($careerModel, 'searchPaginated')) {
            $list  = $careerModel->searchPaginated($filters, 12);
            $pager = $careerModel->pager;

            // Sector items sebaiknya juga hanya dari data publik+aktif
            $sectorItems = (clone $this->careers)
                ->select('sector')
                ->where('is_active', 1)
                ->where('is_public', 1)
                ->where('sector IS NOT NULL', null, false)
                ->groupBy('sector')
                ->orderBy('sector', 'ASC')
                ->findColumn('sector') ?? [];
        } else {
            // Fallback manual (mirip versi siswa)
            $builder = $this->applyPublicScope(clone $this->careers);

            if ($filters['q'] !== '') {
                $builder = $builder->groupStart()
                    ->like('title', $filters['q'])
                    ->orLike('short_description', $filters['q'])
                    ->orLike('sector', $filters['q'])
                ->groupEnd();
            }
            if (!empty($filters['sector'])) {
                $builder = $builder->where('sector', $filters['sector']);
            }
            if (!empty($filters['edu'])) {
                $builder = $builder->where('min_education', $filters['edu']);
            }

            $sort = strtolower((string) $filters['sort']);
            if ($sort === 'popular' && $this->db->tableExists('career_option_stats')) {
                $builder = $builder->select('career_options.*, cos.view_count')
                    ->join('career_option_stats cos', 'cos.career_id = career_options.id', 'left')
                    ->orderBy('cos.view_count', 'DESC')
                    ->orderBy('career_options.updated_at', 'DESC');
            } elseif ($sort === 'salary') {
                $builder = $builder->orderBy('avg_salary_idr', 'DESC')
                    ->orderBy('updated_at', 'DESC');
            } else {
                $builder = $builder->orderBy('updated_at', 'DESC')
                    ->orderBy('created_at', 'DESC');
            }

            $list  = $builder->paginate(12);
            $pager = $this->careers->pager;

            $sectorItems = (clone $this->careers)
                ->select('sector')
                ->where('is_active', 1)
                ->where('is_public', 1)
                ->where('sector IS NOT NULL', null, false)
                ->groupBy('sector')
                ->orderBy('sector', 'ASC')
                ->findColumn('sector') ?? [];
        }

        $educs = ['SMA/SMK', 'D3', 'S1', 'S2'];

        // ======================
        // FILTER UNIVERSITAS
        // ======================
        $uniFilters = [
            'q'        => trim((string) $this->request->getGet('u_q')),
            'location' => $this->request->getGet('u_loc'),
            'accr'     => $this->request->getGet('u_accr'),
            'sort'     => $this->request->getGet('u_sort'),
        ];

        $universities = [];
        $uniPager     = null;
        $uniLocations = [];
        $uniAccrs     = [];

        if ($this->db->tableExists('university_info')) {
            // Dropdown lokasi
            $uniLocations = (clone $this->unis)
                ->select('location')
                ->where('is_active', 1)
                ->where('is_public', 1)
                ->where('location IS NOT NULL', null, false)
                ->groupBy('location')
                ->orderBy('location', 'ASC')
                ->findColumn('location') ?? [];

            // Dropdown akreditasi
            $uniAccrs = (clone $this->unis)
                ->select('accreditation')
                ->where('is_active', 1)
                ->where('is_public', 1)
                ->where('accreditation IS NOT NULL', null, false)
                ->groupBy('accreditation')
                ->orderBy('accreditation', 'ASC')
                ->findColumn('accreditation') ?? [];

            // Query utama universitas
            $uniBuilder = (clone $this->unis)
                ->where('is_active', 1)
                ->where('is_public', 1);

            if ($uniFilters['q'] !== '') {
                $uniBuilder = $uniBuilder->groupStart()
                    ->like('university_name', $uniFilters['q'])
                    ->orLike('alias', $uniFilters['q'])
                    ->orLike('location', $uniFilters['q'])
                ->groupEnd();
            }
            if (!empty($uniFilters['location'])) {
                $uniBuilder = $uniBuilder->where('location', $uniFilters['location']);
            }
            if (!empty($uniFilters['accr'])) {
                $uniBuilder = $uniBuilder->where('accreditation', $uniFilters['accr']);
            }

            $sortUni = strtolower((string) $uniFilters['sort']);
            if ($sortUni === 'location') {
                $uniBuilder = $uniBuilder->orderBy('location', 'ASC')
                    ->orderBy('university_name', 'ASC');
            } elseif ($sortUni === 'accr') {
                $uniBuilder = $uniBuilder->orderBy('accreditation', 'DESC')
                    ->orderBy('university_name', 'ASC');
            } else {
                $uniBuilder = $uniBuilder->orderBy('university_name', 'ASC');
            }

            // Pagination terpisah untuk universitas
            $universities = $uniBuilder->paginate(9, 'universities');
            $uniPager     = $this->unis->pager;
        }

        // ID yang sudah tersimpan untuk anak aktif (untuk badge "Tersimpan")
        $savedCareerIds     = [];
        $savedUniversityIds = [];
        if ($activeChildId) {
            [$savedCareerIds, $savedUniversityIds] = $this->getSavedIdsForStudent($activeChildId);
        }

        return view('parent/career/explore', [
            'careers'            => $list,
            'pager'              => $pager,
            'filters'            => $filters,
            'sectors'            => $sectorItems,
            'educs'              => $educs,
            'today'              => Time::today('Asia/Jakarta')->toDateString(),

            'universities'       => $universities,
            'uniPager'           => $uniPager,
            'uniFilters'         => $uniFilters,
            'uniLocations'       => $uniLocations,
            'uniAccrs'           => $uniAccrs,
            'activeTab'          => $activeTab,

            'savedIds'           => $savedCareerIds, // legacy
            'savedCareerIds'     => $savedCareerIds,
            'savedUniversityIds' => $savedUniversityIds,

            // Data anak (untuk dropdown di view)
            'children'           => $children,
            'activeChildId'      => $activeChildId,
        ]);
    }

    /**
     * Alias ke index()
     */
    public function explore()
    {
        return $this->index();
    }

    /**
     * GET /parent/career/(:num)
     * Detail karier atau perguruan tinggi (sama seperti siswa).
     * Parent hanya melihat; pengelolaan simpanan tetap via saved()/save()/remove().
     */
    public function detail(int $id)
    {
        $this->requireParent();

        $type = $this->request->getGet('type');

        if ($type === 'uni') {
            return $this->showUniversityDetail($id, 'parent/career?tab=universities');
        }

        $career = $this->getPublicCareer($id);
        if ($career) {
            $skills = [];
            if (!empty($career['required_skills'])) {
                $skills = is_array($career['required_skills'])
                    ? $career['required_skills']
                    : (json_decode((string) $career['required_skills'], true) ?: []);
            }

            $links = [];
            if (!empty($career['external_links'])) {
                $links = is_array($career['external_links'])
                    ? $career['external_links']
                    : (json_decode((string) $career['external_links'], true) ?: []);
            }

            // Ambil beberapa universitas publik (optional)
            $universities = (clone $this->unis)
                ->where('is_active', 1)
                ->where('is_public', 1)
                ->orderBy('university_name', 'ASC')
                ->findAll(6);

            $related = [];
            if (!empty($career['sector'])) {
                $related = $this->applyPublicScope(clone $this->careers)
                    ->where('sector', $career['sector'])
                    ->where('id !=', (int) $id)
                    ->orderBy('updated_at', 'DESC')
                    ->findAll(6);
            }

            return view('parent/career/detail', [
                'career'       => $career,
                'skills'       => $skills,
                'links'        => $links,
                'universities' => $universities,
                'related'      => $related,
            ]);
        }

        // Fallback: coba sebagai ID universitas
        return $this->showUniversityDetail($id, 'parent/career?tab=universities');
    }

    /**
     * GET /parent/career/saved
     * Daftar karier & perguruan tinggi yang disimpan anak tertentu.
     * Anak dipilih via dropdown (child_id).
     */
    public function saved()
    {
        $this->requireParent();

        $children      = $this->getChildrenForCurrentParent();
        $activeChildId = $this->resolveActiveChildId($children);

        // Jika orang tua belum punya anak terhubung
        if (!$activeChildId) {
            return view('parent/career/saved', [
                'careers'         => [],
                'careerCount'     => 0,
                'universities'    => [],
                'universityCount' => 0,
                'activeTab'       => 'careers',
                'children'        => $children,
                'activeChildId'   => null,
            ]);
        }

        // Karier tersimpan untuk anak aktif
        $careerList = [];
        if ($this->db->tableExists('student_saved_careers')) {
            $careerList = $this->applyPublicScope(clone $this->careers)
                ->select('career_options.*, creator.full_name AS created_by_name, ssc.created_at AS saved_at')
                ->join('student_saved_careers ssc', 'ssc.career_id = career_options.id', 'inner')
                ->join('users AS creator', 'creator.id = career_options.created_by', 'left')
                ->where('ssc.student_id', $activeChildId)
                ->orderBy('career_options.updated_at', 'DESC')
                ->findAll();
        }

        // Perguruan tinggi tersimpan untuk anak aktif
        $uniList = [];
        if ($this->db->tableExists('student_saved_universities')) {
            $uniList = (clone $this->unis)
                ->select('university_info.*, creator.full_name AS created_by_name, ssu.created_at AS saved_at')
                ->join('student_saved_universities ssu', 'ssu.university_id = university_info.id', 'inner')
                ->join('users AS creator', 'creator.id = university_info.created_by', 'left')
                ->where('university_info.is_active', 1)
                ->where('university_info.is_public', 1)
                ->where('ssu.student_id', $activeChildId)
                ->orderBy('university_info.university_name', 'ASC')
                ->findAll();
        }

        $activeTab = $this->request->getGet('tab') ?? 'careers';
        if (!in_array($activeTab, ['careers', 'universities'], true)) {
            $activeTab = 'careers';
        }

        return view('parent/career/saved', [
            'careers'         => $careerList,
            'careerCount'     => count($careerList),
            'universities'    => $uniList,
            'universityCount' => count($uniList),
            'activeTab'       => $activeTab,

            'children'        => $children,
            'activeChildId'   => $activeChildId,
        ]);
    }

    /**
     * POST /parent/career/save/(:num)
     * Simpan karier atau universitas ke daftar tersimpan milik anak yang dipilih.
     * child_id dikirim via POST/GET.
     */
    public function save(int $id)
    {
        $this->requireParent();

        $children      = $this->getChildrenForCurrentParent();
        $activeChildId = $this->resolveActiveChildId($children);

        if (!$activeChildId) {
            return $this->respondBack(400, 'Pilih anak terlebih dahulu sebelum menyimpan pilihan.');
        }

        $type = $this->request->getGet('type');

        if ($type === 'uni') {
            return $this->saveUniversityForStudent($activeChildId, $id);
        }

        // Simpan karier untuk anak
        $exists = $this->getPublicCareer($id);
        if (!$exists) {
            return $this->respondBack(400, 'Karir tidak valid atau belum dipublikasikan.');
        }

        if ($this->db->tableExists('student_saved_careers')) {
            $row = $this->db->table('student_saved_careers')
                ->where('student_id', $activeChildId)
                ->where('career_id', (int) $id)
                ->get()->getRowArray();

            if (!$row) {
                $now = Time::now('Asia/Jakarta')->toDateTimeString();
                $this->db->table('student_saved_careers')->insert([
                    'student_id' => $activeChildId,
                    'career_id'  => (int) $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $toUrl = '/parent/career/saved?child_id=' . $activeChildId . '&tab=careers';
        return $this->respondForward(200, 'Pilihan karir disimpan untuk anak.', $toUrl);
    }

    /**
     * POST /parent/career/remove/(:num)
     * Hapus dari daftar tersimpan milik anak.
     */
    public function remove(int $id)
    {
        $this->requireParent();

        $children      = $this->getChildrenForCurrentParent();
        $activeChildId = $this->resolveActiveChildId($children);

        if (!$activeChildId) {
            return $this->respondBack(400, 'Pilih anak terlebih dahulu sebelum menghapus pilihan.');
        }

        $type = $this->request->getGet('type');

        if ($type === 'uni') {
            return $this->removeUniversityForStudent($activeChildId, $id);
        }

        if ($this->db->tableExists('student_saved_careers')) {
            $this->db->table('student_saved_careers')
                ->where('student_id', $activeChildId)
                ->where('career_id', (int) $id)
                ->delete();
        }

        $toUrl = '/parent/career/saved?child_id=' . $activeChildId . '&tab=careers';
        return $this->respondForward(200, 'Pilihan karir dihapus dari daftar anak.', $toUrl);
    }

    // ---------------------------------------------------------------------
    // Helpers khusus parent
    // ---------------------------------------------------------------------

    /**
     * Ambil daftar anak yang terhubung dengan orang tua yang sedang login.
     *
     * FIX:
     * - Kolom students.full_name sudah dihapus → ambil dari users.full_name (join via students.user_id)
     */
    protected function getChildrenForCurrentParent(): array
    {
        $parentId = (int) session('user_id');

        if ($parentId <= 0) {
            return [];
        }

        $rows = $this->db->table('students s')
            ->select('
                s.id,
                s.class_id,
                COALESCE(u.full_name, "-") AS full_name,
                c.class_name,
                c.grade_level,
                c.major
            ')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->orderBy('full_name', 'ASC')
            ->get()
            ->getResultArray();

        return $rows ?: [];
    }

    /**
     * Tentukan anak aktif berdasarkan child_id (GET/POST) + validasi masuk di daftar anak.
     */
    protected function resolveActiveChildId(array $children): ?int
    {
        if (empty($children)) {
            return null;
        }

        $requested = (int) ($this->request->getGet('child_id') ?: $this->request->getPost('child_id') ?: 0);
        if ($requested > 0) {
            foreach ($children as $c) {
                $id = (int) ($c['id'] ?? 0);
                if ($id === $requested) {
                    return $id;
                }
            }
        }

        // Default: pakai anak pertama
        $first = reset($children);
        return (int) ($first['id'] ?? 0) ?: null;
    }

    /**
     * Ambil dua array:
     * [ [career_ids], [university_ids] ] untuk satu student.
     */
    protected function getSavedIdsForStudent(int $studentId): array
    {
        $savedCareerIds     = [];
        $savedUniversityIds = [];

        if ($this->db->tableExists('student_saved_careers')) {
            $rows = $this->db->table('student_saved_careers')
                ->select('career_id')
                ->where('student_id', $studentId)
                ->get()->getResultArray();
            $savedCareerIds = array_map('intval', array_column($rows, 'career_id'));
        }

        if ($this->db->tableExists('student_saved_universities')) {
            $rows = $this->db->table('student_saved_universities')
                ->select('university_id')
                ->where('student_id', $studentId)
                ->get()->getResultArray();
            $savedUniversityIds = array_map('intval', array_column($rows, 'university_id'));
        }

        return [$savedCareerIds, $savedUniversityIds];
    }

    // ---------------------------------------------------------------------
    // Helpers turunan dari versi siswa
    // ---------------------------------------------------------------------

    private function applyPublicScope(CareerOptionModel $builder): CareerOptionModel
    {
        return $builder
            ->where('career_options.is_active', 1)
            ->where('career_options.is_public', 1);
    }

    private function getPublicCareer(int $id): ?array
    {
        $m = clone $this->careers;

        $m = $m->select('career_options.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = career_options.created_by', 'left')
            ->where('career_options.is_active', 1)
            ->where('career_options.is_public', 1)
            ->where('career_options.id', $id);

        $row = $m->first();
        return is_array($row) ? $row : ($row ? (array) $row : null);
    }

    private function showUniversityDetail(int $id, string $backUrl)
    {
        $uni = (clone $this->unis)
            ->select('university_info.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = university_info.created_by', 'left')
            ->where('university_info.is_active', 1)
            ->where('university_info.is_public', 1)
            ->where('university_info.id', $id)
            ->first();

        if (!$uni) {
            return redirect()->to('/parent/career')->with('error', 'Data perguruan tinggi tidak ditemukan atau belum dipublikasikan.');
        }

        $faculties = [];
        if (!empty($uni['faculties'])) {
            $faculties = is_array($uni['faculties'])
                ? $uni['faculties']
                : (json_decode((string) $uni['faculties'], true) ?: []);
        }

        $programs = [];
        if (!empty($uni['programs'])) {
            $programs = is_array($uni['programs'])
                ? $uni['programs']
                : (json_decode((string) $uni['programs'], true) ?: []);
        }

        return view('parent/career/university_detail', [
            'university' => $uni,
            'faculties'  => $faculties,
            'programs'   => $programs,
            'backUrl'    => $backUrl,
        ]);
    }

    private function saveUniversityForStudent(int $studentId, int $id)
    {
        $uni = (clone $this->unis)
            ->where('is_active', 1)
            ->where('is_public', 1)
            ->find($id);

        if (!$uni) {
            return $this->respondBack(400, 'Perguruan tinggi tidak valid atau belum dipublikasikan.');
        }

        if ($this->db->tableExists('student_saved_universities')) {
            $row = $this->db->table('student_saved_universities')
                ->where('student_id', $studentId)
                ->where('university_id', (int) $id)
                ->get()->getRowArray();

            if (!$row) {
                $now = Time::now('Asia/Jakarta')->toDateTimeString();
                $this->db->table('student_saved_universities')->insert([
                    'student_id'    => $studentId,
                    'university_id' => (int) $id,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }

        $toUrl = '/parent/career/saved?child_id=' . $studentId . '&tab=universities';
        return $this->respondForward(200, 'Perguruan tinggi disimpan untuk anak.', $toUrl);
    }

    private function removeUniversityForStudent(int $studentId, int $id)
    {
        if ($this->db->tableExists('student_saved_universities')) {
            $this->db->table('student_saved_universities')
                ->where('student_id', $studentId)
                ->where('university_id', (int) $id)
                ->delete();
        }

        $toUrl = '/parent/career/saved?child_id=' . $studentId . '&tab=universities';
        return $this->respondForward(200, 'Perguruan tinggi dihapus dari daftar anak.', $toUrl);
    }

    private function respondBack(int $status, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode($status)->setJSON([
                'status'  => $status,
                'message' => $message,
            ]);
        }
        $type = $status >= 400 ? 'error' : 'message';
        return redirect()->back()->with($type, $message);
    }

    private function respondForward(int $status, string $message, string $toUrl)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'   => $status,
                'message'  => $message,
                'redirect' => $toUrl,
            ]);
        }
        $type = $status >= 400 ? 'error' : 'message';
        return redirect()->to($toUrl)->with($type, $message);
    }
}
