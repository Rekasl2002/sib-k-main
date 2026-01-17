<?php
/**
 * File Path: app/Views/counselor/career/form_university.php
 *
 * Vars:
 * - $university (array|null) atau $uni (array|null)
 * - $errors (array)
 * - $mode   ('create'|'edit')
 *
 * Fitur:
 * - Input URL/Upload logo dengan preview + opsi hapus
 * - Field JSON dinamis: faculties, programs, scholarships, contacts
 * - Select Status & Publikasi sesuai tampilan yang diminta (oldv() + $career)
 */

$this->extend('layouts/main');
$this->section('content');

// Kompatibilitas data & helper
$errors  = $errors ?? [];
$isEdit  = (($mode ?? 'create') === 'edit');

// Alias aman: $career akan dipakai konsisten di view ini
$career = $university ?? ($uni ?? ($career ?? []));

if (!function_exists('oldv')) {
    function oldv($key, $default = '') {
        $v = old($key);
        return $v !== null ? $v : $default;
    }
}
if (!function_exists('v')) {
    function v($a, $k, $d=''){ return esc($a[$k] ?? $d); }
}
if (!function_exists('logo_preview_src')) {
    function logo_preview_src(?string $s): ?string {
        if (!$s) return null;
        return preg_match('~^https?://~',$s) ? $s : base_url($s);
    }
}

// Siapkan array turunan dari JSON bila belum disediakan controller
$faculties_array    = $career['faculties_array']    ?? (isset($career['faculties'])    ? (json_decode($career['faculties'], true) ?: [])    : []);
$programs_array     = $career['programs_array']     ?? (isset($career['programs'])     ? (json_decode($career['programs'], true) ?: [])     : []);
$scholarships_array = $career['scholarships_array'] ?? (isset($career['scholarships']) ? (json_decode($career['scholarships'], true) ?: []) : []);
$contacts_array     = $career['contacts_array']     ?? (isset($career['contacts'])     ? (json_decode($career['contacts'], true) ?: [])     : []);

// Default minimal satu baris
if (empty($faculties_array))    $faculties_array    = [''];
if (empty($programs_array))     $programs_array     = [['name'=>'','degree'=>'']];
if (empty($scholarships_array)) $scholarships_array = [''];
if (empty($contacts_array))     $contacts_array     = [''];

// Logo state
$hasLogo = !empty($career['logo']);
$preview = logo_preview_src($career['logo'] ?? null);

// Tentukan sumber logo default (url|upload), prioritaskan old()
$srcOld  = old('logo_source');
$logoSrc = $srcOld !== null
    ? $srcOld
    : ($hasLogo ? (preg_match('~^https?://~', (string)$career['logo']) ? 'url' : 'upload') : 'url');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">
    <i class="mdi mdi-town-hall me-2"></i>
    <?= $isEdit ? 'Edit Info Universitas' : 'Tambah Info Universitas' ?>
  </h4>
  <a href="<?= site_url('counselor/career-info/universities?tab=universities') ?>" class="btn btn-sm btn-secondary">
    &larr; Kembali
  </a>
</div>

