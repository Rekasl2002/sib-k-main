<!-- app/Views/homeroom_teacher/students/sessions_history.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helpers kecil agar view tahan banting & styling status
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

// Normalisasi data history
$rows = [];
if (!empty($history) && is_array($history)) {
  foreach ($history as $h) { $rows[] = rowa($h); }
}

// Mode tampilan: 1 siswa atau semua siswa
$student     = $student ?? null;
$studentId   = (int)($student['id'] ?? 0);
$isAllMode   = (bool)($isAllMode ?? ($studentId <= 0));
$activeStudentId = (int)($activeStudentId ?? ($studentId > 0 ? $studentId : 0));

$studentName = !$isAllMode
  ? (string)($student['full_name'] ?? 'Siswa')
  : 'Semua Siswa Perwalian';

// Dropdown data siswa
$studentsList = $studentsList ?? [];
if (!is_array($studentsList)) $studentsList = [];

// Apakah dataset punya info student_label? (biasanya dari mode ALL)
$hasStudentLabel = false;
foreach ($rows as $it) {
  if (!empty($it['student_label'])) { $hasStudentLabel = true; break; }
}

// Link kembali:
// - Mode ALL : /homeroom/sessions
// - Mode 1   : /homeroom/students/{id}/sessions (route lama), fallback ke /homeroom/sessions?student_id=id
$backUrl = base_url('homeroom/sessions');
if (!$isAllMode && $studentId > 0) {
  // route_to mungkin tidak ada di semua proyek, jadi siapkan fallback
  try {
    $backUrl = route_to('homeroom.students.sessions', $studentId);
  } catch (\Throwable $e) {
    $backUrl = base_url('homeroom/sessions') . qs_build(['student_id' => $studentId]);
  }
}
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <h4 class="mb-0">RIWAYAT SESI KONSELING</h4>
      </div>

      <a class="btn btn-light" href="<?= $backUrl ?>">
        Kembali ke Jadwal
      </a>
      <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/sessions') ?>">Sesi Konseling</a></li>          
          <li class="breadcrumb-item active">Riwayat Sesi Konseling</li>
        </ol>
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

    <div class="card">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="card-title mb-0">Riwayat Sesi Konseling</h5>
            <small class="text-muted">
              <?= $isAllMode ? 'Menampilkan riwayat untuk semua siswa perwalian.' : ('Siswa: ' . esc($studentName)) ?>
            </small>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <!-- Dropdown siswa / semua siswa -->
            <div>
              <label for="studentPicker" class="form-label mb-0 small">Siswa</label>
              <select id="studentPicker" class="form-select form-select-sm"
                      onchange="(function(sel){ window.location = sel.value; })(this)">
                <?php
                  // URL basis untuk riwayat
                  $baseHistoryUrl = base_url('homeroom/sessions') . qs_build(['range' => 'past']);
                  $allUrl = base_url('homeroom/sessions') . qs_build(['range' => 'past']);
                ?>
                <option value="<?= $allUrl ?>" <?= $isAllMode ? 'selected' : '' ?>>Semua Siswa Perwalian</option>

                <?php foreach ($studentsList as $st): ?>
                  <?php
                    $st = rowa($st);
                    $sid = (int)($st['id'] ?? 0);
                    $label = (string)($st['full_name'] ?? '-');
                    if (!empty($st['class_name'])) $label .= ' • ' . $st['class_name'];

                    $url = base_url('homeroom/sessions') . qs_build([
                      'student_id' => $sid,
                      'range'      => 'past',
                    ]);
                  ?>
                  <option value="<?= esc($url) ?>" <?= (!$isAllMode && $activeStudentId === $sid) ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label for="statusFilter" class="form-label mb-0 small">Filter Status</label>
              <select id="statusFilter" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="dijadwalkan">Dijadwalkan</option>
                <option value="selesai">Selesai</option>
                <option value="batal">Dibatalkan</option>
                <option value="hadir">Tidak Hadir</option>
              </select>
            </div>

            <div>
              <label for="searchInput" class="form-label mb-0 small">Cari Topik/Lokasi</label>
              <input type="text"
                     id="searchInput"
                     class="form-control form-control-sm"
                     placeholder="Ketik kata kunci...">
            </div>
          </div>
        </div>

        <?php if (empty($rows)): ?>
          <p class="text-muted mb-0">
            Belum ada riwayat sesi konseling<?= $isAllMode ? '' : ' untuk siswa ini' ?>.
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped align-middle" id="history-table">
              <thead class="table-light">
                <tr>
                  <th style="width:110px;">Tanggal</th>
                  <th style="width:90px;">Waktu</th>

                  <?php if ($isAllMode || $hasStudentLabel): ?>
                    <th style="width:220px;">Siswa</th>
                  <?php endif; ?>

                  <th style="width:120px;">Jenis</th>
                  <th>Topik</th>
                  <th>Lokasi</th>
                  <th style="width:120px;">Status</th>
                  <th style="width:110px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($rows as $row): ?>
                <?php
                  $statusText  = (string)($row['status'] ?? '');
                  $statusLower = strtolower($statusText);

                  $topicText   = (string)($row['topic'] ?? '');
                  $locText     = (string)($row['location'] ?? '');

                  $time        = trim((string)($row['session_time'] ?? ''));
                  $timeLabel   = ($time !== '' && strlen($time) >= 5) ? substr($time, 0, 5) : ($time !== '' ? $time : '-');

                  $id = (int)($row['id'] ?? 0);

                  // Untuk link detail:
                  // - Mode 1 siswa: pakai $studentId
                  // - Mode ALL    : pakai context_student_id dari row (fallback aman)
                  $ctxStudentId = $isAllMode
                    ? (int)($row['context_student_id'] ?? 0)
                    : $studentId;

                  $studentLabel = (string)($row['student_label'] ?? '');
                ?>
                <tr data-status="<?= esc($statusLower) ?>">
                  <td><?= v($row,'session_date','-') ?></td>
                  <td><?= esc($timeLabel) ?></td>

                  <?php if ($isAllMode || $hasStudentLabel): ?>
                    <td><?= $studentLabel !== '' ? esc($studentLabel) : '-' ?></td>
                  <?php endif; ?>

                  <td><?= v($row,'session_type','-') ?></td>
                  <td data-col="topic"><?= esc($topicText ?: '-') ?></td>
                  <td data-col="location"><?= esc($locText ?: '-') ?></td>
                  <td>
                    <span class="badge <?= badgeClass($statusText) ?>">
                      <?= esc($statusText ?: '-') ?>
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

