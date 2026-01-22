<!-- app/Views/parent/violation_submissions/index.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item">
      <a href="<?= base_url('parent/dashboard') ?>">Dashboard</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Pengaduan</li>
  </ol>
</nav>

<?php
use CodeIgniter\I18n\Time;

// Fail-safe variables
$title = $title ?? 'Pengaduan Pelanggaran';
$rows  = (isset($rows) && is_array($rows)) ? $rows : [];

/**
 * Format datetime (aman) -> "21 Jan 2026 13:40"
 */
if (!function_exists('sibk_fmt_dt')) {
    function sibk_fmt_dt($dt): string
    {
        if (!$dt) return '-';
        try {
            $t = $dt instanceof Time ? $dt : Time::parse((string) $dt);
            return $t->format('d M Y H:i');
        } catch (\Throwable $e) {
            return esc((string) $dt);
        }
    }
}

/**
 * Build label "Terlapor" secara rapi (lebih tahan banting):
 * - Jika subject_other_name terisi -> pakai itu
 * - Jika tidak -> pakai data siswa (nama/kelas/nis) dari field yang tersedia
 */
if (!function_exists('sibk_subject_label')) {
    function sibk_subject_label(array $r): string
    {
        $other = trim((string) ($r['subject_other_name'] ?? ''));
        if ($other !== '') return $other;

        $name  = trim((string) ($r['subject_student_name'] ?? $r['student_name'] ?? ''));
        $class = trim((string) ($r['subject_student_class'] ?? $r['subject_class_name'] ?? $r['class_name'] ?? ''));
        $nis   = trim((string) ($r['subject_student_nis'] ?? $r['nis'] ?? ''));

        $parts = [];
        if ($name !== '')  $parts[] = $name;
        if ($class !== '') $parts[] = $class;
        if ($nis !== '')   $parts[] = 'NIS ' . $nis;

        return $parts ? implode(' • ', $parts) : '-';
    }
}

/**
 * Status -> badge bootstrap
 */
if (!function_exists('sibk_status_badge')) {
    function sibk_status_badge(string $status): string
    {
        $s = strtolower(trim($status));
        return match ($s) {
            'diajukan'   => 'warning',
            'ditinjau'   => 'info',
            'ditolak'    => 'danger',
            'diterima'   => 'success',
            'dikonversi' => 'primary',
            default      => 'secondary',
        };
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><?= esc($title) ?></h4>
    <small class="text-muted">Laporkan pelanggaran yang Anda lihat/ketahui. Data akan ditinjau petugas BK.</small>
  </div>
  <a href="<?= base_url('parent/violation-submissions/create') ?>" class="btn btn-primary">
    + Buat Pengaduan
  </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success mb-3"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (!empty($error ?? null)): ?>
  <div class="alert alert-danger mb-3"><?= esc($error) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body table-responsive">
    <b>Yang Anda Laporkan</b>

    <table class="table table-striped align-middle mb-0">
      <thead>
        <tr>
          <th style="width: 90px;">ID</th>
          <th>Terlapor</th>
          <th>Kategori</th>
          <th>Status</th>
          <th>Dibuat</th>
          <th style="width: 210px;">Aksi</th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Belum ada pengaduan.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $r = is_array($r) ? $r : (array) $r;

              $id     = (int) ($r['id'] ?? 0);
              $status = trim((string) ($r['status'] ?? 'Diajukan'));
              $badge  = sibk_status_badge($status);

              $subjectLabel = sibk_subject_label($r);

              // editable kalau belum final
              $statusNorm = strtolower(trim($status));
              $isEditable = !in_array($statusNorm, ['ditolak','diterima','dikonversi'], true);

              $category = (string) ($r['category_name'] ?? '-');
              $created  = $r['created_at'] ?? null;

              $reviewNotes = trim((string) ($r['review_notes'] ?? ''));
            ?>
            <tr>
              <td>#<?= esc((string) $id) ?></td>
              <td>
                <div class="fw-semibold"><?= esc($subjectLabel) ?></div>

                <?php if ($statusNorm === 'ditolak' && $reviewNotes !== ''): ?>
                  <small class="text-danger">Alasan: <?= esc($reviewNotes) ?></small>
                <?php endif; ?>
              </td>
              <td><?= esc($category) ?></td>
              <td>
                <span class="badge bg-<?= esc($badge) ?>">
                  <?= esc($status !== '' ? $status : 'Diajukan') ?>
                </span>
              </td>
              <td>
                <small class="text-muted"><?= esc(sibk_fmt_dt($created)) ?></small>
              </td>
              <td>
                <a class="btn btn-sm btn-outline-primary"
                   href="<?= base_url('parent/violation-submissions/show/' . $id) ?>">
                  Detail
                </a>

                <?php if ($isEditable): ?>
                  <a class="btn btn-sm btn-outline-secondary"
                     href="<?= base_url('parent/violation-submissions/edit/' . $id) ?>">
                    Edit
                  </a>

                  <form action="<?= base_url('parent/violation-submissions/delete/' . $id) ?>"
                        method="post"
                        class="d-inline"
                        onsubmit="return confirm('Hapus pengaduan ini?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                  </form>
                <?php else: ?>
                  <span class="text-muted small">Terkunci</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links')): ?>
      <div class="mt-3">
        <?= $pager->links() ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
