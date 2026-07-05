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
$valAge        = student_age_text($profile['birth_date'] ?? null);
$genderLabel   = (($profile['gender'] ?? '') === 'L') ? 'Laki-laki' : ((($profile['gender'] ?? '') === 'P') ? 'Perempuan' : '-');

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
              <li class="breadcrumb-item"><a href="<?= route_to('student.dashboard') ?>">Siswa</a></li>
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
            <p class="mb-2">NIK: <strong><?= v($profile,'nik','-') ?></strong></p>
            <p class="mb-0">NISN: <strong><?= v($profile,'nisn','-') ?></strong></p>
          </div>
        </div>
      </div>

      <!-- Kolom kanan: Data Pribadi -->
      <div class="col-xl-8">
        <div class="card">
          <div class="card-body">
            <?php
              $valReligion = old('religion', $profile['religion'] ?? '');
              $valSpecial  = old('special_needs', $profile['special_needs'] ?? '');
              $valDisab    = old('disability', $profile['disability'] ?? '');
              $valHobi     = old('hobi', $profile['hobi'] ?? '');
              $valEkskul   = old('ekskul_organisasi', $profile['ekskul_organisasi'] ?? '');
              $valKip      = old('kip_pip_number', $profile['kip_pip_number'] ?? '');
              $valFather   = old('father_name', $profile['father_name'] ?? '');
              $valMother   = old('mother_name', $profile['mother_name'] ?? '');
              $valGuardian = old('guardian_name', $profile['guardian_name'] ?? '');
              $valGender   = old('gender', $profile['gender'] ?? '');
            ?>

            <?php if ($mode === 'edit'): ?>
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="card-title mb-0">Ubah Biodata</h5>
                <a class="btn btn-sm btn-light" href="<?= route_to('student.profile') ?>">Batal</a>
              </div>
              <div class="alert alert-info">
                Anda boleh mengubah seluruh biodata Anda, <strong>kecuali</strong>
                Kelas, Tingkat, Jurusan, NISN, dan NIK (hanya sekolah yang dapat mengubahnya).
              </div>
              <form method="post" action="<?= route_to('student.profile.update') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label text-dark">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= esc($valFullName, 'attr') ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="<?= esc($valPhone, 'attr') ?>" placeholder="08xxxxxxxxxx">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Jenis Kelamin</label>
                    <select name="gender" class="form-select">
                      <option value="">- Pilih -</option>
                      <option value="L" <?= $valGender === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                      <option value="P" <?= $valGender === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Agama</label>
                    <select name="religion" class="form-select">
                      <option value="">- Pilih -</option>
                      <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                        <option value="<?= $ag ?>" <?= $valReligion === $ag ? 'selected' : '' ?>><?= $ag ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Tempat Lahir</label>
                    <input type="text" name="birth_place" class="form-control" value="<?= esc($valBirthPl, 'attr') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control" value="<?= esc($valBirthDt, 'attr') ?>" max="<?= esc($today, 'attr') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Hobi</label>
                    <input type="text" name="hobi" class="form-control" value="<?= esc($valHobi, 'attr') ?>" placeholder="mis. membaca, sepak bola">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Ekstrakurikuler/Organisasi</label>
                    <input type="text" name="ekskul_organisasi" class="form-control" value="<?= esc($valEkskul, 'attr') ?>" placeholder="mis. Pramuka, OSIS">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Kebutuhan Khusus</label>
                    <input type="text" name="special_needs" class="form-control" value="<?= esc($valSpecial, 'attr') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Disabilitas</label>
                    <input type="text" name="disability" class="form-control" value="<?= esc($valDisab, 'attr') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Nomor KIP/PIP</label>
                    <input type="text" name="kip_pip_number" class="form-control" value="<?= esc($valKip, 'attr') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Nama Ayah Kandung</label>
                    <input type="text" name="father_name" class="form-control" value="<?= esc($valFather, 'attr') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Nama Ibu Kandung</label>
                    <input type="text" name="mother_name" class="form-control" value="<?= esc($valMother, 'attr') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-dark">Nama Wali</label>
                    <input type="text" name="guardian_name" class="form-control" value="<?= esc($valGuardian, 'attr') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label text-dark">Alamat</label>
                    <textarea name="address" class="form-control" rows="2"><?= esc($valAddress) ?></textarea>
                  </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                  <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
                  <a class="btn btn-light" href="<?= route_to('student.profile') ?>">Batal</a>
                </div>
              </form>
            <?php else: ?>
              <h5 class="card-title mb-3">Data Pribadi</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Nama Lengkap</div>
                  <div class="fw-semibold text-dark"><?= esc($valFullName) ?: '—' ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Telepon</div>
                  <div class="fw-semibold text-dark"><?= esc($valPhone) ?: '—' ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Tempat Lahir</div>
                  <div class="fw-semibold text-dark"><?= esc($valBirthPl) ?: '—' ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Tanggal Lahir</div>
                  <div class="fw-semibold text-dark">
                    <?= $valBirthDt ? esc($valBirthDt) : '—' ?>
                    <?php if ($valAge !== '-'): ?><span class="text-dark">(<?= esc($valAge) ?>)</span><?php endif; ?>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Jenis Kelamin</div>
                  <div class="fw-semibold text-dark"><?= esc($genderLabel) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Agama</div>
                  <div class="fw-semibold text-dark"><?= esc($valReligion ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Hobi</div>
                  <div class="fw-semibold text-dark"><?= esc($valHobi ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Ekstrakurikuler/Organisasi</div>
                  <div class="fw-semibold text-dark"><?= esc($valEkskul ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Kebutuhan Khusus</div>
                  <div class="fw-semibold text-dark"><?= esc($valSpecial ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Disabilitas</div>
                  <div class="fw-semibold text-dark"><?= esc($valDisab ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Nomor KIP/PIP</div>
                  <div class="fw-semibold text-dark"><?= esc($valKip ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Nama Ayah Kandung</div>
                  <div class="fw-semibold text-dark"><?= esc($valFather ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Nama Ibu Kandung</div>
                  <div class="fw-semibold text-dark"><?= esc($valMother ?: '—') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="text-dark small mb-1">Nama Wali</div>
                  <div class="fw-semibold text-dark"><?= esc($valGuardian ?: '—') ?></div>
                </div>
                <div class="col-12">
                  <div class="text-dark small mb-1">Alamat</div>
                  <div class="fw-semibold text-dark"><?= nl2br(esc($valAddress ?: '—')) ?></div>
                </div>
              </div>

              <div class="mt-4 d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= route_to('student.profile') ?>?mode=edit">
                  <i class="mdi mdi-account-edit me-1"></i> Ubah Biodata
                </a>
                <a class="btn btn-outline-secondary" href="<?= base_url('/profile?mode=edit') ?>">
                  <i class="ri-edit-2-line me-1"></i> Ubah Email/Foto (Profil Akun)
                </a>
                <a class="btn btn-light" href="<?= route_to('student.dashboard') ?>">Kembali</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
