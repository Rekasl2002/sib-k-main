<?php
/**
 * File Path: app/Views/homeroom_teacher/career/index.php
 * Halaman utama Info Karier dan Studi Lanjut (Wali Kelas) — HANYA BACA (R*).
 *
 * Wali Kelas hanya MELIHAT daftar karier & perguruan tinggi sebagai bahan
 * mendampingi siswa. Mengelola data = wewenang Koordinator BK & Guru BK,
 * jadi TIDAK ada tombol tambah/edit/hapus di sini.
 *
 * Tampilan diseragamkan dengan Manajemen Siswa (page-title-box, kartu statistik,
 * kartu Filter/Saring Data, kartu daftar + DataTables).
 *
 * Variabel dari Controller: $careers, $careerFilters, $universities, $uniFilters, $stats, $activeTab
 */

$this->extend('layouts/main');
$this->section('content');

helper('url');

$careerFilters = $careerFilters ?? [];
$uniFilters    = $uniFilters ?? [];
$stats         = $stats ?? [];
$activeTab      = $activeTab ?? 'careers';

$sectors = [];
foreach (($careers ?? []) as $c) {
    if (!empty($c['sector'])) $sectors[$c['sector']] = $c['sector'];
}
ksort($sectors);

$accs = [];
$locs = [];
foreach (($universities ?? []) as $u) {
    if (!empty($u['accreditation'])) $accs[$u['accreditation']] = $u['accreditation'];
    if (!empty($u['location']))      $locs[$u['location']]      = $u['location'];
}
foreach (['Unggul','A','B','C','Baik','Baik Sekali'] as $std) { $accs[$std] = $accs[$std] ?? $std; }
ksort($accs); ksort($locs);
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Info Karier dan Studi Lanjut</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Wali Kelas</a></li>
                    <li class="breadcrumb-item active">Info Karier dan Studi Lanjut</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-2"></i><?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Catatan peran: hanya melihat -->
