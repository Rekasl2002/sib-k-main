<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">TAMBAH PERAN</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/roles') ?>">Peran</a></li>
                    <li class="breadcrumb-item active">Tambah Peran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

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

<form method="post" action="<?= route_to('admin.roles.store') ?>">
  <?= csrf_field() ?>

  <div class="card mb-3">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Nama Role</label>
        <input type="text" name="role_name" class="form-control" required
               value="<?= esc(old('role_name')) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control" rows="2"><?= esc(old('description')) ?></textarea>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h5 class="m-0">Permissions</h5>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-select-all">Pilih Semua</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-clear-all">Kosongkan</button>
        </div>
      </div>

      <div class="mb-3">
        <input type="text" class="form-control" id="perm-search" placeholder="Cari permission (mis. manage_roles)">
      </div>

      <div class="row" id="perm-list">
        <?php if (!empty($permissions)): ?>
          <?php
            // FIX: jangan beri default array ke old(); cek tipe dulu agar linter tidak protes
            $oldVal     = old('permissions');                 // bisa array|null|string
            $oldChecked = is_array($oldVal) ? array_map('intval', $oldVal) : [];
          ?>
          <?php foreach ($permissions as $p):
            $pid        = (int)($p['id'] ?? 0);
            $pname      = (string)($p['permission_name'] ?? '');
            $pdesc      = (string)($p['description'] ?? '');
            $checked    = in_array($pid, $oldChecked, true) ? 'checked' : '';
            $checkboxId = 'perm_' . $pid;
          ?>
            <div class="col-md-4 mb-2 perm-item" data-name="<?= esc(strtolower($pname)) ?>">
              <label class="d-flex align-items-start gap-2" for="<?= $checkboxId ?>">
                <input type="checkbox" id="<?= $checkboxId ?>" name="permissions[]" value="<?= $pid ?>" <?= $checked ?>>
                <span>
                  <code><?= esc($pname) ?></code><br>
                  <small class="text-muted"><?= esc($pdesc) ?></small>
                </span>
              </label>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="alert alert-warning mb-0">Belum ada data permission.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= route_to('admin.roles') ?>" class="btn btn-light">Batal</a>
    <button class="btn btn-primary">Simpan</button>
  </div>
</form>

<script>
(function(){
  const list   = document.getElementById('perm-list');
  const btnAll = document.getElementById('btn-select-all');
  const btnClr = document.getElementById('btn-clear-all');
  const search = document.getElementById('perm-search');

  if (btnAll) btnAll.addEventListener('click', () => {
    list.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
  });
  if (btnClr) btnClr.addEventListener('click', () => {
    list.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
  });
  if (search) search.addEventListener('input', (e) => {
    const q = e.target.value.trim().toLowerCase();
    list.querySelectorAll('.perm-item').forEach(item => {
      const name = item.getAttribute('data-name') || '';
      item.style.display = name.includes(q) ? '' : 'none';
    });
  });
})();
</script>

<?= $this->endSection() ?>
