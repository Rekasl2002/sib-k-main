<!-- app/Views/student/assessments/available.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php helper(['url','form']); ?>

<?php
// Helpers kecil agar view tahan banting untuk array/objek dan formatting ringan
if (!function_exists('rowa')) {
  function rowa($r): array { return is_array($r) ? $r : (is_object($r) ? (array) $r : []); }
}
if (!function_exists('v')) {
  function v($r, $k, $d='') { $a = rowa($r); return esc($a[$k] ?? $d); }
}
if (!function_exists('dur_label')) {
  function dur_label($m) {
    if (!$m || (int)$m <= 0) return 'Tanpa batas';
    $m = (int)$m;
    if ($m < 60) return $m . ' menit';
    $h = intdiv($m, 60); $r = $m % 60;
    return $r ? ($h.' jam '.$r.' menit') : ($h.' jam');
  }
}
if (!function_exists('target_chip')) {
  function target_chip(array $a): string {
    $aud = $a['target_audience'] ?? 'All';
    if ($aud === 'Grade' && !empty($a['target_grade']))      return 'Tingkat: ' . esc($a['target_grade']);
    if ($aud === 'Class') {
      // Tampilkan nama kelas jika tersedia, fallback ke ID
      if (!empty($a['target_class_name'])) return 'Kelas: ' . esc($a['target_class_name']);
      if (!empty($a['class_name']))        return 'Kelas: ' . esc($a['class_name']);
      if (!empty($a['target_class_id']))   return 'Kelas: #' . (int)$a['target_class_id'];
      return 'Kelas tertentu';
    }
    if ($aud === 'Individual')                               return 'Individual';
    return 'Semua Siswa';
  }
}
if (!function_exists('is_window_open')) {
  function is_window_open(array $a, string $today): bool {
    $startOk = empty($a['start_date']) || $a['start_date'] <= $today;
    $endOk   = empty($a['end_date'])   || $a['end_date']   >= $today;
    return $startOk && $endOk && (int)($a['is_active'] ?? 1) === 1 && (int)($a['is_published'] ?? 1) === 1;
  }
}

// Gunakan nilai $today dari controller jika ada, kalau tidak pakai date() server
$__today = isset($today) && is_string($today) ? $today : date('Y-m-d');

// opsional dari controller (kompat): mapping [assessment_id => attempts_used]
$attemptsByAssessment   = isset($attemptsByAssessment)   && is_array($attemptsByAssessment)   ? $attemptsByAssessment   : [];
// opsional dari controller (kompat): mapping [assessment_id => true] bila ada attempt "In Progress"
$inProgressByAssessment = isset($inProgressByAssessment) && is_array($inProgressByAssessment) ? $inProgressByAssessment : [];
?>

<style>
  /* pastikan tombol dapat diklik walau ada elemen overlay dari layout */
  .assess-available .action-cell { position: relative; z-index: 1060; }
  .assess-available .action-cell .btn { position: relative; z-index: 1061; pointer-events: auto; }
</style>

