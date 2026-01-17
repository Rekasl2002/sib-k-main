<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
// ===== Guard & helpers =====
$assessment         = $assessment ?? [];
$students_by_class  = $students_by_class ?? [];
$assignedMap        = $assignedMap ?? [];
$aid                = (int)($assessment['id'] ?? 0);

// Normalisasi peta "sudah ditugaskan" -> array<int,bool>
// Sumber utama dari $assignedMap (service). Fallback ke DB jika kosong.
$__assignedIds = [];
if (is_array($assignedMap) && !empty($assignedMap)) {
    foreach ($assignedMap as $sid => $info) {
        $__assignedIds[(int)$sid] = true;
    }
} else {
    try {
        $db   = \Config\Database::connect();
        $rows = $db->table('assessment_results')
            ->select('DISTINCT student_id')
            ->where('assessment_id', $aid)
            ->where('deleted_at', null)
            ->get()->getResultArray();
        foreach ($rows as $r) {
            $__assignedIds[(int)($r['student_id'] ?? 0)] = true;
        }
    } catch (\Throwable $e) {
        // keep empty
    }
}

// Hitung berapa siswa yang akan disembunyikan
$__hiddenTotal = 0;
if (!empty($students_by_class)) {
    foreach ($students_by_class as $className => $studs) {
        foreach ($studs as $s) {
            if (!empty($__assignedIds[(int)$s['id']])) $__hiddenTotal++;
        }
    }
}

// Helper kecil
if (!function_exists('slugify')) {
  function slugify($s) {
    $s = iconv('UTF-8','ASCII//TRANSLIT',$s);
    $s = strtolower(preg_replace('/[^a-z0-9]+/i','-',$s));
    return trim($s,'-');
  }
}
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title mb-0">
                <i class="fas fa-user-plus me-2"></i>
                Tugaskan Asesmen
            </h2>
            <p class="text-muted mb-0">
                Pilih siswa yang akan mengerjakan asesmen:
                <strong><?= esc($assessment['title']) ?></strong>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('counselor/assessments/' . $assessment['id']) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Kembali
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

