<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="mb-0">Asesmen — Koordinator BK</h4>
      </div>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <?php if (empty($assessments)): ?>
    <div class="alert alert-info">
      Belum ada data asesmen untuk ditampilkan.
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width:40%">Judul</th>
              <th>Status</th>
              <th>Waktu</th>
              <th class="text-end">Total Hasil</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assessments as $a): ?>
              <?php
                $id    = (int)$a['id'];
                $total = $totalByAssessment[$id] ?? 0;
              ?>
              <tr>
                <td>
                  <strong><?= esc($a['title']) ?></strong><br>
                  <small class="text-muted"><?= esc($a['description'] ?? '') ?></small>
                </td>
                <td>
                  <?php if (!empty($a['is_published'])): ?>
                    <span class="badge bg-success">Dipublikasikan</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Draft</span>
                  <?php endif; ?>
                </td>
                <td>
                  <small>
                    <?= esc($a['opens_at'] ?? '-') ?> — <?= esc($a['closes_at'] ?? '-') ?>
                  </small>
                </td>
                <td class="text-end"><?= number_format($total) ?></td>
                <td class="text-center">
                  <a class="btn btn-sm btn-outline-primary"
                     href="<?= site_url('koordinator/assessments/'.$id.'/results') ?>">
                     Lihat Hasil
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
