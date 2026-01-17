<?php
/**
 * File: app/Views/counselor/assessments/form.php
 * Form Buat/Ubah Asesmen (Guru BK)
 *
 * Catatan terkait "Assigned":
 * - Penugasan per siswa dilakukan dari halaman "Tugaskan Asesmen".
 * - Setelah asesmen disimpan, buka "Tugaskan Asesmen" untuk membuat entri status "Assigned"
 *   ke siswa yang dipilih (terpisah dari eligibility/target).
 */

$this->extend('layouts/main');
$this->section('content');

helper(['form', 'text']);

// Helper ringkas untuk ambil nilai lama/tersimpan
if (!function_exists('fv')) {
    function fv(array $A, string $k, $d = '')
    {
        return esc(old($k, $A[$k] ?? $d));
    }
}

if (!function_exists('is_checked_flag')) {
    function is_checked_flag(string $key, array $A, bool $defaultTrue = true): bool
    {
        $old = old($key, null);
        if ($old !== null) {
            return (int) $old === 1;
        }
        $cur = $A[$key] ?? ($defaultTrue ? 1 : 0);
        return (int) $cur === 1;
    }
}

$A      = $assessment ?? [];
$method = $method ?? 'create';
$action = $method === 'edit'
    ? site_url('counselor/assessments/' . (int) ($A['id'] ?? 0) . '/update')
    : site_url('counselor/assessments/store');

// Samakan istilah tipe asesmen dengan data di DB
$assessmentTypes = ['Psikologi', 'Minat Bakat', 'Kecerdasan', 'Motivasi', 'Custom'];

// Opsi sasaran (label dibuat lebih jelas untuk Guru BK)
$audOpts = [
    'Individual' => 'Per siswa tertentu (dipilih manual)',
    'Class'      => 'Per kelas tertentu',
    'Grade'      => 'Per tingkat (misalnya semua kelas X)',
    'All'        => 'Semua siswa di madrasah',
];

// Flash message & error
$flashSuccess = session()->getFlashdata('success');
$flashError   = session()->getFlashdata('error');
$errors       = session('errors') ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
        <i class="fas fa-clipboard-list me-2"></i>
        <?= $method === 'edit' ? 'Ubah Asesmen' : 'Buat Asesmen' ?>
    </h4>

    <div class="d-flex gap-2">
        <?php if ($method === 'edit' && ! empty($A['id'])): ?>
            <a class="btn btn-outline-primary btn-sm"
               href="<?= site_url('counselor/assessments/' . (int) $A['id']) ?>">
                <i class="fas fa-eye me-1"></i> Lihat Detail
            </a>
            <a class="btn btn-outline-success btn-sm"
               href="<?= site_url('counselor/assessments/' . (int) $A['id'] . '/assign') ?>">
                <i class="fas fa-paper-plane me-1"></i> Tugaskan Asesmen
            </a>
        <?php endif; ?>

        <a href="<?= site_url('counselor/assessments') ?>" class="btn btn-sm btn-outline-secondary">
            &larr; Kembali ke Daftar Asesmen
        </a>
    </div>
</div>

<!-- Petunjuk umum agar guru awam paham alur -->
<div class="alert alert-info mb-3">
    <div class="d-flex">
        <div class="me-2">
            <i class="fas fa-info-circle"></i>
        </div>
        <div>
            <strong>Cara mengisi:</strong>
            <div>Isi formulir ini dari bagian 1 sampai 3. Jika ragu, biarkan pengaturan tetap seperti bawaan sistem.</div>
        </div>
    </div>
</div>