<div class="alert alert-info d-flex align-items-center" role="alert">
    <i class="mdi mdi-information-outline me-2 font-size-18"></i>
    <div>Halaman ini untuk <strong>melihat</strong> informasi karier &amp; perguruan tinggi sebagai bahan mendampingi siswa. Penambahan/perubahan data dikelola oleh Koordinator BK dan Guru BK.</div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <?php
    $miniCards = [
        ['label' => 'Total Pilihan Karier', 'value' => $stats['careers_total'] ?? 0,  'bg' => 'bg-primary', 'icon' => 'mdi-briefcase-outline'],
        ['label' => 'Karier Aktif',         'value' => $stats['careers_active'] ?? 0, 'bg' => 'bg-success', 'icon' => 'mdi-briefcase-check-outline'],
        ['label' => 'Total Perguruan Tinggi','value' => $stats['uni_total'] ?? 0,      'bg' => 'bg-info',    'icon' => 'mdi-town-hall'],
        ['label' => 'Perguruan Tinggi Aktif','value' => $stats['uni_active'] ?? 0,     'bg' => 'bg-secondary','icon' => 'mdi-school-outline'],
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
                                <span class="avatar-title"><i class="mdi <?= esc($mc['icon'], 'attr') ?> font-size-24"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab === 'careers' ? 'active' : '') ?>" href="<?= site_url('homeroom/career-info?tab=careers') ?>">
            <i class="mdi mdi-briefcase-outline me-1"></i>Pilihan Karier
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab === 'universities' ? 'active' : '') ?>" href="<?= site_url('homeroom/career-info?tab=universities') ?>">
            <i class="mdi mdi-town-hall me-1"></i>Info Perguruan Tinggi
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB: KARIER -->
    <div class="tab-pane fade <?= ($activeTab === 'careers' ? 'show active' : '') ?>">
        <div class="row">
            <div class="col-12">
                <div class="card filter-compact">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0 text-dark"><i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data</h5>
                    </div>
                    <div class="card-body py-3">
                        <form action="<?= site_url('homeroom/career-info') ?>" method="get">
                            <input type="hidden" name="tab" value="careers">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Sektor</label>
                                    <select name="sector" class="form-select">
                                        <option value="">Semua Sektor</option>
                                        <?php foreach ($sectors as $s): ?>
                                            <option value="<?= esc($s) ?>" <?= (($careerFilters['sector'] ?? '') === $s) ? 'selected' : '' ?>><?= esc($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Pendidikan Minimal</label>
                                    <select name="edu" class="form-select">
                                        <option value="">Semua Tingkat</option>
                                        <?php foreach (['SMA/SMK','D3','S1','S2'] as $e): ?>
                                            <option value="<?= esc($e) ?>" <?= (($careerFilters['edu'] ?? '') === $e) ? 'selected' : '' ?>><?= esc($e) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <?php $fStatus = $careerFilters['status'] ?? ''; ?>
                                        <option value=""  <?= $fStatus === ''  ? 'selected' : '' ?>>Semua Status</option>
                                        <option value="1" <?= $fStatus === '1' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="0" <?= $fStatus === '0' ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tampil ke Siswa</label>
                                    <select name="pub" class="form-select">
                                        <?php $fPub = $careerFilters['pub'] ?? ''; ?>
                                        <option value=""  <?= $fPub === ''  ? 'selected' : '' ?>>Semua</option>
                                        <option value="1" <?= $fPub === '1' ? 'selected' : '' ?>>Ditampilkan</option>
                                        <option value="0" <?= $fPub === '0' ? 'selected' : '' ?>>Disembunyikan</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Pencarian</label>
                                    <input type="text" name="q" class="form-control" placeholder="Judul, sektor, deskripsi..." value="<?= esc($careerFilters['q'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row mt-2 g-3">
                                <div class="col-md-2">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify me-1"></i> Filter/Saring</button>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <a href="<?= site_url('homeroom/career-info?tab=careers') ?>" class="btn btn-secondary w-100"><i class="mdi mdi-refresh me-1"></i> Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="mdi mdi-briefcase-outline me-2"></i>Daftar Pilihan Karier</h4>
                        <div class="text-end">
                            <a href="<?= route_to('homeroom.career.choices') ?>" class="btn btn-info">
                                <i class="mdi mdi-account-multiple-outline me-1"></i> Pilihan Siswa
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="careersTable" class="table table-hover table-bordered nowrap w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:60px;" class="text-center">No</th>
                                        <th>Judul Karier</th>
                                        <th style="width:150px;">Sektor</th>
                                        <th style="width:140px;">Pendidikan Min.</th>
                                        <th style="width:160px;">Dibuat Oleh</th>
                                        <th style="width:110px;">Status</th>
                                        <th style="width:140px;">Tampil ke Siswa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($careers)): ?>
                                    <?php foreach ($careers as $c): ?>
                                        <?php $creatorName = trim((string)($c['created_by_name'] ?? '')); ?>
                                        <tr>
                                            <td class="text-center"></td>
                                            <td><?= esc($c['title']) ?></td>
                                            <td><?= esc($c['sector'] ?? '—') ?></td>
                                            <td><?= esc($c['min_education'] ?? '—') ?></td>
                                            <td><?= $creatorName !== '' ? esc($creatorName) : '<span class="text-dark">—</span>' ?></td>
                                            <td>
                                                <?php if ((int)($c['is_active'] ?? 0) === 1): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ((int)($c['is_public'] ?? 0) === 1): ?>
                                                    <span class="badge bg-primary">Ditampilkan</span>
                                                <?php else: ?>
                                                    <span class="badge bg-dark">Disembunyikan</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="mdi mdi-briefcase-off-outline text-dark" style="font-size: 48px;"></i>
                                            <p class="text-dark mt-2 mb-0">Belum ada data karier</p>
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
    </div>

    <!-- TAB: PERGURUAN TINGGI -->
    <div class="tab-pane fade <?= ($activeTab === 'universities' ? 'show active' : '') ?>">
        <div class="row">
            <div class="col-12">
                <div class="card filter-compact">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0 text-dark"><i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data</h5>
                    </div>
                    <div class="card-body py-3">
                        <form action="<?= site_url('homeroom/career-info') ?>" method="get">
                            <input type="hidden" name="tab" value="universities">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Akreditasi</label>
                                    <select name="uacc" class="form-select">
                                        <?php $uAcc = $uniFilters['acc'] ?? ''; ?>
                                        <option value="">Semua Akreditasi</option>
                                        <?php foreach ($accs as $acc): ?>
                                            <option value="<?= esc($acc) ?>" <?= $uAcc === $acc ? 'selected' : '' ?>><?= esc($acc) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Lokasi</label>
                                    <select name="uloc" class="form-select">
                                        <?php $uLoc = $uniFilters['loc'] ?? ''; ?>
                                        <option value="">Semua Lokasi</option>
                                        <?php foreach ($locs as $loc): ?>
                                            <option value="<?= esc($loc) ?>" <?= $uLoc === $loc ? 'selected' : '' ?>><?= esc($loc) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="ustatus" class="form-select">
                                        <?php $uStatus = $uniFilters['status'] ?? ''; ?>
                                        <option value=""  <?= $uStatus === ''  ? 'selected' : '' ?>>Semua Status</option>
                                        <option value="1" <?= $uStatus === '1' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="0" <?= $uStatus === '0' ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tampil ke Siswa</label>
                                    <select name="upub" class="form-select">
                                        <?php $uPub = $uniFilters['pub'] ?? ''; ?>
                                        <option value=""  <?= $uPub === ''  ? 'selected' : '' ?>>Semua</option>
                                        <option value="1" <?= $uPub === '1' ? 'selected' : '' ?>>Ditampilkan</option>
                                        <option value="0" <?= $uPub === '0' ? 'selected' : '' ?>>Disembunyikan</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Pencarian</label>
                                    <input type="text" name="uq" class="form-control" placeholder="Nama / alias / deskripsi..." value="<?= esc($uniFilters['q'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row mt-2 g-3">
                                <div class="col-md-2">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify me-1"></i> Filter/Saring</button>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <a href="<?= site_url('homeroom/career-info?tab=universities') ?>" class="btn btn-secondary w-100"><i class="mdi mdi-refresh me-1"></i> Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="mdi mdi-town-hall me-2"></i>Daftar Perguruan Tinggi</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="universitiesTable" class="table table-hover table-bordered nowrap w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:60px;" class="text-center">No</th>
                                        <th>Nama</th>
                                        <th style="width:140px;">Alias</th>
                                        <th style="width:130px;">Akreditasi</th>
                                        <th style="width:170px;">Lokasi</th>
                                        <th style="width:160px;">Dibuat Oleh</th>
                                        <th style="width:110px;">Status</th>
                                        <th style="width:140px;">Tampil ke Siswa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($universities)): ?>
                                    <?php foreach ($universities as $u): ?>
                                        <?php $uCreatorName = trim((string)($u['created_by_name'] ?? '')); ?>
                                        <tr>
                                            <td class="text-center"></td>
                                            <td><?= esc($u['university_name']) ?></td>
                                            <td><?= esc($u['alias'] ?? '—') ?></td>
                                            <td><?= esc($u['accreditation'] ?? '—') ?></td>
                                            <td><?= esc($u['location'] ?? '—') ?></td>
                                            <td><?= $uCreatorName !== '' ? esc($uCreatorName) : '<span class="text-dark">—</span>' ?></td>
                                            <td>
                                                <?php if ((int)($u['is_active'] ?? 0) === 1): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ((int)($u['is_public'] ?? 0) === 1): ?>
                                                    <span class="badge bg-primary">Ditampilkan</span>
                                                <?php else: ?>
                                                    <span class="badge bg-dark">Disembunyikan</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="mdi mdi-town-hall text-dark" style="font-size: 48px;"></i>
                                            <p class="text-dark mt-2 mb-0">Belum ada data perguruan tinggi</p>
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
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        var dtLang = {
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang sesuai",
            emptyTable: "Tidak ada data tersedia",
            paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
        };

        var dtDom = "rt" +
            "<'row align-items-center mt-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start'p>>" +
            "<'row'<'col-12 mt-2'i>>";

        function initTable(sel) {
            var t = $(sel).DataTable({
                responsive: true,
                pageLength: 10,
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0] }],
                dom: dtDom,
                language: dtLang
            });
            function renumber() {
                var info = t.page.info();
                t.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                    cell.innerHTML = info.start + i + 1;
                });
            }
            t.on('order.dt draw.dt', renumber);
            renumber();
            return t;
        }

        if ($('#careersTable tbody tr td[colspan]').length === 0) initTable('#careersTable');
        if ($('#universitiesTable tbody tr td[colspan]').length === 0) initTable('#universitiesTable');
    });
</script>
<?= $this->endSection() ?>
