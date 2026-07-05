<?php
/**
 * View daftar layanan BK (seragam ala Manajemen Siswa).
 * Fitur: Bimbingan, Konseling, Kolaborasi Orang Tua, Kunjungan Rumah, Konferensi Kasus.
 * Fase 4: kartu statistik + kartu Filter/Saring + DataTables (paginasi di view) +
 * tombol aksi berupa ikon (Detail/Edit/Hapus) + teks hitam pekat.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$rows = is_array($rows ?? null) ? $rows : [];
$filters = is_array($filters ?? null) ? $filters : [];
$options = is_array($options ?? null) ? $options : [];
$routePrefix = (string) ($routePrefix ?? '');
$title = (string) ($title ?? ($meta['title'] ?? 'Layanan BK'));
$canManage = ! empty($canManage);
$me = (int) (session('user_id') ?? 0);

// Statistik ringkas dari data yang sudah disaring sesuai hak akses.
$stat = ['total' => count($rows), 'jadwal' => 0, 'selesai' => 0, 'tindak' => 0];
foreach ($rows as $r) {
    $s = $r['status'] ?? '';
    if (in_array($s, ['Dijadwalkan', 'Berlangsung'], true)) $stat['jadwal']++;
    if ($s === 'Selesai') $stat['selesai']++;
    if ($s === 'Perlu Tindak Lanjut') $stat['tindak']++;
}

$statusColors = [
    'Draft' => 'secondary', 'Dijadwalkan' => 'warning', 'Berlangsung' => 'info',
    'Selesai' => 'success', 'Dibatalkan' => 'danger', 'Perlu Tindak Lanjut' => 'dark',
];
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0 text-dark"><?= esc($title) ?></h4>
        <p class="text-dark mb-0">Tampilan <?= esc($roleLabel ?? 'Pengguna') ?>.</p>
      </div>
      <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Guru BK</a></li>
          <li class="breadcrumb-item active"><?= esc($serviceType) ?></li>
        </ol>
        <?php if ($canManage): ?>
          <a href="<?= site_url($routePrefix . '/create') ?>" class="btn btn-primary">
            <i class="mdi mdi-plus me-1"></i> Tambah
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php foreach (['success' => 'check-circle', 'error' => 'alert-circle', 'warning' => 'alert'] as $type => $icon): ?>
  <?php if (session()->getFlashdata($type)): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
      <i class="mdi mdi-<?= $icon ?> me-2"></i><?= esc(session()->getFlashdata($type)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<!-- Kartu Statistik -->
<div class="row">
  <?php
  $miniCards = [
      ['label' => 'Total', 'value' => $stat['total'], 'bg' => 'bg-primary', 'icon' => 'mdi-clipboard-text'],
      ['label' => 'Terjadwal/Berjalan', 'value' => $stat['jadwal'], 'bg' => 'bg-warning', 'icon' => 'mdi-calendar-clock'],
      ['label' => 'Selesai', 'value' => $stat['selesai'], 'bg' => 'bg-success', 'icon' => 'mdi-check-circle'],
      ['label' => 'Perlu Tindak Lanjut', 'value' => $stat['tindak'], 'bg' => 'bg-dark', 'icon' => 'mdi-alert-decagram'],
  ];
  ?>
  <?php foreach ($miniCards as $mc): ?>
    <div class="col-6 col-md-3">
      <div class="card mini-stats-wid">
        <div class="card-body">
          <div class="d-flex">
            <div class="flex-grow-1">
              <p class="text-dark fw-medium mb-2"><?= esc($mc['label']) ?></p>
              <h4 class="mb-0 text-dark"><?= number_format((int) $mc['value']) ?></h4>
            </div>
            <div class="flex-shrink-0 align-self-center">
              <div class="mini-stat-icon avatar-sm rounded-circle <?= esc($mc['bg'], 'attr') ?>">
                <span class="avatar-title bg-transparent"><i class="mdi <?= esc($mc['icon'], 'attr') ?> font-size-24"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Kartu Filter/Saring -->
<div class="row">
  <div class="col-12">
    <div class="card filter-compact">
      <div class="card-header py-2">
        <h5 class="card-title mb-0 text-dark"><i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data</h5>
      </div>
      <div class="card-body py-3">
        <form method="get" action="<?= site_url($routePrefix) ?>">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label text-dark">Pencarian</label>
              <input type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Judul, siswa, kelas, atau Guru BK">
            </div>
            <div class="col-md-3">
              <label class="form-label text-dark">Status</label>
              <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <?php foreach (array_keys($statusColors) as $status): ?>
                  <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label text-dark">Dari Tanggal</label>
              <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label text-dark">Sampai Tanggal</label>
              <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to'] ?? '') ?>">
            </div>
          </div>
          <div class="row mt-2 g-3">
            <div class="col-md-2">
              <label class="form-label d-block">&nbsp;</label>
              <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify me-1"></i> Filter/Saring</button>
            </div>
            <div class="col-md-2">
              <label class="form-label d-block">&nbsp;</label>
              <a href="<?= site_url($routePrefix) ?>" class="btn btn-secondary w-100"><i class="mdi mdi-refresh me-1"></i> Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Daftar -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title mb-0 text-dark"><i class="<?= esc($meta['icon'] ?? 'mdi mdi-clipboard-text', 'attr') ?> me-2"></i>Daftar <?= esc($title) ?></h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="bkServiceTable" class="table table-hover table-bordered nowrap w-100">
            <thead class="table-light">
              <tr>
                <th style="width:60px;" class="text-center">No</th>
                <th>Judul/Topik/Masalah</th>
                <th>Siswa/Kelas</th>
                <th>Guru BK</th>
                <th style="width:170px;">Jadwal</th>
                <th style="width:120px;">Status</th>
                <th style="width:150px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <?php
                  $statusVal = $row['status'] ?? '-';
                  $statusColor = $statusColors[$statusVal] ?? 'secondary';
                  $isOwner = (int) ($row['created_by'] ?? 0) === $me;
                ?>
                <tr>
                  <td class="text-center"></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= esc($row['title'] ?? '-') ?></div>
                    <small class="text-dark"><?= esc($row['service_type'] ?? $serviceType ?? '-') ?></small>
                  </td>
                  <td class="text-dark">
                    <div><?= esc($row['student_name'] ?? '-') ?></div>
                    <small class="text-dark"><?= esc($row['class_name'] ?? '') ?></small>
                  </td>
                  <td class="text-dark"><?= esc($row['counselor_name'] ?? '-') ?></td>
                  <td class="text-dark">
                    <?= esc($row['scheduled_at'] ?? $row['held_at'] ?? '-') ?>
                    <?php if (! empty($row['duration_minutes'])): ?>
                      <small class="text-dark d-block"><?= esc((string) $row['duration_minutes']) ?> menit</small>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-<?= esc($statusColor, 'attr') ?>"><?= esc($statusVal) ?></span></td>
                  <td class="text-center text-nowrap">
                    <div class="btn-group" role="group">
                      <a href="<?= site_url($routePrefix . '/show/' . (int) $row['id']) ?>" class="btn btn-sm btn-info" title="Lihat detail" data-bs-toggle="tooltip"><i class="mdi mdi-eye"></i></a>
                      <?php if ($canManage): ?>
                        <a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-primary" title="Edit" data-bs-toggle="tooltip"><i class="mdi mdi-pencil"></i></a>
                      <?php endif; ?>
                      <?php if ($canManage && $isOwner): ?>
                        <form method="post" action="<?= site_url($routePrefix . '/delete/' . (int) $row['id']) ?>" class="d-inline" onsubmit="return confirm('Pindahkan data ini ke Tempat Sampah?');">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn btn-sm btn-danger" title="Hapus (ke Tempat Sampah)" data-bs-toggle="tooltip"><i class="mdi mdi-delete"></i></button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (! $rows): ?>
                <tr>
                  <td colspan="7" class="text-center py-5">
                    <i class="mdi mdi-clipboard-off text-dark" style="font-size:48px;"></i>
                    <p class="text-dark mt-2 mb-0">Belum ada data.</p>
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
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function () {
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) { return new bootstrap.Tooltip(el); });
    <?php if (! empty($rows)): ?>
      var table = $('#bkServiceTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [0, 6] }],
        dom: "rt" +
             "<'row align-items-center mt-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start'p>>" +
             "<'row'<'col-12 mt-2'i>>",
        language: {
          lengthMenu: "Tampilkan _MENU_ data",
          info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
          infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
          infoFiltered: "(disaring dari _MAX_ total data)",
          zeroRecords: "Tidak ada data yang sesuai",
          emptyTable: "Tidak ada data tersedia",
          paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
        }
      });
      function renumber() {
        var info = table.page.info();
        table.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.innerHTML = info.start + i + 1; });
      }
      table.on('order.dt draw.dt', renumber);
      renumber();
    <?php endif; ?>
  });
</script>
<?= $this->endSection() ?>
