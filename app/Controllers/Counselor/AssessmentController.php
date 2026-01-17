<?php

/**
 * File Path: app/Controllers/Counselor/AssessmentController.php
 *
 * Assessment Controller (Guru BK)
 * Mengelola asesmen: daftar, buat, edit, kelola pertanyaan, target/jadwal, publish, hasil, review.
 * @updated 2026-01-02
 */

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Services\AssessmentService;
use App\Models\AssessmentModel;
use App\Models\AssessmentQuestionModel;
use App\Models\AssessmentResultModel;
use App\Models\ClassModel;
use App\Models\StudentModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;

class AssessmentController extends BaseController
{
    protected AssessmentService $svc;
    protected ClassModel $classModel;
    protected StudentModel $studentModel;

    public function __construct()
    {
        $this->svc = new AssessmentService();
        helper(['form', 'text']);

        $this->classModel   = new ClassModel();
        $this->studentModel = new StudentModel();
    }

    /**
     * Ambil context user login (id, role) dari auth_user() atau session.
     * @return array{id:int,role:string}
     */
    private function authContext(): array
    {
        $id = 0;
        $role = 'counselor';

        if (function_exists('auth_user')) {
            $u = auth_user();
            $id = (int)($u['id'] ?? 0);
            $role = (string)($u['role'] ?? $role);
        }

        if ($id <= 0) {
            $id = (int)(session('user_id') ?? 0);
        }

        // FIX: logika sebelumnya salah precedence (!session('role') === null)
        $roleSession = session('role');
        if ($roleSession !== null && $roleSession !== '') {
            $role = (string)$roleSession;
        }

        return ['id' => $id, 'role' => $role];
    }

    /**
     * Parse input datetime-local jadi 'Y-m-d H:i:s' atau null.
     */
    private function normalizeDateTimeLocal($raw): ?string
    {
        if ($raw === null) return null;
        $raw = trim((string)$raw);
        if ($raw === '') return null;

        $ts = strtotime($raw);
        if ($ts === false) return null;

        return date('Y-m-d H:i:s', $ts);
    }

    /**
     * Normalisasi durasi (menit): kosong / <=0 menjadi NULL (unlimited).
     */
    private function normalizeDurationMinutes($raw): ?int
    {
        if ($raw === '' || $raw === null) return null;
        $n = (int)$raw;
        return $n > 0 ? $n : null;
    }

    /**
     * Semua kelas aktif (untuk dropdown "Per Kelas")
     */
    private function getAllActiveClasses(): array
    {
        return $this->classModel
            ->select('id, class_name, grade_level')
            ->where('is_active', 1)
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }

    /**
     * Daftar tingkat (X, XI, XII, ...) dari tabel classes
     */
    private function getGradeOptions(): array
    {
        $rows = $this->classModel
            ->distinct()
            ->select('grade_level')
            ->where('is_active', 1)
            ->orderBy('grade_level', 'ASC')
            ->findAll();

        $grades = [];
        foreach ($rows as $row) {
            if (!empty($row['grade_level'])) {
                $grades[] = $row['grade_level'];
            }
        }
        return $grades;
    }

