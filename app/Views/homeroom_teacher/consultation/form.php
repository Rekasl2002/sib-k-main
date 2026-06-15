<?php
/**
 * View form Konsultasi & Pengaduan (dipakai semua peran).
 * Bahasa non-teknis agar mudah dipahami Guru Pesantren, siswa, dan orang tua.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$options = is_array($options ?? null) ? $options : [];
$routePrefix = (string) ($routePrefix ?? '');
$roleKey = (string) ($roleKey ?? '');
$action = (string) ($action ?? current_url());
$value = static fn(string $key, $default = '') => old($key, $row[$key] ?? $default);

// Jenis laporan yang boleh dipilih per peran (penguatan tambahan di sisi server menyusul).
$allTypes = ['Konsultasi', 'Pengaduan', 'Permintaan Konseling', 'Laporan Orang Tua', 'Laporan Wali Kelas', 'Lainnya/Tidak Bisa Menentukan'];
$requestTypes = match ($roleKey) {
    'siswa'      => ['Konsultasi', 'Pengaduan', 'Permintaan Konseling', 'Lainnya/Tidak Bisa Menentukan'],
    'orang-tua'  => ['Konsultasi', 'Pengaduan', 'Laporan Orang Tua', 'Permintaan Konseling', 'Lainnya/Tidak Bisa Menentukan'],
    'wali-kelas' => ['Konsultasi', 'Pengaduan', 'Laporan Wali Kelas', 'Permintaan Konseling', 'Lainnya/Tidak Bisa Menentukan'],
    default      => $allTypes,
};
?>
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0 text-dark"><?= esc($title ?? 'Konsultasi & Pengaduan') ?></h4>
      <a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a>
    </div>
  </div>
</div>

<form method="post" action="<?= esc($action, 'attr') ?>">
  <?= csrf_field() ?>
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Jenis Laporan</label>
              <select name="request_type" class="form-select">
                <?php foreach ($requestTypes as $type): ?>
                  <option value="<?= esc($type) ?>" <?= $value('request_type', 'Konsultasi') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Prioritas</label>
              <select name="priority" class="form-select">
                <?php foreach (['Rendah', 'Sedang', 'Tinggi', 'Mendesak'] as $priority): ?>
                  <option value="<?= esc($priority) ?>" <?= $value('priority', 'Sedang') === $priority ? 'selected' : '' ?>><?= esc($priority) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Siswa Terkait (dari data)</label>
              <select name="subject_student_id" class="form-select select2-search">
                <option value="">&mdash; pilih bila ada &mdash;</option>
                <?php foreach (($options['students'] ?? []) as $student): ?>
                  <option value="<?= esc((string) $student['id']) ?>" <?= (string) $value('subject_student_id') === (string) $student['id'] ? 'selected' : '' ?>>
                    <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-dark">Ketik untuk mencari nama siswa di data.</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">atau Nama Siswa/Pihak Lain (manual)</label>
              <input type="text" name="subject_other_name" class="form-control" value="<?= esc($value('subject_other_name')) ?>" placeholder="Untuk siswa/pihak yang belum terdaftar">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label text-dark">Judul/Topik/Masalah</label>
            <input type="text" name="title" class="form-control" required value="<?= esc($value('title')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label text-dark">Ceritakan kebutuhan atau kejadian</label>
            <textarea name="description" class="form-control" rows="6" required><?= esc($value('description')) ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Waktu Kejadian / Jadwal yang Diinginkan</label>
              <input type="datetime-local" name="occurred_at" class="form-control" value="<?= esc(str_replace(' ', 'T', substr((string) $value('occurred_at'), 0, 16))) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Tempat/Lokasi/Alamat</label>
              <textarea name="location" class="form-control" rows="2" placeholder="Contoh: Ruang kelas, rumah, atau alamat lengkap"><?= esc($value('location')) ?></textarea>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label text-dark">Saksi / Pihak Terkait</label>
            <textarea name="witness" class="form-control" rows="2" placeholder="Sebutkan saksi atau pihak terkait (boleh dikosongkan)"><?= esc($value('witness')) ?></textarea>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title text-dark"><i class="mdi mdi-information-outline me-2"></i>Penjelasan Jenis Laporan</h5>
          <ul class="mb-0 text-dark">
            <li><strong>Konsultasi</strong> &mdash; ingin berbicara atau meminta saran kepada Guru BK.</li>
            <li><strong>Pengaduan</strong> &mdash; melaporkan kejadian/masalah yang perlu ditindaklanjuti.</li>
            <li><strong>Permintaan Konseling</strong> &mdash; meminta dijadwalkan sesi konseling.</li>
            <li><strong>Laporan Orang Tua</strong> &mdash; laporan atau masukan dari orang tua.</li>
            <li><strong>Laporan Wali Kelas</strong> &mdash; laporan atau masukan dari wali kelas.</li>
            <li><strong>Lainnya/Tidak Bisa Menentukan</strong> &mdash; bila belum yakin termasuk jenis yang mana.</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark">Privasi &amp; Penanganan</h5>

          <div class="mb-3 border rounded p-2">
            <div class="form-check form-switch">
              <input type="hidden" name="visible_to_homeroom" value="0">
              <input class="form-check-input" type="checkbox" name="visible_to_homeroom" id="vh" value="1" <?= (string) $value('visible_to_homeroom') === '1' ? 'checked' : '' ?>>
              <label class="form-check-label text-dark" for="vh">Boleh dilihat Wali Kelas terkait</label>
            </div>
            <div class="small text-dark mt-1">Bila dimatikan, laporan tidak ditampilkan ke Wali Kelas. Isi rinci tetap hanya untuk Koordinator BK &amp; Guru BK.</div>
          </div>

          <?php if (! empty($canReview)): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Tugaskan ke (Guru BK)</label>
              <select name="assigned_to_user_id" class="form-select select2-search">
                <option value="">Belum ditugaskan</option>
                <?php foreach (($options['counselors'] ?? []) as $user): ?>
                  <option value="<?= esc((string) $user['id']) ?>" <?= (string) $value('assigned_to_user_id') === (string) $user['id'] ? 'selected' : '' ?>>
                    <?= esc($user['full_name'] ?? '-') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <button class="btn btn-primary w-100" type="submit"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
        </div>
      </div>
    </div>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(function () {
    if (window.jQuery && $.fn.select2) {
      $('.select2-search').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: 'Ketik untuk mencari...' });
    }
  });
</script>
<?= $this->endSection() ?>