<?php if ($__hiddenTotal > 0): ?>
    <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-3">
        <i class="fas fa-info-circle me-2"></i>
        <div class="small">
            <strong><?= $__hiddenTotal ?></strong> siswa tidak ditampilkan karena sudah memiliki penugasan/hasil pada asesmen ini.
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= base_url('counselor/assessments/' . $assessment['id'] . '/assign/process') ?>" id="assignForm">
    <?= csrf_field() ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Students Selection -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Pilih Siswa
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                                <i class="fas fa-check-double me-1"></i>Pilih Semua (yang terlihat)
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="deselectAll()">
                                <i class="fas fa-times me-1"></i>Batalkan Semua (yang terlihat)
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <?php
                      $target = (string)($assessment['target_audience'] ?? 'All');
                      $targetLabel = $target;
                      if ($target === 'Class' && !empty($assessment['target_class_id'])) { $targetLabel .= ' (per Kelas)'; }
                      if ($target === 'Grade' && !empty($assessment['target_grade'])) { $targetLabel .= ' (Tingkat: '.$assessment['target_grade'].')'; }
                    ?>
                    <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-4">
                        <i class="fas fa-filter me-2"></i>
                        <div class="small">
                            Daftar siswa sudah difilter otomatis sesuai <strong>Target/Sasaran</strong> asesmen:
                            <span class="badge bg-primary ms-1"><?= esc($targetLabel) ?></span>
                        </div>
                    </div>

                    <!-- Search Filter -->
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchStudent"
                                placeholder="Cari siswa berdasarkan nama atau NIS/NISN...">
                        </div>
                    </div>

                    <!-- Class Filter Tabs -->
                    <ul class="nav nav-pills mb-4" id="classFilterTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="pill"
                                data-bs-target="#all" type="button" role="tab"
                                onclick="filterByClass('all')">
                                Semua Kelas
                                <span class="badge bg-primary ms-1" id="count-all">0</span>
                            </button>
                        </li>
                        <?php $classIndex = 0; ?>
                        <?php foreach ($students_by_class as $className => $students): ?>
                            <?php
                                // Hitung apakah kelas masih punya kandidat setelah menyaring yang sudah ditugaskan
                                $hasCandidate = false;
                                foreach ($students as $s) {
                                    if (empty($__assignedIds[(int)$s['id']])) { $hasCandidate = true; break; }
                                }
                                if (!$hasCandidate) continue;

                                $slug = slugify($className);
                            ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="class-<?= $classIndex ?>-tab"
                                    data-bs-toggle="pill" data-bs-target="#class-<?= $classIndex ?>"
                                    type="button" role="tab"
                                    onclick='filterByClass(<?= json_encode($slug) ?>)'>
                                    <?= esc($className) ?>
                                    <span class="badge bg-secondary ms-1" id="count-<?= esc($slug) ?>">0</span>
                                </button>
                            </li>
                            <?php $classIndex++; ?>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Students List -->
                    <div class="student-list">
                        <?php
                        // Tampilkan pesan jika setelah disaring benar-benar kosong
                        $anyCandidate = false;
                        foreach ($students_by_class as $className => $students) {
                            foreach ($students as $s) {
                                if (empty($__assignedIds[(int)$s['id']])) { $anyCandidate = true; break 2; }
                            }
                        }
                        ?>

                        <?php if (!$anyCandidate): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada siswa yang dapat ditugaskan</h5>
                                <p class="text-muted">Semua siswa eligible sudah mempunyai penugasan/hasil.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($students_by_class as $className => $students): ?>
                                <?php
                                    // Skip grup kelas bila semua siswanya sudah ditugaskan
                                    $hasCandidate = false;
                                    foreach ($students as $s) if (empty($__assignedIds[(int)$s['id']])) { $hasCandidate = true; break; }
                                    if (!$hasCandidate) continue;

                                    $slug = slugify($className);
                                ?>
                                <div class="class-group" data-class="<?= esc($className) ?>" data-class-slug="<?= esc($slug) ?>">
                                    <h6 class="text-muted mb-3 fw-bold">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        <?= esc($className) ?>
                                    </h6>

                                    <div class="row g-3 mb-4">
                                        <?php foreach ($students as $student): ?>
                                            <?php
                                                // ====== Saring: sembunyikan yang sudah ditugaskan ======
                                                if (!empty($__assignedIds[(int)$student['id']])) {
                                                    continue;
                                                }
                                            ?>
                                            <div class="col-md-6 student-item"
                                                data-class="<?= esc($className) ?>"
                                                data-class-slug="<?= esc($slug) ?>"
                                                data-name="<?= esc($student['full_name']) ?>"
                                                data-nisn="<?= esc($student['nisn']) ?>">
                                                <div class="card border h-100 student-card">
                                                    <div class="card-body p-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input student-checkbox"
                                                                type="checkbox"
                                                                name="student_ids[]"
                                                                value="<?= (int)$student['id'] ?>"
                                                                id="student-<?= (int)$student['id'] ?>"
                                                                data-class="<?= esc($className) ?>"
                                                                data-class-slug="<?= esc($slug) ?>">
                                                            <label class="form-check-label w-100 cursor-pointer"
                                                                for="student-<?= (int)$student['id'] ?>">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3">
                                                                        <?= esc(strtoupper(mb_substr($student['full_name'], 0, 2))) ?>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <h6 class="mb-1"><?= esc($student['full_name']) ?></h6>
                                                                        <div class="small text-muted">
                                                                            <i class="fas fa-id-card me-1"></i>
                                                                            <?= esc($student['nisn']) ?>
                                                                        </div>
                                                                        <?php if (!empty($student['class_name'])): ?>
                                                                            <div class="small text-muted">
                                                                                <i class="fas fa-school me-1"></i>
                                                                                <?= esc($student['class_name']) ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Assessment Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Info Asesmen
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Judul</label>
                        <div class="fw-bold"><?= esc($assessment['title']) ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted mb-1">Tipe Asesmen</label>
                        <div>
                            <span class="badge bg-info"><?= esc($assessment['assessment_type']) ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted mb-1">Target/Sasaran</label>
                        <div>
                            <?php $target = (string)($assessment['target_audience'] ?? 'All'); ?>
                            <span class="badge bg-primary"><?= esc($target) ?></span>
                            <?php if ($target === 'Class' && !empty($assessment['target_class_id'])): ?>
                                <span class="small text-muted ms-1">(ID Kelas: <?= (int)$assessment['target_class_id'] ?>)</span>
                            <?php elseif ($target === 'Grade' && !empty($assessment['target_grade'])): ?>
                                <span class="small text-muted ms-1">(Tingkat: <?= esc($assessment['target_grade']) ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($assessment['description'])): ?>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Deskripsi</label>
                            <div class="small"><?= esc($assessment['description']) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="small text-muted mb-1">Total Pertanyaan</label>
                        <div>
                            <span class="badge bg-primary"><?= (int)($assessment['total_questions'] ?? 0) ?> Soal</span>
                        </div>
                    </div>

                    <?php if (!empty($assessment['duration_minutes'])): ?>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Durasi</label>
                            <div>
                                <i class="fas fa-clock me-1"></i>
                                <?= (int)$assessment['duration_minutes'] ?> Menit
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($assessment['passing_score'])): ?>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Nilai Lulus</label>
                            <div>
                                <i class="fas fa-chart-line me-1"></i>
                                <?= (float)$assessment['passing_score'] ?>%
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-0">
                        <label class="small text-muted mb-1">Maksimal Percobaan</label>
                        <div>
                            <i class="fas fa-redo me-1"></i>
                            <?= (int)($assessment['max_attempts'] ?? 1) ?> Kali
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selection Summary -->
            <div class="card border-0 shadow-sm bg-light mb-4">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-check-circle me-2 text-success"></i>
                        Ringkasan Pilihan
                    </h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Siswa Dipilih:</span>
                        <span class="badge bg-success" id="selectedCount">0</span>
                    </div>
                    <div id="selectedSummary" class="small text-muted">
                        Belum ada siswa yang dipilih
                    </div>
                </div>
            </div>

            <!-- Quick Select -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Pilihan Cepat
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php
                        $qClasses = [];
                        if (!empty($students_by_class) && is_array($students_by_class)) {
                            foreach ($students_by_class as $cn => $studs) {
                                // skip kelas tanpa kandidat
                                $hasCandidate = false;
                                foreach ($studs as $s) if (empty($__assignedIds[(int)$s['id']])) { $hasCandidate = true; break; }
                                if ($hasCandidate) $qClasses[] = $cn;
                            }
                        } elseif (isset($classes) && is_array($classes) && count($classes) > 0) {
                            $qClasses = array_map(fn($c) => $c['class_name'], $classes);
                        }
                        foreach ($qClasses as $cn):
                            $slug = slugify($cn);
                        ?>
                            <button type="button" class="btn btn-outline-primary btn-sm text-start"
                                onclick='selectByClass(<?= json_encode($slug) ?>)'>
                                <i class="fas fa-check me-2"></i>
                                Pilih semua <?= esc($cn) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 btn-lg" id="submitBtn" disabled>
                        <i class="fas fa-paper-plane me-2"></i>
                        Tugaskan Asesmen
                    </button>
                    <div class="small text-muted text-center mt-2">
                        Pilih minimal satu siswa untuk melanjutkan
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Utility: slugify (harus sinkron dengan PHP)
    function slugify(s) {
        return String(s || "")
            .normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }

    // Update selection count and summary
    function updateSelection() {
        const checkboxes = document.querySelectorAll('.student-checkbox:checked');
        const count = checkboxes.length;
        const submitBtn = document.getElementById('submitBtn');
        const selectedCount = document.getElementById('selectedCount');
        const selectedSummary = document.getElementById('selectedSummary');

        selectedCount.textContent = count;
        submitBtn.disabled = count === 0;

        selectedSummary.textContent = count === 0
            ? 'Belum ada siswa yang dipilih'
            : `${count} siswa siap ditugaskan`;

        updateClassCounts();
    }

    // Update counts per class (jumlah TERPILIH)
    function updateClassCounts() {
        const allCheckboxes = document.querySelectorAll('.student-checkbox');
        const classCounts = {};
        let totalChecked = 0;

        allCheckboxes.forEach(cb => {
            const slug = cb.dataset.classSlug || slugify(cb.dataset.class);
            if (!classCounts[slug]) classCounts[slug] = { total: 0, checked: 0 };
            classCounts[slug].total++;
            if (cb.checked) { classCounts[slug].checked++; totalChecked++; }
        });

        const allBadge = document.getElementById('count-all');
        if (allBadge) allBadge.textContent = totalChecked;

        Object.keys(classCounts).forEach(slug => {
            const badge = document.getElementById(`count-${slug}`);
            if (badge) badge.textContent = classCounts[slug].checked;
        });
    }

    // Select all visible students
    function selectAll() {
        const visibleCheckboxes = document.querySelectorAll('.student-item:not([style*="display: none"]) .student-checkbox');
        visibleCheckboxes.forEach(cb => cb.checked = true);
        updateSelection();
    }

    // Deselect all visible students
    function deselectAll() {
        const visibleCheckboxes = document.querySelectorAll('.student-item:not([style*="display: none"]) .student-checkbox');
        visibleCheckboxes.forEach(cb => cb.checked = false);
        updateSelection();
    }

    // Select by class (slug)
    function selectByClass(classSlug) {
        const classCheckboxes = document.querySelectorAll(`.student-checkbox[data-class-slug="${classSlug}"]`);
        classCheckboxes.forEach(cb => cb.checked = true);
        updateSelection();
    }

    // Filter by class (slug)
    function filterByClass(classSlug) {
        const allItems = document.querySelectorAll('.student-item');
        const allGroups = document.querySelectorAll('.class-group');

        if (classSlug === 'all') {
            allItems.forEach(item => item.style.display = '');
            allGroups.forEach(group => group.style.display = '');
        } else {
            allGroups.forEach(group => {
                group.style.display = (group.dataset.classSlug === classSlug) ? '' : 'none';
            });
        }
    }

    // Search functionality (name or nis/nisn)
    document.getElementById('searchStudent').addEventListener('input', function(e) {
        const searchTerm = (e.target.value || '').toLowerCase();
        const studentItems = document.querySelectorAll('.student-item');

        studentItems.forEach(item => {
            const name = (item.dataset.name || '').toLowerCase();
            const nisn = (item.dataset.nisn || '').toLowerCase();
            item.style.display = (name.includes(searchTerm) || nisn.includes(searchTerm)) ? '' : 'none';
        });
    });

    // Listeners
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => cb.addEventListener('change', updateSelection));
        updateSelection();

        const form = document.getElementById('assignForm');
        form.addEventListener('submit', function(e) {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            if (checkedCount === 0) {
                e.preventDefault();
                alert('Pilih minimal satu siswa untuk ditugaskan');
                return false;
            }
            if (!confirm(`Tugaskan asesmen ini kepada ${checkedCount} siswa?`)) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
</script>

<style>
    .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem; flex-shrink: 0; }
    .student-card { transition: all 0.2s ease; cursor: pointer; }
    .student-card:hover { box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1); transform: translateY(-2px); }
    .cursor-pointer { cursor: pointer; }
    .nav-pills .nav-link { border-radius: 0.375rem; font-size: 0.875rem; }
    .nav-pills .nav-link.active { background-color: #0d6efd; }
    .class-group { margin-bottom: 2rem; }
    .student-list { max-height: 800px; overflow-y: auto; }
    .form-check-input { cursor: pointer; width: 1.25rem; height: 1.25rem; }
    .badge { font-size: 0.75rem; }
</style>

<?= $this->endSection() ?>
