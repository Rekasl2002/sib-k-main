<?php // app/Views/homeroom_teacher/violations/detail.php ?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * View: Homeroom Teacher • Violation Detail
 * Tampilan detail pelanggaran siswa untuk Wali Kelas
 *
 * Data yang diharapkan:
 * - $violation   : array detail pelanggaran (lihat getViolationDetail())
 * - $sanctions   : array sanksi terkait (lihat getViolationSanctions())
 * - $studentHistory : array riwayat pelanggaran siswa (getStudentViolationHistory())
 * - $class       : info kelas perwalian
 * - $homeroom_class : alias kelas
 */

// Helpers kecil
if (!function_exists('rowa')) {
    function rowa($r): array
    {
        return is_array($r)
            ? $r
            : (is_object($r) ? (array)$r : []);
    }
}

if (!function_exists('h')) {
    function h($v)
    {
        return esc($v ?? '');
    }
}

// Normalisasi data
$violation      = rowa($violation ?? []);
$sanctions      = $sanctions ?? [];
$studentHistory = $studentHistory ?? [];
$homeroomClass  = rowa($homeroom_class ?? $class ?? []);

// Badge status kasus
$status = (string)($violation['status'] ?? '');
$statusBadgeClass = match ($status) {
    'Dilaporkan'      => 'bg-info',
    'Dalam Proses'    => 'bg-warning',
    'Selesai'         => 'bg-success',
    'Dibatalkan'      => 'bg-secondary',
    default           => 'bg-light text-dark',
};

// Badge tingkat pelanggaran
$severity = (string)($violation['severity_level'] ?? '');
$severityBadgeClass = match ($severity) {
    'Ringan' => 'bg-success',
    'Sedang' => 'bg-warning',
    'Berat'  => 'bg-danger',
    default  => 'bg-secondary',
};

// Total poin siswa
$totalPoints = (int)($violation['student_total_points'] ?? 0);

// Decode evidence (jika ada)
$evidenceFiles = [];
if (!empty($violation['evidence'])) {
    $decoded = json_decode((string)$violation['evidence'], true);
    if (is_array($decoded)) {
        $evidenceFiles = $decoded;
    }
}