    /**
     * Utilitas: hitung jumlah peserta per assessment_id
     * @param int[] $ids
     * @return array<int,int> map [assessment_id => distinct_count(student_id)]
     */
    private function getParticipantCountsByAssessmentIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) return [];

        $db = Database::connect();
        $rows = $db->table('assessment_results')
            ->select('assessment_id, COUNT(DISTINCT student_id) AS cnt')
            ->whereIn('assessment_id', $ids)
            ->where('deleted_at', null)
            ->groupBy('assessment_id')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['assessment_id']] = (int)$r['cnt'];
        }
        return $out;
    }

    /**
     * Siswa binaan Guru BK yang sedang login
     * (kelas dengan counselor_id = user_id)
     */
    private function getStudentsForCounselor(): array
    {
        $ctx = $this->authContext();
        $counselorId = (int)$ctx['id'];
        if ($counselorId <= 0) {
            return [];
        }

        // Normalisasi NIS: gunakan COALESCE(nis, nisn) sebagai 'nis'
        return $this->studentModel
            ->select([
                'students.id',
                'u.full_name as full_name',
                'COALESCE(students.nis, students.nisn) as nis',
                'classes.class_name',
                'classes.grade_level',
            ])
            ->join('users u', 'u.id = students.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('classes.counselor_id', $counselorId)
            ->where('students.status', 'Aktif')
            ->orderBy('classes.grade_level', 'ASC')
            ->orderBy('classes.class_name', 'ASC')
            ->orderBy('u.full_name', 'ASC') // FIX: dulu students.full_name (kolom sudah dihapus)
            ->findAll();
    }

    /**
     * Builder siswa eligible sesuai target asesmen dan scope role.
     */
    protected function eligibleStudentQB(array $assessment, ?int $counselorId = null, bool $limitToCounselorScope = true)
    {
        $db = \Config\Database::connect();
        $qb = $db->table('students s')
            ->select('
                s.id,
                COALESCE(u.full_name, "") as full_name,
                COALESCE(s.nis, s.nisn) as nis,
                s.class_id,
                c.class_name,
                c.grade_level,
                c.major
            ')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->where('s.status', 'Aktif')
            ->where('s.deleted_at', null); // penting: QB tidak otomatis filter soft delete

        if ($limitToCounselorScope && $counselorId) {
            $qb->where('c.counselor_id', $counselorId);
        }

        $target = (string)($assessment['target_audience'] ?? 'All');
        if ($target === 'Class' && !empty($assessment['target_class_id'])) {
            $qb->where('s.class_id', (int)$assessment['target_class_id']);
        } elseif ($target === 'Grade' && !empty($assessment['target_grade'])) {
            $qb->where('c.grade_level', (string)$assessment['target_grade']);
        }

        return $qb->orderBy('c.grade_level', 'ASC')
            ->orderBy('c.class_name', 'ASC')
            ->orderBy('full_name', 'ASC'); // FIX: dulu s.full_name (kolom sudah dihapus)
    }

    /**
     * GET /counselor/assessments
     */
    public function index()
    {
        $filters = [
            'assessment_type' => $this->request->getGet('assessment_type') ?? '',
            'is_published'    => $this->request->getGet('is_published') ?? '',
            'target_audience' => $this->request->getGet('target_audience') ?? '',
            'evaluation_mode' => $this->request->getGet('evaluation_mode') ?? '',
            'search'          => $this->request->getGet('search') ?? '',
        ];

        // Stats ringkas
        $dashboard = $this->svc->getDashboardData();
        $stats = [
            'total_assessments' => (int)($dashboard['data']['totalAssessments'] ?? 0),
            'published'         => (int)($dashboard['data']['published'] ?? 0),
            'active'            => (int)($dashboard['data']['active'] ?? 0),
        ];
        $stats['draft'] = max(0, $stats['total_assessments'] - $stats['published']);

        // Tipe asesmen (dropdown)
        $assessment_types = [
            'Psikologi'   => 'Psikologi',
            'Minat Bakat' => 'Minat Bakat',
            'Kecerdasan'  => 'Kecerdasan',
            'Motivasi'    => 'Motivasi',
            'Custom'      => 'Custom',
        ];

        // Mode evaluasi (untuk filter & form)
        $evaluation_modes = [
            'pass_fail'  => 'Pass/Fail',
            'score_only' => 'Skor Saja',
            'survey'     => 'Survei (tanpa skor)',
        ];

        // Listing + filter
        $assessmentModel = new AssessmentModel();
        $builder = $assessmentModel->select('*');

        if ($filters['assessment_type'] !== '') {
            $builder->where('assessment_type', $filters['assessment_type']);
        }
        if ($filters['is_published'] !== '') {
            $builder->where('is_published', (int)$filters['is_published']);
        }
        if ($filters['target_audience'] !== '') {
            $builder->where('target_audience', $filters['target_audience']);
        }

        // Filter evaluation_mode dipetakan ke use_passing_score (kompatibel lama)
        if ($filters['evaluation_mode'] !== '') {
            if ($filters['evaluation_mode'] === 'pass_fail') {
                $builder->where('use_passing_score', 1);
            } else {
                $builder->where('use_passing_score', 0);
            }
        }

        if ($filters['search'] !== '') {
            $builder->groupStart()
                ->like('title', $filters['search'])
                ->orLike('description', $filters['search'])
                ->groupEnd();
        }

        $assessments = $builder->orderBy('id', 'DESC')->findAll();

        // Set total_participants dinamis (distinct student_id)
        $ids = array_column($assessments, 'id');
        $pcMap = $this->getParticipantCountsByAssessmentIds($ids);
        foreach ($assessments as &$a) {
            $aid = (int)($a['id'] ?? 0);
            if ($aid > 0) {
                $a['total_participants'] = (int)($pcMap[$aid] ?? (int)($a['total_participants'] ?? 0));
            }
        }
        unset($a);

        return view('counselor/assessments/index', compact(
            'stats',
            'assessment_types',
            'evaluation_modes',
            'filters',
            'assessments'
        ));
    }

    /**
     * GET /counselor/assessments/create
     */
    public function create()
    {
        $assessment = [
            'is_active'               => 1,
            'is_published'            => 0,
            'show_result_immediately' => 1,
            'allow_review'            => 1,
            'max_attempts'            => 1,
            // default untuk form baru
            'evaluation_mode'         => 'pass_fail',
            'show_score_to_student'   => 1,
            'use_passing_score'       => 1,
        ];

        $classes  = $this->getAllActiveClasses();
        $grades   = $this->getGradeOptions();
        $students = $this->getStudentsForCounselor();

        $evaluation_modes = [
            'pass_fail'  => 'Pass/Fail',
            'score_only' => 'Skor Saja',
            'survey'     => 'Survei (tanpa skor)',
        ];

        return view('counselor/assessments/form', [
            'title'            => 'Buat Asesmen',
            'method'           => 'create',
            'assessment'       => $assessment,
            'classes'          => $classes,
            'grades'           => $grades,
            'students'         => $students,
            'evaluation_modes' => $evaluation_modes,
        ]);
    }

    /**
     * POST /counselor/assessments/store
     */
    public function store(): RedirectResponse
    {
        helper(['form', 'url']);

        $ctx = $this->authContext();
        $uid = (int)$ctx['id'];

        if ($uid <= 0) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Sesi login kadaluarsa. Silakan login ulang.');
        }

        // Ambil evaluation_mode
        $mode = (string)($this->request->getPost('evaluation_mode') ?? 'pass_fail');
        if (!in_array($mode, ['pass_fail', 'score_only', 'survey'], true)) {
            $mode = 'pass_fail';
        }

        $showScore = (int)($this->request->getPost('show_score_to_student') ? 1 : 0);

        // Checkbox gunakan passing score (opsional di form)
        $usePassing = $this->request->getPost('use_passing_score');
        $usePassing = ($usePassing === null || $usePassing === '')
            ? ($mode === 'pass_fail' ? 1 : 0)
            : (int)$usePassing;

        // Normalisasi durasi
        $duration = $this->normalizeDurationMinutes($this->request->getPost('duration_minutes'));

        // Normalisasi jadwal rilis hasil
        $resultRelease = $this->normalizeDateTimeLocal($this->request->getPost('result_release_at'));

        // Normalisasi passing_score: clamp 0..100 atau null
        $rawPs = $this->request->getPost('passing_score');
        if ($rawPs === '' || $rawPs === null) {
            $passingScore = null;
        } elseif (is_numeric($rawPs)) {
            $passingScore = (float)$rawPs;
            if ($passingScore < 0) $passingScore = 0;
            if ($passingScore > 100) $passingScore = 100;
        } else {
            $passingScore = null;
        }

        // Flag use_passing_score hanya aktif untuk mode pass_fail
        $usePassingFlag = ($mode === 'pass_fail' && $usePassing === 1) ? 1 : 0;

        $payload = [
            'title'                   => trim((string)$this->request->getPost('title')),
            'description'             => trim((string)$this->request->getPost('description')),
            'assessment_type'         => trim((string)$this->request->getPost('assessment_type')),
            'target_audience'         => trim((string)$this->request->getPost('target_audience')),
            'target_class_id'         => $this->request->getPost('target_class_id') ?: null,
            'target_grade'            => $this->request->getPost('target_grade') ?: null,
            'created_by'              => $uid,
            'is_active'               => $this->request->getPost('is_active') ? 1 : 0,
            'is_published'            => $this->request->getPost('is_published') ? 1 : 0,
            'start_date'              => $this->request->getPost('start_date') ?: null,
            'end_date'                => $this->request->getPost('end_date') ?: null,
            'duration_minutes'        => $duration,
            'passing_score'           => $passingScore,
            'max_attempts'            => $this->request->getPost('max_attempts') ?: null,
            'show_result_immediately' => $this->request->getPost('show_result_immediately') ? 1 : 0,
            'allow_review'            => $this->request->getPost('allow_review') ? 1 : 0,
            'result_release_at'       => $resultRelease,
            'instructions'            => $this->request->getPost('instructions') ?: null,

            // Kolom DB baru (atau fallback) agar view lama aman
            'evaluation_mode'         => $mode,
            'show_score_to_student'   => $showScore,
            'use_passing_score'       => $usePassingFlag,

            // Non-DB (dibersihkan di service)
            'options'                 => $this->request->getPost('options') ?: null,
        ];

        try {
            $this->svc->create($payload);

            return redirect()->to(base_url('counselor/assessments'))
                ->with('success', 'Asesmen berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal membuat asesmen: ' . $e->getMessage());
        }
    }

    /**
     * GET /counselor/assessments/{id}
     */
    public function show(int $id)
    {
        $assessmentModel = new AssessmentModel();
        $questionModel   = new AssessmentQuestionModel();
        $resultModel     = new AssessmentResultModel();
        $classModel      = new ClassModel();

        $assessment = $assessmentModel->asArray()->find($id);
        if (!$assessment) {
            throw PageNotFoundException::forPageNotFound('Asesmen tidak ditemukan');
        }

        if (!empty($assessment['target_class_id'])) {
            $cls = $classModel->asArray()->find((int)$assessment['target_class_id']);
            $assessment['target_class_name'] = $cls['class_name'] ?? null;
        }

        $statistics    = $resultModel->getAssessmentStatistics($id);
        $questions     = method_exists($questionModel, 'getByAssessment')
            ? $questionModel->getByAssessment($id, true)
            : $questionModel->where('assessment_id', $id)->orderBy('order_number', 'ASC')->findAll();
        $topPerformers = $resultModel->getTopPerformers($id, 5);

        // total_participants dinamis
        $pcMap = $this->getParticipantCountsByAssessmentIds([$id]);
        $assessment['total_participants'] = (int)(
            $pcMap[$id]
            ?? ($statistics['total_participants'] ?? ($statistics['total_attempts'] ?? (int)($assessment['total_participants'] ?? 0)))
        );

        return view('counselor/assessments/show', compact(
            'assessment',
            'statistics',
            'questions',
            'topPerformers'
        ));
    }

    /**
     * GET /counselor/assessments/{id}/edit
     */
    public function edit($id)
    {
        $m = new AssessmentModel();

        $assessment = $m->find((int)$id);
        if (!$assessment) {
            return redirect()->to('/counselor/assessments')->with('error', 'Asesmen tidak ditemukan.');
        }

        $classes  = $this->getAllActiveClasses();
        $grades   = $this->getGradeOptions();
        $students = $this->getStudentsForCounselor();

        $evaluation_modes = [
            'pass_fail'  => 'Pass/Fail',
            'score_only' => 'Skor Saja',
            'survey'     => 'Survei (tanpa skor)',
        ];

        // Sinkron flag kompatibilitas untuk UI lama
        if (!isset($assessment['show_score_to_student'])) {
            $assessment['show_score_to_student'] = (int)($assessment['show_result_immediately'] ?? 1);
        }
        if (!isset($assessment['evaluation_mode'])) {
            $assessment['evaluation_mode'] = ((int)($assessment['use_passing_score'] ?? 1) === 1)
                ? 'pass_fail'
                : 'score_only';
        }
        if (!isset($assessment['use_passing_score'])) {
            $assessment['use_passing_score'] = ($assessment['evaluation_mode'] === 'pass_fail') ? 1 : 0;
        }

        return view('counselor/assessments/form', [
            'title'            => 'Ubah Asesmen',
            'method'           => 'edit',
            'assessment'       => $assessment,
            'classes'          => $classes,
            'grades'           => $grades,
            'students'         => $students,
            'evaluation_modes' => $evaluation_modes,
        ]);
    }

    /**
     * POST /counselor/assessments/{id}/update
     */
    public function update($id): RedirectResponse
    {
        $mode = (string)($this->request->getPost('evaluation_mode') ?? 'pass_fail');
        if (!in_array($mode, ['pass_fail', 'score_only', 'survey'], true)) {
            $mode = 'pass_fail';
        }

        $usePassing = $this->request->getPost('use_passing_score');
        $usePassing = ($usePassing === null || $usePassing === '')
            ? ($mode === 'pass_fail' ? 1 : 0)
            : (int)$usePassing;

        // Guard: kalau class validation tidak ada, jangan fatal.
        $rules = [];
        $msgs  = [];
        if (class_exists(\App\Validation\AssessmentValidation::class) && method_exists(\App\Validation\AssessmentValidation::class, 'rules')) {
            $rules = \App\Validation\AssessmentValidation::rules();
            $msgs  = method_exists(\App\Validation\AssessmentValidation::class, 'messages')
                ? \App\Validation\AssessmentValidation::messages()
                : [];
        } else {
            // Minimal rules (fallback aman)
            $rules = [
                'title'           => 'required|min_length[3]',
                'assessment_type' => 'required',
                'target_audience' => 'required',
            ];
            $msgs = [];
        }

        // passing_score tidak wajib bila bukan pass/fail atau usePassing=0
        if ($mode !== 'pass_fail' || $usePassing === 0) {
            unset($rules['passing_score']);
        }

        if (!empty($rules) && !$this->validate($rules, $msgs)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $showScore = (int)($this->request->getPost('show_score_to_student') ? 1 : 0);

        $payload = $this->request->getPost([
            'title',
            'description',
            'assessment_type',
            'target_audience',
            'target_class_id',
            'target_grade',
            'start_date',
            'end_date',
            'duration_minutes',
            'passing_score',
            'max_attempts',
            'show_result_immediately',
            'allow_review',
            'instructions',
            'is_active',
            'is_published',
            'result_release_at',
        ]);

        // Normalisasi flag boolean dari checkbox
        $payload['is_active']               = !empty($payload['is_active']) ? 1 : 0;
        $payload['is_published']            = !empty($payload['is_published']) ? 1 : 0;
        $payload['show_result_immediately'] = !empty($payload['show_result_immediately']) ? 1 : 0;
        $payload['allow_review']            = !empty($payload['allow_review']) ? 1 : 0;

        // Normalisasi durasi & rilis hasil
        $payload['duration_minutes']  = $this->normalizeDurationMinutes($payload['duration_minutes'] ?? null);
        $payload['result_release_at'] = $this->normalizeDateTimeLocal($payload['result_release_at'] ?? null);

        // Normalisasi passing_score: clamp 0..100 atau null
        $rawPs = $payload['passing_score'] ?? null;
        if ($rawPs === '' || $rawPs === null) {
            $payload['passing_score'] = null;
        } elseif (is_numeric($rawPs)) {
            $ps = (float)$rawPs;
            if ($ps < 0) $ps = 0;
            if ($ps > 100) $ps = 100;
            $payload['passing_score'] = $ps;
        } else {
            $payload['passing_score'] = null;
        }

        // Field tambahan
        $payload['evaluation_mode']       = $mode;
        $payload['show_score_to_student'] = $showScore;
        $payload['use_passing_score']     = ($mode === 'pass_fail' && $usePassing === 1) ? 1 : 0;

        try {
            $this->svc->updateAssessment((int)$id, $payload);
            return redirect()->to('/counselor/assessments')->with('success', 'Asesmen diperbarui.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (DatabaseException $e) {
            log_message('error', 'DB error update assessment: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan asesmen (Database constraint).');
        } catch (\Throwable $e) {
            log_message('error', 'Update assessment failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan asesmen.');
        }
    }

    /**
     * POST /counselor/assessments/{id}/toggle
     */
    public function toggle($id): RedirectResponse
    {
        $this->svc->toggleActive((int)$id);
        return redirect()->back()->with('success', 'Status asesmen diubah.');
    }

    /**
     * POST atau GET /counselor/assessments/{id}/publish
     */
    public function publish(int $id): RedirectResponse
    {
        // GET kompatibel: ?v=1|0; default publish (true)
        $queryToggle = $this->request->getGet('v');
        if ($queryToggle !== null) {
            $v = (int)$queryToggle === 1;
            $this->svc->publish($id, $v);
            return redirect()->back()->with('success', $v ? 'Asesmen dipublikasi.' : 'Publikasi asesmen dibatalkan.');
        }

        // POST default -> publish
        try {
            if (method_exists($this->svc, 'publishAssessment')) {
                $res = $this->svc->publishAssessment($id);
                return redirect()->back()->with($res['success'] ? 'success' : 'error', $res['message']);
            }
            $this->svc->publish($id, true);
            return redirect()->back()->with('success', 'Asesmen dipublikasi.');
        } catch (\Throwable $e) {
            log_message('error', 'Publish error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mempublikasi asesmen.');
        }
    }

    /**
     * POST atau GET /counselor/assessments/{id}/unpublish
     */
    public function unpublish(int $id): RedirectResponse
    {
        try {
            if (method_exists($this->svc, 'unpublishAssessment')) {
                $res = $this->svc->unpublishAssessment($id);
                return redirect()->back()->with($res['success'] ? 'success' : 'error', $res['message']);
            }
            $this->svc->publish($id, false);
            return redirect()->back()->with('success', 'Publikasi asesmen dibatalkan.');
        } catch (\Throwable $e) {
            log_message('error', 'Unpublish error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membatalkan publikasi asesmen.');
        }
    }

    /**
     * POST /counselor/assessments/{id}/duplicate
     */
    public function duplicate(int $id): RedirectResponse
    {
        $ctx = $this->authContext();
        $createdBy = (int)$ctx['id'];

        try {
            $res = $this->svc->duplicateAssessment($id, $createdBy);
            if (is_array($res) && isset($res['success'])) {
                return redirect()->to('/counselor/assessments')->with($res['success'] ? 'success' : 'error', $res['message']);
            }
            return redirect()->to('/counselor/assessments')->with('success', 'Asesmen berhasil diduplikasi.');
        } catch (\Throwable $e) {
            log_message('error', 'Duplicate error: ' . $e->getMessage());
            return redirect()->to('/counselor/assessments')->with('error', 'Gagal menduplikasi asesmen.');
        }
    }

    /**
     * POST /counselor/assessments/{id}/delete
     */
    public function delete(int $id): RedirectResponse
    {
        try {
            $model = new AssessmentModel();
            $model->delete($id);
            return redirect()->to('/counselor/assessments')->with('success', 'Asesmen berhasil dihapus.');
        } catch (\Throwable $e) {
            log_message('error', 'Delete assessment failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus asesmen.');
        }
    }

    /**
     * GET /counselor/assessments/{id}/questions
     */
    public function questions($assessmentId)
    {
        $m = new AssessmentModel();
        $q = new AssessmentQuestionModel();

        $assessment = $m->find((int)$assessmentId);
        if (!$assessment) {
            return redirect()->to('/counselor/assessments')->with('error', 'Asesmen tidak ditemukan.');
        }

        $questions = method_exists($q, 'getByAssessment')
            ? $q->getByAssessment((int)$assessmentId, true)
            : $q->where('assessment_id', $assessmentId)->orderBy('order_number', 'ASC')->findAll();

        return view('counselor/assessments/questions', [
            'assessment' => $assessment,
            'questions'  => $questions
        ]);
    }

    /**
     * Simpan file upload ke public/uploads/assessment_questions dan
     * kembalikan path relatif "uploads/assessment_questions/xxx.ext"
     */
    private function saveImageUpload(?\CodeIgniter\HTTP\Files\UploadedFile $file): ?string
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        // Validasi ringan
        $max = 2 * 1024 * 1024; // 2MB
        if ($file->getSize() > $max) return null;

        $mime = strtolower((string)$file->getMimeType());
        $okMime = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $okMime, true)) return null;

        $ext = strtolower($file->getExtension() ?: 'jpg');
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExt, true)) $ext = 'jpg';

        $targetDir = rtrim(FCPATH, "/\\") . '/uploads/assessment_questions';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        $name = 'q_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->move($targetDir, $name, true);

        return 'uploads/assessment_questions/' . $name;
    }

    /**
     * POST /counselor/assessments/{id}/questions/add
     */
    public function addQuestion($assessmentId): RedirectResponse
    {
        $qData = $this->request->getPost([
            'question_text', 'question_type', 'points', 'is_required', 'explanation', 'dimension'
        ]);
        $options        = $this->request->getPost('options');
        $correctOption  = $this->request->getPost('correct_option');     // radio (MC/TF/Rating)
        $correctOptions = $this->request->getPost('correct_options');    // array (Checkbox)

        // Normalisasi
        $qData['is_required'] = (int)($this->request->getPost('is_required') ?? 0);
        $qData['points']      = ($qData['points'] === '' ? 1 : (float)$qData['points']);
        $qData['options']     = is_array($options) ? array_values(array_filter($options, fn($v) => $v !== '' && $v !== null)) : [];

        // Pertanyaan "survei/tidak dinilai" jika points <= 0
        $isUngraded = ((float)($qData['points'] ?? 0)) <= 0;

        // Validasi jawaban benar (hanya wajib jika dinilai)
        $needsCorrect = in_array($qData['question_type'], ['Multiple Choice', 'True/False', 'Checkbox'], true);

        if ($needsCorrect && !$isUngraded) {
            if ($qData['question_type'] === 'Checkbox') {
                $marked = is_array($correctOptions)
                    ? array_values(array_filter($correctOptions, fn($v) => trim((string)$v) !== ''))
                    : [];

                if (count($marked) === 0) {
                    return redirect()->back()->withInput()
                        ->with('error', 'Pilih minimal satu opsi sebagai jawaban benar untuk tipe Checkbox.');
                }

                if (!empty($qData['options'])) {
                    $optSet = array_map('strval', $qData['options']);
                    foreach ($marked as $mv) {
                        if (!in_array((string)$mv, $optSet, true)) {
                            return redirect()->back()->withInput()
                                ->with('error', 'Jawaban benar harus berupa salah satu dari Options.');
                        }
                    }
                }
            } else {
                $opt = (string)($correctOption ?? '');
                if ($opt === '') {
                    return redirect()->back()->withInput()
                        ->with('error', 'Pilih satu opsi sebagai jawaban benar.');
                }
                if (!empty($qData['options'])) {
                    $optSet = array_map('strval', $qData['options']);
                    if (!in_array($opt, $optSet, true)) {
                        return redirect()->back()->withInput()
                            ->with('error', 'Jawaban benar harus berupa salah satu dari Options.');
                    }
                }
            }
        }

        // Gambar
        $imgSrc = (string)$this->request->getPost('image_source'); // 'url'|'upload'
        if ($imgSrc === 'upload') {
            $qData['image_url'] = $this->saveImageUpload($this->request->getFile('image_file'));
        } elseif ($imgSrc === 'url') {
            $url = trim((string)$this->request->getPost('image_url'));
            $qData['image_url'] = $url !== '' ? $url : null;
        } else {
            $qData['image_url'] = null;
        }

        // Simpan correct_answer sesuai tipe
        switch ($qData['question_type']) {
            case 'Checkbox':
                $marked = is_array($correctOptions)
                    ? array_values(array_filter($correctOptions, fn($v) => trim((string)$v) !== ''))
                    : [];
                $qData['correct_answer'] = !empty($marked) ? json_encode(array_values($marked), JSON_UNESCAPED_UNICODE) : null;
                break;

            case 'Multiple Choice':
            case 'True/False':
            case 'Rating Scale':
                $qData['correct_answer'] = ($correctOption !== null && $correctOption !== '') ? (string)$correctOption : null;
                break;

            default:
                $qData['correct_answer'] = null;
        }

        $this->svc->addQuestion((int)$assessmentId, $qData);
        return redirect()->back()->with('success', 'Pertanyaan ditambahkan.');
    }

    /**
     * POST /counselor/assessments/{id}/questions/{questionId}/update
     */
    public function updateQuestion($assessmentId, $questionId): RedirectResponse
    {
        $data = $this->request->getPost([
            'question_text', 'question_type', 'options', 'points', 'is_required', 'explanation', 'dimension'
        ]);

        $data['is_required'] = (int)($this->request->getPost('is_required') ?? 0);
        $data['points']      = ($data['points'] === '' || $data['points'] === null) ? 1 : (float)$data['points'];
        if (!is_array($data['options'])) $data['options'] = [];
        $data['options'] = array_values(array_filter($data['options'], fn($v) => $v !== '' && $v !== null));

        // Pertanyaan "survei/tidak dinilai" jika points <= 0
        $isUngraded = ((float)($data['points'] ?? 0)) <= 0;

        $correctOption  = $this->request->getPost('correct_option');
        $correctOptions = $this->request->getPost('correct_options');

        // Validasi jawaban benar (hanya wajib jika dinilai)
        $needsCorrect = in_array($data['question_type'], ['Multiple Choice', 'True/False', 'Checkbox'], true);

        if ($needsCorrect && !$isUngraded) {
            if ($data['question_type'] === 'Checkbox') {
                $marked = is_array($correctOptions)
                    ? array_values(array_filter($correctOptions, fn($v) => trim((string)$v) !== ''))
                    : [];
                if (count($marked) === 0) {
                    return redirect()->back()->withInput()
                        ->with('error', 'Pilih minimal satu opsi sebagai jawaban benar untuk tipe Checkbox.');
                }
                if (!empty($data['options'])) {
                    $optSet = array_map('strval', $data['options']);
                    foreach ($marked as $mv) {
                        if (!in_array((string)$mv, $optSet, true)) {
                            return redirect()->back()->withInput()
                                ->with('error', 'Jawaban benar harus berupa salah satu dari Options.');
                        }
                    }
                }
            } else {
                $opt = (string)($correctOption ?? '');
                if ($opt === '') {
                    return redirect()->back()->withInput()
                        ->with('error', 'Pilih satu opsi sebagai jawaban benar.');
                }
                if (!empty($data['options'])) {
                    $optSet = array_map('strval', $data['options']);
                    if (!in_array($opt, $optSet, true)) {
                        return redirect()->back()->withInput()
                            ->with('error', 'Jawaban benar harus berupa salah satu dari Options.');
                    }
                }
            }
        }

        // Gambar pada edit
        $qModel   = new AssessmentQuestionModel();
        $existing = $qModel->asArray()->find((int)$questionId);
        $imgSrc   = (string)$this->request->getPost('image_source');

        if ($imgSrc === 'upload') {
            $newPath = $this->saveImageUpload($this->request->getFile('image_file'));
            $data['image_url'] = $newPath ?: ($existing['image_url'] ?? null);
        } elseif ($imgSrc === 'url') {
            $url = trim((string)$this->request->getPost('image_url'));
            $data['image_url'] = ($url !== '') ? $url : null;
        } else {
            $data['image_url'] = $existing['image_url'] ?? null;
        }

        // Simpan correct_answer sesuai tipe
        switch ($data['question_type']) {
            case 'Checkbox':
                $marked = is_array($correctOptions)
                    ? array_values(array_filter($correctOptions, fn($v) => trim((string)$v) !== ''))
                    : [];
                $data['correct_answer'] = !empty($marked) ? json_encode(array_values($marked), JSON_UNESCAPED_UNICODE) : null;
                break;

            case 'Multiple Choice':
            case 'True/False':
            case 'Rating Scale':
                $data['correct_answer'] = ($correctOption !== null && $correctOption !== '') ? (string)$correctOption : null;
                break;

            default:
                $data['correct_answer'] = null;
        }

        // Reorder jika dikirim
        $orderNo = (int)($this->request->getPost('order_no') ?? 0);
        if ($orderNo > 0) {
            if (!$qModel->moveToOrder((int)$questionId, $orderNo)) {
                return redirect()->back()->with('error', 'Gagal mengubah urutan pertanyaan.');
            }
        }

        $res = $this->svc->updateQuestion((int)$questionId, $data);
        return redirect()->back()->with($res['success'] ? 'success' : 'error', $res['message']);
    }

    /**
     * POST /counselor/assessments/{id}/questions/{questionId}/delete
     */
    public function deleteQuestion($assessmentId, $questionId): RedirectResponse
    {
        $res = $this->svc->removeQuestion((int)$questionId);
        if (is_array($res)) {
            return redirect()->back()->with($res['success'] ? 'success' : 'error', $res['message']);
        }
        return redirect()->back()->with('success', 'Pertanyaan dihapus.');
    }

    /**
     * GET /counselor/assessments/{id}/assign
     */
    public function assign($id)
    {
        $m = new AssessmentModel();
        $assessment = $m->find((int)$id);
        if (!$assessment) {
            return redirect()->to('/counselor/assessments')->with('error', 'Asesmen tidak ditemukan.');
        }

        $classModel = new ClassModel();
        $classes = $classModel->select('id, class_name')->orderBy('class_name', 'ASC')->findAll();

        $ctx = $this->authContext();
        $uid = (int)$ctx['id'];
        $role = (string)$ctx['role'];
        $limitToCounselorScope = !in_array($role, ['admin', 'superadmin'], true);

        $eligible = $this->eligibleStudentQB($assessment, $uid, $limitToCounselorScope)->get()->getResultArray();

        $assignedMap = method_exists($this->svc, 'getAssignedMap') ? $this->svc->getAssignedMap((int)$id) : [];

        // Sembunyikan siswa yang sudah ditugaskan
        $eligible = array_values(array_filter($eligible, function ($s) use ($assignedMap) {
            $sid = (int)($s['id'] ?? 0);
            return $sid > 0 && !isset($assignedMap[$sid]);
        }));

        // Kelompokkan untuk view lama (students_by_class)
        $students_by_class = [];
        foreach ($eligible as $s) {
            $cn = $s['class_name'] ?? 'Tanpa Kelas';
            $students_by_class[$cn][] = [
                'id'         => (int)$s['id'],
                'full_name'  => $s['full_name'],
                'nisn'       => $s['nis'] ?? null,
                'class_name' => $s['class_name'] ?? null,
            ];
        }

        return view('counselor/assessments/assign', [
            'assessment'        => $assessment,
            'classes'           => $classes,
            'students_by_class' => $students_by_class,
            'assignedMap'       => $assignedMap,
        ]);
    }

    /**
     * POST /counselor/assessments/{id}/assign/process
     */
    public function processAssign($id): RedirectResponse
    {
        $studentIds = $this->request->getPost('student_ids');
        if (!is_array($studentIds) || empty($studentIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu siswa.');
        }

        $m = new AssessmentModel();
        $assessment = $m->find((int)$id);
        if (!$assessment) {
            return redirect()->to('/counselor/assessments')->with('error', 'Asesmen tidak ditemukan.');
        }

        $ctx = $this->authContext();
        $uid = (int)$ctx['id'];
        $role = (string)$ctx['role'];
        $limitToCounselorScope = !in_array($role, ['admin', 'superadmin'], true);

        // Whitelist eligible IDs
        $eligibleIds = $this->eligibleStudentQB($assessment, $uid, $limitToCounselorScope)
            ->select('s.id')
            ->get()->getResultArray();
        $eligibleIds = array_map(fn($r) => (int)$r['id'], $eligibleIds);

        $postedIds = array_values(array_unique(array_map('intval', $studentIds)));
        $finalIds  = array_values(array_intersect($postedIds, $eligibleIds));
        $rejected  = array_values(array_diff($postedIds, $eligibleIds));

        if (empty($finalIds)) {
            return redirect()->back()->with('error', 'Tidak ada siswa yang sesuai dengan target asesmen.')->withInput();
        }

        // Replace mode: cabut Assigned eligible yang tidak dipilih
        $replace = (bool)($this->request->getPost('replace_assignments') ?? false);
        $removedCount = 0;

        if ($replace && method_exists($this->svc, 'revokeAssignments')) {
            $assignedMap = method_exists($this->svc, 'getAssignedMap') ? $this->svc->getAssignedMap((int)$id) : [];
            $alreadyAssignedEligible = array_values(array_intersect(array_keys($assignedMap), $eligibleIds));
            $toRemove = array_values(array_diff($alreadyAssignedEligible, $finalIds));
            if (!empty($toRemove)) {
                $res = $this->svc->revokeAssignments((int)$id, $toRemove);
                $removedCount = (int)($res['data']['assigned_removed'] ?? 0);
            }
        }

        // Hormati kuota attempt & in-progress (tetap dipertahankan)
        $resultModel   = new AssessmentResultModel();
        $maxAttempts   = (int)($assessment['max_attempts'] ?? 0); // 0 = unlimited
        $eligibleToAssign = [];
        $skippedInProg = 0;
        $skippedQuota  = 0;

        foreach ($finalIds as $sid) {
            $sid = (int)$sid;

            if ($resultModel->getInProgressAttempt((int)$id, $sid)) {
                $skippedInProg++;
                continue;
            }

            if ($maxAttempts > 0) {
                $used = $resultModel->countUsedAttempts((int)$id, $sid);
                if ($used >= $maxAttempts) {
                    $skippedQuota++;
                    continue;
                }
            }

            $eligibleToAssign[] = $sid;
        }

        if (empty($eligibleToAssign)) {
            $msg = 'Tidak ada siswa yang bisa ditugaskan (terhalang attempt berjalan atau kuota).';
            if (!empty($rejected)) $msg .= ' ' . count($rejected) . ' siswa diabaikan karena tidak sesuai target.';
            return redirect()->back()->with('error', $msg);
        }

        // Prioritaskan service assignStudents agar assessment_assignees ikut tersinkron.
        $created = 0;
        if (method_exists($this->svc, 'assignStudents')) {
            $out = $this->svc->assignStudents((int)$id, $eligibleToAssign);
            $created = (int)($out['data']['assigned_results'] ?? 0);
        } else {
            // Fallback lama
            foreach ($eligibleToAssign as $sid) {
                $rid = $resultModel->createAssignment((int)$id, (int)$sid);
                if ($rid) $created++;
            }
        }

        $msg = "Asesmen ditugaskan ke {$created} siswa.";
        if ($removedCount > 0) $msg .= " {$removedCount} penugasan dicabut (replace mode).";
        if ($skippedInProg > 0) $msg .= " {$skippedInProg} siswa dilewati karena masih punya attempt berjalan.";
        if ($skippedQuota > 0)  $msg .= " {$skippedQuota} siswa melewati batas percobaan.";
        if (!empty($rejected))  $msg .= ' ' . count($rejected) . ' siswa diabaikan karena tidak sesuai target.';

        return redirect()->to('/counselor/assessments/' . $id . '/results')
            ->with('success', $msg);
    }

    /**
     * POST /counselor/assessments/{id}/assign/revoke
     */
    public function revokeAssign($id): RedirectResponse
    {
        $studentIds = $this->request->getPost('student_ids');
        if (!is_array($studentIds) || empty($studentIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu siswa untuk dicabut penugasannya.');
        }

        if (!method_exists($this->svc, 'revokeAssignments')) {
            return redirect()->back()->with('error', 'Fitur revoke tidak tersedia.');
        }

        $res = $this->svc->revokeAssignments((int)$id, array_map('intval', $studentIds));
        $msg = $res['success']
            ? 'Penugasan dicabut: ' . ($res['data']['assigned_removed'] ?? 0) . ' baris Assigned dihapus.'
            : 'Gagal mencabut penugasan: ' . $res['message'];

        return redirect()->back()->with($res['success'] ? 'success' : 'error', $msg);
    }

    /**
     * POST /counselor/assessments/{id}/assign/sync
     */
    public function syncAssignments($id): RedirectResponse
    {
        if (!method_exists($this->svc, 'syncAssignmentsToResults')) {
            return redirect()->back()->with('error', 'Fitur sinkronisasi tidak tersedia.');
        }
        $res = $this->svc->syncAssignmentsToResults((int)$id);
        $msg = $res['success']
            ? 'Sinkronisasi selesai. Dibuat: ' . ((int)($res['data']['created_assigned'] ?? 0)) . ' Assigned.'
            : 'Gagal sinkronisasi: ' . $res['message'];

        return redirect()->back()->with($res['success'] ? 'success' : 'error', $msg);
    }

    /**
     * GET /counselor/assessments/{id}/results
     */
    public function results($id)
    {
        $m = new AssessmentModel();
        $assessment = $m->find((int)$id);
        if (!$assessment) {
            return redirect()->to('/counselor/assessments')->with('error', 'Asesmen tidak ditemukan.');
        }

        $filters = [
            'status'     => $this->request->getGet('status') ?? '',
            'class_id'   => $this->request->getGet('class_id') ?? '',
            'is_passed'  => $this->request->getGet('is_passed') ?? '',
            'search'     => $this->request->getGet('search') ?? '',
        ];

        $resultModel = new AssessmentResultModel();
        $results     = $resultModel->getByAssessment((int)$id, $filters);

        $statistics = $resultModel->getAssessmentStatistics((int)$id);

        // total_participants dinamis (global, bukan filter table)
        $pcMap = $this->getParticipantCountsByAssessmentIds([(int)$id]);
        $statistics['total_participants'] = (int)($pcMap[(int)$id] ?? 0);

        $classModel = new ClassModel();
        $classes    = $classModel->select('id, class_name')->orderBy('class_name', 'ASC')->findAll();

        return view('counselor/assessments/results', [
            'assessment' => $assessment,
            'results'    => $results,
            'statistics' => $statistics,
            'classes'    => $classes,
            'filters'    => $filters,
        ]);
    }

    /**
     * GET /counselor/assessments/{assessmentId}/results/{resultId}
     */
    public function resultDetail($assessmentId, $resultId)
    {
        $rm = new AssessmentResultModel();
        $detail = $rm->getResultWithDetails((int)$resultId);

        if (!$detail) {
            return redirect()->to('/counselor/assessments/' . $assessmentId . '/results')
                ->with('error', 'Detail hasil tidak ditemukan.');
        }

        $db = \Config\Database::connect();

        $result = $db->table('assessment_results r')
            ->select("
                r.*,
                a.title AS assessment_title,
                a.passing_score,
                COALESCE(
                    a.evaluation_mode,
                    CASE WHEN a.use_passing_score = 1 THEN 'pass_fail' ELSE 'score_only' END
                ) AS evaluation_mode,
                COALESCE(a.show_score_to_student, a.show_result_immediately) AS show_score_to_student,
                su.full_name AS student_name,
                s.nisn,
                s.nis,
                c.class_name
            ")
            ->join('assessments a', 'a.id = r.assessment_id AND a.deleted_at IS NULL', 'inner')
            ->join('students s', 's.id = r.student_id AND s.deleted_at IS NULL', 'inner')
            ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('r.id', (int) $resultId)
            ->where('r.assessment_id', (int) $assessmentId)
            ->where('r.deleted_at', null)
            ->get()->getRowArray();

        if (!$result) {
            return redirect()->to(site_url("counselor/assessments/{$assessmentId}/results"))
                ->with('error', 'Hasil tidak ditemukan.');
        }

        $questions = $db->table('assessment_questions q')
            ->select('
                q.*,
                ans.id AS answer_id, ans.answer_text, ans.answer_option, ans.answer_options,
                ans.score AS answer_score, ans.is_correct, ans.is_auto_graded, ans.feedback,
                ans.answered_at, ans.time_spent_seconds
            ')
            ->join(
                'assessment_answers ans',
                'ans.question_id = q.id AND ans.result_id = ' . (int)$resultId . ' AND ans.deleted_at IS NULL',
                'left'
            )
            ->where('q.assessment_id', (int)$assessmentId)
            ->where('q.deleted_at', null)
            ->orderBy('q.order_number', 'ASC')
            ->get()->getResultArray();

        return view('counselor/assessments/result_detail', [
            'assessmentId' => $assessmentId,
            'resultId'     => $resultId,
            'result'       => $result,
            'questions'    => $questions,
        ]);
    }

    /**
     * GET /counselor/assessments/{id}/grading
     */
    public function grading($assessmentId)
    {
        return redirect()->to('/counselor/assessments/' . $assessmentId . '/results')
            ->with('success', 'Silakan pilih hasil "Completed" untuk dinilai.');
    }

    /**
     * POST /counselor/assessments/grade/submit
     * Reuse reviewSave() untuk meta review.
     */
    public function submitGrade(): RedirectResponse
    {
        $resultId = (int)($this->request->getPost('result_id') ?? 0);
        if ($resultId <= 0) {
            return redirect()->back()->with('error', 'Result ID tidak valid.');
        }
        return $this->reviewSave($resultId);
    }

    public function gradeAnswerAction(): RedirectResponse
    {
        $answerId     = (int)($this->request->getPost('answer_id') ?? 0);
        $assessmentId = (int)($this->request->getPost('assessment_id') ?? 0);
        $resultId     = (int)($this->request->getPost('result_id') ?? 0);
        if ($answerId <= 0 || $assessmentId <= 0 || $resultId <= 0) {
            return redirect()->back()->with('error', 'Data penilaian tidak lengkap.');
        }

        $score     = $this->request->getPost('score');
        $isCorrect = $this->request->getPost('is_correct');
        $feedback  = $this->request->getPost('feedback');

        $payload = [];
        if ($score !== '' && $score !== null) $payload['score'] = (float)$score;
        if ($feedback !== null) $payload['feedback'] = (string)$feedback;
        $payload['is_correct'] = ($isCorrect === '' || $isCorrect === null) ? null : (int)$isCorrect;

        $ctx = $this->authContext();
        $uid = (int)$ctx['id'];

        $svc = new AssessmentService();
        $res = $svc->gradeAnswer($answerId, $payload, $uid);

        $type = $res['success'] ? 'success' : 'error';
        return redirect()->to(site_url("counselor/assessments/{$assessmentId}/results/{$resultId}"))->with($type, $res['message']);
    }

    /**
     * POST /counselor/assessments/review/{resultId}
     * Versi baru: mendukung override nilai per-jawaban & tombol ungrade.
     */
    public function reviewSave($resultId): RedirectResponse
    {
        $resultId = (int)$resultId;
        if ($resultId <= 0) {
            return redirect()->back()->with('error', 'Result ID tidak valid.');
        }

        $action = (string)($this->request->getPost('action') ?? 'save');

        // 1) Tombol "Batalkan status dinilai"
        if ($action === 'ungrade') {
            $out = $this->svc->ungradeResult($resultId);
            return redirect()->back()->with($out['success'] ? 'success' : 'error', $out['message']);
        }

        // 2) Penilaian manual per jawaban
        $grades = $this->request->getPost('grades');
        if (is_array($grades) && !empty($grades)) {
            $ctx = $this->authContext();
            $userId = (int)$ctx['id'];

            foreach ($grades as $answerId => $g) {
                $answerId = (int)$answerId;
                if ($answerId <= 0 || !is_array($g)) {
                    continue;
                }

                $scoreRaw = $g['score'] ?? null;
                $score = ($scoreRaw === '' || $scoreRaw === null) ? null : (float)$scoreRaw;

                $flagRaw = $g['is_correct'] ?? '';
                $isCorrect = null;
                if ($flagRaw === '1' || $flagRaw === 1) $isCorrect = 1;
                elseif ($flagRaw === '0' || $flagRaw === 0) $isCorrect = 0;

                $payload = [
                    'score'      => $score,
                    'is_correct' => $isCorrect,
                    'feedback'   => trim((string)($g['feedback'] ?? '')),
                ];

                if (method_exists($this->svc, 'gradeAnswer')) {
                    $this->svc->gradeAnswer($answerId, $payload, $userId);
                }
            }
        }

        // 3) Meta review
        $review = [
            'interpretation'   => trim((string)$this->request->getPost('interpretation')),
            'recommendations'  => trim((string)$this->request->getPost('recommendations')),
            'counselor_notes'  => trim((string)$this->request->getPost('counselor_notes')),
            'dimension_scores' => $this->request->getPost('dimension_scores') ?? [],
        ];

        // 4) Finalisasi
        if (method_exists($this->svc, 'submitGrade')) {
            $out = $this->svc->submitGrade($resultId, $review);
        } else {
            if (method_exists($this->svc, 'reviewResult')) {
                $ctx = $this->authContext();
                $counselorId = (int)$ctx['id'];
                $this->svc->reviewResult($resultId, $counselorId, $review);
            }
            if (method_exists($this->svc, 'recalculateResult')) {
                $this->svc->recalculateResult($resultId);
            }
            if (method_exists($this->svc, 'markGraded')) {
                $this->svc->markGraded($resultId);
            }
            $out = ['success' => true, 'message' => 'Review & nilai tersimpan.'];
        }

        return redirect()->back()->with($out['success'] ? 'success' : 'error', $out['message'] ?? 'Perubahan disimpan.');
    }

    /**
     * POST /counselor/assessments/{assessmentId}/results/{resultId}/ungrade
     */
    public function ungradeResult(int $assessmentId, int $resultId): RedirectResponse
    {
        $svc = new AssessmentService();
        $out = $svc->ungradeResult($resultId);

        $msgType = $out['success'] ? 'success' : 'error';

        return redirect()
            ->to(site_url("counselor/assessments/{$assessmentId}/results"))
            ->with($msgType, $out['message']);
    }

    /**
     * POST /counselor/assessments/{assessmentId}/results/{resultId}/delete
     */
    public function deleteResult(int $assessmentId, int $resultId): RedirectResponse
    {
        $resultModel     = new AssessmentResultModel();
        $assessmentModel = new AssessmentModel();

        $result = $resultModel
            ->where('id', $resultId)
            ->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->first();

        if (!$result) {
            return redirect()
                ->back()
                ->with('error', 'Data hasil asesmen tidak ditemukan.');
        }

        $resultModel->delete($resultId);

        // Bersihkan relasi assignees kalau ada
        try {
            $db = Database::connect();
            $db->table('assessment_assignees')
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $result['student_id'])
                ->delete();
        } catch (\Throwable $e) {
            // abaikan
        }

        // Sinkron total_participants (opsional)
        try {
            $db = Database::connect();
            $count = $db->table('assessment_results')
                ->select('student_id')
                ->distinct()
                ->where('assessment_id', $assessmentId)
                ->where('deleted_at', null)
                ->countAllResults();

            $assessmentModel->update($assessmentId, [
                'total_participants' => $count,
            ]);
        } catch (\Throwable $e) {
            // abaikan
        }

        return redirect()
            ->to(site_url('counselor/assessments/' . $assessmentId . '/results'))
            ->with('success', 'Hasil asesmen siswa berhasil dihapus.');
    }
}
