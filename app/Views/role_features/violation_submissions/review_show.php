<?php
$basePath = trim((string)($basePath ?? ''), '/');
$row = is_array($row ?? null) ? $row : [];
$status = $row['status'] ?? 'Diajukan';
$badge = match ($status) {
    'Ditolak' => 'danger',
    'Diterima' => 'success',
    'Ditinjau' => 'info',
    default => 'warning',
};
$subject = $row['subject_student_name'] ?? $row['subject_other_name'] ?? '-';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Detail Pengaduan #<?= (int)($row['id'] ?? 0) ?></h4>
    <small class="text-muted"><?= esc($roleLabel ?? 'Petugas BK') ?></small>
  </div>
  <a href="<?= site_url($basePath . '/violation-submissions') ?>" class="btn btn-light btn-sm">Kembali</a>
</div>

<?= show_alerts() ?>

<div class="row">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
          <div>
            <div class="text-muted small">Status</div>
            <span class="badge bg-<?= esc($badge) ?>"><?= esc($status) ?></span>
          </div>
          <div class="text-end">
            <div class="text-muted small">Dibuat</div>
            <div><?= esc($row['created_at'] ?? '-') ?></div>
          </div>
        </div>

        <dl class="row mb-0">
          <dt class="col-sm-3">Pelapor</dt>
          <dd class="col-sm-9"><?= esc($row['reporter_name'] ?? '-') ?> <span class="text-muted">(<?= esc($row['reporter_role'] ?? $row['reporter_type'] ?? '-') ?>)</span></dd>
          <dt class="col-sm-3">Terlapor</dt>
          <dd class="col-sm-9"><?= esc($subject) ?><?= !empty($row['subject_student_class']) ? ' - ' . esc($row['subject_student_class']) : '' ?></dd>
          <dt class="col-sm-3">Waktu</dt>
          <dd class="col-sm-9"><?= esc($row['occurred_date'] ?? '-') ?> <?= esc($row['occurred_time'] ?? '') ?></dd>
          <dt class="col-sm-3">Lokasi</dt>
          <dd class="col-sm-9"><?= esc($row['location'] ?? '-') ?></dd>
          <dt class="col-sm-3">Saksi</dt>
          <dd class="col-sm-9"><?= esc($row['witness'] ?? '-') ?></dd>
          <dt class="col-sm-3">Deskripsi</dt>
          <dd class="col-sm-9"><div style="white-space:pre-wrap"><?= esc($row['description'] ?? '-') ?></div></dd>
          <dt class="col-sm-3">Catatan</dt>
          <dd class="col-sm-9"><?= esc($row['review_notes'] ?? '-') ?></dd>
        </dl>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h5 class="mb-0">Bukti</h5></div>
      <div class="card-body">
        <?php $files = is_array($row['evidence_json'] ?? null) ? $row['evidence_json'] : []; ?>
        <?php if (empty($files)): ?>
          <div class="text-muted">Tidak ada bukti terlampir.</div>
        <?php else: ?>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($files as $file): ?>
              <a href="<?= base_url($file) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= esc(basename($file)) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h5 class="mb-0">Tinjau Pengaduan</h5></div>
      <div class="card-body">
        <form method="post" action="<?= site_url($basePath . '/violation-submissions/update-status/' . (int)($row['id'] ?? 0)) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
              <?php foreach (['Ditinjau', 'Ditolak', 'Diterima'] as $option): ?>
                <option value="<?= esc($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Catatan</label>
            <textarea name="review_notes" class="form-control" rows="4"><?= esc($row['review_notes'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">Simpan Tinjauan</button>
        </form>
      </div>
    </div>

  </div>
</div>
