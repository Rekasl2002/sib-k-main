<?php

/**
 * File Path: app/Controllers/Koordinator/AssessmentController.php
 *
 * Koordinator BK • Assessment Controller
 * - Mengadopsi fitur Counselor AssessmentController (CRUD, Questions, Assign, Results, Grading)
 * - Koordinator tidak dibatasi scope counselor_id (bisa lintas kelas/guru)
 *
 * @package    SIB-K
 * @subpackage Controllers/Koordinator
 * @category   Assessment
 */

namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use App\Models\AssessmentModel;
use App\Models\AssessmentQuestionModel;
use App\Models\AssessmentResultModel;
use App\Models\ClassModel;
use App\Services\AssessmentService;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use CodeIgniter\Database\Exceptions\DatabaseException;


class AssessmentController extends BaseController
{
    protected AssessmentService $svc;

    public function __construct()
    {
        $this->svc = new AssessmentService();
        helper(['url', 'form', 'permission']);
    }

    private function uid(): int
    {
        if (function_exists('auth_user')) {
            $u = auth_user();
            return (int)($u['id'] ?? 0);
        }
        return (int)(session('user_id') ?? 0);
    }

    private function denyToDashboard(string $msg): RedirectResponse
    {
        return redirect()->to(base_url('koordinator/dashboard'))->with('error', $msg);
    }

    private function ensureView(): ?RedirectResponse
    {
        if (function_exists('has_permission') && !has_permission(['view_assessments', 'manage_assessments'])) {
            return $this->denyToDashboard('Anda tidak memiliki akses untuk melihat Asesmen.');
        }
        return null;
    }

    private function ensureManage(): ?RedirectResponse
    {
        if (function_exists('has_permission') && !has_permission('manage_assessments')) {
            return $this->denyToDashboard('Anda tidak memiliki akses untuk mengelola Asesmen.');
        }
        return null;
    }

    private function assessmentTypes(): array
    {
        return [
            'Psikologi'   => 'Psikologi',
            'Minat Bakat' => 'Minat Bakat',
            'Kecerdasan'  => 'Kecerdasan',
            'Motivasi'    => 'Motivasi',
            'Custom'      => 'Custom',
        ];
    }

    private function evaluationModes(): array
    {
        return [
            'pass_fail'  => 'Pass/Fail',
            'score_only' => 'Skor Saja',
            'survey'     => 'Survei (tanpa skor)',
        ];
    }

    private function gradeOptions(): array
    {
        return ['X' => 'X', 'XI' => 'XI', 'XII' => 'XII'];
    }

    private function getAllClasses(): array
    {
        $m = new ClassModel();
        // kolom di project kamu biasanya: id, class_name, grade_level
        return $m->select('id, class_name, grade_level')
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }

    /**
     * Builder siswa eligible sesuai target asesmen.
     * Koordinator: tidak dibatasi counselor_id.
     */
    protected function eligibleStudentQB(array $assessment): BaseBuilder
    {
        $db = \Config\Database::connect();
        $qb = $db->table('students s')
            ->select('s.id, s.full_name, COALESCE(s.nis, s.nisn) as nis, s.class_id, c.class_name, c.grade_level, c.major')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.status', 'Aktif');

        $target = (string)($assessment['target_audience'] ?? 'All');
        if ($target === 'Class' && !empty($assessment['target_class_id'])) {
            $qb->where('s.class_id', (int)$assessment['target_class_id']);
        } elseif ($target === 'Grade' && !empty($assessment['target_grade'])) {
            $qb->where('c.grade_level', (string)$assessment['target_grade']);
        }

        return $qb->orderBy('c.grade_level', 'ASC')
            ->orderBy('c.class_name', 'ASC')
            ->orderBy('s.full_name', 'ASC');
    }

