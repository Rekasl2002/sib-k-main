<?php

namespace App\Controllers\Student;

use App\Models\CareerOptionModel;
use App\Models\UniversityInfoModel;
use CodeIgniter\I18n\Time;

class CareerController extends BaseStudentController
{
    protected CareerOptionModel $careers;
    protected UniversityInfoModel $unis;

    public function __construct()
    {
        $this->careers = new CareerOptionModel();
        $this->unis    = new UniversityInfoModel();
    }

    /**
     * GET /student/career
     * Eksplor karier publik (aktif) + info perguruan tinggi (tab).
     */
    public function index()
    {
        $this->requireStudent();

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
            // latest|popular|salary (opsi lama; view boleh kirim nilai lain, fallback ke default)
            'sort'   => $this->request->getGet('sort'),
        ];

        // Jika Model menyediakan scope/metode khusus, pakai. Jika tidak, jatuh ke builder generik.
        if (method_exists($this->careers, 'searchPaginated')) {
            $list        = $this->careers->searchPaginated($filters, 12);
            $pager       = $this->careers->pager;
            $sectorItems = $this->careers
                ->select('sector')
                ->where('sector IS NOT NULL', null, false)
                ->groupBy('sector')
                ->findColumn('sector') ?? [];
        } else {
            // Fallback manual
            $builder = $this->careers;
            $builder = $this->applyPublicScope($builder);

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
                // kolom min_education diasumsikan berisi salah satu dari: SMA/SMK, D3, S1, S2
                $builder = $builder->where('min_education', $filters['edu']);
            }

