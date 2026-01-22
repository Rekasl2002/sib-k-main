<!-- app/Views/student/violation_submissions/show.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item">
      <a href="<?= base_url('student/dashboard') ?>">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
      <a href="<?= base_url('student/violation-submissions') ?>">Pengaduan</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Detail Pengaduan</li>
  </ol>
</nav>

<?php
  // Pastikan $row selalu array (kadang bisa object dari model/query)
  $row = is_array($row ?? null) ? $row : (is_object($row ?? null) ? (array) $row : []);

  $status = $row['status'] ?? 'Diajukan';

  // Badge mapping lebih aman & ringkas
  $badgeMap = [
    'Diajukan'   => 'warning',
    'Ditinjau'   => 'info',
    'Ditolak'    => 'danger',
    'Diterima'   => 'success',
    'Dikonversi' => 'primary',
  ];
  $badge = $badgeMap[$status] ?? 'secondary';

  // Terlapor
  if (!empty($row['subject_student_id'])) {
    $subject = trim(
      ($row['subject_student_name'] ?? '')
      . ' • ' . ($row['subject_student_class'] ?? '')
      . ' • NIS ' . ($row['subject_student_nis'] ?? '')
    );
  } else {
    $subject = $row['subject_other_name'] ?? '-';
  }

  /**
   * Evidence parsing:
   * - Bisa array (sudah decode)
   * - Bisa JSON string '["path1","path2"]'
   * - Bisa string biasa (fallback)
   */
  $evidenceRaw = $row['evidence_json'] ?? [];
  $evidence = [];

  if (is_array($evidenceRaw)) {
    $evidence = $evidenceRaw;
  } elseif (is_string($evidenceRaw) && $evidenceRaw !== '') {
    $decoded = json_decode($evidenceRaw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      $evidence = $decoded;
    } else {
      // Fallback jika ternyata bukan JSON valid
      $evidence = array_map('trim', explode(',', $evidenceRaw));
    }
  }

  // Normalisasi: hanya string path yang valid, buang kosong
  $evidence = array_values(array_filter($evidence, static function ($v) {
    return is_string($v) && trim($v) !== '';
  }));

  // Fallback jika controller tidak mengirim $isEditable
  $isEditable = $isEditable ?? (!in_array($status, ['Ditolak','Diterima','Dikonversi'], true));
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">Detail Pengaduan</h4>
    <small class="text-muted">
      Status:
      <span class="badge bg-<?= esc($badge) ?>"><?= esc($status) ?></span>
    </small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('student/violation-submissions') ?>" class="btn btn-light">Kembali</a>

    <?php if (!empty($isEditable) && !empty($row['id'])): ?>
      <a href="<?= base_url('student/violation-submissions/edit/' . $row['id']) ?>" class="btn btn-outline-secondary">Edit</a>
      <form action="<?= base_url('student/violation-submissions/delete/' . $row['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Hapus pengaduan ini?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger">Hapus</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if ($status === 'Ditolak' && !empty($row['review_notes'])): ?>
  <div class="alert alert-danger">
    <div class="fw-semibold">Pengaduan ditolak</div>
    <div>Alasan: <?= esc($row['review_notes']) ?></div>
  </div>
<?php elseif ($status === 'Diterima'): ?>
  <div class="alert alert-success">
    <div class="fw-semibold">Pengaduan diterima</div>
    <div class="small text-muted">Petugas akan menindaklanjuti dan dapat mengonversi menjadi pelanggaran resmi.</div>
  </div>
<?php elseif ($status === 'Dikonversi'): ?>
  <div class="alert alert-primary">
    <div class="fw-semibold">Sudah dikonversi menjadi pelanggaran resmi</div>
    <div class="small">ID Pelanggaran: <?= esc($row['converted_violation_id'] ?? '-') ?></div>
  </div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="text-muted small">Terlapor</div>
        <div class="fw-semibold"><?= esc($subject) ?></div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">Kategori</div>
        <div class="fw-semibold"><?= esc($row['category_name'] ?? '-') ?></div>
      </div>

      <div class="col-md-3">
        <div class="text-muted small">Tanggal Kejadian</div>
        <div><?= esc($row['occurred_date'] ?? '-') ?></div>
      </div>

      <div class="col-md-3">
        <div class="text-muted small">Waktu Kejadian</div>
        <div><?= esc($row['occurred_time'] ?? '-') ?></div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">Lokasi</div>
        <div><?= esc($row['location'] ?? '-') ?></div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">Saksi</div>
        <div><?= esc($row['witness'] ?? '-') ?></div>
      </div>

      <div class="col-12">
        <div class="text-muted small">Kronologi / Deskripsi</div>
        <div class="border rounded p-3"><?= nl2br(esc($row['description'] ?? '')) ?></div>
      </div>

      <div class="col-12">
        <div class="text-muted small">Lampiran Bukti</div>
        <?php if (empty($evidence)): ?>
          <div class="text-muted">Tidak ada lampiran.</div>
        <?php else: ?>
          <ul class="mb-0">
            <?php foreach ($evidence as $p): ?>
              <?php
                // Label biar rapi: tampilkan nama file saja
                $label = basename((string) $p);
                $href  = base_url(ltrim((string) $p, '/'));
              ?>
              <li>
                <a href="<?= esc($href, 'attr') ?>" target="_blank" rel="noopener noreferrer">
                  <?= esc($label ?: $p) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="col-12">
        <hr>
        <div class="text-muted small">Diproses oleh</div>
        <div><?= esc($row['handled_by_name'] ?? '-') ?></div>

        <div class="text-muted small mt-2">Waktu diproses</div>
        <div><?= esc($row['handled_at'] ?? '-') ?></div>

        <?php if (!empty($row['review_notes']) && $status !== 'Ditolak'): ?>
          <div class="text-muted small mt-2">Catatan petugas</div>
          <div class="border rounded p-2"><?= nl2br(esc($row['review_notes'])) ?></div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?= $this->endSection() ?>
