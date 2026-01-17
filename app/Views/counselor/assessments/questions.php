<?php
/** @var array $assessment */
/** @var array $questions */
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
function v($a,$k,$d=''){ return esc($a[$k] ?? $d); }

$flashSuccess = session()->getFlashdata('success');
$flashError   = session()->getFlashdata('error');
$errors       = session('errors') ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">
    <i class="fas fa-question-circle me-2"></i>
    Pertanyaan: <?= esc($assessment['title'] ?? '') ?>
  </h4>
  <div>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('counselor/assessments/'.$assessment['id']) ?>">
      <i class="fas fa-arrow-left me-1"></i> Detail Asesmen
    </a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('counselor/assessments') ?>">
      <i class="fas fa-list me-1"></i> Semua Asesmen
    </a>
  </div>
</div>

<?php if ($flashSuccess): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?= esc($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flashError): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?= esc($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.esc($e).'</li>'; ?></ul>
  </div>
<?php endif; ?>

<!-- Form Tambah Pertanyaan -->
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header bg-white">
    <strong><i class="fas fa-plus me-2 text-primary"></i>Tambah Pertanyaan</strong>
  </div>
  <div class="card-body">
    <form method="post"
          action="<?= site_url('counselor/assessments/'.$assessment['id'].'/questions/add') ?>"
          id="questionForm"
          enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Teks Pertanyaan <span class="text-danger">*</span></label>
          <textarea name="question_text" class="form-control" rows="3" required placeholder="Tulis pertanyaan di sini..."></textarea>
        </div>

        <div class="col-md-3">
          <label class="form-label">Tipe</label>
          <select name="question_type" id="question_type" class="form-select">
            <?php foreach(['Multiple Choice','True/False','Rating Scale','Checkbox','Essay'] as $t): ?>
              <option><?= $t ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Untuk tipe selain Essay, isi Options.</div>
        </div>

        <div class="col-md-2">
          <label class="form-label">Poin</label>
          <input type="number" step="0.01" name="points" id="points" class="form-control" value="1">
          <div class="form-text">Isi &gt; 0 untuk soal dinilai. Isi <strong>0</strong> untuk <em>Survei</em> atau jawaban tanpa benar-salah agar bisa membuat soal tanpa kunci jawaban.</div>
        </div>

        <div class="col-md-1 d-flex align-items-end">
          <div class="form-check mt-2">
            <input type="hidden" name="is_required" value="0">
            <input class="form-check-input" type="checkbox" name="is_required" id="is_required" value="1" checked>
            <label class="form-check-label" for="is_required">Wajib</label>
          </div>
        </div>

        <!-- OPTIONS BUILDER (add) -->
        <div class="col-12" id="options-builder">
          <label class="form-label d-flex align-items-center gap-2">
            <span>Options</span>
            <small class="text-muted" id="hint-by-type"></small>
          </label>

          <div class="d-flex flex-wrap gap-2 mb-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-option">
              <i class="fas fa-plus"></i> Tambah Opsi
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-fill-true-false">Isi True/False</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-fill-rating">Isi 1–5</button>
            <!-- NEW: clear all correct answers (ADD) -->
            <button type="button" class="btn btn-sm btn-outline-warning" id="btn-clear-correct">
              <i class="fas fa-eraser me-1"></i>Bersihkan Jawaban Benar
            </button>
          </div>

          <div id="options-list" class="vstack gap-2"></div>
          <div class="form-text" id="options-note"></div>
        </div>

        <!-- Warning bila belum pilih jawaban benar -->
        <div id="correct-warning" class="alert alert-warning d-none mt-2">
          <i class="fas fa-exclamation-triangle me-1"></i>
          Untuk tipe <strong>Multiple Choice</strong>, <strong>True/False</strong>, atau <strong>Checkbox</strong>,
          harap tandai <em>jawaban benar</em> terlebih dahulu. (Dikecualikan bila <strong>Poin = 0</strong>)
        </div>

        <!-- EXPLANATION -->
        <div class="col-12">
          <label class="form-label" id="label-explanation">Pembahasan (opsional)</label>
          <textarea name="explanation" class="form-control" rows="2" placeholder="Penjelasan atau kunci bahasan"></textarea>
          <div class="form-text" id="note-explanation">Untuk Essay, isi sebagai contoh jawaban/kunci penilaian.</div>
        </div>

        <!-- IMAGE SOURCE: URL / UPLOAD (ADD) -->
        <div class="col-12">
          <label class="form-label">Gambar (opsional)</label>
          <div class="mb-2">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="image_source" id="img_src_url" value="url" checked>
              <label class="form-check-label" for="img_src_url">URL Internet</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="image_source" id="img_src_upload" value="upload">
              <label class="form-check-label" for="img_src_upload">Upload dari Perangkat</label>
            </div>
          </div>

          <div id="wrap_image_url" class="mb-2">
            <input type="url" class="form-control" name="image_url" id="image_url" placeholder="https://contoh.com/gambar.jpg">
            <div class="form-text">Kosongkan jika tidak memakai gambar.</div>
          </div>

          <div id="wrap_image_file" class="mb-2 d-none">
            <input type="file" class="form-control" name="image_file" id="image_file" accept="image/*">
            <div class="form-text">Pilih file gambar (jpg, png, webp, gif). Maks 2MB.</div>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Dimensi (opsional)</label>
          <input name="dimension" class="form-control" placeholder="Aspek/Skala (mis. Kecemasan, Minat Teknologi)">
        </div>
      </div>

      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-add">
          <i class="fas fa-save me-1"></i> Tambah Pertanyaan
        </button>
        <a class="btn btn-secondary btn-sm" href="<?= site_url('counselor/assessments') ?>">
          <i class="fas fa-list me-1"></i> Kembali
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Daftar Pertanyaan -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <strong><i class="fas fa-database me-2 text-primary"></i>Daftar Pertanyaan</strong>
    <?php if (!empty($questions)): ?>
      <span class="badge bg-primary"><?= count($questions) ?> item</span>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:5%">#</th>
            <th>Pertanyaan</th>
            <th style="width:12%">Tipe</th>
            <th style="width:8%" class="text-center">Poin</th>
            <th style="width:18%">Options</th>
            <th style="width:16%">Jawaban Benar</th>
            <th style="width:10%" class="text-center">Wajib</th>
            <th style="width:10%">Dimensi</th>
            <th style="width:16%" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($questions)): ?>
            <tr>
              <td colspan="9" class="text-center py-4 text-muted">
                <i class="fas fa-info-circle me-1"></i> Belum ada pertanyaan.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($questions as $i=>$q): ?>
              <?php
                $optsRaw  = $q['options'] ?? [];
                if (is_string($optsRaw)) {
                  $opts = json_decode($optsRaw, true) ?: [];
                } else {
                  $opts = (array) $optsRaw;
                }
                $opts = array_values(array_filter(array_map('strval', $opts), fn($s)=>trim($s)!=='')); 
                $optsJson = json_encode($opts, JSON_UNESCAPED_UNICODE);

                $orderNo  = $q['order_number'] ?? ($i+1);
                $required = (isset($q['is_required']) && ((string)$q['is_required']==='1' || $q['is_required']===1));

                $pointsVal = (float)($q['points'] ?? 0);
                $isUngraded = $pointsVal <= 0;

                // Render correct answer
                $correctDisp = $isUngraded ? 'Tidak dinilai' : '-';
                if (!$isUngraded && !empty($q['correct_answer'])) {
                  if (($q['question_type'] ?? '') === 'Checkbox') {
                    $ca = json_decode((string) $q['correct_answer'], true);
                    $correctDisp = is_array($ca) ? implode(', ', $ca) : (string) $q['correct_answer'];
                  } elseif (($q['question_type'] ?? '') === 'Essay') {
                    $correctDisp = 'Manual';
                  } else {
                    $correctDisp = (string) $q['correct_answer'];
                  }
                } elseif (($q['question_type'] ?? '') === 'Essay') {
                  $correctDisp = 'Manual';
                }

                $needsCorrect   = in_array(($q['question_type'] ?? ''), ['Multiple Choice','True/False','Checkbox'], true);
                $isEmptyCorrect = empty($q['correct_answer']);

                // Data gambar untuk modal
                $rawImg  = (string)($q['image_url'] ?? '');
                $imgSrc  = $rawImg ? (preg_match('~^https?://~i', $rawImg) ? $rawImg : base_url($rawImg)) : '';
                $imgName = $rawImg ? basename($rawImg) : '';
              ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= esc($q['question_text']) ?></td>
                <td><?= esc($q['question_type']) ?></td>
                <td class="text-center">
                  <?php if ($isUngraded): ?>
                    <span class="badge bg-secondary">0</span>
                  <?php else: ?>
                    <?= esc($q['points']) ?>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($opts)): ?>
                    <div class="small text-muted">
                      <?= esc(implode(', ', $opts)) ?>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!$isUngraded && $isEmptyCorrect && $needsCorrect): ?>
                    <span class="badge bg-warning text-dark me-1">Belum ditandai</span>
                  <?php endif; ?>
                  <?= esc($correctDisp) ?>
                </td>
                <td class="text-center">
                  <?= $required ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>' ?>
                </td>
                <td>
                  <?php if (($q['dimension'] ?? '') !== ''): ?>
                    <?= esc($q['dimension']) ?>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm" role="group">
                    <!-- View -->
                    <button type="button"
                      class="btn btn-outline-secondary btn-view"
                      data-bs-toggle="modal" data-bs-target="#viewModal"
                      data-id="<?= (int)$q['id'] ?>"
                      data-text="<?= esc($q['question_text'], 'attr') ?>"
                      data-type="<?= esc($q['question_type'], 'attr') ?>"
                      data-points="<?= esc($q['points'], 'attr') ?>"
                      data-required="<?= $required ? '1' : '0' ?>"
                      data-options='<?= esc($optsJson, 'attr') ?>'
                      data-correct="<?= esc((string)($q['correct_answer'] ?? ''), 'attr') ?>"
                      data-dimension="<?= esc((string)($q['dimension'] ?? ''), 'attr') ?>"
                      data-image="<?= esc((string)($q['image_url'] ?? ''), 'attr') ?>"
                      data-imgsrc="<?= esc($imgSrc, 'attr') ?>"
                      data-imgname="<?= esc($imgName, 'attr') ?>"
                      data-explanation="<?= esc((string)($q['explanation'] ?? ''), 'attr') ?>"
                      data-orderno="<?= esc((string)$orderNo, 'attr') ?>"
                    >
                      <i class="fas fa-eye"></i>
                    </button>

                    <!-- Edit -->
                    <button type="button"
                      class="btn btn-outline-primary btn-edit"
                      data-bs-toggle="modal" data-bs-target="#editModal"
                      data-id="<?= (int)$q['id'] ?>"
                      data-text="<?= esc($q['question_text'], 'attr') ?>"
                      data-type="<?= esc($q['question_type'], 'attr') ?>"
                      data-points="<?= esc($q['points'], 'attr') ?>"
                      data-required="<?= $required ? '1' : '0' ?>"
                      data-options='<?= esc($optsJson, 'attr') ?>'
                      data-correct="<?= esc((string)($q['correct_answer'] ?? ''), 'attr') ?>"
                      data-dimension="<?= esc((string)($q['dimension'] ?? ''), 'attr') ?>"
                      data-image="<?= esc((string)($q['image_url'] ?? ''), 'attr') ?>"
                      data-imgsrc="<?= esc($imgSrc, 'attr') ?>"
                      data-imgname="<?= esc($imgName, 'attr') ?>"
                      data-explanation="<?= esc((string)($q['explanation'] ?? ''), 'attr') ?>"
                      data-orderno="<?= esc((string)$orderNo, 'attr') ?>"
                    >
                      <i class="fas fa-edit"></i>
                    </button>

                    <!-- Delete -->
                    <form method="post"
                          action="<?= site_url('counselor/assessments/'.$assessment['id'].'/questions/'.$q['id'].'/delete') ?>"
                          class="d-inline"
                          onsubmit="return confirm('Hapus pertanyaan ini?');">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Detail Pertanyaan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Teks</dt>
          <dd class="col-sm-9" id="v_text"></dd>

          <dt class="col-sm-3">Tipe</dt>
          <dd class="col-sm-9" id="v_type"></dd>

          <dt class="col-sm-3">Poin</dt>
          <dd class="col-sm-9" id="v_points"></dd>

          <dt class="col-sm-3">Wajib</dt>
          <dd class="col-sm-9" id="v_required"></dd>

          <dt class="col-sm-3">Options</dt>
          <dd class="col-sm-9" id="v_options"></dd>

          <dt class="col-sm-3">Jawaban Benar</dt>
          <dd class="col-sm-9" id="v_correct"></dd>

          <dt class="col-sm-3">Dimensi</dt>
          <dd class="col-sm-9" id="v_dimension"></dd>

          <dt class="col-sm-3">Gambar</dt>
          <dd class="col-sm-9">
            <div id="v_image"></div>
            <div class="small text-muted" id="v_image_name"></div>
          </dd>

          <dt class="col-sm-3">Pembahasan</dt>
          <dd class="col-sm-9" id="v_explanation"></dd>

          <dt class="col-sm-3">Urutan</dt>
          <dd class="col-sm-9" id="v_orderno"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" id="editForm" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Pertanyaan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Teks Pertanyaan</label>
              <textarea name="question_text" id="e_text" class="form-control" rows="3" required></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipe</label>
              <select name="question_type" id="e_type" class="form-select">
                <?php foreach(['Multiple Choice','True/False','Rating Scale','Checkbox','Essay'] as $t): ?>
                  <option><?= $t ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Untuk tipe selain Essay, isi/cek Options.</div>
            </div>

            <div class="col-md-2">
              <label class="form-label">Poin</label>
              <input type="number" step="0.01" name="points" id="e_points" class="form-control">
              <div class="form-text">Isi 0 untuk <em>Survei</em> (tidak mempengaruhi skor).</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Urutan</label>
              <input type="number" name="order_no" id="e_order" class="form-control" min="1" max="<?= count($questions ?? []) ?>">
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check mt-2">
                <input type="hidden" name="is_required" value="0">
                <input class="form-check-input" type="checkbox" name="is_required" id="e_required" value="1">
                <label class="form-check-label" for="e_required">Wajib</label>
              </div>
            </div>

            <!-- OPTIONS BUILDER (edit) -->
            <div class="col-12" id="e_options_block">
              <label class="form-label d-flex justify-content-between align-items-center">
                <span>Options</span>
                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-sm btn-outline-primary" id="e_btnAddOption">
                    <i class="fas fa-plus me-1"></i>Tambah Option
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="e_btnFillDefault">
                    Isi Default (TF/Rating)
                  </button>
                  <!-- NEW: clear all correct answers (EDIT) -->
                  <button type="button" class="btn btn-sm btn-outline-warning" id="e_btnClearCorrect">
                    <i class="fas fa-eraser me-1"></i>Bersihkan Jawaban Benar
                  </button>
                </div>
              </label>
              <div id="e_options_container" class="vstack gap-2"></div>
            </div>

            <!-- Warning bila belum pilih jawaban benar (EDIT) -->
            <div id="e_correct_warning" class="alert alert-warning d-none">
              <i class="fas fa-exclamation-triangle me-1"></i>
              Untuk tipe <strong>Multiple Choice</strong>, <strong>True/False</strong>, atau <strong>Checkbox</strong>,
              harap tandai <em>jawaban benar</em> terlebih dahulu. (Dikecualikan bila <strong>Poin = 0</strong>)
            </div>

            <!-- IMAGE SOURCE: URL / UPLOAD (EDIT) -->
            <div class="col-md-12">
              <label class="form-label">Gambar (opsional)</label>
              <div class="d-flex gap-4 align-items-center mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="image_source" id="e_img_src_url" value="url" checked>
                  <label class="form-check-label" for="e_img_src_url">URL Internet</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="image_source" id="e_img_src_upload" value="upload">
                  <label class="form-check-label" for="e_img_src_upload">Upload dari Perangkat</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" id="e_btn_clear_image">
                  <i class="fas fa-trash-alt me-1"></i> Hapus Gambar
                </button>
              </div>

              <div id="e_wrap_image_url" class="mb-2">
                <input type="url" name="image_url" id="e_image_url" class="form-control" placeholder="https://contoh.com/gambar.webp">
                <div class="form-text">Kosongkan untuk menghapus gambar.</div>
              </div>

              <div id="e_wrap_image_file" class="mb-2" style="display:none;">
                <input type="file" name="image_file" id="e_image_file" accept=".jpg,.jpeg,.png,.webp,.gif" class="form-control">
                <div class="form-text">Pilih file baru untuk mengganti gambar. Maks 2MB.</div>
              </div>

              <div id="e_current_image" class="small text-muted"></div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Dimensi</label>
              <input name="dimension" id="e_dimension" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label">Pembahasan</label>
              <textarea name="explanation" id="e_explanation" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // ===== Util umum =====
  function escapeHtml(s){ return (s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
  function tryJsonParse(v){ try { return JSON.parse(v); } catch(e){ return null; } }

  // Validasi file (ukuran <= 2MB dan tipe diperbolehkan)
  function validateImageFile(input){
    const f = input.files && input.files[0];
    if(!f) return true;
    const max = 2 * 1024 * 1024;
    const okType = /image\/(jpeg|jpg|png|webp|gif)/i.test(f.type) || /\.(jpe?g|png|webp|gif)$/i.test(f.name);
    if (f.size > max || !okType) {
      alert('File tidak valid. Pastikan tipe gambar (JPG/PNG/WEBP/GIF) dan ukuran ≤ 2MB.');
      input.value = '';
      return false;
    }
    return true;
  }

  // ===== Options Builder: ADD =====
  (function(){
    const typeSel = document.getElementById('question_type');
    const builder = document.getElementById('options-builder');
    const list    = document.getElementById('options-list');
    const hint    = document.getElementById('hint-by-type');
    const note    = document.getElementById('options-note');

    function rowHtml(type, value='', isCorrect=false){
      const isMulti = (type === 'Checkbox');
      const mark = isMulti
        ? `<input class="form-check-input mt-2" type="checkbox" name="correct_options[]" value="${escapeHtml(value)}" ${isCorrect?'checked':''}>`
        : `<input class="form-check-input mt-2" type="radio" name="correct_option" value="${escapeHtml(value)}" ${isCorrect?'checked':''}>`;
      return `
        <div class="d-flex align-items-start gap-2 option-row">
          <div class="form-check mt-1">${mark}</div>
          <input type="text" class="form-control" name="options[]" placeholder="Teks opsi"
                 value="${escapeHtml(value)}" oninput="this.previousElementSibling.querySelector('input').value=this.value">
          <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.option-row').remove()">
            <i class="fas fa-trash"></i>
          </button>
        </div>`;
    }

    function setHints(type){
      const map = {
        'Multiple Choice': 'Tandai satu opsi sebagai jawaban benar (kecuali Poin=0).',
        'True/False': 'Pilih salah satu yang benar (kecuali Poin=0).',
        'Rating Scale': 'Buat skala 1–5; jawaban benar opsional.',
        'Checkbox': 'Tandai satu atau lebih opsi yang benar (kecuali Poin=0).',
        'Essay': 'Tidak ada opsi. Nilai manual atau gunakan contoh jawaban.'
      };
      hint.textContent = map[type] || '';
      note.textContent = (type === 'Checkbox')
        ? 'Nilai otomatis: benar jika pilihan siswa sama persis dengan jawaban benar.'
        : '';
      document.getElementById('label-explanation').textContent = (type === 'Essay')
        ? 'Contoh Jawaban / Kunci (opsional)'
        : 'Pembahasan (opsional)';
      document.getElementById('note-explanation').textContent = (type === 'Essay')
        ? 'Gunakan sebagai acuan penilaian manual.'
        : 'Opsional, tampil sebagai pembahasan.';
    }

    function toggle(type){
      const isEssay = (type === 'Essay');
      builder.style.display = isEssay ? 'none' : '';
      const tfBtn = document.getElementById('btn-fill-true-false');
      const rtBtn = document.getElementById('btn-fill-rating');
      if (tfBtn) tfBtn.style.display = (type === 'True/False') ? '' : 'none';
      if (rtBtn) rtBtn.style.display = (type === 'Rating Scale') ? '' : 'none';
      setHints(type);
      if (isEssay) { list.innerHTML = ''; }
      if (type === 'True/False' && list.children.length === 0) fillTrueFalse();
      if (type === 'Rating Scale' && list.children.length === 0) fillRating();
    }

    function fillTrueFalse(){
      list.innerHTML = rowHtml('True/False', 'True', true)
                     + rowHtml('True/False', 'False', false);
    }
    function fillRating(){
      list.innerHTML = '';
      for (let i=1;i<=5;i++){
        list.insertAdjacentHTML('beforeend', rowHtml('Rating Scale', String(i), i===5));
      }
    }

    const addBtn = document.getElementById('btn-add-option');
    if (addBtn) addBtn.addEventListener('click', ()=> {
      const t = typeSel.value;
      if (t === 'Essay') return;
      list.insertAdjacentHTML('beforeend', rowHtml(t, '', false));
    });
    const tfBtn = document.getElementById('btn-fill-true-false');
    if (tfBtn) tfBtn.addEventListener('click', fillTrueFalse);
    const rtBtn = document.getElementById('btn-fill-rating');
    if (rtBtn) rtBtn.addEventListener('click', fillRating);

    // NEW: clear all correct answers (ADD)
    const clrBtn = document.getElementById('btn-clear-correct');
    if (clrBtn) clrBtn.addEventListener('click', function(){
      list.querySelectorAll('input[name="correct_option"], input[name="correct_options[]"]').forEach(el => { el.checked = false; });
    });

    if (typeSel) {
      typeSel.addEventListener('change', ()=> { list.innerHTML=''; toggle(typeSel.value); });
      toggle(typeSel.value || 'Multiple Choice');
    }

    // Init toggle image (ADD) with unique IDs + guard
    const rUrl  = document.getElementById('img_src_url');
    const rUp   = document.getElementById('img_src_upload');
    const wUrl  = document.getElementById('wrap_image_url');
    const wFile = document.getElementById('wrap_image_file');
    const fAdd  = document.getElementById('image_file');

    function toggleAddImage(source){
      if (!wUrl || !wFile) return;
      if (source === 'upload') { wUrl.classList.add('d-none'); wFile.classList.remove('d-none'); }
      else { wUrl.classList.remove('d-none'); wFile.classList.add('d-none'); }
    }
    [rUrl, rUp].forEach(r=> r && r.addEventListener('change', ()=> toggleAddImage(r.value)));
    toggleAddImage(document.querySelector('input[name="image_source"]:checked')?.value || 'url');
    if (fAdd) fAdd.addEventListener('change', ()=> validateImageFile(fAdd));

    // ====== Guard submit (ADD): hanya blokir bila Poin > 0 dan tipe butuh jawaban benar ======
    const addForm = document.getElementById('questionForm');
    const addType = document.getElementById('question_type');
    const addPts  = document.getElementById('points');
    const addWarn = document.getElementById('correct-warning');

    function requiresCorrect(type){
      return type === 'Multiple Choice' || type === 'True/False' || type === 'Checkbox';
    }
    function hasCorrectMarkedAdd(type){
      if (type === 'Checkbox') return document.querySelectorAll('#options-list input[name="correct_options[]"]:checked').length > 0;
      if (type === 'Multiple Choice' || type === 'True/False') return !!document.querySelector('#options-list input[name="correct_option"]:checked');
      return true;
    }
    function addUngraded(){
      const raw = (addPts && addPts.value !== '') ? addPts.value : '0'; // jika kosong, anggap 0 agar tidak mengunci
      const p = Number(raw);
      return !Number.isNaN(p) && p <= 0;
    }
    function refreshAddWarning(){
      const t = addType ? addType.value : 'Multiple Choice';
      const ungraded = addUngraded();
      if (ungraded || !requiresCorrect(t)) {
        addWarn && addWarn.classList.add('d-none');
      }
    }
    if (addForm){
      addForm.addEventListener('submit', function(ev){
        const t = addType ? addType.value : 'Multiple Choice';
        const ungraded = addUngraded();
        if (!ungraded && requiresCorrect(t) && !hasCorrectMarkedAdd(t)){
          ev.preventDefault();
          if (addWarn){ addWarn.classList.remove('d-none'); addWarn.scrollIntoView({behavior:'smooth', block:'center'}); }
        } else if (addWarn){
          addWarn.classList.add('d-none');
        }
      });
    }
    addType && addType.addEventListener('change', refreshAddWarning);
    addPts  && addPts.addEventListener('input',  refreshAddWarning);
    refreshAddWarning();
  })();

  // ===== View & Edit modal wiring (delegation) =====
  (function(){
    function get(btn, name){ return btn.getAttribute('data-'+name) || ''; }
    function setVal(id, v){ const el=document.getElementById(id); if(el) el.value=v; }
    function setText(id, v){ const el=document.getElementById(id); if(el) el.textContent=v; }

    document.addEventListener('click', function(e){
      const viewBtn = e.target.closest('.btn-view');
      const editBtn = e.target.closest('.btn-edit');

      // ---------- VIEW ----------
      if (viewBtn){
        const type    = get(viewBtn,'type');
        const opts    = tryJsonParse(get(viewBtn,'options')) || [];
        const corrRaw = get(viewBtn,'correct');
        const pts     = parseFloat(get(viewBtn,'points') || '0');
        const ungraded = !isNaN(pts) && pts <= 0;

        let corr='-';
        if (ungraded){
          corr = 'Tidak dinilai';
        } else if (type === 'Checkbox'){
          const arr = tryJsonParse(corrRaw);
          corr = Array.isArray(arr) ? (arr.length ? arr.join(', ') : '-') : (corrRaw || '-');
        } else if (type === 'Essay'){ corr='Manual'; } else { corr=corrRaw || '-'; }

        setText('v_text', get(viewBtn,'text'));
        setText('v_type', type);
        setText('v_points', isNaN(pts) ? '-' : (ungraded ? '0 (Survei)' : pts));
        setText('v_required', get(viewBtn,'required')==='1' ? 'Ya' : 'Tidak');
        setText('v_options', opts.length ? opts.join(', ') : '-');
        setText('v_correct', corr);
        setText('v_dimension', get(viewBtn,'dimension') || '-');
        setText('v_explanation', get(viewBtn,'explanation') || '-');
        setText('v_orderno', get(viewBtn,'orderno') || '-');

        const src  = get(viewBtn,'imgsrc');
        const name = get(viewBtn,'imgname');
        const wrap = document.getElementById('v_image');
        const nm   = document.getElementById('v_image_name');
        if (wrap){
          if (src){
            wrap.innerHTML = `<img src="${src}" alt="question image" class="img-fluid border rounded" style="max-height:240px">`;
            if (nm) nm.textContent = name ? name : src;
          } else {
            wrap.textContent = '-';
            if (nm) nm.textContent = '';
          }
        }
        return;
      }

      // ---------- EDIT ----------
      if (editBtn){
        const eForm  = document.getElementById('editForm');
        const base   = "<?= site_url('counselor/assessments/'.$assessment['id'].'/questions') ?>";
        if (eForm) eForm.action = `${base}/${get(editBtn,'id')}/update`;

        setVal('e_text',        get(editBtn,'text'));
        setVal('e_points',      get(editBtn,'points') || '1');
        const reqEl = document.getElementById('e_required');
        if (reqEl) reqEl.checked = (get(editBtn,'required')==='1');
        setVal('e_order',       get(editBtn,'orderno') || '');
        setVal('e_dimension',   get(editBtn,'dimension') || '');
        setVal('e_explanation', get(editBtn,'explanation') || '');

        // Tipe + Options
        const eType   = document.getElementById('e_type');
        const eOpts   = document.getElementById('e_options_container');
        const eBlock  = document.getElementById('e_options_block');

        function eRowHtml(type, value='', isCorrect=false){
          const isMulti = (type === 'Checkbox');
          const mark = isMulti
            ? `<input class="form-check-input mt-2" type="checkbox" name="correct_options[]" value="${escapeHtml(value)}" ${isCorrect?'checked':''}>`
            : `<input class="form-check-input mt-2" type="radio" name="correct_option" value="${escapeHtml(value)}" ${isCorrect?'checked':''}>`;
          return `
            <div class="d-flex align-items-start gap-2 option-row">
              <div class="form-check mt-1">${mark}</div>
              <input type="text" class="form-control" name="options[]" placeholder="Teks opsi"
                     value="${escapeHtml(value)}" oninput="this.previousElementSibling.querySelector('input').value=this.value">
              <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.option-row').remove()">
                <i class="fas fa-trash"></i>
              </button>
            </div>`;
        }
        function eFillTF(){
          eOpts.innerHTML = eRowHtml('True/False','True',true)+eRowHtml('True/False','False',false);
        }
        function eFillRating(){
          eOpts.innerHTML = '';
          for(let i=1;i<=5;i++){ eOpts.insertAdjacentHTML('beforeend', eRowHtml('Rating Scale', String(i), i===5)); }
        }
        function eToggle(type){ if (eBlock) eBlock.style.display = (type==='Essay') ? 'none' : ''; }

        const type = get(editBtn,'type') || 'Multiple Choice';
        if (eType) { eType.value = type; eToggle(type); }

        const opts = tryJsonParse(get(editBtn,'options')) || [];
        const corrRaw = get(editBtn,'correct') || '';
        let corrSet = new Set(); let corrVal = null;
        if (type === 'Checkbox'){
          const arr = tryJsonParse(corrRaw); if (Array.isArray(arr)) corrSet = new Set(arr.map(String));
        } else if (type !== 'Essay'){
          corrVal = corrRaw || null;
        }

        if (eOpts){
          eOpts.innerHTML = '';
          if (opts.length){
            opts.forEach(opt=>{
              const isC = (type==='Checkbox') ? corrSet.has(String(opt)) : (String(opt)===String(corrVal));
              eOpts.insertAdjacentHTML('beforeend', eRowHtml(type, String(opt), isC));
            });
          } else {
            if (type==='True/False') eFillTF();
            else if (type==='Rating Scale') eFillRating();
          }
        }

        // Bind change type (EDIT)
        if (eType && !eType.dataset.boundChange){
          eType.addEventListener('change', function(){
            const t = this.value;
            eToggle(t);
            if (!eOpts) return;
            eOpts.innerHTML = '';
            if (t==='True/False') eFillTF();
            else if (t==='Rating Scale') eFillRating();
          });
          eType.dataset.boundChange = '1';
        }

        // Image edit controls
        const eSrcUrl   = document.getElementById('e_img_src_url');
        const eSrcUp    = document.getElementById('e_img_src_upload');
        const eWrapUrl  = document.getElementById('e_wrap_image_url');
        const eWrapFile = document.getElementById('e_wrap_image_file');
        const eImgUrl   = document.getElementById('e_image_url');
        const eImgFile  = document.getElementById('e_image_file');
        const eCurrent  = document.getElementById('e_current_image');
        const eClear    = document.getElementById('e_btn_clear_image');

        function toggleEditImage(source){
          if (!eWrapUrl || !eWrapFile) return;
          if (source === 'upload'){ eWrapUrl.style.display='none'; eWrapFile.style.display=''; }
          else { eWrapUrl.style.display=''; eWrapFile.style.display='none'; }
        }

        const srcAbs  = get(editBtn,'imgsrc');  // absolute preview
        const raw     = get(editBtn,'image');   // stored (relative or URL)
        const name    = get(editBtn,'imgname');

        if (eImgUrl) eImgUrl.value = '';
        if (eImgFile) eImgFile.value = '';

        if (srcAbs){
          if (eCurrent) eCurrent.innerHTML = `Gambar saat ini: <a href="${srcAbs}" target="_blank">${name ? name : srcAbs}</a>`;
          if (/^https?:\/\//i.test(raw)){ if (eSrcUrl) eSrcUrl.checked = true; toggleEditImage('url'); if (eImgUrl) eImgUrl.value = raw; }
          else { if (eSrcUp) eSrcUp.checked = true; toggleEditImage('upload'); }
        } else {
          if (eCurrent) eCurrent.textContent = 'Tidak ada gambar tersimpan.';
          if (eSrcUrl) eSrcUrl.checked = true; toggleEditImage('url');
        }

        [eSrcUrl, eSrcUp].forEach(function(radio){
          if (!radio || radio.dataset.boundEditImage) return;
          radio.addEventListener('change', function(){ toggleEditImage(this.value); });
          radio.dataset.boundEditImage = '1';
        });

        if (eClear && !eClear.dataset.bound){
          eClear.addEventListener('click', function(){
            if (eImgUrl) eImgUrl.value = '';
            if (eImgFile) eImgFile.value = '';
            if (eSrcUrl) eSrcUrl.checked = true;
            toggleEditImage('url');
            if (eCurrent) eCurrent.textContent = 'Gambar akan dihapus setelah disimpan.';
          });
          eClear.dataset.bound = '1';
        }

        const eImgFileOnce = document.getElementById('e_image_file');
        if (eImgFileOnce && !eImgFileOnce.dataset.boundValidate){
          eImgFileOnce.addEventListener('change', ()=> validateImageFile(eImgFileOnce));
          eImgFileOnce.dataset.boundValidate = '1';
        }
      }
    });

    // Validasi "jawaban benar" di modal edit (submit) — dikecualikan jika Poin <= 0
    (function(){
      const eForm   = document.getElementById('editForm');
      const eType   = document.getElementById('e_type');
      const ePts    = document.getElementById('e_points');
      const eWarn   = document.getElementById('e_correct_warning');

      function requiresCorrect(type){
        return type === 'Multiple Choice' || type === 'True/False' || type === 'Checkbox';
      }
      function hasCorrectMarkedEdit(type){
        const scope = document.getElementById('editModal');
        if (!scope) return true;
        if (type === 'Checkbox') return scope.querySelectorAll('input[name="correct_options[]"]:checked').length > 0;
        if (type === 'Multiple Choice' || type === 'True/False') return !!scope.querySelector('input[name="correct_option"]:checked');
        return true;
      }
      function editUngraded(){
        const raw = (ePts && ePts.value !== '') ? ePts.value : '0';
        const p = Number(raw);
        return !Number.isNaN(p) && p <= 0;
      }

      if (eForm){
        eForm.addEventListener('submit', function(ev){
          const t = eType ? eType.value : 'Multiple Choice';
          const ungraded = editUngraded();

          if (!ungraded && requiresCorrect(t) && !hasCorrectMarkedEdit(t)){
            ev.preventDefault();
            if (eWarn) {
              eWarn.classList.remove('d-none');
              eWarn.scrollIntoView({behavior:'smooth', block:'center'});
            }
          } else if (eWarn) {
            eWarn.classList.add('d-none');
          }
        });
      }

      // NEW: clear all correct answers (EDIT)
      document.addEventListener('click', function(ev){
        if (ev.target.closest('#e_btnClearCorrect')){
          const scope = document.getElementById('editModal');
          scope && scope.querySelectorAll('input[name="correct_option"], input[name="correct_options[]"]').forEach(el => { el.checked = false; });
        }
      });

      // Sembunyikan warning dinamis saat Poin diubah atau tipe berubah
      function refreshEditWarning(){
        const t = eType ? eType.value : 'Multiple Choice';
        const ungraded = editUngraded();
        if (ungraded || !requiresCorrect(t)) {
          eWarn && eWarn.classList.add('d-none');
        }
      }
      eType && eType.addEventListener('change', refreshEditWarning);
      ePts  && ePts.addEventListener('input',  refreshEditWarning);
    })();
  })();

  // ========= Delegated handlers tombol di EDIT modal =========
  document.addEventListener('click', function(ev){
    function rowHtml(type, value, isCorrect){
      const esc = (s)=> (s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
      const isMulti = (type === 'Checkbox');
      const mark = isMulti
        ? `<input class="form-check-input mt-2" type="checkbox" name="correct_options[]" value="${esc(value || '')}" ${isCorrect?'checked':''}>`
        : `<input class="form-check-input mt-2" type="radio"    name="correct_option"   value="${esc(value || '')}" ${isCorrect?'checked':''}>`;
      return `
        <div class="d-flex align-items-start gap-2 option-row">
          <div class="form-check mt-1">${mark}</div>
          <input type="text" class="form-control" name="options[]" placeholder="Teks opsi"
                 value="${esc(value || '')}" oninput="this.previousElementSibling.querySelector('input').value=this.value">
          <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.option-row').remove()">
            <i class="fas fa-trash"></i>
          </button>
        </div>`;
    }

    // Tambah Option (EDIT)
    if (ev.target.closest('#e_btnAddOption')){
      const typeEl = document.getElementById('e_type');
      const cont   = document.getElementById('e_options_container');
      if (!typeEl || !cont) return;
      const t = typeEl.value || 'Multiple Choice';
      if (t === 'Essay') return;
      cont.insertAdjacentHTML('beforeend', rowHtml(t, '', false));
    }

    // Isi Default (EDIT)
    if (ev.target.closest('#e_btnFillDefault')){
      const typeEl = document.getElementById('e_type');
      const cont   = document.getElementById('e_options_container');
      if (!typeEl || !cont) return;
      const t = typeEl.value || 'Multiple Choice';
      cont.innerHTML = '';
      if (t === 'True/False'){
        cont.insertAdjacentHTML('beforeend', rowHtml('True/False', 'True',  true));
        cont.insertAdjacentHTML('beforeend', rowHtml('True/False', 'False', false));
      } else if (t === 'Rating Scale'){
        for (let i=1; i<=5; i++) cont.insertAdjacentHTML('beforeend', rowHtml('Rating Scale', String(i), i===5));
      }
    }
  });
</script>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .table> :not(caption)>*>* { padding: .75rem .75rem; }
  .card { transition: transform .2s; }
  .card:hover { transform: translateY(-1px); }
  .option-row .btn { padding: 0 .6rem; }
</style>
<?= $this->endSection() ?>
