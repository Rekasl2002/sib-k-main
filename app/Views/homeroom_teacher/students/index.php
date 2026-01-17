<!-- app/Views/homeroom_teacher/students/index.php -->
<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-people"></i> <?= esc($pageTitle ?? 'Daftar Siswa Kelas Saya'); ?></h4>
    <?php if (!empty($activeYear)) : ?>
      <span class="badge bg-primary">Tahun Ajaran: <?= esc($activeYear['year_name']); ?> (<?= esc($activeYear['semester']); ?>)</span>
    <?php endif; ?>
  </div>

  <?php if (empty($class)) : ?>
    <div class="alert alert-warning">
      Anda belum terhubung dengan kelas aktif. Hubungi Admin/Koordinator BK untuk penetapan kelas perwalian.
    </div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <div class="fw-semibold">Kelas: <?= esc($class['class_name']); ?></div>
            <small class="text-muted">Tingkat: <?= esc($class['grade_level']); ?> <?= $class['major'] ? '(' . esc($class['major']) . ')' : ''; ?></small>
          </div>
          <a href="<?= site_url('homeroom/my-class'); ?>" class="btn btn-outline-secondary btn-sm">Ringkasan Kelas</a>
        </div>

        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:4rem">No</th>
                <th>Nama</th>
                <th class="d-none d-sm-table-cell">NISN</th>
                <th class="d-none d-md-table-cell">NIS</th>
                <th class="text-center">JK</th>
                <th class="text-end">Poin</th>
                <th style="width:7rem"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($students)) : $no=1; foreach ($students as $st): ?>
                <tr>
                  <td><?= $no++; ?></td>
                  <td><?= esc($st['full_name'] ?? '-'); ?></td>
                  <td class="d-none d-sm-table-cell"><?= esc($st['nisn'] ?? '-'); ?></td>
                  <td class="d-none d-md-table-cell"><?= esc($st['nis'] ?? '-'); ?></td>
                  <td class="text-center"><?= esc($st['gender'] ?? '-'); ?></td>
                  <td class="text-end fw-semibold"><?= (int)($st['total_violation_points'] ?? 0); ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('homeroom/students/'.$st['id']); ?>">Detail</a>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted">Belum ada siswa aktif pada kelas ini.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection(); ?>
