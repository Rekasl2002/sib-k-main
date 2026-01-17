<!-- app/Views/parent/child/profile.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
use CodeIgniter\I18n\Time;
helper(['url','form']);

if (!function_exists('v')) {
    function v($src, string $key, $default = '')
    {
        if (is_array($src)) return esc($src[$key] ?? $default);
        if (is_object($src)) return esc($src->$key ?? $default);
        return esc($default);
    }
}
if (!function_exists('fmt_date_id')) {
    function fmt_date_id(?string $date): string
    {
        if (!$date) return '';
        try { $t = Time::parse($date); return esc($t->toLocalizedString('dd MMMM yyyy')); }
        catch (\Throwable $e) { return esc($date); }
    }
}

/**
 * Avatar helper lokal:
 * - Jika profile_photo kosong / placeholder / asset template -> default svg
 * - Jika file gagal load -> onerror fallback ke default svg
 */
if (!function_exists('avatar_url')) {
    function avatar_url($profile): string
    {
        $defaultAvatar = base_url('assets/images/users/default-avatar.svg');

        $photoRaw = is_array($profile)
            ? ($profile['profile_photo'] ?? '')
            : (is_object($profile) ? ($profile->profile_photo ?? '') : '');

        $photoTrim = trim((string)$photoRaw);
        if ($photoTrim === '') {
            return $defaultAvatar;
        }

        $photoNorm = strtolower(ltrim(str_replace('\\', '/', $photoTrim), '/'));
        $photoBase = strtolower(basename($photoNorm));

        $placeholders = [
            'default-avatar.png','default-avatar.jpg','default-avatar.jpeg','default-avatar.svg',
            'avatar.png','avatar.jpg','avatar.jpeg',
            'user.png','user.jpg','user.jpeg',
            'no-image.png','noimage.png','placeholder.png','blank.png',
        ];

        // Jika menunjuk ke assets/ (avatar template), anggap tidak ada foto (kecuali default svg kita)
        if ((strpos($photoNorm, 'assets/') === 0 || strpos($photoNorm, 'public/assets/') === 0)
            && $photoNorm !== 'assets/images/users/default-avatar.svg'
        ) {
            return $defaultAvatar;
        }

        // Jika filename placeholder, anggap tidak ada foto (kecuali default svg kita)
        if (in_array($photoBase, $placeholders, true)
            && $photoNorm !== 'assets/images/users/default-avatar.svg'
        ) {
            return $defaultAvatar;
        }

        return base_url($photoTrim);
    }
}

// Normalisasi data dari controller
$profile = $profile ?? $student ?? [];

// ID siswa untuk route
$studentId = is_array($profile) ? ($profile['id'] ?? null) : (is_object($profile) ? ($profile->id ?? null) : null);

// Avatar
$defaultAvatar = base_url('assets/images/users/default-avatar.svg');
$avatarUrl     = avatar_url($profile);

// Field aman (hindari akses array langsung kalau suatu saat $profile object)
$birthDateRaw = is_array($profile) ? ($profile['birth_date'] ?? '') : (is_object($profile) ? ($profile->birth_date ?? '') : '');
$gradeLevel   = is_array($profile) ? ($profile['grade_level'] ?? '') : (is_object($profile) ? ($profile->grade_level ?? '') : '');

// Error khusus form
$errorsContact = session('errors_contact') ?? [];
$errorsPhoto   = session('errors_photo') ?? [];
?>

