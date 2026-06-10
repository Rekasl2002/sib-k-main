<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .prototype-app .demo-role-switch .btn,
  .prototype-app .demo-screen-nav .btn {
    border-radius: 6px;
  }
  .prototype-app .demo-stat-icon {
    width: 42px;
    height: 42px;
  }
  .prototype-app .demo-message {
    border-radius: 8px;
  }
  .prototype-app .demo-catalog-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    height: 100%;
  }
  .prototype-app .assessment-question-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #fff;
  }
  .prototype-app .assessment-question-card .question-number {
    width: 34px;
    height: 34px;
  }
  .prototype-app .answer-preview {
    background: #f8fafc;
    border: 1px dashed rgba(15, 23, 42, .18);
    border-radius: 8px;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features = is_array($features ?? null) ? $features : [];
$feature = is_array($feature ?? null) ? $feature : [];
$screen = is_array($screen ?? null) ? $screen : [];
$roleOptions = is_array($roleOptions ?? null) ? $roleOptions : [];
$featureKey = (string) ($featureKey ?? '');
$screenKey = (string) ($screenKey ?? '');
$roleMode = (string) ($roleMode ?? '');
$roleLabel = (string) ($roleLabel ?? 'Pengguna');
$screens = is_array($feature['screens'] ?? null) ? $feature['screens'] : [];
$screenType = (string) ($screen['type'] ?? 'list');
$records = is_array($screen['records'] ?? null) ? $screen['records'] : [];
$record = is_array($screen['record'] ?? null) ? $screen['record'] : [];
$metrics = is_array($screen['metrics'] ?? null) ? $screen['metrics'] : [];
$fields = is_array($screen['form_fields'] ?? null) ? $screen['form_fields'] : [];
$filters = is_array($screen['filters'] ?? null) ? $screen['filters'] : [];
$timeline = is_array($screen['timeline'] ?? null) ? $screen['timeline'] : [];
$notes = is_array($screen['notes'] ?? null) ? $screen['notes'] : [];
$questions = is_array($screen['questions'] ?? null) ? $screen['questions'] : [];
$sections = is_array($screen['sections'] ?? null) ? $screen['sections'] : [];
$primaryAction = is_array($screen['primary_action'] ?? null) ? $screen['primary_action'] : [];
$canDelete = (bool) ($canDelete ?? false);
$headers = $records ? array_keys($records[0]) : [];
$tone = (string) ($feature['tone'] ?? 'primary');

if (! function_exists('prototype_app_tone')) {
    function prototype_app_tone(string $status): string
    {
        return match (strtolower(trim($status))) {
            'diajukan', 'draft', 'draf', 'belum dibaca', 'belum hadir', 'menunggu konfirmasi', 'belum selesai', 'perlu diperiksa', 'perlu tindak lanjut' => 'warning',
            'dijadwalkan', 'terjadwal', 'ditinjau', 'berjalan', 'berlangsung', 'aktif', 'terkirim', 'dipublikasi' => 'info',
            'selesai', 'siap', 'siap diimpor', 'publik', 'dibaca', 'hadir', 'konfirmasi', 'tersimpan', 'diterima', 'ditugaskan', 'berhasil' => 'success',
            'ditolak', 'mendesak', 'tinggi', 'gagal', 'dibatalkan' => 'danger',
            'rahasia bk', 'rahasia tinggi', 'terbatas' => 'dark',
            default => 'secondary',
        };
    }
}
?>

<div class="prototype-app">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <div>
          <h4 class="mb-sm-0"><?= esc($screen['title'] ?? $feature['title'] ?? 'Halaman Demo') ?></h4>
          <p class="text-muted mb-0"><?= esc($feature['title'] ?? '') ?> - <?= esc($roleLabel) ?></p>
        </div>
        <div class="page-title-right">
          <div class="d-flex flex-wrap justify-content-sm-end gap-2 mb-2">
            <a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>" class="btn btn-sm btn-outline-secondary">
              <i class="mdi mdi-view-grid-outline me-1"></i> Halaman Awal Prototipe
            </a>
            <a href="<?= base_url($feature['intro_url'] ?? 'prototype') ?>" class="btn btn-sm btn-outline-primary">
              <i class="mdi mdi-arrow-left-circle-outline me-1"></i> Pembuka Fitur
            </a>
          </div>
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>">Prototipe</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url($feature['intro_url'] ?? 'prototype') ?>"><?= esc($feature['short_title'] ?? 'Fitur') ?></a></li>
            <li class="breadcrumb-item active"><?= esc($screen['title'] ?? 'Demo') ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body py-3">
          <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
              <span class="avatar-sm rounded bg-soft-<?= esc($tone) ?> d-flex align-items-center justify-content-center demo-stat-icon">
                <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape-outline') ?> text-<?= esc($tone) ?> font-size-22"></i>
              </span>
              <div>
                <div class="fw-semibold"><?= esc($feature['title'] ?? '') ?></div>
                <small class="text-muted">Tampilan halaman aplikasi dengan data contoh. Sudut pandang aktif: <?= esc($roleLabel) ?>.</small>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2 demo-role-switch">
              <?php foreach ($roleOptions as $key => $label): ?>
                <a
                  href="<?= base_url('prototype/demo/' . $featureKey . '/' . $screenKey . '?role=' . rawurlencode((string) $key)) ?>"
                  class="btn btn-sm <?= $key === $roleMode ? 'btn-primary' : 'btn-outline-primary' ?>">
                  <?= esc($label) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body py-3">
          <div class="d-flex flex-wrap gap-2 demo-screen-nav">
            <?php foreach ($screens as $key => $item): ?>
              <a
                href="<?= base_url($item['url'] ?? '#') ?>"
                class="btn btn-sm <?= $key === $screenKey ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= esc($item['title'] ?? $key) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($metrics): ?>
    <div class="row">
      <?php foreach ($metrics as $metric): ?>
        <?php $metricTone = (string) ($metric['tone'] ?? 'primary'); ?>
        <div class="col-xl-3 col-md-6 mb-3">
          <div class="card mini-stats-wid h-100">
            <div class="card-body">
              <div class="d-flex">
                <div class="flex-grow-1">
                  <p class="text-muted fw-medium mb-1"><?= esc($metric['label'] ?? '') ?></p>
                  <h4 class="mb-0"><?= esc((string) ($metric['value'] ?? '-')) ?></h4>
                </div>
                <div class="avatar-sm rounded-circle bg-soft-<?= esc($metricTone) ?> d-flex align-items-center justify-content-center">
                  <i class="mdi mdi-chart-box-outline text-<?= esc($metricTone) ?> font-size-22"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($screenType === 'dashboard'): ?>
    <div class="row mb-3">
      <div class="col-12">
        <div class="card welcome-card">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h4 class="text-white mb-2">Selamat Datang, <?= esc($roleLabel) ?>!</h4>
                <p class="text-white-50 mb-0">Ringkasan agenda, layanan BK, penugasan, asesmen, dan laporan bulan Juni 2026.</p>
              </div>
              <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= base_url('prototype/demo/reports?role=' . rawurlencode($roleMode)) ?>" class="btn btn-light">
                  <i class="mdi mdi-file-chart-outline me-1"></i> Lihat Laporan
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xl-7 mb-3">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Agenda Terdekat</h5>
          </div>
          <div class="card-body">
            <?= view('prototype/partials_demo_table', ['records' => $records, 'featureKey' => $featureKey, 'roleMode' => $roleMode, 'showActions' => false, 'screens' => $screens]) ?>
          </div>
        </div>
      </div>
      <div class="col-xl-5 mb-3">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Prioritas Hari Ini</h5>
          </div>
          <div class="card-body">
            <?php foreach (($notes ?: [['title' => 'Konseling Siswa 2', 'body' => 'Jadwal konseling perlu dikonfirmasi.'], ['title' => 'Tugas kelas binaan', 'body' => 'Guru BK diminta memperbarui status tugas.']]) as $note): ?>
              <div class="border rounded p-3 mb-2">
                <div class="fw-semibold"><?= esc($note['title'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($note['body'] ?? '') ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'report'): ?>
    <div class="row">
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Saring Laporan</h5>
            <small class="text-muted">Data dibatasi sesuai hak akses.</small>
          </div>
          <div class="card-body">
            <form id="demoForm" class="row g-3">
              <?php foreach ($fields ?: [
                ['label' => 'Jenis laporan', 'type' => 'select', 'options' => ['Layanan BK', 'Asesmen', 'Tindak Lanjut']],
                ['label' => 'Periode', 'type' => 'select', 'options' => ['Juni 2026', 'Juli 2026']],
              ] as $index => $field): ?>
                <?= view('prototype/partials_demo_field', ['field' => $field, 'index' => $index, 'wide' => 'col-12']) ?>
              <?php endforeach; ?>
              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-eye-outline me-1"></i> Pratinjau</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pratinjau Laporan</h5>
            <span class="badge bg-light text-dark border">Juni 2026</span>
          </div>
          <div class="card-body">
            <?= view('prototype/partials_demo_table', ['records' => $records, 'featureKey' => $featureKey, 'roleMode' => $roleMode, 'showActions' => false, 'screens' => $screens]) ?>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'assessment_questions'): ?>
    <div class="row">
      <div class="col-xl-7">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Pertanyaan</h5>
            <span class="badge bg-primary"><?= count($questions) ?> contoh soal</span>
          </div>
          <div class="card-body">
            <form id="demoForm" class="row g-3">
              <div class="col-12">
                <label class="form-label">Teks Pertanyaan</label>
                <textarea class="form-control" rows="3" placeholder="Tulis pertanyaan di sini..."></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label">Jenis Pertanyaan</label>
                <select class="form-select">
                  <option>Pilihan Ganda</option>
                  <option>Pilihan Jamak</option>
                  <option>Skala Penilaian</option>
                  <option>Esai</option>
                  <option>Benar/Salah</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Poin</label>
                <input class="form-control" type="text" value="1">
              </div>
              <div class="col-md-4">
                <label class="form-label">Dimensi</label>
                <input class="form-control" type="text" placeholder="Contoh: Minat Karier">
              </div>
              <div class="col-12">
                <label class="form-label">Opsi Jawaban</label>
                <div class="row g-2">
                  <div class="col-md-6"><input class="form-control" value="Teknologi"></div>
                  <div class="col-md-6"><input class="form-control" value="Pendidikan"></div>
                  <div class="col-md-6"><input class="form-control" value="Kesehatan"></div>
                  <div class="col-md-6"><input class="form-control" value="Bisnis"></div>
                </div>
                <div class="form-text">Untuk esai, opsi dapat dikosongkan dan pembahasan dipakai sebagai kunci penilaian manual.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Jawaban Benar / Contoh Jawaban</label>
                <input class="form-control" type="text" value="Teknologi">
              </div>
              <div class="col-md-6">
                <label class="form-label">Wajib Dijawab</label>
                <select class="form-select">
                  <option>Ya</option>
                  <option>Tidak</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Pembahasan</label>
                <textarea class="form-control" rows="2" placeholder="Penjelasan atau catatan penilaian..."></textarea>
              </div>
              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-plus-circle me-1"></i> Tambah Pertanyaan</button>
                <button type="button" class="btn btn-outline-secondary">Simpan Draf</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-xl-5">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Daftar Pertanyaan</h5>
          </div>
          <div class="card-body">
            <?php foreach ($questions as $question): ?>
              <div class="assessment-question-card p-3 mb-3">
                <div class="d-flex gap-3">
                  <span class="question-number rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-semibold">
                    <?= esc((string) ($question['number'] ?? '-')) ?>
                  </span>
                  <div class="flex-grow-1">
                    <div class="fw-semibold"><?= esc($question['text'] ?? '') ?></div>
                    <div class="d-flex flex-wrap gap-2 my-2">
                      <span class="badge bg-light text-dark border"><?= esc($question['type'] ?? '') ?></span>
                      <span class="badge bg-light text-dark border"><?= esc((string) ($question['points'] ?? 0)) ?> poin</span>
                      <span class="badge bg-light text-dark border"><?= ! empty($question['required']) ? 'Wajib' : 'Opsional' ?></span>
                    </div>
                    <?php if (! empty($question['options'])): ?>
                      <div class="small text-muted mb-1">Opsi:</div>
                      <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach ((array) $question['options'] as $option): ?>
                          <span class="badge bg-secondary bg-opacity-10 text-secondary border"><?= esc((string) $option) ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    <div class="small text-muted">Kunci/contoh: <?= esc(is_array($question['correct'] ?? null) ? implode(', ', $question['correct']) : (string) ($question['correct'] ?? '-')) ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'assessment_answer'): ?>
    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">Kerjakan Asesmen Minat Karier</h5>
              <small class="text-muted">Soal: <?= count($questions) ?> | Durasi contoh: 45 menit</small>
            </div>
            <span class="badge bg-info">Progres 4/4</span>
          </div>
          <div class="card-body">
            <form id="demoForm">
              <?php foreach ($questions as $question): ?>
                <?php $type = (string) ($question['type'] ?? 'Esai'); ?>
                <div class="assessment-question-card p-3 mb-3">
                  <div class="d-flex gap-3 align-items-start">
                    <span class="question-number rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-semibold">
                      <?= esc((string) ($question['number'] ?? '-')) ?>
                    </span>
                    <div class="flex-grow-1">
                      <label class="fw-semibold d-block mb-2">
                        <?= esc($question['text'] ?? '') ?>
                        <?php if (! empty($question['required'])): ?><span class="text-danger">*</span><?php endif; ?>
                      </label>
                      <span class="badge bg-light text-dark border mb-2"><?= esc($type) ?></span>
                      <?php if ($type === 'Esai'): ?>
                        <textarea class="form-control" rows="4"><?= esc((string) ($question['answer'] ?? '')) ?></textarea>
                      <?php elseif ($type === 'Pilihan Jamak'): ?>
                        <?php foreach ((array) ($question['options'] ?? []) as $option): ?>
                          <?php $checked = in_array($option, (array) ($question['answer'] ?? []), true); ?>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" <?= $checked ? 'checked' : '' ?>>
                            <label class="form-check-label"><?= esc((string) $option) ?></label>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <?php foreach ((array) ($question['options'] ?? []) as $option): ?>
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="q_<?= esc((string) ($question['number'] ?? '')) ?>" <?= (string) ($question['answer'] ?? '') === (string) $option ? 'checked' : '' ?>>
                            <label class="form-check-label"><?= esc((string) $option) ?></label>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-send me-1"></i> Kumpulkan Jawaban</button>
                <button type="button" class="btn btn-outline-secondary">Simpan Sementara</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Ringkasan Jawaban</h5></div>
          <div class="card-body">
            <?php foreach ($questions as $question): ?>
              <div class="answer-preview p-3 mb-2">
                <div class="small text-muted">Pertanyaan <?= esc((string) ($question['number'] ?? '-')) ?></div>
                <div class="fw-semibold">
                  <?= esc(is_array($question['answer'] ?? null) ? implode(', ', $question['answer']) : (string) ($question['answer'] ?? '-')) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'form' && $featureKey === 'student-import'): ?>
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title mb-4">
              <i class="mdi mdi-upload text-primary me-2"></i>Unggah File Excel
            </h4>

            <form id="demoForm" class="row g-3">
              <div class="col-12">
                <label for="studentImportFile" class="form-label">
                  Pilih File Excel <span class="text-danger">*</span>
                </label>
                <input type="file" class="form-control" id="studentImportFile" accept=".xlsx,.xls,.csv" required>
                <div class="form-text">Format yang didukung: XLSX, XLS, CSV (maksimal 5MB).</div>
              </div>

              <div class="col-12">
                <div class="alert alert-info mb-0" role="alert">
                  <i class="mdi mdi-file-excel me-1"></i>
                  Setelah file dipilih, sistem akan memvalidasi dan memproses data seperti fitur Impor Siswa yang sudah ada.
                </div>
              </div>

              <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2">
                <a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>" class="btn btn-secondary">
                  <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="mdi mdi-upload me-1"></i> Unggah & Impor
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <h5 class="card-title text-white mb-3">
              <i class="mdi mdi-download me-2"></i>Template Impor
            </h5>
            <p class="card-text">
              Download template Excel untuk impor data siswa. Data orang tua/wali diisi pada template yang sama.
            </p>
            <a href="<?= base_url('prototype/template/student-import?role=' . rawurlencode($roleMode)) ?>" class="btn btn-light btn-block">
              <i class="mdi mdi-microsoft-excel me-1"></i> Download Template
            </a>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">
              <i class="mdi mdi-information text-info me-2"></i>Petunjuk Impor
            </h5>
            <div class="mb-3">
              <h6 class="font-size-14 mb-2">Langkah-langkah:</h6>
              <ol class="ps-3 mb-0 small">
                <li>Download template Excel.</li>
                <li>Isi data siswa atau gunakan file sekolah yang sudah ada.</li>
                <li>Pastikan NISN, nama, tanggal lahir, dan jenis kelamin tersedia.</li>
                <li>Unggah file yang sudah diisi.</li>
                <li>Sistem akan memvalidasi dan memproses data.</li>
              </ol>
            </div>
            <div class="mb-3">
              <h6 class="font-size-14 mb-2">Kolom Wajib:</h6>
              <ul class="ps-3 mb-0 small">
                <li><strong>NISN</strong> (tepat 10 digit angka)</li>
                <li><strong>Nama Lengkap</strong></li>
                <li><strong>Jenis Kelamin</strong> (L / P)</li>
                <li><strong>Tanggal Lahir</strong> (format tanggal valid)</li>
              </ul>
            </div>
            <div class="alert alert-warning py-2 mb-0" role="alert">
              <small>
                <i class="mdi mdi-alert-circle-outline me-1"></i>
                Data tidak valid atau duplikat tidak akan diproses. Baris valid tetap dapat diproses oleh sistem.
              </small>
            </div>
          </div>
        </div>

        <div class="card border-0 bg-light">
          <div class="card-body">
            <h6 class="mb-3">
              <i class="mdi mdi-format-list-bulleted text-success me-2"></i>Format Data
            </h6>
            <table class="table table-sm table-borderless mb-0 small">
              <tr>
                <td class="text-muted" width="45%">Jenis Kelamin:</td>
                <td><code>L</code> atau <code>P</code></td>
              </tr>
              <tr>
                <td class="text-muted">Tanggal:</td>
                <td><code>DD-MM-YYYY</code>, <code>DD/MM/YYYY</code>, atau <code>DDMMYYYY</code></td>
              </tr>
              <tr>
                <td class="text-muted">Status:</td>
                <td>Aktif, Alumni, Pindah, Keluar, Tidak Aktif</td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'form' && $featureKey === 'career-study'): ?>
    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0"><?= esc($screen['title'] ?? 'Tambah Referensi') ?></h5>
          </div>
          <div class="card-body">
            <form id="demoForm" class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="careerReferenceType">Jenis Referensi</label>
                <select class="form-select" id="careerReferenceType">
                  <option>Karier</option>
                  <option>Studi Lanjut</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="careerReferenceStatus">Status Publikasi</label>
                <select class="form-select" id="careerReferenceStatus">
                  <option>Publik</option>
                  <option>Draf</option>
                  <option>Nonaktif</option>
                </select>
              </div>

              <div class="col-12 career-reference-group" data-reference-group="Karier">
                <div class="border rounded p-3">
                  <div class="fw-semibold mb-3">Isian Referensi Karier</div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Nama karier</label>
                      <input class="form-control" type="text" placeholder="Contoh: Pengembang Perangkat Lunak">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Bidang pekerjaan</label>
                      <input class="form-control" type="text" placeholder="Contoh: Teknologi">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Pendidikan minimal</label>
                      <select class="form-select">
                        <option>SMA/MA/SMK</option>
                        <option>D3</option>
                        <option>S1</option>
                        <option>S2</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Kebutuhan di dunia kerja</label>
                      <select class="form-select">
                        <option>Tinggi</option>
                        <option>Sedang</option>
                        <option>Rendah</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Perkiraan penghasilan</label>
                      <input class="form-control" type="text" placeholder="Contoh: Rp4.000.000 - Rp8.000.000">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Sumber referensi</label>
                      <input class="form-control" type="text" placeholder="Contoh: tautan artikel atau situs resmi">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Gambaran pekerjaan</label>
                      <textarea class="form-control" rows="3" placeholder="Tuliskan penjelasan singkat yang mudah dipahami siswa"></textarea>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Keterampilan yang disarankan</label>
                      <textarea class="form-control" rows="3" placeholder="Contoh: logika, komunikasi, pemecahan masalah"></textarea>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Jalur pengembangan</label>
                      <textarea class="form-control" rows="3" placeholder="Contoh: belajar dasar pemrograman, ikut proyek kecil, memilih jurusan terkait"></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 career-reference-group" data-reference-group="Studi Lanjut">
                <div class="border rounded p-3">
                  <div class="fw-semibold mb-3">Isian Referensi Studi Lanjut</div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Nama kampus/prodi</label>
                      <input class="form-control" type="text" placeholder="Contoh: Teknik Informatika - ITB">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Nama singkat</label>
                      <input class="form-control" type="text" placeholder="Contoh: ITB">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Lokasi</label>
                      <input class="form-control" type="text" placeholder="Contoh: Bandung">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Akreditasi</label>
                      <input class="form-control" type="text" placeholder="Contoh: Unggul">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Website resmi</label>
                      <input class="form-control" type="text" placeholder="Contoh: https://www.itb.ac.id">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Kisaran biaya</label>
                      <input class="form-control" type="text" placeholder="Contoh: Rp500.000 - Rp12.500.000 per semester">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Deskripsi singkat</label>
                      <textarea class="form-control" rows="3" placeholder="Tuliskan gambaran kampus/prodi dengan bahasa sederhana"></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Fakultas</label>
                      <textarea class="form-control" rows="3" placeholder="Contoh: STEI, FTI, FMIPA"></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Program studi utama</label>
                      <textarea class="form-control" rows="3" placeholder="Contoh: Informatika, Sistem Informasi"></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Jalur masuk</label>
                      <textarea class="form-control" rows="3" placeholder="Contoh: SNBP, SNBT, Mandiri"></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Beasiswa</label>
                      <textarea class="form-control" rows="3" placeholder="Contoh: KIP Kuliah, beasiswa internal kampus"></textarea>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Kontak atau sumber informasi</label>
                      <textarea class="form-control" rows="3" placeholder="Tuliskan kontak atau tautan resmi yang bisa dicek siswa"></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Catatan Kurasi</label>
                <textarea class="form-control" rows="3" placeholder="Tuliskan catatan sebelum referensi ditayangkan"></textarea>
              </div>
              <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
                <button type="button" class="btn btn-outline-secondary"><i class="mdi mdi-content-save-outline me-1"></i> Simpan Draf</button>
                <a href="<?= base_url('prototype/demo/' . $featureKey . '?role=' . rawurlencode($roleMode)) ?>" class="btn btn-secondary">Kembali</a>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Catatan Pengisian</h5></div>
          <div class="card-body">
            <div class="mb-3">
              <div class="fw-semibold">Isian Menyesuaikan Jenis</div>
              <div class="text-muted">Pilih Karier untuk informasi pekerjaan, atau Studi Lanjut untuk informasi kampus/prodi.</div>
            </div>
            <div class="mb-0">
              <div class="fw-semibold">Bahasa untuk Siswa</div>
              <div class="text-muted">Deskripsi sebaiknya ditulis ringkas agar mudah dipahami siswa dan orang tua.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'form'): ?>
    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0"><?= esc($screen['title'] ?? 'Form') ?></h5>
          </div>
          <div class="card-body">
            <form id="demoForm" class="row g-3">
              <?php foreach ($fields as $index => $field): ?>
                <?php $wide = ($field['type'] ?? '') === 'textarea' ? 'col-12' : 'col-md-6'; ?>
                <?= view('prototype/partials_demo_field', ['field' => $field, 'index' => $index, 'wide' => $wide]) ?>
              <?php endforeach; ?>
              <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
                <button type="button" class="btn btn-outline-secondary"><i class="mdi mdi-content-save-outline me-1"></i> Simpan Draf</button>
                <a href="<?= base_url('prototype/demo/' . $featureKey . '?role=' . rawurlencode($roleMode)) ?>" class="btn btn-secondary">Kembali</a>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Ringkasan</h5></div>
          <div class="card-body">
            <?php foreach ($notes ?: [['title' => 'Status', 'body' => 'Data akan disimpan sebagai contoh prototipe.']] as $note): ?>
              <div class="mb-3">
                <div class="fw-semibold"><?= esc($note['title'] ?? '-') ?></div>
                <div class="text-muted"><?= esc($note['body'] ?? '') ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'conversation'): ?>
    <div class="row">
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Daftar Pesan</h5></div>
          <div class="card-body">
            <?php foreach ($records as $row): ?>
              <div class="border rounded p-3 mb-2">
                <div class="fw-semibold"><?= esc($row['Pengirim'] ?? '-') ?></div>
                <small class="text-muted"><?= esc(($row['Waktu'] ?? '-') . ' - ' . ($row['Status'] ?? '-')) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Percakapan</h5></div>
          <div class="card-body">
            <?php foreach ($records as $index => $row): ?>
              <div class="demo-message p-3 mb-2 <?= $index % 2 === 0 ? 'bg-light' : 'bg-primary bg-opacity-10' ?>">
                <div class="fw-semibold"><?= esc($row['Pengirim'] ?? '-') ?></div>
                <div><?= esc($row['Pesan'] ?? '-') ?></div>
              </div>
            <?php endforeach; ?>
            <textarea class="form-control mt-3" rows="3" placeholder="Tulis balasan..."></textarea>
            <button class="btn btn-primary mt-2" type="button"><i class="mdi mdi-send me-1"></i> Kirim</button>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'catalog'): ?>
    <div class="row">
      <?php foreach ($records as $row): ?>
        <div class="col-md-6 col-xl-4 mb-3">
          <div class="card demo-catalog-card">
            <div class="card-body">
              <span class="badge bg-light text-dark border mb-2"><?= esc($row['Jenis'] ?? 'Referensi') ?></span>
              <h5><?= esc($row['Nama'] ?? $row['Judul'] ?? '-') ?></h5>
              <?php foreach ($row as $key => $value): ?>
                <?php if (in_array($key, ['Nama', 'Judul'], true)) continue; ?>
                <div class="small text-muted"><?= esc($key) ?></div>
                <div class="mb-2"><?= esc((string) $value) ?></div>
              <?php endforeach; ?>
              <a href="<?= base_url('prototype/demo/' . $featureKey . '/detail/1?role=' . rawurlencode($roleMode)) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
              <button class="btn btn-sm btn-primary" type="button">Simpan Minat</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($featureKey === 'student-import' && $screenKey === 'template'): ?>
    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Template Impor</h5>
            <a href="<?= base_url('prototype/template/student-import?role=' . rawurlencode($roleMode)) ?>" class="btn btn-primary">
              <i class="mdi mdi-microsoft-excel me-1"></i> Download Template
            </a>
          </div>
          <div class="card-body">
            <div class="alert alert-info" role="alert">
              <i class="mdi mdi-file-excel-outline me-1"></i>
              Template yang digunakan: <strong>template_import_siswa_2026-06-10_contoh.xlsx</strong>.
            </div>
            <?= view('prototype/partials_demo_table', [
              'records' => $records,
              'featureKey' => $featureKey,
              'roleMode' => $roleMode,
              'showActions' => false,
              'screens' => $screens,
            ]) ?>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Catatan Template</h5></div>
          <div class="card-body">
            <div class="mb-3">
              <div class="fw-semibold">Data Orang Tua/Wali</div>
              <div class="text-muted">Nama ayah, ibu, dan wali dicatat pada template yang sama dengan data siswa.</div>
            </div>
            <div class="mb-3">
              <div class="fw-semibold">Kolom Wajib</div>
              <div class="text-muted">NISN, nama lengkap, jenis kelamin, dan tanggal lahir perlu tersedia agar data dapat diproses dengan baik.</div>
            </div>
            <div class="mb-0">
              <div class="fw-semibold">Sumber Prototype</div>
              <div class="text-muted">Halaman ini mengikuti fitur Impor Siswa yang sudah tersedia pada Admin.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($screenType === 'detail'): ?>
    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Informasi Detail</h5>
              <?php if (isset($screens['edit'])): ?>
                <a href="<?= base_url('prototype/demo/' . $featureKey . '/edit/1?role=' . rawurlencode($roleMode)) ?>" class="btn btn-sm btn-warning">
                  <i class="mdi mdi-pencil me-1"></i> Edit
                </a>
              <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <tbody>
                  <?php foreach ($record as $key => $value): ?>
                    <tr>
                      <th width="28%" class="table-light"><?= esc($key) ?></th>
                      <td>
                        <?php if (in_array(strtolower((string) $key), ['status', 'prioritas', 'akses'], true)): ?>
                          <span class="badge bg-<?= esc(prototype_app_tone((string) $value)) ?>"><?= esc((string) $value) ?></span>
                        <?php else: ?>
                          <?= esc((string) $value) ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <?php if ($timeline): ?>
          <div class="card">
            <div class="card-header"><h5 class="mb-0">Riwayat Alur</h5></div>
            <div class="card-body">
              <?php foreach ($timeline as $step): ?>
                <div class="d-flex gap-2 mb-2">
                  <i class="mdi mdi-check-circle-outline text-success mt-1"></i>
                  <div><?= esc($step) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php foreach ($sections as $section): ?>
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><?= esc($section['title'] ?? 'Detail Tambahan') ?></h5>
            </div>
            <div class="card-body">
              <?php if (($section['type'] ?? '') === 'table'): ?>
                <?= view('prototype/partials_demo_table', [
                  'records' => is_array($section['records'] ?? null) ? $section['records'] : [],
                  'featureKey' => $featureKey,
                  'roleMode' => $roleMode,
                  'showActions' => false,
                  'screens' => $screens,
                ]) ?>
              <?php elseif (($section['type'] ?? '') === 'questions'): ?>
                <?php foreach ((array) ($section['questions'] ?? []) as $question): ?>
                  <div class="answer-preview p-3 mb-2">
                    <div class="fw-semibold"><?= esc(($question['number'] ?? '-') . '. ' . ($question['text'] ?? '')) ?></div>
                    <div class="small text-muted mb-1"><?= esc($question['type'] ?? '') ?> - <?= esc($question['dimension'] ?? '') ?></div>
                    <div>Jawaban contoh: <?= esc(is_array($question['answer'] ?? null) ? implode(', ', $question['answer']) : (string) ($question['answer'] ?? '-')) ?></div>
                    <?php if (! empty($question['explanation'])): ?>
                      <div class="text-muted small mt-1"><?= esc($question['explanation']) ?></div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-muted mb-0"><?= esc($section['body'] ?? '') ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Catatan</h5></div>
          <div class="card-body">
            <?php foreach ($notes ?: [['title' => 'Catatan', 'body' => 'Informasi detail mengikuti batas hak akses pengguna.']] as $note): ?>
              <div class="mb-3">
                <div class="fw-semibold"><?= esc($note['title'] ?? '-') ?></div>
                <div class="text-muted"><?= esc($note['body'] ?? '') ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <?php if ($filters): ?>
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="mdi mdi-filter-variant me-1"></i>Saring Data</h5>
            </div>
            <div class="card-body">
              <form class="row g-3">
                <?php foreach ($filters as $index => $filter): ?>
                  <?php if (($filter['type'] ?? '') === 'date-range'): ?>
                    <div class="col-md-3">
                      <label class="form-label">Dari Tanggal</label>
                      <input type="date" class="form-control" value="2026-06-01">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Sampai Tanggal</label>
                      <input type="date" class="form-control" value="2026-06-30">
                    </div>
                  <?php elseif (($filter['type'] ?? '') === 'search'): ?>
                    <div class="col-md-3">
                      <label class="form-label"><?= esc($filter['label'] ?? 'Pencarian') ?></label>
                      <input type="text" class="form-control" placeholder="Ketik kata kunci...">
                    </div>
                  <?php else: ?>
                    <div class="col-md-3">
                      <label class="form-label"><?= esc($filter['label'] ?? 'Penyaring') ?></label>
                      <select class="form-select">
                        <?php foreach (($filter['options'] ?? ['Semua']) as $option): ?>
                          <option><?= esc($option) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
                <div class="col-md-3 d-flex align-items-end gap-2">
                  <button type="button" class="btn btn-primary"><i class="mdi mdi-magnify me-1"></i> Terapkan</button>
                  <button type="button" class="btn btn-secondary"><i class="mdi mdi-refresh me-1"></i> Bersihkan</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= esc($screen['title'] ?? 'Daftar Data') ?></h5>
            <?php if ($primaryAction): ?>
              <a href="<?= base_url($primaryAction['url'] ?? '#') ?>" class="btn btn-primary">
                <i class="<?= esc($primaryAction['icon'] ?? 'mdi mdi-plus') ?> me-1"></i> <?= esc($primaryAction['label'] ?? 'Tambah') ?>
              </a>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <?= view('prototype/partials_demo_table', [
              'records' => $records,
              'featureKey' => $featureKey,
              'roleMode' => $roleMode,
              'showActions' => true,
              'screens' => $screens,
              'canDelete' => $canDelete,
            ]) ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const referenceType = document.getElementById('careerReferenceType');
  const referenceGroups = document.querySelectorAll('[data-reference-group]');

  function syncReferenceGroups() {
    if (!referenceType || referenceGroups.length === 0) return;

    referenceGroups.forEach(function (group) {
      const isActive = group.getAttribute('data-reference-group') === referenceType.value;
      group.classList.toggle('d-none', !isActive);
    });
  }

  if (referenceType) {
    referenceType.addEventListener('change', syncReferenceGroups);
    syncReferenceGroups();
  }

  const form = document.getElementById('demoForm');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    const alert = document.createElement('div');
    alert.className = 'alert alert-success mt-3 mb-0';
    alert.textContent = 'Data contoh berhasil diproses pada prototipe.';
    form.appendChild(alert);
    setTimeout(function () { alert.remove(); }, 3000);
  });
})();
</script>
<?= $this->endSection() ?>
