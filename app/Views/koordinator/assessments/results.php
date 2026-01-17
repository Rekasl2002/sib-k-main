<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="row mb-3">
    <div class="col">
      <h4 class="mb-0">Hasil Asesmen</h4>
      <div class="text-muted">
        <?= esc($assessment['title'] ?? '—') ?>
      </div>
    </div>
    <div class="col text-end">
      <a class="btn btn-outline-secondary"
         href="<?= site_url('koordinator/assessments') ?>">← Kembali</a>
      <a class="btn btn-primary"
         href="<?= site_url('koordinator/assessments/'.$assessment['id'].'/results/export') ?>">
         Ekspor CSV
      </a>
    </div>
  </div>

  <?php if (empty($results)): ?>
    <div class="alert alert-info">Belum ada hasil untuk asesmen ini.</div>
  <?php else: ?>
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead>
            <tr>
              <th style="width:15%">NIS</th>
              <th style="width:35%">Nama</th>
              <th style="width:10%">Skor</th>
              <th style="width:20%">Selesai Pada</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $r): ?>
              <tr>
                <td><?= esc($r['student_nis'] ?? '') ?></td>
                <td><?= esc($r['student_name'] ?? '') ?></td>
                <td><?= esc($r['score'] ?? '') ?></td>
                <td><?= esc($r['completed_at'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
