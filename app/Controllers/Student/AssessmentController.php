<?php
/**
 * File Path: app/Controllers/Student/AssessmentController.php
 * Student • Assessments
 * Menangani: daftar tersedia, mulai/lanjut, pengerjaan, submit, riwayat, review.
 */

namespace App\Controllers\Student;

use CodeIgniter\I18n\Time;
use App\Models\AssessmentResultModel;

class AssessmentController extends BaseStudentController
{
    /**
     * Daftar asesmen yang tersedia untuk siswa (All/Grade/Class/Individual) dalam rentang tanggal aktif.
     * Menyediakan flag UI:
     * - ui_resume: true jika ada attempt In Progress (hanya tampil "Lanjutkan")
     * - ui_start : true jika boleh mulai attempt baru (hanya tampil "Kerjakan")
     * - resume_result_id: id result in-progress (untuk route resume)
     * - latest_result: ringkasan result terakhir siswa untuk asesmen tsb (status/spent/attempt/started_at)
     */
    public function available()
    {
        $this->requireStudent();

        // Profil ringkas siswa (kelas & grade)
        $student = $this->db->table('students s')
            ->select('s.id, s.class_id, c.grade_level')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.id', $this->studentId)
            ->where('s.deleted_at', null)
            ->get()->getRowArray();

        if (!$student) {
            return redirect()->to('/student/profile')->with('warning', 'Profil siswa belum lengkap.');
        }

        // Ambil asesmen via service bila ada, jika tidak gunakan fallback internal.
        $assessments = [];
        if (\class_exists('\App\Services\StudentService')) {
            try {
                $svc = new \App\Services\StudentService();
                $assessments = $svc->getAvailableAssessments($student, 50) ?? [];
            } catch (\Throwable $e) {
                log_message('warning', '[Student/AssessmentController] StudentService fallback. '.$e->getMessage());
                $assessments = $this->getAvailableAssessmentsFallback($student, 50);
            }
        } else {
            $assessments = $this->getAvailableAssessmentsFallback($student, 50);
        }

        // Pastikan berbentuk object untuk kompatibilitas view
        $assessments = $this->ensureObjects($assessments);

        // Whitelist: buang asesmen yang soft-deleted / non-aktif / non-published / di luar jendela tanggal
        $today       = Time::today($this->tz)->toDateString();
        $assessments = $this->whitelistAssessments($assessments, $today);

        // Kumpulkan ID asesmen utk agregasi attempt
        $ids = array_map(function ($x) {
            return (int) (is_array($x) ? ($x['id'] ?? 0) : ($x->id ?? 0));
        }, $assessments);

        $attemptsByAssessment     = [];
        $inProgressByAssessment   = []; // assessment_id => last in-progress result_id
        $latestResultByAssessment = []; // assessment_id => array ringkas result terakhir

        if (!empty($ids)) {
            // Hitung attempt YANG MENGURAS KUOTA: Completed/Graded/Expired/Abandoned (non-deleted)
            $rowsUsed = $this->db->table('assessment_results')
                ->select('assessment_id, COUNT(id) as cnt')
                ->where('student_id', $this->studentId)
                ->whereIn('assessment_id', $ids)
                ->whereIn('status', ['Completed','Graded','Expired','Abandoned'])
                ->where('deleted_at', null)
                ->groupBy('assessment_id')
                ->get()->getResultArray();

            foreach ($rowsUsed as $r) {
                $attemptsByAssessment[(int)$r['assessment_id']] = (int)$r['cnt'];
            }

            // Deteksi adanya attempt In Progress per asesmen (untuk tombol "Lanjutkan")
            $rowsIP = $this->db->table('assessment_results')
                ->select('assessment_id, MAX(id) as last_id')
                ->where('student_id', $this->studentId)
                ->whereIn('assessment_id', $ids)
                ->where('status', 'In Progress')
                ->where('deleted_at', null)
                ->groupBy('assessment_id')
                ->get()->getResultArray();

            foreach ($rowsIP as $r) {
                $inProgressByAssessment[(int)$r['assessment_id']] = (int)$r['last_id'];
            }

            // Ambil result terakhir per asesmen (termasuk status Assigned), non-deleted
            $rowsLatestIds = $this->db->table('assessment_results')
                ->select('assessment_id, MAX(id) as last_id')
                ->where('student_id', $this->studentId)
                ->whereIn('assessment_id', $ids)
                ->where('deleted_at', null)
                ->groupBy('assessment_id')
                ->get()->getResultArray();

            $lastIds = array_values(array_filter(array_map(fn($x) => (int)$x['last_id'], $rowsLatestIds)));
            if (!empty($lastIds)) {
                $rowsLatest = $this->db->table('assessment_results')
                    ->select('id, assessment_id, status, time_spent_seconds, attempt_number, is_passed, started_at, questions_answered')
                    ->whereIn('id', $lastIds)
                    ->where('deleted_at', null)
                    ->get()->getResultArray();
                foreach ($rowsLatest as $r) {
                    $latestResultByAssessment[(int)$r['assessment_id']] = $r;
                }
            }
        }

        // Lengkapi setiap baris dengan flag UI (mutually exclusive) dan latest_result
        foreach ($assessments as &$a) {
            $aid         = (int)($a->id ?? 0);
            $maxAttempts = (int)($a->max_attempts ?? 0); // 0 = unlimited
            $used        = (int)($attemptsByAssessment[$aid] ?? 0);
            $ipResultId  = (int)($inProgressByAssessment[$aid] ?? 0);

            $canStart    = ($maxAttempts === 0 || $used < $maxAttempts);

            // resume menang jika ada In Progress
            $a->ui_resume        = $ipResultId > 0;
            $a->resume_result_id = $ipResultId;
            // start hanya jika tidak resume dan masih ada kuota
            $a->ui_start         = !$a->ui_resume && $canStart;

            if (isset($latestResultByAssessment[$aid])) {
                $a->latest_result = $latestResultByAssessment[$aid];
            }
        }
        unset($a);

        return view('student/assessments/available', [
            'assessments'             => $assessments,
            'attemptsByAssessment'    => $attemptsByAssessment,    // kompatibilitas view lama
            'inProgressByAssessment'  => array_map(fn($v)=>true, $inProgressByAssessment), // bool map kompatibel
            'today'                   => $today,
        ]);
    }

