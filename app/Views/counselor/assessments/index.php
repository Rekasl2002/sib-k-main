<?php
/**
 * Daftar Asesmen — Guru BK
 * Seragam ala Manajemen Siswa / halaman Bimbingan (counselor/guidance):
 * page-title-box + kartu statistik + kartu Filter/Saring + DataTables (Indonesia).
 * Tombol aksi berupa IKON VERTIKAL (ditumpuk) ber-tooltip.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$assessments      = is_array($assessments ?? null) ? $assessments : [];
$assessment_types = is_array($assessment_types ?? null) ? $assessment_types : [];
$filters          = is_array($filters ?? null) ? $filters : [];
$stats            = is_array($stats ?? null) ? $stats : [];

$prefix = 'counselor/assessments';

// Peta tampilan target peserta (hindari istilah Inggris).
$targetLabels = [
    'Individual' => 'Individu',
    'Class'      => 'Kelas',
    'Grade'      => 'Tingkat',
    'All'        => 'Semua Siswa',
];
$targetIcons = [
    'Individual' => 'mdi-account',
    'Class'      => 'mdi-account-group',
    'Grade'      => 'mdi-school',
    'All'        => 'mdi-earth',
];
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0 text-dark">Asesmen</h4>
        <p class="text-dark mb-0">Kelola asesmen psikologi, minat bakat, dan lainnya untuk siswa.</p>
      </div>
      <a href="<?= site_url($prefix . '/create') ?>" class="btn btn-primary">
        <i class="mdi mdi-plus me-1"></i> Tambah Asesmen
      </a>
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
      ['label' => 'Total Asesmen', 'value' => $stats['total_assessments'] ?? 0, 'bg' => 'bg-primary', 'icon' => 'mdi-clipboard-text'],
      ['label' => 'Dipublikasikan', 'value' => $stats['published'] ?? 0, 'bg' => 'bg-success', 'icon' => 'mdi-check-decagram'],
      ['label' => 'Draf', 'value' => $stats['draft'] ?? 0, 'bg' => 'bg-warning', 'icon' => 'mdi-file-document-edit'],
      ['label' => 'Aktif', 'value' => $stats['active'] ?? 0, 'bg' => 'bg-info', 'icon' => 'mdi-toggle-switch'],
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
        <form method="get" action="<?= site_url($prefix) ?>">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label text-dark">Tipe Asesmen</label>
              <select name="assessment_type" class="form-select">
                <option value="">Semua Tipe</option>
                <?php foreach ($assessment_types as $key => $value): ?>
                  <option value="<?= esc($key) ?>" <?= (($filters['assessment_type'] ?? '') == $key) ? 'selected' : '' ?>><?= esc($value) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label text-dark">Status Publikasi</label>
              <select name="is_published" class="form-select">
                <option value="">Semua Status</option>
                <option value="1" <?= (($filters['is_published'] ?? '') === '1') ? 'selected' : '' ?>>Dipublikasikan</option>
                <option value="0" <?= (($filters['is_published'] ?? '') === '0') ? 'selected' : '' ?>>Draf</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label text-dark">Target Peserta</label>
              <select name="target_audience" class="form-select">
                <option value="">Semua Target</option>
                <option value="Individual" <?= (($filters['target_audience'] ?? '') == 'Individual') ? 'selected' : '' ?>>Individu</option>
                <option value="Class" <?= (($filters['target_audience'] ?? '') == 'Class') ? 'selected' : '' ?>>Kelas</option>
                <option value="Grade" <?= (($filters['target_audience'] ?? '') == 'Grade') ? 'selected' : '' ?>>Tingkat</option>
                <option value="All" <?= (($filters['target_audience'] ?? '') == 'All') ? 'selected' : '' ?>>Semua Siswa</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label text-dark">Pencarian</label>
              <input type="text" name="search" class="form-control" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Cari judul atau keterangan asesmen">
            </div>
          </div>
          <div class="row mt-2 g-3">
            <div class="col-md-2">
              <label class="form-label d-block">&nbsp;</label>
              <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify me-1"></i> Filter/Saring</button>
            </div>
            <div class="col-md-2">
              <label class="form-label d-block">&nbsp;</label>
              <a href="<?= site_url($prefix) ?>" class="btn btn-secondary w-100"><i class="mdi mdi-refresh me-1"></i> Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Daftar Asesmen -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title mb-0 text-dark"><i class="mdi mdi-clipboard-text me-2"></i>Daftar Asesmen</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="assessmentTable" class="table table-hover table-bordered nowrap w-100">
            <thead class="table-light">
              <tr>
                <th style="width:60px;" class="text-center">No</th>
                <th>Judul Asesmen</th>
                <th style="width:130px;">Tipe</th>
                <th style="width:150px;">Target</th>
                <th style="width:90px;" class="text-center">Jumlah Soal</th>
                <th style="width:90px;" class="text-center">Peserta</th>
                <th style="width:140px;" class="text-center">Status</th>
                <th style="width:150px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assessments as $a): ?>
                <?php
                  $aid          = (int) ($a['id'] ?? 0);
                  $tipe         = (string) ($a['assessment_type'] ?? '-');
                  $targetRaw    = (string) ($a['target_audience'] ?? 'All');
                  $targetText   = $targetLabels[$targetRaw] ?? $targetRaw;
                  $targetIcon   = $targetIcons[$targetRaw] ?? 'mdi-account-group';
                  $jumlahSoal   = (int) ($a['total_questions'] ?? 0);
                  $peserta      = (int) ($a['total_participants'] ?? 0);
                  $published    = ! empty($a['is_published']);
                  $aktif        = ! empty($a['is_active']);
                ?>
                <tr>
                  <td class="text-center"></td>
                  <td>
                    <div class="fw-semibold text-dark">
                      <a href="<?= site_url($prefix . '/' . $aid) ?>" class="text-dark text-decoration-none"><?= esc($a['title'] ?? '-') ?></a>
                    </div>
                    <?php if (! empty($a['description'])): ?>
                      <small class="text-dark"><?= esc(mb_substr((string) $a['description'], 0, 70)) ?><?= mb_strlen((string) $a['description']) > 70 ? '…' : '' ?></small>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-info text-white"><?= esc($tipe) ?></span></td>
                  <td class="text-dark">
                    <span class="badge bg-secondary"><i class="mdi <?= esc($targetIcon, 'attr') ?> me-1"></i><?= esc($targetText) ?></span>
                    <?php if (! empty($a['target_class_name'])): ?>
                      <div><small class="text-dark"><?= esc($a['target_class_name']) ?></small></div>
                    <?php endif; ?>
                  </td>
                  <td class="text-center"><span class="badge bg-primary rounded-pill"><?= $jumlahSoal ?></span></td>
                  <td class="text-center"><span class="badge bg-success rounded-pill"><?= $peserta ?></span></td>
                  <td class="text-center">
                    <?php if ($published): ?>
                      <span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i>Dipublikasikan</span>
                    <?php else: ?>
                      <span class="badge bg-warning"><i class="mdi mdi-file-document-edit me-1"></i>Draf</span>
                    <?php endif; ?>
                    <?php if ($aktif): ?>
                      <div class="mt-1"><span class="badge bg-info"><i class="mdi mdi-circle-medium"></i>Aktif</span></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex flex-column gap-1 action-stack mx-auto">
                      <a href="<?= site_url($prefix . '/' . $aid) ?>" class="btn btn-sm btn-info text-start" data-bs-toggle="tooltip" title="Lihat detail asesmen">
                        <i class="mdi mdi-eye me-1"></i> Detail
                      </a>
                      <a href="<?= site_url($prefix . '/' . $aid . '/questions') ?>" class="btn btn-sm btn-secondary text-start" data-bs-toggle="tooltip" title="Kelola soal/pertanyaan">
                        <i class="mdi mdi-help-box me-1"></i> Soal
                      </a>
                      <a href="<?= site_url($prefix . '/' . $aid . '/assign') ?>" class="btn btn-sm btn-primary text-start" data-bs-toggle="tooltip" title="Tugaskan ke siswa">
                        <i class="mdi mdi-account-plus me-1"></i> Tugaskan
                      </a>
                      <a href="<?= site_url($prefix . '/' . $aid . '/results') ?>" class="btn btn-sm btn-dark text-start" data-bs-toggle="tooltip" title="Lihat hasil pengerjaan">
                        <i class="mdi mdi-chart-bar me-1"></i> Hasil
                      </a>
                      <a href="<?= site_url($prefix . '/' . $aid . '/edit') ?>" class="btn btn-sm btn-warning text-start" data-bs-toggle="tooltip" title="Ubah asesmen">
                        <i class="mdi mdi-pencil me-1"></i> Edit
                      </a>
                      <?php if (! $published): ?>
                        <form method="post" action="<?= site_url($prefix . '/' . $aid . '/publish') ?>" onsubmit="return confirm('Publikasikan asesmen ini?');">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn btn-sm btn-success text-start w-100" data-bs-toggle="tooltip" title="Publikasikan asesmen">
                            <i class="mdi mdi-share me-1"></i> Publikasi
                          </button>
                        </form>
                      <?php else: ?>
                        <form method="post" action="<?= site_url($prefix . '/' . $aid . '/unpublish') ?>" onsubmit="return confirm('Batalkan publikasi asesmen ini?');">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn btn-sm btn-outline-success text-start w-100" data-bs-toggle="tooltip" title="Batalkan publikasi">
                            <i class="mdi mdi-share-off me-1"></i> Batal Publikasi
                          </button>
                        </form>
                      <?php endif; ?>
                      <form method="post" action="<?= site_url($prefix . '/' . $aid . '/duplicate') ?>" onsubmit="return confirm('Salin/duplikat asesmen ini?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-light border text-start w-100" data-bs-toggle="tooltip" title="Salin asesmen">
                          <i class="mdi mdi-content-copy me-1"></i> Salin
                        </button>
                      </form>
                      <form method="post" action="<?= site_url($prefix . '/' . $aid . '/delete') ?>" onsubmit="return confirm('Hapus asesmen ini? Semua data terkait akan ikut terhapus.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger text-start w-100" data-bs-toggle="tooltip" title="Hapus asesmen">
                          <i class="mdi mdi-delete me-1"></i> Hapus
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (! $assessments): ?>
                <tr>
                  <td colspan="8" class="text-center py-5">
                    <i class="mdi mdi-clipboard-off text-dark" style="font-size:48px;"></i>
                    <p class="text-dark mt-2 mb-0">Belum ada asesmen. Klik "Tambah Asesmen" untuk membuat yang pertama.</p>
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
<style>
  .action-stack { max-width: 160px; }
  .action-stack .btn { font-size: .78rem; }
</style>
<script>
  $(document).ready(function () {
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) { return new bootstrap.Tooltip(el); });
    <?php if (! empty($assessments)): ?>
      var table = $('#assessmentTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [0, 7] }],
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