<?php if ($msg = session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= nl2br(esc($msg)) ?></div>
<?php endif; ?>
<?php if ($msg = session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post"
          action="<?= $isEdit
              ? route_to('counselor.university.update', (int)($career['id'] ?? 0))
              : route_to('counselor.university.store') ?>"
          enctype="multipart/form-data">

      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label">Nama Universitas <span class="text-danger">*</span></label>
        <input type="text" name="university_name" class="form-control" required
               value="<?= esc(oldv('university_name', $career['university_name'] ?? '')) ?>">
        <?php if (!empty($errors['university_name'])): ?>
          <div class="text-danger small"><?= esc($errors['university_name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="row">
        <div class="mb-3 col-md-4">
          <label class="form-label">Alias / Singkatan</label>
          <input type="text" name="alias" class="form-control" placeholder="contoh: ITB"
                 value="<?= esc(oldv('alias', $career['alias'] ?? '')) ?>">
          <?php if (!empty($errors['alias'])): ?>
            <div class="text-danger small"><?= esc($errors['alias']) ?></div>
          <?php endif; ?>
        </div>
        <div class="mb-3 col-md-4">
          <label class="form-label">Akreditasi</label>
          <?php
            $accreds = ['', 'Unggul', 'A', 'B', 'C', 'Baik', 'Baik Sekali'];
            $curAcc  = oldv('accreditation', $career['accreditation'] ?? '');
          ?>
          <select name="accreditation" class="form-select">
            <?php foreach ($accreds as $acc): ?>
              <option value="<?= esc($acc) ?>" <?= $curAcc === $acc ? 'selected' : '' ?>>
                <?= $acc === '' ? '- Pilih -' : esc($acc) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['accreditation'])): ?>
            <div class="text-danger small"><?= esc($errors['accreditation']) ?></div>
          <?php endif; ?>
        </div>
        <div class="mb-3 col-md-4">
          <label class="form-label">Lokasi</label>
          <input type="text" name="location" class="form-control" placeholder="contoh: Bandung, Jawa Barat"
                 value="<?= esc(oldv('location', $career['location'] ?? '')) ?>">
          <?php if (!empty($errors['location'])): ?>
            <div class="text-danger small"><?= esc($errors['location']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label class="form-label">Website</label>
          <input type="url" name="website" class="form-control" placeholder="https://..."
                 value="<?= esc(oldv('website', $career['website'] ?? '')) ?>">
          <?php if (!empty($errors['website'])): ?>
            <div class="text-danger small"><?= esc($errors['website']) ?></div>
          <?php endif; ?>
        </div>

        <div class="mb-3 col-md-6">
          <label class="form-label d-block">Logo</label>

          <?php if ($hasLogo && $preview): ?>
            <div class="mb-2 d-flex align-items-center gap-3">
              <img src="<?= esc($preview) ?>" alt="Logo" style="height:48px">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1">
                <label for="remove_logo" class="form-check-label">Hapus logo</label>
              </div>
            </div>
          <?php endif; ?>

          <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="logo_source" id="src_url" value="url" <?= $logoSrc==='url'?'checked':'' ?>>
              <label class="form-check-label" for="src_url">URL</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="logo_source" id="src_upload" value="upload" <?= $logoSrc==='upload'?'checked':'' ?>>
              <label class="form-check-label" for="src_upload">Upload</label>
            </div>
          </div>

          <div id="logo_url_group" class="<?= $logoSrc==='upload'?'d-none':'' ?>">
            <input type="text" name="logo_url" class="form-control"
                   placeholder="https://example.ac.id/logo.png"
                   value="<?= $logoSrc==='url' ? esc(oldv('logo_url', $career['logo'] ?? '')) : '' ?>">
            <div class="form-text">Tempel URL gambar (http/https).</div>
          </div>

          <div id="logo_upload_group" class="<?= $logoSrc==='url'?'d-none':'' ?>">
            <input type="file" name="logo_file" accept="image/*" class="form-control">
            <div class="form-text">PNG/JPG/GIF, maks 2 MB.</div>
            <?php if (!empty($errors['logo_file'])): ?>
              <div class="text-danger small"><?= esc($errors['logo_file']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Deskripsi Singkat</label>
        <textarea name="description" class="form-control" rows="3"><?= esc(oldv('description', $career['description'] ?? '')) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Fakultas</label>
        <div id="faculties-wrapper">
          <?php
            $facOld  = old('faculties');
            $facList = $facOld !== null ? (array)$facOld : $faculties_array;
          ?>
          <?php foreach ($facList as $f): ?>
            <div class="input-group mb-2 faculty-row">
              <input type="text" name="faculties[]" class="form-control" placeholder="contoh: Fakultas Teknik"
                     value="<?= esc($f) ?>">
              <button type="button" class="btn btn-outline-danger btn-remove-faculty">
                <i class="mdi mdi-close"></i>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-faculty">
          <i class="mdi mdi-plus"></i> Tambah Fakultas
        </button>
      </div>

      <div class="mb-3">
        <label class="form-label">Program Studi Utama</label>
        <div id="programs-wrapper">
          <?php
            $pNamesOld = old('program_names');
            if ($pNamesOld !== null) {
                $names   = (array) old('program_names');
                $degrees = (array) old('program_degrees');
                $progArr = [];
                foreach ($names as $i => $nm) {
                    $progArr[] = ['name' => $nm, 'degree' => $degrees[$i] ?? ''];
                }
            } else {
                $progArr = $programs_array;
            }
          ?>
          <?php foreach ($progArr as $p): ?>
            <div class="row g-2 mb-2 program-row">
              <div class="col-md-7">
                <input type="text" name="program_names[]" class="form-control" placeholder="contoh: Informatika"
                       value="<?= esc($p['name'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <input type="text" name="program_degrees[]" class="form-control" placeholder="contoh: S1, D3"
                       value="<?= esc($p['degree'] ?? '') ?>">
              </div>
              <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-outline-danger btn-remove-program">
                  <i class="mdi mdi-close"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-program">
          <i class="mdi mdi-plus"></i> Tambah Program
        </button>
      </div>

      <div class="mb-3">
        <label class="form-label">Informasi Penerimaan</label>
        <textarea name="admission_info" class="form-control" rows="3"
                  placeholder="contoh: Jalur SNBP, SNBT, Mandiri"><?= esc(oldv('admission_info', $career['admission_info'] ?? '')) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Perkiraan Biaya Kuliah</label>
        <textarea name="tuition_range" class="form-control" rows="2"
                  placeholder="contoh: 8-15 juta/semester"><?= esc(oldv('tuition_range', $career['tuition_range'] ?? '')) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Beasiswa</label>
        <div id="scholarships-wrapper">
          <?php
            $schOld = old('scholarships');
            $schArr = $schOld !== null ? (array)$schOld : $scholarships_array;
          ?>
          <?php foreach ($schArr as $s): ?>
            <div class="input-group mb-2 scholarship-row">
              <input type="text" name="scholarships[]" class="form-control" placeholder="contoh: Beasiswa KIP Kuliah"
                     value="<?= esc($s) ?>">
              <button type="button" class="btn btn-outline-danger btn-remove-scholarship">
                <i class="mdi mdi-close"></i>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-scholarship">
          <i class="mdi mdi-plus"></i> Tambah Beasiswa
        </button>
      </div>

      <div class="mb-3">
        <label class="form-label">Kontak</label>
        <div id="contacts-wrapper">
          <?php
            $cOld = old('contacts');
            $cArr = $cOld !== null ? (array)$cOld : $contacts_array;
          ?>
          <?php foreach ($cArr as $c): ?>
            <div class="input-group mb-2 contact-row">
              <input type="text" name="contacts[]" class="form-control" placeholder="contoh: Telp, email, IG, dsb."
                     value="<?= esc($c) ?>">
              <button type="button" class="btn btn-outline-danger btn-remove-contact">
                <i class="mdi mdi-close"></i>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-contact">
          <i class="mdi mdi-plus"></i> Tambah Kontak
        </button>
      </div>

      <!-- Tampilan Status & Publikasi sesuai permintaan -->
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="is_active" class="form-select">
            <?php $val = (int) oldv('is_active', $career['is_active'] ?? 1); ?>
            <option value="1" <?= $val === 1 ? 'selected' : '' ?>>Aktif (ditampilkan ke siswa)</option>
            <option value="0" <?= $val === 0 ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <?php if (!empty($errors['is_active'])): ?>
          <div class="text-danger small"><?= esc($errors['is_active']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label">Publikasi</label>
        <?php $pub = (int) oldv('is_public', $career['is_public'] ?? 0); ?>
        <select name="is_public" class="form-select">
            <option value="0" <?= $pub === 0 ? 'selected' : '' ?>>Private (belum tayang)</option>
            <option value="1" <?= $pub === 1 ? 'selected' : '' ?>>Published (tampil di portal)</option>
        </select>
        <?php if (!empty($errors['is_public'])): ?>
          <div class="text-danger small"><?= esc($errors['is_public']) ?></div>
        <?php endif; ?>
      </div>
      <!-- /Tampilan Status & Publikasi -->

      <?php if ($isEdit): ?>
        <div class="mb-3">
          <label class="form-label">Informasi internal</label>
          <div class="form-text">
            <?php
            $creatorName = trim((string)($career['created_by_name'] ?? ''));

            if ($creatorName !== '') {
                echo 'Dibuat oleh: ' . esc($creatorName);
            } else {
                echo 'Informasi pembuat belum tersedia.';
            }
            ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="text-end">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function addRow(wrapperId, rowClass, html) {
    const wrapper = document.getElementById(wrapperId);
    if (!wrapper) return;
    const row = document.createElement('div');
    row.className = rowClass;
    row.innerHTML = html;
    wrapper.appendChild(row);
  }

  // Toggle sumber logo
  const urlGp = document.getElementById('logo_url_group');
  const upGp  = document.getElementById('logo_upload_group');
  const rUrl  = document.getElementById('src_url');
  const rUp   = document.getElementById('src_upload');
  function updateLogoSource(){
    if (rUrl && rUrl.checked) {
      urlGp && urlGp.classList.remove('d-none');
      upGp && upGp.classList.add('d-none');
    } else {
      upGp && upGp.classList.remove('d-none');
      urlGp && urlGp.classList.add('d-none');
    }
  }
  [rUrl, rUp].forEach(el => el && el.addEventListener('change', updateLogoSource));
  updateLogoSource();

  // Fakultas
  const btnAddFaculty = document.getElementById('btn-add-faculty');
  const facWrapper    = document.getElementById('faculties-wrapper');
  if (btnAddFaculty && facWrapper) {
    btnAddFaculty.addEventListener('click', function () {
      addRow('faculties-wrapper', 'input-group mb-2 faculty-row', `
        <input type="text" name="faculties[]" class="form-control" placeholder="contoh: Fakultas Teknik">
        <button type="button" class="btn btn-outline-danger btn-remove-faculty">
          <i class="mdi mdi-close"></i>
        </button>
      `);
    });
    facWrapper.addEventListener('click', function (e) {
      if (e.target.closest('.btn-remove-faculty')) {
        const row = e.target.closest('.faculty-row');
        if (row && facWrapper.querySelectorAll('.faculty-row').length > 1) row.remove();
      }
    });
  }

  // Program studi
  const btnAddProgram = document.getElementById('btn-add-program');
  const progWrapper   = document.getElementById('programs-wrapper');
  if (btnAddProgram && progWrapper) {
    btnAddProgram.addEventListener('click', function () {
      addRow('programs-wrapper', 'row g-2 mb-2 program-row', `
        <div class="col-md-7">
          <input type="text" name="program_names[]" class="form-control" placeholder="contoh: Informatika">
        </div>
        <div class="col-md-4">
          <input type="text" name="program_degrees[]" class="form-control" placeholder="contoh: S1, D3">
        </div>
        <div class="col-md-1 d-flex align-items-center">
          <button type="button" class="btn btn-outline-danger btn-remove-program">
            <i class="mdi mdi-close"></i>
          </button>
        </div>
      `);
    });
    progWrapper.addEventListener('click', function (e) {
      if (e.target.closest('.btn-remove-program')) {
        const row = e.target.closest('.program-row');
        if (row && progWrapper.querySelectorAll('.program-row').length > 1) row.remove();
      }
    });
  }

  // Beasiswa
  const btnAddScholarship = document.getElementById('btn-add-scholarship');
  const schWrapper        = document.getElementById('scholarships-wrapper');
  if (btnAddScholarship && schWrapper) {
    btnAddScholarship.addEventListener('click', function () {
      addRow('scholarships-wrapper', 'input-group mb-2 scholarship-row', `
        <input type="text" name="scholarships[]" class="form-control" placeholder="contoh: Beasiswa KIP Kuliah">
        <button type="button" class="btn btn-outline-danger btn-remove-scholarship">
          <i class="mdi mdi-close"></i>
        </button>
      `);
    });
    schWrapper.addEventListener('click', function (e) {
      if (e.target.closest('.btn-remove-scholarship')) {
        const row = e.target.closest('.scholarship-row');
        if (row && schWrapper.querySelectorAll('.scholarship-row').length > 1) row.remove();
      }
    });
  }

  // Kontak
  const btnAddContact = document.getElementById('btn-add-contact');
  const cWrapper      = document.getElementById('contacts-wrapper');
  if (btnAddContact && cWrapper) {
    btnAddContact.addEventListener('click', function () {
      addRow('contacts-wrapper', 'input-group mb-2 contact-row', `
        <input type="text" name="contacts[]" class="form-control" placeholder="contoh: Telp, email, IG, dsb.">
        <button type="button" class="btn btn-outline-danger btn-remove-contact">
          <i class="mdi mdi-close"></i>
        </button>
      `);
    });
    cWrapper.addEventListener('click', function (e) {
      if (e.target.closest('.btn-remove-contact')) {
        const row = e.target.closest('.contact-row');
        if (row && cWrapper.querySelectorAll('.contact-row').length > 1) row.remove();
      }
    });
  }
});
</script>

<?= $this->endSection() ?>
