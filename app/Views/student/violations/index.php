<!-- app/Views/student/violations/index.php -->
<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>

<?php
/** @var array|object[] $violations */
/** @var int|null $total_points */

$sumPoints = (int) ($total_points ?? 0);
?>

<div class="page-content">
  <div class="container-fluid">

    <div class="row mb-3">
      <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
          <h4 class="mb-0">RIWAYAT PELANGGARAN SAYA</h4>

          <a href="<?= base_url('student/violations/categories') ?>"
             class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-information-outline me-1"></i>
            Kategori Pelanggaran
          </a>
        </div>

        <!-- Ringkasan total poin pelanggaran -->
        <div class="card mb-0" style="min-width: 260px;">
          <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">Total Poin Pelanggaran</div>
              <div class="fw-bold fs-5">
                <?= $sumPoints ?>
              </div>
            </div>
            <div>
              <?php if ($sumPoints > 0): ?>
                <span class="badge bg-danger">Perlu diperhatikan</span>
              <?php else: ?>
                <span class="badge bg-success">Bersih 🎉</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"> <a href="<?= base_url('student/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Kasus & Pelanggaran</li>
        </ol>
      </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php elseif (session()->getFlashdata('info')): ?>
      <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('info')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">

        <?php if (empty($violations)): ?>
          <div class="text-center py-4">
            <i class="bx bx-check-circle text-success font-size-36"></i>
            <p class="text-muted mb-0 mt-2">
              Tidak ada pelanggaran. Pertahankan ya! 🎉
            </p>
          </div>
        <?php else: ?>

          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th style="width: 150px;">Tanggal</th>
                  <th>Pelanggaran</th>
                  <th style="width: 90px;">Poin</th>
                  <th style="width: 180px;">Pencatat</th>
                  <th style="width: 110px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($violations as $v): ?>
                <?php
                  // Normalisasi ke array supaya aman untuk object/array
                  $row = is_array($v) ? $v : (array) $v;

                  // Tanggal & jam
                    $dateRaw = $row['recorded_at']
                        ?? ($row['violation_date'] ?? null)
                        ?? ($row['date'] ?? null)
                        ?? ($row['created_at'] ?? null);

                    $timeRaw = $row['violation_time'] ?? null;

                    $dateLabel = '-';
                    $timeLabel = '';

                    // Format tanggal utama
                    if (!empty($dateRaw)) {
                        $ts = strtotime($dateRaw);
                        if ($ts !== false) {
                            $dateLabel = date('d M Y', $ts);
                            // Jika nanti time tidak diambil dari kolom khusus,
                            // kita bisa jatuhkan ke waktu dari timestamp ini
                            $timeFromDate = date('H:i', $ts);
                            if ($timeFromDate !== '00:00') {
                                $timeLabel = $timeFromDate;
                            }
                        } else {
                            $dateLabel = (string) $dateRaw;
                        }
                    }

                    // Jika ada kolom violation_time, pakai itu sebagai prioritas
                    if (!empty($timeRaw)) {
                        $t = substr($timeRaw, 0, 5);
                        // Kalau nilainya "00:00", kita anggap tidak perlu ditampilkan
                        if ($t !== '00:00') {
                            $timeLabel = $t;
                        } else {
                            $timeLabel = '';
                        }
                    }


                  // Nama / kategori pelanggaran
                  $type = $row['violation_type']
                      ?? ($row['category_name'] ?? null)
                      ?? ($row['type'] ?? null)
                      ?? 'Pelanggaran';

                  // Deskripsi singkat (jika ada)
                  $desc = $row['description'] ?? '';

                  // Poin pelanggaran
                  $points = (int) (
                      $row['points']
                      ?? ($row['poin'] ?? 0)
                  );

                  // Pencatat
                  $recorder = $row['recorder']
                      ?? ($row['reported_by_name'] ?? null)
                      ?? ($row['recorder_name'] ?? null)
                      ?? '-';

                  // Ringkasan sanksi (hasil GROUP_CONCAT di controller)
                  $sanctionSummary = $row['sanctions_summary'] ?? null;

                  $id = (int) ($row['id'] ?? 0);
                ?>
                <tr>
                  <td>
                    <?= esc($dateLabel) ?>
                    <?php if ($timeLabel): ?>
                      <br><small class="text-muted"><?= esc($timeLabel) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-bold"><?= esc($type) ?></div>
                    <?php if ($desc): ?>
                      <div class="text-muted small">
                        <?= nl2br(esc($desc, 'html')) ?>
                      </div>
                    <?php endif; ?>
                    <?php if ($sanctionSummary): ?>
                      <div class="small mt-1">
                        <span class="badge bg-light text-dark border">
                          Sanksi: <?= esc($sanctionSummary) ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($points > 0): ?>
                      <span class="badge bg-danger"><?= $points ?></span>
                    <?php else: ?>
                      <span class="badge bg-success">0</span>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($recorder) ?></td>
                  <td>
                    <?php if ($id > 0): ?>
                      <a href="<?= base_url('student/violations/' . $id) ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-eye"></i> Detail
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<?= $this->endSection(); ?>
