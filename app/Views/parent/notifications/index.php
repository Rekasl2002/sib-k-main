<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/<peran>/notifications/index.php
 * Fitur: Notifikasi Internal — Pusat Notifikasi.
 * Tampilan diseragamkan dengan Manajemen Siswa (page-title-box, kartu statistik,
 * kartu Filter/Saring Data, DataTables). Konten IDENTIK untuk ke-6 peran, digerakkan
 * oleh $basePath/$roleLabel/$stats/$items/$categories dari controller per peran.
 *
 * Preferensi notifikasi TIDAK lagi diatur per pengguna — diatur terpusat oleh Admin
 * pada Pengaturan Aplikasi (matriks peran x kategori).
 */
helper(['notification', 'url']);

$basePath   = trim((string)($basePath ?? ''), '/');
$items      = is_array($items ?? null) ? $items : [];
$stats      = is_array($stats ?? null) ? $stats : ['total' => 0, 'unread' => 0, 'read' => 0];
$categories = is_array($categories ?? null) ? $categories : [];
$roleLabel  = (string)($roleLabel ?? 'Pengguna');
?>

<!-- Page Title -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">Notifikasi Internal</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= site_url($basePath . '/dashboard') ?>"><?= esc($roleLabel) ?></a></li>
          <li class="breadcrumb-item active">Notifikasi</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<!-- Flash Messages -->
<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="mdi mdi-check-circle me-2"></i><?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row">
  <?php
  $miniCards = [
    ['label' => 'Total Notifikasi', 'value' => $stats['total']  ?? 0, 'bg' => 'bg-primary', 'icon' => 'mdi-bell'],
    ['label' => 'Belum Dibaca',     'value' => $stats['unread'] ?? 0, 'bg' => 'bg-danger',  'icon' => 'mdi-bell-alert'],
    ['label' => 'Sudah Dibaca',     'value' => $stats['read']   ?? 0, 'bg' => 'bg-success', 'icon' => 'mdi-bell-check'],
  ];
  ?>
  <?php foreach ($miniCards as $mc): ?>
    <div class="col-12 col-md-4">
      <div class="card mini-stats-wid">
        <div class="card-body">
          <div class="d-flex">
            <div class="flex-grow-1">
              <p class="text-dark fw-medium mb-2"><?= esc($mc['label']) ?></p>
              <h4 class="mb-0 text-dark"><?= number_format((int)$mc['value']) ?></h4>
            </div>
            <div class="flex-shrink-0 align-self-center">
              <div class="mini-stat-icon avatar-sm rounded-circle <?= esc($mc['bg'], 'attr') ?>">
                <span class="avatar-title"><i class="mdi <?= esc($mc['icon'], 'attr') ?> font-size-24"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Filter Card -->
