<!-- app/Views/student/assessments/review.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php helper(['url']); ?>


<?php
// ---------- Helpers ringan & aman ----------
if (!function_exists('rowa')) {
  function rowa($r): array { return is_array($r) ? $r : (is_object($r) ? (array)$r : []); }
}
if (!function_exists('h')) {
  function h($v) { return esc($v ?? ''); }
}
if (!function_exists('fmtNum')) {
  function fmtNum($n, $d=2) {
    if ($n === null || $n === '') return null;
    return number_format((float)$n, $d, ',', '.');
  }
}
if (!function_exists('fmtPct')) {
  function fmtPct($n, $d=2) {
    $f = fmtNum($n, $d);
    return $f === null ? null : ($f.'%');
  }
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
    if ($h) $out[] = $h.'j';
    if ($m) $out[] = $m.'m';
    if ($s) $out[] = $s.'d';
    return implode(' ', $out);
  }
}
if (!function_exists('jsonArr')) {
  /**
   * Decode string ke array:
   * - Format baru: JSON langsung
   * - Format lama: string serialize() yang di dalamnya berisi JSON / array
   *
   * @param mixed $raw
   * @return array
   */
  function jsonArr($raw) {
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
    $arr = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
      return $arr;
    }

    // 2) Coba format lama: string serialize() yang berisi JSON atau array
    //    contoh: s:63:"{\"dim\":\"100\"}";
    if (preg_match('/^[aOs]:/i', $raw)) {
      try {
        $unser = @unserialize($raw, ['allowed_classes' => false]);

        // Kalau hasil unserialize sudah array
        if (is_array($unser)) {
          return $unser;
        }

        // Kasus serialize(string JSON)
        if (is_string($unser)) {
          $arr = json_decode($unser, true);
          if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
            return $arr;
          }
        }
      } catch (\Throwable $e) {
        // Abaikan dan fallback ke []
      }
    }

    return [];
  }
}
if (!function_exists('typeLabel')) {
  function typeLabel(string $t): string {
    $map = [
      'Essay' => 'Essay', 'Multiple Choice' => 'Pilihan Ganda',
      'True/False' => 'Benar/Salah', 'Rating Scale' => 'Skala Penilaian',
      'Checkbox' => 'Pilihan Jamak'
    ];
    return $map[$t] ?? $t;
  }
}
if (!function_exists('statusBadge')) {
  function statusBadge($st) {
    $map = ['Completed'=>'success','Graded'=>'primary','In Progress'=>'warning','Expired'=>'secondary','Assigned'=>'info'];
    return $map[$st] ?? 'secondary';
  }
}
/** Normalisasi URL gambar/file agar selalu absolute */
if (!function_exists('asset_src')) {
  function asset_src(?string $u): string {
    $u = trim((string)$u);
    if ($u === '') return '';
    if (preg_match('~^(?:https?:)?//~i', $u)) return $u;
    $u = str_replace('\\', '/', $u);
    if (stripos($u, 'public/') === 0) $u = substr($u, 7);
    return base_url(ltrim($u, '/'));
  }
}
?>


<?php
// Data utama
$res    = rowa($result ?? []);
$flags  = rowa($flags ?? []);

// Flags dari controller (Opsi B)
$passBool  = $flags['passBool']  ?? null;   // true/false/null
$passLabel = $flags['passLabel'] ?? null;   // 'Lulus'|'Tidak Lulus'|null

$title  = $res['title'] ?? 'Asesmen';
$status = (string)($res['status'] ?? '-');
$badge  = statusBadge($status);

// Flags visibilitas & mode evaluasi (dari controller)
$mode         = $flags['evaluation_mode'] ?? 'score_only';   // 'score_only' | 'pass_fail' | 'survey'
$released     = (bool)($flags['released'] ?? true);          // true jika result_release_at sudah lewat
$showNumsFlag = (bool)($flags['show_score_to_student'] ?? true);