<div class="page-content">
  <div class="container-fluid">

    <!-- Breadcrumb -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
          <h4 class="mb-sm-0">Profil Anak</h4>

          <?php $siblings = $siblings ?? []; ?>
          <?php if (!empty($siblings)): ?>
            <div class="ms-sm-3">
              <form method="get" onChange="if(this.child_id.value){window.location='<?= base_url('parent/child') ?>/'+this.child_id.value+'/profile'}">
                <select name="child_id" class="form-select form-select-sm">
                  <?php foreach ($siblings as $s): ?>
                    <option value="<?= esc($s['id']) ?>" <?= ($s['id'] == $studentId) ? 'selected' : '' ?>>
                      <?= esc($s['full_name']) ?> — <?= esc($s['class_name'] ?? '-') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>
          <?php endif; ?>

          <div class="page-title-right">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= route_to('parent.dashboard') ?>">Dashboard</a></li>
              <li class="breadcrumb-item active">Profil Anak</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Flash -->
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php elseif (session()->getFlashdata('info')): ?>
      <div class="alert alert-info"><?= esc(session()->getFlashdata('info')) ?></div>
    <?php endif; ?>

    <div class="row">
      <!-- Kiri: Ringkasan akun anak -->
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body text-center">
            <img
              src="<?= esc($avatarUrl, 'attr') ?>"
              alt="Foto Anak"
              class="rounded-circle avatar-xl img-thumbnail mb-3"
              loading="lazy"
              style="object-fit:cover;"
              onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
            >

            <h4 class="mb-1"><?= v($profile, 'full_name', 'Nama belum diisi') ?></h4>
            <p class="text-muted mb-1"><?= v($profile, 'class_name', 'Kelas belum diatur') ?></p>
            <p class="text-muted mb-0 small">
              NISN: <?= v($profile, 'nisn', '-') ?> &nbsp; • &nbsp; NIS: <?= v($profile, 'nis', '-') ?>
            </p>
            <hr>
            <div class="text-start text-muted small">
              <p class="mb-1"><i class="mdi mdi-email-outline me-1"></i> <?= v($profile, 'email', '-') ?></p>
              <p class="mb-0"><i class="mdi mdi-phone-outline me-1"></i> <?= v($profile, 'phone', '-') ?></p>
            </div>
          </div>
        </div>

        <!-- Tautan cepat -->
        <?php if ($studentId): ?>
          <div class="card">
            <div class="card-body">
              <h6 class="card-title mb-3">Tautan Cepat</h6>
              <div class="d-grid gap-2">
                <a class="btn btn-outline-primary" href="<?= route_to('parent.children.violations', $studentId) ?>">
                  Riwayat Pelanggaran Anak
                </a>
                <a class="btn btn-outline-secondary" href="<?= route_to('parent.children.sessions', $studentId) ?>">
                  Sesi Konseling Anak
                </a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Kanan -->
      <div class="col-lg-8">
        <!-- Data pribadi anak (read-only) -->
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Data Pribadi Anak</h5>
            <div class="table-responsive">
              <table class="table table-borderless mb-0">
                <tbody>
                  <tr><th class="text-muted" style="width:30%">Nama Lengkap</th><td><?= v($profile, 'full_name', '-') ?></td></tr>
                  <tr><th class="text-muted">NISN / NIS</th><td><?= v($profile, 'nisn', '-') ?> / <?= v($profile, 'nis', '-') ?></td></tr>
                  <tr><th class="text-muted">Jenis Kelamin</th><td><?= v($profile, 'gender', '-') ?></td></tr>
                  <tr><th class="text-muted">Tempat, Tanggal Lahir</th><td><?= v($profile, 'birth_place', '-') ?>, <?= fmt_date_id($birthDateRaw) ?></td></tr>
                  <tr><th class="text-muted">Agama</th><td><?= v($profile, 'religion', '-') ?></td></tr>
                  <tr><th class="text-muted">Alamat</th><td><?= v($profile, 'address', '-') ?></td></tr>
                  <tr>
                    <th class="text-muted">Kelas</th>
                    <td>
                      <?= v($profile, 'class_name', '-') ?>
                      <?php if (!empty($gradeLevel)): ?>(<?= esc($gradeLevel) ?>)<?php endif; ?>
                    </td>
                  </tr>
                  <tr><th class="text-muted">Status Siswa</th><td><?= v($profile, 'status', '-') ?></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Panel: Ubah Kontak Anak -->
        <?php if ($studentId): ?>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title mb-3">Kelola Kontak & Foto Anak</h5>

              <div class="row g-4">
                <!-- Form kontak -->
                <div class="col-md-7">
                  <form action="<?= route_to('parent.children.contact', $studentId) ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                      <label class="form-label">Email Anak</label>
                      <input type="email" name="email"
                             value="<?= esc(old('email', is_array($profile) ? ($profile['email'] ?? '') : ($profile->email ?? ''))) ?>"
                             class="form-control <?= isset($errorsContact['email']) ? 'is-invalid' : '' ?>"
                             required>
                      <?php if (isset($errorsContact['email'])): ?>
                        <div class="invalid-feedback"><?= esc($errorsContact['email']) ?></div>
                      <?php endif; ?>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Nomor Telepon Anak</label>
                      <input type="text" name="phone"
                             value="<?= esc(old('phone', is_array($profile) ? ($profile['phone'] ?? '') : ($profile->phone ?? ''))) ?>"
                             class="form-control <?= isset($errorsContact['phone']) ? 'is-invalid' : '' ?>">
                      <?php if (isset($errorsContact['phone'])): ?>
                        <div class="invalid-feedback"><?= esc($errorsContact['phone']) ?></div>
                      <?php endif; ?>
                      <small class="text-muted">Boleh kosong. Jika diisi, harus diawali 08 dan terdiri dari 10–15 digit angka. Contoh: 081234567890.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Kontak</button>
                  </form>
                </div>

                <!-- Form foto -->
                <div class="col-md-5">
                  <form action="<?= route_to('parent.children.photo', $studentId) ?>" method="post" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                      <label class="form-label">Foto Profil Anak</label>
                      <input type="file" name="profile_photo"
                             accept=".jpg,.jpeg,.png,.webp"
                             class="form-control <?= isset($errorsPhoto['profile_photo']) ? 'is-invalid' : '' ?>"
                             required>
                      <?php if (isset($errorsPhoto['profile_photo'])): ?>
                        <div class="invalid-feedback"><?= esc($errorsPhoto['profile_photo']) ?></div>
                      <?php endif; ?>
                      <small class="text-muted">Maks 2MB, JPG/JPEG/PNG/WEBP.</small>
                    </div>

                    <button type="submit" class="btn btn-outline-primary">Unggah Foto</button>
                  </form>
                </div>
              </div>

              <hr class="my-4">

              <!-- Form Ajukan Perubahan untuk biodata resmi 
              <h6 class="mb-3">Ajukan Perubahan Biodata Resmi</h6>
              <form action="<?= route_to('parent.children.request_update', $studentId) ?>" method="post">
                <?= csrf_field() ?>
                <div id="req-fields">
                  <div class="mb-3 req-field">
                    <label class="form-label">Ringkasan data yang ingin diubah <span class="text-danger">*</span></label>
                    <input type="text" name="requested_fields[]" class="form-control" placeholder="Contoh: Alamat rumah, ejaan nama di ijazah" required>
                  </div>
                </div>
                <div class="mb-3">
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-field">+ Tambah poin perubahan</button>
                </div>
                <div class="mb-3">
                  <label class="form-label">Catatan tambahan</label>
                  <textarea name="notes" rows="3" class="form-control" placeholder="Berikan penjelasan singkat agar mudah diproses."></textarea>
                </div>
                <div class="d-flex justify-content-between">
                  <a href="<?= route_to('parent.children.index') ?>" class="btn btn-light">Kembali</a>
                  <button type="submit" class="btn btn-secondary">Kirim Permintaan</button>
                </div>
              </form>-->
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- Mini JS: tambah field permintaan -->
<script>
  (function() {
    const btn = document.getElementById('btn-add-field');
    if (!btn) return;
    btn.addEventListener('click', function() {
      const wrap = document.getElementById('req-fields');
      if (!wrap) return;
      const div = document.createElement('div');
      div.className = 'mb-3 req-field';
      div.innerHTML = `
        <label class="form-label">Ringkasan data yang ingin diubah</label>
        <input type="text" name="requested_fields[]" class="form-control" placeholder="Contoh: Nomor KK pada data siswa">
      `;
      wrap.appendChild(div);
    });
  })();
</script>

<?= $this->endSection() ?>
