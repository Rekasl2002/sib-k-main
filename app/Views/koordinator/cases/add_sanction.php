<?php
/**
 * File Path: app/Views/koordinator/cases/add_sanction.php
 *
 * Koordinator • Add Sanction Modal (Reusable)
 * - Single modal (ID unik)
 * - Kirim field penting ke controller cases::addSanction
 * - Upload multiple documents
 * - Durasi otomatis (start_date/end_date) + hidden duration_days
 * - Opsi verifikasi (Koordinator)
 *
 * Catatan:
 * - Tetap bisa reusable untuk Counselor/Koordinator dengan prefix URL otomatis.
 * - Guard izin final tetap wajib di Controller/Filter (RBAC).
 */

// Helpers (aman bila tidak ada)
if (function_exists('helper')) {
  helper(['app', 'permission']);
}

// Deteksi prefix dari URL: /counselor/... atau /koordinator/...
$seg1 = '';
try {
  $seg1 = service('uri')->getSegment(1) ?? '';
} catch (\Throwable $e) {
  $seg1 = '';
}

$prefix = in_array($seg1, ['counselor', 'koordinator'], true)
  ? $seg1
  : ((function_exists('is_koordinator') && is_koordinator()) ? 'koordinator' : 'counselor');

// Ambil data violation secara aman
$v = [];
if (isset($violation) && is_array($violation)) {
  $v = $violation;
}

// Ambil violation id robust (support $violation atau $violationId)
$__vid = (int)($v['id'] ?? 0);
if ($__vid <= 0 && isset($violationId)) {
  $__vid = (int)$violationId;
}

// Izin tambah sanksi (view-side hint; controller tetap penentu)
$__isKoordinator = function_exists('is_koordinator') ? (bool)is_koordinator() : false;

// Beberapa proyek pakai helper has_permission()/can()
$__hasPerm = function (string $perm): bool {
  if (function_exists('has_permission')) return (bool) has_permission($perm);
  return false;
};

// Jika helper izin tersedia, gunakan; jika tidak, fallback Koordinator boleh (sesuai pola lama)
$__permAware = function_exists('has_permission') || function_exists('can');
$canAddSanction = $__permAware
  ? ($__hasPerm('manage_sanctions') && $__hasPerm('manage_violations'))
  : $__isKoordinator;

// Jika status kasus dibatalkan, jangan boleh tambah
$caseStatus = (string)($v['status'] ?? '');
if ($caseStatus === 'Dibatalkan') {
  $canAddSanction = false;
}
?>