<div class="row">
  <div class="col-12">
    <div class="card filter-compact">
      <div class="card-header py-2">
        <h5 class="card-title mb-0 text-dark"><i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data</h5>
      </div>
      <div class="card-body py-3">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select id="statusFilter" class="form-select">
              <option value="">Semua Status</option>
              <option value="unread">Belum Dibaca</option>
              <option value="read">Sudah Dibaca</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Kategori</label>
            <select id="categoryFilter" class="form-select">
              <option value="">Semua Kategori</option>
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= esc($key, 'attr') ?>"><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Pencarian</label>
            <input type="text" id="searchBox" class="form-control" placeholder="Cari judul atau isi notifikasi...">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Notifications Table Card -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0"><i class="mdi mdi-bell-outline me-2"></i>Daftar Notifikasi</h4>
        <div class="text-end">
          <form method="post" action="<?= site_url($basePath . '/notifications/mark-all-read') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">
              <i class="mdi mdi-check-all me-1"></i> Tandai Semua Dibaca
            </button>
          </form>
        </div>
      </div>

      <div class="card-body">
        <div class="table-responsive">
          <table id="notifTable" class="table table-hover table-bordered nowrap w-100">
            <thead class="table-light">
              <tr>
                <th style="width:60px;" class="text-center">No</th>
                <th style="width:160px;">Waktu</th>
                <th>Notifikasi</th>
                <th style="width:180px;">Kategori</th>
                <th style="width:130px;">Status</th>
                <th style="width:110px;" class="text-center">Aksi</th>
                <th>_status</th>
                <th>_kategori</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <?php
                  $id       = (int)($item['id'] ?? 0);
                  $type     = (string)($item['type'] ?? 'info');
                  $catKey   = notification_type_category($type);
                  $catLabel = $categories[$catKey] ?? 'Umum';
                  $isRead   = !empty($item['is_read']);
                  $link     = trim((string)($item['link'] ?? ''));
                  $title    = (string)($item['title'] ?? '-');
                  $message  = (string)($item['message'] ?? '');
                ?>
                <tr class="<?= $isRead ? '' : 'bg-light' ?>">
                  <td class="text-center"></td>
                  <td><span class="text-dark"><?= esc($item['created_at'] ?? '-') ?></span></td>
                  <td>
                    <?php if ($link !== ''): ?>
                      <a href="<?= esc($link, 'attr') ?>" class="fw-semibold text-dark notif-open" data-notif-id="<?= $id ?>"><?= esc($title) ?></a>
                    <?php else: ?>
                      <span class="fw-semibold text-dark"><?= esc($title) ?></span>
                    <?php endif; ?>
                    <?php if ($message !== ''): ?>
                      <div class="text-dark font-size-13"><?= esc($message) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-info"><i class="<?= esc(notification_icon($type), 'attr') ?> me-1"></i><?= esc($catLabel) ?></span>
                  </td>
                  <td>
                    <?php if ($isRead): ?>
                      <span class="badge bg-success">Sudah Dibaca</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Belum Dibaca</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      <?php if (!$isRead): ?>
                        <form method="post" action="<?= site_url($basePath . '/notifications/mark-read/' . $id) ?>" class="d-inline">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn btn-sm btn-success" title="Tandai dibaca" data-bs-toggle="tooltip">
                            <i class="mdi mdi-email-open-outline"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                      <button type="button" class="btn btn-sm btn-danger btn-del-notif"
                              title="Hapus" data-bs-toggle="tooltip"
                              data-notif-id="<?= $id ?>" data-notif-title="<?= esc($title, 'attr') ?>">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </div>
                  </td>
                  <td><?= $isRead ? 'read' : 'unread' ?></td>
                  <td><?= esc($catKey) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (empty($items)): ?>
          <div class="text-center py-5">
            <i class="mdi mdi-bell-off text-dark" style="font-size:48px;"></i>
            <p class="text-dark mt-2 mb-0">Belum ada notifikasi.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteNotifModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="#" id="deleteNotifForm" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Hapus Notifikasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Hapus notifikasi <strong id="deleteNotifTitle">ini</strong>?</p>
        <small class="text-dark">Notifikasi akan dihapus dari daftar Anda.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Hapus</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

    <?php if (!empty($items)): ?>
    var table = $('#notifTable').DataTable({
      responsive: true,
      pageLength: 10,
      order: [],
      columnDefs: [
        { orderable: false, targets: [0, 5] },
        { searchable: false, targets: [5] },
        { visible: false, searchable: true, targets: [6, 7] }
      ],
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
      table.column(0, { page: 'current' }).nodes().each(function (cell, i) {
        cell.innerHTML = info.start + i + 1;
      });
    }
    table.on('order.dt draw.dt', renumber);
    renumber();

    $('#statusFilter').on('change', function () {
      var v = this.value;
      table.column(6).search(v ? '^' + v + '$' : '', true, false).draw();
    });
    $('#categoryFilter').on('change', function () {
      var v = this.value;
      table.column(7).search(v ? '^' + v + '$' : '', true, false).draw();
    });
    $('#searchBox').on('keyup', function () {
      table.search(this.value).draw();
    });
    <?php endif; ?>

    var delModalEl = document.getElementById('deleteNotifModal');
    var delModal = delModalEl ? new bootstrap.Modal(delModalEl) : null;
    document.querySelectorAll('.btn-del-notif').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-notif-id');
        var title = btn.getAttribute('data-notif-title') || 'ini';
        document.getElementById('deleteNotifTitle').textContent = title;
        document.getElementById('deleteNotifForm').setAttribute('action', '<?= site_url($basePath . '/notifications/delete') ?>/' + id);
        if (delModal) delModal.show();
      });
    });
  });
</script>
<?= $this->endSection() ?>
