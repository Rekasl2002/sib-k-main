<?php $basePath = trim((string)($basePath ?? ''), '/'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0"><?= esc($title ?? 'Pengaduan Pelanggaran') ?></h4>
    <small class="text-muted">Laporkan pelanggaran untuk ditinjau Guru BK atau Koordinator BK.</small>
  </div>
  <a href="<?= site_url($basePath . '/create') ?>" class="btn btn-primary btn-sm">Buat Pengaduan</a>
</div>

<?= show_alerts() ?>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Terlapor</th>
          <th>Kategori</th>
          <th>Status</th>
          <th>Dibuat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengaduan.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <?php
              $subject = $row['subject_student_name'] ?? $row['subject_other_name'] ?? '-';
              $status = $row['status'] ?? 'Diajukan';
              $badge = match ($status) {
                  'Ditolak' => 'danger',
                  'Diterima' => 'success',
                  'Dikonversi' => 'primary',
                  'Ditinjau' => 'info',
                  default => 'warning',
              };
              $editable = !in_array($status, ['Ditolak', 'Diterima', 'Dikonversi'], true);
            ?>
            <tr>
              <td>#<?= (int)$row['id'] ?></td>
              <td><?= esc($subject) ?></td>
              <td><?= esc($row['category_name'] ?? '-') ?></td>
              <td><span class="badge bg-<?= esc($badge) ?>"><?= esc($status) ?></span></td>
              <td><small class="text-muted"><?= esc($row['created_at'] ?? '-') ?></small></td>
              <td>
                <a class="btn btn-outline-primary btn-sm" href="<?= site_url($basePath . '/show/' . (int)$row['id']) ?>">Detail</a>
                <?php if ($editable): ?>
                  <a class="btn btn-outline-secondary btn-sm" href="<?= site_url($basePath . '/edit/' . (int)$row['id']) ?>">Edit</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
