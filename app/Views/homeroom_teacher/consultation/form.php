<?php
/**
 * View form Konsultasi & Pengaduan (seragam semua peran).
 * Bahasa non-teknis untuk Guru Pesantren, siswa, dan orang tua.
 * Fase 2: subjek (siswa) lebih dari satu (data + manual + tombol "+"),
 * unggah lampiran bukti, dan sakelar privasi (boleh dilihat Wali Kelas/Orang Tua/Anak).
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$options = is_array($options ?? null) ? $options : [];
$subjects = is_array($subjects ?? null) ? $subjects : [];
$attachments = is_array($attachments ?? null) ? $attachments : [];
$routePrefix = (string) ($routePrefix ?? '');
$roleKey = (string) ($roleKey ?? '');
$action = (string) ($action ?? current_url());
$value = static fn(string $key, $default = '') => old($key, $row[$key] ?? $default);

$requestTypes = $options['request_types'] ?? ['Konsultasi', 'Pengaduan', 'Permintaan Konseling', 'Lainnya/Tidak Bisa Menentukan'];

// Subjek terpilih untuk prefill (edit).
$selectedStudentIds = [];
$manualNames = [];
foreach ($subjects as $sub) {
    if (! empty($sub['student_id'])) {
        $selectedStudentIds[] = (int) $sub['student_id'];
    } elseif (trim((string) ($sub['manual_name'] ?? '')) !== '') {
        $manualNames[] = (string) $sub['manual_name'];
    }
}
// Tambah (belum ada subjek) -> sediakan satu baris manual kosong.
if ($manualNames === []) {
    $manualNames[] = '';
}

// Sakelar privasi yang ditampilkan per peran.
$showHomeroom = in_array($roleKey, ['siswa', 'orang-tua', 'wali-kelas'], true) || ! empty($canReview);
$showParent   = in_array($roleKey, ['siswa', 'wali-kelas'], true) || ! empty($canReview);
$showStudent  = in_array($roleKey, ['orang-tua', 'wali-kelas'], true) || ! empty($canReview);
?>
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0 text-dark"><?= esc($title ?? 'Konsultasi & Pengaduan') ?></h4>
      <a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?= esc($action, 'attr') ?>" enctype="multipart/form-data">
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

          <div class="mb-3">
            <label class="form-label text-dark">Siswa Terkait (dari data) &mdash; boleh lebih dari satu</label>
            <select name="subject_student_ids[]" class="form-select select2-search" multiple>
              <?php foreach (($options['students'] ?? []) as $student): ?>
                <option value="<?= esc((string) $student['id']) ?>" <?= in_array((int) $student['id'], $selectedStudentIds, true) ? 'selected' : '' ?>>
                  <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-dark">Ketik untuk mencari nama siswa di data. Bisa memilih lebih dari satu.</small>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">atau Nama Siswa/Pihak Lain (manual)</label>
            <div id="manualSubjects">
              <?php foreach ($manualNames as $name): ?>
                <div class="input-group mb-2 manual-subject-row">
                  <input type="text" name="subject_manual_names[]" class="form-control" value="<?= esc($name) ?>" placeholder="Untuk siswa/pihak yang belum terdaftar">
                  <button type="button" class="btn btn-outline-danger remove-manual" title="Hapus baris"><i class="mdi mdi-minus"></i></button>
                </div>
              <?php endforeach; ?>
            </div>
            <button type="button" id="addManualSubject" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-plus me-1"></i> Tambah nama manual</button>
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

      <!-- Lampiran/Bukti -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title text-dark"><i class="mdi mdi-paperclip me-2"></i>Lampiran/Bukti (opsional)</h5>
          <input type="file" name="attachments[]" class="form-control" multiple>
          <small class="text-dark d-block mt-1">Maksimal 5 berkas, masing-masing 5 MB. Jenis: gambar, PDF, dokumen Office, teks, atau zip.</small>

          <?php if (! empty($attachments)): ?>
            <hr>
            <p class="text-dark mb-2">Lampiran yang sudah ada:</p>
            <ul class="list-group">
              <?php foreach ($attachments as $att): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <a href="<?= site_url($routePrefix . '/attachment/' . (int) $att['id']) ?>" class="text-dark"><i class="mdi mdi-file me-1"></i><?= esc($att['file_name'] ?: $att['file_path']) ?></a>
                  <?php if ((int) ($att['uploaded_by'] ?? 0) === (int) (session('user_id') ?? 0)): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger js-del-att" data-id="<?= (int) $att['id'] ?>" title="Hapus lampiran"><i class="mdi mdi-delete"></i></button>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
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
          <p class="small text-dark">Isi rinci laporan hanya untuk Koordinator BK &amp; Guru BK. Atur di bawah ini siapa lagi yang boleh melihat ringkasannya.</p>

          <?php if ($showHomeroom): ?>
            <div class="mb-2 border rounded p-2">
              <div class="form-check form-switch">
                <input type="hidden" name="visible_to_homeroom" value="0">
                <input class="form-check-input" type="checkbox" name="visible_to_homeroom" id="vh" value="1" <?= (string) $value('visible_to_homeroom') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label text-dark" for="vh">Boleh dilihat Wali Kelas terkait</label>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($showParent): ?>
            <div class="mb-2 border rounded p-2">
              <div class="form-check form-switch">
                <input type="hidden" name="visible_to_parent" value="0">
                <input class="form-check-input" type="checkbox" name="visible_to_parent" id="vp" value="1" <?= (string) $value('visible_to_parent') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label text-dark" for="vp">Boleh dilihat Orang Tua siswa terkait</label>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($showStudent): ?>
            <div class="mb-3 border rounded p-2">
              <div class="form-check form-switch">
                <input type="hidden" name="visible_to_student" value="0">
                <input class="form-check-input" type="checkbox" name="visible_to_student" id="vs" value="1" <?= (string) $value('visible_to_student') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label text-dark" for="vs">Boleh dilihat Siswa terkait</label>
              </div>
            </div>
          <?php endif; ?>

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

<!-- Form tersembunyi untuk hapus lampiran -->
<form id="delAttForm" method="post" action="" class="d-none"><?= csrf_field() ?></form>

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

    // Tambah/hapus baris nama manual.
    $('#addManualSubject').on('click', function () {
      var row = $(
        '<div class="input-group mb-2 manual-subject-row">' +
        '<input type="text" name="subject_manual_names[]" class="form-control" placeholder="Untuk siswa/pihak yang belum terdaftar">' +
        '<button type="button" class="btn btn-outline-danger remove-manual" title="Hapus baris"><i class="mdi mdi-minus"></i></button>' +
        '</div>'
      );
      $('#manualSubjects').append(row);
    });
    $('#manualSubjects').on('click', '.remove-manual', function () {
      $(this).closest('.manual-subject-row').remove();
    });

    // Hapus lampiran via form POST.
    var base = '<?= site_url($routePrefix . '/attachment-delete/') ?>';
    $('.js-del-att').on('click', function () {
      if (!confirm('Hapus lampiran ini?')) return;
      var id = $(this).data('id');
      var f = document.getElementById('delAttForm');
      f.action = base + id;
      f.submit();
    });
  });
</script>
<?= $this->endSection() ?>
