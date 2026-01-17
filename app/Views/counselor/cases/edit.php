<?php
/**
 * File Path: app/Views/counselor/cases/edit.php
 *
 * Edit Case View
 * Form untuk mengubah data pelanggaran (tanggal/waktu, lokasi, deskripsi, saksi, status, catatan resolusi)
 *
 * Catatan:
 * - POST ke counselor/cases/update/{id}
 * - Kategori & siswa readonly
 * - Mendukung hapus sebagian evidence & upload evidence baru (multi)
 */

$this->extend('layouts/main');
$this->section('content');

// Helper kecil utk aman akses nilai array
if (!function_exists('v')) {
    function v(array $a, string $k, $d='') { return esc($a[$k] ?? $d); }
}

$statusVal   = $violation['status'] ?? '-';
$severityVal = $violation['severity_level'] ?? '-';

// Kelas badge status & tingkat
$statusBadge = match ($statusVal) {
    'Dilaporkan'    => 'bg-info',
    'Dalam Proses'  => 'bg-warning',
    'Selesai'       => 'bg-success',
    'Dibatalkan'    => 'bg-secondary',
    default         => 'bg-secondary'
};

$severityBadge = match ($severityVal) {
    'Ringan' => 'bg-info',
    'Sedang' => 'bg-warning',
    'Berat'  => 'bg-danger',
    default  => 'bg-secondary'
};
?>

<!-- Page Title -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Edit Kasus & Pelanggaran</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('counselor/cases') ?>">Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item active">Edit Kasus & Pelanggaran</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php helper('app'); ?>
<?= show_alerts() ?>

