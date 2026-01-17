<?php
/**
 * File: app/Views/counselor/assessments/result_detail.php
 * Detail Hasil Asesmen Siswa (Counselor)
 *
 * Variabel yang diterima:
 * - int   $assessmentId
 * - int   $resultId
 * - array $result     : baris dari assessment_results + join asesmen & siswa (r.* + joins)
 * - array $questions  : daftar pertanyaan + jawaban (LEFT JOIN)
 *
 * Kolom penting:
 * - assessment_results: total_score, max_score, percentage, is_passed, started_at, completed_at,
 *   time_spent_seconds, reviewed_by, reviewed_at, interpretation, recommendations,
 *   counselor_notes, dimension_scores(JSON), status, attempt_number, total_questions,
 *   questions_answered, ip_address, user_agent.
 */

$this->extend('layouts/main');
$this->section('content');

if (!function_exists('h')) {
    function h($v) { return esc($v ?? ''); }
}
if (!function_exists('fmtDate')) {
    function fmtDate($s) { return $s ? date('d M Y H:i', strtotime($s)) : '-'; }
}
if (!function_exists('fmtDur')) {
    function fmtDur($sec) {
        $sec = (int)$sec;
        if ($sec <= 0) return '-';
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        $s = $sec % 60;
        $out = [];
        if ($h) $out[] = $h . 'j';
        if ($m) $out[] = $m . 'm';
        if ($s) $out[] = $s . 'd';
        return implode(' ', $out);
    }
}
if (!function_exists('jsonArr')) {
    /**
     * Decode string ke array:
     * - Format baru: JSON langsung
     * - Format lama: string serialize() yang di dalamnya berisi JSON
     *
     * @param mixed $raw
     * @return array
     */
    function jsonArr($raw)
    {
        // Sudah array
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw)) {
            return [];
        }

        $raw = trim($raw);
        if ($raw === '' || $raw === '[]' || $raw === '{}') {
            return [];
        }

        // 1) Coba langsung sebagai JSON (format baru)
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // 2) Coba format lama: string serialize() yang berisi JSON
        //    contoh: s:63:"{\"dim\":\"100\"}";
        if (preg_match('/^[aOs]:/i', $raw)) {
            try {
                $unser = @unserialize($raw, ['allowed_classes' => false]);

                // kasus: serialize(string JSON)
                if (is_string($unser)) {
                    $decoded = json_decode($unser, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                }

                // kalau ternyata langsung array
                if (is_array($unser)) {
                    return $unser;
                }
            } catch (\Throwable $e) {
                // abaikan dan fallback ke []
            }
        }

        return [];
    }
}
if (!function_exists('ensureFormHelper')) {
    function ensureFormHelper() {
        if (!function_exists('csrf_field')) helper('form');
    }
}

$base = rtrim(base_url(), '/');
$assessmentTitle = $result['assessment_title'] ?? 'Asesmen';
$studentName     = $result['student_name']     ?? '-';
$nisn            = $result['nisn']             ?? '-';
$nis             = $result['nis']              ?? '-';
$className       = $result['class_name']       ?? '-';

$status          = (string)($result['status'] ?? '-');
$attempt         = (int)($result['attempt_number'] ?? 1);
$totalScore      = ($result['total_score'] === null ? null : (float)$result['total_score']);
$maxScore        = (float)($result['max_score']   ?? 0);
$percentage      = array_key_exists('percentage', $result) && $result['percentage'] !== null
                    ? (float)$result['percentage']
                    : ($maxScore > 0 && $totalScore !== null ? round($totalScore / $maxScore * 100, 2) : null);
$passingScore    = isset($result['passing_score']) ? (float)$result['passing_score'] : null;
$isPassed        = array_key_exists('is_passed', $result) && $result['is_passed'] !== null
                    ? (int)$result['is_passed']
                    : null;
