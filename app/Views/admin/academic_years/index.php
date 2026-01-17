<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/academic_years/index.php
 * Admin • Academic Years Index (DataTables pagination in View)
 */
helper('app');

// ✅ Robust stats fallback (agar tidak 0 jika service pakai format by_semester)
$ganjil = (int) (
    ($stats['by_semester']['Ganjil'] ?? null)
    ?? ($stats['ganjil'] ?? null)
    ?? ($stats['odd_semester'] ?? null)
    ?? 0
);

$genap = (int) (
    ($stats['by_semester']['Genap'] ?? null)
    ?? ($stats['genap'] ?? null)
    ?? ($stats['even_semester'] ?? null)
    ?? 0
);
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= esc($page_title ?? 'Manajemen Tahun Ajaran') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <?php if (!empty($breadcrumb) && is_array($breadcrumb)): ?>
                        <?php foreach ($breadcrumb as $bc): ?>
                            <?php if (!empty($bc['link'])): ?>
                                <li class="breadcrumb-item"><a href="<?= esc($bc['link']) ?>"><?= esc($bc['title']) ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active"><?= esc($bc['title']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<?= show_alerts() ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Terdapat kesalahan:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ((array) session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Active Academic Year Info -->
<?php if (!empty($active_year)): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="mdi mdi-information font-size-20 me-2"></i>
            <div>
                <strong>Tahun Ajaran Aktif:</strong>
                <?= esc($active_year['year_name']) ?> (<?= esc($active_year['semester']) ?>)
                <span class="ms-2">
                    <?= date('d M Y', strtotime($active_year['start_date'])) ?>
                    s/d
                    <?= date('d M Y', strtotime($active_year['end_date'])) ?>
                </span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Tahun Ajaran</p>
                        <h4 class="mb-0 counter-value" data-target="<?= (int)($stats['total'] ?? 0) ?>">0</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title"><i class="mdi mdi-calendar-multiple font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Tahun Ajaran Aktif</p>
                        <h4 class="mb-0 counter-value" data-target="<?= (int)($stats['active'] ?? 0) ?>">0</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                            <span class="avatar-title"><i class="mdi mdi-check-circle font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Semester Ganjil</p>
                        <!-- ✅ FIX: pakai fallback variabel -->
                        <h4 class="mb-0 counter-value" data-target="<?= $ganjil ?>">0</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                            <span class="avatar-title"><i class="mdi mdi-calendar-text font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Semester Genap</p>
                        <!-- ✅ FIX: pakai fallback variabel -->
                        <h4 class="mb-0 counter-value" data-target="<?= $genap ?>">0</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                            <span class="avatar-title"><i class="mdi mdi-calendar-range font-size-24"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card (format seperti counselor/sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/academic-years') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="">Semua Semester</option>
                                <?php foreach (($semester_options ?? []) as $key => $label): ?>
                                    <option value="<?= esc($key) ?>" <?= (($filters['semester'] ?? '') === (string)$key) ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="">Semua Status</option>
                                <?php foreach (($status_options ?? []) as $key => $label): ?>
                                    <option value="<?= esc($key) ?>" <?= (($filters['is_active'] ?? '') === (string)$key) ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Pencarian</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Cari tahun ajaran (contoh: 2025/2026)"
                                   value="<?= esc($filters['search'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('admin/academic-years') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Tahun Ajaran</h4>
                <a href="<?= base_url('admin/academic-years/create') ?>" class="btn btn-primary">
                    <i class="mdi mdi-plus-circle me-1"></i> Tambah Tahun Ajaran
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="academicYearsTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Tahun Ajaran</th>
                                <th width="10%">Semester</th>
                                <th width="12%">Tanggal Mulai</th>
                                <th width="12%">Tanggal Selesai</th>
                                <th width="10%" class="text-center">Durasi</th>
                                <th width="10%" class="text-center">Jumlah Kelas</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($academic_years)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="mdi mdi-information-outline text-muted" style="font-size: 48px;"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data tahun ajaran</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($academic_years as $year): ?>
                                    <?php
                                        $start  = !empty($year['start_date']) ? strtotime($year['start_date']) : null;
                                        $end    = !empty($year['end_date']) ? strtotime($year['end_date']) : null;
                                        $months = 0;
                                        if ($start && $end && $end >= $start) {
                                            $durationDays = ($end - $start) / (60 * 60 * 24);
                                            $months = (int) round($durationDays / 30);
                                        }
                                    ?>
                                    <tr>
                                        <td class="text-center"></td>
                                        <td>
                                            <strong><?= esc($year['year_name']) ?></strong>
                                            <?php if (!empty($year['is_active'])): ?>
                                                <span class="badge bg-success ms-2">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($year['semester'] ?? '') === 'Ganjil'): ?>
                                                <span class="badge bg-info"><?= esc($year['semester']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?= esc($year['semester'] ?? '-') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($year['start_date']) ? date('d M Y', strtotime($year['start_date'])) : '-' ?></td>
                                        <td><?= !empty($year['end_date']) ? date('d M Y', strtotime($year['end_date'])) : '-' ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= $months ?> bulan</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info font-size-12"><?= (int)($year['class_count'] ?? 0) ?> kelas</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($year['is_active'])): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tidak Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <?php if (empty($year['is_active'])): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-success"
                                                            onclick="confirmSetActive(<?= (int)$year['id'] ?>, '<?= esc($year['year_name']) ?>')"
                                                            data-bs-toggle="tooltip"
                                                            title="Aktifkan">
                                                        <i class="mdi mdi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <a href="<?= base_url('admin/academic-years/edit/' . $year['id']) ?>"
                                                   class="btn btn-sm btn-primary"
                                                   data-bs-toggle="tooltip"
                                                   title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>

                                                <?php if ((int)($year['class_count'] ?? 0) === 0): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="confirmDelete(<?= (int)$year['id'] ?>, '<?= esc($year['year_name']) ?>')"
                                                            data-bs-toggle="tooltip"
                                                            title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-secondary"
                                                            disabled
                                                            data-bs-toggle="tooltip"
                                                            title="Tidak bisa dihapus (ada kelas)">
                                                        <i class="mdi mdi-lock"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Set Active Confirmation Modal -->
<div class="modal fade" id="setActiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-check-circle text-success me-2"></i>Konfirmasi Aktifkan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin mengaktifkan tahun ajaran <strong id="activeYearName"></strong>?</p>
                <div class="alert alert-info mb-0" role="alert">
                    <i class="mdi mdi-information me-2"></i>
                    Tahun ajaran yang sedang aktif akan otomatis dinonaktifkan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="setActiveForm" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-check-circle me-1"></i> Aktifkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-alert-circle text-danger me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus tahun ajaran <strong id="deleteYearName"></strong>?</p>
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="mdi mdi-alert me-2"></i>
                    <strong>Perhatian:</strong> Tahun ajaran yang memiliki kelas tidak dapat dihapus.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete me-1"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables (samakan dengan counselor/sessions) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    function confirmSetActive(id, yearName) {
        document.getElementById('activeYearName').textContent = yearName;
        document.getElementById('setActiveForm').action = '<?= base_url('admin/academic-years/set-active/') ?>' + id;
        new bootstrap.Modal(document.getElementById('setActiveModal')).show();
    }

    function confirmDelete(id, yearName) {
        document.getElementById('deleteYearName').textContent = yearName;
        document.getElementById('deleteForm').action = '<?= base_url('admin/academic-years/delete/') ?>' + id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    $(document).ready(function() {
        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

        // Counter animation
        const counters = document.querySelectorAll('.counter-value');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const increment = target / 100;

            const updateCounter = () => {
                const current = +counter.innerText;
                if (current < target) {
                    counter.innerText = Math.ceil(current + increment);
                    setTimeout(updateCounter, 10);
                } else {
                    counter.innerText = target;
                }
            };
            updateCounter();
        });

        // DataTables pagination in View (default 10)
        <?php if (!empty($academic_years) && is_array($academic_years)): ?>
            var dt;
            if (window.SIBK && typeof SIBK.initDataTable === 'function') {
                dt = SIBK.initDataTable('academicYearsTable', {
                    pageLength: 10,
                    order: [[1, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 8] }
                    ]
                });
            } else {
                dt = $('#academicYearsTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    order: [[1, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 8] }
                    ],
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(difilter dari _MAX_ total data)",
                        zeroRecords: "Tidak ada data yang sesuai",
                        emptyTable: "Tidak ada data tersedia",
                        processing: "Memproses...",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Berikutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });
            }

            // ✅ Nomor urut selalu mulai dari 1 (dan benar per halaman)
            dt.on('order.dt search.dt draw.dt', function() {
                var info = dt.page.info();
                dt.column(0, { search:'applied', order:'applied' }).nodes().each(function(cell, i) {
                    cell.innerHTML = info.start + i + 1;
                });
            }).draw();
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>
