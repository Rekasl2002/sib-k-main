<?php
/**
 * View form layanan BK (seragam semua peran yang mengelola: Koordinator BK & Guru BK).
 * Fitur: Bimbingan/Konseling/Kolaborasi Orang Tua/Kunjungan Rumah/Konferensi Kasus.
 * Fase 4: istilah "Judul/Topik/Masalah" & "Tempat/Lokasi/Alamat".
 * Perbaikan Kedua:
 *  - Siswa/Kelas Sasaran dipilih sebagai DAFTAR lewat tombol "+ Tambah" + kotak chip
 *    (bukan satu pilihan). Pilihan pertama jadi subjek/target representatif.
 *  - Orang Tua & Wali Kelas digabung jadi satu "+ Tambah Peserta (dari data)".
 *  - Penanggung Jawab: Guru BK terkunci ke dirinya sendiri; Koordinator BK bebas
 *    memilih. Khusus Konferensi Kasus: label "Penanggung Jawab", hanya Koordinator
 *    BK; Guru BK tidak memilih siapa pun (dikosongkan, ditetapkan Koordinator).
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
$roleKey = (string) ($roleKey ?? '');
$isEdit = ! empty($row['id']);
$value = static function (string $key, $default = '') use ($row, $detail) {
    return old($key, $row[$key] ?? $detail[$key] ?? $default);
};

$selfId = (int) (session('user_id') ?? 0);
$selfName = trim((string) (session('full_name') ?? '')) ?: 'Saya (akun ini)';
$isGuruBk = ($roleKey === 'guru-bk');
$isKonferensi = ($serviceType === 'Konferensi Kasus');

// Penanggung Jawab: label & daftar pilihan sesuai jenis layanan.
$pjLabel = $isKonferensi ? 'Penanggung Jawab' : 'Guru BK/Penanggung Jawab';
$pjList = $isKonferensi ? ($options['coordinators'] ?? []) : ($options['counselors'] ?? []);
$pjValue = (string) $value('counselor_id', $isGuruBk && ! $isEdit && ! $isKonferensi ? (string) $selfId : '');

// Peserta tambahan yang sudah ada (untuk dikelola/hapus pada halaman edit).
$extraParticipants = [];
foreach ($participants as $p) {
    // Subjek utama (siswa/kelas sasaran) tidak ditampilkan di daftar peserta tambahan.
    $extraParticipants[] = $p;
}
$participantName = static function (array $p): string {
    return (string) ($p['participant_student_name'] ?? $p['participant_user_name'] ?? $p['participant_parent_name'] ?? $p['participant_class_name'] ?? $p['manual_name'] ?? '-');
};

// Helper membuat satu "chip" (kotak pilihan) berisi hidden input + tombol hapus.
$renderChip = static function (string $name, $val, string $text): string {
    return '<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;">'
        . '<span>' . esc($text) . '</span>'
        . '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc((string) $val, 'attr') . '">'
        . '<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>'
        . '</span>';
};

// Prefill subjek utama pada halaman edit (1 chip masing-masing).
$preStudentId = $isEdit ? (int) ($row['target_student_id'] ?? 0) : 0;
$preStudentText = trim((string) ($row['student_name'] ?? ''));
$preClassId = $isEdit ? (int) ($row['target_class_id'] ?? 0) : 0;
$preClassText = trim((string) ($row['class_name'] ?? ''));
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

          <!-- Siswa Sasaran: pilih satu per satu dengan tombol "+ Tambah" (boleh lebih dari satu). -->
          <div class="mb-3 js-multi" data-name="participant_student_ids[]">
            <label class="form-label text-dark">Siswa Sasaran <span class="text-dark fw-normal">(dari data &mdash; boleh lebih dari satu)</span></label>
            <div class="js-chips border rounded p-2 mb-2 bg-light">
              <?php if ($preStudentId > 0): ?>
                <?= $renderChip('participant_student_ids[]', $preStudentId, $preStudentText !== '' ? $preStudentText : ('Siswa #' . $preStudentId)) ?>
              <?php endif; ?>
              <span class="text-dark js-chip-empty"<?= $preStudentId > 0 ? ' style="display:none;"' : '' ?>>Belum ada siswa dipilih.</span>
            </div>
            <div class="d-flex gap-2 align-items-start">
              <div class="flex-grow-1">
                <select class="form-select select2-search js-picker">
                  <option value="">Ketik untuk mencari siswa&hellip;</option>
                  <?php foreach (($options['students'] ?? []) as $student): ?>
                    <option value="<?= esc((string) $student['id']) ?>">
                      <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="button" class="btn btn-primary flex-shrink-0 js-add"><i class="mdi mdi-plus me-1"></i> Tambah</button>
            </div>
            <small class="text-dark d-block mt-1">Cari nama siswa lalu tekan tombol biru <strong>Tambah</strong>. Ulangi untuk menambah lebih dari satu siswa.</small>
          </div>

          <!-- Kelas Sasaran: pola yang sama dengan Siswa Sasaran. -->
          <div class="mb-3 js-multi" data-name="participant_class_ids[]">
            <label class="form-label text-dark">Kelas Sasaran <span class="text-dark fw-normal">(dari data &mdash; boleh lebih dari satu)</span></label>
            <div class="js-chips border rounded p-2 mb-2 bg-light">
              <?php if ($preClassId > 0): ?>
                <?= $renderChip('participant_class_ids[]', $preClassId, $preClassText !== '' ? $preClassText : ('Kelas #' . $preClassId)) ?>
              <?php endif; ?>
              <span class="text-dark js-chip-empty"<?= $preClassId > 0 ? ' style="display:none;"' : '' ?>>Belum ada kelas dipilih.</span>
            </div>
            <div class="d-flex gap-2 align-items-start">
              <div class="flex-grow-1">
                <select class="form-select select2-search js-picker">
                  <option value="">Ketik untuk mencari kelas&hellip;</option>
                  <?php foreach (($options['classes'] ?? []) as $class): ?>
                    <option value="<?= esc((string) $class['id']) ?>"><?= esc($class['class_name'] ?? '-') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="button" class="btn btn-primary flex-shrink-0 js-add"><i class="mdi mdi-plus me-1"></i> Tambah</button>
            </div>
            <small class="text-dark d-block mt-1">Cari nama kelas lalu tekan tombol biru <strong>Tambah</strong>. Ulangi untuk menambah lebih dari satu kelas.</small>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark"><?= esc($pjLabel) ?></label>
              <?php if ($isGuruBk && $isKonferensi): ?>
                <?php // Guru BK tidak boleh memilih Penanggung Jawab Konferensi Kasus (hanya Koordinator BK). ?>
                <?php if ($isEdit && (int) $pjValue > 0): ?>
                  <input type="hidden" name="counselor_id" value="<?= esc($pjValue, 'attr') ?>">
                  <input type="text" class="form-control bg-light" value="<?= esc($row['counselor_name'] ?? 'Sudah ditetapkan') ?>" readonly>
                <?php else: ?>
                  <input type="text" class="form-control bg-light" value="Hanya Koordinator BK (ditetapkan oleh Koordinator BK)" disabled>
                <?php endif; ?>
                <small class="text-dark d-block mt-1">Penanggung jawab Konferensi Kasus hanya Koordinator BK. Anda tetap dapat mengisi data lainnya.</small>
              <?php elseif ($isGuruBk): ?>
                <?php
                  $lockVal = $isEdit ? $pjValue : (string) $selfId;
                  $lockName = ((string) $lockVal === (string) $selfId) ? $selfName : (string) ($row['counselor_name'] ?? $selfName);
                ?>
                <input type="hidden" name="counselor_id" value="<?= esc($lockVal, 'attr') ?>">
                <input type="text" class="form-control bg-light" value="<?= esc($lockName) ?>" readonly>
                <small class="text-dark d-block mt-1">Guru BK menugaskan dirinya sendiri sebagai penanggung jawab.</small>
              <?php else: ?>
                <select name="counselor_id" class="form-select select2-search">
                  <?php if ($isKonferensi): ?><option value="">Tidak dipilih</option><?php endif; ?>
                  <?php foreach ($pjList as $user): ?>
                    <option value="<?= esc((string) $user['id']) ?>" <?= $pjValue === (string) $user['id'] || ($pjValue === '' && (int) $user['id'] === $selfId) ? 'selected' : '' ?>>
                      <?= esc($user['full_name'] ?? '-') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-dark d-block mt-1"><?= $isKonferensi ? 'Hanya akun Koordinator BK. Bawaan: akun Anda sendiri.' : 'Bawaan: akun Anda sendiri. Boleh memilih Koordinator BK/Guru BK lain.' ?></small>
              <?php endif; ?>
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

          <!-- Peserta dari data (Orang Tua / Wali Kelas) digabung: cari nama lalu "+ Tambah". -->
          <div class="mb-3 js-multi" data-name="auto">
            <label class="form-label text-dark">Tambah Peserta (dari data)</label>
            <div class="js-chips border rounded p-2 mb-2 bg-light">
              <span class="text-dark js-chip-empty">Belum ada peserta dipilih.</span>
            </div>
            <div class="d-flex gap-2 align-items-start">
              <div class="flex-grow-1">
                <select class="form-select select2-search js-picker">
                  <option value="">Cari Orang Tua atau Wali Kelas&hellip;</option>
                  <optgroup label="Orang Tua">
                    <?php foreach (($options['parents'] ?? []) as $user): ?>
                      <option value="parent:<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                  <optgroup label="Wali Kelas">
                    <?php foreach (($options['homeroom_teachers'] ?? []) as $user): ?>
                      <option value="user:<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                </select>
              </div>
              <button type="button" class="btn btn-primary flex-shrink-0 js-add"><i class="mdi mdi-plus me-1"></i> Tambah</button>
            </div>
            <small class="text-dark d-block mt-1">Cari nama Orang Tua atau Wali Kelas lalu tekan <strong>Tambah</strong>. Bisa lebih dari satu.</small>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Peserta Tambahan (manual)</label>
            <div id="manualParticipants">
              <div class="input-group mb-2 manual-participant-row">
                <input type="text" name="manual_participants[]" class="form-control" placeholder="Nama - Peran (mis: Wali Kelas 1 - Wali Kelas)">
                <button type="button" class="btn btn-outline-danger remove-manual" title="Hapus baris"><i class="mdi mdi-minus"></i></button>
              </div>
            </div>
            <button type="button" id="addManualParticipant" class="btn btn-sm btn-primary"><i class="mdi mdi-plus me-1"></i> Tambah peserta manual</button>
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

    // ---- Pola "+ Tambah" untuk pemilihan banyak data (kotak chip) ----
    function addChip($widget, val, text) {
      if (!val) { return; }
      var name = $widget.data('name');
      var hiddenName, hiddenVal;
      if (name === 'auto') {
        var parts = String(val).split(':');
        hiddenName = (parts[0] === 'parent') ? 'participant_parent_ids[]' : 'participant_user_ids[]';
        hiddenVal = parts[1];
      } else {
        hiddenName = name;
        hiddenVal = val;
      }

      var $chips = $widget.find('.js-chips');
      var dup = false;
      $chips.find('input[type=hidden]').each(function () {
        if (this.name === hiddenName && this.value === String(hiddenVal)) { dup = true; }
      });
      if (dup) { return; }

      var $chip = $('<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;"></span>');
      $('<span></span>').text(text).appendTo($chip);
      $('<input type="hidden">').attr('name', hiddenName).val(hiddenVal).appendTo($chip);
      $('<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>').appendTo($chip);
      $widget.find('.js-chip-empty').hide().before($chip);
    }

    $('.js-multi .js-add').on('click', function () {
      var $widget = $(this).closest('.js-multi');
      var $picker = $widget.find('.js-picker');
      var val = $picker.val();
      var text = $.trim($picker.find('option:selected').text());
      addChip($widget, val, text);
      $picker.val('').trigger('change');
    });

    $('.js-multi').on('click', '.js-chip-remove', function () {
      var $widget = $(this).closest('.js-multi');
      $(this).closest('.js-chip').remove();
      if ($widget.find('.js-chip').length === 0) {
        $widget.find('.js-chip-empty').show();
      }
    });

    // ---- Peserta manual (baris dinamis) ----
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
