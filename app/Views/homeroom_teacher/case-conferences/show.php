<?php
/**
 * View detail per peran fitur layanan BK.
 * Menampilkan detail, peserta/undangan, catatan, dan tindak lanjut sesuai
 * hak akses yang sudah disaring di service.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$detail = is_array($row['detail'] ?? null) ? $row['detail'] : [];
$participants = is_array($row['participants'] ?? null) ? $row['participants'] : [];
$notes = is_array($row['notes'] ?? null) ? $row['notes'] : [];
$routePrefix = (string) ($routePrefix ?? '');
$canManage = ! empty($canManage);
$participantName = static function (array $p): string {
    return (string) ($p['participant_student_name'] ?? $p['participant_user_name'] ?? $p['participant_parent_name'] ?? $p['participant_class_name'] ?? $p['manual_name'] ?? '-');
};
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0"><?= esc($row['title'] ?? $title ?? 'Detail Layanan BK') ?></h4>
        <p class="text-muted mb-0"><?= esc($serviceType ?? '-') ?> - <?= esc($roleLabel ?? 'Pengguna') ?></p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a>
        <?php if ($canManage): ?>
          <a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-primary">Edit</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Informasi Layanan</h5>
        <div class="row g-3">
          <div class="col-md-6"><div class="text-muted">Siswa</div><div class="fw-semibold"><?= esc($row['student_name'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Kelas</div><div class="fw-semibold"><?= esc($row['class_name'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Guru BK</div><div class="fw-semibold"><?= esc($row['counselor_name'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Jadwal</div><div class="fw-semibold"><?= esc($row['scheduled_at'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Tempat</div><div class="fw-semibold"><?= esc($row['location'] ?? '-') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Status</div><span class="badge bg-light text-dark border"><?= esc($row['status'] ?? '-') ?></span></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Detail Khusus</h5>
        <?php if ($detail): ?>
          <dl class="row mb-0">
            <?php foreach ($detail as $key => $value): ?>
              <?php if (in_array($key, ['id','bk_service_record_id','created_at','updated_at','deleted_at'], true)) continue; ?>
              <dt class="col-md-4"><?= esc(ucwords(str_replace('_', ' ', (string) $key))) ?></dt>
              <dd class="col-md-8"><?= nl2br(esc((string) ($value ?: '-'))) ?></dd>
            <?php endforeach; ?>
          </dl>
        <?php else: ?>
          <p class="text-muted mb-0">Belum ada detail tambahan.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Catatan</h5>
        <?php if ($notes): ?>
          <?php foreach ($notes as $note): ?>
            <div class="border rounded p-3 mb-2">
              <div class="d-flex justify-content-between">
                <strong><?= esc($note['note_type'] ?? 'Catatan') ?></strong>
                <small class="text-muted"><?= esc($note['created_at'] ?? '') ?></small>
              </div>
              <p class="mb-1"><?= nl2br(esc($note['note_content'] ?? '-')) ?></p>
              <small class="text-muted">Oleh <?= esc($note['author_name'] ?? '-') ?> - <?= esc($note['visibility_level'] ?? '-') ?></small>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted mb-0">Belum ada catatan yang dapat ditampilkan.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Peserta/Undangan</h5>
        <?php foreach ($participants as $participant): ?>
          <?php if (($participant['participant_type'] ?? '') === 'class') continue; ?>
          <div class="border-bottom pb-2 mb-2">
            <div class="fw-semibold"><?= esc($participantName($participant)) ?></div>
            <small class="text-muted"><?= esc($participant['role_in_session'] ?? '-') ?> - <?= esc($participant['attendance_status'] ?? '-') ?></small>
            <?php if ($canManage): ?>
              <form method="post" action="<?= site_url($routePrefix . '/participants/' . (int) $participant['id']) ?>" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="record_id" value="<?= esc((string) $row['id']) ?>">
                <div class="input-group input-group-sm">
                  <select name="attendance_status" class="form-select">
                    <?php foreach (['Hadir','Tidak Hadir','Izin','Sakit'] as $status): ?>
                      <option value="<?= esc($status) ?>" <?= ($participant['attendance_status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-outline-primary">Simpan</button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (! $participants): ?>
          <p class="text-muted mb-0">Belum ada peserta.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($canManage): ?>
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Tambah Catatan</h5>
          <form method="post" action="<?= site_url($routePrefix . '/note/' . (int) $row['id']) ?>">
            <?= csrf_field() ?>
            <div class="mb-2">
              <select name="note_type" class="form-select">
                <?php foreach (['Observasi','Intervensi','Follow-up','Agenda','Lainnya'] as $type): ?>
                  <option value="<?= esc($type) ?>"><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <textarea name="note_content" class="form-control" rows="4" required></textarea>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="is_confidential" value="1" id="isConfidential" checked>
              <label class="form-check-label" for="isConfidential">Rahasia internal BK</label>
            </div>
            <button class="btn btn-primary w-100">Tambah Catatan</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>