    /**
     * Hitung peserta dinamis dari assessment_results (distinct student_id).
     */
    private function getParticipantCountsByAssessmentIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, fn($v) => $v > 0);
        if (empty($ids)) return [];

        $db = \Config\Database::connect();
        $rows = $db->table('assessment_results')
            ->select('assessment_id, COUNT(DISTINCT student_id) AS cnt')
            ->whereIn('assessment_id', $ids)
            ->where('deleted_at', null)
            ->groupBy('assessment_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['assessment_id']] = (int)$r['cnt'];
        }
        return $map;
    }

    private function findAssessmentWithClass(int $id): ?array
    {
        $db = \Config\Database::connect();
        $row = $db->table('assessments a')
            ->select('a.*, c.class_name AS target_class_name')
            ->join('classes c', 'c.id = a.target_class_id', 'left')
            ->where('a.id', $id)
            ->where('a.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * GET /koordinator/assessments
     */
    public function index()
    {
        if ($r = $this->ensureView()) return $r;

        $filters = [
            'assessment_type' => (string)($this->request->getGet('assessment_type') ?? ''),
            'is_published'    => (string)($this->request->getGet('is_published') ?? ''),
            'target_audience' => (string)($this->request->getGet('target_audience') ?? ''),
            'evaluation_mode' => (string)($this->request->getGet('evaluation_mode') ?? ''),
            'search'          => (string)($this->request->getGet('search') ?? ''),
        ];

        $assessment_types  = $this->assessmentTypes();
        $evaluation_modes  = $this->evaluationModes();

        $db = \Config\Database::connect();
        $b  = $db->table('assessments a')
            ->select('a.*, c.class_name AS target_class_name')
            ->join('classes c', 'c.id = a.target_class_id', 'left')
            ->where('a.deleted_at', null);

        if ($filters['assessment_type'] !== '') $b->where('a.assessment_type', $filters['assessment_type']);
        if ($filters['is_published'] !== '')    $b->where('a.is_published', (int)$filters['is_published']);
        if ($filters['target_audience'] !== '') $b->where('a.target_audience', $filters['target_audience']);

        // kompatibel filter lama: map evaluation_mode ke use_passing_score bila perlu
        if ($filters['evaluation_mode'] !== '') {
            if ($filters['evaluation_mode'] === 'pass_fail') $b->where('a.use_passing_score', 1);
            else $b->where('a.use_passing_score', 0);
        }

        if ($filters['search'] !== '') {
            $b->groupStart()
                ->like('a.title', $filters['search'])
                ->orLike('a.description', $filters['search'])
                ->groupEnd();
        }

        $assessments = $b->orderBy('a.id', 'DESC')->get()->getResultArray();

        // timpa total_participants dengan hitung dinamis
        $ids  = array_column($assessments, 'id');
        $pcMap = $this->getParticipantCountsByAssessmentIds($ids);
        foreach ($assessments as &$a) {
            $aid = (int)($a['id'] ?? 0);
            if ($aid > 0) $a['total_participants'] = (int)($pcMap[$aid] ?? (int)($a['total_participants'] ?? 0));
        }
        unset($a);

        // stats ringkas
        $stats = [
            'total_assessments' => (int)$db->table('assessments')->where('deleted_at', null)->countAllResults(),
            'published'         => (int)$db->table('assessments')->where('deleted_at', null)->where('is_published', 1)->countAllResults(),
            'active'            => (int)$db->table('assessments')->where('deleted_at', null)->where('is_active', 1)->countAllResults(),
        ];
        $stats['draft'] = max(0, $stats['total_assessments'] - $stats['published']);

        return view('koordinator/assessments/index', compact(
            'stats', 'assessment_types', 'evaluation_modes', 'filters', 'assessments'
        ));
    }

    /**
     * GET /koordinator/assessments/create
     */
    public function create()
    {
        if ($r = $this->ensureManage()) return $r;

        $assessment = [
            'is_active'               => 1,
            'is_published'            => 0,
            'show_result_immediately' => 1,
            'allow_review'            => 1,
            'max_attempts'            => 1,
            'evaluation_mode'         => 'pass_fail',
            'show_score_to_student'   => 1,
            'use_passing_score'       => 1,
        ];

        return view('koordinator/assessments/form', [
            'title'            => 'Buat Asesmen',
            'method'           => 'create',
            'assessment'       => $assessment,
            'classes'          => $this->getAllClasses(),
            'grades'           => $this->gradeOptions(),
            'evaluation_modes' => $this->evaluationModes(),
        ]);
    }

    /**
     * POST /koordinator/assessments/store
     */
    public function store(): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;
        $uid = $this->uid();
        if ($uid <= 0) return redirect()->to(base_url('login'))->with('error', 'Sesi login kadaluarsa. Silakan login ulang.');

        $mode = (string)($this->request->getPost('evaluation_mode') ?? 'pass_fail');
        if (!in_array($mode, ['pass_fail', 'score_only', 'survey'], true)) $mode = 'pass_fail';

        $rawDuration = $this->request->getPost('duration_minutes');
        $duration = null;
        if ($rawDuration !== '' && $rawDuration !== null) {
            $d = (int)$rawDuration;
            $duration = $d > 0 ? $d : null;
        }

        $rawRelease = (string)($this->request->getPost('result_release_at') ?? '');
        $resultRelease = null;
        if ($rawRelease !== '') {
            $ts = strtotime($rawRelease);
            if ($ts !== false) $resultRelease = date('Y-m-d H:i:s', $ts);
        }

        $data = [
            'title'                 => trim((string)$this->request->getPost('title')),
            'assessment_type'        => (string)$this->request->getPost('assessment_type'),
            'description'            => (string)$this->request->getPost('description'),
            'instructions'           => (string)$this->request->getPost('instructions'),
            'target_audience'        => (string)$this->request->getPost('target_audience'),
            'target_grade'           => $this->request->getPost('target_grade') ?: null,
            'target_class_id'        => $this->request->getPost('target_class_id') ?: null,
            'start_date'             => $this->request->getPost('start_date') ?: null,
            'end_date'               => $this->request->getPost('end_date') ?: null,
            'duration_minutes'       => $duration,
            'max_attempts'           => (int)($this->request->getPost('max_attempts') ?? 1),

            'evaluation_mode'        => $mode,
            'show_score_to_student'  => (int)($this->request->getPost('show_score_to_student') ? 1 : 0),
            'show_result_immediately'=> (int)($this->request->getPost('show_result_immediately') ? 1 : 0),
            'use_passing_score'      => (int)($this->request->getPost('use_passing_score') ? 1 : 0),
            'passing_score'          => ($this->request->getPost('passing_score') === '' ? null : (float)$this->request->getPost('passing_score')),
            'result_release_at'      => $resultRelease,

            'allow_review'           => (int)($this->request->getPost('allow_review') ? 1 : 0),
            'is_active'              => (int)($this->request->getPost('is_active') ? 1 : 0),
            'is_published'           => (int)($this->request->getPost('is_published') ? 1 : 0),

            'created_by'             => $uid,
        ];

        if ($data['title'] === '' || $data['assessment_type'] === '' || $data['target_audience'] === '') {
            return redirect()->back()->withInput()->with('error', 'Judul, Tipe Asesmen, dan Target Peserta wajib diisi.');
        }

        // (opsional) pakai service jika tersedia
        if (method_exists($this->svc, 'createAssessment')) {
            $res = $this->svc->createAssessment($data, $uid);
            if (is_array($res) && ($res['success'] ?? false)) {
                $id = (int)($res['data']['assessment_id'] ?? 0);
                return redirect()->to(base_url("koordinator/assessments/show/{$id}"))->with('success', $res['message'] ?? 'Asesmen berhasil dibuat.');
            }
        }

        $m = new AssessmentModel();
        $id = (int)$m->insert($data, true);

        return redirect()->to(base_url("koordinator/assessments/show/{$id}"))
            ->with('success', 'Asesmen berhasil dibuat.');
    }

    /**
     * GET /koordinator/assessments/show/{id}
     */
    public function show($id)
    {
        if ($r = $this->ensureView()) return $r;

        $assessment = $this->findAssessmentWithClass((int)$id);
        if (!$assessment) return redirect()->to(base_url('koordinator/assessments'))->with('error', 'Asesmen tidak ditemukan.');

        $questions = [];
        if (method_exists($this->svc, 'getAssessmentQuestions')) {
            $out = $this->svc->getAssessmentQuestions((int)$id);
            $questions = (array)($out['data']['questions'] ?? []);
        } else {
            $qm = new AssessmentQuestionModel();
            $questions = $qm->where('assessment_id', (int)$id)->where('deleted_at', null)->orderBy('order_number', 'ASC')->findAll();
        }

        $stats = [];
        if (method_exists($this->svc, 'getAssessmentStatistics')) {
            $s = $this->svc->getAssessmentStatistics((int)$id);
            $stats = (array)($s['data'] ?? []);
        }

        return view('koordinator/assessments/show', [
            'assessment' => $assessment,
            'questions'  => $questions,
            'stats'      => $stats,
        ]);
    }

    /**
     * GET /koordinator/assessments/edit/{id}
     */
    public function edit($id)
    {
        if ($r = $this->ensureManage()) return $r;

        $assessment = $this->findAssessmentWithClass((int)$id);
        if (!$assessment) return redirect()->to(base_url('koordinator/assessments'))->with('error', 'Asesmen tidak ditemukan.');

        return view('koordinator/assessments/form', [
            'title'            => 'Edit Asesmen',
            'method'           => 'edit',
            'assessment'       => $assessment,
            'classes'          => $this->getAllClasses(),
            'grades'           => $this->gradeOptions(),
            'evaluation_modes' => $this->evaluationModes(),
        ]);
    }

    /**
     * POST /koordinator/assessments/update/{id}
     */
    public function update($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $id = (int)$id;
        $existing = (new AssessmentModel())->find($id);
        if (!$existing) return redirect()->to(base_url('koordinator/assessments'))->with('error', 'Asesmen tidak ditemukan.');

        $mode = (string)($this->request->getPost('evaluation_mode') ?? ($existing['evaluation_mode'] ?? 'pass_fail'));
        if (!in_array($mode, ['pass_fail','score_only','survey'], true)) $mode = 'pass_fail';

        $rawDuration = $this->request->getPost('duration_minutes');
        $duration = null;
        if ($rawDuration !== '' && $rawDuration !== null) {
            $d = (int)$rawDuration;
            $duration = $d > 0 ? $d : null;
        }

        $rawRelease = (string)($this->request->getPost('result_release_at') ?? '');
        $resultRelease = null;
        if ($rawRelease !== '') {
            $ts = strtotime($rawRelease);
            if ($ts !== false) $resultRelease = date('Y-m-d H:i:s', $ts);
        }

        $data = [
            'title'                 => trim((string)$this->request->getPost('title')),
            'assessment_type'        => (string)$this->request->getPost('assessment_type'),
            'description'            => (string)$this->request->getPost('description'),
            'instructions'           => (string)$this->request->getPost('instructions'),
            'target_audience'        => (string)$this->request->getPost('target_audience'),
            'target_grade'           => $this->request->getPost('target_grade') ?: null,
            'target_class_id'        => $this->request->getPost('target_class_id') ?: null,
            'start_date'             => $this->request->getPost('start_date') ?: null,
            'end_date'               => $this->request->getPost('end_date') ?: null,
            'duration_minutes'       => $duration,
            'max_attempts'           => (int)($this->request->getPost('max_attempts') ?? 1),

            'evaluation_mode'        => $mode,
            'show_score_to_student'  => (int)($this->request->getPost('show_score_to_student') ? 1 : 0),
            'show_result_immediately'=> (int)($this->request->getPost('show_result_immediately') ? 1 : 0),
            'use_passing_score'      => (int)($this->request->getPost('use_passing_score') ? 1 : 0),
            'passing_score'          => ($this->request->getPost('passing_score') === '' ? null : (float)$this->request->getPost('passing_score')),
            'result_release_at'      => $resultRelease,

            'allow_review'           => (int)($this->request->getPost('allow_review') ? 1 : 0),
            'is_active'              => (int)($this->request->getPost('is_active') ? 1 : 0),
            'is_published'           => (int)($this->request->getPost('is_published') ? 1 : 0),
        ];

        if ($data['title'] === '' || $data['assessment_type'] === '' || $data['target_audience'] === '') {
            return redirect()->back()->withInput()->with('error', 'Judul, Tipe Asesmen, dan Target Peserta wajib diisi.');
        }

        if (method_exists($this->svc, 'updateAssessment')) {
            $ownerId = (int)($existing['created_by'] ?? 0);
            try {
                $this->svc->updateAssessment((int)$id, $data);
                return redirect()->to(base_url("koordinator/assessments/show/{$id}"))
                    ->with('success', 'Asesmen berhasil diperbarui.');
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

        (new AssessmentModel())->update($id, $data);
        return redirect()->to(base_url("koordinator/assessments/show/{$id}"))->with('success', 'Asesmen berhasil diperbarui.');
    }

    public function delete($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $id = (int)$id;
        $m = new AssessmentModel();
        if (!$m->find($id)) return redirect()->to(base_url('koordinator/assessments'))->with('error', 'Asesmen tidak ditemukan.');

        if (method_exists($this->svc, 'deleteAssessment')) {
            $res = $this->svc->deleteAssessment($id, $this->uid());
            if (is_array($res) && ($res['success'] ?? false)) {
                return redirect()->to(base_url('koordinator/assessments'))->with('success', $res['message'] ?? 'Asesmen dihapus.');
            }
        }

        $m->delete($id);
        return redirect()->to(base_url('koordinator/assessments'))->with('success', 'Asesmen dihapus.');
    }

    public function duplicate($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $id = (int)$id;
        if (!method_exists($this->svc, 'duplicateAssessment')) {
            return redirect()->back()->with('error', 'Fitur duplikasi tidak tersedia.');
        }

        $res = $this->svc->duplicateAssessment($id, $this->uid());
        if (!is_array($res) || !($res['success'] ?? false)) {
            return redirect()->back()->with('error', $res['message'] ?? 'Gagal menduplikasi asesmen.');
        }

        $newId = (int)($res['data']['new_assessment_id'] ?? 0);
        return redirect()->to(base_url("koordinator/assessments/show/{$newId}"))->with('success', $res['message'] ?? 'Asesmen berhasil diduplikasi.');
    }

    public function publish($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $id = (int)$id;
        try {
            if (method_exists($this->svc, 'publishAssessment')) {
                $res = $this->svc->publishAssessment((int)$id);
                return redirect()->back()->with(($res['success'] ?? false) ? 'success' : 'error', $res['message'] ?? 'OK');
            }

            // fallback service lama
            $this->svc->publish((int)$id, true);
            return redirect()->back()->with('success', 'Asesmen dipublikasikan.');
        } catch (\Throwable $e) {
            log_message('error', 'Publish error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mempublikasi asesmen.');
        }
    }

    public function unpublish($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $id = (int)$id;
        try {
            if (method_exists($this->svc, 'unpublishAssessment')) {
                $res = $this->svc->unpublishAssessment((int)$id);
                return redirect()->back()->with(($res['success'] ?? false) ? 'success' : 'error', $res['message'] ?? 'OK');
            }

            // fallback service lama
            $this->svc->publish((int)$id, false);
            return redirect()->back()->with('success', 'Publikasi asesmen dibatalkan.');
        } catch (\Throwable $e) {
            log_message('error', 'Unpublish error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membatalkan publikasi asesmen.');
        }
    }

    /**
     * GET /koordinator/assessments/{id}/questions
     */
    public function questions($id)
    {
        if ($r = $this->ensureManage()) return $r;

        $assessment = $this->findAssessmentWithClass((int)$id);
        if (!$assessment) return redirect()->to(base_url('koordinator/assessments'))->with('error', 'Asesmen tidak ditemukan.');

        $questions = [];
        if (method_exists($this->svc, 'getAssessmentQuestions')) {
            $out = $this->svc->getAssessmentQuestions((int)$id);
            $questions = (array)($out['data']['questions'] ?? []);
        } else {
            $qm = new AssessmentQuestionModel();
            $questions = $qm->where('assessment_id', (int)$id)->where('deleted_at', null)->orderBy('order_number', 'ASC')->findAll();
        }

        return view('koordinator/assessments/questions', [
            'assessment' => $assessment,
            'questions'  => $questions,
        ]);
    }

    /**
     * upload image untuk soal
     */
    private function saveImageUpload($file): ?string
    {
        if (!$file || !$file->isValid()) return null;

        $mime = strtolower((string)$file->getMimeType());
        $okMime = ['image/jpg','image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($mime, $okMime, true)) return null;

        $ext = strtolower($file->getExtension() ?: 'jpg');
        $allowedExt = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowedExt, true)) $ext = 'jpg';

        $targetDir = rtrim(FCPATH, "/\\") . '/uploads/assessment_questions';
        if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);

        $name = 'q_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $file->move($targetDir, $name, true);

        return 'uploads/assessment_questions/'.$name;
    }

    public function addQuestion($assessmentId): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $qData = $this->request->getPost([
            'question_text','question_type','points','is_required','explanation','dimension'
        ]);
        $options        = $this->request->getPost('options');
        $correctOption  = $this->request->getPost('correct_option');
        $correctOptions = $this->request->getPost('correct_options');

        $qData['is_required'] = (int)($this->request->getPost('is_required') ?? 0);
        $qData['points']      = ($qData['points'] === '' ? 1 : (float)$qData['points']);
        $qData['options']     = is_array($options) ? array_values(array_filter($options, fn($v)=>$v!=='')) : [];

        $isUngraded   = ((float)($qData['points'] ?? 0)) <= 0;
        $needsCorrect = in_array($qData['question_type'], ['Multiple Choice','True/False','Checkbox'], true);

        if ($needsCorrect && !$isUngraded) {
            if ($qData['question_type'] === 'Checkbox') {
                $marked = is_array($correctOptions) ? array_values(array_filter($correctOptions, fn($v)=>trim((string)$v)!=='')) : [];
                if (count($marked) === 0) return redirect()->back()->withInput()->with('error', 'Pilih minimal satu opsi sebagai jawaban benar.');
                if (!empty($qData['options'])) {
                    $optSet = array_map('strval', $qData['options']);
                    foreach ($marked as $mv) {
                        if (!in_array((string)$mv, $optSet, true)) return redirect()->back()->withInput()->with('error', 'Jawaban benar harus salah satu dari Options.');
                    }
                }
            } else {
                $opt = (string)($correctOption ?? '');
                if ($opt === '') return redirect()->back()->withInput()->with('error', 'Pilih satu opsi sebagai jawaban benar.');
                if (!empty($qData['options'])) {
                    $optSet = array_map('strval', $qData['options']);
                    if (!in_array($opt, $optSet, true)) return redirect()->back()->withInput()->with('error', 'Jawaban benar harus salah satu dari Options.');
                }
            }
        }

        $imgSrc = (string)$this->request->getPost('image_source'); // url|upload
        if ($imgSrc === 'upload') {
            $qData['image_url'] = $this->saveImageUpload($this->request->getFile('image_file'));
        } elseif ($imgSrc === 'url') {
            $url = trim((string)$this->request->getPost('image_url'));
            $qData['image_url'] = $url !== '' ? $url : null;
        } else {
            $qData['image_url'] = null;
        }

        switch ($qData['question_type']) {
            case 'Checkbox':
                $qData['correct_answer'] = is_array($correctOptions) && count($correctOptions)
                    ? json_encode(array_values($correctOptions))
                    : null;
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

    public function updateQuestion($assessmentId, $questionId): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $data = $this->request->getPost([
            'question_text','question_type','options','points','is_required','explanation','dimension'
        ]);

        $data['is_required'] = (int)($this->request->getPost('is_required') ?? 0);
        $data['points']      = ($data['points'] === '' || $data['points'] === null) ? 1 : (float)$data['points'];
        if (!is_array($data['options'])) $data['options'] = [];
        $data['options'] = array_values(array_filter($data['options'], fn($v)=>$v!==''));

        $isUngraded   = ((float)($data['points'] ?? 0)) <= 0;
        $correctOption  = $this->request->getPost('correct_option');
        $correctOptions = $this->request->getPost('correct_options');
        $needsCorrect   = in_array($data['question_type'], ['Multiple Choice','True/False','Checkbox'], true);

        if ($needsCorrect && !$isUngraded) {
            if ($data['question_type'] === 'Checkbox') {
                $marked = is_array($correctOptions) ? array_values(array_filter($correctOptions, fn($v)=>trim((string)$v)!=='')) : [];
                if (count($marked) === 0) return redirect()->back()->withInput()->with('error', 'Pilih minimal satu opsi sebagai jawaban benar.');
                if (!empty($data['options'])) {
                    $optSet = array_map('strval', $data['options']);
                    foreach ($marked as $mv) {
                        if (!in_array((string)$mv, $optSet, true)) return redirect()->back()->withInput()->with('error', 'Jawaban benar harus salah satu dari Options.');
                    }
                }
            } else {
                $opt = (string)($correctOption ?? '');
                if ($opt === '') return redirect()->back()->withInput()->with('error', 'Pilih satu opsi sebagai jawaban benar.');
                if (!empty($data['options'])) {
                    $optSet = array_map('strval', $data['options']);
                    if (!in_array($opt, $optSet, true)) return redirect()->back()->withInput()->with('error', 'Jawaban benar harus salah satu dari Options.');
                }
            }
        }

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

        switch ($data['question_type']) {
            case 'Checkbox':
                $data['correct_answer'] = is_array($correctOptions) && count($correctOptions)
                    ? json_encode(array_values($correctOptions))
                    : null;
                break;
            case 'Multiple Choice':
            case 'True/False':
            case 'Rating Scale':
                $data['correct_answer'] = ($correctOption !== null && $correctOption !== '') ? (string)$correctOption : null;
                break;
            default:
                $data['correct_answer'] = null;
        }

        $orderNo = (int)($this->request->getPost('order_no') ?? 0);
        if ($orderNo > 0 && method_exists($qModel, 'moveToOrder')) {
            if (!$qModel->moveToOrder((int)$questionId, $orderNo)) {
                return redirect()->back()->with('error', 'Gagal mengubah urutan pertanyaan.');
            }
        }

        $res = $this->svc->updateQuestion((int)$questionId, $data);
        return redirect()->back()->with(($res['success'] ?? false) ? 'success' : 'error', $res['message'] ?? 'Perubahan disimpan.');
    }

    public function deleteQuestion($assessmentId, $questionId): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $res = $this->svc->removeQuestion((int)$questionId);
        if (is_array($res)) return redirect()->back()->with(($res['success'] ?? false) ? 'success' : 'error', $res['message'] ?? 'OK');
        return redirect()->back()->with('success', 'Pertanyaan dihapus.');
    }

    /**
     * GET /koordinator/assessments/{id}/assign
     */
    public function assign($id)
    {
        if ($r = $this->ensureManage()) return $r;

        $m = new AssessmentModel();
        $assessment = $m->find((int)$id);
        if (!$assessment) return redirect()->to('/koordinator/assessments')->with('error', 'Asesmen tidak ditemukan.');

        $classes = (new ClassModel())->select('id, class_name')->orderBy('class_name', 'ASC')->findAll();

        $eligible = $this->eligibleStudentQB($assessment)->get()->getResultArray();

        $assignedMap = method_exists($this->svc, 'getAssignedMap') ? $this->svc->getAssignedMap((int)$id) : [];
        $eligible = array_values(array_filter($eligible, function ($s) use ($assignedMap) {
            $sid = (int)($s['id'] ?? 0);
            return $sid > 0 && !isset($assignedMap[$sid]);
        }));

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

        return view('koordinator/assessments/assign', [
            'assessment'        => $assessment,
            'classes'           => $classes,
            'students_by_class' => $students_by_class,
            'assignedMap'       => $assignedMap,
        ]);
    }

    /**
     * POST /koordinator/assessments/{id}/assign/process
     */
    public function processAssign($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $studentIds = $this->request->getPost('student_ids');
        if (!is_array($studentIds) || empty($studentIds)) return redirect()->back()->with('error', 'Pilih minimal satu siswa.');

        $m = new AssessmentModel();
        $assessment = $m->find((int)$id);
        if (!$assessment) return redirect()->to('/koordinator/assessments')->with('error', 'Asesmen tidak ditemukan.');

        $eligibleIds = $this->eligibleStudentQB($assessment)->select('s.id')->get()->getResultArray();
        $eligibleIds = array_map(fn($r) => (int)$r['id'], $eligibleIds);

        $postedIds = array_values(array_unique(array_map('intval', $studentIds)));
        $finalIds  = array_values(array_intersect($postedIds, $eligibleIds));
        $rejected  = array_values(array_diff($postedIds, $eligibleIds));

        if (empty($finalIds)) return redirect()->back()->with('error', 'Tidak ada siswa yang sesuai target asesmen.')->withInput();

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

        $resultModel   = new AssessmentResultModel();
        $created       = 0;
        $skippedInProg = 0;
        $skippedQuota  = 0;

        $maxAttempts = (int)($assessment['max_attempts'] ?? 0); // 0=unlimited

        foreach ($finalIds as $sid) {
            $sid = (int)$sid;

            if (method_exists($resultModel, 'getInProgressAttempt') && $resultModel->getInProgressAttempt((int)$id, $sid)) {
                $skippedInProg++;
                continue;
            }

            if ($maxAttempts > 0 && method_exists($resultModel, 'countUsedAttempts')) {
                $used = (int)$resultModel->countUsedAttempts((int)$id, $sid);
                if ($used >= $maxAttempts) {
                    $skippedQuota++;
                    continue;
                }
            }

            if (method_exists($resultModel, 'createAssignment')) {
                $rid = $resultModel->createAssignment((int)$id, $sid);
                if ($rid) $created++;
            }
        }

        $msg = "Asesmen ditugaskan ke {$created} siswa.";
        if ($removedCount > 0) $msg .= " {$removedCount} penugasan dicabut (replace mode).";
        if ($skippedInProg > 0) $msg .= " {$skippedInProg} siswa dilewati karena masih punya attempt berjalan.";
        if ($skippedQuota  > 0) $msg .= " {$skippedQuota} siswa melewati batas percobaan.";
        if (!empty($rejected))  $msg .= ' '.count($rejected).' siswa diabaikan karena tidak sesuai target.';

        return redirect()->to("/koordinator/assessments/{$id}/results")->with('success', $msg);
    }

    public function revokeAssign($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $studentIds = $this->request->getPost('student_ids');
        if (!is_array($studentIds) || empty($studentIds)) return redirect()->back()->with('error', 'Pilih minimal satu siswa untuk dicabut.');

        if (!method_exists($this->svc, 'revokeAssignments')) return redirect()->back()->with('error', 'Fitur revoke tidak tersedia.');

        $res = $this->svc->revokeAssignments((int)$id, array_map('intval', $studentIds));
        $msg = ($res['success'] ?? false)
            ? 'Penugasan dicabut: '.(int)($res['data']['assigned_removed'] ?? 0).' baris Assigned dihapus.'
            : 'Gagal mencabut penugasan: '.($res['message'] ?? 'Unknown');

        return redirect()->back()->with(($res['success'] ?? false) ? 'success' : 'error', $msg);
    }

    public function syncAssignments($id): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        if (!method_exists($this->svc, 'syncAssignmentsToResults')) return redirect()->back()->with('error', 'Fitur sinkronisasi tidak tersedia.');

        $res = $this->svc->syncAssignmentsToResults((int)$id);
        $msg = ($res['success'] ?? false)
            ? 'Sinkronisasi selesai. Dibuat: '.(int)($res['data']['created_assigned'] ?? 0).' Assigned.'
            : 'Gagal sinkronisasi: '.($res['message'] ?? 'Unknown');

        return redirect()->back()->with(($res['success'] ?? false) ? 'success' : 'error', $msg);
    }

    /**
     * GET /koordinator/assessments/{id}/results
     */
    public function results($id)
    {
        if ($r = $this->ensureView()) return $r;

        $m = new AssessmentModel();
        $assessment = $m->find((int)$id);
        if (!$assessment) return redirect()->to('/koordinator/assessments')->with('error', 'Asesmen tidak ditemukan.');

        $filters = [
            'status'     => (string)($this->request->getGet('status') ?? ''),
            'class_id'   => (string)($this->request->getGet('class_id') ?? ''),
            'is_passed'  => (string)($this->request->getGet('is_passed') ?? ''),
            'search'     => (string)($this->request->getGet('search') ?? ''),
        ];

        $resultModel = new AssessmentResultModel();
        $results     = method_exists($resultModel, 'getByAssessment')
            ? $resultModel->getByAssessment((int)$id, $filters)
            : [];

        $statistics = method_exists($resultModel, 'getAssessmentStatistics')
            ? $resultModel->getAssessmentStatistics((int)$id)
            : [];

        $pcMap = $this->getParticipantCountsByAssessmentIds([(int)$id]);
        $statistics['total_participants'] = (int)($pcMap[(int)$id] ?? 0);

        $classes = (new ClassModel())->select('id, class_name')->orderBy('class_name', 'ASC')->findAll();

        return view('koordinator/assessments/results', [
            'assessment' => $assessment,
            'results'    => $results,
            'statistics' => $statistics,
            'classes'    => $classes,
            'filters'    => $filters,
        ]);
    }

    /**
     * GET /koordinator/assessments/{assessmentId}/results/{resultId}
     */
    public function resultDetail($assessmentId, $resultId)
    {
        if ($r = $this->ensureView()) return $r;

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
                s.full_name AS student_name,
                s.nisn,
                s.nis,
                c.class_name
            ")
            ->join('assessments a', 'a.id = r.assessment_id')
            ->join('students s', 's.id = r.student_id')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('r.id', (int)$resultId)
            ->where('r.assessment_id', (int)$assessmentId)
            ->get()
            ->getRowArray();

        if (!$result) {
            return redirect()->to(site_url("koordinator/assessments/{$assessmentId}/results"))
                ->with('error', 'Hasil tidak ditemukan.');
        }

        $questions = $db->table('assessment_questions q')
            ->select('
                q.*,
                ans.id AS answer_id, ans.answer_text, ans.answer_option, ans.answer_options,
                ans.score AS answer_score, ans.is_correct, ans.is_auto_graded, ans.feedback,
                ans.answered_at, ans.time_spent_seconds
            ')
            ->join('assessment_answers ans', 'ans.question_id = q.id AND ans.result_id = ' . (int)$resultId, 'left')
            ->where('q.assessment_id', (int)$assessmentId)
            ->orderBy('q.order_number', 'ASC')
            ->get()
            ->getResultArray();

        return view('koordinator/assessments/result_detail', [
            'assessmentId' => (int)$assessmentId,
            'resultId'     => (int)$resultId,
            'result'       => $result,
            'questions'    => $questions,
        ]);
    }

    public function submitGrade(): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $resultId = (int)($this->request->getPost('result_id') ?? 0);
        if ($resultId <= 0) return redirect()->back()->with('error', 'Result ID tidak valid.');
        return $this->reviewSave($resultId);
    }

    public function gradeAnswerAction(): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

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

        $uid = $this->uid();
        $res = $this->svc->gradeAnswer($answerId, $payload, $uid);

        $type = ($res['success'] ?? false) ? 'success' : 'error';
        return redirect()->to(site_url("koordinator/assessments/{$assessmentId}/results/{$resultId}"))->with($type, $res['message'] ?? 'OK');
    }

    public function reviewSave($resultId): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $resultId = (int)$resultId;
        if ($resultId <= 0) return redirect()->back()->with('error', 'Result ID tidak valid.');

        $action = (string)($this->request->getPost('action') ?? 'save');
        if ($action === 'ungrade') {
            $out = $this->svc->ungradeResult($resultId);
            return redirect()->back()->with(($out['success'] ?? false) ? 'success' : 'error', $out['message'] ?? 'OK');
        }

        $grades = $this->request->getPost('grades');
        if (is_array($grades) && !empty($grades) && method_exists($this->svc, 'gradeAnswer')) {
            $userId = $this->uid();
            foreach ($grades as $answerId => $g) {
                $answerId = (int)$answerId;
                if ($answerId <= 0 || !is_array($g)) continue;

                $scoreRaw = $g['score'] ?? null;
                $score    = ($scoreRaw === '' || $scoreRaw === null) ? null : (float)$scoreRaw;

                $flagRaw   = $g['is_correct'] ?? '';
                $isCorrect = null;
                if ($flagRaw === '1' || $flagRaw === 1) $isCorrect = 1;
                elseif ($flagRaw === '0' || $flagRaw === 0) $isCorrect = 0;

                $payload = [
                    'score'      => $score,
                    'is_correct' => $isCorrect,
                    'feedback'   => trim((string)($g['feedback'] ?? '')),
                ];

                $this->svc->gradeAnswer($answerId, $payload, $userId);
            }
        }

        $review = [
            'interpretation'   => trim((string)$this->request->getPost('interpretation')),
            'recommendations'  => trim((string)$this->request->getPost('recommendations')),
            'counselor_notes'  => trim((string)$this->request->getPost('counselor_notes')),
            'dimension_scores' => $this->request->getPost('dimension_scores') ?? [],
        ];

        if (method_exists($this->svc, 'submitGrade')) {
            $out = $this->svc->submitGrade($resultId, $review);
        } else {
            $out = ['success' => true, 'message' => 'Review tersimpan.'];
        }

        return redirect()->back()->with(($out['success'] ?? false) ? 'success' : 'error', $out['message'] ?? 'OK');
    }

    public function ungradeResult(int $assessmentId, int $resultId): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $out = $this->svc->ungradeResult($resultId);
        $msgType = ($out['success'] ?? false) ? 'success' : 'error';

        return redirect()->to(site_url("koordinator/assessments/{$assessmentId}/results"))
            ->with($msgType, $out['message'] ?? 'OK');
    }

    public function deleteResult(int $assessmentId, int $resultId): RedirectResponse
    {
        if ($r = $this->ensureManage()) return $r;

        $resultModel     = new AssessmentResultModel();
        $assessmentModel = new AssessmentModel();

        $result = $resultModel
            ->where('id', $resultId)
            ->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->first();

        if (!$result) {
            return redirect()->back()->with('error', 'Data hasil asesmen tidak ditemukan.');
        }

        $resultModel->delete($resultId);

        try {
            $db = Database::connect();
            $db->table('assessment_assignees')
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $result['student_id'])
                ->delete();
        } catch (\Throwable $e) {}

        try {
            $db = Database::connect();
            $count = $db->table('assessment_results')
                ->distinct()
                ->select('student_id')
                ->where('assessment_id', $assessmentId)
                ->where('deleted_at', null)
                ->countAllResults();

            $assessmentModel->update($assessmentId, ['total_participants' => $count]);
        } catch (\Throwable $e) {}

        return redirect()->to(site_url("koordinator/assessments/{$assessmentId}/results"))
            ->with('success', 'Hasil asesmen siswa berhasil dihapus.');
    }

    /**
     * GET /koordinator/assessments/{id}/results/export
     * Export CSV sederhana (aman, tanpa library tambahan).
     */
    public function exportResults($id): ResponseInterface
    {
        if ($r = $this->ensureView()) return $this->response->redirect(base_url('koordinator/dashboard'));

        $id = (int)$id;
        $assessment = (new AssessmentModel())->find($id);
        if (!$assessment) return redirect()->to(base_url('koordinator/assessments'))->with('error', '...');


        $filters = [
            'status'     => (string)($this->request->getGet('status') ?? ''),
            'class_id'   => (string)($this->request->getGet('class_id') ?? ''),
            'is_passed'  => (string)($this->request->getGet('is_passed') ?? ''),
            'search'     => (string)($this->request->getGet('search') ?? ''),
        ];

        $resultModel = new AssessmentResultModel();
        $rows = method_exists($resultModel, 'getByAssessment')
            ? (array)$resultModel->getByAssessment($id, $filters)
            : [];

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, ['Student', 'NIS/NISN', 'Class', 'Status', 'Score', 'Passed', 'Submitted At', 'Graded At']);

        foreach ($rows as $r) {
            $student = $r['student_name'] ?? $r['full_name'] ?? '';
            $nis     = $r['nisn'] ?? $r['nis'] ?? '';
            $class   = $r['class_name'] ?? '';
            $status  = $r['status'] ?? '';
            $score   = $r['score'] ?? $r['total_score'] ?? '';
            $passed  = isset($r['is_passed']) ? ((int)$r['is_passed'] ? 'Yes' : 'No') : '';
            $subAt   = $r['submitted_at'] ?? $r['completed_at'] ?? '';
            $grAt    = $r['graded_at'] ?? '';

            fputcsv($fp, [$student, $nis, $class, $status, $score, $passed, $subAt, $grAt]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', (string)($assessment['title'] ?? 'assessment'));
        $filename = "results_{$id}_{$safeTitle}.csv";

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->setBody($csv);
    }
}
