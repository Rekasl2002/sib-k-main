<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// =============== Guard & defaults ===============
$assessment     = $assessment ?? [];
$statistics     = $statistics ?? [];
$questions      = $questions ?? [];
$topPerformers  = $topPerformers ?? [];
$aid            = (int)($assessment['id'] ?? 0);

// =============== Hitung Peserta & Status (akurat dari DB) ===============
// Prioritas: statistik yang dipass dari controller → fallback: agregasi langsung ke DB
$participants = (int)($statistics['total_participants']
    ?? $statistics['total_attempts']
    ?? $assessment['total_participants']
    ?? 0);

$statusCounts = [
    'Assigned'    => 0,
    'In Progress' => 0,
    'Completed'   => 0,
    'Graded'      => 0,
    'Expired'     => 0,
    'Abandoned'   => 0,
];

try {
    if ($aid > 0) {
        $db = \Config\Database::connect();

        // Peserta unik (COUNT DISTINCT student_id) untuk asesmen ini (abaikan soft-deleted)
        $rowP = $db->table('assessment_results')
            ->select('COUNT(DISTINCT student_id) AS cnt')
            ->where('assessment_id', $aid)
            ->where('deleted_at', null)
            ->get()->getRowArray();
        $participantsDb = (int)($rowP['cnt'] ?? 0);
        if ($participants === 0) {
            $participants = $participantsDb; // override jika statistik kosong
        }

        // Ringkasan status (jumlah baris per status)
        $rowsS = $db->table('assessment_results')
            ->select('status, COUNT(*) AS cnt')
            ->where('assessment_id', $aid)
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get()->getResultArray();

        foreach ($rowsS as $rs) {
            $s = (string)($rs['status'] ?? '');
            if (isset($statusCounts[$s])) {
                $statusCounts[$s] = (int)$rs['cnt'];
            }
        }
    }
} catch (\Throwable $e) {
    // Abaikan error agregasi untuk menjaga tampilan tetap jalan
}

// Turunan statistik lain
$avgScore   = (float)($statistics['average_score'] ?? 0);
$passRate   = (float)($statistics['pass_rate'] ?? 0);
$completedR = (int)($statistics['completed'] ?? ($statusCounts['Completed'] + $statusCounts['Graded']));
$inProgress = (int)($statistics['in_progress'] ?? $statusCounts['In Progress']);
$pctDone    = $participants > 0 ? round(($completedR / $participants) * 100, 1) : 0;

// Label target
$target = (string)($assessment['target_audience'] ?? 'All');
$targetDesc = $target;
if ($target === 'Class' && !empty($assessment['target_class_name'])) {
    $targetDesc .= ' · ' . $assessment['target_class_name'];
} elseif ($target === 'Grade' && !empty($assessment['target_grade'])) {
    $targetDesc .= ' · Tingkat ' . $assessment['target_grade'];
}
?>

