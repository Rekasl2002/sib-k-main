<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Anak Saya</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Orang Tua</a></li>
          <li class="breadcrumb-item active">Anak Saya</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Nama</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach($students as $s): ?>
          <tr>
            <td><?= esc($s['full_name']) ?></td>
            <td>
              <a class="btn btn-outline-primary btn-sm" href="<?= site_url('parent/child/'.$s['id'].'/profile') ?>">Profil</a>
                  <a class="btn btn-outline-success btn-sm" href="<?= site_url('parent/jadwal-bk') ?>">Jadwal BK</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
