<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Profil Saya</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Orang Tua</a></li>
          <li class="breadcrumb-item active">Profil Saya</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">

        <?php if(session('success')): ?>
          <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if(session('errors')): ?>
          <div class="alert alert-danger">
            <?php foreach(session('errors') as $e): ?>
              <div>- <?= esc($e) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= route_to('parent.profile.update') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="full_name" class="form-control" value="<?= esc($user['full_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= esc($user['email']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">No. HP</label>
            <input type="text" name="phone" class="form-control" value="<?= esc($user['phone']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Foto Profil (opsional)</label>
            <input type="file" name="photo" class="form-control">
            <?php if(!empty($user['profile_photo'])): ?>
              <div class="mt-2">
                <img src="/<?= esc($user['profile_photo']) ?>" class="img-thumbnail" style="max-height:120px">
              </div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary">Simpan</button>
        </form>

      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
