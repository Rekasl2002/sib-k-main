<?php
/**
 * File Path: app/Views/counselor/sessions/add_note.php
 *
 * Add Note Modal
 * Modal untuk menambahkan catatan ke sesi konseling
 */

// Pastikan helper form tersedia untuk old()
helper('form');
?>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form
                action="<?= base_url('counselor/sessions/addNote/' . (int)($session['id'] ?? 0)) ?>"
                method="POST"
                id="addNoteForm"
                enctype="multipart/form-data"
            >
                <?= csrf_field() ?>

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addNoteModalLabel">
                        <i class="mdi mdi-note-plus-outline me-2"></i>Tambah Catatan Sesi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Session Info Summary -->
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-information-outline fs-3"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1"><?= esc($session['topic'] ?? 'Tanpa Judul') ?></h6>
                                <small class="text-muted">
                                    <?php if (!empty($session['session_date'])): ?>
                                        <i class="mdi mdi-calendar me-1"></i><?= date('d M Y', strtotime($session['session_date'])) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($session['student_name'])): ?>
                                        <?= !empty($session['session_date']) ? ' | ' : '' ?>
                                        <i class="mdi mdi-account me-1"></i><?= esc($session['student_name']) ?>
                                    <?php elseif (!empty($session['class_name'])): ?>
                                        <?= !empty($session['session_date']) ? ' | ' : '' ?>
                                        <i class="mdi mdi-google-classroom me-1"></i>Kelas <?= esc($session['class_name']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Note Type -->
                    <div class="mb-3">
                        <label class="form-label">Jenis Catatan</label>
                        <?php
                            $types       = ['Observasi','Diagnosis','Intervensi','Follow-up','Lainnya'];
                            $currentType = old('note_type') ?: 'Observasi';
                        ?>
                        <select name="note_type" class="form-select">
                            <?php foreach ($types as $type): ?>
                                <option value="<?= esc($type) ?>" <?= $currentType === $type ? 'selected' : '' ?>>
                                    <?= esc($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Note Content -->
                    <div class="mb-3">
                        <label for="noteContent" class="form-label required">
                            <i class="mdi mdi-text-box-outline me-1"></i>Isi Catatan
                        </label>
                        <textarea
                            name="note_content"
                            id="noteContent"
                            class="form-control"
                            rows="6"
                            placeholder="Tuliskan catatan tentang sesi konseling ini... (perkembangan siswa, observasi, hasil diskusi, dll)"
                            required
                        ><?= esc(old('note_content')) ?></textarea>
                        <div class="form-text">
                            <i class="mdi mdi-information-outline me-1"></i>
                            Catatan akan tersimpan dengan timestamp dan nama Anda secara otomatis
                        </div>
                    </div>

                    <!-- Flags: Rahasia & Penting -->
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="noteConfidential"
                                    name="is_confidential"
                                    value="1"
                                    <?= old('is_confidential') ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="noteConfidential">
                                    Catatan Rahasia (tidak muncul di akun siswa)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2 mt-md-0">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="noteImportant"
                                    name="is_important"
                                    value="1"
                                    <?= old('is_important') ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="noteImportant">
                                    Tandai sebagai Catatan Penting
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="mt-3 mb-1">
                        <label for="noteAttachments" class="form-label">
                            <i class="mdi mdi-paperclip me-1"></i>Lampiran (opsional)
                        </label>
                        <input
                            type="file"
                            name="attachments[]"
                            id="noteAttachments"
                            class="form-control"
                            multiple
                        >
                        <div class="form-text">
                            <i class="mdi mdi-information-outline me-1"></i>
                            Anda dapat menambahkan beberapa file pendukung (misalnya hasil asesmen, foto dokumentasi, atau dokumen pendukung lain).
                            Disarankan maksimal 5 file per catatan.
                        </div>
                    </div>

                    <!-- Tips Section -->
                    <div class="alert alert-success border-0 mb-0 mt-3">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-lightbulb-on-outline fs-5"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <strong class="d-block mb-2">Tips Menulis Catatan:</strong>
                                <ul class="mb-0 ps-3">
                                    <li>Catat perkembangan atau perubahan perilaku siswa</li>
                                    <li>Dokumentasikan keputusan atau kesepakatan penting</li>
                                    <li>Tulis observasi yang relevan untuk tindak lanjut</li>
                                    <li>Gunakan bahasa yang jelas dan profesional</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-success" id="submitNoteBtn">
                        <i class="mdi mdi-content-save me-1"></i>Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addNoteForm     = document.getElementById('addNoteForm');
    const submitBtn       = document.getElementById('submitNoteBtn');
    const noteContent     = document.getElementById('noteContent');
    const noteAttachments = document.getElementById('noteAttachments');
    const addNoteModal    = document.getElementById('addNoteModal');

    if (addNoteForm && noteContent && submitBtn) {
        addNoteForm.addEventListener('submit', function(e) {
            // Konsisten dengan server: min 5, max 2000
            const minLen = 5;
            const maxLen = 2000;
            const val    = noteContent.value.trim();

            if (val.length < minLen) {
                e.preventDefault();
                alert('Catatan harus minimal ' + minLen + ' karakter');
                noteContent.focus();
                return false;
            }
            if (val.length > maxLen) {
                e.preventDefault();
                alert('Catatan maksimal ' + maxLen + ' karakter');
                noteContent.focus();
                return false;
            }

            // Opsional: batasi jumlah lampiran (misal maksimal 5 file)
            if (noteAttachments && noteAttachments.files && noteAttachments.files.length > 5) {
                e.preventDefault();
                alert('Maksimal 5 lampiran per catatan.');
                return false;
            }

            // Prevent double submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menyimpan...';
        });

        // Opsional: hard-limit ke 2000 karakter
        noteContent.addEventListener('input', function() {
            const maxLen = 2000;
            if (this.value.length > maxLen) {
                this.value = this.value.substring(0, maxLen);
            }
        });
    }

    // Reset form ketika modal ditutup
    if (addNoteModal) {
        addNoteModal.addEventListener('hidden.bs.modal', function() {
            if (addNoteForm) {
                addNoteForm.reset();
            }
            if (noteAttachments) {
                noteAttachments.value = '';
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="mdi mdi-content-save me-1"></i>Simpan Catatan';
            }
        });

        // Auto-focus
        addNoteModal.addEventListener('shown.bs.modal', function() {
            if (noteContent) {
                noteContent.focus();
            }
        });
    }
});
</script>

<style>
    /* Modal Specific Styles */
    #addNoteModal .form-check-input { cursor: pointer; }
    #addNoteModal .form-check-label { cursor: pointer; }
    #addNoteModal textarea { resize: vertical; min-height: 150px; }

    /* Required Field Indicator */
    .required::after { content: " *"; color: #f46a6a; font-weight: bold; }

    .alert .mdi { font-size: 1.25rem; }
    .alert ul li { margin-bottom: 0.25rem; font-size: 0.9rem; }
    .alert ul li:last-child { margin-bottom: 0; }
</style>
