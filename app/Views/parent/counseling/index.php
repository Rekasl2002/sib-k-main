<?php
/**
 * View per peran fitur layanan BK.
 * Fitur: Bimbingan, Konseling, Kolaborasi Orang Tua, Kunjungan Rumah,
 * Konferensi Kasus.
 * Peran/izin: Peran: Orang Tua. Fitur: Layanan BK.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$rows = is_array($rows ?? null) ? $rows : [];
$filters = is_array($filters ?? null) ? $filters : [];
$options = is_array($options ?? null) ? $options : [];
$routePrefix = (string) ($routePrefix ?? '');
$title = (string) ($title ?? ($meta['title'] ?? 'Layanan BK'));
$canManage = ! empty($canManage);
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0"><?= esc($title) ?></h4>
        <p class="text-muted mb-0">Tampilan <?= esc($roleLabel ?? 'Pengguna') ?> untuk layanan <?= esc($serviceType ?? 'BK') ?>.</p>
      </div>
      <?php if ($canManage): ?>
        <a href="<?= site_url($routePrefix . '/create') ?>" class="btn btn-primary">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-3">
        <input type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari judul, siswa, kelas">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">Semua Status</option>
          <?php foreach (['Draft','Dijadwalkan','Berlangsung','Selesai','Dibatalkan','Perlu Tindak Lanjut'] as $status): ?>
            <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="class_id" class="form-select">
          <option value="">Semua Kelas</option>
          <?php foreach (($options['classes'] ?? []) as $class): ?>
            <option value="<?= esc((string) $class['id']) ?>" <?= (string) ($filters['class_id'] ?? '') === (string) $class['id'] ? 'selected' : '' ?>>
              <?= esc($class['class_name'] ?? '-') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to'] ?? '') ?>">
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-outline-primary" type="submit"><i class="mdi mdi-filter"></i></button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Judul/Topik</th>
            <th>Siswa/Kelas</th>
            <th>Guru BK</th>
            <th>Jadwal</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows): ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= esc($row['title'] ?? '-') ?></div>
                  <small class="text-muted"><?= esc($row['service_type'] ?? $serviceType ?? '-') ?></small>
                </td>
                <td>
                  <div><?= esc($row['student_name'] ?? '-') ?></div>
                  <small class="text-muted"><?= esc($row['class_name'] ?? '-') ?></small>
                </td>
                <td><?= esc($row['counselor_name'] ?? '-') ?></td>
                <td>
                  <?= esc($row['scheduled_at'] ?? $row['held_at'] ?? '-') ?>
                  <?php if (! empty($row['duration_minutes'])): ?>
                    <small class="text-muted d-block"><?= esc((string) $row['duration_minutes']) ?> menit</small>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-light text-dark border"><?= esc($row['status'] ?? '-') ?></span></td>
                <td class="text-end">
                  <a href="<?= site_url($routePrefix . '/show/' . (int) $row['id']) ?>" class="btn btn-sm btn-outline-primary">
                    Detail
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Belum ada data.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

