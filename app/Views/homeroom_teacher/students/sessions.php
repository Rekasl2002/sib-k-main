<!-- app/Views/homeroom_teacher/students/sessions.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helpers kecil agar view tahan banting untuk array/objek & styling status
if (!function_exists('rowa')) {
  function rowa($r): array { return is_array($r) ? $r : (is_object($r) ? (array)$r : []); }
}
if (!function_exists('v')) {
  function v($r, $k, $d='') { $a = rowa($r); return esc($a[$k] ?? $d); }
}
if (!function_exists('badgeClass')) {
  function badgeClass($status) {
    $s = strtolower((string)$status);
    return match (true) {
      str_contains($s,'dijadwalkan') => 'bg-primary',
      str_contains($s,'selesai')     => 'bg-success',
      str_contains($s,'batal')       => 'bg-danger',
      str_contains($s,'hadir')       => 'bg-info',
      default                        => 'bg-secondary',
    };
  }
}
if (!function_exists('qs_build')) {
  /**
   * Build querystring dengan aman (tanpa null/empty yg tidak perlu)
   */
  function qs_build(array $params): string {
    $clean = [];
    foreach ($params as $k => $v) {
      if ($v === null) continue;
      if (is_string($v) && trim($v) === '') continue;
      if (is_int($v) && $v <= 0) continue;
      $clean[$k] = $v;
    }
    $q = http_build_query($clean);
    return $q ? ('?' . $q) : '';
  }
}

// Normalisasi filter
$filters = $filters ?? [];
$statusFilter = $filters['status']  ?? '';
$rangeFilter  = $filters['range']   ?? 'upcoming';
$qFilter      = $filters['q']       ?? '';
$perPage      = $filters['perPage'] ?? 10;

// Tanggal hari ini (bisa di-overwrite dari controller)
$today = isset($today) ? $today : date('Y-m-d');

// Normalisasi data sesi
$all = [];
if (!empty($sessions) && is_array($sessions)) {
  foreach ($sessions as $s) { $all[] = rowa($s); }
}

// Mode tampilan: 1 siswa atau semua siswa
$student     = $student ?? null;
$studentId   = (int)($student['id'] ?? 0);
$isAllMode   = (bool)($isAllMode ?? ($studentId <= 0));
$activeStudentId = (int)($activeStudentId ?? ($studentId > 0 ? $studentId : 0));

// Label judul
$studentName = !$isAllMode
  ? (string)($student['full_name'] ?? 'Siswa')
  : 'SEMUA SISWA BINAAN';

// Dropdown data siswa (untuk mode semua/atau pindah siswa cepat)
$studentsList = $studentsList ?? [];
if (!is_array($studentsList)) $studentsList = [];

// Split sesi menjadi upcoming vs history (tetap dipertahankan seperti versi awal)
$upcoming = [];
$history  = [];
foreach ($all as $it) {
  $date  = $it['session_date'] ?? null;
  $stat  = strtolower((string)($it['status'] ?? ''));
  $isFutureOrToday  = $date && $date >= $today;
  $isOngoingStatus  = str_contains($stat,'dijadwalkan') || str_contains($stat,'proses');

  if (($isFutureOrToday && $isOngoingStatus)) {
    $upcoming[] = $it;
  } else {
    $history[] = $it;
  }
}

