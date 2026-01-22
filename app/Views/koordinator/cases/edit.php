<?php
/**
 * File Path: app/Views/koordinator/cases/edit.php
 *
 * Koordinator BK • Edit Case View
 * Disamakan dengan counselor/cases/edit.php (struktur & tampilan)
 *
 * Catatan:
 * - POST ke koordinator/cases/update/{id}
 * - Kategori & siswa readonly
 * - Mendukung hapus sebagian evidence & upload evidence baru (multi)
 * - ✅ Tambahan KP: Bisa mengganti "Ditangani Oleh" (handled_by) dari halaman edit
 */

$this->extend('layouts/main');
$this->section('content');

// helper optional: jangan fatal kalau helper tidak ada
try {
    helper(['permission', 'app', 'auth']);
} catch (\Throwable $e) {
    // ignore
}

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

// Guard tombol aksi (sidebar-style)
$canManageViolations = function_exists('has_permission')
    ? (bool) has_permission('manage_violations')
    : true;

// ====== NEW: data user login (untuk opsi "Saya (Koordinator)")
$session = function_exists('session') ? session() : null;
$myId = 0;
try {
    $myId = function_exists('auth_id') ? (int) auth_id() : (int) ($session ? ($session->get('user_id') ?? 0) : 0);
} catch (\Throwable $e) {
    $myId = (int) ($session ? ($session->get('user_id') ?? 0) : 0);
}
$myName = (string) ($session ? ($session->get('full_name') ?? $session->get('name') ?? $session->get('user_full_name') ?? '') : '');
if (trim($myName) === '') {
    $myName = 'Saya (Koordinator)';
}

$currentHandledBy = (int)($violation['handled_by'] ?? 0);

// Pastikan $counselors aman
$counselors = $counselors ?? [];
if (!is_array($counselors)) $counselors = [];

// Evidence files: fallback decode dari JSON evidence jika evidence_files tidak ada
$evidenceFiles = [];
if (!empty($violation['evidence_files']) && is_array($violation['evidence_files'])) {
    $evidenceFiles = $violation['evidence_files'];
} elseif (!empty($violation['evidence']) && is_string($violation['evidence'])) {
    $tmp = json_decode($violation['evidence'], true);
    if (is_array($tmp)) $evidenceFiles = $tmp;
}
?>

<!-- Page Title -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Edit Kasus & Pelanggaran</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('koordinator/cases') ?>">Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item active">Edit Kasus & Pelanggaran</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?= function_exists('show_alerts') ? show_alerts() : '' ?>

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
      action="<?= base_url('koordinator/cases/update/' . (int)($violation['id'] ?? 0)) ?>"
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

          <!-- ✅ NEW: Ditangani Oleh (handled_by) -->
          <div class="row g-3 mb-3">
            <div class="col-12">
              <label class="form-label">Ditangani Oleh (Penangan BK)</label>
              <select name="handled_by" class="form-select">
                <option value="">-- Pilih Penangan --</option>

                <?php if ($myId > 0): ?>
                  <option value="<?= (int)$myId ?>" <?= ($currentHandledBy === (int)$myId) ? 'selected' : '' ?>>
                    Saya (Koordinator): <?= esc($myName) ?>
                  </option>
                <?php endif; ?>

                <?php if (!empty($counselors)): ?>
                  <?php foreach ($counselors as $c): ?>
                    <?php
                      if (!is_array($c) || empty($c['id'])) continue;
                      $cid = (int) $c['id'];
                      $cname = (string) ($c['name'] ?? $c['full_name'] ?? $c['email'] ?? ('User#'.$cid));

                      // Hindari duplikasi jika "saya" juga ada di list
                      if ($myId > 0 && $cid === (int)$myId) continue;
                    ?>
                    <option value="<?= $cid ?>" <?= ($currentHandledBy === $cid) ? 'selected' : '' ?>>
                      <?= esc($cname) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>

              <small class="text-muted d-block mt-1">
                Pilih <strong>Saya (Koordinator)</strong> untuk mengembalikan penanganan ke koordinator, atau pilih Guru BK untuk mendelegasikan.
              </small>
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
          <?php if (!empty($evidenceFiles) && is_array($evidenceFiles)): ?>
            <div class="mb-3">
              <label class="form-label">Lampiran Saat Ini</label>
              <ul class="list-unstyled mb-0">
                <?php foreach ($evidenceFiles as $f):
                    $rel = ltrim(preg_replace('#/+#','/', (string)$f), '/'); ?>
                  <li class="mb-1 d-flex align-items-center">
                    <a href="<?= base_url($rel) ?>" target="_blank" rel="noopener" class="me-2">
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
        <a href="<?= base_url('koordinator/cases/detail/' . (int)($violation['id'] ?? 0)) ?>" class="btn btn-secondary">
          <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Detail
        </a>

        <?php if ($canManageViolations): ?>
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
      // Saat ini selalu tampil; jika perlu, sesuaikan:
      // wrap.style.display = (select.value === 'Selesai' || select.value === 'Dibatalkan') ? 'block' : 'none';
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
    form.action = '<?= base_url('koordinator/cases/delete/') ?>' + id;

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