// Apakah angka (skor/persen) boleh tampil?
$showNumbers     = $released && $showNumsFlag && $mode !== 'survey';
// Apakah benar/salah & kunci jawaban boleh tampil?
$showCorrectness = $released && ($mode !== 'survey');

$totalScore = $res['total_score'] ?? null;
$maxScore   = $res['max_score']   ?? null;
$percentage = $res['percentage']  ?? null;
$passScore  = $res['passing_score'] ?? null;

$attempt    = (int)($res['attempt_number'] ?? 1);
$answered   = (int)($res['questions_answered'] ?? 0);
$totalQs    = (int)($res['total_questions'] ?? 0);
$correct    = (int)($res['correct_answers'] ?? 0);
$spent      = (int)($res['time_spent_seconds'] ?? 0);

$startedAt  = $res['started_at'] ?? null;
$completedAt= $res['completed_at'] ?? null;

$allowReview = (int)($res['allow_review'] ?? 0);
// Hanya izinkan melihat daftar jawaban jika counselor mengizinkan review & status sudah selesai/dinilai
$canReviewAnswers = ($allowReview === 1) && in_array($status, ['Completed','Graded'], true);

// Info interpretasi/rekomendasi dari counselor (ditampilkan sebagai ringkasan hasil)
$interpretation  = $res['interpretation']  ?? '';
$recommendations = $res['recommendations'] ?? '';

// Tulisan rilis (jika ada)
$releaseAt = $res['result_release_at'] ?? null;
?>


<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-1">
      <i class="fas fa-poll-h me-2"></i> Ringkasan Hasil — <?= h($title) ?>
    </h4>
    <div class="text-muted">
      Status:
      <span class="badge bg-<?= h($badge) ?>"><?= h($status) ?></span>

      <?php if ($releaseAt): ?>
        <span class="badge bg-light text-body ms-1" title="Waktu rilis hasil">Rilis: <?= h(fmtDate($releaseAt)) ?></span>
      <?php endif; ?>

      <?php if ($passLabel !== null && $status === 'Graded'): ?>
        <span class="badge bg-<?= $passBool ? 'success' : 'danger' ?> ms-1">
          <i class="fas <?= $passBool ? 'fa-check-circle' : 'fa-times-circle' ?> me-1"></i><?= h($passLabel) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>
  <div class="text-end">
    <?php $backUrl = function_exists('route_to') ? route_to('student.assessments.results') : base_url('student/assessments/results'); ?>
    <button type="button" class="btn btn-outline-secondary me-2" onclick="window.print()">
      <i class="fas fa-print me-1"></i> Cetak
    </button>
    <a href="<?= $backUrl ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
  </div>
</div>


<?php if (!$released): ?>
  <div class="alert alert-info">
    <i class="fas fa-clock me-2"></i> Hasil belum dirilis oleh sekolah. Angka skor dan penilaian disembunyikan sampai waktu rilis.
  </div>
<?php elseif ($mode === 'survey'): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i> Ini adalah <strong>survey</strong>. Skor, persentase, dan kunci jawaban tidak ditampilkan.
  </div>
<?php elseif (!$showNumsFlag): ?>
  <div class="alert alert-info">
    <i class="fas fa-eye-slash me-2"></i> Sekolah menyembunyikan skor untuk asesmen ini. Anda tetap dapat melihat jawaban Anda.
  </div>
<?php endif; ?>


