<!-- app/Views/student/violations/detail.php -->
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * @var array|null       $student
 * @var array|object     $violation
 * @var array|object[]   $sanctions
 * @var array|null       $evidence_files
 */

// Normalisasi violation ke array
$violation = is_array($violation) ? $violation : (array) $violation;

// Bangun $student dari data yang ada bila belum dikirim terpisah
$student = $student ?? [
    'full_name'  => $violation['student_full_name'] ?? '',
    'nis'        => $violation['student_nis'] ?? '',
    'nisn'       => $violation['student_nisn'] ?? '',
    'class_name' => $violation['class_name'] ?? '',
];

// Badge tingkat keparahan
$sev = (string) ($violation['severity_level'] ?? '');
$severityBadge = match ($sev) {
    'Ringan' => 'bg-info',
    'Sedang' => 'bg-warning',
    'Berat'  => 'bg-danger',
    default  => 'bg-secondary',
};

// Badge status pelanggaran
$status = (string) ($violation['status'] ?? '');
$statusBadge = match ($status) {
    'Dilaporkan'   => 'bg-warning',
    'Dalam Proses' => 'bg-info',
    'Diproses'     => 'bg-info', // kompatibel enum lama
    'Selesai'      => 'bg-success',
    'Dibatalkan'   => 'bg-secondary',
    default        => 'bg-secondary',
};

// Tanggal & jam
$dateRaw = $violation['violation_date'] ?? ($violation['created_at'] ?? null);
$timeRaw = $violation['violation_time'] ?? null;

$dateLabel = '-';
if (!empty($dateRaw)) {
    $ts = strtotime($dateRaw);
    $dateLabel = $ts ? date('Y-m-d', $ts) : (string) $dateRaw;
}

$timeLabel = '';
if (!empty($timeRaw)) {
    $t = substr($timeRaw, 0, 5);
    if ($t !== '00:00') {
        $timeLabel = $t;
    }
}

// Poin
$points = (int) ($violation['point_deduction'] ?? 0);

// Nama reporter & handler
$reporterName = $violation['reporter_name'] ?? ($violation['reported_by_name'] ?? '-');
$handlerName  = $violation['handler_name']  ?? ($violation['handled_by_name']  ?? '-');

// Evidence files (JSON array atau string tunggal)
$evidenceFiles = $evidence_files ?? ($violation['evidence_files'] ?? []);
if (!is_array($evidenceFiles)) {
    $evidenceFiles = [];
}
if (empty($evidenceFiles) && !empty($violation['evidence'])) {
    $tmp = json_decode($violation['evidence'], true);
    if (is_array($tmp)) {
        $evidenceFiles = $tmp;
    } else {
        $evidenceFiles = [$violation['evidence']];
    }
}
// Bersihkan & normalisasi path
$evidenceFiles = array_values(array_filter(array_map('strval', $evidenceFiles), static function ($v) {
    return $v !== '';
}));

// Status notifikasi orang tua
$parentNotified   = (int) ($violation['parent_notified'] ?? 0);
$parentNotifiedAt = $violation['parent_notified_at'] ?? null;

$parentBadgeClass = $parentNotified ? 'bg-success' : 'bg-secondary';
$parentLabel      = $parentNotified ? 'Sudah diberitahu' : 'Belum diberitahu';

