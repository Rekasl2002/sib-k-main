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

if (! function_exists('eval_percent')) {
    function eval_percent($value): string
    {
        return number_format((float) $value, 1, ',', '.') . '%';
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
  <div class="card-body">
    <h5 class="mb-2">Rumus Penerimaan Prototipe</h5>
    <p class="text-muted mb-2">
      Evaluasi dihitung dengan bobot jawaban: Diterima = 3, Diterima dengan Revisi = 2, dan Belum Diterima = 1.
      Fitur yang tidak dapat diakses oleh suatu peran tidak dinilai oleh peran tersebut, sehingga tidak masuk sebagai item kosong.
    </p>
    <div class="row g-3">
      <div class="col-md-4">
        <div class="border rounded p-3 h-100">
          <div class="fw-semibold">Total Skor</div>
          <div class="small text-muted">(Diterima x 3) + (Revisi x 2) + (Belum x 1)</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="border rounded p-3 h-100">
          <div class="fw-semibold">Skor Ideal Maksimal</div>
          <div class="small text-muted">Total item yang dievaluasi x 3</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="border rounded p-3 h-100">
          <div class="fw-semibold">Persentase Penerimaan</div>
          <div class="small text-muted">(Total Skor / Skor Ideal Maksimal) x 100%</div>
        </div>
      </div>
    </div>
    <div class="table-responsive mt-3">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr><th>Persentase</th><th>Kategori</th><th>Tindak Lanjut</th></tr>
        </thead>
        <tbody>
          <tr><td>&lt; 60%</td><td><span class="badge bg-danger">Belum diterima</span></td><td>Rancangan perlu dikaji ulang dan diperbaiki secara menyeluruh.</td></tr>
          <tr><td>60% - 80%</td><td><span class="badge bg-info">Diterima dengan revisi</span></td><td>Rancangan dapat dilanjutkan setelah catatan revisi diperbaiki.</td></tr>
          <tr><td>&gt; 80%</td><td><span class="badge bg-success">Diterima</span></td><td>Rancangan dapat dilanjutkan ke tahap implementasi dengan tetap memperhatikan catatan kecil.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

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
              <th>No</th>
              <th>Tanggal</th>
              <th>Nama</th>
              <th>Peran</th>
              <th>Fitur Dinilai</th>
              <th>Item</th>
              <th>Skor</th>
              <th>% Penerimaan</th>
              <th>Kategori</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($evaluations as $i => $e): ?>
              <?php $acc = $e['acceptance'] ?? ['percent' => 0, 'category' => '-', 'score' => 0, 'ideal_score' => 0, 'total' => 0]; ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($e['submitted_at'] ?? '-') ?></td>
                <td class="fw-semibold"><?= esc($e['respondent_name'] ?? '-') ?></td>
                <td><?= esc($e['role_label'] ?? '-') ?></td>
                <td><?= (int) ($e['accessible_feature_count'] ?? 0) ?> fitur</td>
                <td><?= (int) $acc['total'] ?></td>
                <td><?= (int) $acc['score'] ?> / <?= (int) $acc['ideal_score'] ?></td>
                <td><?= eval_percent($acc['percent']) ?></td>
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

<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">Rekap Penerimaan per Fitur</h5>
    <small class="text-muted">Rekap dihitung dari seluruh item evaluasi yang benar-benar dijawab oleh responden pada fitur tersebut.</small>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Fitur</th>
            <th class="text-center">Item</th>
            <th class="text-center">Diterima</th>
            <th class="text-center">Revisi</th>
            <th class="text-center">Belum</th>
            <th class="text-center">Skor</th>
            <th class="text-center">% Penerimaan</th>
            <th class="text-center">Kategori</th>
            <th>Tindak Lanjut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($summary as $feat): ?>
            <?php $overall = $feat['overall'] ?? ['total' => 0, 'diterima' => 0, 'revisi' => 0, 'belum' => 0, 'score' => 0, 'ideal_score' => 0, 'percent' => 0, 'category' => '-', 'follow_up' => '-']; ?>
            <tr>
              <td class="fw-semibold">
                <?= esc($feat['title']) ?>
                <span class="badge bg-light text-dark border"><?= esc($feat['category']) ?></span>
              </td>
              <td class="text-center"><?= (int) $overall['total'] ?></td>
              <td class="text-center"><?= (int) $overall['diterima'] ?></td>
              <td class="text-center"><?= (int) $overall['revisi'] ?></td>
              <td class="text-center"><?= (int) $overall['belum'] ?></td>
              <td class="text-center"><?= (int) $overall['score'] ?> / <?= (int) $overall['ideal_score'] ?></td>
              <td class="text-center fw-semibold"><?= $overall['total'] > 0 ? eval_percent($overall['percent']) : '-' ?></td>
              <td class="text-center"><span class="badge bg-<?= esc(eval_cat_badge($overall['category'])) ?>"><?= esc($overall['category']) ?></span></td>
              <td class="small"><?= esc($overall['follow_up']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Rincian per Kriteria Evaluasi</h5>
    <small class="text-muted">Format angka kecil tiap sel: Diterima / Diterima dengan Revisi / Belum Diterima.</small>
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
          </tr>
        </thead>
        <tbody>
          <?php foreach ($summary as $feat): ?>
            <tr>
              <td class="fw-semibold"><?= esc($feat['title']) ?></td>
              <?php foreach ($questions as $no => $text): ?>
                <?php $cell = $feat['questions'][$no]; ?>
                <td class="text-center">
                  <?php if ($cell['total'] > 0): ?>
                    <div class="fw-semibold"><?= eval_percent($cell['percent']) ?></div>
                    <small class="text-muted" title="Diterima / Revisi / Belum"><?= (int) $cell['diterima'] ?>/<?= (int) $cell['revisi'] ?>/<?= (int) $cell['belum'] ?></small>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
