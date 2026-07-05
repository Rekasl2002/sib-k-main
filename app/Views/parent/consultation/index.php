<?php
/**
 * View daftar Konsultasi & Pengaduan (seragam semua peran, ala Manajemen Siswa).
 * Memakai kartu statistik + DataTables (paginasi di view) + kartu Filter/Saring.
 * Data dari BaseConsultationController: $rows, $stats, $filters, $routePrefix,
 * $roleLabel, $canSubmit, $canReview.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$rows = is_array($rows ?? null) ? $rows : [];
$stats = is_array($stats ?? null) ? $stats : [];
$filters = is_array($filters ?? null) ? $filters : [];
$routePrefix = (string) ($routePrefix ?? '');
$me = (int) (session('user_id') ?? 0);

$statusColors = [
    'Diajukan' => 'secondary', 'Ditinjau' => 'info', 'Diterima' => 'primary',
    'Dijadwalkan' => 'warning', 'Selesai' => 'success', 'Ditolak' => 'danger', 'Diarsipkan' => 'dark',
];
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0 text-dark">Konsultasi & Pengaduan</h4>
        <p class="text-dark mb-0">Tampilan <?= esc($roleLabel ?? 'Pengguna') ?>.</p>
      </div>
      <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Orang Tua</a></li>
          <li class="breadcrumb-item active">Konsultasi &amp; Pengaduan</li>
        </ol>
        <?php if (! empty($canSubmit) || ! empty($canReview)): ?>
          <a href="<?= site_url($routePrefix . '/create') ?>" class="btn btn-primary">
            <i class="mdi mdi-plus me-1"></i> Ajukan
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
      ['label' => 'Total Laporan', 'value' => $stats['total'] ?? 0, 'bg' => 'bg-primary', 'icon' => 'mdi-clipboard-text'],
      ['label' => 'Baru/Diajukan', 'value' => $stats['baru'] ?? 0, 'bg' => 'bg-secondary', 'icon' => 'mdi-inbox-arrow-down'],
      ['label' => 'Sedang Diproses', 'value' => $stats['proses'] ?? 0, 'bg' => 'bg-info', 'icon' => 'mdi-progress-clock'],
      ['label' => 'Selesai', 'value' => $stats['selesai'] ?? 0, 'bg' => 'bg-success', 'icon' => 'mdi-check-circle'],
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
              <input type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Judul, deskripsi, atau nama siswa">
            </div>
            <div class="col-md-3">
              <label class="form-label text-dark">Jenis Laporan</label>
              <select name="request_type" class="form-select">
                <option value="">Semua Jenis</option>
                <?php foreach (['Konsultasi','Pengaduan','Permintaan Konseling','Laporan Orang Tua','Laporan Wali Kelas','Lainnya/Tidak Bisa Menentukan'] as $type): ?>
                  <option value="<?= esc($type) ?>" <?= ($filters['request_type'] ?? '') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
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
              <label class="form-label text-dark">Prioritas</label>
              <select name="priority" class="form-select">
                <option value="">Semua</option>
                <?php foreach (['Rendah','Sedang','Tinggi','Mendesak'] as $priority): ?>
                  <option value="<?= esc($priority) ?>" <?= ($filters['priority'] ?? '') === $priority ? 'selected' : '' ?>><?= esc($priority) ?></option>
                <?php endforeach; ?>
              </select>
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
        <h4 class="card-title mb-0"><i class="mdi mdi-clipboard-text me-2"></i>Daftar Konsultasi & Pengaduan</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="consultationTable" class="table table-hover table-bordered nowrap w-100">
            <thead class="table-light">
              <tr>
                <th style="width:60px;" class="text-center">No</th>
                <th>Judul/Topik/Masalah</th>
                <th>Pelapor</th>
                <th>Siswa Terkait</th>
                <th style="width:160px;">Jenis</th>
                <th style="width:120px;">Status</th>
                <th style="width:140px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <?php
                  $isOwner = (int) ($row['reporter_user_id'] ?? 0) === $me;
                  $editable = in_array($row['status'] ?? '', ['Diajukan', 'Ditinjau'], true);
                  $statusVal = $row['status'] ?? '-';
                  $statusColor = $statusColors[$statusVal] ?? 'secondary';
                  $subjectCount = (int) ($row['subject_count'] ?? 0);
                  $subjectName = $row['student_name'] ?? $row['subject_other_name'] ?? '-';
                ?>
                <tr>
                  <td class="text-center"></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= esc($row['title'] ?? '-') ?></div>
                    <small class="text-dark"><?= esc($row['created_at'] ?? '') ?></small>
                  </td>
                  <td class="text-dark"><?= esc($row['reporter_name'] ?? '-') ?></td>
                  <td class="text-dark">
                    <?= esc($subjectName) ?>
                    <?php if ($subjectCount > 1): ?>
                      <span class="badge bg-light text-dark border">+<?= $subjectCount - 1 ?> lainnya</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-light text-dark border"><?= esc($row['request_type'] ?? '-') ?></span></td>
                  <td><span class="badge bg-<?= esc($statusColor, 'attr') ?>"><?= esc($statusVal) ?></span></td>
                  <td class="text-center text-nowrap">
                    <div class="btn-group" role="group">
                      <a href="<?= site_url($routePrefix . '/show/' . (int) $row['id']) ?>" class="btn btn-sm btn-info" title="Lihat detail" data-bs-toggle="tooltip"><i class="mdi mdi-eye"></i></a>
                      <?php if (! empty($canSubmit) && $isOwner && $editable): ?>
                        <a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-primary" title="Edit" data-bs-toggle="tooltip"><i class="mdi mdi-pencil"></i></a>
                      <?php endif; ?>
                      <?php if ($isOwner): ?>
                        <form method="post" action="<?= site_url($routePrefix . '/delete/' . (int) $row['id']) ?>" class="d-inline" onsubmit="return confirm('Pindahkan laporan ini ke Tempat Sampah?');">
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
      var table = $('#consultationTable').DataTable({
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
