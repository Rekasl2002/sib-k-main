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
$counselors = is_array($counselors ?? null) ? $counselors : [];
?>
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0">Penugasan</h4>
        <p class="text-muted mb-0">Tampilan <?= esc($roleLabel ?? 'Pengguna') ?>.</p>
      </div>
      <?php if (! empty($canManage)): ?>
        <div class="d-flex gap-2 flex-wrap">
          <a href="<?= site_url('koordinator/users') ?>" class="btn btn-outline-primary">Kelola Guru BK</a>
          <a href="<?= site_url($routePrefix . '/create') ?>" class="btn btn-primary">Buat Tugas</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $flashKey => $flashClass): ?>
  <?php if (session()->getFlashdata($flashKey)): ?>
    <div class="alert alert-<?= esc($flashClass, 'attr') ?> alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata($flashKey)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<?php if (! empty($canManage)): ?>
  <div class="row g-3 mb-3">
    <div class="col-12">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <div>
              <h5 class="card-title mb-1">Guru BK dan Kelas Binaan</h5>
              <p class="text-muted mb-0 small">Ringkasan Guru BK yang dapat menerima penugasan.</p>
            </div>
            <a href="<?= site_url('koordinator/users/create') ?>" class="btn btn-sm btn-primary">Tambah Guru BK</a>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light"><tr><th>Nama</th><th>Kelas Binaan</th><th class="text-center">Status</th><th class="text-end">Aksi</th></tr></thead>
              <tbody>
                <?php foreach ($counselors as $counselor): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= esc($counselor['full_name'] ?? '-') ?></div>
                      <small class="text-muted"><?= esc($counselor['username'] ?? '-') ?></small>
                    </td>
                    <td>
                      <?php if ((int) ($counselor['class_count'] ?? 0) > 0): ?>
                        <?= esc($counselor['class_names'] ?? '-') ?>
                      <?php else: ?>
                        <span class="text-muted">Belum ada kelas binaan</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-<?= (int) ($counselor['is_active'] ?? 0) === 1 ? 'success' : 'secondary' ?>">
                        <?= (int) ($counselor['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <a href="<?= site_url('koordinator/users/edit/' . (int) ($counselor['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">Kelola</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (! $counselors): ?>
                  <tr><td colspan="4" class="text-center text-muted py-3">Belum ada akun Guru BK.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

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