$startedAt       = $result['started_at'] ?? null;
$completedAt     = $result['completed_at'] ?? null;
$totalQuestions  = (int)($result['total_questions'] ?? count($questions));
$answeredCount   = (int)($result['questions_answered'] ?? 0);
$ipAddress       = $result['ip_address'] ?? null;
$userAgent       = $result['user_agent'] ?? null;

// Status badges (termasuk Assigned)
$badgeStatusCls = [
    'Assigned'    => 'secondary',
    'In Progress' => 'warning',
    'Completed'   => 'info',
    'Graded'      => 'success',
    'Expired'     => 'danger',
    'Abandoned'   => 'dark',
    'Not Started' => 'secondary',
];
$badge = $badgeStatusCls[$status] ?? 'secondary';

// Data review yang tersimpan (bila ada)
$interpretation = $result['interpretation']  ?? '';
$recommendations= $result['recommendations'] ?? '';
$counselorNotes = $result['counselor_notes'] ?? '';
$reviewedAt     = $result['reviewed_at']     ?? null;
$reviewedBy     = $result['reviewed_by']     ?? null;
$dimScoresSaved = jsonArr($result['dimension_scores'] ?? '');

// Kumpulkan daftar dimensi dari pertanyaan (unik)
$dimensionsInQuestions = [];
foreach ($questions as $qdim) {
    $d = trim((string)($qdim['dimension'] ?? ''));
    if ($d !== '' && !in_array($d, $dimensionsInQuestions, true)) {
        $dimensionsInQuestions[] = $d;
    }
}

// Pastikan form helper ada untuk csrf_field()
ensureFormHelper();

// Hitung ringkas benar/salah/unknown untuk toolbar filter
$cntCorrect = $cntWrong = $cntUnknown = 0;
foreach ($questions as $qf) {
    $ic = $qf['is_correct'] ?? null;
    if ($ic === null || $ic === '') $cntUnknown++;
    elseif ((int)$ic === 1) $cntCorrect++;
    else $cntWrong++;
}

// Flash
$flashSuccess = session()->getFlashdata('success');
$flashError   = session()->getFlashdata('error');
?>

<div class="d-flex align-items-center justify-content-between mb-3" id="top">
  <div>
    <h4 class="mb-1">
      <i class="fas fa-clipboard-check me-2"></i> Hasil Asesmen — <?= h($assessmentTitle) ?>
    </h4>
    <div class="text-muted">
      <i class="fas fa-user-graduate me-1"></i><?= h($studentName) ?>
      <span class="mx-2">•</span>NIS: <?= h($nis) ?>
      <span class="mx-2">•</span>NISN: <?= h($nisn) ?>
      <span class="mx-2">•</span>Kelas: <?= h($className) ?>
    </div>
  </div>
  <div class="text-end d-flex gap-2">
    <?php if ($status === 'Graded'): ?>
      <form method="post" action="<?= site_url("counselor/assessments/{$assessmentId}/results/{$resultId}/ungrade") ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger" title="Batalkan status Graded (kembali jadi Completed)">
          <i class="fas fa-undo me-1"></i> Batalkan “Graded”/Dinilai
        </button>
      </form>
    <?php endif; ?>
    <button class="btn btn-outline-dark" onclick="window.print()" title="Cetak">
      <i class="fas fa-print me-1"></i> Cetak
    </button>
    <a href="<?= site_url("counselor/assessments/{$assessmentId}/results") ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> Kembali ke Hasil
    </a>
  </div>
</div>

