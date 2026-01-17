<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/classes/detail.php
 *
 * Admin • Class Detail View
 * - Menampilkan detail kelas + info wali kelas/guru BK + daftar siswa
 * - Tidak menampilkan ID user (tampilkan nama lengkap)
 */

// Helpers kecil biar view tahan banting
if (!function_exists('v')) {
    function v($arr, string $key, $default = '-')
    {
        if (is_array($arr) && array_key_exists($key, $arr) && $arr[$key] !== null && $arr[$key] !== '') {
            return $arr[$key];
        }
        return $default;
    }
}

if (!function_exists('badgeStatus')) {
    function badgeStatus($isActive)
    {
        $isActive = (string)$isActive;
        if ($isActive === '1' || strtolower($isActive) === 'aktif') {
            return '<span class="badge bg-success">Aktif</span>';
        }
        return '<span class="badge bg-secondary">Nonaktif</span>';
    }
}

$homeroomName  = v($class, 'homeroom_name', '-');
$counselorName = v($class, 'counselor_name', '-');

$yearName   = v($class, 'year_name', '-');
$semester   = v($class, 'semester', '-');
$gradeLevel = v($class, 'grade_level', '-');
$major      = v($class, 'major', '-');

$studentCount = (int) (v($class, 'student_count', 0));
$maxStudents  = v($class, 'max_students', null);
$isActive     = v($class, 'is_active', '1');

$genderStats = v($class, 'gender_stats', ['L' => 0, 'P' => 0]);
$maleCount   = is_array($genderStats) ? (int)($genderStats['L'] ?? 0) : 0;
$femaleCount = is_array($genderStats) ? (int)($genderStats['P'] ?? 0) : 0;
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">DETAIL KELAS</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/classes') ?>">Kelas</a></li>
                    <li class="breadcrumb-item active">Detail Kelas</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- INFO KELAS -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h4 class="card-title mb-1">Informasi Kelas</h4>
                        <div class="text-muted">Ringkasan data kelas dan periode akademik</div>
                    </div>
                    <div class="text-end">
                        <?= badgeStatus($isActive) ?>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="p-3 border rounded">
                            <div class="text-muted small">Nama Kelas</div>
                            <div class="fw-semibold fs-5"><?= esc(v($class, 'class_name', '-')) ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <div class="text-muted small">Tingkat</div>
                            <div class="fw-semibold"><?= esc($gradeLevel) ?></div>

                            <div class="text-muted small mt-2">Jurusan</div>
                            <div class="fw-semibold"><?= esc($major) ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <div class="text-muted small">Tahun Ajaran</div>
                            <div class="fw-semibold"><?= esc($yearName) ?></div>

                            <div class="text-muted small mt-2">Semester</div>
                            <div class="fw-semibold"><?= esc($semester) ?></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="p-3 border rounded">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="text-muted small">Jumlah Siswa</div>
                                    <div class="fw-semibold fs-4"><?= esc($studentCount) ?></div>
                                    <?php if ($maxStudents !== null && $maxStudents !== '-' && $maxStudents !== ''): ?>
                                        <div class="text-muted small">Kapasitas: <?= esc($maxStudents) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-8">
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-primary">L: <?= esc($maleCount) ?></span>
                                        <span class="badge bg-warning text-dark">P: <?= esc($femaleCount) ?></span>
                                        <span class="badge bg-info text-dark">Total: <?= esc($studentCount) ?></span>
                                    </div>
                                    <div class="text-muted small mt-2">
                                        Distribusi gender (berdasarkan siswa aktif & tidak terhapus).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="<?= base_url('admin/classes') ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Kembali
                    </a>
                    <a href="<?= base_url('admin/classes/edit/' . v($class, 'id', '')) ?>" class="btn btn-primary">
                        <i class="bx bx-edit-alt me-1"></i> Edit Kelas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- INFO STAF (WALI & BK) -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Penanggung Jawab</h4>
                <div class="text-muted mb-3">Wali Kelas dan Guru BK untuk kelas ini</div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="p-3 border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Wali Kelas</div>
                                    <div class="fw-semibold"><?= esc($homeroomName) ?></div>
                                    <div class="small text-muted">
                                        <?= esc(v($class, 'homeroom_email', '')) ?>
                                        <?php if (v($class, 'homeroom_phone', '') !== ''): ?>
                                            • <?= esc(v($class, 'homeroom_phone', '')) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark border">
                                    <i class="bx bx-user me-1"></i> Wali
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="p-3 border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Guru BK</div>
                                    <div class="fw-semibold"><?= esc($counselorName) ?></div>
                                    <div class="small text-muted">
                                        <?= esc(v($class, 'counselor_email', '')) ?>
                                        <?php if (v($class, 'counselor_phone', '') !== ''): ?>
                                            • <?= esc(v($class, 'counselor_phone', '')) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark border">
                                    <i class="bx bx-shield-quarter me-1"></i> BK
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- DAFTAR SISWA -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h4 class="card-title mb-1">Daftar Siswa</h4>
                        <div class="text-muted">Siswa aktif di kelas ini</div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-dark">Total: <?= esc($studentCount) ?></span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th style="width: 160px;">NIS</th>
                                <th>Nama Lengkap</th>
                                <th style="width: 110px;">Gender</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 220px;">Kontak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada siswa pada kelas ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                        $nis      = v($student, 'nis', '-');
                                        $name     = v($student, 'full_name', '-'); // dari join users.full_name
                                        $gender   = v($student, 'gender', '-');
                                        $status   = v($student, 'status', '-');
                                        $email    = v($student, 'email', '');
                                        $phone    = v($student, 'phone', '');
                                        $genderBadge = $gender === 'L'
                                            ? '<span class="badge bg-primary">L</span>'
                                            : ($gender === 'P'
                                                ? '<span class="badge bg-warning text-dark">P</span>'
                                                : '<span class="badge bg-secondary">-</span>');
                                        $statusBadge = strtolower((string)$status) === 'aktif'
                                            ? '<span class="badge bg-success">Aktif</span>'
                                            : '<span class="badge bg-secondary">' . esc($status) . '</span>';
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= esc($no++) ?></td>
                                        <td><?= esc($nis) ?></td>
                                        <td class="fw-semibold">
                                            <?= esc($name) ?>
                                        </td>
                                        <td class="text-center"><?= $genderBadge ?></td>
                                        <td class="text-center"><?= $statusBadge ?></td>
                                        <td class="small">
                                            <?php if ($email !== ''): ?>
                                                <div><i class="bx bx-envelope me-1"></i><?= esc($email) ?></div>
                                            <?php endif; ?>
                                            <?php if ($phone !== ''): ?>
                                                <div><i class="bx bx-phone me-1"></i><?= esc($phone) ?></div>
                                            <?php endif; ?>
                                            <?php if ($email === '' && $phone === ''): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <a href="<?= base_url('admin/classes') ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
