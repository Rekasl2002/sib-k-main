<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$evaluations = is_array($evaluations ?? null) ? $evaluations : [];
$summary     = is_array($summary ?? null) ? $summary : [];
$questions   = is_array($questions ?? null) ? $questions : [];

if (! function_exists('eval_cat_badge')) {
    function eval_cat_badge(string $cat): string
    {
        return match ($cat) {
            'Diterima' => 'success',
            'Diterima dengan revisi' => 'info',
            'Belum diterima' => 'danger',
            default => 'secondary',
        };
    }
}
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0">Hasil Evaluasi Prototipe SIB-K</h4>
        <p class="text-muted mb-0"><?= (int) ($totalRespondents ?? 0) ?> responden telah mengisi.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= base_url('prototype') ?>" class="btn btn-light btn-sm"><i class="mdi mdi-arrow-left me-1"></i>Prototipe</a>
        <a href="<?= esc($exportUrl ?? base_url('prototype/evaluation/export')) ?>" class="btn btn-success btn-sm"><i class="mdi mdi-microsoft-excel me-1"></i>Ekspor Excel</a>
      </div>
    </div>
  </div>
</div>

<?php if (session('error')): ?>
  <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Daftar Responden</h5></div>
  <div class="card-body">
    <?php if (! $evaluations): ?>
      <div class="text-center text-muted py-4">Belum ada responden yang mengisi evaluasi.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>No</th><th>Tanggal</th><th>Nama</th><th>Peran</th>
              <th>Fitur Dinilai</th><th>% Penerimaan</th><th>Kategori</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($evaluations as $i => $e): ?>
              <?php $acc = $e['acceptance'] ?? ['percent' => 0, 'category' => '-']; ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($e['submitted_at'] ?? '-') ?></td>
                <td class="fw-semibold"><?= esc($e['respondent_name'] ?? '-') ?></td>
                <td><?= esc($e['role_label'] ?? '-') ?></td>
                <td><?= (int) ($e['accessible_feature_count'] ?? 0) ?> fitur</td>
                <td><?= (int) $acc['percent'] ?>%</td>
                <td><span class="badge bg-<?= esc(eval_cat_badge($acc['category'])) ?>"><?= esc($acc['category']) ?></span></td>
                <td><a href="<?= base_url('prototype/evaluation/results/' . (int) $e['id']) ?>" class="btn btn-sm btn-info"><i class="mdi mdi-eye"></i></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Rekap Penerimaan per Fitur</h5>
    <small class="text-muted">"Diterima" dan "Diterima dengan Revisi" dihitung sebagai diterima. Kategori: &lt;50% belum diterima, 50&ndash;85% diterima dengan revisi, &gt;85% diterima.</small>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Fitur</th>
            <?php foreach ($questions as $no => $text): ?>
              <th class="text-center" title="<?= esc($text) ?>">P<?= (int) $no ?></th>
            <?php endforeach; ?>
            <th class="text-center">Rata-rata</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($summary as $feat): ?>
            <?php
              $sum = 0; $cnt = 0;
              foreach ($feat['questions'] as $cell) { $sum += $cell['percent']; $cnt++; }
              $avg = $cnt ? (int) round($sum / $cnt) : 0;
            ?>
            <tr>
              <td class="fw-semibold"><?= esc($feat['title']) ?> <span class="badge bg-light text-dark border"><?= esc($feat['category']) ?></span></td>
              <?php foreach ($questions as $no => $text): ?>
                <?php $cell = $feat['questions'][$no]; ?>
                <td class="text-center">
                  <?php if ($cell['total'] > 0): ?>
                    <div class="fw-semibold"><?= (int) $cell['percent'] ?>%</div>
                    <small class="text-muted" title="Diterima / Revisi / Belum"><?= (int) $cell['diterima'] ?>/<?= (int) $cell['revisi'] ?>/<?= (int) $cell['belum'] ?></small>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td class="text-center"><span class="badge bg-<?= esc(eval_cat_badge($cnt ? (($avg < 50) ? 'Belum diterima' : (($avg <= 85) ? 'Diterima dengan revisi' : 'Diterima')) : '-')) ?>"><?= $avg ?>%</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <small class="text-muted d-block mt-2">Format angka kecil tiap sel: Diterima / Diterima dengan Revisi / Belum Diterima.</small>
  </div>
</div>
<?= $this->endSection() ?>