<!-- Add Sanction Modal -->
<div class="modal fade" id="addSanctionModal" tabindex="-1" aria-labelledby="addSanctionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <form
        action="<?= $canAddSanction && $__vid > 0 ? base_url($prefix . '/cases/addSanction/' . $__vid) : '#' ?>"
        method="POST"
        id="addSanctionForm"
        enctype="multipart/form-data"
        novalidate
      >
        <?= csrf_field() ?>

        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title" id="addSanctionModalLabel">
            <i class="mdi mdi-gavel me-2"></i>Tambah Sanksi
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <?php if (!$canAddSanction): ?>
            <div class="alert alert-danger border-0">
              <i class="mdi mdi-alert-circle-outline me-1"></i>
              Anda tidak memiliki izin untuk menambahkan sanksi pada kasus ini.
              <?php if ($caseStatus === 'Dibatalkan'): ?>
                <div class="mt-1 small text-muted">Kasus berstatus <strong>Dibatalkan</strong>, penambahan sanksi dinonaktifkan.</div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($__vid <= 0): ?>
            <div class="alert alert-danger border-0">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Data pelanggaran tidak valid (ID tidak ditemukan).
            </div>
          <?php endif; ?>

          <!-- Violation Info Summary -->
          <div class="alert alert-info border-0 mb-4">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="mdi mdi-information-outline fs-3"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-1"><?= esc($v['category_name'] ?? '-') ?></h6>
                <small class="text-muted">
                  <i class="mdi mdi-calendar me-1"></i><?= !empty($v['violation_date']) ? date('d M Y', strtotime($v['violation_date'])) : '-' ?>
                  | <i class="mdi mdi-account me-1"></i><?= esc($v['student_name'] ?? '-') ?>
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
                <select name="sanction_type" id="sanctionType" class="form-select" required <?= $canAddSanction ? '' : 'disabled' ?>>
                  <option value="">-- Pilih Jenis Sanksi --</option>
                  <?php foreach ($sanction_types as $type): ?>
                    <option value="<?= esc($type) ?>" <?= old('sanction_type') === $type ? 'selected' : '' ?>>
                      <?= esc($type) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input
                  type="text"
                  name="sanction_type"
                  id="sanctionType"
                  class="form-control"
                  placeholder="Contoh: Teguran Tertulis"
                  value="<?= old('sanction_type') ?>"
                  required
                  <?= $canAddSanction ? '' : 'disabled' ?>
                >
              <?php endif; ?>
              <div class="form-text">Pilih atau tulis jenis sanksi yang diberikan</div>
            </div>

            <!-- Tanggal Sanksi -->
            <div class="col-md-6">
              <label for="sanctionDate" class="form-label required">
                <i class="mdi mdi-calendar me-1"></i>Tanggal Pemberian Sanksi
              </label>
              <input
                type="date"
                name="sanction_date"
                id="sanctionDate"
                class="form-control"
                value="<?= old('sanction_date', date('Y-m-d')) ?>"
                required
                <?= $canAddSanction ? '' : 'disabled' ?>
              >
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
                    <input
                      type="date"
                      name="start_date"
                      id="startDate"
                      class="form-control"
                      value="<?= old('start_date') ?>"
                      onchange="calculateDuration()"
                      <?= $canAddSanction ? '' : 'disabled' ?>
                    >
                  </div>
                  <div class="col-md-6">
                    <label for="endDate" class="form-label">Tanggal Selesai</label>
                    <input
                      type="date"
                      name="end_date"
                      id="endDate"
                      class="form-control"
                      value="<?= old('end_date') ?>"
                      onchange="calculateDuration()"
                      <?= $canAddSanction ? '' : 'disabled' ?>
                    >
                  </div>
                </div>

                <!-- Hidden duration (opsional, tapi membantu sinkron field duration_days) -->
                <input type="hidden" name="duration_days" id="durationDays" value="<?= old('duration_days') ?>">

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
              <textarea
                name="description"
                id="sanctionDescription"
                class="form-control"
                rows="4"
                required
                <?= $canAddSanction ? '' : 'disabled' ?>
              ><?= old('description') ?></textarea>
              <div class="form-text">Minimal 10 karakter</div>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label for="sanctionStatus" class="form-label">
                <i class="mdi mdi-progress-check me-1"></i>Status Sanksi
              </label>
              <select name="status" id="sanctionStatus" class="form-select" <?= $canAddSanction ? '' : 'disabled' ?>>
                <?php $oldStatus = old('status', 'Dijadwalkan'); ?>
                <option value="Dijadwalkan"     <?= $oldStatus === 'Dijadwalkan' ? 'selected' : '' ?>>Dijadwalkan</option>
                <option value="Sedang Berjalan" <?= $oldStatus === 'Sedang Berjalan' ? 'selected' : '' ?>>Sedang Berjalan</option>
                <option value="Selesai"         <?= $oldStatus === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                <option value="Dibatalkan"      <?= $oldStatus === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
              </select>
              <div class="form-text">Status pelaksanaan saat ini</div>
            </div>

            <!-- Grup Completion (muncul jika status = Selesai) -->
            <div class="col-md-6" id="completionDateWrapper" style="display:none;">
              <label for="completedDate" class="form-label">Tanggal Selesai</label>
              <input type="date" name="completed_date" id="completedDate" class="form-control" value="<?= old('completed_date') ?>" <?= $canAddSanction ? '' : 'disabled' ?>>
            </div>
            <div class="col-12" id="completionNotesWrapper" style="display:none;">
              <label for="completionNotes" class="form-label">Catatan Penyelesaian</label>
              <textarea name="completion_notes" id="completionNotes" class="form-control" rows="2" <?= $canAddSanction ? '' : 'disabled' ?>><?= old('completion_notes') ?></textarea>
            </div>

            <!-- Dokumen & Notes -->
            <div class="col-12">
              <label class="form-label">Dokumen/Lampiran (opsional)</label>
              <input
                type="file"
                name="documents[]"
                class="form-control"
                multiple
                accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.mp4"
                <?= $canAddSanction ? '' : 'disabled' ?>
              >
              <small class="text-muted">jpg, png, pdf, doc, xls, mp4. Maks 5MB/berkas.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Catatan Internal (Notes)</label>
              <input type="text" name="notes" class="form-control" value="<?= old('notes') ?>" <?= $canAddSanction ? '' : 'disabled' ?>>
            </div>

            <!-- Parent Acknowledgement -->
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="parent_acknowledged" id="ackCheck" value="1" <?= old('parent_acknowledged') ? 'checked' : '' ?> <?= $canAddSanction ? '' : 'disabled' ?>>
                <label class="form-check-label" for="ackCheck">Orang tua mengetahui</label>
              </div>
            </div>

            <div class="col-md-6">
              <label for="ackAt" class="form-label">Waktu Orang Tua Mengetahui (opsional)</label>
              <input type="datetime-local" name="parent_acknowledged_at" id="ackAt" class="form-control" value="<?= old('parent_acknowledged_at') ?>" <?= $canAddSanction ? '' : 'disabled' ?>>
            </div>

            <!-- Verify now (Koordinator) -->
            <?php if ($__isKoordinator): ?>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="verify_now" id="verifyNow" value="1" <?= $canAddSanction ? '' : 'disabled' ?>>
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
          <button type="submit" class="btn btn-warning" id="submitSanctionBtn" <?= $canAddSanction && $__vid > 0 ? '' : 'disabled' ?>>
            <i class="mdi mdi-content-save me-1"></i>Simpan
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<script>
  function calculateDuration() {
    const startEl = document.getElementById('startDate');
    const endEl   = document.getElementById('endDate');
    const wrap    = document.getElementById('durationDisplay');
    const text    = document.getElementById('durationText');
    const hidden  = document.getElementById('durationDays');

    if (!startEl || !endEl || !wrap || !text) return;

    const start = startEl.value ? new Date(startEl.value) : null;
    const end   = endEl.value ? new Date(endEl.value) : null;

    const alertBox = wrap.querySelector('.alert');

    if (start && end) {
      wrap.style.display = 'block';

      if (end < start) {
        if (alertBox) {
          alertBox.classList.remove('alert-success');
          alertBox.classList.add('alert-danger');
        }
        text.textContent = 'Tanggal selesai harus setelah atau sama dengan tanggal mulai';
        if (hidden) hidden.value = '';
        return;
      }

      // inclusive days? (opsi kamu: saat ini hitung selisih hari, end==start => 0 hari)
      // kalau ingin end==start => 1 hari, ubah diff + 1
      const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
      if (alertBox) {
        alertBox.classList.remove('alert-danger');
        alertBox.classList.add('alert-success');
      }
      text.textContent = diff + ' hari';
      if (hidden) hidden.value = diff;
    } else {
      wrap.style.display = 'none';
      if (hidden) hidden.value = '';
    }
  }

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
        // Kalau tombol disabled, jangan submit
        if (submit && submit.disabled) {
          e.preventDefault();
          return false;
        }

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

    const modalEl = document.getElementById('addSanctionModal');
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', function() {
        if (form) form.reset();

        const durWrap = document.getElementById('durationDisplay');
        if (durWrap) durWrap.style.display = 'none';

        const durHidden = document.getElementById('durationDays');
        if (durHidden) durHidden.value = '';

        toggleCompletionFields();

        if (submit) {
          // hanya enable kembali jika awalnya tidak disabled permanen
          if (submit.getAttribute('data-perm-disabled') !== '1') {
            submit.disabled = submit.disabled && submit.hasAttribute('disabled') ? true : false;
          }
          submit.disabled = submit.disabled && submit.hasAttribute('disabled') ? true : false;
          submit.innerHTML = '<i class="mdi mdi-content-save me-1"></i>Simpan';
        }
      });

      modalEl.addEventListener('shown.bs.modal', function() {
        document.getElementById('sanctionType')?.focus();
        calculateDuration();
      });
    }
  });
</script>

<style>
  #addSanctionModal .form-label.required::after {
    content: " *";
    color: #f46a6a;
    font-weight: bold;
  }
  #addSanctionModal textarea { resize: vertical; min-height: 100px; }
  #durationDisplay { animation: fadeIn .3s ease-in; }
  @keyframes fadeIn {
    from { opacity:0; transform: translateY(-10px); }
    to   { opacity:1; transform: translateY(0); }
  }
</style>
