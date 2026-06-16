<?php
/**
 * View daftar Penugasan per peran.
 * Peran/izin: Koordinator BK melihat semua; Guru BK melihat tugas miliknya.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$rows = is_array($rows ?? null) ? $rows : [];
$filters = is_array($filters ?? null) ? $filters : [];
$routePrefix = (string) ($routePrefix ?? '');
?>
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0">Tugas</h4>
        <p class="text-muted mb-0">Daftar tugas yang diberikan Koordinator BK untuk Anda. Setiap tugas baru akan tampil di sini &mdash; buka untuk melihat rincian, lalu perbarui statusnya bila sudah dikerjakan.</p>
      </div>
      <?php if (! empty($canManage)): ?>
        <a href="<?= site_url($routePrefix . '/create') ?>" class="btn btn-primary">Buat Tugas</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-4"><input type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari tugas atau Guru BK"></div>
      <div class="col-md-3">
        <select name="assignment_type" class="form-select">
          <option value="">Semua Jenis</option>
          <?php foreach (['Kelas Binaan','Tugas Layanan','Tindak Lanjut','Koordinasi'] as $type): ?>
            <option value="<?= esc($type) ?>" <?= ($filters['assignment_type'] ?? '') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">Semua Status</option>
          <?php foreach (['Draft','Ditugaskan','Dibaca','Berjalan','Selesai','Dibatalkan'] as $status): ?>
            <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Saring</button></div>
    </form>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Tugas</th><th>Guru BK</th><th>Kelas/Siswa</th><th>Batas Waktu</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><div class="fw-semibold"><?= esc($row['title'] ?? '-') ?></div><small class="text-muted"><?= esc($row['assignment_type'] ?? '-') ?></small></td>
              <td><?= esc($row['assigned_to_name'] ?? '-') ?></td>
              <td><?= esc($row['class_name'] ?? $row['student_name'] ?? '-') ?></td>
              <td><?= esc($row['due_at'] ?? '-') ?></td>
              <td><span class="badge bg-light text-dark border"><?= esc($row['status'] ?? '-') ?></span></td>
              <td class="text-end"><a href="<?= site_url($routePrefix . '/show/' . (int) $row['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (! $rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada tugas.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

