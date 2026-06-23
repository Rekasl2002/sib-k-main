<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .reset-requests .access-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .reset-requests .metric-value {
    font-size: 28px;
    line-height: 1;
    font-weight: 700;
  }
  .reset-requests .access-table th,
  .reset-requests .access-table td {
    vertical-align: middle;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$rows    = is_array($rows ?? null) ? $rows : [];
$stats   = is_array($stats ?? null) ? $stats : [];
$filters = is_array($filters ?? null) ? $filters : [];

$statusBadge = static function (string $status): string {
    switch ($status) {
        case 'resolved':
            return '<span class="badge bg-success">Selesai</span>';
        case 'notified':
            return '<span class="badge bg-info text-dark">Diberitahukan ke Admin</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Menunggu</span>';
        default:
            return '<span class="badge bg-light text-dark">' . esc($status ?: '-') . '</span>';
    }
};
?>

<div class="reset-requests">
  <nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Permintaan Reset Password</li>
    </ol>
  </nav>

  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <h2 class="page-title mb-2">Permintaan Reset Password</h2>
      <p class="text-muted mb-0">
        Daftar permintaan lupa/reset password dari halaman login. Buka halaman pengguna untuk mereset password,
        lalu tandai permintaan sebagai <strong>Selesai</strong>. Reset password tidak memerlukan verifikasi email.
      </p>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="<?= base_url('admin/users') ?>" class="btn btn-primary">
        <i class="mdi mdi-account-cog-outline me-1"></i> Kelola Pengguna
      </a>
    </div>
  </div>

  <?php foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $class): ?>
    <?php if (session()->getFlashdata($key)): ?>
      <div class="alert alert-<?= esc($class) ?>"><?= esc(session()->getFlashdata($key)) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card access-card h-100">
        <div class="card-body">
          <div class="metric-value"><?= (int) ($stats['total'] ?? 0) ?></div>
          <div class="text-muted">Total permintaan</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card access-card h-100">
        <div class="card-body">
          <div class="metric-value text-warning"><?= (int) ($stats['open'] ?? 0) ?></div>
          <div class="text-muted">Belum ditangani</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card access-card h-100">
        <div class="card-body">
          <div class="metric-value text-success"><?= (int) ($stats['resolved'] ?? 0) ?></div>
          <div class="text-muted">Sudah selesai</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card access-card mb-4">
    <div class="card-body">
      <form method="get" action="<?= base_url('admin/password-reset-requests') ?>" class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Cari permintaan</label>
          <input type="text" name="search" class="form-control" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Nama, username, email, atau nomor telepon">
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">Semua</option>
            <option value="open" <?= (($filters['status'] ?? '') === 'open') ? 'selected' : '' ?>>Belum ditangani</option>
            <option value="resolved" <?= (($filters['status'] ?? '') === 'resolved') ? 'selected' : '' ?>>Sudah selesai</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">
            <i class="mdi mdi-filter-outline me-1"></i> Filter
          </button>
          <a href="<?= base_url('admin/password-reset-requests') ?>" class="btn btn-light">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card access-card">
    <div class="card-header bg-white">
      <h5 class="mb-0">Daftar Permintaan</h5>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle access-table mb-0">
        <thead>
          <tr>
            <th>Pemohon</th>
            <th>Kontak Diisi</th>
            <th>Status</th>
            <th>Waktu</th>
            <th>Diselesaikan</th>
            <th style="width: 240px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! $rows): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Belum ada permintaan reset password.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <?php
                $userId   = (int) ($row['user_id'] ?? 0);
                $status   = (string) ($row['status'] ?? '');
                $isDone   = $status === 'resolved';
                $email    = trim((string) ($row['email'] ?? ''));
                $phone    = trim((string) ($row['phone'] ?? ''));
                $keyword  = $email !== '' ? $email : $phone;
                $resetUrl = $userId > 0
                    ? base_url('admin/users/edit/' . $userId)
                    : base_url('admin/users' . ($keyword !== '' ? ('?search=' . rawurlencode($keyword)) : ''));
              ?>
              <tr>
                <td>
                  <?php if ($userId > 0): ?>
                    <div class="fw-semibold"><?= esc($row['user_full_name'] ?? '-') ?></div>
                    <small class="text-muted">
                      <?= esc($row['user_username'] ?? '-') ?>
                      <?php if (! empty($row['user_role'])): ?> &middot; <?= esc($row['user_role']) ?><?php endif; ?>
                    </small>
                  <?php else: ?>
                    <span class="badge bg-light text-dark">Akun tidak terdeteksi</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div><i class="mdi mdi-email-outline me-1 text-muted"></i><?= esc($email !== '' ? $email : '-') ?></div>
                  <div><i class="mdi mdi-phone-outline me-1 text-muted"></i><?= esc($phone !== '' ? $phone : '-') ?></div>
                </td>
                <td><?= $statusBadge($status) ?></td>
                <td><small class="text-muted"><?= esc($row['requested_at'] ?? '-') ?></small></td>
                <td>
                  <?php if ($isDone): ?>
                    <div class="small"><?= esc($row['resolved_by_name'] ?? 'Admin') ?></div>
                    <small class="text-muted"><?= esc($row['resolved_at'] ?? '') ?></small>
                  <?php else: ?>
                    <small class="text-muted">-</small>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex flex-column gap-2">
                    <a href="<?= esc($resetUrl) ?>" class="btn btn-sm btn-outline-primary">
                      <i class="mdi mdi-lock-reset me-1"></i> Buka &amp; Reset
                    </a>
                    <?php if (! $isDone): ?>
                      <form method="post" action="<?= base_url('admin/password-reset-requests/resolve/' . (int) ($row['id'] ?? 0)) ?>" onsubmit="return confirm('Tandai permintaan ini sebagai selesai?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-success w-100">
                          <i class="mdi mdi-check-circle-outline me-1"></i> Tandai Selesai
                        </button>
                      </form>
                    <?php else: ?>
                      <button type="button" class="btn btn-sm btn-light" disabled>
                        <i class="mdi mdi-check me-1"></i> Selesai
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
