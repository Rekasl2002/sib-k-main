<?php // app/Views/homeroom_teacher/violations/create.php ?>


<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>


<?php
/** @var array|null $class */
/** @var array|null $homeroom_class */
/** @var array $students */
/** @var array $groupedCategories */


$homeroomClass     = $homeroom_class ?? $class ?? [];
$students          = $students ?? [];
$groupedCategories = $groupedCategories ?? [];
$errors            = session('errors') ?? [];
?>


<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                <?= esc($pageTitle ?? $title ?? 'Tambah Pelanggaran') ?>
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


<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h4 class="card-title mb-0">
                    Form Laporan Pelanggaran Siswa
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('homeroom/violations/store') ?>"
                      method="post"
                      id="homeroomViolationForm"
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
                                    Laporan ini akan otomatis diteruskan ke
                                    <strong>Guru BK</strong> yang bertanggung jawab atas kelas ini.
                                </small>
                            </div>
                        </div>
                    </div>


                    <!-- Student Selection -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label required">Siswa yang Melanggar</label>
                            <select name="student_id" id="studentSelect" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= esc($s['id']) ?>"
                                        <?= old('student_id') == $s['id'] ? 'selected' : '' ?>>
                                        <?= esc($s['full_name'] ?? '-') ?> (NISN: <?= esc($s['nisn'] ?? '-') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                Hanya menampilkan siswa <strong>aktif</strong> di kelas perwalian Anda.
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
                                                <?= old('category_id') == $cat['id'] ? 'selected' : '' ?>>
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
                                Wali kelas hanya mencatat pelanggaran tingkat <strong>Ringan</strong>.
                                Pelanggaran yang lebih berat ditangani langsung oleh Guru BK.
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
                                   value="<?= old('violation_date', date('Y-m-d')) ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Waktu Kejadian</label>
                            <input type="time"
                                   name="violation_time"
                                   class="form-control"
                                   value="<?= old('violation_time') ?>">
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
                                   value="<?= old('location') ?>">
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
                                      required><?= old('description') ?></textarea>
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
                                   value="<?= old('witness') ?>">
                            <small class="text-muted">
                                Bisa diisi nama guru/siswa yang melihat langsung.
                            </small>
                        </div>
                    </div>


                    <!-- Evidence -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Barang Bukti (opsional)</label>
                            <input type="file"
                                   name="evidence[]"
                                   class="form-control"
                                   multiple
                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.mp4">
                            <small class="text-muted d-block">
                                Anda dapat mengunggah foto atau dokumen pendukung (maksimal beberapa file).
                            </small>
                        </div>
                    </div>


                    <!-- Tombol aksi -->
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('homeroom/violations') ?>" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>


                        <button type="submit" class="btn btn-danger">
                            <i class="mdi mdi-content-save"></i> Simpan Laporan
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


        if (!select || !infoDiv) {
            return;
        }


        function updateCategoryInfo() {
            const opt = select.options[select.selectedIndex];


            if (!opt || !opt.value) {
                infoDiv.style.display = 'none';
                return;
            }


            const points = opt.getAttribute('data-points') || '0';
            const severity = opt.getAttribute('data-severity') || 'Ringan';
            const description = opt.getAttribute('data-description') || '';


            severitySpan.textContent = 'Tingkat: ' + severity;
            pointsSpan.textContent = 'Poin: -' + points;
            descP.textContent = description || 'Tidak ada deskripsi tambahan.';


            infoDiv.style.display = 'block';
        }


        select.addEventListener('change', updateCategoryInfo);


        // Trigger on load kalau ada old('category_id')
        if (select.value) {
            updateCategoryInfo();
        }


        // Tampilkan nama file bukti
        document.addEventListener('change', function (e) {
            if (e.target && e.target.name === 'evidence[]') {
                const files = Array.from(e.target.files).map(f => f.name).join(', ');
                if (files) {
                    const info = document.createElement('div');
                    info.className = 'form-text mt-1';
                    info.textContent = 'Dipilih: ' + files;
                    e.target.insertAdjacentElement('afterend', info);
                }
            }
        });
    })();
</script>
<?= $this->endSection() ?>
