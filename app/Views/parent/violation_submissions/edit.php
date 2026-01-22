<?php
// app/Views/parent/violation_submissions/edit.php
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helper form untuk old()
try { helper(['form']); } catch (\Throwable $e) {}

// Normalisasi data
$row        = is_array($row ?? null) ? $row : (array) ($row ?? []);
$students   = is_array($students ?? null) ? $students : [];
$categories = is_array($categories ?? null) ? $categories : [];
$errors     = is_array($errors ?? null) ? $errors : [];

$status = (string)($row['status'] ?? 'Diajukan');

// Badge status (konsisten)
$badge = 'secondary';
if ($status === 'Diajukan')   $badge = 'warning';
if ($status === 'Ditinjau')   $badge = 'info';
if ($status === 'Ditolak')    $badge = 'danger';
if ($status === 'Diterima')   $badge = 'success';
if ($status === 'Dikonversi') $badge = 'primary';

// Editable rules
$isEditable = !in_array($status, ['Ditolak','Diterima','Dikonversi'], true);
$disabledAttr = $isEditable ? '' : 'disabled';

// Evidence normalizer
$evidenceRaw = $row['evidence_json'] ?? [];
if (is_string($evidenceRaw)) {
    $evidenceRaw = trim($evidenceRaw);
    $decoded = $evidenceRaw !== '' ? json_decode($evidenceRaw, true) : [];
    $evidence = is_array($decoded) ? $decoded : [];
} elseif (is_array($evidenceRaw)) {
    $evidence = $evidenceRaw;
} else {
    $evidence = [];
}

// Helper ambil value: old() dulu, fallback ke $row
$val = function (string $key, $default = '') use ($row) {
    $fallback = $row[$key] ?? $default;
    return old($key, $fallback);
};