    /**
     * Mulai attempt baru (timer reset ke full duration).
     * Prioritas:
     *  1) Jika ada attempt In Progress: tutup sebagai Abandoned (agar tidak dobel) lalu lanjut
     *  2) Jika ada attempt Assigned: promosikan menjadi In Progress dan set started_at sekarang
     *  3) Jika tidak ada keduanya: buat attempt baru In Progress
     * Route: GET/POST /student/assessments/start/{assessmentId}
     */
    public function start(int $assessmentId)
    {
        $this->requireStudent();

        $assessment = $this->db->table('assessments')
            ->where('id', $assessmentId)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if (
            !$assessment ||
            (int)$assessment['is_active'] !== 1 ||
            (int)$assessment['is_published'] !== 1
        ) {
            return $this->failBack('Asesmen tidak tersedia.');
        }

        // Cek jendela tanggal & eligibility
        $today = Time::today($this->tz)->toDateString();
        if (!$this->isWindowOpen($assessment, $today)) {
            return $this->failBack('Asesmen belum atau tidak lagi tersedia.');
        }

        $student = $this->db->table('students s')
            ->select('s.id, s.class_id, c.grade_level')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.id', $this->studentId)
            ->where('s.deleted_at', null)
            ->get()->getRowArray();

        if (!$this->isEligible($assessment, $student)) {
            return $this->failBack('Anda tidak memenuhi syarat untuk mengikuti asesmen ini.');
        }

        $now   = Time::now($this->tz)->toDateTimeString();
        $model = new AssessmentResultModel();

        $this->db->transStart();

        // 1) Tutup attempt in-progress (jika ada) sebagai Abandoned
        $existing = $this->db->table('assessment_results')
            ->select('id')
            ->where([
                'assessment_id' => $assessmentId,
                'student_id'    => $this->studentId,
                'status'        => 'In Progress',
            ])
            ->where('deleted_at', null)
            ->orderBy('id','DESC')
            ->get()->getRowArray();

        if ($existing) {
            $this->db->table('assessment_results')->where('id', (int)$existing['id'])->update([
                'status'       => 'Abandoned',
                'completed_at' => $now,
                'updated_at'   => $now,
            ]);
            // Hapus jawaban attempt berjalan (karena abandon)
            $this->db->table('assessment_answers')->where('result_id', (int)$existing['id'])->delete();
        }

        // 2) Ada Assigned? Naikkan jadi In Progress (hanya jika belum melewati kuota)
        $maxAttempts = (int)($assessment['max_attempts'] ?? 0); // 0 = unlimited
        $attemptUsed = $this->db->table('assessment_results')
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $this->studentId)
            ->whereIn('status', ['Completed','Graded','Expired','Abandoned'])
            ->where('deleted_at', null)
            ->countAllResults();