// Apakah dataset punya info "student_label"? (biasanya dari mode ALL)
$hasStudentLabel = false;
foreach ($all as $it) {
  if (!empty($it['student_label'])) { $hasStudentLabel = true; break; }
}
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <h4 class="mb-0">JADWAL KONSELING <?= $isAllMode ? '' : '— ' ?><?= esc($studentName) ?></h4>
      </div>

      <div class="d-flex gap-2">
        <?php
          // Link "Riwayat Lengkap"
          // - Mode ALL  : /homeroom/sessions?range=past (+ filter yg relevan)
          // - Mode 1    : /homeroom/sessions?student_id=xx&range=past (+ filter yg relevan)
          $pastParams = [
            'student_id' => $isAllMode ? null : $studentId,
            'range'      => 'past',
            'status'     => $statusFilter ?: null,
            'q'          => $qFilter ?: null,
            'perPage'    => (int)$perPage,
          ];
          $pastUrl = base_url('homeroom/sessions') . qs_build($pastParams);
        ?>
        <a class="btn btn-light" href="<?= $pastUrl ?>">Riwayat Lengkap</a>
      </div>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Sesi Konseling</li>
        </ol>
      </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Filter (tambahan) -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="get" action="<?= base_url('homeroom/sessions') ?>" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Siswa</label>
            <select name="student_id" class="form-select" onchange="this.form.submit()">
              <option value="">Semua Siswa Binaan</option>
              <?php foreach ($studentsList as $st): ?>
                <?php $st = rowa($st); $sid = (int)($st['id'] ?? 0); ?>
                <option value="<?= $sid ?>" <?= ($activeStudentId === $sid && !$isAllMode) ? 'selected' : '' ?>>
                  <?= esc($st['full_name'] ?? '-') ?>
                  <?= !empty($st['class_name']) ? ' • ' . esc($st['class_name']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
              <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Semua</option>
              <?php foreach (['Dijadwalkan','Selesai','Dibatalkan','Tidak Hadir'] as $opt): ?>
                <option value="<?= esc($opt) ?>" <?= $statusFilter === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Pencarian</label>
            <input type="text" name="q" value="<?= esc($qFilter) ?>" class="form-control" placeholder="Cari topik/lokasi...">
          </div>

          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
            <a class="btn btn-light w-100" href="<?= base_url('homeroom/sessions') ?>">Reset</a>
          </div>

          <input type="hidden" name="range" value="<?= esc($rangeFilter) ?>">
          <input type="hidden" name="perPage" value="<?= esc((string)$perPage) ?>">
        </form>
      </div>
    </div>

    <!-- Jadwal Mendatang / Sedang Berlangsung -->
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0">Jadwal Mendatang & Sedang Berlangsung</h5>
        </div>

        <?php if (empty($upcoming)): ?>
          <p class="text-muted mb-0">
            Belum ada jadwal konseling mendatang atau sedang berlangsung<?= $isAllMode ? '' : ' untuk siswa ini' ?>.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
              <thead class="table-light">
                <tr>
                  <th>Tanggal</th>
                  <th>Waktu</th>
                  <?php if ($isAllMode || $hasStudentLabel): ?>
                    <th>Siswa</th>
                  <?php endif; ?>
                  <th>Jenis</th>
                  <th>Topik</th>
                  <th>Lokasi</th>
                  <th>Status</th>
                  <th style="width: 110px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($upcoming as $s): ?>
                <?php
                  $id   = (int)($s['id'] ?? 0);
                  $time = trim((string)($s['session_time'] ?? ''));
                  // Jika format HH:MM:SS, ambil HH:MM saja
                  $timeLabel = ($time !== '' && strlen($time) >= 5) ? substr($time, 0, 5) : ($time !== '' ? $time : '-');

                  // Untuk link detail:
                  // - Mode 1 siswa: pakai $studentId
                  // - Mode ALL    : pakai context_student_id dari row (fallback aman)
                  $ctxStudentId = $isAllMode
                    ? (int)($s['context_student_id'] ?? 0)
                    : $studentId;

                  // Label siswa (mode ALL) bila tersedia
                  $studentLabel = (string)($s['student_label'] ?? '');
                ?>
                <tr>
                  <td><?= v($s,'session_date','-') ?></td>
                  <td><?= esc($timeLabel) ?></td>

                  <?php if ($isAllMode || $hasStudentLabel): ?>
                    <td><?= $studentLabel !== '' ? esc($studentLabel) : '-' ?></td>
                  <?php endif; ?>

                  <td><?= v($s,'session_type','-') ?></td>
                  <td><?= v($s,'topic','-') ?></td>
                  <td><?= v($s,'location','-') ?: '-' ?></td>
                  <td>
                    <span class="badge <?= badgeClass($s['status'] ?? '') ?>">
                      <?= v($s,'status','-') ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($ctxStudentId > 0 && $id > 0): ?>
                      <div class="btn-group" role="group">
                        <a href="<?= route_to('homeroom.students.sessions.detail', $ctxStudentId, $id) ?>"
                           class="btn btn-sm btn-outline-primary">
                          Detail
                        </a>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Ringkasan Riwayat Singkat -->
    <div class="card mt-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0">Riwayat Singkat Sesi Sebelumnya</h5>
        </div>

        <?php if (empty($history)): ?>
          <p class="text-muted mb-0">
            Belum ada riwayat sesi konseling<?= $isAllMode ? '' : ' untuk siswa ini' ?>.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Tanggal</th>
                  <?php if ($isAllMode || $hasStudentLabel): ?>
                    <th>Siswa</th>
                  <?php endif; ?>
                  <th>Jenis</th>
                  <th>Topik</th>
                  <th>Status</th>
                  <th style="width: 110px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($history as $s): ?>
                <?php
                  $id = (int)($s['id'] ?? 0);

                  $ctxStudentId = $isAllMode
                    ? (int)($s['context_student_id'] ?? 0)
                    : $studentId;

                  $studentLabel = (string)($s['student_label'] ?? '');
                ?>
                <tr>
                  <td><?= v($s,'session_date','-') ?></td>

                  <?php if ($isAllMode || $hasStudentLabel): ?>
                    <td><?= $studentLabel !== '' ? esc($studentLabel) : '-' ?></td>
                  <?php endif; ?>

                  <td><?= v($s,'session_type','-') ?></td>
                  <td><?= v($s,'topic','-') ?></td>
                  <td>
                    <span class="badge <?= badgeClass($s['status'] ?? '') ?>">
                      <?= v($s,'status','-') ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($ctxStudentId > 0 && $id > 0): ?>
                      <a href="<?= route_to('homeroom.students.sessions.detail', $ctxStudentId, $id) ?>"
                         class="btn btn-sm btn-outline-secondary">
                        Detail
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
