<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$evaluation    = is_array($evaluation ?? null) ? $evaluation : [];
$grouped       = is_array($grouped ?? null) ? $grouped : [];
$featureNotes  = is_array($featureNotes ?? null) ? $featureNotes : [];
$questions     = is_array($questions ?? null) ? $questions : [];
$answerOptions = is_array($answerOptions ?? null) ? $answerOptions : [];
$reviewOptions = is_array($reviewOptions ?? null) ? $reviewOptions : [];
$pct           = is_array($pct ?? null) ? $pct : [
    'percent' => 0,
    'category' => '-',
    'diterima' => 0,
    'revisi' => 0,
    'belum' => 0,
    'score' => 0,
    'ideal_score' => 0,
    'total' => 0,
    'follow_up' => '-',
];

if (! function_exists('eval_ans_badge')) {
    function eval_ans_badge(string $a): string
    {
        return match ($a) {
            'diterima' => 'success',
            'revisi'   => 'info',
            'belum'    => 'danger',
            default    => 'secondary',
        };
    }
}

if (! function_exists('eval_detail_cat_badge')) {
    function eval_detail_cat_badge(string $cat): string
    {
        return match ($cat) {
            'Diterima' => 'success',
            'Diterima dengan revisi' => 'info',
            'Belum diterima' => 'danger',
            default => 'secondary',
        };
    }
}

if (! function_exists('eval_detail_percent')) {
    function eval_detail_percent($value): string
    {
        return number_format((float) $value, 1, ',', '.') . '%';
    }
}
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0">Detail Evaluasi: <?= esc($evaluation['respondent_name'] ?? '-') ?></h4>
        <p class="text-muted mb-0"><?= esc($evaluation['role_label'] ?? '-') ?> &middot; <?= esc($evaluation['submitted_at'] ?? '-') ?></p>
      </div>
      <a href="<?= base_url('prototype/evaluation/results') ?>" class="btn btn-light btn-sm"><i class="mdi mdi-arrow-left me-1"></i>Daftar Hasil</a>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Identitas</h5></div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr><th width="45%">Nama</th><td><?= esc($evaluation['respondent_name'] ?? '-') ?></td></tr>
          <tr><th>Peran</th><td><?= esc($evaluation['role_label'] ?? '-') ?></td></tr>
          <tr><th>Kelas/Hubungan/Peran</th><td><?= esc($evaluation['respondent_relation'] ?? '-') ?></td></tr>
          <tr><th>Sudah lihat prototipe</th><td><?= esc($reviewOptions[$evaluation['reviewed_prototype'] ?? ''] ?? ($evaluation['reviewed_prototype'] ?? '-')) ?></td></tr>
          <tr><th>Bersedia</th><td><?= !empty($evaluation['consent_participate']) ? 'Ya' : 'Tidak' ?></td></tr>
          <tr><th>Setuju data dipakai</th><td><?= !empty($evaluation['consent_data_usage']) ? 'Ya' : 'Tidak' ?></td></tr>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-body text-center">
        <div class="text-muted">Persentase Penerimaan</div>
        <div class="display-6 fw-bold"><?= eval_detail_percent($pct['percent']) ?></div>
        <span class="badge bg-<?= esc(eval_detail_cat_badge($pct['category'])) ?>"><?= esc($pct['category']) ?></span>
        <div class="small text-muted mt-2">
          Total skor <?= (int) $pct['score'] ?> dari skor ideal <?= (int) $pct['ideal_score'] ?>.
        </div>
        <div class="small text-muted">
          Diterima/Revisi/Belum: <?= (int) $pct['diterima'] ?>/<?= (int) $pct['revisi'] ?>/<?= (int) $pct['belum'] ?> dari <?= (int) $pct['total'] ?> item.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Tindak Lanjut</h5></div>
      <div class="card-body">
        <p class="mb-0 small"><?= esc($pct['follow_up']) ?></p>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <?php foreach ($grouped as $key => $feat): ?>
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><?= esc($feat['title'] ?? $key) ?></h5>
          <span class="badge bg-light text-dark border"><?= esc($feat['category'] ?? '') ?></span>
        </div>
        <div class="card-body">
          <table class="table table-sm align-middle mb-0">
            <tbody>
              <?php foreach ($questions as $no => $text): ?>
                <?php $a = $feat['answers'][$no] ?? ''; ?>
                <tr>
                  <td><span class="text-muted me-1"><?= (int) $no ?>.</span><?= esc($text) ?></td>
                  <td width="34%" class="text-end">
                    <span class="badge bg-<?= esc(eval_ans_badge($a)) ?>"><?= esc($answerOptions[$a] ?? '-') ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (! empty($featureNotes[$key])): ?>
            <div class="bg-light rounded p-3 border mt-3 mb-0">
              <div class="fw-semibold small mb-1"><i class="mdi mdi-note-edit-outline me-1"></i>Catatan/Revisi fitur ini:</div>
              <div class="small"><?= nl2br(esc($featureNotes[$key])) ?></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="card">
      <div class="card-header"><h5 class="mb-0">Saran / Revisi / Masukan Keseluruhan</h5></div>
      <div class="card-body">
        <?php if (! empty($evaluation['suggestions'])): ?>
          <p class="mb-0"><?= nl2br(esc($evaluation['suggestions'])) ?></p>
        <?php else: ?>
          <p class="text-muted mb-0">Tidak ada masukan tambahan.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
