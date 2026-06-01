<?php
$basePath = trim((string)($basePath ?? ''), '/');
$filters = is_array($filters ?? null) ? $filters : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Pelaporan Pelanggaran</h4>
    <small class="text-muted"><?= esc($roleLabel ?? 'Petugas BK') ?> - tinjau pengaduan masuk</small>
  </div>
</div>

<?= show_alerts() ?>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label">Cari</label>
        <input type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">Semua</option>
          <?php foreach (['Diajukan', 'Ditinjau', 'Ditolak', 'Diterima', 'Dikonversi'] as $status): ?>
            <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Pelapor</label>
        <select name="reporter_type" class="form-select">
          <option value="">Semua</option>
          <?php foreach (['student' => 'Siswa', 'parent' => 'Orang Tua', 'homeroom' => 'Wali Kelas'] as $key => $label): ?>
            <option value="<?= esc($key) ?>" <?= ($filters['reporter_type'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Pelapor</th>
          <th>Terlapor</th>
          <th>Kategori</th>
          <th>Status</th>
          <th>Dibuat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pengaduan.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <?php
              $subject = $row['subject_student_name'] ?? $row['subject_other_name'] ?? '-';
              $class = $row['subject_student_class'] ?? '';
              $status = $row['status'] ?? 'Diajukan';
              $badge = match ($status) {
                  'Ditolak' => 'danger',
                  'Diterima' => 'success',
                  'Dikonversi' => 'primary',
                  'Ditinjau' => 'info',
                  default => 'warning',
              };
            ?>
            <tr>
              <td>#<?= (int)$row['id'] ?></td>
              <td>
                <div class="fw-semibold"><?= esc($row['reporter_name'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($row['reporter_role'] ?? $row['reporter_type'] ?? '-') ?></small>
              </td>
              <td>
                <div><?= esc($subject) ?></div>
                <?php if ($class): ?><small class="text-muted"><?= esc($class) ?></small><?php endif; ?>
              </td>
              <td><?= esc($row['category_name'] ?? '-') ?></td>
              <td><span class="badge bg-<?= esc($badge) ?>"><?= esc($status) ?></span></td>
              <td><small class="text-muted"><?= esc($row['created_at'] ?? '-') ?></small></td>
              <td>
                <a href="<?= site_url($basePath . '/violation-submissions/show/' . (int)$row['id']) ?>" class="btn btn-outline-primary btn-sm">Detail</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
