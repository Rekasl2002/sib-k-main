<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/settings/index.php
 *
 * Admin • Settings Page
 * - Tabbed settings form
 * - Menjelaskan fungsi tiap fitur agar mudah dipahami orang awam
 * - Menampilkan error validasi per field + daftar error (jika ada)
 * - Menjaga input saat validasi gagal (old())
 * - Mengingat tab terakhir (localStorage)
 */
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">PENGATURAN APLIKASI</h4>
    <div class="text-muted">
      Halaman ini untuk mengatur identitas aplikasi, branding, keamanan, dan ambang poin.
    </div>
  </div>

  <div class="text-end">
    <ol class="breadcrumb m-0">
      <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
      <li class="breadcrumb-item active">Pengaturan Aplikasi</li>
    </ol>
  </div>
</div>

<?php if (session('success')): ?>
  <div class="alert alert-success d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-check-circle"></i>
    <div><?= esc(session('success')) ?></div>
  </div>
<?php endif; ?>

<?php if (session('error')): ?>
  <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-exclamation-triangle"></i>
    <div>
      <div class="fw-semibold"><?= esc(session('error')) ?></div>

      <?php if (is_array(session('errors')) && session('errors')): ?>
        <ul class="mb-0 mt-2">
          <?php foreach (session('errors') as $field => $msg): ?>
            <li><span class="text-muted"><?= esc($field) ?>:</span> <?= esc($msg) ?></li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </div>
  </div>
<?php endif; ?>

<?php
// Helper: baca error per field (baik dari $validation maupun session('errors'))
$validation = $validation ?? \Config\Services::validation();
$errors = is_array(session('errors')) ? session('errors') : [];
$getErr = static function(string $name) use ($validation, $errors): ?string {
  if (!empty($errors[$name])) return (string)$errors[$name];
  if (isset($validation) && $validation && $validation->hasError($name)) return (string)$validation->getError($name);
  return null;
};

$invalidClass = static function(?string $err): string {
  return $err ? 'is-invalid' : '';
};

$checked = static function($value): bool {
  // mendukung '1', 1, true, 'true'
  return filter_var($value, FILTER_VALIDATE_BOOLEAN);
};
?>