<div class="page-content assess-available">
  <div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h4 class="mb-0">Asesmen Tersedia</h4>
      <div class="d-flex gap-2">
        <a href="<?= function_exists('route_to') ? route_to('student.assessments.results') : base_url('student/assessments/results') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-clipboard-check me-1"></i> Riwayat Hasil
        </a>
        <a href="" class="btn btn-sm btn-outline-primary" onclick="location.reload();return false;">
          <i class="fas fa-sync me-1"></i> Refresh
        </a>
      </div>
    </div>

    <?php if (session('error')): ?>
      <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif; ?>
    <?php if (session('success')): ?>
      <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <?php if (!empty($assessments)): ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th>Judul</th>
                  <th>Jenis</th>
                  <th>Periode</th>
                  <th>Durasi</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($assessments as $itm): ?>
                <?php
                  $a       = rowa($itm);

                  // Guard anti soft-delete / non-aktif / non-published (double-safety jika controller/service belum memfilter)
                  $isDeleted = isset($a['deleted_at']) && !empty($a['deleted_at']);
                  $isActive  = (int)($a['is_active']    ?? 1) === 1;
                  $isPub     = (int)($a['is_published'] ?? 1) === 1;
                  if ($isDeleted || !$isActive || !$isPub) { continue; }

                  $id      = (int)($a['id'] ?? 0);
                  if ($id <= 0) { continue; } // data tak valid

                  $title   = $a['title'] ?? 'Tanpa Judul';
                  $type    = $a['assessment_type'] ?? 'Assessment';
                  $sd      = $a['start_date'] ?? null;
                  $ed      = $a['end_date'] ?? null;
                  $dur     = $a['duration_minutes'] ?? null;
                  $maxA    = (int)($a['max_attempts'] ?? 0); // 0 = unlimited
                  $usedA   = (int)($attemptsByAssessment[$id] ?? 0);
                  $open    = is_window_open($a, $__today);

                  // Prefer data dari controller (ui_start/ui_resume & latest_result)
                  $uiResume = array_key_exists('ui_resume', $a) ? (bool)$a['ui_resume'] : !empty($inProgressByAssessment[$id]);
                  $uiStart  = array_key_exists('ui_start',  $a) ? (bool)$a['ui_start']  : (!$uiResume && ($maxA === 0 || $usedA < $maxA));

                  // latest_result dipakai untuk menentukan tombol "Lihat Hasil" / badge "Assigned"
                  $lr       = isset($a['latest_result']) ? rowa($a['latest_result']) : [];
                  $lrId     = (int)($lr['id'] ?? 0);
                  $lrStatus = (string)($lr['status'] ?? '');
                  $hasAssigned = ($lrStatus === 'Assigned');

                  // allow_review & result_release_at (default aman)
                  $allowReview = (int)($a['allow_review'] ?? 0);
                  $releaseAt   = $a['result_release_at'] ?? null;
                  $releaseOk   = empty($releaseAt) ? true : (strtotime($releaseAt) <= time());

                  // ------------- PRIORITAS AKSI (fix): -------------
                  // 1) Jika ada In Progress → Lanjutkan
                  // 2) Else jika result terakhir Completed/Graded → Lihat Hasil (tak bergantung allow_review)
                  //    - review diizinkan & sudah rilis → ke halaman review
                  //    - selain itu → ke riwayat hasil
                  // 3) Else jika boleh mulai → Kerjakan
                  // 4) Else → Disabled (Menunggu Rilis / Penuh)
                  $hasCompleted = in_array($lrStatus, ['Completed','Graded'], true);

                  // URL (pakai route_to bila tersedia)
                  $startUrl = function_exists('route_to')
                    ? route_to('student.assessments.start', $id)
                    : base_url('student/assessments/start/'.$id);

                  // Resume langsung ke halaman take (controller akan validasi & memakai rid in-progress)
                  $resumeRid = !empty($a['resume_result_id']) ? (int)$a['resume_result_id'] : ($lrStatus==='In Progress' ? $lrId : 0);
                  $takeUrlBase = function_exists('route_to')
                    ? route_to('student.assessments.take', $id)
                    : base_url('student/assessments/take/'.$id);
                  $takeUrl = $resumeRid ? ($takeUrlBase.'?rid='.$resumeRid) : $takeUrlBase;

                  // Url review & fallback hasil
                  $reviewUrl = $lrId
                    ? (function_exists('route_to') ? route_to('student.assessments.review', $lrId) : base_url('student/assessments/review/'.$lrId))
                    : (function_exists('route_to') ? route_to('student.assessments.results') : base_url('student/assessments/results'));
                  $resultsUrl = function_exists('route_to') ? route_to('student.assessments.results') : base_url('student/assessments/results');
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= esc($title) ?></div>
                    <small class="text-muted"><?= target_chip($a) ?></small>
                    <?php if ($hasAssigned): ?>
                      <span class="badge bg-info text-dark ms-2" title="Ditugaskan oleh Guru BK">Assigned</span>
                    <?php endif; ?>
                    <?php if (!$open): ?>
                      <span class="badge bg-secondary ms-2">Di luar periode</span>
                    <?php endif; ?>
                    <?php if (!empty($a['total_questions'])): ?>
                      <span class="badge bg-light text-body ms-2" title="Jumlah soal">Soal: <?= (int)$a['total_questions'] ?></span>
                    <?php endif; ?>
                    <?php if (!$releaseOk && $lrId): ?>
                      <span class="badge bg-light text-body ms-2" title="Hasil dirilis pada">Rilis: <?= esc($releaseAt) ?></span>
                    <?php endif; ?>
                    <?php if ($maxA > 0): ?>
                      <span class="badge bg-light text-body ms-2">Percobaan: <?= $usedA ?>/<?= $maxA ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($type) ?></td>
                  <td><?= esc($sd ?: '-') ?> s.d. <?= esc($ed ?: '-') ?></td>
                  <td><?= esc(dur_label($dur)) ?></td>
                  <td class="text-end action-cell">
                    <?php if ($open): ?>
                      <?php if ($uiResume): ?>
                        <a href="<?= $takeUrl ?>" class="btn btn-sm btn-warning" title="Lanjutkan pengerjaan yang belum selesai" aria-label="Lanjutkan">Lanjutkan</a>

                      <?php elseif ($hasCompleted): ?>
                        <?php if ($allowReview === 1 && $releaseOk && $lrId): ?>
                          <a href="<?= $reviewUrl ?>" class="btn btn-sm btn-outline-secondary" title="Lihat hasil terbaru" aria-label="Lihat Hasil">Lihat Hasil</a>
                        <?php else: ?>
                          <a href="<?= $resultsUrl ?>" class="btn btn-sm btn-outline-secondary" title="Buka riwayat hasil" aria-label="Lihat Hasil">Lihat Hasil</a>
                        <?php endif; ?>

                      <?php elseif ($uiStart): ?>
                        <form method="post" action="<?= $startUrl ?>" class="d-inline" novalidate>
                          <?= csrf_field() ?>
                          <button type="submit" class="btn btn-sm btn-primary" aria-label="Mulai asesmen">Kerjakan</button>
                        </form>

                      <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled aria-disabled="true">Menunggu Rilis</button>
                      <?php endif; ?>
                    <?php else: ?>
                      <?php if ($hasCompleted): ?>
                        <?php if ($allowReview === 1 && $releaseOk && $lrId): ?>
                          <a href="<?= $reviewUrl ?>" class="btn btn-sm btn-outline-secondary" title="Lihat hasil terbaru" aria-label="Lihat Hasil">Lihat Hasil</a>
                        <?php else: ?>
                          <a href="<?= $resultsUrl ?>" class="btn btn-sm btn-outline-secondary" title="Buka riwayat hasil" aria-label="Lihat Hasil">Lihat Hasil</a>
                        <?php endif; ?>
                      <?php elseif ($allowReview === 1 && !$releaseOk && $lrId): ?>
                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Hasil belum dirilis" aria-disabled="true">Menunggu Rilis</button>
                      <?php else: ?>
                        <button class="btn btn-sm btn-secondary" type="button" disabled title="Di luar rentang tanggal" aria-disabled="true">Kerjakan</button>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="small text-muted mt-2">
            <i class="fas fa-info-circle me-1"></i>
            <span>Badge <strong>Assigned</strong> berarti asesmen sudah ditugaskan oleh Guru BK. Jika hasil belum dirilis, angka mungkin disembunyikan sesuai pengaturan.</span>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-2"></i>
            <p class="text-muted mb-1">Belum ada asesmen yang bisa diambil.</p>
            <small class="text-muted d-block">Jika Anda yakin ada asesmen, coba klik <em>Refresh</em> di kanan atas.</small>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