<!-- Header -->
<div class="page-header mb-4">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h2 class="page-title mb-0">
        <i class="fas fa-clipboard-check me-2"></i>
        <?= esc($assessment['title'] ?? 'Asesmen') ?>
      </h2>
      <div class="text-muted">
        <span class="me-2">
          <i class="fas fa-tag me-1"></i><?= esc($assessment['assessment_type'] ?? '-') ?>
        </span>
        <span class="me-2">
          <i class="fas fa-bullseye me-1"></i><?= esc($targetDesc) ?>
        </span>
        <span>
          <?php if (!empty($assessment['is_published'])): ?>
            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Published</span>
          <?php else: ?>
            <span class="badge bg-warning"><i class="fas fa-file-alt me-1"></i>Draft</span>
          <?php endif; ?>
          <?php if (!empty($assessment['is_active'])): ?>
            <span class="badge bg-info ms-1"><i class="fas fa-circle me-1"></i>Active</span>
          <?php endif; ?>
        </span>
      </div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <a href="<?= base_url('counselor/assessments') ?>" class="btn btn-secondary me-2">
        <i class="fas fa-arrow-left me-2"></i>Kembali
      </a>
      <a href="<?= base_url('counselor/assessments/'.$aid.'/questions') ?>" class="btn btn-outline-primary me-2">
        <i class="fas fa-question-circle me-2"></i>Kelola Soal
      </a>
      <a href="<?= base_url('counselor/assessments/'.$aid.'/results') ?>" class="btn btn-outline-info me-2">
        <i class="fas fa-chart-bar me-2"></i>Hasil
      </a>
      <a href="<?= base_url('counselor/assessments/'.$aid.'/edit') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-edit me-2"></i>Edit
      </a>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
          <i class="fas fa-users text-primary fa-2x"></i>
        </div>
        <div>
          <div class="text-muted">Peserta</div>
          <div class="fs-3 fw-bold"><?= $participants ?></div>
          <small class="text-muted d-block mt-1">
            Assigned: <strong><?= (int)$statusCounts['Assigned'] ?></strong>
            &nbsp;•&nbsp; In Progress: <strong><?= (int)$inProgress ?></strong>
          </small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
          <i class="fas fa-check-circle text-success fa-2x"></i>
        </div>
        <div>
          <div class="text-muted">Selesai</div>
          <div class="fs-3 fw-bold"><?= (int)$completedR ?></div>
          <small class="text-muted"><?= $pctDone ?>%</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
          <i class="fas fa-chart-line text-info fa-2x"></i>
        </div>
        <div>
          <div class="text-muted">Nilai Rata-rata</div>
          <div class="fs-3 fw-bold"><?= number_format($avgScore, 1) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
          <i class="fas fa-trophy text-warning fa-2x"></i>
        </div>
        <div>
          <div class="text-muted">Pass Rate</div>
          <div class="fs-3 fw-bold"><?= number_format($passRate, 1) ?>%</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Left: Info & Questions -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white py-3"><h5 class="mb-0">Informasi Asesmen</h5></div>
      <div class="card-body">
        <?php if (!empty($assessment['description'])): ?>
          <div class="mb-3">
            <div class="text-muted small mb-1">Deskripsi</div>
            <div><?= esc($assessment['description']) ?></div>
          </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-4">
            <div class="text-muted small mb-1">Durasi</div>
            <div><i class="fas fa-clock me-1"></i><?= (int)($assessment['duration_minutes'] ?? 0) ?> menit</div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small mb-1">Nilai Lulus</div>
            <div><i class="fas fa-chart-line me-1"></i><?= (float)($assessment['passing_score'] ?? 0) ?>%</div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small mb-1">Maks. Percobaan</div>
            <div><i class="fas fa-redo me-1"></i><?= (int)($assessment['max_attempts'] ?? 1) ?> kali</div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-4">
            <div class="text-muted small mb-1">Tanggal Mulai</div>
            <div><?= !empty($assessment['start_date']) ? date('d/m/Y H:i', strtotime($assessment['start_date'])) : '-' ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small mb-1">Tanggal Selesai</div>
            <div><?= !empty($assessment['end_date']) ? date('d/m/Y H:i', strtotime($assessment['end_date'])) : '-' ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small mb-1">Total Soal</div>
            <div><span class="badge bg-primary"><?= (int)($assessment['total_questions'] ?? count($questions)) ?> Soal</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Questions -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Soal</h5>
        <a href="<?= base_url('counselor/assessments/'.$aid.'/questions') ?>" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-pencil-alt me-2"></i>Kelola Soal
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($questions)): ?>
          <div class="text-center py-4">
            <i class="fas fa-question-circle fa-2x text-muted mb-2"></i>
            <div class="text-muted">Belum ada soal.</div>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th width="6%">No</th>
                  <th>Pertanyaan</th>
                  <th width="16%">Tipe</th>
                  <th width="10%" class="text-center">Poin</th>
                </tr>
              </thead>
              <tbody>
              <?php $no=1; foreach ($questions as $q): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= esc($q['question_text'] ?? '') ?></td>
                  <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= esc($q['question_type'] ?? '-') ?></span></td>
                  <td class="text-center"><?= (float)($q['points'] ?? 0) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: Actions & Top performers -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white py-3"><h6 class="mb-0">Aksi</h6></div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="<?= base_url('counselor/assessments/'.$aid.'/assign') ?>" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>Tugaskan
          </a>

          <?php if (empty($assessment['is_published'])): ?>
            <form method="post" action="<?= base_url('counselor/assessments/'.$aid.'/publish') ?>" onsubmit="return confirm('Publikasikan asesmen ini?')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-success w-100"><i class="fas fa-share me-2"></i>Publikasi</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?= base_url('counselor/assessments/'.$aid.'/unpublish') ?>" onsubmit="return confirm('Batalkan publikasi asesmen ini?')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-warning w-100"><i class="fas fa-times-circle me-2"></i>Unpublish</button>
            </form>
          <?php endif; ?>

          <form method="post" action="<?= base_url('counselor/assessments/'.$aid.'/duplicate') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-secondary w-100"><i class="fas fa-copy me-2"></i>Duplikat</button>
          </form>

          <a href="<?= base_url('counselor/assessments/'.$aid.'/results') ?>" class="btn btn-outline-info">
            <i class="fas fa-chart-bar me-2"></i>Lihat Hasil
          </a>

          <!-- Opsional: sinkronkan penugasan ke results (membuat baris Assigned) -->
          <form method="post" action="<?= base_url('counselor/assessments/'.$aid.'/assign/sync') ?>" onsubmit="return confirm('Sinkronkan penugasan ke daftar hasil?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-dark w-100">
              <i class="fas fa-sync-alt me-2"></i>Sinkronkan Penugasan
            </button>
          </form>

          <form method="post" action="<?= base_url('counselor/assessments/'.$aid.'/delete') ?>" onsubmit="return confirm('Hapus asesmen ini beserta data terkait?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-trash me-2"></i>Hapus</button>
          </form>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top Performers</h6>
      </div>
      <div class="card-body">
        <?php if (empty($topPerformers)): ?>
          <div class="text-muted">Belum ada data.</div>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($topPerformers as $r): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold"><?= esc($r['student_name'] ?? '-') ?></div>
                  <small class="text-muted">
                    <?= esc($r['class_name'] ?? '-') ?> ·
                    NIS/NISN: <?= esc($r['nis'] ?? $r['nisn'] ?? '-') ?>
                  </small>
                </div>
                <span class="badge bg-success">
                  <?= number_format((float)($r['percentage'] ?? 0), 1) ?>%
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<style>
  .table> :not(caption)>*>* { padding: .75rem .75rem; }
  .card { transition: transform .2s; }
  .card:hover { transform: translateY(-2px); }
</style>

<?= $this->endSection() ?>
