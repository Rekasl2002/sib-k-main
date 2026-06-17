<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$currentUser    = is_array($currentUser ?? null) ? $currentUser : (array) ($currentUser ?? []);
$activeAcademic = is_array($activeAcademic ?? null) ? $activeAcademic : (array) ($activeAcademic ?? []);
$ay = trim((string) ($activeAcademic['year'] ?? ''));
$sem = trim((string) ($activeAcademic['semester'] ?? ''));
?>


<?php
use CodeIgniter\I18n\Time;

// ✅ tambah helper auth supaya bisa pakai user_avatar() (konsisten dengan halaman lain)
helper(['url', 'auth']);

function h($v){ return esc($v ?? ''); }
function dt($d,$t=null){
  if(!$d) return '-';
  try {
    $x = Time::parse($d);
    $s = $x->toLocalizedString('dd MMM yyyy');
    if ($t) $s .= ' ' . h($t);
    return $s;
  } catch (\Throwable $e) {
    return h($d);
  }
}
function badgeClass($status){
  return match((string)$status){
    'Dijadwalkan' => 'bg-info',
    'Selesai'     => 'bg-success',
    'Dibatalkan'  => 'bg-secondary',
    'Ditunda'     => 'bg-warning',
    'Tidak Hadir' => 'bg-danger',
    default       => 'bg-light text-dark',
  };
}

// Normalisasi variabel dari controller
$children         = $children ?? [];
$stats            = $stats ?? null;
$upcoming         = $upcoming ?? [];

// Peta id anak -> nama (untuk fallback di widget)
$childNameMap = [];
foreach ($children as $c) { $childNameMap[$c['id']] = $c['full_name'] ?? ('Siswa #'.$c['id']); }

// Deteksi kolom opsional per anak (jika controller menyiapkan)
$hasUpcoming  = array_reduce($children, fn($carry,$r)=>$carry || isset($r['upcoming_sessions']), false);

// ✅ Default avatar svg (sesuai public/assets/images/users/default-avatar.svg)
$defaultAvatar = base_url('assets/images/users/default-avatar.svg');

// ✅ Avatar helper (robust + konsisten): kosong/placeholder/template -> default svg
function avatar_url($row): string {
  $defaultAvatar = base_url('assets/images/users/default-avatar.svg');

  $photoRaw  = (string)($row['profile_photo'] ?? '');
  $photoTrim = trim($photoRaw);
  $photoNorm = strtolower(ltrim(str_replace('\\', '/', $photoTrim), '/'));
  $photoBase = strtolower(basename($photoNorm));

  $placeholders = [
    'default-avatar.png','default-avatar.jpg','default-avatar.jpeg','default-avatar.svg',
    'avatar.png','avatar.jpg','avatar.jpeg',
    'user.png','user.jpg','user.jpeg',
    'no-image.png','noimage.png','placeholder.png','blank.png',
  ];

  if ($photoTrim === '') {
    $photo = null;
  }
  // jika menunjuk ke assets/ (avatar template) → dianggap tidak ada foto (kecuali default-avatar.svg kita)
  elseif ((strpos($photoNorm, 'assets/') === 0 || strpos($photoNorm, 'public/assets/') === 0)
      && $photoNorm !== 'assets/images/users/default-avatar.svg'
  ) {
    $photo = null;
  }
  // jika filename placeholder → dianggap tidak ada foto (kecuali default-avatar.svg kita)
  elseif (in_array($photoBase, $placeholders, true) && $photoNorm !== 'assets/images/users/default-avatar.svg') {
    $photo = null;
  } else {
    $photo = $photoTrim;
  }

  // gunakan user_avatar() jika tersedia; fallback aman jika helper berubah
  if (function_exists('user_avatar')) {
    $src = user_avatar($photo);
    return $src ?: $defaultAvatar;
  }

  return $photo ? base_url($photo) : $defaultAvatar;
}
?>

