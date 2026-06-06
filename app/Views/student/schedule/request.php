<!-- app/Views/student/schedule/request.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$today = $today ?? date('Y-m-d');
$hasClassAndCounselor = !empty($classId) && !empty($defaultCounselor);
$errors = session()->getFlashdata('errors') ?? [];
$errors = is_array($errors) ? $errors : [];
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">Ajukan Sesi Konseling</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item">
            <a href="<?= base_url('student/dashboard') ?>">Dashboard</a>
          </li>
          <li class="breadcrumb-item">
            <a href="<?= route_to('student.schedule') ?>">Sesi Konseling</a>
          </li>
          <li class="breadcrumb-item active">Ajukan Sesi</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success">
    <?= esc(session()->getFlashdata('success')) ?>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger">
    <?= esc(session()->getFlashdata('error')) ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Periksa kembali input:</div>
    <ul class="mb-0">
      <?php foreach ($errors as $message): ?>
        <li><?= esc($message) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (!$hasClassAndCounselor): ?>
  <div class="alert alert-warning">
    Kelas Anda belum dikaitkan dengan Guru BK, sehingga pengajuan sesi belum dapat dikirim.
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Form Pengajuan</h5>
      </div>
      <div class="card-body">
        <form action="<?= route_to('student.schedule.store') ?>" method="post" autocomplete="off">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="session_date" class="form-label">Tanggal Konseling <span class="text-danger">*</span></label>
            <input
              type="date"
              id="session_date"
              name="session_date"
              class="form-control"
              value="<?= esc(old('session_date', $today)) ?>"
              min="<?= esc($today) ?>"
              required
              <?= !$hasClassAndCounselor ? 'disabled' : '' ?>
            >
          </div>

          <div class="mb-3">
            <label for="session_time" class="form-label">Waktu yang Diinginkan</label>
            <input
              type="time"
              id="session_time"
              name="session_time"
              class="form-control"
              value="<?= esc(old('session_time')) ?>"
              <?= !$hasClassAndCounselor ? 'disabled' : '' ?>
            >
            <div class="form-text">Boleh dikosongkan jika ingin dijadwalkan oleh Guru BK.</div>
          </div>

          <div class="mb-3">
            <label for="topic" class="form-label">Topik Konseling <span class="text-danger">*</span></label>
            <input
              type="text"
              id="topic"
              name="topic"
              class="form-control"
              value="<?= esc(old('topic')) ?>"
              maxlength="255"
              placeholder="Contoh: Konseling akademik, pribadi, atau rencana studi"
              required
              <?= !$hasClassAndCounselor ? 'disabled' : '' ?>
            >
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Uraian Singkat</label>
            <textarea
              id="description"
              name="description"
              class="form-control"
              rows="5"
              placeholder="Ceritakan singkat hal yang ingin dibahas"
              <?= !$hasClassAndCounselor ? 'disabled' : '' ?>
            ><?= esc(old('description')) ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <a href="<?= route_to('student.schedule') ?>" class="btn btn-light">Kembali</a>
            <button type="submit" class="btn btn-primary" <?= !$hasClassAndCounselor ? 'disabled' : '' ?>>
              <i class="mdi mdi-send-outline me-1"></i> Kirim Pengajuan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
