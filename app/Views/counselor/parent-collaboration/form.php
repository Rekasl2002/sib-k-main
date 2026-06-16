<?php
/**
 * View form layanan BK (seragam semua peran yang mengelola: Koordinator BK & Guru BK).
 * Fitur: Bimbingan/Konseling/Kolaborasi Orang Tua/Kunjungan Rumah/Konferensi Kasus.
 * Fase 4: istilah "Judul/Topik/Masalah" & "Tempat/Lokasi/Alamat", pemilihan
 * Siswa/Kelas/Orang Tua/Wali Kelas dari data + manual (Select2 + tombol "+"),
 * "Peserta Tambahan dan Catatan", serta izin "boleh dilihat Wali Kelas" (bawaan mati).
 * Field khusus ditampilkan berdasarkan $serviceType.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$detail = is_array($row['detail'] ?? null) ? $row['detail'] : [];
$participants = is_array($row['participants'] ?? null) ? $row['participants'] : [];
$options = is_array($options ?? null) ? $options : [];
$action = (string) ($action ?? current_url());
$routePrefix = (string) ($routePrefix ?? '');
$serviceType = (string) ($serviceType ?? '');
$isEdit = ! empty($row['id']);
$value = static function (string $key, $default = '') use ($row, $detail) {
    return old($key, $row[$key] ?? $detail[$key] ?? $default);
};

// Peserta tambahan yang sudah ada (untuk dikelola/hapus pada halaman edit).
$extraParticipants = [];
foreach ($participants as $p) {
    // Subjek utama (siswa/kelas sasaran) tidak ditampilkan di daftar peserta tambahan.
    $extraParticipants[] = $p;
}
$participantName = static function (array $p): string {
    return (string) ($p['participant_student_name'] ?? $p['participant_user_name'] ?? $p['participant_parent_name'] ?? $p['participant_class_name'] ?? $p['manual_name'] ?? '-');
};
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0 text-dark"><?= esc($title ?? 'Form Layanan BK') ?></h4>
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

<form method="post" action="<?= esc($action, 'attr') ?>">
  <?= csrf_field() ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark">Data Utama</h5>
          <div class="mb-3">
            <label class="form-label text-dark">Judul/Topik/Masalah</label>
            <input type="text" name="title" class="form-control" required value="<?= esc($value('title')) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Siswa Sasaran (dari data)</label>
              <select name="target_student_id" class="form-select select2-search">
                <option value="">Tidak dipilih</option>
                <?php foreach (($options['students'] ?? []) as $student): ?>
                  <option value="<?= esc((string) $student['id']) ?>" <?= (string) $value('target_student_id') === (string) $student['id'] ? 'selected' : '' ?>>
                    <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Kelas Sasaran (dari data)</label>
              <select name="target_class_id" class="form-select select2-search">
                <option value="">Tidak dipilih</option>
                <?php foreach (($options['classes'] ?? []) as $class): ?>
                  <option value="<?= esc((string) $class['id']) ?>" <?= (string) $value('target_class_id') === (string) $class['id'] ? 'selected' : '' ?>>
                    <?= esc($class['class_name'] ?? '-') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Guru BK/Penanggung Jawab</label>
              <select name="counselor_id" class="form-select select2-search">
                <?php foreach (($options['counselors'] ?? []) as $user): ?>
                  <option value="<?= esc((string) $user['id']) ?>" <?= (string) $value('counselor_id', session('user_id')) === (string) $user['id'] ? 'selected' : '' ?>>
                    <?= esc($user['full_name'] ?? '-') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Draft','Dijadwalkan','Berlangsung','Selesai','Dibatalkan','Perlu Tindak Lanjut'] as $status): ?>
                  <option value="<?= esc($status) ?>" <?= $value('status', 'Dijadwalkan') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Tanggal &amp; Jam Kegiatan</label>
              <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= esc(str_replace(' ', 'T', substr((string) $value('scheduled_at'), 0, 16))) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Lama Kegiatan (menit)</label>
              <input type="number" name="duration_minutes" class="form-control" value="<?= esc($value('duration_minutes', 60)) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label text-dark">Tempat/Lokasi/Alamat</label>
            <textarea name="location" class="form-control" rows="2" placeholder="Contoh: Ruang BK, ruang kelas, atau alamat lengkap"><?= esc($value('location')) ?></textarea>
          </div>

          <?php if ($serviceType === 'Bimbingan'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Jenis Bimbingan</label>
              <select name="guidance_type" class="form-select" id="guidanceType">
                <?php foreach (['Kelompok','Klasikal','Kelas Besar'] as $type): ?>
                  <option value="<?= esc($type) ?>" <?= $value('guidance_type', 'Klasikal') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Ringkasan Materi</label>
              <textarea name="summary" class="form-control" rows="4"><?= esc($value('summary')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Konseling'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Jenis Konseling</label>
              <select name="counseling_type" class="form-select">
                <?php foreach (['Individu','Kelompok'] as $type): ?>
                  <option value="<?= esc($type) ?>" <?= $value('counseling_type', 'Individu') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Deskripsi Masalah</label>
              <textarea name="problem_description" class="form-control" rows="4"><?= esc($value('problem_description')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Rencana Tindak Lanjut</label>
              <textarea name="follow_up_plan" class="form-control" rows="3"><?= esc($value('follow_up_plan')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Kolaborasi Orang Tua'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Ringkasan dan Tindak Lanjut</label>
              <textarea name="summary" class="form-control" rows="4"><?= esc($value('summary')) ?></textarea>
            </div>
            <small class="text-dark d-block mb-2"><i class="mdi mdi-information-outline me-1"></i>Pilih Orang Tua yang hadir pada kartu <strong>Peserta Tambahan dan Catatan</strong> di samping (dari data atau tulis manual). Mereka otomatis tercatat sebagai peserta dan bisa diatur kehadirannya.</small>
          <?php elseif ($serviceType === 'Kunjungan Rumah'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Hasil Kunjungan</label>
              <textarea name="visit_result" class="form-control" rows="4"><?= esc($value('visit_result')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Tindak Lanjut</label>
              <textarea name="follow_up" class="form-control" rows="3"><?= esc($value('follow_up')) ?></textarea>
            </div>
            <small class="text-dark d-block mb-2"><i class="mdi mdi-information-outline me-1"></i>Alamat kunjungan diisi pada kolom <strong>Tempat/Lokasi/Alamat</strong> di atas. Pilih Orang Tua &amp; Wali Kelas yang ditemui pada kartu <strong>Peserta Tambahan dan Catatan</strong>.</small>
          <?php elseif ($serviceType === 'Konferensi Kasus'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Kronologi/Ringkasan Masalah</label>
              <textarea name="chronology" class="form-control" rows="3"><?= esc($value('chronology')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Pembahasan dan Keputusan</label>
              <textarea name="decision_summary" class="form-control" rows="4"><?= esc($value('decision_summary')) ?></textarea>
            </div>
            <small class="text-dark d-block mb-2"><i class="mdi mdi-information-outline me-1"></i>Pilih Orang Tua &amp; Wali Kelas yang hadir pada kartu <strong>Peserta Tambahan dan Catatan</strong> (dari data atau tulis manual).</small>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($isEdit && $extraParticipants): ?>
      <!-- Daftar peserta yang sudah tercatat (bisa dihapus saat edit) -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark"><i class="mdi mdi-account-multiple me-2"></i>Peserta/Undangan Tercatat</h5>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light">
                <tr><th>Nama</th><th>Peran</th><th>Kehadiran</th><th class="text-end">Aksi</th></tr>
              </thead>
              <tbody>
                <?php foreach ($extraParticipants as $p): ?>
                  <tr>
                    <td class="text-dark"><?= esc($participantName($p)) ?></td>
                    <td class="text-dark"><?= esc($p['role_in_session'] ?? '-') ?></td>
                    <td class="text-dark"><?= esc($p['attendance_status'] ?? '-') ?></td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-danger js-del-participant"
                              data-id="<?= (int) $p['id'] ?>" title="Hapus peserta" data-bs-toggle="tooltip">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <small class="text-dark d-block mt-2">Pengaturan kehadiran rinci dilakukan di halaman Detail.</small>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark"><i class="mdi mdi-account-plus me-2"></i>Peserta Tambahan dan Catatan</h5>

          <div class="mb-3">
            <label class="form-label text-dark">Siswa (dari data) &mdash; boleh lebih dari satu</label>
            <select name="participant_student_ids[]" class="form-select select2-search" multiple>
              <?php foreach (($options['students'] ?? []) as $student): ?>
                <option value="<?= esc((string) $student['id']) ?>">
                  <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Kelas (dari data) &mdash; boleh lebih dari satu</label>
            <select name="participant_class_ids[]" class="form-select select2-search" multiple>
              <?php foreach (($options['classes'] ?? []) as $class): ?>
                <option value="<?= esc((string) $class['id']) ?>"><?= esc($class['class_name'] ?? '-') ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Orang Tua (dari data)</label>
            <select name="participant_parent_ids[]" class="form-select select2-search" multiple>
              <?php foreach (($options['parents'] ?? []) as $user): ?>
                <option value="<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Wali Kelas (dari data)</label>
            <select name="participant_user_ids[]" class="form-select select2-search" multiple>
              <?php foreach (($options['homeroom_teachers'] ?? []) as $user): ?>
                <option value="<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Peserta Tambahan (manual)</label>
            <div id="manualParticipants">
              <div class="input-group mb-2 manual-participant-row">
                <input type="text" name="manual_participants[]" class="form-control" placeholder="Nama - Peran (mis: Wali Kelas 1 - Wali Kelas)">
                <button type="button" class="btn btn-outline-danger remove-manual" title="Hapus baris"><i class="mdi mdi-minus"></i></button>
              </div>
            </div>
            <button type="button" id="addManualParticipant" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-plus me-1"></i> Tambah peserta manual</button>
            <small class="text-dark d-block mt-1">Untuk pihak yang belum terdaftar di data. Format: Nama - Peran.</small>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Catatan <?= $isEdit ? 'Baru' : 'Awal' ?></label>
            <textarea name="initial_note" class="form-control" rows="4"><?= $isEdit ? '' : esc($value('initial_note')) ?></textarea>
          </div>

          <div class="mb-3 border rounded p-2">
            <div class="form-check form-switch">
              <input type="hidden" name="visible_to_homeroom" value="0">
              <input class="form-check-input" type="checkbox" name="visible_to_homeroom" id="vh" value="1" <?= (string) $value('visible_to_homeroom') === '1' ? 'checked' : '' ?>>
              <label class="form-check-label text-dark" for="vh">Boleh dilihat Wali Kelas terkait</label>
            </div>
            <small class="text-dark">Bila dimatikan, Wali Kelas hanya melihat jadwal &mdash; bukan catatan rinci. Bawaan: mati.</small>
          </div>

          <button class="btn btn-primary w-100" type="submit"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
        </div>
      </div>
    </div>
  </div>
</form>

<?php if ($isEdit): ?>
<form id="delParticipantForm" method="post" action="<?= site_url($routePrefix . '/participant-delete/0') ?>" class="d-none">
  <?= csrf_field() ?>
  <input type="hidden" name="record_id" value="<?= (int) $row['id'] ?>">
  <input type="hidden" name="back" value="edit">
</form>
<?php endif; ?>
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

    $('#addManualParticipant').on('click', function () {
      var row = $(
        '<div class="input-group mb-2 manual-participant-row">' +
        '<input type="text" name="manual_participants[]" class="form-control" placeholder="Nama - Peran">' +
        '<button type="button" class="btn btn-outline-danger remove-manual" title="Hapus baris"><i class="mdi mdi-minus"></i></button>' +
        '</div>'
      );
      $('#manualParticipants').append(row);
    });
    $('#manualParticipants').on('click', '.remove-manual', function () {
      $(this).closest('.manual-participant-row').remove();
    });

    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) { return new bootstrap.Tooltip(el); });

    var f = document.getElementById('delParticipantForm');
    if (f) {
      var base = '<?= site_url($routePrefix . '/participant-delete/') ?>';
      $('.js-del-participant').on('click', function () {
        if (!confirm('Hapus peserta ini?')) return;
        f.action = base + $(this).data('id');
        f.submit();
      });
    }
  });
</script>
<?= $this->endSection() ?>
