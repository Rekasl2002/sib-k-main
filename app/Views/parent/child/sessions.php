<!-- app/Views/parent/child/sessions.php -->
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
      str_contains($s,'dijadwalkan') => 'bg-info',
      str_contains($s,'proses')      => 'bg-primary',
      str_contains($s,'selesai')     => 'bg-success',
      str_contains($s,'batal')       => 'bg-danger',
      str_contains($s,'tidak hadir') => 'bg-warning',
      default                        => 'bg-secondary',
    };
  }
}

$today = isset($today) ? $today : date('Y-m-d');

// Normalisasi & bagi sesi menjadi mendatang/berlangsung vs riwayat
$all = [];
if (!empty($sessions) && is_array($sessions)) {
  foreach ($sessions as $s) { $all[] = rowa($s); }
}

$upcoming = [];
$history  = [];
foreach ($all as $it) {
  $date  = $it['session_date'] ?? null;
  $stat  = strtolower((string)($it['status'] ?? ''));
  $isFutureOrToday = $date && $date >= $today;
  $isOngoingStatus = str_contains($stat,'dijadwalkan') || str_contains($stat,'proses');

  if (($isFutureOrToday && $isOngoingStatus)) {
    $upcoming[] = $it;
  } else {
    $history[] = $it;
  }
}

// Info anak
$studentId   = (int)($student['id'] ?? 0);
$studentName = (string)($student['full_name'] ?? 'Anak');
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3">
      <h4 class="mb-0">Jadwal Konseling — <?= esc($studentName) ?></h4>
      <div class="d-flex gap-2">
        <a class="btn btn-light" href="<?= base_url('parent/child/'.$studentId.'/sessions?range=past') ?>">Riwayat Lengkap</a>
        <!--<a class="btn btn-primary" href="<?= route_to('messages.index') ?>">Ajukan Konseling</a>-->
      </div>
      <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Dashboard</a></li>
              <li class="breadcrumb-item active">Sesi Konseling</li>
            </ol>
    </div>

    <?php if (session('success')): ?>
      <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php elseif (session('error')): ?>
      <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <!-- Mendatang & Berlangsung -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title mb-3">Mendatang & Berlangsung</h5>
        <?php if (!empty($upcoming)): ?>
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Waktu</th>
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
                  if ($time !== '' && strlen($time) >= 5) {
                    $timeLabel = substr($time, 0, 5);
                  } else {
                    $timeLabel = $time !== '' ? $time : '-';
                  }
                ?>
                <tr>
                  <td><?= v($s,'session_date','-') ?></td>
                  <td><?= esc($timeLabel) ?></td>
                  <td><?= v($s,'session_type','-') ?></td>
                  <td><?= v($s,'topic','-') ?></td>
                  <td><?= v($s,'location','-') ?: '-' ?></td>
                  <td>
                    <span class="badge <?= badgeClass($s['status'] ?? '') ?>">
                      <?= v($s,'status','-') ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($id > 0): ?>
                      <a href="<?= route_to('parent.children.sessions.detail', $studentId, $id) ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-eye"></i> Detail
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
        <?php else: ?>
          <p class="text-muted mb-0">Belum ada jadwal yang akan datang.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Riwayat Singkat (pada halaman ini) -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Riwayat</h5>
        <?php if (!empty($history)): ?>
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Waktu</th>
                  <th>Jenis</th>
                  <th>Topik</th>
                  <th>Lokasi</th>
                  <th>Status</th>
                  <th style="width: 110px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($history as $s): ?>
                <?php
                  $id   = (int)($s['id'] ?? 0);
                  $time = trim((string)($s['session_time'] ?? ''));
                  if ($time !== '' && strlen($time) >= 5) {
                    $timeLabel = substr($time, 0, 5);
                  } else {
                    $timeLabel = $time !== '' ? $time : '-';
                  }
                ?>
                <tr>
                  <td><?= v($s,'session_date','-') ?></td>
                  <td><?= esc($timeLabel) ?></td>
                  <td><?= v($s,'session_type','-') ?></td>
                  <td><?= v($s,'topic','-') ?></td>
                  <td><?= v($s,'location','-') ?: '-' ?></td>
                  <td>
                    <span class="badge <?= badgeClass($s['status'] ?? '') ?>">
                      <?= v($s,'status','-') ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($id > 0): ?>
                      <a href="<?= route_to('parent.children.sessions.detail', $studentId, $id) ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-eye"></i> Detail
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
          <div class="text-end">
            <a class="btn btn-sm btn-light" href="<?= base_url('parent/child/'.$studentId.'/sessions?range=past') ?>">
              Lihat Riwayat Lengkap
            </a>
          </div>
        <?php else: ?>
          <p class="text-muted mb-0">Belum ada riwayat jadwal.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
