<!-- app/Views/student/assessments/results.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php helper(['url']); ?>


<?php
// Util kecil untuk keamanan & format
if (!function_exists('dt_id')) {
  function dt_id(?string $s) {
    if (empty($s)) return '-';
    try { return esc(date('d/m/Y H:i', strtotime($s))); } catch (\Throwable $e) { return esc($s); }
  }
}
if (!function_exists('badge_status')) {
  function badge_status($st) {
    return [
      'Assigned'    => 'badge bg-info text-dark',
      'In Progress' => 'badge bg-primary',
      'Completed'   => 'badge bg-secondary',
      'Graded'      => 'badge bg-success',
      'Expired'     => 'badge bg-dark',
      'Abandoned'   => 'badge bg-warning text-dark',
    ][$st] ?? 'badge bg-light text-dark';
  }
}
if (!function_exists('released_ok')) {
  /**
   * Aturan rilis:
   * - Jika show_result_immediately=1 => rilis OK
   * - Jika result_release_at terisi   => rilis OK bila now >= result_release_at
   * - Selain itu                      => belum rilis
   */
  function released_ok(array $r): bool {
    $imm = (int)($r['show_result_immediately'] ?? 0) === 1;
    if ($imm) return true;
    $rel = $r['result_release_at'] ?? null;
    if (empty($rel)) return false;
    return time() >= strtotime($rel);
  }
}
if (!function_exists('can_show_numbers')) {
  /**
   * Boleh menampilkan angka (score/percentage) jika:
   * - rilis sudah OK, dan
   * - show_score_to_student = 1, dan
   * - evaluation_mode != 'survey'
   */
  function can_show_numbers(array $r): bool {
    $okRelease = released_ok($r);
    $showNum   = (int)($r['show_score_to_student'] ?? 1) === 1;
    $mode      = (string)($r['evaluation_mode'] ?? 'score_only');
    return $okRelease && $showNum && $mode !== 'survey';
  }
}
if (!function_exists('pass_fail_cell')) {
  /**
   * Render sel kolom "Lulus" berbasis kolom r.is_passed (0/1/NULL)
   * Kebijakan tampilan:
   * - Hanya relevan bila evaluation_mode = 'pass_fail' DAN use_passing_score = 1
   * - Jika hasil belum dirilis: "Menunggu Rilis"
   * - Jika sudah dirilis tapi status ≠ "Graded": tampilkan "Belum dinilai"
   * - Jika sudah dirilis dan status "Graded": baca r.is_passed → 1=Lulus, 0=Tidak Lulus, NULL=—
   */
  function pass_fail_cell(array $r): string {
    $mode    = (string)($r['evaluation_mode'] ?? 'score_only');
    $usePass = (int)($r['use_passing_score'] ?? 0) === 1;

    // Bukan mode pass_fail atau passing score dimatikan → tidak ada label
    if (!($mode === 'pass_fail' && $usePass)) {
      return '—';
    }

    // Belum rilis hasil ke siswa
    if (!released_ok($r)) {
      return '<span class="text-muted">Menunggu Rilis</span>';
    }

    // Tambahan aturan: Lulus/Tidak Lulus hanya kalau status sudah "Graded"
    $status = (string)($r['status'] ?? '');
    if ($status !== 'Graded') {
      return '<span class="text-muted">Belum dinilai</span>';
    }

    // Sudah rilis & sudah Graded → boleh baca is_passed
    $val = array_key_exists('is_passed', $r) ? $r['is_passed'] : null;
    if ($val === null || $val === '') {
      return '—';
    }
    return ((int)$val === 1)
      ? '<span class="badge bg-success">Lulus</span>'
      : '<span class="badge bg-danger">Tidak Lulus</span>';
  }
}
?>


