<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-body">
    <h4 class="card-title mb-3">Pelanggaran: <?= esc($student['full_name']) ?></h4>

    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Tingkat</th>
            <th>Poin</th>
            <th>Deskripsi</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($violations as $v): ?>
          <tr>
            <td><?= esc($v['violation_date']) ?></td>
            <td><?= esc($v['category_name']) ?></td>
            <td><?= esc($v['severity_level']) ?></td>
            <td><?= esc($v['points']) ?></td>
            <td><?= esc($v['description']) ?></td>
            <td><?= esc($v['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
