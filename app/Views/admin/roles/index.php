<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Start Page Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Manajemen Peran</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Peran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Role</th><th>Deskripsi</th><th>Izin</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($roles as $r): ?>
        <tr>
          <td><?= esc($r['role_name']) ?></td>
          <td><?= esc($r['description']) ?></td>
          <td><span class="badge bg-info"><?= (int)$r['permission_count'] ?> izin</span></td>
          <td class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="<?= route_to('admin.roles.edit', $r['id']) ?>">Edit</a>
            <form method="post" action="<?= route_to('admin.roles.delete', $r['id']) ?>" onsubmit="return confirm('Hapus role ini?')">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?= $this->endSection() ?>
