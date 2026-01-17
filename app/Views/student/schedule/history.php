<!-- app/Views/student/schedule/history.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helpers kecil agar view tahan banting & styling status
if (!function_exists('rowa')) {
  function rowa($r): array {
    return is_array($r)
      ? $r
      : (is_object($r) ? (array)$r : []);
  }
}
if (!function_exists('v')) {
  function v($r, $k, $d = '') {
    $a = rowa($r);
    return esc($a[$k] ?? $d);
  }
}
if (!function_exists('badgeClass')) {
  function badgeClass($status) {
    $s = strtolower((string)$status);
    if (str_contains($s, 'selesai'))     return 'bg-success';
    if (str_contains($s, 'dijadwal'))    return 'bg-info';
    if (str_contains($s, 'batal'))       return 'bg-danger';
    if (str_contains($s, 'tidak hadir')) return 'bg-warning';
    return 'bg-secondary';
  }
}

/** @var array $history */
$history = $history ?? [];
$total   = is_array($history) ? count($history) : 0;
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">RIWAYAT SESI KONSELING</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item">
            <a href="<?= base_url('student/dashboard') ?>">Dashboard</a>
          </li>
          <li class="breadcrumb-item">
            <a href="<?= base_url('student/schedule') ?>">Sesi Konseling</a>
          </li>
          <li class="breadcrumb-item active">Riwayat Sesi Konseling</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title mb-1">Riwayat Jadwal Konseling</h5>
            <p class="text-muted mb-0">
              Menampilkan semua sesi konseling yang sudah lewat.
            </p>
          </div>
          <div class="text-end">
            <a href="<?= base_url('student/schedule') ?>"
               class="btn btn-sm btn-secondary">
              <i class="mdi mdi-arrow-left"></i>
              Kembali ke Jadwal
            </a>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="badge bg-light text-dark">
            Total sesi: <strong><?= $total ?></strong>
          </span>
        </div>

        <div class="row mb-3">
          <div class="col-md-3 col-sm-6 mb-2">
            <label class="form-label">Status</label>
            <select class="form-select form-select-sm" id="statusFilter">
              <option value="">Semua</option>
              <option value="Dijadwalkan">Dijadwalkan</option>
              <option value="Selesai">Selesai</option>
              <option value="Dibatalkan">Dibatalkan</option>
              <option value="Tidak Hadir">Tidak Hadir</option>
            </select>
          </div>
          <div class="col-md-4 col-sm-6 mb-2">
            <label class="form-label">Cari Topik / Lokasi</label>
            <input type="text"
                   class="form-control form-control-sm"
                   id="searchTopic"
                   placeholder="Ketik kata kunci...">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th style="width: 120px;">Tanggal</th>
                <th style="width: 90px;">Waktu</th>
                <th style="width: 110px;">Jenis</th>
                <th>Topik</th>
                <th style="width: 160px;">Lokasi</th>
                <th style="width: 120px;">Status</th>
                <th style="width: 110px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($history) && is_array($history)): ?>
                <?php foreach ($history as $row): ?>
                  <?php
                    $date      = v($row, 'session_date', '-');
                    $time      = v($row, 'session_time', '-');
                    $type      = v($row, 'session_type', '-');
                    $topic     = v($row, 'topic', '-');
                    $location  = v($row, 'location', '-');
                    $status    = trim((string)($row['status'] ?? ''));
                    $sessionId = (int)($row['id'] ?? 0);
                  ?>
                  <tr
                    data-status="<?= esc($status) ?>"
                    data-topic="<?= esc(strtolower($topic . ' ' . $location)) ?>"
                  >
                    <td><?= esc($date) ?></td>
                    <td><?= esc(substr($time, 0, 5)) ?></td>
                    <td><?= esc($type) ?></td>
                    <td><?= esc($topic) ?></td>
                    <td><?= esc($location) ?></td>
                    <td>
                      <span class="badge <?= badgeClass($status) ?>">
                        <?= esc($status ?: '-') ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= route_to('student.schedule.detail', $sessionId) ?>"
                         class="btn btn-sm btn-outline-primary">
                        Detail
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    Belum ada riwayat sesi konseling yang dapat ditampilkan.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const statusSelect = document.getElementById('statusFilter');
    const searchInput  = document.getElementById('searchTopic');
    if (!statusSelect || !searchInput) return;

    function applyFilter() {
      const statusVal = statusSelect.value;
      const searchVal = (searchInput.value || '').toLowerCase();

      document
        .querySelectorAll('table tbody tr')
        .forEach(function (tr) {
          const rowStatus = tr.getAttribute('data-status') || '';
          const rowTopic  = tr.getAttribute('data-topic')  || '';

          let visible = true;
          if (statusVal && rowStatus !== statusVal) visible = false;
          if (searchVal && !rowTopic.includes(searchVal)) visible = false;

          tr.style.display = visible ? '' : 'none';
        });
    }

    statusSelect.addEventListener('change', applyFilter);
    searchInput.addEventListener('input', applyFilter);
  })();
</script>

<?= $this->endSection() ?>
