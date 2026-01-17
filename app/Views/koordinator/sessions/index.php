<?php

/**
 * File Path: app/Views/koordinator/sessions/index.php
 *
 * Koordinator BK - Sessions Index (Read-only)
 * Tampilan daftar sesi konseling (semua counselor) dengan filter dan DataTables
 *
 * Catatan:
 * - Tidak ada aksi Create/Edit/Delete (sesuai role Koordinator - view only)
 * - Menampilkan kolom Konselor (Guru BK)
 * - Filter counselor & student opsional (jika controller mengirim $counselors / $students)
 * - Pagination hanya menggunakan View (DataTables)
 */

$this->extend('layouts/main');
$this->section('content');

// Helper alert & format tanggal (sesuai proyek kamu)
helper('app');

// Safety defaults
$filters    = $filters    ?? [];
$sessions   = $sessions   ?? [];
$counselors = $counselors ?? [];
$students   = $students   ?? [];
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Sesi Konseling</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
                    <li class="breadcrumb-item active">Sesi Konseling</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
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

<!-- Filter Card (konsisten dengan counselor/sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-filter-variant me-2"></i>Filter Data
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('koordinator/sessions') ?>" method="get" id="filterForm">
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
                            <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from'] ?? '') ?>">
                        </div>

                        <!-- End Date Filter -->
                        <div class="col-md-2">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to'] ?? '') ?>">
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
                        <!-- Counselor Filter (opsional) -->
                        <div class="col-md-4">
                            <label class="form-label">Guru BK (Konselor)</label>
                            <select name="counselor_id" class="form-select" id="counselorFilter">
                                <option value="">Semua Konselor</option>

                                <?php if (!empty($counselors) && is_array($counselors)): ?>
                                    <?php foreach ($counselors as $c): ?>
                                        <?php
                                        $cid   = $c['id'] ?? ($c['counselor_id'] ?? null);
                                        $cname = $c['full_name'] ?? ($c['counselor_name'] ?? ($c['name'] ?? 'Konselor'));
                                        ?>
                                        <?php if ($cid !== null): ?>
                                            <option value="<?= (int)$cid ?>" <?= ((string)($filters['counselor_id'] ?? '') === (string)$cid) ? 'selected' : '' ?>>
                                                <?= esc($cname) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Jika kosong, tampilkan sesi dari semua Guru BK.</small>
                        </div>

                        <!-- Student Filter (opsional) -->
                        <div class="col-md-4">
                            <label class="form-label">Siswa</label>
                            <select name="student_id" class="form-select" id="studentFilter">
                                <option value="">Semua Siswa</option>
                                <?php if (!empty($students) && is_array($students)): ?>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= (int)($student['id'] ?? 0) ?>" <?= ((string)($filters['student_id'] ?? '') === (string)($student['id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= esc($student['student_name'] ?? '-') ?> - <?= esc($student['nisn'] ?? '-') ?>
                                            <?php if (!empty($student['class_name'])): ?>
                                                (<?= esc($student['class_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <a href="<?= base_url('koordinator/sessions') ?>" class="btn btn-secondary w-100">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Sessions Table (konsisten dengan counselor/sessions) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Sesi Konseling</h4>
                <span class="text-muted">
                    <i class="mdi mdi-eye-outline me-1"></i>Mode baca saja
                </span>
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
                                <th width="22%">Topik</th>
                                <th width="18%">Siswa/Kelas</th>
                                <th width="15%">Guru BK</th>
                                <th width="10%">Status</th>
                                <th width="7%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sessions) && is_array($sessions)): ?>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <!-- No diisi oleh DataTables -->
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
                                                'Individu'  => 'info',
                                                'Kelompok'  => 'warning',
                                                'Klasikal'  => 'primary',
                                            ];
                                            $stype = $session['session_type'] ?? '-';
                                            $color = $typeColors[$stype] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= esc($stype) ?></span>
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
                                            <?php if (($session['session_type'] ?? '') === 'Individu' && !empty($session['student_name'])): ?>
                                                <i class="mdi mdi-account"></i> <?= esc($session['student_name']) ?>
                                                <br><small class="text-muted"><?= esc($session['nisn'] ?? '') ?></small>
                                            <?php elseif (($session['session_type'] ?? '') === 'Klasikal' && !empty($session['class_name'])): ?>
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
                                            <i class="mdi mdi-account-tie"></i>
                                            <?= esc($session['counselor_name'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?php
                                            $statusColors = [
                                                'Dijadwalkan' => 'warning',
                                                'Selesai'     => 'success',
                                                'Dibatalkan'  => 'danger',
                                            ];
                                            $st = $session['status'] ?? '-';
                                            $statusColor = $statusColors[$st] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?>"><?= esc($st) ?></span>
                                            <?php if (!empty($session['is_confidential'])): ?>
                                                <br><span class="badge bg-dark mt-1">
                                                    <i class="mdi mdi-lock"></i> Confidential
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <a href="<?= base_url('koordinator/sessions/detail/' . (int)($session['id'] ?? 0)) ?>"
                                               class="btn btn-sm btn-info"
                                               title="Lihat Detail">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="mdi mdi-calendar-blank text-muted" style="font-size: 48px;"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data sesi konseling</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-2 text-muted">
                    <small>
                        <i class="mdi mdi-information-outline"></i>
                        Koordinator hanya melihat data. Perubahan data sesi dilakukan oleh Guru BK (Counselor).
                    </small>
                </div>
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
        // DataTables (pagination di VIEW, konsisten dengan counselor/sessions)
        <?php if (!empty($sessions) && is_array($sessions)): ?>
            var table;

            if (window.SIBK && typeof SIBK.initDataTable === 'function') {
                table = SIBK.initDataTable('sessionsTable', {
                    pageLength: 10,
                    order: [[1, 'desc']], // Tanggal
                    columnDefs: [
                        { orderable: false, targets: [0, 8] } // No + Aksi
                    ]
                });
            } else {
                table = $('#sessionsTable').DataTable({
                    responsive: true,
                    pageLength: 10,
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

        // Select2 - Student
        $('#studentFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Siswa',
            allowClear: true,
            width: '100%'
        });

        // Select2 - Counselor
        $('#counselorFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Guru BK',
            allowClear: true,
            width: '100%'
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
<?php $this->endSection(); ?>
