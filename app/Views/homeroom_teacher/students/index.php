<!--
  File Path: app/Views/homeroom_teacher/students/index.php
  Fitur: daftar siswa kelas binaan dan pintu masuk Impor Data Siswa/Orang Tua untuk Wali Kelas.
  Relasi: HomeroomTeacher\StudentController, HomeroomTeacher\StudentImportController, tabel classes/students/users.
-->
<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<?php
helper('permission');
$class = is_array($class ?? null) ? $class : [];
$isMultipleClass = ! empty($class['is_multiple']);
?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-people"></i> <?= esc($pageTitle ?? 'Daftar Siswa Kelas Saya'); ?></h4>
    <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
      <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Wali Kelas</a></li>
        <li class="breadcrumb-item active">Daftar Siswa</li>
      </ol>
      <?php if (!empty($activeYear)) : ?>
        <span class="badge bg-primary">Tahun Ajaran: <?= esc($activeYear['year_name']); ?> (<?= esc($activeYear['semester']); ?>)</span>
      <?php endif; ?>
    </div>
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
          <div class="d-flex gap-2 flex-wrap">
            <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
              <a href="<?= site_url('homeroom/students/create'); ?>" class="btn btn-primary btn-sm">
                <i class="mdi mdi-account-plus-outline"></i> Tambah Siswa
              </a>
              <a href="<?= site_url('homeroom/parents'); ?>" class="btn btn-outline-primary btn-sm">
                <i class="mdi mdi-account-group-outline"></i> Akun Orang Tua
              </a>
            <?php endif; ?>
            <?php if (function_exists('has_permission') && has_permission('import_export_data')): ?>
              <a href="<?= site_url('homeroom/students/import'); ?>" class="btn btn-info btn-sm">
                <i class="mdi mdi-file-import-outline"></i> Impor Data
              </a>
              <a href="<?= site_url('homeroom/students/export'); ?>" class="btn btn-outline-success btn-sm">
                <i class="mdi mdi-file-export-outline"></i> Ekspor CSV
              </a>
            <?php endif; ?>
            <a href="<?= site_url('homeroom/my-class'); ?>" class="btn btn-outline-secondary btn-sm">Ringkasan Kelas</a>
          </div>
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
                <th>Orang Tua</th>
                <th style="width:16rem"></th>
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
                  <td>
                    <?php if (! empty($st['parent_id'])): ?>
                      <div class="fw-semibold"><?= esc($st['parent_name'] ?? 'Orang tua/wali'); ?></div>
                      <small class="text-muted"><?= esc($st['parent_phone'] ?? $st['parent_email'] ?? '-'); ?></small>
                    <?php else: ?>
                      <span class="text-muted">Belum terhubung</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a class="btn btn-outline-primary" href="<?= site_url('homeroom/students/'.$st['id']); ?>">Detail</a>
                      <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
                        <a class="btn btn-outline-secondary" href="<?= site_url('homeroom/students/edit/'.$st['id']); ?>">Edit</a>
                        <?php if (! empty($st['parent_id'])): ?>
                          <a class="btn btn-outline-info" href="<?= site_url('homeroom/parents/'.$st['parent_id']); ?>">Orang Tua</a>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                    <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
                      <form action="<?= site_url('homeroom/students/delete/'.$st['id']); ?>" method="post" class="d-inline ms-1" onsubmit="return confirm('Hapus siswa ini dari kelas binaan?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr>
                  <td colspan="<?= $isMultipleClass ? 8 : 7 ?>" class="text-center text-muted">Belum ada siswa aktif pada kelas ini.</td>
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
