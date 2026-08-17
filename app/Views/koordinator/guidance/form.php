<?php
/**
 * View form layanan BK (seragam semua peran yang mengelola: Koordinator BK & Guru BK).
 * Fitur: Bimbingan/Konseling/Kolaborasi Orang Tua/Kunjungan Rumah/Konferensi Kasus.
 * Fase 4: istilah "Judul/Topik/Masalah" & "Tempat/Lokasi/Alamat".
 * Perbaikan Kedua (Versi 2):
 *  - Siswa/Kelas Sasaran & "Tambah Peserta (dari data)": klik pilihan -> LANGSUNG
 *    jadi chip (tanpa tombol "+ Tambah"); opsi yang sudah dipakai disembunyikan dari
 *    daftar agar tak terpilih dua kali. Pilihan pertama jadi subjek/target representatif.
 *  - Orang Tua & Wali Kelas digabung jadi satu "Tambah Peserta (dari data)".
 *  - Penanggung Jawab/Guru BK: pengguna terpilih tampil di KOTAK CHIP khusus (meniru
 *    Siswa Sasaran), terpisah dari daftar pencarian & teks bantuan. Guru BK terkunci
 *    ke dirinya sendiri; Koordinator BK bebas memilih. Khusus Konferensi Kasus: hanya
 *    Koordinator BK; Guru BK tidak memilih siapa pun (dikosongkan, ditetapkan Koordinator).
 * Field khusus ditampilkan berdasarkan $serviceType.
 *  - Tanda wajib (*): Judul, Penanggung Jawab (kecuali Konferensi Kasus oleh Guru BK),
 *    Tanggal & Jam, Lama Kegiatan, Tempat/Lokasi/Alamat, dan field deskripsi utama
 *    (Ringkasan Materi/Deskripsi Masalah/Ringkasan/Hasil Kunjungan/Kronologi).
 *    Siswa/Kelas Sasaran & peserta TIDAK wajib. Penegakan PIC juga di sisi server.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$detail = is_array($row['detail'] ?? null) ? $row['detail'] : [];
$participants = is_array($row['participants'] ?? null) ? $row['participants'] : [];
$options = is_array($options ?? null) ? $options : [];
$action = (string) ($action ?? current_url());
$routePrefix = (string) ($routePrefix ?? '');
$serviceType = (string) ($serviceType ?? '');
$roleKey = (string) ($roleKey ?? '');
$isEdit = ! empty($row['id']);
$value = static function (string $key, $default = '') use ($row, $detail) {
    return old($key, $row[$key] ?? $detail[$key] ?? $default);
};

$participantName = static function (array $p): string {
    return (string) ($p['participant_student_name'] ?? $p['participant_user_name'] ?? $p['participant_parent_name'] ?? $p['participant_class_name'] ?? $p['manual_name'] ?? '-');
};

$selfId = (int) (session('user_id') ?? 0);
$selfName = trim((string) (session('full_name') ?? '')) ?: 'Saya (akun ini)';
$isGuruBk = ($roleKey === 'guru-bk');
$isKonferensi = ($serviceType === 'Konferensi Kasus');

// Penanggung Jawab: label & daftar pilihan sesuai jenis layanan.
$pjLabel = $isKonferensi ? 'Penanggung Jawab' : 'Guru BK/Penanggung Jawab';
$pjList = $isKonferensi ? ($options['coordinators'] ?? []) : ($options['counselors'] ?? []);
$pjValue = (string) $value('counselor_id', $isGuruBk && ! $isEdit && ! $isKonferensi ? (string) $selfId : '');

// Kumpulkan siswa sasaran dan kelas sasaran dari peserta
$selectedStudents = [];
$selectedClasses = [];
$extraParticipants = [];

foreach ($participants as $p) {
    if ($p['participant_type'] === 'student') {
        $selectedStudents[] = [
            'id' => (int) $p['participant_student_id'],
            'text' => trim((string) ($p['participant_student_name'] ?? 'Siswa #' . $p['participant_student_id']))
        ];
    } elseif ($p['participant_type'] === 'class') {
        $selectedClasses[] = [
            'id' => (int) $p['participant_class_id'],
            'text' => trim((string) ($p['participant_class_name'] ?? 'Kelas #' . $p['participant_class_id']))
        ];
    } else {
        $extraParticipants[] = $p;
    }
}

$renderChip = static function (string $name, int $id, string $text) {
    return sprintf(
        '<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;">' .
        '<span>%s</span>' .
        '<input type="hidden" name="%s" value="%d">' .
        '<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>' .
        '</span>',
        esc($text),
        esc($name, 'attr'),
        $id
    );
};
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0 text-dark"><?= esc($title ?? 'Form Layanan BK') ?></h4>
      <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('koordinator/dashboard') ?>">Koordinator</a></li>
          <li class="breadcrumb-item"><a href="<?= site_url($routePrefix) ?>"><?= esc($serviceType) ?></a></li>
          <li class="breadcrumb-item active"><?= ! empty($row['id']) ? 'Edit' : 'Tambah' ?></li>
        </ol>
        <a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?= esc($action, 'attr') ?>">
  <?= csrf_field() ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark">Data Utama</h5>
          <div class="mb-3">
            <label class="form-label text-dark">Judul/Topik/Masalah <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required value="<?= esc($value('title')) ?>">
          </div>

          <!-- Siswa Sasaran: klik pilihan -> langsung jadi chip (boleh lebih dari satu). -->
          <div class="mb-3 js-multi" data-name="participant_student_ids[]" id="studentPickerContainer">
            <label class="form-label text-dark">Siswa Sasaran <span class="text-dark fw-normal">(dari data &mdash; boleh lebih dari satu)</span></label>
            <div class="js-chips border rounded p-2 mb-2 bg-light">
              <?php foreach ($selectedStudents as $stu): ?>
                <?= $renderChip('participant_student_ids[]', $stu['id'], $stu['text']) ?>
              <?php endforeach; ?>
              <span class="text-dark js-chip-empty"<?= !empty($selectedStudents) ? ' style="display:none;"' : '' ?>>Belum ada siswa dipilih.</span>
            </div>
            <select class="form-select select2-search js-picker" id="studentPicker">
              <option value="">Ketik untuk mencari siswa&hellip;</option>
              <?php foreach (($options['students'] ?? []) as $student): ?>
                <option value="<?= esc((string) $student['id']) ?>">
                  <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-dark d-block mt-1">Klik nama siswa pada daftar &mdash; otomatis masuk ke kotak di atas. Bisa lebih dari satu; nama yang sudah dipilih tidak muncul lagi.</small>
          </div>

          <!-- Kelas Sasaran: pola yang sama dengan Siswa Sasaran. -->
          <div class="mb-3 js-multi" data-name="participant_class_ids[]" id="classPickerContainer">
            <label class="form-label text-dark">Kelas Sasaran <span class="text-dark fw-normal">(dari data &mdash; boleh lebih dari satu)</span></label>
            <div class="js-chips border rounded p-2 mb-2 bg-light">
              <?php foreach ($selectedClasses as $cls): ?>
                <?= $renderChip('participant_class_ids[]', $cls['id'], $cls['text']) ?>
              <?php endforeach; ?>
              <span class="text-dark js-chip-empty"<?= !empty($selectedClasses) ? ' style="display:none;"' : '' ?>>Belum ada kelas dipilih.</span>
            </div>
            <select class="form-select select2-search js-picker" id="classPicker">
              <option value="">Ketik untuk mencari kelas&hellip;</option>
              <?php foreach (($options['classes'] ?? []) as $class): ?>
                <option value="<?= esc((string) $class['id']) ?>"><?= esc($class['class_name'] ?? '-') ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-dark d-block mt-1">Klik nama kelas pada daftar &mdash; otomatis masuk ke kotak di atas. Bisa lebih dari satu; kelas yang sudah dipilih tidak muncul lagi.</small>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark"><?= esc($pjLabel) ?><?php if (! ($isGuruBk && $isKonferensi)): ?> <span class="text-danger">*</span><?php endif; ?></label>
              <?php
                // Nama PJ yang sedang terpilih (untuk chip awal). Cari di daftar pilihan;
                // jika tak ada, pakai nama akun sendiri / nama dari record.
                $pjSelectedId = (int) $pjValue;
                $pjSelectedName = '';
                foreach ($pjList as $u) {
                    if ((int) ($u['id'] ?? 0) === $pjSelectedId) { $pjSelectedName = (string) ($u['full_name'] ?? ''); break; }
                }
                if ($pjSelectedName === '' && $pjSelectedId > 0) {
                    $pjSelectedName = ($pjSelectedId === $selfId) ? $selfName : (string) ($row['counselor_name'] ?? ('Pengguna #' . $pjSelectedId));
                }
              ?>
              <?php if ($isGuruBk && $isKonferensi): ?>
                <?php // Guru BK tidak menetapkan PJ Konferensi Kasus (hanya Koordinator BK). ?>
                <?php if ($isEdit && $pjSelectedId > 0): ?>
                  <input type="hidden" name="counselor_id" value="<?= esc((string) $pjSelectedId, 'attr') ?>">
                <?php endif; ?>
                <div class="js-chips border rounded p-2 mb-2 bg-light">
                  <?php if ($pjSelectedId > 0): ?>
                    <span class="badge bg-secondary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2" style="font-size:.8rem;"><span><?= esc($pjSelectedName) ?></span></span>
                  <?php else: ?>
                    <span class="text-dark">Ditetapkan oleh Koordinator BK.</span>
                  <?php endif; ?>
                </div>
                <small class="text-dark d-block mt-1">Penanggung jawab Konferensi Kasus hanya Koordinator BK. Anda tetap dapat mengisi data lainnya.</small>
              <?php elseif ($isGuruBk): ?>
                <?php
                  $lockVal  = ($isEdit && $pjSelectedId > 0) ? $pjSelectedId : $selfId;
                  $lockName = ($lockVal === $selfId) ? $selfName : ($pjSelectedName !== '' ? $pjSelectedName : $selfName);
                ?>
                <input type="hidden" name="counselor_id" value="<?= esc((string) $lockVal, 'attr') ?>">
                <div class="js-chips border rounded p-2 mb-2 bg-light">
                  <span class="badge bg-secondary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2" style="font-size:.8rem;"><span><?= esc($lockName) ?></span></span>
                </div>
                <small class="text-dark d-block mt-1">Guru BK menugaskan dirinya sendiri sebagai penanggung jawab.</small>
              <?php else: ?>
                <?php
                  // Koordinator BK bebas memilih. Bawaan: akun sendiri (kecuali sudah ada nilai).
                  $pjChipId   = $pjSelectedId > 0 ? $pjSelectedId : $selfId;
                  $pjChipName = '';
                  foreach ($pjList as $u) {
                      if ((int) ($u['id'] ?? 0) === $pjChipId) { $pjChipName = (string) ($u['full_name'] ?? ''); break; }
                  }
                  if ($pjChipName === '') { $pjChipName = ($pjChipId === $selfId) ? $selfName : ($pjSelectedName !== '' ? $pjSelectedName : ('Pengguna #' . $pjChipId)); }
                ?>
                <div class="js-single" data-name="counselor_id">
                  <input type="hidden" name="counselor_id" value="<?= esc($pjChipId > 0 ? (string) $pjChipId : '', 'attr') ?>">
                  <div class="js-chips border rounded p-2 mb-2 bg-light">
                    <?php if ($pjChipId > 0): ?>
                      <span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;">
                        <span><?= esc($pjChipName) ?></span>
                        <button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>
                      </span>
                    <?php endif; ?>
                    <span class="text-dark js-chip-empty"<?= $pjChipId > 0 ? ' style="display:none;"' : '' ?>>Belum ada penanggung jawab dipilih.</span>
                  </div>
                  <select class="form-select select2-search js-picker-single">
                    <option value="">Ketik untuk mencari&hellip;</option>
                    <?php foreach ($pjList as $user): ?>
                      <option value="<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-dark d-block mt-1"><?= $isKonferensi ? 'Hanya akun Koordinator BK. Bawaan: akun Anda sendiri.' : 'Bawaan: akun Anda sendiri. Boleh memilih Koordinator BK/Guru BK lain.' ?></small>
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Draft','Dijadwalkan','Berlangsung','Selesai','Dibatalkan','Perlu Tindak Lanjut'] as $status): ?>
                  <option value="<?= esc($status) ?>" <?= $value('status', 'Dijadwalkan') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Tanggal &amp; Jam Kegiatan <span class="text-danger">*</span></label>
              <input type="datetime-local" name="scheduled_at" class="form-control" required value="<?= esc(str_replace(' ', 'T', substr((string) $value('scheduled_at'), 0, 16))) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-dark">Lama Kegiatan (menit) <span class="text-danger">*</span></label>
              <input type="number" name="duration_minutes" class="form-control" min="1" required value="<?= esc($value('duration_minutes', 60)) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label text-dark">Tempat/Lokasi/Alamat <span class="text-danger">*</span></label>
            <textarea name="location" class="form-control" rows="2" required placeholder="Contoh: Ruang BK, ruang kelas, atau alamat lengkap"><?= esc($value('location')) ?></textarea>
          </div>

          <?php if ($serviceType === 'Bimbingan'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Jenis Bimbingan</label>
              <select name="guidance_type" class="form-select" id="guidanceType">
                <?php foreach (['Kelompok','Klasikal','Kelas Besar'] as $type): ?>
                  <option value="<?= esc($type) ?>" <?= $value('guidance_type', 'Klasikal') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Ringkasan Materi <span class="text-danger">*</span></label>
              <textarea name="summary" class="form-control" rows="4" required><?= esc($value('summary')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Konseling'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Jenis Konseling</label>
              <select name="counseling_type" class="form-select">
                <?php foreach (['Individu','Kelompok'] as $type): ?>
                  <option value="<?= esc($type) ?>" <?= $value('counseling_type', 'Individu') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Deskripsi Masalah <span class="text-danger">*</span></label>
              <textarea name="problem_description" class="form-control" rows="4" required><?= esc($value('problem_description')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Rencana Tindak Lanjut</label>
              <textarea name="follow_up_plan" class="form-control" rows="3"><?= esc($value('follow_up_plan')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Kolaborasi Orang Tua'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Ringkasan dan Tindak Lanjut <span class="text-danger">*</span></label>
              <textarea name="summary" class="form-control" rows="4" required><?= esc($value('summary')) ?></textarea>
            </div>
            <small class="text-dark d-block mb-2"><i class="mdi mdi-information-outline me-1"></i>Pilih Orang Tua yang hadir pada kartu <strong>Peserta Tambahan dan Catatan</strong> di samping (dari data atau tulis manual). Mereka otomatis tercatat sebagai peserta dan bisa diatur kehadirannya.</small>
          <?php elseif ($serviceType === 'Kunjungan Rumah'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Hasil Kunjungan <span class="text-danger">*</span></label>
              <textarea name="visit_result" class="form-control" rows="4" required><?= esc($value('visit_result')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Tindak Lanjut</label>
              <textarea name="follow_up" class="form-control" rows="3"><?= esc($value('follow_up')) ?></textarea>
            </div>
            <small class="text-dark d-block mb-2"><i class="mdi mdi-information-outline me-1"></i>Alamat kunjungan diisi pada kolom <strong>Tempat/Lokasi/Alamat</strong> di atas. Pilih Orang Tua &amp; Wali Kelas yang ditemui pada kartu <strong>Peserta Tambahan dan Catatan</strong>.</small>
          <?php elseif ($serviceType === 'Konferensi Kasus'): ?>
            <div class="mb-3">
              <label class="form-label text-dark">Kronologi/Ringkasan Masalah <span class="text-danger">*</span></label>
              <textarea name="chronology" class="form-control" rows="3" required><?= esc($value('chronology')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Pembahasan dan Keputusan</label>
              <textarea name="decision_summary" class="form-control" rows="4"><?= esc($value('decision_summary')) ?></textarea>
            </div>
            <small class="text-dark d-block mb-2"><i class="mdi mdi-information-outline me-1"></i>Pilih Orang Tua &amp; Wali Kelas yang hadir pada kartu <strong>Peserta Tambahan dan Catatan</strong> (dari data atau tulis manual).</small>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($isEdit && $extraParticipants): ?>
      <!-- Daftar peserta yang sudah tercatat (bisa dihapus saat edit) -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark"><i class="mdi mdi-account-multiple me-2"></i>Peserta/Undangan Tercatat</h5>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light">
                <tr><th>Nama</th><th>Peran</th><th>Kehadiran</th><th class="text-end">Aksi</th></tr>
              </thead>
              <tbody>
                <?php foreach ($extraParticipants as $p): ?>
                  <tr>
                    <td class="text-dark"><?= esc($participantName($p)) ?></td>
                    <td class="text-dark"><?= esc($p['role_in_session'] ?? '-') ?></td>
                    <td class="text-dark">
                      <select name="attendance[<?= (int) $p['id'] ?>]" class="form-select form-select-sm js-attendance-select" data-id="<?= (int) $p['id'] ?>">
                        <?php foreach (['Hadir','Izin','Sakit','Alpha','Belum Hadir'] as $status): ?>
                          <option value="<?= esc($status) ?>" <?= ($p['attendance_status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td class="text-end">
                      <?php if (($p['participant_type'] ?? '') !== 'student'): ?>
                        <button type="button" class="btn btn-sm btn-danger js-del-participant"
                                data-id="<?= (int) $p['id'] ?>" title="Hapus peserta" data-bs-toggle="tooltip">
                          <i class="mdi mdi-delete"></i>
                        </button>
                      <?php else: ?>
                        <span class="badge bg-secondary text-white" title="Hapus siswa dari kotak Siswa Sasaran di atas" data-bs-toggle="tooltip">Hapus via Sasaran</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <small class="text-dark d-block mt-2">Ubah kehadiran otomatis akan tersimpan saat Anda menekan tombol "Simpan" di bawah.</small>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark"><i class="mdi mdi-account-plus me-2"></i>Peserta Tambahan dan Catatan</h5>

          <!-- Peserta dari data (Orang Tua / Wali Kelas) digabung: klik pilihan -> langsung jadi chip. -->
          <div class="mb-3 js-multi" data-name="auto">
            <label class="form-label text-dark">Tambah Peserta (dari data)</label>
            <div class="js-chips border rounded p-2 mb-2 bg-light">
              <span class="text-dark js-chip-empty">Belum ada peserta dipilih.</span>
            </div>
            <select class="form-select select2-search js-picker">
              <option value="">Cari Orang Tua atau Wali Kelas&hellip;</option>
              <optgroup label="Orang Tua">
                <?php foreach (($options['parents'] ?? []) as $user): ?>
                  <option value="parent:<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Wali Kelas">
                <?php foreach (($options['homeroom_teachers'] ?? []) as $user): ?>
                  <option value="user:<?= esc((string) $user['id']) ?>"><?= esc($user['full_name'] ?? '-') ?></option>
                <?php endforeach; ?>
              </optgroup>
            </select>
            <small class="text-dark d-block mt-1">Klik nama Orang Tua atau Wali Kelas pada daftar &mdash; otomatis masuk ke kotak di atas. Bisa lebih dari satu; nama yang sudah dipilih tidak muncul lagi.</small>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Peserta Tambahan (manual)</label>
            <div id="manualParticipants">
              <div class="input-group mb-2 manual-participant-row">
                <input type="text" name="manual_participants[]" class="form-control" placeholder="Nama - Peran (mis: Nama Wali Kelas - Wali Kelas)">
                <button type="button" class="btn btn-outline-danger remove-manual" title="Hapus baris"><i class="mdi mdi-minus"></i></button>
              </div>
            </div>
            <button type="button" id="addManualParticipant" class="btn btn-sm btn-primary"><i class="mdi mdi-plus me-1"></i> Tambah peserta manual</button>
            <small class="text-dark d-block mt-1">Untuk pihak yang belum terdaftar di data. Format: Nama - Peran.</small>
          </div>

          <div class="mb-3">
            <label class="form-label text-dark">Catatan <?= $isEdit ? 'Baru' : 'Awal' ?></label>
            <textarea name="initial_note" class="form-control" rows="4"><?= $isEdit ? '' : esc($value('initial_note')) ?></textarea>
          </div>

          <div class="mb-3 border rounded p-2">
            <div class="form-check form-switch">
              <input type="hidden" name="visible_to_homeroom" value="0">
              <input class="form-check-input" type="checkbox" name="visible_to_homeroom" id="vh" value="1" <?= (string) $value('visible_to_homeroom') === '1' ? 'checked' : '' ?>>
              <label class="form-check-label text-dark" for="vh">Boleh dilihat Wali Kelas terkait</label>
            </div>
            <small class="text-dark">Bila dimatikan, Wali Kelas hanya melihat jadwal &mdash; bukan catatan rinci. Bawaan: mati.</small>
          </div>

          <button class="btn btn-primary w-100" type="submit"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
        </div>
      </div>
    </div>
  </div>
</form>

<?php if ($isEdit): ?>
<form id="delParticipantForm" method="post" action="<?= site_url($routePrefix . '/participant-delete/0') ?>" class="d-none">
  <?= csrf_field() ?>
  <input type="hidden" name="record_id" value="<?= (int) $row['id'] ?>">
  <input type="hidden" name="back" value="edit">
</form>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(function () {
    if (window.jQuery && $.fn.select2) {
      $('.select2-search').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: 'Ketik untuk mencari...' });
    }

    // ---- Pemilihan banyak data: klik pilihan -> LANGSUNG jadi "chip" (tanpa tombol).
    // Opsi yang sudah dipakai disembunyikan dari daftar agar tak terpilih dua kali;
    // muncul lagi saat chip-nya dihapus. ----
    function pickerOptionByValue($picker, val) {
      return $picker.find('option').filter(function () { return this.value === String(val); });
    }

    function detachOption($chip, $picker, val) {
      var $opt = pickerOptionByValue($picker, val);
      if ($opt.length) {
        $chip.data('opt', $opt);
        $chip.data('optParent', $opt.parent());
        $opt.detach();
      }
    }

    function restoreOption($chip) {
      var $opt = $chip.data('opt');
      var $parent = $chip.data('optParent');
      if ($opt && $parent && $parent.length) { $parent.append($opt); }
    }

    function addChip($widget, pickerVal, text) {
      if (!pickerVal) { return null; }
      var name = $widget.data('name');
      var hiddenName, hiddenVal;
      if (name === 'auto') {
        var parts = String(pickerVal).split(':');
        hiddenName = (parts[0] === 'parent') ? 'participant_parent_ids[]' : 'participant_user_ids[]';
        hiddenVal = parts[1];
      } else {
        hiddenName = name;
        hiddenVal = pickerVal;
      }

      var $chips = $widget.find('.js-chips');
      var dup = false;
      $chips.find('input[type=hidden]').each(function () {
        if (this.name === hiddenName && this.value === String(hiddenVal)) { dup = true; }
      });
      if (dup) { return null; }

      var $chip = $('<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;"></span>');
      $('<span></span>').text(text).appendTo($chip);
      $('<input type="hidden">').attr('name', hiddenName).val(hiddenVal).appendTo($chip);
      $('<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>').appendTo($chip);
      $widget.find('.js-chip-empty').hide().before($chip);
      return $chip;
    }

    // Saat membuka form Edit: sembunyikan dari daftar opsi yang chip-nya sudah tampil
    // (mis. siswa/kelas sasaran representatif yang sudah tercatat).
    $('.js-multi').each(function () {
      var $widget = $(this);
      if ($widget.data('name') === 'auto') { return; }
      var $picker = $widget.find('.js-picker');
      $widget.find('.js-chip').each(function () {
        var $chip = $(this);
        detachOption($chip, $picker, $chip.find('input[type=hidden]').val());
      });
    });

    // Pilih dari daftar -> langsung jadi chip, lalu sembunyikan opsinya.
    $('.js-multi .js-picker').on('select2:select', function () {
      var $widget = $(this).closest('.js-multi');
      var $picker = $(this);
      var pickerVal = $picker.val();
      var text = $.trim($picker.find('option:selected').text());
      var $chip = addChip($widget, pickerVal, text);
      $picker.val('').trigger('change');
      if ($chip) { detachOption($chip, $picker, pickerVal); }
      
    });

    // Hapus chip -> kembalikan opsinya ke daftar pilihan.
    $('.js-multi').on('click', '.js-chip-remove', function () {
      var $widget = $(this).closest('.js-multi');
      var $chip = $(this).closest('.js-chip');
      restoreOption($chip);
      $chip.remove();
      if ($widget.find('.js-chip').length === 0) {
        $widget.find('.js-chip-empty').show();
      }
      
    });

    // Panggil saat inisialisasi
    

    // ---- Penanggung Jawab (pilihan TUNGGAL): tampil di kotak chip yang sama. ----
    // Memilih yang baru mengganti chip lama; bisa dikosongkan lewat tombol hapus.
    $('.js-single .js-picker-single').on('select2:select', function () {
      var $widget = $(this).closest('.js-single');
      var val = $(this).val();
      var text = $.trim($(this).find('option:selected').text());
      if (val) {
        $widget.find('input[type=hidden][name="counselor_id"]').val(val);
        $widget.find('.js-chips .js-chip').remove();
        var $chip = $('<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 me-1 mb-1 p-2 js-chip" style="font-size:.8rem;"></span>');
        $('<span></span>').text(text).appendTo($chip);
        $('<button type="button" class="btn-close btn-close-white js-chip-remove" aria-label="Hapus" style="font-size:.55rem;"></button>').appendTo($chip);
        $widget.find('.js-chip-empty').hide().before($chip);
      }
      $(this).val('').trigger('change');
    });

    $('.js-single').on('click', '.js-chip-remove', function () {
      var $widget = $(this).closest('.js-single');
      $widget.find('input[type=hidden][name="counselor_id"]').val('');
      $(this).closest('.js-chip').remove();
      $widget.find('.js-chip-empty').show();
    });

    // ---- Peserta manual (baris dinamis) ----
    $('#addManualParticipant').on('click', function () {
      var row = $(
        '<div class="input-group mb-2 manual-participant-row">' +
        '<input type="text" name="manual_participants[]" class="form-control" placeholder="Nama - Peran">' +
        '<button type="button" class="btn btn-outline-danger remove-manual" title="Hapus baris"><i class="mdi mdi-minus"></i></button>' +
        '</div>'
      );
      $('#manualParticipants').append(row);
    });
    $('#manualParticipants').on('click', '.remove-manual', function () {
      $(this).closest('.manual-participant-row').remove();
    });

    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) { return new bootstrap.Tooltip(el); });

        // ---- Mutual Exclusivity: Siswa Sasaran vs Kelas Sasaran ----
    function updatePickerExclusivity() {
      var hasClass = $('#classPickerContainer .js-chip').length > 0;
      var hasStudent = $('#studentPickerContainer .js-chip').length > 0;

      if (hasClass) {
        $('#studentPicker').prop('disabled', true).trigger('change');
        $('#studentPickerContainer').css('opacity', '0.5');
      } else {
        $('#studentPicker').prop('disabled', false).trigger('change');
        $('#studentPickerContainer').css('opacity', '1');
      }

      if (hasStudent) {
        $('#classPicker').prop('disabled', true).trigger('change');
        $('#classPickerContainer').css('opacity', '0.5');
      } else {
        $('#classPicker').prop('disabled', false).trigger('change');
        $('#classPickerContainer').css('opacity', '1');
      }
    }

    // ---- Client-side Sync of Student Chips with the Attendance Table ----
    function escapeHtml(str) {
      return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function syncStudentChipsWithTable() {
      var $table = $('.table-responsive table');
      if (!$table.length) return;
      var $tbody = $table.find('tbody');

      // Gather current selected student IDs
      var selectedIds = [];
      $('#studentPickerContainer').find('.js-chip input[type=hidden]').each(function() {
        selectedIds.push(String(this.value));
      });

      // Remove any student rows not currently in the selected IDs list
      $tbody.find('tr[data-participant-type="student"]').each(function() {
        var sid = String($(this).data('student-id'));
        if (selectedIds.indexOf(sid) === -1) {
          $(this).remove();
        }
      });

      // Add rows for new student IDs
      selectedIds.forEach(function(sid) {
        var $existingRow = $tbody.find('tr[data-student-id="' + sid + '"]');
        if (!$existingRow.length) {
          var name = '';
          $('#studentPickerContainer').find('.js-chip input[type=hidden][value="' + sid + '"]').each(function() {
            name = $(this).closest('.js-chip').find('span').text();
          });

          var rowHtml = 
            '<tr data-participant-type="student" data-student-id="' + sid + '">' +
            '  <td class="text-dark">' + escapeHtml(name) + '</td>' +
            '  <td class="text-dark">Siswa terkait</td>' +
            '  <td class="text-dark">' +
            '    <select name="student_attendance[' + sid + ']" class="form-select form-select-sm js-attendance-select" data-id="' + sid + '">' +
            '      <option value="Hadir" selected>Hadir</option>' +
            '      <option value="Izin">Izin</option>' +
            '      <option value="Sakit">Sakit</option>' +
            '      <option value="Alpha">Alpha</option>' +
            '      <option value="Belum Hadir">Belum Hadir</option>' +
            '    </select>' +
            '  </td>' +
            '  <td class="text-end">' +
            '    <span class="badge bg-secondary text-white" title="Hapus siswa dari kotak Siswa Sasaran di atas" data-bs-toggle="tooltip">Hapus via Sasaran</span>' +
            '  </td>' +
            '</tr>';

          $tbody.append(rowHtml);
        }
      });

      // Manage empty placeholder row
      if ($tbody.find('tr:not(#emptyParticipantPlaceholder)').length === 0) {
        $('#emptyParticipantPlaceholder').show();
      } else {
        $('#emptyParticipantPlaceholder').hide();
      }

      // Re-initialize tooltips
      [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) { return new bootstrap.Tooltip(el); });
    }

    var observer = new MutationObserver(function() {
      updatePickerExclusivity();
      syncStudentChipsWithTable();
    });

    var classChips = document.querySelector('#classPickerContainer .js-chips');
    if (classChips) {
      observer.observe(classChips, { childList: true, subtree: true });
    }

    var studentChips = document.querySelector('#studentPickerContainer .js-chips');
    if (studentChips) {
      observer.observe(studentChips, { childList: true, subtree: true });
    }

    // Run on load
    updatePickerExclusivity();
    syncStudentChipsWithTable();

    var f = document.getElementById('delParticipantForm');
    if (f) {
      var base = '<?= site_url($routePrefix . '/participant-delete/') ?>';
      $('.js-del-participant').on('click', function () {
        if (!confirm('Hapus peserta ini?')) return;
        f.action = base + $(this).data('id');
        f.submit();
      });
    }
  });
</script>
<?= $this->endSection() ?>
