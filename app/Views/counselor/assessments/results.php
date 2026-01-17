<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    // Id asesmen untuk tautan
    $assessmentId = (int)($assessment['id'] ?? 0);

    // Helper kecil
    if (!function_exists('h')) {
        function h($v) { return esc($v ?? ''); }
    }
    if (!function_exists('csrf_field')) {
        helper('form');
    }

    // Hitung peserta total secara aman
    $totalParticipants = (int) (
        $assessment['total_participants']
        ?? $statistics['total_participants']
        ?? $statistics['total_attempts']
        ?? 0
    );

    // Jika masih 0, fallback: hitung unik student_id dari $results (termasuk Assigned)
    if ($totalParticipants === 0 && !empty($results) && is_array($results)) {
        $uniq = [];
        foreach ($results as $r) {
            $sid = (int)($r['student_id'] ?? 0);
            if ($sid > 0) $uniq[$sid] = true;
        }
        $totalParticipants = count($uniq);
    }

    // Pastikan $classes ada; fallback dari $results bila belum dipassing
    if (!isset($classes) || !is_array($classes) || count($classes) === 0) {
        $byClass = [];
        if (!empty($results)) {
            foreach ($results as $r) {
                if (!empty($r['class_id']) && !empty($r['class_name'])) {
                    $byClass[$r['class_id']] = [
                        'id' => (int)$r['class_id'],
                        'class_name' => (string)$r['class_name'],
                    ];
                }
            }
        }
        $classes = array_values($byClass);
    }

    // Tambahan: hitung jumlah per status, termasuk "Assigned"
    $statusCounts = [
        'Assigned'     => 0,
        'In Progress'  => 0,
        'Completed'    => 0,
        'Graded'       => 0,
        'Expired'      => 0,
        'Abandoned'    => 0,
    ];
    if (!empty($results)) {
        foreach ($results as $r) {
            $s = (string)($r['status'] ?? '');
            if (isset($statusCounts[$s])) {
                $statusCounts[$s]++;
            }
        }
    }

    // Statistik inti dengan fallback
    $completedCnt = (int)($statistics['completed'] ?? ($statusCounts['Completed'] + $statusCounts['Graded']));
    $avgScore     = (float)($statistics['average_score'] ?? 0);
    $lowScore     = (float)($statistics['lowest_score'] ?? 0);
    $highScore    = (float)($statistics['highest_score'] ?? 0);
    $passRate     = (float)($statistics['pass_rate'] ?? 0);
    $passedCnt    = (int)($statistics['passed'] ?? 0);
    $failedCnt    = (int)($statistics['failed'] ?? max(0, $completedCnt - $passedCnt));

    $inProgressCnt = (int)($statistics['in_progress'] ?? $statusCounts['In Progress']);
    $assignedCnt   = $statusCounts['Assigned'] ?? 0;

    // Persentase selesai dibanding total peserta (jika total ada)
    $pctCompleted = $totalParticipants > 0 ? round(($completedCnt / $totalParticipants) * 100, 1) : 0;

    // Filters keamanan
    $filters = $filters ?? [
        'status' => '', 'class_id' => '', 'is_passed' => '', 'search' => ''
    ];
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Hasil Asesmen
            </h2>
            <p class="text-muted mb-0">
                <strong><?= esc($assessment['title'] ?? '-') ?></strong>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
            <a href="<?= base_url('counselor/assessments/' . $assessmentId) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Kembali
            </a>
            <?php if (function_exists('site_url')): ?>
                <a href="<?= site_url('counselor/assessments/'.$assessmentId.'/assign') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Tugaskan
                </a>
                <!-- Sinkronkan penugasan ke results (Assigned) -->
                <form method="post" action="<?= site_url('counselor/assessments/'.$assessmentId.'/assign/sync') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-dark">
                        <i class="fas fa-sync-alt me-2"></i>Sinkronkan Penugasan
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-times-circle me-2"></i>
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Peserta</h6>
                        <h3 class="mb-0"><?= (int)$totalParticipants ?></h3>
                        <small class="text-muted d-block mt-1">
                            Assigned: <strong><?= (int)$assignedCnt ?></strong> • In Progress: <strong><?= (int)$inProgressCnt ?></strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Selesai</h6>
                        <h3 class="mb-0"><?= $completedCnt ?></h3>
                        <small class="text-muted"><?= $pctCompleted ?>%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fas fa-chart-line text-info fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Nilai Rata-rata</h6>
                        <h3 class="mb-0"><?= number_format($avgScore, 1) ?></h3>
                        <small class="text-muted">
                            Range: <?= number_format($lowScore, 1) ?> - <?= number_format($highScore, 1) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-trophy text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Tingkat Kelulusan</h6>
                        <h3 class="mb-0"><?= number_format($passRate, 1) ?>%</h3>
                        <small class="text-muted">
                            <?= (int)$passedCnt ?> lulus / <?= (int)$failedCnt ?> tidak lulus
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= base_url('counselor/assessments/' . $assessmentId . '/results') ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status Pengerjaan</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Assigned"    <?= (($filters['status'] ?? '') === 'Assigned')    ? 'selected' : '' ?>>Assigned (Belum Mulai)</option>
                        <option value="In Progress" <?= (($filters['status'] ?? '') === 'In Progress') ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                        <option value="Completed"   <?= (($filters['status'] ?? '') === 'Completed')   ? 'selected' : '' ?>>Selesai</option>
                        <option value="Graded"      <?= (($filters['status'] ?? '') === 'Graded')      ? 'selected' : '' ?>>Dinilai</option>
                        <option value="Expired"     <?= (($filters['status'] ?? '') === 'Expired')     ? 'selected' : '' ?>>Kadaluarsa</option>
                        <option value="Abandoned"   <?= (($filters['status'] ?? '') === 'Abandoned')   ? 'selected' : '' ?>>Ditinggalkan</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kelas</label>
                    <select name="class_id" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int)$class['id'] ?>" <?= (($filters['class_id'] ?? '') == $class['id']) ? 'selected' : '' ?>>
                                <?= esc($class['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Hasil</label>
                    <select name="is_passed" class="form-select">
                        <option value="">Semua Hasil</option>
                        <option value="1" <?= (($filters['is_passed'] ?? '') === '1') ? 'selected' : '' ?>>Lulus</option>
                        <option value="0" <?= (($filters['is_passed'] ?? '') === '0') ? 'selected' : '' ?>>Tidak Lulus</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Pencarian</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Nama / NIS / NISN..." value="<?= esc($filters['search'] ?? '') ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <small class="text-muted">Gunakan sebagian nama atau nomor.</small>
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Terapkan Filter
                </button>
                <a href="<?= base_url('counselor/assessments/' . $assessmentId . '/results') ?>" class="btn btn-secondary">
                    <i class="fas fa-redo me-2"></i>Reset
                </a>
                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Table + Bulk Revoke -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Hasil Siswa</h5>
            <?php if (!empty($results)): ?>
                <span class="badge bg-primary"><?= count($results) ?> Hasil</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body pt-0">
        <!-- Toolbar Bulk Actions -->
        <div class="d-flex justify-content-between align-items-center py-3">
            <div class="small text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Status <strong>Assigned</strong> berarti ditugaskan dan belum mulai; <strong>Completed</strong> selesai namun belum dinilai; <strong>Graded</strong> sudah dinilai.
            </div>
            <form id="bulkRevokeForm" class="d-flex gap-2" method="post" action="<?= site_url('counselor/assessments/'.$assessmentId.'/assign/revoke') ?>">
                <?= csrf_field() ?>
                <button type="submit" id="btnBulkRevoke" class="btn btn-outline-danger btn-sm" disabled>
                    <i class="fas fa-user-slash me-1"></i> Cabut Penugasan (Assigned)
                </button>
            </form>
        </div>

        <?php if (empty($results)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Belum ada hasil</h5>
                <p class="text-muted">Hasil akan muncul setelah siswa menyelesaikan asesmen</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="resultsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th width="5%">No</th>
                            <th>Nama Siswa</th>
                            <th>NIS / NISN</th>
                            <th>Kelas</th>
                            <th class="text-center">Percobaan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Nilai</th>
                            <th class="text-center">Hasil</th>
                            <th class="text-center">Waktu Selesai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($results as $result): ?>
                            <?php
                                $status      = (string)($result['status'] ?? 'In Progress');
                                $badgeMap    = [
                                    'Assigned'    => 'secondary',
                                    'In Progress' => 'warning',
                                    'Completed'   => 'info',
                                    'Graded'      => 'success',
                                    'Expired'     => 'danger',
                                    'Abandoned'   => 'dark',
                                ];
                                $iconMap     = [
                                    'Assigned'    => 'fa-circle',
                                    'In Progress' => 'fa-spinner',
                                    'Completed'   => 'fa-check',
                                    'Graded'      => 'fa-check-double',
                                    'Expired'     => 'fa-hourglass-end',
                                    'Abandoned'   => 'fa-ban',
                                ];
                                $badgeClass  = $badgeMap[$status] ?? 'secondary';
                                $iconClass   = $iconMap[$status] ?? 'fa-question';
                                $resId       = (int)($result['id'] ?? $result['result_id'] ?? 0);
                                $sid         = (int)($result['student_id'] ?? 0);

                                $perc = isset($result['percentage']) ? (float)$result['percentage'] : null;
                                if ($perc === null && isset($result['total_score'], $result['max_score']) && (float)$result['max_score'] > 0) {
                                    $perc = round(((float)$result['total_score'] / (float)$result['max_score']) * 100, 1);
                                }

                                $isPassedVal = $result['is_passed'] ?? null; // 1/0/null
                                $nisMixed    = $result['nis'] ?? $result['nisn'] ?? '-';
                            ?>
                            <tr>
                                <td>
                                    <?php if ($status === 'Assigned' && $sid > 0): ?>
                                        <input type="checkbox" class="row-check" data-student-id="<?= $sid ?>">
                                    <?php endif; ?>
                                </td>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold">
                                            <?= esc(strtoupper(mb_substr($result['student_name'] ?? '', 0, 2))) ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= esc($result['student_name'] ?? '-') ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-muted"><?= esc($nisMixed) ?></span></td>
                                <td>
                                    <?php if (!empty($result['class_name'])): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <?= esc($result['class_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">#<?= (int)($result['attempt_number'] ?? 1) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <i class="fas <?= $iconClass ?> me-1"></i><?= esc($status) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if (in_array($status, ['Graded'], true) && $perc !== null): ?>
                                        <div class="fw-bold fs-5"><?= number_format($perc, 1) ?>%</div>
                                        <small class="text-muted">
                                            <?= number_format((float)($result['total_score'] ?? 0), 1) ?>
                                            /
                                            <?= number_format((float)($result['max_score'] ?? 0), 1) ?>
                                        </small>
                                    <?php elseif ($status === 'Completed'): ?>
                                        <span class="text-warning">
                                            <i class="fas fa-clock me-1"></i>Menunggu Penilaian
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isPassedVal === 1 || $isPassedVal === '1'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Lulus
                                        </span>
                                    <?php elseif ($isPassedVal === 0 || $isPassedVal === '0'): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Tidak Lulus
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($result['completed_at'])): ?>
                                        <div class="small">
                                            <?= date('d/m/Y', strtotime($result['completed_at'])) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= date('H:i', strtotime($result['completed_at'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($resId > 0): ?>
                                            <a href="<?= site_url("counselor/assessments/{$assessmentId}/results/{$resId}") ?>" class="btn btn-outline-primary" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <!-- Tombol hapus hasil siswa -->
                                            <form action="<?= site_url('counselor/assessments/'.$assessmentId.'/results/'.$resId.'/delete') ?>"
                                                  method="post"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Hapus hasil asesmen siswa ini? Tindakan ini tidak dapat dibatalkan.');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-danger" title="Hapus Hasil">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                            <?php if ($status === 'Completed'): ?>
                                                <a href="<?= site_url("counselor/assessments/{$assessmentId}/results/{$resId}") ?>#review" class="btn btn-warning" title="Nilai Jawaban">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($status === 'Graded'): ?>
                                                <form method="post" action="<?= site_url("counselor/assessments/{$assessmentId}/results/{$resId}/ungrade") ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-outline-secondary" title="Batalkan status Graded">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Charts -->
<?php
    // Siapkan dataset status untuk grafik donat
    $chartStatus = [
        'Assigned'               => $assignedCnt,
        'In Progress'            => $inProgressCnt,
        'Completed / Graded'     => ($statusCounts['Completed'] + $statusCounts['Graded']),
        'Expired'                => $statusCounts['Expired'],
        'Abandoned'              => $statusCounts['Abandoned'],
    ];

    // Siapkan scores graded untuk distribusi
    $gradedScores = [];
    if (!empty($results)) {
        foreach ($results as $r) {
            if (($r['status'] ?? '') === 'Graded') {
                $p = $r['percentage'] ?? null;
                if ($p === null && isset($r['total_score'], $r['max_score']) && (float)$r['max_score'] > 0) {
                    $p = round(((float)$r['total_score'] / (float)$r['max_score']) * 100, 1);
                }
                if ($p !== null) $gradedScores[] = (float)$p;
            }
        }
    }
?>

<?php if (!empty($results)): ?>
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>
                    Distribusi Nilai (Graded)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="scoreDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                    Ringkasan Status
                </h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    // Muat Chart.js jika belum tersedia
    (function ensureChartJs() {
        if (typeof Chart === 'undefined') {
            var s = document.createElement('script');
            s.src = "https://cdn.jsdelivr.net/npm/chart.js";
            s.onload = function(){ document.dispatchEvent(new Event('chartjs-ready')); };
            document.head.appendChild(s);
        } else {
            document.dispatchEvent(new Event('chartjs-ready'));
        }
    })();

    // Export to Excel (HTML table -> xls)
    function slugify(s) {
        return String(s || '')
            .normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^A-Za-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '')
            .toLowerCase();
    }
    function exportToExcel() {
        const table = document.getElementById('resultsTable');
        if (!table) return;

        const title = <?= json_encode((string)($assessment['title'] ?? 'assessment')) ?>;
        const fileName = slugify(title || 'assessment') + '_results.xls';

        const html = `
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>${table.outerHTML}</body>
</html>`.trim();

        const blob = new Blob(["\ufeff", html], { type: "application/vnd.ms-excel;charset=utf-8;" });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function renderCharts() {
        // Data dari PHP
        const gradedScores = <?= json_encode($gradedScores) ?>;
        const statusData   = <?= json_encode($chartStatus) ?>;

        // Distribusi Nilai (bucket)
        if (gradedScores && gradedScores.length && typeof Chart !== 'undefined') {
            const ranges = {'0-20':0,'21-40':0,'41-60':0,'61-80':0,'81-100':0};
            gradedScores.forEach(score => {
                if (score <= 20) ranges['0-20']++;
                else if (score <= 40) ranges['21-40']++;
                else if (score <= 60) ranges['41-60']++;
                else if (score <= 80) ranges['61-80']++;
                else ranges['81-100']++;
            });

            const ctx1 = document.getElementById('scoreDistributionChart');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(ranges),
                        datasets: [{
                            label: 'Jumlah Siswa',
                            data: Object.values(ranges),
                            backgroundColor: [
                                'rgba(220,53,69,0.8)',
                                'rgba(253,126,20,0.8)',
                                'rgba(255,193,7,0.8)',
                                'rgba(13,202,240,0.8)',
                                'rgba(25,135,84,0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }

        // Ringkasan Status (pie/doughnut)
        if (statusData && typeof Chart !== 'undefined') {
            const labels = Object.keys(statusData);
            const values = Object.values(statusData);

            const ctx2 = document.getElementById('statusChart');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                'rgba(108,117,125,0.8)', // Assigned
                                'rgba(255,193,7,0.8)',   // In Progress
                                'rgba(25,135,84,0.8)',   // Completed/Graded
                                'rgba(220,53,69,0.8)',   // Expired
                                'rgba(33,37,41,0.8)',    // Abandoned
                            ]
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: true }
                });
            }
        }
    }

    document.addEventListener('chartjs-ready', renderCharts);
    if (typeof Chart !== 'undefined') renderCharts();

    // Bulk revoke handling
    (function(){
        const checkAll = document.getElementById('checkAll');
        const rowChecks = document.querySelectorAll('.row-check');
        const revokeBtn = document.getElementById('btnBulkRevoke');
        const form = document.getElementById('bulkRevokeForm');

        function updateState(){
            const sel = Array.from(rowChecks).filter(cb => cb.checked);
            revokeBtn.disabled = sel.length === 0;
            // bersihkan input lama
            Array.from(form.querySelectorAll('input[name="student_ids[]"]')).forEach(el => el.remove());
            // tambah input baru
            sel.forEach(cb => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'student_ids[]';
                inp.value = cb.getAttribute('data-student-id') || '';
                form.appendChild(inp);
            });
        }

        if (checkAll) {
            checkAll.addEventListener('change', function(){
                rowChecks.forEach(cb => { cb.checked = checkAll.checked; });
                updateState();
            });
        }
        rowChecks.forEach(cb => cb.addEventListener('change', updateState));
        updateState();
    })();
</script>

<style>
    .avatar-sm { width: 35px; height: 35px; font-size: 0.875rem; }
    .table> :not(caption)>*>* { padding: 0.75rem 0.5rem; }
    .btn-group-sm>.btn { padding: 0.25rem 0.5rem; }
    canvas { max-height: 300px; }
</style>

<?= $this->endSection() ?>