<!-- Flash messages -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<?php if (! empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Terdapat isian yang perlu dicek kembali:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $msg): ?>
                <li><?= esc($msg) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" novalidate>
            <?= csrf_field() ?>

            <!-- Bagian 1: Informasi Utama -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>1) Informasi dasar asesmen</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                Nama asesmen <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control"
                                   value="<?= fv($A, 'title') ?>" required>
                            <div class="form-text">
                                Contoh: <em>“Asesmen Minat Belajar Kelas X”</em>. Nama ini akan terlihat oleh siswa.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jenis asesmen</label>
                            <select name="assessment_type" class="form-select">
                                <?php
                                $curType = old('assessment_type', $A['assessment_type'] ?? 'Psikologi');
                                foreach ($assessmentTypes as $t):
                                    ?>
                                    <option value="<?= esc($t) ?>" <?= $curType === $t ? 'selected' : '' ?>>
                                        <?= esc($t) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Dipakai untuk pengelompokan dan laporan. Tidak mengubah cara penilaian.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Deskripsi dan tujuan asesmen</label>
                            <textarea name="description" class="form-control" rows="3"><?= fv($A, 'description') ?></textarea>
                            <div class="form-text">
                                Jelaskan secara singkat tujuan asesmen dan hal yang perlu diketahui siswa.
                                Contoh: <em>“Untuk memetakan minat dan kecenderungan belajar siswa.”</em>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Petunjuk untuk siswa</label>
                            <textarea name="instructions" class="form-control" rows="3"><?= fv($A, 'instructions') ?></textarea>
                            <div class="form-text">
                                Teks ini akan tampil sebelum siswa mulai mengerjakan.
                                Cantumkan aturan penting seperti durasi, boleh/tidak kembali ke soal, dan lain-lain.
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <?php
                    $isActive   = is_checked_flag('is_active', $A, true);
                    $isPublished = is_checked_flag('is_published', $A, false);
                    ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active"
                                       name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Asesmen aktif
                                </label>
                            </div>
                            <div class="form-text">
                                Jika dimatikan, asesmen tidak dapat dipakai dan disembunyikan dari siswa.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <input type="hidden" name="is_published" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_published"
                                       name="is_published" value="1" <?= $isPublished ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_published">
                                    Tampilkan di menu siswa (publikasi)
                                </label>
                            </div>
                            <div class="form-text">
                                Centang jika asesmen sudah siap ditugaskan dan dikerjakan siswa.
                            </div>
                        </div>

                        <?php if (! empty($A['total_questions'])): ?>
                            <div class="col-md-4">
                                <div class="small text-muted">
                                    <i class="fas fa-question-circle me-1"></i>
                                    Total pertanyaan saat ini:
                                    <strong><?= (int) ($A['total_questions'] ?? 0) ?></strong>.
                                    Ubah di menu <em>“Kelola Pertanyaan”</em>.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Sasaran Peserta & Jadwal -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>2) Siapa yang mengerjakan & kapan dibuka</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Sasaran utama asesmen</label>
                            <?php $curAud = old('target_audience', $A['target_audience'] ?? 'Individual'); ?>
                            <select name="target_audience" id="target_audience" class="form-select"
                                    onchange="toggleAudienceFields()">
                                <?php foreach ($audOpts as $val => $label): ?>
                                    <option value="<?= esc($val) ?>"
                                        <?= $curAud === $val ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Ini adalah target umum. Penugasan ke siswa tertentu tetap dilakukan
                                di menu <strong>“Tugaskan Asesmen”</strong>.
                            </div>
                        </div>

                        <div class="col-md-4" id="field-target-grade">
                            <label class="form-label">Tingkat (jika sasaran = per tingkat)</label>
                            <?php $grade = old('target_grade', $A['target_grade'] ?? ''); ?>
                            <select name="target_grade" id="target_grade" class="form-select">
                                <option value="">Pilih tingkat</option>
                                <?php foreach (['X', 'XI', 'XII'] as $g): ?>
                                    <option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>>
                                        <?= $g ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Contoh: pilih “X” untuk semua kelas X.
                            </div>
                        </div>

                        <div class="col-md-4" id="field-target-class">
                            <label class="form-label">Kelas (jika sasaran = per kelas)</label>
                            <?php $curClass = old('target_class_id', $A['target_class_id'] ?? ''); ?>
                            <select name="target_class_id" id="target_class_id" class="form-select">
                                <option value="">Pilih kelas</option>
                                <?php if (! empty($classes) && is_array($classes)): ?>
                                    <?php foreach ($classes as $c):
                                        $cid   = $c['id'] ?? $c['class_id'] ?? null;
                                        if (! $cid) {
                                            continue;
                                        }
                                        $cName = $c['class_name'] ?? $c['name'] ?? ('Kelas ' . $cid);
                                        ?>
                                        <option value="<?= (int) $cid ?>"
                                            <?= (string) $curClass === (string) $cid ? 'selected' : '' ?>>
                                            <?= esc($cName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">
                                Daftar diambil dari data kelas yang aktif di sistem.
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal mulai (opsional)</label>
                            <input type="date" name="start_date" class="form-control"
                                   value="<?= esc(old('start_date', isset($A['start_date']) ? substr($A['start_date'], 0, 10) : '')) ?>">
                            <div class="form-text">
                                Biarkan kosong jika asesmen langsung bisa dikerjakan setelah dipublikasikan.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tanggal selesai (opsional)</label>
                            <input type="date" name="end_date" class="form-control"
                                   value="<?= esc(old('end_date', isset($A['end_date']) ? substr($A['end_date'], 0, 10) : '')) ?>">
                            <div class="form-text">
                                Setelah tanggal ini, siswa tidak bisa mulai mengerjakan asesmen.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Durasi pengerjaan (menit)</label>
                            <input type="number" name="duration_minutes" class="form-control" min="0"
                                   value="<?= fv($A, 'duration_minutes') ?>">
                            <div class="form-text">
                                Kosongkan atau isi 0 jika tidak ingin membatasi waktu.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Maksimal percobaan per siswa</label>
                            <input type="number" name="max_attempts" class="form-control" min="1"
                                   value="<?= fv($A, 'max_attempts', 1) ?>">
                            <div class="form-text">
                                Berapa kali satu siswa boleh mengerjakan ulang asesmen ini.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Penilaian & Tampilan -->
            <?php
            // Default baru selaras server:
            // evaluation_mode = pass_fail, show_score_to_student=1, use_passing_score=1 (untuk pass_fail)
            $evalMode        = old('evaluation_mode', $A['evaluation_mode'] ?? 'pass_fail'); // pass_fail | score_only | survey
            $showScore       = is_checked_flag('show_score_to_student', $A, true);
            $usePass         = is_checked_flag('use_passing_score', $A, ($evalMode === 'pass_fail'));
            $showResultNow   = is_checked_flag('show_result_immediately', $A, true);
            $allowReview     = is_checked_flag('allow_review', $A, true);
            ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>3) Pengaturan nilai & tampilan hasil</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Cara penilaian</label>
                            <select name="evaluation_mode" id="evaluation_mode" class="form-select"
                                    onchange="toggleEvaluationFields()">
                                <option value="pass_fail"  <?= $evalMode === 'pass_fail' ? 'selected' : '' ?>>
                                    Lulus / Tidak Lulus (pakai nilai batas)
                                </option>
                                <option value="score_only" <?= $evalMode === 'score_only' ? 'selected' : '' ?>>
                                    Hanya nilai angka (tanpa status lulus/gagal)
                                </option>
                                <option value="survey"     <?= $evalMode === 'survey' ? 'selected' : '' ?>>
                                    Kuesioner / angket (tanpa penilaian nilai)
                                </option>
                            </select>
                            <div class="form-text">
                                Pilih bagaimana sistem menghitung dan menampilkan hasil asesmen ini.
                            </div>
                        </div>

                        <div class="col-md-4 align-self-end" id="field-show-score">
                            <input type="hidden" name="show_score_to_student" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="show_score_to_student"
                                       name="show_score_to_student" value="1" <?= $showScore ? 'checked' : '' ?>>
                                <label class="form-check-label" for="show_score_to_student">
                                    Tampilkan nilai ke siswa
                                </label>
                            </div>
                            <div class="form-text">
                                Jika dimatikan, siswa hanya melihat status lulus atau tidak lulus (jika ada),
                                tanpa angka nilai.
                            </div>
                        </div>

                        <div class="col-md-4 align-self-end">
                            <input type="hidden" name="show_result_immediately" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="show_result_immediately"
                                       name="show_result_immediately" value="1" <?= $showResultNow ? 'checked' : '' ?>>
                                <label class="form-check-label" for="show_result_immediately">
                                    Tampilkan hasil langsung setelah siswa selesai
                                </label>
                            </div>
                            <div class="form-text">
                                Jika dimatikan, hasil hanya bisa dilihat setelah Anda mengaturnya
                                (misalnya lewat jadwal rilis hasil atau laporan).
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <input type="hidden" name="use_passing_score" value="0">
                            <div class="form-check" id="field-use-passing-score">
                                <input class="form-check-input" type="checkbox" id="use_passing_score"
                                       name="use_passing_score" value="1" <?= $usePass ? 'checked' : '' ?>>
                                <label class="form-check-label" for="use_passing_score">
                                    Aktifkan batas nilai lulus
                                </label>
                            </div>
                            <div class="form-text">
                                Jika dimatikan, sistem tidak menandai Lulus/Tidak Lulus, hanya menampilkan nilai.
                            </div>
                        </div>

                        <div class="col-md-4" id="field-passing-score">
                            <label class="form-label">Nilai minimum lulus (persen)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                       name="passing_score" class="form-control"
                                       value="<?= fv($A, 'passing_score') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">
                                Contoh: isi <strong>70</strong> jika siswa dinyatakan lulus mulai dari 70 persen ke atas.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Jadwal rilis hasil ke siswa (opsional)</label>
                            <input type="datetime-local" name="result_release_at" class="form-control"
                                   value="<?php
                                   $raw = old('result_release_at', $A['result_release_at'] ?? '');
                                   if ($raw) {
                                       $t = strtotime($raw);
                                       if ($t) {
                                           echo esc(date('Y-m-d\TH:i', $t));
                                       }
                                   }
                                   ?>">
                            <div class="form-text">
                                Kosongkan jika hasil boleh langsung dilihat sesuai pengaturan di atas.
                                Jika diisi, siswa baru bisa melihat hasil setelah waktu ini.
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <input type="hidden" name="allow_review" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_review"
                                       name="allow_review" value="1" <?= $allowReview ? 'checked' : '' ?>>
                                <label class="form-check-label" for="allow_review">
                                    Izinkan siswa meninjau kembali pertanyaan dan jawaban
                                </label>
                            </div>
                            <div class="form-text">
                                Jika diizinkan, setelah selesai siswa dapat membuka kembali asesmen ini
                                hanya untuk melihat pertanyaan dan jawaban (tidak mengubah nilai).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted small">
                    Bidang bertanda <span class="text-danger">*</span> wajib diisi.
                </div>
                <div class="text-end">
                    <a href="<?= site_url('counselor/assessments') ?>" class="btn btn-outline-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?= $method === 'edit'
                            ? 'Simpan perubahan asesmen'
                            : 'Simpan asesmen dan lanjut ke pengaturan pertanyaan' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
