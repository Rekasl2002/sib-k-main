<?php
/**
 * View form Penugasan (Koordinator BK).
 * Perbaikan Kedua (Item #10):
 *  - Guru BK yang Ditugaskan, Kelas, dan Siswa memakai POLA CHIP ala form Bimbingan
 *    (bisa dicari; klik = langsung jadi chip; opsi terpilih disembunyikan; hapus via ×).
 *  - Kelas & Siswa boleh lebih dari satu; Guru BK petugas juga boleh lebih dari satu.
 *  - Jenis Tugas diperluas + opsi "Lainnya" (memunculkan isian bebas).
 *  - Field WAJIB (*): Guru BK, Jenis Tugas, Judul, Instruksi, Batas Waktu, Prioritas.
 *  - Saat Buat: Status hanya "Ditugaskan" (bawaan) / "Draft"; saat Edit: seluruh status.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$options = is_array($options ?? null) ? $options : [];
$routePrefix = (string) ($routePrefix ?? '');
$action = (string) ($action ?? current_url());
$isEdit = ! empty($row['id']);
$value = static fn(string $key, $default = '') => old($key, $row[$key] ?? $default);

$types = \App\Services\BkAssignmentService::assignmentTypes();
$statuses = $isEdit ? \App\Services\BkAssignmentService::statuses() : \App\Services\BkAssignmentService::createStatuses();

$selectedType = (string) $value('assignment_type', 'Tugas Layanan');

// Prefill chip dari pivot (edit). Bentuk: [['id'=>.., 'name'=>.., 'class_name'=>..], ..]
$preAssignees = is_array($row['assignees'] ?? null) ? $row['assignees'] : [];
$preClasses   = is_array($row['target_classes'] ?? null) ? $row['target_classes'] : [];
$preStudents  = is_array($row['target_students'] ?? null) ? $row['target_students'] : [];

$renderChip = static function (string $name, $val, string $text): string {
    return '<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;">'
        . '<span>' . esc($text) . '</span>'
        . '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc((string) $val, 'attr') . '">'
        . '<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>'
        . '</span>';
};
?>

<div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between"><h4 class="mb-sm-0 text-dark"><?= esc($title ?? 'Penugasan') ?></h4><div class="d-flex align-items-center flex-wrap gap-3 page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li><li class="breadcrumb-item"><a href="<?= site_url($routePrefix) ?>">Penugasan</a></li><li class="breadcrumb-item active"><?= ! empty($row['id']) ? 'Edit' : 'Tambah' ?></li></ol><a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a></div></div></div></div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?= esc($action, 'attr') ?>">
  <?= csrf_field() ?>
  <div class="card"><div class="card-body">

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label text-dark">Jenis Tugas <span class="text-danger">*</span></label>
        <select name="assignment_type" id="assignmentType" class="form-select" required>
          <?php foreach ($types as $type): ?>
            <option value="<?= esc($type) ?>" <?= $selectedType === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="mt-2" id="assignmentTypeOtherWrap" style="<?= $selectedType === 'Lainnya' ? '' : 'display:none;' ?>">
          <input type="text" name="assignment_type_other" class="form-control" placeholder="Tulis jenis tugas lainnya" value="<?= esc($value('assignment_type_other')) ?>">
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label text-dark">Prioritas <span class="text-danger">*</span></label>
        <select name="priority" class="form-select" required>
          <?php foreach (['Rendah','Sedang','Tinggi','Mendesak'] as $priority): ?>
            <option value="<?= esc($priority) ?>" <?= $value('priority','Sedang') === $priority ? 'selected' : '' ?>><?= esc($priority) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Guru BK yang Ditugaskan: chip multi -->
    <div class="mb-3 js-multi" data-name="assigned_to_user_ids[]">
      <label class="form-label text-dark">Guru BK yang Ditugaskan <span class="text-danger">*</span> <span class="text-dark fw-normal">(boleh lebih dari satu)</span></label>
      <div class="js-chips border rounded p-2 mb-2 bg-light">
        <?php foreach ($preAssignees as $a): ?>
          <?= $renderChip('assigned_to_user_ids[]', (int) ($a['user_id'] ?? 0), (string) ($a['name'] ?? ('Guru BK #' . ($a['user_id'] ?? 0)))) ?>
        <?php endforeach; ?>
        <span class="text-dark js-chip-empty"<?= $preAssignees ? ' style="display:none;"' : '' ?>>Belum ada Guru BK dipilih.</span>
      </div>
      <select class="form-select select2-search js-picker">
        <option value="">Ketik untuk mencari Guru BK&hellip;</option>
        <?php foreach (($options['counselors'] ?? []) as $user): ?>
          <option value="<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
        <?php endforeach; ?>
      </select>
      <small class="text-dark d-block mt-1">Klik nama Guru BK pada daftar &mdash; otomatis masuk ke kotak di atas. Bisa lebih dari satu; nama yang sudah dipilih tidak muncul lagi.</small>
    </div>

    <div class="mb-3">
      <label class="form-label text-dark">Judul/Topik/Masalah <span class="text-danger">*</span></label>
      <input type="text" name="title" class="form-control" required value="<?= esc($value('title')) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label text-dark">Instruksi <span class="text-danger">*</span></label>
      <textarea name="instruction" class="form-control" rows="5" required><?= esc($value('instruction')) ?></textarea>
    </div>

    <div class="row">
      <!-- Kelas: chip multi -->
      <div class="col-md-6 mb-3 js-multi" data-name="target_class_ids[]">
        <label class="form-label text-dark">Kelas <span class="text-dark fw-normal">(opsional, boleh lebih dari satu)</span></label>
        <div class="js-chips border rounded p-2 mb-2 bg-light">
          <?php foreach ($preClasses as $c): ?>
            <?= $renderChip('target_class_ids[]', (int) ($c['class_id'] ?? 0), (string) ($c['name'] ?? ('Kelas #' . ($c['class_id'] ?? 0)))) ?>
          <?php endforeach; ?>
          <span class="text-dark js-chip-empty"<?= $preClasses ? ' style="display:none;"' : '' ?>>Belum ada kelas dipilih.</span>
        </div>
        <select class="form-select select2-search js-picker">
          <option value="">Ketik untuk mencari kelas&hellip;</option>
          <?php foreach (($options['classes'] ?? []) as $class): ?>
            <option value="<?= esc((string) $class['id']) ?>"><?= esc($class['class_name'] ?? '-') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Siswa: chip multi -->
      <div class="col-md-6 mb-3 js-multi" data-name="target_student_ids[]">
        <label class="form-label text-dark">Siswa <span class="text-dark fw-normal">(opsional, boleh lebih dari satu)</span></label>
        <div class="js-chips border rounded p-2 mb-2 bg-light">
          <?php foreach ($preStudents as $s): ?>
            <?= $renderChip('target_student_ids[]', (int) ($s['student_id'] ?? 0), trim((string) ($s['name'] ?? ('Siswa #' . ($s['student_id'] ?? 0))) . ' - ' . (string) ($s['class_name'] ?? ''), ' -')) ?>
          <?php endforeach; ?>
          <span class="text-dark js-chip-empty"<?= $preStudents ? ' style="display:none;"' : '' ?>>Belum ada siswa dipilih.</span>
        </div>
        <select class="form-select select2-search js-picker">
          <option value="">Ketik untuk mencari siswa&hellip;</option>
          <?php foreach (($options['students'] ?? []) as $student): ?>
            <option value="<?= esc((string) $student['id']) ?>"><?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label text-dark">Batas Waktu <span class="text-danger">*</span></label>
        <input type="datetime-local" name="due_at" class="form-control" required value="<?= esc(str_replace(' ', 'T', substr((string) $value('due_at'), 0, 16))) ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label text-dark">Status</label>
        <select name="status" class="form-select">
          <?php foreach ($statuses as $status): ?>
            <option value="<?= esc($status) ?>" <?= $value('status','Ditugaskan') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (! $isEdit): ?><small class="text-dark d-block mt-1">Bawaan "Ditugaskan". Pilih "Draft" bila belum ingin dikirim. Status lanjutan diatur dari halaman detail.</small><?php endif; ?>
      </div>
    </div>

    <button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
  </div></div>
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

    // Jenis "Lainnya" -> munculkan isian bebas.
    function toggleOther() {
      $('#assignmentTypeOtherWrap').toggle($('#assignmentType').val() === 'Lainnya');
    }
    $('#assignmentType').on('change', toggleOther);
    toggleOther();

    // ---- Pemilihan banyak data: klik pilihan -> langsung jadi chip. ----
    function pickerOptionByValue($picker, val) {
      return $picker.find('option').filter(function () { return this.value === String(val); });
    }
    function detachOption($chip, $picker, val) {
      var $opt = pickerOptionByValue($picker, val);
      if ($opt.length) { $chip.data('opt', $opt); $chip.data('optParent', $opt.parent()); $opt.detach(); }
    }
    function restoreOption($chip) {
      var $opt = $chip.data('opt'); var $parent = $chip.data('optParent');
      if ($opt && $parent && $parent.length) { $parent.append($opt); }
    }
    function addChip($widget, val, text) {
      if (!val) { return null; }
      var name = $widget.data('name');
      var $chips = $widget.find('.js-chips'); var dup = false;
      $chips.find('input[type=hidden]').each(function () { if (this.name === name && this.value === String(val)) { dup = true; } });
      if (dup) { return null; }
      var $chip = $('<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;"></span>');
      $('<span></span>').text(text).appendTo($chip);
      $('<input type="hidden">').attr('name', name).val(val).appendTo($chip);
      $('<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>').appendTo($chip);
      $widget.find('.js-chip-empty').hide().before($chip);
      return $chip;
    }

    // Sembunyikan opsi yang chip-nya sudah tampil (prefill edit).
    $('.js-multi').each(function () {
      var $widget = $(this); var $picker = $widget.find('.js-picker');
      $widget.find('.js-chip').each(function () { detachOption($(this), $picker, $(this).find('input[type=hidden]').val()); });
    });

    $('.js-multi .js-picker').on('select2:select', function () {
      var $widget = $(this).closest('.js-multi'); var $picker = $(this);
      var val = $picker.val(); var text = $.trim($picker.find('option:selected').text());
      var $chip = addChip($widget, val, text);
      $picker.val('').trigger('change');
      if ($chip) { detachOption($chip, $picker, val); }
    });

    $('.js-multi').on('click', '.js-chip-remove', function () {
      var $widget = $(this).closest('.js-multi'); var $chip = $(this).closest('.js-chip');
      restoreOption($chip); $chip.remove();
      if ($widget.find('.js-chip').length === 0) { $widget.find('.js-chip-empty').show(); }
    });

    // Pastikan minimal satu Guru BK dipilih sebelum submit.
    $('form').on('submit', function (e) {
      var $bk = $('.js-multi[data-name="assigned_to_user_ids[]"]');
      if ($bk.length && $bk.find('input[type=hidden]').length === 0) {
        e.preventDefault();
        alert('Pilih minimal satu Guru BK yang ditugaskan.');
      }
    });
  });
</script>
<?= $this->endSection() ?>