<script>
  (function () {
    const table       = document.getElementById('history-table');
    if (!table) return;

    const statusSelect = document.getElementById('statusFilter');
    const searchInput  = document.getElementById('searchInput');
    const rows         = Array.from(table.querySelectorAll('tbody tr'));

    function applyFilter() {
      const statusVal = (statusSelect.value || '').toLowerCase();
      const searchVal = (searchInput.value || '').toLowerCase();

      rows.forEach(function (tr) {
        const rowStatus = (tr.getAttribute('data-status') || '').toLowerCase();

        const topicCell = tr.querySelector('[data-col="topic"]');
        const locCell   = tr.querySelector('[data-col="location"]');

        const topicText = topicCell ? topicCell.textContent.toLowerCase() : '';
        const locText   = locCell   ? locCell.textContent.toLowerCase()   : '';

        let visible = true;

        if (statusVal && !rowStatus.includes(statusVal)) {
          visible = false;
        }
        if (searchVal && !(topicText.includes(searchVal) || locText.includes(searchVal))) {
          visible = false;
        }

        tr.style.display = visible ? '' : 'none';
      });
    }

    statusSelect && statusSelect.addEventListener('change', applyFilter);
    searchInput  && searchInput.addEventListener('input', applyFilter);
  })();
</script>

<?= $this->endSection() ?>