<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i>
    <strong>Terdapat kesalahan pada input:</strong>
    <ul class="mb-0 mt-2">
      <?php foreach (session()->getFlashdata('errors') as $error): ?>
        <li><?= esc($error) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8">
    <form
      action="<?= base_url('counselor/cases/update/' . (int)($violation['id'] ?? 0)) ?>"
      method="post"
      id="editCaseForm"
      enctype="multipart/form-data"
    >
      <?= csrf_field() ?>

      <!-- Informasi Utama -->
      <div class="card">
        <div class="card-header bg-danger">
          <div class="d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0 text-white">
              <i class="mdi mdi-alert-circle-outline me-2"></i>Informasi Pelanggaran
            </h4>
            <span class="badge <?= $statusBadge ?> fs-6"><?= esc($statusVal) ?></span>
          </div>
        </div>
        <div class="card-body">

          <!-- Siswa & Kategori (readonly) -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Siswa</label>
              <div class="form-control bg-light">
                <strong><?= v($violation, 'student_name', '-') ?></strong>
                <div class="small text-muted">
                  NISN: <?= esc($violation['nisn'] ?? '-') ?>
                  <?php if (!empty($violation['class_name'])): ?>
                      | Kelas: <?= esc($violation['class_name']) ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori</label>
              <div class="form-control bg-light">
                <strong><?= v($violation, 'category_name', '-') ?></strong>
                <div class="mt-1">
                  <span class="badge <?= $severityBadge ?> me-2"><?= esc($severityVal) ?></span>
                  <span class="text-danger fw-semibold">
                    -<?= (int)($violation['point_deduction'] ?? 0) ?> Poin
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Waktu & Lokasi -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Tanggal Kejadian <span class="text-danger">*</span></label>
              <input type="date" name="violation_date" class="form-control"
                     value="<?= esc(date('Y-m-d', strtotime($violation['violation_date'] ?? date('Y-m-d')))) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Waktu Kejadian</label>
              <input type="time" name="violation_time" class="form-control"
                     value="<?= esc(!empty($violation['violation_time']) ? date('H:i', strtotime($violation['violation_time'])) : '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Lokasi</label>
              <input type="text" name="location" class="form-control"
                     placeholder="Contoh: Lapangan, Kantin, Kelas X-2"
                     value="<?= v($violation, 'location') ?>">
            </div>
          </div>

          <!-- Deskripsi & Saksi -->
          <div class="mb-3">
            <label class="form-label">Deskripsi Pelanggaran <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="4" minlength="10" required
                      placeholder="Tuliskan detail kejadian secara ringkas dan jelas..."><?= v($violation, 'description') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Saksi</label>
            <input type="text" name="witness" class="form-control" placeholder="Nama saksi (opsional)"
                   value="<?= v($violation, 'witness') ?>">
          </div>

          <!-- Lampiran yang sudah ada -->
          <?php if (!empty($violation['evidence_files']) && is_array($violation['evidence_files'])): ?>
            <div class="mb-3">
              <label class="form-label">Lampiran Saat Ini</label>
              <ul class="list-unstyled mb-0">
                <?php foreach ($violation['evidence_files'] as $f):
                    $rel = ltrim(preg_replace('#/+#','/',$f), '/'); ?>
                  <li class="mb-1 d-flex align-items-center">
                    <a href="<?= base_url($rel) ?>" target="_blank" class="me-2">
                      <i class="mdi mdi-file"></i> <?= esc(basename($rel)) ?>
                    </a>
                    <label class="ms-2 small">
                      <input type="checkbox" name="remove_evidence[]" value="<?= esc($rel) ?>">
                      Hapus
                    </label>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- Upload evidence baru -->
          <div class="mb-3">
            <label class="form-label">Barang Bukti (optional)</label>
            <input
              type="file"
              name="evidence[]"
              class="form-control"
              multiple
              accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp4"
            >
            <small class="text-muted">Anda dapat mengunggah beberapa file (jpg, png, pdf, doc, docx, mp4). Maks 5MB per file.</small>
          </div>

          <!-- Status & Resolusi -->
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select" id="statusSelect">
                <?php
                  $statuses = ['Dilaporkan','Dalam Proses','Selesai','Dibatalkan'];
                  foreach ($statuses as $st):
                ?>
                  <option value="<?= $st ?>" <?= $statusVal === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12" id="resolutionNotesWrap">
              <label class="form-label">Catatan Penyelesaian</label>
              <textarea name="resolution_notes" class="form-control" rows="3"
                        placeholder="Tuliskan catatan penyelesaian jika status Selesai/Dibatalkan..."><?= v($violation, 'resolution_notes') ?></textarea>
              <div class="form-text">Opsional, tetapi disarankan saat menandai kasus sebagai <em>Selesai</em> atau <em>Dibatalkan</em>.</div>
            </div>
          </div>

        </div>
      </div>

      <!-- Actions -->
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="mdi mdi-content-save me-1"></i>Simpan Perubahan
        </button>
        <a href="<?= base_url('counselor/cases/detail/' . (int)($violation['id'] ?? 0)) ?>" class="btn btn-secondary">
          <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Detail
        </a>
        <?php if (function_exists('is_koordinator') && is_koordinator()): ?>
          <button type="button" class="btn btn-outline-danger" onclick="deleteViolation(<?= (int)($violation['id'] ?? 0) ?>)">
            <i class="mdi mdi-delete-outline me-1"></i>Hapus
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Sidebar ringkas -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0"><i class="mdi mdi-information-outline me-2"></i>Ringkasan</h5></div>
      <div class="card-body">
        <div class="mb-2">
          <small class="text-muted d-block">Dibuat</small>
          <div><?= esc(!empty($violation['created_at']) ? date('d M Y H:i', strtotime($violation['created_at'])) : '-') ?></div>
        </div>
        <?php if (!empty($violation['updated_at'])): ?>
        <div>
          <small class="text-muted d-block">Diperbarui</small>
          <div><?= esc(date('d M Y H:i', strtotime($violation['updated_at']))) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
  // Tampilkan/sembunyikan catatan resolusi berdasarkan status (hook siap pakai)
  (function(){
    const select = document.getElementById('statusSelect');
    const wrap   = document.getElementById('resolutionNotesWrap');
    function toggleNotes(){
      const v = select.value;
      // Saat ini selalu tampil; jika perlu, sesuaikan:
      // wrap.style.display = (v === 'Selesai' || v === 'Dibatalkan') ? 'block' : 'none';
      wrap.style.display = 'block';
    }
    if (select) {
      select.addEventListener('change', toggleNotes);
      toggleNotes();
    }
  })();

  // Hapus
  function deleteViolation(id) {
    if (!confirm('Yakin menghapus pelanggaran ini?\nTindakan tidak dapat dibatalkan.')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('counselor/cases/delete/') ?>' + id;

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '<?= csrf_token() ?>';
    csrf.value = '<?= csrf_hash() ?>';
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
  }
</script>
<?php $this->endSection(); ?>