<?php if ($flashSuccess): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= h($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
  <div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i><?= h($flashError) ?></div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small">Status</div>
            <div class="mt-1">
              <span class="badge bg-<?= h($badge) ?>"><?= h($status === 'Assigned' ? 'Assigned (Belum Mulai)' : $status) ?></span>
            </div>
          </div>
          <i class="fas fa-traffic-light fa-lg text-muted"></i>
        </div>
        <div class="mt-3 small">
          <div><span class="text-muted">Mulai:</span> <?= h(fmtDate($startedAt)) ?></div>
          <div><span class="text-muted">Selesai:</span> <?= h(fmtDate($completedAt)) ?></div>
          <div><span class="text-muted">Durasi:</span> <?= h(fmtDur($result['time_spent_seconds'] ?? null)) ?></div>
        </div>
        <?php if ($ipAddress || $userAgent): ?>
        <hr class="my-2">
        <div class="small">
          <?php if ($ipAddress): ?><div><span class="text-muted">IP:</span> <?= h($ipAddress) ?></div><?php endif; ?>
          <?php if ($userAgent): ?><div class="text-break"><span class="text-muted">UA:</span> <?= h($userAgent) ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Percobaan</div>
        <div class="fs-4 fw-semibold"><?= h('#'.$attempt) ?></div>
        <div class="mt-2 small">
          <span class="text-muted">Dijawab:</span>
          <?= h($answeredCount) ?>/<?= h($totalQuestions) ?>
          <?php if ($status === 'Assigned'): ?>
            <span class="badge bg-secondary ms-1">Belum Mulai</span>
          <?php endif; ?>
        </div>
        <?php if ($totalQuestions > 0): ?>
          <?php $prog = max(0, min(100, round(($answeredCount / max(1,$totalQuestions)) * 100))); ?>
          <div class="progress mt-2" style="height:8px">
            <div class="progress-bar" role="progressbar" style="width: <?= (int)$prog ?>%" aria-valuenow="<?= (int)$prog ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="small text-muted mt-1"><?= (int)$prog ?>%</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Skor</div>
        <div class="fs-4 fw-semibold">
          <?php if ($maxScore > 0 && $totalScore !== null): ?>
            <?= h(number_format($totalScore, 2)) ?> <span class="text-muted fs-6">/ <?= h(number_format($maxScore, 2)) ?></span>
          <?php else: ?>
            <span class="text-muted">Tidak berlaku</span>
          <?php endif; ?>
        </div>
        <div class="mt-2 small"><span class="text-muted">Passing:</span> <?= $passingScore !== null ? h(number_format($passingScore, 2)).'%' : '-' ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small">Nilai</div>
            <div class="fs-4 fw-semibold"><?= $percentage !== null ? h(number_format($percentage, 2)).'%' : '-' ?></div>
          </div>
          <?php if ($percentage !== null && $passingScore !== null): ?>
            <span class="badge bg-light text-<?= ($percentage >= $passingScore ? 'success' : 'danger') ?> border">
              <?= ($percentage >= $passingScore ? 'Lulus' : 'Tidak Lulus') ?>
            </span>
          <?php elseif ($isPassed !== null): ?>
            <span class="badge bg-light text-<?= ($isPassed ? 'success' : 'danger') ?> border">
              <?= ($isPassed ? 'Lulus' : 'Tidak Lulus') ?>
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($counselorNotes)): ?>
<div class="alert alert-info d-flex align-items-start">
  <i class="fas fa-sticky-note me-2 mt-1"></i>
  <div>
    <div class="fw-semibold mb-1">Catatan Guru BK</div>
    <div><?= nl2br(h($counselorNotes)) ?></div>
  </div>
</div>
<?php endif; ?>

<!-- Bantuan istilah -->
<details class="mb-3">
  <summary class="text-muted small">Bantuan istilah</summary>
  <ul class="small mt-2 mb-0">
    <li><strong>Interpretasi</strong>: ringkasan makna hasil untuk siswa (apa artinya skor/temuan utama).</li>
    <li><strong>Rekomendasi</strong>: saran tindak lanjut yang praktis (mis. strategi belajar, rujukan, latihan).</li>
    <li><strong>Catatan Guru BK</strong>: catatan internal/komunikasi singkat yang relevan.</li>
    <li><strong>Skor per Dimensi</strong>: skor ringkas untuk aspek tertentu (mis. “Konsentrasi”, “Kerja Tim”). Opsional.</li>
  </ul>