<!-- Welcome Card Orang Tua -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card welcome-card">
            <div class="card-body">
                <?php
                $currentUser    = is_array($currentUser ?? null) ? $currentUser : (array) ($currentUser ?? []);
                $activeAcademic = is_array($activeAcademic ?? null) ? $activeAcademic : (array) ($activeAcademic ?? []);
                $ay  = trim((string) ($activeAcademic['year'] ?? ''));
                $sem = trim((string) ($activeAcademic['semester'] ?? ''));
                ?>

                <h4 class="text-white mb-2">
                    Selamat Datang, <?= esc($currentUser['full_name'] ?? 'Orang Tua') ?>!
                </h4>

                <p class="text-white-50 mb-2">
                    Anda login sebagai <strong>Orang Tua/Wali</strong>
                    <?php if ($ay !== '' && $sem !== ''): ?>
                        <span class="ms-1">• Tahun Ajaran <?= esc($ay) ?> Semester <?= esc($sem) ?></span>
                    <?php elseif ($ay !== ''): ?>
                        <span class="ms-1">• Tahun Ajaran <?= esc($ay) ?></span>
                    <?php endif; ?>
                </p>

                <p class="text-white-50 mb-0" style="max-width: 900px;">
                    Pantau perkembangan anak, jadwal konseling, dan ringkasan layanan BK.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Title / Breadcrumb -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0">Dashboard ORANG TUA</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-3">
<div class="col-md-6">
  <div class="card shadow-sm h-100">
    <div class="card-body">
      <div class="text-dark small">Total Anak Terdaftar</div>
      <div class="display-6"><?= (int)($stats['children'] ?? count($children)) ?></div>
    </div>
  </div>
</div>
<div class="col-md-6">
  <div class="card shadow-sm h-100">
    <div class="card-body">
      <div class="text-dark small">Jadwal Konseling Mendatang</div>
      <div class="display-6"><?= (int)($stats['upcoming_sessions'] ?? count($upcoming)) ?></div>
    </div>
  </div>
</div>
</div>

<!-- Akses Cepat (daftar anak dipindah ke menu "Daftar Anak" di sidebar) -->
<?php
helper('url');
$quickShortcuts = [
  ['label' => 'Daftar Anak', 'url' => base_url('parent/children'), 'icon' => 'mdi-account-child-circle', 'color' => 'primary'],
  ['label' => 'Jadwal Kegiatan/Acara BK', 'url' => base_url('parent/jadwal-bk'), 'icon' => 'mdi-calendar-heart', 'color' => 'success'],
];
if (! function_exists('consultation_role_can_view') || consultation_role_can_view('orang tua')) {
  $quickShortcuts[] = ['label' => 'Konsultasi & Pengaduan', 'url' => base_url('parent/consultations'), 'icon' => 'mdi-message-alert-outline', 'color' => 'warning'];
}
$quickShortcuts[] = ['label' => 'Info Karier & Studi', 'url' => base_url('parent/career'), 'icon' => 'mdi-school-outline', 'color' => 'info'];
$quickShortcuts[] = ['label' => 'Laporan Anak', 'url' => base_url('parent/reports/children'), 'icon' => 'mdi-file-chart', 'color' => 'secondary'];
$quickShortcuts[] = ['label' => 'Pesan', 'url' => base_url('parent/messages'), 'icon' => 'mdi-email', 'color' => 'primary'];
echo $this->include('role_features/_quick_actions');
?>

<div class="row g-3">
<!-- Jadwal Konseling Mendatang -->
<div class="col-lg-6">
  <div class="card shadow-sm h-100">
    <div class="card-body">
      <h6 class="mb-3">Jadwal Konseling Mendatang</h6>
      <?php if (empty($upcoming)): ?>
        <div class="text-dark">Belum ada jadwal.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Tanggal</th><th>Anak</th><th>Status</th><th>Lokasi</th></tr></thead>
            <tbody>
            <?php foreach ($upcoming as $u): ?>
              <tr>
                <td><?= dt($u['session_date'] ?? null, $u['session_time'] ?? null) ?></td>
                <td>
                  <?php
                    // Prefer data langsung dari controller; fallback ke peta anak
                    $sid   = $u['student_id'] ?? null;
                    $sname = $u['full_name']  ?? ($sid ? ($childNameMap[$sid] ?? null) : null);
                  ?>
                  <?php if ($sid): ?>
                    <a href="<?= route_to('parent.children.sessions', (int)$sid) ?>"><?= h($sname ?? '—') ?></a>
                  <?php else: ?>
                    <?= h($sname ?? '—') ?>
                  <?php endif; ?>
                </td>
                <td><span class="badge <?= badgeClass($u['status'] ?? null) ?>"><?= h($u['status'] ?? '-') ?></span></td>
                <td><?= h($u['location'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