<div class="page-content">
  <div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h4 class="mb-0">Riwayat Hasil Asesmen</h4>
      <a href="<?= function_exists('route_to') ? route_to('student.assessments.available') : base_url('student/assessments/available') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-list me-1"></i> Asesmen Tersedia
      </a>
    </div>


    <?php if (session('error')): ?>
      <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php elseif (session('success')): ?>
      <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php elseif (session('info')): ?>
      <div class="alert alert-info"><?= esc(session('info')) ?></div>
    <?php endif; ?>


    <div class="card">
      <div class="card-body">
        <?php if (empty($results)): ?>
          <div class="text-center py-4">
            <i class="fas fa-clipboard-check fa-3x text-muted mb-2"></i>
            <p class="text-muted mb-0">Belum ada hasil asesmen.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th>Judul</th>
                  <th>Jenis</th>
                  <th class="text-center">Status</th>
                  <th class="text-end">Nilai</th>
                  <th class="text-end">Persen</th>
                  <th class="text-center">Lulus</th>
                  <th class="text-center">Selesai</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($results as $r): ?>
                <?php
                  $mode        = (string)($r['evaluation_mode'] ?? 'score_only');   // survey|score_only|pass_fail
                  $released    = released_ok($r);
                  $showNumbers = can_show_numbers($r);
                  $status      = (string)($r['status'] ?? '');
                  $allowReview = (int)($r['allow_review'] ?? 0) === 1;

                  // Nilai/persen untuk siswa (tanpa hitung ulang)
                  $scoreText = '—';
                  $percText  = '—';

                  if ($mode !== 'survey') {
                    if ($showNumbers) {
                      $scoreText = ($r['total_score'] !== null) ? number_format((float)$r['total_score'], 2) : '—';
                      $percText  = ($r['percentage']  !== null) ? number_format((float)$r['percentage'], 2).'%' : '—';
                    } elseif (!$released) {
                      $scoreText = '<span class="text-muted">Menunggu Rilis</span>';
                      $percText  = '<span class="text-muted">Menunggu Rilis</span>';
                    } else {
                      // rilis OK tapi show_score_to_student = 0
                      $scoreText = '<span class="text-muted">Disembunyikan</span>';
                      $percText  = '<span class="text-muted">Disembunyikan</span>';
                    }
                  } // survey: tetap — untuk keduanya

                  $reviewUrl = function_exists('route_to')
                      ? route_to('student.assessments.review', (int)$r['id'])
                      : base_url('student/assessments/review/' . (int)$r['id']);
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= esc($r['title'] ?? 'Tanpa Judul') ?></div>
                    <small class="text-muted">Soal: <?= (int)($r['total_questions'] ?? 0) ?> • Benar: <?= (int)($r['correct_answers'] ?? 0) ?></small>
                    <?php if (!$released): ?>
                      <span class="badge bg-light text-body ms-2" title="Hasil akan dirilis pada">
                        Rilis: <?= esc($r['result_release_at'] ?: 'Menunggu jadwal') ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($r['assessment_type'] ?? '-') ?></td>
                  <td class="text-center"><span class="<?= badge_status($status) ?>"><?= esc($status ?: '-') ?></span></td>
                  <td class="text-end"><?= $scoreText ?></td>
                  <td class="text-end"><?= $percText ?></td>
                  <td class="text-center"><?= pass_fail_cell($r) ?></td>
                  <td class="text-center"><?= dt_id($r['completed_at'] ?? $r['updated_at'] ?? $r['created_at'] ?? null) ?></td>
                  <td class="text-end">
                    <?php if ($allowReview && $released && in_array($status, ['Completed','Graded'], true)): ?>
                      <a href="<?= $reviewUrl ?>" class="btn btn-sm btn-outline-secondary">Lihat Detail</a>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-secondary" disabled
                              title="<?= !$allowReview ? 'Review dimatikan' : (!$released ? 'Hasil belum dirilis' : 'Review hanya untuk hasil selesai') ?>">
                        Lihat Detail
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="small text-muted mt-2">
            <i class="fas fa-info-circle me-1"></i>
            Aturan tampilan: angka nilai ditunjukkan hanya jika rilis sudah berlaku dan fitur
            <em>Tampilkan nilai ke siswa</em> diaktifkan. Untuk mode <strong>Survey</strong>, angka tidak ditampilkan.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<?= $this->endSection() ?>