</details>

<!-- Review & Penilaian (meta) -->
<div class="card mb-3" id="review">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div><i class="fas fa-pen-alt me-2"></i>Review & Penilaian Ringkas</div>
    <?php if ($reviewedAt): ?>
      <div class="small text-muted">
        Terakhir ditinjau: <?= h(fmtDate($reviewedAt)) ?><?= $reviewedBy ? ' oleh #'.h($reviewedBy) : '' ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="post" action="<?= site_url('counselor/assessments/grade/submit') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="result_id" value="<?= (int)$resultId ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Interpretasi</label>
          <textarea name="interpretation" class="form-control" rows="4" placeholder="Ringkasan interpretasi hasil"><?= h($interpretation) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Rekomendasi</label>
          <textarea name="recommendations" class="form-control" rows="4" placeholder="Rekomendasi tindak lanjut"><?= h($recommendations) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Catatan Guru BK</label>
          <textarea name="counselor_notes" class="form-control" rows="3" placeholder="Catatan tambahan untuk siswa/orang tua/guru"><?= h($counselorNotes) ?></textarea>
        </div>
      </div>

      <?php if (!empty($dimensionsInQuestions) || !empty($dimScoresSaved)): ?>
        <hr>
        <div class="mb-2 fw-semibold">Skor per Dimensi</div>
        <div class="row g-2">
          <?php
            $dimsAll = array_values(array_unique(array_merge($dimensionsInQuestions, array_keys($dimScoresSaved))));
            foreach ($dimsAll as $dimName):
              $val = '';
              if (isset($dimScoresSaved[$dimName])) $val = (string)$dimScoresSaved[$dimName];
          ?>
            <div class="col-sm-6 col-md-3">
              <label class="form-label small text-muted mb-1"><?= h($dimName) ?></label>
              <input type="number" step="0.01" class="form-control" name="dimension_scores[<?= esc($dimName, 'attr') ?>]" value="<?= h($val) ?>" placeholder="Skor">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i> Simpan Review
        </button>
        <a class="btn btn-outline-secondary" href="<?= site_url("counselor/assessments/{$assessmentId}/results") ?>">
          Kembali
        </a>
        <a class="btn btn-outline-secondary" href="#top" title="Ke atas">
          <i class="fas fa-angle-up"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <span><i class="fas fa-list-ol me-2"></i>Daftar Soal & Jawaban</span>
      <!-- Toolbar filter client-side (tidak mengubah backend) -->
      <div class="btn-group btn-group-sm" role="group" aria-label="Filter">
        <button type="button" class="btn btn-outline-secondary active" data-filter="all">
          Semua
        </button>
        <button type="button" class="btn btn-outline-success" data-filter="correct">
          Benar (<?= (int)$cntCorrect ?>)
        </button>
        <button type="button" class="btn btn-outline-danger" data-filter="wrong">
          Salah (<?= (int)$cntWrong ?>)
        </button>
        <button type="button" class="btn btn-outline-dark" data-filter="unknown">
          Belum Dinilai (<?= (int)$cntUnknown ?>)
        </button>
      </div>
    </div>
    <div class="small text-muted">Total: <?= h($totalQuestions) ?> pertanyaan</div>
  </div>
  <div class="card-body p-0">
    <?php if (!$questions): ?>
      <div class="p-4 text-center text-muted">Belum ada pertanyaan untuk asesmen ini.</div>
    <?php else: ?>
      <?php if ($status === 'Assigned'): ?>
        <div class="p-3 alert alert-secondary border-0 rounded-0 mb-0">
          <i class="fas fa-circle me-2"></i>
          Asesmen sudah <strong>Assigned</strong> kepada siswa ini, namun belum ada jawaban karena siswa belum memulai.
        </div>
      <?php endif; ?>
      <div class="list-group list-group-flush" id="qaList">
        <?php foreach ($questions as $idx => $q): ?>
          <?php
            $no      = $idx + 1;
            $qType   = $q['question_type'] ?? 'Multiple Choice';
            $opts    = jsonArr($q['options'] ?? '[]');
            $correct = $q['correct_answer'] ?? null;          // string atau JSON (Checkbox)
            $points  = (float)($q['points'] ?? 0);
            $image   = $q['image_url'] ?? null;
            $dim     = $q['dimension'] ?? null;

            $ansId    = $q['answer_id'] ?? null;
            $ansOpt   = $q['answer_option'] ?? null;          // string (MC/TF/Rating)
            $ansOpts  = jsonArr($q['answer_options'] ?? '');  // array (Checkbox)
            $ansText  = $q['answer_text'] ?? null;            // Essay
            $ansScore = $q['answer_score'] ?? null;
            $isCorrect= array_key_exists('is_correct', $q) ? $q['is_correct'] : null;
            $isAuto   = (int)($q['is_auto_graded'] ?? 0) === 1;
            $fb       = $q['feedback'] ?? null;
            $spent    = $q['time_spent_seconds'] ?? null;

            $correctArr = [];
            if ($qType === 'Checkbox' && $correct) $correctArr = jsonArr($correct);

            $flagCls = $isCorrect === null ? 'secondary' : ((int)$isCorrect === 1 ? 'success' : 'danger');
            $flagTxt = $isCorrect === null ? 'Belum Dinilai' : ((int)$isCorrect === 1 ? 'Benar' : 'Salah');

            $state = 'unknown';
            if ($isCorrect === null || $isCorrect === '') $state = 'unknown';
            elseif ((int)$isCorrect === 1) $state = 'correct';
            else $state = 'wrong';
          ?>
          <div class="list-group-item qa-item" data-state="<?= h($state) ?>">
            <div class="d-flex justify-content-between align-items-start">
              <div class="me-3">
                <div class="fw-semibold">
                  #<?= $no ?> · <?= h($qType) ?>
                  <?php if ($dim): ?> · <span class="text-muted"><?= h($dim) ?></span><?php endif; ?>
                </div>
                <div class="mt-1"><?= nl2br(h($q['question_text'] ?? '')) ?></div>
                <?php if ($image): ?>
                  <div class="mt-2">
                    <img src="<?= strpos($image, 'http') === 0 ? h($image) : h($base.'/'.$image) ?>" alt="image" class="img-fluid rounded border" style="max-height:220px">
                  </div>
                <?php endif; ?>

                <?php if (in_array($qType, ['Multiple Choice','True/False','Rating Scale','Checkbox'])): ?>
                  <div class="mt-3">
                    <div class="text-muted small mb-1">Pilihan:</div>
                    <div class="d-flex flex-wrap gap-2">
                      <?php foreach ($opts as $op): ?>
                        <?php
                          $chosen = false;
                          if ($qType === 'Checkbox') {
                              $chosen = in_array((string)$op, array_map('strval', $ansOpts ?? []), true);
                          } else {
                              $chosen = ((string)$ansOpt === (string)$op);
                          }
                          $isCorr = false;
                          if ($qType === 'Checkbox') $isCorr = in_array((string)$op, array_map('strval', $correctArr), true);
                          else $isCorr = ($correct !== null && (string)$correct === (string)$op);
                        ?>
                        <span class="badge <?= $chosen ? 'bg-primary' : 'bg-light text-muted border' ?>">
                          <?= h($op) ?>
                          <?php if ($isCorr): ?><i class="fas fa-check ms-1"></i><?php endif; ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if ($qType === 'Essay'): ?>
                  <div class="mt-3">
                    <div class="text-muted small mb-1">Jawaban Siswa:</div>
                    <div class="p-2 bg-light rounded border">
                      <?= ($ansText !== null && $ansText !== '') ? nl2br(h($ansText)) : '<span class="text-muted">—</span>' ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (!empty($q['explanation'])): ?>
                  <div class="mt-3">
                    <details>
                      <summary class="text-muted small">Pembahasan</summary>
                      <div class="mt-2 p-2 bg-light rounded border"><?= nl2br(h($q['explanation'])) ?></div>
                    </details>
                  </div>
                <?php endif; ?>
              </div>

              <div class="text-end" style="min-width:260px">
                <div class="d-flex justify-content-end align-items-center gap-1">
                  <span class="badge bg-<?= $flagCls ?>"><?= $flagTxt ?></span>
                  <?php if ($isAuto): ?><span class="badge bg-info text-dark">Otomatis</span><?php endif; ?>
                </div>
                <div class="mt-2">
                  <div class="small text-muted">Skor</div>
                  <div class="fw-semibold">
                    <?= $ansScore !== null ? h(number_format((float)$ansScore, 2)) : '—' ?>
                    <span class="text-muted">/ <?= h(number_format($points, 2)) ?></span>
                  </div>
                </div>
                <div class="mt-2 small text-muted">Waktu: <?= h(fmtDur($spent)) ?></div>

                <?php if ($fb): ?>
                  <div class="mt-2">
                    <span class="text-muted small d-block">Feedback:</span>
                    <div class="p-2 bg-light rounded border small"><?= nl2br(h($fb)) ?></div>
                  </div>
                <?php endif; ?>

                <?php if ($ansId): ?>
                  <hr class="my-2">
                  <form method="post" action="<?= site_url('counselor/assessments/answers/grade') ?>" class="qa-grade-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="assessment_id" value="<?= (int)$assessmentId ?>">
                    <input type="hidden" name="result_id" value="<?= (int)$resultId ?>">
                    <input type="hidden" name="answer_id" value="<?= (int)$ansId ?>">

                    <div class="row g-2 align-items-end">
                      <div class="col-6">
                        <label class="form-label small mb-1">Skor</label>
                        <input type="number" step="0.01" min="0" max="<?= h($points) ?>" name="score" value="<?= h($ansScore !== null ? (float)$ansScore : '') ?>" class="form-control">
                      </div>
                      <div class="col-6">
                        <label class="form-label small mb-1">Tandai</label>
                        <select name="is_correct" class="form-select form-select-sm">
                          <option value="" <?= ($isCorrect === null || $isCorrect === '') ? 'selected':'' ?>>Belum Dinilai</option>
                          <option value="1" <?= (string)$isCorrect === '1' ? 'selected':'' ?>>Benar</option>
                          <option value="0" <?= (string)$isCorrect === '0' ? 'selected':'' ?>>Salah</option>
                        </select>
                      </div>
                      <div class="col-12">
                        <label class="form-label small mb-1">Feedback (opsional)</label>
                        <input type="text" name="feedback" value="<?= h($fb ?? '') ?>" class="form-control" placeholder="Komentar singkat">
                      </div>
                      <div class="col-12 d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i> Simpan Penilaian</button>
                        <a href="#top" class="btn btn-sm btn-outline-secondary">Ke Atas</a>
                      </div>
                    </div>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Toolbar filter client-side
  document.addEventListener('DOMContentLoaded', function () {
    var buttons = document.querySelectorAll('[data-filter]');
    var items   = document.querySelectorAll('.qa-item');

    buttons.forEach(function(btn){
      btn.addEventListener('click', function(){
        buttons.forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        var f = btn.getAttribute('data-filter') || 'all';

        items.forEach(function(it){
          if (f === 'all') { it.style.display = ''; return; }
          var s = it.getAttribute('data-state');
          it.style.display = (s === f) ? '' : 'none';
        });
      });
    });
  });
</script>

<style>
  .list-group-item { padding-top: 1rem; padding-bottom: 1rem; }
  @media print {
    .btn, .card-header .btn-group, .alert, .page-header, .text-end .btn { display: none !important; }
    .card { box-shadow: none !important; }
    a[href]:after { content: ""; }
  }
</style>

<?php $this->endSection(); ?>
