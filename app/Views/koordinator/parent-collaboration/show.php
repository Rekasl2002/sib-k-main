<?php
/**
 * View detail layanan BK (seragam semua peran).
 * Menampilkan detail, peserta/undangan (atur kehadiran + hapus), catatan
 * (hapus milik sendiri), dan tambah catatan. Akses catatan sudah disaring di
 * service sesuai kerahasiaan (Siswa/Orang Tua tanpa catatan; Wali Kelas hanya
 * bila diizinkan). Fase 4.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$detail = is_array($row['detail'] ?? null) ? $row['detail'] : [];
$participants = is_array($row['participants'] ?? null) ? $row['participants'] : [];
$notes = is_array($row['notes'] ?? null) ? $row['notes'] : [];
$routePrefix = (string) ($routePrefix ?? '');
$serviceType = (string) ($serviceType ?? '');
$canManage = ! empty($canManage);
$me = (int) (session('user_id') ?? 0);
// Model "hanya catat yang TIDAK hadir" HANYA untuk Bimbingan Klasikal/Kelas Besar
// (audiens besar). Layanan lain (Konseling, Kolaborasi Ortu, Kunjungan Rumah,
// Konferensi Kasus) & Bimbingan Kelompok memakai daftar Peserta/Undangan biasa.
$isAbsenteeMode = ($serviceType === 'Bimbingan')
    && in_array((string) ($detail['guidance_type'] ?? ''), ['Klasikal', 'Kelas Besar'], true);

$hiddenDetailKeys = ['id','bk_service_record_id','created_at','updated_at','deleted_at','deleted_by',
    'student_id','class_id','counselor_id','session_id','status','session_date','session_time','location',
    'topic','duration_minutes','session_type','privacy_level','is_confidential'];
$detailLabels = [
    'guidance_type' => 'Jenis Bimbingan', 'material_topic' => 'Materi', 'summary' => 'Ringkasan',
    'counseling_type' => 'Jenis Konseling', 'problem_description' => 'Deskripsi Masalah',
    'session_summary' => 'Ringkasan Sesi', 'follow_up_plan' => 'Rencana Tindak Lanjut',
    'follow_up_status' => 'Status Tindak Lanjut', 'parent_name' => 'Orang Tua/Pihak Hadir',
    'topic' => 'Topik', 'follow_up' => 'Tindak Lanjut', 'address_snapshot' => 'Alamat',
    'problem_topic' => 'Topik Masalah', 'visit_result' => 'Hasil Kunjungan',
    'chronology' => 'Kronologi', 'discussion_summary' => 'Pembahasan', 'decision_summary' => 'Keputusan',
];
$participantName = static function (array $p): string {
    return (string) ($p['participant_student_name'] ?? $p['participant_user_name'] ?? $p['participant_parent_name'] ?? $p['participant_class_name'] ?? $p['manual_name'] ?? '-');
};
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0 text-dark"><?= esc($row['title'] ?? $title ?? 'Detail Layanan BK') ?></h4>
        <p class="text-dark mb-0"><?= esc($serviceType ?: '-') ?> &mdash; <?= esc($roleLabel ?? 'Pengguna') ?></p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a>
        <?php if ($canManage): ?>
          <a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-primary"><i class="mdi mdi-pencil me-1"></i> Edit</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php foreach (['success' => 'check-circle', 'error' => 'alert-circle'] as $type => $icon): ?>
  <?php if (session()->getFlashdata($type)): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
      <i class="mdi mdi-<?= $icon ?> me-2"></i><?= esc(session()->getFlashdata($type)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark">Informasi Layanan</h5>
        <div class="row g-3">
          <div class="col-md-6"><div class="text-dark fw-medium">Siswa</div><div class="text-dark"><?= esc($row['student_name'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-dark fw-medium">Kelas</div><div class="text-dark"><?= esc($row['class_name'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-dark fw-medium"><?= $serviceType === 'Konferensi Kasus' ? 'Penanggung Jawab' : 'Guru BK/Penanggung Jawab' ?></div><div class="text-dark"><?= esc($row['counselor_name'] ?? '') ?: ($serviceType === 'Konferensi Kasus' ? 'Belum ditetapkan' : '-') ?></div></div>
          <div class="col-md-6"><div class="text-dark fw-medium">Jadwal</div><div class="text-dark"><?= esc($row['scheduled_at'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-dark fw-medium">Tempat/Lokasi/Alamat</div><div class="text-dark"><?= nl2br(esc($row['location'] ?? '-')) ?></div></div>
          <div class="col-md-6"><div class="text-dark fw-medium">Status</div><span class="badge bg-light text-dark border"><?= esc($row['status'] ?? '-') ?></span></div>
          <?php if ($canManage): ?>
            <div class="col-md-6"><div class="text-dark fw-medium">Boleh dilihat Wali Kelas</div>
              <span class="badge bg-<?= ! empty($row['visible_to_homeroom']) ? 'success' : 'secondary' ?>"><?= ! empty($row['visible_to_homeroom']) ? 'Ya' : 'Tidak' ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark">Detail Khusus</h5>
        <?php
          $shown = false;
          if ($detail): ?>
          <dl class="row mb-0">
            <?php foreach ($detail as $key => $val): ?>
              <?php if (in_array($key, $hiddenDetailKeys, true) || $val === null || trim((string) $val) === '') continue; $shown = true; ?>
              <dt class="col-md-4 text-dark"><?= esc($detailLabels[$key] ?? ucwords(str_replace('_', ' ', (string) $key))) ?></dt>
              <dd class="col-md-8 text-dark"><?= nl2br(esc((string) $val)) ?></dd>
            <?php endforeach; ?>
          </dl>
        <?php endif; ?>
        <?php if (! $shown): ?>
          <p class="text-dark mb-0">Belum ada detail tambahan.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if (! in_array($roleKey ?? '', ['siswa', 'orang-tua'], true)): ?>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark">Catatan</h5>
        <?php if ($notes): ?>
          <?php foreach ($notes as $note): ?>
            <div class="border rounded p-3 mb-2">
              <div class="d-flex justify-content-between align-items-start">
                <strong class="text-dark"><?= esc($note['note_type'] ?? 'Catatan') ?></strong>
                <div class="text-end">
                  <small class="text-dark d-block"><?= esc($note['created_at'] ?? '') ?></small>
                  <?php if ($canManage && (int) ($note['created_by'] ?? 0) === $me): ?>
                    <form method="post" action="<?= site_url($routePrefix . '/note-delete/' . (int) $note['id']) ?>" class="d-inline" onsubmit="return confirm('Hapus catatan ini?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="record_id" value="<?= (int) $row['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger mt-1" title="Hapus catatan saya"><i class="mdi mdi-delete"></i></button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
              <p class="mb-1 text-dark"><?= nl2br(esc($note['note_content'] ?? '-')) ?></p>
              <small class="text-dark">Oleh <?= esc($note['author_name'] ?? '-') ?> &mdash; <?= esc($note['visibility_level'] ?? '-') ?></small>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-dark mb-0">Belum ada catatan yang dapat ditampilkan.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark"><?= $isAbsenteeMode ? 'Peserta Tidak Hadir' : 'Peserta/Undangan' ?></h5>
        <?php if ($canManage && $serviceType === 'Bimbingan'): ?>
          <div class="alert alert-info py-2 px-3 small text-dark">
            <i class="mdi mdi-information-outline me-1"></i>
            Untuk Bimbingan <strong>Klasikal</strong>/<strong>Kelas Besar</strong>: cukup catat siswa yang <strong>TIDAK hadir</strong>. Siswa yang tidak tercatat dianggap <strong>hadir</strong>. Untuk <strong>Kelompok</strong>, masukkan semua peserta lalu atur kehadirannya.
          </div>
        <?php endif; ?>
        <?php foreach ($participants as $participant): ?>
          <?php if (($participant['participant_type'] ?? '') === 'class') continue; ?>
          <div class="border-bottom pb-2 mb-2">
            <div class="fw-semibold text-dark"><?= esc($participantName($participant)) ?></div>
            <small class="text-dark"><?= esc($participant['role_in_session'] ?? '-') ?> &mdash; <?= esc($participant['attendance_status'] ?? '-') ?></small>
            <?php if ($canManage): ?>
              <div class="d-flex gap-1 mt-2">
                <form method="post" action="<?= site_url($routePrefix . '/participants/' . (int) $participant['id']) ?>" class="flex-grow-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="record_id" value="<?= esc((string) $row['id']) ?>">
                  <div class="input-group input-group-sm">
                    <select name="attendance_status" class="form-select">
                      <?php foreach (['Hadir','Izin','Sakit','Alpha','Belum Hadir'] as $status): ?>
                        <option value="<?= esc($status) ?>" <?= ($participant['attendance_status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary" title="Simpan kehadiran"><i class="mdi mdi-content-save"></i></button>
                  </div>
                </form>
                <form method="post" action="<?= site_url($routePrefix . '/participant-delete/' . (int) $participant['id']) ?>" onsubmit="return confirm('Hapus peserta ini?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="record_id" value="<?= esc((string) $row['id']) ?>">
                  <button class="btn btn-sm btn-outline-danger" title="Hapus peserta"><i class="mdi mdi-delete"></i></button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (! $participants): ?>
          <p class="text-dark mb-0">Belum ada peserta.</p>
        <?php endif; ?>

                <?php if ($canManage && $isAbsenteeMode && ! empty($row['target_class_id'])): ?>
          <div class="mt-3 pt-3 border-top js-multi" data-name="student_ids[]" id="absentStudentPickerContainer">
            <h6 class="text-dark mb-2">Catat Siswa Tidak Hadir</h6>
            
            <div class="js-chips border rounded p-2 mb-2 bg-light">
              <span class="text-dark js-chip-empty">Belum ada siswa dipilih.</span>
            </div>

            <form method="post" action="<?= site_url($routePrefix . '/participant-add/' . (int) $row['id']) ?>">
              <?= csrf_field() ?>
              <div class="mb-2">
                <select class="form-select select2-search js-picker" id="absentStudentPicker">
                  <option value="">Ketik untuk mencari siswa&hellip;</option>
                  <?php 
                    $targetClassNames = array_map('trim', explode(',', (string) ($row['class_name'] ?? '')));
                    foreach (($options['students'] ?? []) as $stu): 
                      if (! in_array(trim((string) ($stu['class_name'] ?? '')), $targetClassNames, true)) {
                          continue;
                      }
                  ?>
                    <option value="<?= (int) $stu['id'] ?>"><?= esc($stu['full_name']) ?> (<?= esc($stu['class_name']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <select name="attendance_status" class="form-select form-select-sm">
                  <?php foreach (['Alpha','Izin','Sakit','Hadir','Belum Hadir'] as $status): ?>
                    <option value="<?= esc($status) ?>"><?= esc($status) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn btn-sm btn-primary w-100"><i class="mdi mdi-plus me-1"></i>Tambah Ketidakhadiran</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($canManage): ?>
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark">Tambah Catatan</h5>
          <form method="post" action="<?= site_url($routePrefix . '/note/' . (int) $row['id']) ?>">
            <?= csrf_field() ?>
            <div class="mb-2">
              <select name="note_type" class="form-select">
                <?php foreach (['Observasi','Intervensi','Follow-up','Agenda','Lainnya'] as $type): ?>
                  <option value="<?= esc($type) ?>"><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <textarea name="note_content" class="form-control" rows="4" required placeholder="Tulis catatan..."></textarea>
            </div>
            <div class="form-check form-switch mb-3">
              <input type="hidden" name="visible_to_homeroom" value="0">
              <input class="form-check-input" type="checkbox" name="visible_to_homeroom" value="1" id="noteVisible">
              <label class="form-check-label text-dark" for="noteVisible">Boleh dilihat Wali Kelas terkait</label>
            </div>
            <button class="btn btn-primary w-100"><i class="mdi mdi-plus me-1"></i> Tambah Catatan</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(document).ready(function () {
    if ($.fn.select2) {
      $('#absentStudentPicker').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Ketik untuk mencari...'
      });
    }

    function pickerOptionByValue($picker, val) {
      return $picker.find('option').filter(function () { return this.value === String(val); });
    }

    function detachOption($chip, $picker, val) {
      var $opt = pickerOptionByValue($picker, val);
      if ($opt.length) {
        $chip.data('opt', $opt);
        $chip.data('optParent', $opt.parent());
        $opt.detach();
      }
    }

    function restoreOption($chip) {
      var $opt = $chip.data('opt');
      var $parent = $chip.data('optParent');
      if ($opt && $parent && $parent.length) { $parent.append($opt); }
    }

    function addAbsentChip($widget, pickerVal, text) {
      if (!pickerVal) { return null; }
      var name = $widget.data('name');
      var hiddenName = name;
      var hiddenVal = pickerVal;

      var $chips = $widget.find('.js-chips');
      var dup = false;
      $chips.find('input[type=hidden]').each(function () {
        if (this.name === hiddenName && this.value === String(hiddenVal)) { dup = true; }
      });
      if (dup) { return null; }

      var $chip = $('<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;"></span>');
      $('<span></span>').text(text).appendTo($chip);
      $('<input type="hidden">').attr('name', hiddenName).val(hiddenVal).appendTo($chip);
      $('<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>').appendTo($chip);
      $widget.find('.js-chip-empty').hide().before($chip);
      return $chip;
    }

    $('#absentStudentPicker').on('select2:select', function () {
      var $widget = $(this).closest('.js-multi');
      var $picker = $(this);
      var pickerVal = $picker.val();
      var text = $.trim($picker.find('option:selected').text());
      var $chip = addAbsentChip($widget, pickerVal, text);
      $picker.val('').trigger('change');
      if ($chip) { detachOption($chip, $picker, pickerVal); }
    });

    $('#absentStudentPickerContainer').on('click', '.js-chip-remove', function () {
      var $widget = $(this).closest('.js-multi');
      var $chip = $(this).closest('.js-chip');
      restoreOption($chip);
      $chip.remove();
      if ($widget.find('.js-chip').length === 0) {
        $widget.find('.js-chip-empty').show();
      }
    });
  });
</script>
<?= $this->endSection() ?>