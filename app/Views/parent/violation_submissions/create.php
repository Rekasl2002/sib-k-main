<?php
// app/Views/parent/violation_submissions/create.php
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
try { helper(['form']); } catch (\Throwable $e) {}

$students   = is_array($students ?? null) ? $students : [];
$categories = is_array($categories ?? null) ? $categories : [];
$errors     = is_array($errors ?? null) ? $errors : [];
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h4 class="mb-0"><?= esc($title ?? 'Buat Pengaduan') ?></h4>

  <nav aria-label="breadcrumb" class="m-0">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('parent/violation-submissions') ?>">Pengaduan</a></li>
      <li class="breadcrumb-item active" aria-current="page">Tambah Pengaduan</li>
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

<div class="mb alert-warning mb-3">
  <div class="fw-semibold">Catatan</div>
  <div class="small">
    Pilih <b>salah satu</b>: terlapor siswa terdaftar <i>atau</i> isi “Nama terlapor (lainnya)”.
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form action="<?= base_url('parent/violation-submissions/store') ?>" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Terlapor (Siswa terdaftar)</label>
          <select name="subject_student_id" class="form-select" id="subject_student_id">
            <option value="">- Pilih jika terlapor siswa -</option>
            <?php foreach ($students as $s): ?>
              <?php
                $sid   = $s['id'] ?? '';
                $label = ($s['full_name'] ?? '-') . ' • ' . ($s['class_name'] ?? '-') . ' • NIS ' . ($s['nis'] ?? '-');
              ?>
              <option value="<?= esc($sid) ?>" <?= old('subject_student_id') == $sid ? 'selected' : '' ?>>
                <?= esc($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Jika bukan siswa terdaftar, isi kolom “Nama terlapor (lainnya)”.</small>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Nama terlapor (lainnya)</label>
          <input type="text"
                 name="subject_other_name"
                 id="subject_other_name"
                 class="form-control"
                 value="<?= esc(old('subject_other_name')) ?>"
                 placeholder="Contoh: Orang luar sekolah / dll">
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Kategori Pelanggaran (opsional)</label>
          <select name="category_id" class="form-select">
            <option value="">- Pilih kategori -</option>
            <?php foreach ($categories as $c): ?>
              <?php $cid = $c['id'] ?? ''; ?>
              <option value="<?= esc($cid) ?>" <?= old('category_id') == $cid ? 'selected' : '' ?>>
                <?= esc($c['category_name'] ?? '-') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 mb-3">
          <label class="form-label">Tanggal Kejadian</label>
          <input type="date" name="occurred_date" class="form-control" value="<?= esc(old('occurred_date')) ?>">
        </div>

        <div class="col-md-3 mb-3">
          <label class="form-label">Waktu Kejadian</label>
          <input type="time" name="occurred_time" class="form-control" value="<?= esc(old('occurred_time')) ?>">
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Lokasi</label>
          <input type="text" name="location" class="form-control" value="<?= esc(old('location')) ?>">
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Saksi</label>
          <input type="text" name="witness" class="form-control" value="<?= esc(old('witness')) ?>">
        </div>

        <div class="col-12 mb-3">
          <label class="form-label">Kronologi / Deskripsi Kejadian <span class="text-danger">*</span></label>
          <textarea name="description" class="form-control" rows="5"><?= esc(old('description')) ?></textarea>
        </div>

        <div class="col-12 mb-3">
          <label class="form-label">Bukti (opsional)</label>
          <input type="file" name="evidence_files[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
          <small class="text-muted">Format: JPG/JPEG/PNG/PDF. Maks 3MB per file.</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a href="<?= base_url('parent/violation-submissions') ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Kirim Pengaduan</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const sel = document.getElementById('subject_student_id');
  const other = document.getElementById('subject_other_name');
  if (!sel || !other) return;

  const lock = () => {
    const hasSel = (sel.value || '').trim() !== '';
    const hasOther = (other.value || '').trim() !== '';

    if (hasSel) {
      other.setAttribute('readonly', 'readonly');
      other.classList.add('bg-light');
      if (hasOther) other.value = '';
    } else {
      other.removeAttribute('readonly');
      other.classList.remove('bg-light');
    }

    if (hasOther) {
      sel.value = '';
      sel.setAttribute('disabled', 'disabled');
      sel.classList.add('bg-light');
    } else {
      sel.removeAttribute('disabled');
      sel.classList.remove('bg-light');
    }
  };

  sel.addEventListener('change', lock);
  other.addEventListener('input', lock);
  lock();
});
</script>

<?= $this->endSection() ?>
