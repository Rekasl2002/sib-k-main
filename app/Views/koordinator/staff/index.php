<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Manajemen Staf (Guru BK & Wali Kelas)</h4>
    <a href="<?= route_to('koordinator.staff.create'); ?>" class="btn btn-primary">Tambah Akun</a>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Username</th>
            <th>Email</th>
            <th>Status</th>
            <th style="width:180px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; foreach (($staff ?? []) as $u): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= esc($u['full_name']); ?></td>
              <td><?= esc($u['username']); ?></td>
              <td><?= esc($u['email']); ?></td>
              <td>
                <?php if ((int)($u['is_active'] ?? 0) === 1): ?>
                  <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="<?= route_to('koordinator.staff.edit', $u['id']); ?>">Edit</a>
                <form class="d-inline" method="post" action="<?= route_to('koordinator.staff.toggle', $u['id']); ?>">
                  <?= csrf_field(); ?>
                  <button type="submit" class="btn btn-sm btn-outline-warning">Toggle</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    <?= $pager->links(); ?>
  </div>
</div>
<?= $this->endSection(); ?>