<div class="card">
  <div class="card-body">
    <!--<div class="mb alert-info mb-4">
      <div class="fw-semibold mb-1">Panduan singkat</div>
      <ul class="mb-0">
        <li>Ubah sesuai kebutuhan, lalu klik <b>Simpan Pengaturan</b>.</li>
        <li>Kalau ada error validasi, pesan akan muncul dan isi form tidak hilang.</li>
        <li>Untuk email (SMTP), <b>username/password sebaiknya tetap di file <code>.env</code></b>.</li>
      </ul>
    </div>-->

    <form method="post" action="<?= route_to('admin.settings.update') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <ul class="nav nav-tabs mb-3" role="tablist" id="settingsTabs">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">Umum</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-branding" type="button" role="tab">Branding</button>
        </li>
        <!--<li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-academic" type="button" role="tab">Tahun Ajaran</button>
        </li>-->
        <!--<li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-mail" type="button" role="tab">Email</button>
        </li>-->
        <!--<li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notif" type="button" role="tab">Notifikasi</button>
        </li>-->
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">Keamanan</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-points" type="button" role="tab">Poin</button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- ===================== TAB: GENERAL ===================== -->
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel" tabindex="0">
          <div class="row g-3">
            <?php $err = $getErr('app_name'); ?>
            <div class="col-md-6">
              <label class="form-label">Nama Aplikasi</label>
              <input
                name="app_name"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('app_name', setting('app_name', 'SIB-K', 'general'))) ?>"
                placeholder="Contoh: SIB-K"
              >
              <div class="form-text">Nama yang muncul di judul sistem dan beberapa halaman.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('school_name'); ?>
            <div class="col-md-6">
              <label class="form-label">Nama Sekolah</label>
              <input
                name="school_name"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('school_name', setting('school_name', '', 'general'))) ?>"
                placeholder="Contoh: MA Persis 31 Banjaran"
              >
              <div class="form-text">Dipakai sebagai identitas institusi di laporan atau header.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('contact_email'); ?>
            <!--<div class="col-md-6">
              <label class="form-label">Email Kontak</label>
              <input
                name="contact_email"
                type="email"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('contact_email', setting('contact_email', '', 'general'))) ?>"
                placeholder="Contoh: info@sekolah.sch.id"
              >
              <div class="form-text">Email yang bisa ditampilkan di halaman kontak atau footer.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('contact_phone'); ?>
            <div class="col-md-6">
              <label class="form-label">Telepon</label>
              <input
                name="contact_phone"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('contact_phone', setting('contact_phone', '', 'general'))) ?>"
                placeholder="Contoh: 08xxxxxxxxxx"
              >
              <div class="form-text">Nomor telepon sekolah (opsional).</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('address'); ?>
            <div class="col-12">
              <label class="form-label">Alamat</label>
              <textarea
                name="address"
                class="form-control <?= $invalidClass($err) ?>"
                rows="2"
                placeholder="Contoh: Jl. ...."
              ><?= esc(old('address', setting('address', '', 'general'))) ?></textarea>
              <div class="form-text">Alamat sekolah untuk informasi umum.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>-->
          </div>
        </div>

        <!-- ===================== TAB: BRANDING ===================== -->
        <div class="tab-pane fade" id="tab-branding" role="tabpanel" tabindex="0">
          <div class="mb-3">
            <div class="text-muted">
              Mengatur logo dan favicon (ikon kecil di tab browser). Cocok untuk identitas visual aplikasi.
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Logo</label>
              <input
                type="file"
                name="logo"
                accept="image/png,image/jpeg,image/webp"
                class="form-control"
              >
              <div class="form-text">
                Disarankan PNG/JPG/WebP. (Contoh ukuran: 512x512).
              </div>

              <?php if ($p = setting('logo_path', null, 'branding')): ?>
                <div class="mt-3">
                  <div class="text-muted mb-1">Logo saat ini:</div>
                  <img src="<?= base_url($p) ?>" alt="Logo" class="img-thumbnail" style="height:64px">
                  <div class="small text-muted mt-1"><?= esc($p) ?></div>
                </div>
              <?php endif ?>
            </div>

            <div class="col-md-6">
              <label class="form-label">Favicon</label>
              <input
                type="file"
                name="favicon"
                accept="image/png,image/jpeg,image/webp,image/x-icon,image/vnd.microsoft.icon"
                class="form-control"
              >
              <div class="form-text">
                Ikon kecil untuk tab browser. Biasanya format ICO atau PNG kecil (mis. 32x32 atau 48x48).
              </div>

              <?php if ($p = setting('favicon_path', null, 'branding')): ?>
                <div class="mt-3">
                  <div class="text-muted mb-1">Favicon saat ini:</div>
                  <img src="<?= base_url($p) ?>" alt="Favicon" class="img-thumbnail" style="height:40px">
                  <div class="small text-muted mt-1"><?= esc($p) ?></div>
                </div>
              <?php endif ?>
            </div>
          </div>

          <!--<div class="alert alert-warning mt-4 mb-0">
            <div class="fw-semibold mb-1">Catatan</div>
            <div class="small">
              Logo/favicon akan disimpan di folder <code>public/uploads/branding</code>.
              Pastikan folder tersebut writable di hosting (cPanel) agar upload tidak gagal.
            </div>
          </div>-->
        </div>

        <!-- ===================== TAB: ACADEMIC ===================== -->
        <div class="tab-pane fade" id="tab-academic" role="tabpanel" tabindex="0">
          <div class="mb-3">
            <div class="fw-semibold">Tahun Ajaran</div>
            <div class="text-muted">
              Menentukan tahun ajaran yang dianggap “aktif” dan dipakai sebagai default untuk laporan atau data akademik.
            </div>
          </div>

          <?php $err = $getErr('default_academic_year_id'); ?>
          <div class="row g-3">
            <div class="col-md-7 col-lg-6">
              <label class="form-label">Tahun Ajaran Default</label>
              <select name="default_academic_year_id" class="form-select <?= $invalidClass($err) ?>">
                <option value="">— pilih —</option>

                <?php
                  $cur = (int) old('default_academic_year_id', (int) setting('default_academic_year_id', 0, 'academic'));
                ?>

                <?php foreach ($years as $y): ?>
                  <option value="<?= (int)$y['id'] ?>" <?= $cur === (int)$y['id'] ? 'selected' : '' ?>>
                    <?= esc($y['year_name']) ?> (<?= esc($y['semester']) ?>)
                  </option>
                <?php endforeach ?>
              </select>

              <div class="form-text">
                Saat disimpan, sistem akan menandai tahun ajaran ini sebagai <b>aktif</b>.
              </div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>
          </div>

          <div class="alert alert-info mt-4 mb-0">
            <div class="fw-semibold mb-1">Apa dampaknya?</div>
            <div class="small">
              Pengaturan ini membantu sistem memilih konteks akademik (mis. laporan, rekap kelas, dan data semester) tanpa harus memilih ulang setiap kali.
            </div>
          </div>
        </div>

        <!-- ===================== TAB: MAIL ===================== -->
        <div class="tab-pane fade" id="tab-mail" role="tabpanel" tabindex="0">
          <div class="mb-3">
            <div class="fw-semibold">Email</div>
            <div class="text-muted">
              Mengatur identitas pengirim email (From). Pengaturan SMTP host/port/crypto bersifat opsional (untuk override konfigurasi).
            </div>
          </div>

          <div class="row g-3">
            <?php $err = $getErr('from_name'); ?>
            <div class="col-md-6">
              <label class="form-label">From Name</label>
              <input
                name="from_name"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('from_name', setting('from_name', 'SIB-K', 'mail'))) ?>"
                placeholder="Contoh: SIB-K BK"
              >
              <div class="form-text">Nama pengirim yang terlihat oleh penerima email.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('from_email'); ?>
            <div class="col-md-6">
              <label class="form-label">From Email</label>
              <input
                name="from_email"
                type="email"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('from_email', setting('from_email', 'no-reply@example.test', 'mail'))) ?>"
                placeholder="Contoh: no-reply@domain.sch.id"
              >
              <div class="form-text">Alamat email pengirim (yang tampil di inbox penerima).</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('host'); ?>
            <div class="col-md-6">
              <label class="form-label">SMTP Host (opsional)</label>
              <input
                name="host"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('host', setting('host', '', 'mail'))) ?>"
                placeholder="Contoh: smtp.gmail.com"
              >
              <div class="form-text">
                Jika dikosongkan, sistem bisa memakai konfigurasi dari <code>.env</code> (tergantung implementasi).
              </div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php
              $portVal = old('port', (string) setting('port', 0, 'mail'));
              $portVal = ((int)$portVal) > 0 ? (int)$portVal : '';
              $err = $getErr('port');
            ?>
            <div class="col-md-3">
              <label class="form-label">Port (opsional)</label>
              <input
                name="port"
                type="number"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc($portVal) ?>"
                placeholder="Contoh: 587"
                min="0"
                max="65535"
              >
              <div class="form-text">Contoh umum: 587 (TLS) atau 465 (SSL).</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('crypto'); ?>
            <div class="col-md-3">
              <label class="form-label">Crypto (opsional)</label>
              <?php $c = old('crypto', setting('crypto', 'tls', 'mail')); ?>
              <select name="crypto" class="form-select <?= $invalidClass($err) ?>">
                <option value="tls" <?= $c === 'tls' ? 'selected' : '' ?>>tls</option>
                <option value="ssl" <?= $c === 'ssl' ? 'selected' : '' ?>>ssl</option>
                <option value="starttls" <?= $c === 'starttls' ? 'selected' : '' ?>>starttls</option>
                <option value="none" <?= $c === 'none' ? 'selected' : '' ?>>none</option>
              </select>
              <div class="form-text">Gunakan sesuai penyedia SMTP-mu.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <div class="col-12">
              <div class="alert alert-secondary mb-0">
                <div class="fw-semibold mb-1">Catatan keamanan</div>
                <div class="small">
                  Username/password SMTP sebaiknya disimpan di <code>.env</code>, bukan di database.
                  Ini mengurangi risiko kebocoran data jika database diakses pihak yang tidak berwenang.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===================== TAB: NOTIFICATIONS ===================== -->
        <div class="tab-pane fade" id="tab-notif" role="tabpanel" tabindex="0">
          <div class="mb-3">
            <div class="fw-semibold">Notifikasi</div>
            <div class="text-muted">
              Mengatur cara sistem memberi pemberitahuan. Misalnya saat ada jadwal konseling, pelanggaran, atau informasi penting lainnya.
            </div>
          </div>

          <?php
            $emailVal = old('enable_email', setting('enable_email', true, 'notifications'));
            $intVal   = old('enable_internal', setting('enable_internal', true, 'notifications'));
          ?>

          <div class="row g-3">
            <div class="col-lg-6">
              <div class="border rounded p-3">
                <div class="form-check form-switch">
                  <!-- hidden: agar saat off tetap terkirim -->
                  <input type="hidden" name="enable_email" value="0">
                  <input class="form-check-input" type="checkbox" name="enable_email" id="en_email" value="1" <?= $checked($emailVal) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="en_email">Aktifkan notifikasi Email</label>
                </div>
                <div class="small text-muted mt-2">
                  Jika aktif, sistem dapat mengirim email (contoh: pemberitahuan jadwal, ringkasan laporan, dsb).
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="border rounded p-3">
                <div class="form-check form-switch">
                  <input type="hidden" name="enable_internal" value="0">
                  <input class="form-check-input" type="checkbox" name="enable_internal" id="en_internal" value="1" <?= $checked($intVal) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="en_internal">Aktifkan notifikasi Internal</label>
                </div>
                <div class="small text-muted mt-2">
                  Notifikasi internal biasanya tampil di dashboard/ikon lonceng (jika fitur itu tersedia).
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-4 mb-0">
            <div class="fw-semibold mb-1">Tips</div>
            <div class="small">
              Jika notifikasi Email aktif tapi email tidak terkirim, biasanya penyebabnya konfigurasi SMTP di <code>.env</code> belum benar.
            </div>
          </div>
        </div>

        <!-- ===================== TAB: SECURITY ===================== -->
        <div class="tab-pane fade" id="tab-security" role="tabpanel" tabindex="0">
          <div class="row g-3">
            <?php $err = $getErr('session_timeout_minutes'); ?>
            <div class="col-md-4">
              <label class="form-label">Timeout Sesi (menit)</label>
              <input
                name="session_timeout_minutes"
                type="number"
                min="5"
                max="1440"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('session_timeout_minutes', setting('session_timeout_minutes', 60, 'security'))) ?>"
              >
              <div class="form-text">Jika tidak ada aktivitas, user akan logout otomatis setelah sekian menit.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <!--<?php $err = $getErr('password_min_length'); ?>
            <div class="col-md-4">
              <label class="form-label">Minimal Panjang Password</label>
              <input
                name="password_min_length"
                type="number"
                min="6"
                max="64"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('password_min_length', setting('password_min_length', 8, 'security'))) ?>"
              >
              <div class="form-text">Semakin panjang minimal password, biasanya semakin aman.</div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>-->

            <!--<div class="col-md-4">
              <?php $capVal = old('login_captcha', setting('login_captcha', false, 'security')); ?>
              <label class="form-label d-block">Captcha Login</label>

              <div class="border rounded p-3">
                <div class="form-check form-switch mb-0">
                  <input type="hidden" name="login_captcha" value="0">
                  <input class="form-check-input" type="checkbox" name="login_captcha" value="1" id="cap" <?= $checked($capVal) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="cap">Aktif</label>
                </div>
                <div class="small text-muted mt-2">
                  Jika aktif, user perlu verifikasi tambahan saat login (membantu mengurangi brute-force).
                </div>
              </div>
            </div>-->
          </div>
        </div>

        <!-- ===================== TAB: POINTS ===================== -->
        <div class="tab-pane fade" id="tab-points" role="tabpanel" tabindex="0">
          <!--<div class="mb-3">
            <div class="text-muted">
              Ambang poin untuk memberi peringatan atau status probation. Biasanya dipakai di modul pelanggaran/ketertiban.
            </div>
          </div>-->

          <div class="row g-3">
            <?php $err = $getErr('warning_threshold'); ?>
            <div class="col-md-6">
              <label class="form-label">Ambang Peringatan</label>
              <input
                name="warning_threshold"
                type="number"
                min="0"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('warning_threshold', setting('warning_threshold', 25, 'points'))) ?>"
              >
              <div class="form-text">
                Jika poin pelanggaran mencapai angka ini, siswa bisa mendapat status “peringatan”.
              </div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>

            <?php $err = $getErr('probation_threshold'); ?>
            <!--<div class="col-md-6">
              <label class="form-label">Ambang Probation</label>
              <input
                name="probation_threshold"
                type="number"
                min="0"
                class="form-control <?= $invalidClass($err) ?>"
                value="<?= esc(old('probation_threshold', setting('probation_threshold', 50, 'points'))) ?>"
              >
              <div class="form-text">
                Jika poin melewati angka ini, siswa bisa masuk status “probation” (tindak lanjut lebih serius).
              </div>
              <?php if ($err): ?><div class="invalid-feedback"><?= esc($err) ?></div><?php endif; ?>
            </div>-->
          </div>

          <div class="mb alert-secondary mt-4 mb-0">
            <div class="fw-semibold mb-1">Catatan</div>
            <div class="small">
              Nilai ambang bisa disesuaikan sesuai kebijakan sekolah.
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="text-muted small">
          Pastikan perubahan sudah benar sebelum disimpan.
        </div>
        <button class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Simpan Pengaturan
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Ingat tab terakhir supaya UX lebih enak
  (function () {
    var key = 'admin_settings_active_tab';
    var tabButtons = document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]');
    if (!tabButtons || !tabButtons.length) return;

    // restore
    var last = localStorage.getItem(key);
    if (last) {
      var btn = document.querySelector('#settingsTabs button[data-bs-target="' + last + '"]');
      if (btn) {
        try {
          var tab = new bootstrap.Tab(btn);
          tab.show();
        } catch (e) {}
      }
    }

    // store on change
    tabButtons.forEach(function (btn) {
      btn.addEventListener('shown.bs.tab', function (e) {
        var target = e.target.getAttribute('data-bs-target');
        if (target) localStorage.setItem(key, target);
      });
    });
  })();
</script>

<?= $this->endSection() ?>
