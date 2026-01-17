<!-- app/Views/parent/violations/detail.php -->
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * @var array|null       $student
 * @var array|object     $violation
 * @var array|object[]   $sanctions
 * @var array|null       $evidence_files
 */

// Helper aman (route_to, base_url, csrf_field)
if (function_exists('helper')) {
    try {
        helper(['url', 'form']);
    } catch (\Throwable $e) {
        // ignore
    }
}

// Normalisasi violation ke array
$violation = is_array($violation) ? $violation : (array) $violation;

// Bangun $student dari data yang ada bila belum dikirim terpisah
$student = $student ?? [
    'full_name'  => $violation['student_full_name'] ?? '',
    'nis'        => $violation['student_nis'] ?? '',
    'nisn'       => $violation['student_nisn'] ?? '',
    'class_name' => $violation['class_name'] ?? '',
    // opsional: fallback student_id kalau ada
    'id'         => $violation['student_id'] ?? null,
];

$student = is_array($student) ? $student : (array) $student;

// Fallback id agar route_to tidak error bila student id tidak terkirim
$studentId  = (int) ($student['id'] ?? ($violation['student_id'] ?? 0));
$violationId = (int) ($violation['id'] ?? ($violation['violation_id'] ?? 0));

// URL kembali ke riwayat pelanggaran anak
$backUrl = $studentId
    ? base_url('parent/child/' . $studentId . '/violations')
    : base_url('parent/children');

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
    $t = substr((string) $timeRaw, 0, 5);
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
    $tmp = json_decode((string) $violation['evidence'], true);
    if (is_array($tmp)) {
        $evidenceFiles = $tmp;
    } else {
        $evidenceFiles = [(string) $violation['evidence']];
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
    $pts = strtotime((string) $parentNotifiedAt);
    $parentAtLabel = $pts ? date('d M Y H:i', $pts) : (string) $parentNotifiedAt;
}
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h4 class="mb-0">Detail Pelanggaran Anak</h4>
        <div class="mt-2">
          <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-light">
            <i class="bx bx-arrow-back me-1"></i> Kembali
          </a>
        </div>
      </div>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item">
            <?php if ($studentId): ?>
              <a href="<?= route_to('parent.children.violations', $studentId) ?>">Kasus & Pelanggaran</a>
            <?php else: ?>
              <a href="<?= base_url('parent/children') ?>">Kasus & Pelanggaran</a>
            <?php endif; ?>
          </li>
          <li class="breadcrumb-item active">Detail Kasus & Pelanggaran</li>
        </ol>
      </div>
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

    <?php if (!$studentId || !$violationId): ?>
      <div class="alert alert-warning">
        Data detail belum lengkap (studentId/violationId tidak ditemukan). Beberapa tombol mungkin tidak tersedia.
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
                <span class="badge <?= esc($severityBadge) ?> me-1">
                  <?= esc($violation['severity_level'] ?? 'Tidak diketahui') ?>
                </span>
                <span class="badge <?= esc($statusBadge) ?>">
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
                        <a href="<?= esc($url) ?>" target="_blank" rel="noopener">
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
                  <?= (int) $points ?> poin
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

              <!--<dt class="col-sm-3">Notifikasi Orang Tua</dt>
              <dd class="col-sm-9">
                <span class="badge <?= esc($parentBadgeClass) ?>">
                  <?= esc($parentLabel) ?>
                </span>
                <?php if ($parentAtLabel): ?>
                  <span class="text-muted ms-2">
                    (<?= esc($parentAtLabel) ?>)
                  </span>
                <?php endif; ?>
              </dd>-->

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
          <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0">Sanksi Terkait</h5>

            <?php
              // Hitung apakah masih ada sanksi yang belum dikonfirmasi orang tua
              $pendingAck = [];
              if (!empty($sanctions)) {
                foreach ($sanctions as $sx) {
                  $rx  = is_array($sx) ? $sx : (array) $sx;
                  $sid = (int) ($rx['id'] ?? 0);
                  if ($sid > 0 && (int) ($rx['parent_acknowledged'] ?? 0) !== 1) {
                    $pendingAck[] = $sid;
                  }
                }
              }
              $pendingAck = array_values(array_unique($pendingAck));
            ?>

            <?php if ($studentId && $violationId && !empty($sanctions) && !empty($pendingAck)): ?>
              <form action="<?= route_to('parent.children.violations.ack', $studentId, $violationId) ?>" method="post" class="ms-auto">
                <?= csrf_field() ?>
                <button
                  type="submit"
                  class="btn btn-sm btn-success"
                  onclick="return confirm('Tandai bahwa Anda sudah mengetahui semua sanksi pada kasus ini?')"
                >
                  <i class="bx bx-check-circle me-1"></i> Saya sudah mengetahui sanksi
                </button>
              </form>
            <?php elseif (!empty($sanctions) && empty($pendingAck)): ?>
              <span class="badge bg-success ms-auto">
                <i class="bx bx-check-circle me-1"></i> Sudah dikonfirmasi
              </span>
            <?php endif; ?>
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
                      <th>Konfirmasi Orang Tua</th>
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

                        $dueLabel = (!empty($dueDate) && strpos((string) $dueDate, '0000-00-00') !== 0)
                            ? (string) $dueDate
                            : null;

                        $compLabel = (!empty($completed) && strpos((string) $completed, '0000-00-00') !== 0)
                            ? (string) $completed
                            : null;

                        // Konfirmasi orang tua
                        $ack        = (int) ($row['parent_acknowledged'] ?? 0);
                        $ackAtRaw   = $row['parent_acknowledged_at'] ?? null;
                        $ackAtLabel = '';
                        if (!empty($ackAtRaw)) {
                            $ats = strtotime((string) $ackAtRaw);
                            $ackAtLabel = $ats ? date('d M Y H:i', $ats) : (string) $ackAtRaw;
                        }
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
                          <span class="badge <?= esc($sBadge) ?>">
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
                        <td>
                          <?php if ($ack): ?>
                            <span class="badge bg-success">Sudah</span>
                            <?php if ($ackAtLabel): ?>
                              <div class="text-muted small"><?= esc($ackAtLabel) ?></div>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="badge bg-secondary">Belum</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <p class="text-muted mt-3 mb-0 small">
              Informasi sanksi ini bersifat ringkasan. Untuk penjelasan lebih lanjut,
              orang tua dapat menghubungi Wali Kelas atau Guru BK.
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
                <?php
                  $name = (string) ($student['full_name'] ?? 'A');
                  $initial = strtoupper(substr($name, 0, 1));
                ?>
                <?= esc($initial) ?>
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
              <?= (int) $points ?> poin
            </p>
          </div>
        </div>

        <div class="alert alert-info small mt-3">
          Halaman ini menampilkan pelanggaran yang tercatat untuk anak ini
          sesuai kebijakan akses akun Orang Tua.
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