function toggleAudienceFields() {
    const select = document.getElementById('target_audience');
    if (!select) return;

    const val = select.value;
    const gradeField = document.getElementById('field-target-grade');
    const classField = document.getElementById('field-target-class');

    if (gradeField) {
        gradeField.classList.toggle('d-none', val !== 'Grade');
    }
    if (classField) {
        classField.classList.toggle('d-none', val !== 'Class');
    }
}

function toggleEvaluationFields() {
    const select = document.getElementById('evaluation_mode');
    if (!select) return;

    const val = select.value;
    const showScoreCol = document.getElementById('field-show-score');
    const passScoreCol = document.getElementById('field-passing-score');
    const usePassCheck = document.getElementById('use_passing_score');

    // Passing score hanya relevan jika mode lulus/tidak lulus
    if (passScoreCol) {
        passScoreCol.classList.toggle('d-none', val !== 'pass_fail');
    }

    if (usePassCheck) {
        usePassCheck.disabled = (val !== 'pass_fail');
        if (val !== 'pass_fail') {
            usePassCheck.checked = false;
        }
    }

    // Untuk mode survey, menampilkan nilai biasanya tidak diperlukan
    if (showScoreCol) {
        showScoreCol.classList.toggle('opacity-50', val === 'survey');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    toggleAudienceFields();
    toggleEvaluationFields();

    const aud = document.getElementById('target_audience');
    if (aud) aud.addEventListener('change', toggleAudienceFields);

    const evalSel = document.getElementById('evaluation_mode');
    if (evalSel) evalSel.addEventListener('change', toggleEvaluationFields);
});
</script>
<?php $this->endSection(); ?>
