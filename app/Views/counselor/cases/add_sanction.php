<?php
/**
 * File Path: app/Views/counselor/cases/add_sanction.php
 *
 * Add Sanction Modal (final)
 * - Single modal (ID unik)
 * - Kirim semua field penting ke controller sanctions::store
 * - Upload multiple documents
 * - Durasi otomatis (start_date/end_date)
 * - Opsi verifikasi (Koordinator)
 */
?>

<!-- Add Sanction Modal -->
<div class="modal fade" id="addSanctionModal" tabindex="-1" aria-labelledby="addSanctionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form action="<?= base_url('counselor/cases/addSanction/' . $violation['id']) ?>"
        method="POST" id="addSanctionForm" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title" id="addSanctionModalLabel">
            <i class="mdi mdi-gavel me-2"></i>Tambah Sanksi
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <!-- Violation Info Summary -->
          <div class="alert alert-info border-0 mb-4">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="mdi mdi-information-outline fs-3"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-1"><?= esc($violation['category_name']) ?></h6>
                <small class="text-muted">
                  <i class="mdi mdi-calendar me-1"></i><?= date('d M Y', strtotime($violation['violation_date'])) ?>
                  | <i class="mdi mdi-account me-1"></i><?= esc($violation['student_name']) ?>
                </small>
              </div>
            </div>
          </div>

          <div class="row g-3">

            <!-- Jenis Sanksi -->
            <div class="col-md-6">
              <label for="sanctionType" class="form-label required">
                <i class="mdi mdi-format-list-bulleted me-1"></i>Jenis Sanksi
              </label>
              <?php if (isset($sanction_types) && is_array($sanction_types) && count($sanction_types)): ?>
                <select name="sanction_type" id="sanctionType" class="form-select" required>
                  <option value="">-- Pilih Jenis Sanksi --</option>
                  <?php foreach ($sanction_types as $type): ?>
                    <option value="<?= esc($type) ?>" <?= old('sanction_type') === $type ? 'selected' : '' ?>>
                      <?= esc($type) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input type="text" name="sanction_type" id="sanctionType" class="form-control" placeholder="Contoh: Teguran Tertulis" required>
              <?php endif; ?>
              <div class="form-text">Pilih atau tulis jenis sanksi yang diberikan</div>
            </div>

            <!-- Tanggal Sanksi -->
            <div class="col-md-6">
              <label for="sanctionDate" class="form-label required">
                <i class="mdi mdi-calendar me-1"></i>Tanggal Pemberian Sanksi
              </label>
              <input type="date" name="sanction_date" id="sanctionDate" class="form-control"
                     value="<?= old('sanction_date', date('Y-m-d')) ?>" required>
              <div class="form-text">Tanggal sanksi ditetapkan</div>
            </div>

            <!-- Periode Pelaksanaan -->
            <div class="col-12">
              <div class="border rounded p-3 bg-light">
                <h6 class="mb-3">
                  <i class="mdi mdi-calendar-range me-1"></i>Periode Pelaksanaan
                  <small class="text-muted">(Opsional: skorsing, pembinaan berkala, dll)</small>
                </h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="startDate" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="startDate" class="form-control" value="<?= old('start_date') ?>" onchange="calculateDuration()">
                  </div>
                  <div class="col-md-6">
                    <label for="endDate" class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="endDate" class="form-control" value="<?= old('end_date') ?>" onchange="calculateDuration()">
                  </div>
                </div>

                <div id="durationDisplay" class="mt-2" style="display:none;">
                  <div class="alert alert-success mb-0">
                    <i class="mdi mdi-information-outline me-1"></i>
                    Durasi sanksi: <strong id="durationText">0 hari</strong>
                  </div>
                </div>
              </div>
            </div>

            <!-- Deskripsi -->
            <div class="col-12">
              <label for="sanctionDescription" class="form-label required">
                <i class="mdi mdi-text-box-outline me-1"></i>Deskripsi Sanksi
              </label>
              <textarea name="description" id="sanctionDescription" class="form-control" rows="4" required><?= old('description') ?></textarea>
              <div class="form-text">Minimal 10 karakter</div>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label for="sanctionStatus" class="form-label">
                <i class="mdi mdi-progress-check me-1"></i>Status Sanksi
              </label>
              <select name="status" id="sanctionStatus" class="form-select">
                <option value="Dijadwalkan" selected>Dijadwalkan</option>
                <option value="Sedang Berjalan">Sedang Berjalan</option>
                <option value="Selesai">Selesai</option>
                <option value="Dibatalkan">Dibatalkan</option>
              </select>
              <div class="form-text">Status pelaksanaan saat ini</div>
            </div>

            <!-- Grup Completion (muncul jika status = Selesai) -->
            <div class="col-md-6" id="completionDateWrapper" style="display:none;">
              <label for="completedDate" class="form-label">Tanggal Selesai</label>
              <input type="date" name="completed_date" id="completedDate" class="form-control">
            </div>
            <div class="col-12" id="completionNotesWrapper" style="display:none;">
              <label for="completionNotes" class="form-label">Catatan Penyelesaian</label>
              <textarea name="completion_notes" id="completionNotes" class="form-control" rows="2"></textarea>
            </div>

            <!-- Dokumen & Notes -->
            <div class="mb-3">
                <label class="form-label">Dokumen/Lampiran (opsional)</label>
                <input type="file" name="documents[]" class="form-control" multiple>
                <small class="text-muted">jpg, png, pdf, doc, xls, mp4. Maks 5MB/berkas.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Catatan Internal (Notes)</label>
              <input type="text" name="notes" class="form-control" value="<?= old('notes') ?>">
            </div>

            <!-- Parent Acknowledgement -->
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="parent_acknowledged" id="ackCheck" value="1" <?= old('parent_acknowledged') ? 'checked' : '' ?>>
                <label class="form-check-label" for="ackCheck">Orang tua mengetahui</label>
              </div>
            </div>
            <div class="col-md-6">
              <label for="ackAt" class="form-label">Waktu Orang Tua Mengetahui (opsional)</label>
              <input type="datetime-local" name="parent_acknowledged_at" id="ackAt" class="form-control" value="<?= old('parent_acknowledged_at') ?>">
            </div>

            <!-- Verify now (Koordinator) -->
            <?php if (function_exists('is_koordinator') && is_koordinator()): ?>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="verify_now" id="verifyNow" value="1">
                <label class="form-check-label" for="verifyNow">Verifikasi sekarang (rekam verified_by & verified_at)</label>
              </div>
            </div>
            <?php endif; ?>

          </div>

          <!-- Tips -->
          <div class="alert alert-success border-0 mt-3 mb-0">
            <div class="d-flex">
              <div class="flex-shrink-0">
                <i class="mdi mdi-lightbulb-on-outline fs-5"></i>
              </div>
              <div class="flex-grow-1 ms-2">
                <strong class="d-block mb-2">Tips Pemberian Sanksi:</strong>
                <ul class="mb-0 ps-3">
                  <li>Selaraskan sanksi dengan tingkat keparahan pelanggaran</li>
                  <li>Rinci prosedur pelaksanaan yang terukur</li>
                  <li>Sifat sanksi edukatif dan tidak melanggar HAM</li>
                  <li>Dokumentasikan bukti pelaksanaan</li>
                </ul>
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="mdi mdi-close me-1"></i>Batal
          </button>
          <button type="submit" class="btn btn-warning" id="submitSanctionBtn">
            <i class="mdi mdi-content-save me-1"></i>Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Durasi antara start & end
  function calculateDuration() {
    const startEl = document.getElementById('startDate');
    const endEl   = document.getElementById('endDate');
    if (!startEl || !endEl) return;

    const start = startEl.value ? new Date(startEl.value) : null;
    const end   = endEl.value ? new Date(endEl.value) : null;

    const wrap = document.getElementById('durationDisplay');
    const text = document.getElementById('durationText');
    if (!wrap || !text) return;

    if (start && end) {
      if (end < start) {
        wrap.style.display = 'block';
        wrap.querySelector('.alert').classList.remove('alert-success');
        wrap.querySelector('.alert').classList.add('alert-danger');
        text.textContent = 'Tanggal selesai harus setelah atau sama dengan tanggal mulai';
      } else {
        const diff = Math.ceil((end - start) / (1000*60*60*24));
        wrap.style.display = 'block';
        wrap.querySelector('.alert').classList.remove('alert-danger');
        wrap.querySelector('.alert').classList.add('alert-success');
        text.textContent = diff + ' hari';
      }
    } else {
      wrap.style.display = 'none';
    }
  }

  // Tampilkan field completion saat status = Selesai
  function toggleCompletionFields() {
    const statusEl = document.getElementById('sanctionStatus');
    const show = statusEl && statusEl.value === 'Selesai';
    const dateWrap  = document.getElementById('completionDateWrapper');
    const notesWrap = document.getElementById('completionNotesWrapper');
    if (dateWrap)  dateWrap.style.display = show ? '' : 'none';
    if (notesWrap) notesWrap.style.display = show ? '' : 'none';
  }

  document.addEventListener('DOMContentLoaded', function() {
    const form   = document.getElementById('addSanctionForm');
    const submit = document.getElementById('submitSanctionBtn');
    const desc   = document.getElementById('sanctionDescription');
    const status = document.getElementById('sanctionStatus');
    const compDt = document.getElementById('completedDate');

    if (status) {
      status.addEventListener('change', toggleCompletionFields);
      toggleCompletionFields();
    }

    if (form) {
      form.addEventListener('submit', function(e) {
        // Deskripsi minimal 10 karakter
        if (desc && desc.value.trim().length < 10) {
          e.preventDefault();
          alert('Deskripsi sanksi harus minimal 10 karakter');
          desc.focus();
          return false;
        }
        // Validasi rentang tanggal
        const s = document.getElementById('startDate')?.value;
        const t = document.getElementById('endDate')?.value;
        if (s && t && new Date(t) < new Date(s)) {
          e.preventDefault();
          alert('Tanggal selesai harus setelah atau sama dengan tanggal mulai');
          return false;
        }
        // Jika status Selesai tapi tanggal selesai kosong, isikan tanggal sanksi
        if (status && status.value === 'Selesai' && compDt && !compDt.value) {
          const sancDate = document.getElementById('sanctionDate')?.value;
          if (sancDate) compDt.value = sancDate;
        }

        // cegah double submit
        if (submit) {
          submit.disabled = true;
          submit.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menyimpan...';
        }
      });
    }

    // Reset ketika modal ditutup
    const modalEl = document.getElementById('addSanctionModal');
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', function() {
        if (form) form.reset();
        document.getElementById('durationDisplay').style.display = 'none';
        toggleCompletionFields();
        if (submit) {
          submit.disabled = false;
          submit.innerHTML = '<i class="mdi mdi-content-save me-1"></i>Simpan';
        }
      });

      // autofocus
      modalEl.addEventListener('shown.bs.modal', function() {
        document.getElementById('sanctionType')?.focus();
      });
    }
  });
</script>

<style>
  /* Required mark */
  #addSanctionModal .form-label.required::after {
    content: " *";
    color: #f46a6a;
    font-weight: bold;
  }
  #addSanctionModal textarea { resize: vertical; min-height: 100px; }
  #durationDisplay { animation: fadeIn .3s ease-in; }
  @keyframes fadeIn { from {opacity:0; transform:translateY(-10px);} to {opacity:1; transform:translateY(0);} }
</style>
