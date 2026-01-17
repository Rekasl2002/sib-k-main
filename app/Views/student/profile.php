<!-- app/Views/student/profile.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
helper('url');

// Helpers aman untuk array & formatting
if (!function_exists('v')) {
  function v($a, $k, $d='') { return esc(is_array($a) ? ($a[$k] ?? $d) : (is_object($a) ? ($a->$k ?? $d) : $d)); }
}
if (!function_exists('date_input')) {
  function date_input($val) {
    if (empty($val)) return '';
    $t = is_numeric($val) ? (int)$val : strtotime((string)$val);
    return $t ? date('Y-m-d', $t) : '';
  }
}

// Normalisasi variabel dari controller
$profile         = isset($profile) ? (is_array($profile) ? $profile : (array)$profile) : [];
$mode            = isset($mode) ? (string)$mode : 'view'; // 'view' | 'edit' (untuk UX)
$today           = isset($today) ? $today : date('Y-m-d');
$accountEditable = isset($accountEditable) && is_array($accountEditable) ? $accountEditable : ['email','phone','profile_photo'];

// Prefill values (dengan dukungan old())
$valFullName  = old('full_name',  $profile['full_name']  ?? ($profile['user_full_name'] ?? ''));
$valPhone     = old('phone',      $profile['phone']      ?? '');
$valBirthPl   = old('birth_place',$profile['birth_place']?? '');
$valBirthDt   = old('birth_date', date_input($profile['birth_date'] ?? null));
$valAddress   = old('address',    $profile['address']    ?? '');

// ==============================
// ✅ Avatar: samakan dengan /profile
// - kosong => default-avatar.svg
// - placeholder lama dianggap kosong
// - cache busting ?v=filemtime
// ==============================
$defaultAvatar = base_url('assets/images/users/default-avatar.svg');

$avatarPathRaw = session('profile_photo') ?: ($profile['profile_photo'] ?? null);

$avatarPath = null;
if ($avatarPathRaw) {
    $p    = trim((string)$avatarPathRaw);
    $norm = strtolower(ltrim(str_replace('\\', '/', $p), '/'));
    $base = strtolower(basename($norm));

    $placeholders = [
        'default-avatar.png','default-avatar.jpg','default-avatar.jpeg','default-avatar.svg',
        'avatar.png','avatar.jpg','avatar.jpeg',
        'user.png','user.jpg','user.jpeg',
        'no-image.png','noimage.png','placeholder.png','blank.png',
    ];

    // Jika menunjuk ke assets/ (template) atau filename placeholder, anggap kosong
    if (strpos($norm, 'assets/') === 0) {
        $avatarPath = null;
    } elseif (in_array($base, $placeholders, true)) {
        $avatarPath = null;
    } else {
        $avatarPath = $p;
    }
}

$avatarUrl = $defaultAvatar;
if ($avatarPath) {
    // Kalau tersimpan sebagai URL penuh
    if (preg_match('~^(https?:)?//~i', $avatarPath)) {
        $avatarUrl = $avatarPath;
    } else {
        $rel = ltrim(str_replace('\\', '/', $avatarPath), '/');
        $avatarUrl = base_url($rel);

        $abs = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . $rel;
        if (is_file($abs)) {
            $avatarUrl .= (strpos($avatarUrl, '?') !== false ? '&' : '?') . 'v=' . @filemtime($abs);
        }
    }
}
?>

