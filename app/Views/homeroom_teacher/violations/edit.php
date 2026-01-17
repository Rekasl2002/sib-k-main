<?php // app/Views/homeroom_teacher/violations/edit.php ?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/** @var array|null $class */
/** @var array|null $homeroom_class */
/** @var array $groupedCategories */
/** @var array $violation */

if (!function_exists('rowa')) {
    function rowa($r): array {
        return is_array($r) ? $r : (is_object($r) ? (array)$r : []);
    }
}
if (!function_exists('h')) {
    function h($v) {
        return esc($v ?? '');
    }
}

/**
 * Normalisasi path relatif untuk bukti (biar stabil antara slash/backslash).
 * (View-only helper, controller tetap yang menentukan valid/aman.)
 */
if (!function_exists('norm_rel_path')) {
    function norm_rel_path(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?: $path;
        return ltrim($path, '/');
    }
}

$homeroomClass     = rowa($homeroom_class ?? $class ?? []);
$groupedCategories = $groupedCategories ?? [];
$violation         = rowa($violation ?? []);
$errors            = session('errors') ?? [];

// Decode evidence lama (kalau mau ditampilkan)
$existingEvidence = [];
if (!empty($violation['evidence'])) {
    $decoded = json_decode((string)$violation['evidence'], true);
    if (is_array($decoded)) {
        $existingEvidence = $decoded;
    }
}

$violationId = $violation['id'] ?? $violation['violation_id'] ?? null;
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <?= esc($pageTitle ?? $title ?? 'Edit Pelanggaran') ?>
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

