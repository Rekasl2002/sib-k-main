<?php
/**
 * View daftar Penugasan (Koordinator BK).
 * Perbaikan Kedua (Item #10): tampilan diseragamkan dengan Manajemen Pengguna
 * (page-title-box + breadcrumb, kartu statistik, kartu Filter/Saring, DataTables,
 * tombol aksi IKON: Detail/Edit/Hapus). Petugas & sasaran bisa lebih dari satu.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
helper(['url']);
$rows = is_array($rows ?? null) ? $rows : [];
$filters = is_array($filters ?? null) ? $filters : [];
$routePrefix = (string) ($routePrefix ?? '');
$counselors = is_array($counselors ?? null) ? $counselors : [];

$types = \App\Services\BkAssignmentService::assignmentTypes();
$statusList = \App\Services\BkAssignmentService::statuses();

// Statistik dihitung dari data yang tampil.
$now = date('Y-m-d H:i:s');
$total = count($rows);
$berjalan = 0; $selesai = 0; $terlambat = 0;
foreach ($rows as $r) {
    $st = (string) ($r['status'] ?? '');
    if ($st === 'Selesai') { $selesai++; }
    if (in_array($st, ['Ditugaskan', 'Dibaca', 'Berjalan'], true)) { $berjalan++; }
    $due = (string) ($r['due_at'] ?? '');
    if ($due !== '' && $due < $now && ! in_array($st, ['Selesai', 'Dibatalkan'], true)) { $terlambat++; }
}

$priorityBadge = static function (string $p): string {
    return match ($p) {
        'Mendesak' => 'danger',
        'Tinggi'   => 'warning',
        'Sedang'   => 'info',
        default     => 'secondary',
    };
};
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">Penugasan</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
          <li class="breadcrumb-item active">Penugasan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $flashKey => $flashClass): ?>
  <?php if (session()->getFlashdata($flashKey)): ?>
    <div class="alert alert-<?= esc($flashClass, 'attr') ?> alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata($flashKey)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<!-- Statistik -->
<div class="row">
  <div class="col-md-3 col-6">
    <div class="card mini-stats-wid"><div class="card-body"><div class="d-flex">
      <div class="flex-grow-1"><p class="text-dark fw-medium">Total Tugas</p><h4 class="mb-0"><?= number_format($total) ?></h4></div>
      <div class="flex-shrink-0 align-self-center"><div class="mini-stat-icon avatar-sm rounded-circle bg-primary"><span class="avatar-title"><i class="mdi mdi-clipboard-text font-size-24"></i></span></div></div>
    </div></div></div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mini-stats-wid"><div class="card-body"><div class="d-flex">
      <div class="flex-grow-1"><p class="text-dark fw-medium">Sedang Berjalan</p><h4 class="mb-0"><?= number_format($berjalan) ?></h4></div>
      <div class="flex-shrink-0 align-self-center"><div class="mini-stat-icon avatar-sm rounded-circle bg-info"><span class="avatar-title"><i class="mdi mdi-progress-clock font-size-24"></i></span></div></div>
    </div></div></div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mini-stats-wid"><div class="card-body"><div class="d-flex">
      <div class="flex-grow-1"><p class="text-dark fw-medium">Selesai</p><h4 class="mb-0"><?= number_format($selesai) ?></h4></div>
      <div class="flex-shrink-0 align-self-center"><div class="mini-stat-icon avatar-sm rounded-circle bg-success"><span class="avatar-title"><i class="mdi mdi-check-circle font-size-24"></i></span></div></div>
    </div></div></div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mini-stats-wid"><div class="card-body"><div class="d-flex">
      <div class="flex-grow-1"><p class="text-dark fw-medium">Terlambat</p><h4 class="mb-0"><?= number_format($terlambat) ?></h4></div>
      <div class="flex-shrink-0 align-self-center"><div class="mini-stat-icon avatar-sm rounded-circle bg-warning"><span class="avatar-title"><i class="mdi mdi-alert font-size-24"></i></span></div></div>
    </div></div></div>
  </div>
</div>

<!-- Filter/Saring -->
<div class="row">
  <div class="col-12">
    <div class="card filter-compact">
      <div class="card-header py-2"><h5 class="card-title mb-0 text-dark"><i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data</h5></div>
      <div class="card-body py-3">
        <form action="<?= site_url($routePrefix) ?>" method="get">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Jenis Tugas</label>
              <select name="assignment_type" class="form-select">
                <option value="">Semua Jenis</option>
                <?php foreach ($types as $type): ?>
                  <option value="<?= esc($type) ?>" <?= ($filters['assignment_type'] ?? '') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <?php foreach ($statusList as $status): ?>
                  <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Pencarian</label>
              <input type="text" name="q" class="form-control" placeholder="Judul tugas, instruksi, atau Guru BK..." value="<?= esc($filters['q'] ?? '') ?>">
            </div>
          </div>
          <div class="row mt-2 g-3">
            <div class="col-md-2"><label class="form-label d-block">&nbsp;</label><button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify me-1"></i> Filter/Saring</button></div>
            <div class="col-md-2"><label class="form-label d-block">&nbsp;</label><a href="<?= site_url($routePrefix) ?>" class="btn btn-secondary w-100"><i class="mdi mdi-refresh me-1"></i> Reset</a></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Tabel -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Daftar Tugas</h4>
        <div class="text-end">
          <a href="<?= site_url('koordinator/users') ?>" class="btn btn-outline-primary"><i class="mdi mdi-account-cog me-1"></i> Kelola Guru BK</a>
          <a href="<?= site_url($routePrefix . '/create') ?>" class="btn btn-success ms-1"><i class="mdi mdi-plus me-1"></i> Buat Tugas</a>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="assignmentsTable" class="table table-hover table-bordered nowrap w-100">
            <thead class="table-light">
              <tr>
                <th style="width:60px;" class="text-center">No</th>
                <th>Tugas</th>
                <th>Guru BK</th>
                <th>Kelas/Siswa</th>
                <th style="width:150px;">Batas Waktu</th>
                <th style="width:110px;" class="text-center">Prioritas</th>
                <th style="width:110px;" class="text-center">Status</th>
                <th style="width:140px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <td class="text-center"></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= esc($row['title'] ?? '-') ?></div>
                    <small class="text-dark"><?= esc($row['assignment_type'] ?? '-') ?><?= ! empty($row['assignment_type_other']) ? ': ' . esc($row['assignment_type_other']) : '' ?></small>
                  </td>
                  <td class="text-dark"><?= esc($row['assignee_names'] ?? '') !== '' ? esc($row['assignee_names']) : '<span class="text-dark">-</span>' ?></td>
                  <td class="text-dark"><?= esc($row['target_names'] ?? '') !== '' ? esc($row['target_names']) : '<span class="text-dark">-</span>' ?></td>
                  <td><small><?= ! empty($row['due_at']) ? esc(date('d/m/Y H:i', strtotime((string) $row['due_at']))) : '-' ?></small></td>
                  <td class="text-center"><span class="badge bg-<?= $priorityBadge((string) ($row['priority'] ?? '')) ?>"><?= esc($row['priority'] ?? '-') ?></span></td>
                  <td class="text-center"><span class="badge bg-light text-dark border"><?= esc($row['status'] ?? '-') ?></span></td>
                  <td class="text-center">
                    <div class="d-inline-flex gap-1 justify-content-center flex-wrap">
                      <a href="<?= site_url($routePrefix . '/show/' . (int) $row['id']) ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Detail"><i class="mdi mdi-eye"></i></a>
                      <a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit"><i class="mdi mdi-pencil"></i></a>
                      <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="<?= (int) $row['id'] ?>" data-name="<?= esc($row['title'] ?? '-', 'attr') ?>" data-bs-toggle="tooltip" title="Hapus"><i class="mdi mdi-delete"></i></button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (! $rows): ?>
                <tr><td colspan="8" class="text-center py-5"><i class="mdi mdi-clipboard-off text-dark" style="font-size:48px;"></i><p class="text-dark mt-2 mb-0">Belum ada tugas.</p></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Ringkasan Guru BK & Kelas Binaan -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 text-dark">Guru BK dan Kelas Binaan</h5>
        <a href="<?= site_url('koordinator/users/create') ?>" class="btn btn-sm btn-primary"><i class="mdi mdi-plus me-1"></i> Tambah Guru BK</a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Kelas Binaan</th><th class="text-center">Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($counselors as $counselor): ?>
                <tr>
                  <td><div class="fw-semibold text-dark"><?= esc($counselor['full_name'] ?? '-') ?></div><small class="text-dark"><?= esc($counselor['username'] ?? '-') ?></small></td>
                  <td class="text-dark"><?= (int) ($counselor['class_count'] ?? 0) > 0 ? esc($counselor['class_names'] ?? '-') : '<span class="text-dark">Belum ada kelas binaan</span>' ?></td>
                  <td class="text-center"><span class="badge bg-<?= (int) ($counselor['is_active'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= (int) ($counselor['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                  <td class="text-end"><a href="<?= site_url('koordinator/users/edit/' . (int) ($counselor['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Kelola"><i class="mdi mdi-account-cog"></i></a></td>
                </tr>
              <?php endforeach; ?>
              <?php if (! $counselors): ?><tr><td colspan="4" class="text-center text-dark py-3">Belum ada akun Guru BK.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="mdi mdi-alert-circle text-danger me-2"></i>Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p>Hapus tugas <strong id="delName"></strong>?</p><p class="text-danger mb-0"><i class="mdi mdi-information me-1"></i>Data akan diarsipkan (soft delete).</p></div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="mdi mdi-close me-1"></i>Batal</button>
      <form id="deleteForm" method="post" style="display:inline;"><?= csrf_field() ?><button type="submit" class="btn btn-danger"><i class="mdi mdi-delete me-1"></i>Hapus</button></form>
    </div>
  </div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function () {
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) { return new bootstrap.Tooltip(el); });

    <?php if ($rows): ?>
    var table = $('#assignmentsTable').DataTable({
      responsive: true, pageLength: 10, order: [[4, 'asc']],
      columnDefs: [{ orderable: false, targets: [0, 7] }],
      dom: "rt<'row align-items-center mt-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start'p>><'row'<'col-12 mt-2'i>>",
      language: {
        lengthMenu: "Tampilkan _MENU_ data", info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data", infoFiltered: "(disaring dari _MAX_ total data)",
        zeroRecords: "Tidak ada data yang sesuai", emptyTable: "Tidak ada data tersedia",
        paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
      }
    });
    function renumber() { var info = table.page.info(); table.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.innerHTML = info.start + i + 1; }); }
    table.on('order.dt draw.dt', renumber); renumber();
    <?php endif; ?>

    $('.btn-delete').on('click', function () {
      $('#delName').text($(this).data('name'));
      $('#deleteForm').attr('action', '<?= site_url($routePrefix . '/delete') ?>/' + $(this).data('id'));
      new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
  });
</script>
<?= $this->endSection() ?>
