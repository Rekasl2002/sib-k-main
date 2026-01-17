<?php // app/Views/parent/child/session_detail.php ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Detail Sesi Konseling</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('parent/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= route_to('parent.children.sessions', $studentId) ?>">Sesi Konseling</a></li>
          <li class="breadcrumb-item active">Detail Sesi Konseling</li>
        </ol>
      </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success">
        <?= esc(session()->getFlashdata('success')) ?>
      </div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger">
        <?= esc(session()->getFlashdata('error')) ?>
      </div>
    <?php endif; ?>

    <?php
      // ====== Variabel yang diharapkan dari controller (selaras student/schedule/detail) ======
      // $session           : array detail sesi (session_date, session_time, location, session_type, topic, status, problem_description, session_summary, follow_up_plan, is_confidential, ...)
      // $participants      : array peserta (opsional; tidak ditampilkan namanya untuk privasi orang tua)
      // $sessionNotes      : array catatan sesi YANG TIDAK RAHASIA (session_notes.is_confidential = 0)
      // $participationNote : string|null catatan partisipasi anak (untuk Kelompok/Klasikal)
      // $canSeeNotes       : bool, true jika sesi tidak rahasia (cs.is_confidential = 0)
      $session           = $session ?? [];
      $participants      = $participants ?? [];
      $sessionNotes      = $sessionNotes ?? [];
      $participationNote = $participationNote ?? null;
      $canSeeNotes       = $canSeeNotes ?? true; // default true agar identik dengan page siswa

      $rawType = (string) ($session['session_type'] ?? '');

      $date = !empty($session['session_date'])
        ? date('d/m/Y', strtotime($session['session_date']))
        : '-';

      $time = !empty($session['session_time'])
        ? date('H:i', strtotime($session['session_time']))
        : '-';

      switch ($rawType) {
        case 'individual':
        case 'Individual':
        case 'Individu':
          $typeLabel = 'Individu';
          break;
        case 'group':
        case 'Kelompok':
          $typeLabel = 'Kelompok';
          break;
        case 'class':
        case 'Klasikal':
          $typeLabel = 'Klasikal / Kelas';
          break;
        default:
          $typeLabel = $rawType ?: '-';
      }

      $statusLabel = $session['status'] ?? '-';

      // Orang tua: demi konsistensi dengan siswa, halaman ini juga menampilkan ringkasan & catatan
      // namun catatan yang ditampilkan HARUS sudah difilter di controller (is_confidential = 0).
    ?>

    <!-- Informasi Umum -->
    <div class="card mb-3">
      <div class="card-header">
        <strong>Informasi Sesi</strong>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Tanggal</dt>
          <dd class="col-sm-9"><?= esc($date) ?></dd>

          <dt class="col-sm-3">Waktu</dt>
          <dd class="col-sm-9"><?= esc($time) ?></dd>

          <dt class="col-sm-3">Lokasi</dt>
          <dd class="col-sm-9"><?= esc($session['location'] ?? '-') ?></dd>

          <dt class="col-sm-3">Jenis Layanan</dt>
          <dd class="col-sm-9"><?= esc($typeLabel) ?></dd>

          <dt class="col-sm-3">Topik</dt>
          <dd class="col-sm-9"><?= esc($session['topic'] ?? '-') ?></dd>

          <dt class="col-sm-3">Status</dt>
          <dd class="col-sm-9">
            <span class="badge bg-info"><?= esc($statusLabel) ?></span>
          </dd>
        </dl>
      </div>
    </div>

    <!-- Detail Konseling -->
    <div class="card mb-3">
      <div class="card-header">
        <strong>Detail Konseling</strong>
      </div>
      <div class="card-body">
        <?php if (!$canSeeNotes): ?>
          <div class="alert alert-warning mb-0">
            Sesi ini ditandai <strong>rahasia</strong> oleh Guru BK.
            Ringkasan dan catatan sesi tidak ditampilkan di portal orang tua.
          </div>
        <?php else: ?>
          <div class="mb-3">
            <h6 class="fw-semibold">Uraian Permasalahan</h6>
            <p class="mb-0">
              <?= nl2br(esc($session['problem_description'] ?? '-')) ?>
            </p>
          </div>

          <div class="mb-3">
            <h6 class="fw-semibold">Ringkasan Sesi</h6>
            <p class="mb-0">
              <?= nl2br(esc($session['session_summary'] ?? '-')) ?>
            </p>
          </div>

          <div class="mb-0">
            <h6 class="fw-semibold">Rencana Tindak Lanjut</h6>
            <p class="mb-0">
              <?= nl2br(esc($session['follow_up_plan'] ?? '-')) ?>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php
      // Hanya tampilkan "Catatan Partisipasi Saya" jika sesi Kelompok/Klasikal
      $showParticipation = in_array($rawType, ['Kelompok', 'Klasikal'], true);
    ?>

    <?php if ($showParticipation): ?>
      <!-- Catatan Partisipasi Anak -->
      <div class="card mb-3">
        <div class="card-header">
          <strong>Catatan Partisipasi Anak</strong>
        </div>
        <div class="card-body">
          <?php if (!$canSeeNotes): ?>
            <p class="mb-0 text-muted fst-italic">
              Catatan partisipasi tidak ditampilkan karena sesi ini bersifat rahasia.
            </p>
          <?php else: ?>
            <?php if (!empty($participationNote)): ?>
              <p class="mb-0"><?= nl2br(esc($participationNote)) ?></p>
            <?php else: ?>
              <p class="mb-0 text-muted fst-italic">
                Belum ada catatan partisipasi yang dicatat oleh Guru BK untuk sesi ini.
              </p>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Catatan Sesi (yang tidak confidential) -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Catatan Sesi</strong>
        <?php if ($canSeeNotes): ?>
          <span class="text-muted small">
            Hanya catatan yang tidak ditandai <strong>rahasia</strong> yang ditampilkan.
          </span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$canSeeNotes): ?>
          <p class="mb-0 text-muted fst-italic">
            Catatan sesi tidak ditampilkan karena sesi ini bersifat rahasia.
          </p>
        <?php else: ?>
          <?php if (!empty($sessionNotes)): ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($sessionNotes as $note): ?>
                <?php
                  $noteType    = (string) ($note['note_type'] ?? 'Observasi');
                  $author      = (string) ($note['counselor_name'] ?? 'Guru BK');
                  $isImportant = !empty($note['is_important']);

                  $createdAt = '';
                  if (!empty($note['created_at'])) {
                    $createdAt = date('d/m/Y H:i', strtotime($note['created_at']));
                  }

                  // Decode lampiran dari kolom attachments (JSON / array / null)
                  $attachments = [];
                  if (!empty($note['attachments'])) {
                    if (is_array($note['attachments'])) {
                      $attachments = array_values(array_filter($note['attachments'], 'strlen'));
                    } else {
                      $decoded = json_decode((string) $note['attachments'], true);
                      if (is_array($decoded)) {
                        $attachments = array_values(array_filter($decoded, 'strlen'));
                      }
                    }
                  }
                ?>
                <li class="list-group-item px-0">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                      <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <span class="fw-semibold"><?= esc($author) ?></span>
                        <span class="badge bg-light text-muted border">
                          <?= esc($noteType) ?>
                        </span>
                        <?php if ($isImportant): ?>
                          <span class="badge bg-danger">
                            <i class="mdi mdi-star me-1"></i>Penting
                          </span>
                        <?php endif; ?>
                      </div>
                      <?php if ($createdAt): ?>
                        <small class="text-muted">
                          <i class="mdi mdi-clock-outline me-1"></i><?= esc($createdAt) ?>
                        </small>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="mb-1">
                    <?= nl2br(esc($note['note_content'] ?? '')) ?>
                  </div>

                  <?php if (!empty($attachments)): ?>
                    <div class="mt-1">
                      <div class="small text-muted mb-1">
                        <i class="mdi mdi-paperclip me-1"></i>Lampiran:
                      </div>
                      <ul class="list-unstyled small mb-0">
                        <?php foreach ($attachments as $idx => $path): ?>
                          <li>
                            <a href="<?= base_url($path) ?>" target="_blank" rel="noopener">
                              File <?= $idx + 1 ?>
                            </a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="mb-0 text-muted fst-italic">
              Belum ada catatan sesi yang dapat ditampilkan.
            </p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