$parentAtLabel = '';
if (!empty($parentNotifiedAt)) {
    $pts = strtotime($parentNotifiedAt);
    $parentAtLabel = $pts ? date('d M Y H:i', $pts) : (string) $parentNotifiedAt;
}
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <h4 class="mb-0">DETAIL PELANGGARAN</h4>
      <div class="d-flex align-items-center gap-2">
      </div>
      <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"> <a href="<?= base_url('student/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"> <a href="<?= base_url('student/violations') ?>">Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item active">Detail Kasus & Pelanggaran</li>
        </ol>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php elseif (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-8">

        <!-- Informasi Pelanggaran -->
        <div class="card mb-3">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
              <div>
                <h5 class="card-title mb-1">
                  <?= esc($violation['category_name'] ?? 'Pelanggaran') ?>
                </h5>
                <p class="text-muted mb-0">
                  <?= esc($student['full_name'] ?? '') ?>
                  <?php if (!empty($student['class_name'])): ?>
                    &middot; Kelas <?= esc($student['class_name']) ?>
                  <?php endif; ?>
                  <br>
                  <?php
                  $nis  = trim((string) ($student['nis'] ?? ''));
                  $nisn = trim((string) ($student['nisn'] ?? ''));
                  ?>
                  NIS/NISN:
                  <?= esc($nis  !== '' ? $nis  : '-') ?> /
                  <?= esc($nisn !== '' ? $nisn : '-') ?>
                </p>
              </div>

              <div class="text-end">
                <span class="badge <?= $severityBadge ?> me-1">
                  <?= esc($violation['severity_level'] ?? 'Tidak diketahui') ?>
                </span>
                <span class="badge <?= $statusBadge ?>">
                  <?= esc($status ?: 'Status tidak diketahui') ?>
                </span>
              </div>
            </div>

            <hr>

            <dl class="row mb-0">
              <dt class="col-sm-3">Tanggal</dt>
              <dd class="col-sm-9">
                <?= esc($dateLabel) ?>
                <?php if ($timeLabel): ?>
                  &bull; <?= esc($timeLabel) ?>
                <?php endif; ?>
              </dd>

              <dt class="col-sm-3">Lokasi</dt>
              <dd class="col-sm-9">
                <?= esc($violation['location'] ?? '-') ?>
              </dd>

              <dt class="col-sm-3">Saksi</dt>
              <dd class="col-sm-9">
                <?= esc($violation['witness'] ?? '-') ?>
              </dd>

              <dt class="col-sm-3">Bukti</dt>
              <dd class="col-sm-9">
                <?php if (empty($evidenceFiles)): ?>
                  <span class="text-muted">Tidak ada bukti terlampir di sistem.</span>
                <?php else: ?>
                  <ul class="list-unstyled mb-0">
                    <?php foreach ($evidenceFiles as $idx => $filePath): ?>
                      <?php
                        $filePath = (string) $filePath;
                        if ($filePath === '') continue;
                        $url      = base_url($filePath);
                        $basename = basename($filePath);
                      ?>
                      <li class="mb-1">
                        <i class="bx bx-paperclip me-1"></i>
                        <a href="<?= esc($url) ?>" target="_blank">
                          <?= esc($basename) ?>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </dd>

              <dt class="col-sm-3">Poin Pelanggaran</dt>
              <dd class="col-sm-9">
                <span class="badge bg-danger">
                  <?= $points ?> poin
                </span>
              </dd>

              <dt class="col-sm-3">Pencatat</dt>
              <dd class="col-sm-9">
                <?= esc($reporterName ?: '-') ?>
              </dd>

              <dt class="col-sm-3">Penanggung Jawab</dt>
              <dd class="col-sm-9">
                <?= esc($handlerName ?: '-') ?>
              </dd>

              <dt class="col-sm-3">Deskripsi Kejadian</dt>
              <dd class="col-sm-9">
                <?= nl2br(esc($violation['description'] ?? '-', 'html')) ?>
              </dd>

              <?php if (!empty($violation['resolution_notes'])): ?>
                <dt class="col-sm-3">Tindak Lanjut</dt>
                <dd class="col-sm-9">
                  <?= nl2br(esc($violation['resolution_notes'], 'html')) ?>
                </dd>
              <?php endif; ?>

              <dt class="col-sm-3">Notifikasi Orang Tua</dt>
              <dd class="col-sm-9">
                <span class="badge <?= $parentBadgeClass ?>">
                  <?= esc($parentLabel) ?>
                </span>
                <?php if ($parentAtLabel): ?>
                  <span class="text-muted ms-2">
                    (<?= esc($parentAtLabel) ?>)
                  </span>
                <?php endif; ?>
              </dd>

              <?php if (!empty($violation['notes'])): ?>
                <dt class="col-sm-3">Catatan Tambahan</dt>
                <dd class="col-sm-9">
                  <?= nl2br(esc($violation['notes'], 'html')) ?>
                </dd>
              <?php endif; ?>
            </dl>

          </div>
        </div>

        <!-- Daftar Sanksi -->
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Sanksi Terkait</h5>
          </div>
          <div class="card-body">

            <?php if (empty($sanctions)): ?>
              <p class="text-muted mb-0">
                Belum ada sanksi yang tercatat untuk pelanggaran ini.
              </p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Jenis Sanksi</th>
                      <th>Deskripsi</th>
                      <th>Status</th>
                      <th>Tenggat / Selesai</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($sanctions as $s): ?>
                      <?php
                        $row = is_array($s) ? $s : (array) $s;

                        $sStatus = (string) ($row['status'] ?? '');
                        $sBadge = match ($sStatus) {
                            'Dijadwalkan'     => 'bg-secondary',
                            'Sedang Berjalan' => 'bg-info',
                            'Selesai'         => 'bg-success',
                            'Dibatalkan'      => 'bg-danger',
                            default           => 'bg-secondary',
                        };

                        // Tanggal jatuh tempo & selesai, sembunyikan 0000-00-00
                        $dueDate   = $row['end_date']       ?? null;
                        $completed = $row['completed_date'] ?? null;

                        $dueLabel = (!empty($dueDate) && strpos($dueDate, '0000-00-00') !== 0)
                            ? $dueDate
                            : null;

                        $compLabel = (!empty($completed) && strpos($completed, '0000-00-00') !== 0)
                            ? $completed
                            : null;
                      ?>
                      <tr>
                        <td>
                          <strong><?= esc($row['sanction_type'] ?? '-') ?></strong><br>
                          <small class="text-muted">
                            Oleh: <?= esc($row['assigned_by_name'] ?? '-') ?>
                          </small>
                        </td>
                        <td>
                          <?= nl2br(esc($row['sanction_description'] ?? ($row['description'] ?? '-'), 'html')) ?>
                          <?php
                            $extraNotes = $row['sanction_notes'] ?? ($row['completion_notes'] ?? '');
                          ?>
                          <?php if (!empty($extraNotes)): ?>
                            <br>
                            <small class="text-muted">
                              Catatan: <?= nl2br(esc($extraNotes, 'html')) ?>
                            </small>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="badge <?= $sBadge ?>">
                            <?= esc($sStatus ?: '-') ?>
                          </span>
                        </td>
                        <td>
                          <?php if ($dueLabel): ?>
                            <div>Jatuh tempo: <?= esc($dueLabel) ?></div>
                          <?php endif; ?>
                          <?php if ($compLabel): ?>
                            <div>Selesai: <?= esc($compLabel) ?></div>
                          <?php endif; ?>
                          <?php if (!$dueLabel && !$compLabel): ?>
                            <span class="text-muted">-</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <p class="text-muted mt-3 mb-0 small">
              Informasi sanksi ini bersifat ringkasan. Detail pelaksanaan dapat dibahas langsung
              dengan Wali Kelas atau Guru BK.
            </p>

          </div>
        </div>

      </div>

      <!-- Kolom kanan: ringkasan poin -->
      <div class="col-lg-4 mt-3 mt-lg-0">
        <div class="card">
          <div class="card-body text-center">
            <div class="avatar-lg mx-auto mb-3">
              <span class="avatar-title rounded-circle bg-primary text-white font-size-24">
                <?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?>
              </span>
            </div>
            <h5 class="mb-1"><?= esc($student['full_name'] ?? '') ?></h5>
            <p class="text-muted mb-2">
              NISN: <?= esc($student['nisn'] ?? '-') ?>
            </p>

            <hr>

            <p class="mb-1 text-muted">Kategori Pelanggaran</p>
            <p class="fw-bold mb-2">
              <?= esc($violation['category_name'] ?? '-') ?>
            </p>

            <p class="mb-1 text-muted">Poin Pelanggaran</p>
            <p class="fw-bold mb-0 text-danger">
              <?= $points ?> poin
            </p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
