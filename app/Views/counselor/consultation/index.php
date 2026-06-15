<?php
/**
 * View per peran Konsultasi & Pengaduan.
 * Peran/izin: Pelapor melihat aduan sendiri; BK meninjau seluruh aduan yang
 * masuk sesuai controller per peran.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$rows = is_array($rows ?? null) ? $rows : [];
$filters = is_array($filters ?? null) ? $filters : [];
$routePrefix = (string) ($routePrefix ?? '');
$me = (int) (session('user_id') ?? 0);
?>
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0">Konsultasi & Pengaduan</h4>
        <p class="text-dark mb-0">Tampilan <?= esc($roleLabel ?? 'Pengguna') ?>.</p>
      </div>
      <?php if (! empty($canSubmit) || ! empty($canReview)): ?>
        <a href="<?= site_url($routePrefix . '/create') ?>" class="btn btn-primary">
          <i class="mdi mdi-plus me-1"></i> Ajukan
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-4"><input type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari judul, deskripsi, siswa"></div>
      <div class="col-md-2">
        <select name="request_type" class="form-select">
          <option value="">Semua Jenis</option>
          <?php foreach (['Konsultasi','Pengaduan','Permintaan Konseling','Laporan Orang Tua','Laporan Wali Kelas','Lainnya/Tidak Bisa Menentukan'] as $type): ?>
            <option value="<?= esc($type) ?>" <?= ($filters['request_type'] ?? '') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">Semua Status</option>
          <?php foreach (['Diajukan','Ditinjau','Diterima','Ditolak','Dijadwalkan','Selesai','Diarsipkan'] as $status): ?>
            <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="priority" class="form-select">
          <option value="">Semua Prioritas</option>
          <?php foreach (['Rendah','Sedang','Tinggi','Mendesak'] as $priority): ?>
            <option value="<?= esc($priority) ?>" <?= ($filters['priority'] ?? '') === $priority ? 'selected' : '' ?>><?= esc($priority) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Saring</button></div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Judul</th>
            <th>Pelapor</th>
            <th>Siswa</th>
            <th>Jenis</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= esc($row['title'] ?? '-') ?></div>
                <small class="text-dark"><?= esc($row['created_at'] ?? '') ?></small>
              </td>
              <td><?= esc($row['reporter_name'] ?? '-') ?></td>
              <td><?= esc($row['student_name'] ?? $row['subject_other_name'] ?? '-') ?></td>
              <td><?= esc($row['request_type'] ?? '-') ?></td>
              <td><span class="badge bg-light text-dark border"><?= esc($row['status'] ?? '-') ?></span></td>
              <?php
                $isOwner = (int) ($row['reporter_user_id'] ?? 0) === $me;
                $editable = in_array($row['status'] ?? '', ['Diajukan', 'Ditinjau'], true);
              ?>
              <td class="text-end text-nowrap">
                <a href="<?= site_url($routePrefix . '/show/' . (int) $row['id']) ?>" class="btn btn-sm btn-info" title="Lihat detail" data-bs-toggle="tooltip"><i class="mdi mdi-eye"></i></a>
                <?php if (! empty($canSubmit) && $isOwner && $editable): ?>
                  <a href="<?= site_url($routePrefix . '/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-primary" title="Edit" data-bs-toggle="tooltip"><i class="mdi mdi-pencil"></i></a>
                <?php endif; ?>
                <?php if ($isOwner): ?>
                  <form method="post" action="<?= site_url($routePrefix . '/delete/' . (int) $row['id']) ?>" class="d-inline" onsubmit="return confirm('Pindahkan laporan ini ke Tempat Sampah?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus (ke Tempat Sampah)" data-bs-toggle="tooltip"><i class="mdi mdi-delete"></i></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (! $rows): ?>
            <tr><td colspan="6" class="text-center text-dark py-4">Belum ada data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

