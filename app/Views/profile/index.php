<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/profile/index.php
 *
 * Profile Page
 * - Menampilkan informasi akun
 * - Update data akun terbatas sesuai $editable
 * - Menampilkan info tambahan untuk Siswa/Orang Tua
 * - Menyertakan form ubah password (partial)
 */

helper(['url', 'form']);

// Normalisasi variabel dari controller
$user      = $user      ?? [];
$roleName  = $roleName  ?? 'User';
$editable  = $editable  ?? [];
$student   = $student   ?? null;
$children  = $children  ?? [];
$errors    = session('errors') ?? [];

// ✅ Default avatar sesuai ketentuan (SVG)
$defaultAvatar = base_url('assets/images/users/default-avatar.svg');

// Avatar: utamakan session supaya sinkron dengan topbar
$avatarPathRaw = session('profile_photo') ?: ($user['profile_photo'] ?? null);

// Normalisasi avatar path (hindari placeholder lama seperti default-avatar.png)
$avatarPath = null;
if ($avatarPathRaw) {
    $p = trim((string) $avatarPathRaw);
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

// Build URL avatar + cache busting kalau file ada
$avatarUrl = $defaultAvatar;
if ($avatarPath) {
    $rel = ltrim(str_replace('\\', '/', $avatarPath), '/');
    $avatarUrl = base_url($rel);

    $abs = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . $rel;
    if (is_file($abs)) {
        $avatarUrl .= (str_contains($avatarUrl, '?') ? '&' : '?') . 'v=' . @filemtime($abs);
    }
}

// Prefill dengan old() agar tidak hilang setelah validasi gagal
$valFullName = old('full_name', $user['full_name'] ?? '');
$valEmail    = old('email',     $user['email']    ?? '');
$valPhone    = old('phone',     $user['phone']    ?? '');

// Peran terbatas => tombol Ajukan Perubahan (opsional, saat ini masih komentar)
$restricted = ($roleName === 'Orang Tua' || $roleName === 'Siswa');
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="row align-items-center mb-3">
      <div class="col">
        <h4 class="mb-0">PROFIL PENGGUNA</h4>
      </div>
      <div class="col-auto text-muted">
        <small><?= esc($roleName) ?></small>
      </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php elseif (session()->getFlashdata('info')): ?>
      <div class="alert alert-info"><?= esc(session()->getFlashdata('info')) ?></div>
    <?php endif; ?>

    <div class="row">
      <!-- Kiri: Profil & Form Update Data Akun -->
      <div class="col-lg-7">
        <div class="card">
          <div class="card-body">

            <div class="d-flex align-items-center mb-3">
              <div class="me-3">
                <!-- ✅ Selalu pakai IMG, kosong => default-avatar.svg -->
                <img
                  src="<?= esc($avatarUrl, 'attr') ?>"
                  alt="Foto Profil"
                  class="rounded-circle img-thumbnail"
                  width="72"
                  height="72"
                  loading="lazy"
                  style="object-fit:cover;"
                  onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
                >
              </div>
              <div>
                <div class="h5 mb-0"><?= esc($user['full_name'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($user['email'] ?? '-') ?></small>
              </div>
            </div>

            <form action="<?= route_to('profile.update') ?>" method="post" enctype="multipart/form-data" novalidate>
              <?= csrf_field() ?>

              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= esc($user['username'] ?? '-') ?>" disabled>
              </div>

              <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input
                  name="full_name"
                  type="text"
                  class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                  value="<?= esc($valFullName) ?>"
                  <?= in_array('full_name', $editable, true) ? '' : 'disabled' ?>>
                <?php if (isset($errors['full_name'])): ?>
                  <div class="invalid-feedback"><?= esc($errors['full_name']) ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                  name="email"
                  type="email"
                  class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                  value="<?= esc($valEmail) ?>"
                  <?= in_array('email', $editable, true) ? '' : 'disabled' ?>>
                <?php if (isset($errors['email'])): ?>
                  <div class="invalid-feedback"><?= esc($errors['email']) ?></div>
                <?php endif; ?>
                <?php if (!in_array('email', $editable, true)): ?>
                  <small class="text-muted">Email dikelola oleh sekolah. Untuk perubahan, gunakan Ajukan Perubahan.</small>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label class="form-label">Nomor Telepon</label>
                <input
                  name="phone"
                  type="text"
                  class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                  value="<?= esc($valPhone) ?>"
                  <?= in_array('phone', $editable, true) ? '' : 'disabled' ?>>
                <?php if (isset($errors['phone'])): ?>
                  <div class="invalid-feedback"><?= esc($errors['phone']) ?></div>
                <?php endif; ?>
                <small class="text-muted">Boleh kosong. Jika diisi, gunakan format angka yang valid. Contoh: 081234567890.</small>
              </div>

              <div class="mb-3">
                <label class="form-label">Foto Profil</label>
                <input
                  name="profile_photo"
                  type="file"
                  accept=".jpg,.jpeg,.png,.webp"
                  class="form-control <?= isset($errors['profile_photo']) ? 'is-invalid' : '' ?>"
                  <?= in_array('profile_photo', $editable, true) ? '' : 'disabled' ?>>
                <?php if (isset($errors['profile_photo'])): ?>
                  <div class="invalid-feedback"><?= esc($errors['profile_photo']) ?></div>
                <?php endif; ?>
                <small class="text-muted">Maks 2MB, format: JPG, JPEG, PNG, WEBP. Rekomendasi Foto berukuran 256 x 256 kotak.</small>
              </div>

              <?php if (!empty($editable)): ?>
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                  <a href="<?= current_url() ?>" class="btn btn-light">Batalkan</a>
                </div>
              <?php else: ?>
                <div class="alert alert-info mb-0">
                  Data profil Anda saat ini tidak dapat diubah melalui halaman ini.
                </div>
              <?php endif; ?>

              <?php if ($restricted): ?>
                <!--
                <a class="btn btn-outline-secondary ms-2 mt-2"
                   href="<?= route_to('messages.compose') ?>?subject=Permintaan%20Perubahan%20Profil&body=Halo%2C%20saya%20memohon%20pembaruan%20data%20profil.%20Terima%20kasih.">
                  Ajukan Perubahan
                </a>
                -->
              <?php endif; ?>
            </form>

          </div>
        </div>
      </div>

      <!-- Kanan: Info Tambahan & Ganti Password -->
      <div class="col-lg-5">
        <?php if ($roleName === 'Siswa' && $student): ?>
          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title mb-3">Data Siswa (Ringkas)</h5>
              <div class="mb-2"><strong>Nama:</strong> <?= esc($student['full_name'] ?? '-') ?></div>
              <div class="mb-2"><strong>NISN:</strong> <?= esc($student['nisn'] ?? '-') ?></div>
              <div class="mb-2"><strong>Kelas:</strong> <?= esc($student['class_name'] ?? '-') ?></div>
              <p class="text-muted mb-0">
                <em>Perubahan biodata resmi dilakukan oleh sekolah. Anda dapat mengubah Email/Nomor Telepon/Foto di halaman ini.</em>
              </p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($roleName === 'Orang Tua' && !empty($children)): ?>
          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title mb-3">Anak Terkait</h5>
              <ul class="list-unstyled mb-0">
                <?php foreach ($children as $c): ?>
                  <li class="mb-3 d-flex justify-content-between align-items-start">
                    <div>
                      <a href="<?= route_to('parent.children.profile', $c['id']) ?>" class="fw-semibold">
                        <?= esc($c['full_name'] ?? '-') ?>
                      </a>
                      <div class="text-muted small">
                        NISN: <?= esc($c['nisn'] ?? '-') ?> • Kelas: <?= esc($c['class_name'] ?? '-') ?>
                      </div>
                    </div>
                    <div class="text-nowrap ms-3">
                      <a class="btn btn-sm btn-outline-primary"
                        href="<?= route_to('parent.children.profile', $c['id']) ?>">Profil</a>
                      <a class="btn btn-sm btn-outline-danger"
                        href="<?= route_to('parent.children.violations', $c['id']) ?>">Pelanggaran</a>
                      <a class="btn btn-sm btn-outline-secondary"
                        href="<?= route_to('parent.children.sessions', $c['id']) ?>">Konseling</a>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>

              <!--
              <a class="btn btn-outline-secondary mt-3"
                 href="<?= route_to('messages.compose') ?>?subject=Permintaan%20Perubahan%20Data%20Anak&body=Halo%2C%20saya%20memohon%20pembaruan%20data%20anak.%20Terima%20kasih.">
                Ajukan Perubahan Data Anak
              </a>
              -->
            </div>
          </div>
        <?php endif; ?>

        <!-- ✅ Form Ganti Password -->
        <?= $this->include('profile/_change_password') ?>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
