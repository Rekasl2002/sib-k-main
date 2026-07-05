<?php
/**
 * File: app/Views/role_features/bk_schedule/index.php
 * Halaman terpadu "Jadwal Kegiatan/Acara BK" untuk peran read-only
 * (Siswa, Orang Tua, Wali Kelas). Melebur 5 layanan BK menjadi satu halaman,
 * satu kartu per layanan. JADWAL SAJA (tanggal–waktu–lokasi) tanpa detail catatan.
 *
 * Variabel yang diharapkan dari controller:
 * - $schedule (array [serviceType => rows])         : entri jadwal akan datang
 * - $role ('siswa'|'orang-tua'|'wali-kelas')
 * - $showDetailEye (bool)                            : Wali Kelas, tampilkan ikon mata utk entri yang diizinkan
 * - $showAssessmentReminder (bool) + $assessmentReminders (array) : khusus Orang Tua
 * - $historyUrl (string|null)                        : Wali Kelas, tautan ke Riwayat
 * - $isHistory (bool)                                : true bila ini halaman Riwayat (Wali Kelas)
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$schedule = is_array($schedule ?? null) ? $schedule : [];
$role = (string) ($role ?? 'siswa');
$showDetailEye = ! empty($showDetailEye);
$showAssessmentReminder = ! empty($showAssessmentReminder);
$assessmentReminders = is_array($assessmentReminders ?? null) ? $assessmentReminders : [];
$historyUrl = $historyUrl ?? null;
$isHistory = ! empty($isHistory);
$title = (string) ($title ?? 'Jadwal Kegiatan/Acara BK');

// Konfigurasi tampilan tiap jenis layanan + segmen rute (untuk ikon mata Wali Kelas).
$serviceConfig = [
    'Bimbingan'            => ['icon' => 'mdi-account-group-outline',  'seg' => 'guidance'],
    'Konseling'           => ['icon' => 'mdi-account-heart-outline',  'seg' => 'counseling'],
    'Kolaborasi Orang Tua' => ['icon' => 'mdi-account-child-outline',  'seg' => 'parent-collaborations'],
    'Kunjungan Rumah'      => ['icon' => 'mdi-home-heart',             'seg' => 'home-visits'],
    'Konferensi Kasus'     => ['icon' => 'mdi-account-multiple-check-outline', 'seg' => 'case-conferences'],
];

// Kalimat menenangkan/menyemangati per peran (terutama untuk kegiatan yang bisa terasa berat).
$calmIntro = match ($role) {
    'orang-tua' => 'Kegiatan-kegiatan berikut adalah bentuk perhatian sekolah untuk mendampingi ananda. Mohon dukungannya, ya.',
    'wali-kelas' => 'Berikut jadwal kegiatan/acara BK untuk siswa kelas perwalian Anda.',
    default => 'Kegiatan berikut adalah bagian dari pendampingan BK untuk membantu perkembanganmu — tetap tenang dan semangat, ya.',
};

$calmService = [
    'Konferensi Kasus' => match ($role) {
        'orang-tua' => 'Pertemuan bersama untuk mencari jalan terbaik bagi ananda. Tidak perlu khawatir.',
        'wali-kelas' => 'Pertemuan koordinasi untuk membahas pendampingan siswa.',
        default => 'Ini pertemuan untuk mencari solusi terbaik bersama. Tetap tenang, kamu tidak sendiri.',
    },
];

$idDate = static function (?string $raw): string {
    if (empty($raw)) return '-';
    $ts = strtotime((string) $raw);
    if ($ts === false) return esc($raw);
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $hari[(int) date('w', $ts)] . ', ' . (int) date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
};
$idTime = static function (?string $raw): ?string {
    if (empty($raw)) return null;
    $ts = strtotime((string) $raw);
    if ($ts === false) return null;
    // Hanya tampilkan jam bila memang ada komponen waktu.
    if (date('H:i:s', $ts) === '00:00:00' && ! str_contains((string) $raw, ':')) return null;
    return date('H:i', $ts) . ' WIB';
};

$totalUpcoming = 0;
foreach ($schedule as $rows) { $totalUpcoming += count($rows); }

// Breadcrumb: halaman ini dipakai bersama Siswa/Orang Tua/Wali Kelas — peran
// ditentukan dari segmen pertama URL.
$__crumbSeg   = (string) (service('uri')->getSegment(1) ?? '');
$__crumbRoles = [
    'student'  => ['student/dashboard', 'Siswa'],
    'parent'   => ['parent/dashboard', 'Orang Tua'],
    'homeroom' => ['homeroom/dashboard', 'Wali Kelas'],
];
$__crumbRole = $__crumbRoles[$__crumbSeg] ?? null;
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-sm-0 text-dark"><?= esc($title) ?></h4>
        <p class="text-dark mb-0">
          <?= $isHistory ? 'Arsip kegiatan/acara BK yang sudah lewat.' : 'Jadwal kegiatan/acara BK yang akan datang.' ?>
        </p>
      </div>
      <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
        <?php if ($__crumbRole): ?>
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url($__crumbRole[0]) ?>"><?= esc($__crumbRole[1]) ?></a></li>
            <li class="breadcrumb-item active"><?= esc($title) ?></li>
          </ol>
        <?php endif; ?>
        <?php if ($historyUrl): ?>
          <div class="btn-group">
            <?php if ($isHistory): ?>
              <a href="<?= esc($historyUrl['back'] ?? '#', 'attr') ?>" class="btn btn-outline-primary"><i class="mdi mdi-calendar-clock me-1"></i> Jadwal Akan Datang</a>
            <?php else: ?>
              <a href="<?= esc($historyUrl['history'] ?? '#', 'attr') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-history me-1"></i> Riwayat</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php foreach (['success' => 'check-circle', 'error' => 'alert-circle', 'info' => 'information'] as $type => $icon): ?>
  <?php if (session()->getFlashdata($type)): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
      <i class="mdi mdi-<?= $icon ?> me-2"></i><?= esc(session()->getFlashdata($type)) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<?php if (! $isHistory): ?>
  <div class="alert alert-info" role="alert">
    <i class="mdi mdi-hand-heart me-2"></i><?= esc($calmIntro) ?>
  </div>
<?php endif; ?>

<?php if ($showAssessmentReminder && ! empty($assessmentReminders)): ?>
  <div class="card border-warning">
    <div class="card-header bg-soft-warning">
      <h5 class="card-title mb-0 text-dark"><i class="mdi mdi-clipboard-alert-outline me-2"></i>Pengingat Asesmen Ananda</h5>
    </div>
    <div class="card-body">
      <p class="text-dark">Mohon ingatkan ananda untuk mengerjakan asesmen berikut sebelum batas waktunya.</p>
      <div class="row">
        <?php foreach ($assessmentReminders as $a): ?>
          <div class="col-md-6">
            <div class="border rounded p-3 mb-2 d-flex">
              <i class="mdi mdi-clipboard-text-clock-outline font-size-24 text-warning me-3"></i>
              <div>
                <div class="fw-semibold text-dark"><?= esc($a['title'] ?? 'Asesmen') ?></div>
                <?php if (! empty($a['child_name'])): ?>
                  <small class="text-dark d-block">Untuk: <?= esc($a['child_name']) ?></small>
                <?php endif; ?>
                <?php if (! empty($a['due'])): ?>
                  <small class="text-dark d-block">Batas: <?= $idDate($a['due']) ?></small>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($totalUpcoming === 0): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="mdi mdi-calendar-check text-dark" style="font-size:56px;"></i>
      <p class="text-dark mt-3 mb-0">
        <?= $isHistory ? 'Belum ada arsip kegiatan/acara BK.' : 'Tidak ada jadwal kegiatan/acara BK untuk saat ini.' ?>
      </p>
    </div>
  </div>
<?php else: ?>
  <div class="row">
    <?php foreach ($serviceConfig as $type => $cfg): ?>
      <?php $rows = $schedule[$type] ?? []; if (! $rows) continue; ?>
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center">
            <i class="mdi <?= esc($cfg['icon'], 'attr') ?> font-size-20 me-2 text-primary"></i>
            <h5 class="card-title mb-0 text-dark"><?= esc($type) ?></h5>
            <span class="badge bg-primary ms-auto"><?= count($rows) ?></span>
          </div>
          <div class="card-body">
            <?php if (! empty($calmService[$type]) && ! $isHistory): ?>
              <p class="text-dark fst-italic"><i class="mdi mdi-information-outline me-1"></i><?= esc($calmService[$type]) ?></p>
            <?php endif; ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($rows as $row): ?>
                <?php
                  $raw = ! empty($row['scheduled_at']) ? $row['scheduled_at'] : ($row['held_at'] ?? null);
                  $waktu = $idTime($raw);
                  $canSeeDetail = $showDetailEye && ! empty($row['visible_to_homeroom']);
                ?>
                <li class="list-group-item px-0">
                  <div class="d-flex">
                    <div class="flex-shrink-0 text-center me-3">
                      <span class="avatar-title bg-soft-primary text-primary rounded"><i class="mdi mdi-calendar"></i></span>
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-semibold text-dark"><?= $idDate($raw) ?></div>
                      <div class="text-dark">
                        <?php if ($waktu): ?><i class="mdi mdi-clock-outline me-1"></i><?= esc($waktu) ?><?php else: ?><span class="text-dark">Waktu menyusul</span><?php endif; ?>
                      </div>
                      <?php if (! empty($row['location'])): ?>
                        <div class="text-dark"><i class="mdi mdi-map-marker-outline me-1"></i><?= esc($row['location']) ?></div>
                      <?php endif; ?>
                      <?php if ($showDetailEye): ?>
                        <?php if ($canSeeDetail): ?>
                          <a href="<?= site_url('homeroom/' . $cfg['seg'] . '/show/' . (int) $row['id']) ?>" class="btn btn-sm btn-outline-info mt-2" title="Lihat detail" data-bs-toggle="tooltip">
                            <i class="mdi mdi-eye me-1"></i> Lihat Detail
                          </a>
                        <?php else: ?>
                          <small class="text-dark d-block mt-1"><i class="mdi mdi-lock-outline me-1"></i>Jadwal saja</small>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $(document).ready(function () {
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) { return new bootstrap.Tooltip(el); });
  });
</script>
<?= $this->endSection() ?>