<div class="page-content">
  <div class="container-fluid">

    <!-- Title / Breadcrumb -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
          <h4 class="mb-sm-0">Profil Siswa</h4>
          <div class="page-title-right">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= route_to('student.dashboard') ?>">Dashboard</a></li>
              <li class="breadcrumb-item active">Profil</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Flash -->
    <?php if (session('success')): ?>
      <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php elseif (session('error')): ?>
      <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php elseif (session('info')): ?>
      <div class="alert alert-info"><?= esc(session('info')) ?></div>
    <?php endif; ?>

    <?php if ($mode === 'edit'): ?>
      <div class="alert alert-warning">
        Perubahan biodata resmi dilakukan oleh sekolah. Anda bisa mengubah
        <strong>Email, No. HP, dan Foto Profil</strong> melalui halaman
        <a class="alert-link" href="<?= base_url('/profile?mode=edit') ?>">Profil Akun</a>.
      </div>
    <?php endif; ?>

    <div class="row">
      <!-- Kolom kiri: Info akun & kelas -->
      <div class="col-xl-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Informasi Akun</h5>

            <div class="text-center mb-3">
              <img
                src="<?= esc($avatarUrl, 'attr') ?>"
                alt="Foto Profil"
                class="rounded-circle avatar-xl img-thumbnail"
                loading="lazy"
                style="object-fit:cover;"
                onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
              >
            </div>

            <div class="d-flex align-items-center mb-3">
              <div class="flex-grow-1">
                <div class="text-muted small">Nama</div>
                <div class="fw-semibold"><?= v($profile,'user_full_name', v($profile,'full_name','-')) ?></div>
              </div>
            </div>

            <p class="mb-2"><span class="text-muted">Email:</span><br><?= v($profile,'email','-') ?></p>
            <p class="mb-0"><span class="text-muted">Telepon:</span><br><?= v($profile,'phone','-') ?></p>

            <div class="mt-3 d-flex flex-wrap gap-2">
              <a class="btn btn-sm btn-primary" href="<?= base_url('/profile?mode=edit') ?>">
                <i class="ri-edit-2-line me-1"></i> Ubah Email/HP/Foto (Profil Akun)
              </a>
              <!--<a class="btn btn-sm btn-outline-secondary"
                 href="<?= route_to('messages.compose') ?>?subject=Permintaan%20Perubahan%20Biodata%20Resmi&body=Halo%2C%20saya%20memohon%20pembaruan%20biodata%20resmi.%20Terima%20kasih.">
                Ajukan Perubahan Biodata
              </a>-->
            </div>

            <hr>

            <div class="small text-muted">
              Bidang yang bisa Anda ubah sendiri:
              <ul class="mb-0">
                <?php foreach ($accountEditable as $f): ?>
                  <li><?= esc(ucwords(str_replace('_',' ',$f))) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Kelas</h5>
            <p class="mb-2">Kelas: <strong><?= v($profile,'class_name','-') ?></strong></p>
            <p class="mb-2">Tingkat: <strong><?= v($profile,'grade_level','-') ?></strong></p>
            <p class="mb-0">Jurusan: <strong><?= v($profile,'major','-') ?></strong></p>
          </div>
        </div>

        <!-- Identitas Siswa -->
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Identitas Siswa</h5>
            <p class="mb-2">NIS: <strong><?= v($profile,'nis','-') ?></strong></p>
            <p class="mb-0">NISN: <strong><?= v($profile,'nisn','-') ?></strong></p>
          </div>
        </div>
      </div>

      <!-- Kolom kanan: Data Pribadi (read-only) -->
      <div class="col-xl-8">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Data Pribadi</h5>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="text-muted small mb-1">Nama Lengkap</div>
                <div class="fw-semibold"><?= esc($valFullName) ?: '—' ?></div>
              </div>
              <div class="col-md-6">
                <div class="text-muted small mb-1">Telepon</div>
                <div class="fw-semibold"><?= esc($valPhone) ?: '—' ?></div>
              </div>
              <div class="col-md-6">
                <div class="text-muted small mb-1">Tempat Lahir</div>
                <div class="fw-semibold"><?= esc($valBirthPl) ?: '—' ?></div>
              </div>
              <div class="col-md-6">
                <div class="text-muted small mb-1">Tanggal Lahir</div>
                <div class="fw-semibold"><?= $valBirthDt ? esc($valBirthDt) : '—' ?></div>
              </div>
              <div class="col-12">
                <div class="text-muted small mb-1">Alamat</div>
                <div class="fw-semibold"><?= nl2br(esc($valAddress ?: '—')) ?></div>
              </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
              <a class="btn btn-primary" href="<?= base_url('/profile?mode=edit') ?>">
                <i class="ri-edit-2-line me-1"></i> Ubah Email/HP/Foto (Profil Akun)
              </a>
              <a class="btn btn-light" href="<?= route_to('student.dashboard') ?>">Kembali</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
