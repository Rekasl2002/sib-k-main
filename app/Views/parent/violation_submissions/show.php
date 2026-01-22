<?php
// app/Views/parent/violation_submissions/show.php
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Pastikan $row selalu array (kadang bisa object dari model/query)
$row = is_array($row ?? null) ? $row : (is_object($row ?? null) ? (array) $row : []);

// Status + badge (pakai yang dikirim controller kalau ada, tapi tetap safe)
$status = (string)($status ?? ($row['status'] ?? 'Diajukan'));

$badgeMap = [
  'Diajukan'   => 'warning',
  'Ditinjau'   => 'info',
  'Ditolak'    => 'danger',
  'Diterima'   => 'success',
  'Dikonversi' => 'primary',
];
$badge = (string)($badge ?? ($badgeMap[$status] ?? 'secondary'));

// Editable rules
$isEditable = !in_array($status, ['Ditolak','Diterima','Dikonversi'], true);

// Terlapor label (support beberapa kemungkinan nama kolom)
if (!empty($row['subject_student_id'])) {
  $name  = trim((string)($row['subject_student_name'] ?? ''));
  $class = trim((string)(
    $row['subject_student_class'] ??
    $row['subject_class_name'] ??
    $row['subject_student_class_name'] ??
    ''
  ));
  $nis   = trim((string)($row['subject_student_nis'] ?? ''));

  $parts = [];
  if ($name !== '')  $parts[] = $name;
  if ($class !== '') $parts[] = $class;
  if ($nis !== '')   $parts[] = 'NIS ' . $nis;

  $subject = $parts ? implode(' • ', $parts) : '-';
} else {
  $other = trim((string)($row['subject_other_name'] ?? ''));
  $subject = $other !== '' ? $other : '-';
}

// Evidence parsing (robust)
$evidenceRaw = $row['evidence_json'] ?? [];
$evidence = [];

if (is_array($evidenceRaw)) {
  $evidence = $evidenceRaw;
} elseif (is_string($evidenceRaw) && trim($evidenceRaw) !== '') {
  $decoded = json_decode($evidenceRaw, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    $evidence = $decoded;
  } else {
    // Fallback jika bukan JSON valid (misal "a,b,c")
    $evidence = array_map('trim', explode(',', $evidenceRaw));
  }
}

// Normalisasi evidence: buang kosong, rapikan index
$evidence = array_values(array_filter($evidence, static function ($v) {
  return is_string($v) && trim($v) !== '';
}));

$rid = (int)($row['id'] ?? 0); // dipakai untuk link edit/hapus (tidak ditampilkan)
?>

<div class="d-flex flex-column flex-md-row align-items-md-start justify-content-md-between gap-2 mb-3">
  <div>
    <h4 class="mb-1"><?= esc($title ?? 'Detail Pengaduan') ?></h4>
    <small class="text-muted">
      Status:
      <span class="badge bg-<?= esc($badge) ?>"><?= esc($status) ?></span>
    </small>
  </div>

  <div class="ms-md-auto text-md-end">
    <nav aria-label="breadcrumb" class="mb-2">
      <ol class="breadcrumb mb-0 justify-content-md-end">
        <li class="breadcrumb-item">
          <a href="<?= base_url('parent/dashboard') ?>">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= base_url('parent/violation-submissions') ?>">Pengaduan</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Detail Pengaduan</li>
      </ol>
    </nav>

    <div class="d-flex gap-2 justify-content-md-end">
      <a href="<?= base_url('parent/violation-submissions') ?>" class="btn btn-light">Kembali</a>

      <?php if ($isEditable && $rid): ?>
        <a href="<?= base_url('parent/violation-submissions/edit/' . $rid) ?>" class="btn btn-outline-secondary">Edit</a>

        <form action="<?= base_url('parent/violation-submissions/delete/' . $rid) ?>"
              method="post"
              class="d-inline"
              onsubmit="return confirm('Hapus pengaduan ini?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-danger">Hapus</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success mb-3"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if ($status === 'Ditolak' && !empty($row['review_notes'])): ?>
  <div class="alert alert-danger mb-3">
    <div class="fw-semibold">Pengaduan ditolak</div>
    <div>Alasan: <?= esc((string)$row['review_notes']) ?></div>
  </div>
<?php elseif ($status === 'Diterima'): ?>
  <div class="alert alert-success mb-3">
    <div class="fw-semibold">Pengaduan diterima</div>
    <div class="small text-muted">Petugas akan menindaklanjuti pengaduan ini.</div>
  </div>
<?php elseif ($status === 'Dikonversi'): ?>
  <div class="alert alert-primary mb-3">
    <div class="fw-semibold">Sudah dikonversi menjadi pelanggaran resmi</div>
    <div class="small">ID Pelanggaran: <?= esc((string)($row['converted_violation_id'] ?? '-')) ?></div>
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
        <div class="fw-semibold"><?= esc((string)($row['category_name'] ?? ($row['category_id'] ?? '-'))) ?></div>
      </div>

      <div class="col-md-3">
        <div class="text-muted small">Tanggal Kejadian</div>
        <div><?= esc((string)($row['occurred_date'] ?? '-')) ?></div>
      </div>

      <div class="col-md-3">
        <div class="text-muted small">Waktu Kejadian</div>
        <div><?= esc((string)($row['occurred_time'] ?? '-')) ?></div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">Lokasi</div>
        <div><?= esc((string)($row['location'] ?? '-')) ?></div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">Saksi</div>
        <div><?= esc((string)($row['witness'] ?? '-')) ?></div>
      </div>

      <div class="col-12">
        <div class="text-muted small">Kronologi / Deskripsi</div>
        <div class="border rounded p-3">
          <?= esc((string)($row['description'] ?? '')) ?>
        </div>
      </div>

      <div class="col-12">
        <div class="text-muted small">Lampiran Bukti</div>
        <?php if (empty($evidence)): ?>
          <div class="text-muted">Tidak ada lampiran.</div>
        <?php else: ?>
          <ul class="mb-0">
            <?php foreach ($evidence as $p): ?>
              <?php
                $p = (string)$p;
                $label = basename($p) ?: $p;
                $href  = base_url(ltrim($p, '/'));
              ?>
              <li>
                <a href="<?= esc($href, 'attr') ?>" target="_blank" rel="noopener noreferrer">
                  <?= esc($label) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="col-12">
        <hr class="my-2">
        <div class="text-muted small">Diproses oleh</div>
        <div><?= esc((string)($row['handled_by_name'] ?? '-')) ?></div>

        <div class="text-muted small mt-2">Waktu diproses</div>
        <div><?= esc((string)($row['handled_at'] ?? '-')) ?></div>

        <?php if (!empty($row['review_notes']) && $status !== 'Ditolak'): ?>
          <div class="text-muted small mt-2">Catatan petugas</div>
          <div class="border rounded p-2" style="white-space:pre-wrap;"><?= esc((string)$row['review_notes']) ?></div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?= $this->endSection() ?>
