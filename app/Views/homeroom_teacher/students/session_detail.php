<?php // app/Views/homeroom_teacher/students/session_detail.php ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
// ---------- Helpers ringan ----------
if (!function_exists('h')) {
  function h($v) { return esc($v ?? ''); }
}
if (!function_exists('rowa')) {
  function rowa($r): array {
    return is_array($r)
      ? $r
      : (is_object($r) ? (array)$r : []);
  }
}
if (!function_exists('fmtDateTime')) {
  function fmtDateTime(?string $date, ?string $time = null): string {
    if (!$date) {
      return '-';
    }
    $d = date('d/m/Y', strtotime($date));
    if ($time) {
      $t = date('H:i', strtotime($time));
      return $d . ' • ' . $t;
    }
    return $d;
  }
}
?>

<?php
  // ====== Variabel yang diharapkan dari controller (selaras Parent/Student) ======
  // $student           : array ['id','full_name', ...]
  // $session           : array detail sesi
  //                      (session_date, session_time, location, session_type, topic,
  //                       status, problem_description, session_summary, follow_up_plan,
  //                       is_confidential, counselor_name, counselor_email, class_name, ...)
  // $participants      : array peserta (Kelompok/Klasikal; nama tidak harus ditampilkan)
  // $sessionNotes      : array catatan sesi YANG TIDAK RAHASIA (session_notes.is_confidential = 0)
  // $participationNote : string|null catatan partisipasi siswa (Kelompok/Klasikal)
  // $canSeeNotes       : bool, true jika ringkasan boleh ditampilkan

  $student           = rowa($student ?? []);
  $session           = rowa($session ?? []);
  $participants      = is_array($participants ?? null) ? $participants : [];
  $sessionNotes      = is_array($sessionNotes ?? null) ? $sessionNotes : [];
  $participationNote = $participationNote ?? null;
  $canSeeNotes       = $canSeeNotes ?? true;

  $studentId   = (int)($student['id'] ?? 0);
  $studentName = (string)($student['full_name'] ?? 'Siswa');

  $rawType = (string) ($session['session_type'] ?? '');
  $rawStat = (string) ($session['status'] ?? '');

  $dateLabel = !empty($session['session_date'])
    ? date('d/m/Y', strtotime($session['session_date']))
    : '-';

  $timeLabel = !empty($session['session_time'])
    ? date('H:i', strtotime($session['session_time']))
    : '-';

  switch ($rawType) {
    case 'Individu':
      $typeLabel = 'Konseling Individu';
      $typeBadge = 'bg-primary';
      break;
    case 'Kelompok':
      $typeLabel = 'Konseling Kelompok';
      $typeBadge = 'bg-info';
      break;
    case 'Klasikal':
      $typeLabel = 'Layanan Klasikal';
      $typeBadge = 'bg-secondary';
      break;
    default:
      $typeLabel = $rawType ?: 'Tidak Diketahui';
      $typeBadge = 'bg-light text-muted';
  }

  $statusBadge = 'bg-secondary';
  $statusLabel = $rawStat ?: 'Tidak Diketahui';
  $statusLower = strtolower($rawStat);

  if (str_contains($statusLower, 'dijadwalkan')) {
    $statusBadge = 'bg-primary';
  } elseif (str_contains($statusLower, 'selesai')) {
    $statusBadge = 'bg-success';
  } elseif (str_contains($statusLower, 'batal')) {
    $statusBadge = 'bg-danger';
  } elseif (str_contains($statusLower, 'hadir') || str_contains($statusLower, 'tidak hadir')) {
    $statusBadge = 'bg-info';
  }

  $isConfidential = !empty($session['is_confidential']);
  $location       = (string)($session['location'] ?? '');
  $topic          = (string)($session['topic'] ?? '');
  $counselorName  = (string)($session['counselor_name'] ?? '');
  $counselorEmail = (string)($session['counselor_email'] ?? '');
  $className      = (string)($session['class_name'] ?? '');
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">DETAIL SESI KONSELING</h4>
      <a href="<?= route_to('homeroom.students.sessions', $studentId) ?>"
         class="btn btn-outline-secondary btn-sm">
        &laquo; Kembali ke Jadwal
      </a>
      <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/sessions') ?>">Sesi Konseling</a></li>          
          <li class="breadcrumb-item active">Detail Sesi Konseling</li>
        </ol>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Informasi Utama Sesi -->
    <div class="card mb-3">
      <div class="card-header">
        <strong>Informasi Sesi</strong>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Siswa</dt>
          <dd class="col-sm-9"><?= h($studentName) ?></dd>

          <?php if ($className): ?>
            <dt class="col-sm-3">Kelas</dt>
            <dd class="col-sm-9"><?= h($className) ?></dd>
          <?php endif; ?>

          <dt class="col-sm-3">Tanggal</dt>
          <dd class="col-sm-9"><?= esc($dateLabel) ?></dd>

          <dt class="col-sm-3">Waktu</dt>
          <dd class="col-sm-9"><?= esc($timeLabel) ?> WIB</dd>

          <dt class="col-sm-3">Jenis Layanan</dt>
          <dd class="col-sm-9">
            <span class="badge <?= esc($typeBadge) ?>">
              <?= esc($typeLabel) ?>
            </span>
          </dd>

          <dt class="col-sm-3">Topik</dt>
          <dd class="col-sm-9"><?= h($topic ?: '-') ?></dd>

          <dt class="col-sm-3">Lokasi</dt>
          <dd class="col-sm-9"><?= h($location ?: '-') ?></dd>

          <dt class="col-sm-3">Guru BK</dt>
          <dd class="col-sm-9">
            <?php if ($counselorName || $counselorEmail): ?>
              <div class="d-flex flex-column">
                <?php if ($counselorName): ?>
                  <span><?= h($counselorName) ?></span>
                <?php endif; ?>
                <?php if ($counselorEmail): ?>
                  <small class="text-muted"><?= h($counselorEmail) ?></small>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </dd>

          <dt class="col-sm-3">Status</dt>
          <dd class="col-sm-9">
            <span class="badge <?= esc($statusBadge) ?>">
              <?= esc($statusLabel) ?>
            </span>
          </dd>
        </dl>
      </div>
    </div>

    <!-- Detail Konseling -->
    <div class="card mb-3">
      <div class="card-header">
        <strong>Detail Konseling</strong>
      </div>
      <div class="card-body">
        <?php if (!$canSeeNotes): ?>
          <div class="alert alert-warning mb-0">
            Sesi ini ditandai <strong>rahasia</strong> oleh Guru BK.
            Ringkasan dan catatan sesi tidak ditampilkan.
          </div>
        <?php else: ?>
          <div class="mb-3">
            <h6 class="fw-semibold">Uraian Permasalahan</h6>
            <p class="mb-0">
              <?= nl2br(h($session['problem_description'] ?? '-')) ?>
            </p>
          </div>

          <div class="mb-3">
            <h6 class="fw-semibold">Ringkasan Sesi</h6>
            <p class="mb-0">
              <?= nl2br(h($session['session_summary'] ?? '-')) ?>
            </p>
          </div>

          <div class="mb-0">
            <h6 class="fw-semibold">Rencana Tindak Lanjut</h6>
            <p class="mb-0">
              <?= nl2br(h($session['follow_up_plan'] ?? '-')) ?>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Catatan Partisipasi Siswa (Kelompok / Klasikal) -->
    <?php if ($participationNote): ?>
      <div class="card mb-3">
        <div class="card-header">
          <strong>Catatan Partisipasi Siswa</strong>
        </div>
        <div class="card-body">
          <p class="mb-0">
            <?= nl2br(h($participationNote)) ?>
          </p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Catatan Sesi oleh Guru BK (non-rahasia) -->
    <div class="card mb-3">
      <div class="card-header">
        <strong>Catatan Sesi oleh Guru BK</strong>
      </div>
      <div class="card-body">
        <?php if (!$canSeeNotes): ?>
          <div class="alert alert-warning mb-0">
            Catatan sesi bersifat rahasia dan tidak ditampilkan.
          </div>
        <?php else: ?>
          <?php if (!empty($sessionNotes)): ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($sessionNotes as $noteRaw): ?>
                <?php
                  $note        = rowa($noteRaw);
                  $noteType    = (string)($note['note_type'] ?? 'Catatan');
                  $noteContent = (string)($note['note_content'] ?? '');
                  $isImportant = !empty($note['is_important']);
                  $author      = (string)($note['counselor_name'] ?? 'Guru BK');
                  $createdAt   = null;
                  if (!empty($note['created_at'])) {
                    $createdAt = date('d/m/Y H:i', strtotime($note['created_at']));
                  }

                  // Decode lampiran dari kolom attachments (JSON / array / null)
                  $attachments = [];
                  if (!empty($note['attachments'])) {
                    if (is_array($note['attachments'])) {
                      $attachments = array_values(array_filter($note['attachments'], 'strlen'));
                    } else {
                      $decoded = json_decode((string) $note['attachments'], true);
                      if (is_array($decoded)) {
                        $attachments = array_values(array_filter($decoded, 'strlen'));
                      }
                    }
                  }
                ?>
                <li class="list-group-item px-0">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                      <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <span class="fw-semibold"><?= h($author) ?></span>
                        <span class="badge bg-light text-muted border">
                          <?= h($noteType) ?>
                        </span>
                        <?php if ($isImportant): ?>
                          <span class="badge bg-danger">
                            <i class="mdi mdi-star me-1"></i>Penting
                          </span>
                        <?php endif; ?>
                      </div>
                      <?php if ($createdAt): ?>
                        <small class="text-muted">
                          <i class="mdi mdi-clock-outline me-1"></i><?= esc($createdAt) ?>
                        </small>
                      <?php endif; ?>
                    </div>
                  </div>

                  <?php if ($noteContent): ?>
                    <p class="mb-2">
                      <?= nl2br(h($noteContent)) ?>
                    </p>
                  <?php endif; ?>

                  <?php if (!empty($attachments)): ?>
                    <div class="mb-1">
                      <small class="text-muted d-block mb-1">Lampiran:</small>
                      <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($attachments as $idx => $filePath): ?>
                          <a href="<?= esc(base_url($filePath)) ?>"
                             target="_blank"
                             class="btn btn-sm btn-outline-secondary">
                            <i class="mdi mdi-paperclip me-1"></i>Lampiran <?= $idx + 1 ?>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="mb-0 text-muted fst-italic">
              Belum ada catatan sesi yang dapat ditampilkan.
            </p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
