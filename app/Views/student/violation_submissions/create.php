<?php
// app/Views/parent/violation_submissions/create.php
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
try { helper(['form']); } catch (\Throwable $e) {}

$students   = (isset($students) && is_array($students)) ? $students : [];
$categories = (isset($categories) && is_array($categories)) ? $categories : [];
$errors     = (isset($errors) && is_array($errors)) ? $errors : [];
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mb-3">
  <h4 class="mb-3"><?= esc($title ?? 'Tambah Pengaduan') ?></h4>

  <nav aria-label="breadcrumb" class="mb-0 ms-md-auto">
    <ol class="breadcrumb mb-0 justify-content-md-end">
      <li class="breadcrumb-item">
        <a href="<?= base_url('parent/dashboard') ?>">Dashboard</a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?= base_url('parent/violation-submissions') ?>">Pengaduan</a>
      </li>
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
              <?php $sid = $s['id'] ?? ''; ?>
              <option value="<?= esc($sid) ?>" <?= (string)old('subject_student_id') === (string)$sid ? 'selected' : '' ?>>
                <?= esc(($s['full_name'] ?? '-') . ' • ' . ($s['class_name'] ?? '-') . ' • NIS ' . ($s['nis'] ?? '-')) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Jika terlapor bukan siswa terdaftar, isi kolom “Nama terlapor (lainnya)”.</small>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Nama terlapor (lainnya)</label>
          <input type="text"
                 name="subject_other_name"
                 class="form-control"
                 id="subject_other_name"
                 value="<?= esc(old('subject_other_name')) ?>"
                 placeholder="Contoh: Orang luar sekolah / Orang tak dikenal / dll">
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Kategori Pelanggaran (opsional)</label>
          <select name="category_id" class="form-select">
            <option value="">- Pilih kategori -</option>
            <?php foreach ($categories as $c): ?>
              <?php $cid = $c['id'] ?? ''; ?>
              <option value="<?= esc($cid) ?>" <?= (string)old('category_id') === (string)$cid ? 'selected' : '' ?>>
                <?= esc($c['category_name'] ?? '-') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 mb-3">
          <label class="form-label">Tanggal Kejadian (opsional)</label>
          <input type="date" name="occurred_date" class="form-control" value="<?= esc(old('occurred_date')) ?>">
        </div>

        <div class="col-md-3 mb-3">
          <label class="form-label">Waktu Kejadian (opsional)</label>
          <input type="time" name="occurred_time" class="form-control" value="<?= esc(old('occurred_time')) ?>">
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Lokasi (opsional)</label>
          <input type="text"
                 name="location"
                 class="form-control"
                 value="<?= esc(old('location')) ?>"
                 placeholder="Contoh: Kelas X-IPA-1 / Lapangan / Kantin">
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Saksi (opsional)</label>
          <input type="text"
                 name="witness"
                 class="form-control"
                 value="<?= esc(old('witness')) ?>"
                 placeholder="Nama saksi jika ada">
        </div>

        <div class="col-12 mb-3">
          <label class="form-label">Kronologi / Deskripsi Kejadian <span class="text-danger">*</span></label>
          <textarea name="description"
                    class="form-control"
                    rows="5"
                    placeholder="Tulis kronologi secara jelas..."
                    required><?= esc(old('description')) ?></textarea>
        </div>

        <div class="col-12 mb-3">
          <label class="form-label">Bukti (opsional)</label>
          <input type="file" name="evidence_files[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
          <small class="text-muted">Boleh lebih dari 1 file. Format: JPG/JPEG/PNG/PDF. Maks 3MB per file.</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a href="<?= base_url('parent/violation-submissions') ?>" class="btn btn-light">Kembali</a>
        <button type="submit" class="btn btn-primary">Kirim Pengaduan</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const sel = document.getElementById('subject_student_id');
  const other = document.getElementById('subject_other_name');

  function sync(){
    const hasSel = sel && sel.value && sel.value !== '';
    const hasOther = other && other.value.trim() !== '';

    if (hasSel) {
      other.disabled = true;
    } else {
      other.disabled = false;
    }

    if (hasOther) {
      sel.disabled = true;
    } else {
      sel.disabled = false;
    }
  }

  if (sel) sel.addEventListener('change', sync);
  if (other) other.addEventListener('input', sync);
  sync();
})();
</script>

<?= $this->endSection() ?>