// Untuk selected di dropdown
$sel = function ($current, $option): string {
    return ((string)$current === (string)$option) ? 'selected' : '';
};
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mb-3">
  <h4 class="mb-0"><?= esc($title ?? 'Edit Pengaduan') ?></h4>

  <nav aria-label="breadcrumb" class="mb-0 ms-md-auto">
    <ol class="breadcrumb mb-0 justify-content-md-end">
      <li class="breadcrumb-item">
        <a href="<?= base_url('parent/dashboard') ?>">Dashboard</a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?= base_url('parent/violation-submissions') ?>">Pengaduan</a>
      </li>
      <li class="breadcrumb-item active" aria-current="page">Edit Pengaduan</li>
    </ol>
  </nav>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Periksa kembali input:</div>
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= esc($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="mb alert-<?= esc($badge) ?>">
  Status saat ini: <span class="badge bg-<?= esc($badge) ?>"><?= esc($status) ?></span>
  <?php if (!$isEditable): ?>
    <div class="small mt-1">
      Pengaduan ini <b>terkunci</b> karena status sudah <b><?= esc($status) ?></b>.
      Kamu tetap bisa melihat data, tetapi tidak bisa menyimpan perubahan.
    </div>
  <?php else: ?>
    <div class="small text-muted mt-1">
      Kamu masih bisa mengubah pengaduan selama status belum Ditolak/Diterima/Dikonversi.
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body">
    <form action="<?= base_url('parent/violation-submissions/update/' . ($row['id'] ?? 0)) ?>"
          method="post"
          enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Terlapor (Siswa terdaftar)</label>

          <?php $curStudent = $val('subject_student_id', $row['subject_student_id'] ?? ''); ?>

          <!-- Hidden mirror (penting: saat select disabled, hidden tetap terkirim) -->
          <input type="hidden"
                 name="subject_student_id"
                 id="subject_student_id_hidden"
                 value="<?= esc((string)$curStudent) ?>"
                 <?= $disabledAttr ?>>

          <select name="subject_student_id"
                  class="form-select"
                  id="subject_student_id"
                  <?= $disabledAttr ?>>
            <option value="">- Pilih jika terlapor siswa -</option>
            <?php foreach ($students as $s): ?>
              <?php
                $sid   = $s['id'] ?? '';
                $label = ($s['full_name'] ?? '-') . ' • ' . ($s['class_name'] ?? '-') . ' • NIS ' . ($s['nis'] ?? '-');
              ?>
              <option value="<?= esc($sid) ?>" <?= $sel($curStudent, $sid) ?>>
                <?= esc($label) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <small class="text-muted">Jika bukan siswa terdaftar, isi kolom “Nama terlapor (lainnya)”.</small>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Nama terlapor (lainnya)</label>

          <?php $curOther = (string)$val('subject_other_name', ''); ?>

          <!-- Hidden mirror (penting: saat input disabled, hidden tetap terkirim) -->
          <input type="hidden"
                 name="subject_other_name"
                 id="subject_other_name_hidden"
                 value="<?= esc($curOther) ?>"
                 <?= $disabledAttr ?>>

          <input type="text"
                 name="subject_other_name"
                 id="subject_other_name"
                 class="form-control"
                 value="<?= esc($curOther) ?>"
                 placeholder="Contoh: Orang luar sekolah / dll"
                 autocomplete="off"
                 <?= $disabledAttr ?>>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Kategori Pelanggaran (opsional)</label>
          <?php $curCat = $val('category_id', $row['category_id'] ?? ''); ?>
          <select name="category_id" class="form-select" <?= $disabledAttr ?>>
            <option value="">- Pilih kategori -</option>
            <?php foreach ($categories as $c): ?>
              <?php $cid = $c['id'] ?? ''; ?>
              <option value="<?= esc($cid) ?>" <?= $sel($curCat, $cid) ?>>
                <?= esc($c['category_name'] ?? '-') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 mb-3">
          <label class="form-label">Tanggal Kejadian</label>
          <input type="date"
                 name="occurred_date"
                 class="form-control"
                 value="<?= esc($val('occurred_date', '')) ?>"
                 <?= $disabledAttr ?>>
        </div>

        <div class="col-md-3 mb-3">
          <label class="form-label">Waktu Kejadian</label>
          <input type="time"
                 name="occurred_time"
                 class="form-control"
                 value="<?= esc($val('occurred_time', '')) ?>"
                 <?= $disabledAttr ?>>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Lokasi</label>
          <input type="text"
                 name="location"
                 class="form-control"
                 value="<?= esc($val('location', '')) ?>"
                 <?= $disabledAttr ?>>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Saksi</label>
          <input type="text"
                 name="witness"
                 class="form-control"
                 value="<?= esc($val('witness', '')) ?>"
                 <?= $disabledAttr ?>>
        </div>

        <div class="col-12 mb-3">
          <label class="form-label">Kronologi / Deskripsi Kejadian <span class="text-danger">*</span></label>
          <textarea name="description"
                    class="form-control"
                    rows="5"
                    <?= $disabledAttr ?>><?= esc($val('description', '')) ?></textarea>
        </div>

        <?php if (!empty($evidence)): ?>
          <div class="col-12 mb-3">
            <label class="form-label">Bukti Saat Ini</label>
            <div class="row g-2">
              <?php foreach ($evidence as $p): ?>
                <?php
                  $p = (string)$p;
                  $displayName = basename($p) ?: $p;
                  $id = 'rm_' . md5($p);
                ?>
                <div class="col-md-6">
                  <div class="border rounded p-2 d-flex align-items-center justify-content-between gap-2">
                    <a href="<?= base_url($p) ?>" target="_blank" class="text-decoration-none text-truncate" style="max-width:70%;">
                      <?= esc($displayName) ?>
                    </a>

                    <div class="form-check ms-2">
                      <input class="form-check-input"
                             type="checkbox"
                             name="remove_evidence[]"
                             value="<?= esc($p) ?>"
                             id="<?= esc($id) ?>"
                             <?= $disabledAttr ?>>
                      <label class="form-check-label small" for="<?= esc($id) ?>">hapus</label>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <small class="text-muted">
              Mencentang “hapus” akan melepas lampiran dari pengaduan.
            </small>
          </div>
        <?php endif; ?>

        <div class="col-12 mb-3">
          <label class="form-label">Tambah Bukti Baru (opsional)</label>
          <input type="file"
                 name="evidence_files[]"
                 class="form-control"
                 multiple
                 accept=".jpg,.jpeg,.png,.pdf"
                 <?= $disabledAttr ?>>
          <small class="text-muted">Format: JPG/JPEG/PNG/PDF. Maks 3MB per file.</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a href="<?= base_url('parent/violation-submissions/show/' . ($row['id'] ?? 0)) ?>" class="btn btn-light">Batal</a>

        <?php if ($isEditable): ?>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <?php else: ?>
          <span class="text-muted align-self-center small">Tidak bisa menyimpan karena pengaduan terkunci.</span>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if ($isEditable): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const sel      = document.getElementById('subject_student_id');
  const other    = document.getElementById('subject_other_name');
  const hidSel   = document.getElementById('subject_student_id_hidden');
  const hidOther = document.getElementById('subject_other_name_hidden');

  if (!sel || !other || !hidSel || !hidOther) return;

  const t = (v) => (v ?? '').toString().trim();

  function applyLock() {
    const hasStudent = t(sel.value) !== '';
    const hasOther   = t(other.value) !== '';

    // Jika student dipilih: other dikosongkan & dikunci
    if (hasStudent) {
      other.value = '';
      other.setAttribute('disabled', 'disabled');
      hidOther.value = '';

      sel.removeAttribute('disabled');
      hidSel.value = t(sel.value);
      return;
    }

    // Jika other diisi: student dikosongkan & dikunci
    if (hasOther) {
      sel.value = '';
      sel.setAttribute('disabled', 'disabled');
      hidSel.value = '';

      other.removeAttribute('disabled');
      hidOther.value = t(other.value);
      return;
    }

    // Jika keduanya kosong: buka keduanya
    sel.removeAttribute('disabled');
    other.removeAttribute('disabled');
    hidSel.value = '';
    hidOther.value = '';
  }

  // Penting untuk halaman edit: lock awal mengikuti nilai yang sudah ada
  applyLock();

  sel.addEventListener('change', applyLock);
  other.addEventListener('input', applyLock);
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>
