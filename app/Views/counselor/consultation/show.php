<?php
/**
 * View detail Konsultasi & Pengaduan (seragam semua peran).
 * Menampilkan isi laporan, subjek (siswa) terkait, lampiran bukti,
 * pengaturan privasi, dan form tinjauan untuk peran BK.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$options = is_array($options ?? null) ? $options : [];
$subjects = is_array($subjects ?? null) ? $subjects : [];
$attachments = is_array($attachments ?? null) ? $attachments : [];
$routePrefix = (string) ($routePrefix ?? '');
$me = (int) (session('user_id') ?? 0);

$statusColors = [
    'Diajukan' => 'secondary', 'Ditinjau' => 'info', 'Diterima' => 'primary',
    'Dijadwalkan' => 'warning', 'Selesai' => 'success', 'Ditolak' => 'danger', 'Diarsipkan' => 'dark',
];
$statusVal = $row['status'] ?? '-';
?>
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0 text-dark"><?= esc($row['title'] ?? 'Konsultasi & Pengaduan') ?></h4>
        <p class="text-dark mb-0"><?= esc($row['request_type'] ?? '-') ?> &middot; <span class="badge bg-<?= esc($statusColors[$statusVal] ?? 'secondary', 'attr') ?>"><?= esc($statusVal) ?></span></p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a>
        <?php if (! empty($canSubmit) && (int) ($row['reporter_user_id'] ?? 0) === $me && in_array($statusVal, ['Diajukan','Ditinjau'], true)): ?>
          <a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-primary">Edit</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php foreach (['success' => 'check-circle', 'error' => 'alert-circle'] as $type => $icon): ?>
  <?php if (session()->getFlashdata($type)): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
      <i class="mdi mdi-<?= $icon ?> me-2"></i><?= esc(session()->getFlashdata($type)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark">Isi Laporan/Konsultasi</h5>
        <p class="text-dark"><?= nl2br(esc($row['description'] ?? '-')) ?></p>
        <dl class="row mb-0 text-dark">
          <dt class="col-md-4">Pelapor</dt><dd class="col-md-8"><?= esc($row['reporter_name'] ?? '-') ?></dd>
          <dt class="col-md-4">Waktu</dt><dd class="col-md-8"><?= esc($row['occurred_at'] ?? '-') ?></dd>
          <dt class="col-md-4">Tempat/Lokasi/Alamat</dt><dd class="col-md-8"><?= nl2br(esc($row['location'] ?? '-')) ?></dd>
          <dt class="col-md-4">Prioritas</dt><dd class="col-md-8"><?= esc($row['priority'] ?? '-') ?></dd>
          <dt class="col-md-4">Penanggung Jawab</dt><dd class="col-md-8"><?= esc($row['assigned_to_name'] ?? '-') ?></dd>
        </dl>
      </div>
    </div>

    <!-- Siswa/Pihak Terkait -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark"><i class="mdi mdi-account-multiple me-2"></i>Siswa/Pihak Terkait</h5>
        <?php if (! empty($subjects)): ?>
          <ul class="list-group">
            <?php foreach ($subjects as $sub): ?>
              <li class="list-group-item text-dark">
                <?php if (! empty($sub['student_id'])): ?>
                  <i class="mdi mdi-account me-1"></i><?= esc($sub['student_name'] ?? '-') ?>
                  <?php if (! empty($sub['class_name'])): ?><span class="badge bg-primary ms-1"><?= esc($sub['class_name']) ?></span><?php endif; ?>
                  <?php if (! empty($sub['nisn'])): ?><small class="text-dark ms-1">(NISN <?= esc($sub['nisn']) ?>)</small><?php endif; ?>
                <?php else: ?>
                  <i class="mdi mdi-account-outline me-1"></i><?= esc($sub['manual_name'] ?? '-') ?> <span class="badge bg-light text-dark border">manual</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-dark mb-0"><?= esc($row['student_name'] ?? $row['subject_other_name'] ?? 'Tidak ada siswa terkait.') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Lampiran/Bukti -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark"><i class="mdi mdi-paperclip me-2"></i>Lampiran/Bukti</h5>
        <?php if (! empty($attachments)): ?>
          <ul class="list-group">
            <?php foreach ($attachments as $att): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="<?= site_url($routePrefix . '/attachment/' . (int) $att['id']) ?>" class="text-dark"><i class="mdi mdi-file me-1"></i><?= esc($att['file_name'] ?: $att['file_path']) ?></a>
                <?php if ((int) ($att['uploaded_by'] ?? 0) === $me): ?>
                  <form method="post" action="<?= site_url($routePrefix . '/attachment-delete/' . (int) $att['id']) ?>" onsubmit="return confirm('Hapus lampiran ini?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus lampiran"><i class="mdi mdi-delete"></i></button>
                  </form>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-dark mb-0">Tidak ada lampiran.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Ringkasan privasi -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3 text-dark"><i class="mdi mdi-shield-lock me-2"></i>Privasi</h5>
        <ul class="list-unstyled mb-0 text-dark">
          <li><i class="mdi <?= ! empty($row['visible_to_homeroom']) ? 'mdi-check-circle text-success' : 'mdi-close-circle text-danger' ?> me-1"></i> Wali Kelas <?= ! empty($row['visible_to_homeroom']) ? 'boleh melihat' : 'tidak melihat' ?></li>
          <li><i class="mdi <?= ! empty($row['visible_to_parent']) ? 'mdi-check-circle text-success' : 'mdi-close-circle text-danger' ?> me-1"></i> Orang Tua <?= ! empty($row['visible_to_parent']) ? 'boleh melihat' : 'tidak melihat' ?></li>
          <li><i class="mdi <?= ! empty($row['visible_to_student']) ? 'mdi-check-circle text-success' : 'mdi-close-circle text-danger' ?> me-1"></i> Siswa <?= ! empty($row['visible_to_student']) ? 'boleh melihat' : 'tidak melihat' ?></li>
        </ul>
      </div>
    </div>

    <?php if (! empty($canReview)): ?>
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3 text-dark">Tinjauan BK</h5>
          <form method="post" action="<?= site_url($routePrefix . '/review/' . (int) $row['id']) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label text-dark">Status</label>
              <select name="status" class="form-select">
                <?php foreach (array_keys($statusColors) as $status): ?>
                  <option value="<?= esc($status) ?>" <?= ($row['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label text-dark">Tugaskan ke</label>
              <select name="assigned_to_user_id" class="form-select">
                <option value="">Belum ditugaskan</option>
                <?php foreach (($options['counselors'] ?? []) as $user): ?>
                  <option value="<?= esc((string) $user['id']) ?>" <?= (string) ($row['assigned_to_user_id'] ?? '') === (string) $user['id'] ? 'selected' : '' ?>>
                    <?= esc($user['full_name'] ?? '-') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-check form-switch mb-2">
              <input type="hidden" name="visible_to_homeroom" value="0">
              <input class="form-check-input" type="checkbox" name="visible_to_homeroom" value="1" id="rvh" <?= ! empty($row['visible_to_homeroom']) ? 'checked' : '' ?>>
              <label class="form-check-label text-dark" for="rvh">Boleh dilihat Wali Kelas</label>
            </div>
            <div class="form-check form-switch mb-2">
              <input type="hidden" name="visible_to_parent" value="0">
              <input class="form-check-input" type="checkbox" name="visible_to_parent" value="1" id="rvp" <?= ! empty($row['visible_to_parent']) ? 'checked' : '' ?>>
              <label class="form-check-label text-dark" for="rvp">Boleh dilihat Orang Tua</label>
            </div>
            <div class="form-check form-switch mb-3">
              <input type="hidden" name="visible_to_student" value="0">
              <input class="form-check-input" type="checkbox" name="visible_to_student" value="1" id="rvs" <?= ! empty($row['visible_to_student']) ? 'checked' : '' ?>>
              <label class="form-check-label text-dark" for="rvs">Boleh dilihat Siswa</label>
            </div>
            <button class="btn btn-primary w-100">Simpan Tinjauan</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>
