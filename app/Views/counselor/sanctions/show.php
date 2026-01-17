<?php
/**
 * File Path: app/Views/counselor/sanctions/show.php
 * Detail Sanksi (Counselor/Koordinator)
 */

$this->extend('layouts/main');
$this->section('content');

helper('app');

// Util kecil
if (!function_exists('safe_json_array')) {
    function safe_json_array($value) {
        if (is_array($value)) return $value;
        if (is_string($value) && strlen(trim($value))) {
            $d = json_decode($value, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }
}

$statusBadge = match ($sanction['status'] ?? '') {
    'Dijadwalkan'     => 'bg-info',
    'Sedang Berjalan' => 'bg-warning',
    'Selesai'         => 'bg-success',
    'Dibatalkan'      => 'bg-secondary',
    default           => 'bg-secondary'
};

$docs = safe_json_array($sanction['documents'] ?? null);
$canEdit  = (is_guru_bk() || is_koordinator());
$canDelete = is_koordinator() || (is_guru_bk() && (int)($sanction['assigned_by'] ?? 0) === (int)auth_id());
?>

<!-- Alerts -->
<?= show_alerts() ?>

<!-- Header & Breadcrumbs -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Detail Sanksi</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('counselor/cases') ?>">Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('counselor/cases/detail/'.$sanction['violation_id']) ?>">Detail Kasus & Pelanggaran</a></li>
          <li class="breadcrumb-item active">Detail Sanksi</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- LEFT: Informasi -->
  <div class="col-lg-8">
    <!-- Info Sanksi -->
    <div class="card">
      <div class="card-header bg-warning">
        <div class="d-flex align-items-center justify-content-between">
          <h5 class="mb-0 text-white"><i class="mdi mdi-gavel me-2"></i>Informasi Sanksi</h5>
          <span class="badge <?= $statusBadge ?>"><?= esc($sanction['status'] ?? '—') ?></span>
        </div>
      </div>
      <div class="card-body">
        <div class="mb-3 d-flex justify-content-between">
          <div>
            <div class="text-muted mb-1">Jenis Sanksi</div>
            <h5 class="mb-0"><?= esc($sanction['sanction_type'] ?? '—') ?></h5>
          </div>
          <div class="text-end">
            <div class="text-muted mb-1">Tanggal Sanksi</div>
            <div><?= !empty($sanction['sanction_date']) ? date('d/m/Y', strtotime($sanction['sanction_date'])) : '—' ?></div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted mb-1">Pemberi</div>
            <div><?= esc($sanction['assigned_by_name'] ?? '—') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted mb-1">Durasi (hari)</div>
            <div><?= esc($sanction['duration_days'] ?? '—') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted mb-1">Periode Pelaksanaan</div>
            <div>
              <?php if (!empty($sanction['start_date']) && !empty($sanction['end_date'])): ?>
                <?= date('d/m/Y', strtotime($sanction['start_date'])) ?> - <?= date('d/m/Y', strtotime($sanction['end_date'])) ?>
              <?php else: ?>
                —
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="text-muted mb-1">Catatan Internal</div>
            <div><?= esc($sanction['notes'] ?? '—') ?></div>
          </div>
        </div>

        <div class="mt-3">
          <div class="text-muted mb-1">Deskripsi</div>
          <p class="mb-0"><?= nl2br(esc($sanction['description'] ?? '—')) ?></p>
        </div>
      </div>
    </div>

    <!-- Status Verifikasi & Acknowledgement -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="mdi mdi-shield-check-outline me-2"></i>Status Verifikasi & Acknowledgement</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted mb-1">Verifikasi</div>
            <?php if (!empty($sanction['verified_by'])): ?>
              <div>
                <span class="badge bg-success"><i class="mdi mdi-check"></i> Diverifikasi</span><br>
                <small class="text-muted">
                  Oleh: <?= esc($sanction['verified_by_name'] ?? '—') ?> pada
                  <?= !empty($sanction['verified_at']) ? date('d M Y H:i', strtotime($sanction['verified_at'])) : '—' ?>
                </small>
              </div>
            <?php else: ?>
              <span class="badge bg-warning"><i class="mdi mdi-alert"></i> Menunggu verifikasi</span>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <div class="text-muted mb-1">Orang Tua Mengetahui</div>
            <?php if (!empty($sanction['parent_acknowledged'])): ?>
              <div>
                <span class="badge bg-success"><i class="mdi mdi-account-check-outline"></i> Diketahui Orang Tua</span><br>
                <small class="text-muted">
                  Pada: <?= !empty($sanction['parent_acknowledged_at']) ? date('d M Y H:i', strtotime($sanction['parent_acknowledged_at'])) : '—' ?>
                </small>
              </div>
            <?php else: ?>
              <span class="badge bg-danger"><i class="mdi mdi-account-alert-outline"></i> Belum diketahui</span>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <div class="text-muted mb-1">Penyelesaian</div>
            <?php if (!empty($sanction['completed_date'])): ?>
              <div>
                <span class="badge bg-success"><i class="mdi mdi-check-circle-outline"></i> Selesai</span><br>
                <small class="text-muted">Tanggal: <?= date('d M Y', strtotime($sanction['completed_date'])) ?></small>
              </div>
            <?php else: ?>
              <span class="badge bg-secondary">Belum diselesaikan</span>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <div class="text-muted mb-1">Catatan Penyelesaian</div>
            <div><?= nl2br(esc($sanction['completion_notes'] ?? '—')) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dokumen Lampiran -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="mdi mdi-paperclip me-2"></i>Dokumen & Lampiran</h6>
      </div>
      <div class="card-body">
        <?php if (!empty($docs)): ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($docs as $doc): 
              $href = base_url(is_string($doc) ? $doc : ($doc['path'] ?? ''));
              $name = is_array($doc) ? ($doc['name'] ?? basename($doc['path'] ?? 'dokumen')) : basename($doc);
              $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
              $icon = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'mdi-image' :
                      (in_array($ext, ['pdf']) ? 'mdi-file-pdf-box' :
                      (in_array($ext, ['doc','docx']) ? 'mdi-file-word-box' :
                      (in_array($ext, ['xls','xlsx']) ? 'mdi-file-excel-box' :
                      (in_array($ext, ['mp4']) ? 'mdi-filmstrip' : 'mdi-file'))));
            ?>
              <li class="mb-2 d-flex align-items-center">
                <i class="mdi <?= $icon ?> text-secondary me-2"></i>
                <a class="text-decoration-underline" href="<?= $href ?>" target="_blank" rel="noopener"><?= esc($name) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <span class="text-muted">Tidak ada lampiran.</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Ringkasan Pelanggaran -->
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0"><i class="mdi mdi-alert-circle-outline me-2"></i>Ringkasan Pelanggaran Terkait</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted mb-1">Siswa</div>
            <div>
              <strong><?= esc($sanction['student_name'] ?? '—') ?></strong>
              <small class="text-muted d-block">
                NISN: <?= esc($sanction['nisn'] ?? '—') ?>
                <?php if (!empty($sanction['class_name'])): ?>
                  | Kelas: <?= esc($sanction['class_name']) ?>
                <?php endif; ?>
              </small>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-muted mb-1">Kategori</div>
            <div><?= esc($sanction['category_name'] ?? '—') ?></div>
          </div>
          <div class="col-md-3">
            <div class="text-muted mb-1">Tanggal Pelanggaran</div>
            <div><?= !empty($sanction['violation_date']) ? date('d/m/Y', strtotime($sanction['violation_date'])) : '—' ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: Actions -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="mdi mdi-cog-outline me-2"></i>Aksi</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <?php if ($canEdit): ?>
            <a href="<?= base_url('counselor/sanctions/edit/'.$sanction['id']) ?>" class="btn btn-primary w-100">
              <i class="mdi mdi-pencil me-1"></i>Edit Sanksi
            </a>
          <?php endif; ?>

          <?php if (is_koordinator() && empty($sanction['verified_by'])): ?>
            <form action="<?= base_url('counselor/sanctions/update/'.$sanction['id']) ?>" method="post" class="d-grid">
              <?= csrf_field() ?>
              <input type="hidden" name="verify_now" value="1">
              <button type="submit" class="btn btn-success w-100">
                <i class="mdi mdi-shield-check-outline me-1"></i>Verifikasi Sekarang
              </button>
            </form>
          <?php endif; ?>

          <?php if (($sanction['status'] ?? '') !== 'Selesai'): ?>
            <form action="<?= base_url('counselor/sanctions/update/'.$sanction['id']) ?>" method="post" class="d-grid">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="Selesai">
              <input type="hidden" name="completed_date" value="<?= date('Y-m-d') ?>">
              <button type="submit" class="btn btn-outline-success w-100">
                <i class="mdi mdi-check-circle-outline me-1"></i>Tandai Selesai
              </button>
            </form>
          <?php endif; ?>

          <?php if (empty($sanction['parent_acknowledged'])): ?>
            <form action="<?= base_url('counselor/sanctions/update/'.$sanction['id']) ?>" method="post" class="d-grid">
              <?= csrf_field() ?>
              <input type="hidden" name="parent_acknowledged" value="1">
              <input type="hidden" name="parent_acknowledged_at" value="<?= date('Y-m-d\TH:i:s') ?>">
              <button type="submit" class="btn btn-outline-warning w-100">
                <i class="mdi mdi-account-check-outline me-1"></i>Tandai Ortu Mengetahui
              </button>
            </form>
          <?php endif; ?>

          <?php if ($canDelete): ?>
            <form action="<?= base_url('counselor/sanctions/delete/'.$sanction['id']) ?>" method="post"
                  class="d-grid"
                  onsubmit="return confirm('Hapus sanksi ini? Tindakan tidak dapat dibatalkan.');">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger w-100">
                <i class="mdi mdi-delete me-1"></i>Hapus
              </button>
            </form>
          <?php endif; ?>

          <a href="<?= base_url('counselor/cases/detail/'.$sanction['violation_id']) ?>" class="btn btn-secondary w-100">
            <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Kasus
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>
