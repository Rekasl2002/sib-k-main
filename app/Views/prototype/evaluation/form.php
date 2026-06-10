<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .eval-wizard .step-nav { gap: 8px; }
  .eval-wizard .step-pill {
    border: 1px solid rgba(15,23,42,.12);
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 13px;
    color: #64748b;
    background: #fff;
    white-space: nowrap;
  }
  .eval-wizard .step-pill.active { background: #1f6f54; color: #fff; border-color: #1f6f54; }
  .eval-wizard .step-pill.done { border-color: #1f6f54; color: #1f6f54; }
  .eval-wizard .eval-step { display: none; }
  .eval-wizard .eval-step.active { display: block; }
  .eval-wizard .feature-block { border: 1px solid rgba(15,23,42,.08); border-radius: 10px; }
  .eval-wizard .feature-block + .feature-block { margin-top: 16px; }
  .eval-wizard .q-row { border-top: 1px dashed rgba(15,23,42,.1); }
  .eval-wizard .q-row:first-child { border-top: 0; }
  .eval-wizard .form-check-inline { margin-right: 18px; }
  .eval-wizard .cat-heading { border-left: 4px solid #1f6f54; padding-left: 10px; }
  .eval-wizard .is-missing { background: #fff5f5; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features      = is_array($features ?? null) ? $features : [];
$questions     = is_array($questions ?? null) ? $questions : [];
$answerOptions = is_array($answerOptions ?? null) ? $answerOptions : [];
$reviewOptions = is_array($reviewOptions ?? null) ? $reviewOptions : [];
$errors        = session('eval_errors') ?? [];
$old           = static fn(string $k, $d = '') => old($k, $d);

// Kelompokkan fitur per kategori (urutan tetap dari controller).
$grouped = [];
foreach ($features as $key => $feature) {
    $grouped[$feature['category']][$key] = $feature;
}
?>

<div class="eval-wizard">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <div>
          <h4 class="mb-sm-0"><?= esc($introTitle ?? 'Evaluasi Prototipe SIB-K') ?></h4>
          <p class="text-muted mb-0">Sudut pandang akun Anda: <strong><?= esc($roleLabel ?? 'Pengguna') ?></strong></p>
        </div>
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('prototype') ?>">Prototipe</a></li>
          <li class="breadcrumb-item active">Evaluasi</li>
        </ol>
      </div>
    </div>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1"><i class="mdi mdi-alert-circle-outline me-1"></i>Mohon lengkapi:</div>
      <ul class="mb-0 ps-3">
        <?php foreach ((array) $errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body py-3">
      <div class="d-flex flex-wrap step-nav">
        <span class="step-pill active" data-pill="1">1. Pembuka</span>
        <span class="step-pill" data-pill="2">2. Identitas</span>
        <span class="step-pill" data-pill="3">3. Penilaian Fitur</span>
        <span class="step-pill" data-pill="4">4. Penutup</span>
      </div>
    </div>
  </div>

  <form id="evalForm" method="post" action="<?= esc($submitUrl ?? base_url('prototype/evaluation/submit')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="respondent_role" value="<?= esc($role ?? '') ?>">

    <!-- STEP 1: Pembuka + Persetujuan -->
    <div class="eval-step active" data-step="1">
      <div class="card">
        <div class="card-body">
          <div class="prose"><?= $introHtml ?? '' ?></div>
          <hr>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="consent1" name="consent_participate" value="1" <?= $old('consent_participate') ? 'checked' : '' ?>>
            <label class="form-check-label" for="consent1">
              Saya bersedia mengisi formulir ini sebagai narasumber/responden penelitian.
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="consent2" name="consent_data_usage" value="1" <?= $old('consent_data_usage') ? 'checked' : '' ?>>
            <label class="form-check-label" for="consent2">
              Saya memahami dan setuju jawaban akan digunakan untuk kebutuhan penelitian skripsi dan pengembangan aplikasi SIB-K.
            </label>
          </div>
          <div class="text-danger small mt-2 d-none" data-error="1">Kedua persetujuan wajib dicentang untuk melanjutkan.</div>
        </div>
      </div>
    </div>

    <!-- STEP 2: Identitas -->
    <div class="eval-step" data-step="2">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Identitas Responden</h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="respondent_name">Nama lengkap <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="respondent_name" name="respondent_name" value="<?= esc($old('respondent_name')) ?>" placeholder="Tulis nama lengkap Anda">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="respondent_relation">Kelas / hubungan dengan siswa / peran di sekolah <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="respondent_relation" name="respondent_relation" value="<?= esc($old('respondent_relation')) ?>" placeholder="Contoh: X IPA C / Orang tua Siswa 1 / Guru BK">
            </div>
            <div class="col-md-6">
              <label class="form-label">Peran sebagai responden</label>
              <input type="text" class="form-control" value="<?= esc($roleLabel ?? 'Pengguna') ?>" readonly>
              <div class="form-text">Peran terdeteksi otomatis dari akun Anda dan menentukan fitur yang dapat Anda nilai.</div>
            </div>
            <div class="col-12">
              <label class="form-label d-block">Apakah Anda sudah melihat prototipe pada setiap fitur yang dapat Anda akses? <span class="text-danger">*</span></label>
              <?php foreach ($reviewOptions as $val => $label): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="reviewed_prototype" id="rev_<?= esc($val) ?>" value="<?= esc($val) ?>" <?= $old('reviewed_prototype') === $val ? 'checked' : '' ?>>
                  <label class="form-check-label" for="rev_<?= esc($val) ?>"><?= esc($label) ?></label>
                </div>
              <?php endforeach; ?>
              <div class="text-danger small mt-1 d-none" data-error="2">Lengkapi nama, kelas/hubungan/peran, dan status melihat prototipe.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 3: Penilaian Fitur -->
    <div class="eval-step" data-step="3">
      <div class="alert alert-info">
        <i class="mdi mdi-information-outline me-1"></i>
        Anda menilai <strong><?= count($features) ?> fitur</strong> yang sesuai dengan hak akses peran <strong><?= esc($roleLabel ?? '') ?></strong>. Setiap fitur memiliki 5 pertanyaan.
      </div>
      <?php foreach ($grouped as $category => $items): ?>
        <h5 class="cat-heading mb-3 mt-4">Fitur <?= esc($category) ?></h5>
        <?php foreach ($items as $key => $feature): ?>
          <div class="feature-block" data-feature-block>
            <div class="p-3 border-bottom bg-light fw-semibold"><?= esc($feature['title']) ?></div>
            <div class="p-3">
              <?php foreach ($questions as $no => $text): ?>
                <div class="q-row py-2" data-question>
                  <div class="mb-2"><span class="text-muted me-1"><?= (int) $no ?>.</span><?= esc($text) ?></div>
                  <div>
                    <?php foreach ($answerOptions as $val => $label): ?>
                      <?php $aOld = $old("answers.$key.$no"); ?>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio"
                               name="answers[<?= esc($key) ?>][<?= (int) $no ?>]"
                               id="a_<?= esc($key) ?>_<?= (int) $no ?>_<?= esc($val) ?>"
                               value="<?= esc($val) ?>" <?= $aOld === $val ? 'checked' : '' ?>>
                        <label class="form-check-label" for="a_<?= esc($key) ?>_<?= (int) $no ?>_<?= esc($val) ?>"><?= esc($label) ?></label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              <div class="mt-3">
                <label class="form-label small text-muted" for="note_<?= esc($key) ?>">Catatan / revisi untuk fitur ini (opsional)</label>
                <textarea class="form-control" id="note_<?= esc($key) ?>" name="feature_notes[<?= esc($key) ?>]" rows="2" placeholder="Misal: bagian mana yang perlu diperbaiki"><?= esc($old("feature_notes.$key")) ?></textarea>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">Saran, Revisi, dan Masukan Keseluruhan</h5></div>
        <div class="card-body">
          <textarea class="form-control" name="suggestions" rows="4" placeholder="Tuliskan saran, revisi, atau masukan Anda mengenai keseluruhan fitur prototipe (opsional)"><?= esc($old('suggestions')) ?></textarea>
        </div>
      </div>
      <div class="text-danger small mt-2 d-none" data-error="3">Masih ada pertanyaan yang belum dijawab. Bagian yang kosong ditandai.</div>
    </div>

    <!-- STEP 4: Penutup -->
    <div class="eval-step" data-step="4">
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="mdi mdi-clipboard-check-outline text-success" style="font-size:48px;"></i>
          <h4 class="mt-3">Terima kasih, jawaban Anda siap dikirim</h4>
          <p class="text-muted mb-0">Pastikan seluruh isian sudah sesuai. Tekan <strong>Kirim Jawaban</strong> untuk menyimpan evaluasi Anda.</p>
        </div>
      </div>
    </div>

    <!-- Navigasi -->
    <div class="d-flex justify-content-between mt-3 mb-5">
      <button type="button" class="btn btn-light" id="btnPrev" style="display:none;"><i class="mdi mdi-arrow-left me-1"></i>Sebelumnya</button>
      <div class="ms-auto d-flex gap-2">
        <button type="button" class="btn btn-primary" id="btnNext">Berikutnya<i class="mdi mdi-arrow-right ms-1"></i></button>
        <button type="submit" class="btn btn-success" id="btnSubmit" style="display:none;"><i class="mdi mdi-send me-1"></i>Kirim Jawaban</button>
      </div>
    </div>
  </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const form = document.getElementById('evalForm');
  if (!form) return;

  const steps = Array.from(form.querySelectorAll('.eval-step'));
  const pills = Array.from(document.querySelectorAll('.step-pill'));
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const btnSubmit = document.getElementById('btnSubmit');
  let current = 1;
  const total = steps.length;

  function render() {
    steps.forEach(s => s.classList.toggle('active', Number(s.dataset.step) === current));
    pills.forEach(p => {
      const n = Number(p.dataset.pill);
      p.classList.toggle('active', n === current);
      p.classList.toggle('done', n < current);
    });
    btnPrev.style.display = current > 1 ? '' : 'none';
    btnNext.style.display = current < total ? '' : 'none';
    btnSubmit.style.display = current === total ? '' : 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function clearError(step) {
    const el = form.querySelector('[data-error="' + step + '"]');
    if (el) el.classList.add('d-none');
  }
  function showError(step) {
    const el = form.querySelector('[data-error="' + step + '"]');
    if (el) el.classList.remove('d-none');
  }

  function validateStep(step) {
    clearError(step);
    if (step === 1) {
      const ok = form.querySelector('#consent1').checked && form.querySelector('#consent2').checked;
      if (!ok) { showError(1); return false; }
      return true;
    }
    if (step === 2) {
      const name = form.querySelector('#respondent_name').value.trim();
      const rel = form.querySelector('#respondent_relation').value.trim();
      const reviewed = form.querySelector('input[name="reviewed_prototype"]:checked');
      if (!name || !rel || !reviewed) { showError(2); return false; }
      return true;
    }
    if (step === 3) {
      let firstMissing = null;
      form.querySelectorAll('[data-question]').forEach(function (q) {
        const radios = q.querySelectorAll('input[type="radio"]');
        const answered = Array.from(radios).some(r => r.checked);
        q.classList.toggle('is-missing', !answered);
        if (!answered && !firstMissing) firstMissing = q;
      });
      if (firstMissing) {
        showError(3);
        firstMissing.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      }
      return true;
    }
    return true;
  }

  btnNext.addEventListener('click', function () {
    if (!validateStep(current)) return;
    if (current < total) { current++; render(); }
  });
  btnPrev.addEventListener('click', function () {
    if (current > 1) { current--; render(); }
  });

  form.addEventListener('submit', function (e) {
    for (let s = 1; s <= 3; s++) {
      if (!validateStep(s)) { e.preventDefault(); current = s; render(); return; }
    }
  });

  render();
})();
</script>
<?= $this->endSection() ?>