// Helper URL bukti
if (!function_exists('violationEvidenceUrl')) {
    function violationEvidenceUrl(string $path): string
    {
        $path = ltrim($path, '/');
        return base_url($path);
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <?= esc($pageTitle ?? $title ?? 'Detail Pelanggaran Siswa') ?>
            </h4>

            <?php if (!empty($breadcrumbs ?? [])): ?>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <?php foreach ($breadcrumbs as $bc): ?>
                            <li class="breadcrumb-item<?= !empty($bc['active']) ? ' active' : '' ?>">
                                <?php if (!empty($bc['url']) && empty($bc['active'])): ?>
                                    <a href="<?= esc($bc['url']) ?>"><?= esc($bc['title']) ?></a>
                                <?php else: ?>
                                    <?= esc($bc['title']) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Alert sederhana (tanpa form edit seperti Guru BK) -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <i class="mdi mdi-alert-circle-outline me-2"></i>
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <i class="mdi mdi-check-circle-outline me-2"></i>
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Kolom kiri: detail utama pelanggaran -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div>
                        <h4 class="card-title mb-0">
                            Detail Pelanggaran
                        </h4>
                        <small class="text-light-50">
                            ID Kasus: #<?= esc($violation['id'] ?? '-') ?>
                        </small>
                    </div>
                    <div class="mt-2 mt-md-0 text-md-end">
                        <span class="badge <?= $statusBadgeClass ?> me-1">
                            Status: <?= h($status ?: 'Tidak Diketahui') ?>
                        </span>
                        <?php if (!empty($severity)): ?>
                            <span class="badge <?= $severityBadgeClass ?>">
                                Tingkat: <?= h($severity) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Info siswa -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3">
                        <i class="mdi mdi-account-school-outline me-2"></i>
                        Informasi Siswa
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Nama Siswa</p>
                            <h5 class="mb-0"><?= h($violation['student_name'] ?? '-') ?></h5>
                            <small class="text-muted">NISN: <?= h($violation['nisn'] ?? '-') ?></small>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Kelas Perwalian</p>
                            <h6 class="mb-0">
                                <?= h($homeroomClass['class_name'] ?? '-') ?>
                            </h6>
                            <?php if (!empty($homeroomClass['year_name'])): ?>
                                <small class="text-muted">
                                    Tahun: <?= h($homeroomClass['year_name']) ?>,
                                    Semester: <?= h($homeroomClass['semester'] ?? '-') ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Info pelanggaran -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3">
                        <i class="mdi mdi-alert-octagon-outline me-2"></i>
                        Informasi Pelanggaran
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Kategori Pelanggaran</p>
                            <h6 class="mb-0"><?= h($violation['category_name'] ?? '-') ?></h6>
                            <?php if (!empty($severity)): ?>
                                <span class="badge <?= $severityBadgeClass ?> mt-1">
                                    <?= h($severity) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($violation['point_deduction'])): ?>
                                <div>
                                    <small class="text-muted">
                                        Poin Pengurang: -<?= (int)$violation['point_deduction'] ?> poin
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Tanggal & Waktu Kejadian</p>
                            <h6 class="mb-0">
                                <?php if (!empty($violation['violation_date'])): ?>
                                    <?= date('d F Y', strtotime($violation['violation_date'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </h6>
                            <?php if (!empty($violation['violation_time'])): ?>
                                <small class="text-muted">
                                    Pukul <?= date('H:i', strtotime($violation['violation_time'])) ?> WIB
                                </small>
                            <?php endif; ?>
                            <?php if (!empty($violation['location'])): ?>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        Lokasi: <?= h($violation['location']) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3">
                        <i class="mdi mdi-text-box-outline me-2"></i>
                        Deskripsi Pelanggaran
                    </h5>
                    <p class="text-muted mb-0">
                        <?= nl2br(h($violation['description'] ?? '-')) ?>
                    </p>
                </div>

                <!-- Saksi & Bukti -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3">
                        <i class="mdi mdi-account-eye-outline me-2"></i>
                        Saksi & Bukti
                    </h5>
                    <div class="row g-3">
                        <!-- Saksi -->
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Saksi</p>
                            <?php if (!empty($violation['witness'])): ?>
                                <h6 class="mb-0"><?= h($violation['witness']) ?></h6>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada saksi dicatat.</span>
                            <?php endif; ?>
                        </div>

                        <!-- Bukti -->
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Bukti Terlampir</p>
                            <?php if (!empty($evidenceFiles)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($evidenceFiles as $file): ?>
                                        <?php
                                        $url = violationEvidenceUrl((string)$file);
                                        $name = basename((string)$file);
                                        ?>
                                        <li class="mb-1">
                                            <i class="mdi mdi-paperclip me-1"></i>
                                            <a href="<?= esc($url) ?>" target="_blank" rel="noopener"
                                               class="text-decoration-underline">
                                                Lihat bukti
                                            </a>
                                            <small class="text-muted ms-2"><?= esc($name) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada bukti terlampir.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sanksi yang Diberikan (dipindah ke bawah detail pelanggaran) -->
                <div class="mb-4 pb-3 border-bottom">
                    <h5 class="mb-3">
                        <i class="mdi mdi-gavel me-2"></i>
                        Sanksi yang Diberikan
                    </h5>

                    <?php if (!empty($sanctions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis Sanksi</th>
                                        <th>Deskripsi</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sanctions as $sanction): ?>
                                        <?php
                                        $sanction = rowa($sanction);
                                        $sanctionStatus = (string)($sanction['status'] ?? '');
                                        $sanctionBadge = match ($sanctionStatus) {
                                            'Dijadwalkan'     => 'bg-info',
                                            'Sedang Berjalan' => 'bg-warning',
                                            'Selesai'         => 'bg-success',
                                            default           => 'bg-secondary',
                                        };
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= h($sanction['sanction_type'] ?? '-') ?></strong>
                                                <?php if (!empty($sanction['duration_days'])): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Durasi: <?= (int)$sanction['duration_days'] ?> hari
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($sanction['assigned_by_name'])): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Oleh: <?= h($sanction['assigned_by_name']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($sanction['description'])): ?>
                                                    <span class="d-block">
                                                        <?= nl2br(h($sanction['description'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada deskripsi sanksi.</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($sanction['sanction_date'])): ?>
                                                    <?= date('d/m/Y', strtotime($sanction['sanction_date'])) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $sanctionBadge ?>">
                                                    <?= h($sanctionStatus ?: '-') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Belum ada sanksi yang tercatat untuk pelanggaran ini.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Catatan Penyelesaian (read-only untuk Wali Kelas) -->
                <?php if (!empty($violation['resolution_notes'])): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3">
                            <i class="mdi mdi-file-document-outline me-2"></i>
                            Catatan Penyelesaian
                        </h5>
                        <p class="text-muted mb-0"><?= nl2br(h($violation['resolution_notes'])) ?></p>
                        <?php if (!empty($violation['resolution_date'])): ?>
                            <small class="text-muted d-block mt-1">
                                <i class="mdi mdi-calendar-check me-1"></i>
                                Diselesaikan: <?= date('d M Y', strtotime($violation['resolution_date'])) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Info pelapor & penangan -->
                <div class="mb-0">
                    <h5 class="mb-3">
                        <i class="mdi mdi-account-group-outline me-2"></i>
                        Informasi Penanggung Jawab
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Dilaporkan oleh</p>
                            <p class="mb-0">
                                <strong><?= h($violation['reported_by_name'] ?? '-') ?></strong>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Ditangani oleh Guru BK</p>
                            <p class="mb-0">
                                <strong><?= h($violation['handled_by_name'] ?? 'Belum ditetapkan') ?></strong>
                            </p>
                        </div>
                    </div>
                </div>

            </div> <!-- card-body -->
        </div> <!-- card -->

        <a href="<?= base_url('homeroom/violations') ?>" class="btn btn-light">
            <i class="mdi mdi-arrow-left"></i> Kembali ke Daftar Pelanggaran
        </a>
    </div>

    <!-- Kolom kanan: ringkasan & riwayat (tanpa card sanksi lagi) -->
    <div class="col-lg-4">
        <!-- Ringkasan Kasus -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    Ringkasan Kasus
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title bg-soft-danger text-danger rounded-circle">
                            <i class="mdi mdi-scale-balance"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-muted mb-1">Status</p>
                        <span class="badge <?= $statusBadgeClass ?>">
                            <?= h($status ?: 'Tidak Diketahui') ?>
                        </span>
                    </div>
                </div>

                <p class="text-muted mb-1">Tanggal Dilaporkan</p>
                <p class="mb-2">
                    <?php if (!empty($violation['created_at'])): ?>
                        <?= date('d M Y H:i', strtotime($violation['created_at'])) ?> WIB
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </p>

                <?php if (!empty($violation['updated_at'])): ?>
                    <p class="text-muted mb-1">Terakhir Diperbarui</p>
                    <p class="mb-2">
                        <?= date('d M Y H:i', strtotime($violation['updated_at'])) ?> WIB
                    </p>
                <?php endif; ?>

                <p class="text-muted mb-1">Total Poin Pelanggaran Siswa</p>
                <h5 class="mb-0">
                    <?= $totalPoints ?> poin
                </h5>
                <small class="text-muted">
                    Diakumulasi dari seluruh pelanggaran yang tercatat.
                </small>
            </div>
        </div>

        <!-- Riwayat Pelanggaran Siswa -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    Riwayat Pelanggaran Siswa
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($studentHistory)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($studentHistory as $hist): ?>
                            <?php
                            $hist = rowa($hist);
                            $sev  = (string)($hist['severity_level'] ?? '');
                            $histBadge = match ($sev) {
                                'Ringan' => 'bg-success',
                                'Sedang' => 'bg-warning',
                                'Berat'  => 'bg-danger',
                                default  => 'bg-secondary',
                            };
                            ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= h($hist['category_name'] ?? '-') ?></h6>
                                        <p class="mb-0 small text-muted">
                                            <?php if (!empty($hist['violation_date'])): ?>
                                                <?= date('d/m/Y', strtotime($hist['violation_date'])) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                            <?php if (!empty($hist['status'])): ?>
                                                · Status: <?= h($hist['status']) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="badge <?= $histBadge ?>">
                                        <?= h($sev ?: '-') ?>
                                    </span>
                                </div>
                                <?php if (!empty($hist['point_deduction'])): ?>
                                    <small class="text-muted">
                                        Poin: -<?= (int)$hist['point_deduction'] ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        Belum ada riwayat pelanggaran lain yang tercatat untuk siswa ini.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
