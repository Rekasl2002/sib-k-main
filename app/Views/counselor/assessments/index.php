<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
// ===== Prefetch peserta per assessment agar akurat dan hemat query =====
$participantMap = [];
try {
    if (!empty($assessments) && is_array($assessments)) {
        $ids = array_values(array_unique(array_map(fn($a) => (int)($a['id'] ?? 0), $assessments)));
        $ids = array_filter($ids, fn($v) => $v > 0);

        if (!empty($ids)) {
            $db = \Config\Database::connect();
            // Hitung peserta dari assessment_results (Assigned, In Progress, Completed, Graded, dll)
            $rows = $db->table('assessment_results')
                ->select('assessment_id, COUNT(DISTINCT student_id) AS cnt')
                ->whereIn('assessment_id', $ids)
                ->where('deleted_at', null)
                ->groupBy('assessment_id')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $participantMap[(int)$r['assessment_id']] = (int)$r['cnt'];
            }
        }
    }
} catch (\Throwable $e) {
    // Jika terjadi error, biarkan participantMap kosong. View akan fallback.
}
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2 class="page-title mb-0">
                <i class="fas fa-clipboard-list me-2"></i>
                Manajemen Asesmen
            </h2>
            <p class="text-muted mb-0">Kelola asesmen psikologi dan minat bakat siswa</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('counselor/assessments/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Buat Asesmen Baru
            </a>
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
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-clipboard-list text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Asesmen</h6>
                        <h3 class="mb-0"><?= (int)($stats['total_assessments'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Dipublikasi</h6>
                        <h3 class="mb-0"><?= (int)($stats['published'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-file-alt text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Draft</h6>
                        <h3 class="mb-0"><?= (int)($stats['draft'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fas fa-users text-info fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Aktif</h6>
                        <h3 class="mb-0"><?= (int)($stats['active'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= base_url('counselor/assessments') ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipe Asesmen</label>
                    <select name="assessment_type" class="form-select">
                        <option value="">Semua Tipe</option>
                        <?php foreach ($assessment_types as $key => $value): ?>
                            <option value="<?= esc($key) ?>" <?= (($filters['assessment_type'] ?? '') == $key) ? 'selected' : '' ?>>
                                <?= esc($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status Publikasi</label>
                    <select name="is_published" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="1" <?= (($filters['is_published'] ?? '') === '1') ? 'selected' : '' ?>>Dipublikasi</option>
                        <option value="0" <?= (($filters['is_published'] ?? '') === '0') ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Target Peserta</label>
                    <select name="target_audience" class="form-select">
                        <option value="">Semua Target</option>
                        <option value="Individual" <?= (($filters['target_audience'] ?? '') == 'Individual') ? 'selected' : '' ?>>Individual</option>
                        <option value="Class" <?= (($filters['target_audience'] ?? '') == 'Class') ? 'selected' : '' ?>>Kelas</option>
                        <option value="Grade" <?= (($filters['target_audience'] ?? '') == 'Grade') ? 'selected' : '' ?>>Tingkat</option>
                        <option value="All" <?= (($filters['target_audience'] ?? '') == 'All') ? 'selected' : '' ?>>Semua</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Pencarian</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari asesmen..." value="<?= esc($filters['search'] ?? '') ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Terapkan Filter
                </button>
                <a href="<?= base_url('counselor/assessments') ?>" class="btn btn-secondary">
                    <i class="fas fa-redo me-2"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Assessments List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Daftar Asesmen</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($assessments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Belum ada asesmen</h5>
                <p class="text-muted">Mulai buat asesmen baru untuk siswa Anda</p>
                <a href="<?= base_url('counselor/assessments/create') ?>" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>Buat Asesmen Pertama
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Judul Asesmen</th>
                            <th>Tipe</th>
                            <th>Target</th>
                            <th class="text-center">Total Soal</th>
                            <th class="text-center">Peserta</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessments as $assessment): ?>
                            <?php
                                $aid = (int)($assessment['id'] ?? 0);
                                $participantCount = (int)($participantMap[$aid] ?? ($assessment['total_participants'] ?? 0));
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center">
                                                <i class="fas fa-clipboard-check text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">
                                                <a href="<?= base_url('counselor/assessments/' . $aid) ?>" class="text-dark text-decoration-none">
                                                    <?= esc($assessment['title']) ?>
                                                </a>
                                            </h6>
                                            <?php if (!empty($assessment['description'])): ?>
                                                <small class="text-muted">
                                                    <?= esc(substr($assessment['description'], 0, 60)) ?>
                                                    <?= strlen($assessment['description']) > 60 ? '...' : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info">
                                        <?= esc($assessment['assessment_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $targetIcon = [
                                        'Individual' => 'fa-user',
                                        'Class'      => 'fa-users',
                                        'Grade'      => 'fa-graduation-cap',
                                        'All'        => 'fa-globe'
                                    ];
                                    ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        <i class="fas <?= $targetIcon[$assessment['target_audience']] ?? 'fa-users' ?> me-1"></i>
                                        <?= esc($assessment['target_audience']) ?>
                                    </span>
                                    <?php if (!empty($assessment['target_class_name'])): ?>
                                        <br>
                                        <small class="text-muted"><?= esc($assessment['target_class_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?= (int)$assessment['total_questions'] ?></span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-success rounded-pill"><?= $participantCount ?></span>
                                </td>

                                <td class="text-center">
                                    <?php if (!empty($assessment['is_published'])): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Published
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-file-alt me-1"></i>Draft
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($assessment['is_active'])): ?>
                                        <br>
                                        <span class="badge bg-info mt-1">
                                            <i class="fas fa-circle me-1"></i>Active
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('counselor/assessments/' . $aid) ?>">
                                                    <i class="fas fa-eye me-2"></i>Lihat Detail
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('counselor/assessments/' . $aid . '/questions') ?>">
                                                    <i class="fas fa-question-circle me-2"></i>Kelola Soal
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('counselor/assessments/' . $aid . '/results') ?>">
                                                    <i class="fas fa-chart-bar me-2"></i>Lihat Hasil
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('counselor/assessments/' . $aid . '/edit') ?>">
                                                    <i class="fas fa-edit me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('counselor/assessments/' . $aid . '/assign') ?>">
                                                    <i class="fas fa-user-plus me-2"></i>Tugaskan
                                                </a>
                                            </li>

                                            <!-- ACTIONS THAT REQUIRE POST -->
                                            <?php if (empty($assessment['is_published'])): ?>
                                                <li>
                                                    <form method="post" action="<?= base_url('counselor/assessments/' . $aid . '/publish') ?>" onsubmit="return confirm('Publikasikan asesmen ini?')">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-share me-2"></i>Publikasi
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <form method="post" action="<?= base_url('counselor/assessments/' . $aid . '/unpublish') ?>" onsubmit="return confirm('Nonaktifkan publikasi asesmen ini?')">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-times-circle me-2"></i>Unpublish
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>

                                            <li>
                                                <form method="post" action="<?= base_url('counselor/assessments/' . $aid . '/duplicate') ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-copy me-2"></i>Duplikat
                                                    </button>
                                                </form>
                                            </li>

                                            <li><hr class="dropdown-divider"></li>

                                            <li>
                                                <form method="post" action="<?= base_url('counselor/assessments/' . $aid . '/delete') ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus asesmen ini? Semua data terkait akan terhapus.')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash me-2"></i>Hapus
                                                    </button>
                                                </form>
                                            </li>
                                            <!-- END POST ACTIONS -->
                                        </ul>
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

<script>
    // Auto dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>

<style>
    .avatar-sm { width: 40px; height: 40px; }
    .table> :not(caption)>*>* { padding: 1rem 0.75rem; }
    .card { transition: transform 0.2s; }
    .card:hover { transform: translateY(-2px); }
</style>

<?= $this->endSection() ?>
