<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  // Fallback aman untuk daftar permission yang sudah dimiliki role
  $assigned = $assignedIds ?? $owned ?? $ownedIds ?? [];
  $assigned = is_array($assigned) ? array_map('intval', $assigned) : [];
  // Helper kecil untuk ambil kolom dengan fallback
  $roleName = $role['role_name'] ?? $role['name'] ?? '';
?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Edit Peran</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/roles') ?>">Peran</a></li>
                    <li class="breadcrumb-item active">Edit Peran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">
          <i class="mdi mdi-shield-key-outline me-2"></i>
          Edit Peran & Izin — <?= esc($roleName) ?>
        </h4>

        <?php if (session()->getFlashdata('success')): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/roles/assign-permissions/' . (int)$role['id']) ?>" method="post">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2">
              <span>Daftar Izin</span>
              <button type="button" id="checkAll" class="btn btn-sm btn-outline-secondary">Pilih Semua</button>
              <button type="button" id="uncheckAll" class="btn btn-sm btn-outline-secondary">Kosongkan</button>
            </label>

            <div class="mb-3">
              <input type="text" class="form-control" id="permSearch" placeholder="Cari permission (mis. manage_roles)">
            </div>

            <div class="row" id="permList">
              <?php if (!empty($permissions)): ?>
                <?php foreach ($permissions as $perm): 
                  $pid   = (int)($perm['id'] ?? 0);
                  $pname = $perm['permission_name'] ?? $perm['key'] ?? '';
                  $pdesc = $perm['description'] ?? ($perm['name'] ?? '');
                  $checked = in_array($pid, $assigned, true) ? 'checked' : '';
                ?>
                  <div class="col-md-6 perm-item" data-name="<?= esc(strtolower($pname)) ?>">
                    <div class="form-check mb-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="perm-<?= $pid ?>"
                        name="permissions[]"
                        value="<?= $pid ?>"
                        <?= $checked ?>
                      >
                      <label class="form-check-label" for="perm-<?= $pid ?>">
                        <code><?= esc($pname) ?></code>
                        <?php if ($pdesc !== ''): ?>
                          — <span class="text-muted"><?= esc($pdesc) ?></span>
                        <?php endif; ?>
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="col-12">
                  <div class="alert alert-warning mb-0">Belum ada data permission.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex justify-content-between">
            <a class="btn btn-light" href="<?= base_url('admin/roles') ?>">
              <i class="mdi mdi-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="mdi mdi-content-save me-1"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-2">Ringkasan Peran</h5>
        <p class="mb-1"><strong>Nama:</strong> <?= esc($roleName) ?></p>
        <?php if (isset($role['description']) && $role['description'] !== ''): ?>
          <p class="mb-0 text-muted"><strong>Deskripsi:</strong> <?= esc($role['description']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    const checkAllBtn   = document.getElementById('checkAll');
    const uncheckAllBtn = document.getElementById('uncheckAll');
    const searchInput   = document.getElementById('permSearch');
    const list          = document.getElementById('permList');

    if (checkAllBtn) checkAllBtn.addEventListener('click', () => {
      list.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
    });
    if (uncheckAllBtn) uncheckAllBtn.addEventListener('click', () => {
      list.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
    });
    if (searchInput) searchInput.addEventListener('input', (e) => {
      const q = e.target.value.trim().toLowerCase();
      list.querySelectorAll('.perm-item').forEach(it => {
        const name = (it.getAttribute('data-name') || '');
        it.style.display = name.includes(q) ? '' : 'none';
      });
    });
  })();
</script>

<?= $this->endSection() ?>
