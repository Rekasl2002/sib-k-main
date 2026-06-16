<?php // app/Views/homeroom_teacher/class/my_class.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helpers ringan supaya view tahan banting untuk array/objek & badge-status
helper('permission');

if (! function_exists('rowa')) {
    function rowa($r): array
    {
        return is_array($r) ? $r : (is_object($r) ? (array) $r : []);
    }
}
if (! function_exists('h')) {
    function h($v)
    {
        return esc($v ?? '');
    }
}
if (! function_exists('genderLabel')) {
    function genderLabel(?string $g): string
    {
        if ($g === 'L') return 'Laki-laki';
        if ($g === 'P') return 'Perempuan';
        return '-';
    }
}
if (! function_exists('genderBadgeClass')) {
    function genderBadgeClass(?string $g): string
    {
        $g = strtoupper((string) $g);
        return $g === 'L'
            ? 'badge bg-primary text-white'
            : ($g === 'P'
                ? 'badge bg-info text-white'
                : 'badge bg-light text-muted');
    }
}
if (! function_exists('statusBadgeClass')) {
    function statusBadgeClass(?string $status): string
    {
        $s = strtolower(trim((string) $status));
        return match (true) {
            str_contains($s, 'aktif')      => 'badge bg-success',
            str_contains($s, 'non')        => 'badge bg-secondary',
            str_contains($s, 'dilaporkan') => 'badge bg-warning',
            str_contains($s, 'proses')     => 'badge bg-info',
            str_contains($s, 'selesai')    => 'badge bg-success',
            str_contains($s, 'batal')      => 'badge bg-dark',
            default                        => 'badge bg-light text-muted',
        };
    }
}
// Normalisasi data utama
$pageTitle            = $pageTitle ?? 'Kelas Perwalian Saya';
$class                = rowa($class ?? null);
$activeYear           = rowa($activeYear ?? null);
$stats                = rowa($stats ?? null);
$students             = $students ?? [];
$isMultipleClass      = ! empty($class['is_multiple']);

$studentCount = is_array($students) ? count($students) : 0;
?>