<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Skor</div>
        <div class="fs-4 fw-semibold">
          <?= ($showNumbers && $totalScore !== null) ? h(fmtNum($totalScore)) : '—' ?>
          <span class="text-muted fs-6">/ <?= ($showNumbers && $maxScore !== null) ? h(fmtNum($maxScore)) : '—' ?></span>
        </div>
        <div class="mt-2 small"><span class="text-muted">Percobaan:</span> #<?= h($attempt) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Nilai (%)</div>
        <div class="fs-4 fw-semibold"><?= ($showNumbers && $percentage !== null) ? h(fmtPct($percentage)) : '—' ?></div>
        <div class="mt-2 small"><span class="text-muted">Passing:</span> <?= ($showNumbers && $passScore !== null) ? h(fmtPct($passScore, 2)) : '—' ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Soal</div>
        <div class="fs-4 fw-semibold"><?= h($answered) ?> / <?= h($totalQs ?: count($answers ?? [])) ?></div>
        <div class="mt-2 small"><span class="text-muted">Benar (otomatis):</span> <?= $showNumbers ? h($correct) : '—' ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Waktu</div>
        <div class="fs-5 fw-semibold"><?= h(fmtDur($spent)) ?></div>
        <div class="mt-2 small"><span class="text-muted">Mulai:</span> <?= h(fmtDate($startedAt)) ?></div>
        <div class="small"><span class="text-muted">Selesai:</span> <?= h(fmtDate($completedAt)) ?></div>
      </div>
    </div>
  </div>
</div>


<?php if ($interpretation || $recommendations): ?>
  <div class="alert alert-info">
    <div class="fw-semibold mb-1"><i class="fas fa-lightbulb me-2"></i>Interpretasi & Rekomendasi</div>
    <?php if ($interpretation): ?>
      <div class="mb-2">
        <div class="small text-muted">Interpretasi</div>
        <div><?= nl2br(h($interpretation)) ?></div>
      </div>
    <?php endif; ?>
    <?php if ($recommendations): ?>
      <div>
        <div class="small text-muted">Rekomendasi</div>
        <div><?= nl2br(h($recommendations)) ?></div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>


<?php if ($allowReview !== 1): ?>
  <div class="alert alert-warning">
    <i class="fas fa-lock me-2"></i> Review jawaban tidak diizinkan untuk asesmen ini.
  </div>
<?php endif; ?>


