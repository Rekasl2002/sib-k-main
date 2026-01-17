<?php // app/Views/parent/reports/children.php ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>



<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">LAPORAN ANAK</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Laporan</li>
                </ol>
            </div>
        </div>
        Pilih anak untuk melihat atau mencetak laporan ringkasan layanan BK.
    </div>
</div>


<?php if (empty($children)): ?>
  <div class="alert alert-info">
    Belum ada data anak yang terhubung ke akun ini.
    Silakan hubungi Guru BK atau wali kelas untuk menghubungkan akun orang tua.
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
          <tr>
            <th>Anak</th>
            <th>Kelas</th>
            <th>JK</th>
            <th>Pelanggaran</th>
            <th>Total Poin</th>
            <th>Aksi</th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($children as $c): ?>
            <?php
              $stats = $violationStats[$c['id']] ?? [
                'total_violations' => 0,
                'total_points'     => 0,
              ];
            ?>
            <tr>
              <td>
                <strong><?= esc($c['full_name']) ?></strong><br>
                <small class="text-muted">
                  NIS: <?= esc($c['nis'] ?? '-') ?>
                </small>
              </td>
              <td>
                <?= esc($c['class_name'] ?? '-') ?>
                <?php if (!empty($c['grade_level'])): ?>
                  <br>
                  <small class="text-muted">
                    Tingkat: <?= esc($c['grade_level']) ?>
                  </small>
                <?php endif; ?>
              </td>
              <td><?= esc($c['gender'] ?? '-') ?></td>
              <td><?= (int) ($stats['total_violations'] ?? 0) ?></td>
              <td><?= (int) ($stats['total_points'] ?? 0) ?></td>
              <td>
                <a href="<?= site_url('parent/reports/child/' . $c['id']) ?>"
                   class="btn btn-sm btn-primary">
                  Lihat / Cetak
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
