<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File Path: app/Views/homeroom_teacher/students/parents.php
 * Fitur: Daftar akun orang tua siswa binaan.
 * Peran/izin: Wali Kelas melihat orang tua yang terhubung ke siswa kelas binaannya.
 * Berhubungan dengan: HomeroomTeacher\StudentController, users, students.
 */

$parents = is_array($parents ?? null) ? $parents : [];
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0">Akun Orang Tua</h4>
        <p class="text-muted mb-0">Hanya menampilkan akun orang tua yang terhubung dengan siswa kelas binaan Anda.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= base_url('homeroom/parents/create') ?>" class="btn btn-success">
          <i class="mdi mdi-plus me-1"></i>Tambah Akun
        </a>
        <a href="<?= base_url('homeroom/my-class') ?>" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $class): ?>
  <?php if (session()->getFlashdata($key)): ?>
    <div class="alert alert-<?= $class ?> alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata($key)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Nama</th>
            <th>Username</th>
            <th>Kontak</th>
            <th class="text-center">Jumlah Anak</th>
            <th class="text-center">Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($parents as $parent): ?>
            <tr>
              <td class="fw-semibold"><?= esc($parent['full_name'] ?? '-') ?></td>
              <td><code><?= esc($parent['username'] ?? '-') ?></code></td>
              <td>
                <div><?= esc($parent['phone'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($parent['email'] ?? '-') ?></small>
              </td>
              <td class="text-center"><?= (int) ($parent['child_count'] ?? 0) ?></td>
              <td class="text-center">
                <span class="badge bg-<?= (int) ($parent['is_active'] ?? 0) === 1 ? 'success' : 'secondary' ?>">
                  <?= (int) ($parent['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <a href="<?= base_url('homeroom/parents/' . (int) ($parent['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                  <a href="<?= base_url('homeroom/parents/edit/' . (int) ($parent['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (! $parents): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada akun orang tua yang terhubung.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