        $assigned = $this->db->table('assessment_results')
            ->select('id, attempt_number')
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $this->studentId)
            ->where('status', 'Assigned')
            ->where('deleted_at', null)
            ->orderBy('id','DESC')
            ->get()->getRowArray();

        if ($assigned) {
            if ($maxAttempts > 0 && $attemptUsed >= $maxAttempts) {
                $this->db->transRollback();
                return $this->failBack('Batas jumlah percobaan sudah tercapai.');
            }

            $rid = (int)$assigned['id'];
            // Update via Model agar beforeUpdate (fillClientMeta) jalan.
            $model->update($rid, [
                'status'             => 'In Progress',
                'started_at'         => $now,
                'updated_at'         => $now,
                'time_spent_seconds' => 0,
            ]);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                return $this->failBack('Gagal memulai percobaan (Assigned).');
            }
            $url = route_to('student.assessments.take', $assessmentId) . '?rid=' . $rid;
            return $this->successGo($url, 'Percobaan dimulai.');
        }

        // 3) Tidak ada Assigned: cek batas attempt (0/null = unlimited). Hitung status yang menguras kuota.
        if ($maxAttempts > 0 && $attemptUsed >= $maxAttempts) {
            $this->db->transRollback();
            return $this->failBack('Melebihi batas jumlah percobaan.');
        }

        // 4) Buat attempt baru In Progress via Model (biarkan attempt_number diisi callback)
        $rid = $model->insert([
            'assessment_id'      => $assessmentId,
            'student_id'         => $this->studentId,
            'status'             => 'In Progress',
            'started_at'         => $now,
            'created_at'         => $now,
            'updated_at'         => $now,
            'time_spent_seconds' => 0,
        ], true);

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return $this->failBack('Gagal memulai percobaan.');
        }

        $url = route_to('student.assessments.take', $assessmentId) . '?rid=' . (int)$rid;
        return $this->successGo($url, 'Percobaan dimulai.');
    }

    /**
     * Lanjutkan attempt berjalan (In Progress).
     * Route: GET /student/assessments/resume/{assessmentId}
     */
    public function resume(int $assessmentId)
    {
        $this->requireStudent();

        $inprog = $this->db->table('assessment_results')
            ->select('id')
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $this->studentId)
            ->where('status', 'In Progress')
            ->where('deleted_at', null)
            ->orderBy('id','DESC')
            ->get()->getRowArray();

        if ($inprog) {
            $url = route_to('student.assessments.take', $assessmentId) . '?rid=' . (int)$inprog['id'];
            return $this->successGo($url, 'Lanjutkan pengerjaan.');
        }

        // Jika tak ada in-progress, mulai attempt baru (fallback aman).
        return redirect()->to(route_to('student.assessments.start', $assessmentId))
            ->with('info', 'Memulai percobaan baru.');
    }

    /**
     * Halaman pengerjaan asesmen.
     * Opsional ?rid={result_id} untuk melanjutkan attempt.
     */
    public function take(int $assessmentId)
    {
        $this->requireStudent();

        // Ambil semua kolom asesmen termasuk evaluation flags
        $assessment = $this->db->table('assessments')
            ->where('id', $assessmentId)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if (
            !$assessment ||
            (int)$assessment['is_active'] !== 1 ||
            (int)$assessment['is_published'] !== 1
        ) {
            return redirect()->to(route_to('student.assessments.available'))->with('error', 'Asesmen tidak tersedia.');
        }

        $today = Time::today($this->tz)->toDateString();
        if (!$this->isWindowOpen($assessment, $today)) {
            return redirect()->to(route_to('student.assessments.available'))->with('error', 'Asesmen di luar rentang tanggal.');
        }

        // Ambil/validasi result attempt
        $rid = (int) ($this->request->getGet('rid') ?? 0);
        $result = null;

        if ($rid > 0) {
            $result = $this->db->table('assessment_results')
                ->where('id', $rid)
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $this->studentId)
                ->where('status', 'In Progress')
                ->where('deleted_at', null)
                ->get()->getRowArray();
        }

        // Jika tidak ada RID valid, cari attempt In Progress terbaru
        if (!$result) {
            $existing = $this->db->table('assessment_results')
                ->select('*')
                ->where([
                    'assessment_id' => $assessmentId,
                    'student_id'    => $this->studentId,
                    'status'        => 'In Progress',
                ])
                ->where('deleted_at', null)
                ->orderBy('id','DESC')
                ->get()->getRowArray();

            if ($existing) {
                $result = $existing;
                $rid = (int)$existing['id'];
            } else {
                // Jangan membuat attempt di sini. Arahkan ke /start agar semua validasi terpusat.
                return redirect()->to(route_to('student.assessments.start', $assessmentId));
            }
        }

        // Pertanyaan
        $questions = $this->db->table('assessment_questions')
            ->where('assessment_id', $assessmentId)
            ->where('deleted_at', null)
            ->orderBy('order_number', 'ASC')
            ->get()->getResultArray();

        // Hitung sisa waktu dari server (agar tidak reset saat keluar/masuk)
        $remainingSeconds = null;
        if (!empty($assessment['duration_minutes'])) {
            $durationSec = (int)$assessment['duration_minutes'] * 60;
            $startedAt   = $result['started_at'] ?? null;
            $elapsedFromStart = AssessmentResultModel::calcElapsedFromStartedAt($startedAt);
            $spentRecorded    = (int)($result['time_spent_seconds'] ?? 0);
            $elapsed          = max($elapsedFromStart, $spentRecorded);
            $remainingSeconds = max(0, $durationSec - $elapsed);
        }

        return view('student/assessments/take', [
            'assessment'       => (object)$assessment,
            'questions'        => $questions,
            'resultId'         => $rid, // penting: dipost kembali saat submit
            'remainingSeconds' => $remainingSeconds, // dipakai view utk countdown (opsional)
        ]);
    }

    /**
     * Submit jawaban dan lakukan auto-grading untuk tipe objektif.
     * Route: POST /student/assessments/submit/{assessmentId}
     */
    public function submit(int $assessmentId)
    {
        $this->requireStudent();

        // Ambil asesmen
        $assessment = $this->db->table('assessments')
            ->where('id', $assessmentId)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if (!$assessment) {
            return redirect()->back()->with('error', 'Asesmen tidak ditemukan.');
        }

        $today = Time::today($this->tz)->toDateString();
        if (!$this->isWindowOpen($assessment, $today)) {
            return redirect()->to(route_to('student.assessments.available'))->with('error', 'Asesmen di luar rentang tanggal.');
        }

        // Derivasi mode bila kolom evaluation_mode tidak tersedia
        $modeRaw = $assessment['evaluation_mode'] ?? null;
        if ($modeRaw === null) {
            $sumRow = $this->db->table('assessment_questions')
                ->selectSum('points', 'sum_points')
                ->where('assessment_id', $assessmentId)
                ->where('deleted_at', null)
                ->get()->getRowArray();
            $sumPoints = (float) ($sumRow['sum_points'] ?? 0);
            if ($sumPoints <= 0) {
                $mode = 'survey';
            } else {
                $mode = ((int)($assessment['use_passing_score'] ?? 0) === 1) ? 'pass_fail' : 'score_only';
            }
        } else {
            $mode = (string)$modeRaw;
        }

        $usePassing   = (int)($assessment['use_passing_score'] ?? ($mode === 'pass_fail' ? 1 : 0)) === 1;
        $disableScore = ($mode === 'survey'); // pada survey tidak menghitung skor sama sekali

        // Ambil result attempt yang valid (harus In Progress)
        $resultId = (int) ($this->request->getPost('result_id') ?? 0);
        $now      = Time::now($this->tz)->toDateTimeString();

        $this->db->transStart();

        try {
            $result = null;
            if ($resultId > 0) {
                $result = $this->db->table('assessment_results')
                    ->where('id', $resultId)
                    ->where('assessment_id', $assessmentId)
                    ->where('student_id', $this->studentId)
                    ->where('status', 'In Progress')
                    ->where('deleted_at', null)
                    ->get()->getRowArray();
            }

            // Jika tidak ada result in-progress yang valid, coba temukan yang terbaru.
            if (!$result) {
                $result = $this->db->table('assessment_results')
                    ->where('assessment_id', $assessmentId)
                    ->where('student_id', $this->studentId)
                    ->where('status', 'In Progress')
                    ->where('deleted_at', null)
                    ->orderBy('id','DESC')
                    ->get()->getRowArray();
            }

            // Fallback: bila tetap tidak ada, cek kuota sebelum membuat attempt baru.
            if (!$result) {
                $maxAttempts = (int)($assessment['max_attempts'] ?? 0); // 0 = unlimited
                if ($maxAttempts > 0) {
                    $used = $this->db->table('assessment_results')
                        ->where('assessment_id', $assessmentId)
                        ->where('student_id', $this->studentId)
                        ->whereIn('status', ['Completed','Graded','Expired','Abandoned'])
                        ->where('deleted_at', null)
                        ->countAllResults();
                    if ($used >= $maxAttempts) {
                        $this->db->transRollback();
                        return redirect()->to(route_to('student.assessments.available'))->with('error', 'Melebihi batas percobaan.');
                    }
                }

                // Buat attempt baru via Model agar callback (autoAttemptNumber, fillClientMeta) berjalan
                $model = new AssessmentResultModel();
                $resultId = (int)$model->insert([
                    'assessment_id'  => $assessmentId,
                    'student_id'     => $this->studentId,
                    'status'         => 'In Progress',
                    'started_at'     => $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ], true);

                $result = [
                    'id'             => $resultId,
                    'assessment_id'  => $assessmentId,
                    'student_id'     => $this->studentId,
                    'status'         => 'In Progress',
                    'started_at'     => $now,
                ];
            } else {
                $resultId = (int)$result['id'];
            }

            // Pertanyaan
            $questions = $this->db->table('assessment_questions')
                ->where('assessment_id', $assessmentId)
                ->where('deleted_at', null)
                ->orderBy('order_number', 'ASC')
                ->get()->getResultArray();

            // Hapus jawaban lama untuk result ini (konsistensi jika re-submit)
            $this->db->table('assessment_answers')->where('result_id', $resultId)->delete();

            $totalScore   = 0.0;
            $maxScore     = 0.0;
            $correctCount = 0;
            $answered     = 0;

            foreach ($questions as $q) {
                $qid    = (int) $q['id'];
                $qtype  = (string) $q['question_type'];
                $points = (float) $q['points'];

                // Pada survey, skor/persentase tidak relevan (tetap catat jawaban)
                if (!$disableScore) {
                    $maxScore += $points;
                }

                $field  = 'q_' . $qid;
                $answer = $this->request->getPost($field);

                // Hitung answered
                $hasValue = is_array($answer)
                    ? (count($answer) > 0)
                    : ($answer !== null && $answer !== '');

                if ($hasValue) {
                    $answered++;
                }

                $isAuto    = 0;
                $isCorrect = null;
                $score     = 0.0;
                $opt       = null;
                $opts      = null;
                $text      = null;

                if ($disableScore) {
                    // MODE SURVEY: Simpan jawaban apa adanya tanpa penilaian
                    if (in_array($qtype, ['Multiple Choice', 'True/False', 'Rating Scale'], true)) {
                        $opt = is_array($answer) ? null : ($answer ?? null);
                    } elseif ($qtype === 'Checkbox') {
                        $opts = json_encode(array_values((array) $answer));
                    } else {
                        $text = is_array($answer) ? json_encode($answer) : (string) ($answer ?? '');
                    }
                } else {
                    // MODE DINILAI: hitung skor jika ada kunci jawaban
                    if (in_array($qtype, ['Multiple Choice', 'True/False', 'Rating Scale'], true)) {
                        $isAuto  = 1;
                        $opt     = is_array($answer) ? null : ($answer ?? null);
                        $correct = $q['correct_answer'] ?? null;

                        $hasOpt = ($opt !== null && $opt !== '');
                        $hasKey = ($correct !== null && $correct !== '');

                        if ($hasOpt && $hasKey) {
                            $isCorrect = ((string) $opt === (string) $correct) ? 1 : 0;
                            $score     = $isCorrect ? $points : 0.0;
                            if ($isCorrect) {
                                $correctCount++;
                            }
                        } elseif (!$hasKey && $hasOpt) {
                            // Soal objektif tanpa kunci → Belum Dinilai (manual)
                            $isAuto    = 0;
                            $isCorrect = null;
                            $score     = 0.0;
                        } else {
                            $isCorrect = 0;
                        }
                    } elseif ($qtype === 'Checkbox') {
                        $arr         = (array) $answer;
                        $opts        = json_encode(array_values($arr));
                        $correctJson = $q['correct_answer'] ?? null;

                        if ($correctJson) {
                            // Ada kunci → auto grading set comparison
                            $isAuto = 1;

                            $left  = $this->normalizeChoiceArray($arr);
                            $right = $this->normalizeChoiceArray(json_decode($correctJson, true) ?: []);

                            $match     = ($left === $right);
                            $isCorrect = $match ? 1 : 0;
                            $score     = $match ? $points : 0.0;
                            if ($match) {
                                $correctCount++;
                            }
                        } else {
                            // Checkbox tanpa kunci → Belum Dinilai (manual)
                            $isAuto    = 0;
                            $isCorrect = null;
                            $score     = 0.0;
                        }
                    } elseif ($qtype === 'Essay') {
                        // Essay selalu manual grading
                        $isAuto    = 0;
                        $text      = is_array($answer) ? json_encode($answer) : ($answer ?: null);
                        $isCorrect = null;
                        $score     = 0.0;
                    } else {
                        // Tipe tak dikenal → simpan seadanya sebagai text, tanpa penilaian
                        $isAuto    = 0;
                        $text      = is_array($answer) ? json_encode($answer) : (string) ($answer ?? '');
                        $isCorrect = null;
                        $score     = 0.0;
                    }
                }

                $this->db->table('assessment_answers')->insert([
                    'question_id'     => $qid,
                    'student_id'      => $this->studentId,
                    'result_id'       => $resultId,
                    'answer_text'     => $text,
                    'answer_option'   => $opt,
                    'answer_options'  => $opts,
                    'score'           => $disableScore ? null : $score,
                    'is_correct'      => $disableScore ? null : $isCorrect,
                    'is_auto_graded'  => $disableScore ? 0 : $isAuto,
                    'answered_at'     => $now,
                    'created_at'      => $now,
                ]);

                if (!$disableScore) {
                    $totalScore += $score;
                }
            }

            // Ringkasan hasil
            if ($disableScore) {
                $percentage = null;
                $isPassed   = null;
            } else {
                $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : null;

                // pass_fail dengan passing score aktif → tentukan kelulusan
                if ($mode === 'pass_fail' && $usePassing && $percentage !== null && $assessment['passing_score'] !== null) {
                    $isPassed = ((float)$percentage >= (float)$assessment['passing_score']) ? 1 : 0;
                } else {
                    $isPassed = null; // score_only atau pass_fail tanpa passing-score
                }
            }

            // Hitung waktu dan audit meta
            $resultRow = $this->db->table('assessment_results')->where('id', $resultId)->get()->getRowArray();
            $elapsedFromStart = AssessmentResultModel::calcElapsedFromStartedAt($resultRow['started_at'] ?? null, $now);
            $spentRecorded    = (int)($resultRow['time_spent_seconds'] ?? 0);
            $finalSpent       = max($spentRecorded, $elapsedFromStart);

            // Clamp ke durasi asesmen bila ada
            if (!empty($assessment['duration_minutes'])) {
                $finalSpent = min($finalSpent, (int)$assessment['duration_minutes'] * 60);
            }

            // Update ringkasan result -> Completed.
            $this->db->table('assessment_results')->where('id', $resultId)->update([
                'status'             => 'Completed',
                'total_score'        => $disableScore ? null : $totalScore,
                'max_score'          => $disableScore ? null : ($maxScore > 0 ? $maxScore : null),
                'percentage'         => $disableScore ? null : $percentage,
                'is_passed'          => $isPassed,
                'questions_answered' => $answered,
                'total_questions'    => count($questions),
                'correct_answers'    => $disableScore ? null : $correctCount,
                'time_spent_seconds' => $finalSpent,
                'completed_at'       => $now,
                'updated_at'         => $now,
            ]);

            // Tambahan: isi kolom time_spent_seconds di tabel assessment_answers
            // Menggunakan rata-rata waktu per soal yang terjawab pada attempt ini.
            if ($finalSpent > 0 && $answered > 0) {
                $perAnswer = (int) floor($finalSpent / max(1, $answered));

                $this->db->table('assessment_answers')
                    ->where('result_id', $resultId)
                    ->update([
                        'time_spent_seconds' => $perAnswer,
                    ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi submit gagal.');
            }

            // Redirect sesuai konfigurasi asesmen + waktu rilis
            $showNow     = (int)($assessment['show_result_immediately'] ?? 0) === 1;
            $allowReview = (int)($assessment['allow_review'] ?? 0) === 1;

            $releaseAt   = $assessment['result_release_at'] ?? null;
            $releasedNow = empty($releaseAt) || Time::now($this->tz)->isAfter(Time::parse($releaseAt, $this->tz));

            $goToReview  = $showNow && $allowReview && $releasedNow;

            // AJAX friendly
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'    => 200,
                    'message'   => 'Jawaban tersimpan.',
                    'result_id' => $resultId,
                    'redirect'  => $goToReview
                        ? route_to('student.assessments.review', $resultId)
                        : route_to('student.assessments.results'),
                ]);
            }

            if ($goToReview) {
                return redirect()->to(route_to('student.assessments.review', $resultId))
                    ->with('success', 'Jawaban tersimpan. Menampilkan ringkasan hasil.');
            }

            return redirect()->to(route_to('student.assessments.results'))->with('success', 'Jawaban tersimpan.');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[Student/AssessmentController] submit error: ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status'  => 500,
                    'message' => 'Terjadi kesalahan saat menyimpan jawaban.',
                ]);
            }

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan jawaban.');
        }
    }

    /**
     * Riwayat hasil asesmen milik siswa.
     */
    public function results()
    {
        $this->requireStudent();

        $results = $this->db->table('assessment_results r')
            ->select("
                r.*,
                a.title,
                a.assessment_type,
                a.show_score_to_student,
                a.show_result_immediately,
                a.result_release_at,
                a.evaluation_mode,
                a.use_passing_score,
                a.allow_review
            ")
            ->join('assessments a', 'a.id = r.assessment_id AND a.deleted_at IS NULL', 'inner')
            ->where('r.student_id', $this->studentId)
            ->where('r.deleted_at', null)
            ->orderBy('r.completed_at', 'DESC')
            ->orderBy('r.created_at', 'DESC')
            ->get()->getResultArray();

        return view('student/assessments/results', compact('results'));
    }

    /**
     * Tinjau detail hasil tertentu.
     * Route: GET /student/assessments/review/{resultId}
     */
    public function review(int $resultId)
    {
        $this->requireStudent();

        $result = $this->db->table('assessment_results r')
            ->select("
                r.*,
                a.title,
                a.assessment_type,
                a.passing_score,
                a.allow_review,
                a.show_result_immediately,
                a.show_score_to_student,
                a.result_release_at,
                a.evaluation_mode,
                a.use_passing_score
            ")
            ->join('assessments a', 'a.id = r.assessment_id AND a.deleted_at IS NULL', 'inner')
            ->where('r.id', $resultId)
            ->where('r.student_id', $this->studentId)
            ->where('r.deleted_at', null)
            ->get()->getRowArray();

        if (!$result) {
            return redirect()->to(route_to('student.assessments.results'))
                ->with('error', 'Hasil asesmen tidak ditemukan.');
        }

        // Hanya boleh review jika asesmen mengizinkan dan status sudah selesai
        if ((int)($result['allow_review'] ?? 0) !== 1) {
            return redirect()->to(route_to('student.assessments.results'))
                ->with('error', 'Review jawaban tidak diizinkan untuk asesmen ini.');
        }
        if (!in_array($result['status'] ?? '', ['Completed','Graded'], true)) {
            return redirect()->to(route_to('student.assessments.results'))
                ->with('error', 'Review hanya tersedia setelah penyelesaian.');
        }

        // Hormati waktu rilis hasil
        $releaseAt = $result['result_release_at'] ?? null;
        $now       = Time::now($this->tz);
        $releaseOk = empty($releaseAt) || $now->isAfter(Time::parse($releaseAt, $this->tz));
        if (!$releaseOk) {
            return redirect()->to(route_to('student.assessments.results'))
                ->with('info', 'Hasil belum dirilis. Silakan kembali setelah '.$releaseAt.'.');
        }

        $answers = $this->db->table('assessment_answers aa')
            ->select('aa.*, q.question_text, q.question_type, q.points, q.correct_answer, q.order_number')
            ->join('assessment_questions q', 'q.id = aa.question_id AND q.deleted_at IS NULL', 'left')
            ->where('aa.result_id', $resultId)
            ->where('aa.deleted_at', null)
            ->orderBy('q.order_number', 'ASC')
            ->get()->getResultArray();

        // ---------- LABEL LULUS BERBASIS is_passed (selaras list results) ----------
        $canShowPass = ((int)($result['use_passing_score'] ?? 0) === 1)
            && (($result['evaluation_mode'] ?? 'score_only') === 'pass_fail');

        $passBool  = null;
        $passLabel = null;

        // Di halaman review, rilis sudah dipastikan OK di atas, jadi tidak perlu cek ulang rilis.
        if ($canShowPass) {
            $isp = $result['is_passed'] ?? null;
            if ($isp !== null && $isp !== '') {
                $passBool  = ((int)$isp === 1);
                $passLabel = $passBool ? 'Lulus' : 'Tidak Lulus';
            }
        }
        // ---------------------------------------------------------------------------

        // Flags untuk view
        $flags = [
            'evaluation_mode'       => $result['evaluation_mode'] ?? 'score_only',
            'show_score_to_student' => (int)($result['show_score_to_student'] ?? 1) === 1,
            'use_passing_score'     => (int)($result['use_passing_score'] ?? 0) === 1,
            'released'              => $releaseOk,
            'passBool'              => $passBool,
            'passLabel'             => $passLabel,
        ];

        return view('student/assessments/review', [
            'result'  => $result,
            'answers' => $answers,
            'flags'   => $flags,
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function failBack(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['status'=>400,'message'=>$message]);
        }
        return redirect()->to(route_to('student.assessments.available'))->with('error', $message);
    }

    private function successGo(string $url, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status'=>200,'message'=>$message,'redirect'=>$url]);
        }
        return redirect()->to($url)->with('success', $message);
    }

    /**
     * Cek apakah asesmen dalam rentang tanggal aktif.
     */
    private function isWindowOpen(array $a, string $today): bool
    {
        $startOk = empty($a['start_date']) || $a['start_date'] <= $today;
        $endOk   = empty($a['end_date'])   || $a['end_date']   >= $today;
        return $startOk && $endOk && ((int)$a['is_active'] === 1) && ((int)$a['is_published'] === 1);
    }

    /**
     * Eligibility berbasis target: All / Grade / Class / Individual.
     */
    private function isEligible(array $a, ?array $student): bool
    {
        $aud   = $a['target_audience'] ?? 'All';
        $grade = $student['grade_level'] ?? null; // bisa roman (X/XI/XII) atau angka
        $cid   = (int)($student['class_id'] ?? 0);

        if ($aud === 'All') return true;
        if ($aud === 'Class') return (int)($a['target_class_id'] ?? 0) === $cid;

        if ($aud === 'Grade') {
            [$roman, $num] = $this->gradeRomanAndNumber($grade);
            $tg = $a['target_grade'] ?? null;
            return $tg === $roman || $tg === $num;
        }

        if ($aud === 'Individual') {
            // 1) Assignment eksplisit
            if (method_exists($this->db, 'tableExists') && $this->db->tableExists('assessment_assignees')) {
                $chk = $this->db->table('assessment_assignees')
                    ->where('assessment_id', (int)$a['id'])
                    ->where('student_id', (int)$this->studentId)
                    ->where('deleted_at', null)
                    ->countAllResults();
                if ($chk > 0) return true;
            }
            // 2) Sudah ada result (pre-allocated/Assigned)
            $chk2 = $this->db->table('assessment_results')
                ->where('assessment_id', (int)$a['id'])
                ->where('student_id', (int)$this->studentId)
                ->where('deleted_at', null)
                ->countAllResults();
            if ($chk2 > 0) return true;

            // 3) Fallback jika mekanisme assignment tidak digunakan
            return true;
        }

        return false;
    }

    /**
     * Fallback query untuk daftar asesmen tersedia jika StudentService tidak ada.
     * Termasuk kolom max_attempts agar UI bisa menentukan tombol.
     */
    private function getAvailableAssessmentsFallback(array $student, int $limit = 50): array
    {
        $today = Time::today($this->tz)->toDateString();
        [$gradeRoman, $gradeNum] = $this->gradeRomanAndNumber($student['grade_level'] ?? null);
        $classId = (int)($student['class_id'] ?? 0);

        // Sertakan allow_review & result_release_at agar view bisa memutuskan tombol hasil
        $baseSelect = 'a.id, a.title, a.assessment_type, a.start_date, a.end_date, a.duration_minutes, a.max_attempts, a.target_audience, a.target_class_id, a.target_grade, a.total_questions, a.allow_review, a.result_release_at';

        $builder = $this->db->table('assessments a')
            ->select($baseSelect)
            ->where('a.is_active', 1)
            ->where('a.is_published', 1)
            ->where('a.deleted_at', null)
            ->groupStart()
                ->where("(a.start_date IS NULL OR a.start_date <= '{$today}')", null, false)
            ->groupEnd()
            ->groupStart()
                ->where("(a.end_date IS NULL OR a.end_date >= '{$today}')", null, false)
            ->groupEnd()
            ->groupStart()
                ->where('a.target_audience', 'All')
                ->orGroupStart()
                    ->where('a.target_audience', 'Class')
                    ->where('a.target_class_id', $classId)
                ->groupEnd()
                ->orGroupStart()
                    ->where('a.target_audience', 'Grade')
                    ->groupStart()
                        ->where('a.target_grade', $gradeRoman)
                        ->orWhere('a.target_grade', $gradeNum)
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->orderBy('a.start_date', 'ASC')
            ->limit($limit);

        // Individual: jika ada tabel assignees, ikutkan
        if ($this->db->tableExists('assessment_assignees')) {
            $builder = $this->db->table('assessments a')
                ->select($baseSelect)
                ->where('a.is_active', 1)
                ->where('a.is_published', 1)
                ->where('a.deleted_at', null)
                ->groupStart()
                    ->where("(a.start_date IS NULL OR a.start_date <= '{$today}')", null, false)
                ->groupEnd()
                ->groupStart()
                    ->where("(a.end_date IS NULL OR a.end_date >= '{$today}')", null, false)
                ->groupEnd()
                ->groupStart()
                    ->where('a.target_audience', 'All')
                    ->orGroupStart()
                        ->where('a.target_audience', 'Class')
                        ->where('a.target_class_id', $classId)
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('a.target_audience', 'Grade')
                        ->groupStart()
                            ->where('a.target_grade', $gradeRoman)
                            ->orWhere('a.target_grade', $gradeNum)
                        ->groupEnd()
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('a.target_audience', 'Individual')
                        ->where('EXISTS(SELECT 1 FROM assessment_assignees x WHERE x.assessment_id = a.id AND x.student_id = '.$this->studentId.' AND x.deleted_at IS NULL)', null, false)
                    ->groupEnd()
                ->groupEnd()
                ->orderBy('a.start_date', 'ASC')
                ->limit($limit);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Normalisasi array pilihan (untuk Checkbox) sebagai set terurut.
     */
    private function normalizeChoiceArray($arr): array
    {
        $arr = array_map(static function ($v) {
            return is_scalar($v) ? (string)$v : json_encode($v);
        }, (array)$arr);
        sort($arr, SORT_STRING);
        return array_values($arr);
    }

    /**
     * Kembalikan pasangan [ROMAN, ANGKA] dari grade yang bisa berupa roman/angka/null.
     */
    private function gradeRomanAndNumber($grade): array
    {
        $romanMap = ['10' => 'X', '11' => 'XI', '12' => 'XII'];
        $numMap   = ['X' => '10', 'XI' => '11', 'XII' => '12'];

        $g = strtoupper(trim((string)$grade));
        if (isset($numMap[$g])) {
            return [$g, $numMap[$g]];
        }
        if (isset($romanMap[$g])) {
            return [$romanMap[$g], $g];
        }
        return [null, null];
    }

    /**
     * Pastikan daftar item menjadi object (stdClass) untuk kompatibilitas view.
     */
    private function ensureObjects(array $rows): array
    {
        return array_map(function ($r) {
            return is_array($r) ? (object)$r : $r;
        }, $rows);
    }

    /**
     * Whitelist asesmen: hanya izinkan yang masih ada/aktif/published dan dalam jendela tanggal (server).
     * Ini mengamankan output dari service eksternal yang mungkin tidak mem-filter soft delete.
     */
    private function whitelistAssessments(array $assessments, string $today): array
    {
        if (empty($assessments)) {
            return $assessments;
        }

        $ids = array_values(array_unique(array_map(function ($x) {
            return (int) (is_array($x) ? ($x['id'] ?? 0) : ($x->id ?? 0));
        }, $assessments)));

        if (!$ids) {
            return [];
        }

        $rows = $this->db->table('assessments')
            ->select('id')
            ->whereIn('id', $ids)
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('is_published', 1)
            ->groupStart()
                ->where("(start_date IS NULL OR start_date <= '{$today}')", null, false)
            ->groupEnd()
            ->groupStart()
                ->where("(end_date IS NULL OR end_date >= '{$today}')", null, false)
            ->groupEnd()
            ->get()->getResultArray();

        $okIds = array_map('intval', array_column($rows, 'id'));
        if (!$okIds) {
            return [];
        }

        return array_values(array_filter($assessments, function ($row) use ($okIds) {
            $id = (int)(is_array($row) ? ($row['id'] ?? 0) : ($row->id ?? 0));
            return in_array($id, $okIds, true);
        }));
    }
}
