<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-body">
    <h4 class="card-title">Anak Saya</h4>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Nama</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach($students as $s): ?>
          <tr>
            <td><?= esc($s['full_name']) ?></td>
            <td>
              <a class="btn btn-outline-primary btn-sm" href="<?= site_url('parent/child/'.$s['id'].'/profile') ?>">Profil</a>
                  <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('parent/child/'.$s['id'].'/violations') ?>">Pelanggaran</a>
                  <a class="btn btn-outline-success btn-sm" href="<?= site_url('parent/child/'.$s['id'].'/sessions') ?>">Sesi</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
