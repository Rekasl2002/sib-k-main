<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
  .prototype-feature .proto-card,
  .prototype-feature .proto-panel {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .prototype-feature .proto-icon {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 22px;
  }
  .prototype-feature .proto-nav {
    gap: 8px;
  }
  .prototype-feature .proto-nav .btn {
    border-radius: 8px;
    text-align: left;
  }
  .prototype-feature .proto-step {
    position: relative;
    padding-left: 26px;
    min-height: 38px;
  }
  .prototype-feature .proto-step::before {
    content: "";
    position: absolute;
    left: 0;
    top: 3px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #adb5bd;
    background: #fff;
  }
  .prototype-feature .proto-step.is-active::before {
    border-color: var(--sibk-primary, #1f6f54);
    background: var(--sibk-primary, #1f6f54);
  }
  .prototype-feature .proto-chat {
    min-height: 250px;
    max-height: 360px;
    overflow: auto;
    background: #f8fafc;
    border-radius: 8px;
    padding: 14px;
  }
  .prototype-feature .proto-bubble {
    max-width: 82%;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 10px;
    background: #fff;
    border: 1px solid rgba(15, 23, 42, .08);
  }
  .prototype-feature .proto-bubble.me {
    margin-left: auto;
    background: rgba(31, 111, 84, .1);
    border-color: rgba(31, 111, 84, .18);
  }
  .prototype-feature .proto-result-bar {
    height: 10px;
    border-radius: 999px;
  }
  .prototype-feature .proto-mini-table td,
  .prototype-feature .proto-mini-table th {
    white-space: nowrap;
  }
  .prototype-feature .proto-alert {
    border-radius: 8px;
  }
  .prototype-feature .proto-workbar {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .prototype-feature .proto-route {
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid rgba(15, 23, 42, .08);
    padding: 10px 12px;
    font-size: 12px;
  }
  .prototype-feature .proto-stage-button {
    border-radius: 8px;
  }
  .prototype-feature .proto-stage-preview {
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid rgba(15, 23, 42, .08);
    min-height: 74px;
  }
  .prototype-feature .role-switch {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  }
  .prototype-feature .role-switch .btn {
    border-radius: 8px;
  }
  .prototype-feature .proto-nav .btn.disabled,
  .prototype-feature .flow-next.disabled {
    pointer-events: none;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$features  = is_array($features ?? null) ? $features : [];
$feature   = is_array($feature ?? null) ? $feature : [];
$demo      = is_array($demo ?? null) ? $demo : [];
$activeKey = (string) ($activeKey ?? '');
$featureKeys = array_keys($features);
$activeIndex = array_search($activeKey, $featureKeys, true);
$activeIndex = $activeIndex === false ? 0 : (int) $activeIndex;
$prevFeature = $features[$featureKeys[$activeIndex - 1] ?? ''] ?? null;
$nextFeature = $features[$featureKeys[$activeIndex + 1] ?? ''] ?? null;
$roleMode = (string) ($roleMode ?? '');
$roleLabel = (string) ($roleLabel ?? ($demo['role_label'] ?? 'Pengguna'));
$roleOptions = is_array($roleOptions ?? null) ? $roleOptions : [];
$roleNote = (string) ($demo['role_note'] ?? 'Tampilan dan aksi prototipe mengikuti hak akses demo peran aktif.');
$isTried = ! empty($isTried);
$progressUrl = (string) ($progressUrl ?? '');
$requiresInput = match ($activeKey) {
    'violation-submissions' => ! empty($demo['can_submit']),
    'notifications'         => ! empty($demo['can_send']),
    'messages'              => true,
    'assessments'           => ! empty($demo['can_answer']),
    'career'                => ! empty($demo['can_manage']),
    default                 => false,
};

if (! function_exists('prototype_status_tone')) {
    function prototype_status_tone(string $status): string
    {
        return match (strtolower(trim($status))) {
            'diajukan', 'draft', 'belum dibaca' => 'warning',
            'ditinjau', 'aktif' => 'info',
            'diterima', 'selesai', 'dibaca' => 'success',
            'ditolak' => 'danger',
            'dikonversi' => 'primary',
            default => 'secondary',
        };
    }
}
?>

<div
  class="prototype-feature"
  data-feature="<?= esc($activeKey) ?>"
  data-progress-url="<?= esc(base_url($progressUrl)) ?>"
  data-tried="<?= $isTried ? '1' : '0' ?>">
  <nav aria-label="breadcrumb" class="mb-3 d-flex justify-content-end">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>">Prototipe Skripsi</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($feature['short_title'] ?? 'Fitur') ?></li>
    </ol>
  </nav>

  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <div class="d-flex align-items-start gap-3">
        <span class="proto-icon bg-<?= esc($feature['tone'] ?? 'primary') ?> bg-opacity-10 text-<?= esc($feature['tone'] ?? 'primary') ?>">
          <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape') ?>"></i>
        </span>
        <div>
          <h2 class="page-title mb-1"><?= esc($feature['title'] ?? 'Prototipe') ?></h2>
          <p class="text-muted mb-0"><?= esc($feature['outcome'] ?? '') ?></p>
        </div>
      </div>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>" class="btn btn-light">
        <i class="mdi mdi-view-grid-outline me-1"></i> Semua Prototipe
      </a>
    </div>
  </div>

  <div class="role-switch p-3 mb-4">
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
      <div>
        <div class="fw-semibold">Mode peran aktif: <?= esc($roleLabel) ?></div>
        <small class="text-muted"><?= esc($roleNote) ?></small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($roleOptions as $key => $label): ?>
          <a
            href="<?= base_url('prototype/' . $activeKey . '?role=' . rawurlencode((string) $key)) ?>"
            class="btn btn-sm <?= $key === $roleMode ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= esc($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap proto-nav mb-4">
    <?php foreach ($features as $key => $item): ?>
      <?php $isActive = ($key === $activeKey); ?>
      <?php $isLocked = ! empty($item['is_locked']); ?>
      <?php if ($isLocked): ?>
        <span class="btn btn-outline-secondary disabled">
          <i class="mdi mdi-lock-outline me-1"></i>
          <?= esc($item['short_title'] ?? $item['title'] ?? $key) ?>
        </span>
      <?php else: ?>
        <a class="btn <?= $isActive ? 'btn-primary' : (! empty($item['is_tried']) ? 'btn-outline-success' : 'btn-outline-primary') ?>" href="<?= base_url($item['url'] ?? '#') ?>">
          <i class="<?= esc($item['icon'] ?? 'mdi mdi-shape') ?> me-1"></i>
          <?= esc($item['short_title'] ?? $item['title'] ?? $key) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div id="prototypeNotice" class="alert alert-info proto-alert d-none" role="alert"></div>

  <div class="proto-workbar p-3 mb-3">
    <div class="row g-3 align-items-center">
      <div class="col-xl-4">
        <div class="proto-route d-flex align-items-center gap-2">
          <i class="<?= esc($feature['icon'] ?? 'mdi mdi-shape') ?>"></i>
          <span id="prototypeRoute"><?= esc('/prototype/' . $activeKey . '/input') ?></span>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="d-flex flex-wrap gap-2" role="tablist" aria-label="Tahap prototipe">
          <button type="button" class="btn btn-sm btn-primary proto-stage-button" data-proto-stage="input">
            <i class="mdi mdi-pencil-outline me-1"></i> Input
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary proto-stage-button" data-proto-stage="proses">
            <i class="mdi mdi-progress-clock me-1"></i> Proses
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary proto-stage-button" data-proto-stage="hasil">
            <i class="mdi mdi-check-circle-outline me-1"></i> Hasil
          </button>
          <?php if (! $requiresInput): ?>
            <button type="button" class="btn btn-sm <?= $isTried ? 'btn-outline-success' : 'btn-outline-secondary' ?>" id="markPrototypeTried">
              <i class="mdi mdi-check-all me-1"></i> <?= $isTried ? 'Sudah Dicoba' : 'Tandai Dicoba' ?>
            </button>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="proto-stage-preview p-3" id="prototypeStagePreview">
          <div class="fw-semibold">Halaman input prototipe</div>
          <small class="text-muted">Isi atau pilih data contoh, lalu lanjutkan proses dari panel fitur di bawah.</small>
        </div>
      </div>
    </div>
  </div>

  <?php if ($activeKey === 'violation-submissions'): ?>
    <?php
      $rows = is_array($demo['submissions'] ?? null) ? $demo['submissions'] : [];
      $timeline = is_array($demo['timeline'] ?? null) ? $demo['timeline'] : [];
      $first = $rows[0] ?? [];
      $canSubmit = ! empty($demo['can_submit']);
      $canReview = ! empty($demo['can_review']);
    ?>
    <div class="row g-3">
      <div class="col-xl-4">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0"><?= $canSubmit ? 'Form Pengaduan' : 'Ruang Tinjauan' ?></h5>
          </div>
          <div class="card-body">
            <?php if ($canSubmit): ?>
              <div class="mb-3">
                <label class="form-label">Pelapor</label>
                <select id="vsReporter" class="form-select">
                  <option value="" selected disabled>Contoh: <?= esc($roleLabel) ?></option>
                  <option value="<?= esc($roleLabel) ?>"><?= esc($roleLabel) ?></option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Nama Terlapor</label>
                <input id="vsSubject" type="text" class="form-control" placeholder="Contoh: Rafi Maulana - XI MIPA 2">
              </div>
              <div class="mb-3">
                <label class="form-label">Kategori</label>
                <select id="vsCategory" class="form-select">
                  <option value="" selected disabled>Contoh: Ketertiban</option>
                  <option value="Ketertiban">Ketertiban</option>
                  <option value="Kedisiplinan">Kedisiplinan</option>
                  <option value="Perundungan verbal">Perundungan verbal</option>
                  <option value="Penggunaan gawai">Penggunaan gawai</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Kronologi Singkat</label>
                <textarea id="vsDescription" class="form-control" rows="4" placeholder="Contoh: siswa terlihat mengganggu teman saat pergantian jam pelajaran."></textarea>
              </div>
              <button id="vsSendDemo" type="button" class="btn btn-primary w-100">
                <i class="mdi mdi-send me-1"></i> Kirim Simulasi
              </button>
            <?php else: ?>
              <div class="alert alert-light border mb-3">
                Mode <?= esc($roleLabel) ?> tidak membuat pengaduan baru dari halaman ini.
              </div>
              <div class="border rounded p-3 mb-3">
                <div class="fw-semibold">Tugas utama</div>
                <small class="text-muted">Verifikasi antrian, beri keputusan, dan konversi pengaduan valid menjadi kasus.</small>
              </div>
              <div class="border rounded p-3">
                <div class="fw-semibold">Akses data</div>
                <small class="text-muted">Melihat pengaduan masuk sesuai lingkup layanan BK.</small>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card proto-card mb-3">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Antrian Pengaduan</h5>
            <span class="badge bg-light text-dark"><span id="vsQueueCount"><?= count($rows) ?></span> data contoh</span>
          </div>
          <div class="card-body table-responsive">
            <table class="table align-middle proto-mini-table mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Pelapor</th>
                  <th>Terlapor</th>
                  <th>Kategori</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="vsQueueBody">
                <?php foreach ($rows as $index => $row): ?>
                  <?php $status = (string) ($row['status'] ?? 'Diajukan'); ?>
                  <tr <?= $index === 0 ? 'id="prototype-vs-row-main"' : '' ?>>
                    <td class="vs-id"><?= esc($row['id'] ?? '-') ?></td>
                    <td><?= esc($row['reporter'] ?? '-') ?><br><small class="text-muted"><?= esc($row['role'] ?? '-') ?></small></td>
                    <td class="vs-subject"><?= esc($row['subject'] ?? '-') ?></td>
                    <td class="vs-category"><?= esc($row['category'] ?? '-') ?></td>
                    <td>
                      <?php if ($index === 0): ?>
                        <span id="vsStatusBadge" class="badge bg-<?= esc(prototype_status_tone($status)) ?>"><?= esc($status) ?></span>
                      <?php else: ?>
                        <span class="badge bg-<?= esc(prototype_status_tone($status)) ?>"><?= esc($status) ?></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card proto-card h-100">
              <div class="card-header bg-white">
                <h5 class="mb-0"><?= $canReview ? 'Tindakan Petugas' : 'Status Pengaduan Saya' ?></h5>
              </div>
              <div class="card-body">
                <?php if ($canReview): ?>
                  <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-info" data-vs-status="Ditinjau">
                      <i class="mdi mdi-eye-check-outline me-1"></i> Tinjau Pengaduan
                    </button>
                    <button type="button" class="btn btn-outline-success" data-vs-status="Diterima">
                      <i class="mdi mdi-check-circle-outline me-1"></i> Terima untuk Ditindaklanjuti
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-vs-status="Dikonversi">
                      <i class="mdi mdi-source-branch me-1"></i> Konversi ke Kasus
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-vs-status="Ditolak">
                      <i class="mdi mdi-close-circle-outline me-1"></i> Tolak dengan Catatan
                    </button>
                  </div>
                <?php else: ?>
                  <div class="alert alert-light border mb-0">
                    Pengguna pada mode <?= esc($roleLabel) ?> dapat mengirim dan memantau status, tetapi tidak bisa menerima, menolak, atau mengonversi pengaduan.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card proto-card h-100">
              <div class="card-header bg-white">
                <h5 class="mb-0">Status Alur</h5>
              </div>
              <div class="card-body">
                <?php foreach ($timeline as $step): ?>
                  <div class="proto-step mb-3 <?= ($step === ($first['status'] ?? 'Diajukan')) ? 'is-active' : '' ?>" data-vs-step="<?= esc($step) ?>">
                    <div class="fw-semibold"><?= esc($step) ?></div>
                    <small class="text-muted">
                      <?= $step === 'Dikonversi' ? 'Membentuk draft kasus pelanggaran.' : 'Tercatat dalam riwayat pengaduan.' ?>
                    </small>
                  </div>
                <?php endforeach; ?>
                <div id="vsCasePreview" class="alert alert-success mb-0 d-none">
                  Draft kasus dibuat: kategori <strong>Ketertiban</strong>, prioritas <strong>sedang</strong>, tindak lanjut <strong>konseling awal</strong>.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($activeKey === 'notifications'): ?>
    <?php
      $items = is_array($demo['items'] ?? null) ? $demo['items'] : [];
      $targets = is_array($demo['targets'] ?? null) ? $demo['targets'] : ['Guru BK', 'Wali Kelas', 'Siswa', 'Orang Tua'];
      $canSend = ! empty($demo['can_send']);
    ?>
    <div class="row g-3">
      <div class="col-xl-4">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0"><?= $canSend ? 'Kirim Notifikasi' : 'Notifikasi Saya' ?></h5>
          </div>
          <div class="card-body">
            <?php if ($canSend): ?>
              <div class="mb-3">
                <label class="form-label">Tujuan</label>
                <select id="notifTarget" class="form-select">
                  <option value="" selected disabled>Contoh: Guru BK</option>
                  <?php foreach ($targets as $target): ?>
                    <option value="<?= esc($target) ?>"><?= esc($target) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Judul</label>
                <input id="notifTitle" type="text" class="form-control" placeholder="Contoh: Pengaduan perlu ditinjau">
              </div>
              <div class="mb-3">
                <label class="form-label">Isi</label>
                <textarea id="notifBody" class="form-control" rows="4" placeholder="Contoh: ada pengaduan baru yang perlu diverifikasi sebelum menjadi kasus."></textarea>
              </div>
              <button id="notifPushDemo" type="button" class="btn btn-primary w-100">
                <i class="mdi mdi-bell-plus-outline me-1"></i> Kirim Simulasi
              </button>
            <?php else: ?>
              <div class="alert alert-light border mb-3">
                Mode <?= esc($roleLabel) ?> hanya menerima notifikasi dan menandainya sudah dibaca.
              </div>
              <button id="notifMarkAllSide" type="button" class="btn btn-outline-primary w-100">
                <i class="mdi mdi-check-all me-1"></i> Tandai Semua Dibaca
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card proto-card">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pusat Notifikasi</h5>
            <div>
              <span class="badge bg-danger me-2"><span id="notifUnreadCount">2</span> belum dibaca</span>
              <button id="notifMarkAll" type="button" class="btn btn-sm btn-outline-primary">Tandai Dibaca</button>
            </div>
          </div>
          <div class="card-body" id="notifList">
            <?php foreach ($items as $item): ?>
              <?php $status = (string) ($item['status'] ?? 'Dibaca'); ?>
              <div class="border rounded p-3 mb-3 notif-item <?= strtolower($status) === 'belum dibaca' ? 'bg-light' : '' ?>">
                <div class="d-flex justify-content-between gap-3">
                  <div>
                    <h6 class="mb-1"><?= esc($item['title'] ?? '-') ?></h6>
                    <div class="text-muted"><?= esc($item['body'] ?? '-') ?></div>
                    <small class="text-muted"><?= esc($item['time'] ?? '-') ?></small>
                  </div>
                  <span class="badge notif-status bg-<?= esc(prototype_status_tone($status)) ?> align-self-start"><?= esc($status) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($activeKey === 'messages'): ?>
    <?php
      $threads = is_array($demo['threads'] ?? null) ? $demo['threads'] : [];
      $recipients = is_array($demo['recipients'] ?? null) ? $demo['recipients'] : [];
    ?>
    <div class="row g-3">
      <div class="col-xl-4">
        <div class="card proto-card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Kotak Masuk</h5>
            <span class="badge bg-primary"><span id="messageThreadCount"><?= count($threads) ?></span> percakapan</span>
          </div>
          <div class="list-group list-group-flush" id="messageThreadList">
            <?php foreach ($threads as $thread): ?>
              <button type="button" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                  <span class="fw-semibold"><?= esc($thread['from'] ?? '-') ?></span>
                  <small class="text-muted"><?= esc($thread['time'] ?? '-') ?></small>
                </div>
                <div><?= esc($thread['subject'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($thread['snippet'] ?? '-') ?></small>
                <?php if (! empty($thread['unread'])): ?>
                  <span class="badge bg-danger mt-2">Baru</span>
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0">Percakapan</h5>
          </div>
          <div class="card-body">
            <div class="proto-chat" id="messageChat">
              <div class="proto-bubble">
                <div class="fw-semibold">Guru BK</div>
                <div>Mohon konfirmasi waktu kejadian dan saksi yang melihat.</div>
                <small class="text-muted">09.30</small>
              </div>
              <div class="proto-bubble me">
                <div class="fw-semibold">Saya</div>
                <div>Kejadian sekitar pukul 09.10, disaksikan dua teman sekelas.</div>
                <small class="text-muted">09.38</small>
              </div>
            </div>
            <div class="input-group mt-3">
              <input id="messageReply" type="text" class="form-control" placeholder="Contoh: Baik, saya lampirkan kronologi tambahan.">
              <button id="messageReplyBtn" type="button" class="btn btn-primary">
                <i class="mdi mdi-reply"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0">Tulis Pesan</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Penerima</label>
              <select id="messageRecipient" class="form-select">
                <option value="" selected disabled>Contoh: Guru BK</option>
                <?php foreach ($recipients as $recipient): ?>
                  <option value="<?= esc($recipient) ?>"><?= esc($recipient) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Subjek</label>
              <input id="messageSubject" type="text" class="form-control" placeholder="Contoh: Koordinasi tindak lanjut">
            </div>
            <div class="mb-3">
              <label class="form-label">Isi</label>
              <textarea id="messageBody" class="form-control" rows="5" placeholder="Contoh: mohon arahan untuk langkah tindak lanjut berikutnya."></textarea>
            </div>
            <button id="messageSendDemo" type="button" class="btn btn-primary w-100">
              <i class="mdi mdi-send me-1"></i> Kirim Simulasi
            </button>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($activeKey === 'assessments'): ?>
    <?php
      $assessments = is_array($demo['assessments'] ?? null) ? $demo['assessments'] : [];
      $questions = is_array($demo['questions'] ?? null) ? $demo['questions'] : [];
      $results = is_array($demo['results'] ?? null) ? $demo['results'] : [];
      $canAssign = ! empty($demo['can_assign']);
      $canAnswer = ! empty($demo['can_answer']);
      $canReview = ! empty($demo['can_review']);
    ?>
    <div class="row g-3">
      <div class="col-xl-4">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0"><?= $canAssign ? 'Daftar Asesmen' : 'Tugas Asesmen' ?></h5>
          </div>
          <div class="card-body">
            <?php foreach ($assessments as $item): ?>
              <?php $status = (string) ($item['status'] ?? 'Draft'); ?>
              <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between gap-2">
                  <div>
                    <h6 class="mb-1"><?= esc($item['name'] ?? '-') ?></h6>
                    <small class="text-muted"><?= esc($item['target'] ?? '-') ?></small>
                  </div>
                  <span class="badge bg-<?= esc(prototype_status_tone($status)) ?> align-self-start"><?= esc($status) ?></span>
                </div>
                <div class="progress proto-result-bar mt-3">
                  <div class="progress-bar" style="width: <?= (int) ($item['progress'] ?? 0) ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if ($canAssign): ?>
              <button id="assessmentAssignDemo" type="button" class="btn btn-primary w-100">
                <i class="mdi mdi-account-plus-outline me-1"></i> Tugaskan Simulasi
              </button>
            <?php else: ?>
              <div class="alert alert-light border mb-0">
                Mode <?= esc($roleLabel) ?> tidak menugaskan asesmen dari halaman ini.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card proto-card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pengisian Siswa</h5>
            <span class="badge bg-info"><span id="assessmentProgressText">0</span>%</span>
          </div>
          <div class="card-body">
            <?php if ($canAnswer): ?>
              <div class="progress proto-result-bar mb-3">
                <div id="assessmentProgressBar" class="progress-bar bg-info" style="width: 0%"></div>
              </div>
              <?php foreach ($questions as $index => $question): ?>
                <div class="border rounded p-3 mb-3">
                  <div class="fw-semibold mb-2"><?= ($index + 1) ?>. <?= esc($question) ?></div>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-outline-secondary assessment-answer">Tidak</button>
                    <button type="button" class="btn btn-outline-secondary assessment-answer">Ragu</button>
                    <button type="button" class="btn btn-outline-secondary assessment-answer">Ya</button>
                  </div>
                </div>
              <?php endforeach; ?>
              <div id="assessmentResultNotice" class="alert alert-success d-none mb-0">
                Jawaban lengkap. Ringkasan hasil siap dilihat oleh Guru BK.
              </div>
            <?php else: ?>
              <div class="alert alert-light border mb-0">
                Mode <?= esc($roleLabel) ?> melihat progres dan hasil, tetapi tidak mengisi jawaban siswa.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0">Ringkasan Hasil</h5>
          </div>
          <div class="card-body">
            <?php if ($canReview): ?>
              <?php foreach ($results as $result): ?>
                <div class="mb-3">
                  <div class="d-flex justify-content-between">
                    <span><?= esc($result['label'] ?? '-') ?></span>
                    <strong><?= (int) ($result['score'] ?? 0) ?></strong>
                  </div>
                  <div class="progress proto-result-bar">
                    <div class="progress-bar bg-success" style="width: <?= (int) ($result['score'] ?? 0) ?>%"></div>
                  </div>
                </div>
              <?php endforeach; ?>
              <div class="alert alert-light border mb-0">
                Rekomendasi awal: siswa cocok diarahkan ke eksplorasi karier sosial-humaniora dan sesi refleksi minat belajar.
              </div>
            <?php else: ?>
              <div class="alert alert-light border mb-0">
                Ringkasan detail akan ditinjau Guru BK setelah asesmen selesai. Siswa melihat status pengisian pada panel tengah.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($activeKey === 'career'): ?>
    <?php
      $careers = is_array($demo['careers'] ?? null) ? $demo['careers'] : [];
      $universities = is_array($demo['universities'] ?? null) ? $demo['universities'] : [];
      $saved = is_array($demo['saved'] ?? null) ? $demo['saved'] : [];
      $savedByStudents = is_array($demo['saved_by_students'] ?? null) ? $demo['saved_by_students'] : [];
      $canManage = ! empty($demo['can_manage']);
      $canSave = ! empty($demo['can_save']);
      $canDelete = ! empty($demo['can_delete']);
    ?>
    <div class="row g-3">
      <div class="col-xl-5">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0"><?= $canManage ? 'Tambah Referensi' : 'Eksplorasi Karier' ?></h5>
          </div>
          <div class="card-body">
            <?php if ($canManage): ?>
              <div class="mb-3">
                <label class="form-label">Nama Karier/Program</label>
                <input id="careerRefName" type="text" class="form-control" placeholder="Contoh: Psikolog Pendidikan">
              </div>
              <div class="mb-3">
                <label class="form-label">Jenis Referensi</label>
                <select id="careerRefType" class="form-select">
                  <option value="" selected disabled>Contoh: Karier</option>
                  <option value="Karier">Karier</option>
                  <option value="Program Studi">Program Studi</option>
                  <option value="Perguruan Tinggi">Perguruan Tinggi</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Keterangan</label>
                <textarea id="careerRefDescription" class="form-control" rows="4" placeholder="Contoh: cocok untuk siswa yang tertarik pada layanan pendampingan belajar dan psikologi."></textarea>
              </div>
              <button id="careerAddReference" type="button" class="btn btn-primary w-100">
                <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Referensi Simulasi
              </button>
            <?php else: ?>
              <?php foreach ($careers as $career): ?>
                <div class="border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between gap-3">
                    <div>
                      <h6 class="mb-1"><?= esc($career['name'] ?? '-') ?></h6>
                      <small class="text-muted"><?= esc($career['cluster'] ?? '-') ?></small>
                    </div>
                    <span class="badge bg-success align-self-start"><?= (int) ($career['match'] ?? 0) ?>%</span>
                  </div>
                  <?php if ($canSave): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-3 career-save" data-save="<?= esc($career['name'] ?? '-') ?>">
                      <i class="mdi mdi-bookmark-outline me-1"></i> Simpan
                    </button>
                  <?php else: ?>
                    <span class="badge bg-light text-dark mt-3">Hanya lihat</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card proto-card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0">Referensi Studi Lanjut</h5>
          </div>
          <div class="card-body" id="careerReferenceList">
            <?php foreach ($universities as $university): ?>
              <div class="border rounded p-3 mb-3">
                <h6 class="mb-1"><?= esc($university['name'] ?? '-') ?></h6>
                <div><?= esc($university['program'] ?? '-') ?></div>
                <small class="text-muted"><?= esc($university['city'] ?? '-') ?></small>
                <?php if ($canSave): ?>
                  <button type="button" class="btn btn-sm btn-outline-primary d-block mt-3 career-save" data-save="<?= esc($university['program'] ?? '-') ?>">
                    <i class="mdi mdi-bookmark-outline me-1"></i> Simpan
                  </button>
                <?php elseif ($canManage): ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary d-block mt-3">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit Simulasi
                  </button>
                <?php else: ?>
                  <span class="badge bg-light text-dark d-inline-block mt-3">Hanya lihat</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-xl-3">
        <div class="card proto-card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= $savedByStudents ? 'Pilihan Siswa' : 'Pilihan Disimpan' ?></h5>
            <span class="badge bg-primary"><span id="careerSavedCount"><?= $savedByStudents ? count($savedByStudents) : count($saved) ?></span></span>
          </div>
          <div class="card-body">
            <?php if ($savedByStudents): ?>
              <div id="careerSavedList" class="list-group mb-3">
                <?php foreach ($savedByStudents as $item): ?>
                  <div class="list-group-item">
                    <div class="fw-semibold"><?= esc($item['student'] ?? '-') ?></div>
                    <small class="text-muted"><?= esc($item['class'] ?? '-') ?> &middot; <?= esc($item['type'] ?? '-') ?></small>
                    <div class="mt-1"><?= esc($item['choice'] ?? '-') ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="alert alert-light border mb-0">
                Data ini menjadi bahan Guru BK atau wali kelas saat memberi arahan.
              </div>
            <?php else: ?>
              <ul id="careerSavedList" class="list-group mb-3">
                <?php foreach ($saved as $item): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= esc($item) ?></span>
                    <?php if ($canDelete): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger career-remove" title="Hapus pilihan">
                        <i class="mdi mdi-delete-outline"></i>
                      </button>
                    <?php else: ?>
                      <i class="mdi mdi-check text-success"></i>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
              <div class="alert alert-light border mb-0">
                <?= $canSave ? 'Pilihan ini bisa disimpan atau dihapus oleh siswa.' : 'Pilihan ini ditampilkan sebagai bahan pendampingan.' ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-3">
    <?php if ($prevFeature): ?>
      <a href="<?= base_url($prevFeature['url'] ?? 'prototype') ?>" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> <?= esc($prevFeature['short_title'] ?? 'Sebelumnya') ?>
      </a>
    <?php else: ?>
      <a href="<?= base_url('prototype?role=' . rawurlencode($roleMode)) ?>" class="btn btn-outline-secondary">
        <i class="mdi mdi-view-grid-outline me-1"></i> Daftar Prototipe
      </a>
    <?php endif; ?>

    <?php if ($nextFeature): ?>
      <a
        id="flowNextLink"
        href="<?= $isTried ? base_url($nextFeature['url'] ?? 'prototype') : '#' ?>"
        data-next-url="<?= esc(base_url($nextFeature['url'] ?? 'prototype')) ?>"
        data-next-label="<?= esc('Lanjut ke ' . ($nextFeature['short_title'] ?? 'Prototipe berikutnya')) ?>"
        class="btn <?= $isTried ? 'btn-primary' : 'btn-outline-secondary disabled' ?> flow-next"
        aria-disabled="<?= $isTried ? 'false' : 'true' ?>">
        <?= $isTried ? 'Lanjut ke ' . esc($nextFeature['short_title'] ?? 'Prototipe berikutnya') : 'Coba prototipe ini dulu' ?> <i class="mdi mdi-arrow-right ms-1"></i>
      </a>
    <?php else: ?>
      <a
        id="flowNextLink"
        href="<?= $isTried ? base_url('simulation?role=' . rawurlencode($roleMode)) : '#' ?>"
        data-next-url="<?= esc(base_url('simulation?role=' . rawurlencode($roleMode))) ?>"
        data-next-label="Kembali ke Simulasi"
        class="btn <?= $isTried ? 'btn-primary' : 'btn-outline-secondary disabled' ?> flow-next"
        aria-disabled="<?= $isTried ? 'false' : 'true' ?>">
        <?= $isTried ? 'Kembali ke Simulasi' : 'Coba prototipe ini dulu' ?> <i class="mdi mdi-arrow-right ms-1"></i>
      </a>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const root = document.querySelector('.prototype-feature');
  if (!root) return;

  const feature = root.dataset.feature || '';
  const notice = document.getElementById('prototypeNotice');
  const routeLabel = document.getElementById('prototypeRoute');
  const stagePreview = document.getElementById('prototypeStagePreview');
  const stageButtons = Array.from(root.querySelectorAll('[data-proto-stage]'));
  const progressUrl = root.dataset.progressUrl || '';
  const nextLink = document.getElementById('flowNextLink');
  let hasMarkedProgress = root.dataset.tried === '1';
  const toneMap = {
    'Diajukan': 'warning',
    'Ditinjau': 'info',
    'Diterima': 'success',
    'Ditolak': 'danger',
    'Dikonversi': 'primary',
    'Belum dibaca': 'warning',
    'Dibaca': 'success'
  };
  const stageLabels = {
    input: 'Halaman input prototipe',
    proses: 'Halaman proses petugas',
    hasil: 'Halaman hasil dan tindak lanjut'
  };
  const stageBodies = {
    input: 'Pengguna mengisi atau memilih data contoh sebelum pekerjaan dikirim.',
    proses: 'Data contoh masuk ke antrian, ditinjau, lalu diberi status seperti alur kerja aplikasi.',
    hasil: 'Hasil simulasi tampil sebagai ringkasan, riwayat, atau pilihan yang sudah tersimpan.'
  };

  function showNotice(message, type) {
    if (!notice) return;
    notice.className = 'alert proto-alert alert-' + (type || 'info');
    notice.textContent = message;
    notice.classList.remove('d-none');
    markProgress();
  }

  function showValidation(message) {
    if (!notice) return;
    notice.className = 'alert proto-alert alert-danger';
    notice.textContent = message;
    notice.classList.remove('d-none');
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function setPrototypeStage(stage, detail) {
    const nextStage = stageLabels[stage] ? stage : 'input';
    stageButtons.forEach((button) => {
      const isActive = button.dataset.protoStage === nextStage;
      button.classList.toggle('btn-primary', isActive);
      button.classList.toggle('btn-outline-primary', !isActive);
    });
    if (routeLabel) routeLabel.textContent = '/prototype/' + feature + '/' + nextStage;
    if (stagePreview) {
      stagePreview.innerHTML =
        '<div class="fw-semibold">' + escapeHtml(stageLabels[nextStage]) + '</div>' +
        '<small class="text-muted">' + escapeHtml(detail || stageBodies[nextStage]) + '</small>';
    }
  }

  function validateRequiredInputs(inputs, message) {
    let firstInvalid = null;

    inputs.forEach((input) => {
      if (!input) return;
      const value = String(input.value || '').trim();
      const invalid = value === '';
      input.classList.toggle('is-invalid', invalid);
      if (invalid && !firstInvalid) firstInvalid = input;
    });

    if (firstInvalid) {
      firstInvalid.focus();
      showValidation(message || 'Lengkapi semua masukan terlebih dahulu sebelum melanjutkan.');
      return false;
    }

    return true;
  }

  root.querySelectorAll('input, select, textarea').forEach((input) => {
    input.addEventListener('input', () => input.classList.remove('is-invalid'));
    input.addEventListener('change', () => input.classList.remove('is-invalid'));
  });

  function enableNext(nextUrl) {
    if (!nextLink) return;
    const targetUrl = nextUrl || nextLink.dataset.nextUrl || nextLink.getAttribute('href') || '#';
    nextLink.href = targetUrl;
    nextLink.classList.remove('btn-outline-secondary', 'disabled');
    nextLink.classList.add('btn-primary');
    nextLink.setAttribute('aria-disabled', 'false');
    nextLink.innerHTML = escapeHtml(nextLink.dataset.nextLabel || 'Lanjut') + ' <i class="mdi mdi-arrow-right ms-1"></i>';
    const markButton = document.getElementById('markPrototypeTried');
    if (markButton) {
      markButton.classList.remove('btn-outline-secondary');
      markButton.classList.add('btn-outline-success');
      markButton.innerHTML = '<i class="mdi mdi-check-all me-1"></i> Sudah Dicoba';
    }
  }

  function markProgress() {
    if (hasMarkedProgress || !progressUrl) {
      enableNext();
      return;
    }

    hasMarkedProgress = true;
    fetch(progressUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then((response) => response.ok ? response.json() : null)
      .then((payload) => {
        enableNext(payload && payload.next_url ? payload.next_url : null);
      })
      .catch(() => enableNext());
  }

  stageButtons.forEach((button) => {
    button.addEventListener('click', () => setPrototypeStage(button.dataset.protoStage || 'input'));
  });

  document.getElementById('markPrototypeTried')?.addEventListener('click', () => {
    markProgress();
    setPrototypeStage('hasil', 'Prototipe ini sudah ditandai dicoba untuk membuka langkah berikutnya.');
    showNotice('Prototipe ditandai sudah dicoba. Langkah berikutnya sudah bisa dibuka.', 'success');
  });

  if (feature === 'violation-submissions') {
    const badge = document.getElementById('vsStatusBadge');
    const casePreview = document.getElementById('vsCasePreview');

    function setViolationStatus(status) {
      if (badge) {
        badge.textContent = status;
        badge.className = 'badge bg-' + (toneMap[status] || 'secondary');
      }

      root.querySelectorAll('[data-vs-step]').forEach((step) => {
        const order = ['Diajukan', 'Ditinjau', 'Diterima', 'Dikonversi'];
        const statusIndex = order.indexOf(status);
        const stepIndex = order.indexOf(step.dataset.vsStep || '');
        step.classList.toggle('is-active', stepIndex >= 0 && statusIndex >= 0 && stepIndex <= statusIndex);
      });

      if (casePreview) {
        casePreview.classList.toggle('d-none', status !== 'Dikonversi');
      }

      setPrototypeStage(
        ['Dikonversi', 'Ditolak'].includes(status) ? 'hasil' : (status === 'Diajukan' ? 'input' : 'proses'),
        'Status pengaduan berubah menjadi ' + status + ' dan riwayat simulasi ikut diperbarui.'
      );
      showNotice('Status pengaduan simulasi berubah menjadi ' + status + '.', status === 'Ditolak' ? 'warning' : 'success');
    }

    document.querySelectorAll('[data-vs-status]').forEach((button) => {
      button.addEventListener('click', () => setViolationStatus(button.dataset.vsStatus || 'Diajukan'));
    });

    document.getElementById('vsSendDemo')?.addEventListener('click', () => {
      const reporterInput = document.getElementById('vsReporter');
      const subjectInput = document.getElementById('vsSubject');
      const categoryInput = document.getElementById('vsCategory');
      const descriptionInput = document.getElementById('vsDescription');
      if (!validateRequiredInputs([reporterInput, subjectInput, categoryInput, descriptionInput], 'Lengkapi pelapor, terlapor, kategori, dan kronologi sebelum mengirim pengaduan.')) return;

      const subject = subjectInput.value;
      const category = categoryInput.value;
      const reporterRole = reporterInput.value;
      const queueBody = document.getElementById('vsQueueBody');
      const queueCount = document.getElementById('vsQueueCount');
      const id = 'PGD-2026-' + String(Math.floor(Math.random() * 800) + 100);

      if (queueBody) {
        queueBody.insertAdjacentHTML('afterbegin',
          '<tr>' +
            '<td>' + escapeHtml(id) + '</td>' +
            '<td>Pelapor Demo<br><small class="text-muted">' + escapeHtml(reporterRole) + '</small></td>' +
            '<td>' + escapeHtml(subject) + '</td>' +
            '<td>' + escapeHtml(category) + '</td>' +
            '<td><span class="badge bg-warning">Diajukan</span></td>' +
          '</tr>'
        );
      }

      if (queueCount) queueCount.textContent = String(Number(queueCount.textContent || '0') + 1);
      setViolationStatus('Diajukan');
      setPrototypeStage('proses', 'Pengaduan baru masuk ke antrian petugas untuk diverifikasi.');
      showNotice('Pengaduan simulasi berhasil masuk ke antrian petugas BK.', 'success');
    });
  }

  if (feature === 'notifications') {
    const unreadCount = document.getElementById('notifUnreadCount');
    const list = document.getElementById('notifList');

    function markAllNotificationsRead() {
      document.querySelectorAll('.notif-status').forEach((badge) => {
        badge.textContent = 'Dibaca';
        badge.className = 'badge notif-status bg-success align-self-start';
      });
      document.querySelectorAll('.notif-item').forEach((item) => item.classList.remove('bg-light'));
      if (unreadCount) unreadCount.textContent = '0';
      setPrototypeStage('hasil', 'Semua notifikasi pada pusat notifikasi sudah berstatus dibaca.');
      showNotice('Semua notifikasi simulasi ditandai sudah dibaca.', 'success');
    }

    document.getElementById('notifMarkAll')?.addEventListener('click', markAllNotificationsRead);
    document.getElementById('notifMarkAllSide')?.addEventListener('click', markAllNotificationsRead);

    document.getElementById('notifPushDemo')?.addEventListener('click', () => {
      const targetInput = document.getElementById('notifTarget');
      const titleInput = document.getElementById('notifTitle');
      const bodyInput = document.getElementById('notifBody');
      if (!validateRequiredInputs([targetInput, titleInput, bodyInput], 'Lengkapi tujuan, judul, dan isi notifikasi sebelum mengirim.')) return;

      const title = titleInput.value;
      const body = bodyInput.value;
      if (list) {
        list.insertAdjacentHTML('afterbegin',
          '<div class="border rounded p-3 mb-3 notif-item bg-light">' +
            '<div class="d-flex justify-content-between gap-3">' +
              '<div><h6 class="mb-1">' + escapeHtml(title) + '</h6>' +
              '<div class="text-muted">' + escapeHtml(body) + '</div>' +
              '<small class="text-muted">Baru saja</small></div>' +
              '<span class="badge notif-status bg-warning align-self-start">Belum dibaca</span>' +
            '</div>' +
          '</div>'
        );
      }
      if (unreadCount) unreadCount.textContent = String(Number(unreadCount.textContent || '0') + 1);
      setPrototypeStage('proses', 'Notifikasi baru dikirim dan muncul di pusat notifikasi penerima.');
      showNotice('Notifikasi simulasi masuk ke pusat notifikasi.', 'success');
    });
  }

  if (feature === 'messages') {
    const threadList = document.getElementById('messageThreadList');
    const threadCount = document.getElementById('messageThreadCount');
    const chat = document.getElementById('messageChat');

    document.getElementById('messageSendDemo')?.addEventListener('click', () => {
      const recipientInput = document.getElementById('messageRecipient');
      const subjectInput = document.getElementById('messageSubject');
      const bodyInput = document.getElementById('messageBody');
      if (!validateRequiredInputs([recipientInput, subjectInput, bodyInput], 'Lengkapi penerima, subjek, dan isi pesan sebelum mengirim.')) return;

      const recipient = recipientInput.value;
      const subject = subjectInput.value;
      const body = bodyInput.value;
      if (threadList) {
        threadList.insertAdjacentHTML('afterbegin',
          '<button type="button" class="list-group-item list-group-item-action">' +
            '<div class="d-flex justify-content-between"><span class="fw-semibold">' + escapeHtml(recipient) + '</span><small class="text-muted">Baru</small></div>' +
            '<div>' + escapeHtml(subject) + '</div>' +
            '<small class="text-muted">' + escapeHtml(body) + '</small>' +
            '<span class="badge bg-danger mt-2">Baru</span>' +
          '</button>'
        );
      }
      if (threadCount) threadCount.textContent = String(Number(threadCount.textContent || '0') + 1);
      setPrototypeStage('hasil', 'Pesan terkirim dan tercatat sebagai percakapan baru.');
      showNotice('Pesan simulasi terkirim dan muncul sebagai percakapan baru.', 'success');
    });

    document.getElementById('messageReplyBtn')?.addEventListener('click', () => {
      const replyInput = document.getElementById('messageReply');
      if (!validateRequiredInputs([replyInput], 'Isi balasan terlebih dahulu sebelum mengirim.')) return;

      const reply = replyInput.value;
      if (chat) {
        chat.insertAdjacentHTML('beforeend',
          '<div class="proto-bubble me"><div class="fw-semibold">Saya</div><div>' + escapeHtml(reply) + '</div><small class="text-muted">Baru saja</small></div>'
        );
        chat.scrollTop = chat.scrollHeight;
      }
      setPrototypeStage('proses', 'Balasan ditambahkan ke percakapan aktif dan riwayat komunikasi ikut berubah.');
      showNotice('Balasan simulasi ditambahkan ke percakapan.', 'success');
    });
  }

  if (feature === 'assessments') {
    let progress = 0;
    const progressText = document.getElementById('assessmentProgressText');
    const progressBar = document.getElementById('assessmentProgressBar');
    const resultNotice = document.getElementById('assessmentResultNotice');
    const answerGroups = Array.from(new Set(Array.from(document.querySelectorAll('.assessment-answer')).map((button) => button.closest('.btn-group')).filter(Boolean)));
    const progressStep = Math.ceil(100 / Math.max(1, answerGroups.length));

    function updateAssessmentProgress(next) {
      progress = Math.min(100, next);
      if (progressText) progressText.textContent = String(progress);
      if (progressBar) progressBar.style.width = progress + '%';
      if (resultNotice) resultNotice.classList.toggle('d-none', progress < 100);
      setPrototypeStage(progress >= 100 ? 'hasil' : 'proses', progress >= 100
        ? 'Jawaban lengkap dan ringkasan hasil dapat ditinjau.'
        : 'Siswa sedang mengisi asesmen, progres diperbarui per jawaban.');
      if (progress >= 100) {
        showNotice('Jawaban asesmen lengkap. Langkah berikutnya sudah bisa dibuka.', 'success');
      }
    }

    document.querySelectorAll('.assessment-answer').forEach((button) => {
      button.addEventListener('click', () => {
        const group = button.closest('.btn-group');
        const wasAnswered = group?.dataset.answered === '1';
        group?.querySelectorAll('.btn').forEach((btn) => {
          btn.classList.remove('active', 'btn-primary');
          btn.classList.add('btn-outline-secondary');
        });
        button.classList.add('active', 'btn-primary');
        button.classList.remove('btn-outline-secondary');
        if (group) group.dataset.answered = '1';
        updateAssessmentProgress(wasAnswered ? progress : progress + progressStep);
      });
    });

    document.getElementById('assessmentAssignDemo')?.addEventListener('click', () => {
      answerGroups.forEach((group) => {
        group.dataset.answered = '0';
        group.querySelectorAll('.btn').forEach((btn) => {
          btn.classList.remove('active', 'btn-primary');
          btn.classList.add('btn-outline-secondary');
        });
      });
      updateAssessmentProgress(0);
      setPrototypeStage('input', 'Asesmen ditugaskan, siswa dapat mulai mengisi pertanyaan.');
      showNotice('Asesmen simulasi ditugaskan kepada siswa contoh.', 'success');
    });
  }

  if (feature === 'career') {
    const savedList = document.getElementById('careerSavedList');
    const savedCount = document.getElementById('careerSavedCount');
    const referenceList = document.getElementById('careerReferenceList');

    document.querySelectorAll('.career-save').forEach((button) => {
      button.addEventListener('click', () => {
        const label = button.dataset.save || 'Pilihan baru';
        const exists = Array.from(savedList?.querySelectorAll('span') || []).some((node) => node.textContent === label);
        if (!exists && savedList) {
          savedList.insertAdjacentHTML('beforeend',
            '<li class="list-group-item d-flex justify-content-between align-items-center"><span>' + escapeHtml(label) + '</span><i class="mdi mdi-check text-success"></i></li>'
          );
          if (savedCount) savedCount.textContent = String(Number(savedCount.textContent || '0') + 1);
        }
        setPrototypeStage('hasil', 'Pilihan minat tersimpan dan siap menjadi bahan konsultasi Guru BK.');
        showNotice('Pilihan "' + label + '" tersimpan dalam rencana karier simulasi.', 'success');
      });
    });

    savedList?.addEventListener('click', (event) => {
      const button = event.target.closest('.career-remove');
      if (!button) return;
      const item = button.closest('.list-group-item');
      const label = item?.querySelector('span')?.textContent || 'Pilihan';
      item?.remove();
      if (savedCount) savedCount.textContent = String(Math.max(0, Number(savedCount.textContent || '0') - 1));
      setPrototypeStage('hasil', 'Pilihan minat dihapus dari daftar simulasi siswa.');
      showNotice('Pilihan "' + label + '" dihapus dari rencana karier simulasi.', 'warning');
    });

    document.getElementById('careerAddReference')?.addEventListener('click', () => {
      const nameInput = document.getElementById('careerRefName');
      const typeInput = document.getElementById('careerRefType');
      const descriptionInput = document.getElementById('careerRefDescription');
      if (!validateRequiredInputs([nameInput, typeInput, descriptionInput], 'Lengkapi nama, jenis, dan keterangan referensi sebelum menambahkan.')) return;

      const name = nameInput.value;
      const type = typeInput.value;
      const description = descriptionInput.value;

      if (referenceList) {
        referenceList.insertAdjacentHTML('afterbegin',
          '<div class="border rounded p-3 mb-3">' +
            '<h6 class="mb-1">' + escapeHtml(name) + '</h6>' +
            '<div>' + escapeHtml(type) + '</div>' +
            '<small class="text-muted">' + escapeHtml(description) + '</small>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary d-block mt-3"><i class="mdi mdi-pencil-outline me-1"></i> Edit Simulasi</button>' +
          '</div>'
        );
      }

      setPrototypeStage('hasil', 'Referensi karier dan studi lanjut baru tampil di daftar simulasi.');
      showNotice('Referensi "' + name + '" ditambahkan oleh mode Guru BK.', 'success');
    });
  }

  setPrototypeStage('input');
})();
</script>
<?= $this->endSection() ?>
