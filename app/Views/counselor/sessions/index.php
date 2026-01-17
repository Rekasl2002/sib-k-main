<?php

/**
 * File Path: app/Views/counselor/sessions/index.php
 * 
 * Sessions Index View
 * Tampilan daftar sesi konseling dengan filter dan DataTables
 * 
 * @package    SIB-K
 * @subpackage Views/Counselor/Sessions
 * @category   View
 * @author     Development Team
 * @created    2025-01-06
 */

$this->extend('layouts/main');
$this->section('content');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Sesi Konseling</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Sesi Konseling</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php
helper('app');
?>
<?= show_alerts() ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Terdapat kesalahan pada input:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filter Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('counselor/sessions') ?>" method="get" id="filterForm">
                    <div class="row g-3">
                        <!-- Session Type Filter -->
                        <div class="col-md-3">
                            <label class="form-label">Jenis Sesi</label>
                            <select name="session_type" class="form-select">
                                <option value="">Semua Jenis</option>
                                <option value="Individu" <?= ($filters['session_type'] ?? '') === 'Individu' ? 'selected' : '' ?>>Individu</option>
                                <option value="Kelompok" <?= ($filters['session_type'] ?? '') === 'Kelompok' ? 'selected' : '' ?>>Kelompok</option>
                                <option value="Klasikal" <?= ($filters['session_type'] ?? '') === 'Klasikal' ? 'selected' : '' ?>>Klasikal</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="Dijadwalkan" <?= ($filters['status'] ?? '') === 'Dijadwalkan' ? 'selected' : '' ?>>Dijadwalkan</option>
                                <option value="Selesai" <?= ($filters['status'] ?? '') === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="Dibatalkan" <?= ($filters['status'] ?? '') === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>

                        <!-- Start Date Filter -->
                        <div class="col-md-2">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control" value="<?= esc($filters['start_date'] ?? '') ?>">
                        </div>

                        <!-- End Date Filter -->
                        <div class="col-md-2">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="<?= esc($filters['end_date'] ?? '') ?>">
                        </div>

                        <!-- Filter Buttons -->
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                        </div>
                    </div>

                    <div class="row mt-2 g-3">
                        <!-- Student Filter -->
                        <div class="col-md-4">
                            <label class="form-label">Siswa</label>
                            <select name="student_id" class="form-select" id="studentFilter">
                                <option value="">Semua Siswa</option>
                                <?php foreach (($students ?? []) as $student): ?>
                                    <option value="<?= esc($student['id']) ?>" <?= ($filters['student_id'] ?? '') == $student['id'] ? 'selected' : '' ?>>
                                        <?= esc($student['student_name'] ?? '') ?> - <?= esc($student['nisn'] ?? '') ?>
                                        <?php if (!empty($student['class_name'])): ?>
                                            (<?= esc($student['class_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('counselor/sessions') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Sessions Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Sesi Konseling</h4>
                <a href="<?= base_url('counselor/sessions/create') ?>" class="btn btn-primary">
                    <i class="mdi mdi-plus-circle me-1"></i> Tambah Sesi Baru
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="sessionsTable" class="table table-hover table-bordered nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Tanggal</th>
                                <th width="8%">Waktu</th>
                                <th width="10%">Jenis</th>
                                <th width="25%">Topik</th>
                                <th width="20%">Siswa/Kelas</th>
                                <th width="10%">Status</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sessions) && is_array($sessions)): ?>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <!-- No diisi oleh DataTables agar selalu urut (mulai 1) -->
                                        <td class="text-center"></td>

                                        <td data-order="<?= esc($session['session_date'] ?? '') ?>">
                                            <?= indonesian_date($session['session_date'] ?? '') ?>
                                        </td>

                                        <td class="text-center" data-order="<?= esc($session['session_time'] ?? '') ?>">
                                            <?= !empty($session['session_time']) ? date('H:i', strtotime($session['session_time'])) : '-' ?>
                                        </td>

                                        <td>
                                            <?php
                                            $typeColors = [
                                                'Individu' => 'info',
                                                'Kelompok' => 'warning',
                                                'Klasikal' => 'primary',
                                            ];
                                            $color = $typeColors[$session['session_type'] ?? ''] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= esc($session['session_type'] ?? '-') ?></span>
                                        </td>

                                        <td>
                                            <strong><?= esc($session['topic'] ?? '-') ?></strong>
                                            <?php if (!empty($session['location'])): ?>
                                                <br><small class="text-muted">
                                                    <i class="mdi mdi-map-marker"></i> <?= esc($session['location']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (($session['session_type'] ?? '') === 'Individu' && isset($session['student_name'])): ?>
                                                <i class="mdi mdi-account"></i> <?= esc($session['student_name']) ?>
                                                <br><small class="text-muted"><?= esc($session['nisn'] ?? '') ?></small>
                                            <?php elseif (($session['session_type'] ?? '') === 'Klasikal' && isset($session['class_name'])): ?>
                                                <i class="mdi mdi-google-classroom"></i> Kelas <?= esc($session['class_name']) ?>
                                            <?php elseif (($session['session_type'] ?? '') === 'Kelompok'): ?>
                                                <i class="mdi mdi-account-group"></i> Sesi Kelompok
                                                <?php if (isset($session['participant_count'])): ?>
                                                    <br><small class="text-muted"><?= (int)$session['participant_count'] ?> peserta</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php
                                            $statusColors = [
                                                'Dijadwalkan' => 'warning',
                                                'Selesai' => 'success',
                                                'Dibatalkan' => 'danger',
                                            ];
                                            $statusColor = $statusColors[$session['status'] ?? ''] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?>"><?= esc($session['status'] ?? '-') ?></span>
                                            <?php if (!empty($session['is_confidential'])): ?>
                                                <br><span class="badge bg-dark mt-1">
                                                    <i class="mdi mdi-lock"></i> Confidential
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url('counselor/sessions/detail/' . (int)($session['id'] ?? 0)) ?>"
                                                    class="btn btn-sm btn-info"
                                                    title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>

                                                <a href="<?= base_url('counselor/sessions/edit/' . (int)($session['id'] ?? 0)) ?>"
                                                    class="btn btn-sm btn-warning"
                                                    title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>

                                                <button type="button"
                                                    class="btn btn-sm btn-danger btn-delete"
                                                    data-id="<?= (int)($session['id'] ?? 0) ?>"
                                                    data-topic="<?= esc($session['topic'] ?? '') ?>"
                                                    title="Hapus">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="mdi mdi-calendar-blank text-muted" style="font-size: 48px;"></i>
                                        <p class="text-muted mt-2">Tidak ada data sesi konseling</p>
                                        <a href="<?= base_url('counselor/sessions/create') ?>" class="btn btn-primary btn-sm">
                                            <i class="mdi mdi-plus"></i> Tambah Sesi Baru
                                        </a>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-alert-circle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus sesi konseling ini?</p>
                <div class="alert alert-warning">
                    <strong>Topik:</strong> <span id="deleteSessionTopic"></span>
                </div>
                <p class="text-muted mb-0">
                    <small>
                        <i class="mdi mdi-information"></i>
                        Data yang dihapus dapat dipulihkan dari recycle bin.
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="mdi mdi-close"></i> Batal
                </button>
                <form id="deleteForm" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTables (pagination di View, 10 data per halaman)
        <?php if (!empty($sessions) && is_array($sessions)): ?>
            var table;

            if (window.SIBK && typeof SIBK.initDataTable === 'function') {
                table = SIBK.initDataTable('sessionsTable', {
                    pageLength: 10,
                    order: [[1, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 7] } // No + Aksi
                    ]
                });
            } else {
                table = $('#sessionsTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[1, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 7] }
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

            // Nomor urut selalu benar (mulai 1) walau sort/search/paging
            function renumber() {
                var info = table.page.info();
                table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                    cell.innerHTML = info.start + i + 1;
                });
            }
            table.on('order.dt search.dt draw.dt', renumber);
            renumber();
        <?php endif; ?>

        // Initialize Select2 for student filter
        $('#studentFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Siswa',
            allowClear: true,
            width: '100%'
        });

        // Delete button handler
        $('.btn-delete').on('click', function() {
            const sessionId = $(this).data('id');
            const sessionTopic = $(this).data('topic');

            $('#deleteSessionTopic').text(sessionTopic);
            $('#deleteForm').attr('action', '<?= base_url('counselor/sessions/delete/') ?>' + sessionId);

            $('#deleteModal').modal('show');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?php $this->endSection(); ?>