<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div><i class="fas fa-list-ol me-2"></i>Daftar Soal & Jawaban</div>
    <div class="small text-muted">Total: <?= h($totalQs ?: count($answers ?? [])) ?> pertanyaan</div>
  </div>
  <div class="card-body p-0">
    <?php if (empty($answers) || !$canReviewAnswers): ?>
      <div class="p-4 text-center text-muted">
        <?php if ($allowReview !== 1): ?>
          Tidak dapat menampilkan jawaban karena fitur review dimatikan.
        <?php else: ?>
          Tidak ada data jawaban untuk ditampilkan.
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($answers as $idx => $aRaw): ?>
          <?php
            $a        = rowa($aRaw);
            $no       = $idx + 1;
            $qText    = $a['question_text'] ?? '(Pertanyaan)';
            $qType    = $a['question_type'] ?? 'Essay';
            $points   = (float)($a['points'] ?? 0);

            $ansText  = $a['answer_text'] ?? null;
            $ansOpt   = $a['answer_option'] ?? null;             // string (MC/TF/Rating)
            $ansOpts  = jsonArr($a['answer_options'] ?? '');      // array (Checkbox)
            $score    = $a['score'] ?? null;
            $isAuto   = (int)($a['is_auto_graded'] ?? 0) === 1;
            $isCorrect= $a['is_correct'] ?? null;                 // 1/0/null
            $answeredAt = $a['answered_at'] ?? null;
            $feedback = $a['feedback'] ?? null;

            $imgUrl   = $a['image_url'] ?? ($a['image'] ?? null);
            $imgSrc   = asset_src($imgUrl);

            $correctRaw = $a['correct_answer'] ?? null;
            $correctArr = jsonArr($correctRaw);
            $correctStr = is_array($correctArr) && $correctArr ? implode(', ', array_map('strval', $correctArr)) : (string)$correctRaw;

            // Normalisasi status benar/salah untuk Checkbox bila backend tidak set is_correct
            if ($qType === 'Checkbox' && $isCorrect === null) {
              $left  = array_values(array_map('strval', $ansOpts));
              $right = array_values(array_map('strval', $correctArr));
              sort($left, SORT_STRING); sort($right, SORT_STRING);
              $isCorrect = ($left === $right) ? 1 : 0;
            }

            // Flag tampilan per-soal mengikuti kebijakan halaman
            $showKeyThis      = $showCorrectness;                 // tampilkan kunci jawaban?
            $showCorrectThis  = $showCorrectness;                 // tampilkan badge Benar/Salah?
            $showScoreThis    = $showNumbers;                     // tampilkan angka skor per soal?

            $flagCls = ($isCorrect === null) ? 'secondary' : (($isCorrect ? 'success' : 'danger'));
            $flagTxt = ($isCorrect === null) ? 'Belum Dinilai' : ($isCorrect ? 'Benar' : 'Salah');
          ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
              <div class="pe-3">
                <div class="fw-semibold">
                  #<?= $no ?> · <?= h(typeLabel((string)$qType)) ?>
                </div>
                <div class="mt-1"><?= nl2br(h($qText)) ?></div>

                <?php if ($imgSrc !== ''): ?>
                  <div class="mt-2">
                    <img src="<?= h($imgSrc) ?>" alt="Gambar pertanyaan" class="img-fluid rounded border" style="max-height:220px">
                  </div>
                <?php endif; ?>

                <div class="row g-2 mt-3">
                  <div class="col-md-<?= $showKeyThis ? '6' : '12' ?>">
                    <div class="text-muted small mb-1">Jawaban kamu</div>
                    <div class="p-2 bg-light rounded border">
                      <?php if ($qType === 'Essay'): ?>
                        <?= ($ansText !== null && $ansText !== '') ? nl2br(h($ansText)) : '<span class="text-muted">—</span>' ?>
                      <?php elseif ($qType === 'Checkbox'): ?>
                        <?php if (!empty($ansOpts)): ?>
                          <ul class="mb-0 ps-3">
                            <?php foreach ($ansOpts as $o): ?>
                              <li><?= h((string)$o) ?></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      <?php else: /* MC/TF/Rating */ ?>
                        <?= ($ansOpt !== null && $ansOpt !== '') ? h((string)$ansOpt) : '<span class="text-muted">—</span>' ?>
                      <?php endif; ?>
                    </div>
                  </div>

                  <?php if ($showKeyThis): ?>
                    <div class="col-md-6">
                      <div class="text-muted small mb-1">Jawaban benar</div>
                      <div class="p-2 bg-light rounded border">
                        <?php if ($qType === 'Checkbox'): ?>
                          <?php if (!empty($correctArr)): ?>
                            <ul class="mb-0 ps-3">
                              <?php foreach ($correctArr as $o): ?>
                                <li><?= h((string)$o) ?></li>
                              <?php endforeach; ?>
                            </ul>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <?= ($correctStr !== '' && $correctStr !== null) ? h($correctStr) : '<span class="text-muted">—</span>' ?>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="mt-2 small text-muted">
                  Dijawab: <?= h(fmtDate($answeredAt)) ?>
                </div>

                <?php if ($feedback): ?>
                  <div class="mt-2">
                    <span class="text-muted small d-block">Feedback Guru BK:</span>
                    <div class="p-2 bg-light rounded border small"><?= nl2br(h($feedback)) ?></div>
                  </div>
                <?php endif; ?>
              </div>

              <div class="text-end" style="min-width:180px">
                <?php if ($showCorrectThis): ?>
                  <div>
                    <span class="badge bg-<?= $flagCls ?>"><?= $flagTxt ?></span>
                    <?php if ($isAuto): ?><span class="badge bg-info text-dark">Auto</span><?php endif; ?>
                  </div>
                <?php endif; ?>
                <div class="mt-2">
                  <div class="small text-muted">Skor</div>
                  <div class="fw-semibold">
                    <?= ($showScoreThis && $score !== null) ? h(fmtNum((float)$score)) : '—' ?>
                    <span class="text-muted">/ <?= $showScoreThis ? h(fmtNum($points)) : '—' ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>


<style>
@media print {
  .btn, .card-header .btn, .alert { display:none !important; }
  a { text-decoration:none; color:#000; }
}
</style>


<?= $this->endSection() ?>