<div class="container-fluid">

    <!-- Page Title + Breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">
                        <i class="bi bi-people me-1"></i>
                        <?= esc($pageTitle) ?>
                    </h4>

                    <?php if (! empty($class)) : ?>
                        <div class="text-muted small mt-1">
                            <?= $isMultipleClass ? esc(($class['class_count'] ?? 0) . ' kelas: ' . ($class['class_name'] ?? '-')) : esc($class['class_name'] ?? '-') ?>
                            <?php if (! empty($class['grade_level'])) : ?>
                                · Kelas <?= esc($class['grade_level']) ?>
                            <?php endif; ?>
                            <?php if (! empty($class['major'])) : ?>
                                · <?= esc($class['major']) ?>
                            <?php endif; ?>
                            · <span class="fw-semibold"><?= (int) $studentCount ?></span> siswa aktif
                        </div>
                    <?php endif; ?>
                </div>

                <div class="page-title-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    <?php if (! empty($activeYear)) : ?>
                        <span class="badge bg-primary text-white fw-semibold">
                            Tahun Ajaran:
                            <?= esc($activeYear['year_name'] ?? '-') ?>
                            (<?= esc($activeYear['semester'] ?? '-') ?>)
                        </span>
                    <?php endif; ?>

                    <?php if (function_exists('has_permission') && has_permission('manage_students')) : ?>
                        <a href="<?= site_url('homeroom/students/create') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-person-plus me-1"></i>Tambah Siswa
                        </a>
                        <a href="<?= site_url('homeroom/parents') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-people me-1"></i>Akun Orang Tua
                        </a>
                    <?php endif; ?>

                    <?php if (function_exists('has_permission') && has_permission('import_export_data')) : ?>
                        <a href="<?= site_url('homeroom/students/import') ?>" class="btn btn-info btn-sm text-white">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i>Impor
                        </a>
                        <a href="<?= site_url('homeroom/students/export') ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Ekspor
                        </a>
                    <?php endif; ?>

                    <ol class="breadcrumb m-0 ms-2">
                        <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Kelas</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $flashKey => $flashClass) : ?>
        <?php if (session()->getFlashdata($flashKey)) : ?>
            <div class="alert alert-<?= esc($flashClass, 'attr') ?> alert-dismissible fade show" role="alert">
                <?= esc(session()->getFlashdata($flashKey)) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($class)) : ?>
        <div class="alert alert-warning shadow-sm border-0">
            <div class="d-flex">
                <div class="me-3">
                    <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                </div>
                <div>
                    <h5 class="alert-heading mb-1">Belum ada kelas perwalian aktif</h5>
                    <p class="mb-0 small">
                        Anda belum terhubung dengan kelas manapun pada tahun ajaran aktif.
                        Silakan hubungi Koordinator BK untuk mengatur kelas perwalian Anda.
                    </p>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="row g-3">
            <div class="col-12 col-xl-8">

                <!-- Ringkasan (Info + Statistik) -->
                <div class="row g-3 mb-3">

                    <!-- Info kelas -->
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Info Kelas</h5>
                                <ul class="list-unstyled mb-0 small">
                                    <li class="mb-2">
                                        <span class="text-muted">Nama Kelas</span><br>
                                        <span class="fw-semibold">
                                            <?= $isMultipleClass ? esc(($class['class_count'] ?? 0) . ' kelas: ' . ($class['class_name'] ?? '-')) : esc($class['class_name'] ?? '-') ?>
                                        </span>
                                    </li>
                                    <li class="mb-2">
                                        <span class="text-muted">Tingkat</span><br>
                                        <span class="fw-semibold"><?= esc($class['grade_level'] ?? '-') ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <span class="text-muted">Program/Konsentrasi</span><br>
                                        <span class="fw-semibold"><?= esc($class['major'] ?? '-') ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <span class="text-muted">Wali Kelas</span><br>
                                        <span class="fw-semibold"><?= esc($class['homeroom_name'] ?? '-') ?></span>
                                    </li>
                                    <li class="mb-0">
                                        <span class="text-muted">Guru BK Pendamping</span><br>
                                        <span class="fw-semibold"><?= esc($class['counselor_name'] ?? '-') ?></span>
                                    </li>

                                    <?php if (isset($class['max_students'])) : ?>
                                        <li class="mt-2">
                                            <span class="text-muted">Kuota</span><br>
                                            <span class="fw-semibold"><?= (int) ($class['max_students'] ?? 0) ?> siswa</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Statistik kelas -->
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Statistik Kelas</h5>

                                <?php if (! empty($stats) && (int) ($stats['total_students'] ?? 0) > 0) : ?>
                                    <div class="row text-center mb-3 gy-2">
                                        <div class="col-6">
                                            <div class="fw-semibold fs-4">
                                                <?= (int) ($stats['total_students'] ?? 0) ?>
                                            </div>
                                            <div class="text-muted small">Siswa Aktif</div>
                                        </div>
                                        <div class="col-3 border-start">
                                            <div class="fw-semibold">
                                                <?= (int) ($stats['total_male'] ?? 0) ?>
                                            </div>
                                            <div class="text-muted small">L</div>
                                        </div>
                                        <div class="col-3 border-start">
                                            <div class="fw-semibold">
                                                <?= (int) ($stats['total_female'] ?? 0) ?>
                                            </div>
                                            <div class="text-muted small">P</div>
                                        </div>
                                    </div>

                                    <?php
                                    ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar"
                                                 role="progressbar"
                                                 aria-valuemin="0"
                                                 aria-valuemax="50">
                                            </div>
                                        </div>
                                        <div class="fw-semibold small">
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <p class="text-muted mb-0 small">
                                        Belum ada data statistik. Pastikan data siswa di kelas ini sudah lengkap.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Tabel siswa -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                            <div>
                                <h5 class="card-title mb-0">Daftar Siswa</h5>
                                <div class="text-muted small">Menampilkan siswa berstatus <span class="fw-semibold">Aktif</span></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="input-group input-group-sm" style="min-width: 220px;">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" id="studentSearch" class="form-control"
                                           placeholder="Cari nama / NIK / NISN">
                                </div>
                                <select id="studentFilterGender" class="form-select form-select-sm" style="min-width: 140px;">
                                    <option value="">Semua</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>

                        <?php if (empty($students)) : ?>
                            <p class="text-muted mb-0">
                                Belum ada data siswa aktif untuk kelas ini.
                            </p>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="studentsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Nama</th>
                                            <th class="text-nowrap">NISN</th>
                                            <th class="text-center">JK</th>
                                            <th>Orang Tua</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" style="width: 260px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $i => $s) :
                                            $s = rowa($s);
                                            $searchText = strtolower(
                                                trim(
                                                    ($s['full_name'] ?? '') . ' ' .
                                                    ($s['nik'] ?? '') . ' ' .
                                                    ($s['nisn'] ?? '') . ' ' .
                                                    ($s['class_name'] ?? '') . ' ' .
                                                    ($s['parent_name'] ?? '')
                                                )
                                            );
                                        ?>
                                            <tr
                                                data-gender="<?= esc($s['gender'] ?? '', 'attr') ?>"
                                                data-search="<?= esc($searchText, 'attr') ?>"
                                            >
                                                <td><?= $i + 1 ?></td>
                                                <td>
                                                    <div class="fw-semibold">
                                                        <?= esc($s['full_name'] ?? '-') ?>
                                                    </div>
                                                    <?php if ($isMultipleClass): ?>
                                                        <small class="text-muted">
                                                            <?= esc($s['class_name'] ?? '-') ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <!-- ✅ DIHAPUS: tampilan ID (internal) -->
                                                </td>
                                                <td class="text-nowrap small">
                                                    <div>
                                                        NIK:
                                                        <span class="fw-semibold"><?= esc($s['nik'] ?? '-') ?></span>
                                                    </div>
                                                    <div>
                                                        NISN:
                                                        <span class="fw-semibold"><?= esc($s['nisn'] ?? '-') ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="<?= genderBadgeClass($s['gender'] ?? null) ?>">
                                                        <?= genderLabel($s['gender'] ?? null) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (! empty($s['parent_id'])) : ?>
                                                        <div class="fw-semibold"><?= esc($s['parent_name'] ?? 'Orang tua/wali') ?></div>
                                                        <small class="text-muted"><?= esc($s['parent_phone'] ?? $s['parent_email'] ?? '-') ?></small>
                                                    <?php else : ?>
                                                        <span class="text-muted">Belum terhubung</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="<?= statusBadgeClass($s['status'] ?? '') ?>">
                                                        <?= esc($s['status'] ?? '-') ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                                        <a href="<?= site_url('homeroom/students/' . (int) ($s['id'] ?? 0)) ?>"
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-person-lines-fill"></i>
                                                            <span class="d-none d-xl-inline">Detail</span>
                                                        </a>
                                                        <?php if (function_exists('has_permission') && has_permission('manage_students')) : ?>
                                                            <a href="<?= site_url('homeroom/students/edit/' . (int) ($s['id'] ?? 0)) ?>"
                                                               class="btn btn-sm btn-outline-secondary">
                                                                <i class="bi bi-pencil-square"></i>
                                                                <span class="d-none d-xl-inline">Edit</span>
                                                            </a>
                                                            <?php if (! empty($s['parent_id'])) : ?>
                                                                <a href="<?= site_url('homeroom/parents/' . (int) ($s['parent_id'] ?? 0)) ?>"
                                                                   class="btn btn-sm btn-outline-info">
                                                                    <i class="bi bi-people"></i>
                                                                    <span class="d-none d-xl-inline">Orang Tua</span>
                                                                </a>
                                                            <?php else : ?>
                                                                <a href="<?= site_url('homeroom/students/edit/' . (int) ($s['id'] ?? 0)) ?>"
                                                                   class="btn btn-sm btn-outline-info">
                                                                    <i class="bi bi-link-45deg"></i>
                                                                    <span class="d-none d-xl-inline">Hubungkan</span>
                                                                </a>
                                                            <?php endif; ?>
                                                            <form action="<?= site_url('homeroom/students/delete/' . (int) ($s['id'] ?? 0)) ?>"
                                                                  method="post"
                                                                  class="d-inline"
                                                                  onsubmit="return confirm('Hapus siswa ini dari kelas binaan?')">
                                                                <?= csrf_field() ?>
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="bi bi-trash"></i>
                                                                    <span class="d-none d-xl-inline">Hapus</span>
                                                                </button>
                                                            </form>
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

            </div>

        </div>
    <?php endif; ?>
</div>

<!-- Filter tabel siswa (client-side) -->
<script>
(function () {
    const searchInput  = document.getElementById('studentSearch');
    const genderFilter = document.getElementById('studentFilterGender');
    const rows         = document.querySelectorAll('#studentsTable tbody tr');

    if (!rows.length) return;

    function applyFilter() {
        const term   = (searchInput && searchInput.value ? searchInput.value : '').toLowerCase();
        const gender = (genderFilter && genderFilter.value ? genderFilter.value.toLowerCase() : '');

        rows.forEach(function (row) {
            const rowGender  = (row.getAttribute('data-gender') || '').toLowerCase();
            const searchText = (row.getAttribute('data-search') || '').toLowerCase();

            const matchTerm   = !term || searchText.indexOf(term) !== -1;
            const matchGender = !gender || rowGender === gender;

            row.style.display = (matchTerm && matchGender) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    if (genderFilter) genderFilter.addEventListener('change', applyFilter);
})();
</script>

<?= $this->endSection() ?>