<!-- Flash messages -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Terjadi kesalahan:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($violationId)): ?>
    <div class="alert alert-warning">
        Data pelanggaran tidak valid (ID tidak ditemukan). Silakan kembali ke daftar pelanggaran.
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h4 class="card-title mb-0">
                    Edit Laporan Pelanggaran Siswa
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('homeroom/violations/update/' . $violationId) ?>"
                      method="post"
                      id="homeroomViolationEditForm"
                      enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- Informasi kelas -->
                    <div class="alert alert-secondary">
                        <div class="d-flex flex-column flex-md-row justify-content-between">
                            <div>
                                <strong>Kelas Perwalian:</strong>
                                <?= esc($homeroomClass['class_name'] ?? '-') ?>
                                <?php if (!empty($homeroomClass['year_name'])): ?>
                                    <span class="text-muted">
                                        (<?= esc($homeroomClass['year_name']) ?> - Semester <?= esc($homeroomClass['semester'] ?? '-') ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-2 mt-md-0 text-md-end">
                                <small class="text-muted">
                                    Perubahan hanya berlaku untuk pelanggaran tingkat
                                    <strong>Ringan</strong>.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Info siswa (read-only) -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Siswa yang Melanggar</label>
                            <div class="form-control-plaintext">
                                <strong><?= h($violation['student_name'] ?? '-') ?></strong>
                                <span class="text-muted ms-2">
                                    (NISN: <?= h($violation['nisn'] ?? '-') ?>)
                                </span>
                            </div>
                            <small class="text-muted">
                                Siswa tidak dapat diubah dari halaman ini.
                            </small>
                        </div>
                    </div>

                    <!-- Category Selection (Ringan only) -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label required">Kategori Pelanggaran</label>
                            <?php $ringan = $groupedCategories['Ringan'] ?? []; ?>

                            <select name="category_id" id="categorySelect" class="form-select" required>
                                <option value="">-- Pilih Kategori Pelanggaran Ringan --</option>

                                <?php if (!empty($ringan)): ?>
                                    <optgroup label="Pelanggaran Ringan">
                                        <?php foreach ($ringan as $cat): ?>
                                            <option value="<?= esc($cat['id']) ?>"
                                                    data-points="<?= esc($cat['points'] ?? 0) ?>"
                                                    data-severity="<?= esc($cat['severity_level'] ?? 'Ringan') ?>"
                                                    data-description="<?= esc($cat['description'] ?? '') ?>"
                                                <?= old('category_id', $violation['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                <?= esc($cat['category_name'] ?? '-') ?>
                                                (Poin: -<?= esc($cat['points'] ?? 0) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php else: ?>
                                    <option value="">
                                        Belum ada kategori pelanggaran ringan yang aktif.
                                    </option>
                                <?php endif; ?>
                            </select>

                            <!-- Category Info Display -->
                            <div id="categoryInfo" class="mt-2" style="display: none;">
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-information fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <strong id="categorySeverity">Tingkat: Ringan</strong>
                                            <div id="categoryPoints" class="small fw-semibold mb-1"></div>
                                            <p class="mb-0 small" id="categoryDescription"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-1">
                                Wali kelas hanya mencatat dan mengubah pelanggaran tingkat
                                <strong>Ringan</strong>.
                            </small>
                        </div>
                    </div>

                    <!-- Tanggal & Waktu Kejadian -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Kejadian</label>
                            <input type="date"
                                   name="violation_date"
                                   class="form-control"
                                   value="<?= old('violation_date', $violation['violation_date'] ?? date('Y-m-d')) ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Waktu Kejadian</label>
                            <input type="time"
                                   name="violation_time"
                                   class="form-control"
                                   value="<?= old('violation_time', $violation['violation_time'] ?? '') ?>">
                            <small class="text-muted">Opsional - Waktu terjadinya pelanggaran</small>
                        </div>
                    </div>

                    <!-- Lokasi -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Lokasi Kejadian</label>
                            <input type="text"
                                   name="location"
                                   class="form-control"
                                   maxlength="200"
                                   value="<?= old('location', $violation['location'] ?? '') ?>">
                            <small class="text-muted">
                                Contoh: "Ruang Kelas X-IPA-1", "Lapangan", "Koridor lantai 2".
                            </small>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label required">Deskripsi Pelanggaran</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="4"
                                      minlength="10"
                                      required><?= old('description', $violation['description'] ?? '') ?></textarea>
                            <small class="text-muted">
                                Tuliskan kronologi singkat dan detail penting (tanpa memuat hal yang terlalu sensitif).
                            </small>
                        </div>
                    </div>

                    <!-- Saksi -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Saksi (jika ada)</label>
                            <input type="text"
                                   name="witness"
                                   class="form-control"
                                   maxlength="200"
                                   value="<?= old('witness', $violation['witness'] ?? '') ?>">
                            <small class="text-muted">
                                Bisa diisi nama guru/siswa yang melihat langsung.
                            </small>
                        </div>
                    </div>

                    <!-- Evidence -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Tambah Barang Bukti (opsional)</label>
                            <input type="file"
                                   name="evidence[]"
                                   class="form-control"
                                   multiple
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp4">
                            <small class="text-muted d-block">
                                Anda dapat mengunggah bukti tambahan. Bukti yang sudah ada akan tetap tersimpan.
                            </small>

                            <!-- Info file baru yang dipilih (JS akan isi) -->
                            <div id="evidenceNewInfo" class="form-text mt-1" style="display:none;"></div>

                            <?php if (!empty($existingEvidence)): ?>
                                <div class="mt-3">

                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($existingEvidence as $file): ?>
                                            <?php
                                            $raw  = (string) $file;
                                            $path = norm_rel_path($raw);
                                            $url  = base_url($path);
                                            $name = basename($path);

                                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                            $icon = 'mdi-file';
                                            if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) $icon = 'mdi-file-image';
                                            elseif ($ext === 'pdf') $icon = 'mdi-file-pdf-box';
                                            elseif (in_array($ext, ['doc','docx'], true)) $icon = 'mdi-file-word-box';
                                            elseif (in_array($ext, ['mp4'], true)) $icon = 'mdi-file-video';
                                            ?>
                                            <li class="mb-2 d-flex align-items-center justify-content-between">
                                                <div class="text-truncate me-3">
                                                    <i class="mdi <?= esc($icon) ?> me-1"></i>
                                                    <a href="<?= esc($url) ?>"
                                                       target="_blank"
                                                       rel="noopener"
                                                       class="text-decoration-underline">
                                                        <?= esc($name) ?>
                                                    </a>
                                                </div>

                                                <div class="ms-auto">
                                                    <label class="small mb-0">
                                                        <input type="checkbox"
                                                               name="remove_evidence[]"
                                                               value="<?= esc($path) ?>">
                                                        Hapus
                                                    </label>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="mdi mdi-information-outline me-1"></i>
                                        Bukti yang dicentang akan dihapus saat Anda klik <strong>Simpan Perubahan</strong>.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tombol aksi -->
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('homeroom/violations/detail/' . $violationId) ?>" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Batal
                        </a>

                        <button type="submit" class="btn btn-danger" <?= empty($violationId) ? 'disabled' : '' ?>>
                            <i class="mdi mdi-content-save"></i> Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const select = document.getElementById('categorySelect');
        const infoDiv = document.getElementById('categoryInfo');
        const severitySpan = document.getElementById('categorySeverity');
        const pointsSpan = document.getElementById('categoryPoints');
        const descP = document.getElementById('categoryDescription');

        if (select && infoDiv) {
            function updateCategoryInfo() {
                const opt = select.options[select.selectedIndex];

                if (!opt || !opt.value) {
                    infoDiv.style.display = 'none';
                    return;
                }

                const points = opt.getAttribute('data-points') || '0';
                const severity = opt.getAttribute('data-severity') || 'Ringan';
                const description = opt.getAttribute('data-description') || '';

                if (severitySpan) severitySpan.textContent = 'Tingkat: ' + severity;
                if (pointsSpan) pointsSpan.textContent = 'Poin: -' + points;
                if (descP) descP.textContent = description || 'Tidak ada deskripsi tambahan.';

                infoDiv.style.display = 'block';
            }

            select.addEventListener('change', updateCategoryInfo);

            // Trigger on load kalau sudah ada nilai (edit mode)
            if (select.value) {
                updateCategoryInfo();
            }
        }

        // Tampilkan nama file bukti baru yang dipilih (tanpa duplikasi)
        const newInfo = document.getElementById('evidenceNewInfo');
        document.addEventListener('change', function (e) {
            if (!e.target) return;
            if (e.target.name !== 'evidence[]') return;

            const files = Array.from(e.target.files || []).map(f => f.name);
            if (!newInfo) return;

            if (!files.length) {
                newInfo.style.display = 'none';
                newInfo.textContent = '';
                return;
            }

            const label = (files.length === 1)
                ? ('Dipilih: ' + files[0])
                : ('Dipilih (' + files.length + ' file): ' + files.join(', '));

            newInfo.textContent = label;
            newInfo.style.display = 'block';
        });
    })();
</script>
<?= $this->endSection() ?>
