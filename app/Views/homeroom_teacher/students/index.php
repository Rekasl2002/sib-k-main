<!-- app/Views/homeroom_teacher/students/index.php -->
<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<?php
$class = is_array($class ?? null) ? $class : [];
$isMultipleClass = ! empty($class['is_multiple']);
?>

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
            <div class="fw-semibold">
              Kelas:
              <?= $isMultipleClass ? esc(($class['class_count'] ?? 0) . ' kelas: ' . ($class['class_name'] ?? '-')) : esc($class['class_name'] ?? '-'); ?>
            </div>
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
                <?php if ($isMultipleClass): ?><th>Kelas</th><?php endif; ?>
                <th class="d-none d-sm-table-cell">NIK</th>
                <th class="d-none d-md-table-cell">NISN</th>
                <th class="text-center">JK</th>
                <th style="width:7rem"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($students)) : $no=1; foreach ($students as $st): ?>
                <tr>
                  <td><?= $no++; ?></td>
                  <td><?= esc($st['full_name'] ?? '-'); ?></td>
                  <?php if ($isMultipleClass): ?><td><?= esc($st['class_name'] ?? '-'); ?></td><?php endif; ?>
                  <td class="d-none d-sm-table-cell"><?= esc($st['nik'] ?? '-'); ?></td>
                  <td class="d-none d-md-table-cell"><?= esc($st['nisn'] ?? '-'); ?></td>
                  <td class="text-center"><?= esc($st['gender'] ?? '-'); ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('homeroom/students/'.$st['id']); ?>">Detail</a>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr>
                  <td colspan="<?= $isMultipleClass ? 7 : 6 ?>" class="text-center text-muted">Belum ada siswa aktif pada kelas ini.</td>
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