            // Sorting sederhana
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
                // default: terbaru di atas
                $builder = $builder->orderBy('updated_at', 'DESC')
                                   ->orderBy('created_at', 'DESC');
            }

            $list        = $builder->paginate(12);
            $pager       = $this->careers->pager;
            $sectorItems = $this->careers
                ->select('sector')
                ->where('sector IS NOT NULL', null, false)
                ->groupBy('sector')
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

        // ==============================
        // Info Perguruan Tinggi (tab siswa)
        // ==============================
        $universities = [];
        $uniPager     = null;
        $uniLocations = [];
        $uniAccrs     = [];

        if ($this->db->tableExists('university_info')) {
            // Sumber opsi dropdown lokasi
            $uniLocations = $this->unis
                ->select('location')
                ->where('is_active', 1)
                ->where('is_public', 1)
                ->where('location IS NOT NULL', null, false)
                ->groupBy('location')
                ->orderBy('location', 'ASC')
                ->findColumn('location') ?? [];

            // Sumber opsi dropdown akreditasi
            $uniAccrs = $this->unis
                ->select('accreditation')
                ->where('is_active', 1)
                ->where('is_public', 1)
                ->where('accreditation IS NOT NULL', null, false)
                ->groupBy('accreditation')
                ->orderBy('accreditation', 'ASC')
                ->findColumn('accreditation') ?? [];

            // Query utama universitas
            $uniBuilder = $this->unis
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
                // default: nama A-Z
                $uniBuilder = $uniBuilder->orderBy('university_name', 'ASC');
            }

            // Pagination terpisah agar tidak bentrok dengan daftar karir
            $universities = $uniBuilder->paginate(9, 'universities');
            $uniPager     = $this->unis->pager;
        }

        // ID yang sudah tersimpan (untuk badge "Tersimpan" di listing)
        $savedCareerIds      = [];
        $savedUniversityIds  = [];

        // Karier tersimpan
        if ($this->db->tableExists('student_saved_careers')) {
            $rows = $this->db->table('student_saved_careers')
                ->select('career_id')
                ->where('student_id', $this->studentId)
                ->get()->getResultArray();
            $savedCareerIds = array_map('intval', array_column($rows, 'career_id'));
        } else {
            $savedCareerIds = array_map('intval', session()->get('saved_careers') ?? []);
        }

        // Universitas tersimpan
        if ($this->db->tableExists('student_saved_universities')) {
            $rows = $this->db->table('student_saved_universities')
                ->select('university_id')
                ->where('student_id', $this->studentId)
                ->get()->getResultArray();
            $savedUniversityIds = array_map('intval', array_column($rows, 'university_id'));
        } else {
            $savedUniversityIds = array_map('intval', session()->get('saved_universities') ?? []);
        }

        return view('student/career/explore', [
            'careers'            => $list,
            'pager'              => $pager,
            'filters'            => $filters,
            'sectors'            => $sectorItems,
            'educs'              => $educs,
            'today'              => Time::today('Asia/Jakarta')->toDateString(),

            // Data tambahan untuk tab Info Perguruan Tinggi
            'universities'       => $universities,
            'uniPager'           => $uniPager,
            'uniFilters'         => $uniFilters,
            'uniLocations'       => $uniLocations,
            'uniAccrs'           => $uniAccrs,
            'activeTab'          => $activeTab,

            // Data tersimpan
            'savedIds'           => $savedCareerIds,      // kompatibilitas lama
            'savedCareerIds'     => $savedCareerIds,
            'savedUniversityIds' => $savedUniversityIds,
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
     * GET /student/career/(:num)
     * Detail karier publik atau (jika diminta) detail perguruan tinggi.
     */
    public function detail(int $id)
    {
        $this->requireStudent();

        $type = $this->request->getGet('type');

        // Jika eksplisit meminta tipe universitas (?type=uni) -> tampilkan detail universitas
        if ($type === 'uni') {
            return $this->showUniversityDetail($id);
        }

        // Default: detail karier publik
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

            // Ambil 6 universitas publik (fallback aman tanpa filter program)
            $uniBuilder = $this->unis->where('is_active', 1)->where('is_public', 1);
            $universities = $uniBuilder->orderBy('university_name', 'ASC')->findAll(6);

            // Rekomendasi karier lain dalam sektor yang sama (jika ada)
            $related = [];
            if (!empty($career['sector'])) {
                $related = $this->applyPublicScope(clone $this->careers)
                    ->where('sector', $career['sector'])
                    ->where('id !=', (int) $id)
                    ->orderBy('updated_at', 'DESC')
                    ->findAll(6);
            }

            return view('student/career/detail', [
                'career'       => $career,
                'skills'       => $skills,
                'links'        => $links,
                'universities' => $universities,
                'related'      => $related,
            ]);
        }

        // Jika bukan karier, coba fallback sebagai ID universitas agar URL /student/career/{id} tetap bisa dipakai
        return $this->showUniversityDetail($id);
    }

    /**
     * POST /student/career/save/(:num)
     * Simpan karier atau universitas ke daftar favorit siswa.
     */
    public function save(int $id)
    {
        $this->requireStudent();

        $type = $this->request->getGet('type');

        // Simpan perguruan tinggi
        if ($type === 'uni') {
            return $this->saveUniversityInternal($id);
        }

        // ------------------------------
        // Simpan karier (logika lama)
        // ------------------------------

        // validasi ID karir publik
        $exists = $this->getPublicCareer($id);
        if (!$exists) {
            return $this->respondBack(400, 'Karir tidak valid atau belum dipublikasikan.');
        }

        // Jika ada tabel student_saved_careers, simpan ke DB
        if ($this->db->tableExists('student_saved_careers')) {
            $row = $this->db->table('student_saved_careers')
                ->where('student_id', $this->studentId)
                ->where('career_id', (int) $id)
                ->get()->getRowArray();

            if (!$row) {
                $now = Time::now('Asia/Jakarta')->toDateTimeString();
                $this->db->table('student_saved_careers')->insert([
                    'student_id' => $this->studentId,
                    'career_id'  => (int) $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } else {
            // fallback session
            $saved = session()->get('saved_careers') ?? [];
            if (!in_array((int) $id, $saved, true)) {
                $saved[] = (int) $id;
                session()->set('saved_careers', $saved);
            }
        }

        return $this->respondForward(200, 'Karir disimpan.', '/student/career/saved');
    }

    /**
     * GET /student/career/saved
     * Daftar karier & perguruan tinggi yang disimpan siswa.
     */
    public function saved()
    {
        $this->requireStudent();

        // -------------------------------------------------
        // Karier tersimpan
        // -------------------------------------------------
        $careerList = [];
        if ($this->db->tableExists('student_saved_careers')) {
            $careerList = $this->applyPublicScope(clone $this->careers)
                ->select('career_options.*, creator.full_name AS created_by_name, ssc.created_at AS saved_at')
                ->join('student_saved_careers ssc', 'ssc.career_id = career_options.id', 'inner')
                ->join('users AS creator', 'creator.id = career_options.created_by', 'left')
                ->where('ssc.student_id', $this->studentId)
                ->orderBy('career_options.updated_at', 'DESC')
                ->findAll();
        } else {
            $ids = array_map('intval', session()->get('saved_careers') ?? []);
            if ($ids) {
                $careerList = $this->applyPublicScope(clone $this->careers)
                    ->whereIn('id', $ids)
                    ->findAll();
            }
        }

        // -------------------------------------------------
        // Perguruan tinggi tersimpan
        // -------------------------------------------------
        $uniList = [];
        if ($this->db->tableExists('student_saved_universities')) {
            $uniList = $this->unis
                ->select('university_info.*, creator.full_name AS created_by_name, ssu.created_at AS saved_at')
                ->join('student_saved_universities ssu', 'ssu.university_id = university_info.id', 'inner')
                ->join('users AS creator', 'creator.id = university_info.created_by', 'left')
                ->where('university_info.is_active', 1)
                ->where('university_info.is_public', 1)
                ->where('ssu.student_id', $this->studentId)
                ->orderBy('university_info.university_name', 'ASC')
                ->findAll();
        } else {
            $ids = array_map('intval', session()->get('saved_universities') ?? []);
            if ($ids) {
                $uniList = $this->unis
                    ->where('is_active', 1)
                    ->where('is_public', 1)
                    ->whereIn('id', $ids)
                    ->orderBy('university_name', 'ASC')
                    ->findAll();
            }
        }

        // Tentukan tab aktif di halaman tersimpan
        $activeTab = $this->request->getGet('tab') ?? 'careers';
        if (!in_array($activeTab, ['careers', 'universities'], true)) {
            $activeTab = 'careers';
        }

        return view('student/career/saved', [
            'careers'           => $careerList,
            'careerCount'       => count($careerList),
            'universities'      => $uniList,
            'universityCount'   => count($uniList),
            'activeTab'         => $activeTab,
        ]);
    }

    /**
     * POST /student/career/remove/(:num)
     * Hapus dari daftar tersimpan (karier atau universitas).
     */
    public function remove(int $id)
    {
        $this->requireStudent();

        $type = $this->request->getGet('type');

        // Hapus perguruan tinggi tersimpan
        if ($type === 'uni') {
            return $this->removeUniversityInternal($id);
        }

        // ---------------------------------
        // Hapus karier tersimpan (logika lama)
        // ---------------------------------
        if ($this->db->tableExists('student_saved_careers')) {
            $this->db->table('student_saved_careers')
                ->where('student_id', $this->studentId)
                ->where('career_id', (int) $id)
                ->delete();
        } else {
            $ids = session()->get('saved_careers') ?? [];
            $ids = array_values(array_filter($ids, fn ($v) => (int) $v !== (int) $id));
            session()->set('saved_careers', $ids);
        }

        return $this->respondForward(200, 'Berhasil dihapus dari tersimpan.', '/student/career/saved');
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Terapkan filter publik+aktif ke Model builder.
     * Dipaksa memakai nama tabel lengkap agar tidak ambiguous saat JOIN.
     */
    private function applyPublicScope(CareerOptionModel $builder): CareerOptionModel
    {
        // Selalu gunakan prefix "career_options." agar tidak berbenturan dengan kolom is_active milik tabel lain (mis. users)
        return $builder
            ->where('career_options.is_active', 1)
            ->where('career_options.is_public', 1);
    }

    /**
     * Ambil satu karier publik berdasarkan ID, beserta nama pembuatnya (jika ada).
     * Menggunakan prefix tabel eksplisit supaya tidak terjadi ambiguous column.
     */
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

    /**
     * Tampilkan detail universitas untuk siswa, beserta nama pembuat (jika ada).
     * Dipanggil dari detail() saat tipe=uni atau fallback jika bukan ID karier.
     */
    private function showUniversityDetail(int $id)
    {
        $uni = $this->unis
            ->select('university_info.*, creator.full_name AS created_by_name')
            ->join('users AS creator', 'creator.id = university_info.created_by', 'left')
            ->where('university_info.is_active', 1)
            ->where('university_info.is_public', 1)
            ->where('university_info.id', $id)
            ->first();

        if (!$uni) {
            return redirect()->to('/student/career')->with('error', 'Data perguruan tinggi tidak ditemukan atau belum dipublikasikan.');
        }

        // Decode fakultas & program jika disimpan sebagai JSON; fallback ke string biasa
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

        return view('student/career/university_detail', [
            'university' => $uni,
            'faculties'  => $faculties,
            'programs'   => $programs,
        ]);
    }

    /**
     * Simpan perguruan tinggi ke daftar tersimpan.
     */
    private function saveUniversityInternal(int $id)
    {
        // validasi ID universitas publik & aktif
        $uni = $this->unis
            ->where('is_active', 1)
            ->where('is_public', 1)
            ->find($id);

        if (!$uni) {
            return $this->respondBack(400, 'Perguruan tinggi tidak valid atau belum dipublikasikan.');
        }

        if ($this->db->tableExists('student_saved_universities')) {
            $row = $this->db->table('student_saved_universities')
                ->where('student_id', $this->studentId)
                ->where('university_id', (int) $id)
                ->get()->getRowArray();

            if (!$row) {
                $now = Time::now('Asia/Jakarta')->toDateTimeString();
                $this->db->table('student_saved_universities')->insert([
                    'student_id'    => $this->studentId,
                    'university_id' => (int) $id,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        } else {
            // fallback session
            $saved = array_map('intval', session()->get('saved_universities') ?? []);
            if (!in_array((int) $id, $saved, true)) {
                $saved[] = (int) $id;
                session()->set('saved_universities', $saved);
            }
        }

        return $this->respondForward(200, 'Perguruan tinggi disimpan.', '/student/career/saved?tab=universities');
    }

    /**
     * Hapus perguruan tinggi dari daftar tersimpan.
     */
    private function removeUniversityInternal(int $id)
    {
        if ($this->db->tableExists('student_saved_universities')) {
            $this->db->table('student_saved_universities')
                ->where('student_id', $this->studentId)
                ->where('university_id', (int) $id)
                ->delete();
        } else {
            $ids = array_map('intval', session()->get('saved_universities') ?? []);
            $ids = array_values(array_filter($ids, fn ($v) => (int) $v !== (int) $id));
            session()->set('saved_universities', $ids);
        }

        return $this->respondForward(200, 'Berhasil dihapus dari tersimpan.', '/student/career/saved?tab=universities');
    }

    /**
     * Jika request AJAX -> JSON, selain itu redirect dengan flashdata.
     */
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

    /**
     * Response sukses + redirect ke URL tujuan, JSON bila AJAX.
     */
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
