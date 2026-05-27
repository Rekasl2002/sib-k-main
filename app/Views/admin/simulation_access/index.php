<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .simulation-access .access-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .simulation-access .metric-value {
    font-size: 28px;
    line-height: 1;
    font-weight: 700;
  }
  .simulation-access .access-table th,
  .simulation-access .access-table td {
    vertical-align: middle;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$users   = is_array($users ?? null) ? $users : [];
$stats   = is_array($stats ?? null) ? $stats : [];
$filters = is_array($filters ?? null) ? $filters : [];
?>

<div class="simulation-access">
  <nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Akses Prototipe/Simulasi</li>
    </ol>
  </nav>

  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <h2 class="page-title mb-2">Akses Prototipe/Simulasi</h2>
      <p class="text-muted mb-0">
        Admin dapat menentukan akun non-admin yang boleh membuka menu Simulasi Fitur dan Prototipe Skripsi. Admin selalu mendapat akses otomatis.
      </p>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="<?= base_url('simulation') ?>" class="btn btn-outline-primary me-2">
        <i class="mdi mdi-monitor-dashboard me-1"></i> Simulasi
      </a>
      <a href="<?= base_url('prototype') ?>" class="btn btn-primary">
        <i class="mdi mdi-test-tube me-1"></i> Prototipe
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
          <div class="metric-value"><?= (int) ($stats['total_users'] ?? 0) ?></div>
          <div class="text-muted">Akun ditampilkan</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card access-card h-100">
        <div class="card-body">
          <div class="metric-value"><?= (int) ($stats['granted'] ?? 0) ?></div>
          <div class="text-muted">Akses diberikan</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card access-card h-100">
        <div class="card-body">
          <div class="metric-value"><?= (int) ($stats['automatic'] ?? 0) ?></div>
          <div class="text-muted">Admin otomatis</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card access-card mb-4">
    <div class="card-body">
      <form method="get" action="<?= base_url('admin/simulation-access') ?>" class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Cari akun</label>
          <input type="text" name="search" class="form-control" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Nama, username, email, atau role">
        </div>
        <div class="col-md-3">
          <label class="form-label">Status akses</label>
          <select name="status" class="form-select">
            <option value="">Semua</option>
            <option value="granted" <?= (($filters['status'] ?? '') === 'granted') ? 'selected' : '' ?>>Diberi akses</option>
            <option value="not_granted" <?= (($filters['status'] ?? '') === 'not_granted') ? 'selected' : '' ?>>Belum diberi akses</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">
            <i class="mdi mdi-filter-outline me-1"></i> Filter
          </button>
          <a href="<?= base_url('admin/simulation-access') ?>" class="btn btn-light">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card access-card">
    <div class="card-header bg-white">
      <h5 class="mb-0">Daftar Akses Akun</h5>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle access-table mb-0">
        <thead>
          <tr>
            <th>Akun</th>
            <th>Role</th>
            <th>Status Akun</th>
            <th>Akses Simulasi</th>
            <th>Catatan</th>
            <th style="width: 260px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (! $users): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Tidak ada akun yang cocok.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <?php
                $role = strtolower(trim((string) ($user['role_name'] ?? '')));
                $isAdmin = (int) ($user['role_id'] ?? 0) === 1 || in_array($role, ['admin', 'administrator'], true);
                $hasAccess = $isAdmin || (int) ($user['simulation_access'] ?? 0) === 1;
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= esc($user['full_name'] ?? '-') ?></div>
                  <small class="text-muted"><?= esc($user['username'] ?? '-') ?> &middot; <?= esc($user['email'] ?? '-') ?></small>
                </td>
                <td><?= esc($user['role_name'] ?? '-') ?></td>
                <td>
                  <?php if ((int) ($user['is_active'] ?? 0) === 1): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($isAdmin): ?>
                    <span class="badge bg-primary">Otomatis admin</span>
                  <?php elseif ($hasAccess): ?>
                    <span class="badge bg-success">Diberi akses</span>
                    <?php if (! empty($user['granted_at'])): ?>
                      <div><small class="text-muted">Sejak <?= esc($user['granted_at']) ?></small></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="badge bg-light text-dark">Belum ada akses</span>
                  <?php endif; ?>
                </td>
                <td>
                  <small class="text-muted"><?= esc($user['notes'] ?? '-') ?></small>
                </td>
                <td>
                  <?php if ($isAdmin): ?>
                    <button type="button" class="btn btn-sm btn-light" disabled>Akses otomatis</button>
                  <?php elseif ($hasAccess): ?>
                    <form method="post" action="<?= base_url('admin/simulation-access/revoke') ?>" class="d-inline" onsubmit="return confirm('Cabut akses simulasi akun ini?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="user_id" value="<?= (int) ($user['id'] ?? 0) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="mdi mdi-lock-remove-outline me-1"></i> Cabut
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= base_url('admin/simulation-access/grant') ?>" class="d-flex gap-2">
                      <?= csrf_field() ?>
                      <input type="hidden" name="user_id" value="<?= (int) ($user['id'] ?? 0) ?>">
                      <input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan opsional">
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-lock-open-variant-outline me-1"></i> Beri
                      </button>
                    </form>
                  <?php endif; ?>
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
