<?php
/**
 * File Path: app/Views/koordinator/sanctions/edit.php
 * Edit Sanksi (Koordinator)
 * - UI disamakan dengan counselor/sanctions/edit.php
 * - Semua action diarahkan ke prefix /koordinator
 */

$this->extend('layouts/main');
$this->section('content');

helper('app');

$statusOptions = ['Dijadwalkan','Sedang Berjalan','Selesai','Dibatalkan'];

if (!function_exists('safe_json_array')) {
    function safe_json_array($value) {
        if (is_array($value)) return $value;
        if (is_string($value) && strlen(trim($value))) {
            $d = json_decode($value, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }
}

$docs = safe_json_array($sanction['documents'] ?? null);

if (empty($type_options) || !is_array($type_options)) {
    $type_options = [
        'Teguran Lisan','Teguran Tertulis','Pemanggilan Orang Tua','Pembinaan Khusus',
        'Skorsing 1 Hari','Skorsing 3 Hari','Skorsing 1 Minggu','Kerja Sosial',
        'Poin Pengurangan','Pembuatan Surat Pernyataan','Konseling Wajib'
    ];
}
?>

<?= show_alerts() ?>

<!-- Page Title -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-0">Edit Sanksi</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('koordinator/cases') ?>">Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('koordinator/cases/detail/'.$sanction['violation_id']) ?>">Detail Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item active">Detail Sanksi</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- LEFT: Form -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header bg-primary">
        <h5 class="mb-0 text-white"><i class="mdi mdi-pencil me-2"></i>Form Edit Sanksi</h5>
      </div>
      <div class="card-body">
        <form action="<?= base_url('koordinator/sanctions/update/'.(int)($sanction['id'] ?? 0)) ?>"
              method="post" enctype="multipart/form-data" id="sanctionEditForm">
          <?= csrf_field() ?>
          <input type="hidden" name="violation_id" value="<?= (int)($sanction['violation_id'] ?? 0) ?>">

          <!-- Jenis & Tanggal & Status -->
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Jenis Sanksi <span class="text-danger">*</span></label>
              <select name="sanction_type" class="form-select" required>
                <?php foreach ($type_options as $opt): ?>
                  <option value="<?= esc($opt) ?>" <?= (($sanction['sanction_type'] ?? '') === $opt) ? 'selected' : '' ?>>
                    <?= esc($opt) ?>
                  </option>
                <?php endforeach; ?>
                <?php if (!in_array(($sanction['sanction_type'] ?? ''), $type_options ?? [], true) && !empty($sanction['sanction_type'])): ?>
                  <option value="<?= esc($sanction['sanction_type']) ?>" selected>(Lainnya) <?= esc($sanction['sanction_type']) ?></option>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Sanksi <span class="text-danger">*</span></label>
              <input type="date" name="sanction_date" class="form-control" required
                     value="<?= esc(!empty($sanction['sanction_date']) ? date('Y-m-d', strtotime($sanction['sanction_date'])) : date('Y-m-d')) ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" id="statusSelect" class="form-select" required>
                <?php foreach ($statusOptions as $st): ?>
                  <option value="<?= esc($st) ?>" <?= (($sanction['status'] ?? '') === $st) ? 'selected' : '' ?>>
                    <?= esc($st) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Periode Pelaksanaan -->
          <div class="border rounded p-3 mt-3 bg-light">
            <div class="d-flex align-items-center justify-content-between">
              <h6 class="mb-0"><i class="mdi mdi-calendar-range me-2"></i>Periode Pelaksanaan (opsional)</h6>
              <small class="text-muted">Isi untuk skorsing/pembinaan berkala</small>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label">Mulai</label>
                <input type="date" name="start_date" id="startDate" class="form-control"
                       value="<?= esc(!empty($sanction['start_date']) ? date('Y-m-d', strtotime($sanction['start_date'])) : '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Selesai</label>
                <input type="date" name="end_date" id="endDate" class="form-control"
                       value="<?= esc(!empty($sanction['end_date']) ? date('Y-m-d', strtotime($sanction['end_date'])) : '') ?>">
              </div>
              <div class="col-12" id="durationDisplay" style="display:none;">
                <div class="alert alert-success mb-0">
                  Durasi terhitung: <strong id="durationText">0 hari</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Deskripsi -->
          <div class="mt-3">
            <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
            <textarea name="description" rows="4" class="form-control" required><?= esc($sanction['description'] ?? '') ?></textarea>
            <div class="form-text">Minimal 10 karakter</div>
          </div>

          <!-- Penyelesaian -->
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date" name="completed_date" class="form-control"
                     value="<?= esc(!empty($sanction['completed_date']) ? date('Y-m-d', strtotime($sanction['completed_date'])) : '') ?>">
            </div>
            <div class="col-md-8">
              <label class="form-label">Catatan Penyelesaian</label>
              <input type="text" name="completion_notes" class="form-control"
                     value="<?= esc($sanction['completion_notes'] ?? '') ?>">
            </div>
          </div>

          <!-- Acknowledgement Ortu -->
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="ackCheck"
                       name="parent_acknowledged" value="1"
                       <?= !empty($sanction['parent_acknowledged']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="ackCheck">Orang tua mengetahui</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Waktu Mengetahui</label>
              <input type="datetime-local" name="parent_acknowledged_at" class="form-control"
                     value="<?php
                        if (!empty($sanction['parent_acknowledged_at'])) {
                            echo esc(date('Y-m-d\TH:i', strtotime($sanction['parent_acknowledged_at'])));
                        }
                     ?>">
            </div>
          </div>

          <!-- Verifikasi (Koordinator) -->
          <?php if (function_exists('is_koordinator') && is_koordinator()): ?>
          <div class="mt-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="verifyNow" name="verify_now" value="1">
              <label class="form-check-label" for="verifyNow">Verifikasi sekarang</label>
            </div>
            <?php if (!empty($sanction['verified_by'])): ?>
              <small class="text-muted d-block mt-1">
                Sudah diverifikasi sebelumnya pada
                <?= esc(!empty($sanction['verified_at']) ? date('d M Y H:i', strtotime($sanction['verified_at'])) : '-') ?>
              </small>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Dokumen & Notes -->
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Dokumen/Lampiran (multi)</label>
              <input type="file" name="documents[]" class="form-control" multiple>
              <small class="text-muted">jpg, png, pdf, doc, xls, mp4. Maks 5MB/berkas.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Catatan Internal (Notes)</label>
              <input type="text" name="notes" class="form-control" value="<?= esc($sanction['notes'] ?? '') ?>">
            </div>
          </div>

          <?php if (!empty($docs)): ?>
          <div class="mt-3">
            <label class="form-label d-block">Lampiran Saat Ini</label>
            <ul class="list-unstyled mb-0">
              <?php foreach ($docs as $i => $doc):
                  $path = is_array($doc) ? ($doc['path'] ?? '') : (string)$doc;
                  $name = is_array($doc) ? ($doc['name'] ?? basename($path)) : basename($path);
                  if (!$path) continue;
              ?>
                <li class="mb-2 d-flex align-items-center justify-content-between">
                  <div class="me-2">
                    <i class="mdi mdi-paperclip text-secondary me-1"></i>
                    <a href="<?= base_url($path) ?>" target="_blank" rel="noopener"><?= esc($name) ?></a>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remove_docs[]"
                           id="rm<?= (int)$i ?>" value="<?= esc($path) ?>">
                    <label class="form-check-label small" for="rm<?= (int)$i ?>">hapus</label>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
            <small class="text-muted d-block mt-1">Centang “hapus” lalu klik Simpan untuk menghapus lampiran dari data.</small>
          </div>
          <?php endif; ?>

          <div class="d-grid d-sm-flex gap-2 mt-4">
            <button class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i>Simpan</button>
            <a href="<?= base_url('koordinator/cases/detail/'.(int)($sanction['violation_id'] ?? 0)) ?>" class="btn btn-secondary">
              <i class="mdi mdi-arrow-left me-1"></i>Kembali
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- RIGHT: Ringkas Pelanggaran -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="mdi mdi-alert-circle-outline me-2"></i>Ringkasan Pelanggaran</h6>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <div class="text-muted">Siswa</div>
          <strong><?= esc($sanction['student_name'] ?? '—') ?></strong>
          <small class="text-muted d-block">
            NISN: <?= esc($sanction['nisn'] ?? '—') ?>
            <?php if (!empty($sanction['class_name'])): ?>
              | Kelas: <?= esc($sanction['class_name']) ?>
            <?php endif; ?>
          </small>
        </div>
        <div class="mb-2">
          <div class="text-muted">Kategori</div>
          <div><?= esc($sanction['category_name'] ?? '—') ?></div>
        </div>
        <div>
          <div class="text-muted">Tanggal Pelanggaran</div>
          <div><?= !empty($sanction['violation_date']) ? date('d/m/Y', strtotime($sanction['violation_date'])) : '—' ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const startEl = document.getElementById('startDate');
  const endEl   = document.getElementById('endDate');
  const box     = document.getElementById('durationDisplay');
  const text    = document.getElementById('durationText');

  function updateDuration(){
    if (!startEl || !endEl || !box || !text) return;
    const sVal = startEl.value;
    const eVal = endEl.value;
    if (!sVal || !eVal) { box.style.display = 'none'; return; }

    const s = new Date(sVal);
    const e = new Date(eVal);
    if (isNaN(s.getTime()) || isNaN(e.getTime())) { box.style.display = 'none'; return; }

    const diff = Math.ceil((e - s) / (1000*60*60*24));
    if (e >= s && diff >= 0) {
      text.textContent = diff + ' hari';
      box.style.display = 'block';
    } else {
      text.textContent = 'Tanggal selesai harus >= tanggal mulai';
      box.style.display = 'block';
    }
  }

  if (startEl) startEl.addEventListener('change', updateDuration);
  if (endEl) endEl.addEventListener('change', updateDuration);
  updateDuration();

  const form = document.getElementById('sanctionEditForm');
  if (form) {
    form.addEventListener('submit', function(e){
      const status = document.getElementById('statusSelect')?.value || '';
      const completed = form.querySelector('input[name="completed_date"]');
      const desc = form.querySelector('textarea[name="description"]');

      if (desc && desc.value.trim().length < 10) {
        e.preventDefault();
        alert('Deskripsi minimal 10 karakter.');
        desc.focus();
        return false;
      }

      if (status === 'Selesai' && completed && !completed.value) {
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth()+1).padStart(2,'0');
        const d = String(today.getDate()).padStart(2,'0');
        completed.value = y + '-' + m + '-' + d;
      }
    });
  }
})();
</script>
<?php $this->endSection(); ?>
