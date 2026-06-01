<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AssessmentAnswerModel;
use App\Models\AssessmentModel;
use App\Models\AssessmentQuestionModel;
use App\Models\AssessmentResultModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Database\BaseConnection;

// Catatan: service opsional; controller akan fallback ke DB bila service tidak tersedia.
use App\Services\AssessmentService;

class AssessmentApiController extends BaseController
{
    /** Service opsional (nullable agar aman bila class tidak ada) */
    protected ?AssessmentService $svc = null;

    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        // Inisialisasi service bila tersedia
        if (class_exists(AssessmentService::class)) {
            $this->svc = new AssessmentService();
        }
        // Selalu siapkan koneksi DB untuk fallback
        $this->db = db_connect();
    }

    /**
     * GET /api/assessments/list
     * Query params:
     * - q           : string pencarian judul (contains)
     * - active      : 0|1 (dipakai bila service mendukung)
     * - published   : 0|1 (dipakai bila service mendukung)
     * - limit       : default 50, max 200
     *
     * Response: [{"id":1,"title":"..."}, ...]
     */
    public function list(): ResponseInterface
    {
        $q         = trim((string) $this->request->getGet('q'));
        $active    = $this->request->getGet('active');
        $published = $this->request->getGet('published');
        $limit     = (int) ($this->request->getGet('limit') ?? 50);
        $limit     = max(1, min($limit, 200));

        $rows = [];

        // 1) Coba gunakan service jika ada
        if ($this->svc && method_exists($this->svc, 'list')) {
            $filters = [];
            if ($active !== null && $active !== '') {
                $filters['active'] = (int) $active;
            }
            if ($published !== null && $published !== '') {
                $filters['published'] = (int) $published;
            }

            $rows = $this->svc->list($filters); // diasumsikan sudah soft-delete aware & aman
            // Pencarian ringan di PHP jika service belum sediakan 'q'
            if ($q !== '') {
                $rows = array_values(array_filter($rows, static function ($r) use ($q) {
                    return stripos((string) ($r['title'] ?? ''), $q) !== false;
                }));
            }
            // Batasi jumlah baris
            if (count($rows) > $limit) {
                $rows = array_slice($rows, 0, $limit);
            }
        } else {
            // 2) Fallback langsung ke DB (minimal fields, soft-delete aware)
            $b = $this->db->table('assessments')
                ->select('id, title')
                ->where('deleted_at', null);

            if ($q !== '') {
                $b->like('title', $q);
            }

            // Catatan: filter 'active'/'published' sengaja tidak dipakai di fallback
            // untuk menghindari error jika kolom tidak ada pada skema.

            $rows = $b->orderBy('title', 'ASC')
                      ->limit($limit)
                      ->get()
                      ->getResultArray();
        }

        // Bentuk payload minimal untuk dropdown
        $payload = array_map(static function ($r) {
            return [
                'id'    => (int) ($r['id'] ?? 0),
                'title' => (string) ($r['title'] ?? ''),
            ];
        }, $rows);

        return $this->response->setJSON($payload);
    }

    /**
     * GET /api/assessments/{id}
     */
    public function show(int $id): ResponseInterface
    {
        $assessment = (new AssessmentModel())
            ->select('id, title, description, assessment_type, evaluation_mode, target_audience, start_date, end_date, duration_minutes, max_attempts, instructions, show_score_to_student, show_result_immediately, allow_review')
            ->where('id', $id)
            ->where('is_active', 1)
            ->where('is_published', 1)
            ->where('deleted_at', null)
            ->first();

        if (!$assessment) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'not_found',
                'message' => 'Asesmen tidak ditemukan atau belum dipublikasikan.',
            ]);
        }

        $assessment['question_count'] = (int) $this->db->table('assessment_questions')
            ->where('assessment_id', $id)
            ->where('deleted_at', null)
            ->countAllResults();

        return $this->response->setJSON($assessment);
    }

    /**
     * GET /api/assessments/{id}/questions
     */
    public function getQuestions(int $id): ResponseInterface
    {
        if (!$this->publishedAssessmentExists($id)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'not_found',
                'message' => 'Asesmen tidak ditemukan atau belum dipublikasikan.',
            ]);
        }

        $rows = (new AssessmentQuestionModel())
            ->select('id, assessment_id, question_text, question_type, options, points, order_number, is_required, image_url, dimension')
            ->where('assessment_id', $id)
            ->where('deleted_at', null)
            ->orderBy('order_number', 'ASC')
            ->findAll();

        $payload = array_map(static function (array $row): array {
            $options = $row['options'] ?? null;
            if (is_string($options) && $options !== '') {
                $decoded = json_decode($options, true);
                $options = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }

            $row['options'] = is_array($options) ? array_values($options) : [];
            $row['id'] = (int) $row['id'];
            $row['assessment_id'] = (int) $row['assessment_id'];
            $row['points'] = (float) ($row['points'] ?? 0);
            $row['order_number'] = (int) ($row['order_number'] ?? 0);
            $row['is_required'] = (int) ($row['is_required'] ?? 0);
            return $row;
        }, $rows);

        return $this->response->setJSON($payload);
    }

    /**
     * GET /api/assessments/{id}/statistics
     */
    public function getStatistics(int $id): ResponseInterface
    {
        if (!$this->publishedAssessmentExists($id)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'not_found',
                'message' => 'Asesmen tidak ditemukan.',
            ]);
        }

        $model = new AssessmentResultModel();
        $stats = method_exists($model, 'getAssessmentStatistics')
            ? $model->getAssessmentStatistics($id)
            : [
                'total_attempts' => (int) $this->db->table('assessment_results')->where('assessment_id', $id)->where('deleted_at', null)->countAllResults(),
            ];

        return $this->response->setJSON($stats);
    }

    /**
     * GET /api/assessments/{id}/progress/{studentId}
     */
    public function getProgress(int $id, int $studentId): ResponseInterface
    {
        $currentStudentId = $this->currentStudentId();
        if ($currentStudentId > 0 && $studentId !== $currentStudentId) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'forbidden',
                'message' => 'Akses progres siswa lain ditolak.',
            ]);
        }

        if (!$this->publishedAssessmentExists($id)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'not_found',
                'message' => 'Asesmen tidak ditemukan.',
            ]);
        }

        $answerModel = new AssessmentAnswerModel();
        $progress = method_exists($answerModel, 'getStudentProgress')
            ? $answerModel->getStudentProgress($studentId, $id)
            : [];

        $totalQuestions = (int) $this->db->table('assessment_questions')
            ->where('assessment_id', $id)
            ->where('deleted_at', null)
            ->countAllResults();

        return $this->response->setJSON(array_merge([
            'assessment_id'   => $id,
            'student_id'      => $studentId,
            'total_questions' => $totalQuestions,
        ], $progress));
    }

    /**
     * POST /api/assessments/answer
     */
    public function saveAnswer(): ResponseInterface
    {
        $studentId = $this->currentStudentId();
        if ($studentId <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'forbidden',
                'message' => 'Akun belum terhubung ke data siswa.',
            ]);
        }

        $resultId = (int) ($this->request->getPost('result_id') ?? 0);
        $questionId = (int) ($this->request->getPost('question_id') ?? 0);
        if ($resultId <= 0 || $questionId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'invalid',
                'message' => 'result_id dan question_id wajib diisi.',
            ]);
        }

        $result = $this->db->table('assessment_results')
            ->where('id', $resultId)
            ->where('student_id', $studentId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$result || !in_array((string)($result['status'] ?? ''), ['Assigned', 'In Progress'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'forbidden',
                'message' => 'Attempt asesmen tidak tersedia untuk autosave.',
            ]);
        }

        $payload = $this->answerPayload($questionId, $studentId, $resultId);
        $answerModel = new AssessmentAnswerModel();
        $existing = $answerModel
            ->where('result_id', $resultId)
            ->where('question_id', $questionId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing) {
            $answerModel->update((int) $existing['id'], $payload);
            $answerId = (int) $existing['id'];
        } else {
            $answerId = (int) $answerModel->insert($payload, true);
        }

        $this->refreshResultProgress($resultId);

        return $this->response->setJSON([
            'status'    => 'ok',
            'answer_id' => $answerId,
        ]);
    }

    /**
     * POST /api/assessments/{id}/autosave
     */
    public function autosave(int $id): ResponseInterface
    {
        $studentId = $this->currentStudentId();
        if ($studentId <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'forbidden',
                'message' => 'Akun belum terhubung ke data siswa.',
            ]);
        }

        $resultId = (int) ($this->request->getPost('result_id') ?? 0);
        if ($resultId <= 0) {
            $row = $this->db->table('assessment_results')
                ->select('id')
                ->where('assessment_id', $id)
                ->where('student_id', $studentId)
                ->whereIn('status', ['Assigned', 'In Progress'])
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
            $resultId = (int) ($row['id'] ?? 0);
        }

        if ($resultId <= 0) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'not_found',
                'message' => 'Attempt asesmen aktif tidak ditemukan.',
            ]);
        }

        $answers = (array) ($this->request->getPost('answers') ?? []);
        $saved = 0;
        foreach ($answers as $questionId => $value) {
            $payload = $this->answerPayload((int) $questionId, $studentId, $resultId, $value);
            $model = new AssessmentAnswerModel();
            $existing = $model->where('result_id', $resultId)
                ->where('question_id', (int) $questionId)
                ->where('student_id', $studentId)
                ->first();

            if ($existing) {
                $model->update((int) $existing['id'], $payload);
            } else {
                $model->insert($payload);
            }
            $saved++;
        }

        $this->refreshResultProgress($resultId);

        return $this->response->setJSON([
            'status'    => 'ok',
            'result_id' => $resultId,
            'saved'     => $saved,
        ]);
    }

    private function publishedAssessmentExists(int $id): bool
    {
        return (new AssessmentModel())
            ->where('id', $id)
            ->where('is_active', 1)
            ->where('is_published', 1)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    private function currentStudentId(): int
    {
        $studentId = (int) (session('student_id') ?? 0);
        if ($studentId > 0) {
            return $studentId;
        }

        $userId = (int) (session('user_id') ?? 0);
        if ($userId <= 0) {
            return 0;
        }

        $row = $this->db->table('students')
            ->select('id')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $studentId = (int) ($row['id'] ?? 0);
        if ($studentId > 0) {
            session()->set('student_id', $studentId);
        }

        return $studentId;
    }

    private function answerPayload(int $questionId, int $studentId, int $resultId, $answer = null): array
    {
        if ($answer === null) {
            $answer = $this->request->getPost('answer')
                ?? $this->request->getPost('answer_text')
                ?? $this->request->getPost('answer_option')
                ?? $this->request->getPost('answer_options');
        }

        return [
            'question_id'     => $questionId,
            'student_id'      => $studentId,
            'result_id'       => $resultId,
            'answer_text'     => is_array($answer) ? null : (string) ($answer ?? ''),
            'answer_option'   => is_array($answer) ? null : (string) ($answer ?? ''),
            'answer_options'  => is_array($answer) ? array_values($answer) : null,
            'answered_at'     => date('Y-m-d H:i:s'),
            'ip_address'      => $this->request->getIPAddress(),
            'user_agent'      => substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ];
    }

    private function refreshResultProgress(int $resultId): void
    {
        $row = $this->db->table('assessment_results')
            ->select('assessment_id')
            ->where('id', $resultId)
            ->get()
            ->getRowArray();

        $assessmentId = (int) ($row['assessment_id'] ?? 0);
        if ($assessmentId <= 0) {
            return;
        }

        $answered = (int) $this->db->table('assessment_answers')
            ->where('result_id', $resultId)
            ->where('deleted_at', null)
            ->countAllResults();

        $total = (int) $this->db->table('assessment_questions')
            ->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->countAllResults();

        $this->db->table('assessment_results')
            ->where('id', $resultId)
            ->update([
                'status'             => 'In Progress',
                'questions_answered' => $answered,
                'total_questions'    => $total,
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
    }
}
