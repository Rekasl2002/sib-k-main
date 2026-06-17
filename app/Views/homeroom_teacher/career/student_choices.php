<?php
/**
 * File Path: app/Views/homeroom_teacher/career/student_choices.php
 * Pilihan Karier dan Studi Lanjut Siswa — kelas perwalian (Wali Kelas, R*).
 *
 * Tampilan diseragamkan dengan Manajemen Pengguna/Siswa + DataTables.
 * Setiap baris punya tombol "Detail" untuk melihat detail karier / perguruan tinggi.
 */

$this->extend('layouts/main');
$this->section('content');

helper('url');

$activeTab = $activeTab ?? 'careers';
$filters   = $filters ?? [];

$baseUrl = site_url('homeroom/career-info/student-choices');
$tabUrl  = static function (string $tab) use ($baseUrl, $filters) {
    return $baseUrl . '?' . http_build_query([
        'tab'  => $tab,
        'q'    => $filters['q'] ?? '',
        'sort' => $filters['sort'] ?? '',
    ]);
};
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Pilihan Karier dan Studi Lanjut Siswa</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= route_to('homeroom.career.index') ?>">Info Karier dan Studi Lanjut</a></li>
                    <li class="breadcrumb-item active">Pilihan Siswa</li>
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

<div class="alert alert-info d-flex align-items-center" role="alert">
    <i class="mdi mdi-information-outline me-2 font-size-18"></i>
    <div>Menampilkan pilihan karier &amp; perguruan tinggi siswa <strong>kelas perwalian Anda</strong>.</div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'careers' ? 'active' : '' ?>" href="<?= $tabUrl('careers') ?>">
            <i class="mdi mdi-briefcase-outline me-1"></i>Pilihan Karier
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'universities' ? 'active' : '' ?>" href="<?= $tabUrl('universities') ?>">
            <i class="mdi mdi-town-hall me-1"></i>Pilihan Perguruan Tinggi
        </a>
    </li>
</ul>

<!-- Filter Card -->
<div class="row">
    <div class="col-12">
        <div class="card filter-compact">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 text-dark"><i class="mdi mdi-filter-variant me-2"></i>Filter/Saring Data</h5>
            </div>
            <div class="card-body py-3">
                <form method="get" action="<?= $baseUrl ?>">
                    <input type="hidden" name="tab" value="<?= esc($activeTab, 'attr') ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pencarian</label>
                            <input type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>"
                                   placeholder="Nama siswa / NISN / <?= $activeTab === 'careers' ? 'karier' : 'perguruan tinggi' ?>...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Urutkan</label>
                            <select name="sort" class="form-select">
                                <option value="">Nama Siswa (A–Z)</option>
                                <option value="student_desc" <?= ($filters['sort'] ?? '') === 'student_desc' ? 'selected' : '' ?>>Nama Siswa (Z–A)</option>
                                <option value="class" <?= ($filters['sort'] ?? '') === 'class' ? 'selected' : '' ?>>Kelas</option>
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
                            <a href="<?= $tabUrl($activeTab) ?>" class="btn btn-secondary w-100"><i class="mdi mdi-refresh me-1"></i> Reset</a>
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
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-account-multiple-outline me-2"></i>
                    <?= $activeTab === 'careers' ? 'Pilihan Karier Siswa' : 'Pilihan Perguruan Tinggi Siswa' ?>
                </h4>
            </div>
            <div class="card-body">
                <?php if ($activeTab === 'careers'): ?>
                    <?php if (! $hasCareerTable): ?>
                        <div class="alert alert-info mb-0">Tabel <code>student_saved_careers</code> belum tersedia di database.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="choicesTable" class="table table-hover table-bordered nowrap w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:60px;" class="text-center">No</th>
                                        <th>Siswa</th>
                                        <th style="width:130px;">NISN</th>
                                        <th style="width:140px;">Kelas</th>
                                        <th>Karier</th>
                                        <th style="width:150px;">Sektor</th>
                                        <th style="width:140px;">Min. Pendidikan</th>
                                        <th style="width:160px;">Disimpan</th>
                                        <th style="width:90px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach (($careerChoices ?? []) as $row): ?>
                                    <tr>
                                        <td class="text-center"></td>
                                        <td><?= esc($row['student_name'] ?? '-') ?></td>
                                        <td><?= esc($row['nisn'] ?? '-') ?></td>
                                        <td><?= esc($row['class_name'] ?? '-') ?></td>
                                        <td><?= esc($row['career_title'] ?? '-') ?></td>
                                        <td><?= esc($row['sector'] ?? '-') ?></td>
                                        <td><?= esc($row['min_education'] ?? '-') ?></td>
                                        <td><?= esc($row['saved_at'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <a href="<?= route_to('homeroom.career.show', (int)($row['career_id'] ?? 0)) ?>" class="btn btn-sm btn-info" title="Detail Karier" data-bs-toggle="tooltip"><i class="mdi mdi-eye"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (! $hasUnivTable): ?>
                        <div class="alert alert-info mb-0">Tabel <code>student_saved_universities</code> belum tersedia di database.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="choicesTable" class="table table-hover table-bordered nowrap w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:60px;" class="text-center">No</th>
                                        <th>Siswa</th>
                                        <th style="width:130px;">NISN</th>
                                        <th style="width:140px;">Kelas</th>
                                        <th>Perguruan Tinggi</th>
                                        <th style="width:160px;">Lokasi</th>
                                        <th style="width:120px;">Akreditasi</th>
                                        <th style="width:160px;">Disimpan</th>
                                        <th style="width:90px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach (($universityChoices ?? []) as $row): ?>
                                    <tr>
                                        <td class="text-center"></td>
                                        <td><?= esc($row['student_name'] ?? '-') ?></td>
                                        <td><?= esc($row['nisn'] ?? '-') ?></td>
                                        <td><?= esc($row['class_name'] ?? '-') ?></td>
                                        <td><?= esc($row['university_name'] ?? '-') ?></td>
                                        <td><?= esc($row['location'] ?? '-') ?></td>
                                        <td><?= esc($row['accreditation'] ?? '-') ?></td>
                                        <td><?= esc($row['saved_at'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <a href="<?= route_to('homeroom.university.show', (int)($row['university_id'] ?? 0)) ?>" class="btn btn-sm btn-info" title="Detail Perguruan Tinggi" data-bs-toggle="tooltip"><i class="mdi mdi-eye"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

        var dtLang = {
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang sesuai",
            emptyTable: "Belum ada siswa yang menyimpan pilihan",
            paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
        };
        var dtDom = "rt" +
            "<'row align-items-center mt-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start'p>>" +
            "<'row'<'col-12 mt-2'i>>";

        if ($('#choicesTable').length) {
            var t = $('#choicesTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, -1] }],
                dom: dtDom,
                language: dtLang
            });
            var renumber = function () {
                var info = t.page.info();
                t.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.innerHTML = info.start + i + 1; });
            };
            t.on('order.dt draw.dt', renumber); renumber();
        }
    });
</script>
<?= $this->endSection() ?>
