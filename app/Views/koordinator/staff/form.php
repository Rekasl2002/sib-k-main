<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h5 class="mb-0"><?= isset($user) ? 'Edit Akun' : 'Tambah Akun'; ?></h5></div>
        <div class="card-body">
          <form method="post" action="<?= isset($user) ? route_to('koordinator.staff.update', $user['id']) : route_to('koordinator.staff.store'); ?>">
            <?= csrf_field(); ?>

            <div class="mb-3">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" name="full_name" class="form-control" value="<?= esc($user['full_name'] ?? old('full_name')); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" value="<?= esc($user['username'] ?? old('username')); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= esc($user['email'] ?? old('email')); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Peran</label>
              <select name="role_id" class="form-select" required>
                <option value="">-- Pilih --</option>
                <?php foreach(($roles ?? []) as $r): ?>
                  <option value="<?= $r['id']; ?>" <?= (isset($user) && (int)$user['role_id'] === (int)$r['id']) ? 'selected' : '' ?>><?= esc($r['role_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Password <?= isset($user) ? '(biarkan kosong jika tidak ganti)' : '' ?></label>
              <input type="password" name="password" class="form-control" <?= isset($user) ? '' : 'required' ?>>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-primary" type="submit">Simpan</button>
              <a class="btn btn-light" href="<?= route_to('koordinator.staff.index'); ?>">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection(); ?>